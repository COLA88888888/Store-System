<?php
// ໃບສະຫຼຸບກະ ແບບເອກະສານ A4 — ໃຊ້ຮ່ວມກັນລະຫວ່າງປຸ່ມ "ພິມສະຫຼຸບ" ແລະ "PDF" ໃນ modal ລາຍງານກະ (modals.php).
// ສ່ວນຍ່ອຍຂອງ pages/settings/shifts/partials/js.php — ຖືກ require ພາຍໃນ <script> ຂອງໄຟລ໌ນັ້ນ,
// ໃຊ້ srT(), formatNumber(), window.currentReportSession / currentReportBills ທີ່ js.php ສ້າງໄວ້.
// ໜັງສືສີດຳທັງໝົດ, ພື້ນເທົາອ່ອນສະເພາະຫົວຕາຕະລາງ/ແຖວລວມ. CSS ຕ້ອງຢູ່ inline ໃນ string ນີ້: ເອກະສານພິມຢູ່ໃນ
// iframe ແຍກ ແລະ PDF ແຕ້ມຈາກ div ທີ່ບໍ່ຢູ່ໃນໜ້າ — stylesheet ພາຍນອກໂຫຼດບໍ່ທັນ. ທຸກ selector ຂຶ້ນຕົ້ນດ້ວຍ .sa4
// ເພື່ອບໍ່ໃຫ້ຮົ່ວອອກໄປໃສ່ໜ້າຫຼັກ ຕອນ html2pdf ແຕ້ມ.
$shA4Store = '';
try {
    if (isset($pdo)) {
        $shA4Stmt = $pdo->prepare("SELECT store_name FROM st_tbstore WHERE store_id = ?");
        $shA4Stmt->execute([getActiveStoreId($pdo)]);
        $shA4Store = (string)($shA4Stmt->fetchColumn() ?: '');
    }
} catch (Throwable $e) {}
$shA4PrintedBy = trim(($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? '')) ?: ($_SESSION['username'] ?? '');
?>
window.SHIFT_A4_STORE = <?php echo json_encode($shA4Store, JSON_UNESCAPED_UNICODE); ?>;
window.SHIFT_A4_PRINTED_BY = <?php echo json_encode($shA4PrintedBy, JSON_UNESCAPED_UNICODE); ?>;
window.SHIFT_A4_FONT = new URL('<?php echo ($base_path ?? "../../../") . 'assets/fonts/Phetsarath_OT.ttf'; ?>', window.location.href).href;

function shiftA4Esc(v) {
  return String(v === null || v === undefined ? '' : v).replace(/[&<>"']/g, function (c) {
    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
  });
}

function shiftA4NowText() {
  var pad = function (n) { return (n < 10 ? '0' : '') + n; };
  var d = new Date();
  return pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear() + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
}

// ຂໍ້ຄວາມສ່ວນຕ່າງເງິນສົດ: "-38,961 ₭ (ເງິນຂາດ)" / "+500 ₭ (ເງິນເກີນ)" / "0 ₭ (ດຸ່ນດ່ຽງ)"
function shiftA4DiffText(s) {
  if (s.difference_cash === null) return '-';
  var diff = parseFloat(s.difference_cash) || 0;
  if (diff > 0) return '+' + formatNumber(diff) + ' <?php echo kipSymbol(); ?> (' + srT('shifts.rpt_over') + ')';
  if (diff < 0) return formatNumber(diff) + ' <?php echo kipSymbol(); ?> (' + srT('shifts.rpt_short') + ')';
  return '0 <?php echo kipSymbol(); ?> (' + srT('shifts.rpt_balanced') + ')';
}

