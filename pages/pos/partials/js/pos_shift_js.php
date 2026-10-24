<?php /* ລາຍງານປິດກະ Excel / PDF: ເຄື່ອງມືລາຍງານກາງ (ໂຫຼດ html2pdf ເອງເມື່ອກົດ PDF) */ ?>
<script src="<?php echo $base_path; ?>assets/js/report-export.js?v=<?php echo @filemtime(dirname(__DIR__, 4) . '/assets/js/report-export.js'); ?>"></script>
<script>
window.SHIFTS_API = '../../api/shifts_backend.php';
window.currentPosShift = null;

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
window.posReportSession = null;
window.posReportBills = [];

function formatPosNumber(num) {
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
// 1. ກວດສອບສະຖານະກະຕອນໂຫຼດໜ້າ POS
// ============================================================================
function checkShiftStatusOnPosLoad() {
  $.get(window.SHIFTS_API, { action: 'get_current_status' }, function (res) {
    if (!res || !res.success) return;
    window.currentPosShift = res;
    window.STORE_HAS_SHIFTS = !!res.has_shifts;

    var btnText = $('#posShiftStatusText');
    var btnAction = $('#btnPosShiftAction');

    if (res.has_active_shift && res.session) {
      // ມີກະທີ່ກຳລັງເປີດຢູ່ → ປຸ່ມປິດກະ (ສະເພາະພະນັກງານຂາຍ; ຜູ້ບໍລິຫານ/ຜູ້ຈັດການບໍ່ມີປຸ່ມກະ)
      $('#posShiftEarlyLockModal').modal('hide');
      $('#posShiftOpenModal').modal('hide');

      btnText.html('<strong>' + $('<div>').text(res.session.shift_name).html() + '</strong> (ປິດກະ)');
      $('#posShiftStatusMobileText').text('ປິດກະ');
      btnAction.removeClass('is-closed').addClass('is-open').toggleClass('d-none', !window.IS_CASHIER)
               .attr('title', 'ກະ: ' + res.session.shift_name + ' | ເປີດໂດຍ: ' + res.session.opened_by_name +
                     (res.session.staff_names ? ' | ພະນັກງານ: ' + res.session.staff_names : '') + ' — ກົດເພື່ອປິດກະ');
    } else if (!res.has_shifts) {
      // ສາຂານີ້ບໍ່ໄດ້ຕັ້ງກະໄວ້ (ຕັ້ງຄ່າ > ກະເຮັດວຽກ) → ເຊື່ອງປຸ່ມ ແລະ ບໍ່ບັງຄັບເປີດກະ
      btnAction.addClass('d-none');
      $('#posShiftEarlyLockModal').modal('hide');
      $('#posShiftOpenModal').modal('hide');
    } else {
      // ມີກະຕັ້ງໄວ້ ແຕ່ຍັງບໍ່ເປີດ: ບໍ່ມີປຸ່ມ "ເປີດກະ" ສຳລັບໃຜເລີຍ — ພະນັກງານຂາຍໄດ້ຟອມປ້ອນເງິນລິ້ນຊັກຂຶ້ນເອງ;
      // ຜູ້ບໍລິຫານ/ຜູ້ຈັດການຂາຍບໍ່ໄດ້ເມື່ອມີກະ (checkShiftSalePermission) ຈຶ່ງບໍ່ຕ້ອງເປີດກະ
      btnAction.removeClass('is-open').addClass('is-closed').addClass('d-none');

      // ຖ້າຫາກເປັນພະນັກງານຂາຍ: ບັງຄັບເປີດກະ ຫຼື ບລັອກຫາກຍັງບໍ່ຮອດໂມງ
      if (window.IS_CASHIER) {
        if (!res.can_open_any) {
          // ຍັງບໍ່ຮອດໂມງເປີດກະ
          var nextInfo = res.next_shift ? `ກະ "${res.next_shift.shift_name}" ຈະເລີ່ມເວລາ <strong>${formatFriendlyTime(res.next_shift.start_time)}</strong>` : 'ບໍ່ມີກະທີ່ເປີດໃຊ້ງານ';
          $('#posEarlyShiftMessage').html(nextInfo);
          $('#posEarlyCurrentClock').text(res.current_time_clock || '--:--');
          $('#posShiftEarlyLockModal').modal('show');
        } else {
          // ຮອດໂມງແລ້ວ: ໃຫ້ເລືອກກະ ແລະ ປ້ອນເງິນລິ້ນຊັກ
          $('#posShiftEarlyLockModal').modal('hide');
          populatePosOpenShiftModal(res);
          $('#posShiftOpenModal').modal('show');
        }
      }
    }
  }, 'json');
}

// ຈັດການປຸ່ມກົດ Shift ໃນ Cart Bar — ດຶງສະຖານະໃໝ່ທຸກເທື່ອ (ກະອາດຖືກເປີດ/ປິດຈາກເຄື່ອງອື່ນ ຫຼື ປິດອໍໂຕ້ແລ້ວ)
function handlePosShiftAction() {
  $.get(window.SHIFTS_API, { action: 'get_current_status' }, function (res) {
    if (!res || !res.success) return;
    if (res.has_active_shift && res.session) {
      // ຍັງມີລາຍການຄ້າງ: ສິນຄ້າໃນກະຕ່າ ຫຼື ບິນທີ່ພັກໄວ້ → ຕ້ອງຊຳລະໃຫ້ໝົດກ່ອນຈຶ່ງປິດກະໄດ້
      var cartCount = (typeof cart !== 'undefined' && cart && cart.length) ? cart.length : 0;
      var heldCount = parseInt(res.session.held_count, 10) || 0;
      if (cartCount > 0 || heldCount > 0) {
        var lines = [];
        if (cartCount > 0) lines.push('ສິນຄ້າໃນກະຕ່າ <strong>' + cartCount + '</strong> ລາຍການ');
        if (heldCount > 0) lines.push('ບິນທີ່ພັກໄວ້ <strong>' + heldCount + '</strong> ບິນ');
        Swal.fire({
          icon: 'warning',
          title: 'ຍັງມີລາຍການຄ້າງ — ປິດກະບໍ່ໄດ້',
          html: lines.join('<br>') + '<div class="mt-2">ກະລຸນາຊຳລະໃຫ້ໝົດກ່ອນ ຈຶ່ງປິດກະໄດ້</div>',
          confirmButtonText: 'ຕົກລົງ',
          confirmButtonColor: '#244886'
        });
        return;
      }
      // ຍັງບໍ່ຮອດໂມງປິດກະ: ພະນັກງານປິດບໍ່ໄດ້ (ຜູ້ບໍລິຫານ/ຜູ້ຈັດການ ປິດກ່ອນເວລາໄດ້ ເມື່ອຢືນຢັນຕອນບັນທຶກ)
      if (!res.session.can_close_now && window.IS_CASHIER) {
        posWarnTooEarlyClose(res.session.shift_name, res.session.scheduled_end);
        return;
      }
      openPosCloseShiftModal();
    } else {
      populatePosOpenShiftModal(res);
      $('#posShiftOpenModal').modal('show');
    }
  }, 'json');
}

// ເຕືອນ "ຍັງບໍ່ທັນຮອດໂມງປິດກະ" ພ້ອມໂມງປິດ
function posWarnTooEarlyClose(shiftName, scheduledEnd, message) {
  var endClock = scheduledEnd ? String(scheduledEnd).substr(11, 5) : '';
  Swal.fire({
    icon: 'warning',
    title: 'ຍັງບໍ່ທັນຮອດໂມງປິດກະ',
    html: message ? $('<div>').text(message).html()
                  : 'ກະ "<strong>' + $('<div>').text(shiftName || '').html() + '</strong>" ປິດໄດ້ເວລາ <strong>' + formatFriendlyTime(endClock) + '</strong>',
    confirmButtonText: 'ຕົກລົງ',
    confirmButtonColor: '#244886'
  });
}

// ============================================================================
// Realtime: heartbeat ທຸກ 30 ວິນາທີ
//  - ບອກເຊີເວີວ່າພະນັກງານກຳລັງໃຊ້ງານ (active=1 ຖ້າມີການແຕະ/ພິມ/ຄລິກ ພາຍໃນ 2 ນາທີ) → ຮອດໂມງແລ້ວກໍບໍ່ປິດອໍໂຕ້
//    ເປີດໜ້າປະໄວ້ ບໍ່ມີໃຜແຕະ → active=0 → ຮອດໂມງປິດ + ບໍ່ໃຊ້ງານ 15 ນາທີ ລະບົບປິດກະໃຫ້ (config/helpers/shift_auto.php)
//  - ກະຖືກເປີດ/ປິດ (ເຄື່ອງອື່ນ ຫຼື ປິດອໍໂຕ້) → ອັບເດດປຸ່ມກະທັນທີ ບໍ່ຕ້ອງຣີເຟຣດ
// ============================================================================
window.posLastInteraction = Date.now();
['mousedown', 'keydown', 'touchstart', 'wheel'].forEach(function (type) {
  document.addEventListener(type, function () { window.posLastInteraction = Date.now(); }, { passive: true, capture: true });
});
function posShiftHeartbeat() {
  if (window.STORE_HAS_SHIFTS === false) return;   // ສາຂາບໍ່ມີກະ
  var active = (Date.now() - window.posLastInteraction) < 2 * 60 * 1000 && !document.hidden;
  $.post(window.SHIFTS_API, { action: 'heartbeat', active: active ? 1 : 0 }, function (res) {
    if (!res || !res.success) return;
    var cur = window.currentPosShift || {};
    var curId = cur.has_active_shift && cur.session ? parseInt(cur.session.id, 10) : 0;
    if ((res.session_id || 0) !== curId) checkShiftStatusOnPosLoad();
  }, 'json');
}

// ຕື່ມຂໍ້ມູນໃສ່ Modal ເປີດກະ
function populatePosOpenShiftModal(res) {
  var select = $('#posSelectShiftId');
  select.empty();
  select.append('<option value="">-- ເລືອກກະເຮັດວຽກ --</option>');

  $('#posCurrentClockDisplay').text(res.current_time_clock || '--:--');

  var shifts = res.shifts || [];
  var defaultId = null;

  shifts.forEach(function (s) {
    var opt = $('<option></option>');
    opt.val(s.id);
    opt.text(`${s.shift_name} (${formatFriendlyTime(s.start_time)} - ${formatFriendlyTime(s.end_time)})`);
    opt.data('can_open', s.can_open);
    opt.data('start_time', s.start_time);
    opt.data('shift_name', s.shift_name);

    if (s.can_open && !defaultId) {
      defaultId = s.id;
    }
    select.append(opt);
  });

  if (defaultId) {
    select.val(defaultId);
  }

  // ມີກະທີ່ເປີດໄດ້ກະດຽວ → ເລືອກໃຫ້ເອງ: ພະນັກງານປ້ອນແຕ່ເງິນລິ້ນຊັກ ແລ້ວກົດຕົກລົງ
  var openable = shifts.filter(function (s) { return s.can_open; });
  if (openable.length === 1) {
    var only = openable[0];
    $('#posShiftAutoName').text(only.shift_name + ' (' + formatFriendlyTime(only.start_time) + ' - ' + formatFriendlyTime(only.end_time) + ')');
    $('#posShiftAutoInfo').show();
    $('#posShiftSelectGroup').hide();
  } else {
    $('#posShiftAutoInfo').hide();
    $('#posShiftSelectGroup').show();
  }

  $('#posInitialCashInput').val('');   // ວ່າງໄວ້: ຕ້ອງປ້ອນເອງ (ບໍ່ມີເງິນໃຫ້ໃສ່ 0) — submitPosOpenShift
  onPosShiftSelectionChange();
  // ເປີດຟອມແລ້ວ ເຄີເຊີຢູ່ບັອກເງິນ (ເລືອກໄວ້ທັງໝົດ ພິມທັບໄດ້ເລີຍ)
  $('#posShiftOpenModal').one('shown.bs.modal', function () { $('#posInitialCashInput').trigger('focus').select(); });
}

function onPosShiftSelectionChange() {
  var sel = $('#posSelectShiftId option:selected');
  var canOpen = sel.data('can_open');
  var startTime = sel.data('start_time');
  var shiftName = sel.data('shift_name');
  var btn = $('#btnPosSubmitOpenShift');
  var alertBox = $('#posShiftTimeWarningAlert');
  var textEl = $('#posShiftTimeWarningText');

  if (sel.val() && canOpen === false) {
    textEl.html(`<strong>ຍັງບໍ່ຮອດໂມງເປີດກະ!</strong> ກະ "${shiftName}" ເປີດເວລາ <strong>${formatFriendlyTime(startTime)}</strong>`);
    alertBox.slideDown(150);
    btn.prop('disabled', true);
  } else {
    alertBox.slideUp(150);
    btn.prop('disabled', false);
  }
}

// ສົ່ງຟອມເປີດກະຈາກ POS
function submitPosOpenShift(e) {
  e.preventDefault();
  var shiftId = $('#posSelectShiftId').val();
  var initialCash = $('#posInitialCashInput').val();

  if (!shiftId) {
    Swal.fire({ icon: 'warning', title: 'ກະລຸນາເລືອກກະເຮັດວຽກ' });
    return;
  }
  if ($.trim(initialCash) === '') {
    Swal.fire({ icon: 'warning', title: 'ກະລຸນາປ້ອນເງິນສົດໃນລິ້ນຊັກ', text: 'ຖ້າບໍ່ມີເງິນທອນ ໃຫ້ປ້ອນ 0', confirmButtonText: 'ຕົກລົງ', confirmButtonColor: '#244886' })
      .then(function () { $('#posInitialCashInput').trigger('focus'); });
    return;
  }

  var btn = $('#btnPosSubmitOpenShift');
  btn.prop('disabled', true);

  $.post(window.SHIFTS_API, {
    action: 'open_shift',
    shift_id: shiftId,
    initial_cash: initialCash
  }, function (res) {
    btn.prop('disabled', false);
    if (res && res.success) {
      $('#posShiftOpenModal').modal('hide');
      Swal.fire({
        icon: 'success',
        title: 'ເປີດກະສຳເລັດ!',
        text: `ເລີ່ມຕົ້ນກະ "${res.shift_name}" ເງິນລິ້ນຊັກ: ${formatPosNumber(res.initial_cash)} ₭`,
        timer: 1800,
        showConfirmButton: false
      });
      checkShiftStatusOnPosLoad();
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
// 2. ປິດກະເຮັດວຽກຈາກ POS
// ============================================================================
function openPosCloseShiftModal() {
  $.get(window.SHIFTS_API, { action: 'get_shift_summary' }, function (res) {
    if (!res || !res.success || !res.session) {
      Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: res ? res.message : 'ບໍ່ພົບກະທີ່ເປີດຢູ່' });
      return;
    }

    var s = res.session;
    window.activePosClosingShift = s;

    $('#posCloseSessionId').val(s.id);
    $('#posCloseShiftNameDisplay').text(s.shift_name);
    $('#posCloseShiftOpenTimeDisplay').text(s.open_time);
    $('#posCloseShiftOpenedByDisplay').text(s.opened_by_name);
    $('#posCloseInitialCashDisplay').text(formatPosNumber(s.initial_cash) + ' ₭');
    $('#posCloseSysCashDisplay').text(formatPosNumber(s.system_sales_cash) + ' ₭');
    $('#posCloseSysTransferDisplay').text(formatPosNumber(s.system_sales_transfer) + ' ₭');
    $('#posCloseSysTotalDisplay').text(formatPosNumber(s.system_total_sales) + ' ₭');
    $('#posCloseTotalBillsDisplay').text(formatPosNumber(s.system_total_bills));

    // ນັບເງິນແບບບໍ່ຮູ້ຍອດ (ທຸກຕຳແໜ່ງ): ຟອມສະແດງແຕ່ເງິນລິ້ນຊັກຕອນເປີດ; ບັອກເງິນນັບໄດ້ວ່າງ ຕ້ອງປ້ອນເອງ
    // → ກົດປິດກະແລ້ວເບິ່ງລາຍງານ (ສ່ວນຕ່າງ ສະເພາະຜູ້ຈັດການ/ຜູ້ບໍລິຫານ ເຫັນໃນລາຍງານ)
    $('#posCloseCountedCashInput').val('');
    $('#posCloseShiftNotes').val('');
    $('#posCloseSubmitLabel').text('ປິດກະ & ເບິ່ງລາຍງານ');
    $('#posShiftCloseModal').one('shown.bs.modal', function () { $('#posCloseCountedCashInput').trigger('focus'); });

    $('#posShiftCloseModal').modal('show');
  }, 'json');
}

function calculatePosShiftDifference() {
  if (!window.activePosClosingShift) return;
  var sysCash = parseFloat(window.activePosClosingShift.system_sales_cash) || 0;
  var countedStr = $('#posCloseCountedCashInput').val().replace(/,/g, '');
  var countedCash = parseFloat(countedStr) || 0;
  var diff = countedCash - sysCash;

  var alertBox = $('#posShiftDiffAlert');
  var display = $('#posShiftDiffDisplay');

  alertBox.removeClass('alert-secondary alert-success alert-danger alert-info');

  if (diff === 0) {
    alertBox.addClass('alert-success');
    display.html('<i class="fas fa-check-circle mr-1"></i> ດຸ່ນດ່ຽງພໍດີ (0 ₭)').css('color', '#15803d');
  } else if (diff > 0) {
    alertBox.addClass('alert-info');
    display.html('<i class="fas fa-arrow-up mr-1"></i> ເງິນເກີນ: +' + formatPosNumber(diff) + ' ₭').css('color', '#0284c7');
  } else {
    alertBox.addClass('alert-danger');
    display.html('<i class="fas fa-arrow-down mr-1"></i> ເງິນຂາດ: ' + formatPosNumber(diff) + ' ₭').css('color', '#dc2626');
  }
}

function submitPosCloseShift(e, forceEarly) {
  if (e) e.preventDefault();
  var sessionId = $('#posCloseSessionId').val();
  var countedCash = $('#posCloseCountedCashInput').val();
  var notes = $('#posCloseShiftNotes').val();
  if ($.trim(countedCash) === '') {
    Swal.fire({ icon: 'warning', title: 'ກະລຸນາປ້ອນເງິນສົດທີ່ນັບໄດ້ຕົວຈິງ', text: 'ນັບເງິນສົດທີ່ໄດ້ຈາກການຂາຍ (ບໍ່ລວມເງິນລິ້ນຊັກຕອນເປີດ) — ບໍ່ມີໃຫ້ປ້ອນ 0', confirmButtonText: 'ຕົກລົງ', confirmButtonColor: '#244886' })
      .then(function () { $('#posCloseCountedCashInput').trigger('focus'); });
    return;
  }
  if (forceEarly) { posPostCloseShift(sessionId, countedCash, notes, true); return; }

  Swal.fire({
    title: 'ຢືນຢັນການປິດກະ?',
    text: 'ທ່ານຕ້ອງການປິດກະເຮັດວຽກນີ້ ແລະ ສະຫຼຸບຍອດຂາຍແທ້ບໍ່?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'ຢືນຢັນປິດກະ',
    cancelButtonText: 'ຍົກເລີກ'
  }).then(function (result) {
    if (result.isConfirmed) posPostCloseShift(sessionId, countedCash, notes, false);
  });
}

// ສົ່ງປິດກະ. ຍັງບໍ່ຮອດໂມງປິດ: ພະນັກງານ → ເຕືອນ; ຜູ້ບໍລິຫານ/ຜູ້ຈັດການ → ຖາມຢືນຢັນປິດກ່ອນເວລາ ແລ້ວສົ່ງ force_early=1
function posPostCloseShift(sessionId, countedCash, notes, forceEarly) {
  var btn = $('#btnPosSubmitCloseShift');
  btn.prop('disabled', true);
  $.post(window.SHIFTS_API, {
    action: 'close_shift',
    session_id: sessionId,
    counted_sales_cash: countedCash,
    notes: notes,
    force_early: forceEarly ? 1 : 0
  }, function (res) {
    btn.prop('disabled', false);
    if (res && res.success) {
      $('#posShiftCloseModal').modal('hide');
      // ເປີດ Modal ລາຍງານສະຫຼຸບປິດກະ
      showPosShiftSummaryModal(sessionId);
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
      }).then(function (r) { if (r.isConfirmed) posPostCloseShift(sessionId, countedCash, notes, true); });
    } else if (res && res.too_early_close) {
      posWarnTooEarlyClose('', res.scheduled_end, res.message);
    } else if (res && res.pending_held) {
      Swal.fire({ icon: 'warning', title: 'ຍັງມີບິນພັກໄວ້ — ປິດກະບໍ່ໄດ້', text: res.message, confirmButtonText: 'ຕົກລົງ', confirmButtonColor: '#244886' });
    } else {
      Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: res ? res.message : 'ເກີດຂໍ້ຜິດພາດ' });
    }
  }, 'json').fail(function () {
    btn.prop('disabled', false);
    Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: 'ບໍ່ສາມາດເຊື່ອມຕໍ່ລະບົບໄດ້' });
  });
}

