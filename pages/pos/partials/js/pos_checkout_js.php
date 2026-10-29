<?php
/*
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * JS ຂອງການຊຳລະເງິນໃນໜ້າຂາຍ (POS). ທຸກສ່ວນຢູ່ໃນ <script> ອັນດຽວກັນ — ໄຟລ໌ຍ່ອຍຖືກ require ຕໍ່ກັນເປັນລຳດັບ ເຮັດໃຫ້ browser ໄດ້ຮັບ JS ຄືເກົ່າທຸກຕົວອັກສອນ.
 *   - checkout/currency_helpers.php : ຂໍ້ຄວາມແປ, ສະກຸນເງິນ ແລະ ອັບເດດສະຕັອກ
 *   - checkout/payment_modes.php : ວິທີຊຳລະ: ດຽວ / ຫຼາຍຊ່ອງ / ຕິດໜີ້
 *   - checkout/open_and_submit.php : ເປີດຟອມຊຳລະ ແລະ ສົ່ງການຂາຍ
 *   - checkout/receipt_ui.php : ສະແດງໃບບິນຫຼັງຂາຍສຳເລັດ
 *   - checkout/kitchen_tickets.php : ບິນຍ່ອຍຄົວ / ເຄື່ອງພິມຕາມໝວດ
 *   - checkout/receipt_print.php : ພິມໃບບິນ
 *   - checkout/receipt_bluetooth.php : ພິມໃບບິນຜ່ານ Bluetooth (ໃຊ້ assets/js/bluetooth-printer.js)
 */
?>

