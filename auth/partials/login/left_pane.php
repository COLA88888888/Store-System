<?php
/*
 * ສ່ວນ: ຟອມເຂົ້າສູ່ລະບົບ (ເຄິ່ງຊ້າຍ)
 * ປຸ່ມປ່ຽນພາສາ, ໂລໂກ້, ຟອມຊື່ຜູ້ໃຊ້/ລະຫັດຜ່ານ ແລະ ລິ້ງຕິດຕໍ່ທ້າຍຟອມ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ auth/login.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>            <!-- ================= LEFT PANE: LOGIN FORM ================= -->
            <div class="login-left-pane">
                <!-- ປຸ່ມປ່ຽນພາສາມຸມຂວາເທິງ -->
                <div class="dropdown login-lang-corner">
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
                             class="login-lang-flag-icon">
                    </button>
                    <div class="dropdown-menu dropdown-menu-right shadow-lg py-1 px-1 text-center"
                         aria-labelledby="loginLangDropdown"
                         style="min-width: 68px; width: 68px; border-radius: 12px; border: 1px solid #e2e8f0; background: #ffffff;">
                        <?php foreach (POS_LANG_META as $langCode => $meta): ?>
                            <a class="dropdown-item p-1.5 d-flex align-items-center justify-content-center my-1 login-lang-flag-btn<?php echo $loginCurrentLang === $langCode ? ' active' : ''; ?>"
                               href="javascript:void(0);"
                               data-lang="<?php echo htmlspecialchars($langCode); ?>"
                               style="border-radius: 8px; background: <?php echo $loginCurrentLang === $langCode ? '#eaf2ff' : 'transparent'; ?>; transition: background 0.15s;"
                               title="<?php echo htmlspecialchars($meta['label']); ?>">
                                <img src="../assets/img/flag_img/<?php echo $meta['img']; ?>"
                                     alt="<?php echo $langCode; ?>"
                                     style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; box-shadow: 0 2px 5px rgba(0,0,0,0.25);">
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="login-form-top-wrap">
                    <!-- ໂລໂກ້ລະບົບຂະໜາດໃຫຍ່ ເຫັນແຕ່ໂລໂກ້ ບໍ່ມີກາດອ້ອມ -->
                    <div class="login-hero-logo">
                        <img src="<?php echo htmlspecialchars($site_logo); ?>" alt="WLaoDev Logo">
                    </div>

                    <!-- Header Title: WlaoDev POS ສຳລັບທຸລະກິດທ່ານ -->
                    <div class="login-title-group">
                        <h3 class="login-title-main">
                            <span class="brand-blue">WlaoDev POS</span> <span class="brand-purple"><?php echo tlh('ສຳລັບທຸລະກິດທ່ານ'); ?></span>
                        </h3>
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

                        <!-- Password Input (ເລີ່ມຕົ້ນເຊື່ອງລະຫັດ type="password" + ໄອຄອນຕາປິດ fa-eye-slash) -->
                        <div class="custom-input-group">
                            <label for="password"><?php echo htmlspecialchars(t('login.password_label', 'ລະຫັດຜ່ານ:')); ?></label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" id="password" name="password" class="form-control-custom" placeholder="<?php echo htmlspecialchars(t('login.password_placeholder', 'ປ້ອນລະຫັດຜ່ານ...')); ?>" autocomplete="new-password" style="padding-right: 42px !important;" required>
                                <button type="button" class="btn-toggle-pass" id="togglePassword" tabindex="-1" aria-label="Toggle Password">
                                    <i class="fas fa-eye-slash" id="eyeIcon"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn-login-custom" id="btnLogin">
                            <i class="fas fa-sign-in-alt mr-1"></i> <?php echo htmlspecialchars(t('login.btn_login', 'ເຂົ້າສູ່ລະບົບ')); ?>
                        </button>
                    </form>
                </div>

                <!-- Bottom Info & Social Links -->
                <div class="login-pane-footer">
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
