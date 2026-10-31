<?php
/**
 * TTS Proxy Service ສຳລັບລະບົບສຽງເວົ້າ StoreMini
 * ຮອງຮັບ Microsoft Edge Neural TTS (ຟຣີ 100% ບໍ່ຈຳກັດ) ແລະ Siangmuan.com API
 * ພ້ອມລະບົບ Smart Fallback ແລະ Caching ໄຟລ໌ສຽງໃນເຄື່ອງ
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$configFile = __DIR__ . '/../config/tts_config.php';
$config = file_exists($configFile) ? require $configFile : [];

// ສະວິດເປີດ/ປິດສຽງ (ຕັ້ງຄ່າ › ລະບົບສຽງ). ບໍ່ມີຄ່າ = ເປີດ (ຄືເກົ່າ)
$ttsSwitches = ['enabled' => !array_key_exists('enabled', $config) || !empty($config['enabled'])];
foreach (['pos_total', 'pos_sale', 'pos_stock', 'pos_queue', 'pos_welcome'] as $__k) {
    $ttsSwitches[$__k] = !array_key_exists($__k, $config) || !empty($config[$__k]);
}
// ?config=1: ໜ້າຂາຍ/ຈໍລູກຄ້າ ອ່ານສະວິດຄືນທຸກ 10 ວິນາທີ (assets/js/tts-speak.js)
if (isset($_GET['config'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($ttsSwitches);
    exit();
}
// ປິດສຽງ → ບໍ່ສ້າງສຽງ (ບໍ່ເຊື່ອມ Microsoft ບໍ່ປຸກເຄື່ອງສ້າງສຽງ). ປຸ່ມທົດສອບໃນໜ້າຕັ້ງຄ່າ (?test=1) ຍັງໃຊ້ໄດ້
if (!$ttsSwitches['enabled'] && !isset($_GET['test'])) {
    http_response_code(204);
    exit();
}

$provider = trim($_GET['provider'] ?? ($config['provider'] ?? 'edge'));
$apiKey   = trim($config['api_key'] ?? '');
$apiUrl   = trim($config['api_url'] ?? 'https://siangmuan.com/tts');
$format   = trim($config['format'] ?? 'mp3');
$cacheDir = $config['cache_dir'] ?? (__DIR__ . '/../assets/audio_cache/');

// ຖ້າບໍ່ທັນມີ API key ໃນ config, ກວດສອບຈາກ st_system_settings
if ($apiKey === '' || $apiKey === 'YOUR_API_KEY') {
    require_once __DIR__ . '/../config/db.php';
    if (function_exists('getSetting')) {
        $dbKey = getSetting($pdo, 'tts_api_key', '');
        if ($dbKey !== '') $apiKey = $dbKey;
    }
}

// ບໍ່ຖື session ໄວ້ລະຫວ່າງສ້າງສຽງ — ບໍ່ດັ່ງນັ້ນຄຳຂໍອື່ນຂອງໜ້າຂາຍ (ກວດສະຕັອກ, ກະ...) ຕ້ອງລໍຈົນສຽງແລ້ວ
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// ?warm=1 (assets/js/tts-speak.js ຕອນເປີດໜ້າຂາຍ/ຈໍລູກຄ້າ): ປຸກເຄື່ອງສ້າງສຽງ (api/tts_daemon.py) ໄວ້ລ່ວງໜ້າ —
// ສຽງທຳອິດຂອງມື້ບໍ່ຕ້ອງລໍເປີດ Python. ຕອບທັນທີ ບໍ່ສ້າງສຽງ.
if (isset($_GET['warm'])) {
    $warmFp = @fsockopen('127.0.0.1', 5599, $wErr, $wStr, 0.3);
    if ($warmFp) {
        fclose($warmFp);
    } elseif (stripos(PHP_OS, 'WIN') === 0 && file_exists(__DIR__ . '/tts_daemon.py')) {
        $warmPy = 'C:\\Users\\rog\\AppData\\Local\\Programs\\Python\\Python313\\pythonw.exe';
        if (!file_exists($warmPy)) $warmPy = 'pythonw';
        if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
        @pclose(@popen('start "" /B "' . $warmPy . '" "' . __DIR__ . '/tts_daemon.py" "' . rtrim(realpath($cacheDir) ?: $cacheDir, '/\\') . '" 5599', 'r'));
    }
    http_response_code(204);
    exit();
}

$text  = trim($_GET['text'] ?? $_POST['text'] ?? '');
$voice = trim($_GET['voice'] ?? $_POST['voice'] ?? ($config['voice'] ?? ''));

if ($text === '') {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Missing text parameter']);
    exit();
}

// ຕັດຄວາມຍາວບໍ່ໃຫ້ເກີນ 500 ຕົວອັກສອນຕໍ່ຄັ້ງ ເພື່ອຄວາມປອດໄພ
if (mb_strlen($text) > 500) {
    $text = mb_substr($text, 0, 500);
}

// ແປງຫົວໜ່ວຍສິນຄ້າທີ່ພົບເລື້ອຍໆ ໃຫ້ອ່ານອອກສຽງພາສາລາວຊັດເຈນ:
// ml -> ເອັມແອວ (ເຊັ່ນ: 330ml -> 330 ເອັມແອວ)
$text = preg_replace('/(\d+(?:\.\d+)?)\s*(ml|mL|ML)\b/u', '$1 ເອັມແອວ', $text);
$text = preg_replace('/\b(ml|mL|ML)\b/u', 'ເອັມແອວ', $text);

// kg -> ກິໂລ (ເຊັ່ນ: 5kg -> 5 ກິໂລ)
$text = preg_replace('/(\d+(?:\.\d+)?)\s*(kg|Kg|KG)\b/u', '$1 ກິໂລ', $text);
$text = preg_replace('/\b(kg|Kg|KG)\b/u', 'ກິໂລ', $text);

// cc -> ຊີຊີ
$text = preg_replace('/(\d+(?:\.\d+)?)\s*(cc|CC)\b/u', '$1 ຊີຊີ', $text);

// L/l ທີ່ຕິດກັບຕົວເລກ (ເຊັ່ນ 1.5L -> 1.5 ລິດ)
$text = preg_replace('/(\d+(?:\.\d+)?)\s*(l|L)\b/u', '$1 ລິດ', $text);

// ແທນທີ່ຄຳສັບຕາມວັດຈະນານຸກົມທ້ອງຖິ່ນ (Local Lexicon Substitution) ເຊັ່ນ: StoreMini -> ສະໂຕ ມິນິ, BCEL -> ບີຊີອີແອວ
$lexiconFile = __DIR__ . '/../config/tts_lexicon.json';
if (file_exists($lexiconFile)) {
    $lexicon = json_decode(file_get_contents($lexiconFile), true);
    if (is_array($lexicon)) {
        // ຮຽງລຳດັບຄຳສັບຈາກຍາວໄປຫາສັ້ນ ເພື່ອໃຫ້ແທນທີ່ຄຳປະສົມກ່ອນ (ເຊັ່ນ: StoreMini ກ່ອນ Store)
        uksort($lexicon, function($a, $b) {
            return mb_strlen($b) - mb_strlen($a);
        });
        foreach ($lexicon as $word => $pron) {
            if ($word !== '' && $pron !== '') {
                $text = preg_replace('/\b' . preg_quote($word, '/') . '\b/iu', $pron, $text);
            }
        }
    }
}

/**
 * ແປງຕົວເລກເປັນຄຳອ່ານພາສາລາວທຳມະຊາດ (ເຊັ່ນ: 15,000 -> ສິບຫ້າພັນ, 20,000 -> ຊາວພັນ, 100,000 -> ໜຶ່ງແສນ)
 * ປ້ອງກັນບໍ່ໃຫ້ລະບົບສຽງອ່ານຕົວເລກເປັນ "ສູນໆໆ"
 */
