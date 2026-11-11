<?php
/**
 * ໜ້າອາຍຸການນຳໃຊ້ບັນຊີໝົດ (ສະເພາະຜູ້ໃຊ້ຄົນນັ້ນ — w_tbuser.access_expire_date <= ມື້ນີ້, ຕັ້ງ "ຈຳນວນມື້" = 0 ກໍໝົດທັນທີ).
 * ໂຄງດຽວກັບໜ້າເຂົ້າສູ່ລະບົບ ແລະ ໜ້າແພັກເກັດໝົດອາຍຸ: ກາດສີຂາວ + ໂລໂກ້ບໍ່ມີພື້ນຫຼັງ. ສ່ວນຮ່ວມ (auth/partials/expired/, ຕາມລຳດັບ require):
 *   - head_logo.php      : ປຸ່ມປ່ຽນພາສາ + ໂລໂກ້
 *   - contact_footer.php : ປຸ່ມຕິດຕໍ່ຕໍ່ອາຍຸ, ລິ້ງກັບໜ້າເຂົ້າສູ່ລະບົບ, ທ້າຍກາດ (ປິດເຄິ່ງຊ້າຍ)
 *   - right_pane.php     : ເຄິ່ງຂວາ (ໂລ່ + ກະແຈ), ປິດກາດ, script ປ່ຽນພາສາ
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// A visitor arriving here should not still be logged in — config/db.php's per-request check
// and check_user.php's login-time check both destroy the session before sending anyone here,
// but clear it again defensively in case this URL is opened directly.
session_unset();
session_destroy();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lang/translator.php';
$site_logo = '../assets/img/logosystem/Wlaodev.jpg';
$site_logo_png = '../assets/img/logosystem/Wlaodev.png';
if (!is_file(__DIR__ . '/../assets/img/logosystem/Wlaodev.png')) {
    $site_logo_png = $site_logo;
}

// Escapes the translated string, then re-highlights the brand name in it — the strings
// from t() are already stored with "WlaoDev" written out, so this just colors that
// substring rather than the whole sentence, without ever passing raw HTML through t().
function highlightBrand($text) {
    return str_replace('WlaoDev', '<span class="brand-wd">WlaoDev</span>', htmlspecialchars($text));
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(getCurrentLang()); ?>" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="google" content="notranslate">
    <title>Wlaodev POS</title>
    <link rel="icon" type="image/svg+xml" href="../assets/img/logosystem/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/img/logosystem/favicon-32.png">
    <link rel="shortcut icon" href="../assets/img/logosystem/favicon-32.png">

    <!-- CSS Plugins & Theme -->
    <link rel="stylesheet" href="../plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../fontawesome-free-5.15.3-web/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/local-font.css?v=<?php echo @filemtime(dirname(__DIR__, 1) . '/assets/css/local-font.css'); ?>">
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

            <!-- ================= LEFT PANE: ACCESS EXPIRED NOTICE & CONTACT ACTIONS ================= -->
            <div class="login-left-pane login-brand-hero">
<?php require __DIR__ . '/partials/expired/head_logo.php'; ?>

                <div class="login-form-top-wrap">
                    <div class="login-title-group text-center mb-2">
                        <h3 class="login-title-main">
                            <span class="brand-blue">WlaoDev POS</span> <span class="text-danger"><i class="fas fa-user-lock ml-1 mr-1" style="font-size: 1.25rem;"></i> <?php echo tlh('ບັນຊີໝົດອາຍຸ'); ?></span>
                        </h3>
                        <p class="text-muted mt-1 mb-0" style="font-size: 0.9rem; font-weight: 600;"><?php echo highlightBrand(t('access_expired.contact_us', 'ກະລຸນາຕິດຕໍ່ທີມງານ WlaoDev ເພື່ອຕໍ່ອາຍຸການນຳໃຊ້')); ?></p>
                    </div>

                    <div class="expired-alert-box">
                        <div class="d-flex align-items-center mb-1">
                            <span class="badge badge-danger font-weight-bold mr-2" style="font-size: 0.76rem; border-radius: 6px;">
                                <i class="fas fa-exclamation-triangle mr-1"></i> <?php echo tlh('ໝົດອາຍຸ'); ?>
                            </span>
                            <span style="font-size: 0.88rem; color: #991b1b; font-weight: 700;">
                                <?php echo htmlspecialchars(t('access_expired.title', 'ອາຍຸການນຳໃຊ້ບັນຊີຂອງທ່ານໝົດແລ້ວ')); ?>
                            </span>
                        </div>
                        <div style="font-size: 0.82rem; color: #7f1d1d; line-height: 1.45;">
                            <?php echo highlightBrand(t('access_expired.body', 'ບໍ່ສາມາດເຂົ້າໃຊ້ງານລະບົບໄດ້ຈົນກວ່າທີມງານ WlaoDev ຈະຕໍ່ອາຍຸການນຳໃຊ້ໃຫ້')); ?>
                        </div>
                    </div>

<?php require __DIR__ . '/partials/expired/contact_footer.php'; ?>

<?php require __DIR__ . '/partials/expired/right_pane.php'; ?>
</body>
</html>
