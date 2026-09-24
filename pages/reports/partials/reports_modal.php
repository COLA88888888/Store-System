<?php
  // The receipt header (logo/name/address/phone) below needs $company — several pages that
  // include this partial (item_sales.php, all_sales.php, sales_details.php, daily_report.php,
  // delete_bills.php) never fetch it themselves, so without this guard the reprinted receipt
  // falls back to blank fields and the literal string "MiniPOS" instead of the real store info.
  if (empty($company)) {
      // Shared name/address/phone comes from the main branch; logo from the active branch.
      $company = getCompanyInfoForBranch($pdo, getActiveStoreId($pdo));
  }
?>
<!-- Modal: Bill Details Popup -->
<div class="modal fade" id="reportBillModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
      <div class="modal-header bg-primary text-white py-3 px-4" style="background: linear-gradient(135deg, #2563eb, #1d4ed8) !important;">
        <h5 class="modal-title font-weight-bold" style="font-size: 1.05rem;">
          <i class="fas fa-receipt mr-2"></i> <?php echo htmlspecialchars(t('reports.modal_bill_detail_title', 'ລາຍລະອຽດໃບບິນ:')); ?> <span id="modal_bill_no" class="text-warning"></span>
        </h5>
        <button type="button" class="close text-white opacity-100" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-4">
        <!-- Bill Header Info -->
        <div class="row mb-3 bg-light p-2.5 rounded border mx-0" style="font-size: 0.86rem; row-gap: 6px;">
          <div class="col-6">
            <span class="text-muted"><?php echo htmlspecialchars(t('reports.modal_date_time_label', 'ວັນທີ-ເວລາ:')); ?></span> <br>
            <strong id="modal_date" class="text-dark"></strong>
          </div>
          <div class="col-6 text-right">
            <span class="text-muted"><?php echo htmlspecialchars(t('reports.modal_seller_label', 'ຜູ້ຂາຍ:')); ?></span> <br>
            <strong id="modal_cashier" class="text-dark"></strong>
          </div>
          <div class="col-12 mt-1 border-top pt-1 text-muted d-flex justify-content-between flex-wrap" style="gap: 4px;">
            <div><?php echo htmlspecialchars(t('reports.modal_customer_label', 'ລູກຄ້າ:')); ?> <strong id="modal_customer" class="text-dark"></strong></div>
            <div id="modal_bank_wrapper" style="display:none;"><i class="fas fa-university text-info mr-1"></i> <?php echo htmlspecialchars(t('reports.modal_bank_transfer_label', 'ໂອນຜ່ານທະນາຄານ:')); ?> <strong id="modal_bank_name" class="text-primary font-weight-bold"></strong></div>
          </div>
        </div>

        <!-- Bill Items Table -->
        <div class="table-responsive mb-3 border rounded">
          <table class="table table-sm table-striped mb-0" style="font-size: 0.85rem;">
            <thead class="bg-light">
              <tr>
                <th class="text-left"><?php echo htmlspecialchars(t('reports.col_product_item', 'ລາຍການສິນຄ້າ')); ?></th>
                <th class="text-center" style="width: 70px;"><?php echo htmlspecialchars(t('reports.col_qty', 'ຈຳນວນ')); ?></th>
                <th class="text-right" style="width: 90px;"><?php echo htmlspecialchars(t('reports.col_price', 'ລາຄາ')); ?></th>
                <th class="text-right" style="width: 100px;"><?php echo htmlspecialchars(t('reports.col_total_kip', 'ລວມ (₭)')); ?></th>
              </tr>
            </thead>
            <tbody id="modal_items_body">
              <!-- Loaded via AJAX -->
            </tbody>
          </table>
        </div>

        <!-- Bill Financial Summary -->
        <div class="border-top pt-2" style="font-size: 0.88rem;">
          <div class="d-flex justify-content-between mb-1">
            <span class="text-muted"><?php echo htmlspecialchars(t('reports.modal_subtotal_label', 'ລາຄາລວມ:')); ?></span>
            <span id="modal_subtotal" class="font-weight-bold text-dark">0 ₭</span>
          </div>
          <div class="d-flex justify-content-between mb-1 text-danger">
            <span><?php echo htmlspecialchars(t('reports.modal_discount_label', 'ສ່ວນຫຼຸດ:')); ?></span>
            <span id="modal_discount" class="font-weight-bold">0 ₭</span>
          </div>
          <div class="d-flex justify-content-between mb-2 pt-1 border-top" style="font-size: 1.05rem;">
            <strong class="text-dark"><?php echo htmlspecialchars(t('reports.modal_net_total_label', 'ຍອດຂາຍສຸດທິ:')); ?></strong>
            <strong id="modal_net_total" class="text-success">0 ₭</strong>
          </div>

          <div class="bg-light p-2.5 rounded border" style="font-size: 0.84rem;">
            <div class="d-flex justify-content-between mb-1" id="modal_cash_row">
              <span class="text-muted"><?php echo htmlspecialchars(t('reports.modal_cash_received_label', 'ຮັບເງິນສົດ:')); ?></span>
              <span id="modal_cash_rec" class="font-weight-bold text-dark">0 ₭</span>
            </div>
            <div class="d-flex justify-content-between mb-1" id="modal_qr_row">
              <span class="text-muted"><?php echo htmlspecialchars(t('reports.modal_qr_received_label', 'ຮັບເງິນໂອນ:')); ?></span>
              <span id="modal_qr_rec" class="font-weight-bold text-primary">0 ₭</span>
            </div>
            <div class="d-flex justify-content-between mb-1 border-top pt-1">
              <span class="text-muted"><?php echo htmlspecialchars(t('reports.modal_change_label', 'ເງິນທອນ:')); ?></span>
              <span id="modal_change" class="font-weight-bold text-info">0 ₭</span>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer bg-light py-2 px-4">
        <button type="button" class="btn btn-secondary btn-sm font-weight-bold px-3" data-dismiss="modal" style="border-radius: 8px;">
          <?php echo htmlspecialchars(t('reports.close_btn', 'ປິດ')); ?>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: POS Thermal Receipt Print (Exact POS Format) -->
