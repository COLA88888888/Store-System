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
window.currentReportItems = [];

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
      $('#activeShiftInitialCash').text(formatNumber(res.session.initial_cash) + ' <?php echo kipSymbol(); ?>');
      $('#activeShiftBills').text(formatNumber(res.session.system_total_bills));
      $('#activeShiftCashSales').text(formatNumber(res.session.system_sales_cash) + ' <?php echo kipSymbol(); ?>');
      $('#activeShiftTransferSales').text(formatNumber(res.session.system_sales_transfer) + ' <?php echo kipSymbol(); ?>');
      $('#activeShiftTotalSales').text(formatNumber(res.session.system_total_sales) + ' <?php echo kipSymbol(); ?>');

      // ປຸ່ມສະຫຼຸບ (ປຸ່ມປິດກະຢູ່ແຖບນີ້ ເອົາອອກແລ້ວ — ປິດກະທີ່ໜ້າຂາຍ)
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
      // ອອນໄລນ໌ສະເພາະຕອນມີພະນັກງານເປີດກະນີ້ຢູ່ (open_session ຈາກ list_shifts); ກະທີ່ປິດໃຊ້ງານ = ປິດໄວ້
      var statusBadge;
      if (parseInt(s.status) !== 1) {
        statusBadge = '<span class="badge badge-secondary px-2 py-1"><i class="fas fa-pause-circle mr-1"></i> ປິດໄວ້</span>';
      } else if (s.open_session) {
        statusBadge = '<span class="badge badge-success px-2 py-1"><i class="fas fa-circle mr-1" style="font-size: 0.6rem; vertical-align: middle;"></i> ກຳລັງເປີດກະ</span>' +
          '<div class="small text-muted mt-1">' + $('<div>').text(s.open_session.opened_by_name || '-').html() + ' · ' + s.open_session.open_time_fmt + '</div>';
      } else {
        statusBadge = '<span class="badge badge-danger px-2 py-1"><i class="fas fa-power-off mr-1"></i> ປິດໃຊ້ງານ</span>';   // ກະບໍ່ໄດ້ເປີດຢູ່ຕອນນີ້
      }

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

// ຊື່ກະວ່າງ → SweetAlert (ແທນ tooltip "Please fill out this field" ຂອງ browser) ແລ້ວໂຟກັສຊ່ອງຊື່
function shiftNameMissing() {
  var $name = $('#shiftConfigName');
  if ($.trim($name.val() || '') !== '') return false;
  Swal.fire({
    icon: 'warning',
    title: 'ແຈ້ງເຕືອນ',
    text: 'ກະລຸນາປ້ອນຊື່ກະເຮັດວຽກກ່ອນ',
    confirmButtonText: 'ຕົກລົງ',
    confirmButtonColor: '#244886'
  }).then(function () { $name.trigger('focus'); });
  return true;
}

