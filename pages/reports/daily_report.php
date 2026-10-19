<?php
/**
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * ລາຍງານປະຈຳວັນ: ບັດສະຖິຕິ (ຍອດຂາຍ, ສ່ວນຫຼຸດ, ເງິນສົດ, ເງິນໂອນ, ເງິນໄດ້ຮັບຕົວຈິງ, ບິນ, ລາຍການ, ຈຳນວນ), ຍອດຕາມທະນາຄານ/ສະກຸນເງິນ ແລະ ກຣາຟ.
 *   - partials/daily_report/data.php : ຂໍ້ມູນ: ຕົວກັ່ນຕອງ ແລະ ຕົວເລກ
 *   - partials/daily_report/page_body.php : ສ່ວນສະແດງຜົນ
 *   - partials/daily_report/charts_js.php : JS: ກຣາຟ ແລະ ນັບຕົວເລກ
 */

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

// ຂໍ້ມູນ: ຕົວກັ່ນຕອງ ແລະ ຕົວເລກ
require __DIR__ . '/partials/daily_report/data.php';
require_once __DIR__ . '/../../layouts/header.php';
?>

<link rel="stylesheet" href="../../themes/reports.css?v=<?php echo filemtime(__DIR__ . '/../../themes/reports.css'); ?>">

<div class="container-fluid p-3 p-md-4">
<?php require __DIR__ . '/partials/daily_report/page_body.php'; /* ສ່ວນສະແດງຜົນ */ ?>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<?php require __DIR__ . '/partials/daily_report/charts_js.php'; /* JS: ກຣາຟ ແລະ ນັບຕົວເລກ */ ?>

<!-- Bill Details & Thermal Receipt Modals -->
<?php require_once __DIR__ . '/partials/reports_modal.php'; ?>
<?php require_once __DIR__ . '/partials/js/reports_js.php'; ?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
