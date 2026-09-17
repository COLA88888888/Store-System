<?php
// pages/branches/partials/js/branches_js.php
if (!defined('MINIPOS_APP')) {
    define('MINIPOS_APP', true);
}
?>
<script>
var I18N_BRANCHES = <?php echo tjson([
    'branches.action_close' => 'ປິດສາຂາ',
    'branches.action_open' => 'ເປີດໃຊ້ງານສາຂາ',
    'branches.confirm_close_text' => 'ທ່ານຕ້ອງການປິດສາຂາ "{name}" ບໍ່?',
    'branches.confirm_open_text' => 'ທ່ານຕ້ອງການເປີດໃຊ້ງານສາຂາ "{name}" ບໍ່?',
    'branches.yes_prefix' => 'ແມ່ນແລ້ວ, ',
    'branches.btn_cancel' => 'ຍົກເລີກ',
    'branches.delete_title' => 'ລົບສາຂາ?',
    'branches.confirm_delete_text' => 'ທ່ານຕ້ອງການລົບສາຂາ "{name}" ຫຼືບໍ່? ການກະທຳນີ້ບໍ່ສາມາດຍົກເລີກໄດ້!',
    'branches.btn_delete_now' => 'ລົບເລີຍ',
]); ?>;

function openEditBranchModal(data) {
  if (!data) return;
  $('#edit_store_id').val(data.store_id);
  $('#edit_store_code').val(data.store_code || '');
  $('#edit_store_name').val(data.store_name || '');
  $('#edit_store_tel').val(data.tel || '');
  $('#edit_store_address').val(data.address || '');
  $('#edit_is_main').prop('checked', parseInt(data.is_main) === 1);
  $('#editBranchModal').modal('show');
}

function confirmToggleBranchStatus(id, name, status) {
  var actionText = (status === 'active') ? I18N_BRANCHES['branches.action_close'] : I18N_BRANCHES['branches.action_open'];
  var questionText = (status === 'active')
    ? I18N_BRANCHES['branches.confirm_close_text'].replace('{name}', name)
    : I18N_BRANCHES['branches.confirm_open_text'].replace('{name}', name);
  var iconType = (status === 'active') ? 'warning' : 'question';
  var confirmBtnColor = (status === 'active') ? '#ef4444' : '#10b981';

  Swal.fire({
    title: actionText + '?',
    text: questionText,
    icon: iconType,
    showCancelButton: true,
    confirmButtonColor: confirmBtnColor,
    cancelButtonColor: '#64748b',
    confirmButtonText: I18N_BRANCHES['branches.yes_prefix'] + actionText,
    cancelButtonText: I18N_BRANCHES['branches.btn_cancel']
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = '?toggle_status_id=' + id;
    }
  });
}

function confirmDeleteBranch(id, name) {
  Swal.fire({
    title: I18N_BRANCHES['branches.delete_title'],
    text: I18N_BRANCHES['branches.confirm_delete_text'].replace('{name}', name),
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> ' + I18N_BRANCHES['branches.btn_delete_now'],
    cancelButtonText: I18N_BRANCHES['branches.btn_cancel']
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = '?delete_branch_id=' + id;
    }
  });
}

// ====== Pagination (client-side, all rows already rendered) ======
var branchCurrentPage = 1;
var branchPageSize = 10;
var branchFilteredRows = [];

function initBranchPagination() {
  branchFilteredRows = Array.from(document.querySelectorAll('#branchTable tbody tr.branch-row'));
  branchCurrentPage = 1;
  applyBranchPagination();
}

function changeBranchPageSize() {
  var val = document.getElementById('branchPageSizeSelect').value;
  branchPageSize = (val === 'all') ? (branchFilteredRows.length || 99999) : parseInt(val, 10);
  branchCurrentPage = 1;
  applyBranchPagination();
}

function goToBranchPage(page) {
  var totalPages = Math.ceil(branchFilteredRows.length / branchPageSize) || 1;
  if (page < 1) page = 1;
  if (page > totalPages) page = totalPages;
  branchCurrentPage = page;
  applyBranchPagination();
}

function applyBranchPagination() {
  var allRows = document.querySelectorAll('#branchTable tbody tr.branch-row');
  allRows.forEach(function(r) { r.style.display = 'none'; });

  var total = branchFilteredRows.length;
  var totalPages = Math.ceil(total / branchPageSize) || 1;
  if (branchCurrentPage > totalPages) branchCurrentPage = totalPages;

  var startIdx = (branchCurrentPage - 1) * branchPageSize;
  var endIdx = (branchPageSize >= 99999) ? total : startIdx + branchPageSize;

  for (var i = startIdx; i < endIdx && i < total; i++) {
    if (branchFilteredRows[i]) branchFilteredRows[i].style.display = '';
  }

  var startEl = document.getElementById('branch_page_info_start');
  var endEl = document.getElementById('branch_page_info_end');
  var totalEl = document.getElementById('branch_page_info_total');
  if (startEl) startEl.textContent = total === 0 ? 0 : startIdx + 1;
  if (endEl) endEl.textContent = Math.min(endIdx, total);
  if (totalEl) totalEl.textContent = total;

  renderBranchPaginationControls(totalPages);
}

function renderBranchPaginationControls(totalPages) {
  var ul = document.getElementById('branchPagination');
  if (!ul) return;
  ul.innerHTML = '';
  if (totalPages < 1) totalPages = 1;

  var prevLi = document.createElement('li');
  prevLi.className = 'page-item ' + (branchCurrentPage <= 1 ? 'disabled' : '');
  prevLi.innerHTML = '<a class="page-link" href="javascript:void(0)" ' + (branchCurrentPage > 1 ? 'onclick="goToBranchPage(' + (branchCurrentPage - 1) + ')"' : '') + '><i class="fas fa-chevron-left"></i></a>';
  ul.appendChild(prevLi);

  var maxButtons = 5;
  var startPage = Math.max(1, branchCurrentPage - 2);
  var endPage = Math.min(totalPages, startPage + maxButtons - 1);
  if (endPage - startPage < maxButtons - 1) startPage = Math.max(1, endPage - maxButtons + 1);

  if (startPage > 1) {
    var firstLi = document.createElement('li');
    firstLi.className = 'page-item';
    firstLi.innerHTML = '<a class="page-link" href="javascript:void(0)" onclick="goToBranchPage(1)">1</a>';
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
    pageLi.className = 'page-item ' + (p === branchCurrentPage ? 'active' : '');
    pageLi.innerHTML = '<a class="page-link" href="javascript:void(0)" onclick="goToBranchPage(' + p + ')">' + p + '</a>';
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
    lastLi.innerHTML = '<a class="page-link" href="javascript:void(0)" onclick="goToBranchPage(' + totalPages + ')">' + totalPages + '</a>';
    ul.appendChild(lastLi);
  }

  var nextLi = document.createElement('li');
  nextLi.className = 'page-item ' + (branchCurrentPage >= totalPages ? 'disabled' : '');
  nextLi.innerHTML = '<a class="page-link" href="javascript:void(0)" ' + (branchCurrentPage < totalPages ? 'onclick="goToBranchPage(' + (branchCurrentPage + 1) + ')"' : '') + '><i class="fas fa-chevron-right"></i></a>';
  ul.appendChild(nextLi);
}

document.addEventListener('DOMContentLoaded', initBranchPagination);
</script>
