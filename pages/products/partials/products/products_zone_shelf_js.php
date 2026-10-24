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
  var selectedZone = (zoneSelect.val() || '').trim();
  var selectedOpt = zoneSelect.find('option:selected');
  var selectedZoneId = selectedOpt.attr('data-id') || '';

  // ບັອກຊັ້ນວາງສະແດງຕະຫຼອດເວລາ ບໍ່ເຊື່ອງ
  shelfBox.show();

  if (!selectedZone) {
    // ຖ້າບໍ່ໄດ້ເລືອກໂຊນ: ສະແດງທຸກຊັ້ນວາງໃຫ້ເລືອກໄດ້ຕາມສະບາຍ
    shelfSelect.find('option').show().prop('disabled', false);
    return;
  }

  // ຖ້າມີການເລືອກໂຊນ: ກັ່ນຕອງສະເພາະຊັ້ນວາງໃນໂຊນນັ້ນ (ຫຼື ຊັ້ນວາງທົ່ວໄປທີ່ບໍ່ມີໂຊນ)
  var curShelfVal = shelfSelect.val();
  shelfSelect.find('option:not(:first)').each(function() {
    var $opt = $(this);
    var optZoneId = $opt.attr('data-zone-id') || '';
    var optZoneName = $opt.attr('data-zone-name') || '';
    if (!optZoneId || optZoneId == '0' || optZoneId == selectedZoneId || optZoneName == selectedZone) {
      $opt.show().prop('disabled', false);
    } else {
      $opt.hide().prop('disabled', true);
    }
  });

  var activeOpt = shelfSelect.find('option[value="' + curShelfVal + '"]:not(:disabled)');
  if (!activeOpt.length) {
    shelfSelect.val('');
  }
}

function applyProductZoneShelf(p) {
  $('#edit_shelf_box').show();
  if (p && p.zone) {
    $('#edit_zone').val(p.zone);
    onZoneSelectChange('edit');
  } else {
    $('#edit_zone').val('');
    onZoneSelectChange('edit');
  }
  if (p && p.shelf) {
    $('#edit_shelf').val(p.shelf);
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
    inputValidator: function(v) {
      if (!v || !v.trim()) return pqT('products_q.zone_required');
    }
  }).then(function(res) {
    if (res.isConfirmed && res.value) {
      var name = res.value.trim();
      $.post('api_product.php', { action: 'add_zone', zone_name: name }, function(data) {
        if (data && data.success) {
          ['add', 'edit', 'bulk'].forEach(function(m) {
            var $sel = $('#' + m + '_zone');
            if ($sel.length && !$sel.find('option[value="' + name + '"]').length) {
              $sel.append($('<option>', { value: name, text: name, 'data-id': data.zone_id }));
            }
          });
          $('.bulk-zone').each(function() {
            var $bz = $(this);
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
    inputValidator: function(v) {
      if (!v || !v.trim()) return pqT('products_q.shelf_required');
    }
  }).then(function(res) {
    if (res.isConfirmed && res.value) {
      var name = res.value.trim();
      $.post('api_product.php', { action: 'add_shelf', shelf_name: name, zone_id: zoneId }, function(data) {
        if (data && data.success) {
          ['add', 'edit', 'bulk'].forEach(function(m) {
            var $sel = $('#' + m + '_shelf');
            if ($sel.length && !$sel.find('option[value="' + name + '"]').length) {
              $sel.append($('<option>', {
                value: name,
                text: name,
                'data-zone-id': zoneId,
                'data-zone-name': zoneName
              }));
            }
          });
          $('.bulk-shelf').each(function() {
            var $bsh = $(this);
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
          }
          Swal.fire({ icon: 'success', title: pqT('products_q.shelf_added'), timer: 1200, showConfirmButton: false });
        } else {
          Swal.fire({ icon: 'error', title: pqT('products_q.failed'), text: (data && data.message) || pqT('products_q.shelf_failed') });
        }
      }, 'json');
    }
  });
}
</script>
