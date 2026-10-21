<?php
/*
 * ສ່ວນ: JS: ຈັດການໂຊນ ແລະ ຊັ້ນວາງໃນຟອມສິນຄ້າ
 * ກັ່ນຕອງຊັ້ນວາງຕາມໂຊນ, ເຊື່ອງບັອກຊັ້ນວາງຖ້າບໍ່ໄດ້ເລືອກໂຊນ, ແລະ ປຸ່ມເພີ່ມໂຊນ/ຊັ້ນວາງດ່ວນ (AJAX).
 */
?>
<script>
function onZoneSelectChange(mode) {
  var zoneSelect = $('#' + mode + '_zone');
  var shelfBox = $('#' + mode + '_shelf_box');
  var shelfSelect = $('#' + mode + '_shelf');
  var selectedZone = (zoneSelect.val() || '').trim();
  var selectedOpt = zoneSelect.find('option:selected');
  var selectedZoneId = selectedOpt.attr('data-id') || '';

  if (!selectedZone) {
    // ຖ້າບໍ່ໄດ້ປ້ອນ / ບໍ່ໄດ້ເລືອກໂຊນ: ບັອກແມ່ນເຊື່ອງໄວ້
    shelfSelect.val('');
    shelfBox.hide();
    return;
  }

  // ຖ້າມີການເລືອກໂຊນ: ສະແດງບັອກຊັ້ນວາງ
  shelfBox.show();
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
  if (p && p.zone) {
    $('#edit_zone').val(p.zone);
    onZoneSelectChange('edit');
    if (p.shelf) {
      $('#edit_shelf').val(p.shelf);
    }
  } else {
    $('#edit_zone').val('');
    onZoneSelectChange('edit'); // ຖ້າບໍ່ໄດ້ປ້ອນ ບັອກແມ່ນເຊື່ອງໄວ້
  }
}

function quickAddZonePrompt(mode) {
  Swal.fire({
    title: 'ເພີ່ມໂຊນໃໝ່',
    input: 'text',
    inputPlaceholder: 'ປ້ອນຊື່ໂຊນ ເຊັ່ນ: ໂຊນ C, ສາງ 2...',
    showCancelButton: true,
    confirmButtonText: 'ບັນທຶກ',
    cancelButtonText: 'ຍົກເລີກ',
    confirmButtonColor: '#2563eb',
    inputValidator: function(v) {
      if (!v || !v.trim()) return 'ກະລຸນາປ້ອນຊື່ໂຊນ!';
    }
  }).then(function(res) {
    if (res.isConfirmed && res.value) {
      var name = res.value.trim();
      $.post('api_product.php', { action: 'add_zone', zone_name: name }, function(data) {
        if (data && data.success) {
          ['add', 'edit'].forEach(function(m) {
            var $sel = $('#' + m + '_zone');
            if (!$sel.find('option[value="' + name + '"]').length) {
              $sel.append($('<option>', { value: name, text: name, 'data-id': data.zone_id }));
            }
          });
          $('#' + mode + '_zone').val(name);
          onZoneSelectChange(mode);
          Swal.fire({ icon: 'success', title: 'ເພີ່ມໂຊນສຳເລັດ', timer: 1200, showConfirmButton: false });
        } else {
          Swal.fire({ icon: 'error', title: 'ບໍ່ສຳເລັດ', text: (data && data.message) || 'ບໍ່ສາມາດເພີ່ມໂຊນໄດ້' });
        }
      }, 'json');
    }
  });
}

function quickAddShelfPrompt(mode) {
  var zoneSelect = $('#' + mode + '_zone');
  var zoneName = zoneSelect.val() || '';
  var zoneId = zoneSelect.find('option:selected').attr('data-id') || 0;

  Swal.fire({
    title: 'ເພີ່ມຊັ້ນວາງໃໝ່' + (zoneName ? ' (' + zoneName + ')' : ''),
    input: 'text',
    inputPlaceholder: 'ປ້ອນຊື່ຊັ້ນວາງ ເຊັ່ນ: ຊັ້ນ 4, ລັອກ B-03...',
    showCancelButton: true,
    confirmButtonText: 'ບັນທຶກ',
    cancelButtonText: 'ຍົກເລີກ',
    confirmButtonColor: '#0891b2',
    inputValidator: function(v) {
      if (!v || !v.trim()) return 'ກະລຸນາປ້ອນຊື່ຊັ້ນວາງ!';
    }
  }).then(function(res) {
    if (res.isConfirmed && res.value) {
      var name = res.value.trim();
      $.post('api_product.php', { action: 'add_shelf', shelf_name: name, zone_id: zoneId }, function(data) {
        if (data && data.success) {
          ['add', 'edit'].forEach(function(m) {
            var $sel = $('#' + m + '_shelf');
            if (!$sel.find('option[value="' + name + '"]').length) {
              $sel.append($('<option>', {
                value: name,
                text: name,
                'data-zone-id': zoneId,
                'data-zone-name': zoneName
              }));
            }
          });
          $('#' + mode + '_shelf').val(name);
          Swal.fire({ icon: 'success', title: 'ເພີ່ມຊັ້ນວາງສຳເລັດ', timer: 1200, showConfirmButton: false });
        } else {
          Swal.fire({ icon: 'error', title: 'ບໍ່ສຳເລັດ', text: (data && data.message) || 'ບໍ່ສາມາດເພີ່ມຊັ້ນວາງໄດ້' });
        }
      }, 'json');
    }
  });
}
</script>
