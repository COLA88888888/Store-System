<?php
/*
 * ສ່ວນ: ສ່ວນສະແດງຜົນ
 * ໝາຍເລກຄິວ, ແຖບເທິງ (ໂລໂກ້ + ຊື່ຮ້ານ + ເວລາ), ແຜງ QR ຊຳລະ, ແຜງລາຍການສັ່ງຊື້ ແລະ ແຖບຍອດລວມ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/pos/customer_display.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>  <!-- Queue-number flash (coffee-shop style) — shown briefly on payment success -->
  <div id="queueFlash" style="display:none;position:fixed;inset:0;z-index:3000;background:rgba(15,23,42,0.94);
       flex-direction:column;align-items:center;justify-content:center;color:#fff;text-align:center;">
    <div style="font-size:2rem;font-weight:700;letter-spacing:2px;color:#93c5fd;">ບັດຄິວທີ</div>
    <div id="queueFlashNo" style="font-size:26vh;font-weight:900;line-height:1;text-shadow:0 8px 30px rgba(0,0,0,0.5);">0</div>
    <div style="font-size:1.6rem;font-weight:700;color:#cbd5e1;margin-top:10px;">ຂອບໃຈທີ່ມາອຸດໜູນ</div>
  </div>

  <!-- QR ທະນາຄານທີ່ພະນັກງານເລືອກຊຳລະ — ເດັ້ງຂຶ້ນຕອນໂອນ/ແຍກຈ່າຍ (renderDisplayData ໃນ display_js.php) -->
  <div id="bankPayPopup" class="bank-pay-popup" style="display:none;">
    <div class="bpp-card">
      <div class="bpp-bank">
        <img id="bppLogo" src="" alt="" style="display:none;" onerror="this.style.display='none'">
        <span id="bppBankName"></span>
      </div>
      <img id="bppQr" class="bpp-qr" src="" alt="QR" onerror="this.style.visibility='hidden'">
      <div class="bpp-amount-label">ຈຳນວນເງິນທີ່ຕ້ອງໂອນ</div>
      <div class="bpp-amount" id="bppAmount">0</div>
      <div class="bpp-hint"><i class="fas fa-mobile-alt"></i> ສະແກນ QR ດ້ວຍແອັບທະນາຄານ</div>
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

    <div class="header-welcome">
      <i class="fas fa-heart"></i> <span>ຍິນດີຕ້ອນຮັບ</span>
    </div>

    <div class="header-datetime">
      <span class="header-clock" id="liveClock">00:00:00</span>
      <span class="header-date" id="liveDate">--/--/----</span>
    </div>
  </div>

  <!-- MAIN DUAL PANEL -->
  <div class="display-main">

    <!-- LEFT PANEL: QR ຊຳລະເງິນ (ໂລໂກ້ ແລະ ຊື່ຮ້ານ ຍ້າຍຂຶ້ນໄປແຖບເທິງແລ້ວ) -->
    <div class="left-store-panel<?php echo count($cd_bank_accounts) >= 3 ? ' has-3-qr' : ''; ?>">
      <div class="store-card">
        <canvas class="particle-canvas" id="storeCardParticles"></canvas>
        <div class="store-card-content">
          <?php if (!empty($cd_bank_accounts)): ?>
            <!-- Payment QR codes (per bank account). One card -> centred; 2-3 -> in a row. -->
            <div class="pay-qr-heading">
              <i class="fas fa-qrcode"></i> <span>ສະແກນ QR ເພື່ອຊຳລະເງິນ</span>
            </div>
            <div class="pay-qr-wrap count-<?php echo count($cd_bank_accounts); ?>">
              <?php foreach ($cd_bank_accounts as $ba): ?>
                <div class="pay-qr-card">
                  <div class="pqc-bank">
                    <?php if (!empty($ba['logo'])): ?>
                      <img src="<?php echo htmlspecialchars($ba['logo']); ?>" alt="" onerror="this.style.display='none'">
                    <?php else: ?>
                      <i class="fas fa-university text-primary"></i>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($ba['bank_name']); ?></span>
                  </div>
                  <img class="pqc-qr" src="<?php echo htmlspecialchars($ba['qr']); ?>" alt="QR"
                       onerror="this.style.display='none'">
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="welcome-banner-box">
              <i class="fas fa-heart text-danger fa-2x mb-2"></i>
              <h4 class="font-weight-bold text-dark mb-1" style="font-size: 1.35rem;">ຂໍຂອບໃຈທີ່ມາອຸດໜູນ</h4>
              <p class="text-secondary mb-0" style="font-size: 1.02rem; font-weight: 600;">ຍິນດີຕ້ອນຮັບ! ຂໍໃຫ້ທ່ານມີຄວາມສຸກໃນການຊື້ສິນຄ້າ</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- RIGHT PANEL: CUSTOMER ORDER LIST & COMPLETE PAYMENT DETAILS (60%) -->
    <div class="right-order-panel">
      <!-- Order Items Table -->
      <div class="cart-card">
        <div class="display-table-body">
          <table class="w-100 border-0 mb-0 display-cols-table">
            <colgroup>
              <col style="width: 6%;">
              <col style="width: 12%;">
              <col style="width: 21%;">
              <col style="width: 10%;">
              <col style="width: 12%;">
              <col style="width: 18%;">
              <col style="width: 21%;">
            </colgroup>
            <thead class="display-table-head">
              <tr>
                <th class="text-center">ລຳດັບ</th>
                <th class="text-center">ຮູບສິນຄ້າ</th>
                <th class="text-center">ຊື່ສິນຄ້າ</th>
                <th class="text-center">ຫົວໜ່ວຍ</th>
                <th class="text-center">ຈຳນວນ</th>
                <th class="text-right">ລາຄາ</th>
                <th class="text-right">ລວມ</th>
              </tr>
            </thead>
            <tbody id="displayCartBody"></tbody>
          </table>
          <div class="display-cart-empty" id="displayCartEmpty">
            <i class="fas fa-shopping-basket"></i>
            <h4>ຍັງບໍ່ມີລາຍການສິນຄ້າ</h4>
            <p>ກະລຸນາລໍຖ້າພະນັກງານສະແກນ ຫຼື ເລືອກສິນຄ້າ...</p>
          </div>
        </div>
      </div>

    </div>

  </div>

  <!-- GREEN GRAND-TOTAL BOX: full-width bar below both panels, every payment stat + grand total -->
  <div class="grand-total-banner">
    <div class="gt-substats">
      <div class="gt-substat">
        <span class="gt-substat-label"><i class="fas fa-boxes"></i> ລາຍການສິນຄ້າ</span>
        <span class="gt-substat-value" id="displayItemCount">0 ລາຍການ / 0 ອັນ</span>
      </div>
      <div class="gt-substat">
        <span class="gt-substat-label"><i class="fas fa-calculator"></i> ຍອດລວມ</span>
        <span class="gt-substat-value" id="displaySubtotal">0 <?php echo htmlspecialchars($cd['symbol']); ?></span>
      </div>
      <div class="gt-substat">
        <span class="gt-substat-label"><i class="fas fa-percent"></i> ສ່ວນຫຼຸດ</span>
        <span class="gt-substat-value" id="displayDiscount">0 <?php echo htmlspecialchars($cd['symbol']); ?></span>
      </div>
      <div class="gt-substat">
        <span class="gt-substat-label"><i class="fas fa-qrcode"></i> ຮັບເງິນໂອນ</span>
        <span class="gt-substat-value" id="displayQrReceived">0 <?php echo htmlspecialchars($cd['symbol']); ?></span>
      </div>
      <div class="gt-substat">
        <span class="gt-substat-label"><i class="fas fa-money-bill-wave"></i> ຮັບເງິນສົດ</span>
        <span class="gt-substat-value" id="displayCashReceived">0 <?php echo htmlspecialchars($cd['symbol']); ?></span>
      </div>
      <div class="gt-substat">
        <span class="gt-substat-label"><i class="fas fa-hand-holding-usd"></i> ເງິນທອນ</span>
        <span class="gt-substat-value" id="displayChangeAmount">0 <?php echo htmlspecialchars($cd['symbol']); ?></span>
      </div>
    </div>

    <div class="gt-final">
      <span class="gt-final-label">ຍອດຊຳລະ (ຍອດລວມ - ສ່ວນຫຼຸດ)</span>
      <h2 id="displayGrandTotal">0 <?php echo htmlspecialchars($cd['symbol']); ?></h2>
    </div>
  </div>
</div>

