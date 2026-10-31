<?php
/**
 * ສ່ວນ: JS ຂອງໜ້າ ລຶບ/ລ້າງຂໍ້ມູນລະບົບ — ນັບຈຳນວນ, ເລືອກສ່ວນ, ເລືອກດ່ວນ, ຢືນຢັນ ແລະ ສົ່ງລຶບ
 * ມາຈາກ pages/settings/clear_data/index.php. ຂໍ້ຄວາມທັງໝົດມາຈາກ I18N_CLEAR_DATA (ພາສາປັດຈຸບັນ);
 * ປ່ຽນພາສາ → ໜ້າໂຫຼດໃໝ່ເປັນພາສາໃໝ່ (home/partials/dashboard/js_language.php).
 */
?>
<script>
window.CLEAR_DATA_API = '<?php echo $base_path; ?>api/clear_data_backend.php';

var I18N_CLEAR_DATA = <?php echo tjson(require dirname(__DIR__, 4) . '/lang/lo/clear_data.php'); ?>;
function cdT(key) { return I18N_CLEAR_DATA[key] || key; }

// ເລືອກດ່ວນ: ທຸລະກຳ = ການຂາຍ + ກະ/ບັນທຶກ + ການເຄື່ອນໄຫວສະຕັອກ (ບໍ່ລວມປັບສະຕັອກເປັນ 0)
var CD_PRESETS = {
  transactions: { groups: ['sales', 'shifts', 'stock'], except: ['reset_stock'] },
  keep_catalog: { groups: ['sales', 'shifts', 'stock', 'customers'], except: [] },
  factory_reset: { groups: null, except: [] },
  none: { groups: [], except: [] }
};

function cdLoadCounts() {
  $.get(window.CLEAR_DATA_API, { action: 'get_counts' }, function (res) {
    if (!res || !res.success || !res.counts) return;
    $.each(res.counts, function (key, n) {
      var $badge = $('#cd_count_' + key);
      $badge.find('.cd-count-num').text(Number(n || 0).toLocaleString());
      $badge.toggleClass('has-data', n > 0);
    });
  }, 'json');
}

function cdSetPreset(name) {
  var p = CD_PRESETS[name] || CD_PRESETS.none;
  $('.cd-chk').each(function () {
    var on = (p.groups === null || p.groups.indexOf($(this).data('group')) !== -1) && p.except.indexOf(this.value) === -1;
    this.checked = on;
  });
  cdRefresh();
}

// ກົດຫົວກຸ່ມ: ເລືອກທັງກຸ່ມ / ຍົກເລີກທັງກຸ່ມ (ຖ້າເລືອກຄົບແລ້ວ)
function cdToggleGroup(group) {
  var $chk = $('.cd-chk[data-group="' + group + '"]');
  var allOn = $chk.length === $chk.filter(':checked').length;
  $chk.prop('checked', !allOn);
  cdRefresh();
}

function cdRefresh() {
  $('.cd-chk').each(function () {
    $(this).closest('.cd-item').toggleClass('is-checked', this.checked);
  });
  $('.card[data-group]').each(function () {
    var $chk = $(this).find('.cd-chk');
    var allOn = $chk.length > 0 && $chk.length === $chk.filter(':checked').length;
    $(this).find('.cd-group-toggle').text(cdT(allOn ? 'clear_data.unselect_group' : 'clear_data.select_group'));
  });
  var n = $('.cd-chk:checked').length;
  $('#cdSelectedCount').text(n);
  $('#btnSubmitClear').prop('disabled', n === 0);
}

function cdOpenConfirm() {
  var $checked = $('.cd-chk:checked');
  if (!$checked.length) {
    Swal.fire({ icon: 'warning', title: cdT('clear_data.alert_no_selection'), confirmButtonColor: '#2563eb' });
    return;
  }
  var $ul = $('<ul class="mb-0 pl-3 text-danger font-weight-bold" style="line-height: 1.8;"></ul>');
  $checked.each(function () {
    var label = $('#cd_label_' + this.value).text();
    var count = $('#cd_count_' + this.value).text().replace(/\s+/g, ' ').trim();
    $('<li></li>').text(label + ' (' + count + ')').appendTo($ul);
  });
  $('#confirmScopesList').empty().append($ul);
  $('#adminPasswordInput').val('').attr('type', 'password');
  $('#togglePasswordIcon').removeClass('fa-eye-slash').addClass('fa-eye');
  $('#confirmDeleteModal').modal('show');
  setTimeout(function () { $('#adminPasswordInput').focus(); }, 400);
}

function cdTogglePassword() {
  var $inp = $('#adminPasswordInput');
  var show = $inp.attr('type') === 'password';
  $inp.attr('type', show ? 'text' : 'password');
  $('#togglePasswordIcon').toggleClass('fa-eye', !show).toggleClass('fa-eye-slash', show);
}

function cdSubmit() {
  var pw = $('#adminPasswordInput').val();
  if (!$.trim(pw)) {
    Swal.fire({ icon: 'warning', title: cdT('clear_data.alert_password_required'), confirmButtonColor: '#dc2626' })
      .then(function () { $('#adminPasswordInput').focus(); });
    return;
  }
  var data = $('#clearDataForm').serialize() + '&action=execute_clean&password=' + encodeURIComponent(pw);

  Swal.fire({
    title: cdT('clear_data.deleting_progress'),
    text: cdT('clear_data.please_wait'),
    allowOutsideClick: false,
    allowEscapeKey: false,
    didOpen: function () { Swal.showLoading(); }
  });

  $.post(window.CLEAR_DATA_API, data, function (res) {
    if (res && res.success) {
      $('#confirmDeleteModal').modal('hide');
      Swal.fire({
        icon: 'success',
        title: cdT('clear_data.success_title'),
        text: res.message || cdT('clear_data.success_text'),
        confirmButtonColor: '#2563eb'
      }).then(function () {
        cdSetPreset('none');
        cdLoadCounts();
      });
    } else {
      Swal.fire({
        icon: 'error',
        title: cdT('clear_data.error_title'),
        text: (res && res.message) || cdT('clear_data.error_generic'),
        confirmButtonColor: '#dc2626'
      });
    }
  }, 'json').fail(function () {
    Swal.fire({ icon: 'error', title: cdT('clear_data.error_title'), text: cdT('clear_data.error_server'), confirmButtonColor: '#dc2626' });
  });
}

$(function () {
  cdRefresh();
  cdLoadCounts();
});
</script>
