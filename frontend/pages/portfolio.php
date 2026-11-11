<?php
/**
 * ໜ້າຜົນງານ (ເມນູ "ຜົນງານ") — ຜົນງານທັງໝົດຈາກ $siteWorks (includes/content.php, models/works.php) ເປັນບັດ components/work_card.php,
 * ປຸ່ມກັ່ນຕອງຕາມປະເພດ ([data-filter-group] ໃນ assets/js/main.js). ກົດບັດ → pages/work.php (ຮູບທັງໝົດ + lightbox).
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
if (!hasWorks()) {
    redirect('pages/services.php');   // ຍັງບໍ່ມີຜົນງານແທ້ ($siteWorks ຫວ່າງ) → ໄປໜ້າບໍລິການ ແທນໜ້າຫວ່າງ
}
$pageTitle = t('ຜົນງານ');
$pageDescription = t('ຮູບພາບການຕິດຕັ້ງລະບົບ POS ແລະ ອຸປະກອນ ໃຫ້ຮ້ານຄ້າ ແລະ ທຸລະກິດໃນລາວ');
$currentPage = 'portfolio';
$works = getWorks();
require dirname(__DIR__) . '/layouts/header.php';
?>
<section class="section eq-section">
    <div class="container">
        <h1 class="eq-list-title"><?php echo te('ຜົນງານຂອງພວກເຮົາ'); ?></h1>
        <?php if ($works): ?>
            <div class="wk-grid">
                <?php foreach ($works as $w): ?>
                    <?php require dirname(__DIR__) . '/components/work_card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="empty"><?php echo te('ຍັງບໍ່ມີຜົນງານ'); ?></p>
        <?php endif; ?>
    </div>
</section>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
