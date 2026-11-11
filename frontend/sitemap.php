<?php
/**
 * sitemap.xml (URL /sitemap.xml → ໄຟລ໌ນີ້, rewrite ໃນ .htaccess ທີ່ root) — ລາຍຊື່ໜ້າທັງໝົດໃຫ້ Google ເກັບ:
 * ໜ້າຫຼັກ, ໜ້າທົ່ວໄປ, ລະບົບ/ອຸປະກອນທຸກອັນ ($siteSolutions) ແລະ ສິນຄ້າທຸກລາຍການ (w_products, ສາຂາໃຫຍ່).
 * ສົ່ງ URL ນີ້ໃຫ້ Google Search Console ເມື່ອເວັບຂຶ້ນ server ແທ້.
 */
require __DIR__ . '/includes/bootstrap.php';

$today = date('Y-m-d');
$urls = [
    ['', '1.0', 'daily'],
    ['pages/equipment.php', '0.9', 'weekly'],
    ['pages/equipment_list.php', '0.8', 'weekly'],
    ['pages/services.php', '0.7', 'monthly'],
    ['pages/pos_systems.php', '0.8', 'weekly'],
    ['pages/portfolio.php', '0.6', 'monthly'],
    ['pages/about.php', '0.6', 'monthly'],
    ['pages/contact.php', '0.7', 'monthly'],
];
if (!hasWorks()) {   // ຍັງບໍ່ມີຜົນງານແທ້ → ບໍ່ໃຫ້ Google ເກັບໜ້າຜົນງານຫວ່າງ
    $urls = array_values(array_filter($urls, fn($u) => $u[0] !== 'pages/portfolio.php'));
}
foreach ($siteSolutions as $tab) {
    foreach ($tab['items'] as $it) {
        $urls[] = ['pages/solution.php?slug=' . rawurlencode($it['slug']), '0.8', 'weekly'];
    }
}
foreach (getWorks() as $w) {
    $urls[] = ['pages/work.php?w=' . rawurlencode($w['slug']), '0.6', 'monthly'];
}
if ($pdo) {
    $st = $pdo->prepare("SELECT product_id FROM w_products WHERE store_id = ? AND price > 0 ORDER BY code1 DESC LIMIT 5000");
    $st->execute([$shopStoreId]);
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $id) {
        $urls[] = ['pages/product.php?id=' . (int)$id, '0.7', 'weekly'];
    }
}

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as [$path, $priority, $freq]) {
    echo '  <url><loc>' . htmlspecialchars(absUrl($path), ENT_XML1) . '</loc><lastmod>' . $today . '</lastmod>'
       . '<changefreq>' . $freq . '</changefreq><priority>' . $priority . '</priority></url>' . "\n";
}
echo '</urlset>' . "\n";
