<script>
// ==========================================
// MULTI-CURRENCY HELPERS  (base data is always KIP; these only convert for display)
// ==========================================
window.POS_EX_RATES = window.POS_EX_RATES || { KIP: 1, THB: 0, USD: 0, CNY: 0 };
window.POS_CURRENCY_META = window.POS_CURRENCY_META || {
  KIP: { name: 'ກີບ', symbol: '₭', decimals: 0 },
  THB: { name: 'ບາດ', symbol: '฿', decimals: 2 },
  USD: { name: 'ໂດລາ', symbol: '$', decimals: 2 },
  CNY: { name: 'ຢວນ', symbol: '¥', decimals: 2 }
};
window.POS_DISPLAY_CURRENCY = window.POS_DISPLAY_CURRENCY || 'KIP';

// Receipt strings that get injected into the DOM by JS (not present in the server HTML the
// flag switcher walks). Keyed by "domain.key" so the dashboard's live language switcher
// re-points the values on a flag click — read from here at render time, never bake t() inline.
window.POS_RCPT_I18N = window.POS_RCPT_I18N || {};
window.POS_RCPT_I18N['pos.receipt_scan_transfer'] = <?php echo json_encode(t('pos.receipt_scan_transfer', 'ສະແກນ QR ໂອນຊຳລະ'), JSON_UNESCAPED_UNICODE); ?>;

// Convert a KIP amount into `code`; returns a formatted string ("12.50 $") or '-' when
// that currency has no configured rate.
function posMoneyIn(amountKip, code) {
  code = (code || 'KIP').toUpperCase();
  var meta = window.POS_CURRENCY_META[code] || window.POS_CURRENCY_META.KIP;
  if (code === 'KIP') {
    return Math.round(amountKip || 0).toLocaleString('en-US') + ' <span class="cur-sym">' + meta.symbol + '</span>';
  }
  var rate = parseFloat(window.POS_EX_RATES[code]) || 0;
  if (rate <= 0) return '-';
  var val = (amountKip || 0) / rate;
  return val.toLocaleString('en-US', { minimumFractionDigits: meta.decimals, maximumFractionDigits: meta.decimals }) + ' <span class="cur-sym">' + meta.symbol + '</span>';
}

// Fill the receipt's 4-currency net-total block from a KIP amount.
function renderReceiptMultiCurrency(totalKip) {
  totalKip = parseFloat(totalKip) || 0;
  $('#rc_total_kip').html(posMoneyIn(totalKip, 'KIP'));
  $('#rc_total_thb').html(posMoneyIn(totalKip, 'THB'));
  $('#rc_total_usd').html(posMoneyIn(totalKip, 'USD'));
  $('#rc_total_cny').html(posMoneyIn(totalKip, 'CNY'));
}

// Show the customer's phone on the receipt only when there is one
function setReceiptCustomerPhone(phone) {
  phone = (phone || '').toString().trim();
  if (phone) {
    $('#rc_customer_phone').text(phone);
    $('#rc_customer_phone_row').attr('style', 'color:#000;font-weight:600;display:flex;').show();
  } else {
    $('#rc_customer_phone_row').attr('style', 'display:none !important;').hide();
  }
}

// ==========================================
// REAL-TIME CROSS-WINDOW STOCK BROADCAST SYNC
// ==========================================
var posStockChannel = (typeof BroadcastChannel !== 'undefined') ? new BroadcastChannel('pos_stock_channel') : null;

function broadcastStockUpdate(updatedStocks) {
  if (!updatedStocks || !updatedStocks.length) return;
  try {
    if (posStockChannel) {
      posStockChannel.postMessage({ type: 'stock_updated', updated_stocks: updatedStocks });
    }
  } catch(e) {}
  try {
    localStorage.setItem('pos_live_stock_update', JSON.stringify({ timestamp: Date.now(), updated_stocks: updatedStocks }));
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
    localStocks = JSON.parse(localStorage.getItem('pos_local_stocks') || '{}');
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
    localStorage.setItem('pos_local_stocks', JSON.stringify(localStocks));
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
  if (e.key === 'pos_live_stock_update' && e.newValue) {
    try {
      var data = JSON.parse(e.newValue);
      if (data && data.updated_stocks) {
        applyStockUpdateFromBroadcast(data.updated_stocks);
      }
    } catch(err) {}
  }
});

// ============================
// PAYMENT MODE & TYPE STATE
// ============================
var currentPayMode = 'single'; // 'single' | 'split'

// ============================
// NUMPAD
// ============================
function getActiveAmountInput() {
  if (currentPayMode === 'split') {
    // Focus on whichever split input was last focused
    if (document.activeElement && document.activeElement.id === 'splitQrAmount') return $('#splitQrAmount');
    return $('#splitCashAmount');
  }
  return $('#cashReceived');
}

function numpadPress(digit) {
  var inp = getActiveAmountInput();
  var cur = inp.val().replace(/[,\s]/g, '').replace(/[^0-9]/g, '');
  if (cur === '0' || cur === '') cur = '';
  cur += digit;
  var num = parseInt(cur, 10) || 0;
  inp.val(num.toLocaleString('en-US'));
  if (currentPayMode === 'split') calcSplitRemaining();
  else calculateChange();
}

function numpadBackspace() {
  var inp = getActiveAmountInput();
  var cur = inp.val().replace(/[,\s]/g, '').replace(/[^0-9]/g, '');
  cur = cur.slice(0, -1) || '0';
  var num = parseInt(cur, 10) || 0;
  inp.val(num === 0 ? '0' : num.toLocaleString('en-US'));
  if (currentPayMode === 'split') calcSplitRemaining();
  else calculateChange();
}

