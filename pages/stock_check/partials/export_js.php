<?php
/*
 * ສ່ວນ: Excel / PDF / ພິມ ສຳລັບທຸກຕາຕະລາງໃນໂມດູນເຊັກສະຕັອກ
 * ສ້າງເປັນຕາຕະລາງລາຍງານທຳມະດາ (ຫົວລາຍງານ + ຕາຕະລາງມີຂອບ + ແຖວລວມ) ຈາກຂໍ້ມູນ ບໍ່ແມ່ນຈາກໜ້າຈໍ,
 * ສະນັ້ນ ໄດ້ຂໍ້ມູນຄົບທຸກໜ້າ ແລະ ບໍ່ມີປຸ່ມ/ຊ່ອງປ້ອນຕິດໄປນຳ.
 * ແຕ່ລະໜ້າກຳນົດ scGetReport() ທີ່ສົ່ງຄືນ { title, fileBase, meta: [..], headers: [{t, a}], rows: [[..]], footer: [..]|null }
 * (a = 'l' | 'c' | 'r' ຈັດຊ້າຍ/ກາງ/ຂວາ). ຖືກ require ຈາກ pages/stock_check/stock_check.php ຫຼັງ footer.
 */
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
var SC_STORE_NAME = <?php echo json_encode($scStoreName, JSON_UNESCAPED_UNICODE); ?>;
var SC_PRINTED_BY = <?php echo json_encode(trim(($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? '')) ?: ($_SESSION['username'] ?? ''), JSON_UNESCAPED_UNICODE); ?>;

function scEsc(s) { return $('<div>').text(s === null || s === undefined ? '' : String(s)).html(); }

// ຟອນລາຍງານ: Phetsarath OT — ເນື້ອໃນ 12pt, ຫົວຂໍ້ (ຊື່ລາຍງານ + ຫົວຖັນ) 14pt ໜາ.
// ໄຟລ໌ຟອນຢູ່ໃນ assets/fonts (ໃຊ້ URL ເຕັມ ເພື່ອໃຫ້ iframe ພິມ ແລະ PDF ໂຫຼດໄດ້);
// Excel ໃຊ້ແຕ່ຊື່ຟອນ — ເຄື່ອງທີ່ເປີດໄຟລ໌ຕ້ອງມີ Phetsarath OT.
var SC_FONT_URL = new URL('../../assets/fonts/Phetsarath_OT.ttf', window.location.href).href;
var SC_FONT = '"Phetsarath OT","Noto Sans Lao",sans-serif';

function scReportCss(forExcel) {
  return (forExcel ? '' : '@font-face{font-family:"Phetsarath OT";src:url("' + SC_FONT_URL + '") format("truetype");}') +
    '*{font-family:' + SC_FONT + ';}' +
    'body{margin:0;padding:16px;color:#000;font-size:12pt;}' +
    'h2{text-align:center;font-size:14pt;font-weight:bold;margin:0 0 4px;}' +
    '.rp-store{text-align:center;font-size:12pt;font-weight:bold;margin-bottom:4px;}' +
    '.rp-meta{text-align:center;font-size:12pt;margin-bottom:10px;}' +
    'table{border-collapse:collapse;width:100%;font-size:12pt;}' +
    'th,td{border:1px solid #555;padding:5px 7px;vertical-align:middle;font-size:12pt;}' +
    'th{background:#eee;font-size:14pt;font-weight:bold;text-align:center;}' +
    'tfoot td{font-weight:bold;background:#f5f5f5;}' +
    '.a-r{text-align:right;}.a-c{text-align:center;}.a-l{text-align:left;}' +
    '.rp-foot{display:flex;justify-content:space-between;margin-top:14px;font-size:12pt;}' +
    '@page{size:A4 landscape;margin:10mm;}';
}

// ລໍຖ້າໃຫ້ຟອນໂຫຼດກ່ອນ ເພື່ອບໍ່ໃຫ້ PDF/ພິມ ອອກມາເປັນຟອນສຳຮອງ
function scLoadFont() {
  try {
    if (!document.fonts || typeof FontFace === 'undefined') return Promise.resolve();
    if (!window.scFontPromise) {
      var ff = new FontFace('Phetsarath OT', 'url("' + SC_FONT_URL + '")');
      window.scFontPromise = ff.load().then(function(f) { document.fonts.add(f); }).catch(function() {});
    }
    return window.scFontPromise;
  } catch (e) { return Promise.resolve(); }
}

