<?php
/**
 * ສ່ວນລຸ່ມຂອງໜ້າລະບົບ POS (pages/solution.php, ສະເພາະ category 'pos') — ອ່ານໜ້ອຍ ເຂົ້າໃຈໄວ:
 *   1) ບັນຫາ → ແກ້ໄດ້   ($solution['importance']: pain + icon + title, ຂໍ້ມູນຈາກ models/solutions.php)
 *   2) ມີຫຍັງໃນລະບົບ    ($solution['features'])
 *   3) ເລີ່ມໃຊ້ 4 ຂັ້ນຕອນ
 *   4) ຄຳຖາມທີ່ພົບເລື້ອຍ ($solution['faq'], ກົດເປີດ-ປິດ)
 *   5) ແຖບປຶກສາຟຣີ      ($waHref ຈາກ solution.php)
 * ເອັບເຟັກ: [data-reveal] (main.js) ຄ່ອຍໆລອຍຂຶ້ນ; CSS ໃນ assets/css/pages.css ໝວດ "solx-".
 */
$solPains = array_values(array_filter($solution['importance'], fn($imp) => !empty($imp['pain'])));
// ໄອຄອນເສັ້ນ SVG (ບໍ່ໃຊ້ emoji)
$solSteps = [
    ['<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/>', t('ທັກຫາພວກເຮົາ'), t('ໂທ ຫຼື WhatsApp')],
    ['<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>', t('ປຶກສາຟຣີ'), t('ເລືອກຊຸດທີ່ເໝາະກັບຮ້ານ')],
    ['<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>', t('ຕິດຕັ້ງຟຣີ & ສອນໃຊ້ງານໃຫ້ຟຣີ'), t('ເຖິງຮ້ານ ໃຊ້ເປັນໃນມື້ດຽວ')],
    ['<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="2" y="14" width="5" height="6" rx="1.5"/><rect x="17" y="14" width="5" height="6" rx="1.5"/>', t('ດູແລ 24 ຊົ່ວໂມງ'), t('ມີບັນຫາ ແກ້ໃຫ້ທັນທີ')],
];
?>
<?php if ($solPains): ?>
<section class="solx-block">
    <div class="solx-head" data-reveal>
        <span class="solx-kicker"><?php echo te('%s ມັກພົບບັນຫານີ້ບໍ?', $solution['title']); ?></span>
        <h2><?php echo te('ບັນຫາຂອງທ່ານ ພວກເຮົາແກ້ໄດ້'); ?></h2>
    </div>
    <div class="solx-pf-grid">
        <?php foreach ($solPains as $i => $imp): ?>
            <div class="solx-pf" data-reveal style="--reveal-delay: <?php echo ($i % 3) * 110; ?>ms">
                <div class="solx-pf-pain">
                    <span class="solx-pf-x" aria-hidden="true">✕</span>
                    <p><?php echo e($imp['pain']); ?></p>
                </div>
                <span class="solx-pf-arrow" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M6 13l6 6 6-6"/></svg>
                </span>
                <div class="solx-pf-fix">
                    <span class="solx-pf-ok" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    </span>
                    <strong><?php echo e($imp['title']); ?></strong>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="solx-block">
    <div class="solx-head" data-reveal>
        <span class="solx-kicker"><?php echo te('ຄົບໃນລະບົບດຽວ'); ?></span>
        <h2><?php echo te('ມີຫຍັງໃນລະບົບ'); ?></h2>
    </div>
    <div class="solx-feat-grid">
        <?php foreach ($solution['features'] as $i => $feat): ?>
            <div class="solx-feat" data-reveal style="--reveal-delay: <?php echo ($i % 3) * 90; ?>ms">
                <span class="solx-feat-ok" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                </span>
                <span><?php echo e($feat); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="solx-block">
    <div class="solx-head" data-reveal>
        <span class="solx-kicker"><?php echo te('ງ່າຍ ບໍ່ຫຍຸ້ງຍາກ'); ?></span>
        <h2><?php echo te('ເລີ່ມໃຊ້ງານໄດ້ໃນ 4 ຂັ້ນຕອນ'); ?></h2>
    </div>
    <ol class="solx-steps" data-reveal>
        <?php foreach ($solSteps as $i => [$ico, $title, $sub]): ?>
            <li class="solx-step" style="--step-delay: <?php echo 150 + $i * 160; ?>ms">
                <span class="solx-step-ico" aria-hidden="true"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?php echo $ico; ?></svg><b><?php echo $i + 1; ?></b></span>
                <strong><?php echo e($title); ?></strong>
                <small><?php echo e($sub); ?></small>
            </li>
        <?php endforeach; ?>
    </ol>
</section>

<?php if (!empty($solution['faq'])): ?>
<section class="solx-block">
    <div class="solx-head" data-reveal>
        <span class="solx-kicker"><?php echo te('ລູກຄ້າຖາມເລື້ອຍ'); ?></span>
        <h2><?php echo te('ຄຳຖາມທີ່ພົບເລື້ອຍ'); ?></h2>
    </div>
    <div class="solx-faq-list" data-reveal>
        <?php foreach (array_slice($solution['faq'], 0, 5) as $i => $f): // ສູງສຸດ 5 ຂໍ້ — ລູກຄ້າບໍ່ມັກອ່ານຍາວ ?>
            <details class="solx-faq"<?php echo $i === 0 ? ' open' : ''; ?>>
                <summary><span><?php echo e($f['q']); ?></span><i aria-hidden="true"></i></summary>
                <p><?php echo e($f['a']); ?></p>
            </details>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="solx-cta" data-reveal>
    <div class="solx-cta-body">
        <div class="solx-cta-brand">
            <img src="<?php echo asset('img/logo.png'); ?>" alt="" width="22" height="15">
            <span>WlaoDev for Business</span>
        </div>
        <strong><?php echo te('ຍັງບໍ່ແນ່ໃຈວ່າແບບໃດເໝາະກັບຮ້ານ?'); ?></strong>
        <span><?php echo te('ປຶກສາຟຣີ ບໍ່ມີຄ່າໃຊ້ຈ່າຍ — ທີມງານຕອບໄວ 24 ຊົ່ວໂມງ'); ?></span>
    </div>
    <div class="solx-cta-btns">
        <?php if (!empty($chatHref)): ?>
            <a class="sol-btn is-chat" href="<?php echo e($chatHref); ?>"><?php echo te('ແຊັດສອບຖາມ'); ?></a>
        <?php endif; ?>
        <a class="sol-btn is-wa" href="<?php echo e($waHref); ?>" target="_blank" rel="noopener">WhatsApp</a>
        <a class="sol-btn sol-btn-primary" href="<?php echo url('pages/contact.php'); ?>">
            <span><?php echo te('ປຶກສາຟຣີ'); ?></span>
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
    </div>
</section>
