<?php
/*
 * ສ່ວນ: ພິມໃບບິນ
 * ພິມໃບບິນລູກຄ້າ: ແປງເປັນຮູບສົ່ງເຄື່ອງພິມ LAN/USB ຖ້າຕັ້ງໄວ້, ບໍ່ດັ່ງນັ້ນພິມຜ່ານ iframe ຂອງ
 * browser ຂະໜາດ 80mm.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/pos/partials/js/pos_checkout_js.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>// ============================
// LAN RECEIPT PRINTING — send the already-rendered #receiptPrintArea straight to the
// designated receipt printer (Settings > Printers) instead of opening a browser print
// dialog. Renders the real DOM (same markup the browser-print path uses) to an image via
// an SVG <foreignObject>, so every business rule already applied to that DOM — VAT mode,
// promo strike-through, gifts, multi-currency, bank QR — comes along for free instead of
// being re-implemented here.
// ============================

// Per-id sizes for the checkout receipt — the reprint in the reports passes the same values for
// its #rc_rep_* ids (REPORT_RCPT_EXTRA_CSS in reports_js.php). Everything else about the bill
// image (fonts, sizes, width, black/white) lives in assets/js/lan-receipt.js, shared by both, so
// the bill printed at the sale and a reprint of it always come out the same.
var POS_RCPT_EXTRA_CSS = ' #rc_total{font-size:19px !important;} #rc_multicur{font-size:14.5px !important;} #rc_bank_name_lbl{font-size:15px !important;}';

// ເປີດໜ້າຂາຍ: ໂຫຼດຟອນໃບບິນ ແລະ ແປງໂລໂກ້/QR ໄວ້ລ່ວງໜ້າ ຕອນໜ້າວ່າງ — ບິນທຳອິດຫຼັງເປີດໜ້າ ບໍ່ຕ້ອງລໍໂຫຼດ
// (ໃຊ້ໄດ້ທັງ Bluetooth, LAN ແລະ ໜ້າຕ່າງພິມ). ລໍໃຫ້ໜ້າໂຫຼດແລ້ວກ່ອນ ບໍ່ໃຫ້ເປີດໜ້າຊ້າລົງ.
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

// Render #receiptPrintArea to a PNG data URI `widthPx` CSS pixels wide (see paperPx()).
function receiptAreaToImage(widthPx) {
  var source = document.getElementById('receiptPrintArea');
  if (!source) return Promise.reject(new Error('no receiptPrintArea'));
  if (!window.MiniposLanReceipt) return Promise.reject(new Error('assets/js/lan-receipt.js not loaded'));
  return MiniposLanReceipt.toImage(source, widthPx, '<?php echo $base_path; ?>', POS_RCPT_EXTRA_CSS);
}

// Send the receipt to its LAN/USB printer. Resolves true only when the printer actually accepted
// it — false in every other case (no LAN/USB receipt printer configured, rendering broke, the
// printer didn't respond) so the caller falls back to the browser dialog.
function printReceiptLan() {
  var rp = window.POS_RECEIPT_PRINTER;
  if (!rp || (rp.printer_type !== 'ip' && rp.printer_type !== 'usb')) return Promise.resolve(false);

  var widthPx = window.MiniposLanReceipt ? MiniposLanReceipt.paperPx(rp.paper_width) : ((parseInt(rp.paper_width, 10) === 58) ? 219 : 302);
  return receiptAreaToImage(widthPx).then(function (dataUri) {
    return $.ajax({
      url: window.POS_BACKEND_URL || '../../api/pos_backend.php',
      type: 'POST',
      dataType: 'json',
      data: { action: 'print_lan_ticket', printer_id: rp.printer_id, image: dataUri }
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

// Called after the receipt DOM is fully populated. When a LAN or USB receipt printer is configured,
// the shop has deliberately opted out of the browser dialog — a failed attempt shows an
// error toast (see printReceiptLan) but must NOT pop the OS print dialog as a "fallback".
function printReceiptAuto() {
  var rp = window.POS_RECEIPT_PRINTER;
  // ເຈ້ຍ A4/A5 (ຕັ້ງຄ່າ > ເຄື່ອງພິມ): ພິມຜ່ານໜ້າຕ່າງພິມເປັນໜ້າເຕັມສະເໝີ — ເຄື່ອງພິມ LAN/USB ຄວາມຮ້ອນພິມ A4/A5 ບໍ່ໄດ້
  var rpPaper = window.MiniposLanReceipt && MiniposLanReceipt.receiptPaper ? MiniposLanReceipt.receiptPaper((rp || {}).paper_width) : (rp || {}).paper_width;
  if (window.MiniposLanReceipt && MiniposLanReceipt.isPagePaper && MiniposLanReceipt.isPagePaper(rpPaper)) {
    printReceipt();
    return Promise.resolve(true);
  }
  if (rp && (rp.printer_type === 'ip' || rp.printer_type === 'usb')) {
    return printReceiptLan();
  }

  // ຖ້າ window.POS_RECEIPT_PRINTER ຍັງບໍ່ທັນຖືກໂຫຼດ (ຫຼື ຍັງບໍ່ໄດ້ reload ໜ້າ), ກວດສອບຈາກ backend ລ້າສຸດ
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
    // ຖ້າບໍ່ມີເຄື່ອງພິມ USB/LAN ຈຶ່ງເປີດໜ້າຕ່າງພິມ browser
    printReceipt();
    return true;
  }).catch(function () {
    printReceipt();
    return true;
  });
}

// ============================
// PRINT RECEIPT
// ============================
function printReceipt() {
  var area = document.getElementById("receiptPrintArea");
  // lan-receipt.js missing (e.g. not uploaded to the server): still print, with the images as-is
  if (!window.MiniposLanReceipt) { printReceiptHtml(area.innerHTML); return; }
  // Print the very image the LAN printer gets (receiptAreaToImage), so the dialog's bill is
  // identical to a LAN bill; the HTML version is only a fallback if rendering the image fails.
  var paper = MiniposLanReceipt.receiptPaper ? MiniposLanReceipt.receiptPaper((window.POS_RECEIPT_PRINTER || {}).paper_width) : (window.POS_RECEIPT_PRINTER || {}).paper_width;
  // A4/A5: ພິມ HTML ເຕັມໜ້າ (ບໍ່ແມ່ນຮູບບິນແຄບ) — ຂະໜາດໜ້າມາຈາກ MiniposLanReceipt.dialogPageCss(paper)
  if (MiniposLanReceipt.isPagePaper && MiniposLanReceipt.isPagePaper(paper)) {
    Promise.resolve(MiniposLanReceipt.printHtml(area)).then(printReceiptHtml);
    return;
  }
  // dialogPaperPx: ເຄື່ອງພິມໃນຕົວ (Android) ວາງບິນແບບ 58mm ບໍ່ໃຫ້ຖືກຕັດຂວາ; ເຄື່ອງອື່ນຄືເກົ່າ
  var dialogPx = MiniposLanReceipt.dialogPaperPx ? MiniposLanReceipt.dialogPaperPx(paper) : MiniposLanReceipt.paperPx(paper);
  receiptAreaToImage(dialogPx).then(function (uri) {
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
      * { box-sizing: border-box; font-family: 'Receipt Latin', 'Noto Sans Lao Local', 'Noto Sans Lao', 'Souliyo', 'Boon', Arial, sans-serif !important; color: #000 !important; -webkit-font-smoothing: antialiased; text-rendering: optimizeLegibility; }
      html, body { width: 80mm; margin: 0 auto; padding: 8px 6px; background: #fff; color: #000 !important; font-size: 17px; font-weight: 400; line-height: 1.5; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      /* No Bootstrap in this iframe — without this, headings keep the browser's default
         ~2.33em top margin and push the shop name far below the logo. */
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
      .receipt-footer-msg { font-size: 15px !important; font-weight: 400 !important; color: #000 !important; border-top: 1px dashed #000 !important; margin-top: 10px !important; padding-top: 8px !important; text-align: center !important; }
      /* logo + QR arrive already converted to black/white by MiniposLanReceipt.printHtml() */
      img.receipt-logo { max-width:90px!important; max-height:90px!important; height:auto!important; display:block!important; margin:0 auto 12px auto!important; object-fit:contain!important; }
      img.receipt-qr-img { width:50%!important; max-width:50%!important; max-height:none!important; height:auto!important; display:block; margin:6px auto 2px auto!important; object-fit:contain!important; }
      table { width:100%; border-collapse:collapse; margin:4px 0; font-size:15px !important; color: #000 !important; }
      td,th { padding:4px 0; vertical-align:top; color: #000 !important; font-weight: 400 !important; }
      th { font-weight: 400 !important; }
      .rc-strong, .rc-strong * { font-weight: 400 !important; -webkit-text-stroke: 0.25px #000; }
      #rc_total { font-size: 19px !important; }
      #rc_multicur { font-size: 14.5px !important; }
      #rc_bank_name_lbl { font-size: 15px !important; }
      /* The bill image already carries its own margins — let it span the whole page */
      html:has(.receipt-print-img), body:has(.receipt-print-img) { width: auto; padding: 0 !important; }
      @media print {
        html,body { width:100%; margin:0; padding:2mm; color: #000 !important; }
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