function laoNumberToWords($number) {
    $numStr = (string)$number;
    $numStr = preg_replace('/[^\d.]/', '', $numStr);
    if ($numStr === '' || !is_numeric($numStr)) return '';

    $parts = explode('.', $numStr);
    $intPart = ltrim($parts[0], '0');
    if ($intPart === '') $intPart = '0';

    if ($intPart === '0') {
        $result = 'ສູນ';
    } else {
        $result = convertLaoInteger($intPart);
    }

    if (isset($parts[1]) && strlen($parts[1]) > 0 && intval($parts[1]) > 0) {
        $decPart = $parts[1];
        $result .= ' ຈຸດ';
        $digits = ['0'=>'ສູນ','1'=>'ໜຶ່ງ','2'=>'ສອງ','3'=>'ສາມ','4'=>'ສີ່','5'=>'ຫ້າ','6'=>'ຫົກ','7'=>'ເຈັດ','8'=>'ແປດ','9'=>'ເກົ້າ'];
        for ($i = 0; $i < strlen($decPart); $i++) {
            $result .= ' ' . ($digits[$decPart[$i]] ?? $decPart[$i]);
        }
    }

    return trim($result);
}

function convertLaoInteger($numStr) {
    $digits = ['', 'ໜຶ່ງ', 'ສອງ', 'ສາມ', 'ສີ່', 'ຫ້າ', 'ຫົກ', 'ເຈັດ', 'ແປດ', 'ເກົ້າ'];
    $len = strlen($numStr);
    if ($len === 0) return '';

    if ($len > 6) {
        $millions = substr($numStr, 0, $len - 6);
        $remainder = substr($numStr, -6);
        $mWords = convertLaoInteger($millions) . 'ລ້ານ';
        $rWords = convertLaoInteger(ltrim($remainder, '0'));
        return $mWords . $rWords;
    }

    if ($len == 6) {
        $firstDigit = intval($numStr[0]);
        $rest = substr($numStr, 1);
        $w = $digits[$firstDigit] . 'ແສນ';
        $restNum = ltrim($rest, '0');
        if ($restNum !== '') {
            $w .= convertLaoInteger($restNum);
        }
        return $w;
    }

    if ($len == 5) {
        $thousandsPart = substr($numStr, 0, 2);
        $rest = substr($numStr, 2);
        $thWords = convertUnderHundred(intval($thousandsPart)) . 'ພັນ';
        $restWords = convertUnderThousand($rest);
        return $thWords . $restWords;
    }

    if ($len == 4) {
        $firstDigit = intval($numStr[0]);
        $rest = substr($numStr, 1);
        $w = $digits[$firstDigit] . 'ພັນ';
        $restWords = convertUnderThousand($rest);
        return $w . $restWords;
    }

    if ($len == 3) {
        return convertUnderThousand($numStr);
    }

    return convertUnderHundred(intval($numStr));
}

