<?php
/**
 * ແຖບຫົວໜ້າ (ຊື່ໜ້າ + ຄຳອະທິບາຍ) ຂອງໜ້າຍ່ອຍ — ໃຊ້ $pageTitle ແລະ $pageDescription.
 */
?>
<section class="page-hero">
    <div class="container">
        <h1><?php echo e($pageTitle); ?></h1>
        <?php if (!empty($pageDescription)): ?><p><?php echo e($pageDescription); ?></p><?php endif; ?>
    </div>
</section>