// ຕາຕະລາງລາຍງານແບບທົ່ວໄປ (ໃຊ້ຮ່ວມກັນທັງ Excel, PDF ແລະ ພິມ)
function scReportBody(r) {
  var cls = function(h) { return 'a-' + (h && h.a ? h.a : 'l'); };
  var html = '<h2>' + scEsc(r.title) + '</h2>';
  if (SC_STORE_NAME) html += '<div class="rp-store">' + scEsc(SC_STORE_NAME) + '</div>';
  if (r.meta && r.meta.length) html += '<div class="rp-meta">' + r.meta.map(scEsc).join(' &nbsp;|&nbsp; ') + '</div>';
  html += '<table><thead><tr>' + r.headers.map(function(h) { return '<th>' + scEsc(h.t) + '</th>'; }).join('') + '</tr></thead><tbody>';
  if (!r.rows.length) {
    html += '<tr><td class="a-c" colspan="' + r.headers.length + '">ບໍ່ມີຂໍ້ມູນ</td></tr>';
  }
  r.rows.forEach(function(row) {
    html += '<tr>' + row.map(function(c, i) { return '<td class="' + cls(r.headers[i]) + '">' + scEsc(c) + '</td>'; }).join('') + '</tr>';
  });
  html += '</tbody>';
  if (r.footer) {
    html += '<tfoot><tr>' + r.footer.map(function(c, i) { return '<td class="' + cls(r.headers[i]) + '">' + scEsc(c) + '</td>'; }).join('') + '</tr></tfoot>';
  }
  html += '</table>';
  var d = new Date(), p = function(n) { return (n < 10 ? '0' : '') + n; };
  html += '<div class="rp-foot"><span>ພິມໂດຍ: ' + scEsc(SC_PRINTED_BY) + '</span><span>ວັນທີພິມ: ' +
          p(d.getDate()) + '/' + p(d.getMonth() + 1) + '/' + d.getFullYear() + ' ' + p(d.getHours()) + ':' + p(d.getMinutes()) + '</span></div>';
  return html;
}

function scFileName(r, ext) { return (r.fileBase || 'stock_check') + '_' + new Date().toISOString().slice(0, 10) + '.' + ext; }

// Excel: ທຸກຢ່າງຢູ່ໃນຕາຕະລາງດຽວ (ຊື່ລາຍງານ/ສາຂາ/ວັນທີ ເປັນແຖວລວມເຊວ) ແລະ ກຳນົດຄວາມສູງແຖວເອງ —
// Phetsarath OT ສູງ, ຄວາມສູງແຖວຕັ້ງຕົ້ນຂອງ Excel ຕັດຕົວອັກສອນລາວ. ລະຫັດທີ່ເປັນຕົວເລກຍາວ (ບາໂຄ້ດ)
// ບັງຄັບເປັນຂໍ້ຄວາມ (mso-number-format:\@) ບໍ່ດັ່ງນັ້ນ Excel ສະແດງເປັນ 8.85012E+12.
function scExcelTable(r) {
  var n = r.headers.length;
  var F = 'font-family:\'Phetsarath OT\';';
  var TXT = 'mso-number-format:\'\\@\';';
  var B = 'border:.5pt solid #555;';
  var al = function(h) { return 'text-align:' + ({ r: 'right', c: 'center' }[h && h.a] || 'left') + ';'; };
  var isCode = function(v) { return /^\d{8,}$/.test(v) || /^0\d+$/.test(v); };
  var row = function(h, cells) { return '<tr style="height:' + h + 'pt">' + cells + '</tr>'; };
  var full = function(txt, size, bold, h) {
    return row(h, '<td colspan="' + n + '" style="' + F + 'font-size:' + size + 'pt;' + (bold ? 'font-weight:bold;' : '') + 'text-align:center;vertical-align:middle;">' + scEsc(txt) + '</td>');
  };
  var d = new Date(), p = function(x) { return (x < 10 ? '0' : '') + x; };

  var html = '<table style="border-collapse:collapse;">';
  html += full(r.title, 14, true, 30);
  if (SC_STORE_NAME) html += full(SC_STORE_NAME, 12, true, 24);
  if (r.meta && r.meta.length) html += full(r.meta.join('  |  '), 12, false, 24);
  html += row(10, '<td colspan="' + n + '"></td>');
  html += row(30, r.headers.map(function(h) {
    return '<td style="' + F + B + 'font-size:14pt;font-weight:bold;text-align:center;vertical-align:middle;background:#e5e7eb;">' + scEsc(h.t) + '</td>';
  }).join(''));
  if (!r.rows.length) {
    html += row(24, '<td colspan="' + n + '" style="' + F + B + 'font-size:12pt;text-align:center;">ບໍ່ມີຂໍ້ມູນ</td>');
  }
  r.rows.forEach(function(cells) {
    html += row(24, cells.map(function(c, i) {
      var v = c === null || c === undefined ? '' : String(c);
      return '<td style="' + F + B + 'font-size:12pt;vertical-align:middle;' + al(r.headers[i]) + (isCode(v) ? TXT : '') + '">' + scEsc(v) + '</td>';
    }).join(''));
  });
  if (r.footer) {
    html += row(26, r.footer.map(function(c, i) {
      return '<td style="' + F + B + 'font-size:12pt;font-weight:bold;vertical-align:middle;background:#f3f4f6;' + al(r.headers[i]) + '">' + scEsc(c) + '</td>';
    }).join(''));
  }
  html += row(10, '<td colspan="' + n + '"></td>');
  html += row(22, '<td colspan="' + n + '" style="' + F + 'font-size:12pt;">ພິມໂດຍ: ' + scEsc(SC_PRINTED_BY) + '   |   ວັນທີພິມ: ' +
          p(d.getDate()) + '/' + p(d.getMonth() + 1) + '/' + d.getFullYear() + ' ' + p(d.getHours()) + ':' + p(d.getMinutes()) + '</td>');
  return html + '</table>';
}

