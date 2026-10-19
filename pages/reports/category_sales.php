<?php
/**
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * ລາຍງານຍອດຂາຍຕາມປະເພດສິນຄ້າ: ຕົວກັ່ນຕອງ, ຕາຕະລາງປະເພດ → ສິນຄ້າ, ຍອດລວມ ແລະ ແຍກເງິນສົດ/ໂອນ.
 *   - partials/category_sales/data.php : ຂໍ້ມູນ: ຕົວກັ່ນຕອງ ແລະ ຍອດຕາມປະເພດ
 *   - partials/category_sales/page_body.php : ສ່ວນສະແດງຜົນ
 */
// Standalone Page: ລາຍງານຕາມປະເພດສິນຄ້າ (Category Sales Report)
session_start();

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

require_once dirname(__DIR__, 2) . '/config/db.php';

if (!hasPermission('category_sales') && !hasPermission('report')) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$from_date     = trim($_GET['from_date'] ?? date('Y-m-01'));
$to_date       = trim($_GET['to_date'] ?? date('Y-m-d'));
$category_id   = trim($_GET['category_id'] ?? '');
// ຂໍ້ມູນ: ຕົວກັ່ນຕອງ ແລະ ຍອດຕາມປະເພດ
require __DIR__ . '/partials/category_sales/data.php';
require_once __DIR__ . '/../../layouts/header.php';
?>

<link rel="stylesheet" href="../../themes/reports.css?v=<?php echo filemtime(__DIR__ . '/../../themes/reports.css'); ?>">
<?php require __DIR__ . '/partials/category_sales/page_body.php'; /* ສ່ວນສະແດງຜົນ */ ?>
<!-- html2pdf & Shared Report JS (Excel / PDF / Print export buttons) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<?php require_once __DIR__ . '/partials/js/reports_js.php'; ?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
