<?php
/**
 * ແຖວອໍເດີ 1 ລາຍການ (ກົດ → pages/my_order.php) — ໃຊ້ໃນ pages/account.php (ອໍເດີຫຼ້າສຸດ) ແລະ pages/my_orders.php.
 * ຕົວແປ: $o (ອໍເດີ + items ຈາກ memberOrders). ສະຖານະ: orderStatusMeta() (models/orders.php)
 */
$oMeta = orderStatusMeta((string)$o['status']);
$oItems = $o['items'] ?? [];
$oFirst = $oItems[0]['product_name'] ?? '';
$oMore = count($oItems) - 1;
$oTotal = orderDisplayTotal($o);
$oNeedSlip = empty($o['slip_img']) && in_array($o['status'], ['pending', 'confirmed'], true);
?>
<a class="mo-row" href="<?php echo url('pages/my_order.php?no=' . rawurlencode((string)$o['order_no'])); ?>">
    <img class="mo-row-img" src="<?php echo e($oMeta['img']); ?>" alt="" width="44" height="44" loading="lazy">
    <span class="mo-row-main">
        <b><?php echo e($o['order_no']); ?></b>
        <span class="mo-row-items"><?php echo e($oFirst); ?><?php echo $oMore > 0 ? ' ' . te('+ ອີກ %d ລາຍການ', $oMore) : ''; ?></span>
        <small><?php echo e(date('d/m/Y H:i', strtotime($o['created_at']))); ?><?php echo !empty($o['tracking_no']) ? ' · ' . te('ເລກໃບຝາກ') . ' ' . e($o['tracking_no']) : ''; ?></small>
    </span>
    <span class="mo-row-side">
        <span class="mo-badge is-<?php echo e($oMeta['tone']); ?>"><?php echo e($oMeta['label']); ?></span>
        <?php if ($oTotal > 0): ?><b><?php echo e(money($oTotal)); ?></b><?php endif; ?>
        <?php if ($oNeedSlip): ?><small class="mo-need-slip"><?php echo te('ຍັງບໍ່ແນບສະລິບ'); ?></small><?php endif; ?>
    </span>
</a>
