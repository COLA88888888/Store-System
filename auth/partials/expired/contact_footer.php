<?php
/*
 * ສ່ວນ: ປຸ່ມຕິດຕໍ່ຕໍ່ອາຍຸ (ໂທ / WhatsApp / ເຟສບຸກ), ລິ້ງກັບໜ້າເຂົ້າສູ່ລະບົບ ແລະ ທ້າຍກາດ
 * ໃຊ້ຮ່ວມ: auth/license_expired.php, auth/access_expired.php (ຢູ່ລຸ່ມຂໍ້ຄວາມແຈ້ງໝົດອາຍຸຂອງແຕ່ລະໜ້າ)
 */
?>                    <div class="text-center mb-2" style="font-size: 0.90rem; font-weight: 700; color: #334155;">
                        <i class="fas fa-headset mr-1 text-primary"></i> <?php echo tlh('ຕິດຕໍ່ຕໍ່ອາຍຸການນຳໃຊ້ໄດ້ທັນທີ:'); ?>
                    </div>

                    <div class="d-flex flex-column" style="gap: 9px;">
                        <div class="d-flex" style="gap: 8px;">
                            <a href="tel:02055949007" class="btn btn-outline-danger flex-fill contact-btn" style="border-width: 1.5px;">
                                <i class="fas fa-phone-alt mr-1.5"></i> 020 55949007
                            </a>
                            <a href="https://wa.me/8562055949007" target="_blank" rel="noopener noreferrer" class="btn btn-success contact-btn px-3" style="background-color: #22c55e; border-color: #22c55e;">
                                <i class="fab fa-whatsapp mr-1"></i> WhatsApp
                            </a>
                        </div>
                        <div class="d-flex" style="gap: 8px;">
                            <a href="tel:02095321848" class="btn btn-outline-danger flex-fill contact-btn" style="border-width: 1.5px;">
                                <i class="fas fa-phone-alt mr-1.5"></i> 020 95321848
                            </a>
                            <a href="https://wa.me/8562095321848" target="_blank" rel="noopener noreferrer" class="btn btn-success contact-btn px-3" style="background-color: #22c55e; border-color: #22c55e;">
                                <i class="fab fa-whatsapp mr-1"></i> WhatsApp
                            </a>
                        </div>
                        <a href="https://www.facebook.com/profile.php?id=100067585680024&locale=th_TH" target="_blank" rel="noopener noreferrer"
                           class="btn btn-primary btn-block contact-btn" style="background: linear-gradient(135deg, #1877f2, #0d65d9); border: none;">
                            <i class="fab fa-facebook mr-2"></i> <?php echo tlh('ເຟສບຸກ'); ?>: WLaoDev
                        </a>
                    </div>

                    <div class="text-center mt-3">
                        <a href="login.php" class="btn btn-link font-weight-bold text-secondary p-0" style="font-size: 0.86rem; text-decoration: none;">
                            <i class="fas fa-arrow-left mr-1"></i> <?php echo htmlspecialchars(t('license_expired.back_to_login', 'ກັບຄືນໜ້າເຂົ້າສູ່ລະບົບ')); ?>
                        </a>
                    </div>
                </div>

                <div class="login-pane-footer mt-3">
                    <div class="login-pane-note">
                        <?php echo tlh('ລະບົບບໍລິຫານຈັດການຮ້ານຄ້າ ແລະ ຈຸດຂາຍທັນສະໄໝ'); ?> WlaoDev POS
                    </div>
                    <div class="login-pane-links">
                        <span>&copy; 2026 <a href="https://laodev.la/" target="_blank" rel="noopener noreferrer">LaoDev.la</a></span>
                        <a href="https://www.facebook.com/profile.php?id=100067585680024&locale=th_TH" target="_blank" rel="noopener noreferrer">
                            <i class="fab fa-facebook" style="color:#1877f2;"></i> WLaoDev
                        </a>
                        <a href="https://wa.me/8562055949007" target="_blank" rel="noopener noreferrer">
                            <i class="fab fa-whatsapp" style="color:#16a34a;"></i> +856 20 55 949 007
                        </a>
                    </div>
                </div>
            </div>