function setPayMode(mode) {
  currentPayMode = mode;
  if (mode === 'single') {
    $('#payModeSingleBtn').css({ 'border-color': '#244886', 'background': '#eef2fb', 'color': '#1a3666' });
    $('#payModeSplitBtn').css({ 'border-color': '#cbd5e1', 'background': '#ffffff', 'color': '#64748b' });
    $('#singlePaySection').show();
    $('#splitPaySection').hide();
    $('#payTypeRow').show();
    $('#singleSummary').show();
    $('#splitSummary').hide();
    if (selectedPayType === 'ເງິນສົດ') {
      $('#singleQrSection').hide();
    } else {
      $('#singleQrSection').show();
    }
    calculateChange();
  } else {
    $('#payModeSplitBtn').css({ 'border-color': '#7c3aed', 'background': '#faf5ff', 'color': '#7c3aed' });
    $('#payModeSingleBtn').css({ 'border-color': '#cbd5e1', 'background': '#ffffff', 'color': '#64748b' });
    $('#singlePaySection').hide();
    $('#splitPaySection').show();
    $('#payTypeRow').hide();
    $('#singleSummary').hide();
    $('#splitSummary').show();
    $('#singleQrSection').show();
    $('#splitTotal').html(POSCUR.fmt(window.CART_NET_KIP || 0));
    calcSplitRemaining();
    $('#btnSubmitPayment').prop('disabled', true);
  }
}

// ============================
// SINGLE PAYMENT FUNCTIONS
// ============================
function selectPayTypeTab(type) {
  selectedPayType = type;
  if (type === 'ເງິນສົດ') {
    $('#payTypeCashBtn').css({ 'border-color': '#244886', 'background': '#eef2fb', 'color': '#1a3666' });
    $('#payTypeQrBtn').css({ 'border-color': '#cbd5e1', 'background': '#ffffff', 'color': '#64748b' });
    $('#singleQrSection').slideUp(150);
  } else {
    $('#payTypeQrBtn').css({ 'border-color': '#7c3aed', 'background': '#faf5ff', 'color': '#7c3aed' });
    $('#payTypeCashBtn').css({ 'border-color': '#cbd5e1', 'background': '#ffffff', 'color': '#64748b' });
    $('#singleQrSection').slideDown(150);

    // Auto trigger selected/first bank option to load bank QR
    var activeBankOpt = $('.pos-bank-option.selected-bank');
    if (!activeBankOpt.length) {
      activeBankOpt = $('.pos-bank-option').first();
    }
    if (activeBankOpt.length) {
      activeBankOpt.click();
    }
  }
}

function setPaymentType(type) { selectPayTypeTab(type); }

function setExactAmount() {
  $('#cashReceived').val(POSCUR.num(window.CART_NET_KIP || 0));
  calculateChange();
}

function addCashShortcut(amountKip) {
  var curKip = POSCUR.toKip($('#cashReceived').val());
  $('#cashReceived').val(POSCUR.num(curKip + amountKip));
  calculateChange();
}

function calculateChange() {
  if (currentPayMode !== 'single') return;
  var totalKip = window.CART_NET_KIP || 0;
  // Judge "enough / short / change" in the tender currency's own rounded units — the
  // amount actually shown to and typed by the cashier — not by converting back to KIP.
  // A KIP total rarely divides evenly by the exchange rate, so comparing raw KIP would
  // flag the exact displayed amount as short by a fraction of a unit (e.g. "ຂາດ 0 ฿").
  var totalUnits  = POSCUR.units(totalKip);
  var cashUnits   = POSCUR.parseUnits($('#cashReceived').val());
  var changeUnits = cashUnits - totalUnits;

  // update summary labels
  $('#singleTotalLbl').html(POSCUR.fmtUnits(totalUnits));
  $('#singleCashLbl').html(POSCUR.fmtUnits(cashUnits));

  if (changeUnits < 0) {
    $('#changeDisplay').html('ຂາດ ' + POSCUR.fmtUnits(Math.abs(changeUnits))).removeClass('text-success').addClass('text-danger').css('color','#ef4444');
    $('#btnSubmitPayment').prop('disabled', true);
  } else {
    $('#changeDisplay').html(POSCUR.fmtUnits(changeUnits)).removeClass('text-danger').addClass('text-success').css('color','#16a34a');
    $('#btnSubmitPayment').prop('disabled', false);
  }
}

// ============================
// SPLIT PAYMENT FUNCTIONS
// ============================
function calcSplitRemaining() {
  var totalKip = window.CART_NET_KIP || 0;
  // Compare in the tender currency's own rounded units (see calculateChange() above) so
  // paying the exact displayed total never reads as short by a rounding fraction.
  var totalUnits = POSCUR.units(totalKip);
  var cashUnits  = POSCUR.parseUnits($('#splitCashAmount').val());
  var qrUnits    = POSCUR.parseUnits($('#splitQrAmount').val());
  var remainUnits = totalUnits - (cashUnits + qrUnits);

  $('#splitTotal').html(POSCUR.fmtUnits(totalUnits));
  $('#splitCashDisplay').html(POSCUR.fmtUnits(cashUnits));
  $('#splitQrDisplay').html(POSCUR.fmtUnits(qrUnits));

  if (remainUnits > 0) {
    $('#splitStatusLabel').text('ຍັງຄ້າງ:').css('color', '#ef4444');
    $('#splitRemainingDisplay').html(POSCUR.fmtUnits(remainUnits)).css('color', '#ef4444');
    $('#btnSubmitPayment').prop('disabled', true);
  } else if (remainUnits < 0) {
    // over-paid → show change
    $('#splitStatusLabel').text('ເງິນທອນ:').css('color', '#16a34a');
    $('#splitRemainingDisplay').html(POSCUR.fmtUnits(Math.abs(remainUnits))).css('color', '#16a34a');
    $('#btnSubmitPayment').prop('disabled', false);
  } else {
    $('#splitStatusLabel').text('ພໍດີ ✓').css('color', '#16a34a');
    $('#splitRemainingDisplay').html(POSCUR.fmtUnits(0)).css('color', '#16a34a');
    $('#btnSubmitPayment').prop('disabled', false);
  }
}

function setSplitCashExact() {
  $('#splitCashAmount').val(POSCUR.num(window.CART_NET_KIP || 0));
  $('#splitQrAmount').val('0');
  calcSplitRemaining();
}

function addSplitCash(amountKip) {
  var curKip = POSCUR.toKip($('#splitCashAmount').val());
  $('#splitCashAmount').val(POSCUR.num(curKip + amountKip));
  calcSplitRemaining();
}

function fillSplitQrRemaining() {
  var totalKip = window.CART_NET_KIP || 0;
  var cashKip  = POSCUR.toKip($('#splitCashAmount').val());
  var remKip   = Math.max(0, totalKip - cashKip);
  $('#splitQrAmount').val(POSCUR.num(remKip));
  calcSplitRemaining();
}

