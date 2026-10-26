<!-- Modal: Select Customer -->
<div class="modal fade" id="selectCustomerModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
      <div class="modal-header bg-primary text-white py-3 px-4">
        <h5 class="modal-title font-weight-bold" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <i class="fas fa-user-friends mr-2"></i> <?php echo htmlspecialchars(t('pos.select_customer_title', 'ເລືອກລູກຄ້າ / ສະມາຊິກ')); ?>
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body p-4">
        <div class="row mb-3 align-items-center">
          <div class="col-md-8 mb-2">
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span></div>
              <input type="text" id="customerSearchInput" class="form-control" placeholder="<?php echo htmlspecialchars(t('pos.customer_search_placeholder', 'ຄົ້ນຫາຊື່, ເບີໂທ ຫຼື ລະຫັດລູກຄ້າ...')); ?>" oninput="filterCustomersList()">
            </div>
          </div>
          <div class="col-md-4 mb-2 text-right">
            <button class="btn btn-success font-weight-bold w-100" onclick="openAddCustomerModal()">
              <i class="fas fa-plus-circle mr-1"></i> <?php echo htmlspecialchars(t('pos.add_new_customer', 'ເພີ່ມລູກຄ້າໃໝ່')); ?>
            </button>
          </div>
        </div>

        <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
          <table class="table table-hover align-middle border-top mb-0" id="selectCustomerTable" style="width: 100%; white-space: nowrap;">
            <thead class="bg-light text-secondary" style="font-size: 0.88rem; text-transform: uppercase;">
              <tr style="white-space: nowrap;">
                <th class="py-3 text-center" style="width: 60px; white-space: nowrap;"><?php echo htmlspecialchars(t('pos.col_no', 'ລຳດັບ')); ?></th>
                <th class="py-3 text-center" style="width: 140px; white-space: nowrap;"><?php echo htmlspecialchars(t('pos.col_code', 'ລະຫັດ')); ?></th>
                <th class="py-3" style="white-space: nowrap;"><?php echo htmlspecialchars(t('pos.col_name', 'ຊື່')); ?></th>
                <th class="py-3 text-center" style="width: 140px; white-space: nowrap;"><?php echo htmlspecialchars(t('pos.col_phone', 'ເບີໂທ')); ?></th>
                <th class="py-3 text-center" style="width: 160px; white-space: nowrap;"><?php echo htmlspecialchars(t('pos.col_member_card', 'ເລກບັດສະມາຊິກ')); ?></th>
                <th class="py-3 text-center" style="width: 140px; white-space: nowrap;"><?php echo htmlspecialchars(t('pos.col_registered_at', 'ເວລາທີ່ສະໝັກ')); ?></th>
                <th class="py-3 text-center" style="width: 110px; white-space: nowrap;"><?php echo htmlspecialchars(t('pos.col_select', 'ເລືອກ')); ?></th>
              </tr>
            </thead>
            <tbody id="customerTableBody" style="font-size: 0.92rem; white-space: nowrap;">
              <!-- Dynamically populated -->
            </tbody>
          </table>
        </div>
        <!-- Page info + page buttons, filled by renderCustomerPager() in pos_customer_js.php -->
        <div id="customerPager" class="d-flex justify-content-between align-items-center flex-wrap mt-3" style="gap: 8px;"></div>
      </div>
      <div class="modal-footer border-0 bg-light p-3">
        <button type="button" class="btn btn-primary font-weight-bold px-3 py-2" onclick="selectCustomer(null)" data-dismiss="modal" style="border-radius: 6px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none; box-shadow: 0 3px 10px rgba(36, 72, 134, 0.30);">
          <i class="fas fa-user-check mr-1.5"></i> <?php echo htmlspecialchars(t('pos.select_general_customer', 'ເລືອກລູກຄ້າທົ່ວໄປ')); ?>
        </button>
        <button type="button" class="btn btn-secondary font-weight-bold px-3 py-2" data-dismiss="modal" style="border-radius: 6px;"><?php echo htmlspecialchars(t('pos.close', 'ປິດ')); ?></button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Quick Add Customer -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
      <div class="modal-header bg-primary text-white py-3 px-4">
        <h5 class="modal-title font-weight-bold" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <i class="fas fa-user-plus mr-2"></i> <?php echo htmlspecialchars(t('pos.add_customer_title', 'ເພີ່ມຂໍ້ມູນລູກຄ້າໃໝ່')); ?>
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body p-4">
        <form id="quickAddCustomerForm">

          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('pos.phone_label', 'ເບີໂທລະສັບ:')); ?> <span class="text-primary font-weight-normal">(<?php echo htmlspecialchars(t('pos.phone_search_hint', 'ພິມເບີໂທ, ຊື່ ຫຼື ເລກບັດ ເພື່ອຄົ້ນຫາລູກຄ້າເກົ່າ')); ?>)</span></label>
            <input type="text" id="newCusPhone" class="form-control font-weight-bold" placeholder="020 XXXXXXXX" style="border-radius: 8px; height: 42px;" oninput="checkExistingCustomer()">
          </div>

          <div id="existingCustomerAlert" class="alert alert-info py-2 px-3 mb-3 d-none" style="border-radius: 8px; font-size: 0.85rem;">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <i class="fas fa-info-circle mr-1"></i> <?php echo htmlspecialchars(t('pos.existing_customer_found', 'ພົບຂໍ້ມູນລູກຄ້າເກົ່າ:')); ?> <strong id="existingCusInfoText"></strong>
              </div>
              <button type="button" class="btn btn-sm btn-primary font-weight-bold ml-2 py-0 px-2" id="btnSelectExistingCus" style="font-size: 0.88rem;">
                <i class="fas fa-check-circle mr-1"></i> <?php echo htmlspecialchars(t('pos.select_this_existing_customer', 'ເລືອກລູກຄ້າເກົ່ານີ້')); ?>
              </button>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('pos.customer_code_label', 'ລະຫັດລູກຄ້າ')); ?> <span class="text-danger">*</span></label>
              <input type="text" id="newCusCode" class="form-control bg-light font-weight-bold" readonly placeholder="CUST-001" style="border-radius: 8px; height: 42px; color: #244886;">
            </div>

            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('pos.customer_fullname_label', 'ຊື່ ແລະ ນາມສະກຸນ')); ?> <span class="text-danger">*</span></label>
              <input type="text" id="newCusName" class="form-control" placeholder="<?php echo htmlspecialchars(t('pos.customer_fullname_placeholder', 'ປ້ອນຊື່ ແລະ ນາມສະກຸນ')); ?>" required style="border-radius: 8px; height: 42px;" oninput="checkExistingCustomer()">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('pos.member_card_label', 'ເລກບັດສະມາຊິກ')); ?></label>
              <input type="text" id="newCusMemberCard" class="form-control" placeholder="<?php echo htmlspecialchars(t('pos.member_card_placeholder', 'ຕົວຢ່າງ: MB-001001')); ?>" style="border-radius: 8px; height: 42px;" oninput="checkExistingCustomer()">
            </div>

            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('pos.notes_label', 'ໝາຍເຫດ')); ?></label>
              <input type="text" id="newCusNotes" class="form-control" placeholder="<?php echo htmlspecialchars(t('pos.notes_placeholder', 'ປ້ອນໝາຍເຫດເພີ່ມເຕີມ (ຖ້າມີ)')); ?>" style="border-radius: 8px; height: 42px;">
            </div>
          </div>

          <div class="form-group mb-1">
            <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('pos.customer_address_label', 'ທີ່ຢູ່')); ?></label>
            <input type="text" id="newCusAddress" class="form-control" placeholder="<?php echo htmlspecialchars(t('pos.customer_address_placeholder', 'ບ້ານ, ເມືອງ, ແຂວງ / ຈຸດສັງເກດ')); ?>" style="border-radius: 8px; height: 42px;">
          </div>

        </form>
      </div>
      <div class="modal-footer border-0 bg-light p-3">
        <button type="button" class="btn btn-light font-weight-bold px-4" style="border-radius: 6px;" data-dismiss="modal"><?php echo htmlspecialchars(t('pos.cancel', 'ຍົກເລີກ')); ?></button>
        <button type="button" class="btn btn-primary font-weight-bold px-4 shadow-sm" style="border-radius: 6px;" onclick="submitQuickAddCustomer()">
          <i class="fas fa-save mr-1"></i> <?php echo htmlspecialchars(t('pos.save', 'ບັນທຶກ')); ?>
        </button>
      </div>
    </div>
  </div>
</div>
