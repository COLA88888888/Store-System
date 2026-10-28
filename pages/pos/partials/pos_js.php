<script>
var allProducts = <?php echo json_encode($products); ?>;
var allCustomers = <?php echo json_encode($customersList); ?>;
// ລະຫັດສາຂາຂອງໜ້າຂາຍ — ສ່ວນໜ້າຂອງລະຫັດລູກຄ້າອໍໂຕ້ "<ລະຫັດສາຂາ>-<ລຳດັບ>" (pos_customer_js.php)
var POS_STORE_CODE = <?php echo json_encode(branchStoreCode($pdo, getPosStoreId())); ?>;
var POS_STORE_ID_NUM = <?php echo (int)getPosStoreId(); ?>;
var cart = [];

// "Walk-in customer" label. Kept in a "domain.key" map so the dashboard flag switcher
// re-points it live (no reload); read it through posCustomerDisplayName() at every place
// the name is shown, so a language switch updates the cart panel and the receipt too.
window.POS_RCPT_I18N = window.POS_RCPT_I18N || {};
window.POS_RCPT_I18N['pos.customer_default'] = <?php echo json_encode(t('pos.customer_default', 'ລູກຄ້າທົ່ວໄປ'), JSON_UNESCAPED_UNICODE); ?>;
// Stock badge labels — JS rewrites these on every live-stock recalculation, so read them from
// here (retranslated on a flag click) instead of baking Lao literals.
window.POS_RCPT_I18N['pos.stock_out'] = <?php echo json_encode(t('pos.stock_out', 'ໝົດແລ້ວ'), JSON_UNESCAPED_UNICODE); ?>;
window.POS_RCPT_I18N['pos.stock_low'] = <?php echo json_encode(t('pos.stock_low', 'ໃກ້ໝົດ'), JSON_UNESCAPED_UNICODE); ?>;
window.posStockLabel = function (kind) {
  var k = (kind === 'out') ? 'pos.stock_out' : 'pos.stock_low';
  return (window.POS_RCPT_I18N && POS_RCPT_I18N[k]) || (kind === 'out' ? 'ໝົດແລ້ວ' : 'ໃກ້ໝົດ');
};

// Stock / cart alert strings — built with interpolation at click time, so the flag switcher's
// DOM walker can't reach them. Kept here (retranslated live) and read via posT('key', a, b...)
// which fills each %s in order.
Object.assign(window.POS_RCPT_I18N, <?php echo tjson([
  'pos.alert_out_of_stock_title'  => 'ສິນຄ້າໝົດແລ້ວ!',
  'pos.alert_product_quoted'      => 'ສິນຄ້າ "%s"',
  'pos.alert_out_of_stock_body'   => 'ບໍ່ສາມາດກົດຂາຍໄດ້ ເນື່ອງຈາກຈຳນວນສິນຄ້າໃນຄັງເປັນ 0',
  'pos.alert_out_of_stock_hint'   => 'ກະລຸນາເພີ່ມສະຕັອກ ຫຼື ເລືອກສິນຄ້າອື່ນ',
  'pos.alert_qty_not_enough_title'=> 'ຈຳນວນສິນຄ້າບໍ່ພໍ!',
  'pos.alert_remaining_only'      => 'ສິນຄ້າ "%s" ເຫຼືອພຽງ %s %s',
  'pos.alert_cannot_sell_over'    => 'ບໍ່ສາມາດຂາຍເກີນຈຳນວນທີ່ມີໃນສາງໄດ້.',
  'pos.toast_out_title'           => '⚠️ ເຕືອນ: ສິນຄ້າໝົດແລ້ວ!',
  'pos.toast_low_title'           => 'ເຕືອນ: ສິນຄ້າໃກ້ຈະໝົດ!',
  'pos.toast_out_body'            => 'ສິນຄ້າ "%s" ໝົດແລ້ວ! (ເຫຼືອ 0 %s)',
  'pos.toast_low_body'            => 'ສິນຄ້າ "%s" ໃກ້ຈະໝົດແລ້ວ! (ເຫຼືອພຽງ %s %s)',
  'pos.btn_ok'                    => 'ຕົກລົງ',
  'pos.unit_generic'              => 'ອັນ',
  'pos.lan_print_failed'          => 'ພິມໃບຍ່ອຍຜ່ານ LAN ບໍ່ສຳເລັດ: %s',
]); ?>);
window.posT = function (key) {
  var s = (window.POS_RCPT_I18N && POS_RCPT_I18N[key]) || key;
  var args = Array.prototype.slice.call(arguments, 1);
  return s.replace(/%s/g, function () { return args.length ? args.shift() : ''; });
};
// The default name may have been saved in any language on older bills — recognise them all.
window.POS_CUSTOMER_DEFAULT_ALIASES = ['ລູກຄ້າທົ່ວໄປ', 'Walk-in Customer', 'ลูกค้าทั่วไป', '散客'];
window.posCustomerDisplayName = function (name) {
  name = (name == null ? '' : String(name)).trim();
  if (!name || window.POS_CUSTOMER_DEFAULT_ALIASES.indexOf(name) !== -1) {
    return (window.POS_RCPT_I18N && POS_RCPT_I18N['pos.customer_default']) || 'ລູກຄ້າທົ່ວໄປ';
  }
  return name;
};

