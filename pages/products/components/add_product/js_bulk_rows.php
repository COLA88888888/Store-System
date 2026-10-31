<?php
/*
 * ສ່ວນ: JS: ແຖວຂອງເພີ່ມຫຼາຍລາຍການ
 * ສ້າງ/ລຶບ/ນັບແຖວ, ອັບໂຫຼດຮູບລາຍແຖວ, ແລະ ຟັງຊັນອ່ານໄຟລ໌ Excel (ໂຫຼດ SheetJS, ແປງຄ່າເຊວ).
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/products/components/form_add_product.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>/* ============================ BULK ADD (ເພີ່ມຫຼາຍລາຍການ) ============================ */

var __bulkT = <?php echo tjson([
    'products.bulk_col_cat'            => 'ໝວດໝູ່',
    'products.bulk_col_name'           => 'ຊື່ສິນຄ້າ',
    'products.bulk_col_barcode'        => 'ບາໂຄ້ດ',
    'products.bulk_col_unit'           => 'ຫົວໜ່ວຍ',
    'products.bulk_col_bprice'         => 'ລາຄາຊື້',
    'products.bulk_col_price'          => 'ລາຄາຂາຍ',
    'products.bulk_col_expiry'         => 'ວັນໝົດອາຍຸ',
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
    'products.bulk_col_img'            => 'ຮູບ',
    'products.bulk_img_hint'           => 'ກົດເພື່ອເລືອກຮູບ (ບໍ່ໃສ່ກໍໄດ້)',
    'products.bulk_img_fail'           => 'ອັບໂຫຼດຮູບບໍ່ສຳເລັດ',
    'products.bulk_img_busy'           => 'ກຳລັງອັບໂຫຼດຮູບ — ກະລຸນາລໍຖ້າບຶດໜຶ່ງ',
    'products.multi_unit_bulk_label'   => 'ຫົວໜ່ວຍເພີ່ມເຕີມ (ແພັກ, ແກັດ...):',
    'products.btn_add_unit_short'      => 'ເພີ່ມແພັກ/ແກັດ',
]); ?>;

