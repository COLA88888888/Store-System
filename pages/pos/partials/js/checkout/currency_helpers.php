<?php
/*
 * ສ່ວນ: ຂໍ້ຄວາມແປ, ສະກຸນເງິນ ແລະ ອັບເດດສະຕັອກ
 * ຂໍ້ຄວາມທີ່ JS ໃສ່ໃນໃບບິນ (ຫຼາຍພາສາ), ຟັງຊັນສະແດງເງິນຫຼາຍສະກຸນ (rcMoney, posMoneyIn...),
 * ເບີໂທລູກຄ້າໃນໃບບິນ, ແລະ ກະຈາຍຈຳນວນສະຕັອກໃໝ່ໄປທຸກແທັບຫຼັງຂາຍ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/pos/partials/js/pos_checkout_js.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>// ==========================================
// MULTI-CURRENCY HELPERS  (base data is always KIP; these only convert for display)
// ==========================================
window.POS_EX_RATES = window.POS_EX_RATES || { KIP: 1, THB: 0, USD: 0, CNY: 0 };
window.POS_CURRENCY_META = window.POS_CURRENCY_META || {
  KIP: { name: 'ກີບ', symbol: '<?php echo kipSymbol(); ?>', decimals: 0 },
  THB: { name: '<?php echo tlj('ບາດ'); ?>', symbol: '฿', decimals: 0 },
  USD: { name: '<?php echo tlj('ໂດລາ'); ?>', symbol: '$', decimals: 0 },
  CNY: { name: '<?php echo tlj('ຢວນ'); ?>', symbol: '¥', decimals: 0 }
};
window.POS_DISPLAY_CURRENCY = window.POS_DISPLAY_CURRENCY || 'KIP';

// Receipt strings that get injected into the DOM by JS (not present in the server HTML the
// flag switcher walks). Keyed by "domain.key" so the dashboard's live language switcher
// re-points the values on a flag click — read from here at render time, never bake t() inline.
window.POS_RCPT_I18N = window.POS_RCPT_I18N || {};
window.POS_RCPT_I18N['pos.receipt_scan_transfer'] = <?php echo json_encode(t('pos.receipt_scan_transfer', 'ສະແກນ QR ໂອນຊຳລະ'), JSON_UNESCAPED_UNICODE); ?>;
Object.assign(window.POS_RCPT_I18N, <?php echo tjson([
  'pos.receipt_transfer_bank'        => 'ໂອນເຂົ້າ: %s',
  'pos.receipt_vat_exclusive'        => 'ອມພ (%s):',
  'pos.receipt_vat_inclusive'        => 'ລວມ ອມພ (%s):',
  'pos.receipt_free_gift'            => 'ແຖມຟຣີ',
  'pos.receipt_doc_title'            => 'ໃບບິນຮັບເງິນ',
  'pos.change_short'                 => 'ຂາດ %s',
  'pos.checkout_split_short_title'   => 'ຍອດບໍ່ຄົບ!',
  'pos.checkout_split_short_body'    => 'ຍັງຂາດ %s ກະລຸນາຕື່ມ',
  'pos.checkout_cash_short_title'    => 'ເງິນທີ່ຮັບມາບໍ່ພໍ!',
  'pos.checkout_cash_short_body'     => 'ກະລຸນາກວດສອບຈຳນວນເງິນທີ່ຮັບມາ',
  'pos.checkout_bad_cart_title'      => 'ຂໍ້ມູນສິນຄ້າຜິດພາດ!',
  'pos.checkout_bad_cart_body'       => 'ບໍ່ພົບ ID ສິນຄ້າທີ່ຖືກຕ້ອງໃນກະຕ່າ ກະລຸນາລ້າງກະຕ່າແລ້ວເລືອກສິນຄ້າໃໝ່!',
  'pos.checkout_error_title'         => 'ຜິດພາດ',
  'pos.checkout_failed'              => 'ບໍ່ສາມາດຊຳລະເງິນໄດ້!',
  'pos.checkout_offline_title'       => 'ເຊື່ອມຕໍ່ບໍ່ໄດ້!',
  'pos.checkout_offline_body'        => 'ບໍ່ສາມາດຕິດຕໍ່ເຊີເວີໄດ້ ກະລຸນາກວດສອບອິນເຕີເນັດ/ເຄືອຂ່າຍ ແລ້ວລອງໃໝ່ອີກຄັ້ງ.',
  'pos.cancel'                       => 'ຍົກເລີກ',
  'pos.kitchen_cashier_label'        => 'ຜູ້ຂາຍ:',
  'pos.kitchen_total'                => 'ລວມ %s',
]); ?>);

// Receipt number format: thousands commas + always 2 decimals ("148,000.00").
function rcNum(v) {
  v = Math.round((parseFloat(v) || 0) * 100) / 100;
  return v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Convert a KIP amount into `code` for the receipt ("12.50 $"); '-' when that currency has
// no rate. `rate` (KIP per unit) overrides the current rate — the rate the bill was paid at.
function posMoneyIn(amountKip, code, rate) {
  code = (code || 'KIP').toUpperCase();
  var meta = window.POS_CURRENCY_META[code] || window.POS_CURRENCY_META.KIP;
  if (code === 'KIP') return rcNum(amountKip) + ' <span class="cur-sym">' + meta.symbol + '</span>';
  rate = parseFloat(rate) || parseFloat(window.POS_EX_RATES[code]) || 0;
  if (rate <= 0) return '-';
  return rcNum(posRoundUnits((amountKip || 0) / rate)) + ' <span class="cur-sym">' + meta.symbol + '</span>';
}

// Receipt money in the bill's tender currency, whole numbers (prices, discount, cash,
// transfer, change).
// A bill that carries VAT (window.RC_TAXED, set per receipt) shows every amount to 2 decimals.
function rcMoney(kip) {
  if (window.RC_TAXED) return rcMoneyExact(kip);
  return window.POSCUR ? POSCUR.fmt(kip) : Math.round(parseFloat(kip) || 0).toLocaleString('en-US') + ' <?php echo kipSymbol(); ?>';
}
// VAT and the net total of a taxed bill: 2 decimals (13,454.55 ₭) so they add up exactly.
function rcMoneyExact(kip) {
  if (!window.POSCUR || POSCUR.isKip) return posMoneyIn(kip, 'KIP');
  return posMoneyIn(kip, POSCUR.code, POSCUR.rate);
}

// The receipt's net-total currency block: only the currency the customer paid in.
// ຈ່າຍເປັນກີບ (ບໍ່ໄດ້ເລືອກສະກຸນເງິນ) → ບໍ່ສະແດງເລີຍ (ບໍ່ໃຫ້ມີແຖວ "ກີບ (LAK)" ຊ້ຳກັບຍອດສຸດທິ);
// ຈ່າຍເປັນສະກຸນອື່ນທີ່ມີອັດຕາແລກປ່ຽນ → ສະແດງຊື່ ແລະ ຍອດຂອງສະກຸນນັ້ນ (ເຊັ່ນ "ບາດ (THB): 250.00 ฿").
function renderReceiptMultiCurrency(totalKip, paidCode, paidRate) {
  totalKip = parseFloat(totalKip) || 0;
  paidCode = String(paidCode || 'KIP').toUpperCase();
  var r = window.POS_EX_RATES || {};
  var show = paidCode !== 'KIP' && (parseFloat(paidRate) > 0 || parseFloat(r[paidCode]) > 0);
  $('#rc_multicur').css('display', show ? 'block' : 'none');
  if (!show) return;
  ['KIP', 'THB', 'USD', 'CNY'].forEach(function (c) {
    var el = $('#rc_total_' + c.toLowerCase());
    // ແຖວມີ class d-flex (display:flex !important) — ຕ້ອງໃສ່ !important ຈຶ່ງເຊື່ອງໄດ້ແທ້
    if (c === paidCode) {
      el.html(posMoneyIn(totalKip, c, paidRate));
      el.parent()[0].style.setProperty('display', 'flex', 'important');
    } else {
      el.parent()[0].style.setProperty('display', 'none', 'important');
    }
  });
}

// Show the customer's phone on the receipt only when there is one
function setReceiptCustomerPhone(phone) {
  phone = (phone || '').toString().trim();
  if (phone) {
    $('#rc_customer_phone').text(phone);
    $('#rc_customer_phone_row').attr('style', 'color:#000;font-weight:400;display:flex;').show();
  } else {
    $('#rc_customer_phone_row').attr('style', 'display:none !important;').hide();
  }
}

// ==========================================
// REAL-TIME CROSS-WINDOW STOCK BROADCAST SYNC
// ==========================================
var posStockChannel = (typeof BroadcastChannel !== 'undefined') ? new BroadcastChannel('StoreMini.stock_channel') : null;

function broadcastStockUpdate(updatedStocks) {
  if (!updatedStocks || !updatedStocks.length) return;
  try {
    if (posStockChannel) {
      posStockChannel.postMessage({ type: 'stock_updated', updated_stocks: updatedStocks });
    }
  } catch(e) {}
  try {
    localStorage.setItem('StoreMini.live_stock_update', JSON.stringify({ timestamp: Date.now(), updated_stocks: updatedStocks }));
  } catch(e) {}
}

// Bumped on every authoritative stock write (checkout success, cross-tab broadcast).
// Lets a slow-in-flight poll request notice it's stale and skip applying, instead of
// reverting a just-decremented stock number back up for a moment ("bounce back").
window.__posStockApplySeq = window.__posStockApplySeq || 0;
// Timestamp of the last authoritative write. The periodic live-stock poll (pos_cart_js.php)
// stays quiet for a few seconds after this, so it can never race a just-completed sale.
window.__posLastAuthoritativeWriteAt = window.__posLastAuthoritativeWriteAt || 0;

function applyStockUpdateFromBroadcast(updatedStocks) {
  if (!updatedStocks || !updatedStocks.length) return;
  window.__posStockApplySeq++;
  window.__posLastAuthoritativeWriteAt = Date.now();

  var localStocks = {};
  try {
    localStocks = JSON.parse(localStorage.getItem('StoreMini.local_stocks') || '{}');
  } catch(e) {}

  updatedStocks.forEach(function(st) {
    var pid = st.product_id;
    var newQty = parseFloat(st.new_qty);

    // Persist the new baseline only — do NOT write it straight to the visible badge here.
    // The badge must show baseline minus whatever's still in the cart, and recalculateLiveStock()
    // below computes that correctly from this baseline. Writing the raw baseline to the DOM first
    // is what caused the old "bounce": it briefly showed the un-adjusted number before being corrected.
    localStocks[pid] = newQty;

    if (typeof allProducts !== 'undefined' && allProducts && allProducts.length > 0) {
      var pItem = allProducts.find(function(item) { return String(item.product_id) === String(pid); });
      if (pItem) {
        pItem.qty = newQty;
      }
    }
  });

  try {
    localStorage.setItem('StoreMini.local_stocks', JSON.stringify(localStocks));
  } catch(e) {}

  if (typeof recalculateLiveStock === 'function') {
    recalculateLiveStock();
  }
}

if (posStockChannel) {
  posStockChannel.onmessage = function(ev) {
    if (ev && ev.data && ev.data.type === 'stock_updated') {
      applyStockUpdateFromBroadcast(ev.data.updated_stocks);
    }
  };
}

window.addEventListener('storage', function(e) {
  if (e.key === 'StoreMini.live_stock_update' && e.newValue) {
    try {
      var data = JSON.parse(e.newValue);
      if (data && data.updated_stocks) {
        applyStockUpdateFromBroadcast(data.updated_stocks);
      }
    } catch(err) {}
  }
});

