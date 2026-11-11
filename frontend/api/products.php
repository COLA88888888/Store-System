<?php
/**
 * API ສິນຄ້າ (JSON, ອ່ານຢ່າງດຽວ) — ສຳລັບ JS ຫຼື ແອັບມືຖື ໃນອະນາຄົດ.
 *   GET ?id=N                         → ສິນຄ້າ 1 ລາຍການ
 *   GET ?q=&cat=&sort=&page=&per_page= → ລາຍການ + ແບ່ງໜ້າ
 *   GET ?categories=1                 → ໝວດທີ່ມີສິນຄ້າ
 */
require dirname(__DIR__) . '/includes/bootstrap.php';

if (!$pdo) {
    jsonOut(['success' => false, 'message' => t('ລະບົບບໍ່ພ້ອມໃຊ້ງານ')], 503);
}

$present = function (array $p) {
    return [
        'id'       => (int)$p['product_id'],
        'name'     => $p['product_name'],
        // ບໍ່ສົ່ງລາຄາອອກ — ຮ້ານບໍ່ເປີດເຜີຍລາຄາໃນເວັບ (API ນີ້ເປີດສາທາລະນະ)
        'unit'     => $p['unit'],
        'category' => $p['category_name'],
        'image'    => productImage($p['img_url']),
        'in_stock' => productInStock($p),
        'url'      => url('pages/product.php?id=' . (int)$p['product_id']),
    ];
};

if (isset($_GET['categories'])) {
    jsonOut(['success' => true, 'categories' => getCategories($pdo, $shopStoreId)]);
}
if (isset($_GET['id'])) {
    $p = getProduct($pdo, $shopStoreId, (int)$_GET['id']);
    jsonOut($p ? ['success' => true, 'product' => $present($p)] : ['success' => false, 'message' => t('ບໍ່ພົບສິນຄ້າ')], $p ? 200 : 404);
}
$result = getProducts($pdo, $shopStoreId, [
    'q'        => (string)($_GET['q'] ?? ''),
    'category' => (int)($_GET['cat'] ?? 0),
    'sort'     => in_array($_GET['sort'] ?? '', ['new', 'name'], true) ? $_GET['sort'] : 'new', // ບໍ່ໃຫ້ຮຽງຕາມລາຄາ
    'page'     => (int)($_GET['page'] ?? 1),
    'per_page' => min(50, max(1, (int)($_GET['per_page'] ?? $config['per_page']))),
]);
jsonOut([
    'success' => true,
    'items'   => array_map($present, $result['items']),
    'total'   => $result['total'],
    'page'    => $result['page'],
    'pages'   => $result['pages'],
]);
