<?php
/*
 * ສ່ວນ: ເປີດຟອມຊຳລະ ແລະ ສົ່ງການຂາຍ
 * openCheckoutModal (ຕັ້ງຄ່າຟອມໃໝ່ທຸກຄັ້ງ), openCreditCheckout (ປຸ່ມຕິດໜີ້), processCheckout
 * (ກວດຂໍ້ມູນ, ຢືນຢັນ ແລະ ສົ່ງບິນໄປເຊີເວີ).
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/pos/partials/js/pos_checkout_js.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>// ============================
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
  paintPayModeButtons('single');
  // Split-payment amounts belong to this one bill only — left as-is, the next sale opened
  // straight in split mode would start pre-filled with the last customer's cash/QR split.
  $('#splitCashAmount').val('0');
  $('#splitQrAmount').val('0');
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
  try { savedTender = localStorage.getItem('StoreMini.tender_currency') || ''; } catch (e) {}
  var wantTender = (savedTender && $('#checkoutCurrency option[value="' + savedTender + '"]').length)
    ? savedTender : (window.POS_DISPLAY_CURRENCY || 'KIP');
  if (window.POSCUR && POSCUR.setCurrency) POSCUR.setCurrency(wantTender);
  $('#checkoutCurrency').val(POSCUR ? POSCUR.code : 'KIP');

  selectPayTypeTab('ເງິນສົດ');
  var netKip = window.CART_NET_KIP || 0;
  $('#checkoutTotalDisplay').html(posFmtTotal(netKip));
  $('#cashReceived').val(POSCUR.num(netKip));
  calculateChange();
  $('#checkoutModal').modal('show');
  if (typeof broadcastCustomerDisplay === 'function') {
    broadcastCustomerDisplay('checkout_modal_open', { paymentType: selectedPayType });
  }
  setTimeout(function(){ $('#cashReceived').focus(); }, 400);
}

// ============================
// ຂາຍຕິດໜີ້ BUTTON (scan bar) — opens the payment form straight in credit mode.
// No customer yet → pick one first, then the form opens by itself (see selectCustomer).
// ============================
function openCreditCheckout() {
  if (!cart || cart.length === 0) {
    Swal.fire({ icon: 'warning', title: posT('pos.credit_empty_cart_title'), text: posT('pos.credit_empty_cart_body'), confirmButtonColor: '#244886' });
    return;
  }
  if (!(selectedCustomer && parseInt(selectedCustomer.customer_id, 10) > 0)) {
    window.__posCreditPending = true;
    Swal.fire({ toast: true, position: 'top-end', icon: 'info', title: posT('pos.credit_need_customer'), timer: 2200, showConfirmButton: false });
    if (typeof openCustomerSelectModal === 'function') openCustomerSelectModal();
    return;
  }
  openCheckoutModal();
  setPayMode('credit');
}

// ============================
// PROCESS CHECKOUT
// ============================
function processCheckout() {
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

  if (currentPayMode === 'credit') {
    // ຂາຍຕິດໜີ້ — the server re-checks all of this (customer, permission, amount owed).
    var hasCust = !!(selectedCustomer && parseInt(selectedCustomer.customer_id, 10) > 0);
    var depUnits = Math.min(POSCUR.parseUnits($('#cashReceived').val()), totalUnits);
    if (!hasCust || depUnits >= totalUnits) { calcCredit(); return; }
    var depositKip = Math.min(total, POSCUR.isKip ? depUnits : Math.round(depUnits * POSCUR.rate));
    if (selectedPayType === 'ເງິນສົດ') { cashReceived = depositKip; qrReceived = 0; }
    else { cashReceived = 0; qrReceived = depositKip; }
    paymentType = 'ຕິດໜີ້';
    change = 0;
    if (!window.__posCreditConfirmed) {
      var confirmHtml = posT('pos.credit_confirm_body', $('<b>').text(selectedCustomer.customer_name || '').prop('outerHTML'), '<b style="color:#dc2626">' + POSCUR.fmtUnits(totalUnits - depUnits) + '</b>')
        + '<div style="margin-top:8px;font-size:0.92rem;color:#475569;">' + $('<span>').text(posT('pos.credit_confirm_due', $('#creditDueLbl').text())).prop('outerHTML') + '</div>';
      if ($('#creditOldDebtBox').is(':visible')) {
        confirmHtml += '<div style="margin-top:4px;font-size:0.92rem;color:#7f1d1d;font-weight:700;">' + posT('pos.credit_confirm_total_after', $('#creditTotalAfterLbl').html()) + '</div>';
      }
      Swal.fire({
        icon: 'question',
        title: posT('pos.credit_confirm_title'),
        html: confirmHtml,
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: posT('pos.credit_confirm_yes'),
        cancelButtonText: posT('pos.cancel')
      }).then(function (r) {
        if (!r.isConfirmed) return;
        window.__posCreditConfirmed = true;
        try { processCheckout(); } finally { window.__posCreditConfirmed = false; }
      });
      return;
    }
  } else if (currentPayMode === 'split') {
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
    var changeUnits = paidUnits - totalUnits;
    change = POSCUR.isKip ? changeUnits : Math.round(changeUnits * POSCUR.rate);
    cashReceived += change;
  } else {
    var cashUnits = POSCUR.parseUnits($('#cashReceived').val());
    if (cashUnits < totalUnits) {
      Swal.fire({ icon: 'error', title: posT('pos.checkout_cash_short_title'), text: posT('pos.checkout_cash_short_body'), confirmButtonColor: '#ef4444' });
      return;
    }
    // Convert only the change (the part that matters for what's owed back) through the
    // exchange rate, then derive cash received as total + change so the stored KIP
    // figures always add up exactly.
    var changeUnitsOver = cashUnits - totalUnits;
    change = POSCUR.isKip ? changeUnitsOver : Math.round(changeUnitsOver * POSCUR.rate);
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
                      (currentPayMode === 'single' && selectedPayType !== 'ເງິນສົດ') ||
                      (currentPayMode === 'credit' && qrReceived > 0);

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
    paid_rate:       (window.POSCUR && POSCUR.rate) ? POSCUR.rate : 1,
    // ຂາຍຕິດໜີ້: the day the customer promised to pay (optional)
    due_date:        currentPayMode === 'credit' ? ($('#creditDueDate').val() || '') : ''
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

