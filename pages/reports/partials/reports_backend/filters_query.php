<?php
/**
 * ສ່ວນ: ຕົວກັ່ນຕອງ ແລະ ເງື່ອນໄຂ SQL
 * ຊື່/ໄອຄອນຂອງແຕ່ລະລາຍງານ, ກວດຄໍລຳ, ບັນຊີທະນາຄານ, ເງື່ອນໄຂວັນທີ/ຄົ້ນຫາ/ທະນາຄານ/ສະກຸນເງິນ/ສາຂາ ແລະ
 * ຕົວແປເລີ່ມຕົ້ນ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/reports/partials/reports_backend.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

// Titles and icons mapping
$report_meta = [
    'daily' => [
        'title' => 'ລາຍງານປະຈຳວັນ',
        'icon' => 'fas fa-calendar-day text-info'
    ],
    'all_sales' => [
        'title' => ($view_mode === 'item') ? 'ລາຍການຂາຍສິນຄ້າ' : 'ລາຍງານການຂາຍທັງໝົດ',
        'icon' => 'fas fa-file-invoice-dollar text-success'
    ],
    'best_seller' => [
        'title' => 'ລາຍງານສິນຄ້າຂາຍດີ',
        'icon' => 'fas fa-fire text-danger'
    ],
    'profit_cost' => [
        'title' => 'ລາຍງານກຳໄລ-ຕົ້ນທຶນ',
        'icon' => 'fas fa-chart-line text-warning'
    ],
    'financial' => [
        'title' => 'ລາຍງານການເງິນ',
        'icon' => 'fas fa-wallet text-info'
    ],
    'category' => [
        'title' => 'ລາຍງານຕາມ/ປະເພດສິນຄ້າ',
        'icon' => 'fas fa-layer-group text-primary'
    ],
    'delete_bills' => [
        'title' => 'ປະຫວັດການລົບບິນຂາຍ',
        'icon' => 'fas fa-trash-alt text-danger'
    ]
];

$current_meta = $report_meta[$type] ?? $report_meta['all_sales'];

// Inspect columns in tbsale_save to handle cash, qr, tip, and currency columns dynamically
$hasCashCol     = false;
$hasQrCol       = false;
$hasTipCol      = false;
$hasCurrencyCol = false;
try {
    $cols = $pdo->query("SHOW COLUMNS FROM st_tbsale_save")->fetchAll(PDO::FETCH_COLUMN);
    $hasCashCol     = in_array('cash_received', $cols);
    $hasQrCol       = in_array('qr_received', $cols);
    $hasTipCol      = in_array('tip_amount', $cols) || in_array('tip', $cols);
    $hasCurrencyCol = in_array('paid_currency', $cols);
} catch (Exception $e) {}

// Build SQL Query & Fetch Data
$where  = [];
$params = [];

if ($type === 'delete_bills') {
    $where[] = "s.sale_status = 'CANCEL'";
} else {
    $where[] = "(s.sale_status IS NULL OR s.sale_status != 'CANCEL')";
}
$params = [];

$bank_filter = trim($_GET['bank_filter'] ?? '');

// Bank accounts for the filter & breakdown — scoped to the branch this report is actually
// viewing (honours ?switch_store_id for admin/main-branch) plus any bank shared across every
// branch (store_id = 0), so the dropdown never mixes in ANOTHER branch's own bank accounts.
$bank_accounts = [];
$bank_accounts_map = [];
try {
    $b_stmt = $pdo->prepare("SELECT * FROM st_bank_accounts WHERE store_id = ? OR store_id = 0 ORDER BY id ASC");
    $b_stmt->execute([getActiveStoreId($pdo)]);
    if ($b_stmt) {
        $bank_accounts = $b_stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($bank_accounts as $bAcc) {
            $bank_accounts_map[$bAcc['id']] = $bAcc;
        }
    }
} catch (Throwable $e) {}

// Separate, UNSCOPED lookup (every branch's bank_accounts, by id) — a bill's own
// bank_account_id is a concrete reference to whichever bank account was actually picked at
// checkout time, and must still resolve to its real name/logo for display even when that
// account's store_id doesn't match the branch this report is currently viewing (e.g. a bank
// account created before branches were split out, or shared/misfiled under another store_id).
// Only $bank_accounts_map above (branch-scoped) feeds the filter dropdown & breakdown, so this
// doesn't reopen the cross-branch mixing that was fixed there.
$all_bank_accounts_map = [];
try {
    foreach ($pdo->query("SELECT * FROM st_bank_accounts")->fetchAll(PDO::FETCH_ASSOC) as $bAcc) {
        $all_bank_accounts_map[$bAcc['id']] = $bAcc;
    }
} catch (Throwable $e) {}

// Always apply date range filter
if (!empty($from_date)) {
    $where[] = "DATE(s.sale_date) >= :from_date";
    $params[':from_date'] = $from_date;
}

if (!empty($to_date)) {
    $where[] = "DATE(s.sale_date) <= :to_date";
    $params[':to_date'] = $to_date;
}

if (!empty($search)) {
    $where[] = "(s.sale_save_bill LIKE :search OR s.user_receive LIKE :search OR s.customer_name LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($bank_filter)) {
    if ($bank_filter === 'cash') {
        $where[] = "(s.type_pay LIKE '%ເງິນສົດ%' AND (s.type_pay NOT LIKE '%ໂອນ%' AND s.type_pay NOT LIKE '%QR%'))";
    } elseif ($bank_filter === 'transfer') {
        $where[] = "(s.type_pay LIKE '%ໂອນ%' OR s.type_pay LIKE '%QR%' OR s.bank_account_id > 0)";
    } elseif (is_numeric($bank_filter) && intval($bank_filter) > 0) {
        $bId = intval($bank_filter);
        $bAcc = $bank_accounts_map[$bId] ?? [];
        $bName = trim($bAcc['bank_name'] ?? '');
        $bCode = trim($bAcc['bank_code'] ?? '');
        $shortCode = strtok($bName, ' '); // e.g. "BCEL" from "BCEL One"

        $bankConds = ["s.bank_account_id = :b_id"];
        $params[':b_id'] = $bId;

        if (!empty($bName)) {
            $bankConds[] = "s.bank_name LIKE :b_name";
            $bankConds[] = "s.type_pay LIKE :b_name2";
            $params[':b_name'] = '%' . $bName . '%';
            $params[':b_name2'] = '%' . $bName . '%';
        }

        if (!empty($shortCode) && strlen($shortCode) >= 2 && $shortCode !== $bName) {
            $bankConds[] = "s.bank_name LIKE :b_code";
            $bankConds[] = "s.type_pay LIKE :b_code2";
            $params[':b_code'] = '%' . $shortCode . '%';
            $params[':b_code2'] = '%' . $shortCode . '%';
        }

        // Include unassigned transfer sales under the primary bank account (e.g. BCEL One)
        $firstBankId = !empty($bank_accounts) ? intval($bank_accounts[0]['id']) : 1;
        if ($bId === $firstBankId) {
            $bankConds[] = "( (s.bank_account_id IS NULL OR s.bank_account_id = 0) AND (s.bank_name IS NULL OR s.bank_name = '') AND (s.type_pay LIKE '%ໂອນ%' OR s.type_pay LIKE '%QR%') )";
        }

        $where[] = "(" . implode(" OR ", $bankConds) . ")";
    }
}

// Currency Filter — which currency the customer actually tendered on the bill
// (paid_currency, saved per sale at checkout; 'KIP' = base currency, no conversion).
$currency_filter = strtoupper(trim($_GET['currency_filter'] ?? ''));
if ($currency_filter !== '' && $hasCurrencyCol && isset(getCurrencyMeta()[$currency_filter])) {
    $where[] = "COALESCE(NULLIF(s.paid_currency, ''), 'KIP') = :currency_filter";
    $params[':currency_filter'] = $currency_filter;

    // Reports normally show money in the global display-currency setting; once the
    // user filters down to one tendered currency, every amount on the page should
    // switch to match it instead (KPI cards, table cells, totals) — not stay stuck
    // showing ₭ while the filter says THB.
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

// Branch Store Filter
$userStoreId = intval($_SESSION['store_id'] ?? 1);
$isAdmin = ($_SESSION['status'] ?? '') === 'ຜູ້ບໍລິຫານ' || strtolower($_SESSION['status'] ?? '') === 'admin' || ($_SESSION['user_id'] ?? 0) == 1;
$isMain = isMainBranch($pdo, $userStoreId);
$canAllBranches = canViewAllBranches($pdo);

// Only users who pass canViewAllBranches() see every branch's figures; everyone else,
// including staff granted the "report" permission, is locked to their own branch.
$reportsCrossBranch = $canAllBranches;

$filter_store_id = isset($_GET['store_id']) && $_GET['store_id'] !== '' ? intval($_GET['store_id']) : intval($_SESSION['store_id'] ?? 1);
// Users who can't see every branch are locked to their own store_id
if (!$reportsCrossBranch) {
    $filter_store_id = $userStoreId;
}

$branchesList = [];
try {
    $branchesList = $pdo->query("SELECT * FROM st_tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$hasStoreIdCol = false;
try {
    $saleCols = $pdo->query("SHOW COLUMNS FROM st_tbsale_save")->fetchAll(PDO::FETCH_COLUMN);
    $hasStoreIdCol = in_array('store_id', $saleCols) || in_array('branch_id', $saleCols);
    $storeColName = in_array('store_id', $saleCols) ? 'store_id' : 'branch_id';
} catch (Exception $e) {}

if ($filter_store_id > 0 && $hasStoreIdCol) {
    $where[] = "s.{$storeColName} = :filter_store_id";
    $params[':filter_store_id'] = $filter_store_id;
}

// ພະນັກງານ (ຕຳແໜ່ງ "ພະນັກງານ"): ເຫັນສະເພາະບິນທີ່ຕົນເອງຂາຍ (user_receive = username, api/pos/checkout_totals.php).
// ສາຂາທີ່ໃຊ້ກະ: ສະເພາະກະປັດຈຸບັນ (ກະທີ່ເປີດຢູ່; ບໍ່ມີ → ກະຫຼ້າສຸດທີ່ຕົນເອງເປີດ) ແລະ ບໍ່ອີງວັນທີ
// (ກະຂ້າມຄືນບໍ່ຂາດ). ຜູ້ຈັດການ/ຜູ້ບໍລິຫານ ເຫັນທັງໝົດຄືເກົ່າ.
$staffScope = null;   // ['user' => ..., 'shift' => [...] | null] — ໃຫ້ໜ້າສະແດງປ້າຍບອກ
if (($_SESSION['status'] ?? '') === 'ພະນັກງານ' && intval($_SESSION['user_id'] ?? 0) !== 1) {
    $staffUser = (string)($_SESSION['username'] ?? '');
    $where[] = "s.user_receive = :staff_user";
    $params[':staff_user'] = $staffUser;
    $staffScope = ['user' => $staffUser, 'shift' => null];

    if (branchHasShifts($pdo, $userStoreId)) {
        $scSt = $pdo->prepare("SELECT id, shift_name, open_time, close_time, status FROM st_shift_sessions
                               WHERE store_id = ? AND (status = 'open' OR opened_by_user_id = ?)
                               ORDER BY (status = 'open') DESC, id DESC LIMIT 1");
        $scSt->execute([$userStoreId, intval($_SESSION['user_id'])]);
        $staffShift = $scSt->fetch(PDO::FETCH_ASSOC);
        // ຕັດເງື່ອນໄຂວັນທີອອກ: ກະກຳນົດຊ່ວງແທນ
        foreach ($where as $wi => $wc) {
            if (strpos($wc, ':from_date') !== false || strpos($wc, ':to_date') !== false) unset($where[$wi]);
        }
        unset($params[':from_date'], $params[':to_date']);
        $where = array_values($where);
        if ($staffShift) {
            $where[] = "s.shift_session_id = :staff_shift";
            $params[':staff_shift'] = (int)$staffShift['id'];
        } else {
            $where[] = "1 = 0";   // ຍັງບໍ່ເຄີຍມີກະ → ບໍ່ມີລາຍການ
        }
        $staffScope['shift'] = $staffShift ?: null;
    }
}

// ບັອກເລືອກກະ (reports_filter.php): ສະເພາະສາຂາດຽວທີ່ມີກະ. ພະນັກງານເຫັນແຕ່ບິນ/ກະຂອງຕົນເອງຢູ່ແລ້ວ → ບໍ່ມີບັອກ
$shiftFilter = reportShiftFilter($pdo, $filter_store_id, 's', $staffScope === null);
if ($shiftFilter['sql'] !== '') {
    $where[] = $shiftFilter['sql'];
}

$whereClause = implode(" AND ", $where);

// Data variables
$sales_data        = [];
$total_qty         = 0;
$total_gross       = 0;
$total_disc        = 0;
$total_net         = 0;
$total_cash        = 0;
$total_qr          = 0;
$total_change      = 0;
$total_tip         = 0;
$total_profit      = 0;
$has_cost_data     = false;
$total_bills_count = 0;

// Daily Report 10 Metrics Variables
$daily_gross_sales    = 0;
$daily_item_discounts = 0;
$daily_bill_discounts = 0;
$daily_total_discounts= 0;
$daily_net_sales      = 0;
$daily_cash_payments  = 0;
$daily_qr_payments    = 0;
$daily_tips_sum       = 0;
$daily_bills_count    = 0;
$daily_items_qty      = 0;

