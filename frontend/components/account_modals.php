<?php
/**
 * modal ຂອງໜ້າບັນຊີ (ແຍກອອກຈາກ pages/account.php — require ຢູ່ທ້າຍ <section class="ap">). CSS: .ap-modal ໃນ assets/css/account.css
 *   #acEditInfo    ຮູບໂປຣໄຟລ໌ + ຊື່ + ວັນເດືອນປີເກີດ (3 ກ່ອງເລືອກ memberBirthSelects)      → action=profile, section=info
 *   #acEditContact ເບີໂທ, WhatsApp, ອີເມວ (+ ລະຫັດຢືນຢັນອີເມວໃໝ່) + ທີ່ຢູ່ປັດຈຸບັນ ແຂວງ/ເມືອງ/ບ້ານ → action=profile, section=contact
 *   #acPw          ລະຫັດເກົ່າ + ລະຫັດໃໝ່ 2 ຄັ້ງ (ປຸ່ມຕາ) → action=password; "ລືມລະຫັດຜ່ານ?" → #acForgot
 *   #acForgot      ປ້ອນອີເມວ (ຕ້ອງກົງກັບອີເມວບັນຊີ, ຜິດ = ແຈ້ງທັນທີ) → action=pw_forgot (ສົ່ງລະຫັດຜ່ານໃໝ່ທາງອີເມວ)
 * ໃຊ້ຕົວແປຈາກ account.php: $member, $form, $errors, $pwErrors, $fgErrors, $field, $pwField, $emailOtpSent, $openModal
 */
$apX = '<button type="button" class="ap-modal-x" data-ap-close aria-label="' . te('ປິດ') . '"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>';
$apOpen = fn(string $id) => $openModal === $id ? ' data-open' : '';
$apPass = fn(string $name, string $ph, string $ac) => '<span class="ap-pass"><input class="input" name="' . $name . '" type="password" autocomplete="' . $ac . '" placeholder="' . e($ph) . '">'
    . '<button type="button" class="ap-eye" data-ap-eye aria-label="' . te('ສະແດງລະຫັດຜ່ານ') . '">'
    . '<svg class="ap-eye-off" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
    . '<svg class="ap-eye-on" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg></button></span>';
$apProv = getLaoProvincesAndDistricts();
$apDists = $apProv[$form['province'] ?? ''] ?? [];
?>
<!-- modal: ແກ້ໄຂຂໍ້ມູນບັນຊີ -->
<dialog class="ap-modal" id="acEditInfo"<?php echo $apOpen('acEditInfo'); ?> aria-labelledby="acEditInfoT">
    <div class="ap-modal-head"><h3 id="acEditInfoT"><?php echo te('ແກ້ໄຂຂໍ້ມູນບັນຊີ'); ?></h3><?php echo $apX; ?></div>
    <div class="ap-modal-body">
        <form method="post" novalidate enctype="multipart/form-data">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="profile">
            <input type="hidden" name="section" value="info">
            <?php if (!empty($errors['form']) && $openModal === 'acEditInfo'): ?><div class="alert alert-error"><?php echo e($errors['form']); ?></div><?php endif; ?>
            <?php /* ຮູບໂປຣໄຟລ໌: ກົດຮູບ ຫຼື ໄອຄອນກ້ອງ = ເລືອກຮູບໃໝ່ → ເບິ່ງຕົວຢ່າງທັນທີ (ບັນທຶກພ້ອມຟອມ) */ ?>
            <div class="ap-av-pick">
                <label class="ap-av-img" title="<?php echo te('ປ່ຽນຮູບໂປຣໄຟລ໌'); ?>">
                    <img src="<?php echo e(memberAvatarUrl($member)); ?>" alt="" data-ap-av-preview referrerpolicy="no-referrer">
                    <span class="ap-av-cam" aria-hidden="true"><svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8h3l2-3h8l2 3h3v12H3z"/><circle cx="12" cy="13" r="3.5"/></svg></span>
                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" hidden data-ap-av-input>
                </label>
                <div><b><?php echo te('ຮູບໂປຣໄຟລ໌'); ?></b><small><?php echo te('ກົດຮູບເພື່ອປ່ຽນ — JPG, PNG, WEBP ບໍ່ເກີນ 5 MB'); ?></small><?php echo $field('avatar'); ?></div>
            </div>
            <label class="mf-field"><span><?php echo te('ຊື່ ແລະ ນາມສະກຸນ *'); ?></span><input class="input" name="name" value="<?php echo e($form['name']); ?>" required maxlength="150"><?php echo $field('name'); ?></label>
            <div class="mf-field">
                <span><?php echo te('ວັນເດືອນປີເກີດ (ປີ ຄ.ສ.)'); ?></span>
                <?php echo memberBirthSelects((string)($form['birth_date'] ?? '')); ?>
                <?php echo $field('birth_date'); ?>
            </div>
            <button class="btn btn-primary btn-block" type="submit"><?php echo te('ບັນທຶກ'); ?></button>
        </form>
    </div>
</dialog>

