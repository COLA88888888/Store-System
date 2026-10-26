<?php
/*
 * ສ່ວນ: JS: ເພີ່ມທີລະລາຍການ
 * ເປີດ <script>: ລະຫັດສິນຄ້າ preview, ຈື່ປະເພດ/ຫົວໜ່ວຍລ່າສຸດ, ແຖວຫົວໜ່ວຍເພີ່ມເຕີມ, ກວດ ແລະ
 * ສົ່ງຟອມ, ກວດບາໂຄ້ດຊ້ຳ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/products/components/form_add_product.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?><script>
// ຂໍ້ຄວາມເພີ່ມດ່ວນ ຫົວໜ່ວຍ/ໂຊນ/ຊັ້ນວາງ (lang/*/products_q.php) — ເປັນ window.I18N_* ຈຶ່ງປ່ຽນພາສາເອງເມື່ອກົດທຸງ
// (home/partials/dashboard/js_language.php); popup ອ່ານຜ່ານ pqT() ຕອນເປີດ ຈຶ່ງບໍ່ຕ້ອງໂຫຼດໜ້າ.
window.I18N_PRODUCTS_Q = Object.assign(window.I18N_PRODUCTS_Q || {}, <?php echo tjson(require dirname(__DIR__, 4) . '/lang/lo/products_q.php'); ?>);
if (typeof window.pqT !== 'function') window.pqT = function (key) { var d = window.I18N_PRODUCTS_Q || {}; return d[key] || key; };
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
    'products.unit_row_qty_label' => 'ຈຳນວນທີ່ຕັດສະຕັອກ:',
    'products.unit_row_qty_placeholder' => '0',
    'products.unit_row_bprice_label' => 'ລາຄາຊື້:',
    'products.unit_row_price_label' => 'ລາຄາຂາຍ:',
    'products.unit_row_barcode_label' => 'ບາໂຄ້ດ:',
    'products.unit_row_barcode_placeholder' => 'ບາໂຄ້ດ...',
    'products.gen_barcode_title' => 'ສ້າງບາໂຄ້ດ 13 ຫຼັກ',
    'products.unit_row_delete_title' => 'ລົບແຖວນີ້',
]); ?>;

// ດຶງລຳດັບການປ້ອນສິນຄ້າເພີ່ມເຂົ້າທົ່ວລະບົບ (Sequence: 0001, 0002, 0003...)
var GLOBAL_NEXT_SEQ = '<?php echo htmlspecialchars($nextSeqFormatted ?? "0001"); ?>';
var GLOBAL_CODE_PREFIX = '<?php echo htmlspecialchars(getProductCodePrefix($pdo, getActiveStoreId($pdo))); ?>';
var LAST_ADD_CATEGORY_ID = localStorage.getItem('StoreMini.minipos_last_add_category_id') || '';
// ຫົວໜ່ວຍ: ບໍ່ຈື່ຄ່າທີ່ເລືອກຄັ້ງກ່ອນ — ຟອມເປີດມາວ່າງ "-- ເລືອກຫົວໜ່ວຍ --" ທຸກເທື່ອ (ລຶບຄ່າເກົ່າທີ່ເຄີຍເກັບໄວ້)
try { localStorage.removeItem('StoreMini.minipos_last_add_unit_id'); } catch (e) {}

