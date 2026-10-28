<?php
/**
 * ສ່ວນ: ຂໍ້ມູນ: ຕົວກັ່ນຕອງ ແລະ ຕົວເລກຫຼັກ
 * ລາຍການປີ, ຮັບຄ່າສາຂາ/ປີ/ວັນທີ, ປຸ່ມຊ່ວງເວລາ, ສ້າງເງື່ອນໄຂ SQL ແລະ ຄິດຍອດຂາຍ, ຕົ້ນທຶນ, ກຳໄລ, %
 * ກຳໄລ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ home/home.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

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
        FROM st_tbsale_save 
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
    $stores_list = $pdo->query("SELECT store_id, store_name, is_main FROM st_tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$selected_store = isset($_GET['store_id']) && is_numeric($_GET['store_id']) ? (int)$_GET['store_id'] : intval($_SESSION['store_id'] ?? 1);
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
        FROM st_tbsale_save s
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

    $stmtCost = $pdo->prepare("SELECT COALESCE(SUM(i.total_cost), 0) FROM st_imports i WHERE {$importWhereClause}");
    $stmtCost->execute($importParams);
    $total_cost = floatval($stmtCost->fetchColumn() ?? 0);

    // "ຕົ້ນທຶນນຳເຂົ້າສິນຄ້າທັງໝົດ" card is only meaningful once at least one product actually has a
    // ລາຄາຊື້ (bprice) on file — otherwise it's just a shop that never records cost, and
    // showing "0 ₭" would look like a real (zero) cost rather than "unknown".
    if ($selected_store > 0) {
        $stmtHasCost = $pdo->prepare("SELECT 1 FROM st_products WHERE store_id = :sid AND bprice > 0 LIMIT 1");
        $stmtHasCost->execute([':sid' => $selected_store]);
    } else {
        $stmtHasCost = $pdo->query("SELECT 1 FROM st_products WHERE bprice > 0 LIMIT 1");
    }
    $has_product_cost_data = (bool)$stmtHasCost->fetchColumn();

    // ຕົ້ນທຶນການຊື້ສິນຄ້າ (COGS) = ຕົ້ນທຶນຂອງລາຍການສິນຄ້າທີ່ຂາຍອອກຈິງ ຈາກ tbsale_save_detail
    // (save_qty ຄູນ cost_price ຕໍ່ຫົວໜ່ວຍທີ່ຂາຍ — ຄືກັບທີ່ພ້ອມຄິດຕອນ checkout ໃນ pos_backend.php)
    try {
        $stmtCogs = $pdo->prepare("
            SELECT COALESCE(SUM(d.save_qty * d.cost_price), 0)
            FROM st_tbsale_save_detail d
            INNER JOIN st_tbsale_save s ON d.save_bill = s.sale_save_bill
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

