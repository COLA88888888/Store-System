<script>
var QUOTE_I18N = <?php echo tjson([
    'quotations.js_error_title'        => 'ຜິດພາດ',
    'quotations.js_warn_title'         => 'ແຈ້ງເຕືອນ',
    'quotations.js_generic_error'      => 'ຜິດພາດ!',
    'quotations.js_done'               => 'ດຳເນີນການສຳເລັດ!',
    'quotations.js_need_product'       => 'ກະລຸນາເລືອກສິນຄ້າຢ່າງໜ້ອຍ 1 ລາຍການ!',
    'quotations.js_need_customer'      => 'ກະລຸນາເລືອກ ຫຼື ພິມຊື່ລູກຄ້າ!',
    'quotations.js_confirm_delete_title' => 'ຢືນຢັນການລົບ?',
    'quotations.js_confirm_delete_text'  => 'ຕ້ອງການລົບໃບສະເໜີລາຄາ "{no}" ແທ້ຫຼືບໍ່?',
    'quotations.js_confirm_delete_yes'   => 'ລົບເລີຍ',
    'quotations.btn_cancel'            => 'ຍົກເລີກ',
    'quotations.js_deleted_success'    => 'ລົບໃບສະເໜີລາຄາສຳເລັດ!',
    'quotations.js_confirm_sell_title' => 'ຢືນຢັນການຂາຍ',
    'quotations.js_confirm_sell_text'  => 'ລູກຄ້າຮັບໃບສະເໜີລາຄາ "{no}" ແລ້ວແມ່ນບໍ່? ລະບົບຈະຕັດສະຕັອກ ແລະ ບັນທຶກເປັນລາຍການຂາຍທັນທີ.',
    'quotations.js_confirm_sell_yes'   => 'ແມ່ນ, ຂາຍເລີຍ',
    'quotations.js_payment_type_label' => 'ຮັບເງິນແບບໃດ?',
    'quotations.picker_page_label'     => 'ໜ້າ',
    'quotations.payment_cash'          => 'ເງິນສົດ',
    'quotations.payment_transfer'      => 'ໂອນຈ່າຍ',
    'quotations.js_out_of_stock'       => 'ສິນຄ້າ "{name}" ໝົດແລ້ວໃນສາງ! (ຍັງສາມາດເລືອກໃສ່ໃບສະເໜີລາຄາໄດ້)',
    'quotations.js_out_of_stock_block_title' => 'ບໍ່ສາມາດບັນທຶກໄດ້!',
    'quotations.js_out_of_stock_block_text'  => 'ສິນຄ້າຕໍ່ໄປນີ້ໝົດແລ້ວໃນສາງ: {names}. ກະລຸນາລຶບອອກ ຫຼື ລໍຖ້າຈົນກວ່າສິນຄ້າຈະມີສະຕັອກ ກ່ອນບັນທຶກ.',
]); ?>;

var QUOTE_CUSTOMERS  = <?php echo json_encode($customersList, JSON_UNESCAPED_UNICODE); ?>;
var QUOTE_PRODUCTS   = <?php echo json_encode($productsLookup, JSON_UNESCAPED_UNICODE); ?>;
var quoteRowSeq = 0;

