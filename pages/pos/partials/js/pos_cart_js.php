<script>
// i18n words for the "N items (M qty)" cart count label
var CART_WORD_ITEMS = <?php echo json_encode(t('pos.cart_count_items', 'ລາຍການ'), JSON_UNESCAPED_UNICODE); ?>;
var CART_WORD_QTY   = <?php echo json_encode(t('pos.cart_count_qty', 'ຈຳນວນ'), JSON_UNESCAPED_UNICODE); ?>;
function cartCountText(lineCount, qtyCount) {
  return lineCount + ' ' + CART_WORD_ITEMS + ' (' + qtyCount + ' ' + CART_WORD_QTY + ')';
}

// --- DUAL-SCREEN POS CUSTOMER DISPLAY BROADCAST ---
var customerDisplayChannel = null;
try {
  customerDisplayChannel = new BroadcastChannel('pos_customer_display_channel');
} catch(e) {}

function broadcastCustomerDisplay(checkoutState, extraData) {
  var payload = {
    cart: (typeof cart !== 'undefined') ? cart : [],
    customer: (typeof selectedCustomer !== 'undefined') ? selectedCustomer : null,
    discount: $('#cartDiscount').val() || '0',
    checkoutState: checkoutState || 'shopping',
    ...(extraData || {})
  };

  localStorage.setItem('pos_customer_display_data', JSON.stringify(payload));
  if (customerDisplayChannel) {
    try {
      customerDisplayChannel.postMessage(payload);
    } catch(e) {}
  }
}

function openCustomerDisplayOnScreen(left, top, w, h) {
  var win = window.open('customer_display.php', 'POSCustomerDisplay',
    'width=' + w + ',height=' + h + ',left=' + left + ',top=' + top + ',scrollbars=yes,resizable=yes');
  if (win) {
    try { win.moveTo(left, top); win.resizeTo(w, h); } catch(e) {}
  }
  broadcastCustomerDisplay('shopping');
}

function openCustomerDisplayWindow() {
  // Prefer the Window Management API so the display opens directly on the OTHER monitor,
  // not just fullscreen on whichever monitor the cashier's POS window is already on.
  if (typeof window.getScreenDetails === 'function') {
    window.getScreenDetails().then(function(details) {
      var current = details.currentScreen;
      var other = details.screens.find(function(s) { return s !== current; });
      if (other) {
        openCustomerDisplayOnScreen(other.availLeft, other.availTop, other.availWidth, other.availHeight);
      } else {
        // Only one screen detected — fall back to filling the current one.
        openCustomerDisplayOnScreen(0, 0, screen.availWidth || 1200, screen.availHeight || 800);
      }
    }).catch(function() {
      openCustomerDisplayFallback();
    });
    return;
  }
  openCustomerDisplayFallback();
}

function openCustomerDisplayFallback() {
  // No Window Management API (or permission denied) — best-effort guess: most dual-monitor
  // setups extend the desktop to the right, so positioning past the current screen's width
  // lands the window on the second monitor.
  var w = screen.availWidth || screen.width || 1200;
  var h = screen.availHeight || screen.height || 800;
  openCustomerDisplayOnScreen(w, 0, w, h);
}

