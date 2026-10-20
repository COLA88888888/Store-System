<!-- Modal: Checkout -->
<div class="modal fade" id="checkoutModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered checkout-modal-dialog" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden; background: #ffffff;">

      <!-- Header -->
      <div class="modal-header bg-primary py-2 px-4">
        <h5 class="modal-title font-weight-bold d-flex align-items-center mb-0" style="font-size: 18px; font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span id="checkoutTitlePay"><?php echo htmlspecialchars(t('pos.pay', 'ຊຳລະເງິນ')); ?></span>
          <span id="checkoutTitleCredit" style="display:none;"><i class="fas fa-hand-holding-usd mr-2"></i><?php echo htmlspecialchars(t('pos.credit_summary', 'ຂາຍຕິດໜີ້')); ?></span>
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" style="opacity:0.9;font-size:1.4rem;"><span>&times;</span></button>
      </div>

      <!-- Body: Responsive Grid -->
      <div class="modal-body p-0" style="background:#f1f5f9; max-height:85vh; overflow-y:auto; -webkit-overflow-scrolling:touch;">
        <div class="checkout-grid">

          <!-- ===== LEFT COLUMN ===== -->
          <div class="checkout-left-col">

            <!-- Total -->
            <?php echo themeCssLink('pos-checkout-modal.css'); /* CSS ຍ້າຍໄປ themes/pos-checkout-modal.css */ ?>
            <div class="text-center text-white checkout-total-card">
              <div class="checkout-total-icon"><i class="fas fa-wallet" style="font-size:0.85rem;color:#fff;"></i></div>
              <div style="color:rgba(255,255,255,0.82);font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;margin-bottom:3px;position:relative;">
                <?php echo htmlspecialchars(t('pos.amount_due', 'ຍອດທີ່ຕ້ອງຊຳລະ')); ?>
              </div>
              <div class="font-weight-bold" id="checkoutTotalDisplay" style="font-size:1.55rem;color:#fff;font-family:'Noto Sans Lao','Souliyo','Boon',sans-serif;line-height:1.2;word-break:break-all;text-shadow:0 2px 8px rgba(0,0,0,0.15);position:relative;">0 ₭</div>
            </div>

            <!-- Pay Mode (hidden in credit mode — that is opened from the ຕິດໜີ້ button on the scan bar) -->
            <div id="payModeRow">
              <div class="font-weight-bold mb-1" style="font-size:0.80rem;color:#475569;"><?php echo htmlspecialchars(t('pos.pay_method_label', 'ວິທີຊຳລະ')); ?></div>
              <div class="d-flex" style="gap:7px;">
                <button type="button" id="payModeSingleBtn" onclick="setPayMode('single')"
                  style="flex:1;padding:8px 12px;border-radius:9px;border:2px solid #244886;background:#eef2fb;color:#1a3666;font-weight:700;font-size:0.85rem;cursor:pointer;transition:all 0.15s;font-family:'Noto Sans Lao','Souliyo','Boon',sans-serif;text-align:center;">
                  <?php echo htmlspecialchars(t('pos.pay_mode_single', 'ຊຳລະດຽວ')); ?>
                </button>
                <button type="button" id="payModeSplitBtn" onclick="setPayMode('split')"
                  style="flex:1;padding:8px 12px;border-radius:9px;border:2px solid #cbd5e1;background:#fff;color:#64748b;font-weight:700;font-size:0.85rem;cursor:pointer;transition:all 0.15s;font-family:'Noto Sans Lao','Souliyo','Boon',sans-serif;text-align:center;">
                  <?php echo htmlspecialchars(t('pos.pay_mode_split', 'ຊຳລະຫຼາຍຊ່ອງ')); ?>
                </button>
              </div>
            </div>

            <!-- Pay Type (single only) -->
            <div id="payTypeRow">
              <div class="font-weight-bold mb-1" style="font-size:0.80rem;color:#475569;"><?php echo htmlspecialchars(t('pos.pay_type_label', 'ປະເພດ')); ?></div>
              <div class="d-flex" style="gap:7px;">
                <button type="button" id="payTypeCashBtn" onclick="selectPayTypeTab('ເງິນສົດ')"
                  style="flex:1;padding:8px 12px;border-radius:9px;border:2px solid #244886;background:#eef2fb;color:#1a3666;font-weight:700;font-size:0.85rem;cursor:pointer;transition:all 0.15s;font-family:'Noto Sans Lao','Souliyo','Boon',sans-serif;text-align:center;">
                  <?php echo htmlspecialchars(t('pos.pay_type_cash', 'ເງິນສົດ')); ?>
                </button>
                <button type="button" id="payTypeQrBtn" onclick="selectPayTypeTab('ໂອນເງິນ / QR')"
                  style="flex:1;padding:8px 12px;border-radius:9px;border:2px solid #cbd5e1;background:#fff;color:#64748b;font-weight:700;font-size:0.85rem;cursor:pointer;transition:all 0.15s;font-family:'Noto Sans Lao','Souliyo','Boon',sans-serif;text-align:center;">
                  <?php echo htmlspecialchars(t('pos.pay_type_qr', 'ໂອນ / QR')); ?>
                </button>
              </div>
            </div>

            <!-- SINGLE: Bank Selection & QR Code Display Container (When payType is QR Pay) -->
            <div id="singleQrSection" style="display:none;" class="p-3 my-2 rounded border bg-white shadow-sm">
              <?php 
                $pos_bank_accounts = [];
                try {
                    // This branch's own banks, plus any shared across every branch (store_id = 0).
                    $b_stmt = $pdo->prepare("SELECT * FROM st_bank_accounts WHERE is_active = 1 AND (store_id = ? OR store_id = 0) ORDER BY id ASC");
                    $b_stmt->execute([getPosStoreId()]);
                    $pos_bank_accounts = $b_stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Throwable $e) {}

                $defaultQrPath = resolveBankQr($company['qr_img'] ?? '');
              ?>

              <div class="font-weight-bold mb-2 text-dark" style="font-size:0.85rem;">
                <i class="fas fa-university text-primary mr-1"></i> <?php echo htmlspecialchars(t('pos.select_receiving_bank', 'ເລືອກທະນາຄານຮັບເງິນ:')); ?>
              </div>

              <?php if (!empty($pos_bank_accounts)): ?>
                <div class="row mb-2" style="row-gap: 8px; margin-left: -4px; margin-right: -4px;" id="posBankSelectGrid">
                  <?php foreach ($pos_bank_accounts as $bIdx => $bAccount): ?>
                    <?php 
                      $bLogo = resolveBankLogo($bAccount['bank_logo'] ?? '', $bAccount['bank_code'] ?? '');
                      $bQr   = resolveBankQr($bAccount['qr_code_img'] ?? '', $bAccount['bank_code'] ?? '');
                      $code = strtoupper(trim($bAccount['bank_code'] ?? ''));
                      $bName = !empty($bAccount['bank_name']) ? $bAccount['bank_name'] : (!empty($code) ? $code : 'Bank');
                      $displayLabel = $bName;
                      $bColor = (strpos($code, 'BCEL') !== false) ? '#002d72' : ((strpos($code, 'LDB') !== false) ? '#047857' : ((strpos($code, 'JDB') !== false) ? '#6b21a8' : ((strpos($code, 'STB') !== false) ? '#ea580c' : ((strpos($code, 'APB') !== false) ? '#15803d' : ((strpos($code, 'LVB') !== false) ? '#b91c1c' : '#0284c7')))));
                    ?>
                    <div class="col-6 px-1">
                      <div class="pos-bank-option px-2.5 py-1.5 rounded border d-flex align-items-center justify-content-between" 
                           style="cursor: pointer; transition: all 0.15s; background: #ffffff; border-color: #cbd5e1 !important; min-height: 38px;"
                           onclick="selectPosBank(<?php echo $bAccount['id']; ?>, '<?php echo htmlspecialchars(addslashes($displayLabel)); ?>', '<?php echo htmlspecialchars($bQr); ?>', '<?php echo htmlspecialchars(addslashes($bAccount['account_number'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($bAccount['account_name'] ?? '')); ?>', this)">
                        <div class="d-flex align-items-center text-truncate" style="gap: 6px;">
                          <?php if (!empty($bLogo)): ?>
                            <img src="<?php echo htmlspecialchars($bLogo); ?>" style="width: 24px; height: 24px; object-fit: contain; border-radius: 5px; border: 1px solid #e2e8f0; background: #fff; padding: 1px; flex-shrink: 0;">
                          <?php else: ?>
                            <i class="fas fa-university text-primary" style="font-size: 1.1rem; flex-shrink: 0;"></i>
                          <?php endif; ?>
                          <div class="font-weight-bold text-dark text-truncate" style="font-size: 0.80rem; font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;" title="<?php echo htmlspecialchars($displayLabel); ?>">
                            <?php echo htmlspecialchars($displayLabel); ?>
                          </div>
                        </div>
                        <input type="radio" name="pos_selected_bank_id" value="<?php echo $bAccount['id']; ?>" style="accent-color: <?php echo $bColor; ?>; width: 14px; height: 14px; cursor: pointer; flex-shrink: 0;">
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <!-- QR preview only needs to be on the printed receipt, not re-shown here too —
                   selectPosBank() still fills #rc_bank_qr_img for the receipt; this block just
                   stays hidden so the cashier isn't looking at a redundant QR on this screen. -->
              <div class="text-center mt-2 pt-2 border-top" id="posActiveBankQrDisplaySection" style="display:none;">
                <div id="posActiveBankQrContainer" style="display: none;">
                  <div class="font-weight-bold mb-1.5" style="font-size:0.85rem;color:#7c3aed;">
                    <i class="fas fa-qrcode mr-1"></i> <?php echo htmlspecialchars(t('pos.scan_qr_to_pay', 'ສະແກນ QR Code ຊຳລະເງິນ')); ?> <span id="posActiveBankNameTitle" class="text-primary font-weight-bold"></span>
                  </div>
                  <div style="display: inline-block; position: relative; cursor: pointer;" onclick="zoomPosQrImage()" title="<?php echo htmlspecialchars(t('pos.zoom_qr_hd', 'ກົດເພື່ອຂະຫຍາຍ QR Code ໃຫຍ່ HD')); ?>">
                    <img id="posActiveBankQrImg" src=""
                         style="width: 180px; height: 180px; object-fit: contain; border-radius: 12px; border: 2.5px solid #6366f1; padding: 5px; background: #ffffff; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.20); transition: transform 0.2s;"
                         alt="Bank QR Code"
                         onmouseover="this.style.transform='scale(1.04)'"
                         onmouseout="this.style.transform='scale(1)'">
                    <div style="font-size: 0.85rem; color: #4f46e5; font-weight: 700; margin-top: 5px;">
                      <i class="fas fa-search-plus mr-1"></i> <?php echo htmlspecialchars(t('pos.zoom_qr_hd', 'ກົດເພື່ອຂະຫຍາຍໃຫຍ່ HD')); ?>
                    </div>
                  </div>
                </div>
                <div id="posNoQrMessage" style="display: none;" class="p-3 text-muted small font-weight-bold">
                  <i class="fas fa-info-circle text-info mr-1"></i> <?php echo htmlspecialchars(t('pos.no_qr_uploaded', 'ບັນຊີທະນາຄານນີ້ ຍັງບໍ່ໄດ້ອັບໂຫຼດ QR Code')); ?>
                </div>
              </div>
            </div>

            <!-- Tender currency (per bill) — feeds the daily report's "money received by currency" breakdown -->
            <?php
              $__coMeta  = function_exists('getCurrencyMeta') ? getCurrencyMeta() : ['KIP' => ['name' => 'ກີບ', 'symbol' => '₭']];
              $__coRates = function_exists('getExchangeRates') ? getExchangeRates($pdo) : [];
              $__coCurrencies = ['KIP'];
              foreach (['THB', 'USD', 'CNY'] as $__cc) {
                  if (!empty($__coRates[$__cc]) && (float)$__coRates[$__cc] > 0) $__coCurrencies[] = $__cc;
              }
            ?>
            <div id="checkoutCurrencyRow"<?php echo count($__coCurrencies) < 2 ? ' style="display:none;"' : ''; ?>>
              <div class="font-weight-bold mb-1" style="font-size:0.80rem;color:#475569;"><?php echo htmlspecialchars(t('pos.tender_currency_label', 'ສະກຸນເງິນທີ່ຮັບ')); ?></div>
              <select id="checkoutCurrency" class="form-control font-weight-bold mb-2" style="height:40px;border:2px solid #244886;border-radius:8px;color:#1a3666;" onchange="onCheckoutCurrencyChange(this.value)">
                <?php foreach ($__coCurrencies as $__cc): ?>
                  <option value="<?php echo $__cc; ?>"><?php echo htmlspecialchars(($__coMeta[$__cc]['name'] ?? $__cc) . ' (' . ($__coMeta[$__cc]['symbol'] ?? $__cc) . ')'); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- SINGLE: cash input -->
            <div id="singlePaySection">
              <div class="font-weight-bold mb-1" style="font-size:0.80rem;color:#475569;">
                <span id="singleAmountLbl"><?php echo htmlspecialchars(t('pos.amount_received_label', 'ຈຳນວນທີ່ຮັບ')); ?> <span class="text-danger">*</span></span>
                <span id="creditDepositLbl" style="display:none;"><?php echo htmlspecialchars(t('pos.credit_deposit_label', 'ເງິນມັດຈຳ (ຖ້າມີ)')); ?> <small class="text-muted font-weight-normal"><?php echo htmlspecialchars(t('pos.credit_deposit_hint', '— ບໍ່ຈ່າຍເລີຍ ໃຫ້ປ່ອຍເປັນ 0')); ?></small></span>
              </div>
              <div class="input-group" style="border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(36,72,134,0.10);">
                <input type="text" id="cashReceived" class="form-control text-right font-weight-bold"
                  placeholder="0" oninput="formatMoneyInput(this);calculateChange();"
                  style="height:42px;font-size:1.2rem;color:#1a3666;background:#fff;border:2px solid #244886;border-right:none;">
                <div class="input-group-append">
                  <span class="input-group-text font-weight-bold text-white" style="background:#244886;border:2px solid #244886;border-left:none;padding:0 14px;font-size:1rem;">₭</span>
                </div>
              </div>
              <!-- Shortcuts -->
              <div class="d-flex flex-wrap mt-2" style="gap:5px;">
                <button type="button" class="btn btn-sm font-weight-bold text-white" onclick="setExactAmount()"
                  style="background:linear-gradient(135deg,#244886,#1a3666);border:none;border-radius:7px;padding:6px 12px;font-size:0.80rem;">
                  <?php echo htmlspecialchars(t('pos.exact_amount', 'ພໍດີ')); ?>
                </button>
                <button type="button" class="btn btn-sm btn-quick-cash font-weight-bold" onclick="addCashShortcut(20000)" style="font-size:0.80rem;padding:6px 10px;">+20K</button>
                <button type="button" class="btn btn-sm btn-quick-cash font-weight-bold" onclick="addCashShortcut(50000)" style="font-size:0.80rem;padding:6px 10px;">+50K</button>
                <button type="button" class="btn btn-sm btn-quick-cash font-weight-bold" onclick="addCashShortcut(100000)" style="font-size:0.80rem;padding:6px 10px;">+100K</button>
                <button type="button" class="btn btn-sm btn-quick-cash font-weight-bold" onclick="addCashShortcut(500000)" style="font-size:0.80rem;padding:6px 10px;">+500K</button>
              </div>
            </div>

            <!-- CREDIT: when the customer promises to pay (ກຳນົດຊຳລະ) — optional -->
            <div id="creditDueSection" style="display:none;">
              <div class="font-weight-bold mb-1" style="font-size:0.80rem;color:#475569;"><i class="far fa-calendar-alt text-danger mr-1"></i><?php echo htmlspecialchars(t('pos.credit_due_label', 'ກຳນົດຊຳລະ (ມື້ທີ່ຈະມາຈ່າຍ)')); ?></div>
              <input type="date" id="creditDueDate" class="form-control font-weight-bold" onchange="calcCredit()" style="height:40px;border:2px solid #fca5a5;border-radius:8px;color:#b91c1c;">
              <div class="d-flex flex-wrap mt-1" style="gap:5px;">
                <?php foreach ([7, 15, 30] as $__dd): ?>
                  <button type="button" class="btn btn-sm font-weight-bold" onclick="setCreditDueIn(<?php echo $__dd; ?>)"
                    style="background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;border-radius:6px;font-size:0.8rem;padding:3px 10px;">
                    +<?php echo $__dd; ?> <?php echo htmlspecialchars(t('pos.credit_due_days', 'ວັນ')); ?>
                  </button>
                <?php endforeach; ?>
                <button type="button" class="btn btn-sm font-weight-bold" onclick="$('#creditDueDate').val(''); calcCredit();"
                  style="background:#fff;color:#64748b;border:1px solid #cbd5e1;border-radius:6px;font-size:0.8rem;padding:3px 10px;">
                  <?php echo htmlspecialchars(t('pos.credit_due_none', 'ບໍ່ກຳນົດ')); ?>
                </button>
              </div>
            </div>

            <!-- SPLIT: cash + qr inputs -->
            <div id="splitPaySection" style="display:none;">
              <!-- Cash -->
              <div class="mb-2">
                <div class="font-weight-bold mb-1 d-flex align-items-center" style="font-size:0.80rem;color:#16a34a;">
                  <?php echo htmlspecialchars(t('pos.pay_type_cash', 'ເງິນສົດ')); ?>
                </div>
                <div class="input-group" style="border-radius:9px;overflow:hidden;">
                  <input type="text" id="splitCashAmount" class="form-control text-right font-weight-bold"
                    placeholder="0" oninput="formatMoneyInput(this);calcSplitRemaining();"
                    style="height:40px;font-size:1.1rem;color:#16a34a;border:2px solid #22c55e;border-right:none;background:#f0fdf4;">
                  <div class="input-group-append">
                    <span class="input-group-text font-weight-bold text-white" style="background:#22c55e;border:2px solid #22c55e;border-left:none;padding:0 12px;font-size:0.95rem;">₭</span>
                  </div>
                </div>
                <div class="d-flex flex-wrap mt-1" style="gap:4px;">
                  <button onclick="setSplitCashExact()" style="flex:1;background:#dcfce7;color:#16a34a;border:1px solid #86efac;border-radius:6px;font-size:0.84rem;font-weight:700;padding:4px 6px;cursor:pointer;">
                    <?php echo htmlspecialchars(t('pos.exact_amount_all', 'ພໍດີທັງໝົດ')); ?>
                  </button>
                  <button onclick="addSplitCash(20000)" style="flex:1;background:#f0fdf4;color:#16a34a;border:1px solid #86efac;border-radius:6px;font-size:0.84rem;font-weight:700;padding:4px 5px;cursor:pointer;">+20K</button>
                  <button onclick="addSplitCash(50000)" style="flex:1;background:#f0fdf4;color:#16a34a;border:1px solid #86efac;border-radius:6px;font-size:0.84rem;font-weight:700;padding:4px 5px;cursor:pointer;">+50K</button>
                  <button onclick="addSplitCash(100000)" style="flex:1;background:#f0fdf4;color:#16a34a;border:1px solid #86efac;border-radius:6px;font-size:0.84rem;font-weight:700;padding:4px 5px;cursor:pointer;">+100K</button>
                  <button onclick="addSplitCash(500000)" style="flex:1;background:#f0fdf4;color:#16a34a;border:1px solid #86efac;border-radius:6px;font-size:0.84rem;font-weight:700;padding:4px 5px;cursor:pointer;">+500K</button>
                </div>
              </div>
              <!-- QR -->
              <div>
                <div class="font-weight-bold mb-1 d-flex align-items-center" style="font-size:0.80rem;color:#7c3aed;">
                  <?php echo htmlspecialchars(t('pos.pay_type_qr', 'ໂອນ / QR')); ?>
                </div>
                <div class="input-group" style="border-radius:9px;overflow:hidden;">
                  <input type="text" id="splitQrAmount" class="form-control text-right font-weight-bold"
                    placeholder="0" oninput="formatMoneyInput(this);calcSplitRemaining();"
                    style="height:40px;font-size:1.1rem;color:#7c3aed;border:2px solid #a78bfa;border-right:none;background:#faf5ff;">
                  <div class="input-group-append">
                    <span class="input-group-text font-weight-bold text-white" style="background:#7c3aed;border:2px solid #7c3aed;border-left:none;padding:0 12px;font-size:0.95rem;">₭</span>
                  </div>
                </div>
                <button onclick="fillSplitQrRemaining()" style="margin-top:5px;background:#f3e8ff;color:#7c3aed;border:1px solid #c4b5fd;border-radius:6px;font-size:0.84rem;font-weight:700;padding:4px 10px;cursor:pointer;">
                  <?php echo htmlspecialchars(t('pos.fill_remaining_amount', 'ຕື່ມຍອດທີ່ຍັງຄ້າງ')); ?>
                </button>
              </div>
            </div>

          </div><!-- /LEFT -->

          <!-- ===== RIGHT COLUMN ===== -->
          <div class="checkout-right-col">

            <!-- SINGLE: change display -->
            <div id="singleSummary">
              <div style="background:#f8fafc;border-radius:12px;border:1.5px solid #e2e8f0;padding:14px 16px;">
                <div style="font-size:0.9rem;color:#64748b;font-weight:600;margin-bottom:8px;text-transform:uppercase;letter-spacing:.4px;"><?php echo htmlspecialchars(t('pos.payment_summary', 'ສຳຫຼວດການຊຳລະ')); ?></div>
                <div class="d-flex justify-content-between mb-2" style="font-size:1.05rem;">
                  <span class="text-muted"><?php echo htmlspecialchars(t('pos.total_label', 'ລວມ:')); ?></span>
                  <span class="font-weight-bold text-dark text-truncate ml-2" id="singleTotalLbl">0 ₭</span>
                </div>
                <div class="d-flex justify-content-between mb-1" style="font-size:1.05rem;">
                  <span class="text-muted"><?php echo htmlspecialchars(t('pos.received_label', 'ຮັບມາ:')); ?></span>
                  <span class="font-weight-bold text-primary text-truncate ml-2" id="singleCashLbl">0 ₭</span>
                </div>
                <div style="border-top:1px dashed #e2e8f0;margin:8px 0;"></div>
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                  <span class="font-weight-bold" style="font-size:1.05rem;"><?php echo htmlspecialchars(t('pos.change_label', 'ເງິນທອນ:')); ?></span>
                  <span class="font-weight-bold text-success" id="changeDisplay" style="font-size:1.5rem;font-family:'Noto Sans Lao','Souliyo','Boon',sans-serif;word-break:break-all;">0 ₭</span>
                </div>
              </div>
            </div>

            <!-- CREDIT: summary (ຂາຍຕິດໜີ້) -->
            <div id="creditSummary" style="display:none;">
              <div style="background:#fef2f2;border-radius:12px;border:1.5px solid #fecaca;padding:14px 16px;">
                <div style="font-size:0.9rem;color:#b91c1c;font-weight:700;margin-bottom:8px;letter-spacing:.4px;"><i class="fas fa-hand-holding-usd mr-1"></i><?php echo htmlspecialchars(t('pos.credit_summary', 'ຂາຍຕິດໜີ້')); ?></div>
                <div class="d-flex justify-content-between mb-2" style="font-size:1rem;">
                  <span class="text-muted"><?php echo htmlspecialchars(t('pos.credit_customer', 'ລູກຄ້າ:')); ?></span>
                  <span class="font-weight-bold text-dark text-truncate ml-2" id="creditCustomerLbl">-</span>
                </div>
                <div class="d-flex justify-content-between mb-1" style="font-size:1rem;">
                  <span class="text-muted"><?php echo htmlspecialchars(t('pos.credit_bill_total', '① ຍອດບິນນີ້:')); ?></span>
                  <span class="font-weight-bold text-dark text-truncate ml-2" id="creditTotalLbl">0 ₭</span>
                </div>
                <div class="d-flex justify-content-between mb-1" style="font-size:1rem;">
                  <span class="text-muted"><?php echo htmlspecialchars(t('pos.credit_deposit_today', '② ຈ່າຍມັດຈຳມື້ນີ້:')); ?></span>
                  <span class="font-weight-bold text-primary text-truncate ml-2" id="creditDepositDisplay">0 ₭</span>
                </div>
                <div style="border-top:1px dashed #fecaca;margin:8px 0;"></div>
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                  <span class="font-weight-bold" style="font-size:1rem;color:#b91c1c;"><?php echo htmlspecialchars(t('pos.credit_owed_this_bill', 'ຕິດໜີ້ບິນນີ້ (① − ②):')); ?></span>
                  <span class="font-weight-bold" id="creditOwedDisplay" style="font-size:1.5rem;color:#dc2626;font-family:'Noto Sans Lao','Souliyo','Boon',sans-serif;word-break:break-all;">0 ₭</span>
                </div>
                <!-- Customer's other unpaid bills (api/pos/customer_debt_info.php) -->
                <div id="creditOldDebtBox" style="display:none;margin-top:8px;padding-top:8px;border-top:1px dashed #fecaca;">
                  <div class="d-flex justify-content-between mb-1" style="font-size:0.95rem;">
                    <span class="text-muted"><?php echo htmlspecialchars(t('pos.credit_old_debt', 'ໜີ້ເກົ່າທີ່ຍັງຄ້າງ:')); ?> <small id="creditOldDebtBills"></small></span>
                    <span class="font-weight-bold text-dark text-truncate ml-2" id="creditOldDebtLbl">0 ₭</span>
                  </div>
                  <div class="d-flex justify-content-between" style="font-size:1rem;">
                    <span class="font-weight-bold" style="color:#7f1d1d;"><?php echo htmlspecialchars(t('pos.credit_total_after', 'ໜີ້ລວມຫຼັງບິນນີ້:')); ?></span>
                    <span class="font-weight-bold text-truncate ml-2" id="creditTotalAfterLbl" style="color:#7f1d1d;">0 ₭</span>
                  </div>
                  <div id="creditOverdueLbl" class="mt-1 font-weight-bold" style="display:none;font-size:0.82rem;color:#b45309;"></div>
                </div>
                <div class="d-flex justify-content-between mt-2" style="font-size:0.95rem;">
                  <span class="text-muted"><i class="far fa-calendar-alt mr-1"></i><?php echo htmlspecialchars(t('pos.credit_due_short', 'ກຳນົດຊຳລະ:')); ?></span>
                  <span class="font-weight-bold text-dark ml-2" id="creditDueLbl">-</span>
                </div>
                <div id="creditWarning" class="mt-2 font-weight-bold" style="display:none;font-size:0.85rem;color:#b91c1c;"></div>
                <div class="mt-2" style="font-size:0.78rem;color:#7f1d1d;line-height:1.45;background:#fff;border-radius:8px;padding:6px 8px;border:1px solid #fecaca;">
                  <i class="fas fa-info-circle mr-1"></i><?php echo htmlspecialchars(t('pos.credit_explain', 'ສິນຄ້າຈະຖືກຕັດສະຕັອກທັນທີ. ຍອດ "ຕິດໜີ້ບິນນີ້" ຈະຖືກບັນທຶກເປັນໜີ້ຂອງລູກຄ້າ — ເມື່ອລູກຄ້າມາຈ່າຍ ໃຫ້ຮັບເງິນຢູ່ເມນູ ລູກຄ້າຕິດໜີ້.')); ?>
                </div>
              </div>
            </div>

            <!-- SPLIT: summary -->
            <div id="splitSummary" style="display:none;">
              <div style="background:#f8fafc;border-radius:12px;border:1.5px solid #e2e8f0;padding:14px 16px;">
                <div style="font-size:0.9rem;color:#64748b;font-weight:600;margin-bottom:8px;text-transform:uppercase;letter-spacing:.4px;"><?php echo htmlspecialchars(t('pos.payment_summary', 'ສຳຫຼວດການຊຳລະ')); ?></div>
                <div class="d-flex justify-content-between mb-2" style="font-size:1.05rem;">
                  <span class="text-muted"><?php echo htmlspecialchars(t('pos.grand_total_label', 'ຍອດທັງໝົດ:')); ?></span>
                  <span class="font-weight-bold text-dark text-truncate ml-2" id="splitTotal">0 ₭</span>
                </div>
                <div class="d-flex justify-content-between mb-2" style="font-size:1.05rem;">
                  <span class="text-muted"><?php echo htmlspecialchars(t('pos.pay_type_cash', 'ເງິນສົດ')); ?>:</span>
                  <span class="font-weight-bold text-success text-truncate ml-2" id="splitCashDisplay">0 ₭</span>
                </div>
                <div class="d-flex justify-content-between mb-1" style="font-size:1.05rem;">
                  <span class="text-muted"><?php echo htmlspecialchars(t('pos.pay_type_qr', 'ໂອນ / QR')); ?>:</span>
                  <span class="font-weight-bold text-truncate ml-2" style="color:#7c3aed;" id="splitQrDisplay">0 ₭</span>
                </div>
                <div style="border-top:1px dashed #e2e8f0;margin:8px 0;"></div>
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                  <span class="font-weight-bold" style="font-size:1.05rem;" id="splitStatusLabel"><?php echo htmlspecialchars(t('pos.split_status_remaining', 'ຍັງຄ້າງ:')); ?></span>
                  <span class="font-weight-bold" style="font-size:1.45rem;font-family:'Noto Sans Lao','Souliyo','Boon',sans-serif;word-break:break-all;" id="splitRemainingDisplay">0 ₭</span>
                </div>
              </div>
            </div>

            <!-- Numpad -->
            <div style="flex:1;">
              <div class="font-weight-bold mb-1" style="font-size:0.76rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;"></div>
              <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:5px;">
                <?php foreach(['7','8','9','4','5','6','1','2','3'] as $n): ?>
                <button onclick="numpadPress('<?= $n ?>')" class="checkout-numpad-btn"
                  style="border-radius:8px;border:1.5px solid #e2e8f0;background:#fff;font-weight:700;color:#1e293b;cursor:pointer;transition:background 0.1s;"
                  onmouseover="this.style.background='#eef2fb';" onmouseout="this.style.background='#fff';"><?= $n ?></button>
                <?php endforeach; ?>
                <button onclick="numpadPress('000')" class="checkout-numpad-btn"
                  style="border-radius:8px;border:1.5px solid #e2e8f0;background:#fff;font-weight:700;color:#1e293b;cursor:pointer;transition:background 0.1s;"
                  onmouseover="this.style.background='#eef2fb';" onmouseout="this.style.background='#fff';">000</button>
                <button onclick="numpadPress('0')" class="checkout-numpad-btn"
                  style="border-radius:8px;border:1.5px solid #e2e8f0;background:#fff;font-weight:700;color:#1e293b;cursor:pointer;transition:background 0.1s;"
                  onmouseover="this.style.background='#eef2fb';" onmouseout="this.style.background='#fff';">0</button>
                <button onclick="numpadBackspace()" class="checkout-numpad-btn"
                  style="border-radius:8px;border:1.5px solid #fca5a5;background:#fff5f5;font-weight:700;color:#ef4444;cursor:pointer;transition:background 0.1s;"
                  onmouseover="this.style.background='#fee2e2';" onmouseout="this.style.background='#fff5f5';">
                  <i class="fas fa-backspace"></i>
                </button>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex" style="gap:8px;margin-top:4px;">
              <button type="button" class="btn btn-light font-weight-bold flex-fill py-2" data-dismiss="modal"
                style="border-radius:9px;border:1.5px solid #cbd5e1;color:#475569;font-size:0.86rem;">
                <?php echo htmlspecialchars(t('pos.cancel', 'ຍົກເລີກ')); ?>
              </button>
              <button type="button" class="btn font-weight-bold flex-fill py-2 text-white" id="btnSubmitPayment"
                onclick="processCheckout()"
                style="background:linear-gradient(135deg,#244886,#1a3666);border:none;border-radius:9px;font-size:0.90rem;box-shadow:0 4px 14px rgba(36,72,134,0.35);">
                <span id="btnSubmitPayLbl"><?php echo htmlspecialchars(t('pos.confirm_payment', 'ຢືນຢັນຊຳລະ')); ?></span>
                <span id="btnSubmitCreditLbl" style="display:none;"><?php echo htmlspecialchars(t('pos.credit_submit', 'ບັນທຶກຂາຍຕິດໜີ້')); ?></span>
              </button>
            </div>

          </div><!-- /RIGHT -->

        </div><!-- /grid -->
      </div><!-- /modal-body -->

    </div>
  </div>
