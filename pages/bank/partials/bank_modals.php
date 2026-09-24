<?php
// pages/bank/partials/bank_modals.php
if (!defined('MINIPOS_APP')) {
    define('MINIPOS_APP', true);
}
?>

<!-- ADD / EDIT BANK ACCOUNT MODAL -->
<div class="modal fade" id="addBankAccountModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content" style="border-radius: 14px; overflow: hidden;">
      <form id="bankAccountForm" method="POST" enctype="multipart/form-data" onsubmit="return validateBankForm(event)">
        <input type="hidden" name="action" id="form_bank_action" value="add_bank_account">
        <input type="hidden" name="bank_id" id="form_bank_id" value="0">
        
        <div class="modal-header text-white py-3 px-4" style="background-color: #244886;">
          <h5 class="modal-title font-weight-bold" id="bankModalTitle">
            <i class="fas fa-university mr-2"></i> <?php echo htmlspecialchars(t('bank.modal_title_add', 'ເພີ່ມບັນຊີທະນາຄານໃໝ່')); ?>
          </h5>
          <button type="button" class="close text-white opacity-100" data-dismiss="modal" aria-label="Close" onclick="resetBankForm()">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body p-4" style="font-size: 0.9rem;">
          
          <div class="form-group">
            <label class="font-weight-bold"><?php echo htmlspecialchars(t('bank.field_bank_name', 'ຊື່ທະນາຄານ:')); ?> <span class="text-danger">*</span></label>
            <input type="text" name="bank_name" id="form_bank_name" class="form-control font-weight-bold text-dark" placeholder="BCEL, LDB, JDB..." required autocomplete="off" style="border-radius: 8px;">
          </div>

          <div class="form-group">
            <label class="font-weight-bold"><?php echo htmlspecialchars(t('bank.field_account_number', 'ເລກບັນຊີ:')); ?> <span class="text-danger">*</span></label>
            <input type="text" name="account_number" id="form_account_number" class="form-control font-weight-bold text-primary" placeholder="<?php echo htmlspecialchars(t('bank.placeholder_account_number', 'ກະລຸນາປ້ອນເລກບັນຊີທະນາຄານ...')); ?>" required autocomplete="off" style="border-radius: 8px;">
          </div>

          <div class="form-group">
            <label class="font-weight-bold"><?php echo htmlspecialchars(t('bank.field_account_name', 'ຊື່ບັນຊີ:')); ?> <span class="text-danger">*</span></label>
            <input type="text" name="account_name" id="form_account_name" class="form-control" placeholder="<?php echo htmlspecialchars(t('bank.placeholder_account_name', 'ກະລຸນາປ້ອນຊື່ບັນຊີທະນາຄານ...')); ?>" required autocomplete="off" style="border-radius: 8px;">
          </div>

          <div class="row">
            <div class="col-6">
              <div class="form-group mb-0">
                <label class="font-weight-bold" style="font-size: 0.82rem;"><?php echo htmlspecialchars(t('bank.field_bank_logo', 'ຮູບໂລໂກ້ທະນາຄານ:')); ?></label>
                <input type="file" name="bank_logo" class="form-control-file" accept="image/*">
              </div>
            </div>
            <div class="col-6">
              <div class="form-group mb-0">
                <label class="font-weight-bold" style="font-size: 0.82rem;"><?php echo htmlspecialchars(t('bank.field_qr_code', 'ຮູບ QR Code:')); ?></label>
                <input type="file" name="qr_code_img" class="form-control-file" accept="image/*">
              </div>
            </div>
          </div>

          <div class="row mt-3">
            <?php if (!$bankMultiBranch): ?>
              <!-- Single-branch shop: no branch to choose, the account belongs to this branch -->
              <input type="hidden" name="store_id" id="form_store_id" value="<?php echo (int)$userStoreId; ?>">
            <?php else: ?>
            <div class="col-6">
              <div class="form-group mb-0">
                <label class="font-weight-bold" style="font-size: 0.82rem;"><?php echo htmlspecialchars(t('bank.field_branch', 'ສາຂາ:')); ?></label>
                <select name="store_id" id="form_store_id" class="form-control" style="border-radius: 8px;" <?php echo (!$canAllBranches) ? 'disabled' : ''; ?>>
                  <?php if ($canAllBranches): ?>
                    <option value="0"><?php echo htmlspecialchars(t('bank.opt_global', '-- ທຸກສາຂາ (Global) --')); ?></option>
                  <?php endif; ?>
                  <?php foreach ($branches_list as $st): ?>
                    <option value="<?php echo $st['store_id']; ?>" <?php echo ($userStoreId == $st['store_id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($st['store_name']); ?> <?php echo !empty($st['is_main']) ? htmlspecialchars(t('bank.main_branch_suffix', '(ສາຂາຫຼັກ)')) : ''; ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <?php if (!$canAllBranches): ?>
                  <input type="hidden" name="store_id" value="<?php echo $userStoreId; ?>">
                <?php endif; ?>
              </div>
            </div>
            <?php endif; ?>
            <div class="<?php echo $bankMultiBranch ? 'col-6' : 'col-12'; ?>">
              <div class="form-group mb-0">
                <label class="font-weight-bold" style="font-size: 0.82rem;"><?php echo htmlspecialchars(t('bank.field_status', 'ສະຖານະ:')); ?></label>
                <select name="is_active" id="form_is_active" class="form-control" style="border-radius: 8px;">
                  <option value="1"><?php echo htmlspecialchars(t('bank.status_option_active', 'ເປີດໃຊ້ງານ')); ?></option>
                  <option value="0"><?php echo htmlspecialchars(t('bank.status_option_inactive', 'ປິດໃຊ້ງານ')); ?></option>
                </select>
              </div>
            </div>
          </div>

        </div>

        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal" onclick="resetBankForm()"><?php echo htmlspecialchars(t('bank.cancel', 'ຍົກເລີກ')); ?></button>
          <button type="submit" class="btn btn-primary font-weight-bold px-4" style="background: linear-gradient(135deg, #2c5aa0, #244886); border: none;"><i class="fas fa-save mr-1"></i> <?php echo htmlspecialchars(t('bank.btn_save', 'ບັນທຶກ')); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
