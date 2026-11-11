<?php
/**
 * ເລີ່ມ session ຂອງເວັບໄຊ — ຊື່ cookie ແຍກ (WLAOWEBSSID ≠ WLAODEVSSID ຂອງ backend),
 * ດັ່ງນັ້ນ ກະຕ່າສິນຄ້າຂອງລູກຄ້າ ກັບ session ພະນັກງານໃນ backend/ ບໍ່ປົນກັນ.
 * ຕ້ອງຢູ່ຫຼັງ includes/helpers.php (ໃຊ້ frontBase()).
 */
if (session_status() === PHP_SESSION_NONE) {
    session_name($config['session_name']);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => frontBase(),
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}
