<?php
/**
 * ລົງທະບຽນ / ເຂົ້າສູ່ລະບົບສະມາຊິກດ້ວຍ Google (Google Identity Services — ປຸ່ມ components/google_button.php).
 *   1. browser ໄດ້ ID token (JWT) ຈາກ Google → assets/js/member.js ສົ່ງໄປ api/member.php (action=google, credential)
 *   2. googleVerifyIdToken() ກວດ token ກັບ Google (tokeninfo): aud = Client ID ຂອງເຮົາ, iss, ໝົດອາຍຸ, ອີເມວຢືນຢັນແລ້ວ
 *   3. memberGoogleLogin(): ພົບ google_id → login; ພົບອີເມວດຽວກັນ → ຜູກ Google ກັບບັນຊີນັ້ນ; ບໍ່ພົບ → ສ້າງສະມາຊິກໃໝ່
 *      (ຍັງບໍ່ມີເບີໂທ → ໜ້າ account ບັງຄັບເພີ່ມເບີໂທຈິງກ່ອນສັ່ງຊື້)
 * ຕັ້ງຄ່າ: frontend/config/google.php (ສຳເນົາຈາກ google.example.php) — ບໍ່ມີ Client ID = ບໍ່ສະແດງປຸ່ມ Google.
 */

function googleClientId(): string
{
    static $id = null;
    if ($id === null) {
        $file = dirname(__DIR__) . '/config/google.php';
        $cfg = is_file($file) ? (require $file) : [];
        $id = is_array($cfg) ? trim((string)($cfg['client_id'] ?? '')) : '';
    }
    return $id;
}

/** ກວດ ID token ກັບ Google. ສຳເລັດ → payload (sub, email, name, picture), ບໍ່ຜ່ານ → null */
function googleVerifyIdToken(string $idToken): ?array
{
    $clientId = googleClientId();
    if ($clientId === '' || !preg_match('/^[\w-]+\.[\w-]+\.[\w-]+$/', $idToken)) {
        return null;
    }
    $ch = curl_init('https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode($idToken));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_CONNECTTIMEOUT => 5]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false || $code !== 200) {
        error_log('frontend googleVerifyIdToken: HTTP ' . $code);
        return null;
    }
    $p = json_decode((string)$body, true);
    if (!is_array($p)
        || ($p['aud'] ?? '') !== $clientId
        || !in_array($p['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com'], true)
        || (int)($p['exp'] ?? 0) < time()
        || !in_array((string)($p['email_verified'] ?? ''), ['true', '1'], true)
        || empty($p['sub']) || empty($p['email'])) {
        return null;
    }
    return $p;
}

/** login / ລົງທະບຽນ ຈາກ payload ຂອງ Google. ['success', 'id', 'is_new', 'member_no'] ຫຼື ['success' => false, 'message'] */
function memberGoogleLogin(PDO $pdo, array $g): array
{
    $sub = (string)$g['sub'];
    $email = mb_strtolower((string)$g['email']);
    $st = $pdo->prepare("SELECT id, member_no, status, google_id FROM w_web_members WHERE google_id = ? OR email = ? ORDER BY (google_id = ?) DESC LIMIT 1");
    $st->execute([$sub, $email, $sub]);
    $m = $st->fetch(PDO::FETCH_ASSOC);
    if ($m) {
        if ($m['status'] !== 'active') {
            return ['success' => false, 'message' => t('ບັນຊີນີ້ຖືກປິດໃຊ້ງານ ກະລຸນາຕິດຕໍ່ພວກເຮົາ')];
        }
        if ((string)$m['google_id'] !== $sub) {
            $pdo->prepare("UPDATE w_web_members SET google_id = ?, avatar_url = COALESCE(avatar_url, ?) WHERE id = ?")
                ->execute([$sub, $g['picture'] ?? null, $m['id']]);
        }
        memberStartSession($pdo, (int)$m['id']);
        return ['success' => true, 'id' => (int)$m['id'], 'is_new' => false, 'member_no' => (int)$m['member_no']];
    }
    $name = trim((string)($g['name'] ?? '')) ?: strstr($email, '@', true);
    $res = memberInsert($pdo, [
        'name'       => mb_substr($name, 0, 150),
        'email'      => $email,
        'google_id'  => $sub,
        'avatar_url' => isset($g['picture']) ? mb_substr((string)$g['picture'], 0, 500) : null,
    ]);
    if (!$res['success']) {
        return ['success' => false, 'message' => $res['message']];
    }
    memberStartSession($pdo, $res['id']);
    return ['success' => true, 'id' => $res['id'], 'is_new' => true, 'member_no' => $res['member_no']];
}
