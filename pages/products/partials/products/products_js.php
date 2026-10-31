<?php
/*
 * ສ່ວນ: JS ຂອງໜ້າສິນຄ້າ
 * preview/ລຶບຮູບ, ສ້າງບາໂຄ້ດ EAN-13, ຈັດຮູບແບບລາຄາ, ເປີດຟອມແກ້ໄຂ, pagination ຝັ່ງ browser,
 * ກັ່ນຕອງປະເພດ+ຄົ້ນຫາ, ຢືນຢັນລົບ ແລະ ສະຫຼັບສາຂາ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/products/products.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?><script>
  var I18N_PRODUCTS = <?php echo tjson([
      'products.page_prev' => 'ໜ້າກ່ອນໜ້າ',
      'products.page_next' => 'ໜ້າຖັດໄປ',
      'products.showing_count_prefix' => 'ສະແດງ',
      'products.showing_count_suffix' => 'ລາຍການ',
      'products.confirm_delete_title' => 'ຢືນຢັນການລົບ?',
      'products.confirm_delete_prefix' => 'ທ່ານຕ້ອງການລົບ',
      'products.confirm_delete_suffix' => 'ແທ້ຫຼືບໍ່?',
      'products.confirm_delete_button' => '<i class="fas fa-trash-alt mr-1"></i> ລົບເລີຍ',
      'products.cancel' => 'ຍົກເລີກ',
  ]); ?>;

  var DEFAULT_IMG = '<?php echo $base_path; ?>assets/img/image.jpg';

  // ====== ຟັງຊັນ Preview ຮູບພາບ ພ້ອມກວດສອບປະເພດ ແລະ ຂະໜາດໄຟລ໌ ======
  var MAX_IMG_SIZE = 10 * 1024 * 1024; // 10MB

  function previewProductImg(input, previewId, btnRemoveId) {
    if (input.files && input.files[0]) {
      var file = input.files[0];

      // 1. ກວດສອບປະເພດໄຟລ໌ (ກວດສອບທັງ MIME type ແລະ ນາມສະກຸນໄຟລ໌)
      var fileName = (file.name || '').toLowerCase();
      var ext = fileName.split('.').pop();
      var allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'jfif', 'avif'];
      var isImage = (file.type && file.type.startsWith('image/')) || allowedExts.includes(ext);

      if (!isImage) {
        Swal.fire({ icon: 'error', title: '<?php echo tlj('ຮູບແບບໄຟລ໌ບໍ່ຖືກຕ້ອງ'); ?>', text: '<?php echo tlj('ກະລຸນາເລືອກສະເພາະໄຟລ໌ຮູບພາບ (JPG, PNG, WEBP, GIF, JFIF)'); ?>', confirmButtonColor: '#2563eb', confirmButtonText: '<?php echo tlj('ຕົກລົງ'); ?>' });
        input.value = '';
        return;
      }

      // 2. ກວດສອບຂະໜາດໄຟລ໌ (ບໍ່ໃຫ້ເກີນ 10MB)
      if (file.size > MAX_IMG_SIZE) {
        var fileSizeMB = (file.size / (1024 * 1024)).toFixed(1);
        Swal.fire({ icon: 'warning', title: '<?php echo tlj('ຂະໜາດຮູບພາບໃຫຍ່ເກີນໄປ'); ?>', html: '<?php echo tlj('ຂະໜາດຮູບຂອງທ່ານ:'); ?> <strong>' + fileSizeMB + ' MB</strong><br><?php echo tlj('ລະບົບຮອງຮັບຂະໜາດສູງສຸດບໍ່ເກີນ'); ?> <strong>10 MB</strong>', confirmButtonColor: '#2563eb', confirmButtonText: '<?php echo tlj('ຕົກລົງ'); ?>' });
        input.value = '';
        return;
      }

      // 3. ສະແດງ Preview
      var reader = new FileReader();
      reader.onload = function(e) {
        var prev = document.getElementById(previewId);
        if (prev) prev.src = e.target.result;
        var btn = document.getElementById(btnRemoveId);
        if (btn) btn.style.display = 'flex';
      };
      reader.readAsDataURL(file);

      // ຖ້າຢູ່ໃນ Edit Modal, ຣີເຊັດ remove_img flag ເປັນ 0
      if (previewId === 'edit_img_preview') {
        var flag = document.getElementById('edit_remove_img');
        if (flag) flag.value = '0';
      }
    }
  }

  // ====== ຟັງຊັນ ລົບຮູບພາບ ======
  function removeProductImg(mode) {
    if (mode === 'add') {
      var input = document.getElementById('add_product_img');
      if (input) input.value = '';
      var prev = document.getElementById('add_img_preview');
      if (prev) prev.src = DEFAULT_IMG;
      var btn = document.getElementById('btn_remove_add_img');
      if (btn) btn.style.display = 'none';
    } else if (mode === 'edit') {
      var input = document.getElementById('edit_product_img');
      if (input) input.value = '';
      var flag = document.getElementById('edit_remove_img');
      if (flag) flag.value = '1';
      var prev = document.getElementById('edit_img_preview');
      if (prev) prev.src = DEFAULT_IMG;
      var btn = document.getElementById('btn_remove_edit_img');
      if (btn) btn.style.display = 'none';
    }
  }

  // ====== ຟັງຊັນ ສ້າງບາໂຄ້ດອັດຕະໂນມັດ 13 ຫຼັກ (EAN-13 Generator) ======
  function generateEAN13(target) {
    // Generate 12 random digits (Prefix '20' for internal POS barcodes)
    var code = '20';
    for (var i = 0; i < 10; i++) {
      code += Math.floor(Math.random() * 10);
    }
    // Calculate EAN-13 Checksum digit
    var sum = 0;
    for (var i = 0; i < 12; i++) {
      var digit = parseInt(code.charAt(i), 10);
      sum += (i % 2 === 0) ? digit : digit * 3;
    }
    var checksum = (10 - (sum % 10)) % 10;
    var finalBarcode = code + checksum;

    if (typeof target === 'string') {
      $('#' + target).val(finalBarcode).trigger('change');
    } else if (target && (target instanceof HTMLElement || target.jquery)) {
      $(target).closest('.input-group').find('input').val(finalBarcode).trigger('change');
    }
  }

  // ====== ຟັງຊັນຈັດ format ລາຄາມີຈຸດ (Thousand Separator) ແລະ ຮັບແຕ່ຕົວເລກ ======
  function formatPriceInput(input) {
    // ອະນຸຍາດສະເພາະຕົວເລກ 0-9
    var val = input.value.replace(/\D/g, '');
    if (val === '') {
      input.value = '';
      return;
    }
    input.value = Number(val).toLocaleString('en-US');
  }

  // ====== ຟັງຊັນ ສະແດງສີລະຫັດສິນຄ້າ (ສາຂາ=ແດງ, ປະເພດ=ຟ້າ, ສິນຄ້າ=ດຳ) ======
  function formatProductIdColoredHtml(fullCode, catId, storeCode) {
    fullCode = String(fullCode || '').trim();
    if (!fullCode) return '';
    if (fullCode.length < 5) return '<span style="color:#0f172a;">' + fullCode + '</span>';

    var seq = fullCode.slice(-4);
    var prefix = fullCode.slice(0, -4);
    var branch = '';
    var cat = '';

    storeCode = String(storeCode || '').replace(/\D+/g, '');
    if (storeCode && prefix.indexOf(storeCode) === 0) {
      branch = storeCode;
      cat = prefix.slice(storeCode.length);
    } else if (catId && prefix.slice(-String(catId).length) === String(catId)) {
      cat = String(catId);
      branch = prefix.slice(0, -cat.length);
    } else {
      branch = prefix.charAt(0);
      cat = prefix.slice(1);
    }

    return '<span style="color: #dc2626;" title="<?php echo tlj('ລະຫັດສາຂາ'); ?>">' + branch + '</span>' +
           '<span style="color: #2563eb;" title="<?php echo tlj('ລະຫັດປະເພດສິນຄ້າ'); ?>">' + cat + '</span>' +
           '<span style="color: #0f172a;" title="<?php echo tlj('ລະຫັດສິນຄ້າ'); ?>">' + seq + '</span>';
  }

  // ====== Edit Modal: Populate fields ======
  function editProduct(p) {
    $('#edit_product_id').val(p.product_id);
    $('#edit_product_id_display').html(formatProductIdColoredHtml(p.product_id, p.category_id, p.store_code));
    $('#edit_product_name').val(p.product_name);
    $('#edit_barcode').val(p.barcode || '');
    $('#edit_category_id').val(p.category_id);

    // Selected by unit_id (the real link into the units table, backfilled automatically for
    // older products — see config/db.php). If a product still has no unit_id at all (the
    // rare case where its old free-text unit didn't match anything in this branch's units
    // list), show that old text as an informational placeholder so it's clear the dropdown
    // needs a real selection rather than silently appearing blank.
    var editUnitSelect = $('#edit_unit');
    if (p.unit_id && editUnitSelect.find('option[value="' + p.unit_id + '"]').length > 0) {
      editUnitSelect.val(p.unit_id);
    } else if (p.unit) {
      if (editUnitSelect.find('option[value=""]').length > 0) {
        editUnitSelect.find('option[value=""]').text('-- ' + p.unit + ' <?php echo tlj('(ກະລຸນາເລືອກໃໝ່)'); ?> --');
      }
      editUnitSelect.val('');
    } else {
      editUnitSelect.val('');
    }
    $('#edit_qty').val(p.qty !== undefined && p.qty !== null ? p.qty : 0);
    // cut_qty: default 1 (deduct) when the field is missing on older rows
    $('#edit_cut_qty').prop('checked', (p.cut_qty === undefined || p.cut_qty === null) ? true : (parseInt(p.cut_qty) === 1));
    $('#edit_expiry_date').val(p.expiry_date || '');
    $('#edit_expiry_store_id').val(p.store_id || '');
    $('#edit_supplier_id').val(p.supplier_id ? String(p.supplier_id) : '');
    if (window.applyProductZoneShelf) applyProductZoneShelf(p);
    // Format ລາຄາມີຈຸດ
    var bpriceNum = p.bprice ? Number(p.bprice) : 0;
    var priceNum  = p.price ? Number(p.price) : 0;
    $('#edit_bprice').val(bpriceNum > 0 ? bpriceNum.toLocaleString('en-US') : '0');
    $('#edit_price').val(priceNum > 0 ? priceNum.toLocaleString('en-US') : '0');

    // ຈັດການຮູບພາບໃນ Edit Modal
    $('#edit_remove_img').val('0');
    $('#edit_product_img').val('');
    
    var imgName = p.img_url ? p.img_url.replace(/^.*[\\\/]/, '') : '';
    if (imgName) {
      $('#edit_img_preview').attr('src', '<?php echo $base_path; ?>assets/product_img/' + imgName);
      $('#edit_img_preview').off('error').on('error', function() {
        $(this).off('error').attr('src', DEFAULT_IMG);
      });
      $('#btn_remove_edit_img').css('display', 'flex');
    } else {
      $('#edit_img_preview').attr('src', DEFAULT_IMG);
      $('#btn_remove_edit_img').css('display', 'none');
    }

    $('#editProductModal').modal('show');

    // ໂຫຼດຫຼາຍລາຄາ / ຫຼາຍຫົວໜ່ວຍ (product_units) ຜ່ານ AJAX
    $('#edit_multi_units_container').empty();
    $.getJSON('../../api/products_api.php?action=get_product_units&product_id=' + p.product_id, function(res) {
      if (res && res.success && res.units && res.units.length > 0) {
        res.units.forEach(function(u) {
          addUnitRow('edit', u);
        });
      }
    });
  }

  // ====== Client-side Pagination Engine ======
  var currentPage = 1;
  var perPage = 10;
  var filteredRows = [];

  function initPagination() {
    try {
      var savedCat = localStorage.getItem('StoreMini.minipos_product_category_filter');
      if (savedCat !== null && $('#categoryFilter option[value="' + savedCat + '"]').length > 0) {
        $('#categoryFilter').val(savedCat);
      }
    } catch(e) {}

    // ເປີດມາຈາກແຈ້ງເຕືອນສິນຄ້າໃກ້ໝົດ/ໝົດ (?stock=low|out): ກັ່ນຕອງຕາມນັ້ນ ແລະ ລ້າງໝວດທີ່ຈື່ໄວ້
    // ບໍ່ໃຫ້ລາຍການຖືກເຊື່ອງ
    try {
      var qs = new URLSearchParams(window.location.search);
      var stockParam = qs.get('stock');
      if (stockParam === 'low' || stockParam === 'out') {
        $('#stockFilter').val(stockParam);
        $('#categoryFilter').val('');
      }
      // ?product=ID: ສະແດງສະເພາະສິນຄ້າທີ່ກົດໃນແຈ້ງເຕືອນ (ບໍ່ກັ່ນສະຕັອກ/ໝວດ ເພື່ອໃຫ້ເຫັນແນ່ນອນ)
      var pidParam = qs.get('product');
      if (pidParam) {
        var $row = $('.product-row[data-pid="' + pidParam.replace(/"/g, '') + '"]');
        if ($row.length) {
          focusPid = pidParam;
          $('#stockFilter').val('');
          $('#categoryFilter').val('');
          $('#focusProductName').text($.trim($row.find('.product-name-cell').text()));
          $('#focusProductBar').removeClass('d-none').addClass('d-flex');
        }
      }
    } catch(e) {}

    // Restore the "show N per page" choice so a page reload (e.g. right after adding a
    // product) keeps it instead of snapping back to 10.
    try {
      var savedPer = localStorage.getItem('StoreMini.minipos_product_per_page');
      if (savedPer !== null && $('#perPageHeaderSelect option[value="' + savedPer + '"]').length > 0) {
        applyPerPage(savedPer);
      }
    } catch(e) {}

    filterProducts();
  }

  // Apply a per-page value to the state + both selects, WITHOUT persisting or re-rendering.
  function applyPerPage(val) {
    perPage = (val === 'all') ? 999999 : (parseInt(val) || 10);
    var hSelect = document.getElementById('perPageHeaderSelect');
    var fSelect = document.getElementById('perPageSelect');
    if (hSelect) hSelect.value = val;
    if (fSelect) fSelect.value = val;
  }

  function changePerPage(val) {
    applyPerPage(val);
    try { localStorage.setItem('StoreMini.minipos_product_per_page', val); } catch(e) {}
    currentPage = 1;
    applyPagination();
  }

  function goToPage(page) {
    var totalPages = Math.ceil(filteredRows.length / perPage) || 1;
    if (page < 1) page = 1;
    if (page > totalPages) page = totalPages;
    currentPage = page;
    applyPagination();
  }

  function applyPagination() {
    var total = filteredRows.length;
    var totalPages = Math.ceil(total / perPage) || 1;
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    var startIdx = (currentPage - 1) * perPage;
    var endIdx   = startIdx + perPage;

    // Show/Hide rows
    var allRows = document.querySelectorAll('.product-row');
    allRows.forEach(function(row) {
      row.style.display = 'none';
    });

    var noDataEl = document.getElementById('noProductDataRow');

    if (total === 0) {
      if (noDataEl) noDataEl.style.display = '';
    } else {
      if (noDataEl) noDataEl.style.display = 'none';
      for (var i = startIdx; i < endIdx && i < total; i++) {
        if (filteredRows[i]) {
          filteredRows[i].style.display = '';
        }
      }
    }

    // Update left info text
    var startDisplay = total === 0 ? 0 : startIdx + 1;
    var endDisplay   = Math.min(endIdx, total);
    var startEl = document.getElementById('page_info_start');
    var endEl   = document.getElementById('page_info_end');
    var totalEl = document.getElementById('page_info_total');
    if (startEl) startEl.textContent = startDisplay;
    if (endEl)   endEl.textContent = endDisplay;
    if (totalEl) totalEl.textContent = total;

    // Render Circular Pagination Buttons
    renderPaginationControls(totalPages);
  }

  function renderPaginationControls(totalPages) {
    var paginationUl = document.getElementById('productPagination');
    if (!paginationUl) return;
    paginationUl.innerHTML = '';

    if (totalPages < 1) totalPages = 1;

    // 1. ປຸ່ມ ກ່ອນໜ້າ (Previous)
    var prevLi = document.createElement('li');
    prevLi.className = 'page-item ' + (currentPage <= 1 ? 'disabled' : '');
    prevLi.innerHTML = '<a class="page-link" href="javascript:void(0)" ' + (currentPage > 1 ? 'onclick="goToPage(' + (currentPage - 1) + ')"' : '') + ' title="' + I18N_PRODUCTS['products.page_prev'] + '"><i class="fas fa-chevron-left"></i></a>';
    paginationUl.appendChild(prevLi);

    // 2. ປຸ່ມເລກໜ້າ (Circular page numbers with smart window)
    var maxButtons = 5;
    var startPage = Math.max(1, currentPage - 2);
    var endPage = Math.min(totalPages, startPage + maxButtons - 1);
    if (endPage - startPage < maxButtons - 1) {
      startPage = Math.max(1, endPage - maxButtons + 1);
    }

    if (startPage > 1) {
      var firstLi = document.createElement('li');
      firstLi.className = 'page-item';
      firstLi.innerHTML = '<a class="page-link" href="javascript:void(0)" onclick="goToPage(1)">1</a>';
      paginationUl.appendChild(firstLi);

      if (startPage > 2) {
        var dotLi = document.createElement('li');
        dotLi.className = 'page-item disabled';
        dotLi.innerHTML = '<span class="page-link" style="border:none; background:transparent;">...</span>';
        paginationUl.appendChild(dotLi);
      }
    }

    for (var p = startPage; p <= endPage; p++) {
      var pageLi = document.createElement('li');
      pageLi.className = 'page-item ' + (p === currentPage ? 'active' : '');
      pageLi.innerHTML = '<a class="page-link" href="javascript:void(0)" onclick="goToPage(' + p + ')">' + p + '</a>';
      paginationUl.appendChild(pageLi);
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1) {
        var dotLi2 = document.createElement('li');
        dotLi2.className = 'page-item disabled';
        dotLi2.innerHTML = '<span class="page-link" style="border:none; background:transparent;">...</span>';
        paginationUl.appendChild(dotLi2);
      }

      var lastLi = document.createElement('li');
      lastLi.className = 'page-item';
      lastLi.innerHTML = '<a class="page-link" href="javascript:void(0)" onclick="goToPage(' + totalPages + ')">' + totalPages + '</a>';
      paginationUl.appendChild(lastLi);
    }

    // 3. ປຸ່ມ ຖັດໄປ (Next)
    var nextLi = document.createElement('li');
    nextLi.className = 'page-item ' + (currentPage >= totalPages ? 'disabled' : '');
    nextLi.innerHTML = '<a class="page-link" href="javascript:void(0)" ' + (currentPage < totalPages ? 'onclick="goToPage(' + (currentPage + 1) + ')"' : '') + ' title="' + I18N_PRODUCTS['products.page_next'] + '"><i class="fas fa-chevron-right"></i></a>';
    paginationUl.appendChild(nextLi);
  }

  // ====== Combined filter: category + search ======
  // ສິນຄ້າດຽວທີ່ເປີດມາຈາກແຈ້ງເຕືອນ (?product=ID) — null = ບໍ່ກັ່ນ
  var focusPid = null;
  function clearFocusProduct() {
    focusPid = null;
    $('#focusProductBar').removeClass('d-flex').addClass('d-none');
    try {
      var u = new URL(window.location.href);
      u.searchParams.delete('product');
      history.replaceState(null, '', u.toString());
    } catch (e) {}
    filterProducts();
  }
  window.clearFocusProduct = clearFocusProduct;

  function filterProducts() {
    var search   = document.getElementById('productSearch').value.toLowerCase().trim();
    var catId    = document.getElementById('categoryFilter').value;
    var stockSel = document.getElementById('stockFilter');
    var stockF   = stockSel ? stockSel.value : '';   // '' | 'low' | 'out'
    try {
      localStorage.setItem('StoreMini.minipos_product_category_filter', catId);
    } catch(e) {}
    var rows     = document.querySelectorAll('.product-row');
    filteredRows = [];

    rows.forEach(function(row) {
      var rowCat  = row.getAttribute('data-category');
      var name    = row.querySelector('.product-name-cell').textContent.toLowerCase();
      var barCell = row.querySelector('.product-barcode-cell') ? row.querySelector('.product-barcode-cell').textContent.toLowerCase() : '';
      var barAttr = (row.getAttribute('data-barcodes') || '').toLowerCase();
      var code    = row.querySelector('.product-code-cell') ? row.querySelector('.product-code-cell').textContent.toLowerCase() : '';
      var zoneCell = row.querySelector('.product-zone-cell') ? row.querySelector('.product-zone-cell').textContent.toLowerCase() : '';
      var shelfCell = row.querySelector('.product-shelf-cell') ? row.querySelector('.product-shelf-cell').textContent.toLowerCase() : '';

      var matchCat    = (catId === '' || rowCat === catId);
      var matchSearch = (search === '' || name.includes(search) || barCell.includes(search) || barAttr.includes(search) || code.includes(search) || zoneCell.includes(search) || shelfCell.includes(search));

      var matchStock  = (stockF === '' || row.getAttribute('data-stock') === stockF);
      var matchFocus  = (focusPid === null || row.getAttribute('data-pid') === focusPid);

      if (matchCat && matchSearch && matchStock && matchFocus) {
        filteredRows.push(row);
      }
    });

    // ອັບເດດ count badge
    var countBadge = document.getElementById('productCount');
    if (countBadge) {
      countBadge.textContent = I18N_PRODUCTS['products.showing_count_prefix'] + ' ' + filteredRows.length + ' ' + I18N_PRODUCTS['products.showing_count_suffix'];
    }

    currentPage = 1;
    applyPagination();
    updateProductTotals();
  }

  // Footer totals = (buy price x stock qty) and (sell price x stock qty), over the current filter result
  function updateProductTotals() {
    var rows = (typeof filteredRows !== 'undefined' && filteredRows.length)
      ? filteredRows
      : document.querySelectorAll('.product-row');
    var sumB = 0, sumP = 0;
    rows.forEach(function(r) {
      sumB += parseFloat(r.getAttribute('data-bval')) || 0;
      sumP += parseFloat(r.getAttribute('data-pval')) || 0;
    });
    var elB = document.getElementById('totalBprice');
    var elP = document.getElementById('totalPrice');
    if (elB) elB.textContent = sumB.toLocaleString('en-US', {maximumFractionDigits: <?php echo $__pdec; ?>}) + ' <?php echo addslashes($__psym); ?>';
    if (elP) elP.textContent = sumP.toLocaleString('en-US', {maximumFractionDigits: <?php echo $__pdec; ?>}) + ' <?php echo addslashes($__psym); ?>';
  }

  $(document).ready(function() {
    initPagination();

    // Prevent Enter key in productSearch from reloading page or submitting forms
    $('#productSearch').on('keydown', function(e) {
      if (e.key === 'Enter' || e.keyCode === 13) {
        e.preventDefault();
        filterProducts();
      }
    });

    // Global barcode scanner listener when no input/modal is active
    var barcodeBuffer = '';
    var barcodeTimer = null;

    $(document).on('keydown', function(e) {
      var activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
      var isInput = (activeTag === 'input' || activeTag === 'select' || activeTag === 'textarea');
      var isModalOpen = $('.modal.show').length > 0;

      if (isInput || isModalOpen) return;

      if (e.key === 'Enter' || e.keyCode === 13) {
        if (barcodeBuffer.length >= 2) {
          if (window.hasNonEnglishChars && window.hasNonEnglishChars(barcodeBuffer) && window.showSwitchKeyboardAlert) {
            var bad = barcodeBuffer; barcodeBuffer = '';
            window.showSwitchKeyboardAlert(bad, document.getElementById('productSearch'));
            return;
          }
          $('#productSearch').val(barcodeBuffer);
          filterProducts();
          $('#productSearch').focus();
        }
        barcodeBuffer = '';
        if (barcodeTimer) clearTimeout(barcodeTimer);
      } else if (e.key && e.key.length === 1) {
        barcodeBuffer += e.key;
        if (barcodeTimer) clearTimeout(barcodeTimer);
        barcodeTimer = setTimeout(function() {
          barcodeBuffer = '';
        }, 300);
      }
    });
  });

  // ====== Delete confirm ======
  function confirmDeleteProduct(productId, productName) {
    Swal.fire({
      title: I18N_PRODUCTS['products.confirm_delete_title'],
      html: I18N_PRODUCTS['products.confirm_delete_prefix'] + ' <strong>"' + productName + '"</strong> ' + I18N_PRODUCTS['products.confirm_delete_suffix'],
      icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#64748b',
      confirmButtonText: I18N_PRODUCTS['products.confirm_delete_button'], cancelButtonText: I18N_PRODUCTS['products.cancel'], heightAuto: false
    }).then(function(result) {
      if (result.isConfirmed) {
        var form = document.createElement('form');
        form.method = 'POST'; form.action = '';
        form.innerHTML = '<input type="hidden" name="action" value="delete_product"><input type="hidden" name="product_id" value="' + productId + '">';
        document.body.appendChild(form);
        form.submit();
      }
    });
  }

  // ສະຫຼັບສາຂາແບບ Real-time (AJAX) — ດຶງໜ້າ products.php ຂອງສາຂາທີ່ເລືອກ ແລ້ວດຶງສະເພາະ
  // ຕາຕະລາງສິນຄ້າ + ຕົວກອງປະເພດ (ປະເພດຕ່າງກັນລະຫວ່າງສາຂາ) ມາສະຫຼັບໃສ່ໜ້າປັດຈຸບັນ ໂດຍບໍ່ໂຫຼດໜ້າໃໝ່
  function switchStoreFilter(storeId) {
    if (storeId) {
      try { localStorage.removeItem('StoreMini.minipos_product_category_filter'); } catch(e){}
      window.location.href = 'products.php?store_id=' + storeId;
    }
  }
</script>
