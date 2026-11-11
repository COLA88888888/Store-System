<?php
/**
 * ໜ້າຫຼັກ: ພາກ "ຜົນງານຕິດຕັ້ງລະບົບ" ຖັດຈາກແບນເນີສີເຫຼືອງ (components/promo_banner.php) — ກອບສີຟ້າອ່ອນ, ຫົວຂໍ້ກາງ,
 * ສະໄລດ໌ 3 ບັດຕໍ່ໜ້າ (ບັດແບບເກົ່າ components/work_card.php) + ປຸ່ມ ‹ › ສອງຂ້າງ + ຈຸດບອກໜ້າຢູ່ລຸ່ມ ([data-carousel] ໃນ main.js)
 * + "ເບິ່ງທັງໝົດ" → pages/portfolio.php. ບໍ່ມີຜົນງານ → ບໍ່ສະແດງພາກນີ້. CSS: .wk-panel / .wk-track / .wk-nav / .wk-dots ໃນ pages.css
 */
$homeWorks = getWorks(12);
if (!$homeWorks) {
    return;
}
?>
<section class="section stack-over wk-section">
    <div class="container">
        <div class="wk-panel" data-carousel data-reveal>
            <div class="wk-panel-head">
                <p class="wk-kicker"><?php echo te('ຜົນງານຂອງພວກເຮົາ'); ?></p>
                <h2><?php echo te('ຕິດຕັ້ງລະບົບ ແລະ ສອນລູກຄ້າໃຊ້ງານລະບົບ'); ?></h2>
                <a class="eq-all" href="<?php echo url('pages/portfolio.php'); ?>"><?php echo te('ເບິ່ງທັງໝົດ'); ?></a>
            </div>
            <button type="button" class="wk-nav wk-prev" data-car-prev aria-label="<?php echo te('ກ່ອນໜ້າ'); ?>">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 6-6 6 6 6"/></svg>
            </button>
            <div class="wk-track" data-car-track>
                <?php foreach ($homeWorks as $w): ?>
                    <?php require __DIR__ . '/work_card.php'; ?>
                <?php endforeach; ?>
            </div>
            <button type="button" class="wk-nav wk-next" data-car-next aria-label="<?php echo te('ຖັດໄປ'); ?>">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
            </button>
            <div class="wk-dots" data-car-dots></div>
        </div>
    </div>
</section>