function convertUnderThousand($numStr) {
    $numStr = str_pad($numStr, 3, '0', STR_PAD_LEFT);
    $digits = ['', 'ໜຶ່ງ', 'ສອງ', 'ສາມ', 'ສີ່', 'ຫ້າ', 'ຫົກ', 'ເຈັດ', 'ແປດ', 'ເກົ້າ'];
    $h = intval($numStr[0]);
    $res = '';
    if ($h > 0) {
        $res .= $digits[$h] . 'ຮ້ອຍ';
    }
    $sub = intval(substr($numStr, 1));
    if ($sub > 0) {
        $res .= convertUnderHundred($sub);
    }
    return $res;
}

function convertUnderHundred($n) {
    $digits = ['', 'ໜຶ່ງ', 'ສອງ', 'ສາມ', 'ສີ່', 'ຫ້າ', 'ຫົກ', 'ເຈັດ', 'ແປດ', 'ເກົ້າ'];
    if ($n <= 0) return '';
    if ($n < 10) return $digits[$n];
    
    $tens = intval($n / 10);
    $units = $n % 10;
    
    $res = '';
    if ($tens == 1) {
        $res = 'ສິບ';
    } else if ($tens == 2) {
        $res = 'ຊາວ';
    } else if ($tens > 2) {
        $res = $digits[$tens] . 'ສິບ';
    }
    
    if ($units == 1) {
        $res .= 'ເອັດ';
    } else if ($units > 1) {
        $res .= $digits[$units];
    }
    
    return $res;
}

function replaceNumbersWithLaoWords($text) {
    return preg_replace_callback('/(?<![A-Za-z0-9])(\d{1,3}(?:,\d{3})+(?:\.\d+)?|\d+(?:\.\d+)?)(?![A-Za-z0-9])/u', function($matches) {
        $clean = str_replace(',', '', $matches[0]);
        if (is_numeric($clean)) {
            $words = laoNumberToWords($clean);
            return $words !== '' ? ' ' . $words . ' ' : $matches[0];
        }
        return $matches[0];
    }, $text);
}

// ແປງຕົວເລກທັງໝົດໃນຂໍ້ຄວາມເປັນຄຳອ່ານລາວ
$text = replaceNumbersWithLaoWords($text);
$text = preg_replace('/\s+/', ' ', trim($text));

// ສ້າງໂຟນເດີ Cache ຖ້າຍັງບໍ່ມີ
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}

