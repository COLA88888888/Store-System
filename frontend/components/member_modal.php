<?php
/**
 * Popup ສະມາຊິກ: ລົງທະບຽນ (ຟອມປ້ອນຂໍ້ມູນ) + ເຂົ້າສູ່ລະບົບ (ເບີໂທ/ອີເມວ + Google) (ສະຫຼັບດ້ວຍລິ້ງລຸ່ມຟອມ) — ຊ້າຍ = ຟອມ WlaoDev POS, ຂວາ = ຮູບເທິງພື້ນຟ້າເຂັ້ມ.
 * ເປີດ: ປຸ່ມ [data-member-open="register|login"] (layouts/navbar.php). ສົ່ງຟອມ: assets/js/member.js → api/member.php
 * ການລົງທະບຽນມີແຕ່ຢູ່ໃນ popup ນີ້ (ບໍ່ມີໜ້າ register ແຍກ — URL ເກົ່າ pages/register → ໜ້າຫຼັກ #register). ສຳເລັດ → ໂຫຼດໜ້າໃໝ່. ບໍ່ມີ JS → ລິ້ງເຂົ້າສູ່ລະບົບໄປ pages/member_login.php.
 * ໂຫຼດໂດຍ layouts/footer.php ສະເພາະຕອນຍັງບໍ່ໄດ້ login. CSS: .mm-* ໃນ assets/css/components.css
 */
$mmEyeClosed = '<svg class="mm-ico-closed" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
$mmEyeOpen = '<svg class="mm-ico-open" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>';
$mmIcoUser = '<svg class="mm-input-ico" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M12 12a4.5 4.5 0 1 0-4.5-4.5A4.5 4.5 0 0 0 12 12zm0 2.25c-3.85 0-7.5 1.93-7.5 4.75v1.25h15V19c0-2.82-3.65-4.75-7.5-4.75z"/></svg>';
$mmIcoPhone = '<svg class="mm-input-ico" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.01-.24 11.36 11.36 0 0 0 3.58.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 11.36 11.36 0 0 0 .57 3.58 1 1 0 0 1-.25 1.01z"/></svg>';
$mmIcoMail = '<svg class="mm-input-ico" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 4.2-8 5-8-5V6l8 5 8-5z"/></svg>';
$mmIcoLock = '<svg class="mm-input-ico" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2zm-6 6.73V17a1 1 0 0 0 2 0v-1.27a2 2 0 1 0-2 0zM9 9V7a3 3 0 0 1 6 0v2z"/></svg>';
$mmIcoLogin = '<svg class="mm-btn-ico" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>';

