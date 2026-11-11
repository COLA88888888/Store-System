<?php
/**
 * ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ ($pdo) ສຳລັບ frontend — ລອງພອດ 3306 ກ່ອນ ແລ້ວ 3336 (ຄືກັບ backend).
 * ເຊື່ອມບໍ່ໄດ້ → $pdo = null; ໜ້າເວັບຍັງເປີດໄດ້ ແຕ່ສະແດງວ່າບໍ່ມີສິນຄ້າ.
 * ໃຊ້ຕົວແປ $config ຈາກ includes/bootstrap.php.
 */
$pdo = null;
foreach ($config['db']['ports'] as $dbPort) {
    try {
        $pdo = new PDO(
            "mysql:host={$config['db']['host']};port={$dbPort};dbname={$config['db']['name']};charset=utf8mb4",
            $config['db']['user'],
            $config['db']['pass'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT            => 3,
            ]
        );
        break;
    } catch (Throwable $e) {
        $pdo = null;
    }
}
if (!$pdo) {
    error_log('frontend: ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ ' . $config['db']['name'] . ' ບໍ່ໄດ້');
}
