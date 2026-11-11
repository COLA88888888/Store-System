<?php
/**
 * ສະແດງຮູບສະລິບໂອນເງິນ ໃຫ້ສະເພາະເຈົ້າຂອງອໍເດີ (GET: no = ເລກອໍເດີ) — ໂຟນເດີ backend/assets/web_slips/ ຫ້າມເປີດໂດຍກົງ (.htaccess).
 * ໃຊ້ໃນ pages/my_order.php (slipUrl() ໃນ models/member_orders.php). ພະນັກງານເບິ່ງຜ່ານ backend/pages/web_orders/slip.php.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
$member = currentMember($pdo);
$order = ($member && $pdo) ? memberOrder($pdo, $member, (string)($_GET['no'] ?? '')) : null;
$col = ($_GET['type'] ?? '') === 'receipt' ? 'receipt_img' : 'slip_img';   // type=receipt → ບິນຮັບເຄື່ອງຂອງຂົນສົ່ງ
$file = $order && !empty($order[$col]) ? dirname(__DIR__, 2) . '/' . ltrim((string)$order[$col], '/') : '';
$okDir = $col === 'receipt_img' ? '/backend/assets/web_receipts/' : '/backend/assets/web_slips/';   // ບິນຮັບເຄື່ອງ / ສະລິບ ຢູ່ຄົນລະໂຟນເດີ
if ($file === '' || !is_file($file) || !str_contains(str_replace('\\', '/', realpath($file)), $okDir)) {
    http_response_code(404);
    exit;
}
@ini_set('zlib.output_compression', '0');
session_write_close();
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($file));
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');
readfile($file);
