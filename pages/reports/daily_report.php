<?php

// Standalone Page: ລາຍງານປະຈຳວັນ (Daily Sales Report)
session_start();

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

require_once dirname(__DIR__, 2) . '/config/db.php';

if (!function_exists('__cvtArr')) { function __cvtArr($dc, $arr){ return array_map(function($v) use ($dc){ return convertFromKip($v, $dc['code'], $dc['rates']); }, (array)$arr); } }

if (!hasPermission('daily_report') && !hasPermission('report')) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

// Date Range & Branch Filter Inputs (Default: 1st of current month to Today)
$from_date = trim($_GET['from_date'] ?? date('Y-m-01'));
$to_date   = trim($_GET['to_date'] ?? date('Y-m-d'));
$filter_store_id = isset($_GET['store_id']) && $_GET['store_id'] !== '' ? intval($_GET['store_id']) : 0;
if (!canViewAllBranches($pdo)) {
    $filter_store_id = intval($_SESSION['store_id'] ?? 1);
}

// Fetch all branches for executive/manager dropdown filter
$branchesList = [];
try {
    $branchesList = $pdo->query("SELECT * FROM tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Check if tbsale_save has store_id / paid_currency columns
$hasStoreIdCol  = false;
$hasCurrencyCol = false;
try {
    $saleCols = $pdo->query("SHOW COLUMNS FROM tbsale_save")->fetchAll(PDO::FETCH_COLUMN);
    $hasStoreIdCol  = in_array('store_id', $saleCols) || in_array('branch_id', $saleCols);
    $storeColName   = in_array('store_id', $saleCols) ? 'store_id' : 'branch_id';
    $hasCurrencyCol = in_array('paid_currency', $saleCols);
} catch (Exception $e) {}

// Currency Filter — which currency the customer actually tendered on the bill
$currency_filter = strtoupper(trim($_GET['currency_filter'] ?? ''));
if ($currency_filter === '' || !isset(getCurrencyMeta()[$currency_filter])) {
    $currency_filter = '';
}

// Multi-currency: reports normally show the global display currency (data stored in
// KIP). When the user filters the report down to one tendered currency, switch the
// whole page's symbol/amounts to match that currency instead, so "ເລືອກສະກຸນເງິນໃດ
// ໂຊສະເພາະສະກຸນເງິນນັ້ນ" — picking THB shows ฿, not the store's default ₭/$.
// getDisplayCurrency() (and displayMoney()) both honor this override globally.
if ($currency_filter !== '') {
    $__curRates = getExchangeRates($pdo);
    $__curRate  = ($currency_filter === 'KIP') ? 1.0 : (float)($__curRates[$currency_filter] ?? 0);
    if ($currency_filter === 'KIP' || $__curRate > 0) {
        setDisplayCurrencyOverride(array_merge(
            ['code' => $currency_filter, 'rate' => $__curRate, 'rates' => $__curRates],
            getCurrencyMeta()[$currency_filter]
        ));
    }
}
$__dc  = getDisplayCurrency($pdo);
$__sym = $__dc['symbol'];
$__dec = (int)$__dc['decimals'];

// Build SQL Query for Daily Report
$where   = ["(s.sale_status IS NULL OR s.sale_status != 'CANCEL')"];
$params  = [];

if (!empty($from_date)) {
    $where[] = "DATE(s.sale_date) >= :from_date";
    $params[':from_date'] = $from_date;
}

if (!empty($to_date)) {
    $where[] = "DATE(s.sale_date) <= :to_date";
    $params[':to_date'] = $to_date;
}

if ($filter_store_id > 0 && $hasStoreIdCol) {
    $where[] = "s.{$storeColName} = :filter_store_id";
    $params[':filter_store_id'] = $filter_store_id;
}

if ($currency_filter !== '' && $hasCurrencyCol) {
    $where[] = "COALESCE(NULLIF(s.paid_currency, ''), 'KIP') = :currency_filter";
    $params[':currency_filter'] = $currency_filter;
}

$whereClause = implode(" AND ", $where);

// Fetch 10 Daily Metrics
$daily_gross_sales     = 0;
$daily_item_discounts  = 0;
$daily_bill_discounts  = 0;
$daily_total_discounts = 0;
$daily_net_sales       = 0;
$daily_cash_payments   = 0;
$daily_qr_payments     = 0;
$daily_tips_sum        = 0;
$daily_bills_count     = 0;
$daily_items_qty       = 0;
$currency_breakdown    = [];

$hasCashCol = false;
$hasQrCol   = false;
try {
    $cols = $pdo->query("SHOW COLUMNS FROM tbsale_save")->fetchAll(PDO::FETCH_COLUMN);
    $hasCashCol = in_array('cash_received', $cols);
    $hasQrCol   = in_array('qr_received', $cols);
} catch (Exception $e) {}

$cashSql = $hasCashCol ? "WHEN s.cash_received > 0 THEN s.cash_received" : "";
$qrSql   = $hasQrCol   ? "WHEN s.qr_received > 0 THEN s.qr_received" : "";

try {
    $stmtDaily = $pdo->prepare("
        SELECT 
            COUNT(s.Id) AS total_bills,
            COALESCE(SUM(s.sale_qty), 0) AS total_items_qty,
            COALESCE(SUM(s.sale_amount), 0) AS total_gross_sales,
            COALESCE(SUM(s.sale_discount_bill), 0) AS total_bill_discounts,
            COALESCE(SUM(s.sale_barlance), 0) AS total_net_sales,
            COALESCE(SUM(CASE 
                {$cashSql}
                WHEN (s.type_pay LIKE '%ເງິນສົດ%' AND s.type_pay NOT LIKE '%ໂອນ%' AND s.type_pay NOT LIKE '%QR%' AND (s.bank_account_id IS NULL OR s.bank_account_id = 0)) THEN s.sale_barlance
                WHEN s.type_pay LIKE '%ເງິນສົດ%' THEN GREATEST(0, s.sale_pay - s.sale_return)
                WHEN (s.type_pay NOT LIKE '%ໂອນ%' AND s.type_pay NOT LIKE '%QR%' AND (s.bank_account_id IS NULL OR s.bank_account_id = 0)) THEN s.sale_barlance
                ELSE 0 
            END), 0) AS total_cash_received,
            COALESCE(SUM(CASE 
                {$qrSql}
                WHEN (s.type_pay LIKE '%ໂອນ%' OR s.type_pay LIKE '%QR%' OR s.bank_account_id > 0) THEN s.sale_barlance
                WHEN s.sale_transfer > 0 THEN s.sale_transfer
                ELSE 0 
            END), 0) AS total_qr_received,
            COALESCE(SUM(GREATEST(0, (s.sale_pay - s.sale_return) - s.sale_barlance)), 0) AS total_tips_sum
        FROM tbsale_save s
        WHERE {$whereClause}
    ");
    $stmtDaily->execute($params);
    $dailyStats = $stmtDaily->fetch(PDO::FETCH_ASSOC);

    // Bank Transfer Revenue Breakdown (Accurately Grouped Per Bank)
    $bank_daily_breakdown = [];
    try {
        $bStmt = $pdo->prepare("
            SELECT 
                COALESCE(s.bank_account_id, 0) as bank_acc_id,
                COALESCE(s.bank_name, '') as bank_label,
                COUNT(s.Id) as total_tx,
                COALESCE(SUM(CASE 
                    WHEN s.sale_transfer > 0 THEN s.sale_transfer
                    WHEN (s.type_pay LIKE '%ໂອນ%' OR s.type_pay LIKE '%QR%' OR s.bank_account_id > 0) THEN s.sale_barlance
                    ELSE 0
                END), 0) as total_received
            FROM tbsale_save s
            WHERE {$whereClause} AND (s.type_pay LIKE '%ໂອນ%' OR s.type_pay LIKE '%QR%' OR s.bank_account_id > 0 OR s.sale_transfer > 0)
            GROUP BY bank_acc_id, bank_label
            ORDER BY total_received DESC
        ");
        $bStmt->execute($params);
        $bank_daily_breakdown_raw = $bStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch bank accounts master list for accurate ID mapping
        $bank_accounts_map = [];
        try {
            $bList = $pdo->query("SELECT * FROM bank_accounts WHERE is_active = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($bList as $b) {
                $bank_accounts_map[$b['id']] = $b;
            }
        } catch (Exception $e) {}

        // Strictly group per bank account ID
        $bank_summary_grouped = [];
        foreach ($bank_daily_breakdown_raw as $bRow) {
            $accId = intval($bRow['bank_acc_id']);
            $label = trim($bRow['bank_label'] ?? '');
            
            // Match accId by label if 0
            if ($accId === 0 && !empty($label) && !empty($bank_accounts_map)) {
                foreach ($bank_accounts_map as $bId => $bAcc) {
                    if (stripos($label, $bAcc['bank_name']) !== false || stripos($bAcc['bank_name'], $label) !== false) {
                        $accId = $bId;
                        break;
                    }
                }
            }

            // Strictly check bank matching - ignore unassigned transactions
            if ($accId === 0) {
                continue;
            }

            if ($accId > 0) {
                if (!isset($bank_summary_grouped[$accId])) {
                    $bank_summary_grouped[$accId] = [
                        'bank_acc_id' => $accId,
                        'bank_label'  => $bank_accounts_map[$accId]['bank_name'] ?? $label,
                        'total_tx'    => 0,
                        'total_received' => 0
                    ];
                }
                $bank_summary_grouped[$accId]['total_tx'] += intval($bRow['total_tx']);
                $bank_summary_grouped[$accId]['total_received'] += floatval($bRow['total_received']);
            }
        }

        $bank_daily_breakdown = array_values($bank_summary_grouped);
    } catch (Exception $e) {}

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

    // Per-day breakdown query (GROUP BY date)
    $stmtPerDay = $pdo->prepare("
        SELECT
            DATE(s.sale_date) AS day_date,
            COUNT(s.Id) AS day_bills,
            COALESCE(SUM(s.sale_qty), 0) AS day_qty,
            COALESCE(SUM(s.sale_amount), 0) AS day_gross,
            COALESCE(SUM(s.sale_discount_bill), 0) AS day_bill_disc,
            COALESCE(SUM(s.sale_barlance), 0) AS day_net,
            COALESCE(SUM(CASE
                {$cashSql}
                WHEN s.type_pay LIKE '%ເງິນສົດ%' AND (s.type_pay NOT LIKE '%ໂອນ%' AND s.type_pay NOT LIKE '%QR%') THEN s.sale_barlance
                WHEN s.type_pay LIKE '%ເງິນສົດ%' THEN GREATEST(0, s.sale_pay - s.sale_return)
                ELSE 0
            END), 0) AS day_cash,
            COALESCE(SUM(CASE
                {$qrSql}
                WHEN (s.type_pay LIKE '%ໂອນ%' OR s.type_pay LIKE '%QR%') AND s.type_pay NOT LIKE '%ເງິນສົດ%' THEN s.sale_barlance
                WHEN s.sale_transfer > 0 THEN s.sale_transfer
                ELSE 0
            END), 0) AS day_qr,
            COALESCE(SUM(GREATEST(0, (s.sale_pay - s.sale_return) - s.sale_barlance)), 0) AS day_tip
        FROM tbsale_save s
        WHERE {$whereClause}
        GROUP BY DATE(s.sale_date)
        ORDER BY DATE(s.sale_date) ASC
    ");
    $stmtPerDay->execute($params);
    $perDayRows = $stmtPerDay->fetchAll(PDO::FETCH_ASSOC);

    // Per-day item discount (GROUP BY date)
    $dayItemDiscMap = [];
    if ($hasItemDiscCol) {
        $stmtDayItemDisc = $pdo->prepare("
            SELECT DATE(s.sale_date) AS day_date, COALESCE(SUM(d.{$hasItemDiscCol}), 0) AS day_item_disc
            FROM tbsale_save_detail d
            INNER JOIN tbsale_save s ON d.save_bill = s.sale_save_bill
            WHERE {$whereClause}
            GROUP BY DATE(s.sale_date)
        ");
        $stmtDayItemDisc->execute($params);
        foreach ($stmtDayItemDisc->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $dayItemDiscMap[$r['day_date']] = (float)$r['day_item_disc'];
        }
    }

    // Money received, broken down by the currency the customer actually tendered per bill.
    // total_foreign divides each bill's net by ITS OWN saved rate, so a day mixing rates
    // still totals correctly.
    $curCols = $pdo->query("SHOW COLUMNS FROM tbsale_save")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('paid_currency', $curCols, true)) {
        $stmtCur = $pdo->prepare("
            SELECT
                COALESCE(NULLIF(s.paid_currency, ''), 'KIP')        AS cur,
                COUNT(*)                                            AS bills,
                COALESCE(SUM(s.sale_barlance), 0)                   AS total_kip,
                COALESCE(SUM(
                    CASE WHEN COALESCE(NULLIF(s.paid_currency, ''), 'KIP') = 'KIP' OR s.paid_rate <= 0
                         THEN s.sale_barlance
                         ELSE s.sale_barlance / s.paid_rate END
                ), 0)                                               AS total_foreign
            FROM tbsale_save s
            WHERE {$whereClause}
            GROUP BY cur
            ORDER BY total_kip DESC
        ");
        $stmtCur->execute($params);
        $currency_breakdown = $stmtCur->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch individual sales bills for transaction list table
    $stmtSalesList = $pdo->prepare("
        SELECT s.*
        FROM tbsale_save s
        WHERE {$whereClause}
        ORDER BY DATE(s.sale_date) DESC, s.sale_time DESC, s.Id DESC
    ");
    $stmtSalesList->execute($params);
    $todaySalesRows = $stmtSalesList->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) { $perDayRows = []; $dayItemDiscMap = []; $todaySalesRows = []; }

require_once __DIR__ . '/../../layouts/header.php';
?>

<link rel="stylesheet" href="../../themes/reports.css?v=<?php echo filemtime(__DIR__ . '/../../themes/reports.css'); ?>">

<div class="container-fluid p-3 p-md-4">
  <!-- Header & Page Meta -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
    <div>
      <h5 class="font-weight-bold text-dark mb-1" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
        <i class="fas fa-calendar-day text-info"></i> <?php echo htmlspecialchars(t('reports.title_daily', 'ລາຍງານປະຈຳວັນ')); ?>
      </h5>
    </div>
  </div>

  <!-- Date Range Filter Box on Top (Compact Inline Row on PC, 2-Col Side-by-Side on Mobile) -->
  <div class="report-filter-box no-print mb-3.5">
    <form method="GET" action="daily_report.php" class="d-flex align-items-end flex-wrap flex-md-nowrap" style="gap: 10px;">
      <!-- From Date -->
      <div class="report-filter-date-col">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-calendar-alt text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.from_date', 'ຕັ້ງແຕ່ວັນທີ:')); ?>
        </label>
        <input type="date" name="from_date" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($from_date); ?>" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #cbd5e1;">
      </div>

      <!-- To Date -->
      <div class="report-filter-date-col">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-calendar-check text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.to_date', 'ຫາວັນທີ:')); ?>
        </label>
        <input type="date" name="to_date" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($to_date); ?>" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #cbd5e1;">
      </div>

      <!-- Branch Filter Dropdown — only worth showing once there's more than one branch -->
      <?php if (canViewAllBranches($pdo) && count($branchesList) > 1): ?>
      <div class="report-filter-date-col" style="min-width: 170px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-store text-info mr-1"></i> <?php echo htmlspecialchars(t('reports.branch_label', 'ສາຂາ:')); ?>
        </label>
        <select name="store_id" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #cbd5e1;" onchange="this.form.submit()">
          <option value="0"><?php echo htmlspecialchars(t('reports.all_branches_opt', '-- ທຸກສາຂາ --')); ?></option>
          <?php foreach ($branchesList as $b): ?>
            <option value="<?php echo $b['store_id']; ?>" <?php echo ($filter_store_id == $b['store_id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($b['store_name']); ?> <?php echo !empty($b['is_main']) ? htmlspecialchars(t('reports.main_branch_suffix', '(ສາຂາໃຫຍ່)')) : ''; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php else: ?>
        <input type="hidden" name="store_id" value="0">
      <?php endif; ?>

      <!-- Currency Filter Dropdown -->
      <div class="report-filter-date-col" style="min-width: 170px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-coins text-warning mr-1"></i> <?php echo htmlspecialchars(t('reports.currency_label', 'ສະກຸນເງິນ:')); ?>
        </label>
        <select name="currency_filter" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #a16207; color: #854d0e; background: #fefce8;" onchange="this.form.submit()">
          <option value=""><?php echo htmlspecialchars(t('reports.all_currencies_opt', '-- ທຸກສະກຸນເງິນ --')); ?></option>
          <?php foreach (getCurrencyMeta() as $__curCode => $__curInfo): ?>
            <option value="<?php echo htmlspecialchars($__curCode); ?>" <?php echo $currency_filter === $__curCode ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($__curInfo['name'] . ' (' . $__curInfo['symbol'] . ')'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Search & Refresh Buttons -->
      <div class="d-flex align-items-center" style="gap: 6px; flex: 0 0 auto;">
        <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-3.5 d-inline-flex align-items-center justify-content-center" style="border-radius: 8px; height: 38px; background: linear-gradient(135deg, #2c5aa0, #244886); white-space: nowrap; font-size: 0.85rem;">
          <i class="fas fa-search mr-1.5"></i> <?php echo htmlspecialchars(t('reports.search_btn', 'ຄົ້ນຫາ')); ?>
        </button>
        <a href="daily_report.php" class="btn btn-light btn-sm border font-weight-bold d-inline-flex align-items-center justify-content-center px-2.5" title="<?php echo htmlspecialchars(t('reports.clear', 'ລ້າງຄ່າ')); ?>" style="border-radius: 8px; height: 38px;">
          <i class="fas fa-redo"></i>
        </a>
      </div>
    </form>
  </div>

  <!-- 10 Summary KPI Cards Box -->
  <div class="mb-4 kpi-cards-grid">
    <!-- 1. ລວມຍອດ -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.kpi_gross_total', 'ລວມຍອດ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" data-target="<?php echo number_format(convertFromKip($daily_gross_sales, $__dc['code'], $__dc['rates']), $__dec, '.', ''); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-file-invoice-dollar"></i></div>
    </div>

    <!-- 2. ສ່ວນຫຼຸດສິນຄ້າ -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.kpi_item_discount', 'ສ່ວນຫຼຸດສິນຄ້າ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" data-target="<?php echo number_format(convertFromKip($daily_item_discounts, $__dc['code'], $__dc['rates']), $__dec, '.', ''); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-tag"></i></div>
    </div>

    <!-- 3. ສ່ວນຫຼຸດໃບບິນ -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.kpi_bill_discount', 'ສ່ວນຫຼຸດໃບບິນ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" data-target="<?php echo number_format(convertFromKip($daily_bill_discounts, $__dc['code'], $__dc['rates']), $__dec, '.', ''); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-receipt"></i></div>
    </div>

    <!-- 4. ໂປຣໂມຊັ່ນ & ຂອງແຖມ (Promotion Summary Block) -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title text-nowrap" style="white-space: nowrap !important;"><i class="fas fa-bullhorn mr-1"></i> <?php echo htmlspecialchars(t('reports.kpi_promotion', 'ໂປຣໂມຊັ່ນ')); ?> <?php if ($daily_promo_gifts_count > 0): ?><span class="text-warning font-weight-normal ml-1" style="font-size: 0.75rem;">(<?php echo htmlspecialchars(t('reports.gift_label', 'ແຖມ')); ?>: <?php echo number_format($daily_promo_gifts_count); ?>)</span><?php endif; ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val text-nowrap" style="white-space: nowrap !important; font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" data-target="<?php echo number_format(convertFromKip($daily_promo_discounts, $__dc['code'], $__dc['rates']), $__dec, '.', ''); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-gift"></i></div>
    </div>

    <!-- 4. ສ່ວນຫຼຸດທັງໝົດ -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.kpi_total_discount', 'ສ່ວນຫຼຸດທັງໝົດ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" data-target="<?php echo number_format(convertFromKip($daily_total_discounts, $__dc['code'], $__dc['rates']), $__dec, '.', ''); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-tags"></i></div>
    </div>

    <!-- 5. ຍອດຂາຍສຸດທິ -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #10b981 0%, #047857 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.kpi_net_sales', 'ຍອດຂາຍສຸດທິ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" data-target="<?php echo number_format(convertFromKip($daily_net_sales, $__dc['code'], $__dc['rates']), $__dec, '.', ''); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-coins"></i></div>
    </div>

    <!-- 6. ເງິນສົດ -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.cash_opt', 'ເງິນສົດ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" data-target="<?php echo number_format(convertFromKip($daily_cash_payments, $__dc['code'], $__dc['rates']), $__dec, '.', ''); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-money-bill-wave"></i></div>
    </div>

    <!-- 7. ເງິນໂອນ -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #06b6d4 0%, #0e7490 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.kpi_transfer', 'ເງິນໂອນ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" data-target="<?php echo number_format(convertFromKip($daily_qr_payments, $__dc['code'], $__dc['rates']), $__dec, '.', ''); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-qrcode"></i></div>
    </div>

    <!-- 9. ບິນ -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.bills_unit', 'ບິນ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <?php $u_bills = htmlspecialchars(t('reports.bills_unit', 'ບິນ')); ?>
          <span class="counter-num" data-suffix-key="reports.bills_unit" data-target="<?php echo (int)$daily_bills_count; ?>" data-suffix=" <?php echo $u_bills; ?>">0 <?php echo $u_bills; ?></span>
        </div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-file-invoice"></i></div>
    </div>

    <!-- 10. ລາຍການ -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.items_unit', 'ລາຍການ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <?php $u_pcs = htmlspecialchars(t('reports.unit_pcs', 'ອັນ')); ?>
          <span class="counter-num" data-suffix-key="reports.unit_pcs" data-target="<?php echo (int)$daily_items_qty; ?>" data-suffix=" <?php echo $u_pcs; ?>">0 <?php echo $u_pcs; ?></span>
        </div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-boxes"></i></div>
    </div>
  </div>

  <!-- Bank Revenue Breakdown Row -->
  <?php if (!empty($bank_daily_breakdown)): ?>
    <div class="card border-0 shadow-sm mb-4 w-100" style="border-radius: 12px; background: #f8fafc; border: 1.5px solid #e2e8f0;">
      <div class="card-body p-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <h6 class="font-weight-bold text-dark mb-0">
            <i class="fas fa-university text-primary mr-1.5"></i> <?php echo htmlspecialchars(t('reports.bank_breakdown_title', 'ຍອດຮັບເງິນໂອນແຍກຕາມທະນາຄານ')); ?>
          </h6>
          <span class="badge badge-primary font-weight-bold px-2.5 py-1" style="font-size: 0.82rem;"><?php echo htmlspecialchars(t('reports.total_label', 'ລວມ:')); ?> <?php echo displayMoney($pdo, $daily_qr_payments); ?></span>
        </div>
        <div class="row" style="row-gap: 12px;">
          <?php foreach ($bank_daily_breakdown as $bRow): ?>
            <?php 
              $bAccId = intval($bRow['bank_acc_id']);
              $bInfo = $bank_accounts_map[$bAccId] ?? [];
              $label = !empty($bInfo['bank_name']) ? $bInfo['bank_name'] : (!empty($bRow['bank_label']) && $bRow['bank_label'] !== 'ບໍ່ໄດ້ລະບຸ' ? $bRow['bank_label'] : 'ບໍ່ໄດ້ລະບຸ');
              if ($label === 'ບໍ່ໄດ້ລະບຸ' && !empty($bank_accounts_map)) {
                  $firstBank = reset($bank_accounts_map);
                  $label = $firstBank['bank_name'];
                  $bInfo = $firstBank;
              }
              $bCode = $bInfo['bank_code'] ?? $label;
              $code = strtoupper(trim($bCode));
              $bColor = ($code === 'BCEL' || stripos($label, 'BCEL') !== false) ? '#002d72' : (($code === 'LDB' || stripos($label, 'LDB') !== false) ? '#047857' : (($code === 'JDB' || stripos($label, 'JDB') !== false) ? '#6b21a8' : (($code === 'STB' || stripos($label, 'ST') !== false) ? '#ea580c' : '#0284c7')));
              $logoFile = $bInfo['bank_logo'] ?? '';
              $bLogo = (!empty($logoFile) && file_exists(__DIR__ . '/../../assets/img/banks/' . basename($logoFile)))
                       ? ('../../assets/img/banks/' . basename($logoFile))
                       : ('../../assets/img/banks/' . strtolower($code) . '.svg');
            ?>
            <div class="col-lg-3 col-md-4 col-sm-6">
              <div class="px-2.5 py-1.5 rounded border bg-white d-flex align-items-center justify-content-between shadow-2xs h-100" style="border: 1px solid #e2e8f0; min-height: 44px;">
                <div class="d-flex align-items-center" style="gap: 8px;">
                  <img src="<?php echo htmlspecialchars($bLogo); ?>" style="width: 32px; height: 32px; object-fit: cover; border-radius: 50% !important; padding: 1px; border: 1.5px solid #cbd5e1; background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,0.08);" onerror="this.src='../../assets/img/banks/default.svg';">
                  <div>
                    <div class="font-weight-bold text-dark" style="font-size: 0.82rem; line-height: 1.1;"><?php echo htmlspecialchars($label); ?></div>
                    <small class="text-muted font-weight-bold" style="font-size: 0.72rem;"><?php echo number_format($bRow['total_tx']); ?> <?php echo htmlspecialchars(t('reports.bills_unit', 'ບິນ')); ?></small>
                  </div>
                </div>
                <div class="font-weight-bold text-right pl-1" style="font-size: 0.92rem; color: <?php echo $bColor; ?>;">
                  <?php echo displayMoney($pdo, $bRow['total_received']); ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Money Received by Tender Currency -->
  <?php if (!empty($currency_breakdown)): ?>
    <?php $__curMeta = function_exists('getCurrencyMeta') ? getCurrencyMeta() : []; ?>
    <div class="card border-0 shadow-sm mb-4 w-100" style="border-radius: 12px; background: #f8fafc; border: 1.5px solid #e2e8f0;">
      <div class="card-body" style="padding: 16px 18px;">
        <h6 class="font-weight-bold text-dark mb-3">
          <i class="fas fa-coins text-warning mr-1.5"></i> <?php echo htmlspecialchars(t('reports.currency_breakdown_title', 'ຮັບເງິນແຍກຕາມສະກຸນເງິນ')); ?>
        </h6>
        <div style="display: flex; flex-wrap: wrap; gap: 12px;">
          <?php foreach ($currency_breakdown as $cRow): ?>
            <?php
              $cCode = strtoupper($cRow['cur'] ?? 'KIP');
              $cName = $__curMeta[$cCode]['name'] ?? $cCode;
              $cSym  = $__curMeta[$cCode]['symbol'] ?? $cCode;
              $cDec  = (int)($__curMeta[$cCode]['decimals'] ?? ($cCode === 'KIP' ? 0 : 2));
              $cColor = ($cCode === 'THB') ? '#0369a1' : (($cCode === 'USD') ? '#047857' : (($cCode === 'CNY') ? '#b91c1c' : '#a16207'));
            ?>
            <div style="flex: 1 1 200px; min-width: 190px; max-width: 300px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px;">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="font-weight-bold text-dark" style="font-size: 0.9rem;">
                  <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: <?php echo $cColor; ?>; margin-right: 6px; vertical-align: middle;"></span>
                  <?php echo htmlspecialchars($cName); ?> <span class="text-muted">(<?php echo htmlspecialchars($cSym); ?>)</span>
                </span>
                <span class="badge badge-light border text-muted font-weight-bold" style="font-size: 0.7rem;"><?php echo number_format($cRow['bills']); ?> <?php echo htmlspecialchars(t('reports.bills_unit', 'ບິນ')); ?></span>
              </div>
              <div class="font-weight-bold" style="font-size: 1.15rem; line-height: 1.2; color: <?php echo $cColor; ?>;">
                <?php echo number_format((float)$cRow['total_foreign'], $cDec); ?> <?php echo htmlspecialchars($cSym); ?>
              </div>
              <?php if ($cCode !== $__dc['code']): ?>
                <div class="text-muted font-weight-bold" style="font-size: 0.76rem; margin-top: 3px;">≈ <?php echo displayMoney($pdo, $cRow['total_kip']); ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Charts Section (always shown) -->
  <?php
    // Prepare chart data (always, even if empty)
    $chartLabels = [];
    $chartNet    = [];
    $chartCash   = [];
    $chartQr     = [];
    $chartTip    = [];
    $chartBills  = [];
    foreach (($perDayRows ?? []) as $row) {
      $chartLabels[] = $row['day_date'];
      $chartNet[]    = (float)$row['day_net'];
      $chartCash[]   = (float)$row['day_cash'];
      $chartQr[]     = (float)$row['day_qr'];
      $chartTip[]    = (float)$row['day_tip'];
      $chartBills[]  = (int)$row['day_bills'];
    }
    // If no data, use today as placeholder with 0
    if (empty($chartLabels)) {
      $chartLabels = [date('Y-m-d')];
      $chartNet = $chartCash = $chartQr = $chartTip = $chartBills = [0];
    }
    $totalCash = array_sum($chartCash);
    $totalQr   = array_sum($chartQr);
    $totalTip  = array_sum($chartTip);
  ?>

  <div class="row mt-3" style="gap: 0;">
    <!-- Bar Chart: ຍອດຂາຍສຸດທິລາຍວັນ -->
    <div class="col-12 col-md-8 mb-3">
      <div class="card border-0 shadow-sm" style="border-radius: 14px; padding: 18px 20px;">
        <div class="font-weight-bold text-dark mb-3" style="font-size: 0.92rem;">
          <i class="fas fa-chart-bar text-primary mr-2"></i> <?php echo htmlspecialchars(t('reports.chart_daily_net_sales', 'ຍອດຂາຍສຸດທິລາຍວັນ')); ?>
        </div>
        <canvas id="chartDailySales" style="max-height: 240px;"></canvas>
      </div>
    </div>

    <!-- Donut Chart: ສັດສ່ວນການຊຳລະ -->
    <div class="col-12 col-md-4 mb-3">
      <div class="card border-0 shadow-sm" style="border-radius: 14px; padding: 18px 20px; height: 100%;">
        <div class="font-weight-bold text-dark mb-3" style="font-size: 0.92rem;">
          <i class="fas fa-chart-pie text-success mr-2"></i> <?php echo htmlspecialchars(t('reports.chart_payment_ratio', 'ສັດສ່ວນການຊຳລະ')); ?>
        </div>
        <canvas id="chartPaymentMethod" style="max-height: 200px;"></canvas>
        <!-- Legend -->
        <div class="mt-3 d-flex flex-wrap justify-content-center" style="gap: 10px;">
          <div class="d-flex align-items-center" style="gap: 6px; font-size: 0.8rem;">
            <span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:#6366f1;"></span>
            <span class="font-weight-bold"><?php echo htmlspecialchars(t('reports.cash_opt', 'ເງິນສົດ')); ?></span>
            <span class="text-muted"><?php echo displayMoney($pdo, $totalCash); ?></span>
          </div>
          <div class="d-flex align-items-center" style="gap: 6px; font-size: 0.8rem;">
            <span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:#06b6d4;"></span>
            <span class="font-weight-bold"><?php echo htmlspecialchars(t('reports.col_transfer', 'ໂອນ')); ?></span>
            <span class="text-muted"><?php echo displayMoney($pdo, $totalQr); ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bill Count Line Chart -->
  <div class="card border-0 shadow-sm mb-4" style="border-radius: 14px; padding: 18px 20px;">
    <div class="font-weight-bold text-dark mb-3" style="font-size: 0.92rem;">
      <i class="fas fa-chart-line text-warning mr-2"></i> <?php echo htmlspecialchars(t('reports.chart_daily_bill_count', 'ຈຳນວນບິນລາຍວັນ')); ?>
    </div>
    <canvas id="chartDailyBills" style="max-height: 160px;"></canvas>
  </div>

</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

  // ຕັ້ງຟ້ອນ Chart.js ໃຫ້ຄືກັນກັບໜ້າ
  Chart.defaults.font.family = "'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif";
  Chart.defaults.font.size   = 11;

  // CountUp Animation
  document.querySelectorAll('.counter-num').forEach(function (el) {
    var target = parseFloat(el.getAttribute('data-target') || '0');
    var decs = parseInt(el.getAttribute('data-decimals') || '0', 10);
    var cfmt = function(v){ return v.toLocaleString('en-US',{minimumFractionDigits:decs,maximumFractionDigits:decs}); };
    var suffix = el.getAttribute('data-suffix') || '';
    if (isNaN(target)) target = 0;
    var startTime = null;
    var duration = 1800;
    function step(ts) {
      if (!startTime) startTime = ts;
      var prog = Math.min((ts - startTime) / duration, 1);
      var ease = 1 - Math.pow(1 - prog, 3);
      el.textContent = cfmt(ease * target) + suffix;
      if (prog < 1) requestAnimationFrame(step);
      else el.textContent = cfmt(target) + suffix;
    }
    requestAnimationFrame(step);
  });

  // ຊ່ອງໜ່ວຍ (ບິນ/ອັນ) ຖືກ "ອົບ" ເຂົ້າໃນ text node ດຽວກັນກັບຕົວເລກ (ເຊັ່ນ "16 ບິນ") ຕອນນັບແລ້ວ —
  // ຄຳແປແບບ reverse-match ຂອງ dashboard ຈັບ text ປະສົມແບບນີ້ບໍ່ໄດ້ ຈຶ່ງບໍ່ປ່ຽນພາສາໃຫ້ອັດຕະໂນມັດ.
  // ຟັງເຫດການ pos:langchange (dashboard.php dispatch ໃສ່ iframe ນີ້ຕອນສະຫຼັບພາສາ) ແລ້ວແປງຊ່ອງໜ່ວຍ
  // ໃໝ່ດ້ວຍ dictionary ຂອງ parent window, ຄືນ text ດ້ວຍຄ່າສຸດທ້າຍ (ນັບແລ້ວ) + ຊ່ອງໜ່ວຍພາສາໃໝ່.
  window.addEventListener('pos:langchange', function(e) {
    var lang = (e && e.detail && e.detail.lang) || (window.parent && window.parent.CURRENT_ACTIVE_LANG);
    if (!lang) return;
    var dict = null;
    try { dict = window.parent.I18N_ALL_MERGED && window.parent.I18N_ALL_MERGED[lang]; } catch (err) {}
    if (!dict) return;

    document.querySelectorAll('.counter-num[data-suffix-key]').forEach(function(el) {
      var key = el.getAttribute('data-suffix-key');
      if (!dict[key]) return;
      var newSuffix = ' ' + dict[key];
      el.setAttribute('data-suffix', newSuffix);

      var target = parseFloat(el.getAttribute('data-target') || '0');
      var decs = parseInt(el.getAttribute('data-decimals') || '0', 10);
      if (isNaN(target)) target = 0;
      var cfmt = function(v){ return v.toLocaleString('en-US', {minimumFractionDigits:decs, maximumFractionDigits:decs}); };
      el.textContent = cfmt(target) + newSuffix;
    });
  });

  // Chart data (always defined, 0 when no data)
  var labels   = <?php echo json_encode($chartLabels); ?>;
  var netData  = <?php echo json_encode(__cvtArr($__dc, $chartNet)); ?>;
  var cashData = <?php echo json_encode(__cvtArr($__dc, $chartCash)); ?>;
  var qrData   = <?php echo json_encode(__cvtArr($__dc, $chartQr)); ?>;
  var tipData  = <?php echo json_encode(__cvtArr($__dc, $chartTip)); ?>;
  var billsData = <?php echo json_encode($chartBills); ?>;

  // Bar Chart: Daily Net Sales
  new Chart(document.getElementById('chartDailySales'), {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: <?php echo json_encode(t('reports.chart_legend_net', 'ສຸດທິ'), JSON_UNESCAPED_UNICODE); ?> + ' (<?php echo addslashes($__sym); ?>)',
        data: netData,
        backgroundColor: 'rgba(37,99,235,0.75)',
        borderColor: '#1d4ed8',
        borderWidth: 1.5,
        borderRadius: 6,
      }, {
        label: <?php echo json_encode(t('reports.chart_legend_cash', 'ເງິນສົດ'), JSON_UNESCAPED_UNICODE); ?> + ' (<?php echo addslashes($__sym); ?>)',
        data: cashData,
        backgroundColor: 'rgba(99,102,241,0.55)',
        borderColor: '#4338ca',
        borderWidth: 1,
        borderRadius: 4,
      }, {
        label: <?php echo json_encode(t('reports.chart_legend_transfer', 'ໂອນ'), JSON_UNESCAPED_UNICODE); ?> + ' (<?php echo addslashes($__sym); ?>)',
        data: qrData,
        backgroundColor: 'rgba(6,182,212,0.55)',
        borderColor: '#0e7490',
        borderWidth: 1,
        borderRadius: 4,
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 12 } },
        tooltip: {
          callbacks: {
            label: function(ctx) {
              return ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString('en-US',{maximumFractionDigits:<?php echo $__dec; ?>}) + ' <?php echo addslashes($__sym); ?>';
            }
          }
        }
      },
      scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10 } } },
        y: {
          grid: { color: 'rgba(0,0,0,0.05)' },
          ticks: {
            font: { size: 10 },
            callback: function(v) { return <?php echo ($__dc['code']==='KIP')?'true':'false'; ?> ? (v/1000).toFixed(0) + 'K' : v.toLocaleString('en-US',{maximumFractionDigits:<?php echo $__dec; ?>}); }
          }
        }
      }
    }
  });

  // Donut Chart: Payment Method
  var totalPay = <?php echo (convertFromKip($totalCash + $totalQr, $__dc['code'], $__dc['rates']) ?: 1); ?>;
  new Chart(document.getElementById('chartPaymentMethod'), {
    type: 'doughnut',
    data: {
      labels: [<?php echo json_encode(t('reports.cash_opt', 'ເງິນສົດ'), JSON_UNESCAPED_UNICODE); ?>, <?php echo json_encode(t('reports.col_transfer', 'ໂອນ'), JSON_UNESCAPED_UNICODE); ?>],
      datasets: [{
        data: [<?php echo convertFromKip($totalCash, $__dc['code'], $__dc['rates']); ?>, <?php echo convertFromKip($totalQr, $__dc['code'], $__dc['rates']); ?>],
        backgroundColor: ['#6366f1', '#06b6d4'],
        borderColor: ['#4338ca', '#0e7490'],
        borderWidth: 2,
        hoverOffset: 8,
      }]
    },
    options: {
      responsive: true,
      cutout: '68%',
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: function(ctx) {
              var pct = ((ctx.parsed / totalPay) * 100).toFixed(1);
              return ctx.label + ': ' + ctx.parsed.toLocaleString('en-US',{maximumFractionDigits:<?php echo $__dec; ?>}) + ' <?php echo addslashes($__sym); ?> (' + pct + '%)';
            }
          }
        }
      }
    }
  });

  // Line Chart: Daily Bills Count
  new Chart(document.getElementById('chartDailyBills'), {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: <?php echo json_encode(t('reports.chart_legend_bill_count', 'ຈຳນວນບິນ'), JSON_UNESCAPED_UNICODE); ?>,
        data: billsData,
        borderColor: '#f59e0b',
        backgroundColor: 'rgba(245,158,11,0.12)',
        borderWidth: 2.5,
        pointRadius: 5,
        pointBackgroundColor: '#f59e0b',
        fill: true,
        tension: 0.35,
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: { label: function(ctx) { return <?php echo json_encode(t('reports.chart_tooltip_bill', 'ບິນ:') . ' ', JSON_UNESCAPED_UNICODE); ?> + ctx.parsed.y; } }
        }
      },
      scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10 } } },
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(0,0,0,0.04)' },
          ticks: { stepSize: 1, font: { size: 10 } }
        }
      }
    }
  });
});
</script>

<!-- Bill Details & Thermal Receipt Modals -->
<?php require_once __DIR__ . '/partials/reports_modal.php'; ?>
<?php require_once __DIR__ . '/partials/js/reports_js.php'; ?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