// ====== Number formatting helpers (comma thousands, same pattern as other modules) ======
function quoteFormatInput(input) {
  let val = input.value.replace(/[^0-9.]/g, '');
  if (val === '') { input.value = ''; return; }
  let parts = val.split('.');
  parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  if (parts.length > 2) parts = [parts[0], parts.slice(1).join('')];
  input.value = parts.join('.');
}
function quoteNum(val) {
  if (val === null || val === undefined || val === '') return 0;
  var n = Number(String(val).replace(/,/g, ''));
  return isNaN(n) ? 0 : n;
}
function quoteMoney(n) {
  return Math.round(n).toLocaleString('en-US') + ' ₭';
}
function quoteEsc(s) {
  return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// ====== Line items — added only via the tick-box product picker, never typed ======
function quoteToggleEmptyRow() {
  var hasRows = document.querySelectorAll('#quoteItemsBody tr:not(#quoteItemsEmptyRow)').length > 0;
  var emptyRow = document.getElementById('quoteItemsEmptyRow');
  if (emptyRow) emptyRow.style.display = hasRows ? 'none' : '';
}

function quoteAddItemRow(prefill) {
  prefill = prefill || {};
  var rid = 'qrow_' + (quoteRowSeq++);
  var tr = document.createElement('tr');
  tr.id = rid;
  tr.setAttribute('data-product-id', prefill.product_id || '');
  tr.innerHTML =
    '<td class="align-middle font-weight-bold text-dark q-item-name-display">' + quoteEsc(prefill.product_name || '') + '</td>' +
    '<td><input type="text" class="form-control form-control-sm q-item-unit" value="' + quoteEsc(prefill.unit_name || '') + '"></td>' +
    '<td><input type="text" class="form-control form-control-sm text-right q-item-qty" value="' + (prefill.qty || 1) + '" oninput="quoteFormatInput(this); quoteRecalcRow(\'' + rid + '\')"></td>' +
    '<td><input type="text" class="form-control form-control-sm text-right q-item-price" value="' + (prefill.price ? Number(prefill.price).toLocaleString('en-US') : '0') + '" oninput="quoteFormatInput(this); quoteRecalcRow(\'' + rid + '\')"></td>' +
    '<td><input type="text" class="form-control form-control-sm text-right q-item-discount" value="' + (prefill.discount ? Number(prefill.discount).toLocaleString('en-US') : '0') + '" oninput="quoteFormatInput(this); quoteRecalcRow(\'' + rid + '\')"></td>' +
    '<td class="text-right font-weight-bold q-item-total align-middle">0 ₭</td>' +
    '<td class="text-center align-middle"><button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="quoteRemoveItemRow(\'' + rid + '\')"><i class="fas fa-times"></i></button></td>';
  document.getElementById('quoteItemsBody').appendChild(tr);
  quoteRecalcRow(rid);
  quoteToggleEmptyRow();
}

function quoteRemoveItemRow(rid) {
  var el = document.getElementById(rid);
  if (el) el.remove();
  quoteRecalcTotals();
  quoteToggleEmptyRow();
}

function quoteRecalcRow(rid) {
  var tr = document.getElementById(rid);
  if (!tr) return;
  var qty = quoteNum(tr.querySelector('.q-item-qty').value);
  var price = quoteNum(tr.querySelector('.q-item-price').value);
  var disc = quoteNum(tr.querySelector('.q-item-discount').value);
  var total = (qty * price) - disc;
  tr.querySelector('.q-item-total').textContent = quoteMoney(total);
  quoteRecalcTotals();
}

function quoteRecalcTotals() {
  var subtotal = 0;
  document.querySelectorAll('#quoteItemsBody tr:not(#quoteItemsEmptyRow)').forEach(function (tr) {
    var qty = quoteNum(tr.querySelector('.q-item-qty').value);
    var price = quoteNum(tr.querySelector('.q-item-price').value);
    var disc = quoteNum(tr.querySelector('.q-item-discount').value);
    subtotal += (qty * price) - disc;
  });
  var discountAmount = quoteNum(document.getElementById('quote_discount_amount').value);
  var vatAmount = quoteNum(document.getElementById('quote_vat_amount').value);
  var total = subtotal - discountAmount + vatAmount;
  document.getElementById('quote_subtotal_display').textContent = quoteMoney(subtotal);
  document.getElementById('quote_total_display').textContent = quoteMoney(total);
}

// ====== Product picker — browse by category, tick boxes, no typing ======
var quotePickerSelectedIds = new Set();
var quotePickerCurrentPage = 1;
var quotePickerPageSize = 10;
var quotePickerFilteredList = [];

function openProductPicker() {
  document.getElementById('productPickerCategory').value = '';
  document.getElementById('productPickerSearch').value = '';
  quotePickerSelectedIds.clear();
  document.getElementById('pickerSelectAll').checked = false;
  quoteResetPickerPageAndRender();
  $('#productPickerModal').modal('show');
}

function quoteResetPickerPageAndRender() {
  quotePickerCurrentPage = 1;
  document.getElementById('pickerSelectAll').checked = false;
  renderProductPickerList();
}

function renderProductPickerList() {
  var catVal = document.getElementById('productPickerCategory').value;
  var q = (document.getElementById('productPickerSearch').value || '').toLowerCase().trim();
  
  quotePickerFilteredList = QUOTE_PRODUCTS.filter(function (p) {
    var matchCat = (catVal === '') || (String(p.category_id) === catVal);
    var matchSearch = (q === '') || p.name.toLowerCase().indexOf(q) > -1 || (p.barcode || '').indexOf(q) > -1 || (p.code || '').indexOf(q) > -1;
    return matchCat && matchSearch;
  });

  var totalRows = quotePickerFilteredList.length;
  var totalPages = Math.ceil(totalRows / quotePickerPageSize) || 1;
  if (quotePickerCurrentPage > totalPages) quotePickerCurrentPage = totalPages;
  if (quotePickerCurrentPage < 1) quotePickerCurrentPage = 1;

  var startIdx = (quotePickerCurrentPage - 1) * quotePickerPageSize;
  var endIdx = Math.min(startIdx + quotePickerPageSize, totalRows);
  
  var html = '';
  if (totalRows === 0) {
    html = '<div class="text-center text-muted py-4"><?php echo htmlspecialchars(t("quotations.picker_product_empty", "ບໍ່ພົບລາຍການສິນຄ້າ"), ENT_QUOTES); ?></div>';
  } else {
    for (var i = startIdx; i < endIdx; i++) {
      var p = quotePickerFilteredList[i];
      var isChecked = quotePickerSelectedIds.has(p.id) ? 'checked' : '';
      html += '<label class="d-flex align-items-center justify-content-between px-3 py-2" style="border-bottom: 1px solid #f1f5f9; margin: 0; cursor: pointer;">' +
        '<span><input type="checkbox" class="product-picker-check mr-2" id="chk_picker_' + p.id + '" value="' + p.id + '" onchange="quoteTogglePickerItem(' + p.id + ', this.checked)" ' + isChecked + '> ' + quoteEsc(p.name) + ' <small class="text-muted">(' + quoteEsc(p.unit || '-') + ')</small></span>' +
        '<span class="font-weight-bold text-primary">' + Number(p.price).toLocaleString('en-US') + ' ₭</span>' +
        '</label>';
    }
  }
  document.getElementById('productPickerList').innerHTML = html;
  
  // Render Pagination
  document.getElementById('pickerPageInfo').textContent = QUOTE_I18N['quotations.picker_page_label'] + ' ' + quotePickerCurrentPage + ' / ' + totalPages;
  var ul = document.getElementById('pickerPagination');
  var phtml = '';
  
  var prevDisabled = (quotePickerCurrentPage === 1) ? 'disabled' : '';
  phtml += '<li class="page-item ' + prevDisabled + '"><a class="page-link" href="javascript:void(0)" onclick="quoteGoToPickerPage(' + (quotePickerCurrentPage - 1) + ')"><i class="fas fa-chevron-left"></i></a></li>';
  
  var startPage = Math.max(1, quotePickerCurrentPage - 2);
  var endPage = Math.min(totalPages, startPage + 4);
  if (endPage - startPage < 4) {
      startPage = Math.max(1, endPage - 4);
  }
  for (var j = startPage; j <= endPage; j++) {
      var active = (j === quotePickerCurrentPage) ? 'active' : '';
      phtml += '<li class="page-item ' + active + '"><a class="page-link" href="javascript:void(0)" onclick="quoteGoToPickerPage(' + j + ')">' + j + '</a></li>';
  }
  var nextDisabled = (quotePickerCurrentPage === totalPages) ? 'disabled' : '';
  phtml += '<li class="page-item ' + nextDisabled + '"><a class="page-link" href="javascript:void(0)" onclick="quoteGoToPickerPage(' + (quotePickerCurrentPage + 1) + ')"><i class="fas fa-chevron-right"></i></a></li>';
  
  ul.innerHTML = phtml;
  quoteUpdatePickerCount();
}

function quoteGoToPickerPage(page) {
    var totalPages = Math.ceil(quotePickerFilteredList.length / quotePickerPageSize) || 1;
    if (page < 1 || page > totalPages) return;
    quotePickerCurrentPage = page;
    renderProductPickerList();
}

function quoteTogglePickerItem(id, checked) {
    if (checked) {
        quotePickerSelectedIds.add(id);
        var p = QUOTE_PRODUCTS.find(function (x) { return String(x.id) === String(id); });
        if (p && p.cut_qty != 0 && Number(p.qty) <= 0) {
            Swal.fire({
                toast: true, position: 'top-end', icon: 'warning',
                title: QUOTE_I18N['quotations.js_out_of_stock'].replace('{name}', p.name),
                showConfirmButton: false, timer: 2500
            });
        }
    } else {
        quotePickerSelectedIds.delete(id);
    }
    quoteUpdatePickerCount();
}

function quoteToggleSelectAllPicker(checked) {
    for (var i = 0; i < quotePickerFilteredList.length; i++) {
        var id = quotePickerFilteredList[i].id;
        if (checked) {
            quotePickerSelectedIds.add(id);
        } else {
            quotePickerSelectedIds.delete(id);
        }
    }
    renderProductPickerList();
}

function quoteUpdatePickerCount() {
  var count = quotePickerSelectedIds.size;
  document.getElementById('productPickerCheckedCount').textContent = count;
  var selectAllEl = document.getElementById('pickerSelectAll');
  if (selectAllEl) {
      if (count > 0 && count === quotePickerFilteredList.length) {
          selectAllEl.checked = true;
      } else {
          selectAllEl.checked = false;
      }
  }
}

function quoteAddPickedProducts() {
  if (quotePickerSelectedIds.size === 0) return;

  var addedCount = 0;
  quotePickerSelectedIds.forEach(function (id) {
    var p = QUOTE_PRODUCTS.find(function (x) { return String(x.id) === String(id); });
    if (p) {
      quoteAddItemRow({ product_id: p.id, product_name: p.name, unit_name: p.unit, qty: 1, price: p.price, discount: 0 });
      addedCount++;
    }
  });

  if (addedCount > 0) {
    quotePickerSelectedIds.clear();
    $('#productPickerModal').modal('hide');
  }
}

// ====== Customer picker — choose an existing customer, or type a new one ======
function openCustomerPicker() {
  document.getElementById('customerPickerSearch').value = '';
  document.getElementById('customerPickerNewName').value = '';
  renderCustomerPickerList();
  $('#customerPickerModal').modal('show');
}

function renderCustomerPickerList() {
  var q = (document.getElementById('customerPickerSearch').value || '').toLowerCase().trim();
  var list = QUOTE_CUSTOMERS.filter(function (c) {
    return q === '' || c.customer_name.toLowerCase().indexOf(q) > -1 || (c.phone || '').indexOf(q) > -1;
  });
  var html = '';
  list.forEach(function (c) {
    html += '<div class="d-flex align-items-center justify-content-between px-3 py-2" style="border-bottom: 1px solid #f1f5f9; cursor: pointer;" onclick="quoteSelectExistingCustomer(' + c.customer_id + ')">' +
      '<span class="font-weight-bold text-dark">' + quoteEsc(c.customer_name) + '</span>' +
      '<span class="text-muted small">' + quoteEsc(c.phone || '') + '</span>' +
      '</div>';
  });
  document.getElementById('customerPickerList').innerHTML = html;
}

function quoteSelectExistingCustomer(id) {
  var c = QUOTE_CUSTOMERS.find(function (x) { return String(x.customer_id) === String(id); });
  if (!c) return;
  document.getElementById('quote_customer_id').value = c.customer_id;
  document.getElementById('quote_customer_name').value = c.customer_name;
  document.getElementById('quote_customer_display').value = c.customer_name;
  document.getElementById('quote_customer_tel').value = c.phone || '';
  document.getElementById('quote_customer_address').value = c.address || '';
  $('#customerPickerModal').modal('hide');
}

function quoteSelectNewCustomer() {
  var name = document.getElementById('customerPickerNewName').value.trim();
  if (!name) return;
  document.getElementById('quote_customer_id').value = '';
  document.getElementById('quote_customer_name').value = name;
  document.getElementById('quote_customer_display').value = name;
  $('#customerPickerModal').modal('hide');
}

// ====== Add / Edit modal ======
function quoteResetForm() {
  document.getElementById('quotationForm').reset();
  document.getElementById('quote_action').value = 'add_quotation';
  document.getElementById('quote_id').value = '';
  document.getElementById('quote_customer_id').value = '';
  document.getElementById('quote_customer_name').value = '';
  document.getElementById('quote_customer_display').value = '';
  document.getElementById('quoteItemsBody').innerHTML = '<tr id="quoteItemsEmptyRow"><td colspan="7" class="text-center text-muted py-3"><?php echo htmlspecialchars(t('quotations.items_empty', 'ຍັງບໍ່ໄດ້ເລືອກສິນຄ້າ — ກົດ "ເລືອກສິນຄ້າ" ຂ້າງເທິງ'), ENT_QUOTES); ?></td></tr>';
  document.getElementById('quote_discount_amount').value = '0';
  document.getElementById('quote_vat_amount').value = '0';
  var today = new Date();
  var plus7 = new Date(today.getTime() + 7 * 86400000);
  document.getElementById('quote_date').value = today.toISOString().slice(0, 10);
  document.getElementById('quote_valid_until').value = plus7.toISOString().slice(0, 10);
  quoteRecalcTotals();
}

function openQuotationModal(q) {
  quoteResetForm();
  var isEdit = !!(q && q.quotation_id);
  document.getElementById('quotationModalTitle').innerHTML = '<i class="fas fa-file-invoice-dollar mr-2"></i> ' +
    (isEdit ? '<?php echo htmlspecialchars(t('quotations.modal_edit_title', 'ແກ້ໄຂໃບສະເໜີລາຄາ'), ENT_QUOTES); ?>' : '<?php echo htmlspecialchars(t('quotations.modal_add_title', 'ສ້າງໃບສະເໜີລາຄາໃໝ່'), ENT_QUOTES); ?>');

  if (!isEdit) {
    $('#quotationModal').modal('show');
    return;
  }

  document.getElementById('quote_action').value = 'edit_quotation';
  document.getElementById('quote_id').value = q.quotation_id;
  document.getElementById('quote_customer_id').value = q.customer_id || '';
  document.getElementById('quote_customer_name').value = q.customer_name || '';
  document.getElementById('quote_customer_display').value = q.customer_name || '';
  document.getElementById('quote_customer_tel').value = q.customer_tel || '';
  document.getElementById('quote_customer_address').value = q.customer_address || '';
  document.getElementById('quote_date').value = q.quote_date || '';
  document.getElementById('quote_valid_until').value = q.valid_until || '';
  document.getElementById('quote_notes').value = q.notes || '';
  document.getElementById('quote_discount_amount').value = Number(q.discount_amount || 0).toLocaleString('en-US');
  document.getElementById('quote_vat_amount').value = Number(q.vat_amount || 0).toLocaleString('en-US');

  // Line items live in a separate table — fetch them before showing the modal.
  $.get('quotations.php?get_quotation=' + q.quotation_id, function (res) {
    if (res && res.success && Array.isArray(res.data.items) && res.data.items.length) {
      res.data.items.forEach(function (it) { quoteAddItemRow(it); });
    }
    $('#quotationModal').modal('show');
  }, 'json');
}

$(document).ready(function () {
  $('#quotationForm').on('submit', function (e) {
    e.preventDefault();

    var items = [];
    document.querySelectorAll('#quoteItemsBody tr:not(#quoteItemsEmptyRow)').forEach(function (tr) {
      items.push({
        product_id: tr.getAttribute('data-product-id') || null,
        product_name: tr.querySelector('.q-item-name-display').textContent.trim(),
        unit_name: tr.querySelector('.q-item-unit').value.trim(),
        qty: quoteNum(tr.querySelector('.q-item-qty').value),
        price: quoteNum(tr.querySelector('.q-item-price').value),
        discount: quoteNum(tr.querySelector('.q-item-discount').value),
      });
    });

      var customerName = document.getElementById('quote_customer_name').value.trim();
      if (!customerName) {
        customerName = 'ລູກຄ້າທົ່ວໄປ';
        document.getElementById('quote_customer_name').value = customerName;
      }
  
      if (items.length === 0) {
      Swal.fire({ icon: 'warning', title: QUOTE_I18N['quotations.js_warn_title'], text: QUOTE_I18N['quotations.js_need_product'] });
      return;
    }

    function doSaveQuotation() {
      document.getElementById('quote_items_json').value = JSON.stringify(items);

      var payload = {
        action: document.getElementById('quote_action').value,
        quotation_id: document.getElementById('quote_id').value,
        customer_id: document.getElementById('quote_customer_id').value,
        customer_name: document.getElementById('quote_customer_name').value,
        customer_tel: document.getElementById('quote_customer_tel').value,
        customer_address: document.getElementById('quote_customer_address').value,
        quote_date: document.getElementById('quote_date').value,
        valid_until: document.getElementById('quote_valid_until').value,
        discount_amount: quoteNum(document.getElementById('quote_discount_amount').value),
        vat_amount: quoteNum(document.getElementById('quote_vat_amount').value),
        notes: document.getElementById('quote_notes').value,
        items: document.getElementById('quote_items_json').value,
        is_ajax: 1,
      };

      $.post('quotations.php', payload, function (res) {
        if (res.success) {
          $('#quotationModal').modal('hide');
          refreshQuotationsTable();
          Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: res.message || QUOTE_I18N['quotations.js_done'], showConfirmButton: false, timer: 1600 });
        } else {
          Swal.fire({ icon: 'error', title: QUOTE_I18N['quotations.js_error_title'], text: res.message || QUOTE_I18N['quotations.js_generic_error'] });
        }
      }, 'json');
    }

    // Block saving outright when the quote still contains items that are currently out of
    // stock (checked against the picker's product list, fetched on page load).
    var outOfStockNames = [];
    document.querySelectorAll('#quoteItemsBody tr:not(#quoteItemsEmptyRow)').forEach(function (tr) {
      var pid = tr.getAttribute('data-product-id');
      if (!pid) return;
      var p = QUOTE_PRODUCTS.find(function (x) { return String(x.id) === String(pid); });
      if (p && p.cut_qty != 0 && Number(p.qty) <= 0) outOfStockNames.push(p.name);
    });

    if (outOfStockNames.length > 0) {
      Swal.fire({
        icon: 'error',
        title: QUOTE_I18N['quotations.js_out_of_stock_block_title'],
        text: QUOTE_I18N['quotations.js_out_of_stock_block_text'].replace('{names}', outOfStockNames.join(', ')),
      });
      return;
    }

    doSaveQuotation();
  });
});

