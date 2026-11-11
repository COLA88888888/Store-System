<?php
/**
 * API ສະມາຊິກ (JSON) — ຟອມ popup components/member_modal.php ↔ assets/js/member.js
 *   POST action=register ຟອມລົງທະບຽນລະອຽດ (validateMemberProfile) → ລົງທະບຽນ + login → {success, member_no}
 *     → ຕ້ອງຢືນຢັນອີເມວກ່ອນ: ບໍ່ມີ otp → ສົ່ງລະຫັດ 6 ຕົວໄປອີເມວ {success:false, need_otp:true}; ມີ otp ຖືກ → ລົງທະບຽນ (models/member_otp.php)
 *   POST action=forgot   login (ອີເມວເທົ່ານັ້ນ) → ສົ່ງລະຫັດໄປອີເມວບັນຊີ {need_otp}; + otp, password, password2 → ຕັ້ງລະຫັດໃໝ່ + login
 *   POST action=login    login (ເບີໂທ ຫຼື ອີເມວ), password          → {success} ຫຼື {success:false, message}
 *   POST action=google   credential (ID token ຈາກ Google)          → {success, redirect} (models/member_google.php)
 * ຜິດ → {success:false, errors:{field: ຂໍ້ຄວາມ}} ຫຼື message. ຕ້ອງມີ csrf (ຊ່ອງ csrf ໃນຟອມ). ກົດການ: models/members.php
 */
require dirname(__DIR__) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(['success' => false, 'message' => 'POST only'], 405);
}
if (!checkCsrf()) {
    jsonOut(['success' => false, 'message' => t('ໝົດເວລາຂອງຟອມ ກະລຸນາລອງສົ່ງອີກຄັ້ງ')], 419);
}
if (!$pdo) {
    jsonOut(['success' => false, 'message' => t('ລະບົບບໍ່ພ້ອມໃຊ້ງານ ກະລຸນາລອງໃໝ່ພາຍຫຼັງ')], 503);
}

