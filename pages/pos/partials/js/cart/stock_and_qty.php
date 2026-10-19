<?php
/*
 * ສ່ວນ: ສະຕັອກ ແລະ ຈຳນວນໃນກະຕ່າ
 * ສະຕັອກຄົງເຫຼືອ, ສຽງ/ແຈ້ງເຕືອນສິນຄ້າໃກ້ໝົດ, ປ້າຍສະຕັອກໃນຕາຕະລາງສິນຄ້າ, ເພີ່ມ/ຫຼຸດ/ປ່ຽນຈຳນວນ, ລົບ
 * ແລະ ລ້າງກະຕ່າ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/pos/partials/js/pos_cart_js.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>// --- STOCK CHECK HELPERS & ALERTS ---
function getProductCutQty(productId) {
  var stockValEl = $('.product-stock-val-' + productId);
  if (stockValEl.length) {
    return parseInt(stockValEl.attr('data-cut-qty') ?? '1');
  }
  // fallback from allProducts
  var p = (typeof allProducts !== 'undefined') ? allProducts.find(function(item) { return String(item.product_id) === String(productId); }) : null;
  return p ? parseInt(p.cut_qty ?? 1) : 1;
}

function getProductRemainingStock(productId) {
  // ຖ້າ cut_qty = 0 = ບໍ່ຕັດ qty, ຊົ່ນ Infinity
  if (getProductCutQty(productId) === 0) return Infinity;

  var p = (typeof allProducts !== 'undefined') ? allProducts.find(function(item) { return String(item.product_id) === String(productId); }) : null;
  var initialStock = 0;

  var stockValEl = $('.product-stock-val-' + productId);
  if (stockValEl.length) {
    initialStock = parseFloat(stockValEl.attr('data-initial-stock')) || 0;
  } else if (p) {
    initialStock = parseFloat(p.qty) || 0;
  }

  var inCartDeduct = 0;
  if (cart && cart.length > 0) {
    cart.forEach(function(item) {
      if (String(item.product_id) === String(productId)) {
        var qty = parseFloat(item.quantity) || 0;
        var multiplier = parseInt(item.multiplier) || 1;
        inCartDeduct += (qty * multiplier);
      }
    });
  }

  return initialStock - inCartDeduct;
}

function triggerAudioSoundAlert() {
  try {
    if (typeof window.playNotificationSound === 'function') {
      window.playNotificationSound();
    } else if (window.parent && typeof window.parent.playNotificationSound === 'function') {
      window.parent.playNotificationSound();
    } else {
      var AudioCtx = window.AudioContext || window.webkitAudioContext;
      if (AudioCtx) {
        var ctx = new AudioCtx();
        var now = ctx.currentTime;
        [880, 1108.73, 1318.51].forEach(function(freq, i) {
          var osc = ctx.createOscillator();
          var gain = ctx.createGain();
          osc.type = 'triangle';
          osc.frequency.value = freq;
          gain.gain.setValueAtTime(0.8, now + (i * 0.12));
          gain.gain.exponentialRampToValueAtTime(0.001, now + (i * 0.12) + 0.35);
          osc.connect(gain);
          gain.connect(ctx.destination);
          osc.start(now + (i * 0.12));
          osc.stop(now + (i * 0.12) + 0.35);
        });
      }
    }
  } catch(e) {}
}

function showLowStockToast(productName, remainingQty, unitName) {
  triggerAudioSoundAlert();

  var isZero = (remainingQty <= 0);
  var uName = unitName || posT('pos.unit_generic');
  Swal.fire({
    icon: isZero ? 'error' : 'warning',
    title: isZero ? posT('pos.toast_out_title') : posT('pos.toast_low_title'),
    text: isZero
      ? posT('pos.toast_out_body', productName, uName)
      : posT('pos.toast_low_body', productName, remainingQty.toLocaleString(), uName),
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 4500,
    timerProgressBar: true
  });
}

function updateProductGridBadges() {
  // 1. Reset all cart badges
  $('.cart-qty-badge').hide().text('0');

  // 2. Map cart quantities and stock deductions
  var productCartQtyMap = {};
  var productStockDeductMap = {};

  if (cart && cart.length > 0) {
    cart.forEach(function(item) {
      var pid = String(item.product_id);
      var qty = parseFloat(item.quantity) || 0;
      var multiplier = parseInt(item.multiplier) || 1;
      var baseDeduct = qty * multiplier;

      productCartQtyMap[pid] = (productCartQtyMap[pid] || 0) + qty;
      productStockDeductMap[pid] = (productStockDeductMap[pid] || 0) + baseDeduct;
    });
  }

  // 3. Update the cart-quantity badge for EVERY product card, stock-tracked or not —
  // the stock-value loop below only touches products that render a .product-stock-val
  // element (cut_qty != 0). Made-to-order items (cut_qty = 0, e.g. drinks) never get
  // one, so their "in cart" badge needs updating here independently.
  $('.cart-qty-badge').each(function() {
    var badgeEl = $(this);
    var pidMatch = badgeEl.attr('class').match(/product-qty-badge-(\d+)/);
    if (!pidMatch) return;
    var pid = pidMatch[1];
    var totalQtyInCart = productCartQtyMap[pid] || productCartQtyMap[parseInt(pid, 10)] || 0;
    if (totalQtyInCart > 0) {
      badgeEl.text(totalQtyInCart).show();
    } else {
      badgeEl.hide().text('0');
    }
  });

  // 4. Update every stock-tracked product card's remaining-stock figure & status badges
  var localStocks = {};
  try {
    localStocks = JSON.parse(localStorage.getItem('StoreMini.local_stocks') || '{}');
  } catch(e) {}

  $('.product-stock-val').each(function() {
    var stockValEl = $(this);
    var pidStr = stockValEl.attr('class').match(/product-stock-val-(\d+)/);
    if (!pidStr) return;
    var pid = pidStr[1];

    var initialStock = parseFloat(stockValEl.attr('data-initial-stock')) || 0;
    if (typeof localStocks[pid] !== 'undefined') {
      initialStock = parseFloat(localStocks[pid]);
    } else if (typeof localStocks[parseInt(pid, 10)] !== 'undefined') {
      initialStock = parseFloat(localStocks[parseInt(pid, 10)]);
    } else if (typeof allProducts !== 'undefined' && allProducts && allProducts.length > 0) {
      var pItem = allProducts.find(function(item) { return String(item.product_id) === String(pid); });
      if (pItem) {
        initialStock = parseFloat(pItem.qty) || 0;
      }
    }
    stockValEl.attr('data-initial-stock', initialStock);
    stockValEl.data('initial-stock', initialStock);

    var totalStockDeducted = productStockDeductMap[pid] || productStockDeductMap[parseInt(pid, 10)] || 0;
    var remainingStock = Math.max(0, initialStock - totalStockDeducted);

    stockValEl.text(remainingStock.toLocaleString());

    var cardEl = $('.product-card-' + pid);
    var infoWrap = $('.stock-info-wrap-' + pid);

    if (remainingStock <= 0) {
      if (cardEl.length) {
        cardEl.css({'border': '2px solid #ef4444', 'opacity': '0.82', 'background': '#fff5f5'});
      }
      if (infoWrap.length) {
        infoWrap.removeClass('in-stock low-stock').addClass('out-of-stock');
      }
    } else if (remainingStock <= 10) {
      if (cardEl.length) {
        cardEl.css({'border': '2px solid #f59e0b', 'opacity': '1.0', 'background': '#ffffff'});
      }
      if (infoWrap.length) {
        infoWrap.removeClass('in-stock out-of-stock').addClass('low-stock');
      }
    } else {
      if (cardEl.length) {
        cardEl.css({'border': '1.5px solid #244886', 'opacity': '1.0', 'background': '#ffffff'});
      }
      if (infoWrap.length) {
        infoWrap.removeClass('out-of-stock low-stock').addClass('in-stock');
      }
    }
  });
}

function recalculateLiveStock() {
  updateProductGridBadges();
}
window.recalculateLiveStock = recalculateLiveStock;

function incCartQty(cartKey, delta) {
  var item = cart.find(function(i) { return i.cartKey === cartKey; });
  // A points-reward line has a fixed quantity (the reward's); minus cancels the redemption.
  if (item && item.reward_uid) {
    if (delta < 0) removeRedemption(item.reward_uid);
    return;
  }
  if (item) {
    if (delta > 0) {
      var remaining = getProductRemainingStock(item.product_id);
      var needed = delta * (item.multiplier || 1);
      if (remaining < needed) {
        Swal.fire({
          icon: 'error',
          title: 'ຈຳນວນສິນຄ້າບໍ່ພໍ!',
          html: '<div style="font-size:1.0rem; font-weight:600; color:#ef4444;">ສິນຄ້າ "' + item.product_name + '" ບໍ່ສາມາດເພີ່ມຈຳນວນໄດ້ອີກ</div><div class="mt-2 text-muted" style="font-size:0.88rem;">ຈຳນວນເຫຼືອໃນສາງ: <b>' + Math.max(0, remaining).toLocaleString() + '</b> ' + item.unit_name + '</div>',
          confirmButtonText: 'ຕົກລົງ',
          confirmButtonColor: '#ef4444'
        });
        return;
      }
      var newRemaining = remaining - needed;
      if (newRemaining <= 10 && newRemaining >= 0) {
        showLowStockToast(item.product_name, newRemaining, item.unit_name);
      }
    }
    item.quantity += delta;
    if (item.quantity <= 0) {
      removeFromCart(cartKey);
    } else {
      updateCartUI();
    }
  }
}

function changeCartQty(cartKey, val) {
  var q = parseInt(val) || 1;
  if (q <= 0) q = 1;
  var item = cart.find(function(i) { return i.cartKey === cartKey; });
  if (item) {
    var oldQty = item.quantity;
    var diffQty = q - oldQty;
    if (diffQty > 0) {
      var remaining = getProductRemainingStock(item.product_id);
      var needed = diffQty * (item.multiplier || 1);
      if (remaining < needed) {
        var maxAdd = Math.floor(remaining / (item.multiplier || 1));
        var allowedQty = oldQty + maxAdd;
        item.quantity = allowedQty > 0 ? allowedQty : 1;
        Swal.fire({
          icon: 'error',
          title: 'ຈຳນວນສິນຄ້າບໍ່ພໍ!',
          html: '<div style="font-size:1.0rem; font-weight:600; color:#ef4444;">ສິນຄ້າ "' + item.product_name + '" ເຫຼືອໃນສາງບໍ່ພໍ</div><div class="mt-2 text-muted" style="font-size:0.88rem;">ສາມາດຂາຍໄດ້ສູງສຸດ: <b>' + (oldQty + maxAdd).toLocaleString() + '</b> ' + item.unit_name + '</div>',
          confirmButtonText: 'ຕົກລົງ',
          confirmButtonColor: '#ef4444'
        });
        updateCartUI();
        return;
      }
      var newRemaining = remaining - needed;
      if (newRemaining <= 10 && newRemaining >= 0) {
        showLowStockToast(item.product_name, newRemaining, item.unit_name);
      }
    }
    item.quantity = q;
    updateCartUI();
  }
}

function removeFromCart(cartKey) {
  var itemToRemove = cart.find(function(i) { return i.cartKey === cartKey; });
  if (itemToRemove && itemToRemove.reward_uid) {
    removeRedemption(itemToRemove.reward_uid);
    return;
  }
  if (itemToRemove && itemToRemove.product_id) {
    var pid = itemToRemove.product_id;
    cart = cart.filter(function(i) { 
      return i.cartKey !== cartKey && i.parent_product_id !== pid; 
    });
  } else {
    cart = cart.filter(function(i) { return i.cartKey !== cartKey; });
  }
  updateCartUI();
}

function clearCart() {
  // ລ້າງກະຕ່າໂດຍກົງ ບໍ່ຕ້ອງມີ popup ຢືນຢັນ
  if (cart.length === 0) return;
  cart = [];
  // The cleared cart is no longer the recalled held bill — that bill stays held (unpaid).
  if (typeof setCurrentHeldId === 'function') setCurrentHeldId(null);
  if (typeof posDropAllRedemptions === 'function') posDropAllRedemptions();
  updateCartUI();
}