$mmPass = function (string $name, string $placeholder, string $autocomplete) use ($mmEyeClosed, $mmEyeOpen, $mmIcoLock) {
    return '<span class="mm-input-wrap mm-pass">'
        . $mmIcoLock
        . '<input class="input" name="' . $name . '" type="password" required minlength="6" autocomplete="' . $autocomplete . '" placeholder="' . e($placeholder) . '">'
        . '<button type="button" class="mm-eye" data-mm-eye aria-label="' . te('ສະແດງລະຫັດຜ່ານ') . '">' . $mmEyeClosed . $mmEyeOpen . '</button></span>';
};
?>
<div class="mm" id="memberModal" data-member-modal data-endpoint="<?php echo e(url('api/member.php')); ?>" hidden>
    <div class="mm-backdrop" data-mm-close></div>
    <div class="mm-dialog" role="dialog" aria-modal="true" aria-labelledby="mmTitle">
        <div class="mm-form-side">
            <!-- ປຸ່ມປ່ຽນພາສາມຸມຂວາເທິງ ເລືອກພາສາໄດ້ຈິງ -->
            <?php $currentModalLang = currentLang(); ?>
            <div class="mm-lang-corner" data-mm-lang>
                <button type="button" class="mm-lang-btn" data-mm-lang-toggle aria-label="<?php echo te('ປ່ຽນພາສາ'); ?>" title="<?php echo te('ປ່ຽນພາສາ'); ?>">
                    <img class="mm-lang-flag-current" src="<?php echo asset('img/flags/' . (SITE_LANGS[$currentModalLang]['flag'] ?? 'lo.webp')); ?>" alt="<?php echo e(SITE_LANGS[$currentModalLang]['label'] ?? 'ພາສາລາວ'); ?>" width="34" height="34">
                </button>
                <div class="mm-lang-menu" role="menu">
                    <?php foreach (SITE_LANGS as $code => $meta): ?>
                        <a class="mm-lang-item<?php echo $code === $currentModalLang ? ' active' : ''; ?>" href="<?php echo e(langUrl($code)); ?>" data-mm-lang-link title="<?php echo e($meta['label']); ?>">
                            <img src="<?php echo asset('img/flags/' . $meta['flag']); ?>" alt="" width="22" height="22">
                            <span><?php echo e($meta['label']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mm-brand-top">
                <img class="mm-brand-logo" src="<?php echo asset('img/logo.png'); ?>" alt="WlaoDev" width="116" height="76">
                <h2 class="mm-gradient-title" id="mmTitle">
                    <span class="mm-brand-blue">WlaoDev POS</span>
                    <span class="mm-brand-purple"><?php echo te('ສຳລັບທຸລະກິດທ່ານ'); ?></span>
                </h2>
            </div>

            <!-- ລົງທະບຽນ (ຟອມໃນ popup): ເກັບແຕ່ ຊື່, ເບີໂທ, ອີເມວ (+ ລະຫັດຜ່ານສຳລັບເຂົ້າສູ່ລະບົບ) → api/member.php action=register.
                 ທີ່ຢູ່ຈັດສົ່ງ ປ້ອນຕອນສັ່ງຊື້ ຫຼື ໃນໜ້າບັນຊີ. ຫຼື ລົງທະບຽນດ້ວຍ Google (ໃຊ້ອີເມວຂອງບັນຊີ Google) -->
            <form class="mm-view" data-mm-view="register" novalidate hidden>
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="register">
                <?php /* ຊ່ອງດັກ bot — ຄົນບໍ່ເຫັນ (CSS .mm-hp); ຖ້າມີຄ່າ = bot → api/member.php ປະຕິເສດ */ ?>
                <label class="mm-hp" aria-hidden="true">Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                <div class="mm-alert" data-mm-alert hidden></div>

                <label class="mm-field">
                    <span><?php echo te('ຊື່ ແລະ ນາມສະກຸນ *'); ?></span>
                    <span class="mm-input-wrap"><?php echo $mmIcoUser; ?><input class="input" name="name" required maxlength="150" autocomplete="name" placeholder="<?php echo te('ເຊັ່ນ: ສົມຊາຍ ພົມມະວົງ'); ?>"></span>
                    <small data-mm-err="name"></small>
                </label>
                <?php /* ວັນເດືອນປີເກີດ: 3 ກ່ອງເລືອກ ວັນ | ເດືອນ | ປີ (memberBirthSelects — ບັງຄັບ, ອາຍຸ 10–110 ປີ) */ ?>
                <div class="mm-field">
                    <span><?php echo te('ວັນເດືອນປີເກີດ (ປີ ຄ.ສ.) *'); ?></span>
                    <?php echo memberBirthSelects('', true); ?>
                    <small data-mm-err="birth_date"></small>
                </div>

                <div class="mm-row">
                    <label class="mm-field">
                        <span><?php echo te('ເບີໂທມືຖື * (ເບີຈິງ)'); ?></span>
                        <span class="mm-input-wrap"><?php echo $mmIcoPhone; ?><input class="input" name="tel" type="tel" inputmode="tel" required maxlength="20" autocomplete="tel" placeholder="020 5xxx xxxx"></span>
                        <small data-mm-err="tel"></small>
                    </label>
                    <label class="mm-field">
                        <span><?php echo te('ອີເມວ *'); ?></span>
                        <span class="mm-input-wrap"><?php echo $mmIcoMail; ?><input class="input" name="email" type="email" required maxlength="150" autocomplete="email" placeholder="name@gmail.com"></span>
                        <small data-mm-err="email"></small>
                    </label>
                </div>

                <div class="mm-row">
                    <label class="mm-field">
                        <span><?php echo te('ລະຫັດຜ່ານ * (ຢ່າງໜ້ອຍ 6 ຕົວ)'); ?></span>
                        <?php echo $mmPass('password', t('ປ້ອນລະຫັດຜ່ານ...'), 'new-password'); ?>
                        <small data-mm-err="password"></small>
                    </label>
                    <label class="mm-field">
                        <span><?php echo te('ຢືນຢັນລະຫັດຜ່ານ *'); ?></span>
                        <?php echo $mmPass('password2', t('ປ້ອນອີກຄັ້ງ...'), 'new-password'); ?>
                        <small data-mm-err="password2"></small>
                    </label>
                </div>

                <label class="mm-check">
                    <input type="checkbox" name="accept_terms" value="1">
                    <span><?php echo te('ຍອມຮັບ'); ?> <a href="<?php echo url('pages/policy.php?type=terms'); ?>" target="_blank"><?php echo te('ເງື່ອນໄຂການໃຊ້ບໍລິການ'); ?></a></span>
                </label>
                <small class="mm-err-line" data-mm-err="accept_terms"></small>
                <?php /* ລະຫັດຢືນຢັນ 6 ຕົວທາງອີເມວ — ສະແດງຫຼັງ server ຕອບ need_otp (assets/js/member.js); ຊ່ອງ data-otp-req ກາຍເປັນ required ຕອນນັ້ນ */ ?>
                <div class="mm-otp" data-mm-otp hidden>
                    <label class="mm-field">
                        <span><?php echo te('ລະຫັດຢືນຢັນ 6 ຕົວ (ຈາກອີເມວ) *'); ?></span>
                        <span class="mm-input-wrap"><?php echo $mmIcoMail; ?><input class="input mm-otp-input" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" data-otp-req placeholder="______"></span>
                        <small class="mm-err-line" data-mm-err="otp"></small>
                    </label>
                    <button type="button" class="mm-otp-resend" data-mm-otp-resend><?php echo te('ບໍ່ໄດ້ຮັບລະຫັດ? ສົ່ງລະຫັດໃໝ່'); ?></button>
                </div>

                <button class="mm-submit" type="submit">
                    <?php echo $mmIcoLogin; ?>
                    <span><?php echo te('ລົງທະບຽນ'); ?></span>
                </button>
                <?php $googleBtnText = 'signup_with'; require __DIR__ . '/google_button.php'; ?>
                <p class="mm-switch"><?php echo te('ມີບັນຊີແລ້ວ?'); ?> <a href="<?php echo url('pages/member_login.php'); ?>" data-mm-go="login"><?php echo te('ເຂົ້າສູ່ລະບົບ'); ?></a></p>
            </form>

            <!-- ເຂົ້າສູ່ລະບົບ -->
            <form class="mm-view" data-mm-view="login" novalidate autocomplete="off">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="login">
                <div class="mm-alert" data-mm-alert hidden></div>

                <label class="mm-field">
                    <span><?php echo te('ເບີໂທ ຫຼື ອີເມວ:'); ?></span>
                    <span class="mm-input-wrap">
                        <?php echo $mmIcoUser; ?>
                        <input class="input" name="login" type="text" required maxlength="150" autocomplete="off" readonly data-mm-noautofill placeholder="<?php echo te('020 xxxx xxxx ຫຼື name@gmail.com'); ?>">
                    </span>
                </label>

                <label class="mm-field">
                    <span><?php echo te('ລະຫັດຜ່ານ:'); ?></span>
                    <?php echo str_replace('<input ', '<input readonly data-mm-noautofill ', $mmPass('password', t('ປ້ອນລະຫັດຜ່ານ...'), 'new-password')); /* ບໍ່ໃຫ້ browser ຕື່ມລະຫັດທີ່ບັນທຶກໄວ້ເອງ (data-mm-noautofill ໃນ member.js) */ ?>
                </label>
                <?php /* ລືມລະຫັດຜ່ານ → ແທັບ forgot ຂ້າງລຸ່ມ (ລະຫັດ OTP ທາງອີເມວ → ຕັ້ງລະຫັດໃໝ່ເອງ) */ ?>
                <a class="mm-forgot" href="#forgot" data-mm-go="forgot"><?php echo te('ລືມລະຫັດຜ່ານ?'); ?></a>

                <button class="mm-submit" type="submit">
                    <?php echo $mmIcoLogin; ?>
                    <span><?php echo te('ເຂົ້າສູ່ລະບົບ'); ?></span>
                </button>

                <?php $googleBtnText = 'signin_with'; require __DIR__ . '/google_button.php'; ?>
                <p class="mm-switch"><?php echo te('ຍັງບໍ່ມີບັນຊີ?'); ?> <a href="#register" data-mm-go="register"><?php echo te('ລົງທະບຽນຟຣີ'); ?></a></p>
            </form>

            <!-- ລືມລະຫັດຜ່ານ: ເບີ/ອີເມວ → ລະຫັດ 6 ຕົວໄປອີເມວຂອງບັນຊີ → ລະຫັດ + ລະຫັດຜ່ານໃໝ່ (api/member.php action=forgot) -->
            <form class="mm-view" data-mm-view="forgot" novalidate autocomplete="off" hidden>
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="forgot">
                <div class="mm-alert" data-mm-alert hidden></div>
                <?php /* ອີເມວເທົ່ານັ້ນ — ຍັງບໍ່ມີ OTP ທາງ SMS (ລະຫັດສົ່ງໄປອີເມວຂອງບັນຊີ) */ ?>
                <p class="mm-forgot-intro"><?php echo te('ປ້ອນອີເມວທີ່ໃຊ້ລົງທະບຽນ — ພວກເຮົາຈະສົ່ງລະຫັດ 6 ຕົວໄປອີເມວນັ້ນ ເພື່ອຕັ້ງລະຫັດຜ່ານໃໝ່'); ?></p>
                <label class="mm-field">
                    <span><?php echo te('ອີເມວ:'); ?></span>
                    <span class="mm-input-wrap"><?php echo $mmIcoMail; ?><input class="input" name="login" type="email" required maxlength="150" autocomplete="email" placeholder="name@gmail.com"></span>
                    <small class="mm-err-line" data-mm-err="login"></small>
                </label>
                <?php /* ລະຫັດຢືນຢັນ 6 ຕົວທາງອີເມວ — ສະແດງຫຼັງ server ຕອບ need_otp (assets/js/member.js); ຊ່ອງ data-otp-req ກາຍເປັນ required ຕອນນັ້ນ */ ?>
                <div class="mm-otp" data-mm-otp hidden>
                    <label class="mm-field">
                        <span><?php echo te('ລະຫັດຢືນຢັນ 6 ຕົວ (ຈາກອີເມວ) *'); ?></span>
                        <span class="mm-input-wrap"><?php echo $mmIcoMail; ?><input class="input mm-otp-input" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" data-otp-req placeholder="______"></span>
                        <small class="mm-err-line" data-mm-err="otp"></small>
                    </label>
                    <div class="mm-row">
                        <label class="mm-field">
                            <span><?php echo te('ລະຫັດຜ່ານໃໝ່ *'); ?></span>
                            <?php echo str_replace('<input ', '<input data-otp-req ', str_replace(' required', '', $mmPass('password', t('ຢ່າງໜ້ອຍ 6 ຕົວ'), 'new-password'))); ?>
                            <small class="mm-err-line" data-mm-err="password"></small>
                        </label>
                        <label class="mm-field">
                            <span><?php echo te('ຢືນຢັນລະຫັດຜ່ານ *'); ?></span>
                            <?php echo str_replace('<input ', '<input data-otp-req ', str_replace(' required', '', $mmPass('password2', t('ປ້ອນອີກຄັ້ງ...'), 'new-password'))); ?>
                            <small class="mm-err-line" data-mm-err="password2"></small>
                        </label>
                    </div>
                    <button type="button" class="mm-otp-resend" data-mm-otp-resend><?php echo te('ບໍ່ໄດ້ຮັບລະຫັດ? ສົ່ງລະຫັດໃໝ່'); ?></button>
                </div>

                <button class="mm-submit" type="submit">
                    <?php echo $mmIcoLogin; ?>
                    <span data-mm-submit-text data-otp-text="<?php echo te('ຕັ້ງລະຫັດຜ່ານໃໝ່'); ?>"><?php echo te('ສົ່ງລະຫັດໄປອີເມວ'); ?></span>
                </button>
                <p class="mm-switch"><a href="#login" data-mm-go="login">← <?php echo te('ກັບໄປເຂົ້າສູ່ລະບົບ'); ?></a></p>
            </form>
        </div>

        <aside class="mm-visual" aria-hidden="true">
            <?php require __DIR__ . '/member_cartoon_stage.php'; ?>
        </aside>
        <button type="button" class="mm-x" data-mm-close aria-label="<?php echo te('ປິດ'); ?>">×</button>
    </div>
</div>
