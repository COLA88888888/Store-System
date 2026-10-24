<?php
/*
 * ສ່ວນ: ບັດ "ເຄື່ອງພິມ Bluetooth" ໃນໜ້າ ຕັ້ງຄ່າ > ເຄື່ອງພິມ — ບ່ອນດຽວທີ່ເຊື່ອມຕໍ່ເຄື່ອງພິມ Bluetooth.
 * ສະຖານະ ເຊື່ອມຕໍ່ຢູ່ / ບໍ່ໄດ້ເຊື່ອມຕໍ່, ລາຍຊື່ເຄື່ອງພິມ (ໃຊ້ເຄື່ອງນີ້ / ລຶບ), ເພີ່ມເຄື່ອງ (Bluetooth / COM port),
 * ຂະໜາດເຈ້ຍ, ເປີດ/ປິດ ການພິມໃບບິນຜ່ານ Bluetooth ແລະ ທົດສອບພິມ.
 * ຕົວເຊື່ອມຕໍ່ = assets/js/bluetooth-printer.js ຂອງໜ້າຫຼັກ (home/dashboard.php) — ເຊື່ອມຢູ່ນີ້ ແລ້ວໄປໜ້າຂາຍ
 * ການເຊື່ອມຕໍ່ຍັງຢູ່ ກົດພິມອອກເຄື່ອງນັ້ນທັນທີ (pages/pos/partials/js/checkout/receipt_bluetooth.php).
 * ຕັ້ງຄ່າຈື່ໄວ້ໃນ browser ເຄື່ອງນີ້ (localStorage) — ແຕ່ລະເຄື່ອງຂາຍມີເຄື່ອງພິມຂອງຕົນ.
 * ຂໍ້ຄວາມທັງໝົດມາຈາກ lang/<ພາສາ>/printers.php (printers.bt_*): ຂໍ້ຄວາມຄົງທີ່ຜ່ານ t(), ຂໍ້ຄວາມທີ່ JS ສ້າງ
 * ຜ່ານ I18N_BT_CARD — ກົດທຸງປ່ຽນພາສາ ໜ້າຫຼັກປ່ຽນທັງສອງແບບ ແລ້ວບັດນີ້ແຕ້ມສະຖານະ/ລາຍຊື່ໃໝ່ (pos:langchange).
 * ຖືກ require ຈາກ pages/settings/printers/index.php.
 */
?>
<script src="<?php echo $base_path; ?>assets/js/bluetooth-printer-escpos.js?v=<?php echo @filemtime(__DIR__ . '/../../../../assets/js/bluetooth-printer-escpos.js'); ?>"></script>
<script src="<?php echo $base_path; ?>assets/js/bluetooth-printer.js?v=<?php echo @filemtime(__DIR__ . '/../../../../assets/js/bluetooth-printer.js'); ?>"></script>

