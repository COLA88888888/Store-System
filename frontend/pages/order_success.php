<?php
/**
 * ໜ້າບັນທຶກຂໍ້ມູນສັ່ງຊື້ສຳເລັດ (GET: no = ເລກຄຳສັ່ງຊື້). ສະແດງລາຍລະອຽດສິນຄ້າ, ທີ່ຢູ່ຈັດສົ່ງ
 * ແລະ ບໍລິສັດຂົນສົ່ງໃນລາວ + ສາຂາປາຍທາງ ພ້ອມຊ່ອງທາງຕິດຕໍ່ໂອນເງິນເພື່ອໃຫ້ພະນັກງານຈັດຝາກເຄື່ອງ.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
$pageTitle = t('ບັນທຶກຂໍ້ມູນສັ່ງຊື້ສຳເລັດ');
$currentPage = 'equipment';
$pageNoIndex = true;
$orderNo = (string)($_GET['no'] ?? '');
$order = ($pdo && $orderNo !== '' && hash_equals((string)($_SESSION['last_order_no'] ?? ''), $orderNo)) ? getWebOrder($pdo, $orderNo) : null;
$couriers = getLaoCouriers();
require dirname(__DIR__) . '/layouts/header.php';
?>
<section class="section">
    <div class="container narrow">
        <?php if (!$order): ?>
            <p class="empty"><?php echo te('ບໍ່ພົບຄຳສັ່ງຊື້.'); ?></p>
        <?php else:
            $cCode = (string)($order['courier_code'] ?? '');
            $courierLogo = $couriers[$cCode]['logo'] ?? '';
            // ຂໍ້ຄວາມ WhatsApp / ແຊັດ: ບອກຊື່ສິນຄ້າທີ່ຂໍໃບສະເໜີລາຄາເລີຍ (ບໍ່ອ້າງເລກບິນ)
            $itemText = implode(', ', array_map(fn($it) => $it['product_name'] . ' × ' . (int)$it['qty'], $order['items']));
            $waMsg = t('ສະບາຍດີ ຂໍໃບສະເໜີລາຄາ: %s', $itemText);
            $waHref = 'https://wa.me/8562055949007?text=' . rawurlencode($waMsg);
        ?>
            <div class="card success-card">
                <div class="success-icon" aria-hidden="true">✓</div>
                <h1><?php echo te('ຮັບຂໍ້ມູນຂໍໃບສະເໜີລາຄາສຳເລັດ!'); ?></h1>
                <p><?php echo te('ເລກອ້າງອີງ:'); ?> <strong><?php echo e($order['order_no']); ?></strong></p>
                <p class="muted"><?php echo te('ທີມງານໄດ້ຮັບຂໍ້ມູນຂອງທ່ານແລ້ວ ແລະ ກຳລັງກຽມແຈ້ງລາຄາພິເສດໃຫ້ທາງ WhatsApp / ໂທລະສັບ — ຫຼື ທ່ານສາມາດກົດປຸ່ມລຸ່ມນີ້ເພື່ອທັກຫາທີມງານທັນທີ.'); ?></p>

                <div class="os-summary">
                    <div class="os-row"><span><?php echo te('ຊື່ຜູ້ຕິດຕໍ່:'); ?></span><strong><?php echo e($order['customer_name']); ?> (<?php echo e($order['customer_tel']); ?>)</strong></div>
                    <?php if (!empty($order['customer_address'])): ?>
                        <div class="os-row"><span><?php echo te('ທີ່ຢູ່ຈັດສົ່ງ:'); ?></span><strong><?php echo e((string)$order['customer_address']); ?></strong></div>
                    <?php endif; ?>
                    <?php if (!empty($order['courier_name'])): ?>
                        <div class="os-row">
                            <span><?php echo te('ບໍລິສັດຂົນສົ່ງ:'); ?></span>
                            <strong class="os-courier">
                                <?php if ($courierLogo): ?><img src="<?php echo e($courierLogo); ?>" alt="" width="44" height="26"><?php endif; ?>
                                <?php echo e($order['courier_name']); ?>
                                <?php if (!empty($order['courier_branch'])): ?> — <?php echo te('ສາຂາ:'); ?> <?php echo e($order['courier_branch']); ?><?php endif; ?>
                            </strong>
                        </div>
                    <?php endif; ?>
                </div>

                <table class="table mt-2">
                    <tbody>
                    <?php foreach ($order['items'] as $it): ?>
                        <tr>
                            <td><?php echo e($it['product_name']); ?> × <?php echo (int)$it['qty']; ?></td>
                            <?php /* ລາຄາໃນ DB ເປັນລາຄາຂາຍໜ້າຮ້ານ ບໍ່ແມ່ນໃບສະເໜີ — ບໍ່ສະແດງ (ກັນຄູ່ແຂ່ງສັ່ງທົດລອງເພື່ອເບິ່ງລາຄາ) */ ?>
                            <td class="right"><?php echo te('ຕາມໃບສະເໜີລາຄາ'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total">
                        <td><?php echo te('ຍອດລວມທັງໝົດ'); ?></td>
                        <td class="right"><?php echo te('ຕາມໃບສະເໜີລາຄາ'); ?></td>
                    </tr>
                    </tbody>
                </table>

                <div class="sol-contact-btns mt-3">
                    <a class="sol-btn is-wa" href="<?php echo e($waHref); ?>" target="_blank" rel="noopener">
                        <span><?php echo te('ສົ່ງໃບສະເໜີລາຄາທາງ WhatsApp'); ?></span>
                    </a>
                    <a class="sol-btn is-fb" href="<?php echo e(fbChatUrl($waMsg)); ?>" target="_blank" rel="noopener">
                        <span><?php echo te('ແຊັດ Facebook'); ?></span>
                    </a>
                    <a class="sol-btn is-chat" href="<?php echo e(url('pages/contact.php?subject=' . rawurlencode($waMsg))); ?>">
                        <span><?php echo te('ແຊັດສອບຖາມ'); ?></span>
                    </a>
                    <a class="sol-btn is-tel" href="tel:+8562029777302">
                        <span>020 29 777 302</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>
        <div class="center mt-4"><a class="btn btn-primary" href="<?php echo url('pages/equipment_list.php'); ?>"><?php echo te('ເບິ່ງສິນຄ້າອື່ນຕໍ່'); ?></a></div>
    </div>
</section>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
