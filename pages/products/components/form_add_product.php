<!-- Modal: Add Product -->
<div class="modal fade" id="addProductModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 1120px;">
    <div class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">

      <!-- HEADER -->
      <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, #2c5aa0, #244886) !important;">
        <h5 class="modal-title font-weight-bold" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif; font-size: 1.15rem;">
          <i class="fas fa-plus-circle mr-2"></i> <?php echo htmlspecialchars(t('products.modal_add_title', 'ເພີ່ມສິນຄ້າໃໝ່')); ?>
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <!-- MODE TABS: ເພີ່ມດ່ຽວ / ເພີ່ມຫຼາຍລາຍການ -->
      <ul class="nav nav-tabs px-4 pt-2" id="addProductModeTabs" role="tablist" style="border-bottom: 1px solid #e2e8f0;">
        <li class="nav-item">
          <a class="nav-link active font-weight-bold" id="tab-add-single" data-toggle="tab" href="#pane-add-single" role="tab" style="font-size: 0.9rem;">
            <i class="fas fa-box mr-1"></i> <?php echo htmlspecialchars(t('products.tab_add_single', 'ເພີ່ມດ່ຽວ')); ?>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link font-weight-bold" id="tab-add-bulk" data-toggle="tab" href="#pane-add-bulk" role="tab" style="font-size: 0.9rem;">
            <i class="fas fa-layer-group mr-1"></i> <?php echo htmlspecialchars(t('products.tab_add_bulk', 'ເພີ່ມຫຼາຍລາຍການ')); ?>
          </a>
        </li>
      </ul>

      <div class="tab-content">

      <!-- ============================ PANE 1: SINGLE ADD ============================ -->
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
                  <input type="text" id="add_product_id_display" class="form-control bg-light font-weight-bold text-primary" placeholder="<?php echo htmlspecialchars(t('products.product_code_placeholder', 'ເລືອກປະເພດສິນຄ້າ...')); ?>" style="border-radius: 6px; height: 40px;" readonly>
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

              <!-- Row 3b: ຜູ້ສະໜອງ (ບໍ່ບັງຄັບ) -->
              <div class="row">
                <div class="col-md-12 mb-3">
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

      <!-- ============================ PANE 2: BULK ADD ============================ -->
      <div class="tab-pane fade" id="pane-add-bulk" role="tabpanel">
      <form id="bulkAddProductForm" novalidate action="" method="POST" onsubmit="return submitBulkAddProduct(event)">
        <input type="hidden" name="action" value="add_products_bulk">

        <div class="modal-body p-4" style="max-height: 78vh; overflow-y: auto;">
          <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap" style="gap: 8px;">
            <small class="text-muted font-weight-bold" style="font-size: 0.8rem;">
              <i class="fas fa-info-circle mr-1"></i>
              <?php echo htmlspecialchars(t('products.bulk_hint', 'ປ້ອນໄດ້ຫຼາຍລາຍການພ້ອມກັນ — ບັອກທີ່ວ່າງຈະຖືກຂ້າມ. ຈຳນວນສະຕັອກເລີ່ມຕົ້ນ = 0')); ?>
            </small>
            <span class="badge badge-primary" style="font-size: 0.8rem;" id="bulkRowCounter">0</span>
          </div>

          <div class="custom-control custom-switch mb-3">
            <input type="checkbox" class="custom-control-input" id="bulk_no_cut_qty" name="bulk_no_cut_qty" value="1">
            <label class="custom-control-label font-weight-bold text-dark" for="bulk_no_cut_qty">
              <?php echo htmlspecialchars(t('products.bulk_no_cut_qty', 'ບໍ່ຕັດສະຕັອກ ສຳລັບທຸກລາຍການໃນຊຸດນີ້')); ?>
            </label>
            <br><small class="text-muted"><?php echo htmlspecialchars(t('products.bulk_no_cut_qty_hint', 'ໃຊ້ສຳລັບບໍລິການ / ສິນຄ້າສັ່ງເຮັດ — ຂາຍໄດ້ໂດຍບໍ່ຕັດຈຳນວນຄັງ. ຕັ້ງທີ່ນີ້ຄັ້ງດຽວ ແທນທີ່ຈະໄປແກ້ໄຂເອົາທີລະລາຍການພາຍຫຼັງ')); ?></small>
          </div>

          <div id="bulk_rows_container"></div>

          <div class="btn-group btn-group-sm mt-2" role="group">
            <button type="button" class="btn btn-outline-primary font-weight-bold" style="border-radius: 6px 0 0 6px;" onclick="bulkAddRows(1)">
              <i class="fas fa-plus mr-1"></i> <?php echo htmlspecialchars(t('products.bulk_add_row', 'ເພີ່ມແຖວ')); ?>
            </button>
            <button type="button" class="btn btn-outline-primary font-weight-bold px-3" onclick="bulkAddRows(5)">+5</button>
            <button type="button" class="btn btn-outline-primary font-weight-bold px-3" style="border-radius: 0 6px 6px 0;" onclick="bulkAddRows(10)">+10</button>
          </div>
        </div>

        <div class="modal-footer border-0 pt-0 pb-4 px-4">
          <button type="button" class="btn btn-light font-weight-bold px-4" style="border-radius: 6px; cursor: pointer;" data-dismiss="modal"><?php echo htmlspecialchars(t('products.cancel', 'ຍົກເລີກ')); ?></button>
          <button type="submit" class="btn btn-primary font-weight-bold px-4 shadow-sm" style="border-radius: 6px; cursor: pointer; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
            <i class="fas fa-save mr-1"></i> <?php echo htmlspecialchars(t('products.bulk_btn_save', 'ບັນທຶກທັງໝົດ')); ?>
          </button>
        </div>
      </form>
      </div><!-- /pane-add-bulk -->

      </div><!-- /tab-content -->
    </div>
  </div>