// ============================
// OPEN CHECKOUT MODAL
// ============================
function openCheckoutModal() {
  // ກະຕ່າຫວ່າງ: ບໍ່ເປີດ modal ຊຳລະ ແລະ ບໍ່ຕ້ອງມີ popup (ປຸ່ມ ຊຳລະ ຖືກ disable ໄວ້ແລ້ວ)
  if (cart.length === 0) return;

  currentPayMode = 'single';
  $('#singlePaySection').show();
  $('#splitPaySection').hide();
  $('#payTypeRow').show();
  $('#singleSummary').show();
  $('#splitSummary').hide();
  $('#payModeSingleBtn').css({ 'border-color': '#244886', 'background': '#eef2fb', 'color': '#1a3666' });
  $('#payModeSplitBtn').css({ 'border-color': '#cbd5e1', 'background': '#ffffff', 'color': '#64748b' });
  window.currentSelectedBankId = null;
  window.currentSelectedBankName = null;
  window.currentSelectedBankQrPath = '';
  window.currentSelectedBankAccNo = '';
  window.currentSelectedBankAccName = '';
  $('input[name="pos_selected_bank_id"]').prop('checked', false);
  $('.pos-bank-option').removeClass('selected-bank').css({'background': '#ffffff', 'border-color': '#cbd5e1'});
  $('#posActiveBankQrContainer').hide();
  $('#posNoQrMessage').hide();
  $('#posActiveBankNameTitle').text('');

  // Tender currency: reuse the cashier's last pick (a shop that always takes Baht sets it
  // once), falling back to the POS display currency. Still changeable per bill.
  var savedTender = '';
  try { savedTender = localStorage.getItem('pos_tender_currency') || ''; } catch (e) {}
  var wantTender = (savedTender && $('#checkoutCurrency option[value="' + savedTender + '"]').length)
    ? savedTender : (window.POS_DISPLAY_CURRENCY || 'KIP');
  if (window.POSCUR && POSCUR.setCurrency) POSCUR.setCurrency(wantTender);
  $('#checkoutCurrency').val(POSCUR ? POSCUR.code : 'KIP');

  selectPayTypeTab('ເງິນສົດ');
  var netKip = window.CART_NET_KIP || 0;
  $('#checkoutTotalDisplay').html(POSCUR.fmt(netKip));
  $('#cashReceived').val(POSCUR.num(netKip));
  calculateChange();
  $('#checkoutModal').modal('show');
  if (typeof broadcastCustomerDisplay === 'function') {
    broadcastCustomerDisplay('checkout_modal_open', { paymentType: selectedPayType });
  }
  setTimeout(function(){ $('#cashReceived').focus(); }, 400);
}

