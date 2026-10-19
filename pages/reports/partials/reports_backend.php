<?php
/**
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * Backend ລວມຂອງໜ້າລາຍງານການຂາຍ (all_sales, item_sales, reports, sales_details, delete_bills): ຮັບຕົວກັ່ນຕອງ, AJAX ລາຍລະອຽດບິນ/ລົບບິນ, ດຶງຂໍ້ມູນ, ແບ່ງໜ້າ ແລະ ສະຖານະບິນຕິດໜີ້.
 *   - reports_backend/ajax_bill_details.php : AJAX: ລາຍລະອຽດບິນ
 *   - reports_backend/ajax_delete_bill.php : AJAX: ລົບ (ຍົກເລີກ) ບິນ
 *   - reports_backend/filters_query.php : ຕົວກັ່ນຕອງ ແລະ ເງື່ອນໄຂ SQL
 *   - reports_backend/daily_and_repayments.php : ຕົວເລກລາຍວັນ ແລະ ການຊຳລະໜີ້
 *   - reports_backend/sales_rows.php : ດຶງແຖວການຂາຍ
 *   - reports_backend/page_slice_badges.php : ແບ່ງໜ້າ ແລະ ສະຖານະບິນຕິດໜີ້
 */
// Reports Backend Processing & Data Layer - Modular Architecture
session_start();

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

require_once dirname(__DIR__, 3) . '/config/db.php';

// Check permissions (Skip for read-only get_bill_details action)
$currentReportType = trim($_GET['type'] ?? 'all_sales');
$reqAction = $_REQUEST['action'] ?? '';

if ($reqAction !== 'get_bill_details') {
    if ($currentReportType === 'item_sales') {
        if (!hasPermission('item_sales') && !hasPermission('sale') && !hasPermission('report') && !hasPermission('pos')) {
            echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
            exit();
        }
    } else {
        if (!hasPermission($currentReportType) && !hasPermission('all_sales') && !hasPermission('report') && !hasPermission('sale') && !hasPermission('pos')) {
            echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
            exit();
        }
    }
}

// 1. Get Filter Input Parameters
$type         = trim($_GET['type'] ?? 'all_sales');
$default_from = date('Y-m-01');

$from_date  = trim($_GET['from_date'] ?? $default_from); // default: today for daily, 1st of month for others
$to_date    = trim($_GET['to_date'] ?? date('Y-m-d'));   // default: today
$search     = trim($_GET['search'] ?? '');
$view_mode  = trim($_GET['view_mode'] ?? 'invoice');      // 'invoice' | 'item'

// Pagination Parameters
$page = intval($_GET['page'] ?? 1);
if ($page < 1) $page = 1;

$per_page_default = '10';
$per_page_raw     = trim($_GET['per_page'] ?? $per_page_default);

if ($per_page_raw === 'all') {
    $per_page = 'all';
} else {
    $per_page = intval($per_page_raw);
    if (!in_array($per_page, [5, 10, 25, 50, 100])) {
        $per_page = 10;
    }
}

function getReportPageUrl($targetPage) {
    $queryParams = $_GET;
    $queryParams['page'] = $targetPage;
    $currentScript = basename($_SERVER['PHP_SELF'] ?? 'reports.php');
    return $currentScript . '?' . http_build_query($queryParams);
}

function getBankLogoByInfo($bankName, $bankAccountId = 0) {
    // Unscoped on purpose: a bill's own bank_account_id/bank_name is a concrete record of
    // whichever bank was actually picked at checkout, and must still resolve to its real logo
    // for display even when that account's store_id doesn't match the branch currently being
    // viewed (see the note where $all_bank_accounts_map is built). $bank_accounts_map (branch-
    // scoped) stays reserved for the filter dropdown & per-branch breakdown only.
    global $pdo, $all_bank_accounts_map;
    $logoFile = '';
    $bankCode = '';

    // 1. Search by bankAccountId
    if (!empty($bankAccountId) && isset($all_bank_accounts_map[$bankAccountId])) {
        $logoFile = $all_bank_accounts_map[$bankAccountId]['bank_logo'] ?? '';
        $bankCode = $all_bank_accounts_map[$bankAccountId]['bank_code'] ?? '';
    }

    // 2. Search all_bank_accounts_map by bank_name or bank_code if logoFile is empty
    if (empty($logoFile) && !empty($all_bank_accounts_map)) {
        $search = strtolower(trim($bankName));
        foreach ($all_bank_accounts_map as $acc) {
            $bName = strtolower(trim($acc['bank_name'] ?? ''));
            $bCode = strtolower(trim($acc['bank_code'] ?? ''));
            if ($search && (($bName && strpos($search, $bName) !== false) || ($bCode && strpos($search, $bCode) !== false) || ($bName && strpos($bName, $search) !== false))) {
                $logoFile = $acc['bank_logo'] ?? '';
                $bankCode = $acc['bank_code'] ?? '';
                break;
            }
        }
    }

    if (empty($bankCode) && !empty($bankName)) {
        if (stripos($bankName, 'BCEL') !== false || stripos($bankName, 'BCL') !== false) { $bankCode = 'BCEL'; }
        elseif (stripos($bankName, 'LDB') !== false) { $bankCode = 'LDB'; }
        elseif (stripos($bankName, 'JDB') !== false) { $bankCode = 'JDB'; }
        elseif (stripos($bankName, 'STB') !== false || stripos($bankName, 'ST') !== false) { $bankCode = 'STB'; }
        elseif (stripos($bankName, 'APB') !== false) { $bankCode = 'APB'; }
        elseif (stripos($bankName, 'LVB') !== false) { $bankCode = 'LVB'; }
    }

    $root = dirname(__DIR__, 3); // Root directory of MiniPos
    if (!empty($logoFile) && file_exists($root . '/assets/img/banks/' . basename($logoFile))) {
        return '../../assets/img/banks/' . basename($logoFile);
    }
    $code = strtolower(trim($bankCode));
    if (!empty($code) && file_exists($root . '/assets/img/banks/' . $code . '.svg')) {
        return '../../assets/img/banks/' . $code . '.svg';
    }
    // No uploaded logo and no matching code-based .svg on disk (the app ships none by default,
    // e.g. bcel.svg) — empty string tells the caller to show a generic fallback icon instead of
    // an <img> pointed at a file that doesn't exist.
    return '';
}

// AJAX: ລາຍລະອຽດບິນ
require __DIR__ . '/reports_backend/ajax_bill_details.php';
// AJAX: ລົບ (ຍົກເລີກ) ບິນ
require __DIR__ . '/reports_backend/ajax_delete_bill.php';
// ຕົວກັ່ນຕອງ ແລະ ເງື່ອນໄຂ SQL
require __DIR__ . '/reports_backend/filters_query.php';
// ຕົວເລກລາຍວັນ ແລະ ການຊຳລະໜີ້
require __DIR__ . '/reports_backend/daily_and_repayments.php';
// ດຶງແຖວການຂາຍ
require __DIR__ . '/reports_backend/sales_rows.php';
// ແບ່ງໜ້າ ແລະ ສະຖານະບິນຕິດໜີ້
require __DIR__ . '/reports_backend/page_slice_badges.php';
