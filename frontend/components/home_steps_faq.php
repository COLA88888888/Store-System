<?php
/**
 * ໜ້າຫຼັກ (ກ່ອນແຖບ CTA): "ເລີ່ມໃຊ້ງານໄດ້ໃນ 4 ຂັ້ນຕອນ" + "ຄຳຖາມທີ່ພົບເລື້ອຍ" 5 ຂໍ້ (siteFaq() ໃນ models/solutions.php).
 * ໃຊ້ CSS .solx-* ຂອງໜ້າລະບົບ POS (assets/css/pages.css) — ເອັບເຟັກ [data-reveal] ໃນ main.js.
 */
$hsSvg = fn(string $p) => '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
$hsSteps = [
    ['<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/>', t('ທັກຫາພວກເຮົາ'), t('ໂທ ຫຼື WhatsApp')],
    ['<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>', t('ປຶກສາຟຣີ'), t('ເລືອກຊຸດທີ່ເໝາະກັບຮ້ານ')],
    ['<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>', t('ຕິດຕັ້ງຟຣີ & ສອນໃຊ້ງານໃຫ້ຟຣີ'), t('ເຖິງຮ້ານ ໃຊ້ເປັນໃນມື້ດຽວ')],
    ['<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="2" y="14" width="5" height="6" rx="1.5"/><rect x="17" y="14" width="5" height="6" rx="1.5"/>', t('ດູແລ 24 ຊົ່ວໂມງ'), t('ມີບັນຫາ ແກ້ໃຫ້ທັນທີ')],
];
?>
<section class="section home-steps-faq">
    <div class="container">
        <div class="solx-head" data-reveal>
            <span class="solx-kicker"><?php echo te('ງ່າຍ ບໍ່ຫຍຸ້ງຍາກ'); ?></span>
            <h2><?php echo te('ເລີ່ມໃຊ້ງານໄດ້ໃນ 4 ຂັ້ນຕອນ'); ?></h2>
        </div>
        <ol class="solx-steps" data-reveal>
            <?php foreach ($hsSteps as $i => [$ico, $title, $sub]): ?>
                <li class="solx-step" style="--step-delay: <?php echo 150 + $i * 160; ?>ms">
                    <span class="solx-step-ico"><?php echo $hsSvg($ico); ?><b><?php echo $i + 1; ?></b></span>
                    <strong><?php echo e($title); ?></strong>
                    <small><?php echo e($sub); ?></small>
                </li>
            <?php endforeach; ?>
        </ol>

        <div class="solx-block">
            <div class="solx-head" data-reveal>
                <span class="solx-kicker"><?php echo te('ລູກຄ້າຖາມເລື້ອຍ'); ?></span>
                <h2><?php echo te('ຄຳຖາມທີ່ພົບເລື້ອຍ'); ?></h2>
            </div>
            <div class="solx-faq-list" data-reveal>
                <?php foreach (array_slice(siteFaq(), 0, 5) as $i => $f): ?>
                    <details class="solx-faq"<?php echo $i === 0 ? ' open' : ''; ?>>
                        <summary><span><?php echo e($f['q']); ?></span><i aria-hidden="true"></i></summary>
                        <p><?php echo e($f['a']); ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