// ============================
// PROCESS CHECKOUT
// ============================
function processCheckout() {
  // Everything below is KIP — inputs are typed in the display currency and converted here.
  var total    = window.CART_NET_KIP || 0;
  var discount = window.CART_DISCOUNT_KIP || 0;

  var cashReceived, qrReceived, paymentType, change;

  // Both branches judge "enough / short" against the rounded amount actually shown to
  // and typed by the cashier in the tender currency, not raw KIP — a KIP total rarely
  // divides evenly by the exchange rate, so comparing raw KIP round-trips would flag
  // the exact displayed amount as short by a fraction of a unit.
  var totalUnits = POSCUR.units(total);

  if (currentPayMode === 'split') {
    var splitCashUnits = POSCUR.parseUnits($('#splitCashAmount').val());
    var splitQrUnits   = POSCUR.parseUnits($('#splitQrAmount').val());
    var paidUnits      = splitCashUnits + splitQrUnits;
    if (paidUnits < totalUnits) {
      Swal.fire({ icon: 'error', title: 'ຍອດບໍ່ຄົບ!', html: 'ຍັງຂາດ ' + POSCUR.fmtUnits(totalUnits - paidUnits) + ' ກະລຸນາຕື່ມ', confirmButtonColor: '#ef4444' });
      return;
    }
    // Split the recorded KIP total in the same proportion as the units the cashier
    // entered, then land any rounding remainder on cash so cash+qr always sums to the
    // true KIP total exactly (no phantom shortfall from the currency round trip).
    qrReceived   = paidUnits > 0 ? Math.round((splitQrUnits / paidUnits) * total) : 0;
    cashReceived = total - qrReceived;
    paymentType  = 'ເງິນສົດ + ໂອນ';
    var changeUnits = paidUnits - totalUnits;
    change = POSCUR.isKip ? changeUnits : Math.round(changeUnits * POSCUR.rate);
    cashReceived += change;
  } else {
    var cashUnits = POSCUR.parseUnits($('#cashReceived').val());
    if (cashUnits < totalUnits) {
      Swal.fire({ icon: 'error', title: 'ເງິນທີ່ຮັບມາບໍ່ພໍ!', text: 'ກະລຸນາກວດສອບຈຳນວນເງິນທີ່ຮັບມາ', confirmButtonColor: '#ef4444' });
      return;
    }
    // Convert only the change (the part that matters for what's owed back) through the
    // exchange rate, then derive cash received as total + change so the stored KIP
    // figures always add up exactly.
    var changeUnitsOver = cashUnits - totalUnits;
    change = POSCUR.isKip ? changeUnitsOver : Math.round(changeUnitsOver * POSCUR.rate);
    var rawInput = total + change;
    if (selectedPayType === 'ໂອນ' || selectedPayType === 'QR') {
      cashReceived = 0;
      qrReceived   = rawInput;
    } else {
      cashReceived = rawInput;
      qrReceived   = 0;
    }
    paymentType = selectedPayType;
  }

  var isTransferTxn = (currentPayMode === 'split' && qrReceived > 0) ||
                      (currentPayMode === 'single' && selectedPayType !== 'ເງິນສົດ');

  var activeBankId = isTransferTxn ? (window.currentSelectedBankId || $('input[name="pos_selected_bank_id"]:checked').val() || null) : null;
  var activeBankName = isTransferTxn ? (window.currentSelectedBankName || null) : null;

  // Validate & clean cart items payload before sending to server
  var cleanCart = cart.filter(function(item) {
    if (!item) return false;
    var pid = parseInt(item.product_id, 10);
    return !isNaN(pid) || item.is_free_gift || (typeof item.product_id === 'string' && item.product_id.indexOf('GIFT_') === 0);
  }).map(function(item) {
    var pid = parseInt(item.product_id, 10);
    if (isNaN(pid)) {
      if (item.parent_product_id) {
        pid = parseInt(item.parent_product_id, 10);
      } else {
        pid = 999999;
      }
    }
    return {
      product_id:     pid,
      product_name:   item.product_name || '',
      unit_name:      item.unit_name || 'ອັນ',
      unit_price:     parseFloat(item.unit_price) || 0,
      original_price: parseFloat(item.original_price || item.base_price || item.unit_price) || 0,
      cost_price:     parseFloat(item.cost_price) || 0,
      multiplier:     parseInt(item.multiplier, 10) || 1,
      quantity:       parseFloat(item.quantity) || 1,
      is_free_gift:   !!item.is_free_gift,
      parent_product_id: item.parent_product_id ? parseInt(item.parent_product_id, 10) : null
    };
  });

  if (cleanCart.length === 0) {
    Swal.fire({
      icon: 'error',
      title: 'ຂໍ້ມູນສິນຄ້າຜິດພາດ!',
      text: 'ບໍ່ພົບ ID ສິນຄ້າທີ່ຖືກຕ້ອງໃນກະຕ່າ ກະລຸນາລ້າງກະຕ່າແລ້ວເລືອກສິນຄ້າໃໝ່!',
      confirmButtonColor: '#ef4444'
    });
    return;
  }

  // Snapshot the per-printer kitchen tickets now — the cart is cleared on checkout success.
  window.__lastKitchenTickets = buildKitchenTickets(cart);

  var payload = {
    cart:            cleanCart,
    cash_received:   cashReceived,
    qr_received:     qrReceived,
    payment_type:    paymentType,
    pay_mode:        currentPayMode,
    discount_amount: discount,
    bank_account_id: activeBankId,
    bank_name:       activeBankName,
    customer_id:     selectedCustomer ? selectedCustomer.customer_id : null,
    customer_name:   selectedCustomer ? selectedCustomer.customer_name : 'ລູກຄ້າທົ່ວໄປ',
    customer_phone:  selectedCustomer ? (selectedCustomer.phone || '') : '',
    // Currency the customer actually tendered on this bill (+ KIP-per-unit rate used).
    paid_currency:   (window.POSCUR && POSCUR.code) ? POSCUR.code : 'KIP',
    paid_rate:       (window.POSCUR && POSCUR.rate) ? POSCUR.rate : 1
  };

  $.ajax({
    url: (window.POS_BACKEND_URL || '../../api/pos_backend.php'),
    type: 'POST',
    // payload.cart is the raw array — the stringified cart below must come last so it always wins,
    // or Object.assign lets the raw array clobber it, jQuery then serializes it as
    // cart[0][product_id]=... instead of one JSON string, and PHP's json_decode($_POST['cart'])
    // fatal-errors on every checkout (array given, not string).
    data: Object.assign({}, payload, { action: 'checkout', cart: JSON.stringify(cleanCart) }),
    dataType: 'json',
    success: function(res) {
      if (res && res.success) {
        handleOnlineCheckoutReceiptUI(res);
      } else {
        Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: (res && res.message) ? res.message : 'ບໍ່ສາມາດຊຳລະເງິນໄດ້!' });
      }
    },
    error: function(xhr, status, err) {
      var parsedRes = null;
      try {
        if (xhr.responseJSON) {
          parsedRes = xhr.responseJSON;
        } else if (xhr.responseText) {
          var match = xhr.responseText.match(/\{[\s\S]*\}/);
          if (match) {
            parsedRes = JSON.parse(match[0]);
          }
        }
      } catch(e) {}

      if (parsedRes && parsedRes.success) {
        handleOnlineCheckoutReceiptUI(parsedRes);
        return;
      }

      if (parsedRes && !parsedRes.success && parsedRes.message) {
        Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: parsedRes.message, confirmButtonColor: '#ef4444' });
        return;
      }

      console.error('POS Checkout request failed:', status, err);
      Swal.fire({
        icon: 'error',
        title: 'ເຊື່ອມຕໍ່ບໍ່ໄດ້!',
        text: 'ບໍ່ສາມາດຕິດຕໍ່ເຊີເວີໄດ້ ກະລຸນາກວດສອບອິນເຕີເນັດ/ເຄືອຂ່າຍ ແລ້ວລອງໃໝ່ອີກຄັ້ງ.',
        confirmButtonColor: '#ef4444'
      });
    }
  });
}

