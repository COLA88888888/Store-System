<!-- Modal: Edit Product -->
<div class="modal fade" id="editProductModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 860px;">
    <div class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
      <form id="editProductForm" novalidate action="" method="POST" enctype="multipart/form-data" onsubmit="return submitEditProduct(event)">
        <input type="hidden" name="action" value="edit_product">
        <input type="hidden" name="product_id" id="edit_product_id">
        <input type="hidden" name="remove_img" id="edit_remove_img" value="0">

        <!-- HEADER -->
        <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, #2c5aa0, #244886) !important;">
          <h5 class="modal-title font-weight-bold" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif; font-size: 1.15rem;">
            <i class="fas fa-edit mr-2"></i> <?php echo htmlspecialchars(t('products.modal_edit_title', 'ແກ້ໄຂຂໍ້ມູນສິນຄ້າ')); ?>
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <!-- BODY -->
        <div class="modal-body p-4" style="max-height: 80vh; overflow-y: auto;">
          <div class="row align-items-center">

            <!-- LEFT: Product Image -->
            <div class="col-md-4 text-center border-right pr-md-4 py-2 d-flex flex-column align-items-center justify-content-center">
              <div class="position-relative my-2 d-inline-block" style="width: 140px; height: 140px;">
                <img id="edit_img_preview"
                     src="<?php echo $base_path; ?>assets/img/image.jpg"
                     class="shadow-sm"
                     style="width: 140px; height: 140px; object-fit: cover; border-radius: 14px; border: 2px dashed #006affff; background: #f8fafc; cursor: pointer;"
                     onclick="document.getElementById('edit_product_img').click();"
                     title="<?php echo htmlspecialchars(t('products.img_click_change_title', 'ຄລິກເພື່ອປ່ຽນຮູບພາບ')); ?>">

                <!-- Camera button -->
                <button type="button"
                        class="position-absolute d-flex align-items-center justify-content-center shadow-sm border-0"
                        style="right: -6px; bottom: -6px; width: 34px; height: 34px; background: #006affff; color: #fff; border-radius: 50%; border: 2px solid #fff; cursor: pointer; z-index: 2;"
                        onclick="document.getElementById('edit_product_img').click();"
                        title="<?php echo htmlspecialchars(t('products.img_select_new_btn_title', 'ເລືອກຮູບໃໝ່')); ?>">
                  <i class="fas fa-camera" style="font-size: 0.85rem;"></i>
                </button>

                <!-- Remove button (hidden by default unless product has image or new image selected) -->
                <button type="button" id="btn_remove_edit_img"
                        onclick="removeProductImg('edit');"
                        class="position-absolute align-items-center justify-content-center border-0 shadow-sm"
                        style="right: -6px; top: -6px; width: 30px; height: 30px; background: #ef4444; color: #fff; border-radius: 50%; border: 2px solid #fff; cursor: pointer; display: none; z-index: 3;"
                        title="<?php echo htmlspecialchars(t('products.img_remove_btn_title', 'ລົບຮູບ')); ?>">
                  <i class="fas fa-times" style="font-size: 0.82rem;"></i>
                </button>
              </div>

              <small class="text-muted mt-1 font-weight-bold" style="font-size: 0.78rem;"><?php echo htmlspecialchars(t('products.img_change_hint', 'ຄລິກເພື່ອປ່ຽນຮູບສິນຄ້າ')); ?></small>
              <input type="file" name="product_img" id="edit_product_img" accept="image/*" style="display: none;"
                     onchange="previewProductImg(this, 'edit_img_preview', 'btn_remove_edit_img')">
            </div>

            <!-- RIGHT: Base Form Fields -->
            <div class="col-md-8 pl-md-4 py-2">

              <!-- Row 1: ປະເພດສິນຄ້າ & ລະຫັດສິນຄ້າ (Readonly) -->
              <div class="row">
                <div class="col-md-7 mb-3">
                  <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('products.field_category', 'ປະເພດສິນຄ້າ:')); ?> <span class="text-danger">*</span></label>
                  <select name="category_id" id="edit_category_id" class="form-control" style="border-radius: 6px; height: 40px;" required>
                    <?php foreach ($categories as $cat): ?>
                      <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-5 mb-3">
                  <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('products.field_product_code', 'ລະຫັດສິນຄ້າ:')); ?></label>
                  <div id="edit_product_id_display" class="form-control bg-light d-flex align-items-center" style="border-radius: 6px; height: 40px; font-family: 'Courier New', Courier, monospace; font-size: 1.05rem; font-weight: bold; overflow: hidden; letter-spacing: 0.5px;"></div>
                </div>
              </div>

              <!-- Row 2: ຊື່ສິນຄ້າ & ບາໂຄ້ດຫຼັກ -->
              <div class="row">
                <div class="col-md-7 mb-3">
                  <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('products.field_product_name', 'ຊື່ສິນຄ້າ:')); ?> </label>
