<?php
/**
 * api/tts_voices.php — ດຶງລາຍຊື່ສຽງທັງໝົດຕາມ Provider (Edge Neural TTS ຫຼື Siangmuan.com)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$configFile = __DIR__ . '/../config/tts_config.php';
$config = file_exists($configFile) ? require $configFile : [];

$provider = trim($_GET['provider'] ?? ($config['provider'] ?? 'edge'));

// 1. ຖ້າເປັນ Microsoft Edge Neural TTS (ຟຣີ 100%)
if ($provider === 'edge') {
    echo json_encode([
        'success'  => true,
        'provider' => 'edge',
        'voices'   => [
            [
                'id'     => 'lo-LA-KeomanyNeural',
                'name'   => 'lo-LA-KeomanyNeural',
                'label'  => 'Keomany (ເກສມະນີ - ສຽງຍິງ, ສຽງຫວານ ຊັດເຈນ)',
                'gender' => 'female',
                'locked' => false
            ],
            [
                'id'     => 'lo-LA-ChanthavongNeural',
                'name'   => 'lo-LA-ChanthavongNeural',
                'label'  => 'Chanthavong (ຈັນທະວົງ - ສຽງຊາຍ, ສຽງທຸ້ມ ນຸ່ມນວນ)',
                'gender' => 'male',
                'locked' => false
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// 2. ຖ້າເປັນ Siangmuan.com
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
        'message' => 'ກະລຸນາຕັ້ງຄ່າ API Key ຂອງ Siangmuan ກ່ອນ'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$ch = curl_init("https://siangmuan.com/voices");
curl_setopt_array($ch, [
    CURLOPT_HTTPGET        => true,
    CURLOPT_HTTPHEADER     => [
        "Authorization: Bearer " . $apiKey,
        "X-API-Key: " . $apiKey
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($httpCode === 200 && !empty($response)) {
    $data = json_decode($response, true);
    echo json_encode([
        'success'  => true,
        'provider' => 'siangmuan',
        'voices'   => $data
    ], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code($httpCode >= 400 ? $httpCode : 500);
    echo json_encode([
        'success'   => false,
        'http_code' => $httpCode,
        'error'     => $curlErr ?: $response
    ], JSON_UNESCAPED_UNICODE);
}
