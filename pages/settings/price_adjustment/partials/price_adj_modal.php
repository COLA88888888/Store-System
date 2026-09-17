<!-- Modal Form: Add / Adjust Price -->
<div class="modal fade" id="priceAdjModal" tabindex="-1" role="dialog" aria-labelledby="priceAdjModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document" style="max-width: 760px;">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
      <div class="modal-header text-white py-3 px-4" style="border-top-left-radius: 16px; border-top-right-radius: 16px; background-color: #244886;">
        <h6 class="modal-title font-weight-bold m-0" id="priceAdjModalLabel">
          <i class="fas fa-edit mr-1.5"></i> <?php echo htmlspecialchars(t('price_adjustment.modal_title', 'ຟອມປັບປຸງລາຄາສິນຄ້າ')); ?>
        </h6>
        <button type="button" class="close text-white opacity-90" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form action="" method="POST" id="priceAdjForm">
        <div class="modal-body p-4">
          <input type="hidden" name="action" value="adjust_price">

          <!-- Grid Row 1: Branch & Target Mode -->
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('price_adjustment.label_branch', 'ສາຂາ:')); ?></label>
              <select name="store_id" id="modal_store_id" class="form-control" style="height: 42px;" <?php echo (!$canAllBranches) ? 'disabled' : ''; ?>>
                <?php foreach ($stores as $st): ?>
                  <option value="<?php echo $st['store_id']; ?>" <?php echo (($filter_store > 0 ? $filter_store : $userStoreId) == $st['store_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($st['store_name']); ?> <?php echo !empty($st['is_main']) ? htmlspecialchars(t('price_adjustment.badge_main_branch', '(ສາງຫຼັກ)')) : ''; ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <?php if (!$canAllBranches): ?>
                <input type="hidden" name="store_id" value="<?php echo $userStoreId; ?>">
              <?php endif; ?>
            </div>
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('price_adjustment.label_adjust_mode', 'ຮູບແບບການປັບ:')); ?> <span class="text-danger">*</span></label>
              <select name="target_mode" id="target_mode" class="form-control" style="height: 42px;" onchange="onTargetModeChange(this.value)">
                <option value="product"><?php echo htmlspecialchars(t('price_adjustment.opt_by_product', 'ຕາມສິນຄ້າ')); ?></option>
                <option value="category"><?php echo htmlspecialchars(t('price_adjustment.opt_by_category', 'ຕາມໝວດໝູ່ສິນຄ້າ')); ?></option>
              </select>
            </div>
          </div>

          <div class="row">
            <div class="col-12 mb-3" id="category_select_wrap" style="display: none;">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('price_adjustment.label_category', 'ໝວດໝູ່ສິນຄ້າ:')); ?> <span class="text-danger">*</span></label>
              <select name="category_id" id="category_id" class="form-control" style="width: 100%; height: 42px;">
                <option value=""><?php echo htmlspecialchars(t('price_adjustment.opt_select_category', '-- ເລືອກໝວດໝູ່ --')); ?></option>
                <?php foreach ($categoriesList as $cat): ?>
                  <option value="<?php echo $cat['category_id']; ?>">
                    <?php echo htmlspecialchars($cat['category_name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-12 mb-3" id="product_scan_wrap">
              <label class="font-weight-bold text-primary small mb-1"><i class="fas fa-barcode mr-1"></i> <?php echo htmlspecialchars(t('price_adjustment.label_scan', 'ພິມລະຫັດ / ສະແກນບາໂຄ້ດສິນຄ້າ:')); ?> <span class="text-danger">*</span></label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-primary text-white font-weight-bold"><i class="fas fa-search"></i></span>
                </div>
                <input type="text" id="quick_search_code" class="form-control font-weight-bold" placeholder="<?php echo htmlspecialchars(t('price_adjustment.placeholder_scan', 'ສະແກນບາໂຄ້ດ ຫຼື ພິມລະຫັດສິນຄ້າ...')); ?>" autocomplete="off" style="height: 42px; font-size: 0.95rem;" onkeyup="quickFindProduct(this.value)">
              </div>
            </div>
          </div>

          <!-- Grid Row 2: Product Name Display (Full width across left and right or paired) -->
          <div class="row" id="product_name_wrap">
            <div class="col-12 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('price_adjustment.label_product_name', 'ຊື່ສິນຄ້າ (ສະແດງອໍໂຕ້):')); ?></label>
              <input type="hidden" name="product_id" id="product_id_hidden">
              <input type="text" id="product_name_display" class="form-control bg-light font-weight-bold text-dark" readonly placeholder="<?php echo htmlspecialchars(t('price_adjustment.placeholder_product_name', '-- ລໍຖ້າສະແກນບາໂຄ້ດ / ພິມລະຫັດສິນຄ້າ --')); ?>" style="height: 42px; font-size: 0.95rem; border: 1.5px solid #cbd5e1;">

              <!-- Hidden select element for data matching -->
              <select id="product_id" style="display: none;" onchange="onSelectProduct(this)">
                <option value=""><?php echo htmlspecialchars(t('price_adjustment.opt_select_product', '-- ເລືອກສິນຄ້າ --')); ?></option>
                <?php foreach ($productsList as $p): ?>
                  <?php 
                    $codeStr = !empty($p['barcode']) ? $p['barcode'] : ($p['product_id']);
                  ?>
                  <option value="<?php echo $p['product_id']; ?>"
                          data-name="<?php echo htmlspecialchars($p['product_name']); ?>"
                          data-code="<?php echo htmlspecialchars($codeStr); ?>"
                          data-barcode="<?php echo htmlspecialchars($p['barcode'] ?? ''); ?>"
                          data-id="<?php echo $p['product_id']; ?>"
                          data-bprice="<?php echo $p['bprice']; ?>"
                          data-price="<?php echo $p['price']; ?>"
                          data-unit="<?php echo htmlspecialchars($p['unit'] ?? ''); ?>">
                    [<?php echo htmlspecialchars($codeStr); ?>] <?php echo htmlspecialchars($p['product_name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- Grid Row 2: Old Prices Display (Cost vs Sales) -->
          <div class="row" id="old_prices_display" style="display: flex;">
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-muted small mb-1"><?php echo htmlspecialchars(t('price_adjustment.label_old_bprice', 'ລາຄາຊື້ເກົ່າ:')); ?></label>
              <input type="text" id="cur_bprice" class="form-control bg-light text-right" readonly placeholder="0 ₭" style="height: 42px;">
            </div>
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-muted small mb-1"><?php echo htmlspecialchars(t('price_adjustment.label_old_price', 'ລາຄາຂາຍເກົ່າ:')); ?></label>
              <input type="text" id="cur_price" class="form-control bg-light text-right font-weight-bold text-primary" readonly placeholder="0 ₭" style="height: 42px;">
            </div>
          </div>

          <!-- Grid Row 3: Target Price Type & Calc Type -->
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('price_adjustment.label_target_price', 'ປັບລາຄາ:')); ?> <span class="text-danger">*</span></label>
              <select name="target_price_type" id="target_price_type" class="form-control">
                <option value="price"><?php echo htmlspecialchars(t('price_adjustment.opt_target_price', 'ປັບ ລາຄາຂາຍ')); ?></option>
                <option value="bprice"><?php echo htmlspecialchars(t('price_adjustment.opt_target_bprice', 'ປັບ ລາຄາຊື້')); ?></option>
                <option value="both"><?php echo htmlspecialchars(t('price_adjustment.opt_target_both', 'ປັບ ທັງລາຄາຂາຍ ແລະ ລາຄາຊື້')); ?></option>
              </select>
            </div>

            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('price_adjustment.label_calc_type', 'ປະເພດການປັບ:')); ?> <span class="text-danger">*</span></label>
              <select name="calc_type" id="calc_type" class="form-control">
                <option value="set"><?php echo htmlspecialchars(t('price_adjustment.opt_calc_set', 'ຕັ້ງລາຄາໃໝ່')); ?></option>
                <option value="increase"><?php echo htmlspecialchars(t('price_adjustment.opt_calc_increase', 'ເພີ່ມລາຄາ')); ?></option>
                <option value="decrease"><?php echo htmlspecialchars(t('price_adjustment.opt_calc_decrease', 'ຫຼຸດລາຄາ')); ?></option>
              </select>
            </div>
          </div>

          <!-- Grid Row 4: Adjust Value & Remark -->
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('price_adjustment.label_adjust_value', 'ຈຳນວນເງິນປັບ:')); ?> <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="text" inputmode="decimal" name="adjust_value" id="adjust_value" class="form-control font-weight-bold text-danger text-right" placeholder="0" required style="height: 42px;" oninput="formatNumberInput(this)">
                <div class="input-group-append"><span class="input-group-text font-weight-bold">₭</span></div>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('price_adjustment.label_remark', 'ໝາຍເຫດ:')); ?></label>
              <input type="text" name="remark" class="form-control" placeholder="<?php echo htmlspecialchars(t('price_adjustment.placeholder_remark', 'ເຊັ່ນ: ຕົ້ນທຶນເພີ່ມ, ປັບລາຄາຂາຍສົ່ງ...')); ?>" style="height: 42px;">
            </div>
          </div>

        </div>
        <div class="modal-footer bg-light py-2.5 px-4" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
          <button type="button" class="btn btn-secondary font-weight-bold px-3 py-2" data-dismiss="modal" style="border-radius: 6px;">
            <?php echo htmlspecialchars(t('price_adjustment.btn_cancel', 'ຍົກເລີກ')); ?>
          </button>
          <button type="submit" class="btn btn-primary font-weight-bold text-white px-4 py-2 shadow-sm" style="border-radius: 6px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
            <i class="fas fa-check-circle mr-1"></i><?php echo htmlspecialchars(t('price_adjustment.btn_save', 'ບັນທຶກ')); ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