function refreshQuotationsTable() {
  $.get('quotations.php?fetch_table=1', function (html) {
    var $newContent = $(html);
    var $tableCard = $('.card.border-0.shadow-sm');
    if ($tableCard.length) {
      $tableCard.replaceWith($newContent);
      initQuotePagination();
    } else {
      location.reload();
    }
  });
}

function deleteQuotation(id, no) {
  Swal.fire({
    icon: 'warning',
    title: QUOTE_I18N['quotations.js_confirm_delete_title'],
    text: QUOTE_I18N['quotations.js_confirm_delete_text'].replace('{no}', no),
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#64748b',
    confirmButtonText: QUOTE_I18N['quotations.js_confirm_delete_yes'],
    cancelButtonText: QUOTE_I18N['quotations.btn_cancel'],
  }).then(function (res) {
    if (res.isConfirmed) {
      $.post('quotations.php', { action: 'delete_quotation', id: id, is_ajax: 1 }, function (r) {
        if (r.success) {
          refreshQuotationsTable();
          Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: r.message || QUOTE_I18N['quotations.js_deleted_success'], showConfirmButton: false, timer: 1500 });
        } else {
          Swal.fire({ icon: 'error', title: QUOTE_I18N['quotations.js_error_title'], text: r.message || QUOTE_I18N['quotations.js_generic_error'] });
        }
      }, 'json');
    }
  });
}

