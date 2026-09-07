<?php
// pages/bank/partials/bank_header.php
if (!defined('MINIPOS_APP')) {
    define('MINIPOS_APP', true);
}
?>
<?php
  $selected_branch_name = 'ທຸກສາຂາ (All Branches)';
  if (!empty($filter_store_id) && $filter_store_id > 0) {
    foreach ($branches_list as $b) {
      if ($b['store_id'] == $filter_store_id) {
        $selected_branch_name = $b['store_name'];
        break;
      }
    }
  }
?>
<!-- Bank Management Header with Date Range & Store Filter -->
<div class="bank-header-bar d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h5 class="font-weight-bold text-dark mb-0 text-nowrap" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
      <i class="fas fa-university text-primary mr-2"></i> <?php echo htmlspecialchars(t('bank.title', 'ຈັດການທະນາຄານ')); ?>
    </h5>
  </div>

  <div class="bank-header-tools d-flex align-items-center flex-wrap mt-2 mt-md-0" style="gap: 6px;">
    <!-- Date & Store Filter Form -->
    <form method="GET" action="" class="form-inline d-flex align-items-center flex-wrap" style="gap: 6px;">
      <!-- Store / Branch Selector Box -->
      <div class="input-group input-group-sm">
        <div class="input-group-prepend">
          <span class="input-group-text bg-white font-weight-bold text-dark" style="font-size: 0.78rem;">
            <i class="fas fa-store text-primary mr-1"></i> <?php echo htmlspecialchars(t('bank.branch_label', 'ສາຂາ:')); ?>
          </span>
        </div>
        <select name="store_id" class="form-control form-control-sm font-weight-bold text-primary" style="border-radius: 0 6px 6px 0; min-width: 118px; width: auto; font-size: 0.82rem;" onchange="this.form.submit()">
          <option value="0"><?php echo htmlspecialchars(t('bank.all_branches', '-- ທຸກສາຂາ --')); ?></option>
          <?php foreach ($branches_list as $b): ?>
            <option value="<?php echo $b['store_id']; ?>" <?php echo ($filter_store_id == $b['store_id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($b['store_name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="input-group input-group-sm">
        <div class="input-group-prepend">
          <span class="input-group-text bg-white font-weight-bold" style="font-size: 0.78rem;"><?php echo htmlspecialchars(t('bank.from_date', 'ແຕ່ວັນທີ:')); ?></span>
        </div>
        <input type="date" name="start_date" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($start_date === '2000-01-01' ? date('Y-m-01') : $start_date); ?>" style="border-radius: 0 6px 6px 0;">
      </div>

      <div class="input-group input-group-sm">
        <div class="input-group-prepend">
          <span class="input-group-text bg-white font-weight-bold" style="font-size: 0.78rem;"><?php echo htmlspecialchars(t('bank.to_date', 'ຫາວັນທີ:')); ?></span>
        </div>
        <input type="date" name="end_date" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($end_date); ?>" style="border-radius: 0 6px 6px 0;">
      </div>

      <button type="submit" class="btn btn-sm btn-info font-weight-bold shadow-xs px-2.5" style="border-radius: 6px; font-size: 0.82rem; background: linear-gradient(135deg, #2c5aa0, #244886); border: none; color: #fff;" title="<?php echo htmlspecialchars(t('bank.search_title', 'ຄົ້ນຫາຕາມຊ່ວງວັນທີ ແລະ ສາຂາ')); ?>">
        <i class="fas fa-search mr-1"></i> <?php echo htmlspecialchars(t('bank.search', 'ຄົ້ນຫາ')); ?>
      </button>

      <a href="bank.php" class="btn btn-sm btn-outline-secondary font-weight-bold shadow-xs px-2" style="border-radius: 6px; font-size: 0.82rem;" title="<?php echo htmlspecialchars(t('bank.clear_all_title', 'ລ້າງຄ່າຄົ້ນຫາ (ທັງໝົດ)')); ?>">
        <i class="fas fa-sync-alt"></i> <?php echo htmlspecialchars(t('bank.clear_all', 'ທັງໝົດ')); ?>
      </a>
    </form>

    <?php if (hasPermission('accounting', 'add') || hasPermission('bank', 'add')): ?>
      <button type="button" id="btnAddBankAccount" class="btn btn-sm btn-primary font-weight-bold shadow-sm ml-md-2" data-toggle="modal" data-target="#addBankAccountModal" style="border-radius: 6px; font-size: 0.82rem; padding: 6px 14px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
        <i class="fas fa-plus-circle mr-1"></i> <?php echo htmlspecialchars(t('bank.btn_add_account', 'ເພີ່ມບັນຊີທະນາຄານ')); ?>
      </button>
    <?php endif; ?>
  </div>
</div>

<style>
  /* Keep the title and the whole filter toolbar on ONE line. Lao labels are short; English
     and Thai are wider and used to spill the toolbar onto a second row. Tighten every
     control (label padding, font, gaps, date-input width) so the row fits beside the title
     down to ~1100px before Bootstrap's flex-wrap finally lets it drop on small screens. */
  .bank-header-tools { flex: 1 1 auto; justify-content: flex-end; }
  .bank-header-tools form { gap: 4px !important; }
  .bank-header-tools .input-group-text {
    padding: 0.15rem 0.4rem;
    font-size: 0.72rem !important;
    white-space: nowrap;
  }
  .bank-header-tools input[type="date"] {
    max-width: 132px;
    font-size: 0.78rem;
  }
  .bank-header-tools .btn { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }

  /* On mobile, the filter form wraps onto its own line(s) and the Add button drops below
     it — align that button to the right edge instead of leaving it stuck on the left. */
  @media (max-width: 767.98px) {
    #btnAddBankAccount {
      margin-left: auto !important;
    }
  }
</style>

<?php if (!empty($message)): ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      Swal.fire({
        icon: '<?php echo ($message_type === "success") ? "success" : "warning"; ?>',
        title: '<?php echo ($message_type === "success") ? t('bank.alert_success_title', 'ດຳເນີນການສຳເລັດ!') : t('bank.alert_warning_title', 'ແຈ້ງເຕືອນ!'); ?>',
        text: <?php echo json_encode($message); ?>,
        confirmButtonColor: '#0284c7',
        confirmButtonText: '<?php echo t('bank.btn_ok', 'ຕົກລົງ'); ?>'
      });
    });
  </script>
<?php endif; ?>
