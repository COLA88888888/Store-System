<?php
/*
 * ble_bridge.php — ຂົວຕໍ່ສົ່ງຂໍ້ມູນໃບບິນ ESC/POS ໄປເຄື່ອງພິມ Bluetooth BLE ຜ່ານ Windows ໂດຍກົງ.
 * ຮອງຮັບການພິມຕໍ່ເນື່ອງຫຼາຍບິນ (ມີ flock ປ້ອງກັນການສົ່ງຊ້ອນກັນ) ໂດຍບໍ່ຕ້ອງກົດເຊື່ອມຕໍ່ໃໝ່ໃນ browser.
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../lang/translator.php'; // tl() ສຳລັບຂໍ້ຄວາມຕອບກັບ (ພາສາຈາກ cookie minipos_lang — ໄຟລ໌ນີ້ບໍ່ໄດ້ໂຫຼດ config/db.php)
@set_time_limit(90);   // ເຄື່ອງພິມໃໝ່ທີ່ບໍ່ໄດ້ຈັບຄູ່ໃນ Windows: ble_send.ps1 ຊອກໄດ້ເຖິງ 40 ວິ ໃນເທື່ອທຳອິດ

// ຂົວນີ້ໃຊ້ PowerShell + Bluetooth ຂອງ Windows ເຄື່ອງທີ່ແລ່ນ server (ຄອມໃນຮ້ານ / Laragon). server ອອນລາຍ (Linux hosting)
// ໃຊ້ບໍ່ໄດ້ → ຕອບທັນທີ "unsupported" ໃຫ້ assets/js/bluetooth-printer.js ສະຫຼັບໄປເຊື່ອມເຄື່ອງພິມຈາກ browser ໂດຍກົງ (Web Bluetooth)
if (stripos(PHP_OS, 'WIN') !== 0) {
    echo json_encode(['status' => 'error', 'unsupported' => true, 'message' => 'BLE bridge needs a Windows server']);
    exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw ?: '{}', true) ?: [];
$action = trim((string)($body['action'] ?? $_GET['action'] ?? 'print'));
$name = trim((string)($body['name'] ?? $_GET['name'] ?? ''));
$b64  = (string)($body['data'] ?? '');

// ---------- ໂຕຊ່ວຍທີ່ເປີດຄ້າງໄວ້ (ble_helper.ps1) ----------
// ຖືການເຊື່ອມຕໍ່ BLE ໄວ້ລະຫວ່າງບິນ: ກວດສະຖານະ ~0.05 ວິ, ບິນເລີ່ມສົ່ງທັນທີ (ແທນ 2.5–6 ວິ ຂອງ ble_send.ps1 ທຸກບິນ)
// ແລະ ເຊື່ອມໃໝ່ເອງເມື່ອເປີດເຄື່ອງພິມ. ເປີດບໍ່ໄດ້ → null → ໃຊ້ ble_send.ps1 ຄືເກົ່າ.
const BLE_HELPER_PORT = 9133;
function bleHelperStart() {
    $ps1   = __DIR__ . DIRECTORY_SEPARATOR . 'ble_helper.ps1';
    $cache = __DIR__ . DIRECTORY_SEPARATOR . 'ble_cache.json';
    if (!is_file($ps1) || !function_exists('popen')) return;
    // start /B: ແລ່ນຢູ່ເບື້ອງຫຼັງ ບໍ່ມີໜ້າຕ່າງ; ໂຕທີສອງທີ່ເປີດຊ້ອນຈະອອກເອງ (port ຖືກໃຊ້ແລ້ວ)
    $cmd = 'start "" /B powershell -NoProfile -NonInteractive -WindowStyle Hidden -ExecutionPolicy Bypass -File "'
         . $ps1 . '" -Port ' . BLE_HELPER_PORT . ' -CacheFile "' . $cache . '" >NUL 2>&1';
    $h = @popen($cmd, 'r');
    if ($h) @pclose($h);
}
function bleHelperRequest($line, $data = '', $readTimeout = 20) {
    $fp = @fsockopen('127.0.0.1', BLE_HELPER_PORT, $en, $es, 0.3);
    if (!$fp) {
        bleHelperStart();
        $until = microtime(true) + 12;   // PowerShell + WinRT ໃຊ້ ~3 ວິ ກວ່າຈະເປີດ
        while (!$fp && microtime(true) < $until) {
            usleep(250000);
            $fp = @fsockopen('127.0.0.1', BLE_HELPER_PORT, $en, $es, 0.3);
        }
        if (!$fp) return null;
    }
    stream_set_timeout($fp, $readTimeout);
    $payload = $line . "\n" . $data;
    for ($off = 0; $off < strlen($payload); ) {
        $n = @fwrite($fp, substr($payload, $off, 65536));
        if ($n === false || $n === 0) { fclose($fp); return null; }
        $off += $n;
    }
    $reply = trim((string)fgets($fp));
    fclose($fp);
    return $reply === '' ? null : $reply;
}

if ($action === 'warm') {
    // ເປີດໜ້າຕ່າງຊຳລະເງິນ: ໃຫ້ໂຕຊ່ວຍເປີດ ແລະ ເຊື່ອມເຄື່ອງພິມໄວ້ກ່ອນ ບິນຈະອອກທັນທີ
    if ($name !== '' && bleHelperRequest('CHECK ' . base64_encode($name), '', 15) !== null) {
        echo json_encode(['status' => 'success']);
        exit;
    }
    $lockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'storemini_ble_print.lock';
    $lockFp = @fopen($lockFile, 'c+');
    if ($lockFp && @flock($lockFp, LOCK_EX | LOCK_NB)) {
        @flock($lockFp, LOCK_UN);
        @fclose($lockFp);
    }
    echo json_encode(['status' => 'success']);
    exit;
}

// ກວດສອບວ່າເຄື່ອງພິມເປີດຢູ່ ແລະ ເຊື່ອມຕໍ່ໄດ້ຫຼືບໍ່
if ($action === 'ping' || $action === 'check') {
    if ($name === '') {
        echo json_encode(['status' => 'error', 'message' => tl('ບໍ່ມີຊື່ເຄື່ອງພິມ')]);
        exit;
    }
    // ເຄື່ອງພິມໃໝ່ທີ່ຍັງບໍ່ມີໃນ ble_cache.json: ການຊອກອາດໃຊ້ເຖິງ 40 ວິ
    $reply = bleHelperRequest('CHECK ' . base64_encode($name), '', 50);
    if ($reply === 'ON' || $reply === 'OFF') {
        echo json_encode($reply === 'ON'
            ? ['status' => 'success', 'message' => tl('ເຊື່ອມຕໍ່ປິ່ນເຕີສຳເລັດ')]
            : ['status' => 'error', 'message' => (tl('ບໍ່ພົບເຄື່ອງພິມ') . ' — ' . tl('ກວດວ່າເປີດເຄື່ອງພິມ') . ' ') . $name . (' ' . tl('ຢູ່'))]);
        exit;
    }
    $ps1   = __DIR__ . DIRECTORY_SEPARATOR . 'ble_send.ps1';
    $cache = __DIR__ . DIRECTORY_SEPARATOR . 'ble_cache.json';
    $cmd = sprintf(
        'powershell -NoProfile -NonInteractive -ExecutionPolicy Bypass -File %s -WarmOnly -CacheFile %s -PrinterName %s 2>&1',
        escapeshellarg($ps1),
        escapeshellarg($cache),
        escapeshellarg($name)
    );
    $out = [];
    $code = 0;
    exec($cmd, $out, $code);
    $text_out = trim(implode("\n", $out));
    if ($code === 0 && strpos($text_out, 'OK') !== false) {
        echo json_encode(['status' => 'success', 'message' => tl('ເຊື່ອມຕໍ່ປິ່ນເຕີສຳເລັດ')]);
    } else {
        echo json_encode(['status' => 'error', 'message' => tl('ເຊື່ອມຕໍ່ປິ່ນເຕີບໍ່ສຳເລັດ ກະລຸນາລອງໃໝ່ອີກຄັ້ງ'), 'detail' => $text_out]);
    }
    exit;
}

if ($name === '' || $b64 === '') {
    echo json_encode(['status' => 'error', 'message' => tl('ຂໍ້ມູນບໍ່ຄົບ')]);
    exit;
}

$bytes = base64_decode($b64, true);
if ($bytes === false || strlen($bytes) === 0) {
    echo json_encode(['status' => 'error', 'message' => tl('ຂໍ້ມູນໃບບິນບໍ່ຖືກຕ້ອງ')]);
    exit;
}

// ທາງໄວ: ສົ່ງໃຫ້ໂຕຊ່ວຍທີ່ຖືການເຊື່ອມຕໍ່ໄວ້ (ໂຕຊ່ວຍຮັບເທື່ອລະບິນ ຈຶ່ງບໍ່ສົ່ງຊ້ອນກັນ)
$reply = bleHelperRequest('PRINT ' . base64_encode($name) . ' ' . strlen($bytes), $bytes, 90);
if ($reply !== null) {
    if ($reply === 'OK') {
        echo json_encode(['status' => 'success']);
    } else {
        $msg = (tl('ບໍ່ພົບເຄື່ອງພິມທີ່ເປີດຢູ່') . ' — ' . tl('ກະລຸນາເປີດເຄື່ອງພິມ') . ' ') . $name;
        if (strpos($reply, 'ERR_WRITE') === 0) {
            $msg = tl('ສົ່ງຂໍ້ມູນໄປເຄື່ອງພິມ Bluetooth ບໍ່ສຳເລັດ');
        } elseif (strpos($reply, 'TIMEOUT') !== false) {
            $msg = (tl('ເຄື່ອງພິມ Bluetooth ບໍ່ຕອບສະໜອງ') . ' — ' . tl('ກວດວ່າເປີດເຄື່ອງພິມຢູ່'));
        }
        echo json_encode(['status' => 'error', 'message' => $msg, 'detail' => $reply]);
    }
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
    $msg = (tl('ບໍ່ພົບເຄື່ອງພິມທີ່ເປີດຢູ່') . ' — ' . tl('ກະລຸນາເປີດເຄື່ອງພິມ') . ' ') . $name;
    if (strpos($text, 'ERR_TIMEOUT') !== false) {
        $msg = (tl('ເຄື່ອງພິມ Bluetooth ບໍ່ຕອບສະໜອງ') . ' — ' . tl('ກວດວ່າເປີດເຄື່ອງພິມຢູ່'));
    } elseif (strpos($text, 'ERR_WRITE') !== false) {
        $msg = tl('ສົ່ງຂໍ້ມູນໄປເຄື່ອງພິມ Bluetooth ບໍ່ສຳເລັດ');
    }
    echo json_encode(['status' => 'error', 'message' => $msg]);
}
