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

var POS_RCPT_EXTRA_CSS = ' #rc_total{font-size:17px !important;} #rc_multicur{font-size:14.5px !important;} #rc_bank_name_lbl{font-size:15px !important;}';

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
  var iframeDoc = iframe.contentWindow || iframe.contentDocument;
  if (iframeDoc.document) iframeDoc = iframeDoc.document;

  iframeDoc.open();
  iframeDoc.write(`<!DOCTYPE html><html><head>
    <meta charset="utf-8">
    <title>${posT('pos.receipt_doc_title')}</title>
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/local-font.css">
    <style>
      @page { size: 80mm auto; margin: 0mm; }
      @font-face { font-family: 'Receipt Latin'; src: url('<?php echo $base_path; ?>assets/fonts/NotoSans-Latin-400.woff2') format('woff2'); font-weight: 400; }
      @font-face { font-family: 'Receipt Latin'; src: url('<?php echo $base_path; ?>assets/fonts/NotoSans-Latin-400.woff2') format('woff2'); font-weight: 400; size-adjust: 90%; unicode-range: U+0041-005A, U+0061-007A, U+00C0-024F; }
      @font-face { font-family: 'Receipt Latin'; src: url('<?php echo $base_path; ?>assets/fonts/NotoSans-Latin-700.woff2') format('woff2'); font-weight: 700; }
      @font-face { font-family: 'Receipt Latin'; src: url('<?php echo $base_path; ?>assets/fonts/NotoSans-Latin-700.woff2') format('woff2'); font-weight: 700; size-adjust: 90%; unicode-range: U+0041-005A, U+0061-007A, U+00C0-024F; }
      * { box-sizing: border-box; font-family: 'Nuanta', 'Receipt Latin', 'Noto Sans Lao Local', 'Noto Sans Lao', 'Souliyo', 'Boon', Arial, sans-serif !important; color: #000 !important; -webkit-font-smoothing: antialiased; text-rendering: optimizeLegibility; }
      html, body { width: 80mm; margin: 0 auto; padding: 8px 6px; background: #fff; color: #000 !important; font-size: 17px; font-weight: 400; line-height: 1.5; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      
      h1,h2,h3,h4,h5,h6 { margin: 0 !important; } h6 { font-size: 20px !important; white-space: nowrap; }
      .text-center { text-align: center !important; } .text-right { text-align: right !important; }
      .font-weight-bold { font-weight: 400 !important; color: #000 !important; }
      .small { font-size: 14px !important; color: #000 !important; font-weight: 400 !important; }
      .d-flex { display: flex; } .justify-content-between { justify-content: space-between; }
      [style*="display: none"], [style*="display:none"], .d-none { display: none !important; }
      .mb-0{margin-bottom:0!important}.mb-1{margin-bottom:4px!important}.mb-2{margin-bottom:8px!important}
      .mt-2{margin-top:8px!important}
      .text-muted { color: #000 !important; font-weight: 400 !important; }
      .receipt-header-address, .receipt-header-tel { font-size: 15px !important; font-weight: 400 !important; color: #000 !important; line-height: 1.4 !important; }
      
      /* ສ່ວນ HTML Fallback: ຫຍັບເສັ້ນຂີດປະ ແລະ ຕົວໜັງສືມາຂວາ 1 ຂີດ (margin-left: -6px) */
      .receipt-meta, table, #rc_payment_rows, #rc_rep_payment_rows, .rc-sum-wrap, .rc-change-wrap, #rc_multicur, #rc_rep_multicur, .receipt-qr-box, .receipt-footer-msg, .rc-sum, .rc-net {
        margin-left: 0px !important; width: calc(100% - 6px) !important;
      }
      .receipt-header-block { margin-left: 0px !important; width: 100% !important; text-align: center !important; }
      div[style*="dashed"], .rc-divider {
        border-top: 1px dashed #000 !important; margin: 6px 0 !important; margin-left: 0px !important; width: calc(100% - 6px) !important;
      }
      th [style*="dashed"], th .rc-divider, .receipt-qr-box [style*="dashed"], .receipt-qr-box .rc-divider {
        display: none !important;
      }
      div[style*="dashed"], .rc-divider { border-top: 1px dashed #000 !important; margin: 6px 0 !important; }
      tr.rc-strong { border-top: none !important; border-bottom: none !important; }
      thead tr, tr:first-child th { border-bottom: 1px dashed #000 !important; padding-bottom: 4px !important; }

      .receipt-footer-msg { font-size: 15px !important; font-weight: 400 !important; color: #000 !important; margin-top: 10px !important; padding-top: 8px !important; text-align: center !important; }
      img.receipt-logo { max-width:90px!important; max-height:90px!important; height:auto!important; display:block!important; margin:0 auto 12px auto!important; object-fit:contain!important; }
      img.receipt-qr-img { width:50%!important; max-width:50%!important; max-height:none!important; height:auto!important; display:block; margin:6px auto 2px auto!important; object-fit:contain!important; }
      table { border-collapse:collapse; margin:4px 0; font-size:15px !important; color: #000 !important; }
      td,th { padding:4px 0; vertical-align:top; color: #000 !important; font-weight: 400 !important; }
      th { font-weight: 400 !important; }
      .rc-strong, .rc-strong * { font-weight: 400 !important; -webkit-text-stroke: 0.25px #000; }
      #rc_total { font-size: 17px !important; }
      .rc-sum, .rc-sum * { font-size: 15px !important; font-weight: 400 !important; -webkit-text-stroke: 0.25px #000; }
      .rc-net, .rc-net * { font-size: 17px !important; font-weight: 400 !important; -webkit-text-stroke: 0.25px #000; }
      #rc_multicur { font-size: 14.5px !important; }
      #rc_bank_name_lbl { font-size: 15px !important; }
      html:has(.receipt-print-img), body:has(.receipt-print-img) { width: auto; padding: 0 !important; }
      @media print {
        html,body { width:100%; margin:0; padding:2mm; color: #000 !important; padding-right: 6px !important; }
        * { color: #000 !important; }
        body:has(.receipt-print-img) { padding: 0 !important; }
      }
      ${(window.MiniposLanReceipt && MiniposLanReceipt.dialogPageCss) ? MiniposLanReceipt.dialogPageCss(MiniposLanReceipt.receiptPaper ? MiniposLanReceipt.receiptPaper((window.POS_RECEIPT_PRINTER || {}).paper_width) : (window.POS_RECEIPT_PRINTER || {}).paper_width) : ''}
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