<?php
  // Multi-currency: KPI figures are stored in KIP; convert to the active display currency.
  $__dc = function_exists('getDisplayCurrency') ? getDisplayCurrency($pdo) : ['code'=>'KIP','symbol'=>'₭','decimals'=>0,'rates'=>['KIP'=>1]];
  $__sym = $__dc['symbol']; $__dec = (int)$__dc['decimals'];
  if (!function_exists('__kpiNum')) { function __kpiNum($dc,$kip){ return number_format(convertFromKip($kip,$dc['code'],$dc['rates']), (int)$dc['decimals'], '.', ''); } }
?>


<?php if ($type === 'daily'): ?>

  <!-- DAILY REPORT: 10 SPECIFIC SUMMARY KPI CARDS BOX -->
  <div class="mb-4 kpi-cards-grid">

    <!-- 1. ລວມຍອດ (Blue Gradient) -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" 
         style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.kpi_gross_total', 'ລວມຍອດ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" data-target="<?php echo __kpiNum($__dc, $daily_gross_sales ?? 0); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon">
        <i class="fas fa-file-invoice-dollar"></i>
      </div>
    </div>

    <!-- 2. ສ່ວນຫຼຸດສິນຄ້າ (Rose Gradient) -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" 
         style="background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.kpi_item_discount', 'ສ່ວນຫຼຸດສິນຄ້າ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" data-target="<?php echo __kpiNum($__dc, $daily_item_discounts ?? 0); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon">
        <i class="fas fa-tag"></i>
      </div>
    </div>

    <!-- 3. ສ່ວນຫຼຸດໃບບິນ (Pink/Purple Gradient) -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" 
         style="background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.kpi_bill_discount', 'ສ່ວນຫຼຸດໃບບິນ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" data-target="<?php echo __kpiNum($__dc, $daily_bill_discounts ?? 0); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon">
        <i class="fas fa-receipt"></i>
      </div>
    </div>

    <!-- 4. ໂປຣໂມຊັ່ນ & ຂອງແຖມ (Vibrant Red/Pink Gradient) -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" 
         style="background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title text-nowrap" style="white-space: nowrap !important;"><i class="fas fa-bullhorn mr-1"></i> <?php echo htmlspecialchars(t('reports.kpi_promotion', 'ໂປຣໂມຊັ່ນ')); ?> <?php if (($daily_promo_gifts_count ?? 0) > 0): ?><span class="text-warning font-weight-normal ml-1" style="font-size: 0.75rem;">(<?php echo htmlspecialchars(t('reports.gift_label', 'ແຖມ')); ?>: <?php echo number_format($daily_promo_gifts_count); ?>)</span><?php endif; ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val text-nowrap" style="white-space: nowrap !important; font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" data-target="<?php echo __kpiNum($__dc, $daily_promo_discounts ?? 0); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon">
        <i class="fas fa-gift"></i>
      </div>
    </div>

    <!-- 4. ສ່ວນຫຼຸດທັງໝົດ (Crimson Red Gradient) -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" 
         style="background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.kpi_total_discount', 'ສ່ວນຫຼຸດທັງໝົດ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" data-target="<?php echo __kpiNum($__dc, $daily_total_discounts ?? 0); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon">
        <i class="fas fa-tags"></i>
      </div>
    </div>

    <!-- 5. ຍອດຂາຍສຸດທິ (Emerald Green Gradient) -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" 
         style="background: linear-gradient(135deg, #10b981 0%, #047857 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.kpi_net_sales', 'ຍອດຂາຍສຸດທິ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" data-target="<?php echo __kpiNum($__dc, $daily_net_sales ?? 0); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon">
        <i class="fas fa-coins"></i>
      </div>
    </div>

    <!-- 6. ເງິນສົດ (Indigo Gradient) -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" 
         style="background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.col_cash', 'ເງິນສົດ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" data-target="<?php echo __kpiNum($__dc, $daily_cash_payments ?? 0); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon">
        <i class="fas fa-money-bill-wave"></i>
      </div>
    </div>

    <!-- 7. ເງິນໂອນ (Cyan/Teal Gradient) -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" 
         style="background: linear-gradient(135deg, #06b6d4 0%, #0e7490 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.kpi_transfer', 'ເງິນໂອນ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" data-target="<?php echo __kpiNum($__dc, $daily_qr_payments ?? 0); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon">
        <i class="fas fa-qrcode"></i>
      </div>
    </div>

    <!-- 9. ບິນ (Royal Blue Gradient) -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" 
         style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.col_bill', 'ບິນ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" data-suffix-key="reports.bills_unit" data-target="<?php echo (int)($daily_bills_count ?? 0); ?>" data-suffix=" <?php echo htmlspecialchars(t('reports.bills_unit', 'ບິນ')); ?>">0 <?php echo htmlspecialchars(t('reports.bills_unit', 'ບິນ')); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon">
        <i class="fas fa-file-invoice"></i>
      </div>
    </div>

    <!-- 10. ລາຍການ (Teal Gradient) -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" 
         style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.items_unit', 'ລາຍການ')); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" data-suffix-key="reports.unit_pcs" data-target="<?php echo (int)($daily_items_qty ?? 0); ?>" data-suffix=" <?php echo htmlspecialchars(t('reports.unit_pcs', 'ອັນ')); ?>">0 <?php echo htmlspecialchars(t('reports.unit_pcs', 'ອັນ')); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon">
        <i class="fas fa-boxes"></i>
      </div>
    </div>

  </div>

