<?php
/*
 * ສ່ວນ: JS ຂອງໜ້າ
 * ຄົ້ນຫາ, pagination, ເປີດ popup ເພີ່ມ/ແກ້ໄຂ, ບັນທຶກ ແລະ ລົບຜູ້ສະໜອງ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/suppliers/suppliers.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?><script>
var SUP_I18N = <?php echo tjson([
    'suppliers.modal_add_title'  => 'ເພີ່ມຜູ້ສະໜອງ',
    'suppliers.modal_edit_title' => 'ແກ້ໄຂຜູ້ສະໜອງ',
    'suppliers.del_confirm_title' => 'ຢືນຢັນການລົບ',
    'suppliers.del_confirm_text' => 'ຕ້ອງການລົບຜູ້ສະໜອງ "{name}" ບໍ?',
    'suppliers.del_yes' => 'ລົບ',
    'suppliers.del_no' => 'ຍົກເລີກ',
    'suppliers.validate_name' => 'ກະລຸນາປ້ອນຊື່ຜູ້ສະໜອງ!',
    'suppliers.btn_ok' => 'ຕົກລົງ',
]); ?>;

// Live search — filters which rows are eligible, then pagination decides what's actually shown
var supCurrentPage = 1;
var supPageSize = 10;
var supFilteredRows = [];

function filterSuppliers() {
  var q = (document.getElementById('supplierSearch').value || '').toLowerCase().trim();
  var rows = Array.from(document.querySelectorAll('tr.supplier-row'));
  supFilteredRows = rows.filter(function(tr) {
    return q === '' || (tr.getAttribute('data-s') || '').indexOf(q) > -1;
  });

  var noRes = document.getElementById('supplierNoResult');
  if (noRes) noRes.style.display = (supFilteredRows.length === 0 && rows.length > 0) ? '' : 'none';

  supCurrentPage = 1;
  applySupplierPagination();
}

function initSupplierPagination() {
  filterSuppliers();
}

function changeSupplierPageSize() {
  var val = document.getElementById('supPageSizeSelect').value;
  supPageSize = (val === 'all') ? (supFilteredRows.length || 99999) : parseInt(val, 10);
  supCurrentPage = 1;
  applySupplierPagination();
}

function goToSupplierPage(page) {
  var totalPages = Math.ceil(supFilteredRows.length / supPageSize) || 1;
  if (page < 1) page = 1;
  if (page > totalPages) page = totalPages;
  supCurrentPage = page;
  applySupplierPagination();
}

function applySupplierPagination() {
  var allRows = document.querySelectorAll('tr.supplier-row');
  allRows.forEach(function(r) { r.style.display = 'none'; });

  var total = supFilteredRows.length;
  var totalPages = Math.ceil(total / supPageSize) || 1;
  if (supCurrentPage > totalPages) supCurrentPage = totalPages;

  var startIdx = (supCurrentPage - 1) * supPageSize;
  var endIdx = (supPageSize >= 99999) ? total : startIdx + supPageSize;

  var shown = 0;
  for (var i = startIdx; i < endIdx && i < total; i++) {
    if (supFilteredRows[i]) {
      supFilteredRows[i].style.display = '';
      shown++;
      var no = supFilteredRows[i].querySelector('.row-no');
      if (no) no.textContent = startIdx + shown;
    }
  }

  var startEl = document.getElementById('sup_page_info_start');
  var endEl = document.getElementById('sup_page_info_end');
  var totalEl = document.getElementById('sup_page_info_total');
  if (startEl) startEl.textContent = total === 0 ? 0 : startIdx + 1;
  if (endEl) endEl.textContent = Math.min(endIdx, total);
  if (totalEl) totalEl.textContent = total;

  var num = document.getElementById('supplierCountNum');
  if (num) num.textContent = total;

  renderSupplierPaginationControls(totalPages);
}

function renderSupplierPaginationControls(totalPages) {
  var ul = document.getElementById('supplierPagination');
  if (!ul) return;
  ul.innerHTML = '';
  if (totalPages < 1) totalPages = 1;

  var prevLi = document.createElement('li');
  prevLi.className = 'page-item ' + (supCurrentPage <= 1 ? 'disabled' : '');
  prevLi.innerHTML = '<a class="page-link" href="javascript:void(0)" ' + (supCurrentPage > 1 ? 'onclick="goToSupplierPage(' + (supCurrentPage - 1) + ')"' : '') + '><i class="fas fa-chevron-left"></i></a>';
  ul.appendChild(prevLi);

  var maxButtons = 5;
  var startPage = Math.max(1, supCurrentPage - 2);
  var endPage = Math.min(totalPages, startPage + maxButtons - 1);
  if (endPage - startPage < maxButtons - 1) startPage = Math.max(1, endPage - maxButtons + 1);

  if (startPage > 1) {
    var firstLi = document.createElement('li');
    firstLi.className = 'page-item';
    firstLi.innerHTML = '<a class="page-link" href="javascript:void(0)" onclick="goToSupplierPage(1)">1</a>';
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
    pageLi.className = 'page-item ' + (p === supCurrentPage ? 'active' : '');
    pageLi.innerHTML = '<a class="page-link" href="javascript:void(0)" onclick="goToSupplierPage(' + p + ')">' + p + '</a>';
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
    lastLi.innerHTML = '<a class="page-link" href="javascript:void(0)" onclick="goToSupplierPage(' + totalPages + ')">' + totalPages + '</a>';
    ul.appendChild(lastLi);
  }

  var nextLi = document.createElement('li');
  nextLi.className = 'page-item ' + (supCurrentPage >= totalPages ? 'disabled' : '');
  nextLi.innerHTML = '<a class="page-link" href="javascript:void(0)" ' + (supCurrentPage < totalPages ? 'onclick="goToSupplierPage(' + (supCurrentPage + 1) + ')"' : '') + '><i class="fas fa-chevron-right"></i></a>';
  ul.appendChild(nextLi);
}

document.addEventListener('DOMContentLoaded', initSupplierPagination);

function openSupplierModal(s) {
  var isEdit = !!(s && s.supplier_id);
  $('#supplier_action').val(isEdit ? 'edit_supplier' : 'add_supplier');
  $('#supplier_id').val(isEdit ? s.supplier_id : '');
  $('#supplierModalTitle').html('<i class="fas fa-truck mr-2"></i> ' + (isEdit ? SUP_I18N['suppliers.modal_edit_title'] : SUP_I18N['suppliers.modal_add_title']));
  $('#f_supplier_name').val(isEdit ? (s.supplier_name || '') : '');
  $('#f_phone').val(isEdit ? (s.phone || '') : '');
  $('#f_contact_person').val(isEdit ? (s.contact_person || '') : '');
  $('#f_address').val(isEdit ? (s.address || '') : '');
  $('#f_notes').val(isEdit ? (s.notes || '') : '');
  $('#f_status').val(isEdit ? (s.status || 'active') : 'active');
  $('#supplierModal').modal('show');
}

$('#supplierForm').on('submit', function(e) {
  if ($('#f_supplier_name').val().trim() === '') {
    e.preventDefault();
    Swal.fire({ icon: 'warning', title: SUP_I18N['suppliers.btn_ok'], text: SUP_I18N['suppliers.validate_name'], confirmButtonColor: '#2563eb', confirmButtonText: SUP_I18N['suppliers.btn_ok'] })
      .then(function() { $('#f_supplier_name').focus(); });
    return false;
  }
});

function deleteSupplier(id, name) {
  Swal.fire({
    icon: 'warning',
    title: SUP_I18N['suppliers.del_confirm_title'],
    text: SUP_I18N['suppliers.del_confirm_text'].replace('{name}', name),
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#64748b',
    confirmButtonText: SUP_I18N['suppliers.del_yes'],
    cancelButtonText: SUP_I18N['suppliers.del_no']
  }).then(function(res) {
    if (res.isConfirmed) {
      var f = $('<form method="POST" action=""></form>');
      f.append('<input type="hidden" name="action" value="delete_supplier">');
      f.append('<input type="hidden" name="supplier_id" value="' + id + '">');
      $('body').append(f);
      f.trigger('submit');
    }
  });
}
</script>
