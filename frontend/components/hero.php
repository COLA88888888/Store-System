<?php
/**
 * ສ່ວນເທິງສຸດຂອງໜ້າຫຼັກ (hero) — ພື້ນສີເຫຼືອງຈາງ + ວົງແສງລອຍ, ຫົວຂໍ້ພິມເອງ ແລະ ຊ່ອງຄົ້ນຫາ (real-time + ກົດປຸ່ມ → pages/search.php?q=)
 * ພ້ອມແມວນ້ອຍ SVG ໂຜ່ຫົວຊີ້ບອກບ່ອນພິມຄົ້ນຫາ (ເອັບເຟັກ: .mascot-* ໃນ assets/css/pages.css).
 * ພື້ນຫຼັງ: ເອັບເຟັກ Particles.js (#heroParticles, assets/js/hero_particles.js). ແບບໂລໂກ້ລອຍເກົ່າ: assets/js/logo_net.js
 * ຄຳທີ່ພິມວົນ = ຊື່ກ່ອງຂອງແຖບທຳອິດໃນ $siteSolutions (includes/content.php) — [data-typing] ໃນ assets/js/main.js.
 */
$heroWords = [];
if (!empty($siteSolutions)) {
    $heroWords = array_column(reset($siteSolutions)['items'], 'title');
}
?>
<section class="hero-home" data-cursor-zone>
    <!-- ເອັບເຟັກ Particles.js: ຈຸດ + ເສັ້ນເຊື່ອມ, ເມົາດຶງເສັ້ນ, ກົດເພີ່ມຈຸດ (assets/js/hero_particles.js).
         ແບບເກົ່າ (ໂລໂກ້ລອຍ): <canvas class="hero-logo-net" data-logo-net data-logo="…logo.png"> + js/logo_net.js -->
    <div class="hero-logo-net" id="heroParticles" aria-hidden="true"></div>
    <div class="hero-glow" aria-hidden="true">
        <span class="hero-orb orb-1"></span>
        <span class="hero-orb orb-2"></span>
        <span class="hero-orb orb-3"></span>
    </div>
    <div class="container hero-home-inner">
        <p class="hero-kicker"><?php echo te('ລະບົບຂາຍສຳລັບ..'); ?></p>
        <h1 class="hero-typing">
            <span class="hero-word-text" data-typing='<?php echo e(json_encode($heroWords, JSON_UNESCAPED_UNICODE)); ?>'><?php echo e($heroWords[0] ?? $company['name']); ?></span><span class="hero-caret" aria-hidden="true"></span>
        </h1>

        <div class="hero-search-wrap">
        <!-- ແມວນ້ອຍ ໂຜ່ຫົວຢູ່ຂອບເທິງຊ່ອງຄົ້ນຫາ: ຫົວຢູ່ຫຼັງກ່ອງ (ກົ້ມເບິ່ງ-ເງີຍຂຶ້ນ), ຕີນໜ້າພາດຂອບກ່ອງ ຊີ້ບອກບ່ອນພິມ "ຄົ້ນຫາສິນຄ້າ..." (ກະຕຸກ) -->
        <svg class="mascot-head" viewBox="0 0 80 64" aria-hidden="true">
            <g class="mascot-face">
                <path d="M14 30 L12 4 L32 16 Z" fill="#facc15" stroke="#eab308" stroke-width="2" stroke-linejoin="round"/>
                <path d="M66 30 L68 4 L48 16 Z" fill="#facc15" stroke="#eab308" stroke-width="2" stroke-linejoin="round"/>
                <path d="M16 11 L19 22 L26 16 Z M64 11 L61 22 L54 16 Z" fill="#fda4af" opacity=".8"/>
                <ellipse cx="40" cy="40" rx="30" ry="25" fill="#fde047" stroke="#eab308" stroke-width="2"/>
                <path d="M33 17 q2 5 0 9 M40 15 v10 M47 17 q-2 5 0 9" stroke="#eab308" stroke-width="2" fill="none" stroke-linecap="round"/>
                <g class="mascot-eyes">
                    <ellipse cx="29" cy="38" rx="4" ry="5" fill="#1c1917"/>
                    <ellipse cx="51" cy="38" rx="4" ry="5" fill="#1c1917"/>
                    <circle cx="30.3" cy="36.3" r="1.4" fill="#fff"/>
                    <circle cx="52.3" cy="36.3" r="1.4" fill="#fff"/>
                </g>
                <ellipse cx="21" cy="47" rx="4.5" ry="2.6" fill="#fb923c" opacity=".45"/>
                <ellipse cx="59" cy="47" rx="4.5" ry="2.6" fill="#fb923c" opacity=".45"/>
                <path d="M38 44.5 h4 l-2 2.3 Z" fill="#f472b6"/>
                <path d="M35.5 48.5 q2.2 2.2 4.5 0 q2.3 2.2 4.5 0" stroke="#78350f" stroke-width="1.5" fill="none" stroke-linecap="round"/>
            </g>
        </svg>
        <svg class="mascot-paw" viewBox="0 0 30 40" aria-hidden="true">
            <path d="M20 2 C24 10 22 22 15 31 C12 35 7 34 7 30 C7 22 10 12 12 2 Z" fill="#fde047" stroke="#eab308" stroke-width="2" stroke-linejoin="round"/>
            <path d="M11.5 30 C10 34 8 37.5 6 38" stroke="#eab308" stroke-width="5" stroke-linecap="round" fill="none"/>
            <path d="M11.5 30 C10 34 8 37.5 6 38" stroke="#fde047" stroke-width="2.4" stroke-linecap="round" fill="none"/>
            <path d="M14 27 q2 1 4 -1" stroke="#eab308" stroke-width="1.4" fill="none" stroke-linecap="round"/>
        </svg>
        <!-- ພິມ → ຜົນລັບ real-time ລຸ່ມຊ່ອງ (api/search.php), ລຶບໝົດ → ຫາຍ ([data-live-search] ໃນ assets/js/main.js);
             ກົດປຸ່ມ "ຄົ້ນຫາ" / Enter → ໜ້າຜົນລັບ pages/search.php -->
        <form class="hero-search" action="<?php echo url('pages/search.php'); ?>" method="get" role="search"
              data-live-search="<?php echo url('api/search.php'); ?>" data-empty-text="<?php echo te('ບໍ່ພົບລາຍການທີ່ກົງກັນ'); ?>">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><line x1="16.5" y1="16.5" x2="21" y2="21"></line></svg>
            <input type="search" name="q" placeholder="<?php echo te('ຄົ້ນຫາສິນຄ້າ, ອຸປະກອນ ຫຼື ລະບົບທີ່ທ່ານຕ້ອງການ...'); ?>" aria-label="<?php echo te('ຄົ້ນຫາ'); ?>"
                   autocomplete="off" role="combobox" aria-expanded="false" aria-controls="liveResults" aria-autocomplete="list">
            <button type="submit"><?php echo te('ຄົ້ນຫາ'); ?></button>
            <div class="live-results" id="liveResults" role="listbox" hidden></div>
        </form>
        </div>
    </div>
</section>
