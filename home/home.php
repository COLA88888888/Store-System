<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Multi-currency: dashboard figures stored in KIP, shown in the active currency. A
// currency picked here overrides the store's global display_currency setting for this
// view only (does not filter which bills are counted — just how the totals are shown).
$currency_filter = strtoupper(trim($_GET['currency_filter'] ?? ''));
if ($currency_filter !== '' && isset(getCurrencyMeta()[$currency_filter])) {
    $__hRates = getExchangeRates($pdo);
    $__hRate  = ($currency_filter === 'KIP') ? 1.0 : (float)($__hRates[$currency_filter] ?? 0);
    if ($currency_filter === 'KIP' || $__hRate > 0) {
        setDisplayCurrencyOverride(array_merge(
            ['code' => $currency_filter, 'rate' => $__hRate, 'rates' => $__hRates],
            getCurrencyMeta()[$currency_filter]
        ));
    } else {
        $currency_filter = '';
    }
} else {
    $currency_filter = '';
}

$__hdc = getDisplayCurrency($pdo);
$__hsym = $__hdc['symbol']; $__hdec = (int)$__hdc['decimals'];
$__hcvt = function($v) use ($__hdc) { return convertFromKip($v, $__hdc['code'], $__hdc['rates']); };

if (empty($_SESSION['user_id']) || empty($_SESSION['checked'])) {
    echo "<script>window.top.location.href = '../auth/login.php?expired=1';</script>";
    exit();
}

// 1. Get Years starting from 2021 up to current year
$current_year_real = (int)date('Y');
$start_year = 2021;
$available_years = [];

for ($y = $current_year_real; $y >= $start_year; $y--) {
    $available_years[] = $y;
}

