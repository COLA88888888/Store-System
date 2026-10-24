<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
window.SHIFTS_API = window.SHIFTS_API || '<?php echo $base_path ?? "../../../"; ?>api/shifts_backend.php';

// ຕົວໂຫຼດແບບໂລໂກ້ WLaodev (ຄືກັບຕົວໂຫຼດໜ້າຂອງ dashboard) — CSS: .system-loader-* ໃນ themes/layout-header.css
window.SHIFT_LOADER_LOGO = '<?php echo ($base_path ?? "../../../") . 'assets/img/logosystem/Wlaodev.png?v=' . (@filemtime(dirname(__DIR__, 4) . '/assets/img/logosystem/Wlaodev.png') ?: 1); ?>';
// ຂໍ້ຄວາມລາຍງານສະຫຼຸບກະ ຕາມພາສາທີ່ເລືອກ (lang/*/shifts.php). ເປັນ window.I18N_* ຈຶ່ງຖືກປ່ຽນຄ່າເອງ
// ເມື່ອກົດທຸງພາສາ (home/partials/dashboard/js_language.php) ແລ້ວ pos:langchange ຂ້າງລຸ່ມວາດ modal ໃໝ່.
var I18N_SHIFT_RPT = <?php echo tjson(require dirname(__DIR__, 4) . '/lang/lo/shifts.php'); ?>;
function srT(key) {
  var v = I18N_SHIFT_RPT[key] || key;
  var args = Array.prototype.slice.call(arguments, 1);
  return args.length ? v.replace(/%s/g, function () { return args.length ? args.shift() : ''; }) : v;
}
window.addEventListener('pos:langchange', function () {
  if (window.currentReportSession && $('#modalShiftReport').hasClass('show')) {
    renderShiftReportContent(window.currentReportSession, window.currentReportBills || []);
  }
  // ຕາຕະລາງປະຫວັດ + ແຖບກະປັດຈຸບັນ ທີ່ JS ວາດ → ວາດໃໝ່ເປັນພາສາໃໝ່
  if ($('#sessionsTableBody').length && window.allSessionsData) renderSessionsPage();
  if ($('#noActiveShiftNotice').length) loadCurrentShiftStatus();
});

function shiftLoaderHtml(text) {
  return '<div class="d-flex flex-column align-items-center justify-content-center py-5">' +
    '<div class="system-loader-wrapper"><div class="system-loader-pulse-ring"></div>' +
    '<img src="' + window.SHIFT_LOADER_LOGO + '" alt="WLaodev" class="system-loader-logo"></div>' +
    '<span class="preloader-text"><span>' + (text || 'ກຳລັງໂຫຼດຂໍ້ມູນ') + '</span>' +
    '<span class="loader-dot d1">.</span><span class="loader-dot d2">.</span><span class="loader-dot d3">.</span></span>' +
    '</div>';
}
window.currentShiftData = null;
window.currentReportSession = null;
window.currentReportBills = [];

// ============================================================================
// 0. Language & Friendly Time Formatting (ໂມງ / ນາທີ ປ່ຽນຕາມພາສາ)
// ============================================================================
function getCurrentShiftLang() {
  var l = (document.documentElement.lang || 'lo').toLowerCase();
  if (l.indexOf('en') !== -1) return 'en';
  if (l.indexOf('th') !== -1) return 'th';
  if (l.indexOf('zh') !== -1) return 'zh';
  return 'lo';
}

function getTimeWords() {
  var lang = getCurrentShiftLang();
  var dict = {
    lo: { hour: 'ໂມງ', minute: 'ນາທີ' },
    th: { hour: 'โมง', minute: 'นาที' },
    en: { hour: 'hr', minute: 'min' },
    zh: { hour: '点', minute: '分' }
  };
  return dict[lang] || dict['lo'];
}

function formatFriendlyTime(timeStr) {
  if (!timeStr) return '-';
  var parts = String(timeStr).split(':');
  if (parts.length < 2) return timeStr;
  var h = parseInt(parts[0], 10);
  var m = parseInt(parts[1], 10);
  if (isNaN(h)) h = 0;
  if (isNaN(m)) m = 0;
  var mPad = m < 10 ? '0' + m : '' + m;
  var words = getTimeWords();
  return h + ' ' + words.hour + ' ' + mPad + ' ' + words.minute;
}

function initShiftTimePickers() {
  var words = getTimeWords();

  function buildHours(selectId, defaultVal) {
    var $sel = $('#' + selectId);
    var cur = $sel.val() || defaultVal;
    $sel.empty();
    for (var i = 0; i < 24; i++) {
      var val = i < 10 ? '0' + i : '' + i;
      var text = i + ' ' + words.hour + ' (' + val + ':00)';
      $sel.append('<option value="' + val + '"' + (parseInt(val, 10) === parseInt(cur, 10) ? ' selected' : '') + '>' + text + '</option>');
    }
  }

  function buildMinutes(selectId, defaultVal) {
    var $sel = $('#' + selectId);
    var cur = $sel.val() || defaultVal;
    $sel.empty();
    for (var i = 0; i < 60; i++) {
      var val = i < 10 ? '0' + i : '' + i;
      var text = val + ' ' + words.minute;
      $sel.append('<option value="' + val + '"' + (parseInt(val, 10) === parseInt(cur, 10) ? ' selected' : '') + '>' + text + '</option>');
    }
  }

  buildHours('shiftStartHour', '07');
  buildMinutes('shiftStartMinute', '00');
  buildHours('shiftEndHour', '15');
  buildMinutes('shiftEndMinute', '00');

  syncShiftTimePicker();
}

function syncShiftTimePicker() {
  var sH = $('#shiftStartHour').val() || '07';
  var sM = $('#shiftStartMinute').val() || '00';
  var eH = $('#shiftEndHour').val() || '15';
  var eM = $('#shiftEndMinute').val() || '00';

  var startTime = sH + ':' + sM;
  var endTime   = eH + ':' + eM;

  $('#shiftConfigStartTime').val(startTime);
  $('#shiftConfigEndTime').val(endTime);

  $('#shiftStartTimeDisplay').html('<i class="fas fa-check-circle text-success mr-1"></i> ' + formatFriendlyTime(startTime));
  $('#shiftEndTimeDisplay').html('<i class="fas fa-check-circle text-success mr-1"></i> ' + formatFriendlyTime(endTime));
}

function setShiftPickerTime(startStr, endStr) {
  if (startStr) {
    var sp = String(startStr).split(':');
    var sH = sp[0] ? (parseInt(sp[0], 10) < 10 ? '0' + parseInt(sp[0], 10) : '' + parseInt(sp[0], 10)) : '07';
    var sM = sp[1] ? (parseInt(sp[1], 10) < 10 ? '0' + parseInt(sp[1], 10) : '' + parseInt(sp[1], 10)) : '00';
    $('#shiftStartHour').val(sH);
    $('#shiftStartMinute').val(sM);
  }
  if (endStr) {
    var ep = String(endStr).split(':');
    var eH = ep[0] ? (parseInt(ep[0], 10) < 10 ? '0' + parseInt(ep[0], 10) : '' + parseInt(ep[0], 10)) : '15';
    var eM = ep[1] ? (parseInt(ep[1], 10) < 10 ? '0' + parseInt(ep[1], 10) : '' + parseInt(ep[1], 10)) : '00';
    $('#shiftEndHour').val(eH);
    $('#shiftEndMinute').val(eM);
  }
  syncShiftTimePicker();
}

