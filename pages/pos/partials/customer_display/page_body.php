<?php
/*
 * ສ່ວນ: ສ່ວນສະແດງຜົນ
 * ໝາຍເລກຄິວ, ແຖບເທິງ (ໂລໂກ້ + ຊື່ຮ້ານ + ເວລາ), ແຜງ QR ຊຳລະ, ແຜງລາຍການສັ່ງຊື້ ແລະ ແຖບຍອດລວມ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/pos/customer_display.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>  <!-- Payment Success Overlay (ແບບມາດຕະຖານ ສະອາດຕາ ມຸມ 4px) -->
  <div id="paymentSuccessOverlay" style="display:none;position:fixed;inset:0;z-index:3100;background:rgba(15,23,42,0.78);
       flex-direction:column;align-items:center;justify-content:center;color:#fff;text-align:center;padding:20px;backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);">
    
    <div class="pay-success-card" style="background:#ffffff;color:#0f172a;border-radius:4px;padding:28px 32px 22px;max-width:520px;width:95%;box-shadow:0 16px 40px rgba(0,0,0,0.38);text-align:center;position:relative;border:1px solid #cbd5e1;font-family:'Nuanta', 'Noto Sans Lao','Souliyo','Boon',sans-serif;">
      
      <!-- 1. Header: ໄອຄອນ + ຊຳລະເງິນສຳເລັດ (ບໍ່ມີຄຳວ່າ ຂອບໃຈທີ່ມາອຸດໜູນ) -->
      <div style="display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:20px;">
        <i class="fas fa-check-circle" style="font-size:2.2rem;color:#16a34a;line-height:1;"></i>
        <h2 style="font-size:1.85rem;font-weight:800;color:#0f172a;margin:0;line-height:1.2;">
          <?php echo htmlspecialchars(t('cd.pay_success')); ?>
        </h2>
      </div>

      <!-- 2. ຈຳນວນເງິນທີ່ຊຳລະ (ມຸມ 4px) -->
      <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;padding:16px 20px;margin-bottom:14px;text-align:center;">
        <div style="font-size:0.92rem;color:#475569;font-weight:700;margin-bottom:4px;">
          <?php echo htmlspecialchars(t('cd.amount_paid')); ?>
        </div>
        <div id="paySuccessAmount" style="font-size:2.85rem;font-weight:900;color:#15803d;line-height:1.12;word-break:break-all;">
          0 <?php echo kipSymbol(); ?>
        </div>
        <div id="paySuccessAmountKip" style="display:none;font-size:1.35rem;color:#64748b;font-weight:800;margin-top:4px;"></div>
      </div>

      <!-- 3. ຊ່ອງທາງການຊຳລະ & ທະນາຄານ (ໄອຄອນ ແລະ ທະນາຄານ ບໍ່ມີກາດຫຸ້ມ) -->
      <div id="paySuccessMethodBadge" style="display:flex;align-items:center;justify-content:space-between;padding:10px 4px;border-bottom:1px solid #e2e8f0;margin-bottom:6px;font-size:0.96rem;font-weight:700;color:#1e293b;">
        <div style="display:flex;align-items:center;gap:8px;">
          <span id="paySuccessMethodIcon" style="font-size:1.05rem;display:inline-flex;align-items:center;">
            <i class="fas fa-money-bill-wave text-success"></i>
          </span>
          <span id="paySuccessMethodText" style="color:#334155;"><?php echo htmlspecialchars(t('cd.paid_by_cash')); ?></span>
        </div>
        <div id="paySuccessBankWrap" style="display:none;align-items:center;gap:7px;">
          <img id="paySuccessBankLogo" src="" alt="" style="width:22px;height:22px;object-fit:contain;display:none;" onerror="this.style.display='none'">
          <span id="paySuccessBankName" style="color:#0284c7;font-weight:800;font-size:0.96rem;"></span>
        </div>
      </div>

      <!-- 4. ລາຍລະອຽດການຮັບເງິນ & ເງິນທອນ (ໄອຄອນບໍ່ມີກາດຫຸ້ມ, ເງິນທອນສະແດງສະເພາະເມື່ອມີເງິນທອນ) -->
      <div id="paySuccessBreakdown" style="padding:4px 4px 10px;margin-bottom:12px;font-size:0.96rem;text-align:left;border-bottom:1px solid #e2e8f0;">
        <!-- Cash Row -->
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;" id="paySuccessCashRow">
          <span style="color:#475569;font-weight:700;display:inline-flex;align-items:center;gap:8px;">
            <i class="fas fa-money-bill-wave" style="color:#16a34a;width:18px;text-align:center;"></i>
            <span><?php echo htmlspecialchars(t('cd.cash_received')); ?></span>
          </span>
          <span id="paySuccessCashAmt" style="font-weight:800;color:#0f172a;font-size:1.1rem;">0 <?php echo kipSymbol(); ?></span>
        </div>
        <!-- QR Transfer Row -->
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;" id="paySuccessQrRow">
          <span style="color:#475569;font-weight:700;display:inline-flex;align-items:center;gap:8px;">
            <i class="fas fa-qrcode" style="color:#0284c7;width:18px;text-align:center;"></i>
            <span><?php echo htmlspecialchars(t('cd.transfer_received')); ?> <span id="paySuccessQrBankInline" style="color:#0284c7;font-weight:700;"></span></span>
          </span>
          <span id="paySuccessQrAmt" style="font-weight:800;color:#0f172a;font-size:1.1rem;">0 <?php echo kipSymbol(); ?></span>
        </div>
        <!-- Change Row (ສະແດງສະເພາະເມື່ອມີເງິນທອນ > 0) -->
        <div style="display:none;justify-content:space-between;align-items:center;padding:8px 0;border-top:1px dashed #cbd5e1;" id="paySuccessChangeRow">
          <span style="color:#475569;font-weight:700;display:inline-flex;align-items:center;gap:8px;">
            <i class="fas fa-hand-holding-usd" style="color:#d97706;width:18px;text-align:center;"></i>
            <span><?php echo htmlspecialchars(t('cd.change')); ?></span>
          </span>
          <span id="paySuccessChange" style="font-weight:900;color:#16a34a;font-size:1.18rem;">0 <?php echo kipSymbol(); ?></span>
        </div>
      </div>

      <!-- 5. Footer: ເລກທີໃບບິນ & ເວລາ 24 ຊົ່ວໂມງ (ບໍ່ມີກາດຫຸ້ມ) -->
      <div style="display:flex;align-items:center;justify-content:space-between;font-size:0.88rem;color:#64748b;font-weight:600;padding:0 4px;">
        <span id="paySuccessInvoiceNo"><?php echo htmlspecialchars(t('cd.invoice_no')); ?> -</span>
        <div style="display:inline-flex;align-items:center;gap:12px;">
          <span id="paySuccessTimeText" style="color:#64748b;">00:00:00</span>
          <span id="paySuccessQueueBadge" style="display:none;color:#1d4ed8;font-weight:800;">
            <?php echo htmlspecialchars(t('cd.queue_no')); ?> <strong id="paySuccessQueueNo"></strong>
          </span>
        </div>
      </div>

      <!-- 6. ແຖບນັບຖອຍຫຼັງປິດອັດຕະໂນມັດ (ມຸມ 4px) -->
      <div style="margin-top:14px;height:4px;background:#e2e8f0;border-radius:4px;overflow:hidden;width:100%;">
        <div id="paySuccessProgressBar" style="height:100%;width:100%;background:#16a34a;border-radius:4px;"></div>
      </div>

    </div>
  </div>

  <!-- Queue-number flash (coffee-shop style) — shown briefly on payment success -->
  <div id="queueFlash" style="display:none;position:fixed;inset:0;z-index:3000;background:rgba(15,23,42,0.94);
       flex-direction:column;align-items:center;justify-content:center;color:#fff;text-align:center;">
    <div style="font-size:2rem;font-weight:700;letter-spacing:2px;color:#93c5fd;"><?php echo htmlspecialchars(t('cd.queue_ticket')); ?></div>
    <div id="queueFlashNo" style="font-size:26vh;font-weight:900;line-height:1;text-shadow:0 8px 30px rgba(0,0,0,0.5);">0</div>
    <div style="font-size:1.6rem;font-weight:700;color:#cbd5e1;margin-top:10px;"><?php echo htmlspecialchars(t('cd.thank_you')); ?></div>
  </div>

  <!-- QR ທະນາຄານທີ່ພະນັກງານເລືອກຊຳລະ — ເດັ້ງຂຶ້ນຕອນໂອນ/ແຍກຈ່າຍ (renderDisplayData ໃນ display_js.php) -->
  <div id="bankPayPopup" class="bank-pay-popup" style="display:none;">
    <div class="bpp-card">
      <div class="bpp-bank">
        <img id="bppLogo" src="" alt="" style="display:none;" onerror="this.style.display='none'">
        <span id="bppBankName"></span>
      </div>
      <div class="bpp-qr-frame">
        <span class="qr-corner qr-corner-tl"></span>
        <span class="qr-corner qr-corner-tr"></span>
        <span class="qr-corner qr-corner-bl"></span>
        <span class="qr-corner qr-corner-br"></span>
        <img id="bppQr" class="bpp-qr" src="" alt="QR" onerror="this.style.visibility='hidden'">
      </div>
      <div class="bpp-amount-label"><?php echo htmlspecialchars(t('cd.amount_to_transfer')); ?></div>
      <div class="bpp-amount" id="bppAmount">0</div>
      <div class="bpp-amount-kip" id="bppAmountKip" style="display:none;"></div>
      <div class="bpp-hint"><i class="fas fa-mobile-alt"></i> <?php echo htmlspecialchars(t('cd.scan_qr_hint')); ?></div>
      <!-- Split Payment: ສະແດງເງິນສົດທີ່ຕ້ອງຈ່າຍພ້ອມ ຢູ່ລຸ່ມ QR -->
      <div id="bppSplitSection" class="bpp-split-section" style="display:none;">
        <div class="bpp-split-divider"></div>
        <div class="bpp-split-cash-box" id="bppSplitCashBox">
          <div class="bpp-split-cash-header">
            <div class="bpp-split-cash-icon" id="bppSplitCashIcon"><i class="fas fa-money-bill-wave"></i></div>
            <span class="bpp-split-cash-label" id="bppSplitCashLabel"><?php echo htmlspecialchars(t('cd.cash_to_pay', 'ເງິນສົດທີ່ຕ້ອງຈ່າຍ')); ?></span>
          </div>
          <div class="bpp-split-cash-amount" id="bppSplitCashAmount">0</div>
          <div class="bpp-split-cash-kip" id="bppSplitCashKip" style="display:none;"></div>
          <div class="bpp-split-cash-sub" id="bppSplitCashSub" style="display:none;"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- 1. ແຖບເທິງ: ໂລໂກ້ + ຊື່ຮ້ານ (ຊ້າຍ), ຄຳຕ້ອນຮັບ (ກາງ), ເວລາ/ວັນທີ (ຂວາ) -->
  <div class="display-header">
    <div class="header-brand">
      <div class="header-logo">
        <img src="<?php echo htmlspecialchars($company['com_logo']); ?>" alt="Store Logo"
             onerror="this.style.display='none'; document.getElementById('headerFallbackIcon').style.display='block';">
        <i class="fas fa-store" id="headerFallbackIcon" style="display:none;"></i>
      </div>
      <div class="header-brand-text">
        <div class="header-store-name"><?php echo htmlspecialchars($company['com_name']); ?></div>
        <div class="header-store-sub">
          <?php if(!empty($company['com_tel'])): ?>
            <span><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($company['com_tel']); ?></span>
          <?php endif; ?>
          <?php if(!empty($company['com_address'])): ?>
            <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($company['com_address']); ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="header-datetime">
      <span class="header-sys-name"><i class="fas fa-desktop mr-1 text-warning"></i> WlaoDev POS</span>
      <div class="header-datetime-text">
        <span class="header-clock" id="liveClock">00:00:00</span>
        <span class="header-date" id="liveDate">--/--/----</span>
      </div>
    </div>
  </div>

  <!-- MAIN DUAL PANEL -->
  <div class="display-main">

    <!-- LEFT PANEL: 100% 3D CARTOON MINI-MART WORLD (ສະແດງທັນທີ 0ms ບໍ່ມີຈໍຂາວ) -->
    <div class="left-store-panel">
      <div class="store-card minimart-store-card" style="background:#f1f5f9;">
        <!-- 3D Mini-Mart World -->
        <div class="minimart-3d-wrap" id="minimart3dWrap" style="background:#f1f5f9;position:relative;">
          <!-- 3D Canvas Viewport (ເຕັມແຜງຊ້າຍມື 100%) -->
          <div id="minimart3dContainer" class="minimart-3d-container" style="position:relative;width:100%;height:100%;overflow:hidden;background-color:#f1f5f9;background-size:100% 100%;background-position:center;background-repeat:no-repeat;"></div>
          <script>
          (function() {
            try {
              ['StoreMini.mm3d_poster_v1', 'StoreMini.mm3d_poster_v2', 'StoreMini.mm3d_poster_v3', 'StoreMini.mm3d_poster_v4', 'StoreMini.mm3d_poster_v5'].forEach(function(k) {
                localStorage.removeItem(k);
              });
              var c = document.getElementById('minimart3dContainer');
              var p = localStorage.getItem('StoreMini.mm3d_poster_v6');
              if (c && p) c.style.backgroundImage = 'url(' + p + ')';
            } catch(e) {}
          })();
          </script>
        </div>
      </div>
    </div>

    <!-- RIGHT PANEL: CUSTOMER ORDER LIST & COMPLETE PAYMENT DETAILS (60%) -->
    <div class="right-order-panel">
      <!-- Order Items Table -->
      <div class="cart-card">
        <div class="display-table-body">
          <table class="w-100 border-0 mb-0 display-cols-table" style="width: 100% !important; table-layout: fixed;">
            <!-- ຖັນຊື່ສິນຄ້າກວ້າງສຸດ ໃຫ້ຊື່ຍາວຢູ່ 1–2 ແຖວ ພ້ອມຫົວໜ່ວຍ -->
            <colgroup>
              <col style="width: 7%;">
              <col style="width: 10%;">
              <col style="width: 42%;">
              <col style="width: 10%;">
              <col style="width: 15%;">
              <col style="width: 16%;">
            </colgroup>
            <thead class="display-table-head">
              <tr>
                <th class="text-center" style="white-space: nowrap; text-align: center !important;"><?php echo htmlspecialchars(t('cd.col_no')); ?></th>
                <th class="text-center" style="white-space: nowrap; text-align: center !important;"><?php echo htmlspecialchars(t('cd.col_image')); ?></th>
                <th class="text-left" style="white-space: nowrap; text-align: left !important; padding-left: 10px !important;"><?php echo htmlspecialchars(t('cd.col_item')); ?></th>
                <th class="text-center" style="white-space: nowrap; text-align: center !important;"><?php echo htmlspecialchars(t('cd.col_qty')); ?></th>
                <th class="text-center" style="white-space: nowrap; text-align: center !important;"><?php echo htmlspecialchars(t('cd.col_price')); ?></th>
                <th class="text-center" style="white-space: nowrap; text-align: center !important;"><?php echo htmlspecialchars(t('cd.col_total')); ?></th>
              </tr>
            </thead>
            <tbody id="displayCartBody"></tbody>
          </table>
          <div class="display-cart-empty" id="displayCartEmpty">
            <i class="fas fa-shopping-basket"></i>
            <h4><?php echo htmlspecialchars(t('cd.empty_title')); ?></h4>
            <p><?php echo htmlspecialchars(t('cd.empty_hint')); ?></p>
          </div>
        </div>
      </div>

    </div>

  </div>

  <!-- GREEN GRAND-TOTAL BOX: full-width bar below both panels, every payment stat + grand total -->
  <div class="grand-total-banner">
    <div class="gt-substats">
      <div class="gt-substat">
        <span class="gt-substat-label"><i class="fas fa-boxes"></i> <?php echo htmlspecialchars(t('cd.items')); ?></span>
        <span class="gt-substat-value" id="displayItemCount">0 <?php echo htmlspecialchars(t('cd.lines')); ?> / 0 <?php echo htmlspecialchars(t('cd.pieces')); ?></span>
      </div>
      <div class="gt-substat">
        <span class="gt-substat-label"><i class="fas fa-calculator"></i> <?php echo htmlspecialchars(t('cd.subtotal')); ?></span>
        <span class="gt-substat-value" id="displaySubtotal">0 <?php echo htmlspecialchars($cd['symbol']); ?></span>
      </div>
      <div class="gt-substat">
        <span class="gt-substat-label"><i class="fas fa-percent"></i> <?php echo htmlspecialchars(t('cd.discount')); ?></span>
        <span class="gt-substat-value" id="displayDiscount">0 <?php echo htmlspecialchars($cd['symbol']); ?></span>
      </div>
      <div class="gt-substat">
        <span class="gt-substat-label"><i class="fas fa-qrcode"></i> <?php echo htmlspecialchars(t('cd.transfer_received')); ?></span>
        <span class="gt-substat-value" id="displayQrReceived">0 <?php echo htmlspecialchars($cd['symbol']); ?></span>
      </div>
      <div class="gt-substat">
        <span class="gt-substat-label"><i class="fas fa-money-bill-wave"></i> <?php echo htmlspecialchars(t('cd.cash_received')); ?></span>
        <span class="gt-substat-value" id="displayCashReceived">0 <?php echo htmlspecialchars($cd['symbol']); ?></span>
      </div>
      <div class="gt-substat">
        <span class="gt-substat-label"><i class="fas fa-hand-holding-usd"></i> <?php echo htmlspecialchars(t('cd.change')); ?></span>
        <span class="gt-substat-value" id="displayChangeAmount">0 <?php echo htmlspecialchars($cd['symbol']); ?></span>
      </div>
    </div>

    <div class="gt-final">
      <span class="gt-final-label"><i class="fas fa-wallet mr-1"></i> <?php echo htmlspecialchars(t('cd.amount_due')); ?></span>
      <h2 id="displayGrandTotal">0 <?php echo htmlspecialchars($cd['symbol']); ?></h2>
      <div id="displayGrandTotalKip" style="display:none;font-size:1.15rem;color:#bbf7d0;font-weight:700;margin-top:2px;"></div>
    </div>
  </div>
</div>

