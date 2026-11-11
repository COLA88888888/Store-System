<?php
/**
 * ໜ້າຜົນລັບການຄົ້ນຫາ — ເປີດເມື່ອກົດປຸ່ມ "ຄົ້ນຫາ" (ຫຼື Enter) ໃນຊ່ອງຄົ້ນຫາໜ້າຫຼັກ (components/hero.php). GET q.
 * ຜົນລັບຈາກ searchSite() (models/search.php) ສະແດງເປັນບັດ (components/market_card.php) ແບບດຽວກັບໜ້າອຸປະກອນ.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
$q = trim((string)($_GET['q'] ?? ''));
$pageTitle = $q !== '' ? t('ຜົນການຄົ້ນຫາ "%s"', $q) : t('ຄົ້ນຫາ');
$currentPage = 'search';
$pageNoIndex = true; // SEO: ບໍ່ໃຫ້ Google ເກັບໜ້ານີ້
$logo = asset('img/logo.png');

$cards = [];
foreach (searchSite($pdo, $shopStoreId, $q, 48) as $r) {
    $cards[] = [
        'link'  => $r['url'],
        'img'   => $r['image'],
        'icon'  => $r['icon'],
        'title' => $r['title'],
        'meta'  => $r['sub'],
        'label' => '',
        'price' => (string)($r['price'] ?? ''),   // ສະເພາະສະມາຊິກ (models/search.php)
        'old'   => '',
    ];
}

require dirname(__DIR__) . '/layouts/header.php';
?>
<section class="section eq-section eq-list">
    <div class="container">
        <form class="search-page-form" action="<?php echo url('pages/search.php'); ?>" method="get" role="search">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><line x1="16.5" y1="16.5" x2="21" y2="21"></line></svg>
            <input type="search" name="q" value="<?php echo e($q); ?>" placeholder="<?php echo te('ຄົ້ນຫາສິນຄ້າ, ອຸປະກອນ ຫຼື ລະບົບທີ່ທ່ານຕ້ອງການ...'); ?>" aria-label="<?php echo te('ຄົ້ນຫາ'); ?>">
            <button type="submit"><?php echo te('ຄົ້ນຫາ'); ?></button>
        </form>

        <h1 class="eq-list-title"><?php echo e($pageTitle); ?></h1>
        <div class="eq-count"><span><?php echo te('ພົບ %d ລາຍການ', count($cards)); ?></span></div>

        <?php if ($cards): ?>
            <div class="eq-grid">
                <?php foreach ($cards as $card): ?>
                    <?php require dirname(__DIR__) . '/components/market_card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="empty"><?php echo te('ບໍ່ພົບລາຍການທີ່ກົງກັນ'); ?></p>
        <?php endif; ?>
    </div>
</section>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
