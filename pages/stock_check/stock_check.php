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

// ຂໍ້ຄວາມໃນໜ້າ ຕາມພາສາທີ່ເລືອກ (lang/*/stock_check.php, key sc.sNNN) — PHP ໃຊ້ $sc(), JS ໃຊ້ scT()
$sc = function ($key) { return htmlspecialchars(t($key)); };

require_once __DIR__ . '/../../layouts/header.php';
?>
<script>
// ເປັນ window.I18N_* ຈຶ່ງຖືກປ່ຽນຄ່າເອງເມື່ອກົດທຸງພາສາ (home/partials/dashboard/js_language.php)
var I18N_SC = <?php echo tjson(require __DIR__ . '/../../lang/lo/stock_check.php'); ?>;
function scT(key) { return I18N_SC[key] || key; }
</script>

<link rel="stylesheet" href="../../themes/stock_check.css?v=<?php echo filemtime(__DIR__ . '/../../themes/stock_check.css'); ?>">
<?php echo themeCssLink('report-table.css'); /* ຕາຕະລາງ + ປຸ່ມ Excel/PDF/ພິມ ແບບດຽວກັນທົ່ວລະບົບ */ ?>

<div class="content-wrapper bg-light">

  <?php if ($sc_message !== ''): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
          icon: <?php echo json_encode($sc_message_type === 'success' ? 'success' : 'error'); ?>,
          title: <?php echo json_encode($sc_message_type === 'success' ? t('sc.s001') : t('sc.s002')); ?>,
          text: <?php echo json_encode($sc_message, JSON_UNESCAPED_UNICODE); ?>,
          confirmButtonColor: '#2563eb',
          confirmButtonText: '' + scT('sc.s003') + ''
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
