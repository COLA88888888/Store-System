<?php
/**
 * ໜ້າສັ່ງຊື້ຈາກກະຕ່າ. POST ມາໜ້ານີ້ເອງ → createWebOrder() → ລ້າງກະຕ່າ.
 *   - ສະມາຊິກ: ເຫັນລາຄາ + ຍອດລວມ, ທີ່ຢູ່/ຂົນສົ່ງ/ສາຂາ (ຕື່ມຈາກບັນຊີ), ທະນາຄານ + ສະລິບ (memberValidateOrder) → pages/my_order.php
 *   - ລູກຄ້າທົ່ວໄປ: ຂໍໃບສະເໜີລາຄາ (ຊື່, ເບີ, ທີ່ຢູ່ — quick_inquiry) → pages/order_success.php
 * ຄຳສັ່ງຊື້ບໍ່ຕັດສະຕັອກ; ພະນັກງານຢືນຢັນ ແລ້ວອັບເດດສະຖານະໃນ backend/pages/web_orders/.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
if (!currentMember($pdo)) {   // ກະຕ່າສະເພາະສະມາຊິກ → ໜ້າຫຼັກ + ເປີດ popup ລົງທະບຽນ
    redirect('#register');
}
$pageTitle = t('ສັ່ງຊື້');
$currentPage = 'cart';
$pageNoIndex = true; // SEO: ບໍ່ໃຫ້ Google ເກັບໜ້ານີ້
$errors = [];
$member = currentMember($pdo);
$banks = $member ? getActiveBankAccounts($pdo, $shopStoreId) : [];
$form = ['name' => '', 'tel' => '', 'email' => '', 'address' => '', 'note' => ''];
if ($member) {   // ສະມາຊິກ → ຕື່ມຊື່/ເບີ/ທີ່ຢູ່/ຂົນສົ່ງ ຈາກບັນຊີ (models/member_orders.php)
    $form = memberOrderDefaults($member) + $form;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($member) {
        [$customer, $errors] = memberValidateOrder($member, $_POST, $_FILES['slip'] ?? null, $banks);
        $form = array_merge($form, array_intersect_key($_POST, $form));
        if (memberNeedsPhone($member)) {
            $errors['form'] = t('ກະລຸນາເພີ່ມເບີໂທຈິງໃນບັນຊີກ່ອນສັ່ງຊື້');
        }
    } else {
        [$customer, $errors] = validateOrderCustomer($_POST + ['quick_inquiry' => 1]);
        $form = $customer + $form;
    }
    if (!checkCsrf()) {
        $errors['form'] = t('ໝົດເວລາຂອງຟອມ ກະລຸນາລອງສົ່ງອີກຄັ້ງ');
    } elseif (!$pdo) {
        $errors['form'] = t('ລະບົບບໍ່ພ້ອມໃຊ້ງານ ກະລຸນາລອງໃໝ່ພາຍຫຼັງ');
    }
    if (!$errors) {
        cartDetails($pdo, $shopStoreId); // ຈຳກັດຈຳນວນຕາມສະຕັອກປັດຈຸບັນກ່ອນບັນທຶກ
        $res = createWebOrder($pdo, $shopStoreId, $customer, cartItems());
        if ($res['success']) {
            cartClear();
            $_SESSION['last_order_no'] = $res['order_no'];
            if ($member) {
                memberRememberAddress($pdo, $member, $customer);
                memberMailOrder($pdo, (int)$member['id'], $res['order_no']);   // ອີເມວ "ໄດ້ຮັບອໍເດີແລ້ວ"
                flash($customer['slip_img'] ? t('ສັ່ງຊື້ສຳເລັດ! ທີມງານກຳລັງກວດສະລິບ') : t('ສັ່ງຊື້ສຳເລັດ! ກະລຸນາໂອນເງິນ ແລະ ແນບສະລິບ'));
                redirect('pages/my_orders.php?s=all&track=' . urlencode($res['order_no']));
            }
            redirect('pages/order_success.php?no=' . urlencode($res['order_no']));
        }
        $errors['form'] = $res['message'];
    }
}

$cart = cartDetails($pdo, $shopStoreId);
if (!$cart['lines'] && empty($errors['form'])) {
    redirect('pages/cart.php');
}
require dirname(__DIR__) . '/layouts/header.php';
require dirname(__DIR__) . '/components/page_hero.php';
$field = function (string $name) use ($errors) {
    return isset($errors[$name]) ? '<small class="field-error">' . e($errors[$name]) . '</small>' : '';
};
?>
<section class="section">
    <div class="container cart-layout">
        <?php if ($member): ?>
            <form class="card form mo-checkout" method="post" enctype="multipart/form-data" novalidate data-order-form>
                <?php echo csrfField(); ?>
                <?php if (memberNeedsPhone($member)): ?>
                    <div class="alert alert-error"><?php echo te('ກະລຸນາເພີ່ມເບີໂທຈິງໃນບັນຊີກ່ອນສັ່ງຊື້ (ທີມງານຕ້ອງໂທຢືນຢັນອໍເດີ)'); ?> <a href="<?php echo url('pages/account.php?edit=1'); ?>"><?php echo te('ເພີ່ມເບີໂທ'); ?></a></div>
                <?php endif; ?>
                <?php if (!empty($errors['form'])): ?><div class="alert alert-error"><?php echo e($errors['form']); ?></div><?php endif; ?>
                <?php if ($errors && empty($errors['form'])): ?><div class="alert alert-error"><?php echo te('ກະລຸນາກວດຂໍ້ມູນທີ່ໝາຍສີແດງ'); ?></div><?php endif; ?>

                <h3 class="mf-h"><span>1</span><?php echo te('ຜູ້ຮັບເຄື່ອງ'); ?></h3>
                <div class="mf-grid-2">
                    <label class="mf-field"><span><?php echo te('ຊື່ຜູ້ຮັບ *'); ?></span><input class="input" name="name" value="<?php echo e($form['name']); ?>" required maxlength="150"><?php echo $field('name'); ?></label>
                    <label class="mf-field"><span><?php echo te('ເບີໂທ (ເບີທີ່ລົງທະບຽນ)'); ?></span><input class="input" value="<?php echo e(laoPhoneFormat($member['tel'])); ?>" readonly></label>
                </div>

                <h3 class="mf-h"><span>2</span><?php echo te('ທີ່ຢູ່ຈັດສົ່ງ ແລະ ສາຂາຂົນສົ່ງ'); ?></h3>
                <?php $af = array_merge($form, array_intersect_key($_POST, array_flip(['province', 'district', 'village', 'address', 'courier_code', 'courier_branch']))); $afErr = $field; $afRequireCourier = true;
                require dirname(__DIR__) . '/components/member_address_fields.php'; ?>

                <h3 class="mf-h"><span>3</span><?php echo te('ຊຳລະເງິນ (ໂອນ) ແລະ ແນບສະລິບ'); ?></h3>
                <?php $bankSel = (int)($_POST['bank_account_id'] ?? 0); $slipRequired = false; $slipAmount = $cart['total'];
                require dirname(__DIR__) . '/components/bank_slip_fields.php'; ?>

                <label class="mf-field"><span><?php echo te('ໝາຍເຫດເຖິງທີມງານ'); ?></span><textarea class="input" name="note" rows="2" maxlength="500" placeholder="<?php echo te('ເຊັ່ນ: ຕ້ອງການໃບບິນ, ໂທກ່ອນສົ່ງ...'); ?>"><?php echo e($form['note']); ?></textarea></label>
                <button class="btn btn-primary btn-lg btn-block" type="submit"><?php echo te('ຢືນຢັນການສັ່ງຊື້'); ?></button>
            </form>
        <?php else: ?>
            <form class="card form" method="post" novalidate>
                <?php echo csrfField(); ?>
                <?php if (!empty($errors['form'])): ?><div class="alert alert-error"><?php echo e($errors['form']); ?></div><?php endif; ?>
                <h3><?php echo te('ຂໍ້ມູນຜູ້ສັ່ງ'); ?></h3>
                <label><?php echo te('ຊື່ ແລະ ນາມສະກຸນ *'); ?><input class="input" name="name" value="<?php echo e($form['name']); ?>" required maxlength="150"></label><?php echo $field('name'); ?>
                <label><?php echo te('ເບີໂທ *'); ?><input class="input" name="tel" type="tel" value="<?php echo e($form['tel']); ?>" required maxlength="20"></label><?php echo $field('tel'); ?>
                <label><?php echo te('ອີເມວ'); ?><input class="input" name="email" type="email" value="<?php echo e($form['email']); ?>" maxlength="150"></label><?php echo $field('email'); ?>
                <label><?php echo te('ທີ່ຢູ່ຈັດສົ່ງ'); ?><textarea class="input" name="address" rows="3"><?php echo e($form['address']); ?></textarea></label><?php echo $field('address'); ?>
                <label><?php echo te('ໝາຍເຫດ'); ?><textarea class="input" name="note" rows="2"><?php echo e($form['note']); ?></textarea></label>
                <button class="btn btn-primary btn-lg btn-block" type="submit"><?php echo te('ຢືນຢັນການສັ່ງຊື້'); ?></button>
                <p class="muted small"><?php echo te('ພະນັກງານຈະໂທຫາທ່ານເພື່ອຢືນຢັນ ແລະ ແຈ້ງຄ່າຈັດສົ່ງ.'); ?></p>
                <p class="muted small"><a href="#register" data-member-open="register"><?php echo te('ລົງທະບຽນເພື່ອເບິ່ງລາຄາ, ແນບສະລິບ ແລະ ຕິດຕາມສະຖານະເຄື່ອງ'); ?></a></p>
            </form>
        <?php endif; ?>
        <aside class="card cart-summary">
            <h3><?php echo te('ລາຍການສັ່ງຊື້'); ?></h3>
            <?php foreach ($cart['lines'] as $line): ?>
                <div class="summary-row"><span><?php echo e($line['product']['product_name']); ?> × <?php echo (int)$line['qty']; ?></span><span><?php echo $member && $line['subtotal'] > 0 ? e(money($line['subtotal'])) : te('ຕິດຕໍ່ສອບຖາມລາຄາ'); ?></span></div>
            <?php endforeach; ?>
            <div class="summary-row total"><span><?php echo te('ລວມທັງໝົດ'); ?></span><span><?php echo $member && $cart['total'] > 0 ? e(money($cart['total'])) : te('ຕິດຕໍ່ສອບຖາມລາຄາ'); ?></span></div>
            <a class="btn btn-outline btn-block" href="<?php echo url('pages/cart.php'); ?>"><?php echo te('ແກ້ໄຂກະຕ່າ'); ?></a>
        </aside>
    </div>
</section>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
