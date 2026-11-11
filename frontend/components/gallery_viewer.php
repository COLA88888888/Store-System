<?php
/**
 * ເບິ່ງຮູບເຕັມຈໍ (ຮູບສິນຄ້າ pages/product.php, ຮູບລະບົບ pages/solution.php) — ກົດຮູບໃຫຍ່ / ປຸ່ມ ⤢ ໃນແກເລີຣີ #solutionGallery:
 *   ເທິງຊ້າຍ: ລຳດັບ "4 / 12" | ເທິງຂວາ: ສະແດງ/ເຊື່ອງ ແຖບຮູບນ້ອຍ, ຊູມເຂົ້າ, ຊູມອອກ, ສະໄລອັດຕະໂນມັດ, ເຕັມຈໍ, ປິດ
 *   ກາງ: ຮູບ + ລູກສອນຊ້າຍ/ຂວາ | ລຸ່ມ: ແຖບຮູບນ້ອຍ (ກົດເລືອກ)
 *   ຊູມແລ້ວລາກເລື່ອນເບິ່ງໄດ້, ກົດສອງເທື່ອ = ຊູມ/ຄືນ, ລໍ້ເມົາ = ຊູມ, ປັດ (ໂທລະສັບ) = ຮູບຖັດໄປ; ຄີ ← → + − Esc
 * ຮູບມາຈາກ .thumb-btn[data-img] ຂອງແກເລີຣີ (ຫຼື #galleryMainImg ຖ້າມີຮູບດຽວ). JS: assets/js/gallery_viewer.js, CSS: assets/css/gallery_viewer.css
 */
$gvIco = fn(string $d, int $s = 22) => '<svg viewBox="0 0 24 24" width="' . $s . '" height="' . $s . '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
?>
<link rel="stylesheet" href="<?php echo asset('css/gallery_viewer.css'); ?>">
<div class="gv" id="galleryViewer" hidden role="dialog" aria-modal="true" aria-label="<?php echo te('ເບິ່ງຮູບເຕັມຈໍ'); ?>">
    <div class="gv-top">
        <span class="gv-count" data-gv-count>1 / 1</span>
        <div class="gv-tools">
            <button type="button" data-gv="thumbs" title="<?php echo te('ຮູບທັງໝົດ'); ?>" aria-label="<?php echo te('ຮູບທັງໝົດ'); ?>"><?php echo $gvIco('<rect x="3" y="3" width="5" height="5" rx="1"/><rect x="9.5" y="3" width="5" height="5" rx="1"/><rect x="16" y="3" width="5" height="5" rx="1"/><rect x="3" y="9.5" width="5" height="5" rx="1"/><rect x="9.5" y="9.5" width="5" height="5" rx="1"/><rect x="16" y="9.5" width="5" height="5" rx="1"/><rect x="3" y="16" width="5" height="5" rx="1"/><rect x="9.5" y="16" width="5" height="5" rx="1"/><rect x="16" y="16" width="5" height="5" rx="1"/>'); ?></button>
            <button type="button" data-gv="zin" title="<?php echo te('ຊູມເຂົ້າ'); ?>" aria-label="<?php echo te('ຊູມເຂົ້າ'); ?>"><?php echo $gvIco('<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5M11 8v6M8 11h6"/>'); ?></button>
            <button type="button" data-gv="zout" title="<?php echo te('ຊູມອອກ'); ?>" aria-label="<?php echo te('ຊູມອອກ'); ?>"><?php echo $gvIco('<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5M8 11h6"/>'); ?></button>
            <button type="button" data-gv="play" title="<?php echo te('ສະໄລອັດຕະໂນມັດ'); ?>" aria-label="<?php echo te('ສະໄລອັດຕະໂນມັດ'); ?>"><span class="gv-play"><?php echo $gvIco('<path d="M7 4.5v15l12-7.5z" fill="currentColor" stroke="none"/>'); ?></span><span class="gv-pause"><?php echo $gvIco('<rect x="6" y="4.5" width="4" height="15" rx="1" fill="currentColor" stroke="none"/><rect x="14" y="4.5" width="4" height="15" rx="1" fill="currentColor" stroke="none"/>'); ?></span></button>
            <button type="button" data-gv="full" title="<?php echo te('ເຕັມຈໍ'); ?>" aria-label="<?php echo te('ເຕັມຈໍ'); ?>"><?php echo $gvIco('<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>'); ?></button>
            <button type="button" data-gv="close" title="<?php echo te('ປິດ'); ?>" aria-label="<?php echo te('ປິດ'); ?>"><?php echo $gvIco('<path d="M5 5l14 14M19 5 5 19"/>', 24); ?></button>
        </div>
    </div>
    <div class="gv-stage" data-gv-stage>
        <img class="gv-img" data-gv-img src="" alt="" draggable="false">
        <p class="gv-cap" data-gv-cap hidden></p>
    </div>
    <button type="button" class="gv-nav is-prev" data-gv="prev" aria-label="<?php echo te('ຮູບກ່ອນໜ້າ'); ?>"><?php echo $gvIco('<path d="M19 12H5M11 6l-6 6 6 6"/>', 26); ?></button>
    <button type="button" class="gv-nav is-next" data-gv="next" aria-label="<?php echo te('ຮູບຖັດໄປ'); ?>"><?php echo $gvIco('<path d="M5 12h14M13 6l6 6-6 6"/>', 26); ?></button>
    <div class="gv-thumbs" data-gv-thumbs></div>
</div>
<script src="<?php echo asset('js/gallery_viewer.js'); ?>"></script>
