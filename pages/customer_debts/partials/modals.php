<?php
/*
 * ສ່ວນ: Popup ທັງໝົດ
 * popup ລາຍລະອຽດໜີ້ຂອງລູກຄ້າ (ສີຫົວຕາມທີມ), popup ຮັບຊຳລະ (ເງິນສົດ/ທະນາຄານ) ແລະ popup
 * ເພີ່ມໜີ້ຍົກມາ (ຄົ້ນຫາລູກຄ້າ).
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/customer_debts/customer_debts.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>  <div class="modal fade" id="debtDetailModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <!-- bg-primary: follows the colour chosen in the dashboard theme picker -->
        <div class="modal-header bg-primary">
          <h5 class="modal-title font-weight-bold"><i class="fas fa-user mr-1"></i> <span id="ddCustomerName"></span></h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <!-- Totals for this customer + one button to take money against all their bills -->
          <div class="cdb-dd-sum">
            <div><small><?php echo htmlspecialchars(t('customer_debt.col_total', 'ຍອດໜີ້')); ?></small><b id="ddSumTotal">0 ₭</b></div>
            <div><small><?php echo htmlspecialchars(t('customer_debt.col_paid', 'ຊຳລະແລ້ວ')); ?></small><b class="text-success" id="ddSumPaid">0 ₭</b></div>
            <div style="background: #fef2f2; border-color: #fecaca;"><small><?php echo htmlspecialchars(t('customer_debt.col_balance', 'ຄົງເຫຼືອ')); ?></small><b class="text-danger" id="ddSumBalance">0 ₭</b></div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap" style="gap: 8px;">
            <h6 class="font-weight-bold mb-0"><?php echo htmlspecialchars(t('customer_debt.detail_bills', 'ລາຍການໜີ້')); ?></h6>
            <button type="button" class="btn btn-success btn-sm font-weight-bold px-3" id="ddPayAllBtn" style="display: none; border-radius: 8px;" onclick="openPayDebtAll()">
              <i class="fas fa-money-bill-wave mr-1"></i> <?php echo htmlspecialchars(t('customer_debt.btn_receive_all', 'ຮັບເງິນ (ຫັກບິນເກົ່າກ່ອນ)')); ?>
            </button>
          </div>
          <div class="table-responsive mb-3">
            <table class="table cdb-table">
              <thead><tr>
                <th><?php echo htmlspecialchars(t('customer_debt.col_date', 'ວັນທີ')); ?></th>
                <th><?php echo htmlspecialchars(t('customer_debt.col_invoice', 'ເລກບິນ')); ?></th>
                <th class="text-right"><?php echo htmlspecialchars(t('customer_debt.col_bill_amount', 'ຍອດບິນ')); ?></th>
                <th class="text-right"><?php echo htmlspecialchars(t('customer_debt.col_deposit', 'ມັດຈຳ')); ?></th>
                <th class="text-right"><?php echo htmlspecialchars(t('customer_debt.col_total', 'ຍອດໜີ້')); ?></th>
                <th class="text-right"><?php echo htmlspecialchars(t('customer_debt.col_paid', 'ຊຳລະແລ້ວ')); ?></th>
                <th class="text-right"><?php echo htmlspecialchars(t('customer_debt.col_balance', 'ຄົງເຫຼືອ')); ?></th>
                <th class="text-center"><?php echo htmlspecialchars(t('customer_debt.col_due', 'ກຳນົດຊຳລະ')); ?></th>
                <th class="text-center"><?php echo htmlspecialchars(t('customer_debt.col_status', 'ສະຖານະ')); ?></th>
                <th></th>
              </tr></thead>
              <tbody id="ddBills"></tbody>
            </table>
          </div>
          <div class="cdb-modal-pager mb-3" id="ddBillsPager"></div>
          <h6 class="font-weight-bold"><?php echo htmlspecialchars(t('customer_debt.detail_payments', 'ປະຫວັດການຊຳລະ')); ?></h6>
          <div class="table-responsive">
            <table class="table cdb-table">
              <thead><tr>
                <th><?php echo htmlspecialchars(t('customer_debt.col_date', 'ວັນທີ')); ?></th>
                <th><?php echo htmlspecialchars(t('customer_debt.col_invoice', 'ເລກບິນ')); ?></th>
                <th class="text-right"><?php echo htmlspecialchars(t('customer_debt.col_amount', 'ຈຳນວນເງິນ')); ?></th>
                <th><?php echo htmlspecialchars(t('customer_debt.col_method', 'ຊ່ອງທາງ')); ?></th>
                <th><?php echo htmlspecialchars(t('customer_debt.col_note', 'ໝາຍເຫດ')); ?></th>
                <th><?php echo htmlspecialchars(t('customer_debt.col_received_by', 'ຜູ້ຮັບ')); ?></th>
              </tr></thead>
              <tbody id="ddPayments"></tbody>
            </table>
          </div>
          <div class="cdb-modal-pager" id="ddPaymentsPager"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Pay modal -->
  <div class="modal fade" id="payDebtModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header" style="background: #16a34a;">
          <h5 class="modal-title font-weight-bold"><i class="fas fa-money-bill-wave mr-1"></i> <?php echo htmlspecialchars(t('customer_debt.pay_title', 'ຮັບຊຳລະໜີ້')); ?></h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="payCustomerId"><input type="hidden" id="payDebtId">
          <div class="mb-2"><span class="text-muted"><?php echo htmlspecialchars(t('customer_debt.col_customer', 'ລູກຄ້າ')); ?>:</span> <b id="payCustomerName"></b> <span id="payInvoiceLbl" class="text-muted"></span></div>
          <div class="p-2 mb-3 rounded" style="background: #fef2f2;">
            <span class="text-muted"><?php echo htmlspecialchars(t('customer_debt.pay_outstanding', 'ຍອດຄ້າງຊຳລະ')); ?>:</span>
            <b class="text-danger" id="payOutstanding" style="font-size: 1.15rem;"></b>
          </div>
          <div class="form-group">
            <label class="font-weight-bold"><?php echo htmlspecialchars(t('customer_debt.col_amount', 'ຈຳນວນເງິນ')); ?> <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="text" id="payAmount" class="form-control text-right font-weight-bold" style="font-size: 1.15rem;" inputmode="numeric" placeholder="<?php echo htmlspecialchars(t('customer_debt.amount_placeholder', 'ປ້ອນຈຳນວນເງິນ ເຊັ່ນ: 100,000')); ?>" oninput="cdbFormatMoney(this); updatePayAfter();">
              <div class="input-group-append"><button type="button" class="btn btn-outline-success" onclick="setPayAmount(window.__payOutstanding)"><?php echo htmlspecialchars(t('customer_debt.pay_full', 'ຊຳລະທັງໝົດ')); ?></button></div>
            </div>
            <!-- Quick amounts (filled by openPayDebt: ½ of the debt + round notes below it) -->
            <div class="cdb-quick" id="payQuick"></div>
            <!-- What is still owed after this payment (or why it can't be taken) -->
            <div class="cdb-after" id="payAfter"></div>
            <small class="text-muted d-block mt-1" id="payFifoHint"><i class="fas fa-info-circle mr-1"></i><?php echo htmlspecialchars(t('customer_debt.pay_fifo_hint', 'ເງິນຈະຖືກຫັກຈາກບິນເກົ່າສຸດກ່ອນ')); ?></small>
          </div>
          <div class="form-group">
            <label class="font-weight-bold"><?php echo htmlspecialchars(t('customer_debt.col_method', 'ຊ່ອງທາງ')); ?> <span class="text-danger">*</span></label>
            <input type="hidden" id="payMethod" value="cash">
            <input type="hidden" id="payBank" value="">
            <!-- One click picks everything: ເງິນສົດ, or the bank the transfer went into -->
            <div class="cdb-paybtns">
              <button type="button" class="cdb-paybtn cdb-paybtn-cash active" data-pay="cash" onclick="pickPay('cash')">
                <i class="fas fa-money-bill-wave"></i><span><?php echo htmlspecialchars(t('customer_debt.method_cash', 'ເງິນສົດ')); ?></span>
              </button>
              <?php foreach ($debtBanks as $b): ?>
                <button type="button" class="cdb-paybtn" data-pay="bank" data-bank="<?php echo $b['id']; ?>" onclick="pickPay('bank', <?php echo $b['id']; ?>)"
                        title="<?php echo htmlspecialchars(trim($b['bank_name'] . ' · ' . $b['account_number'] . ($b['account_name'] !== '' ? ' · ' . $b['account_name'] : ''), ' ·')); ?>">
                  <?php if (!empty($b['logo'])): ?>
                    <img src="<?php echo htmlspecialchars($b['logo']); ?>" alt="" onerror="this.replaceWith(Object.assign(document.createElement('i'), {className: 'fas fa-university'}))">
                  <?php else: ?>
                    <i class="fas fa-university"></i>
                  <?php endif; ?>
                  <span><?php echo htmlspecialchars($b['bank_name']); ?></span>
                </button>
              <?php endforeach; ?>
              <?php if (empty($debtBanks)): ?>
                <button type="button" class="cdb-paybtn" data-pay="transfer" onclick="pickPay('transfer')">
                  <i class="fas fa-university"></i><span><?php echo htmlspecialchars(t('customer_debt.method_transfer', 'ໂອນເງິນ / QR')); ?></span>
                </button>
              <?php endif; ?>
            </div>
          </div>
          <div class="form-group mb-0">
            <label class="font-weight-bold"><?php echo htmlspecialchars(t('customer_debt.col_note', 'ໝາຍເຫດ')); ?></label>
            <input type="text" id="payNote" class="form-control" maxlength="255" placeholder="<?php echo htmlspecialchars(t('customer_debt.pay_note_placeholder', 'ເຊັ່ນ: ໂອນຈາກບັນຊີລູກຄ້າ, ເລກອ້າງອີງການໂອນ...')); ?>">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light border" data-dismiss="modal"><?php echo htmlspecialchars(t('customer_debt.btn_cancel', 'ຍົກເລີກ')); ?></button>
          <button type="button" class="btn btn-success font-weight-bold" id="btnSubmitPay" onclick="submitPayDebt()"><i class="fas fa-check mr-1"></i> <?php echo htmlspecialchars(t('customer_debt.btn_confirm_pay', 'ຢືນຢັນຮັບເງິນ')); ?></button>
        </div>
      </div>
    </div>
  </div>

  <?php if ($canAddDebt): ?>
  <!-- Add opening balance modal -->
  <div class="modal fade" id="addDebtModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header" style="background: #dc2626;">
          <h5 class="modal-title font-weight-bold"><i class="fas fa-plus mr-1"></i> <?php echo htmlspecialchars(t('customer_debt.btn_add', 'ເພີ່ມໜີ້ຍົກມາ')); ?></h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label class="font-weight-bold"><?php echo htmlspecialchars(t('customer_debt.col_customer', 'ລູກຄ້າ')); ?> <span class="text-danger">*</span></label>
            <input type="hidden" id="addCustomer" value="">
            <!-- Picked customer -->
            <div id="addCustomerChosen" class="cdb-chosen" style="display: none;">
              <div class="min-w-0">
                <div class="font-weight-bold text-truncate" id="addCustomerChosenName"></div>
                <div class="cdb-meta text-truncate" id="addCustomerChosenMeta"></div>
              </div>
              <button type="button" class="btn btn-sm btn-light border flex-shrink-0" onclick="clearPickedCustomer()">
                <i class="fas fa-exchange-alt mr-1"></i><?php echo htmlspecialchars(t('customer_debt.change_customer', 'ປ່ຽນ')); ?>
              </button>
            </div>
            <!-- Live search -->
            <div id="addCustomerSearchWrap">
              <div class="input-group">
                <div class="input-group-prepend"><span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span></div>
                <input type="text" id="addCustomerSearch" class="form-control" autocomplete="off"
                       placeholder="<?php echo htmlspecialchars(t('customer_debt.customer_search_placeholder', 'ພິມຊື່, ເບີໂທ ຫຼື ລະຫັດລູກຄ້າ...')); ?>">
              </div>
              <div id="addCustomerResults" class="cdb-results"></div>
            </div>
          </div>
          <div class="form-group">
            <label class="font-weight-bold"><?php echo htmlspecialchars(t('customer_debt.col_amount', 'ຈຳນວນເງິນ')); ?> <span class="text-danger">*</span></label>
            <input type="text" id="addAmount" class="form-control text-right font-weight-bold" inputmode="numeric" placeholder="<?php echo htmlspecialchars(t('customer_debt.amount_placeholder', 'ປ້ອນຈຳນວນເງິນ ເຊັ່ນ: 100,000')); ?>" oninput="cdbFormatMoney(this)">
          </div>
          <div class="form-group">
            <label class="font-weight-bold"><?php echo htmlspecialchars(t('customer_debt.col_due', 'ກຳນົດຊຳລະ')); ?></label>
            <input type="date" id="addDueDate" class="form-control">
          </div>
          <div class="form-group mb-0">
            <label class="font-weight-bold"><?php echo htmlspecialchars(t('customer_debt.col_note', 'ໝາຍເຫດ')); ?></label>
            <input type="text" id="addNote" class="form-control" maxlength="255" placeholder="<?php echo htmlspecialchars(t('customer_debt.add_note_placeholder', 'ເຊັ່ນ: ໜີ້ຍົກມາຈາກປຶ້ມບັນຊີເກົ່າ')); ?>">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light border" data-dismiss="modal"><?php echo htmlspecialchars(t('customer_debt.btn_cancel', 'ຍົກເລີກ')); ?></button>
          <button type="button" class="btn btn-danger font-weight-bold" onclick="submitAddDebt()"><i class="fas fa-save mr-1"></i> <?php echo htmlspecialchars(t('customer_debt.btn_save', 'ບັນທຶກ')); ?></button>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