</div>

<script>
var I18N_ADD_PRODUCT = <?php echo tjson([
    'products.alert_notice_title' => 'ແຈ້ງເຕືອນ',
    'products.btn_ok' => 'ຕົກລົງ',
    'products.validate_category' => 'ກະລຸນາເລືອກປະເພດສິນຄ້າ!',
    'products.validate_name' => 'ກະລຸນາປ້ອນຊື່ສິນຄ້າ!',
    'products.validate_barcode_required' => 'ກະລຸນາປ້ອນ ຫຼື ສະແກນລະຫັດບາໂຄ້ດສິນຄ້າ!',
    'products.validate_barcode_numeric' => 'ລະຫັດບາໂຄ້ດຮອງຮັບສະເພາະຕົວເລກ (0-9) ເທົ່ານັ້ນ!',
    'products.validate_unit' => 'ກະລຸນາປ້ອນຫົວໜ່ວຍ!',
    'products.validate_price' => 'ກະລຸນາປ້ອນລາຄາຂາຍ!',
    'products.validate_unit_name' => 'ກະລຸນາປ້ອນຊື່ຫົວໜ່ວຍເພີ່ມເຕີມ!',
    'products.validate_unit_qty' => 'ກະລຸນາປ້ອນຈຳນວນຫົວໜ່ວຍເພີ່ມເຕີມ!',
    'products.validate_unit_price' => 'ກະລຸນາປ້ອນລາຄາຂາຍຫົວໜ່ວຍເພີ່ມເຕີມ!',
    'products.alert_duplicate_title' => 'ລະຫັດບາໂຄ້ດຊໍ້າກັນ!',
    'products.duplicate_in_form_text' => 'ລະຫັດບາໂຄ້ດ "{value}" ນີ້ຊໍ້າກັນກັບຫົວໜ່ວຍອື່ນໃນຟອມດຽວກັນ!',
    'products.duplicate_in_db_text' => 'ລະຫັດບາໂຄ້ດ "{value}" ນີ້ມີໃນລະບົບແລ້ວ!',
    'products.unit_row_name_label' => 'ຊື່ຫົວໜ່ວຍ:',
    'products.unit_row_name_placeholder' => 'ເຊັ່ນ: ແພັກ, ແກັດ...',
    'products.unit_row_qty_label' => 'ຈຳນວນ:',
    'products.unit_row_qty_placeholder' => 'ເຊັ່ນ: 6',
    'products.unit_row_bprice_label' => 'ລາຄາຊື້:',
    'products.unit_row_price_label' => 'ລາຄາຂາຍ:',
    'products.unit_row_barcode_label' => 'ບາໂຄ້ດ:',
    'products.unit_row_barcode_placeholder' => 'ບາໂຄ້ດ...',
    'products.gen_barcode_title' => 'ສ້າງບາໂຄ້ດ 13 ຫຼັກ',
    'products.unit_row_delete_title' => 'ລົບແຖວນີ້',
]); ?>;

// ດຶງລຳດັບການປ້ອນສິນຄ້າເພີ່ມເຂົ້າທົ່ວລະບົບ (Sequence: 0001, 0002, 0003...)
var GLOBAL_NEXT_SEQ = '<?php echo htmlspecialchars($nextSeqFormatted ?? "0001"); ?>';
var LAST_ADD_CATEGORY_ID = localStorage.getItem('minipos_last_add_category_id') || '';
var LAST_ADD_UNIT_ID = localStorage.getItem('minipos_last_add_unit_id') || '';