// "ລູກຄ້າຮັບແລ້ວ" → converts the quote into a real sale (stock cut + written into the same
// sales tables as a normal POS checkout, using the QUOTED price — see convert_to_sale in
// api/quotations_backend.php).
function confirmConvertToSale(id, no) {
  Swal.fire({
    icon: 'question',
    title: QUOTE_I18N['quotations.js_confirm_sell_title'],
    html: '<p>' + QUOTE_I18N['quotations.js_confirm_sell_text'].replace('{no}', '<b>' + no + '</b>') + '</p>' +
          '<label class="font-weight-bold d-block text-left mt-2 mb-1">' + QUOTE_I18N['quotations.js_payment_type_label'] + '</label>' +
          '<select id="quoteSellPaymentType" class="swal2-input" style="margin:0; width:100%;">' +
          '<option value="ເງິນສົດ">' + QUOTE_I18N['quotations.payment_cash'] + '</option>' +
          '<option value="ໂອນຈ່າຍ">' + QUOTE_I18N['quotations.payment_transfer'] + '</option>' +
          '</select>',
    showCancelButton: true,
    confirmButtonColor: '#16a34a',
    cancelButtonColor: '#64748b',
    confirmButtonText: QUOTE_I18N['quotations.js_confirm_sell_yes'],
    cancelButtonText: QUOTE_I18N['quotations.btn_cancel'],
    preConfirm: function () {
      return document.getElementById('quoteSellPaymentType').value;
    }
  }).then(function (res) {
    if (res.isConfirmed) {
      $.post('quotations.php', { action: 'convert_to_sale', id: id, payment_type: res.value, is_ajax: 1 }, function (r) {
        if (r.success) {
          refreshQuotationsTable();
          Swal.fire({ icon: 'success', title: QUOTE_I18N['quotations.js_done'], text: r.message, timer: 2200 }).then(function() {
            if (r.id) {
              window.open('print_quotation.php?quotation_id=' + r.id, '_blank');
            }
          });
        } else {
          Swal.fire({ icon: 'error', title: QUOTE_I18N['quotations.js_error_title'], text: r.message || QUOTE_I18N['quotations.js_generic_error'] });
        }
      }, 'json');
    }
  });
}

