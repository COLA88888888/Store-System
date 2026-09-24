<?php
// Reports Backend Processing & Data Layer - Modular Architecture
session_start();

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

require_once dirname(__DIR__, 3) . '/config/db.php';

// Check permissions (Skip for read-only get_bill_details action)
$currentReportType = trim($_GET['type'] ?? 'all_sales');
$reqAction = $_REQUEST['action'] ?? '';

if ($reqAction !== 'get_bill_details') {
    if ($currentReportType === 'item_sales') {
        if (!hasPermission('item_sales') && !hasPermission('sale') && !hasPermission('report') && !hasPermission('pos')) {
            echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
            exit();
        }
    } else {
        if (!hasPermission($currentReportType) && !hasPermission('all_sales') && !hasPermission('report') && !hasPermission('sale') && !hasPermission('pos')) {
            echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
            exit();
        }
    }
}

// 1. Get Filter Input Parameters
$type         = trim($_GET['type'] ?? 'all_sales');
$default_from = date('Y-m-01');

$from_date  = trim($_GET['from_date'] ?? $default_from); // default: today for daily, 1st of month for others
$to_date    = trim($_GET['to_date'] ?? date('Y-m-d'));   // default: today
$search     = trim($_GET['search'] ?? '');
$view_mode  = trim($_GET['view_mode'] ?? 'invoice');      // 'invoice' | 'item'

// Pagination Parameters
$page = intval($_GET['page'] ?? 1);
if ($page < 1) $page = 1;

$per_page_default = '10';
$per_page_raw     = trim($_GET['per_page'] ?? $per_page_default);

if ($per_page_raw === 'all') {
    $per_page = 'all';
} else {
    $per_page = intval($per_page_raw);
    if (!in_array($per_page, [5, 10, 25, 50, 100])) {
        $per_page = 10;
    }
}

function getReportPageUrl($targetPage) {
    $queryParams = $_GET;
    $queryParams['page'] = $targetPage;
    $currentScript = basename($_SERVER['PHP_SELF'] ?? 'reports.php');
    return $currentScript . '?' . http_build_query($queryParams);
}

function getBankLogoByInfo($bankName, $bankAccountId = 0) {
    // Unscoped on purpose: a bill's own bank_account_id/bank_name is a concrete record of
    // whichever bank was actually picked at checkout, and must still resolve to its real logo
    // for display even when that account's store_id doesn't match the branch currently being
    // viewed (see the note where $all_bank_accounts_map is built). $bank_accounts_map (branch-
    // scoped) stays reserved for the filter dropdown & per-branch breakdown only.
    global $pdo, $all_bank_accounts_map;
    $logoFile = '';
    $bankCode = '';

    // 1. Search by bankAccountId
    if (!empty($bankAccountId) && isset($all_bank_accounts_map[$bankAccountId])) {
        $logoFile = $all_bank_accounts_map[$bankAccountId]['bank_logo'] ?? '';
        $bankCode = $all_bank_accounts_map[$bankAccountId]['bank_code'] ?? '';
    }

    // 2. Search all_bank_accounts_map by bank_name or bank_code if logoFile is empty
    if (empty($logoFile) && !empty($all_bank_accounts_map)) {
        $search = strtolower(trim($bankName));
        foreach ($all_bank_accounts_map as $acc) {
            $bName = strtolower(trim($acc['bank_name'] ?? ''));
            $bCode = strtolower(trim($acc['bank_code'] ?? ''));
            if ($search && (($bName && strpos($search, $bName) !== false) || ($bCode && strpos($search, $bCode) !== false) || ($bName && strpos($bName, $search) !== false))) {
                $logoFile = $acc['bank_logo'] ?? '';
                $bankCode = $acc['bank_code'] ?? '';
                break;
            }
        }
    }

    if (empty($bankCode) && !empty($bankName)) {
        if (stripos($bankName, 'BCEL') !== false || stripos($bankName, 'BCL') !== false) { $bankCode = 'BCEL'; }
        elseif (stripos($bankName, 'LDB') !== false) { $bankCode = 'LDB'; }
        elseif (stripos($bankName, 'JDB') !== false) { $bankCode = 'JDB'; }
        elseif (stripos($bankName, 'STB') !== false || stripos($bankName, 'ST') !== false) { $bankCode = 'STB'; }
        elseif (stripos($bankName, 'APB') !== false) { $bankCode = 'APB'; }
        elseif (stripos($bankName, 'LVB') !== false) { $bankCode = 'LVB'; }
    }

    $root = dirname(__DIR__, 3); // Root directory of MiniPos
    if (!empty($logoFile) && file_exists($root . '/assets/img/banks/' . basename($logoFile))) {
        return '../../assets/img/banks/' . basename($logoFile);
    }
    $code = strtolower(trim($bankCode));
    if (!empty($code) && file_exists($root . '/assets/img/banks/' . $code . '.svg')) {
        return '../../assets/img/banks/' . $code . '.svg';
    }
    // No uploaded logo and no matching code-based .svg on disk (the app ships none by default,
    // e.g. bcel.svg) — empty string tells the caller to show a generic fallback icon instead of
    // an <img> pointed at a file that doesn't exist.
    return '';
}

