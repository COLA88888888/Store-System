<?php
/**
 * ລະບົບ POS ທັງໝົດ (ໜ້າແຍກ) - ປາຍທາງຂອງປຸ່ມແບນເນີ POS.png ໜ້າຫຼັກ (components/promo_banner.php).
 *   ເທິງ: ແບນເນີ POS.png (backend/assets/img/baner/, ຮອງຮັບ = ອັດຕາສ່ວນຈິງ + ຮູບ)
 *   ລຸ່ມ: ກາດລະບົບທຸກປະເພດ = $siteSolutions['pos'] (includes/content.php) - ຮູບ solutionImage(), ລິ້ງ pages/solution.php?slug=
 * CSS: .sysp-* ໃນ assets/css/pages.css
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
$pageTitle = t('ລະບົບ POS ຕາມປະເພດທຸລະກິດ');
$pageDescription = t('ໂປຣແກຣມຂາຍໜ້າຮ້ານ ຄົບທຸກປະເພດທຸລະກິດ - ຮ້ານອາຫານ, ຮ້ານຄ້າ, ຢາ, ເຄື່ອງດື່ມ ແລະ ອື່ນໆ. ຕິດຕັ້ງພ້ອມໃຊ້ອຸປະກອນຄົບຊຸດ');
$currentPage = 'solutions';   // ເມນູ "ລະບົບ POS" (layouts/navbar.php)

$sysTab = $siteSolutions['pos'] ?? ['label' => t('ລະບົບ POS'), 'items' => []];
$sysBanner = null;
foreach (getBanners() as $b) {
    if (strtolower($b['slug']) === 'pos') {
        $sysBanner = $b;
    }
}
// ຈຸດເດັ່ນຂອງແຕ່ລະລະບົບ (ສະແດງໃນກາດ)
$sysPerks = [t('ຂາຍໜ້າຮ້ານ'), t('ສະຕັອກ'), t('ລາຍງານຍອດ'), t('ພິມບິນ')];
$isMember = function_exists('canSeePrices') ? canSeePrices() : false;
require dirname(__DIR__) . '/layouts/header.php';
?>
<section class="section sysp-section">
    <?php /* ແຖບພື້ນຫຼັງເຕັມຈໍແບບຂັ້ນ ພ້ອມໜັງສື 3D Wlaodev.com ຢູ່ເຄິ່ງກາງ ແລະ ໂລໂກ້ WD ປະດັບ */ ?>
    <div class="sysp-cover-band" aria-hidden="true">
        <span class="sysp-cover-title-3d">Wlaodev.com</span>
        <img class="sysp-cover-logo" src="<?php echo e(asset('img/wd_mark.png')); ?>" alt="" width="800" height="369">
    </div>

    <div class="container sysp-container">
        <?php if ($sysBanner): ?>
            <div class="sysp-banner" style="--img: url('<?php echo e($sysBanner['url']); ?>')">
                <span class="sysp-banner-img" style="aspect-ratio: <?php echo (int)$sysBanner['width']; ?> / <?php echo (int)$sysBanner['height']; ?>">
                    <img src="<?php echo e($sysBanner['url']); ?>" width="<?php echo (int)$sysBanner['width']; ?>" height="<?php echo (int)$sysBanner['height']; ?>"
                         alt="<?php echo e($pageTitle); ?>" fetchpriority="high" draggable="false">
                </span>
                <span class="sysp-banner-shine" aria-hidden="true"></span>
            </div>
        <?php endif; ?>

        <div class="sysp-head">
            <h1><?php echo e($pageTitle); ?></h1>
            <p><?php echo e($pageDescription); ?></p>
        </div>

        <div class="sysp-grid">
            <?php foreach ($sysTab['items'] as $i => $item):
                $img = solutionImage($item['slug']);
                $href = url('pages/solution.php?slug=' . rawurlencode($item['slug']));
                $name = $item['title']; // ບໍ່ໃສ່ "ລະບົບ POS" ຊ້ຳຊ້ອນໃນບັອກກາດ
                $desc = $item['hint'] ?? t('ລະບົບ POS ຄົບວົງຈອນ ສຳລັບ%s - ງ່າຍ, ຖືກຕ້ອງ ແລະ ຄຸ້ມຄ່າການລົງທຶນ', $item['title']);
                $price = (float)($item['price'] ?? 0);
                $oldPrice = (float)($item['old_price'] ?? 0);
                $pct = ($oldPrice > $price && $oldPrice > 0) ? (int)round((($oldPrice - $price) / $oldPrice) * 100) : 0;
            ?>
                <a class="sysp-card" href="<?php echo e($href); ?>" data-reveal style="--reveal-delay: <?php echo ($i % 4) * 90; ?>ms">
                    <span class="sysp-media">
                        <?php if ($img): ?>
                            <img src="<?php echo e($img); ?>" alt="<?php echo e($name); ?>" loading="lazy">
                        <?php else: ?>
                            <span class="sysp-icon" aria-hidden="true"><?php echo $item['icon']; ?></span>
                        <?php endif; ?>
                    </span>
                    <span class="sysp-body">
                        <strong><?php echo e($name); ?></strong>
                        <span class="sysp-desc"><?php echo e($desc); ?></span>
                        <span class="sysp-perks">
                            <?php foreach ($sysPerks as $pk): ?><span><?php echo e($pk); ?></span><?php endforeach; ?>
                        </span>
                        <?php if ($isMember && $price > 0): ?>
                            <span class="sysp-price-box">
                                <span class="sysp-price-row">
                                    <span class="sysp-price"><?php echo money($price); ?></span>
                                    <?php if ($oldPrice > $price): ?>
                                        <s class="sysp-old"><?php echo money($oldPrice); ?></s>
                                    <?php endif; ?>
                                    <?php if ($pct > 0): ?>
                                        <span class="sysp-disc-tag">-<?php echo $pct; ?>%</span>
                                    <?php endif; ?>
                                </span>
                                <?php if ($pct > 0): ?>
                                    <span class="sysp-member-note">
                                        <span class="sysp-member-dot" aria-hidden="true"></span>
                                        <span><?php echo te('ສະມາຊິກຫຼຸດ %d%% (ຈາກ %s ເຫຼືອ %s)', $pct, money($oldPrice), money($price)); ?></span>
                                    </span>
                                <?php endif; ?>
                            </span>
                        <?php elseif (!$isMember && $price > 0): ?>
                            <span class="sysp-price-box sysp-price-locked">
                                <span class="sysp-lock-label">
                                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align: -1px; margin-right: 3px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                    <?php echo te('ສະເພາະສະມາຊິກ'); ?>
                                </span>
                            </span>
                        <?php endif; ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