</div>

<!-- Modal: Receipt Print -->
<div class="modal fade" id="receiptModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
  <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius:14px;overflow:hidden;">
      <div class="modal-header bg-success text-white py-3 px-4">
        <h6 class="modal-title font-weight-bold"><i class="fas fa-receipt mr-1"></i> <?php echo htmlspecialchars(t('pos.receipt_success_title', 'ໃບບິນຮັບເງິນສຳເລັດ')); ?></h6>
        <button type="button" class="close text-white" onclick="resetPOS()"><span>&times;</span></button>
      </div>
      <?php $rcAreaId = 'receiptPrintArea'; $rcP = 'rc_'; require __DIR__ . '/../../../layouts/receipt_template.php'; ?>
      <div class="modal-footer border-0 p-3 bg-light">
        <button type="button" class="btn btn-secondary btn-sm font-weight-bold" onclick="resetPOS()"><?php echo htmlspecialchars(t('pos.close', 'ປິດ')); ?></button>
        <button type="button" class="btn btn-primary btn-sm font-weight-bold px-3" onclick="printReceiptAuto()"><i class="fas fa-print mr-1"></i> <?php echo htmlspecialchars(t('pos.print_receipt', 'ພິມໃບບິນ')); ?></button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: QR Code Enlarge / Zoom HD -->