// Handle AJAX Request for Bill Items Modal Details
if (isset($_GET['action']) && $_GET['action'] === 'get_bill_details') {
    header('Content-Type: application/json');
    $billNo = trim($_GET['bill_no'] ?? '');

    if (empty($billNo)) {
        echo json_encode(['success' => false, 'message' => 'ບໍ່ພົບເລກບິນ!']);
        exit();
    }

    try {
        $stmtB = $pdo->prepare("
            SELECT s.*, sl.vat_amount, sl.tax_type, sl.vat_rate,
                   COALESCE(NULLIF(u.fname, ''), s.user_receive, 'Admin') AS cashier_display_name,
                   TRIM(CONCAT(IFNULL(u.fname, ''), ' ', IFNULL(u.lname, ''))) AS seller_full_name,
                   u.tel AS seller_tel,
                   COALESCE(NULLIF(s.customer_name, ''), 'ລູກຄ້າທົ່ວໄປ') AS customer_display_name
            FROM tbsale_save s
            LEFT JOIN sales sl ON s.sale_save_bill = sl.invoice_number 
            LEFT JOIN tbuser u ON (s.user_receive = u.username OR s.user_receive = u.fname)
            WHERE s.sale_save_bill = :bill
        ");
        $stmtB->execute([':bill' => $billNo]);
        $billData = $stmtB->fetch(PDO::FETCH_ASSOC);

        if (!$billData) {
            echo json_encode(['success' => false, 'message' => 'ບໍ່ພົບຂໍ້ມູນບິນຂາຍນີ້!']);
            exit();
        }

        // Tax settings are shared across all branches (live on the main branch's row only)
        $companyTax = getCompanyInfoForBranch($pdo, getActiveStoreId($pdo));
        if ($companyTax) {
            if (empty($billData['tax_type']) || $billData['tax_type'] === 'none') {
                if (!empty($companyTax['tax_type']) && $companyTax['tax_type'] !== 'none') {
                    $billData['tax_type'] = $companyTax['tax_type'];
                }
            }
            if (empty($billData['vat_rate']) || floatval($billData['vat_rate']) == 0) {
                if (!empty($companyTax['vat_percent'])) {
                    $billData['vat_rate'] = floatval($companyTax['vat_percent']);
                }
            }
            $billData['tax_id'] = $companyTax['tax_id'] ?? '';
        }

        // Resolve the exact bank/QR that was used at checkout time — a reprint must show
        // the same QR code and account the customer actually paid to, not the store's default.
        $billData['bank_qr_img'] = '';
        $billData['bank_account_no'] = '';
        $billData['bank_account_name'] = '';
        if (!empty($billData['bank_account_id'])) {
            try {
                $bStmt = $pdo->prepare("SELECT * FROM bank_accounts WHERE id = ?");
                $bStmt->execute([$billData['bank_account_id']]);
                $bInfo = $bStmt->fetch(PDO::FETCH_ASSOC);
                if ($bInfo) {
                    $billData['bank_qr_img'] = resolveBankQr($bInfo['qr_code_img'] ?? '', $bInfo['bank_code'] ?? '');
                    $billData['bank_account_no'] = $bInfo['account_number'] ?? '';
                    $billData['bank_account_name'] = $bInfo['account_name'] ?? '';
                    if (empty($billData['bank_name'])) {
                        $billData['bank_name'] = $bInfo['bank_name'] ?? '';
                    }
                }
            } catch (Throwable $e) {}
        }

        // Delivery-note extras: sender = the staff member who made this sale (name + phone) on the
        // selling branch's letterhead logo; recipient = the customer's phone/address
        $billData['sender_name'] = !empty($billData['seller_full_name'])
            ? $billData['seller_full_name']
            : ($billData['cashier_display_name'] ?? ($billData['user_receive'] ?? 'Admin'));
        $billData['sender_tel']  = trim((string)($billData['seller_tel'] ?? ''));
        $billData['sender_logo'] = 'assets/img/logo/' . (!empty($companyTax['img_url']) ? basename($companyTax['img_url']) : 'logo.png');
        $dnStoreId = intval($billData['store_id'] ?? 0);
        if ($dnStoreId > 0) {
            try {
                $stStmt = $pdo->prepare("SELECT logo_path FROM tbstore WHERE store_id = ? LIMIT 1");
                $stStmt->execute([$dnStoreId]);
                $stRow = $stStmt->fetch(PDO::FETCH_ASSOC);
                if ($stRow && !empty($stRow['logo_path'])) {
                    $billData['sender_logo'] = str_replace('\\', '/', $stRow['logo_path']);
                }
            } catch (Throwable $e) {}
        }
        $billData['customer_phone']   = '';
        $billData['customer_address'] = '';
        if (!empty($billData['customer_id'])) {
            try {
                $cStmt = $pdo->prepare("SELECT phone, address, notes FROM customers WHERE customer_id = ? LIMIT 1");
                $cStmt->execute([$billData['customer_id']]);
                $cRow = $cStmt->fetch(PDO::FETCH_ASSOC);
                if ($cRow) {
                    $billData['customer_phone']   = $cRow['phone'] ?? '';
                    // Address falls back to the notes field (addresses are often stored there)
                    $addr = trim((string)($cRow['address'] ?? ''));
                    $billData['customer_address'] = $addr !== '' ? $addr : trim((string)($cRow['notes'] ?? ''));
                }
            } catch (Throwable $e) {}
        }

        // Customer's points balance right after this sale (as printed on the checkout receipt when
        // points were redeemed) — from the points log, not today's balance.
        $billData['points_balance_after'] = null;
        try {
            $plStmt = $pdo->prepare("SELECT balance_after FROM loyalty_points_log WHERE sale_bill = ? AND log_type IN ('redeem','earn') ORDER BY id DESC LIMIT 1");
            $plStmt->execute([$billNo]);
            $plBal = $plStmt->fetchColumn();
            if ($plBal !== false) $billData['points_balance_after'] = (int)$plBal;
        } catch (Throwable $e) {}

        $amountAfterDisc = max(0, floatval($billData['sale_amount'] ?? 0) - floatval($billData['sale_discount_bill'] ?? 0));
        if ((empty($billData['vat_amount']) || floatval($billData['vat_amount']) == 0) && !empty($billData['vat_rate']) && floatval($billData['vat_rate']) > 0) {
            $taxT = $billData['tax_type'] ?? 'inclusive';
            $vatR = floatval($billData['vat_rate']);
            if ($taxT === 'exclusive' && $vatR < 100) {
                $billData['vat_amount'] = round($amountAfterDisc * ($vatR / (100 - $vatR)), 2);
            } elseif ($taxT === 'inclusive') {
                $billData['vat_amount'] = round($amountAfterDisc - ($amountAfterDisc / (1 + ($vatR / 100))), 2);
            }
        }

        $stmtD = $pdo->prepare("
            SELECT d.*, p.product_name, cat.category_name 
            FROM tbsale_save_detail d
            LEFT JOIN tbsale_save s ON d.save_bill = s.sale_save_bill
            LEFT JOIN products p ON (d.save_proid = p.product_id AND (p.store_id = s.store_id OR s.store_id IS NULL OR s.store_id <= 1))
            LEFT JOIN categories cat ON p.category_id = cat.category_id
            WHERE d.save_bill = :bill
            GROUP BY d.Id
            ORDER BY d.Id ASC
        ");
        $stmtD->execute([':bill' => $billNo]);
        $details = $stmtD->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'bill' => $billData,
            'details' => $details
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// Handle AJAX Request for Delete Bill & Restore Product Inventory Stock
$reqAction = $_REQUEST['action'] ?? '';
if ($reqAction === 'delete_bill') {
    header('Content-Type: application/json');
    $billNo = trim($_REQUEST['bill_no'] ?? '');

    if (empty($billNo)) {
        echo json_encode(['success' => false, 'message' => 'ບໍ່ພົບເລກບິນທີ່ຈະລົບ!']);
        exit();
    }

    try {
        $pdo->beginTransaction();

        // 1. Fetch bill header
        $stmtB = $pdo->prepare("SELECT * FROM tbsale_save WHERE sale_save_bill = :bill");
        $stmtB->execute([':bill' => $billNo]);
        $bill = $stmtB->fetch(PDO::FETCH_ASSOC);

        if (!$bill) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'ບໍ່ພົບຂໍ້ມູນບິນຂາຍນີ້ໃນລະບົບ!']);
            exit();
        }

        if (($bill['sale_status'] ?? '') === 'CANCEL') {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'ບິນຂາຍນີ້ຖືກລົບ/ຍົກເລີກໄປແລ້ວ!']);
            exit();
        }

        // 2. Fetch bill items
        $stmtD = $pdo->prepare("SELECT save_proid, save_qty, multiplier FROM tbsale_save_detail WHERE save_bill = :bill");
        $stmtD->execute([':bill' => $billNo]);
        $details = $stmtD->fetchAll(PDO::FETCH_ASSOC);

        // 3. Restore Product Inventory Stock — must restore save_qty * multiplier (the actual
        // quantity that was cut at checkout), not just save_qty, or a sale made in a multi-unit
        // (e.g. 1 "ຕູດ" = 12 base units) only gets 1 unit restored instead of 12.
        $billStoreId = intval($bill['store_id'] ?? 1);
        $stmtRestore = $pdo->prepare("UPDATE products SET qty = qty + :restore_qty WHERE product_id = :proid AND store_id = :store_id");
        foreach ($details as $item) {
            $proid = $item['save_proid'];
            $qty   = floatval($item['save_qty']) * (intval($item['multiplier'] ?? 1) ?: 1);
            if (!empty($proid) && $qty > 0) {
                $stmtRestore->execute([
                    ':restore_qty' => $qty,
                    ':proid'       => $proid,
                    ':store_id'    => $billStoreId
                ]);
            }
        }

        // 4. Undo the bill's loyalty points: give back what was redeemed on it and take back
        // what it earned, each logged separately so the customer's history stays readable.
        $billCustomerId = intval($bill['customer_id'] ?? 0);
        if ($billCustomerId > 0) {
            $billRedeemed = intval($bill['points_redeemed'] ?? 0);
            $billEarned   = intval($bill['points_earned'] ?? 0);
            if ($billRedeemed > 0) {
                changeCustomerPoints($pdo, $billCustomerId, $billRedeemed, 'redeem_refund', $billNo, null, $billStoreId);
            }
            if ($billEarned > 0) {
                changeCustomerPoints($pdo, $billCustomerId, -$billEarned, 'earn_reversal', $billNo, null, $billStoreId);
            }
        }

        // 5. Update sale_status = 'CANCEL'
        $stmtCancel = $pdo->prepare("UPDATE tbsale_save SET sale_status = 'CANCEL' WHERE sale_save_bill = :bill");
        $stmtCancel->execute([':bill' => $billNo]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'ລົບບິນຂາຍ ' . $billNo . ' ແລະ ຄືນສະຕັອກສິນຄ້າສຳເລັດແລ້ວ!'
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'ເກີດຂໍ້ຜິດພາດ: ' . $e->getMessage()]);
    }
    exit();
}

