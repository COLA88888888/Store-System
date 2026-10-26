<?php
// Component: Modal for editing individual imported product item detail
?>
<!-- MODAL: EDIT IMPORT DETAIL -->
<div class="modal fade" id="editImportModal" tabindex="-1" role="dialog" aria-labelledby="editImportModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
      <div class="modal-header text-white py-3" style="background: linear-gradient(135deg, #2c5aa0, #244886) !important;">
        <h5 class="modal-title font-weight-bold" id="editImportModalLabel" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <i class="fas fa-edit mr-2"></i> <?php echo htmlspecialchars(t('import_list.modal_edit_detail_title', 'ແກ້ໄຂຂໍ້ມູນການຮັບສິນຄ້າເຂົ້າ')); ?>
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form method="POST" action="">
        <input type="hidden" name="action" value="edit_import_detail">
        <input type="hidden" name="import_detail_id" id="edit_import_detail_id">

        <div class="modal-body p-4">
          <div class="alert alert-info py-2 px-3 mb-3" style="border-radius: 8px;">
            <small class="font-weight-bold d-block text-primary">
              <i class="fas fa-receipt mr-1"></i> <?php echo htmlspecialchars(t('import_list.modal_receipt_label', 'ໃບບິນ:')); ?> <span id="edit_invoice_label" class="text-dark"></span>
            </small>
            <small class="font-weight-bold d-block text-dark mt-1" style="font-size: 0.95rem;" id="edit_product_name_label"></small>
          </div>

          <!-- Supplier Name -->
          <div class="form-group">
            <label class="form-label font-weight-bold text-dark"><?php echo htmlspecialchars(t('import_list.modal_supplier_label', 'ຜູ້ສະໜອງສິນຄ້າ:')); ?></label>
            <input type="text" name="supplier_name" id="edit_supplier_name" class="form-control" placeholder="<?php echo htmlspecialchars(t('import_list.modal_supplier_placeholder', 'ຊື່ຜູ້ສະໜອງ/ຮ້ານຄ້າ')); ?>" style="border-radius: 8px;">
          </div>

          <div class="row">
            <!-- Unit Selector -->
            <div class="col-md-6 form-group">
              <label class="form-label font-weight-bold text-dark"><?php echo htmlspecialchars(t('import_list.modal_unit_label', 'ຫົວໜ່ວຍຮັບເຂົ້າ:')); ?></label>
              <select name="unit_key" id="edit_unit_key" class="form-control font-weight-bold" style="border-radius: 8px;">
              </select>
            </div>

            <!-- Quantity -->
            <div class="col-md-6 form-group">
              <label class="form-label font-weight-bold text-dark"><?php echo htmlspecialchars(t('import_list.modal_qty_label', 'ຈຳນວນຮັບເຂົ້າ:')); ?></label>
              <input type="number" name="quantity" id="edit_quantity" class="form-control font-weight-bold" min="1" required style="border-radius: 8px;">
            </div>
          </div>

          <div class="row">
            <!-- Cost Price -->
            <div class="col-md-6 form-group">
              <label class="form-label font-weight-bold text-dark"><?php echo htmlspecialchars(t('import_list.modal_cost_label', 'ລາຄາຊື້ (ຕໍ່ຫົວໜ່ວຍ):')); ?></label>
              <div class="input-group">
                <input type="number" name="cost_price" id="edit_cost_price" class="form-control font-weight-bold" step="0.01" min="0" required style="border-radius: 8px 0 0 8px;">
                <div class="input-group-append">
                  <span class="input-group-text font-weight-bold bg-light" style="border-radius: 0 8px 8px 0;">₭</span>
                </div>
              </div>
            </div>

            <!-- Expiry Date -->
            <div class="col-md-6 form-group">
              <label class="form-label font-weight-bold text-dark"><?php echo htmlspecialchars(t('import_list.modal_expiry_label', 'ວັນໝົດອາຍຸ (ຖ້າມີ):')); ?></label>
              <input type="date" name="expiry_date" id="edit_expiry_date" class="form-control" style="border-radius: 8px;">
            </div>
          </div>

          <!-- Notes -->
          <div class="form-group mb-0">
            <label class="form-label font-weight-bold text-dark"><?php echo htmlspecialchars(t('import_list.modal_notes_label', 'ໝາຍເຫດ:')); ?></label>
            <textarea name="notes" id="edit_notes" class="form-control" rows="2" placeholder="<?php echo htmlspecialchars(t('import_list.modal_notes_placeholder', 'ໝາຍເຫດເພີ່ມເຕີມ')); ?>" style="border-radius: 8px;"></textarea>
          </div>
        </div>

        <div class="modal-footer bg-light py-3 px-4 border-top">
          <button type="button" class="btn btn-secondary font-weight-bold px-4" data-dismiss="modal" style="border-radius: 6px;"><?php echo htmlspecialchars(t('import_list.modal_btn_cancel', 'ຍົກເລີກ')); ?></button>
          <button type="submit" class="btn btn-primary font-weight-bold px-4 shadow-sm" style="border-radius: 6px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
            <i class="fas fa-save mr-1"></i> <?php echo htmlspecialchars(t('import_list.modal_btn_save_edit', 'ບັນທຶກການແກ້ໄຂ')); ?>
          </button>
        </div>
      </form>

    </div>
  </div>
</div>
