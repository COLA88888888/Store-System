<?php
/**
 * robots.txt (URL /robots.txt → ໄຟລ໌ນີ້, rewrite ໃນ .htaccess ທີ່ root): ບອກ Google ໃຫ້ເກັບທຸກໜ້າ ຍົກເວັ້ນ
 * ລະບົບຫຼັງບ້ານ (backend/), API, ກະຕ່າ, ສັ່ງຊື້ ແລະ ຜົນຄົ້ນຫາ — ພ້ອມບອກບ່ອນຢູ່ຂອງ sitemap.xml.
 * ໝາຍເຫດ: Google ອ່ານ robots.txt ສະເພາະທີ່ root ຂອງ domain — ເວັບຕ້ອງຢູ່ root (https://domain/) ຈຶ່ງມີຜົນ.
 */
require __DIR__ . '/includes/bootstrap.php';

$root = siteRoot();
header('Content-Type: text/plain; charset=utf-8');
echo "User-agent: *\n";
echo "Allow: {$root}\n";
foreach (['backend/', 'api/', 'pages/cart', 'pages/checkout', 'pages/order_success', 'pages/search'] as $p) {
    echo 'Disallow: ' . $root . $p . "\n";
}
echo "\nSitemap: " . absUrl('sitemap.xml') . "\n";
