<?php
/**
 * ປຸ່ມແບ່ງໜ້າ (ວົງມົນ + ລູກສອນ ‹ ›) — ຕ້ອງການ $result (pages, page) ແລະ $queryBase (array ຂອງ GET ທີ່ຕ້ອງເກັບໄວ້).
 * ໜ້າຫຼາຍ → ສະແດງ 1 … (ໜ້າປັດຈຸບັນ ±1) … ໜ້າສຸດທ້າຍ. ລິ້ງມີ data-ajax: ໜ້າທີ່ມີ [data-ajax-list] ໂຫຼດແບບບໍ່ refresh (assets/js/main.js).
 */
$pages = (int)($result['pages'] ?? 1);
if ($pages <= 1) {
    return;
}
$cur = (int)$result['page'];
$pageLink = function (int $n) use ($queryBase) {
    return '?' . http_build_query(array_merge($queryBase, ['page' => $n])) . '#eq-results';
};
$show = array_unique(array_filter([1, $cur - 1, $cur, $cur + 1, $pages], fn($n) => $n >= 1 && $n <= $pages));
sort($show);
$arrow = fn(string $points) => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="' . $points . '"></polyline></svg>';
?>
<nav class="pagination" aria-label="<?php echo te('ແບ່ງໜ້າ'); ?>">
    <?php if ($cur > 1): ?>
        <a class="page-link page-arrow" href="<?php echo e($pageLink($cur - 1)); ?>" data-ajax aria-label="<?php echo te('ກ່ອນໜ້າ'); ?>"><?php echo $arrow('15 18 9 12 15 6'); ?></a>
    <?php else: ?>
        <span class="page-link page-arrow is-disabled" aria-hidden="true"><?php echo $arrow('15 18 9 12 15 6'); ?></span>
    <?php endif; ?>
    <?php $prev = 0; foreach ($show as $n): ?>
        <?php if ($n - $prev > 1): ?><span class="page-gap">…</span><?php endif; ?>
        <a class="page-link<?php echo $n === $cur ? ' active' : ''; ?>" href="<?php echo e($pageLink($n)); ?>" data-ajax<?php echo $n === $cur ? ' aria-current="page"' : ''; ?>><?php echo $n; ?></a>
    <?php $prev = $n; endforeach; ?>
    <?php if ($cur < $pages): ?>
        <a class="page-link page-arrow" href="<?php echo e($pageLink($cur + 1)); ?>" data-ajax aria-label="<?php echo te('ຖັດໄປ'); ?>"><?php echo $arrow('9 18 15 12 9 6'); ?></a>
    <?php else: ?>
        <span class="page-link page-arrow is-disabled" aria-hidden="true"><?php echo $arrow('9 18 15 12 9 6'); ?></span>
    <?php endif; ?>
</nav>
