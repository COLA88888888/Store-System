<?php
/*
 * ສ່ວນ: ພິມໃບບິນຜ່ານ Bluetooth (ບໍ່ຕ້ອງໃຊ້ສາຍ) — ໜ້າຂາຍ ພິມຢ່າງດຽວ, ບໍ່ເຊື່ອມຕໍ່
 * ເຊື່ອມຕໍ່/ເພີ່ມ/ລຶບ ເຄື່ອງພິມ ເຮັດຢູ່ ຕັ້ງຄ່າ > ເຄື່ອງພິມ (pages/settings/printers/partials/bluetooth_card.php).
 * ການເຊື່ອມຕໍ່ຢູ່ກັບໜ້າຫຼັກ (home/dashboard.php) ຈຶ່ງບໍ່ຂາດເວລາປ່ຽນໜ້າ.
 * - ຫໍ່ printReceiptAuto() (receipt_print.php): ເຄື່ອງພິມ Bluetooth ເຊື່ອມຕໍ່ຢູ່ → ສົ່ງຮູບໃບບິນແບບດຽວກັບເຄື່ອງພິມທົ່ວໄປ
 *   ໄປເຄື່ອງນັ້ນທັນທີ (ຫຼັງຂາຍສຳເລັດ ແລະ ຕອນກົດ "ພິມໃບບິນ").
 *   ບໍ່ໄດ້ເຊື່ອມຕໍ່ / ປິດ Bluetooth → ພິມແບບປົກກະຕິ (LAN / ໜ້າຕ່າງພິມ).
 * - ແຖວ "ພິມອອກ: …" ໃຕ້ປຸ່ມຢືນຢັນຊຳລະ: ສະແດງສະຖານະຢ່າງດຽວ.
 * ໄຟລ໌ນີ້ຖືກ require ຈາກ pages/pos/partials/js/pos_checkout_js.php ຕໍ່ຈາກ receipt_print.php.
 */
?>
var BT_SETTINGS_KEY = 'StoreMini.bt_printer_settings';

// ຕັ້ງຄ່າ (ບັນທຶກຈາກໜ້າຕັ້ງຄ່າເຄື່ອງພິມ): enabled ຖ້າບໍ່ເຄີຍປິດເອງ ແລະ ມີເຄື່ອງພິມໃນລາຍຊື່ (ຫຼື ເຄື່ອງ Android ທີ່ມີເຄື່ອງພິມໃນຕົວ 57x40mm) → ເປີດໃຊ້
function btSettings() {
  var s = {};
  try { s = JSON.parse(localStorage.getItem(BT_SETTINGS_KEY) || '{}') || {}; } catch (e) { s = {}; }
  var isHandheld = /Android/i.test(navigator.userAgent || '');
  var known = isHandheld || !!(window.MiniposBtPrinter && MiniposBtPrinter.list().length);
  var defPaper = isHandheld ? 58 : 80;
  return { enabled: (s.enabled === undefined) ? known : !!s.enabled, paper: parseInt(s.paper, 10) === 58 ? 58 : (parseInt(s.paper, 10) === 80 ? 80 : defPaper) };
}

// ແຖວ "ພິມອອກ: …" ໃນໜ້າຕ່າງຊຳລະເງິນ (ສະແດງຢ່າງດຽວ)
function btPaintStatus() {
  return;
  var s = btSettings(), ok = window.MiniposBtPrinter && MiniposBtPrinter.info().connected;
  var esc = function (t) { return $('<div>').text(t || '').html(); };
  var name = window.MiniposBtPrinter ? MiniposBtPrinter.info().name : '';
  $('.bt-printer-line').html(
    !s.enabled ? '<span class="text-muted"><i class="fas fa-print mr-1"></i><?php echo tlj('ພິມອອກ: ເຄື່ອງພິມປົກກະຕິ'); ?></span>'
    : ok ? '<span class="text-success font-weight-bold"><i class="fab fa-bluetooth-b mr-1"></i><?php echo tlj('ພິມອອກ:'); ?> ' + esc(name) + ' <?php echo tlj('(ເຊື່ອມຕໍ່ຢູ່)'); ?></span>'
         : '<span class="text-warning font-weight-bold"><i class="fas fa-exclamation-triangle mr-1"></i><?php echo tlj('ເຄື່ອງພິມ Bluetooth ບໍ່ໄດ້ເຊື່ອມຕໍ່'); ?></span>' +
           ' <span class="text-muted">— <?php echo tlj('ເຊື່ອມຢູ່ ຕັ້ງຄ່າ'); ?> › <?php echo tlj('ເຄື່ອງພິມ'); ?></span>'
  );
}

