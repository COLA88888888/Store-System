<?php
/**
 * ໜ້າຫຼັກ: ກາດ "ເປັນຫຍັງຕ້ອງເລືອກພວກເຮົາ" 4 ກາດ — ໃຕ້ຫົວຂໍ້ "ເປັນຫຍັງຕ້ອງເລືອກ Wlaodev?" (components/promo_banner.php).
 * ຂໍ້ມູນ: $siteWhys (includes/content.php, ໃຊ້ຮ່ວມກັບ pages/about.php). CSS: .abt-why-* ໃນ assets/css/pages.css (ໄອຄອນສີທຳມະດາ ບໍ່ມີເສັ້ນຫົວກາດ).
 */
$whySvg = fn(string $p) => '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
?>
<div class="abt-why-grid home-why-grid">
    <?php foreach ($siteWhys as $i => [$ico, $title, $sub]): ?>
        <div class="abt-why-item" data-reveal style="--reveal-delay: <?php echo $i * 110; ?>ms">
            <span class="abt-why-ico"><?php echo $whySvg($ico); ?></span>
            <strong><?php echo e($title); ?></strong>
            <p><?php echo e($sub); ?></p>
        </div>
    <?php endforeach; ?>
</div>
