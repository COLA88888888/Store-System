<?php
/*
 * ສ່ວນ: ພິມໃບບິນຄືນ
 * fillReprintReceipt ເຕີມໃບບິນເກົ່າ (ລວມແຖວຕິດໜີ້ + ກຳນົດຊຳລະ), printBill ແລະ ພິມຜ່ານເຄື່ອງພິມ
 * LAN/USB ຫຼື iframe.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/reports/partials/js/reports_js.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>function fillReprintReceipt(b, details, billNo) {
  var P = '#rc_rep_';
  function rpT(key) {
    var s = (window.REPORT_RCPT_I18N && REPORT_RCPT_I18N[key]) || key;
    var args = Array.prototype.slice.call(arguments, 1);
    return s.replace(/%s/g, function () { return args.length ? args.shift() : ''; });
  }
  var DEFAULT_ALIASES = ['ລູກຄ້າທົ່ວໄປ', 'Walk-in Customer', 'ลูกค้าทั่วไป', '散客'];
  function customerName(name) {
    name = (name == null ? '' : String(name)).trim();
    return (!name || DEFAULT_ALIASES.indexOf(name) !== -1) ? rpT('pos.customer_default') : name;
  }
  // Checkout prints the date as d/m/Y H:i
  function saleDate(d, t) {
    var m = String(d || '').match(/^(\d{4})-(\d{2})-(\d{2})/);
    var hm = String(t || '').slice(0, 5);
    return m ? (m[3] + '/' + m[2] + '/' + m[1] + (hm ? ' ' + hm : '')) : ((d || '') + ' ' + (t || '')).trim();
  }
  var fmt = repMoneyFromKip, fmtExact = repReceiptMoneyExact, vatShown = false;

  $(P + 'bill').text(b.sale_save_bill || billNo);
  $(P + 'date').text(saleDate(b.sale_date, b.sale_time));
  $(P + 'cashier').text(b.user_receive || 'Admin');

  // Queue number: same rule as checkout — printed only while the shop has queue numbers
  // switched on (the bill always stores one), using the bill's own saved number
  if (window.REPORT_QUEUE_ENABLED && b.queue_no !== undefined && b.queue_no !== null && String(b.queue_no).trim() !== '') {
    $(P + 'queue_no').text(b.queue_no);
    $(P + 'queue_wrap').css('display', 'block');
  } else {
    $(P + 'queue_wrap').css('display', 'none');
  }
  $(P + 'customer').text(customerName(b.customer_name));
  var phone = (b.customer_phone || '').toString().trim();
  if (phone) {
    $(P + 'customer_phone').text(phone);
    $(P + 'customer_phone_row').attr('style', 'color:#000;font-weight:400;display:flex;').show();
  } else {
    $(P + 'customer_phone_row').attr('style', 'display:none !important;').hide();
  }

  var subtotal = parseFloat(b.sale_amount || 0);
  var discount = parseFloat(b.sale_discount_bill || 0);
  var net      = parseFloat(b.sale_barlance || 0);
  var change   = parseFloat(b.sale_return || 0);
  // VAT on this bill → every amount on the receipt gets 2 decimals, same as at checkout
  if ((b.tax_type || 'none') !== 'none' && (parseFloat(b.vat_rate) || 0) > 0) fmt = fmtExact;
  $(P + 'subtotal').html(fmt(subtotal));
  $(P + 'discount').html(fmt(discount));

  var rTaxType = b.tax_type || 'none';
  var rTaxId   = b.tax_id || '';
  if (rTaxType === 'none' || !rTaxId || String(rTaxId).trim() === '') {
    $(P + 'tax_id_row').attr('style', 'display: none !important;').hide();
  } else {
    $(P + 'tax_id').text(rTaxId);
    $(P + 'tax_id_row').attr('style', 'color:#000;font-weight:400;display:flex !important;').show();
  }

  var rVatRate = parseFloat(b.vat_rate) || 0;
  var rVatAmt  = parseFloat(b.vat_amount);
  if (isNaN(rVatAmt) || (rVatAmt === 0 && rVatRate > 0 && rTaxType !== 'none')) {
    var amtAfterDisc = Math.max(0, subtotal - discount);
    if (rTaxType === 'exclusive' && rVatRate > 0 && rVatRate < 100) {
      rVatAmt = Math.round(amtAfterDisc * (rVatRate / (100 - rVatRate)) * 100) / 100;
    } else if (rTaxType === 'inclusive' && rVatRate > 0) {
      rVatAmt = Math.round((amtAfterDisc - (amtAfterDisc / (1 + (rVatRate / 100)))) * 100) / 100;
    } else {
      rVatAmt = 0;
    }
  }
  var vatShow = 'color:#000;font-weight:400;display:flex !important;';
  if (rTaxType === 'none' || rVatRate <= 0 || rVatAmt <= 0) {
    $(P + 'vat_row').attr('style', 'display: none !important;').hide();
  } else if (rTaxType === 'exclusive' || rTaxType === 'inclusive') {
    $(P + 'vat_label').text(rpT(rTaxType === 'inclusive' ? 'pos.receipt_vat_inclusive' : 'pos.receipt_vat_exclusive', rVatRate + '%'));
    $(P + 'vat').html(fmtExact(rVatAmt));
    $(P + 'vat_row').attr('style', vatShow).show();
    vatShown = true;
  } else {
    $(P + 'vat_label').text(rpT('pos.receipt_vat_exclusive', rVatRate > 0 ? rVatRate + '%' : ''));
    $(P + 'vat').html(fmtExact(rVatAmt));
    $(P + 'vat_row').attr('style', vatShow).show();
    vatShown = true;
  }

  $(P + 'total').html(vatShown ? fmtExact(net) : fmt(net));
  $(P + 'change').html(fmt(change));
  fillReprintMultiCurrency(net, b.paid_currency, b.paid_rate);

  // Loyalty points are never printed on the receipt — same as at checkout
  $(P + 'points_row, ' + P + 'points_redeem_row, ' + P + 'points_discount_row, ' + P + 'points_balance_row').hide();

  var cashAmt = parseFloat(b.cash_received) || 0;
  var qrAmt   = parseFloat(b.qr_received) || 0;
  var pType   = b.type_pay || b.payment_type || '';
  // ຂາຍຕິດໜີ້: cash/qr hold only the deposit; the rest of the bill was put on credit.
  var creditAmt = (pType === 'ຕິດໜີ້') ? Math.max(0, net - cashAmt - qrAmt) : 0;
  if (cashAmt === 0 && qrAmt === 0 && pType !== 'ຕິດໜີ້') {
    if (pType === 'ເງິນສົດ') cashAmt = net + change;
    else qrAmt = net;
  }
  $(P + 'cash_amt').html(fmt(cashAmt));
  $(P + 'qr_amt').html(fmt(qrAmt));
  $(P + 'credit_amt').html(fmt(creditAmt));
  $(P + 'credit_row').attr('style', creditAmt > 0 ? 'display:flex !important;font-weight:700;' : 'display:none !important;');
  $(P + 'credit_due').text(b.credit_due_date || '-');
  $(P + 'credit_due_row').attr('style', (creditAmt > 0 && b.credit_due_date) ? 'display:flex !important;' : 'display:none !important;');
  $(P + 'cash_row').toggle(cashAmt > 0 || (cashAmt === 0 && qrAmt === 0 && creditAmt <= 0));
  $(P + 'qr_row').toggle(qrAmt > 0);

  // Bank QR — the exact bank/QR used for this sale
  var isTransferPayment = (qrAmt > 0) || (pType && (pType.indexOf('ໂອນ') !== -1 || pType.indexOf('QR') !== -1));
  var $qrBox = $('#reportReceiptPrintArea .receipt-qr-box');
  if (isTransferPayment) {
    var bQrImg = b.bank_qr_img || '';
    var hasQr = bQrImg.trim() !== '';
    var labelBank = b.bank_name || '';
    if (hasQr) {
      $(P + 'bank_qr_img').attr('src', bQrImg).show();
      var scanLbl = rpT('pos.receipt_scan_transfer');
      $(P + 'bank_name_lbl').text(labelBank ? scanLbl + ' (' + labelBank + ')' : scanLbl);
    } else {
      // Bank has no QR: print no QR at all, just which bank the transfer went to
      $(P + 'bank_qr_img').removeAttr('src').hide();
      $(P + 'bank_name_lbl').text(rpT('pos.receipt_transfer_bank', labelBank));
    }
    // ເສັ້ນເທິງກ່ອງ QR ແທນເສັ້ນທີ່ເຄີຍຢູ່ໃຕ້ເງິນທອນ (layouts/receipt_template.php) — ບໍ່ມີ QR ກໍ່ເຫຼືອເສັ້ນດຽວ
    if (hasQr || labelBank) $qrBox.attr('style', 'display:block!important; text-align:center; margin:6px 0 8px 0; padding-top:6px; border-top:1px dashed #000;').show();
    else $qrBox.attr('style', 'display:none!important;').hide();
  } else {
    $qrBox.attr('style', 'display:none!important;').hide();
  }

  // Item lines — same format as checkout: name (unit), xQty, price / gift / reward / promo
  var tbody = $(P + 'items');
  tbody.empty();
  (details || []).forEach(function (d) {
    var displayName = d.save_proname || d.product_name || '';
    var qty = parseFloat(d.save_qty) || 0;
    var curUnitPrice = parseFloat(d.save_price) || 0;
    var origUnitPrice = (d.save_orig_price !== null && d.save_orig_price !== undefined && d.save_orig_price !== '')
      ? (parseFloat(d.save_orig_price) || 0) : curUnitPrice;
    var totalAmt = parseFloat(d.save_money) || (curUnitPrice * qty);
    var isReward = parseInt(d.is_reward, 10) === 1;
    var isGift = curUnitPrice === 0 || displayName.indexOf('(ແຖມ)') !== -1;

    var nameHtml = displayName;
    var priceHtml;
    if (isReward) {
      nameHtml = '<span style="font-weight:bold; color:#b45309;">[' + rpT('pos.redeem_badge') + ']</span> ' + nameHtml;
      priceHtml = '<span style="font-weight:bold; color:#b45309;">' + fmt(0) + '</span>';
    } else if (isGift) {
      if (nameHtml.indexOf('ແຖມ') === -1) {
        nameHtml = '<span style="font-weight:bold; color:#059669;">[' + rpT('pos.receipt_free_gift') + ']</span> ' + nameHtml;
      }
      priceHtml = '<span style="font-weight:bold; color:#059669;">' + fmt(0) + ' (' + rpT('pos.receipt_free_gift') + ')</span>';
    } else if (origUnitPrice > curUnitPrice && curUnitPrice >= 0) {
      priceHtml = '<del style="color:#64748b; font-size:0.85em;">' + fmt(origUnitPrice * qty) + '</del><br><span style="font-weight:bold;">' + fmt(totalAmt) + '</span>';
    } else {
      priceHtml = fmt(totalAmt);
    }
    tbody.append(
      '<tr>' +
        '<td>' + nameHtml + '</td>' +
        '<td class="text-center">x' + qty + '</td>' +
        '<td class="text-right">' + priceHtml + '</td>' +
      '</tr>'
    );
  });
}

function printBill(billNo) {
  if (!billNo) return;

  $.ajax({
    url: 'reports.php',
    type: 'GET',
    data: { action: 'get_bill_details', bill_no: billNo },
    dataType: 'json',
    success: function(res) {
      if (res.success && res.bill) {
        fillReprintReceipt(res.bill, res.details || [], billNo);
        reprintReceiptAuto();
      } else {
        alert(repT('reports.js_load_fail'));
      }
    },
    error: function() {
      alert(repT('reports.js_connect_error'));
    }
  });
}

function doPrintReportReceipt() {
  var area = document.getElementById("reportReceiptPrintArea");
  // lan-receipt.js missing (e.g. not uploaded to the server): still print, with the images as-is
  if (!window.MiniposLanReceipt) { printReportReceiptHtml(area.innerHTML); return; }
  // Print the very image the LAN printer gets, so the dialog's bill is identical to a LAN
  // bill; the HTML version is only a fallback if rendering the image fails.
  var paper = (window.REPORT_RECEIPT_PRINTER || {}).paper_width;
  // dialogPaperPx: ເຄື່ອງພິມໃນຕົວ (Android) ວາງບິນແບບ 58mm ບໍ່ໃຫ້ຖືກຕັດຂວາ; ເຄື່ອງອື່ນຄືເກົ່າ
  var dialogPx = MiniposLanReceipt.dialogPaperPx ? MiniposLanReceipt.dialogPaperPx(paper) : MiniposLanReceipt.paperPx(paper);
  MiniposLanReceipt.toImage(area, dialogPx, '../../', REPORT_RCPT_EXTRA_CSS).then(function (uri) {
    return MiniposLanReceipt.imageHtml(uri, paper);
  }).catch(function (err) {
    console.warn('Receipt image failed, printing HTML instead:', err);
    return MiniposLanReceipt.printHtml(area);
  }).then(printReportReceiptHtml);
}

function printReportReceiptHtml(printContent) {
  var iframe = document.getElementById("reportPrintIframe");
  if (!iframe) {
    iframe = document.createElement('iframe');
    iframe.id = 'reportPrintIframe';
    iframe.style.cssText = 'position:absolute;width:0px;height:0px;left:-9999px;top:-9999px;border:none;';
    document.body.appendChild(iframe);
  }
  var iframeDoc = iframe.contentWindow || iframe.contentDocument;
  if (iframeDoc.document) iframeDoc = iframeDoc.document;

  iframeDoc.open();
  iframeDoc.write(`<!DOCTYPE html><html><head>
    <meta charset="utf-8">
    <title>${repT('reports.receipt_print_title')}</title>
    <link rel="stylesheet" href="../../assets/css/local-font.css">
    <style>
      @page { size: 80mm auto; margin: 0mm; }
      @font-face { font-family: 'Receipt Latin'; src: url('../../assets/fonts/NotoSans-Latin-400.woff2') format('woff2'); font-weight: 400; }
      @font-face { font-family: 'Receipt Latin'; src: url('../../assets/fonts/NotoSans-Latin-400.woff2') format('woff2'); font-weight: 400; size-adjust: 90%; unicode-range: U+0041-005A, U+0061-007A, U+00C0-024F; }
      @font-face { font-family: 'Receipt Latin'; src: url('../../assets/fonts/NotoSans-Latin-700.woff2') format('woff2'); font-weight: 700; }
      @font-face { font-family: 'Receipt Latin'; src: url('../../assets/fonts/NotoSans-Latin-700.woff2') format('woff2'); font-weight: 700; size-adjust: 90%; unicode-range: U+0041-005A, U+0061-007A, U+00C0-024F; }
      * { box-sizing: border-box; font-family: 'Receipt Latin', 'Noto Sans Lao Local', 'Noto Sans Lao', 'Souliyo', 'Boon', Arial, sans-serif !important; color: #000 !important; -webkit-font-smoothing: antialiased; text-rendering: optimizeLegibility; }
      html, body { width: 80mm; margin: 0 auto; padding: 8px 6px; background: #fff; color: #000 !important; font-size: 17px; font-weight: 400; line-height: 1.5; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      /* No Bootstrap in this iframe — without this, headings keep the browser's default
         ~2.33em top margin and push the shop name far below the logo. */
      h1,h2,h3,h4,h5,h6 { margin: 0 !important; } h6 { font-size: 20px !important; white-space: nowrap; }
      .text-center { text-align: center !important; } .text-right { text-align: right !important; }
      .font-weight-bold { font-weight: 700 !important; color: #000 !important; }
      .small { font-size: 14px !important; color: #000 !important; font-weight: 400 !important; }
      .d-flex { display: flex; } .justify-content-between { justify-content: space-between; }
      [style*="display: none"], [style*="display:none"], .d-none { display: none !important; }
      .mb-0{margin-bottom:0!important}.mb-1{margin-bottom:4px!important}.mb-2{margin-bottom:8px!important}
      .mt-2{margin-top:8px!important}
      .text-muted { color: #000 !important; font-weight: 400 !important; }
      .receipt-header-address, .receipt-header-tel { font-size: 15px !important; font-weight: 400 !important; color: #000 !important; line-height: 1.4 !important; }
      .receipt-footer-msg { font-size: 15px !important; font-weight: 400 !important; color: #000 !important; border-top: 1px dashed #000 !important; margin-top: 10px !important; padding-top: 8px !important; text-align: center !important; }
      /* logo + QR arrive already converted to black/white by MiniposLanReceipt.printHtml() */
      img.receipt-logo { max-width:90px!important; max-height:90px!important; height:auto!important; display:block!important; margin:0 auto 12px auto!important; object-fit:contain!important; }
      img.receipt-qr-img { width:50%!important; max-width:50%!important; max-height:none!important; height:auto!important; display:block; margin:6px auto 2px auto!important; object-fit:contain!important; }
      table { width:100%; border-collapse:collapse; margin:4px 0; font-size:15px !important; color: #000 !important; }
      td,th { padding:4px 0; vertical-align:top; color: #000 !important; font-weight: 400 !important; }
      th { font-weight: 700 !important; }
      .rc-strong, .rc-strong * { font-weight: 400 !important; -webkit-text-stroke: 0.25px #000; }
      #rc_rep_total { font-size: 19px !important; }
      #rc_rep_multicur { font-size: 14.5px !important; }
      #rc_rep_bank_name_lbl { font-size: 15px !important; }
      /* The bill image already carries its own margins — let it span the whole page */
      html:has(.receipt-print-img), body:has(.receipt-print-img) { width: auto; padding: 0 !important; }
      @media print {
        html,body { width:100%; margin:0; padding:2mm; color: #000 !important; }
        * { color: #000 !important; }
        body:has(.receipt-print-img) { padding: 0 !important; }
      }
      ${(window.MiniposLanReceipt && MiniposLanReceipt.dialogPageCss) ? MiniposLanReceipt.dialogPageCss() : ''}
    </style>
  </head><body>${printContent}</body></html>`);
  iframeDoc.close();

  var images = iframeDoc.getElementsByTagName('img');
  var totalImages = images.length;
  var loadedCount = 0;
  var printTriggered = false;

  function doTriggerPrint() {
    if (printTriggered) return;
    printTriggered = true;
    iframe.contentWindow.focus();
    iframe.contentWindow.print();
  }

  if (totalImages === 0) {
    setTimeout(doTriggerPrint, 150);
  } else {
    for (var i = 0; i < totalImages; i++) {
      if (images[i].complete && images[i].naturalWidth !== 0) {
        loadedCount++;
      } else {
        images[i].onload = images[i].onerror = function() {
          loadedCount++;
          if (loadedCount >= totalImages) {
            setTimeout(doTriggerPrint, 100);
          }
        };
      }
    }
    if (loadedCount >= totalImages) {
      setTimeout(doTriggerPrint, 150);
    } else {
      setTimeout(doTriggerPrint, 500);
    }
  }
}

