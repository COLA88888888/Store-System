<script>
// ===== Report display currency (data is stored in KIP; reports show the active currency) =====
<?php
  $__rdc = function_exists('getDisplayCurrency') ? getDisplayCurrency($pdo)
         : ['code' => 'KIP', 'symbol' => '₭', 'decimals' => 0, 'rate' => 1, 'rates' => ['KIP' => 1]];
?>
window.REP_CUR = {
  code: <?php echo json_encode($__rdc['code']); ?>,
  symbol: <?php echo json_encode($__rdc['symbol'], JSON_UNESCAPED_UNICODE); ?>,
  decimals: <?php echo (int)$__rdc['decimals']; ?>,
  rate: <?php echo json_encode((float)$__rdc['rate']); ?>,
  isKip: <?php echo ($__rdc['code'] === 'KIP') ? 'true' : 'false'; ?>,
  rates: <?php echo json_encode($__rdc['rates'] ?? ['KIP'=>1,'THB'=>0,'USD'=>0,'CNY'=>0]); ?>,
  meta: <?php echo json_encode(getCurrencyMeta(), JSON_UNESCAPED_UNICODE); ?>
};

// A KIP amount formatted into currency `code` (KIP|THB|USD|CNY); '-' if no rate set.
function repMoneyInCode(kip, code) {
  kip = parseFloat(kip) || 0;
  var m = (REP_CUR.meta && REP_CUR.meta[code]) || { symbol: code, decimals: 0 };
  if (code === 'KIP') return Math.round(kip).toLocaleString('en-US') + ' ' + m.symbol;
  var r = parseFloat(REP_CUR.rates[code]) || 0;
  if (r <= 0) return '-';
  return (kip / r).toLocaleString('en-US', { minimumFractionDigits: m.decimals, maximumFractionDigits: m.decimals }) + ' ' + m.symbol;
}
// Fill the reprint receipt's 4-currency net-total block from a KIP amount.
function fillReprintMultiCurrency(netKip) {
  $('#rc_rep_total_kip').text(repMoneyInCode(netKip, 'KIP'));
  $('#rc_rep_total_thb').text(repMoneyInCode(netKip, 'THB'));
  $('#rc_rep_total_usd').text(repMoneyInCode(netKip, 'USD'));
  $('#rc_rep_total_cny').text(repMoneyInCode(netKip, 'CNY'));
}
// n is a KIP amount -> formatted string in the report currency
function repMoneyFromKip(n) {
  n = parseFloat(n) || 0;
  var v = REP_CUR.isKip ? Math.round(n) : (n / REP_CUR.rate);
  return v.toLocaleString('en-US', { minimumFractionDigits: REP_CUR.decimals, maximumFractionDigits: REP_CUR.decimals }) + ' <span class="cur-sym">' + REP_CUR.symbol + '</span>';
}
// n is ALREADY in the report currency -> just format it
function repMoney(n) {
  n = parseFloat(n) || 0;
  return n.toLocaleString('en-US', { minimumFractionDigits: REP_CUR.decimals, maximumFractionDigits: REP_CUR.decimals }) + ' <span class="cur-sym">' + REP_CUR.symbol + '</span>';
}

