<?php
/**
 * ອ່ານສິນຄ້າ ແລະ ໝວດ ຈາກ w_products / w_categories (ຂໍ້ມູນດຽວກັບ POS ໃນ backend/).
 * ສະແດງສະເພາະສິນຄ້າຂອງສາຂາໃຫຍ່ ທີ່ມີລາຄາ > 0. ອ່ານຢ່າງດຽວ — ບໍ່ແກ້ໄຂສິນຄ້າ.
 */

const PRODUCT_COLUMNS = "p.product_id, p.product_name, p.barcode, p.price, p.qty, p.unit, p.img_url, p.cut_qty,
                         p.category_id, c.category_name";

// ຖັນທີ່ອ່ານ = PRODUCT_COLUMNS + old_price (ລາຄາກ່ອນຫຼຸດ, migration v23 ຂອງ backend). ຖ້າ DB ຍັງບໍ່ທັນ migrate
// (ຍັງບໍ່ມີໃຜເປີດ backend ຫຼັງອັບເດດ) → ໃຊ້ NULL ແທນ ເພື່ອບໍ່ໃຫ້ໜ້າເວັບລົ້ມ
function productColumns(PDO $pdo): string
{
    static $cols = null;
    if ($cols === null) {
        $has = (bool)$pdo->query("SHOW COLUMNS FROM w_products LIKE 'old_price'")->fetch();
        $cols = PRODUCT_COLUMNS . ($has ? ', p.old_price' : ', NULL AS old_price');
    }
    return $cols;
}

// ສິນຄ້າທີ່ບໍ່ຕັດສະຕັອກ (cut_qty = 0: ບໍລິການ) ຂາຍໄດ້ສະເໝີ
function productInStock(array $p): bool
{
    return (int)$p['cut_qty'] === 0 || (int)$p['qty'] > 0;
}

function productMaxQty(array $p): int
{
    return (int)$p['cut_qty'] === 0 ? 999 : max(0, min(999, (int)$p['qty']));
}

