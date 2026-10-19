<?php
/*
 * ສ່ວນ: Popup ເລືອກສິນຄ້າຈາກຄັງ
 * ຄົ້ນຫາ, ຈຳນວນຕໍ່ໜ້າ, ແຕ້ມຕາຕະລາງ ແລະ pagination ຂອງ popup ເລືອກສິນຄ້າ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/import_stock/components/transfer_js_component.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>  // ====== "ເລືອກສິນຄ້າຈາກຄັງ" MODAL: CLIENT-SIDE PAGINATION & SEARCH ======
  // The modal (components/modal_select_product.php) renders every product row server-side.
  // Mirror the behaviour import_list_js.php / import_stock_js.php give the same modal so the
  // page-size dropdown actually limits the list instead of showing all rows at once.
  var modalCurrentPage = 1;
  var modalPageSize = 10;
  var modalFilteredRows = [];

  function filterModalProducts() {
    var q = $('#modal_product_search').val().trim().toLowerCase();
    var cat = $('#modal_category_filter').val();
    var rows = document.querySelectorAll('.modal-product-row');
    modalFilteredRows = [];

    rows.forEach(function(r) {
      var sData = r.getAttribute('data-search') || '';
      var rCat = r.getAttribute('data-category') || '';

      var matchSearch = !q || sData.includes(q);
      var matchCat = !cat || rCat === cat;

      if (matchSearch && matchCat) {
        modalFilteredRows.push(r);
      } else {
        r.style.display = 'none';
      }
    });

    modalCurrentPage = 1;
    renderModalProductTable();
  }

  function changeModalPageSize(val) {
    modalPageSize = parseInt(val, 10) || 10;
    modalCurrentPage = 1;
    // Remember the choice so it survives the page reload after a transfer is saved.
    try { localStorage.setItem('StoreMini.minipos_modal_page_size', modalPageSize); } catch (e) {}
    renderModalProductTable();
  }

  function renderModalProductTable() {
    var totalRows = modalFilteredRows.length;
    var totalPages = Math.ceil(totalRows / modalPageSize) || 1;

    if (modalCurrentPage > totalPages) modalCurrentPage = totalPages;
    if (modalCurrentPage < 1) modalCurrentPage = 1;

    var startIdx = (modalCurrentPage - 1) * modalPageSize;
    var endIdx = startIdx + modalPageSize;

    document.querySelectorAll('.modal-product-row').forEach(function(r) {
      r.style.display = 'none';
    });

    for (var i = startIdx; i < endIdx && i < totalRows; i++) {
      modalFilteredRows[i].style.display = '';
    }

    $('#modal_page_start').text(totalRows > 0 ? startIdx + 1 : 0);
    $('#modal_page_end').text(Math.min(endIdx, totalRows));
    $('#modal_page_total').text(totalRows);

    renderModalPagination(totalPages);
  }

  function renderModalPagination(totalPages) {
    var container = $('#modalProductPagination');
    container.empty();

    if (totalPages < 1) totalPages = 1;

    var prevDisabled = (modalCurrentPage === 1) ? 'disabled' : '';
    container.append(`
      <li class="page-item ${prevDisabled}">
        <a class="page-link" href="javascript:void(0)" onclick="goToModalPage(${modalCurrentPage - 1})">
          <i class="fas fa-chevron-left"></i>
        </a>
      </li>
    `);

    var maxButtons = 5;
    var startPage = Math.max(1, modalCurrentPage - 2);
    var endPage = Math.min(totalPages, startPage + maxButtons - 1);
    if (endPage - startPage < maxButtons - 1) {
      startPage = Math.max(1, endPage - maxButtons + 1);
    }

    if (startPage > 1) {
      container.append(`<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToModalPage(1)">1</a></li>`);
      if (startPage > 2) {
        container.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
      }
    }

    for (var p = startPage; p <= endPage; p++) {
      var activeClass = (p === modalCurrentPage) ? 'active' : '';
      container.append(`
        <li class="page-item ${activeClass}">
          <a class="page-link" href="javascript:void(0)" onclick="goToModalPage(${p})">${p}</a>
        </li>
      `);
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1) {
        container.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
      }
      container.append(`<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToModalPage(${totalPages})">${totalPages}</a></li>`);
    }

    var nextDisabled = (modalCurrentPage === totalPages) ? 'disabled' : '';
    container.append(`
      <li class="page-item ${nextDisabled}">
        <a class="page-link" href="javascript:void(0)" onclick="goToModalPage(${modalCurrentPage + 1})">
          <i class="fas fa-chevron-right"></i>
        </a>
      </li>
    `);
  }

  function goToModalPage(page) {
    modalCurrentPage = page;
    renderModalProductTable();
  }

  $('#productSelectModal').on('shown.bs.modal', function () {
    var savedSize = null;
    try { savedSize = localStorage.getItem('StoreMini.minipos_modal_page_size'); } catch (e) {}
    modalPageSize = parseInt(savedSize || $('#modal_page_size_select').val(), 10) || 10;
    $('#modal_page_size_select').val(String(modalPageSize));
    filterModalProducts();
    $('#modal_product_search').focus();
  });
