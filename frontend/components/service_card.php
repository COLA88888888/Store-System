<?php
/**
 * ບັດບໍລິການ 1 ອັນ — ຕ້ອງການ $s (ແຖວຈາກ $siteServices ໃນ includes/content.php).
 * ສະໄຕລ໌ກາດຂະຫຍາຍເມື່ອຄຣິກ (Expandable Card):
 * - ຕອນນ້ອຍ (collapsed): ສະແດງໄອຄອນວົງມົນ + ຊື່ບໍລິການຢູ່ເຄິ່ງກາງກາດ.
 * - ຕອນຂະຫຍາຍ (is-center): ກາດຂະຫຍາຍກວ້າງອອກ ສະແດງເນື້ອຫາລາຍລະອຽດ + ຊື່ຢູ່ມຸມລຸ່ມຊ້າຍ ແລະ ໄອຄອນຍ້າຍໄປຢູ່ມຸມລຸ່ມຂວາເປັນລາຍນ້ຳ.
 */
$author = $s['author'] ?? $s['title'];
$role = $s['role'] ?? ($s['title'] ?? '');
$svgPaths = $s['svg'] ?? '<polyline points="20 6 9 17 4 12"></polyline>';
?>
<article class="card service-card srv-card">
    <div class="srv-expanded-content">
        <div class="srv-body">
            <p class="srv-text">“<?php echo e($s['text']); ?>”</p>
        </div>

        <div class="srv-foot">
            <strong class="srv-author"><?php echo e($author); ?></strong>
            <span class="srv-role"><?php echo e($role); ?></span>
        </div>
    </div>

    <div class="srv-brand-mark" aria-hidden="true">
        <div class="srv-icon-circle">
            <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round">
                <?php echo $svgPaths; ?>
            </svg>
        </div>
        <span class="srv-compact-label"><?php echo e($author); ?></span>
    </div>
</article>
