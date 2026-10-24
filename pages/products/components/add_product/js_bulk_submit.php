<?php
/*
 * ສ່ວນ: JS: ນຳເຂົ້າ Excel ແລະ ບັນທຶກທັງໝົດ
 * ເຕີມແຖວຈາກ Excel, ດາວໂຫຼດແບບຟອມ, ກວດບາໂຄ້ດຊ້ຳ, ແລະ submitBulkAddProduct ສົ່ງທຸກແຖວເປັນ JSON.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/products/components/form_add_product.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>function bulkFillFromRows(rows) {
  // ຊອກແຖວຫົວຖັນໃນ 10 ແຖວທຳອິດ (ຕ້ອງມີຖັນ "ຊື່ສິນຄ້າ")
  var aliasToKey = {};
  Object.keys(BULK_EXCEL_HEADERS).forEach(function (k) {
    BULK_EXCEL_HEADERS[k].forEach(function (a) { aliasToKey[bulkNorm(a)] = k; });
  });
  var colOf = null, start = 0;
  for (var h = 0; h < Math.min(10, rows.length); h++) {
    var found = {};
    rows[h].forEach(function (cell, ci) {
      var k = aliasToKey[bulkNorm(cell)];
      if (k && found[k] === undefined) found[k] = ci;
    });
    if (found.name !== undefined) { colOf = found; start = h + 1; break; }
  }
  if (!colOf) {
    colOf = {};
    BULK_EXCEL_ORDER.forEach(function (k, i) { colOf[k] = i; });
  }

  var catMap = bulkOptionMap(BULK_CAT_OPTIONS);
  var unitMap = bulkOptionMap(BULK_UNIT_OPTIONS);
  var supMap = bulkOptionMap(BULK_SUPPLIER_OPTIONS);
  var get = function (r, k) { return colOf[k] === undefined ? '' : r[colOf[k]]; };

  var items = [];
  for (var i = start; i < rows.length; i++) {
    var r = rows[i];
    var name = bulkCellText(get(r, 'name'));
    if (name === '') continue;
    var catTxt = bulkCellText(get(r, 'cat'));
    var unitTxt = bulkCellText(get(r, 'unit'));
    var supTxt = bulkCellText(get(r, 'supplier'));
    var zoneTxt = bulkCellText(get(r, 'zone'));
    var shelfTxt = bulkCellText(get(r, 'shelf'));
    items.push({
      name: name,
      catTxt: catTxt, category_id: catMap[bulkNorm(catTxt)] || '',
      unitTxt: unitTxt, unit_id: unitMap[bulkNorm(unitTxt)] || '',
      supTxt: supTxt, supplier_id: supMap[bulkNorm(supTxt)] || '',
      barcode: bulkCellBarcode(get(r, 'barcode')),
      bprice: bulkCellPrice(get(r, 'bprice')),
      price: bulkCellPrice(get(r, 'price')),
      zone: zoneTxt,
      shelf: shelfTxt
    });
  }

  if (items.length === 0) {
    Swal.fire({ icon: 'warning', title: I18N_ADD_PRODUCT['products.alert_notice_title'], text: 'ບໍ່ພົບລາຍການສິນຄ້າໃນໄຟລ໌ (ຕ້ອງມີຖັນ "ຊື່ສິນຄ້າ")', confirmButtonColor: '#2563eb' });
    return;
  }

  // ລົບແຖວວ່າງອອກກ່ອນ, ແຖວທີ່ປ້ອນໄວ້ແລ້ວເກັບໄວ້ຄືເກົ່າ
  $('#bulk_rows_container .bulk-row').each(function () {
    var $r = $(this), filled = false;
    $r.find('input').each(function () { if ($(this).val().trim() !== '' && $(this).val() !== '0') filled = true; });
    $r.find('select').each(function () { if ($(this).val()) filled = true; });
    if (!filled) $r.remove();
  });

  var missCat = {}, missUnit = {}, missSup = {}, badRows = 0;
  items.forEach(function (it) {
    bulkAddRow({ category_id: it.category_id, unit_id: it.unit_id, supplier_id: it.supplier_id, zone: it.zone, shelf: it.shelf });
    var $row = $('#bulk_rows_container .bulk-row').last();
    $row.find('.bulk-name').val(it.name);
    $row.find('.bulk-barcode').val(it.barcode);
    $row.find('.bulk-bprice').val(it.bprice);
    $row.find('.bulk-price').val(it.price);
    var bad = false;
    if (!it.category_id) { bad = true; $row.find('.bulk-cat').addClass('is-invalid'); if (it.catTxt) missCat[it.catTxt] = 1; }
    if (!it.unit_id)     { bad = true; $row.find('.bulk-unit').addClass('is-invalid'); if (it.unitTxt) missUnit[it.unitTxt] = 1; }
    if (!it.price)       { bad = true; $row.find('.bulk-price').addClass('is-invalid'); }
    if (it.supTxt && !it.supplier_id) missSup[it.supTxt] = 1;
    if (bad) { badRows++; $row.addClass('border-danger'); }
  });
  bulkRenumber();

  // Whole file from one supplier → put it in the single batch box at the top.
  var supIds = items.map(function (it) { return it.supplier_id; }).filter(Boolean);
  var uniqSup = supIds.filter(function (v, i, a) { return a.indexOf(v) === i; });
  if (uniqSup.length === 1 && supIds.length === items.length) $('#bulk_supplier_id').val(uniqSup[0]);

  var esc = function (s) { return $('<div>').text(s).html(); };
  var html = 'ອ່ານໄດ້ <b>' + items.length + '</b> ລາຍການ — ກວດສອບແລ້ວກົດ <b>ບັນທຶກທັງໝົດ</b>';
  if (badRows > 0) html += '<br><br><span class="text-danger">' + badRows + ' ລາຍການຂໍ້ມູນບໍ່ຄົບ (ຂອບສີແດງ) — ກະລຸນາເລືອກ/ປ້ອນໃຫ້ຄົບ</span>';
  var listMiss = function (label, obj) {
    var k = Object.keys(obj);
    return k.length ? '<br><small>' + label + ': ' + k.slice(0, 10).map(esc).join(', ') + (k.length > 10 ? ' ...' : '') + '</small>' : '';
  };
  html += listMiss('ບໍ່ພົບປະເພດໃນລະບົບ', missCat) + listMiss('ບໍ່ພົບຫົວໜ່ວຍໃນລະບົບ', missUnit) + listMiss('ບໍ່ພົບຜູ້ສະໜອງ (ຈະວ່າງໄວ້)', missSup);
  Swal.fire({ icon: badRows > 0 ? 'warning' : 'success', title: 'ນຳເຂົ້າຈາກ Excel', html: html, confirmButtonColor: '#2563eb', confirmButtonText: I18N_ADD_PRODUCT['products.btn_ok'] });
}

$(document).on('change input', '#bulk_rows_container .is-invalid', function () {
  if ($(this).val()) $(this).removeClass('is-invalid');
  var $row = $(this).closest('.bulk-row');
  if ($row.find('.is-invalid').length === 0) $row.removeClass('border-danger');
});

// ແບບຟອມ Excel: sheet ທີ 1 = ຟອມປ້ອນ, sheet ທີ 2 = ລາຍຊື່ ປະເພດ/ຫົວໜ່ວຍ/ຜູ້ສະໜອງ/ໂຊນ/ຊັ້ນວາງ ທີ່ມີໃນລະບົບ (ຕ້ອງສະກົດໃຫ້ກົງ)
function bulkDownloadExcelTemplate() {
  bulkLoadXlsx(function () {
    var names = function (html) { return $('<select>' + html + '</select>').find('option').map(function () { return $(this).text(); }).get(); };
    var cats = names(BULK_CAT_OPTIONS), units = names(BULK_UNIT_OPTIONS), sups = names(BULK_SUPPLIER_OPTIONS);
    var zns = (typeof BULK_ZONE_OPTIONS !== 'undefined') ? names(BULK_ZONE_OPTIONS) : [];
    var shs = (typeof BULK_SHELF_OPTIONS !== 'undefined') ? names(BULK_SHELF_OPTIONS) : [];

    var form = XLSX.utils.aoa_to_sheet([
      ['ປະເພດ', 'ຊື່ສິນຄ້າ', 'ບາໂຄ້ດ', 'ຫົວໜ່ວຍ', 'ລາຄາຊື້', 'ລາຄາຂາຍ', 'ຜູ້ສະໜອງ', 'ໂຊນ', 'ຊັ້ນວາງ'],
      [cats[0] || 'ເຄື່ອງດື່ມ', 'ຕົວຢ່າງ: ນ້ຳດື່ມ 600ml', '8850999320014', units[0] || 'ຂວດ', 3000, 5000, sups[0] || '', zns[0] || '', shs[0] || '']
    ]);
    form['!cols'] = [{ wch: 18 }, { wch: 30 }, { wch: 18 }, { wch: 12 }, { wch: 12 }, { wch: 12 }, { wch: 20 }, { wch: 15 }, { wch: 15 }];

    var n = Math.max(cats.length, units.length, sups.length, zns.length, shs.length, 1);
    var ref = [['ປະເພດ', 'ຫົວໜ່ວຍ', 'ຜູ້ສະໜອງ', 'ໂຊນ', 'ຊັ້ນວາງ']];
    for (var i = 0; i < n; i++) ref.push([cats[i] || '', units[i] || '', sups[i] || '', zns[i] || '', shs[i] || '']);
    var refWs = XLSX.utils.aoa_to_sheet(ref);
    refWs['!cols'] = [{ wch: 22 }, { wch: 16 }, { wch: 24 }, { wch: 18 }, { wch: 18 }];

    var wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, form, 'ສິນຄ້າ');
    XLSX.utils.book_append_sheet(wb, refWs, 'ລາຍຊື່ໃນລະບົບ');
    XLSX.writeFile(wb, 'ແບບຟອມນຳເຂົ້າສິນຄ້າ.xlsx');
  });
}

// Delete a box — keep at least one (clear it instead of removing the last one)
$(document).on('click', '.bulk-row-del', function () {
  var $rows = $('#bulk_rows_container .bulk-row');
  if ($rows.length <= 1) {
    var $r = $(this).closest('.bulk-row');
    $r.find('input').val('');
    $r.find('select').prop('selectedIndex', 0);
    $r.data('supplier', '');
    bulkClearImg($r.find('.bulk-img-box'));
  } else {
    $(this).closest('.bulk-row').remove();
  }
  bulkRenumber();
});

// Barcode duplicate check (within the bulk form, then against the DB)
$(document).on('blur', '#bulkAddProductForm input[name="b_barcode[]"]', function () {
  var $input = $(this), bc = $input.val().trim();
  if (!bc) return;
  var cnt = 0;
  $('#bulkAddProductForm input[name="b_barcode[]"]').each(function () {
    if ($(this).val().trim() === bc) cnt++;
  });
  if (cnt > 1) {
    Swal.fire({
      icon: 'warning', title: I18N_ADD_PRODUCT['products.alert_duplicate_title'],
      text: I18N_BULK.e_bc_dup.replace('{value}', bc),
      confirmButtonColor: '#2563eb', confirmButtonText: I18N_ADD_PRODUCT['products.btn_ok']
    }).then(function () { $input.val('').focus(); });
    return;
  }
  $.getJSON('api_product.php', { action: 'check_barcode', barcode: bc, product_id: 0 }, function (res) {
    if (res && res.exists) {
      Swal.fire({
        icon: 'warning', title: I18N_ADD_PRODUCT['products.alert_duplicate_title'],
        text: res.message || I18N_ADD_PRODUCT['products.duplicate_in_db_text'].replace('{value}', bc),
        confirmButtonColor: '#2563eb', confirmButtonText: I18N_ADD_PRODUCT['products.btn_ok']
      }).then(function () { $input.val('').focus(); });
    }
  });
});

function submitBulkAddProduct(e) {
  if ($('#bulk_rows_container .bulk-img-spin:visible').length) {
    if (e) e.preventDefault();
    Swal.fire({ icon: 'info', title: I18N_BULK.img_busy, confirmButtonColor: '#2563eb' });
    return false;
  }
  var validCount = 0;
  var seen = [];
  var err = null;

  $('#bulk_rows_container .bulk-row').each(function () {
    if (err) return false;
    var $r = $(this);
    var cat  = $r.find('.bulk-cat').val();
    var name = $r.find('.bulk-name').val().trim();
    var bc   = $r.find('.bulk-barcode').val().trim();
    var unit = $r.find('.bulk-unit').val();
    var bp   = $r.find('.bulk-bprice').val().trim();
    var pr   = $r.find('.bulk-price').val().trim();

    var isBlank = (!cat && !name && !bc && !unit && (bp === '' || bp === '0') && (pr === '' || pr === '0'));
    if (isBlank) return; // unused box — skip

    if (name === '')            { err = [$r.find('.bulk-name'), I18N_BULK.e_name]; return false; }
    if (!cat)                   { err = [$r.find('.bulk-cat'), I18N_BULK.e_cat]; return false; }
    // ບາໂຄ້ດ ບໍ່ບັງຄັບ — ກວດຮູບແບບ/ຊ້ຳ ສະເພາະເມື່ອປ້ອນມາ
    if (bc !== '') {
      if (!/^[0-9]+$/.test(bc))    { err = [$r.find('.bulk-barcode'), I18N_BULK.e_bc_num]; return false; }
      if (seen.indexOf(bc) !== -1) { err = [$r.find('.bulk-barcode'), I18N_BULK.e_bc_dup.replace('{value}', bc)]; return false; }
      seen.push(bc);
    }
    if (!unit)                  { err = [$r.find('.bulk-unit'), I18N_BULK.e_unit]; return false; }
    // ລາຄາຊື້ (bp) ບໍ່ບັງຄັບ — ປ້ອນ ຫຼື ບໍ່ປ້ອນກໍໄດ້ (ວ່າງ = 0)
    if (pr === '' || pr === '0') { err = [$r.find('.bulk-price'), I18N_BULK.e_pr]; return false; }
    validCount++;
  });

  if (err) {
    if (e) e.preventDefault();
    Swal.fire({
      icon: 'warning', title: I18N_ADD_PRODUCT['products.alert_notice_title'], text: err[1],
      confirmButtonColor: '#2563eb', confirmButtonText: I18N_ADD_PRODUCT['products.btn_ok']
    }).then(function () { err[0].focus(); });
    return false;
  }
  if (validCount === 0) {
    if (e) e.preventDefault();
    Swal.fire({
      icon: 'warning', title: I18N_ADD_PRODUCT['products.alert_notice_title'], text: I18N_BULK.e_none,
      confirmButtonColor: '#2563eb', confirmButtonText: I18N_ADD_PRODUCT['products.btn_ok']
    });
    return false;
  }

  // ສົ່ງແຖວທັງໝົດເປັນ JSON ກ້ອນດຽວ — ຖ້າສົ່ງເປັນ b_name[] ... PHP ຈະຕັດທິ້ມເກີນ max_input_vars
  // (1000 ຊ່ອງ ≈ 140 ແຖວ) ໂດຍບໍ່ແຈ້ງເຕືອນ, ເຊິ່ງການນຳເຂົ້າຈາກ Excel ເກີນໄດ້ງ່າຍ.
  var topZone = $('#bulk_zone').val() || '';
  var topShelf = $('#bulk_shelf').val() || '';

  var payload = [];
  $('#bulk_rows_container .bulk-row').each(function () {
    var $r = $(this);
    var cat  = $r.find('.bulk-cat').val();
    var name = $r.find('.bulk-name').val().trim();
    var bc   = $r.find('.bulk-barcode').val().trim();
    var unit = $r.find('.bulk-unit').val();
    var bp   = $r.find('.bulk-bprice').val().trim();
    var pr   = $r.find('.bulk-price').val().trim();

    var isBlank = (!cat && !name && !bc && !unit && (bp === '' || bp === '0') && (pr === '' || pr === '0'));
    if (isBlank) return; // unused box — skip

    payload.push({
      category_id: cat || '',
      name:        name,
      barcode:     bc,
      unit_id:     unit || '',
      bprice:      bp,
      price:       pr,
      supplier_id: $r.data('supplier') || '',      // Excel only; the batch box overrides on the server
      zone:        $r.find('.bulk-zone').val() || topZone,
      shelf:       $r.find('.bulk-shelf').val() || topShelf,
      img:         $r.find('.bulk-img').val() || ''
    });
  });
  var $form = $('#bulkAddProductForm');
  $form.find('input[name="bulk_json"]').remove();
  $('<input type="hidden" name="bulk_json">').val(JSON.stringify(payload)).appendTo($form);
  $('#bulk_rows_container').find('input, select').prop('disabled', true);
  return true;
}

// ຍົກເລີກຟອມເພີ່ມຫຼາຍລາຍການ: ລ້າງຂໍ້ມູນ ແລະ ປິດ modal
window.cancelAddBulkForm = function () {
  $('#bulk_supplier_id').val('');
  $('#bulk_zone').val('').trigger('change');
  $('#bulk_shelf').val('');
  $('#bulk_no_cut_qty').prop('checked', false);
  bulkResetRows();
  $('#addProductModal').modal('hide');
};

// Seed 10 boxes the first time the modal opens if container is empty
$('#addProductModal').on('show.bs.modal', function () {
  if ($('#bulk_rows_container .bulk-row').length === 0) bulkResetRows();
});
// ເມື່ອປິດ modal ໂດຍບໍ່ບັນທຶກ: ບໍ່ລ້າງຄ່າ (ຄ້າງຂໍ້ມູນໄວ້ຕາມຄວາມຕ້ອງການ)
