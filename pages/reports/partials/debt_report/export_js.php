<?php
/*
 * ສ່ວນ: ພິມ / ອອກ Excel / ອອກ PDF ຂອງລາຍງານໜີ້ລູກຄ້າ
 * ປຸ່ມທັງສາມ ໂຫຼດໜ້າໃໝ່ດ້ວຍລາຍການທັງໝົດ (per_page=all) + print=1 ຫຼື export=excel|pdf, ແລ້ວໄຟລ໌ນີ້
 * ສັ່ງພິມ / ສ້າງໄຟລ໌ ຈາກຕາຕະລາງ .dbr-table ຕອນໜ້າໂຫຼດແລ້ວ.
 * Excel/PDF ສ້າງເອກະສານແຍກ ຈຶ່ງໃສ່ CSS ໄວ້ໃນນັ້ນເອງ (stylesheet ຂອງໜ້າບໍ່ໄປນຳ).
 * ໄຟລ໌ນີ້ຖືກ require ຈາກ pages/reports/debt_report.php ກ່ອນ layouts/footer.php
 * (ໃຊ້ $view, $views, $storeLabel, $dateFrom, $dateTo, $outFrom, $outTo ຂອງໜ້ານັ້ນ).
 */
$dbrAction = !empty($_GET['print']) ? 'print' : (in_array($_GET['export'] ?? '', ['excel', 'pdf'], true) ? $_GET['export'] : '');
if ($dbrAction === '') return;

