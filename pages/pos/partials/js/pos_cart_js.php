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

function broadcastCustomerDisplay(checkoutState, extraData) {
  var payload = {
    cart: (typeof cart !== 'undefined') ? cart : [],
    customer: (typeof selectedCustomer !== 'undefined') ? selectedCustomer : null,
    // Customer display works in KIP — send the discount already converted to KIP.
    // Includes the loyalty-points discount so the customer sees the same total as the cashier.
    discount: String(Math.round(window.CART_DISCOUNT_KIP || 0) + (window.CART_POINTS_DISCOUNT_KIP || 0)),
    checkoutState: checkoutState || 'shopping',
    ...(extraData || {})
  };

  localStorage.setItem('StoreMini.customer_display_data', JSON.stringify(payload));
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