var I18N_BULK = {
  cat: __bulkT['products.bulk_col_cat'], cat_ph: __bulkT['products.select_category_placeholder'],
  name: __bulkT['products.bulk_col_name'], name_ph: __bulkT['products.product_name_placeholder'],
  barcode: __bulkT['products.bulk_col_barcode'], barcode_ph: __bulkT['products.barcode_placeholder'],
  unit: __bulkT['products.bulk_col_unit'], unit_ph: __bulkT['products.select_unit_placeholder'],
  bprice: __bulkT['products.bulk_col_bprice'], price: __bulkT['products.bulk_col_price'],
  expiry: __bulkT['products.bulk_col_expiry'],
  supplier: __bulkT['products.bulk_col_supplier'], supplier_ph: __bulkT['products.select_supplier_placeholder'],
  gen: __bulkT['products.gen_barcode_title'], del: __bulkT['products.unit_row_delete_title'],
  e_name: __bulkT['products.validate_name'], e_cat: __bulkT['products.validate_category'],
  e_bc: __bulkT['products.validate_barcode_required'], e_bc_num: __bulkT['products.validate_barcode_numeric'],
  e_unit: __bulkT['products.validate_unit'], e_bp: __bulkT['products.validate_bprice'],
  e_pr: __bulkT['products.validate_price'], e_bc_dup: __bulkT['products.duplicate_in_form_text'],
  e_none: __bulkT['products.bulk_validate_none'],
  img: __bulkT['products.bulk_col_img'], img_ph: __bulkT['products.bulk_img_hint'],
  img_fail: __bulkT['products.bulk_img_fail'], img_busy: __bulkT['products.bulk_img_busy'],
  multi_unit: __bulkT['products.multi_unit_bulk_label'],
  btn_add_unit: __bulkT['products.btn_add_unit_short']
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

var BULK_ZONE_OPTIONS = <?php echo json_encode(implode('', array_map(function ($z) {
    return '<option value="' . htmlspecialchars($z['zone_name']) . '" data-id="' . (int)$z['zone_id'] . '">' . htmlspecialchars($z['zone_name']) . '</option>';
}, $zones ?? [])), JSON_UNESCAPED_UNICODE); ?>;

var BULK_SHELF_OPTIONS = <?php echo json_encode(implode('', array_map(function ($sh) {
    return '<option value="' . htmlspecialchars($sh['shelf_name']) . '" data-zone-id="' . (int)($sh['zone_id'] ?? 0) . '" data-zone-name="' . htmlspecialchars($sh['zone_name'] ?? '') . '">' . htmlspecialchars($sh['shelf_name']) . '</option>';
}, $shelves ?? [])), JSON_UNESCAPED_UNICODE); ?>;

var BULK_DEFAULT_ROWS = 10;

function bulkRenumber() {
  var rows = $('#bulk_rows_container .bulk-row');
  rows.each(function (i) { $(this).find('.bulk-row-idx').text(i + 1); });
  $('#bulkRowCounter').text(rows.length);
}

function bulkAddRow(data) {
  data = data || {};
  var hasZones = (typeof BULK_ZONE_OPTIONS !== 'undefined' && BULK_ZONE_OPTIONS.trim().length > 0);
  var hasShelves = (typeof BULK_SHELF_OPTIONS !== 'undefined' && BULK_SHELF_OPTIONS.trim().length > 0);
  var html = `
  <div class="card border mb-2.5 bulk-row shadow-xs" style="border-radius: 10px; background: #fff; border-color: #cbd5e1 !important;">
    <div class="card-body p-2.5">
      <div class="d-flex align-items-start" style="gap: 12px;">
        <!-- Left: Index + Photo -->
        <div class="d-flex flex-column align-items-center" style="gap: 6px; padding-top: 2px;">
          <span class="badge badge-light border font-weight-bold bulk-row-idx" style="font-size: 0.8rem; min-width: 26px;">0</span>
          <div class="bulk-img-box" title="${I18N_BULK.img_ph}">
            <input type="file" class="d-none bulk-img-file" accept="image/*">
            <input type="hidden" class="bulk-img" value="">
            <img class="bulk-img-preview" alt="" style="display:none;">
            <i class="fas fa-camera bulk-img-ico"></i>
            <span class="bulk-img-spin spinner-border spinner-border-sm text-primary" style="display:none;"></span>
            <button type="button" class="bulk-img-clear" title="${I18N_BULK.del}" style="display:none;">&times;</button>
          </div>
        </div>

        <!-- Right: 2 Rows -->
        <div class="flex-grow-1" style="min-width: 0;">
          <!-- Row 1: <?php echo tlj('ໝວດໝູ່, ຊື່ສິນຄ້າ, ບາໂຄ້ດ, ຫົວໜ່ວຍ'); ?> -->
          <div class="row align-items-end mb-2" style="row-gap: 8px;">
            <div class="col-lg-3 col-md-6 col-12">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="small text-muted font-weight-bold mb-0">${I18N_BULK.cat} <span class="text-danger">*</span></label>
                <a href="javascript:void(0)" onclick="quickAddCategoryPrompt('bulk', this)" class="text-primary font-weight-bold" style="font-size: 0.78rem;" title="${pqT('products_q.add_category_link')}">
                  <i class="fas fa-plus mr-1"></i>${pqT('products_q.add_category_btn') || 'ເພີ່ມໝວດ'}
                </a>
              </div>
              <select name="b_category_id[]" class="form-control form-control-sm bulk-cat" style="height: 38px; border-radius: 6px;"><option value="">${I18N_BULK.cat_ph}</option>${BULK_CAT_OPTIONS}</select>
            </div>
            <div class="col-lg-4 col-md-6 col-12">
              <label class="small text-muted font-weight-bold mb-1">${I18N_BULK.name} <span class="text-danger">*</span></label>
              <input type="text" name="b_name[]" class="form-control form-control-sm bulk-name" placeholder="${I18N_BULK.name_ph}" style="height: 38px; border-radius: 6px;">
            </div>
            <div class="col-lg-3 col-md-7 col-8">
              <label class="small text-muted font-weight-bold mb-1">${I18N_BULK.barcode}</label>
              <div class="input-group input-group-sm barcode-combo barcode-combo-sm">
                <input type="text" name="b_barcode[]" class="form-control form-control-sm bulk-barcode" placeholder="${I18N_BULK.barcode_ph}" oninput="this.value = this.value.replace(/[^0-9]/g, '')" style="height: 38px; border-radius: 6px 0 0 6px;">
                <div class="input-group-append">
                  <button type="button" class="btn btn-outline-primary font-weight-bold px-2" title="${I18N_BULK.gen}" onclick="generateEAN13(this)" style="height: 38px; border-radius: 0 6px 6px 0; display: inline-flex; align-items: center; gap: 3px;">
                    <i class="fas fa-barcode"></i> <span style="font-size: 0.8rem;">${pqT('products_q.gen_short')}</span>
                  </button>
                </div>
              </div>
            </div>
            <div class="col-lg-2 col-md-5 col-4">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="small text-muted font-weight-bold mb-0">${I18N_BULK.unit} <span class="text-danger">*</span></label>
                <a href="javascript:void(0)" onclick="quickAddUnitPrompt('bulk', this)" class="text-primary font-weight-bold" style="font-size: 0.78rem;" title="${pqT('products_q.add_unit_link')}">
                  <i class="fas fa-plus mr-1"></i>${pqT('products_q.add_unit_btn') || 'ເພີ່ມຫົວໜ່ວຍ'}
                </a>
              </div>
              <select name="b_unit_id[]" class="form-control form-control-sm bulk-unit" style="height: 38px; border-radius: 6px;"><option value="">${I18N_BULK.unit_ph}</option>${BULK_UNIT_OPTIONS}</select>
            </div>
          </div>

          <!-- Row 2: <?php echo tlj('ລາຄາຊື້, ລາຄາຂາຍ, ວັນໝົດອາຍຸ, ໂຊນ, ຊັ້ນວາງ, ປຸ່ມລົບ'); ?> -->
          <div class="row align-items-end" style="row-gap: 8px;">
            <div class="col-lg-2 col-md-4 col-6">
              <label class="small text-muted font-weight-bold mb-1">${I18N_BULK.bprice}</label>
              <input type="text" name="b_bprice[]" class="form-control form-control-sm text-right price-input bulk-bprice" placeholder="0" oninput="formatPriceInput(this)" style="height: 38px; border-radius: 6px;">
            </div>
            <div class="col-lg-2 col-md-4 col-6">
              <label class="small text-muted font-weight-bold mb-1">${I18N_BULK.price} <span class="text-danger">*</span></label>
              <input type="text" name="b_price[]" class="form-control form-control-sm text-right font-weight-bold text-success price-input bulk-price" placeholder="0" oninput="formatPriceInput(this)" style="height: 38px; border-radius: 6px;">
            </div>
            <!-- <?php echo tlj('ວັນໝົດອາຍຸ (ບໍ່ບັງຄັບ)'); ?> — <?php echo tlj('ປະຕິທິນຈາກ plugins/flatpickr/minipos-datepicker.'); ?>js -->
            <div class="col-lg-2 col-md-4 col-12">
              <label class="small text-muted font-weight-bold mb-1">${I18N_BULK.expiry}</label>
              <input type="date" name="b_expiry[]" class="form-control form-control-sm font-weight-bold bulk-expiry" style="height: 38px; border-radius: 6px;">
            </div>
            <div class="col-lg-2.5 col-md-5 col-5" style="flex: 1;">
              <label class="small text-muted font-weight-bold mb-1">${pqT('products_q.zone')}</label>
              <select name="b_zone[]" class="form-control form-control-sm bulk-zone" style="height: 38px; border-radius: 6px;" onchange="if(window.onBulkRowZoneChange) onBulkRowZoneChange(this)" ${!hasZones ? 'disabled' : ''}>
                <option value="">${hasZones ? pqT('products_q.zone_opt') : (pqT('products_q.no_zone_short') || '-- ບໍ່ມີໂຊນ --')}</option>
                ${BULK_ZONE_OPTIONS}
              </select>
            </div>
            <div class="col-lg-2.5 col-md-5 col-5" style="flex: 1;">
              <label class="small text-muted font-weight-bold mb-1">${pqT('products_q.shelf')}</label>
              <select name="b_shelf[]" class="form-control form-control-sm bulk-shelf" style="height: 38px; border-radius: 6px;" ${!hasShelves ? 'disabled' : ''}>
                <option value="">${hasShelves ? pqT('products_q.shelf_opt') : (pqT('products_q.no_shelf_short') || '-- ບໍ່ມີຊັ້ນວາງ --')}</option>
                ${BULK_SHELF_OPTIONS}
              </select>
            </div>
            <div class="col-auto text-right pl-1">
              <button type="button" class="btn btn-outline-danger bulk-row-del" title="${I18N_BULK.del}" style="height: 38px; width: 38px; border-radius: 6px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                <i class="fas fa-trash-alt"></i>
              </button>
            </div>
          </div>

          <!-- Row 3: ຫົວໜ່ວຍເພີ່ມເຕີມ (ແພັກ, ແກັດ...) -->
          <div class="bulk-extra-units-wrap mt-2 pt-2 border-top" style="border-color: #f1f5f9 !important;">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <span class="text-muted font-weight-bold" style="font-size: 0.78rem;">
                <i class="fas fa-boxes text-info mr-1"></i> ${I18N_BULK.multi_unit}
              </span>
              <button type="button" class="btn btn-xs btn-outline-info font-weight-bold px-2 py-0.5" style="border-radius: 5px; font-size: 0.76rem;" onclick="bulkAddUnitSubRow(this)">
                <i class="fas fa-plus mr-1"></i> ${I18N_BULK.btn_add_unit}
              </button>
            </div>
            <div class="bulk-extra-units-container"></div>
          </div>
        </div>
      </div>
    </div>
  </div>`;
  var $row = $(html);
  if (data.category_id) $row.find('.bulk-cat').val(String(data.category_id));
  if (data.unit_id) $row.find('.bulk-unit').val(String(data.unit_id));
  if (data.zone) {
    $row.find('.bulk-zone').prop('disabled', false).removeClass('select-disabled').val(String(data.zone));
    if (window.onBulkRowZoneChange) window.onBulkRowZoneChange($row.find('.bulk-zone')[0]);
  } else {
    if (window.onBulkRowZoneChange) window.onBulkRowZoneChange($row.find('.bulk-zone')[0]);
  }
  if (data.shelf) {
    $row.find('.bulk-shelf').prop('disabled', false).removeClass('select-disabled').val(String(data.shelf));
  }
  if (data.extra_units && Array.isArray(data.extra_units)) {
    data.extra_units.forEach(function (eu) {
      bulkAddUnitSubRow($row.find('.bulk-extra-units-wrap button'), eu);
    });
  }
  // Per-row supplier only comes from an Excel import; the batch box at the top wins when set.
  $row.data('supplier', data.supplier_id ? String(data.supplier_id) : '');
  $('#bulk_rows_container').append($row);
  bulkRenumber();
}

// ====== ເພີ່ມຫົວໜ່ວຍຍ່ອຍ (ແພັກ, ແກັດ...) ພາຍໃນແຖວເພີ່ມຫຼາຍລາຍການ ======
window.bulkAddUnitSubRow = function(btn, subData) {
  subData = subData || {};
  var $card = $(btn).closest('.bulk-row');
  var $container = $card.find('.bulk-extra-units-container');

  var subUnitName = subData.unit_name || '';
  var subMult     = subData.multiplier || '';
  var subBprice   = subData.bprice || '';
  var subPrice    = subData.price || '';
  var subBarcode  = subData.barcode || '';

  var existingCount = $container.find('.bulk-sub-unit-row').length;
  var defaultSuggest = '';
  if (!subUnitName) {
    if (existingCount === 0) defaultSuggest = 'ແພັກ';
    else if (existingCount === 1) defaultSuggest = 'ແກັດ';
  }

  var subUnitOpts = '<option value="">' + (I18N_ADD_PRODUCT['products.unit_row_name_placeholder'] || '-- ເລືອກຫົວໜ່ວຍ --') + '</option>';
  var matched = false;
  (window.ALL_UNITS || []).forEach(function(u) {
    var uName = (u.unit_name || '').trim();
    if (!uName) return;
    var isSel = (subUnitName && subUnitName.trim() === uName) || (!subUnitName && defaultSuggest && uName === defaultSuggest);
    if (isSel) matched = true;
    subUnitOpts += '<option value="' + escapeHtml(uName) + '" ' + (isSel ? 'selected' : '') + '>' + escapeHtml(uName) + '</option>';
  });
  if (subUnitName && !matched) {
    subUnitOpts = '<option value="' + escapeHtml(subUnitName) + '" selected>' + escapeHtml(subUnitName) + '</option>' + subUnitOpts;
  }

  if (!subMult) {
    if (defaultSuggest === 'ແພັກ') subMult = '6';
    else if (defaultSuggest === 'ແກັດ') subMult = '24';
  }

  var subHtml = `
  <div class="bulk-sub-unit-row p-2 mb-2 rounded border" style="background: #f8fafc; border-color: #cbd5e1 !important;">
    <div class="row align-items-end" style="row-gap: 6px;">
      <div class="col-lg-2 col-md-4 col-6">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <label class="small text-muted font-weight-bold mb-0" style="font-size: 0.75rem;">
            ${I18N_ADD_PRODUCT['products.unit_row_name_label'] || 'ຊື່ຫົວໜ່ວຍ'} <span class="text-danger">*</span>
          </label>
          <a href="javascript:void(0)" onclick="quickAddUnitPrompt('bulk_sub', this)" class="text-primary font-weight-bold" style="font-size: 0.75rem;" title="${pqT('products_q.add_unit_link')}">
            <i class="fas fa-plus mr-1"></i>${pqT('products_q.add_unit_btn') || 'ເພີ່ມຫົວໜ່ວຍ'}
          </a>
        </div>
        <select class="form-control form-control-sm bulk-sub-unit-name font-weight-bold text-dark" style="height: 34px; border-radius: 5px; font-size: 0.82rem;">
          ${subUnitOpts}
        </select>
      </div>
      <div class="col-lg-2 col-md-4 col-6">
        <label class="small text-muted font-weight-bold mb-1" style="font-size: 0.75rem;">
          ${I18N_ADD_PRODUCT['products.unit_row_qty_label'] || 'ຈຳນວນຕັດສະຕັອກ'} <span class="text-danger">*</span>
        </label>
        <input type="text" inputmode="numeric" class="form-control form-control-sm text-center font-weight-bold bulk-sub-unit-mult text-primary no-spin" placeholder="1" value="${escapeHtml(subMult)}" style="height: 34px; border-radius: 5px; font-size: 0.85rem;" oninput="this.value = this.value.replace(/[^0-9]/g, '');">
      </div>
      <div class="col-lg-2 col-md-4 col-6">
        <label class="small text-muted font-weight-bold mb-1" style="font-size: 0.75rem;">
          ${I18N_ADD_PRODUCT['products.unit_row_bprice_label'] || 'ລາຄາຊື້'}
        </label>
        <input type="text" class="form-control form-control-sm text-right price-input bulk-sub-unit-bprice" placeholder="0" value="${escapeHtml(subBprice)}" style="height: 34px; border-radius: 5px; font-size: 0.85rem; color: #475569;" oninput="formatPriceInput(this)">
      </div>
      <div class="col-lg-2 col-md-4 col-6">
        <label class="small text-muted font-weight-bold mb-1" style="font-size: 0.75rem;">
          ${I18N_ADD_PRODUCT['products.unit_row_price_label'] || 'ລາຄາຂາຍ'} <span class="text-danger">*</span>
        </label>
        <input type="text" class="form-control form-control-sm text-right font-weight-bold text-success price-input bulk-sub-unit-price" placeholder="0" value="${escapeHtml(subPrice)}" style="height: 34px; border-radius: 5px; font-size: 0.85rem;" oninput="formatPriceInput(this)">
      </div>
      <div class="col-lg-3 col-md-6 col-10" style="flex: 1;">
        <label class="small text-muted font-weight-bold mb-1" style="font-size: 0.75rem;">
          ${I18N_ADD_PRODUCT['products.unit_row_barcode_label'] || 'ບາໂຄ້ດ'}
        </label>
        <div class="input-group input-group-sm barcode-combo barcode-combo-sm">
          <input type="text" class="form-control form-control-sm bulk-sub-unit-barcode font-weight-bold" placeholder="${I18N_BULK.barcode_ph}" value="${escapeHtml(subBarcode)}" style="height: 34px; border-radius: 5px 0 0 5px; font-size: 0.85rem;" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
          <div class="input-group-append">
            <button type="button" class="btn btn-outline-primary font-weight-bold px-2" title="${I18N_BULK.gen}" onclick="generateEAN13(this)" style="height: 34px; border-radius: 0 5px 5px 0; display: inline-flex; align-items: center; gap: 3px;">
              <i class="fas fa-barcode"></i> <span style="font-size: 0.75rem;">${pqT('products_q.gen_short')}</span>
            </button>
          </div>
        </div>
      </div>
      <div class="col-auto text-right pl-1">
        <button type="button" class="btn btn-outline-danger btn-xs" title="${I18N_BULK.del}" onclick="$(this).closest('.bulk-sub-unit-row').remove()" style="height: 34px; width: 34px; border-radius: 5px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>
  </div>`;

  $container.append(subHtml);
}

// ====== ຮູບພາບລາຍແຖວ (ບໍ່ບັງຄັບ) — ອັບໂຫຼດທັນທີທີ່ເລືອກ, ເກັບໄວ້ແຕ່ຊື່ໄຟລ໌ ======
function bulkClearImg($box) {
  $box.find('.bulk-img').val('');
  $box.find('.bulk-img-file').val('');
  $box.find('.bulk-img-preview').hide().attr('src', '');
  $box.find('.bulk-img-clear, .bulk-img-spin').hide();
  $box.find('.bulk-img-ico').show();
  $box.removeClass('has-img');
}

$(document).on('click', '#bulk_rows_container .bulk-img-box', function (e) {
  if ($(e.target).closest('.bulk-img-clear, .bulk-img-file').length) return;
  if ($(this).data('busy')) return;
  $(this).find('.bulk-img-file')[0].click();
});

$(document).on('click', '#bulk_rows_container .bulk-img-clear', function (e) {
  e.stopPropagation();
  bulkClearImg($(this).closest('.bulk-img-box'));
});

$(document).on('change', '#bulk_rows_container .bulk-img-file', function () {
  var file = this.files && this.files[0];
  var $box = $(this).closest('.bulk-img-box');
  if (!file) return;
  var fd = new FormData();
  fd.append('action', 'upload_image');
  fd.append('image', file);
  $box.data('busy', true);
  $box.find('.bulk-img-ico').hide();
  $box.find('.bulk-img-spin').show();
  $.ajax({ url: '../../api/products_api.php', type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json' })
    .done(function (res) {
      if (res && res.success) {
        $box.find('.bulk-img').val(res.file);
        $box.find('.bulk-img-preview').attr('src', res.url).show();
        $box.find('.bulk-img-clear').show();
        $box.addClass('has-img');
      } else {
        bulkClearImg($box);
        Swal.fire({ icon: 'warning', title: I18N_ADD_PRODUCT['products.alert_notice_title'], text: (res && res.message) || I18N_BULK.img_fail, confirmButtonColor: '#2563eb' });
      }
    })
    .fail(function () {
      bulkClearImg($box);
      Swal.fire({ icon: 'error', title: I18N_ADD_PRODUCT['products.alert_notice_title'], text: I18N_BULK.img_fail, confirmButtonColor: '#2563eb' });
    })
    .always(function () {
      $box.data('busy', false);
      $box.find('.bulk-img-spin').hide();
    });
});

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

// ====== ນຳເຂົ້າຈາກ Excel ======
// SheetJS (plugins/sheetjs) ໂຫຼດເມື່ອກົດປຸ່ມຄັ້ງທຳອິດເທົ່ານັ້ນ — ໄຟລ໌ໃຫຍ່ ~900KB, ບໍ່ຢາກໃຫ້ທຸກໜ້າຕ້ອງໂຫຼດ.
// ໄຟລ໌ຖືກອ່ານຢູ່ໃນ browser ແລ້ວຕື່ມລົງແຖວ bulk ດ້ານລຸ່ມ; ການບັນທຶກຍັງຜ່ານ add_products_bulk ຄືເກົ່າ.
var BULK_XLSX_URL = '<?php echo $base_path; ?>plugins/sheetjs/xlsx.full.min.js';

// ຊື່ຫົວຖັນທີ່ຮັບໄດ້ (ປຽບທຽບແບບຕັດຍະຫວ່າງ, * ແລະ ໂຕພິມໃຫຍ່ອອກ)
var BULK_EXCEL_HEADERS = {
  cat:      ['ປະເພດ', 'ປະເພດສິນຄ້າ', 'ໝວດໝູ່', 'ໝວດ', 'category'],
  name:     ['ຊື່ສິນຄ້າ', 'ຊື່', 'ລາຍການ', 'ລາຍການສິນຄ້າ', 'name', 'productname', 'product'],
  barcode:  ['ບາໂຄ້ດ', 'ບາໂຄດ', 'ລະຫັດບາໂຄ້ດ', 'ລະຫັດບາໂຄດ', 'barcode'],
  unit:     ['ຫົວໜ່ວຍ', 'ຫົວໜ່ວຍນັບ', 'unit'],
  bprice:   ['ລາຄາຊື້', 'ຕົ້ນທຶນ', 'ລາຄາຕົ້ນທຶນ', 'bprice', 'cost', 'buyprice'],
  price:    ['ລາຄາຂາຍ', 'ລາຄາ', 'price', 'sellprice'],
  supplier: ['ຜູ້ສະໜອງ', 'supplier'],
  zone:     ['ໂຊນ', 'zone'],
  shelf:    ['ຊັ້ນວາງ', 'ຊັ້ນ', 'shelf']
};
// ລຳດັບຖັນຂອງແບບຟອມ — ໃຊ້ເມື່ອໄຟລ໌ບໍ່ມີແຖວຫົວຖັນ
var BULK_EXCEL_ORDER = ['cat', 'name', 'barcode', 'unit', 'bprice', 'price', 'supplier', 'zone', 'shelf'];

// NFKC: "ຳ" ພິມໄດ້ 2 ແບບ (U+0EB3 ຫຼື ໍ+າ) — ໃຫ້ຖືວ່າເທົ່າກັນ ເວລາທຽບຊື່ປະເພດ/ຫົວໜ່ວຍ
function bulkNorm(s) {
  return String(s == null ? '' : s).normalize('NFKC').toLowerCase().replace(/[\s*_\-]+/g, '');
}

function bulkLoadXlsx(cb) {
  if (window.XLSX) return cb();
  Swal.fire({ title: pqT('products_q.loading'), allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
  $.ajax({ url: BULK_XLSX_URL, dataType: 'script', cache: true })
    .done(function () { Swal.close(); cb(); })
    .fail(function () {
      Swal.fire({ icon: 'error', title: I18N_ADD_PRODUCT['products.alert_notice_title'], text: pqT('products_q.excel_reader_failed'), confirmButtonColor: '#2563eb' });
    });
}

// ຊື່ -> id ຈາກ <option> ທີ່ມີຢູ່ແລ້ວ (ຮັບທັງຊື່ ແລະ ລະຫັດ id)
function bulkOptionMap(optionsHtml) {
  var map = {};
  $('<select>' + optionsHtml + '</select>').find('option').each(function () {
    map[bulkNorm($(this).text())] = this.value;
    map[bulkNorm(this.value)] = this.value;
  });
  return map;
}

function bulkCellText(v) {
  return v == null ? '' : String(v).trim();
}

// ບາໂຄ້ດທີ່ Excel ເກັບເປັນຕົວເລກ (8850999...) -> ສະຕຣິງຕົວເລກລ້ວນ
function bulkCellBarcode(v) {
  if (v == null || v === '') return '';
  if (typeof v === 'number') return Math.round(v).toLocaleString('fullwide', { useGrouping: false });
  return String(v).replace(/[^0-9]/g, '');
}

// "12,000", "12000 ກີບ", 12000.4 -> "12,000" (ຮູບແບບດຽວກັບ formatPriceInput)
function bulkCellPrice(v) {
  if (v == null || v === '') return '';
  var n = typeof v === 'number' ? v : parseFloat(String(v).replace(/[^0-9.]/g, ''));
  if (!isFinite(n) || n <= 0) return '';
  return Math.round(n).toLocaleString('en-US');
}

function bulkImportExcel(input) {
  var file = input.files && input.files[0];
  if (!file) return;
  bulkLoadXlsx(function () {
    var reader = new FileReader();
    reader.onload = function (ev) {
      var rows;
      try {
        var wb = XLSX.read(new Uint8Array(ev.target.result), { type: 'array' });
        var ws = wb.Sheets[wb.SheetNames[0]];
        rows = XLSX.utils.sheet_to_json(ws, { header: 1, raw: true, defval: '', blankrows: false });
      } catch (err) {
        Swal.fire({ icon: 'error', title: I18N_ADD_PRODUCT['products.alert_notice_title'], text: pqT('products_q.file_read_failed') + ' ' + err.message, confirmButtonColor: '#2563eb' });
        return;
      }
      bulkFillFromRows(rows);
    };
    reader.readAsArrayBuffer(file);
  });
}

