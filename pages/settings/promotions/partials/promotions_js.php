<script>
// Localized strings for the promotions client-side script. Keyed by the full "promotions.*"
// translation keys so the dashboard's flag switcher can re-point this dict live (no reload).
var PROMO_I18N = <?php echo tjson([
    'promotions.opt_target_all'        => 'ທຸກສິນຄ້າ',
    'promotions.js_warn_title'         => 'ແຈ້ງເຕືອນ',
    'promotions.js_error_title'        => 'ຜິດພາດ',
    'promotions.js_status_updated'     => 'ອັບເດດສະຖານະສຳເລັດ!',
    'promotions.js_generic_error'      => 'ຜິດພາດ!',
    'promotions.toggle_disable'        => 'ປິດໃຊ້ງານ',
    'promotions.toggle_enable'         => 'ເປີດໃຊ້ງານ',
    'promotions.js_confirm_delete_title' => 'ຢືນຢັນການລົບ?',
    'promotions.js_confirm_delete_text'  => 'ຕ້ອງການລົບໂປຣໂມຊັ່ນນີ້ແທ້ຫຼືບໍ່?',
    'promotions.js_confirm_delete_yes'   => 'ລົບເລີຍ',
    'promotions.btn_cancel'            => 'ຍົກເລີກ',
    'promotions.js_deleted_success'    => 'ລົບໂປຣໂມຊັ່ນສຳເລັດ!',
    'promotions.js_done'               => 'ດຳເນີນການສຳເລັດ!',
    'promotions.target_label_all'      => 'ຊື່ ສິນຄ້າ/ປະເພດ:',
    'promotions.target_label_category' => 'ຊື່ ໝວດໝູ່ສິນຄ້າ:',
    'promotions.target_label_product'  => 'ລະຫັດ ຫຼື ຊື່ ສິນຄ້າ:',
]); ?>;

// Product directory for the target field: lets the user type a barcode / code and see the
// matching product name underneath, so they know exactly which item the promo will hit.
var PROMO_PRODUCTS = <?php echo json_encode(array_values($productsLookup ?? []), JSON_UNESCAPED_UNICODE); ?>;

function promoLookupProduct(val) {
  val = (val || '').trim();
  if (!val) return null;
  var low = val.toLowerCase();
  return PROMO_PRODUCTS.find(function (p) {
    return (p.barcode && p.barcode === val) ||
           (p.code && p.code === val) ||
           (p.name && p.name.toLowerCase() === low);
  }) || null;
}

// Show the resolved product name (or a "not found" note) under the target-name input.
function promoUpdateTargetHint(mode) {
  var typeEl = document.getElementById(mode + '_target_type');
  var inEl   = document.getElementById(mode + '_target_name');
  var hintEl = document.getElementById(mode + '_target_name_hint');
  if (!inEl || !hintEl) return;
  var val = inEl.value.trim();
  if (!typeEl || typeEl.value !== 'product' || val === '' || val === PROMO_I18N['promotions.opt_target_all']) {
    hintEl.textContent = '';
    promoFillUnitDatalist(null);
    return;
  }
  var hit = promoLookupProduct(val);
  if (hit) {
    var us = (hit.units && hit.units.length) ? ' — ຫົວໜ່ວຍ: ' + hit.units.join(', ') : '';
    hintEl.textContent = '✓ ' + hit.name + us;
    hintEl.style.color = '#16a34a';
    promoFillUnitDatalist(hit.units || []);
  } else {
    hintEl.textContent = 'ບໍ່ພົບສິນຄ້ານີ້ໃນລະບົບ';
    hintEl.style.color = '#f59e0b';
    promoFillUnitDatalist(null);
  }
}

// Repopulate #units_datalist with the chosen product's real unit names (ແກ້ວ / ປ໋ອງຍາວ / ...)
// so the "ສະເພາະ ຫົວໜ່ວຍ" field offers exactly what exists. null = restore the generic list.
var PROMO_UNITS_DEFAULT = ['all', 'ລັງ', 'ແກ້ວ', 'ເເກັດ', 'ປ໋ອງ', 'ຖົງ', 'ຊິ້ນ', 'ອັນ'];
function promoFillUnitDatalist(units) {
  var dl = document.getElementById('units_datalist');
  if (!dl) return;
  var list = (units && units.length) ? ['all'].concat(units) : PROMO_UNITS_DEFAULT;
  dl.innerHTML = list.map(function (u) {
    return '<option value="' + String(u).replace(/"/g, '&quot;') + '"></option>';
  }).join('');
}

function formatNumberInput(input) {
  let val = input.value.replace(/[^0-9.]/g, '');
  if (val === '') {
    input.value = '';
    return;
  }
  let parts = val.split('.');
  parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  if (parts.length > 2) {
    parts = [parts[0], parts.slice(1).join('')];
  }
  input.value = parts.join('.');
}

function formatNumVal(val) {
  if (val === null || val === undefined || val === '') return '0';
  let num = Number(val);
  if (isNaN(num) || num === 0) return '0';
  return num.toLocaleString('en-US');
}

function togglePromoType(val, mode) {
  var isGift = (val === 'buy_x_get_y');
  var giftSec = document.getElementById(mode + '_gift_section');
  if (giftSec) {
    giftSec.style.display = isGift ? 'flex' : 'none';
  }
  // A pure freebie promo needs no money value — don't let the required field block the save.
  var modal = document.getElementById(mode + 'PromoModal');
  if (modal) {
    var dv = modal.querySelector('[name="discount_value"]');
    if (dv) {
      if (isGift) {
        dv.removeAttribute('required');
        if (dv.value.trim() === '') dv.value = '0';
      } else {
        dv.setAttribute('required', 'required');
      }
      var dvGroup = dv.closest('.mb-3') || dv.parentElement;
      var star = dvGroup ? dvGroup.querySelector('label .text-danger') : null;
      if (star) star.style.display = isGift ? 'none' : '';
    }
    var gn = modal.querySelector('[name="gift_product_name"]');
    if (gn) {
      if (isGift) gn.setAttribute('required', 'required');
      else gn.removeAttribute('required');
    }
  }
}

function toggleTargetInput(val, mode) {
  var inputEl = document.getElementById(mode + '_target_name');
  if (val === 'all') {
    inputEl.value = PROMO_I18N['promotions.opt_target_all'];
    inputEl.removeAttribute('list');
  } else if (val === 'category') {
    if (inputEl.value === PROMO_I18N['promotions.opt_target_all']) inputEl.value = '';
    inputEl.setAttribute('list', 'category_datalist');
  } else if (val === 'product') {
    if (inputEl.value === PROMO_I18N['promotions.opt_target_all']) inputEl.value = '';
    inputEl.setAttribute('list', 'product_datalist');
  }
  var lblEl = document.getElementById(mode + '_target_name_label');
  if (lblEl) {
    lblEl.textContent = (val === 'product') ? PROMO_I18N['promotions.target_label_product']
                      : (val === 'category') ? PROMO_I18N['promotions.target_label_category']
                      : PROMO_I18N['promotions.target_label_all'];
  }
  promoUpdateTargetHint(mode);
}

document.addEventListener('DOMContentLoaded', function () {
  ['add', 'edit'].forEach(function (mode) {
    var el = document.getElementById(mode + '_target_name');
    if (el) el.addEventListener('input', function () { promoUpdateTargetHint(mode); });
  });
});

function editPromo(p) {
  document.getElementById('edit_promo_id').value = p.id;
  document.getElementById('edit_promo_name').value = p.promo_name || '';
  document.getElementById('edit_promo_type').value = p.promo_type || 'discount';
  document.getElementById('edit_discount_type').value = p.discount_type || 'percentage';
  document.getElementById('edit_discount_value').value = formatNumVal(p.discount_value);
  if (document.getElementById('edit_gift_product_name')) {
    document.getElementById('edit_gift_product_name').value = p.gift_product_name || '';
  }
  if (document.getElementById('edit_gift_qty')) {
    document.getElementById('edit_gift_qty').value = p.gift_qty || 1;
  }
  document.getElementById('edit_start_date').value = p.start_date || '';
  document.getElementById('edit_end_date').value = p.end_date || '';
  document.getElementById('edit_min_qty').value = formatNumVal(p.min_qty);
  document.getElementById('edit_min_amount').value = formatNumVal(p.min_amount);
  document.getElementById('edit_target_type').value = p.target_type || 'all';
  document.getElementById('edit_target_name').value = p.target_name || PROMO_I18N['promotions.opt_target_all'];
  if (document.getElementById('edit_target_unit_name')) {
    document.getElementById('edit_target_unit_name').value = p.target_unit_name || 'all';
  }
  if (document.getElementById('edit_branch_id')) {
    document.getElementById('edit_branch_id').value = (p.branch_id !== undefined && p.branch_id !== null) ? p.branch_id : 1;
  }
  toggleTargetInput(p.target_type || 'all', 'edit');
  togglePromoType(p.promo_type || 'discount', 'edit');
  $('#editPromoModal').modal('show');
}

function refreshPromotionsTable() {
  var url = 'index.php?fetch_table=1';
  var storeVal = $('select[name="branch_id"]').val();
  if (storeVal) url += '&branch_id=' + storeVal;

  $.get(url, function(html) {
    var $newContent = $(html);
    var $tableCard = $('.card.border-0.shadow-sm');
    if ($tableCard.length) {
      $tableCard.replaceWith($newContent);
      initPromoPagination();
    } else {
      location.reload();
    }
  });
}

function togglePromoStatus(id, newStatus, btn) {
  $.post('index.php', { action: 'toggle_status', id: id, status: newStatus, is_ajax: 1 }, function(res) {
    if (res.success) {
      var track = btn.querySelector('div');
      var dot = track ? track.querySelector('div') : null;
      if (track && dot) {
        if (newStatus == 1) {
          track.style.background = '#10b981';
          dot.style.right = '3px';
          dot.style.left = 'auto';
          btn.setAttribute('onclick', 'togglePromoStatus(' + id + ', 0, this)');
          btn.setAttribute('title', PROMO_I18N['promotions.toggle_disable']);
        } else {
          track.style.background = '#cbd5e1';
          dot.style.left = '3px';
          dot.style.right = 'auto';
          btn.setAttribute('onclick', 'togglePromoStatus(' + id + ', 1, this)');
          btn.setAttribute('title', PROMO_I18N['promotions.toggle_enable']);
        }
      }
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: res.message || PROMO_I18N['promotions.js_status_updated'],
        showConfirmButton: false,
        timer: 1500
      });
    } else {
      Swal.fire({ icon: 'warning', title: PROMO_I18N['promotions.js_warn_title'], text: res.message || PROMO_I18N['promotions.js_generic_error'] });
    }
  }, 'json');
}