<div class="modal fade" id="reportReceiptModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius:14px;overflow:hidden;">
      <div class="modal-header bg-success text-white py-3 px-4">
        <h6 class="modal-title font-weight-bold"><i class="fas fa-receipt mr-1"></i> <?php echo htmlspecialchars(t('reports.receipt_title', 'ໃບບິນຮັບເງິນ')); ?></h6>
        <button type="button" class="close text-white opacity-100" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <?php $base_path = '../../'; $rcAreaId = 'reportReceiptPrintArea'; $rcP = 'rc_rep_'; require __DIR__ . '/../../../layouts/receipt_template.php'; ?>
      <div class="modal-footer border-0 p-3 bg-light">
        <button type="button" class="btn btn-secondary btn-sm font-weight-bold" data-dismiss="modal"><?php echo htmlspecialchars(t('reports.close_btn', 'ປິດ')); ?></button>
        <button type="button" class="btn btn-primary btn-sm font-weight-bold px-3" onclick="doPrintReportReceipt()"><i class="fas fa-print mr-1"></i> <?php echo htmlspecialchars(t('reports.print_receipt_btn', 'ພິມໃບບິນ')); ?></button>
      </div>
    </div>
  </div>
</div>

<!-- Delivery Note print template (ບິນສົ່ງເຄື່ອງ) — hidden; filled by printDeliveryNote(billNo) from get_bill_details, printed 80mm -->
<div id="deliveryNoteReportArea" style="display:none;font-family:'Noto Sans Lao','Souliyo','Boon',Arial,sans-serif;font-size:13px;font-weight:600;line-height:1.5;color:#000;padding:8px 6px;">
  <img id="dn_rep_logo" src="" alt="Logo" style="max-width:70px;max-height:70px;object-fit:contain;display:block;margin:6px auto 4px auto;" onerror="this.onerror=null;this.src='<?php echo $base_path; ?>assets/img/logo/logo.png';">
  <div style="text-align:center;font-size:14px;font-weight:800;letter-spacing:1px;margin-bottom:4px;"><i class="fas fa-truck mr-1"></i> <?php echo htmlspecialchars(t('reports.delivery_note', 'ບິນສົ່ງເຄື່ອງ')); ?></div>
  <div style="display:flex;justify-content:space-between;"><span><?php echo htmlspecialchars(t('reports.receipt_bill_no_label', 'ເລກບິນ:')); ?></span><span id="dn_rep_bill" style="font-weight:700;">-</span></div>
  <div style="display:flex;justify-content:space-between;"><span><?php echo htmlspecialchars(t('reports.receipt_date_label', 'ວັນທີ:')); ?></span><span id="dn_rep_date">-</span></div>
  <div style="border-top:1px dashed #000;margin:6px 0;"></div>
  <!-- Sender = the staff member who made this sale: name + phone right after the label -->
  <div style="display:flex;justify-content:space-between;"><span style="font-weight:800;"><i class="fas fa-store mr-1"></i> <?php echo htmlspecialchars(t('reports.dn_sender', 'ຜູ້ສົ່ງ')); ?>:</span><span id="dn_rep_sender_name" style="font-weight:700;">-</span></div>
  <div style="display:flex;justify-content:space-between;" id="dn_rep_sender_phone_row"><span><?php echo htmlspecialchars(t('reports.dn_phone', 'ເບີໂທ:')); ?></span><span id="dn_rep_sender_tel" style="font-weight:700;">-</span></div>
  <div style="border-top:1px dashed #000;margin:6px 0;"></div>
  <div style="display:flex;justify-content:space-between;"><span style="font-weight:800;"><i class="fas fa-user mr-1"></i> <?php echo htmlspecialchars(t('reports.dn_recipient', 'ຜູ້ຮັບ')); ?>:</span><span id="dn_rep_customer" style="font-weight:700;">-</span></div>
  <div style="display:flex;justify-content:space-between;" id="dn_rep_phone_row"><span><?php echo htmlspecialchars(t('reports.dn_phone', 'ເບີໂທ:')); ?></span><span id="dn_rep_phone" style="font-weight:700;">-</span></div>
  <div id="dn_rep_address_row" style="margin-top:1px;"><?php echo htmlspecialchars(t('reports.dn_recipient_address', 'ທີ່ຢູ່:')); ?> <span id="dn_rep_address" style="font-weight:700;">-</span></div>
  <div style="border-top:1px dashed #000;margin:6px 0;"></div>
  <table style="width:100%;font-size:12.5px;">
    <thead><tr style="border-bottom:1px dashed #000;">
      <th style="text-align:left;padding-bottom:3px;font-weight:800;"><?php echo htmlspecialchars(t('reports.col_product_item', 'ລາຍການສິນຄ້າ')); ?></th>
      <th style="text-align:right;padding-bottom:3px;font-weight:800;width:70px;"><?php echo htmlspecialchars(t('reports.col_qty', 'ຈຳນວນ')); ?></th>
    </tr></thead>
    <tbody id="dn_rep_items"></tbody>
  </table>
</div>
