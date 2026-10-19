<?php
/**
 * ======================================================================
 * ໄຟລ໌: pages/bank/partials/bank_header.php
 * ໜ້າທີ່: ຫົວໜ້າທະນາຄານ — ຊື່ໜ້າ + ປຸ່ມເພີ່ມບັນຊີ (ຂວາມື), ແລະ ກ່ອງຕົວກອງແບບໜ້າລາຍງານ: ຊ່ວງວັນທີ, ສາຂາ (ເຊື່ອງຖ້າມີສາຂາດຽວ), ຄົ້ນຫາ/ລ້າງຄ່າ.
 * ======================================================================
 */
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
<!-- Bank Management Header: title + add button (right) -->
<div class="bank-header-bar d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap: 10px;">
  <div>
    <h5 class="font-weight-bold text-dark mb-0 text-nowrap" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
      <i class="fas fa-university text-primary mr-2"></i> <?php echo htmlspecialchars(t('bank.title', 'ຈັດການທະນາຄານ')); ?>
    </h5>
  </div>

  <?php if (hasPermission('accounting', 'add') || hasPermission('bank', 'add')): ?>
    <button type="button" id="btnAddBankAccount" class="btn btn-primary bank-add-btn font-weight-bold shadow-sm ml-auto" data-toggle="modal" data-target="#addBankAccountModal">
      <i class="fas fa-plus-circle mr-1"></i> <?php echo htmlspecialchars(t('bank.btn_add_account', 'ເພີ່ມບັນຊີທະນາຄານ')); ?>
    </button>
  <?php endif; ?>
</div>

<!-- Filter box (ຄືໜ້າລາຍງານ): ສາຂາ, ແຕ່ວັນທີ, ຫາວັນທີ, ປຸ່ມຄົ້ນຫາ + ລ້າງຄ່າ -->
<div class="bank-filter-box mb-3">
  <form method="GET" action="" class="bank-filter-form">
    <div class="bf-date">
      <label><i class="fas fa-calendar-alt text-primary mr-1"></i> <?php echo htmlspecialchars(t('bank.from_date', 'ແຕ່ວັນທີ:')); ?></label>
      <input type="date" name="start_date" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($start_date === '2000-01-01' ? date('Y-m-01') : $start_date); ?>">
    </div>

    <div class="bf-date">
      <label><i class="fas fa-calendar-check text-primary mr-1"></i> <?php echo htmlspecialchars(t('bank.to_date', 'ຫາວັນທີ:')); ?></label>
      <input type="date" name="end_date" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($end_date); ?>">
    </div>

    <?php if (canViewAllBranches($pdo) && $bankMultiBranch): ?>
    <!-- Branch selector (hidden when the shop has only one branch) -->
    <div class="bf-branch">
      <label><i class="fas fa-store text-info mr-1"></i> <?php echo htmlspecialchars(t('bank.branch_label', 'ສາຂາ:')); ?></label>
      <select name="store_id" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()">
        <?php foreach ($branches_list as $b): ?>
          <option value="<?php echo $b['store_id']; ?>" <?php echo ($filter_store_id == $b['store_id']) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($b['store_name']); ?>
          </option>
        <?php endforeach; ?>
        <option value="0" <?php echo ((int)$filter_store_id === 0) ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('bank.all_branches', '-- ທຸກສາຂາ --')); ?></option>
      </select>
    </div>
    <?php endif; ?>

    <div class="bf-act">
      <button type="submit" class="btn btn-primary btn-sm font-weight-bold bf-search" title="<?php echo htmlspecialchars(t('bank.search_title', 'ຄົ້ນຫາຕາມຊ່ວງວັນທີ ແລະ ສາຂາ')); ?>">
        <i class="fas fa-search mr-1"></i> <?php echo htmlspecialchars(t('bank.search', 'ຄົ້ນຫາ')); ?>
      </button>
      <a href="bank.php" class="btn btn-light btn-sm border font-weight-bold bf-reset" title="<?php echo htmlspecialchars(t('bank.clear_all_title', 'ລ້າງຄ່າຄົ້ນຫາ (ທັງໝົດ)')); ?>">
        <i class="fas fa-redo"></i>
      </a>
    </div>
  </form>
</div>

<style>
  /* ປຸ່ມເພີ່ມບັນຊີ: ຢູ່ຂວາມືແຖວຫົວຂໍ້ ແລະ ໃຫຍ່ກວ່າປຸ່ມທົ່ວໄປ (!important ຊະນະກົດ .btn ນ້ອຍໃນມືຖື) */
  #btnAddBankAccount {
    font-size: 0.95rem !important; padding: 9px 18px !important; border-radius: 8px !important;
    border: none !important; color: #fff !important;
  }
  /* Default colour only — class-level, so the theme colour the shell injects
     (`body .btn-primary{background:…!important}`, home/dashboard.php) wins when one is picked. */
  .bank-add-btn { background: linear-gradient(135deg, #2c5aa0, #244886) !important; }
  #btnAddBankAccount i { font-size: 1rem !important; }

  /* ກ່ອງຕົວກອງ — ແບບດຽວກັບໜ້າລາຍງານ (ປ້າຍຢູ່ເທິງຊ່ອງ) */
  .bank-filter-box { background: #fff; border: 1.5px solid #e2e8f0; border-radius: 14px; box-shadow: 0 3px 12px rgba(0,0,0,.03); padding: 12px 16px; }
  .bank-filter-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 10px; }
  .bank-filter-form > div { flex: 0 1 190px; min-width: 0; }
  .bank-filter-form > div.bf-branch { flex: 0 1 220px; }
  .bank-filter-form > div.bf-act { flex: 0 0 auto; display: flex; gap: 8px; }
  .bank-filter-form label { display: block; font-weight: 700; color: #1e293b; font-size: 0.82rem; margin-bottom: 4px; white-space: nowrap; }
  .bank-filter-form .form-control { height: 38px; border-radius: 8px; font-size: 0.85rem; width: 100%; }
  .bank-filter-form .btn { height: 38px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; }
  .bank-filter-form .bf-search { background: linear-gradient(135deg, #2c5aa0, #244886); border: none; padding: 0 18px; white-space: nowrap; }
  .bank-filter-form .bf-reset { padding: 0 12px; }

  /* ໂທລະສັບ: ວັນທີ 2 ອັນຮຽງກັນ, ສາຂາ ເຕັມແຖວ, ປຸ່ມຄົ້ນຫາ + ລ້າງຄ່າ ເຕັມແຖວ */
  @media (max-width: 767.98px) {
    .bank-filter-box { padding: 10px 12px; }
    .bank-filter-form { gap: 10px 8px; }
    .bank-filter-form > div.bf-date { flex: 1 1 calc(50% - 4px); }
    .bank-filter-form > div.bf-branch { flex: 1 1 100%; }
    .bank-filter-form > div.bf-act { flex: 1 1 100%; }
    .bank-filter-form .bf-search { flex: 1 1 auto; }
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