<!-- modal: ແກ້ໄຂຂໍ້ມູນຕິດຕໍ່ + ທີ່ຢູ່ປັດຈຸບັນ (data-order-form → assets/js/main.js: ເລືອກແຂວງ → ລາຍຊື່ເມືອງ) -->
<dialog class="ap-modal" id="acEditContact"<?php echo $apOpen('acEditContact'); ?> aria-labelledby="acEditContactT">
    <div class="ap-modal-head"><h3 id="acEditContactT"><?php echo te('ແກ້ໄຂຂໍ້ມູນຕິດຕໍ່'); ?></h3><?php echo $apX; ?></div>
    <div class="ap-modal-body">
        <form method="post" novalidate data-order-form>
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="profile">
            <input type="hidden" name="section" value="contact">
            <?php if (!empty($errors['form']) && $openModal === 'acEditContact'): ?><div class="alert alert-error"><?php echo e($errors['form']); ?></div><?php endif; ?>
            <h4 class="ap-form-h"><?php echo te('ຊ່ອງທາງຕິດຕໍ່'); ?></h4>
            <div class="mf-grid-2">
                <label class="mf-field"><span><?php echo te('ເບີໂທມືຖື * (ເບີຈິງ)'); ?></span><input class="input" name="tel" type="tel" inputmode="tel" value="<?php echo e(laoPhoneFormat($form['tel'])); ?>" required maxlength="20" placeholder="020 5xxx xxxx"><?php echo $field('tel'); ?></label>
                <label class="mf-field"><span><?php echo te('ເບີ WhatsApp'); ?></span><input class="input" name="whatsapp" type="tel" value="<?php echo e(laoPhoneFormat($form['whatsapp'])); ?>" maxlength="20"><?php echo $field('whatsapp'); ?></label>
            </div>
            <label class="mf-field"><span><?php echo te('ອີເມວ'); ?></span><input class="input" name="email" type="email" value="<?php echo e($form['email']); ?>" maxlength="150"><?php echo $field('email'); ?></label>
            <?php if ($emailOtpSent): /* ປ່ຽນອີເມວ: ລະຫັດທີ່ສົ່ງໄປອີເມວໃໝ່ */ ?>
                <label class="mf-field ap-otp"><span><?php echo te('ລະຫັດຢືນຢັນ 6 ຕົວ (ຈາກອີເມວ) *'); ?></span><input class="input" name="email_otp" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="______"><?php echo $field('email_otp'); ?></label>
            <?php endif; ?>
            <h4 class="ap-form-h"><?php echo te('ທີ່ຢູ່ປັດຈຸບັນ'); ?></h4>
            <div class="mf-address" data-prov-map="<?php echo e(json_encode($apProv, JSON_UNESCAPED_UNICODE)); ?>">
                <div class="mf-grid-3">
                    <label class="mf-field"><span><?php echo te('ແຂວງ / ນະຄອນຫຼວງ'); ?></span>
                        <select class="input" name="province" data-province-select>
                            <option value=""><?php echo te('-- ເລືອກແຂວງ --'); ?></option>
                            <?php foreach ($apProv as $prov => $_d): ?>
                                <option value="<?php echo e($prov); ?>"<?php echo ($form['province'] ?? '') === $prov ? ' selected' : ''; ?>><?php echo e($prov); ?></option>
                            <?php endforeach; ?>
                        </select><?php echo $field('province'); ?></label>
                    <label class="mf-field"><span><?php echo te('ເມືອງ'); ?></span>
                        <select class="input" name="district" data-district-select data-placeholder="<?php echo te('-- ເລືອກເມືອງ --'); ?>" data-empty-placeholder="<?php echo te('-- ເລືອກແຂວງກ່ອນ --'); ?>">
                            <option value=""><?php echo $apDists ? te('-- ເລືອກເມືອງ --') : te('-- ເລືອກແຂວງກ່ອນ --'); ?></option>
                            <?php foreach ($apDists as $dist): ?>
                                <option value="<?php echo e($dist); ?>"<?php echo ($form['district'] ?? '') === $dist ? ' selected' : ''; ?>><?php echo e($dist); ?></option>
                            <?php endforeach; ?>
                        </select><?php echo $field('district'); ?></label>
                    <label class="mf-field"><span><?php echo te('ບ້ານ'); ?></span><input class="input" name="village" value="<?php echo e((string)($form['village'] ?? '')); ?>" maxlength="150" placeholder="<?php echo te('ປ້ອນຊື່ບ້ານ...'); ?>"><?php echo $field('village'); ?></label>
                </div>
                <label class="mf-field"><span><?php echo te('ລາຍລະອຽດທີ່ຢູ່ (ຮ່ອມ, ເຮືອນເລກທີ, ຈຸດສັງເກດ)'); ?></span><textarea class="input" name="address" rows="2" maxlength="255" placeholder="<?php echo te('ເຊັ່ນ: ຮ່ອມ 5, ໃກ້ຕະຫຼາດ...'); ?>"><?php echo e((string)($form['address'] ?? '')); ?></textarea></label>
            </div>
            <p class="ap-hint"><?php echo te('ປ່ຽນອີເມວ ຕ້ອງຢືນຢັນດ້ວຍລະຫັດທີ່ສົ່ງໄປອີເມວໃໝ່'); ?></p>
            <button class="btn btn-primary btn-block" type="submit"><?php echo te('ບັນທຶກ'); ?></button>
        </form>
    </div>
