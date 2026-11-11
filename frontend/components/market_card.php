<?php
/**
 * ບັດສິນຄ້າແບບຕະຫຼາດ (ຫົວບັດ = ໂລໂກ້ + ຊື່ຮ້ານ, ຮູບຈິງ, ຊື່ສິນຄ້າ) — ໃຊ້ໃນ pages/equipment.php & equipment_list.php
 */
?>
<a class="mk-card is-loading" href="<?php echo e($card['link']); ?>">
    <?php require __DIR__ . '/market_skeleton.php'; ?>
    <span class="mk-seller">
        <img src="<?php echo asset('img/logo.png'); ?>" alt="" width="32" height="32">
        <span class="mk-seller-text">
            <strong><?php echo e($company['name'] ?? 'Wlaodev'); ?></strong>
            <small><?php echo te('ຂາຍ ແລະ ຕິດຕັ້ງໂດຍທີມງານ'); ?></small>
        </span>
    </span>
    <span class="mk-thumb">
        <?php if (!empty($card['img'])): ?>
            <img src="<?php echo e($card['img']); ?>" alt="<?php echo e($card['title']); ?>" loading="lazy">
        <?php else: ?>
            <span class="mk-icon" aria-hidden="true"><?php echo $card['icon'] ?? '📦'; ?></span>
        <?php endif; ?>
    </span>
    <span class="mk-body">
        <strong class="mk-title"><?php echo e($card['title']); ?></strong>
        <?php if (!empty($card['price'])): ?>
            <span class="mk-price-box">
                <?php if (!empty($card['old'])): ?><s class="mk-old"><?php echo e($card['old']); ?></s><?php endif; ?>
                <span class="mk-price"><?php echo e($card['price']); ?></span>
            </span>
        <?php elseif (empty($card['is_member'])): ?>
            <span class="mk-price-box mk-price-locked">
                <span class="mk-lock-label">
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align: -1px; margin-right: 3px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <?php echo te('ສະເພາະສະມາຊິກ'); ?>
                </span>
            </span>
        <?php endif; ?>
    </span>
</a>