// ==========================================================================
// MULTI-CURRENCY DISPLAY LAYER
// All cart math, the checkout payload, and everything stored in the DB stay in
// KIP. POSCUR only converts at the display/input boundary. When the active
// currency is KIP (the default) every method is an identity of the old code,
// so the KIP path is unchanged.
// ==========================================================================
// ປັດເປັນຈຳນວນເຕັມ: ເສດບໍ່ກາຍ .50 → ປັດລົງ (160.50 → 160), ກາຍ .50 → ປັດຂຶ້ນ (160.51 → 161).
// ຕັດ error ຂອງ floating point ອອກກ່ອນ (ເຊັ່ນ 160.5000000001 ຍັງນັບເປັນ .50).
window.posRoundUnits = function (v) {
  v = Math.round((parseFloat(v) || 0) * 1e6) / 1e6;
  var neg = v < 0; if (neg) v = -v;
  var f = Math.floor(v);
  var r = (v - f > 0.5) ? f + 1 : f;
  return neg ? -r : r;
};
window.posCeilUnits = window.posRoundUnits; // ຊື່ເກົ່າ (ເຂົ້າກັນໄດ້)
window.POSCUR = (function () {
  var rates = window.POS_EX_RATES || { KIP: 1, THB: 0, USD: 0, CNY: 0 };
  var metaAll = window.POS_CURRENCY_META || {
    KIP: { name: 'ກີບ', symbol: '<?php echo kipSymbol(); ?>', decimals: 0 },
    THB: { name: 'ບາດ', symbol: '฿', decimals: 0 },
    USD: { name: 'ໂດລາ', symbol: '$', decimals: 0 },
    CNY: { name: 'ຢວນ', symbol: '¥', decimals: 0 }
  };
  // Mutable so the checkout modal can switch the tender currency per bill via
  // POSCUR.setCurrency(); every helper below reads these at call time.
  var code, rate, meta, isKip;

  function setCurrency(next) {
    next = String(next || 'KIP').toUpperCase();
    if (!metaAll[next]) next = 'KIP';
    var r = (next === 'KIP') ? 1 : (parseFloat(rates[next]) || 0);
    if (next !== 'KIP' && r <= 0) { next = 'KIP'; r = 1; }   // rate not set -> fall back to KIP
    code = next; rate = r; meta = metaAll[code]; isKip = (code === 'KIP');
    // keep the exposed snapshot properties in sync for callers that read them directly
    if (window.POSCUR) {
      POSCUR.code = code; POSCUR.symbol = meta.symbol; POSCUR.name = meta.name;
      POSCUR.decimals = meta.decimals; POSCUR.rate = rate; POSCUR.isKip = isKip;
    }
    return code;
  }
  setCurrency(window.POS_DISPLAY_CURRENCY || 'KIP');

  // ສະກຸນຕ່າງປະເທດ: ກີບ ÷ ອັດຕາທີ່ຕັ້ງໄວ້ (ກີບຕໍ່ 1 ໜ່ວຍ) ແລ້ວປັດເປັນຈຳນວນເຕັມ: ເສດບໍ່ກາຍ .50 ປັດລົງ, ກາຍ .50 ປັດຂຶ້ນ
  // (ເຊັ່ນ 104,325 ₭ ÷ 650 = 160.50 → 160 ฿; 104,400 ₭ ÷ 650 = 160.62 → 161 ฿).
  function fromKip(kip) {
    kip = parseFloat(kip) || 0;
    return isKip ? Math.round(kip) : posRoundUnits(kip / rate);
  }
  // A typed/displayed amount, parsed to a plain number in the display currency
  // (no symbol, no thousands separators), rounded to this currency's own decimals.
  function parseUnits(str) {
    var p = Math.pow(10, meta.decimals);
    if (typeof str === 'number') return Math.round(str * p) / p;
    str = String(str == null ? '' : str);
    var n = isKip ? (parseFloat(str.replace(/[^\d]/g, '')) || 0) : (parseFloat(str.replace(/[^0-9.]/g, '')) || 0);
    return Math.round(n * p) / p;
  }
  // Parse a user-typed amount (already in the display currency) back to whole KIP.
  function toKip(str) {
    var n = parseUnits(str);
    return isKip ? n : Math.round(n * rate);
  }
  // Numeric string in the display currency (no symbol) — for pre-filling inputs.
  function num(kip) {
    var v = fromKip(kip);
    return isKip
      ? Math.round(v).toLocaleString('en-US')
      : v.toLocaleString('en-US', { minimumFractionDigits: meta.decimals, maximumFractionDigits: meta.decimals });
  }
  // The amount as the cashier actually sees it: display-currency units rounded to this
  // currency's decimals. "Is the payment enough?" MUST be judged on these, not on raw
  // KIP — a KIP total rarely divides evenly by the rate, so tendering the exact shown
  // amount lands a few KIP short and would read as an impossible "ຂາດ 0 ฿".
  function units(kip) {
    var p = Math.pow(10, meta.decimals);
    return Math.round(fromKip(kip) * p) / p;
  }
  // Full display string with symbol. A non-breaking space glues the number to the symbol so
  // a narrow receipt/print column never wraps the currency symbol onto its own line.
  function fmt(kip) { return num(kip) + ' <span class="cur-sym">' + meta.symbol + '</span>'; }
  // Format an amount that is ALREADY in display-currency units (see units()/parseUnits()).
  function fmtUnits(u) {
    u = parseFloat(u) || 0;
    return u.toLocaleString('en-US', { minimumFractionDigits: meta.decimals, maximumFractionDigits: meta.decimals })
      + ' <span class="cur-sym">' + meta.symbol + '</span>';
  }

  return {
    code: code, symbol: meta.symbol, name: meta.name, decimals: meta.decimals,
    rate: rate, isKip: isKip, rates: rates,
    fromKip: fromKip, toKip: toKip, num: num, fmt: fmt, units: units,
    parseUnits: parseUnits, fmtUnits: fmtUnits, setCurrency: setCurrency
  };
})();