// ສ້າງ { title, css, body } ຂອງໃບສະຫຼຸບກະ A4 — body ຫໍ່ດ້ວຍ <div class="sa4">
function buildShiftReportA4(s, bills) {
  var esc = shiftA4Esc;
  var money = function (v) { return formatNumber(parseFloat(v) || 0) + ' <?php echo kipSymbol(); ?>'; };

  var billsTotal = 0;
  var billRows = bills.map(function (b, i) {
    billsTotal += parseFloat(b.sale_barlance) || 0;
    var bName = b.bank_display_name || b.bank_name || '';
    var isTrf = (b.type_pay && (b.type_pay.includes('ໂອນ') || b.type_pay.includes('QR'))) || (parseFloat(b.qr_received) > 0);
    var payText = esc(b.type_pay);
    if (isTrf && bName) {
      payText = esc(b.type_pay) + ' (' + esc(bName) + ')';
    }
    return '<tr><td class="c">' + (i + 1) + '</td><td>' + esc(b.sale_save_bill) + '</td>' +
      '<td class="c">' + esc(b.sale_date) + ' ' + esc(b.sale_time) + '</td><td>' + esc(b.user_receive) + '</td>' +
      '<td class="c">' + payText + '</td><td class="r">' + money(b.sale_barlance) + '</td></tr>';
  }).join('');
  if (!billRows) billRows = '<tr><td colspan="6" class="c">' + esc(srT('shifts.rpt_no_bills')) + '</td></tr>';

  // ສິນຄ້າທີ່ຂາຍໃນກະ (window.currentReportItems — get_session_details.items)
  var items = window.currentReportItems || [];
  var itTot = { qty: 0, discount: 0, net: 0 };
  var itemRows = items.map(function (it, i) {
    itTot.qty += it.qty; itTot.discount += it.discount; itTot.net += it.net;
    return '<tr><td class="c">' + (i + 1) + '</td><td>' + esc(it.product_name) + '</td>' +
      '<td class="c">' + formatNumber(it.qty) + '</td><td class="r">' + money(it.price) + '</td>' +
      '<td class="r">' + (it.discount ? money(it.discount) : '-') + '</td><td class="r">' + money(it.net) + '</td></tr>';
  }).join('');
  if (!itemRows) itemRows = '<tr><td colspan="6" class="c">' + esc(srT('shifts.rpt_no_items')) + '</td></tr>';

  var sumRow = function (label, value, cls) {
    return '<tr' + (cls ? ' class="' + cls + '"' : '') + '><td>' + esc(label) + '</td><td class="r">' + esc(value) + '</td></tr>';
  };

  var css =
    '@font-face{font-family:"Phetsarath OT";src:url("' + window.SHIFT_A4_FONT + '") format("truetype");}' +
    '@page{size:A4 portrait;margin:12mm 12mm 14mm;}' +
    '.sa4,.sa4 *{box-sizing:border-box;font-family:"Phetsarath OT","Noto Sans Lao",sans-serif;color:#000;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
    '.sa4{font-size:10.5pt;line-height:1.45;background:#fff;}' +
    '.sa4 .head{text-align:center;border-bottom:2px solid #000;padding-bottom:8px;margin-bottom:12px;}' +
    '.sa4 .store{font-size:11.5pt;font-weight:bold;}' +
    '.sa4 h1{font-size:17pt;font-weight:bold;margin:2px 0 3px;}' +
    '.sa4 .sub{font-size:10.5pt;}' +
    '.sa4 table{width:100%;border-collapse:collapse;}' +
    '.sa4 th,.sa4 td{border:1px solid #000;padding:5px 8px;vertical-align:middle;}' +
    '.sa4 .info td.k{background:#f2f2f2;font-weight:bold;width:17%;}.sa4 .info td.v{width:33%;}' +
    '.sa4 .sec{margin:14px 0 6px;padding-left:8px;border-left:4px solid #000;font-weight:bold;font-size:11pt;}' +
    '.sa4 .sum td:first-child{width:62%;}.sa4 .sum td.r{font-weight:bold;white-space:nowrap;}' +
    '.sa4 .sum tr.total td,.sa4 .sum tr.diff td{background:#e7e6e6;font-weight:bold;font-size:11pt;}' +
    '.sa4 .sum tr.counted td{background:#f2f2f2;font-weight:bold;}' +
    '.sa4 .notes{margin-top:10px;border:1px dashed #000;padding:6px 10px;}' +
    '.sa4 .bills th{background:#e7e6e6;font-weight:bold;text-align:center;}' +
    '.sa4 .bills tbody tr:nth-child(even) td{background:#f7f7f7;}' +
    '.sa4 .bills tfoot td{background:#e7e6e6;font-weight:bold;}' +
    '.sa4 thead{display:table-header-group;}.sa4 tr{page-break-inside:avoid;break-inside:avoid;}' +
    '.sa4 .c{text-align:center;}.sa4 .r{text-align:right;}' +
    '.sa4 table.plain td{border:0;padding:0;}' +
    '.sa4 .signs{margin-top:34px;page-break-inside:avoid;break-inside:avoid;}' +
    '.sa4 .signs td{width:50%;text-align:center;}.sa4 .signs .line{border-bottom:1px dotted #000;height:48px;margin:0 30px 4px;}' +
    '.sa4 .small{font-size:9.5pt;}' +
    '.sa4 .foot{margin-top:22px;border-top:1px solid #000;}.sa4 .foot td{padding-top:5px;font-size:9.5pt;}';

  var body = '<div class="sa4">' +
    '<div class="head">' +
      ((s.store_name || window.SHIFT_A4_STORE) ? '<div class="store">' + esc(s.store_name || window.SHIFT_A4_STORE) + '</div>' : '') +
      '<h1>' + esc(srT('shifts.rpt_title')) + '</h1>' +
      '<div class="sub">' + (s.store_name ? '<b>' + esc(srT('shifts.rpt_branch')) + '</b> ' + esc(s.store_name) + ' &nbsp;|&nbsp; ' : '') +
        '<b>' + esc(srT('shifts.rpt_shift')) + '</b> ' + esc(formatShiftName(s.shift_name)) + ' &nbsp;|&nbsp; <b>' + esc(srT('shifts.rpt_status')) + '</b> ' +
        esc(s.status === 'open' ? srT('shifts.rpt_status_open') : srT('shifts.rpt_status_closed')) + '</div>' +
    '</div>' +

    '<table class="info">' +
      '<tr><td class="k">' + esc(srT('shifts.rpt_open_time')) + '</td><td class="v">' + esc(s.open_time) + '</td>' +
      '<td class="k">' + esc(srT('shifts.rpt_close_time')) + '</td><td class="v">' + esc(s.close_time || srT('shifts.rpt_not_closed')) + '</td></tr>' +
      '<tr><td class="k">' + esc(srT('shifts.rpt_opened_by')) + '</td><td class="v">' + esc(s.opened_by_name) + '</td>' +
      '<td class="k">' + esc(srT('shifts.rpt_closed_by')) + '</td><td class="v">' + esc(shiftCloserName(s.closed_by_name)) + '</td></tr>' +
    '</table>' +

    '<div class="sec">' + esc(srT('shifts.rpt_summary_title')) + '</div>' +
    '<table class="sum">' +
      sumRow(srT('shifts.rpt_initial_cash'), money(s.initial_cash)) +
      sumRow(srT('shifts.rpt_cash_sales'), money(s.system_sales_cash)) +
      sumRow(srT('shifts.rpt_transfer_sales'), money(s.system_sales_transfer)) +
      sumRow(srT('shifts.rpt_credit_sales'), money(s.system_sales_debt)) +
      sumRow(srT('shifts.rpt_total_sales', formatNumber(s.system_total_bills)), money(s.system_total_sales), 'total') +
      sumRow(srT('shifts.rpt_counted_cash'), s.counted_sales_cash !== null ? money(s.counted_sales_cash) : '-', 'counted') +
      (s.diff_hidden ? '' : sumRow(srT('shifts.rpt_diff'), shiftA4DiffText(s), 'diff')) +
    '</table>' +
    (s.notes ? '<div class="notes"><b>' + esc(srT('shifts.rpt_notes')) + '</b> ' + esc(s.notes) + '</div>' : '') +

    '<div class="sec">' + esc(srT('shifts.rpt_items_title', items.length)) + '</div>' +
    '<table class="bills"><thead><tr>' +
      '<th style="width:36px;">#</th><th>' + esc(srT('shifts.rpt_col_product')) + '</th><th style="width:70px;">' + esc(srT('shifts.rpt_col_qty')) + '</th>' +
      '<th>' + esc(srT('shifts.rpt_col_price')) + '</th><th>' + esc(srT('shifts.rpt_col_discount')) + '</th><th>' + esc(srT('shifts.rpt_col_total')) + '</th>' +
    '</tr></thead><tbody>' + itemRows + '</tbody>' +
    (items.length ? '<tfoot><tr><td colspan="2" class="r">' + esc(srT('shifts.rpt_items_total')) + '</td><td class="c">' + formatNumber(itTot.qty) + '</td><td></td>' +
      '<td class="r">' + (itTot.discount ? money(itTot.discount) : '-') + '</td><td class="r">' + money(itTot.net) + '</td></tr></tfoot>' : '') +
    '</table>' +

    '<div class="sec">' + esc(srT('shifts.rpt_bills_title', bills.length)) + '</div>' +
    '<table class="bills"><thead><tr>' +
      '<th style="width:36px;">#</th><th>' + esc(srT('shifts.rpt_col_bill')) + '</th><th>' + esc(srT('shifts.rpt_col_datetime')) + '</th>' +
      '<th>' + esc(srT('shifts.rpt_col_cashier')) + '</th><th>' + esc(srT('shifts.rpt_col_channel')) + '</th><th>' + esc(srT('shifts.rpt_col_amount')) + '</th>' +
    '</tr></thead><tbody>' + billRows + '</tbody>' +
    (bills.length ? '<tfoot><tr><td colspan="5" class="r">' + esc(srT('shifts.rpt_a4_bills_total')) + '</td><td class="r">' + money(billsTotal) + '</td></tr></tfoot>' : '') +
    '</table>' +

    '<table class="plain signs"><tr>' +
      '<td><div class="line"></div><b>' + esc(srT('shifts.rpt_a4_sign_closer')) + '</b><br><span class="small">' + esc(srT('shifts.rpt_a4_sign_line')) + '</span></td>' +
      '<td><div class="line"></div><b>' + esc(srT('shifts.rpt_a4_sign_checker')) + '</b><br><span class="small">' + esc(srT('shifts.rpt_a4_sign_line')) + '</span></td>' +
    '</tr></table>' +
    '<table class="plain foot"><tr>' +
      '<td>' + esc(srT('shifts.rpt_a4_printed_by')) + ' ' + esc(window.SHIFT_A4_PRINTED_BY) + '</td>' +
      '<td class="r">' + esc(srT('shifts.rpt_printed_at')) + ' ' + shiftA4NowText() + '</td>' +
    '</tr></table>' +
  '</div>';

  return { title: srT('shifts.rpt_title') + ' - ' + s.shift_name, css: css, body: body };
}