function handleOnlineCheckoutReceiptUI(res) {
  $('#checkoutModal').modal('hide');
  // Receipt is always rendered in the POS display currency, not the tender currency
  // the cashier may have just picked. (hidden.bs.modal fires async — reset now.)
  if (window.POSCUR && POSCUR.setCurrency) POSCUR.setCurrency(window.POS_DISPLAY_CURRENCY || 'KIP');

  // ອັບເດດຍອດສະຕັອກຄົງເຫຼືອທີ່ຕັດແລ້ວເຂົ້າໃນ DOM ແລະ memory ທັນທີ
  if (res.updated_stocks && res.updated_stocks.length > 0) {
    applyStockUpdateFromBroadcast(res.updated_stocks);
    broadcastStockUpdate(res.updated_stocks);
  }

  // Reset cart memory and recalculate live stock
  cart = [];
  if (typeof renderCart === 'function') renderCart();
  if (typeof recalculateLiveStock === 'function') recalculateLiveStock();

  var cashierName = res.cashier || window.CURRENT_USER_NAME || 'Admin';
  if (!cashierName) cashierName = 'Admin';

  $('#rc_bill').text(res.invoice_number);
  $('#rc_date').text(res.date);
  $('#rc_cashier').text(cashierName);

  // Queue number: big block above the logo, only when the shop has it enabled.
  if (window.POS_QUEUE_ENABLED && res.queue_number) {
    $('#rc_queue_no').text(res.queue_number);
    $('#rc_queue_wrap').css('display', 'block');
  } else {
    $('#rc_queue_wrap').css('display', 'none');
  }
  $('#rc_customer').text(posCustomerDisplayName(res.customer_name));
  setReceiptCustomerPhone(res.customer_phone || (selectedCustomer && selectedCustomer.phone) || '');
  window.__deliveryNoteData = {
    bill:     res.invoice_number,
    date:     res.date,
    customer: posCustomerDisplayName(res.customer_name),
    phone:    res.customer_phone || (selectedCustomer && selectedCustomer.phone) || '',
    address:  (selectedCustomer && (selectedCustomer.address || selectedCustomer.notes)) || '',
    items:    (res.details || []).map(function(d) { return { name: d.proname || '', qty: d.qty, unit: d.unit_name || '' }; })
  };
  $('#rc_subtotal').html(POSCUR.fmt(res.subtotal));
  $('#rc_discount').html(POSCUR.fmt(res.discount_amount));

  var rTaxType = res.tax_type || window.STORE_TAX_TYPE || 'none';
  var rTaxId   = res.tax_id || window.STORE_TAX_ID || '';
  if (rTaxType === 'none' || !rTaxId || rTaxId.trim() === '') {
    $('#rc_tax_id_row').attr('style', 'display: none !important;').hide();
  } else {
    $('#rc_tax_id').text(rTaxId);
    $('#rc_tax_id_row').attr('style', 'color:#000;font-weight:600;display:flex !important;').show();
  }

  var rVatRate = parseFloat(res.vat_rate) || parseFloat(window.STORE_VAT_PERCENT) || 0;
  var rVatAmt  = parseFloat(res.vat_amount);

  if (isNaN(rVatAmt) || (rVatAmt === 0 && rVatRate > 0 && rTaxType !== 'none')) {
    var subAmt = parseFloat(res.subtotal) || 0;
    var discAmt = parseFloat(res.discount_amount) || 0;
    var amtAfterDisc = Math.max(0, subAmt - discAmt);
    if (rTaxType === 'exclusive' && rVatRate > 0) {
      rVatAmt = Math.round(amtAfterDisc * (rVatRate / 100));
    } else if (rTaxType === 'inclusive' && rVatRate > 0) {
      rVatAmt = Math.round(amtAfterDisc - (amtAfterDisc / (1 + (rVatRate / 100))));
    } else {
      rVatAmt = 0;
    }
  }

  if (rTaxType === 'none' || rVatRate <= 0 || rVatAmt <= 0) {
    $('#rc_vat_row').attr('style', 'display: none !important;').hide();
  } else if (rTaxType === 'exclusive' && (rVatAmt > 0 || rVatRate > 0)) {
    $('#rc_vat_label').text('ອມພ (' + rVatRate + '%):');
    $('#rc_vat').html(POSCUR.fmt(rVatAmt));
    $('#rc_vat_row').attr('style', 'color:#000;font-weight:600;display:flex !important;').show();
  } else if (rTaxType === 'inclusive' && (rVatAmt > 0 || rVatRate > 0)) {
    $('#rc_vat_label').text('ລວມ ອມພ (' + rVatRate + '%):');
    $('#rc_vat').html(POSCUR.fmt(rVatAmt));
    $('#rc_vat_row').attr('style', 'color:#000;font-weight:600;display:flex !important;').show();
  } else if (rVatAmt > 0) {
    $('#rc_vat_label').text('ອມພ (' + (rVatRate > 0 ? rVatRate + '%' : '') + '):');
    $('#rc_vat').html(POSCUR.fmt(rVatAmt));
    $('#rc_vat_row').attr('style', 'color:#000;font-weight:600;display:flex !important;').show();
  } else {
    $('#rc_vat_row').attr('style', 'display: none !important;').hide();
  }

  $('#rc_total').html(POSCUR.fmt(res.total_amount));
  $('#rc_change').html(POSCUR.fmt(res.change));

  // Net total in all 4 currencies (data is stored in KIP; convert with latest rates)
  renderReceiptMultiCurrency(parseFloat(res.total_amount) || 0);

  // Customer loyalty points — show only the points earned on this sale (running balance
  // is not printed on the receipt; it's visible on the Customers page instead).
  var ptsEarned = parseInt(res.points_earned) || 0;
  if (ptsEarned > 0) {
    $('#rc_points_earned').text(ptsEarned.toLocaleString());
    $('#rc_points_row').css('display', 'flex');
  } else {
    $('#rc_points_row').hide();
  }

  var cashAmt = parseFloat(res.cash_received) || 0;
  var qrAmt   = parseFloat(res.qr_received) || 0;

  if (cashAmt === 0 && qrAmt === 0) {
    var pType = res.payment_type || '';
    if (pType === 'ເງິນສົດ') {
      cashAmt = (parseFloat(res.total_amount) || 0) + (parseFloat(res.change) || 0);
    } else {
      qrAmt = parseFloat(res.total_amount) || 0;
    }
  }

  $('#rc_cash_amt').html(POSCUR.fmt(cashAmt));
  $('#rc_qr_amt').html(POSCUR.fmt(qrAmt));

  var bName = res.bank_name || window.currentSelectedBankName || '';
  var bQrImg = res.bank_qr_img || window.currentSelectedBankQrPath || '';
  var pType = res.payment_type || selectedPayType || '';

  var isTransferPayment = (qrAmt > 0) || (pType && (pType.includes('ໂອນ') || pType.includes('QR')));

  if (isTransferPayment) {
    if (bQrImg && bQrImg.trim() !== '') {
      $('#rc_bank_qr_img').attr('src', bQrImg).show();
    } else {
      $('#rc_bank_qr_img').hide();
    }
    var labelBank = bName ? bName : '';
    var scanTransferLbl = (window.POS_RCPT_I18N && POS_RCPT_I18N['pos.receipt_scan_transfer']) || 'ສະແກນ QR ໂອນຊຳລະ';
    $('#rc_bank_name_lbl').text(labelBank ? scanTransferLbl + ' (' + labelBank + ')' : scanTransferLbl);
    $('.receipt-qr-box').attr('style', 'display:block!important; text-align:center; margin:8px 0;').show();
  } else {
    $('.receipt-qr-box').attr('style', 'display:none!important;').hide();
  }

  var tbody = $('#rc_items');
  tbody.empty();
  if (res.details && res.details.length > 0) {
    res.details.forEach(function(d) {
      var uName = d.unit_name || '';
      var displayName = d.proname || '';
      if (uName && !displayName.includes('(')) {
        displayName += ' (' + uName + ')';
      }

      var isGift = !!d.is_free_gift || d.price === 0 || displayName.includes('(ແຖມ)');
      var origUnitPrice = parseFloat(d.original_price || d.price) || 0;
      var curUnitPrice = parseFloat(d.price) || 0;
      var totalAmt = parseFloat(d.total) || (curUnitPrice * d.qty);

      var nameHtml = displayName;
      var priceHtml = '';

      if (isGift) {
        if (!nameHtml.includes('ແຖມ')) {
          nameHtml = `<span style="font-weight:bold; color:#059669;">[ແຖມຟຣີ]</span> ${nameHtml}`;
        }
        priceHtml = '<span style="font-weight:bold; color:#059669;">' + POSCUR.fmt(0) + ' (ແຖມຟຣີ)</span>';
      } else if (origUnitPrice > curUnitPrice && curUnitPrice >= 0) {
        var origTotal = origUnitPrice * d.qty;
        priceHtml = `<del style="color:#64748b; font-size:0.85em;">${POSCUR.fmt(origTotal)}</del><br><span style="font-weight:bold;">${POSCUR.fmt(totalAmt)}</span>`;
      } else {
        priceHtml = `${POSCUR.fmt(totalAmt)}`;
      }

      tbody.append(`
        <tr>
          <td>${nameHtml}</td>
          <td class="text-center">x${d.qty}</td>
          <td class="text-right">${priceHtml}</td>
        </tr>
      `);
    });
  }

  if (typeof broadcastCustomerDisplay === 'function') {
    broadcastCustomerDisplay('payment_success', { cashReceived: res.cash_received, qrReceived: res.qr_received, changeAmount: res.change, queueNumber: (window.POS_QUEUE_ENABLED ? res.queue_number : null) });
  }
  printReceipt();
  printKitchenTickets(window.__lastKitchenTickets || [], { invoice_number: res.invoice_number, date: res.date });
  window.__lastKitchenTickets = [];
  resetPOS();
}

