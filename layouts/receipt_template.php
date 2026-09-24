<?php
// Customer receipt markup — ONE template for the checkout receipt (pages/pos/partials/checkout_modal.php)
// and the reprint from the reports (pages/reports/partials/reports_modal.php), so a reprinted bill
// is exactly the bill printed at the sale. Callers set:
//   $rcAreaId  id of the printable wrapper ('receiptPrintArea' / 'reportReceiptPrintArea')
//   $rcP       id prefix of every field ('rc_' / 'rc_rep_') — the JS fills #{prefix}bill, #{prefix}items, …
//   $company   getCompanyInfoForBranch() row;  $base_path  path back to the app root
$rcAreaId = $rcAreaId ?? 'receiptPrintArea';
$rcP      = $rcP ?? 'rc_';
?>
      <div class="modal-body p-3" id="<?php echo $rcAreaId; ?>" style="font-family:'Noto Sans Lao', 'Souliyo', 'Boon', Arial, sans-serif;font-size:17px;font-weight:600;line-height:1.5;color:#000;-webkit-font-smoothing:antialiased;text-rendering:optimizeLegibility;">
        <div class="text-center mb-2">
          <!-- Queue number (coffee-shop style) — "ຄິວທີ N" on one line, above the logo. Shown only when enabled. -->
          <div id="<?php echo $rcP; ?>queue_wrap" style="display:none;margin:2px 0 8px;font-size:34px;font-weight:900;line-height:1.15;color:#000;">
            <?php echo htmlspecialchars(t('pos.receipt_queue_label', 'ບັດຄິວທີ')); ?> <span id="<?php echo $rcP; ?>queue_no">-</span>
          </div>
          <?php
            $logoName = !empty($company['img_url']) ? basename($company['img_url']) : 'logo.png';
            $logoPath = $base_path . 'assets/img/logo/' . $logoName;
          ?>
          <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="Logo" class="receipt-logo"
            style="max-width:80px;height:auto;max-height:80px;object-fit:contain;margin:10px auto 2px auto;display:block;"
            onerror="this.onerror=null;this.src='<?php echo $base_path; ?>assets/img/logo/logo.png';">
          <h6 class="font-weight-bold mb-0" style="font-size:20px;color:#000;font-weight:700;"><?php echo htmlspecialchars($company['com_name_la']); ?></h6>
          <div class="receipt-header-address" style="font-size:15px;font-weight:600;color:#000;line-height:1.4;margin-top:2px;"><?php echo htmlspecialchars($company['com_address']); ?></div>
          <div class="receipt-header-tel" style="font-size:15px;font-weight:600;color:#000;line-height:1.4;"><?php echo htmlspecialchars(t('pos.receipt_tel', 'ໂທ:')); ?> <?php echo htmlspecialchars($company['com_tel']); ?></div>
        </div>
        <div style="border-top:1px dashed #000;margin:6px 0;"></div>
        <?php
          $cTaxType = !empty($company['tax_type']) ? $company['tax_type'] : 'none';
          $cTaxId = !empty($company['tax_id']) ? $company['tax_id'] : '';
          $showTaxId = ($cTaxType !== 'none' && !empty($cTaxId));
        ?>
        <div class="d-flex justify-content-between" id="<?php echo $rcP; ?>tax_id_row" style="color:#000;font-weight:600;<?php echo $showTaxId ? '' : 'display:none;'; ?>"><span><?php echo htmlspecialchars(t('pos.receipt_tax_id', 'ເລກປະຈຳຕົວຜູ້ເສຍອາກອນ:')); ?></span><span id="<?php echo $rcP; ?>tax_id" style="font-weight:700;"><?php echo htmlspecialchars($cTaxId); ?></span></div>
        <div class="d-flex justify-content-between" style="color:#000;font-weight:600;"><span><?php echo htmlspecialchars(t('pos.receipt_bill_no', 'ເລກບິນ:')); ?></span><span id="<?php echo $rcP; ?>bill" style="font-weight:700;">-</span></div>
        <div class="d-flex justify-content-between" style="color:#000;font-weight:600;"><span><?php echo htmlspecialchars(t('pos.receipt_date', 'ວັນທີ:')); ?></span><span id="<?php echo $rcP; ?>date">-</span></div>
        <div class="d-flex justify-content-between" style="color:#000;font-weight:600;"><span><?php echo htmlspecialchars(t('pos.receipt_cashier', 'ຜູ້ຂາຍ:')); ?></span><span id="<?php echo $rcP; ?>cashier">-</span></div>
        <div class="d-flex justify-content-between" style="color:#000;font-weight:600;"><span><?php echo htmlspecialchars(t('pos.receipt_customer', 'ລູກຄ້າ:')); ?></span><span id="<?php echo $rcP; ?>customer"><?php echo htmlspecialchars(t('pos.customer_default', 'ລູກຄ້າທົ່ວໄປ')); ?></span></div>
        <div class="d-flex justify-content-between" id="<?php echo $rcP; ?>customer_phone_row" style="color:#000;font-weight:600;display:none;"><span><?php echo htmlspecialchars(t('pos.receipt_customer_phone', 'ເບີໂທ:')); ?></span><span id="<?php echo $rcP; ?>customer_phone">-</span></div>
        <div style="border-top:1px dashed #000;margin:6px 0;"></div>
        <table style="width:100%;font-size:15px;color:#000;">
          <thead>
            <tr style="border-bottom:1px dashed #000;">
              <th style="text-align:left;padding-bottom:3px;font-weight:700;color:#000;"><?php echo htmlspecialchars(t('pos.receipt_col_item', 'ລາຍການ')); ?></th>
              <th style="text-align:center;padding-bottom:3px;font-weight:700;width:50px;color:#000;"><?php echo htmlspecialchars(t('pos.receipt_col_qty', 'ຈຳນວນ')); ?></th>
              <th style="text-align:right;padding-bottom:3px;font-weight:700;width:75px;color:#000;"><?php echo htmlspecialchars(t('pos.receipt_col_price', 'ລາຄາ')); ?></th>
            </tr>
          </thead>
          <tbody id="<?php echo $rcP; ?>items" style="color:#000;font-weight:600;"></tbody>
        </table>
        <div style="border-top:1px dashed #000;margin:6px 0;"></div>
        <div id="<?php echo $rcP; ?>payment_rows" style="color:#000;font-weight:600;">
          <div class="d-flex justify-content-between" id="<?php echo $rcP; ?>cash_row"><span><?php echo htmlspecialchars(t('pos.receipt_cash_received', 'ຮັບເງິນສົດ:')); ?></span><span id="<?php echo $rcP; ?>cash_amt">0 ₭</span></div>
          <div class="d-flex justify-content-between" id="<?php echo $rcP; ?>qr_row"><span><?php echo htmlspecialchars(t('pos.receipt_transfer_received', 'ຮັບເງິນໂອນ:')); ?></span><span id="<?php echo $rcP; ?>qr_amt">0 ₭</span></div>
        </div>
        <div style="border-top:1px dashed #000;margin:6px 0;"></div>
        <div class="d-flex justify-content-between" style="color:#000;font-weight:600;"><span><?php echo htmlspecialchars(t('pos.total_label', 'ລວມ:')); ?></span><span id="<?php echo $rcP; ?>subtotal">0 ₭</span></div>
        <div class="d-flex justify-content-between" style="color:#000;font-weight:600;"><span><?php echo htmlspecialchars(t('pos.receipt_discount', 'ສ່ວນຫຼຸດ:')); ?></span><span id="<?php echo $rcP; ?>discount">0 ₭</span></div>
        <div class="d-flex justify-content-between" id="<?php echo $rcP; ?>points_discount_row" style="color:#000;font-weight:600;display:none;font-size:0.92em;"><span>&nbsp;&nbsp;<?php echo htmlspecialchars(t('pos.receipt_points_discount', '(ໃນນັ້ນ ແລກຄະແນນ:')); ?></span><span><span id="<?php echo $rcP; ?>points_discount">0 ₭</span>)</span></div>
        <div class="d-flex justify-content-between" id="<?php echo $rcP; ?>vat_row" style="color:#000;font-weight:600;display:none;"><span id="<?php echo $rcP; ?>vat_label"><?php echo htmlspecialchars(t('pos.receipt_vat', 'ອມພ (VAT):')); ?></span><span id="<?php echo $rcP; ?>vat">0 ₭</span></div>
        <div class="d-flex justify-content-between font-weight-bold" style="font-size:19px;color:#000;font-weight:700;"><span><?php echo htmlspecialchars(t('pos.receipt_net_total', 'ຍອດສຸດທິ:')); ?></span><span id="<?php echo $rcP; ?>total">0 ₭</span></div>
        <!-- ຍອດຊຳລະສຸດທິ ໃນ 4 ສະກຸນເງິນ (ຄິດຈາກກີບ × ອັດຕາລ່າສຸດ) -->
        <div id="<?php echo $rcP; ?>multicur" style="color:#000;font-weight:600;font-size:14.5px;margin-top:4px;border-top:1px dotted #000;padding-top:4px;">
          <div class="d-flex justify-content-between"><span><?php echo htmlspecialchars(t('pos.receipt_cur_kip', 'ກີບ (LAK):')); ?></span><span id="<?php echo $rcP; ?>total_kip">0 ₭</span></div>
          <div class="d-flex justify-content-between"><span><?php echo htmlspecialchars(t('pos.receipt_cur_thb', 'ບາດ (THB):')); ?></span><span id="<?php echo $rcP; ?>total_thb">-</span></div>
          <div class="d-flex justify-content-between"><span><?php echo htmlspecialchars(t('pos.receipt_cur_usd', 'ໂດລາ (USD):')); ?></span><span id="<?php echo $rcP; ?>total_usd">-</span></div>
          <div class="d-flex justify-content-between"><span><?php echo htmlspecialchars(t('pos.receipt_cur_cny', 'ຢວນ (CNY):')); ?></span><span id="<?php echo $rcP; ?>total_cny">-</span></div>
        </div>
        <div style="border-top:1px dashed #000;margin:6px 0;"></div>
        <div class="d-flex justify-content-between font-weight-bold" style="color:#000;font-weight:700;"><span><?php echo htmlspecialchars(t('pos.change_label', 'ເງິນທອນ:')); ?></span><span id="<?php echo $rcP; ?>change">0 ₭</span></div>
        <div class="d-flex justify-content-between" id="<?php echo $rcP; ?>points_redeem_row" style="color:#000;font-weight:600;display:none;"><span><?php echo htmlspecialchars(t('pos.receipt_points_redeemed', 'ຄະແນນທີ່ແລກ:')); ?></span><span id="<?php echo $rcP; ?>points_redeemed" style="font-weight:700;">0</span></div>
        <div class="d-flex justify-content-between" id="<?php echo $rcP; ?>points_row" style="color:#000;font-weight:600;display:none;"><span><?php echo htmlspecialchars(t('pos.receipt_points_earned', 'ຄະແນນທີ່ໄດ້ຮັບ:')); ?></span><span id="<?php echo $rcP; ?>points_earned" style="font-weight:700;">0</span></div>
        <div class="d-flex justify-content-between" id="<?php echo $rcP; ?>points_balance_row" style="color:#000;font-weight:600;display:none;"><span><?php echo htmlspecialchars(t('pos.receipt_points_balance', 'ຄະແນນຄົງເຫຼືອ:')); ?></span><span id="<?php echo $rcP; ?>points_balance" style="font-weight:700;">0</span></div>
        <div style="border-top:1px dashed #000;margin:6px 0;"></div>
        <?php 
          $storeQrName = !empty($company['qr_img']) ? $company['qr_img'] : '';
          $storeQrPath = !empty($storeQrName) ? ($base_path . 'assets/img/qr/' . $storeQrName) : ($base_path . 'assets/img/qr_placeholder.png');
        ?>
        <div class="text-center my-2 receipt-qr-box" style="display:none;">
          <img id="<?php echo $rcP; ?>bank_qr_img" src="<?php echo htmlspecialchars($storeQrPath); ?>" alt="QR Code" class="receipt-qr-img"
               style="max-width:130px;max-height:130px;width:130px;height:auto;object-fit:contain;margin:4px auto 2px auto;display:block;image-rendering:-webkit-optimize-contrast;image-rendering:crisp-edges;image-rendering:pixelated;"
               onerror="this.onerror=null;this.src='<?php echo $base_path; ?>assets/img/qr_placeholder.png';">
          <div id="<?php echo $rcP; ?>bank_name_lbl" style="font-size:15px;font-weight:800;color:#000;margin-top:3px;"><?php echo htmlspecialchars(t('pos.scan_qr_to_pay', 'ສະແກນ QR Code ຊຳລະເງິນ')); ?></div>
        </div>
        <?php
          // The "receipt footer" (tbcompanyinfo.barcode) is seeded with the default thank-you
          // text. Only honour it verbatim when the shop actually customised it — otherwise use
          // t() so the line follows the language (and the flag switcher can retranslate it).
          $footerDefaults = [
            'ຂອບໃຈທີ່ມາອຸດໜູນ, ໂອກາດໜ້າເຊີນໃໝ່!',
            'Thank you for your purchase, see you again!',
            'ขอบคุณที่อุดหนุน แล้วพบกันใหม่!',
            '感谢惠顾，欢迎下次光临！',
          ];
          $footerRaw = trim((string)($company['barcode'] ?? ''));
          $footerMsg = ($footerRaw === '' || in_array($footerRaw, $footerDefaults, true))
            ? t('pos.receipt_thank_you', 'ຂອບໃຈທີ່ມາອຸດໜູນ, ໂອກາດໜ້າເຊີນໃໝ່!')
            : $footerRaw;
        ?>
        <div class="text-center receipt-footer-msg" style="margin-top:10px !important; padding-top:8px; border-top:1px dashed #000; font-size:15px; font-weight:700; color:#000; text-align:center;"><?php echo htmlspecialchars($footerMsg); ?></div>
      </div>