function onAddCategoryChange() {
  var select = document.getElementById('add_category_id');
  var catId = select ? select.value : '';
  if (catId) {
    LAST_ADD_CATEGORY_ID = catId;
    try {
      localStorage.setItem('StoreMini.minipos_last_add_category_id', catId);
      localStorage.setItem('StoreMini.minipos_product_category_filter', catId);
    } catch(e){}

    // ດຶງລະຫັດສິນຄ້າຖັດໄປຕາມສາຂາ ແລະ ປະເພດສິນຄ້າ (e.g. 1 (ສາຂາ) + 1 (ປະເພດ) + 0001 (ລຳດັບ) = 110001)
    $.getJSON('../../api/products_api.php?action=get_next_product_id&category_id=' + encodeURIComponent(catId), function(res) {
      if (res && res.success) {
        var fullCode = String(res.next_id);
        var prefix = String(res.prefix !== undefined ? res.prefix : GLOBAL_CODE_PREFIX);
        var seqFormatted = String(res.seq_formatted || fullCode.slice(-4));
        var catPart = String(catId);

        $('#add_product_id').val(fullCode);
        $('#add_product_id_display').html(
          '<span style="color: #dc2626;" title="' + pqT('products_q.code_branch') + '">' + prefix + '</span>' +
          '<span style="color: #2563eb;" title="' + pqT('products_q.code_category') + '">' + catPart + '</span>' +
          '<span style="color: #0f172a;" title="' + pqT('products_q.code_product') + '">' + seqFormatted + '</span>'
        );
      }
    });
  } else {
    $('#add_product_id').val('');
    $('#add_product_id_display').html('<span class="text-muted" style="font-family: inherit; font-size: 0.88rem; font-weight: normal;"><?php echo htmlspecialchars(t('products.product_code_placeholder', 'ເລືອກປະເພດສິນຄ້າ...')); ?></span>');
  }
}

$('#addProductModal').on('show.bs.modal', function() {
  // ຖ້າຍັງບໍ່ມີລະຫັດສິນຄ້າ (ຍັງບໍ່ໄດ້ເລືອກປະເພດ) ໃຫ້ສະແດງຂໍ້ຄວາມແນະນຳ
  if (!$('#add_product_id').val()) {
    $('#add_product_id_display').html('<span class="text-muted" style="font-family: inherit; font-size: 0.88rem; font-weight: normal;"><?php echo htmlspecialchars(t('products.product_code_placeholder', 'ເລືອກປະເພດສິນຄ້າ...')); ?></span>');
  }
});

// Escape HTML string
function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

window.ALL_UNITS = <?php echo json_encode($units ?? [], JSON_UNESCAPED_UNICODE); ?>;

// ເພີ່ມຫົວໜ່ວຍໃໝ່ແບບດ່ວນ
function quickAddUnitPrompt(mode, btn) {
  Swal.fire({
    title: pqT('products_q.unit_title'),
    input: 'text',
    inputPlaceholder: pqT('products_q.unit_ph'),
    showCancelButton: true,
    confirmButtonText: pqT('products_q.save'),
    cancelButtonText: pqT('products_q.cancel'),
    confirmButtonColor: '#2563eb',
    inputValidator: function(v) {
      if (!v || !v.trim()) return pqT('products_q.unit_required');
    }
  }).then(function(res) {
    if (res.isConfirmed && res.value) {
      var name = res.value.trim();
      $.post('../../api/products_api.php', { action: 'add_unit', unit_name: name }, function(data) {
        if (data && data.success) {
          if (!window.ALL_UNITS) window.ALL_UNITS = [];
          var exists = window.ALL_UNITS.some(function(u) { return (u.unit_name || '').trim() === name; });
          if (!exists) {
            window.ALL_UNITS.push({ unit_id: data.unit_id, unit_name: name });
          }

          // ອັບເດດ dropdown ຫົວໜ່ວຍຫຼັກ (#add_unit ແລະ #edit_unit)
          ['add', 'edit'].forEach(function(m) {
            var $sel = $('#' + m + '_unit');
            if ($sel.length && !$sel.find('option[value="' + data.unit_id + '"]').length) {
              $sel.append($('<option>', { value: data.unit_id, text: name }));
            }
          });

          // ອັບເດດ dropdown ຫົວໜ່ວຍເພີ່ມເຕີມທຸກແຖວ
          $('.extra-unit-select').each(function() {
            var $s = $(this);
            if (!$s.find('option[value="' + name + '"]').length) {
              $s.append($('<option>', { value: name, text: name }));
            }
          });

          // ຖ້າກົດເພີ່ມຈາກແຖວໃດ ໃຫ້ເລືອກຫົວໜ່ວຍໃໝ່ນັ້ນໃນແຖວນັ້ນທັນທີ
          if (btn && $(btn).closest('.unit-row').length) {
            $(btn).closest('.unit-row').find('.extra-unit-select').val(name);
          } else if (mode) {
            $('#' + mode + '_unit').val(data.unit_id);
          }

          Swal.fire({ icon: 'success', title: pqT('products_q.unit_added'), timer: 1200, showConfirmButton: false });
        } else {
          Swal.fire({ icon: 'error', title: pqT('products_q.failed'), text: (data && data.message) || pqT('products_q.unit_failed') });
        }
      }, 'json').fail(function() {
        Swal.fire({ icon: 'error', title: pqT('products_q.error'), text: pqT('products_q.no_connection') });
      });
    }
  });
}

