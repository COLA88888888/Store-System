<?php
/**
 * ກະຕ່າສິນຄ້າ — ເກັບໃນ $_SESSION['cart'] ເປັນ [product_id => ຈຳນວນ].
 * ເກັບພຽງ id ແລະ ຈຳນວນ; ລາຄາ ແລະ ສະຕັອກ ອ່ານຈາກຖານຂໍ້ມູນທຸກຄັ້ງ (cartDetails) ເພື່ອບໍ່ໃຫ້ລູກຄ້າແກ້ລາຄາເອງໄດ້.
 * ຕ້ອງຢູ່ຫຼັງ config/session.php; cartDetails() ໃຊ້ຟັງຊັນໃນ models/products.php.
 */

function cartItems(): array
{
    return (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) ? $_SESSION['cart'] : [];
}

function cartSet($productId, int $qty): void
{
    $key = is_int($productId) ? (int)$productId : trim((string)$productId);
    if ($key === '' || $key === 0 || $key === '0') {
        return;
    }
    if ($qty <= 0) {
        unset($_SESSION['cart'][$key]);
        return;
    }
    $_SESSION['cart'][$key] = min($qty, 999);
}

function cartAdd($productId, int $qty = 1): void
{
    $key = is_int($productId) ? (int)$productId : trim((string)$productId);
    $items = cartItems();
    cartSet($key, ($items[$key] ?? 0) + max(1, $qty));
}

function cartRemove($productId): void
{
    $key = is_int($productId) ? (int)$productId : trim((string)$productId);
    unset($_SESSION['cart'][$key]);
}

function cartClear(): void
{
    $_SESSION['cart'] = [];
}

/**
 * ລາຍລະອຽດກະຕ່າ ພ້ອມຂໍ້ມູນສິນຄ້າ. ສິນຄ້າທີ່ຖືກລຶບ ຫຼື ໝົດ ຈະຖືກເອົາອອກ/ຈຳກັດຈຳນວນຕາມສະຕັອກ.
 * ຮອງຮັບທັງສິນຄ້າຈາກ DB (product_id) ແລະ ອຸປະກອນ hardware (slug).
 * @return array{lines: array, total: float, qty: int}
 */
function cartDetails(?PDO $pdo, int $storeId): array
{
    $items = cartItems();
    $result = ['lines' => [], 'total' => 0.0, 'qty' => 0];
    if (!$items) {
        return $result;
    }

    $numericIds = [];
    foreach (array_keys($items) as $k) {
        if (is_numeric($k) && (int)$k > 0) {
            $numericIds[] = (int)$k;
        }
    }
    $products = ($pdo && $numericIds) ? getProductsByIds($pdo, $storeId, $numericIds) : [];
    $hwFallback = function_exists('hardwareFallback') ? hardwareFallback() : [];

    foreach ($items as $k => $qty) {
        $p = null;
        if (is_numeric($k) && isset($products[(int)$k])) {
            $p = $products[(int)$k];
            $p['item_key'] = (int)$k;
            $p['url'] = url('pages/product.php?id=' . (int)$p['product_id']);
        } elseif (isset($hwFallback[$k])) {
            $hw = $hwFallback[$k];
            $p = [
                'product_id'   => 0,
                'item_slug'    => $k,
                'item_key'     => $k,
                'product_name' => $hw['card']['title'],
                'price'        => 0.0,
                'img_url'      => $hw['card']['img'] ?? null,
                'unit'         => t('ອັນ'),
                'qty'          => 999,
                'cut_qty'      => 0,
                'url'          => $hw['card']['link'] ?? url('pages/solution.php?slug=' . rawurlencode($k)),
            ];
        }

        if (!$p || !productInStock($p)) {
            cartRemove($k);
            continue;
        }

        $qty = min((int)$qty, productMaxQty($p));
        cartSet($k, $qty);
        $subtotal = $qty * (float)$p['price'];
        $result['lines'][] = [
            'key'      => $k,
            'product'  => $p,
            'qty'      => $qty,
            'subtotal' => $subtotal,
        ];
        $result['total'] += $subtotal;
        $result['qty'] += $qty;
    }
    return $result;
}
