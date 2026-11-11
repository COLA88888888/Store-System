<?php
/*
 * ສ່ວນ: ປຸ່ມປ່ຽນພາສາມຸມຂວາເທິງ + ໂລໂກ້ (ບໍ່ມີພື້ນຫຼັງ) ຂອງກາດສີຂາວ — ແບບດຽວກັບໜ້າເຂົ້າສູ່ລະບົບ
 * (auth/partials/login/left_pane.php). ໃຊ້ຮ່ວມ: auth/license_expired.php, auth/access_expired.php
 * ຕ້ອງມີ $site_logo_png ກ່ອນ require. ການກົດປ່ຽນພາສາ: script ໃນ right_pane.php
 */
?>                <div class="dropdown login-lang-corner">
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

                <div class="login-hero-logo">
                    <img src="<?php echo htmlspecialchars($site_logo_png); ?>" alt="WLaoDev Logo" class="login-hero-logo-img">
                </div>
