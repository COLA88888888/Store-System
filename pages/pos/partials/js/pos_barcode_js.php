<script>
// --- BARCODE SCANNER, SEARCH & CATEGORY FILTERING ---
var selectedCategoryPillId = '';
var filterGridTimer = null;

function playPosBeep() {
  try {
    var ctx = new (window.AudioContext || window.webkitAudioContext)();
    var osc = ctx.createOscillator();
    var gain = ctx.createGain();
    osc.type = 'sine';
    osc.frequency.setValueAtTime(1200, ctx.currentTime);
    gain.gain.setValueAtTime(0.15, ctx.currentTime);
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.start();
    osc.stop(ctx.currentTime + 0.08);
  } catch(e) {}
}

// --- SMART BARCODE FOCUS & SCANNER TRAP ---
function ensureBarcodeInputFocused() {
  var activeEl = document.activeElement;
  if (activeEl && (
      activeEl.tagName === 'INPUT' || 
      activeEl.tagName === 'TEXTAREA' || 
      activeEl.tagName === 'SELECT' || 
      activeEl.isContentEditable ||
      $(activeEl).closest('.modal, .swal2-container').length > 0
  )) {
    return;
  }
  var barcodeInput = document.getElementById('barcodeInput');
  if (barcodeInput && activeEl !== barcodeInput) {
    barcodeInput.setAttribute('inputmode', 'none'); // ໂຟກັສໂດຍໂປຣແກຣມ → ບໍ່ເດັ້ງແປ້ນພິມ
    barcodeInput.focus();
  }
}

// ຈໍສຳພັດ: ຊ່ອງສະແກນໃຊ້ inputmode="none" ເພື່ອໃຫ້ໂຟກັສຮັບບາໂຄ້ດໄດ້ທັນທີ ໂດຍບໍ່ເດັ້ງແປ້ນພິມໜ້າຈໍ.
// ເປີດແປ້ນພິມສະເພາະຕອນຜູ້ໃຊ້ແຕະໃສ່ຊ່ອງເອງ (ເພື່ອພິມຊອກຊື່ສິນຄ້າ), ແລ້ວປິດຄືນເມື່ອອອກຈາກຊ່ອງ.
(function() {
  var input = document.getElementById('barcodeInput');
  if (!input) return;

  input.addEventListener('pointerdown', function() {
    if (input.getAttribute('inputmode') !== 'none') return;
    input.setAttribute('inputmode', 'text');
    // ຖ້າໂຟກັສຢູ່ແລ້ວ ຕ້ອງ blur/focus ໃໝ່ ບຣາວເຊີຈຶ່ງຍອມເປີດແປ້ນພິມ
    if (document.activeElement === input) {
      input.blur();
      setTimeout(function() { input.focus(); }, 0);
    }
  });
  input.addEventListener('blur', function() {
    setTimeout(function() {
      if (document.activeElement !== input) input.setAttribute('inputmode', 'none');
    }, 0);
  });

  // ເຂົ້າໜ້າມາແລ້ວພ້ອມສະແກນເລີຍ — ດຶງໂຟກັສເຂົ້າ iframe ແລະ ຊ່ອງສະແກນ
  function grabFocus() {
    try { window.focus(); } catch (e) {}
    input.setAttribute('inputmode', 'none');
    input.focus({ preventScroll: true });
  }
  grabFocus();
  window.addEventListener('load', function() { setTimeout(grabFocus, 100); });
  window.addEventListener('pageshow', function() { setTimeout(grabFocus, 100); });
})();

// Automatically catch scanner typing or refocus barcode input when user scans/types outside inputs
$(document).on('keydown', function(e) {
  var activeEl = document.activeElement;
  var isInputOrModal = activeEl && (
      activeEl.tagName === 'INPUT' || 
      activeEl.tagName === 'TEXTAREA' || 
      activeEl.tagName === 'SELECT' || 
      activeEl.isContentEditable ||
      $(activeEl).closest('.modal, .swal2-container').length > 0
  );

  if (!isInputOrModal && !e.ctrlKey && !e.altKey && !e.metaKey) {
    var barcodeInput = document.getElementById('barcodeInput');
    if (barcodeInput && activeEl !== barcodeInput) {
      barcodeInput.focus();
    }
  }
});

