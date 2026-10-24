<?php
/*
 * ສ່ວນ: ສ່ວນສະແດງຜົນ: ຕົວກັ່ນຕອງ, ບັດສະຖິຕິ ແລະ ກ່ອງກຣາຟ
 * ຫົວໜ້າ, ກ່ອງເລືອກສາຂາ/ປີ/ຊ່ວງເວລາ/ສະກຸນເງິນ, 5 ບັດສະຖິຕິ ແລະ 4 ກ່ອງ canvas ຂອງກຣາຟ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ home/home.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>  <!-- Header Title & Controls -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3.5 gap-2">
    <div>
      <h4 class="font-weight-bold text-dark mb-1" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
        <i class="fas fa-chart-pie text-primary mr-2"></i> <?php echo htmlspecialchars(t('home.title', 'ດາດສ໌ບອດບໍລິຫານ & ວິເຄາະການຂາຍ')); ?>
      </h4>
      <p class="text-muted mb-0" style="font-size: 0.85rem;"><?php echo htmlspecialchars(t('home.subtitle', 'ສະຫຼຸບພາບລວມຍອດຂາຍ, ຕົ້ນທຶນ, ກຳໄລ ແລະ ສະຖິຕິການຂາຍປະຈຳປີ')); ?></p>
    </div>
  </div>

  <!-- Modern Filter Box: Date Range + Dynamic Auto Year Selector -->
  <div class="report-filter-box no-print mb-4" style="padding: 14px 18px; border-radius: 12px; background: #ffffff; border: 1.5px solid #e2e8f0; box-shadow: 0 3px 12px rgba(0,0,0,0.03);">
    <form method="GET" action="home.php" class="d-flex align-items-end flex-wrap" style="gap: 12px;">
      <!-- Branch / Store Selector — only worth showing once there's more than one branch -->
      <?php if (canViewAllBranches($pdo) && count($stores_list) > 1): ?>
      <div style="flex: 1 1 170px; min-width: 150px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-store text-success mr-1"></i> <?php echo htmlspecialchars(t('home.select_branch', 'ເລືອກສາຂາ:')); ?>
        </label>
        <select name="store_id" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit();" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #10b981; color: #047857; background: #ecfdf5;">
          <?php foreach ($stores_list as $st): ?>
            <option value="<?php echo $st['store_id']; ?>" <?php echo ($selected_store == $st['store_id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($st['store_name']); ?><?php echo (!empty($st['is_main']) ? htmlspecialchars(t('home.main_branch_suffix', ' (ສາຂາໃຫຍ່)')) : ''); ?>
            </option>
          <?php endforeach; ?>
          <option value="0" <?php echo ($selected_store === 0) ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('home.all_branches', '-- ທຸກສາຂາ --')); ?></option>
        </select>
      </div>
      <?php else: ?>
        <input type="hidden" name="store_id" value="0">
      <?php endif; ?>

      <!-- Year Select (Auto-Generated) -->
      <div style="flex: 0 0 auto;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-calendar-check text-primary mr-1"></i> <?php echo htmlspecialchars(t('home.select_year', 'ເລືອກປີ:')); ?>
        </label>
        <select name="year" class="form-control form-control-sm font-weight-bold home-year-select" onchange="this.form.from_date.value=''; this.form.to_date.value=''; this.form.submit();" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #2563eb; color: #1e40af; background: #eff6ff;">
          <?php foreach ($available_years as $yr): ?>
            <option value="<?php echo $yr; ?>" <?php echo ($selected_year == $yr) ? 'selected' : ''; ?>><?php echo htmlspecialchars(sprintf(t('home.year_label', 'ປີ %d'), $yr)); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Quick Period Buttons: ມື້ນີ້ / ເດືອນນີ້ / ທັງປີ -->
      <div style="flex: 0 0 auto;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-clock text-primary mr-1"></i> <span data-i18n-key="home.period_label"><?php echo htmlspecialchars(t('home.period_label', 'ຊ່ວງເວລາ:')); ?></span>
        </label>
        <div class="home-period-group">
          <?php foreach (['today' => ['home.period_today', 'ມື້ນີ້'], 'month' => ['home.period_month', 'ເດືອນນີ້'], 'year' => ['home.period_year', 'ທັງປີ']] as $__pk => $__pl): ?>
            <button type="button" class="home-period-btn<?php echo $active_period === $__pk ? ' active' : ''; ?>" onclick="homeSetPeriod('<?php echo $__pk; ?>', this.form)">
              <span data-i18n-key="<?php echo $__pl[0]; ?>"><?php echo htmlspecialchars(t($__pl[0], $__pl[1])); ?></span>
            </button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- From Date -->
      <div style="flex: 1 1 150px; min-width: 140px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-calendar-alt text-primary mr-1"></i> <?php echo htmlspecialchars(t('home.from_date', 'ຕັ້ງແຕ່ວັນທີ:')); ?>
        </label>
        <input type="date" name="from_date" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($from_date); ?>" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #cbd5e1;">
      </div>

      <!-- To Date -->
      <div style="flex: 1 1 150px; min-width: 140px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-calendar-day text-primary mr-1"></i> <?php echo htmlspecialchars(t('home.to_date', 'ຫາວັນທີ:')); ?>
        </label>
        <input type="date" name="to_date" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($to_date); ?>" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #cbd5e1;">
      </div>

      <!-- Currency Selector — view the same totals converted into a chosen currency -->
      <div style="flex: 0 0 120px; width: 120px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-coins text-warning mr-1"></i> <?php echo htmlspecialchars(t('reports.currency_label', 'ສະກຸນເງິນ:')); ?>
        </label>
        <select name="currency_filter" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit();" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #a16207; color: #854d0e; background: #fefce8;">
          <option value=""><?php echo htmlspecialchars(t('home.default_currency_short', 'ຄ່າເລີ່ມຕົ້ນ')); ?></option>
          <?php foreach (getCurrencyMeta() as $__cc => $__ci): ?>
            <option value="<?php echo htmlspecialchars($__cc); ?>" <?php echo $currency_filter === $__cc ? 'selected' : ''; ?>>
              <?php /* ລະຫັດສະກຸນເງິນເທົ່ານັ້ນ (KIP → LAK) ບໍ່ມີສັນຍາລັກ */ echo htmlspecialchars($__cc === 'KIP' ? 'LAK' : $__cc); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Action Buttons -->
      <div class="d-flex align-items-center" style="flex: 0 0 auto; gap: 6px;">
        <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-3.5 d-inline-flex align-items-center justify-content-center" style="border-radius: 8px; height: 38px; background: linear-gradient(135deg, #2563eb, #1d4ed8); white-space: nowrap; font-size: 0.85rem;">
          <i class="fas fa-search mr-1.5"></i> <?php echo htmlspecialchars(t('home.search', 'ຄົ້ນຫາ')); ?>
        </button>
        <a href="home.php" class="btn btn-light btn-sm border font-weight-bold d-inline-flex align-items-center justify-content-center px-2.5" title="<?php echo htmlspecialchars(t('home.clear', 'ລ້າງຄ່າ')); ?>" style="border-radius: 8px; height: 38px;">
          <i class="fas fa-redo"></i>
        </a>
      </div>
    </form>
  </div>

  <!-- Row 1: 5 Key Metrics Cards (Modern Gradient & Animated CountUp) -->
  <div class="row mb-4">
    <!-- Card 1: Total Sales -->
    <div class="col-lg col-md-4 col-6 mb-3 mb-lg-0">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 14px; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: #ffffff;">
        <div class="card-body p-3 p-lg-3.5 position-relative overflow-hidden">
          <div class="d-flex justify-content-between align-items-start mb-1.5">
            <span class="text-white font-weight-bold metric-title"><?php echo htmlspecialchars(t('home.card_total_sales', 'ຍອດຂາຍທັງໝົດ')); ?></span>
            <div class="rounded-circle d-flex align-items-center justify-content-center metric-icon-box" style="background: rgba(255,255,255,0.22);">
              <i class="fas fa-cash-register text-white metric-icon"></i>
            </div>
          </div>
          <h4 class="font-weight-bold text-white mb-0" style="font-family: sans-serif; word-break: break-word; white-space: normal; line-height: 1.2;">
            <span class="metric-num counter-num" data-target="<?php echo $__hcvt((float)$total_sales); ?>" data-decimals="<?php echo $__hdec; ?>">0</span> <small class="metric-currency"><?php echo htmlspecialchars($__hsym); ?></small>
          </h4>
        </div>
      </div>
    </div>

    <!-- Card 2: Total Cost — ເຊື່ອງໄວ້ຖ້າຍັງບໍ່ມີສິນຄ້າໃດຕັ້ງລາຄາຊື້ (bprice) ໄວ້ເລີຍ, ເພື່ອບໍ່ໃຫ້
         ເບິ່ງຄືວ່າຕົ້ນທຶນແມ່ນ 0 ແທ້ໆ ທັງທີ່ຄວາມຈິງແມ່ນຍັງບໍ່ໄດ້ບັນທຶກ -->
    <?php if ($has_product_cost_data): ?>
    <div class="col-lg col-md-4 col-6 mb-3 mb-lg-0">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 14px; background: linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%); color: #ffffff;">
        <div class="card-body p-3 p-lg-3.5 position-relative overflow-hidden">
          <div class="d-flex justify-content-between align-items-start mb-1.5">
            <span class="text-white font-weight-bold metric-title"><?php echo htmlspecialchars(t('home.card_total_cost', 'ຕົ້ນທຶນສິນຄ້າທັງໝົດ')); ?></span>
            <div class="rounded-circle d-flex align-items-center justify-content-center metric-icon-box" style="background: rgba(255,255,255,0.22);">
              <i class="fas fa-boxes text-white metric-icon"></i>
            </div>
          </div>
          <h4 class="font-weight-bold text-white mb-0" style="font-family: sans-serif; word-break: break-word; white-space: normal; line-height: 1.2;">
            <span class="metric-num counter-num" data-target="<?php echo $__hcvt((float)$total_cost); ?>" data-decimals="<?php echo $__hdec; ?>">0</span> <small class="metric-currency"><?php echo htmlspecialchars($__hsym); ?></small>
          </h4>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Card 3: Cost of Goods Sold — ຕົ້ນທຶນການຊື້ສິນຄ້າ (ຕົ້ນທຶນຂອງລາຍການທີ່ຂາຍອອກຈິງ) -->
    <div class="col-lg col-md-4 col-6 mb-3 mb-lg-0">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 14px; background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%); color: #ffffff;">
        <div class="card-body p-3 p-lg-3.5 position-relative overflow-hidden">
          <div class="d-flex justify-content-between align-items-start mb-1.5">
            <span class="text-white font-weight-bold metric-title"><?php echo htmlspecialchars(t('home.card_cogs', 'ຕົ້ນທຶນການຂາຍ')); ?></span>
            <div class="rounded-circle d-flex align-items-center justify-content-center metric-icon-box" style="background: rgba(255,255,255,0.22);">
              <i class="fas fa-shopping-cart text-white metric-icon"></i>
            </div>
          </div>
          <h4 class="font-weight-bold text-white mb-0" style="font-family: sans-serif; word-break: break-word; white-space: normal; line-height: 1.2;">
            <span class="metric-num counter-num" data-target="<?php echo $__hcvt((float)$cogs); ?>" data-decimals="<?php echo $__hdec; ?>">0</span> <small class="metric-currency"><?php echo htmlspecialchars($__hsym); ?></small>
          </h4>
        </div>
      </div>
    </div>

    <!-- Card 4: Total Profit -->
    <div class="col-lg col-md-4 col-6 mb-3 mb-lg-0">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 14px; background: linear-gradient(135deg, #10b981 0%, #047857 100%); color: #ffffff;">
        <div class="card-body p-3 p-lg-3.5 position-relative overflow-hidden">
          <div class="d-flex justify-content-between align-items-start mb-1.5">
            <span class="text-white font-weight-bold metric-title"><?php echo htmlspecialchars(t('home.card_total_profit', 'ກຳໄລທັງໝົດ')); ?></span>
            <div class="rounded-circle d-flex align-items-center justify-content-center metric-icon-box" style="background: rgba(255,255,255,0.22);">
              <i class="fas fa-chart-line text-white metric-icon"></i>
            </div>
          </div>
          <h4 class="font-weight-bold text-white mb-0" style="font-family: sans-serif; word-break: break-word; white-space: normal; line-height: 1.2;">
            <span class="metric-num counter-num" data-target="<?php echo $__hcvt((float)$total_profit); ?>" data-decimals="<?php echo $__hdec; ?>">0</span> <small class="metric-currency"><?php echo htmlspecialchars($__hsym); ?></small>
          </h4>
        </div>
      </div>
    </div>

    <!-- Card 5: Profit Margin % -->
    <div class="col-lg col-md-4 col-6 mb-3 mb-lg-0">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 14px; background: linear-gradient(135deg, #f59e0b 0%, #b45309 100%); color: #ffffff;">
        <div class="card-body p-3 p-lg-3.5 position-relative overflow-hidden">
          <div class="d-flex justify-content-between align-items-start mb-1.5">
            <span class="text-white font-weight-bold metric-title"><?php echo htmlspecialchars(t('home.card_profit_pct', 'ກຳໄລ %')); ?></span>
            <div class="rounded-circle d-flex align-items-center justify-content-center metric-icon-box" style="background: rgba(255,255,255,0.22);">
              <i class="fas fa-percentage text-white metric-icon"></i>
            </div>
          </div>
          <h4 class="font-weight-bold text-white mb-0" style="font-family: sans-serif; word-break: break-word; white-space: normal; line-height: 1.2;">
            <span class="metric-num counter-num" data-target="<?php echo (float)$profit_pct; ?>" data-decimals="1">0</span>%
          </h4>
        </div>
      </div>
    </div>
  </div>

  <?php echo themeCssLink('home-body.css'); /* CSS ຍ້າຍໄປ themes/home-body.css */ ?>

  <!-- Row 2: 2 Primary Charts (Monthly Revenue for Selected Year & Yearly Sales Comparison) -->
  <div class="row mb-4">
    <!-- Chart 1: Monthly Revenue for Selected Year -->
    <div class="col-lg-8 mb-4 mb-lg-0">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 14px; background: #ffffff;">
        <div class="card-header bg-white border-0 py-3 px-3.5 d-flex justify-content-between align-items-center">
          <h6 class="font-weight-bold text-dark mb-0" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
            <i class="fas fa-chart-area text-primary mr-2"></i> <span data-i18n-key="<?php echo $chart_title_key; ?>"><?php echo htmlspecialchars(t($chart_title_key, $chart_title_def)); ?></span> (<?php echo htmlspecialchars($chart_period_text); ?>)
          </h6>
          <span class="badge badge-light border text-primary font-weight-bold px-2.5 py-1"><?php echo htmlspecialchars($chart_badge_text); ?></span>
        </div>
        <div class="card-body p-3">
          <div style="position: relative; height: 380px; width: 100%;">
            <canvas id="monthlyRevenueChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Chart 2: Yearly Sales Comparison -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 14px; background: #ffffff;">
        <div class="card-header bg-white border-0 py-3 px-3.5 d-flex justify-content-between align-items-center">
          <h6 class="font-weight-bold text-dark mb-0" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
            <i class="fas fa-calendar-alt text-info mr-2"></i> <?php echo htmlspecialchars(t('home.chart_yearly_compare', 'ກາຟປຽບທຽບຍອດຂາຍປະຈຳປີ')); ?>
          </h6>
        </div>
        <div class="card-body p-3">
          <div style="position: relative; height: 320px; width: 100%;">
            <canvas id="yearlySalesChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Row 3: 2 Secondary Charts (Profit Margin % & Top 10 Best Sellers) -->
  <div class="row mb-4">
    <!-- Chart 3: Monthly Profit Margin % -->
    <div class="col-lg-5 mb-4 mb-lg-0">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 14px; background: #ffffff;">
        <div class="card-header bg-white border-0 py-3 px-3.5 d-flex justify-content-between align-items-center">
          <h6 class="font-weight-bold text-dark mb-0" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
            <i class="fas fa-percentage text-warning mr-2"></i> <span><?php echo htmlspecialchars(t('home.chart_profit_margin', 'ກາຟອັດຕາກຳໄລ %')); ?></span> (<?php echo htmlspecialchars($chart_period_text); ?>)
          </h6>
        </div>
        <div class="card-body p-3">
          <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="profitMarginChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Chart 4: Top 10 Best Sellers -->
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 14px; background: #ffffff;">
        <div class="card-header bg-white border-0 py-3 px-3.5 d-flex justify-content-between align-items-center">
          <h6 class="font-weight-bold text-dark mb-0" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
            <i class="fas fa-fire text-danger mr-2"></i> <?php echo htmlspecialchars(t('home.chart_top10', 'ກາຟ 10 ລາຍການສິນຄ້າທີ່ຂາຍດີ')); ?>
          </h6>
          <span class="badge badge-light border text-danger font-weight-bold px-2.5 py-1">Top 10</span>
        </div>
        <div class="card-body p-3">
          <div style="position: relative; min-height: 360px; height: 360px; width: 100%;">
            <canvas id="topSellersChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

