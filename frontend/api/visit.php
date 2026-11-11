<?php
/**
 * Beacon ນັບຜູ້ເຂົ້າຊົມ — footer (layouts/footer.php) ສົ່ງ POST ມາຫຼັງໂຫຼດໜ້າ (ສະເພາະບຣາວເຊີທີ່ແລ່ນ JS).
 * ກົດການນັບ: recordVisit() ໃນ models/visitors.php. ຕອບ 204 ສະເໝີ (ບໍ່ມີຂໍ້ມູນໃຫ້ອ່ານ).
 */
require dirname(__DIR__) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    recordVisit($pdo);
}
http_response_code(204);
header('Cache-Control: no-store');