// ແຕ້ມໃບບິນ (#receiptPrintArea) ດ້ວຍໂຄງຮ່າງດຽວກັບເຄື່ອງພິມທົ່ວໄປທຸກຢ່າງ (302px) ແລ້ວປັບຂະໜາດຕາມຈຸດຫົວພິມ (384 ຫຼື 576 dots)
function renderReceiptBluetoothCanvas() {
  var s = btSettings(), dots = s.paper === 58 ? 384 : 576;
  var source = document.getElementById('receiptPrintArea');
  var layoutPx = MiniposLanReceipt.paperPx(80); // ກັອບປີ້ໂຄງຮ່າງບິນເຄື່ອງພິມທົ່ວໄປມາເລີຍ (302px)
  var render = MiniposLanReceipt.toCanvas
    ? MiniposLanReceipt.toCanvas(source, layoutPx, '<?php echo $base_path; ?>', POS_RCPT_EXTRA_CSS, dots)
    : receiptAreaToImage(layoutPx);
  return render.then(function (img) { return { img: img, dots: dots }; });
}

function printReceiptBluetooth() {
  return renderReceiptBluetoothCanvas().then(function (r) {
    return MiniposBtPrinter.printImage(r.img, { dots: r.dots });
  }).then(function () { return true; });
}

function btNotice(icon, title, text) {
  Swal.fire({ toast: true, position: 'top-end', icon: icon, title: title, text: text || '',
    showConfirmButton: false, timer: 5000, timerProgressBar: true });
}

// ຫໍ່ printReceiptAuto ເດີມ: ແຕ້ມບິນຂະໜານກັບການກວດເຊື່ອມຕໍ່ Bluetooth ເພື່ອໃຫ້ບິນພິມອອກໄວທັນທີ
(function () {
  var original = window.printReceiptAuto;
  if (typeof original !== 'function') return;
  window.printReceiptAuto = function () {
    var self = this, args = arguments;
    var normal = function () { return original.apply(self, args); };
    if (!btSettings().enabled || !window.MiniposBtPrinter || !MiniposBtPrinter.list().length) return normal();
    // ເຄື່ອງພິມໃບບິນຕັ້ງເປັນ A4/A5 → ພິມເປັນໜ້າເຕັມ (ບໍ່ສົ່ງເຄື່ອງ Bluetooth ທີ່ເປັນມ້ວນ)
    var rpPaper = (window.POS_RECEIPT_PRINTER || {}).paper_width;
    if (window.MiniposLanReceipt && MiniposLanReceipt.receiptPaper) rpPaper = MiniposLanReceipt.receiptPaper(rpPaper);   // A5/A4 ທີ່ເລືອກໃນເຄື່ອງນີ້ມາກ່ອນ
    if (window.MiniposLanReceipt && MiniposLanReceipt.isPagePaper && MiniposLanReceipt.isPagePaper(rpPaper)) return normal();
    var rendered = renderReceiptBluetoothCanvas();
    var conn = MiniposBtPrinter.isConnected() ? Promise.resolve(true) : MiniposBtPrinter.reconnect();
    return conn.then(function (ok) {
      btPaintStatus();
      if (!ok) return normal();
      return rendered.then(function (r) {
        return MiniposBtPrinter.printImage(r.img, { dots: r.dots });
      }).then(function () {
        return true;
      }).catch(function (err) {
        btPaintStatus();
        btNotice('error', '<?php echo tlj('ພິມຜ່ານ Bluetooth ບໍ່ສຳເລັດ'); ?>', ((err && err.message) || '') + ' — <?php echo tlj('ພິມແບບປົກກະຕິແທນ'); ?>');
        return normal();
      });
    });
  };
})();

$(function () {
  if (!window.MiniposBtPrinter) return;
  MiniposBtPrinter.onStatus(btPaintStatus);
  window.addEventListener('pagehide', function () { MiniposBtPrinter.offStatus(btPaintStatus); });
  btPaintStatus();
  if (btSettings().enabled && !MiniposBtPrinter.isConnected() && MiniposBtPrinter.list().length) {
    MiniposBtPrinter.reconnect().then(btPaintStatus);
  }
  $('#checkoutModal').on('show.bs.modal', function () {
    btPaintStatus();
    if (btSettings().enabled && window.MiniposBtPrinter) {
      if (!MiniposBtPrinter.isConnected() && MiniposBtPrinter.list().length) {
        MiniposBtPrinter.reconnect().then(btPaintStatus);
      }
      if (MiniposBtPrinter.warm) MiniposBtPrinter.warm();
    }
  });
});