// ============================
// KITCHEN TICKETS — split the print by product category to a separate printer each
// ============================
// Group the cart by the printer assigned to each item's category (window.POS_KITCHEN_ROUTES).
function buildKitchenTickets(items) {
  var routes = window.POS_KITCHEN_ROUTES || {};
  if (!routes || !Object.keys(routes).length) return [];
  var groups = {};
  (items || []).forEach(function(it) {
    if (!it) return;
    var r = routes[String(it.category_id)];
    if (!r) return;
    var g = groups[r.printer_id] || (groups[r.printer_id] = { printer_id: r.printer_id, name: r.printer_name || ('ເຄື່ອງພິມ ' + r.printer_id), items: [] });
    g.items.push({ name: it.product_name || '', qty: parseFloat(it.quantity) || 1, unit: it.unit_name || '' });
  });
  return Object.keys(groups).map(function(k) { return groups[k]; }).filter(function(g) { return g.items.length; });
}

function kitchenTicketHtml(group, meta) {
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function(c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]; }); }
  var totalQty = group.items.reduce(function(s, i) { return s + i.qty; }, 0);
  var rows = group.items.map(function(it) {
    return '<tr><td style="padding:4px 0;font-size:15px;font-weight:700;line-height:1.25;">' + esc(it.name) +
      '</td><td style="text-align:right;padding:4px 0;font-size:17px;font-weight:800;white-space:nowrap;vertical-align:top;">x' +
      it.qty + (it.unit ? ' ' + esc(it.unit) : '') + '</td></tr>';
  }).join('');
  return '<!DOCTYPE html><html><head><meta charset="utf-8">' +
    '<link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/local-font.css">' +
    '<style>@page{size:80mm auto;margin:0}' +
    '*{box-sizing:border-box;font-family:\'Noto Sans Lao\',\'Souliyo\',\'Boon\',Arial,sans-serif !important;color:#000 !important}' +
    'html,body{width:80mm;margin:0 auto;padding:10px 8px}' +
    'h2{font-size:19px;margin:0 0 2px;text-align:center;font-weight:800}' +
    '.sub{text-align:center;font-size:12px;font-weight:600;margin-bottom:8px}' +
    'table{width:100%;border-collapse:collapse}' +
    'hr{border:none;border-top:1px dashed #000;margin:7px 0}' +
    '.foot{text-align:right;font-size:13px;font-weight:800;margin-top:4px}</style></head><body>' +
    '<h2>' + esc(group.name) + '</h2>' +
    '<div class="sub">' + (window.STORE_NAME ? esc(window.STORE_NAME) + ' — ' : '') +
      (meta && meta.invoice_number ? '#' + esc(meta.invoice_number) : '') +
      (meta && meta.date ? '  ' + esc(meta.date) : '') + '</div>' +
    '<hr><table>' + rows + '</table><hr>' +
    '<div class="foot">ລວມ ' + totalQty + '</div>' +
    '</body></html>';
}

// USB / driver printers: the browser opens one print dialog per ticket; the cashier picks
// the right printer (Chrome/Edge remember the destination per print after the first setup).
function printKitchenTickets(groups, meta) {
  if (!groups || !groups.length) return;
  var frame = document.getElementById('posKitchenIframe');
  if (!frame) {
    frame = document.createElement('iframe');
    frame.id = 'posKitchenIframe';
    frame.style.cssText = 'position:fixed;right:-9999px;bottom:-9999px;width:320px;height:320px;border:none;opacity:0;pointer-events:none;';
    document.body.appendChild(frame);
  }
  var idx = 0;
  function next() {
    if (idx >= groups.length) return;
    var g = groups[idx++];
    var doc = frame.contentWindow.document;
    doc.open(); doc.write(kitchenTicketHtml(g, meta)); doc.close();
    var win = frame.contentWindow;
    var advanced = false;
    var go = function () { if (advanced) return; advanced = true; setTimeout(next, 500); };
    win.onafterprint = go;
    setTimeout(function () {
      try { win.focus(); win.print(); } catch (e) {}
      setTimeout(go, 3000); // fallback if onafterprint never fires
    }, 350);
  }
  // Let the customer receipt fire its own dialog first.
  setTimeout(next, 1600);
}

