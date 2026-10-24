/*
 * ເຄື່ອງມືພິມ / Excel / PDF ຂອງລາຍງານ — ໃຊ້ຮ່ວມກັນທົ່ວລະບົບ (ເຊັກສະຕັອກ, ລາຍງານສິນຄ້າໝົດອາຍຸ, ລາຍງານໜີ້ລູກຄ້າ).
 * ທຸກແບບອອກມາເປັນຕາຕະລາງດຽວກັນ: ຟອນ Phetsarath OT, ສີດຳທຳມະດາ, ມີຂອບທຸກຊ່ອງ, ແຖວລວມໜາ,
 * ຫົວລາຍງານ (ຊື່ / ສາຂາ / ເງື່ອນໄຂ) ຂ້າງເທິງ ແລະ ຜູ້ພິມ / ວັນທີພິມ ຂ້າງລຸ່ມ.
 *
 * ຂໍ້ມູນລາຍງານ (r):
 *   { title, fileBase, store?, meta: ['..'], headers: [{t, a}], rows: [[..]], footer: [..] | null }
 *   a = 'l' | 'c' | 'r' (ຊ້າຍ / ກາງ / ຂວາ); ຊ່ອງໃນ footer ເປັນຂໍ້ຄວາມ ຫຼື {t, a} ກໍໄດ້.
 *   summary (ບໍ່ບັງຄັບ): [[ຫົວຂໍ້, ຄ່າ], ...] — ຕາຕະລາງສະຫຼຸບ 2 ຖັນ ກ່ອນຕາຕະລາງຫຼັກ (ເຊັ່ນ ລາຍງານປິດກະ),
 *   ແຖວທີ 3 ເປັນ true = ໜາ.
 *
 * ການໃຊ້:
 *   RptExport.config({ storeName, printedBy })        — ຄ່າເລີ່ມຕົ້ນ (ໜ້າ PHP ໃສ່ໃຫ້)
 *   RptExport.excel(r) / .pdf(r) / .print(r)
 *   RptExport.fromTable(tableEl, { title, fileBase, store, meta })  — ສ້າງ r ຈາກຕາຕະລາງໃນໜ້າ
 *   RptExport.reloadAll('excel'|'pdf'|'print')        — ໂຫຼດໜ້າໃໝ່ດ້ວຍລາຍການທັງໝົດ (per_page=all) + export=…
 *   RptExport.autoRun(function () { return r; })      — ຖ້າ URL ມີ export=… ຈະສັ່ງໃຫ້ເອງຫຼັງໜ້າໂຫຼດ
 *
 * html2pdf (PDF) ໂຫຼດຈາກ cdnjs ເມື່ອຕ້ອງການເທົ່ານັ້ນ; ບໍ່ມີອິນເຕີເນັດ → ພິມແທນ.
 */
