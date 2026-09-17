<?php
// api/quotations_backend.php — ໃບສະເໜີລາຄາ (Quotations)
// Dual-purpose: included by pages/quotations/quotations.php as the page's controller, and
// hit directly for AJAX (product lookup, load-for-edit, save, status change, delete, table
// refresh). See CLAUDE.md's "api/*_backend.php is dual-purpose" note.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($base_path)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';
}
require_once __DIR__ . '/../config/db.php';

$isAjaxRequest = isset($_GET['fetch_table']) || isset($_GET['product_lookup']) || isset($_GET['get_quotation']) || !empty($_POST['is_ajax']);

if (empty($_SESSION['user_id']) || !hasPermission('quotations')) {
    if ($isAjaxRequest) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => t('quotations.no_permission', 'ບໍ່ມີສິດເຂົ້າໃຊ້ງານ')]);
        exit();
    }
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$activeStoreId = getActiveStoreId($pdo);
$currentUserId = intval($_SESSION['user_id'] ?? 0);

$message = '';
$message_type = '';

// ---------- AJAX: product lookup for the line-item picker (barcode / code / name) ----------
if (isset($_GET['product_lookup'])) {
    header('Content-Type: application/json');
    $q = trim((string)$_GET['product_lookup']);
    $rows = [];
    if ($q !== '') {
        try {
            $stmt = $pdo->prepare("SELECT product_id, product_name, barcode, code1, price, unit FROM products WHERE store_id = ? AND (product_name LIKE ? OR barcode = ? OR code1 = ?) ORDER BY product_name ASC LIMIT 20");
            $like = '%' . $q . '%';
            $stmt->execute([$activeStoreId, $like, $q, $q]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {}
    }
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
    exit();
}

// ---------- AJAX: load one quotation + its line items, for the edit modal ----------
if (isset($_GET['get_quotation'])) {
    header('Content-Type: application/json');
    $id = intval($_GET['get_quotation']);
    $qStmt = $pdo->prepare("SELECT * FROM quotations WHERE quotation_id = ? AND store_id = ?");
    $qStmt->execute([$id, $activeStoreId]);
    $qRow = $qStmt->fetch(PDO::FETCH_ASSOC);
    if (!$qRow) {
        echo json_encode(['success' => false]);
        exit();
    }
    $dStmt = $pdo->prepare("SELECT * FROM quotation_details WHERE quotation_id = ? ORDER BY detail_id ASC");
    $dStmt->execute([$id]);
    $qRow['items'] = $dStmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $qRow], JSON_UNESCAPED_UNICODE);
    exit();
}

