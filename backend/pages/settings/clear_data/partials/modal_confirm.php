<?php
/**
 * ສ່ວນ: ໜ້າຕ່າງຢືນຢັນການລຶບດ້ວຍລະຫັດຜ່ານ
 * ມາຈາກ pages/settings/clear_data/index.php. ລາຍການທີ່ເລືອກຖືກຕື່ມໂດຍ js.php (cdOpenConfirm).
 */
?>
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
  <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 520px;">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
      <div class="modal-header text-white px-4 py-3" style="background: linear-gradient(135deg, #dc2626, #b91c1c); border: none;">
        <h5 class="modal-title font-weight-bold" style="font-size: 1.1rem;">
          <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo htmlspecialchars(t('clear_data.modal_confirm_title')); ?>
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.9;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body p-4">
        <div class="alert border-0 mb-3 text-center" style="border-radius: 10px; background: #fef2f2; color: #991b1b; padding: 12px 16px;">
          <div class="font-weight-bold mb-1" style="font-size: 0.95rem;">
            <i class="fas fa-biohazard mr-1"></i> <?php echo htmlspecialchars(t('clear_data.modal_confirm_text')); ?>
          </div>
          <div class="small"><?php echo htmlspecialchars(t('clear_data.modal_irreversible')); ?></div>
        </div>

        <div class="mb-2 font-weight-bold text-dark" style="font-size: 0.9rem;">
          <i class="fas fa-store text-primary mr-1"></i> <?php echo htmlspecialchars(t('clear_data.modal_branch')); ?>
          <span id="confirmBranchName" class="text-danger"></span>
        </div>

        <div class="mb-3">
          <label class="font-weight-bold text-dark small mb-1">
            <i class="fas fa-list text-primary mr-1"></i> <?php echo htmlspecialchars(t('clear_data.modal_selected_list')); ?>
          </label>
          <div id="confirmScopesList" class="p-2 rounded border" style="background: #f8fafc; max-height: 180px; overflow-y: auto; font-size: 0.85rem;"></div>
        </div>

        <div class="form-group mb-2">
          <label class="font-weight-bold text-dark" for="adminPasswordInput" style="font-size: 0.88rem;">
            <i class="fas fa-key text-warning mr-1"></i> <?php echo htmlspecialchars(t('clear_data.admin_password_label')); ?> <span class="text-danger">*</span>
          </label>
          <div class="input-group">
            <input type="password" id="adminPasswordInput" class="form-control" placeholder="<?php echo htmlspecialchars(t('clear_data.admin_password_placeholder')); ?>" style="height: 42px;" autocomplete="current-password" onkeydown="if (event.key === 'Enter') cdSubmit();">
            <div class="input-group-append">
              <button class="btn btn-outline-secondary" type="button" onclick="cdTogglePassword()" style="border-color: #cbd5e1;">
                <i class="fas fa-eye" id="togglePasswordIcon"></i>
              </button>
            </div>
          </div>
          <small class="text-muted mt-1 d-block" style="font-size: 0.78rem;"><?php echo htmlspecialchars(t('clear_data.admin_password_hint')); ?></small>
        </div>
      </div>

      <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-between">
        <button type="button" class="btn btn-secondary btn-sm px-3 py-2 font-weight-bold" data-dismiss="modal" style="border-radius: 6px;">
          <?php echo htmlspecialchars(t('clear_data.btn_cancel')); ?>
        </button>
        <button type="button" class="btn btn-danger btn-sm px-4 py-2 font-weight-bold" id="btnConfirmDeleteFinal" onclick="cdSubmit()" style="border-radius: 6px; background: #dc2626; border: none;">
          <i class="fas fa-trash-alt mr-2"></i> <?php echo htmlspecialchars(t('clear_data.btn_confirm_delete')); ?>
        </button>
      </div>
    </div>
  </div>
</div>
