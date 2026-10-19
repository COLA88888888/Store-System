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
 */
?>

<script src="<?php echo $base_path; ?>assets/js/lan-receipt.js?v=<?php echo @filemtime(__DIR__ . '/../../../../assets/js/lan-receipt.js'); ?>"></script>
<script>
<?php require __DIR__ . '/checkout/currency_helpers.php'; /* ຂໍ້ຄວາມແປ, ສະກຸນເງິນ ແລະ ອັບເດດສະຕັອກ */ ?>
<?php require __DIR__ . '/checkout/payment_modes.php'; /* ວິທີຊຳລະ: ດຽວ / ຫຼາຍຊ່ອງ / ຕິດໜີ້ */ ?>
<?php require __DIR__ . '/checkout/open_and_submit.php'; /* ເປີດຟອມຊຳລະ ແລະ ສົ່ງການຂາຍ */ ?>
<?php require __DIR__ . '/checkout/receipt_ui.php'; /* ສະແດງໃບບິນຫຼັງຂາຍສຳເລັດ */ ?>
<?php require __DIR__ . '/checkout/kitchen_tickets.php'; /* ບິນຍ່ອຍຄົວ / ເຄື່ອງພິມຕາມໝວດ */ ?>
<?php require __DIR__ . '/checkout/receipt_print.php'; /* ພິມໃບບິນ */ ?>
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
    updateCartUI();
    updateActiveBillsUI();
  } else {
    isBillOpened = true;
    localStorage.setItem('StoreMini.bill_opened', '1');
    currentBillId = activeBills[0].id;
    localStorage.setItem('StoreMini.active_bills', JSON.stringify(activeBills));
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
  try { localStorage.setItem('StoreMini.tender_currency', POSCUR.code); } catch (e) {}
  var netKip = window.CART_NET_KIP || 0;
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