// Format Number with commas
function formatNumber(num) {
  var n = parseFloat(num);
  if (isNaN(n)) return '0';
  return n.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

function formatNumberInput(input) {
  var val = input.value.replace(/,/g, '').replace(/[^\d.]/g, '');
  if (!val) {
    input.value = '0';
    return;
  }
  var parts = val.split('.');
  parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  if (parts.length > 2) parts = [parts[0], parts[1]];
  input.value = parts.join('.');
}

// ============================================================================
// 1. ໂຫຼດສະຖານະກະປະຈຸບັນ (Active Shift Banner)
// ============================================================================
function loadCurrentShiftStatus() {
  $.get(window.SHIFTS_API, { action: 'get_current_status' }, function (res) {
    if (!res || !res.success) return;
    // Realtime: ກະຖືກເປີດ/ປິດ (POS, ເຄື່ອງອື່ນ ຫຼື ປິດອໍໂຕ້) → ໂຫຼດປະຫວັດໃໝ່ພ້ອມ
    var prev = window.currentShiftData;
    var prevId = prev && prev.has_active_shift && prev.session ? prev.session.id : 0;
    var newId = res.has_active_shift && res.session ? res.session.id : 0;
    if (prev && String(prevId) !== String(newId) && $('#sessionsTableBody').length) loadSessionsHistory();
    window.currentShiftData = res;

    var banner = $('#activeShiftBanner');
    var noActiveNotice = $('#noActiveShiftNotice');

    if (res.has_active_shift && res.session) {
      banner.show();
      noActiveNotice.hide();

      $('#activeShiftName').text(res.session.shift_name);
      $('#activeShiftOpenTime').text(res.session.open_time);
      $('#activeShiftOpenedBy').text(res.session.opened_by_name);
      $('#activeShiftInitialCash').text(formatNumber(res.session.initial_cash) + ' ₭');
      $('#activeShiftBills').text(formatNumber(res.session.system_total_bills));
      $('#activeShiftCashSales').text(formatNumber(res.session.system_sales_cash) + ' ₭');
      $('#activeShiftTransferSales').text(formatNumber(res.session.system_sales_transfer) + ' ₭');
      $('#activeShiftTotalSales').text(formatNumber(res.session.system_total_sales) + ' ₭');

      // ຕັ້ງຄ່າປຸ່ມປິດກະ
      $('#btnBannerCloseShift').off('click').on('click', function () {
        openCloseShiftModal(res.session.id);
      });
      $('#btnBannerViewSummary').off('click').on('click', function () {
        openShiftReportModal(res.session.id);
      });
    } else {
      banner.hide();
      noActiveNotice.show();
      var nextText = res.next_shift ? (res.next_shift.shift_name + ' (' + srT('shifts.pg_next_start') + ' ' + formatFriendlyTime(res.next_shift.start_time) + ')') : srT('shifts.pg_no_data');
      $('#noticeNextShiftText').text(nextText);
    }
  }, 'json');
}

// ============================================================================
// 2. ໂຫຼດລາຍການຕັ້ງຄ່າກະ (List Shifts Definitions)
// ============================================================================
function loadShiftsConfigList() {
  $.get(window.SHIFTS_API, { action: 'list_shifts' }, function (res) {
    if (!res || !res.success) return;
    var tbody = $('#shiftsConfigTableBody');
    tbody.empty();

    if (!res.shifts || res.shifts.length === 0) {
      tbody.html('<tr><td colspan="7" class="text-center text-muted py-4">ຍັງບໍ່ມີຂໍ້ມູນກະເຮັດວຽກ. ກະລຸນາກົດ "+ ເພີ່ມກະໃໝ່"</td></tr>');
      return;
    }

    res.shifts.forEach(function (s, idx) {
      var statusBadge = parseInt(s.status) === 1
        ? '<span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> ເປີດໃຊ້ງານ</span>'
        : '<span class="badge badge-secondary px-2 py-1"><i class="fas fa-pause-circle mr-1"></i> ປິດໄວ້</span>';

      var tr = $('<tr></tr>');
      tr.html(`
        <td class="text-center align-middle font-weight-bold">${idx + 1}</td>
        <td class="align-middle font-weight-bold text-dark" style="font-size: 0.96rem;">
          <i class="fas fa-business-time text-primary mr-1.5"></i> ${s.shift_name}
        </td>
        <td class="text-center align-middle">
          <span class="badge badge-light border text-dark font-weight-bold px-2.5 py-1.5" style="font-size: 0.88rem;">
            <i class="fas fa-clock text-info mr-1"></i> ${formatFriendlyTime(s.start_time_fmt)}
          </span>
        </td>
        <td class="text-center align-middle">
          <span class="badge badge-light border text-dark font-weight-bold px-2.5 py-1.5" style="font-size: 0.88rem;">
            <i class="fas fa-history text-secondary mr-1"></i> ${formatFriendlyTime(s.end_time_fmt)}
          </span>
        </td>
        <td class="align-middle" style="font-size: 0.88rem;">${shiftStaffCell(s.staff)}</td>
        <td class="text-center align-middle">${statusBadge}</td>
        <td class="text-center align-middle">
          <div class="d-inline-flex align-items-center" style="gap: 6px;">
            <button type="button" class="shift-act-btn btn-edit" onclick='editShiftConfig(${JSON.stringify(s).replace(/'/g, "&#39;")})' title="ແກ້ໄຂ">
              <i class="fas fa-edit"></i>
            </button>
            <button type="button" class="shift-act-btn btn-del btn-delete" onclick="deleteShiftConfig(${s.id}, '${s.shift_name}')" title="ລຶບ/ປິດໃຊ້ງານ">
              <i class="fas fa-trash-alt"></i>
            </button>
          </div>
        </td>
      `);
      tbody.append(tr);
    });
  }, 'json');
}

// ບັນທຶກຕັ້ງຄ່າກະ
function saveShiftConfig(e) {
  e.preventDefault();
  // ຕ້ອງຕິກພະນັກງານໃນກະຢ່າງໜ້ອຍ 1 ຄົນ ຈຶ່ງບັນທຶກໄດ້ (ເຊີເວີກວດຊ້ຳ — save_shift)
  if (!$('#shiftStaffList input[name="staff_user_ids[]"]:checked').length) {
    Swal.fire({ icon: 'warning', title: 'ກະລຸນາເລືອກພະນັກງານ', text: 'ຕິກເລືອກພະນັກງານໃນກະນີ້ຢ່າງໜ້ອຍ 1 ຄົນ ກ່ອນບັນທຶກ', confirmButtonText: 'ຕົກລົງ', confirmButtonColor: '#244886' })
      .then(function () {
        var box = document.getElementById('shiftStaffList');
        if (box) { box.scrollIntoView({ block: 'center', behavior: 'smooth' }); box.style.borderColor = '#dc2626'; setTimeout(function () { box.style.borderColor = ''; }, 2500); }
      });
    return;
  }
  var formData = $('#formShiftConfig').serialize();
  formData += '&action=save_shift';

  $.post(window.SHIFTS_API, formData, function (res) {
    if (res && res.success) {
      $('#modalShiftConfig').modal('hide');
      Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false });
      loadShiftsConfigList();
      loadCurrentShiftStatus();
    } else {
      Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: res ? res.message : 'ເກີດຂໍ້ຜິດພາດ' });
    }
  }, 'json').fail(function () {
    Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: 'ບໍ່ສາມາດເຊື່ອມຕໍ່ລະບົບໄດ້' });
  });
}

