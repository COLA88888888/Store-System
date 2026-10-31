<?php
/**
 * ສ່ວນ: ຂໍ້ມູນ: ກຣາຟ
 * ຂໍ້ມູນກຣາຟລາຍຮັບ/ກຳໄລ (ລາຍຊົ່ວໂມງ/ວັນ/ເດືອນ ຕາມຊ່ວງວັນທີ), ຍອດຂາຍລາຍປີ ແລະ 10 ສິນຄ້າຂາຍດີ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ home/home.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

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
        FROM st_tbsale_save s
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
        FROM st_tbsale_save_detail d
        INNER JOIN st_tbsale_save s ON d.save_bill = s.sale_save_bill
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
    $chart_title_key = 'home.chart_revenue_hourly'; $chart_title_def = tl('ກາຟລາຍຮັບລາຍຊົ່ວໂມງ');
    $chart_period_text = t('home.lbl_date', 'ວັນທີ') . ' ' . date('d/m/Y', strtotime($chartFrom));
    $chart_badge_key = 'home.badge_hours'; $chart_badge_def = ('24 ' . tl('ຊົ່ວໂມງ')); $chart_badge_n = null;
} elseif ($chart_mode === 'day') {
    $chart_title_key = 'home.chart_revenue_daily'; $chart_title_def = tl('ກາຟລາຍຮັບລາຍວັນ');
    $chart_period_text = t('home.lbl_date', 'ວັນທີ') . ' ' . date('d/m/Y', strtotime($chartFrom)) . ' – ' . t('home.lbl_date', 'ວັນທີ') . ' ' . date('d/m/Y', strtotime($chartTo));
    $chart_badge_key = 'home.badge_days'; $chart_badge_def = ('%s ' . tl('ວັນ')); $chart_badge_n = count($chart_keys);
} else {
    $chart_title_key = 'home.chart_monthly_revenue'; $chart_title_def = tl('ກາຟລາຍຮັບປະຈຳເດືອນ');
    $sameYear = substr($chartFrom, 0, 4) === substr($chartTo, 0, 4);
    $chart_period_text = ($sameYear && substr($chartFrom, 5) === '01-01' && substr($chartTo, 5) === '12-31')
        ? sprintf(t('home.year_label', 'ປີ %d'), (int)substr($chartFrom, 0, 4))
        : t('home.lbl_month', 'ເດືອນ') . ' ' . date('m/Y', strtotime($chartFrom)) . ' – ' . t('home.lbl_month', 'ເດືອນ') . ' ' . date('m/Y', strtotime($chartTo));
    $chart_badge_key = 'home.badge_months'; $chart_badge_def = ('%s ' . tl('ເດືອນ')); $chart_badge_n = count($chart_keys);
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
        FROM st_tbsale_save
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
            FROM st_tbsale_save_detail d
            INNER JOIN st_tbsale_save s ON d.save_bill = s.sale_save_bill
            LEFT JOIN st_products p ON d.save_proid = p.product_id
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

