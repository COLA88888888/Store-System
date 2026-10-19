<?php
// ============================================================
// customer_debts_backend.php — ລູກຄ້າຕິດໜີ້ (customer debts)
// Controller for pages/customer_debts/customer_debts.php AND a direct AJAX target:
//   debt_detail  — one customer's debt bills + repayment history (JSON)
//   pay_debt     — receive a repayment, for one bill or spread oldest-first over all of them
//   add_debt     — enter an opening balance owed from before the system (no sale bill)
//   cancel_debt  — cancel a hand-entered debt (a POS credit bill is cancelled by deleting the bill)
// Debts from the POS are created by api/pos_backend.php checkout (pay_mode = credit).
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

$isAjaxDebt = $_SERVER['REQUEST_METHOD'] === 'POST'
    && in_array($_POST['action'] ?? '', ['debt_detail', 'pay_debt', 'add_debt', 'cancel_debt', 'search_customers'], true);

if (empty($_SESSION['user_id']) || !hasPermission('customer_debt')) {
    if ($isAjaxDebt) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => t('customer_debt.err_no_permission', 'ທ່ານບໍ່ມີສິດເຂົ້າເຖິງລູກຄ້າຕິດໜີ້!')]);
        exit();
    }
    $bp = $base_path ?? '../../';
    echo "<script>window.top.location.href = '" . $bp . "index.php';</script>";
    exit();
}

if (!function_exists('debtStatusFor')) {
    // unpaid | partial | paid, from what has been repaid so far (cancelled is set explicitly).
    function debtStatusFor($amount, $paid) {
        if ($paid <= 0.004) return 'unpaid';
        return ($amount - $paid) <= 0.004 ? 'paid' : 'partial';
    }
}

$debtStoreId = getActiveStoreId($pdo);
$debtUser    = $_SESSION['username'] ?? 'user';