// Titles and icons mapping
$report_meta = [
    'daily' => [
        'title' => 'ລາຍງານປະຈຳວັນ',
        'icon' => 'fas fa-calendar-day text-info'
    ],
    'all_sales' => [
        'title' => ($view_mode === 'item') ? 'ລາຍການຂາຍສິນຄ້າ' : 'ລາຍງານການຂາຍທັງໝົດ',
        'icon' => 'fas fa-file-invoice-dollar text-success'
    ],
    'best_seller' => [
        'title' => 'ລາຍງານສິນຄ້າຂາຍດີ',
        'icon' => 'fas fa-fire text-danger'
    ],
    'profit_cost' => [
        'title' => 'ລາຍງານກຳໄລ-ຕົ້ນທຶນ',
        'icon' => 'fas fa-chart-line text-warning'
    ],
    'financial' => [
        'title' => 'ລາຍງານການເງິນ',
        'icon' => 'fas fa-wallet text-info'
    ],
    'category' => [
        'title' => 'ລາຍງານຕາມ/ປະເພດສິນຄ້າ',
        'icon' => 'fas fa-layer-group text-primary'
    ],
    'delete_bills' => [
        'title' => 'ປະຫວັດການລົບບິນຂາຍ',
        'icon' => 'fas fa-trash-alt text-danger'
    ]
];

