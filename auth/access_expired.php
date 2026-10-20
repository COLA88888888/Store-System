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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate">
    <title>Wlaodev POS</title>
    <link rel="shortcut icon" href="<?php echo $site_logo; ?>" type="image/x-icon">

    <!-- CSS Plugins & Theme -->
    <link rel="stylesheet" href="../plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../fontawesome-free-5.15.3-web/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/local-font.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../themes/login.css?v=<?php echo filemtime(__DIR__ . '/../themes/login.css'); ?>">

    <!-- Page-specific styling (kept local so login.php / license_expired.php are unaffected) -->
    <?php echo themeCssLink('access_expired.css', '../'); /* CSS ຍ້າຍໄປ themes/access_expired.css */ ?>
</head>
<body>

    <!-- Banner Background on Top -->
    <div class="login-banner-stripe"></div>
    <div class="login-banner-bg"></div>
    <div class="login-banner-overlay"></div>

    <!-- Access Expired Container -->
    <div class="login-card-container">
        <!-- Main Card -->
        <div class="login-card expired-card">
            <!-- Language Switcher Dropdown (Top Right of Card) -->
            <div class="dropdown" style="position: absolute; top: 16px; right: 16px; z-index: 100;">
                <?php $expiredCurrentLang = getCurrentLang(); ?>
                <button class="btn border-0 p-0 d-flex align-items-center justify-content-center"
                        type="button"
                        id="expiredLangDropdown"
                        data-toggle="dropdown"
                        aria-haspopup="true"
                        aria-expanded="false"
                        style="background: transparent; border: none; padding: 0 !important; outline: none;"
                        title="<?php echo htmlspecialchars(t('layout.lang_switch_title', 'ປ່ຽນພາສາ')); ?>">
                    <img src="../assets/img/flag_img/<?php echo POS_LANG_META[$expiredCurrentLang]['img']; ?>"
                         alt="<?php echo $expiredCurrentLang; ?>"
                         style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; box-shadow: 0 1.5px 4px rgba(0,0,0,0.3); border: 2px solid rgba(255,255,255,0.8);">
                </button>
                <div class="dropdown-menu dropdown-menu-right shadow-lg py-1 px-1 text-center"
                     aria-labelledby="expiredLangDropdown"
                     style="min-width: 60px; width: 60px; border-radius: 10px; border: 1px solid #e2e8f0; background: #ffffff;">
                    <?php foreach (POS_LANG_META as $langCode => $meta): ?>
                        <a class="dropdown-item p-1.5 d-flex align-items-center justify-content-center my-1 login-lang-flag-btn<?php echo $expiredCurrentLang === $langCode ? ' active' : ''; ?>"
                           href="javascript:void(0);"
                           data-lang="<?php echo htmlspecialchars($langCode); ?>"
                           style="border-radius: 6px; background: <?php echo $expiredCurrentLang === $langCode ? '#eaf2ff' : 'transparent'; ?>; transition: background 0.15s;"
                           title="<?php echo htmlspecialchars($meta['label']); ?>">
                            <img src="../assets/img/flag_img/<?php echo $meta['img']; ?>"
                                 alt="<?php echo $langCode; ?>"
                                 style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover; box-shadow: 0 1.5px 3px rgba(0,0,0,0.25);">
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Overlapping Circle Logo Badge -->
            <div class="logo-badge">
                <img src="<?php echo htmlspecialchars($site_logo); ?>" alt="POS Logo">
            </div>

            <!-- Title Header -->
            <div class="login-header-text">
                <h4 style="color: #dc2626;"><i class="fas fa-exclamation-triangle mr-1"></i> <?php echo htmlspecialchars(t('access_expired.title', 'ອາຍຸການນຳໃຊ້ບັນຊີຂອງທ່ານໝົດແລ້ວ')); ?></h4>
                <p class="expired-subtitle">
                    <?php echo highlightBrand(t('access_expired.body', 'ບໍ່ສາມາດເຂົ້າໃຊ້ງານລະບົບໄດ້ຈົນກວ່າທີມງານ WlaoDev ຈະຕໍ່ອາຍຸການນຳໃຊ້ໃຫ້')); ?>
                </p>
            </div>

            <div class="text-center" style="padding: 4px 8px 20px;">
                <p style="font-size: 0.95rem; font-weight: 700; color: #1e293b; margin-bottom: 14px;">
                    <?php echo highlightBrand(t('access_expired.contact_us', 'ກະລຸນາຕິດຕໍ່ທີມງານ WlaoDev ເພື່ອຕໍ່ອາຍຸການນຳໃຊ້')); ?>
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

                <a href="login.php" class="back-to-login-link">
                    <i class="fas fa-arrow-left mr-1"></i> <?php echo htmlspecialchars(t('access_expired.back_to_login', 'ກັບຄືນໜ້າເຂົ້າສູ່ລະບົບ')); ?>
                </a>
            </div>
        </div>

        <!-- Footer Below Card -->
        <div class="bct-footer">
            <div class="bct-footer-copy">&copy; 2026 - Wlaodev POS By <a href="https://laodev.la/" target="_blank" rel="noopener noreferrer">LaoDev.la</a></div>
        </div>
    </div>

    <!-- Required Scripts (language switcher only) -->
    <script src="../plugins/jquery/jquery.min.js"></script>
    <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.login-lang-flag-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var lang = btn.getAttribute('data-lang');
                if (!lang) return;
                fetch('../lang/set_lang.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'lang=' + encodeURIComponent(lang)
                }).then(function() {
                    window.location.reload();
                }).catch(function() {
                    window.location.reload();
                });
            });
        });
    </script>
</body>
</html>
