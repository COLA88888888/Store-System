<?php
/*
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * JS ຂອງກະຕ່າສິນຄ້າໃນໜ້າຂາຍ: ຈໍລູກຄ້າ, ສະແດງກະຕ່າ, ກວດສະຕັອກ, ເພີ່ມສິນຄ້າ, ແທັບມືຖື ແລະ ດຶງສະຕັອກສົດ.
 *   - cart/cart_ui.php : ສະແດງກະຕ່າ
 *   - cart/stock_and_qty.php : ສະຕັອກ ແລະ ຈຳນວນໃນກະຕ່າ
 *   - cart/add_to_cart.php : ເພີ່ມສິນຄ້າເຂົ້າກະຕ່າ
 */
?>
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
  customerDisplayChannel = new BroadcastChannel('StoreMini.customer_display_channel');
} catch(e) {}

function broadcastCashierEnter(name) {
  try {
    var cName = name || (typeof window.CURRENT_USER_NAME !== 'undefined' ? window.CURRENT_USER_NAME : '');
    if (!cName) return;
    var enterData = {
      action: 'cashier_enter',
      name: cName,
      time: Date.now()
    };
    if (customerDisplayChannel) {
      customerDisplayChannel.postMessage(enterData);
    }
    try {
      var bc2 = new BroadcastChannel('StoreMini.cashier_channel');
      bc2.postMessage(enterData);
      setTimeout(function() { bc2.close(); }, 800);
    } catch(e) {}
    localStorage.setItem('StoreMini.cashier_entered', JSON.stringify(enterData));
  } catch(e) {}
}
setTimeout(function() { broadcastCashierEnter(); }, 350);

function broadcastCustomerDisplay(checkoutState, extraData) {
  var tenderCur = (window.POSCUR && POSCUR.code) ? {
    code: POSCUR.code,
    symbol: POSCUR.symbol,
    rate: POSCUR.rate,
    decimals: POSCUR.decimals,
    isKip: POSCUR.code === 'KIP'
  } : null;

  var payload = {
    cart: (typeof cart !== 'undefined') ? cart : [],
    customer: (typeof selectedCustomer !== 'undefined') ? selectedCustomer : null,
    // Customer display works in KIP — send the discount already converted to KIP.
    // Includes the loyalty-points discount so the customer sees the same total as the cashier.
    discount: String(Math.round(window.CART_DISCOUNT_KIP || 0) + (window.CART_POINTS_DISCOUNT_KIP || 0)),
    checkoutState: checkoutState || 'shopping',
    tenderCurrency: tenderCur,
    ...(extraData || {})
  };

  localStorage.setItem('StoreMini.customer_display_data', JSON.stringify(payload));
  if (customerDisplayChannel) {
    try {
      customerDisplayChannel.postMessage(payload);
    } catch(e) {}
  }
}

// ດັກຈັບຈໍທີ 2 ລ່ວງໜ້າ (Window Management API) ເພື່ອໃຫ້ຕອນກົດປຸ່ມ ສາມາດເປີດແບບ Synchronous Fullscreen (ຄືກົດ F11) ໄດ້ທັນທີ
window.cachedCustomerDisplayScreen = null;
window.cachedCustomerDisplayScreenObj = null;
if (typeof window.getScreenDetails === 'function') {
  try {
    navigator.permissions.query({ name: 'window-management' }).then(function(res) {
      if (res.state === 'granted') {
        window.getScreenDetails().then(function(details) {
          function cacheOtherScreen() {
            var curr = details.currentScreen;
            var other = details.screens.find(function(s) { return s !== curr; }) || curr;
            if (other) {
              window.cachedCustomerDisplayScreenObj = other;
              window.cachedCustomerDisplayScreen = {
                left: other.availLeft,
                top: other.availTop,
                width: other.availWidth,
                height: other.availHeight
              };
              try { localStorage.setItem('StoreMini.customer_display_screen', JSON.stringify(window.cachedCustomerDisplayScreen)); } catch(e){}
            }
          }
          cacheOtherScreen();
          details.addEventListener('screenschange', cacheOtherScreen);
        }).catch(function(){});
      }
    }).catch(function(){});
  } catch(e) {}
}

