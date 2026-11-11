<?php
// Component: Stock Transfer History JavaScript Logic
?>
<script>
var I18N_TRANSFER_HISTORY = <?php echo tjson([
    'transfer_history.status_completed' => 'ສຳເລັດ',
    'transfer_history.status_cancelled' => 'ຍົກເລີກ',
    'transfer_history.default_unit' => 'ອັນ',
    'transfer_history.js_error_title' => 'ຜິດພາດ',
    'transfer_history.js_load_failed' => 'ບໍ່ສາມາດໂຫຼດຂໍ້ມູນໄດ້',
    'transfer_history.js_connection_error' => 'ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່',
    'transfer_history.js_cancel_confirm_title' => 'ຢືນຢັນການຍົກເລີກໃບໂອນ?',
    'transfer_history.js_transfer_code_label' => 'ເລກທີໃບໂອນ:',
    'transfer_history.js_cancel_confirm_hint' => 'ລະບົບຈະຫັກສະຕັອກຄືນຈາກສາຂາປາຍທາງ ແລະ ເພີ່ມຄືນໃຫ້ສາຂາຕົ້ນທາງ.',
    'transfer_history.js_confirm_cancel_btn' => 'ຢືນຢັນຍົກເລີກ',
    'transfer_history.js_close_btn' => 'ປິດ',
    'transfer_history.js_cancel_success' => 'ຍົກເລີກໃບໂອນສຳເລັດ!',
    'transfer_history.js_generic_error' => 'ຜິດພາດ!',
]); ?>;

var currentPage = 1;
var pageSize = 10;
var filteredRows = [];

$(document).ready(function() {
  // Run initial filter and pagination setup on page load
  filterTransferHistory();

  // Instant client-side search key binding
  $('input[name="search"]').on('keyup', function() {
    filterTransferHistory();
  });
});

function filterTransferHistory() {
  var query = $('input[name="search"]').val().trim().toLowerCase();
  var rows = document.querySelectorAll('#historyTableBody .transfer-row');
  filteredRows = [];

  rows.forEach(function(row) {
    var searchStr = row.getAttribute('data-search') || '';
    if (!query || searchStr.indexOf(query) !== -1) {
      filteredRows.push(row);
    } else {
      row.style.display = 'none';
    }
  });

  currentPage = 1;
  renderTransferTable();
}

function renderTransferTable() {
  var totalRows = filteredRows.length;
  var totalPages = Math.ceil(totalRows / pageSize) || 1;

  if (currentPage > totalPages) currentPage = totalPages;
  if (currentPage < 1) currentPage = 1;

  var startIdx = (currentPage - 1) * pageSize;
  var endIdx = startIdx + pageSize;

  // Hide all rows first
  document.querySelectorAll('#historyTableBody .transfer-row').forEach(function(r) {
    r.style.display = 'none';
  });

  // Show only paged rows
  for (var i = startIdx; i < endIdx && i < totalRows; i++) {
    var r = filteredRows[i];
    r.style.display = '';
    var cellIndex = r.querySelector('.row-index');
    if (cellIndex) cellIndex.textContent = i + 1;
  }

  // Update Page info description
  $('#page_info_start').text(totalRows > 0 ? startIdx + 1 : 0);
  $('#page_info_end').text(Math.min(endIdx, totalRows));
  $('#page_info_total').text(totalRows);

  renderTransferPagination(totalPages);
}

function renderTransferPagination(totalPages) {
  var container = $('#transferPagination');
  container.empty();

  if (totalPages < 1) totalPages = 1;

  var prevDisabled = (currentPage === 1) ? 'disabled' : '';
  container.append(
    '<li class="page-item ' + prevDisabled + '">' +
      '<a class="page-link" href="javascript:void(0)" onclick="goToTransferPage(' + (currentPage - 1) + ')">' +
        '<i class="fas fa-chevron-left"></i>' +
      '</a>' +
    '</li>'
  );

  var maxButtons = 5;
  var startPage = Math.max(1, currentPage - 2);
  var endPage = Math.min(totalPages, startPage + maxButtons - 1);
  if (endPage - startPage < maxButtons - 1) {
    startPage = Math.max(1, endPage - maxButtons + 1);
  }

  if (startPage > 1) {
    container.append('<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToTransferPage(1)">1</a></li>');
    if (startPage > 2) {
      container.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
    }
  }

  for (var p = startPage; p <= endPage; p++) {
    var activeClass = (p === currentPage) ? 'active' : '';
    container.append(
      '<li class="page-item ' + activeClass + '">' +
        '<a class="page-link" href="javascript:void(0)" onclick="goToTransferPage(' + p + ')">' + p + '</a>' +
      '</li>'
    );
  }

  if (endPage < totalPages) {
    if (endPage < totalPages - 1) {
      container.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
    }
    container.append('<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToTransferPage(' + totalPages + ')">' + totalPages + '</a></li>');
  }

  var nextDisabled = (currentPage === totalPages) ? 'disabled' : '';
  container.append(
    '<li class="page-item ' + nextDisabled + '">' +
      '<a class="page-link" href="javascript:void(0)" onclick="goToTransferPage(' + (currentPage + 1) + ')">' +
        '<i class="fas fa-chevron-right"></i>' +
      '</a>' +
    '</li>'
  );
}