<div class="card border-0 shadow-sm mb-4" id="btCard" style="border-radius: 12px; border: 1.5px solid #e2e8f0; background: #ffffff;">
  <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center flex-wrap" style="gap: 8px;">
    <h6 class="m-0 font-weight-bold text-dark"><i class="fab fa-bluetooth-b mr-2 text-primary"></i> <?php echo htmlspecialchars(t('printers.bt_title', 'ເຄື່ອງພິມ Bluetooth (ໃບບິນ — ບໍ່ໃຊ້ສາຍ)')); ?></h6>
    <span id="btStatusBadge" class="badge px-3 py-2" style="font-size: 0.85rem;"></span>
  </div>
  <div class="card-body px-4 py-3">
    <div id="btUnsupported" class="alert alert-warning py-2 small" style="display: none;">
      <i class="fas fa-exclamation-triangle mr-1"></i>
      <?php echo htmlspecialchars(t('printers.bt_unsupported', 'browser ນີ້ບໍ່ຮອງຮັບ Bluetooth — ກະລຸນາໃຊ້ Google Chrome ຫຼື Microsoft Edge ແລະ ເປີດລະບົບຜ່ານ localhost ຫຼື https.')); ?>
    </div>

    <div class="row">
      <div class="col-lg-7 mb-3">
        <div class="font-weight-bold small text-dark mb-1"><?php echo htmlspecialchars(t('printers.bt_list_label', 'ເຄື່ອງພິມທີ່ຕັ້ງໄວ້')); ?> <span class="font-weight-normal text-muted"><?php echo htmlspecialchars(t('printers.bt_list_hint', '— ເຄື່ອງໃດເປີດຢູ່ ໃບບິນພິມອອກເຄື່ອງນັ້ນ')); ?></span></div>
        <div id="btList" class="border rounded px-3" style="background: #f8fafc; min-height: 52px;"></div>
        <div id="btMsg" class="small mt-2"></div>
        <div class="d-flex flex-wrap mt-2" style="gap: 8px;">
          <button type="button" class="btn btn-primary btn-sm font-weight-bold" id="btAddBle"><i class="fas fa-plus mr-1"></i> <?php echo htmlspecialchars(t('printers.bt_add_ble', 'ເພີ່ມເຄື່ອງພິມ Bluetooth')); ?></button>
          <button type="button" class="btn btn-outline-primary btn-sm font-weight-bold" id="btAddSerial"><i class="fas fa-plug mr-1"></i> <?php echo htmlspecialchars(t('printers.bt_add_serial', 'ເພີ່ມ COM port')); ?></button>
          <button type="button" class="btn btn-outline-secondary btn-sm font-weight-bold" id="btScan"><i class="fas fa-sync-alt mr-1"></i> <?php echo htmlspecialchars(t('printers.bt_scan', 'ເຊື່ອມເຄື່ອງທີ່ເປີດຢູ່')); ?></button>
          <button type="button" class="btn btn-outline-danger btn-sm font-weight-bold" id="btDisconnect"><i class="fas fa-unlink mr-1"></i> <?php echo htmlspecialchars(t('printers.bt_disconnect', 'ຕັດການເຊື່ອມຕໍ່')); ?></button>
        </div>
        <small class="text-muted d-block mt-2"><?php echo htmlspecialchars(t('printers.bt_help', 'ເຄື່ອງພິມ Bluetooth ນ້ອຍສ່ວນຫຼາຍ → ເພີ່ມເຄື່ອງພິມ Bluetooth. ຖ້າບໍ່ພົບໃນລາຍການ ແລະ Windows ຈັບຄູ່ເຄື່ອງພິມເປັນ COM port ແລ້ວ → ເພີ່ມ COM port.')); ?></small>
      </div>
      <div class="col-lg-5 mb-3">
        <div class="form-group mb-2">
          <label class="font-weight-bold small text-dark mb-1"><?php echo htmlspecialchars(t('printers.bt_paper', 'ຂະໜາດເຈ້ຍ')); ?></label>
          <select id="btPaper" class="form-control" style="height: 42px;">
            <option value="80"><?php echo htmlspecialchars(t('printers.bt_paper_80', '80 mm (ມ້ວນກວ້າງ ~8 cm)')); ?></option>
            <option value="58"><?php echo htmlspecialchars(t('printers.bt_paper_58', '58 mm / 57x40 mm (ເຄື່ອງພິມໃນຕົວ / ມ້ວນ ~5.7 cm)')); ?></option>
            <?php /* A5/A4: ເຄື່ອງນີ້ພິມບິນເປັນໜ້າເຕັມຜ່ານໜ້າຕ່າງພິມ (ເຄື່ອງພິມ Wi-Fi/ເອກະສານ) ແທນ Bluetooth —
                     assets/js/lan-receipt.js receiptPaper() */ ?>
            <option value="148"><?php echo htmlspecialchars(t('printers.paper_a5', 'A5 (ເຈ້ຍແຜ່ນ 148×210 mm)')); ?></option>
            <option value="210"><?php echo htmlspecialchars(t('printers.paper_a4', 'A4 (ເຈ້ຍແຜ່ນ 210×297 mm)')); ?></option>
          </select>
          <small class="text-muted d-block mt-1"><?php echo htmlspecialchars(t('printers.bt_paper_page_hint', 'A5/A4: ເຄື່ອງນີ້ພິມບິນເປັນໜ້າເຕັມຜ່ານໜ້າຕ່າງພິມ (ເຄື່ອງພິມ Wi-Fi / ເອກະສານ) ແທນເຄື່ອງພິມ Bluetooth')); ?></small>
        </div>
        <div class="custom-control custom-switch mb-3">
          <input type="checkbox" class="custom-control-input" id="btEnabled">
          <label class="custom-control-label font-weight-bold small" for="btEnabled"><?php echo htmlspecialchars(t('printers.bt_enabled', 'ພິມໃບບິນຂາຍຜ່ານ Bluetooth (ພິມອັດຕະໂນມັດຫຼັງຂາຍສຳເລັດ)')); ?></label>
        </div>
        <button type="button" class="btn btn-success btn-block font-weight-bold" id="btTest"><i class="fas fa-print mr-1"></i> <?php echo htmlspecialchars(t('printers.bt_test', 'ທົດສອບພິມ')); ?></button>
      </div>
    </div>
  </div>
</div>

<script>
var I18N_BT_CARD = <?php echo tjson([
    'printers.bt_connected_to' => 'ເຊື່ອມຕໍ່ຢູ່: %s',
    'printers.bt_not_connected' => 'ບໍ່ໄດ້ເຊື່ອມຕໍ່',
    'printers.bt_connected' => 'ເຊື່ອມຕໍ່ຢູ່',
    'printers.bt_use' => 'ເຊື່ອມເຄື່ອງນີ້',
    'printers.bt_remove_title' => 'ລຶບເຄື່ອງນີ້',
    'printers.bt_empty' => 'ຍັງບໍ່ໄດ້ຕັ້ງເຄື່ອງພິມ — ເປີດເຄື່ອງພິມ ແລ້ວກົດ "ເພີ່ມເຄື່ອງພິມ Bluetooth"',
    'printers.bt_load_fail' => 'ໂຫຼດ assets/js/bluetooth-printer.js ບໍ່ໄດ້',
    'printers.bt_paper_saved' => 'ບັນທຶກຂະໜາດເຈ້ຍແລ້ວ',
    'printers.bt_enabled_on' => 'ໃບບິນຂາຍຈະພິມອອກເຄື່ອງພິມ Bluetooth ທີ່ເຊື່ອມຕໍ່ຢູ່',
    'printers.bt_enabled_off' => 'ປິດແລ້ວ — ໃບບິນພິມແບບປົກກະຕິ',
    'printers.bt_choose' => 'ເລືອກເຄື່ອງພິມໃນລາຍການທີ່ຂຶ້ນມາ...',
    'printers.bt_added' => 'ເຊື່ອມຕໍ່ %s ແລ້ວ — ກົດ "ທົດສອບພິມ" ເພື່ອກວດ',
    'printers.bt_not_chosen' => 'ບໍ່ໄດ້ເລືອກເຄື່ອງພິມ',
    'printers.bt_connect_fail' => 'ເຊື່ອມຕໍ່ບໍ່ສຳເລັດ',
    'printers.bt_need_add' => 'ຍັງບໍ່ໄດ້ຕັ້ງເຄື່ອງພິມ — ກົດ "ເພີ່ມເຄື່ອງພິມ Bluetooth" ກ່ອນ',
    'printers.bt_scanning' => 'ກຳລັງຊອກເຄື່ອງພິມທີ່ເປີດຢູ່...',
    'printers.bt_connected_msg' => 'ເຊື່ອມຕໍ່ %s ແລ້ວ',
    'printers.bt_none_on' => 'ບໍ່ພົບເຄື່ອງພິມທີ່ເປີດຢູ່ — ເປີດເຄື່ອງພິມ ແລ້ວລອງໃໝ່',
    'printers.bt_disconnected' => 'ຕັດການເຊື່ອມຕໍ່ແລ້ວ',
    'printers.bt_connecting' => 'ກຳລັງເຊື່ອມຕໍ່...',
    'printers.bt_remove_confirm' => 'ລຶບເຄື່ອງພິມນີ້?',
    'printers.bt_remove_btn' => 'ລຶບ',
    'printers.bt_cancel' => 'ຍົກເລີກ',
    'printers.bt_removed' => 'ລຶບເຄື່ອງພິມແລ້ວ',
    'printers.bt_test_need' => 'ກົດ "ເພີ່ມເຄື່ອງພິມ Bluetooth" ກ່ອນ',
    'printers.bt_testing' => 'ກຳລັງພິມທົດສອບ...',
    'printers.bt_test_sent' => 'ສົ່ງໃບທົດສອບໄປ %s ແລ້ວ',
    'printers.bt_print_fail' => 'ພິມບໍ່ສຳເລັດ',
    'printers.bt_page_mode' => 'ເລືອກ A5/A4 ແລ້ວ — ໃບບິນຂາຍຈະເປີດໜ້າຕ່າງພິມເປັນໜ້າເຕັມ ໃຫ້ເລືອກເຄື່ອງພິມ Wi-Fi / ເອກະສານ',
]); ?>;