function onAddCategoryChange() {
  var select = document.getElementById('add_category_id');
  var catId = select ? select.value : '';
  if (catId) {
    LAST_ADD_CATEGORY_ID = catId;
    try {
      localStorage.setItem('minipos_last_add_category_id', catId);
      localStorage.setItem('minipos_product_category_filter', catId);
    } catch(e){}
    var fullCode = catId + GLOBAL_NEXT_SEQ;
    $('#add_product_id_display').val(fullCode);
    $('#add_product_id').val(fullCode);
  } else {
    $('#add_product_id_display').val('');
    $('#add_product_id').val('');
  }
}

$(document).on('change', '#add_unit', function() {
  var uVal = $(this).val();
  if (uVal) {
    LAST_ADD_UNIT_ID = uVal;
    try { localStorage.setItem('minipos_last_add_unit_id', uVal); } catch(e){}
  }
});

$('#addProductModal').on('show.bs.modal', function() {
  $.getJSON('api_product.php?action=get_next_product_id', function(res) {
    if (res && res.success && res.seq_formatted) {
      GLOBAL_NEXT_SEQ = res.seq_formatted;

      // ບັງຄັບໃຫ້ຜູ້ໃຊ້ເລືອກໝວດໝູ່ໃໝ່ທຸກຄັ້ງທີ່ເປີດໜ້າເພີ່ມສິນຄ້າ
        $('#add_category_id').val('');
        
        if (LAST_ADD_UNIT_ID && $('#add_unit option[value="' + LAST_ADD_UNIT_ID + '"]').length > 0) {
          $('#add_unit').val(LAST_ADD_UNIT_ID);
        }
        onAddCategoryChange();
    }
  });
});

// ເພີ່ມແຖວຫົວໜ່ວຍເພີ່ມເຕີມ
function addUnitRow(mode, data) {
  var container = mode === 'add' ? $('#add_multi_units_container') : $('#edit_multi_units_container');
  var unitName   = data ? data.unit_name : '';
  var multiplier = data ? data.multiplier : '';
  var bprice     = data && data.bprice > 0 ? Number(data.bprice).toLocaleString('en-US') : '';
  var price      = data && data.price > 0 ? Number(data.price).toLocaleString('en-US') : '';
  var barcode    = data ? (data.barcode || '') : '';

  var rowHtml = `
    <div class="card bg-light border-0 mb-2 p-2 unit-row shadow-none" style="border-radius: 8px;">
      <div class="row align-items-center">
        <div class="col-md-3 col-6 mb-2 mb-md-0">
          <label class="small text-muted font-weight-bold mb-1">${I18N_ADD_PRODUCT['products.unit_row_name_label']} <span class="text-danger">*</span></label>
          <input type="text" name="extra_unit_name[]" class="form-control form-control-sm" placeholder="${I18N_ADD_PRODUCT['products.unit_row_name_placeholder']}" value="${unitName}" required>
        </div>
        <div class="col-md-2 col-6 mb-2 mb-md-0">
          <label class="small text-muted font-weight-bold mb-1">${I18N_ADD_PRODUCT['products.unit_row_qty_label']}</label>
          <input type="number" name="extra_unit_multiplier[]" class="form-control form-control-sm text-center" placeholder="1" min="1" value="${multiplier}">
        </div>
        <div class="col-md-2 col-6 mb-2 mb-md-0">
          <label class="small text-muted font-weight-bold mb-1">${I18N_ADD_PRODUCT['products.unit_row_bprice_label']}</label>
          <input type="text" name="extra_unit_bprice[]" class="form-control form-control-sm text-right price-input" placeholder="0" value="${bprice}" oninput="formatPriceInput(this)">
        </div>
        <div class="col-md-2 col-6 mb-2 mb-md-0">
          <label class="small text-muted font-weight-bold mb-1">${I18N_ADD_PRODUCT['products.unit_row_price_label']} </label>
<input type="text" name="extra_unit_price[]" class="form-control form-control-sm text-right font-weight-bold text-success price-input" placeholder="0" value="${price}" oninput="formatPriceInput(this)" >
        </div>
        <div class="col-md-2 col-10 mb-2 mb-md-0">
          <label class="small text-muted font-weight-bold mb-1">${I18N_ADD_PRODUCT['products.unit_row_barcode_label']}</label>
          <div class="input-group input-group-sm">
            <input type="text" name="extra_unit_barcode[]" class="form-control form-control-sm" placeholder="${I18N_ADD_PRODUCT['products.unit_row_barcode_placeholder']}" value="${barcode}" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
            <div class="input-group-append">
              <button type="button" class="btn btn-outline-primary" onclick="generateEAN13(this)" title="${I18N_ADD_PRODUCT['products.gen_barcode_title']}">
                <i class="fas fa-barcode"></i>
              </button>
            </div>
          </div>
        </div>
        <div class="col-md-1 col-2 text-right pt-md-3">
          <button type="button" class="btn btn-sm btn-outline-danger border-0" title="${I18N_ADD_PRODUCT['products.unit_row_delete_title']}" onclick="$(this).closest('.unit-row').remove();">
            <i class="fas fa-trash-alt"></i>
          </button>
        </div>
      </div>
    </div>
  `;
  container.append(rowHtml);
}