// ບັນທຶກຕັ້ງຄ່າກະ
function saveShiftConfig(e) {
  if (e) e.preventDefault();
  if (shiftNameMissing()) return false;
  // ຕ້ອງຕິກພະນັກງານໃນກະຢ່າງໜ້ອຍ 1 ຄົນ ຈຶ່ງບັນທຶກໄດ້ (ເຊີເວີກວດຊ້ຳ — save_shift)
  var checkedStaff = $('#shiftStaffList input[name="staff_user_ids[]"]:checked').length;
  if (!checkedStaff) {
    Swal.fire({
      icon: 'warning',
      title: 'ແຈ້ງເຕືອນ',
      text: 'ກະລຸນາເລືອກພະນັກງານກະນີ້ກ່ອນ',
      confirmButtonText: 'ຕົກລົງ',
      confirmButtonColor: '#244886'
    }).then(function () {
      var box = document.getElementById('shiftStaffList');
      if (box) {
        box.scrollIntoView({ block: 'center', behavior: 'smooth' });
        box.style.borderColor = '#dc2626';
        box.style.boxShadow = '0 0 0 3px rgba(220, 38, 38, 0.25)';
        setTimeout(function () {
          box.style.borderColor = '#e2e8f0';
          box.style.boxShadow = '';
        }, 2500);
      }
    });
    return false;
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
  var count = $('#shiftStaffList input:checked').length;
  $('#shiftStaffCount').text(count + ' ຄົນ');
  if (count === 0) {
    $('#shiftStaffCount').removeClass('badge-primary badge-success').addClass('badge-danger');
    $('#shiftStaffRequiredNotice')
      .removeClass('text-success text-muted')
      .addClass('text-danger font-weight-bold')
      .html('<i class="fas fa-exclamation-circle mr-1"></i> ກະລຸນາເລືອກພະນັກງານກະນີ້ກ່ອນ');
  } else {
    $('#shiftStaffCount').removeClass('badge-danger badge-primary').addClass('badge-success');
    $('#shiftStaffRequiredNotice')
      .removeClass('text-danger')
      .addClass('text-success font-weight-bold')
      .html('<i class="fas fa-check-circle mr-1"></i> ເລືອກແລ້ວ ' + count + ' ຄົນ');
  }
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

// ກວດສອບເມື່ອກົດປຸ່ມບັນທຶກກະ: ຖ້າບໍ່ເລືອກພະນັກງານກົດບັນທຶກບໍ່ໄດ້ ແລະ ແຈ້ງເຕືອນທັນທີ
$(document).on('click', '#btnSaveShiftConfig', function (e) {
  if (shiftNameMissing()) { e.preventDefault(); return false; }
  var checkedStaff = $('#shiftStaffList input[name="staff_user_ids[]"]:checked').length;
  if (!checkedStaff) {
    e.preventDefault();
    Swal.fire({
      icon: 'warning',
      title: 'ແຈ້ງເຕືອນ',
      text: 'ກະລຸນາເລືອກພະນັກງານກະນີ້ກ່ອນ',
      confirmButtonText: 'ຕົກລົງ',
      confirmButtonColor: '#244886'
    }).then(function () {
      var box = document.getElementById('shiftStaffList');
      if (box) {
        box.scrollIntoView({ block: 'center', behavior: 'smooth' });
        box.style.borderColor = '#dc2626';
        box.style.boxShadow = '0 0 0 3px rgba(220, 38, 38, 0.25)';
        setTimeout(function () {
          box.style.borderColor = '#e2e8f0';
          box.style.boxShadow = '';
        }, 2500);
      }
    });
    return false;
  }
});

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
    $('#closeShiftInitialCashDisplay').text(formatNumber(s.initial_cash) + ' <?php echo kipSymbol(); ?>');
    $('#closeShiftSysCashDisplay').text(formatNumber(s.system_sales_cash) + ' <?php echo kipSymbol(); ?>');
    $('#closeShiftSysTransferDisplay').text(formatNumber(s.system_sales_transfer) + ' <?php echo kipSymbol(); ?>');
    $('#closeShiftSysTotalDisplay').text(formatNumber(s.system_total_sales) + ' <?php echo kipSymbol(); ?>');
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
    display.html('<i class="fas fa-check-circle mr-1"></i> ດຸ່ນດ່ຽງພໍດີ (0 <?php echo kipSymbol(); ?>)').css('color', '#15803d');
  } else if (diff > 0) {
    alertBox.addClass('alert-info');
    display.html('<i class="fas fa-arrow-up mr-1"></i> ເງິນເກີນ: +' + formatNumber(diff) + ' <?php echo kipSymbol(); ?>').css('color', '#0284c7');
  } else {
    alertBox.addClass('alert-danger');
    display.html('<i class="fas fa-arrow-down mr-1"></i> ເງິນຂາດ: ' + formatNumber(diff) + ' <?php echo kipSymbol(); ?>').css('color', '#dc2626');
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

  $('#sessionsTableBody').html('<tr><td colspan="13" class="p-0">' + shiftLoaderHtml(srT('shifts.rpt_loading')) + '</td></tr>');

  var params = { action: 'list_sessions', date_from: dFrom, date_to: dTo, shift_id: shiftId };
  if ($('#filterStoreSelect').length) params.branch = $('#filterStoreSelect').val();   // ບັອກສາຂາ (2 ສາຂາຂຶ້ນໄປ)
  $.get(window.SHIFTS_API, params, function (res) {
    if (!res || !res.success) { showSessionsLoadError(); return; }

    // KPI Cards: ທັງ ລາຍງານປິດກະ ແລະ ລາຍງານລາຍຮັບຕາມກະ
    var totalSales = 0, totalBills = 0, totalDiff = 0;
    var sumInit = 0, sumCash = 0, sumTrf = 0, sumCounted = 0;
    (res.sessions || []).forEach(function (s) {
      sumInit += parseFloat(s.initial_cash) || 0;
      sumCash += parseFloat(s.system_sales_cash) || 0;
      sumTrf += parseFloat(s.system_sales_transfer) || 0;
      totalSales += parseFloat(s.system_total_sales) || 0;
      totalBills += parseInt(s.system_total_bills) || 0;
      if (s.counted_sales_cash !== null) sumCounted += parseFloat(s.counted_sales_cash) || 0;
      if (s.difference_cash !== null) totalDiff += parseFloat(s.difference_cash) || 0;
    });

    // ບັດສະຫຼຸບປິດກະ (Shift Close KPI)
    $('#kpiTotalShifts, #kpiCloseTotalShifts').text(formatNumber((res.sessions || []).length));
    $('#kpiCloseTotalDrawer').text(formatNumber(sumInit) + ' <?php echo kipSymbol(); ?>');
    $('#kpiCloseTotalCounted').text(formatNumber(sumCounted) + ' <?php echo kipSymbol(); ?>');
    var diffEl = $('#kpiTotalDiff, #kpiCloseTotalDiff');
    if (totalDiff > 0) {
      diffEl.text('+' + formatNumber(totalDiff) + ' <?php echo kipSymbol(); ?>');
    } else if (totalDiff < 0) {
      diffEl.text(formatNumber(totalDiff) + ' <?php echo kipSymbol(); ?>');
    } else {
      diffEl.text('0 <?php echo kipSymbol(); ?>');
    }

    // ບັດສະຫຼຸບລາຍຮັບ (Shift Revenue KPI)
    $('#kpiRevTotalSales, #kpiTotalSales').text(formatNumber(totalSales) + ' <?php echo kipSymbol(); ?>');
    $('#kpiRevCashSales').text(formatNumber(sumCash) + ' <?php echo kipSymbol(); ?>');
    $('#kpiRevTransferSales').text(formatNumber(sumTrf) + ' <?php echo kipSymbol(); ?>');
    $('#kpiRevTotalBills, #kpiTotalBills').text(formatNumber(totalBills));

    window.allSessionsRaw = res.sessions || [];
    applySessionsSearch();
  }, 'json').fail(showSessionsLoadError);
}

// ໂຫຼດບໍ່ສຳເລັດ → ແທນຕົວໂຫຼດດ້ວຍຂໍ້ຄວາມ (ບໍ່ໃຫ້ໝູນຄ້າງ)
function showSessionsLoadError() {
  $('#sessionsTableBody').html('<tr><td colspan="13" class="text-center text-danger py-5 font-weight-bold"><i class="fas fa-exclamation-circle mr-1"></i> ' + srT('shifts.pg_load_error') + '</td></tr>');
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
    return [s.store_name, s.shift_name, s.opened_by_name, s.closed_by_name, s.staff_names, s.notes, s.open_time, s.close_time]
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
    tbody.html('<tr><td colspan="13" class="text-center text-muted py-5 font-weight-bold">' + srT('shifts.pg_empty_sessions') + '</td></tr>');
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

  if (window.SHIFT_REPORT_MODE === 'revenue') {
    // ໂໝດ: ລາຍງານລາຍຮັບຕາມກະ — ຖັນໂອນສະແດງແຕ່ຈຳນວນເງິນ; ທະນາຄານເບິ່ງໃນຟອມລາຍລະອຽດ (ປຸ່ມ .shift-detail-btn)
    pageRows.forEach(function (s, i) {
      var tr = $('<tr></tr>');
      tr.html(`
        <td class="text-center align-middle font-weight-bold">${startIdx + i + 1}</td>
        <td class="align-middle" style="font-size: 0.85rem;"><i class="fas fa-store text-info mr-1"></i>${$('<div>').text(s.store_name || '-').html()}</td>
        <td class="align-middle font-weight-bold text-dark">${s.shift_name}</td>
        <td class="align-middle" style="font-size: 0.85rem;">
          <div><i class="fas fa-clock text-muted mr-1"></i>${s.open_time}</div>
          <div class="text-muted">${s.close_time || 'ກຳລັງຂາຍ'}</div>
        </td>
        <td class="align-middle" style="font-size: 0.85rem;">
          <div><i class="fas fa-user text-primary mr-1"></i>${$('<div>').text(s.staff_names || s.opened_by_name || '-').html()}</div>
        </td>
        <td class="text-center align-middle font-weight-bold text-dark">${formatNumber(s.system_total_bills)}</td>
        <td class="text-right align-middle font-weight-bold text-success">${formatNumber(s.system_sales_cash)} <?php echo kipSymbol(); ?></td>
        <td class="text-right align-middle font-weight-bold text-info">${formatNumber(s.system_sales_transfer)} <?php echo kipSymbol(); ?></td>
        <td class="text-right align-middle font-weight-bold text-warning">${formatNumber(s.system_sales_debt)} <?php echo kipSymbol(); ?></td>
        <td class="text-right align-middle font-weight-bold text-primary" style="font-size: 1rem;">${formatNumber(s.system_total_sales)} <?php echo kipSymbol(); ?></td>
        <td class="text-center align-middle">
          <button type="button" class="btn btn-sm btn-outline-success shift-detail-btn d-inline-flex align-items-center justify-content-center" onclick="openShiftReportModal(${s.id})" title="ເບິ່ງລາຍການສິນຄ້າ ແລະ ບິນຂາຍ" style="width: 32px; height: 32px; padding: 0; border-radius: 6px; font-size: 0.95rem;">
            <i class="fas fa-list-alt"></i>
          </button>
        </td>
      `);
      tbody.append(tr);
    });
  } else {
    // ໂໝດ: ລາຍງານປິດກະ
    pageRows.forEach(function (s, i) {
      var isOpen = (s.status === 'open');
      var statusBadge = isOpen
        ? '<span class="badge badge-primary px-2 py-1"><i class="fas fa-spinner fa-spin mr-1"></i> ' + srT('shifts.rpt_status_open') + '</span>'
        : '<span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> ' + srT('shifts.rpt_status_closed') + '</span>';

      var diffBadge = '-';
      if (s.difference_cash !== null) {
        var d = parseFloat(s.difference_cash);
        if (d === 0) {
          diffBadge = '<span class="badge badge-success font-weight-bold">0 <?php echo kipSymbol(); ?> (' + srT('shifts.rpt_balanced') + ')</span>';
        } else if (d > 0) {
          diffBadge = '<span class="badge badge-info font-weight-bold">+' + formatNumber(d) + ' <?php echo kipSymbol(); ?></span>';
        } else {
          diffBadge = '<span class="badge badge-danger font-weight-bold">' + formatNumber(d) + ' <?php echo kipSymbol(); ?></span>';
        }
      }

      var tr = $('<tr></tr>');
      tr.html(`
        <td class="text-center align-middle font-weight-bold">${startIdx + i + 1}</td>
        <td class="align-middle" style="font-size: 0.85rem;"><i class="fas fa-store text-info mr-1"></i>${$('<div>').text(s.store_name || '-').html()}</td>
        <td class="align-middle font-weight-bold text-dark">${s.shift_name}</td>
        <td class="align-middle" style="font-size: 0.85rem;">
          <div>${s.open_time}</div>
          <div>${s.close_time || '<span class="text-muted">' + srT('shifts.pg_selling') + '</span>'}</div>
        </td>
        <td class="align-middle" style="font-size: 0.85rem;">
          <div><strong>${srT('shifts.pg_lbl_open')}</strong> ${s.opened_by_name}</div>
          <div><strong>${srT('shifts.pg_lbl_close')}</strong> ${shiftCloserName(s.closed_by_name)}</div>
        </td>
        <td class="text-right align-middle font-weight-bold text-secondary">${formatNumber(s.initial_cash)} <?php echo kipSymbol(); ?></td>
        <td class="text-right align-middle font-weight-bold text-success">${formatNumber(s.system_sales_cash)} <?php echo kipSymbol(); ?></td>
        <td class="text-right align-middle font-weight-bold text-dark">${s.counted_sales_cash !== null ? formatNumber(s.counted_sales_cash) + ' <?php echo kipSymbol(); ?>' : '-'}</td>
        <td class="text-center align-middle">${diffBadge}</td>
        <td class="text-center align-middle">${statusBadge}</td>
        <td class="text-center align-middle">
          <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center" onclick="openShiftReportModal(${s.id})" title="ໃບສະຫຼຸບປິດກະ" style="width: 32px; height: 32px; padding: 0; border-radius: 6px; font-size: 0.95rem;">
            <i class="fas fa-file-invoice-dollar"></i>
          </button>
        </td>
      `);
      tbody.append(tr);
    });
  }

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
  var store = $('#filterStoreSelect');
  if (store.length && store.val() !== String(store.data('default'))) {
    store.val(String(store.data('default')));
    onSessionsBranchChange();   // ໂຫຼດກະຂອງສາຂາເດີມ + ປະຫວັດ
    return;
  }
  loadSessionsHistory();
}

// ບັອກກະ (#filterShiftSelect): ກະຂອງສາຂາທີ່ເລືອກ (ທຸກສາຂາ → ທຸກກະ ພ້ອມຊື່ສາຂາ)
function loadShiftFilterOptions(done) {
  var sel = $('#filterShiftSelect');
  if (!sel.length) return;
  var params = { action: 'list_shifts' };
  var store = $('#filterStoreSelect');
  if (store.length) params.branch = store.val();
  $.get(window.SHIFTS_API, params, function (res) {
    sel.find('option:not([value="0"])').remove();
    if (res && res.success && res.shifts) {
      res.shifts.forEach(function (s) {
        var label = (params.branch === '0' && s.store_name) ? s.shift_name + ' (' + s.store_name + ')' : s.shift_name;
        sel.append($('<option>').val(s.id).text(label));
      });
    }
    if (done) done();
  }, 'json');
}

// ປ່ຽນສາຂາ: ລ້າງກະທີ່ເລືອກ (ກະເປັນຂອງແຕ່ລະສາຂາ) ແລ້ວໂຫຼດກະ + ປະຫວັດຂອງສາຂານັ້ນ
function onSessionsBranchChange() {
  $('#filterShiftSelect').val('0');
  window.sessionsCurrentPage = 1;
  loadShiftFilterOptions();
  loadSessionsHistory();
}

// ============================================================================
// 6. ລາຍງານສະຫຼຸບກະ ແລະ Export Excel / PDF (Shift Report Modal)
// ============================================================================
function openShiftReportModal(sessionId, wasJustClosed) {
  // ລາຍຮັບຕາມກະ: ຟອມກວ້າງ (modal-xl) ແລະ ສູງເກືອບເຕັມຈໍ — ເລື່ອນລົງທັງຟອມ (ຕາຕະລາງຂ້າງໃນບໍ່ມີສະກໍບາຂອງຕົນເອງ)
  var isRevenue = (window.SHIFT_REPORT_MODE === 'revenue');
  $('#modalShiftReport .modal-dialog').toggleClass('modal-xl', isRevenue).toggleClass('modal-lg', !isRevenue);
  $('#modalShiftReport .modal-content').css('height', isRevenue ? 'calc(100vh - 40px)' : '');
  if (isRevenue) {
    $('#modalShiftReportTitle').html('<i class="fas fa-chart-line text-success mr-2"></i> ລາຍລະອຽດລາຍຮັບປະຈຳກະ');
  } else {
    $('#modalShiftReportTitle').html('<i class="fas fa-cash-register text-warning mr-2"></i> ໃບສະຫຼຸບປິດກະເຮັດວຽກ');
  }
  $('#modalShiftReport').modal('show');
  $('#shiftReportContent').html(shiftLoaderHtml(srT('shifts.rpt_loading')));

  $.get(window.SHIFTS_API, { action: 'get_session_details', session_id: sessionId }, function (res) {
    if (!res || !res.success || !res.session) {
      $('#shiftReportContent').html('<div class="alert alert-danger">' + srT('shifts.rpt_not_found') + '</div>');
      return;
    }

    window.currentReportSession = res.session;
    window.currentReportBills = res.bills || [];
    window.currentReportItems = res.items || [];
    window.currentReportChannels = res.items_by_channel || [];

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

// ປ້າຍ "ເງິນສົດ" ກ່ອງຂອບສີຂຽວ — ຖັນຊ່ອງທາງຂອງບິນ ແລະ ຕາຕະລາງສິນຄ້າແຍກຕາມການຊຳລະ
function cashBadge() {
  return '<span class="badge d-inline-flex align-items-center" style="gap: 5px; padding: 3px 9px; font-size: 0.8rem; font-weight: 700; ' +
         'background: #dcfce7; color: #15803d; border: 1.5px solid #22c55e;"><span>ເງິນສົດ</span></span>';
}

function renderShiftReportContent(s, bills) {
  var diff = s.difference_cash !== null ? parseFloat(s.difference_cash) : 0;
  var diffText = '0 <?php echo kipSymbol(); ?> (' + srT('shifts.rpt_balanced') + ')';
  var diffColor = '#15803d';
  if (diff > 0) {
    diffText = '+' + formatNumber(diff) + ' <?php echo kipSymbol(); ?> (' + srT('shifts.rpt_over') + ')';
    diffColor = '#0284c7';
  } else if (diff < 0) {
    diffText = formatNumber(diff) + ' <?php echo kipSymbol(); ?> (' + srT('shifts.rpt_short') + ')';
    diffColor = '#dc2626';
  }

  var billsRows = '';
  if (bills && bills.length > 0) {
    bills.forEach(function (b, i) {
      var isTransfer = (b.type_pay && (b.type_pay.includes('ໂອນ') || b.type_pay.includes('QR'))) || (parseFloat(b.qr_received) > 0);
      var bName = b.bank_display_name || b.bank_name || '';
      var payTypeHtml = '';
      if (isTransfer) {
        var bankImg = b.bank_logo_url ? `<img src="${b.bank_logo_url}" alt="${$('<div>').text(bName).html()}" style="width: 18px; height: 18px; object-fit: contain; border-radius: 3px; vertical-align: middle;" onerror="this.style.display='none'">` : '<i class="fas fa-university text-primary" style="font-size: 12px;"></i>';
        var label = bName || b.type_pay;   // ໂລໂກ້ + ຊື່ທະນາຄານ ພໍ (ບໍ່ມີທະນາຄານ → ປະເພດການຊຳລະ)
        payTypeHtml = `<span class="badge badge-light border d-inline-flex align-items-center" style="padding: 3px 8px; gap: 5px; font-size: 0.82rem; font-weight: 700;">
          ${bankImg} <span class="text-primary">${$('<div>').text(label).html()}</span>
        </span>`;
        // ຈ່າຍປົນ ເງິນສົດ + ໂອນ: ສະແດງປ້າຍເງິນສົດນຳ (ຈຳນວນແຕ່ລະທາງຢູ່ຕາຕະລາງ "ສິນຄ້າແຍກຕາມການຊຳລະ")
        if (b.cash_part > 0 && b.transfer_part > 0) payTypeHtml = cashBadge() + ' ' + payTypeHtml;
      } else {
        payTypeHtml = (b.type_pay || '').indexOf('ເງິນສົດ') !== -1 ? cashBadge()
          : `<span class="badge badge-light border" style="padding: 3px 8px; font-size: 0.82rem;">${$('<div>').text(b.type_pay).html()}</span>`;
      }

      // ລາຍຮັບຕາມກະ: ບັອກເງິນສົດ ແລະ ເງິນໂອນ ແຍກຖັນກັນ ພ້ອມຈຳນວນທີ່ຮັບແຕ່ລະທາງ (cash_part / transfer_part)
      var channelCells = `<td class="text-center" style="white-space: nowrap;">${payTypeHtml}</td>`;
      if (window.SHIFT_REPORT_MODE === 'revenue') {
        var cashPart = parseFloat(b.cash_part) || 0, trfPart = parseFloat(b.transfer_part) || 0;
        var bankHtml = isTransfer ? payTypeHtml.replace(cashBadge() + ' ', '') : '';
        channelCells =
          `<td class="text-center" style="white-space: nowrap;">${cashPart > 0 ? cashBadge() + `<div class="small font-weight-bold text-success mt-1">${formatNumber(cashPart)} <?php echo kipSymbol(); ?></div>` : '<span class="text-muted">-</span>'}</td>` +
          `<td class="text-center" style="white-space: nowrap;">${trfPart > 0 ? bankHtml + `<div class="small font-weight-bold text-info mt-1">${formatNumber(trfPart)} <?php echo kipSymbol(); ?></div>` : '<span class="text-muted">-</span>'}</td>`;
      }

      billsRows += `
        <tr>
          <td class="text-center" style="white-space: nowrap;">${i + 1}</td>
          <td style="white-space: nowrap;"><strong>${b.sale_save_bill}</strong></td>
          <td class="text-center" style="white-space: nowrap;">${b.sale_date} ${b.sale_time}</td>
          <td style="white-space: nowrap;">${b.user_receive}</td>
          ${channelCells}
          <td class="text-right font-weight-bold text-dark" style="white-space: nowrap;">${formatNumber(b.sale_barlance)} <?php echo kipSymbol(); ?></td>
        </tr>
      `;
    });
  } else {
    billsRows = '<tr><td colspan="' + (window.SHIFT_REPORT_MODE === 'revenue' ? 7 : 6) + '" class="text-center text-muted py-3">' + srT('shifts.rpt_no_bills') + '</td></tr>';
  }

  // ສິນຄ້າທີ່ຂາຍໃນກະ (ລວມຕາມສິນຄ້າ — get_session_details.items)
  var items = window.currentReportItems || [];
  var itemsTotal = { qty: 0, discount: 0, net: 0 };
  var itemRows = items.map(function (it, i) {
    itemsTotal.qty += it.qty; itemsTotal.discount += it.discount; itemsTotal.net += it.net;
    return `
        <tr>
          <td class="text-center" style="white-space: nowrap;">${i + 1}</td>
          <td style="white-space: nowrap;">${$('<div>').text(it.product_name || '-').html()}</td>
          <td class="text-center font-weight-bold" style="white-space: nowrap;">${formatNumber(it.qty)}</td>
          <td class="text-right" style="white-space: nowrap;">${formatNumber(it.price)} <?php echo kipSymbol(); ?></td>
          <td class="text-right" style="white-space: nowrap;">${it.discount ? formatNumber(it.discount) + ' <?php echo kipSymbol(); ?>' : '-'}</td>
          <td class="text-right font-weight-bold text-dark" style="white-space: nowrap;">${formatNumber(it.net)} <?php echo kipSymbol(); ?></td>
        </tr>`;
  }).join('') || '<tr><td colspan="6" class="text-center text-muted py-3">' + srT('shifts.rpt_no_items') + '</td></tr>';

  var isRevenueMode = (window.SHIFT_REPORT_MODE === 'revenue');
  var headerTitle = isRevenueMode ? 'ລາຍລະອຽດລາຍຮັບຍອດຂາຍປະຈຳກະ' : srT('shifts.rpt_title');
  // ລາຍຮັບຕາມກະ: ຕາຕະລາງໃນຟອມບໍ່ມີສະກໍບາຂອງຕົນເອງ — ເລື່ອນລົງທັງຟອມ
  var innerScroll = function (h) { return isRevenueMode ? '' : 'max-height: ' + h + 'px; overflow-y: auto;'; };

  // ສິນຄ້າແຍກຕາມການຊຳລະ (get_session_details.items_by_channel) + ເງິນໂອນແຍກຕາມທະນາຄານ
  var channels = window.currentReportChannels || [];
  var esc = function (v) { return $('<div>').text(v == null ? '' : String(v)).html(); };
  var bankLogo = function (url, name) {
    return url ? `<img src="${url}" alt="${esc(name)}" style="width: 18px; height: 18px; object-fit: contain; border-radius: 3px;" onerror="this.style.display='none'">`
               : '<i class="fas fa-university text-primary" style="font-size: 12px;"></i>';
  };
  var chStyle = {
    cash:     { icon: 'fa-money-bill-wave', color: '#15803d', bg: '#f0fdf4' },
    transfer: { icon: 'fa-university',      color: '#0e7490', bg: '#ecfeff' },
    mixed:    { icon: 'fa-random',          color: '#7c3aed', bg: '#f5f3ff' },
    debt:     { icon: 'fa-file-invoice',    color: '#b45309', bg: '#fffbeb' }
  };
  var transferBanksHtml = channels.filter(function (c) { return c.type === 'transfer'; }).map(function (c) {
    return `<tr><td class="pl-5 text-muted" style="font-size: 0.86rem;"><span class="d-inline-flex align-items-center" style="gap: 6px;">${bankLogo(c.bank_logo_url, c.bank_name)} ${esc(c.bank_name)} <span class="small">(${c.bill_count} ບິນ)</span></span></td>
            <td class="text-right pr-3 text-info" style="font-size: 0.9rem;">${formatNumber(c.total)} <?php echo kipSymbol(); ?></td></tr>`;
  }).join('');
  // ຕາຕະລາງດຽວ ຈັດຕາມບິນ: ສິນຄ້າຂອງແຕ່ລະບິນ (bills[].lines) + ຖັນ "ການຊຳລະ" ຮວມແຖວຂອງບິນ ບອກຮັບແຕ່ລະທາງເທົ່າໃດ
  // (bills[].cash_part / transfer_part / debt_part — ຈ່າຍປົນ ເງິນສົດ + ໂອນ ສະແດງທັງສອງ ພ້ອມຈຳນວນ)
  var payBox = function (type, b) {
    if (type === 'cash') return cashBadge();
    var st = chStyle[type];
    var inner = type === 'transfer'
      ? bankLogo(b.bank_logo_url, b.bank_display_name) + ' <span>' + esc(b.bank_display_name || 'ໂອນ / QR') + '</span>'
      : '<i class="fas ' + st.icon + '"></i> <span>ຕິດໜີ້</span>';
    return '<span class="badge border d-inline-flex align-items-center" style="gap: 5px; padding: 3px 8px; font-size: 0.8rem; font-weight: 700; background: ' + st.bg + '; color: ' + st.color + ';">' + inner + '</span>';
  };
  // ການຊຳລະຂອງແຖວ: ທາງດຽວ = ປ້າຍທາງນັ້ນ (ໂລໂກ້+ຊື່ທະນາຄານ / ເງິນສົດ); ຈ່າຍປົນ = ທຸກປ້າຍ ພ້ອມຈຳນວນທີ່ຮັບຂອງບິນ
  var payCell = function (b) {
    var parts = [['cash', b.cash_part], ['transfer', b.transfer_part], ['debt', b.debt_part]].filter(function (p) { return p[1] > 0; });
    if (parts.length === 1) return payBox(parts[0][0], b);
    return '<span class="d-inline-flex align-items-center text-nowrap" style="gap: 6px;">' + parts.map(function (p) {
      return payBox(p[0], b) + '<span class="font-weight-bold small">' + formatNumber(p[1]) + ' <?php echo kipSymbol(); ?></span>';
    }).join('<span class="text-muted">+</span>') + '</span>';
  };
  var chOrder = { cash: 0, transfer: 1, mixed: 2, debt: 3 };
  var payBills = (bills || []).filter(function (b) { return b.lines && b.lines.length; }).slice().sort(function (a, b) {
    return (chOrder[a.channel_type] - chOrder[b.channel_type]) || String(a.bank_display_name || '').localeCompare(String(b.bank_display_name || ''));
  });
  var chRowNo = 0, chTotal = { qty: 0, discount: 0, net: 0, cash: 0, transfer: 0, debt: 0 };
  var chRows = payBills.map(function (b) {
    chTotal.cash += +b.cash_part || 0; chTotal.transfer += +b.transfer_part || 0; chTotal.debt += +b.debt_part || 0;
    var pay = payCell(b);
    // ແຕ່ລະແຖວສະແດງເລກບິນ ແລະ ການຊຳລະ (ທະນາຄານ) ຂອງຕົນເອງ — ບໍ່ຮວມແຖວ
    return b.lines.map(function (it) {
      chRowNo++;
      chTotal.qty += it.qty; chTotal.discount += it.discount; chTotal.net += it.net;
      return `<tr>
        <td class="text-center">${chRowNo}</td>
        <td class="font-weight-bold">${esc(b.sale_save_bill)}</td>
        <td>${esc(it.product_name)}</td>
        <td class="text-center font-weight-bold">${formatNumber(it.qty)}</td><td class="text-right">${formatNumber(it.price)} <?php echo kipSymbol(); ?></td>
        <td class="text-right">${it.discount ? formatNumber(it.discount) + ' <?php echo kipSymbol(); ?>' : '-'}</td><td class="text-right font-weight-bold">${formatNumber(it.net)} <?php echo kipSymbol(); ?></td>
        <td>${pay}</td>
      </tr>`;
    }).join('');
  }).join('');
  var channelsHtml = !isRevenueMode ? '' : `
      <div class="mt-3">
        <h6 class="font-weight-bold text-dark mb-2" style="font-size: 0.95rem;">
          <i class="fas fa-wallet text-primary mr-1"></i> ສິນຄ້າແຍກຕາມການຊຳລະ (ເງິນສົດ / ເງິນໂອນ)
        </h6>
        <div class="table-responsive">
          <table class="table table-sm table-striped table-bordered mb-0 text-nowrap" style="font-size: 0.85rem;">
            <thead class="bg-light text-center"><tr><th style="width: 40px;">#</th><th>${srT('shifts.rpt_col_bill')}</th><th>${srT('shifts.rpt_col_product')}</th>
              <th style="width: 80px;">${srT('shifts.rpt_col_qty')}</th><th class="text-right">${srT('shifts.rpt_col_price')}</th>
              <th class="text-right">${srT('shifts.rpt_col_discount')}</th><th class="text-right">${srT('shifts.rpt_col_total')}</th><th>ການຊຳລະ (ຮັບເງິນ)</th></tr></thead>
            <tbody>${chRows || '<tr><td colspan="8" class="text-center text-muted py-3">' + srT('shifts.rpt_no_items') + '</td></tr>'}</tbody>
            ${chRowNo ? `<tfoot><tr class="font-weight-bold" style="background: #f1f5f9;">
              <td colspan="3" class="text-right">${srT('shifts.rpt_items_total')}</td>
              <td class="text-center">${formatNumber(chTotal.qty)}</td><td></td>
              <td class="text-right">${chTotal.discount ? formatNumber(chTotal.discount) + ' <?php echo kipSymbol(); ?>' : '-'}</td>
              <td class="text-right text-primary">${formatNumber(chTotal.net)} <?php echo kipSymbol(); ?></td>
              <td>
                <div class="d-flex justify-content-between"><span class="text-success">ຮັບເງິນສົດ</span><span class="text-success">${formatNumber(chTotal.cash)} <?php echo kipSymbol(); ?></span></div>
                <div class="d-flex justify-content-between"><span class="text-info">ຮັບເງິນໂອນ</span><span class="text-info">${formatNumber(chTotal.transfer)} <?php echo kipSymbol(); ?></span></div>
                ${chTotal.debt ? `<div class="d-flex justify-content-between"><span class="text-warning">ຕິດໜີ້</span><span class="text-warning">${formatNumber(chTotal.debt)} <?php echo kipSymbol(); ?></span></div>` : ''}
              </td>
            </tr></tfoot>` : ''}
          </table>
        </div>
      </div>`;

  var summaryBoxHtml = '';
  if (isRevenueMode) {
    summaryBoxHtml = `
      <div class="card border mb-3" style="border-radius: 8px; overflow: hidden;">
        <div class="card-header bg-light font-weight-bold py-2" style="font-size: 0.92rem;">
          <i class="fas fa-coins text-success mr-1"></i> ສະຫຼຸບລາຍຮັບຍອດຂາຍຕາມຊ່ອງທາງ
        </div>
        <div class="card-body p-0">
          <table class="table table-sm table-bordered m-0" style="font-size: 0.92rem;">
            <tbody>
              <tr>
                <td style="width: 55%;" class="pl-3 font-weight-bold">1. ຍອດຂາຍເງິນສົດ</td>
                <td class="text-right pr-3 font-weight-bold text-success" style="font-size: 1rem;">${formatNumber(s.system_sales_cash)} <?php echo kipSymbol(); ?></td>
              </tr>
              <tr>
                <td class="pl-3 font-weight-bold">2. ຍອດຂາຍໂອນ / QR</td>
                <td class="text-right pr-3 font-weight-bold text-info" style="font-size: 1rem;">${formatNumber(s.system_sales_transfer)} <?php echo kipSymbol(); ?></td>
              </tr>
              ${transferBanksHtml}
              <tr>
                <td class="pl-3 font-weight-bold">3. ຍອດຂາຍຕິດໜີ້</td>
                <td class="text-right pr-3 font-weight-bold text-warning" style="font-size: 1rem;">${formatNumber(s.system_sales_debt)} <?php echo kipSymbol(); ?></td>
              </tr>
              <tr class="bg-light font-weight-bold" style="background: #f1f5f9;">
                <td class="pl-3" style="font-size: 0.98rem;">ລວມລາຍຮັບຍອດຂາຍທັງໝົດ (${formatNumber(s.system_total_bills)} ບິນ)</td>
                <td class="text-right pr-3 font-weight-bold text-primary" style="font-size: 1.15rem;">${formatNumber(s.system_total_sales)} <?php echo kipSymbol(); ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    `;
  } else {
    summaryBoxHtml = `
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
                <td class="text-right pr-3 font-weight-bold text-secondary">${formatNumber(s.initial_cash)} <?php echo kipSymbol(); ?></td>
              </tr>
              <tr>
                <td class="pl-3">${srT('shifts.rpt_cash_sales')}</td>
                <td class="text-right pr-3 font-weight-bold text-success">${formatNumber(s.system_sales_cash)} <?php echo kipSymbol(); ?></td>
              </tr>
              <tr>
                <td class="pl-3">${srT('shifts.rpt_transfer_sales')}</td>
                <td class="text-right pr-3 font-weight-bold text-info">${formatNumber(s.system_sales_transfer)} <?php echo kipSymbol(); ?></td>
              </tr>
              <tr>
                <td class="pl-3">${srT('shifts.rpt_credit_sales')}</td>
                <td class="text-right pr-3 font-weight-bold text-warning">${formatNumber(s.system_sales_debt)} <?php echo kipSymbol(); ?></td>
              </tr>
              <tr class="bg-light font-weight-bold">
                <td class="pl-3">${srT('shifts.rpt_total_sales', formatNumber(s.system_total_bills))}</td>
                <td class="text-right pr-3 font-weight-bold text-primary" style="font-size: 1.05rem;">${formatNumber(s.system_total_sales)} <?php echo kipSymbol(); ?></td>
              </tr>
              <tr style="background: #f1f5f9;">
                <td class="pl-3 font-weight-bold text-dark">${srT('shifts.rpt_counted_cash')}</td>
                <td class="text-right pr-3 font-weight-bold text-dark" style="font-size: 1.05rem;">
                  ${s.counted_sales_cash !== null ? formatNumber(s.counted_sales_cash) + ' <?php echo kipSymbol(); ?>' : '-'}
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
    `;
  }

  var html = `
    <div id="shiftExportPrintArea" style="font-family: 'Nuanta', 'Noto Sans Lao', sans-serif;">
      <!-- Report Header -->
      <div class="text-center border-bottom pb-3 mb-3">
        <h4 class="font-weight-bold mb-1" style="color: #1e293b;">${headerTitle}</h4>
        <div class="text-muted" style="font-size: 0.95rem;">
          ${s.store_name ? `<strong>${srT('shifts.rpt_branch')}</strong> ${$('<div>').text(s.store_name).html()} | ` : ''}<strong>${srT('shifts.rpt_shift')}</strong> ${s.shift_name} | <strong>${srT('shifts.rpt_status')}</strong> ${s.status === 'open' ? srT('shifts.rpt_status_open') : srT('shifts.rpt_status_closed')}
        </div>
      </div>

      <!-- Info Meta Grid -->
      <div class="row mb-3" style="font-size: 0.88rem;">
        <div class="col-6 col-md-3"><strong>${srT('shifts.rpt_open_time')}</strong><br><span class="text-muted">${s.open_time}</span></div>
        <div class="col-6 col-md-3"><strong>${srT('shifts.rpt_close_time')}</strong><br><span class="text-muted">${s.close_time || srT('shifts.rpt_not_closed')}</span></div>
        <div class="col-6 col-md-3"><strong>${srT('shifts.rpt_opened_by')}</strong><br><span class="text-muted">${s.opened_by_name}</span></div>
        <div class="col-6 col-md-3"><strong>${srT('shifts.rpt_closed_by')}</strong><br><span class="text-muted">${s.closed_by_name || '-'}</span></div>
      </div>

      ${summaryBoxHtml}

      ${s.notes ? `<div class="alert alert-secondary py-2 px-3 mb-3" style="font-size: 0.88rem;"><strong>${srT('shifts.rpt_notes')}</strong> ${s.notes}</div>` : ''}
${channelsHtml}
      <!-- Items Sold in Shift -->
      <div class="mt-3">
        <h6 class="font-weight-bold text-dark mb-2" style="font-size: 0.95rem;">
          <i class="fas fa-box-open text-primary mr-1"></i> ${srT('shifts.rpt_items_title', items.length)}
        </h6>
        <div class="table-responsive" style="${innerScroll(320)} overflow-x: auto; -webkit-overflow-scrolling: touch;">
          <table class="table table-sm table-striped table-bordered mb-0 text-nowrap" style="font-size: 0.85rem; white-space: nowrap;">
            <thead class="bg-light text-center">
              <tr style="white-space: nowrap;">
                <th style="width: 40px; white-space: nowrap;">#</th>
                <th style="white-space: nowrap;">${srT('shifts.rpt_col_product')}</th>
                <th style="width: 80px; white-space: nowrap;">${srT('shifts.rpt_col_qty')}</th>
                <th class="text-right" style="white-space: nowrap;">${srT('shifts.rpt_col_price')}</th>
                <th class="text-right" style="white-space: nowrap;">${srT('shifts.rpt_col_discount')}</th>
                <th class="text-right" style="white-space: nowrap;">${srT('shifts.rpt_col_total')}</th>
              </tr>
            </thead>
            <tbody>${itemRows}</tbody>
            ${items.length ? `<tfoot><tr class="font-weight-bold" style="background: #f1f5f9; white-space: nowrap;">
              <td colspan="2" class="text-right" style="white-space: nowrap;">${srT('shifts.rpt_items_total')}</td>
              <td class="text-center" style="white-space: nowrap;">${formatNumber(itemsTotal.qty)}</td>
              <td style="white-space: nowrap;"></td>
              <td class="text-right" style="white-space: nowrap;">${itemsTotal.discount ? formatNumber(itemsTotal.discount) + ' <?php echo kipSymbol(); ?>' : '-'}</td>
              <td class="text-right text-primary" style="white-space: nowrap;">${formatNumber(itemsTotal.net)} <?php echo kipSymbol(); ?></td>
            </tr></tfoot>` : ''}
          </table>
        </div>
      </div>

      <!-- Detailed Bills Table -->
      <div class="mt-3">
        <h6 class="font-weight-bold text-dark mb-2" style="font-size: 0.95rem;">
          <i class="fas fa-list-alt text-primary mr-1"></i> ${srT('shifts.rpt_bills_title', bills.length)}
        </h6>
        <div class="table-responsive" style="${innerScroll(280)} overflow-x: auto; -webkit-overflow-scrolling: touch;">
          <table class="table table-sm table-striped table-hover table-bordered mb-0 text-nowrap" style="font-size: 0.85rem; white-space: nowrap;">
            <thead class="bg-light text-center">
              <tr style="white-space: nowrap;">
                <th style="width: 40px; white-space: nowrap;">#</th>
                <th style="white-space: nowrap;">${srT('shifts.rpt_col_bill')}</th>
                <th style="white-space: nowrap;">${srT('shifts.rpt_col_datetime')}</th>
                <th style="white-space: nowrap;">${srT('shifts.rpt_col_cashier')}</th>
                ${isRevenueMode ? '<th class="text-center" style="white-space: nowrap;">ເງິນສົດ</th><th class="text-center" style="white-space: nowrap;">ເງິນໂອນ</th>'
                                : `<th style="white-space: nowrap;">${srT('shifts.rpt_col_channel')}</th>`}
                <th class="text-right" style="white-space: nowrap;">${srT('shifts.rpt_col_amount')}</th>
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
  var diffText = diff === 0 ? '0 <?php echo kipSymbol(); ?> (' + srT('shifts.rpt_balanced') + ')' : (diff > 0 ? '+' + formatNumber(diff) + ' <?php echo kipSymbol(); ?> (' + srT('shifts.rpt_over') + ')' : formatNumber(diff) + ' <?php echo kipSymbol(); ?> (' + srT('shifts.rpt_short') + ')');

  var slipHtml = `<!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>${srT('shifts.rpt_slip_title')} - ${s.shift_name}</title>
      <style>
        @page { size: 80mm auto; margin: 2mm 3mm; }
        * { box-sizing: border-box; }
        body { font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Phetsarath OT', Arial, sans-serif; font-size: 13px; line-height: 1.4; margin: 0; padding: 6px; color: #000; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
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
      <div class="row"><span>${srT('shifts.rpt_initial_cash')}</span><span class="font-bold">${formatNumber(s.initial_cash)} <?php echo kipSymbol(); ?></span></div>
      <div class="row"><span>${srT('shifts.rpt_cash_sales')}</span><span class="font-bold">${formatNumber(s.system_sales_cash)} <?php echo kipSymbol(); ?></span></div>
      <div class="row"><span>${srT('shifts.rpt_transfer_sales')}</span><span class="font-bold">${formatNumber(s.system_sales_transfer)} <?php echo kipSymbol(); ?></span></div>
      <div class="row"><span>${srT('shifts.rpt_credit_sales')}</span><span class="font-bold">${formatNumber(s.system_sales_debt)} <?php echo kipSymbol(); ?></span></div>
      <div class="row font-bold" style="font-size: 13.5px;"><span>${srT('shifts.rpt_total_sales', s.system_total_bills)}</span><span>${formatNumber(s.system_total_sales)} <?php echo kipSymbol(); ?></span></div>

      <div class="border-top"></div>
      <div class="row font-bold"><span>${srT('shifts.rpt_counted_cash')}</span><span>${s.counted_sales_cash !== null ? formatNumber(s.counted_sales_cash) + ' <?php echo kipSymbol(); ?>' : '-'}</span></div>
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

<?php require __DIR__ . '/js/print_a4.php'; ?>

<?php require __DIR__ . '/js/export_excel.php'; ?>

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
    // Realtime: ພະນັກງານເປີດ/ປິດກະ → ຖັນສະຖານະປ່ຽນເອງທຸກ 15 ວິນາທີ
    setInterval(function () { if (!document.hidden) loadShiftsConfigList(); }, 15000);
  }
  if ($('#sessionsTableBody').length) {
    loadSessionsHistory();
  }

  // ດຶງລາຍການກະໃສ່ filter dropdown (ຕາມສາຂາທີ່ເລືອກ)
  loadShiftFilterOptions();
});
</script>
