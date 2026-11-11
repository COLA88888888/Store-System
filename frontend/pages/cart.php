<?php
/**
 * ໜ້າກະຕ່າສິນຄ້າ — ລາຄາຈາກ DB ຜ່ານ cartDetails(). ປ່ຽນຈຳນວນ/ລຶບ ຜ່ານ api/cart.php (assets/js/cart.js).
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
if (!currentMember($pdo)) {   // ກະຕ່າສະເພາະສະມາຊິກ → ໜ້າຫຼັກ + ເປີດ popup ລົງທະບຽນ
    redirect('#register');
}
$pageTitle = t('ກະຕ່າສິນຄ້າ');
$currentPage = 'cart';
$pageNoIndex = true; // SEO: ບໍ່ໃຫ້ Google ເກັບໜ້ານີ້
$cart = cartDetails($pdo, $shopStoreId);
$seePrice = canSeePrices();   // ສະເພາະສະມາຊິກເຫັນລາຄາ (models/member_orders.php)
require dirname(__DIR__) . '/layouts/header.php';
require dirname(__DIR__) . '/components/page_hero.php';
?>
<section class="section">
    <div class="container">
        <?php if (!$cart['lines']): ?>
            <p class="empty"><?php echo te('ກະຕ່າຂອງທ່ານຍັງຫວ່າງ.'); ?></p>
            <div class="center"><a class="btn btn-primary" href="<?php echo url('pages/equipment_list.php'); ?>"><?php echo te('ເລືອກຊື້ສິນຄ້າ'); ?></a></div>
        <?php else: ?>
            <div class="cart-layout">
                <div class="table-wrap">
                    <table class="table cart-table">
                        <thead><tr><th><?php echo te('ສິນຄ້າ'); ?></th><th><?php echo te('ລາຄາ'); ?></th><th><?php echo te('ຈຳນວນ'); ?></th><th class="right"><?php echo te('ລວມ'); ?></th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($cart['lines'] as $line): $p = $line['product']; $img = productImage($p['img_url'] ?? null); ?>
                            <tr>
                                <td>
                                    <div class="cart-item">
                                        <?php if ($img): ?><img src="<?php echo e($img); ?>" alt=""><?php else: ?><span class="product-noimg sm">📦</span><?php endif; ?>
                                        <a href="<?php echo e($p['url'] ?? url('pages/product.php?id=' . (int)($p['product_id'] ?? 0))); ?>"><?php echo e($p['product_name']); ?></a>
                                    </div>
                                </td>
                                <td><?php echo $seePrice && (float)$p['price'] > 0 ? e(money((float)$p['price'])) : te('ຕິດຕໍ່ສອບຖາມລາຄາ'); ?></td>
                                <td><input class="input qty-input" type="number" min="1" max="<?php echo productMaxQty($p); ?>" value="<?php echo (int)$line['qty']; ?>" data-cart-qty="<?php echo e($line['key']); ?>" aria-label="<?php echo te('ຈຳນວນ'); ?>"></td>
                                <td class="right"><?php echo $seePrice && $line['subtotal'] > 0 ? e(money($line['subtotal'])) : te('ຕິດຕໍ່ສອບຖາມລາຄາ'); ?></td>
                                <td><button class="btn-icon" type="button" data-cart-remove="<?php echo e($line['key']); ?>" aria-label="<?php echo te('ລຶບ'); ?>">✕</button></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <aside class="card cart-summary">
                    <h3><?php echo te('ສະຫຼຸບ'); ?></h3>
                    <div class="summary-row"><span><?php echo te('ຈຳນວນ'); ?></span><span><?php echo (int)$cart['qty']; ?> <?php echo te('ອັນ'); ?></span></div>
                    <div class="summary-row total"><span><?php echo te('ລາຄາລວມ'); ?></span><span><?php echo $seePrice && $cart['total'] > 0 ? e(money($cart['total'])) : te('ຕິດຕໍ່ສອບຖາມລາຄາ'); ?></span></div>
                    <?php if (!$seePrice): ?><p class="muted small"><a href="#register" data-member-open="register"><?php echo te('ລົງທະບຽນເພື່ອເບິ່ງລາຄາ ແລະ ສັ່ງຊື້ອອນລາຍ'); ?></a></p><?php endif; ?>
                    <a class="btn btn-primary btn-block" href="<?php echo url('pages/checkout.php'); ?>"><?php echo te('ສັ່ງຊື້ & ປ້ອນທີ່ຢູ່ຈັດສົ່ງ'); ?></a>
                    <a class="btn btn-outline btn-block" href="<?php echo url('pages/equipment_list.php'); ?>"><?php echo te('ເລືອກສິນຄ້າຕໍ່'); ?></a>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
