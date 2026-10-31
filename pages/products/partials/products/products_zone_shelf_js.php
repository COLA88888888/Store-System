<?php
/*
 * ສ່ວນ: JS: ຈັດການໂຊນ ແລະ ຊັ້ນວາງໃນຟອມສິນຄ້າ
 * ກັ່ນຕອງຊັ້ນວາງຕາມໂຊນ, ເຊື່ອງບັອກຊັ້ນວາງຖ້າບໍ່ໄດ້ເລືອກໂຊນ, ແລະ ປຸ່ມເພີ່ມໂຊນ/ຊັ້ນວາງດ່ວນ (AJAX).
 */
?>
<script>
// ຂໍ້ຄວາມເພີ່ມດ່ວນ ຫົວໜ່ວຍ/ໂຊນ/ຊັ້ນວາງ (lang/*/products_q.php) — ເປັນ window.I18N_* ຈຶ່ງປ່ຽນພາສາເອງເມື່ອກົດທຸງ
// (home/partials/dashboard/js_language.php); popup ອ່ານຜ່ານ pqT() ຕອນເປີດ ຈຶ່ງບໍ່ຕ້ອງໂຫຼດໜ້າ.
window.I18N_PRODUCTS_Q = Object.assign(window.I18N_PRODUCTS_Q || {}, <?php echo tjson(require dirname(__DIR__, 4) . '/lang/lo/products_q.php'); ?>);
if (typeof window.pqT !== 'function') window.pqT = function (key) { var d = window.I18N_PRODUCTS_Q || {}; return d[key] || key; };
function onZoneSelectChange(mode) {
  var zoneSelect = $('#' + mode + '_zone');
  var shelfBox = $('#' + mode + '_shelf_box');
  var shelfSelect = $('#' + mode + '_shelf');
  if (!zoneSelect.length || !shelfSelect.length) return;

  var selectedZone = (zoneSelect.val() || '').trim();
  var selectedOpt = zoneSelect.find('option:selected');
  var selectedZoneId = selectedOpt.attr('data-id') || '';

  // ບັອກຊັ້ນວາງສະແດງຕະຫຼອດເວລາ ບໍ່ເຊື່ອງ
  shelfBox.show();

  // 1. ກວດເບິ່ງວ່າໂຊນມີຂໍ້ມູນບໍ່: ຖ້າບໍ່ມີໃຫ້ disable ໄວ້
  var hasZoneOptions = zoneSelect.find('option:not(:first)').length > 0;
  if (!hasZoneOptions) {
    zoneSelect.prop('disabled', true).addClass('select-disabled');
    zoneSelect.find('option:first').text(mode === 'bulk' ? (pqT('products_q.no_zone_short') || '-- ບໍ່ມີໂຊນ --') : (pqT('products_q.no_zone') || '-- ບໍ່ມີຂໍ້ມູນໂຊນ --'));
  } else {
    zoneSelect.prop('disabled', false).removeClass('select-disabled');
    zoneSelect.find('option:first').text(mode === 'bulk' ? (pqT('products_q.zone_none_opt') || '-- ບໍ່ລະບຸ / ເລືອກໂຊນ --') : (pqT('products_q.select_zone') || '-- ເລືອກໂຊນ / ພື້ນທີ່ຈັດວາງ --'));
  }

  // 2. ກວດເບິ່ງຊັ້ນວາງໃນລະບົບ
  var totalShelves = shelfSelect.find('option:not(:first)').length;
  if (totalShelves === 0) {
    // ລະບົບບໍ່ມີຊັ້ນວາງເລີຍ: disable ໄວ້
    shelfSelect.prop('disabled', true).addClass('select-disabled');
    shelfSelect.find('option:first').text(mode === 'bulk' ? (pqT('products_q.no_shelf_short') || '-- ບໍ່ມີຊັ້ນວາງ --') : (pqT('products_q.no_shelf') || '-- ບໍ່ມີຂໍ້ມູນຊັ້ນວາງ --'));
    shelfSelect.val('');
    return;
  }

  var curShelfVal = shelfSelect.val();
  var visibleCount = 0;

  if (!selectedZone) {
    // ຖ້າບໍ່ໄດ້ເລືອກໂຊນ: ສະແດງທຸກຊັ້ນວາງໃຫ້ເລືອກໄດ້ຕາມສະບາຍ
    shelfSelect.find('option:not(:first)').show().prop('disabled', false);
    visibleCount = totalShelves;
  } else {
    // ຖ້າມີການເລືອກໂຊນ: ກັ່ນຕອງສະເພາະຊັ້ນວາງໃນໂຊນນັ້ນ (ຫຼື ຊັ້ນວາງທົ່ວໄປທີ່ບໍ່ມີໂຊນ)
    shelfSelect.find('option:not(:first)').each(function() {
      var $opt = $(this);
      var optZoneId = $opt.attr('data-zone-id') || '';
      var optZoneName = $opt.attr('data-zone-name') || '';
      if (!optZoneId || optZoneId == '0' || optZoneId == selectedZoneId || optZoneName == selectedZone) {
        $opt.show().prop('disabled', false);
        visibleCount++;
      } else {
        $opt.hide().prop('disabled', true);
      }
    });
  }

  // 3. ຖ້າໂຊນນີ້ບໍ່ມີຊັ້ນວາງເລີຍ: disable ໄວ້
  if (visibleCount === 0) {
    shelfSelect.prop('disabled', true).addClass('select-disabled');
    shelfSelect.find('option:first').text(pqT('products_q.no_shelf_in_zone') || '-- ບໍ່ມີຊັ້ນວາງໃນໂຊນນີ້ --');
    shelfSelect.val('');
  } else {
    shelfSelect.prop('disabled', false).removeClass('select-disabled');
    shelfSelect.find('option:first').text(mode === 'bulk' ? (pqT('products_q.shelf_none_opt') || '-- ບໍ່ລະບຸ / ເລືອກຊັ້ນວາງ --') : (pqT('products_q.select_shelf') || '-- ເລືອກຊັ້ນ / ຊ່ອງວາງສິນຄ້າ --'));
    var activeOpt = shelfSelect.find('option[value="' + curShelfVal + '"]:not(:disabled)');
    if (!activeOpt.length) {
      shelfSelect.val('');
    }
  }
}