// Auto-refocus on barcode box when clicking on neutral background areas
$(document).on('click', function(e) {
  if ($(e.target).closest('input, textarea, select, button, a, .modal, .swal2-container, .product-card').length === 0) {
    setTimeout(ensureBarcodeInputFocused, 50);
  }
});

// ຈັບຈັງຫວະການພິມ: ເຄື່ອງສະແກນພິມໄວຫຼາຍ (ຫ່າງກັນບໍ່ເຖິງ ~40ms ຕໍ່ຕົວ), ຄົນພິມຊ້າກວ່າ.
// ໃຊ້ແຍກ "ສະແກນ" ອອກຈາກ "ພິມຊອກຊື່ສິນຄ້າເປັນພາສາລາວ".
var barcodeKeyBurst = { start: 0, last: 0, count: 0 };
$('#barcodeInput').on('keydown', function(e) {
  if (!e.key || e.key.length !== 1 || e.ctrlKey || e.altKey || e.metaKey) return;
  var now = Date.now();
  if (now - barcodeKeyBurst.last > 60) {
    barcodeKeyBurst.start = now;
    barcodeKeyBurst.count = 0;
  }
  barcodeKeyBurst.count++;
  barcodeKeyBurst.last = now;
});

function isLikelyScannerInput() {
  var b = barcodeKeyBurst;
  if (b.count < 4) return false;
  if (Date.now() - b.last > 150) return false; // Enter ຕ້ອງຕາມມາທັນທີ
  return (b.last - b.start) / (b.count - 1) < 40;
}

// ມີຕົວອັກສອນນອກ ASCII (ເຊັ່ນ ອັກສອນລາວ/ໄທ) → ແປ້ນພິມບໍ່ແມ່ນພາສາອັງກິດ
function hasNonEnglishChars(str) {
  return /[^\x20-\x7E]/.test(str);
}

function showSwitchKeyboardAlert(code) {
  Swal.fire({
    icon: 'warning',
    title: 'ກະລຸນາປ່ຽນແປ້ນພິມເປັນພາສາອັງກິດ',
    html: '<div style="font-size:0.95rem;">ແປ້ນພິມປັດຈຸບັນບໍ່ແມ່ນພາສາອັງກິດ ຈຶ່ງອ່ານບາໂຄ້ດຜິດເປັນ:</div>' +
          '<div style="font-size:1.1rem; font-weight:700; color:#ef4444; margin:8px 0; word-break:break-all;">' + $('<div>').text(code).html() + '</div>' +
          '<small class="text-muted d-block">ກົດ <b>Alt + Shift</b> ຫຼື <b>Win + Space</b> ເພື່ອປ່ຽນເປັນ ENG ແລ້ວສະແກນໃໝ່</small>',
    confirmButtonText: '<i class="fas fa-check mr-1"></i> ຕົກລົງ',
    confirmButtonColor: '#f59e0b',
    didClose: function() {
      $('#barcodeInput').val('');
      barcodeShowingLastScan = false;
      _executeFilterGrid();
      ensureBarcodeInputFocused();
    }
  });
}

$('#barcodeInput').on('keypress', function(e) {
  if (e.which === 13) {
    e.preventDefault();
    handleBarcodeSubmit();
  }
});