function openCustomerDisplayOnScreen(left, top, w, h) {
  var screenW = Math.max(w || 0, window.screen.width || 1920);
  var screenH = Math.max(h || 0, window.screen.height || 1080);

  var features = 'popup=yes,fullscreen=yes,left=' + left + ',top=' + top +
    ',width=' + screenW + ',height=' + screenH +
    ',menubar=no,toolbar=no,location=no,status=no,resizable=yes,scrollbars=no';

  var win = window.open('customer_display.php', 'POSCustomerDisplay', features);
  if (win) {
    try {
      var isWinAlreadyFull = (win.innerWidth >= screenW - 6 && win.innerHeight >= screenH - 6);
      if (!isWinAlreadyFull) {
        win.moveTo(left, top);
        win.resizeTo(screenW, screenH);
      }
      win.focus();
    } catch(e) {}

    // 1) ລອງສັ່ງ HTML5 Fullscreen API ຜ່ານ User Gesture
    var triggerFs = function() {
      try {
        win.focus();
        var doc = win.document;
        var el = doc && (doc.documentElement || doc.body);
        if (!el) return;
        if (doc.fullscreenElement || doc.webkitFullscreenElement) return;
        var rfs = el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen || el.msRequestFullscreen;
        if (rfs) {
          var opts = { navigationUI: 'hide' };
          if (window.cachedCustomerDisplayScreenObj) {
            opts.screen = window.cachedCustomerDisplayScreenObj;
          }
          var p = rfs.call(el, opts);
          if (p && p.catch) {
            p.catch(function() {
              try { rfs.call(el, { navigationUI: 'hide' }).catch(function(){}); } catch(err){}
            });
          }
        }
      } catch(e) {}
    };

    triggerFs();
    setTimeout(triggerFs, 80);
  }

  // 2) ແຈ້ງເຕືອນຜ່ານ Storage ໃຫ້ຈໍລູກຄ້າກວດສອບ ແລະ ກົດ F11 ອັດຕະໂນມັດທັນທີໂດຍບໍ່ຕ້ອງເອົາເມົ້າໄປແຕະ
  try {
    localStorage.setItem('StoreMini.customer_display_trigger_fs', String(Date.now()));
  } catch(e) {}
  broadcastCustomerDisplay('shopping');
  broadcastCashierEnter();
}

function openCustomerDisplayWindow() {
  var w = window.screen.width || 1920;
  var h = window.screen.height || 1080;
  var left = 0;
  var top = 0;

  var cached = window.cachedCustomerDisplayScreen;
  if (!cached) {
    try { cached = JSON.parse(localStorage.getItem('StoreMini.customer_display_screen')); } catch(e) {}
  }

  if (cached && typeof cached.left === 'number') {
    left = cached.left;
    top = cached.top || 0;
    w = Math.max(cached.width || 0, w);
    h = Math.max(cached.height || 0, h);
  } else if (window.screen.availLeft && window.screen.availLeft !== 0) {
    left = window.screen.availLeft;
  } else if (window.screen.isExtended && window.screen.width) {
    left = screen.availWidth || screen.width || 1920;
  }

  openCustomerDisplayOnScreen(left, top, w, h);

  // ຖ້າຍັງບໍ່ມີສິດ Window Management: ຖາມຂໍສິດໃນ Background ສຳລັບຄັ້ງຕໍ່ໄປ
  if (!cached && typeof window.getScreenDetails === 'function') {
    try {
      window.getScreenDetails().then(function(details) {
        var curr = details.currentScreen;
        var other = details.screens.find(function(s) { return s !== curr; });
        if (other) {
          window.cachedCustomerDisplayScreen = {
            left: other.availLeft,
            top: other.availTop,
            width: other.availWidth,
            height: other.availHeight
          };
          try { localStorage.setItem('StoreMini.customer_display_screen', JSON.stringify(window.cachedCustomerDisplayScreen)); } catch(e){}
        }
      }).catch(function(){});
    } catch(e) {}
  }
}

<?php require __DIR__ . '/cart/cart_ui.php'; /* ສະແດງກະຕ່າ */ ?>
<?php require __DIR__ . '/cart/stock_and_qty.php'; /* ສະຕັອກ ແລະ ຈຳນວນໃນກະຕ່າ */ ?>
<?php require __DIR__ . '/cart/add_to_cart.php'; /* ເພີ່ມສິນຄ້າເຂົ້າກະຕ່າ */ ?>
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
          localStocks = JSON.parse(localStorage.getItem('StoreMini.local_stocks') || '{}');
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