// --- CART & ITEM MANAGEMENT ---
function updateCartUI() {
  var container = $('#cartItemsContainer');
  container.empty();

  if (cart.length === 0) {
    container.html(`
      <div class="cart-empty-state d-flex flex-column align-items-center justify-content-center h-100 py-5">
        <i class="fas fa-shopping-basket text-muted mb-2" style="font-size: 2.2rem; opacity: 0.3;"></i>
        <div class="text-muted font-weight-bold" style="font-size: 0.92rem;"><?php echo htmlspecialchars(t('pos.cart_empty_title', 'ບໍ່ມີລາຍການສິນຄ້າໃນກະຕ່າ'), ENT_QUOTES); ?></div>
        <small class="text-muted"><?php echo htmlspecialchars(t('pos.cart_empty_hint', 'ກະລຸນາເລືອກສິນຄ້າ ຫຼື ສະແກນບາໂຄ້ດ'), ENT_QUOTES); ?></small>
      </div>
    `);
    $('#cartItemCountBadge').text(cartCountText(0, 0));
    $('#cartSubtotal').text('0 ₭');
    $('#cartVat').text('0 ₭');
    $('#cartTotal').text('0 ₭');
    $('#mobileCartCountBadge').text('0').hide();
    $('#floatingCartItemCount').text(cartCountText(0, 0));
    $('#floatingCartTotal').html('0 ₭ <i class="fas fa-chevron-right ml-1"></i>');
    $('#mobileFloatingCartBar').hide();
    $('#btnCheckout').prop('disabled', true).addClass('disabled');
    $('#btnClearCart').prop('disabled', true).addClass('disabled');
    $('#btnHoldOrder, #btnHoldCart').prop('disabled', true).addClass('disabled');
    updateProductGridBadges();
    saveCurrentBillState();
    broadcastCustomerDisplay('shopping');
    return;
  }

  var subtotal = 0;
  var totalItemsCount = 0;

  cart.forEach(function(item, idx) {
    var itemTotal = item.quantity * item.unit_price;
    subtotal += itemTotal;
    totalItemsCount += item.quantity;

    var rawImg = item.image || item.img_url || '';
    var imgName = rawImg ? rawImg.split('/').pop().split('\\').pop() : 'image.jpg';
    if (!imgName || imgName === 'image.jpg') {
      var imgPath = '<?php echo $base_path; ?>assets/img/image.jpg';
    } else {
      var imgPath = '<?php echo $base_path; ?>assets/product_img/' + imgName;
    }

    var isGift = !!item.is_free_gift;
    var giftBadge = isGift ? `<span class="badge badge-success font-weight-bold ml-1" style="font-size:0.78rem; padding:2px 6px; border-radius:4px;"><i class="fas fa-gift mr-1"></i>ແຖມຟຣີ</span>` : '';
    var priceDisplay = isGift 
      ? `<span class="text-success font-weight-bold" style="font-size:0.80rem;">0 ₭</span>` 
      : `${Number(item.unit_price).toLocaleString()} ₭`;

    var rowHtml = `
      <div class="cart-item-row mb-2 d-flex align-items-center" style="background:${isGift ? '#f0fdf4' : '#ffffff'}; border:1.5px solid ${isGift ? '#86efac' : '#e2e8f0'}; border-radius:12px; padding:8px 10px; gap:8px; min-height:54px;">

        <!-- 1. ຮູບພາບ -->
        <img src="${imgPath}" class="cart-item-img" style="width:44px; height:44px; object-fit:contain; border-radius:8px; border:1px solid #e2e8f0; background:#f8fafc; padding:3px; flex-shrink:0;" onerror="this.src='<?php echo $base_path; ?>assets/img/image.jpg';">

        <!-- 2. ຊື່ສິນຄ້າ + ຫົວໜ່ວຍ -->
        <div class="cart-item-name-wrap" style="flex:1.4; min-width:0; display:flex; flex-direction:column; justify-content:center; gap:2px;">
          <div class="font-weight-bold text-truncate" style="font-size:0.86rem; color:#1e293b; line-height:1.2;" title="${item.product_name}">${item.product_name} ${giftBadge}</div>
          <span class="cart-item-unit-badge d-none d-lg-inline-block" style="font-size:0.80rem; font-weight:700; color:#244886; background:#eef2fb; border:1px solid #c7d2e8; border-radius:5px; padding:1px 7px; width:fit-content; max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${item.unit_name}</span>
          <span class="cart-item-unit-badge-mobile d-inline-block d-lg-none" style="font-size:0.78rem; font-weight:700; color:#244886; background:#eef2fb; border:1px solid #c7d2e8; border-radius:5px; padding:1px 5px; width:fit-content; max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${item.unit_name} (${priceDisplay})</span>
        </div>

        <!-- 3. ລາຄາຕໍ່ໜ່ວຍ (Desktop Only) -->
        <div class="cart-item-unit-price d-none d-lg-block" style="flex-shrink:0; text-align:center; min-width:60px;">
          <div style="font-size:0.84rem; font-weight:700; color:${isGift ? '#16a34a' : '#475569'};">${priceDisplay}</div>
        </div>

        <!-- 4. ຈຳນວນ -->
        <div class="cart-item-qty-wrap" style="flex-shrink:0; display:flex; align-items:center; border:1.5px solid #cbd5e1; border-radius:8px; overflow:hidden; background:#f8fafc;">
          <button onclick="incCartQty('${item.cartKey}', -1)" title="ຫຼຸດ" class="btn-qty-minus" style="width:32px; height:34px; border:none; background:transparent; color:#ef4444; font-size:0.92rem; cursor:pointer; line-height:1; display:flex; align-items:center; justify-content:center; padding:0;">
            <i class="fas fa-minus"></i>
          </button>
          <input type="number" min="1" value="${item.quantity}" onchange="changeCartQty('${item.cartKey}', this.value)" class="input-qty-val" style="width:38px; height:34px; border:none; border-left:1px solid #cbd5e1; border-right:1px solid #cbd5e1; text-align:center; font-size:0.92rem; font-weight:700; color:#1e293b; background:#ffffff; padding:0; -moz-appearance:textfield;" ${isGift ? 'readonly' : ''}>
          <button onclick="incCartQty('${item.cartKey}', 1)" title="ເພີ່ມ" class="btn-qty-plus" style="width:32px; height:34px; border:none; background:transparent; color:#244886; font-size:0.92rem; cursor:pointer; line-height:1; display:flex; align-items:center; justify-content:center; padding:0;" ${isGift ? 'disabled style="opacity:0.4; cursor:not-allowed;"' : ''}>
            <i class="fas fa-plus"></i>
          </button>
        </div>

        <!-- 5. ລວມ -->
        <div class="cart-item-total-wrap" style="flex-shrink:0; text-align:right; min-width:65px;">
          <div style="font-size:0.92rem; font-weight:800; color:${isGift ? '#16a34a' : '#16a34a'}; white-space:nowrap;">${isGift ? '0 ₭' : itemTotal.toLocaleString() + ' ₭'}</div>
        </div>

        <!-- 6. ປຸ່ມລົບ -->
        <button class="cart-item-del-btn" onclick="removeFromCart('${item.cartKey}')" title="ລຶບ" style="flex-shrink:0; width:32px; height:32px; border:none; background:transparent; color:#ef4444; cursor:pointer; display:flex; align-items:center; justify-content:center; padding:0;">
          <i class="fas fa-trash-alt" style="font-size:1.1rem !important;"></i>
        </button>

      </div>
    `;
    container.append(rowHtml);
  });

  <?php
    $storeTaxType = !empty($company['tax_type']) ? $company['tax_type'] : 'none';
    $storeVatPercent = ($storeTaxType === 'none') ? 0 : (isset($company['vat_percent']) ? floatval($company['vat_percent']) : 0);
  ?>
  window.STORE_TAX_TYPE = '<?php echo $storeTaxType; ?>';
  window.STORE_VAT_PERCENT = <?php echo $storeVatPercent; ?>;
  window.STORE_TAX_ID = '<?php echo htmlspecialchars($company['tax_id'] ?? ''); ?>';
  var discountStr  = $('#cartDiscount').val() || '0';
  var discountVal  = parseFloat(discountStr.replace(/\D/g, '')) || 0;
  var taxType      = window.STORE_TAX_TYPE;
  var vatPercent   = window.STORE_VAT_PERCENT;
  var amtAfterDisc = Math.max(0, subtotal - discountVal);
  var vatVal       = 0;
  var netTotal     = amtAfterDisc;

  if (vatPercent > 0 && taxType !== 'none') {
    if (taxType === 'exclusive') {
      vatVal = Math.round(amtAfterDisc * (vatPercent / 100));
      netTotal = amtAfterDisc + vatVal;
      $('#cartVatRow').attr('style', 'display: flex !important;');
      $('#cartVatLabel').text('ອມພ (' + vatPercent + '%):');
      $('#cartVat').text(vatVal.toLocaleString() + ' ₭');
    } else {
      vatVal = Math.round(amtAfterDisc - (amtAfterDisc / (1 + (vatPercent / 100))));
      netTotal = amtAfterDisc;
      $('#cartVatRow').attr('style', 'display: flex !important;');
      $('#cartVatLabel').text('ລວມ ອມພ (' + vatPercent + '%):');
      $('#cartVat').text(vatVal.toLocaleString() + ' ₭');
    }
  } else {
    vatVal = 0;
    netTotal = amtAfterDisc;
    $('#cartVatRow').attr('style', 'display: none !important;');
  }

  var lineCount = cart.length;
  var countText = cartCountText(lineCount, totalItemsCount);

  $('#cartItemCountBadge').text(countText);
  $('#cartSubtotal').text(subtotal.toLocaleString() + ' ₭');
  $('#cartTotal').text(netTotal.toLocaleString() + ' ₭');

  if (totalItemsCount > 0) {
    $('#mobileCartCountBadge').text(lineCount).css('display', 'inline-block');
    $('#floatingCartItemCount').text(countText);
    $('#floatingCartTotal').html(netTotal.toLocaleString() + ' ₭ <i class="fas fa-chevron-right ml-1"></i>');
    if (window.innerWidth < 992 && $('#mobileTabProductsBtn').hasClass('active')) {
      $('#mobileFloatingCartBar').fadeIn(150);
    }
  } else {
    $('#mobileCartCountBadge').text('0').hide();
    $('#mobileFloatingCartBar').hide();
  }

  $('#btnCheckout').prop('disabled', false).removeClass('disabled');
  $('#btnClearCart').prop('disabled', false).removeClass('disabled');
  $('#btnHoldOrder, #btnHoldCart').prop('disabled', false).removeClass('disabled');

  updateProductGridBadges();
  saveCurrentBillState();
  broadcastCustomerDisplay('shopping');
}