<?php else: ?>

  <?php if ($view_mode !== 'item'): ?>
    <?php
      $net_val = (float)$total_net;
      $disc_val = (float)$total_disc;
      // Only ever a real figure (from actual recorded cost_price / product bprice) — never a
      // guessed margin. When nothing in this filtered result has a real cost on file
      // ($has_cost_data is false, e.g. all non-stock/service items), the card below is hidden
      // instead of showing a fabricated number.
      $profit_val = (float)$total_profit;
    ?>

    <!-- REGULAR SUMMARY KPI CARDS (5 CARDS) -->
    <div class="kpi-cards-grid" style="margin-bottom: 20px;">

      <!-- 1. ຈຳນວນບິນ -->
      <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" 
           style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
        <div style="z-index: 2; min-width: 0;">
          <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.kpi_bill_count', 'ຈຳນວນບິນ')); ?></div>
          <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
            <span class="counter-num" data-suffix-key="reports.bills_unit" data-target="<?php echo (int)$total_bills_count; ?>" data-suffix=" <?php echo htmlspecialchars(t('reports.bills_unit', 'ບິນ')); ?>">0 <?php echo htmlspecialchars(t('reports.bills_unit', 'ບິນ')); ?></span>
          </div>
        </div>
        <div class="kpi-card-icon">
          <i class="fas fa-file-invoice"></i>
        </div>
      </div>

      <!-- 2. ຈຳນວນສິນຄ້າ -->
      <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" 
           style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);">
        <div style="z-index: 2; min-width: 0;">
          <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.kpi_item_count', 'ຈຳນວນສິນຄ້າ')); ?></div>
          <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
            <span class="counter-num" data-suffix-key="reports.unit_pcs" data-target="<?php echo (int)$total_qty; ?>" data-suffix=" <?php echo htmlspecialchars(t('reports.unit_pcs', 'ອັນ')); ?>">0 <?php echo htmlspecialchars(t('reports.unit_pcs', 'ອັນ')); ?></span>
          </div>
        </div>
        <div class="kpi-card-icon">
          <i class="fas fa-boxes"></i>
        </div>
      </div>

      <!-- 3. ຍອດຂາຍ -->
      <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" 
           style="background: linear-gradient(135deg, #10b981 0%, #047857 100%);">
        <div style="z-index: 2; min-width: 0;">
          <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.kpi_sales', 'ຍອດຂາຍ')); ?></div>
          <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
            <span class="counter-num" data-target="<?php echo __kpiNum($__dc, $net_val); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
          </div>
        </div>
        <div class="kpi-card-icon">
          <i class="fas fa-coins"></i>
        </div>
      </div>

      <!-- 4. ສ່ວນຫຼຸດ -->
      <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" 
           style="background: linear-gradient(135deg, #f43f5e 0%, #be123c 100%);">
        <div style="z-index: 2; min-width: 0;">
          <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.discount_label', 'ສ່ວນຫຼຸດ')); ?></div>
          <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
            <span class="counter-num" data-target="<?php echo __kpiNum($__dc, $disc_val); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
          </div>
        </div>
        <div class="kpi-card-icon">
          <i class="fas fa-tags"></i>
        </div>
      </div>

      <!-- 5. ກຳໄລ — ເຊື່ອງໄວ້ຖ້າຂໍ້ມູນທີ່ກອງໄວ້ບໍ່ມີຕົ້ນທຶນແທ້ບັນທຶກໄວ້ເລີຍ (ເຊັ່ນ ມີແຕ່ລາຍການ
           ບໍລິການ/ບໍ່ຕັດສະຕັອກ ທີ່ບໍ່ມີລາຄາຊື້) ເພື່ອບໍ່ໃຫ້ສະແດງກຳໄລແບບເດົາ -->
      <?php if (!empty($has_cost_data)): ?>
      <div class="kpi-card-item d-flex align-items-center justify-content-between text-white"
           style="background: linear-gradient(135deg, #f59e0b 0%, #b45309 100%);">
        <div style="z-index: 2; min-width: 0;">
          <div class="kpi-card-title"><?php echo htmlspecialchars(t('reports.kpi_profit', 'ກຳໄລ')); ?></div>
          <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
            <span class="counter-num" data-target="<?php echo __kpiNum($__dc, $profit_val); ?>" data-suffix=" <?php echo htmlspecialchars($__sym); ?>" data-decimals="<?php echo $__dec; ?>">0 <?php echo htmlspecialchars($__sym); ?></span>
          </div>
        </div>
        <div class="kpi-card-icon">
          <i class="fas fa-chart-line"></i>
        </div>
      </div>
      <?php endif; ?>

    </div>
  <?php endif; ?>