function viewBillDetails(billNo) {
  if (!billNo) return;

  $('#modal_bill_no').text(billNo);
  $('#modal_items_body').empty();
  $('#reportBillModal').modal('show');

  // Smart Slow Network Fallback: Only show loading if server takes > 600ms
  var slowNetTimer = setTimeout(function() {
    $('#modal_items_body').html('<tr><td colspan="4" class="text-center py-3 text-muted" style="font-size:0.9rem;"><i class="fas fa-wifi text-warning mr-1"></i> ກຳລັງເຊື່ອມຕໍ່ເຊີບເວີ...</td></tr>');
  }, 600);

  $.ajax({
    url: 'reports.php',
    type: 'GET',
    data: { action: 'get_bill_details', bill_no: billNo },
    dataType: 'json',
    success: function(res) {
      clearTimeout(slowNetTimer);
      if (res.success && res.bill) {
        var b = res.bill;
        $('#modal_date').text((b.sale_date || '') + ' ' + (b.sale_time || ''));
        $('#modal_cashier').text(b.cashier_display_name || b.user_receive || 'Admin');
        $('#modal_customer').text(b.customer_display_name || b.customer_name || 'ລູກຄ້າທົ່ວໄປ');

        if (b.bank_name) {
          $('#modal_bank_wrapper').show();
          $('#modal_bank_name').text(b.bank_name);
        } else {
          $('#modal_bank_wrapper').hide();
        }

        var subtotal = parseFloat(b.sale_amount || 0);
        var discount = parseFloat(b.sale_discount_bill || 0);
        var net      = parseFloat(b.sale_barlance || 0);
        var cash     = parseFloat(b.cash_received || (b.sale_pay || 0));
        var qr       = parseFloat(b.qr_received || 0);
        var change   = parseFloat(b.sale_return || 0);

        $('#modal_subtotal').html(repMoneyFromKip(subtotal));
        $('#modal_discount').html(repMoneyFromKip(discount));
        $('#modal_net_total').html(repMoneyFromKip(net));
        $('#modal_cash_rec').html(repMoneyFromKip(cash));
        $('#modal_qr_rec').html(repMoneyFromKip(qr));
        $('#modal_change').html(repMoneyFromKip(change));

        var tbody = $('#modal_items_body');
        tbody.empty();

        if (res.details && res.details.length > 0) {
          $.each(res.details, function(idx, item) {
            var q = floatval(item.save_qty);
            var p = floatval(item.save_price);
            var total = floatval(item.save_money || (q * p));

            tbody.append(`
              <tr>
                <td class="text-left font-weight-bold">${item.save_proname || item.product_name || 'ສິນຄ້າ'}</td>
                <td class="text-center">${q.toLocaleString()}</td>
                <td class="text-right">${repMoneyFromKip(p)}</td>
                <td class="text-right font-weight-bold">${repMoneyFromKip(total)}</td>
              </tr>
            `);
          });
        } else {
          tbody.html('<tr><td colspan="4" class="text-center text-muted">ບໍ່ມີລາຍການສິນຄ້າ</td></tr>');
        }
      } else {
        $('#modal_items_body').html('<tr><td colspan="4" class="text-center text-danger">ບໍ່ສາມາດໂຫຼດຂໍ້ມູນບິນໄດ້!</td></tr>');
      }
    },
    error: function() {
      clearTimeout(slowNetTimer);
      $('#modal_items_body').html('<tr><td colspan="4" class="text-center text-danger">ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່!</td></tr>');
    }
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
        var b = res.bill;
        $('#rc_rep_bill').text(b.sale_save_bill || billNo);
        $('#rc_rep_date').text((b.sale_date || '') + ' ' + (b.sale_time || ''));

        // Reprint must show the SAME queue number the bill got at checkout, not a new
        // or missing one — so this goes off the bill's own saved queue_no, not the
        // store's current queue_enabled setting (which may have since been toggled off).
        if (b.queue_no !== undefined && b.queue_no !== null && String(b.queue_no).trim() !== '') {
          $('#rc_rep_queue_no').text(b.queue_no);
          $('#rc_rep_queue_wrap').css('display', 'block');
        } else {
          $('#rc_rep_queue_wrap').css('display', 'none');
        }
        $('#rc_rep_cashier').text(b.user_receive || 'Admin');
        $('#rc_rep_customer').text(b.customer_name || 'ລູກຄ້າທົ່ວໄປ');

        var subtotal = parseFloat(b.sale_amount || 0);
        var discount = parseFloat(b.sale_discount_bill || 0);
        var net      = parseFloat(b.sale_barlance || 0);
        var cash     = parseFloat(b.cash_received || 0);
        var qr       = parseFloat(b.qr_received || 0);
        var change   = parseFloat(b.sale_return || 0);
        var typePay  = b.type_pay || b.payment_type || '';

        if (cash === 0 && qr === 0) {
          if (typePay.indexOf('ເງິນສົດ') !== -1) {
            cash = parseFloat(b.sale_pay || b.sale_barlance || 0);
          }
          if (typePay.indexOf('ໂອນ') !== -1 || typePay.indexOf('QR') !== -1) {
            qr = parseFloat(b.sale_barlance || 0);
          }
        }

        $('#rc_rep_subtotal').html(repMoneyFromKip(subtotal));
        $('#rc_rep_discount').html(repMoneyFromKip(discount));

        var bTaxType = b.tax_type || 'none';
        var bTaxId   = b.tax_id || b.company_tax_id || '';
        if (bTaxType === 'none' || !bTaxId || bTaxId.trim() === '') {
          $('#rc_rep_tax_id_row').hide();
        } else {
          $('#rc_rep_tax_id').text(bTaxId);
          $('#rc_rep_tax_id_row').show();
        }

        var bVatRate = parseFloat(b.vat_rate) || 0;
        var bVatAmt  = parseFloat(b.vat_amount);

        if (isNaN(bVatAmt) || (bVatAmt === 0 && bVatRate > 0 && bTaxType !== 'none')) {
          var amtAfterDisc = Math.max(0, subtotal - discount);
          if (bTaxType === 'exclusive' && bVatRate > 0) {
            bVatAmt = Math.round(amtAfterDisc * (bVatRate / 100));
          } else if (bTaxType === 'inclusive' && bVatRate > 0) {
            bVatAmt = Math.round(amtAfterDisc - (amtAfterDisc / (1 + (bVatRate / 100))));
          } else {
            bVatAmt = 0;
          }
        }

        if (bTaxType === 'none' || bVatRate <= 0) {
          $('#rc_rep_vat_row').hide();
        } else if (bTaxType === 'exclusive' && (bVatAmt > 0 || bVatRate > 0)) {
          $('#rc_rep_vat_label').text('ອມພ (' + bVatRate + '%):');
          $('#rc_rep_vat').html(repMoneyFromKip(bVatAmt));
          $('#rc_rep_vat_row').show();
        } else if (bTaxType === 'inclusive' && (bVatAmt > 0 || bVatRate > 0)) {
          $('#rc_rep_vat_label').text('ລວມ ອມພ (' + bVatRate + '%):');
          $('#rc_rep_vat').html(repMoneyFromKip(bVatAmt));
          $('#rc_rep_vat_row').show();
        } else if (bVatAmt > 0) {
          $('#rc_rep_vat_label').text('ອມພ (' + (bVatRate > 0 ? bVatRate + '%' : '') + '):');
          $('#rc_rep_vat').html(repMoneyFromKip(bVatAmt));
          $('#rc_rep_vat_row').show();
        } else {
          $('#rc_rep_vat_row').hide();
        }

        $('#rc_rep_total').html(repMoneyFromKip(net));
        $('#rc_rep_change').html(repMoneyFromKip(change));
        fillReprintMultiCurrency(net);   // net total in all 4 currencies

        var payHtml = '';
        payHtml += '<div class="d-flex justify-content-between"><span>ຮັບເງິນສົດ:</span><span>' + repMoneyFromKip(cash) + '</span></div>';
        payHtml += '<div class="d-flex justify-content-between"><span>ຮັບເງິນໂອນ:</span><span>' + repMoneyFromKip(qr) + '</span></div>';
        $('#rc_rep_payment_rows').html(payHtml);

        // Bank QR box — must show the exact bank/QR/account used for this sale, same as the
        // original receipt printed at checkout, not a generic store default.
        var isTransferPayment = (qr > 0) || (typePay && (typePay.indexOf('ໂອນ') !== -1 || typePay.indexOf('QR') !== -1));
        if (isTransferPayment) {
          var bQrImg = b.bank_qr_img || '';
          if (bQrImg) {
            $('#rc_rep_bank_qr_img').attr('src', bQrImg).show();
          } else {
            $('#rc_rep_bank_qr_img').hide();
          }
          // Reprinted bill: show the bank name only — no account name, no account number.
          var labelBank = b.bank_name || '';
          $('#rc_rep_bank_name_lbl').text(labelBank ? 'ສະແກນ QR ໂອນຊຳລະ (' + labelBank + ')' : 'ສະແກນ QR ໂອນຊຳລະ');
          $('#rc_rep_bank_acc_lbl').text('').hide();
          $('.receipt-qr-box').show();
        } else {
          $('.receipt-qr-box').hide();
        }

        var tbody = $('#rc_rep_items');
        tbody.empty();

        if (res.details && res.details.length > 0) {
          $.each(res.details, function(idx, item) {
            var q = floatval(item.save_qty);
            var p = floatval(item.save_price);
            var total = floatval(item.save_money || (q * p));

            tbody.append(`
              <tr>
                <td style="text-align:left;padding:3px 0;">${item.save_proname || item.product_name || 'ສິນຄ້າ'}</td>
                <td style="text-align:center;padding:3px 0;">${q.toLocaleString()}</td>
                <td style="text-align:right;padding:3px 0;">${repMoneyFromKip(total)}</td>
              </tr>
            `);
          });
        }

        doPrintReportReceipt();
      } else {
        alert('ບໍ່ສາມາດໂຫຼດຂໍ້ມູນບິນໄດ້!');
      }
    },
    error: function() {
      alert('ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່!');
    }
  });
}

