<?php
/*
 * ສ່ວນ: ສ້າງສຽງ Microsoft Edge Neural TTS ດ້ວຍ PHP ລ້ວນ (ແທນ api/edge_tts_speak.py + api/tts_daemon.py ທີ່ລຶບແລ້ວ)
 * ເຊື່ອມ WebSocket (wss://speech.platform.bing.com) ດ້ວຍ stream_socket_client ເອງ — ບໍ່ຕ້ອງມີ Python, Composer ຫຼື extension ເພີ່ມ.
 * ໂປຣໂຕຄໍແປງຈາກ edge_tts/communicate.py (edge-tts 7.3.1): ສົ່ງ speech.config → ssml, ຮັບສຽງ mp3 ເປັນ binary frame
 * (Path:audio) ຈົນຮອດ Path:turn.end. ລະຫັດ Sec-MS-GEC: api/tts/edge_token.php.
 * ໃຊ້ໂດຍ api/tts_speak.php: edgeTtsSynthesize($text, $voice, $destFile) → true/false.
 */
require_once __DIR__ . '/edge_token.php';

const EDGE_TTS_HOST = 'speech.platform.bing.com';
const EDGE_TTS_PATH = '/consumer/speech/synthesize/readaloud/edge/v1';
const EDGE_TTS_CHROMIUM = '143.0.3650.75';

// ໂມງເຄື່ອງຄາດຈາກເວລາ Microsoft (ວິນາທີ) — ເກັບໄວ້ ໃຫ້ຄຳຂໍຕໍ່ໄປໃຊ້ຄ່າທີ່ແກ້ແລ້ວ
function edgeTtsSkewFile() {
    return rtrim(sys_get_temp_dir(), '/\\') . '/storemini_edge_tts_skew.txt';
}

function edgeTtsUuid() {
    return bin2hex(random_bytes(16));
}

// ສົ່ງ text frame (client → server ຕ້ອງ mask ສະເໝີ)
function edgeTtsSendFrame($fp, $payload, $opcode = 0x1) {
    $len = strlen($payload);
    $head = chr(0x80 | $opcode);
    if ($len < 126) {
        $head .= chr(0x80 | $len);
    } elseif ($len < 65536) {
        $head .= chr(0x80 | 126) . pack('n', $len);
    } else {
        $head .= chr(0x80 | 127) . pack('J', $len);
    }
    $mask = random_bytes(4);
    $masked = '';
    for ($i = 0; $i < $len; $i++) {
        $masked .= $payload[$i] ^ $mask[$i % 4];
    }
    return @fwrite($fp, $head . $mask . $masked) !== false;
}

function edgeTtsReadExact($fp, $n) {
    $buf = '';
    while (strlen($buf) < $n) {
        $chunk = @fread($fp, $n - strlen($buf));
        if ($chunk === false || $chunk === '') {
            $meta = stream_get_meta_data($fp);
            if (!empty($meta['timed_out']) || feof($fp)) return null;
            continue;
        }
        $buf .= $chunk;
    }
    return $buf;
}

// ອ່ານ 1 frame → [opcode, fin, payload] ຫຼື null
function edgeTtsReadFrame($fp) {
    $h = edgeTtsReadExact($fp, 2);
    if ($h === null) return null;
    $b0 = ord($h[0]); $b1 = ord($h[1]);
    $len = $b1 & 0x7F;
    if ($len === 126) {
        $x = edgeTtsReadExact($fp, 2); if ($x === null) return null;
        $len = unpack('n', $x)[1];
    } elseif ($len === 127) {
        $x = edgeTtsReadExact($fp, 8); if ($x === null) return null;
        $len = unpack('J', $x)[1];
    }
    $mask = ($b1 & 0x80) ? edgeTtsReadExact($fp, 4) : '';
    $data = $len > 0 ? edgeTtsReadExact($fp, $len) : '';
    if ($data === null) return null;
    if ($mask !== '' && $mask !== null) {
        for ($i = 0; $i < $len; $i++) $data[$i] = $data[$i] ^ $mask[$i % 4];
    }
    return [$b0 & 0x0F, ($b0 & 0x80) !== 0, $data];
}

