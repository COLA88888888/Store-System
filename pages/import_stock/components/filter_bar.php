<?php
// Component: Date Range Filter, Branch Selector & View Type Bar
?>
<div class="card border-0 shadow-sm bg-white mb-4" style="border-radius: 16px;">
  <div class="card-body p-3.5">
    <form method="GET" action="import_list.php" class="d-flex align-items-end flex-wrap" style="gap: 12px;">

      <!-- 1. From Date -->
      <div style="flex: 1 1 150px; min-width: 140px;">
        <label class="form-label font-weight-bold text-dark mb-1" style="font-size: 0.88rem; white-space: nowrap;">
          <i class="fas fa-calendar-alt text-primary mr-1"></i> <?php echo htmlspecialchars(t('import_list.filter_from_date', 'ຕັ້ງແຕ່ວັນທີ:')); ?>
        </label>
        <input type="date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>" class="form-control font-weight-bold" style="height: 38px; border-radius: 8px; width: 100%;">
      </div>

      <!-- 2. To Date -->
      <div style="flex: 1 1 150px; min-width: 140px;">
        <label class="form-label font-weight-bold text-dark mb-1" style="font-size: 0.88rem; white-space: nowrap;">
          <i class="fas fa-calendar-alt text-primary mr-1"></i> <?php echo htmlspecialchars(t('import_list.filter_to_date', 'ຫາວັນທີ:')); ?>
        </label>
        <input type="date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>" class="form-control font-weight-bold" style="height: 38px; border-radius: 8px; width: 100%;">
      </div>

      <!-- 3. Branch / Store Selector — main branch / admin only, and only worth showing
           once there's more than one branch to pick from -->
      <?php if (($canAllBranches) && count($stores) > 1): ?>
      <div style="flex: 1 1 170px; min-width: 150px;">
        <label class="form-label font-weight-bold text-dark mb-1" style="font-size: 0.88rem; white-space: nowrap;">
          <i class="fas fa-store text-info mr-1"></i> <?php echo htmlspecialchars(t('import_list.filter_select_branch', 'ເລືອກສາຂາ:')); ?>
        </label>
        <select name="store_id" class="form-control font-weight-bold border-info" style="height: 38px; border-radius: 8px; background-color: #f0f9ff; width: 100%;" onchange="this.form.submit()">
          <?php foreach ($stores as $st): ?>
            <option value="<?php echo $st['store_id']; ?>" <?php echo ($filter_store == $st['store_id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($st['store_name']); ?> <?php echo !empty($st['is_main']) ? htmlspecialchars(t('import_list.main_branch_suffix', '(ສາງຫຼັກ)')) : ''; ?>
            </option>
          <?php endforeach; ?>
          <option value="0" <?php echo ((int)$filter_store === 0) ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('import_list.filter_all_branches', '-- ທຸກສາຂາ --')); ?></option>
        </select>
      </div>
      <?php else: ?>
        <input type="hidden" name="store_id" value="<?php echo $filter_store; ?>">
      <?php endif; ?>

      <!-- 4. Report View Type Selector -->
      <div style="flex: 1 1 170px; min-width: 150px;">
        <label class="form-label font-weight-bold text-dark mb-1" style="font-size: 0.88rem; white-space: nowrap;">
          <i class="fas fa-list text-success mr-1"></i> <?php echo htmlspecialchars(t('import_list.filter_report_type', 'ປະເພດລາຍງານ:')); ?>
        </label>
        <select name="view_type" class="form-control font-weight-bold" style="height: 38px; border-radius: 8px; width: 100%;" onchange="this.form.submit()">
          <option value="bill" <?php echo ($view_type === 'bill') ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('import_list.opt_view_bill', 'ຈັດການໃບບິນ')); ?></option>
          <option value="detail" <?php echo ($view_type === 'detail') ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('import_list.opt_view_detail', 'ລາຍລະອຽດສິນຄ້າ')); ?></option>
        </select>
      </div>

      <!-- 5. Currency Selector — view the cost figures converted into a chosen currency -->
      <div style="flex: 0 0 120px; width: 120px;">
        <label class="form-label font-weight-bold text-dark mb-1" style="font-size: 0.88rem; white-space: nowrap;">
          <i class="fas fa-coins text-warning mr-1"></i> <?php echo htmlspecialchars(t('reports.currency_label', 'ສະກຸນເງິນ:')); ?>
        </label>
        <select name="currency_filter" class="form-control font-weight-bold" style="height: 38px; border-radius: 8px; border: 1.5px solid #a16207; color: #854d0e; background: #fefce8; width: 100%;" onchange="this.form.submit()">
          <option value=""><?php echo htmlspecialchars(t('home.default_currency_short', 'ຄ່າເລີ່ມຕົ້ນ')); ?></option>
          <?php foreach (getCurrencyMeta() as $__cc => $__ci): ?>
            <option value="<?php echo htmlspecialchars($__cc); ?>" <?php echo $currency_filter === $__cc ? 'selected' : ''; ?>>
              <?php /* ລະຫັດສະກຸນເງິນເທົ່ານັ້ນ (KIP → LAK) ບໍ່ມີສັນຍາລັກ */ echo htmlspecialchars($__cc === 'KIP' ? 'LAK' : $__cc); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- 6. Action Buttons -->
      <div class="d-flex align-items-center" style="flex: 0 0 auto; gap: 6px;">
        <button type="submit" class="btn btn-primary font-weight-bold px-2.5 d-inline-flex align-items-center justify-content-center shadow-sm" style="height: 38px; border-radius: 8px; white-space: nowrap; font-size: 0.85rem; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
          <i class="fas fa-search mr-1.5"></i> <?php echo htmlspecialchars(t('import_list.search_btn', 'ຄົ້ນຫາ')); ?>
        </button>
        <a href="import_list.php" class="btn btn-outline-secondary font-weight-bold d-inline-flex align-items-center justify-content-center px-2.5" style="height: 38px; border-radius: 8px;" title="<?php echo htmlspecialchars(t('import_list.clear_filter_title', 'ລ້າງຄ່າກັ່ນກອງ')); ?>">
          <i class="fas fa-sync-alt"></i>
        </a>
      </div>

    </form>
  </div>
</div>
