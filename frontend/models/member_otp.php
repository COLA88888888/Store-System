<?php
/**
 * ລະຫັດຢືນຢັນ 6 ຕົວທາງອີເມວ (OTP) — ໃຊ້ໃນ api/member.php:
 *   'reg'    ລົງທະບຽນ: ຕ້ອງປ້ອນລະຫັດທີ່ສົ່ງໄປອີເມວກ່ອນ ຈຶ່ງສ້າງບັນຊີ (ກັນອີເມວປອມ / ອີເມວຄົນອື່ນ)
 *   'forgot' ລືມລະຫັດຜ່ານ: ລະຫັດສົ່ງໄປອີເມວຂອງບັນຊີ → ຕັ້ງລະຫັດຜ່ານໃໝ່ໄດ້ເອງ
 * ເກັບໃນ session (ບໍ່ມີຕາຕະລາງໃໝ່): hash ຂອງລະຫັດ + ອີເມວ + ໝົດອາຍຸ 10 ນາທີ + ລອງຜິດໄດ້ 5 ເທື່ອ.
 * ຂໍລະຫັດໃໝ່ໄດ້ທຸກ 60 ວິ, ສູງສຸດ 10 ຄັ້ງ/ຊົ່ວໂມງ/IP (w_web_rate_limits). ສົ່ງຜ່ານແຖວອີເມວ (includes/mailer.php).
 */

const MEMBER_OTP_TTL = 600;
const MEMBER_OTP_COOLDOWN = 60;
const MEMBER_OTP_MAX_TRIES = 5;

/**
 * ສົ່ງ OTP ໄດ້ບໍ: ເຊີເວີ SMTP (config/mail.php) ຕິດຕໍ່ໄດ້. ບໍ່ໄດ້ (ຍັງບໍ່ຕັ້ງ / Mailpit ບໍ່ເປີດ) → ລົງທະບຽນໂດຍບໍ່ຕ້ອງ OTP
 * ເພື່ອບໍ່ໃຫ້ລູກຄ້າລົງທະບຽນບໍ່ໄດ້ເລີຍ (ຍັງມີ emailRealError() ກັນອີເມວປອມ). ຈື່ຜົນໄວ້ 5 ນາທີໃນ session.
 */
function memberOtpAvailable(): bool
{
    $cfg = mailConfig();
    if (empty($cfg['enabled'])) {
        return false;
    }
    $c = $_SESSION['otp_smtp_ok'] ?? null;
    if ($c && $c['at'] > time() - 300) {
        return $c['ok'];
    }
    $fp = @fsockopen((string)$cfg['host'], (int)$cfg['port'], $errno, $errstr, 2.0);
    $ok = (bool)$fp;
    if ($fp) {
        fclose($fp);
    } else {
        error_log('member OTP: SMTP ' . $cfg['host'] . ':' . $cfg['port'] . ' unreachable — registration without email code');
    }
    $_SESSION['otp_smtp_ok'] = ['ok' => $ok, 'at' => time()];
    return $ok;
}

function memberOtpHash(string $code): string
{
    return hash_hmac('sha256', $code, 'wlao-otp|' . session_id());
}