try {
    $yrStmt = $pdo->query("
        SELECT DISTINCT YEAR(sale_date) AS yr 
        FROM tbsale_save 
        WHERE sale_date IS NOT NULL 
          AND sale_date > '1970-01-01' 
          AND (sale_status IS NULL OR sale_status != 'CANCEL')
        ORDER BY yr DESC
    ");
    while ($row = $yrStmt->fetch(PDO::FETCH_ASSOC)) {
        $dbYr = (int)($row['yr'] ?? 0);
        if ($dbYr >= 2021 && !in_array($dbYr, $available_years)) {
            $available_years[] = $dbYr;
        }
    }
} catch (Exception $e) {}

rsort($available_years);

// 2. Fetch Stores & Filter Inputs
$stores_list = [];
try {
    $stores_list = $pdo->query("SELECT store_id, store_name, is_main FROM tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$selected_store = isset($_GET['store_id']) && is_numeric($_GET['store_id']) ? (int)$_GET['store_id'] : 0;
if (!canViewAllBranches($pdo)) {
    $selected_store = intval($_SESSION['store_id'] ?? 1);
}
$selected_year  = isset($_GET['year']) && is_numeric($_GET['year']) ? (int)$_GET['year'] : $current_year_real;

if (isset($_GET['year']) || isset($_GET['store_id'])) {
    if (isset($_GET['from_date']) && !empty($_GET['from_date']) && date('Y', strtotime($_GET['from_date'])) == $selected_year) {
        $from_date = trim($_GET['from_date']);
    } else {
        $from_date = ($selected_year === $current_year_real) ? date('Y-m-01') : "{$selected_year}-01-01";
    }

    if (isset($_GET['to_date']) && !empty($_GET['to_date']) && date('Y', strtotime($_GET['to_date'])) == $selected_year) {
        $to_date = trim($_GET['to_date']);
    } else {
        $to_date = ($selected_year === $current_year_real) ? date('Y-m-d') : "{$selected_year}-12-31";
    }
} else {
    $from_date = trim($_GET['from_date'] ?? date('Y-m-01'));
    $to_date   = trim($_GET['to_date'] ?? date('Y-m-d'));
}

// ປຸ່ມຊ່ວງເວລາດ່ວນ (ມື້ນີ້ / ເດືອນນີ້ / ທັງປີ) — ໄຮໄລທ໌ປຸ່ມທີ່ກົງກັບຊ່ວງວັນທີປັດຈຸບັນ
$period_today_str = date('Y-m-d');
$active_period = '';
if ($from_date === $period_today_str && $to_date === $period_today_str) {
    $active_period = 'today';
} elseif ($from_date === date('Y-m-01') && $to_date === $period_today_str) {
    $active_period = 'month';
} elseif ($from_date === "{$selected_year}-01-01" && $to_date === "{$selected_year}-12-31") {
    $active_period = 'year';
}

// 3. Build SQL Where for Main Metrics
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
if ($selected_store > 0) {
    $where[] = "s.store_id = :store_id";
    $params[':store_id'] = $selected_store;
}
$whereClause = implode(" AND ", $where);

// 4. Fetch 4 Main Cards Stats: Total Sales, Total Cost, Total Profit, Profit %
// ຕົ້ນທຶນ = ເງິນທີ່ນຳສິນຄ້າເຂົ້າສາຂາ (ຈາກ imports.total_cost) ໃນຊ່ວງວັນທີ/ສາຂາທີ່ເລືອກ — ນັບທັງສິນຄ້າທີ່ຊື້ເຂົ້າໂດຍກົງ
// ແລະ ສິນຄ້າທີ່ໂອນມາຈາກສາຂາອື່ນ (stock_transfer.php ຈະສ້າງແຖວ imports ໃຫ້ສາຂາປາຍທາງອັດຕະໂນມັດ, ເບິ່ງ execute_transfer)
$total_sales  = 0.00;
$total_cost   = 0.00;   // ຕົ້ນທຶນນຳເຂົ້າສິນຄ້າ (imports.total_cost) — ປະໄວ້ໃຫ້ຮູ້
$cogs         = 0.00;   // ຕົ້ນທຶນການຊື້ສິນຄ້າ = ຕົ້ນທຶນຂອງລາຍການທີ່ຂາຍອອກຈິງ (SUM save_qty * cost_price)
$total_profit = 0.00;
$profit_pct   = 0.00;
$has_product_cost_data = false;

try {
    $stmtSales = $pdo->prepare("
        SELECT COALESCE(SUM(s.sale_barlance), SUM(s.sale_amount - COALESCE(s.sale_discount_bill, 0)), 0) AS total_sales
        FROM tbsale_save s
        WHERE {$whereClause}
    ");
    $stmtSales->execute($params);
    $total_sales = floatval($stmtSales->fetchColumn() ?? 0);

    $importWhere = ["DATE(i.import_date) >= :imp_from_date", "DATE(i.import_date) <= :imp_to_date"];
    $importParams = [':imp_from_date' => $from_date, ':imp_to_date' => $to_date];
    if ($selected_store > 0) {
        $importWhere[] = "i.store_id = :imp_store_id";
        $importParams[':imp_store_id'] = $selected_store;
    }
    $importWhereClause = implode(" AND ", $importWhere);

    $stmtCost = $pdo->prepare("SELECT COALESCE(SUM(i.total_cost), 0) FROM imports i WHERE {$importWhereClause}");
    $stmtCost->execute($importParams);
    $total_cost = floatval($stmtCost->fetchColumn() ?? 0);

    // "ຕົ້ນທຶນສິນຄ້າທັງໝົດ" card is only meaningful once at least one product actually has a
    // ລາຄາຊື້ (bprice) on file — otherwise it's just a shop that never records cost, and
    // showing "0 ₭" would look like a real (zero) cost rather than "unknown".
    if ($selected_store > 0) {
        $stmtHasCost = $pdo->prepare("SELECT 1 FROM products WHERE store_id = :sid AND bprice > 0 LIMIT 1");
        $stmtHasCost->execute([':sid' => $selected_store]);
    } else {
        $stmtHasCost = $pdo->query("SELECT 1 FROM products WHERE bprice > 0 LIMIT 1");
    }
    $has_product_cost_data = (bool)$stmtHasCost->fetchColumn();

    // ຕົ້ນທຶນການຊື້ສິນຄ້າ (COGS) = ຕົ້ນທຶນຂອງລາຍການສິນຄ້າທີ່ຂາຍອອກຈິງ ຈາກ tbsale_save_detail
    // (save_qty ຄູນ cost_price ຕໍ່ຫົວໜ່ວຍທີ່ຂາຍ — ຄືກັບທີ່ພ້ອມຄິດຕອນ checkout ໃນ pos_backend.php)
    try {
        $stmtCogs = $pdo->prepare("
            SELECT COALESCE(SUM(d.save_qty * d.cost_price), 0)
            FROM tbsale_save_detail d
            INNER JOIN tbsale_save s ON d.save_bill = s.sale_save_bill
            WHERE {$whereClause}
        ");
        $stmtCogs->execute($params);
        $cogs = floatval($stmtCogs->fetchColumn() ?? 0);
    } catch (Exception $e) { $cogs = 0.00; }

    // ຖ້າຍັງບໍ່ມີຍອດຂາຍເລີຍໃນຊ່ວງນີ້ ໃຫ້ກຳໄລເປັນ 0 (ບໍ່ໃຫ້ເປັນຄ່າລົບ) — ຄ່າລົບຈິງໆ (ຂາຍໄດ້ແຕ່ຕົ້ນທຶນສູງກວ່າ) ຍັງສະແດງໄດ້ຕາມປົກກະຕິ
    // ກຳໄລ = ຍອດຂາຍ - ຕົ້ນທຶນການຊື້ສິນຄ້າ (COGS), ບໍ່ແມ່ນ - ຕົ້ນທຶນນຳເຂົ້າທັງໝົດ
    $total_profit = ($total_sales > 0) ? ($total_sales - $cogs) : 0.00;
    $profit_pct = ($total_sales > 0) ? round(($total_profit / $total_sales) * 100, 1) : 0.0;
} catch (Exception $e) {}

// 5. Revenue & Profit % chart data — follows the selected date range (same as the cards):
//    one day → by hour, up to 62 days → by day, longer → by month. So "ມື້ນີ້" shows today's
//    sales hour by hour, "ເດືອນນີ້" day by day, "ທັງປີ" month by month.
$chartFrom = $from_date ?: date('Y-m-01');
$chartTo   = $to_date ?: date('Y-m-d');
if ($chartTo < $chartFrom) { $__tmp = $chartFrom; $chartFrom = $chartTo; $chartTo = $__tmp; }
$chartDays = (int)((strtotime($chartTo) - strtotime($chartFrom)) / 86400) + 1;
$chart_mode = ($chartDays <= 1) ? 'hour' : (($chartDays <= 62) ? 'day' : 'month');

// Bucket keys in order: hours 0–23, each Y-m-d, or each Y-m in the range
$chart_keys = [];
if ($chart_mode === 'hour') {
    $chart_keys = range(0, 23);
    $bucketSql = "HOUR(s.sale_time)";
} elseif ($chart_mode === 'day') {
    for ($t = strtotime($chartFrom); $t <= strtotime($chartTo); $t = strtotime('+1 day', $t)) $chart_keys[] = date('Y-m-d', $t);
    $bucketSql = "DATE_FORMAT(s.sale_date, '%Y-%m-%d')";
} else {
    for ($t = strtotime(date('Y-m-01', strtotime($chartFrom))); $t <= strtotime($chartTo); $t = strtotime('+1 month', $t)) $chart_keys[] = date('Y-m', $t);
    $bucketSql = "DATE_FORMAT(s.sale_date, '%Y-%m')";
}
$chart_sales = array_fill_keys($chart_keys, 0.00);
$chart_cost  = array_fill_keys($chart_keys, 0.00);

try {
    $stmtChart = $pdo->prepare("
        SELECT {$bucketSql} AS b, COALESCE(SUM(s.sale_barlance), 0) AS sales
        FROM tbsale_save s
        WHERE {$whereClause}
        GROUP BY b
    ");
    $stmtChart->execute($params);
    while ($r = $stmtChart->fetch(PDO::FETCH_ASSOC)) {
        $k = ($chart_mode === 'hour') ? (int)$r['b'] : (string)$r['b'];
        if (array_key_exists($k, $chart_sales)) $chart_sales[$k] = floatval($r['sales']);
    }

    // ຕົ້ນທຶນຍອດຂາຍ (COGS) ຕໍ່ຊ່ວງ ເພື່ອໃຫ້ "ກຳໄລ" ໃນກາຟກົງກັບບັອກສະຫຼຸບຂ້າງເທິງ
    $stmtChartCost = $pdo->prepare("
        SELECT {$bucketSql} AS b, COALESCE(SUM(d.save_qty * d.cost_price), 0) AS cost
        FROM tbsale_save_detail d
        INNER JOIN tbsale_save s ON d.save_bill = s.sale_save_bill
        WHERE {$whereClause}
        GROUP BY b
    ");
    $stmtChartCost->execute($params);
    while ($r = $stmtChartCost->fetch(PDO::FETCH_ASSOC)) {
        $k = ($chart_mode === 'hour') ? (int)$r['b'] : (string)$r['b'];
        if (array_key_exists($k, $chart_cost)) $chart_cost[$k] = floatval($r['cost']);
    }
} catch (Exception $e) {}

$chart_profit = [];
$chart_margin = [];
foreach ($chart_keys as $k) {
    $s = $chart_sales[$k];
    // ຊ່ວງທີ່ຍັງບໍ່ມີຍອດຂາຍ ໃຫ້ກຳໄລເປັນ 0 (ບໍ່ໃຫ້ເປັນຄ່າລົບ)
    $p = ($s > 0) ? ($s - $chart_cost[$k]) : 0.00;
    $chart_profit[] = $p;
    $chart_margin[] = ($s > 0) ? round(($p / $s) * 100, 1) : 0;
}

// Chart titles/badges for the current mode
if ($chart_mode === 'hour') {
    $chart_title_key = 'home.chart_revenue_hourly'; $chart_title_def = 'ກາຟລາຍຮັບລາຍຊົ່ວໂມງ';
    $chart_period_text = date('d/m/Y', strtotime($chartFrom));
    $chart_badge_key = 'home.badge_hours'; $chart_badge_def = '24 ຊົ່ວໂມງ'; $chart_badge_n = null;
} elseif ($chart_mode === 'day') {
    $chart_title_key = 'home.chart_revenue_daily'; $chart_title_def = 'ກາຟລາຍຮັບລາຍວັນ';
    $chart_period_text = date('d/m/Y', strtotime($chartFrom)) . ' – ' . date('d/m/Y', strtotime($chartTo));
    $chart_badge_key = 'home.badge_days'; $chart_badge_def = '%s ວັນ'; $chart_badge_n = count($chart_keys);
} else {
    $chart_title_key = 'home.chart_monthly_revenue'; $chart_title_def = 'ກາຟລາຍຮັບປະຈຳເດືອນ';
    $sameYear = substr($chartFrom, 0, 4) === substr($chartTo, 0, 4);
    $chart_period_text = ($sameYear && substr($chartFrom, 5) === '01-01' && substr($chartTo, 5) === '12-31')
        ? sprintf(t('home.year_label', 'ປີ %d'), (int)substr($chartFrom, 0, 4))
        : date('m/Y', strtotime($chartFrom)) . ' – ' . date('m/Y', strtotime($chartTo));
    $chart_badge_key = 'home.badge_months'; $chart_badge_def = '%s ເດືອນ'; $chart_badge_n = count($chart_keys);
}
$chart_badge_text = $chart_badge_n === null ? t($chart_badge_key, $chart_badge_def) : sprintf(t($chart_badge_key, $chart_badge_def), $chart_badge_n);

// 6. Yearly Sales Comparison Chart (Include selected year with 0 if no sales)
$yearly_sales_map = [];
foreach ($available_years as $yrItem) {
    $yearly_sales_map[$yrItem] = 0.00;
}

try {
    $yearlyWhere = ["sale_date > '1970-01-01'", "(sale_status IS NULL OR sale_status != 'CANCEL')"];
    $yearlyParams = [];
    if ($selected_store > 0) {
        $yearlyWhere[] = "store_id = :store_id";
        $yearlyParams[':store_id'] = $selected_store;
    }
    $yearlyWhereClause = implode(" AND ", $yearlyWhere);

    $stmtYearly = $pdo->prepare("
        SELECT YEAR(sale_date) AS yr, COALESCE(SUM(sale_barlance), 0) AS total_sales
        FROM tbsale_save
        WHERE {$yearlyWhereClause}
        GROUP BY YEAR(sale_date)
        ORDER BY yr ASC
    ");
    $stmtYearly->execute($yearlyParams);
    while ($yRow = $stmtYearly->fetch(PDO::FETCH_ASSOC)) {
        $yrVal = (int)$yRow['yr'];
        $yearly_sales_map[$yrVal] = floatval($yRow['total_sales']);
    }
} catch (Exception $e) {}

ksort($yearly_sales_map);

$yearly_labels = [];
$yearly_sales  = [];
foreach ($yearly_sales_map as $yrKey => $yrSalesVal) {
    $yearly_labels[] = sprintf(t('home.year_label', 'ປີ %d'), $yrKey);
    $yearly_sales[]  = $yrSalesVal;
}

// 7. Top 10 Best Selling Products Chart
$top10_names = [];
$top10_qty   = [];
if ($total_sales > 0) {
    try {
        $stmtTop10 = $pdo->prepare("
            SELECT 
                COALESCE(d.save_proname, p.product_name, 'ສິນຄ້າ') AS pro_name,
                SUM(d.save_qty) AS total_qty
            FROM tbsale_save_detail d
            INNER JOIN tbsale_save s ON d.save_bill = s.sale_save_bill
            LEFT JOIN products p ON d.save_proid = p.product_id
            WHERE {$whereClause}
            GROUP BY d.save_proid, pro_name
            ORDER BY total_qty DESC
            LIMIT 10
        ");
        $stmtTop10->execute($params);
        while ($tRow = $stmtTop10->fetch(PDO::FETCH_ASSOC)) {
            $top10_names[] = $tRow['pro_name'];
            $top10_qty[]   = floatval($tRow['total_qty']);
        }
    } catch (Exception $e) {}
}

$base_path = '../';
require_once __DIR__ . '/../layouts/header.php';
?>

<link rel="stylesheet" href="../themes/reports.css?v=<?php echo filemtime(__DIR__ . '/../themes/reports.css'); ?>">

<style>
  .home-period-group { display: inline-flex; height: 38px; padding: 3px; gap: 3px; border-radius: 8px; border: 1.5px solid #2563eb; background: #eff6ff; }
  .home-period-btn { border: 0; background: transparent; color: #1e40af; font-weight: 700; font-size: 0.82rem; padding: 0 12px; border-radius: 6px; white-space: nowrap; cursor: pointer; transition: background .15s, color .15s; }
  .home-period-btn:hover { background: #dbeafe; }
  .home-period-btn.active { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #ffffff; box-shadow: 0 2px 6px rgba(37,99,235,0.3); }
  .home-period-btn:focus { outline: none; }
  .home-year-select { width: auto; min-width: 0; padding-right: 26px; }
</style>
<script>
  // ຕັ້ງຊ່ວງວັນທີຕາມປຸ່ມທີ່ກົດ ແລ້ວສົ່ງຟອມ — ມື້ນີ້/ເດືອນນີ້ ບັງຄັບເປັນປີປັດຈຸບັນ, ທັງປີ ໃຊ້ປີທີ່ເລືອກຢູ່
  function homeSetPeriod(period, form) {
    var today = <?php echo json_encode(date('Y-m-d')); ?>;
    var curYear = <?php echo (int)$current_year_real; ?>;
    var from, to;
    if (period === 'today') {
      from = to = today;
      form.year.value = curYear;
    } else if (period === 'month') {
      from = today.slice(0, 8) + '01';
      to = today;
      form.year.value = curYear;
    } else {
      var yr = form.year.value;
      from = yr + '-01-01';
      to = yr + '-12-31';
    }
    form.from_date.value = from;
    form.to_date.value = to;
    form.submit();
  }
</script>
<div class="container-fluid p-3 p-md-4">
  <!-- Header Title & Controls -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3.5 gap-2">
    <div>
      <h4 class="font-weight-bold text-dark mb-1" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
        <i class="fas fa-chart-pie text-primary mr-2"></i> <?php echo htmlspecialchars(t('home.title', 'ດາດສ໌ບອດບໍລິຫານ & ວິເຄາະການຂາຍ')); ?>
      </h4>
      <p class="text-muted mb-0" style="font-size: 0.85rem;"><?php echo htmlspecialchars(t('home.subtitle', 'ສະຫຼຸບພາບລວມຍອດຂາຍ, ຕົ້ນທຶນ, ກຳໄລ ແລະ ສະຖິຕິການຂາຍປະຈຳປີ')); ?></p>
    </div>
  </div>

  <!-- Modern Filter Box: Date Range + Dynamic Auto Year Selector -->
  <div class="report-filter-box no-print mb-4" style="padding: 14px 18px; border-radius: 12px; background: #ffffff; border: 1.5px solid #e2e8f0; box-shadow: 0 3px 12px rgba(0,0,0,0.03);">
    <form method="GET" action="home.php" class="d-flex align-items-end flex-wrap" style="gap: 12px;">
      <!-- Branch / Store Selector — only worth showing once there's more than one branch -->
      <?php if (canViewAllBranches($pdo) && count($stores_list) > 1): ?>
      <div style="flex: 1 1 170px; min-width: 150px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-store text-success mr-1"></i> <?php echo htmlspecialchars(t('home.select_branch', 'ເລືອກສາຂາ:')); ?>
        </label>
        <select name="store_id" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit();" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #10b981; color: #047857; background: #ecfdf5;">
          <option value="0" <?php echo ($selected_store === 0) ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('home.all_branches', '-- ທຸກສາຂາ --')); ?></option>
          <?php foreach ($stores_list as $st): ?>
            <option value="<?php echo $st['store_id']; ?>" <?php echo ($selected_store == $st['store_id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($st['store_name']); ?><?php echo (!empty($st['is_main']) ? htmlspecialchars(t('home.main_branch_suffix', ' (ສາຂາໃຫຍ່)')) : ''); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php else: ?>
        <input type="hidden" name="store_id" value="0">
      <?php endif; ?>

      <!-- Year Select (Auto-Generated) -->
      <div style="flex: 0 0 auto;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-calendar-check text-primary mr-1"></i> <?php echo htmlspecialchars(t('home.select_year', 'ເລືອກປີ:')); ?>
        </label>
        <select name="year" class="form-control form-control-sm font-weight-bold home-year-select" onchange="this.form.from_date.value=''; this.form.to_date.value=''; this.form.submit();" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #2563eb; color: #1e40af; background: #eff6ff;">
          <?php foreach ($available_years as $yr): ?>
            <option value="<?php echo $yr; ?>" <?php echo ($selected_year == $yr) ? 'selected' : ''; ?>><?php echo htmlspecialchars(sprintf(t('home.year_label', 'ປີ %d'), $yr)); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Quick Period Buttons: ມື້ນີ້ / ເດືອນນີ້ / ທັງປີ -->
      <div style="flex: 0 0 auto;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-clock text-primary mr-1"></i> <span data-i18n-key="home.period_label"><?php echo htmlspecialchars(t('home.period_label', 'ຊ່ວງເວລາ:')); ?></span>
        </label>
        <div class="home-period-group">
          <?php foreach (['today' => ['home.period_today', 'ມື້ນີ້'], 'month' => ['home.period_month', 'ເດືອນນີ້'], 'year' => ['home.period_year', 'ທັງປີ']] as $__pk => $__pl): ?>
            <button type="button" class="home-period-btn<?php echo $active_period === $__pk ? ' active' : ''; ?>" onclick="homeSetPeriod('<?php echo $__pk; ?>', this.form)">
              <span data-i18n-key="<?php echo $__pl[0]; ?>"><?php echo htmlspecialchars(t($__pl[0], $__pl[1])); ?></span>
            </button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- From Date -->
      <div style="flex: 1 1 150px; min-width: 140px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-calendar-alt text-primary mr-1"></i> <?php echo htmlspecialchars(t('home.from_date', 'ຕັ້ງແຕ່ວັນທີ:')); ?>
        </label>
        <input type="date" name="from_date" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($from_date); ?>" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #cbd5e1;">
      </div>

      <!-- To Date -->
      <div style="flex: 1 1 150px; min-width: 140px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-calendar-day text-primary mr-1"></i> <?php echo htmlspecialchars(t('home.to_date', 'ຫາວັນທີ:')); ?>
        </label>
        <input type="date" name="to_date" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($to_date); ?>" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #cbd5e1;">
      </div>

      <!-- Currency Selector — view the same totals converted into a chosen currency -->
      <div style="flex: 1 1 160px; min-width: 150px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-coins text-warning mr-1"></i> <?php echo htmlspecialchars(t('reports.currency_label', 'ສະກຸນເງິນ:')); ?>
        </label>
        <select name="currency_filter" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit();" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #a16207; color: #854d0e; background: #fefce8;">
          <option value=""><?php echo htmlspecialchars(t('home.default_currency_opt', '-- ຄ່າເລີ່ມຕົ້ນຮ້ານ --')); ?></option>
          <?php foreach (getCurrencyMeta() as $__cc => $__ci): ?>
            <option value="<?php echo htmlspecialchars($__cc); ?>" <?php echo $currency_filter === $__cc ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($__ci['name'] . ' (' . $__ci['symbol'] . ')'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Action Buttons -->
      <div class="d-flex align-items-center" style="flex: 0 0 auto; gap: 6px;">
        <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-3.5 d-inline-flex align-items-center justify-content-center" style="border-radius: 8px; height: 38px; background: linear-gradient(135deg, #2563eb, #1d4ed8); white-space: nowrap; font-size: 0.85rem;">
          <i class="fas fa-search mr-1.5"></i> <?php echo htmlspecialchars(t('home.search', 'ຄົ້ນຫາ')); ?>
        </button>
        <a href="home.php" class="btn btn-light btn-sm border font-weight-bold d-inline-flex align-items-center justify-content-center px-2.5" title="<?php echo htmlspecialchars(t('home.clear', 'ລ້າງຄ່າ')); ?>" style="border-radius: 8px; height: 38px;">
          <i class="fas fa-redo"></i>
        </a>
      </div>
    </form>
  </div>

  <!-- Row 1: 5 Key Metrics Cards (Modern Gradient & Animated CountUp) -->
  <div class="row mb-4">
    <!-- Card 1: Total Sales -->
    <div class="col-lg col-md-4 col-6 mb-3 mb-lg-0">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 14px; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: #ffffff;">
        <div class="card-body p-3 p-lg-3.5 position-relative overflow-hidden">
          <div class="d-flex justify-content-between align-items-start mb-1.5">
            <span class="text-white font-weight-bold metric-title"><?php echo htmlspecialchars(t('home.card_total_sales', 'ຍອດຂາຍທັງໝົດ')); ?></span>
            <div class="rounded-circle d-flex align-items-center justify-content-center metric-icon-box" style="background: rgba(255,255,255,0.22);">
              <i class="fas fa-cash-register text-white metric-icon"></i>
            </div>
          </div>
          <h4 class="font-weight-bold text-white mb-0" style="font-family: sans-serif; word-break: break-word; white-space: normal; line-height: 1.2;">
            <span class="metric-num counter-num" data-target="<?php echo $__hcvt((float)$total_sales); ?>" data-decimals="<?php echo $__hdec; ?>">0</span> <small class="metric-currency"><?php echo htmlspecialchars($__hsym); ?></small>
          </h4>
        </div>
      </div>
    </div>

    <!-- Card 2: Total Cost — ເຊື່ອງໄວ້ຖ້າຍັງບໍ່ມີສິນຄ້າໃດຕັ້ງລາຄາຊື້ (bprice) ໄວ້ເລີຍ, ເພື່ອບໍ່ໃຫ້
         ເບິ່ງຄືວ່າຕົ້ນທຶນແມ່ນ 0 ແທ້ໆ ທັງທີ່ຄວາມຈິງແມ່ນຍັງບໍ່ໄດ້ບັນທຶກ -->
    <?php if ($has_product_cost_data): ?>
    <div class="col-lg col-md-4 col-6 mb-3 mb-lg-0">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 14px; background: linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%); color: #ffffff;">
        <div class="card-body p-3 p-lg-3.5 position-relative overflow-hidden">
          <div class="d-flex justify-content-between align-items-start mb-1.5">
            <span class="text-white font-weight-bold metric-title"><?php echo htmlspecialchars(t('home.card_total_cost', 'ຕົ້ນທຶນສິນຄ້າທັງໝົດ')); ?></span>
            <div class="rounded-circle d-flex align-items-center justify-content-center metric-icon-box" style="background: rgba(255,255,255,0.22);">
              <i class="fas fa-boxes text-white metric-icon"></i>
            </div>
          </div>
          <h4 class="font-weight-bold text-white mb-0" style="font-family: sans-serif; word-break: break-word; white-space: normal; line-height: 1.2;">
            <span class="metric-num counter-num" data-target="<?php echo $__hcvt((float)$total_cost); ?>" data-decimals="<?php echo $__hdec; ?>">0</span> <small class="metric-currency"><?php echo htmlspecialchars($__hsym); ?></small>
          </h4>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Card 3: Cost of Goods Sold — ຕົ້ນທຶນການຊື້ສິນຄ້າ (ຕົ້ນທຶນຂອງລາຍການທີ່ຂາຍອອກຈິງ) -->
    <div class="col-lg col-md-4 col-6 mb-3 mb-lg-0">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 14px; background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%); color: #ffffff;">
        <div class="card-body p-3 p-lg-3.5 position-relative overflow-hidden">
          <div class="d-flex justify-content-between align-items-start mb-1.5">
            <span class="text-white font-weight-bold metric-title"><?php echo htmlspecialchars(t('home.card_cogs', 'ຕົ້ນທຶນການຂາຍ')); ?></span>
            <div class="rounded-circle d-flex align-items-center justify-content-center metric-icon-box" style="background: rgba(255,255,255,0.22);">
              <i class="fas fa-shopping-cart text-white metric-icon"></i>
            </div>
          </div>
          <h4 class="font-weight-bold text-white mb-0" style="font-family: sans-serif; word-break: break-word; white-space: normal; line-height: 1.2;">
            <span class="metric-num counter-num" data-target="<?php echo $__hcvt((float)$cogs); ?>" data-decimals="<?php echo $__hdec; ?>">0</span> <small class="metric-currency"><?php echo htmlspecialchars($__hsym); ?></small>
          </h4>
        </div>
      </div>
    </div>

    <!-- Card 4: Total Profit -->
    <div class="col-lg col-md-4 col-6 mb-3 mb-lg-0">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 14px; background: linear-gradient(135deg, #10b981 0%, #047857 100%); color: #ffffff;">
        <div class="card-body p-3 p-lg-3.5 position-relative overflow-hidden">
          <div class="d-flex justify-content-between align-items-start mb-1.5">
            <span class="text-white font-weight-bold metric-title"><?php echo htmlspecialchars(t('home.card_total_profit', 'ກຳໄລທັງໝົດ')); ?></span>
            <div class="rounded-circle d-flex align-items-center justify-content-center metric-icon-box" style="background: rgba(255,255,255,0.22);">
              <i class="fas fa-chart-line text-white metric-icon"></i>
            </div>
          </div>
          <h4 class="font-weight-bold text-white mb-0" style="font-family: sans-serif; word-break: break-word; white-space: normal; line-height: 1.2;">
            <span class="metric-num counter-num" data-target="<?php echo $__hcvt((float)$total_profit); ?>" data-decimals="<?php echo $__hdec; ?>">0</span> <small class="metric-currency"><?php echo htmlspecialchars($__hsym); ?></small>
          </h4>
        </div>
      </div>
    </div>

    <!-- Card 5: Profit Margin % -->
    <div class="col-lg col-md-4 col-6 mb-3 mb-lg-0">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 14px; background: linear-gradient(135deg, #f59e0b 0%, #b45309 100%); color: #ffffff;">
        <div class="card-body p-3 p-lg-3.5 position-relative overflow-hidden">
          <div class="d-flex justify-content-between align-items-start mb-1.5">
            <span class="text-white font-weight-bold metric-title"><?php echo htmlspecialchars(t('home.card_profit_pct', 'ກຳໄລ %')); ?></span>
            <div class="rounded-circle d-flex align-items-center justify-content-center metric-icon-box" style="background: rgba(255,255,255,0.22);">
              <i class="fas fa-percentage text-white metric-icon"></i>
            </div>
          </div>
          <h4 class="font-weight-bold text-white mb-0" style="font-family: sans-serif; word-break: break-word; white-space: normal; line-height: 1.2;">
            <span class="metric-num counter-num" data-target="<?php echo (float)$profit_pct; ?>" data-decimals="1">0</span>%
          </h4>
        </div>
      </div>
    </div>
  </div>

  <style>
    .metric-title { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.3px; }
    .metric-num { font-size: 0.95rem; }
    .metric-currency { font-size: 1.05rem; font-weight: 800; }
    .metric-icon-box { width: 30px; height: 30px; }
    .metric-icon { font-size: 0.9rem; }

    @media (min-width: 992px) {
      .metric-title { font-size: 0.85rem; letter-spacing: 0.5px; }
      .metric-num { font-size: 1.4rem; }
      .metric-currency { font-size: 1.15rem; font-weight: 800; }
      .metric-icon-box { width: 38px; height: 38px; }
      .metric-icon { font-size: 1.1rem; }
    }
  </style>

  <!-- Row 2: 2 Primary Charts (Monthly Revenue for Selected Year & Yearly Sales Comparison) -->
  <div class="row mb-4">
    <!-- Chart 1: Monthly Revenue for Selected Year -->
    <div class="col-lg-8 mb-4 mb-lg-0">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 14px; background: #ffffff;">
        <div class="card-header bg-white border-0 py-3 px-3.5 d-flex justify-content-between align-items-center">
          <h6 class="font-weight-bold text-dark mb-0" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
            <i class="fas fa-chart-area text-primary mr-2"></i> <span data-i18n-key="<?php echo $chart_title_key; ?>"><?php echo htmlspecialchars(t($chart_title_key, $chart_title_def)); ?></span> (<?php echo htmlspecialchars($chart_period_text); ?>)
          </h6>
          <span class="badge badge-light border text-primary font-weight-bold px-2.5 py-1"><?php echo htmlspecialchars($chart_badge_text); ?></span>
        </div>
        <div class="card-body p-3">
          <div style="position: relative; height: 380px; width: 100%;">
            <canvas id="monthlyRevenueChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Chart 2: Yearly Sales Comparison -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 14px; background: #ffffff;">
        <div class="card-header bg-white border-0 py-3 px-3.5 d-flex justify-content-between align-items-center">
          <h6 class="font-weight-bold text-dark mb-0" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
            <i class="fas fa-calendar-alt text-info mr-2"></i> <?php echo htmlspecialchars(t('home.chart_yearly_compare', 'ກາຟປຽບທຽບຍອດຂາຍປະຈຳປີ')); ?>
          </h6>
        </div>
        <div class="card-body p-3">
          <div style="position: relative; height: 320px; width: 100%;">
            <canvas id="yearlySalesChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Row 3: 2 Secondary Charts (Profit Margin % & Top 10 Best Sellers) -->
  <div class="row mb-4">
    <!-- Chart 3: Monthly Profit Margin % -->
    <div class="col-lg-5 mb-4 mb-lg-0">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 14px; background: #ffffff;">
        <div class="card-header bg-white border-0 py-3 px-3.5 d-flex justify-content-between align-items-center">
          <h6 class="font-weight-bold text-dark mb-0" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
            <i class="fas fa-percentage text-warning mr-2"></i> <span><?php echo htmlspecialchars(t('home.chart_profit_margin', 'ກາຟອັດຕາກຳໄລ %')); ?></span> (<?php echo htmlspecialchars($chart_period_text); ?>)
          </h6>
        </div>
        <div class="card-body p-3">
          <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="profitMarginChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Chart 4: Top 10 Best Sellers -->
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 14px; background: #ffffff;">
        <div class="card-header bg-white border-0 py-3 px-3.5 d-flex justify-content-between align-items-center">
          <h6 class="font-weight-bold text-dark mb-0" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
            <i class="fas fa-fire text-danger mr-2"></i> <?php echo htmlspecialchars(t('home.chart_top10', 'ກາຟ 10 ລາຍການສິນຄ້າທີ່ຂາຍດີ')); ?>
          </h6>
          <span class="badge badge-light border text-danger font-weight-bold px-2.5 py-1">Top 10</span>
        </div>
        <div class="card-body p-3">
          <div style="position: relative; min-height: 360px; height: 360px; width: 100%;">
            <canvas id="topSellersChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Chart.js Library -->
<script src="../plugins/chart.js/Chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script>
var I18N_HOME = <?php echo tjson([
    'home.legend_sales' => 'ຍອດຂາຍ',
    'home.legend_cost' => 'ຕົ້ນທຶນ',
    'home.legend_profit' => 'ກຳໄລ',
    'home.legend_margin' => 'ອັດຕາກຳໄລ (%)',
    'home.legend_qty_sold' => 'ຈຳນວນຂາຍ',
    'home.no_sales_data' => 'ບໍ່ມີຂໍ້ມູນການຂາຍ',
    'home.year_label' => 'ປີ %d',
    'home.month_1' => 'ມັງກອນ', 'home.month_2' => 'ກຸມພາ', 'home.month_3' => 'ມີນາ',
    'home.month_4' => 'ເມສາ', 'home.month_5' => 'ພຶດສະພາ', 'home.month_6' => 'ມິຖຸນາ',
    'home.month_7' => 'ກໍລະກົດ', 'home.month_8' => 'ສິງຫາ', 'home.month_9' => 'ກັນຍາ',
    'home.month_10' => 'ຕຸລາ', 'home.month_11' => 'ພະຈິກ', 'home.month_12' => 'ທັນວາ',
]); ?>;

$(document).ready(function() {
  // CountUp Number Animation for Metrics Cards
  $('.counter-num').each(function() {
    var $el = $(this);
    var target = parseFloat($el.attr('data-target') || 0);
    var decimals = parseInt($el.attr('data-decimals') || 0, 10);
    if (isNaN(target)) target = 0;
    
    var startTime = null;
    var duration = 1200;
    
    function step(timestamp) {
      if (!startTime) startTime = timestamp;
      var progress = Math.min((timestamp - startTime) / duration, 1);
      var ease = 1 - Math.pow(1 - progress, 3);
      var current = ease * target;
      
      if (decimals > 0) {
        $el.text(current.toFixed(decimals));
      } else {
        $el.text(Math.floor(current).toLocaleString());
      }
      
      if (progress < 1) {
        requestAnimationFrame(step);
      } else {
        if (decimals > 0) {
          $el.text(target.toFixed(decimals));
        } else {
          $el.text(Math.round(target).toLocaleString());
        }
      }
    }
    requestAnimationFrame(step);
  });

  if (typeof Chart === 'undefined') {
    return;
  }

  try {
    Chart.defaults.font = Chart.defaults.font || {};
    Chart.defaults.font.family = "'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif";
  } catch(e) {}

  // Function to format long labels into multi-line arrays
  function formatChartLabel(str, maxLen) {
    maxLen = maxLen || 16;
    if (!str) return '';
    if (typeof str !== 'string') return str;
    if (str.length <= maxLen) return str;
    
    if (str.indexOf(' ') !== -1) {
      var words = str.split(' ');
      var lines = [];
      var currentLine = '';
      for (var i = 0; i < words.length; i++) {
        var w = words[i];
        if ((currentLine ? currentLine + ' ' + w : w).length <= maxLen) {
          currentLine = currentLine ? currentLine + ' ' + w : w;
        } else {
          if (currentLine) lines.push(currentLine);
          currentLine = w;
        }
      }
      if (currentLine) lines.push(currentLine);
      if (lines.length > 1) return lines;
    }
    
    var chunks = [];
    for (var i = 0; i < str.length; i += maxLen) {
      chunks.push(str.substring(i, i + maxLen));
    }
    return chunks;
  }

  // Multi-currency: append the active symbol in brackets to chart legends.
  var CUR_SYM = <?php echo json_encode($__hsym, JSON_UNESCAPED_UNICODE); ?>;
  var CUR_DEC = <?php echo $__hdec; ?>;
  function curLabel(name){ return name + ' (' + CUR_SYM + ')'; }
  function curAxis(v){ return Number(v).toLocaleString('en-US',{maximumFractionDigits:CUR_DEC}) + ' ' + CUR_SYM; }
  // Data variables from PHP
  // Revenue / margin charts follow the selected range: 'hour' | 'day' | 'month' buckets
  var CHART_MODE    = <?php echo json_encode($chart_mode); ?>;
  var chartKeys     = <?php echo json_encode(array_values($chart_keys)); ?>;
  var CHART_MULTI_YEAR = <?php echo json_encode($chart_mode === 'month' && substr($chartFrom, 0, 4) !== substr($chartTo, 0, 4)); ?>;
  function chartBucketLabel(k) {
    if (CHART_MODE === 'hour') return (k < 10 ? '0' : '') + k + ':00';
    if (CHART_MODE === 'day') return k.slice(8, 10) + '/' + k.slice(5, 7);
    var name = I18N_HOME['home.month_' + parseInt(k.slice(5, 7), 10)] || k.slice(5, 7);
    return CHART_MULTI_YEAR ? name + ' ' + k.slice(0, 4) : name;
  }
  var monthLabels   = chartKeys.map(chartBucketLabel);
  var monthlySales  = <?php echo json_encode(array_map($__hcvt, array_values($chart_sales))); ?>;
  var monthlyCost   = <?php echo json_encode(array_map($__hcvt, array_values($chart_cost))); ?>;
  var monthlyProfit = <?php echo json_encode(array_map($__hcvt, $chart_profit)); ?>;
  var monthlyMargin = <?php echo json_encode($chart_margin); ?>;
  var hasProductCostData = <?php echo json_encode($has_product_cost_data); ?>;

  var yearlyLabels  = <?php echo json_encode($yearly_labels); ?>;
  var yearlySales   = <?php echo json_encode(array_map($__hcvt, $yearly_sales)); ?>;
  // Raw years behind yearlyLabels, kept so refreshChartLangs() can rebuild the "ປີ %d" labels
  // in the new language without re-fetching data from the server.
  var yearlyYears   = <?php echo json_encode(array_keys($yearly_sales_map)); ?>;

  var top10Names    = <?php echo json_encode($top10_names); ?>;
  var top10Qty      = <?php echo json_encode($top10_qty); ?>;

  if (!yearlyLabels.length) {
    yearlyLabels = [<?php echo json_encode(sprintf(t('home.year_label', 'ປີ %d'), $selected_year), JSON_UNESCAPED_UNICODE); ?>];
    yearlySales  = [0];
    yearlyYears  = [<?php echo (int)$selected_year; ?>];
  }

  function fmtYearLabel(year) {
    var pattern = I18N_HOME['home.year_label'] || 'ປີ %d';
    return pattern.replace('%d', year);
  }

  if (!top10Names.length) {
    top10Names = [I18N_HOME['home.no_sales_data']];
    top10Qty   = [0];
  }

  // Chart instances kept so their labels can be re-translated on an instant language switch
  var monthlyRevenueChartObj = null;
  var profitMarginChartObj = null;
  var topSellersChartObj = null;
  var yearlySalesChartObj = null;

  // ===== 1. Monthly Revenue & Profit Chart =====
  var ctxMonthly = document.getElementById('monthlyRevenueChart');
  if (ctxMonthly) {
    monthlyRevenueChartObj = new Chart(ctxMonthly, {
      type: 'bar',
      data: {
        labels: monthLabels,
        datasets: (function() {
          // The "ຕົ້ນທຶນ" series is only meaningful once real product cost data exists —
          // otherwise every bar would just be a flat 0, which reads as "cost is zero" rather
          // than "cost is unknown". Tagged with _key so refreshChartLangs() can find it by
          // name after a language switch instead of relying on a fixed array index.
          var mds = [
            { _key: 'sales', label: curLabel(I18N_HOME['home.legend_sales']), backgroundColor: '#2563eb', borderColor: '#1d4ed8', data: monthlySales, borderRadius: 6 }
          ];
          if (hasProductCostData) {
            mds.push({ _key: 'cost', label: curLabel(I18N_HOME['home.legend_cost']), backgroundColor: '#38bdf8', borderColor: '#0284c7', data: monthlyCost, borderRadius: 6 });
          }
          mds.push({ _key: 'profit', label: curLabel(I18N_HOME['home.legend_profit']), backgroundColor: '#22c55e', borderColor: '#16a34a', data: monthlyProfit, borderRadius: 6 });
          return mds;
        })()
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'top' },
          tooltip: { callbacks: { label: function(ctx){ return ctx.dataset.label + ': ' + curAxis(ctx.parsed.y); } } }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(val) {
                if (<?php echo ($__hdc['code']==='KIP')?'true':'false'; ?>) {
                  if (val >= 1000000) return (val / 1000000).toLocaleString() + 'M';
                  if (val >= 1000) return (val / 1000).toLocaleString() + 'k';
                  return val;
                }
                return curAxis(val);
              }
            }
          }
        }
      }
    });
  }

  // ===== 2. Yearly Sales Comparison Chart =====
  var ctxYearly = document.getElementById('yearlySalesChart');
  if (ctxYearly) {
    yearlySalesChartObj = new Chart(ctxYearly, {
      type: 'bar',
      data: {
        labels: yearlyLabels,
        datasets: [{
          label: curLabel(I18N_HOME['home.legend_sales']),
          backgroundColor: ['#2563eb', '#0284c7', '#16a34a', '#d97706', '#8b5cf6'],
          data: yearlySales,
          borderRadius: 8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(val) { return curAxis(val); }
            }
          }
        }
      }
    });
  }

  // ===== 3. Profit Margin % Chart =====
  var ctxMargin = document.getElementById('profitMarginChart');
  if (ctxMargin) {
    profitMarginChartObj = new Chart(ctxMargin, {
      type: 'line',
      data: {
        labels: monthLabels,
        datasets: [{
          label: I18N_HOME['home.legend_margin'],
          backgroundColor: 'rgba(217, 119, 6, 0.15)',
          borderColor: '#d97706',
          pointBackgroundColor: '#b45309',
          borderWidth: 3,
          data: monthlyMargin,
          fill: true,
          tension: 0.3
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: {
            beginAtZero: true,
            suggestedMax: 100,
            ticks: {
              callback: function(val) { return val + '%'; }
            }
          }
        }
      }
    });
  }

  // ===== 4. Top 10 Best Sellers Chart =====
  var ctxTop = document.getElementById('topSellersChart');
  if (ctxTop) {
    var formattedTop10Names = top10Names.map(function(name) {
      return formatChartLabel(name, 16);
    });

    topSellersChartObj = new Chart(ctxTop, {
      type: 'bar',
      data: {
        labels: formattedTop10Names,
        datasets: [{
          label: I18N_HOME['home.legend_qty_sold'],
          backgroundColor: '#dc2626',
          borderColor: '#b91c1c',
          data: top10Qty,
          borderRadius: 6
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        layout: {
          padding: {
            left: 5,
            right: 15,
            top: 5,
            bottom: 5
          }
        },
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              title: function(tooltipItems) {
                if (!tooltipItems.length) return '';
                var idx = tooltipItems[0].dataIndex;
                return top10Names[idx] || '';
              }
            }
          }
        },
        scales: {
          x: {
            beginAtZero: true,
            ticks: {
              font: {
                family: "'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif"
              }
            }
          },
          y: {
            ticks: {
              autoSkip: false,
              font: {
                family: "'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif",
                size: 11
              }
            }
          }
        }
      }
    });
  }

  // The dashboard language switch is instant (no reload), so the chart <canvas> bitmaps keep
  // their build-time labels. The shell updates I18N_HOME in place then fires 'pos:langchange';
  // rebuild the affected charts' month-axis and legend labels from the refreshed dictionary.
  function refreshChartLangs() {
    monthLabels = chartKeys.map(chartBucketLabel);

    if (monthlyRevenueChartObj) {
      monthlyRevenueChartObj.data.labels = monthLabels;
      var mdsByKey = { sales: 'home.legend_sales', cost: 'home.legend_cost', profit: 'home.legend_profit' };
      monthlyRevenueChartObj.data.datasets.forEach(function(ds) {
        if (ds._key && mdsByKey[ds._key]) ds.label = curLabel(I18N_HOME[mdsByKey[ds._key]]);
      });
      monthlyRevenueChartObj.update();
    }
    if (profitMarginChartObj) {
      profitMarginChartObj.data.labels = monthLabels;
      if (profitMarginChartObj.data.datasets[0]) {
        profitMarginChartObj.data.datasets[0].label = I18N_HOME['home.legend_margin'];
      }
      profitMarginChartObj.update();
    }
    if (topSellersChartObj && topSellersChartObj.data.datasets[0]) {
      // Only the dataset label needs updating — the tooltip title callback reads top10Names
      // directly (product names, not translated) and the axis ticks use formattedTop10Names.
      topSellersChartObj.data.datasets[0].label = I18N_HOME['home.legend_qty_sold'];
      topSellersChartObj.update();
    }
    if (yearlySalesChartObj) {
      yearlyLabels = yearlyYears.map(fmtYearLabel);
      yearlySalesChartObj.data.labels = yearlyLabels;
      if (yearlySalesChartObj.data.datasets[0]) {
        yearlySalesChartObj.data.datasets[0].label = curLabel(I18N_HOME['home.legend_sales']);
      }
      yearlySalesChartObj.update();
    }
  }
  window.addEventListener('pos:langchange', refreshChartLangs);

});
</script>
