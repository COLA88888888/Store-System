<?php
/**
 * ບັດໂຄສະນາສີເຫຼືອງ ໃນຕາຕະລາງບັດຂອງ pages/equipment_list.php (ຕຳແໜ່ງທີ 5 ຂອງໜ້າທຳອິດ) → ໜ້າຕິດຕໍ່.
 */
?>
<a class="eq-promo" href="<?php echo url('pages/contact.php?subject=' . rawurlencode(t('ສົນໃຈ ອຸປະກອນ POS'))); ?>">
    <span class="eq-promo-brand"><img src="<?php echo e($logo); ?>" alt="" width="54" height="36"> <?php echo e($company['name']); ?></span>
    <strong class="eq-promo-title"><?php echo te('ຕ້ອງການລະບົບຄົບຊຸດ?'); ?></strong>
    <span class="eq-promo-text"><?php echo te('ເລືອກອຸປະກອນ + ໂປຣແກຣມ POS ໃຫ້ເໝາະກັບຮ້ານ ພ້ອມຕິດຕັ້ງຟຣີ ແລະ ສອນໃຊ້ງານໃຫ້ຟຣີ ຈົບໃນບ່ອນດຽວ'); ?></span>
    <span class="eq-promo-btn"><?php echo te('ປຶກສາຟຣີ'); ?></span>
    <span class="eq-promo-icons" aria-hidden="true"><?php foreach (['monitor', 'printer', 'scan', 'drawer', 'paper'] as $ic) echo icon($ic, 22); ?></span>
</a>
