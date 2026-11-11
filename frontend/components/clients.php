<?php
/**
 * ໜ້າຫຼັກ: ໂລໂກ້ຮ້ານຄ້າ ແລະ ທຸລະກິດ ທີ່ໃຊ້ບໍລິການ WlaoDev (components/clients.php)
 * ອອກແບບແບບ Minimal Corporate Showcase (ແບບ Fastwork):
 *   - ຫົວຂໍ້ດຽວສະອາດຕາ: "ຮ້ານຄ້າ ແລະ ທຸລະກິດຊັ້ນນຳຕ່າງໆ ໄວ້ວາງໃຈ ແລະ ເລືອກໃຊ້ບໍລິການຂອງ WlaoDev"
 *   - ສະແດງໂລໂກ້ 2 ແຖວ ເຄື່ອນໄຫວລອຍໄປ (Dual Smooth Floating Marquee)
 *   - ໃນໂທລະສັບສະແດງ 2 ແຖວ ແຖວລະ 5 ໂລໂກ້ພໍດີຈໍ
 */
$clientLogos = getClientLogos();
if (!$clientLogos) {
    return;
}

// ແບ່ງໂລໂກ້ອອກເປັນ 2 ແຖວ (Row 1 ແລະ Row 2)
$row1Raw = [];
$row2Raw = [];
foreach ($clientLogos as $idx => $item) {
    if ($idx % 2 === 0) {
        $row1Raw[] = $item;
    } else {
        $row2Raw[] = $item;
    }
}
if (empty($row2Raw)) {
    $row2Raw = $row1Raw;
}

/**
 * ສ້າງລາຍການໂລໂກ້ໃຫ້ເຄິ່ງໜຶ່ງ (Half 1) ມີຢ່າງໜ້ອຍ 15 ໄອເທັມ
 * ແລ້ວນຳ Half 1 + Half 2 ມາຕໍ່ກັນ ເພື່ອໃຫ້ loop translateX(-50%) ຕໍ່ເນື່ອງບໍ່ມີສະດຸດ
 */
$buildTrack = function (array $logos, int $minHalf = 15): array {
    if (empty($logos)) {
        return [];
    }
    $half = [];
    while (count($half) < $minHalf) {
        $half = array_merge($half, $logos);
    }
    return array_merge($half, $half);
};

$displayRow1 = $buildTrack($row1Raw, 15);
$displayRow2 = $buildTrack($row2Raw, 15);
?>
<section class="section cl-section" id="clients-section">
    <div class="container">
        <!-- ຫົວຂໍ້ດຽວສະອາດແບບ Fastwork -->
        <div class="cl-head">
            <h2 class="cl-title">
                <?php echo te('ຮ້ານຄ້າ ແລະ ທຸລະກິດຊັ້ນນຳຕ່າງໆ ໄວ້ວາງໃຈ ແລະ ເລືອກໃຊ້ບໍລິການຂອງ'); ?> <span class="cl-brand-highlight">WlaoDev</span>
            </h2>
        </div>
    </div>

    <!-- ໂລໂກ້ 2 ແຖວ ເຄື່ອນໄຫວລອຍໄປ (Smooth Floating Marquee) -->
    <div class="cl-slide-container" aria-label="<?php echo te('ລູກຄ້າທີ່ໄວ້ວາງໃຈ WlaoDev'); ?>">
        <!-- Row 1: ສະໄລ້ໄປຊ້າຍ -->
        <div class="cl-marquee-row cl-row-1">
            <div class="cl-track cl-track-left">
                <?php foreach ($displayRow1 as $logo): ?>
                    <div class="cl-item" title="<?php echo e($logo['name']); ?>">
                        <img class="cl-img" src="<?php echo e($logo['url']); ?>" alt="<?php echo e($logo['name']); ?>" loading="lazy" draggable="false">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Row 2: ສະໄລ້ໄປຂວາ -->
        <div class="cl-marquee-row cl-row-2">
            <div class="cl-track cl-track-right">
                <?php foreach ($displayRow2 as $logo): ?>
                    <div class="cl-item" title="<?php echo e($logo['name']); ?>">
                        <img class="cl-img" src="<?php echo e($logo['url']); ?>" alt="<?php echo e($logo['name']); ?>" loading="lazy" draggable="false">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
