<?php
// lang/set_lang.php - AJAX endpoint to persist the user's chosen UI language.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/translator.php';

header('Content-Type: application/json');

$lang = $_POST['lang'] ?? $_GET['lang'] ?? '';

if (!in_array($lang, POS_SUPPORTED_LANGS, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unsupported language']);
    exit();
}

$_SESSION['lang'] = $lang;
setcookie('minipos_lang', $lang, [
    'expires' => time() + 31536000,
    'path' => '/',
    'httponly' => false,
    'samesite' => 'Lax',
]);

echo json_encode(['success' => true, 'lang' => $lang]);
