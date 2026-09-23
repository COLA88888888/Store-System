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

        // Generate daily resetting invoice number starting from 0001 (Format: YYYYMMDD-0001)
        $todayDate = date('Y-m-d');
        $todayStr  = date('Ymd');

        $stmtSeq = $pdo->prepare("SELECT COUNT(*) FROM tbsale_save WHERE sale_date = :today_date");
        $stmtSeq->execute([':today_date' => $todayDate]);
        $todayCount = intval($stmtSeq->fetchColumn() ?? 0);
        $nextSeq = $todayCount + 1;

        $invoice_no = $todayStr . '-' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
        $activeStoreId = getActiveStoreId($pdo);
        
        $bank_account_id = isset($_POST['bank_account_id']) && intval($_POST['bank_account_id']) > 0 ? intval($_POST['bank_account_id']) : null;
        $bank_name = isset($_POST['bank_name']) && trim($_POST['bank_name']) !== '' ? trim($_POST['bank_name']) : null;

        if (empty($bank_account_id) && ($qr_received > 0 || strpos($payment_type, 'ໂອນ') !== false || strpos($payment_type, 'QR') !== false || strpos($payment_type, 'Transfer') !== false)) {
            try {
                // This branch's own banks, plus any shared across every branch (store_id = 0).
                $bStmt = $pdo->prepare("SELECT * FROM bank_accounts WHERE is_active = 1 AND (store_id = ? OR store_id = 0) ORDER BY id ASC LIMIT 1");
                $bStmt->execute([$activeStoreId]);
                $bank_info = $bStmt->fetch(PDO::FETCH_ASSOC);
                if ($bank_info) {
                    $bank_account_id = intval($bank_info['id']);
                    $bank_name = $bank_info['bank_name'];
                }
            } catch (Throwable $e) {}
        } else if ($bank_account_id) {
            try {
                $bStmt = $pdo->prepare("SELECT * FROM bank_accounts WHERE id = ? AND (store_id = ? OR store_id = 0)");
                $bStmt->execute([$bank_account_id, $activeStoreId]);
                $bank_info = $bStmt->fetch(PDO::FETCH_ASSOC);
                if ($bank_info && !empty($bank_info['bank_name'])) {
                    $bank_name = $bank_info['bank_name'];
                }
            } catch (Throwable $e) {}
        }

        // Insert into quotations
        $stmtSave = $pdo->prepare("
            INSERT INTO quotations (quotation_number, created_by, customer_id, customer_name, subtotal, discount_amount, vat_amount, total_amount, status, store_id)
            VALUES (:invoice_no, :sold_by_id, :customer_id, :customer_name, :subtotal, :discount_bill, :vat_amount, :net_total, 'pending', :store_id)
        ");
        $stmtSave->execute([
            ':invoice_no'      => $invoice_no,
            ':sold_by_id'      => $sold_by_id,
            ':customer_id'     => $customer_id,
            ':customer_name'   => $customer_name,
            ':subtotal'        => $subtotal,
            ':discount_bill'   => $discount_bill,
            ':vat_amount'      => $vat_amount,
            ':net_total'       => $net_total,
            ':store_id'        => $activeStoreId
        ]);
        
        $quotation_id = $pdo->lastInsertId();

        // Insert into quotation_details
        $stmtDetail = $pdo->prepare("
            INSERT INTO quotation_details (quotation_id, product_id, product_name, unit, quantity, price, discount, total)
            VALUES (:quotation_id, :product_id, :product_name, :unit, :quantity, :price, 0, :total)
        ");
        
        $details_summary = [];
        $updated_stocks = []; // Empty since no stock deduction
        
        foreach ($cart as $item) {
            $pid = intval($item['product_id']);
            $unit_name = trim($item['unit_name'] ?? '');
            $proname = $item['product_name'];
            $qty = floatval($item['quantity']);
            $price = floatval($item['unit_price']);
            $total_item = $qty * $price;
            
            $stmtDetail->execute([
                ':quotation_id' => $quotation_id,
                ':product_id'   => $pid,
                ':product_name' => $proname,
                ':unit'         => $unit_name,
                ':quantity'     => $qty,
                ':price'        => $price,
                ':total'        => $total_item
            ]);
            
            $orig_price = floatval($item['original_price'] ?? $price);
            $is_gift = !empty($item['is_free_gift']) || $price == 0 || strpos($item['product_name'], '(ແຖມ)') !== false;

            $details_summary[] = [
                'proname'        => $proname . ($unit_name !== '' ? ' (' . $unit_name . ')' : ''),
                'unit_name'      => $unit_name,
                'qty'            => $qty,
                'price'          => $price,
                'original_price' => $orig_price,
                'is_free_gift'   => $is_gift,
                'total'          => $total_item
            ];
        }
        
        // No loyalty points for quotation

        $pdo->commit();

        if (ob_get_length()) ob_clean();
        echo json_encode([
            'success'        => true,
            'points_earned'   => $points_earned,
            'customer_points' => $customer_points_balance,
            'loyalty_active'  => $loyalty_active,
            'invoice_number' => $invoice_no,
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
    $currentPosStoreId = intval($_SESSION['store_id'] ?? 1);
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

function getProductPromotion($product, $activePromos) {
    if (empty($activePromos)) return null;
    
    $pName = trim($product['product_name'] ?? '');
    $cName = trim($product['category_name'] ?? '');

    $matchedDiscountPromo = null;
    $matchedGiftPromo = null;
    
    foreach ($activePromos as $promo) {
        $targetType = $promo['target_type'] ?? 'all';
        $targetName = trim($promo['target_name'] ?? '');
        $targetUnit = mb_strtolower(trim($promo['target_unit_name'] ?? 'all'));
        $baseUnit   = mb_strtolower(trim($product['unit'] ?? ''));

        $isMatch = false;
        
        if ($targetType === 'all' || empty($targetName) || $targetName === 'ທຸກສິນຄ້າ') {
            $isMatch = true;
        } elseif ($targetType === 'category') {
            if (!empty($cName) && (mb_strtolower($cName) === mb_strtolower($targetName) || mb_stripos($cName, $targetName) !== false || mb_stripos($targetName, $cName) !== false)) {
                $isMatch = true;
            }
        } elseif ($targetType === 'product') {
            if (!empty($pName) && (mb_strtolower($pName) === mb_strtolower($targetName) || mb_stripos($pName, $targetName) !== false || mb_stripos($targetName, $pName) !== false)) {
                $isMatch = true;
            }
        }

        // Check if unit matches target_unit_name
        $isUnitMatch = ($targetUnit === 'all' || empty($targetUnit) || $baseUnit === $targetUnit);
        if (!$isUnitMatch && !empty($baseUnit) && !empty($targetUnit)) {
            $n1 = preg_replace('/ເເກັດ|ເກັດ|ແກັດ|ແກັດ/u', 'ເກັດ', $baseUnit);
            $n2 = preg_replace('/ເເກັດ|ເກັດ|ແກັດ|ແກັດ/u', 'ເກັດ', $targetUnit);
            if ($n1 === $n2 || mb_stripos($baseUnit, $targetUnit) !== false || mb_stripos($targetUnit, $baseUnit) !== false) {
                $isUnitMatch = true;
            }
        }
        
        if ($isMatch) {
            $discVal = floatval($promo['discount_value'] ?? 0);
            $giftName = trim($promo['gift_product_name'] ?? '');
            
            // Apply discount if unit matches OR if target_unit_name is all
            if ($discVal > 0 && !$matchedDiscountPromo && $isUnitMatch) {
                $matchedDiscountPromo = $promo;
            }
            if (!empty($giftName) && !$matchedGiftPromo && $isUnitMatch) {
                $matchedGiftPromo = $promo;
            }
            if ($matchedDiscountPromo && $matchedGiftPromo) {
                break;
            }
        }
    }

    $promoToUse = $matchedDiscountPromo ?? $matchedGiftPromo;
    if (!$promoToUse) return null;

    $origPrice = floatval($product['price'] ?? 0);
    $promoType = $promoToUse['promo_type'] ?? 'discount';
    $discType  = $promoToUse['discount_type'] ?? 'percentage';
    $discVal   = floatval($promoToUse['discount_value'] ?? 0);
    $giftName  = trim($matchedGiftPromo['gift_product_name'] ?? $promoToUse['gift_product_name'] ?? '');
    $giftQty   = intval($matchedGiftPromo['gift_qty'] ?? $promoToUse['gift_qty'] ?? 1);
    if ($giftQty < 1) $giftQty = 1;
    
    $tunit = trim($promoToUse['target_unit_name'] ?? 'all');
    if (empty($tunit)) $tunit = 'all';

    $unitSuffix = ($tunit !== 'all') ? ' (ສະເພາະ ' . $tunit . ')' : '';

    $discountAmount = 0;
    $promoPrice = $origPrice;

    $discountText = '';
    if ($discType === 'percentage' && $discVal > 0) {
        $discountAmount = $origPrice * ($discVal / 100);
        $promoPrice = max(0, $origPrice - $discountAmount);
        $discountText = 'ຫຼຸດ: ' . (floor($discVal) == $discVal ? intval($discVal) : number_format($discVal, 1)) . '% (-' . number_format($discountAmount) . '₭)';
    } elseif ($discVal > 0) {
        $discountAmount = $discVal;
        $promoPrice = max(0, $origPrice - $discountAmount);
        $discountText = 'ຫຼຸດ: ' . number_format($discVal) . '₭';
    }

    $baseUnit = mb_strtolower(trim($product['unit'] ?? ''));
    $tunitLower = mb_strtolower($tunit);
    
    // Check if base product unit matches target unit
    $isUnitMatch = ($tunitLower === 'all' || empty($tunitLower) || $baseUnit === $tunitLower);
    if (!$isUnitMatch) {
        // Normalize Lao spelling variations (ເຊັ່ນ: ເເກັດ vs ເກັດ vs ແກັດ)
        $n1 = preg_replace('/ເເກັດ|ເກັດ|ແກັດ|ແກັດ/u', 'ເກັດ', $baseUnit);
        $n2 = preg_replace('/ເເກັດ|ເກັດ|ແກັດ|ແກັດ/u', 'ເກັດ', $tunitLower);
        if ($n1 === $n2 || mb_stripos($baseUnit, $tunitLower) !== false || mb_stripos($tunitLower, $baseUnit) !== false) {
            $isUnitMatch = true;
        }
    }

    $giftText = '';
    // Only show Free Gift text on the product card badge if the product's unit matches the target unit!
    if (!empty($giftName) && $isUnitMatch) {
        if ($tunit === 'all' || empty($tunit)) {
            $giftText = 'ແຖມ: ' . $giftName . ($giftQty > 1 ? ' x' . $giftQty : '');
        } else {
            $giftText = 'ແຖມ: ' . $giftName . ($giftQty > 1 ? ' x' . $giftQty : '') . ' (ສະເພາະ ' . $tunit . ')';
        }
    }

    if (!empty($discountText) && !empty($giftText)) {
        $badge = $discountText . ' + ' . $giftText;
    } elseif (!empty($discountText)) {
        $badge = $discountText;
    } elseif (!empty($giftText)) {
        $badge = $giftText;
    } else {
        $badge = $promoToUse['promo_name'];
    }

    return [
        'promo_name'        => $promoToUse['promo_name'],
        'promo_type'        => $promoType,
        'discount_type'     => $discType,
        'discount_value'    => $discVal,
        'discount_amount'   => $discountAmount,
        'original_price'    => $origPrice,
        'promo_price'       => $promoPrice,
        'badge'             => $badge,
        'min_qty'           => intval($promoToUse['min_qty'] ?? 0),
        'gift_product_name' => $giftName,
        'gift_qty'          => $giftQty,
        'target_unit_name'  => $tunit
    ];
}

// Fetch categories, products, and customers list
$activeStoreId = getActiveStoreId($pdo);
$categoriesStmt = $pdo->prepare("SELECT * FROM categories WHERE store_id = ? ORDER BY category_name ASC");
$categoriesStmt->execute([$activeStoreId]);
$categories = $categoriesStmt->fetchAll();
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
$products = [];
foreach ($productsRaw as $p) {
    $pid = $p['product_id'];
    $p['extra_units'] = $unitsMap[$pid] ?? [];
    
    $promo = getProductPromotion($p, $activePromos);
    if ($promo) {
        $p['has_promo']         = true;
        $p['original_price']    = floatval($p['price']);
        $p['promo_price']       = $promo['promo_price'];
        $p['promo_badge']       = $promo['badge'];
        $p['promo_name']        = $promo['promo_name'];
        $p['promo_type']        = $promo['promo_type'] ?? 'discount';
        $p['gift_product_name'] = $promo['gift_product_name'] ?? '';
        $p['gift_qty']          = $promo['gift_qty'] ?? 1;
        $p['target_unit_name']  = $promo['target_unit_name'] ?? 'all';
        $p['price']             = $promo['promo_price']; // Set active price to promotional price
        
        if (!empty($p['extra_units']) && $promo['discount_type'] === 'percentage') {
            foreach ($p['extra_units'] as &$eu) {
                $eu['original_price'] = floatval($eu['price']);
                $eu['price'] = round($eu['price'] * (1 - ($promo['discount_value'] / 100)));
            }
        }
    } else {
        $p['has_promo']       = false;
        $p['original_price']  = floatval($p['price']);
    }
    
    $products[] = $p;
}