function deletePromo(id, btn) {
  Swal.fire({
    title: PROMO_I18N['promotions.js_confirm_delete_title'],
    text: PROMO_I18N['promotions.js_confirm_delete_text'],
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: PROMO_I18N['promotions.js_confirm_delete_yes'],
    cancelButtonText: PROMO_I18N['promotions.btn_cancel']
  }).then(function(r) {
    if (r.isConfirmed) {
      $.post('index.php', { action: 'delete_promo', id: id, is_ajax: 1 }, function(res) {
        if (res.success) {
          $(btn).closest('tr').fadeOut(300, function() { $(this).remove(); initPromoPagination(); });
          Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: PROMO_I18N['promotions.js_deleted_success'],
            showConfirmButton: false,
            timer: 1500
          });
        } else {
          Swal.fire({ icon: 'error', title: PROMO_I18N['promotions.js_error_title'], text: res.message || PROMO_I18N['promotions.js_generic_error'] });
        }
      }, 'json');
    }
  });
}

$(document).ready(function() {
  $('#addPromoModal form, #editPromoModal form').on('submit', function(e) {
    e.preventDefault();
    var $form = $(this);
    var formData = $form.serialize() + '&is_ajax=1';
    $.post('index.php', formData, function(res) {
      if (res.success) {
        $form.closest('.modal').modal('hide');
        refreshPromotionsTable();
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'success',
          title: res.message || PROMO_I18N['promotions.js_done'],
          showConfirmButton: false,
          timer: 1500
        });
      } else {
        Swal.fire({ icon: 'error', title: PROMO_I18N['promotions.js_error_title'], text: res.message || PROMO_I18N['promotions.js_generic_error'] });
      }
    }, 'json');
  });
});

