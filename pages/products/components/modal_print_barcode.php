<?php
// ດຶງຂໍ້ມູນຊື່ຮ້ານ (ຊື່ຮ້ານແມ່ນຂໍ້ມູນຮ່ວມກັນ ມາຈາກສາຂາຫຼັກ)
$company_name = getCompanyInfoForBranch($pdo, getActiveStoreId($pdo))['com_name_la'] ?? 'MiniPos';
if (empty($company_name)) $company_name = 'MiniPos';

// ຈັດຮູບແບບຂໍ້ມູນສິນຄ້າ ສຳລັບສົ່ງໃຫ້ JavaScript ໃຊ້ງານ
$products_for_js = [];
if (!empty($products) && is_array($products)) {
    foreach ($products as $p) {
        $p_copy = $p;
        $p_copy['img_path'] = !empty($p['img_url']) ? getProductImagePath($p['img_url']) : '../../assets/img/image.jpg';
        $products_for_js[] = $p_copy;
    }
}
?>


<!-- Modal: Print Barcode (Multi-Item Block View) -->
<div class="modal fade" id="printBarcodeModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden; max-height: 90vh;">
      
      <!-- HEADER -->
      <div class="modal-header barcode-modal-header text-white py-3 px-4 d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
        <h5 class="modal-title font-weight-bold mb-0" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif; font-size: 1.15rem;">
          <i class="fas fa-barcode mr-2"></i> ປິ່ນສະຕິກເກີບາໂຄ້ດ (ເລືອກລາຍການ ແລະ ຈຳນວນດວງ)
        </h5>
        <div class="d-flex align-items-center" style="gap: 12px;">
          <button type="button" class="btn btn-sm btn-light font-weight-bold text-primary shadow-xs px-3" style="border-radius: 6px; font-size: 0.85rem;" onclick="executeBarcodePrint()">
            <i class="fas fa-print mr-1"></i> ປິ່ນບາໂຄ້ດ
          </button>
          <button type="button" class="close text-white opacity-100 p-0 m-0" data-dismiss="modal" aria-label="Close" style="font-size: 1.5rem;">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      </div>

      <!-- TOOLBAR: Global Controls -->
      <div class="bg-light border-bottom p-3">
        <div class="row align-items-center gy-2">
          
          <!-- Column 1: Search Box -->
          <div class="col-md-3 col-sm-6 mb-2 mb-md-0">
            <div class="input-group input-group-sm">
              <div class="input-group-prepend">
                <span class="input-group-text bg-white border-right-0" style="border-radius: 8px 0 0 8px;">
                  <i class="fas fa-search text-muted"></i>
                </span>
              </div>
              <input type="text" id="modalBarcodeSearch" class="form-control border-left-0" 
                     placeholder="ຄົ້ນຫາຊື່ສິນຄ້າ ຫຼື ບາໂຄ້ດ..." 
                     style="border-radius: 0 8px 8px 0; font-size: 0.88rem; height: 34px;" 
                     onkeyup="filterModalBarcodeBlocks()">
            </div>
          </div>

          <!-- Column 2: Selection & Batch Quantity Buttons -->
          <div class="col-md-5 col-sm-6 mb-2 mb-md-0 d-flex flex-wrap align-items-center" style="gap: 6px;">
            <button type="button" class="btn btn-sm btn-outline-primary font-weight-bold" style="border-radius: 6px; height: 34px; font-size: 0.82rem;" onclick="selectAllBarcodeProducts(true)">
              <i class="fas fa-check-square mr-1"></i> ເລືອກທັງໝົດ
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary font-weight-bold" style="border-radius: 6px; height: 34px; font-size: 0.82rem;" onclick="selectAllBarcodeProducts(false)">
              <i class="far fa-square mr-1"></i> ຍົກເລີກທັງໝົດ
            </button>
            
            <!-- Quick Batch Quantity Setter -->
            <div class="btn-group btn-group-sm ml-auto ml-md-1">
              <span class="btn btn-sm btn-light border disabled font-weight-bold text-dark px-2" style="font-size: 0.78rem; height: 34px; display: flex; align-items: center;">ຕັ້ງດວງ:</span>
              <button type="button" class="btn btn-sm btn-light border font-weight-bold" style="height: 34px;" onclick="setAllSelectedCopies(1)">1</button>
              <button type="button" class="btn btn-sm btn-light border font-weight-bold" style="height: 34px;" onclick="setAllSelectedCopies(2)">2</button>
              <button type="button" class="btn btn-sm btn-light border font-weight-bold" style="height: 34px;" onclick="setAllSelectedCopies(5)">5</button>
              <button type="button" class="btn btn-sm btn-light border font-weight-bold" style="height: 34px;" onclick="setAllSelectedCopies(10)">10</button>
            </div>
          </div>

          <!-- Column 3: Paper Size & Prominent Print Action Button -->
          <div class="col-md-4 col-12 d-flex align-items-center justify-content-md-end" style="gap: 8px;">
            <select id="print_size" class="form-control form-control-sm font-weight-bold text-dark" style="border-radius: 6px; height: 34px; max-width: 250px;" onchange="renderModalBarcodeBlocks()">
              <option value="40x30_vert" selected>40mm x 30mm (ລວງຕັ້ງ - ດວງດ່ຽວ)</option>
              <option value="40x30_horiz">40mm x 30mm (ລວງນອນ - ດວງດ່ຽວ)</option>
              <option value="105x25_3up">105mm x 25mm (3 ຕາລວງນອນ)</option>
              <option value="50x30">50mm x 30mm (ມ້ວນກາງ)</option>
              <option value="30x20">30mm x 20mm (ມ້ວນນ້ອຍ)</option>
              <option value="50x40">50mm x 40mm (ມ້ວນໃຫຍ່)</option>
              <option value="a4">ເຈ້ຍ A4 (ຕາຕະລາງ)</option>
            </select>

            <button type="button" class="btn btn-sm btn-info font-weight-bold shadow-sm px-3 text-white" style="border-radius: 6px; height: 34px; white-space: nowrap; font-size: 0.85rem;" onclick="executeBarcodePrint()">
              <i class="fas fa-print mr-1"></i> ປິ່ນບາໂຄ້ດ
            </button>
          </div>

        </div>

        <!-- Display Toggles Row -->
        <div class="d-flex flex-wrap align-items-center mt-2 pt-2 border-top" style="gap: 16px;">
          <small class="font-weight-bold text-secondary mr-1"><i class="fas fa-sliders-h mr-1"></i> ສະແດງຜົນໃນສະຕິກເກີ:</small>
          <div class="custom-control custom-checkbox custom-control-inline mb-0">
            <input type="checkbox" class="custom-control-input" id="opt_show_shop" checked onchange="renderModalBarcodeBlocks()">
            <label class="custom-control-label font-weight-bold text-dark" for="opt_show_shop" style="font-size: 0.85rem; cursor: pointer;">
              ຊື່ຮ້ານ (<?php echo htmlspecialchars($company_name); ?>)
            </label>
          </div>
          <div class="custom-control custom-checkbox custom-control-inline mb-0">
            <input type="checkbox" class="custom-control-input" id="opt_show_name" checked onchange="renderModalBarcodeBlocks()">
            <label class="custom-control-label font-weight-bold text-dark" for="opt_show_name" style="font-size: 0.85rem; cursor: pointer;">
              ຊື່ສິນຄ້າ
            </label>
          </div>
          <div class="custom-control custom-checkbox custom-control-inline mb-0">
            <input type="checkbox" class="custom-control-input" id="opt_show_price" checked onchange="renderModalBarcodeBlocks()">
            <label class="custom-control-label font-weight-bold text-dark" for="opt_show_price" style="font-size: 0.85rem; cursor: pointer;">
              ລາຄາຂາຍ
            </label>
          </div>
        </div>

      </div>

      <!-- BODY: Product Item Blocks Container -->
      <div class="modal-body p-3 bg-light" style="min-height: 350px;">
        <div id="modalBarcodeBlocksContainer" class="row">
          <!-- Dynamic product blocks rendered by JavaScript -->
        </div>
      </div>

      <!-- FOOTER (Sticky & Always Visible) -->
      <div class="modal-footer bg-white border-top py-2.5 px-4 d-flex justify-content-between align-items-center" style="position: sticky; bottom: 0; z-index: 1050; box-shadow: 0 -4px 12px rgba(0,0,0,0.05);">
        <!-- Left: Summary Info -->
        <div id="barcodeSelectionSummary" class="font-weight-bold text-primary" style="font-size: 0.95rem;">
          <i class="fas fa-info-circle mr-1"></i> ເລືອກແລ້ວ: 0 ລາຍການ (ລວມ 0 ດວງ)
        </div>

        <!-- Right: Actions -->
        <div>
          <button type="button" class="btn btn-light font-weight-bold px-4 mr-2" style="border-radius: 8px;" data-dismiss="modal">ຍົກເລີກ</button>
          <button type="button" class="btn btn-info font-weight-bold px-4 shadow-sm text-white" style="border-radius: 8px;" onclick="executeBarcodePrint()">
            <i class="fas fa-print mr-1"></i> ປິ່ນບາໂຄ້ດ
          </button>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Hidden Iframe for printing -->
