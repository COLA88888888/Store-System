<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true
    ]);
    session_start();
}
if (isset($_SESSION['checked']) && $_SESSION['checked'] === 1 && !empty($_SESSION['user_id'])) {
    header("Location: ../home/dashboard.php");
    exit();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lang/translator.php';
$site_logo = '../assets/img/logosystem/Wlaodev.jpg';
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
    <link rel="stylesheet" href="../sweetalert/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../assets/css/local-font.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../themes/login.css?v=<?php echo filemtime(__DIR__ . '/../themes/login.css'); ?>">
</head>
<body>

    <!-- Banner Background on Top -->
    <div class="login-banner-stripe"></div>
    <div class="login-banner-bg"></div>
    <div class="login-banner-overlay"></div>

    <!-- Login Container -->
    <div class="login-card-container">
        <!-- Main Card -->
        <div class="login-card">
            <!-- Language Switcher Dropdown (Top Right of Card) -->
            <div class="dropdown" style="position: absolute; top: 16px; right: 16px; z-index: 100;">
                <?php $loginCurrentLang = getCurrentLang(); ?>
                <button class="btn border-0 p-0 d-flex align-items-center justify-content-center" 
                        type="button" 
                        id="loginLangDropdown" 
                        data-toggle="dropdown" 
                        aria-haspopup="true" 
                        aria-expanded="false" 
                        style="background: transparent; border: none; padding: 0 !important; outline: none;"
                        title="<?php echo htmlspecialchars(t('layout.lang_switch_title', 'ປ່ຽນພາສາ')); ?>">
                    <img src="../assets/img/flag_img/<?php echo POS_LANG_META[$loginCurrentLang]['img']; ?>"
                         alt="<?php echo $loginCurrentLang; ?>"
                         style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; box-shadow: 0 1.5px 4px rgba(0,0,0,0.3); border: 2px solid rgba(255,255,255,0.8);">
                </button>
                <div class="dropdown-menu dropdown-menu-right shadow-lg py-1 px-1 text-center" 
                     aria-labelledby="loginLangDropdown" 
                     style="min-width: 60px; width: 60px; border-radius: 10px; border: 1px solid #e2e8f0; background: #ffffff;">
                    <?php foreach (POS_LANG_META as $langCode => $meta): ?>
                        <a class="dropdown-item p-1.5 d-flex align-items-center justify-content-center my-1 login-lang-flag-btn<?php echo $loginCurrentLang === $langCode ? ' active' : ''; ?>" 
                           href="javascript:void(0);" 
                           data-lang="<?php echo htmlspecialchars($langCode); ?>" 
                           style="border-radius: 6px; background: <?php echo $loginCurrentLang === $langCode ? '#eaf2ff' : 'transparent'; ?>; transition: background 0.15s;" 
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
                <h4><?php echo htmlspecialchars(t('login.title', 'ເຂົ້າສູ່ລະບົບ Wlaodev POS')); ?></h4>
                <!-- <p>POS Wlaodev</p> -->
                <!-- <div class="login-hint">ກະລຸນາເຂົ້າສູ່ລະບົບ...</div> -->
            </div>

            <!-- Login Form -->
            <form id="loginForm" autocomplete="off" novalidate>
                <!-- Autofill prevention -->
                <input type="text" style="display:none;" aria-hidden="true">
                <input type="password" style="display:none;" aria-hidden="true">

                <!-- Username Input -->
                <div class="custom-input-group">
                    <label for="username"><?php echo htmlspecialchars(t('login.username_label', 'ຊື່ຜູ້ໃຊ້:')); ?></label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="username" name="username" class="form-control-custom" placeholder="<?php echo htmlspecialchars(t('login.username_placeholder', 'ປ້ອນຊື່ຜູ້ໃຊ້ງານ...')); ?>" autocomplete="off" required>
                    </div>
                </div>

                <!-- Password Input -->
                <div class="custom-input-group">
                    <label for="password"><?php echo htmlspecialchars(t('login.password_label', 'ລະຫັດຜ່ານ:')); ?></label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="form-control-custom" placeholder="<?php echo htmlspecialchars(t('login.password_placeholder', 'ປ້ອນລະຫັດຜ່ານ...')); ?>" autocomplete="new-password" style="padding-right: 42px !important;" required>
                        <button type="button" class="btn-toggle-pass" id="togglePassword" tabindex="-1">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-login-custom" id="btnLogin">
                    <i class="fas fa-user-check mr-1"></i> <?php echo htmlspecialchars(t('login.btn_login', 'ເຂົ້າສູ່ລະບົບ')); ?>
                </button>
            </form>
        </div>

        <!-- Footer Below Card -->
        <div class="bct-footer">
            <div class="bct-footer-copy">&copy; 2026 - Wlaodev POS By <a href="https://laodev.la/" target="_blank" rel="noopener noreferrer">LaoDev.la</a></div>
            <div class="bct-social-links">
                <a href="https://www.facebook.com/profile.php?id=100067585680024&locale=th_TH" target="_blank" rel="noopener noreferrer" class="bct-social-link">
                    <i class="fab fa-facebook"></i> WLaoDev
                </a>
                <a href="https://wa.me/8562055949007" target="_blank" rel="noopener noreferrer" class="bct-social-link">
                    <i class="fab fa-whatsapp"></i> +856 20 55 949 007
                </a>
            </div>
        </div>
    </div>

    <!-- Required Scripts -->
    <script src="../plugins/jquery/jquery.min.js"></script>
    <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../sweetalert/dist/sweetalert2.all.min.js"></script>

    <script>
        var I18N_LOGIN = <?php echo tjson([
            'login.btn_login' => 'ເຂົ້າສູ່ລະບົບ',
            'login.btn_login_loading' => 'ກຳລັງກວດສອບ...',
            'login.token_expired_title' => 'Token ໝົດອາຍຸແລ້ວ',
            'login.token_expired_text' => 'ກະລຸນາເຂົ້າສູ່ລະບົບໃໝ່',
            'login.ok' => 'ຕົກລົງ',
            'login.warning_title' => 'ແຈ້ງເຕືອນ',
            'login.err_both_required' => 'ກະລຸນາປ້ອນຊື່ຜູ້ໃຊ້ງານ ແລະ ລະຫັດຜ່ານ!',
            'login.err_username_required' => 'ກະລຸນາປ້ອນຊື່ຜູ້ໃຊ້ງານ!',
            'login.err_password_required' => 'ກະລຸນາປ້ອນລະຫັດຜ່ານ!',
            'login.err_invalid' => 'ລະຫັດບໍ່ຖືກຕ້ອງ ກະລຸນາລອງໃໝ່',
        ]); ?>;

        $(document).ready(function() {
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

            sessionStorage.removeItem('StoreMini.isSessionActive');

            setTimeout(function() {
                $('#username').val('');
                $('#password').val('');
            }, 150);

            // Check if session/token has expired
            <?php if (isset($_GET['expired'])): ?>
            Swal.fire({
                icon: 'warning',
                title: I18N_LOGIN['login.token_expired_title'],
                text: I18N_LOGIN['login.token_expired_text'],
                confirmButtonColor: '#007bff',
                confirmButtonText: I18N_LOGIN['login.ok'],
                heightAuto: false
            }).then(function() {
                window.history.replaceState({}, document.title, window.location.pathname);
            });
            <?php endif; ?>

            // Toggle Password
            $('#togglePassword').on('click', function() {
                const passwordField = $('#password');
                const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
                passwordField.attr('type', type);
                $('#eyeIcon').toggleClass('fa-eye fa-eye-slash');
            });

            // Submit Form
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();
                
                var username = $.trim($('#username').val());
                var password = $('#password').val();
                var btn = $('#btnLogin');

                if (!username && !password) {
                    Swal.fire({
                        icon: 'warning',
                        title: I18N_LOGIN['login.warning_title'],
                        text: I18N_LOGIN['login.err_both_required'],
                        confirmButtonColor: '#007bff',
                        confirmButtonText: I18N_LOGIN['login.ok'],
                        heightAuto: false
                    }).then(function() {
                        $('#username').focus();
                    });
                    return;
                }

                if (!username) {
                    Swal.fire({
                        icon: 'warning',
                        title: I18N_LOGIN['login.warning_title'],
                        text: I18N_LOGIN['login.err_username_required'],
                        confirmButtonColor: '#007bff',
                        confirmButtonText: I18N_LOGIN['login.ok'],
                        heightAuto: false
                    }).then(function() {
                        $('#username').focus();
                    });
                    return;
                }

                if (!password) {
                    Swal.fire({
                        icon: 'warning',
                        title: I18N_LOGIN['login.warning_title'],
                        text: I18N_LOGIN['login.err_password_required'],
                        confirmButtonColor: '#007bff',
                        confirmButtonText: I18N_LOGIN['login.ok'],
                        heightAuto: false
                    }).then(function() {
                        $('#password').focus();
                    });
                    return;
                }

                btn.prop('disabled', true).html('<i class="fas fa-circle-notch fa-spin"></i> ' + I18N_LOGIN['login.btn_login_loading']);

                $.ajax({
                    url: 'check_user.php',
                    type: 'POST',
                    data: { username: username, password: password },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            var tabToken = 'TOKEN_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                            sessionStorage.setItem('StoreMini.pos_tab_active', tabToken);
                            sessionStorage.setItem('StoreMini.isSessionActive', 'true');
                            window.location.href = response.redirect;
                        } else if (response.expired) {
                            window.location.href = response.redirect;
                        } else {
                            btn.prop('disabled', false).html('<i class="fas fa-user-check mr-1"></i> ' + I18N_LOGIN['login.btn_login']);
                            $('#loginForm')[0].reset();
                            $('#username').val('').focus();
                            $('#password').val('');

                            Swal.fire({
                                icon: 'error',
                                title: I18N_LOGIN['login.warning_title'],
                                text: response.message || I18N_LOGIN['login.err_invalid'],
                                confirmButtonColor: '#007bff',
                                confirmButtonText: I18N_LOGIN['login.ok'],
                                heightAuto: false
                            }).then(function() {
                                $('#username').focus();
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        btn.prop('disabled', false).html('<i class="fas fa-user-check mr-1"></i> ' + I18N_LOGIN['login.btn_login']);
                        $('#loginForm')[0].reset();
                        $('#username').val('').focus();
                        $('#password').val('');

                        Swal.fire({
                            icon: 'error',
                            title: I18N_LOGIN['login.warning_title'],
                            text: I18N_LOGIN['login.err_invalid'],
                            confirmButtonColor: '#007bff',
                            confirmButtonText: I18N_LOGIN['login.ok'],
                            heightAuto: false
                        }).then(function() {
                            $('#username').focus();
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
