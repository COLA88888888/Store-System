<?php
/*
 * ສ່ວນ: ເບິ່ງລາຍລະອຽດບິນ
 * ພິມບິນອັດຕະໂນມັດ, ສະກຸນເງິນຂອງລາຍງານ, ຟັງຊັນສະແດງເງິນ ແລະ viewBillDetails (ເປີດ popup
 * ລາຍລະອຽດບິນ).
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/reports/partials/js/reports_js.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>// ພິມບິນຄືນ: ມີເຄື່ອງພິມ Bluetooth ທີ່ຕັ້ງໄວ້ (ເຊື່ອມຢູ່ ຫຼື ເຊື່ອມຄືນໄດ້) → ອອກເຄື່ອງ Bluetooth ທັນທີ (ຮູບບິນດຽວກັບເຄື່ອງພິມທົ່ວໄປ);
// ບໍ່ດັ່ງນັ້ນ → LAN → ໜ້າຕ່າງພິມ. ຕົວເຊື່ອມຕໍ່ Bluetooth ຢູ່ໜ້າຫຼັກ (home/dashboard.php)
function reportBtPrinter() {
  if (window.MiniposBtPrinter) return window.MiniposBtPrinter;
  try { return (window.top && window.top.MiniposBtPrinter) || (window.parent && window.parent.MiniposBtPrinter) || null; } catch (e) { return null; }
}
function reprintReceiptAuto() {
  var bt = reportBtPrinter();
  var btCfg = {};
  try { btCfg = JSON.parse(localStorage.getItem('StoreMini.bt_printer_settings') || '{}') || {}; } catch (e) {}
  var btOn = bt && bt.list && bt.list().length && (btCfg.enabled === undefined ? true : !!btCfg.enabled);
  if (btOn && window.MiniposLanReceipt) {
    var area = document.getElementById('reportReceiptPrintArea');
    var dots = parseInt(btCfg.paper, 10) === 58 ? 384 : 576;
    var layoutPx = MiniposLanReceipt.paperPx(80);
    var rendered = MiniposLanReceipt.toCanvas
      ? MiniposLanReceipt.toCanvas(area, layoutPx, '../../', REPORT_RCPT_EXTRA_CSS, dots)
      : MiniposLanReceipt.toImage(area, layoutPx, '../../', REPORT_RCPT_EXTRA_CSS);
    var conn = bt.isConnected() ? Promise.resolve(true) : bt.reconnect();
    conn.then(function (ok) {
      if (!ok) { reprintReceiptFallback(); return; }
      return rendered.then(function (img) {
        return bt.printImage(img, { dots: dots });
      }).then(function () {
        if (typeof Swal !== 'undefined') {
          Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: REPORT_LAN_MSG.sent + ' (Bluetooth)', showConfirmButton: false, timer: 1800 });
        }
      }).catch(function (err) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'ພິມຜ່ານ Bluetooth ບໍ່ສຳເລັດ — ພິມແບບປົກກະຕິແທນ', text: (err && err.message) || '', showConfirmButton: false, timer: 4000 });
        }
        reprintReceiptFallback();
      });
    });
    return;
  }
  reprintReceiptFallback();
}
function reprintReceiptFallback() {
  var rp = window.REPORT_RECEIPT_PRINTER;
  if (!rp || rp.printer_type !== 'ip' || !window.MiniposLanReceipt) {
    doPrintReportReceiptDialog();
    return;
  }
  MiniposLanReceipt.print(document.getElementById('reportReceiptPrintArea'), rp, { basePath: '../../', extraCss: REPORT_RCPT_EXTRA_CSS }).then(function (r) {
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

// Receipt number format: thousands commas + always 2 decimals ("148,000.00").
function repRcNum(v) {
  v = Math.round((parseFloat(v) || 0) * 100) / 100;
  return v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
// A KIP amount in currency `code` for the receipt; '-' if no rate. `rate` = the bill's own rate.
function repMoneyInCode(kip, code, rate) {
  kip = parseFloat(kip) || 0;
  var m = (REP_CUR.meta && REP_CUR.meta[code]) || { symbol: code, decimals: 0 };
  if (code === 'KIP') return repRcNum(kip) + ' ' + m.symbol;
  var r = parseFloat(rate) || parseFloat(REP_CUR.rates[code]) || 0;
  if (r <= 0) return '-';
  return repRcNum(kip / r) + ' ' + m.symbol;
}
// The reprint receipt's net-total currency block: only the currency the bill was paid in
// (same as the checkout receipt, renderReceiptMultiCurrency).
function fillReprintMultiCurrency(netKip, paidCode, paidRate) {
  paidCode = String(paidCode || 'KIP').toUpperCase();
  // No exchange rate set up → the shop only uses KIP: no block at all (same as checkout).
  var r = REP_CUR.rates || {};
  var hasRates = ['THB', 'USD', 'CNY'].some(function (c) { return parseFloat(r[c]) > 0; });
  $('#rc_rep_multicur').css('display', hasRates ? 'block' : 'none');
  if (!hasRates) return;
  ['KIP', 'THB', 'USD', 'CNY'].forEach(function (c) {
    var el = $('#rc_rep_total_' + c.toLowerCase());
    if (c !== paidCode) { el.parent().css('display', 'none'); return; }
    var txt = repMoneyInCode(netKip, c, paidRate);
    var i = txt.lastIndexOf(' ');
    el.html(txt === '-' || i < 0 ? txt : txt.slice(0, i) + ' <span class="cur-sym">' + txt.slice(i + 1) + '</span>');
    el.parent().css('display', 'flex');
  });
}
// n is a KIP amount -> formatted string in the report currency
function repMoneyFromKip(n) {
  n = parseFloat(n) || 0;
  var v = REP_CUR.isKip ? Math.round(n) : (n / REP_CUR.rate);
  return v.toLocaleString('en-US', { minimumFractionDigits: REP_CUR.decimals, maximumFractionDigits: REP_CUR.decimals }) + ' <span class="cur-sym">' + REP_CUR.symbol + '</span>';
}
// Reprinted receipt money — same rules as the checkout receipt: whole numbers
// (repMoneyFromKip), except VAT and a taxed bill's net total, which get 2 decimals.
function repReceiptMoneyExact(n) {
  n = parseFloat(n) || 0;
  var v = REP_CUR.isKip ? n : (n / REP_CUR.rate);
  return repRcNum(v) + ' <span class="cur-sym">' + REP_CUR.symbol + '</span>';
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
