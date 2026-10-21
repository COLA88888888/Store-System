<?php
/*
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * JS ຂອງໜ້າພິມບາໂຄ້ດ: ເລືອກສິນຄ້າ, ຈຳນວນສຳເນົາ ແລະ ສັ່ງພິມ.
 *   - js/print_execute.php : ສັ່ງພິມບາໂຄ້ດ
 */
?>
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
        fontSize: 12,
        fontOptions: "bold",
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

// ພິມແລ້ວ: ເອົາເຄື່ອງໝາຍຕິກອອກທັງໝົດ (ຈຳນວນດວງກັບເປັນ 1) — ເອີ້ນຈາກ js/print_execute.php ແລະ js/print_bluetooth.php
function clearPageBarcodeSelection() {
  Object.keys(pageBarcodeState).forEach(function(pId) {
    pageBarcodeState[pId].selected = false;
    pageBarcodeState[pId].copies = 1;
  });
  renderPageBarcodeBlocks();
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

<?php require __DIR__ . '/js/print_execute.php'; /* ສັ່ງພິມບາໂຄ້ດ */ ?>
<?php require __DIR__ . '/js/print_bluetooth.php'; /* ພິມບາໂຄ້ດຜ່ານເຄື່ອງພິມ Bluetooth */ ?>
$(document).ready(function() {
  initPageBarcodeState();
  renderPageBarcodeBlocks();
});
</script>