// ເລກບາໂຄ້ດທີ່ສະແກນລ່າສຸດ ຄ້າງສະແດງໃນຊ່ອງ (ຖືກເລືອກໄວ້ທັງໝົດ) — ສະແກນ/ພິມຄັ້ງຕໍ່ໄປ ຈະແທນທີ່ເລີຍ ບໍ່ຕ້ອງລຶບກ່ອນ.
// ຕອນຄ້າງສະແດງ ບໍ່ເອົາໄປກັ່ນຕອງລາຍການສິນຄ້າ (ສິນຄ້າທັງໝົດຍັງເຫັນຄືເກົ່າ).
var barcodeShowingLastScan = false;

function handleBarcodeSubmit() {
  var rawCode = $('#barcodeInput').val().trim();
  ensureBarcodeInputFocused();
  if (!rawCode) return;

  var scanned = isLikelyScannerInput();
  barcodeKeyBurst.count = 0;
  if (scanned && hasNonEnglishChars(rawCode)) {
    showSwitchKeyboardAlert(rawCode);
    return;
  }

  barcodeShowingLastScan = true;
  var input = document.getElementById('barcodeInput');
  if (input) {
    input.value = rawCode;
    try { input.select(); } catch (e) {}
  }

  findAndAddByBarcode(rawCode);
  _executeFilterGrid();
}

// ພິມ/ສະແກນໃໝ່ (ຫຼື ກົດປຸ່ມລ້າງ) → ເລີກສະແດງເລກເກົ່າ, ກັບມາກັ່ນຕອງຕາມທີ່ພິມ
$('#barcodeInput').on('input', function() { barcodeShowingLastScan = false; });
// ກັບມາໂຟກັສ ຂະນະເລກເກົ່າຍັງຄ້າງ → ເລືອກທັງໝົດໄວ້ ໃຫ້ສະແກນຕໍ່ແທນທີ່ໄດ້ທັນທີ
$('#barcodeInput').on('focus', function() {
  if (barcodeShowingLastScan) { var el = this; setTimeout(function() { try { el.select(); } catch (e) {} }, 0); }
});

function findAndAddByBarcode(code) {
  var cleanCode = code.toLowerCase();
  var matchedProduct = null;
  var matchedUnit = null;

  for (var i = 0; i < allProducts.length; i++) {
    var p = allProducts[i];
    if (p.barcode && p.barcode.toLowerCase() === cleanCode) {
      matchedProduct = p;
      matchedUnit = { unit_name: p.unit || 'ອັນ', multiplier: 1, price: p.price, bprice: p.bprice };
      break;
    }
    if (String(p.product_id) === cleanCode) {
      matchedProduct = p;
      matchedUnit = { unit_name: p.unit || 'ອັນ', multiplier: 1, price: p.price, bprice: p.bprice };
      break;
    }
    if (p.extra_units && p.extra_units.length > 0) {
      for (var j = 0; j < p.extra_units.length; j++) {
        var eu = p.extra_units[j];
        if (eu.barcode && eu.barcode.toLowerCase() === cleanCode) {
          matchedProduct = p;
          matchedUnit = eu;
          break;
        }
      }
      if (matchedProduct) break;
    }
  }

  if (!matchedProduct) {
    for (var i = 0; i < allProducts.length; i++) {
      var p = allProducts[i];
      if (p.product_name && p.product_name.toLowerCase().indexOf(cleanCode) !== -1) {
        matchedProduct = p;
        matchedUnit = { unit_name: p.unit || 'ອັນ', multiplier: 1, price: p.price, bprice: p.bprice };
        break;
      }
    }
  }

  if (matchedProduct) {
    playPosBeep();
    addProductToCart(matchedProduct, matchedUnit);
  } else if (hasNonEnglishChars(code)) {
    // ເຄື່ອງສະແກນຊ້າ (ບໍ່ຜ່ານການກວດຄວາມໄວ) ແຕ່ຊອກບໍ່ພົບ ແລະ ມີອັກສອນນອກພາສາອັງກິດ → ບອກໃຫ້ກວດແປ້ນພິມນຳ
    Swal.fire({
      icon: 'warning',
      title: 'ບໍ່ພົບສິນຄ້າ!',
      html: 'ລະຫັດ: ' + $('<div>').text(code).html() +
            '<small class="text-muted d-block mt-2">ຖ້າສະແກນບາໂຄ້ດ ກະລຸນາປ່ຽນແປ້ນພິມເປັນພາສາອັງກິດ (ENG) ແລ້ວສະແກນໃໝ່</small>',
      timer: 3000,
      showConfirmButton: false,
      didClose: function() { ensureBarcodeInputFocused(); }
    });
  } else {
    Swal.fire({
      icon: 'warning',
      title: 'ບໍ່ພົບສິນຄ້າ!',
      text: 'ລະຫັດບາໂຄ້ດ: ' + code,
      timer: 1500,
      showConfirmButton: false,
      didClose: function() { ensureBarcodeInputFocused(); }
    });
  }
}

