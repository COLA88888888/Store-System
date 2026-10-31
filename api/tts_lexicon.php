<?php
/**
 * api/tts_lexicon.php — ຈັດການວັດຈະນານຸກົມການອອກສຽງ (Lexicon) ຂອງ Siangmuan.com
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// ແກ້ວັດຈະນານຸກົມ = ສິດ "ຕັ້ງຄ່າລະບົບສຽງ" (ດຽວກັບໜ້າ pages/settings/tts/)
require_once __DIR__ . '/../config/db.php';
if (empty($_SESSION['user_id']) || !hasPermission('tts')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ທ່ານບໍ່ມີສິດຕັ້ງຄ່າລະບົບສຽງ'], JSON_UNESCAPED_UNICODE);
    exit();
}

$configFile = __DIR__ . '/../config/tts_config.php';
$config = file_exists($configFile) ? require $configFile : [];

$apiKey = trim($config['api_key'] ?? '');
if ($apiKey === '' || $apiKey === 'YOUR_API_KEY') {
    require_once __DIR__ . '/../config/db.php';
    if (function_exists('getSetting')) {
        $dbKey = getSetting($pdo, 'tts_api_key', '');
        if ($dbKey !== '') $apiKey = $dbKey;
    }
}

if ($apiKey === '' || $apiKey === 'YOUR_API_KEY') {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'ກະລຸນາຕັ້ງຄ່າ API Key ໃນ config/tts_config.php ກ່ອນ'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$action = $input['action'] ?? 'add_word';

// ຟັງຊັນຍິງ PUT ໄປຫາ siangmuan.com/lexicon
function sendLexiconPut($apiKey, $word, $pronunciation) {
    $ch = curl_init("https://siangmuan.com/lexicon");
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => 'PUT',
        CURLOPT_HTTPHEADER     => [
            "Content-Type: application/json",
            "Authorization: Bearer " . $apiKey,
            "X-API-Key: " . $apiKey
        ],
        CURLOPT_POSTFIELDS     => json_encode([
            'word'          => trim($word),
            'pronunciation' => trim($pronunciation)
        ], JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    return [
        'http_code' => $httpCode,
        'response'  => json_decode($res, true) ?: $res,
        'error'     => $err
    ];
}

$localFile = __DIR__ . '/../config/tts_lexicon.json';
function getLocalLexicon($file) {
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if (is_array($data)) return $data;
    }
    return [];
}
function saveLocalLexicon($file, $data) {
    file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

// 0. ດຶງລາຍການຄຳສັບທັງໝົດ
if ($action === 'get_words') {
    echo json_encode([
        'success' => true,
        'lexicon' => getLocalLexicon($localFile)
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// 1. ເພີ່ມ/ແກ້ໄຂຄຳສັບດຽວ
if ($action === 'add_word') {
    $word = trim($input['word'] ?? '');
    $pronunciation = trim($input['pronunciation'] ?? '');

    if ($word === '' || $pronunciation === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ກະລຸນາປ້ອນທັງຄຳສັບ ແລະ ຄຳອ່ານ']);
        exit();
    }

    // ບັນທຶກລົງ local lexicon file ທຸກຄັ້ງ
    $lexData = getLocalLexicon($localFile);
    $lexData[$word] = $pronunciation;
    saveLocalLexicon($localFile, $lexData);

    // ລອງສົ່ງໄປ Siangmuan API ນຳ
    $res = sendLexiconPut($apiKey, $word, $pronunciation);

    echo json_encode([
        'success' => true,
        'message' => "ບັນທຶກຄຳອ່ານ \"$word\" -> \"$pronunciation\" ສຳເລັດ!",
        'remote_status' => $res['http_code']
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// 2. ລຶບຄຳສັບ
if ($action === 'delete_word') {
    $word = trim($input['word'] ?? '');
    $lexData = getLocalLexicon($localFile);
    if (isset($lexData[$word])) {
        unset($lexData[$word]);
        saveLocalLexicon($localFile, $lexData);
    }
    echo json_encode(['success' => true, 'message' => "ລຶບ \"$word\" ສຳເລັດ!"], JSON_UNESCAPED_UNICODE);
    exit();
}

// 3. ເພີ່ມຊຸດຄຳສັບແນະນຳ (Presets / Batch Add)
if ($action === 'batch_add') {
    $words = $input['words'] ?? [];
    if (!is_array($words) || empty($words)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No words provided']);
        exit();
    }

    $lexData = getLocalLexicon($localFile);
    $count = 0;
    foreach ($words as $item) {
        $w = trim($item['word'] ?? '');
        $p = trim($item['pronunciation'] ?? ($item['pron'] ?? ''));
        if ($w !== '' && $p !== '') {
            $lexData[$w] = $p;
            $count++;
            // ຍັງສາມາດຍິງໄປ server ໄດ້
            @sendLexiconPut($apiKey, $w, $p);
        }
    }
    saveLocalLexicon($localFile, $lexData);

    echo json_encode([
        'success' => true,
        'message' => "ບັນທຶກຄຳສັບ $count ຄຳ ເຂົ້າສູ່ລະບົບສຳເລັດແລ້ວ!",
        'lexicon' => $lexData
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action']);