// ກັ່ນຕອງ ແລະ disable ຊັ້ນວາງໃນແຖວເພີ່ມຫຼາຍລາຍການ (Bulk row)
window.onBulkRowZoneChange = function(el) {
  var $row = $(el).closest('.bulk-row');
  var $shelf = $row.find('.bulk-shelf');
  if (!$shelf.length) return;

  var zoneName = ($(el).val() || '').trim();
  var zoneId = $(el).find('option:selected').attr('data-id') || '';

  var totalShelves = $shelf.find('option:not(:first)').length;
  if (totalShelves === 0) {
    $shelf.prop('disabled', true).addClass('select-disabled');
    $shelf.find('option:first').text(pqT('products_q.no_shelf_short') || '-- ບໍ່ມີຊັ້ນວາງ --');
    $shelf.val('');
    return;
  }

  var curVal = $shelf.val();
  var visibleCount = 0;
  $shelf.find('option:not(:first)').each(function() {
    var $opt = $(this);
    var optZoneId = $opt.attr('data-zone-id') || '';
    var optZoneName = $opt.attr('data-zone-name') || '';
    if (!zoneName || !optZoneId || optZoneId == '0' || optZoneId == zoneId || optZoneName == zoneName) {
      $opt.show().prop('disabled', false);
      visibleCount++;
    } else {
      $opt.hide().prop('disabled', true);
    }
  });

  if (visibleCount === 0) {
    $shelf.prop('disabled', true).addClass('select-disabled');
    $shelf.find('option:first').text(pqT('products_q.no_shelf_in_zone') || '-- ບໍ່ມີຊັ້ນວາງໃນໂຊນນີ້ --');
    $shelf.val('');
  } else {
    $shelf.prop('disabled', false).removeClass('select-disabled');
    $shelf.find('option:first').text(pqT('products_q.shelf_opt') || '-- ຊັ້ນວາງ --');
    if (!$shelf.find('option[value="' + curVal + '"]:not(:disabled)').length) {
      $shelf.val('');
    }
  }
};

function applyProductZoneShelf(p) {
  $('#edit_shelf_box').show();
  var $z = $('#edit_zone');
  var $sh = $('#edit_shelf');
  if (p && p.zone) {
    if ($z.length && !$z.find('option[value="' + p.zone + '"]').length) {
      $z.append($('<option>', { value: p.zone, text: p.zone }));
    }
    $z.prop('disabled', false).removeClass('select-disabled').val(p.zone);
    onZoneSelectChange('edit');
  } else {
    $z.val('');
    onZoneSelectChange('edit');
  }
  if (p && p.shelf) {
    if ($sh.length && !$sh.find('option[value="' + p.shelf + '"]').length) {
      $sh.append($('<option>', { value: p.shelf, text: p.shelf }));
    }
    $sh.prop('disabled', false).removeClass('select-disabled').val(p.shelf);
  }
}