// ====== Search + Pagination (same pattern as suppliers.php) ======
var quoteCurrentPage = 1;
var quotePageSize = 10;
var quoteFilteredRows = [];

function filterQuotations() {
  var q = (document.getElementById('quoteSearch').value || '').toLowerCase().trim();
  var rows = Array.from(document.querySelectorAll('tr.quote-row'));
  quoteFilteredRows = rows.filter(function (tr) {
    return q === '' || (tr.getAttribute('data-s') || '').indexOf(q) > -1;
  });
  var noRes = document.getElementById('quoteNoResult');
  if (noRes) noRes.style.display = (quoteFilteredRows.length === 0 && rows.length > 0) ? '' : 'none';
  quoteCurrentPage = 1;
  applyQuotePagination();
}

function initQuotePagination() {
  filterQuotations();
}

function changeQuotePageSize() {
  var val = document.getElementById('quotePageSizeSelect').value;
  quotePageSize = (val === 'all') ? (quoteFilteredRows.length || 99999) : parseInt(val, 10);
  quoteCurrentPage = 1;
  applyQuotePagination();
}

function goToQuotePage(page) {
  var totalPages = Math.ceil(quoteFilteredRows.length / quotePageSize) || 1;
  if (page < 1) page = 1;
  if (page > totalPages) page = totalPages;
  quoteCurrentPage = page;
  applyQuotePagination();
}

