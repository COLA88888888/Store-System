<!-- ============================================================
     print_barcode_js.php - JavaScript Render Barcode & Engine ສັ່ງພິມ
     ============================================================ -->
<script>
var SHOP_NAME = <?php echo json_encode($company_name); ?>;
var ALL_PRODUCTS_PAGE = <?php echo json_encode($products_for_js); ?>;

var I18N_PRINT_BARCODE = <?php echo tjson([
    'print_barcode.qty_label' => 'ຈຳນວນດວງ:',
    'print_barcode.empty_search_state' => 'ບໍ່ພົບລາຍການສິນຄ້າທີ່ກົງກັບຄຳຄົ້ນຫາ',
    'print_barcode.summary_selected_label' => 'ເລືອກແລ້ວ',
    'print_barcode.summary_items_suffix' => 'ລາຍການ',
    'print_barcode.summary_total_label' => 'ລວມທັງໝົດ',
    'print_barcode.summary_stickers_suffix' => 'ດວງ',
    'print_barcode.no_selection_title' => 'ບໍ່ມີລາຍການຖືກເລືອກ',
    'print_barcode.no_selection_text' => 'ກະລຸນາເລືອກລາຍການສິນຄ້າ ແລະ ກຳນົດຈຳນວນດວງທີ່ຈະພິມຢ່າງນ້ອຍ 1 ດວງ!',
    'print_barcode.ok_button' => 'ຕົກລົງ',
]); ?>;

// State Structure: { [productId]: { selected: boolean, copies: number } }
var pageBarcodeState = {};

// 1. ຟັງຊັ້ນເລີ່ມຕົ້ນ State
function initPageBarcodeState() {
  ALL_PRODUCTS_PAGE.forEach(function(p) {
    if (!pageBarcodeState[p.product_id]) {
      pageBarcodeState[p.product_id] = {
        selected: false,
        copies: 1
      };
    }
  });
}