// ---- ພະນັກງານປະຈຳກະ ----
function shiftEsc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
function shiftStaffCell(staff) {
  if (!staff || !staff.length) return '<span class="text-muted">-</span>';
  return '<span class="badge badge-primary mr-1">' + staff.length + ' ຄົນ</span>' + staff.map(function (u) { return shiftEsc(u.name); }).join(', ');
}
function updateShiftStaffCount() {
  $('#shiftStaffCount').text($('#shiftStaffList input:checked').length + ' ຄົນ');
}
// ໂຫຼດລາຍຊື່ພະນັກງານຂອງສາຂາ ເປັນ checkbox ແລ້ວຕິກຄົນທີ່ຢູ່ໃນກະນີ້ (selectedIds)
function loadShiftStaffPicker(selectedIds) {
  var sel = (selectedIds || []).map(function (x) { return parseInt(x, 10); });
  var $box = $('#shiftStaffList');
  $.get(window.SHIFTS_API, { action: 'list_staff' }, function (res) {
    if (!res || !res.success) { $box.html('<div class="text-danger small py-2 text-center">ໂຫຼດລາຍຊື່ພະນັກງານບໍ່ໄດ້</div>'); return; }
    if (!res.staff.length) { $box.html('<div class="text-muted small py-2 text-center">ສາຂານີ້ຍັງບໍ່ມີພະນັກງານ</div>'); updateShiftStaffCount(); return; }
    $box.html(res.staff.map(function (u) {
      var id = 'shiftStaff_' + u.id;
      return '<div class="custom-control custom-checkbox py-1">' +
        '<input type="checkbox" class="custom-control-input" name="staff_user_ids[]" value="' + u.id + '" id="' + id + '"' + (sel.indexOf(u.id) !== -1 ? ' checked' : '') + '>' +
        '<label class="custom-control-label font-weight-bold text-dark" for="' + id + '" style="cursor: pointer;">' + shiftEsc(u.name) +
        (u.role ? ' <small class="text-muted font-weight-normal">(' + shiftEsc(u.role) + ')</small>' : '') + '</label></div>';
    }).join(''));
    updateShiftStaffCount();
  }, 'json').fail(function () { $box.html('<div class="text-danger small py-2 text-center">ໂຫຼດລາຍຊື່ພະນັກງານບໍ່ໄດ້</div>'); });
}
$(document).on('change', '#shiftStaffList input', updateShiftStaffCount);

function openAddShiftModal() {
  $('#formShiftConfig')[0].reset();
  loadShiftStaffPicker([]);
  $('#shiftConfigId').val('0');
  $('#shiftConfigStatus').prop('checked', true);
  initShiftTimePickers();
  setShiftPickerTime('07:00', '15:00');
  $('#modalShiftConfigTitle').html('<i class="fas fa-calendar-plus text-primary mr-2"></i> ເພີ່ມກະເຮັດວຽກໃໝ່');
  $('#modalShiftConfig').modal('show');
}

function editShiftConfig(shift) {
  $('#formShiftConfig')[0].reset();
  $('#shiftConfigId').val(shift.id);
  $('#shiftConfigName').val(shift.shift_name);
  initShiftTimePickers();
  setShiftPickerTime(shift.start_time_fmt || '07:00', shift.end_time_fmt || '15:00');
  $('#shiftConfigStatus').prop('checked', parseInt(shift.status) === 1);
  loadShiftStaffPicker((shift.staff || []).map(function (u) { return u.id; }));
  $('#modalShiftConfigTitle').html('<i class="fas fa-edit text-warning mr-2"></i> ແກ້ໄຂກະເຮັດວຽກ');
  $('#modalShiftConfig').modal('show');
}