function doPrintReportReceipt() {
  var printContent = document.getElementById("reportReceiptPrintArea").innerHTML;
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
    <title>ໃບບິນຮັບເງິນ (POS)</title>
    <link rel="stylesheet" href="../../assets/css/local-font.css">
    <style>
      @page { size: 80mm auto; margin: 0mm; }
      * { box-sizing: border-box; font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', Arial, sans-serif !important; color: #000 !important; }
      html, body { width: 80mm; margin: 0 auto; padding: 8px 6px; background: #fff; color: #000 !important; font-size: 12px; line-height: 1.4; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
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
      img.receipt-qr-img { max-width:100px!important; max-height:100px!important; height:auto!important; display:block!important; margin:6px auto 2px auto!important; object-fit:contain!important; }
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

// ===== DELIVERY NOTE (ບິນສົ່ງເຄື່ອງ) from a historical sale =====
function printDeliveryNote(billNo) {
  if (!billNo) return;
  $.ajax({
    url: 'reports.php',
    type: 'GET',
    data: { action: 'get_bill_details', bill_no: billNo },
    dataType: 'json',
    success: function(res) {
      if (!res.success || !res.bill) { alert('<?php echo htmlspecialchars(t('reports.js_load_fail', 'ບໍ່ສາມາດໂຫຼດຂໍ້ມູນບິນໄດ້!'), ENT_QUOTES); ?>'); return; }
      var b = res.bill;

      var logo = (b.sender_logo || 'assets/img/logo/logo.png');
      $('#dn_rep_logo').attr('src', '../../' + logo.replace(/^\/+/, ''));
      $('#dn_rep_sender_name').text(b.sender_name || '-');
      var sph = (b.sender_tel || '').toString().trim();
      if (sph) { $('#dn_rep_sender_tel').text(sph); $('#dn_rep_sender_phone_row').css('display', 'flex'); }
      else { $('#dn_rep_sender_phone_row').css('display', 'none'); }

      $('#dn_rep_bill').text(b.sale_save_bill || billNo);
      $('#dn_rep_date').text(((b.sale_date || '') + ' ' + (b.sale_time || '')).trim() || '-');
      $('#dn_rep_customer').text(b.customer_name || '<?php echo htmlspecialchars(t('reports.default_customer', 'ລູກຄ້າທົ່ວໄປ'), ENT_QUOTES); ?>');

      var ph = (b.customer_phone || '').toString().trim();
      if (ph) { $('#dn_rep_phone').text(ph); $('#dn_rep_phone_row').css('display', 'flex'); }
      else { $('#dn_rep_phone_row').css('display', 'none'); }
      var ad = (b.customer_address || '').toString().trim();
      if (ad) { $('#dn_rep_address').text(ad); $('#dn_rep_address_row').css('display', 'block'); }
      else { $('#dn_rep_address_row').css('display', 'none'); }

      var rows = '';
      $.each(res.details || [], function(i, item) {
        var name = item.save_proname || item.product_name || 'ສິນຄ້າ';
        var q = floatval(item.save_qty);
        var unit = item.unit_name ? (' ' + item.unit_name) : '';
        rows += '<tr>'
          + '<td style="padding:2px 0;vertical-align:top;">' + name + '</td>'
          + '<td style="text-align:right;padding:2px 0;vertical-align:top;">' + q.toLocaleString() + unit + '</td>'
          + '</tr>';
      });
      $('#dn_rep_items').html(rows || '<tr><td colspan="2" style="text-align:center;padding:6px 0;">-</td></tr>');

      doPrintReportDeliveryNote();
    },
    error: function() { alert('<?php echo htmlspecialchars(t('reports.js_connect_error', 'ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່!'), ENT_QUOTES); ?>'); }
  });
}

function doPrintReportDeliveryNote() {
  var printContent = document.getElementById('deliveryNoteReportArea').innerHTML;
  var iframe = document.getElementById('reportPrintIframe');
  if (!iframe) {
    iframe = document.createElement('iframe');
    iframe.id = 'reportPrintIframe';
    iframe.style.cssText = 'position:absolute;width:0px;height:0px;left:-9999px;top:-9999px;border:none;';
    document.body.appendChild(iframe);
  }
  var doc = iframe.contentWindow || iframe.contentDocument;
  if (doc.document) doc = doc.document;
  doc.open();
  doc.write('<!DOCTYPE html><html><head><meta charset="utf-8">'
    + '<title><?php echo htmlspecialchars(t('reports.delivery_note', 'ບິນສົ່ງເຄື່ອງ'), ENT_QUOTES); ?></title>'
    + '<link rel="stylesheet" href="../../assets/css/local-font.css">'
    + '<link rel="stylesheet" href="../../plugins/fontawesome-free/css/all.min.css">'
    + '<style>'
    + '@page { size: 80mm auto; margin: 0mm; }'
    + '* { box-sizing: border-box; font-family: \'Noto Sans Lao\', \'Souliyo\', \'Boon\', Arial, sans-serif !important; color: #000 !important; -webkit-font-smoothing: antialiased; }'
    + 'html, body { width: 80mm; margin: 0 auto; padding: 8px 6px; background: #fff; font-size: 13px; font-weight: 600; line-height: 1.5; -webkit-print-color-adjust: exact; print-color-adjust: exact; }'
    + 'table { width: 100%; border-collapse: collapse; }'
    + 'td, th { color: #000 !important; }'
    + 'img { max-width: 70px; max-height: 70px; object-fit: contain; display: block; margin: 6px auto 2px auto; }'
    + '[style*="display: none"], [style*="display:none"] { display: none !important; }'
    + '@media print { html, body { width: 100%; padding: 2mm; } }'
    + '</style></head><body>' + printContent + '</body></html>');
  doc.close();

  var images = doc.getElementsByTagName('img');
  var total = images.length, loaded = 0, fired = false;
  function fire() { if (fired) return; fired = true; try { iframe.contentWindow.focus(); iframe.contentWindow.print(); } catch (e) { window.print(); } }
  if (total === 0) { setTimeout(fire, 200); }
  else {
    for (var i = 0; i < total; i++) {
      if (images[i].complete && images[i].naturalWidth !== 0) { loaded++; }
      else { images[i].onload = images[i].onerror = function() { loaded++; if (loaded >= total) setTimeout(fire, 150); }; }
    }
    setTimeout(fire, loaded >= total ? 250 : 700);
  }
}

function floatval(val) {
  var n = parseFloat(val);
  return isNaN(n) ? 0 : n;
}

function changePerPage(val) {
  var url = new URL(window.location.href);
  url.searchParams.set('per_page', val);
  url.searchParams.set('page', 1);
  window.location.href = url.toString();
}

function deleteBill(billNo) {
  if (!billNo) return;

  var doDelete = function() {
    $.ajax({
      url: 'reports.php',
      type: 'POST',
      data: { action: 'delete_bill', bill_no: billNo },
      dataType: 'json',
      success: function(res) {
        if (res.success) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: 'ລົບບິນສຳເລັດ!',
              text: res.message,
              timer: 1800,
              showConfirmButton: false
            }).then(function() {
              window.location.reload();
            });
          } else {
            alert(res.message);
            window.location.reload();
          }
        } else {
          if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'ເກີດຂໍ້ຜິດພາດ!', text: res.message });
          } else {
            alert('ເກີດຂໍ້ຜິດພາດ: ' + res.message);
          }
        }
      },
      error: function() {
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'error', title: 'ເກີດຂໍ້ຜິດພາດ!', text: 'ບໍ່ສາມາດເຊື່ອມຕໍ່ກັບເຊີເວີໄດ້!' });
        } else {
          alert('ບໍ່ສາມາດເຊື່ອມຕໍ່ກັບເຊີເວີໄດ້!');
        }
      }
    });
  };

  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: 'ຢືນຢັນການລົບບິນຂາຍ?',
      html: 'ທ່ານຕ້ອງການລົບບິນເລກທີ <strong class="text-danger">' + billNo + '</strong> ແທ້ບໍ?<br><small class="text-muted">ສະຕັອກສິນຄ້າທັງໝົດໃນບິນນີ້ຈະຖືກຄືນເຂົ້າຄັງອັດໂຕໂນມັດ!</small>',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#64748b',
      confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> ຢືນຢັນລົບ',
      cancelButtonText: 'ຍົກເລີກ'
    }).then(function(result) {
      if (result.isConfirmed) {
        doDelete();
      }
    });
  } else {
    if (confirm('ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການລົບບິນເລກທີ ' + billNo + ' ?\nສະຕັອກສິນຄ້າທັງໝົດໃນບິນນີ້ຈະຖືກຄືນເຂົ້າຄັງອັດໂຕໂນມັດ!')) {
      doDelete();
    }
  }
}

