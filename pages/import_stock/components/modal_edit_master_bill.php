<?php
// Component: Modal for editing Master Invoice details (Supplier Name, Import Date, Notes)
?>
<!-- MODAL: EDIT MASTER IMPORT BILL -->
<div class="modal fade" id="editMasterBillModal" tabindex="-1" role="dialog" aria-labelledby="editMasterBillModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
      <div class="modal-header bg-warning text-dark py-3">
        <h5 class="modal-title font-weight-bold" id="editMasterBillModalLabel" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <i class="fas fa-edit mr-2"></i> <?php echo htmlspecialchars(t('import_list.modal_master_title', 'ແກ້ໄຂຂໍ້ມູນໃບບິນຮັບເຂົ້າ:')); ?> <span id="edit_master_invoice_label" class="badge badge-light text-dark px-2 py-1"></span>
        </h5>
        <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form method="POST" action="">
        <input type="hidden" name="action" value="edit_master_import">
        <input type="hidden" name="import_id" id="edit_master_import_id">

        <div class="modal-body p-4">
          <!-- Supplier Name -->
          <div class="form-group">
            <label class="form-label font-weight-bold text-dark"><?php echo htmlspecialchars(t('import_list.modal_supplier_label', 'ຜູ້ສະໜອງສິນຄ້າ:')); ?></label>
            <input type="text" name="supplier_name" id="edit_master_supplier" class="form-control font-weight-bold" placeholder="<?php echo htmlspecialchars(t('import_list.modal_supplier_placeholder', 'ຊື່ຜູ້ສະໜອງ/ຮ້ານຄ້າ')); ?>" style="border-radius: 8px; height: 42px;">
          </div>

          <!-- Import Date -->
          <div class="form-group">
            <label class="form-label font-weight-bold text-dark"><?php echo htmlspecialchars(t('import_list.modal_import_date_label', 'ວັນທີຮັບເຂົ້າ:')); ?></label>
            <input type="date" name="import_date" id="edit_master_date" class="form-control font-weight-bold" required style="border-radius: 8px; height: 42px;">
          </div>

          <!-- Notes -->
          <div class="form-group mb-0">
            <label class="form-label font-weight-bold text-dark"><?php echo htmlspecialchars(t('import_list.modal_notes_label', 'ໝາຍເຫດ:')); ?></label>
            <textarea name="notes" id="edit_master_notes" class="form-control" rows="2" placeholder="<?php echo htmlspecialchars(t('import_list.modal_master_notes_placeholder', 'ໝາຍເຫດເພີ່ມເຕີມສຳລັບໃບບິນນີ້')); ?>" style="border-radius: 8px;"></textarea>
          </div>
        </div>

        <div class="modal-footer bg-light py-3 px-4 border-top">
          <button type="button" class="btn btn-secondary font-weight-bold px-4" data-dismiss="modal" style="border-radius: 6px;"><?php echo htmlspecialchars(t('import_list.modal_btn_cancel', 'ຍົກເລີກ')); ?></button>
          <button type="submit" class="btn btn-warning font-weight-bold px-4 shadow-sm" style="border-radius: 6px;">
            <i class="fas fa-save mr-1"></i> <?php echo htmlspecialchars(t('import_list.modal_btn_update', 'ອັບເດດ')); ?>
          </button>
        </div>
      </form>

    </div>
  </div>
</div>
