<?php
/**
 * ສະແດງຮູບສະລິບໂອນເງິນຂອງອໍເດີເວັບໄຊ ໃຫ້ພະນັກງານ (GET: id = w_web_orders.id) — ສິດ sale ຫຼື customers ຄືກັບ index.php.
 * ໂຟນເດີ backend/assets/web_slips/ ຫ້າມເປີດໂດຍກົງ (.htaccess) ເພາະສະລິບມີຂໍ້ມູນບັນຊີຂອງລູກຄ້າ. ໃຊ້ໃນ partials/rows.php.
 */
session_start();
require_once __DIR__ . '/../../config/db.php';
if (empty($_SESSION['user_id']) || (!hasPermission('sale') && !hasPermission('customers'))) {
    http_response_code(403);
    exit;
}
$col = ($_GET['type'] ?? '') === 'receipt' ? 'receipt_img' : 'slip_img';   // type=receipt → ບິນຮັບເຄື່ອງ
$st = $pdo->prepare("SELECT $col FROM w_web_orders WHERE id = ?");
$st->execute([(int)($_GET['id'] ?? 0)]);
$rel = (string)$st->fetchColumn();
$file = $rel !== '' ? dirname(__DIR__, 3) . '/' . ltrim($rel, '/') : '';
$okDir = $col === 'receipt_img' ? '/backend/assets/web_receipts/' : '/backend/assets/web_slips/';   // ແຕ່ລະຊະນິດ ແຕ່ລະໂຟນເດີ
if ($file === '' || !is_file($file) || !str_contains(str_replace('\\', '/', realpath($file)), $okDir)) {
    http_response_code(404);
    exit;
}
@ini_set('zlib.output_compression', '0');
session_write_close();
header('Content-Type: ' . ((new finfo(FILEINFO_MIME_TYPE))->file($file) ?: 'application/octet-stream'));
header('Content-Length: ' . filesize($file));
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');
readfile($file);
