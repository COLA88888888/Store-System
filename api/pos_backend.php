<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($base_path)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';
}
require_once __DIR__ . '/../config/db.php';

// Check authorization
if (empty($_SESSION['user_id'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ເຊດຊັນໝົດອາຍຸ! ກະລຸນາເຂົ້າສູ່ລະບົບໃໝ່']);
        exit();
    }
    echo "<script>window.top.location.href = '../index.php';</script>";
    exit();
}

$vat_rate = floatval(getSetting($pdo, 'vat_rate', '0'));

// Fetch Company/Store info for the branch making this sale — shared name/address/phone from
// the main branch, logo specific to this branch.
$company = getCompanyInfoForBranch($pdo, getActiveStoreId($pdo));

// Helper function to get client IP
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}

// Live Stock Polling Handler
if (isset($_GET['action']) && $_GET['action'] === 'get_live_stocks') {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    $liveStoreId = getActiveStoreId($pdo);
    $stmt = $pdo->prepare("SELECT product_id, qty FROM products WHERE store_id = ?");
    $stmt->execute([$liveStoreId]);
    $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'stocks' => $stocks]);
    exit();
}

// 1. AJAX handler for Checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    ob_start();
    header('Content-Type: application/json');
    $cart = json_decode($_POST['cart'] ?? '[]', true);
    $cash_received = floatval($_POST['cash_received'] ?? 0.00);
    $qr_received   = floatval($_POST['qr_received']   ?? 0.00);
    $payment_type  = $_POST['payment_type'] ?? 'ເງິນສົດ';
    $pay_mode      = $_POST['pay_mode']     ?? 'single';
    $discount_bill = floatval($_POST['discount_amount'] ?? 0.00);
    $customer_id   = !empty($_POST['customer_id']) ? strval($_POST['customer_id']) : '';
    $customer_name = !empty($_POST['customer_name']) ? trim($_POST['customer_name']) : 'ລູກຄ້າທົ່ວໄປ';
    $client_ip     = getClientIP();

    // Tender currency for this bill (chosen at checkout). Base data stays in KIP; these
    // just record what the customer handed over so the daily report can split by currency.
    $allowed_currencies = ['KIP', 'THB', 'USD', 'CNY'];
    $paid_currency = strtoupper(trim((string)($_POST['paid_currency'] ?? 'KIP')));
    if (!in_array($paid_currency, $allowed_currencies, true)) $paid_currency = 'KIP';
    $paid_rate = ($paid_currency === 'KIP') ? 1.0 : (float)($_POST['paid_rate'] ?? 0);
    if ($paid_rate <= 0) { $paid_currency = 'KIP'; $paid_rate = 1.0; }
    
    if (empty($cart)) {
        echo json_encode(['success' => false, 'message' => 'ກະຕ່າສິນຄ້າຫວ່າງເປົ່າ!']);
        exit();
    }
    
    try {
        $pdo->beginTransaction();
        
        $user_receive_name = $_SESSION['username'] ?? $_SESSION['fname'] ?? 'Admin';
        $sold_by_id        = intval($_SESSION['user_id'] ?? 1);
        
        // Calculate totals & Tax/VAT
        $subtotal = 0;
        $total_cost = 0;
        $total_qty = 0;
        
        foreach ($cart as $item) {
            $qty = floatval($item['quantity']);
            $price = floatval($item['unit_price']);
            $cost = floatval($item['cost_price']);
            $subtotal += ($qty * $price);
            $total_cost += ($qty * $cost);
            $total_qty += $qty;
        }
        
        $tax_type = !empty($company['tax_type']) ? $company['tax_type'] : 'none';
        $vat_percent = ($tax_type === 'none') ? 0.00 : floatval($company['vat_percent'] ?? 0);

        $amount_after_discount = max(0, $subtotal - $discount_bill);
        $vat_amount = 0.00;
        $net_total = $amount_after_discount;

        if ($tax_type === 'exclusive' && $vat_percent > 0) {
            $vat_amount = round($amount_after_discount * ($vat_percent / 100), 2);
            $net_total = $amount_after_discount + $vat_amount;
        } elseif ($tax_type === 'inclusive' && $vat_percent > 0) {
            $vat_amount = round($amount_after_discount - ($amount_after_discount / (1 + ($vat_percent / 100))), 2);
            $net_total = $amount_after_discount;
        }

        $total_paid = $cash_received + $qr_received;
        $change = max(0, $total_paid - $net_total);

        // Net bill value expressed in the tendered currency (KIP amount / rate).
        $paid_amount_foreign = ($paid_currency === 'KIP')
            ? round($net_total, 2)
            : round($net_total / $paid_rate, 2);

        // Generate daily resetting invoice number starting from 0001 (Format: YYYYMMDD-0001)
        $todayDate = date('Y-m-d');
        $todayStr  = date('Ymd');

        $stmtSeq = $pdo->prepare("SELECT COUNT(*) FROM tbsale_save WHERE sale_date = :today_date");
        $stmtSeq->execute([':today_date' => $todayDate]);
        $todayCount = intval($stmtSeq->fetchColumn() ?? 0);
        $nextSeq = $todayCount + 1;

        $invoice_no = $todayStr . '-' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
        // Queue number: same daily-resetting counter, zero-padded to 2 digits (01, 02, ... 10).
        $queue_display = str_pad((string)$nextSeq, 2, '0', STR_PAD_LEFT);
        $activeStoreId = getActiveStoreId($pdo);
        
        $bank_account_id = isset($_POST['bank_account_id']) && intval($_POST['bank_account_id']) > 0 ? intval($_POST['bank_account_id']) : null;
        $bank_name = isset($_POST['bank_name']) && trim($_POST['bank_name']) !== '' ? trim($_POST['bank_name']) : null;

        if (empty($bank_account_id) && ($qr_received > 0 || strpos($payment_type, 'ໂອນ') !== false || strpos($payment_type, 'QR') !== false || strpos($payment_type, 'Transfer') !== false)) {
            try {
                $bStmt = $pdo->query("SELECT * FROM bank_accounts WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
                $bank_info = $bStmt->fetch(PDO::FETCH_ASSOC);
                if ($bank_info) {
                    $bank_account_id = intval($bank_info['id']);
                    $bank_name = $bank_info['bank_name'];
                }
            } catch (Throwable $e) {}
        } else if ($bank_account_id) {
            try {
                $bStmt = $pdo->prepare("SELECT * FROM bank_accounts WHERE id = ?");
                $bStmt->execute([$bank_account_id]);
                $bank_info = $bStmt->fetch(PDO::FETCH_ASSOC);
                if ($bank_info && !empty($bank_info['bank_name'])) {
                    $bank_name = $bank_info['bank_name'];
                }
            } catch (Throwable $e) {}
        }

        // Insert into tbsale_save
        $stmtSave = $pdo->prepare("
            INSERT INTO tbsale_save (sale_save_bill, sale_date, sale_time, user_receive, customer_id, customer_name, ip_address, sale_qty, sale_amount, sale_discount_bill, sale_barlance, sale_pay, sale_return, type_pay, bank_account_id, bank_name, sale_status, store_id, paid_currency, paid_rate, paid_amount_foreign, queue_no)
            VALUES (:invoice_no, CURDATE(), CURTIME(), :user_id, :customer_id, :customer_name, :ip_address, :sale_qty, :sale_amount, :discount_bill, :net_total, :cash_received, :change, :payment_type, :bank_account_id, :bank_name, 'SUCCESS', :store_id, :paid_currency, :paid_rate, :paid_amount_foreign, :queue_no)
        ");
        $stmtSave->execute([
            ':invoice_no'      => $invoice_no,
            ':queue_no'        => $queue_display,
            ':user_id'         => $user_receive_name,
            ':customer_id'     => $customer_id,
            ':customer_name'   => $customer_name,
            ':ip_address'      => $client_ip,
            ':sale_qty'        => $total_qty,
            ':sale_amount'     => $subtotal,
            ':discount_bill'   => $discount_bill,
            ':net_total'       => $net_total,
            ':cash_received'   => $cash_received,
            ':change'          => $change,
            ':payment_type'    => $payment_type,
            ':bank_account_id' => $bank_account_id,
            ':bank_name'       => $bank_name,
            ':store_id'        => $activeStoreId,
            ':paid_currency'      => $paid_currency,
            ':paid_rate'         => $paid_rate,
            ':paid_amount_foreign' => $paid_amount_foreign
        ]);

        // Insert into physical sales table with tax details
        try {
            $stmtSalesTable = $pdo->prepare("
                INSERT INTO sales (invoice_number, sold_by, customer_id, customer_name, subtotal, discount_amount, vat_amount, tax_type, vat_rate, total_amount, cash_received, change_amount, payment_type, bank_account_id, bank_name, status, store_id, created_at, paid_currency, paid_rate, paid_amount_foreign)
                VALUES (:invoice_no, :sold_by_id, :customer_id, :customer_name, :subtotal, :discount_bill, :vat_amount, :tax_type, :vat_rate, :net_total, :cash_received, :change, :payment_type, :bank_account_id, :bank_name, 'SUCCESS', :store_id, NOW(), :paid_currency, :paid_rate, :paid_amount_foreign)
                ON DUPLICATE KEY UPDATE subtotal = :subtotal, discount_amount = :discount_bill, vat_amount = :vat_amount, tax_type = :tax_type, vat_rate = :vat_rate, total_amount = :net_total, bank_account_id = :bank_account_id, bank_name = :bank_name, store_id = :store_id, paid_currency = :paid_currency, paid_rate = :paid_rate, paid_amount_foreign = :paid_amount_foreign
            ");
            $stmtSalesTable->execute([
                ':invoice_no'      => $invoice_no,
                ':sold_by_id'      => $sold_by_id,
                ':customer_id'     => $customer_id,
                ':customer_name'   => $customer_name,
                ':subtotal'        => $subtotal,
                ':discount_bill'   => $discount_bill,
                ':vat_amount'      => $vat_amount,
                ':tax_type'        => $tax_type,
                ':vat_rate'        => $vat_percent,
                ':net_total'       => $net_total,
                ':cash_received'   => $cash_received,
                ':change'          => $change,
                ':payment_type'    => $payment_type,
                ':bank_account_id' => $bank_account_id,
                ':bank_name'       => $bank_name,
                ':store_id'        => $activeStoreId,
                ':paid_currency'      => $paid_currency,
                ':paid_rate'         => $paid_rate,
                ':paid_amount_foreign' => $paid_amount_foreign
            ]);
        } catch (Throwable $ex) {}
        
        // Insert into tbsale_save_detail and update inventory
        $stmtDetail = $pdo->prepare("
            INSERT INTO tbsale_save_detail (save_bill, save_date, save_time, save_proid, save_proname, save_qty, save_price, cost_price, save_money, save_net_money, user_receives, multiplier)
            VALUES (:save_bill, CURDATE(), CURTIME(), :save_proid, :save_proname, :save_qty, :save_price, :cost_price, :save_money, :save_net_money, :user_receives, :multiplier)
        ");
        
        $stmtUpdateStock = $pdo->prepare("
            UPDATE products SET qty = qty - :deduct_qty WHERE product_id = :product_id AND store_id = :store_id
        ");

        $stmtCheckStock = $pdo->prepare("
            SELECT product_name, qty, cut_qty FROM products WHERE product_id = :pid AND store_id = :store_id FOR UPDATE
        ");
        
        $details_summary = [];
        $updated_stocks = [];
        
        foreach ($cart as $item) {
            $pid = intval($item['product_id']);
            $proname = $item['product_name'] . ' (' . $item['unit_name'] . ')';
            $qty = floatval($item['quantity']);
            $price = floatval($item['unit_price']);
            $cost = floatval($item['cost_price']);
            $multiplier = intval($item['multiplier'] ?? 1);
            $total_item = $qty * $price;
            $deduct_stock_qty = $qty * $multiplier;

            $is_gift = !empty($item['is_free_gift']) || $price == 0 || strpos($item['product_name'], '(ແຖມ)') !== false;

            if ($is_gift) {
                $stmtCheckStock->execute([':pid' => $pid, ':store_id' => $activeStoreId]);
                $pData = $stmtCheckStock->fetch();
                $shouldCutQty = $pData ? intval($pData['cut_qty'] ?? 0) : 0;
                $currentDbStock = $pData ? floatval($pData['qty']) : 0;
            } else {
                $stmtCheckStock->execute([':pid' => $pid, ':store_id' => $activeStoreId]);
                $pData = $stmtCheckStock->fetch();
                if (!$pData) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    echo json_encode(['success' => false, 'message' => "ບໍ່ພົບຂໍ້ມູນສິນຄ້າ ID: {$pid} ໃນລະບົບ!"]);
                    exit();
                }

                $shouldCutQty = intval($pData['cut_qty'] ?? 1); // 1=ຕັດ qty, 0=ບໍ່ຕັດ qty
                $currentDbStock = floatval($pData['qty']);

                if ($shouldCutQty) {
                    if ($currentDbStock <= 0) {
                        if ($pdo->inTransaction()) $pdo->rollBack();
                        echo json_encode(['success' => false, 'message' => "ສິນຄ້າ \"{$pData['product_name']}\" ໝົດແລ້ວ! ບໍ່ສາມາດຂາຍໄດ້"]);
                        exit();
                    }

                    if ($currentDbStock < $deduct_stock_qty) {
                        if ($pdo->inTransaction()) $pdo->rollBack();
                        echo json_encode(['success' => false, 'message' => "ສິນຄ້າ \"{$pData['product_name']}\" ເຫຼືອພຽງ {$currentDbStock} ອັນ! ບໍ່ພໍສຳລັບການຂາຍ"]);
                        exit();
                    }
                }
            }
            
            $stmtDetail->execute([
                ':save_bill'    => $invoice_no,
                ':save_proid'   => $pid,
                ':save_proname' => $proname,
                ':save_qty'     => $qty,
                ':save_price'   => $price,
                ':cost_price'   => $cost,
                ':save_money'   => $total_item,
                ':save_net_money'=> $total_item,
                ':user_receives'=> $user_receive_name,
                ':multiplier'   => $multiplier
            ]);
            
            $newStock = $currentDbStock;
            // ຕັດ qty ສະເພາະສິນຄ້າທີ່ cut_qty = 1 ເທົ່ານັ້ນ
            if ($shouldCutQty) {
                $stmtUpdateStock->execute([
                    ':deduct_qty' => $deduct_stock_qty,
                    ':product_id' => $pid,
                    ':store_id'   => $activeStoreId
                ]);
                $newStock = max(0, $currentDbStock - $deduct_stock_qty);
            }

            $updated_stocks[] = [
                'product_id' => $pid,
                'new_qty'    => $newStock,
                'cut_qty'    => $shouldCutQty
            ];

            $unit_name = trim($item['unit_name'] ?? '');
            $proname = $item['product_name'] . ($unit_name !== '' ? ' (' . $unit_name . ')' : '');
            $orig_price = floatval($item['original_price'] ?? $price);
            $is_gift = !empty($item['is_free_gift']) || $price == 0 || strpos($item['product_name'], '(ແຖມ)') !== false;

            $details_summary[] = [
                'proname'        => $proname,
                'unit_name'      => $unit_name,
                'qty'            => $qty,
                'price'          => $price,
                'original_price' => $orig_price,
                'is_free_gift'   => $is_gift,
                'total'          => $total_item
            ];
        }
        
        // ===== CUSTOMER LOYALTY POINTS: earn Y points for every X kip of net spend =====
        $points_earned = 0;
        $customer_points_balance = 0;
        $loyalty_active = 0;
        if (!empty($customer_id) && intval($customer_id) > 0) {
            if (getSetting($pdo, 'loyalty_enabled', '0') === '1') {
                $loyalty_active = 1;
                $lpSpend = (int)getSetting($pdo, 'loyalty_spend_amount', '100000');
                $lpPer   = (int)getSetting($pdo, 'loyalty_points_earned', '1');
                if ($lpSpend > 0 && $lpPer > 0) {
                    $points_earned = intdiv((int)round($net_total), $lpSpend) * $lpPer;
                    if ($points_earned > 0) {
                        try {
                            $pdo->prepare("UPDATE customers SET points = points + ? WHERE customer_id = ?")
                                ->execute([$points_earned, intval($customer_id)]);
                            $pdo->prepare("UPDATE tbsale_save SET points_earned = ? WHERE sale_save_bill = ?")
                                ->execute([$points_earned, $invoice_no]);
                        } catch (Throwable $e) { $points_earned = 0; }
                    }
                }
            }
            try {
                $cpStmt = $pdo->prepare("SELECT points FROM customers WHERE customer_id = ?");
                $cpStmt->execute([intval($customer_id)]);
                $customer_points_balance = (int)$cpStmt->fetchColumn();
            } catch (Throwable $e) {}
        }

        $pdo->commit();

        if (ob_get_length()) ob_clean();
        echo json_encode([
            'success'        => true,
            'points_earned'   => $points_earned,
            'customer_points' => $customer_points_balance,
            'loyalty_active'  => $loyalty_active,
            'invoice_number' => $invoice_no,
            'queue_number'   => $queue_display,
            'date'           => date('d/m/Y H:i'),
            'cashier'        => $user_receive_name,
            'customer_name'  => $customer_name,
            'customer_phone' => trim($_POST['customer_phone'] ?? ''),
            'subtotal'       => $subtotal,
            'discount_amount'=> $discount_bill,
            'tax_type'       => $tax_type,
            'vat_rate'       => $vat_percent,
            'vat_amount'     => $vat_amount,
            'total_amount'   => $net_total,
            'cash_received'  => $cash_received,
            'qr_received'    => $qr_received,
            'pay_mode'       => $pay_mode,
            'change'         => $change,
            'payment_type'   => $payment_type,
            'bank_account_id'=> $bank_account_id,
            'bank_name'      => $bank_name,
            'bank_account_no'=> $bank_info['account_number'] ?? '',
            'bank_account_name'=> $bank_info['account_name'] ?? '',
            'bank_qr_img'    => resolveBankQr($bank_info['qr_code_img'] ?? '', $bank_info['bank_code'] ?? ''),
            'details'        => $details_summary,
            'updated_stocks' => $updated_stocks
        ]);
        exit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'message' => 'ເກີດຂໍ້ຜິດພາດ: ' . $e->getMessage()]);
        exit();
    }
}

// 2. AJAX handler for Quick Add Customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_customer_ajax') {
    header('Content-Type: application/json');
    $c_name        = trim($_POST['customer_name'] ?? '');
    $c_phone       = trim($_POST['phone'] ?? '');
    $c_address     = trim($_POST['address'] ?? '');
    $c_code        = trim($_POST['customer_code'] ?? '');
    $c_member_card = trim($_POST['member_card'] ?? '');
    $c_notes       = trim($_POST['notes'] ?? '');

    if (empty($c_name)) {
        echo json_encode(['success' => false, 'message' => 'ກະລຸນາປ້ອນຊື່ລູກຄ້າ!']);
        exit();
    }

    $activePosStoreId = getActiveStoreId($pdo);
    if (empty($c_code)) {
        $stmtSeq = $pdo->prepare("SELECT IFNULL(MAX(customer_id), 0) + 1 FROM customers WHERE store_id = ?");
        $stmtSeq->execute([$activePosStoreId]);
        $maxCusId = (int)$stmtSeq->fetchColumn();
        $c_code = 'CUST-' . str_pad($activePosStoreId, 2, '0', STR_PAD_LEFT) . '-' . str_pad($maxCusId, 3, '0', STR_PAD_LEFT);
    }

    try {
        $stmtIns = $pdo->prepare("INSERT INTO customers (customer_code, customer_name, phone, address, member_card, notes, store_id, created_at) VALUES (:code, :name, :phone, :address, :card, :notes, :store_id, NOW())");
        $stmtIns->execute([
            ':code'     => $c_code,
            ':name'     => $c_name,
            ':phone'    => $c_phone,
            ':address'  => $c_address,
            ':card'     => $c_member_card,
            ':notes'    => $c_notes,
            ':store_id' => $activePosStoreId
        ]);
        $new_id = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'customer' => [
                'customer_id'   => $new_id,
                'customer_code' => $c_code,
                'customer_name' => $c_name,
                'phone'         => $c_phone,
                'address'       => $c_address,
                'member_card'   => $c_member_card,
                'notes'         => $c_notes
            ]
        ]);
        exit();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'ຜິດພາດ: ' . $e->getMessage()]);
        exit();
    }
}

