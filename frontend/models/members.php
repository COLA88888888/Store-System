<?php
/**
 * ສະມາຊິກເວັບໄຊ — w_web_members (backend/config/migrations/schema_website.php v28, v38), ແຍກຈາກລູກຄ້າ POS (w_customers).
 *   - ລົງທະບຽນ (popup components/member_modal.php → api/member.php): ຂໍ້ມູນລະອຽດ — ຊື່, ເບີໂທຈິງ (020/030 ຂອງລາວ, ຫ້າມຊ້ຳ), WhatsApp, ອີເມວ (ຫ້າມຊ້ຳ),
 *     ຊື່ຮ້ານ/ປະເພດທຸລະກິດ, ທີ່ຢູ່ຈັດສົ່ງ (ແຂວງ/ເມືອງ/ບ້ານ) + ຂົນສົ່ງ/ສາຂາທີ່ສະດວກ, ລະຫັດຜ່ານ ≥ 6 ຕົວ
 *     → member_no = ເລກສະມາຊິກຕາມລຳດັບ 1, 2, 3… (ສະແດງເປັນ WL-0001, WL-0002… ດ້ວຍ memberCode()) ຫຼື ລົງທະບຽນດ້ວຍ Google (models/member_google.php)
 *   - ເຂົ້າລະບົບ (pages/member_login.php, popup): ເບີໂທ ຫຼື ອີເມວ + ລະຫັດຜ່ານ. ຜິດ 5 ເທື່ອ → ລໍຖ້າ 5 ນາທີ
 *   - ແກ້ໄຂຂໍ້ມູນ / ປ່ຽນລະຫັດຜ່ານ: pages/account.php (memberUpdateProfile, memberChangePassword)
 *   - ສະມາຊິກເຫັນລາຄາສິນຄ້າ ແລະ ສັ່ງຊື້ໄດ້ເລີຍ (models/member_orders.php); ລູກຄ້າທົ່ວໄປ = ຂໍໃບສະເໜີລາຄາ
 *   - session: $_SESSION['member_id'] (ແຍກຈາກ session ຂອງ backend). ອອກຈາກລະບົບ: pages/account.php (POST)
 * ຈຳນວນສະມາຊິກສະແດງໃນ footer (models/visitors.php getSiteStats). ພະນັກງານເບິ່ງລາຍຊື່: backend/pages/web_members/
 */

/** ຖັນທີ່ອ່ານ/ແກ້ໄຂໄດ້ໃນຟອມລົງທະບຽນ ແລະ ແກ້ໄຂຂໍ້ມູນ */
const MEMBER_PROFILE_FIELDS = ['name', 'tel', 'whatsapp', 'email', 'birth_date', 'shop_name', 'business_type', 'province', 'district', 'village', 'address', 'courier_code', 'courier_branch'];

