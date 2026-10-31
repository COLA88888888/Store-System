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
  if (l.indexOf('vi') !== -1) return 'vi';
  return 'lo';
}

function getTimeWords() {
  var lang = getCurrentShiftLang();
  var dict = {
    lo: { hour: '<?php echo tlj('ໂມງ'); ?>', minute: '<?php echo tlj('ນາທີ'); ?>' },
    th: { hour: 'โมง', minute: 'นาที' },
    en: { hour: 'hr', minute: 'min' },
    zh: { hour: '点', minute: '分' },
    vi: { hour: 'giờ', minute: 'phút' }
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
function shiftCloserName(name) {
  if (!name) return '-';
  var trimmed = String(name).trim();
  var autoPatterns = [
    'ລະບົບ (ປິດອໍໂຕ້)', 'ລະບົບ (ປິດອັດຕະໂນມັດ)', 'ລະບົບ',
    'ระบบ (ปิดอัตโนมัติ)', 'ระบบ (ปิดออโต้)', 'ระบบ',
    'System (auto-closed)', 'System (auto closed)', 'System (Auto-closed)', 'System',
    'Hệ thống (tự đóng)', 'Hệ thống',
    '系统（自动交班）', '系统(自动交班)', '系统（自动关闭）', '系统(自动关闭)', '系统'
  ];
  if (autoPatterns.indexOf(trimmed) !== -1 || trimmed.toLowerCase().indexOf('auto-close') !== -1 || trimmed.indexOf('ປິດອໍໂຕ້') !== -1 || trimmed.indexOf('ปิดอัตโนมัติ') !== -1 || trimmed.indexOf('tự đóng') !== -1 || trimmed.indexOf('自动交班') !== -1) {
    var lang = getCurrentShiftLang();
    var dict = {
      lo: 'ລະບົບ (ປິດອໍໂຕ້)',
      th: 'ระบบ (ปิดอัตโนมัติ)',
      en: 'System (auto-closed)',
      zh: '系统（自动交班）',
      vi: 'Hệ thống (tự đóng)'
    };
    return dict[lang] || dict['lo'];
  }
  return name;
}

function formatShiftName(name) {
  var n = String(name || '').trim();
  if (!n) return '';
  var lang = getCurrentShiftLang();
  var shiftGroups = [
    { keys: ['ກະເຊົ້າ', 'กะเช้า', 'morning shift', 'morning', 'ca sáng', '早班'], trans: { lo: 'ກະເຊົ້າ', th: 'กะเช้า', en: 'Morning Shift', zh: '早班', vi: 'Ca sáng' } },
    { keys: ['ກະບ່າຍ', 'กะบ่าย', 'afternoon shift', 'afternoon', 'ca chiều', '下午班'], trans: { lo: 'ກະບ່າຍ', th: 'กะบ่าย', en: 'Afternoon Shift', zh: '下午班', vi: 'Ca chiều' } },
    { keys: ['ກະແລງ', 'กะเย็น', 'evening shift', 'evening', 'ca tối', '晚班'], trans: { lo: 'ກະແລງ', th: 'กะเย็น', en: 'Evening Shift', zh: '晚班', vi: 'Ca tối' } },
    { keys: ['ກະກາງຄືນ', 'ກະດຶກ', 'กะดึก', 'night shift', 'night', 'ca đêm', '夜班'], trans: { lo: 'ກະດຶກ', th: 'กะดึก', en: 'Night Shift', zh: '夜班', vi: 'Ca đêm' } }
  ];
  var nLower = n.toLowerCase();
  for (var i = 0; i < shiftGroups.length; i++) {
    var g = shiftGroups[i];
    for (var k = 0; k < g.keys.length; k++) {
      if (nLower === g.keys[k].toLowerCase()) {
        return g.trans[lang] || g.trans['lo'];
      }
    }
  }
  return n;
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
// ປຸ່ມກະເມື່ອກະເປີດຢູ່: "<ຊື່ກະ> · ກຳລັງໃຊ້ງານ" (ກົດ = ປິດກະ). ໃຊ້ທັງຕອນໂຫຼດໜ້າ ແລະ ທັນທີຫຼັງເປີດກະສຳເລັດ
// (ບໍ່ຕ້ອງລໍຖາມສະຖານະຈາກເຊີເວີອີກຮອບ)
function posPaintShiftActive(session) {
  $('#posShiftEarlyLockModal').modal('hide');
  $('#posShiftOpenModal').modal('hide');
  $('#posShiftStatusText').html('<strong>' + $('<div>').text(session.shift_name || '').html() + '</strong> · <?php echo tlj('ກຳລັງໃຊ້ງານ'); ?>');
  $('#posShiftStatusMobileText').text('<?php echo tlj('ກຳລັງໃຊ້ງານ'); ?>');
  $('#btnPosShiftAction').removeClass('is-closed d-none').addClass('is-open').toggleClass('d-none', !window.IS_CASHIER)
    .attr('title', '<?php echo tlj('ກະ:'); ?> ' + (session.shift_name || '') + ' | <?php echo tlj('ເປີດໂດຍ:'); ?> ' + (session.opened_by_name || '') +
          (session.staff_names ? ' | <?php echo tlj('ພະນັກງານ:'); ?> ' + session.staff_names : '') + ' — <?php echo tlj('ກົດເພື່ອປິດກະ'); ?>');
}

// ກະຕ່າ (ລາຍການຂາຍ) ເປັນຂອງກະ: ຈື່ id ກະໄວ້ກັບກະຕ່າໃນ browser.
//  - ກະເດີມຍັງເປີດຢູ່ (logout ກ່ອນໂມງປິດ ແລ້ວລັອກອິນຄືນ) → ກະຕ່າຢູ່ຄືເກົ່າ
//  - ກະປິດແລ້ວ (ສົ່ງລາຍງານແລ້ວ) ຫຼື ເປັນກະໃໝ່ → ລ້າງກະຕ່າ ເລີ່ມໃໝ່
function posSyncCartWithShift(sessionId) {
  if (!window.IS_CASHIER) return;
  var KEY = 'StoreMini.cart_shift_session';
  var prev = localStorage.getItem(KEY);
  var cur = sessionId ? String(sessionId) : '';
  if (prev === cur) return;
  if (prev !== null || !cur) {
    localStorage.removeItem('StoreMini.bill_opened');
    localStorage.removeItem('StoreMini.active_bills');
    if (typeof initActiveBills === 'function') initActiveBills();
  }
  if (cur) localStorage.setItem(KEY, cur); else localStorage.removeItem(KEY);
}

function checkShiftStatusOnPosLoad() {
  $.get(window.SHIFTS_API, { action: 'get_current_status' }, function (res) {
    if (!res || !res.success) return;
    window.currentPosShift = res;
    window.STORE_HAS_SHIFTS = !!res.has_shifts;

    var btnText = $('#posShiftStatusText');
    var btnAction = $('#btnPosShiftAction');

    if (res.has_active_shift && res.session) {
      // ມີກະທີ່ກຳລັງເປີດຢູ່ → ປຸ່ມ "ກຳລັງໃຊ້ງານ" (ກົດ = ປິດກະ; ສະເພາະພະນັກງານຂາຍ — ຜູ້ບໍລິຫານ/ຜູ້ຈັດການບໍ່ມີປຸ່ມກະ)
      posSyncCartWithShift(res.session.id);
      posPaintShiftActive(res.session);
      // ມາຈາກແຈ້ງເຕືອນ "ກະລຸນາປິດກະກ່ອນອອກຈາກລະບົບ" (?close_shift=1) → ເປີດຟອມປິດກະໃຫ້ເລີຍ (ເທື່ອດຽວ)
      if (/[?&]close_shift=1\b/.test(window.location.search) && !window.__closeShiftAutoOpened && typeof openPosCloseShiftModal === 'function') {
        window.__closeShiftAutoOpened = true;
        openPosCloseShiftModal();
      }
    } else if (res.has_shifts) {
      posSyncCartWithShift(null);   // ກະປິດແລ້ວ → ກະຕ່າຂອງກະນັ້ນບໍ່ໃຊ້ຕໍ່
    }
    if (res.has_active_shift && res.session) return;
    if (!res.has_shifts) {
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
          var nextInfo = res.next_shift ? `<?php echo tlj('ກະ'); ?> "${res.next_shift.shift_name}" <?php echo tlj('ຈະເລີ່ມເວລາ'); ?> <strong>${formatFriendlyTime(res.next_shift.start_time)}</strong>` : '<?php echo tlj('ບໍ່ມີກະທີ່ເປີດໃຊ້ງານ'); ?>';
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
        if (cartCount > 0) lines.push('<?php echo tlj('ສິນຄ້າໃນກະຕ່າ'); ?> <strong>' + cartCount + '</strong> <?php echo tlj('ລາຍການ'); ?>');
        if (heldCount > 0) lines.push('<?php echo tlj('ບິນທີ່ພັກໄວ້'); ?> <strong>' + heldCount + '</strong> <?php echo tlj('ບິນ'); ?>');
        Swal.fire({
          icon: 'warning',
          title: '<?php echo tlj('ຍັງມີລາຍການຄ້າງ'); ?> — <?php echo tlj('ປິດກະບໍ່ໄດ້'); ?>',
          html: lines.join('<br>') + '<div class="mt-2"><?php echo tlj('ກະລຸນາຊຳລະໃຫ້ໝົດກ່ອນ ຈຶ່ງປິດກະໄດ້'); ?></div>',
          confirmButtonText: '<?php echo tlj('ຕົກລົງ'); ?>',
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
    title: '<?php echo tlj('ຍັງບໍ່ທັນຮອດໂມງປິດກະ'); ?>',
    html: message ? $('<div>').text(message).html()
                  : '<?php echo tlj('ກະ'); ?> "<strong>' + $('<div>').text(shiftName || '').html() + '</strong>" <?php echo tlj('ປິດໄດ້ເວລາ'); ?> <strong>' + formatFriendlyTime(endClock) + '</strong>',
    confirmButtonText: '<?php echo tlj('ຕົກລົງ'); ?>',
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
  select.append('<option value="">-- <?php echo tlj('ເລືອກກະເຮັດວຽກ'); ?> --</option>');

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
    textEl.html(`<strong><?php echo tlj('ຍັງບໍ່ຮອດໂມງເປີດກະ!'); ?></strong> <?php echo tlj('ກະ'); ?> "${shiftName}" <?php echo tlj('ເປີດເວລາ'); ?> <strong>${formatFriendlyTime(startTime)}</strong>`);
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
    Swal.fire({ icon: 'warning', title: '<?php echo tlj('ກະລຸນາເລືອກກະເຮັດວຽກ'); ?>' });
    return;
  }
  if ($.trim(initialCash) === '') {
    Swal.fire({ icon: 'warning', title: '<?php echo tlj('ກະລຸນາປ້ອນເງິນສົດໃນລິ້ນຊັກ'); ?>', text: '<?php echo tlj('ຖ້າບໍ່ມີເງິນທອນ ໃຫ້ປ້ອນ'); ?> 0', confirmButtonText: '<?php echo tlj('ຕົກລົງ'); ?>', confirmButtonColor: '#244886' })
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
      // ປຸ່ມຂຶ້ນ "ກຳລັງໃຊ້ງານ" ທັນທີ + ກະຕ່າເປັນຂອງກະໃໝ່
      posSyncCartWithShift(res.session_id);
      posPaintShiftActive({ shift_name: res.shift_name, opened_by_name: window.CURRENT_USER_NAME || '' });
      $('#posShiftOpenModal').modal('hide');
      Swal.fire({
        icon: 'success',
        title: '<?php echo tlj('ເປີດກະສຳເລັດ!'); ?>',
        text: `<?php echo tlj('ເລີ່ມຕົ້ນກະ'); ?> "${res.shift_name}" <?php echo tlj('ເງິນລິ້ນຊັກ:'); ?> ${formatPosNumber(res.initial_cash)} <?php echo kipSymbol(); ?>`,
        timer: 1800,
        showConfirmButton: false
      });
      checkShiftStatusOnPosLoad();
    } else {
      Swal.fire({
        icon: res && res.too_early ? 'warning' : 'error',
        title: res && res.too_early ? '<?php echo tlj('ຍັງບໍ່ຮອດໂມງເປີດກະ'); ?>' : '<?php echo tlj('ຜິດພາດ'); ?>',
        text: res ? res.message : '<?php echo tlj('ເກີດຂໍ້ຜິດພາດ'); ?>'
      });
    }
  }, 'json').fail(function () {
    btn.prop('disabled', false);
    Swal.fire({ icon: 'error', title: '<?php echo tlj('ຜິດພາດ'); ?>', text: '<?php echo tlj('ບໍ່ສາມາດເຊື່ອມຕໍ່ລະບົບໄດ້'); ?>' });
  });
}

// ============================================================================
// 2. ປິດກະເຮັດວຽກຈາກ POS
// ============================================================================
function openPosCloseShiftModal() {
  $.get(window.SHIFTS_API, { action: 'get_shift_summary' }, function (res) {
    if (!res || !res.success || !res.session) {
      Swal.fire({ icon: 'error', title: '<?php echo tlj('ຜິດພາດ'); ?>', text: res ? res.message : '<?php echo tlj('ບໍ່ພົບກະທີ່ເປີດຢູ່'); ?>' });
      return;
    }

    var s = res.session;
    window.activePosClosingShift = s;

    $('#posCloseSessionId').val(s.id);
    $('#posCloseShiftNameDisplay').text(s.shift_name);
    $('#posCloseShiftOpenTimeDisplay').text(s.open_time);
    $('#posCloseShiftOpenedByDisplay').text(s.opened_by_name);
    $('#posCloseInitialCashDisplay').text(formatPosNumber(s.initial_cash) + ' <?php echo kipSymbol(); ?>');
    $('#posCloseSysCashDisplay').text(formatPosNumber(s.system_sales_cash) + ' <?php echo kipSymbol(); ?>');
    $('#posCloseSysTransferDisplay').text(formatPosNumber(s.system_sales_transfer) + ' <?php echo kipSymbol(); ?>');
    $('#posCloseSysTotalDisplay').text(formatPosNumber(s.system_total_sales) + ' <?php echo kipSymbol(); ?>');
    $('#posCloseTotalBillsDisplay').text(formatPosNumber(s.system_total_bills));

    // ນັບເງິນແບບບໍ່ຮູ້ຍອດ (ທຸກຕຳແໜ່ງ): ຟອມສະແດງແຕ່ເງິນລິ້ນຊັກຕອນເປີດ; ບັອກເງິນນັບໄດ້ວ່າງ ຕ້ອງປ້ອນເອງ
    // → ກົດປິດກະແລ້ວເບິ່ງລາຍງານ (ສ່ວນຕ່າງ ສະເພາະຜູ້ຈັດການ/ຜູ້ບໍລິຫານ ເຫັນໃນລາຍງານ)
    $('#posCloseCountedCashInput').val('');
    $('#posCloseShiftNotes').val('');
    $('#posCloseSubmitLabel').text('<?php echo tlj('ປິດກະ & ເບິ່ງລາຍງານ'); ?>');
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
    display.html('<i class="fas fa-check-circle mr-1"></i> <?php echo tlj('ດຸ່ນດ່ຽງພໍດີ'); ?> (0 <?php echo kipSymbol(); ?>)').css('color', '#15803d');
  } else if (diff > 0) {
    alertBox.addClass('alert-info');
    display.html('<i class="fas fa-arrow-up mr-1"></i> <?php echo tlj('ເງິນເກີນ:'); ?> +' + formatPosNumber(diff) + ' <?php echo kipSymbol(); ?>').css('color', '#0284c7');
  } else {
    alertBox.addClass('alert-danger');
    display.html('<i class="fas fa-arrow-down mr-1"></i> <?php echo tlj('ເງິນຂາດ:'); ?> ' + formatPosNumber(diff) + ' <?php echo kipSymbol(); ?>').css('color', '#dc2626');
  }
}

function submitPosCloseShift(e, forceEarly) {
  if (e) e.preventDefault();
  var sessionId = $('#posCloseSessionId').val();
  var countedCash = $('#posCloseCountedCashInput').val();
  var notes = $('#posCloseShiftNotes').val();
  if ($.trim(countedCash) === '') {
    Swal.fire({ icon: 'warning', title: '<?php echo tlj('ກະລຸນາປ້ອນເງິນສົດທີ່ນັບໄດ້ຕົວຈິງ'); ?>', text: '<?php echo tlj('ນັບເງິນສົດທີ່ໄດ້ຈາກການຂາຍ (ບໍ່ລວມເງິນລິ້ນຊັກຕອນເປີດ)'); ?> — <?php echo tlj('ບໍ່ມີໃຫ້ປ້ອນ'); ?> 0', confirmButtonText: '<?php echo tlj('ຕົກລົງ'); ?>', confirmButtonColor: '#244886' })
      .then(function () { $('#posCloseCountedCashInput').trigger('focus'); });
    return;
  }
  if (forceEarly) { posPostCloseShift(sessionId, countedCash, notes, true); return; }

  Swal.fire({
    title: '<?php echo tlj('ຢືນຢັນການປິດກະ?'); ?>',
    text: '<?php echo tlj('ທ່ານຕ້ອງການປິດກະເຮັດວຽກນີ້ ແລະ ສະຫຼຸບຍອດຂາຍແທ້ບໍ່?'); ?>',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#64748b',
    confirmButtonText: '<?php echo tlj('ຢືນຢັນປິດກະ'); ?>',
    cancelButtonText: '<?php echo tlj('ຍົກເລີກ'); ?>'
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
        title: '<?php echo tlj('ຍັງບໍ່ທັນຮອດໂມງປິດກະ'); ?>',
        text: res.message + ' — <?php echo tlj('ຕ້ອງການປິດກ່ອນເວລາບໍ່?'); ?>',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<?php echo tlj('ປິດກ່ອນເວລາ'); ?>',
        cancelButtonText: '<?php echo tlj('ຍົກເລີກ'); ?>'
      }).then(function (r) { if (r.isConfirmed) posPostCloseShift(sessionId, countedCash, notes, true); });
    } else if (res && res.too_early_close) {
      posWarnTooEarlyClose('', res.scheduled_end, res.message);
    } else if (res && res.pending_held) {
      Swal.fire({ icon: 'warning', title: '<?php echo tlj('ຍັງມີບິນພັກໄວ້'); ?> — <?php echo tlj('ປິດກະບໍ່ໄດ້'); ?>', text: res.message, confirmButtonText: '<?php echo tlj('ຕົກລົງ'); ?>', confirmButtonColor: '#244886' });
    } else {
      Swal.fire({ icon: 'error', title: '<?php echo tlj('ຜິດພາດ'); ?>', text: res ? res.message : '<?php echo tlj('ເກີດຂໍ້ຜິດພາດ'); ?>' });
    }
  }, 'json').fail(function () {
    btn.prop('disabled', false);
    Swal.fire({ icon: 'error', title: '<?php echo tlj('ຜິດພາດ'); ?>', text: '<?php echo tlj('ບໍ່ສາມາດເຊື່ອມຕໍ່ລະບົບໄດ້'); ?>' });
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
      <p class="text-muted"><?php echo tlj('ກຳລັງໂຫຼດລາຍງານສະຫຼຸບ...'); ?></p>
    </div>
  `);

  $.get(window.SHIFTS_API, { action: 'get_session_details', session_id: sessionId }, function (res) {
    if (!res || !res.success || !res.session) {
      $('#posShiftReportModalContent').html('<div class="alert alert-danger"><?php echo tlj('ບໍ່ພົບຂໍ້ມູນກະ'); ?></div>');
      return;
    }

    window.posReportSession = res.session;
    window.posReportBills = res.bills || [];
    window.posReportItems = res.items || [];

    var s = res.session;
    var diff = s.difference_cash !== null ? parseFloat(s.difference_cash) : 0;
    var diffText = diff === 0 ? '0 <?php echo kipSymbol(); ?> <?php echo tlj('(ດຸ່ນດ່ຽງ)'); ?>' : (diff > 0 ? '+' + formatPosNumber(diff) + ' <?php echo kipSymbol(); ?> <?php echo tlj('(ເກີນ)'); ?>' : formatPosNumber(diff) + ' <?php echo kipSymbol(); ?> <?php echo tlj('(ຂາດ)'); ?>');
    var diffColor = diff === 0 ? '#15803d' : (diff > 0 ? '#0284c7' : '#dc2626');
    // ພະນັກງານ: ເຊີເວີບໍ່ສົ່ງສ່ວນຕ່າງ (diff_hidden) → ບໍ່ສະແດງແຖວສ່ວນຕ່າງ (ສະເພາະຜູ້ຈັດການ/ຜູ້ບໍລິຫານ)
    var diffRow = s.diff_hidden ? '' : `
            <tr style="background: #e2e8f0; font-weight: bold;">
              <td><?php echo tlj('ສ່ວນຕ່າງເງິນສົດ (ຕົວຈິງ - ລະບົບ)'); ?></td>
              <td class="text-right" style="color: ${diffColor}; font-size: 1.15rem;">${diffText}</td>
            </tr>`;
    // ລາຍການຂາຍຂອງກະນີ້ (ກະຂອງຕົນເອງ)
    var esc = function (v) { return $('<div>').text(v == null ? '' : String(v)).html(); };
    var billRows = (res.bills || []).map(function (b, i) {
      return `<tr style="white-space: nowrap;"><td class="text-center" style="white-space: nowrap;">${i + 1}</td><td style="white-space: nowrap;">${esc(b.sale_save_bill)}</td><td class="text-center" style="white-space: nowrap;">${esc((b.sale_time || '').substr(0, 5))}</td>
              <td style="white-space: nowrap;">${esc(b.type_pay)}</td><td class="text-right font-weight-bold" style="white-space: nowrap;">${formatPosNumber(b.sale_barlance)} <?php echo kipSymbol(); ?></td></tr>`;
    }).join('');
    var billsHtml = `
        <div class="font-weight-bold text-dark mb-2"><i class="fas fa-receipt text-primary mr-1"></i> <?php echo tlj('ລາຍການຂາຍໃນກະນີ້'); ?> (${(res.bills || []).length} <?php echo tlj('ບິນ)'); ?></div>
        <div class="table-responsive mb-3" style="max-height: 240px; overflow-x: auto; overflow-y: auto; -webkit-overflow-scrolling: touch;">
          <table class="table table-sm table-bordered mb-0 text-nowrap" style="font-size: 0.86rem; white-space: nowrap;">
            <thead class="bg-light"><tr style="white-space: nowrap;"><th class="text-center" style="white-space: nowrap;">#</th><th style="white-space: nowrap;"><?php echo tlj('ເລກບິນ'); ?></th><th class="text-center" style="white-space: nowrap;"><?php echo tlj('ເວລາ'); ?></th><th style="white-space: nowrap;"><?php echo tlj('ຊ່ອງທາງ'); ?></th><th class="text-right" style="white-space: nowrap;"><?php echo tlj('ມູນຄ່າ'); ?></th></tr></thead>
            <tbody>${billRows || '<tr><td colspan="5" class="text-center text-muted py-3"><?php echo tlj('ບໍ່ມີລາຍການຂາຍໃນກະນີ້'); ?></td></tr>'}</tbody>
          </table>
        </div>`;

    // ສິນຄ້າທີ່ຂາຍໃນກະ (ລວມຕາມສິນຄ້າ — get_session_details.items)
    var items = res.items || [];
    var itTot = { qty: 0, net: 0 };
    var itemRows = items.map(function (it, i) {
      itTot.qty += it.qty; itTot.net += it.net;
      return `<tr style="white-space: nowrap;"><td class="text-center" style="white-space: nowrap;">${i + 1}</td><td style="white-space: nowrap;">${esc(it.product_name)}</td><td class="text-center font-weight-bold" style="white-space: nowrap;">${formatPosNumber(it.qty)}</td>
              <td class="text-right" style="white-space: nowrap;">${formatPosNumber(it.price)} <?php echo kipSymbol(); ?></td><td class="text-right font-weight-bold" style="white-space: nowrap;">${formatPosNumber(it.net)} <?php echo kipSymbol(); ?></td></tr>`;
    }).join('');
    var itemsHtml = `
        <div class="font-weight-bold text-dark mb-2"><i class="fas fa-box-open text-primary mr-1"></i> <?php echo tlj('ສິນຄ້າທີ່ຂາຍໃນກະ'); ?> (${items.length} <?php echo tlj('ລາຍການ)'); ?></div>
        <div class="table-responsive mb-3" style="max-height: 260px; overflow-x: auto; overflow-y: auto; -webkit-overflow-scrolling: touch;">
          <table class="table table-sm table-bordered mb-0 text-nowrap" style="font-size: 0.86rem; white-space: nowrap;">
            <thead class="bg-light"><tr style="white-space: nowrap;"><th class="text-center" style="white-space: nowrap;">#</th><th style="white-space: nowrap;"><?php echo tlj('ສິນຄ້າ'); ?></th><th class="text-center" style="white-space: nowrap;"><?php echo tlj('ຈຳນວນ'); ?></th><th class="text-right" style="white-space: nowrap;"><?php echo tlj('ລາຄາ'); ?></th><th class="text-right" style="white-space: nowrap;"><?php echo tlj('ລວມ'); ?></th></tr></thead>
            <tbody>${itemRows || '<tr><td colspan="5" class="text-center text-muted py-3"><?php echo tlj('ບໍ່ມີສິນຄ້າທີ່ຂາຍໃນກະນີ້'); ?></td></tr>'}</tbody>
            ${items.length ? `<tfoot><tr class="font-weight-bold" style="background: #f1f5f9; white-space: nowrap;"><td colspan="2" class="text-right" style="white-space: nowrap;"><?php echo tlj('ລວມທັງໝົດ'); ?></td>
              <td class="text-center" style="white-space: nowrap;">${formatPosNumber(itTot.qty)}</td><td style="white-space: nowrap;"></td><td class="text-right text-primary" style="white-space: nowrap;">${formatPosNumber(itTot.net)} <?php echo kipSymbol(); ?></td></tr></tfoot>` : ''}
          </table>
        </div>`;

    var html = `
      <div id="posExportContentArea" style="font-family: 'Nuanta', 'Noto Sans Lao', sans-serif;">
        <div class="text-center border-bottom pb-3 mb-3">
          <h4 class="font-weight-bold mb-1" style="color: #1e293b;"><?php echo tlj('ໃບສະຫຼຸບປິດກະເຮັດວຽກ'); ?></h4>
          <div class="text-muted" style="font-size: 0.95rem;">
            ${s.store_name ? `<strong><?php echo tlj('ສາຂາ:'); ?></strong> ${esc(s.store_name)} | ` : ''}<strong><?php echo tlj('ກະ:'); ?></strong> ${formatShiftName(s.shift_name)} | <strong><?php echo tlj('ເວລາ:'); ?></strong> ${s.open_time} - ${s.close_time}
          </div>
          <div class="text-muted" style="font-size: 0.88rem;">
            <strong><?php echo tlj('ຜູ້ເປີດ:'); ?></strong> ${s.opened_by_name} | <strong><?php echo tlj('ຜູ້ປິດ:'); ?></strong> ${shiftCloserName(s.closed_by_name)}
          </div>
          ${s.staff_names ? `<div class="text-muted" style="font-size: 0.88rem;"><strong><?php echo tlj('ພະນັກງານໃນກະ:'); ?></strong> ${$('<div>').text(s.staff_names).html()}</div>` : ''}
        </div>

        <table class="table table-bordered mb-3" style="font-size: 0.95rem;">
          <tbody>
            <tr>
              <td class="bg-light font-weight-bold" style="width: 55%;">1. <?php echo tlj('ເງິນສົດໃນລິ້ນຊັກ (ຕອນເປີດກະ)'); ?></td>
              <td class="text-right font-weight-bold text-secondary">${formatPosNumber(s.initial_cash)} <?php echo kipSymbol(); ?></td>
            </tr>
            <tr>
              <td>2. <?php echo tlj('ຍອດຂາຍເງິນສົດໃນລະບົບ'); ?></td>
              <td class="text-right font-weight-bold text-success">${formatPosNumber(s.system_sales_cash)} <?php echo kipSymbol(); ?></td>
            </tr>
            <tr>
              <td>3. <?php echo tlj('ຍອດຂາຍໂອນ / QR ໃນລະບົບ'); ?></td>
              <td class="text-right font-weight-bold text-info">${formatPosNumber(s.system_sales_transfer)} <?php echo kipSymbol(); ?></td>
            </tr>
            <tr>
              <td>4. <?php echo tlj('ຍອດຂາຍຕິດໜີ້'); ?></td>
              <td class="text-right font-weight-bold text-warning">${formatPosNumber(s.system_sales_debt)} <?php echo kipSymbol(); ?></td>
            </tr>
            <tr class="bg-light font-weight-bold">
              <td><?php echo tlj('ຍອດຂາຍລວມທັງໝົດ'); ?> (${formatPosNumber(s.system_total_bills)} <?php echo tlj('ບິນ)'); ?></td>
              <td class="text-right font-weight-bold text-primary" style="font-size: 1.1rem;">${formatPosNumber(s.system_total_sales)} <?php echo kipSymbol(); ?></td>
            </tr>
            <tr style="background: #f8fafc;">
              <td class="font-weight-bold text-dark">5. <?php echo tlj('ເງິນສົດນັບໄດ້ຕົວຈິງ (ສະເພາະຂາຍ ບໍ່ລວມເງິນລິ້ນຊັກ)'); ?></td>
              <td class="text-right font-weight-bold text-dark" style="font-size: 1.1rem;">${formatPosNumber(s.counted_sales_cash)} <?php echo kipSymbol(); ?></td>
            </tr>${diffRow}
          </tbody>
        </table>
${itemsHtml}
${billsHtml}

        ${s.notes ? `<div class="alert alert-secondary py-2 px-3 mb-2 small"><strong><?php echo tlj('ໝາຍເຫດ:'); ?></strong> ${s.notes}</div>` : ''}
        <div class="alert alert-warning py-2 px-3 small font-weight-bold text-center">
          <i class="fas fa-info-circle mr-1"></i> <?php echo tlj('ເມື່ອກົດ'); ?> "<?php echo tlj('ອອກຈາກລະບົບ'); ?>" <?php echo tlj('ທ່ານຈະອອກຈາກລະບົບທັນທີ ແລະ ບໍ່ສາມາດເຂົ້າຂາຍໄດ້ຈົນກວ່າຈະຮອດໂມງເປີດກະຖັດໄປ'); ?>
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
  var diffText = diff === 0 ? '0 <?php echo kipSymbol(); ?> <?php echo tlj('(ດຸ່ນດ່ຽງ)'); ?>' : (diff > 0 ? '+' + formatPosNumber(diff) + ' <?php echo kipSymbol(); ?> <?php echo tlj('(ເກີນ)'); ?>' : formatPosNumber(diff) + ' <?php echo kipSymbol(); ?> <?php echo tlj('(ຂາດ)'); ?>');

  var slipHtml = `<!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Shift Report - ${s.shift_name}</title>
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
      <div class="text-center font-bold" style="font-size: 15px;"><?php echo tlj('ໃບສະຫຼຸບປິດກະເຮັດວຽກ'); ?></div>
      ${s.store_name ? `<div class="text-center" style="font-size: 12px;"><?php echo tlj('ສາຂາ:'); ?> ${$('<div>').text(s.store_name).html()}</div>` : ''}
      <div class="text-center font-bold" style="font-size: 13px;">${formatShiftName(s.shift_name)}</div>
      <div class="text-center border-bottom" style="font-size: 12px;">${s.open_time} - ${s.close_time}</div>

      <div class="row"><span><?php echo tlj('ຜູ້ເປີດ:'); ?></span><span>${s.opened_by_name}</span></div>
      <div class="row"><span><?php echo tlj('ຜູ້ປິດ:'); ?></span><span>${shiftCloserName(s.closed_by_name)}</span></div>
      ${s.staff_names ? `<div class="row"><span><?php echo tlj('ພະນັກງານ:'); ?></span><span>${$('<div>').text(s.staff_names).html()}</span></div>` : ''}

      <div class="border-top"></div>
      <div class="row"><span><?php echo tlj('ເງິນລິ້ນຊັກຕອນເປີດ:'); ?></span><span class="font-bold">${formatPosNumber(s.initial_cash)} <?php echo kipSymbol(); ?></span></div>
      <div class="row"><span><?php echo tlj('ຂາຍເງິນສົດ (ລະບົບ):'); ?></span><span class="font-bold">${formatPosNumber(s.system_sales_cash)} <?php echo kipSymbol(); ?></span></div>
      <div class="row"><span><?php echo tlj('ຂາຍໂອນ / QR:'); ?></span><span class="font-bold">${formatPosNumber(s.system_sales_transfer)} <?php echo kipSymbol(); ?></span></div>
      <div class="row"><span><?php echo tlj('ຂາຍຕິດໜີ້:'); ?></span><span class="font-bold">${formatPosNumber(s.system_sales_debt)} <?php echo kipSymbol(); ?></span></div>
      <div class="row font-bold" style="font-size: 13.5px;"><span><?php echo tlj('ຍອດຂາຍລວມ'); ?> (${s.system_total_bills} <?php echo tlj('ບິນ):'); ?></span><span>${formatPosNumber(s.system_total_sales)} <?php echo kipSymbol(); ?></span></div>

      ${(window.posReportItems || []).length ? `<div class="border-top"></div>
      <div class="font-bold" style="margin-bottom: 3px;"><?php echo tlj('ສິນຄ້າທີ່ຂາຍ'); ?> (${window.posReportItems.length} <?php echo tlj('ລາຍການ):'); ?></div>
      ${window.posReportItems.map(function (it) {
        return `<div class="row"><span>${$('<div>').text(it.product_name || '-').html()} x${formatPosNumber(it.qty)}</span><span>${formatPosNumber(it.net)} <?php echo kipSymbol(); ?></span></div>`;
      }).join('')}` : ''}

      <div class="border-top"></div>
      <div class="row font-bold"><span><?php echo tlj('ເງິນສົດນັບໄດ້ (ຂາຍ):'); ?></span><span>${formatPosNumber(s.counted_sales_cash)} <?php echo kipSymbol(); ?></span></div>
      ${s.diff_hidden ? '' : `<div class="row font-bold"><span><?php echo tlj('ສ່ວນຕ່າງເງິນສົດ:'); ?></span><span>${diffText}</span></div>`}

      <div class="border-top text-center" style="margin-top: 15px; font-size: 11px;">
        <?php echo tlj('ພິມເມື່ອ:'); ?> ${new Date().toLocaleString()}
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
  var K = function (n) { return formatPosNumber(n) + ' <?php echo kipSymbol(); ?>'; };
  var diff = s.difference_cash !== null && s.difference_cash !== undefined ? parseFloat(s.difference_cash) : null;
  var diffText = diff === null ? '-' : (diff === 0 ? '0 <?php echo kipSymbol(); ?> <?php echo tlj('(ດຸ່ນດ່ຽງ)'); ?>' : (diff > 0 ? '+' + K(diff) + ' <?php echo tlj('(ເກີນ)'); ?>' : K(diff) + ' <?php echo tlj('(ຂາດ)'); ?>'));
  var meta = ['<?php echo tlj('ເວລາ:'); ?> ' + (s.open_time || '') + ' - ' + (s.close_time || ''),
              '<?php echo tlj('ຜູ້ເປີດ:'); ?> ' + (s.opened_by_name || '-') + '  <?php echo tlj('ຜູ້ປິດ:'); ?> ' + (shiftCloserName(s.closed_by_name) || '-')];
  if (s.staff_names) meta.push('<?php echo tlj('ພະນັກງານໃນກະ:'); ?> ' + s.staff_names);
  var summary = [
    ['1. <?php echo tlj('ເງິນສົດໃນລິ້ນຊັກ (ຕອນເປີດກະ)'); ?>', K(s.initial_cash)],
    ['2. <?php echo tlj('ຍອດຂາຍເງິນສົດໃນລະບົບ'); ?>', K(s.system_sales_cash)],
    ['3. <?php echo tlj('ຍອດຂາຍໂອນ'); ?> / QR', K(s.system_sales_transfer)],
    ['4. <?php echo tlj('ຍອດຂາຍຕິດໜີ້'); ?>', K(s.system_sales_debt)],
    ['<?php echo tlj('ຍອດຂາຍລວມທັງໝົດ'); ?> (' + formatPosNumber(s.system_total_bills) + ' <?php echo tlj('ບິນ)'); ?>', K(s.system_total_sales), true],
    ['5. <?php echo tlj('ເງິນສົດນັບໄດ້ຕົວຈິງ (ສະເພາະຂາຍ ບໍ່ລວມເງິນລິ້ນຊັກ)'); ?>', K(s.counted_sales_cash)]
  ];
  if (!s.diff_hidden) summary.push(['<?php echo tlj('ສ່ວນຕ່າງເງິນສົດ (ຕົວຈິງ - ລະບົບ)'); ?>', diffText, true]);   // ພະນັກງານບໍ່ເຫັນ
  if (s.notes) summary.push(['<?php echo tlj('ໝາຍເຫດ'); ?>', s.notes]);
  // ສິນຄ້າທີ່ຂາຍໃນກະ — RptExport ມີຕາຕະລາງຫຼັກດຽວ (ບິນ) ຈຶ່ງໃສ່ໃນຕາຕະລາງສະຫຼຸບ: "ຊື່ x ຈຳນວນ" | ລວມ
  var items = window.posReportItems || [];
  if (items.length) {
    summary.push(['<?php echo tlj('ສິນຄ້າທີ່ຂາຍໃນກະ'); ?> (' + items.length + ' <?php echo tlj('ລາຍການ)'); ?>', '', true]);
    items.forEach(function (it) { summary.push([(it.product_name || '-') + '  x' + formatPosNumber(it.qty), K(it.net)]); });
  }
  var total = 0;
  var rows = bills.map(function (b, i) {
    total += parseFloat(b.sale_barlance) || 0;
    return [i + 1, b.sale_save_bill, (b.sale_date || '') + ' ' + (b.sale_time || ''), b.user_receive || '', b.type_pay || '', K(b.sale_barlance)];
  });
  return {
    title: '<?php echo tlj('ລາຍງານສະຫຼຸບປິດກະ:'); ?> ' + (s.shift_name || ''),
    fileBase: 'shift_report_' + String(s.shift_name || 'shift').replace(/[\\\/:*?"<>|\s]+/g, '_'),
    store: s.store_name ? '<?php echo tlj('ສາຂາ:'); ?> ' + s.store_name : '',
    meta: meta,
    summary: summary,
    headers: [{t: '#', a: 'c'}, {t: '<?php echo tlj('ເລກບິນ'); ?>'}, {t: '<?php echo tlj('ວັນທີ-ເວລາ'); ?>', a: 'c'}, {t: '<?php echo tlj('ພະນັກງານຂາຍ'); ?>'}, {t: '<?php echo tlj('ຊ່ອງທາງ'); ?>', a: 'c'}, {t: '<?php echo tlj('ມູນຄ່າ'); ?>', a: 'r'}],
    rows: rows,
    footer: rows.length ? ['', '<?php echo tlj('ລວມ'); ?> ' + rows.length + ' <?php echo tlj('ບິນ'); ?>', '', '', '', K(total)] : null
  };
}
function posShiftExportReady() {
  if (!window.posReportSession) return false;
  if (!window.RptExport) { Swal.fire({ icon: 'error', title: '<?php echo tlj('ໂຫຼດ assets/js/report-export.js ບໍ່ໄດ້'); ?>' }); return false; }
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
