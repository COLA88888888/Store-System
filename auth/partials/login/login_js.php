<?php
/*
 * ສ່ວນ: JS ໜ້າເຂົ້າສູ່ລະບົບ
 * ຂໍ້ຄວາມແປພາສາ (I18N_LOGIN), ຕົວເລກເຄື່ອນໄຫວໃນຈໍ, ແຈ້ງເຕືອນໝົດອາຍຸ/ຖືກປິດ,
 * ປ່ຽນພາສາ ແລະ ສົ່ງຟອມເຂົ້າສູ່ລະບົບ (check_user.php) ພ້ອມແອນິເມຊັນໂຫຼດ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ auth/login.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>    <script>
        var I18N_LOGIN = <?php echo tjson([
            'login.btn_login' => 'ເຂົ້າສູ່ລະບົບ',
            'login.btn_login_loading' => 'ກຳລັງກວດສອບ...',
            'login.token_expired_title' => '<span style="letter-spacing: 2.5px; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; font-weight: 700; margin-right: 3px;">Token</span> ໝົດອາຍຸແລ້ວ',
            'login.token_expired_text' => 'ກະລຸນາເຂົ້າສູ່ລະບົບໃໝ່',
            'login.ok' => 'ຕົກລົງ',
            'login.warning_title' => 'ແຈ້ງເຕືອນ',
            'login.err_both_required' => 'ກະລຸນາປ້ອນຊື່ຜູ້ໃຊ້ງານ ແລະ ລະຫັດຜ່ານ!',
            'login.err_username_required' => 'ກະລຸນາປ້ອນຊື່ຜູ້ໃຊ້ງານ!',
            'login.err_password_required' => 'ກະລຸນາປ້ອນລະຫັດຜ່ານ!',
            'login.err_invalid' => 'ລະຫັດບໍ່ຖືກຕ້ອງ ກະລຸນາລອງໃໝ່',
            'login.err_account_disabled' => 'ບັນຊີນີ້ຖືກປິດໃຊ້ງານແລ້ວ ກະລຸນາຕິດຕໍ່ຜູ້ບໍລິຫານ',
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
                $('#password').val('').attr('type', 'password');
                $('#eyeIcon').removeClass('fa-eye').addClass('fa-eye-slash');
            }, 150);

            // Live Counting Numbers on Cartoon Laptop Dashboard + Interactive Eye Tracking
            (function initCartoonLiveActivity() {
                var salesEl = document.getElementById('svgLiveSales');
                var profitEl = document.getElementById('svgLiveProfit');
                var billsEl = document.getElementById('svgLiveBills');
                var pupilsEl = document.getElementById('chibiEyePupils');

                var baseSales = 48500000;
                var baseProfit = 14200000;
                var baseBills = 328;

                function fmtKip(n) {
                    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',') + ' <?php echo kipSymbol(); ?>';
                }

                // ຕົວເລກຍອດຂາຍ, ກຳໄລ ແລະ ຈຳນວນບິນແລ່ນຂຶ້ນແບບ Real-Time
                setInterval(function() {
                    var addBill = Math.floor(Math.random() * 2) + 1;
                    var addSale = addBill * (Math.floor(Math.random() * 8) + 2) * 15000;
                    var addProfit = Math.round(addSale * 0.28);

                    baseBills += addBill;
                    baseSales += addSale;
                    baseProfit += addProfit;

                    if (baseBills > 499) {
                        baseSales = 48500000;
                        baseProfit = 14200000;
                        baseBills = 328;
                    }

                    if (salesEl) salesEl.textContent = fmtKip(baseSales);
                    if (profitEl) profitEl.textContent = fmtKip(baseProfit);
                    if (billsEl) billsEl.textContent = baseBills + ' <?php echo tlj('ໃບບິນ'); ?>';
                }, 1400);

                // ຕາຂອງກະຕູນເຫຼືອບເບິ່ງຕາມເມົ້າ ແລະ ເບິ່ງຟອມເວລາພິມ
                if (pupilsEl) {
                    document.addEventListener('mousemove', function(e) {
                        var rect = pupilsEl.getBoundingClientRect();
                        if (!rect.width) return;
                        var cx = rect.left + rect.width / 2;
                        var cy = rect.top + rect.height / 2;
                        var dx = Math.max(-4.5, Math.min(4.5, (e.clientX - cx) / 55));
                        var dy = Math.max(-3.5, Math.min(3.5, (e.clientY - cy) / 55));
                        pupilsEl.style.transform = 'translate(' + dx.toFixed(2) + 'px, ' + dy.toFixed(2) + 'px)';
                    });

                    $('#username, #password').on('focus input', function() {
                        pupilsEl.style.transform = 'translate(-4px, 1.5px)';
                    }).on('blur', function() {
                        pupilsEl.style.transform = '';
                    });
                }
            })();

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

            // ບັນຊີຖືກປິດໃຊ້ງານລະຫວ່າງໃຊ້ງານ (ພະນັກງານລາອອກ)
            <?php if (isset($_GET['disabled'])): ?>
            Swal.fire({
                icon: 'error',
                title: I18N_LOGIN['login.warning_title'],
                text: I18N_LOGIN['login.err_account_disabled'],
                confirmButtonColor: '#007bff',
                confirmButtonText: I18N_LOGIN['login.ok'],
                heightAuto: false
            }).then(function() {
                window.history.replaceState({}, document.title, window.location.pathname);
            });
            <?php endif; ?>

            // Toggle Password: ຕາປິດ (fa-eye-slash) = ເຊື່ອງລະຫັດ (password) | ຕາບໍ່ປິດ (fa-eye) = ໂຊລະຫັດ (text)
            $('#togglePassword').on('click', function() {
                const passwordField = $('#password');
                const isHidden = passwordField.attr('type') === 'password';
                if (isHidden) {
                    passwordField.attr('type', 'text');
                    $('#eyeIcon').removeClass('fa-eye-slash').addClass('fa-eye');
                } else {
                    passwordField.attr('type', 'password');
                    $('#eyeIcon').removeClass('fa-eye').addClass('fa-eye-slash');
                }
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

                // ຟອມເປີດອອກຄົນລະຂ້າງ (ຊ້າຍ-ຂວາ ຄືເປີດຜ້າ) ທັນທີທີ່ກົດເຂົ້າສູ່ລະບົບ, ແລ້ວໂຕກຳລັງໂຫຼດ (ແບບດຽວກັບດາສບອດ)
                // ຄ່ອຍໆຂຶ້ນມາ (CSS #loginLoaderOverlay ໃນ themes/login.css)
                var $splitCard = $('#loginSplitCard');
                var $loginLoader = $('#loginLoaderOverlay');
                var curtainStartAt = Date.now();
                // ປິດລາຍຊື່ລະຫັດຜ່ານທີ່ browser ຈື່ໄວ້ (autofill) ທີ່ຍັງຄ້າງຢູ່ໃຕ້ຊ່ອງປ້ອນ — ໃຫ້ເຫັນແຕ່ໂລໂກ້ກຳລັງໂຫຼດ
                $('#username, #password').prop('readonly', true).blur();
                if (document.activeElement && document.activeElement.blur) document.activeElement.blur();
                $splitCard.addClass('is-curtain-opening');
                $loginLoader.css('display', 'flex');

                $.ajax({
                    url: 'check_user.php',
                    type: 'POST',
                    data: { username: username, password: password },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // ກະດິ່ງປະຕູ "ຕິງ-ຕ່ອງ" ແບບຮ້ານສະດວກຊື້ ເມື່ອພະນັກງານເຂົ້າລະບົບ (assets/js/pos-sounds.js)
                            if (window.MiniposSounds) MiniposSounds.doorChime();
                            var tabToken = 'TOKEN_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                            sessionStorage.setItem('StoreMini.pos_tab_active', tabToken);
                            sessionStorage.setItem('StoreMini.isSessionActive', 'true');

                            // ແຈ້ງເຕືອນຈໍລູກຄ້າ Real-Time ວ່າພະນັກງານເຂົ້າສູ່ລະບົບແລ້ວ (BroadcastChannel + localStorage)
                            try {
                                var enterPayload = {
                                    action: 'cashier_enter',
                                    user_id: response.user_id || 0,
                                    name: response.employee_name || response.username || '',
                                    gender: response.gender || '',
                                    store_id: response.store_id || 1,
                                    time: Date.now()
                                };
                                try {
                                    var bc1 = new BroadcastChannel('StoreMini.customer_display_channel');
                                    bc1.postMessage(enterPayload);
                                    setTimeout(function() { bc1.close(); }, 800);
                                } catch(e) {}
                                try {
                                    var bc2 = new BroadcastChannel('StoreMini.cashier_channel');
                                    bc2.postMessage(enterPayload);
                                    setTimeout(function() { bc2.close(); }, 800);
                                } catch(e) {}
                                localStorage.setItem('StoreMini.cashier_entered', JSON.stringify(enterPayload));
                            } catch(e) {}
                            try {
                                var pf = document.createElement('link');
                                pf.rel = 'prefetch';
                                pf.href = response.redirect;
                                document.head.appendChild(pf);
                            } catch(e) {}
                            // ໃຫ້ແອນິເມຊັນເປີດຜ້າຫຼິ້ນຈົບ ແລະ ສຽງກະດິ່ງດັງຄົບ 2 ໂນດກ່ອນ (~1.1 ວິ) ແລ້ວຈຶ່ງໄປໜ້າດາສບອດ
                            var elapsed = Date.now() - curtainStartAt;
                            var remain = Math.max(220, 1100 - elapsed);
                            setTimeout(function() {
                                window.location.href = response.redirect;
                            }, remain);
                        } else if (response.expired) {
                            window.location.href = response.redirect;
                        } else {
                            $splitCard.removeClass('is-curtain-opening');
                            $loginLoader.hide();
                            $('#username, #password').prop('readonly', false);
                            btn.prop('disabled', false).html('<i class="fas fa-sign-in-alt mr-1"></i> ' + I18N_LOGIN['login.btn_login']);
                            $('#loginForm')[0].reset();
                            $('#username').val('').focus();
                            $('#password').val('').attr('type', 'password');
                            $('#eyeIcon').removeClass('fa-eye').addClass('fa-eye-slash');

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
                        $splitCard.removeClass('is-curtain-opening');
                        $loginLoader.hide();
                        $('#username, #password').prop('readonly', false);
                        btn.prop('disabled', false).html('<i class="fas fa-sign-in-alt mr-1"></i> ' + I18N_LOGIN['login.btn_login']);
                        $('#loginForm')[0].reset();
                        $('#username').val('').focus();
                        $('#password').val('').attr('type', 'password');
                        $('#eyeIcon').removeClass('fa-eye').addClass('fa-eye-slash');

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
