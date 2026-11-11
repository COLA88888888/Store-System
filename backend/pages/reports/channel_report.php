<?php
/**
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍ):
 * ລາຍງານລາຍຮັບ ເວັບໄຊ vs ລູກຄ້າໜ້າຮ້ານ — ປຽບທຽບສອງຊ່ອງທາງ, ສັດສ່ວນ, ສິນຄ້າຂາຍດີແຕ່ລະຊ່ອງທາງ, ລາຍວັນ
 * ແລະ ແນວທາງການຕະຫຼາດຕາມຊ່ອງທາງທີ່ລາຍຮັບສູງກວ່າ. ສິດ: financial (ລາຍງານການເງິນ).
 *   - partials/channel_report/data.php      : ຂໍ້ມູນ
 *   - partials/channel_report/page_body.php : ສ່ວນສະແດງຜົນ
 */
session_start();
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';
require_once dirname(__DIR__, 2) . '/config/db.php';

if (!hasPermission('financial')) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

require __DIR__ . '/partials/channel_report/data.php';
require_once __DIR__ . '/../../layouts/header.php';
?>
<?php echo themeCssLink('channel_report.css'); ?>
<?php require __DIR__ . '/partials/channel_report/page_body.php'; ?>
<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
