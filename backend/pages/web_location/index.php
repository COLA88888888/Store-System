<?php
/**
 * ປັກໝຸດຕຳແໜ່ງຮ້ານ — ຕຳແໜ່ງ (lat/lng) ແລະ ທີ່ຢູ່ ທີ່ເວັບໄຊໃຊ້ທຸກບ່ອນ: ແຜນທີ່ footer, ໜ້າຕິດຕໍ່, ນຳທາງໃນແຊັດ
 * (frontend/models/company.php ອ່ານຈາກ w_system_settings: web_shop_lat / web_shop_lng / web_shop_address).
 * ວິທີປັກ: ກົດ "ໃຊ້ຕຳແໜ່ງປັດຈຸບັນ" (GPS ຂອງເຄື່ອງ — ຕ້ອງເປີດຜ່ານ https ຫຼື localhost), ລາກໝຸດ, ກົດໃສ່ແຜນທີ່,
 * ຫຼື ວາງລິ້ງ Google Maps / ພິກັດ "17.97, 102.63". ແຜນທີ່: Leaflet (plugins/leaflet, BSD) + OpenStreetMap.
 * ສິດ: ໂມດູນ setup (ຕັ້ງຄ່າ).
 */
session_start();
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';
require_once __DIR__ . '/../../config/db.php';
if (empty($_SESSION['user_id']) || !hasPermission('setup')) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$saveSetting = function (string $key, string $val) use ($pdo) {
    $pdo->prepare("INSERT INTO w_system_settings (setting_key, setting_value) VALUES (?, ?)
                   ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$key, $val]);
};
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lat = filter_var($_POST['lat'] ?? '', FILTER_VALIDATE_FLOAT);
    $lng = filter_var($_POST['lng'] ?? '', FILTER_VALIDATE_FLOAT);
    if ($lat === false || $lng === false || abs($lat) > 90 || abs($lng) > 180) {
        $message = 'error';
    } else {
        $saveSetting('web_shop_lat', number_format($lat, 6, '.', ''));
        $saveSetting('web_shop_lng', number_format($lng, 6, '.', ''));
        $addr = trim((string)($_POST['address'] ?? ''));
        if ($addr !== '') {
            $saveSetting('web_shop_address', mb_substr($addr, 0, 255));
        }
        $message = 'saved';
    }
}
$curLat = getSetting($pdo, 'web_shop_lat', '17.9693056');
$curLng = getSetting($pdo, 'web_shop_lng', '102.6574093');
$curAddr = getSetting($pdo, 'web_shop_address', 'ບ້ານ ໂນນຄໍ້ເໜືອ ເມືອງ ໄຊເສດຖາ ນະຄອນຫຼວງວຽງຈັນ');
$isSet = getSetting($pdo, 'web_shop_lat', '') !== '';

require_once __DIR__ . '/../../layouts/header.php';
?>
<link rel="stylesheet" href="<?php echo $base_path; ?>plugins/leaflet/leaflet.css">
<?php echo themeCssLink('web_chats.css'); ?>
<div class="content-wrapper bg-light">
  <section class="content pt-3">
    <div class="container-fluid">
      <h1 class="wc-title"><i class="fas fa-map-marker-alt text-danger"></i> ປັກໝຸດຕຳແໜ່ງຮ້ານ (ແຜນທີ່ເວັບໄຊ)</h1>
      <?php if ($message === 'saved'): ?>
        <div class="alert alert-success py-2">ບັນທຶກຕຳແໜ່ງແລ້ວ — ແຜນທີ່ໃນເວັບໄຊ (footer, ໜ້າຕິດຕໍ່, ນຳທາງໃນແຊັດ) ໃຊ້ຕຳແໜ່ງໃໝ່ນີ້ທັນທີ</div>
      <?php elseif ($message === 'error'): ?>
        <div class="alert alert-danger py-2">ພິກັດບໍ່ຖືກຕ້ອງ ກະລຸນາປັກໝຸດໃໝ່</div>
      <?php elseif (!$isSet): ?>
        <div class="alert alert-warning py-2">ຍັງບໍ່ໄດ້ປັກໝຸດ — ເວັບໄຊກຳລັງໃຊ້ຕຳແໜ່ງປະມານ (17.9516, 102.6682). ກົດ "ໃຊ້ຕຳແໜ່ງປັດຈຸບັນ" ຕອນຢູ່ຮ້ານ ແລ້ວບັນທຶກ</div>
      <?php endif; ?>

      <div class="wl">
        <div class="wl-map" id="wlMap"></div>
        <form method="post" class="wl-panel card">
          <div class="card-body">
            <button type="button" class="btn btn-danger btn-block mb-2" id="wlGps"><i class="fas fa-crosshairs"></i> ໃຊ້ຕຳແໜ່ງປັດຈຸບັນ (GPS)</button>
            <small class="text-muted d-block mb-3">ກົດຕອນຢູ່ຮ້ານ — ຫຼື ລາກໝຸດສີແດງ / ກົດໃສ່ແຜນທີ່ ໃຫ້ຖືກບ່ອນ</small>

            <label class="mb-1">ວາງລິ້ງ Google Maps ຫຼື ພິກັດ</label>
            <div class="input-group input-group-sm mb-3">
              <input type="text" class="form-control" id="wlPaste" placeholder="https://maps.google.com/... ຫຼື 17.97, 102.63">
              <div class="input-group-append"><button type="button" class="btn btn-outline-secondary" id="wlPasteBtn">ໃຊ້</button></div>
            </div>

            <div class="form-row">
              <div class="col"><label class="mb-1">Latitude</label><input type="text" name="lat" id="wlLat" class="form-control form-control-sm" value="<?php echo htmlspecialchars($curLat); ?>" required></div>
              <div class="col"><label class="mb-1">Longitude</label><input type="text" name="lng" id="wlLng" class="form-control form-control-sm" value="<?php echo htmlspecialchars($curLng); ?>" required></div>
            </div>
            <div id="wlAcc" class="small text-muted mt-1"></div>

            <label class="mb-1 mt-3">ທີ່ຢູ່ (ສະແດງໃນເວັບໄຊ)</label>
            <textarea name="address" class="form-control form-control-sm" rows="2" maxlength="255"><?php echo htmlspecialchars($curAddr); ?></textarea>

            <button class="btn btn-primary btn-block mt-3"><i class="fas fa-save"></i> ບັນທຶກຕຳແໜ່ງ</button>
            <a class="btn btn-light btn-block btn-sm mt-2" id="wlGmaps" target="_blank" rel="noopener">ເປີດກວດໃນ Google Maps</a>
          </div>
        </form>
      </div>
    </div>
  </section>
