<?php
/**
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * auth/login.php ແມ່ນໜ້າເຂົ້າສູ່ລະບົບ: ສ່ວນ PHP ຕອນເລີ່ມ, <head>, ໜ້າຈໍກຳລັງໂຫຼດ ແລະ ສະຄຣິບ plugin; ສ່ວນໃຫຍ່ແຍກໄປ partials/login/ (ຕາມລຳດັບ require):
 *   - partials/login/left_pane.php : ຟອມເຂົ້າສູ່ລະບົບ (ເຄິ່ງຊ້າຍ)
 *   - partials/login/right_pane.php : ຮູບກາຕູນເຄິ່ງຂວາ
 *   - partials/login/login_js.php : JS ໜ້າເຂົ້າສູ່ລະບົບ
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true
    ]);
    session_start();
}
if (isset($_SESSION['checked']) && $_SESSION['checked'] === 1 && !empty($_SESSION['user_id'])) {
    header("Location: ../home/dashboard.php?open_home=1");
    exit();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lang/translator.php';
$site_logo = '../assets/img/logosystem/wlaodev_system_logo.jpg';
if (!is_file(__DIR__ . '/../assets/img/logosystem/wlaodev_system_logo.jpg')) {
    $site_logo = '../assets/img/logosystem/Wlaodev.jpg';
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(getCurrentLang()); ?>" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="google" content="notranslate">
    <title>Minimart Wlaodev POS</title>
    <link rel="shortcut icon" href="<?php echo $site_logo; ?>" type="image/x-icon">

    <!-- CSS Plugins & Theme -->
    <link rel="stylesheet" href="../plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../fontawesome-free-5.15.3-web/css/all.min.css">
    <link rel="stylesheet" href="../sweetalert/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../assets/css/local-font.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../themes/login.css?v=<?php echo filemtime(__DIR__ . '/../themes/login.css'); ?>">
    <link rel="stylesheet" href="../themes/dashboard-preloader.css?v=<?php echo filemtime(__DIR__ . '/../themes/dashboard-preloader.css'); ?>">
</head>
<body>

    <!-- Ambient Background Glows -->
    <div class="login-bg-glow-1"></div>
    <div class="login-bg-glow-2"></div>

    <!-- Split Card Login Container -->
    <div class="login-split-wrapper">
        <div class="login-split-card" id="loginSplitCard">


<?php require __DIR__ . '/partials/login/left_pane.php'; /* ຟອມເຂົ້າສູ່ລະບົບ (ເຄິ່ງຊ້າຍ) */ ?>

<?php require __DIR__ . '/partials/login/right_pane.php'; /* ຮູບກາຕູນເຄິ່ງຂວາ */ ?>

        </div>
    </div>

    <!-- ໂຕກຳລັງໂຫຼດຫຼັງກົດເຂົ້າສູ່ລະບົບ: ແບບດຽວກັບໂຕໂຫຼດໜ້າໃນດາສບອດ (home/dashboard.php #iframeLoaderOverlay,
         CSS themes/dashboard-preloader.css), ແຕ່ປົກເຕັມຈໍ ແລະ ເຊື່ອງໄວ້ຈົນກົດເຂົ້າສູ່ລະບົບ -->
    <?php $wlao_ver = @filemtime(__DIR__ . '/../assets/img/logosystem/Wlaodev.png') ?: 1; ?>
    <div id="loginLoaderOverlay" class="system-loader-overlay" style="position: fixed; z-index: 9999; display: none;">
      <div class="system-loader-wrapper">
        <div class="system-loader-pulse-ring"></div>
        <img src="../assets/img/logosystem/Wlaodev.png?v=<?php echo $wlao_ver; ?>" alt="WLaodev" class="system-loader-logo">
      </div>
      <div class="system-loader-text">
        <span><?php echo htmlspecialchars(t('layout.loading_data', 'ກຳລັງໂຫຼດຂໍ້ມູນ')); ?></span><span class="loader-dot d1">.</span><span class="loader-dot d2">.</span><span class="loader-dot d3">.</span>
      </div>
    </div>

    <!-- Required Scripts -->
    <script src="../plugins/jquery/jquery.min.js"></script>
    <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../sweetalert/dist/sweetalert2.all.min.js"></script>
    <script src="../assets/js/pos-sounds.js?v=<?php echo @filemtime(__DIR__ . '/../assets/js/pos-sounds.js'); ?>"></script>

<?php require __DIR__ . '/partials/login/login_js.php'; /* JS ໜ້າເຂົ້າສູ່ລະບົບ */ ?>
</body>
</html>
