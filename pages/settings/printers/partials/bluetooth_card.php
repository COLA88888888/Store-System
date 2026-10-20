<?php
/*
 * ສ່ວນ: ບັດ "ເຄື່ອງພິມ Bluetooth" ໃນໜ້າ ຕັ້ງຄ່າ > ເຄື່ອງພິມ — ບ່ອນດຽວທີ່ເຊື່ອມຕໍ່ເຄື່ອງພິມ Bluetooth.
 * ສະຖານະ ເຊື່ອມຕໍ່ຢູ່ / ບໍ່ໄດ້ເຊື່ອມຕໍ່, ລາຍຊື່ເຄື່ອງພິມ (ໃຊ້ເຄື່ອງນີ້ / ລຶບ), ເພີ່ມເຄື່ອງ (Bluetooth / COM port),
 * ຂະໜາດເຈ້ຍ, ເປີດ/ປິດ ການພິມໃບບິນຜ່ານ Bluetooth ແລະ ທົດສອບພິມ.
 * ຕົວເຊື່ອມຕໍ່ = assets/js/bluetooth-printer.js ຂອງໜ້າຫຼັກ (home/dashboard.php) — ເຊື່ອມຢູ່ນີ້ ແລ້ວໄປໜ້າຂາຍ
 * ການເຊື່ອມຕໍ່ຍັງຢູ່ ກົດພິມອອກເຄື່ອງນັ້ນທັນທີ (pages/pos/partials/js/checkout/receipt_bluetooth.php).
 * ຕັ້ງຄ່າຈື່ໄວ້ໃນ browser ເຄື່ອງນີ້ (localStorage) — ແຕ່ລະເຄື່ອງຂາຍມີເຄື່ອງພິມຂອງຕົນ.
 * ຖືກ require ຈາກ pages/settings/printers/index.php.
 */
?>
<script src="<?php echo $base_path; ?>assets/js/bluetooth-printer-escpos.js?v=<?php echo @filemtime(__DIR__ . '/../../../../assets/js/bluetooth-printer-escpos.js'); ?>"></script>
<script src="<?php echo $base_path; ?>assets/js/bluetooth-printer.js?v=<?php echo @filemtime(__DIR__ . '/../../../../assets/js/bluetooth-printer.js'); ?>"></script>