/** ສະມາຊິກທີ່ login ຢູ່ (null = ລູກຄ້າທົ່ວໄປ). $reload = ອ່ານໃໝ່ຫຼັງແກ້ໄຂຂໍ້ມູນ */
function currentMember(?PDO $pdo = null, bool $reload = false): ?array
{
    static $cache = false;
    if ($cache !== false && !$reload) {
        return $cache;
    }
    $cache = null;
    if ($pdo === null) {
        $pdo = $GLOBALS['pdo'] ?? null;
    }
    $id = (int)($_SESSION['member_id'] ?? 0);

    // ກວດສອບຄວາມບໍ່ມີການເຄື່ອນໄຫວ (Inactivity timeout 15 ນາທີ):
    // ບໍ່ໃຊ້ງານເປີດຢູ່ແມ່ນອອກຈາກລະບົບ
    if ($id) {
        $lastActive = (int)($_SESSION['member_last_active'] ?? 0);
        $timeout = 900; // 15 ນາທີ (900 ວິນາທີ)
        if ($lastActive > 0 && (time() - $lastActive) > $timeout) {
            memberLogout($pdo);
            return null;
        }
        $_SESSION['member_last_active'] = time();
    }

    // ຕາມຄວາມຕ້ອງການ: "ເຂົ້າໃຫມ່ ແມ່ນຕ້ອງໄດ້ເຂົ້າສູ່ລະບົບໃຫມ່" — ບໍ່ໃຊ້ cookie remember ເກົ່າ
    if (!$id) {
        if (!empty($_COOKIE[MEMBER_REMEMBER_COOKIE])) {
            memberSetRememberCookie('', time() - 3600);
        }
        return null;
    }
    if ($id && $pdo) {
        try {
            $st = $pdo->prepare("SELECT id, member_no, " . implode(', ', MEMBER_PROFILE_FIELDS) . ", google_id, avatar_url, email_notify,
                                        (password_hash IS NOT NULL AND password_hash <> '') AS has_password, created_at
                                 FROM w_web_members WHERE id = ? AND status = 'active'");
            $st->execute([$id]);
            $cache = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
        }
        if (!$cache) {
            unset($_SESSION['member_id'], $_SESSION['member_last_active']);
        }
    }
    return $cache;
}

/**
 * ຄິດໄລ່ລາຄາຂາຍຕົວຈິງ + ລາຄາກ່ອນຫຼຸດ (ສ່ວນຫຼຸດພິເສດ 5% ສຳລັບສະມາຊິກ ໂດຍທີ່ລາຄາຫຼັງຫຼຸດ 5% = ລາຄາຂາຍຕົວຈິງ).
 * ຕົວຢ່າງ: ລາຄາຂາຍຕົວຈິງ $realPrice = 1,000,000 ₭ → ລາຄາກ່ອນຫຼຸດ (mark-up) = round(1000000 / 0.95) = 1,052,632 ₭ (ຫຼຸດ 5% ເຫຼືອ 1,000,000 ₭).
 * @return array{sell: float, old: float, is_member: bool, pct: int}
 */
function memberPriceInfo(float $realPrice, float $explicitOld = 0): array
{
    $isMember = currentMember() !== null;
    if ($realPrice <= 0) {
        return ['sell' => 0.0, 'old' => 0.0, 'is_member' => $isMember, 'pct' => 5];
    }
    // ຕັ້ງລາຄາກ່ອນຫຼຸດໃຫ້ສູງກວ່າລາຄາຂາຍຕົວຈິງ ເມື່ອຫຼຸດ 5% (× 0.95) ແລ້ວເທົ່າກັບລາຄາຂາຍຕົວຈິງພໍດີ
    $inflatedOld = round($realPrice / 0.95);
    $oldPrice = $explicitOld > $realPrice ? $explicitOld : $inflatedOld;
    return [
        'sell'      => $realPrice,
        'old'       => $oldPrice,
        'is_member' => $isMember,
        'pct'       => 5,
    ];
}

/** ລະຫັດສະມາຊິກສຳລັບສະແດງ: member_no 1 → "WL-0001" (ຄືກັບ backend/config/helpers/web_member.php) */
if (!function_exists('memberCode')) {
    function memberCode($no): string
    {
        return 'WL-' . str_pad((string)(int)$no, 4, '0', STR_PAD_LEFT);
    }
}

/** ເລກສະມາຊິກສຳລັບສະແດງ: "ສະມາຊິກເລກທີ WL-0001" */
function memberNoLabel(array $m): string
{
    return t('ສະມາຊິກເລກທີ %s', memberCode($m['member_no']));
}

function memberNormalizeTel(string $tel): string
{
    return preg_replace('/[^\d+]/', '', $tel);
}

/**
 * ເບີໂທມືຖືລາວແທ້ → ຮູບແບບດຽວ '020XXXXXXXX' (020 + 8 ຕົວ) ຫຼື '030XXXXXXX' (030 + 7 ຕົວ). ຮັບ +856 / 856 / 0 ນຳໜ້າ, ຍະຫວ່າງ, ຂີດ.
 * null = ບໍ່ແມ່ນເບີແທ້ (ຜິດຮູບແບບ, ຕົວເລກຊ້ຳກັນໝົດ ເຊັ່ນ 020 1111 1111, ຫຼື ລຽງກັນ 12345678)
 */
function laoPhoneNormalize(string $tel): ?string
{
    $d = preg_replace('/\D+/', '', $tel);
    if (str_starts_with($d, '856')) {
        $d = substr($d, 3);
    }
    $d = ltrim($d, '0');
    if (preg_match('/^20([2-9]\d{7})$/', $d, $m) || preg_match('/^30(\d{7})$/', $d, $m)) {
        $tail = $m[1];
        if (preg_match('/^(\d)\1+$/', substr($tail, 1)) || str_contains('0123456789', $tail) || str_contains('9876543210', $tail)) {
            return null;
        }
        return '0' . $d;
    }
    return null;
}

/** '02055551234' → '020 5555 1234' (ສະແດງ) */
function laoPhoneFormat(?string $tel): string
{
    $tel = (string)$tel;
    if (preg_match('/^(020)(\d{4})(\d{4})$/', $tel, $m) || preg_match('/^(030)(\d{3})(\d{4})$/', $tel, $m)) {
        return "$m[1] $m[2] $m[3]";
    }
    return $tel;
}

/** ປະເພດທຸລະກິດໃຫ້ເລືອກໃນຟອມລົງທະບຽນ (key ເກັບໃນ DB) */
function memberBusinessTypes(): array
{
    return [
        'restaurant' => t('ຮ້ານອາຫານ'),
        'cafe'       => t('ຮ້ານກາເຟ / ເຄື່ອງດື່ມ'),
        'minimart'   => t('ມິນິມາດ / ຮ້ານຂາຍຍ່ອຍ'),
        'fashion'    => t('ຮ້ານເຄື່ອງນຸ່ງ / ແຟຊັ່ນ'),
        'pharmacy'   => t('ຮ້ານຂາຍຢາ'),
        'service'    => t('ບໍລິການ / ຮ້ານເສີມສວຍ'),
        'wholesale'  => t('ຂາຍສົ່ງ / ຄັງສິນຄ້າ'),
        'personal'   => t('ໃຊ້ສ່ວນຕົວ'),
        'other'      => t('ອື່ນໆ'),
    ];
}

/**
 * ກວດຟອມລົງທະບຽນ ($mode = 'register') ຫຼື ແກ້ໄຂຂໍ້ມູນ ($mode = 'profile' — ບໍ່ມີລະຫັດຜ່ານ / ເງື່ອນໄຂ).
 * @return array [$form, $errors]
 */
/** ວັນເດືອນປີເກີດຈາກ 3 ກ່ອງເລືອກ (bd_d, bd_m, bd_y) → 'Y-m-d'; ວ່າງໝົດ = ''; ເລືອກບໍ່ຄົບ = 'x' (validateMemberProfile ແຈ້ງວ່າບໍ່ຖືກ) */
function memberBirthFromParts(array $in): string
{
    $d = trim((string)($in['bd_d'] ?? ''));
    $mo = trim((string)($in['bd_m'] ?? ''));
    $y = trim((string)($in['bd_y'] ?? ''));
    if ($d === '' && $mo === '' && $y === '') {
        return '';
    }
    if (!ctype_digit($d) || !ctype_digit($mo) || !ctype_digit($y)) {
        return 'x';
    }
    return sprintf('%04d-%02d-%02d', (int)$y, (int)$mo, (int)$d);
}

/** HTML 3 ກ່ອງເລືອກ ວັນ | ເດືອນ (ຊື່ເດືອນຕາມພາສາ) | ປີ ຄ.ສ. (ອາຍຸ 10–110 ປີ) — ໃຊ້ໃນ popup ລົງທະບຽນ ແລະ pages/account.php */
function memberBirthSelects(string $value, bool $required = false): string
{
    [$sy, $sm, $sd] = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $mm) ? [(int)$mm[1], (int)$mm[2], (int)$mm[3]] : [0, 0, 0];
    $months = ['ມັງກອນ', 'ກຸມພາ', 'ມີນາ', 'ເມສາ', 'ພຶດສະພາ', 'ມິຖຸນາ', 'ກໍລະກົດ', 'ສິງຫາ', 'ກັນຍາ', 'ຕຸລາ', 'ພະຈິກ', 'ທັນວາ'];
    $req = $required ? ' required' : '';
    $sel = function (string $name, string $ph, array $opts, int $cur) use ($req): string {
        $h = '<select class="input mm-noico" name="' . $name . '"' . $req . ' aria-label="' . e($ph) . '"><option value="">' . e($ph) . '</option>';
        foreach ($opts as $v => $lbl) {
            $h .= '<option value="' . $v . '"' . ($cur === $v ? ' selected' : '') . '>' . e((string)$lbl) . '</option>';
        }
        return $h . '</select>';
    };
    $days = array_combine(range(1, 31), range(1, 31));
    $mons = [];
    foreach ($months as $i => $mn) {
        $mons[$i + 1] = t($mn);
    }
    $yNow = (int)date('Y');
    $years = array_combine(range($yNow - 10, $yNow - 110), range($yNow - 10, $yNow - 110));
    return '<div class="bd-sel">' . $sel('bd_d', t('ວັນ'), $days, $sd) . $sel('bd_m', t('ເດືອນ'), $mons, $sm) . $sel('bd_y', t('ປີ'), $years, $sy) . '</div>';
}