<input type="text" name="product_name" id="edit_product_name" class="form-control" placeholder="<?php echo htmlspecialchars(t('products.product_name_placeholder', 'ປ້ອນຊື່ສິນຄ້າ...')); ?>" style="border-radius: 6px; height: 40px;" required>
                </div>
                <div class="col-md-5 mb-3">
                  <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('products.field_barcode', 'ບາໂຄ້ດ:')); ?></label>
                  <div class="input-group barcode-combo">
                    <input type="text" name="barcode" id="edit_barcode" class="form-control" placeholder="<?php echo htmlspecialchars(t('products.barcode_edit_placeholder', 'ບາໂຄ້ດ...')); ?>" style="border-radius: 6px 0 0 6px; height: 40px;" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    <div class="input-group-append">
                      <button type="button" class="btn btn-outline-primary font-weight-bold" onclick="generateEAN13('edit_barcode')" title="<?php echo htmlspecialchars(t('products.gen_barcode_title', 'ສ້າງບາໂຄ້ດ 13 ຫຼັກ')); ?>" style="border-radius: 0 6px 6px 0; height: 40px; border-color: #cbd5e1;">
                        <i class="fas fa-barcode mr-1"></i> <?php echo htmlspecialchars(t('products.gen_barcode_btn', 'ສ້າງ')); ?>
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Row 3: ຫົວໜ່ວຍຍ່ອຍພື້ນຖານ & ຈຳນວນສະຕັອກ (ສະເພາະແອັດມິນ) -->
              <div class="row">
                <div class="<?php echo $isAdmin ? 'col-md-7' : 'col-md-12'; ?> mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="font-weight-bold text-dark mb-0"><?php echo htmlspecialchars(t('products.field_unit', 'ຫົວໜ່ວຍ:')); ?> <span class="text-danger">*</span></label>
                    <a href="javascript:void(0)" onclick="quickAddUnitPrompt('edit')" class="text-primary font-weight-bold" style="font-size: 0.78rem;">
                      <i class="fas fa-plus mr-1"></i><?php echo htmlspecialchars(t('products_q.add_unit_link')); ?>
                    </a>
                  </div>
                  <select name="unit_id" id="edit_unit" class="form-control" style="border-radius: 6px; height: 40px;" required>
                    <option value=""><?php echo htmlspecialchars(t('products.select_unit_placeholder', '-- ເລືອກຫົວໜ່ວຍ --')); ?></option>
                    <?php foreach ($units as $u): ?>
                      <option value="<?php echo (int)$u['unit_id']; ?>"><?php echo htmlspecialchars($u['unit_name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <?php if ($isAdmin): ?>
                  <div class="col-md-5 mb-3">
                    <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('products.field_qty', 'ຈຳນວນສະຕັອກ:')); ?></label>
                    <input type="number" name="qty" id="edit_qty" class="form-control text-center font-weight-bold text-primary" placeholder="0" min="0" style="border-radius: 6px; height: 40px;">
                  </div>
                <?php endif; ?>
              </div>

              <!-- Row 3b: ຜູ້ສະໜອງ & ວັນໝົດອາຍຸ (ທັງສອງບໍ່ບັງຄັບ) -->
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('products.field_supplier', 'ຜູ້ສະໜອງ:')); ?></label>
                  <select name="supplier_id" id="edit_supplier_id" class="form-control" style="border-radius: 6px; height: 40px;">
                    <option value=""><?php echo htmlspecialchars(t('products.select_supplier_placeholder', '-- ບໍ່ລະບຸ / ເລືອກຜູ້ສະໜອງ --')); ?></option>
                    <?php foreach (($suppliers ?? []) as $sp): ?>
                      <option value="<?php echo (int)$sp['supplier_id']; ?>"><?php echo htmlspecialchars($sp['supplier_name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('products.field_expiry_date', 'ວັນໝົດອາຍຸ:')); ?></label>
                  <input type="date" id="edit_expiry_date" name="expiry_date" class="form-control" style="height: 40px; border-radius: 6px;">
                  <input type="hidden" id="edit_expiry_store_id" name="expiry_store_id" value="">
                  <small class="text-muted"><?php echo htmlspecialchars(t('products.field_expiry_hint', 'ບໍ່ໃສ່ກໍໄດ້ — ໃສ່ແລ້ວລະບົບຈະແຈ້ງເຕືອນກ່ອນໝົດອາຍຸ')); ?></small>
                </div>
              </div>

              <!-- Row 3c: ໂຊນ & ຊັ້ນວາງ (ເລືອກຈາກຕາຕະລາງ, ຖ້າບໍ່ເລືອກໂຊນ ບັອກຊັ້ນວາງແມ່ນເຊື່ອງໄວ້) -->
              <div class="row">
                <div class="col-md-6 mb-3" id="edit_zone_box">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="font-weight-bold text-dark mb-0">
                      <?php echo htmlspecialchars(t('products.field_zone', 'ໂຊນ:')); ?>
                    </label>
                    <a href="javascript:void(0)" onclick="quickAddZonePrompt('edit')" class="text-primary font-weight-bold" style="font-size: 0.78rem;">
                      <i class="fas fa-plus mr-1"></i><?php echo htmlspecialchars(t('products_q.add_zone_link')); ?>
                    </a>
                  </div>
                  <select id="edit_zone" name="zone" class="form-control" style="border-radius: 6px; height: 40px;" onchange="onZoneSelectChange('edit')">
                    <option value=""><?php echo htmlspecialchars(t('products.select_zone_placeholder', '-- ບໍ່ລະບຸ / ເລືອກໂຊນ --')); ?></option>
                    <?php foreach (($zones ?? []) as $z): ?>
                      <option value="<?php echo htmlspecialchars($z['zone_name']); ?>" data-id="<?php echo (int)$z['zone_id']; ?>"><?php echo htmlspecialchars($z['zone_name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6 mb-3" id="edit_shelf_box">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="font-weight-bold text-dark mb-0">
                      <?php echo htmlspecialchars(t('products.field_shelf', 'ຊັ້ນວາງ:')); ?>
                    </label>
                    <a href="javascript:void(0)" onclick="quickAddShelfPrompt('edit')" class="text-info font-weight-bold" style="font-size: 0.78rem;">
                      <i class="fas fa-plus mr-1"></i><?php echo htmlspecialchars(t('products_q.add_shelf_link')); ?>
                    </a>
                  </div>
                  <select id="edit_shelf" name="shelf" class="form-control" style="border-radius: 6px; height: 40px;">
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
                    <input type="text" name="bprice" id="edit_bprice" class="form-control price-input text-right font-weight-bold" placeholder="0" style="border-radius: 6px 0 0 6px; height: 40px; color: #475569;" oninput="formatPriceInput(this)">
                    <div class="input-group-append"><span class="input-group-text" style="border-radius: 0 6px 6px 0; font-size: 0.85rem;">₭</span></div>
                  </div>
                </div>
                <div class="col-6">
                  <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('products.field_price_retail', 'ລາຄາຂາຍຍ່ອຍ')); ?> <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <input type="text" name="price" id="edit_price" class="form-control price-input text-right font-weight-bold" placeholder="0" style="border-radius: 6px 0 0 6px; height: 40px; color: #16a34a;" oninput="formatPriceInput(this)" >
                    <div class="input-group-append"><span class="input-group-text" style="border-radius: 0 6px 6px 0; font-size: 0.85rem;">₭</span></div>
                  </div>
                </div>
              </div>

              <!-- ຕັດສະຕັອກເມື່ອຂາຍ -->
              <div class="row mt-2">
                <div class="col-md-12">
                  <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="edit_cut_qty" name="cut_qty" value="1">
                    <label class="custom-control-label font-weight-bold text-dark" for="edit_cut_qty">
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
                  <i class="fas fa-tags text-warning mr-1"></i> <?php echo htmlspecialchars(t('products.multi_unit_title', 'ລາຄາ ແລະ ຫົວໜ່ວຍເພີ່ມເຕີມ (ຫຼາຍລາຄາ)')); ?>
                </span>
                <small class="text-muted d-block" style="font-size: 0.76rem;"><?php echo htmlspecialchars(t('products.multi_unit_hint', 'ຖ້າສິນຄ້ານີ້ມີຂາຍເປັນ ແພັກ (x6 ປ໋ອງ), ແກັດ (x24 ປ໋ອງ)... ສາມາດເພີ່ມໄດ້ທີ່ນີ້ (ຕັດສະຕັອກອັດຕະໂນມັດ)')); ?></small>
              </div>
              <button type="button" class="btn btn-sm btn-outline-warning font-weight-bold" style="border-radius: 6px; font-size: 0.82rem;" onclick="addUnitRow('edit')">
                <i class="fas fa-plus mr-1"></i> <?php echo htmlspecialchars(t('products.btn_add_unit', 'ເພີ່ມລາຄາ/ຫົວໜ່ວຍ')); ?>
              </button>
            </div>

            <div id="edit_multi_units_container">
              <!-- Dynamic Unit Rows loaded via AJAX -->
            </div>
          </div>

        </div>

        <!-- FOOTER -->
        <div class="modal-footer border-0 pt-0 pb-4 px-4">
          <button type="button" class="btn btn-light font-weight-bold px-4" style="border-radius: 6px; cursor: pointer;" data-dismiss="modal"><?php echo htmlspecialchars(t('products.cancel', 'ຍົກເລີກ')); ?></button>
          <button type="submit" class="btn btn-primary font-weight-bold px-4 shadow-sm text-white" style="border-radius: 6px; cursor: pointer; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
            <i class="fas fa-save mr-1"></i> <?php echo htmlspecialchars(t('products.btn_update', 'ອັບເດດ')); ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
var I18N_EDIT_PRODUCT = <?php echo tjson([
    'products.alert_notice_title' => 'ແຈ້ງເຕືອນ',
    'products.btn_ok' => 'ຕົກລົງ',
    'products.validate_category' => 'ກະລຸນາເລືອກປະເພດສິນຄ້າ!',
    'products.validate_name' => 'ກະລຸນາປ້ອນຊື່ສິນຄ້າ!',
    'products.validate_barcode_required' => 'ກະລຸນາປ້ອນ ຫຼື ສະແກນລະຫັດບາໂຄ້ດສິນຄ້າ!',
    'products.validate_barcode_numeric' => 'ລະຫັດບາໂຄ້ດຮອງຮັບສະເພາະຕົວເລກ (0-9) ເທົ່ານັ້ນ!',
    'products.validate_unit' => 'ກະລຸນາປ້ອນຫົວໜ່ວຍ!',
    'products.validate_bprice' => 'ກະລຸນາປ້ອນລາຄາຊື້!',
    'products.validate_price' => 'ກະລຸນາປ້ອນລາຄາຂາຍ!',
    'products.validate_unit_name' => 'ກະລຸນາປ້ອນຊື່ຫົວໜ່ວຍເພີ່ມເຕີມ!',
    'products.validate_unit_qty' => 'ກະລຸນາປ້ອນຈຳນວນຫົວໜ່ວຍເພີ່ມເຕີມ!',
    'products.validate_unit_price' => 'ກະລຸນາປ້ອນລາຄາຂາຍຫົວໜ່ວຍເພີ່ມເຕີມ!',
    'products.alert_duplicate_title' => 'ລະຫັດບາໂຄ້ດຊໍ້າກັນ!',
    'products.duplicate_in_form_text' => 'ລະຫັດບາໂຄ້ດ "{value}" ນີ້ຊໍ້າກັນກັບຫົວໜ່ວຍອື່ນໃນຟອມດຽວກັນ!',
]); ?>;

function submitEditProduct(e) {
  var catId   = $('#edit_category_id').val();
  var name    = $('#edit_product_name').val().trim();
  var barcode = $('#edit_barcode').val().trim();
  var unit    = $('#edit_unit').val().trim();
  var bprice  = $('#edit_bprice').val().trim();
  var price   = $('#edit_price').val().trim();

  if (!catId) {
    if (e) e.preventDefault();
    Swal.fire({
      icon: 'warning',
      title: I18N_EDIT_PRODUCT['products.alert_notice_title'],
      text: I18N_EDIT_PRODUCT['products.validate_category'],
      confirmButtonColor: '#2563eb',
      confirmButtonText: I18N_EDIT_PRODUCT['products.btn_ok']
    }).then(function() {
      $('#edit_category_id').focus();
    });
    return false;
  }
  if (name === '') {
    if (e) e.preventDefault();
    Swal.fire({
      icon: 'warning',
      title: I18N_EDIT_PRODUCT['products.alert_notice_title'],
      text: I18N_EDIT_PRODUCT['products.validate_name'],
      confirmButtonColor: '#2563eb',
      confirmButtonText: I18N_EDIT_PRODUCT['products.btn_ok']
    }).then(function() {
      $('#edit_product_name').focus();
    });
    return false;
  }
  // ບາໂຄ້ດ ບໍ່ບັງຄັບ — ຖ້າປ້ອນ ຕ້ອງເປັນຕົວເລກ; ວ່າງ = ບໍ່ມີບາໂຄ້ດ
  if (barcode !== '' && !/^[0-9]+$/.test(barcode)) {
    if (e) e.preventDefault();
    Swal.fire({
      icon: 'warning',
      title: I18N_EDIT_PRODUCT['products.alert_notice_title'],
      text: I18N_EDIT_PRODUCT['products.validate_barcode_numeric'],
      confirmButtonColor: '#2563eb',
      confirmButtonText: I18N_EDIT_PRODUCT['products.btn_ok']
    }).then(function() {
      $('#edit_barcode').focus();
    });
    return false;
  }
  if (unit === '') {
    if (e) e.preventDefault();
    Swal.fire({
      icon: 'warning',
      title: I18N_EDIT_PRODUCT['products.alert_notice_title'],
      text: I18N_EDIT_PRODUCT['products.validate_unit'],
      confirmButtonColor: '#2563eb',
      confirmButtonText: I18N_EDIT_PRODUCT['products.btn_ok']
    }).then(function() {
      $('#edit_unit').focus();
    });
    return false;
  }
  // ລາຄາຊື້ (bprice) ບໍ່ບັງຄັບ — ປ້ອນ ຫຼື ບໍ່ປ້ອນກໍໄດ້ (ວ່າງ = 0)
  // ລາຄາຂາຍບໍ່ບັງຄັບ

  // Dynamic multi-unit validation
  var validExtra = true;
  $('#edit_multi_units_container .unit-row').each(function() {
    var uName  = ($(this).find('[name="extra_unit_name[]"]').val() || '').trim();
    var uMult  = ($(this).find('[name="extra_unit_multiplier[]"]').val() || '').trim();
    var uPrice = ($(this).find('[name="extra_unit_price[]"]').val() || '').trim();

    if (uName === '') {
      if (e) e.preventDefault();
      var inputEl = $(this).find('[name="extra_unit_name[]"]');
      Swal.fire({
        icon: 'warning',
        title: I18N_EDIT_PRODUCT['products.alert_notice_title'],
        text: I18N_EDIT_PRODUCT['products.validate_unit_name'],
        confirmButtonColor: '#2563eb',
        confirmButtonText: I18N_EDIT_PRODUCT['products.btn_ok']
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
  $('#editProductForm').find('#edit_barcode, input[name="extra_unit_barcode[]"]').each(function() {
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
      title: I18N_EDIT_PRODUCT['products.alert_duplicate_title'],
      text: I18N_EDIT_PRODUCT['products.duplicate_in_form_text'].replace('{value}', dupValue),
      confirmButtonColor: '#2563eb',
      confirmButtonText: I18N_EDIT_PRODUCT['products.btn_ok']
    });
    return false;
  }

  if (catId) {
    try {
      localStorage.setItem('StoreMini.minipos_product_category_filter', catId);
    } catch(e) {}
  }

  return true;
}

// Event Listeners for Edit Product Barcode checking
$(document).on('change blur', '#edit_barcode', function() {
  var prodId = $('#edit_product_id').val();
  checkBarcodeDuplicate(this, prodId);
});

$(document).on('change blur', '#editProductForm input[name="extra_unit_barcode[]"]', function() {
  var prodId = $('#edit_product_id').val();
  checkBarcodeDuplicate(this, prodId);
});

// Prevent Enter key in barcode inputs from prematurely submitting form
$(document).on('keydown', '#edit_barcode, input[name="extra_unit_barcode[]"]', function(e) {
  if (e.key === 'Enter' || e.keyCode === 13) {
    e.preventDefault();
    return false;
  }
});
</script>
