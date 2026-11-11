<?php
/**
 * ແບນເນີສີເຫຼືອງ ຖັດຈາກກ່ອງລະບົບ (components/solutions.php) ໃນໜ້າຫຼັກ + ຫົວຂໍ້ "ເປັນຫຍັງຕ້ອງເລືອກ".
 * ສະໄລໂຊແບນເນີ: ທຸກຮູບໃນ backend/assets/img/baner/ (getBanners() ໃນ models/banners.php) — ສີເຫຼືອງກ່ອນ, ສະແດງເຕັມຮູບ (ບໍ່ຕັດ).
 * ຄຣິກ → pages/equipment_list.php (ແບນເນີສີຟ້າ ban.png + ອຸປະກອນ POS ທັງໝົດ).
 */
// ສະໄລໂຊແບນເນີ: ທຸກຮູບໃນ backend/assets/img/baner/ (models/banners.php) — ສີເຫຼືອງ (wlaodev) ກ່ອນ, ແລ້ວຮູບອື່ນ ເຊັ່ນ ban.png (ສີຟ້າ).
// ຂະໜາດກອບ = ຮູບທຳອິດ; ຮູບທີ່ສັດສ່ວນບໍ່ຄືກັນ ສະແດງເຕັມຮູບຢູ່ກາງ + ສີຟ້າສອງຂ້າງ (.promo-slide.is-fit). ເລື່ອນເອງທຸກ 5 ວິ ([data-promo-slider] ໃນ main.js)
$promoSlides = getBanners();
usort($promoSlides, fn($a, $b) => ($b['slug'] === 'wlaodev') <=> ($a['slug'] === 'wlaodev'));
$promoImage = $promoSlides[0] ?? null;

