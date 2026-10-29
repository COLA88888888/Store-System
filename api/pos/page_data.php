<?php
/**
 * ສ່ວນ: ຂໍ້ມູນສຳລັບໜ້າຂາຍ
 * ໝວດໝູ່, ເຄື່ອງພິມບິນ ແລະ ເຄື່ອງພິມຄົວຕາມໝວດ, ສິນຄ້າ (ພ້ອມຫົວໜ່ວຍ ແລະ ໂປຣ), ລູກຄ້າ ແລະ
 * ຈຳນວນບິນມື້ນີ້.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ api/pos_backend.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

// Fetch categories, products, and customers list
$activeStoreId = getPosStoreId();
$categoriesStmt = $pdo->query("SELECT * FROM st_categories ORDER BY category_id ASC");
$categories = $categoriesStmt->fetchAll();

// The customer receipt's own printer, if THIS branch designated one (Settings > Printers).
// Each branch sets and prints through its own receipt printer. Read first: the kitchen routes
// below must never point back at it.
$posReceiptPrinter = null;
$posReceiptPrinterIp = '';
try {
    $rpStmt = $pdo->prepare("SELECT id, name, type, paper_width, ip_address FROM st_tbl_printer WHERE is_receipt_printer = 1 AND status = 1 AND (branch_id = ? OR branch_id = 0 OR branch_id IS NULL) ORDER BY (branch_id = ?) DESC, id DESC LIMIT 1");
    $rpStmt->execute([$activeStoreId, $activeStoreId]);
    $rpRow = $rpStmt->fetch();
    if ($rpRow) {
        $posReceiptPrinter = [
            'printer_id'   => (int)$rpRow['id'],
            'printer_name' => $rpRow['name'],
            'printer_type' => in_array($rpRow['type'] ?? '', ['ip', 'usb'], true) ? $rpRow['type'] : 'browser', // direct (LAN or USB)
            'paper_width'  => in_array(intval($rpRow['paper_width'] ?? 80), [58, 80, 148, 210], true) ? intval($rpRow['paper_width']) : 80,   // 148 = A5, 210 = A4
        ];
        $posReceiptPrinterIp = strtolower(trim((string)($rpRow['ip_address'] ?? '')));
    }
} catch (Throwable $e) {}

// Kitchen / category tickets: category_id => {printer_id, printer_name, printer_type, paper_width}.
// At checkout the main bill goes ONLY to the receipt printer above, and each category's items go
// as a separate ticket to the printer set on that category. A category pointed at the receipt
// printer itself (same printer, or another entry with the same IP) gets no ticket — otherwise the
// main printer prints the bill plus a duplicate slip. 'ip' tickets go over the LAN (through the
// print agent when print_mode = agent, like the receipt); 'browser' tickets open a print dialog.
// Switch all category tickets off with POS_PRINT_KITCHEN_TICKETS in config/db.php.
$posKitchenRoutes = [];
if (!defined('POS_PRINT_KITCHEN_TICKETS') || POS_PRINT_KITCHEN_TICKETS) try {
    $krStmt = $pdo->prepare("
        SELECT p.*, c.category_id, c.printer_id, p.name AS printer_name
        FROM st_categories c
        JOIN st_tbl_printer p ON p.id = c.printer_id AND p.status = 1 AND p.branch_id = ?
        WHERE c.printer_id IS NOT NULL
    ");
    $krStmt->execute([$activeStoreId]);
    foreach ($krStmt->fetchAll() as $kr) {
        $krType = in_array($kr['type'] ?? '', ['ip', 'usb'], true) ? 'ip' : 'browser'; // 'ip' = direct (LAN or USB)
        $krIp = strtolower(trim((string)($kr['ip_address'] ?? '')));
        if ($posReceiptPrinter) {
            if ((int)$kr['printer_id'] === $posReceiptPrinter['printer_id']) continue;
            if ($krType === 'ip' && $krIp !== '' && $krIp === $posReceiptPrinterIp) continue;
        }
        $posKitchenRoutes[(string)$kr['category_id']] = [
            'printer_id'   => (int)$kr['printer_id'],
            'printer_name' => $kr['printer_name'],
            'printer_type' => $krType,
            'paper_width'  => intval($kr['paper_width'] ?? 80) === 58 ? 58 : 80,
        ];
    }
} catch (Throwable $e) {}

$productsStmt = $pdo->prepare("
    SELECT p.*, c.category_name 
    FROM st_products p 
    LEFT JOIN st_categories c ON p.category_id = c.category_id 
    WHERE p.store_id = ?
    ORDER BY p.code1 DESC, p.product_name ASC
");
$productsStmt->execute([$activeStoreId]);
$productsRaw = $productsStmt->fetchAll();

$stmtPosCust = $pdo->prepare("SELECT customer_id, customer_code, customer_name, phone, address, member_card, notes, points, created_at, store_id, branch_no FROM st_customers WHERE store_id = ? OR store_id = 0 ORDER BY customer_id DESC");
$stmtPosCust->execute([$activeStoreId]);
$customersList = $stmtPosCust->fetchAll();

// Loyalty redemption catalogue for the "ແລກຄະແນນ" button. Product rewards are resolved in
// THIS branch (product_id repeats per store); one whose product doesn't exist here is dropped.
$posLoyalty = ['enabled' => getSetting($pdo, 'loyalty_enabled', '0') === '1', 'rewards' => []];
if ($posLoyalty['enabled']) {
    try {
        $lrStmt = $pdo->prepare("
            SELECT r.id, r.reward_name, r.points_cost, r.reward_type, r.discount_amount, r.min_spend, r.product_id, r.product_qty,
                   p.product_name, p.unit, p.price, p.bprice, p.img_url, p.category_id
            FROM st_loyalty_rewards r
            LEFT JOIN st_products p ON (r.reward_type = 'product' AND p.product_id = r.product_id AND p.store_id = ?)
            WHERE r.is_active = 1
            ORDER BY r.points_cost ASC, r.id ASC
        ");
        $lrStmt->execute([$activeStoreId]);
        foreach ($lrStmt->fetchAll(PDO::FETCH_ASSOC) as $lr) {
            if ($lr['reward_type'] === 'product' && $lr['product_name'] === null) continue;
            $posLoyalty['rewards'][] = [
                'id'              => (int)$lr['id'],
                'name'            => $lr['reward_name'],
                'points'          => (int)$lr['points_cost'],
                'type'            => $lr['reward_type'] === 'product' ? 'product' : 'discount',
                'discount_amount' => (float)$lr['discount_amount'],
                'min_spend'       => (float)$lr['min_spend'],
                'product_id'      => $lr['product_id'] !== null ? (int)$lr['product_id'] : null,
                'product_qty'     => max(1, (int)$lr['product_qty']),
                'product_name'    => $lr['product_name'],
                'unit'            => $lr['unit'],
                'price'           => (float)$lr['price'],
                'bprice'          => (float)$lr['bprice'],
                'image'           => $lr['img_url'],
                'category_id'     => $lr['category_id'],
            ];
        }
    } catch (Throwable $e) {}
}

// Fetch extra units
$extraUnits = $pdo->query("SELECT * FROM st_product_units ORDER BY multiplier ASC, id ASC")->fetchAll();
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
