<?php
/**
 * ໜ້າຫຼັກຂອງເວັບໄຊ: hero ສີເຫຼືອງ ພ້ອມຊ່ອງຄົ້ນຫາ (components/hero.php), ລະບົບຕາມປະເພດທຸລະກິດ (components/solutions.php), ແບນເນີສີເຫຼືອງ + ເປັນຫຍັງຕ້ອງເລືອກ (components/promo_banner.php), ຜົນງານຕິດຕັ້ງລະບົບ (components/works.php), ໂລໂກ້ລູກຄ້າ (components/clients.php), ບໍລິການເດັ່ນ ແລະ ປຸ່ມຕິດຕໍ່.
 * ໂຄງສ້າງ frontend/ ທັງໝົດ: ເບິ່ງ frontend/README.md
 */
require __DIR__ . '/includes/bootstrap.php';
$pageTitle = '';
$pageCanonical = '';  // ໜ້າຫຼັກ = URL root (ບໍ່ມີ index.php)
$currentPage = 'home';
$pageScripts = ['vendor/particles.min.js', 'js/hero_particles.js'];   // ເອັບເຟັກ Particles.js ໃນ hero (components/hero.php); ແບບໂລໂກ້ລອຍເກົ່າ = 'js/logo_net.js'
require __DIR__ . '/layouts/header.php';
require __DIR__ . '/components/hero.php';
require __DIR__ . '/components/solutions.php';
require __DIR__ . '/components/promo_banner.php';
require __DIR__ . '/components/works.php';
require __DIR__ . '/components/clients.php';
?>

<section class="section srv-section" data-stack-under>
    <div class="container">
        <div class="section-head srv-section-head">
            <div class="srv-head-title">
                <span class="srv-live-pill"><i class="srv-live-dot" aria-hidden="true"></i><?php echo te('ພ້ອມບໍລິການ 24 ຊົ່ວໂມງ'); ?></span>
                <h2><?php echo te('ບໍລິການຂອງພວກເຮົາ'); ?></h2>
            </div>
            <div class="srv-head-nav">
                <div class="srv-nav-arrows">
                    <button type="button" class="srv-nav-btn" data-srv-prev aria-label="<?php echo te('ກ່ອນໜ້າ'); ?>">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 6-6 6 6 6"/></svg>
                    </button>
                    <button type="button" class="srv-nav-btn" data-srv-next aria-label="<?php echo te('ຖັດໄປ'); ?>">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
                    </button>
                </div>
                <a href="<?php echo url('pages/services.php'); ?>"><?php echo te('ເບິ່ງທັງໝົດ →'); ?></a>
            </div>
        </div>

        <div class="srv-slider-box" data-srv-slider>
            <div class="srv-slider-track" data-srv-track>
                <?php
                // ຈັດລຽງສະເພາະໜ້າຫຼັກໃຫ້ "ພັດທະນາລະບົບ POS" (ລາຍການທຳອິດທີ່ເປັນບໍລິການຫຼັກ) ຢູ່ເຄິ່ງກາງສະເໝີ
                $homeServices = array_values($siteServices);
                $srvMidIndex = (int) floor(count($homeServices) / 2);
                if ($srvMidIndex > 0 && isset($homeServices[0])) {
                    $posService = array_shift($homeServices);
                    array_splice($homeServices, $srvMidIndex, 0, [$posService]);
                }
                foreach ($homeServices as $srvIdx => $s):
                    $isInitialCenter = ($srvIdx === $srvMidIndex);
                ?>
                    <div class="srv-slide-col<?php echo $isInitialCenter ? ' is-center' : ''; ?>" data-srv-index="<?php echo $srvIdx; ?>">
                        <?php require __DIR__ . '/components/service_card.php'; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- ເສັ້ນແສງພຸ່ງລົງມາຕິດບັອກໂລໂກ້ WlaoDev ເລື່ອນຂຶ້ນມາທັບພາກບໍລິການ ([data-rays] ໃນ main.js) -->