// 2. ຟັງຊັ້ນ Render ບັອກສິນຄ້າ ແລະ Barcode SVG Preview
function renderPageBarcodeBlocks() {
  // ຊ່ອງ "ໝູນປ້າຍ 180°" ໃຫ້ຂຶ້ນສະເພາະຂະໜາດ 3-up (ຂະໜາດອື່ນບໍ່ຕ້ອງການ)
  $('#rotateLabelWrap').toggle($('#print_size').val() === '3up_105x25');

  var container = $('#pageBarcodeBlocksContainer');
  container.empty();

  var searchVal = ($('#pageBarcodeSearch').val() || '').toLowerCase().trim();
  var catFilter = $('#pageCategoryFilter').val();
  var showShop  = $('#opt_show_shop').is(':checked');
  var showName  = $('#opt_show_name').is(':checked');
  var showPrice = $('#opt_show_price').is(':checked');

  var visibleCount = 0;

  ALL_PRODUCTS_PAGE.forEach(function(p) {
    var pId = p.product_id;
    var st  = pageBarcodeState[pId] || { selected: false, copies: 1 };

    var pName    = p.product_name || '';
    var pBarcode = (p.barcode && p.barcode.trim() !== '') ? p.barcode.trim() : String(pId);
    var pPrice   = Number(p.price || 0).toLocaleString('en-US') + ' ₭';
    var pUnit    = p.unit || '';
    var pCatId   = String(p.category_id || '');

    // ກັ່ນຕອງປະເພດສິນຄ້າ
    if (catFilter !== '' && pCatId !== String(catFilter)) {
      return;
    }

    // ກັ່ນຕອງຄຳຄົ້ນຫາ (ຊື່, ບາໂຄ້ດ, ID)
    if (searchVal !== '') {
      var matchName = pName.toLowerCase().includes(searchVal);
      var matchBar  = pBarcode.toLowerCase().includes(searchVal);
      var matchId   = String(pId).includes(searchVal);
      if (!matchName && !matchBar && !matchId) {
        return;
      }
    }

    visibleCount++;

    var isSelected = st.selected;
    var copies = st.copies || 1;

    var blockHtml = `
      <div class="col-md-4 col-sm-6 col-12 mb-4">
        <div class="barcode-block-card p-3 ${isSelected ? 'selected' : ''}" id="page_block_card_${pId}">
          
          <!-- Block Header: Checkbox & Product Title -->
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center text-truncate" style="gap: 8px;">
              <input type="checkbox" class="barcode-block-checkbox" id="page_chk_${pId}" 
                     ${isSelected ? 'checked' : ''} onchange="togglePageBlockSelect(${pId}, this.checked)">
              <label for="page_chk_${pId}" class="font-weight-bold text-dark mb-0 text-truncate" style="font-size: 0.95rem; cursor: pointer;" title="${pName}">
                ${pName}
              </label>
            </div>
            ${pUnit ? `<span class="badge badge-light border text-secondary ml-1" style="font-size: 0.76rem;">${pUnit}</span>` : ''}
          </div>

          <!-- Block Body: Image + Live Barcode Preview -->
          <div class="row align-items-center no-gutters my-2">
            
            <!-- Left: Product Image & Price -->
            <div class="col-4 text-center pr-2">
              <img src="${p.img_path}" class="img-fluid rounded border shadow-sm mb-1" 
                   style="max-height: 55px; object-fit: cover;" 
                   onerror="this.onerror=null; this.src='../../assets/img/image.jpg';">
              ${showPrice ? `<div class="font-weight-bold text-success" style="font-size: 0.82rem;">${pPrice}</div>` : ''}
            </div>

            <!-- Right: Barcode Preview SVG -->
            <div class="col-8 text-center pl-1">
              <div class="barcode-preview-box">
                ${showShop ? `<div class="text-secondary font-weight-bold text-truncate" style="font-size: 0.72rem; line-height: 1;">${SHOP_NAME}</div>` : ''}
                ${showName ? `<div class="text-dark font-weight-bold text-truncate" style="font-size: 0.76rem; line-height: 1.1; margin-top: 1px;">${pName}</div>` : ''}
                
                <div class="my-1 d-flex align-items-center justify-content-center" style="max-width: 100%; overflow: hidden;">
                  <svg id="page_svg_${pId}" style="max-width: 100%;"></svg>
                </div>

                ${showPrice ? `<div class="font-weight-bold text-dark" style="font-size: 0.8rem; line-height: 1;">${pPrice}</div>` : ''}
              </div>
            </div>

          </div>

          <!-- Block Footer: Quantity Control -->
          <div class="d-flex align-items-center justify-content-between pt-2 border-top mt-2">
            <small class="font-weight-bold text-dark" style="font-size: 0.82rem;">
              <i class="fas fa-copy text-primary mr-1"></i> ${I18N_PRINT_BARCODE['print_barcode.qty_label']}
            </small>

            <div class="d-flex align-items-center" style="gap: 4px;">
              <button type="button" class="btn btn-sm btn-outline-secondary barcode-qty-btn" onclick="changePageBlockCopies(${pId}, -1)">
                <i class="fas fa-minus" style="font-size: 0.7rem;"></i>
              </button>
              
              <input type="number" id="page_input_copies_${pId}" class="form-control barcode-qty-input" 
                     value="${copies}" min="1" max="500" 
                     oninput="updatePageBlockCopies(${pId}, this.value)">

              <button type="button" class="btn btn-sm btn-outline-secondary barcode-qty-btn" onclick="changePageBlockCopies(${pId}, 1)">
                <i class="fas fa-plus" style="font-size: 0.7rem;"></i>
              </button>
            </div>

            <!-- Quick Copies Shortcuts -->
            <div class="d-flex" style="gap: 2px;">
              <button type="button" class="btn btn-xs btn-light border py-0 px-1 font-weight-bold" style="font-size:0.7rem;" onclick="setPageBlockCopies(${pId}, 1)">1</button>
              <button type="button" class="btn btn-xs btn-light border py-0 px-1 font-weight-bold" style="font-size:0.7rem;" onclick="setPageBlockCopies(${pId}, 5)">5</button>
              <button type="button" class="btn btn-xs btn-light border py-0 px-1 font-weight-bold" style="font-size:0.7rem;" onclick="setPageBlockCopies(${pId}, 10)">10</button>
            </div>
          </div>

        </div>
      </div>
    `;

    container.append(blockHtml);

  // ວາດ Barcode SVG ດ້ວຍ JsBarcode
    var bcMode = $('#barcode_height').val() || 'auto';
    var printSize = $('#print_size').val() || '3up_105x25';
    var bcWidth = 1.0;
    var bcHeight = 26;
    var bcFontSize = 8.5;

    if (printSize === '3up_105x25') {
      if (bcMode === 'tall') { bcHeight = 32; bcWidth = 1.1; bcFontSize = 9; }
      else if (bcMode === 'extra_tall') { bcHeight = 38; bcWidth = 1.2; bcFontSize = 9.5; }
      else { bcHeight = 26; bcWidth = 1.0; bcFontSize = 8.5; }
    } else if (printSize === '40x30') {
      if (bcMode === 'tall') { bcHeight = 40; bcWidth = 1.4; bcFontSize = 10; }
      else if (bcMode === 'extra_tall') { bcHeight = 48; bcWidth = 1.5; bcFontSize = 10.5; }
      else { bcHeight = 34; bcWidth = 1.3; bcFontSize = 9; }
    } else if (printSize === '50x30') {
      if (bcMode === 'tall') { bcHeight = 46; bcWidth = 1.6; bcFontSize = 11.5; }
      else if (bcMode === 'extra_tall') { bcHeight = 54; bcWidth = 1.75; bcFontSize = 12; }
      else { bcHeight = 38; bcWidth = 1.5; bcFontSize = 11; }
    } else {
      if (bcMode === 'tall') { bcHeight = 52; bcWidth = 1.85; bcFontSize = 12; }
      else if (bcMode === 'extra_tall') { bcHeight = 62; bcWidth = 2.0; bcFontSize = 13; }
      else { bcHeight = 42; bcWidth = 1.7; bcFontSize = 11; }
    }

    try {
      JsBarcode(`#page_svg_${pId}`, pBarcode, {
        format: "CODE128",
        width: bcWidth,
        height: bcHeight,
        displayValue: true,
        fontSize: bcFontSize,
        font: "monospace",
        margin: 0,
        textMargin: 0
      });
    } catch (e) {
      console.error("Barcode render error for product " + pId, e);
    }
  });

  if (visibleCount === 0) {
    container.html(`
      <div class="col-12 text-center py-5 text-muted bg-white rounded border">
        <i class="fas fa-search fa-3x mb-3 text-secondary" style="opacity: 0.5;"></i>
        <h5>${I18N_PRINT_BARCODE['print_barcode.empty_search_state']}</h5>
      </div>
    `);
  }

  updatePageBarcodeSummary();
}

