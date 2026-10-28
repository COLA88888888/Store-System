<?php
/*
 * ສ່ວນ: ສະຕັອກ ແລະ ຈຳນວນໃນກະຕ່າ
 * ສະຕັອກຄົງເຫຼືອ, ສຽງ/ແຈ້ງເຕືອນສິນຄ້າໃກ້ໝົດ, ປ້າຍສະຕັອກໃນຕາຕະລາງສິນຄ້າ, ເພີ່ມ/ຫຼຸດ/ປ່ຽນຈຳນວນ, ລົບ
 * ແລະ ລ້າງກະຕ່າ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/pos/partials/js/pos_cart_js.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>// ຂໍ້ຄວາມ popup ສ່ວນຫຼຸດ + ສະຕັອກບໍ່ພໍ (lang/*/pos_discount.php). ເປັນ window.I18N_* ຈຶ່ງຖືກປ່ຽນຄ່າເອງເມື່ອກົດທຸງພາສາ
// (home/partials/dashboard/js_language.php) — popup ສ້າງ HTML ໃໝ່ທຸກຄັ້ງທີ່ເປີດ ຈຶ່ງເປັນພາສາໃໝ່ທັນທີ.
var I18N_POS_DISC = <?php echo tjson(require dirname(__DIR__, 5) . '/lang/lo/pos_discount.php'); ?>;
function pdT(key) {
  var v = I18N_POS_DISC[key] || key;
  var args = Array.prototype.slice.call(arguments, 1);
  return args.length ? v.replace(/%s/g, function () { return args.length ? args.shift() : ''; }) : v;
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
          title: pdT('pos_disc.stock_short_title'),
          html: '<div style="font-size:1.0rem; font-weight:600; color:#ef4444;">' + pdT('pos_disc.cannot_add_more', item.product_name) + '</div><div class="mt-2 text-muted" style="font-size:0.88rem;">' + pdT('pos_disc.remaining') + ' <b>' + Math.max(0, remaining).toLocaleString() + '</b> ' + item.unit_name + '</div>',
          confirmButtonText: pdT('pos_disc.ok'),
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
          title: pdT('pos_disc.stock_short_title'),
          html: '<div style="font-size:1.0rem; font-weight:600; color:#ef4444;">' + pdT('pos_disc.not_enough', item.product_name) + '</div><div class="mt-2 text-muted" style="font-size:0.88rem;">' + pdT('pos_disc.max_sell') + ' <b>' + (oldQty + maxAdd).toLocaleString() + '</b> ' + item.unit_name + '</div>',
          confirmButtonText: pdT('pos_disc.ok'),
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

// ປຸ່ມ "ລ້າງ": ຖາມຢືນຢັນກ່ອນ (ຍົກເລີກ / ຕົກລົງ) ແລ້ວຈຶ່ງລ້າງ
function confirmClearCart() {
  if (cart.length === 0) return;
  Swal.fire({
    icon: 'warning',
    title: <?php echo json_encode(t('pos.clear_confirm_title', 'ລ້າງລາຍການທັງໝົດ?'), JSON_UNESCAPED_UNICODE); ?>,
    text: <?php echo json_encode(t('pos.clear_confirm_text', 'ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການລ້າງລາຍການທັງໝົດ?'), JSON_UNESCAPED_UNICODE); ?>,
    showCancelButton: true,
    focusCancel: true,
    confirmButtonText: <?php echo json_encode(t('pos.ok', 'ຕົກລົງ'), JSON_UNESCAPED_UNICODE); ?>,
    cancelButtonText: <?php echo json_encode(t('pos.cancel', 'ຍົກເລີກ'), JSON_UNESCAPED_UNICODE); ?>,
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#64748b'
  }).then(function(r) { if (r.isConfirmed) clearCart(); });
}

function clearCart() {
  // ລ້າງກະຕ່າທັນທີ (ປຸ່ມ "ລ້າງ" ເອີ້ນຜ່ານ confirmClearCart() ທີ່ຖາມຢືນຢັນກ່ອນ)
  if (cart.length === 0) return;
  cart = [];
  // The cleared cart is no longer the recalled held bill — that bill stays held (unpaid).
  if (typeof setCurrentHeldId === 'function') setCurrentHeldId(null);
  if (typeof posDropAllRedemptions === 'function') posDropAllRedemptions();
  updateCartUI();
}

// --- LINE ITEM DISCOUNT MODAL & MANAGEMENT ---
var _currentDiscModalCartKey = null;
var _currentDiscModalMode = 'percent';

function openItemDiscountModal(cartKey) {
  var item = cart.find(function(i) { return i.cartKey === cartKey; });
  if (!item) return;

  _currentDiscModalCartKey = cartKey;
  var origPrice = (typeof item.original_price !== 'undefined' && item.original_price !== null) ? parseFloat(item.original_price) : parseFloat(item.unit_price);
  item.original_price = origPrice;

  _currentDiscModalMode = (item.item_discount_type === 'amount') ? 'amount' : 'percent';
  var initialVal = '';
  if (_currentDiscModalMode === 'percent') {
    initialVal = (item.item_discount_val > 0) ? item.item_discount_val : (item.item_discount ? item.item_discount : '');
  } else {
    var rawAmt = (item.item_discount_val > 0) ? item.item_discount_val : (item.item_discount ? item.item_discount : '');
    initialVal = (rawAmt > 0) ? Number(rawAmt).toLocaleString('en-US') : '';
  }

  var hasDiscount = (parseFloat(item.item_discount) || 0) > 0;
  var symbol = (window.POSCUR && POSCUR.symbol) ? POSCUR.symbol : '<?php echo kipSymbol(); ?>';

  var modalHtml = `
    <style>
      #swalItemDiscInput::-webkit-outer-spin-button,
      #swalItemDiscInput::-webkit-inner-spin-button {
        -webkit-appearance: none !important;
        margin: 0 !important;
      }
      #swalItemDiscInput {
        -moz-appearance: textfield !important;
      }
      /* ໂທລະສັບ: ຟອມບໍ່ເກີນຈໍ — ກວ້າງ/ສູງບໍ່ເກີນໜ້າຈໍ (ເກີນ → ເລື່ອນໃນຟອມ), ໄລຍະຫ່າງນ້ອຍລົງ, ປຸ່ມ % ແບ່ງເທົ່າກັນ 5 ຊ່ອງ */
      .item-disc-swal { width: min(32em, calc(100vw - 16px)) !important; max-height: calc(100vh - 16px); max-height: calc(100dvh - 16px); overflow-y: auto; }
      @media (max-width: 576px) {
        .item-disc-swal { padding: 14px 12px 12px !important; }
        .item-disc-swal .swal2-title { font-size: 1.3rem !important; padding: 0 !important; margin: 0 0 4px !important; }
        .item-disc-swal .swal2-html-container { margin: 6px 0 0 !important; padding: 0 !important; }
        .item-disc-swal .item-disc-presets { display: grid !important; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 4px !important; }
        .item-disc-swal .item-disc-presets .btn { padding: 6px 0; min-width: 0; }
        .item-disc-swal .swal2-actions { flex-wrap: wrap; gap: 6px; margin-top: 10px !important; }
        .item-disc-swal .swal2-actions .btn { margin: 0 !important; flex: 1 1 auto; }
      }
    </style>
    <div class="item-disc-modal-wrap text-left" style="font-family: inherit;">
      <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
        <div style="width:40px; height:40px; border-radius:10px; background:#eff6ff; color:#2563eb; display:flex; align-items:center; justify-content:center; font-size:1.15rem; flex-shrink:0;">
          <i class="fas fa-tags"></i>
        </div>
        <div class="ml-3" style="min-width:0; flex:1;">
          <div class="font-weight-bold text-dark text-truncate" style="font-size:1.0rem;" title="${item.product_name}">${item.product_name}</div>
          <div class="text-muted small">
            ${pdT('pos_disc.unit')} <b class="text-dark">${item.unit_name || pdT('pos_disc.unit_default')}</b> | ${pdT('pos_disc.orig_price')} <b class="text-primary">${POSCUR.fmt(origPrice)}</b>
          </div>
        </div>
      </div>

      <!-- Mode Selector -->
      <div class="d-flex mb-3" style="background:#f1f5f9; padding:4px; border-radius:8px; gap:4px;">
        <button type="button" id="swalDiscModePct" class="btn btn-sm flex-fill font-weight-bold ${_currentDiscModalMode === 'percent' ? 'bg-white shadow-sm text-primary' : 'text-muted'}" style="border-radius:6px; border:none; padding:8px 12px; transition:all 0.15s ease;" onclick="swalSwitchItemDiscMode('percent')">
          <i class="fas fa-percent mr-1"></i> ${pdT('pos_disc.mode_pct')} (%)
        </button>
        <button type="button" id="swalDiscModeAmt" class="btn btn-sm flex-fill font-weight-bold ${_currentDiscModalMode === 'amount' ? 'bg-white shadow-sm text-primary' : 'text-muted'}" style="border-radius:6px; border:none; padding:8px 12px; transition:all 0.15s ease;" onclick="swalSwitchItemDiscMode('amount')">
          <i class="fas fa-coins mr-1"></i> ${pdT('pos_disc.mode_amt')} (${symbol})
        </button>
      </div>

      <!-- Quick Presets for Percent -->
      <div id="swalDiscQuickPct" class="mb-3" style="${_currentDiscModalMode === 'percent' ? '' : 'display:none;'}">
        <div class="text-muted small mb-1 font-weight-bold">${pdT('pos_disc.quick')}</div>
        <div class="d-flex item-disc-presets" style="gap:6px;">
          <button type="button" class="btn btn-outline-secondary btn-sm flex-fill font-weight-bold" style="border-radius:6px;" onclick="swalSetItemDiscVal(5)">5%</button>
          <button type="button" class="btn btn-outline-secondary btn-sm flex-fill font-weight-bold" style="border-radius:6px;" onclick="swalSetItemDiscVal(10)">10%</button>
          <button type="button" class="btn btn-outline-secondary btn-sm flex-fill font-weight-bold" style="border-radius:6px;" onclick="swalSetItemDiscVal(15)">15%</button>
          <button type="button" class="btn btn-outline-secondary btn-sm flex-fill font-weight-bold" style="border-radius:6px;" onclick="swalSetItemDiscVal(20)">20%</button>
          <button type="button" class="btn btn-outline-secondary btn-sm flex-fill font-weight-bold" style="border-radius:6px;" onclick="swalSetItemDiscVal(50)">50%</button>
        </div>
      </div>

      <!-- Input Field -->
      <div class="form-group mb-3">
        <label id="swalDiscInputLabel" class="font-weight-bold text-dark small mb-1 d-flex justify-content-between align-items-center">
          <span id="swalDiscInputLabelText">${_currentDiscModalMode === 'percent' ? pdT('pos_disc.input_pct') + ' (%):' : pdT('pos_disc.input_amt') + ' (' + symbol + '):'}</span>
          <span id="swalDiscConvertedHint" class="text-danger font-weight-bold" style="font-size:0.85rem;"></span>
        </label>
        <div class="input-group">
          <div class="input-group-prepend">
            <span class="input-group-text font-weight-bold bg-light" id="swalDiscInputPrefix" style="border-radius:6px 0 0 6px;">
              ${_currentDiscModalMode === 'percent' ? '%' : symbol}
            </span>
          </div>
          <input type="text" inputmode="numeric" id="swalItemDiscInput" class="form-control form-control-lg text-right font-weight-bold" placeholder="0" value="${initialVal || ''}" style="border-radius:0 6px 6px 0; font-size:1.2rem; color:#dc2626;" oninput="swalHandleItemDiscInput(this)" autofocus>
        </div>
      </div>

      <!-- Live Preview -->
      <div class="p-3 mb-2" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; font-size:0.88rem;">
        <div class="d-flex justify-content-between mb-1">
          <span class="text-muted">${pdT('pos_disc.per_unit')}</span>
          <span class="font-weight-bold text-danger" id="swalPreviewDiscPerUnit"></span>
        </div>
        <div class="d-flex justify-content-between mb-1">
          <span class="text-muted">${pdT('pos_disc.new_price')}</span>
          <span class="font-weight-bold text-dark" id="swalPreviewNewPrice"></span>
        </div>
        <div class="d-flex justify-content-between pt-2 mt-1 border-top" style="border-color:#e2e8f0 !important;">
          <span class="text-muted">${pdT('pos_disc.line_total')} (<span id="swalPreviewQty">${item.quantity}</span> ${item.unit_name || pdT('pos_disc.unit_default')}):</span>
          <span class="font-weight-bold text-success" id="swalPreviewLineTotal" style="font-size:1.05rem;"></span>
        </div>
        <div class="text-right text-muted small mt-1" id="swalPreviewTotalSaved"></div>
      </div>
    </div>
  `;

  Swal.fire({
    title: pdT('pos_disc.title'),
    html: modalHtml,
    showCancelButton: true,
    showDenyButton: hasDiscount,
    confirmButtonText: '<i class="fas fa-check mr-1"></i> ' + pdT('pos_disc.btn_save'),
    denyButtonText: '<i class="fas fa-trash-alt mr-1"></i> ' + pdT('pos_disc.btn_clear'),
    cancelButtonText: pdT('pos_disc.btn_cancel'),
    confirmButtonColor: '#2563eb',
    denyButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    focusConfirm: false,
    customClass: {
      popup: 'item-disc-swal',
      confirmButton: 'btn btn-primary font-weight-bold px-3',
      denyButton: 'btn btn-danger font-weight-bold px-3',
      cancelButton: 'btn btn-secondary font-weight-bold px-3'
    },
    didOpen: function() {
      swalRecalcItemDisc();
      setTimeout(function() {
        var el = document.getElementById('swalItemDiscInput');
        if (el) { el.focus(); el.select(); }
      }, 100);
    },
    preConfirm: function() {
      var rawStr = ($('#swalItemDiscInput').val() || '').replace(/,/g, '');
      var rawVal = parseFloat(rawStr) || 0;
      return { mode: _currentDiscModalMode, value: rawVal };
    }
  }).then(function(result) {
    if (result.isConfirmed) {
      if (result.value && result.value.value > 0) {
        setItemDiscount(cartKey, result.value.mode, result.value.value);
      } else {
        clearItemDiscount(cartKey);
      }
    } else if (result.isDenied) {
      clearItemDiscount(cartKey);
    }
  });
}

function swalHandleItemDiscInput(input) {
  if (_currentDiscModalMode === 'amount') {
    var raw = input.value.replace(/[^0-9]/g, '');
    input.value = (raw === '') ? '' : Number(raw).toLocaleString('en-US');
  } else {
    var s = input.value.replace(/[^0-9.]/g, '');
    var parts = s.split('.');
    if (parts.length > 2) s = parts[0] + '.' + parts.slice(1).join('');
    if (parseFloat(s) > 100) s = '100';
    input.value = s;
  }
  swalRecalcItemDisc();
}

function swalSwitchItemDiscMode(mode) {
  if (_currentDiscModalMode === mode) return;
  var oldMode = _currentDiscModalMode;
  _currentDiscModalMode = mode;
  var symbol = (window.POSCUR && POSCUR.symbol) ? POSCUR.symbol : '<?php echo kipSymbol(); ?>';

  var item = cart.find(function(i) { return i.cartKey === _currentDiscModalCartKey; });
  var origPrice = item ? ((typeof item.original_price !== 'undefined' && item.original_price !== null) ? parseFloat(item.original_price) : parseFloat(item.unit_price)) : 0;

  var inputEl = document.getElementById('swalItemDiscInput');
  var currentValStr = inputEl ? inputEl.value : '';

  if (mode === 'amount') {
    // ສະຫຼັບຈາກ % ໄປເປັນ ຈຳນວນເງິນ (ກີບ): ຄິດໄລ່ຈຳນວນເງິນຈາກເປີເຊັນ ແລະ ໃສ່ເຄື່ອງໝາຍຈຸດ ,
    var pct = parseFloat(currentValStr.replace(/[^0-9.]/g, '')) || 0;
    if (pct > 100) pct = 100;
    var calcAmt = (pct > 0 && origPrice > 0) ? Math.round(origPrice * (pct / 100)) : 0;
    if (inputEl) {
      inputEl.value = calcAmt > 0 ? Number(calcAmt).toLocaleString('en-US') : '';
    }

    $('#swalDiscModeAmt').addClass('bg-white shadow-sm text-primary').removeClass('text-muted');
    $('#swalDiscModePct').removeClass('bg-white shadow-sm text-primary').addClass('text-muted');
    $('#swalDiscQuickPct').hide();
    $('#swalDiscInputLabelText').text(pdT('pos_disc.input_amt') + ' (' + symbol + '):');
    $('#swalDiscInputPrefix').text(symbol);
  } else {
    // ສະຫຼັບຈາກ ຈຳນວນເງິນ ໄປເປັນ %: ຄິດໄລ່ເປີເຊັນຈາກຈຳນວນເງິນ
    var amt = parseFloat(currentValStr.replace(/,/g, '')) || 0;
    var calcPct = (amt > 0 && origPrice > 0) ? Math.min(100, Math.round((amt / origPrice) * 100 * 10) / 10) : 0;
    if (inputEl) {
      inputEl.value = calcPct > 0 ? calcPct : '';
    }

    $('#swalDiscModePct').addClass('bg-white shadow-sm text-primary').removeClass('text-muted');
    $('#swalDiscModeAmt').removeClass('bg-white shadow-sm text-primary').addClass('text-muted');
    $('#swalDiscQuickPct').show();
    $('#swalDiscInputLabelText').text(pdT('pos_disc.input_pct') + ' (%):');
    $('#swalDiscInputPrefix').text('%');
  }

  swalRecalcItemDisc();
  if (inputEl) { inputEl.focus(); inputEl.select(); }
}

function swalSetItemDiscVal(val) {
  var inputEl = document.getElementById('swalItemDiscInput');
  if (inputEl) {
    if (_currentDiscModalMode === 'amount') {
      inputEl.value = (val > 0) ? Number(val).toLocaleString('en-US') : '';
    } else {
      inputEl.value = val;
    }
  }
  swalRecalcItemDisc();
}

function swalRecalcItemDisc() {
  if (!_currentDiscModalCartKey) return;
  var item = cart.find(function(i) { return i.cartKey === _currentDiscModalCartKey; });
  if (!item) return;

  var origPrice = (typeof item.original_price !== 'undefined' && item.original_price !== null) ? parseFloat(item.original_price) : parseFloat(item.unit_price);
  var qty = parseFloat(item.quantity) || 1;
  var rawStr = ($('#swalItemDiscInput').val() || '').replace(/,/g, '');
  var val = parseFloat(rawStr) || 0;
  if (val < 0) val = 0;

  var discPerUnit = 0;
  if (_currentDiscModalMode === 'percent') {
    if (val > 100) val = 100;
    discPerUnit = Math.round(origPrice * (val / 100));
  } else {
    if (val > origPrice) val = origPrice;
    discPerUnit = Math.round(val);
  }

  var newUnitPrice = Math.max(0, origPrice - discPerUnit);
  var lineTotal = newUnitPrice * qty;
  var totalSaved = discPerUnit * qty;

  var symbol = (window.POSCUR && POSCUR.symbol) ? POSCUR.symbol : '<?php echo kipSymbol(); ?>';

  // ສະແດງທັງຈຳນວນເງິນ ແລະ ເປີເຊັນໃຫ້ເຫັນຈະແຈ້ງ
  if (_currentDiscModalMode === 'percent') {
    if (val > 0) {
      $('#swalDiscConvertedHint').html('= -' + POSCUR.fmt(discPerUnit) + ' / ' + (item.unit_name || pdT('pos_disc.unit_default'))).show();
      $('#swalPreviewDiscPerUnit').html('-' + POSCUR.fmt(discPerUnit) + ' <span class="badge badge-danger ml-1" style="font-size:0.75rem;">' + val + '%</span>');
    } else {
      $('#swalDiscConvertedHint').hide();
      $('#swalPreviewDiscPerUnit').html('-' + POSCUR.fmt(0));
    }
  } else {
    if (val > 0 && origPrice > 0) {
      var equivPct = Math.min(100, Math.round((discPerUnit / origPrice) * 100 * 10) / 10);
      $('#swalDiscConvertedHint').html('= ' + equivPct + '%').show();
      $('#swalPreviewDiscPerUnit').html('-' + POSCUR.fmt(discPerUnit) + ' <span class="badge badge-info ml-1" style="font-size:0.75rem;">' + equivPct + '%</span>');
    } else {
      $('#swalDiscConvertedHint').hide();
      $('#swalPreviewDiscPerUnit').html('-' + POSCUR.fmt(0));
    }
  }

  $('#swalPreviewNewPrice').html(POSCUR.fmt(newUnitPrice));
  $('#swalPreviewLineTotal').html(POSCUR.fmt(lineTotal));

  if (totalSaved > 0) {
    $('#swalPreviewTotalSaved').html('(' + pdT('pos_disc.total_saved') + ' <b class="text-danger">-' + POSCUR.fmt(totalSaved) + '</b>)').show();
  } else {
    $('#swalPreviewTotalSaved').hide();
  }
}

function setItemDiscount(cartKey, type, value) {
  var item = cart.find(function(i) { return i.cartKey === cartKey; });
  if (!item) return;

  var origPrice = (typeof item.original_price !== 'undefined' && item.original_price !== null) ? parseFloat(item.original_price) : parseFloat(item.unit_price);
  item.original_price = origPrice;

  var rawVal = (typeof value === 'string') ? (parseFloat(value.replace(/,/g, '')) || 0) : (parseFloat(value) || 0);
  if (rawVal <= 0) {
    clearItemDiscount(cartKey);
    return;
  }

  var discPerUnit = 0;
  var discText = '';
  if (type === 'percent') {
    rawVal = Math.min(100, Math.max(0, rawVal));
    discPerUnit = Math.round(origPrice * (rawVal / 100));
    discText = rawVal + '%';
  } else {
    type = 'amount';
    rawVal = Math.min(origPrice, Math.max(0, rawVal));
    discPerUnit = Math.round(rawVal);
    discText = POSCUR.fmt(discPerUnit);
  }

  item.item_discount = discPerUnit;
  item.item_discount_type = type;
  item.item_discount_val = rawVal;
  item.item_discount_text = discText;
  item.unit_price = Math.max(0, origPrice - discPerUnit);

  updateCartUI();
}

function clearItemDiscount(cartKey) {
  var item = cart.find(function(i) { return i.cartKey === cartKey; });
  if (!item) return;

  var origPrice = (typeof item.original_price !== 'undefined' && item.original_price !== null) ? parseFloat(item.original_price) : parseFloat(item.unit_price);
  item.unit_price = origPrice;
  item.original_price = origPrice;
  item.item_discount = 0;
  item.item_discount_type = null;
  item.item_discount_val = 0;
  item.item_discount_text = '';

  updateCartUI();
}

