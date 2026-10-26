<?php
// Component: Modal for inspecting itemized products inside a master bill
?>
<!-- MODAL: VIEW BILL ITEMS -->
<div class="modal fade" id="billDetailsModal" tabindex="-1" role="dialog" aria-labelledby="billDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
      <div class="modal-header text-white py-3" style="background: linear-gradient(135deg, #2c5aa0, #244886) !important;">
        <h5 class="modal-title font-weight-bold" id="billDetailsModalLabel" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <i class="fas fa-file-invoice mr-2"></i> <?php echo htmlspecialchars(t('import_list.modal_details_title', 'ລາຍລະອຽດສິນຄ້າໃນໃບບິນ:')); ?> <span id="modal_invoice_no" class="badge badge-light text-primary px-2 py-1 ml-1" style="font-size: 1.05rem;"></span>
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body p-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between bg-light p-3 rounded mb-3 border">
          <div>
            <span class="text-muted font-weight-bold d-block" style="font-size: 0.85rem;"><?php echo htmlspecialchars(t('import_list.modal_supplier_short', 'ຜູ້ສະໜອງ:')); ?></span>
            <span id="modal_supplier" class="font-weight-bold text-dark" style="font-size: 0.98rem;">-</span>
          </div>
          <div>
            <span class="text-muted font-weight-bold d-block" style="font-size: 0.85rem;"><?php echo htmlspecialchars(t('import_list.modal_import_date_label', 'ວັນທີຮັບເຂົ້າ:')); ?></span>
            <span id="modal_import_date" class="font-weight-bold text-primary" style="font-size: 0.98rem;">-</span>
          </div>
          <div>
            <span class="text-muted font-weight-bold d-block" style="font-size: 0.85rem;"><?php echo htmlspecialchars(t('import_list.modal_total_cost_label', 'ມູນຄ່າທຶນລວມ:')); ?></span>
            <span id="modal_total_cost" class="font-weight-bold text-success" style="font-size: 1.1rem;">0 ₭</span>
          </div>
        </div>

        <div class="table-responsive border rounded" style="max-height: 350px; overflow-y: auto;">
          <table class="table table-hover mb-0 align-middle">
            <thead class="bg-light text-dark font-weight-bold" style="position: sticky; top: 0; z-index: 5;">
              <tr>
                <th class="text-center" style="width: 50px;"><?php echo htmlspecialchars(t('import_list.modal_col_no', 'ລຳດັບ')); ?></th>
                <th><?php echo htmlspecialchars(t('import_list.modal_col_product', 'ຊື່ສິນຄ້າ')); ?></th>
                <th class="text-center"><?php echo htmlspecialchars(t('import_list.modal_col_qty', 'ຈຳນວນຮັບເຂົ້າ')); ?></th>
                <th class="text-right"><?php echo htmlspecialchars(t('import_list.modal_col_buy_price', 'ລາຄາຊື້')); ?></th>
                <th class="text-right"><?php echo htmlspecialchars(t('import_list.modal_col_total', 'ລວມ')); ?></th>
                <th class="text-center" style="width: 100px;"><?php echo htmlspecialchars(t('import_list.modal_col_actions', 'ຈັດການ')); ?></th>
              </tr>
            </thead>
            <tbody id="bill_items_modal_tbody">
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer bg-light border-top" style="display: flex !important; justify-content: flex-end !important; padding: 12px 20px;">
        <button type="button" class="btn btn-primary font-weight-bold px-4 mr-2" style="border-radius: 6px;" onclick="printCurrentModalBill()">
          <i class="fas fa-print mr-1"></i> <?php echo htmlspecialchars(t('import_list.modal_btn_print_bill', 'ພິມໃບບິນນີ້')); ?>
        </button>
        <button type="button" class="btn btn-secondary font-weight-bold px-4" data-dismiss="modal" style="border-radius: 6px;"><?php echo htmlspecialchars(t('import_list.modal_btn_close', 'ປິດ')); ?></button>
      </div>
    </div>
  </div>
</div>