// 3. ຟັງຊັ້ນປ່ຽນສະຖານະເລືອກສິນຄ້າ
function togglePageBlockSelect(pId, isChecked) {
  if (!pageBarcodeState[pId]) pageBarcodeState[pId] = { selected: false, copies: 1 };
  pageBarcodeState[pId].selected = isChecked;

  var card = $(`#page_block_card_${pId}`);
  if (isChecked) {
    card.addClass('selected');
  } else {
    card.removeClass('selected');
  }

  updatePageBarcodeSummary();
}

// 4. ຟັງຊັ້ນເລືອກທັງໝົດ / ຍົກເລີກທັງໝົດ
function selectAllPageProducts(isSelected) {
  ALL_PRODUCTS_PAGE.forEach(function(p) {
    var pId = p.product_id;
    if (!pageBarcodeState[pId]) pageBarcodeState[pId] = { selected: false, copies: 1 };
    pageBarcodeState[pId].selected = isSelected;
  });
  renderPageBarcodeBlocks();
}

// 5. ຟັງຊັ້ນເພີ່ມ/ຫຼຸດ ຈຳນວນດວງ
function changePageBlockCopies(pId, delta) {
  if (!pageBarcodeState[pId]) pageBarcodeState[pId] = { selected: true, copies: 1 };
  var current = pageBarcodeState[pId].copies || 1;
  var newVal = current + delta;
  if (newVal < 1) newVal = 1;
  if (newVal > 500) newVal = 500;
  
  pageBarcodeState[pId].copies = newVal;
  pageBarcodeState[pId].selected = true;
  $(`#page_chk_${pId}`).prop('checked', true);
  $(`#page_block_card_${pId}`).addClass('selected');
  $(`#page_input_copies_${pId}`).val(newVal);

  updatePageBarcodeSummary();
}