<?php endif; ?>

<!-- Animated Counter Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  var counters = document.querySelectorAll('.counter-num');
  counters.forEach(function(el) {
    var target = parseFloat(el.getAttribute('data-target') || '0');
    var decs = parseInt(el.getAttribute('data-decimals') || '0', 10);
    var fmt = function(v){ return v.toLocaleString('en-US', {minimumFractionDigits:decs, maximumFractionDigits:decs}); };
    var suffix = el.getAttribute('data-suffix') || '';
    if (isNaN(target)) target = 0;

    var startTime = null;
    var duration = 1800; // 1.8 seconds smooth elegant count-up

    function step(timestamp) {
      if (!startTime) startTime = timestamp;
      var progress = Math.min((timestamp - startTime) / duration, 1);
      // Smooth ease out cubic
      var easeProgress = 1 - Math.pow(1 - progress, 3);
      var currentVal = easeProgress * target;
      
      el.textContent = fmt(currentVal) + suffix;
      
      if (progress < 1) {
        window.requestAnimationFrame(step);
      } else {
        el.textContent = fmt(target) + suffix;
      }
    }
    
    window.requestAnimationFrame(step);
  });
});

// ຊ່ອງໜ່ວຍ (ບິນ/ອັນ) ຖືກ "ອົບ" ເຂົ້າໃນ text node ດຽວກັນກັບຕົວເລກ (ເຊັ່ນ "16 ບິນ") ຕອນນັບແລ້ວ —
// ຄຳແປແບບ reverse-match ຂອງ dashboard ຈັບ text ປະສົມແບບນີ້ບໍ່ໄດ້ ຈຶ່ງບໍ່ປ່ຽນພາສາໃຫ້ອັດຕະໂນມັດ.
// ຟັງເຫດການ pos:langchange (dashboard.php dispatch ໃສ່ iframe ນີ້ຕອນສະຫຼັບພາສາ) ແລ້ວແປງຊ່ອງໜ່ວຍ
// ໃໝ່ດ້ວຍ dictionary ຂອງ parent window, ຄືນ text ດ້ວຍຄ່າສຸດທ້າຍ (ນັບແລ້ວ) + ຊ່ອງໜ່ວຍພາສາໃໝ່.
window.addEventListener('pos:langchange', function(e) {
  var lang = (e && e.detail && e.detail.lang) || (window.parent && window.parent.CURRENT_ACTIVE_LANG);
  if (!lang) return;
  var dict = null;
  try { dict = window.parent.I18N_ALL_MERGED && window.parent.I18N_ALL_MERGED[lang]; } catch (err) {}
  if (!dict) return;

  document.querySelectorAll('.counter-num[data-suffix-key]').forEach(function(el) {
    var key = el.getAttribute('data-suffix-key');
    if (!dict[key]) return;
    var newSuffix = ' ' + dict[key];
    el.setAttribute('data-suffix', newSuffix);

    var target = parseFloat(el.getAttribute('data-target') || '0');
    var decs = parseInt(el.getAttribute('data-decimals') || '0', 10);
    if (isNaN(target)) target = 0;
    var fmt = function(v){ return v.toLocaleString('en-US', {minimumFractionDigits:decs, maximumFractionDigits:decs}); };
    el.textContent = fmt(target) + newSuffix;
  });
});
</script>