$(document).ready(function() {
  $('#reportSearchInput').on('keyup input search', function() {
    var val = $.trim($(this).val()).toLowerCase();
    var $rows = $('.report-table tbody tr:not(#noSearchResultRow)');

    if (val === '') {
      $rows.show();
      $('#noSearchResultRow').remove();
      return;
    }

    var visibleCount = 0;
    $rows.each(function() {
      var text = $(this).text().toLowerCase();
      if (text.indexOf(val) > -1) {
        $(this).show();
        visibleCount++;
      } else {
        $(this).hide();
      }
    });

    $('#noSearchResultRow').remove();
    if (visibleCount === 0 && $rows.length > 0) {
      var colCount = $('.report-table thead th').length || 12;
      $('.report-table tbody').append(
        '<tr id="noSearchResultRow"><td colspan="' + colCount + '" class="text-center py-4 text-muted font-weight-bold">ບໍ່ພົບຂໍ້ມູນທີ່ຕົງກັບຄຳຄົ້ນຫາ "' + $('<div>').text(val).html() + '"</td></tr>'
      );
    }
  });
});

// Helper to auto-trigger export after loading all records
function checkAutoExport() {
  var urlParams = new URLSearchParams(window.location.search);
  var autoAction = urlParams.get('auto_export');
  if (autoAction === 'excel') {
    window.history.replaceState({}, document.title, window.location.pathname + '?' + removeParam(window.location.search, 'auto_export'));
    setTimeout(function() { doExportExcel(); }, 300);
  } else if (autoAction === 'pdf') {
    window.history.replaceState({}, document.title, window.location.pathname + '?' + removeParam(window.location.search, 'auto_export'));
    setTimeout(function() { doExportPDF(); }, 300);
  } else if (autoAction === 'print') {
    window.history.replaceState({}, document.title, window.location.pathname + '?' + removeParam(window.location.search, 'auto_export'));
    setTimeout(function() { window.print(); }, 300);
  }
}