function getCategories(PDO $pdo, int $storeId): array
{
    $st = $pdo->prepare("SELECT c.category_id, c.category_name, COUNT(p.product_id) AS total
        FROM w_categories c
        JOIN w_products p ON p.category_id = c.category_id AND p.store_id = ? AND p.price >= 0
        GROUP BY c.category_id, c.category_name
        ORDER BY c.category_name");
    $st->execute([$storeId]);
    return localizeCategories($st->fetchAll());   // ຊື່ໝວດຕາມພາສາທີ່ເລືອກ (models/translations.php)
}

/**
 * ລາຍການສິນຄ້າ ພ້ອມຄົ້ນຫາ, ກັ່ນຕອງໝວດ, ຮຽງລຳດັບ ແລະ ແບ່ງໜ້າ.
 * $filter: q, category, in_stock (bool: ສະເພາະທີ່ຍັງມີ ຫຼື ບໍ່ຕັດສະຕັອກ), sort (new|price_asc|price_desc|name), page, per_page
 * @return array{items: array, total: int, pages: int, page: int}
 */
function getProducts(PDO $pdo, int $storeId, array $filter = []): array
{
    $where = ["p.store_id = ?"];
    $args = [$storeId];
    $q = trim($filter['q'] ?? '');
    if ($q !== '') {
        $terms = function_exists('expandSearchTerms') ? expandSearchTerms($q) : [$q];
        $clauses = [];
        foreach ($terms as $t) {
            $clauses[] = "p.product_name LIKE ?";
            $args[] = '%' . $t . '%';
        }
        // ຄົ້ນດ້ວຍຊື່ທີ່ແປແລ້ວໄດ້ນຳ (ເຊັ່ນ ລູກຄ້າເລືອກພາສາອັງກິດ ພິມ "printer")
        $clauses[] = "p.product_id IN (SELECT entity_id FROM w_translations WHERE entity = 'products' AND text LIKE ?)";
        $args[] = '%' . $q . '%';
        $clauses[] = "p.barcode = ?";
        $args[] = $q;
        $where[] = '(' . implode(' OR ', $clauses) . ')';
    }
    if (!empty($filter['category'])) {
        $where[] = "p.category_id = ?";
        $args[] = (int)$filter['category'];
    }
    if (!empty($filter['in_stock'])) {
        $where[] = "(p.cut_qty = 0 OR p.qty > 0)";
    }
    $sorts = [
        'new'        => 'p.code1 DESC',
        'price_asc'  => 'p.price ASC',
        'price_desc' => 'p.price DESC',
        'name'       => 'p.product_name ASC',
    ];
    $order = $sorts[$filter['sort'] ?? 'new'] ?? $sorts['new'];
    $orderArgs = [];
    if ($q !== '') {
        $firstSynonym = $terms[1] ?? $q;
        $order = "(CASE WHEN p.product_name LIKE ? THEN 0 WHEN p.product_name LIKE ? THEN 1 ELSE 2 END), " . $order;
        $orderArgs[] = '%' . $q . '%';
        $orderArgs[] = '%' . $firstSynonym . '%';
    }
    $whereSql = implode(' AND ', $where);

    $count = $pdo->prepare("SELECT COUNT(*) FROM w_products p WHERE $whereSql");
    $count->execute($args);
    $total = (int)$count->fetchColumn();

    $perPage = max(1, (int)($filter['per_page'] ?? 12));
    $pages = max(1, (int)ceil($total / $perPage));
    $page = min(max(1, (int)($filter['page'] ?? 1)), $pages);
    $offset = ($page - 1) * $perPage;

    $st = $pdo->prepare("SELECT " . productColumns($pdo) . "
        FROM w_products p LEFT JOIN w_categories c ON c.category_id = p.category_id
        WHERE $whereSql ORDER BY $order LIMIT $perPage OFFSET $offset");
    $st->execute(array_merge($args, $orderArgs));
    return ['items' => localizeProducts($st->fetchAll()), 'total' => $total, 'pages' => $pages, 'page' => $page];
}

function getLatestProducts(PDO $pdo, int $storeId, int $limit = 8): array
{
    return getProducts($pdo, $storeId, ['sort' => 'new', 'per_page' => $limit])['items'];
}

function getProduct(PDO $pdo, int $storeId, int $productId): ?array
{
    $st = $pdo->prepare("SELECT " . productColumns($pdo) . "
        FROM w_products p LEFT JOIN w_categories c ON c.category_id = p.category_id
        WHERE p.store_id = ? AND p.product_id = ? AND p.price >= 0 LIMIT 1");
    $st->execute([$storeId, $productId]);
    return localizeProduct($st->fetch() ?: null);
}

function getRelatedProducts(PDO $pdo, int $storeId, array $product, int $limit = 4): array
{
    $st = $pdo->prepare("SELECT " . productColumns($pdo) . "
        FROM w_products p LEFT JOIN w_categories c ON c.category_id = p.category_id
        WHERE p.store_id = ? AND p.category_id = ? AND p.product_id <> ? AND p.price >= 0
        ORDER BY p.code1 DESC LIMIT " . (int)$limit);
    $st->execute([$storeId, (int)$product['category_id'], (int)$product['product_id']]);
    return localizeProducts($st->fetchAll());
}

// [product_id => row] ສຳລັບກະຕ່າ ແລະ ຄຳສັ່ງຊື້; $forUpdate ລັອກແຖວໃນ transaction
function getProductsByIds(PDO $pdo, int $storeId, array $ids, bool $forUpdate = false): array
{
    $ids = array_values(array_filter(array_map('intval', $ids)));
    if (!$ids) {
        return [];
    }
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare("SELECT " . productColumns($pdo) . "
        FROM w_products p LEFT JOIN w_categories c ON c.category_id = p.category_id
        WHERE p.store_id = ? AND p.price >= 0 AND p.product_id IN ($marks)" . ($forUpdate ? " FOR UPDATE" : ""));
    $st->execute(array_merge([$storeId], $ids));
    $rows = [];
    foreach ($st->fetchAll() as $r) {
        $rows[(int)$r['product_id']] = $r;
    }
    return $rows;
}

/**
 * ຮູບທັງໝົດຂອງສິນຄ້າສຳລັບແກເລີຣີ (pages/product.php): ຮູບຫຼັກ (img_url) ກ່ອນ + ຮູບເພີ່ມເຕີມຫຼາຍມຸມ (w_product_images, v39 —
 * ອັບໂຫຼດໃນ backend/pages/products/). ຄືນ [['url' => ..., 'thumb' => ...], ...] — ໄຟລ໌ທີ່ຫາຍໄປ ຖືກຂ້າມ.
 */
function getProductImages(PDO $pdo, array $product): array
{
    $files = [(string)($product['img_url'] ?? '')];
    try {
        $st = $pdo->prepare("SELECT img_file FROM w_product_images WHERE product_id = ? ORDER BY sort_order, id");
        $st->execute([(int)$product['product_id']]);
        $files = array_merge($files, $st->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable $e) {
    }
    $out = [];
    foreach (array_unique(array_filter($files)) as $f) {
        if ($u = productImage($f)) {
            $out[] = ['url' => $u, 'thumb' => $u];
        }
    }
    return $out;
}