function applyQuotePagination() {
  var allRows = document.querySelectorAll('tr.quote-row');
  allRows.forEach(function (r) { r.style.display = 'none'; });

  var total = quoteFilteredRows.length;
  var totalPages = Math.ceil(total / quotePageSize) || 1;
  if (quoteCurrentPage > totalPages) quoteCurrentPage = totalPages;

  var startIdx = (quoteCurrentPage - 1) * quotePageSize;
  var endIdx = (quotePageSize >= 99999) ? total : startIdx + quotePageSize;

  var shown = 0;
  for (var i = startIdx; i < endIdx && i < total; i++) {
    if (quoteFilteredRows[i]) {
      quoteFilteredRows[i].style.display = '';
      shown++;
      var no = quoteFilteredRows[i].querySelector('.row-no');
      if (no) no.textContent = startIdx + shown;
    }
  }

  var startEl = document.getElementById('quote_page_info_start');
  var endEl = document.getElementById('quote_page_info_end');
  if (startEl) startEl.textContent = total === 0 ? 0 : startIdx + 1;
  if (endEl) endEl.textContent = Math.min(endIdx, total);
  var countNum = document.getElementById('quoteCountNum');
  if (countNum) countNum.textContent = total;

  renderQuotePaginationControls(totalPages);
}

