<?php
/**
 * ໜ້າອຸປະກອນ POS (ເປີດຈາກແບນເນີສີເຫຼືອງໃນໜ້າຫຼັກ — components/promo_banner.php).
 * ເທິງສຸດ: ແບນເນີ wlaodev ເຕັມຄວາມກວ້າງຈໍ. ລຸ່ມ: ບັດສິນຄ້າ (components/market_card.php) ແຍກແຖວຕາມໝວດ, ແຕ່ລະແຖວເລື່ອນທາງຂ້າງໄດ້
 * ([data-strip] ໃນ assets/js/main.js). ສິນຄ້າມາຈາກ w_products (ສາຂາໃຫຍ່).
 * ຍັງບໍ່ມີສິນຄ້າໃນຖານຂໍ້ມູນ → ອຸປະກອນສຳຮອງ (hardwareFallback() ໃນ models/equipment.php). "ເບິ່ງທັງໝົດ" → pages/equipment_list.php
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
$pageTitle = t('ອຸປະກອນ POS');
$pageDescription = t('ເຄື່ອງ POS, ປິ້ນເຕີ, ເຄື່ອງສະແກນ, ລິ້ນຊັກເກັບເງິນ ແລະ ເຈ້ຍພິມບິນ — ຂາຍພ້ອມລະບົບ ຕິດຕັ້ງຟຣີ ແລະ ສອນໃຊ້ງານໃຫ້ຟຣີ');
$currentPage = 'equipment';

$heroBanner = getBannerBySlug('wlaodev');
$logo = asset('img/logo.png');

// ແຖວບັດ: [title, link "ເບິ່ງທັງໝົດ" (→ pages/equipment_list.php), cards[]] — ບັດສ້າງໂດຍ models/equipment.php
$rows = [];
if ($pdo) {
    foreach (getCategories($pdo, $shopStoreId) as $c) {
        $items = getProducts($pdo, $shopStoreId, ['category' => (int)$c['category_id'], 'per_page' => 12])['items'];
        $rows[] = [$c['category_name'], url('pages/equipment_list.php?cat=' . (int)$c['category_id'] . '#eq-results'), array_map('productCard', $items)];
    }
}
if (!$rows && ($hw = hardwareFallback())) {
    $rows[] = [$siteSolutions['hardware']['label'], url('pages/equipment_list.php#eq-results'), array_column($hw, 'card')];
}

require dirname(__DIR__) . '/layouts/header.php';
?>
<?php if ($heroBanner): ?>
    <?php /* ຄຣິກແບນເນີສີເຫຼືອງ → ອຸປະກອນ POS ທັງໝົດ (ແບນເນີສີຟ້າ ban.png) */ ?>
    <a class="eq-banner" href="<?php echo url('pages/equipment_list.php'); ?>" style="display: block; aspect-ratio: <?php echo (int)$heroBanner['width']; ?> / <?php echo (int)$heroBanner['height']; ?>">
        <img src="<?php echo e($heroBanner['url']); ?>" width="<?php echo (int)$heroBanner['width']; ?>" height="<?php echo (int)$heroBanner['height']; ?>"
             alt="<?php echo e($pageTitle); ?>" fetchpriority="high" draggable="false">
    </a>
<?php endif; ?>

<section class="section eq-section">
    <div class="container">
        <h1 class="eq-title"><?php echo e($pageTitle); ?></h1>
        <p class="eq-desc"><?php echo e($pageDescription); ?></p>

        <?php foreach ($rows as [$rowTitle, $rowLink, $cards]): ?>
            <div class="eq-row">
                <div class="eq-row-head">
                    <h2><?php echo e($rowTitle); ?></h2>
                    <a class="eq-all" href="<?php echo e($rowLink); ?>"><?php echo te('ເບິ່ງທັງໝົດ'); ?></a>
                </div>
                <div class="eq-strip-wrap" data-strip>
                    <div class="eq-strip" data-strip-track>
                        <?php foreach ($cards as $card): ?>
                            <?php require dirname(__DIR__) . '/components/market_card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="eq-strip-btn prev" data-strip-prev aria-label="<?php echo te('ກ່ອນໜ້າ'); ?>" hidden>
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    </button>
                    <button type="button" class="eq-strip-btn next" data-strip-next aria-label="<?php echo te('ຖັດໄປ'); ?>" hidden>
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
