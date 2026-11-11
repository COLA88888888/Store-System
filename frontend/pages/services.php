<?php
/**
 * ໜ້າບໍລິການ — ລາຍການດຶງຈາກ $siteServices (includes/content.php: title, role, text, svg).
 *   ກາດບໍລິການ (ໄອຄອນເສັ້ນ + ຊື່ + ຄຳອະທິບາຍ) → ລາຍການແບບສະຫຼັບຊ້າຍ-ຂວາ, ຂັ້ນຕອນ 4 ຂັ້ນ ແລະ ແຖບປຶກສາ (CSS .solx-* ຂອງໜ້າ solution).
 *   ກາດໝາຍຖືກສີຂຽວ (components/service_card.php) ຍັງໃຊ້ຢູ່ສະໄລ້ໜ້າຫຼັກ. CSS: pages.css ໝວດ "svp-".
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
$pageTitle = t('ບໍລິການ');
$pageDescription = t('ພັດທະນາລະບົບ POS, ເວັບໄຊ ແລະ ແອັບມືຖື ພ້ອມດູແລ 24 ຊົ່ວໂມງ');
$currentPage = 'services';
require dirname(__DIR__) . '/layouts/header.php';
require dirname(__DIR__) . '/components/page_hero.php';

$waHref = 'https://wa.me/8562055949007?text=' . rawurlencode(t('ສະບາຍດີ ສົນໃຈບໍລິການ'));
$svg = fn(string $paths, int $s = 26) => '<svg viewBox="0 0 24 24" width="' . $s . '" height="' . $s . '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';
$steps = [
    ['<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/>', t('ທັກຫາພວກເຮົາ'), t('ໂທ ຫຼື WhatsApp')],
    ['<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>', t('ປຶກສາຟຣີ'), t('ຟັງຄວາມຕ້ອງການຂອງທ່ານ')],
    ['<path d="m16 18 6-6-6-6M8 6l-6 6 6 6"/>', t('ຕິດຕັ້ງຟຣີ & ສອນໃຊ້ງານໃຫ້ຟຣີ'), t('ເຮັດໃຫ້ຕາມທີ່ຕ້ອງການ')],
    ['<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="2" y="14" width="5" height="6" rx="1.5"/><rect x="17" y="14" width="5" height="6" rx="1.5"/>', t('ດູແລ 24 ຊົ່ວໂມງ'), t('ມີບັນຫາ ແກ້ໃຫ້ທັນທີ')],
];
$phone = '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/></svg>';
?>
<section class="section svp-section">
    <div class="container">
        <div class="svp-grid">
            <?php foreach ($siteServices as $i => $s): ?>
                <article class="svp-card" data-reveal style="--reveal-delay: <?php echo ($i % 3) * 110; ?>ms">
                    <span class="svp-num" aria-hidden="true"><?php echo str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT); ?></span>
                    <span class="svp-ico"><?php echo $svg($s['svg'] ?? '<path d="M20 6 9 17l-5-5"/>'); ?></span>
                    <h2><?php echo e($s['title']); ?></h2>
                    <span class="svp-role"><?php echo e($s['role'] ?? ''); ?></span>
                    <p><?php echo e($s['text']); ?></p>
                    <a class="svp-link" href="<?php echo e('https://wa.me/8562055949007?text=' . rawurlencode(t('ສົນໃຈ %s', $s['title']))); ?>" target="_blank" rel="noopener"><?php echo te('ສອບຖາມ →'); ?></a>
                </article>
            <?php endforeach; ?>
            <!-- ກາດສຸດທ້າຍ: ເຕີມຊ່ອງວ່າງຂອງແຖວທີ 2 ດ້ວຍການຊວນຕິດຕໍ່ -->
            <a class="svp-card svp-card-cta" href="<?php echo url('pages/contact.php'); ?>" data-reveal style="--reveal-delay: 220ms">
                <strong><?php echo te('ບໍ່ເຫັນສິ່ງທີ່ຕ້ອງການ?'); ?></strong>
                <span><?php echo te('ບອກພວກເຮົາ — ລູກຄ້າຕ້ອງການແນວໃດ ພວກເຮົາເຮັດໃຫ້ໄດ້'); ?></span>
                <b><?php echo te('ຂໍໃບສະເໜີລາຄາ →'); ?></b>
            </a>
        </div>

        <div class="solx-block">
            <div class="solx-head" data-reveal>
                <span class="solx-kicker"><?php echo te('ງ່າຍ ບໍ່ຫຍຸ້ງຍາກ'); ?></span>
                <h2><?php echo te('ວິທີເຮັດວຽກກັບພວກເຮົາ'); ?></h2>
            </div>
            <ol class="solx-steps" data-reveal>
                <?php foreach ($steps as $i => [$ico, $title, $sub]): ?>
                    <li class="solx-step" style="--step-delay: <?php echo 150 + $i * 160; ?>ms">
                        <span class="solx-step-ico"><?php echo $svg($ico, 24); ?><b><?php echo $i + 1; ?></b></span>
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
