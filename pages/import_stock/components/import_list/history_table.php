<?php
/*
 * ສ່ວນ: ຕາຕະລາງປະຫວັດ ແລະ Excel
 * ຄົ້ນຫາ, ຍອດລວມ, ຈຳນວນຕໍ່ໜ້າ, ແຕ້ມຕາຕະລາງ + pagination ແລະ ສົ່ງອອກ Excel.
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

  function exportToExcel() {
    var table = document.querySelector('.table-responsive table');
    if (!table) return;

    var cloneTable = table.cloneNode(true);

    // Remove action column (last column) from header and body
    cloneTable.querySelectorAll('tr').forEach(function(row) {
      if (row.children.length > 0) {
        row.removeChild(row.lastElementChild);
      }
    });

    var F = "font-family: 'Phetsarath OT', 'Noto Sans Lao', sans-serif;";
    var B = "border: .5pt solid #000;";

    cloneTable.querySelectorAll('thead tr').forEach(function(tr) {
      tr.style.cssText = 'height: 28pt; mso-height-source: userset;';
      tr.setAttribute('height', '37');
    });
    cloneTable.querySelectorAll('thead th').forEach(function(th) {
      th.style.cssText = F + B + 'height: 28pt; font-size: 13pt; font-weight: bold; text-align: center; vertical-align: middle; background-color: #e5e7eb; padding: 6px 10px; white-space: nowrap;';
    });

    cloneTable.querySelectorAll('tbody tr').forEach(function(tr) {
      tr.style.cssText = 'height: 24pt; mso-height-source: userset;';
      tr.setAttribute('height', '32');
    });
    cloneTable.querySelectorAll('tbody td').forEach(function(td) {
      td.style.cssText = F + B + 'height: 24pt; font-size: 11pt; vertical-align: middle; padding: 6px 10px; white-space: nowrap;';
    });

    cloneTable.querySelectorAll('tfoot tr').forEach(function(tr) {
      tr.style.cssText = 'height: 26pt; mso-height-source: userset;';
      tr.setAttribute('height', '34');
    });
    cloneTable.querySelectorAll('tfoot td').forEach(function(td) {
      td.style.cssText = F + B + 'height: 26pt; font-size: 12pt; font-weight: bold; background-color: #f3f4f6; vertical-align: middle; padding: 6px 10px; white-space: nowrap;';
    });

    var tableHtml = cloneTable.outerHTML;

    var excelDoc = `
      <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
      <head>
        <meta charset="utf-8">
        <!--[if gte mso 9]>
        <xml>
          <x:ExcelWorkbook>
            <x:ExcelWorksheets>
              <x:ExcelWorksheet>
                <x:Name>${I18N_IMPORT_LIST['import_list.js_export_name']}</x:Name>
                <x:WorksheetOptions>
                  <x:DisplayGridlines/>
                </x:WorksheetOptions>
              </x:ExcelWorksheet>
            </x:ExcelWorksheets>
          </x:ExcelWorkbook>
        </xml>
        <![endif]-->
        <style>
          * { font-family: "Phetsarath OT", "Noto Sans Lao", sans-serif !important; }
          body { margin: 0; padding: 10px; font-family: "Phetsarath OT", "Noto Sans Lao", sans-serif; }
          table { border-collapse: collapse; width: 100%; font-family: "Phetsarath OT", "Noto Sans Lao", sans-serif; }
          th, td { font-family: "Phetsarath OT", "Noto Sans Lao", sans-serif; border: .5pt solid #000; padding: 6px 10px; vertical-align: middle; }
          th { background-color: #e5e7eb; font-weight: bold; text-align: center; }
          tfoot td { background-color: #f3f4f6; font-weight: bold; }
        </style>
      </head>
      <body>
        ${tableHtml}
      </body>
      </html>
    `;

    var blob = new Blob(['\ufeff' + excelDoc], {
      type: 'application/vnd.ms-excel;charset=utf-8'
    });

    var url = URL.createObjectURL(blob);

    var a = document.createElement('a');
    a.href = url;
    a.download = I18N_IMPORT_LIST['import_list.js_export_name'] + '_' + new Date().toISOString().slice(0, 10) + '.xls';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }

