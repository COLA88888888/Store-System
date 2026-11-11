<?php
/**
 * ສົ່ງອີເມວແຈ້ງເຕືອນທີ່ລໍຖ້າ (w_web_email_queue) ໃນເບື້ອງຫຼັງ — ເອີ້ນໂດຍ mailKick() (includes/mailer.php)
 * ຈາກ frontend (ລົງທະບຽນ, ສັ່ງຊື້) ແລະ backend (ປ່ຽນສະຖານະອໍເດີ, ລົດລາຄາ — backend/config/helpers/web_mail.php).
 * ຕອບ 202 ທັນທີ ແລ້ວສົ່ງຕໍ່ (ignore_user_abort). ແລ່ນໄດ້ເທື່ອລະ 1 (MySQL GET_LOCK). ບໍ່ຮັບຂໍ້ມູນຈາກຜູ້ເອີ້ນ.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$pdo) {
    http_response_code(405);
    exit;
}
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
    if ((int)$pdo->query("SELECT GET_LOCK('wlao_mail_queue', 0)")->fetchColumn() !== 1) {
        exit;
    }
    for ($round = 0; $round < 5; $round++) {   // ສູງສຸດ 150 ອີເມວ / ຄັ້ງ (ລົດລາຄາ ສົ່ງຫາສະມາຊິກຫຼາຍຄົນ)
        $stats = mailProcessQueue($pdo, 30);
        if ($stats['failed'] > 0) {
            error_log('frontend mail_queue: ' . json_encode($stats));
        }
        if ($stats['sent'] + $stats['failed'] < 30 || $stats['failed'] > 0) {
            break;
        }
    }
    $pdo->query("SELECT RELEASE_LOCK('wlao_mail_queue')");
} catch (Throwable $e) {
    error_log('frontend mail_queue: ' . $e->getMessage());
}
