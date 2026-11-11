<script>
var I18N_PRICE_ADJUSTMENT = <?php echo tjson([
    'price_adjustment.select2_placeholder' => '-- ເລືອກ ຫຼື ພິມຄົ້ນຫາໝວດໝູ່ --',
    'price_adjustment.scan_wait_placeholder' => '-- ລໍຖ້າສະແກນບາໂຄ້ດ/ພິມລະຫັດ --',
    'price_adjustment.not_found_placeholder' => '❌ ບໍ່ພົບສິນຄ້ານີ້ໃນລະບົບ',
    'price_adjustment.confirm_delete_title' => 'ຢືນຢັນການລົບ?',
    'price_adjustment.confirm_delete_text' => 'ຕ້ອງການລົບປະຫວັດການປັບລາຄານີ້ແທ້ຫຼືບໍ່?',
    'price_adjustment.btn_delete_confirm' => 'ລົບເລີຍ',
    'price_adjustment.btn_cancel' => 'ຍົກເລີກ',
    'price_adjustment.msg_delete_success' => 'ລຶບປະຫວັດສຳເລັດ!',
    'price_adjustment.msg_error_title' => 'ຜິດພາດ',
    'price_adjustment.msg_error_generic' => 'ຜິດພາດ!',
    'price_adjustment.msg_action_success' => 'ດຳເນີນການສຳເລັດ!',
]); ?>;

$(document).ready(function() {
  if ($.fn.select2) {
    $('#category_id').select2({
      theme: 'bootstrap4',
      placeholder: I18N_PRICE_ADJUSTMENT['price_adjustment.select2_placeholder'],
      allowClear: true,
      dropdownParent: $('#priceAdjModal')
    });
  }
  
  // Focus quick search input on modal open
  $('#priceAdjModal').on('shown.bs.modal', function() {
    $('#quick_search_code').val('').focus();
  });

  // ສະແກນແລ້ວ Enter: ບາໂຄ້ດບໍ່ມີໃນລະບົບ → ເຕືອນໃຫ້ປ້ອນລາຍການສິນຄ້າກ່ອນ (ແລະ ບໍ່ສົ່ງຟອມ)
  $(document).on('keydown', '#quick_search_code', function(e) {
    if (e.key !== 'Enter') return;
    var code = $.trim(this.value);
    if (!code) return;
    quickFindProduct(code);
    if (!$('#product_id_hidden').val()) {
      e.preventDefault();
      if (window.showBarcodeNotFoundAlert) window.showBarcodeNotFoundAlert(code, this);
    }
  });
});

function quickFindProduct(val) {
  val = $.trim(val).toLowerCase();
  if (!val) {
    $('#product_id_hidden').val('');
    $('#product_name_display').val(I18N_PRICE_ADJUSTMENT['price_adjustment.scan_wait_placeholder']);
    $('#cur_bprice').val('');
    $('#cur_price').val('');
    return;
  }

  var foundOpt = null;
  $('#product_id option').each(function() {
    var pCode = String($(this).data('code') || '').toLowerCase();
    var pBarcode = String($(this).data('barcode') || '').toLowerCase();
    var pId = String($(this).data('id') || '').toLowerCase();

    if (pCode === val || pBarcode === val || pId === val) {
      foundOpt = this;
      return false; // Break loop
    }
  });

  if (foundOpt) {
    var pId = $(foundOpt).val();
    var pName = $(foundOpt).data('name') || '';
    var bprice = $(foundOpt).data('bprice') || '0';
    var price = $(foundOpt).data('price') || '0';

    $('#product_id_hidden').val(pId);
    $('#product_name_display').val(pName);
    $('#cur_bprice').val(Number(bprice).toLocaleString() + ' <?php echo kipSymbol(); ?>');
    $('#cur_price').val(Number(price).toLocaleString() + ' <?php echo kipSymbol(); ?>');
  } else {
    $('#product_id_hidden').val('');
    $('#product_name_display').val(I18N_PRICE_ADJUSTMENT['price_adjustment.not_found_placeholder']);
    $('#cur_bprice').val('');
    $('#cur_price').val('');
  }
}

function onTargetModeChange(mode) {
  if (mode === 'category') {
    $('#category_select_wrap').show();
    $('#product_scan_wrap').hide();
    $('#product_name_wrap').hide();
    $('#old_prices_display').hide();
    $('#category_id').prop('required', true);
    $('#product_id_hidden').prop('required', false);
  } else {
    $('#category_select_wrap').hide();
    $('#product_scan_wrap').show();
    $('#product_name_wrap').show();
    $('#old_prices_display').css('display', 'flex').show();
    $('#category_id').prop('required', false);
    $('#product_id_hidden').prop('required', true);
  }
}

function formatNumberInput(input) {
  var val = input.value.replace(/[^0-9.]/g, '');
  if (val === '') {
    input.value = '';
    return;
  }
  var parts = val.split('.');
  parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  if (parts.length > 2) {
    parts = [parts[0], parts.slice(1).join('')];
  }
  input.value = parts.join('.');
}