if ($isAjaxDebt) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    // --- customer picker: live search by name / phone / code / member card ---
    // Best matches first (exact phone or code, then name starting with the text, then
    // anywhere), capped at 20 so a shop with thousands of customers stays instant.
    if ($action === 'search_customers') {
        $q = trim($_POST['q'] ?? '');
        $params = [$debtStoreId, $debtStoreId];
        $where = '';
        $order = 'c.customer_name ASC';
        if ($q !== '') {
            $like = '%' . $q . '%';
            $where = ' AND (c.customer_name LIKE ? OR c.phone LIKE ? OR c.customer_code LIKE ? OR c.member_card LIKE ?)';
            array_push($params, $like, $like, $like, $like);
            $order = '(c.phone = ? OR c.customer_code = ? OR c.member_card = ?) DESC, (c.customer_name LIKE ?) DESC, c.customer_name ASC';
            array_push($params, $q, $q, $q, $q . '%');
        }
        $st = $pdo->prepare("
            SELECT c.customer_id, c.customer_code, c.customer_name, c.phone, c.member_card,
                   COALESCE((SELECT SUM(d.balance) FROM st_customer_debts d
                             WHERE d.customer_id = c.customer_id AND d.store_id = ? AND d.status IN ('unpaid','partial')), 0) AS balance
            FROM st_customers c
            WHERE (c.store_id = ? OR c.store_id = 0) $where
            ORDER BY $order
            LIMIT 20");
        $st->execute($params);
        echo json_encode(['success' => true, 'customers' => $st->fetchAll(PDO::FETCH_ASSOC)]);
        exit();
    }

    // --- one customer's bills + repayments ---
    if ($action === 'debt_detail') {
        $customerId = intval($_POST['customer_id'] ?? 0);
        $st = $pdo->prepare("SELECT * FROM st_customer_debts WHERE customer_id = ? AND store_id = ? ORDER BY created_at ASC, id ASC");
        $st->execute([$customerId, $debtStoreId]);
        $debts = $st->fetchAll(PDO::FETCH_ASSOC);

        $sp = $pdo->prepare("SELECT p.*, d.invoice_number, b.bank_logo, b.bank_code, b.account_number AS bank_account_no
                             FROM st_customer_debt_payments p
                             LEFT JOIN st_customer_debts d ON d.id = p.debt_id
                             LEFT JOIN st_bank_accounts b ON b.id = p.bank_account_id
                             WHERE p.customer_id = ? AND p.store_id = ? ORDER BY p.paid_at DESC, p.id DESC");
        $sp->execute([$customerId, $debtStoreId]);
        $payments = [];
        foreach ($sp->fetchAll(PDO::FETCH_ASSOC) as $p) {
            // Transfer → the logo of the bank that received it (shown beside the bank name)
            $p['bank_logo_url'] = !empty($p['bank_account_id']) ? resolveBankLogo($p['bank_logo'] ?? '', $p['bank_code'] ?? '') : '';
            unset($p['bank_logo']);
            $payments[] = $p;
        }
        echo json_encode(['success' => true, 'debts' => $debts, 'payments' => $payments]);
        exit();
    }

    // --- receive a repayment ---
    if ($action === 'pay_debt') {
        if (!hasPermission('customer_debt', 'edit')) {
            echo json_encode(['success' => false, 'message' => t('customer_debt.err_no_pay_permission', 'ທ່ານບໍ່ມີສິດຮັບຊຳລະໜີ້!')]);
            exit();
        }
        $customerId = intval($_POST['customer_id'] ?? 0);
        $debtId     = intval($_POST['debt_id'] ?? 0);
        $amount     = round(floatval(str_replace(',', '', $_POST['amount'] ?? '0')), 2);
        $method     = ($_POST['payment_method'] ?? '') === 'transfer' ? 'ໂອນເງິນ / QR' : 'ເງິນສົດ';
        $note       = mb_substr(trim($_POST['note'] ?? ''), 0, 255);
        $bankId     = $method === 'ເງິນສົດ' ? null : (intval($_POST['bank_account_id'] ?? 0) ?: null);
        $bankName   = null;

        if ($amount <= 0) {
            echo json_encode(['success' => false, 'message' => t('customer_debt.err_amount', 'ກະລຸນາປ້ອນຈຳນວນເງິນທີ່ຖືກຕ້ອງ!')]);
            exit();
        }
        if ($bankId) {
            $bs = $pdo->prepare("SELECT COALESCE(NULLIF(bank_name, ''), bank_code) FROM st_bank_accounts WHERE id = ? AND is_active = 1 AND (store_id = ? OR store_id = 0)");
            $bs->execute([$bankId, $debtStoreId]);
            $bankName = $bs->fetchColumn() ?: null;
            if (!$bankName) $bankId = null;
        }
        // A transfer must say which bank received it (so the income can be traced), whenever
        // the branch has any bank account set up.
        if ($method !== 'ເງິນສົດ' && !$bankId) {
            $hasBanks = $pdo->prepare("SELECT COUNT(*) FROM st_bank_accounts WHERE is_active = 1 AND (store_id = ? OR store_id = 0)");
            $hasBanks->execute([$debtStoreId]);
            if ((int)$hasBanks->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => t('customer_debt.err_bank', 'ກະລຸນາເລືອກທະນາຄານທີ່ຮັບເງິນໂອນ!')]);
                exit();
            }
        }

        try {
            $pdo->beginTransaction();
            // Lock the open bills being paid so two tills can't over-collect the same debt.
            if ($debtId > 0) {
                $ld = $pdo->prepare("SELECT * FROM st_customer_debts WHERE id = ? AND store_id = ? AND status IN ('unpaid','partial') FOR UPDATE");
                $ld->execute([$debtId, $debtStoreId]);
            } else {
                $ld = $pdo->prepare("SELECT * FROM st_customer_debts WHERE customer_id = ? AND store_id = ? AND status IN ('unpaid','partial') ORDER BY created_at ASC, id ASC FOR UPDATE");
                $ld->execute([$customerId, $debtStoreId]);
            }
            $open = $ld->fetchAll(PDO::FETCH_ASSOC);
            $totalOpen = array_sum(array_map(function ($d) { return (float)$d['balance']; }, $open));

            if (empty($open)) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => t('customer_debt.err_no_open', 'ບໍ່ມີໜີ້ຄ້າງຊຳລະ!')]);
                exit();
            }
            if ($amount - $totalOpen > 0.004) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => sprintf(t('customer_debt.err_over', 'ຈຳນວນເງິນເກີນຍອດໜີ້ຄ້າງ (%s)'), formatCurrency($totalOpen))]);
                exit();
            }

            // Oldest bill first; each bill gets its own payment row so the history shows
            // exactly which bill a repayment cleared.
            $ins = $pdo->prepare("INSERT INTO st_customer_debt_payments (debt_id, store_id, customer_id, amount, payment_method, bank_account_id, bank_name, note, received_by, paid_at)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $upd = $pdo->prepare("UPDATE st_customer_debts SET paid_amount = ?, balance = ?, status = ? WHERE id = ?");
            $left = $amount;
            $paidBills = [];
            foreach ($open as $d) {
                if ($left <= 0.004) break;
                $take = min($left, (float)$d['balance']);
                $newPaid = round((float)$d['paid_amount'] + $take, 2);
                $newBal  = round((float)$d['amount'] - $newPaid, 2);
                $ins->execute([$d['id'], $debtStoreId, $d['customer_id'], $take, $method, $bankId, $bankName, $note ?: null, $debtUser]);
                $upd->execute([$newPaid, max(0, $newBal), debtStatusFor((float)$d['amount'], $newPaid), $d['id']]);
                $paidBills[] = $d['invoice_number'] ?: ('#' . $d['id']);
                $left = round($left - $take, 2);
            }
            $pdo->commit();

            logActivity($pdo, 'ຮັບຊຳລະໜີ້ລູກຄ້າ', ($open[0]['customer_name'] ?? '') . ' — ' . formatCurrency($amount) . ' (' . implode(', ', $paidBills) . ')');
            echo json_encode([
                'success'   => true,
                'message'   => sprintf(t('customer_debt.msg_paid', 'ຮັບຊຳລະໜີ້ %s ສຳເລັດ!'), formatCurrency($amount)),
                'remaining' => round($totalOpen - $amount, 2),
            ]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => t('customer_debt.err_generic', 'ເກີດຂໍ້ຜິດພາດ: ') . $e->getMessage()]);
        }
        exit();
    }

    // --- opening balance (debt from before the system / outside the POS) ---
    if ($action === 'add_debt') {
        if (!hasPermission('customer_debt', 'add')) {
            echo json_encode(['success' => false, 'message' => t('customer_debt.err_no_add_permission', 'ທ່ານບໍ່ມີສິດເພີ່ມໜີ້!')]);
            exit();
        }
        $customerId = intval($_POST['customer_id'] ?? 0);
        $amount     = round(floatval(str_replace(',', '', $_POST['amount'] ?? '0')), 2);
        $dueDate    = trim($_POST['due_date'] ?? '');
        $dueDate    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate) ? $dueDate : null;
        $note       = mb_substr(trim($_POST['note'] ?? ''), 0, 255);

        $cs = $pdo->prepare("SELECT customer_id, customer_name FROM st_customers WHERE customer_id = ? AND (store_id = ? OR store_id = 0)");
        $cs->execute([$customerId, $debtStoreId]);
        $cust = $cs->fetch(PDO::FETCH_ASSOC);
        if (!$cust) {
            echo json_encode(['success' => false, 'message' => t('customer_debt.err_customer', 'ກະລຸນາເລືອກລູກຄ້າ!')]);
            exit();
        }
        if ($amount <= 0) {
            echo json_encode(['success' => false, 'message' => t('customer_debt.err_amount', 'ກະລຸນາປ້ອນຈຳນວນເງິນທີ່ຖືກຕ້ອງ!')]);
            exit();
        }
        $pdo->prepare("INSERT INTO st_customer_debts (store_id, customer_id, customer_name, invoice_number, bill_amount, deposit_amount, amount, paid_amount, balance, status, due_date, note, created_by, created_at)
                       VALUES (?, ?, ?, NULL, ?, 0, ?, 0, ?, 'unpaid', ?, ?, ?, NOW())")
            ->execute([$debtStoreId, $cust['customer_id'], $cust['customer_name'], $amount, $amount, $amount, $dueDate, $note ?: null, $debtUser]);
        logActivity($pdo, 'ເພີ່ມໜີ້ລູກຄ້າ', $cust['customer_name'] . ' — ' . formatCurrency($amount));
        echo json_encode(['success' => true, 'message' => t('customer_debt.msg_added', 'ບັນທຶກໜີ້ສຳເລັດ!')]);
        exit();
    }

    // --- cancel a hand-entered debt ---
    if ($action === 'cancel_debt') {
        if (!hasPermission('customer_debt', 'delete')) {
            echo json_encode(['success' => false, 'message' => t('customer_debt.err_no_del_permission', 'ທ່ານບໍ່ມີສິດຍົກເລີກໜີ້!')]);
            exit();
        }
        $debtId = intval($_POST['debt_id'] ?? 0);
        $ds = $pdo->prepare("SELECT * FROM st_customer_debts WHERE id = ? AND store_id = ?");
        $ds->execute([$debtId, $debtStoreId]);
        $debt = $ds->fetch(PDO::FETCH_ASSOC);
        if (!$debt || $debt['status'] === 'cancelled') {
            echo json_encode(['success' => false, 'message' => t('customer_debt.err_not_found', 'ບໍ່ພົບລາຍການໜີ້!')]);
            exit();
        }
        if (!empty($debt['invoice_number'])) {
            echo json_encode(['success' => false, 'message' => t('customer_debt.err_cancel_invoice', 'ໜີ້ຈາກບິນຂາຍ ຕ້ອງຍົກເລີກໂດຍການລົບບິນຂາຍໃນລາຍງານ')]);
            exit();
        }
        if ((float)$debt['paid_amount'] > 0) {
            echo json_encode(['success' => false, 'message' => t('customer_debt.err_cancel_paid', 'ໜີ້ນີ້ມີການຊຳລະແລ້ວ ບໍ່ສາມາດຍົກເລີກໄດ້')]);
            exit();
        }
        $pdo->prepare("UPDATE st_customer_debts SET status = 'cancelled', balance = 0 WHERE id = ?")->execute([$debtId]);
        logActivity($pdo, 'ຍົກເລີກໜີ້ລູກຄ້າ', $debt['customer_name'] . ' — ' . formatCurrency($debt['amount']));
        echo json_encode(['success' => true, 'message' => t('customer_debt.msg_cancelled', 'ຍົກເລີກໜີ້ສຳເລັດ!')]);
        exit();
    }
}