// ============================
// PRINT RECEIPT
// ============================
function printReceipt() {
  var printContent = document.getElementById("receiptPrintArea").innerHTML;
  var iframe = document.getElementById("posPrintIframe");
  if (!iframe) {
    iframe = document.createElement('iframe');
    iframe.id = 'posPrintIframe';
    iframe.style.cssText = 'position:fixed;right:-9999px;bottom:-9999px;width:300px;height:300px;border:none;opacity:0;pointer-events:none;';
    document.body.appendChild(iframe);
  }
  var iframeDoc = iframe.contentWindow || iframe.contentDocument;
  if (iframeDoc.document) iframeDoc = iframeDoc.document;

  iframeDoc.open();
  iframeDoc.write(`<!DOCTYPE html><html><head>
    <meta charset="utf-8">
    <title>ໃບບິນຮັບເງິນ</title>
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/local-font.css">
    <style>
      @page { size: 80mm auto; margin: 0mm; }
      * { box-sizing: border-box; font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', Arial, sans-serif !important; color: #000 !important; -webkit-font-smoothing: antialiased; text-rendering: optimizeLegibility; }
      html, body { width: 80mm; margin: 0 auto; padding: 8px 6px; background: #fff; color: #000 !important; font-size: 13px; font-weight: 600; line-height: 1.5; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      .text-center { text-align: center !important; } .text-right { text-align: right !important; }
      .font-weight-bold { font-weight: 700 !important; color: #000 !important; }
      .small { font-size: 11.5px !important; color: #000 !important; font-weight: 600 !important; }
      .d-flex { display: flex; } .justify-content-between { justify-content: space-between; }
      [style*="display: none"], [style*="display:none"], .d-none { display: none !important; }
      .mb-0{margin-bottom:0!important}.mb-1{margin-bottom:4px!important}.mb-2{margin-bottom:8px!important}
      .mt-2{margin-top:8px!important}
      .text-muted { color: #000 !important; font-weight: 600 !important; }
      .receipt-header-address, .receipt-header-tel { font-size: 12px !important; font-weight: 600 !important; color: #000 !important; line-height: 1.4 !important; }
      .receipt-footer-msg { font-size: 12.5px !important; font-weight: 700 !important; color: #000 !important; border-top: 1px dashed #000 !important; margin-top: 10px !important; padding-top: 8px !important; text-align: center !important; }
      img.receipt-logo { max-width:80px!important; max-height:80px!important; height:auto!important; display:block!important; margin:10px auto 2px auto!important; object-fit:contain!important; }
      img.receipt-qr-img { max-width:130px!important; max-height:130px!important; height:auto!important; display:block!important; margin:6px auto 2px auto!important; object-fit:contain!important; image-rendering:-webkit-optimize-contrast; image-rendering:crisp-edges; image-rendering:pixelated; }
      table { width:100%; border-collapse:collapse; margin:4px 0; font-size:11.5px; color: #000 !important; }
      td,th { padding:3px 0; vertical-align:top; color: #000 !important; font-weight: 600 !important; }
      th { font-weight: 700 !important; }
      @media print {
        html,body { width:100%; margin:0; padding:2mm; color: #000 !important; }
        * { color: #000 !important; }
      }
    </style>
  </head><body>${printContent}</body></html>`);
  iframeDoc.close();

  var images = iframeDoc.getElementsByTagName('img');
  var totalImages = images.length;
  var loadedCount = 0;
  var printTriggered = false;

  function doTriggerPOSPrint() {
    if (printTriggered) return;
    printTriggered = true;
    try {
      iframe.contentWindow.focus();
      iframe.contentWindow.print();
    } catch(e) {
      console.warn('Iframe print error, falling back to window.print():', e);
      window.print();
    }
  }

  if (totalImages === 0) {
    setTimeout(doTriggerPOSPrint, 200);
  } else {
    for (var i = 0; i < totalImages; i++) {
      if (images[i].complete && images[i].naturalWidth !== 0) {
        loadedCount++;
      } else {
        images[i].onload = images[i].onerror = function() {
          loadedCount++;
          if (loadedCount >= totalImages) {
            setTimeout(doTriggerPOSPrint, 150);
          }
        };
      }
    }
    if (loadedCount >= totalImages) {
      setTimeout(doTriggerPOSPrint, 200);
    } else {
      setTimeout(doTriggerPOSPrint, 600);
    }
  }
}

