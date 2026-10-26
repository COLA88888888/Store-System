<?php
// Export Excel ໃບສະຫຼຸບກະ (ປຸ່ມ "Excel" ໃນ modal ລາຍງານກະ — modals.php).
// ສ່ວນຍ່ອຍຂອງ pages/settings/shifts/partials/js.php — ຖືກ require ພາຍໃນ <script> ຫຼັງ js/print_a4.php
// (ໃຊ້ shiftA4Esc, shiftA4DiffText, shiftA4NowText, shiftA4FileBase, window.SHIFT_A4_* ຈາກໄຟລ໌ນັ້ນ).
// ຕາຕະລາງດຽວ 6 ຖັນ: ໜັງສືສີດຳ Phetsarath OT, ຂອບທຸກຊ່ອງ, ພື້ນເທົາອ່ອນສະເພາະຫົວຕາຕະລາງ/ແຖວລວມ.
// ກຳນົດຄວາມສູງແຖວເອງ — ຄວາມສູງຕັ້ງຕົ້ນຂອງ Excel ຕັດສະຫຼະລາວ. ຈຳນວນເງິນເປັນຕົວເລກແທ້ (#,##0) ຈຶ່ງບວກຕໍ່ໄດ້;
// ເລກບິນ/ວັນທີ ບັງຄັບເປັນຂໍ້ຄວາມ ບໍ່ດັ່ງນັ້ນ Excel ປ່ຽນຮູບແບບເອງ.
?>
function exportShiftReportExcel() {
  if (!window.currentReportSession) return;
  var s = window.currentReportSession;
  var bills = window.currentReportBills || [];
  var esc = shiftA4Esc;

  var F = 'font-family:\'Phetsarath OT\';color:#000;font-size:11pt;vertical-align:middle;';
  var B = 'border:.5pt solid #000;';
  var TXT = 'mso-number-format:\'\\@\';';
  var NUM = 'mso-number-format:\'\\#\\,\\#\\#0\';';
  var GREY = 'background:#E7E6E6;', LIGHT = 'background:#F2F2F2;';
  var row = function (h, cells) { return '<tr style="height:' + h + 'pt;mso-height-source:userset;">' + cells + '</tr>'; };
  var td = function (txt, style, span) {
    return '<td' + (span > 1 ? ' colspan="' + span + '"' : '') + ' style="' + F + (style || '') + '">' + txt + '</td>';
  };
  var blank = function (h) { return row(h, '<td colspan="6"></td>'); };
  var num = function (v) { return parseFloat(v) || 0; };

  // ຖັນ: # | ເລກບິນ | ວັນທີ-ເວລາ | ພະນັກງານຂາຍ | ຊ່ອງທາງ | ມູນຄ່າບິນ (px)
  var widths = [44, 170, 165, 150, 120, 150];
  var html = '<table style="border-collapse:collapse;table-layout:fixed;">' + '<colgroup>' + widths.map(function (px) {
    return '<col width="' + px + '" style="width:' + Math.round(px * 0.75) + 'pt;mso-width-source:userset;">';
  }).join('') + '</colgroup>';

  // ຫົວລາຍງານ
  html += row(34, td(esc(srT('shifts.rpt_title')), 'font-size:16pt;font-weight:bold;text-align:center;', 6));
  var store = s.store_name || window.SHIFT_A4_STORE;
  if (store) html += row(24, td(esc(store), 'font-size:12pt;font-weight:bold;text-align:center;', 6));
  html += row(22, td(esc((s.store_name ? srT('shifts.rpt_branch') + ' ' + s.store_name + '   |   ' : '') + srT('shifts.rpt_shift') + ' ' + s.shift_name + '   |   ' + srT('shifts.rpt_status') + ' ' +
    (s.status === 'open' ? srT('shifts.rpt_status_open') : srT('shifts.rpt_status_closed'))), 'text-align:center;', 6));
  html += blank(8);

  // ຂໍ້ມູນກະ: ຫົວຂໍ້ (ຖັນ 1-2) ຄ່າ (ຖັນ 3) | ຫົວຂໍ້ (ຖັນ 4) ຄ່າ (ຖັນ 5-6)
  var info = function (k1, v1, k2, v2) {
    return row(24, td(esc(k1), B + LIGHT + 'font-weight:bold;', 2) + td(esc(v1), B + TXT) +
      td(esc(k2), B + LIGHT + 'font-weight:bold;') + td(esc(v2), B + TXT, 2));
  };
  html += info(srT('shifts.rpt_open_time'), s.open_time, srT('shifts.rpt_close_time'), s.close_time || srT('shifts.rpt_not_closed'));
  html += info(srT('shifts.rpt_opened_by'), s.opened_by_name, srT('shifts.rpt_closed_by'), s.closed_by_name || '-');
  html += blank(10);

  // ສະຫຼຸບລາຍຮັບ: ຫົວຂໍ້ (ຖັນ 1-4) ຄ່າ (ຖັນ 5-6)
  html += row(26, td(esc(srT('shifts.rpt_summary_title')), 'font-size:12pt;font-weight:bold;', 6));
  var sum = function (label, value, isNum, extra) {
    return row(24, td(esc(label), B + (extra || ''), 4) +
      td(isNum ? value : esc(value), B + (extra || '') + 'font-weight:bold;text-align:right;' + (isNum ? NUM : ''), 2));
  };
  html += sum(srT('shifts.rpt_initial_cash'), num(s.initial_cash), true);
  html += sum(srT('shifts.rpt_cash_sales'), num(s.system_sales_cash), true);
  html += sum(srT('shifts.rpt_transfer_sales'), num(s.system_sales_transfer), true);
  html += sum(srT('shifts.rpt_credit_sales'), num(s.system_sales_debt), true);
  html += sum(srT('shifts.rpt_total_sales', formatNumber(s.system_total_bills)), num(s.system_total_sales), true, GREY + 'font-weight:bold;');
  html += s.counted_sales_cash !== null
    ? sum(srT('shifts.rpt_counted_cash'), num(s.counted_sales_cash), true, LIGHT + 'font-weight:bold;')
    : sum(srT('shifts.rpt_counted_cash'), '-', false, LIGHT + 'font-weight:bold;');
  if (!s.diff_hidden) html += sum(srT('shifts.rpt_diff'), shiftA4DiffText(s), false, GREY + 'font-weight:bold;');
  if (s.notes) html += row(24, td('<b>' + esc(srT('shifts.rpt_notes')) + '</b> ' + esc(s.notes), B, 6));
  html += blank(10);

  // ສິນຄ້າທີ່ຂາຍໃນກະ (window.currentReportItems): # | ສິນຄ້າ (ຖັນ 2-3) | ຈຳນວນ | ສ່ວນຫຼຸດ | ລວມ
  var items = window.currentReportItems || [];
  html += row(26, td(esc(srT('shifts.rpt_items_title', items.length)), 'font-size:12pt;font-weight:bold;', 6));
  html += row(28, td('#', B + GREY + 'font-weight:bold;text-align:center;') +
    td(esc(srT('shifts.rpt_col_product')), B + GREY + 'font-weight:bold;text-align:center;', 2) +
    [srT('shifts.rpt_col_qty'), srT('shifts.rpt_col_discount') + ' (₭)', srT('shifts.rpt_col_total') + ' (₭)'].map(function (h) {
      return td(esc(h), B + GREY + 'font-weight:bold;text-align:center;');
    }).join(''));
  var itTot = { qty: 0, discount: 0, net: 0 };
  items.forEach(function (it, i) {
    var z = i % 2 === 1 ? 'background:#F7F7F7;' : '';
    itTot.qty += it.qty; itTot.discount += it.discount; itTot.net += it.net;
    html += row(22, td(i + 1, B + z + 'text-align:center;') + td(esc(it.product_name), B + z, 2) +
      td(it.qty, B + z + NUM + 'text-align:center;') + td(it.discount, B + z + NUM + 'text-align:right;') +
      td(it.net, B + z + NUM + 'text-align:right;'));
  });
  if (!items.length) {
    html += row(24, td(esc(srT('shifts.rpt_no_items')), B + 'text-align:center;', 6));
  } else {
    html += row(26, td(esc(srT('shifts.rpt_items_total')), B + GREY + 'font-weight:bold;text-align:right;', 3) +
      td(itTot.qty, B + GREY + NUM + 'font-weight:bold;text-align:center;') +
      td(itTot.discount, B + GREY + NUM + 'font-weight:bold;text-align:right;') +
      td(itTot.net, B + GREY + NUM + 'font-weight:bold;text-align:right;'));
  }
  html += blank(10);

  // ລາຍການບິນ
  html += row(26, td(esc(srT('shifts.rpt_bills_title', bills.length)), 'font-size:12pt;font-weight:bold;', 6));
  html += row(28, ['#', srT('shifts.rpt_col_bill'), srT('shifts.rpt_col_datetime'), srT('shifts.rpt_col_cashier'),
    srT('shifts.rpt_col_channel'), srT('shifts.rpt_col_amount') + ' (₭)'].map(function (h) {
    return td(esc(h), B + GREY + 'font-weight:bold;text-align:center;');
  }).join(''));
  var total = 0;
  bills.forEach(function (b, i) {
    var z = i % 2 === 1 ? 'background:#F7F7F7;' : '';
    total += num(b.sale_barlance);
    var bName = b.bank_display_name || b.bank_name || '';
    var isTrf = (b.type_pay && (b.type_pay.includes('ໂອນ') || b.type_pay.includes('QR'))) || (parseFloat(b.qr_received) > 0);
    var payText = b.type_pay + (isTrf && bName ? ' (' + bName + ')' : '');
    html += row(22, td(i + 1, B + z + 'text-align:center;') + td(esc(b.sale_save_bill), B + z + TXT) +
      td(esc(b.sale_date + ' ' + b.sale_time), B + z + TXT + 'text-align:center;') + td(esc(b.user_receive), B + z) +
      td(esc(payText), B + z + 'text-align:center;') + td(num(b.sale_barlance), B + z + NUM + 'text-align:right;'));
  });
  if (!bills.length) {
    html += row(24, td(esc(srT('shifts.rpt_no_bills')), B + 'text-align:center;', 6));
  } else {
    html += row(26, td(esc(srT('shifts.rpt_a4_bills_total')), B + GREY + 'font-weight:bold;text-align:right;', 5) +
      td(total, B + GREY + NUM + 'font-weight:bold;text-align:right;'));
  }

  // ລາຍເຊັນ ແລະ ຜູ້ພິມ
  html += blank(40);
  html += row(22, td('.......................................', 'text-align:center;', 3) + td('.......................................', 'text-align:center;', 3));
  html += row(22, td(esc(srT('shifts.rpt_a4_sign_closer')), 'font-weight:bold;text-align:center;', 3) +
    td(esc(srT('shifts.rpt_a4_sign_checker')), 'font-weight:bold;text-align:center;', 3));
  html += row(20, td(esc(srT('shifts.rpt_a4_sign_line')), 'font-size:10pt;text-align:center;', 3) +
    td(esc(srT('shifts.rpt_a4_sign_line')), 'font-size:10pt;text-align:center;', 3));
  html += blank(14);
  html += row(22, td(esc(srT('shifts.rpt_a4_printed_by') + ' ' + window.SHIFT_A4_PRINTED_BY + '   |   ' +
    srT('shifts.rpt_printed_at') + ' ' + shiftA4NowText()), 'font-size:10pt;', 6));
  html += '</table>';

  var sheet = esc(String(s.shift_name || 'Shift').replace(/[\\\/\?\*\[\]:]/g, ' ').slice(0, 31));
  var doc = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">' +
    '<head><meta charset="utf-8">' +
    '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>' + sheet + '</x:Name>' +
    '<x:WorksheetOptions><x:DoNotDisplayGridlines/><x:FitToPage/><x:Print><x:FitWidth>1</x:FitWidth><x:FitHeight>0</x:FitHeight>' +
    '<x:ValidPrinterInfo/><x:PaperSizeIndex>9</x:PaperSizeIndex></x:Print></x:WorksheetOptions>' +
    '</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->' +
    '<style>td{font-family:"Phetsarath OT";font-size:11pt;color:#000;mso-font-charset:0;} ' +
    '@page{mso-page-orientation:portrait;margin:.5in .4in .5in .4in;}</style></head><body>' + html + '</body></html>';

  var blob = new Blob(['\ufeff' + doc], { type: 'application/vnd.ms-excel;charset=utf-8' });
  var url = URL.createObjectURL(blob);
  var a = document.createElement('a');
  a.href = url;
  a.download = shiftA4FileBase() + '.xls';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
}