// "Path:audio\r\nContent-Type:..." → ['Path' => 'audio', ...]
function edgeTtsHeaders($raw) {
    $out = [];
    foreach (explode("\r\n", $raw) as $line) {
        $p = strpos($line, ':');
        if ($p !== false) $out[trim(substr($line, 0, $p), "\x00..\x1F ")] = trim(substr($line, $p + 1));
    }
    return $out;
}

/**
 * ສ້າງສຽງ 1 ເທື່ອ. ຄືນ true = ສຳເລັດ (ຂຽນ $destFile ແລ້ວ), ຫຼື ລະຫັດ HTTP ຂອງ handshake ທີ່ລົ້ມເຫຼວ (ເຊັ່ນ 403 = ລະຫັດ/ໂມງບໍ່ກົງ),
 * ຫຼື false. $serverDate ຮັບຄ່າ header Date ຂອງ Microsoft ເພື່ອແກ້ໂມງ.
 */
function edgeTtsOnce($text, $voice, $destFile, $skew, &$serverDate) {
    $cafile = ini_get('openssl.cafile') ?: ini_get('curl.cainfo');
    $ssl = ($cafile && is_file($cafile))
        ? ['verify_peer' => true, 'verify_peer_name' => true, 'cafile' => $cafile, 'SNI_enabled' => true, 'peer_name' => EDGE_TTS_HOST]
        : ['verify_peer' => false, 'verify_peer_name' => false, 'SNI_enabled' => true, 'peer_name' => EDGE_TTS_HOST];
    $fp = @stream_socket_client('ssl://' . EDGE_TTS_HOST . ':443', $errno, $errstr, 8, STREAM_CLIENT_CONNECT, stream_context_create(['ssl' => $ssl]));
    if (!$fp) return false;
    stream_set_timeout($fp, 15);

    $major = explode('.', EDGE_TTS_CHROMIUM)[0];
    $query = 'TrustedClientToken=' . EDGE_TTS_TRUSTED_CLIENT_TOKEN
           . '&ConnectionId=' . edgeTtsUuid()
           . '&Sec-MS-GEC=' . edgeTtsSecMsGec(microtime(true) + $skew)
           . '&Sec-MS-GEC-Version=2-' . EDGE_TTS_CHROMIUM;
    $req = "GET " . EDGE_TTS_PATH . "?{$query} HTTP/1.1\r\n"
         . "Host: " . EDGE_TTS_HOST . "\r\n"
         . "Upgrade: websocket\r\n"
         . "Connection: Upgrade\r\n"
         . "Sec-WebSocket-Key: " . base64_encode(random_bytes(16)) . "\r\n"
         . "Sec-WebSocket-Version: 13\r\n"
         . "Pragma: no-cache\r\n"
         . "Cache-Control: no-cache\r\n"
         . "Origin: chrome-extension://jdiccldimpdaibmpdkjnbmckianbfold\r\n"
         . "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/{$major}.0.0.0 Safari/537.36 Edg/{$major}.0.0.0\r\n"
         . "Accept-Language: en-US,en;q=0.9\r\n"
         . "Cookie: muid=" . strtoupper(bin2hex(random_bytes(16))) . ";\r\n\r\n";
    if (@fwrite($fp, $req) === false) { fclose($fp); return false; }

    // ຄຳຕອບ handshake (101 Switching Protocols)
    $resp = '';
    while (strpos($resp, "\r\n\r\n") === false) {
        $line = @fgets($fp, 4096);
        if ($line === false) { fclose($fp); return false; }
        $resp .= $line;
        if (strlen($resp) > 16384) break;
    }
    if (preg_match('/^Date:\s*(.+)$/mi', $resp, $dm)) $serverDate = trim($dm[1]);
    if (!preg_match('#^HTTP/1\.\d\s+(\d{3})#', $resp, $sm) || $sm[1] !== '101') {
        fclose($fp);
        return isset($sm[1]) ? (int)$sm[1] : false;
    }

    $ts = gmdate('D M d Y H:i:s') . ' GMT+0000 (Coordinated Universal Time)';
    $config = "X-Timestamp:{$ts}\r\nContent-Type:application/json; charset=utf-8\r\nPath:speech.config\r\n\r\n"
            . '{"context":{"synthesis":{"audio":{"metadataoptions":{"sentenceBoundaryEnabled":"true","wordBoundaryEnabled":"false"},'
            . '"outputFormat":"audio-24khz-48kbitrate-mono-mp3"}}}}' . "\r\n";
    // ຕົວອັກສອນຄວບຄຸມທີ່ບໍລິການບໍ່ຮັບ → ຍະຫວ່າງ; escape XML ຄື xml.sax.saxutils.escape
    $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', ' ', $text);
    $escaped = str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $clean);
    $voiceFull = $voice;
    if (preg_match('/^([a-z]{2,})-([A-Z]{2,})-(.+Neural)$/', $voice, $vm)) {
        $voiceFull = "Microsoft Server Speech Text to Speech Voice ({$vm[1]}-{$vm[2]}, {$vm[3]})";
    }
    $ssml = "<speak version='1.0' xmlns='http://www.w3.org/2001/10/synthesis' xml:lang='en-US'>"
          . "<voice name='{$voiceFull}'><prosody pitch='+0Hz' rate='+0%' volume='+0%'>{$escaped}</prosody></voice></speak>";
    $ssmlMsg = "X-RequestId:" . edgeTtsUuid() . "\r\nContent-Type:application/ssml+xml\r\nX-Timestamp:{$ts}Z\r\nPath:ssml\r\n\r\n" . $ssml;
    if (!edgeTtsSendFrame($fp, $config) || !edgeTtsSendFrame($fp, $ssmlMsg)) { fclose($fp); return false; }

    $audio = '';
    $done = false;
    $deadline = microtime(true) + 20;
    $msgOp = 0; $msgBuf = '';
    while (!$done && microtime(true) < $deadline) {
        $frame = edgeTtsReadFrame($fp);
        if ($frame === null) break;
        [$op, $fin, $data] = $frame;
        if ($op === 0x9) { edgeTtsSendFrame($fp, $data, 0xA); continue; }   // ping → pong
        if ($op === 0x8) break;                                             // ປິດການເຊື່ອມຕໍ່
        if ($op === 0x1 || $op === 0x2) { $msgOp = $op; $msgBuf = $data; }
        elseif ($op === 0x0) { $msgBuf .= $data; }
        else continue;
        if (!$fin) continue;

        if ($msgOp === 0x1) {
            $p = strpos($msgBuf, "\r\n\r\n");
            $hdr = edgeTtsHeaders($p === false ? $msgBuf : substr($msgBuf, 0, $p));
            if (($hdr['Path'] ?? '') === 'turn.end') $done = true;
        } elseif ($msgOp === 0x2 && strlen($msgBuf) >= 2) {
            $hl = unpack('n', substr($msgBuf, 0, 2))[1];
            if ($hl > strlen($msgBuf)) continue;
            $hdr = edgeTtsHeaders(substr($msgBuf, 0, $hl));
            if (($hdr['Path'] ?? '') === 'audio' && ($hdr['Content-Type'] ?? '') === 'audio/mpeg') {
                $audio .= substr($msgBuf, $hl + 2);
            }
        }
    }
    fclose($fp);
    if (!$done || $audio === '') return false;

    $tmp = $destFile . '.part';
    if (@file_put_contents($tmp, $audio) === false) return false;
    if (!@rename($tmp, $destFile)) {
        @unlink($destFile);
        if (!@rename($tmp, $destFile)) { @unlink($tmp); return false; }
    }
    clearstatcache(true, $destFile);
    return filesize($destFile) > 0;
}

/**
 * ສ້າງສຽງພາສາລາວໃສ່ $destFile (mp3). ໂມງເຄື່ອງບໍ່ກົງກັບ Microsoft (403) → ແກ້ໂມງຕາມ header Date ແລ້ວລອງໃໝ່ເທື່ອດຽວ.
 */
function edgeTtsSynthesize($text, $voice, $destFile) {
    $text = trim((string)$text);
    if ($text === '') return false;
    if (!$voice || strpos($voice, 'lo-LA-') !== 0) $voice = 'lo-LA-KeomanyNeural';
    $skew = (float)@file_get_contents(edgeTtsSkewFile());
    for ($attempt = 0; $attempt < 2; $attempt++) {
        $serverDate = null;
        $r = edgeTtsOnce($text, $voice, $destFile, $skew, $serverDate);
        if ($r === true) return true;
        if ($r === 403 && $serverDate && ($srv = strtotime($serverDate)) !== false) {
            $skew = $srv - microtime(true);
            @file_put_contents(edgeTtsSkewFile(), (string)$skew);
            continue;
        }
        return false;
    }
    return false;
}