function shiftA4FileBase() {
  var s = window.currentReportSession || {};
  return 'shift_report_' + (s.shift_name || 'shift') + '_' + new Date().toISOString().slice(0, 10);
}

function printShiftReportA4() {
  if (!window.currentReportSession) return;
  var a4 = buildShiftReportA4(window.currentReportSession, window.currentReportBills || []);
  var html = '<!doctype html><html><head><meta charset="utf-8"><title>' + shiftA4Esc(a4.title) + '</title>' +
    '<style>body{margin:0;background:#fff;}' + a4.css + '</style></head><body>' + a4.body + '</body></html>';

  var f = document.getElementById('shiftA4PrintFrame');
  if (f) f.remove();
  f = document.createElement('iframe');
  f.id = 'shiftA4PrintFrame';
  f.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
  document.body.appendChild(f);
  var doc = f.contentWindow.document;
  doc.open();
  doc.write(html);
  doc.close();

  // ລໍຟອນ Phetsarath OT ໂຫຼດກ່ອນພິມ (ສູງສຸດ 2 ວິນາທີ) — ບໍ່ດັ່ງນັ້ນໜ້າທຳອິດອອກເປັນຟອນສຳຮອງ
  var printed = false, go = function () { if (printed) return; printed = true; f.contentWindow.focus(); f.contentWindow.print(); };
  try {
    if (doc.fonts && doc.fonts.load) {
      Promise.all([doc.fonts.load('12pt "Phetsarath OT"'), doc.fonts.load('bold 12pt "Phetsarath OT"')])
        .then(function () { setTimeout(go, 100); }, function () { setTimeout(go, 100); });
    }
  } catch (e) {}
  setTimeout(go, 2000);
}