// ຊື່ໄຟລ໌ Cache ຕາມ MD5 ຂອງຂໍ້ຄວາມ, ສຽງ ແລະ Provider
$cacheHash = md5($provider . '_' . $text . '_' . $voice . '_' . $format);
$cacheFile = rtrim($cacheDir, '/\\') . '/' . $cacheHash . '.' . $format;

// 1. ກວດສອບ Cache ກ່ອນ (ຖ້າມີແລ້ວ ສົ່ງອອກທັນທີ 0 millisecond)
if (file_exists($cacheFile) && filesize($cacheFile) > 0) {
    header('Content-Type: audio/mpeg');
    header('Content-Length: ' . filesize($cacheFile));
    header('Cache-Control: public, max-age=604800'); // Cache 7 ມື້ໃນ Browser
    header('X-TTS-Source: cache');
    readfile($cacheFile);
    exit();
}

if (!defined('TTS_DAEMON_PORT')) define('TTS_DAEMON_PORT', 5599);

function ttsPythonBin($windowless = false) {
    $customPython = 'C:\\Users\\rog\\AppData\\Local\\Programs\\Python\\Python313\\python.exe';
    if (file_exists($customPython)) {
        $w = dirname($customPython) . '\\pythonw.exe';
        return ($windowless && file_exists($w)) ? $w : $customPython;
    }
    return $windowless ? 'pythonw' : 'python';
}

/**
 * ສ້າງສຽງຜ່ານເຄື່ອງສ້າງສຽງທີ່ເປີດຄ້າງໄວ້ (api/tts_daemon.py) — ~0.9 s ແທນ ~5 s ຂອງການເປີດ Python ໃໝ່.
 * ຄືນ true = ສຳເລັດ, false = ສ້າງບໍ່ໄດ້, null = ເຄື່ອງສ້າງສຽງບໍ່ໄດ້ເປີດ (ຈະເປີດໃຫ້ ແລ້ວໃຊ້ວິທີເກົ່າເທື່ອນີ້)
 */
function callEdgeDaemon($text, $voice, $destFile) {
    $fp = @fsockopen('127.0.0.1', TTS_DAEMON_PORT, $errno, $errstr, 0.3);
    if (!$fp) {
        return null;
    }
    stream_set_timeout($fp, 25);
    fwrite($fp, json_encode(['text' => $text, 'voice' => $voice, 'file' => basename($destFile)], JSON_UNESCAPED_UNICODE) . "\n");
    $reply = trim((string)fgets($fp));
    fclose($fp);
    clearstatcache(true, $destFile);
    return $reply === 'OK' && file_exists($destFile) && filesize($destFile) > 0;
}

function startEdgeDaemon($cacheDir) {
    $script = __DIR__ . '/tts_daemon.py';
    if (!file_exists($script) || stripos(PHP_OS, 'WIN') !== 0) {
        return;
    }
    // ເປີດແຍກອອກຈາກ request (start /B) ໂດຍບໍ່ມີໜ້າຕ່າງ — ເປີດຊ້ຳ ໂຕໃໝ່ຈະອອກເອງ (port ຖືກໃຊ້ແລ້ວ)
    $cmd = 'start "" /B "' . ttsPythonBin(true) . '" "' . $script . '" "' . rtrim(realpath($cacheDir) ?: $cacheDir, '/\\') . '" ' . TTS_DAEMON_PORT;
    @pclose(@popen($cmd, 'r'));
}

/**
 * ຟັງຊັນສ້າງສຽງຜ່ານ Microsoft Edge Neural TTS (Python edge-tts)
 */
function callEdgeTTS($text, $voice, $destFile) {
    global $cacheDir;
    $selectedVoice = $voice;
    if (empty($selectedVoice) || strpos($selectedVoice, 'lo-LA-') !== 0) {
        $selectedVoice = 'lo-LA-KeomanyNeural';
    }

    // 1. ເຄື່ອງສ້າງສຽງທີ່ເປີດຄ້າງໄວ້ (ໄວ)
    $viaDaemon = callEdgeDaemon($text, $selectedVoice, $destFile);
    if ($viaDaemon === true) {
        return true;
    }
    if ($viaDaemon === null) {
        startEdgeDaemon($cacheDir);   // ຂໍ້ຄວາມຕໍ່ໄປຈະໄວ
    }

    // 2. ວິທີເກົ່າ: ເປີດ Python ໃໝ່ສຳລັບຂໍ້ຄວາມນີ້
    $script = __DIR__ . '/edge_tts_speak.py';
    if (!file_exists($script)) {
        return false;
    }
    $cmd = '"' . ttsPythonBin() . '" "' . $script . '" ' . escapeshellarg(base64_encode($text)) . ' ' . escapeshellarg($destFile) . ' ' . escapeshellarg($selectedVoice);
    @exec($cmd, $output, $returnCode);
    clearstatcache(true, $destFile);
    return ($returnCode === 0 && file_exists($destFile) && filesize($destFile) > 0);
}