function quickAddZonePrompt(mode) {
  Swal.fire({
    title: pqT('products_q.zone_title'),
    input: 'text',
    inputPlaceholder: pqT('products_q.zone_ph'),
    showCancelButton: true,
    confirmButtonText: pqT('products_q.save'),
    cancelButtonText: pqT('products_q.cancel'),
    confirmButtonColor: '#2563eb',
    didOpen: function() {
      $(document).off('focusin.bs.modal');
      var inp = Swal.getInput();
      if (inp) { inp.focus(); }
    },
    inputValidator: function(v) {
      if (!v || !v.trim()) return pqT('products_q.zone_required');
    }
  }).then(function(res) {
    if (res.isConfirmed && res.value) {
      var name = res.value.trim();
      $.post('../../api/products_api.php', { action: 'add_zone', zone_name: name }, function(data) {
        if (data && data.success) {
          ['add', 'edit', 'bulk'].forEach(function(m) {
            var $sel = $('#' + m + '_zone');
            if ($sel.length) {
              $sel.prop('disabled', false).removeClass('select-disabled');
              $sel.find('option:first').text(m === 'bulk' ? (pqT('products_q.zone_none_opt') || '-- ບໍ່ລະບຸ / ເລືອກໂຊນ --') : (pqT('products_q.select_zone') || '-- ເລືອກໂຊນ / ພື້ນທີ່ຈັດວາງ --'));
              if (!$sel.find('option[value="' + name + '"]').length) {
                $sel.append($('<option>', { value: name, text: name, 'data-id': data.zone_id }));
              }
            }
          });
          $('.bulk-zone').each(function() {
            var $bz = $(this);
            $bz.prop('disabled', false).removeClass('select-disabled');
            $bz.find('option:first').text(pqT('products_q.zone_opt') || '-- ໂຊນ --');
            if (!$bz.find('option[value="' + name + '"]').length) {
              $bz.append($('<option>', { value: name, text: name, 'data-id': data.zone_id }));
            }
          });
          if (typeof BULK_ZONE_OPTIONS !== 'undefined') {
            BULK_ZONE_OPTIONS += '<option value="' + $('<div>').text(name).html() + '" data-id="' + data.zone_id + '">' + $('<div>').text(name).html() + '</option>';
          }

          if (mode) {
            $('#' + mode + '_zone').val(name);
            onZoneSelectChange(mode);
          }
          Swal.fire({ icon: 'success', title: pqT('products_q.zone_added'), timer: 1200, showConfirmButton: false });
        } else {
          Swal.fire({ icon: 'error', title: pqT('products_q.failed'), text: (data && data.message) || pqT('products_q.zone_failed') });
        }
      }, 'json');
    }
  });
}

function quickAddShelfPrompt(mode) {
  var zoneSelect = $('#' + mode + '_zone');
  var zoneName = zoneSelect.length ? (zoneSelect.val() || '') : '';
  var zoneId = zoneSelect.length ? (zoneSelect.find('option:selected').attr('data-id') || 0) : 0;

  Swal.fire({
    title: pqT('products_q.shelf_title') + (zoneName ? ' (' + zoneName + ')' : ''),
    input: 'text',
    inputPlaceholder: pqT('products_q.shelf_ph'),
    showCancelButton: true,
    confirmButtonText: pqT('products_q.save'),
    cancelButtonText: pqT('products_q.cancel'),
    confirmButtonColor: '#0891b2',
    didOpen: function() {
      $(document).off('focusin.bs.modal');
      var inp = Swal.getInput();
      if (inp) { inp.focus(); }
    },
    inputValidator: function(v) {
      if (!v || !v.trim()) return pqT('products_q.shelf_required');
    }
  }).then(function(res) {
    if (res.isConfirmed && res.value) {
      var name = res.value.trim();
      $.post('../../api/products_api.php', { action: 'add_shelf', shelf_name: name, zone_id: zoneId }, function(data) {
        if (data && data.success) {
          ['add', 'edit', 'bulk'].forEach(function(m) {
            var $sel = $('#' + m + '_shelf');
            if ($sel.length) {
              $sel.prop('disabled', false).removeClass('select-disabled');
              $sel.find('option:first').text(m === 'bulk' ? (pqT('products_q.shelf_none_opt') || '-- ບໍ່ລະບຸ / ເລືອກຊັ້ນວາງ --') : (pqT('products_q.select_shelf') || '-- ເລືອກຊັ້ນ / ຊ່ອງວາງສິນຄ້າ --'));
              if (!$sel.find('option[value="' + name + '"]').length) {
                $sel.append($('<option>', {
                  value: name,
                  text: name,
                  'data-zone-id': zoneId,
                  'data-zone-name': zoneName
                }));
              }
            }
          });
          $('.bulk-shelf').each(function() {
            var $bsh = $(this);
            $bsh.prop('disabled', false).removeClass('select-disabled');
            $bsh.find('option:first').text(pqT('products_q.shelf_opt') || '-- ຊັ້ນວາງ --');
            if (!$bsh.find('option[value="' + name + '"]').length) {
              $bsh.append($('<option>', {
                value: name,
                text: name,
                'data-zone-id': zoneId,
                'data-zone-name': zoneName
              }));
            }
          });
          if (typeof BULK_SHELF_OPTIONS !== 'undefined') {
            BULK_SHELF_OPTIONS += '<option value="' + $('<div>').text(name).html() + '" data-zone-id="' + zoneId + '" data-zone-name="' + $('<div>').text(zoneName).html() + '">' + $('<div>').text(name).html() + '</option>';
          }

          if (mode) {
            $('#' + mode + '_shelf').val(name);
            onZoneSelectChange(mode);
          }
          Swal.fire({ icon: 'success', title: pqT('products_q.shelf_added'), timer: 1200, showConfirmButton: false });
        } else {
          Swal.fire({ icon: 'error', title: pqT('products_q.failed'), text: (data && data.message) || pqT('products_q.shelf_failed') });
        }
      }, 'json');
    }
  });
}

$(document).ready(function() {
  onZoneSelectChange('add');
  if ($('#bulk_zone').length) onZoneSelectChange('bulk');
});
</script>