function validateMemberProfile(array $in, string $mode = 'register'): array
{
    if (array_key_exists('bd_d', $in) || array_key_exists('bd_m', $in) || array_key_exists('bd_y', $in)) {
        $in['birth_date'] = memberBirthFromParts($in);   // ຟອມໃຊ້ 3 ກ່ອງເລືອກ
    }
    $f = [];
    foreach (MEMBER_PROFILE_FIELDS as $k) {
        $f[$k] = trim((string)($in[$k] ?? ''));
    }
    $errors = [];
    if ($f['name'] === '' || mb_strlen($f['name']) < 2 || mb_strlen($f['name']) > 150) {
        $errors['name'] = t('ກະລຸນາປ້ອນຊື່ ແລະ ນາມສະກຸນ');
    }
    // ວັນເດືອນປີເກີດ (Y-m-d ຈາກ 3 ກ່ອງເລືອກ): ລົງທະບຽນ = ບັງຄັບ; ຕ້ອງເປັນວັນທີແທ້ (ບໍ່ແມ່ນ 31/02) ອາຍຸ 10–110 ປີ
    if ($f['birth_date'] === '') {
        if ($mode === 'register') {
            $errors['birth_date'] = t('ກະລຸນາປ້ອນວັນເດືອນປີເກີດ');
        }
    } else {
        $bd = DateTime::createFromFormat('!Y-m-d', $f['birth_date']);
        $age = $bd ? (int)$bd->diff(new DateTime('today'))->y : -1;
        if (!$bd || $bd->format('Y-m-d') !== $f['birth_date'] || $bd > new DateTime('today') || $age < 10 || $age > 110) {
            $errors['birth_date'] = t('ວັນເດືອນປີເກີດບໍ່ຖືກຕ້ອງ');
        }
    }
    $tel = laoPhoneNormalize($f['tel']);
    if ($tel === null) {
        $errors['tel'] = t('ກະລຸນາປ້ອນເບີໂທມືຖືຈິງ: 020 XXXX XXXX (8 ຕົວເລກ) ຫຼື 030 XXX XXXX');
    } else {
        $f['tel'] = $tel;
    }
    if ($f['whatsapp'] !== '') {
        $wa = laoPhoneNormalize($f['whatsapp']);
        if ($wa === null) {
            $errors['whatsapp'] = t('ເບີ WhatsApp ບໍ່ຖືກຕ້ອງ (ວ່າງໄວ້ = ໃຊ້ເບີໂທດຽວກັນ)');
        } else {
            $f['whatsapp'] = $wa;
        }
    }
    // ລົງທະບຽນ: ຕ້ອງມີອີເມວ (ຊື່ + ເບີໂທ + ອີເມວ); ແກ້ໄຂຂໍ້ມູນ: ບໍ່ບັງຄັບ (ບັນຊີເກົ່າອາດບໍ່ມີ)
    if ($f['email'] === '' && $mode === 'register') {
        $errors['email'] = t('ກະລຸນາປ້ອນອີເມວ');
    } elseif ($f['email'] !== '' && ($emailErr = emailRealError($f['email'])) !== null) {
        $errors['email'] = $emailErr;   // ອີເມວປອມ / ຊົ່ວຄາວ / ໂດເມນບໍ່ມີຈິງ (models/email_check.php)
    }
    $f['email'] = mb_strtolower($f['email']);
    if ($f['business_type'] !== '' && !isset(memberBusinessTypes()[$f['business_type']])) {
        $f['business_type'] = 'other';
    }
    // ທີ່ຢູ່ຈັດສົ່ງ ບໍ່ບັງຄັບຕອນລົງທະບຽນ / ແກ້ໄຂຂໍ້ມູນ — ປ້ອນຕອນສັ່ງຊື້ (memberValidateOrder ບັງຄັບ) ແລ້ວບັນທຶກໃສ່ບັນຊີເອງ
    if ($f['courier_code'] !== '' && !isset(getLaoCouriers()[$f['courier_code']])) {
        $f['courier_code'] = '';
    }
    foreach (['shop_name' => 150, 'village' => 150, 'address' => 255, 'courier_branch' => 200] as $k => $max) {
        $f[$k] = mb_substr($f[$k], 0, $max);
    }
    if ($mode === 'register') {
        $f['password'] = (string)($in['password'] ?? '');
        $f['password2'] = (string)($in['password2'] ?? '');
        if (strlen($f['password']) < 6) {
            $errors['password'] = t('ລະຫັດຜ່ານຕ້ອງມີຢ່າງໜ້ອຍ 6 ຕົວ');
        } elseif ($f['password'] !== $f['password2']) {
            $errors['password2'] = t('ລະຫັດຜ່ານທັງສອງບໍ່ກົງກັນ');
        }
        if (empty($in['accept_terms'])) {
            $errors['accept_terms'] = t('ກະລຸນາຍອມຮັບເງື່ອນໄຂການໃຊ້ບໍລິການ');
        }
    }
    return [$f, $errors];
}

