<?php
/*
 * ສ່ວນ: ວິທີຊຳລະ: ດຽວ / ຫຼາຍຊ່ອງ
 * ສະຫຼັບໂໝດຊຳລະ, ແປ້ນຕົວເລກ, ເລືອກທະນາຄານອັດຕະໂນມັດ, ເງິນສົດ/ໂອນ, ຄິດເງິນທອນ
 * ແລະ ຊຳລະຫຼາຍຊ່ອງ (ເງິນສົດ + ໂອນ). (ໂໝດຂາຍຕິດໜີ້ ຖືກລຶບອອກແລ້ວ)
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/pos/partials/js/pos_checkout_js.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>// ============================
// PAYMENT MODE & TYPE STATE
// ============================
var currentPayMode = 'single'; // 'single' | 'split'

// Highlight the active pay-mode button.
function paintPayModeButtons(mode) {
  var off = { 'border-color': '#cbd5e1', 'background': '#ffffff', 'color': '#64748b' };
  $('#payModeSingleBtn').css(mode === 'single' ? { 'border-color': '#244886', 'background': '#eef2fb', 'color': '#1a3666' } : off);
  $('#payModeSplitBtn').css(mode === 'split' ? { 'border-color': '#7c3aed', 'background': '#faf5ff', 'color': '#7c3aed' } : off);
}

function recalcPayment() {
  if (currentPayMode === 'split') calcSplitRemaining(); else calculateChange();
}

// ============================
// NUMPAD
// ============================
window.lastFocusedSplitInput = 'splitCashAmount';
function setSplitFocus(target) {
  window.lastFocusedSplitInput = (target === 'qr') ? 'splitQrAmount' : 'splitCashAmount';
  if (window.lastFocusedSplitInput === 'splitQrAmount') {
    $('#splitQrAmount').css('box-shadow', '0 0 0 3px rgba(124,58,237,0.40)');
    $('#splitCashAmount').css('box-shadow', 'none');
  } else {
    $('#splitCashAmount').css('box-shadow', '0 0 0 3px rgba(34,197,94,0.40)');
    $('#splitQrAmount').css('box-shadow', 'none');
  }
}

function getActiveAmountInput() {
  if (currentPayMode === 'split') {
    if (window.lastFocusedSplitInput === 'splitQrAmount') return $('#splitQrAmount');
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
  if (mode !== 'split') mode = 'single';
  currentPayMode = mode;
  paintPayModeButtons(mode);
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
    setSplitFocus('cash');
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
    $('#splitStatusLabel').text('<?php echo tlj('ຍັງຄ້າງ:'); ?>').css('color', '#ef4444');
    $('#splitRemainingDisplay').html(POSCUR.fmtUnits(remainUnits)).css('color', '#ef4444');
    $('#btnSubmitPayment').prop('disabled', true);
  } else if (remainUnits < 0) {
    // over-paid → show change
    $('#splitStatusLabel').text('<?php echo tlj('ເງິນທອນ:'); ?>').css('color', '#16a34a');
    $('#splitRemainingDisplay').html(POSCUR.fmtUnits(Math.abs(remainUnits))).css('color', '#16a34a');
    $('#btnSubmitPayment').prop('disabled', false);
  } else {
    $('#splitStatusLabel').text('<?php echo tlj('ພໍດີ'); ?> ✓').css('color', '#16a34a');
    $('#splitRemainingDisplay').html(POSCUR.fmtUnits(0)).css('color', '#16a34a');
    $('#btnSubmitPayment').prop('disabled', false);
  }
}

function setSplitCashExact() {
  $('#splitCashAmount').val(POSCUR.num(window.CART_NET_KIP || 0));
  $('#splitQrAmount').val('0');
  setSplitFocus('cash');
  calcSplitRemaining();
}

function addSplitCash(amountKip) {
  var curKip = POSCUR.toKip($('#splitCashAmount').val());
  $('#splitCashAmount').val(POSCUR.num(curKip + amountKip));
  setSplitFocus('cash');
  calcSplitRemaining();
}

function fillSplitCashRemaining() {
  var totalKip = window.CART_NET_KIP || 0;
  var qrKip    = POSCUR.toKip($('#splitQrAmount').val());
  var remKip   = Math.max(0, totalKip - qrKip);
  $('#splitCashAmount').val(POSCUR.num(remKip));
  setSplitFocus('cash');
  calcSplitRemaining();
}

function setSplitQrExact() {
  $('#splitQrAmount').val(POSCUR.num(window.CART_NET_KIP || 0));
  $('#splitCashAmount').val('0');
  setSplitFocus('qr');
  calcSplitRemaining();
}

function addSplitQr(amountKip) {
  var curKip = POSCUR.toKip($('#splitQrAmount').val());
  $('#splitQrAmount').val(POSCUR.num(curKip + amountKip));
  setSplitFocus('qr');
  calcSplitRemaining();
}

function fillSplitQrRemaining() {
  var totalKip = window.CART_NET_KIP || 0;
  var cashKip  = POSCUR.toKip($('#splitCashAmount').val());
  var remKip   = Math.max(0, totalKip - cashKip);
  $('#splitQrAmount').val(POSCUR.num(remKip));
  setSplitFocus('qr');
  calcSplitRemaining();
}