// Bill totals on screen. When the shop charges VAT (ອມພ) the KIP amounts carry satang
// (13,454.55), so the VAT and the net total show 2 decimals, matching the receipt; without VAT
// they stay whole numbers (POSCUR.fmt).
function posVatActive() {
  return window.STORE_TAX_TYPE && window.STORE_TAX_TYPE !== 'none' && parseFloat(window.STORE_VAT_PERCENT) > 0;
}
function posFmtTotal(kip) {
  if (!posVatActive() || !POSCUR.isKip) return POSCUR.fmt(kip);
  kip = Math.round((parseFloat(kip) || 0) * 100) / 100;
  return kip.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' <span class="cur-sym">' + POSCUR.symbol + '</span>';
}

// Live formatter for money <input>s (discount, cash received...). KIP => digits with
// thousands grouping (old behaviour). Foreign => digits + a single decimal point.
function formatMoneyInput(input) {
  if (!window.POSCUR || POSCUR.isKip) {
    var v = input.value.replace(/\D/g, '');
    input.value = v === '' ? '0' : Number(v).toLocaleString('en-US');
    return;
  }
  var s = input.value.replace(/[^0-9.]/g, '');
  var i = s.indexOf('.');
  if (i !== -1) s = s.slice(0, i + 1) + s.slice(i + 1).replace(/\./g, '');
  input.value = s;
}