function submitAddProduct(e) {
  var catId   = $('#add_category_id').val();
  var name    = $('#add_product_name').val().trim();
  var barcode = $('#add_barcode').val().trim();
  var unit    = $('#add_unit').val().trim();
  var bprice  = $('#add_bprice').val().trim();
  var price   = $('#add_price').val().trim();

  if (!catId) {
    if (e) e.preventDefault();
    Swal.fire({
      icon: 'warning',
      title: I18N_ADD_PRODUCT['products.alert_notice_title'],
      text: I18N_ADD_PRODUCT['products.validate_category'],
      confirmButtonColor: '#2563eb',
      confirmButtonText: I18N_ADD_PRODUCT['products.btn_ok']
    }).then(function() {
      $('#add_category_id').focus();
    });
    return false;
  }
  if (name === '') {
    if (e) e.preventDefault();
    Swal.fire({
      icon: 'warning',
      title: I18N_ADD_PRODUCT['products.alert_notice_title'],
      text: I18N_ADD_PRODUCT['products.validate_name'],
      confirmButtonColor: '#2563eb',
      confirmButtonText: I18N_ADD_PRODUCT['products.btn_ok']
    }).then(function() {
      $('#add_product_name').focus();
    });
    return false;
  }
  // ບາໂຄ້ດ ບໍ່ບັງຄັບ — ຖ້າປ້ອນ ຕ້ອງເປັນຕົວເລກ; ວ່າງ = ບໍ່ມີບາໂຄ້ດ
  if (barcode !== '' && !/^[0-9]+$/.test(barcode)) {
    if (e) e.preventDefault();
    Swal.fire({
      icon: 'warning',
      title: I18N_ADD_PRODUCT['products.alert_notice_title'],
      text: I18N_ADD_PRODUCT['products.validate_barcode_numeric'],
      confirmButtonColor: '#2563eb',
      confirmButtonText: I18N_ADD_PRODUCT['products.btn_ok']
    }).then(function() {
      $('#add_barcode').focus();
    });
    return false;
  }
  if (unit === '') {
    if (e) e.preventDefault();
    Swal.fire({
      icon: 'warning',
      title: I18N_ADD_PRODUCT['products.alert_notice_title'],
      text: I18N_ADD_PRODUCT['products.validate_unit'],
      confirmButtonColor: '#2563eb',
      confirmButtonText: I18N_ADD_PRODUCT['products.btn_ok']
    }).then(function() {
      $('#add_unit').focus();
    });
    return false;
  }
  // ລາຄາຊື້ (bprice) ບໍ່ບັງຄັບ — ປ້ອນ ຫຼື ບໍ່ປ້ອນກໍໄດ້ (ວ່າງ = 0)
  // ລາຄາຂາຍບໍ່ບັງຄັບ

  // Dynamic multi-unit validation
  var validExtra = true;
  $('#add_multi_units_container .unit-row').each(function() {
    var uName  = $(this).find('input[name="extra_unit_name[]"]').val().trim();
    var uMult  = $(this).find('input[name="extra_unit_multiplier[]"]').val().trim();
    var uPrice = $(this).find('input[name="extra_unit_price[]"]').val().trim();

    if (uName === '') {
      if (e) e.preventDefault();
      var inputEl = $(this).find('input[name="extra_unit_name[]"]');
      Swal.fire({
        icon: 'warning',
        title: I18N_ADD_PRODUCT['products.alert_notice_title'],
        text: I18N_ADD_PRODUCT['products.validate_unit_name'],
        confirmButtonColor: '#2563eb',
        confirmButtonText: I18N_ADD_PRODUCT['products.btn_ok']
      }).then(function() {
        inputEl.focus();
      });
      validExtra = false;
      return false;
    }
    // uMult optional — blank/0 defaults to 1 (same quantity as the base unit) server-side.
    // uPrice optional
  });

  if (!validExtra) return false;

  // Check form internal duplicate barcodes
  var formBarcodes = [];
  var hasDuplicate = false;
  var dupValue = '';
  $('#addProductForm').find('#add_barcode, input[name="extra_unit_barcode[]"]').each(function() {
    var val = $(this).val().trim();
    if (val !== '') {
      if (formBarcodes.indexOf(val) !== -1) {
        hasDuplicate = true;
        dupValue = val;
        return false;
      }
      formBarcodes.push(val);
    }
  });

  if (hasDuplicate) {
    if (e) e.preventDefault();
    Swal.fire({
      icon: 'warning',
      title: I18N_ADD_PRODUCT['products.alert_duplicate_title'],
      text: I18N_ADD_PRODUCT['products.duplicate_in_form_text'].replace('{value}', dupValue),
      confirmButtonColor: '#2563eb',
      confirmButtonText: I18N_ADD_PRODUCT['products.btn_ok']
    });
    return false;
  }

  if (catId) {
    try {
      localStorage.setItem('minipos_product_category_filter', catId);
      localStorage.setItem('minipos_last_add_category_id', catId);
    } catch(e) {}
  }

  return true;
}

