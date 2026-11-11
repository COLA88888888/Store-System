<?php
/**
 * ໜ້າກ່ຽວກັບພວກເຮົາ — ອ່ານໜ້ອຍ ເບິ່ງເຂົ້າໃຈໄວ:
 *   1) ແນະນຳ (ຂໍ້ຄວາມສັ້ນ + ຮູບ + ປຸ່ມ)   2) ຕົວເລກນັບຂຶ້ນ ([data-count] ໃນ main.js — ຂໍ້ມູນຈິງຈາກເວັບ)
 *   3) ພວກເຮົາເຮັດຫຍັງ ($siteServices)      4) ເປັນຫຍັງຕ້ອງເລືອກພວກເຮົາ
 *   5) ພາລະກິດ                            6) ວິທີເຮັດວຽກ 4 ຂັ້ນຕອນ + ແຖບປຶກສາ (ໃຊ້ CSS .solx-* ຂອງໜ້າ solution)
 * CSS: assets/css/pages.css ໝວດ "abt-". ເອັບເຟັກ: [data-reveal].
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
$pageTitle = t('ກ່ຽວກັບພວກເຮົາ');
$pageDescription = t('ທີມພັດທະນາລະບົບ POS, ເວັບໄຊ ແລະ ແອັບມືຖື ທີ່ເຂົ້າໃຈທຸລະກິດລາວ');
$currentPage = 'about';
require dirname(__DIR__) . '/layouts/header.php';

$waHref = 'https://wa.me/8562055949007?text=' . rawurlencode(t('ສະບາຍດີ ສົນໃຈປຶກສາລະບົບ'));
$stats = [
    [count($siteSolutions['pos']['items'] ?? []), t('ລະບົບ POS ຕາມປະເພດທຸລະກິດ')],
    hasWorks() ? [count(getWorks()), t('ຜົນງານຕິດຕັ້ງລະບົບ')] : [count($siteServices), t('ບໍລິການ ຄົບວົງຈອນ')],   // ຍັງບໍ່ມີຜົນງານແທ້ → ນັບບໍລິການແທນ
    [5, t('ພາສາ ທີ່ລະບົບຮອງຮັບ'), false],
    [24, t('ຊົ່ວໂມງ ດູແລຫຼັງການຂາຍ'), false],
];
// ໄອຄອນບໍລິການ = 'svg' ໃນ $siteServices (includes/content.php)
$whys = $siteWhys;   // includes/content.php — ໃຊ້ຮ່ວມກັບໜ້າຫຼັກ (components/home_why.php)
$steps = [
    ['<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/>', t('ທັກຫາພວກເຮົາ'), t('ໂທ ຫຼື WhatsApp')],
    ['<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>', t('ປຶກສາຟຣີ'), t('ຟັງຄວາມຕ້ອງການຂອງທ່ານ')],
    ['<path d="m16 18 6-6-6-6M8 6l-6 6 6 6"/>', t('ຕິດຕັ້ງຟຣີ & ສອນໃຊ້ງານໃຫ້ຟຣີ'), t('ເຮັດໃຫ້ຕາມທີ່ຕ້ອງການ')],
    ['<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="2" y="14" width="5" height="6" rx="1.5"/><rect x="17" y="14" width="5" height="6" rx="1.5"/>', t('ດູແລ 24 ຊົ່ວໂມງ'), t('ມີບັນຫາ ແກ້ໃຫ້ທັນທີ')],
];
$svg = fn(string $paths, int $s = 24) => '<svg viewBox="0 0 24 24" width="' . $s . '" height="' . $s . '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';
?>
<section class="abt-hero">
    <div class="container abt-hero-grid">
        <div class="abt-hero-text" data-reveal>
            <span class="srv-live-pill"><i class="srv-live-dot" aria-hidden="true"></i><?php echo te('ກ່ຽວກັບພວກເຮົາ'); ?></span>
            <h1><?php echo te('ພວກເຮົາສ້າງລະບົບ ໃຫ້ທຸລະກິດລາວ ຂາຍງ່າຍຂຶ້ນ'); ?></h1>
            <p><?php echo te('%s ພັດທະນາລະບົບ POS, ເວັບໄຊ ແລະ ແອັບມືຖື ເປັນພາສາລາວ ໃຊ້ງານງ່າຍ — ລູກຄ້າຕ້ອງການແນວໃດ ພວກເຮົາເຮັດໃຫ້ໄດ້.', $company['name']); ?></p>
            <div class="abt-hero-btns">
                <a class="btn btn-primary" href="<?php echo url('pages/contact.php'); ?>"><?php echo te('ປຶກສາຟຣີ'); ?></a>
                <?php if (hasWorks()): ?>
                    <a class="btn abt-btn-ghost" href="<?php echo url('pages/portfolio.php'); ?>"><?php echo te('ເບິ່ງຜົນງານ →'); ?></a>
                <?php else: ?>
                    <a class="btn abt-btn-ghost" href="<?php echo url('pages/services.php'); ?>"><?php echo te('ເບິ່ງບໍລິການ →'); ?></a>
                <?php endif; ?>
            </div>
        </div>
        <div class="abt-hero-media" data-reveal style="--reveal-delay: 150ms">
            <?php $abtHeroBanner = getBannerBySlug('wlaodev'); /* ແບນເນີສີເຫຼືອງ (backend/assets/img/baner/wlaodev.*, models/banners.php) — ຂໍ້ຄວາມຊ້າຍຄືເກົ່າ */ ?>
            <img class="abt-hero-banner" src="<?php echo e($abtHeroBanner['url'] ?? asset('img/demo/pos_retail_counter.jpg')); ?>" alt="<?php echo te('ລະບົບ POS ໜ້າຮ້ານ'); ?>" width="<?php echo (int)($abtHeroBanner['width'] ?? 1200); ?>" height="<?php echo (int)($abtHeroBanner['height'] ?? 900); ?>" loading="eager">
            <div class="abt-float abt-float-a"><b>24/7</b><span><?php echo te('ດູແລຫຼັງການຂາຍ'); ?></span></div>
        </div>
    </div>
