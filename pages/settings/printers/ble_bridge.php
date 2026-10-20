<?php
/*
 * ble_bridge.php — ຂົວຕໍ່ສົ່ງຂໍ້ມູນໃບບິນ ESC/POS ໄປເຄື່ອງພິມ Bluetooth BLE ຜ່ານ Windows ໂດຍກົງ.
 * ຮອງຮັບການພິມຕໍ່ເນື່ອງຫຼາຍບິນ (ມີ flock ປ້ອງກັນການສົ່ງຊ້ອນກັນ) ໂດຍບໍ່ຕ້ອງກົດເຊື່ອມຕໍ່ໃໝ່ໃນ browser.
 */
header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$body = json_decode($raw ?: '{}', true) ?: [];
$action = trim((string)($body['action'] ?? $_GET['action'] ?? 'print'));
$name = trim((string)($body['name'] ?? $_GET['name'] ?? ''));
$b64  = (string)($body['data'] ?? '');

if ($action === 'warm') {
    $lockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'storemini_ble_print.lock';
    $lockFp = @fopen($lockFile, 'c+');
    if ($lockFp && @flock($lockFp, LOCK_EX | LOCK_NB)) {
        @flock($lockFp, LOCK_UN);
        @fclose($lockFp);
    }
    echo json_encode(['status' => 'success']);
    exit;
}

if ($name === '' || $b64 === '') {
    echo json_encode(['status' => 'error', 'message' => 'ຂໍ້ມູນບໍ່ຄົບ']);
    exit;
}

$bytes = base64_decode($b64, true);
if ($bytes === false || strlen($bytes) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'ຂໍ້ມູນໃບບິນບໍ່ຖືກຕ້ອງ']);
    exit;
}

$lockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'storemini_ble_print.lock';
$lockFp = @fopen($lockFile, 'c+');
if ($lockFp) {
    @flock($lockFp, LOCK_EX);
}

$tmpFile = tempnam(sys_get_temp_dir(), 'bt_');
file_put_contents($tmpFile, $bytes);

$ps1   = __DIR__ . DIRECTORY_SEPARATOR . 'ble_send.ps1';
$cache = __DIR__ . DIRECTORY_SEPARATOR . 'ble_cache.json';

$cmd = sprintf(
    'powershell -NoProfile -NonInteractive -ExecutionPolicy Bypass -File %s -BinFile %s -CacheFile %s -PrinterName %s 2>&1',
    escapeshellarg($ps1),
    escapeshellarg($tmpFile),
    escapeshellarg($cache),
    escapeshellarg($name)
);

$out = [];
$code = 0;
exec($cmd, $out, $code);
@unlink($tmpFile);

if ($lockFp) {
    @flock($lockFp, LOCK_UN);
    @fclose($lockFp);
}

$text = trim(implode("\n", $out));
if ($code === 0 && strpos($text, 'OK') !== false) {
    echo json_encode(['status' => 'success']);
} else {
    $msg = 'ບໍ່ພົບເຄື່ອງພິມທີ່ເປີດຢູ່ — ກະລຸນາເປີດເຄື່ອງພິມ ' . $name;
    if (strpos($text, 'ERR_TIMEOUT') !== false) {
        $msg = 'ເຄື່ອງພິມ Bluetooth ບໍ່ຕອບສະໜອງ — ກວດວ່າເປີດເຄື່ອງພິມຢູ່';
    } elseif (strpos($text, 'ERR_WRITE') !== false) {
        $msg = 'ສົ່ງຂໍ້ມູນໄປເຄື່ອງພິມ Bluetooth ບໍ່ສຳເລັດ';
    }
    echo json_encode(['status' => 'error', 'message' => $msg]);
}