// Global Barcode Duplicate Checker Function
function checkBarcodeDuplicate(inputEl, productId) {
  var $input = $(inputEl);
  var barcode = $input.val().trim();
  if (!barcode) return;

  var $form = $input.closest('form');

  // 1. Check for duplicate within the same form
  var allBarcodes = [];
  $form.find('#add_barcode, #edit_barcode, input[name="extra_unit_barcode[]"]').each(function() {
    var val = $(this).val().trim();
    if (val !== '') {
      allBarcodes.push({ el: this, val: val });
    }
  });

  var duplicateCount = 0;
  for (var i = 0; i < allBarcodes.length; i++) {
    if (allBarcodes[i].val === barcode) {
      duplicateCount++;
    }
  }

  if (duplicateCount > 1) {
    Swal.fire({
      icon: 'warning',
      title: I18N_ADD_PRODUCT['products.alert_duplicate_title'],
      text: I18N_ADD_PRODUCT['products.duplicate_in_form_text'].replace('{value}', barcode),
      confirmButtonColor: '#2563eb',
      confirmButtonText: I18N_ADD_PRODUCT['products.btn_ok']
    }).then(function() {
      $input.val('').focus();
    });
    return false;
  }

  // 2. Check duplicate against Database via AJAX
  $.ajax({
    url: 'api_product.php',
    type: 'GET',
    dataType: 'json',
    data: {
      action: 'check_barcode',
      barcode: barcode,
      product_id: productId || 0
    },
    success: function(res) {
      if (res && res.exists) {
        Swal.fire({
          icon: 'warning',
          title: I18N_ADD_PRODUCT['products.alert_duplicate_title'],
          text: res.message || I18N_ADD_PRODUCT['products.duplicate_in_db_text'].replace('{value}', barcode),
          confirmButtonColor: '#2563eb',
          confirmButtonText: I18N_ADD_PRODUCT['products.btn_ok']
        }).then(function() {
          $input.val('').focus();
        });
      }
    }
  });
}

// Event Listeners for Add Product Barcode checking
$(document).on('change blur', '#add_barcode', function() {
  checkBarcodeDuplicate(this, 0);
});

$(document).on('change blur', '#addProductForm input[name="extra_unit_barcode[]"]', function() {
  checkBarcodeDuplicate(this, 0);
});

// Reset add modal on close (ລ້າງສະເພາະຂໍ້ມູນສິນຄ້າ, ຄົງໝວດໝູ່ ແລະ ຫົວໜ່ວຍຫຼ້າສຸດໄວ້ ເພື່ອໃຫ້ເພີ່ມສິນຄ້າໄວขึ้น)
$('#addProductModal').on('hidden.bs.modal', function() {
    // ບໍ່ບັນທຶກໝວດໝູ່ເກົ່າໄວ້ ເພື່ອໃຫ້ຜູ້ໃຊ້ຕ້ອງເລືອກໃໝ່ທຸກຄັ້ງ
    var savedUnit = $('#add_unit').val() || LAST_ADD_UNIT_ID;

    $('#add_product_name').val('');
    $('#add_barcode').val('');
    $('#add_bprice').val('');
    $('#add_price').val('');
    $('#add_qty').val('0');
    $('#add_supplier_id').val('');
    $('#add_category_id').val('');
    $('#add_multi_units_container').empty();
    removeProductImg('add');
    onAddCategoryChange(); // ອັບເດດລະຫັດສິນຄ້າໃຫ້ກາຍເປັນຄ່າວ່າງ

    if (savedUnit && $('#add_unit option[value="' + savedUnit + '"]').length > 0) {
      $('#add_unit').val(savedUnit);
    }
});

// Prevent Enter key in barcode inputs from prematurely submitting form
$(document).on('keydown', '#add_barcode, input[name="extra_unit_barcode[]"], #bulkAddProductForm input[name="b_barcode[]"]', function(e) {
  if (e.key === 'Enter' || e.keyCode === 13) {
    e.preventDefault();
    return false;
  }
});

/* ============================ BULK ADD (ເພີ່ມຫຼາຍລາຍການ) ============================ */