function deletePriceAdjLog(id, btn) {
  Swal.fire({
    title: I18N_PRICE_ADJUSTMENT['price_adjustment.confirm_delete_title'],
    text: I18N_PRICE_ADJUSTMENT['price_adjustment.confirm_delete_text'],
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: I18N_PRICE_ADJUSTMENT['price_adjustment.btn_delete_confirm'],
    cancelButtonText: I18N_PRICE_ADJUSTMENT['price_adjustment.btn_cancel']
  }).then(function(r) {
    if (r.isConfirmed) {
      $.post('price_adjustment.php', { action: 'delete_log', adjust_id: id, is_ajax: 1 }, function(res) {
        if (res.success) {
          $(btn).closest('tr').fadeOut(300, function() { $(this).remove(); initPadjPagination(); });
          Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: I18N_PRICE_ADJUSTMENT['price_adjustment.msg_delete_success'],
            showConfirmButton: false,
            timer: 1500
          });
        } else {
          Swal.fire({ icon: 'error', title: I18N_PRICE_ADJUSTMENT['price_adjustment.msg_error_title'], text: res.message || I18N_PRICE_ADJUSTMENT['price_adjustment.msg_error_generic'] });
        }
      }, 'json');
    }
  });
}

// Reuse the add modal as an edit form. `d` = row data from the table's edit button.
function editPriceAdjLog(d) {
  var $form = $('#priceAdjForm');
  // switch the form into "update" mode
  $form.find('input[name="action"]').val('update_log');
  if (!$form.find('input[name="adjust_id"]').length) {
    $form.append('<input type="hidden" name="adjust_id">');
  }
  $form.find('input[name="adjust_id"]').val(d.adjust_id);

  // lock to product mode, prefill the product + its ORIGINAL prices (the true baseline)
  $('#target_mode').val('product').prop('disabled', true);
  onTargetModeChange('product');
  $('#quick_search_code').closest('#product_scan_wrap').hide();  // no re-scan when editing
  $('#product_id_hidden').val(d.product_id);
  $('#product_name_display').val(d.product_name || ('ID: ' + d.product_id));
  $('#cur_bprice').val(Number(d.old_bprice || 0).toLocaleString() + ' <?php echo kipSymbol(); ?>');
  $('#cur_price').val(Number(d.old_price || 0).toLocaleString() + ' <?php echo kipSymbol(); ?>');

  $('#target_price_type').val(d.target_price_type || 'price');
  $('#calc_type').val(d.calc_type || 'set');
  $('#adjust_value').val(Number(d.adjust_value || 0).toLocaleString());
  $form.find('input[name="remark"]').val(d.remark || '');

  $('#priceAdjModalLabel').html('<i class="fas fa-edit mr-1.5"></i> <?php echo tlj('ແກ້ໄຂການປັບລາຄາ'); ?>');
  $form.find('button[type="submit"]').html('<i class="fas fa-check-circle mr-1"></i> <?php echo tlj('ບັນທຶກການແກ້ໄຂ'); ?>');

  $('#priceAdjModal').modal('show');
}

// Whenever the modal closes, put it back to "add" mode for the next open.
$(document).on('hidden.bs.modal', '#priceAdjModal', function() {
  var $form = $('#priceAdjForm');
  $form[0].reset();
  $form.find('input[name="action"]').val('adjust_price');
  $form.find('input[name="adjust_id"]').val('');
  $('#target_mode').prop('disabled', false);
  $('#product_scan_wrap').show();
  $('#product_id_hidden').val('');
  $('#product_name_display').val('');
  $('#cur_bprice').val('');
  $('#cur_price').val('');
  $('#priceAdjModalLabel').html('<i class="fas fa-edit mr-1.5"></i> <?php echo htmlspecialchars(t('price_adjustment.modal_title', 'ຟອມປັບປຸງລາຄາສິນຄ້າ'), ENT_QUOTES); ?>');
  $('#priceAdjForm button[type="submit"]').html('<i class="fas fa-check-circle mr-1"></i> <?php echo htmlspecialchars(t('price_adjustment.btn_save', 'ບັນທຶກ'), ENT_QUOTES); ?>');
});

function refreshPriceAdjTable() {
  var url = 'price_adjustment.php?fetch_table=1';
  var storeVal = $('select[name="store_id"]').val();
  if (storeVal) url += '&store_id=' + storeVal;
  
  $.get(url, function(html) {
    var $newContent = $(html);
    var $tableCard = $('.card.border-0.shadow-sm');
    if ($tableCard.length) {
      $tableCard.replaceWith($newContent);
      initPadjPagination();
    }
  });
}