// ---------- POST: add / edit / status / delete ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add_quotation' || $action === 'edit_quotation') {
        $quotation_id     = intval($_POST['quotation_id'] ?? 0);
        $customer_id      = intval($_POST['customer_id'] ?? 0) ?: null;
        $customer_name    = trim($_POST['customer_name'] ?? '');
        $customer_tel     = trim($_POST['customer_tel'] ?? '');
        $customer_address = trim($_POST['customer_address'] ?? '');
        $quote_date       = !empty($_POST['quote_date']) ? $_POST['quote_date'] : date('Y-m-d');
        $valid_until      = !empty($_POST['valid_until']) ? $_POST['valid_until'] : date('Y-m-d', strtotime('+7 days'));
        $discount_amount  = floatval(str_replace(',', '', $_POST['discount_amount'] ?? 0));
        $vat_amount       = floatval(str_replace(',', '', $_POST['vat_amount'] ?? 0));
        $notes            = trim($_POST['notes'] ?? '');
        $items            = json_decode($_POST['items'] ?? '[]', true);
        if (!is_array($items)) $items = [];

        if ($customer_name === '') {
            $message = t('quotations.msg_need_customer', 'ກະລຸນາປ້ອນຊື່ລູກຄ້າ!');
            $message_type = 'error';
        } elseif (empty($items)) {
            $message = t('quotations.msg_need_items', 'ກະລຸນາເພີ່ມລາຍການສິນຄ້າຢ່າງໜ້ອຍ 1 ລາຍການ!');
            $message_type = 'error';
        } else {
            $subtotal = 0.0;
            foreach ($items as $it) {
                $subtotal += (floatval($it['qty'] ?? 0) * floatval($it['price'] ?? 0)) - floatval($it['discount'] ?? 0);
            }
            $total_amount = $subtotal - $discount_amount + $vat_amount;

            try {
                $pdo->beginTransaction();
                
                $log_action = '';
                $log_detail = '';

                if ($action === 'add_quotation') {
                    // QT + today's date + a per-day sequence, e.g. QT20260917-001.
                    $todayCount = (int)$pdo->query("SELECT COUNT(*) FROM quotations WHERE DATE(created_at) = CURDATE()")->fetchColumn();
                    $quotation_no = 'QT' . date('Ymd') . '-' . str_pad((string)($todayCount + 1), 4, '0', STR_PAD_LEFT);

                    $ins = $pdo->prepare("INSERT INTO quotations
                        (quotation_no, customer_id, customer_name, customer_tel, customer_address, quote_date, valid_until, subtotal, discount_amount, vat_amount, total_amount, status, notes, store_id, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)");
                    $ins->execute([$quotation_no, $customer_id, $customer_name, $customer_tel, $customer_address, $quote_date, $valid_until, $subtotal, $discount_amount, $vat_amount, $total_amount, $notes, $activeStoreId, $currentUserId]);
                    $quotation_id = (int)$pdo->lastInsertId();
                    $message = t('quotations.msg_added', 'ເພີ່ມໃບສະເໜີລາຄາສຳເລັດ!');
                    
                    $log_action = "ເພີ່ມໃບສະເໜີລາຄາ";
                    $log_detail = "$quotation_no - $customer_name";
                } else {
                    $upd = $pdo->prepare("UPDATE quotations SET
                        customer_id = ?, customer_name = ?, customer_tel = ?, customer_address = ?, quote_date = ?, valid_until = ?,
                        subtotal = ?, discount_amount = ?, vat_amount = ?, total_amount = ?, notes = ?
                        WHERE quotation_id = ? AND store_id = ?");
                    $upd->execute([$customer_id, $customer_name, $customer_tel, $customer_address, $quote_date, $valid_until, $subtotal, $discount_amount, $vat_amount, $total_amount, $notes, $quotation_id, $activeStoreId]);
                    $pdo->prepare("DELETE FROM quotation_details WHERE quotation_id = ?")->execute([$quotation_id]);
                    $message = t('quotations.msg_updated', 'ແກ້ໄຂໃບສະເໜີລາຄາສຳເລັດ!');
                    
                    $log_action = "ແກ້ໄຂໃບສະເໜີລາຄາ";
                    $log_detail = "ID: $quotation_id - $customer_name";
                }

                $detStmt = $pdo->prepare("INSERT INTO quotation_details (quotation_id, product_id, product_name, unit_name, qty, price, discount, line_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($items as $it) {
                    $qty   = floatval($it['qty'] ?? 0);
                    $price = floatval($it['price'] ?? 0);
                    $disc  = floatval($it['discount'] ?? 0);
                    $detStmt->execute([
                        $quotation_id,
                        !empty($it['product_id']) ? intval($it['product_id']) : null,
                        trim((string)($it['product_name'] ?? '')),
                        trim((string)($it['unit_name'] ?? '')),
                        $qty, $price, $disc, ($qty * $price) - $disc,
                    ]);
                }

                $pdo->commit();
                
                if ($log_action !== '') {
                    logActivity($pdo, $log_action, $log_detail);
                }
                
                $message_type = 'success';
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $message = t('quotations.msg_error_prefix', 'ຜິດພາດ: ') . $e->getMessage();
                $message_type = 'error';
            }
        }
    } elseif ($action === 'update_status') {
        $id = intval($_POST['id'] ?? 0);
        $status = (string)($_POST['status'] ?? '');
        $allowedStatuses = ['pending', 'accepted', 'rejected', 'expired', 'converted'];
        if ($id > 0 && in_array($status, $allowedStatuses, true)) {
            $pdo->prepare("UPDATE quotations SET status = ? WHERE quotation_id = ? AND store_id = ?")->execute([$status, $id, $activeStoreId]);
            $message = t('quotations.msg_status_updated', 'ອັບເດດສະຖານະສຳເລັດ!');
            $message_type = 'success';
        }
    } elseif ($action === 'convert_to_sale') {
        // Customer accepted the quote: turn it into a real sale using the QUOTED price (not
        // whatever the product costs today), decrement stock, and write into the same tables
        // (tbsale_save / tbsale_save_detail / sales) a normal POS checkout uses — so it shows
        // up correctly in item-sales lists and reports without going through pos.php's cart.
        $id = intval($_POST['id'] ?? 0);
        $payment_type = trim($_POST['payment_type'] ?? 'ເງິນສົດ');

        $qStmt = $pdo->prepare("SELECT * FROM quotations WHERE quotation_id = ? AND store_id = ?");
        $qStmt->execute([$id, $activeStoreId]);
        $quote = $qStmt->fetch(PDO::FETCH_ASSOC);

        if (!$quote) {
            $message = t('quotations.msg_not_found', 'ບໍ່ພົບໃບສະເໜີລາຄານີ້!');
            $message_type = 'error';
        } elseif ($quote['status'] === 'converted') {
            $message = t('quotations.msg_already_converted', 'ໃບສະເໜີລາຄານີ້ຖືກຂາຍໄປແລ້ວ!');
            $message_type = 'error';
        } else {
            $dStmt = $pdo->prepare("SELECT * FROM quotation_details WHERE quotation_id = ? ORDER BY detail_id ASC");
            $dStmt->execute([$id]);
            $items = $dStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($items)) {
                $message = t('quotations.msg_need_items', 'ກະລຸນາເພີ່ມລາຍການສິນຄ້າຢ່າງໜ້ອຍ 1 ລາຍການ!');
                $message_type = 'error';
            } else {
                try {
                    $pdo->beginTransaction();

                    $userReceiveName = $_SESSION['username'] ?? $_SESSION['fname'] ?? 'Admin';
                    $soldById = intval($_SESSION['user_id'] ?? 1);

                    $todayDate = date('Y-m-d');
                    $stmtSeq = $pdo->prepare("SELECT COUNT(*) FROM tbsale_save WHERE sale_date = :d");
                    $stmtSeq->execute([':d' => $todayDate]);
                    $nextSeq = intval($stmtSeq->fetchColumn() ?? 0) + 1;
                    $invoiceNo = date('Ymd') . '-' . str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT);

                    $subtotal = (float)$quote['subtotal'];
                    $discountBill = (float)$quote['discount_amount'];
                    $netTotal = (float)$quote['total_amount'];
                    $totalQty = 0.0;
                    foreach ($items as $it) $totalQty += floatval($it['qty']);

                    $pdo->prepare("
                        INSERT INTO tbsale_save (sale_save_bill, sale_date, sale_time, user_receive, customer_id, customer_name, ip_address, sale_qty, sale_amount, sale_discount_bill, sale_barlance, sale_pay, sale_return, type_pay, sale_status, store_id, sale_remark)
                        VALUES (?, CURDATE(), CURTIME(), ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 'SUCCESS', ?, ?)
                    ")->execute([
                        $invoiceNo, $userReceiveName, $quote['customer_id'], $quote['customer_name'],
                        $_SERVER['REMOTE_ADDR'] ?? '', $totalQty, $subtotal, $discountBill, $netTotal, $netTotal,
                        $payment_type, $activeStoreId, 'Quotation To Sale: ' . $quote['quotation_no']
                    ]);

                    try {
                        $pdo->prepare("
                            INSERT INTO sales (invoice_number, sold_by, customer_id, customer_name, subtotal, discount_amount, vat_amount, total_amount, cash_received, change_amount, payment_type, status, store_id, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 'SUCCESS', ?, NOW())
                        ")->execute([
                            $invoiceNo, $soldById, $quote['customer_id'], $quote['customer_name'],
                            $subtotal, $discountBill, (float)$quote['vat_amount'], $netTotal, $netTotal,
                            $payment_type, $activeStoreId,
                        ]);
                    } catch (Throwable $ex) {}

                    $stmtDetail = $pdo->prepare("
                        INSERT INTO tbsale_save_detail (save_bill, save_date, save_time, save_proid, save_proname, save_qty, save_price, cost_price, save_money, save_net_money, user_receives)
                        VALUES (?, CURDATE(), CURTIME(), ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmtCheckStock = $pdo->prepare("SELECT product_name, qty, cut_qty, bprice FROM products WHERE product_id = ? AND store_id = ? FOR UPDATE");
                    $stmtUpdateStock = $pdo->prepare("UPDATE products SET qty = qty - ? WHERE product_id = ? AND store_id = ?");

                    foreach ($items as $it) {
                        $pid = intval($it['product_id'] ?? 0);
                        $qty = floatval($it['qty']);
                        $price = floatval($it['price']); // the quoted price — never re-priced at conversion time
                        $lineTotal = ($qty * $price) - floatval($it['discount'] ?? 0);
                        $proname = trim((string)$it['product_name']) . (!empty($it['unit_name']) ? ' (' . $it['unit_name'] . ')' : '');
                        $costPrice = 0.0;

                        if ($pid > 0) {
                            $stmtCheckStock->execute([$pid, $activeStoreId]);
                            $pData = $stmtCheckStock->fetch(PDO::FETCH_ASSOC);
                            if ($pData) {
                                $costPrice = floatval($pData['bprice'] ?? 0);
                                $shouldCut = intval($pData['cut_qty'] ?? 1);
                                $currentStock = floatval($pData['qty']);
                                if ($shouldCut && $currentStock < $qty) {
                                    $pdo->rollBack();
                                    $message = sprintf(t('quotations.msg_out_of_stock', 'ສິນຄ້າ "%s" ເຫຼືອພຽງ %s ອັນ! ບໍ່ພໍສຳລັບການຂາຍ'), $pData['product_name'], $currentStock);
                                    $message_type = 'error';
                                    break;
                                }
                                if ($shouldCut) {
                                    $stmtUpdateStock->execute([$qty, $pid, $activeStoreId]);
                                }
                            }
                        }

                        $proname_with_ref = $proname . ' (ຈາກໃບສະເໜີລາຄາ ' . $quote['quotation_no'] . ')';

                        $stmtDetail->execute([$invoiceNo, $pid ?: null, $proname_with_ref, $qty, $price, $costPrice, $lineTotal, $lineTotal, $userReceiveName]);
                    }

                    if ($pdo->inTransaction()) {
                        $pdo->prepare("UPDATE quotations SET status = 'converted', notes = CONCAT(COALESCE(notes, ''), ?) WHERE quotation_id = ?")
                            ->execute([' [→ ' . $invoiceNo . ']', $id]);
                        $pdo->commit();
                        $message = sprintf(t('quotations.msg_converted', 'ຂາຍສຳເລັດ! ເລກບິນ: %s'), $invoiceNo);
                        $message_type = 'success';
                        logActivity($pdo, "ຂາຍຈາກໃບສະເໜີລາຄາ", "{$quote['quotation_no']} → {$invoiceNo}");
                    }
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $message = t('quotations.msg_error_prefix', 'ຜິດພາດ: ') . $e->getMessage();
                    $message_type = 'error';
                }
            }
        }
    } elseif ($action === 'delete_quotation') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("DELETE FROM quotation_details WHERE quotation_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM quotations WHERE quotation_id = ? AND store_id = ?")->execute([$id, $activeStoreId]);
            $message = t('quotations.msg_deleted', 'ລົບໃບສະເໜີລາຄາສຳເລັດ!');
            $message_type = 'success';
            logActivity($pdo, "ລົບໃບສະເໜີລາຄາ", "ID: $id");
        }
    }

    if (!empty($_POST['is_ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => ($message_type === 'success'), 'message' => $message]);
        exit();
    }
}

// ---------- List data (page render + fetch_table AJAX refresh) ----------
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$where = ["q.store_id = :store_id"];
$params = [':store_id' => $activeStoreId];
if ($statusFilter !== '') {
    $where[] = "q.status = :status";
    $params[':status'] = $statusFilter;
}
$whereSql = implode(' AND ', $where);

$quotations = [];
try {
    $listStmt = $pdo->prepare("
        SELECT q.*, COALESCE(NULLIF(u.fname, ''), u.username) AS creator_name
        FROM quotations q
        LEFT JOIN tbuser u ON q.created_by = u.Id
        WHERE {$whereSql}
        ORDER BY q.quotation_id DESC
    ");
    $listStmt->execute($params);
    $quotations = $listStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

// Customers directory, for the picker (falls back to free-text if the list is empty)
$customersList = [];
try {
    $custStmt = $pdo->prepare("SELECT customer_id, customer_name, phone, address FROM customers WHERE store_id = ? ORDER BY customer_name ASC");
    $custStmt->execute([$activeStoreId]);
    $customersList = $custStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

// Category list, to filter the tick-box product picker.
$categoriesLookup = [];
try {
    $catStmt = $pdo->prepare("SELECT category_id, category_name FROM categories WHERE store_id = ? OR store_id = 1 ORDER BY category_name ASC");
    $catStmt->execute([$activeStoreId]);
    $categoriesLookup = $catStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

// Product directory for the tick-box picker (browse by category, no typing needed) — the
// user can still edit qty/price/discount per line after picking.
$productsLookup = [];
try {
    $prodStmt = $pdo->prepare("SELECT product_id, product_name, barcode, code1, price, unit, category_id FROM products WHERE store_id = ? ORDER BY product_name ASC");
    $prodStmt->execute([$activeStoreId]);
    foreach ($prodStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $productsLookup[] = [
            'id'          => (int)$row['product_id'],
            'name'        => $row['product_name'],
            'barcode'     => (string)($row['barcode'] ?? ''),
            'code'        => (string)($row['code1'] ?? ''),
            'category_id' => (int)($row['category_id'] ?? 0),
            'price'       => (float)$row['price'],
            'unit'        => (string)($row['unit'] ?? ''),
        ];
    }
} catch (Throwable $e) {}

// Active display currency, so amounts render in whatever currency the shop uses
$quoteDispCur = function_exists('getDisplayCurrency') ? getDisplayCurrency($pdo) : ['code' => 'KIP', 'symbol' => '₭', 'decimals' => 0, 'rates' => null];
$quoteFmtMoney = function ($amountKip) use ($quoteDispCur) {
    $code = strtoupper((string)($quoteDispCur['code'] ?? 'KIP'));
    if ($code === 'KIP' || !function_exists('convertFromKip')) {
        return number_format((float)$amountKip, 0) . ' ₭';
    }
    $val = convertFromKip($amountKip, $code, $quoteDispCur['rates'] ?? null);
    $dec = (int)($quoteDispCur['decimals'] ?? 2);
    return number_format((float)$val, $dec, '.', ',') . ' ' . (string)($quoteDispCur['symbol'] ?? $code);
};

if (!empty($_GET['fetch_table'])) {
    require __DIR__ . '/../pages/quotations/partials/quotations_table.php';
    exit();
}
