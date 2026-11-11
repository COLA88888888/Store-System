<?php
/**
 * ຂໍ້ມູນບັດສິນຄ້າຂອງໜ້າອຸປະກອນ POS — ໃຊ້ຮ່ວມກັນໂດຍ pages/equipment.php (ແຖວຕາມໝວດ) ແລະ pages/equipment_list.php (ທັງໝົດ + ໝວດ).
 * ບັດ 1 ອັນ = array ທີ່ components/market_card.php ຕ້ອງການ: link, img, icon?, title, meta, label, price, old? (ລາຄາກ່ອນຫຼຸດ ຂີດຂ້າ).
 * ສິນຄ້າມາຈາກ w_products (models/products.php). ຍັງບໍ່ມີສິນຄ້າໃນຖານຂໍ້ມູນ → ໃຊ້ອຸປະກອນໃນແຖບ 'hardware'
 * ຂອງ $siteSolutions (includes/content.php) ແທນ — ລາຄາ = 'price' ຂອງແຕ່ລະອຸປະກອນ (0 = "ສອບຖາມລາຄາ").
 */

function productCard(array $p): array
{
    $price = (float)($p['price'] ?? 0);
    $oldPrice = (float)($p['old_price'] ?? 0);
    $isMember = function_exists('canSeePrices') ? canSeePrices() : false;

    // ລາຄາ: ສະເພາະສະມາຊິກທີ່ເຂົ້າສູ່ລະບົບຈຶ່ງເຫັນລາຄາ (ອອກຈາກລະບົບເຊື່ອງລາຄາໄວ້)
    if ($isMember) {
        if ($price > 0 && function_exists('memberPriceInfo')) {
            $pi = memberPriceInfo($price, $oldPrice);
            $sellPrice = $pi['sell'];
            $oldPriceVal = $pi['old'] > $pi['sell'] ? $pi['old'] : 0;
        } else {
            $sellPrice = $price;
            $oldPriceVal = $oldPrice > $price ? $oldPrice : 0;
        }
        $priceText = $sellPrice > 0 ? money($sellPrice) : '';
        $oldPriceText = $oldPriceVal > 0 ? money($oldPriceVal) : '';
    } else {
        $priceText = '';
        $oldPriceText = '';
    }

    return [
        'link'      => url('pages/product.php?id=' . (int)$p['product_id']),
        'img'       => productImage($p['img_url'] ?? null),
        'title'     => $p['product_name'],
        'meta'      => (string)($p['category_name'] ?? t('ອຸປະກອນ POS')),
        'category'  => (string)($p['category_name'] ?? t('ອຸປະກອນ POS')),
        'in_stock'  => (int)($p['cut_qty'] ?? 0) === 0 || (int)($p['qty'] ?? 0) > 0,
        'label'     => '',
        'price'     => $priceText,
        'old'       => $oldPriceText,
        'badge'     => '',
        'is_member' => $isMember,
    ];
}

/** ອຸປະກອນສຳຮອງ (ບໍ່ມີສິນຄ້າໃນຖານຂໍ້ມູນ) — key = slug, ພ້ອມ 'card' ແລະ 'label' (ຊື່ສັ້ນສຳລັບປຸ່ມໝວດ) */
function hardwareFallback(): array
{
    global $siteSolutions;
    $out = [];
    foreach ($siteSolutions['hardware']['items'] ?? [] as $it) {
        $price = (float)($it['price'] ?? 0);
        $oldPrice = (float)($it['old_price'] ?? 0);
        $seePrice = function_exists('canSeePrices') && canSeePrices();   // ອອກຈາກລະບົບ = ບໍ່ເຫັນລາຄາ
        $out[$it['slug']] = [
            'label' => $it['title'],
            'card'  => [
                'link'    => url('pages/solution.php?slug=' . rawurlencode($it['slug'])),
                'cart_id' => $it['slug'],
                'img'     => solutionImage($it['slug']),
                'icon'    => $it['icon'],
                'title'   => $it['title'],
                'meta'    => t('ພ້ອມຈັດສົ່ງທົ່ວປະເທດ'),
                'label'   => '',
                'price'   => $seePrice ? ($price > 0 ? money($price) : t('ຕິດຕໍ່ສອບຖາມລາຄາ')) : '',
                'old'     => $seePrice && $oldPrice > $price ? money($oldPrice) : '',
                'badge'   => '',
                'is_member' => $seePrice,
            ],
        ];
    }
    return $out;
}