(function () {
  'use strict';

  var scriptSrc = (document.currentScript && document.currentScript.src) || window.location.href;
  var FONT_URL = new URL('../fonts/Phetsarath_OT.ttf', scriptSrc).href;
  var FONT = '"Phetsarath OT","Noto Sans Lao",sans-serif';
  var HTML2PDF_URL = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';

  var cfg = { storeName: '', printedBy: '' };

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s === null || s === undefined ? '' : String(s);
    return d.innerHTML;
  }
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function nowText() {
    var d = new Date();
    return pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear() + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
  }
  function cellText(c) { return c && typeof c === 'object' ? c.t : c; }
  function cellAlign(c, h) { return (c && typeof c === 'object' && c.a) || (h && h.a) || 'l'; }
  function storeOf(r) { return r.store !== undefined ? r.store : cfg.storeName; }
  function fileName(r, ext) { return (r.fileBase || 'report') + '_' + new Date().toISOString().slice(0, 10) + '.' + ext; }

  // ---- CSS ຂອງລາຍງານ (ພິມ + PDF) — ສີດຳທຳມະດາ ----
  function reportCss() {
    return '@font-face{font-family:"Phetsarath OT";src:url("' + FONT_URL + '") format("truetype");}' +
      '*{font-family:' + FONT + ';color:#000;}' +
      'body{margin:0;padding:16px;background:#fff;font-size:12pt;}' +
      'h2{text-align:center;font-size:15pt;font-weight:bold;margin:0 0 4px;}' +
      '.rp-store{text-align:center;font-size:12pt;font-weight:bold;margin-bottom:2px;}' +
      '.rp-meta{text-align:center;font-size:11pt;margin-bottom:10px;}' +
      'table{border-collapse:collapse;width:100%;font-size:11pt;}' +
      'th,td{border:1px solid #000;padding:5px 7px;vertical-align:middle;}' +
      'th{font-size:12pt;font-weight:bold;text-align:center;background:#fff;}' +
      'thead{display:table-header-group;}' +
      'tr{page-break-inside:avoid;break-inside:avoid;}' +
      'tfoot td{font-weight:bold;}' +
      '.a-r{text-align:right;}.a-c{text-align:center;}.a-l{text-align:left;}' +
      '.rp-foot{display:flex;justify-content:space-between;margin-top:14px;font-size:11pt;}' +
      'table.rp-sum{width:70%;margin:0 auto 14px;}table.rp-sum td{font-size:11pt;}table.rp-sum td.v{text-align:right;white-space:nowrap;}' +
      'table.rp-sum tr.b td{font-weight:bold;}' +
      '@page{size:A4 landscape;margin:10mm;}';
  }

  function reportBody(r) {
    var cls = function (c, h) { return 'a-' + cellAlign(c, h); };
    var store = storeOf(r);
    var html = '<h2>' + esc(r.title) + '</h2>';
    if (store) html += '<div class="rp-store">' + esc(store) + '</div>';
    if (r.meta && r.meta.length) html += '<div class="rp-meta">' + r.meta.map(esc).join(' &nbsp;|&nbsp; ') + '</div>';
    if (r.summary && r.summary.length) {
      html += '<table class="rp-sum">' + r.summary.map(function (s) {
        return '<tr' + (s[2] ? ' class="b"' : '') + '><td>' + esc(s[0]) + '</td><td class="v">' + esc(s[1]) + '</td></tr>';
      }).join('') + '</table>';
    }
    html += '<table><thead><tr>' + r.headers.map(function (h) { return '<th>' + esc(h.t) + '</th>'; }).join('') + '</tr></thead><tbody>';
    if (!r.rows.length) html += '<tr><td class="a-c" colspan="' + r.headers.length + '">ບໍ່ມີຂໍ້ມູນ</td></tr>';
    r.rows.forEach(function (row) {
      html += '<tr>' + row.map(function (c, i) { return '<td class="' + cls(c, r.headers[i]) + '">' + esc(cellText(c)) + '</td>'; }).join('') + '</tr>';
    });
    html += '</tbody>';
    if (r.footer) {
      html += '<tfoot><tr>' + r.footer.map(function (c, i) { return '<td class="' + cls(c, r.headers[i]) + '">' + esc(cellText(c)) + '</td>'; }).join('') + '</tr></tfoot>';
    }
    html += '</table>';
    html += '<div class="rp-foot"><span>ພິມໂດຍ: ' + esc(cfg.printedBy) + '</span><span>ວັນທີພິມ: ' + nowText() + '</span></div>';
    return html;
  }

  // ລໍຖ້າຟອນ Phetsarath OT ໃນໜ້ານີ້ (PDF ແຕ້ມຈາກໜ້ານີ້ ບໍ່ດັ່ງນັ້ນຈະອອກເປັນຟອນສຳຮອງ)
  var fontPromise = null;
  function loadFont() {
    try {
      if (!document.fonts || typeof FontFace === 'undefined') return Promise.resolve();
      if (!fontPromise) {
        var ff = new FontFace('Phetsarath OT', 'url("' + FONT_URL + '")');
        fontPromise = ff.load().then(function (f) { document.fonts.add(f); }).catch(function () {});
      }
      return fontPromise;
    } catch (e) { return Promise.resolve(); }
  }

  var pdfLibPromise = null;
  function loadHtml2Pdf() {
    if (typeof window.html2pdf !== 'undefined') return Promise.resolve(true);
    if (!pdfLibPromise) {
      pdfLibPromise = new Promise(function (resolve) {
        var s = document.createElement('script');
        s.src = HTML2PDF_URL;
        s.onload = function () { resolve(typeof window.html2pdf !== 'undefined'); };
        s.onerror = function () { resolve(false); };
        document.head.appendChild(s);
      });
    }
    return pdfLibPromise;
  }

  // ---- Excel: ທຸກຢ່າງຢູ່ໃນຕາຕະລາງດຽວ (ຊື່ລາຍງານ/ສາຂາ/ເງື່ອນໄຂ ເປັນແຖວລວມເຊວ), ກຳນົດຄວາມສູງແຖວເອງ —
  // Phetsarath OT ສູງ, ຄວາມສູງແຖວຕັ້ງຕົ້ນຂອງ Excel ຕັດສະຫຼະລາວ. ຕົວເລກຍາວ (ບາໂຄ້ດ/ເລກບິນ) ບັງຄັບເປັນຂໍ້ຄວາມ
  // (mso-number-format:\@) ບໍ່ດັ່ງນັ້ນ Excel ສະແດງເປັນ 8.85012E+12. Excel ໃຊ້ແຕ່ຊື່ຟອນ — ເຄື່ອງທີ່ເປີດຕ້ອງມີຟອນນີ້.
  // ຄວາມກວ້າງຖັນ Excel ຕາມຂໍ້ຄວາມຍາວສຸດ (ທຸກແຖວຢູ່ແຖວດຽວ ບໍ່ຕັດລົງ). ສະຫຼະ/ວັນນະຍຸດລາວທີ່ຊ້ອນເທິງ-ລຸ່ມ
  // (U+0EB1, U+0EB4–0EBC, U+0EC8–0ECD) ບໍ່ກິນຄວາມກວ້າງ ຈຶ່ງບໍ່ນັບ.
  function textUnits(v) {
    var s = v === null || v === undefined ? '' : String(v);
    return s.replace(/[\u0EB1\u0EB4-\u0EBC\u0EC8-\u0ECD]/g, '').length;
  }
  function excelColWidths(r) {
    var w = r.headers.map(function (h) { return textUnits(h.t) * 1.15 + 2; });   // ຫົວຖັນໜາ ກວ້າງກວ່ານິດໜຶ່ງ
    r.rows.concat(r.footer ? [r.footer] : []).forEach(function (row) {
      row.forEach(function (c, i) { if (i < w.length) w[i] = Math.max(w[i], textUnits(cellText(c)) + 2); });
    });
    (r.summary || []).forEach(function (s) { w[w.length - 1] = Math.max(w[w.length - 1], textUnits(s[1]) + 2); });
    // ຕົວອັກສອນ ≈ 7.5px ໃນ Phetsarath OT 11pt; ຂັ້ນຕ່ຳ 48px, ສູງສຸດ 460px
    return w.map(function (u) { return Math.max(48, Math.min(460, Math.round(u * 7.5 + 10))); });
  }

  function excelTable(r) {
    var n = r.headers.length;
    var F = 'font-family:\'Phetsarath OT\';color:#000;';
    var TXT = 'mso-number-format:\'\\@\';';
    var B = 'border:.5pt solid #000;';
    var NW = 'white-space:nowrap;mso-wrap-style:none;';   // ແຖວດຽວ — ບໍ່ຕັດຂໍ້ຄວາມລົງແຖວ
    var al = function (c, h) { return 'text-align:' + ({ r: 'right', c: 'center' }[cellAlign(c, h)] || 'left') + ';'; };
    var isCode = function (v) { return /^\d{8,}$/.test(v) || /^0\d+$/.test(v) || /^\d+-\d+$/.test(v); };
    var row = function (h, cells) { return '<tr style="height:' + h + 'pt;mso-height-source:userset;">' + cells + '</tr>'; };
    var full = function (txt, size, bold, h) {
      return row(h, '<td colspan="' + n + '" style="' + F + 'font-size:' + size + 'pt;' + (bold ? 'font-weight:bold;' : '') + 'text-align:center;vertical-align:middle;">' + esc(txt) + '</td>');
    };
    var store = storeOf(r);

    var widths = excelColWidths(r);
    var html = '<table style="border-collapse:collapse;table-layout:fixed;">';
    html += '<colgroup>' + widths.map(function (px) {
      return '<col width="' + px + '" style="width:' + Math.round(px * 0.75) + 'pt;mso-width-source:userset;">';
    }).join('') + '</colgroup>';
    var top = 0;   // ຈຳນວນແຖວຫົວລາຍງານ (ສຳລັບແຊ່ແຖວຫົວຕາຕະລາງ)
    html += full(r.title, 16, true, 34); top++;
    if (store) { html += full(store, 12, true, 24); top++; }
    if (r.meta && r.meta.length) { html += full(r.meta.join('   |   '), 11, false, 24); top++; }
    // ຕາຕະລາງສະຫຼຸບ: ຫົວຂໍ້ຂະຫຍາຍທຸກຖັນຍົກເວັ້ນຖັນສຸດທ້າຍ, ຄ່າຢູ່ຖັນສຸດທ້າຍ (ຈັດຂວາ)
    if (r.summary && r.summary.length) {
      html += row(8, '<td colspan="' + n + '"></td>'); top++;
      r.summary.forEach(function (s) {
        var bold = s[2] ? 'font-weight:bold;background:#F2F2F2;' : '';
        html += row(22, (n > 1 ? '<td colspan="' + (n - 1) + '" style="' + F + B + NW + bold + 'font-size:11pt;vertical-align:middle;padding:0 4px;">' + esc(s[0]) + '</td>' : '') +
          '<td style="' + F + B + NW + bold + 'font-size:11pt;vertical-align:middle;text-align:right;padding:0 4px;">' + esc(s[1]) + '</td>');
        top++;
      });
    }
    html += row(8, '<td colspan="' + n + '"></td>'); top++;
    // ຫົວຕາຕະລາງ: ພື້ນເທົາອ່ອນ, ໜາ, ກາງ
    html += row(30, r.headers.map(function (h) {
      return '<td style="' + F + B + NW + 'font-size:12pt;font-weight:bold;text-align:center;vertical-align:middle;background:#E7E6E6;">' + esc(h.t) + '</td>';
    }).join(''));
    r._freezeRows = top + 1;
    if (!r.rows.length) html += row(24, '<td colspan="' + n + '" style="' + F + B + 'font-size:11pt;text-align:center;">ບໍ່ມີຂໍ້ມູນ</td>');
    r.rows.forEach(function (cells, ri) {
      var zebra = ri % 2 === 1 ? 'background:#F7F7F7;' : '';   // ແຖວສະຫຼັບສີອ່ອນ ອ່ານງ່າຍ
      html += row(22, cells.map(function (c, i) {
        var v = cellText(c);
        v = v === null || v === undefined ? '' : String(v);
        return '<td style="' + F + B + NW + zebra + 'font-size:11pt;vertical-align:middle;padding:0 4px;' + al(c, r.headers[i]) + (isCode(v) ? TXT : '') + '">' + esc(v) + '</td>';
      }).join(''));
    });
    if (r.footer) {
      html += row(26, r.footer.map(function (c, i) {
        return '<td style="' + F + B + NW + 'font-size:11pt;font-weight:bold;vertical-align:middle;background:#E7E6E6;' + al(c, r.headers[i]) + '">' + esc(cellText(c)) + '</td>';
      }).join(''));
    }
    html += row(10, '<td colspan="' + n + '"></td>');
    html += row(22, '<td colspan="' + n + '" style="' + F + 'font-size:11pt;">ພິມໂດຍ: ' + esc(cfg.printedBy) + '   |   ວັນທີພິມ: ' + nowText() + '</td>');
    return html + '</table>';
  }

  function download(blob, name) {
    var url = URL.createObjectURL(blob), a = document.createElement('a');
    a.href = url; a.download = name;
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
    setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
  }

  function excel(r) {
    var sheet = esc(String(r.title || 'Sheet1').replace(/[\\\/\?\*\[\]:]/g, ' ').slice(0, 31));
    var table = excelTable(r);   // ຕັ້ງ r._freezeRows
    var fr = r._freezeRows || 0;
    // ແຊ່ແຖວຫົວຕາຕະລາງ (ເລື່ອນລົງແລ້ວຫົວຖັນຍັງຢູ່) + ບໍ່ສະແດງເສັ້ນຕາຕະລາງຂອງ Excel (ມີຂອບເອງແລ້ວ)
    var opts = (fr ? '<x:FreezePanes/><x:FrozenNoSplit/><x:SplitHorizontal>' + fr + '</x:SplitHorizontal><x:TopRowBottomPane>' + fr +
               '</x:TopRowBottomPane><x:ActivePane>2</x:ActivePane>' : '') +
               '<x:DoNotDisplayGridlines/><x:Print><x:ValidPrinterInfo/><x:PaperSizeIndex>9</x:PaperSizeIndex></x:Print>';
    var html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">' +
      '<head><meta charset="utf-8">' +
      '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>' + sheet + '</x:Name>' +
      '<x:WorksheetOptions>' + opts + '</x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->' +
      '<style>td{font-family:"Phetsarath OT";font-size:11pt;color:#000;mso-font-charset:0;white-space:nowrap;} ' +
      '@page{mso-page-orientation:landscape;margin:.5in .4in .5in .4in;}</style></head><body>' + table + '</body></html>';
    download(new Blob(['\ufeff' + html], { type: 'application/vnd.ms-excel;charset=utf-8' }), fileName(r, 'xls'));
  }

  function pdf(r) {
    loadHtml2Pdf().then(function (ok) {
      if (!ok) { print(r); return; }
      var wrap = document.createElement('div');
      wrap.innerHTML = '<style>' + reportCss().replace('body{', '.rp-wrap{') + '</style><div class="rp-wrap">' + reportBody(r) + '</div>';
      loadFont().then(function () {
        window.html2pdf().set({
          margin: 8, filename: fileName(r, 'pdf'),
          image: { type: 'jpeg', quality: 0.98 },
          html2canvas: { scale: 2, useCORS: true },
          jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' },
          pagebreak: { mode: ['css', 'legacy'] }
        }).from(wrap).save();
      });
    });
  }

  // ພິມ: ຕາຕະລາງລາຍງານໃນ iframe ເຊື່ອງ (ບໍ່ພິມໜ້າຈໍທັງໜ້າ), ລໍຟອນໂຫຼດກ່ອນ (ສູງສຸດ 2 ວິນາທີ)
  function print(r) {
    var f = document.getElementById('rptPrintFrame');
    if (f) f.remove();
    f = document.createElement('iframe');
    f.id = 'rptPrintFrame';
    f.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
    document.body.appendChild(f);
    var doc = f.contentWindow.document;
    doc.open();
    doc.write('<!doctype html><html><head><meta charset="utf-8"><title>' + esc(r.title) + '</title><style>' + reportCss() + '</style></head><body>' + reportBody(r) + '</body></html>');
    doc.close();
    var printed = false, go = function () { if (printed) return; printed = true; f.contentWindow.focus(); f.contentWindow.print(); };
    try {
      if (doc.fonts && doc.fonts.load) {
        Promise.all([doc.fonts.load('12pt "Phetsarath OT"'), doc.fonts.load('bold 12pt "Phetsarath OT"')])
          .then(function () { setTimeout(go, 100); }, function () { setTimeout(go, 100); });
      }
    } catch (e) {}
    setTimeout(go, 2000);
  }

  // ສ້າງ r ຈາກຕາຕະລາງ HTML ໃນໜ້າ: ຂ້າມຖັນ .no-print, ແຖວ "ບໍ່ພົບລາຍການ" (td ດຽວ colspan ເຕັມ),
  // ຂໍ້ຄວາມຫຼາຍແຖວໃນຊ່ອງດຽວ (ຊື່ + ລະຫັດ) ຕໍ່ກັນດ້ວຍ " · ". ການຈັດວາງເອົາຈາກ class text-right / text-center.
  function alignOf(el) {
    if (!el) return 'l';
    var c = el.classList;
    if (c.contains('text-right') || c.contains('dbr-money') || c.contains('rpt-r')) return 'r';
    if (c.contains('text-center') || c.contains('rpt-c')) return 'c';
    return 'l';
  }
  function textOf(el) {
    var t = (el.innerText !== undefined ? el.innerText : el.textContent) || '';
    return t.split(/\s*\n\s*/).filter(function (s) { return s !== ''; }).join(' · ').replace(/[ \t\u00a0]+/g, ' ').trim();
  }
  function visibleCells(tr, tag) {
    return Array.prototype.filter.call(tr.children, function (c) { return c.tagName === tag && !c.classList.contains('no-print'); });
  }
  function fromTable(table, opts) {
    opts = opts || {};
    var headRow = table.querySelector('thead tr:last-child');
    var ths = headRow ? visibleCells(headRow, 'TH') : [];
    var headers = ths.map(function (th) { return { t: textOf(th), a: alignOf(th) }; });
    var n = headers.length;
    var rows = [];
    table.querySelectorAll('tbody tr').forEach(function (tr) {
      var tds = visibleCells(tr, 'TD');
      if (tds.length === 1 && parseInt(tds[0].getAttribute('colspan') || '1', 10) > 1) return;   // ແຖວວ່າງ / ຂໍ້ຄວາມ
      var row = tds.map(function (td, i) {
        var a = alignOf(td);
        return a !== (headers[i] && headers[i].a) ? { t: textOf(td), a: a } : textOf(td);
      });
      while (row.length < n) row.push('');
      rows.push(row.slice(0, n));
    });
    var footer = null;
    var ftr = table.querySelector('tfoot tr');
    if (ftr) {
      footer = [];
      visibleCells(ftr, 'TD').forEach(function (td) {
        var span = parseInt(td.getAttribute('colspan') || '1', 10);
        for (var k = 1; k < span; k++) footer.push('');
        footer.push({ t: textOf(td), a: alignOf(td) });
      });
      while (footer.length < n) footer.push('');
      footer = footer.slice(0, n);
    }
    return { title: opts.title || '', fileBase: opts.fileBase, store: opts.store, meta: opts.meta || [], headers: headers, rows: rows, footer: footer };
  }

  function run(action, r) {
    if (action === 'excel') excel(r);
    else if (action === 'pdf') pdf(r);
    else print(r);
  }

  // ໂຫຼດໜ້າໃໝ່ດ້ວຍລາຍການທັງໝົດ ເພື່ອໃຫ້ພິມ/ອອກໄຟລ໌ໄດ້ທຸກໜ້າ ບໍ່ແມ່ນແຕ່ໜ້າປັດຈຸບັນ
  function reloadAll(action) {
    var u = new URL(window.location.href);
    u.searchParams.set('per_page', 'all');
    u.searchParams.set('page', '1');
    u.searchParams.delete('print');
    u.searchParams.set('export', action);
    window.location.href = u.toString();
  }

  function autoRun(getReport) {
    var u = new URL(window.location.href);
    var action = u.searchParams.get('export') || (u.searchParams.get('print') ? 'print' : '');
    if (['excel', 'pdf', 'print'].indexOf(action) === -1) return;
    // ເອົາ export ອອກຈາກ URL — ໂຫຼດໜ້າຄືນບໍ່ສັ່ງຊ້ຳ
    u.searchParams.delete('export'); u.searchParams.delete('print');
    try { history.replaceState(null, '', u.toString()); } catch (e) {}
    var start = function () { setTimeout(function () { run(action, getReport()); }, 300); };
    if (document.readyState === 'complete') start(); else window.addEventListener('load', start);
  }

  window.RptExport = {
    config: function (c) { for (var k in c) if (Object.prototype.hasOwnProperty.call(c, k)) cfg[k] = c[k]; },
    excel: excel, pdf: pdf, print: print, run: run,
    fromTable: fromTable, reloadAll: reloadAll, autoRun: autoRun
  };
})();