<div class="card border-0 shadow-sm mb-4" id="btCard" style="border-radius: 12px; border: 1.5px solid #e2e8f0; background: #ffffff;">
  <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center flex-wrap" style="gap: 8px;">
    <h6 class="m-0 font-weight-bold text-dark"><i class="fab fa-bluetooth-b mr-2 text-primary"></i> ເຄື່ອງພິມ Bluetooth (ໃບບິນ — ບໍ່ໃຊ້ສາຍ)</h6>
    <span id="btStatusBadge" class="badge px-3 py-2" style="font-size: 0.85rem;"></span>
  </div>
  <div class="card-body px-4 py-3">
    <div id="btUnsupported" class="alert alert-warning py-2 small" style="display: none;">
      <i class="fas fa-exclamation-triangle mr-1"></i>
      browser ນີ້ບໍ່ຮອງຮັບ Bluetooth — ກະລຸນາໃຊ້ <b>Google Chrome</b> ຫຼື <b>Microsoft Edge</b> ແລະ ເປີດລະບົບຜ່ານ <b>localhost</b> ຫຼື <b>https</b>.
    </div>

    <div class="row">
      <div class="col-lg-7 mb-3">
        <div class="font-weight-bold small text-dark mb-1">ເຄື່ອງພິມທີ່ຕັ້ງໄວ້ <span class="font-weight-normal text-muted">— ເຄື່ອງໃດເປີດຢູ່ ໃບບິນພິມອອກເຄື່ອງນັ້ນ</span></div>
        <div id="btList" class="border rounded px-3" style="background: #f8fafc; min-height: 52px;"></div>
        <div id="btMsg" class="small mt-2"></div>
        <div class="d-flex flex-wrap mt-2" style="gap: 8px;">
          <button type="button" class="btn btn-primary btn-sm font-weight-bold" id="btAddBle"><i class="fas fa-plus mr-1"></i> ເພີ່ມເຄື່ອງພິມ Bluetooth</button>
          <button type="button" class="btn btn-outline-primary btn-sm font-weight-bold" id="btAddSerial"><i class="fas fa-plug mr-1"></i> ເພີ່ມ COM port</button>
          <button type="button" class="btn btn-outline-secondary btn-sm font-weight-bold" id="btScan"><i class="fas fa-sync-alt mr-1"></i> ເຊື່ອມເຄື່ອງທີ່ເປີດຢູ່</button>
          <button type="button" class="btn btn-outline-danger btn-sm font-weight-bold" id="btDisconnect"><i class="fas fa-unlink mr-1"></i> ຕັດການເຊື່ອມຕໍ່</button>
        </div>
        <small class="text-muted d-block mt-2">ເຄື່ອງພິມ Bluetooth ນ້ອຍສ່ວນຫຼາຍ → <b>ເພີ່ມເຄື່ອງພິມ Bluetooth</b>. ຖ້າບໍ່ພົບໃນລາຍການ ແລະ Windows ຈັບຄູ່ເຄື່ອງພິມເປັນ COM port ແລ້ວ → <b>ເພີ່ມ COM port</b>.</small>
      </div>
      <div class="col-lg-5 mb-3">
        <div class="form-group mb-2">
          <label class="font-weight-bold small text-dark mb-1">ຂະໜາດເຈ້ຍ</label>
          <select id="btPaper" class="form-control" style="height: 42px;">
            <option value="80">80 mm (ມ້ວນກວ້າງ ~8 cm)</option>
            <option value="58">58 mm / 57x40 mm (ເຄື່ອງພິມໃນຕົວ / ມ້ວນ ~5.7 cm)</option>
          </select>
        </div>
        <div class="custom-control custom-switch mb-3">
          <input type="checkbox" class="custom-control-input" id="btEnabled">
          <label class="custom-control-label font-weight-bold small" for="btEnabled">ພິມໃບບິນຂາຍຜ່ານ Bluetooth (ພິມອັດຕະໂນມັດຫຼັງຂາຍສຳເລັດ)</label>
        </div>
        <button type="button" class="btn btn-success btn-block font-weight-bold" id="btTest"><i class="fas fa-print mr-1"></i> ທົດສອບພິມ</button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var KEY = 'StoreMini.bt_printer_settings';
  var P = window.MiniposBtPrinter;
  var esc = function (t) { return $('<div>').text(t || '').html(); };
  if (!P) { $('#btCard .card-body').html('<div class="text-danger small">ໂຫຼດ assets/js/bluetooth-printer.js ບໍ່ໄດ້</div>'); return; }

  function settings() {
    var s = {};
    try { s = JSON.parse(localStorage.getItem(KEY) || '{}') || {}; } catch (e) { s = {}; }
    var isHandheld = /Android/i.test(navigator.userAgent || '');
    var defPaper = isHandheld ? 58 : 80;
    return {
      enabled: (s.enabled === undefined) ? (P.list().length > 0 || isHandheld) : !!s.enabled,
      paper: parseInt(s.paper, 10) === 58 ? 58 : (parseInt(s.paper, 10) === 80 ? 80 : defPaper)
    };
  }
  function save(patch) {
    var s = settings();
    Object.keys(patch).forEach(function (k) { s[k] = patch[k]; });
    try { localStorage.setItem(KEY, JSON.stringify(s)); } catch (e) {}
  }
  function msg(text, cls) { $('#btMsg').html(text ? '<span class="' + (cls || 'text-muted') + '">' + text + '</span>' : ''); }
  function busy(text) { msg('<i class="fas fa-spinner fa-spin mr-1"></i>' + esc(text), 'text-primary'); }

  // ສະຖານະ + ລາຍຊື່ເຄື່ອງພິມ
  function render() {
    var info = P.info(), items = P.list();
    $('#btStatusBadge')
      .toggleClass('badge-success', info.connected).toggleClass('badge-danger', !info.connected)
      .html(info.connected ? '<i class="fas fa-check-circle mr-1"></i> ເຊື່ອມຕໍ່ຢູ່: ' + esc(info.name)
                           : '<i class="fas fa-times-circle mr-1"></i> ບໍ່ໄດ້ເຊື່ອມຕໍ່');
    $('#btList').html(items.length ? items.map(function (p) {
      return '<div class="d-flex align-items-center justify-content-between py-2 border-bottom">' +
        '<span><i class="' + (p.mode === 'serial' ? 'fas fa-plug' : 'fab fa-bluetooth-b') + ' mr-2 ' + (p.connected ? 'text-success' : 'text-muted') + '"></i>' +
          '<b>' + esc(p.name) + '</b>' +
          (p.connected ? ' <span class="badge badge-success ml-1">ເຊື່ອມຕໍ່ຢູ່</span>' : ' <span class="badge badge-light border ml-1">ບໍ່ໄດ້ເຊື່ອມຕໍ່</span>') + '</span>' +
        '<span class="text-nowrap">' +
          (p.connected ? '' : '<button type="button" class="btn btn-outline-primary btn-sm py-0 mr-1 bt-use" data-key="' + esc(p.key) + '">ເຊື່ອມເຄື່ອງນີ້</button>') +
          '<button type="button" class="btn btn-link btn-sm text-danger p-0 bt-remove" data-key="' + esc(p.key) + '" title="ລຶບເຄື່ອງນີ້"><i class="fas fa-trash-alt"></i></button>' +
        '</span></div>';
    }).join('') : '<div class="text-muted small py-3"><i class="fas fa-info-circle mr-1"></i>ຍັງບໍ່ໄດ້ຕັ້ງເຄື່ອງພິມ — ເປີດເຄື່ອງພິມ ແລ້ວກົດ <b>ເພີ່ມເຄື່ອງພິມ Bluetooth</b></div>');
    $('#btDisconnect').prop('disabled', !info.connected);
  }

  var s = settings();
  $('#btPaper').val(String(s.paper));
  $('#btEnabled').prop('checked', s.enabled);
  $('#btAddBle').prop('disabled', !P.isSupported('ble'));
  $('#btAddSerial').prop('disabled', !P.isSupported('serial'));
  $('#btUnsupported').toggle(!P.isSupported('ble') && !P.isSupported('serial'));

  // ບັນທຶກທັນທີເມື່ອປ່ຽນ (ບໍ່ຕ້ອງກົດບັນທຶກ)
  $('#btPaper').on('change', function () { save({ paper: parseInt(this.value, 10) }); msg('ບັນທຶກຂະໜາດເຈ້ຍແລ້ວ', 'text-success'); });
  if (P.speed) P.speed('fast');   // ຄວາມໄວພິມ: ໃຊ້ແບບໄວສະເໝີ (ບໍ່ມີບັອກເລືອກແລ້ວ)
  $('#btEnabled').on('change', function () {
    save({ enabled: this.checked });
    msg(this.checked ? 'ໃບບິນຂາຍຈະພິມອອກເຄື່ອງພິມ Bluetooth ທີ່ເຊື່ອມຕໍ່ຢູ່' : 'ປິດແລ້ວ — ໃບບິນພິມແບບປົກກະຕິ', 'text-success');
  });

  function add(mode) {
    busy('ເລືອກເຄື່ອງພິມໃນລາຍການທີ່ຂຶ້ນມາ...');
    P.connect(mode).then(function (name) {   // ເປີດລາຍຊື່ເລືອກເຄື່ອງ — ຕ້ອງມາຈາກການກົດ (browser ບັງຄັບ)
      save({ enabled: true });
      $('#btEnabled').prop('checked', true);
      render();
      msg('<i class="fas fa-check-circle mr-1"></i>ເຊື່ອມຕໍ່ ' + esc(name) + ' ແລ້ວ — ກົດ <b>ທົດສອບພິມ</b> ເພື່ອກວດ', 'text-success');
    }).catch(function (e) {
      render();
      msg(e && e.name === 'NotFoundError' ? 'ບໍ່ໄດ້ເລືອກເຄື່ອງພິມ' : esc((e && e.message) || 'ເຊື່ອມຕໍ່ບໍ່ສຳເລັດ'), 'text-danger');
    });
  }
  $('#btAddBle').on('click', function () { add('ble'); });
  $('#btAddSerial').on('click', function () { add('serial'); });

  $('#btScan').on('click', function () {
    if (!P.list().length) { msg('ຍັງບໍ່ໄດ້ຕັ້ງເຄື່ອງພິມ — ກົດ "ເພີ່ມເຄື່ອງພິມ Bluetooth" ກ່ອນ', 'text-danger'); return; }
    busy('ກຳລັງຊອກເຄື່ອງພິມທີ່ເປີດຢູ່...');
    P.reconnect(true).then(function (ok) {
      render();
      msg(ok ? '<i class="fas fa-check-circle mr-1"></i>ເຊື່ອມຕໍ່ ' + esc(P.info().name) + ' ແລ້ວ' : 'ບໍ່ພົບເຄື່ອງພິມທີ່ເປີດຢູ່ — ເປີດເຄື່ອງພິມ ແລ້ວລອງໃໝ່', ok ? 'text-success' : 'text-danger');
    });
  });
  $('#btDisconnect').on('click', function () { P.disconnect().then(function () { render(); msg('ຕັດການເຊື່ອມຕໍ່ແລ້ວ'); }); });

  $('#btList').on('click', '.bt-use', function () {
    busy('ກຳລັງເຊື່ອມຕໍ່...');
    P.use($(this).attr('data-key')).then(function (name) {
      render(); msg('<i class="fas fa-check-circle mr-1"></i>ເຊື່ອມຕໍ່ ' + esc(name) + ' ແລ້ວ', 'text-success');
    }).catch(function (e) { render(); msg(esc((e && e.message) || 'ເຊື່ອມຕໍ່ບໍ່ສຳເລັດ'), 'text-danger'); });
  });
  $('#btList').on('click', '.bt-remove', function () {
    var key = $(this).attr('data-key');
    Swal.fire({ icon: 'warning', title: 'ລຶບເຄື່ອງພິມນີ້?', showCancelButton: true, reverseButtons: true,
      confirmButtonText: 'ລຶບ', cancelButtonText: 'ຍົກເລີກ', confirmButtonColor: '#ef4444' }).then(function (r) {
      if (!r.isConfirmed) return;
      P.forget(key).then(function () {
        if (!P.list().length) { save({ enabled: false }); $('#btEnabled').prop('checked', false); }
        render(); msg('ລຶບເຄື່ອງພິມແລ້ວ', 'text-success');
      });
    });
  });

  $('#btTest').on('click', function () {
    if (!P.list().length) { msg('ກົດ "ເພີ່ມເຄື່ອງພິມ Bluetooth" ກ່ອນ', 'text-danger'); return; }
    busy('ກຳລັງພິມທົດສອບ...');
    var dots = parseInt($('#btPaper').val(), 10) === 58 ? 384 : 576;
    P.printTest(dots).then(function () {
      render(); msg('<i class="fas fa-check-circle mr-1"></i>ສົ່ງໃບທົດສອບໄປ ' + esc(P.info().name) + ' ແລ້ວ', 'text-success');
    }).catch(function (e) { render(); msg(esc((e && e.message) || 'ພິມບໍ່ສຳເລັດ'), 'text-danger'); });
  });

  // ສະຖານະອັບເດດເອງ ເວລາເຄື່ອງພິມຖືກເປີດ/ປິດ; ຖອນ listener ຕອນອອກຈາກໜ້າ
  P.onStatus(render);
  window.addEventListener('pagehide', function () { P.offStatus(render); });
  render();
  if (settings().enabled && !P.isConnected() && P.list().length) {
    P.reconnect().then(function () { render(); });
  }
})();
</script>