$(document).ready(function() {
  $('#priceAdjForm').on('submit', function(e) {
    e.preventDefault();
    var $form = $(this);
    var formData = $form.serialize() + '&is_ajax=1';
    $.post('price_adjustment.php', formData, function(res) {
      if (res.success) {
        $('#priceAdjModal').modal('hide');
        $form[0].reset();
        refreshPriceAdjTable();
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'success',
          title: res.message || I18N_PRICE_ADJUSTMENT['price_adjustment.msg_action_success'],
          showConfirmButton: false,
          timer: 1500
        });
      } else {
        Swal.fire({ icon: 'error', title: I18N_PRICE_ADJUSTMENT['price_adjustment.msg_error_title'], text: res.message || I18N_PRICE_ADJUSTMENT['price_adjustment.msg_error_generic'] });
      }
    }, 'json');
  });
});

// ====== Pagination (client-side; re-init after any AJAX table swap or row removal) ======
var padjCurrentPage = 1;
var padjPageSize = 10;
var padjFilteredRows = [];

function initPadjPagination() {
  padjFilteredRows = Array.from(document.querySelectorAll('#priceAdjTable tbody tr.price-adj-row'));
  padjCurrentPage = 1;
  applyPadjPagination();
}

function changePadjPageSize() {
  var val = document.getElementById('padjPageSizeSelect').value;
  padjPageSize = (val === 'all') ? (padjFilteredRows.length || 99999) : parseInt(val, 10);
  padjCurrentPage = 1;
  applyPadjPagination();
}

function goToPadjPage(page) {
  var totalPages = Math.ceil(padjFilteredRows.length / padjPageSize) || 1;
  if (page < 1) page = 1;
  if (page > totalPages) page = totalPages;
  padjCurrentPage = page;
  applyPadjPagination();
}

function applyPadjPagination() {
  var allRows = document.querySelectorAll('#priceAdjTable tbody tr.price-adj-row');
  allRows.forEach(function(r) { r.style.display = 'none'; });

  var total = padjFilteredRows.length;
  var totalPages = Math.ceil(total / padjPageSize) || 1;
  if (padjCurrentPage > totalPages) padjCurrentPage = totalPages;

  var startIdx = (padjCurrentPage - 1) * padjPageSize;
  var endIdx = (padjPageSize >= 99999) ? total : startIdx + padjPageSize;

  for (var i = startIdx; i < endIdx && i < total; i++) {
    if (padjFilteredRows[i]) padjFilteredRows[i].style.display = '';
  }

  var startEl = document.getElementById('padj_page_info_start');
  var endEl = document.getElementById('padj_page_info_end');
  var totalEl = document.getElementById('padj_page_info_total');
  if (startEl) startEl.textContent = total === 0 ? 0 : startIdx + 1;
  if (endEl) endEl.textContent = Math.min(endIdx, total);
  if (totalEl) totalEl.textContent = total;

  renderPadjPaginationControls(totalPages);
}

function renderPadjPaginationControls(totalPages) {
  var ul = document.getElementById('padjPagination');
  if (!ul) return;
  ul.innerHTML = '';
  if (totalPages < 1) totalPages = 1;

  var prevLi = document.createElement('li');
  prevLi.className = 'page-item ' + (padjCurrentPage <= 1 ? 'disabled' : '');
  prevLi.innerHTML = '<a class="page-link" href="javascript:void(0)" ' + (padjCurrentPage > 1 ? 'onclick="goToPadjPage(' + (padjCurrentPage - 1) + ')"' : '') + '><i class="fas fa-chevron-left"></i></a>';
  ul.appendChild(prevLi);

  var maxButtons = 5;
  var startPage = Math.max(1, padjCurrentPage - 2);
  var endPage = Math.min(totalPages, startPage + maxButtons - 1);
  if (endPage - startPage < maxButtons - 1) startPage = Math.max(1, endPage - maxButtons + 1);

  if (startPage > 1) {
    var firstLi = document.createElement('li');
    firstLi.className = 'page-item';
    firstLi.innerHTML = '<a class="page-link" href="javascript:void(0)" onclick="goToPadjPage(1)">1</a>';
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
    pageLi.className = 'page-item ' + (p === padjCurrentPage ? 'active' : '');
    pageLi.innerHTML = '<a class="page-link" href="javascript:void(0)" onclick="goToPadjPage(' + p + ')">' + p + '</a>';
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
    lastLi.innerHTML = '<a class="page-link" href="javascript:void(0)" onclick="goToPadjPage(' + totalPages + ')">' + totalPages + '</a>';
    ul.appendChild(lastLi);
  }

  var nextLi = document.createElement('li');
  nextLi.className = 'page-item ' + (padjCurrentPage >= totalPages ? 'disabled' : '');
  nextLi.innerHTML = '<a class="page-link" href="javascript:void(0)" ' + (padjCurrentPage < totalPages ? 'onclick="goToPadjPage(' + (padjCurrentPage + 1) + ')"' : '') + '><i class="fas fa-chevron-right"></i></a>';
  ul.appendChild(nextLi);
}

document.addEventListener('DOMContentLoaded', initPadjPagination);
</script>
