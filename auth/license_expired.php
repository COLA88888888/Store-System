<?php
/**
 * ໜ້າແພັກເກັດໝົດອາຍຸ (ລະບົບທັງໝົດ — st_tbcompanyinfo.license_expire_date ຂອງສາຂາຫຼັກ).
 * ໂຄງດຽວກັບໜ້າເຂົ້າສູ່ລະບົບ: ກາດສີຂາວ + ໂລໂກ້ບໍ່ມີພື້ນຫຼັງ. ສ່ວນຮ່ວມ (auth/partials/expired/, ຕາມລຳດັບ require):
 *   - head_logo.php      : ປຸ່ມປ່ຽນພາສາ + ໂລໂກ້
 *   - contact_footer.php : ປຸ່ມຕິດຕໍ່ຕໍ່ອາຍຸ, ລິ້ງກັບໜ້າເຂົ້າສູ່ລະບົບ, ທ້າຍກາດ (ປິດເຄິ່ງຊ້າຍ)
 *   - right_pane.php     : ເຄິ່ງຂວາ (ໂລ່ + ກະແຈ), ປິດກາດ, script ປ່ຽນພາສາ
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Clear session defensively
session_unset();
session_destroy();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lang/translator.php';

$site_logo = '../assets/img/logosystem/wlaodev_system_logo.jpg';
if (!is_file(__DIR__ . '/../assets/img/logosystem/wlaodev_system_logo.jpg')) {
    $site_logo = '../assets/img/logosystem/Wlaodev.jpg';
}
$site_logo_png = '../assets/img/logosystem/Wlaodev.png';
if (!is_file(__DIR__ . '/../assets/img/logosystem/Wlaodev.png')) {
    $site_logo_png = $site_logo;
}

$expire_display = '-';
$store_name = '';
try {
    $info = $pdo->query("SELECT com_name_la, license_expire_date FROM st_tbcompanyinfo WHERE branch_id = (SELECT store_id FROM st_tbstore WHERE is_main = 1 ORDER BY store_id ASC LIMIT 1) LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($info) {
        if (!empty($info['license_expire_date'])) {
            $expire_display = date('d/m/Y', strtotime($info['license_expire_date']));
        }
        $store_name = trim($info['com_name_la'] ?? '');
    }
} catch (Throwable $ex) {}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(getCurrentLang()); ?>" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="google" content="notranslate">
    <title>ແພັກເກັດໝົດອາຍຸການໃຊ້ງານ - WlaoDev POS</title>
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($site_logo); ?>" type="image/x-icon">

    <!-- CSS Plugins & Theme -->
    <link rel="stylesheet" href="../plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../fontawesome-free-5.15.3-web/css/all.min.css">
    <link rel="stylesheet" href="../sweetalert/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../assets/css/local-font.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../themes/login.css?v=<?php echo filemtime(__DIR__ . '/../themes/login.css'); ?>">
    <link rel="stylesheet" href="../themes/login-hero.css?v=<?php echo filemtime(__DIR__ . '/../themes/login-hero.css'); ?>">
    <link rel="stylesheet" href="../themes/expired-pages.css?v=<?php echo filemtime(__DIR__ . '/../themes/expired-pages.css'); ?>">
</head>
<body class="expired-page">

    <!-- Ambient Background Glows (ດຽວກັບໜ້າ Login) -->
    <div class="login-bg-glow-1"></div>
    <div class="login-bg-glow-2"></div>

    <div class="login-split-wrapper">
        <div class="login-split-card" id="loginSplitCard">

            <!-- ================= LEFT PANE: EXPIRED NOTICE & CONTACT ACTIONS ================= -->
            <div class="login-left-pane login-brand-hero">
<?php require __DIR__ . '/partials/expired/head_logo.php'; ?>

                <div class="login-form-top-wrap">
                    <!-- Header Title: WlaoDev POS (ສີຟ້າ) ແພັກເກັດໝົດອາຍຸ (ສີແດງ) -->
                    <div class="login-title-group text-center mb-2">
                        <h3 class="login-title-main">
                            <span class="brand-blue">WlaoDev POS</span> <span class="text-danger"><i class="fas fa-lock ml-1 mr-1" style="font-size: 1.25rem;"></i> ແພັກເກັດໝົດອາຍຸ</span>
                        </h3>
                        <p class="text-muted mt-1 mb-0" style="font-size: 0.9rem; font-weight: 600;">ກະລຸນາຕິດຕໍ່ຕໍ່ອາຍຸການໃຊ້ງານລະບົບ</p>
                    </div>

                    <!-- Store Name Badge (ຖ້າມີ) -->
                    <?php if ($store_name !== ''): ?>
                        <div class="text-center mb-2">
                            <span class="badge badge-light border text-dark font-weight-bold px-3 py-1.5" style="font-size: 0.85rem; border-radius: 20px;">
                                <i class="fas fa-store mr-1 text-primary"></i> <?php echo htmlspecialchars($store_name); ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <!-- Expired Alert Box -->
                    <div class="expired-alert-box">
                        <div class="d-flex align-items-center mb-1">
                            <span class="badge badge-danger font-weight-bold mr-2" style="font-size: 0.76rem; border-radius: 6px;">
                                <i class="fas fa-exclamation-triangle mr-1"></i> ໝົດອາຍຸ
                            </span>
                            <span style="font-size: 0.88rem; color: #991b1b; font-weight: 700;">
                                ວັນທີໝົດອາຍຸ: <u style="font-size: 0.95rem;"><?php echo htmlspecialchars($expire_display); ?></u>
                            </span>
                        </div>
                        <div style="font-size: 0.82rem; color: #7f1d1d; line-height: 1.45;">
                            ລະບົບຖືກລະງັບການເຂົ້າໃຊ້ງານຊົ່ວຄາວ. ຂໍ້ມູນສິນຄ້າ ແລະ ຍອດຂາຍທັງໝົດຍັງຄົງຢູ່ປອດໄພ.
                        </div>
                    </div>

<?php require __DIR__ . '/partials/expired/contact_footer.php'; ?>

<?php require __DIR__ . '/partials/expired/right_pane.php'; ?>
</body>
</html>
