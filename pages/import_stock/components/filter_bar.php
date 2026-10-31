<?php
// Component: Date Range Filter, Branch Selector & View Type Bar
// ໂຄງຮ່າງ responsive ມາຈາກ .mp-filter-bar (assets/css/global-custom/03-controls.css)
?>
<div class="card border-0 shadow-sm bg-white mb-4" style="border-radius: 16px;">
  <div class="card-body p-3">
    <form method="GET" action="import_list.php" class="mp-filter-bar">

      <!-- 1. From Date -->
      <div class="mp-f">
        <label><i class="fas fa-calendar-alt text-primary"></i> <?php echo htmlspecialchars(t('import_list.filter_from_date', 'ຕັ້ງແຕ່ວັນທີ:')); ?></label>
        <input type="date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>" class="form-control font-weight-bold">
      </div>

      <!-- 2. To Date -->
      <div class="mp-f">
        <label><i class="fas fa-calendar-alt text-primary"></i> <?php echo htmlspecialchars(t('import_list.filter_to_date', 'ຫາວັນທີ:')); ?></label>
        <input type="date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>" class="form-control font-weight-bold">
      </div>

      <!-- 3. Branch / Store Selector — main branch / admin only, and only worth showing
           once there's more than one branch to pick from -->
      <?php if (($canAllBranches) && count($stores) > 1): ?>
      <div class="mp-f mp-f-wide">
        <label><i class="fas fa-store text-info"></i> <?php echo htmlspecialchars(t('import_list.filter_select_branch', 'ເລືອກສາຂາ:')); ?></label>
        <select name="store_id" class="form-control font-weight-bold border-info" style="background-color: #f0f9ff;" onchange="this.form.submit()">
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
      <div class="mp-f">
        <label><i class="fas fa-list text-success"></i> <?php echo htmlspecialchars(t('import_list.filter_report_type', 'ປະເພດລາຍງານ:')); ?></label>
        <select name="view_type" class="form-control font-weight-bold" onchange="this.form.submit()">
          <option value="bill" <?php echo ($view_type === 'bill') ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('import_list.opt_view_bill', 'ຈັດການໃບບິນ')); ?></option>
          <option value="detail" <?php echo ($view_type === 'detail') ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('import_list.opt_view_detail', 'ລາຍລະອຽດສິນຄ້າ')); ?></option>
        </select>
      </div>

      <!-- 5. Currency Selector — view the cost figures converted into a chosen currency -->
      <div class="mp-f mp-f-sm">
        <label><i class="fas fa-coins text-warning"></i> <?php echo htmlspecialchars(t('reports.currency_label', 'ສະກຸນເງິນ:')); ?></label>
        <select name="currency_filter" class="form-control font-weight-bold" style="border: 1.5px solid #a16207; color: #854d0e; background: #fefce8;" onchange="this.form.submit()">
          <option value=""><?php echo htmlspecialchars(t('home.default_currency_short', 'ຄ່າເລີ່ມຕົ້ນ')); ?></option>
          <?php foreach (getCurrencyMeta() as $__cc => $__ci): ?>
            <option value="<?php echo htmlspecialchars($__cc); ?>" <?php echo $currency_filter === $__cc ? 'selected' : ''; ?>>
              <?php /* ລະຫັດສະກຸນເງິນເທົ່ານັ້ນ (KIP → LAK) ບໍ່ມີສັນຍາລັກ */ echo htmlspecialchars($__cc === 'KIP' ? 'LAK' : $__cc); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- 6. Action Buttons -->
      <div class="mp-f-actions">
        <button type="submit" class="btn mp-btn-search shadow-sm">
          <i class="fas fa-search"></i> <?php echo htmlspecialchars(t('import_list.search_btn', 'ຄົ້ນຫາ')); ?>
        </button>
        <a href="import_list.php" class="btn btn-outline-secondary" title="<?php echo htmlspecialchars(t('import_list.clear_filter_title', 'ລ້າງຄ່າກັ່ນກອງ')); ?>">
          <i class="fas fa-sync-alt"></i>
        </a>
      </div>

    </form>
  </div>
</div>