/** ເບີໂທ / ອີເມວ ຖືກໃຊ້ແລ້ວໂດຍສະມາຊິກອື່ນບໍ: ['field' => ຂໍ້ຄວາມ] ຫຼື [] */
function memberDuplicateErrors(PDO $pdo, array $f, int $exceptId = 0): array
{
    $errors = [];
    $st = $pdo->prepare("SELECT COUNT(*) FROM w_web_members WHERE tel = ? AND id <> ?");
    $st->execute([$f['tel'], $exceptId]);
    if ($st->fetchColumn()) {
        $errors['tel'] = t('ເບີໂທນີ້ລົງທະບຽນແລ້ວ — ກະລຸນາເຂົ້າສູ່ລະບົບ');
    }
    if ($f['email'] !== '') {
        $st = $pdo->prepare("SELECT COUNT(*) FROM w_web_members WHERE email = ? AND id <> ?");
        $st->execute([$f['email'], $exceptId]);
        if ($st->fetchColumn()) {
            $errors['email'] = t('ອີເມວນີ້ລົງທະບຽນແລ້ວ — ກະລຸນາເຂົ້າສູ່ລະບົບ');
        }
    }
    return $errors;
}

/** ສ້າງແຖວສະມາຊິກ + ໃຫ້ເລກລຳດັບຖັດໄປ. $cols = [ຖັນ => ຄ່າ]. ['success', 'id', 'member_no'] ຫຼື ['success' => false, 'field', 'message'] */
function memberInsert(PDO $pdo, array $cols): array
{
    $names = array_keys($cols);
    $sql = "INSERT INTO w_web_members (member_no, " . implode(', ', $names) . ") VALUES (?" . str_repeat(', ?', count($names)) . ")";
    // ເລກສະມາຊິກ = MAX + 1 ໃນ transaction (ລັອກແຖວສຸດທ້າຍ) — ມີຄົນສະໝັກພ້ອມກັນ unique key ກັນຊ້ຳ ແລ້ວລອງໃໝ່
    for ($try = 0; $try < 3; $try++) {
        try {
            $pdo->beginTransaction();
            $no = (int)$pdo->query("SELECT COALESCE(MAX(member_no), 0) + 1 FROM w_web_members FOR UPDATE")->fetchColumn();
            $pdo->prepare($sql)->execute(array_merge([$no], array_values($cols)));
            $id = (int)$pdo->lastInsertId();
            $pdo->commit();
            return ['success' => true, 'id' => $id, 'member_no' => $no];
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $msg = $e->getMessage();
            if (str_contains($msg, 'uniq_web_member_tel')) {
                return ['success' => false, 'field' => 'tel', 'message' => t('ເບີໂທນີ້ລົງທະບຽນແລ້ວ — ກະລຸນາເຂົ້າສູ່ລະບົບ')];
            }
            if (str_contains($msg, 'uniq_web_member_email')) {
                return ['success' => false, 'field' => 'email', 'message' => t('ອີເມວນີ້ລົງທະບຽນແລ້ວ — ກະລຸນາເຂົ້າສູ່ລະບົບ')];
            }
            if (!str_contains($msg, 'uniq_web_member_no')) {
                error_log('frontend memberInsert: ' . $msg);
                break;
            }
        }
    }
    return ['success' => false, 'field' => 'form', 'message' => t('ລົງທະບຽນບໍ່ສຳເລັດ ກະລຸນາລອງໃໝ່')];
}