var __bulkT = <?php echo tjson([
    'products.bulk_col_cat'            => 'ໝວດໝູ່',
    'products.bulk_col_name'           => 'ຊື່ສິນຄ້າ',
    'products.bulk_col_barcode'        => 'ບາໂຄ້ດ',
    'products.bulk_col_unit'           => 'ຫົວໜ່ວຍ',
    'products.bulk_col_bprice'         => 'ລາຄາຊື້',
    'products.bulk_col_price'          => 'ລາຄາຂາຍ',
    'products.bulk_col_supplier'       => 'ຜູ້ສະໜອງ',
    'products.select_category_placeholder' => '-- ເລືອກປະເພດ --',
    'products.select_unit_placeholder' => '-- ເລືອກຫົວໜ່ວຍ --',
    'products.select_supplier_placeholder' => '-- ບໍ່ລະບຸ / ເລືອກຜູ້ສະໜອງ --',
    'products.product_name_placeholder' => 'ປ້ອນຊື່ສິນຄ້າ...',
    'products.barcode_placeholder'     => 'ສະແກນ ຫຼື ປ້ອນຕົວເລກ...',
    'products.gen_barcode_title'       => 'ສ້າງບາໂຄ້ດ 13 ຫຼັກ',
    'products.unit_row_delete_title'   => 'ລົບແຖວນີ້',
    'products.validate_name'           => 'ກະລຸນາປ້ອນຊື່ສິນຄ້າ!',
    'products.validate_category'       => 'ກະລຸນາເລືອກປະເພດສິນຄ້າ!',
    'products.validate_barcode_required' => 'ກະລຸນາປ້ອນ ຫຼື ສະແກນລະຫັດບາໂຄ້ດສິນຄ້າ!',
    'products.validate_barcode_numeric' => 'ລະຫັດບາໂຄ້ດຮອງຮັບສະເພາະຕົວເລກ (0-9) ເທົ່ານັ້ນ!',
    'products.validate_unit'           => 'ກະລຸນາເລືອກຫົວໜ່ວຍ!',
    'products.validate_bprice'         => 'ກະລຸນາປ້ອນລາຄາຊື້!',
    'products.validate_price'          => 'ກະລຸນາປ້ອນລາຄາຂາຍ!',
    'products.duplicate_in_form_text'  => 'ລະຫັດບາໂຄ້ດ "{value}" ນີ້ຊໍ້າກັນກັບແຖວອື່ນໃນຟອມດຽວກັນ!',
    'products.bulk_validate_none'      => 'ກະລຸນາປ້ອນຢ່າງໜ້ອຍ 1 ລາຍການ!',
]); ?>;

var I18N_BULK = {
  cat: __bulkT['products.bulk_col_cat'], cat_ph: __bulkT['products.select_category_placeholder'],
  name: __bulkT['products.bulk_col_name'], name_ph: __bulkT['products.product_name_placeholder'],
  barcode: __bulkT['products.bulk_col_barcode'], barcode_ph: __bulkT['products.barcode_placeholder'],
  unit: __bulkT['products.bulk_col_unit'], unit_ph: __bulkT['products.select_unit_placeholder'],
  bprice: __bulkT['products.bulk_col_bprice'], price: __bulkT['products.bulk_col_price'],
  supplier: __bulkT['products.bulk_col_supplier'], supplier_ph: __bulkT['products.select_supplier_placeholder'],
  gen: __bulkT['products.gen_barcode_title'], del: __bulkT['products.unit_row_delete_title'],
  e_name: __bulkT['products.validate_name'], e_cat: __bulkT['products.validate_category'],
  e_bc: __bulkT['products.validate_barcode_required'], e_bc_num: __bulkT['products.validate_barcode_numeric'],
  e_unit: __bulkT['products.validate_unit'], e_bp: __bulkT['products.validate_bprice'],
  e_pr: __bulkT['products.validate_price'], e_bc_dup: __bulkT['products.duplicate_in_form_text'],
  e_none: __bulkT['products.bulk_validate_none']
};

var BULK_CAT_OPTIONS = <?php echo json_encode(implode('', array_map(function ($c) {
    return '<option value="' . (int)$c['category_id'] . '">' . htmlspecialchars($c['category_name']) . '</option>';
}, $categories)), JSON_UNESCAPED_UNICODE); ?>;

var BULK_UNIT_OPTIONS = <?php echo json_encode(implode('', array_map(function ($u) {
    return '<option value="' . (int)$u['unit_id'] . '">' . htmlspecialchars($u['unit_name']) . '</option>';
}, $units)), JSON_UNESCAPED_UNICODE); ?>;

var BULK_SUPPLIER_OPTIONS = <?php echo json_encode(implode('', array_map(function ($sp) {
    return '<option value="' . (int)$sp['supplier_id'] . '">' . htmlspecialchars($sp['supplier_name']) . '</option>';
}, $suppliers ?? [])), JSON_UNESCAPED_UNICODE); ?>;