window.selectProductById = function(productId) {
  if (typeof allProducts === 'undefined' || !allProducts) {
    console.error('allProducts array not loaded yet!');
    return;
  }
  var p = allProducts.find(function(item) {
    return String(item.product_id) === String(productId);
  });
  if (p) {
    addProductToCart(p);
  } else {
    console.error('Product not found for ID:', productId);
  }
};

$(document).on('click', '.product-card', function(e) {
  e.stopPropagation();
  var id = $(this).attr('data-id');
  if (id) {
    window.selectProductById(id);
  }
});

function addProductToCart(product, specificUnit) {
  if (!product) return;

  var cutQty = getProductCutQty(product.product_id);
  var remaining = getProductRemainingStock(product.product_id);
  var isOutOfStock = (cutQty !== 0 && remaining <= 0) || (parseFloat(product.qty || 0) <= 0 && cutQty !== 0);

  if (isOutOfStock) {
    Swal.fire({
      icon: 'error',
      title: posT('pos.alert_out_of_stock_title'),
      html: '<div style="font-size:1.05rem; font-weight:700; color:#ef4444; margin-bottom:6px;">' + posT('pos.alert_product_quoted', product.product_name) + '</div>' +
            '<div class="text-dark font-weight-bold" style="font-size:0.92rem;">' + posT('pos.alert_out_of_stock_body') + '</div>' +
            '<small class="text-muted d-block mt-2">' + posT('pos.alert_out_of_stock_hint') + '</small>',
      confirmButtonText: '<i class="fas fa-check mr-1"></i> ' + posT('pos.btn_ok'),
      confirmButtonColor: '#ef4444',
      customClass: {
        confirmButton: 'btn btn-danger font-weight-bold px-4'
      },
      didClose: function() { ensureBarcodeInputFocused(); }
    });
    return;
  }

  if (!checkBillOpenedOrAlert()) return;

  var allUnits = buildAvailableUnits(product);

  if (specificUnit || allUnits.length <= 1) {
    _doAddToCart(product, specificUnit || null);
    ensureBarcodeInputFocused();
    return;
  }

  var htmlOptions = allUnits.map(function(u, uIdx) {
    var hasPromo = (parseFloat(u.original_price) || 0) > (parseFloat(u.price) || 0);
    var priceHtml = hasPromo
      ? `<span style="font-size:0.82rem; font-weight:700; color:#94a3b8; text-decoration:line-through; margin-right:6px;">${POSCUR.fmt(u.original_price)}</span>
         <span style="font-size:1.0rem; font-weight:800; color:#ef4444;">${POSCUR.fmt(u.price)}</span>`
      : `<span style="font-size:1.0rem; font-weight:800; color:#16a34a;">${POSCUR.fmt(u.price)}</span>`;
    return `<button class="unit-pick-btn" data-unit-idx="${uIdx}"
      style="display:flex; align-items:center; justify-content:space-between; width:100%; margin-bottom:8px; padding:10px 16px; border:1.5px solid #e2e8f0; border-radius:10px; background:#fff; cursor:pointer; transition:all 0.15s ease; font-family:inherit;"
      onmouseover="this.style.borderColor='#244886'; this.style.background='#eef2fb';"
      onmouseout="this.style.borderColor='#e2e8f0'; this.style.background='#fff';">
      <span style="font-size:0.95rem; font-weight:700; color:#1e293b;">${u.unit_name}</span>
      <span style="display:flex; align-items:baseline;">${priceHtml}</span>
    </button>`;
  }).join('');

  Swal.fire({
    title: '<span style="font-size:1.05rem; color:#1e293b;">ເລືອກຫົວໜ່ວຍ</span>',
    html: `<div style="text-align:left; margin-bottom:6px; font-size:0.88rem; color:#64748b; font-weight:600;">${product.product_name}</div>
           <div style="margin-top:10px;">${htmlOptions}</div>`,
    showConfirmButton: false,
    showCloseButton: true,
    // ບໍ່ໃຫ້ໜ້າ POS ຂ້າງຫຼັງຍັບ/ເດັ້ງຕອນເປີດ:
    //   animation:false      -> ບໍ່ເດັ້ງ scale
    //   backdrop:false + heightAuto:false + scrollbarPadding:false
    //                        -> SweetAlert ບໍ່ໄປແຕະ <body>/<html> ເລີຍ (ບໍ່ lock scroll,
    //                           ບໍ່ toggle scrollbar, ບໍ່ height:auto) => ບັອກສິນຄ້າ ແລະ ປຸ່ມກະຕ່າ ຢູ່ນິ່ງ
    // ພື້ນມືດໆ ແລະ ການຈັດກາງ ສ້າງຄືນດ້ວຍ CSS (.unit-pick-container ໃນ themes/pos.css)
    animation: false,
    backdrop: false,
    heightAuto: false,
    scrollbarPadding: false,
    customClass: { popup: 'unit-pick-popup', container: 'unit-pick-container' },
    didOpen: function() {
      $('.unit-pick-btn').on('click', function() {
        var uIdx = parseInt($(this).data('unit-idx'));
        var uObj = allUnits[uIdx];
        Swal.close();
        _doAddToCart(product, uObj);
        ensureBarcodeInputFocused();
      });
    },
    didClose: function() { ensureBarcodeInputFocused(); }
  });
}