<section class="section stack-over stack-over-rays" data-cursor-zone>
    <div class="stack-rays" data-rays>
        <svg class="stack-rays-svg" viewBox="0 0 1400 280" preserveAspectRatio="none" aria-hidden="true">
            <defs>
                <linearGradient id="wlaoRaysGrad" gradientUnits="userSpaceOnUse" x1="40" y1="0" x2="1360" y2="0">
                    <!-- ສີຂອງເວັບ: ຟ້າ → ເຫຼືອງ -->
                    <stop offset="0%" stop-color="#1d4ed8" stop-opacity=".4"/>
                    <stop offset="28%" stop-color="#2563eb"/>
                    <stop offset="52%" stop-color="#3b82f6"/>
                    <stop offset="76%" stop-color="#facc15"/>
                    <stop offset="100%" stop-color="#f59e0b" stop-opacity=".45"/>
                </linearGradient>
                <linearGradient id="wlaoRaysFade" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#fff" stop-opacity=".18"/>
                    <stop offset="35%" stop-color="#fff" stop-opacity=".85"/>
                    <stop offset="100%" stop-color="#fff" stop-opacity="1"/>
                </linearGradient>
                <mask id="wlaoRaysMask">
                    <rect width="1400" height="280" fill="url(#wlaoRaysFade)"/>
                </mask>
            </defs>
            <g mask="url(#wlaoRaysMask)" stroke="url(#wlaoRaysGrad)" stroke-width="3.6" stroke-linecap="round">
                <line x1="-40"  y1="0" x2="598" y2="280" pathLength="1"/>
                <line x1="55"   y1="0" x2="611" y2="280" pathLength="1"/>
                <line x1="150"  y1="0" x2="624" y2="280" pathLength="1"/>
                <line x1="245"  y1="0" x2="637" y2="280" pathLength="1"/>
                <line x1="340"  y1="0" x2="650" y2="280" pathLength="1"/>
                <line x1="435"  y1="0" x2="663" y2="280" pathLength="1"/>
                <line x1="530"  y1="0" x2="676" y2="280" pathLength="1"/>
                <line x1="620"  y1="0" x2="689" y2="280" pathLength="1"/>
                <line x1="700"  y1="0" x2="700" y2="280" pathLength="1"/>
                <line x1="780"  y1="0" x2="711" y2="280" pathLength="1"/>
                <line x1="870"  y1="0" x2="724" y2="280" pathLength="1"/>
                <line x1="965"  y1="0" x2="737" y2="280" pathLength="1"/>
                <line x1="1060" y1="0" x2="750" y2="280" pathLength="1"/>
                <line x1="1155" y1="0" x2="763" y2="280" pathLength="1"/>
                <line x1="1250" y1="0" x2="776" y2="280" pathLength="1"/>
                <line x1="1345" y1="0" x2="789" y2="280" pathLength="1"/>
                <line x1="1440" y1="0" x2="802" y2="280" pathLength="1"/>
            </g>
        </svg>
        <div class="stack-pill">
            <img src="<?php echo asset('img/logo.png'); ?>" alt="" width="39" height="26">
            <span>WlaoDev</span>
        </div>
    </div>
    <!-- ໃຕ້ບັອກ WlaoDev: ເສັ້ນແຕກກິ່ງຍາວລົງມາຫາ 4 ກາດສີອ່ອນສະໄຕລ໌ມິນິມໍ ພ້ອມຕົວເລກນັບຂຶ້ນ ([data-count] ໃນ main.js) -->
    <div class="container stack-hub" data-reveal>
        <div class="stack-hub-head">
            <span class="stack-hub-kicker"><?php echo te('ຄົບຈົບໃນບ່ອນດຽວ'); ?></span>
            <h3 class="stack-hub-title"><?php echo te('ທຸກຢ່າງທີ່ທຸລະກິດຂອງທ່ານຕ້ອງການ ຢູ່ໃນທີ່ດຽວ'); ?></h3>
        </div>
        <div class="stack-hub-grid">
            <?php foreach ([
                ['n' => count($siteSolutions['pos']['items'] ?? []), 'label' => 'ລະບົບ POS ຕາມປະເພດທຸລະກິດ',
                 'desc' => 'ຮອງຮັບຮ້ານອາຫານ, ຄາເຟ່, ມິນິມາດ, ຮ້ານຂາຍຍ່ອຍ-ຂາຍສົ່ງ ແລະ ທຸລະກິດບໍລິການທຸກຮູບແບບ',
                 'meta' => 'ຕິດຕັ້ງຟຣີ · ສອນໃຊ້ງານໃຫ້ຟຣີ',
                 'href' => url('pages/services.php'), 'tone' => 'g',
                 'icon' => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>'],
                ['n' => count($siteSolutions['hardware']['items'] ?? []), 'label' => 'ອຸປະກອນ POS ພ້ອມຕິດຕັ້ງ',
                 'desc' => 'ຈໍສຳຜັດ POS, ເຄື່ອງພິມໃບບິນ, ເຄື່ອງສະແກນບາໂຄ້ດ ແລະ ລິ້ນຊັກເກັບເງິນ ຄົບຊຸດພ້ອມໃຊ້ງານທັນທີ',
                 'meta' => 'ຮັບປະກັນອຸປະກອນ · ພ້ອມຕິດຕັ້ງເຖິງຮ້ານ',
                 'href' => url('pages/equipment_list.php'), 'tone' => 'y',
                 'icon' => '<path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M7 17v4h10v-4"/>'],
                // ກ່ອງທີ 3: ຈຳນວນຜົນງານ (ເມື່ອມີຜົນງານແທ້) — ຍັງບໍ່ມີ = ຈຳນວນບໍລິການແທນ
                hasWorks()
                    ? ['n' => count(getWorks()), 'label' => 'ຜົນງານຕິດຕັ້ງລະບົບ',
                       'desc' => 'ຮ້ານຄ້າ ແລະ ທຸລະກິດທົ່ວປະເທດທີ່ໄວ້ວາງໃຈເລືອກໃຊ້ລະບົບ ແລະ ອຸປະກອນຈາກທີມງານ WlaoDev',
                       'meta' => 'ດູແລຈິງ · ທີມງານຄົນລາວ 24 ຊົ່ວໂມງ',
                       'href' => url('pages/portfolio.php'), 'tone' => 'o',
                       'icon' => '<path d="M3 21h18M5 21V8l7-5 7 5v13"/><path d="M9 21v-6h6v6"/>']
                    : ['n' => count($siteServices), 'label' => 'ບໍລິການ ຄົບວົງຈອນ',
                       'desc' => 'ພັດທະນາລະບົບ POS, ເວັບໄຊບໍລິສັດ, ເວັບຂາຍເຄື່ອງ ແລະ ແອັບມືຖື iOS / Android ຕາມຄວາມຕ້ອງການ',
                       'meta' => 'ອອກແບບໃໝ່ · ຕາມໃຈລູກຄ້າ',
                       'href' => url('pages/services.php'), 'tone' => 'o', 'plus' => false,
                       'icon' => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.4-.6-.6-2.4z"/>'],
                ['n' => 5, 'label' => 'ພາສາ ທີ່ລະບົບຮອງຮັບ',
                 'desc' => 'ໃຊ້ງານງ່າຍດ້ວຍເມນູ ແລະ ໃບບິນພາສາລາວ ພ້ອມຮອງຮັບ ໄທ, ອັງກິດ, ຈີນ ແລະ ຫວຽດນາມ ໃນລະບົບດຽວ',
                 'meta' => 'ລາວ · ໄທ · ອັງກິດ · ຈີນ · ຫວຽດນາມ',
                 'href' => url('pages/services.php'), 'tone' => 'r', 'plus' => false,
                 'icon' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>'],
            ] as $i => $hub): ?>
                <a class="stack-hub-item tone-<?php echo $hub['tone']; ?><?php echo ($hub['plus'] ?? true) ? '' : ' no-plus'; ?>" href="<?php echo $hub['href']; ?>" style="--reveal-delay: <?php echo $i * 110; ?>ms">
                    <!-- ຄໍຕໍ່: ເສັ້ນຈາກຈຸດເທິງບານອອກເປັນຂອບກາດ (ສີຕາມເສັ້ນຂອງກາດ) -->
                    <svg class="stack-hub-neck" viewBox="0 0 48 56" preserveAspectRatio="none" aria-hidden="true">
                        <path d="M2 56 Q19 56 20.5 43 L27.5 43 Q29 56 46 56 Z" fill="#fff"/>
                        <path d="M0 55.5 Q19.5 55.5 20.5 42 L20.5 2 M48 55.5 Q28.5 55.5 27.5 42 L27.5 2" fill="none" stroke="currentColor" stroke-width="1" vector-effect="non-scaling-stroke" stroke-linecap="round"/>
                    </svg>
                    <div class="stack-hub-top">
                        <span class="stack-hub-icon"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?php echo $hub['icon']; ?></svg></span>
                        <b data-count="<?php echo (int) $hub['n']; ?>"><?php echo (int) $hub['n']; ?></b>
                    </div>
                    <strong class="stack-hub-label"><?php echo te($hub['label']); ?></strong>
                    <p class="stack-hub-desc"><?php echo te($hub['desc']); ?></p>
                    <div class="stack-hub-foot">
                        <small class="stack-hub-meta"><?php echo te($hub['meta']); ?></small>
                        <span class="stack-hub-more"><?php echo te('ເບິ່ງລາຍລະອຽດ'); ?> →</span>
                    </div>
                    <span class="stack-hub-watermark" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="120" height="120" fill="none" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"><?php echo $hub['icon']; ?></svg>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/components/home_steps_faq.php'; /* 4 ຂັ້ນຕອນ + ຄຳຖາມທີ່ພົບເລື້ອຍ */ ?>

<section class="section cta" data-cursor-zone>
    <div class="container cta-inner" data-reveal>
        <span class="cta-badge"><i class="cta-dot" aria-hidden="true"></i><?php echo te('ປຶກສາພວກເຮົາ'); ?></span>
        <h2><?php echo te('ມີໂປຣເຈັກໃນໃຈບໍ?'); ?></h2>
        <p><?php echo te('ບອກພວກເຮົາວ່າທ່ານຕ້ອງການຫຍັງ ພວກເຮົາຈະຕິດຕໍ່ກັບພາຍໃນ 24 ຊົ່ວໂມງ.'); ?></p>
        <div class="cta-actions">
            <a class="btn cta-btn-main" href="<?php echo url('pages/contact.php'); ?>">
                <span><?php echo te('ຕິດຕໍ່ພວກເຮົາ'); ?></span>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
            <a class="btn cta-btn-wa" href="https://wa.me/8562055949007" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" width="17" height="17" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.5 14.1c-.2.6-1.3 1.2-1.8 1.3-.5.1-1.1.1-1.8-.1a16.4 16.4 0 0 1-6.5-5.7c-.9-1.2-1.5-2.6-1.5-3.9 0-1.3.7-2 1-2.3.3-.3.6-.3.8-.3h.6c.2 0 .5 0 .7.5.2.6.8 2 .9 2.1.1.2.1.4 0 .6-.1.2-.2.4-.4.6l-.4.5c-.1.1-.3.3-.1.6.2.3.8 1.4 1.8 2.2 1.2 1.1 2.2 1.4 2.5 1.6.3.1.5.1.7-.1.2-.2.8-.9 1-1.2.2-.3.4-.2.7-.1.3.1 1.8.9 2.1 1 .3.2.5.2.6.4.1.1.1.7-.1 1.3z"/></svg>
                <span>WhatsApp: +856 20 55 949 007</span>
            </a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/layouts/footer.php'; ?>