(function () {
  var KEY = 'StoreMini.bt_printer_settings';
  var P = window.MiniposBtPrinter;
  var esc = function (t) { return $('<div>').text(t || '').html(); };
  // ຂໍ້ຄວາມຕາມພາສາປັດຈຸບັນ (%s = ຊື່ເຄື່ອງພິມ, escape ແລ້ວ)
  var T = function (k, name) {
    var s = esc(I18N_BT_CARD['printers.' + k] || k);
    return name === undefined ? s : s.replace('%s', esc(name));
  };
  if (!P) { $('#btCard .card-body').html('<div class="text-danger small">' + T('bt_load_fail') + '</div>'); return; }

  function settings() {
    var s = {};
    try { s = JSON.parse(localStorage.getItem(KEY) || '{}') || {}; } catch (e) { s = {}; }
    var isHandheld = /Android/i.test(navigator.userAgent || '');
    var defPaper = isHandheld ? 58 : 80;
    return {
      enabled: (s.enabled === undefined) ? (P.list().length > 0 || isHandheld) : !!s.enabled,
      paper: [58, 80, 148, 210].indexOf(parseInt(s.paper, 10)) !== -1 ? parseInt(s.paper, 10) : defPaper
    };
  }
  function save(patch) {
    var s = settings();
    Object.keys(patch).forEach(function (k) { s[k] = patch[k]; });
    try { localStorage.setItem(KEY, JSON.stringify(s)); } catch (e) {}
  }
  function msg(html, cls) { $('#btMsg').html(html ? '<span class="' + (cls || 'text-muted') + '">' + html + '</span>' : ''); }
  function busy(html) { msg('<i class="fas fa-spinner fa-spin mr-1"></i>' + html, 'text-primary'); }
  var OK = '<i class="fas fa-check-circle mr-1"></i>';

  // ສະຖານະ + ລາຍຊື່ເຄື່ອງພິມ
  function render() {
    var info = P.info(), items = P.list();
    $('#btStatusBadge')
      .toggleClass('badge-success', info.connected).toggleClass('badge-danger', !info.connected)
      .html(info.connected ? OK + ' ' + T('bt_connected_to', info.name)
                           : '<i class="fas fa-times-circle mr-1"></i> ' + T('bt_not_connected'));
    $('#btList').html(items.length ? items.map(function (p) {
      return '<div class="d-flex align-items-center justify-content-between py-2 border-bottom">' +
        '<span><i class="' + (p.mode === 'serial' ? 'fas fa-plug' : 'fab fa-bluetooth-b') + ' mr-2 ' + (p.connected ? 'text-success' : 'text-muted') + '"></i>' +
          '<b>' + esc(p.name) + '</b>' +
          (p.connected ? ' <span class="badge badge-success ml-1">' + T('bt_connected') + '</span>' : ' <span class="badge badge-light border ml-1">' + T('bt_not_connected') + '</span>') + '</span>' +
        '<span class="text-nowrap">' +
          (p.connected ? '' : '<button type="button" class="btn btn-outline-primary btn-sm py-0 mr-1 bt-use" data-key="' + esc(p.key) + '">' + T('bt_use') + '</button>') +
          '<button type="button" class="btn btn-link btn-sm text-danger p-0 bt-remove" data-key="' + esc(p.key) + '" title="' + T('bt_remove_title') + '"><i class="fas fa-trash-alt"></i></button>' +
        '</span></div>';
    }).join('') : '<div class="text-muted small py-3"><i class="fas fa-info-circle mr-1"></i>' + T('bt_empty') + '</div>');
    $('#btDisconnect').prop('disabled', !info.connected);
  }

  var s = settings();
  $('#btPaper').val(String(s.paper));
  $('#btEnabled').prop('checked', s.enabled);
  $('#btAddBle').prop('disabled', !P.isSupported('ble'));
  $('#btAddSerial').prop('disabled', !P.isSupported('serial'));
  $('#btUnsupported').toggle(!P.isSupported('ble') && !P.isSupported('serial'));

  // ບັນທຶກທັນທີເມື່ອປ່ຽນ (ບໍ່ຕ້ອງກົດບັນທຶກ)
  $('#btPaper').on('change', function () { save({ paper: parseInt(this.value, 10) }); msg(T('bt_paper_saved'), 'text-success'); });
  if (P.speed) P.speed('fast');   // ຄວາມໄວພິມ: ໃຊ້ແບບໄວສະເໝີ (ບໍ່ມີບັອກເລືອກແລ້ວ)
  $('#btEnabled').on('change', function () {
    save({ enabled: this.checked });
    msg(this.checked ? T('bt_enabled_on') : T('bt_enabled_off'), 'text-success');
  });

  function add(mode) {
    busy(T('bt_choose'));
    P.connect(mode).then(function (name) {   // ເປີດລາຍຊື່ເລືອກເຄື່ອງ — ຕ້ອງມາຈາກການກົດ (browser ບັງຄັບ)
      save({ enabled: true });
      $('#btEnabled').prop('checked', true);
      render();
      msg(OK + T('bt_added', name), 'text-success');
      Swal.fire({
        icon: 'success',
        title: 'ເຊື່ອມຕໍ່ປິ່ນເຕີສຳເລັດ',
        text: name ? 'ເຊື່ອມຕໍ່ກັບ ' + name + ' ແລ້ວ' : '',
        timer: 2000,
        showConfirmButton: false
      });
    }).catch(function (e) {
      render();
      if (e && e.name === 'NotFoundError') {
        msg(T('bt_not_chosen'), 'text-muted');
        return;
      }
      msg(e && e.message ? esc(e.message) : T('bt_connect_fail'), 'text-danger');
      Swal.fire({
        icon: 'error',
        title: 'ເຊື່ອມຕໍ່ປິ່ນເຕີບໍ່ສຳເລັດ',
        text: 'ກະລຸນາລອງໃໝ່ອີກຄັ້ງ',
        confirmButtonText: 'ຕົກລົງ',
        confirmButtonColor: '#2563eb'
      });
    });
  }
  $('#btAddBle').on('click', function () { add('ble'); });
  $('#btAddSerial').on('click', function () { add('serial'); });

  $('#btScan').on('click', function () {
    if (!P.list().length) {
      msg(T('bt_need_add'), 'text-danger');
      Swal.fire({
        icon: 'warning',
        title: 'ຍັງບໍ່ໄດ້ຕັ້ງເຄື່ອງພິມ',
        text: 'ກະລຸນາກົດ "ເພີ່ມເຄື່ອງພິມ Bluetooth" ກ່ອນ',
        confirmButtonText: 'ຕົກລົງ',
        confirmButtonColor: '#2563eb'
      });
      return;
    }
    busy(T('bt_scanning'));
    P.reconnect(true).then(function (ok) {
      render();
      if (ok) {
        msg(OK + T('bt_connected_msg', P.info().name), 'text-success');
        Swal.fire({
          icon: 'success',
          title: 'ເຊື່ອມຕໍ່ປິ່ນເຕີສຳເລັດ',
          text: P.info().name ? 'ເຊື່ອມຕໍ່ກັບ ' + P.info().name + ' ແລ້ວ' : '',
          timer: 2000,
          showConfirmButton: false
        });
      } else {
        msg(T('bt_none_on'), 'text-danger');
        Swal.fire({
          icon: 'error',
          title: 'ເຊື່ອມຕໍ່ປິ່ນເຕີບໍ່ສຳເລັດ',
          text: 'ກະລຸນາລອງໃໝ່ອີກຄັ້ງ',
          confirmButtonText: 'ຕົກລົງ',
          confirmButtonColor: '#2563eb'
        });
      }
    });
  });

  $('#btDisconnect').on('click', function () {
    P.disconnect().then(function () {
      render();
      msg(T('bt_disconnected'));
      Swal.fire({
        icon: 'info',
        title: 'ຕັດການເຊື່ອມຕໍ່ແລ້ວ',
        timer: 1500,
        showConfirmButton: false
      });
    });
  });

  $('#btList').on('click', '.bt-use', function () {
    busy(T('bt_connecting'));
    var key = $(this).attr('data-key');
    P.use(key).then(function (name) {
      render();
      msg(OK + T('bt_connected_msg', name), 'text-success');
      Swal.fire({
        icon: 'success',
        title: 'ເຊື່ອມຕໍ່ປິ່ນເຕີສຳເລັດ',
        text: name ? 'ເຊື່ອມຕໍ່ກັບ ' + name + ' ແລ້ວ' : '',
        timer: 2000,
        showConfirmButton: false
      });
    }).catch(function (e) {
      render();
      msg(e && e.message ? esc(e.message) : T('bt_connect_fail'), 'text-danger');
      Swal.fire({
        icon: 'error',
        title: 'ເຊື່ອມຕໍ່ປິ່ນເຕີບໍ່ສຳເລັດ',
        text: 'ກະລຸນາລອງໃໝ່ອີກຄັ້ງ',
        confirmButtonText: 'ຕົກລົງ',
        confirmButtonColor: '#2563eb'
      });
    });
  });

  $('#btList').on('click', '.bt-remove', function () {
    var key = $(this).attr('data-key');
    Swal.fire({ icon: 'warning', title: I18N_BT_CARD['printers.bt_remove_confirm'], showCancelButton: true, reverseButtons: true,
      confirmButtonText: I18N_BT_CARD['printers.bt_remove_btn'], cancelButtonText: I18N_BT_CARD['printers.bt_cancel'], confirmButtonColor: '#ef4444' }).then(function (r) {
      if (!r.isConfirmed) return;
      P.forget(key).then(function () {
        if (!P.list().length) { save({ enabled: false }); $('#btEnabled').prop('checked', false); }
        render(); msg(T('bt_removed'), 'text-success');
      });
    });
  });

  $('#btTest').on('click', function () {
    var pw = parseInt($('#btPaper').val(), 10);
    if (pw === 148 || pw === 210) { msg(T('bt_page_mode'), 'text-info'); return; }   // A5/A4 ບໍ່ສົ່ງ Bluetooth
    if (!P.list().length) { msg(T('bt_test_need'), 'text-danger'); return; }
    busy(T('bt_testing'));
    var dots = parseInt($('#btPaper').val(), 10) === 58 ? 384 : 576;
    P.printTest(dots).then(function () {
      render(); msg(OK + T('bt_test_sent', P.info().name), 'text-success');
    }).catch(function (e) { render(); msg(e && e.message ? esc(e.message) : T('bt_print_fail'), 'text-danger'); });
  });

  // ສະຖານະອັບເດດເອງ ເວລາເຄື່ອງພິມຖືກເປີດ/ປິດ; ຖອນ listener ຕອນອອກຈາກໜ້າ
  P.onStatus(render);
  window.addEventListener('pagehide', function () { P.offStatus(render); });
  // ກົດທຸງປ່ຽນພາສາ: ໜ້າຫຼັກປ່ຽນຄ່າໃນ I18N_BT_CARD ແລ້ວ → ແຕ້ມສະຖານະ/ລາຍຊື່ໃໝ່ ແລະ ລ້າງຂໍ້ຄວາມເກົ່າ
  window.addEventListener('pos:langchange', function () { render(); msg(''); });
  render();
  if (settings().enabled && !P.isConnected() && P.list().length) {
    P.reconnect().then(function () { render(); });
  }
})();
</script>
