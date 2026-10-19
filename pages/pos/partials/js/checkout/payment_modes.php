<?php
/*
 * ສ່ວນ: ວິທີຊຳລະ: ດຽວ / ຫຼາຍຊ່ອງ / ຕິດໜີ້
 * ສະຫຼັບໂໝດຊຳລະ, ກຳນົດວັນຊຳລະໜີ້, ແປ້ນຕົວເລກ, ເລືອກທະນາຄານອັດຕະໂນມັດ, ເງິນສົດ/ໂອນ, ຄິດເງິນທອນ,
 * ຍອດຕິດໜີ້ ແລະ ຊຳລະຫຼາຍຊ່ອງ (ເງິນສົດ + ໂອນ).
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/pos/partials/js/pos_checkout_js.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>// ============================
// PAYMENT MODE & TYPE STATE
// ============================
var currentPayMode = 'single'; // 'single' | 'split' | 'credit' (ຂາຍຕິດໜີ້)

// Highlight the active pay-mode button. The credit button only exists when the user
// has the customer_debt add right (see checkout_modal.php).
function paintPayModeButtons(mode) {
  var off = { 'border-color': '#cbd5e1', 'background': '#ffffff', 'color': '#64748b' };
  $('#payModeSingleBtn').css(mode === 'single' ? { 'border-color': '#244886', 'background': '#eef2fb', 'color': '#1a3666' } : off);
  $('#payModeSplitBtn').css(mode === 'split' ? { 'border-color': '#7c3aed', 'background': '#faf5ff', 'color': '#7c3aed' } : off);
  $('#payModeRow').toggle(mode !== 'credit');
  $('#singleAmountLbl').toggle(mode !== 'credit');
  $('#creditDepositLbl').toggle(mode === 'credit');
  $('#creditSummary').toggle(mode === 'credit');
  $('#creditDueSection').toggle(mode === 'credit');
  // Modal title + submit button say what will actually happen
  $('#checkoutTitlePay, #btnSubmitPayLbl').toggle(mode !== 'credit');
  $('#checkoutTitleCredit, #btnSubmitCreditLbl').toggle(mode === 'credit');
  $('#btnSubmitPayment').css('background', mode === 'credit'
    ? 'linear-gradient(135deg,#dc2626,#b91c1c)' : 'linear-gradient(135deg,#244886,#1a3666)');
}

// Local date as yyyy-mm-dd, `days` from today
function posYmdIn(days) {
  var d = new Date();
  d.setDate(d.getDate() + days);
  var pad = function (n) { return (n < 10 ? '0' : '') + n; };
  return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
}

// ກຳນົດຊຳລະ quick picks: today + N days
function setCreditDueIn(days) {
  $('#creditDueDate').val(posYmdIn(days));
  calcCredit();
}

// The server ignores a due date in the past, so the calendar doesn't offer one.
function limitCreditDueToToday() {
  var el = document.getElementById('creditDueDate');
  if (!el) return;
  el.setAttribute('min', posYmdIn(0));
  if (el._flatpickr) el._flatpickr.set('minDate', posYmdIn(0));
}

// Customer's other unpaid bills, so the cashier sees the whole debt before adding more.
// Cached per customer for the open form; null = not loaded (row stays hidden).
var creditOldDebt = null;
function loadCreditOldDebt() {
  creditOldDebt = null;
  var cid = selectedCustomer ? parseInt(selectedCustomer.customer_id, 10) : 0;
  if (!(cid > 0)) { calcCredit(); return; }
  $.ajax({
    url: (window.POS_BACKEND_URL || '../../api/pos_backend.php'),
    data: { action: 'customer_debt_info', customer_id: cid },
    dataType: 'json',
    cache: false,
    success: function (res) {
      var still = selectedCustomer && parseInt(selectedCustomer.customer_id, 10) === cid;
      if (res && res.success && still) creditOldDebt = res;
      if (currentPayMode === 'credit') calcCredit();
    }
  });
}

function recalcPayment() {
  if (currentPayMode === 'split') calcSplitRemaining(); else calculateChange();
}

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

// Auto trigger the already-selected bank, or default to the first configured bank account
// (bank_accounts.id ASC — for a shop with only one bank on file, e.g. BCEL One, that IS the
// default) so the QR/transfer amount always has a bank chosen without the cashier hunting for
// the picker first. Shared by single QR-pay and split payment — both show the same bank grid.
function autoSelectPosBank() {
  var activeBankOpt = $('.pos-bank-option.selected-bank');
  if (!activeBankOpt.length) {
    activeBankOpt = $('.pos-bank-option').first();
  }
  if (activeBankOpt.length) {
    activeBankOpt.click();
  }
}

function setPayMode(mode) {
  var prevMode = currentPayMode;
  currentPayMode = mode;
  paintPayModeButtons(mode);
  if (mode === 'credit') {
    $('#singlePaySection').show();
    $('#splitPaySection').hide();
    $('#payTypeRow').show();
    $('#singleSummary').hide();
    $('#splitSummary').hide();
    if (selectedPayType === 'ເງິນສົດ') $('#singleQrSection').hide(); else $('#singleQrSection').show();
    // Deposit starts at 0 — the whole bill goes on credit unless the cashier types an amount.
    $('#cashReceived').val('0');
    $('#creditDueDate').val(''); // no due date until the cashier picks one
    limitCreditDueToToday();
    loadCreditOldDebt();
    calcCredit();
    return;
  }
  if (prevMode === 'credit' && mode === 'single') {
    $('#cashReceived').val(POSCUR.num(window.CART_NET_KIP || 0));
  }
  if (mode === 'single') {
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
    $('#singlePaySection').hide();
    $('#splitPaySection').show();
    $('#payTypeRow').hide();
    $('#singleSummary').hide();
    $('#splitSummary').show();
    $('#singleQrSection').show();
    autoSelectPosBank();
    $('#splitTotal').html(posFmtTotal(window.CART_NET_KIP || 0));
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
    autoSelectPosBank();
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

// ຂາຍຕິດໜີ້: the typed amount is an optional deposit; whatever is left goes on credit.
// Submit needs a selected customer and something actually left owing.
function calcCredit() {
  var totalKip   = window.CART_NET_KIP || 0;
  var totalUnits = POSCUR.units(totalKip);
  var depUnits   = POSCUR.parseUnits($('#cashReceived').val());
  var owedUnits  = Math.max(0, totalUnits - depUnits);
  var hasCustomer = !!(selectedCustomer && parseInt(selectedCustomer.customer_id, 10) > 0);

  $('#creditCustomerLbl').text(hasCustomer ? (selectedCustomer.customer_name || '') : '-');
  $('#creditTotalLbl').html(posFmtTotal(totalKip));
  $('#creditDepositDisplay').html(POSCUR.fmtUnits(Math.min(depUnits, totalUnits)));
  $('#creditOwedDisplay').html(POSCUR.fmtUnits(owedUnits));

  // Old debt + total after this bill (KIP, same conversion processCheckout uses)
  var oldBal = creditOldDebt ? (parseFloat(creditOldDebt.balance) || 0) : 0;
  if (hasCustomer && creditOldDebt && oldBal > 0) {
    var depKip  = Math.min(totalKip, POSCUR.isKip ? Math.min(depUnits, totalUnits) : Math.round(Math.min(depUnits, totalUnits) * POSCUR.rate));
    var owedKip = Math.max(0, totalKip - depKip);
    $('#creditOldDebtLbl').html(posFmtTotal(oldBal));
    $('#creditOldDebtBills').text('(' + posT('pos.credit_old_bills', creditOldDebt.bills) + ')');
    $('#creditTotalAfterLbl').html(posFmtTotal(oldBal + owedKip));
    $('#creditOverdueLbl').text(creditOldDebt.overdue > 0 ? posT('pos.credit_overdue_warn', creditOldDebt.overdue) : '').toggle(creditOldDebt.overdue > 0);
    $('#creditOldDebtBox').show();
  } else {
    $('#creditOldDebtBox').hide();
  }

  var due = $('#creditDueDate').val() || '';
  $('#creditDueLbl').text(/^\d{4}-\d{2}-\d{2}$/.test(due) ? due.split('-').reverse().join('/') : posT('pos.credit_due_none_lbl'));

  var warn = !hasCustomer ? posT('pos.credit_need_customer') : (owedUnits <= 0 ? posT('pos.credit_full_deposit') : '');
  $('#creditWarning').text(warn).toggle(!!warn);
  $('#btnSubmitPayment').prop('disabled', !!warn);
}

function calculateChange() {
  if (currentPayMode === 'credit') return calcCredit();
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
  $('#singleTotalLbl').html(posFmtTotal(totalKip));
  $('#singleCashLbl').html(POSCUR.fmtUnits(cashUnits));

  if (changeUnits < 0) {
    $('#changeDisplay').html(posT('pos.change_short', POSCUR.fmtUnits(Math.abs(changeUnits)))).removeClass('text-success').addClass('text-danger').css('color','#ef4444');
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

  $('#splitTotal').html(posFmtTotal(totalKip));
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

