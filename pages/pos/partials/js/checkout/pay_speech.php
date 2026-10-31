<?php
/*
 * ສ່ວນ: ຂໍ້ຄວາມສຽງ "ໄດ້ຮັບເງິນ…ເງິນທອນ…" — ສ້າງບ່ອນດຽວ ໃຫ້ preload (ຕອນພິມເງິນ / ກົດຊຳລະ) ແລະ ຕອນເວົ້າແທ້
 * (handleOnlineCheckoutReceiptUI) ໄດ້ຂໍ້ຄວາມດຽວກັນທຸກຕົວອັກສອນ → ສຽງຢູ່ໃນ cache ແລ້ວ ເວົ້າອອກທັນທີ.
 * ເມື່ອກ່ອນ preload ບໍ່ໃສ່ຊື່ທະນາຄານ/ຍອດແຍກຈ່າຍ ແຕ່ຕອນເວົ້າໃສ່ → ຂໍ້ຄວາມບໍ່ກົງ → ລໍສ້າງສຽງໃໝ່ 1-5 ວິນາທີ.
 * ຍອດເງິນຄິດຄືກັບ processCheckout() (open_and_submit.php) ທຸກປະການ.
 * require ຈາກ pages/pos/partials/js/pos_checkout_js.php (ຢູ່ໃນ <script> ດຽວກັນ)
 */
?>
// a = { mode: 'split' | 'qr' | 'cash', totalKip, cashKip, qrKip, changeKip, bank }
function posPaySpeechText(a) {
  if (!window.LaoTTS || typeof LaoTTS.buildPaymentSpeechMessage !== 'function' || !a) return '';
  var code = (window.POSCUR && POSCUR.code) || 'KIP';
  var rate = (window.POSCUR && POSCUR.rate) || 1;
  var isKip = (code === 'KIP' || rate <= 1);
  var u = function (k) { k = parseFloat(k) || 0; return isKip ? k : Math.round(k / rate); };
  var spTotal = u(a.totalKip);
  var spChange = isKip ? (parseFloat(a.changeKip) || 0) : Math.max(0, Math.round((parseFloat(a.changeKip) || 0) / rate));
  var bank = a.bank || '';
  if (a.mode === 'split') {
    var c = u(a.cashKip), q = u(a.qrKip);
    return LaoTTS.buildPaymentSpeechMessage(c + q, spChange, 'split', code, { cash: c, qr: q, bank: bank }, bank);
  }
  if (a.mode === 'qr') {
    return LaoTTS.buildPaymentSpeechMessage(u(a.qrKip) || spTotal, 0, true, code, null, bank);
  }
  return LaoTTS.buildPaymentSpeechMessage(u(a.cashKip) || spTotal, spChange, false, code);
}

// ຍອດເງິນຈາກຟອມຊຳລະຕອນນີ້ (ສູດດຽວກັບ processCheckout) — null ຖ້າເງິນຍັງບໍ່ພໍ
function posPaySpeechAmountsLive() {
  if (!window.POSCUR) return null;
  var total = window.CART_NET_KIP || 0;
  if (total <= 0) return null;
  var totalUnits = POSCUR.units(total);
  var bank = window.currentSelectedBankName || '';
  if (currentPayMode === 'split') {
    var sc = POSCUR.parseUnits($('#splitCashAmount').val());
    var sq = POSCUR.parseUnits($('#splitQrAmount').val());
    var paid = sc + sq;
    if (paid < totalUnits) return null;
    var qrKip = paid > 0 ? Math.round((sq / paid) * total) : 0;
    var change = POSCUR.isKip ? (paid - totalUnits) : Math.max(0, Math.round((paid - totalUnits) * POSCUR.rate));
    return { mode: 'split', totalKip: total, cashKip: total - qrKip + change, qrKip: qrKip, changeKip: change, bank: qrKip > 0 ? bank : '' };
  }
  var units = POSCUR.parseUnits($('#cashReceived').val());
  if (units < totalUnits) return null;
  var ch = POSCUR.isKip ? (units - totalUnits) : Math.max(0, Math.round((units - totalUnits) * POSCUR.rate));
  var isCash = (selectedPayType === 'ເງິນສົດ');
  return { mode: isCash ? 'cash' : 'qr', totalKip: total, cashKip: isCash ? total + ch : 0, qrKip: isCash ? 0 : total + ch, changeKip: ch, bank: isCash ? '' : bank };
}

function posPreloadPaySpeech(a) {
  var text = posPaySpeechText(a);
  if (text && window.LaoTTS && typeof LaoTTS.preload === 'function') LaoTTS.preload(text, undefined, 'pos_sale');
  return text;
}
