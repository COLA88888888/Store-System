<?php
// Standalone Page: ລາຍງານຕາມປະເພດສິນຄ້າ (Category Sales Report)
session_start();

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

require_once dirname(__DIR__, 2) . '/config/db.php';

if (!hasPermission('category_sales') && !hasPermission('report') && ($_SESSION['status'] ?? '') !== 'ຜູ້ບໍລິຫານ') {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$from_date     = trim($_GET['from_date'] ?? date('Y-m-01'));
$to_date       = trim($_GET['to_date'] ?? date('Y-m-d'));
$category_id   = trim($_GET['category_id'] ?? '');
$search        = trim($_GET['search'] ?? '');
$filter_store_id = isset($_GET['store_id']) && $_GET['store_id'] !== '' ? intval($_GET['store_id']) : 0;
$page          = max(1, intval($_GET['page'] ?? 1));
$per_page_raw  = trim($_GET['per_page'] ?? '10');

// Fetch all active branches
$branchesList = [];
try {
    $branchesList = $pdo->query("SELECT * FROM tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Check store_id column
$hasStoreIdCol = false;
try {
    $saleCols = $pdo->query("SHOW COLUMNS FROM tbsale_save")->fetchAll(PDO::FETCH_COLUMN);
    $hasStoreIdCol = in_array('store_id', $saleCols) || in_array('branch_id', $saleCols);
    $storeColName = in_array('store_id', $saleCols) ? 'store_id' : 'branch_id';
} catch (Exception $e) {}

if ($per_page_raw === 'all') {
    $per_page = 999999;
} else {
    $per_page = max(1, intval($per_page_raw));
}

// Fetch Categories for Dropdown — categories are duplicated per branch (same 10 names exist
// in every store), so collapse by name to one entry each. The value is a representative id.
$catStmt = $pdo->query("SELECT MIN(category_id) AS category_id, category_name FROM categories GROUP BY category_name ORDER BY category_name ASC");
$all_categories = $catStmt ? $catStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// The selected value is one representative id; resolve it to the shared name so the report
// matches that category across every branch, not just the one store's copy.
$category_name_filter = '';
if ($category_id !== '') {
    $cnStmt = $pdo->prepare("SELECT category_name FROM categories WHERE category_id = ? LIMIT 1");
    $cnStmt->execute([$category_id]);
    $category_name_filter = $cnStmt->fetchColumn() ?: '';
}
$hasCatFilter = ($category_id !== '' && $category_name_filter !== '');

$storeWhere = ($filter_store_id > 0 && $hasStoreIdCol) ? " AND s.{$storeColName} = :filter_store_id" : "";

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
    FROM categories cat
    LEFT JOIN products p ON cat.category_id = p.category_id
    LEFT JOIN tbsale_save_detail d ON p.product_id = d.save_proid
    LEFT JOIN tbsale_save s ON d.save_bill = s.sale_save_bill
        AND (s.sale_status IS NULL OR s.sale_status != 'CANCEL')
        AND s.store_id = p.store_id
        {$storeWhere}
        " . (!empty($from_date) ? " AND s.sale_date >= :from_date" : "") . "
        " . (!empty($to_date) ? " AND s.sale_date <= :to_date" : "") . "
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
    FROM tbsale_save_detail d
    INNER JOIN tbsale_save s ON d.save_bill = s.sale_save_bill
    LEFT JOIN products p ON (d.save_proid = p.product_id AND p.store_id = s.store_id)
    LEFT JOIN categories cat ON p.category_id = cat.category_id
    WHERE (s.sale_status IS NULL OR s.sale_status != 'CANCEL')
        " . (!empty($from_date) ? " AND s.sale_date >= :from_date" : "") . "
        " . (!empty($to_date) ? " AND s.sale_date <= :to_date" : "") . "
        " . (($filter_store_id > 0 && $hasStoreIdCol) ? " AND s.{$storeColName} = :items_filter_store_id" : "") . "
        " . (!empty($search) ? " AND (d.save_proname LIKE :search OR d.save_proid LIKE :search OR p.product_name LIKE :search)" : "") . "
    GROUP BY COALESCE(cat.category_name, ''), BINARY COALESCE(d.save_proname, p.product_name, 'ບໍ່ລະບຸຊື່ສິນຄ້າ')
    ORDER BY qty_sold DESC
";
$items_stmt = $pdo->prepare($items_sql);
$item_params = [];
if (!empty($from_date)) $item_params[':from_date'] = $from_date;
if (!empty($to_date)) $item_params[':to_date'] = $to_date;
if ($filter_store_id > 0 && $hasStoreIdCol) $item_params[':items_filter_store_id'] = $filter_store_id;
if (!empty($search)) $item_params[':search'] = '%' . $search . '%';
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
    if ($hasCatFilter) {
        $grandWhere[] = "p.category_id IN (SELECT category_id FROM categories WHERE category_name = :grand_category_name)";
        $grandParams[':grand_category_name'] = $category_name_filter;
    }
    if (!empty($search)) {
        $grandWhere[] = "(d.save_proname LIKE :grand_search OR d.save_proid LIKE :grand_search OR p.product_name LIKE :grand_search)";
        $grandParams[':grand_search'] = '%' . $search . '%';
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
        FROM tbsale_save_detail d
        INNER JOIN tbsale_save s ON d.save_bill = s.sale_save_bill
        LEFT JOIN products p ON (d.save_proid = p.product_id AND p.store_id = s.store_id)
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
        FROM tbsale_save s
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

require_once __DIR__ . '/../../layouts/header.php';
?>

<link rel="stylesheet" href="../../themes/reports.css?v=<?php echo filemtime(__DIR__ . '/../../themes/reports.css'); ?>">
<!-- category_sales build: dedup-by-name-2026-08-30 | categories in dropdown: <?php echo count($all_categories); ?> -->

<div class="container-fluid p-3 p-md-4">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="row-gap: 10px;">
    <div>
      <h5 class="font-weight-bold text-dark mb-1" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
        <i class="fas fa-layer-group text-primary mr-2"></i> <?php echo htmlspecialchars(t('reports.title_category_full', 'ລາຍງານຕາມປະເພດສິນຄ້າ ແລະ ລາຍການສິນຄ້າທີ່ຂາຍ')); ?>
      </h5>
    </div>

    <!-- Export & Print Buttons (Excel, PDF, Print) -->
    <div class="d-flex align-items-center justify-content-end ml-auto no-print" style="gap: 5px;">
      <button type="button" onclick="exportReportExcel('all')" class="btn btn-xs btn-success font-weight-bold px-2 d-inline-flex align-items-center shadow-sm" style="border-radius: 6px; height: 30px; font-size: 0.78rem; background: linear-gradient(135deg, #16a34a, #15803d); border: none;">
        <i class="fas fa-file-excel mr-1" style="font-size: 0.8rem;"></i> Excel
      </button>
      <button type="button" onclick="exportReportPDF('all')" class="btn btn-xs btn-danger font-weight-bold px-2 d-inline-flex align-items-center shadow-sm" style="border-radius: 6px; height: 30px; font-size: 0.78rem; background: linear-gradient(135deg, #dc2626, #b91c1c); border: none;">
        <i class="fas fa-file-pdf mr-1" style="font-size: 0.8rem;"></i> PDF
      </button>
      <button type="button" onclick="printReportTable('all')" class="btn btn-xs btn-primary font-weight-bold px-2 d-inline-flex align-items-center shadow-sm" style="border-radius: 6px; height: 30px; font-size: 0.78rem; background: linear-gradient(135deg, #2563eb, #1d4ed8); border: none;">
        <i class="fas fa-print mr-1" style="font-size: 0.8rem;"></i> <?php echo htmlspecialchars(t('reports.print_btn', 'ພິມ')); ?>
      </button>
    </div>
  </div>

  <!-- Search & Filter Bar -->
  <div class="report-filter-box no-print mb-3.5" style="padding: 12px 16px;">
    <form method="GET" action="category_sales.php" class="d-flex flex-column flex-md-row align-items-md-end flex-wrap" style="gap: 12px;">
      <!-- Per Page Dropdown (ໂຊລາຍການ - ໜ້າສຸດ) -->
      <div style="flex: 1 1 120px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-layer-group text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.per_page_label', 'ໂຊລາຍການ:')); ?>
        </label>
        <select name="per_page" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()" style="border-radius: 8px; height: 38px; font-size: 0.85rem; background: #ffffff; border: 1.5px solid #cbd5e1; width: 100%;">
          <option value="5" <?php echo $per_page_raw === '5' ? 'selected' : ''; ?>>5</option>
          <option value="10" <?php echo $per_page_raw === '10' ? 'selected' : ''; ?>>10</option>
          <option value="25" <?php echo $per_page_raw === '25' ? 'selected' : ''; ?>>25</option>
          <option value="50" <?php echo $per_page_raw === '50' ? 'selected' : ''; ?>>50</option>
          <option value="100" <?php echo $per_page_raw === '100' ? 'selected' : ''; ?>>100</option>
          <option value="all" <?php echo $per_page_raw === 'all' ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('reports.all_opt', 'ທັງໝົດ')); ?></option>
        </select>
      </div>

      <!-- From Date (ຕັ້ງແຕ່ວັນທີ) -->
      <div style="flex: 1 1 130px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-calendar-alt text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.from_date', 'ຕັ້ງແຕ່ວັນທີ:')); ?>
        </label>
        <input type="date" name="from_date" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($from_date); ?>" style="border-radius: 8px; height: 38px; width: 100%;">
      </div>

      <!-- To Date (ຫາວັນທີ) -->
      <div style="flex: 1 1 130px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-calendar-check text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.to_date', 'ຫາວັນທີ:')); ?>
        </label>
        <input type="date" name="to_date" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($to_date); ?>" style="border-radius: 8px; height: 38px; width: 100%;">
      </div>

      <!-- Category Filter Dropdown (ເລືອກປະເພດສິນຄ້າ) -->
      <div style="flex: 1 1 150px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-tags text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.category_label', 'ປະເພດສິນຄ້າ:')); ?>
        </label>
        <select name="category_id" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()" style="border-radius: 8px; height: 38px; font-size: 0.85rem; background: #ffffff; border: 1.5px solid #cbd5e1; width: 100%;">
          <option value=""><?php echo htmlspecialchars(t('reports.select_category_opt', '-- ເລືອກປະເພດສິນຄ້າ --')); ?></option>
          <?php foreach ($all_categories as $c): ?>
            <option value="<?php echo $c['category_id']; ?>" <?php echo $category_id == $c['category_id'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($c['category_name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Branch Filter Dropdown (ເລືອກສາຂາ) -->
      <div style="flex: 1 1 140px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-store text-info mr-1"></i> <?php echo htmlspecialchars(t('reports.branch_label', 'ສາຂາ:')); ?>
        </label>
        <select name="store_id" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #cbd5e1; width: 100%;" onchange="this.form.submit()">
          <option value="0"><?php echo htmlspecialchars(t('reports.all_branches_opt', '-- ທຸກສາຂາ --')); ?></option>
          <?php if (!empty($branchesList)): ?>
            <?php foreach ($branchesList as $b): ?>
              <option value="<?php echo $b['store_id']; ?>" <?php echo ($filter_store_id == $b['store_id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($b['store_name']); ?> <?php echo !empty($b['is_main']) ? htmlspecialchars(t('reports.main_branch_suffix', '(ສາຂາໃຫຍ່)')) : ''; ?>
              </option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
      </div>

      <!-- Action Buttons (ຄົ້ນຫາ & ຣີໂຫລດ) -->
      <div style="flex: 1 1 130px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block d-md-none" style="font-size: 0.82rem; visibility: hidden;">&nbsp;</label>
        <div class="d-flex align-items-center" style="gap: 8px; width: 100%;">
          <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-3 d-inline-flex align-items-center justify-content-center" style="border-radius: 8px; height: 38px; background: linear-gradient(135deg, #2c5aa0, #244886); flex: 1;">
            <i class="fas fa-search mr-1.5"></i> <?php echo htmlspecialchars(t('reports.search_btn', 'ຄົ້ນຫາ')); ?>
          </button>
          <a href="category_sales.php" class="btn btn-light btn-sm border font-weight-bold px-3 d-inline-flex align-items-center justify-content-center" style="border-radius: 8px; height: 38px;" title="<?php echo htmlspecialchars(t('reports.clear', 'ລ້າງຄ່າ')); ?>">
            <i class="fas fa-redo"></i>
          </a>
        </div>
      </div>
    </form>
  </div>

  <!-- Category Table Card -->
  <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
    <div class="card-body p-0">
      <div class="table-responsive" style="overflow: visible;">
        <table class="table table-hover align-middle mb-0 text-nowrap report-table" style="font-size: 0.88rem;">
          <thead style="background-color: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;">
            <tr style="background: #ffffff; color: #1e293b;">
              <th class="text-center py-3" style="width: 60px; border-bottom: 2px solid #cbd5e1;"><?php echo htmlspecialchars(t('reports.col_no', 'ລຳດັບ')); ?></th>
              <th class="py-3" style="border-bottom: 2px solid #cbd5e1;"><?php echo htmlspecialchars(t('reports.col_category_product', 'ປະເພດສິນຄ້າ / ລາຍການສິນຄ້າ')); ?></th>
              <th class="text-center py-3" style="width: 120px; border-bottom: 2px solid #cbd5e1;"><?php echo htmlspecialchars(t('reports.col_qty', 'ຈຳນວນ')); ?></th>
              <th class="text-center py-3" style="width: 130px; border-bottom: 2px solid #cbd5e1;"><?php echo htmlspecialchars(t('reports.discount_label', 'ສ່ວນຫຼຸດ')); ?></th>
              <th class="text-center py-3" style="width: 150px; border-bottom: 2px solid #cbd5e1;"><?php echo htmlspecialchars(t('reports.col_stock_cut_actual', 'ຕັດສະຕັອກຕົວຈິງ')); ?></th>
              <th class="text-right py-3" style="width: 160px; border-bottom: 2px solid #cbd5e1;"><?php echo htmlspecialchars(t('reports.col_total_money', 'ລວມເງິນ')); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($display_data)): ?>
              <tr>
                <td colspan="6" class="text-center py-5 text-muted font-weight-bold">
                  <i class="fas fa-layer-group fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                  <?php echo htmlspecialchars(t('reports.no_category_data', 'ບໍ່ພົບຂໍ້ມູນປະເພດສິນຄ້າ')); ?>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($display_data as $idx => $row): ?>
                <?php
                  $cid = $row['category_name'];
                  $prods = $category_products[$cid] ?? [];
                ?>
                <!-- Category Header Row -->
                <tr class="cat-header-row" style="background-color: #f1f5f9; border-top: 2px solid #cbd5e1; font-weight: bold;">
                  <td class="text-center align-middle text-dark" style="font-size: 0.95rem; font-weight: 700; background-color: #f1f5f9;">
                    <?php echo $offset + $idx + 1; ?>
                  </td>
                  <td class="align-middle text-dark" style="font-size: 0.96rem; font-weight: 700; background-color: #f1f5f9;">
                    <?php echo htmlspecialchars($row['category_name']); ?>
                  </td>
                  <td class="text-center align-middle text-dark" style="font-size: 0.95rem; font-weight: 700; background-color: #f1f5f9;">
                    <?php echo number_format($row['total_qty_sold']); ?>
                  </td>
                  <td class="text-center align-middle" style="background-color: #f1f5f9;">
                    <?php if (!empty($row['total_category_discount']) && floatval($row['total_category_discount']) > 0): ?>
                      <span class="font-weight-bold text-danger" style="font-size: 0.9rem;">
                        -<?php echo displayMoney($pdo, $row['total_category_discount']); ?>
                      </span>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center align-middle text-dark" style="font-size: 0.95rem; font-weight: 700; background-color: #f1f5f9;">
                    <?php echo number_format($row['total_stock_cut_qty']); ?>
                  </td>
                  <td class="text-right align-middle text-dark" style="font-size: 0.96rem; font-weight: 700; background-color: #f1f5f9;">
                    <?php echo displayMoney($pdo, max(0, floatval($row['total_category_revenue']) - floatval($row['total_category_discount'])), false); ?>
                  </td>
                </tr>

                <!-- Product Detail Rows -->
                <?php if (!empty($prods)): ?>
                  <?php foreach ($prods as $pIdx => $p): ?>
                    <?php
                      // $p['product_name'] and $p['unit_label'] were already normalized (redundant
                      // leading "1" stripped) and merged with same-named duplicates during aggregation above.
                      $unitMultiplier = intval($p['unit_multiplier'] ?? 1);
                      $baseUnit       = trim($p['base_unit'] ?? '');
                      $unitLabel      = trim($p['unit_label'] ?? '');
                      $displayName    = $p['product_name'] ?? '';
                      $showUnitDetail = ($unitMultiplier > 1 && $unitLabel !== '' && $baseUnit !== '' && $unitLabel !== $baseUnit);
                    ?>
                    <tr style="background-color: #ffffff;">
                      <td class="text-center align-middle text-muted" style="font-size: 0.88rem; padding-left: 20px;">
                        <?php echo $pIdx + 1; ?>
                      </td>
                      <td class="align-middle text-secondary" style="font-size: 0.9rem; padding-left: 28px;">
                        <?php echo htmlspecialchars($displayName); ?>
                        <?php if ($showUnitDetail): ?>
                          <div class="text-muted" style="font-size: 0.76rem; font-weight: 600;">
                            (1 <?php echo htmlspecialchars($unitLabel); ?> = <?php echo number_format($unitMultiplier); ?> <?php echo htmlspecialchars($baseUnit); ?>)
                          </div>
                        <?php endif; ?>
                      </td>
                      <td class="text-center align-middle text-secondary" style="font-size: 0.9rem;">
                        <?php echo number_format($p['qty_sold']); ?>
                      </td>
                      <td class="text-center align-middle">
                        <?php if (!empty($p['item_discount']) && floatval($p['item_discount']) > 0): ?>
                          <span class="font-weight-bold text-danger" style="font-size: 0.86rem;">
                            -<?php echo displayMoney($pdo, $p['item_discount']); ?>
                          </span>
                        <?php else: ?>
                          <span class="text-muted">-</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-center align-middle text-secondary" style="font-size: 0.9rem;">
                        <?php echo number_format($p['stock_cut_qty']); ?>
                      </td>
                      <td class="text-right align-middle text-dark" style="font-size: 0.9rem;">
                        <?php echo displayMoney($pdo, max(0, floatval($p['item_revenue']) - floatval($p['item_discount'])), false); ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr style="background-color: #ffffff;">
                    <td></td>
                    <td colspan="6" class="text-muted italic py-2" style="font-size: 0.85rem; padding-left: 28px;">
                      <?php echo htmlspecialchars(t('reports.no_category_sales_history', '(ບໍ່ມີປະຫວັດການຂາຍ)')); ?>
                    </td>
                  </tr>
                <?php endif; ?>

              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
          <?php if (!empty($display_data)): ?>
            <tfoot style="background: #f8fafc; border-top: 2px solid #cbd5e1;">
              <tr class="font-weight-bold" style="font-size: 0.9rem; color: #0f172a;">
                <td colspan="2" class="text-center py-3 font-weight-bold" style="background: #f8fafc; color: #0f172a;"><?php echo htmlspecialchars(t('reports.total_all_label', 'ລວມທັງໝົດ:')); ?></td>
                <td class="text-center py-3 text-dark font-weight-bold" style="background: #f8fafc;"><?php echo number_format($grand_total_qty); ?></td>
                <td class="text-center py-3 font-weight-bold" style="background: #f8fafc;">
                  <?php if ($grand_total_discount > 0): ?>
                    <span class="font-weight-bold text-danger" style="font-size: 0.94rem;">-<?php echo displayMoney($pdo, $grand_total_discount); ?></span>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td class="text-center py-3 text-dark font-weight-bold" style="background: #f8fafc;"><?php echo number_format($grand_total_stock_cut); ?></td>
                <td class="text-right py-3 text-primary font-weight-bold" style="background: #f8fafc; font-size: 0.96rem;"><?php echo displayMoney($pdo, max(0, $grand_total_revenue - $grand_total_discount)); ?></td>
              </tr>
              <tr style="font-size: 0.88rem; color: #16a34a;">
                <td colspan="5" class="text-right py-2 font-weight-bold" style="background: #f8fafc; color: #16a34a;">
                   <?php echo htmlspecialchars(t('reports.total_cash_label', 'ເງິນສົດ:')); ?>
                </td>
                <td class="text-right py-2 font-weight-bold" style="background: #f8fafc; color: #16a34a;">
                  <?php echo displayMoney($pdo, $cash_total); ?>
                </td>
              </tr>
              <tr style="font-size: 0.88rem; color: #2563eb;">
                <td colspan="5" class="text-right py-2 font-weight-bold" style="background: #f8fafc; color: #2563eb;">
                   <?php echo htmlspecialchars(t('reports.total_transfer_label', 'ເງິນໂອນ:')); ?>
                </td>
                <td class="text-right py-2 font-weight-bold" style="background: #f8fafc; color: #2563eb;">
                  <?php echo displayMoney($pdo, $transfer_total); ?>
                </td>
              </tr>
              <tr style="font-size: 0.98rem; border-top: 2px solid #cbd5e1;">
                <td colspan="5" class="text-right py-2.5 font-weight-bold" style="background: #f1f5f9; color: #0f172a;">
                   <?php echo htmlspecialchars(t('reports.total_label', 'ລວມ:')); ?>
                </td>
                <td class="text-right py-2.5 font-weight-bold" style="background: #f1f5f9; color: #0f172a; font-size: 1.02rem;">
                  <?php echo displayMoney($pdo, $grand_total_paid); ?>
                </td>
              </tr>
            </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </div>

    <!-- Table Card Footer: Circular Blue Pagination Aligned Right -->
    <div class="card-footer bg-white border-top py-3 px-3.5 d-flex flex-column flex-md-row justify-content-between align-items-center no-print" style="row-gap: 12px; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">

      <nav aria-label="Page navigation" class="ml-auto">
        <ul class="pagination report-pagination mb-0">
          <!-- Previous Page -->
          <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="category_sales.php?page=<?php echo max(1, $page - 1); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&category_id=<?php echo urlencode($category_id); ?>&search=<?php echo urlencode($search); ?>&per_page=<?php echo urlencode($per_page_raw); ?>">
              <i class="fas fa-chevron-left" style="font-size: 0.76rem;"></i>
            </a>
          </li>

          <?php
            $range = 2;
            $startP = max(1, $page - $range);
            $endP   = min($total_pages, $page + $range);

            if ($startP > 1) {
                echo '<li class="page-item"><a class="page-link" href="category_sales.php?page=1&from_date=' . urlencode($from_date) . '&to_date=' . urlencode($to_date) . '&category_id=' . urlencode($category_id) . '&search=' . urlencode($search) . '&per_page=' . urlencode($per_page_raw) . '">1</a></li>';
                if ($startP > 2) {
                    echo '<li class="page-item disabled"><span class="page-link" style="border:none;">...</span></li>';
                }
            }

            for ($p = $startP; $p <= $endP; $p++) {
                $activeClass = ($p == $page) ? 'active' : '';
                echo '<li class="page-item ' . $activeClass . '"><a class="page-link" href="category_sales.php?page=' . $p . '&from_date=' . urlencode($from_date) . '&to_date=' . urlencode($to_date) . '&category_id=' . urlencode($category_id) . '&search=' . urlencode($search) . '&per_page=' . urlencode($per_page_raw) . '">' . $p . '</a></li>';
            }

            if ($endP < $total_pages) {
                if ($endP < $total_pages - 1) {
                    echo '<li class="page-item disabled"><span class="page-link" style="border:none;">...</span></li>';
                }
                echo '<li class="page-item"><a class="page-link" href="category_sales.php?page=' . $total_pages . '&from_date=' . urlencode($from_date) . '&to_date=' . urlencode($to_date) . '&category_id=' . urlencode($category_id) . '&search=' . urlencode($search) . '&per_page=' . urlencode($per_page_raw) . '">' . $total_pages . '</a></li>';
            }
          ?>

          <!-- Next Page -->
          <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
            <a class="page-link" href="category_sales.php?page=<?php echo min($total_pages, $page + 1); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&category_id=<?php echo urlencode($category_id); ?>&search=<?php echo urlencode($search); ?>&per_page=<?php echo urlencode($per_page_raw); ?>">
              <i class="fas fa-chevron-right" style="font-size: 0.76rem;"></i>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</div>

<!-- html2pdf & Shared Report JS (Excel / PDF / Print export buttons) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<?php require_once __DIR__ . '/partials/js/reports_js.php'; ?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
