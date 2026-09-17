<?php
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

$expire_display = '-';
try {
    $exp = $pdo->query("SELECT license_expire_date FROM tbcompanyinfo LIMIT 1")->fetchColumn();
    if (!empty($exp)) {
        $expire_display = date('d/m/Y', strtotime($exp));
    }
} catch (Throwable $ex) {}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(getCurrentLang()); ?>" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate">
    <title>Wlaodev POS</title>
    <link rel="shortcut icon" href="<?php echo $site_logo; ?>" type="image/x-icon">

    <!-- CSS Plugins & Theme -->
    <link rel="stylesheet" href="../plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../fontawesome-free-5.15.3-web/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/local-font.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../themes/login.css?v=<?php echo filemtime(__DIR__ . '/../themes/login.css'); ?>">
</head>
<body>

    <!-- Banner Background on Top -->
    <div class="login-banner-stripe"></div>
    <div class="login-banner-bg"></div>
    <div class="login-banner-overlay"></div>

    <!-- License Expired Container -->
    <div class="login-card-container">
        <!-- Main Card -->
        <div class="login-card">
            <!-- Overlapping Circle Logo Badge -->
            <div class="logo-badge">
                <img src="<?php echo htmlspecialchars($site_logo); ?>" alt="POS Logo">
            </div>

            <!-- Title Header -->
            <div class="login-header-text">
                <h4 style="color: #dc2626;"><i class="fas fa-exclamation-triangle mr-1"></i> <?php echo htmlspecialchars(t('license_expired.title', 'ແພັກເກັດຂອງທ່ານໝົດອາຍຸແລ້ວ')); ?></h4>
            </div>

            <div class="text-center" style="padding: 4px 8px 20px;">
                <p style="font-size: 0.95rem; color: #334155; margin-bottom: 4px;">
                    <?php echo htmlspecialchars(t('license_expired.body', 'ບໍ່ສາມາດເຂົ້າໃຊ້ງານລະບົບໄດ້ຈົນກວ່າຈະຕໍ່ອາຍຸແພັກເກັດ')); ?>
                </p>
                <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 18px;">
                    <?php echo htmlspecialchars(t('license_expired.expired_on', 'ໝົດອາຍຸວັນທີ')); ?>: <strong><?php echo htmlspecialchars($expire_display); ?></strong>
                </p>

                <p style="font-size: 0.95rem; font-weight: 700; color: #1e293b; margin-bottom: 14px;">
                    <?php echo htmlspecialchars(t('license_expired.contact_us', 'ກະລຸນາຕິດຕໍ່ພວກເຮົາເພື່ອຕໍ່ອາຍຸການນຳໃຊ້')); ?>
                </p>

                <div class="d-flex flex-column" style="gap: 10px;">
                    <a href="https://wa.me/8562055949007" target="_blank" rel="noopener noreferrer"
                       class="btn btn-success btn-block font-weight-bold" style="border-radius: 8px; padding: 10px;">
                        <i class="fab fa-whatsapp mr-2"></i> +856 20 55 949 007
                    </a>
                    <a href="https://wa.me/8562095321848" target="_blank" rel="noopener noreferrer"
                       class="btn btn-success btn-block font-weight-bold" style="border-radius: 8px; padding: 10px;">
                        <i class="fab fa-whatsapp mr-2"></i> +856 20 95 321 848
                    </a>
                    <a href="https://www.facebook.com/profile.php?id=100067585680024&locale=th_TH" target="_blank" rel="noopener noreferrer"
                       class="btn btn-primary btn-block font-weight-bold" style="border-radius: 8px; padding: 10px; background: #1877f2; border-color: #1877f2;">
                        <i class="fab fa-facebook mr-2"></i> WLaoDev
                    </a>
                </div>

                <a href="login.php" class="d-block mt-4" style="font-size: 0.85rem; color: #64748b;">
                    <i class="fas fa-arrow-left mr-1"></i> <?php echo htmlspecialchars(t('license_expired.back_to_login', 'ກັບຄືນໜ້າເຂົ້າສູ່ລະບົບ')); ?>
                </a>
            </div>
        </div>

        <!-- Footer Below Card -->
        <div class="bct-footer">
            <div class="bct-footer-copy">&copy; 2026 - Wlaodev POS By <a href="https://laodev.la/" target="_blank" rel="noopener noreferrer">LaoDev.la</a></div>
        </div>
    </div>

</body>
</html>
