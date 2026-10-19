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
      $__rpStmt = $pdo->prepare("SELECT id, name, type, paper_width FROM st_tbl_printer WHERE is_receipt_printer = 1 AND status = 1 AND branch_id = ? LIMIT 1");
      $__rpStmt->execute([getActiveStoreId($pdo)]);
      if ($__rpRow = $__rpStmt->fetch(PDO::FETCH_ASSOC)) {
          $__repReceiptPrinter = [
              'printer_id'   => (int)$__rpRow['id'],
              'printer_name' => $__rpRow['name'],
              'printer_type' => in_array($__rpRow['type'] ?? '', ['ip', 'usb'], true) ? 'ip' : 'browser', // direct (LAN or USB)
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
  'pos.receipt_transfer_bank' => 'ໂອນເຂົ້າ: %s',
]); ?>;
window.REPORT_QUEUE_ENABLED = <?php echo getSetting($pdo, 'queue_enabled', '0') === '1' ? 'true' : 'false'; ?>;
window.REPORT_RECEIPT_PRINTER = <?php echo json_encode($__repReceiptPrinter, JSON_UNESCAPED_UNICODE); ?>;
window.REPORT_LAN_MSG = <?php echo json_encode([
    'sent'   => t('reports.reprint_sent', 'ສົ່ງບິນໄປເຄື່ອງພິມແລ້ວ'),
    'failed' => t('pos.lan_print_failed', 'ພິມໃບຍ່ອຍຜ່ານ LAN ບໍ່ສຳເລັດ: %s'),
], JSON_UNESCAPED_UNICODE); ?>;

// Same per-id sizes the POS adds to its receipt image (#rc_total etc.)
var REPORT_RCPT_EXTRA_CSS = ' #rc_rep_total{font-size:19px !important;} #rc_rep_multicur{font-size:14.5px !important;} #rc_rep_bank_name_lbl{font-size:15px !important;}';

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