$current_meta = $report_meta[$type] ?? $report_meta['all_sales'];

// Inspect columns in tbsale_save to handle cash, qr, tip, and currency columns dynamically
$hasCashCol     = false;
$hasQrCol       = false;
$hasTipCol      = false;
$hasCurrencyCol = false;
try {
    $cols = $pdo->query("SHOW COLUMNS FROM tbsale_save")->fetchAll(PDO::FETCH_COLUMN);
    $hasCashCol     = in_array('cash_received', $cols);
    $hasQrCol       = in_array('qr_received', $cols);
    $hasTipCol      = in_array('tip_amount', $cols) || in_array('tip', $cols);
    $hasCurrencyCol = in_array('paid_currency', $cols);
} catch (Exception $e) {}

// Build SQL Query & Fetch Data
$where  = [];
$params = [];

if ($type === 'delete_bills') {
    $where[] = "s.sale_status = 'CANCEL'";
} else {
    $where[] = "(s.sale_status IS NULL OR s.sale_status != 'CANCEL')";
}
$params = [];

$bank_filter = trim($_GET['bank_filter'] ?? '');

// Bank accounts for the filter & breakdown — scoped to the branch this report is actually
// viewing (honours ?switch_store_id for admin/main-branch) plus any bank shared across every
// branch (store_id = 0), so the dropdown never mixes in ANOTHER branch's own bank accounts.
$bank_accounts = [];
$bank_accounts_map = [];
try {
    $b_stmt = $pdo->prepare("SELECT * FROM bank_accounts WHERE store_id = ? OR store_id = 0 ORDER BY id ASC");
    $b_stmt->execute([getActiveStoreId($pdo)]);
    if ($b_stmt) {
        $bank_accounts = $b_stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($bank_accounts as $bAcc) {
            $bank_accounts_map[$bAcc['id']] = $bAcc;
        }
    }
} catch (Throwable $e) {}

// Separate, UNSCOPED lookup (every branch's bank_accounts, by id) — a bill's own
// bank_account_id is a concrete reference to whichever bank account was actually picked at
// checkout time, and must still resolve to its real name/logo for display even when that
// account's store_id doesn't match the branch this report is currently viewing (e.g. a bank
// account created before branches were split out, or shared/misfiled under another store_id).
// Only $bank_accounts_map above (branch-scoped) feeds the filter dropdown & breakdown, so this
// doesn't reopen the cross-branch mixing that was fixed there.
$all_bank_accounts_map = [];
try {
    foreach ($pdo->query("SELECT * FROM bank_accounts")->fetchAll(PDO::FETCH_ASSOC) as $bAcc) {
        $all_bank_accounts_map[$bAcc['id']] = $bAcc;
    }
} catch (Throwable $e) {}

// Always apply date range filter
if (!empty($from_date)) {
    $where[] = "DATE(s.sale_date) >= :from_date";
    $params[':from_date'] = $from_date;
}

if (!empty($to_date)) {
    $where[] = "DATE(s.sale_date) <= :to_date";
    $params[':to_date'] = $to_date;
}

if (!empty($search)) {
    $where[] = "(s.sale_save_bill LIKE :search OR s.user_receive LIKE :search OR s.customer_name LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($bank_filter)) {
    if ($bank_filter === 'cash') {
        $where[] = "(s.type_pay LIKE '%ເງິນສົດ%' AND (s.type_pay NOT LIKE '%ໂອນ%' AND s.type_pay NOT LIKE '%QR%'))";
    } elseif ($bank_filter === 'transfer') {
        $where[] = "(s.type_pay LIKE '%ໂອນ%' OR s.type_pay LIKE '%QR%' OR s.bank_account_id > 0)";
    } elseif (is_numeric($bank_filter) && intval($bank_filter) > 0) {
        $bId = intval($bank_filter);
        $bAcc = $bank_accounts_map[$bId] ?? [];
        $bName = trim($bAcc['bank_name'] ?? '');
        $bCode = trim($bAcc['bank_code'] ?? '');
        $shortCode = strtok($bName, ' '); // e.g. "BCEL" from "BCEL One"

        $bankConds = ["s.bank_account_id = :b_id"];
        $params[':b_id'] = $bId;

        if (!empty($bName)) {
            $bankConds[] = "s.bank_name LIKE :b_name";
            $bankConds[] = "s.type_pay LIKE :b_name2";
            $params[':b_name'] = '%' . $bName . '%';
            $params[':b_name2'] = '%' . $bName . '%';
        }

        if (!empty($shortCode) && strlen($shortCode) >= 2 && $shortCode !== $bName) {
            $bankConds[] = "s.bank_name LIKE :b_code";
            $bankConds[] = "s.type_pay LIKE :b_code2";
            $params[':b_code'] = '%' . $shortCode . '%';
            $params[':b_code2'] = '%' . $shortCode . '%';
        }

        // Include unassigned transfer sales under the primary bank account (e.g. BCEL One)
        $firstBankId = !empty($bank_accounts) ? intval($bank_accounts[0]['id']) : 1;
        if ($bId === $firstBankId) {
            $bankConds[] = "( (s.bank_account_id IS NULL OR s.bank_account_id = 0) AND (s.bank_name IS NULL OR s.bank_name = '') AND (s.type_pay LIKE '%ໂອນ%' OR s.type_pay LIKE '%QR%') )";
        }

        $where[] = "(" . implode(" OR ", $bankConds) . ")";
    }
}