/** ລົງທະບຽນຈາກຟອມ (validateMemberProfile ຜ່ານແລ້ວ) */
function memberRegister(PDO $pdo, array $f): array
{
    if ($dup = memberDuplicateErrors($pdo, $f)) {
        return ['success' => false, 'field' => array_key_first($dup), 'message' => reset($dup)];
    }
    $cols = [];
    foreach (MEMBER_PROFILE_FIELDS as $k) {
        $cols[$k] = $f[$k] !== '' ? $f[$k] : null;
    }
    $cols['password_hash'] = password_hash($f['password'], PASSWORD_DEFAULT);
    return memberInsert($pdo, $cols);
}

/** ບັນທຶກການແກ້ໄຂຂໍ້ມູນ (validateMemberProfile($in, 'profile') ຜ່ານແລ້ວ). [] = ສຳເລັດ, ['field' => ຂໍ້ຄວາມ] = ຜິດ */
function memberUpdateProfile(PDO $pdo, int $id, array $f): array
{
    if ($dup = memberDuplicateErrors($pdo, $f, $id)) {
        return $dup;
    }
    $set = [];
    $vals = [];
    foreach (MEMBER_PROFILE_FIELDS as $k) {
        $set[] = "`$k` = ?";
        $vals[] = $f[$k] !== '' ? $f[$k] : null;
    }
    $vals[] = $id;
    try {
        $pdo->prepare("UPDATE w_web_members SET " . implode(', ', $set) . " WHERE id = ?")->execute($vals);
    } catch (PDOException $e) {
        error_log('frontend memberUpdateProfile: ' . $e->getMessage());
        return ['form' => t('ບັນທຶກບໍ່ສຳເລັດ ກະລຸນາລອງໃໝ່')];
    }
    return [];
}

/** ປ່ຽນ / ຕັ້ງລະຫັດຜ່ານ (ສະມາຊິກ Google ທີ່ຍັງບໍ່ມີລະຫັດ ບໍ່ຕ້ອງປ້ອນລະຫັດເກົ່າ). [] = ສຳເລັດ */
function memberChangePassword(PDO $pdo, int $id, string $current, string $new, string $new2): array
{
    $st = $pdo->prepare("SELECT password_hash FROM w_web_members WHERE id = ?");
    $st->execute([$id]);
    $hash = (string)$st->fetchColumn();
    if ($hash !== '' && !password_verify($current, $hash)) {
        return ['current_password' => t('ລະຫັດຜ່ານປັດຈຸບັນບໍ່ຖືກຕ້ອງ')];
    }
    if (strlen($new) < 6) {
        return ['new_password' => t('ລະຫັດຜ່ານຕ້ອງມີຢ່າງໜ້ອຍ 6 ຕົວ')];
    }
    if ($new !== $new2) {
        return ['new_password2' => t('ລະຫັດຜ່ານທັງສອງບໍ່ກົງກັນ')];
    }
    $pdo->prepare("UPDATE w_web_members SET password_hash = ? WHERE id = ?")->execute([password_hash($new, PASSWORD_DEFAULT), $id]);
    return [];
}

