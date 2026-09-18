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

var pageBarcodeState = {};

function initPageBarcodeState() {
  ALL_PRODUCTS_PAGE.forEach(function(p) {
    if (!pageBarcodeState[p.product_id]) {
      pageBarcodeState[p.product_id] = { selected: false, copies: 1 };
    }
  });
}

function renderPageBarcodeBlocks() {
  var container = $('#pageBarcodeBlocksContainer');
  container.empty();

  var searchVal = ($('#pageBarcodeSearch').val() || '').toLowerCase().trim();
  var catFilter = $('#pageCategoryFilter').val();
  var showShop  = $('#opt_show_shop').is(':checked');
  var showName  = $('#opt_show_name').is(':checked');
  var showPrice = $('#opt_show_price').is(':checked');
  var showCode  = $('#opt_show_code').is(':checked');
  var barcodeHeightMm = parseFloat($('#barcode_height_mm').val()) || 8.5;
  var previewBarcodeHeightPx = Math.max(10, Math.round(barcodeHeightMm * 3.78));

  var visibleCount = 0;

  ALL_PRODUCTS_PAGE.forEach(function(p) {
    var pId = p.product_id;
    var st  = pageBarcodeState[pId] || { selected: false, copies: 1 };

    var pName    = p.product_name || '';
    var pBarcode = (p.barcode && p.barcode.trim() !== '') ? p.barcode.trim() : String(pId);
    var pPrice   = Number(p.price || 0).toLocaleString('en-US') + ' ₭';
    var pUnit    = p.unit || '';
    var pCatId   = String(p.category_id || '');

    if (catFilter !== '' && pCatId !== String(catFilter)) return;

    if (searchVal !== '') {
      var matchName = pName.toLowerCase().includes(searchVal);
      var matchBar  = pBarcode.toLowerCase().includes(searchVal);
      var matchId   = String(pId).includes(searchVal);
      if (!matchName && !matchBar && !matchId) return;
    }

    visibleCount++;
    var isSelected = st.selected;
    var copies = st.copies || 1;

    var blockHtml = `
      <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-4">
        <div class="barcode-block-card p-3 ${isSelected ? 'selected' : ''}" id="page_block_card_${pId}">
          
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

          <div class="row align-items-center no-gutters my-2">
            <div class="col-4 text-center pr-2">
              <img src="${p.img_path}" class="img-fluid rounded border shadow-sm mb-1" 
                   style="max-height: 55px; object-fit: cover;" 
                   onerror="this.onerror=null; this.src='../../assets/img/image.jpg';">
              ${showPrice ? `<div class="font-weight-bold text-success" style="font-size: 0.82rem;">${pPrice}</div>` : ''}
            </div>

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

    try {
      JsBarcode(`#page_svg_${pId}`, pBarcode, {
        format: "CODE128",
        width: 1.3,
        height: previewBarcodeHeightPx,
        displayValue: showCode,
        fontSize: 10,
        font: "monospace",
        margin: 1,
        textMargin: 1
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

function togglePageBlockSelect(pId, isChecked) {
  if (!pageBarcodeState[pId]) pageBarcodeState[pId] = { selected: false, copies: 1 };
  pageBarcodeState[pId].selected = isChecked;

  var card = $(`#page_block_card_${pId}`);
  if (isChecked) card.addClass('selected');
  else card.removeClass('selected');

  updatePageBarcodeSummary();
}

function selectAllPageProducts(isSelected) {
  // Only touch products that currently match the category filter + search box —
  // "select all" should mean "select all of what I'm looking at", not every product in the shop.
  var searchVal = ($('#pageBarcodeSearch').val() || '').toLowerCase().trim();
  var catFilter = $('#pageCategoryFilter').val();

  ALL_PRODUCTS_PAGE.forEach(function(p) {
    var pId = p.product_id;
    var pCatId = String(p.category_id || '');
    if (catFilter !== '' && pCatId !== String(catFilter)) return;

    if (searchVal !== '') {
      var pName    = (p.product_name || '').toLowerCase();
      var pBarcode = (p.barcode && p.barcode.trim() !== '') ? p.barcode.trim().toLowerCase() : String(pId);
      var matchName = pName.includes(searchVal);
      var matchBar  = pBarcode.includes(searchVal);
      var matchId   = String(pId).includes(searchVal);
      if (!matchName && !matchBar && !matchId) return;
    }

    if (!pageBarcodeState[pId]) pageBarcodeState[pId] = { selected: false, copies: 1 };
    pageBarcodeState[pId].selected = isSelected;
  });
  renderPageBarcodeBlocks();
}

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

var BARCODE_SIZE_CONFIGS = {
  '40x30_vert': {
    pageCss: '@page { size: 40mm 30mm; margin: 0; }',
    pageBoxCss: 'width: 40mm; height: 30mm; max-width: 40mm; max-height: 30mm; page-break-after: always; break-after: page; page-break-inside: avoid; break-inside: avoid; overflow: hidden; margin: 0 auto; padding: 0; display: block;',
    labelCss: 'width: 100%; height: 100%; padding: 1mm 1.5mm; box-sizing: border-box; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; overflow: hidden;',
    shopTitleCss: 'font-size: 8px; font-weight: bold; line-height: 1; max-width: 37mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 0 1px 0; text-align: center;',
    prodTitleCss: 'font-size: 10.5px; font-weight: bold; line-height: 1.1; max-width: 37mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 0 1px 0; text-align: center;',
    priceTitleCss: 'font-size: 12.5px; font-weight: 800; line-height: 1; max-width: 37mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 1px 0 0 0; text-align: center;',
    maxBarcodeMm: 36,
    mode: 'single_roll'
  },
  '40x30_horiz': {
    pageCss: '@page { size: 40mm 30mm; margin: 0; }',
    pageBoxCss: 'width: 40mm; height: 30mm; max-width: 40mm; max-height: 30mm; page-break-after: always; break-after: page; page-break-inside: avoid; break-inside: avoid; overflow: hidden; margin: 0 auto; padding: 0; display: block;',
    labelCss: 'width: 100%; height: 100%; padding: 1mm 1.5mm; box-sizing: border-box; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; overflow: hidden;',
    shopTitleCss: 'font-size: 8px; font-weight: bold; line-height: 1; max-width: 37mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 0 1px 0; text-align: center;',
    prodTitleCss: 'font-size: 10.5px; font-weight: bold; line-height: 1.1; max-width: 37mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 0 1px 0; text-align: center;',
    priceTitleCss: 'font-size: 12.5px; font-weight: 800; line-height: 1; max-width: 37mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 1px 0 0 0; text-align: center;',
    maxBarcodeMm: 36,
    mode: 'single_roll'
  },
  '105x25_3up': {
    pageCss: '@page { size: 105mm 25mm; margin: 0; }',
    pageBoxCss: 'width: 32mm; height: 24.5mm;',
    labelCss: 'width: 32mm; height: 24.5mm; padding: 1.5mm 0.5mm 0.5mm 0.5mm; box-sizing: border-box; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; overflow: hidden;',
    shopTitleCss: 'font-size: 7px; font-weight: bold; line-height: 1; max-width: 31mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 0 0.5px 0; text-align: center;',
    prodTitleCss: 'font-size: 8px; font-weight: bold; line-height: 1.1; max-width: 31mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 0 0.5px 0; text-align: center;',
    priceTitleCss: 'font-size: 8.5px; font-weight: 800; line-height: 1; max-width: 31mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0.5px 0 0 0; text-align: center;',
    maxBarcodeMm: 31,
    mode: '3up_roll'
  },
  '50x30': {
    pageCss: '@page { size: 50mm 30mm; margin: 0; }',
    pageBoxCss: 'width: 50mm; height: 30mm; max-width: 50mm; max-height: 30mm; page-break-after: always; break-after: page; page-break-inside: avoid; break-inside: avoid; overflow: hidden; margin: 0 auto; padding: 0; display: block;',
    labelCss: 'width: 100%; height: 100%; padding: 1mm 1.5mm; box-sizing: border-box; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; overflow: hidden;',
    shopTitleCss: 'font-size: 9px; font-weight: bold; line-height: 1; max-width: 47mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 0 1px 0; text-align: center;',
    prodTitleCss: 'font-size: 11.5px; font-weight: bold; line-height: 1.1; max-width: 47mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 0 1px 0; text-align: center;',
    priceTitleCss: 'font-size: 13.5px; font-weight: 800; line-height: 1; max-width: 47mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 1px 0 0 0; text-align: center;',
    maxBarcodeMm: 46,
    mode: 'single_roll'
  },
  '30x20': {
    pageCss: '@page { size: 30mm 20mm; margin: 0; }',
    pageBoxCss: 'width: 30mm; height: 20mm; max-width: 30mm; max-height: 20mm; page-break-after: always; break-after: page; page-break-inside: avoid; break-inside: avoid; overflow: hidden; margin: 0 auto; padding: 0; display: block;',
    labelCss: 'width: 100%; height: 100%; padding: 0.5mm 0.8mm; box-sizing: border-box; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; overflow: hidden;',
    shopTitleCss: 'font-size: 7px; font-weight: bold; line-height: 1; max-width: 28mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 0 1px 0; text-align: center;',
    prodTitleCss: 'font-size: 8.5px; font-weight: bold; line-height: 1.05; max-width: 28mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 0 1px 0; text-align: center;',
    priceTitleCss: 'font-size: 9.5px; font-weight: 800; line-height: 1; max-width: 28mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 1px 0 0 0; text-align: center;',
    maxBarcodeMm: 27,
    mode: 'single_roll'
  },
  '50x40': {
    pageCss: '@page { size: 50mm 40mm; margin: 0; }',
    pageBoxCss: 'width: 50mm; height: 40mm; max-width: 50mm; max-height: 40mm; page-break-after: always; break-after: page; page-break-inside: avoid; break-inside: avoid; overflow: hidden; margin: 0 auto; padding: 0; display: block;',
    labelCss: 'width: 100%; height: 100%; padding: 1.5mm 2mm; box-sizing: border-box; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; overflow: hidden;',
    shopTitleCss: 'font-size: 10px; font-weight: bold; line-height: 1; max-width: 46mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 0 1px 0; text-align: center;',
    prodTitleCss: 'font-size: 12px; font-weight: bold; line-height: 1.1; max-width: 46mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 0 2px 0; text-align: center;',
    priceTitleCss: 'font-size: 14px; font-weight: 800; line-height: 1; max-width: 46mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 2px 0 0 0; text-align: center;',
    maxBarcodeMm: 46,
    mode: 'single_roll'
  },
  'a4': {
    pageCss: '@page { size: A4; margin: 8mm; }',
    pageBoxCss: '',
    labelCss: 'border: 1px dashed #cbd5e1; border-radius: 6px; padding: 2.5mm 2mm; min-height: 28mm; box-sizing: border-box; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; overflow: hidden;',
    shopTitleCss: 'font-size: 10px; font-weight: bold; line-height: 1.1; margin: 0 0 2px 0; text-align: center;',
    prodTitleCss: 'font-size: 12px; font-weight: bold; line-height: 1.15; margin: 0 0 2px 0; text-align: center;',
    priceTitleCss: 'font-size: 14px; font-weight: 800; line-height: 1; margin: 2px 0 0 0; text-align: center;',
    maxBarcodeMm: 50,
    mode: 'grid_a4'
  }
};

function executePageBarcodePrint() {
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

  var printDoc = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Print Barcodes - MiniPos</title>';
  printDoc += '<script src="' + basePath + 'assets/js/JsBarcode.all.min.js"><\/script>';
  printDoc += '<style>';
  printDoc += cfg.pageCss + ' ';
  printDoc += '@media print { @page { ' + (cfg.pageCss.replace('@page {', '').replace('}', '')) + ' } html, body { margin: 0 !important; padding: 0 !important; } } ';
  printDoc += '* { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; } ';
  printDoc += 'html, body { margin: 0 !important; padding: 0 !important; width: 100%; background: #ffffff; color: #000000; font-family: "Noto Sans Lao", "Souliyo", "Boon", Arial, sans-serif; } ';

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
  printDoc += '.barcode-num-text { font-family: monospace, "Courier New", Courier, sans-serif; font-size: 9.5px; font-weight: 700; letter-spacing: 1px; line-height: 1.1; max-width: ' + barcodeWidthMm + 'mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 1px 0 0 0; text-align: center; color: #000000; } ';
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
    var priceFormatted = Number(p.price || 0).toLocaleString('en-US') + ' ₭';

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
  printDoc += 'function calcBarcodeWidth(code, maxMm) {';
  printDoc += '  var len = String(code || "").length;';
  printDoc += '  var modules = 35 + 11 * Math.max(len, 1);';
  printDoc += '  var targetPx = ((maxMm || 36) / 25.4) * 96;';
  printDoc += '  var w = targetPx / modules;';
  printDoc += '  if (w > 1.7) w = 1.7;';
  printDoc += '  if (w < 0.75) w = 0.75;';
  printDoc += '  return Math.round(w * 100) / 100;';
  printDoc += '}';
  printDoc += 'function doPrint() {';
  printDoc += '  var svgs = document.querySelectorAll(".barcode-svg");';
  printDoc += '  var maxMm = ' + barcodeWidthMm + ';';
  printDoc += '  var heightPx = ' + Math.round(barcodeHeightMm * 3.78) + ';';
  printDoc += '  svgs.forEach(function(el) {';
  printDoc += '    var code = el.getAttribute("data-barcode");';
  printDoc += '    var bWidth = calcBarcodeWidth(code, maxMm);';
  printDoc += '    try {';
  printDoc += '      JsBarcode(el, code, {';
  printDoc += '        format: "CODE128",';
  printDoc += '        width: bWidth,';
  printDoc += '        height: heightPx,';
  printDoc += '        displayValue: false,';
  printDoc += '        margin: 0';
  printDoc += '      });';
  printDoc += '    } catch (e) { console.error(e); }';
  printDoc += '  });';
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
}

$(document).ready(function() {
  initPageBarcodeState();
  renderPageBarcodeBlocks();
});
</script>