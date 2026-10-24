<?php
/*
 * ສ່ວນ: ປ້ອນສິນຄ້າໂດຍກົງ
 * ສະແກນ/ຄົ້ນຫາບາໂຄ້ດ, ເລືອກສິນຄ້າຈາກ popup (ຫຼາຍລາຍການ), ກຳນົດຈຳນວນ ແລະ ເພີ່ມເຂົ້າກະຕ່ານຳເຂົ້າ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/import_stock/components/import_stock_js.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>  // ====== DIRECT ENTRY FORM JS LOGIC ======
  $(document).ready(function() {
    $('#direct_barcode_input').focus();
  });

  function playImportBeep() {
    try {
      var AudioCtx = window.AudioContext || window.webkitAudioContext;
      if (!AudioCtx) return;
      var ctx = new AudioCtx();
      var osc = ctx.createOscillator();
      var gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.type = 'sine';
      osc.frequency.setValueAtTime(1400, ctx.currentTime);
      gain.gain.setValueAtTime(0.2, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.12);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.12);
    } catch (e) {}
  }

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
    var matchedUnit = null;

    PRODUCTS_LIST.forEach(function(p) {
      var pBarcode = (p.barcode || "").toLowerCase();
      var pId = p.product_id.toString();
      var pName = (p.product_name || "").toLowerCase();

      var unitMatch = null;
      if (PRODUCTS_UNITS[p.product_id]) {
        PRODUCTS_UNITS[p.product_id].forEach(function(u) {
          if (u.barcode && u.barcode.toLowerCase() === query) {
            unitMatch = u;
          }
        });
      }

      if (unitMatch) {
        exactMatch = p;
        matchedUnit = unitMatch;
      } else if (pBarcode === query || pId === query) {
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
      if (isEnter) {
        // ສະແກນແລ້ວເດັ້ງເຂົ້າລາຍການທັນທີ (Instant scan & add to cart)
        addScannedProductToCart(targetProduct, matchedUnit);
      } else {
        setDirectSelectedProduct(targetProduct);
      }
    } else if (isEnter && window.hasNonEnglishChars && window.hasNonEnglishChars(query) && window.showSwitchKeyboardAlert) {
      window.showSwitchKeyboardAlert(query, document.getElementById('direct_barcode_input'));
    } else if (isEnter && window.showBarcodeNotFoundAlert) {
      window.showBarcodeNotFoundAlert($('#direct_barcode_input').val().trim(), document.getElementById('direct_barcode_input'));
    }
  }

  function addScannedProductToCart(p, unitObj) {
    var qty = parseInt($('#direct_qty').val() || 1, 10);
    if (isNaN(qty) || qty <= 0) qty = 1;

    var baseUnit = p.unit || I18N_IMPORT_STOCK['import_stock.default_unit'];
    var unitKey = 'base';
    var unitName = baseUnit;
    var multiplier = 1;
    var costPrice = parseFloat(p.bprice || 0);
    var sellPrice = parseFloat(p.price || 0);

    var targetUnit = unitObj;
    var selUnitVal = $('#direct_unit_key').val();
    if (!targetUnit && selUnitVal && selUnitVal !== 'base' && PRODUCTS_UNITS[p.product_id]) {
      targetUnit = PRODUCTS_UNITS[p.product_id].find(function(u) { return u.id == selUnitVal; });
    }

    if (targetUnit) {
      unitKey = targetUnit.id;
      unitName = targetUnit.unit_name;
      multiplier = parseInt(targetUnit.multiplier || 1, 10);
      if (targetUnit.bprice && parseFloat(targetUnit.bprice) > 0) {
        costPrice = parseFloat(targetUnit.bprice);
      }
      if (targetUnit.price && parseFloat(targetUnit.price) > 0) {
        sellPrice = parseFloat(targetUnit.price);
      }
    }

    var expiryDate = ($('#direct_expiry_date').val() || '').trim();

    // Check if item already exists in cart with same product_id, unit_key, expiry_date
    var existingIdx = cartItems.findIndex(function(item) {
      return item.product_id == p.product_id && item.unit_key == unitKey && item.expiry_date == expiryDate;
    });

    if (existingIdx !== -1) {
      cartItems[existingIdx].quantity += qty;
      var existingItem = cartItems.splice(existingIdx, 1)[0];
      cartItems.unshift(existingItem);
    } else {
      cartItems.unshift({
        product_id: p.product_id,
        product_name: p.product_name,
        barcode: (targetUnit && targetUnit.barcode) ? targetUnit.barcode : (p.barcode || '-'),
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

    playImportBeep();
    cartCurrentPage = 1;
    renderCartTable();
    resetDirectSelection();

    // Visual feedback: brief highlight on the newly added top row
    var $firstRow = $('#cart_table_body tr:first');
    $firstRow.css('background-color', '#dcfce7');
    setTimeout(function() {
      $firstRow.css('transition', 'background-color 0.8s ease').css('background-color', '');
    }, 400);

    // Keep cursor on barcode input ready for next scan
    $('#direct_barcode_input').val('').focus();
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
      var updated = cartItems.splice(existingIdx, 1)[0];
      cartItems.unshift(updated);
    } else {
      cartItems.unshift({
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

    playImportBeep();
    cartCurrentPage = 1;
    resetDirectSelection();
    renderCartTable();

    var $firstRow = $('#cart_table_body tr:first');
    $firstRow.css('background-color', '#dcfce7');
    setTimeout(function() {
      $firstRow.css('transition', 'background-color 0.8s ease').css('background-color', '');
    }, 400);

    $('#direct_barcode_input').focus();
  }