var BULK_DEFAULT_ROWS = 10;

function bulkRenumber() {
  var rows = $('#bulk_rows_container .bulk-row');
  rows.each(function (i) { $(this).find('.bulk-row-idx').text(i + 1); });
  $('#bulkRowCounter').text(rows.length);
}

function bulkAddRow(data) {
  data = data || {};
  var html = `
  <div class="card border mb-2 bulk-row" style="border-radius: 10px;">
    <div class="card-body p-2">
      <div class="row align-items-end" style="row-gap: 8px;">
        <div class="col-auto d-flex align-items-center" style="padding-bottom: 6px;">
          <span class="badge badge-light border font-weight-bold bulk-row-idx" style="font-size: 0.8rem;">0</span>
        </div>
        <div class="col-md col-6">
          <label class="small text-muted font-weight-bold mb-1">${I18N_BULK.cat} <span class="text-danger">*</span></label>
          <select name="b_category_id[]" class="form-control form-control-sm bulk-cat"><option value="">${I18N_BULK.cat_ph}</option>${BULK_CAT_OPTIONS}</select>
        </div>
        <div class="col-md col-6">
          <label class="small text-muted font-weight-bold mb-1">${I18N_BULK.name} <span class="text-danger">*</span></label>
          <input type="text" name="b_name[]" class="form-control form-control-sm bulk-name" placeholder="${I18N_BULK.name_ph}">
        </div>
        <div class="col-md col-8">
          <label class="small text-muted font-weight-bold mb-1">${I18N_BULK.barcode}</label>
          <div class="input-group input-group-sm">
            <input type="text" name="b_barcode[]" class="form-control form-control-sm bulk-barcode" placeholder="${I18N_BULK.barcode_ph}" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
            <div class="input-group-append">
              <button type="button" class="btn btn-outline-primary" title="${I18N_BULK.gen}" onclick="generateEAN13(this)"><i class="fas fa-barcode"></i></button>
            </div>
          </div>
        </div>
        <div class="col-md col-4">
          <label class="small text-muted font-weight-bold mb-1">${I18N_BULK.unit} <span class="text-danger">*</span></label>
          <select name="b_unit_id[]" class="form-control form-control-sm bulk-unit"><option value="">${I18N_BULK.unit_ph}</option>${BULK_UNIT_OPTIONS}</select>
        </div>
        <div class="col-md col-6">
          <label class="small text-muted font-weight-bold mb-1">${I18N_BULK.bprice}</label>
          <input type="text" name="b_bprice[]" class="form-control form-control-sm text-right price-input bulk-bprice" placeholder="0" oninput="formatPriceInput(this)">
        </div>
        <div class="col-md col-6">
          <label class="small text-muted font-weight-bold mb-1">${I18N_BULK.price} </label>
<input type="text" name="b_price[]" class="form-control form-control-sm text-right font-weight-bold text-success price-input bulk-price" placeholder="0" oninput="formatPriceInput(this)">
        </div>
        <div class="col-md col-8">
          <label class="small text-muted font-weight-bold mb-1">${I18N_BULK.supplier}</label>
          <select name="b_supplier_id[]" class="form-control form-control-sm bulk-supplier"><option value="">${I18N_BULK.supplier_ph}</option>${BULK_SUPPLIER_OPTIONS}</select>
        </div>
        <div class="col-auto text-right" style="padding-bottom: 4px;">
          <button type="button" class="btn btn-sm btn-outline-danger border-0 bulk-row-del" title="${I18N_BULK.del}"><i class="fas fa-trash-alt"></i></button>
        </div>
      </div>
    </div>
  </div>`;
  var $row = $(html);
  if (data.category_id) $row.find('.bulk-cat').val(String(data.category_id));
  if (data.unit_id) $row.find('.bulk-unit').val(String(data.unit_id));
  if (data.supplier_id) $row.find('.bulk-supplier').val(String(data.supplier_id));
  $('#bulk_rows_container').append($row);
  bulkRenumber();
}