// ====== Pagination (client-side; re-init after any AJAX table swap or row removal) ======
var promoCurrentPage = 1;
var promoPageSize = 10;
var promoFilteredRows = [];

function initPromoPagination() {
  promoFilteredRows = Array.from(document.querySelectorAll('#promoTable tbody tr.promo-row'));
  promoCurrentPage = 1;
  applyPromoPagination();
}

function changePromoPageSize() {
  var val = document.getElementById('promoPageSizeSelect').value;
  promoPageSize = (val === 'all') ? (promoFilteredRows.length || 99999) : parseInt(val, 10);
  promoCurrentPage = 1;
  applyPromoPagination();
}

function goToPromoPage(page) {
  var totalPages = Math.ceil(promoFilteredRows.length / promoPageSize) || 1;
  if (page < 1) page = 1;
  if (page > totalPages) page = totalPages;
  promoCurrentPage = page;
  applyPromoPagination();
}

function applyPromoPagination() {
  var allRows = document.querySelectorAll('#promoTable tbody tr.promo-row');
  allRows.forEach(function(r) { r.style.display = 'none'; });

  var total = promoFilteredRows.length;
  var totalPages = Math.ceil(total / promoPageSize) || 1;
  if (promoCurrentPage > totalPages) promoCurrentPage = totalPages;

  var startIdx = (promoCurrentPage - 1) * promoPageSize;
  var endIdx = (promoPageSize >= 99999) ? total : startIdx + promoPageSize;

  for (var i = startIdx; i < endIdx && i < total; i++) {
    if (promoFilteredRows[i]) promoFilteredRows[i].style.display = '';
  }

  var startEl = document.getElementById('promo_page_info_start');
  var endEl = document.getElementById('promo_page_info_end');
  var totalEl = document.getElementById('promo_page_info_total');
  if (startEl) startEl.textContent = total === 0 ? 0 : startIdx + 1;
  if (endEl) endEl.textContent = Math.min(endIdx, total);
  if (totalEl) totalEl.textContent = total;

  renderPromoPaginationControls(totalPages);
}

function renderPromoPaginationControls(totalPages) {
  var ul = document.getElementById('promoPagination');
  if (!ul) return;
  ul.innerHTML = '';
  if (totalPages < 1) totalPages = 1;

  var prevLi = document.createElement('li');
  prevLi.className = 'page-item ' + (promoCurrentPage <= 1 ? 'disabled' : '');
  prevLi.innerHTML = '<a class="page-link" href="javascript:void(0)" ' + (promoCurrentPage > 1 ? 'onclick="goToPromoPage(' + (promoCurrentPage - 1) + ')"' : '') + '><i class="fas fa-chevron-left"></i></a>';
  ul.appendChild(prevLi);

  var maxButtons = 5;
  var startPage = Math.max(1, promoCurrentPage - 2);
  var endPage = Math.min(totalPages, startPage + maxButtons - 1);
  if (endPage - startPage < maxButtons - 1) startPage = Math.max(1, endPage - maxButtons + 1);

  if (startPage > 1) {
    var firstLi = document.createElement('li');
    firstLi.className = 'page-item';
    firstLi.innerHTML = '<a class="page-link" href="javascript:void(0)" onclick="goToPromoPage(1)">1</a>';
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
    pageLi.className = 'page-item ' + (p === promoCurrentPage ? 'active' : '');
    pageLi.innerHTML = '<a class="page-link" href="javascript:void(0)" onclick="goToPromoPage(' + p + ')">' + p + '</a>';
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
    lastLi.innerHTML = '<a class="page-link" href="javascript:void(0)" onclick="goToPromoPage(' + totalPages + ')">' + totalPages + '</a>';
    ul.appendChild(lastLi);
  }

  var nextLi = document.createElement('li');
  nextLi.className = 'page-item ' + (promoCurrentPage >= totalPages ? 'disabled' : '');
  nextLi.innerHTML = '<a class="page-link" href="javascript:void(0)" ' + (promoCurrentPage < totalPages ? 'onclick="goToPromoPage(' + (promoCurrentPage + 1) + ')"' : '') + '><i class="fas fa-chevron-right"></i></a>';
  ul.appendChild(nextLi);
}

document.addEventListener('DOMContentLoaded', initPromoPagination);
</script>