// ສ່ວນຫຼຸດທ້າຍບິນ: ເລືອກໄດ້ 2 ແບບ — 'amount' (ຈຳນວນເງິນ ຕາມສະກຸນເງິນທີ່ສະແດງ) ຫຼື 'percent' (ເປີເຊັນຂອງຍອດລວມ).
// ໂໝດເກັບໄວ້ໃນ data-mode ຂອງ #cartDiscount; ເຊີບເວີຮັບເປັນຈຳນວນເງິນກີບສະເໝີ (CART_DISCOUNT_KIP).
function getCartDiscountMode() {
  return $('#cartDiscount').attr('data-mode') === 'percent' ? 'percent' : 'amount';
}

function setCartDiscountMode(mode) {
  mode = (mode === 'percent') ? 'percent' : 'amount';
  $('#cartDiscount').attr('data-mode', mode);
  // ປຸ່ມທີ່ເລືອກຢູ່ = ສີຟ້າ, ອີກປຸ່ມ = ຂອບເທົາ
  $('.cart-disc-mode-btn').each(function() {
    var on = $(this).attr('data-mode') === mode;
    $(this).toggleClass('btn-primary', on).toggleClass('btn-outline-secondary', !on);
  });
}

// ກົດປຸ່ມ ₭ ຫຼື % → ປ່ຽນໂໝດ: ຖ້າມີຄ່າຢູ່ແລ້ວ ໃຫ້ຄິດໄລ່ແປງຄ່າສະຫຼັບລະຫວ່າງ % ແລະ ຈຳນວນເງິນ (ກີບ) ອັດຕະໂນມັດ ພ້ອມໃສ່ເຄື່ອງໝາຍຈຸດ ,
function setCartDiscountModeAndReset(mode) {
  if (getCartDiscountMode() === mode) return;
  var oldMode = getCartDiscountMode();
  var oldVal = $('#cartDiscount').val() || '0';
  var subtotal = window.CART_SUBTOTAL_KIP || 0;

  setCartDiscountMode(mode);

  if (mode === 'amount') {
    // ສະຫຼັບຈາກ % ໄປເປັນ ຈຳນວນເງິນ (ກີບ): ຄິດໄລ່ຈຳນວນເງິນຈາກເປີເຊັນ ແລະ ໃສ່ເຄື່ອງໝາຍຈຸດ ,
    var pct = parseFloat(String(oldVal).replace(/[^0-9.]/g, '')) || 0;
    var amt = (pct > 0 && subtotal > 0) ? Math.round(subtotal * (pct / 100)) : 0;
    $('#cartDiscount').val(amt > 0 ? (Number(amt).toLocaleString('en-US')) : '0');
  } else {
    // ສະຫຼັບຈາກ ຈຳນວນເງິນ ໄປເປັນ %: ຄິດໄລ່ເປີເຊັນ
    var amt = window.POSCUR ? POSCUR.toKip(oldVal) : (parseFloat(String(oldVal).replace(/\D/g, '')) || 0);
    var pct = (amt > 0 && subtotal > 0) ? Math.min(100, Math.round((amt / subtotal) * 100 * 10) / 10) : 0;
    $('#cartDiscount').val((pct > 0 ? pct : 0) + '%');
  }

  updateCartUI();
  var el = document.getElementById('cartDiscount');
  if (el) { el.focus(); try { el.select(); } catch (e) {} }
}

// ໂໝດ %: ຊ່ອງສະແດງເຄື່ອງໝາຍ % ຕໍ່ທ້າຍສະເໝີ (ພິມ 1 → "1%"), ແລະ ວາງເຄີເຊີໄວ້ກ່ອນ %
// ເພື່ອໃຫ້ພິມຕໍ່ ຫຼື ກົດ Backspace ລຶບຕົວເລກໄດ້ປົກກະຕິ.
function formatCartDiscountInput(input) {
  if (getCartDiscountMode() !== 'percent') { formatMoneyInput(input); return; }
  var s = input.value.replace(/[^0-9.]/g, '');
  var i = s.indexOf('.');
  if (i !== -1) s = s.slice(0, i + 1) + s.slice(i + 1).replace(/\./g, '').slice(0, 2);
  s = s.replace(/^0+(?=\d)/, '');
  if (parseFloat(s) > 100) s = '100';
  input.value = (s === '' ? '0' : s) + '%';
  var caret = input.value.length - 1;
  try { input.setSelectionRange(caret, caret); } catch (e) {}
}

