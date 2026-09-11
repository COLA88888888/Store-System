<?php
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

  // ====== DIRECT ENTRY FORM JS LOGIC ======
  $(document).ready(function() {
    $('#direct_barcode_input').focus();
  });

  function onDirectBarcodeKeyDown(e) {
    if (e.key === 'Enter' || e.keyCode === 13) {
      e.preventDefault();
      searchDirectProduct(true);
    }
  }

  function onDirectBarcodeChange() {
    searchDirectProduct(false);
  }

  function searchDirectProduct(isEnter) {
    var query = $('#direct_barcode_input').val().trim().toLowerCase();
    if (!query) {
      resetDirectSelection();
      return;
    }

    var exactMatch = null;
    var partialMatches = [];

    PRODUCTS_LIST.forEach(function(p) {
      var pBarcode = (p.barcode || "").toLowerCase();
      var pId = p.product_id.toString();
      var pName = (p.product_name || "").toLowerCase();

      var extraBarcodes = [];
      if (PRODUCTS_UNITS[p.product_id]) {
        PRODUCTS_UNITS[p.product_id].forEach(function(u) {
          if (u.barcode) extraBarcodes.push(u.barcode.toLowerCase());
        });
      }

      if (pBarcode === query || extraBarcodes.includes(query) || pId === query) {
        exactMatch = p;
      } else if (pBarcode.includes(query) || pName.includes(query)) {
        partialMatches.push(p);
      }
    });

    var targetProduct = exactMatch;
    if (!targetProduct && isEnter && partialMatches.length > 0) {
      targetProduct = partialMatches[0];
    }

    if (targetProduct) {
      setDirectSelectedProduct(targetProduct);
      if (isEnter) {
        $('#direct_qty').focus().select();
      }
    }
  }

  function selectProductFromModal(productId) {
    var p = PRODUCTS_LIST.find(function(item) { return item.product_id == productId; });
    if (p) {
      setDirectSelectedProduct(p);
      $('#productSelectModal').modal('hide');
      $('#direct_qty').focus().select();
    }
  }

  function addSelectedModalProductsToCart() {
    var targetProducts = [];

    if (window.modalSelectAllCatalog && typeof PRODUCTS_LIST !== 'undefined' && PRODUCTS_LIST.length > 0) {
      targetProducts = PRODUCTS_LIST;
    } else {
      $('.modal-product-checkbox:checked').each(function() {
        var pid = $(this).attr('data-product-id');
        if (pid) {
          var p = PRODUCTS_LIST.find(function(item) { return item.product_id == pid; });
          if (p) targetProducts.push(p);
        }
      });
    }

    if (targetProducts.length === 0) {
      Swal.fire({ icon: 'warning', title: I18N_IMPORT_STOCK['import_stock.js_select_product_title'], text: I18N_IMPORT_STOCK['import_stock.js_select_product_text'], confirmButtonColor: '#2563eb' });
      return;
    }

    var addedCount = 0;
    targetProducts.forEach(function(p) {
      var baseUnit = p.unit || I18N_IMPORT_STOCK['import_stock.default_unit'];
      var existingIndex = cartItems.findIndex(function(item) {
        return item.product_id == p.product_id && item.unit_key == 'base' && item.expiry_date == '';
      });

      if (existingIndex !== -1) {
        cartItems[existingIndex].quantity += 1;
      } else {
        cartItems.push({
          product_id: p.product_id,
          product_name: p.product_name,
          barcode: p.barcode || '-',
          base_unit: baseUnit,
          unit_key: 'base',
          unit_name: baseUnit,
          multiplier: 1,
          quantity: 1,
          cost_price: parseFloat(p.bprice || 0),
          sell_price: parseFloat(p.price || 0),
          expiry_date: ''
        });
      }
      addedCount++;
    });

    renderCartTable();
    $('#productSelectModal').modal('hide');

    // Reset modal selection state
    window.modalSelectAllCatalog = false;
    $('#selectAllModalProducts').prop('checked', false);
    $('.modal-product-checkbox').prop('checked', false);
    if (typeof updateModalSelectedCount === 'function') updateModalSelectedCount();

    Swal.fire({
      icon: 'success',
      title: I18N_IMPORT_STOCK['import_stock.js_added_title'],
      text: I18N_IMPORT_STOCK['import_stock.js_added_text'].replace('%s', addedCount),
      timer: 1800,
      showConfirmButton: false
    });
  }

  function setDirectSelectedProduct(p) {
    selectedProduct = p;
    $('#direct_matched_name').text(p.product_name);
    $('#direct_matched_barcode').text(p.barcode || '-');
    $('#direct_matched_bprice').text(parseFloat(p.bprice || 0).toLocaleString() + ' ₭');
    $('#direct_matched_stock').text(parseInt(p.qty || 0).toLocaleString());
    $('#direct_matched_banner').removeClass('d-none').addClass('d-flex');

    // Build units dropdown
    var unitSelect = document.getElementById("direct_unit_key");
    unitSelect.options.length = 0;
    var baseUnit = p.unit || I18N_IMPORT_STOCK['import_stock.default_unit'];

    unitSelect.options[unitSelect.options.length] = new Option(I18N_IMPORT_STOCK['import_stock.js_sub_unit_prefix'] + " (" + baseUnit + ")", "base");

    if (PRODUCTS_UNITS[p.product_id]) {
      PRODUCTS_UNITS[p.product_id].forEach(function(u) {
        var optText = u.unit_name + " (1 " + u.unit_name + " = " + u.multiplier + " " + baseUnit + ")";
        unitSelect.options[unitSelect.options.length] = new Option(optText, u.id);
      });
    }
  }

  function resetDirectSelection() {
    selectedProduct = null;
    $('#direct_barcode_input').val('');
    $('#direct_qty').val(1);
    $('#direct_expiry_date').val('');
    $('#direct_matched_banner').addClass('d-none').removeClass('d-flex');
    var unitSelect = document.getElementById("direct_unit_key");
    unitSelect.options.length = 0;
    unitSelect.options[unitSelect.options.length] = new Option(I18N_IMPORT_STOCK['import_stock.js_sub_unit_option'], "base");
  }

  function onQtyKeyDown(e) {
    if (e.key === 'Enter' || e.keyCode === 13) {
      e.preventDefault();
      addCurrentItemToCart();
    }
  }

  function addCurrentItemToCart() {
    if (!selectedProduct) {
      Swal.fire({ icon: 'warning', title: I18N_IMPORT_STOCK['import_stock.js_select_product_title'], text: I18N_IMPORT_STOCK['import_stock.js_scan_first_text'], confirmButtonColor: '#2563eb' });
      return;
    }

    var qty = intval($('#direct_qty').val() || 1);
    if (qty <= 0) {
      Swal.fire({ icon: 'warning', title: I18N_IMPORT_STOCK['import_stock.js_invalid_qty_title'], text: I18N_IMPORT_STOCK['import_stock.js_invalid_qty_text'], confirmButtonColor: '#2563eb' });
      return;
    }

    var unitKey = $('#direct_unit_key').val();
    var expiryDate = $('#direct_expiry_date').val();
    var baseUnit = selectedProduct.unit || I18N_IMPORT_STOCK['import_stock.default_unit'];
    var unitName = baseUnit;
    var multiplier = 1;
    // Prices default to the product's current values; they are edited inline in the items table below
    var costPrice = parseFloat(selectedProduct.bprice || 0);
    var sellPrice = parseFloat(selectedProduct.price || 0);

    if (unitKey !== 'base' && PRODUCTS_UNITS[selectedProduct.product_id]) {
      var foundU = PRODUCTS_UNITS[selectedProduct.product_id].find(function(u) { return u.id == unitKey; });
      if (foundU) {
        unitName = foundU.unit_name;
        multiplier = parseInt(foundU.multiplier || 1);
        if (foundU.bprice && parseFloat(foundU.bprice) > 0) {
          costPrice = parseFloat(foundU.bprice);
        }
      }
    }

    // Check if item already exists in cart
    var existingIdx = cartItems.findIndex(function(item) {
      return item.product_id == selectedProduct.product_id && item.unit_key == unitKey && item.expiry_date == expiryDate;
    });

    if (existingIdx >= 0) {
      cartItems[existingIdx].quantity += qty;
      cartItems[existingIdx].cost_price = costPrice;
      cartItems[existingIdx].sell_price = sellPrice;
    } else {
      cartItems.push({
        product_id: selectedProduct.product_id,
        product_name: selectedProduct.product_name,
        barcode: selectedProduct.barcode || '-',
        base_unit: baseUnit,
        unit_key: unitKey,
        unit_name: unitName,
        multiplier: multiplier,
        quantity: qty,
        cost_price: costPrice,
        sell_price: sellPrice,
        expiry_date: expiryDate
      });
    }

    resetDirectSelection();
    renderCartTable();
    $('#direct_barcode_input').focus();
  }

  // ====== LIVE CART TABLE PAGINATION ======
  var cartCurrentPage = 1;
  var cartPageSize = 10;

  function renderCartTable() {
    var tbody = $('#cart_table_body');
    tbody.empty();

    if (cartItems.length === 0) {
      tbody.html(`
        <tr id="empty_cart_row">
          <td colspan="7" class="text-center text-muted py-5">
            <i class="fas fa-box-open fa-3x d-block mb-2 text-muted" style="opacity: 0.4;"></i>
            <span class="font-weight-bold">${I18N_IMPORT_STOCK['import_stock.js_empty_cart_title']}</span><br>
            <small>${I18N_IMPORT_STOCK['import_stock.js_empty_cart_hint']}</small>
          </td>
        </tr>
      `);
      $('#cart_total_items').text(0);
      $('#cart_total_base').text(0);
      $('#cart_grand_total').text('0 ₭');
      $('#cart_page_start').text(0);
      $('#cart_page_end').text(0);
      $('#cart_page_total').text(0);
      $('#cart_json_input').val('[]');
      renderCartPagination(1);
      return;
    }

    var totalItems = cartItems.length;
    var grandTotalBase = 0;
    var grandTotalCost = 0;

    cartItems.forEach(function(item) {
      var cost = parseFloat(item.cost_price) || 0;
      var qty = parseInt(item.quantity) || 1;
      var mult = parseInt(item.multiplier) || 1;
      var itemBaseQty = qty * mult;
      var itemTotalCost = qty * cost;
      grandTotalBase += itemBaseQty;
      grandTotalCost += itemTotalCost;
    });

    $('#cart_total_items').text(totalItems);
    $('#cart_total_base').text((grandTotalBase || 0).toLocaleString());
    $('#cart_grand_total').text((grandTotalCost || 0).toLocaleString() + ' ₭');
    $('#cart_json_input').val(JSON.stringify(cartItems));

    // Pagination for cart items table
    var totalPages = Math.ceil(totalItems / cartPageSize) || 1;
    if (cartCurrentPage > totalPages) cartCurrentPage = totalPages;
    if (cartCurrentPage < 1) cartCurrentPage = 1;

    var startIdx = (cartCurrentPage - 1) * cartPageSize;
    var endIdx = Math.min(startIdx + cartPageSize, totalItems);

    for (var idx = startIdx; idx < endIdx; idx++) {
      var item = cartItems[idx];
      var cost = parseFloat(item.cost_price) || 0;
      var sell = parseFloat(item.sell_price) || 0;
      var qty = parseInt(item.quantity) || 1;
      var itemTotalCost = qty * cost;

      var rowHtml = `
        <tr>
          <td class="text-center align-middle font-weight-bold text-muted">${idx + 1}</td>
          <td class="align-middle">
            <span class="font-weight-bold text-dark d-block" style="font-size: 0.98rem;">${escapeHtml(item.product_name)}</span>
            <small class="text-muted"><i class="fas fa-barcode mr-1"></i> ${escapeHtml(item.barcode)} ${item.expiry_date ? '| ' + I18N_IMPORT_STOCK['import_stock.js_expiry_prefix'] + ' ' + item.expiry_date : ''}</small>
          </td>
          <td class="text-center align-middle">
            <div class="input-group input-group-sm mx-auto" style="max-width: 130px;">
              <input type="number" class="form-control text-center font-weight-bold" min="1" value="${qty}" onchange="updateCartQty(${idx}, this.value)">
              <div class="input-group-append">
                <span class="input-group-text bg-light font-weight-bold text-dark">${escapeHtml(item.unit_name)}</span>
              </div>
            </div>
          </td>
          <td class="text-right align-middle">
            <input type="text" inputmode="decimal" class="form-control form-control-sm text-right font-weight-bold text-danger ml-auto" style="max-width: 120px;" value="${cost.toLocaleString('en-US')}" oninput="formatNumberInput(this)" onchange="updateCartCost(${idx}, this.value)">
          </td>
          <td class="text-right align-middle">
            <input type="text" inputmode="decimal" class="form-control form-control-sm text-right font-weight-bold text-success ml-auto" style="max-width: 120px;" value="${sell.toLocaleString('en-US')}" oninput="formatNumberInput(this)" onchange="updateCartSell(${idx}, this.value)">
          </td>
          <td class="text-right align-middle font-weight-bold text-primary" style="font-size: 1.05rem;">
            ${itemTotalCost.toLocaleString()} ₭
          </td>
          <td class="text-center align-middle">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCartItem(${idx})" title="${I18N_IMPORT_STOCK['import_stock.js_remove_title']}"><i class="fas fa-trash-alt"></i></button>
          </td>
        </tr>
      `;
      tbody.append(rowHtml);
    }

    if (totalItems > 10) {
      $('#cart_pagination_row').removeClass('d-none').addClass('d-flex');
      $('#cart_page_start').text(startIdx + 1);
      $('#cart_page_end').text(endIdx);
      $('#cart_page_total').text(totalItems);
      renderCartPagination(totalPages);
    } else {
      $('#cart_pagination_row').addClass('d-none').removeClass('d-flex');
    }
  }

  function renderCartPagination(totalPages) {
    var container = $('#cartTablePagination');
    container.empty();

    if (totalPages < 1) totalPages = 1;

    var prevDisabled = (cartCurrentPage === 1) ? 'disabled' : '';
    container.append(`
      <li class="page-item ${prevDisabled}">
        <a class="page-link" href="javascript:void(0)" onclick="goToCartPage(${cartCurrentPage - 1})">
          <i class="fas fa-chevron-left"></i>
        </a>
      </li>
    `);

    var maxButtons = 5;
    var startPage = Math.max(1, cartCurrentPage - 2);
    var endPage = Math.min(totalPages, startPage + maxButtons - 1);
    if (endPage - startPage < maxButtons - 1) {
      startPage = Math.max(1, endPage - maxButtons + 1);
    }

    if (startPage > 1) {
      container.append(`<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToCartPage(1)">1</a></li>`);
      if (startPage > 2) {
        container.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
      }
    }

    for (var p = startPage; p <= endPage; p++) {
      var activeClass = (p === cartCurrentPage) ? 'active' : '';
      container.append(`
        <li class="page-item ${activeClass}">
          <a class="page-link" href="javascript:void(0)" onclick="goToCartPage(${p})">${p}</a>
        </li>
      `);
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1) {
        container.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
      }
      container.append(`<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToCartPage(${totalPages})">${totalPages}</a></li>`);
    }

    var nextDisabled = (cartCurrentPage === totalPages) ? 'disabled' : '';
    container.append(`
      <li class="page-item ${nextDisabled}">
        <a class="page-link" href="javascript:void(0)" onclick="goToCartPage(${cartCurrentPage + 1})">
          <i class="fas fa-chevron-right"></i>
        </a>
      </li>
    `);
  }

  function goToCartPage(page) {
    cartCurrentPage = page;
    renderCartTable();
  }

  function updateCartQty(idx, val) {
    var q = intval(val || 1);
    if (q <= 0) q = 1;
    cartItems[idx].quantity = q;
    renderCartTable();
  }

  function updateCartCost(idx, val) {
    if (!cartItems[idx]) return;
    cartItems[idx].cost_price = parsePrice(val);
    renderCartTable();
  }

  function updateCartSell(idx, val) {
    if (!cartItems[idx]) return;
    cartItems[idx].sell_price = parsePrice(val);
    renderCartTable();
  }

  function removeCartItem(idx) {
    cartItems.splice(idx, 1);
    renderCartTable();
  }

  function submitDirectImportBill() {
    if (cartItems.length === 0) {
      Swal.fire({ icon: 'warning', title: I18N_IMPORT_STOCK['import_stock.js_empty_bill_title'], text: I18N_IMPORT_STOCK['import_stock.js_empty_bill_text'], confirmButtonColor: '#2563eb' });
      return;
    }

    Swal.fire({
      title: I18N_IMPORT_STOCK['import_stock.js_confirm_save_title'],
      text: I18N_IMPORT_STOCK['import_stock.js_confirm_save_text'].replace('%s', cartItems.length),
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#10b981',
      cancelButtonColor: '#64748b',
      confirmButtonText: '<i class="fas fa-save mr-1"></i> ' + I18N_IMPORT_STOCK['import_stock.js_confirm_save_btn'],
      cancelButtonText: I18N_IMPORT_STOCK['import_stock.js_cancel_btn']
    }).then((result) => {
      if (result.isConfirmed) {
        $('#directImportForm').submit();
      }
    });
  }

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
    try { localStorage.setItem('minipos_modal_page_size', modalPageSize); } catch (e) {}
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
    try { savedSize = localStorage.getItem('minipos_modal_page_size'); } catch (e) {}
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