$dbrRangeFrom = $view === 'outstanding' ? $outFrom : $dateFrom;
$dbrRangeTo   = $view === 'outstanding' ? $outTo : $dateTo;
$dbrSub = $storeLabel;
if ($dbrRangeFrom !== '' || $dbrRangeTo !== '') {
    $dbrSub .= ' · ' . ($dbrRangeFrom !== '' ? date('d/m/Y', strtotime($dbrRangeFrom)) : '…')
             . ' – ' . ($dbrRangeTo !== '' ? date('d/m/Y', strtotime($dbrRangeTo)) : '…');
}
$dbrExport = [
    'action' => $dbrAction,
    'title'  => t('debt_report.title', 'ລາຍງານໜີ້ລູກຄ້າ') . ' — ' . $views[$view],
    'sub'    => $dbrSub,
    'file'   => 'debt_report_' . $view . '_' . date('Y-m-d'),
];
?>
<?php if ($dbrAction === 'pdf'): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<?php endif; ?>
<script>
(function () {
  var EX = <?php echo json_encode($dbrExport, JSON_UNESCAPED_UNICODE); ?>;
  var FONT = "'Phetsarath OT', 'Noto Sans Lao', 'Souliyo', sans-serif";
  var FONT_NAME = "Phetsarath OT";

  // ສຳເນົາຕາຕະລາງ: ເອົາຮູບ/ລິ້ງ/ສ່ວນ no-print ອອກ, ປ້າຍສະຖານະເປັນຂໍ້ຄວາມທຳມະດາ
  function cleanTable() {
    var table = document.querySelector('.dbr-table');
    if (!table) return null;
    var t = table.cloneNode(true);
    t.querySelectorAll('.no-print, img, i.fas, i.fab').forEach(function (el) { el.remove(); });
    t.querySelectorAll('a').forEach(function (a) { a.parentNode.replaceChild(document.createTextNode(a.textContent), a); });
    t.removeAttribute('class');
    return t;
  }

  function download(blob, name) {
    var url = URL.createObjectURL(blob), a = document.createElement('a');
    a.href = url; a.download = name;
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
    setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
  }

  function esc(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

  function exportExcel() {
    var t = cleanTable();
    if (!t) return;

    var headerThs = t.querySelectorAll('thead th');
    var cols = headerThs.length || (t.querySelector('tr') ? t.querySelector('tr').children.length : 1);

    var F = "font-family: '" + FONT_NAME + "', 'Noto Sans Lao', sans-serif;";
    var B = "border: .5pt solid #000;";
    var TXT = "mso-number-format: '\\@';";

    // 1. ຈັດການຫົວຖັນ (thead) — ຄວາມສູງແຖວ 28pt ເພື່ອບໍ່ໃຫ້ຕັດສະຫຼະເທິງ-ລຸ່ມ
    t.querySelectorAll('thead tr').forEach(function (tr) {
      tr.style.cssText = 'height: 28pt; mso-height-source: userset;';
      tr.setAttribute('height', '37');
    });
    t.querySelectorAll('thead th').forEach(function (th) {
      th.style.cssText = F + B + 'height: 28pt; font-size: 13pt; font-weight: bold; text-align: center; vertical-align: middle; background-color: #e5e7eb; padding: 6px 10px; white-space: nowrap;';
    });

    // 2. ຈັດການແຖວເນື້ອໃນ (tbody) — ຄວາມສູງແຖວ 24pt, ຟອນ Phetsarath OT ເຕັມແຖວ
    t.querySelectorAll('tbody tr').forEach(function (tr) {
      tr.style.cssText = 'height: 24pt; mso-height-source: userset;';
      tr.setAttribute('height', '32');
    });
    t.querySelectorAll('tbody td').forEach(function (td) {
      var align = 'left';
      if (td.classList.contains('text-center')) align = 'center';
      else if (td.classList.contains('text-right') || td.classList.contains('dbr-money')) align = 'right';

      var txt = td.textContent.trim();
      var isCode = /^\d{8,}$/.test(txt) || /^0\d+$/.test(txt) || /^\d+-\d+/.test(txt);

      td.style.cssText = F + B + 'height: 24pt; font-size: 11pt; vertical-align: middle; padding: 6px 10px; white-space: nowrap; text-align: ' + align + ';' + (isCode ? TXT : '');
    });

    // 3. ຈັດການແຖວລວມ (tfoot) — ໃຫ້ເຕັມແຖວ ໂດຍແຍກທຸກຖັນໃຫ້ມີຂອບ ແລະ ພື້ນສີຄົບ ບໍ່ໃຫ້ແຫວ່ງ
    t.querySelectorAll('tfoot tr').forEach(function (tr) {
      tr.style.cssText = 'height: 26pt; mso-height-source: userset;';
      tr.setAttribute('height', '34');

      var cells = Array.from(tr.children);
      cells.forEach(function (td) {
        var cs = parseInt(td.getAttribute('colspan') || '1', 10);
        if (cs > 1) {
          td.removeAttribute('colspan');
          for (var i = 0; i < cs - 1; i++) {
            var emptyTd = document.createElement('td');
            emptyTd.style.cssText = F + B + 'height: 26pt; background-color: #f3f4f6; vertical-align: middle;';
            tr.insertBefore(emptyTd, td);
          }
        }
        var isMoney = td.classList.contains('dbr-money') || td.classList.contains('text-right');
        td.style.cssText = F + B + 'height: 26pt; font-size: 12pt; font-weight: bold; background-color: #f3f4f6; vertical-align: middle; padding: 6px 10px; white-space: nowrap; text-align: ' + (isMoney ? 'right' : 'center') + ';';
      });
    });

    // 4. ກຳນົດຄວາມກວ້າງຖັນ (colgroup) ໃຫ້ກວ້າງພໍດີກັບຂໍ້ຄວາມ
    var colgroupHtml = '<colgroup>';
    for (var ci = 0; ci < cols; ci++) {
      var th = headerThs[ci];
      var txt = th ? th.textContent.trim() : '';
      var w = 120;
      if (txt === '#' || txt.indexOf('#') !== -1) w = 45;
      else if (txt.indexOf('ວັນທີ') !== -1) w = 135;
      else if (txt.indexOf('ລູກຄ້າ') !== -1) w = 185;
      else if (txt.indexOf('ເລກບິນ') !== -1) w = 145;
      else if (txt.indexOf('ສາຂາ') !== -1) w = 110;
      else if (txt.indexOf('ຊ່ອງທາງ') !== -1) w = 115;
      else if (txt.indexOf('ຜູ້ຮັບ') !== -1) w = 105;
      else if (txt.indexOf('ເງິນ') !== -1 || txt.indexOf('ຍອດ') !== -1 || txt.indexOf('ຊຳລະ') !== -1 || txt.indexOf('ເຫຼືອ') !== -1) w = 130;
      else if (txt.indexOf('ສະຖານະ') !== -1 || txt.indexOf('ອາຍຸ') !== -1) w = 105;
      colgroupHtml += '<col width="' + Math.round(w * 1.33) + '" style="width:' + w + 'pt; mso-width-source:userset;">';
    }
    colgroupHtml += '</colgroup>';

    // 5. ຫົວຂໍ້ລາຍງານ ແລະ ສາຂາ/ວັນທີ (Title & Subtitle) ເປັນແຖວລວມເຊວໃນຕາຕະລາງດຽວກັນ
    var titleRows = '<tr style="height: 32pt; mso-height-source: userset;" height="42">'
      + '<td colspan="' + cols + '" style="' + F + 'border:none; font-size:16pt; font-weight:bold; text-align:center; vertical-align:middle;">' + esc(EX.title) + '</td>'
      + '</tr>'
      + '<tr style="height: 24pt; mso-height-source: userset;" height="32">'
      + '<td colspan="' + cols + '" style="' + F + 'border:none; font-size:12pt; text-align:center; vertical-align:middle;">' + esc(EX.sub) + '</td>'
      + '</tr>'
      + '<tr style="height: 12pt; mso-height-source: userset;" height="16">'
      + '<td colspan="' + cols + '" style="border:none;"></td>'
      + '</tr>';

    var thead = t.querySelector('thead');
    if (thead) {
      thead.insertAdjacentHTML('afterbegin', titleRows);
    } else {
      t.insertAdjacentHTML('afterbegin', '<thead>' + titleRows + '</thead>');
    }

    var sheetName = esc(String(EX.title || 'Sheet1').replace(/[\\\/\?\*\[\]:]/g, ' ').slice(0, 31));

    var html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">'
      + '<head><meta charset="utf-8">'
      + '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>' + sheetName + '</x:Name>'
      + '<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->'
      + '<style>'
      + '* { font-family: "' + FONT_NAME + '", "Noto Sans Lao", sans-serif !important; }'
      + 'body { margin: 0; padding: 10px; font-family: "' + FONT_NAME + '", "Noto Sans Lao", sans-serif; }'
      + 'table { border-collapse: collapse; width: 100%; font-family: "' + FONT_NAME + '", "Noto Sans Lao", sans-serif; }'
      + 'th, td { font-family: "' + FONT_NAME + '", "Noto Sans Lao", sans-serif; border: .5pt solid #000; padding: 6px 10px; vertical-align: middle; }'
      + 'th { background-color: #e5e7eb; font-weight: bold; text-align: center; }'
      + 'tfoot td { background-color: #f3f4f6; font-weight: bold; }'
      + '.text-right, .dbr-money { text-align: right; }'
      + '.text-center { text-align: center; }'
      + '.text-left { text-align: left; }'
      + '</style></head><body>'
      + '<table style="border-collapse: collapse; width: 100%;">'
      + colgroupHtml
      + t.innerHTML
      + '</table>'
      + '</body></html>';

    download(new Blob(['\ufeff' + html], { type: 'application/vnd.ms-excel;charset=utf-8' }), EX.file + '.xls');
  }

  function exportPdf() {
    var t = cleanTable();
    if (!t) return;
    if (typeof html2pdf === 'undefined') { window.print(); return; }   // ໂຫຼດ html2pdf ບໍ່ໄດ້ (ບໍ່ມີອິນເຕີເນັດ) → ພິມແທນ
    t.style.cssText = 'width:100%; border-collapse:collapse; font-size:11px; font-family:' + FONT + ';';
    t.querySelectorAll('th, td').forEach(function (c) {
      c.style.border = '1px solid #000';
      c.style.padding = '4px 6px';
      c.style.color = '#000';
      c.style.whiteSpace = 'normal';
    });
    t.querySelectorAll('thead th').forEach(function (c) { c.style.background = '#e5e7eb'; c.style.fontWeight = 'bold'; });
    t.querySelectorAll('tfoot td').forEach(function (c) { c.style.background = '#f3f4f6'; c.style.fontWeight = 'bold'; });
    t.querySelectorAll('tr').forEach(function (r) { r.style.pageBreakInside = 'avoid'; });

    var wrap = document.createElement('div');
    wrap.style.cssText = 'padding:10px; font-family:' + FONT + '; color:#000; background:#fff;';
    wrap.innerHTML = '<h3 style="text-align:center; margin:0 0 4px; font-family:' + FONT + ';">' + esc(EX.title) + '</h3>'
      + '<div style="text-align:center; margin-bottom:12px; font-size:12px;">' + esc(EX.sub) + '</div>';
    wrap.appendChild(t);

    html2pdf().set({
      margin: 8,
      filename: EX.file + '.pdf',
      image: { type: 'jpeg', quality: 0.98 },
      html2canvas: { scale: 2, useCORS: true },
      jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' },
      pagebreak: { mode: ['css', 'legacy'] }
    }).from(wrap).save();
  }

  window.addEventListener('load', function () {
    // ເອົາ print / export ອອກຈາກ URL — ໂຫຼດໜ້າຄືນບໍ່ສັ່ງຊ້ຳ
    try {
      var u = new URL(location.href);
      u.searchParams.delete('print'); u.searchParams.delete('export');
      history.replaceState(null, '', u.toString());
    } catch (e) {}
    setTimeout(function () {
      if (EX.action === 'print') window.print();
      else if (EX.action === 'excel') exportExcel();
      else exportPdf();
    }, 300);
  });
})();
</script>