// ============================================================================
// 3. ລາຍງານສະຫຼຸບ & ອອກຈາກລະບົບ
// ============================================================================
function showPosShiftSummaryModal(sessionId) {
  $('#posShiftReportModal').modal('show');
  $('#posShiftReportModalContent').html(`
    <div class="text-center py-5">
      <i class="fas fa-spinner fa-spin fa-2x text-primary mb-2"></i>
      <p class="text-muted">ກຳລັງໂຫຼດລາຍງານສະຫຼຸບ...</p>
    </div>
  `);

  $.get(window.SHIFTS_API, { action: 'get_session_details', session_id: sessionId }, function (res) {
    if (!res || !res.success || !res.session) {
      $('#posShiftReportModalContent').html('<div class="alert alert-danger">ບໍ່ພົບຂໍ້ມູນກະ</div>');
      return;
    }

    window.posReportSession = res.session;
    window.posReportBills = res.bills || [];

    var s = res.session;
    var diff = s.difference_cash !== null ? parseFloat(s.difference_cash) : 0;
    var diffText = diff === 0 ? '0 ₭ (ດຸ່ນດ່ຽງ)' : (diff > 0 ? '+' + formatPosNumber(diff) + ' ₭ (ເກີນ)' : formatPosNumber(diff) + ' ₭ (ຂາດ)');
    var diffColor = diff === 0 ? '#15803d' : (diff > 0 ? '#0284c7' : '#dc2626');
    // ພະນັກງານ: ເຊີເວີບໍ່ສົ່ງສ່ວນຕ່າງ (diff_hidden) → ບໍ່ສະແດງແຖວສ່ວນຕ່າງ (ສະເພາະຜູ້ຈັດການ/ຜູ້ບໍລິຫານ)
    var diffRow = s.diff_hidden ? '' : `
            <tr style="background: #e2e8f0; font-weight: bold;">
              <td>ສ່ວນຕ່າງເງິນສົດ (ຕົວຈິງ - ລະບົບ)</td>
              <td class="text-right" style="color: ${diffColor}; font-size: 1.15rem;">${diffText}</td>
            </tr>`;
    // ລາຍການຂາຍຂອງກະນີ້ (ກະຂອງຕົນເອງ)
    var esc = function (v) { return $('<div>').text(v == null ? '' : String(v)).html(); };
    var billRows = (res.bills || []).map(function (b, i) {
      return `<tr><td class="text-center">${i + 1}</td><td>${esc(b.sale_save_bill)}</td><td class="text-center">${esc((b.sale_time || '').substr(0, 5))}</td>
              <td>${esc(b.type_pay)}</td><td class="text-right font-weight-bold">${formatPosNumber(b.sale_barlance)} ₭</td></tr>`;
    }).join('');
    var billsHtml = `
        <div class="font-weight-bold text-dark mb-2"><i class="fas fa-receipt text-primary mr-1"></i> ລາຍການຂາຍໃນກະນີ້ (${(res.bills || []).length} ບິນ)</div>
        <div class="table-responsive mb-3" style="max-height: 240px; overflow-y: auto;">
          <table class="table table-sm table-bordered mb-0" style="font-size: 0.86rem;">
            <thead class="bg-light"><tr><th class="text-center">#</th><th>ເລກບິນ</th><th class="text-center">ເວລາ</th><th>ຊ່ອງທາງ</th><th class="text-right">ມູນຄ່າ</th></tr></thead>
            <tbody>${billRows || '<tr><td colspan="5" class="text-center text-muted py-3">ບໍ່ມີລາຍການຂາຍໃນກະນີ້</td></tr>'}</tbody>
          </table>
        </div>`;

    var html = `
      <div id="posExportContentArea" style="font-family: 'Noto Sans Lao', sans-serif;">
        <div class="text-center border-bottom pb-3 mb-3">
          <h4 class="font-weight-bold mb-1" style="color: #1e293b;">ໃບສະຫຼຸບປິດກະເຮັດວຽກ</h4>
          <div class="text-muted" style="font-size: 0.95rem;">
            <strong>ກະ:</strong> ${s.shift_name} | <strong>ເວລາ:</strong> ${s.open_time} - ${s.close_time}
          </div>
          <div class="text-muted" style="font-size: 0.88rem;">
            <strong>ຜູ້ເປີດ:</strong> ${s.opened_by_name} | <strong>ຜູ້ປິດ:</strong> ${s.closed_by_name}
          </div>
          ${s.staff_names ? `<div class="text-muted" style="font-size: 0.88rem;"><strong>ພະນັກງານໃນກະ:</strong> ${$('<div>').text(s.staff_names).html()}</div>` : ''}
        </div>

        <table class="table table-bordered mb-3" style="font-size: 0.95rem;">
          <tbody>
            <tr>
              <td class="bg-light font-weight-bold" style="width: 55%;">1. ເງິນສົດໃນລິ້ນຊັກ (ຕອນເປີດກະ)</td>
              <td class="text-right font-weight-bold text-secondary">${formatPosNumber(s.initial_cash)} ₭</td>
            </tr>
            <tr>
              <td>2. ຍອດຂາຍເງິນສົດໃນລະບົບ</td>
              <td class="text-right font-weight-bold text-success">${formatPosNumber(s.system_sales_cash)} ₭</td>
            </tr>
            <tr>
              <td>3. ຍອດຂາຍໂອນ / QR ໃນລະບົບ</td>
              <td class="text-right font-weight-bold text-info">${formatPosNumber(s.system_sales_transfer)} ₭</td>
            </tr>
            <tr>
              <td>4. ຍອດຂາຍຕິດໜີ້</td>
              <td class="text-right font-weight-bold text-warning">${formatPosNumber(s.system_sales_debt)} ₭</td>
            </tr>
            <tr class="bg-light font-weight-bold">
              <td>ຍອດຂາຍລວມທັງໝົດ (${formatPosNumber(s.system_total_bills)} ບິນ)</td>
              <td class="text-right font-weight-bold text-primary" style="font-size: 1.1rem;">${formatPosNumber(s.system_total_sales)} ₭</td>
            </tr>
            <tr style="background: #f8fafc;">
              <td class="font-weight-bold text-dark">5. ເງິນສົດນັບໄດ້ຕົວຈິງ (ສະເພາະຂາຍ ບໍ່ລວມເງິນລິ້ນຊັກ)</td>
              <td class="text-right font-weight-bold text-dark" style="font-size: 1.1rem;">${formatPosNumber(s.counted_sales_cash)} ₭</td>
            </tr>${diffRow}
          </tbody>
        </table>
${billsHtml}

        ${s.notes ? `<div class="alert alert-secondary py-2 px-3 mb-2 small"><strong>ໝາຍເຫດ:</strong> ${s.notes}</div>` : ''}
        <div class="alert alert-warning py-2 px-3 small font-weight-bold text-center">
          <i class="fas fa-info-circle mr-1"></i> ເມື່ອກົດ "ອອກຈາກລະບົບ" ທ່ານຈະອອກຈາກລະບົບທັນທີ ແລະ ບໍ່ສາມາດເຂົ້າຂາຍໄດ້ຈົນກວ່າຈະຮອດໂມງເປີດກະຖັດໄປ
        </div>
      </div>
    `;

    $('#posShiftReportModalContent').html(html);
  }, 'json');
}

