<!-- ============================================================
     exchange_rate_header.php - ສ່ວນหัวຂໍ້ໜ້າ ແລະ ການແຈ້ງເຕືອນອັດຕາແລກປ່ຽນ
     ============================================================ -->
<?php
$bp = isset($base_path) ? rtrim($base_path, '/') . '/' : '../../../';
?>
<!-- ດຶງ CSS ສຳລັບ Flag Icons -->
<link rel="stylesheet" href="<?php echo $bp; ?>plugins/flag-icon-css/css/flag-icon.min.css">

<!-- ແຖບหัวຂໍ້ໜ້າ ແລະ ປຸ່ມເພີ່ມອັດຕາແລກປ່ຽນໃໝ່ -->
<div class="row mb-3 align-items-center">
  <div class="col-sm-6">
    <h5 class="m-0 font-weight-bold" style="color: #1e293b; font-size: 1.15rem;">
      <i class="fas fa-exchange-alt text-primary mr-2"></i> <?php echo htmlspecialchars(t('exchange_rate.header_title', 'ອັດຕາແລກປ່ຽນເງິນ')); ?>
    </h5>
  </div>
  <div class="col-sm-6 text-right">
    <?php if (hasPermission('exchange_rate', 'add')): ?>
      <button type="button" class="btn btn-primary px-3.5 font-weight-bold" data-toggle="modal" data-target="#rateModal" style="border-radius: 6px; height: 38px; border: none; background: linear-gradient(135deg, #2c5aa0, #244886);">
        <i class="fas fa-plus-circle mr-1.5"></i> <?php echo htmlspecialchars(t('exchange_rate.btn_add_new', 'ອັບເດດອັດຕາໃໝ່')); ?>
      </button>
    <?php endif; ?>
  </div>
</div>

<!-- ເລືອກສະກຸນເງິນສະແດງຜົນ (POS ແລະ ບິນ ຈະຄິດໄລ່/ສະແດງເປັນສະກຸນນີ້) -->
<?php
  $dcCode  = $displayCurrency['code'] ?? 'KIP';
  $dcMeta  = getCurrencyMeta();
  $dcRates = $exRates ?? ['KIP' => 1, 'THB' => 0, 'USD' => 0, 'CNY' => 0];
?>
<div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
  <div class="card-body py-3 px-3 px-md-4">
    <form method="POST" action="index.php" class="d-flex flex-wrap align-items-center" style="gap: 10px 18px;">
      <input type="hidden" name="action" value="set_display_currency">
      <div class="font-weight-bold text-dark" style="font-size: 0.95rem; white-space: nowrap;">
        <i class="fas fa-globe text-primary mr-1"></i>
        <?php echo htmlspecialchars(t('exchange_rate.display_currency_label', 'ສະກຸນເງິນສະແດງຜົນຫຼັກ:')); ?>
      </div>
      <div class="btn-group btn-group-toggle flex-wrap" data-toggle="buttons" style="gap: 6px;">
        <?php foreach (['KIP', 'THB', 'USD', 'CNY'] as $cc): ?>
          <?php
            $isBase   = ($cc === 'KIP');
            $rateVal  = (float)($dcRates[$cc] ?? 0);
            $disabled = (!$isBase && $rateVal <= 0);
            $checked  = ($dcCode === $cc);
          ?>
          <?php $ccNameKey = 'exchange_rate.col_' . strtolower($cc); ?>
          <label class="btn btn-sm <?php echo $checked ? 'btn-primary' : 'btn-outline-primary'; ?> font-weight-bold px-3 <?php echo $disabled ? 'disabled' : ''; ?>" style="border-radius: 8px;" title="<?php echo $disabled ? htmlspecialchars(t('exchange_rate.rate_not_set', 'ຍັງບໍ່ໄດ້ຕັ້ງອັດຕາ')) : ''; ?>">
            <input type="radio" name="display_currency" value="<?php echo $cc; ?>" <?php echo $checked ? 'checked' : ''; ?> <?php echo $disabled ? 'disabled' : ''; ?> onchange="this.form.submit()">
            <?php if (!$isBase) echo htmlspecialchars($dcMeta[$cc]['symbol']); ?> <span><?php echo htmlspecialchars(t($ccNameKey, $dcMeta[$cc]['name'])); ?></span>
            <?php if (!$isBase): ?>
              <small class="d-block font-weight-normal" style="font-size: 0.72rem; opacity: 0.85;">
                <?php echo $rateVal > 0 ? '1 ' . htmlspecialchars(t($ccNameKey, $dcMeta[$cc]['name'])) . ' = ' . number_format($rateVal) . ' ' . kipSymbol() : htmlspecialchars(t('exchange_rate.rate_not_set', 'ຍັງບໍ່ໄດ້ຕັ້ງ')); ?>
              </small>
            <?php endif; ?>
          </label>
        <?php endforeach; ?>
      </div>
      <small class="text-muted" style="font-size: 0.78rem;">
        <?php echo htmlspecialchars(t('exchange_rate.display_currency_hint', 'ຂໍ້ມູນຍັງເກັບເປັນກີບ — ນີ້ພຽງປ່ຽນການສະແດງ/ຄິດໄລ່ໜ້າ POS. ໃນບິນສະແດງຄົບ 4 ສະກຸນສະເໝີ.')); ?>
      </small>
    </form>
  </div>
</div>

<!-- ສະແດງຂໍ້ຄວາມແຈ້ງເຕືອນ (SweetAlert2) ເມື່ອດຳເນີນການສຳເລັດ ຫຼື ຜິດພາດ -->
<?php if ($message !== ''): ?>
  <?php $__isCurToast = (($_POST['action'] ?? '') === 'set_display_currency' && $message_type === 'success'); ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      <?php if ($__isCurToast): ?>
      // Currency switch: quiet top-right toast, auto-dismiss, no button
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: '<?php echo addslashes($message); ?>',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true
      });
      <?php else: ?>
      Swal.fire({
        icon: '<?php echo $message_type === "success" ? "success" : "error"; ?>',
        title: '<?php echo $message_type === "success" ? t('exchange_rate.swal_success_title', 'ສຳເລັດ') : t('exchange_rate.swal_error_title', 'ແຈ້ງເຕືອນ'); ?>',
        text: '<?php echo addslashes($message); ?>',
        confirmButtonColor: '#2563eb'
      });
      <?php endif; ?>
    });
  </script>
<?php endif; ?>