function scExportExcel() {
  var r = scGetReport();
  var sheet = scEsc(String(r.title || 'Sheet1').replace(/[\\\/\?\*\[\]:]/g, ' ').slice(0, 31));
  var html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">' +
             '<head><meta charset="utf-8">' +
             '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>' + sheet + '</x:Name>' +
             '<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->' +
             '<style>td{font-family:"Phetsarath OT";font-size:12pt;mso-font-charset:0;}</style></head><body>' + scExcelTable(r) + '</body></html>';
  var blob = new Blob(['﻿' + html], { type: 'application/vnd.ms-excel;charset=utf-8' });
  var url = URL.createObjectURL(blob), a = document.createElement('a');
  a.href = url; a.download = scFileName(r, 'xls');
  document.body.appendChild(a); a.click(); document.body.removeChild(a);
  setTimeout(function() { URL.revokeObjectURL(url); }, 1000);
}

function scExportPDF() {
  var r = scGetReport();
  if (typeof html2pdf === 'undefined') { scPrintReport(); return; }
  var wrap = document.createElement('div');
  wrap.innerHTML = '<style>' + scReportCss().replace('body{', '.rp-wrap{') + '</style><div class="rp-wrap">' + scReportBody(r) + '</div>';
  scLoadFont().then(function() {
    html2pdf().set({
      margin: 8, filename: scFileName(r, 'pdf'),
      image: { type: 'jpeg', quality: 0.98 },
      html2canvas: { scale: 2, useCORS: true },
      jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' },
      pagebreak: { mode: ['css', 'legacy'] }
    }).from(wrap).save();
  });
}

// ພິມ: ຕາຕະລາງລາຍງານທຳມະດາ ໃນ iframe ເຊື່ອງ (ບໍ່ພິມໜ້າຈໍທັງໜ້າ)
function scPrintReport() {
  var r = scGetReport();
  var f = document.getElementById('scPrintFrame');
  if (f) f.remove();
  f = document.createElement('iframe');
  f.id = 'scPrintFrame';
  f.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
  document.body.appendChild(f);
  var doc = f.contentWindow.document;
  doc.open();
  doc.write('<!doctype html><html><head><meta charset="utf-8"><title>' + scEsc(r.title) + '</title><style>' + scReportCss() + '</style></head><body>' + scReportBody(r) + '</body></html>');
  doc.close();
  // ພິມຫຼັງຟອນໃນ iframe ໂຫຼດແລ້ວ (ສູງສຸດ 2 ວິນາທີ)
  var printed = false, go = function() { if (printed) return; printed = true; f.contentWindow.focus(); f.contentWindow.print(); };
  try {
    if (doc.fonts && doc.fonts.load) {
      Promise.all([doc.fonts.load('12pt "Phetsarath OT"'), doc.fonts.load('bold 14pt "Phetsarath OT"')])
        .then(function() { setTimeout(go, 100); }, function() { setTimeout(go, 100); });
    }
  } catch (e) {}
  setTimeout(go, 2000);
}
</script>