/** ເຂົ້າລະບົບດ້ວຍ ເບີໂທ ຫຼື ອີເມວ. null = ສຳເລັດ, string = ຂໍ້ຄວາມຜິດພາດ */
function memberLogin(PDO $pdo, string $login, string $password): ?string
{
    $lock = $_SESSION['member_login_lock'] ?? ['fails' => 0, 'until' => 0];
    if ($lock['until'] > time()) {
        return t('ລອງຜິດຫຼາຍເທື່ອ ກະລຸນາລໍຖ້າ %d ນາທີ', (int)ceil(($lock['until'] - time()) / 60));
    }
    if (rateLimitCount($pdo, 'login') >= 30) {   // ເດົາລະຫັດຜ່ານຈາກ IP ດຽວ (ເຖິງປ່ຽນ session) → ລໍຖ້າ 15 ນາທີ
        return t('ລອງຜິດຫຼາຍເທື່ອ ກະລຸນາລໍຖ້າ %d ນາທີ', 15);
    }
    $login = trim($login);
    if (str_contains($login, '@')) {
        $st = $pdo->prepare("SELECT id, password_hash, status FROM w_web_members WHERE email = ? LIMIT 1");
        $st->execute([mb_strtolower($login)]);
    } else {
        // ເບີທີ່ບັນທຶກແບບໃໝ່ (020XXXXXXXX) ຫຼື ແບບເກົ່າ (ຕົວເລກຕາມທີ່ພິມ)
        $st = $pdo->prepare("SELECT id, password_hash, status FROM w_web_members WHERE tel IN (?, ?) LIMIT 1");
        $st->execute([laoPhoneNormalize($login) ?? '-', memberNormalizeTel($login)]);
    }
    $m = $st->fetch(PDO::FETCH_ASSOC);
    if ($m && empty($m['password_hash'])) {
        return t('ບັນຊີນີ້ລົງທະບຽນດ້ວຍ Google — ກະລຸນາກົດ "ເຂົ້າສູ່ລະບົບດ້ວຍ Google"');
    }
    if (!$m || !password_verify($password, $m['password_hash'])) {
        $lock['fails']++;
        if ($lock['fails'] >= 5) {
            $lock = ['fails' => 0, 'until' => time() + 300];
        }
        $_SESSION['member_login_lock'] = $lock;
        rateLimitAdd($pdo, 'login', 900);
        return t('ເບີໂທ/ອີເມວ ຫຼື ລະຫັດຜ່ານບໍ່ຖືກຕ້ອງ');
    }
    if ($m['status'] !== 'active') {
        return t('ບັນຊີນີ້ຖືກປິດໃຊ້ງານ ກະລຸນາຕິດຕໍ່ພວກເຮົາ');
    }
    memberStartSession($pdo, (int)$m['id']);
    return null;
}

function memberStartSession(PDO $pdo, int $id): void
{
    session_regenerate_id(true);
    if (function_exists('chatForget') && (int)($_SESSION['member_id'] ?? 0) !== $id) {
        chatForget();   // login / ລົງທະບຽນເປັນຄົນໃໝ່ → ບໍ່ເອົາຫ້ອງແຊັດຂອງຄົນກ່ອນໃນ browser ນີ້
    }
    $_SESSION['member_id'] = $id;
    $_SESSION['member_last_active'] = time();
    unset($_SESSION['member_login_lock']);
    $pdo->prepare("UPDATE w_web_members SET last_login_at = NOW() WHERE id = ?")->execute([$id]);
    // ເຂົ້າໃຫມ່ ແມ່ນຕ້ອງໄດ້ເຂົ້າສູ່ລະບົບໃຫມ່ — ບໍ່ໃຊ້ cookie ຈື່ໄວ້ 180 ມື້, ລຶບ cookie remember ອອກ
    memberSetRememberCookie('', time() - 3600);
}

function memberLogout(?PDO $pdo = null): void
{
    $pdo = $pdo ?? ($GLOBALS['pdo'] ?? null);
    $token = (string)($_COOKIE[MEMBER_REMEMBER_COOKIE] ?? '');
    if ($pdo && $token !== '') {
        try {
            $pdo->prepare("DELETE FROM w_web_member_tokens WHERE token_hash = ?")->execute([hash('sha256', $token)]);
        } catch (Throwable $e) {
        }
    }
    memberSetRememberCookie('', time() - 3600);
    if (function_exists('chatForget')) {
        chatForget();   // ຫ້ອງແຊັດຂອງສະມາຊິກຄົນນີ້ ບໍ່ຄ້າງໃນ browser
    }
    unset($_SESSION['member_id'], $_SESSION['member_last_active']);
    session_regenerate_id(true);
}

/* ---------- ຈື່ການເຂົ້າສູ່ລະບົບ (w_web_member_tokens, migration v40) ----------
 * cookie WLAOWEBREM = token ສຸ່ມ 64 ຕົວ (httponly), DB ເກັບແຕ່ sha256 → ຂໍ້ມູນ DB ຮົ່ວ ກໍເອົາໄປ login ບໍ່ໄດ້.
 * 1 ເຄື່ອງ = 1 token; ໝົດອາຍຸ 180 ມື້; ກົດອອກຈາກລະບົບ = ລຶບ token ຂອງເຄື່ອງນັ້ນ. ບັນຊີຖືກປິດ → currentMember() ບໍ່ຮັບ. */
const MEMBER_REMEMBER_COOKIE = 'WLAOWEBREM';
const MEMBER_REMEMBER_DAYS = 180;

