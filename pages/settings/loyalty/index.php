<?php
// ============================================================
// pages/settings/loyalty/index.php
// ຕັ້ງຄ່າຄະແນນສະສົມຂອງລູກຄ້າ (Customer Loyalty Points)
// ============================================================
session_start();
$base_path = '../../../';
require_once __DIR__ . '/../../../config/db.php';

if (empty($_SESSION['user_id']) || (!hasPermission('loyalty') && !hasPermission('setup'))) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_loyalty') {
    if (!hasPermission('loyalty', 'edit') && !hasPermission('setup')) {
        $message = t('loyalty.err_no_permission', 'ທ່ານບໍ່ມີສິດແກ້ໄຂການຕັ້ງຄ່ານີ້!');
        $message_type = 'warning';
    } else {
        $enabled = isset($_POST['loyalty_enabled']) ? '1' : '0';
        $spend   = (int)str_replace(',', '', $_POST['loyalty_spend_amount'] ?? '0');
        $points  = (int)str_replace(',', '', $_POST['loyalty_points_earned'] ?? '0');
        // Fall back to sensible values so the rule stays meaningful; saving always goes through
        if ($spend < 1)  $spend  = 100000;
        if ($points < 1) $points = 1;
        try {
            $up = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $up->execute(['loyalty_enabled', $enabled]);
            $up->execute(['loyalty_spend_amount', (string)$spend]);
            $up->execute(['loyalty_points_earned', (string)$points]);
            $message = t('loyalty.msg_saved', 'ບັນທຶກການຕັ້ງຄ່າຄະແນນສະສົມສຳເລັດ!');
            $message_type = 'success';
            logActivity($pdo, 'ຕັ້ງຄ່າຄະແນນສະສົມ', "enabled=$enabled, spend=$spend, points=$points");
        } catch (Exception $e) {
            $message = t('loyalty.msg_error_prefix', 'ຜິດພາດ: ') . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

$lpEnabled = getSetting($pdo, 'loyalty_enabled', '0') === '1';
$lpSpend   = (int)getSetting($pdo, 'loyalty_spend_amount', '100000');
$lpPoints  = (int)getSetting($pdo, 'loyalty_points_earned', '1');
if ($lpSpend < 1)  $lpSpend  = 100000;
if ($lpPoints < 1) $lpPoints = 1;

require_once __DIR__ . '/../../../layouts/header.php';
?>

<div class="container-fluid p-3 p-md-4">
  <div class="d-flex align-items-center mb-3">
    <h5 class="font-weight-bold text-dark mb-0" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
      <i class="fas fa-star text-warning mr-2"></i> <?php echo htmlspecialchars(t('loyalty.page_title', 'ຕັ້ງຄ່າຄະແນນສະສົມຂອງລູກຄ້າ')); ?>
    </h5>
  </div>

  <?php if (!empty($message)): ?>
    <script>
      $(function () {
        Swal.fire({
          title: '<?php echo addslashes($message_type === 'success' ? t('loyalty.msg_saved_title', 'ບັນທຶກສຳເລັດ!') : t('loyalty.msg_error_title', 'ແຈ້ງເຕືອນ')); ?>',
          text: '<?php echo addslashes($message); ?>',
          icon: '<?php echo $message_type === 'success' ? 'success' : ($message_type === 'warning' ? 'warning' : 'error'); ?>',
          confirmButtonColor: '#2c5aa0',
          confirmButtonText: '<?php echo addslashes(t('loyalty.ok_btn', 'ຕົກລົງ')); ?>',
          <?php if ($message_type === 'success'): ?>timer: 2500, timerProgressBar: true<?php endif; ?>
        });
      });
    </script>
  <?php endif; ?>

  <div class="card border-0 shadow-sm" style="border-radius: 14px; max-width: 640px;">
    <div class="card-body p-4">
      <form method="POST" action="index.php">
        <input type="hidden" name="action" value="save_loyalty">

        <div class="custom-control custom-switch mb-4">
          <input type="checkbox" class="custom-control-input" id="loyalty_enabled" name="loyalty_enabled" <?php echo $lpEnabled ? 'checked' : ''; ?>>
          <label class="custom-control-label font-weight-bold" for="loyalty_enabled"><?php echo htmlspecialchars(t('loyalty.enable_label', 'ເປີດໃຊ້ງານຄະແນນສະສົມ')); ?></label>
        </div>

        <div class="form-group mb-3">
          <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('loyalty.spend_amount_label', 'ຍອດຊື້ທຸກໆ (ກີບ)')); ?></label>
          <div class="input-group">
            <input type="text" inputmode="numeric" class="form-control font-weight-bold text-right" id="loyalty_spend_amount" name="loyalty_spend_amount" value="<?php echo number_format($lpSpend); ?>" oninput="this.value=this.value.replace(/[^0-9]/g,'').replace(/\B(?=(\d{3})+(?!\d))/g,',')" style="height: 44px;">
            <div class="input-group-append"><span class="input-group-text font-weight-bold">₭</span></div>
          </div>
        </div>

        <div class="form-group mb-3">
          <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('loyalty.points_earned_label', 'ຈະໄດ້ຮັບຄະແນນ')); ?></label>
          <div class="input-group">
            <input type="number" min="0" class="form-control font-weight-bold text-right" id="loyalty_points_earned" name="loyalty_points_earned" value="<?php echo $lpPoints; ?>" style="height: 44px;">
            <div class="input-group-append"><span class="input-group-text font-weight-bold"><?php echo htmlspecialchars(t('loyalty.points_unit', 'ຄະແນນ')); ?></span></div>
          </div>
        </div>

        <p class="text-muted mb-4" style="font-size: 0.88rem;">
          <i class="fas fa-info-circle mr-1 text-info"></i>
          <?php echo htmlspecialchars(sprintf(t('loyalty.hint', 'ຕົວຢ່າງ: ລູກຄ້າຊື້ຄົບ %s ກີບ ຈະໄດ້ຮັບ %s ຄະແນນ (ຄິດແບບປັດເສດທິ້ງ). ໃຫ້ສະເພາະລູກຄ້າທີ່ມີຂໍ້ມູນໃນລະບົບ.'), number_format(max(1, $lpSpend)), number_format(max(0, $lpPoints)))); ?>
        </p>

        <?php if (hasPermission('loyalty', 'edit') || hasPermission('setup') || ($_SESSION['status'] ?? '') === 'ຜູ້ບໍລິຫານ'): ?>
          <button type="submit" class="btn btn-primary font-weight-bold px-4" style="height: 42px; border-radius: 8px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
            <i class="fas fa-save mr-1"></i> <?php echo htmlspecialchars(t('loyalty.save_btn', 'ບັນທຶກ')); ?>
          </button>
        <?php endif; ?>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
