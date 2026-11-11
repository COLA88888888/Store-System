<?php
/**
 * ກວດວ່າອີເມວເປັນອີເມວແທ້ (ໃຊ້ຕອນລົງທະບຽນ / ແກ້ໄຂຂໍ້ມູນສະມາຊິກ — validateMemberProfile() ໃນ models/members.php).
 *   1. ຮູບແບບຖືກຕ້ອງ (filter_var)
 *   2. ບໍ່ແມ່ນໂດເມນອີເມວຊົ່ວຄາວ / ອີເມວທົດລອງ (mailinator, 10minutemail, example.com, test.com …)
 *   3. ພິມໂດເມນຜິດທີ່ພົບເລື້ອຍ (gmial.com, gmail.co …) → ບອກໃຫ້ແກ້ເປັນ gmail.com
 *   4. ໂດເມນມີເຊີເວີຮັບອີເມວແທ້ (DNS MX ຫຼື A) — ຖ້າເຄື່ອງເຊີເວີບໍ່ມີອິນເຕີເນັດ (ກວດ gmail.com ກໍບໍ່ຜ່ານ) → ຂ້າມຂໍ້ນີ້ ບໍ່ບລັອກທຸກຄົນ
 * ໝາຍເຫດ: ກວດໄດ້ວ່າໂດເມນຮັບອີເມວໄດ້ແທ້ ແຕ່ບໍ່ຮູ້ວ່າກ່ອງອີເມວ (ຊື່ກ່ອນ @) ມີແທ້ບໍ່ — ຢາກໄດ້ 100% ຕ້ອງສົ່ງລະຫັດຢືນຢັນທາງອີເມວ.
 */

/** @return string|null ຂໍ້ຄວາມຜິດພາດ, null = ຜ່ານ */
function emailRealError(string $email): ?string
{
    $email = mb_strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
        return t('ອີເມວບໍ່ຖືກຕ້ອງ');
    }
    [$local, $domain] = explode('@', $email, 2);

    $typos = [
        'gmial.com' => 'gmail.com', 'gmai.com' => 'gmail.com', 'gmal.com' => 'gmail.com', 'gamil.com' => 'gmail.com',
        'gmail.co' => 'gmail.com', 'gmail.con' => 'gmail.com', 'gmail.cm' => 'gmail.com', 'gmail.om' => 'gmail.com',
        'gmaill.com' => 'gmail.com', 'gnail.com' => 'gmail.com', 'gmail.coom' => 'gmail.com',
        'hotmial.com' => 'hotmail.com', 'hotmail.co' => 'hotmail.com', 'hotmal.com' => 'hotmail.com',
        'yaho.com' => 'yahoo.com', 'yahoo.co' => 'yahoo.com', 'yahooo.com' => 'yahoo.com',
        'outlok.com' => 'outlook.com', 'outlook.co' => 'outlook.com', 'iclod.com' => 'icloud.com',
    ];
    if (isset($typos[$domain])) {
        return t('ອີເມວອາດພິມຜິດ — ແມ່ນ %s ບໍ?', $local . '@' . $typos[$domain]);
    }

    $fake = [
        'example.com', 'example.org', 'example.net', 'test.com', 'test.test', 'email.com', 'abc.com', 'xyz.com', 'domain.com',
        'mailinator.com', 'guerrillamail.com', 'guerrillamail.net', 'sharklasers.com', 'grr.la', '10minutemail.com', '10minutemail.net',
        'tempmail.com', 'temp-mail.org', 'temp-mail.io', 'tempmail.net', 'tempmailo.com', 'tempr.email', 'throwawaymail.com',
        'yopmail.com', 'yopmail.net', 'getnada.com', 'nada.email', 'trashmail.com', 'dispostable.com', 'maildrop.cc',
        'mailnesia.com', 'mintemail.com', 'fakeinbox.com', 'fakemail.net', 'emailondeck.com', 'mohmal.com', 'moakt.com',
        'mail.tm', 'mail.gw', 'spamgourmet.com', 'getairmail.com', 'burnermail.io', 'inboxkitten.com', 'mytemp.email',
        'tmpmail.org', 'tmpmail.net', 'emailfake.com', 'crazymailing.com', 'mailpoof.com', 'linshiyouxiang.net', '1secmail.com',
    ];
    if (in_array($domain, $fake, true) || preg_match('/(^|\.)(temp|trash|fake|disposable|throwaway)[a-z0-9-]*mail/', $domain)) {
        return t('ບໍ່ຮັບອີເມວຊົ່ວຄາວ ຫຼື ອີເມວທົດລອງ — ກະລຸນາໃຊ້ອີເມວແທ້ຂອງທ່ານ');
    }

    if (!emailDomainReceivesMail($domain)) {
        return t('ອີເມວນີ້ບໍ່ມີຢູ່ແທ້ (ໂດເມນ @%s ຮັບອີເມວບໍ່ໄດ້) — ກະລຸນາກວດອີກຄັ້ງ', $domain);
    }
    return null;
}

/** ໂດເມນມີ MX / A record ບໍ (ຜົນຈື່ໄວ້ໃນ request). DNS ໃຊ້ບໍ່ໄດ້ທັງໝົດ → true (ບໍ່ບລັອກ) */
function emailDomainReceivesMail(string $domain): bool
{
    static $cache = [];
    if (isset($cache[$domain])) {
        return $cache[$domain];
    }
    $popular = ['gmail.com', 'googlemail.com', 'hotmail.com', 'outlook.com', 'live.com', 'yahoo.com', 'icloud.com', 'me.com', 'qq.com', '163.com', 'protonmail.com', 'proton.me'];
    if (in_array($domain, $popular, true) || !function_exists('checkdnsrr')) {
        return $cache[$domain] = true;
    }
    $ok = @checkdnsrr($domain . '.', 'MX') || @checkdnsrr($domain . '.', 'A');
    if (!$ok && !@checkdnsrr('gmail.com.', 'MX')) {
        $ok = true;   // ເຊີເວີບໍ່ມີ DNS/ອິນເຕີເນັດ — ບໍ່ຕັດສິນ
    }
    return $cache[$domain] = $ok;
}