function goToTransferPage(p) {
  currentPage = p;
  renderTransferTable();
}

function viewTransferDetails(transferId) {
  $.ajax({
    url: '../../api/stock_transfer_backend.php',
    type: 'GET',
    data: { action: 'get_transfer_details', transfer_id: transferId },
    dataType: 'json',
    success: function(res) {
      if (res.success) {
        var trf = res.transfer;
        $('#modalTransferCode').text(trf.transfer_code);
        $('#modalFromStore').text(trf.from_store_name);
        $('#modalToStore').text(trf.to_store_name);
        $('#modalDate').text(trf.transfer_date);
        $('#modalCreator').text(trf.creator_name || 'Admin');
        
        var statusHtml = '';
        if (trf.status === 'completed') {
          statusHtml = '<span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i>' + I18N_TRANSFER_HISTORY['transfer_history.status_completed'] + '</span>';
        } else {
          statusHtml = '<span class="badge badge-danger px-2 py-1"><i class="fas fa-times-circle mr-1"></i>' + I18N_TRANSFER_HISTORY['transfer_history.status_cancelled'] + '</span>';
        }
        $('#modalStatus').html(statusHtml);

        if (trf.notes && trf.notes.trim() !== '') {
          $('#modalNotesText').text(trf.notes);
          $('#modalNotesBox').removeClass('d-none');
        } else {
          $('#modalNotesBox').addClass('d-none');
        }

        // Render detail table
        var tbody = $('#modalDetailsTableBody');
        tbody.empty();
        res.details.forEach(function(item, idx) {
          tbody.append(
            '<tr>' +
              '<td class="text-center align-middle font-weight-bold text-muted">' + (idx + 1) + '</td>' +
              '<td class="align-middle"><strong>' + escapeHtml(item.product_name) + '</strong></td>' +
              '<td class="text-center align-middle font-weight-bold text-secondary">' + escapeHtml(item.barcode || item.prod_barcode || '-') + '</td>' +
              '<td class="text-center align-middle font-weight-bold text-dark">' + item.qty + '</td>' +
              '<td class="text-center align-middle">' + escapeHtml(item.unit || I18N_TRANSFER_HISTORY['transfer_history.default_unit']) + '</td>' +
            '</tr>'
          );
        });

        $('#transferDetailsModal').modal('show');
      } else {
        Swal.fire(I18N_TRANSFER_HISTORY['transfer_history.js_error_title'], res.message || I18N_TRANSFER_HISTORY['transfer_history.js_load_failed'], 'error');
      }
    },
    error: function() {
      Swal.fire(I18N_TRANSFER_HISTORY['transfer_history.js_error_title'], I18N_TRANSFER_HISTORY['transfer_history.js_connection_error'], 'error');
    }
  });
}

function confirmCancelTransfer(transferId, transferCode) {
  Swal.fire({
    title: I18N_TRANSFER_HISTORY['transfer_history.js_cancel_confirm_title'],
    html: '<div style="font-size:0.9rem; color:#ef4444; font-weight:bold;">' + I18N_TRANSFER_HISTORY['transfer_history.js_transfer_code_label'] + ' ' + transferCode + '</div><div style="font-size:0.85rem; color:#475569; margin-top:5px;">' + I18N_TRANSFER_HISTORY['transfer_history.js_cancel_confirm_hint'] + '</div>',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: I18N_TRANSFER_HISTORY['transfer_history.js_confirm_cancel_btn'],
    cancelButtonText: I18N_TRANSFER_HISTORY['transfer_history.js_close_btn']
  }).then(function(result) {
    if (result.isConfirmed) {
      $.post('transfer_history.php', { action: 'cancel_transfer', transfer_id: transferId, is_ajax: 1 }, function(res) {
        if (res.success) {
          Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: res.message || I18N_TRANSFER_HISTORY['transfer_history.js_cancel_success'],
            showConfirmButton: false,
            timer: 1500
          });
          $.get('transfer_history.php?fetch_table=1', function(html) {
            var $tableCard = $('#historyTableBody').closest('.card');
            if ($tableCard.length) {
              $tableCard.replaceWith($(html));
              filterTransferHistory();
            }
          });
        } else {
          Swal.fire({ icon: 'error', title: I18N_TRANSFER_HISTORY['transfer_history.js_error_title'], text: res.message || I18N_TRANSFER_HISTORY['transfer_history.js_generic_error'] });
        }
      }, 'json');
    }
  });
}

function escapeHtml(text) {
  if (!text) return '';
  return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>