<iframe id="barcodePrintIframe" style="display: none; width: 0; height: 0; border: none;"></iframe>

<script src="<?php echo $base_path; ?>assets/js/JsBarcode.all.min.js"></script>
<script>
var SHOP_NAME = <?php echo json_encode($company_name); ?>;
var ALL_BARCODE_PRODUCTS = <?php echo json_encode($products_for_js); ?>;

// State objects for each product block
// state structure: { [productId]: { selected: boolean, copies: number } }
var barcodeProductState = {};

// Initialize state
function initBarcodeState() {
  ALL_BARCODE_PRODUCTS.forEach(function(p) {
    if (!barcodeProductState[p.product_id]) {
      barcodeProductState[p.product_id] = {
        selected: false,
        copies: 1
      };
    }
  });
}

/**
 * Open print modal
 * @param {Object|null} targetProduct - optional single product object to pre-select
 */
function openPrintBarcodeModal(targetProduct) {
  initBarcodeState();

  if (targetProduct && targetProduct.product_id) {
    // Unselect all others, select targetProduct
    Object.keys(barcodeProductState).forEach(function(id) {
      barcodeProductState[id].selected = (Number(id) === Number(targetProduct.product_id));
      if (Number(id) === Number(targetProduct.product_id)) {
        barcodeProductState[id].copies = 1;
      }
    });
  } else {
    // Select all products by default when opened from general print button
    Object.keys(barcodeProductState).forEach(function(id) {
      barcodeProductState[id].selected = true;
    });
  }

  $('#modalBarcodeSearch').val('');
  $('#printBarcodeModal').modal('show');

  // Render blocks after modal is shown so SVG dimensions calculate properly
  setTimeout(function() {
    renderModalBarcodeBlocks();
  }, 150);
}

