<?php
/**
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * JS ຂອງໜ້ານຳເຂົ້າສິນຄ້າ: ປ້ອນໂດຍກົງ (ສະແກນ/ຄົ້ນຫາ), ກະຕ່ານຳເຂົ້າ ແລະ popup ເລືອກສິນຄ້າ.
 *   - import_stock/direct_entry.php : ປ້ອນສິນຄ້າໂດຍກົງ
 *   - import_stock/cart_table.php : ຕາຕະລາງກະຕ່ານຳເຂົ້າ
 */
// Component: JavaScript Logic for Import Stock Entry (import_stock.php)
?>
<script>
  var I18N_IMPORT_STOCK = <?php echo tjson([
      'import_stock.default_unit' => 'ອັນ',
      'import_stock.js_select_product_title' => 'ກະລຸນາເລືອກສິນຄ້າ',
      'import_stock.js_select_product_text' => 'ກະລຸນາຕິກເລືອກສິນຄ້າຢ່າງນ້ອຍ 1 ລາຍການ!',
      'import_stock.js_scan_first_text' => 'ກະລຸນາສະແກນບາໂຄ້ດ ຫຼື ກົດປຸ່ມ ເລືອກສິນຄ້າ ກ່ອນ!',
      'import_stock.js_added_title' => 'ເພີ່ມສິນຄ້າສຳເລັດ!',
      'import_stock.js_added_text' => 'ເພີ່ມສິນຄ້າລວມ %s ລາຍການ ເຂົ້າໃນລາຍການນໍາເຂົ້າຮຽບຮ້ອຍແລ້ວ',
      'import_stock.js_invalid_qty_title' => 'ຈຳນວນບໍ່ຖືກຕ້ອງ',
      'import_stock.js_invalid_qty_text' => 'ຈຳນວນຮັບເຂົ້າຕ້ອງຫຼາຍກວ່າ 0!',
      'import_stock.js_sub_unit_prefix' => 'ຍ່ອຍ',
      'import_stock.js_sub_unit_option' => 'ຫົວໜ່ວຍຍ່ອຍ',
      'import_stock.js_empty_cart_title' => 'ຍັງບໍ່ມີລາຍການສິນຄ້າໃນໃບບິນ',
      'import_stock.js_empty_cart_hint' => 'ກະລຸນາສີດບາໂຄ້ດ ຫຼື ກົດປຸ່ມ "ເລືອກສິນຄ້າ" ເພື່ອເພີ່ມສິນຄ້າຮັບເຂົ້າ',
      'import_stock.js_expiry_prefix' => 'ໝົດອາຍຸ:',
      'import_stock.js_remove_title' => 'ລົບອອກ',
      'import_stock.js_empty_bill_title' => 'ໃບບິນຫວ່າງເປົ່າ',
      'import_stock.js_empty_bill_text' => 'ກະລຸນາເພີ່ມສິນຄ້າຮັບເຂົ້າຢ່າງນ້ອຍ 1 ລາຍການ!',
      'import_stock.js_confirm_save_title' => 'ຢືນຢັນການບັນທຶກຮັບເຂົ້າ?',
      'import_stock.js_confirm_save_text' => 'ລາຍການຮັບເຂົ້າທັງໝົດ %s ລາຍການ ຈະຖືກເພີ່ມເຂົ້າສະຕັອກຄັງສິນຄ້າ!',
      'import_stock.js_confirm_save_btn' => 'ບັນທຶກຮັບເຂົ້າ',
      'import_stock.js_cancel_btn' => 'ຍົກເລີກ',
  ]); ?>;

  var PRODUCTS_LIST = <?php echo json_encode($products); ?>;
  var PRODUCTS_UNITS = <?php echo json_encode($product_units_map); ?>;
  var selectedProduct = null;
  var cartItems = [];

<?php require __DIR__ . '/import_stock/direct_entry.php'; /* ປ້ອນສິນຄ້າໂດຍກົງ */ ?>
<?php require __DIR__ . '/import_stock/cart_table.php'; /* ຕາຕະລາງກະຕ່ານຳເຂົ້າ */ ?>
  // ====== MODAL PAGINATION & SEARCH ======
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
    // Remember the choice so it survives the page reload after an import/transfer is saved.
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
      var r = modalFilteredRows[i];
      r.style.display = '';
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
    if (savedSize) {
      modalPageSize = parseInt(savedSize, 10) || 10;
      $('#modal_page_size_select').val(String(modalPageSize));
    }
    filterModalProducts();
    $('#modal_product_search').focus();
  });

  function intval(val) {
    var parsed = parseInt(val, 10);
    return isNaN(parsed) ? 0 : parsed;
  }

  // Live thousands-separator formatting for price inputs
  function formatNumberInput(input) {
    var raw = String(input.value).replace(/[^0-9.]/g, '');
    if (raw === '') { input.value = ''; return; }
    var parts = raw.split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    if (parts.length > 2) parts = [parts[0], parts.slice(1).join('')];
    input.value = parts.join('.');
  }

  // Parse a formatted "12,345.6" price string back to a number
  function parsePrice(val) {
    var n = parseFloat(String(val).replace(/,/g, ''));
    return isNaN(n) ? 0 : n;
  }

  function escapeHtml(text) {
    return String(text || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
</script>