// --- STOCK CHECK HELPERS & ALERTS ---
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
  Swal.fire({
    icon: isZero ? 'error' : 'warning',
    title: isZero ? '⚠️ ເຕືອນ: ສິນຄ້າໝົດແລ້ວ!' : 'ເຕືອນ: ສິນຄ້າໃກ້ຈະໝົດ!',
    text: isZero 
      ? 'ສິນຄ້າ "' + productName + '" ໝົດແລ້ວ! (ເຫຼືອ 0 ' + (unitName || 'ອັນ') + ')'
      : 'ສິນຄ້າ "' + productName + '" ໃກ້ຈະໝົດແລ້ວ! (ເຫຼືອພຽງ ' + remainingQty.toLocaleString() + ' ' + (unitName || 'ອັນ') + ')',
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

  // 3. Update every product card stock & badges dynamically
  var localStocks = {};
  try {
    localStocks = JSON.parse(localStorage.getItem('pos_local_stocks') || '{}');
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

    var totalQtyInCart = productCartQtyMap[pid] || 0;
    var badge = $('.product-qty-badge-' + pid);
    if (badge.length) {
      if (totalQtyInCart > 0) {
        badge.text(totalQtyInCart).css('display', 'block').show();
      } else {
        badge.hide().text('0');
      }
    }

    var cardEl = $('.product-card-' + pid);
    var statusBadge = $('.stock-status-badge-' + pid);
    var infoWrap = $('.stock-info-wrap-' + pid);

    if (remainingStock <= 0) {
      if (statusBadge.length) {
        statusBadge.text('ໝົດແລ້ວ').removeClass('low-stock').addClass('out-of-stock').css('display', 'inline-block').show();
      }
      if (cardEl.length) {
        cardEl.css({'border': '2px solid #ef4444', 'opacity': '0.82', 'background': '#fff5f5'});
      }
      if (infoWrap.length) {
        infoWrap.removeClass('in-stock low-stock').addClass('out-of-stock');
      }
    } else if (remainingStock <= 10) {
      if (statusBadge.length) {
        statusBadge.text('ໃກ້ໝົດ').removeClass('out-of-stock').addClass('low-stock').css('display', 'inline-block').show();
      }
      if (cardEl.length) {
        cardEl.css({'border': '2px solid #f59e0b', 'opacity': '1.0', 'background': '#ffffff'});
      }
      if (infoWrap.length) {
        infoWrap.removeClass('in-stock out-of-stock').addClass('low-stock');
      }
    } else {
      if (statusBadge.length) {
        statusBadge.hide().removeClass('out-of-stock low-stock');
      }
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
  if (cart.length === 0) {
    Swal.fire({
      icon: 'info',
      title: 'ແຈ້ງເຕືອນ',
      text: 'ກະຕ່າສິນຄ້າຫວ່າງເປົ່າຢູ່ແລ້ວ!',
      confirmButtonColor: '#244886'
    });
    return;
  }
  Swal.fire({
    title: 'ຢືນຢັນລ້າງກະຕ່າ?',
    text: 'ລາຍການສິນຄ້າທັງໝົດໃນກະຕ່າຈະຖືກລຶບອອກ!',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'ລ້າງກະຕ່າ',
    cancelButtonText: 'ຍົກເລີກ'
  }).then(function(result) {
    if (result.isConfirmed) {
      cart = [];
      updateCartUI();
    }
  });
}

function buildAvailableUnits(product) {
  var units = [];
  var baseUnitName = product.unit ? product.unit : 'ອັນ';
  var basePrice = parseFloat(product.price) || 0;
  var baseBprice = parseFloat(product.bprice) || 0;

  units.push({
    unit_name: baseUnitName,
    multiplier: 1,
    price: basePrice,
    bprice: baseBprice
  });

  if (product.extra_units && product.extra_units.length > 0) {
    product.extra_units.forEach(function(eu) {
      units.push({
        unit_name: eu.unit_name,
        multiplier: parseInt(eu.multiplier) || 1,
        price: parseFloat(eu.price) || 0,
        bprice: parseFloat(eu.bprice) || 0
      });
    });
  }

  return units;
}

function _doAddToCart(product, unitObj) {
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
      title: 'ສິນຄ້າໝົດແລ້ວ!',
      html: '<div style="font-size:1.05rem; font-weight:700; color:#ef4444; margin-bottom:6px;">ສິນຄ້າ "' + product.product_name + '"</div>' +
            '<div class="text-dark font-weight-bold" style="font-size:0.92rem;">ບໍ່ສາມາດກົດຂາຍໄດ້ ເນື່ອງຈາກຈຳນວນສິນຄ້າໃນຄັງເປັນ 0</div>' +
            '<small class="text-muted d-block mt-2">ກະລຸນາເພີ່ມສະຕັອກ ຫຼື ເລືອກສິນຄ້າອື່ນ</small>',
      confirmButtonText: '<i class="fas fa-check mr-1"></i> ຕົກລົງ',
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
      title: 'ຈຳນວນສິນຄ້າບໍ່ພໍ!',
      html: '<div style="font-size:1.0rem; font-weight:700; color:#d97706;">ສິນຄ້າ "' + product.product_name + '" ເຫຼືອພຽງ ' + currentRemaining.toLocaleString() + ' ' + (product.unit || 'ອັນ') + '</div><div class="mt-2 text-dark" style="font-size:0.88rem;">ບໍ່ສາມາດຂາຍເກີນຈຳນວນທີ່ມີໃນສາງໄດ້.</div>',
      confirmButtonText: 'ຕົກລົງ',
      confirmButtonColor: '#f59e0b'
    });
    return false;
  }

  var cartKey = product.product_id + '_' + unitName;
  var existing = cart.find(function(i) { return i.cartKey === cartKey; });

  if (existing) {
    existing.quantity += 1;
  } else {
    cart.push({
      cartKey: cartKey,
      product_id: product.product_id,
      product_name: product.product_name,
      unit_name: unitName,
      unit_price: unitPrice,
      original_price: origPrice,
      cost_price: costPrice,
      multiplier: multiplier,
      quantity: 1,
      image: product.img_url || product.image || ''
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
    var s1 = trimStr(u1);
    var s2 = trimStr(u2);
    if (s2 === 'all' || s2 === '' || s1 === 'all' || s1 === '') return true;
    if (s1 === s2) return true;
    // Normalize Lao spelling variations for crates/cartons (ເຊັ່ນ: ເກັດ vs ເເກັດ, ເເກັດ vs ເເກັດ)
    var norm1 = s1.replace(/ເເກັດ|ເກັດ|ແກັດ|ແກັດ/g, 'ເກັດ');
    var norm2 = s2.replace(/ເເກັດ|ເກັດ|ແກັດ|ແກັດ/g, 'ເກັດ');
    if (norm1 === norm2) return true;
    return s1.indexOf(s2) !== -1 || s2.indexOf(s1) !== -1;
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

// --- MOBILE TAB SWITCHER ---
function switchMobilePosTab(tab) {
  if (window.innerWidth >= 992) return;

  if (tab === 'products') {
    $('.pos-products').removeClass('mobile-hidden').addClass('mobile-active').show();
    $('.pos-cart').removeClass('mobile-active').addClass('mobile-hidden').hide();
    $('#mobileTabProductsBtn').addClass('btn-primary active').removeClass('btn-outline-primary');
    $('#mobileTabCartBtn').removeClass('btn-primary active').addClass('btn-outline-primary');
    
    if (typeof cart !== 'undefined' && cart.length > 0) {
      $('#mobileFloatingCartBar').fadeIn(150);
    } else {
      $('#mobileFloatingCartBar').hide();
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
  } else if (tab === 'cart') {
    $('.pos-products').removeClass('mobile-active').addClass('mobile-hidden').hide();
    $('.pos-cart').removeClass('mobile-hidden').addClass('mobile-active').show();
    $('#mobileTabProductsBtn').removeClass('btn-primary active').addClass('btn-outline-primary');
    $('#mobileTabCartBtn').addClass('btn-primary active').removeClass('btn-outline-primary');
    $('#mobileFloatingCartBar').hide();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
}

// Live Stock Automatic Sync Polling
var POS_STOCK_POLL_COOLDOWN_MS = 8000;

function pollPOSLiveStock() {
  // Skip entirely for a few seconds after any checkout/broadcast — that write is already
  // correct, and the server's own row may not even reflect it yet if this fires right on
  // its heels, which is exactly what caused stock to bounce back up and then self-correct.
  var lastWrite = window.__posLastAuthoritativeWriteAt || 0;
  if (lastWrite && (Date.now() - lastWrite) < POS_STOCK_POLL_COOLDOWN_MS) return;

  var seqAtRequest = window.__posStockApplySeq || 0;
  $.ajax({
    url: (window.POS_BACKEND_URL || '../../api/pos_backend.php') + '?action=get_live_stocks&_=' + Date.now(),
    type: 'GET',
    cache: false,
    dataType: 'json',
    success: function(res) {
      // A checkout (or another tab's broadcast) already applied a newer, authoritative
      // update while this request was in flight — this response is stale, discard it
      // instead of bouncing the just-decremented stock number back up.
      if ((window.__posStockApplySeq || 0) !== seqAtRequest) return;

      if (res && res.success && res.stocks && res.stocks.length > 0) {
        var localStocks = {};
        try {
          localStocks = JSON.parse(localStorage.getItem('pos_local_stocks') || '{}');
        } catch(e) {}

        var updated = [];
        res.stocks.forEach(function(s) {
          var serverQty = parseFloat(s.qty);
          var pid = s.product_id;
          var curLocalQty = null;
          if (typeof localStocks[pid] !== 'undefined') {
            curLocalQty = parseFloat(localStocks[pid]);
          } else if (typeof localStocks[String(pid)] !== 'undefined') {
            curLocalQty = parseFloat(localStocks[String(pid)]);
          } else if (typeof localStocks[parseInt(pid, 10)] !== 'undefined') {
            curLocalQty = parseFloat(localStocks[parseInt(pid, 10)]);
          }

          if (curLocalQty === null || serverQty !== curLocalQty) {
            updated.push({ product_id: pid, new_qty: serverQty });
          }
        });
        if (updated.length > 0 && typeof applyStockUpdateFromBroadcast === 'function') {
          applyStockUpdateFromBroadcast(updated);
        }
      }
    }
  });
}

$(document).ready(function() {
  setInterval(pollPOSLiveStock, 5000);
});
</script>
