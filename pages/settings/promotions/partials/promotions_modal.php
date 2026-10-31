<!-- Modal: Add Promo -->
<div class="modal fade" id="addPromoModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
      <form action="" method="POST">
        <input type="hidden" name="action" value="add_promo">
        <div class="modal-header text-white py-3 px-4" style="background-color: #244886;">
          <h5 class="modal-title font-weight-bold" style="font-size: 1rem;">
            <i class="fas fa-plus-circle mr-2"></i> <?php echo htmlspecialchars(t('promotions.modal_add_title', 'ສ້າງໂປຣໂມຊັ່ນໃໝ່')); ?>
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body p-4">
          <div class="row">
            <div class="col-md-7 mb-3 mp-wide">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('promotions.field_name', 'ຊື່ໂປຣ:')); ?> <span class="text-danger">*</span></label>
              <input type="text" name="promo_name" class="form-control" placeholder="<?php echo htmlspecialchars(t('promotions.field_name_ph', 'ເຊັ່ນ: ຫຼຸດພິເສດທ້າຍປີ, ສ່ວນຫຼຸດ 10%...')); ?>" required style="height: 42px;">
            </div>
            <div class="col-md-5 mb-3 mp-wide">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('promotions.field_type', 'ປະເພດ:')); ?></label>
              <select name="promo_type" id="add_promo_type" class="form-control" style="height: 42px;" onchange="togglePromoType(this.value, 'add')">
                <option value="discount"><?php echo htmlspecialchars(t('promotions.opt_discount', 'ສ່ວນຫຼຸດທົ່ວໄປ')); ?></option>
                <option value="qty_discount"><?php echo htmlspecialchars(t('promotions.opt_qty_discount', 'ສ່ວນຫຼຸດຕາມຈຳນວນຊື້')); ?></option>
                <option value="amount_discount"><?php echo htmlspecialchars(t('promotions.opt_amount_discount', 'ສ່ວນຫຼຸດຕາມຍອດຊື້')); ?></option>
                <option value="buy_x_get_y"><?php echo htmlspecialchars(t('promotions.opt_buy_x_get_y', 'ຊື້ X ແຖມ Y / ແຖມສິນຄ້າ')); ?></option>
              </select>
            </div>
          </div>

          <div class="row" id="add_gift_section" style="display: none;">
            <div class="col-md-8 mb-3">
              <label class="font-weight-bold text-success small mb-1"><i class="fas fa-gift mr-1"></i> <?php echo htmlspecialchars(t('promotions.field_gift_name', 'ຊື່ສິນຄ້າທີ່ແຖມ:')); ?></label>
              <input type="text" name="gift_product_name" id="add_gift_product_name" class="form-control border-success" list="product_datalist" placeholder="<?php echo htmlspecialchars(t('promotions.field_gift_name_ph', 'ເຊັ່ນ: ນ້ຳກ້ອນ, ເຄື່ອງດື່ມ...')); ?>" style="height: 42px;">
            </div>
            <div class="col-md-4 mb-3">
              <label class="font-weight-bold text-success small mb-1"><?php echo htmlspecialchars(t('promotions.field_gift_qty', 'ຈຳນວນແຖມ:')); ?></label>
              <input type="number" name="gift_qty" id="add_gift_qty" class="form-control text-right border-success" value="1" min="1" style="height: 42px;">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('promotions.field_discount_type', 'ປະເພດສ່ວນຫຼຸດ%:')); ?></label>
              <select name="discount_type" class="form-control" style="height: 42px;">
                <option value="percentage"><?php echo htmlspecialchars(t('promotions.opt_percentage', 'ເປີເຊັນ (%)')); ?></option>
                <option value="fixed"><?php echo htmlspecialchars(t('promotions.opt_fixed', 'ຈຳນວນເງິນ (' . kipSymbol() . ')')); ?></option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('promotions.field_discount_value', 'ມູນຄ່າສ່ວນຫຼຸດ:')); ?> <span class="text-danger">*</span></label>
              <input type="text" inputmode="decimal" name="discount_value" class="form-control font-weight-bold text-danger text-right" placeholder="0" required style="height: 42px;" oninput="formatNumberInput(this)">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('promotions.field_start_date', 'ເລີ່ມວັນທີ:')); ?></label>
              <input type="date" name="start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required style="height: 42px;">
            </div>
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('promotions.field_end_date', 'ຫາ ວັນທີ:')); ?></label>
              <input type="date" name="end_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required style="height: 42px;">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('promotions.field_min_qty', 'ຈຳນວນຂັ້ນຕ່ຳ:')); ?></label>
              <input type="text" inputmode="numeric" name="min_qty" class="form-control text-right" placeholder="0" value="0" style="height: 42px;" oninput="formatNumberInput(this)">
            </div>
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('promotions.field_min_amount', 'ຍອດເງິນຂັ້ນຕ່ຳ:')); ?></label>
              <input type="text" inputmode="decimal" name="min_amount" class="form-control text-right" placeholder="0" value="0" style="height: 42px;" oninput="formatNumberInput(this)">
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('promotions.field_target_type', 'ເປົ້າໝາຍໂປຣ:')); ?></label>
              <select name="target_type" id="add_target_type" class="form-control" style="height: 42px;" onchange="toggleTargetInput(this.value, 'add')">
                <option value="all"><?php echo htmlspecialchars(t('promotions.opt_target_all', 'ທຸກສິນຄ້າ')); ?></option>
                <option value="category"><?php echo htmlspecialchars(t('promotions.opt_target_category', 'ສະເພາະ ໝວດໝູ່ສິນຄ້າ')); ?></option>
                <option value="product"><?php echo htmlspecialchars(t('promotions.opt_target_product', 'ສະເພາະ ສິນຄ້າ')); ?></option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label id="add_target_name_label" class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('promotions.field_target_name', 'ຊື່ ສິນຄ້າ/ປະເພດ:')); ?></label>
              <input type="text" name="target_name" id="add_target_name" class="form-control" value="<?php echo htmlspecialchars(t('promotions.opt_target_all', 'ທຸກສິນຄ້າ')); ?>" placeholder="<?php echo htmlspecialchars(t('promotions.field_target_name_ph', 'ເຊັ່ນ: ເບຍລາວ...')); ?>" style="height: 42px;">
              <small id="add_target_name_hint" class="d-block mt-1 font-weight-bold" style="font-size:0.78rem; min-height:1em;"></small>
            </div>
            <div class="col-md-4 mb-3">
              <label class="font-weight-bold text-primary small mb-1"><i class="fas fa-boxes mr-1"></i> <?php echo htmlspecialchars(t('promotions.field_target_unit', 'ສະເພາະ ຫົວໜ່ວຍ:')); ?></label>
              <input type="text" name="target_unit_name" id="add_target_unit_name" list="units_datalist" class="form-control border-primary" value="all" placeholder="<?php echo htmlspecialchars(t('promotions.field_target_unit_ph', 'ປ້ອນຫົວໜ່ວຍ (ເຊັ່ນ: ລັງ, ແກ້ວ, ປ໋ອງ, all...)')); ?>" style="height: 42px;">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('promotions.field_branch', 'ສາຂາ:')); ?></label>
              <select name="branch_id" id="add_branch_id" class="form-control" style="height: 42px;" <?php echo (!$canAllBranches) ? 'disabled' : ''; ?>>
                <?php if ($canAllBranches): ?>
                  <option value="0"><?php echo htmlspecialchars(t('promotions.opt_global', '-- ທຸກສາຂາ (Global) --')); ?></option>
                <?php endif; ?>
                <?php foreach ($stores as $st): ?>
                  <option value="<?php echo $st['store_id']; ?>" <?php echo (($filter_branch > 0 ? $filter_branch : $userStoreId) == $st['store_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($st['store_name']); ?> <?php echo !empty($st['is_main']) ? htmlspecialchars(t('promotions.main_branch_suffix', '(ສາງຫຼັກ)')) : ''; ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <?php if (!$canAllBranches): ?>
                <input type="hidden" name="branch_id" value="<?php echo $userStoreId; ?>">
              <?php endif; ?>
            </div>
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('promotions.field_status', 'ສະຖານະ:')); ?></label>
              <select name="status" class="form-control" style="height: 42px;">
                <option value="1"><?php echo htmlspecialchars(t('promotions.opt_enable', 'ເປີດໃຊ້ງານ')); ?></option>
                <option value="0"><?php echo htmlspecialchars(t('promotions.opt_disable', 'ປິດໃຊ້ງານ')); ?></option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pb-4 px-4 pt-0">
          <button type="button" class="btn btn-light font-weight-bold px-3" data-dismiss="modal"><?php echo htmlspecialchars(t('promotions.btn_cancel', 'ຍົກເລີກ')); ?></button>
          <button type="submit" class="btn btn-primary font-weight-bold px-4" style="background: linear-gradient(135deg, #2c5aa0, #244886); border: none;"><?php echo htmlspecialchars(t('promotions.btn_save', 'ບັນທຶກ')); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Edit Promo -->
<div class="modal fade" id="editPromoModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
      <form action="" method="POST">
        <input type="hidden" name="action" value="edit_promo">
        <input type="hidden" name="id" id="edit_promo_id" value="">
        <div class="modal-header text-white py-3 px-4" style="background-color: #244886;">
          <h5 class="modal-title font-weight-bold" style="font-size: 1rem;">
            <i class="fas fa-edit mr-2"></i> <?php echo htmlspecialchars(t('promotions.modal_edit_title', 'ແກ້ໄຂຂໍ້ມູນໂປຣໂມຊັ່ນ')); ?>
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body p-4">
          <div class="row">
            <div class="col-md-7 mb-3 mp-wide">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('promotions.field_name', 'ຊື່ໂປຣ:')); ?> <span class="text-danger">*</span></label>
              <input type="text" name="promo_name" id="edit_promo_name" class="form-control" required style="height: 42px;">
            </div>
            <div class="col-md-5 mb-3 mp-wide">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('promotions.field_type', 'ປະເພດ:')); ?></label>
              <select name="promo_type" id="edit_promo_type" class="form-control" style="height: 42px;" onchange="togglePromoType(this.value, 'edit')">
                <option value="discount"><?php echo htmlspecialchars(t('promotions.opt_discount', 'ສ່ວນຫຼຸດທົ່ວໄປ')); ?></option>
                <option value="qty_discount"><?php echo htmlspecialchars(t('promotions.opt_qty_discount', 'ສ່ວນຫຼຸດຕາມຈຳນວນຊື້')); ?></option>
                <option value="amount_discount"><?php echo htmlspecialchars(t('promotions.opt_amount_discount', 'ສ່ວນຫຼຸດຕາມຍອດຊື້')); ?></option>
                <option value="buy_x_get_y"><?php echo htmlspecialchars(t('promotions.opt_buy_x_get_y', 'ຊື້ X ແຖມ Y / ແຖມສິນຄ້າ')); ?></option>
              </select>
            </div>
          </div>

          <div class="row" id="edit_gift_section" style="display: none;">
            <div class="col-md-8 mb-3">
              <label class="font-weight-bold text-success small mb-1"><i class="fas fa-gift mr-1"></i> <?php echo htmlspecialchars(t('promotions.field_gift_name', 'ຊື່ສິນຄ້າທີ່ແຖມ:')); ?></label>
              <input type="text" name="gift_product_name" id="edit_gift_product_name" class="form-control border-success" list="product_datalist" placeholder="<?php echo htmlspecialchars(t('promotions.field_gift_name_ph', 'ເຊັ່ນ: ນ້ຳກ້ອນ, ເຄື່ອງດື່ມ...')); ?>" style="height: 42px;">
            </div>
            <div class="col-md-4 mb-3">
              <label class="font-weight-bold text-success small mb-1"><?php echo htmlspecialchars(t('promotions.field_gift_qty', 'ຈຳນວນແຖມ:')); ?></label>
              <input type="number" name="gift_qty" id="edit_gift_qty" class="form-control text-right border-success" value="1" min="1" style="height: 42px;">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('promotions.field_discount_type', 'ປະເພດສ່ວນຫຼຸດ%:')); ?></label>
              <select name="discount_type" id="edit_discount_type" class="form-control" style="height: 42px;">
                <option value="percentage"><?php echo htmlspecialchars(t('promotions.opt_percentage', 'ເປີເຊັນ (%)')); ?></option>
                <option value="fixed"><?php echo htmlspecialchars(t('promotions.opt_fixed', 'ຈຳນວນເງິນ (' . kipSymbol() . ')')); ?></option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('promotions.field_discount_value', 'ມູນຄ່າສ່ວນຫຼຸດ:')); ?> <span class="text-danger">*</span></label>
              <input type="text" inputmode="decimal" name="discount_value" id="edit_discount_value" class="form-control font-weight-bold text-danger text-right" required style="height: 42px;" oninput="formatNumberInput(this)">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('promotions.field_start_date', 'ເລີ່ມວັນທີ:')); ?></label>
              <input type="date" name="start_date" id="edit_start_date" class="form-control" required style="height: 42px;">
            </div>
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('promotions.field_end_date', 'ຫາ ວັນທີ:')); ?></label>
              <input type="date" name="end_date" id="edit_end_date" class="form-control" required style="height: 42px;">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('promotions.field_min_qty', 'ຈຳນວນຂັ້ນຕ່ຳ:')); ?></label>
              <input type="text" inputmode="numeric" name="min_qty" id="edit_min_qty" class="form-control text-right" style="height: 42px;" oninput="formatNumberInput(this)">
            </div>
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('promotions.field_min_amount', 'ຍອດເງິນຂັ້ນຕ່ຳ:')); ?></label>
              <input type="text" inputmode="decimal" name="min_amount" id="edit_min_amount" class="form-control text-right" style="height: 42px;" oninput="formatNumberInput(this)">
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('promotions.field_target_type', 'ເປົ້າໝາຍໂປຣ:')); ?></label>
              <select name="target_type" id="edit_target_type" class="form-control" style="height: 42px;" onchange="toggleTargetInput(this.value, 'edit')">
                <option value="all"><?php echo htmlspecialchars(t('promotions.opt_target_all', 'ທຸກສິນຄ້າ')); ?></option>
                <option value="category"><?php echo htmlspecialchars(t('promotions.opt_target_category', 'ສະເພາະ ໝວດໝູ່ສິນຄ້າ')); ?></option>
                <option value="product"><?php echo htmlspecialchars(t('promotions.opt_target_product', 'ສະເພາະ ສິນຄ້າ')); ?></option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label id="edit_target_name_label" class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('promotions.field_target_name', 'ຊື່ ສິນຄ້າ/ປະເພດ:')); ?></label>
              <input type="text" name="target_name" id="edit_target_name" class="form-control" style="height: 42px;">
              <small id="edit_target_name_hint" class="d-block mt-1 font-weight-bold" style="font-size:0.78rem; min-height:1em;"></small>
            </div>
            <div class="col-md-4 mb-3">
              <label class="font-weight-bold text-primary small mb-1"><i class="fas fa-boxes mr-1"></i> <?php echo htmlspecialchars(t('promotions.field_target_unit', 'ສະເພາະ ຫົວໜ່ວຍ:')); ?></label>
              <input type="text" name="target_unit_name" id="edit_target_unit_name" list="units_datalist" class="form-control border-primary" value="all" placeholder="<?php echo htmlspecialchars(t('promotions.field_target_unit_ph', 'ປ້ອນຫົວໜ່ວຍ (ເຊັ່ນ: ລັງ, ແກ້ວ, ປ໋ອງ, all...)')); ?>" style="height: 42px;">
            </div>
          </div>

          <div class="form-group mb-0">
            <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('promotions.field_branch', 'ສາຂາ:')); ?></label>
            <select name="branch_id" id="edit_branch_id" class="form-control" style="height: 42px;" <?php echo (!$canAllBranches) ? 'disabled' : ''; ?>>
              <?php if ($canAllBranches): ?>
                <option value="0"><?php echo htmlspecialchars(t('promotions.opt_global', '-- ທຸກສາຂາ (Global) --')); ?></option>
              <?php endif; ?>
              <?php foreach ($stores as $st): ?>
                <option value="<?php echo $st['store_id']; ?>">
                  <?php echo htmlspecialchars($st['store_name']); ?> <?php echo !empty($st['is_main']) ? htmlspecialchars(t('promotions.main_branch_suffix', '(ສາງຫຼັກ)')) : ''; ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (!$canAllBranches): ?>
              <input type="hidden" name="branch_id" value="<?php echo $userStoreId; ?>">
            <?php endif; ?>
          </div>
        </div>
        <div class="modal-footer border-0 pb-4 px-4 pt-0">
          <button type="button" class="btn btn-light font-weight-bold px-3" data-dismiss="modal"><?php echo htmlspecialchars(t('promotions.btn_cancel', 'ຍົກເລີກ')); ?></button>
          <button type="submit" class="btn btn-primary font-weight-bold px-4" style="background: linear-gradient(135deg, #2c5aa0, #244886); border: none;"><?php echo htmlspecialchars(t('promotions.btn_update', 'ອັບເດດ')); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<datalist id="category_datalist">
  <?php foreach ($categories as $cat): ?>
    <option value="<?php echo htmlspecialchars($cat); ?>"></option>
  <?php endforeach; ?>
</datalist>

<datalist id="product_datalist">
  <?php foreach ($productsList as $prod): ?>
    <option value="<?php echo htmlspecialchars($prod); ?>"></option>
  <?php endforeach; ?>
</datalist>

<datalist id="units_datalist">
  <option value="all"><?php echo htmlspecialchars(t('promotions.all_units', 'ທຸກຫົວໜ່ວຍ')); ?></option>
  <option value="ລັງ"></option>
  <option value="ແກ້ວ"></option>
  <option value="ເເກັດ"></option>
  <option value="ປ໋ອງ"></option>
  <option value="ຖົງ"></option>
  <option value="ຊິ້ນ"></option>
  <option value="ອັນ"></option>
</datalist>