// Currency Filter — which currency the customer actually tendered on the bill
// (paid_currency, saved per sale at checkout; 'KIP' = base currency, no conversion).
$currency_filter = strtoupper(trim($_GET['currency_filter'] ?? ''));
if ($currency_filter !== '' && $hasCurrencyCol && isset(getCurrencyMeta()[$currency_filter])) {
    $where[] = "COALESCE(NULLIF(s.paid_currency, ''), 'KIP') = :currency_filter";
    $params[':currency_filter'] = $currency_filter;

    // Reports normally show money in the global display-currency setting; once the
    // user filters down to one tendered currency, every amount on the page should
    // switch to match it instead (KPI cards, table cells, totals) — not stay stuck
    // showing ₭ while the filter says THB.
    $__curRates = getExchangeRates($pdo);
    $__curRate  = ($currency_filter === 'KIP') ? 1.0 : (float)($__curRates[$currency_filter] ?? 0);
    if ($currency_filter === 'KIP' || $__curRate > 0) {
        setDisplayCurrencyOverride(array_merge(
            ['code' => $currency_filter, 'rate' => $__curRate, 'rates' => $__curRates],
            getCurrencyMeta()[$currency_filter]
        ));
    }
} else {
    $currency_filter = '';
}

// Branch Store Filter
$userStoreId = intval($_SESSION['store_id'] ?? 1);
$isAdmin = ($_SESSION['status'] ?? '') === 'ຜູ້ບໍລິຫານ' || strtolower($_SESSION['status'] ?? '') === 'admin' || ($_SESSION['user_id'] ?? 0) == 1;
$isMain = isMainBranch($pdo, $userStoreId);
$canAllBranches = canViewAllBranches($pdo);

// Only users who pass canViewAllBranches() see every branch's figures; everyone else,
// including staff granted the "report" permission, is locked to their own branch.
$reportsCrossBranch = $canAllBranches;

$filter_store_id = isset($_GET['store_id']) && $_GET['store_id'] !== '' ? intval($_GET['store_id']) : 0;
// Users who can't see every branch are locked to their own store_id
if (!$reportsCrossBranch) {
    $filter_store_id = $userStoreId;
}