// 6. ຟັງຊັ້ນຕັ້ງຄ່າຈຳນວນດວງດ່ວນ
function setPageBlockCopies(pId, num) {
  if (!pageBarcodeState[pId]) pageBarcodeState[pId] = { selected: true, copies: 1 };
  pageBarcodeState[pId].copies = num;
  pageBarcodeState[pId].selected = true;
  $(`#page_chk_${pId}`).prop('checked', true);
  $(`#page_block_card_${pId}`).addClass('selected');
  $(`#page_input_copies_${pId}`).val(num);

  updatePageBarcodeSummary();
}

function updatePageBlockCopies(pId, val) {
  var num = parseInt(val) || 1;
  if (num < 1) num = 1;
  if (num > 500) num = 500;
  if (!pageBarcodeState[pId]) pageBarcodeState[pId] = { selected: true, copies: 1 };
  pageBarcodeState[pId].copies = num;
  pageBarcodeState[pId].selected = true;
  $(`#page_chk_${pId}`).prop('checked', true);
  $(`#page_block_card_${pId}`).addClass('selected');

  updatePageBarcodeSummary();
}

function setAllPageCopies(num) {
  ALL_PRODUCTS_PAGE.forEach(function(p) {
    var pId = p.product_id;
    if (pageBarcodeState[pId] && pageBarcodeState[pId].selected) {
      pageBarcodeState[pId].copies = num;
      $(`#page_input_copies_${pId}`).val(num);
    }
  });
  updatePageBarcodeSummary();
}

// 7. ຟັງຊັ້ນອັບເດດຂໍ້ຄວາມສະຫຼຸບຈຳນວນເລືອກ ແລະ ຈຳນວນດວງລວມ
function updatePageBarcodeSummary() {
  var selectedCount = 0;
  var totalStickers = 0;

  ALL_PRODUCTS_PAGE.forEach(function(p) {
    var st = pageBarcodeState[p.product_id];
    if (st && st.selected) {
      selectedCount++;
      totalStickers += (st.copies || 1);
    }
  });

  $('#pageBarcodeSummary').html(
    `<i class="fas fa-check-circle text-success mr-1"></i> ${I18N_PRINT_BARCODE['print_barcode.summary_selected_label']}: <strong>${selectedCount}</strong> ${I18N_PRINT_BARCODE['print_barcode.summary_items_suffix']}
     <span class="mx-2">|</span>
     <i class="fas fa-print text-info mr-1"></i> ${I18N_PRINT_BARCODE['print_barcode.summary_total_label']}: <strong class="text-primary font-weight-bold">${totalStickers}</strong> ${I18N_PRINT_BARCODE['print_barcode.summary_stickers_suffix']}`
  );
}

