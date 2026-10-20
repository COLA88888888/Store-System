<?php
/*
 * ສ່ວນ: ສະແດງໃບບິນຫຼັງຂາຍສຳເລັດ
 * handleOnlineCheckoutReceiptUI: ລ້າງກະຕ່າ, ລົບບິນພັກທີ່ຈ່າຍແລ້ວ, ເຕີມຂໍ້ມູນໃບບິນ (ລາຍການ, VAT,
 * ເງິນສົດ/ໂອນ/ຕິດໜີ້, QR ທະນາຄານ) ແລ້ວສັ່ງພິມ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/pos/partials/js/pos_checkout_js.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>function handleOnlineCheckoutReceiptUI(res) {
  // Idempotency guard: if this exact invoice already ran through here once (e.g. a duplicate
  // success callback, or the ajax success firing after the checkout button's own re-entrancy
  // guard let a second request slip through some other way), never repeat the stock update,
  // cart reset or — most visibly — the physical print. One invoice number prints one receipt.
  if (res.invoice_number && window.__posLastReceiptInvoice === res.invoice_number) return;
  window.__posLastReceiptInvoice = res.invoice_number;

  // A recalled held bill leaves the held list only now that it has actually been paid.
  if (typeof removePaidHeldOrder === 'function') removePaidHeldOrder();

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
  // VAT on this bill → every amount on the receipt gets 2 decimals (see rcMoney)
  window.RC_TAXED = (res.tax_type || window.STORE_TAX_TYPE || 'none') !== 'none'
    && (parseFloat(res.vat_rate) || parseFloat(window.STORE_VAT_PERCENT) || 0) > 0;
  $('#rc_subtotal').html(rcMoney(res.subtotal));
  // ສ່ວນຫຼຸດແບບ % → ສະແດງເປີເຊັນນຳໜ້າຈຳນວນເງິນ, ເຊັ່ນ "(10%) 5,000 ₭"
  var rcDiscPct = (typeof getCartDiscountMode === 'function' && getCartDiscountMode() === 'percent')
    ? (parseFloat($('#cartDiscount').val()) || 0) : 0;
  $('#rc_discount').html((rcDiscPct > 0 ? '(' + rcDiscPct + '%) ' : '') + rcMoney(res.discount_amount));

  var rTaxType = res.tax_type || window.STORE_TAX_TYPE || 'none';
  var rTaxId   = res.tax_id || window.STORE_TAX_ID || '';
  if (rTaxType === 'none' || !rTaxId || rTaxId.trim() === '') {
    $('#rc_tax_id_row').attr('style', 'display: none !important;').hide();
  } else {
    $('#rc_tax_id').text(rTaxId);
    $('#rc_tax_id_row').attr('style', 'color:#000;display:flex !important;').show();
  }

  var rVatRate = parseFloat(res.vat_rate) || parseFloat(window.STORE_VAT_PERCENT) || 0;
  var rVatAmt  = parseFloat(res.vat_amount);

  if (isNaN(rVatAmt) || (rVatAmt === 0 && rVatRate > 0 && rTaxType !== 'none')) {
    var subAmt = parseFloat(res.subtotal) || 0;
    var discAmt = parseFloat(res.discount_amount) || 0;
    var amtAfterDisc = Math.max(0, subAmt - discAmt);
    if (rTaxType === 'exclusive' && rVatRate > 0 && rVatRate < 100) {
      rVatAmt = Math.round(amtAfterDisc * (rVatRate / (100 - rVatRate)) * 100) / 100;
    } else if (rTaxType === 'inclusive' && rVatRate > 0) {
      rVatAmt = Math.round((amtAfterDisc - (amtAfterDisc / (1 + (rVatRate / 100)))) * 100) / 100;
    } else {
      rVatAmt = 0;
    }
  }

  if (rTaxType === 'none' || rVatRate <= 0 || rVatAmt <= 0) {
    $('#rc_vat_row').attr('style', 'display: none !important;').hide();
  } else if (rTaxType === 'exclusive' && (rVatAmt > 0 || rVatRate > 0)) {
    $('#rc_vat_label').text(posT('pos.receipt_vat_exclusive', rVatRate + '%'));
    $('#rc_vat').html(rcMoneyExact(rVatAmt));
    $('#rc_vat_row').attr('style', 'color:#000;font-weight:400;display:flex !important;').show();
  } else if (rTaxType === 'inclusive' && (rVatAmt > 0 || rVatRate > 0)) {
    $('#rc_vat_label').text(posT('pos.receipt_vat_inclusive', rVatRate + '%'));
    $('#rc_vat').html(rcMoneyExact(rVatAmt));
    $('#rc_vat_row').attr('style', 'color:#000;font-weight:400;display:flex !important;').show();
  } else if (rVatAmt > 0) {
    $('#rc_vat_label').text(posT('pos.receipt_vat_exclusive', rVatRate > 0 ? rVatRate + '%' : ''));
    $('#rc_vat').html(rcMoneyExact(rVatAmt));
    $('#rc_vat_row').attr('style', 'color:#000;font-weight:400;display:flex !important;').show();
  } else {
    $('#rc_vat_row').attr('style', 'display: none !important;').hide();
  }

  var rcVatShown = $('#rc_vat_row').attr('style').indexOf('display: none') === -1;
  $('#rc_total').html(rcVatShown ? rcMoneyExact(res.total_amount) : rcMoney(res.total_amount));
  $('#rc_change').html(rcMoney(res.change));

  // Net total in all 4 currencies (data is stored in KIP; convert with latest rates)
  renderReceiptMultiCurrency(parseFloat(res.total_amount) || 0, res.paid_currency || (window.POSCUR && POSCUR.code), res.paid_rate || (window.POSCUR && POSCUR.rate));

  // Loyalty points are never printed on the receipt (earned, redeemed or balance) — the
  // shop's choice; they're visible on the Customers page instead.
  $('#rc_points_row, #rc_points_redeem_row, #rc_points_discount_row, #rc_points_balance_row').hide();
  if (typeof posApplyCustomerPointsFromCheckout === 'function') posApplyCustomerPointsFromCheckout(res);

  var cashAmt = parseFloat(res.cash_received) || 0;
  var qrAmt   = parseFloat(res.qr_received) || 0;
  var creditAmt = parseFloat(res.credit_amount) || 0;

  // ຂາຍຕິດໜີ້: print what is still owed; cash/transfer rows show only a real deposit.
  $('#rc_credit_amt').html(rcMoney(creditAmt));
  $('#rc_credit_row').attr('style', creditAmt > 0 ? 'display:flex !important;font-weight:700;' : 'display:none !important;');
  $('#rc_credit_due').text(res.credit_due_date || '-');
  $('#rc_credit_due_row').attr('style', (creditAmt > 0 && res.credit_due_date) ? 'display:flex !important;' : 'display:none !important;');

  if (cashAmt === 0 && qrAmt === 0 && creditAmt <= 0) {
    var pType = res.payment_type || '';
    if (pType === 'ເງິນສົດ') {
      cashAmt = (parseFloat(res.total_amount) || 0) + (parseFloat(res.change) || 0);
    } else {
      qrAmt = parseFloat(res.total_amount) || 0;
    }
  }

  $('#rc_cash_amt').html(rcMoney(cashAmt));
  $('#rc_qr_amt').html(rcMoney(qrAmt));
  // Only print the row(s) actually used — a straight cash sale showed a confusing
  // "ຮັບເງິນໂອນ: 0 ₭" line too (and a transfer sale showed "ຮັບເງິນສົດ: 0 ₭"). A split
  // payment genuinely used both, so both stay visible in that case. If neither amount
  // came through, fall back to showing cash so the receipt never prints with this
  // section blank.
  $('#rc_cash_row').toggle(cashAmt > 0 || (cashAmt === 0 && qrAmt === 0 && creditAmt <= 0));
  $('#rc_qr_row').toggle(qrAmt > 0);

  var bName = res.bank_name || window.currentSelectedBankName || '';
  var bQrImg = res.bank_qr_img || window.currentSelectedBankQrPath || '';
  var pType = res.payment_type || selectedPayType || '';

  var isTransferPayment = (qrAmt > 0) || (pType && (pType.includes('ໂອນ') || pType.includes('QR')));

  if (isTransferPayment) {
    var hasQr = !!(bQrImg && bQrImg.trim() !== '');
    var labelBank = bName ? bName : '';
    if (hasQr) {
      $('#rc_bank_qr_img').attr('src', bQrImg).show();
      var scanTransferLbl = (window.POS_RCPT_I18N && POS_RCPT_I18N['pos.receipt_scan_transfer']) || 'ສະແກນ QR ໂອນຊຳລະ';
      $('#rc_bank_name_lbl').text(labelBank ? scanTransferLbl + ' (' + labelBank + ')' : scanTransferLbl);
    } else {
      // Bank has no QR: print no QR at all, just which bank the transfer went to
      $('#rc_bank_qr_img').removeAttr('src').hide();
      var bankLbl = (window.POS_RCPT_I18N && POS_RCPT_I18N['pos.receipt_transfer_bank']) || 'ໂອນເຂົ້າ: %s';
      $('#rc_bank_name_lbl').text(bankLbl.replace('%s', labelBank));
    }
    if (hasQr || labelBank) {
      $('.receipt-qr-box').attr('style', 'display:block!important; text-align:center; margin:6px 0 8px 0; padding-top:6px; border-top:1px dashed #000;').show();
    } else {
      $('.receipt-qr-box').attr('style', 'display:none!important;').hide();
    }
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

      if (d.is_reward) {
        nameHtml = `<span style="font-weight:bold; color:#b45309;">[${posT('pos.redeem_badge')}]</span> ${nameHtml}`;
        priceHtml = '<span style="font-weight:bold; color:#b45309;">' + rcMoney(0) + '</span>';
      } else if (isGift) {
        if (!nameHtml.includes('ແຖມ')) {
          nameHtml = `<span style="font-weight:bold; color:#059669;">[${posT('pos.receipt_free_gift')}]</span> ${nameHtml}`;
        }
        priceHtml = '<span style="font-weight:bold; color:#059669;">' + rcMoney(0) + ' (' + posT('pos.receipt_free_gift') + ')</span>';
      } else if (origUnitPrice > curUnitPrice && curUnitPrice >= 0) {
        var origTotal = origUnitPrice * d.qty;
        priceHtml = `<del style="color:#64748b; font-size:0.85em;">${rcMoney(origTotal)}</del><br><span style="font-weight:bold;">${rcMoney(totalAmt)}</span>`;
      } else {
        priceHtml = `${rcMoney(totalAmt)}`;
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
  // The customer's payment receipt always goes to the printer first. Kitchen / counter tickets
  // only go out once it has been sent — rendering the receipt to an image takes a moment, and
  // firing both at once let the (instant) kitchen tickets jump ahead of it on the roll.
  var pendingTickets = window.__lastKitchenTickets || [];
  var ticketMeta = { invoice_number: res.invoice_number, date: res.date, cashier: cashierName };
  window.__lastKitchenTickets = [];
  printReceiptAuto().then(function () {
    printKitchenTickets(pendingTickets, ticketMeta);
  });
  resetPOS();
}