// ຄຳນວນລາຄາຊື້ ແລະ ລາຄາຂາຍເມື່ອປ້ອນຈຳນວນຕັດສະຕັອກ
function onExtraUnitMultiplierChange(input, mode) {
  var row = $(input).closest('.unit-row');
  var multVal = parseFloat($(input).val()) || 0;
  if (multVal <= 0) return;

  var baseBpriceStr = $('#' + mode + '_bprice').val() || '0';
  var basePriceStr = $('#' + mode + '_price').val() || '0';

  var baseBprice = parseFloat(baseBpriceStr.replace(/,/g, '')) || 0;
  var basePrice = parseFloat(basePriceStr.replace(/,/g, '')) || 0;

  var calcBprice = Math.round(baseBprice * multVal);
  var calcPrice = Math.round(basePrice * multVal);

  var bpriceInput = row.find('input[name="extra_unit_bprice[]"]');
  var priceInput = row.find('input[name="extra_unit_price[]"]');

  bpriceInput.val(calcBprice > 0 ? Number(calcBprice).toLocaleString('en-US') : '0');
  priceInput.val(calcPrice > 0 ? Number(calcPrice).toLocaleString('en-US') : '0');
}

// ====== ຟັງຊັນ ສ້າງລະຫັດບາໂຄ້ດ EAN-13 (13 ຫຼັກ) ======
function generateEAN13Value() {
  var code = '20';
  for (var i = 0; i < 10; i++) {
    code += Math.floor(Math.random() * 10);
  }
  var sum = 0;
  for (var i = 0; i < 12; i++) {
    var digit = parseInt(code.charAt(i), 10);
    sum += (i % 2 === 0) ? digit : digit * 3;
  }
  var checksum = (10 - (sum % 10)) % 10;
  return code + checksum;
}

window.generateEAN13 = function(target) {
  var finalBarcode = generateEAN13Value();
  if (typeof target === 'string') {
    $('#' + target).val(finalBarcode).trigger('change');
  } else if (target && (target instanceof HTMLElement || target.jquery)) {
    $(target).closest('.input-group').find('input').val(finalBarcode).trigger('change');
  }
  return finalBarcode;
};

