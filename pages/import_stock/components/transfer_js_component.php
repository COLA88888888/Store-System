<?php
/**
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * JS ຂອງໜ້າໂອນສິນຄ້າລະຫວ່າງສາຂາ: ປ້ອນ/ສະແກນສິນຄ້າ, ກະຕ່າໂອນ ແລະ popup ເລືອກສິນຄ້າຈາກຄັງ.
 *   - transfer/modal_products.php : Popup ເລືອກສິນຄ້າຈາກຄັງ
 */
// Component: Stock Transfer JavaScript Logic
?>
<script>
  var I18N_STOCK_TRANSFER = <?php echo tjson([
      'stock_transfer.err_product_not_found_title' => 'ບໍ່ພົບສິນຄ້າ',
      'stock_transfer.err_product_not_found_text' => 'ບໍ່ພົບສິນຄ້າທີ່ກົງກັບບາໂຄ້ດ ຫຼື ຊື່ນີ້!',
      'stock_transfer.err_select_product_title' => 'ກະລຸນາເລືອກສິນຄ້າ',
      'stock_transfer.err_select_product_text' => 'ກະລຸນາຕິກເລືອກສິນຄ້າຢ່າງນ້ອຍ 1 ລາຍການ!',
      'stock_transfer.err_scan_first_text' => 'ກະລຸນາສະແກນບາໂຄ້ດ ຫຼື ເລືອກສິນຄ້າກ່ອນ!',
      'stock_transfer.err_invalid_qty_title' => 'ຈຳນວນບໍ່ຖືກຕ້ອງ',
      'stock_transfer.err_invalid_qty_text' => 'ກະລຸນາປ້ອນຈຳນວນໂອນຢ່າງນ້ອຍ 1!',
      'stock_transfer.err_insufficient_stock_title' => 'ສະຕັອກບໍ່ພໍ',
      'stock_transfer.err_qty_exceeds_stock' => 'ຈຳນວນໂອນ (%s ອັນ) ເກີນສະຕັອກຕົ້ນທາງທີ່ມີ (%s ອັນ)!',
      'stock_transfer.err_qty_exceeds_stock_short' => 'ຈຳນວນໂອນ ເກີນສະຕັອກຕົ້ນທາງທີ່ມີ (%s)!',
      'stock_transfer.success_added_title' => 'ເພີ່ມສິນຄ້າສຳເລັດ!',
      'stock_transfer.success_added_text' => 'ເພີ່ມສິນຄ້າລວມ %s ລາຍການ ເຂົ້າໃນລາຍການໂອນຮຽບຮ້ອຍແລ້ວ',
      'stock_transfer.empty_cart_title' => 'ຍັງບໍ່ມີລາຍການສິນຄ້າໃນໃບໂອນ',
      'stock_transfer.empty_cart_hint' => 'ກະລຸນາສະແກນບາໂຄ້ດ ຫຼື ກົດປຸ່ມ "ເລືອກສິນຄ້າ" ເພື່ອເພີ່ມສິນຄ້າທີ່ຈະໂອນ',
      'stock_transfer.default_unit' => 'ອັນ',
      'stock_transfer.unit_label' => 'ຫົວໜ່ວຍ',
      'stock_transfer.barcode_label' => 'ບາໂຄ້ດ:',
      'stock_transfer.err_select_target_store_title' => 'ກະລຸນາເລືອກສາຂາ',
      'stock_transfer.err_select_target_store_text' => 'ກະລຸນາເລືອກສາຂາປາຍທາງທີ່ຈະໂອນສິນຄ້າໄປຫາ!',
      'stock_transfer.err_no_items_title' => 'ບໍ່ມີລາຍການ',
      'stock_transfer.err_no_items_text' => 'ກະລຸນາເພີ່ມສິນຄ້າໃສ່ລາຍການໂອນຢ່າງນ້ອຍ 1 ລາຍການ!',
      'stock_transfer.confirm_transfer_title' => 'ຢືນຢັນການໂອນສິນຄ້າ?',
      'stock_transfer.confirm_transfer_text' => 'ລະບົບຈະຄັດລົບສະຕັອກຈາກສາຂາຕົ້ນທາງ ແລະ ເພີ່ມສະຕັອກເຂົ້າສາຂາປາຍທາງທັນທີ.',
      'stock_transfer.confirm_transfer_button' => 'ຢືນຢັນໂອນສະຕັອກ',
      'stock_transfer.cancel_button' => 'ຍົກເລີກ',
  ]); ?>;

  var PRODUCTS_LIST = <?php echo json_encode($products); ?>;
  var PRODUCTS_UNITS = <?php echo json_encode($product_units_map); ?>;
  var PRE_PRODUCT_ID = <?php echo json_encode($preProductId ?? 0); ?>;
  var AUTO_ADD = <?php echo json_encode(!empty($autoAdd) ? 1 : 0); ?>;
  var selectedProduct = null;
  var cartItems = [];

  $(document).ready(function() {
    $('#direct_barcode_input').focus();
    if ("<?php echo addslashes($preSearch); ?>" !== '' || PRE_PRODUCT_ID > 0) {
      onDirectBarcodeChange();
      if (AUTO_ADD && selectedProduct) {
        addCurrentItemToCart();
      }
    }
  });

  function onDirectBarcodeChange() {
    var query = $('#direct_barcode_input').val().trim().toLowerCase();
    var found = null;

    if (PRE_PRODUCT_ID > 0) {
      found = PRODUCTS_LIST.find(function(p) { return p.product_id == PRE_PRODUCT_ID; });
    }

    if (!found && query !== '') {
      found = PRODUCTS_LIST.find(function(p) {
        if (String(p.product_id) === query) return true;
        if (p.barcode && String(p.barcode).toLowerCase() === query) return true;
        if (PRODUCTS_UNITS[p.product_id]) {
          var uMatch = PRODUCTS_UNITS[p.product_id].find(function(u) {
            return u.barcode && String(u.barcode).toLowerCase() === query;
          });
          if (uMatch) return true;
        }
        return false;
      });

      if (!found) {
        found = PRODUCTS_LIST.find(function(p) {
          return (p.product_name && p.product_name.toLowerCase().indexOf(query) !== -1);
        });
      }
    }

    if (found) {
      setDirectSelectedProduct(found);
    } else {
      resetDirectSelection();
    }
  }

  function onDirectBarcodeKeyDown(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      if (selectedProduct) {
        $('#direct_qty').focus().select();
      } else {
        var rawQ = ($('#direct_barcode_input').val() || '').trim();
        if (rawQ && window.hasNonEnglishChars && window.hasNonEnglishChars(rawQ) && window.showSwitchKeyboardAlert) {
          window.showSwitchKeyboardAlert(rawQ, document.getElementById('direct_barcode_input'));
          return;
        }
        Swal.fire({ icon: 'warning', title: I18N_STOCK_TRANSFER['stock_transfer.err_product_not_found_title'], text: I18N_STOCK_TRANSFER['stock_transfer.err_product_not_found_text'], confirmButtonColor: '#2563eb' });
      }
    }
  }

  function onQtyKeyDown(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      addCurrentItemToCart();
    }
  }

  function setDirectSelectedProduct(prod) {
    selectedProduct = prod;
    $('#direct_matched_banner').removeClass('d-none').addClass('d-flex');
    $('#direct_matched_name').text(prod.product_name + (prod.unit ? ' (' + prod.unit + ')' : ''));
    $('#direct_matched_barcode').text(prod.barcode || '-');
    $('#direct_matched_stock').text((prod.qty || 0) + ' ' + (prod.unit || I18N_STOCK_TRANSFER['stock_transfer.default_unit']));

    var unitSelect = $('#direct_unit_key');
    unitSelect.empty();
    unitSelect.append('<option value="base">' + (prod.unit || I18N_STOCK_TRANSFER['stock_transfer.default_unit']) + ' (x1)</option>');

    if (PRODUCTS_UNITS[prod.product_id]) {
      PRODUCTS_UNITS[prod.product_id].forEach(function(u) {
        unitSelect.append('<option value="' + u.id + '">' + u.unit_name + ' (x' + u.multiplier + ')</option>');
      });
    }
  }

  function resetDirectSelection() {
    selectedProduct = null;
    $('#direct_matched_banner').addClass('d-none').removeClass('d-flex');
    $('#direct_unit_key').html('<option value="base">' + I18N_STOCK_TRANSFER['stock_transfer.unit_label'] + '</option>');
  }

  function selectProductFromModal(productId) {
    var prod = PRODUCTS_LIST.find(function(p) { return p.product_id == productId; });
    if (prod) {
      var existingIndex = cartItems.findIndex(function(item) {
        return item.product_id == prod.product_id && item.unit_key == 'base';
      });

      if (existingIndex !== -1) {
        cartItems[existingIndex].quantity += 1;
      } else {
        cartItems.push({
          product_id: prod.product_id,
          product_name: prod.product_name,
          barcode: prod.barcode || '',
          stock_qty: parseInt(prod.qty || 0),
          unit_key: 'base',
          unit_name: prod.unit || I18N_STOCK_TRANSFER['stock_transfer.default_unit'],
          multiplier: 1,
          quantity: 1
        });
      }
      renderCartTable();
      $('#productSelectModal').modal('hide');
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
          var prod = PRODUCTS_LIST.find(function(p) { return p.product_id == pid; });
          if (prod) targetProducts.push(prod);
        }
      });
    }

    if (targetProducts.length === 0) {
      Swal.fire({ icon: 'warning', title: I18N_STOCK_TRANSFER['stock_transfer.err_select_product_title'], text: I18N_STOCK_TRANSFER['stock_transfer.err_select_product_text'], confirmButtonColor: '#2563eb' });
      return;
    }

    var addedCount = 0;
    targetProducts.forEach(function(prod) {
      var existingIndex = cartItems.findIndex(function(item) {
        return item.product_id == prod.product_id && item.unit_key == 'base';
      });

      if (existingIndex !== -1) {
        cartItems[existingIndex].quantity += 1;
      } else {
        cartItems.push({
          product_id: prod.product_id,
          product_name: prod.product_name,
          barcode: prod.barcode || '',
          stock_qty: parseInt(prod.qty || 0),
          unit_key: 'base',
          unit_name: prod.unit || I18N_STOCK_TRANSFER['stock_transfer.default_unit'],
          multiplier: 1,
          quantity: 1
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
      title: I18N_STOCK_TRANSFER['stock_transfer.success_added_title'],
      text: I18N_STOCK_TRANSFER['stock_transfer.success_added_text'].replace('%s', addedCount),
      timer: 1800,
      showConfirmButton: false
    });
  }

  function addCurrentItemToCart() {
    if (!selectedProduct) {
      Swal.fire({ icon: 'warning', title: I18N_STOCK_TRANSFER['stock_transfer.err_select_product_title'], text: I18N_STOCK_TRANSFER['stock_transfer.err_scan_first_text'], confirmButtonColor: '#2563eb' });
      return;
    }

    var qty = parseInt($('#direct_qty').val());
    if (isNaN(qty) || qty < 1) {
      Swal.fire({ icon: 'warning', title: I18N_STOCK_TRANSFER['stock_transfer.err_invalid_qty_title'], text: I18N_STOCK_TRANSFER['stock_transfer.err_invalid_qty_text'], confirmButtonColor: '#2563eb' });
      return;
    }

    var unitKey = $('#direct_unit_key').val();
    var unitName = selectedProduct.unit || I18N_STOCK_TRANSFER['stock_transfer.default_unit'];
    var multiplier = 1;

    if (unitKey !== 'base' && PRODUCTS_UNITS[selectedProduct.product_id]) {
      var uObj = PRODUCTS_UNITS[selectedProduct.product_id].find(function(u) { return u.id == unitKey; });
      if (uObj) {
        unitName = uObj.unit_name;
        multiplier = parseInt(uObj.multiplier) || 1;
      }
    }

    var baseQtyNeeded = qty * multiplier;
    if (baseQtyNeeded > parseInt(selectedProduct.qty)) {
      Swal.fire({ icon: 'warning', title: I18N_STOCK_TRANSFER['stock_transfer.err_insufficient_stock_title'], text: I18N_STOCK_TRANSFER['stock_transfer.err_qty_exceeds_stock'].replace('%s', baseQtyNeeded).replace('%s', selectedProduct.qty), confirmButtonColor: '#2563eb' });
      return;
    }

    var existingIndex = cartItems.findIndex(function(item) {
      return item.product_id == selectedProduct.product_id && item.unit_key == unitKey;
    });

    if (existingIndex !== -1) {
      cartItems[existingIndex].quantity += qty;
    } else {
      cartItems.push({
        product_id: selectedProduct.product_id,
        product_name: selectedProduct.product_name,
        barcode: selectedProduct.barcode,
        stock_qty: parseInt(selectedProduct.qty),
        unit_key: unitKey,
        unit_name: unitName,
        multiplier: multiplier,
        quantity: qty
      });
    }

    $('#direct_barcode_input').val('');
    $('#direct_qty').val(1);
    resetDirectSelection();
    renderCartTable();
    $('#direct_barcode_input').focus();
  }

  var cartCurrentPage = 1;
  var cartPageSize = 10;

  function renderCartTable() {
    var tbody = $('#cart_table_body');
    if (cartItems.length === 0) {
      tbody.html('<tr id="empty_cart_row"><td colspan="5" class="text-center text-muted py-5"><i class="fas fa-box-open fa-3x d-block mb-2 text-muted" style="opacity: 0.4;"></i><span class="font-weight-bold">' + I18N_STOCK_TRANSFER['stock_transfer.empty_cart_title'] + '</span><br><small>' + I18N_STOCK_TRANSFER['stock_transfer.empty_cart_hint'] + '</small></td></tr>');
      $('#cart_pagination_row').addClass('d-none').removeClass('d-flex');
      return;
    }

    var totalItems = cartItems.length;
    var totalPages = Math.ceil(totalItems / cartPageSize) || 1;
    if (cartCurrentPage > totalPages) cartCurrentPage = totalPages;
    if (cartCurrentPage < 1) cartCurrentPage = 1;

    var startIdx = (cartCurrentPage - 1) * cartPageSize;
    var endIdx = Math.min(startIdx + cartPageSize, totalItems);

    var html = '';
    for (var idx = startIdx; idx < endIdx; idx++) {
      var item = cartItems[idx];
      html += '<tr>' +
                '<td class="text-center align-middle font-weight-bold text-secondary">' + (idx + 1) + '</td>' +
                '<td class="align-middle transfer-cell-name">' +
                  '<strong class="d-block text-dark">' + escapeHtml(item.product_name) + '</strong>' +
                  '<small class="text-muted">' + I18N_STOCK_TRANSFER['stock_transfer.barcode_label'] + ' ' + escapeHtml(item.barcode || '-') + '</small>' +
                '</td>' +
                '<td class="text-center align-middle font-weight-bold text-info">' + item.stock_qty + ' ' + escapeHtml(item.unit_name) + '</td>' +
                '<td class="text-center align-middle">' +
                  '<input type="number" class="form-control form-control-sm font-weight-bold text-center mx-auto" min="1" value="' + item.quantity + '" onchange="updateCartItemQty(' + idx + ', this.value)" style="width: 100px; height: 36px; border-radius: 6px;">' +
                '</td>' +
                '<td class="text-center align-middle">' +
                  '<button type="button" class="btn btn-outline-danger btn-sm px-2 py-1" onclick="removeCartItem(' + idx + ')"><i class="fas fa-trash-alt"></i></button>' +
                '</td>' +
              '</tr>';
    }

    tbody.html(html);

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

  function goToCartPage(p) {
    cartCurrentPage = p;
    renderCartTable();
  }

  function renderCartPagination(totalPages) {
    var container = $('#cartTablePagination');
    container.empty();
    if (totalPages <= 1) return;

    var prevDisabled = (cartCurrentPage === 1) ? 'disabled' : '';
    container.append('<li class="page-item ' + prevDisabled + '"><a class="page-link" href="javascript:void(0)" onclick="goToCartPage(' + (cartCurrentPage - 1) + ')"><i class="fas fa-chevron-left"></i></a></li>');

    for (var p = 1; p <= totalPages; p++) {
      var activeClass = (p === cartCurrentPage) ? 'active' : '';
      container.append('<li class="page-item ' + activeClass + '"><a class="page-link" href="javascript:void(0)" onclick="goToCartPage(' + p + ')">' + p + '</a></li>');
    }

    var nextDisabled = (cartCurrentPage === totalPages) ? 'disabled' : '';
    container.append('<li class="page-item ' + nextDisabled + '"><a class="page-link" href="javascript:void(0)" onclick="goToCartPage(' + (cartCurrentPage + 1) + ')"><i class="fas fa-chevron-right"></i></a></li>');
  }

  function updateCartItemQty(idx, val) {
    var qty = parseInt(val);
    if (isNaN(qty) || qty < 1) qty = 1;
    if (cartItems[idx]) {
      var needed = qty * cartItems[idx].multiplier;
      if (needed > cartItems[idx].stock_qty) {
        Swal.fire({ icon: 'warning', title: I18N_STOCK_TRANSFER['stock_transfer.err_insufficient_stock_title'], text: I18N_STOCK_TRANSFER['stock_transfer.err_qty_exceeds_stock_short'].replace('%s', cartItems[idx].stock_qty), confirmButtonColor: '#2563eb' });
        qty = Math.floor(cartItems[idx].stock_qty / cartItems[idx].multiplier) || 1;
      }
      cartItems[idx].quantity = qty;
    }
    renderCartTable();
  }

  function removeCartItem(idx) {
    cartItems.splice(idx, 1);
    renderCartTable();
  }

  function submitDirectTransferBill() {
    var toStore = $('#to_store_id').val();
    if (!toStore) {
      Swal.fire({ icon: 'warning', title: I18N_STOCK_TRANSFER['stock_transfer.err_select_target_store_title'], text: I18N_STOCK_TRANSFER['stock_transfer.err_select_target_store_text'], confirmButtonColor: '#2563eb' });
      return;
    }

    if (cartItems.length === 0) {
      Swal.fire({ icon: 'warning', title: I18N_STOCK_TRANSFER['stock_transfer.err_no_items_title'], text: I18N_STOCK_TRANSFER['stock_transfer.err_no_items_text'], confirmButtonColor: '#2563eb' });
      return;
    }

    $('#cart_json_input').val(JSON.stringify(cartItems));

    Swal.fire({
      title: I18N_STOCK_TRANSFER['stock_transfer.confirm_transfer_title'],
      text: I18N_STOCK_TRANSFER['stock_transfer.confirm_transfer_text'],
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#10b981',
      cancelButtonColor: '#64748b',
      confirmButtonText: I18N_STOCK_TRANSFER['stock_transfer.confirm_transfer_button'],
      cancelButtonText: I18N_STOCK_TRANSFER['stock_transfer.cancel_button']
    }).then(function(result) {
      if (result.isConfirmed) {
        $('#directTransferForm').submit();
      }
    });
  }

  function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

<?php require __DIR__ . '/transfer/modal_products.php'; /* Popup ເລືອກສິນຄ້າຈາກຄັງ */ ?>
</script>