$branchesList = [];
try {
    $branchesList = $pdo->query("SELECT * FROM tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$hasStoreIdCol = false;
try {
    $saleCols = $pdo->query("SHOW COLUMNS FROM tbsale_save")->fetchAll(PDO::FETCH_COLUMN);
    $hasStoreIdCol = in_array('store_id', $saleCols) || in_array('branch_id', $saleCols);
    $storeColName = in_array('store_id', $saleCols) ? 'store_id' : 'branch_id';
} catch (Exception $e) {}

if ($filter_store_id > 0 && $hasStoreIdCol) {
    $where[] = "s.{$storeColName} = :filter_store_id";
    $params[':filter_store_id'] = $filter_store_id;
}

$whereClause = implode(" AND ", $where);

// Data variables
$sales_data        = [];
$total_qty         = 0;
$total_gross       = 0;
$total_disc        = 0;
$total_net         = 0;
$total_cash        = 0;
$total_qr          = 0;
$total_change      = 0;
$total_tip         = 0;
$total_profit      = 0;
$has_cost_data     = false;
$total_bills_count = 0;

// Daily Report 10 Metrics Variables
$daily_gross_sales    = 0;
$daily_item_discounts = 0;
$daily_bill_discounts = 0;
$daily_total_discounts= 0;
$daily_net_sales      = 0;
$daily_cash_payments  = 0;
$daily_qr_payments    = 0;
$daily_tips_sum       = 0;
$daily_bills_count    = 0;
$daily_items_qty      = 0;

if ($type === 'daily') {
    try {
        $cashSql = $hasCashCol ? "WHEN s.cash_received > 0 THEN s.cash_received" : "";
        $qrSql   = $hasQrCol   ? "WHEN s.qr_received > 0 THEN s.qr_received" : "";

        $stmtDaily = $pdo->prepare("
            SELECT 
                COUNT(s.Id) AS total_bills,
                COALESCE(SUM(s.sale_qty), 0) AS total_items_qty,
                COALESCE(SUM(s.sale_amount), 0) AS total_gross_sales,
                COALESCE(SUM(s.sale_discount_bill), 0) AS total_bill_discounts,
                COALESCE(SUM(s.sale_barlance), 0) AS total_net_sales,
                COALESCE(SUM(CASE 
                    {$cashSql}
                    WHEN s.type_pay LIKE '%ເງິນສົດ%' AND (s.type_pay NOT LIKE '%ໂອນ%' AND s.type_pay NOT LIKE '%QR%') THEN s.sale_barlance
                    WHEN s.type_pay LIKE '%ເງິນສົດ%' THEN GREATEST(0, s.sale_pay - s.sale_return)
                    ELSE 0 
                END), 0) AS total_cash_received,
                COALESCE(SUM(CASE 
                    {$qrSql}
                    WHEN (s.type_pay LIKE '%ໂອນ%' OR s.type_pay LIKE '%QR%') AND s.type_pay NOT LIKE '%ເງິນສົດ%' THEN s.sale_barlance
                    WHEN s.sale_transfer > 0 THEN s.sale_transfer
                    ELSE 0 
                END), 0) AS total_qr_received,
                COALESCE(SUM(GREATEST(0, (s.sale_pay - s.sale_return) - s.sale_barlance)), 0) AS total_tips_sum
            FROM tbsale_save s
            WHERE {$whereClause}
        ");
        $stmtDaily->execute($params);
        $dailyStats = $stmtDaily->fetch(PDO::FETCH_ASSOC);

        $hasItemDiscCol = false;
        try {
            $colsD = $pdo->query("SHOW COLUMNS FROM tbsale_save_detail")->fetchAll(PDO::FETCH_COLUMN);
            $hasItemDiscCol = in_array('save_discount_item', $colsD) ? 'save_discount_item' : (in_array('save_discount', $colsD) ? 'save_discount' : '');
        } catch (Exception $e) {}

        if ($hasItemDiscCol) {
            $stmtItemDisc = $pdo->prepare("
                SELECT COALESCE(SUM(d.{$hasItemDiscCol}), 0) AS total_item_disc
                FROM tbsale_save_detail d
                INNER JOIN tbsale_save s ON d.save_bill = s.sale_save_bill
                WHERE {$whereClause}
            ");
            $stmtItemDisc->execute($params);
            $itemDiscRes = $stmtItemDisc->fetch(PDO::FETCH_ASSOC);
            $daily_item_discounts = (float)($itemDiscRes['total_item_disc'] ?? 0);
        } else {
            $daily_item_discounts = 0;
        }

        $daily_bills_count     = (int)($dailyStats['total_bills'] ?? 0);
        $daily_items_qty       = (int)($dailyStats['total_items_qty'] ?? 0);
        $daily_gross_sales     = (float)($dailyStats['total_gross_sales'] ?? 0);
        $daily_bill_discounts  = (float)($dailyStats['total_bill_discounts'] ?? 0);
        $daily_total_discounts = $daily_item_discounts + $daily_bill_discounts;
        $daily_net_sales       = (float)($dailyStats['total_net_sales'] ?? 0);
        $daily_cash_payments   = (float)($dailyStats['total_cash_received'] ?? 0);
        $daily_qr_payments     = (float)($dailyStats['total_qr_received'] ?? 0);
        $daily_tips_sum        = (float)($dailyStats['total_tips_sum'] ?? 0);

        // Promotion & Free Gift Statistics
        $daily_promo_discounts = $daily_item_discounts;
        $daily_promo_gifts_count = 0;
        try {
            $stmtGifts = $pdo->prepare("
                SELECT COALESCE(SUM(d.save_qty), 0) AS total_gifts
                FROM tbsale_save_detail d
                INNER JOIN tbsale_save s ON d.save_bill = s.sale_save_bill
                WHERE {$whereClause} AND (d.save_name LIKE '%(ແຖມ)%' OR d.save_name LIKE '%🎁%' OR d.save_price = 0)
            ");
            $stmtGifts->execute($params);
            $daily_promo_gifts_count = (int)($stmtGifts->fetchColumn() ?: 0);
        } catch (Exception $e) {}
    } catch (Exception $e) {}
}

if ($view_mode === 'item') {
    // VIEW MODE 2: ສະແດງຕາມລາຍການສິນຄ້າ (ITEM DETAIL VIEW)
    $sql = "
        SELECT d.*, s.sale_date, s.sale_time, s.user_receive, s.customer_name, s.type_pay, s.sale_status, s.sale_remark,
               s.sale_amount AS bill_gross, s.sale_discount_bill AS bill_discount, s.sale_barlance AS bill_net,
               s.sale_pay, s.sale_return,
               " . ($hasCashCol ? "s.cash_received," : "") . "
               " . ($hasQrCol ? "s.qr_received," : "") . "
               " . ($hasCurrencyCol ? "s.paid_currency, s.paid_rate, s.paid_amount_foreign," : "") . "
               p.product_name, p.bprice, cat.category_name, COALESCE(st.store_name, 'ສາຂາ') AS store_name
        FROM tbsale_save_detail d
        INNER JOIN tbsale_save s ON d.save_bill = s.sale_save_bill
        LEFT JOIN products p ON (d.save_proid = p.product_id AND (p.store_id = s.store_id OR s.store_id IS NULL OR s.store_id <= 1))
        LEFT JOIN categories cat ON p.category_id = cat.category_id
        LEFT JOIN tbstore st ON s.store_id = st.store_id
        WHERE {$whereClause}
        GROUP BY d.Id
        ORDER BY s.sale_date DESC, s.sale_time DESC, d.Id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        $qty   = floatval($r['save_qty']);
        $price = floatval($r['save_price']);
        $gross = floatval($r['save_money'] ?? ($qty * $price));

        $billGross = floatval($r['bill_gross'] ?? 0);
        $billDisc  = floatval($r['bill_discount'] ?? 0);
        $itemDisc  = ($billGross > 0) ? round(($gross / $billGross) * $billDisc, 2) : 0;
        $net       = max(0, $gross - $itemDisc);

        $typePay  = $r['type_pay'] ?? '';
        $billNet  = floatval($r['bill_net'] ?? 0);
        $billPay  = floatval($r['sale_pay'] ?? 0);
        $billRet  = floatval($r['sale_return'] ?? 0);
        $billPaid = max(0, $billPay - $billRet);

        if (isset($r['cash_received']) && isset($r['qr_received']) && ($r['cash_received'] > 0 || $r['qr_received'] > 0)) {
            $billCash = floatval($r['cash_received']);
            $billQr   = floatval($r['qr_received']);
        } else {
            if (mb_strpos($typePay, 'ໂອນ') !== false || mb_strpos($typePay, 'QR') !== false) {
                if (mb_strpos($typePay, 'ເງິນສົດ') !== false) {
                    $billCash = max(0, $billPaid);
                    $billQr   = max(0, $billNet - $billCash);
                } else {
                    $billCash = 0;
                    $billQr   = $billNet;
                }
            } else {
                $billCash = $billNet;
                $billQr   = 0;
            }
        }

        $billTip = max(0, $billPaid - $billNet);
        $ratio   = ($billNet > 0) ? ($net / $billNet) : 0;
        $itemCash = round($billCash * $ratio, 2);
        $itemQr   = round($billQr * $ratio, 2);
        $itemTip  = round($billTip * $ratio, 2);

        // Real cost only — d.cost_price (recorded at checkout time) falling back to the
        // product's current bprice for older sales. NEVER a guessed margin: a non-stock/
        // service item (cut_qty = 0) typically has no purchase cost at all, and fabricating
        // one (e.g. 70% of the sale price) would misreport its profit. When no real cost is
        // on file, $profit is left null so the KPI card can hide instead of showing a guess.
        $realCostPrice = floatval($r['cost_price'] ?? 0);
        $fallbackBprice = floatval($r['bprice'] ?? 0);
        $hasCost  = $realCostPrice > 0 || $fallbackBprice > 0;
        $buyPrice = $realCostPrice > 0 ? $realCostPrice : $fallbackBprice;
        $itemCost = $qty * $buyPrice;
        $profit   = $hasCost ? max(0, $net - $itemCost) : null;

        if ($hasCost) {
            $has_cost_data = true;
        }

        $sales_data[] = [
            'bill_no'   => $r['save_bill'],
            'date_time' => date('d/m/Y', strtotime($r['sale_date'])) . ' ' . substr($r['sale_time'], 0, 5),
            'pro_code'  => $r['save_proid'] ?: ($r['product_id'] ?? '-'),
            'pro_name'  => $r['save_proname'] ?: ($r['product_name'] ?? 'ສິນຄ້າ'),
            'store_name'=> $r['store_name'] ?? 'ສາຂາ',
            'qty'       => $qty,
            'price'     => $price,
            'gross'     => $gross,
            'discount'  => $itemDisc,
            'net'       => $net,
            'profit'    => $profit,
            'cash'      => $itemCash,
            'qr'        => $itemQr,
            'tip'       => $itemTip,
            'status'    => $r['sale_status'] ?? 'SUCCESS',
            'cashier'   => $r['user_receive'] ?? 'Admin',
            'currency'  => strtoupper($r['paid_currency'] ?? 'KIP') ?: 'KIP',
            'sale_remark' => $r['sale_remark'] ?? ''
        ];

        $total_qty    += $qty;
        $total_gross  += $gross;
        $total_disc   += $itemDisc;
        $total_net    += $net;
        if ($hasCost) {
            $total_profit += $profit;
        }
        $total_cash  += $itemCash;
        $total_qr    += $itemQr;
        $total_tip   += $itemTip;
    }

    $total_bills_count = count($sales_data);



} else {
    // VIEW MODE 1: ສະແດງຕາມບິນ (INVOICE SUMMARY VIEW)
    $sql = "
        SELECT s.*, COALESCE(st.store_name, 'ສາຂາ') AS store_name
        FROM tbsale_save s
        LEFT JOIN tbstore st ON s.store_id = st.store_id
        WHERE {$whereClause}
        ORDER BY s.sale_date DESC, s.sale_time DESC, s.Id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        $qty   = floatval($r['sale_qty'] ?? 0);
        $gross = floatval($r['sale_amount'] ?? 0);
        $disc  = floatval($r['sale_discount_bill'] ?? 0);
        $net   = floatval($r['sale_barlance'] ?? max(0, $gross - $disc));

        $typePay = $r['type_pay'] ?? '';
        $pay     = floatval($r['sale_pay'] ?? 0);
        $ret     = floatval($r['sale_return'] ?? 0);
        $paid    = max(0, $pay - $ret);

        if (isset($r['cash_received']) && isset($r['qr_received']) && ($r['cash_received'] > 0 || $r['qr_received'] > 0)) {
            $cash = floatval($r['cash_received']);
            $qr   = floatval($r['qr_received']);
        } else {
            if (mb_strpos($typePay, 'ໂອນ') !== false || mb_strpos($typePay, 'QR') !== false) {
                if (mb_strpos($typePay, 'ເງິນສົດ') !== false) {
                    $cash = max(0, $paid);
                    $qr   = max(0, $net - $cash);
                } else {
                    $cash = 0;
                    $qr   = $net;
                }
            } else {
                $cash = $net;
                $qr   = 0;
            }
        }

        if (isset($r['tip_amount']) && $r['tip_amount'] > 0) {
            $tip = floatval($r['tip_amount']);
        } else if (isset($r['tip']) && $r['tip'] > 0) {
            $tip = floatval($r['tip']);
        } else {
            $tip = max(0, $paid - $net);
        }

        $change = floatval($r['sale_return'] ?? 0);

        $bAccId = intval($r['bank_account_id'] ?? 0);
        // Unscoped lookup — this bill's own bank_account_id is a concrete reference that must
        // resolve regardless of which branch's report is currently being viewed (see the note
        // where $all_bank_accounts_map is built above).
        $bInfo = $all_bank_accounts_map[$bAccId] ?? [];
        $bName = !empty($r['bank_name']) ? $r['bank_name'] : ($bInfo['bank_name'] ?? '');
        $bCode = !empty($bInfo['bank_code']) ? $bInfo['bank_code'] : $bName;
        $bLogo = !empty($bInfo['bank_logo']) ? $bInfo['bank_logo'] : '';

        if (empty($bName) && $qr > 0) {
            $tPay = strtoupper($r['type_pay'] ?? '');
            if (strpos($tPay, 'BCEL') !== false || strpos($tPay, 'BCL') !== false) {
                $bName = 'BCEL One';
                $bCode = 'BCEL';
            } else if (strpos($tPay, 'LDB') !== false) {
                $bName = 'LDB';
                $bCode = 'LDB';
            } else if (strpos($tPay, 'JDB') !== false) {
                $bName = 'JDB';
                $bCode = 'JDB';
            } else if (!empty($bank_accounts_map)) {
                $firstBank = reset($bank_accounts_map);
                $bName = $firstBank['bank_name'];
                $bCode = !empty($firstBank['bank_code']) ? $firstBank['bank_code'] : $bName;
                $bLogo = !empty($firstBank['bank_logo']) ? $firstBank['bank_logo'] : '';
            } else {
                $bName = 'BCEL One';
                $bCode = 'BCEL';
            }
        }

        $sales_data[] = [
            'bill_no'         => $r['sale_save_bill'],
            'date_time'       => date('d/m/Y', strtotime($r['sale_date'])) . ' ' . substr($r['sale_time'], 0, 5),
            'store_name'      => $r['store_name'] ?? 'ສາຂາ',
            'qty'             => $qty,
            'gross'           => $gross,
            'discount'        => $disc,
            'net'             => $net,
            'cash'            => $cash,
            'qr'              => $qr,
            'change'          => $change,
            'tip'             => $tip,
            'status'          => $r['sale_status'] ?? 'SUCCESS',
            'type_pay'        => $typePay,
            'bank_account_id' => $bAccId,
            'bank_name'       => $bName,
            'bank_code'       => $bCode,
            'bank_logo'       => $bLogo,
            'cashier'         => $r['user_receive'] ?? 'Admin',
            'customer'        => $r['customer_name'] ?? 'ລູກຄ້າທົ່ວໄປ',
            'currency'        => strtoupper($r['paid_currency'] ?? 'KIP') ?: 'KIP',
            'sale_remark'     => $r['sale_remark'] ?? ''
        ];

        $total_qty    += $qty;
        $total_gross  += $gross;
        $total_disc   += $disc;
        $total_net    += $net;
        $total_cash   += $cash;
        $total_qr     += $qr;
        $total_change += $change;
        $total_tip    += $tip;
    }

    $total_bills_count = count($sales_data);

    // Invoice view has no per-item rows to derive cost from, so pull real cost (d.cost_price,
    // falling back to the product's current bprice) straight from the sale details for the
    // same filtered bills. Never assume a margin — if nothing here has a recorded cost,
    // $has_cost_data stays false and reports_kpi.php hides the ກຳໄລ card instead of guessing.
    try {
        $costStmt = $pdo->prepare("
            SELECT
                COALESCE(SUM(d.save_qty * (CASE WHEN d.cost_price > 0 THEN d.cost_price ELSE COALESCE(p.bprice, 0) END)), 0) AS known_cost,
                MAX(CASE WHEN d.cost_price > 0 OR COALESCE(p.bprice, 0) > 0 THEN 1 ELSE 0 END) AS has_cost
            FROM tbsale_save_detail d
            INNER JOIN tbsale_save s ON d.save_bill = s.sale_save_bill
            LEFT JOIN products p ON (d.save_proid = p.product_id AND (p.store_id = s.store_id OR s.store_id IS NULL OR s.store_id <= 1))
            WHERE {$whereClause}
        ");
        $costStmt->execute($params);
        $costRow = $costStmt->fetch(PDO::FETCH_ASSOC);
        $has_cost_data = !empty($costRow['has_cost']);
        if ($has_cost_data) {
            $total_profit = max(0, $total_net - floatval($costRow['known_cost'] ?? 0));
        }
    } catch (Exception $e) {}
}

// Slice $sales_data into $display_data for pagination
$total_records = count($sales_data);

if ($per_page === 'all') {
    $total_pages = 1;
    $page = 1;
    $display_data = $sales_data;
    $start_record = ($total_records > 0) ? 1 : 0;
    $end_record = $total_records;
} else {
    $total_pages = max(1, intval(ceil($total_records / $per_page)));
    if ($page > $total_pages) $page = $total_pages;

    $offset = ($page - 1) * $per_page;
    $display_data = array_slice($sales_data, $offset, $per_page);
    $start_record = ($total_records > 0) ? ($offset + 1) : 0;
    $end_record = min($offset + $per_page, $total_records);
}

$grouped_bills = [];
if ($view_mode === 'item') {
    foreach ($display_data as $row) {
        $bno = $row['bill_no'];
        if (!isset($grouped_bills[$bno])) {
            $grouped_bills[$bno] = [
                'bill_no'   => $bno,
                'date_time' => $row['date_time'],
                'cashier'   => $row['cashier'],
                'currency'  => $row['currency'] ?? 'KIP',
                'items'     => [],
                'bill_net'  => 0,
            ];
        }
        $grouped_bills[$bno]['items'][] = $row;
        $grouped_bills[$bno]['bill_net'] += $row['net'];
    }
}