// 8. ຟັງຊັ້ນປະມວນຜົນ ແລະ ສັ່ງພິມບາໂຄ້ດຜ່ານ Iframe
function executePageBarcodePrint() {
  var size      = $('#print_size').val();
  var bcMode    = $('#barcode_height').val() || 'auto';
  var showShop  = $('#opt_show_shop').is(':checked');
  var showName  = $('#opt_show_name').is(':checked');
  var showPrice = $('#opt_show_price').is(':checked');
  var rotate180 = $('#opt_rotate_180').is(':checked');

  var bcWidth = 1.0;
  var bcHeight = 26;
  var bcFontSize = 8.5;

  if (size === '3up_105x25') {
    if (bcMode === 'tall') { bcHeight = 32; bcWidth = 1.05; bcFontSize = 8.5; }
    else if (bcMode === 'extra_tall') { bcHeight = 38; bcWidth = 1.15; bcFontSize = 9; }
    else { bcHeight = 26; bcWidth = 0.98; bcFontSize = 8; }
  } else if (size === '40x30') {
    if (bcMode === 'tall') { bcHeight = 42; bcWidth = 1.4; bcFontSize = 10; }
    else if (bcMode === 'extra_tall') { bcHeight = 50; bcWidth = 1.5; bcFontSize = 10.5; }
    else { bcHeight = 36; bcWidth = 1.3; bcFontSize = 9; }
  } else if (size === '50x30') {
    if (bcMode === 'tall') { bcHeight = 48; bcWidth = 1.6; bcFontSize = 11.5; }
    else if (bcMode === 'extra_tall') { bcHeight = 56; bcWidth = 1.75; bcFontSize = 12; }
    else { bcHeight = 40; bcWidth = 1.5; bcFontSize = 11; }
  } else {
    if (bcMode === 'tall') { bcHeight = 54; bcWidth = 1.85; bcFontSize = 12; }
    else if (bcMode === 'extra_tall') { bcHeight = 64; bcWidth = 2.0; bcFontSize = 13; }
    else { bcHeight = 44; bcWidth = 1.7; bcFontSize = 11; }
  }

  var itemsToPrint = [];
  var totalStickers = 0;

  ALL_PRODUCTS_PAGE.forEach(function(p) {
    var st = pageBarcodeState[p.product_id];
    if (st && st.selected && st.copies > 0) {
      itemsToPrint.push({
        product: p,
        copies: st.copies
      });
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

  var printDoc = `
  <!DOCTYPE html>
  <html>
  <head>
    <meta charset="UTF-8">
    <title>Print Barcodes - MiniPos</title>
    <script src="${'<?php echo $base_path; ?>assets/js/JsBarcode.all.min.js'}"><\/script>
    <style>
      @page {
        margin: 0;
        ${size === '3up_105x25' ? 'size: 105mm 25mm;' : (size === '40x30' ? 'size: 40mm 30mm;' : (size === '50x30' ? 'size: 50mm 30mm;' : 'size: auto;'))}
      }
      * { box-sizing: border-box; }
      body { margin: 0 !important; padding: 0 !important; font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', Arial, sans-serif; background: #fff; color: #000; -webkit-print-color-adjust: exact; }
      .label-container { display: flex; flex-wrap: wrap; align-items: flex-start; }
      .barcode-label { box-sizing: border-box; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: space-between; page-break-inside: avoid; break-inside: avoid; }
      .barcode-wrapper { width: 100%; max-width: 31mm; overflow: hidden; display: flex; justify-content: center; align-items: center; margin: 0 auto; flex-shrink: 0; }
      .barcode-svg { max-width: 100% !important; height: auto !important; display: block; margin: 0 auto; shape-rendering: crispEdges; image-rendering: pixelated; }
      ${size === '40x30' ? `
        .barcode-label { width: 40mm; height: 30mm; padding: ${showShop ? '2mm' : '4mm'} 1mm 1.5mm 1mm; overflow: hidden; }
        .shop-title { font-size: 8px; font-weight: bold; line-height: 1; max-width: 38mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 1px; }
        .prod-title { font-size: 9px; font-weight: bold; line-height: 1.1; max-width: 38mm; white-space: nowrap; overflow: hidden; margin-top: ${showShop ? '0' : '1mm'}; margin-bottom: 1px; }
        .price-title { font-size: 10px; font-weight: bold; line-height: 1; margin-top: 1px; }
      ` : size === '50x30' ? `
        .barcode-label { width: 50mm; height: 30mm; padding: ${showShop ? '2.5mm' : '4.5mm'} 1.5mm 2mm 1.5mm; overflow: hidden; }
        .shop-title { font-size: 9px; font-weight: bold; line-height: 1; max-width: 47mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 1px; }
        .prod-title { font-size: 10px; font-weight: bold; line-height: 1.1; max-width: 47mm; white-space: nowrap; overflow: hidden; margin-top: ${showShop ? '0' : '1mm'}; margin-bottom: 1px; }
        .price-title { font-size: 11px; font-weight: bold; line-height: 1; margin-top: 1px; }
      ` : size === '3up_105x25' ? `
        /* ມ້ວນ "3-up 105x25mm": ຍັບດວງ 1 (ໂຕທາງໜ້າ nth-child(3n+1)) ອອກຈາກຂອບດຶງກະດາດ 3.5mm ປ້ອງກັນຫົວພິມຕັດ/ຈາງ */
        .label-container { display: grid; grid-template-columns: 32mm 32mm 32mm; justify-content: space-between; width: 105mm; margin: 0 auto; padding: 0 1.5mm; box-sizing: border-box; }
        .barcode-label { width: 32mm; height: 24.5mm; padding: ${showShop ? '1.5mm' : '3.2mm'} 0.5mm 0.5mm 0.5mm; overflow: hidden; ${rotate180 ? 'transform: rotate(180deg);' : ''} }
        .barcode-label:nth-child(3n+1) { margin-left: 3.5mm; }
        .shop-title { font-size: 7px; font-weight: bold; line-height: 1; max-width: 31mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 0.5px; }
        .prod-title { font-size: 8px; font-weight: bold; line-height: 1.1; max-width: 31mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: ${showShop ? '0' : '0.6mm'}; margin-bottom: 0.5px; }
        .price-title { font-size: 8.5px; font-weight: bold; line-height: 1; margin-top: 0.5px; white-space: nowrap; }
      ` : `
        .label-container { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; padding: 10mm; }
        .barcode-label { border: 1px dashed #cbd5e1; border-radius: 6px; padding: 4mm 2mm; min-height: 32mm; }
        .shop-title { font-size: 10px; font-weight: bold; line-height: 1.1; margin-bottom: 2px; }
        .prod-title { font-size: 11px; font-weight: bold; line-height: 1.2; margin-bottom: 2px; }
        .price-title { font-size: 12px; font-weight: bold; line-height: 1; margin-top: 2px; }
      `}
    </style>
  </head>
  <body>
  `;

  printDoc += `<div class="label-container">`;

  itemsToPrint.forEach(function(item) {
    var p = item.product;
    var copies = item.copies;
    var barcodeVal = (p.barcode && p.barcode.trim() !== '') ? p.barcode.trim() : String(p.product_id);
    var prodName = p.product_name || '';
    if (p.unit) prodName += ' (' + p.unit + ')';
    var priceFormatted = Number(p.price || 0).toLocaleString('en-US') + ' ₭';

    for (var i = 0; i < copies; i++) {
      printDoc += `
        <div class="barcode-label">
          ${showShop ? `<div class="shop-title">${SHOP_NAME}</div>` : ''}
          ${showName ? `<div class="prod-title">${prodName}</div>` : ''}
          <div class="barcode-wrapper">
            <svg class="barcode-svg" data-barcode="${barcodeVal}"></svg>
          </div>
          ${showPrice ? `<div class="price-title">${priceFormatted}</div>` : ''}
        </div>
      `;
    }
  });

  printDoc += `</div>`;

  printDoc += `
    <script>
      window.onload = function() {
        var svgs = document.querySelectorAll('.barcode-svg');
        svgs.forEach(function(el) {
          var code = el.getAttribute('data-barcode');
          JsBarcode(el, code, {
            format: "CODE128",
            width: ${bcWidth},
            height: ${bcHeight},
            displayValue: true,
            fontSize: ${bcFontSize},
            font: "monospace",
            background: "#ffffff",
            lineColor: "#000000",
            margin: 2,
            marginLeft: 6,
            marginRight: 6,
            marginTop: 1,
            marginBottom: 1,
            textMargin: 2
          });
        });
        setTimeout(function() {
          window.print();
        }, 300);
      };
    <\/script>
  </body>
  </html>
  `;

  var iframe = document.getElementById('pageBarcodePrintIframe');
  var ifrDoc = iframe.contentWindow || iframe.contentDocument;
  if (ifrDoc.document) ifrDoc = ifrDoc.document;
  ifrDoc.open();
  ifrDoc.write(printDoc);
  ifrDoc.close();
}

$(document).ready(function() {
  initPageBarcodeState();
  renderPageBarcodeBlocks();
});
</script>