// ເພີ່ມແຖວຫົວໜ່ວຍເພີ່ມເຕີມ
function addUnitRow(mode, data) {
  var container = mode === 'add' ? $('#add_multi_units_container') : $('#edit_multi_units_container');
  var unitName   = data ? (data.unit_name || '') : '';
  var multiplier = data ? data.multiplier : '';
  var bprice     = data && data.bprice > 0 ? Number(data.bprice).toLocaleString('en-US') : '';
  var price      = data && data.price > 0 ? Number(data.price).toLocaleString('en-US') : '';
  var barcode    = (data && data.barcode) ? data.barcode : '';

  // ດຶງລາຍການຫົວໜ່ວຍຈາກລະບົບມາໃສ່ dropdown
  var unitOpts = '<option value="">' + (I18N_ADD_PRODUCT['products.unit_row_name_placeholder'] || pqT('products_q.select_unit')) + '</option>';
  var matchedUnit = false;
  (window.ALL_UNITS || []).forEach(function(u) {
    var uName = (u.unit_name || '').trim();
    var isSel = (unitName && unitName.trim() === uName) ? 'selected' : '';
    if (isSel) matchedUnit = true;
    unitOpts += '<option value="' + escapeHtml(uName) + '" ' + isSel + '>' + escapeHtml(uName) + '</option>';
  });
  if (unitName && !matchedUnit) {
    unitOpts = '<option value="' + escapeHtml(unitName) + '" selected>' + escapeHtml(unitName) + '</option>' + unitOpts;
  }

  var rowHtml = `
    <div class="card mb-2.5 p-3 unit-row shadow-xs" style="background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 10px;">
      <div class="row align-items-end">
        <div class="col-lg-2 col-md-6 col-12 mb-2 mb-lg-0">
          <?php /* ແຖວດຽວ: ປ້າຍບໍ່ຕັດລົງແຖວ (nowrap), ລິ້ງເພີ່ມຫົວໜ່ວຍເປັນໄອຄອນ ⊕ ພ້ອມ title — ເມື່ອກ່ອນຂໍ້ຄວາມ
                 "+ ເພີ່ມຫົວໜ່ວຍ" ດັນປ້າຍລົງເປັນ 3 ແຖວໃນຈໍແຄບ */ ?>
          <div class="d-flex align-items-center mb-1" style="gap: 6px; white-space: nowrap;">
            <label class="font-weight-bold text-dark mb-0 text-nowrap" style="font-size: 0.86rem;">
              ${I18N_ADD_PRODUCT['products.unit_row_name_label']} <span class="text-danger">*</span>
            </label>
            <a href="javascript:void(0)" onclick="quickAddUnitPrompt('${mode}', this)" class="text-primary" title="${pqT('products_q.add_unit_link')}" style="font-size: 0.95rem; line-height: 1;">
              <i class="fas fa-plus-circle"></i>
            </a>
          </div>
          <select name="extra_unit_name[]" class="form-control extra-unit-select font-weight-bold text-dark" style="height: 40px; border-radius: 6px; font-size: 0.92rem;" required>
            ${unitOpts}
          </select>
        </div>
        <div class="col-lg-2 col-md-6 col-6 mb-2 mb-lg-0">
          <label class="font-weight-bold text-dark mb-1 text-nowrap" style="font-size: 0.86rem;">
            ${I18N_ADD_PRODUCT['products.unit_row_qty_label']} <span class="text-danger">*</span>
          </label>
          <input type="text" inputmode="numeric" pattern="[0-9]*" name="extra_unit_multiplier[]" class="form-control text-center font-weight-bold extra-unit-multiplier text-primary no-spin" placeholder="0" value="${multiplier}" style="height: 40px; border-radius: 6px; font-size: 0.95rem;" oninput="this.value = this.value.replace(/[^0-9]/g, ''); onExtraUnitMultiplierChange(this, '${mode}');" required>
        </div>
        <div class="col-lg-2 col-md-6 col-6 mb-2 mb-lg-0">
          <label class="font-weight-bold text-dark mb-1 text-nowrap" style="font-size: 0.86rem;">
            ${I18N_ADD_PRODUCT['products.unit_row_bprice_label']}
          </label>
          <input type="text" name="extra_unit_bprice[]" class="form-control text-right price-input extra-unit-bprice" placeholder="0" value="${bprice}" style="height: 40px; border-radius: 6px; font-size: 0.95rem; color: #475569;" oninput="formatPriceInput(this)">
        </div>
        <div class="col-lg-2 col-md-6 col-6 mb-2 mb-lg-0">
          <label class="font-weight-bold text-dark mb-1 text-nowrap" style="font-size: 0.86rem;">
            ${I18N_ADD_PRODUCT['products.unit_row_price_label']} <span class="text-danger">*</span>
          </label>
          <input type="text" name="extra_unit_price[]" class="form-control text-right font-weight-bold text-success price-input extra-unit-price" placeholder="0" value="${price}" style="height: 40px; border-radius: 6px; font-size: 0.95rem;" oninput="formatPriceInput(this)" required>
        </div>
        <div class="col-lg-2.5 col-md-5 col-10 mb-2 mb-lg-0" style="flex: 1;">
          <label class="font-weight-bold text-dark mb-1 text-nowrap" style="font-size: 0.86rem;">
            ${I18N_ADD_PRODUCT['products.unit_row_barcode_label']}
          </label>
          <div class="input-group barcode-combo">
            <input type="text" name="extra_unit_barcode[]" class="form-control font-weight-bold" placeholder="${I18N_ADD_PRODUCT['products.unit_row_barcode_placeholder']}" value="${barcode}" style="height: 40px; border-radius: 6px 0 0 6px; font-size: 0.92rem;" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
            <div class="input-group-append">
              <button type="button" class="btn btn-outline-primary font-weight-bold px-2.5" onclick="generateEAN13(this)" title="${I18N_ADD_PRODUCT['products.gen_barcode_title']}" style="height: 40px; border-radius: 0 6px 6px 0; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 4px;">
                <i class="fas fa-barcode"></i> <span>${pqT('products_q.gen_short')}</span>
              </button>
            </div>
          </div>
        </div>
        <div class="col-auto text-right mb-2 mb-lg-0 pl-1">
          <button type="button" class="btn btn-outline-danger shadow-xs" title="${I18N_ADD_PRODUCT['products.unit_row_delete_title']}" onclick="$(this).closest('.unit-row').remove();" style="height: 40px; width: 40px; border-radius: 6px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
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
    var uName  = ($(this).find('[name="extra_unit_name[]"]').val() || '').trim();
    var uMult  = ($(this).find('[name="extra_unit_multiplier[]"]').val() || '').trim();
    var uPrice = ($(this).find('[name="extra_unit_price[]"]').val() || '').trim();

    if (uName === '') {
      if (e) e.preventDefault();
      var inputEl = $(this).find('[name="extra_unit_name[]"]');
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
      localStorage.setItem('StoreMini.minipos_product_category_filter', catId);
      localStorage.setItem('StoreMini.minipos_last_add_category_id', catId);
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
    url: '../../api/products_api.php',
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

// ລ້າງຟອມເພີ່ມທີລະລາຍການ ໃຫ້ວ່າງທັງໝົດ (ຫົວໜ່ວຍ = "-- ເລືອກຫົວໜ່ວຍ --")
window.resetAddSingleForm = function() {
  var $f = $('#addProductForm');
  if ($f.length) $f[0].reset();
  $('#add_category_id').val('');
  $('#add_product_id').val('');
  $('#add_product_id_display').html('<span class="text-muted" style="font-family: inherit; font-size: 0.88rem; font-weight: normal;"><?php echo htmlspecialchars(t('products.product_code_placeholder', 'ເລືອກປະເພດສິນຄ້າ...')); ?></span>');
  $('#add_product_name').val('');
  $('#add_barcode').val('');
  $('#add_bprice').val('');
  $('#add_price').val('');
  $('#add_qty').val('0');
  $('#add_supplier_id').val('');
  $('#add_expiry_date').val('');
  $('#add_zone').val('').trigger('change');
  $('#add_shelf').val('');
  $('#add_multi_units_container').empty();
  $('#add_unit').val('');
  removeProductImg('add');
};

// ປຸ່ມ "ຍົກເລີກ": ປິດ modal (ການລ້າງຂໍ້ມູນເກີດຂຶ້ນໃນ hidden.bs.modal ຂ້າງລຸ່ມ)
window.cancelAddSingleForm = function() {
  $('#addProductModal').modal('hide');
};

// ປິດ modal ທຸກທາງ (ໄອຄອນ ✕, ຍົກເລີກ, ກົດນອກຟອມ, ຫຼັງບັນທຶກ) → ລ້າງຂໍ້ມູນໃຫ້ວ່າງ ບໍ່ຄ້າງໄວ້ເທື່ອໜ້າ
$('#addProductModal').on('hidden.bs.modal', function () {
  resetAddSingleForm();
});

// Prevent Enter key in barcode inputs from prematurely submitting form
$(document).on('keydown', '#add_barcode, input[name="extra_unit_barcode[]"], #bulkAddProductForm input[name="b_barcode[]"]', function(e) {
  if (e.key === 'Enter' || e.keyCode === 13) {
    e.preventDefault();
    return false;
  }
});

