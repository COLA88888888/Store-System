<?php
/**
 * ໜ້າ ເຊັກສະຕັອກ (ນັບສິນຄ້າຈິງທຽບກັບລະບົບ). Controller: api/stock_check_backend.php
 * ໄຟລ໌ຍ່ອຍ (require ຕາມລຳດັບ):
 *   - partials/list_view.php  : ລາຍການໃບເຊັກສະຕັອກ + ປຸ່ມເລີ່ມນັບໃໝ່ (ເມື່ອບໍ່ມີ ?id)
 *       ├ partials/history_view.php : ແຖບ "ປະຫວັດການນັບ" — ສິນຄ້າໃດນັບແລ້ວ ໃຜນັບ ເມື່ອໃດ (?view=history)
 *       ├ partials/filter_box.php   : ກ່ອງວັນທີ (ແບບໜ້າທະນາຄານ)
 *       └ partials/pagination.php   : ແບ່ງໜ້າ (ຝັ່ງເຊີບເວີ)
 *   - partials/count_view.php : ຕາຕະລາງນັບສິນຄ້າຂອງໃບດຽວ (ເມື່ອມີ ?id)
 *   - partials/export_buttons.php : ປຸ່ມ Excel / PDF / ພິມ (ໃຊ້ໃນທຸກແຖບ)
 *   - partials/export_js.php  : ສ້າງລາຍງານທຳມະດາ ສຳລັບ Excel / PDF / ພິມ (ຫຼັງ footer)
 *   - partials/count_js.php   : ສະແກນ/ປ້ອນຈຳນວນ, ແບ່ງໜ້າ, ບັນທຶກ (ຈຶ່ງສະແດງຜົນ), ຢືນຢັນ
 */
session_start();
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

require_once __DIR__ . '/../../api/stock_check_backend.php';

require_once __DIR__ . '/../../layouts/header.php';
?>

<link rel="stylesheet" href="../../themes/stock_check.css?v=<?php echo filemtime(__DIR__ . '/../../themes/stock_check.css'); ?>">

<div class="content-wrapper bg-light">

  <?php if ($sc_message !== ''): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
          icon: <?php echo json_encode($sc_message_type === 'success' ? 'success' : 'error'); ?>,
          title: <?php echo json_encode($sc_message_type === 'success' ? 'ສຳເລັດ' : 'ແຈ້ງເຕືອນ'); ?>,
          text: <?php echo json_encode($sc_message, JSON_UNESCAPED_UNICODE); ?>,
          confirmButtonColor: '#2563eb',
          confirmButtonText: 'ຕົກລົງ'
        });
      });
    </script>
  <?php endif; ?>

  <?php if ($scCheck): ?>
<?php require __DIR__ . '/partials/count_view.php'; ?>
  <?php else: ?>
<?php require __DIR__ . '/partials/list_view.php'; ?>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

<?php require __DIR__ . '/partials/export_js.php'; ?>
<?php if ($scCheck): ?>
<?php require __DIR__ . '/partials/count_js.php'; ?>
<?php endif; ?>