// 2. ຖ້າເລືອກ Provider ເປັນ Microsoft Edge (ຟຣີ 100% ບໍ່ຈຳກັດ)
if ($provider === 'edge') {
    if (callEdgeTTS($text, $voice, $cacheFile)) {
        header('Content-Type: audio/mpeg');
        header('Content-Length: ' . filesize($cacheFile));
        header('Cache-Control: public, max-age=604800');
        header('X-TTS-Provider: edge-neural');
        readfile($cacheFile);
        exit();
    }
}

// 3. ຖ້າເລືອກ Provider ເປັນ Siangmuan (ຫຼື ຖ້າ Edge ບໍ່ໄດ້ຕິດຕັ້ງ)
if ($provider === 'siangmuan') {
    if ($apiKey !== '' && $apiKey !== 'YOUR_API_KEY') {
        $payload = ["text" => $text, "format" => $format];
        if ($voice !== '' && strpos($voice, 'lo-LA-') === false) {
            $payload['voice'] = $voice;
        }

        $respHeaders = [];
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                "Content-Type: application/json",
                "Authorization: Bearer " . $apiKey,
                "X-API-Key: " . $apiKey
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADERFUNCTION => function($c, $h) use (&$respHeaders) {
                $parts = explode(':', $h, 2);
                if (count($parts) === 2) {
                    $respHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return strlen($h);
            },
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
            CURLOPT_TCP_NODELAY    => 1,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $audio = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && !empty($audio)) {
            @file_put_contents($cacheFile, $audio);
            if (isset($respHeaders['x-quota-remaining'])) {
                @file_put_contents(__DIR__ . '/../config/tts_quota.json', json_encode([
                    'remaining'  => intval($respHeaders['x-quota-remaining']),
                    'updated_at' => date('Y-m-d H:i:s')
                ]));
            }

            header('Content-Type: audio/mpeg');
            header('Content-Length: ' . strlen($audio));
            header('Cache-Control: public, max-age=604800');
            header('X-TTS-Provider: siangmuan');
            echo $audio;
            exit();
        }
    }

    // 4. SMART FALLBACK: ຖ້າ Siangmuan ຕິດ 429 (ໂຄຕ້າໝົດ), 401 ຫຼື ບໍ່ມີ Key -> ສະຫຼັບໄປໃຊ້ Edge Neural TTS ອັດຕະໂນມັດທັນທີ!
    if (callEdgeTTS($text, 'lo-LA-KeomanyNeural', $cacheFile)) {
        header('Content-Type: audio/mpeg');
        header('Content-Length: ' . filesize($cacheFile));
        header('Cache-Control: public, max-age=604800');
        header('X-TTS-Provider: edge-fallback');
        readfile($cacheFile);
        exit();
    }
}

// 5. ຖ້າ Edge ຍັງບໍ່ໄດ້ລອງ (ເຊັ່ນ: provider ບໍ່ຖືກຕ້ອງ ຫຼື Siangmuan ບໍ່ມີ key)
if (callEdgeTTS($text, $voice, $cacheFile)) {
    header('Content-Type: audio/mpeg');
    header('Content-Length: ' . filesize($cacheFile));
    header('Cache-Control: public, max-age=604800');
    header('X-TTS-Provider: edge-neural');
    readfile($cacheFile);
    exit();
}

// 6. ຖ້າບໍ່ສາມາດຫຼິ້ນໄດ້ເລີຍ ສົ່ງ Error JSON
http_response_code(500);
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => false,
    'message' => 'ບໍ່ສາມາດສ້າງສຽງເວົ້າໄດ້ (ທັງ Siangmuan ແລະ Edge TTS ບໍ່ພ້ອມໃຊ້ງານ)',
    'hint'    => 'ກວດສອບການຕິດຕັ້ງ python ແລະ edge-tts'
], JSON_UNESCAPED_UNICODE);
