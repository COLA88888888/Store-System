<?php
  // The branch's own LAN receipt printer (Settings > Printers), if it has one — reprinting a
  // bill then goes straight to that printer with no print preview, same as the POS checkout.
  $__repReceiptPrinter = null;
  try {
      $__rpStmt = $pdo->prepare("SELECT id, name, type, paper_width FROM tbl_printer WHERE is_receipt_printer = 1 AND status = 1 AND branch_id = ? LIMIT 1");
      $__rpStmt->execute([getActiveStoreId($pdo)]);
      if ($__rpRow = $__rpStmt->fetch(PDO::FETCH_ASSOC)) {
          $__repReceiptPrinter = [
              'printer_id'   => (int)$__rpRow['id'],
              'printer_name' => $__rpRow['name'],
              'printer_type' => (($__rpRow['type'] ?? '') === 'ip') ? 'ip' : 'browser',
              'paper_width'  => intval($__rpRow['paper_width'] ?? 80) === 58 ? 58 : 80,
          ];
      }
  } catch (Throwable $e) {}
?>
<script src="../../assets/js/lan-receipt.js?v=<?php echo @filemtime(__DIR__ . '/../../../../assets/js/lan-receipt.js'); ?>"></script>
<script>
// Strings the reprint receipt shares with the checkout receipt (same keys, same defaults)
window.REPORT_RCPT_I18N = <?php echo tjson([
  'pos.customer_default'      => 'ລູກຄ້າທົ່ວໄປ',
  'pos.receipt_vat_exclusive' => 'ອມພ (%s):',
  'pos.receipt_vat_inclusive' => 'ລວມ ອມພ (%s):',
  'pos.receipt_free_gift'     => 'ແຖມຟຣີ',
  'pos.redeem_badge'          => 'ແລກຄະແນນ',
  'pos.receipt_scan_transfer' => 'ສະແກນ QR ໂອນຊຳລະ',
]); ?>;
window.REPORT_QUEUE_ENABLED = <?php echo getSetting($pdo, 'queue_enabled', '0') === '1' ? 'true' : 'false'; ?>;
window.REPORT_RECEIPT_PRINTER = <?php echo json_encode($__repReceiptPrinter, JSON_UNESCAPED_UNICODE); ?>;
window.REPORT_LAN_MSG = <?php echo json_encode([
    'sent'   => t('reports.reprint_sent', 'ສົ່ງບິນໄປເຄື່ອງພິມແລ້ວ'),
    'failed' => t('pos.lan_print_failed', 'ພິມໃບຍ່ອຍຜ່ານ LAN ບໍ່ສຳເລັດ: %s'),
], JSON_UNESCAPED_UNICODE); ?>;

// Reprint: LAN receipt printer → print straight away (no preview); otherwise the browser dialog.
function reprintReceiptAuto() {
  var rp = window.REPORT_RECEIPT_PRINTER;
  if (!rp || rp.printer_type !== 'ip' || !window.MiniposLanReceipt) {
    doPrintReportReceipt();
    return;
  }
  // Same per-id sizes the POS adds to its LAN receipt image (#rc_total etc.)
  var extraCss = ' #rc_rep_total{font-size:19px !important;} #rc_rep_multicur{font-size:14.5px !important;} #rc_rep_bank_name_lbl{font-size:15px !important;}';
  MiniposLanReceipt.print(document.getElementById('reportReceiptPrintArea'), rp, { basePath: '../../', extraCss: extraCss }).then(function (r) {
    if (typeof Swal === 'undefined') return;
    if (r.success) {
      Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: REPORT_LAN_MSG.sent, showConfirmButton: false, timer: 1800 });
    } else {
      Swal.fire({ icon: 'error', title: REPORT_LAN_MSG.failed.replace('%s', r.message || '') });
    }
  });
}

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

