<?php
/**
 * ສ່ວນ: ຂໍ້ມູນ: ຕົວກັ່ນຕອງ ແລະ ຕົວເລກ
 * ຮັບວັນທີ/ສາຂາ/ສະກຸນເງິນ, ສ້າງເງື່ອນໄຂ SQL (ບິນຕິດໜີ້ທີ່ຍັງບໍ່ຈ່າຍນັບແຕ່ຍອດຂາຍ/ບິນ/ລາຍການ),
 * ຍອດຊຳລະໜີ້ໃນຊ່ວງວັນທີ, ຍອດຕາມທະນາຄານ ແລະ ຂໍ້ມູນກຣາຟ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/reports/daily_report.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

// Date Range & Branch Filter Inputs (Default: 1st of current month to Today)
$from_date = trim($_GET['from_date'] ?? date('Y-m-01'));
$to_date   = trim($_GET['to_date'] ?? date('Y-m-d'));
$filter_store_id = isset($_GET['store_id']) && $_GET['store_id'] !== '' ? intval($_GET['store_id']) : intval($_SESSION['store_id'] ?? 1);
if (!canViewAllBranches($pdo)) {
    $filter_store_id = intval($_SESSION['store_id'] ?? 1);
}

// Fetch all branches for executive/manager dropdown filter
$branchesList = [];
try {
    $branchesList = $pdo->query("SELECT * FROM st_tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Check if tbsale_save has store_id / paid_currency columns
$hasStoreIdCol  = false;
$hasCurrencyCol = false;
try {
    $saleCols = $pdo->query("SHOW COLUMNS FROM st_tbsale_save")->fetchAll(PDO::FETCH_COLUMN);
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

// A credit bill not yet fully paid (ຕິດໜີ້ ຍັງບໍ່ຈ່າຍ / ຍັງບໍ່ຄົບ) only counts toward the sales total,
// bill count and item counts — never toward the money / discount cards.
$openDebtSql = "EXISTS (SELECT 1 FROM st_customer_debts cd WHERE cd.invoice_number = s.sale_save_bill AND cd.status IN ('unpaid','partial'))";

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
$daily_products_count  = 0;
$currency_breakdown    = [];

$hasCashCol = false;
$hasQrCol   = false;
try {
    $cols = $pdo->query("SHOW COLUMNS FROM st_tbsale_save")->fetchAll(PDO::FETCH_COLUMN);
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
            COALESCE(SUM(CASE WHEN {$openDebtSql} THEN 0 ELSE s.sale_discount_bill END), 0) AS total_bill_discounts,
            COALESCE(SUM(s.sale_barlance), 0) AS total_net_sales,
            COALESCE(SUM(CASE
                {$cashSql}
                WHEN s.type_pay = 'ຕິດໜີ້' THEN 0
                WHEN (s.type_pay LIKE '%ເງິນສົດ%' AND s.type_pay NOT LIKE '%ໂອນ%' AND s.type_pay NOT LIKE '%QR%' AND (s.bank_account_id IS NULL OR s.bank_account_id = 0)) THEN s.sale_barlance
                WHEN s.type_pay LIKE '%ເງິນສົດ%' THEN GREATEST(0, s.sale_pay - s.sale_return)
                WHEN (s.type_pay NOT LIKE '%ໂອນ%' AND s.type_pay NOT LIKE '%QR%' AND (s.bank_account_id IS NULL OR s.bank_account_id = 0)) THEN s.sale_barlance
                ELSE 0 
            END), 0) AS total_cash_received,
            COALESCE(SUM(CASE
                {$qrSql}
                WHEN s.type_pay = 'ຕິດໜີ້' THEN 0
                WHEN (s.type_pay LIKE '%ໂອນ%' OR s.type_pay LIKE '%QR%' OR s.bank_account_id > 0) THEN s.sale_barlance
                WHEN s.sale_transfer > 0 THEN s.sale_transfer
                ELSE 0 
            END), 0) AS total_qr_received,
            COALESCE(SUM(GREATEST(0, (s.sale_pay - s.sale_return) - s.sale_barlance)), 0) AS total_tips_sum
        FROM st_tbsale_save s
        WHERE {$whereClause}
    ");
    $stmtDaily->execute($params);
    $dailyStats = $stmtDaily->fetch(PDO::FETCH_ASSOC);

    // Bank Transfer Revenue Breakdown — every bank account of this branch (plus shared ones),
    // with the same per-bank totals as the Bank page (getBankTransferTotals in config/db.php),
    // so the two pages always agree. Accounts with no transfers show 0.
    $bank_daily_breakdown = [];
    $bank_breakdown_total = 0;
    $bank_accounts_map = [];
    try {
        $bListStmt = $pdo->prepare("SELECT * FROM st_bank_accounts WHERE is_active = 1 AND (store_id = ? OR store_id = 0) ORDER BY id ASC");
        $bListStmt->execute([getActiveStoreId($pdo)]);
        foreach ($bListStmt->fetchAll(PDO::FETCH_ASSOC) as $b) {
            $bank_accounts_map[$b['id']] = $b;
        }
        $bankTotals = getBankTransferTotals($pdo, $from_date !== '' ? $from_date : '2000-01-01', $to_date !== '' ? $to_date : date('Y-m-d'), $filter_store_id);
        foreach ($bank_accounts_map as $bId => $bAcc) {
            $t = $bankTotals[$bId] ?? ['total_tx' => 0, 'total_received' => 0];
            $bank_daily_breakdown[] = [
                'bank_acc_id'    => $bId,
                'bank_label'     => $bAcc['bank_name'],
                'total_tx'       => $t['total_tx'],
                'total_received' => $t['total_received'],
            ];
            $bank_breakdown_total += $t['total_received'];
        }
    } catch (Exception $e) {}

    $hasItemDiscCol = false;
    try {
        $colsD = $pdo->query("SHOW COLUMNS FROM st_tbsale_save_detail")->fetchAll(PDO::FETCH_COLUMN);
        $hasItemDiscCol = in_array('save_discount_item', $colsD) ? 'save_discount_item' : (in_array('save_discount', $colsD) ? 'save_discount' : '');
    } catch (Exception $e) {}

    if ($hasItemDiscCol) {
        $stmtItemDisc = $pdo->prepare("
            SELECT COALESCE(SUM(d.{$hasItemDiscCol}), 0) AS total_item_disc
            FROM st_tbsale_save_detail d
            INNER JOIN st_tbsale_save s ON d.save_bill = s.sale_save_bill
            WHERE {$whereClause} AND NOT ({$openDebtSql})
        ");
        $stmtItemDisc->execute($params);
        $itemDiscRes = $stmtItemDisc->fetch(PDO::FETCH_ASSOC);
        $daily_item_discounts = (float)($itemDiscRes['total_item_disc'] ?? 0);
    } else {
        $daily_item_discounts = 0;
    }

    $daily_bills_count     = (int)($dailyStats['total_bills'] ?? 0);
    $daily_items_qty       = (int)($dailyStats['total_items_qty'] ?? 0);

    // ລາຍການສິນຄ້າ: how many different products were sold in the range
    $daily_products_count = 0;
    try {
        $stmtProd = $pdo->prepare("SELECT COUNT(DISTINCT d.save_proid) FROM st_tbsale_save_detail d INNER JOIN st_tbsale_save s ON d.save_bill = s.sale_save_bill WHERE {$whereClause}");
        $stmtProd->execute($params);
        $daily_products_count = (int)$stmtProd->fetchColumn();
    } catch (Throwable $e) {}
    $daily_gross_sales     = (float)($dailyStats['total_gross_sales'] ?? 0);
    $daily_bill_discounts  = (float)($dailyStats['total_bill_discounts'] ?? 0);
    $daily_total_discounts = $daily_item_discounts + $daily_bill_discounts;
    $daily_net_sales       = (float)($dailyStats['total_net_sales'] ?? 0);
    $daily_cash_payments   = (float)($dailyStats['total_cash_received'] ?? 0);
    $daily_qr_payments     = (float)($dailyStats['total_qr_received'] ?? 0);

    // ລູກຄ້າຕິດໜີ້: repayments received in this date range (by the day the money came in, not the
    // day of the sale) are real income too — added to ເງິນສົດ / ເງິນໂອນ by channel. Debts are
    // always in KIP, so they only count when no other tender currency is being filtered.
    $daily_debt_repaid_cash = 0;
    $daily_debt_repaid_qr   = 0;
    if ($currency_filter === '' || $currency_filter === 'KIP') {
        try {
            $rpWhere  = ['1=1'];
            $rpParams = [];
            if (!empty($from_date)) { $rpWhere[] = 'DATE(p.paid_at) >= ?'; $rpParams[] = $from_date; }
            if (!empty($to_date))   { $rpWhere[] = 'DATE(p.paid_at) <= ?'; $rpParams[] = $to_date; }
            if ($filter_store_id > 0) { $rpWhere[] = 'p.store_id = ?'; $rpParams[] = $filter_store_id; }
            $rpStmt = $pdo->prepare("
                SELECT COALESCE(SUM(CASE WHEN p.bank_account_id IS NULL AND p.payment_method = 'ເງິນສົດ' THEN p.amount ELSE 0 END), 0) AS cash,
                       COALESCE(SUM(CASE WHEN p.bank_account_id IS NULL AND p.payment_method = 'ເງິນສົດ' THEN 0 ELSE p.amount END), 0) AS qr
                FROM st_customer_debt_payments p
                WHERE " . implode(' AND ', $rpWhere));
            $rpStmt->execute($rpParams);
            $rp = $rpStmt->fetch(PDO::FETCH_ASSOC);
            $daily_debt_repaid_cash = (float)($rp['cash'] ?? 0);
            $daily_debt_repaid_qr   = (float)($rp['qr'] ?? 0);
        } catch (Throwable $e) {}
    }
    $daily_cash_payments += $daily_debt_repaid_cash;
    $daily_qr_payments   += $daily_debt_repaid_qr;
    $daily_tips_sum        = (float)($dailyStats['total_tips_sum'] ?? 0);

    // Promotion & Free Gift Statistics
    $daily_promo_discounts = $daily_item_discounts;
    $daily_promo_gifts_count = 0;
    try {
        $stmtGifts = $pdo->prepare("
            SELECT COALESCE(SUM(d.save_qty), 0) AS total_gifts
            FROM st_tbsale_save_detail d
            INNER JOIN st_tbsale_save s ON d.save_bill = s.sale_save_bill
            WHERE {$whereClause} AND NOT ({$openDebtSql}) AND (d.save_name LIKE '%(ແຖມ)%' OR d.save_name LIKE '%🎁%' OR d.save_price = 0)
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
        FROM st_tbsale_save s
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
            FROM st_tbsale_save_detail d
            INNER JOIN st_tbsale_save s ON d.save_bill = s.sale_save_bill
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
    $curCols = $pdo->query("SHOW COLUMNS FROM st_tbsale_save")->fetchAll(PDO::FETCH_COLUMN);
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
            FROM st_tbsale_save s
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
        FROM st_tbsale_save s
        WHERE {$whereClause}
        ORDER BY DATE(s.sale_date) DESC, s.sale_time DESC, s.Id DESC
    ");
    $stmtSalesList->execute($params);
    $todaySalesRows = $stmtSalesList->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) { $perDayRows = []; $dayItemDiscMap = []; $todaySalesRows = []; }

