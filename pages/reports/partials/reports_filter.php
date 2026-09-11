<?php if ($type === 'daily'): ?>
  <!-- Daily Report Date Filter Control Box (ON TOP ABOVE CARDS) -->
  <div class="report-filter-box no-print mb-3.5" style="padding: 14px 18px; border-radius: 12px; background: #ffffff; border: 1.5px solid #e2e8f0; box-shadow: 0 3px 12px rgba(0,0,0,0.03);">
    <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="d-flex align-items-end flex-wrap" style="gap: 14px;">
      <input type="hidden" name="type" value="daily">

      <!-- From Date -->
      <div style="flex: 0 0 auto; min-width: 170px;">
        <label class="font-weight-bold text-dark mb-1.5 d-block" style="font-size: 0.84rem; white-space: nowrap;">
          <i class="fas fa-calendar-alt text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.from_date', 'ຕັ້ງແຕ່ວັນທີ:')); ?>
        </label>
        <input type="date" name="from_date" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($from_date); ?>" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #cbd5e1;">
      </div>

      <!-- To Date -->
      <div style="flex: 0 0 auto; min-width: 170px;">
        <label class="font-weight-bold text-dark mb-1.5 d-block" style="font-size: 0.84rem; white-space: nowrap;">
          <i class="fas fa-calendar-check text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.to_date', 'ຫາວັນທີ:')); ?>
        </label>
        <input type="date" name="to_date" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($to_date); ?>" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #cbd5e1;">
      </div>

      <!-- Search & Refresh Buttons -->
      <div class="d-flex align-items-center flex-wrap" style="flex: 0 1 auto; gap: 6px; row-gap: 8px;">
        <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-3.5 d-inline-flex align-items-center justify-content-center" style="border-radius: 8px; height: 38px; background: linear-gradient(135deg, #2c5aa0, #244886); white-space: nowrap; font-size: 0.85rem;">
          <i class="fas fa-search mr-1.5"></i> <?php echo htmlspecialchars(t('reports.search_btn', 'ຄົ້ນຫາ')); ?>
        </button>
        <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?type=daily" class="btn btn-light btn-sm border font-weight-bold d-inline-flex align-items-center justify-content-center px-2.5" title="<?php echo htmlspecialchars(t('reports.clear', 'ລ້າງຄ່າ')); ?>" style="border-radius: 8px; height: 38px;">
          <i class="fas fa-redo"></i>
        </a>
      </div>
    </form>
  </div>
<?php else: ?>
  <!-- Regular Search & Filter Controls Box (Full Width - Single Inline Row) -->
  <div class="report-filter-box no-print mb-3" style="padding: 12px 16px; width: 100%; box-sizing: border-box;">
    <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="reportFilterForm" style="display: flex; align-items: flex-end; flex-wrap: wrap; gap: 8px; width: 100%;">
      <input type="hidden" name="type" value="<?php echo htmlspecialchars($type ?: 'all_sales'); ?>">

      <!-- From Date -->
      <div style="flex: 1 1 0; min-width: 120px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-calendar-alt text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.from_date', 'ຕັ້ງແຕ່ວັນທີ:')); ?>
        </label>
        <input type="date" name="from_date" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($from_date); ?>" style="border-radius: 8px; height: 38px; font-size: 0.85rem; width: 100%;">
      </div>

      <!-- To Date -->
      <div style="flex: 1 1 0; min-width: 120px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-calendar-check text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.to_date', 'ຫາວັນທີ:')); ?>
        </label>
        <input type="date" name="to_date" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($to_date); ?>" style="border-radius: 8px; height: 38px; font-size: 0.85rem; width: 100%;">
      </div>

      <!-- Search Input -->
      <div style="flex: 2 1 0; min-width: 160px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-search text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.search_bill_seller_label', 'ຄົ້ນຫາ (ເລກບິນ / ຜູ້ຂາຍ):')); ?>
        </label>
        <input type="text" name="search" id="reportSearchInput" class="form-control form-control-sm" placeholder="<?php echo htmlspecialchars(t('reports.search_placeholder_bill_emp', 'ປ້ອນເລກບິນ ຫຼື ຊື່ພະນັກງານ...')); ?>" value="<?php echo htmlspecialchars($search); ?>" autocomplete="off" style="border-radius: 8px; height: 38px; font-size: 0.85rem; width: 100%;">
      </div>

      <!-- Branch Store Filter Dropdown (Main branch / Admin / anyone granted the report
           permission) — only worth showing once there's more than one branch to pick from -->
      <?php if (!empty($reportsCrossBranch) && count($branchesList) > 1): ?>
        <div style="flex: 1 1 0; min-width: 140px;">
          <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
            <i class="fas fa-store text-info mr-1"></i> <?php echo htmlspecialchars(t('reports.branch_label', 'ສາຂາ:')); ?>
          </label>
          <select name="store_id" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #059669; color: #047857; background: #ecfdf5; width: 100%;">
            <option value="0"><?php echo htmlspecialchars(t('reports.all_branches_opt', '-- ທຸກສາຂາ --')); ?></option>
            <?php foreach ($branchesList as $b): ?>
              <option value="<?php echo $b['store_id']; ?>" <?php echo ($filter_store_id == $b['store_id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($b['store_name']); ?> <?php echo !empty($b['is_main']) ? htmlspecialchars(t('reports.main_branch_suffix', '(ສາຂາໃຫຍ່)')) : ''; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php else: ?>
        <input type="hidden" name="store_id" value="<?php echo $reportsCrossBranch ? 0 : $userStoreId; ?>">
      <?php endif; ?>

      <!-- Bank Account Filter Dropdown -->
      <div style="flex: 1 1 0; min-width: 150px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-university text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.bank_payment_label', 'ທະນາຄານ / ຊຳລະ:')); ?>
        </label>
        <select name="bank_filter" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #0284c7; color: #0369a1; background: #f0f9ff; width: 100%;">
          <option value=""><?php echo htmlspecialchars(t('reports.all_payment_opt', '-- ຊຳລະທັງໝົດ --')); ?></option>
          <option value="cash" <?php echo ($bank_filter ?? '') === 'cash' ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('reports.cash_opt', 'ເງິນສົດ')); ?></option>
          <option value="transfer" <?php echo ($bank_filter ?? '') === 'transfer' ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('reports.transfer_opt', 'ເງິນໂອນ')); ?></option>
          <?php if (!empty($bank_accounts)): ?>
            <optgroup label="<?php echo htmlspecialchars(t('reports.by_bank_group', 'ແຍກຕາມບັນຊີທະນາຄານ')); ?>">
              <?php foreach ($bank_accounts as $bAcc): ?>
                <option value="<?php echo $bAcc['id']; ?>" <?php echo (string)($bank_filter ?? '') === (string)$bAcc['id'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($bAcc['bank_name']); ?>
                </option>
              <?php endforeach; ?>
            </optgroup>
          <?php endif; ?>
        </select>
      </div>

      <!-- Currency Filter Dropdown -->
      <div style="flex: 1 1 0; min-width: 140px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-coins text-warning mr-1"></i> <?php echo htmlspecialchars(t('reports.currency_label', 'ສະກຸນເງິນ:')); ?>
        </label>
        <select name="currency_filter" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #a16207; color: #854d0e; background: #fefce8; width: 100%;">
          <option value=""><?php echo htmlspecialchars(t('reports.all_currencies_opt', '-- ທຸກສະກຸນເງິນ --')); ?></option>
          <?php foreach (getCurrencyMeta() as $__curCode => $__curInfo): ?>
            <option value="<?php echo htmlspecialchars($__curCode); ?>" <?php echo ($currency_filter ?? '') === $__curCode ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($__curInfo['name'] . ' (' . $__curInfo['symbol'] . ')'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- View Mode Dropdown -->
      <div style="flex: 1 1 0; min-width: 140px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-list text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.view_mode_label', 'ຮູບແບບການສະແດງ:')); ?>
        </label>
        <select name="view_mode" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #2563eb; color: #1e40af; background: #eff6ff; width: 100%;">
          <option value="invoice" <?php echo $view_mode === 'invoice' ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('reports.by_invoice_opt', 'ສະແດງຕາມບິນ')); ?></option>
          <option value="item" <?php echo $view_mode === 'item' ? 'selected' : ''; ?>><?php echo $type === 'all_sales' ? htmlspecialchars(t('reports.item_list_opt', 'ລາຍການສິນຄ້າ')) : htmlspecialchars(t('reports.detail_opt', 'ລາຍລະອຽດ')); ?></option>
        </select>
      </div>

      <!-- Per Page Dropdown -->
      <div style="flex: 1 1 0; min-width: 100px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-layer-group text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.per_page_label', 'ໂຊລາຍການ:')); ?>
        </label>
        <select name="per_page" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()" style="border-radius: 8px; height: 38px; font-size: 0.85rem; background: #ffffff; border: 1.5px solid #cbd5e1; width: 100%;">
          <option value="5" <?php echo $per_page_raw === '5' ? 'selected' : ''; ?>>5</option>
          <option value="10" <?php echo $per_page_raw === '10' ? 'selected' : ''; ?>>10</option>
          <option value="25" <?php echo $per_page_raw === '25' ? 'selected' : ''; ?>>25</option>
          <option value="50" <?php echo $per_page_raw === '50' ? 'selected' : ''; ?>>50</option>
          <option value="100" <?php echo $per_page_raw === '100' ? 'selected' : ''; ?>>100</option>
          <option value="all" <?php echo $per_page_raw === 'all' ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('reports.all_opt', 'ທັງໝົດ')); ?></option>
        </select>
      </div>

      <!-- Action Buttons -->
      <div class="d-flex align-items-center flex-wrap" style="flex: 0 1 auto; gap: 6px; row-gap: 8px;">
        <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-3 d-inline-flex align-items-center justify-content-center" style="border-radius: 8px; height: 38px; background: linear-gradient(135deg, #2c5aa0, #244886); white-space: nowrap; font-size: 0.85rem;">
          <i class="fas fa-search mr-1"></i> <?php echo htmlspecialchars(t('reports.search_btn', 'ຄົ້ນຫາ')); ?>
        </button>
        <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?type=<?php echo htmlspecialchars($type ?: 'all_sales'); ?>&view_mode=<?php echo htmlspecialchars($view_mode); ?>" class="btn btn-light btn-sm border font-weight-bold d-inline-flex align-items-center justify-content-center px-2" title="<?php echo htmlspecialchars(t('reports.clear', 'ລ້າງຄ່າ')); ?>" style="border-radius: 8px; height: 38px;">
          <i class="fas fa-redo"></i>
        </a>
      </div>
    </form>
  </div>
<?php endif; ?>
