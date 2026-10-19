<?php
/*
 * ສ່ວນ: JS: ເພີ່ມທີລະລາຍການ
 * ເປີດ <script>: ລະຫັດສິນຄ້າ preview, ຈື່ປະເພດ/ຫົວໜ່ວຍລ່າສຸດ, ແຖວຫົວໜ່ວຍເພີ່ມເຕີມ, ກວດ ແລະ
 * ສົ່ງຟອມ, ກວດບາໂຄ້ດຊ້ຳ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/products/components/form_add_product.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?><script>
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
var GLOBAL_CODE_PREFIX = '<?php echo htmlspecialchars(getProductCodePrefix($pdo, getActiveStoreId($pdo))); ?>';
var LAST_ADD_CATEGORY_ID = localStorage.getItem('StoreMini.minipos_last_add_category_id') || '';
var LAST_ADD_UNIT_ID = localStorage.getItem('StoreMini.minipos_last_add_unit_id') || '';

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
    $.getJSON('api_product.php?action=get_next_product_id&category_id=' + encodeURIComponent(catId), function(res) {
      if (res && res.success) {
        var fullCode = String(res.next_id);
        var prefix = String(res.prefix !== undefined ? res.prefix : GLOBAL_CODE_PREFIX);
        var seqFormatted = String(res.seq_formatted || fullCode.slice(-4));
        var catPart = String(catId);

        $('#add_product_id').val(fullCode);
        $('#add_product_id_display').html(
          '<span style="color: #dc2626;" title="ລະຫັດສາຂາ">' + prefix + '</span>' +
          '<span style="color: #2563eb;" title="ລະຫັດປະເພດສິນຄ້າ">' + catPart + '</span>' +
          '<span style="color: #0f172a;" title="ລະຫັດສິນຄ້າ">' + seqFormatted + '</span>'
        );
      }
    });
  } else {
    $('#add_product_id').val('');
    $('#add_product_id_display').html('<span class="text-muted" style="font-family: inherit; font-size: 0.88rem; font-weight: normal;"><?php echo htmlspecialchars(t('products.product_code_placeholder', 'ເລືອກປະເພດສິນຄ້າ...')); ?></span>');
  }
}

$(document).on('change', '#add_unit', function() {
  var uVal = $(this).val();
  if (uVal) {
    LAST_ADD_UNIT_ID = uVal;
    try { localStorage.setItem('StoreMini.minipos_last_add_unit_id', uVal); } catch(e){}
  }
});

$('#addProductModal').on('show.bs.modal', function() {
  $('#add_category_id').val('');
  $('#add_product_id').val('');
  $('#add_product_id_display').html('<span class="text-muted" style="font-family: inherit; font-size: 0.88rem; font-weight: normal;"><?php echo htmlspecialchars(t('products.product_code_placeholder', 'ເລືອກປະເພດສິນຄ້າ...')); ?></span>');

  if (LAST_ADD_UNIT_ID && $('#add_unit option[value="' + LAST_ADD_UNIT_ID + '"]').length > 0) {
    $('#add_unit').val(LAST_ADD_UNIT_ID);
  }
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