function logoutCashierAfterShift() {
  window.top.location.href = '../../auth/logout.php';
}

// ພິມ Slip Thermal ຈາກ POS - ຂຶ້ນພຣີວິວເຕັມ (Full Print Preview) ຜ່ານ Iframe
function printPosShiftThermalSlip() {
  if (!window.posReportSession) return;
  var s = window.posReportSession;
  var diff = s.difference_cash !== null ? parseFloat(s.difference_cash) : 0;
  var diffText = diff === 0 ? '0 ₭ (ດຸ່ນດ່ຽງ)' : (diff > 0 ? '+' + formatPosNumber(diff) + ' ₭ (ເກີນ)' : formatPosNumber(diff) + ' ₭ (ຂາດ)');

  var slipHtml = `<!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Shift Report - ${s.shift_name}</title>
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
      <div class="text-center font-bold" style="font-size: 15px;">ໃບສະຫຼຸບປິດກະເຮັດວຽກ</div>
      <div class="text-center font-bold" style="font-size: 13px;">${s.shift_name}</div>
      <div class="text-center border-bottom" style="font-size: 12px;">${s.open_time} - ${s.close_time}</div>

      <div class="row"><span>ຜູ້ເປີດ:</span><span>${s.opened_by_name}</span></div>
      <div class="row"><span>ຜູ້ປິດ:</span><span>${s.closed_by_name}</span></div>
      ${s.staff_names ? `<div class="row"><span>ພະນັກງານ:</span><span>${$('<div>').text(s.staff_names).html()}</span></div>` : ''}

      <div class="border-top"></div>
      <div class="row"><span>ເງິນລິ້ນຊັກຕອນເປີດ:</span><span class="font-bold">${formatPosNumber(s.initial_cash)} ₭</span></div>
      <div class="row"><span>ຂາຍເງິນສົດ (ລະບົບ):</span><span class="font-bold">${formatPosNumber(s.system_sales_cash)} ₭</span></div>
      <div class="row"><span>ຂາຍໂອນ / QR:</span><span class="font-bold">${formatPosNumber(s.system_sales_transfer)} ₭</span></div>
      <div class="row"><span>ຂາຍຕິດໜີ້:</span><span class="font-bold">${formatPosNumber(s.system_sales_debt)} ₭</span></div>
      <div class="row font-bold" style="font-size: 13.5px;"><span>ຍອດຂາຍລວມ (${s.system_total_bills} ບິນ):</span><span>${formatPosNumber(s.system_total_sales)} ₭</span></div>

      <div class="border-top"></div>
      <div class="row font-bold"><span>ເງິນສົດນັບໄດ້ (ຂາຍ):</span><span>${formatPosNumber(s.counted_sales_cash)} ₭</span></div>
      ${s.diff_hidden ? '' : `<div class="row font-bold"><span>ສ່ວນຕ່າງເງິນສົດ:</span><span>${diffText}</span></div>`}

      <div class="border-top text-center" style="margin-top: 15px; font-size: 11px;">
        ພິມເມື່ອ: ${new Date().toLocaleString()}
      </div>
    </body>
    </html>
  `;

  var iframe = document.getElementById('posShiftSlipPrintIframe');
  if (!iframe) {
    iframe = document.createElement('iframe');
    iframe.id = 'posShiftSlipPrintIframe';
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

// ລາຍງານປິດກະສຳລັບ Excel / PDF (assets/js/report-export.js — Phetsarath OT ແບບດຽວກັບລາຍງານອື່ນ):
// ຕາຕະລາງສະຫຼຸບ (ເງິນລິ້ນຊັກແຍກຕ່າງຫາກ ບໍ່ລວມກັບຍອດຂາຍ) + ລາຍການບິນໃນກະ
function posShiftReportData() {
  var s = window.posReportSession || {};
  var bills = window.posReportBills || [];
  var K = function (n) { return formatPosNumber(n) + ' ₭'; };
  var diff = s.difference_cash !== null && s.difference_cash !== undefined ? parseFloat(s.difference_cash) : null;
  var diffText = diff === null ? '-' : (diff === 0 ? '0 ₭ (ດຸ່ນດ່ຽງ)' : (diff > 0 ? '+' + K(diff) + ' (ເກີນ)' : K(diff) + ' (ຂາດ)'));
  var meta = ['ເວລາ: ' + (s.open_time || '') + ' - ' + (s.close_time || ''),
              'ຜູ້ເປີດ: ' + (s.opened_by_name || '-') + '  ຜູ້ປິດ: ' + (s.closed_by_name || '-')];
  if (s.staff_names) meta.push('ພະນັກງານໃນກະ: ' + s.staff_names);
  var summary = [
    ['1. ເງິນສົດໃນລິ້ນຊັກ (ຕອນເປີດກະ)', K(s.initial_cash)],
    ['2. ຍອດຂາຍເງິນສົດໃນລະບົບ', K(s.system_sales_cash)],
    ['3. ຍອດຂາຍໂອນ / QR', K(s.system_sales_transfer)],
    ['4. ຍອດຂາຍຕິດໜີ້', K(s.system_sales_debt)],
    ['ຍອດຂາຍລວມທັງໝົດ (' + formatPosNumber(s.system_total_bills) + ' ບິນ)', K(s.system_total_sales), true],
    ['5. ເງິນສົດນັບໄດ້ຕົວຈິງ (ສະເພາະຂາຍ ບໍ່ລວມເງິນລິ້ນຊັກ)', K(s.counted_sales_cash)]
  ];
  if (!s.diff_hidden) summary.push(['ສ່ວນຕ່າງເງິນສົດ (ຕົວຈິງ - ລະບົບ)', diffText, true]);   // ພະນັກງານບໍ່ເຫັນ
  if (s.notes) summary.push(['ໝາຍເຫດ', s.notes]);
  var total = 0;
  var rows = bills.map(function (b, i) {
    total += parseFloat(b.sale_barlance) || 0;
    return [i + 1, b.sale_save_bill, (b.sale_date || '') + ' ' + (b.sale_time || ''), b.user_receive || '', b.type_pay || '', K(b.sale_barlance)];
  });
  return {
    title: 'ລາຍງານສະຫຼຸບປິດກະ: ' + (s.shift_name || ''),
    fileBase: 'shift_report_' + String(s.shift_name || 'shift').replace(/[\\\/:*?"<>|\s]+/g, '_'),
    store: '',
    meta: meta,
    summary: summary,
    headers: [{t: '#', a: 'c'}, {t: 'ເລກບິນ'}, {t: 'ວັນທີ-ເວລາ', a: 'c'}, {t: 'ພະນັກງານຂາຍ'}, {t: 'ຊ່ອງທາງ', a: 'c'}, {t: 'ມູນຄ່າ', a: 'r'}],
    rows: rows,
    footer: rows.length ? ['', 'ລວມ ' + rows.length + ' ບິນ', '', '', '', K(total)] : null
  };
}
function posShiftExportReady() {
  if (!window.posReportSession) return false;
  if (!window.RptExport) { Swal.fire({ icon: 'error', title: 'ໂຫຼດ assets/js/report-export.js ບໍ່ໄດ້' }); return false; }
  RptExport.config({ printedBy: window.CURRENT_USER_NAME || '' });
  return true;
}

// Export PDF ຈາກ POS (ເມື່ອກ່ອນໃຊ້ html2pdf ທີ່ໜ້າຂາຍບໍ່ໄດ້ໂຫຼດ — ປຸ່ມບໍ່ເຮັດວຽກ)
function exportPosShiftPDF() {
  if (posShiftExportReady()) RptExport.pdf(posShiftReportData());
}

// Export Excel ຈາກ POS
function exportPosShiftExcel() {
  if (posShiftExportReady()) RptExport.excel(posShiftReportData());
}

// ເລີ່ມຕົ້ນກວດສອບສະຖານະກະ
$(document).ready(function () {
  checkShiftStatusOnPosLoad();
  setInterval(posShiftHeartbeat, 30000);   // realtime: ສະຖານະກະ + ການໃຊ້ງານ
});
</script>
