<?php
/**
 * ກາດແທນຟອມສັ່ງຊື້ ສຳລັບລູກຄ້າທົ່ວໄປ (ບໍ່ມີຟອມຂໍໃບສະເໜີລາຄາແລ້ວ): ລົງທະບຽນ / ເຂົ້າສູ່ລະບົບ → ເຫັນລາຄາ + ສັ່ງຊື້ອອນລາຍ.
 * ປຸ່ມເປີດ popup ສະມາຊິກ (components/member_modal.php — [data-member-open]). ໃຊ້ໃນ pages/product.php ແລະ pages/solution.php.
 * $ctaOutOfStock = true → ສະມາຊິກ + ສິນຄ້າໝົດ: ບອກວ່າໝົດຊົ່ວຄາວ + ປຸ່ມແຊັດ (ໃຊ້ $chatHref ຂອງໜ້າ ຖ້າມີ)
 * ຕົວແປ: $orderItem (product_name, img). CSS: .mcta-* ໃນ assets/css/member.css
 */
$ctaOut = !empty($ctaOutOfStock);
?>
<div class="mcta" id="productOrderSection">
    <?php if (!empty($orderItem['img'])): ?><img class="mcta-img" src="<?php echo e($orderItem['img']); ?>" alt="" width="84" height="84" loading="lazy"><?php endif; ?>
    <div class="mcta-text">
        <strong><?php echo e($orderItem['product_name']); ?></strong>
        <?php if ($ctaOut): ?>
            <span><?php echo te('ສິນຄ້ານີ້ໝົດຊົ່ວຄາວ — ແຊັດຖາມທີມງານວ່າຈະມີເຂົ້າມາເມື່ອໃດ'); ?></span>
        <?php else: ?>
            <span><?php echo te('ລົງທະບຽນຟຣີ ຫຼື ເຂົ້າສູ່ລະບົບ ເພື່ອເບິ່ງລາຄາ ແລະ ສັ່ງຊື້ອອນລາຍ — ເລືອກສາຂາຂົນສົ່ງ, ແນບສະລິບ ແລະ ຕິດຕາມສະຖານະເຄື່ອງໄດ້ເອງ'); ?></span>
        <?php endif; ?>
    </div>
    <div class="mcta-btns">
        <?php if ($ctaOut): ?>
            <a class="btn btn-primary" href="<?php echo e($chatHref ?? url('pages/contact.php')); ?>"><?php echo te('ແຊັດສອບຖາມ'); ?></a>
        <?php else: ?>
            <a class="btn btn-primary" href="#register" data-member-open="register"><?php echo te('ລົງທະບຽນຟຣີ'); ?></a>
            <a class="btn btn-outline" href="<?php echo url('pages/member_login.php'); ?>" data-member-open="login"><?php echo te('ເຂົ້າສູ່ລະບົບ'); ?></a>
        <?php endif; ?>
    </div>
</div>