// Bill window / reprint / delete strings injected by JS. Keyed "domain.key" so the dashboard's
// language switcher re-points the values live; always read them at render time through repT().
window.REPORTS_JS_I18N = <?php echo tjson([
  'reports.js_error_prefix'           => 'ເກີດຂໍ້ຜິດພາດ:',
  'reports.js_confirm_delete_plain'   => 'ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການລົບບິນເລກທີ %s ?\nສະຕັອກສິນຄ້າທັງໝົດໃນບິນນີ້ຈະຖືກຄືນເຂົ້າຄັງອັດໂຕໂນມັດ!',
  'reports.js_connecting'             => 'ກຳລັງເຊື່ອມຕໍ່ເຊີບເວີ...',
  'reports.js_no_items'               => 'ບໍ່ມີລາຍການສິນຄ້າ',
  'reports.js_load_fail'              => 'ບໍ່ສາມາດໂຫຼດຂໍ້ມູນບິນໄດ້!',
  'reports.js_connect_error'          => 'ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່!',
  'reports.js_connect_fail_alert'     => 'ບໍ່ສາມາດເຊື່ອມຕໍ່ກັບເຊີເວີໄດ້!',
  'reports.js_delete_success_title'   => 'ລົບບິນສຳເລັດ!',
  'reports.js_error_title'            => 'ເກີດຂໍ້ຜິດພາດ!',
  'reports.js_delete_confirm_title'   => 'ຢືນຢັນການລົບບິນຂາຍ?',
  'reports.js_delete_confirm_part1'   => 'ທ່ານຕ້ອງການລົບບິນເລກທີ',
  'reports.js_delete_confirm_part2'   => 'ແທ້ບໍ?',
  'reports.js_delete_confirm_note'    => 'ສະຕັອກສິນຄ້າທັງໝົດໃນບິນນີ້ຈະຖືກຄືນເຂົ້າຄັງອັດໂຕໂນມັດ!',
  'reports.js_confirm_delete_btn'     => 'ຢືນຢັນລົບ',
  'reports.js_cancel_btn'             => 'ຍົກເລີກ',
  'reports.export_title_all_sales'    => 'ລາຍງານການຂາຍທັງໝົດ',
  'reports.receipt_print_title'       => 'ໃບບິນຮັບເງິນ (POS)',
  'reports.no_search_match'           => 'ບໍ່ພົບຂໍ້ມູນທີ່ຕົງກັບຄຳຄົ້ນຫາ',
  'reports.default_customer'          => 'ລູກຄ້າທົ່ວໄປ',
  'reports.modal_cash_received_label' => 'ຮັບເງິນສົດ:',
  'reports.modal_qr_received_label'   => 'ຮັບເງິນໂອນ:',
  'reports.js_item_fallback'          => 'ສິນຄ້າ',
  'reports.js_vat_exclusive'          => 'ອມພ (%s):',
  'reports.js_vat_inclusive'          => 'ລວມ ອມພ (%s):',
  'reports.js_scan_transfer'          => 'ສະແກນ QR ໂອນຊຳລະ',
]); ?>;
function repT(key) {
  var s = (window.REPORTS_JS_I18N && REPORTS_JS_I18N[key]) || key;
  var args = Array.prototype.slice.call(arguments, 1);
  return String(s).replace(/%s/g, function () { return args.length ? args.shift() : ''; });
}

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
  // Same markup as the checkout receipt (posMoneyIn): the symbol in a .cur-sym span
  function withSym(code) {
    var txt = repMoneyInCode(netKip, code);
    if (txt === '-') return txt;
    var i = txt.lastIndexOf(' ');
    return i < 0 ? txt : txt.slice(0, i) + ' <span class="cur-sym">' + txt.slice(i + 1) + '</span>';
  }
  $('#rc_rep_total_kip').html(withSym('KIP'));
  $('#rc_rep_total_thb').html(withSym('THB'));
  $('#rc_rep_total_usd').html(withSym('USD'));
  $('#rc_rep_total_cny').html(withSym('CNY'));
}
// n is a KIP amount -> formatted string in the report currency
function repMoneyFromKip(n) {
  n = parseFloat(n) || 0;
  var v = REP_CUR.isKip ? Math.round(n) : (n / REP_CUR.rate);
  return v.toLocaleString('en-US', { minimumFractionDigits: REP_CUR.decimals, maximumFractionDigits: REP_CUR.decimals }) + ' <span class="cur-sym">' + REP_CUR.symbol + '</span>';
}
// n is ALREADY in the report currency -> just format it
function repMoney(n) {
  n = parseFloat(n) || 0;
  return n.toLocaleString('en-US', { minimumFractionDigits: REP_CUR.decimals, maximumFractionDigits: REP_CUR.decimals }) + ' <span class="cur-sym">' + REP_CUR.symbol + '</span>';
}

