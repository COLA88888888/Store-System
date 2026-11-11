<?php
/**
 * ສັ່ງເວັບໄຊແປຊື່ສິນຄ້າ / ໝວດ ອັດຕະໂນມັດ — ຫຼັງບັນທຶກ (POST) ໃນໜ້າສິນຄ້າ, ໝວດ, ນຳເຂົ້າສິນຄ້າ ຂອງຫຼັງບ້ານ
 * ຈະເອີ້ນ frontend/api/translate_missing.php (ແປເປັນ th / en / zh / vi ລົງ w_translations ໃນເບື້ອງຫຼັງ).
 * ລໍຖ້າສູງສຸດ 0.4 ວິ (API ຕອບ 202 ທັນທີ ແລ້ວແປຕໍ່ເອງ) — ຫຼັງບ້ານບໍ່ຊ້າລົງ. ຖືກ require ຈາກ config/db.php.
 */
function webTranslatePing(): void
{
    if (!function_exists('curl_init')) {
        return;
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // /<project>/backend/... → /<project>/api/translate_missing.php (root .htaccess ພາໄປ frontend/)
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $root = ($p = strpos($script, '/backend/')) !== false ? substr($script, 0, $p) : '';
    $ch = curl_init($scheme . '://' . $host . $root . '/api/translate_missing.php');
    curl_setopt_array($ch, [
        CURLOPT_POST              => true,
        CURLOPT_POSTFIELDS        => '',
        CURLOPT_RETURNTRANSFER    => true,
        CURLOPT_TIMEOUT_MS        => 400,
        CURLOPT_CONNECTTIMEOUT_MS => 300,
        CURLOPT_NOSIGNAL          => true,
        CURLOPT_SSL_VERIFYPEER    => false,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// ຫຼັງ request POST ຂອງໜ້າທີ່ເພີ່ມ/ແກ້ຊື່ສິນຄ້າ ຫຼື ໝວດ → ສັ່ງແປ (ທ້າຍ request ຈຶ່ງບໍ່ຂັດການບັນທຶກ)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && preg_match('#/(pages/products/|pages/categories/|pages/import_stock/|api/products_api\.php|api/categories_backend\.php|api/categories_api\.php)#', str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''))) {
    register_shutdown_function('webTranslatePing');
}
