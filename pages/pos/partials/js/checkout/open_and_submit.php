<?php
/*
 * ສ່ວນ: ເປີດຟອມຊຳລະ ແລະ ສົ່ງການຂາຍ
 * openCheckoutModal (ຕັ້ງຄ່າຟອມໃໝ່ທຸກຄັ້ງ), processCheckout
 * (ກວດຂໍ້ມູນ, ຢືນຢັນ ແລະ ສົ່ງບິນໄປເຊີເວີ).
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/pos/partials/js/pos_checkout_js.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>// ============================
// HELPER: ສິດການຂາຍເມື່ອມີກະ — ສາຂາທີ່ຕັ້ງກະໄວ້ (ຕັ້ງຄ່າ > ກະເຮັດວຽກ) ຜູ້ບໍລິຫານ ແລະ ຜູ້ຈັດການ
// ຂາຍບໍ່ໄດ້ (ພະນັກງານຂາຍຕາມກະ); ສາຂາທີ່ບໍ່ມີກະ ຂາຍໄດ້ປົກກະຕິ. ຜູ້ບໍລິຫານຄົນທຳອິດ (Id 1) ຂາຍໄດ້ສະເໝີ.
// ເຊີເວີກວດຊ້ຳໃນ api/pos_backend.php (action=checkout).
// ============================
function checkShiftSalePermission() {
  if (window.POS_IS_OWNER) return true;
  if (!window.STORE_HAS_SHIFTS || !window.IS_ADMIN_OR_MANAGER) return true;
  if (typeof Swal !== 'undefined' && !Swal.isVisible()) {
    Swal.fire({
      icon: 'warning',
      title: '<?php echo tlj('ບໍ່ສາມາດຂາຍໄດ້'); ?>',
      html: '<?php echo tlj('ສາຂານີ້ມີການຕັ້ງກະເຮັດວຽກແລ້ວ'); ?><br><strong><?php echo tlj('ຜູ້ບໍລິຫານ ແລະ ຜູ້ຈັດການບໍ່ສາມາດຂາຍໄດ້'); ?></strong><br><?php echo tlj('ກະລຸນາໃຫ້ພະນັກງານຂາຍເປີດກະເພື່ອຂາຍ'); ?>',
      confirmButtonText: '<?php echo tlj('ຕົກລົງ'); ?>',
      confirmButtonColor: '#244886'
    });
  }
  return false;
}

// ============================
// OPEN CHECKOUT MODAL
// ============================
function openCheckoutModal() {
  // ຖ້າມີການຕັ້ງຄ່າກະ: ຜູ້ບໍລິຫານ ແລະ ຜູ້ຈັດການບໍ່ສາມາດຂາຍໄດ້
  if (!checkShiftSalePermission()) return;

  // ກະຕ່າຫວ່າງ: ບໍ່ເປີດ modal ຊຳລະ ແລະ ບໍ່ຕ້ອງມີ popup (ປຸ່ມ ຊຳລະ ຖືກ disable ໄວ້ແລ້ວ)
  if (cart.length === 0) return;

  currentPayMode = 'single';
  $('#singlePaySection').show();
  $('#splitPaySection').hide();
  $('#payTypeRow').show();
  $('#singleSummary').show();
  $('#splitSummary').hide();
  paintPayModeButtons('single');
  // Split-payment amounts belong to this one bill only — left as-is, the next sale opened
  // straight in split mode would start pre-filled with the last customer's cash/QR split.
  $('#splitCashAmount').val('0').css('box-shadow', 'none');
  $('#splitQrAmount').val('0').css('box-shadow', 'none');
  window.lastFocusedSplitInput = 'splitCashAmount';
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

  // ເລີ່ມຕົ້ນສະກຸນເງິນເປັນ ກີບ (KIP) ສະເໝີ — ບໍ່ໃຫ້ຄ້າງຄ່າສະກຸນເງິນເກົ່າ (ປິດອອກ ຫຼື ຊຳລະເລີ່ມຕົ້ນເປັນກີບ)
  try { localStorage.removeItem('StoreMini.tender_currency'); } catch (e) {}
  if (window.POSCUR && POSCUR.setCurrency) POSCUR.setCurrency('KIP');
  $('#checkoutCurrency').val('KIP');
  $('#cashReceived').closest('.input-group').find('.input-group-text').text(window.POSCUR ? POSCUR.symbol : '₭');
  $('#splitCashAmount').closest('.input-group').find('.input-group-text').text(window.POSCUR ? POSCUR.symbol : '₭');
  $('#splitQrAmount').closest('.input-group').find('.input-group-text').text(window.POSCUR ? POSCUR.symbol : '₭');

  selectPayTypeTab('ເງິນສົດ');
  var netKip = window.CART_NET_KIP || 0;
  $('#checkoutTotalDisplay').html(posFmtTotal(netKip));
  $('#cashReceived').val(POSCUR.num(netKip));
  calculateChange();
  $('#checkoutModal').modal('show');
  try {
    if (window.parent && typeof window.parent.setPosModalOpen === 'function') {
      window.parent.setPosModalOpen(true);
    } else if (window.parent && window.parent.document && window.parent.document.body) {
      window.parent.document.body.classList.add('pos-modal-open');
    }
  } catch (e) {}
  if (typeof broadcastCustomerDisplay === 'function') {
    broadcastCustomerDisplay('checkout_modal_open', { paymentType: selectedPayType });
  }
  setTimeout(function(){ $('#cashReceived').focus(); }, 400);
}

// ============================
// PROCESS CHECKOUT
// ============================
function processCheckout() {
  // ຖ້າມີການຕັ້ງຄ່າກະ: ຜູ້ບໍລິຫານ ແລະ ຜູ້ຈັດການບໍ່ສາມາດຂາຍໄດ້
  if (!checkShiftSalePermission()) return;

  // Re-entrancy guard: a fast double-tap on the submit button (common on a touchscreen POS)
  // used to fire two checkout requests before the first response came back, creating two
  // separate bills in the DB and printing a receipt for each. Cleared once the request settles
  // (success or error) in the ajax `complete` callback below.
  if (window.__posCheckoutInFlight) return;

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
      Swal.fire({ icon: 'error', title: posT('pos.checkout_split_short_title'), html: posT('pos.checkout_split_short_body', POSCUR.fmtUnits(totalUnits - paidUnits)), confirmButtonColor: '#ef4444' });
      return;
    }
    // Split the recorded KIP total in the same proportion as the units the cashier
    // entered, then land any rounding remainder on cash so cash+qr always sums to the
    // true KIP total exactly (no phantom shortfall from the currency round trip).
    qrReceived   = paidUnits > 0 ? Math.round((splitQrUnits / paidUnits) * total) : 0;
    cashReceived = total - qrReceived;
    paymentType  = 'ເງິນສົດ + ໂອນ';
    // ເງິນທອນ = (ເງິນທີ່ຈ່າຍ − ຍອດບິນທີ່ປັດຂຶ້ນເປັນຈຳນວນເຕັມ) × ເລດ — ເທົ່າກັບເງິນທອນທີ່ສະແດງ ແລະ ທອນໃຫ້ລູກຄ້າແທ້
    // (ເຊັ່ນ ບິນ 97,700₭ ÷ 650 = 150.3 → 151฿, ຈ່າຍ 200฿ → ທອນ 49฿ = 31,850₭)
    change = POSCUR.isKip ? (paidUnits - totalUnits) : Math.max(0, Math.round((paidUnits - totalUnits) * POSCUR.rate));
    cashReceived += change;
  } else {
    var cashUnits = POSCUR.parseUnits($('#cashReceived').val());
    if (cashUnits < totalUnits) {
      Swal.fire({ icon: 'error', title: posT('pos.checkout_cash_short_title'), text: posT('pos.checkout_cash_short_body'), confirmButtonColor: '#ef4444' });
      return;
    }
    // ເງິນທອນ = (ເງິນທີ່ຮັບ − ຍອດບິນທີ່ປັດຂຶ້ນເປັນຈຳນວນເຕັມ) × ເລດ = ເງິນທອນທີ່ສະແດງໃນຈໍ ແລະ ທອນໃຫ້ລູກຄ້າແທ້,
    // ແລ້ວບັນທຶກເງິນຮັບ = ຍອດບິນ + ເງິນທອນ ເພື່ອໃຫ້ຕົວເລກກີບໃນຖານຂໍ້ມູນລວມກັນຖືກສະເໝີ.
    change = POSCUR.isKip ? (cashUnits - totalUnits) : Math.max(0, Math.round((cashUnits - totalUnits) * POSCUR.rate));
    var rawInput = total + change;
    // The QR/transfer tab's actual value is 'ໂອນເງິນ / QR' (see selectPayTypeTab's button in
    // checkout_modal.php) — comparing against 'ໂອນ' / 'QR' here never matched it, so every
    // single-payment sale got recorded and printed as cash received even when paid by
    // transfer. 'ເງິນສົດ' is the one fixed cash value; anything else is a transfer/QR method,
    // same rule selectPayTypeTab() itself already uses.
    if (selectedPayType === 'ເງິນສົດ') {
      cashReceived = rawInput;
      qrReceived   = 0;
    } else {
      cashReceived = 0;
      qrReceived   = rawInput;
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
      discount:       parseFloat(item.item_discount) || 0,
      discount_type:  item.item_discount_type || null,
      discount_val:   parseFloat(item.item_discount_val) || 0,
      is_free_gift:   !!item.is_free_gift,
      reward_id:      item.reward_id ? parseInt(item.reward_id, 10) : null,
      parent_product_id: item.parent_product_id ? parseInt(item.parent_product_id, 10) : null
    };
  });

  if (cleanCart.length === 0) {
    Swal.fire({
      icon: 'error',
      title: posT('pos.checkout_bad_cart_title'),
      text: posT('pos.checkout_bad_cart_body'),
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

  window.__posCheckoutInFlight = true;
  $('#btnSubmitPayment').prop('disabled', true);

  $.ajax({
    url: (window.POS_BACKEND_URL || '../../api/pos_backend.php'),
    type: 'POST',
    // payload.cart is the raw array — the stringified cart below must come last so it always wins,
    // or Object.assign lets the raw array clobber it, jQuery then serializes it as
    // cart[0][product_id]=... instead of one JSON string, and PHP's json_decode($_POST['cart'])
    // fatal-errors on every checkout (array given, not string).
    // Loyalty rewards being redeemed, as a JSON list of reward ids (same reason as cart).
    data: Object.assign({}, payload, {
      action: 'checkout',
      cart: JSON.stringify(cleanCart),
      held_id: (typeof getCurrentHeldId === 'function') ? (getCurrentHeldId() || '') : '',
      redemptions: JSON.stringify((typeof posRedemptions === 'function') ? posRedemptions().map(function (r) { return r.reward_id; }) : [])
    }),
    dataType: 'json',
    complete: function() {
      window.__posCheckoutInFlight = false;
      // On success the modal is already closing (handleOnlineCheckoutReceiptUI); on failure
      // re-derive the button's disabled state from the still-visible amounts instead of just
      // force-enabling it, so a genuinely short payment still blocks the retry.
      if (currentPayMode === 'split') calcSplitRemaining(); else calculateChange();
    },
    success: function(res) {
      if (res && res.success) {
        handleOnlineCheckoutReceiptUI(res);
      } else {
        Swal.fire({ icon: 'error', title: posT('pos.checkout_error_title'), text: (res && res.message) ? res.message : posT('pos.checkout_failed') });
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
        Swal.fire({ icon: 'error', title: posT('pos.checkout_error_title'), text: parsedRes.message, confirmButtonColor: '#ef4444' });
        return;
      }

      console.error('POS Checkout request failed:', status, err);
      Swal.fire({
        icon: 'error',
        title: posT('pos.checkout_offline_title'),
        text: posT('pos.checkout_offline_body'),
        confirmButtonColor: '#ef4444'
      });
    }
  });
}

