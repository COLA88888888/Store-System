<?php
/*
 * ສ່ວນ: ສັ່ງພິມບາໂຄ້ດ
 * executePageBarcodePrint: ສ້າງໜ້າພິມຂອງປ້າຍບາໂຄ້ດຕາມຂະໜາດເຈ້ຍ ແລ້ວພິມຜ່ານ iframe
 * (ຫຼື ສົ່ງໄປເຄື່ອງພິມ Bluetooth ທີ່ເລືອກ — barcodeBluetoothPrint ໃນ js/print_bluetooth.php).
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/settings/print_barcode/print_barcode_js.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>function executePageBarcodePrint() {
  var size       = $('#print_size').val();
  var showShop   = $('#opt_show_shop').is(':checked');
  var showName   = $('#opt_show_name').is(':checked');
  var showPrice  = $('#opt_show_price').is(':checked');
  var showCode   = $('#opt_show_code').is(':checked');
  var barcodeWidthMm  = parseFloat($('#barcode_width_mm').val()) || 36;
  var barcodeHeightMm = parseFloat($('#barcode_height_mm').val()) || 8.5;
  var verticalOffsetMm = parseFloat($('#label_vertical_offset_mm').val()) || 0;

  var itemsToPrint = [];
  var totalStickers = 0;

  ALL_PRODUCTS_PAGE.forEach(function(p) {
    var st = pageBarcodeState[p.product_id];
    if (st && st.selected && st.copies > 0) {
      itemsToPrint.push({ product: p, copies: st.copies });
      totalStickers += st.copies;
    }
  });

  if (itemsToPrint.length === 0 || totalStickers === 0) {
    Swal.fire({
      icon: 'warning',
      title: I18N_PRINT_BARCODE['print_barcode.no_selection_title'],
      text: I18N_PRINT_BARCODE['print_barcode.no_selection_text'],
      confirmButtonColor: '#0284c7',
      confirmButtonText: I18N_PRINT_BARCODE['print_barcode.ok_button']
    });
    return;
  }

  var cfg = BARCODE_SIZE_CONFIGS[size] || BARCODE_SIZE_CONFIGS['40x30_vert'];
  var basePath = '<?php echo $base_path; ?>';

  // Clamp the barcode to what actually fits this label format — the width/height fields above
  // are shared across every sticker size, and their default (36mm) is wider than a 3-up cell
  // (~31mm), which overflowed the cell and got clipped by the printer.
  if (cfg.maxBarcodeMm && barcodeWidthMm > cfg.maxBarcodeMm) {
    barcodeWidthMm = cfg.maxBarcodeMm;
  }

  // ຄວາມສູງໜ້າຕ້ອງເທົ່າກັບ "ໄລຍະຕໍ່ 1 ດວງ" ຂອງມ້ວນແທ້ (ສະຕິກເກີ + ຮ່ອງ) ບໍ່ດັ່ງນັ້ນທຸກໆດວງ
  // ຈະຄາດເຄື່ອນສະສົມໄປເລື່ອຍໆ. ສ່ວນເນື້ອຫາຍັງຕ້ອງຈັດເຄິ່ງກາງໃນ "ເນື້ອທີ່ສະຕິກເກີ" ເທົ່ານັ້ນ
  // (ຮ່ອງທີ່ເຫຼືອປະໄວ້ຫວ່າງ) — ຖ້າຈັດເຄິ່ງກາງທັງໜ້າ ເນື້ອຫາຈະຕ່ຳໄປເຄິ່ງໜຶ່ງຂອງຮ່ອງ.
  var labelPitchMm = parseFloat($('#label_pitch_mm').val());
  var sizeMatch = /size:\s*([\d.]+)mm\s+([\d.]+)mm/.exec(cfg.pageCss || '');
  // Label pitch only makes sense for a single-column roll (one sticker per physical page).
  // The 3-up layout prints 3 stickers per row inside a fixed 105x25mm page — its row-container
  // CSS is a fixed grid, not a per-label page, so overriding the page height here (defaults to
  // 30mm) desyncs @page from the hardcoded 25mm row-container and breaks the print.
  if (cfg.mode === 'single_roll' && !isNaN(labelPitchMm) && labelPitchMm > 0 && sizeMatch) {
    var stickerHeightMm = parseFloat(sizeMatch[2]);
    cfg = Object.assign({}, cfg);
    cfg.pageCss = '@page { size: ' + sizeMatch[1] + 'mm ' + labelPitchMm + 'mm; margin: 0; }';
    cfg.pageBoxCss = (cfg.pageBoxCss || '')
      .replace(/(^|[^-])height:\s*[\d.]+mm/, '$1height: ' + labelPitchMm + 'mm')
      .replace(/max-height:\s*[\d.]+mm/, 'max-height: ' + labelPitchMm + 'mm');
    if (labelPitchMm > stickerHeightMm) {
      cfg.labelCss = (cfg.labelCss || '').replace(/height:\s*100%/, 'height: ' + stickerHeightMm + 'mm');
    }
  }

  // ເລືອກພິມຜ່ານເຄື່ອງພິມ Bluetooth ທີ່ເຊື່ອມຕໍ່ຢູ່ → ສົ່ງໄປເຄື່ອງນັ້ນເລີຍ (js/print_bluetooth.php)
  if (typeof barcodeBluetoothPrint === 'function' && barcodeBluetoothPrint(itemsToPrint, BARCODE_SIZE_CONFIGS[size] || BARCODE_SIZE_CONFIGS['40x30_vert'], {
    showShop: showShop, showName: showName, showPrice: showPrice, showCode: showCode,
    barcodeWidthMm: barcodeWidthMm, barcodeHeightMm: barcodeHeightMm,
    verticalOffsetMm: verticalOffsetMm, labelPitchMm: (cfg.mode === 'single_roll' && labelPitchMm > 0) ? labelPitchMm : 0
  })) return;

  var printDoc = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Print Barcodes - MiniPos</title>';
  printDoc += '<script src="' + basePath + 'assets/js/JsBarcode.all.min.js"><\/script>';
  printDoc += '<style>';
  printDoc += cfg.pageCss + ' ';
  printDoc += '@media print { @page { ' + (cfg.pageCss.replace('@page {', '').replace('}', '')) + ' } html, body { margin: 0 !important; padding: 0 !important; } } ';
  printDoc += '* { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; } ';
  printDoc += 'html, body { margin: 0 !important; padding: 0 !important; width: 100%; background: #ffffff; color: #000000; font-family: "Nuanta", "Noto Sans Lao", "Souliyo", "Boon", Arial, sans-serif; } ';

  if (cfg.mode === 'single_roll') {
    printDoc += '.label-page { ' + cfg.pageBoxCss + ' } ';
    printDoc += '.label-page:last-child { page-break-after: auto !important; break-after: auto !important; } ';
    printDoc += '.barcode-label { ' + cfg.labelCss + ' } ';
  } else if (cfg.mode === '3up_roll') {
    // Each row of 3 is its own explicit block with page-break-after — letting the browser
    // auto-paginate one continuous multi-row grid across print pages is unreliable in Chrome
    // (rows can end up isolated on their own page with the rest blank).
    printDoc += '.row-container { display: grid; grid-template-columns: 32mm 32mm 32mm; justify-content: space-between; width: 105mm; padding: 0 1.5mm; box-sizing: border-box; page-break-after: always; break-after: page; page-break-inside: avoid; break-inside: avoid; } ';
    printDoc += '.row-container:last-child { page-break-after: auto !important; break-after: auto !important; } ';
    printDoc += '.barcode-label { ' + cfg.labelCss + ' } ';
  } else {
    printDoc += '.label-container { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6mm; padding: 4mm; } ';
    printDoc += '.barcode-label { ' + cfg.labelCss + ' page-break-inside: avoid; break-inside: avoid; } ';
  }

  if (verticalOffsetMm) {
    printDoc += '.barcode-label { transform: translateY(' + verticalOffsetMm + 'mm); } ';
  }

  printDoc += '.shop-title { ' + cfg.shopTitleCss + ' } ';
  printDoc += '.prod-title { ' + cfg.prodTitleCss + ' } ';
  printDoc += '.barcode-svg-wrap { width: 100%; display: flex; justify-content: center; align-items: center; margin: 0; padding: 0; overflow: hidden; } ';
  printDoc += '.barcode-svg { max-width: ' + barcodeWidthMm + 'mm; max-height: ' + barcodeHeightMm + 'mm; height: auto; display: block; margin: 0 auto; } ';
  // ເລກໃຕ້ບາໂຄ້ດ: ເລີ່ມ 11px ແລ້ວ fitCodeText() (ໃນ doPrint) ຫຍໍ້ລົງຈົນພໍດີກວ້າງບາໂຄ້ດ
  printDoc += '.barcode-num-text { font-family: monospace, "Courier New", Courier, sans-serif; font-size: 11px; font-weight: 800; letter-spacing: 0.6px; margin-left: auto !important; margin-right: auto !important; text-overflow: clip !important; line-height: 1; max-width: ' + barcodeWidthMm + 'mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0.3mm 0 0 0; text-align: center; color: #000000; } ';
  printDoc += '.price-title { ' + cfg.priceTitleCss + ' } ';
  printDoc += '</style></head><body>';

  function escapeHtml(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  var allStickerElements = [];
  itemsToPrint.forEach(function(item) {
    var p = item.product;
    var copies = item.copies;
    var barcodeVal = (p.barcode && p.barcode.trim() !== '') ? p.barcode.trim() : String(p.product_id);
    var prodName = p.product_name || '';
    if (p.unit) prodName += ' (' + p.unit + ')';
    var priceFormatted = Number(p.price || 0).toLocaleString('en-US') + ' <?php echo kipSymbol(); ?>';

    for (var i = 0; i < copies; i++) {
      var contentHtml = '';
      if (showShop) contentHtml += '<div class="shop-title">' + escapeHtml(SHOP_NAME) + '</div>';
      if (showName) contentHtml += '<div class="prod-title">' + escapeHtml(prodName) + '</div>';
      contentHtml += '<div class="barcode-svg-wrap"><svg class="barcode-svg" data-barcode="' + escapeHtml(barcodeVal) + '"></svg></div>';
      if (showCode) contentHtml += '<div class="barcode-num-text">' + escapeHtml(barcodeVal) + '</div>';
      if (showPrice) contentHtml += '<div class="price-title">' + escapeHtml(priceFormatted) + '</div>';

      if (cfg.mode === 'single_roll') {
        allStickerElements.push('<div class="label-page"><div class="barcode-label">' + contentHtml + '</div></div>');
      } else {
        allStickerElements.push('<div class="barcode-label">' + contentHtml + '</div>');
      }
    }
  });

  if (cfg.mode === '3up_roll') {
    for (var k = 0; k < allStickerElements.length; k += 3) {
      printDoc += '<div class="row-container">';
      printDoc += allStickerElements[k] || '';
      printDoc += allStickerElements[k + 1] || '';
      printDoc += allStickerElements[k + 2] || '';
      printDoc += '</div>';
    }
  } else if (cfg.mode === 'grid_a4') {
    printDoc += '<div class="label-container">' + allStickerElements.join('') + '</div>';
  } else {
    printDoc += allStickerElements.join('');
  }

  printDoc += '<script>';
  // ສະແກນຕິດງ່າຍ: (1) ມີຂອບຂາວ (quiet zone) 10 ແທ່ງ ທັງສອງຂ້າງ — ເມື່ອກ່ອນ margin:0 ເຄື່ອງສະແກນຫາຈຸດເລີ່ມບໍ່ເຫັນ;
  // (2) ຄວາມກວ້າງແທ່ງ 1 ໜ່ວຍ = ຈຳນວນເຕັມຂອງເມັດໝຶກເຄື່ອງພິມ 203dpi (0.125mm), ບໍ່ຖືກຢືດເປັນເສດສ່ວນ
  // ຈົນແທ່ງໜາບາງບໍ່ເທົ່າກັນ. ແຕ້ມດ້ວຍ width:1 ແລ້ວກຳນົດຂະໜາດ SVG ເປັນ mm ເອງ (JsBarcode ມີ viewBox).
  printDoc += 'var QUIET = 10, DOT_MM = 0.125, MIN_MODULE_MM = 0.19;';
  printDoc += 'function fitCodeText(el) {';
  printDoc += '  var size = 11;';
  printDoc += '  el.style.fontSize = size + "px";';
  printDoc += '  while (el.scrollWidth > el.clientWidth + 0.5 && size > 6) { size -= 0.5; el.style.fontSize = size + "px"; }';
  printDoc += '}';
  printDoc += 'function doPrint() {';
  printDoc += '  var maxMm = ' + barcodeWidthMm + ';';
  printDoc += '  var heightMm = ' + barcodeHeightMm + ' * 50 / 70;';
  printDoc += '  document.querySelectorAll(".barcode-svg").forEach(function(el) {';
  printDoc += '    var code = el.getAttribute("data-barcode");';
  printDoc += '    try {';
  // ຂອບຂາວສະເພາະຊ້າຍ-ຂວາ (ເທິງ-ລຸ່ມ 0) → ເລກບາໂຄ້ດຢູ່ໃກ້ແທ່ງ; ຄວາມສູງແທ່ງເທົ່າເກົ່າ (50/70 ຂອງ heightMm)
  printDoc += '      JsBarcode(el, code, { format: "CODE128", width: 1, height: 50, displayValue: false, margin: QUIET, marginTop: 0, marginBottom: 0 });';
  printDoc += '      var units = parseFloat((el.getAttribute("viewBox") || "").split(" ")[2]) || 0;';
  printDoc += '      if (!units) return;';
  printDoc += '      var moduleMm = Math.floor(maxMm / units / DOT_MM) * DOT_MM;';
  // ບາໂຄ້ດຍາວ ຈົນແທ່ງເຕັມເມັດບໍ່ພໍ → ໃຊ້ເຕັມຄວາມກວ້າງແທນ (ດີກວ່າລົ້ນ ແລ້ວຖືກຕັດ)
  printDoc += '      if (moduleMm < MIN_MODULE_MM) moduleMm = maxMm / units;';
  printDoc += '      var wMm = units * moduleMm;';
  printDoc += '      el.setAttribute("preserveAspectRatio", "none");';
  printDoc += '      el.setAttribute("width", wMm + "mm");';
  printDoc += '      el.setAttribute("height", heightMm + "mm");';
  printDoc += '      el.style.width = wMm + "mm"; el.style.height = heightMm + "mm";';
  printDoc += '      el.style.maxWidth = "none"; el.style.maxHeight = "none";';
  printDoc += '      el.style.shapeRendering = "crispEdges";';
  printDoc += '      var label = el.closest(".barcode-label");';
  printDoc += '      var num = label ? label.querySelector(".barcode-num-text") : null;';
  printDoc += '      if (num) { num.style.width = Math.min(maxMm, Math.max(10, wMm - 2 * QUIET * moduleMm + 4)) + "mm"; num.style.maxWidth = "100%"; }';
  printDoc += '    } catch (e) { console.error(e); }';
  printDoc += '  });';
  // ເລກໃຕ້ບາໂຄ້ດ: ຫຍໍ້ຂະໜາດຈົນພໍດີກວ້າງບາໂຄ້ດ — ບໍ່ລົ້ນອອກນອກ ແລະ ບໍ່ຖືກຕັດເປັນ …
  printDoc += '  document.querySelectorAll(".barcode-num-text").forEach(fitCodeText);';
  printDoc += '  setTimeout(function() { window.focus(); window.print(); }, 250);';
  printDoc += '}';
  printDoc += 'if (document.readyState === "complete") { doPrint(); } else { window.onload = doPrint; }';
  printDoc += '<\/script></body></html>';

  var iframe = document.getElementById('pageBarcodePrintIframe');
  var ifrDoc = iframe.contentWindow || iframe.contentDocument;
  if (ifrDoc.document) ifrDoc = ifrDoc.document;
  ifrDoc.open();
  ifrDoc.write(printDoc);
  ifrDoc.close();
  clearPageBarcodeSelection();   // ສົ່ງພິມແລ້ວ → ເອົາຕິກອອກ (ເນື້ອຫາຢູ່ໃນ iframe ແລ້ວ ບໍ່ກະທົບການພິມ)
}