function removeParam(queryString, param) {
  var params = new URLSearchParams(queryString);
  params.delete(param);
  return params.toString();
}

function printReportTable(mode) {
  var urlParams = new URLSearchParams(window.location.search);
  if (mode === 'all' && urlParams.get('per_page') !== 'all') {
    urlParams.set('per_page', 'all');
    urlParams.set('auto_export', 'print');
    window.location.href = window.location.pathname + '?' + urlParams.toString();
    return;
  }
  $('#global-preloader').hide();
  window.print();
}

function exportReportExcel(mode) {
  var urlParams = new URLSearchParams(window.location.search);
  if (mode === 'all' && urlParams.get('per_page') !== 'all') {
    urlParams.set('per_page', 'all');
    urlParams.set('auto_export', 'excel');
    window.location.href = window.location.pathname + '?' + urlParams.toString();
    return;
  }
  doExportExcel();
}

function doExportExcel() {
  var table = document.querySelector('.report-table') || document.querySelector('table');
  if (!table) return;

  var cloneTable = table.cloneNode(true);
  
  var noPrints = cloneTable.querySelectorAll('.no-print');
  noPrints.forEach(function(el) { el.remove(); });

  var links = cloneTable.querySelectorAll('a');
  links.forEach(function(a) {
    var textNode = document.createTextNode(a.textContent);
    a.parentNode.replaceChild(textNode, a);
  });

  var images = cloneTable.querySelectorAll('img');
  images.forEach(function(img) {
    var title = img.getAttribute('title') || img.getAttribute('alt') || '';
    if (title && img.parentNode) {
      var textNode = document.createTextNode(title);
      img.parentNode.replaceChild(textNode, img);
    } else if (img.parentNode) {
      img.remove();
    }
  });

  var html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
  html += '<head><meta charset="utf-8"><style>';
  html += '* { font-family: "Noto Sans Lao", "Souliyo", "Boon", sans-serif !important; }';
  html += 'table { border-collapse: collapse; width: 100%; font-family: "Noto Sans Lao", "Souliyo", "Boon", sans-serif; }';
  html += 'th, td { border: 1px solid #cbd5e1; padding: 10px 12px; font-family: "Noto Sans Lao", "Souliyo", "Boon", sans-serif; vertical-align: middle; }';
  html += 'th { background-color: #f1f5f9; font-weight: bold; text-align: center !important; }';
  html += '.text-right { text-align: right !important; }';
  html += '.text-center { text-align: center !important; }';
  html += '.text-left { text-align: left !important; }';
  html += 'h2 { text-align: center !important; font-family: "Noto Sans Lao", "Souliyo", "Boon", sans-serif; font-size: 18px; margin: 15px 0; }';
  html += '</style></head><body>';
  html += '<h2>ລາຍງານການຂາຍທັງໝົດ</h2>';
  html += cloneTable.outerHTML;
  html += '</body></html>';

  var blob = new Blob(['\ufeff' + html], { type: 'application/vnd.ms-excel;charset=utf-8' });
  var url = URL.createObjectURL(blob);
  var a = document.createElement('a');
  a.href = url;
  a.download = 'sales_report_' + new Date().toISOString().slice(0, 10) + '.xls';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}

