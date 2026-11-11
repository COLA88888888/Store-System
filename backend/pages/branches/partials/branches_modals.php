<?php
// pages/branches/partials/branches_modals.php
if (!defined('MINIPOS_APP')) {
    define('MINIPOS_APP', true);
}
?>
<!-- ADD BRANCH MODAL -->
<div class="modal fade" id="addBranchModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
      <form method="POST">
        <input type="hidden" name="action" value="add_branch">
        <div class="modal-header text-white" style="background-color: #244886;">
          <h5 class="modal-title font-weight-bold"><i class="fas fa-plus-circle mr-2"></i> <?php echo htmlspecialchars(t('branches.modal_add_title', 'ເພີ່ມສາຂາໃໝ່')); ?></h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body p-4">
          <div class="form-group">
            <label class="font-weight-bold"><?php echo htmlspecialchars(t('branches.label_store_code', 'ລະຫັດສາຂາ:')); ?><span class="text-danger">*</span></label>
            <input type="text" name="store_code" class="form-control font-weight-bold text-primary"
                   value="<?php echo htmlspecialchars($next_store_code ?? '1'); ?>" readonly
                   style="background-color: #f1f5f9; cursor: not-allowed;">
          </div>
          <div class="form-group">
            <label class="font-weight-bold"><?php echo htmlspecialchars(t('branches.label_store_name', 'ຊື່ສາຂາ:')); ?><span class="text-danger">*</span></label>
            <?php /* $addBranchPrefill (api/branches_backend.php): ສາຂາທຳອິດ = ຂໍ້ມູນຮ້ານ, ສາຂາທີ 2 ຂຶ້ນໄປ = ວ່າງ */ ?>
            <input type="text" name="store_name" class="form-control" value="<?php echo htmlspecialchars($addBranchPrefill['name'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars(t('branches.placeholder_store_name', 'ເຊັ່ນ: ສາຂາ ດົງໂດກ, ສາຂາ ຫຼວງພະບາງ')); ?>" required>
          </div>
          <div class="form-group">
            <label class="font-weight-bold"><?php echo htmlspecialchars(t('branches.label_tel', 'ເບີໂທຕິດຕໍ່ສາຂາ:')); ?></label>
            <input type="text" name="tel" class="form-control" value="<?php echo htmlspecialchars($addBranchPrefill['tel'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars(t('branches.placeholder_tel', '020 xxxxxxxx')); ?>">
          </div>
          <div class="form-group">
            <label class="font-weight-bold"><?php echo htmlspecialchars(t('branches.label_address', 'ທີ່ຢູ່ສາຂາ:')); ?></label>
            <textarea name="address" class="form-control" rows="2" placeholder="<?php echo htmlspecialchars(t('branches.placeholder_address', 'ບ້ານ, ເມືອງ, ແຂວງ...')); ?>"><?php echo htmlspecialchars($addBranchPrefill['address'] ?? ''); ?></textarea>
          </div>
          <div class="form-group form-check">
            <input type="checkbox" name="is_main" value="1" class="form-check-input" id="add_is_main"<?php echo !empty($addBranchPrefill['is_main']) ? ' checked' : ''; ?>>
            <label class="form-check-input-label font-weight-bold text-dark" for="add_is_main"><?php echo htmlspecialchars(t('branches.label_is_main', 'ຕັ້ງເປັນສາຂາໃຫຍ່')); ?></label>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo htmlspecialchars(t('branches.btn_cancel', 'ຍົກເລີກ')); ?></button>
          <button type="submit" class="btn btn-primary font-weight-bold" style="background: linear-gradient(135deg, #2c5aa0, #244886); border: none;"><i class="fas fa-save mr-1"></i> <?php echo htmlspecialchars(t('branches.btn_save', 'ບັນທຶກ')); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- EDIT BRANCH MODAL -->
<div class="modal fade" id="editBranchModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
      <form method="POST">
        <input type="hidden" name="action" value="edit_branch">
        <input type="hidden" name="store_id" id="edit_store_id">
        <div class="modal-header text-white" style="background-color: #244886;">
          <h5 class="modal-title font-weight-bold"><i class="fas fa-edit mr-2"></i> <?php echo htmlspecialchars(t('branches.modal_edit_title', 'ແກ້ໄຂຂໍ້ມູນສາຂາ')); ?></h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body p-4">
          <div class="form-group">
            <label class="font-weight-bold"><?php echo htmlspecialchars(t('branches.label_store_code', 'ລະຫັດສາຂາ:')); ?><span class="text-danger">*</span></label>
            <input type="text" name="store_code" id="edit_store_code" class="form-control font-weight-bold text-secondary"
                   readonly style="background-color: #f1f5f9; cursor: not-allowed;">
          </div>
          <div class="form-group">
            <label class="font-weight-bold"><?php echo htmlspecialchars(t('branches.label_store_name', 'ຊື່ສາຂາ:')); ?><span class="text-danger">*</span></label>
            <input type="text" name="store_name" id="edit_store_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="font-weight-bold"><?php echo htmlspecialchars(t('branches.label_tel', 'ເບີໂທຕິດຕໍ່ສາຂາ:')); ?></label>
            <input type="text" name="tel" id="edit_store_tel" class="form-control">
          </div>
          <div class="form-group">
            <label class="font-weight-bold"><?php echo htmlspecialchars(t('branches.label_address', 'ທີ່ຢູ່ສາຂາ:')); ?></label>
            <textarea name="address" id="edit_store_address" class="form-control" rows="2"></textarea>
          </div>
          <div class="form-group form-check">
            <input type="checkbox" name="is_main" value="1" class="form-check-input" id="edit_is_main">
            <label class="form-check-input-label font-weight-bold text-dark" for="edit_is_main"><?php echo htmlspecialchars(t('branches.label_is_main', 'ຕັ້ງເປັນສາຂາໃຫຍ່')); ?></label>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo htmlspecialchars(t('branches.btn_cancel', 'ຍົກເລີກ')); ?></button>
          <button type="submit" class="btn btn-warning font-weight-bold"><i class="fas fa-save mr-1"></i> <?php echo htmlspecialchars(t('branches.btn_update', 'ອັບເດດ')); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
