<?php
/**
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * JS ລວມຂອງໜ້າລາຍງານການຂາຍ: ເບິ່ງ/ພິມບິນຄືນ, ລົບບິນ, ແບ່ງໜ້າ, ສົ່ງອອກ Excel/PDF ແລະ ພິມຕາຕະລາງ.
 *   - reports/bill_view.php : ເບິ່ງລາຍລະອຽດບິນ
 *   - reports/reprint_receipt.php : ພິມໃບບິນຄືນ
 */
  // The branch's own LAN receipt printer (Settings > Printers), if it has one — reprinting a
  // bill then goes straight to that printer with no print preview, same as the POS checkout.
  $__repReceiptPrinter = null;
  try {
      $activeStore = getActiveStoreId($pdo);
      $__rpStmt = $pdo->prepare("SELECT id, name, type, paper_width FROM st_tbl_printer WHERE is_receipt_printer = 1 AND status = 1 AND (branch_id = ? OR branch_id = 0 OR branch_id IS NULL) ORDER BY (branch_id = ?) DESC, id DESC LIMIT 1");
      $__rpStmt->execute([$activeStore, $activeStore]);
      $__rpRow = $__rpStmt->fetch(PDO::FETCH_ASSOC);
      if ($__rpRow) {
          $__repReceiptPrinter = [
              'printer_id'   => (int)$__rpRow['id'],
              'printer_name' => $__rpRow['name'],
              'printer_type' => in_array($__rpRow['type'] ?? '', ['ip', 'usb'], true) ? $__rpRow['type'] : 'browser', // direct (LAN or USB)
              'paper_width'  => in_array(intval($__rpRow['paper_width'] ?? 80), [58, 80, 148, 210], true) ? intval($__rpRow['paper_width']) : 80,   // 148 = A5, 210 = A4
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
  'pos.receipt_transfer_bank' => 'ໂອນເຂົ້າ: %s',
]); ?>;
window.REPORT_QUEUE_ENABLED = <?php echo getSetting($pdo, 'queue_enabled', '0') === '1' ? 'true' : 'false'; ?>;
window.REPORT_RECEIPT_PRINTER = <?php echo json_encode($__repReceiptPrinter, JSON_UNESCAPED_UNICODE); ?>;
window.REPORT_LAN_MSG = <?php echo json_encode([
    'sent'   => t('reports.reprint_sent', 'ສົ່ງບິນໄປເຄື່ອງພິມແລ້ວ'),
    'failed' => t('pos.lan_print_failed', 'ພິມໃບຍ່ອຍຜ່ານ LAN ບໍ່ສຳເລັດ: %s'),
], JSON_UNESCAPED_UNICODE); ?>;

// Same per-id sizes the POS adds to its receipt image (#rc_total etc.)
var REPORT_RCPT_EXTRA_CSS = ' #rc_rep_total{font-size:17px !important;} #rc_rep_multicur{font-size:14.5px !important;} #rc_rep_bank_name_lbl{font-size:15px !important;}';

// Reprint: LAN receipt printer → print straight away (no preview); otherwise the browser dialog.
<?php require __DIR__ . '/reports/bill_view.php'; /* ເບິ່ງລາຍລະອຽດບິນ */ ?>
<?php require __DIR__ . '/reports/reprint_receipt.php'; /* ພິມໃບບິນຄືນ */ ?>
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
  
  var noPrints = cloneTable.querySelectorAll('.no-print, button, input, select');
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

  var headerThs = cloneTable.querySelectorAll('thead th');
  var cols = headerThs.length || (cloneTable.querySelector('tr') ? cloneTable.querySelector('tr').children.length : 1);

  cloneTable.querySelectorAll('tbody td[colspan]').forEach(function(td) {
    var cs = parseInt(td.getAttribute('colspan') || '1', 10);
    if (cs > cols) td.setAttribute('colspan', cols);
  });

  var F = "font-family: 'Phetsarath OT', 'Noto Sans Lao', sans-serif;";
  var B = "border: .5pt solid #000;";
  var TXT = "mso-number-format: '\\@';";

  // 1. thead
  cloneTable.querySelectorAll('thead tr').forEach(function(tr) {
    tr.style.cssText = 'height: 28pt; mso-height-source: userset;';
    tr.setAttribute('height', '37');
  });
  cloneTable.querySelectorAll('thead th').forEach(function(th) {
    th.style.cssText = F + B + 'height: 28pt; font-size: 13pt; font-weight: bold; text-align: center; vertical-align: middle; background-color: #e5e7eb; padding: 6px 10px; white-space: nowrap;';
  });

  // 2. tbody
  cloneTable.querySelectorAll('tbody tr').forEach(function(tr) {
    tr.style.cssText = 'height: 24pt; mso-height-source: userset;';
    tr.setAttribute('height', '32');
  });
  cloneTable.querySelectorAll('tbody td').forEach(function(td) {
    var align = 'left';
    if (td.classList.contains('text-center')) align = 'center';
    else if (td.classList.contains('text-right')) align = 'right';

    var txt = td.textContent.trim();
    var isCode = /^\d{8,}$/.test(txt) || /^0\d+$/.test(txt) || /^\d+-\d+/.test(txt);

    td.style.cssText = F + B + 'height: 24pt; font-size: 11pt; vertical-align: middle; padding: 6px 10px; white-space: nowrap; text-align: ' + align + ';' + (isCode ? TXT : '');
  });

  // 3. tfoot — ເຕັມແຖວ
  cloneTable.querySelectorAll('tfoot tr').forEach(function(tr) {
    tr.style.cssText = 'height: 26pt; mso-height-source: userset;';
    tr.setAttribute('height', '34');

    var cells = Array.from(tr.children);
    cells.forEach(function(td) {
      var cs = parseInt(td.getAttribute('colspan') || '1', 10);
      if (cs > 1) {
        td.removeAttribute('colspan');
        for (var i = 0; i < cs - 1; i++) {
          var emptyTd = document.createElement('td');
          emptyTd.style.cssText = F + B + 'height: 26pt; background-color: #f3f4f6; vertical-align: middle;';
          tr.insertBefore(emptyTd, td);
        }
      }
      var isRight = td.classList.contains('text-right') || /[\d,\.]+\s*(?:<?php echo kipSymbol(); ?>|LAK|THB|USD|%)/.test(td.textContent);
      td.style.cssText = F + B + 'height: 26pt; font-size: 12pt; font-weight: bold; background-color: #f3f4f6; vertical-align: middle; padding: 6px 10px; white-space: nowrap; text-align: ' + (isRight ? 'right' : 'center') + ';';
    });
  });

  // 4. colgroup
  var colgroupHtml = '<colgroup>';
  for (var ci = 0; ci < cols; ci++) {
    var th = headerThs[ci];
    var thTxt = th ? th.textContent.trim() : '';
    var w = 120;
    if (thTxt === '#' || thTxt.indexOf('#') !== -1) w = 45;
    else if (thTxt.indexOf('ວັນທີ') !== -1) w = 135;
    else if (thTxt.indexOf('ລູກຄ້າ') !== -1) w = 185;
    else if (thTxt.indexOf('ເລກບິນ') !== -1) w = 145;
    else if (thTxt.indexOf('ສິນຄ້າ') !== -1 || thTxt.indexOf('ລາຍການ') !== -1) w = 190;
    else if (thTxt.indexOf('ໝວດ') !== -1) w = 130;
    else if (thTxt.indexOf('ສາຂາ') !== -1) w = 110;
    else if (thTxt.indexOf('ຊ່ອງທາງ') !== -1) w = 115;
    else if (thTxt.indexOf('ຜູ້ຮັບ') !== -1 || thTxt.indexOf('ຜູ້ຂາຍ') !== -1) w = 105;
    else if (thTxt.indexOf('ເງິນ') !== -1 || thTxt.indexOf('ຍອດ') !== -1 || thTxt.indexOf('ລາຄາ') !== -1 || thTxt.indexOf('ຕົ້ນທຶນ') !== -1 || thTxt.indexOf('ກຳໄລ') !== -1) w = 130;
    else if (thTxt.indexOf('ຈຳນວນ') !== -1) w = 85;
    colgroupHtml += '<col width="' + Math.round(w * 1.33) + '" style="width:' + w + 'pt; mso-width-source:userset;">';
  }
  colgroupHtml += '</colgroup>';

  // 5. Title row inside table
  var titleEl = document.querySelector('h1, h2, h3, h4, .page-title');
  var repTitle = (titleEl ? titleEl.textContent.trim() : '') || repT('reports.export_title_all_sales');
  repTitle = repTitle.replace(/\s+/g, ' ');

  var titleRows = '<tr style="height: 32pt; mso-height-source: userset;" height="42">'
    + '<td colspan="' + cols + '" style="' + F + 'border:none; font-size:16pt; font-weight:bold; text-align:center; vertical-align:middle;">' + repTitle + '</td>'
    + '</tr>'
    + '<tr style="height: 12pt; mso-height-source: userset;" height="16">'
    + '<td colspan="' + cols + '" style="border:none;"></td>'
    + '</tr>';

  var theadEl = cloneTable.querySelector('thead');
  if (theadEl) {
    theadEl.insertAdjacentHTML('afterbegin', titleRows);
  } else {
    cloneTable.insertAdjacentHTML('afterbegin', '<thead>' + titleRows + '</thead>');
  }

  var sheetName = String(repTitle || 'Report').replace(/[\\\/\?\*\[\]:]/g, ' ').slice(0, 31);

  var html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
  html += '<head><meta charset="utf-8">';
  html += '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>' + sheetName + '</x:Name>';
  html += '<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
  html += '<style>';
  html += '* { font-family: "Phetsarath OT", "Noto Sans Lao", sans-serif !important; }';
  html += 'body { margin: 0; padding: 10px; font-family: "Phetsarath OT", "Noto Sans Lao", sans-serif; }';
  html += 'table { border-collapse: collapse; width: 100%; font-family: "Phetsarath OT", "Noto Sans Lao", sans-serif; }';
  html += 'th, td { font-family: "Phetsarath OT", "Noto Sans Lao", sans-serif; border: .5pt solid #000; padding: 6px 10px; vertical-align: middle; }';
  html += 'th { background-color: #e5e7eb; font-weight: bold; text-align: center; }';
  html += 'tfoot td { background-color: #f3f4f6; font-weight: bold; }';
  html += '.text-right { text-align: right; }';
  html += '.text-center { text-align: center; }';
  html += '.text-left { text-align: left; }';
  html += '</style></head><body>';
  html += '<table style="border-collapse: collapse; width: 100%;">';
  html += colgroupHtml;
  html += cloneTable.innerHTML;
  html += '</table>';
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

    var cols = cloneTable.querySelectorAll('thead th').length;
    cloneTable.querySelectorAll('tbody td[colspan]').forEach(function(td) {
      var cs = parseInt(td.getAttribute('colspan') || '1', 10);
      if (cs > cols) td.setAttribute('colspan', cols);
    });

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