function memberSetRememberCookie(string $value, int $expires): void
{
    if (headers_sent()) {
        return;
    }
    setcookie(MEMBER_REMEMBER_COOKIE, $value, [
        'expires'  => $expires,
        'path'     => frontBase(),
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    if ($value === '') {
        unset($_COOKIE[MEMBER_REMEMBER_COOKIE]);
    }
}

function memberRemember(PDO $pdo, int $id): void
{
    try {
        $old = (string)($_COOKIE[MEMBER_REMEMBER_COOKIE] ?? '');
        if ($old !== '') {
            $pdo->prepare("DELETE FROM w_web_member_tokens WHERE token_hash = ?")->execute([hash('sha256', $old)]);
        }
        $pdo->exec("DELETE FROM w_web_member_tokens WHERE expires_at < NOW()");
        $token = bin2hex(random_bytes(32));
        $expires = time() + MEMBER_REMEMBER_DAYS * 86400;
        $pdo->prepare("INSERT INTO w_web_member_tokens (member_id, token_hash, expires_at) VALUES (?, ?, ?)")
            ->execute([$id, hash('sha256', $token), date('Y-m-d H:i:s', $expires)]);
        memberSetRememberCookie($token, $expires);
    } catch (Throwable $e) {
        error_log('frontend memberRemember: ' . $e->getMessage());
    }
}

/** ບໍ່ມີ session ແຕ່ມີ cookie ຈື່ການເຂົ້າສູ່ລະບົບທີ່ຍັງບໍ່ໝົດອາຍຸ → login ໃຫ້ເອງ. ຄືນ member id ຫຼື 0 */
function memberFromRememberCookie(PDO $pdo): int
{
    $token = (string)($_COOKIE[MEMBER_REMEMBER_COOKIE] ?? '');
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return 0;
    }
    try {
        $st = $pdo->prepare("SELECT t.id, t.member_id FROM w_web_member_tokens t JOIN w_web_members m ON m.id = t.member_id
                             WHERE t.token_hash = ? AND t.expires_at > NOW() AND m.status = 'active' LIMIT 1");
        $st->execute([hash('sha256', $token)]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            memberSetRememberCookie('', time() - 3600);
            return 0;
        }
        $pdo->prepare("UPDATE w_web_member_tokens SET last_used_at = NOW() WHERE id = ?")->execute([(int)$row['id']]);
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }
        $_SESSION['member_id'] = (int)$row['member_id'];
        return (int)$row['member_id'];
    } catch (Throwable $e) {
        return 0;
    }
}

/** ໜ້າຫຼັງ login ໄປໃສ: ?next= ພາຍໃນເວັບເທົ່ານັ້ນ (ກັນ open redirect) */
function memberNextPath(): string
{
    $next = (string)($_POST['next'] ?? $_GET['next'] ?? '');
    return preg_match('#^pages/[a-z_]+(?:\.php)?(\?[\w=&%.-]*)?$#', $next) ? $next : 'pages/account.php';
}

/** ສະມາຊິກທີ່ຍັງບໍ່ມີເບີໂທ (ລົງທະບຽນດ້ວຍ Google) — ຕ້ອງເພີ່ມເບີກ່ອນສັ່ງຊື້ */
function memberNeedsPhone(?array $m): bool
{
    return $m !== null && trim((string)$m['tel']) === '';
}

/* ---------- ຈຳກັດຄວາມຖີ່ຕາມ IP (w_web_rate_limits, migration v42) — ກັນ bot ລົງທະບຽນ spam / ເດົາລະຫັດຜ່ານ ---------- */
function rateLimitKey(string $scope): string
{
    return $scope . ':' . hash('sha256', 'wlao-rl|' . ($_SERVER['REMOTE_ADDR'] ?? '0'));
}

/** ຈຳນວນຄັ້ງໃນຊ່ວງເວລາປັດຈຸບັນ (ໝົດຊ່ວງແລ້ວ = 0) */
function rateLimitCount(PDO $pdo, string $scope): int
{
    try {
        $st = $pdo->prepare("SELECT hits FROM w_web_rate_limits WHERE rkey = ? AND reset_at > NOW()");
        $st->execute([rateLimitKey($scope)]);
        return (int)$st->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

/** ນັບເພີ່ມ 1 ຄັ້ງ (ຊ່ວງເວລາ $windowSec ວິນາທີ ນັບຈາກຄັ້ງທຳອິດ) */
function rateLimitAdd(PDO $pdo, string $scope, int $windowSec): void
{
    try {
        $pdo->prepare("INSERT INTO w_web_rate_limits (rkey, hits, reset_at) VALUES (?, 1, NOW() + INTERVAL ? SECOND)
                       ON DUPLICATE KEY UPDATE hits = IF(reset_at <= NOW(), 1, hits + 1),
                                               reset_at = IF(reset_at <= NOW(), NOW() + INTERVAL ? SECOND, reset_at)")
            ->execute([rateLimitKey($scope), $windowSec, $windowSec]);
        if (random_int(1, 50) === 1) {
            $pdo->exec("DELETE FROM w_web_rate_limits WHERE reset_at < NOW()");   // ລ້າງແຖວເກົ່າ ບາງຄັ້ງຄາວ
        }
    } catch (Throwable $e) {
    }
}

/** ລົງທະບຽນໄດ້ບໍ: ຊ່ອງດັກ bot (website) ຕ້ອງວ່າງ, 1 IP ລົງທະບຽນສຳເລັດໄດ້ສູງສຸດ 5 ບັນຊີ / ຊົ່ວໂມງ. null = ໄດ້, string = ເຫດຜົນ */
function memberRegisterBlocked(PDO $pdo, array $in): ?string
{
    if (trim((string)($in['website'] ?? '')) !== '') {
        return t('ລົງທະບຽນບໍ່ສຳເລັດ ກະລຸນາລອງໃໝ່');
    }
    if (rateLimitCount($pdo, 'reg') >= 5) {
        return t('ລົງທະບຽນຫຼາຍເກີນໄປຈາກເຄື່ອງນີ້ ກະລຸນາລອງໃໝ່ໃນ 1 ຊົ່ວໂມງ');
    }
    return null;
}


/** ຮູບໂປຣໄຟລ໌ສະມາຊິກ: ຮູບ Google (avatar_url) ຫຼື ຮູບເລີ່ມຕົ້ນ backend/assets/img/users/default.png (ຄືກັບຜູ້ໃຊ້ຫຼັງບ້ານ) */
function memberAvatarUrl(array $m): string
{
    $a = (string)($m['avatar_url'] ?? '');
    if ($a === '') {
        return backendUrl('assets/img/users/default.png');
    }
    return preg_match('#^https?://#i', $a) ? $a : siteRoot() . ltrim($a, '/');   // ຮູບທີ່ອັບໂຫຼດເອງ = path ຈາກ root (backend/assets/img/members/…)
}

/** ຮູບເຕັມ (ບໍ່ຕັດ) ຂອງຮູບທີ່ອັບໂຫຼດເອງ — ກົດເບິ່ງເຕັມຈໍ. ບໍ່ມີ (ຮູບ Google / ຮູບເກົ່າ) → ຮູບໂປຣໄຟລ໌ປົກກະຕິ */
function memberAvatarFullUrl(array $m): string
{
    $a = (string)($m['avatar_url'] ?? '');
    if (str_starts_with($a, 'backend/assets/img/members/')) {
        $full = substr($a, 0, -4) . '_full.jpg';
        if (is_file(dirname(__DIR__, 2) . '/' . $full)) {
            return siteRoot() . $full;
        }
    }
    return memberAvatarUrl($m);
}

/**
 * ອັບໂຫຼດຮູບໂປຣໄຟລ໌ (pages/account.php): JPG/PNG/WEBP ≤ 5MB → ສີ່ຫຼ່ຽມ 480px + ຮູບເຕັມ ≤1600px (GD) → backend/assets/img/members/m<id>_<ສຸ່ມ>.jpg / _full.jpg
 * ລຶບຮູບເກົ່າທີ່ເຄີຍອັບ (ບໍ່ແຕະຮູບ Google). null = ສຳເລັດ / ບໍ່ໄດ້ເລືອກຮູບ, string = ຂໍ້ຄວາມຜິດພາດ
 */
function memberSaveAvatar(PDO $pdo, array $member, ?array $file): ?string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return t('ອັບໂຫຼດຮູບບໍ່ສຳເລັດ ກະລຸນາລອງໃໝ່');
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return t('ຮູບໃຫຍ່ເກີນ 5 MB');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        return t('ຮອງຮັບສະເພາະຮູບ JPG, PNG ຫຼື WEBP');
    }
    $dirRel = 'backend/assets/img/members';
    $dir = dirname(__DIR__, 2) . '/' . $dirRel;
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
        return t('ບັນທຶກຮູບບໍ່ສຳເລັດ');
    }
    $name = 'm' . (int)$member['id'] . '_' . bin2hex(random_bytes(5)) . '.jpg';
    $src = function_exists('imagecreatefromstring') ? @imagecreatefromstring((string)file_get_contents($file['tmp_name'])) : false;
    if ($src) {
        $w = imagesx($src); $h = imagesy($src); $side = min($w, $h);
        // 1) ຮູບໂປຣໄຟລ໌ສີ່ຫຼ່ຽມ 480px (ສະແດງໃນວົງມົນ — ຄົມໃນຈໍ retina)
        $dst = imagecreatetruecolor(480, 480);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, (int)(($w - $side) / 2), (int)(($h - $side) / 2), 480, 480, $side, $side);
        $ok = imagejpeg($dst, $dir . '/' . $name, 92);
        imagedestroy($dst);
        // 2) ຮູບເຕັມຕາມທີ່ອັບໂຫຼດ (ບໍ່ຕັດ, ດ້ານຍາວສູງສຸດ 1600px) → <ຊື່>_full.jpg ສຳລັບກົດເບິ່ງເຕັມຈໍ (memberAvatarFullUrl)
        $scale = min(1, 1600 / max($w, $h));
        $fw = max(1, (int)round($w * $scale)); $fh = max(1, (int)round($h * $scale));
        $full = imagecreatetruecolor($fw, $fh);
        imagefill($full, 0, 0, imagecolorallocate($full, 255, 255, 255));
        imagecopyresampled($full, $src, 0, 0, 0, 0, $fw, $fh, $w, $h);
        imagejpeg($full, $dir . '/' . substr($name, 0, -4) . '_full.jpg', 92);
        imagedestroy($full); imagedestroy($src);
    } else {
        $ok = @move_uploaded_file($file['tmp_name'], $dir . '/' . $name);
    }
    if (!$ok) {
        return t('ບັນທຶກຮູບບໍ່ສຳເລັດ');
    }
    $old = (string)($member['avatar_url'] ?? '');
    if (str_starts_with($old, $dirRel . '/')) {
        @unlink(dirname(__DIR__, 2) . '/' . $old);
        @unlink(dirname(__DIR__, 2) . '/' . substr($old, 0, -4) . '_full.jpg');
    }
    $pdo->prepare("UPDATE w_web_members SET avatar_url = ? WHERE id = ?")->execute([$dirRel . '/' . $name, (int)$member['id']]);
    return null;
}
