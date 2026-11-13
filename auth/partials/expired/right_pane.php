<?php
/*
 * ສ່ວນ: ເຄິ່ງຂວາຂອງໜ້າໝົດອາຍຸ (ໂລ່ + ກະແຈ, ປ້າຍຂໍ້ມູນປອດໄພ), ປິດກາດ ແລະ script ປ່ຽນພາສາ
 * ຍ້າຍມາຈາກ auth/license_expired.php — ໃຊ້ຮ່ວມ: auth/license_expired.php, auth/access_expired.php
 */
?>
            <!-- ================= RIGHT PANE: 3D SECURITY LOCK & DATA SAFE GUARANTEE ================= -->
            <div class="login-right-pane d-flex flex-column align-items-center justify-content-center p-4 text-white text-center position-relative">
                
                <!-- 4 ປ້າຍສະຖານະຄວາມປອດໄພ ພ້ອມເອັບເຟັກ Shimmer -->
                <div class="cartoon-badge badge-1" style="background: rgba(239, 68, 68, 0.2); border-color: rgba(239, 68, 68, 0.4);">
                    <span class="cb-icon" style="color: #f87171;"><i class="fas fa-lock"></i></span>
                    <span>ລະບົບລັອກຊົ່ວຄາວ</span>
                </div>

                <div class="cartoon-badge badge-2">
                    <span class="cb-icon" style="color: #34d399;"><i class="fas fa-shield-alt"></i></span>
                    <span>ຂໍ້ມູນປອດໄພ 100%</span>
                </div>

                <div class="cartoon-badge badge-3">
                    <span class="cb-icon" style="color: #60a5fa;"><i class="fas fa-bolt"></i></span>
                    <span>ເປີດຄືນພາຍໃນ 5 ນາທີ</span>
                </div>

                <div class="cartoon-badge badge-4">
                    <span class="cb-icon" style="color: #fbbf24;"><i class="fas fa-database"></i></span>
                    <span>ສິນຄ້າ & ສະຕັອກຄົບຖ້ວນ</span>
                </div>

                <!-- 3D Security Shield & Lock Vector Art -->
                <div class="my-4 expired-shield-icon">
                    <svg width="150" height="150" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="shieldGrad" x1="15" y1="10" x2="105" y2="110" gradientUnits="userSpaceOnUse">
                                <stop offset="0%" stop-color="#3b82f6"/>
                                <stop offset="50%" stop-color="#1d4ed8"/>
                                <stop offset="100%" stop-color="#1e1b4b"/>
                            </linearGradient>
                            <linearGradient id="lockGold" x1="40" y1="45" x2="80" y2="95" gradientUnits="userSpaceOnUse">
                                <stop offset="0%" stop-color="#fef08a"/>
                                <stop offset="40%" stop-color="#facc15"/>
                                <stop offset="100%" stop-color="#ca8a04"/>
                            </linearGradient>
                            <filter id="shieldGlow" x="-20%" y="-20%" width="140%" height="140%">
                                <feDropShadow dx="0" dy="8" stdDeviation="12" flood-color="#2563eb" flood-opacity="0.5"/>
                            </filter>
                        </defs>
                        <!-- Shield Body -->
                        <path d="M60 10 L102 24 C102 68 84 96 60 110 C36 96 18 68 18 24 Z" fill="url(#shieldGrad)" filter="url(#shieldGlow)" stroke="#60a5fa" stroke-width="2.5"/>
                        <!-- Inner Red Alert Ribbon -->
                        <path d="M60 18 L94 30 C94 65 79 88 60 100 C41 88 26 65 26 30 Z" fill="rgba(239, 68, 68, 0.22)" stroke="#f87171" stroke-width="1.5" stroke-dasharray="4 3"/>
                        <!-- Lock Shackle -->
                        <path d="M46 54 V42 C46 34 52 28 60 28 C68 28 74 34 74 42 V54" stroke="url(#lockGold)" stroke-width="6" stroke-linecap="round"/>
                        <!-- Lock Body -->
                        <rect x="38" y="52" width="44" height="36" rx="8" fill="url(#lockGold)" stroke="#fef08a" stroke-width="1.5"/>
                        <!-- Keyhole -->
                        <circle cx="60" cy="67" r="4.5" fill="#78350f"/>
                        <path d="M58 68 L62 68 L63.5 78 L56.5 78 Z" fill="#78350f"/>
                    </svg>
                </div>

                <!-- Right Pane Text Header & Information -->
                <h4 class="font-weight-bold mb-2 text-white" style="letter-spacing: 0.3px;">
                    ລະບົບຖືກປົກປ້ອງດ້ວຍຄວາມປອດໄພ
                </h4>
                <p class="text-white-50 small px-3 mb-3" style="line-height: 1.6; max-width: 340px;">
                    ຂໍ້ມູນທຸລະກິດ, ບັນຊີ, ລາຍການສິນຄ້າ ແລະ ປະຫວັດການຂາຍທັງໝົດຖືກເກັບຮັກສາຢ່າງປອດໄພ. ພຽງແຕ່ຕິດຕໍ່ຕໍ່ອາຍຸການນຳໃຊ້ ທ່ານຈະສາມາດເຂົ້າໃຊ້ງານລະບົບໄດ້ທັນທີ!
                </p>

                <!-- Quick Feature Indicators -->
                <div class="d-flex flex-wrap justify-content-center" style="gap: 8px; max-width: 340px;">
                    <div class="lock-stat-card py-1 px-2.5">
                        <i class="fas fa-check-circle text-success"></i>
                        <span>ບໍ່ມີຂໍ້ມູນສູນຫາຍ</span>
                    </div>
                    <div class="lock-stat-card py-1 px-2.5">
                        <i class="fas fa-sync-alt text-info"></i>
                        <span>ເປີດລະບົບຕໍ່ເນື່ອງ 24/7</span>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- Required Scripts -->
    <script src="../plugins/jquery/jquery.min.js"></script>
    <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
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
    });
    </script>