// ສິນຄ້າຂາຍດີ (ຖັດຈາກແບນເນີສີເຫຼືອງ): ດຶງຈາກ w_products ກ່ອນ; ຖ້າຍັງບໍ່ມີສິນຄ້າໃນຖານຂໍ້ມູນ → ໃຊ້ອຸປະກອນ ແລະ ລະບົບ POS ທີ່ມີຮູບ
$bestSellers = [];
$bsSeePrice = function_exists('canSeePrices') && canSeePrices();   // ລາຄາສະເພາະສະມາຊິກ
if ($pdo) {
    foreach (getProducts($pdo, $shopStoreId, ['per_page' => 12])['items'] as $p) {
        $bestSellers[] = [
            'link'  => url('pages/product.php?id=' . (int)$p['product_id']),
            'img'   => productImage($p['img_url'] ?? null),
            'icon'  => '📦',
            'title' => $p['product_name'],
            'sub'   => $p['category_name'] ?: t('ອຸປະກອນ POS'),
            'prod'  => true,
            'price' => $bsSeePrice && (float)$p['price'] > 0 ? money((float)$p['price']) : '',
            'old'   => $bsSeePrice && !empty($p['old_price']) && (float)$p['old_price'] > (float)$p['price'] ? money((float)$p['old_price']) : '',
        ];
    }
}
if (!$bestSellers) {
    foreach (['hardware', 'pos'] as $groupKey) {
        $groupLabel = $siteSolutions[$groupKey]['label'] ?? '';
        foreach ($siteSolutions[$groupKey]['items'] ?? [] as $it) {
            $img = solutionImage($it['slug']);
            if (!$img) {
                continue;
            }
            $bestSellers[] = [
                'link'  => url('pages/solution.php?slug=' . rawurlencode($it['slug'])),
                'img'   => $img,
                'icon'  => $it['icon'] ?? '📦',
                'title' => $it['title'],
                'sub'   => $groupLabel,
                'prod'  => $groupKey === 'hardware' && $it['slug'] !== 'hw-pos',
            ];
            if (count($bestSellers) >= 12) {
                break 2;
            }
        }
    }
}
?>
<section class="promo-section">
    <div class="container">
        <?php if ($promoImage):
            $promoRatio = $promoImage['width'] / max(1, $promoImage['height']);
        ?>
            <div class="promo-banner promo-slider" data-promo-slider
                 style="aspect-ratio: <?php echo (int)$promoImage['width']; ?> / <?php echo (int)$promoImage['height']; ?>">
                <?php foreach ($promoSlides as $i => $sl):
                    $fit = abs($sl['width'] / max(1, $sl['height']) - $promoRatio) > 0.05;   // ສັດສ່ວນຕ່າງຈາກກອບ → ສະແດງເຕັມຮູບຢູ່ກາງ
                    $slKey = strtolower($sl['slug']);
                    // ລິ້ງຂອງແຕ່ລະຮູບ: POS.png → ລະບົບ POS ຕາມປະເພດທຸລະກິດ (pages/pos_systems.php); ຮູບອື່ນ (ອຸປະກອນ) → ອຸປະກອນ POS ທັງໝົດ
                    $slHref = $slKey === 'pos' ? url('pages/pos_systems.php') : url('pages/equipment_list.php');
                ?>
                    <a class="promo-slide<?php echo $fit ? ' is-fit' : ''; ?><?php echo in_array($slKey, ['ban', 'pos'], true) ? ' is-' . $slKey : ''; ?><?php echo $fit && $slKey !== 'ban' ? ' is-smear' : ''; ?><?php echo $i === 0 ? ' is-active' : ''; ?>"
                       href="<?php echo e($slHref); ?>" style="--img: url('<?php echo e($sl['url']); ?>')" data-slide<?php echo $i ? ' tabindex="-1" aria-hidden="true"' : ''; ?>>
                        <span class="promo-slide-img"<?php echo $fit ? ' style="aspect-ratio: ' . (int)$sl['width'] . ' / ' . (int)$sl['height'] . '"' : ''; ?>>
                            <img src="<?php echo e($sl['url']); ?>" width="<?php echo (int)$sl['width']; ?>" height="<?php echo (int)$sl['height']; ?>"
                                 alt="<?php echo te('ອຸປະກອນ POS ຄົບວົງຈອນ'); ?>" loading="<?php echo $i ? 'lazy' : 'eager'; ?>" draggable="false">
                        </span>
                    </a>
                <?php endforeach; ?>
                <?php if (count($promoSlides) > 1): ?>
                    <button type="button" class="promo-nav prev" data-slide-prev aria-label="<?php echo te('ກ່ອນໜ້າ'); ?>"><svg viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 4l-8 8 8 8"/></svg></button>
                    <button type="button" class="promo-nav next" data-slide-next aria-label="<?php echo te('ຖັດໄປ'); ?>"><svg viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 4l8 8-8 8"/></svg></button>
                    <div class="promo-dots">
                        <?php foreach ($promoSlides as $i => $sl): ?>
                            <button type="button" class="<?php echo $i === 0 ? 'is-active' : ''; ?>" data-slide-dot="<?php echo $i; ?>" aria-label="<?php echo te('ແບນເນີທີ %d', $i + 1); ?>"></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($bestSellers): ?>
            <!-- ສິນຄ້າຂາຍດີ (ບໍ່ມີກາດຫຸ້ມ — ວາງເທິງພື້ນຂອງພາກເລີຍ, 4 ບັດຕໍ່ໜ້າ + ປຸ່ມ ‹ › ມຸມຂວາເທິງ + ຈຸດບອກໜ້າທາງລຸ່ມ) -->
            <div class="bs-panel" data-carousel>
                <div class="bs-head">
                    <div class="bs-head-text">
                        <h2 class="bs-heading"><span><?php echo te('ສິນຄ້າ'); ?></span> <em><?php echo te('ຂາຍດີ'); ?></em></h2>
                        <p class="bs-subhead"><?php echo te('ອຸປະກອນ ແລະ ລະບົບຂາຍໜ້າຮ້ານຍອດນິຍົມ ພ້ອມບໍລິການຕິດຕັ້ງຄົບຊຸດ'); ?></p>
                    </div>
                    <div class="bs-arrows">
                        <button type="button" class="bs-nav" data-car-prev aria-label="<?php echo te('ກ່ອນໜ້າ'); ?>">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 6-6 6 6 6"/></svg>
                        </button>
                        <button type="button" class="bs-nav" data-car-next aria-label="<?php echo te('ຖັດໄປ'); ?>">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                        </button>
                    </div>
                </div>
                <div class="bs-track" data-car-track>
                    <?php foreach ($bestSellers as $bs): ?>
                        <a class="bs-card<?php echo !empty($bs['prod']) ? ' is-product' : ''; ?> skel is-loading" href="<?php echo e($bs['link']); ?>">
                            <span class="bs-thumb">
                                <?php if ($bs['img']): ?>
                                    <img src="<?php echo e($bs['img']); ?>" alt="<?php echo e($bs['title']); ?>" loading="lazy">
                                <?php else: ?>
                                    <span class="mk-icon" aria-hidden="true"><?php echo $bs['icon']; ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="bs-body">
                                <strong class="bs-title"><?php echo e($bs['title']); ?></strong>
                                <small class="bs-sub"><?php echo e($bs['sub']); ?></small>
                                <?php if (!empty($bs['price'])): ?>
                                    <span class="mk-price-box">
                                        <?php if (!empty($bs['old'])): ?><s class="mk-old"><?php echo e($bs['old']); ?></s><?php endif; ?>
                                        <span class="mk-price"><?php echo e($bs['price']); ?></span>
                                    </span>
                                <?php endif; ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="wk-dots bs-dots" data-car-dots></div>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section promo-why-section" data-stack-under>
    <div class="container">
        <div class="promo-why">
            <p class="promo-why-kicker"><?php echo te('ເປັນຫຍັງຕ້ອງເລືອກ %s?', $company['name']); ?></p>
            <h2 class="promo-why-title"><?php echo te('ລະບົບຂາຍ ແລະ ອຸປະກອນ POS ຄົບຈົບໃນບ່ອນດຽວ ຕອບໂຈດທຸກທຸລະກິດ ຮ້ານທົ່ວໄປເລີຍ ມີທຸກຢ່າງ'); ?></h2>
            <?php require __DIR__ . '/home_why.php'; /* 4 ກາດເຫດຜົນ ໃຕ້ຫົວຂໍ້ */ ?>
        </div>
    </div>
</section>
