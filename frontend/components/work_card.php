<?php
/**
 * ບັດຜົນງານ 1 ອັນ: ຮູບ 3 ຊ່ອງ (ໃຫຍ່ຊ້າຍ + 2 ນ້ອຍຂວາ, ຊ່ອງສຸດທ້າຍ "+N" ເມື່ອມີຮູບຫຼາຍກວ່າ), ຊື່, ວັນທີ, ລາຍລະອຽດ, "ເບິ່ງຮູບເພີ່ມເຕີມ".
 * ຜົນງານທີ່ຕັ້ງ 'join' => true (includes/content.php): ຮູບ 1 + 2 ວາງຊ້າຍ-ຂວາ ຕໍ່ກັນບໍ່ມີຮອຍ ເຕັມບ່ອນຮູບ (.wk-join).
 * ຍັງບໍ່ມີຮູບ → ພື້ນສີ + ຕົວອັກສອນທຳອິດ. ມີ category → data-filter-item (ປຸ່ມກັ່ນຕອງໃນ pages/portfolio.php).
 * ຕ້ອງການ $w (ຈາກ getWorks() ໃນ models/works.php). ໃຊ້ໃນ components/works.php ແລະ pages/portfolio.php. CSS: .wk-* ໃນ assets/css/pages.css
 */
$photos = $w['photos'];
$join = !empty($w['join']) && count($photos) >= 2;   // 'join' => true: ຮູບ 1 + 2 ຕັດມາຈາກຮູບດຽວ → ວາງຕໍ່ກັນບໍ່ມີຮອຍ
$shown = array_slice($photos, 0, $join ? 2 : 3);
$more = count($photos) - count($shown);
$link = url('pages/work.php?w=' . rawurlencode($w['slug']));
?>
<a class="wk-card skel is-loading" href="<?php echo e($link); ?>"<?php echo !empty($w['category']) ? ' data-filter-item="' . e($w['category']) . '"' : ''; ?>>
    <span class="wk-photos <?php echo $join ? 'wk-join' : 'wk-n' . count($shown); ?>">
        <?php if (!$shown): ?>
            <span class="wk-ph wk-ph-empty" aria-hidden="true"><?php echo e(mb_substr($w['title'], 0, 1)); ?></span>
        <?php endif; ?>
        <?php foreach ($shown as $i => $src): ?>
            <span class="wk-ph">
                <img src="<?php echo e($src); ?>" alt="<?php echo $i === 0 ? e($w['title']) : ''; ?>" loading="lazy">
                <?php if ($more > 0 && $i === count($shown) - 1): ?><span class="wk-more">+<?php echo $more; ?></span><?php endif; ?>
            </span>
        <?php endforeach; ?>
    </span>
    <span class="wk-body">
        <?php /* ບັດຜົນງານ: ບໍ່ສະແດງໂລໂກ້ ແລະ ຫົວຂໍ້ — ສະແດງແຕ່ຊື່ຮ້ານ + ໝວດ, ວັນທີ ແລະ ລາຍລະອຽດ */ ?>
        <?php if (!empty($w['shop_name']) || !empty($w['category'])): ?>
            <span class="wk-client-head">
                <?php if (!empty($w['shop_name'])): ?>
                    <span class="wk-client-name"><?php echo e($w['shop_name']); ?></span>
                <?php endif; ?>
                <?php if (!empty($w['category'])): ?><span class="wk-cat"><?php echo e($w['category']); ?></span><?php endif; ?>
            </span>
        <?php endif; ?>
        <?php if (!empty($w['date'])): ?>
            <span class="wk-date">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M9 15l2 2 4-4"/></svg>
                <?php echo e(workDate($w['date'])); ?>
            </span>
        <?php endif; ?>
        <span class="wk-text"><?php echo e($w['text']); ?></span>
        <?php if (mb_strlen($w['text']) > 55): ?>
            <button type="button" class="wk-expand" data-wk-expand><?php echo te('ອ່ານເພີ່ມເຕີມ...'); ?></button>
        <?php endif; ?>
        <span class="wk-link"><?php echo $photos ? te('ເບິ່ງຮູບເພີ່ມເຕີມ') : te('ເບິ່ງລາຍລະອຽດ'); ?> →</span>
    </span>
</a>
