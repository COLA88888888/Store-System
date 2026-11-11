<?php
/**
 * ເຂົ້າສູ່ລະບົບສະມາຊິກເວັບໄຊ (ເບີໂທ ຫຼື ອີເມວ + ລະຫັດຜ່ານ, ຫຼື Google) — memberLogin() ໃນ models/members.php.
 * ບໍ່ແມ່ນການເຂົ້າລະບົບ POS ຂອງພະນັກງານ (ອັນນັ້ນແມ່ນ backend/). ສຳເລັດ → ?next= ຫຼື pages/account.php
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
$pageTitle = t('ເຂົ້າສູ່ລະບົບສະມາຊິກ');
$currentPage = 'member';
$pageNoIndex = true;
if (currentMember($pdo)) {
    redirect('pages/account.php');
}
$error = '';
$tel = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tel = trim((string)($_POST['login'] ?? ''));
    if (!checkCsrf()) {
        $error = t('ໝົດເວລາຂອງຟອມ ກະລຸນາລອງສົ່ງອີກຄັ້ງ');
    } elseif (!$pdo) {
        $error = t('ລະບົບບໍ່ພ້ອມໃຊ້ງານ ກະລຸນາລອງໃໝ່ພາຍຫຼັງ');
    } else {
        $error = memberLogin($pdo, $tel, (string)($_POST['password'] ?? '')) ?? '';
        if ($error === '') {
            redirect(memberNextPath());
        }
    }
}
require dirname(__DIR__) . '/layouts/header.php';
require dirname(__DIR__) . '/components/page_hero.php';
?>
<section class="section">
    <div class="container mb-wrap">
        <form class="card form" method="post" novalidate>
            <?php echo csrfField(); ?>
            <input type="hidden" name="next" value="<?php echo e((string)($_GET['next'] ?? $_POST['next'] ?? '')); ?>">
            <?php if ($error !== ''): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>
            <label><?php echo te('ເບີໂທ ຫຼື ອີເມວ'); ?><input class="input" name="login" type="text" value="<?php echo e($tel); ?>" required maxlength="150" autocomplete="off" placeholder="020 xxxx xxxx / name@gmail.com"></label>
            <label><?php echo te('ລະຫັດຜ່ານ'); ?><input class="input" name="password" type="password" required autocomplete="new-password"></label>
            <button class="btn btn-primary btn-lg btn-block" type="submit"><?php echo te('ເຂົ້າສູ່ລະບົບ'); ?></button>
            <?php $googleBtnText = 'signin_with'; require dirname(__DIR__) . '/components/google_button.php'; ?>
            <p class="mb-switch"><?php echo te('ຍັງບໍ່ເປັນສະມາຊິກ?'); ?> <a href="#register" data-member-open="register"><?php echo te('ລົງທະບຽນຟຣີ'); ?></a></p>
            <p class="muted small mb-switch"><?php echo te('ລືມລະຫັດຜ່ານ?'); ?> <a href="<?php echo e('https://wa.me/8562055949007?text=' . rawurlencode(t('ສະບາຍດີ ຂ້ອຍລືມລະຫັດຜ່ານສະມາຊິກ WlaoDev — ຂໍຕັ້ງລະຫັດໃໝ່'))); ?>" target="_blank" rel="noopener"><?php echo te('ທັກ WhatsApp ຂໍລະຫັດໃໝ່'); ?></a></p>
        </form>
    </div>
</section>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