// ແປງສ່ວນຫຼຸດເປັນກີບ. ໃຊ້ໄດ້ທັງກະຕ່າປັດຈຸບັນ ແລະ ບິນທີ່ເປີດຄ້າງ/ພັກໄວ້ (ສົ່ງ valStr, mode ຂອງບິນນັ້ນ).
function cartDiscountKip(subtotalKip, valStr, mode) {
  if (valStr === undefined) valStr = $('#cartDiscount').val() || '0';
  if (mode === undefined) mode = getCartDiscountMode();
  if (mode === 'percent') {
    var pct = Math.min(100, Math.max(0, parseFloat(String(valStr).replace(/[^0-9.]/g, '')) || 0));
    return Math.round((parseFloat(subtotalKip) || 0) * pct / 100);
  }
  return window.POSCUR ? POSCUR.toKip(valStr || '0') : (parseFloat(String(valStr).replace(/\D/g, '')) || 0);
}

var selectedPayType = 'ເງິນສົດ';
var selectedCustomer = { customer_id: null, customer_name: 'ລູກຄ້າທົ່ວໄປ', phone: '', address: '', notes: '' };
var heldOrders = [];
var activeBills = [];
var currentBillId = '';
var isBillOpened = false;

function checkBillOpenedOrAlert() {
  if (!isBillOpened || !activeBills || activeBills.length === 0) {
    if (typeof createNewBillModal === 'function') {
      createNewBillModal();
    } else {
      isBillOpened = true;
    }
  }
  return true;
}

try {
  heldOrders = JSON.parse(localStorage.getItem('StoreMini.held_orders') || '[]');
} catch(e) { heldOrders = []; }
</script>

<?php
// POS Modular JS Includes
require_once __DIR__ . '/js/pos_bills_js.php';
require_once __DIR__ . '/js/pos_customer_js.php';
require_once __DIR__ . '/js/pos_loyalty_js.php';
require_once __DIR__ . '/js/pos_cart_js.php';
require_once __DIR__ . '/js/pos_barcode_js.php';
require_once __DIR__ . '/js/pos_checkout_js.php';
require_once __DIR__ . '/js/pos_shift_js.php';
?>

<script>
$(document).ready(function() {
  if (typeof loadHeldOrdersFromServer === 'function') {
    loadHeldOrdersFromServer();
  } else {
    updateHeldOrdersBadge();
  }
  initActiveBills();

  // ຕອນໂຫຼດໜ້າໃນ Mobile (≤ 991px): ສະແດງໜ້າ "ເລືອກສິນຄ້າ" ເປັນ Default ເລີຍ
  if (window.innerWidth < 992) {
    if (typeof switchMobilePosTab === 'function') {
      switchMobilePosTab('products');
    }
  }

  function syncProductsNavbarEdge() {
    if (window.innerWidth >= 992) {
      var prod = document.querySelector('.pos-products');
      if (prod) {
        var dist = window.innerWidth - prod.getBoundingClientRect().right;
        if (dist > 0) {
          document.documentElement.style.setProperty('--pos-products-right-dist', Math.round(dist) + 'px');
        }
      }
    }
  }
  syncProductsNavbarEdge();
  setTimeout(syncProductsNavbarEdge, 100);
  setTimeout(syncProductsNavbarEdge, 400);

  $(window).on('resize', function() {
    syncProductsNavbarEdge();
    if (window.innerWidth >= 992) {
      $('.pos-products').removeClass('mobile-hidden').show();
      $('.pos-cart').removeClass('mobile-active').show();
      $('#mobileFloatingCartBar').hide();
    } else {
      if (!$('#mobileTabCartBtn').hasClass('active')) {
        switchMobilePosTab('products');
      }
    }
  });
});
</script>