function exportReportPDF(mode) {
  var urlParams = new URLSearchParams(window.location.search);
  if (mode === 'all' && urlParams.get('per_page') !== 'all') {
    urlParams.set('per_page', 'all');
    urlParams.set('auto_export', 'pdf');
    window.location.href = window.location.pathname + '?' + urlParams.toString();
    return;
  }
  doExportPDF();
}

function doExportPDF() {
  var table = document.querySelector('.report-table') || document.querySelector('table');
  if (!table) return;

  var fileName = 'sales_report_' + new Date().toISOString().slice(0, 10) + '.pdf';

  if (typeof html2pdf !== 'undefined') {
    var wrapper = document.createElement('div');
    wrapper.style.padding = '15px';
    wrapper.style.fontFamily = "'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif";
    
    var title = document.createElement('h3');
    title.innerText = 'ລາຍງານການຂາຍທັງໝົດ';
    title.style.textAlign = 'center';
    title.style.marginBottom = '15px';
    title.style.fontFamily = "'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif";
    
    var cloneTable = table.cloneNode(true);
    cloneTable.style.width = '100%';
    cloneTable.style.borderCollapse = 'collapse';
    cloneTable.style.fontSize = '11px';

    var noPrints = cloneTable.querySelectorAll('.no-print');
    noPrints.forEach(function(el) { el.remove(); });

    wrapper.appendChild(title);
    wrapper.appendChild(cloneTable);

    var opt = {
      margin:       10,
      filename:     fileName,
      image:        { type: 'jpeg', quality: 0.98 },
      html2canvas:  { scale: 2, useCORS: true },
      jsPDF:        { unit: 'mm', format: 'a4', orientation: 'landscape' }
    };

    html2pdf().set(opt).from(wrapper).save();
  } else {
    var originalTitle = document.title;
    document.title = fileName;
    window.print();
    setTimeout(function() {
      document.title = originalTitle;
    }, 1000);
  }
}

$(document).ready(function() {
  checkAutoExport();
});
</script>