function viewBillDetails(billNo) {
  if (!billNo) return;

  $('#modal_bill_no').text(billNo);
  $('#modal_items_body').empty();
  $('#reportBillModal').modal('show');

  // Smart Slow Network Fallback: Only show loading if server takes > 600ms
  var slowNetTimer = setTimeout(function() {
    $('#modal_items_body').html('<tr><td colspan="4" class="text-center py-3 text-muted" style="font-size:0.9rem;"><i class="fas fa-wifi text-warning mr-1"></i> ' + repT('reports.js_connecting') + '</td></tr>');
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
        $('#modal_customer').text(b.customer_display_name || b.customer_name || repT('reports.default_customer'));

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
        // Show only the payment method(s) actually used: a cash bill has no transfer row and
        // vice versa; a split bill shows both.
        // (the rows are .d-flex, whose display:flex !important beats .toggle(), so swap classes)
        var showCash = cash > 0 || qr <= 0, showQr = qr > 0;
        $('#modal_cash_row').toggleClass('d-flex', showCash).toggleClass('d-none', !showCash);
        $('#modal_qr_row').toggleClass('d-flex', showQr).toggleClass('d-none', !showQr);

        var tbody = $('#modal_items_body');
        tbody.empty();

        if (res.details && res.details.length > 0) {
          $.each(res.details, function(idx, item) {
            var q = floatval(item.save_qty);
            var p = floatval(item.save_price);
            var total = floatval(item.save_money || (q * p));

            tbody.append(`
              <tr>
                <td class="text-left font-weight-bold">${item.save_proname || item.product_name || repT('reports.js_item_fallback')}</td>
                <td class="text-center">${q.toLocaleString()}</td>
                <td class="text-right">${repMoneyFromKip(p)}</td>
                <td class="text-right font-weight-bold">${repMoneyFromKip(total)}</td>
              </tr>
            `);
          });
        } else {
          tbody.html('<tr><td colspan="4" class="text-center text-muted">' + repT('reports.js_no_items') + '</td></tr>');
        }
      } else {
        $('#modal_items_body').html('<tr><td colspan="4" class="text-center text-danger">' + repT('reports.js_load_fail') + '</td></tr>');
      }
    },
    error: function() {
      clearTimeout(slowNetTimer);
      $('#modal_items_body').html('<tr><td colspan="4" class="text-center text-danger">' + repT('reports.js_connect_error') + '</td></tr>');
    }
  });
}

