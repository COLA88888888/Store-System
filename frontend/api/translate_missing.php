<?php
/**
 * ແປຊື່ສິນຄ້າ / ໝວດ ທີ່ຍັງບໍ່ມີຄຳແປ ເປັນ th / en / zh / vi ໃນເບື້ອງຫຼັງ (dbTrProcessMissing ໃນ models/translations.php).
 * ຖືກເອີ້ນ: (1) ຫຼັງບ້ານ ທັນທີທີ່ເພີ່ມ/ແກ້ສິນຄ້າ ຫຼື ໝວດ (backend/config/helpers/web_translate.php)
 *           (2) footer ສົ່ງ beacon ເມື່ອໜ້າທີ່ເປີດມີຊື່ທີ່ຍັງບໍ່ໄດ້ແປ.
 * ຕອບ 202 ທັນທີ ແລ້ວແປຕໍ່ຫຼັງຕັດການເຊື່ອມຕໍ່ (ignore_user_abort) — ບໍ່ມີໃຜຕ້ອງລໍຖ້າ.
 * ບໍ່ຮັບຂໍ້ຄວາມຈາກຜູ້ເອີ້ນ: ອ່ານສິ່ງທີ່ຕ້ອງແປຈາກຖານຂໍ້ມູນເອງ. ແລ່ນໄດ້ເທື່ອລະ 1 (MySQL GET_LOCK), ແປສູງສຸດ 60 ຄັ້ງ / ຮອບ.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$pdo) {
    http_response_code(405);
    exit;
}
// ສົ່ງຄຳຕອບ ແລະ ປິດການເຊື່ອມຕໍ່ກ່ອນ ແລ້ວຈຶ່ງແປ (ປິດການບີບອັດ ເພື່ອໃຫ້ Content-Length: 0 ຖືກຕ້ອງ)
@ini_set('zlib.output_compression', '0');
ignore_user_abort(true);
set_time_limit(300);
session_write_close();
http_response_code(202);
header('Cache-Control: no-store');
header('Connection: close');
header('Content-Length: 0');
while (ob_get_level() > 0) {
    ob_end_flush();
}
flush();

try {
    if ((int)$pdo->query("SELECT GET_LOCK('wlao_db_translate', 0)")->fetchColumn() !== 1) {
        exit;   // ກຳລັງແປຢູ່ແລ້ວ (ຮອບອື່ນ)
    }
    $stats = dbTrProcessMissing($pdo, (int)$shopStoreId, 60);
    $pdo->query("SELECT RELEASE_LOCK('wlao_db_translate')");
    if ($stats['failed'] > 0) {
        error_log('frontend translate_missing: ' . json_encode($stats));
    }
} catch (Throwable $e) {
    error_log('frontend translate_missing: ' . $e->getMessage());
}