function deleteShiftConfig(id, name) {
  Swal.fire({
    title: 'ຢືນຢັນການລຶບ?',
    text: `ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການລຶບ ຫຼື ປິດໃຊ້ງານກະ "${name}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'ລຶບເລີຍ',
    cancelButtonText: 'ຍົກເລີກ'
  }).then(function (result) {
    if (result.isConfirmed) {
      $.post(window.SHIFTS_API, { action: 'delete_shift', id: id }, function (res) {
        if (res && res.success) {
          Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false });
          loadShiftsConfigList();
          loadCurrentShiftStatus();
        } else {
          Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: res ? res.message : 'ເກີດຂໍ້ຜິດພາດ' });
        }
      }, 'json');
    }
  });
}

// ============================================================================
// 3. ເປີດກະເຮັດວຽກ (Open Shift Modal Logic)
// ============================================================================
function openManualOpenShiftModal() {
  $.get(window.SHIFTS_API, { action: 'get_current_status' }, function (res) {
    if (!res || !res.success) return;
    if (res.has_active_shift) {
      Swal.fire({
        icon: 'info',
        title: 'ມີກະເປີດຢູ່ແລ້ວ',
        text: `ກະ "${res.session.shift_name}" ກຳລັງເປີດໃຊ້ງານຢູ່. ກະລຸນາປິດກະເກົ່າກ່ອນຈຶ່ງເປີດກະໃໝ່ໄດ້.`
      });
      return;
    }

    var select = $('#openShiftSelectId');
    select.empty();
    select.append('<option value="">-- ເລືອກກະເຮັດວຽກ --</option>');

    $('#openShiftCurrentTimeDisplay').text(res.current_time_clock || '--:--');

    var shifts = res.shifts || [];
    var defaultSelectId = null;

    shifts.forEach(function (s) {
      var opt = $('<option></option>');
      opt.val(s.id);
      opt.text(`${s.shift_name} (${formatFriendlyTime(s.start_time)} - ${formatFriendlyTime(s.end_time)})`);
      opt.data('can_open', s.can_open);
      opt.data('start_time', s.start_time);
      opt.data('shift_name', s.shift_name);

      if (s.can_open && !defaultSelectId) {
        defaultSelectId = s.id;
      }
      select.append(opt);
    });

    if (defaultSelectId) {
      select.val(defaultSelectId);
    }

    $('#openShiftInitialCash').val('');   // ວ່າງໄວ້: ຕ້ອງປ້ອນເອງ (ບໍ່ມີເງິນໃຫ້ໃສ່ 0) — submitOpenShift
    onShiftSelectionChange();
    $('#modalOpenShift').modal('show');
  }, 'json');
}

function onShiftSelectionChange() {
  var sel = $('#openShiftSelectId option:selected');
  var canOpen = sel.data('can_open');
  var startTime = sel.data('start_time');
  var shiftName = sel.data('shift_name');
  var btn = $('#btnSubmitOpenShift');
  var warning = $('#openShiftTimeWarning');
  var warningText = $('#openShiftTimeWarningText');

  if (sel.val() && canOpen === false) {
    warningText.html(`<strong>ຍັງບໍ່ຮອດໂມງເປີດກະ!</strong> ກະ "${shiftName}" ເປີດເວລາ <strong>${formatFriendlyTime(startTime)}</strong>`);
    warning.slideDown(150);
    btn.prop('disabled', true);
  } else {
    warning.slideUp(150);
    btn.prop('disabled', false);
  }
}

function submitOpenShift(e) {
  e.preventDefault();
  var shiftId = $('#openShiftSelectId').val();
  var initialCash = $('#openShiftInitialCash').val();

  if (!shiftId) {
    Swal.fire({ icon: 'warning', title: 'ກະລຸນາເລືອກກະເຮັດວຽກ' });
    return;
  }
  if ($.trim(initialCash) === '') {
    Swal.fire({ icon: 'warning', title: 'ກະລຸນາປ້ອນເງິນສົດໃນລິ້ນຊັກ', text: 'ຖ້າບໍ່ມີເງິນທອນ ໃຫ້ປ້ອນ 0', confirmButtonText: 'ຕົກລົງ', confirmButtonColor: '#244886' })
      .then(function () { $('#openShiftInitialCash').trigger('focus'); });
    return;
  }

  var btn = $('#btnSubmitOpenShift');
  btn.prop('disabled', true);

  $.post(window.SHIFTS_API, {
    action: 'open_shift',
    shift_id: shiftId,
    initial_cash: initialCash
  }, function (res) {
    btn.prop('disabled', false);
    if (res && res.success) {
      $('#modalOpenShift').modal('hide');
      Swal.fire({
        icon: 'success',
        title: 'ເປີດກະສຳເລັດ!',
        text: res.message,
        timer: 1800,
        showConfirmButton: false
      });
      loadCurrentShiftStatus();
      loadSessionsHistory();
    } else {
      Swal.fire({
        icon: res && res.too_early ? 'warning' : 'error',
        title: res && res.too_early ? 'ຍັງບໍ່ຮອດໂມງເປີດກະ' : 'ຜິດພາດ',
        text: res ? res.message : 'ເກີດຂໍ້ຜິດພາດ'
      });
    }
  }, 'json').fail(function () {
    btn.prop('disabled', false);
    Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: 'ບໍ່ສາມາດເຊື່ອມຕໍ່ລະບົບໄດ້' });
  });
}

// ============================================================================
// 4. ປິດກະເຮັດວຽກ (Close Shift Modal Logic)
// ============================================================================
// ເຕືອນ "ຍັງບໍ່ທັນຮອດໂມງປິດກະ"
function warnTooEarlyClose(message) {
  Swal.fire({ icon: 'warning', title: 'ຍັງບໍ່ທັນຮອດໂມງປິດກະ', text: message, confirmButtonText: 'ຕົກລົງ', confirmButtonColor: '#244886' });
}

function openCloseShiftModal(sessionId) {
  // ຍັງບໍ່ຮອດໂມງປິດກະ: ພະນັກງານປິດບໍ່ໄດ້ (ຜູ້ບໍລິຫານ/ຜູ້ຈັດການ ຖືກຖາມຢືນຢັນຕອນບັນທຶກ)
  var cur = window.currentShiftData;
  if (window.IS_CASHIER_USER && cur && cur.session && String(cur.session.id) === String(sessionId) && !cur.session.can_close_now) {
    warnTooEarlyClose('ກະ "' + cur.session.shift_name + '" ປິດໄດ້ເວລາ ' + formatFriendlyTime(String(cur.session.scheduled_end || '').substr(11, 5)));
    return;
  }
  $.get(window.SHIFTS_API, { action: 'get_shift_summary', session_id: sessionId }, function (res) {
    if (!res || !res.success || !res.session) {
      Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: res ? res.message : 'ບໍ່ພົບຂໍ້ມູນກະ' });
      return;
    }

    var s = res.session;
    $('#closeShiftSessionId').val(s.id);
    $('#closeShiftNameDisplay').text(s.shift_name);
    $('#closeShiftOpenTimeDisplay').text(s.open_time);
    $('#closeShiftOpenedByDisplay').text(s.opened_by_name);
    $('#closeShiftInitialCashDisplay').text(formatNumber(s.initial_cash) + ' ₭');
    $('#closeShiftSysCashDisplay').text(formatNumber(s.system_sales_cash) + ' ₭');
    $('#closeShiftSysTransferDisplay').text(formatNumber(s.system_sales_transfer) + ' ₭');
    $('#closeShiftSysTotalDisplay').text(formatNumber(s.system_total_sales) + ' ₭');
    $('#closeShiftTotalBillsDisplay').text(formatNumber(s.system_total_bills));

    window.activeClosingShift = s;

    // Reset input
    $('#closeShiftCountedCash').val('0');
    $('#closeShiftNotes').val('');
    calculateShiftDifference();

    $('#modalCloseShift').modal('show');
  }, 'json');
}

function calculateShiftDifference() {
  if (!window.activeClosingShift) return;
  var sysCash = parseFloat(window.activeClosingShift.system_sales_cash) || 0;
  var countedStr = $('#closeShiftCountedCash').val().replace(/,/g, '');
  var countedCash = parseFloat(countedStr) || 0;
  var diff = countedCash - sysCash;

  var alertBox = $('#shiftDiffAlert');
  var display = $('#shiftDiffDisplay');

  alertBox.removeClass('alert-secondary alert-success alert-danger alert-info');

  if (diff === 0) {
    alertBox.addClass('alert-success');
    display.html('<i class="fas fa-check-circle mr-1"></i> ດຸ່ນດ່ຽງພໍດີ (0 ₭)').css('color', '#15803d');
  } else if (diff > 0) {
    alertBox.addClass('alert-info');
    display.html('<i class="fas fa-arrow-up mr-1"></i> ເງິນເກີນ: +' + formatNumber(diff) + ' ₭').css('color', '#0284c7');
  } else {
    alertBox.addClass('alert-danger');
    display.html('<i class="fas fa-arrow-down mr-1"></i> ເງິນຂາດ: ' + formatNumber(diff) + ' ₭').css('color', '#dc2626');
  }
}

function submitCloseShift(e) {
  e.preventDefault();
  var sessionId = $('#closeShiftSessionId').val();
  var countedCash = $('#closeShiftCountedCash').val();
  var notes = $('#closeShiftNotes').val();
  var forceEarly = 0;

  Swal.fire({
    title: 'ຢືນຢັນການປິດກະ?',
    text: 'ເມື່ອປິດກະແລ້ວ ຍອດຂາຍຈະຖືກສະຫຼຸບ ແລະ ບັນທຶກເຂົ້າຖານຂໍ້ມູນ',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'ຢືນຢັນປິດກະ',
    cancelButtonText: 'ຍົກເລີກ'
  }).then(function (result) {
    if (result.isConfirmed) send();
  });

  // ສົ່ງປິດກະ. ຍັງບໍ່ຮອດໂມງປິດ: ຜູ້ບໍລິຫານ/ຜູ້ຈັດການ → ຖາມຢືນຢັນປິດກ່ອນເວລາ (force_early=1); ພະນັກງານ → ເຕືອນ
  function send() {
    var btn = $('#btnSubmitCloseShift');
    btn.prop('disabled', true);

    $.post(window.SHIFTS_API, {
      action: 'close_shift',
      session_id: sessionId,
      counted_sales_cash: countedCash,
      notes: notes,
      force_early: forceEarly
    }, function (res) {
      btn.prop('disabled', false);
      if (res && res.success) {
        $('#modalCloseShift').modal('hide');
        loadCurrentShiftStatus();
        loadSessionsHistory();

        // ເປີດ Modal ລາຍງານສະຫຼຸບທັນທີ
        openShiftReportModal(sessionId, true);
      } else if (res && res.too_early_close && res.can_force) {
        Swal.fire({
          icon: 'warning',
          title: 'ຍັງບໍ່ທັນຮອດໂມງປິດກະ',
          text: res.message + ' — ຕ້ອງການປິດກ່ອນເວລາບໍ່?',
          showCancelButton: true,
          confirmButtonColor: '#dc2626',
          cancelButtonColor: '#64748b',
          confirmButtonText: 'ປິດກ່ອນເວລາ',
          cancelButtonText: 'ຍົກເລີກ'
        }).then(function (r) { if (r.isConfirmed) { forceEarly = 1; send(); } });
      } else if (res && res.too_early_close) {
        warnTooEarlyClose(res.message);
      } else {
        Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: res ? res.message : 'ເກີດຂໍ້ຜິດພາດ' });
      }
    }, 'json').fail(function () {
      btn.prop('disabled', false);
      Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: 'ບໍ່ສາມາດເຊື່ອມຕໍ່ລະບົບໄດ້' });
    });
  }
}

// ============================================================================
// 5. ປະຫວັດການເປີດ-ປິດກະ & Pagination (Sessions History)
// ============================================================================
window.allSessionsData = [];
window.sessionsCurrentPage = 1;

function loadSessionsHistory() {
  var dFrom = $('#filterDateFrom').val();
  var dTo = $('#filterDateTo').val();
  var shiftId = $('#filterShiftSelect').val();

  $('#sessionsTableBody').html('<tr><td colspan="12" class="p-0">' + shiftLoaderHtml(srT('shifts.rpt_loading')) + '</td></tr>');

  $.get(window.SHIFTS_API, {
    action: 'list_sessions',
    date_from: dFrom,
    date_to: dTo,
    shift_id: shiftId
  }, function (res) {
    if (!res || !res.success) { showSessionsLoadError(); return; }

    // KPI Cards
    if (res.kpi) {
      $('#kpiTotalShifts').text(formatNumber(res.kpi.total_shifts));
      $('#kpiTotalSales').text(formatNumber(res.kpi.total_sales) + ' ₭');
      $('#kpiTotalBills').text(formatNumber(res.kpi.total_bills));
      var diffVal = res.kpi.total_diff;
      var diffEl = $('#kpiTotalDiff');
      if (diffVal > 0) {
        diffEl.text('+' + formatNumber(diffVal) + ' ₭');
      } else if (diffVal < 0) {
        diffEl.text(formatNumber(diffVal) + ' ₭');
      } else {
        diffEl.text('0 ₭');
      }
    }

    window.allSessionsRaw = res.sessions || [];
    applySessionsSearch();
  }, 'json').fail(showSessionsLoadError);
}

// ໂຫຼດບໍ່ສຳເລັດ → ແທນຕົວໂຫຼດດ້ວຍຂໍ້ຄວາມ (ບໍ່ໃຫ້ໝູນຄ້າງ)
function showSessionsLoadError() {
  $('#sessionsTableBody').html('<tr><td colspan="12" class="text-center text-danger py-5 font-weight-bold"><i class="fas fa-exclamation-circle mr-1"></i> ' + srT('shifts.pg_load_error') + '</td></tr>');
}

// ຊື່ຜູ້ປິດກະ: ຄ່າ "ລະບົບ (ປິດອໍໂຕ້)" ຖືກບັນທຶກເປັນພາສາລາວໃນ DB (config/helpers/shift_auto.php) → ສະແດງຕາມພາສາທີ່ເລືອກ
function shiftCloserName(name) {
  if (!name) return '-';
  return name === 'ລະບົບ (ປິດອໍໂຕ້)' ? srT('shifts.pg_auto_closed') : name;
}

// ບັອກຄົ້ນຫາ (#filterSessionSearch ໃນ pages/reports/shift_report.php): ກັ່ນແຖວທີ່ໂຫຼດມາແລ້ວ
// ຕາມ ຊື່ກະ, ຜູ້ເປີດ/ປິດ, ພະນັກງານ, ໝາຍເຫດ, ເວລາ — ບໍ່ມີບັອກ (ໜ້າອື່ນ) = ສະແດງທັງໝົດ
function applySessionsSearch() {
  var q = ($('#filterSessionSearch').val() || '').toLowerCase().trim();
  var raw = window.allSessionsRaw || [];
  window.allSessionsData = !q ? raw : raw.filter(function (s) {
    return [s.shift_name, s.opened_by_name, s.closed_by_name, s.staff_names, s.notes, s.open_time, s.close_time]
      .join(' ').toLowerCase().indexOf(q) !== -1;
  });
  window.sessionsCurrentPage = 1;
  renderSessionsPage();
}

function changeSessionsPageSize() {
  window.sessionsCurrentPage = 1;
  renderSessionsPage();
}

function goToSessionsPage(page) {
  var total = (window.allSessionsData || []).length;
  var sizeVal = $('#filterPageSizeSelect').val() || '10';
  var pageSize = (sizeVal === 'all') ? (total || 99999) : parseInt(sizeVal, 10);
  var totalPages = Math.ceil(total / pageSize) || 1;

  if (page < 1) page = 1;
  if (page > totalPages) page = totalPages;
  window.sessionsCurrentPage = page;
  renderSessionsPage();
}

function renderSessionsPage() {
  var sessions = window.allSessionsData || [];
  var total = sessions.length;
  var sizeVal = $('#filterPageSizeSelect').val() || '10';
  var pageSize = (sizeVal === 'all') ? (total || 99999) : parseInt(sizeVal, 10);
  var totalPages = Math.ceil(total / pageSize) || 1;

  if (window.sessionsCurrentPage > totalPages) window.sessionsCurrentPage = totalPages;
  if (window.sessionsCurrentPage < 1) window.sessionsCurrentPage = 1;

  var tbody = $('#sessionsTableBody');
  tbody.empty();

  if (total === 0) {
    tbody.html('<tr><td colspan="12" class="text-center text-muted py-5 font-weight-bold">' + srT('shifts.pg_empty_sessions') + '</td></tr>');
    $('#sessionsPageInfoStart').text('0');
    $('#sessionsPageInfoEnd').text('0');
    $('#sessionsPageInfoTotal').text('0');
    renderSessionsPaginationControls(1);
    return;
  }

  var startIdx = (window.sessionsCurrentPage - 1) * pageSize;
  var endIdx = (sizeVal === 'all') ? total : Math.min(startIdx + pageSize, total);
  var pageRows = sessions.slice(startIdx, endIdx);

  $('#sessionsPageInfoStart').text(formatNumber(startIdx + 1));
  $('#sessionsPageInfoEnd').text(formatNumber(endIdx));
  $('#sessionsPageInfoTotal').text(formatNumber(total));

  pageRows.forEach(function (s) {
    var isOpen = (s.status === 'open');
    var statusBadge = isOpen
      ? '<span class="badge badge-primary px-2 py-1"><i class="fas fa-spinner fa-spin mr-1"></i> ' + srT('shifts.rpt_status_open') + '</span>'
      : '<span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> ' + srT('shifts.rpt_status_closed') + '</span>';

    var diffBadge = '-';
    if (s.difference_cash !== null) {
      var d = parseFloat(s.difference_cash);
      if (d === 0) {
        diffBadge = '<span class="badge badge-success font-weight-bold">0 ₭ (' + srT('shifts.rpt_balanced') + ')</span>';
      } else if (d > 0) {
        diffBadge = '<span class="badge badge-info font-weight-bold">+' + formatNumber(d) + ' ₭</span>';
      } else {
        diffBadge = '<span class="badge badge-danger font-weight-bold">' + formatNumber(d) + ' ₭</span>';
      }
    }

    var tr = $('<tr></tr>');
    tr.html(`
      <td class="text-center align-middle font-weight-bold">${s.id}</td>
      <td class="align-middle font-weight-bold text-dark">${s.shift_name}</td>
      <td class="align-middle" style="font-size: 0.85rem;">
        <div>${s.open_time}</div>
        <div>${s.close_time || '<span class="text-muted">' + srT('shifts.pg_selling') + '</span>'}</div>
      </td>
      <td class="align-middle" style="font-size: 0.85rem;">
        <div><strong>${srT('shifts.pg_lbl_open')}</strong> ${s.opened_by_name}</div>
        <div><strong>${srT('shifts.pg_lbl_close')}</strong> ${shiftCloserName(s.closed_by_name)}</div>
      </td>
      <td class="text-right align-middle font-weight-bold text-secondary">${formatNumber(s.initial_cash)} ₭</td>
      <td class="text-right align-middle font-weight-bold text-success">${formatNumber(s.system_sales_cash)} ₭</td>
      <td class="text-right align-middle font-weight-bold text-info">${formatNumber(s.system_sales_transfer)} ₭</td>
      <td class="text-right align-middle font-weight-bold text-primary" style="font-size: 0.98rem;">${formatNumber(s.system_total_sales)} ₭</td>
      <td class="text-right align-middle font-weight-bold text-dark">${s.counted_sales_cash !== null ? formatNumber(s.counted_sales_cash) + ' ₭' : '-'}</td>
      <td class="text-center align-middle">${diffBadge}</td>
      <td class="text-center align-middle">${statusBadge}</td>
      <td class="text-center align-middle">
        <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center" onclick="openShiftReportModal(${s.id})" title="${srT('shifts.pg_view_report')}" style="width: 32px; height: 32px; padding: 0; border-radius: 6px; font-size: 0.95rem;">
          <i class="fas fa-file-invoice-dollar"></i>
        </button>
      </td>
    `);
    tbody.append(tr);
  });

  renderSessionsPaginationControls(totalPages);
}

function renderSessionsPaginationControls(totalPages) {
  var ul = $('#sessionsPagination');
  ul.empty();
  // ສະແດງສະເໝີ ເຖິງມີໜ້າດຽວ (‹ 1 ›) — ແບບດຽວກັບໜ້າປະເພດສິນຄ້າ
  if (totalPages < 1) totalPages = 1;

  var cur = window.sessionsCurrentPage;

  // Previous Button
  var prevDisabled = (cur <= 1);
  var prevLi = $('<li class="page-item' + (prevDisabled ? ' disabled' : '') + '"></li>');
  prevLi.html('<a class="page-link" href="javascript:void(0)" ' + (prevDisabled ? '' : 'onclick="goToSessionsPage(' + (cur - 1) + ')"') + ' title="ໜ້າກ່ອນໜ້າ"><i class="fas fa-chevron-left"></i></a>');
  ul.append(prevLi);

  // Numbered Buttons with Smart Ellipsis
  var maxButtons = 5;
  var startPage = Math.max(1, cur - 2);
  var endPage = Math.min(totalPages, startPage + maxButtons - 1);
  if (endPage - startPage < maxButtons - 1) {
    startPage = Math.max(1, endPage - maxButtons + 1);
  }

  if (startPage > 1) {
    var firstLi = $('<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToSessionsPage(1)">1</a></li>');
    ul.append(firstLi);
    if (startPage > 2) {
      ul.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
    }
  }

  for (var p = startPage; p <= endPage; p++) {
    var pLi = $('<li class="page-item' + (p === cur ? ' active' : '') + '"></li>');
    pLi.html('<a class="page-link" href="javascript:void(0)" onclick="goToSessionsPage(' + p + ')">' + p + '</a>');
    ul.append(pLi);
  }

  if (endPage < totalPages) {
    if (endPage < totalPages - 1) {
      ul.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
    }
    var lastLi = $('<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToSessionsPage(' + totalPages + ')">' + totalPages + '</a></li>');
    ul.append(lastLi);
  }

  // Next Button
  var nextDisabled = (cur >= totalPages);
  var nextLi = $('<li class="page-item' + (nextDisabled ? ' disabled' : '') + '"></li>');
  nextLi.html('<a class="page-link" href="javascript:void(0)" ' + (nextDisabled ? '' : 'onclick="goToSessionsPage(' + (cur + 1) + ')"') + ' title="ໜ້າຖັດໄປ"><i class="fas fa-chevron-right"></i></a>');
  ul.append(nextLi);
}

function resetShiftFilters() {
  $('#filterDateFrom').val('<?php echo date('Y-m-01'); ?>');
  $('#filterDateTo').val('<?php echo date('Y-m-d'); ?>');
  $('#filterShiftSelect').val('0');
  $('#filterPageSizeSelect').val('10');
  $('#filterSessionSearch').val('');
  window.sessionsCurrentPage = 1;
  loadSessionsHistory();
}

// ============================================================================
// 6. ລາຍງານສະຫຼຸບກະ ແລະ Export Excel / PDF (Shift Report Modal)
// ============================================================================
function openShiftReportModal(sessionId, wasJustClosed) {
  $('#modalShiftReport').modal('show');
  $('#shiftReportContent').html(shiftLoaderHtml(srT('shifts.rpt_loading')));

  $.get(window.SHIFTS_API, { action: 'get_session_details', session_id: sessionId }, function (res) {
    if (!res || !res.success || !res.session) {
      $('#shiftReportContent').html('<div class="alert alert-danger">' + srT('shifts.rpt_not_found') + '</div>');
      return;
    }

    window.currentReportSession = res.session;
    window.currentReportBills = res.bills || [];

    renderShiftReportContent(res.session, res.bills || []);

    // ຖ້າຫາກເປັນການປິດກະຂອງພະນັກງານຂາຍ: ເມື່ອປິດ modal ຈະ logout ທັນທີຕາມເງື່ອນໄຂ
    if (wasJustClosed && window.IS_CASHIER_USER) {
      $('#modalShiftReport').on('hidden.bs.modal', function () {
        Swal.fire({
          title: 'ປິດກະສຳເລັດແລ້ວ',
          text: 'ລະບົບຈະອອກຈາກລະບົບອັດຕະໂນມັດຕາມກຳນົດເວລາ',
          icon: 'info',
          timer: 2000,
          showConfirmButton: false
        }).then(function () {
          window.top.location.href = '../../../auth/logout.php';
        });
      });
    }
  }, 'json');
}

function renderShiftReportContent(s, bills) {
  var diff = s.difference_cash !== null ? parseFloat(s.difference_cash) : 0;
  var diffText = '0 ₭ (' + srT('shifts.rpt_balanced') + ')';
  var diffColor = '#15803d';
  if (diff > 0) {
    diffText = '+' + formatNumber(diff) + ' ₭ (' + srT('shifts.rpt_over') + ')';
    diffColor = '#0284c7';
  } else if (diff < 0) {
    diffText = formatNumber(diff) + ' ₭ (' + srT('shifts.rpt_short') + ')';
    diffColor = '#dc2626';
  }

  var billsRows = '';
  if (bills && bills.length > 0) {
    bills.forEach(function (b, i) {
      billsRows += `
        <tr>
          <td class="text-center">${i + 1}</td>
          <td><strong>${b.sale_save_bill}</strong></td>
          <td class="text-center">${b.sale_date} ${b.sale_time}</td>
          <td>${b.user_receive}</td>
          <td class="text-center"><span class="badge badge-light border">${b.type_pay}</span></td>
          <td class="text-right font-weight-bold text-dark">${formatNumber(b.sale_barlance)} ₭</td>
        </tr>
      `;
    });
  } else {
    billsRows = '<tr><td colspan="6" class="text-center text-muted py-3">' + srT('shifts.rpt_no_bills') + '</td></tr>';
  }

  var html = `
    <div id="shiftExportPrintArea" style="font-family: 'Noto Sans Lao', sans-serif;">
      <!-- Report Header -->
      <div class="text-center border-bottom pb-3 mb-3">
        <h4 class="font-weight-bold mb-1" style="color: #1e293b;">${srT('shifts.rpt_title')}</h4>
        <div class="text-muted" style="font-size: 0.95rem;">
          <strong>${srT('shifts.rpt_shift')}</strong> ${s.shift_name} | <strong>${srT('shifts.rpt_status')}</strong> ${s.status === 'open' ? srT('shifts.rpt_status_open') : srT('shifts.rpt_status_closed')}
        </div>
      </div>

      <!-- Info Meta Grid -->
      <div class="row mb-3" style="font-size: 0.88rem;">
        <div class="col-6 col-md-3"><strong>${srT('shifts.rpt_open_time')}</strong><br><span class="text-muted">${s.open_time}</span></div>
        <div class="col-6 col-md-3"><strong>${srT('shifts.rpt_close_time')}</strong><br><span class="text-muted">${s.close_time || srT('shifts.rpt_not_closed')}</span></div>
        <div class="col-6 col-md-3"><strong>${srT('shifts.rpt_opened_by')}</strong><br><span class="text-muted">${s.opened_by_name}</span></div>
        <div class="col-6 col-md-3"><strong>${srT('shifts.rpt_closed_by')}</strong><br><span class="text-muted">${s.closed_by_name || '-'}</span></div>
      </div>

      <!-- Financial Reconciliation Box -->
      <div class="card border mb-3" style="border-radius: 10px; overflow: hidden;">
        <div class="card-header bg-light font-weight-bold py-2" style="font-size: 0.92rem;">
          <i class="fas fa-calculator text-primary mr-1"></i> ${srT('shifts.rpt_summary_title')}
        </div>
        <div class="card-body p-0">
          <table class="table table-sm table-bordered m-0" style="font-size: 0.92rem;">
            <tbody>
              <tr>
                <td style="width: 55%;" class="pl-3">${srT('shifts.rpt_initial_cash')}</td>
                <td class="text-right pr-3 font-weight-bold text-secondary">${formatNumber(s.initial_cash)} ₭</td>
              </tr>
              <tr>
                <td class="pl-3">${srT('shifts.rpt_cash_sales')}</td>
                <td class="text-right pr-3 font-weight-bold text-success">${formatNumber(s.system_sales_cash)} ₭</td>
              </tr>
              <tr>
                <td class="pl-3">${srT('shifts.rpt_transfer_sales')}</td>
                <td class="text-right pr-3 font-weight-bold text-info">${formatNumber(s.system_sales_transfer)} ₭</td>
              </tr>
              <tr>
                <td class="pl-3">${srT('shifts.rpt_credit_sales')}</td>
                <td class="text-right pr-3 font-weight-bold text-warning">${formatNumber(s.system_sales_debt)} ₭</td>
              </tr>
              <tr class="bg-light font-weight-bold">
                <td class="pl-3">${srT('shifts.rpt_total_sales', formatNumber(s.system_total_bills))}</td>
                <td class="text-right pr-3 font-weight-bold text-primary" style="font-size: 1.05rem;">${formatNumber(s.system_total_sales)} ₭</td>
              </tr>
              <tr style="background: #f1f5f9;">
                <td class="pl-3 font-weight-bold text-dark">${srT('shifts.rpt_counted_cash')}</td>
                <td class="text-right pr-3 font-weight-bold text-dark" style="font-size: 1.05rem;">
                  ${s.counted_sales_cash !== null ? formatNumber(s.counted_sales_cash) + ' ₭' : '-'}
                </td>
              </tr>
              ${s.diff_hidden ? '' : `<tr style="background: #e2e8f0; font-weight: bold;">
                <td class="pl-3">${srT('shifts.rpt_diff')}</td>
                <td class="text-right pr-3" style="color: ${diffColor}; font-size: 1.05rem;">
                  ${s.difference_cash === null ? '-' : diffText}
                </td>
              </tr>`}
            </tbody>
          </table>
        </div>
      </div>

      ${s.notes ? `<div class="alert alert-secondary py-2 px-3 mb-3" style="font-size: 0.88rem;"><strong>${srT('shifts.rpt_notes')}</strong> ${s.notes}</div>` : ''}

      <!-- Detailed Bills Table -->
      <div class="mt-3">
        <h6 class="font-weight-bold text-dark mb-2" style="font-size: 0.95rem;">
          <i class="fas fa-list-alt text-primary mr-1"></i> ${srT('shifts.rpt_bills_title', bills.length)}
        </h6>
        <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
          <table class="table table-sm table-striped table-hover table-bordered" style="font-size: 0.85rem;">
            <thead class="bg-light text-center">
              <tr>
                <th style="width: 40px;">#</th>
                <th>${srT('shifts.rpt_col_bill')}</th>
                <th>${srT('shifts.rpt_col_datetime')}</th>
                <th>${srT('shifts.rpt_col_cashier')}</th>
                <th>${srT('shifts.rpt_col_channel')}</th>
                <th class="text-right">${srT('shifts.rpt_col_amount')}</th>
              </tr>
            </thead>
            <tbody>
              ${billsRows}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  `;

  $('#shiftReportContent').html(html);
}

// ພິມ Slip Thermal 80mm - ຂຶ້ນພຣີວິວເຕັມ (Full Print Preview) ຜ່ານ Iframe
function printShiftThermalSlip() {
  if (!window.currentReportSession) return;
  var s = window.currentReportSession;
  var diff = s.difference_cash !== null ? parseFloat(s.difference_cash) : 0;
  var diffText = diff === 0 ? '0 ₭ (' + srT('shifts.rpt_balanced') + ')' : (diff > 0 ? '+' + formatNumber(diff) + ' ₭ (' + srT('shifts.rpt_over') + ')' : formatNumber(diff) + ' ₭ (' + srT('shifts.rpt_short') + ')');

  var slipHtml = `<!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>${srT('shifts.rpt_slip_title')} - ${s.shift_name}</title>
      <style>
        @page { size: 80mm auto; margin: 2mm 3mm; }
        * { box-sizing: border-box; }
        body { font-family: 'Noto Sans Lao', 'Souliyo', 'Phetsarath OT', Arial, sans-serif; font-size: 13px; line-height: 1.4; margin: 0; padding: 6px; color: #000; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .border-top { border-top: 1px dashed #000; padding-top: 5px; margin-top: 5px; }
        .border-bottom { border-bottom: 1px dashed #000; padding-bottom: 5px; margin-bottom: 5px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 3px; }
      </style>
    </head>
    <body>
      <div class="text-center font-bold" style="font-size: 15px;">${srT('shifts.rpt_slip_title')}</div>
      <div class="text-center font-bold" style="font-size: 13px;">${s.shift_name}</div>
      <div class="text-center border-bottom" style="font-size: 12px;">${s.open_time} - ${s.close_time || srT('shifts.rpt_until_now')}</div>

      <div class="row"><span>${srT('shifts.rpt_opened_by')}</span><span>${s.opened_by_name}</span></div>
      <div class="row"><span>${srT('shifts.rpt_closed_by')}</span><span>${s.closed_by_name || '-'}</span></div>

      <div class="border-top"></div>
      <div class="row"><span>${srT('shifts.rpt_initial_cash')}</span><span class="font-bold">${formatNumber(s.initial_cash)} ₭</span></div>
      <div class="row"><span>${srT('shifts.rpt_cash_sales')}</span><span class="font-bold">${formatNumber(s.system_sales_cash)} ₭</span></div>
      <div class="row"><span>${srT('shifts.rpt_transfer_sales')}</span><span class="font-bold">${formatNumber(s.system_sales_transfer)} ₭</span></div>
      <div class="row"><span>${srT('shifts.rpt_credit_sales')}</span><span class="font-bold">${formatNumber(s.system_sales_debt)} ₭</span></div>
      <div class="row font-bold" style="font-size: 13.5px;"><span>${srT('shifts.rpt_total_sales', s.system_total_bills)}</span><span>${formatNumber(s.system_total_sales)} ₭</span></div>

      <div class="border-top"></div>
      <div class="row font-bold"><span>${srT('shifts.rpt_counted_cash')}</span><span>${s.counted_sales_cash !== null ? formatNumber(s.counted_sales_cash) + ' ₭' : '-'}</span></div>
      ${s.diff_hidden ? '' : `<div class="row font-bold"><span>${srT('shifts.rpt_diff')}</span><span>${s.difference_cash === null ? '-' : diffText}</span></div>`}

      <div class="border-top text-center" style="margin-top: 15px; font-size: 11px;">
        ${srT('shifts.rpt_printed_at')} ${new Date().toLocaleString()}
      </div>
    </body>
    </html>
  `;

  var iframe = document.getElementById('shiftSlipPrintIframe');
  if (!iframe) {
    iframe = document.createElement('iframe');
    iframe.id = 'shiftSlipPrintIframe';
    iframe.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:0;height:0;border:none;visibility:hidden;';
    document.body.appendChild(iframe);
  }
  var doc = iframe.contentWindow || iframe.contentDocument;
  if (doc.document) doc = doc.document;
  doc.open();
  doc.write(slipHtml);
  doc.close();

  setTimeout(function() {
    try {
      iframe.contentWindow.focus();
      iframe.contentWindow.print();
    } catch (e) {
      var printWin = window.open('', '_blank');
      if (printWin) {
        printWin.document.open();
        printWin.document.write(slipHtml + '<script>window.onload=function(){window.print();};<\/script>');
        printWin.document.close();
      }
    }
  }, 250);
}

// Export PDF ໂດຍ html2pdf
function exportShiftReportPDF() {
  var element = document.getElementById('shiftExportPrintArea');
  if (!element) return;
  var s = window.currentReportSession || {};
  var opt = {
    margin: [8, 8, 8, 8],
    filename: 'shift_report_' + (s.shift_name || 'shift') + '_' + new Date().toISOString().slice(0,10) + '.pdf',
    image: { type: 'jpeg', quality: 0.98 },
    html2canvas: { scale: 2, useCORS: true },
    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
  };
  html2pdf().set(opt).from(element).save();
}

// Export Excel ແບບ Clean XML/HTML ພ້ອມ Lao Font
function exportShiftReportExcel() {
  if (!window.currentReportSession) return;
  var s = window.currentReportSession;
  var bills = window.currentReportBills || [];

  var tableHtml = `
    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
    <head>
      <meta charset="utf-8">
      <style>
        body { font-family: 'Phetsarath OT', 'Noto Sans Lao', sans-serif; }
        th { background-color: #f1f5f9; font-weight: bold; border: 1px solid #000; }
        td { border: 1px solid #000; }
      </style>
    </head>
    <body>
      <table>
        <tr><th colspan="6" style="font-size: 16pt; text-align: center;">${srT('shifts.rpt_title')}: ${s.shift_name}</th></tr>
        <tr><td colspan="6" style="text-align: center;">${srT('shifts.rpt_open_time')} ${s.open_time} | ${srT('shifts.rpt_close_time')} ${s.close_time || srT('shifts.rpt_not_closed')} | ${srT('shifts.rpt_opened_by')} ${s.opened_by_name}</td></tr>
        <tr><td colspan="6"></td></tr>
        <tr><th colspan="3" style="text-align: left;">${srT('shifts.rpt_col_item')}</th><th colspan="3" style="text-align: right;">${srT('shifts.rpt_col_value')}</th></tr>
        <tr><td colspan="3">${srT('shifts.rpt_initial_cash')}</td><td colspan="3" style="text-align: right;">${formatNumber(s.initial_cash)} ₭</td></tr>
        <tr><td colspan="3">${srT('shifts.rpt_cash_sales')}</td><td colspan="3" style="text-align: right;">${formatNumber(s.system_sales_cash)} ₭</td></tr>
        <tr><td colspan="3">${srT('shifts.rpt_transfer_sales')}</td><td colspan="3" style="text-align: right;">${formatNumber(s.system_sales_transfer)} ₭</td></tr>
        <tr><td colspan="3">${srT('shifts.rpt_credit_sales')}</td><td colspan="3" style="text-align: right;">${formatNumber(s.system_sales_debt)} ₭</td></tr>
        <tr><td colspan="3" style="font-weight: bold;">${srT('shifts.rpt_total_sales', s.system_total_bills)}</td><td colspan="3" style="font-weight: bold; text-align: right;">${formatNumber(s.system_total_sales)} ₭</td></tr>
        <tr><td colspan="3">${srT('shifts.rpt_counted_cash')}</td><td colspan="3" style="text-align: right;">${s.counted_sales_cash !== null ? formatNumber(s.counted_sales_cash) + ' ₭' : '-'}</td></tr>
        ${s.diff_hidden ? '' : `<tr><td colspan="3" style="font-weight: bold;">${srT('shifts.rpt_diff')}</td><td colspan="3" style="font-weight: bold; text-align: right;">${s.difference_cash !== null ? formatNumber(s.difference_cash) + ' ₭' : '-'}</td></tr>`}
        <tr><td colspan="6"></td></tr>
        <tr><th colspan="6" style="font-size: 13pt; text-align: left;">${srT('shifts.rpt_bills_title', bills.length)}</th></tr>
        <tr>
          <th>#</th>
          <th>${srT('shifts.rpt_col_bill')}</th>
          <th>${srT('shifts.rpt_col_datetime')}</th>
          <th>${srT('shifts.rpt_col_cashier')}</th>
          <th>${srT('shifts.rpt_col_channel')}</th>
          <th style="text-align: right;">${srT('shifts.rpt_col_amount')}</th>
        </tr>
  `;

  bills.forEach(function (b, idx) {
    tableHtml += `
      <tr>
        <td style="text-align: center;">${idx + 1}</td>
        <td>${b.sale_save_bill}</td>
        <td style="text-align: center;">${b.sale_date} ${b.sale_time}</td>
        <td>${b.user_receive}</td>
        <td style="text-align: center;">${b.type_pay}</td>
        <td style="text-align: right;">${formatNumber(b.sale_barlance)} ₭</td>
      </tr>
    `;
  });

  tableHtml += '</table></body></html>';

  var blob = new Blob([tableHtml], { type: 'application/vnd.ms-excel;charset=utf-8' });
  var url = URL.createObjectURL(blob);
  var a = document.createElement('a');
  a.href = url;
  a.download = 'shift_report_' + (s.shift_name || 'shift') + '_' + new Date().toISOString().slice(0,10) + '.xls';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
}

// ເລີ່ມຕົ້ນໂຫຼດຂໍ້ມູນເມື່ອໜ້າພ້ອມ
$(document).ready(function () {
  initShiftTimePickers();

  if ($('#activeShiftBanner').length || $('#noActiveShiftNotice').length) {
    loadCurrentShiftStatus();
    // Realtime: ກະເປີດ/ປິດ ຂຶ້ນເອງທຸກ 15 ວິນາທີ (ໜ້າລາຍງານຕາມກະ)
    setInterval(function () { if (!document.hidden) loadCurrentShiftStatus(); }, 15000);
  }
  if ($('#shiftsConfigTableBody').length) {
    loadShiftsConfigList();
  }
  if ($('#sessionsTableBody').length) {
    loadSessionsHistory();
  }

  // ດຶງລາຍການກະໃສ່ filter dropdown
  if ($('#filterShiftSelect').length) {
    $.get(window.SHIFTS_API, { action: 'list_shifts' }, function (res) {
      if (res && res.success && res.shifts) {
        var sel = $('#filterShiftSelect');
        res.shifts.forEach(function (s) {
          sel.append(`<option value="${s.id}">${s.shift_name}</option>`);
        });
      }
    }, 'json');
  }
});
</script>