// ============================================================
// Page-load data
// ============================================================
$debtSearch = trim($_GET['q'] ?? '');
$debtStatus = $_GET['status'] ?? 'open';
if (!in_array($debtStatus, ['open', 'overdue', 'paid', 'all'], true)) $debtStatus = 'open';

// Summary tiles (whole branch, ignoring the filters)
$debtSummary = ['outstanding' => 0, 'customers' => 0, 'overdue' => 0, 'collected_today' => 0];
try {
    $s = $pdo->prepare("SELECT COALESCE(SUM(balance), 0) AS outstanding,
                               COUNT(DISTINCT customer_id) AS customers,
                               COALESCE(SUM(CASE WHEN due_date IS NOT NULL AND due_date < CURDATE() THEN balance ELSE 0 END), 0) AS overdue
                        FROM st_customer_debts WHERE store_id = ? AND status IN ('unpaid','partial')");
    $s->execute([$debtStoreId]);
    $debtSummary = array_merge($debtSummary, $s->fetch(PDO::FETCH_ASSOC));
    $s = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM st_customer_debt_payments WHERE store_id = ? AND DATE(paid_at) = CURDATE()");
    $s->execute([$debtStoreId]);
    $debtSummary['collected_today'] = $s->fetchColumn();
} catch (Throwable $e) {}

// One row per customer
$having = [
    'open'    => 'HAVING balance > 0',
    'overdue' => 'HAVING overdue > 0',
    'paid'    => 'HAVING balance <= 0',
    'all'     => '',
][$debtStatus];
$where  = "d.store_id = ? AND d.status <> 'cancelled'";
$params = [$debtStoreId];
if ($debtSearch !== '') {
    $where .= " AND (d.customer_name LIKE ? OR c.phone LIKE ? OR c.customer_code LIKE ? OR d.invoice_number LIKE ?)";
    $like = '%' . $debtSearch . '%';
    array_push($params, $like, $like, $like, $like);
}
$debtCustomers = [];
try {
    $st = $pdo->prepare("
        SELECT d.customer_id,
               MAX(COALESCE(c.customer_name, d.customer_name)) AS customer_name,
               MAX(c.phone) AS phone,
               MAX(c.customer_code) AS customer_code,
               COUNT(*) AS bills,
               SUM(d.status IN ('unpaid','partial')) AS open_bills,
               SUM(d.amount) AS total_amount,
               SUM(d.paid_amount) AS paid_amount,
               SUM(CASE WHEN d.status IN ('unpaid','partial') THEN d.balance ELSE 0 END) AS balance,
               SUM(CASE WHEN d.status IN ('unpaid','partial') AND d.due_date IS NOT NULL AND d.due_date < CURDATE() THEN d.balance ELSE 0 END) AS overdue,
               MIN(CASE WHEN d.status IN ('unpaid','partial') THEN d.created_at END) AS oldest_open,
               MAX(d.created_at) AS last_debt
        FROM st_customer_debts d
        LEFT JOIN st_customers c ON c.customer_id = d.customer_id
        WHERE $where
        GROUP BY d.customer_id
        $having
        ORDER BY balance DESC, last_debt DESC");
    $st->execute($params);
    $debtCustomers = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

// Paging (one row per customer, so slicing the grouped result is cheap)
$debtPerPageRaw = $_GET['per_page'] ?? '10';
if (!in_array($debtPerPageRaw, ['10', '25', '50', '100', 'all'], true)) $debtPerPageRaw = '10';
$debtTotalRows  = count($debtCustomers);
$debtPerPage    = $debtPerPageRaw === 'all' ? max(1, $debtTotalRows) : (int)$debtPerPageRaw;
$debtTotalPages = max(1, (int)ceil($debtTotalRows / $debtPerPage));
$debtPage       = min($debtTotalPages, max(1, (int)($_GET['page'] ?? 1)));
$debtOffset     = ($debtPage - 1) * $debtPerPage;
$debtCustomers  = array_slice($debtCustomers, $debtOffset, $debtPerPage);

// Bank accounts for transfer repayments
$debtBanks = [];
try {
    $st = $pdo->prepare("SELECT * FROM st_bank_accounts WHERE is_active = 1 AND (store_id = ? OR store_id = 0) ORDER BY id ASC");
    $st->execute([$debtStoreId]);
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $b) {
        $debtBanks[] = [
            'id'             => (int)$b['id'],
            'bank_name'      => $b['bank_name'] ?: ($b['bank_code'] ?? 'Bank'),
            'account_number' => $b['account_number'] ?? '',
            'account_name'   => $b['account_name'] ?? '',
            'logo'           => resolveBankLogo($b['bank_logo'] ?? '', $b['bank_code'] ?? ''),
        ];
    }
} catch (Throwable $e) {}
