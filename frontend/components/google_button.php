<?php
/**
 * ປຸ່ມ "ດຳເນີນການຕໍ່ດ້ວຍ Google" (Google Identity Services) — ໃຊ້ໃນ popup ສະມາຊິກ (components/member_modal.php), pages/member_login.php.
 * ບໍ່ມີ Client ID ໃນ config/google.php → ບໍ່ສະແດງຫຍັງເລີຍ. ກົດແລ້ວ assets/js/member.js ສົ່ງ credential → api/member.php (action=google).
 * $googleBtnText (ທາງເລືອກ): 'signup_with' | 'signin_with' | 'continue_with' (ຄ່າເລີ່ມຕົ້ນ)
 */
$gClientId = googleClientId();
if ($gClientId === '') {
    return;
}
$gLocale = ['lo' => 'lo', 'th' => 'th', 'en' => 'en', 'zh' => 'zh-CN', 'vi' => 'vi'][currentLang()] ?? 'lo';
// ຂໍ້ຄວາມປຸ່ມ: ແປດ້ວຍລະບົບພາສາຂອງເວັບ (t()) — ປຸ່ມຂອງ Google ເອງ ສະແດງພາສາລາວບໍ່ໝັ້ນຄົງ (ບາງເທື່ອວ່າງ / ເປັນພາສາອື່ນ)
$gFace = ['signup_with' => t('ລົງທະບຽນດ້ວຍ Google'), 'signin_with' => t('ເຂົ້າສູ່ລະບົບດ້ວຍ Google')][$googleBtnText ?? ''] ?? t('ດຳເນີນການຕໍ່ດ້ວຍ Google');
?>
<div class="gsi-wrap">
    <div class="gsi-or"><span><?php echo te('ຫຼື'); ?></span></div>
    <?php /* ໜ້າປຸ່ມ (.gsi-face) ເປັນຂອງເຮົາ — ປຸ່ມແທ້ຂອງ Google (.gsi-btn) ວາງທັບເທິງແບບໂປ່ງໃສ ຮັບການກົດ (ການເຂົ້າສູ່ລະບົບຍັງເປັນຂອງ Google ທັງໝົດ) */ ?>
    <div class="gsi-btn-wrap">
    <span class="gsi-face" aria-hidden="true">
        <svg viewBox="0 0 48 48" width="20" height="20"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
        <span><?php echo e($gFace); ?></span>
    </span>
    <div class="gsi-btn" data-google-login
         data-client-id="<?php echo e($gClientId); ?>"
         data-endpoint="<?php echo e(url('api/member.php')); ?>"
         data-csrf="<?php echo e(csrfToken()); ?>"
         data-text="<?php echo e($googleBtnText ?? 'continue_with'); ?>"
         data-locale="<?php echo e($gLocale); ?>"
         data-error="<?php echo te('ເຂົ້າສູ່ລະບົບດ້ວຍ Google ບໍ່ສຳເລັດ ກະລຸນາລອງໃໝ່'); ?>"></div>
    </div>
    <small class="gsi-msg" data-google-msg hidden></small>
</div>
<?php if (empty($GLOBALS['gsiScriptLoaded'])): $GLOBALS['gsiScriptLoaded'] = true; ?>
<script src="https://accounts.google.com/gsi/client" async defer></script>
<?php endif; ?>
