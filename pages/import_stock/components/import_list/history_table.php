<?php
/*
 * ສ່ວນ: ຕາຕະລາງປະຫວັດ
 * ຄົ້ນຫາ, ຍອດລວມ, ຈຳນວນຕໍ່ໜ້າ, ແຕ້ມຕາຕະລາງ + pagination (Excel/PDF/ພິມ: partials/import_list/export.php).
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/import_stock/components/import_list_js.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>  function filterImportHistory() {
    var query = $('#import_search').val().trim().toLowerCase();
    var rows = document.querySelectorAll('#historyTableBody .import-row');
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
    renderHistoryTable();
    updateImportTotal();
  }

  // Sum ມູນຄ່າທຶນ over the current search result and show it in the table footer
  function updateImportTotal() {
    var el = document.getElementById('importGrandTotal');
    if (!el) return;
    var rows = (typeof filteredRows !== 'undefined' && filteredRows.length)
      ? filteredRows
      : document.querySelectorAll('#historyTableBody .import-row');
    var sum = 0;
    rows.forEach(function(r) { sum += parseFloat(r.getAttribute('data-cost')) || 0; });
    el.innerHTML = impFmtFromKip(sum);
  }

  function changePageSize(val) {
    pageSize = parseInt(val);
    currentPage = 1;
    renderHistoryTable();
  }

  function renderHistoryTable() {
    var totalRows = filteredRows.length;
    var totalPages = Math.ceil(totalRows / pageSize) || 1;

    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    var startIdx = (currentPage - 1) * pageSize;
    var endIdx = startIdx + pageSize;

    document.querySelectorAll('#historyTableBody .import-row').forEach(function(r) {
      r.style.display = 'none';
    });

    for (var i = startIdx; i < endIdx && i < totalRows; i++) {
      var r = filteredRows[i];
      r.style.display = '';
      var cellIndex = r.querySelector('.row-index');
      if (cellIndex) cellIndex.textContent = i + 1;
    }

    $('#page_info_start').text(totalRows > 0 ? startIdx + 1 : 0);
    $('#page_info_end').text(Math.min(endIdx, totalRows));
    $('#page_info_total').text(totalRows);

    renderHistoryPagination(totalPages);
  }

  function renderHistoryPagination(totalPages) {
    var container = $('#importPagination');
    container.empty();

    if (totalPages < 1) totalPages = 1;

    var prevDisabled = (currentPage === 1) ? 'disabled' : '';
    container.append(`
      <li class="page-item ${prevDisabled}">
        <a class="page-link" href="javascript:void(0)" onclick="goToHistoryPage(${currentPage - 1})">
          <i class="fas fa-chevron-left"></i>
        </a>
      </li>
    `);

    var maxButtons = 5;
    var startPage = Math.max(1, currentPage - 2);
    var endPage = Math.min(totalPages, startPage + maxButtons - 1);
    if (endPage - startPage < maxButtons - 1) {
      startPage = Math.max(1, endPage - maxButtons + 1);
    }

    if (startPage > 1) {
      container.append(`<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToHistoryPage(1)">1</a></li>`);
      if (startPage > 2) {
        container.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
      }
    }

    for (var p = startPage; p <= endPage; p++) {
      var activeClass = (p === currentPage) ? 'active' : '';
      container.append(`
        <li class="page-item ${activeClass}">
          <a class="page-link" href="javascript:void(0)" onclick="goToHistoryPage(${p})">${p}</a>
        </li>
      `);
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1) {
        container.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
      }
      container.append(`<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToHistoryPage(${totalPages})">${totalPages}</a></li>`);
    }

    var nextDisabled = (currentPage === totalPages) ? 'disabled' : '';
    container.append(`
      <li class="page-item ${nextDisabled}">
        <a class="page-link" href="javascript:void(0)" onclick="goToHistoryPage(${currentPage + 1})">
          <i class="fas fa-chevron-right"></i>
        </a>
      </li>
    `);
  }

  function goToHistoryPage(page) {
    currentPage = page;
    renderHistoryTable();
  }

  var currentModalImportId = 0;


