<?php
// Component: Stock Transfer History Filters Form
// ໂຄງຮ່າງ responsive ມາຈາກ .mp-filter-bar (assets/css/global-custom/03-controls.css) — ແບບດຽວກັບໜ້າລາຍການຮັບເຂົ້າ
?>
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
  <div class="card-body p-3">
    <form method="GET" action="" class="mp-filter-bar">
      <!-- Date range -->
      <div class="mp-f">
        <label><i class="fas fa-calendar-alt text-primary"></i> <?php echo htmlspecialchars(t('transfer_history.from_date', 'ຕັ້ງແຕ່ວັນທີ:')); ?></label>
        <input type="date" name="from_date" class="form-control font-weight-bold" value="<?php echo htmlspecialchars($from_date); ?>">
      </div>
      <div class="mp-f">
        <label><i class="fas fa-calendar-check text-primary"></i> <?php echo htmlspecialchars(t('transfer_history.to_date', 'ຫາວັນທີ:')); ?></label>
        <input type="date" name="to_date" class="form-control font-weight-bold" value="<?php echo htmlspecialchars($to_date); ?>">
      </div>

      <?php if ($canAllBranches): ?>
        <!-- Origin Branch -->
        <div class="mp-f mp-f-wide">
          <label><i class="fas fa-store text-info"></i> <?php echo htmlspecialchars(t('transfer_history.from_store', 'ສາຂາຕົ້ນທາງ:')); ?></label>
          <select name="from_store" class="form-control font-weight-bold">
            <option value=""><?php echo htmlspecialchars(t('transfer_history.all_opt', '-- ທັງໝົດ --')); ?></option>
            <?php foreach ($stores as $st): ?>
              <option value="<?php echo $st['store_id']; ?>" <?php echo ($filter_from_store == $st['store_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($st['store_name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <!-- Destination Branch -->
        <div class="mp-f mp-f-wide">
          <label><i class="fas fa-store-alt text-success"></i> <?php echo htmlspecialchars(t('transfer_history.to_store', 'ສາຂາປາຍທາງ:')); ?></label>
          <select name="to_store" class="form-control font-weight-bold">
            <option value=""><?php echo htmlspecialchars(t('transfer_history.all_opt', '-- ທັງໝົດ --')); ?></option>
            <?php foreach ($stores as $st): ?>
              <option value="<?php echo $st['store_id']; ?>" <?php echo ($filter_to_store == $st['store_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($st['store_name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>

      <!-- Search input -->
      <div class="mp-f mp-f-wide">
        <label><i class="fas fa-search text-primary"></i> <?php echo htmlspecialchars(t('transfer_history.search_label', 'ຄົ້ນຫາລະຫັດ/ໝາຍເຫດ:')); ?></label>
        <input type="text" name="search" class="form-control" placeholder="<?php echo htmlspecialchars(t('transfer_history.search_placeholder', 'ລະຫັດໂອນ, ໝາຍເຫດ...')); ?>" value="<?php echo htmlspecialchars($search); ?>">
      </div>

      <div class="mp-f-actions">
        <button type="submit" class="btn mp-btn-search shadow-sm">
          <i class="fas fa-search"></i> <?php echo htmlspecialchars(t('transfer_history.search_btn', 'ຄົ້ນຫາ')); ?>
        </button>
        <a href="transfer_history.php" class="btn btn-outline-secondary" title="<?php echo htmlspecialchars(t('transfer_history.clear_btn', 'ລ້າງຄ່າ')); ?>">
          <i class="fas fa-redo"></i>
        </a>
      </div>
    </form>
  </div>
</div>