</section>

<section class="abt-stats-wrap">
    <div class="container">
        <div class="abt-stats" data-reveal>
            <?php foreach ($stats as $st): [$n, $label] = $st; $plus = $st[2] ?? true; /* false = ບໍ່ມີ + */ ?>
                <div class="abt-stat<?php echo $plus ? '' : ' no-plus'; ?>"><b data-count="<?php echo (int)$n; ?>"><?php echo (int)$n; ?></b><span><?php echo e($label); ?></span></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section abt-section">
    <div class="container">
        <div class="solx-head" data-reveal>
            <span class="solx-kicker"><?php echo te('ສິ່ງທີ່ພວກເຮົາເຮັດ'); ?></span>
            <h2><?php echo te('ບໍລິການຂອງພວກເຮົາ'); ?></h2>
        </div>
        <div class="abt-svc-grid">
            <?php foreach ($siteServices as $i => $s): ?>
                <a class="abt-svc" href="<?php echo url('pages/services.php'); ?>" data-reveal style="--reveal-delay: <?php echo $i * 90; ?>ms">
                    <span class="abt-svc-ico"><?php echo $svg($s['svg'] ?? '<path d="M20 6 9 17l-5-5"/>'); ?></span>
                    <strong><?php echo e($s['title']); ?></strong>
                    <small><?php echo e($s['role'] ?? ''); ?></small>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section abt-section abt-why">
    <div class="container">
        <div class="solx-head" data-reveal>
            <span class="solx-kicker"><?php echo te('ຈຸດເດັ່ນ'); ?></span>
            <h2><?php echo te('ເປັນຫຍັງຕ້ອງເລືອກພວກເຮົາ'); ?></h2>
        </div>
        <div class="abt-why-grid">
            <?php foreach ($whys as $i => [$ico, $title, $sub]): ?>
                <div class="abt-why-item" data-reveal style="--reveal-delay: <?php echo $i * 110; ?>ms">
                    <span class="abt-why-ico"><?php echo $svg($ico, 26); ?></span>
                    <strong><?php echo e($title); ?></strong>
                    <p><?php echo e($sub); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section abt-section">
    <div class="container">
        <?php $abtPosBanner = getBannerBySlug('POS'); /* ພື້ນຫຼັງກາດ: ແບນເນີລະບົບ POS ແບບຈາງຂາວ + ມົວ (.abt-mission::before ໃນ pages.css) */ ?>
        <div class="abt-mission" data-reveal<?php echo !empty($abtPosBanner['url']) ? ' style="--abt-mission-bg: url(' . e($abtPosBanner['url']) . ')"' : ''; ?>>
            <div class="abt-mission-body">
                <div class="abt-mission-brand">
                    <img src="<?php echo asset('img/logo.png'); ?>" alt="" width="26" height="17">
                    <span>WlaoDev</span>
                    <span class="abt-mission-badge"><?php echo te('ພາລະກິດຂອງພວກເຮົາ'); ?></span>
                </div>
                <h3 class="abt-mission-title"><?php echo te('ຊ່ວຍໃຫ້ທຸລະກິດລາວທຸກຂະໜາດ ໃຊ້ເຕັກໂນໂລຢີໄດ້ງ່າຍ ແລະ ເຕີບໂຕໄປພ້ອມກັນ'); ?></h3>
                <p class="abt-mission-desc"><?php echo te('WlaoDev ພັດທະນາລະບົບ POS ແລະ ເວັບໄຊ ທີ່ຕອບໂຈດທຸລະກິດລາວຢ່າງແທ້ຈິງ ຄົບຈົບໃນບ່ອນດຽວ'); ?></p>
            </div>
            <div class="abt-mission-visual">
                <a class="abt-mission-btn" href="<?php echo url('pages/services.php'); ?>">
                    <span><?php echo te('ເລືອກເບິ່ງບໍລິການ'); ?></span>
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
            </div>
        </div>

        <div class="solx-block">
            <div class="solx-head" data-reveal>
                <span class="solx-kicker"><?php echo te('ງ່າຍ ບໍ່ຫຍຸ້ງຍາກ'); ?></span>
                <h2><?php echo te('ວິທີເຮັດວຽກກັບພວກເຮົາ'); ?></h2>
            </div>
            <ol class="solx-steps" data-reveal>
                <?php foreach ($steps as $i => [$ico, $title, $sub]): ?>
                    <li class="solx-step" style="--step-delay: <?php echo 150 + $i * 160; ?>ms">
                        <span class="solx-step-ico"><?php echo $svg($ico); ?><b><?php echo $i + 1; ?></b></span>
                        <strong><?php echo e($title); ?></strong>
                        <small><?php echo e($sub); ?></small>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>

        <section class="solx-cta" data-reveal>
            <div class="solx-cta-body">
                <div class="solx-cta-brand">
                    <img src="<?php echo asset('img/logo.png'); ?>" alt="" width="22" height="15">
                    <span>WlaoDev for Business</span>
                </div>
                <strong><?php echo te('ມີໂປຣເຈັກໃນໃຈບໍ?'); ?></strong>
                <span><?php echo te('ປຶກສາຟຣີ ບໍ່ມີຄ່າໃຊ້ຈ່າຍ — ທີມງານຕອບໄວ 24 ຊົ່ວໂມງ'); ?></span>
            </div>
            <div class="solx-cta-btns">
                <a class="sol-btn is-wa" href="<?php echo e($waHref); ?>" target="_blank" rel="noopener">WhatsApp</a>
                <a class="sol-btn sol-btn-primary" href="<?php echo url('pages/contact.php'); ?>">
                    <span><?php echo te('ປຶກສາຟຣີ'); ?></span>
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
            </div>
        </section>
    </div>
</section>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