// ============================
// DELIVERY NOTE (ບິນສົ່ງເຄື່ອງ) — pulls sender (store) + recipient (customer) + items from the sale
// ============================
function printDeliveryNote() {
  var d = window.__deliveryNoteData;
  if (!d) {
    Swal.fire({ icon: 'info', title: '<?php echo htmlspecialchars(t('pos.delivery_note', 'ບິນສົ່ງເຄື່ອງ'), ENT_QUOTES); ?>', text: '-', timer: 1200, showConfirmButton: false });
    return;
  }

  $('#dn_bill').text(d.bill || '-');
  $('#dn_date').text(d.date || '-');
  $('#dn_customer').text(d.customer || '-');

  if ((d.phone || '').toString().trim()) {
    $('#dn_phone').text(d.phone);
    $('#dn_phone_row').css('display', 'flex');
  } else {
    $('#dn_phone_row').css('display', 'none');
  }
  if ((d.address || '').toString().trim()) {
    $('#dn_address').text(d.address);
    $('#dn_address_row').css('display', 'block');
  } else {
    $('#dn_address_row').css('display', 'none');
  }

  var rows = '';
  (d.items || []).forEach(function(it) {
    var unit = it.unit ? (' ' + it.unit) : '';
    rows += '<tr>' +
      '<td style="padding:2px 0;vertical-align:top;">' + (it.name || '') + '</td>' +
      '<td style="text-align:right;padding:2px 0;vertical-align:top;">' + (parseFloat(it.qty) || 0) + unit + '</td>' +
    '</tr>';
  });
  $('#dn_items').html(rows || '<tr><td colspan="2" style="text-align:center;padding:6px 0;">-</td></tr>');

  var printContent = document.getElementById('deliveryNoteArea').innerHTML;
  var iframe = document.getElementById('posPrintIframe');
  if (!iframe) {
    iframe = document.createElement('iframe');
    iframe.id = 'posPrintIframe';
    iframe.style.cssText = 'position:fixed;right:-9999px;bottom:-9999px;width:300px;height:300px;border:none;opacity:0;pointer-events:none;';
    document.body.appendChild(iframe);
  }
  var doc = iframe.contentWindow || iframe.contentDocument;
  if (doc.document) doc = doc.document;

  doc.open();
  doc.write('<!DOCTYPE html><html><head><meta charset="utf-8">'
    + '<title><?php echo htmlspecialchars(t('pos.delivery_note', 'ບິນສົ່ງເຄື່ອງ'), ENT_QUOTES); ?></title>'
    + '<link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/local-font.css">'
    + '<link rel="stylesheet" href="<?php echo $base_path; ?>plugins/fontawesome-free/css/all.min.css">'
    + '<style>'
    + '@page { size: 80mm auto; margin: 0mm; }'
    + '* { box-sizing: border-box; font-family: \'Noto Sans Lao\', \'Souliyo\', \'Boon\', Arial, sans-serif !important; color: #000 !important; -webkit-font-smoothing: antialiased; }'
    + 'html, body { width: 80mm; margin: 0 auto; padding: 8px 6px; background: #fff; font-size: 13px; font-weight: 600; line-height: 1.5; -webkit-print-color-adjust: exact; print-color-adjust: exact; }'
    + 'table { width: 100%; border-collapse: collapse; }'
    + 'td, th { color: #000 !important; }'
    + 'img { max-width: 70px; max-height: 70px; object-fit: contain; display: block; margin: 6px auto 2px auto; }'
    + '.fas, .far, .fa { font-family: "Font Awesome 6 Free" !important; }'
    + '[style*="display: none"], [style*="display:none"] { display: none !important; }'
    + '@media print { html, body { width: 100%; padding: 2mm; } }'
    + '</style></head><body>' + printContent + '</body></html>');
  doc.close();

  var images = doc.getElementsByTagName('img');
  var total = images.length, loaded = 0, fired = false;
  function fire() {
    if (fired) return;
    fired = true;
    try { iframe.contentWindow.focus(); iframe.contentWindow.print(); }
    catch (e) { window.print(); }
  }
  if (total === 0) {
    setTimeout(fire, 250);
  } else {
    for (var i = 0; i < total; i++) {
      if (images[i].complete && images[i].naturalWidth !== 0) { loaded++; }
      else { images[i].onload = images[i].onerror = function() { loaded++; if (loaded >= total) setTimeout(fire, 150); }; }
    }
    setTimeout(fire, loaded >= total ? 250 : 700);
  }
}

// ============================
// RESET POS
// ============================
function resetPOS() {
  activeBills = activeBills.filter(function(b) { return b.id !== currentBillId; });
  if (typeof resequenceActiveBills === 'function') resequenceActiveBills();

  if (activeBills.length === 0) {
    isBillOpened = false;
    localStorage.removeItem('pos_bill_opened');
    localStorage.removeItem('pos_active_bills');
    cart = [];
    selectedCustomer = { customer_id: null, customer_name: 'ລູກຄ້າທົ່ວໄປ', phone: '' };
    currentBillId = null;
    updateCartUI();
    updateActiveBillsUI();
  } else {
    isBillOpened = true;
    localStorage.setItem('pos_bill_opened', '1');
    currentBillId = activeBills[0].id;
    localStorage.setItem('pos_active_bills', JSON.stringify(activeBills));
    loadBillState(currentBillId);
  }
  $('#receiptModal').modal('hide');
  $('#barcodeInput').val('').focus();
}

function selectPosBank(bankId, bankName, qrPath, accNo, accName, el) {
  $('.pos-bank-option').css({'background': '#ffffff', 'border-color': '#cbd5e1'}).removeClass('selected-bank');
  if (el) {
    $(el).addClass('selected-bank').css({'background': '#f0f9ff', 'border-color': '#0284c7'});
    $(el).find('input[type="radio"]').prop('checked', true);
  }

  if (qrPath && qrPath.trim() !== '') {
    $('#posActiveBankQrImg').attr('src', qrPath);
    $('#rc_bank_qr_img').attr('src', qrPath);
    $('#posActiveBankQrContainer').show();
    $('#posNoQrMessage').hide();
  } else {
    $('#posActiveBankQrImg').attr('src', '');
    $('#posActiveBankQrContainer').hide();
    $('#posNoQrMessage').show();
  }

  if (bankName) {
    $('#posActiveBankNameTitle').text(' - ' + bankName);
  }
  window.currentSelectedBankId = bankId;
  window.currentSelectedBankName = bankName;
  window.currentSelectedBankQrPath = qrPath || '';
  window.currentSelectedBankAccNo = accNo || '';
  window.currentSelectedBankAccName = accName || '';
}

function formatPriceInput(input) {
  var val = input.value.replace(/\D/g, '');
  input.value = val === '' ? '0' : Number(val).toLocaleString('en-US');
}

// Tender-currency selector inside the checkout modal (per-bill). Switching it re-bases
// POSCUR so the amount field, change calc and the checkout payload all use that currency.
function onCheckoutCurrencyChange(code) {
  if (!window.POSCUR || !POSCUR.setCurrency) return;
  POSCUR.setCurrency(code || 'KIP');
  try { localStorage.setItem('pos_tender_currency', POSCUR.code); } catch (e) {}
  var netKip = window.CART_NET_KIP || 0;
  $('#checkoutTotalDisplay').html(POSCUR.fmt(netKip));
  $('#cashReceived').val(POSCUR.num(netKip));
  $('#splitCashAmount').val(POSCUR.num(netKip));
  $('#splitQrAmount').val('0');
  if (currentPayMode === 'split') {
    $('#splitTotal').html(POSCUR.fmt(netKip));
    calcSplitRemaining();
  } else {
    calculateChange();
  }
}

// Closing the modal (cancel or after a sale) restores the POS-wide display currency so
// the cart behind it doesn't stay stuck on the last-picked tender currency.
if (window.jQuery) {
  jQuery(function ($) {
    $('#checkoutModal').on('hidden.bs.modal', function () {
      if (window.POSCUR && POSCUR.setCurrency) POSCUR.setCurrency(window.POS_DISPLAY_CURRENCY || 'KIP');
    });
  });
}

</script>