// PDF: ແຕ້ມເອກະສານ A4 ດຽວກັນກັບການພິມ ດ້ວຍ html2pdf (ໂຫຼດຢູ່ຫົວ js.php). ຟອນຕ້ອງຢູ່ໃນ document.fonts
// ຂອງໜ້ານີ້ກ່ອນ ເພາະ html2canvas ແຕ້ມຈາກໜ້າຫຼັກ ບໍ່ດັ່ງນັ້ນອອກເປັນຟອນສຳຮອງ
var shiftA4FontPromise = null;
function shiftA4LoadFont() {
  try {
    if (!document.fonts || typeof FontFace === 'undefined') return Promise.resolve();
    if (!shiftA4FontPromise) {
      shiftA4FontPromise = new FontFace('Phetsarath OT', 'url("' + window.SHIFT_A4_FONT + '")').load()
        .then(function (f) { document.fonts.add(f); }).catch(function () {});
    }
    return shiftA4FontPromise;
  } catch (e) { return Promise.resolve(); }
}

function exportShiftReportPDF() {
  if (!window.currentReportSession) return;
  if (typeof window.html2pdf === 'undefined') { printShiftReportA4(); return; }   // ບໍ່ມີອິນເຕີເນັດ → ພິມແທນ
  var a4 = buildShiftReportA4(window.currentReportSession, window.currentReportBills || []);
  var wrap = document.createElement('div');
  wrap.innerHTML = '<style>' + a4.css.replace(/@page\{[^}]*\}/, '') + '</style>' + a4.body;
  shiftA4LoadFont().then(function () {
    window.html2pdf().set({
      margin: [12, 12, 14, 12],
      filename: shiftA4FileBase() + '.pdf',
      image: { type: 'jpeg', quality: 0.98 },
      html2canvas: { scale: 2, useCORS: true },
      jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
      pagebreak: { mode: ['css', 'legacy'], avoid: 'tr' }
    }).from(wrap).save();
  });
}
