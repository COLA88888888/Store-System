<?php
/**
 * ສ່ວນ: ຂໍ້ມູນ: ຕົວກັ່ນຕອງ ແລະ ຍອດຕາມປະເພດ
 * ສາຂາ/ສະກຸນເງິນ/ໝວດ/ຄົ້ນຫາ, ຍອດຕາມປະເພດ (ລວມໝວດຊື່ດຽວກັນຂ້າມສາຂາ), ສິນຄ້າທີ່ຂາຍໃນແຕ່ລະປະເພດ,
 * ຍອດລວມ ແລະ ແຍກຕາມວິທີຊຳລະ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/reports/category_sales.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

$search        = trim($_GET['search'] ?? '');
$filter_store_id = isset($_GET['store_id']) && $_GET['store_id'] !== '' ? intval($_GET['store_id']) : intval($_SESSION['store_id'] ?? 1);
if (!canViewAllBranches($pdo)) {
    $filter_store_id = intval($_SESSION['store_id'] ?? 1);
}
$page          = max(1, intval($_GET['page'] ?? 1));
$per_page_raw  = trim($_GET['per_page'] ?? '10');

// Fetch all active branches
$branchesList = [];
try {
    $branchesList = $pdo->query("SELECT * FROM st_tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Check store_id / paid_currency columns
$hasStoreIdCol  = false;
$hasCurrencyCol = false;
try {
    $saleCols = $pdo->query("SHOW COLUMNS FROM st_tbsale_save")->fetchAll(PDO::FETCH_COLUMN);
    $hasStoreIdCol  = in_array('store_id', $saleCols) || in_array('branch_id', $saleCols);
    $storeColName   = in_array('store_id', $saleCols) ? 'store_id' : 'branch_id';
    $hasCurrencyCol = in_array('paid_currency', $saleCols);
} catch (Exception $e) {}

// Currency Filter — which currency the customer actually tendered on the bill. Once
// applied, every money figure on the page switches to show in that currency instead
// of the store's default, via setDisplayCurrencyOverride(). Used both as a plain WHERE
// condition (items/grand/payment queries) and folded into the main query's LEFT JOIN
// ON-clause ($currencyJoinSql) so categories/products with no sales in that currency
// still show up (with zero), instead of disappearing as they would under a WHERE.
$currency_filter = strtoupper(trim($_GET['currency_filter'] ?? ''));
$currencyJoinSql = '';
if ($currency_filter !== '' && $hasCurrencyCol && isset(getCurrencyMeta()[$currency_filter])) {
    $currencyJoinSql = " AND COALESCE(NULLIF(s.paid_currency, ''), 'KIP') = :currency_filter";

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

if ($per_page_raw === 'all') {
    $per_page = 999999;
} else {
    $per_page = max(1, intval($per_page_raw));
}

// Fetch Categories for Dropdown — categories are duplicated per branch (same 10 names exist
// in every store), so collapse by name to one entry each. The value is a representative id.
$catStmt = $pdo->query("SELECT MIN(category_id) AS category_id, category_name FROM st_categories GROUP BY category_name ORDER BY category_name ASC");
$all_categories = $catStmt ? $catStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// The selected value is one representative id; resolve it to the shared name so the report
// matches that category across every branch, not just the one store's copy.
$category_name_filter = '';
if ($category_id !== '') {
    $cnStmt = $pdo->prepare("SELECT category_name FROM st_categories WHERE category_id = ? LIMIT 1");
    $cnStmt->execute([$category_id]);
    $category_name_filter = $cnStmt->fetchColumn() ?: '';
}
$hasCatFilter = ($category_id !== '' && $category_name_filter !== '');

$storeWhere = ($filter_store_id > 0 && $hasStoreIdCol) ? " AND s.{$storeColName} = :filter_store_id" : "";

// ກະ (ບັອກເລືອກກະ — partials/shift_filter_select.php): ສະເພາະສາຂາດຽວທີ່ມີກະ — ໃສ່ທັງ 3 query ຂ້າງລຸ່ມ
$shiftFilter = reportShiftFilter($pdo, $filter_store_id);
$shiftJoinSql = $shiftFilter['sql'] !== '' ? ' AND ' . $shiftFilter['sql'] : '';
$storeWhere .= $shiftJoinSql;

// Query ALL categories from database, joining sales data to get total quantity sold and revenue
$sql = "
    SELECT
        MIN(cat.category_id) AS category_id,
        cat.category_name,
        COALESCE(SUM(CASE WHEN s.sale_save_bill IS NOT NULL THEN d.save_qty ELSE 0 END), 0) AS total_qty_sold,
        COALESCE(SUM(CASE WHEN s.sale_save_bill IS NOT NULL THEN d.save_qty * d.multiplier ELSE 0 END), 0) AS total_stock_cut_qty,
        COALESCE(SUM(CASE WHEN s.sale_save_bill IS NOT NULL THEN d.save_money ELSE 0 END), 0) AS total_category_revenue,
        COALESCE(SUM(CASE WHEN s.sale_save_bill IS NOT NULL THEN
            COALESCE(d.save_discount_item, 0) + (CASE WHEN s.sale_amount > 0 THEN (d.save_money / s.sale_amount) * COALESCE(s.sale_discount_bill, 0) ELSE 0 END)
        ELSE 0 END), 0) AS total_category_discount
    FROM st_categories cat
    LEFT JOIN st_products p ON cat.category_id = p.category_id
    LEFT JOIN st_tbsale_save_detail d ON p.product_id = d.save_proid
    LEFT JOIN st_tbsale_save s ON d.save_bill = s.sale_save_bill
        AND (s.sale_status IS NULL OR s.sale_status != 'CANCEL')
        AND s.store_id = p.store_id
        {$storeWhere}
        " . (!empty($from_date) ? " AND s.sale_date >= :from_date" : "") . "
        " . (!empty($to_date) ? " AND s.sale_date <= :to_date" : "") . "
        {$currencyJoinSql}
    " . ($hasCatFilter ? "WHERE cat.category_name = :category_name_filter" : "") . "
    " . (!empty($search) ? ($hasCatFilter ? "AND" : "WHERE") . " (cat.category_name LIKE :search OR d.save_proname LIKE :search OR p.product_name LIKE :search)" : "") . "
    GROUP BY cat.category_name
    ORDER BY cat.category_name ASC
";

$params = [];
if ($filter_store_id > 0 && $hasStoreIdCol) {
    $params[':filter_store_id'] = $filter_store_id;
}
if (!empty($from_date)) {
    $params[':from_date'] = $from_date;
}
if (!empty($to_date)) {
    $params[':to_date'] = $to_date;
}
if ($hasCatFilter) {
    $params[':category_name_filter'] = $category_name_filter;
}
if (!empty($search)) {
    $params[':search'] = '%' . $search . '%';
}
if ($currency_filter !== '') {
    $params[':currency_filter'] = $currency_filter;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all sold products per category
$items_sql = "
    SELECT
        COALESCE(cat.category_name, '') AS category_name,
        COALESCE(d.save_proname, p.product_name, 'ບໍ່ລະບຸຊື່ສິນຄ້າ') AS product_name,
        SUM(d.save_qty) AS qty_sold,
        SUM(d.save_qty * d.multiplier) AS stock_cut_qty,
        MAX(d.multiplier) AS unit_multiplier,
        MAX(p.unit) AS base_unit,
        SUM(d.save_money) AS item_revenue,
        SUM(
            COALESCE(d.save_discount_item, 0) +
            (CASE WHEN s.sale_amount > 0 THEN (d.save_money / s.sale_amount) * COALESCE(s.sale_discount_bill, 0) ELSE 0 END)
        ) AS item_discount
    FROM st_tbsale_save_detail d
    INNER JOIN st_tbsale_save s ON d.save_bill = s.sale_save_bill
    LEFT JOIN st_products p ON (d.save_proid = p.product_id AND p.store_id = s.store_id)
    LEFT JOIN st_categories cat ON p.category_id = cat.category_id
    WHERE (s.sale_status IS NULL OR s.sale_status != 'CANCEL')
        " . (!empty($from_date) ? " AND s.sale_date >= :from_date" : "") . "
        " . (!empty($to_date) ? " AND s.sale_date <= :to_date" : "") . "
        " . (($filter_store_id > 0 && $hasStoreIdCol) ? " AND s.{$storeColName} = :items_filter_store_id" : "") . "
        " . (!empty($search) ? " AND (d.save_proname LIKE :search OR d.save_proid LIKE :search OR p.product_name LIKE :search)" : "") . ("
        {$currencyJoinSql}
        {$shiftJoinSql}
    GROUP BY COALESCE(cat.category_name, ''), BINARY COALESCE(d.save_proname, p.product_name, '" . tl('ບໍ່ລະບຸຊື່ສິນຄ້າ') . "')
    ORDER BY qty_sold DESC
");
$items_stmt = $pdo->prepare($items_sql);
$item_params = [];
if (!empty($from_date)) $item_params[':from_date'] = $from_date;
if (!empty($to_date)) $item_params[':to_date'] = $to_date;
if ($filter_store_id > 0 && $hasStoreIdCol) $item_params[':items_filter_store_id'] = $filter_store_id;
if (!empty($search)) $item_params[':search'] = '%' . $search . '%';
if ($currency_filter !== '') $item_params[':currency_filter'] = $currency_filter;
$items_stmt->execute($item_params);
$raw_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

$category_products = [];
$category_products_index = []; // [cid][cleaned_display_name] => index into $category_products[cid], for merging duplicates
foreach ($raw_items as $item) {
    $cid = $item['category_name'];

    // Extract the unit name sold, e.g. "ຢາສູບ AA (ຕູດ)" -> "ຕູດ", to explain the stock-cut multiplier
    $item['unit_label'] = '';
    if (preg_match('/\(([^)]+)\)\s*$/u', $item['product_name'] ?? '', $m)) {
        $item['unit_label'] = trim($m[1]);
    }

    // A unit typed as "1ຕຸດ" duplicates the "(1 X = Y Z)" note shown below it — strip the
    // redundant leading "1" so it reads as "ຕຸດ". This also doubles as the merge key below:
    // a sale recorded before the unit name was corrected in Products ("1ຕຸດ") and one recorded
    // after ("ຕຸດ") are the same real unit and must be combined, not shown as two rows.
    if ($item['unit_label'] !== '' && preg_match('/^1(\D.+)$/u', $item['unit_label'], $mUnit)) {
        $cleanLabel = trim($mUnit[1]);
        if ($cleanLabel !== '') {
            $item['product_name'] = preg_replace('/\(' . preg_quote($item['unit_label'], '/') . '\)\s*$/u', '(' . $cleanLabel . ')', $item['product_name']);
            $item['unit_label'] = $cleanLabel;
        }
    }

    if (!isset($category_products[$cid])) {
        $category_products[$cid] = [];
        $category_products_index[$cid] = [];
    }

    $mergeKey = $item['product_name'];
    if (isset($category_products_index[$cid][$mergeKey])) {
        $idx = $category_products_index[$cid][$mergeKey];
        $category_products[$cid][$idx]['qty_sold']       += $item['qty_sold'];
        $category_products[$cid][$idx]['stock_cut_qty']  += $item['stock_cut_qty'];
        $category_products[$cid][$idx]['item_revenue']   += $item['item_revenue'];
        $category_products[$cid][$idx]['item_discount']  += $item['item_discount'];
    } else {
        $category_products_index[$cid][$mergeKey] = count($category_products[$cid]);
        $category_products[$cid][] = $item;
    }
}

// Re-sort each category's merged products by quantity sold, highest first (merging can change order)
foreach ($category_products as $cid => $prodList) {
    usort($category_products[$cid], function($a, $b) {
        return $b['qty_sold'] <=> $a['qty_sold'];
    });
}

// Grand Total row (ລວມທັງໝົດ) — computed server-side here, scoped exactly like the table
// above (date/store/category/search). This used to be summed in JS from only the category
// rows visible on the current page, which was wrong once results spanned more than one page,
// and separately the categories->products->details join direction could double- or under-count
// rows depending on how products/categories are shared across branches. Querying flat from
// tbsale_save_detail avoids both problems.
$grand_total_qty = 0;
$grand_total_stock_cut = 0;
$grand_total_revenue = 0;
$grand_total_discount = 0;
try {
    $grandWhere = ["(s.sale_status IS NULL OR s.sale_status != 'CANCEL')"];
    $grandParams = [];
    if (!empty($from_date)) {
        $grandWhere[] = "s.sale_date >= :grand_from_date";
        $grandParams[':grand_from_date'] = $from_date;
    }
    if (!empty($to_date)) {
        $grandWhere[] = "s.sale_date <= :grand_to_date";
        $grandParams[':grand_to_date'] = $to_date;
    }
    if ($filter_store_id > 0 && $hasStoreIdCol) {
        $grandWhere[] = "s.{$storeColName} = :grand_store_id";
        $grandParams[':grand_store_id'] = $filter_store_id;
    }
    if ($shiftFilter['sql'] !== '') {
        $grandWhere[] = $shiftFilter['sql'];
    }
    if ($hasCatFilter) {
        $grandWhere[] = "p.category_id IN (SELECT category_id FROM st_categories WHERE category_name = :grand_category_name)";
        $grandParams[':grand_category_name'] = $category_name_filter;
    }
    if (!empty($search)) {
        $grandWhere[] = "(d.save_proname LIKE :grand_search OR d.save_proid LIKE :grand_search OR p.product_name LIKE :grand_search)";
        $grandParams[':grand_search'] = '%' . $search . '%';
    }
    if ($currency_filter !== '') {
        $grandWhere[] = "COALESCE(NULLIF(s.paid_currency, ''), 'KIP') = :currency_filter";
        $grandParams[':currency_filter'] = $currency_filter;
    }
    $grandWhereClause = implode(' AND ', $grandWhere);

    $grandStmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(d.save_qty), 0) AS total_qty,
            COALESCE(SUM(d.save_qty * d.multiplier), 0) AS total_stock_cut,
            COALESCE(SUM(d.save_money), 0) AS total_revenue,
            COALESCE(SUM(
                COALESCE(d.save_discount_item, 0) +
                (CASE WHEN s.sale_amount > 0 THEN (d.save_money / s.sale_amount) * COALESCE(s.sale_discount_bill, 0) ELSE 0 END)
            ), 0) AS total_discount
        FROM st_tbsale_save_detail d
        INNER JOIN st_tbsale_save s ON d.save_bill = s.sale_save_bill
        LEFT JOIN st_products p ON (d.save_proid = p.product_id AND p.store_id = s.store_id)
        WHERE {$grandWhereClause}
    ");
    $grandStmt->execute($grandParams);
    $grandRow = $grandStmt->fetch(PDO::FETCH_ASSOC);
    $grand_total_qty       = intval($grandRow['total_qty'] ?? 0);
    $grand_total_stock_cut = intval($grandRow['total_stock_cut'] ?? 0);
    $grand_total_revenue   = floatval($grandRow['total_revenue'] ?? 0);
    $grand_total_discount  = floatval($grandRow['total_discount'] ?? 0);
} catch (Exception $e) {}

// Payment Method Breakdown (Cash / Transfer / Total) for the bottom summary —
// scoped by date, branch, employee only (a bill's payment isn't tied to one category/product filter)
$payWhere  = ["(s.sale_status IS NULL OR s.sale_status != 'CANCEL')"];
$payParams = [];
if (!empty($from_date)) {
    $payWhere[] = "s.sale_date >= :pay_from_date";
    $payParams[':pay_from_date'] = $from_date;
}
if (!empty($to_date)) {
    $payWhere[] = "s.sale_date <= :pay_to_date";
    $payParams[':pay_to_date'] = $to_date;
}
if ($filter_store_id > 0 && $hasStoreIdCol) {
    $payWhere[] = "s.{$storeColName} = :pay_store_id";
    $payParams[':pay_store_id'] = $filter_store_id;
}
if ($currency_filter !== '') {
    $payWhere[] = "COALESCE(NULLIF(s.paid_currency, ''), 'KIP') = :currency_filter";
    $payParams[':currency_filter'] = $currency_filter;
}
$payWhereClause = implode(' AND ', $payWhere);

// Each bill's cash/transfer split must add up to exactly its own sale_barlance (net total) —
// a bill paid partly cash + partly transfer ("ເງິນສົດ + ໂອນ") must not have its full amount
// counted in both buckets, or cash_total + transfer_total would exceed grand_total_paid.
$cash_total = 0;
$transfer_total = 0;
$grand_total_paid = 0;
try {
    $payStmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(CASE
                WHEN s.type_pay LIKE '%ເງິນສົດ%' AND (s.type_pay LIKE '%ໂອນ%' OR s.type_pay LIKE '%QR%')
                    THEN GREATEST(0, LEAST(s.sale_barlance, s.sale_pay - s.sale_return))
                WHEN s.type_pay LIKE '%ເງິນສົດ%' THEN s.sale_barlance
                WHEN (s.type_pay NOT LIKE '%ໂອນ%' AND s.type_pay NOT LIKE '%QR%' AND (s.bank_account_id IS NULL OR s.bank_account_id = 0)) THEN s.sale_barlance
                ELSE 0
            END), 0) AS cash_total,
            COALESCE(SUM(CASE
                WHEN s.type_pay LIKE '%ເງິນສົດ%' AND (s.type_pay LIKE '%ໂອນ%' OR s.type_pay LIKE '%QR%')
                    THEN GREATEST(0, s.sale_barlance - GREATEST(0, LEAST(s.sale_barlance, s.sale_pay - s.sale_return)))
                WHEN (s.type_pay LIKE '%ໂອນ%' OR s.type_pay LIKE '%QR%' OR s.bank_account_id > 0) THEN s.sale_barlance
                ELSE 0
            END), 0) AS transfer_total,
            COALESCE(SUM(s.sale_barlance), 0) AS grand_total_paid
        FROM st_tbsale_save s
        WHERE {$payWhereClause}
    ");
    $payStmt->execute($payParams);
    $payRow = $payStmt->fetch(PDO::FETCH_ASSOC);
    $cash_total       = floatval($payRow['cash_total'] ?? 0);
    $transfer_total   = floatval($payRow['transfer_total'] ?? 0);
    $grand_total_paid = floatval($payRow['grand_total_paid'] ?? 0);
} catch (Exception $e) {}

$total_records = count($categories);
$total_pages   = max(1, ceil($total_records / $per_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $per_page;

$display_data = array_slice($categories, $offset, $per_page);

