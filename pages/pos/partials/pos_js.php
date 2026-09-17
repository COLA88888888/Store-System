<script>
var allProducts = <?php echo json_encode($products); ?>;
var allCustomers = <?php echo json_encode($customersList); ?>;
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
window.POSCUR = (function () {
  var rates = window.POS_EX_RATES || { KIP: 1, THB: 0, USD: 0, CNY: 0 };
  var metaAll = window.POS_CURRENCY_META || {
    KIP: { name: 'ກີບ', symbol: '₭', decimals: 0 },
    THB: { name: 'ບາດ', symbol: '฿', decimals: 2 },
    USD: { name: 'ໂດລາ', symbol: '$', decimals: 2 },
    CNY: { name: 'ຢວນ', symbol: '¥', decimals: 2 }
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

  function fromKip(kip) {
    kip = parseFloat(kip) || 0;
    return isKip ? Math.round(kip) : (kip / rate);
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
  // Full display string with symbol.
  function fmt(kip) { return num(kip) + ' <span class="cur-sym">' + meta.symbol + '</span>'; }
  // Format an amount that is ALREADY in display-currency units (see units()/parseUnits()).
  function fmtUnits(u) {
    u = parseFloat(u) || 0;
    return u.toLocaleString('en-US', { minimumFractionDigits: meta.decimals, maximumFractionDigits: meta.decimals })
      + ' <span class="cur-sym">' + meta.symbol + '</span>';
  }

  return {
    code: code, symbol: meta.symbol, name: meta.name, decimals: meta.decimals,
    rate: rate, isKip: isKip, rates: rates,
    fromKip: fromKip, toKip: toKip, num: num, fmt: fmt, units: units,
    parseUnits: parseUnits, fmtUnits: fmtUnits, setCurrency: setCurrency
  };
})();

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
  heldOrders = JSON.parse(localStorage.getItem('pos_held_orders') || '[]');
} catch(e) { heldOrders = []; }
</script>

<?php
// POS Modular JS Includes
require_once __DIR__ . '/js/pos_bills_js.php';
require_once __DIR__ . '/js/pos_customer_js.php';
require_once __DIR__ . '/js/pos_cart_js.php';
require_once __DIR__ . '/js/pos_barcode_js.php';
require_once __DIR__ . '/js/pos_checkout_js.php';
?>

<script>
$(document).ready(function() {
  updateHeldOrdersBadge();
  initActiveBills();

  // ຕອນໂຫຼດໜ້າໃນ Mobile (≤ 991px): ສະແດງໜ້າ "ເລືອກສິນຄ້າ" ເປັນ Default ເລີຍ
  if (window.innerWidth < 992) {
    if (typeof switchMobilePosTab === 'function') {
      switchMobilePosTab('products');
    }
  }

  $(window).on('resize', function() {
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