</dialog>

<!-- modal: ລະຫັດຜ່ານ (ແບບຮູບຕົວຢ່າງ: ຫົວຂໍ້ກາງ + × ຂວາ, ຊ່ອງມີປຸ່ມຕາ, ປຸ່ມບັນທຶກເຕັມຄວາມກວ້າງ) -->
<dialog class="ap-modal ap-modal-sm" id="acPw"<?php echo $apOpen('acPw'); ?> aria-labelledby="acPwT">
    <div class="ap-modal-head is-plain"><h3 id="acPwT"><?php echo te('ລະຫັດຜ່ານ'); ?></h3><?php echo $apX; ?></div>
    <div class="ap-modal-body">
        <?php if (!empty($pwErrors['form'])): ?><div class="alert alert-error"><?php echo e($pwErrors['form']); ?></div><?php endif; ?>
        <form method="post" novalidate>
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="password">
            <?php if ($member['has_password']): ?>
                <label class="mf-field"><span><?php echo te('ລະຫັດຜ່ານເກົ່າ'); ?></span><?php echo $apPass('old_password', t('ປ້ອນລະຫັດຜ່ານເກົ່າ'), 'current-password'); ?><?php echo $pwField('old_password'); ?></label>
                <p class="ap-forgot">
                    <?php if ($member['email']): ?>
                        <button type="button" class="ap-link" data-ap-open="acForgot"><?php echo te('ລືມລະຫັດຜ່ານ?'); ?></button>
                    <?php else: ?>
                        <small><?php echo te('ລືມລະຫັດຜ່ານ? ເພີ່ມອີເມວໃນ "ຂໍ້ມູນຕິດຕໍ່" ກ່ອນ ຫຼື ຕິດຕໍ່ທີມງານທາງ WhatsApp'); ?></small>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
            <label class="mf-field"><span><?php echo te('ລະຫັດຜ່ານໃໝ່'); ?></span><?php echo $apPass('new_password', t('ປ້ອນລະຫັດຜ່ານໃໝ່ (ຢ່າງໜ້ອຍ 6 ຕົວ)'), 'new-password'); ?><?php echo $pwField('new_password'); ?></label>
            <label class="mf-field"><span><?php echo te('ຢືນຢັນລະຫັດຜ່ານໃໝ່'); ?></span><?php echo $apPass('new_password2', t('ຢືນຢັນລະຫັດຜ່ານໃໝ່'), 'new-password'); ?><?php echo $pwField('new_password2'); ?></label>
            <button class="btn btn-primary btn-block" type="submit"><?php echo te('ບັນທຶກ'); ?></button>
        </form>
    </div>
</dialog>

<!-- modal: ລືມລະຫັດຜ່ານ — ປ້ອນອີເມວທີ່ສະໝັກບັນຊີນີ້ (ຜິດ = ແຈ້ງທັນທີ) → ສົ່ງລະຫັດຜ່ານໃໝ່ໄປອີເມວນັ້ນ (account.php action=pw_forgot) -->
<dialog class="ap-modal ap-modal-sm" id="acForgot"<?php echo $apOpen('acForgot'); ?> aria-labelledby="acForgotT">
    <div class="ap-modal-head is-plain"><h3 id="acForgotT"><?php echo te('ລືມລະຫັດຜ່ານ'); ?></h3><?php echo $apX; ?></div>
    <div class="ap-modal-body">
        <?php if (!empty($fgErrors['form'])): ?><div class="alert alert-error"><?php echo e($fgErrors['form']); ?></div><?php endif; ?>
        <p class="ap-fg-intro"><?php echo te('ປ້ອນອີເມວທີ່ໃຊ້ສະໝັກບັນຊີນີ້ — ຖ້າຖືກຕ້ອງ ລະບົບຈະສົ່ງລະຫັດຜ່ານໃໝ່ໄປອີເມວນັ້ນ ແລ້ວໃຊ້ເຂົ້າສູ່ລະບົບໄດ້ເລີຍ'); ?></p>
        <form method="post" novalidate data-ap-forgot>
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="pw_forgot">
            <label class="mf-field"><span><?php echo te('ອີເມວ'); ?></span>
                <input class="input" name="email" type="email" inputmode="email" autocomplete="email" maxlength="150" placeholder="name@gmail.com" value="<?php echo e((string)($_POST['email'] ?? '')); ?>">
                <small class="field-error" data-fg-err><?php echo e($fgErrors['email'] ?? ''); ?></small></label>
            <button class="btn btn-primary btn-block" type="submit"><?php echo te('ສົ່ງລະຫັດຜ່ານໃໝ່'); ?></button>
        </form>
        <p class="ap-fg-back"><button type="button" class="ap-link" data-ap-open="acPw">← <?php echo te('ກັບໄປປ່ຽນລະຫັດຜ່ານ'); ?></button></p>
    </div>
</dialog>
