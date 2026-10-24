<?php
/*
 * ສ່ວນ: ສ່ວນສະແດງຜົນ
 * ໝາຍເລກຄິວ, ແຖບເທິງ (ໂລໂກ້ + ຊື່ຮ້ານ + ເວລາ), ແຜງ QR ຊຳລະ, ແຜງລາຍການສັ່ງຊື້ ແລະ ແຖບຍອດລວມ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/pos/customer_display.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>  <!-- Payment Success Overlay (ແຈ້ງເຕືອນຊຳລະເງິນສຳເລັດ + ລາຍລະອຽດລະອຽດຄົບຖ້ວນ, ມຸມ 6px) -->
  <div id="paymentSuccessOverlay" style="display:none;position:fixed;inset:0;z-index:3100;background:rgba(15,23,42,0.88);
       flex-direction:column;align-items:center;justify-content:center;color:#fff;text-align:center;padding:20px;backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);">
    <div class="pay-success-card" style="background:#ffffff;color:#1e293b;border-radius:6px;padding:26px 34px;max-width:540px;width:95%;box-shadow:0 20px 50px -10px rgba(0,0,0,0.45);text-align:center;position:relative;border:1px solid #e2e8f0;">
      
      <!-- Top Success Header: Check Icon + Title -->
      <div style="display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:12px;">
        <div style="width:46px;height:46px;border-radius:6px;background:linear-gradient(135deg,#22c55e,#16a34a);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(34,197,94,0.35);color:#fff;flex-shrink:0;">
          <i class="fas fa-check" style="font-size:24px;"></i>
        </div>
        <div style="text-align:left;">
          <h2 style="font-size:1.85rem;font-weight:800;color:#15803d;margin:0;line-height:1.15;font-family:'Noto Sans Lao','Souliyo','Boon',sans-serif;">
            ຊຳລະເງິນສຳເລັດ!
          </h2>
          <div style="font-size:0.90rem;color:#64748b;font-weight:600;margin:0;">
            ຂອບໃຈທີ່ມາອຸດໜູນ • Thank You!
          </div>
        </div>
      </div>

      <!-- Main Amount Highlight Box (ມຸມ 6px) -->
      <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:6px;padding:14px 18px;margin-bottom:12px;text-align:center;">
        <div style="font-size:0.88rem;color:#166534;font-weight:700;letter-spacing:0.3px;margin-bottom:2px;">
          <i class="fas fa-wallet mr-1"></i> ຈຳນວນເງິນທີ່ຊຳລະ
        </div>
        <div id="paySuccessAmount" style="font-size:2.8rem;font-weight:900;color:#15803d;line-height:1.15;font-family:'Noto Sans Lao','Souliyo','Boon',sans-serif;word-break:break-all;">
          0 ₭
        </div>
      </div>

      <!-- Payment Method & Bank Info Badge (ມຸມ 6px) -->
      <div id="paySuccessMethodBadge" style="display:flex;align-items:center;justify-content:space-between;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:8px 14px;margin-bottom:10px;font-size:0.92rem;font-weight:700;color:#334155;">
        <div style="display:flex;align-items:center;gap:6px;">
          <span id="paySuccessMethodIcon"><i class="fas fa-money-bill-wave text-success"></i></span>
          <span id="paySuccessMethodText">ຊຳລະດ້ວຍ: ເງິນສົດ</span>
        </div>
        <div id="paySuccessBankWrap" style="display:none;align-items:center;gap:6px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:6px;padding:3px 8px;">
          <img id="paySuccessBankLogo" src="" alt="" style="width:20px;height:20px;object-fit:contain;border-radius:4px;display:none;" onerror="this.style.display='none'">
          <span id="paySuccessBankName" style="color:#0369a1;font-weight:800;font-size:0.88rem;"></span>
        </div>
      </div>

      <!-- Detailed Breakdown Box (ມຸມ 6px) -->
      <div id="paySuccessBreakdown" style="background:#ffffff;border:1px solid #e2e8f0;border-radius:6px;padding:10px 14px;margin-bottom:12px;font-size:0.92rem;text-align:left;">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 0;border-bottom:1px dashed #e2e8f0;" id="paySuccessCashRow">
          <span style="color:#64748b;font-weight:600;"><i class="fas fa-money-bill-wave text-success mr-1"></i> ຮັບເງິນສົດ</span>
          <span id="paySuccessCashAmt" style="font-weight:800;color:#1e293b;font-size:1.05rem;">0 ₭</span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 0;border-bottom:1px dashed #e2e8f0;" id="paySuccessQrRow">
          <span style="color:#64748b;font-weight:600;"><i class="fas fa-qrcode text-primary mr-1"></i> ຮັບເງິນໂອນ <span id="paySuccessQrBankInline" style="color:#0284c7;font-weight:700;"></span></span>
          <span id="paySuccessQrAmt" style="font-weight:800;color:#1e293b;font-size:1.05rem;">0 ₭</span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 0;" id="paySuccessChangeRow">
          <span style="color:#64748b;font-weight:600;"><i class="fas fa-hand-holding-usd text-warning mr-1"></i> ເງິນທອນ</span>
          <span id="paySuccessChange" style="font-weight:900;color:#16a34a;font-size:1.15rem;">0 ₭</span>
        </div>
      </div>

      <!-- Footer Info: Invoice No & Queue Number (ມຸມ 6px) -->
      <div style="display:flex;align-items:center;justify-content:space-between;font-size:0.86rem;color:#64748b;font-weight:600;padding:0 2px;">
        <span id="paySuccessInvoiceNo">ເລກທີໃບບິນ: -</span>
        <span id="paySuccessQueueBadge" style="display:none;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:6px;padding:2px 8px;font-weight:700;">
          ຄິວທີ: <strong id="paySuccessQueueNo"></strong>
        </span>
      </div>

      <!-- Auto-dismiss countdown progress bar (ມຸມ 6px) -->
      <div style="margin-top:14px;height:4px;background:#e2e8f0;border-radius:6px;overflow:hidden;width:100%;">
        <div id="paySuccessProgressBar" style="height:100%;width:100%;background:linear-gradient(90deg,#22c55e,#16a34a);border-radius:6px;"></div>
      </div>

    </div>
  </div>

  <!-- Queue-number flash (coffee-shop style) — shown briefly on payment success -->
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
    <?php
      // ທະນາຄານຫຼາຍ: 1–3 = ແຖວດຽວ, 4 ຂຶ້ນໄປ = ຕາຂ່າຍ 2 ຖັນ (ຂະໜາດ QR ຫຍໍ້ຕາມຈຳນວນແຖວ ໃຫ້ເຫັນຄົບທຸກທະນາຄານ)
      $cdBankCount = count($cd_bank_accounts);
      $cdQrRows = $cdBankCount >= 4 ? (int)ceil($cdBankCount / 2) : 1;
    ?>
    <div class="left-store-panel<?php echo $cdBankCount >= 3 ? ' has-3-qr' : ''; ?><?php echo $cdBankCount >= 4 ? ' has-many-qr' : ''; ?>">
      <div class="store-card">
        <canvas class="particle-canvas" id="storeCardParticles"></canvas>
        <div class="store-card-content">
          <?php if (!empty($cd_bank_accounts)): ?>
            <!-- Payment QR codes (per bank account). One card -> centred; 2-3 -> in a row. -->
            <div class="pay-qr-heading">
              <i class="fas fa-qrcode"></i> <span>ສະແກນ QR ເພື່ອຊຳລະເງິນ</span>
            </div>
            <div class="pay-qr-wrap count-<?php echo min($cdBankCount, 3); ?><?php echo $cdBankCount >= 4 ? ' many' : ''; ?>" id="payQrWrap" style="--qr-rows: <?php echo $cdQrRows; ?>;">
              <?php foreach ($cd_bank_accounts as $ba): ?>
                <!-- data-bank-id: ໄຮໄລ້ບັດນີ້ເມື່ອພະນັກງານເລືອກທະນາຄານນີ້ຊຳລະ (renderBankPopup ໃນ display_js.php) -->
                <div class="pay-qr-card" data-bank-id="<?php echo (int)($ba['id'] ?? 0); ?>">
                  <div class="pqc-selected-tag"><i class="fas fa-hand-point-down"></i> ສະແກນອັນນີ້</div>
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
            <!-- ຖັນຊື່ສິນຄ້າກວ້າງສຸດ ໃຫ້ຊື່ຍາວຢູ່ 1–2 ແຖວ -->
            <colgroup>
              <col style="width: 6%;">
              <col style="width: 8%;">
              <col style="width: 34%;">
              <col style="width: 9%;">
              <col style="width: 9%;">
              <col style="width: 16%;">
              <col style="width: 18%;">
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
      <span class="gt-final-label"><i class="fas fa-wallet mr-1"></i> ຍອດທີ່ຕ້ອງຊຳລະ</span>
      <h2 id="displayGrandTotal">0 <?php echo htmlspecialchars($cd['symbol']); ?></h2>
    </div>
  </div>
</div>