<div class="modal fade" id="qrZoomModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1070;">
  <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 380px;">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 18px; overflow: hidden; background: #ffffff;">
      <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, #4f46e5, #3730a3);">
        <h6 class="modal-title font-weight-bold m-0" style="font-size: 1rem;">
          <i class="fas fa-qrcode mr-2 text-warning"></i> <?php echo htmlspecialchars(t('pos.qr_pay_title', 'QR Code ຊຳລະເງິນ')); ?>
        </h6>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body text-center p-4">
        <div class="font-weight-bold text-dark mb-2" id="qrZoomBankTitle" style="font-size: 1.05rem;">
          <?php echo htmlspecialchars(t('pos.scan_qr_to_pay', 'ສະແກນ QR Code ຊຳລະເງິນ')); ?>
        </div>
        <div class="p-3 d-inline-block shadow-sm" style="background: #ffffff; border: 3px solid #6366f1; border-radius: 16px;">
          <img id="qrZoomImg" src="" style="width: 280px; height: 280px; object-fit: contain; image-rendering: -webkit-optimize-contrast; image-rendering: crisp-edges; display: block;" alt="Zoomed QR Code">
        </div>
        <p class="text-muted mt-3 mb-0" style="font-size: 0.85rem;">
          <i class="fas fa-mobile-alt mr-1 text-primary"></i> <?php echo htmlspecialchars(t('pos.use_bank_app_hint', 'ໃຊ້ແອັບທະນາຄານ ສະແກນ QR Code ເພື່ອຊຳລະເງິນ')); ?>
        </p>
      </div>
      <div class="modal-footer border-0 bg-light p-2.5 justify-content-center">
        <button type="button" class="btn btn-secondary font-weight-bold px-4 py-2" data-dismiss="modal" style="border-radius: 10px;"><?php echo htmlspecialchars(t('pos.close', 'ປິດ')); ?></button>
      </div>
    </div>
  </div>
</div>

<script>
function zoomPosQrImage() {
  var src = $('#posActiveBankQrImg').attr('src');
  var bankName = $('#posActiveBankNameTitle').text() || '';
  if (src && src.indexOf('qr_placeholder') === -1) {
    $('#qrZoomImg').attr('src', src);
    $('#qrZoomBankTitle').text('ສະແກນ QR Code ' + bankName);
    $('#qrZoomModal').modal('show');
  }
}
</script>
