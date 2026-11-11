<!-- Modal: Active / Opened Bills List -->
<div class="modal fade" id="activeBillsModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
      <div class="modal-header bg-primary text-white py-3 px-4">
        <h5 class="modal-title font-weight-bold" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <i class="fas fa-list-alt mr-2"></i> <?php echo htmlspecialchars(t('pos.active_bills_modal_title', 'ລາຍການບິນທີ່ເປີດຢູ່')); ?>
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body p-4" style="max-height: 420px; overflow-y: auto;">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="font-weight-bold mb-0 text-dark"><?php echo htmlspecialchars(t('pos.active_bills_list_label', 'ລາຍການບິນທີ່ກຳລັງເປີດຢູ່:')); ?></h6>
          <button type="button" class="btn btn-sm btn-success font-weight-bold" onclick="createNewBillModal()" data-dismiss="modal">
            <i class="fas fa-plus-circle mr-1"></i> <?php echo htmlspecialchars(t('pos.new_bill', 'ເປີດບິນໃໝ່')); ?>
          </button>
        </div>
        <div id="activeBillsSearchWrap" class="mb-3" style="display:none;">
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text bg-white" style="border-radius:8px 0 0 8px;"><i class="fas fa-search text-muted"></i></span>
            </div>
            <input type="text" id="activeBillsSearchInput" class="form-control font-weight-bold" autocomplete="off"
                   placeholder="<?php echo htmlspecialchars(t('pos.active_bills_search_placeholder', 'ຄົ້ນຫາ: ຊື່ບິນ, ລູກຄ້າ ຫຼື ສິນຄ້າ...')); ?>"
                   oninput="filterActiveBillsList()" style="border-radius:0 8px 8px 0; height:40px;">
          </div>
        </div>
        <div id="activeBillsContainer">
          <!-- Dynamically populated active bills -->
        </div>
        <div id="activeBillsNoMatch" class="text-center py-4 text-muted" style="display:none;">
          <i class="fas fa-search mr-1"></i> <?php echo htmlspecialchars(t('pos.active_bills_no_match', 'ບໍ່ພົບບິນທີ່ກົງກັບການຄົ້ນຫາ')); ?>
        </div>
      </div>
      <div class="modal-footer border-0 bg-light p-3">
        <button type="button" class="btn btn-light font-weight-bold" data-dismiss="modal"><?php echo htmlspecialchars(t('pos.close', 'ປິດ')); ?></button>
      </div>
    </div>
  </div>
</div>