// ເພີ່ມຫຼາຍແຖວພ້ອມກັນ (1 / 5 / 10)
function bulkAddRows(n) {
  n = parseInt(n, 10) || 1;
  for (var i = 0; i < n; i++) bulkAddRow();
  var $last = $('#bulk_rows_container .bulk-row').last();
  if ($last.length && $last[0].scrollIntoView) {
    $last[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
}

function bulkResetRows() {
  $('#bulk_rows_container').empty();
  for (var i = 0; i < BULK_DEFAULT_ROWS; i++) bulkAddRow();
}

// Delete a box — keep at least one (clear it instead of removing the last one)
$(document).on('click', '.bulk-row-del', function () {
  var $rows = $('#bulk_rows_container .bulk-row');
  if ($rows.length <= 1) {
    var $r = $(this).closest('.bulk-row');
    $r.find('input').val('');
    $r.find('select').prop('selectedIndex', 0);
  } else {
    $(this).closest('.bulk-row').remove();
  }
  bulkRenumber();
});

// Barcode duplicate check (within the bulk form, then against the DB)
$(document).on('blur', '#bulkAddProductForm input[name="b_barcode[]"]', function () {
  var $input = $(this), bc = $input.val().trim();
  if (!bc) return;
  var cnt = 0;
  $('#bulkAddProductForm input[name="b_barcode[]"]').each(function () {
    if ($(this).val().trim() === bc) cnt++;
  });
  if (cnt > 1) {
    Swal.fire({
      icon: 'warning', title: I18N_ADD_PRODUCT['products.alert_duplicate_title'],
      text: I18N_BULK.e_bc_dup.replace('{value}', bc),
      confirmButtonColor: '#2563eb', confirmButtonText: I18N_ADD_PRODUCT['products.btn_ok']
    }).then(function () { $input.val('').focus(); });
    return;
  }
  $.getJSON('api_product.php', { action: 'check_barcode', barcode: bc, product_id: 0 }, function (res) {
    if (res && res.exists) {
      Swal.fire({
        icon: 'warning', title: I18N_ADD_PRODUCT['products.alert_duplicate_title'],
        text: res.message || I18N_ADD_PRODUCT['products.duplicate_in_db_text'].replace('{value}', bc),
        confirmButtonColor: '#2563eb', confirmButtonText: I18N_ADD_PRODUCT['products.btn_ok']
      }).then(function () { $input.val('').focus(); });
    }
  });
});

function submitBulkAddProduct(e) {
  var validCount = 0;
  var seen = [];
  var err = null;

  $('#bulk_rows_container .bulk-row').each(function () {
    if (err) return false;
    var $r = $(this);
    var cat  = $r.find('.bulk-cat').val();
    var name = $r.find('.bulk-name').val().trim();
    var bc   = $r.find('.bulk-barcode').val().trim();
    var unit = $r.find('.bulk-unit').val();
    var bp   = $r.find('.bulk-bprice').val().trim();
    var pr   = $r.find('.bulk-price').val().trim();

    var isBlank = (!cat && !name && !bc && !unit && (bp === '' || bp === '0') && (pr === '' || pr === '0'));
    if (isBlank) return; // unused box — skip

    if (name === '')            { err = [$r.find('.bulk-name'), I18N_BULK.e_name]; return false; }
    if (!cat)                   { err = [$r.find('.bulk-cat'), I18N_BULK.e_cat]; return false; }
    // ບາໂຄ້ດ ບໍ່ບັງຄັບ — ກວດຮູບແບບ/ຊ້ຳ ສະເພາະເມື່ອປ້ອນມາ
    if (bc !== '') {
      if (!/^[0-9]+$/.test(bc))    { err = [$r.find('.bulk-barcode'), I18N_BULK.e_bc_num]; return false; }
      if (seen.indexOf(bc) !== -1) { err = [$r.find('.bulk-barcode'), I18N_BULK.e_bc_dup.replace('{value}', bc)]; return false; }
      seen.push(bc);
    }
    if (!unit)                  { err = [$r.find('.bulk-unit'), I18N_BULK.e_unit]; return false; }
    // ລາຄາຊື້ (bp) ບໍ່ບັງຄັບ — ປ້ອນ ຫຼື ບໍ່ປ້ອນກໍໄດ້ (ວ່າງ = 0)
    if (pr === '' || pr === '0') { err = [$r.find('.bulk-price'), I18N_BULK.e_pr]; return false; }
    validCount++;
  });

  if (err) {
    if (e) e.preventDefault();
    Swal.fire({
      icon: 'warning', title: I18N_ADD_PRODUCT['products.alert_notice_title'], text: err[1],
      confirmButtonColor: '#2563eb', confirmButtonText: I18N_ADD_PRODUCT['products.btn_ok']
    }).then(function () { err[0].focus(); });
    return false;
  }
  if (validCount === 0) {
    if (e) e.preventDefault();
    Swal.fire({
      icon: 'warning', title: I18N_ADD_PRODUCT['products.alert_notice_title'], text: I18N_BULK.e_none,
      confirmButtonColor: '#2563eb', confirmButtonText: I18N_ADD_PRODUCT['products.btn_ok']
    });
    return false;
  }
  return true;
}

// Seed 10 boxes the first time the modal opens, and re-seed on close
$('#addProductModal').on('show.bs.modal', function () {
  if ($('#bulk_rows_container .bulk-row').length === 0) bulkResetRows();
});
$('#addProductModal').on('hidden.bs.modal', function () {
  $('#tab-add-single').tab('show');
  bulkResetRows();
});
</script>