// Auto-deactivate expired promotions & Fetch active promotions for current branch/store
$activePromos = [];
try {
    // Scope promos to the branch the POS is actually operating as (honours ?switch_store_id
    // for admin/main-branch users), matching how products/categories are scoped below.
    $currentPosStoreId = getActiveStoreId($pdo);
    $pdo->exec("UPDATE promotions SET status = 0 WHERE status = 1 AND end_date < CURDATE()");
    $stmtActivePromos = $pdo->prepare("
        SELECT * FROM promotions 
        WHERE status = 1 
          AND (branch_id = ? OR branch_id = 0)
          AND CURDATE() BETWEEN start_date AND end_date 
        ORDER BY id DESC
    ");
    $stmtActivePromos->execute([$currentPosStoreId]);
    $activePromos = $stmtActivePromos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Loosen string comparison so a promo's free-typed target still matches the real product/
// category name despite spacing, casing, zero-width chars or Unicode form differences
// (common with Lao text entered by hand).
function promoNormalizeName($s) {
    $s = (string)$s;
    if (class_exists('Normalizer')) {
        $n = Normalizer::normalize($s, Normalizer::FORM_C);
        if ($n !== false) $s = $n;
    }
    // Fold the two ways Lao vowels get typed: ເ+ເ vs ແ, and ເ+ໂ etc. (ແກັດ vs ເເກັດ, ແກ້ວ vs ເເກ້ວ)
    $s = str_replace(["\u{0EC0}\u{0EC0}", "\u{0EC0}\u{0EC1}"], "\u{0EC1}", $s);
    // drop zero-width / BOM / all whitespace, then lowercase
    $s = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\s]+/u', '', $s);
    return mb_strtolower(trim($s));
}
function promoNameMatches($actual, $target) {
    $a = promoNormalizeName($actual);
    $t = promoNormalizeName($target);
    if ($a === '' || $t === '') return false;
    return $a === $t || mb_stripos($a, $t) !== false || mb_stripos($t, $a) !== false;
}
// Unit matching must be EXACT (after normalising) — never substring. BeerLao's ປ໋ອງຍາວ and
// ປ໋ອງສັ້ນ share the "ປ໋ອງ" prefix, so a loose match would discount both when a promo names
// only one. "all"/empty means every unit.
function promoUnitMatches($actualUnit, $targetUnit) {
    $t = promoNormalizeName($targetUnit);
    if ($t === '' || $t === 'all') return true;
    return promoNormalizeName($actualUnit) === $t;
}

function getProductPromotion($product, $activePromos, $dispCur = null) {
    if (empty($activePromos)) return null;

    $pName = trim($product['product_name'] ?? '');
    $cName = trim($product['category_name'] ?? '');
    // Codes a promo's target may be entered as instead of the product name
    $pCodes = array_filter([
        trim((string)($product['barcode'] ?? '')),
        trim((string)($product['code1'] ?? '')),
        trim((string)($product['product_id'] ?? '')),
    ], 'strlen');

    $matchedDiscountPromo = null;
    $matchedGiftPromo = null;

    foreach ($activePromos as $promo) {
        $targetType = $promo['target_type'] ?? 'all';
        $targetName = trim($promo['target_name'] ?? '');

        // Match the promo to the PRODUCT (or its category, or "all"). Unit scoping is applied
        // later, per sellable unit — a promo aimed at one unit (e.g. ແກັດ) still "belongs" to
        // the product; its other units simply stay at full price.
        $isMatch = false;
        if ($targetType === 'all' || empty($targetName) || $targetName === 'ທຸກສິນຄ້າ') {
            $isMatch = true;
        } elseif ($targetType === 'category') {
            $isMatch = (!empty($cName) && promoNameMatches($cName, $targetName));
        } elseif ($targetType === 'product') {
            $isMatch = (!empty($pName) && promoNameMatches($pName, $targetName))
                       || in_array($targetName, $pCodes, true);
        }
        if (!$isMatch) continue;

        if (floatval($promo['discount_value'] ?? 0) > 0 && !$matchedDiscountPromo) {
            $matchedDiscountPromo = $promo;
        }
        if (trim($promo['gift_product_name'] ?? '') !== '' && !$matchedGiftPromo) {
            $matchedGiftPromo = $promo;
        }
        if ($matchedDiscountPromo && $matchedGiftPromo) break;
    }

    $promoToUse = $matchedDiscountPromo ?? $matchedGiftPromo;
    if (!$promoToUse) return null;

    $promoType = $promoToUse['promo_type'] ?? 'discount';
    $discType  = $promoToUse['discount_type'] ?? 'percentage';
    $discVal   = floatval($promoToUse['discount_value'] ?? 0);
    $giftName  = trim($matchedGiftPromo['gift_product_name'] ?? $promoToUse['gift_product_name'] ?? '');
    $giftQty   = intval($matchedGiftPromo['gift_qty'] ?? $promoToUse['gift_qty'] ?? 1);
    if ($giftQty < 1) $giftQty = 1;

    $tunit = trim($promoToUse['target_unit_name'] ?? 'all');
    if ($tunit === '') $tunit = 'all';
    $unitSuffix = ($tunit !== 'all') ? ' (ສະເພາະ ' . $tunit . ')' : '';

    // Discount descriptor (unit-agnostic — the actual amount is computed per selected unit).
    // A fixed ₭ amount is shown converted into the POS's active display currency.
    $discountText = '';
    if ($discType === 'percentage' && $discVal > 0) {
        $discountText = 'ຫຼຸດ ' . (floor($discVal) == $discVal ? intval($discVal) : number_format($discVal, 1)) . '%';
    } elseif ($discVal > 0) {
        $curCode = strtoupper((string)($dispCur['code'] ?? 'KIP'));
        if ($curCode !== 'KIP' && function_exists('convertFromKip')) {
            $amt = convertFromKip($discVal, $curCode, $dispCur['rates'] ?? null);
            $dec = (int)($dispCur['decimals'] ?? 2);
            $sym = (string)($dispCur['symbol'] ?? $curCode);
            $discountText = 'ຫຼຸດ ' . number_format((float)$amt, $dec, '.', ',') . ' ' . $sym;
        } else {
            $discountText = 'ຫຼຸດ ' . number_format($discVal) . '₭';
        }
    }
    $giftText = ($giftName !== '') ? ('ແຖມ ' . $giftName . ($giftQty > 1 ? ' x' . $giftQty : '')) : '';

    // Badge always leads with the promo name so the cashier can see which promo is in effect
    $parts = array_values(array_filter([$discountText, $giftText]));
    $badge = trim($promoToUse['promo_name'] ?? '') !== '' ? trim($promoToUse['promo_name']) : 'ໂປຣ';
    if ($parts) $badge .= ' · ' . implode(' + ', $parts);
    $badge .= $unitSuffix;

    return [
        'promo_name'        => $promoToUse['promo_name'] ?? '',
        'promo_type'        => $promoType,
        'discount_type'     => $discType,
        'discount_value'    => $discVal,
        'badge'             => $badge,
        'min_qty'           => intval($promoToUse['min_qty'] ?? 0),
        'min_amount'        => floatval($promoToUse['min_amount'] ?? 0),
        'gift_product_name' => $giftName,
        'gift_qty'          => $giftQty,
        'target_unit_name'  => $tunit
    ];
}

// Apply a promo to ONE sellable unit. Returns [originalPrice, effectivePrice, wasDiscounted].
// The discount only bites when the promo targets "all" units or this unit's name — so a promo
// scoped to ແກັດ leaves ຫໍ່ (and every other unit) untouched.
function applyPromoToUnit($promo, $unitName, $unitPrice) {
    $unitPrice = floatval($unitPrice);
    $hit = promoUnitMatches($unitName, $promo['target_unit_name'] ?? 'all');
    if (!$hit || floatval($promo['discount_value'] ?? 0) <= 0) {
        return [$unitPrice, $unitPrice, false];
    }
    $dv = floatval($promo['discount_value']);
    if (($promo['discount_type'] ?? 'percentage') === 'percentage') {
        $eff = $unitPrice - ($unitPrice * $dv / 100);
    } else {
        $eff = $unitPrice - $dv;
    }
    return [$unitPrice, max(0, round($eff)), true];
}

// Fetch categories, products, and customers list
$activeStoreId = getActiveStoreId($pdo);
$categoriesStmt = $pdo->prepare("SELECT * FROM categories WHERE store_id = ? ORDER BY category_name ASC");
$categoriesStmt->execute([$activeStoreId]);
$categories = $categoriesStmt->fetchAll();

// Kitchen-ticket routing for this branch: category_id => {printer_id, printer_name}.
// Only categories with an active printer assigned; the POS uses this to split the
// print by category at checkout (each printer gets a ticket with only its items).
$posKitchenRoutes = [];
try {
    $krStmt = $pdo->prepare("
        SELECT c.category_id, c.printer_id, p.name AS printer_name
        FROM categories c
        JOIN tbl_printer p ON p.id = c.printer_id AND p.status = 1
        WHERE c.store_id = ? AND c.printer_id IS NOT NULL
    ");
    $krStmt->execute([$activeStoreId]);
    foreach ($krStmt->fetchAll() as $kr) {
        $posKitchenRoutes[(string)$kr['category_id']] = [
            'printer_id'   => (int)$kr['printer_id'],
            'printer_name' => $kr['printer_name'],
        ];
    }
} catch (Throwable $e) {}
$productsStmt = $pdo->prepare("
    SELECT p.*, c.category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE p.store_id = ?
    ORDER BY p.code1 DESC, p.product_name ASC
");
$productsStmt->execute([$activeStoreId]);
$productsRaw = $productsStmt->fetchAll();

$stmtPosCust = $pdo->prepare("SELECT customer_id, customer_code, customer_name, phone, address, member_card, notes, points, created_at FROM customers WHERE store_id = ? OR store_id = 0 ORDER BY customer_id DESC");
$stmtPosCust->execute([$activeStoreId]);
$customersList = $stmtPosCust->fetchAll();

// Fetch extra units
$extraUnits = $pdo->query("SELECT * FROM product_units ORDER BY multiplier ASC, id ASC")->fetchAll();
$unitsMap = [];
foreach ($extraUnits as $u) {
    $unitsMap[$u['product_id']][] = $u;
}

// Map products with complete unit structures and promotions
$posDisplayCurrency = function_exists('getDisplayCurrency') ? getDisplayCurrency($pdo) : ['code' => 'KIP'];
$products = [];
foreach ($productsRaw as $p) {
    $pid = $p['product_id'];
    $p['extra_units'] = $unitsMap[$pid] ?? [];

    $promo = getProductPromotion($p, $activePromos, $posDisplayCurrency);
    if ($promo) {
        // Price each unit independently so only the promo's target unit is discounted.
        list($bOrig, $bEff, $anyHit) = applyPromoToUnit($promo, $p['unit'] ?? '', $p['price']);
        $p['original_price'] = $bOrig;
        $p['price']          = $bEff;

        if (!empty($p['extra_units'])) {
            foreach ($p['extra_units'] as &$eu) {
                list($euOrig, $euEff, $euHit) = applyPromoToUnit($promo, $eu['unit_name'] ?? '', $eu['price']);
                $eu['original_price'] = $euOrig;
                $eu['price']          = $euEff;
                $anyHit = $anyHit || $euHit;
            }
            unset($eu);
        }

        $isGift = (trim($promo['gift_product_name'] ?? '') !== '');
        $p['has_promo']         = ($anyHit || $isGift);
        $p['promo_badge']       = $promo['badge'];
        $p['promo_name']        = $promo['promo_name'];
        $p['promo_type']        = $promo['promo_type'] ?? 'discount';
        $p['discount_type']     = $promo['discount_type'] ?? 'percentage';
        $p['discount_value']    = $promo['discount_value'] ?? 0;
        $p['gift_product_name'] = $promo['gift_product_name'] ?? '';
        $p['gift_qty']          = $promo['gift_qty'] ?? 1;
        $p['target_unit_name']  = $promo['target_unit_name'] ?? 'all';
        $p['promo_min_qty']     = $promo['min_qty'] ?? 0;
        $p['promo_min_amount']  = $promo['min_amount'] ?? 0;
    } else {
        $p['has_promo']       = false;
        $p['original_price']  = floatval($p['price']);
    }
    
    $products[] = $p;
}