<script src="<?php echo $base_path; ?>assets/js/lan-receipt.js?v=<?php echo @filemtime(__DIR__ . '/../../../../assets/js/lan-receipt.js'); ?>"></script>
<script src="<?php echo $base_path; ?>assets/js/bluetooth-printer-escpos.js?v=<?php echo @filemtime(__DIR__ . '/../../../../assets/js/bluetooth-printer-escpos.js'); ?>"></script>
<script src="<?php echo $base_path; ?>assets/js/bluetooth-printer.js?v=<?php echo @filemtime(__DIR__ . '/../../../../assets/js/bluetooth-printer.js'); ?>"></script>
<script>
<?php require __DIR__ . '/checkout/currency_helpers.php'; /* ຂໍ້ຄວາມແປ, ສະກຸນເງິນ ແລະ ອັບເດດສະຕັອກ */ ?>
<?php require __DIR__ . '/checkout/payment_modes.php'; /* ວິທີຊຳລະ: ດຽວ / ຫຼາຍຊ່ອງ / ຕິດໜີ້ */ ?>
<?php require __DIR__ . '/checkout/open_and_submit.php'; /* ເປີດຟອມຊຳລະ ແລະ ສົ່ງການຂາຍ */ ?>
<?php require __DIR__ . '/checkout/receipt_ui.php'; /* ສະແດງໃບບິນຫຼັງຂາຍສຳເລັດ */ ?>
<?php require __DIR__ . '/checkout/kitchen_tickets.php'; /* ບິນຍ່ອຍຄົວ / ເຄື່ອງພິມຕາມໝວດ */ ?>
<?php require __DIR__ . '/checkout/receipt_print.php'; /* ພິມໃບບິນ */ ?>
<?php require __DIR__ . '/checkout/receipt_bluetooth.php'; /* ພິມໃບບິນຜ່ານ Bluetooth (ຫໍ່ printReceiptAuto) */ ?>
// ============================
// RESET POS
// ============================
function resetPOS() {
  activeBills = activeBills.filter(function(b) { return b.id !== currentBillId; });
  if (typeof resequenceActiveBills === 'function') resequenceActiveBills();

  if (activeBills.length === 0) {
    isBillOpened = false;
    localStorage.removeItem('StoreMini.bill_opened');
    localStorage.removeItem('StoreMini.active_bills');
    cart = [];
    selectedCustomer = { customer_id: null, customer_name: 'ລູກຄ້າທົ່ວໄປ', phone: '' };
    currentBillId = null;
    setCartDiscountMode('amount');
    $('#cartDiscount').val('0');
    updateCartUI();
    updateActiveBillsUI();
  } else {
    isBillOpened = true;
    localStorage.setItem('StoreMini.bill_opened', '1');
    currentBillId = activeBills[0].id;
    localStorage.setItem('StoreMini.active_bills', JSON.stringify(activeBills));
    loadBillState(currentBillId);
  }
  if (window.POSCUR && POSCUR.setCurrency) POSCUR.setCurrency('KIP');
  $('#checkoutCurrency').val('KIP');
  try { localStorage.removeItem('StoreMini.tender_currency'); } catch (e) {}
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
// ບໍ່ໃຫ້ບັນທຶກລົງ localStorage ຂ້າມບິນ — ຖ້າເລືອກໃນລະຫວ່າງຍັງບໍ່ຊຳລະແມ່ນຄ້າງສະເພາະບິນນີ້, ເມື່ອປິດອອກ ຫຼື ຊຳລະສຳເລັດຈະກັບເປັນກີບສະເໝີ.
function onCheckoutCurrencyChange(code) {
  if (!window.POSCUR || !POSCUR.setCurrency) return;
  POSCUR.setCurrency(code || 'KIP');
  try { localStorage.removeItem('StoreMini.tender_currency'); } catch (e) {}
  var netKip = window.CART_NET_KIP || 0;
  $('#checkoutCurrency').val(POSCUR.code);
  $('#cashReceived').closest('.input-group').find('.input-group-text').text(POSCUR.symbol || '₭');
  $('#splitCashAmount').closest('.input-group').find('.input-group-text').text(POSCUR.symbol || '₭');
  $('#splitQrAmount').closest('.input-group').find('.input-group-text').text(POSCUR.symbol || '₭');
  $('#checkoutTotalDisplay').html(posFmtTotal(netKip));
  $('#cashReceived').val(POSCUR.num(netKip));
  $('#splitCashAmount').val(POSCUR.num(netKip));
  $('#splitQrAmount').val('0');
  if (currentPayMode === 'split') {
    $('#splitTotal').html(POSCUR.fmt(netKip));
    calcSplitRemaining();
  } else {
    calculateChange();
  }
  try { broadcastPaymentLive(); } catch (e) {}
}

// Closing the modal (cancel or after a sale) restores the POS-wide display currency so
// the cart behind it doesn't stay stuck on the last-picked tender currency.
if (window.jQuery) {
  jQuery(function ($) {
    $('#checkoutModal').on('hidden.bs.modal', function () {
      try {
        if (window.parent && typeof window.parent.setPosModalOpen === 'function') {
          window.parent.setPosModalOpen(false);
        } else if (window.parent && window.parent.document && window.parent.document.body) {
          window.parent.document.body.classList.remove('pos-modal-open');
        }
      } catch (e) {}
      if (window.POSCUR && POSCUR.setCurrency) POSCUR.setCurrency('KIP');
      $('#checkoutCurrency').val('KIP');
      $('#cashReceived').closest('.input-group').find('.input-group-text').text(window.POSCUR ? POSCUR.symbol : '₭');
      $('#splitCashAmount').closest('.input-group').find('.input-group-text').text(window.POSCUR ? POSCUR.symbol : '₭');
      $('#splitQrAmount').closest('.input-group').find('.input-group-text').text(window.POSCUR ? POSCUR.symbol : '₭');
      try { localStorage.removeItem('StoreMini.tender_currency'); } catch (e) {}

      // ຖ້າຫາກ modal ປິດຍ້ອນຊຳລະເງິນສຳເລັດ: ຫ້າມສົ່ງ 'shopping' ໄປທັບໜ້າຈໍລູກຄ້າ (ໃຫ້ຈໍລູກຄ້າສະແດງຍອດຊຳລະສຳເລັດຄ້າງໄວ້)
      if (window.__isCheckoutSuccessFinishing) {
        window.__isCheckoutSuccessFinishing = false;
        return;
      }

      // ຍົກເລີກການຊຳລະ (ກະຕ່າຍັງມີສິນຄ້າ) → ລ້າງຍອດເງິນສົດ/ໂອນ ແລະ QR ທະນາຄານ ອອກຈາກຈໍລູກຄ້າ.
      // ຂາຍສຳເລັດແລ້ວ ກະຕ່າວ່າງ — ຈໍລູກຄ້າສະແດງ payment_success ຢູ່, ບໍ່ແຕະ.
      if (typeof cart !== 'undefined' && cart.length > 0 && typeof broadcastCustomerDisplay === 'function') {
        broadcastCustomerDisplay('shopping');
      }
    });
  });
}

// ============================
// ຈໍລູກຄ້າ ເຣວທາມ: ເງິນສົດ / ເງິນໂອນ / ເງິນທອນ ແລະ QR ທະນາຄານທີ່ເລືອກ — ສົ່ງທຸກຄັ້ງທີ່
// ພະນັກງານປ້ອນຈຳນວນ, ປ່ຽນວິທີຊຳລະ ຫຼື ເລືອກທະນາຄານ ໃນໜ້າຕ່າງຊຳລະເງິນ.
// ຍອດເງິນສົ່ງເປັນກີບ (ຈໍລູກຄ້າແປງສະກຸນເອງ). ສະແດງຢູ່ pages/pos/partials/customer_display/.
// ============================
function broadcastPaymentLive() {
  if (typeof broadcastCustomerDisplay !== 'function' || !window.POSCUR) return;
  if (!$('#checkoutModal').hasClass('show')) return;   // ສົ່ງສະເພາະຕອນໜ້າຕ່າງຊຳລະເປີດຢູ່
  var totalKip = window.CART_NET_KIP || 0;
  var cashKip = 0, qrKip = 0;
  if (currentPayMode === 'split') {
    cashKip = POSCUR.toKip($('#splitCashAmount').val() || '0');
    qrKip   = POSCUR.toKip($('#splitQrAmount').val() || '0');
  } else {
    var amtKip = POSCUR.toKip($('#cashReceived').val() || '0');
    if (selectedPayType === 'ເງິນສົດ') cashKip = amtKip; else qrKip = amtKip;
  }
  var changeKip = (currentPayMode === 'credit') ? 0 : Math.max(0, cashKip + qrKip - totalKip);
  var usesTransfer = (currentPayMode === 'split') ? true : (selectedPayType !== 'ເງິນສົດ');
  var bank = null;
  if (usesTransfer && window.currentSelectedBankId) {
    bank = {
      id: window.currentSelectedBankId,
      name: window.currentSelectedBankName || '',
      qr: window.currentSelectedBankQrPath || ''
    };
  }
  broadcastCustomerDisplay('payment_live', {
    payMode: currentPayMode,
    paymentType: selectedPayType,
    cashReceived: cashKip,
    qrReceived: qrKip,
    changeAmount: changeKip,
    // ຈຳນວນທີ່ລູກຄ້າຕ້ອງໂອນ: ແຍກຈ່າຍ = ສ່ວນ QR, ໂອນທັງໝົດ = ຍອດທັງໝົດ
    transferDue: currentPayMode === 'split' ? qrKip : (usesTransfer ? Math.max(qrKip, totalKip) : 0),
    payBank: bank,
    tenderCurrency: {
      code: POSCUR.code,
      symbol: POSCUR.symbol,
      rate: POSCUR.rate,
      decimals: POSCUR.decimals,
      isKip: POSCUR.code === 'KIP'
    }
  });
}

if (window.jQuery) {
  jQuery(function () {
    // ຫໍ່ຟັງຊັນຄິດໄລ່ເງິນ/ເລືອກທະນາຄານ — ທຸກບ່ອນທີ່ເອີ້ນ (ແປ້ນຕົວເລກ, ປຸ່ມລັດ, ພິມເອງ) ສົ່ງໄປຈໍລູກຄ້າດ້ວຍ
    ['calculateChange', 'calcSplitRemaining', 'calcCredit', 'selectPosBank', 'selectPayTypeTab', 'setPayMode'].forEach(function (fn) {
      var orig = window[fn];
      if (typeof orig !== 'function' || orig.__cdLive) return;
      var wrapped = function () {
        var r = orig.apply(this, arguments);
        try { broadcastPaymentLive(); } catch (e) {}
        return r;
      };
      wrapped.__cdLive = true;
      window[fn] = wrapped;
    });
    $('#checkoutModal').on('shown.bs.modal', function () { try { broadcastPaymentLive(); } catch (e) {} });
  });
}

</script>
