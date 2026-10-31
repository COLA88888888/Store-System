<?php
/*
 * ສ່ວນ: ຕາຕະລາງກະຕ່ານຳເຂົ້າ
 * ຍອດລວມ, ແຕ້ມຕາຕະລາງ + pagination, ແກ້ຈຳນວນ/ລາຄາທຶນ/ລາຄາຂາຍ, ລົບແຖວ ແລະ ສົ່ງບິນນຳເຂົ້າ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/import_stock/components/import_stock_js.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>  // ====== LIVE CART TABLE PAGINATION ======
  var cartCurrentPage = 1;
  var cartPageSize = 10;

  // Summary line + hidden JSON only — no table rebuild, so typing in a row keeps its focus.
  function updateCartTotals() {
    var grandTotalBase = 0;
    var grandTotalCost = 0;
    cartItems.forEach(function(item) {
      var cost = parseFloat(item.cost_price) || 0;
      var qty = parseInt(item.quantity) || 0;
      var mult = parseInt(item.multiplier) || 1;
      grandTotalBase += qty * mult;
      grandTotalCost += qty * cost;
    });
    $('#cart_total_items').text(cartItems.length);
    $('#cart_total_base').text((grandTotalBase || 0).toLocaleString());
    $('#cart_grand_total').text((grandTotalCost || 0).toLocaleString() + ' <?php echo kipSymbol(); ?>');
    $('#cart_json_input').val(JSON.stringify(cartItems));
  }

  function renderCartTable() {
    var tbody = $('#cart_table_body');
    // ປິດປະຕິທິນວັນໝົດອາຍຸຂອງແຖວເກົ່າກ່ອນ (ບໍ່ດັ່ງນັ້ນກ່ອງປະຕິທິນຄ້າງຢູ່ໃນ <body> ທຸກເທື່ອທີ່ແຕ້ມໃໝ່)
    tbody.find('input').each(function () { if (this._flatpickr) this._flatpickr.destroy(); });
    tbody.empty();

    if (cartItems.length === 0) {
      tbody.html(`
        <tr id="empty_cart_row">
          <td colspan="8" class="text-center text-muted py-5">
            <i class="fas fa-box-open fa-3x d-block mb-2 text-muted" style="opacity: 0.4;"></i>
            <span class="font-weight-bold">${I18N_IMPORT_STOCK['import_stock.js_empty_cart_title']}</span><br>
            <small>${I18N_IMPORT_STOCK['import_stock.js_empty_cart_hint']}</small>
          </td>
        </tr>
      `);
      $('#cart_total_items').text(0);
      $('#cart_total_base').text(0);
      $('#cart_grand_total').text('0 <?php echo kipSymbol(); ?>');
      $('#cart_page_start').text(0);
      $('#cart_page_end').text(0);
      $('#cart_page_total').text(0);
      $('#cart_json_input').val('[]');
      renderCartPagination(1);
      return;
    }

    var totalItems = cartItems.length;
    updateCartTotals();

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
            <small class="text-muted"><i class="fas fa-barcode mr-1"></i> ${escapeHtml(item.barcode)}</small>
          </td>
          <td class="text-center align-middle">
            <div class="input-group input-group-sm mx-auto" style="max-width: 190px;">
              <input type="text" inputmode="numeric" class="form-control text-center font-weight-bold cart-qty-input" value="${qty}" data-col="qty" onfocus="this.select()" oninput="liveCartQty(${idx}, this)" onblur="updateCartQty(${idx}, this.value, this)" onkeydown="cartInputKey(event, this)">
              <div class="input-group-append">${cartUnitCell(item, idx)}</div>
            </div>
          </td>
          <td class="text-center align-middle">
            <input type="date" class="form-control form-control-sm font-weight-bold mx-auto" style="max-width: 140px;" value="${escapeHtml(item.expiry_date || '')}" placeholder="${I18N_IMPORT_STOCK['import_stock.expiry_date_label'] || ''}" onchange="updateCartExpiry(${idx}, this.value)">
          </td>
          <td class="text-right align-middle">
            <input type="text" inputmode="decimal" class="form-control form-control-sm text-right font-weight-bold text-danger ml-auto" style="max-width: 120px;" value="${cost.toLocaleString('en-US')}" data-col="cost" onfocus="this.select()" oninput="formatNumberInput(this); updateCartCost(${idx}, this.value)" onkeydown="cartInputKey(event, this)">
          </td>
          <td class="text-right align-middle">
            <input type="text" inputmode="decimal" class="form-control form-control-sm text-right font-weight-bold text-success ml-auto" style="max-width: 120px;" value="${sell.toLocaleString('en-US')}" data-col="sell" onfocus="this.select()" oninput="formatNumberInput(this); updateCartSell(${idx}, this.value)" onkeydown="cartInputKey(event, this)">
          </td>
          <td class="text-right align-middle font-weight-bold text-primary" style="font-size: 1.05rem;" id="cart_line_total_${idx}">
            ${itemTotalCost.toLocaleString()} <?php echo kipSymbol(); ?>
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

  // Every keystroke updates the row's amount and the totals straight away. The table is NOT
  // rebuilt: rebuilding on change destroyed the input the cashier had just clicked into, so
  // every field needed two clicks.
  function refreshCartLine(idx) {
    var item = cartItems[idx];
    if (!item) return;
    var total = (parseInt(item.quantity) || 0) * (parseFloat(item.cost_price) || 0);
    $('#cart_line_total_' + idx).text(total.toLocaleString() + ' <?php echo kipSymbol(); ?>');
    updateCartTotals();
  }

  function liveCartQty(idx, input) {
    if (!cartItems[idx]) return;
    input.value = String(input.value).replace(/\D/g, '');
    cartItems[idx].quantity = intval(input.value || 0);
    refreshCartLine(idx);
  }

  // On leaving the field: an empty / 0 quantity becomes 1.
  function updateCartQty(idx, val, input) {
    if (!cartItems[idx]) return;
    var q = intval(val || 1);
    if (q <= 0) q = 1;
    cartItems[idx].quantity = q;
    if (input) input.value = q;
    refreshCartLine(idx);
  }

  function updateCartCost(idx, val) {
    if (!cartItems[idx]) return;
    cartItems[idx].cost_price = parsePrice(val);
    refreshCartLine(idx);
  }

  // ຫົວໜ່ວຍໃນແຖວ: ສິນຄ້າທີ່ມີຫຼາຍຫົວໜ່ວຍ → ເລືອກໄດ້ (ຫົວໜ່ວຍຫຼັກ / ແພັກ / ແກັດ…); ບໍ່ດັ່ງນັ້ນສະແດງຊື່ຫົວໜ່ວຍຢ່າງດຽວ
  function cartUnitCell(item, idx) {
    var units = PRODUCTS_UNITS[item.product_id] || [];
    if (!units.length) {
      return `<span class="input-group-text bg-light font-weight-bold text-dark">${escapeHtml(item.unit_name)}</span>`;
    }
    var opts = `<option value="base"${item.unit_key == 'base' ? ' selected' : ''}>${escapeHtml(item.base_unit)}</option>`;
    units.forEach(function (u) {
      opts += `<option value="${u.id}"${item.unit_key == u.id ? ' selected' : ''}>${escapeHtml(u.unit_name)} (×${parseInt(u.multiplier || 1, 10)})</option>`;
    });
    return `<select class="custom-select custom-select-sm font-weight-bold" style="border-radius: 0 4px 4px 0; max-width: 110px;" onchange="updateCartUnit(${idx}, this.value)">${opts}</select>`;
  }

  // ປ່ຽນຫົວໜ່ວຍ: ລາຄາຊື້/ຂາຍ ເປັນລາຄາຂອງຫົວໜ່ວຍນັ້ນ (ຖ້າບໍ່ໄດ້ຕັ້ງ = ລາຄາຫົວໜ່ວຍຫຼັກ × ຈຳນວນຕໍ່ຫົວໜ່ວຍ)
  function updateCartUnit(idx, unitKey) {
    var item = cartItems[idx];
    if (!item) return;
    var p = PRODUCTS_LIST.find(function (x) { return x.product_id == item.product_id; }) || {};
    var baseCost = parseFloat(p.bprice || 0), baseSell = parseFloat(p.price || 0);
    var u = unitKey === 'base' ? null : (PRODUCTS_UNITS[item.product_id] || []).find(function (x) { return x.id == unitKey; });
    var mult = u ? (parseInt(u.multiplier || 1, 10) || 1) : 1;
    item.unit_key = u ? u.id : 'base';
    item.unit_name = u ? u.unit_name : item.base_unit;
    item.multiplier = mult;
    item.barcode = (u && u.barcode) ? u.barcode : (p.barcode || '-');
    item.cost_price = (u && parseFloat(u.bprice) > 0) ? parseFloat(u.bprice) : baseCost * mult;
    item.sell_price = (u && parseFloat(u.price) > 0) ? parseFloat(u.price) : baseSell * mult;
    renderCartTable();
  }

  function updateCartExpiry(idx, val) {
    if (!cartItems[idx]) return;
    cartItems[idx].expiry_date = (val || '').trim();
    updateCartTotals();
  }

  function updateCartSell(idx, val) {
    if (!cartItems[idx]) return;
    cartItems[idx].sell_price = parsePrice(val);
    updateCartTotals();
  }

  // Enter / ↓ jumps to the same field on the next row, ↑ to the previous one.
  function cartInputKey(e, input) {
    var dir = (e.key === 'Enter' || e.key === 'ArrowDown') ? 1 : (e.key === 'ArrowUp' ? -1 : 0);
    if (!dir) return;
    e.preventDefault();
    var col = input.getAttribute('data-col');
    var list = $('#cart_table_body').length ? $('#cart_table_body').find('input[data-col="' + col + '"]') : $('input[data-col="' + col + '"]');
    var i = list.index(input) + dir;
    if (i >= 0 && i < list.length) list.eq(i).focus();
    else input.blur();
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