// Render dynamic product blocks
function renderModalBarcodeBlocks() {
  var container = $('#modalBarcodeBlocksContainer');
  container.empty();

  var searchVal = ($('#modalBarcodeSearch').val() || '').toLowerCase().trim();
  var showShop  = $('#opt_show_shop').is(':checked');
  var showName  = $('#opt_show_name').is(':checked');
  var showPrice = $('#opt_show_price').is(':checked');

  var visibleCount = 0;

  ALL_BARCODE_PRODUCTS.forEach(function(p) {
    var pId = p.product_id;
    var st  = barcodeProductState[pId] || { selected: false, copies: 1 };

    var pName    = p.product_name || '';
    var pBarcode = (p.barcode && p.barcode.trim() !== '') ? p.barcode.trim() : String(pId);
    var pPrice   = Number(p.price || 0).toLocaleString('en-US') + ' ₭';
    var pUnit    = p.unit || '';

    // Search filter matching
    if (searchVal !== '') {
      var matchName = pName.toLowerCase().includes(searchVal);
      var matchBar  = pBarcode.toLowerCase().includes(searchVal);
      var matchId   = String(pId).includes(searchVal);
      if (!matchName && !matchBar && !matchId) {
        return; // skip non-matching
      }
    }

    visibleCount++;

    var isSelected = st.selected;
    var copies = st.copies || 1;

    var blockHtml = `
      <div class="col-xl-4 col-md-6 col-12 mb-3">
        <div class="barcode-block-card p-3 ${isSelected ? 'selected' : ''}" id="block_card_${pId}">
          
          <!-- Block Header: Checkbox & Product Title -->
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center text-truncate" style="gap: 8px;">
              <input type="checkbox" class="barcode-block-checkbox" id="chk_block_${pId}" 
                     ${isSelected ? 'checked' : ''} onchange="toggleProductBlockSelect(${pId}, this.checked)">
              <label for="chk_block_${pId}" class="font-weight-bold text-dark mb-0 text-truncate" style="font-size: 0.92rem; cursor: pointer;" title="${pName}">
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
                   style="max-height: 52px; object-fit: cover;" 
                   onerror="this.onerror=null; this.src='../../assets/img/image.jpg';">
              ${showPrice ? `<div class="font-weight-bold text-success" style="font-size: 0.82rem;">${pPrice}</div>` : ''}
            </div>

            <!-- Right: Barcode Preview SVG -->
            <div class="col-8 text-center pl-1">
              <div class="barcode-preview-box">
                ${showShop ? `<div class="text-secondary font-weight-bold text-truncate" style="font-size: 0.72rem; line-height: 1;">${SHOP_NAME}</div>` : ''}
                ${showName ? `<div class="text-dark font-weight-bold text-truncate" style="font-size: 0.76rem; line-height: 1.1; margin-top: 1px;">${pName}</div>` : ''}
                
                <div class="my-1 d-flex align-items-center justify-content-center" style="max-width: 100%; overflow: hidden;">
                  <svg id="svg_block_${pId}" style="max-width: 100%;"></svg>
                </div>

                ${showPrice ? `<div class="font-weight-bold text-dark" style="font-size: 0.8rem; line-height: 1;">${pPrice}</div>` : ''}
              </div>
            </div>

          </div>

          <!-- Block Footer: Quantity Control (ລາຍການລະຈັກອັນ) -->
          <div class="d-flex align-items-center justify-content-between pt-2 border-top mt-2">
            <small class="font-weight-bold text-dark" style="font-size: 0.82rem;">
              <i class="fas fa-copy text-primary mr-1"></i> ຈຳນວນດວງ:
            </small>

            <div class="d-flex align-items-center" style="gap: 4px;">
              <button type="button" class="btn btn-sm btn-outline-secondary barcode-qty-btn" onclick="changeBlockCopies(${pId}, -1)">
                <i class="fas fa-minus" style="font-size: 0.7rem;"></i>
              </button>
              
              <input type="number" id="input_copies_${pId}" class="form-control barcode-qty-input" 
                     value="${copies}" min="1" max="500" 
                     oninput="updateBlockCopies(${pId}, this.value)">

              <button type="button" class="btn btn-sm btn-outline-secondary barcode-qty-btn" onclick="changeBlockCopies(${pId}, 1)">
                <i class="fas fa-plus" style="font-size: 0.7rem;"></i>
              </button>
            </div>

            <!-- Quick Copies Shortcuts for this block -->
            <div class="d-none d-sm-flex" style="gap: 2px;">
              <button type="button" class="btn btn-xs btn-light border py-0 px-1 font-weight-bold" style="font-size:0.7rem;" onclick="setBlockCopies(${pId}, 1)">1</button>
              <button type="button" class="btn btn-xs btn-light border py-0 px-1 font-weight-bold" style="font-size:0.7rem;" onclick="setBlockCopies(${pId}, 5)">5</button>
              <button type="button" class="btn btn-xs btn-light border py-0 px-1 font-weight-bold" style="font-size:0.7rem;" onclick="setBlockCopies(${pId}, 10)">10</button>
            </div>
          </div>

        </div>
      </div>
    `;

    container.append(blockHtml);

    // Generate SVG barcode for this block
    try {
      JsBarcode(`#svg_block_${pId}`, pBarcode, {
        format: "CODE128",
        width: 1.3,
        height: 32,
        displayValue: true,
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
      <div class="col-12 text-center py-5 text-muted">
        <i class="fas fa-search fa-3x mb-3 text-secondary" style="opacity: 0.5;"></i>
        <h5>ບໍ່ພົບລາຍການສິນຄ້າທີ່ກົງກັບຄຳຄົ້ນຫາ</h5>
      </div>
    `);
  }

  updateBarcodeSummaryInfo();
}

// Filter blocks on search input
function filterModalBarcodeBlocks() {
  renderModalBarcodeBlocks();
}

// Toggle selection for a single product block
function toggleProductBlockSelect(pId, isChecked) {
  if (!barcodeProductState[pId]) barcodeProductState[pId] = { selected: false, copies: 1 };
  barcodeProductState[pId].selected = isChecked;

  var card = $(`#block_card_${pId}`);
  if (isChecked) {
    card.addClass('selected');
  } else {
    card.removeClass('selected');
  }

  updateBarcodeSummaryInfo();
}

// Select All / Deselect All
function selectAllBarcodeProducts(isSelected) {
  ALL_BARCODE_PRODUCTS.forEach(function(p) {
    var pId = p.product_id;
    if (!barcodeProductState[pId]) barcodeProductState[pId] = { selected: false, copies: 1 };
    barcodeProductState[pId].selected = isSelected;
  });
  renderModalBarcodeBlocks();
}

// Set copies for a block via +/- button
function changeBlockCopies(pId, delta) {
  if (!barcodeProductState[pId]) barcodeProductState[pId] = { selected: true, copies: 1 };
  var current = barcodeProductState[pId].copies || 1;
  var newVal = current + delta;
  if (newVal < 1) newVal = 1;
  if (newVal > 500) newVal = 500;
  
  barcodeProductState[pId].copies = newVal;
  barcodeProductState[pId].selected = true; // Auto select if user interacts with quantity
  $(`#chk_block_${pId}`).prop('checked', true);
  $(`#block_card_${pId}`).addClass('selected');
  $(`#input_copies_${pId}`).val(newVal);

  updateBarcodeSummaryInfo();
}

// Set copies directly for a block
function setBlockCopies(pId, num) {
  if (!barcodeProductState[pId]) barcodeProductState[pId] = { selected: true, copies: 1 };
  barcodeProductState[pId].copies = num;
  barcodeProductState[pId].selected = true;
  $(`#chk_block_${pId}`).prop('checked', true);
  $(`#block_card_${pId}`).addClass('selected');
  $(`#input_copies_${pId}`).val(num);

  updateBarcodeSummaryInfo();
}

// Update copies on direct input change
function updateBlockCopies(pId, val) {
  var num = parseInt(val) || 1;
  if (num < 1) num = 1;
  if (num > 500) num = 500;
  if (!barcodeProductState[pId]) barcodeProductState[pId] = { selected: true, copies: 1 };
  barcodeProductState[pId].copies = num;
  barcodeProductState[pId].selected = true;
  $(`#chk_block_${pId}`).prop('checked', true);
  $(`#block_card_${pId}`).addClass('selected');

  updateBarcodeSummaryInfo();
}

// Batch set copies for all selected products
function setAllSelectedCopies(num) {
  ALL_BARCODE_PRODUCTS.forEach(function(p) {
    var pId = p.product_id;
    if (barcodeProductState[pId] && barcodeProductState[pId].selected) {
      barcodeProductState[pId].copies = num;
      $(`#input_copies_${pId}`).val(num);
    }
  });
  updateBarcodeSummaryInfo();
}

// Update summary text at footer
function updateBarcodeSummaryInfo() {
  var selectedCount = 0;
  var totalStickers = 0;

  ALL_BARCODE_PRODUCTS.forEach(function(p) {
    var st = barcodeProductState[p.product_id];
    if (st && st.selected) {
      selectedCount++;
      totalStickers += (st.copies || 1);
    }
  });

  $('#barcodeSelectionSummary').html(
    `<i class="fas fa-check-circle text-success mr-1"></i> ເລືອກແລ້ວ: <strong>${selectedCount}</strong> ລາຍການ 
     <span class="mx-1">|</span> 
     <i class="fas fa-print text-info mr-1"></i> ລວມທັງໝົດ: <strong class="text-primary">${totalStickers}</strong> ດວງ`
  );
}

// ====== ຟັງຊັນສັ່ງປິ່ນບາໂຄ້ດ (Execute Print) ======
var MODAL_BARCODE_SIZE_CONFIGS = {
  '40x30_vert': {
    pageCss: '@page { size: 40mm 30mm; margin: 0; }',
    pageBoxCss: 'width: 40mm; height: 30mm; max-width: 40mm; max-height: 30mm; page-break-after: always; break-after: page; page-break-inside: avoid; break-inside: avoid; overflow: hidden; margin: 0 auto; padding: 0; display: block;',
    labelCss: 'width: 100%; height: 100%; padding: 1mm 1.5mm; box-sizing: border-box; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; overflow: hidden;',
    shopTitleCss: 'font-size: 8px; font-weight: bold; line-height: 1; max-width: 37mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 0 1px 0; text-align: center;',
    prodTitleCss: 'font-size: 10.5px; font-weight: bold; line-height: 1.1; max-width: 37mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 0 1px 0; text-align: center;',
    priceTitleCss: 'font-size: 12.5px; font-weight: 800; line-height: 1; max-width: 37mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 1px 0 0 0; text-align: center;',
    maxBarcodeMm: 36,
    barcodeHeight: 24,
    barcodeFontSize: 8.5,
    maxSvgHeight: 11,
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
    barcodeHeight: 24,
    barcodeFontSize: 8.5,
    maxSvgHeight: 11,
    mode: 'single_roll'
  },
  '40x30': {
    pageCss: '@page { size: 40mm 30mm; margin: 0; }',
    pageBoxCss: 'width: 40mm; height: 30mm; max-width: 40mm; max-height: 30mm; page-break-after: always; break-after: page; page-break-inside: avoid; break-inside: avoid; overflow: hidden; margin: 0 auto; padding: 0; display: block;',
    labelCss: 'width: 100%; height: 100%; padding: 1mm 1.5mm; box-sizing: border-box; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; overflow: hidden;',
    shopTitleCss: 'font-size: 8px; font-weight: bold; line-height: 1; max-width: 37mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 0 1px 0; text-align: center;',
    prodTitleCss: 'font-size: 10.5px; font-weight: bold; line-height: 1.1; max-width: 37mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 0 1px 0; text-align: center;',
    priceTitleCss: 'font-size: 12.5px; font-weight: 800; line-height: 1; max-width: 37mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 1px 0 0 0; text-align: center;',
    maxBarcodeMm: 36,
    barcodeHeight: 24,
    barcodeFontSize: 8.5,
    maxSvgHeight: 11,
    mode: 'single_roll'
  },
  '105x25_3up': {
    pageCss: '@page { size: 105mm 25mm; margin: 0; }',
    pageBoxCss: 'width: 105mm; height: 25mm;',
    labelCss: 'padding: 0.5mm 1mm; box-sizing: border-box; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; overflow: hidden;',
    shopTitleCss: 'font-size: 8px; font-weight: bold; line-height: 1; max-width: 32mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 0 1px 0; text-align: center;',
    prodTitleCss: 'font-size: 9.5px; font-weight: bold; line-height: 1.05; max-width: 32mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 0 1px 0; text-align: center;',
    priceTitleCss: 'font-size: 11px; font-weight: 800; line-height: 1; max-width: 32mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 1px 0 0 0; text-align: center;',
    maxBarcodeMm: 31,
    barcodeHeight: 20,
    barcodeFontSize: 8,
    maxSvgHeight: 10,
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
    barcodeHeight: 26,
    barcodeFontSize: 9,
    maxSvgHeight: 12,
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
    barcodeHeight: 18,
    barcodeFontSize: 7.5,
    maxSvgHeight: 9,
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
    barcodeHeight: 32,
    barcodeFontSize: 10,
    maxSvgHeight: 15,
    mode: 'single_roll'
  },
  'a4': {
    pageCss: '@page { size: A4; margin: 8mm; }',
    pageBoxCss: '',
    labelCss: 'border: 1px dashed #cbd5e1; border-radius: 6px; padding: 2.5mm 2mm; min-height: 28mm; box-sizing: border-box; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; overflow: hidden;',
    shopTitleCss: 'font-size: 11px; font-weight: bold; line-height: 1.1; margin: 0 0 2px 0; text-align: center;',
    prodTitleCss: 'font-size: 12px; font-weight: bold; line-height: 1.15; margin: 0 0 2px 0; text-align: center;',
    priceTitleCss: 'font-size: 14px; font-weight: 800; line-height: 1; margin: 2px 0 0 0; text-align: center;',
    maxBarcodeMm: 50,
    barcodeHeight: 32,
    barcodeFontSize: 10,
    maxSvgHeight: 14,
    mode: 'grid_a4'
  }
};

function executeBarcodePrint() {
  var size      = $('#print_size').val();
  var showShop  = $('#opt_show_shop').is(':checked');
  var showName  = $('#opt_show_name').is(':checked');
  var showPrice = $('#opt_show_price').is(':checked');

  // Collect items to print
  var itemsToPrint = [];
  var totalStickers = 0;

  ALL_BARCODE_PRODUCTS.forEach(function(p) {
    var st = barcodeProductState[p.product_id];
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
      title: 'ບໍ່ມີລາຍການຖືກເລືອກ',
      text: 'ກະລຸນາເລືອກລາຍການສິນຄ້າ ແລະ ກຳນົດຈຳນວນດວງທີ່ຈະພິມຢ່າງນ້ອຍ 1 ດວງ!',
      confirmButtonColor: '#0284c7',
      confirmButtonText: 'ຕົກລົງ'
    });
    return;
  }

  var cfg = MODAL_BARCODE_SIZE_CONFIGS[size] || MODAL_BARCODE_SIZE_CONFIGS['40x30_vert'] || MODAL_BARCODE_SIZE_CONFIGS['40x30_horiz'];
  var basePath = '<?php echo $base_path; ?>';

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
    printDoc += '.row-container { display: grid; grid-template-columns: repeat(3, 1fr); width: 105mm; height: 25mm; gap: 2mm; padding: 0.5mm; box-sizing: border-box; page-break-after: always; break-after: page; page-break-inside: avoid; break-inside: avoid; } ';
    printDoc += '.row-container:last-child { page-break-after: auto !important; break-after: auto !important; } ';
    printDoc += '.barcode-label { ' + cfg.labelCss + ' } ';
  } else {
    printDoc += '.label-container { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6mm; padding: 4mm; } ';
    printDoc += '.barcode-label { ' + cfg.labelCss + ' page-break-inside: avoid; break-inside: avoid; } ';
  }

  printDoc += '.shop-title { ' + cfg.shopTitleCss + ' } ';
  printDoc += '.prod-title { ' + cfg.prodTitleCss + ' } ';
  printDoc += '.barcode-svg-wrap { width: 100%; display: flex; justify-content: center; align-items: center; margin: 0; padding: 0; overflow: hidden; } ';
  printDoc += '.barcode-svg { max-width: ' + (cfg.maxBarcodeMm || 36) + 'mm; max-height: 8.5mm; height: auto; display: block; margin: 0 auto; } ';
  printDoc += '.barcode-num-text { font-family: monospace, "Courier New", Courier, sans-serif; font-size: 9.5px; font-weight: 700; letter-spacing: 1px; line-height: 1.1; max-width: ' + (cfg.maxBarcodeMm || 36) + 'mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 1px 0 0 0; text-align: center; color: #000000; } ';
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
      contentHtml += '<div class="barcode-num-text">' + escapeHtml(barcodeVal) + '</div>';
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
      printDoc += allStickerElements[k] || '<div class="barcode-label" style="visibility:hidden;"></div>';
      printDoc += allStickerElements[k + 1] || '<div class="barcode-label" style="visibility:hidden;"></div>';
      printDoc += allStickerElements[k + 2] || '<div class="barcode-label" style="visibility:hidden;"></div>';
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
  printDoc += '  var maxMm = ' + (cfg.maxBarcodeMm || 36) + ';';
  printDoc += '  svgs.forEach(function(el) {';
  printDoc += '    var code = el.getAttribute("data-barcode");';
  printDoc += '    var bWidth = calcBarcodeWidth(code, maxMm);';
  printDoc += '    try {';
  printDoc += '      JsBarcode(el, code, {';
  printDoc += '        format: "CODE128",';
  printDoc += '        width: bWidth,';
  printDoc += '        height: 20,';
  printDoc += '        displayValue: false,';
  printDoc += '        margin: 0';
  printDoc += '      });';
  printDoc += '    } catch (e) { console.error(e); }';
  printDoc += '  });';
  printDoc += '  setTimeout(function() { window.focus(); window.print(); }, 250);';
  printDoc += '}';
  printDoc += 'if (document.readyState === "complete") { doPrint(); } else { window.onload = doPrint; }';
  printDoc += '<\/script></body></html>';

  var iframe = document.getElementById('barcodePrintIframe');
  var ifrDoc = iframe.contentWindow || iframe.contentDocument;
  if (ifrDoc.document) ifrDoc = ifrDoc.document;
  ifrDoc.open();
  ifrDoc.write(printDoc);
  ifrDoc.close();
}
</script>
