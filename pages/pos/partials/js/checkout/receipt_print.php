<?php
/*
 * ສ່ວນ: ພິມໃບບິນ
 * ພິມໃບບິນລູກຄ້າ: ແປງເປັນຮູບສົ່ງເຄື່ອງພິມ LAN/USB ຖ້າຕັ້ງໄວ້, ບໍ່ດັ່ງນັ້ນພິມຜ່ານ iframe ຂອງ
 * browser ຂະໜາດ 80mm.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/pos/partials/js/pos_checkout_js.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>// ============================
// LAN RECEIPT PRINTING 
// ============================

// CSS ບິນມີບ່ອນດຽວ: RECEIPT_CSS ໃນ assets/js/lan-receipt.js (ທຸກຊ່ອງທາງ). ຕົວແປນີ້ຄົງໄວ້ໃຫ້ໂຄດເກົ່າທີ່ອ້າງເຖິງ — ວ່າງສະເໝີ.
var POS_RCPT_EXTRA_CSS = '';

(function () {
  function warm() {
    if (!window.MiniposLanReceipt || !MiniposLanReceipt.warmUp) return;
    var paper = (window.POS_RECEIPT_PRINTER || {}).paper_width;
    MiniposLanReceipt.warmUp(document.getElementById('receiptPrintArea'), MiniposLanReceipt.paperPx(paper), '<?php echo $base_path; ?>', POS_RCPT_EXTRA_CSS);
  }
  window.addEventListener('load', function () {
    if (window.requestIdleCallback) requestIdleCallback(warm, { timeout: 4000 }); else setTimeout(warm, 1500);
  });
})();

function receiptAreaToImage(widthPx) {
  var source = document.getElementById('receiptPrintArea');
  if (!source) return Promise.reject(new Error('no receiptPrintArea'));
  if (!window.MiniposLanReceipt) return Promise.reject(new Error('assets/js/lan-receipt.js not loaded'));
  return MiniposLanReceipt.toImage(source, widthPx, '<?php echo $base_path; ?>', POS_RCPT_EXTRA_CSS);
}

function printReceiptLan() {
  var rp = window.POS_RECEIPT_PRINTER;
  if (!rp || (rp.printer_type !== 'ip' && rp.printer_type !== 'usb')) return Promise.resolve(false);

  var widthPx = window.MiniposLanReceipt ? MiniposLanReceipt.paperPx(rp.paper_width) : ((parseInt(rp.paper_width, 10) === 58) ? 219 : 302);
  return receiptAreaToImage(widthPx).then(function (dataUri) {
    return $.ajax({
      url: window.POS_BACKEND_URL || '../../api/pos_backend.php',
      type: 'POST',
      dataType: 'json',
      data: { action: 'print_lan_ticket', printer_id: rp.printer_id, image: dataUri, bw: 1 }
    });
  }).then(function (res) {
    if (res && res.success) return true;
    lanTicketFailed({ name: rp.printer_name || 'Receipt' }, res && res.message);
    return false;
  }).catch(function (err) {
    lanTicketFailed({ name: rp.printer_name || 'Receipt' }, err && (err.message || err.statusText));
    return false;
  });
}

function printReceiptAuto() {
  var rp = window.POS_RECEIPT_PRINTER;
  var rpPaper = window.MiniposLanReceipt && MiniposLanReceipt.receiptPaper ? MiniposLanReceipt.receiptPaper((rp || {}).paper_width) : (rp || {}).paper_width;
  if (window.MiniposLanReceipt && MiniposLanReceipt.isPagePaper && MiniposLanReceipt.isPagePaper(rpPaper)) {
    printReceipt();
    return Promise.resolve(true);
  }
  if (rp && (rp.printer_type === 'ip' || rp.printer_type === 'usb')) {
    return printReceiptLan();
  }

  return $.ajax({
    url: window.POS_BACKEND_URL || '../../api/pos_backend.php',
    type: 'GET',
    data: { action: 'get_receipt_printer' },
    dataType: 'json',
    timeout: 3000
  }).then(function (res) {
    if (res && res.printer && (res.printer.printer_type === 'ip' || res.printer.printer_type === 'usb')) {
      window.POS_RECEIPT_PRINTER = res.printer;
      return printReceiptLan();
    }
    printReceipt();
    return true;
  }).catch(function () {
    printReceipt();
    return true;
  });
}

function printReceipt() {
  var area = document.getElementById("receiptPrintArea");
  if (!window.MiniposLanReceipt) { printReceiptHtml(area.innerHTML); return; }
  var paper = MiniposLanReceipt.receiptPaper ? MiniposLanReceipt.receiptPaper((window.POS_RECEIPT_PRINTER || {}).paper_width) : (window.POS_RECEIPT_PRINTER || {}).paper_width;
  if (MiniposLanReceipt.isPagePaper && MiniposLanReceipt.isPagePaper(paper)) {
    Promise.resolve(MiniposLanReceipt.printHtml(area)).then(printReceiptHtml);
    return;
  }
  var dialogPx = MiniposLanReceipt.dialogPaperPx ? MiniposLanReceipt.dialogPaperPx(paper) : MiniposLanReceipt.paperPx(paper);

  // ໂຄງຮ່າງບິນມາຈາກ RECEIPT_CSS ໃນ assets/js/lan-receipt.js — ດຽວກັນກັບ LAN/USB/Bluetooth
  MiniposLanReceipt.toImage(area, dialogPx, '<?php echo $base_path; ?>', POS_RCPT_EXTRA_CSS).then(function (uri) {
    return MiniposLanReceipt.imageHtml(uri, paper);
  }).catch(function (err) {
    console.warn('Receipt image failed, printing HTML instead:', err);
    return MiniposLanReceipt.printHtml(area);
  }).then(printReceiptHtml);
}

function printReceiptHtml(printContent) {
  var iframe = document.getElementById("posPrintIframe");
  if (!iframe) {
    iframe = document.createElement('iframe');
    iframe.id = 'posPrintIframe';
    iframe.style.cssText = 'position:fixed;right:-9999px;bottom:-9999px;width:300px;height:300px;border:none;opacity:0;pointer-events:none;';
    document.body.appendChild(iframe);
  }
  // ບິນເປັນຮູບແລ້ວ → ໜ້າພິມແບບເບົາ (ບໍ່ໂຫຼດ font 1.2 MB) ໃຫ້ preview ຂຶ້ນໄວ — assets/js/lan-receipt.js
  if (window.MiniposLanReceipt && MiniposLanReceipt.printImageReceipt && printContent.indexOf('receipt-print-img') !== -1) {
    var imgPaper = MiniposLanReceipt.receiptPaper ? MiniposLanReceipt.receiptPaper((window.POS_RECEIPT_PRINTER || {}).paper_width) : (window.POS_RECEIPT_PRINTER || {}).paper_width;
    MiniposLanReceipt.printImageReceipt(iframe, printContent, posT('pos.receipt_doc_title'), imgPaper);
    return;
  }

  var iframeDoc = iframe.contentWindow || iframe.contentDocument;
  if (iframeDoc.document) iframeDoc = iframeDoc.document;

  iframeDoc.open();
  iframeDoc.write(`<!DOCTYPE html><html><head>
    <meta charset="utf-8">
    <title>${posT('pos.receipt_doc_title')}</title>
    <style>
      /* CSS ດຽວກັບຮູບບິນທຸກຊ່ອງທາງ (RECEIPT_CSS) — ບໍ່ມີ CSS ບິນແຍກຢູ່ໜ້ານີ້ອີກ (assets/js/lan-receipt.js htmlReceiptCss) */
      ${(window.MiniposLanReceipt && MiniposLanReceipt.htmlReceiptCss) ? MiniposLanReceipt.htmlReceiptCss('<?php echo $base_path; ?>', MiniposLanReceipt.receiptPaper ? MiniposLanReceipt.receiptPaper((window.POS_RECEIPT_PRINTER || {}).paper_width) : (window.POS_RECEIPT_PRINTER || {}).paper_width) : '@page { size: 80mm auto; margin: 0mm; }'}
    </style>
  </head><body>${printContent}</body></html>`);
  iframeDoc.close();

  var images = iframeDoc.getElementsByTagName('img');
  var totalImages = images.length;
  var loadedCount = 0;
  var printTriggered = false;

  function doTriggerPOSPrint() {
    if (printTriggered) return;
    printTriggered = true;
    try {
      iframe.contentWindow.focus();
      iframe.contentWindow.print();
    } catch(e) {
      console.warn('Iframe print error, falling back to window.print():', e);
      window.print();
    }
  }

  if (totalImages === 0) {
    setTimeout(doTriggerPOSPrint, 200);
  } else {
    for (var i = 0; i < totalImages; i++) {
      if (images[i].complete && images[i].naturalWidth !== 0) {
        loadedCount++;
      } else {
        images[i].onload = images[i].onerror = function() {
          loadedCount++;
          if (loadedCount >= totalImages) {
            setTimeout(doTriggerPOSPrint, 150);
          }
        };
      }
    }
    if (loadedCount >= totalImages) {
      setTimeout(doTriggerPOSPrint, 200);
    } else {
      setTimeout(doTriggerPOSPrint, 600);
    }
  }
}