/** ສົ່ງລະຫັດໃໝ່ໄປ $email. null = ສົ່ງແລ້ວ, string = ຂໍ້ຄວາມຜິດພາດ */
function memberOtpSend(PDO $pdo, string $purpose, string $email, string $name = ''): ?string
{
    $email = mb_strtolower(trim($email));
    $cur = $_SESSION['member_otp'][$purpose] ?? null;
    if ($cur && $cur['email'] === $email && time() - $cur['sent_at'] < MEMBER_OTP_COOLDOWN) {
        return t('ສົ່ງລະຫັດໄປແລ້ວ — ຂໍລະຫັດໃໝ່ໄດ້ອີກ %d ວິນາທີ', MEMBER_OTP_COOLDOWN - (time() - $cur['sent_at']));
    }
    if (rateLimitCount($pdo, 'otp') >= 10) {
        return t('ຂໍລະຫັດຫຼາຍເກີນໄປ ກະລຸນາລອງໃໝ່ໃນ 1 ຊົ່ວໂມງ');
    }
    $code = (string)random_int(100000, 999999);
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $what = $purpose === 'reg' ? 'ຢືນຢັນອີເມວເພື່ອລົງທະບຽນສະມາຊິກ WlaoDev' : 'ຕັ້ງລະຫັດຜ່ານໃໝ່ຂອງບັນຊີສະມາຊິກ WlaoDev';
    $body = '<p>ສະບາຍດີ' . ($name !== '' ? ' ' . $h($name) : '') . ' 🙏</p>'
        . '<p>ລະຫັດ' . $h($what) . ' ຂອງທ່ານແມ່ນ:</p>'
        . '<p style="font-size:32px;font-weight:800;letter-spacing:8px;color:#1d4ed8;margin:16px 0">' . $code . '</p>'
        . '<p style="color:#64748b">ລະຫັດໃຊ້ໄດ້ 10 ນາທີ. ຖ້າທ່ານບໍ່ໄດ້ຂໍລະຫັດນີ້ ບໍ່ຕ້ອງເຮັດຫຍັງ — ຢ່າບອກລະຫັດນີ້ໃຫ້ຜູ້ອື່ນ.</p>';
    $subject = 'ລະຫັດຢືນຢັນ WlaoDev: ' . $code;
    if (!mailQueue($pdo, $email, $subject, mailTemplate('ລະຫັດຢືນຢັນ', $body), 'otp_' . $purpose)) {
        return t('ສົ່ງອີເມວບໍ່ສຳເລັດ ກະລຸນາລອງໃໝ່ ຫຼື ຕິດຕໍ່ພວກເຮົາ');
    }
    register_shutdown_function('mailKick');
    rateLimitAdd($pdo, 'otp', 3600);
    $_SESSION['member_otp'][$purpose] = [
        'hash' => memberOtpHash($code), 'email' => $email, 'sent_at' => time(),
        'expires' => time() + MEMBER_OTP_TTL, 'tries' => 0,
    ];
    return null;
}

/** ກວດລະຫັດ. null = ຖືກ (ລະຫັດຖືກລຶບ ໃຊ້ຊ້ຳບໍ່ໄດ້), string = ຂໍ້ຄວາມຜິດພາດ */
function memberOtpCheck(string $purpose, string $email, string $code): ?string
{
    $cur = $_SESSION['member_otp'][$purpose] ?? null;
    $code = preg_replace('/\D/', '', $code);
    if (!$cur || $cur['email'] !== mb_strtolower(trim($email))) {
        return t('ກະລຸນາກົດ "ສົ່ງລະຫັດໃໝ່" ເພື່ອຮັບລະຫັດທາງອີເມວ');
    }
    if (time() > $cur['expires']) {
        unset($_SESSION['member_otp'][$purpose]);
        return t('ລະຫັດໝົດອາຍຸແລ້ວ ກະລຸນາກົດ "ສົ່ງລະຫັດໃໝ່"');
    }
    if (!hash_equals($cur['hash'], memberOtpHash($code))) {
        $_SESSION['member_otp'][$purpose]['tries']++;
        if ($_SESSION['member_otp'][$purpose]['tries'] >= MEMBER_OTP_MAX_TRIES) {
            unset($_SESSION['member_otp'][$purpose]);
            return t('ປ້ອນລະຫັດຜິດຫຼາຍເທື່ອ ກະລຸນາກົດ "ສົ່ງລະຫັດໃໝ່"');
        }
        return t('ລະຫັດບໍ່ຖືກຕ້ອງ');
    }
    unset($_SESSION['member_otp'][$purpose]);
    return null;
}

/** ສະແດງອີເມວແບບບັງບາງສ່ວນ: somchai@gmail.com → so****@gmail.com */
function memberMaskEmail(string $email): string
{
    [$l, $d] = array_pad(explode('@', $email, 2), 2, '');
    return mb_substr($l, 0, 2) . str_repeat('*', max(2, mb_strlen($l) - 2)) . '@' . $d;
}

/** ຫາບັນຊີຈາກ ເບີໂທ ຫຼື ອີເມວ (ສຳລັບລືມລະຫັດຜ່ານ) */
function memberFindByLogin(PDO $pdo, string $login): ?array
{
    $login = trim($login);
    if ($login === '') {
        return null;
    }
    if (str_contains($login, '@')) {
        $st = $pdo->prepare("SELECT id, name, email, status FROM w_web_members WHERE email = ? LIMIT 1");
        $st->execute([mb_strtolower($login)]);
    } else {
        $st = $pdo->prepare("SELECT id, name, email, status FROM w_web_members WHERE tel IN (?, ?) LIMIT 1");
        $st->execute([laoPhoneNormalize($login) ?? '-', memberNormalizeTel($login)]);
    }
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
}