$action = (string)($_POST['action'] ?? '');
try {
    if ($action === 'register') {
        if ($blocked = memberRegisterBlocked($pdo, $_POST)) {   // ກັນ bot: ຊ່ອງດັກ + ຈຳກັດ 5 ບັນຊີ/ຊົ່ວໂມງ/IP
            jsonOut(['success' => false, 'message' => $blocked], 429);
        }
        [$form, $errors] = validateMemberProfile($_POST, 'register');
        if ($errors) {
            jsonOut(['success' => false, 'errors' => $errors], 422);
        }
        $otp = trim((string)($_POST['otp'] ?? ''));
        $needOtp = memberOtpAvailable();   // ບໍ່ມີເຊີເວີອີເມວ → ລົງທະບຽນໄດ້ເລີຍ (models/member_otp.php)
        if ($needOtp && $otp === '') {   // ຂັ້ນທີ 1: ສົ່ງລະຫັດຢືນຢັນໄປອີເມວ
            if ($err = memberOtpSend($pdo, 'reg', $form['email'], $form['name'])) {
                jsonOut(['success' => false, 'need_otp' => true, 'message' => $err], 429);
            }
            jsonOut(['success' => false, 'need_otp' => true, 'note' => true,
                     'message' => t('ສົ່ງລະຫັດ 6 ຕົວໄປທີ່ %s ແລ້ວ — ກະລຸນາເປີດອີເມວ ແລ້ວປ້ອນລະຫັດເພື່ອຢືນຢັນ', $form['email'])]);
        }
        if ($needOtp && ($err = memberOtpCheck('reg', $form['email'], $otp))) {   // ຂັ້ນທີ 2: ກວດລະຫັດ
            jsonOut(['success' => false, 'need_otp' => true, 'errors' => ['otp' => $err]], 422);
        }
        $res = memberRegister($pdo, $form);
        if (!$res['success']) {
            jsonOut(['success' => false, 'errors' => [$res['field'] => $res['message']]], 422);
        }
        memberStartSession($pdo, $res['id']);
        rateLimitAdd($pdo, 'reg', 3600);
        memberMailWelcome($pdo, (int)$res['id']);   // ອີເມວຍິນດີຕ້ອນຮັບ (models/member_mail.php)
        memberNotify($pdo, (int)$res['id'], 'welcome', [], 'pages/equipment_list.php');   // ກະດິ່ງແຈ້ງເຕືອນ
        flash(t('ລົງທະບຽນສຳເລັດ! ທ່ານແມ່ນສະມາຊິກເລກທີ %s — ເບິ່ງລາຄາ ແລະ ສັ່ງຊື້ໄດ້ເລີຍ', memberCode($res['member_no'])));
        jsonOut(['success' => true, 'member_no' => $res['member_no']]);
    }
    if ($action === 'forgot') {
        if (!memberOtpAvailable()) {
            jsonOut(['success' => false, 'message' => t('ລະບົບສົ່ງອີເມວຍັງບໍ່ພ້ອມ — ກະລຸນາຕິດຕໍ່ທີມງານທາງ WhatsApp 020 5594 9007 ເພື່ອຕັ້ງລະຫັດໃໝ່')], 503);
        }
        // ອີເມວເທົ່ານັ້ນ (ຍັງບໍ່ມີ OTP ທາງ SMS) — ບັນຊີເກົ່າທີ່ບໍ່ມີອີເມວ → ຕິດຕໍ່ທີມງານ
        $fEmail = mb_strtolower(trim((string)($_POST['login'] ?? '')));
        if (!filter_var($fEmail, FILTER_VALIDATE_EMAIL)) {
            jsonOut(['success' => false, 'errors' => ['login' => t('ກະລຸນາປ້ອນອີເມວທີ່ໃຊ້ລົງທະບຽນ')]], 422);
        }
        $m = memberFindByLogin($pdo, $fEmail);
        if (!$m || $m['status'] !== 'active') {
            jsonOut(['success' => false, 'errors' => ['login' => t('ບໍ່ພົບບັນຊີຂອງອີເມວນີ້ — ຖ້າລົງທະບຽນດ້ວຍເບີໂທຢ່າງດຽວ ກະລຸນາຕິດຕໍ່ທີມງານທາງ WhatsApp')]], 422);
        }
        $otp = trim((string)($_POST['otp'] ?? ''));
        if ($otp === '') {
            if ($err = memberOtpSend($pdo, 'forgot', $m['email'], $m['name'])) {
                jsonOut(['success' => false, 'need_otp' => true, 'message' => $err], 429);
            }
            jsonOut(['success' => false, 'need_otp' => true, 'note' => true,
                     'message' => t('ສົ່ງລະຫັດ 6 ຕົວໄປທີ່ %s ແລ້ວ — ປ້ອນລະຫັດ ແລະ ລະຫັດຜ່ານໃໝ່', memberMaskEmail($m['email']))]);
        }
        $p1 = (string)($_POST['password'] ?? '');
        $p2 = (string)($_POST['password2'] ?? '');
        if (strlen($p1) < 6) {
            jsonOut(['success' => false, 'need_otp' => true, 'errors' => ['password' => t('ລະຫັດຜ່ານຕ້ອງມີຢ່າງໜ້ອຍ 6 ຕົວ')]], 422);
        }
        if ($p1 !== $p2) {
            jsonOut(['success' => false, 'need_otp' => true, 'errors' => ['password2' => t('ລະຫັດຜ່ານທັງສອງບໍ່ກົງກັນ')]], 422);
        }
        if ($err = memberOtpCheck('forgot', $m['email'], $otp)) {
            jsonOut(['success' => false, 'need_otp' => true, 'errors' => ['otp' => $err]], 422);
        }
        $pdo->prepare("UPDATE w_web_members SET password_hash = ? WHERE id = ?")->execute([password_hash($p1, PASSWORD_DEFAULT), (int)$m['id']]);
        memberStartSession($pdo, (int)$m['id']);
        flash(t('ຕັ້ງລະຫັດຜ່ານໃໝ່ສຳເລັດ — ເຂົ້າສູ່ລະບົບແລ້ວ'));
        jsonOut(['success' => true]);
    }
    if ($action === 'logout') {
        memberLogout($pdo);
        jsonOut(['success' => true]);
    }
    if ($action === 'login') {
        $err = memberLogin($pdo, (string)($_POST['login'] ?? $_POST['tel'] ?? ''), (string)($_POST['password'] ?? ''));
        if ($err !== null) {
            jsonOut(['success' => false, 'message' => $err], 422);
        }
        $m = currentMember($pdo);
        flash(t('ຍິນດີຕ້ອນຮັບກັບມາ%s 🙏', $m ? ' ' . $m['name'] : ''));
        jsonOut(['success' => true]);
    }
    if ($action === 'google') {
        $payload = googleVerifyIdToken((string)($_POST['credential'] ?? ''));
        if (!$payload) {
            jsonOut(['success' => false, 'message' => t('ຢືນຢັນບັນຊີ Google ບໍ່ສຳເລັດ ກະລຸນາລອງໃໝ່')], 422);
        }
        $res = memberGoogleLogin($pdo, $payload);
        if (!$res['success']) {
            jsonOut(['success' => false, 'message' => $res['message']], 422);
        }
        $m = currentMember($pdo, true);
        if ($res['is_new']) {
            memberMailWelcome($pdo, (int)$res['id']);   // ອີເມວຍິນດີຕ້ອນຮັບ ໄປອີເມວຂອງບັນຊີ Google
            memberNotify($pdo, (int)$res['id'], 'welcome', [], 'pages/equipment_list.php');
        }
        if ($res['is_new'] || memberNeedsPhone($m)) {
            // ບັນຊີ Google ຍັງບໍ່ມີເບີໂທ / ທີ່ຢູ່ → ໄປໜ້າບັນຊີເພື່ອຕື່ມຂໍ້ມູນໃຫ້ຄົບກ່ອນສັ່ງຊື້
            flash($res['is_new'] ? t('ລົງທະບຽນດ້ວຍ Google ສຳເລັດ! ສະມາຊິກເລກທີ %s — ກະລຸນາເພີ່ມເບີໂທ ແລະ ທີ່ຢູ່ຈັດສົ່ງ', memberCode($res['member_no'])) : t('ກະລຸນາເພີ່ມເບີໂທ ແລະ ທີ່ຢູ່ຈັດສົ່ງ ກ່ອນສັ່ງຊື້'), 'info');
            jsonOut(['success' => true, 'redirect' => url('pages/account.php?edit=1')]);
        }
        flash(t('ຍິນດີຕ້ອນຮັບກັບມາ%s 🙏', $m ? ' ' . $m['name'] : ''));
        jsonOut(['success' => true, 'redirect' => '']);
    }
    jsonOut(['success' => false, 'message' => 'unknown action'], 400);
} catch (Throwable $e) {
    error_log('frontend api/member: ' . $e->getMessage());
    jsonOut(['success' => false, 'message' => t('ເກີດຂໍ້ຜິດພາດ ກະລຸນາລອງໃໝ່')], 500);
}
