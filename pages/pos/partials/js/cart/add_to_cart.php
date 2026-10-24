<?php
/*
 * ສ່ວນ: ເພີ່ມສິນຄ້າເຂົ້າກະຕ່າ
 * ລາຍການຫົວໜ່ວຍຂອງສິນຄ້າ ແລະ _doAddToCart (ກວດສະຕັອກ, ໂປຣໂມຊັ່ນ, ຂອງແຖມ, ລວມແຖວຊ້ຳ).
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/pos/partials/js/pos_cart_js.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>function buildAvailableUnits(product) {
  var units = [];
  var baseUnitName = product.unit ? product.unit : 'ອັນ';
  var basePrice = parseFloat(product.price) || 0;
  var baseBprice = parseFloat(product.bprice) || 0;

  units.push({
    unit_name: baseUnitName,
    multiplier: 1,
    price: basePrice,
    original_price: parseFloat(product.original_price) || basePrice,
    bprice: baseBprice
  });

  if (product.extra_units && product.extra_units.length > 0) {
    product.extra_units.forEach(function(eu) {
      units.push({
        unit_name: eu.unit_name,
        multiplier: parseInt(eu.multiplier) || 1,
        price: parseFloat(eu.price) || 0,
        original_price: parseFloat(eu.original_price) || parseFloat(eu.price) || 0,
        bprice: parseFloat(eu.bprice) || 0
      });
    });
  }

  return units;
}

function _doAddToCart(product, unitObj) {
  // ສາຂາມີກະ: ຜູ້ບໍລິຫານ ແລະ ຜູ້ຈັດການຂາຍບໍ່ໄດ້ (checkout/open_and_submit.php)
  if (typeof checkShiftSalePermission === 'function' && !checkShiftSalePermission()) return;
  if (!isBillOpened || !activeBills || activeBills.length === 0) {
    if (typeof createNewBillModal === 'function') {
      createNewBillModal();
    } else {
      isBillOpened = true;
    }
  }

  var unitName = unitObj ? unitObj.unit_name : (product.unit || 'ອັນ');
  var unitPrice = unitObj ? parseFloat(unitObj.price) : parseFloat(product.price);
  var origPrice = (unitObj && unitObj.original_price) ? parseFloat(unitObj.original_price) : (parseFloat(product.original_price) || unitPrice);
  var costPrice = unitObj ? parseFloat(unitObj.bprice) : parseFloat(product.bprice);
  var multiplier = unitObj ? (parseInt(unitObj.multiplier) || 1) : 1;

  var currentRemaining = getProductRemainingStock(product.product_id);
  var cutQty = getProductCutQty(product.product_id);

  // 1. ຖ້າສິນຄ້າເປັນ 0 (ຫຼື ໝົດແລ້ວ) -> ບໍ່ສາມາດຂາຍໄດ້, ສະແດງ ປັອບອັບ ເເຈ້ງເຕືອນ
  if (cutQty !== 0 && (currentRemaining <= 0 || parseFloat(product.qty || 0) <= 0)) {
    Swal.fire({
      icon: 'error',
      title: posT('pos.alert_out_of_stock_title'),
      html: '<div style="font-size:1.05rem; font-weight:700; color:#ef4444; margin-bottom:6px;">' + posT('pos.alert_product_quoted', product.product_name) + '</div>' +
            '<div class="text-dark font-weight-bold" style="font-size:0.92rem;">' + posT('pos.alert_out_of_stock_body') + '</div>' +
            '<small class="text-muted d-block mt-2">' + posT('pos.alert_out_of_stock_hint') + '</small>',
      confirmButtonText: '<i class="fas fa-check mr-1"></i> ' + posT('pos.btn_ok'),
      confirmButtonColor: '#ef4444',
      customClass: {
        confirmButton: 'btn btn-danger font-weight-bold px-4'
      }
    });
    return false;
  }

  // 2. ຖ້າຈຳນວນເຫຼືອໜ້ອຍກວ່າຫົວໜ່ວຍທີ່ເລືອກ
  if (currentRemaining < multiplier) {
    Swal.fire({
      icon: 'warning',
      title: posT('pos.alert_qty_not_enough_title'),
      html: '<div style="font-size:1.0rem; font-weight:700; color:#d97706;">' + posT('pos.alert_remaining_only', product.product_name, currentRemaining.toLocaleString(), (product.unit || posT('pos.unit_generic'))) + '</div><div class="mt-2 text-dark" style="font-size:0.88rem;">' + posT('pos.alert_cannot_sell_over') + '</div>',
      confirmButtonText: posT('pos.btn_ok'),
      confirmButtonColor: '#f59e0b'
    });
    return false;
  }

  // Include price in the key: a product can have two unit rows sharing the same
  // unit_name but different price (e.g. "ຖົງ" at 70 and at 60) — those must stay as
  // separate cart/receipt lines, not merge their quantity under whichever price was
  // added first.
  var cartKey = product.product_id + '_' + unitName + '_' + unitPrice;
  var existing = cart.find(function(i) { return i.cartKey === cartKey; });

  // Monotonic "last touched" stamp so the customer display can float the most
  // recently scanned/tapped item to the top without reordering the cashier's cart.
  window._cartTouchSeq = (window._cartTouchSeq || 0) + 1;

  if (existing) {
    existing.quantity += 1;
    existing._touch = window._cartTouchSeq;
  } else {
    cart.push({
      cartKey: cartKey,
      product_id: product.product_id,
      product_name: product.product_name,
      category_id: (product.category_id != null ? product.category_id : null),
      unit_name: unitName,
      unit_price: unitPrice,
      original_price: origPrice,
      cost_price: costPrice,
      multiplier: multiplier,
      quantity: 1,
      image: product.img_url || product.image || '',
      _touch: window._cartTouchSeq
    });
  }

  // Handle Free Gift Auto-Adding into Cart
  var giftName = '';
  var giftQty = 1;
  var targetUnit = (product && product.target_unit_name) ? trimStr(product.target_unit_name) : 'all';
  
  function trimStr(str) {
    return String(str || '').trim().toLowerCase();
  }

  function checkUnitMatch(u1, u2) {
    // Exact match only (after trimming spaces/case + folding ເເ→ແ). BeerLao's ປ໋ອງຍາວ / ປ໋ອງສັ້ນ
    // share the "ປ໋ອງ" prefix, so any substring match would wrongly fire the gift on both.
    var fold = function (s) {
      return trimStr(s).replace(/\s+/g, '').replace(/ເເ/g, 'ແ').replace(/ເແ/g, 'ແ');
    };
    var s1 = fold(u1);
    var s2 = fold(u2);
    if (s2 === 'all' || s2 === '' || s1 === 'all' || s1 === '') return true;
    return s1 === s2;
  }

  var unitMatches = checkUnitMatch(unitName, targetUnit);

  if (unitMatches) {
    if (product && product.gift_product_name && product.gift_product_name !== '') {
      giftName = product.gift_product_name;
      giftQty = parseInt(product.gift_qty) || 1;
    } else if (product && product.promo_type === 'buy_x_get_y') {
      giftName = 'ສິນຄ້າແຖມ';
      giftQty = parseInt(product.gift_qty) || 1;
    }
  }

  if (giftName !== '' && unitMatches) {
    var giftCartKey = 'GIFT_' + product.product_id + '_' + giftName;
    var existingGift = cart.find(function(i) { return i.cartKey === giftCartKey; });
    
    if (existingGift) {
      existingGift.quantity += giftQty;
    } else {
      cart.push({
        cartKey: giftCartKey,
        product_id: 'GIFT_' + product.product_id,
        product_name: giftName + ' (ແຖມ' + (targetUnit !== 'all' ? ' ສະເພາະ ' + targetUnit : '') + ')',
        category_id: (product.category_id != null ? product.category_id : null),
        unit_name: 'ຊິ້ນ',
        unit_price: 0,
        cost_price: 0,
        multiplier: 1,
        quantity: giftQty,
        image: 'image.jpg',
        is_free_gift: true,
        parent_product_id: product.product_id
      });
    }
  }

  var newRemaining = currentRemaining - multiplier;

  // 3. ຖ້າເຫຼືອ 10 ລາຍການ (ຫຼື ຫຼຸດລົງ <= 10) -> ແຈ້ງເຕືອນວ່າສິນຄ້າໃກ້ຈະໝົດແລ້ວ (ເທົ່ານັ້ນ cut_qty = 1)
  if (getProductCutQty(product.product_id) === 1 && newRemaining <= 10 && newRemaining >= 0) {
    showLowStockToast(product.product_name, newRemaining, product.unit || 'ອັນ');
  }

  updateCartUI();
  return true;
}