// Fill the reprint receipt (layouts/receipt_template.php, prefix rc_rep_) exactly the way the
// POS fills the checkout receipt (handleOnlineCheckoutReceiptUI in pos_checkout_js.php) — same
// rows, same labels, same show/hide rules — so a reprinted bill is the bill printed at the sale.
function fillReprintReceipt(b, details, billNo) {
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
  var fmt = repMoneyFromKip;

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
    $(P + 'customer_phone_row').attr('style', 'color:#000;font-weight:600;display:flex;').show();
  } else {
    $(P + 'customer_phone_row').attr('style', 'display:none !important;').hide();
  }

  var subtotal = parseFloat(b.sale_amount || 0);
  var discount = parseFloat(b.sale_discount_bill || 0);
  var net      = parseFloat(b.sale_barlance || 0);
  var change   = parseFloat(b.sale_return || 0);
  $(P + 'subtotal').html(fmt(subtotal));
  $(P + 'discount').html(fmt(discount));

  var rTaxType = b.tax_type || 'none';
  var rTaxId   = b.tax_id || '';
  if (rTaxType === 'none' || !rTaxId || String(rTaxId).trim() === '') {
    $(P + 'tax_id_row').attr('style', 'display: none !important;').hide();
  } else {
    $(P + 'tax_id').text(rTaxId);
    $(P + 'tax_id_row').attr('style', 'color:#000;font-weight:600;display:flex !important;').show();
  }

  var rVatRate = parseFloat(b.vat_rate) || 0;
  var rVatAmt  = parseFloat(b.vat_amount);
  if (isNaN(rVatAmt) || (rVatAmt === 0 && rVatRate > 0 && rTaxType !== 'none')) {
    var amtAfterDisc = Math.max(0, subtotal - discount);
    if (rTaxType === 'exclusive' && rVatRate > 0 && rVatRate < 100) {
      rVatAmt = Math.round(amtAfterDisc * (rVatRate / (100 - rVatRate)));
    } else if (rTaxType === 'inclusive' && rVatRate > 0) {
      rVatAmt = Math.round(amtAfterDisc - (amtAfterDisc / (1 + (rVatRate / 100))));
    } else {
      rVatAmt = 0;
    }
  }
  var vatShow = 'color:#000;font-weight:600;display:flex !important;';
  if (rTaxType === 'none' || rVatRate <= 0 || rVatAmt <= 0) {
    $(P + 'vat_row').attr('style', 'display: none !important;').hide();
  } else if (rTaxType === 'exclusive' || rTaxType === 'inclusive') {
    $(P + 'vat_label').text(rpT(rTaxType === 'inclusive' ? 'pos.receipt_vat_inclusive' : 'pos.receipt_vat_exclusive', rVatRate + '%'));
    $(P + 'vat').html(fmt(rVatAmt));
    $(P + 'vat_row').attr('style', vatShow).show();
  } else {
    $(P + 'vat_label').text(rpT('pos.receipt_vat_exclusive', rVatRate > 0 ? rVatRate + '%' : ''));
    $(P + 'vat').html(fmt(rVatAmt));
    $(P + 'vat_row').attr('style', vatShow).show();
  }

  $(P + 'total').html(fmt(net));
  $(P + 'change').html(fmt(change));
  fillReprintMultiCurrency(net);

  // Loyalty points, same rows as at checkout
  var ptsEarned = parseInt(b.points_earned) || 0;
  if (ptsEarned > 0) {
    $(P + 'points_earned').text(ptsEarned.toLocaleString());
    $(P + 'points_row').css('display', 'flex');
  } else {
    $(P + 'points_row').hide();
  }
  var ptsRedeemed = parseInt(b.points_redeemed) || 0;
  if (ptsRedeemed > 0) {
    $(P + 'points_redeemed').text('-' + ptsRedeemed.toLocaleString());
    $(P + 'points_redeem_row').css('display', 'flex');
    var ptsDisc = parseFloat(b.points_discount) || 0;
    if (ptsDisc > 0) {
      $(P + 'points_discount').html('-' + fmt(ptsDisc));
      $(P + 'points_discount_row').css('display', 'flex');
    } else {
      $(P + 'points_discount_row').hide();
    }
    if (b.points_balance_after !== null && b.points_balance_after !== undefined) {
      $(P + 'points_balance').text((parseInt(b.points_balance_after) || 0).toLocaleString());
      $(P + 'points_balance_row').css('display', 'flex');
    } else {
      $(P + 'points_balance_row').hide();
    }
  } else {
    $(P + 'points_redeem_row, ' + P + 'points_discount_row, ' + P + 'points_balance_row').hide();
  }

  var cashAmt = parseFloat(b.cash_received) || 0;
  var qrAmt   = parseFloat(b.qr_received) || 0;
  var pType   = b.type_pay || b.payment_type || '';
  if (cashAmt === 0 && qrAmt === 0) {
    if (pType === 'ເງິນສົດ') cashAmt = net + change;
    else qrAmt = net;
  }
  $(P + 'cash_amt').html(fmt(cashAmt));
  $(P + 'qr_amt').html(fmt(qrAmt));
  $(P + 'cash_row').toggle(cashAmt > 0 || (cashAmt === 0 && qrAmt === 0));
  $(P + 'qr_row').toggle(qrAmt > 0);

  // Bank QR — the exact bank/QR used for this sale
  var isTransferPayment = (qrAmt > 0) || (pType && (pType.indexOf('ໂອນ') !== -1 || pType.indexOf('QR') !== -1));
  var $qrBox = $('#reportReceiptPrintArea .receipt-qr-box');
  if (isTransferPayment) {
    var bQrImg = b.bank_qr_img || '';
    if (bQrImg && bQrImg.trim() !== '') $(P + 'bank_qr_img').attr('src', bQrImg).show();
    else $(P + 'bank_qr_img').hide();
    var labelBank = b.bank_name || '';
    var scanLbl = rpT('pos.receipt_scan_transfer');
    $(P + 'bank_name_lbl').text(labelBank ? scanLbl + ' (' + labelBank + ')' : scanLbl);
    $qrBox.attr('style', 'display:block!important; text-align:center; margin:8px 0;').show();
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
    <title>${repT('reports.receipt_print_title')}</title>
    <link rel="stylesheet" href="../../assets/css/local-font.css">
    <style>
      @page { size: 80mm auto; margin: 0mm; }
      * { box-sizing: border-box; font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', Arial, sans-serif !important; color: #000 !important; -webkit-font-smoothing: antialiased; text-rendering: optimizeLegibility; }
      html, body { width: 80mm; margin: 0 auto; padding: 8px 6px; background: #fff; color: #000 !important; font-size: 17px; font-weight: 600; line-height: 1.5; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      /* No Bootstrap in this iframe — without this, headings keep the browser's default
         ~2.33em top margin and push the shop name far below the logo. */
      h1,h2,h3,h4,h5,h6 { margin: 0 !important; } h6 { font-size: 20px !important; }
      .text-center { text-align: center !important; } .text-right { text-align: right !important; }
      .font-weight-bold { font-weight: 700 !important; color: #000 !important; }
      .small { font-size: 14px !important; color: #000 !important; font-weight: 600 !important; }
      .d-flex { display: flex; } .justify-content-between { justify-content: space-between; }
      [style*="display: none"], [style*="display:none"], .d-none { display: none !important; }
      .mb-0{margin-bottom:0!important}.mb-1{margin-bottom:4px!important}.mb-2{margin-bottom:8px!important}
      .mt-2{margin-top:8px!important}
      .text-muted { color: #000 !important; font-weight: 600 !important; }
      .receipt-header-address, .receipt-header-tel { font-size: 15px !important; font-weight: 600 !important; color: #000 !important; line-height: 1.4 !important; }
      .receipt-footer-msg { font-size: 15px !important; font-weight: 700 !important; color: #000 !important; border-top: 1px dashed #000 !important; margin-top: 10px !important; padding-top: 8px !important; text-align: center !important; }
      img.receipt-logo { max-width:80px!important; max-height:80px!important; height:auto!important; display:block!important; margin:2px auto 2px auto!important; object-fit:contain!important; }
      img.receipt-qr-img { max-width:130px!important; max-height:130px!important; height:auto!important; display:block!important; margin:6px auto 2px auto!important; object-fit:contain!important; image-rendering:-webkit-optimize-contrast; image-rendering:crisp-edges; image-rendering:pixelated; }
      table { width:100%; border-collapse:collapse; margin:4px 0; font-size:15px; color: #000 !important; }
      td,th { padding:4px 0; vertical-align:top; color: #000 !important; font-weight: 600 !important; }
      th { font-weight: 700 !important; }
      #rc_rep_total { font-size: 19px !important; }
      #rc_rep_multicur { font-size: 14.5px !important; }
      #rc_rep_bank_name_lbl { font-size: 15px !important; }
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
        var name = item.save_proname || item.product_name || repT('reports.js_item_fallback');
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
              title: repT('reports.js_delete_success_title'),
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
            Swal.fire({ icon: 'error', title: repT('reports.js_error_title'), text: res.message });
          } else {
            alert(repT('reports.js_error_prefix') + ' ' + res.message);
          }
        }
      },
      error: function() {
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'error', title: repT('reports.js_error_title'), text: repT('reports.js_connect_fail_alert') });
        } else {
          alert(repT('reports.js_connect_fail_alert'));
        }
      }
    });
  };

  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: repT('reports.js_delete_confirm_title'),
      html: repT('reports.js_delete_confirm_part1') + ' <strong class="text-danger">' + billNo + '</strong> ' + repT('reports.js_delete_confirm_part2') + '<br><small class="text-muted">' + repT('reports.js_delete_confirm_note') + '</small>',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#64748b',
      confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> ' + repT('reports.js_confirm_delete_btn'),
      cancelButtonText: repT('reports.js_cancel_btn')
    }).then(function(result) {
      if (result.isConfirmed) {
        doDelete();
      }
    });
  } else {
    if (confirm(repT('reports.js_confirm_delete_plain', billNo))) {
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
        '<tr id="noSearchResultRow"><td colspan="' + colCount + '" class="text-center py-4 text-muted font-weight-bold">' + repT('reports.no_search_match') + ' "' + $('<div>').text(val).html() + '"</td></tr>'
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
  html += '<h2>' + repT('reports.export_title_all_sales') + '</h2>';
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
    title.innerText = repT('reports.export_title_all_sales');
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