function renderQuotePaginationControls(totalPages) {
  var ul = document.getElementById('quotePagination');
  if (!ul) return;
  ul.innerHTML = '';
  if (totalPages < 1) totalPages = 1;

  var prevLi = document.createElement('li');
  prevLi.className = 'page-item ' + (quoteCurrentPage <= 1 ? 'disabled' : '');
  prevLi.innerHTML = '<a class="page-link" href="javascript:void(0)" ' + (quoteCurrentPage > 1 ? 'onclick="goToQuotePage(' + (quoteCurrentPage - 1) + ')"' : '') + '><i class="fas fa-chevron-left"></i></a>';
  ul.appendChild(prevLi);

  var maxButtons = 5;
  var startPage = Math.max(1, quoteCurrentPage - 2);
  var endPage = Math.min(totalPages, startPage + maxButtons - 1);
  if (endPage - startPage < maxButtons - 1) startPage = Math.max(1, endPage - maxButtons + 1);

  if (startPage > 1) {
    var firstLi = document.createElement('li');
    firstLi.className = 'page-item';
    firstLi.innerHTML = '<a class="page-link" href="javascript:void(0)" onclick="goToQuotePage(1)">1</a>';
    ul.appendChild(firstLi);
    if (startPage > 2) {
      var dotLi = document.createElement('li');
      dotLi.className = 'page-item disabled';
      dotLi.innerHTML = '<span class="page-link" style="border:none; background:transparent;">...</span>';
      ul.appendChild(dotLi);
    }
  }

  for (var p = startPage; p <= endPage; p++) {
    var pageLi = document.createElement('li');
    pageLi.className = 'page-item ' + (p === quoteCurrentPage ? 'active' : '');
    pageLi.innerHTML = '<a class="page-link" href="javascript:void(0)" onclick="goToQuotePage(' + p + ')">' + p + '</a>';
    ul.appendChild(pageLi);
  }

  if (endPage < totalPages) {
    if (endPage < totalPages - 1) {
      var dotLi2 = document.createElement('li');
      dotLi2.className = 'page-item disabled';
      dotLi2.innerHTML = '<span class="page-link" style="border:none; background:transparent;">...</span>';
      ul.appendChild(dotLi2);
    }
    var lastLi = document.createElement('li');
    lastLi.className = 'page-item';
    lastLi.innerHTML = '<a class="page-link" href="javascript:void(0)" onclick="goToQuotePage(' + totalPages + ')">' + totalPages + '</a>';
    ul.appendChild(lastLi);
  }

  var nextLi = document.createElement('li');
  nextLi.className = 'page-item ' + (quoteCurrentPage >= totalPages ? 'disabled' : '');
  nextLi.innerHTML = '<a class="page-link" href="javascript:void(0)" ' + (quoteCurrentPage < totalPages ? 'onclick="goToQuotePage(' + (quoteCurrentPage + 1) + ')"' : '') + '><i class="fas fa-chevron-right"></i></a>';
  ul.appendChild(nextLi);
}

document.addEventListener('DOMContentLoaded', initQuotePagination);
</script>