</div>
<script src="<?php echo $base_path; ?>plugins/leaflet/leaflet.js"></script>
<script>
(function () {
  L.Icon.Default.imagePath = '<?php echo $base_path; ?>plugins/leaflet/images/';
  var lat = parseFloat($('#wlLat').val()) || 17.9516, lng = parseFloat($('#wlLng').val()) || 102.6682;
  var map = L.map('wlMap').setView([lat, lng], 17);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
  var pin = L.marker([lat, lng], { draggable: true }).addTo(map);
  var circle = null;

  function setPos(la, ln, zoom, acc) {
    la = +(+la).toFixed(6); ln = +(+ln).toFixed(6);
    pin.setLatLng([la, ln]);
    map.setView([la, ln], zoom || map.getZoom());
    $('#wlLat').val(la); $('#wlLng').val(ln);
    $('#wlGmaps').attr('href', 'https://www.google.com/maps?q=' + la + ',' + ln);
    if (circle) { map.removeLayer(circle); circle = null; }
    if (acc) {
      circle = L.circle([la, ln], { radius: acc, color: '#dc2626', weight: 1, fillOpacity: .08 }).addTo(map);
      $('#wlAcc').text('ຄວາມແມ່ນຍຳ GPS ປະມານ ±' + Math.round(acc) + ' ແມັດ — ຖ້າບໍ່ພໍດີ ລາກໝຸດແກ້ໄດ້');
    } else { $('#wlAcc').text(''); }
  }
  setPos(lat, lng, 17);
  pin.on('dragend', function () { var p = pin.getLatLng(); setPos(p.lat, p.lng); });
  map.on('click', function (e) { setPos(e.latlng.lat, e.latlng.lng); });
  $('#wlLat, #wlLng').on('change', function () { setPos($('#wlLat').val(), $('#wlLng').val()); });

  // GPS: ຂໍຫຼາຍຄັ້ງ ເອົາຄ່າທີ່ແມ່ນຍຳທີ່ສຸດ (ພາຍໃນ 12 ວິ)
  $('#wlGps').on('click', function () {
    if (!window.isSecureContext || !navigator.geolocation) {
      return Swal.fire({ icon: 'warning', text: 'GPS ໃຊ້ໄດ້ສະເພາະເມື່ອເປີດລະບົບດ້ວຍ https ຫຼື localhost — ລາກໝຸດ ຫຼື ວາງລິ້ງ Google Maps ແທນ' });
    }
    var $b = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ກຳລັງຫາຕຳແໜ່ງ…');
    var best = null, done = false;
    var finish = function () {
      if (done) return; done = true;
      navigator.geolocation.clearWatch(watch);
      $b.prop('disabled', false).html('<i class="fas fa-crosshairs"></i> ໃຊ້ຕຳແໜ່ງປັດຈຸບັນ (GPS)');
      if (best) setPos(best.latitude, best.longitude, 18, best.accuracy);
    };
    var watch = navigator.geolocation.watchPosition(function (pos) {
      if (!best || pos.coords.accuracy < best.accuracy) best = pos.coords;
      if (best.accuracy <= 15) finish();
    }, function () {
      done = true; navigator.geolocation.clearWatch(watch);
      $b.prop('disabled', false).html('<i class="fas fa-crosshairs"></i> ໃຊ້ຕຳແໜ່ງປັດຈຸບັນ (GPS)');
      Swal.fire({ icon: 'warning', text: 'ບໍ່ໄດ້ຮັບຕຳແໜ່ງ — ກະລຸນາອະນຸຍາດໃຫ້ໃຊ້ຕຳແໜ່ງ (Location) ແລ້ວລອງໃໝ່' });
    }, { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 });
    setTimeout(finish, 12000);
  });

  // ວາງລິ້ງ Google Maps (…@lat,lng… / ?q=lat,lng / !3dlat!4dlng) ຫຼື "lat, lng"
  $('#wlPasteBtn').on('click', function () {
    var t = $('#wlPaste').val(), m = t.match(/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/) || t.match(/@(-?\d+\.\d+),\s*(-?\d+\.\d+)/) || t.match(/(-?\d{1,2}\.\d+)\s*,\s*(-?\d{1,3}\.\d+)/);
    if (!m) return Swal.fire({ icon: 'warning', text: 'ບໍ່ພົບພິກັດໃນລິ້ງ — ໃນ Google Maps ກົດຄ້າງບ່ອນຮ້ານ ແລ້ວກັອບປີ້ຕົວເລກພິກັດມາວາງ' });
    setPos(m[1], m[2], 18);
  });
})();
</script>
<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