function selectCategoryPill(btn, catId) {
  $('.category-pill-btn').removeClass('active');
  $(btn).addClass('active');
  selectedCategoryPillId = String(catId);
  _executeFilterGrid();
  ensureBarcodeInputFocused();
}

function filterGrid() {
  if (filterGridTimer) clearTimeout(filterGridTimer);
  filterGridTimer = setTimeout(function() {
    _executeFilterGrid();
  }, 100);
}

function _executeFilterGrid() {
  var q = barcodeShowingLastScan ? '' : ($('#barcodeInput').val() || '').toLowerCase().trim();
  var catId = selectedCategoryPillId;
  var visibleCount = 0;

  var cards = document.querySelectorAll('.product-item-card');
  for (var i = 0; i < cards.length; i++) {
    var card = cards[i];
    var name = card.getAttribute('data-name') || '';
    var barcode = card.getAttribute('data-barcode') || '';
    var pid = card.getAttribute('data-id') || '';
    var cat = card.getAttribute('data-category') || '';

    var matchText = (!q) || (name.indexOf(q) !== -1) || (barcode.indexOf(q) !== -1) || (pid.indexOf(q) !== -1);
    var matchCat = (!catId) || (cat == catId);

    if (matchText && matchCat) {
      if (card.style.display === 'none') card.style.display = '';
      visibleCount++;
    } else {
      if (card.style.display !== 'none') card.style.display = 'none';
    }
  }

  var emptyEl = document.getElementById('productGridEmpty');
  if (emptyEl) {
    emptyEl.style.display = (visibleCount === 0) ? 'block' : 'none';
  }
}

$(function() { 
  _executeFilterGrid();
  ensureBarcodeInputFocused();
});
</script>

