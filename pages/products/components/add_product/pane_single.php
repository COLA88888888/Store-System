<?php
/*
 * ສ່ວນ: ແທັບ: ເພີ່ມທີລະລາຍການ
 * ຟອມເພີ່ມສິນຄ້າ 1 ລາຍການ: ປະເພດ, ລະຫັດ, ຊື່, ບາໂຄ້ດ, ຫົວໜ່ວຍ, ສະຕັອກ, ຜູ້ສະໜອງ, ວັນໝົດອາຍຸ,
 * ລາຄາ, ຕັດສະຕັອກ, ຮູບ ແລະ ຫົວໜ່ວຍເພີ່ມເຕີມ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/products/components/form_add_product.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>      <!-- ============================ PANE 1: SINGLE ADD ============================ -->
      <div class="tab-pane fade show active" id="pane-add-single" role="tabpanel">
      <form id="addProductForm" novalidate action="" method="POST" enctype="multipart/form-data" onsubmit="return submitAddProduct(event)">
        <input type="hidden" name="action" value="add_product">
        <input type="hidden" name="product_id" id="add_product_id">

        <!-- BODY -->
        <div class="modal-body p-4" style="max-height: 78vh; overflow-y: auto;">
          <div class="row align-items-center">
            
            <!-- LEFT: Product Image -->
            <div class="col-md-4 text-center border-right pr-md-4 py-2 d-flex flex-column align-items-center justify-content-center">
              <div class="position-relative my-2 d-inline-block" style="width: 140px; height: 140px;">
                <img id="add_img_preview"
                     src="<?php echo $base_path; ?>assets/img/image.jpg"
                     class="shadow-sm"
                     style="width: 140px; height: 140px; object-fit: cover; border-radius: 14px; border: 2px dashed #93c5fd; background: #f8fafc; cursor: pointer;"
                     onclick="document.getElementById('add_product_img').click();"
                     title="<?php echo htmlspecialchars(t('products.img_click_select_title', 'ຄລິກເພື່ອເລືອກຮູບພາບ')); ?>">
                
                <!-- Camera button -->
                <button type="button"
                        class="position-absolute d-flex align-items-center justify-content-center shadow-sm border-0"
                        style="right: -6px; bottom: -6px; width: 34px; height: 34px; background: #2563eb; color: #fff; border-radius: 50%; border: 2px solid #fff; cursor: pointer; z-index: 2;"
                        onclick="document.getElementById('add_product_img').click();"
                        title="<?php echo htmlspecialchars(t('products.img_select_btn_title', 'ເລືອກຮູບ')); ?>">
                  <i class="fas fa-camera" style="font-size: 0.85rem;"></i>
                </button>

                <!-- Remove button (hidden by default) -->
                <button type="button" id="btn_remove_add_img"
                        onclick="removeProductImg('add');"
                        class="position-absolute align-items-center justify-content-center border-0 shadow-sm"
                        style="right: -6px; top: -6px; width: 30px; height: 30px; background: #ef4444; color: #fff; border-radius: 50%; border: 2px solid #fff; cursor: pointer; display: none; z-index: 3;"
                        title="<?php echo htmlspecialchars(t('products.img_remove_btn_title', 'ລົບຮູບ')); ?>">
                  <i class="fas fa-times" style="font-size: 0.82rem;"></i>
                </button>
              </div>

              <small class="text-muted mt-1 font-weight-bold" style="font-size: 0.78rem;"><?php echo htmlspecialchars(t('products.img_select_hint', 'ຄລິກເພື່ອເລືອກຮູບສິນຄ້າ')); ?></small>
              <input type="file" name="product_img" id="add_product_img" accept="image/*" style="display: none;"
                     onchange="previewProductImg(this, 'add_img_preview', 'btn_remove_add_img')">
            </div>

            <!-- RIGHT: Base Form Fields -->
            <div class="col-md-8 pl-md-4 py-2">
              
              <!-- Row 1: ປະເພດສິນຄ້າ (ຂຶ້ນກ່ອນ) & ລະຫັດສິນຄ້າ (Auto) -->
              <div class="row">
                <div class="col-md-7 mb-3">
                  <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('products.field_category', 'ປະເພດສິນຄ້າ:')); ?> <span class="text-danger">*</span></label>
                  <select id="add_category_id" name="category_id" class="form-control" style="border-radius: 6px; height: 40px;" onchange="onAddCategoryChange()" required>
                    <option value=""><?php echo htmlspecialchars(t('products.select_category_placeholder', '-- ເລືອກປະເພດ --')); ?></option>
                    <?php foreach ($categories as $cat): ?>
                      <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-5 mb-3">
                  <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('products.field_product_code', 'ລະຫັດສິນຄ້າ:')); ?></label>
                  <div id="add_product_id_display" class="form-control bg-light d-flex align-items-center" style="border-radius: 6px; height: 40px; font-family: 'Courier New', Courier, monospace; font-size: 1.05rem; font-weight: bold; overflow: hidden; letter-spacing: 0.5px;">
                    <span class="text-muted" style="font-family: inherit; font-size: 0.88rem; font-weight: normal;"><?php echo htmlspecialchars(t('products.product_code_placeholder', 'ເລືອກປະເພດສິນຄ້າ...')); ?></span>
                  </div>
                </div>
              </div>

              <!-- Row 2: ຊື່ສິນຄ້າ & ບາໂຄ້ດຫຼັກ -->
              <div class="row">
                <div class="col-md-7 mb-3">
                  <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('products.field_product_name', 'ຊື່ສິນຄ້າ:')); ?> </label>
<input type="text" id="add_product_name" name="product_name" class="form-control" placeholder="<?php echo htmlspecialchars(t('products.product_name_placeholder', 'ປ້ອນຊື່ສິນຄ້າ...')); ?>" style="border-radius: 6px; height: 40px;" required>
                </div>
                <div class="col-md-5 mb-3">
                  <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('products.field_barcode', 'ບາໂຄ້ດ:')); ?></label>
                  <div class="input-group">
                    <input type="text" id="add_barcode" name="barcode" class="form-control" placeholder="<?php echo htmlspecialchars(t('products.barcode_placeholder', 'ສະແກນ ຫຼື ປ້ອນຕົວເລກ...')); ?>" style="border-radius: 6px 0 0 6px; height: 40px;" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    <div class="input-group-append">
                      <button type="button" class="btn btn-outline-primary font-weight-bold" onclick="generateEAN13('add_barcode')" title="<?php echo htmlspecialchars(t('products.gen_barcode_title', 'ສ້າງບາໂຄ້ດ 13 ຫຼັກ')); ?>" style="border-radius: 0 6px 6px 0; height: 40px; border-color: #cbd5e1;">
                        <i class="fas fa-barcode mr-1"></i> <?php echo htmlspecialchars(t('products.gen_barcode_btn', 'ສ້າງ')); ?>
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Row 3: ຫົວໜ່ວຍຍ່ອຍພື້ນຖານ & ຈຳນວນສະຕັອກ (ສະເພາະແອັດມິນ) -->
              <div class="row">
                <div class="<?php echo $isAdmin ? 'col-md-7' : 'col-md-12'; ?> mb-3">
                  <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('products.field_unit', 'ຫົວໜ່ວຍ:')); ?> <span class="text-danger">*</span></label>
                  <select name="unit_id" id="add_unit" class="form-control" style="border-radius: 6px; height: 40px;" required>
                    <option value=""><?php echo htmlspecialchars(t('products.select_unit_placeholder', '-- ເລືອກຫົວໜ່ວຍ --')); ?></option>
                    <?php foreach ($units as $u): ?>
                      <option value="<?php echo (int)$u['unit_id']; ?>"><?php echo htmlspecialchars($u['unit_name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <?php if ($isAdmin): ?>
                  <div class="col-md-5 mb-3">
                    <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('products.field_qty', 'ຈຳນວນສະຕັອກ:')); ?></label>
                    <input type="number" name="qty" id="add_qty" class="form-control text-center font-weight-bold text-primary" placeholder="0" min="0" value="0" style="border-radius: 6px; height: 40px;">
                  </div>
                <?php endif; ?>
              </div>

              <!-- Row 3b: ຜູ້ສະໜອງ & ວັນໝົດອາຍຸ (ທັງສອງບໍ່ບັງຄັບ) -->
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('products.field_supplier', 'ຜູ້ສະໜອງ:')); ?></label>
                  <select name="supplier_id" id="add_supplier_id" class="form-control" style="border-radius: 6px; height: 40px;">
                    <option value=""><?php echo htmlspecialchars(t('products.select_supplier_placeholder', '-- ບໍ່ລະບຸ / ເລືອກຜູ້ສະໜອງ --')); ?></option>
                    <?php foreach (($suppliers ?? []) as $sp): ?>
                      <option value="<?php echo (int)$sp['supplier_id']; ?>"><?php echo htmlspecialchars($sp['supplier_name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <?php if (empty($suppliers)): ?>
                    <small class="text-muted"><?php echo htmlspecialchars(t('products.supplier_empty_hint', 'ຍັງບໍ່ມີຜູ້ສະໜອງ — ເພີ່ມໄດ້ທີ່ເມນູ "ຈັດການຜູ້ສະໜອງ"')); ?></small>
                  <?php endif; ?>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('products.field_expiry_date', 'ວັນໝົດອາຍຸ:')); ?></label>
                  <input type="date" id="add_expiry_date" name="expiry_date" class="form-control" style="height: 40px; border-radius: 6px;">
                  <small class="text-muted"><?php echo htmlspecialchars(t('products.field_expiry_hint', 'ບໍ່ໃສ່ກໍໄດ້ — ໃສ່ແລ້ວລະບົບຈະແຈ້ງເຕືອນກ່ອນໝົດອາຍຸ')); ?></small>
                </div>
              </div>

              <!-- Row 3c: ໂຊນ (Zone) & ຊັ້ນວາງ (Shelf) (ເລືອກຈາກຕາຕະລາງ, ຖ້າບໍ່ເລືອກໂຊນ ບັອກຊັ້ນວາງແມ່ນເຊື່ອງໄວ້) -->
              <div class="row">
                <div class="col-md-6 mb-3" id="add_zone_box">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="font-weight-bold text-dark mb-0">
                      <?php echo htmlspecialchars(t('products.field_zone', 'ໂຊນ (Zone):')); ?>
                    </label>
                    <a href="javascript:void(0)" onclick="quickAddZonePrompt('add')" class="text-primary font-weight-bold" style="font-size: 0.78rem;">
                      <i class="fas fa-plus mr-1"></i>ເພີ່ມໂຊນ
                    </a>
                  </div>
                  <select id="add_zone" name="zone" class="form-control" style="border-radius: 6px; height: 40px;" onchange="onZoneSelectChange('add')">
                    <option value=""><?php echo htmlspecialchars(t('products.select_zone_placeholder', '-- ບໍ່ລະບຸ / ເລືອກໂຊນ --')); ?></option>
                    <?php foreach (($zones ?? []) as $z): ?>
                      <option value="<?php echo htmlspecialchars($z['zone_name']); ?>" data-id="<?php echo (int)$z['zone_id']; ?>"><?php echo htmlspecialchars($z['zone_name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6 mb-3" id="add_shelf_box" style="display: none;">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="font-weight-bold text-dark mb-0">
                      <?php echo htmlspecialchars(t('products.field_shelf', 'ຊັ້ນວາງ (Shelf):')); ?>
                    </label>
                    <a href="javascript:void(0)" onclick="quickAddShelfPrompt('add')" class="text-info font-weight-bold" style="font-size: 0.78rem;">
                      <i class="fas fa-plus mr-1"></i>ເພີ່ມຊັ້ນວາງ
                    </a>
                  </div>
                  <select id="add_shelf" name="shelf" class="form-control" style="border-radius: 6px; height: 40px;">
                    <option value=""><?php echo htmlspecialchars(t('products.select_shelf_placeholder', '-- ບໍ່ລະບຸ / ເລືອກຊັ້ນວາງ --')); ?></option>
                    <?php foreach (($shelves ?? []) as $sh): ?>
                      <option value="<?php echo htmlspecialchars($sh['shelf_name']); ?>" data-zone-id="<?php echo (int)($sh['zone_id'] ?? 0); ?>" data-zone-name="<?php echo htmlspecialchars($sh['zone_name'] ?? ''); ?>"><?php echo htmlspecialchars($sh['shelf_name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <!-- Row 4: ລາຄາຊື້ & ລາຄາຂາຍຍ່ອຍ -->
              <div class="row">
                <div class="col-6">
                  <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('products.field_bprice', 'ລາຄາຊື້:')); ?></label>
                  <div class="input-group">
                    <input type="text" id="add_bprice" name="bprice" class="form-control price-input text-right font-weight-bold" placeholder="0" style="border-radius: 6px 0 0 6px; height: 40px; color: #475569;" oninput="formatPriceInput(this)">
                    <div class="input-group-append"><span class="input-group-text" style="border-radius: 0 6px 6px 0; font-size: 0.85rem;">₭</span></div>
                  </div>
                </div>
                <div class="col-6">
                  <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('products.field_price', 'ລາຄາຂາຍ:')); ?> <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <input type="text" id="add_price" name="price" class="form-control price-input text-right font-weight-bold" placeholder="0" style="border-radius: 6px 0 0 6px; height: 40px; color: #16a34a;" oninput="formatPriceInput(this)" >
                    <div class="input-group-append"><span class="input-group-text" style="border-radius: 0 6px 6px 0; font-size: 0.85rem;">₭</span></div>
                  </div>
                </div>
              </div>

              <!-- Row 5: ຕັດສະຕັອກເມື່ອຂາຍ -->
              <div class="row mt-2">
                <div class="col-md-12">
                  <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="add_cut_qty" name="cut_qty" value="1" checked>
                    <label class="custom-control-label font-weight-bold text-dark" for="add_cut_qty">
                      <?php echo htmlspecialchars(t('products.field_cut_qty', 'ຕັດສະຕັອກເມື່ອຂາຍ')); ?>
                    </label>
                  </div>
                  <small class="text-muted"><?php echo htmlspecialchars(t('products.field_cut_qty_hint', 'ປິດໄວ້ສຳລັບ ບໍລິການ / ສິນຄ້າສັ່ງເຮັດ (ຂາຍໄດ້ໂດຍບໍ່ຕັດຈຳນວນຄັງ)')); ?></small>
                </div>
              </div>

            </div>
          </div>

          <!-- Multi-Unit Section (ຫຼາຍລາຄາ / ຫຼາຍຫົວໜ່ວຍ ເຊັ່ນ: ແພັກ, ແກັດ) -->
          <div class="mt-3 pt-3 border-top">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div>
                <span class="font-weight-bold text-dark" style="font-size: 0.95rem;">
                   <?php echo htmlspecialchars(t('products.multi_unit_title', 'ລາຄາ ແລະ ຫົວໜ່ວຍເພີ່ມເຕີມ (ຫຼາຍລາຄາ)')); ?>
                </span>
                <small class="text-muted d-block" style="font-size: 0.78rem;"><?php echo htmlspecialchars(t('products.multi_unit_hint', 'ຖ້າສິນຄ້ານີ້ມີຂາຍເປັນ ແພັກ (x6 ປ໋ອງ), ແກັດ (x24 ປ໋ອງ)... ສາມາດເພີ່ມໄດ້ທີ່ນີ້ (ຕັດສະຕັອກອັດຕະໂນມັດ)')); ?></small>
              </div>
              <button type="button" class="btn btn-sm btn-outline-primary font-weight-bold" style="border-radius: 6px; font-size: 0.82rem;" onclick="addUnitRow('add')">
                 <?php echo htmlspecialchars(t('products.btn_add_unit', 'ເພີ່ມລາຄາ/ຫົວໜ່ວຍ')); ?>
              </button>
            </div>

            <div id="add_multi_units_container">
              <!-- Dynamic Unit Rows -->
            </div>
          </div>

        </div>

        <!-- FOOTER -->
        <div class="modal-footer border-0 pt-0 pb-4 px-4">
          <button type="button" class="btn btn-light font-weight-bold px-4" style="border-radius: 6px; cursor: pointer;" data-dismiss="modal"><?php echo htmlspecialchars(t('products.cancel', 'ຍົກເລີກ')); ?></button>
          <button type="submit" class="btn btn-primary font-weight-bold px-4 shadow-sm" style="border-radius: 6px; cursor: pointer; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
            <i class="fas fa-save mr-1"></i> <?php echo htmlspecialchars(t('products.btn_save', 'ບັນທຶກ')); ?>
          </button>
        </div>
      </form>
      </div><!-- /pane-add-single -->

