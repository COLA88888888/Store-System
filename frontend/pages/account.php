<?php
/**
 * ບັນຊີສະມາຊິກ (css/account.css): ແຖບຟ້າ → ຊ້າຍ = ກາດໂປຣໄຟລ໌ + ເມນູ, ຂວາ = ກາດເນື້ອຫາ. ?tab=info|contact|orders|settings
 *   info     ຂໍ້ມູນບັນຊີ (ຊື່, ເລກສະມາຊິກ, ວັນເດືອນປີເກີດ) — modal #acEditInfo (ຮູບໂປຣໄຟລ໌ + ຊື່ + ວັນເກີດ 3 ກ່ອງເລືອກ)
 *   contact  ຂໍ້ມູນຕິດຕໍ່ (ເບີໂທ, WhatsApp, ອີເມວ) + ທີ່ຢູ່ປັດຈຸບັນ (ບ້ານ, ເມືອງ, ແຂວງ) — modal #acEditContact
 *            (ປ່ຽນອີເມວ = ຢືນຢັນລະຫັດ 6 ຕົວທີ່ສົ່ງໄປອີເມວໃໝ່). ?edit=1 = ເປີດ modal ນີ້ (ເຊັ່ນ ບັນຊີ Google ທີ່ຍັງບໍ່ມີເບີໂທ)
 *   orders   ປະຫວັດການສັ່ງຊື້ 6 ເດືອນ (→ pages/my_orders.php)
 *   settings ແຈ້ງເຕືອນອີເມວ, ລະຫັດຜ່ານ (modal #acPw: ລະຫັດເກົ່າ + ລະຫັດໃໝ່; ລືມລະຫັດເກົ່າ → ສົ່ງລະຫັດຜ່ານໃໝ່ໄປອີເມວ), ອອກຈາກລະບົບ
 * POST action: profile (section=info|contact → memberUpdateProfile), notify, password, pw_forgot (ອີເມວຕ້ອງກົງກັບບັນຊີ), logout.
 * modal ທັງໝົດ: components/account_modals.php. ບໍ່ໄດ້ login → pages/member_login.php.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
$pageTitle = t('ບັນຊີຂອງຂ້ອຍ');
$currentPage = 'member';
$pageNoIndex = true;
$action = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['action'] ?? '') : '';
if ($action === 'logout' && checkCsrf()) {
    memberLogout();
    flash(t('ອອກຈາກລະບົບແລ້ວ'));
    redirect('');
}
$member = currentMember($pdo);
if (!$member) {
    redirect('pages/member_login.php?next=' . rawurlencode('pages/account.php'));
}

$errors = [];
$pwErrors = [];
$fgErrors = [];        // ລືມລະຫັດຜ່ານ (modal #acForgot)
$form = $member;
$section = '';           // modal ທີ່ຕ້ອງເປີດຄືນ (info | contact) ເມື່ອຟອມຜິດພາດ
$emailOtpSent = false;   // ປ່ຽນອີເມວ → ສົ່ງລະຫັດໄປອີເມວໃໝ່ກ່ອນ → ສະແດງຊ່ອງລະຫັດໃນ modal ຂໍ້ມູນຕິດຕໍ່
if ($action === 'profile') {
    // ແຕ່ລະ modal ສົ່ງສະເພາະຊ່ອງຂອງຕົນ — ຊ່ອງທີ່ບໍ່ໄດ້ສົ່ງມາ ເກັບຄ່າເດີມໄວ້ (ຮ້ານ / ຂົນສົ່ງ ຈື່ຕອນສັ່ງຊື້: memberRememberAddress)
    $section = ($_POST['section'] ?? '') === 'contact' ? 'contact' : 'info';
    $editable = $section === 'contact' ? ['tel', 'whatsapp', 'email', 'province', 'district', 'village', 'address'] : ['name', 'bd_d', 'bd_m', 'bd_y'];
    $in = array_map(fn($v) => (string)($v ?? ''), array_intersect_key($member, array_flip(MEMBER_PROFILE_FIELDS)));
    foreach ($editable as $k) {
        $in[$k] = (string)($_POST[$k] ?? '');
    }
    [$form, $errors] = validateMemberProfile($in, 'profile');
    if (!checkCsrf()) {
        $errors['form'] = t('ໝົດເວລາຂອງຟອມ ກະລຸນາລອງສົ່ງອີກຄັ້ງ');
    } elseif ($section === 'info' && ($avErr = memberSaveAvatar($pdo, $member, $_FILES['avatar'] ?? null))) {   // ຮູບໂປຣໄຟລ໌ (ບໍ່ບັງຄັບ)
        $errors['avatar'] = $avErr;
    }
    // ປ່ຽນອີເມວ = ຕ້ອງຢືນຢັນວ່າເປັນເຈົ້າຂອງອີເມວໃໝ່ (ລະຫັດ 6 ຕົວ) — ກັນຄົນອື່ນປ່ຽນອີເມວ ແລ້ວໃຊ້ "ລືມລະຫັດຜ່ານ" ຍຶດບັນຊີ
    $emailChanged = !$errors && $form['email'] !== '' && $form['email'] !== mb_strtolower((string)$member['email']);
    if ($emailChanged && memberOtpAvailable()) {
        $code = trim((string)($_POST['email_otp'] ?? ''));
        if ($code === '') {
            $err = memberOtpSend($pdo, 'email', $form['email'], (string)$member['name']);
            $errors['email_otp'] = $err ?? t('ສົ່ງລະຫັດ 6 ຕົວໄປທີ່ %s ແລ້ວ — ປ້ອນລະຫັດເພື່ອຢືນຢັນອີເມວໃໝ່', $form['email']);
            $emailOtpSent = true;
        } elseif ($err = memberOtpCheck('email', $form['email'], $code)) {
            $errors['email_otp'] = $err;
            $emailOtpSent = true;
        }
    }
    if (!$errors) {
        $errors = memberUpdateProfile($pdo, (int)$member['id'], $form);
        if (!$errors) {
            flash(t('ບັນທຶກຂໍ້ມູນແລ້ວ'));
            redirect('pages/account.php?tab=' . $section);
        }
    }
} elseif ($action === 'notify' && checkCsrf()) {   // ເປີດ/ປິດ ອີເມວແຈ້ງເຕືອນ (ລົດລາຄາ, ສະຖານະອໍເດີ — models/member_mail.php, backend/config/helpers/web_mail.php)
    $pdo->prepare("UPDATE w_web_members SET email_notify = ? WHERE id = ?")->execute([!empty($_POST['email_notify']) ? 1 : 0, (int)$member['id']]);
    flash(!empty($_POST['email_notify']) ? t('ເປີດການແຈ້ງເຕືອນທາງອີເມວແລ້ວ') : t('ປິດການແຈ້ງເຕືອນທາງອີເມວແລ້ວ'));
    redirect('pages/account.php?tab=settings');
} elseif ($action === 'password') {
    // ປ່ຽນລະຫັດຜ່ານ: ລະຫັດເກົ່າ (ບັນຊີທີ່ມີລະຫັດແລ້ວ) + ລະຫັດໃໝ່ 2 ຄັ້ງ. ເດົາລະຫັດເກົ່າຜິດໄດ້ 10 ຄັ້ງ/ຊົ່ວໂມງ
    $old = (string)($_POST['old_password'] ?? '');
    $p1 = (string)($_POST['new_password'] ?? '');
    $p2 = (string)($_POST['new_password2'] ?? '');
    $st = $pdo->prepare("SELECT password_hash FROM w_web_members WHERE id = ?");
    $st->execute([(int)$member['id']]);
    $hash = (string)$st->fetchColumn();
    if (!checkCsrf()) {
        $pwErrors['form'] = t('ໝົດເວລາຂອງຟອມ ກະລຸນາລອງສົ່ງອີກຄັ້ງ');
    } elseif ($hash !== '' && rateLimitCount($pdo, 'pwold') >= 10) {
        $pwErrors['form'] = t('ປ້ອນລະຫັດຜ່ານເກົ່າຜິດຫຼາຍເກີນໄປ ກະລຸນາລອງໃໝ່ໃນ 1 ຊົ່ວໂມງ ຫຼື ກົດ "ລືມລະຫັດຜ່ານ"');
    } elseif ($hash !== '' && !password_verify($old, $hash)) {
        rateLimitAdd($pdo, 'pwold', 3600);
        $pwErrors['old_password'] = $old === '' ? t('ກະລຸນາປ້ອນລະຫັດຜ່ານເກົ່າ') : t('ລະຫັດຜ່ານເກົ່າບໍ່ຖືກຕ້ອງ');
    } elseif (strlen($p1) < 6) {
        $pwErrors['new_password'] = t('ລະຫັດຜ່ານຕ້ອງມີຢ່າງໜ້ອຍ 6 ຕົວ');
    } elseif ($p1 !== $p2) {
        $pwErrors['new_password2'] = t('ລະຫັດຜ່ານທັງສອງບໍ່ກົງກັນ');
    } else {
        $pdo->prepare("UPDATE w_web_members SET password_hash = ? WHERE id = ?")->execute([password_hash($p1, PASSWORD_DEFAULT), (int)$member['id']]);
        try {
            $pdo->prepare("DELETE FROM w_web_member_tokens WHERE member_id = ?")->execute([(int)$member['id']]);   // ເຄື່ອງອື່ນທີ່ຈື່ໄວ້ ຕ້ອງ login ໃໝ່
        } catch (Throwable $e) {
        }
        flash(t('ປ່ຽນລະຫັດຜ່ານແລ້ວ'));
        redirect('pages/account.php?tab=settings');
    }
} elseif ($action === 'pw_forgot') {
    // ລືມລະຫັດຜ່ານເກົ່າ (modal #acForgot): ປ້ອນອີເມວ — ຕ້ອງກົງກັບອີເມວທີ່ສະໝັກບັນຊີນີ້ເທົ່ານັ້ນ (ຜິດ = ແຈ້ງທັນທີ, ເດົາຜິດໄດ້ 10 ຄັ້ງ/ຊົ່ວໂມງ)
    // ຖືກ → ສ້າງລະຫັດຜ່ານໃໝ່ 8 ຕົວ ສົ່ງໄປອີເມວນັ້ນ → ໃຊ້ເຂົ້າສູ່ລະບົບໄດ້ເລີຍ / ປ້ອນເປັນລະຫັດເກົ່າເພື່ອປ່ຽນ. ajax=1 → ຕອບ JSON
    $fgEmail = mb_strtolower(trim((string)($_POST['email'] ?? '')));
    if (!checkCsrf()) {
        $fgErrors['form'] = t('ໝົດເວລາຂອງຟອມ ກະລຸນາລອງສົ່ງອີກຄັ້ງ');
    } elseif (rateLimitCount($pdo, 'pwfgbad') >= 10) {
        $fgErrors['email'] = t('ປ້ອນອີເມວຜິດຫຼາຍເກີນໄປ ກະລຸນາລອງໃໝ່ໃນ 1 ຊົ່ວໂມງ');
    } elseif ($fgEmail === '') {
        $fgErrors['email'] = t('ກະລຸນາປ້ອນອີເມວທີ່ໃຊ້ສະໝັກບັນຊີ');
    } elseif (!empty($member['email']) && $fgEmail !== mb_strtolower(trim((string)$member['email']))) {
        rateLimitAdd($pdo, 'pwfgbad', 3600);
        $fgErrors['email'] = t('ອີເມວບໍ່ຖືກຕ້ອງ — ກະລຸນາປ້ອນອີເມວທີ່ໃຊ້ສະໝັກບັນຊີນີ້');
    } elseif (empty($member['email'])) {
        $fgErrors['form'] = t('ກະລຸນາເພີ່ມອີເມວໃນຂໍ້ມູນຕິດຕໍ່ກ່ອນ — ລະຫັດຜ່ານໃໝ່ຈະສົ່ງໄປອີເມວ');
    } elseif (!memberOtpAvailable()) {
        $fgErrors['form'] = t('ລະບົບສົ່ງອີເມວຍັງບໍ່ພ້ອມ — ກະລຸນາຕິດຕໍ່ທີມງານທາງ WhatsApp 020 5594 9007 ເພື່ອຕັ້ງລະຫັດໃໝ່');
    } elseif (rateLimitCount($pdo, 'pwmail') >= 3) {
        $fgErrors['form'] = t('ຂໍລະຫັດຜ່ານໃໝ່ຫຼາຍເກີນໄປ ກະລຸນາກວດອີເມວ ຫຼື ລອງໃໝ່ໃນ 1 ຊົ່ວໂມງ');
    } else {
        $abc = 'abcdefghjkmnpqrstuvwxyz23456789';
        $temp = '';
        for ($i = 0; $i < 8; $i++) {
            $temp .= $abc[random_int(0, strlen($abc) - 1)];
        }
        $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
        $body = '<p>ສະບາຍດີ ' . $h($member['name']) . ' 🙏</p>'
            . '<p>ລະຫັດຜ່ານໃໝ່ຂອງບັນຊີສະມາຊິກ ' . $h(memberCode($member['member_no'])) . ' ແມ່ນ:</p>'
            . '<p style="font-size:28px;font-weight:800;letter-spacing:4px;color:#1d4ed8;margin:16px 0;font-family:monospace">' . $temp . '</p>'
            . '<p>ໃຊ້ລະຫັດນີ້ເຂົ້າສູ່ລະບົບດ້ວຍເບີໂທ ຫຼື ອີເມວໄດ້ເລີຍ — ແນະນຳໃຫ້ປ່ຽນເປັນລະຫັດຂອງທ່ານເອງໃນ "ຈັດການບັນຊີ → ລະຫັດຜ່ານ".</p>'
            . '<p style="color:#64748b">ຖ້າທ່ານບໍ່ໄດ້ຂໍລະຫັດນີ້ ກະລຸນາຕິດຕໍ່ທີມງານທັນທີ — ຢ່າບອກລະຫັດນີ້ໃຫ້ຜູ້ອື່ນ.</p>';
        // ສົ່ງເຂົ້າແຖວອີເມວກ່ອນ ສຳເລັດແລ້ວຈຶ່ງປ່ຽນລະຫັດ (ສົ່ງບໍ່ໄດ້ = ລະຫັດເກົ່າຍັງໃຊ້ໄດ້)
        if (!mailQueue($pdo, (string)$member['email'], 'ລະຫັດຜ່ານໃໝ່ຂອງບັນຊີ WlaoDev', mailTemplate('ລະຫັດຜ່ານໃໝ່', $body), 'pw_reset', (int)$member['id'])) {
            $fgErrors['form'] = t('ສົ່ງອີເມວບໍ່ສຳເລັດ ກະລຸນາລອງໃໝ່ ຫຼື ຕິດຕໍ່ພວກເຮົາ');
        } else {
            register_shutdown_function('mailKick');
            rateLimitAdd($pdo, 'pwmail', 3600);
            $pdo->prepare("UPDATE w_web_members SET password_hash = ? WHERE id = ?")->execute([password_hash($temp, PASSWORD_DEFAULT), (int)$member['id']]);
            try {
                $pdo->prepare("DELETE FROM w_web_member_tokens WHERE member_id = ?")->execute([(int)$member['id']]);
            } catch (Throwable $e) {
            }
            flash(t('ສົ່ງລະຫັດຜ່ານໃໝ່ໄປທີ່ %s ແລ້ວ — ໃຊ້ລະຫັດນັ້ນເຂົ້າສູ່ລະບົບໄດ້ເລີຍ ຫຼື ປ້ອນເປັນລະຫັດເກົ່າເພື່ອຕັ້ງລະຫັດຂອງທ່ານເອງ', memberMaskEmail((string)$member['email'])));
            if (!empty($_POST['ajax'])) {   // ແຈ້ງດ້ວຍ SweetAlert ໃນໜ້າ (ເອົາ flash ອອກ ບໍ່ໃຫ້ຂຶ້ນຊ້ຳຕອນໂຫຼດໜ້າຕໍ່ໄປ)
                $fl = flash();
                jsonOut(['success' => true, 'message' => $fl['message'] ?? '', 'redirect' => url('pages/account.php?tab=settings&pw=1')]);
            }
            redirect('pages/account.php?tab=settings&pw=1');
        }
    }
    if ($fgErrors && !empty($_POST['ajax'])) {
        jsonOut(['success' => false, 'errors' => $fgErrors]);
    }
}

$needsPhone = memberNeedsPhone($member);
// ປະຫວັດການສັ່ງຊື້ 6 ເດືອນລ່າສຸດ: ກຳລັງດຳເນີນການ | ສຳເລັດ + ຍົກເລີກ
$histFrom = strtotime('-6 months');
$histGroups = ['active' => [], 'done' => []];
foreach (memberOrders($pdo, $member, '', 200) as $o) {
    if (strtotime($o['created_at']) >= $histFrom) {
        $histGroups[in_array($o['status'], ['completed', 'cancelled'], true) ? 'done' : 'active'][] = $o;
    }
}
if (!$section && ($needsPhone || !empty($_GET['edit']))) {
    $section = 'contact';
}
$openModal = $errors ? ($section === 'contact' ? 'acEditContact' : 'acEditInfo')
    : ($fgErrors ? 'acForgot' : ($pwErrors || !empty($_GET['pw']) ? 'acPw' : ($section === 'contact' ? 'acEditContact' : '')));
$field = fn(string $name) => isset($errors[$name]) ? '<small class="field-error">' . e($errors[$name]) . '</small>' : '';
$pwField = fn(string $name) => isset($pwErrors[$name]) ? '<small class="field-error">' . e($pwErrors[$name]) . '</small>' : '';
$tab = $errors ? $section : ($pwErrors || $fgErrors ? 'settings' : (string)($_GET['tab'] ?? ($section ?: 'info')));
if (!in_array($tab, ['info', 'contact', 'orders', 'settings'], true)) {
    $tab = 'info';
}
$pageStyles = ['css/account.css'];
$pageScripts = ['js/vendor/sweetalert2.all.min.js', 'js/account.js'];   // SweetAlert2 (ສຳເນົາຈາກ backend/plugins) + JS ຂອງໜ້າ
require dirname(__DIR__) . '/layouts/header.php';

$none = '<span class="ap-none">' . te('ຍັງບໍ່ມີ') . '</span>';
$apInfo = [
    t('ຊື່ ແລະ ນາມສະກຸນ') => (string)$member['name'],
    t('ເລກສະມາຊິກ')      => memberCode($member['member_no']),
    t('ວັນເດືອນປີເກີດ')   => !empty($member['birth_date']) ? date('d/m/Y', strtotime($member['birth_date'])) : '',
    t('ເປັນສະມາຊິກຕັ້ງແຕ່') => date('d/m/Y', strtotime($member['created_at'])),
];
$apContact = [
    t('ເບີໂທ')        => $member['tel'] ? laoPhoneFormat($member['tel']) : '',
    t('ເບີ WhatsApp') => $member['whatsapp'] ? laoPhoneFormat($member['whatsapp']) : ($member['tel'] ? t('ໃຊ້ເບີໂທດຽວກັນ') : ''),
    t('ອີເມວ')        => (string)$member['email'],
];
$apAddress = [
    t('ບ້ານ')                => (string)$member['village'],
    t('ເມືອງ')               => (string)$member['district'],
    t('ແຂວງ / ນະຄອນຫຼວງ')    => (string)$member['province'],
    t('ລາຍລະອຽດທີ່ຢູ່')       => (string)$member['address'],
];
// ຕາຕະລາງ 2 ຄູ່ຕໍ່ແຖວ (ຫົວຂໍ້ | ຂໍ້ມູນ | ຫົວຂໍ້ | ຂໍ້ມູນ); ມືຖື 1 ຄູ່ຕໍ່ແຖວ (CSS)
$infoTable = function (array $rows) use ($none): string {
    $h = '<table class="ap-info-table"><tbody>';
    foreach (array_chunk($rows, 2, true) as $pair) {
        $h .= '<tr>';
        foreach ($pair as $k => $v) {   // ແຖວທີ່ມີຄູ່ດຽວ (ເຊັ່ນ ອີເມວ) → ຂໍ້ມູນກວ້າງເຕັມແຖວ ບໍ່ຕັດຄຳ
            $h .= '<th>' . e($k) . '</th><td' . (count($pair) === 1 ? ' colspan="3"' : '') . '>' . ($v !== '' ? e($v) : $none) . '</td>';
        }
        $h .= '</tr>';
    }
    return $h . '</tbody></table>';
};
$editBtn = fn(string $id) => '<button type="button" class="as-btn" data-ap-open="' . $id . '">' . te('ແກ້ໄຂ') . '</button>';
?>
<section class="ap">
    <?php /* ແຖບຟ້າຫົວໜ້າ (ໜັງສື 3D Wlaodev.com + ຮູບປະດັບ SVG ຂວາມື) */ ?>
    <div class="ap-banner" aria-hidden="true">
        <span class="ap-banner-title-3d">Wlaodev.com</span>
        <img class="ap-banner-logo" src="<?php echo e(asset('img/wd_mark.png')); ?>" alt="" width="800" height="369">
    </div>

    <div class="container ap-layout">
        <aside class="ap-side">
            <?php /* ກາດໂປຣໄຟລ໌ (ທັບແຖບຟ້າ): ຮູບ (ກົດ = ເບິ່ງເຕັມ) + ຊື່ + ເລກສະມາຊິກ + ວັນທີເປັນສະມາຊິກ; ປຸ່ມສໍ = modal ແກ້ໄຂຂໍ້ມູນບັນຊີ */ ?>
            <div class="ap-profile">
                <button type="button" class="ap-prof-edit" data-ap-open="acEditInfo" title="<?php echo te('ແກ້ໄຂໂປຣໄຟລ໌'); ?>" aria-label="<?php echo te('ແກ້ໄຂໂປຣໄຟລ໌'); ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H5a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h13a2 2 0 0 0 2-2v-6"/><path d="M18.4 2.6a2 2 0 0 1 2.9 2.9L12 14.8l-4 1 1-4z"/></svg>
                </button>
                <button type="button" class="ap-avatar" data-ap-view="<?php echo e(memberAvatarFullUrl($member)); ?>" title="<?php echo te('ກົດເພື່ອເບິ່ງຮູບເຕັມ'); ?>">
                    <img src="<?php echo e(memberAvatarUrl($member)); ?>" alt="" referrerpolicy="no-referrer">
                </button>
                <h1><?php echo e($member['name']); ?></h1>
                <p class="ap-code"><?php echo e(memberCode($member['member_no'])); ?></p>
                <span class="ap-perk"><?php echo te('ເຫັນລາຄາ + ສ່ວນຫຼຸດສະມາຊິກ 5%'); ?></span>
                <dl class="ap-prof-meta">
                    <div><dt><?php echo te('ເປັນສະມາຊິກເມື່ອ'); ?></dt><dd><?php echo e(date('d/m/Y', strtotime($member['created_at']))); ?></dd></div>
                    <div><dt><?php echo te('ເຂົ້າສູ່ລະບົບດ້ວຍ'); ?></dt><dd><?php echo $member['google_id'] ? 'Google' : te('ເບີໂທ / ອີເມວ'); ?></dd></div>
                </dl>
            </div>
        </aside>

        <div class="ap-main">
            <?php /* ຫົວຂໍ້ + ແທັບແນວນອນ (ຂີດກ້ອງ) ເທິງກາດເນື້ອຫາ ([data-ap-tab], ?tab= ສຳລັບລິ້ງກົງ) — ມືຖື = ເລື່ອນທາງຂວາງ */ ?>
            <h2 class="ap-heading"><?php echo te('ບັນຊີຂອງທ່ານ'); ?></h2>
            <nav class="ap-menu" role="tablist">
                <?php foreach ([
                'info'     => [t('ຂໍ້ມູນບັນຊີ'), '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>'],
                'contact'  => [t('ຂໍ້ມູນຕິດຕໍ່'), '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="10" cy="10" r="2.5"/><path d="M6 17a4 4 0 0 1 8 0M16 8h2M16 12h2"/>'],
                'orders'   => [t('ຄຳສັ່ງຊື້'), '<path d="M6 2h12v20l-3-2-3 2-3-2-3 2z"/><path d="M9 7h6M9 11h6M9 15h4"/>'],
                'settings' => [t('ຈັດການບັນຊີ'), '<rect x="4.5" y="10.5" width="15" height="10" rx="2"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/>'],
                ] as $k => [$lbl, $d]): ?>
                <a role="tab" class="<?php echo $tab === $k ? 'is-active' : ''; ?>" href="<?php echo url('pages/account.php?tab=' . $k); ?>" data-ap-tab="<?php echo $k; ?>"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?php echo $d; ?></svg><span><?php echo e($lbl); ?></span></a>
                <?php endforeach; ?>
            </nav>

            <?php if ($needsPhone): ?>
                <div class="alert alert-error"><?php echo te('ກະລຸນາເພີ່ມເບີໂທຈິງ — ຈຳເປັນສຳລັບການສັ່ງຊື້ ແລະ ໃຫ້ທີມງານໂທຢືນຢັນ'); ?></div>
            <?php endif; ?>

            <!-- ແທັບ 1: ຂໍ້ມູນບັນຊີ -->
            <div class="ap-pane ap-panel" data-ap-pane="info"<?php echo $tab === 'info' ? '' : ' hidden'; ?>>
                <header class="ap-panel-head">
                    <div><h2><?php echo te('ຂໍ້ມູນບັນຊີ'); ?></h2><p><?php echo te('ຊື່, ເລກສະມາຊິກ ແລະ ວັນເດືອນປີເກີດຂອງທ່ານ'); ?></p></div>
                    <?php echo $editBtn('acEditInfo'); ?>
                </header>
                <div class="ap-panel-body"><?php echo $infoTable($apInfo); ?></div>
            </div>

            <!-- ແທັບ 2: ຂໍ້ມູນຕິດຕໍ່ — ຊ່ອງທາງຕິດຕໍ່ + ທີ່ຢູ່ປັດຈຸບັນ -->
            <div class="ap-pane ap-panel" data-ap-pane="contact"<?php echo $tab === 'contact' ? '' : ' hidden'; ?>>
                <header class="ap-panel-head">
                    <div><h2><?php echo te('ຂໍ້ມູນຕິດຕໍ່'); ?></h2><p><?php echo te('ເບີໂທ, ອີເມວ ແລະ ທີ່ຢູ່ປັດຈຸບັນຂອງທ່ານ'); ?></p></div>
                    <?php echo $editBtn('acEditContact'); ?>
                </header>
                <div class="ap-panel-body">
                    <h3 class="ap-sub-h"><?php echo te('ຊ່ອງທາງຕິດຕໍ່'); ?></h3>
                    <?php echo $infoTable($apContact); ?>
                    <h3 class="ap-sub-h"><?php echo te('ທີ່ຢູ່ປັດຈຸບັນ'); ?></h3>
                    <?php echo $infoTable($apAddress); ?>
                </div>
            </div>

            <!-- ແທັບ 3: ປະຫວັດການສັ່ງຊື້ (6 ເດືອນລ່າສຸດ) — ແທັບຍ່ອຍ ກຳລັງດຳເນີນການ / ສຳເລັດ ແລະ ຍົກເລີກ ([data-ap-sub]) -->
            <div class="ap-pane ap-panel" data-ap-pane="orders"<?php echo $tab === 'orders' ? '' : ' hidden'; ?>>
                <header class="ap-panel-head">
                    <div><h2><?php echo te('ປະຫວັດການສັ່ງຊື້'); ?></h2><p><?php echo te('ໝາຍເຫດ: ສະແດງສະເພາະຂໍ້ມູນຍ້ອນຫຼັງ 6 ເດືອນລ່າສຸດ'); ?></p></div>
                    <a class="as-btn" href="<?php echo url('pages/my_orders.php?s=all'); ?>"><?php echo te('ຈັດການທັງໝົດ'); ?></a>
                </header>
                <div class="ap-panel-body">
                    <div class="ap-hist">
                        <div class="ap-hist-tabs" role="tablist">
                            <button type="button" class="is-active" data-ap-sub="active"><?php echo te('ກຳລັງດຳເນີນການ'); ?> (<?php echo count($histGroups['active']); ?>)</button>
                            <button type="button" data-ap-sub="done"><?php echo te('ສຳເລັດ / ຍົກເລີກ'); ?> (<?php echo count($histGroups['done']); ?>)</button>
                        </div>
                        <?php foreach ($histGroups as $gk => $gOrders): ?>
                            <div class="ap-hist-body" data-ap-sub-pane="<?php echo $gk; ?>"<?php echo $gk === 'active' ? '' : ' hidden'; ?>>
                                <table class="ap-table">
                                    <thead><tr><th><?php echo te('ວັນທີສັ່ງຊື້'); ?></th><th><?php echo te('ລາຍລະອຽດ'); ?></th><th><?php echo te('ສະຖານະ'); ?></th><th class="is-num"><?php echo te('ຍອດເງິນ'); ?></th></tr></thead>
                                    <tbody>
                                        <?php if (!$gOrders): ?>
                                            <tr><td colspan="4" class="ap-table-empty"><?php echo te('ບໍ່ພົບຂໍ້ມູນ'); ?></td></tr>
                                        <?php endif; ?>
                                        <?php foreach ($gOrders as $o): $hm = orderStatusMeta((string)$o['status']); $hi = $o['items'] ?? []; $ht = orderDisplayTotal($o); ?>
                                            <tr>
                                                <td data-label="<?php echo te('ວັນທີສັ່ງຊື້'); ?>"><?php echo e(date('d/m/Y H:i', strtotime($o['created_at']))); ?></td>
                                                <td data-label="<?php echo te('ລາຍລະອຽດ'); ?>"><a class="ap-hist-no" href="<?php echo url('pages/my_order.php?no=' . rawurlencode((string)$o['order_no'])); ?>"><?php echo e($o['order_no']); ?></a>
                                                    <small><?php echo e($hi[0]['product_name'] ?? ''); ?><?php echo count($hi) > 1 ? ' ' . te('+ ອີກ %d ລາຍການ', count($hi) - 1) : ''; ?></small></td>
                                                <td data-label="<?php echo te('ສະຖານະ'); ?>"><span class="mo-badge is-<?php echo e($hm['tone']); ?>"><?php echo e($hm['label']); ?></span></td>
                                                <td data-label="<?php echo te('ຍອດເງິນ'); ?>" class="is-num"><b><?php echo $ht > 0 ? e(money($ht)) : '—'; ?></b></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ແທັບ 4: ຈັດການບັນຊີ — ແຖວກອບ (ໄອຄອນ + ຫົວຂໍ້/ຄຳອະທິບາຍ + ປຸ່ມ/ສະວິດ ຂວາ) -->
            <div class="ap-pane ap-panel" data-ap-pane="settings"<?php echo $tab === 'settings' ? '' : ' hidden'; ?>>
                <header class="ap-panel-head">
                    <div><h2><?php echo te('ຈັດການບັນຊີ'); ?></h2><p><?php echo te('ຕັ້ງຄ່າການແຈ້ງເຕືອນ ແລະ ຄວາມປອດໄພຂອງບັນຊີ'); ?></p></div>
                </header>
                <div class="ap-panel-body as-list">
                    <form method="post">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="notify">
                        <label class="as-item as-row<?php echo $member['email'] ? '' : ' is-off'; ?>">
                            <span class="as-ico is-blue"><svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true"><path d="M4 5h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2zm0 2.2v.3l8 5.3 8-5.3v-.3H4z"/></svg></span>
                            <span class="as-text">
                                <b><?php echo te('ຮັບແຈ້ງເຕືອນທາງອີເມວ'); ?></b>
                                <small><?php echo $member['email']
                                    ? te('ສົ່ງໄປ %s: ສິນຄ້າລົດລາຄາ, ອໍເດີມີການອັບເດດ ແລະ ບິນຮັບເຄື່ອງ', $member['email'])
                                    : te('ເພີ່ມອີເມວໃນ "ຂໍ້ມູນຕິດຕໍ່" ກ່ອນ ຈຶ່ງຮັບແຈ້ງເຕືອນໄດ້'); ?></small>
                            </span>
                            <input type="checkbox" class="as-switch" name="email_notify" value="1"<?php echo !empty($member['email_notify']) ? ' checked' : ''; ?><?php echo $member['email'] ? '' : ' disabled'; ?> onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()">
                        </label>
                    </form>
                    <div class="as-item as-row as-static">
                        <span class="as-ico is-blue"><svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true"><path d="M12 2a5 5 0 0 1 5 5v3h1a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h1V7a5 5 0 0 1 5-5zm0 11a2 2 0 0 0-1 3.7V18h2v-1.3A2 2 0 0 0 12 13zm0-9a3 3 0 0 0-3 3v3h6V7a3 3 0 0 0-3-3z"/></svg></span>
                        <span class="as-text">
                            <b><?php echo te('ລະຫັດຜ່ານ'); ?></b>
                            <small><?php echo $member['has_password'] ? te('ລະຫັດຜ່ານສຳລັບເຂົ້າສູ່ລະບົບດ້ວຍເບີໂທ ຫຼື ອີເມວ') : te('ຕັ້ງລະຫັດຜ່ານ ເພື່ອເຂົ້າສູ່ລະບົບດ້ວຍເບີໂທ ຫຼື ອີເມວໄດ້ນຳ'); ?></small>
                        </span>
                        <button type="button" class="as-btn" data-ap-open="acPw"><?php echo $member['has_password'] ? te('ປ່ຽນລະຫັດຜ່ານ') : te('ຕັ້ງລະຫັດຜ່ານ'); ?></button>
                    </div>
                    <form method="post" data-logout-confirm="<?php echo te('ທ່ານຕ້ອງການອອກຈາກລະບົບແທ້ບໍ?'); ?>" data-logout-text="<?php echo te('ທ່ານຈະຕ້ອງເຂົ້າສູ່ລະບົບອີກຄັ້ງ ເພື່ອເບິ່ງລາຄາ ແລະ ສັ່ງຊື້'); ?>" data-logout-yes="<?php echo te('ອອກຈາກລະບົບ'); ?>" data-logout-no="<?php echo te('ຍົກເລີກ'); ?>">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="logout">
                        <div class="as-item as-row as-logout">
                            <span class="as-ico is-red"><svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true"><path d="M5 3h8a2 2 0 0 1 2 2v3h-2V5H5v14h8v-3h2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zm11.3 4.3L21 12l-4.7 4.7-1.4-1.4 2.3-2.3H9v-2h8.2l-2.3-2.3z"/></svg></span>
                            <span class="as-text"><b><?php echo te('ອອກຈາກລະບົບ'); ?></b><small><?php echo te('ອອກຈາກບັນຊີ %s ໃນເຄື່ອງນີ້', memberCode($member['member_no'])); ?></small></span>
                            <button type="submit" class="as-btn is-red"><?php echo te('ອອກຈາກລະບົບ'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php require dirname(__DIR__) . '/components/account_modals.php'; ?>
</section>
<?php /* JS ຂອງໜ້າ: assets/js/account.js (modal, ແທັບ, ບັນທຶກແບບ real-time + SweetAlert) — ໂຫຼດຜ່ານ $pageScripts ໃນ layouts/footer.php */ ?>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
