<?php
/*
 * ສ່ວນ: ສ່ວນສະແດງຜົນ
 * ຫົວໜ້າ, ກ່ອງກັ່ນຕອງ, ບັດສະຖິຕິ, ຍອດຕາມທະນາຄານ, ເງິນທີ່ຮັບຕາມສະກຸນ ແລະ ກ່ອງກຣາຟ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/reports/daily_report.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>  <!-- Header & Page Meta -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
    <div>
      <h5 class="font-weight-bold text-dark mb-1" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
        <i class="fas fa-calendar-day text-info"></i> <?php echo htmlspecialchars(t('reports.title_daily', 'ລາຍງານປະຈຳວັນ')); ?>
      </h5>
    </div>
  </div>

  <!-- Date Range Filter Box on Top (Compact Inline Row on PC, 2-Col Side-by-Side on Mobile) -->
  <div class="report-filter-box no-print mb-3.5">
    <form method="GET" action="daily_report.php" class="d-flex align-items-end flex-wrap flex-md-nowrap" style="gap: 10px;">
      <!-- From Date -->
      <div class="report-filter-date-col">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-calendar-alt text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.from_date', 'ຕັ້ງແຕ່ວັນທີ:')); ?>
        </label>
        <input type="date" name="from_date" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($from_date); ?>" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #cbd5e1;">
      </div>

      <!-- To Date -->
      <div class="report-filter-date-col">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-calendar-check text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.to_date', 'ຫາວັນທີ:')); ?>
        </label>
        <input type="date" name="to_date" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($to_date); ?>" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #cbd5e1;">
      </div>

      <!-- Branch Filter Dropdown — only worth showing once there's more than one branch -->
      <?php if (canViewAllBranches($pdo) && count($branchesList) > 1): ?>
      <div class="report-filter-date-col" style="min-width: 170px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-store text-info mr-1"></i> <?php echo htmlspecialchars(t('reports.branch_label', 'ສາຂາ:')); ?>
        </label>
        <select name="store_id" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #cbd5e1;" onchange="this.form.submit()">
          <?php foreach ($branchesList as $b): ?>
            <option value="<?php echo $b['store_id']; ?>" <?php echo ($filter_store_id == $b['store_id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($b['store_name']); ?> <?php echo !empty($b['is_main']) ? htmlspecialchars(t('reports.main_branch_suffix', '(ສາຂາໃຫຍ່)')) : ''; ?>
            </option>
          <?php endforeach; ?>
          <option value="0" <?php echo ((int)$filter_store_id === 0) ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('reports.all_branches_opt', '-- ທຸກສາຂາ --')); ?></option>
        </select>
      </div>
      <?php else: ?>
        <input type="hidden" name="store_id" value="0">
      <?php endif; ?>

<?php $shiftSelBoxStyle = 'flex: 1 1 150px; width: 100%;'; require dirname(__DIR__) . '/shift_filter_select.php'; /* ບັອກເລືອກກະ — ສາຂາທີ່ບໍ່ມີກະ: ເຊື່ອງ */ ?>

      <!-- Currency Filter Dropdown - ບັອກກະທັດຮັດ ບໍ່ຍາວໃນທຸກໜ້າຈໍ -->
      <div style="flex: 0 0 140px; width: 140px; max-width: 140px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-coins text-warning mr-1"></i> <?php echo htmlspecialchars(t('reports.currency_label', 'ສະກຸນເງິນ:')); ?>
        </label>
        <select name="currency_filter" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #a16207; color: #854d0e; background: #fefce8; width: 140px; max-width: 140px !important;" onchange="this.form.submit()">
          <option value=""><?php echo htmlspecialchars(t('reports.all_currencies_opt', '-- ທຸກສະກຸນເງິນ --')); ?></option>
          <?php foreach (getCurrencyMeta() as $__curCode => $__curInfo): ?>
            <option value="<?php echo htmlspecialchars($__curCode); ?>" <?php echo $currency_filter === $__curCode ? 'selected' : ''; ?>>
              <?php /* ລະຫັດສະກຸນເງິນເທົ່ານັ້ນ (KIP → LAK) ບໍ່ມີສັນຍາລັກ */ echo htmlspecialchars($__curCode === 'KIP' ? 'LAK' : $__curCode); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Search & Refresh Buttons -->
      <div class="d-flex align-items-center" style="gap: 6px; flex: 0 0 auto;">
        <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-3.5 d-inline-flex align-items-center justify-content-center" style="border-radius: 8px; height: 38px; background: linear-gradient(135deg, #2c5aa0, #244886); white-space: nowrap; font-size: 0.85rem;">
          <i class="fas fa-search mr-1.5"></i> <?php echo htmlspecialchars(t('reports.search_btn', 'ຄົ້ນຫາ')); ?>
        </button>
        <a href="daily_report.php" class="btn btn-light btn-sm border font-weight-bold d-inline-flex align-items-center justify-content-center px-2.5" title="<?php echo htmlspecialchars(t('reports.clear', 'ລ້າງຄ່າ')); ?>" style="border-radius: 8px; height: 38px;">
          <i class="fas fa-redo"></i>
        </a>
      </div>
    </form>
  </div>

  <!-- 10 Summary KPI Cards Box -->
  <div class="mb-4 kpi-cards-grid">
    <!-- 1. ລວມຍອດ -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.kpi_gross_total', 'ລວມຍອດ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" data-target="<?php echo number_format(convertFromKip($daily_gross_sales, $__dc['code'], $__dc['rates']), $__dec, '.', ''); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-file-invoice-dollar"></i></div>
    </div>

    <!-- 2. ສ່ວນຫຼຸດສິນຄ້າ -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.kpi_item_discount', 'ສ່ວນຫຼຸດສິນຄ້າ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" data-target="<?php echo number_format(convertFromKip($daily_item_discounts, $__dc['code'], $__dc['rates']), $__dec, '.', ''); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-tag"></i></div>
    </div>

    <!-- 3. ສ່ວນຫຼຸດໃບບິນ -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.kpi_bill_discount', 'ສ່ວນຫຼຸດໃບບິນ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" data-target="<?php echo number_format(convertFromKip($daily_bill_discounts, $__dc['code'], $__dc['rates']), $__dec, '.', ''); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-receipt"></i></div>
    </div>

    <!-- 4. ໂປຣໂມຊັ່ນ & ຂອງແຖມ (Promotion Summary Block) -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title text-nowrap" style="white-space: nowrap !important;"><i class="fas fa-bullhorn mr-1"></i> <?php echo htmlspecialchars(t('reports.kpi_promotion', 'ໂປຣໂມຊັ່ນ')); ?> <?php if ($daily_promo_gifts_count > 0): ?><span class="text-warning font-weight-normal ml-1" style="font-size: 0.75rem;">(<?php echo htmlspecialchars(t('reports.gift_label', 'ແຖມ')); ?>: <?php echo number_format($daily_promo_gifts_count); ?>)</span><?php endif; ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val text-nowrap" style="white-space: nowrap !important; font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" data-target="<?php echo number_format(convertFromKip($daily_promo_discounts, $__dc['code'], $__dc['rates']), $__dec, '.', ''); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-gift"></i></div>
    </div>

    <!-- 4. ສ່ວນຫຼຸດທັງໝົດ -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.kpi_total_discount', 'ສ່ວນຫຼຸດທັງໝົດ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" data-target="<?php echo number_format(convertFromKip($daily_total_discounts, $__dc['code'], $__dc['rates']), $__dec, '.', ''); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-tags"></i></div>
    </div>

    <!-- 5. ຍອດຂາຍສຸດທິ -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #10b981 0%, #047857 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.kpi_net_sales', 'ຍອດຂາຍສຸດທິ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" data-target="<?php echo number_format(convertFromKip($daily_net_sales, $__dc['code'], $__dc['rates']), $__dec, '.', ''); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-coins"></i></div>
    </div>

    <!-- 6. ເງິນສົດ -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.cash_opt', 'ເງິນສົດ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" data-target="<?php echo number_format(convertFromKip($daily_cash_payments, $__dc['code'], $__dc['rates']), $__dec, '.', ''); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-money-bill-wave"></i></div>
    </div>

    <!-- 7. ເງິນໂອນ -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #06b6d4 0%, #0e7490 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.kpi_transfer', 'ເງິນໂອນ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" data-target="<?php echo number_format(convertFromKip($daily_qr_payments, $__dc['code'], $__dc['rates']), $__dec, '.', ''); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-qrcode"></i></div>
    </div>

    <!-- 8. ເງິນໄດ້ຮັບຕົວຈິງ = ເງິນສົດ + ເງິນໂອນ (unpaid credit sales are not in it; debt repayments are) -->
    <?php $daily_received_total = $daily_cash_payments + $daily_qr_payments; ?>
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #16a34a 0%, #166534 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.kpi_actual_received', 'ເງິນໄດ້ຮັບຕົວຈິງ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" data-target="<?php echo number_format(convertFromKip($daily_received_total, $__dc['code'], $__dc['rates']), $__dec, '.', ''); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
        </div>
        <div style="font-size: 0.72rem; color: rgba(255,255,255,.88); white-space: nowrap;"><?php echo htmlspecialchars(t('reports.kpi_actual_received_hint', 'ເງິນສົດ + ເງິນໂອນ')); ?></div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-hand-holding-usd"></i></div>
    </div>

    <!-- 9. ບິນ -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.bills_unit', 'ບິນ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <?php $u_bills = htmlspecialchars(t('reports.bills_unit', 'ບິນ')); ?>
          <span class="counter-num" data-suffix-key="reports.bills_unit" data-target="<?php echo (int)$daily_bills_count; ?>" data-suffix=" <?php echo $u_bills; ?>">0 <?php echo $u_bills; ?></span>
        </div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-file-invoice"></i></div>
    </div>

    <!-- 10. ລາຍການສິນຄ້າ — how many different products were sold -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.kpi_products_sold', 'ລາຍການສິນຄ້າ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <?php $u_lines = htmlspecialchars(t('reports.items_unit', 'ລາຍການ')); ?>
          <span class="counter-num" data-suffix-key="reports.items_unit" data-target="<?php echo (int)$daily_products_count; ?>" data-suffix=" <?php echo $u_lines; ?>">0 <?php echo $u_lines; ?></span>
        </div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-list-ul"></i></div>
    </div>

    <!-- 11. ຈຳນວນ — total pieces sold -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.kpi_qty_sold', 'ຈຳນວນ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <?php $u_pcs = htmlspecialchars(t('reports.unit_pieces', 'ຊິ້ນ')); ?>
          <span class="counter-num" data-suffix-key="reports.unit_pieces" data-target="<?php echo (int)$daily_items_qty; ?>" data-suffix=" <?php echo $u_pcs; ?>">0 <?php echo $u_pcs; ?></span>
        </div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-boxes"></i></div>
    </div>
  </div>

  <!-- Bank Revenue Breakdown Row -->
  <?php if (!empty($bank_daily_breakdown)): ?>
    <div class="card border-0 shadow-sm mb-4 w-100" style="border-radius: 12px; background: #f8fafc; border: 1.5px solid #e2e8f0;">
      <div class="card-body p-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <h6 class="font-weight-bold text-dark mb-0">
            <i class="fas fa-university text-primary mr-1.5"></i> <?php echo htmlspecialchars(t('reports.bank_breakdown_title', 'ຍອດຮັບເງິນໂອນແຍກຕາມທະນາຄານ')); ?>
          </h6>
          <span class="badge badge-primary font-weight-bold px-2.5 py-1" style="font-size: 0.82rem;"><?php echo htmlspecialchars(t('reports.total_label', 'ລວມ:')); ?> <?php echo displayMoney($pdo, $bank_breakdown_total); ?></span>
        </div>
        <div class="row" style="row-gap: 12px;">
          <?php foreach ($bank_daily_breakdown as $bRow): ?>
            <?php 
              $bAccId = intval($bRow['bank_acc_id']);
              $bInfo = $bank_accounts_map[$bAccId] ?? [];
              $label = !empty($bInfo['bank_name']) ? $bInfo['bank_name'] : (!empty($bRow['bank_label']) && $bRow['bank_label'] !== 'ບໍ່ໄດ້ລະບຸ' ? $bRow['bank_label'] : tl('ບໍ່ໄດ້ລະບຸ'));
              if ($label === 'ບໍ່ໄດ້ລະບຸ' && !empty($bank_accounts_map)) {
                  $firstBank = reset($bank_accounts_map);
                  $label = $firstBank['bank_name'];
                  $bInfo = $firstBank;
              }
              $bCode = $bInfo['bank_code'] ?? $label;
              $code = strtoupper(trim($bCode));
              $bColor = ($code === 'BCEL' || stripos($label, 'BCEL') !== false) ? '#002d72' : (($code === 'LDB' || stripos($label, 'LDB') !== false) ? '#047857' : (($code === 'JDB' || stripos($label, 'JDB') !== false) ? '#6b21a8' : (($code === 'STB' || stripos($label, 'ST') !== false) ? '#ea580c' : '#0284c7')));
              $logoFile = $bInfo['bank_logo'] ?? '';
              // No logo on file (never uploaded, or removed in Bank settings) → a bank icon in the
              // bank's colour, not a guessed <code>.svg that doesn't exist and 404s on every load.
              $bLogo = (!empty($logoFile) && file_exists(dirname(__DIR__, 2) . '/../../assets/img/banks/' . basename($logoFile)))
                       ? ('../../assets/img/banks/' . basename($logoFile))
                       : '';
              $bLogoFallback = '<span class="bank-logo-fallback" style="width: 32px; height: 32px; border-radius: 50%; background: ' . $bColor . '; color: #fff; display: ' . ($bLogo === '' ? 'inline-flex' : 'none') . '; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0;"><i class="fas fa-university"></i></span>';
            ?>
            <div class="col-lg-3 col-md-4 col-sm-6">
              <div class="px-2.5 py-1.5 rounded border bg-white d-flex align-items-center justify-content-between shadow-2xs h-100" style="border: 1px solid #e2e8f0; min-height: 44px;">
                <div class="d-flex align-items-center" style="gap: 8px;">
                  <?php if ($bLogo !== ''): ?>
                  <img src="<?php echo htmlspecialchars($bLogo); ?>" style="width: 32px; height: 32px; object-fit: cover; border-radius: 50% !important; padding: 1px; border: 1.5px solid #cbd5e1; background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,0.08);" onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                  <?php endif; ?>
                  <?php echo $bLogoFallback; ?>
                  <div>
                    <div class="font-weight-bold text-dark" style="font-size: 0.82rem; line-height: 1.1;"><?php echo htmlspecialchars($label); ?></div>
                    <small class="text-muted font-weight-bold" style="font-size: 0.72rem;"><?php echo number_format($bRow['total_tx']); ?> <?php echo htmlspecialchars(t('reports.bills_unit', 'ບິນ')); ?></small>
                  </div>
                </div>
                <div class="font-weight-bold text-right pl-1" style="font-size: 0.92rem; color: <?php echo $bColor; ?>;">
                  <?php echo displayMoney($pdo, $bRow['total_received']); ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Money Received by Tender Currency -->
  <?php if (!empty($currency_breakdown)): ?>
    <?php $__curMeta = function_exists('getCurrencyMeta') ? getCurrencyMeta() : []; ?>
    <div class="card border-0 shadow-sm mb-4 w-100" style="border-radius: 12px; background: #f8fafc; border: 1.5px solid #e2e8f0;">
      <div class="card-body" style="padding: 16px 18px;">
        <h6 class="font-weight-bold text-dark mb-3">
          <i class="fas fa-coins text-warning mr-1.5"></i> <?php echo htmlspecialchars(t('reports.currency_breakdown_title', 'ຮັບເງິນແຍກຕາມສະກຸນເງິນ')); ?>
        </h6>
        <div style="display: flex; flex-wrap: wrap; gap: 12px;">
          <?php foreach ($currency_breakdown as $cRow): ?>
            <?php
              $cCode = strtoupper($cRow['cur'] ?? 'KIP');
              $cName = $__curMeta[$cCode]['name'] ?? $cCode;
              $cSym  = $__curMeta[$cCode]['symbol'] ?? $cCode;
              $cDec  = (int)($__curMeta[$cCode]['decimals'] ?? ($cCode === 'KIP' ? 0 : 2));
              $cColor = ($cCode === 'THB') ? '#0369a1' : (($cCode === 'USD') ? '#047857' : (($cCode === 'CNY') ? '#b91c1c' : '#a16207'));
            ?>
            <div style="flex: 1 1 200px; min-width: 190px; max-width: 300px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px;">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="font-weight-bold text-dark" style="font-size: 0.9rem;">
                  <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: <?php echo $cColor; ?>; margin-right: 6px; vertical-align: middle;"></span>
                  <?php echo htmlspecialchars($cName); ?><?php if ($cSym !== $cName): ?> <span class="text-muted">(<?php echo htmlspecialchars($cSym); ?>)</span><?php endif; ?>
                </span>
                <span class="badge badge-light border text-muted font-weight-bold" style="font-size: 0.7rem;"><?php echo number_format($cRow['bills']); ?> <?php echo htmlspecialchars(t('reports.bills_unit', 'ບິນ')); ?></span>
              </div>
              <div class="font-weight-bold" style="font-size: 1.15rem; line-height: 1.2; color: <?php echo $cColor; ?>;">
                <?php echo number_format((float)$cRow['total_foreign'], $cDec); ?> <?php echo htmlspecialchars($cSym); ?>
              </div>
              <?php if ($cCode !== $__dc['code']): ?>
                <div class="text-muted font-weight-bold" style="font-size: 0.76rem; margin-top: 3px;">≈ <?php echo displayMoney($pdo, $cRow['total_kip']); ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Charts Section (always shown) -->
  <?php
    // Prepare chart data (always, even if empty)
    $chartLabels = [];
    $chartNet    = [];
    $chartCash   = [];
    $chartQr     = [];
    $chartTip    = [];
    $chartBills  = [];
    foreach (($perDayRows ?? []) as $row) {
      $chartLabels[] = !empty($row['day_date']) ? date('d/m/Y', strtotime($row['day_date'])) : '';
      $chartNet[]    = (float)$row['day_net'];
      $chartCash[]   = (float)$row['day_cash'];
      $chartQr[]     = (float)$row['day_qr'];
      $chartTip[]    = (float)$row['day_tip'];
      $chartBills[]  = (int)$row['day_bills'];
    }
    // If no data, use today as placeholder with 0
    if (empty($chartLabels)) {
      $chartLabels = [date('d/m/Y')];
      $chartNet = $chartCash = $chartQr = $chartTip = $chartBills = [0];
    }
    $totalCash = array_sum($chartCash);
    $totalQr   = array_sum($chartQr);
    $totalTip  = array_sum($chartTip);
  ?>

  <div class="row mt-3" style="gap: 0;">
    <!-- Bar Chart: ຍອດຂາຍສຸດທິລາຍວັນ -->
    <div class="col-12 col-md-8 mb-3">
      <div class="card border-0 shadow-sm" style="border-radius: 14px; padding: 18px 20px;">
        <div class="font-weight-bold text-dark mb-3" style="font-size: 0.92rem;">
          <i class="fas fa-chart-bar text-primary mr-2"></i> <?php echo htmlspecialchars(t('reports.chart_daily_net_sales', 'ຍອດຂາຍສຸດທິລາຍວັນ')); ?>
        </div>
        <?php /* ກ່ອງກຳນົດຄວາມສູງ (ໂທລະສັບສູງຂຶ້ນ — .daily-sales-chart-box ໃນ themes/reports.css) */ ?>
        <div class="daily-sales-chart-box" style="position: relative; height: 240px;"><canvas id="chartDailySales"></canvas></div>
      </div>
    </div>

    <!-- Donut Chart: ສັດສ່ວນການຊຳລະ -->
    <div class="col-12 col-md-4 mb-3">
      <div class="card border-0 shadow-sm" style="border-radius: 14px; padding: 18px 20px; height: 100%;">
        <div class="font-weight-bold text-dark mb-3" style="font-size: 0.92rem;">
          <i class="fas fa-chart-pie text-success mr-2"></i> <?php echo htmlspecialchars(t('reports.chart_payment_ratio', 'ສັດສ່ວນການຊຳລະ')); ?>
        </div>
        <canvas id="chartPaymentMethod" style="max-height: 200px;"></canvas>
        <!-- Legend -->
        <div class="mt-3 d-flex flex-wrap justify-content-center" style="gap: 10px;">
          <div class="d-flex align-items-center" style="gap: 6px; font-size: 0.8rem;">
            <span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:#6366f1;"></span>
            <span class="font-weight-bold"><?php echo htmlspecialchars(t('reports.cash_opt', 'ເງິນສົດ')); ?></span>
            <span class="text-muted"><?php echo displayMoney($pdo, $totalCash); ?></span>
          </div>
          <div class="d-flex align-items-center" style="gap: 6px; font-size: 0.8rem;">
            <span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:#06b6d4;"></span>
            <span class="font-weight-bold"><?php echo htmlspecialchars(t('reports.col_transfer', 'ໂອນ')); ?></span>
            <span class="text-muted"><?php echo displayMoney($pdo, $totalQr); ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bill Count Line Chart -->
  <div class="card border-0 shadow-sm mb-4" style="border-radius: 14px; padding: 18px 20px;">
    <div class="font-weight-bold text-dark mb-3" style="font-size: 0.92rem;">
      <i class="fas fa-chart-line text-warning mr-2"></i> <?php echo htmlspecialchars(t('reports.chart_daily_bill_count', 'ຈຳນວນບິນລາຍວັນ')); ?>
    </div>
    <canvas id="chartDailyBills" style="max-height: 160px;"></canvas>
  </div>

</div>

