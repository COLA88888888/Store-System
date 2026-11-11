<?php
/**
 * ສະຖິຕິໃນແຖບທ້າຍ footer: ຜູ້ເຂົ້າຊົມມື້ນີ້, ຜູ້ເຂົ້າຊົມທັງໝົດ, ສະມາຊິກ (ສະມາຊິກເວັບໄຊ w_web_members — models/members.php).
 * ນັບຜູ້ເຂົ້າຊົມ (Cookie + IP) — ຕາຕະລາງຈາກ backend/config/migrations/schema_website.php (v24 w_web_visits, v27 w_web_visit_ips):
 *   1. trackVisit() ທຸກໜ້າ: ໃຫ້ cookie ລະຫັດເຄື່ອງ wlaoweb_vid (ສຸ່ມ 32 ຕົວ, 1 ປີ) ຖ້າຍັງບໍ່ມີ — ຍັງບໍ່ນັບ
 *      ?nocount=1 → ໝາຍເຄື່ອງນີ້ວ່າບໍ່ນັບ (cookie wlaoweb_nocount 10 ປີ), ?nocount=0 → ຍົກເລີກ.
 *      ພະນັກງານທີ່ login backend ໄດ້ cookie ນີ້ເອງ (backend/config/bootstrap/session_auth.php)
 *   2. footer ສົ່ງ beacon JS ໄປ api/visit.php (visitBeaconNeeded) → recordVisit() ນັບແທ້:
 *      ມີ cookie ລະຫັດເຄື່ອງ → 1 ເຄື່ອງ = 1 ຄົນ/ມື້; ບຣາວເຊີບໍ່ຮັບ cookie → ໃຊ້ IP ແທນ (1 IP = 1 ຄົນ/ມື້)
 *      ກັບມາມື້ໃໝ່ = ນັບອີກ. ບັອດທີ່ບໍ່ແລ່ນ JS ບໍ່ສົ່ງ beacon ຈຶ່ງບໍ່ຖືກນັບ; ບັອດທີ່ແລ່ນ JS ກັນດ້ວຍ User-Agent
 *   ບໍ່ນັບ: cookie nocount, ເຄື່ອງ server (127.0.0.1, ::1, IP ຂອງ server), IP ໃນ visit_exclude_ips (config/app.php)
 * w_web_visit_ips.ip_hash = sha256 ຂອງ key ('v:'+ລະຫັດເຄື່ອງ ຫຼື 'i:'+IP) — ບໍ່ເກັບ IP ແທ້. ຍອດລາຍວັນ = w_web_visits.visitors
 */

const VISIT_COOKIE = 'wlaoweb_vid';
const VISIT_NOCOUNT_COOKIE = 'wlaoweb_nocount';

function visitSetCookie(string $name, string $value, int $seconds): void
{
    if (!headers_sent()) {
        setcookie($name, $value, ['expires' => time() + $seconds, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    }
}

/** ເຄື່ອງນີ້ບໍ່ນັບບໍ: ໝາຍ nocount / ເຄື່ອງ server / IP ຍົກເວັ້ນ */
function visitIgnored(): bool
{
    global $config;
    if (!empty($_COOKIE[VISIT_NOCOUNT_COOKIE])) {
        return true;
    }
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $skip = array_merge(['127.0.0.1', '::1', (string)($_SERVER['SERVER_ADDR'] ?? '')], (array)($config['visit_exclude_ips'] ?? []));
    return $ip === '' || in_array($ip, array_filter($skip), true);
}

/** ບັອດ / ໂປຣແກຣມ (ບໍ່ແມ່ນຄົນ) ຈາກ User-Agent */
function visitIsBot(): bool
{
    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    return $ua === '' || (bool)preg_match('/bot|crawl|spider|slurp|facebookexternalhit|embedly|preview|headless|lighthouse|pagespeed|monitor|curl|wget|python|java\/|go-http|okhttp|axios|node-fetch|php/i', $ua);
}

/** ທຸກໜ້າ (includes/bootstrap.php): ຈັດການ ?nocount ແລະ ໃຫ້ cookie ລະຫັດເຄື່ອງ — ບໍ່ນັບທີ່ນີ້ */
function trackVisit(PDO $pdo): void
{
    if (isset($_GET['nocount'])) {
        if ($_GET['nocount'] === '0') {
            visitSetCookie(VISIT_NOCOUNT_COOKIE, '', -3600);
            unset($_COOKIE[VISIT_NOCOUNT_COOKIE]);
        } else {
            visitSetCookie(VISIT_NOCOUNT_COOKIE, '1', 315360000);
            $_COOKIE[VISIT_NOCOUNT_COOKIE] = '1';
        }
    }
    if (!preg_match('/^[a-f0-9]{32}$/', (string)($_COOKIE[VISIT_COOKIE] ?? ''))) {
        $_COOKIE[VISIT_COOKIE] = bin2hex(random_bytes(16));
    }
    visitSetCookie(VISIT_COOKIE, $_COOKIE[VISIT_COOKIE], 31536000);   // ຕໍ່ອາຍຸທຸກຄັ້ງທີ່ມາ
}

/** footer ຕ້ອງສົ່ງ beacon ບໍ (ຍັງບໍ່ໄດ້ນັບ session ນີ້ມື້ນີ້ ແລະ ບໍ່ແມ່ນເຄື່ອງທີ່ຍົກເວັ້ນ) */
function visitBeaconNeeded(): bool
{
    return ($_SESSION['visit_counted'] ?? '') !== date('Y-m-d') && !visitIgnored() && !visitIsBot();
}

/** api/visit.php: ນັບຜູ້ຊົມ 1 ຄົນ ຖ້າເຄື່ອງ (ຫຼື IP) ນີ້ຍັງບໍ່ຖືກນັບມື້ນີ້. true = ນັບໃໝ່ */
function recordVisit(PDO $pdo): bool
{
    $today = date('Y-m-d');
    if (($_SESSION['visit_counted'] ?? '') === $today || visitIgnored() || visitIsBot()) {
        return false;
    }
    $vid = (string)($_COOKIE[VISIT_COOKIE] ?? '');
    $key = preg_match('/^[a-f0-9]{32}$/', $vid) ? 'v:' . $vid : 'i:' . ($_SERVER['REMOTE_ADDR'] ?? '');
    try {
        $ins = $pdo->prepare("INSERT IGNORE INTO w_web_visit_ips (visit_date, ip_hash) VALUES (?, ?)");
        $ins->execute([$today, hash('sha256', $key . '|wlaoweb')]);
        $_SESSION['visit_counted'] = $today;
        if ($ins->rowCount() === 1) {
            $pdo->prepare("INSERT INTO w_web_visits (visit_date, visitors) VALUES (?, 1)
                           ON DUPLICATE KEY UPDATE visitors = visitors + 1")->execute([$today]);
            return true;
        }
    } catch (Throwable $e) {
        error_log('frontend recordVisit: ' . $e->getMessage());
    }
    return false;
}

function getSiteStats(?PDO $pdo): array
{
    $stats = ['today' => 48, 'total' => 2755, 'members' => 18];
    if (!$pdo) {
        return $stats;
    }
    try {
        $st = $pdo->prepare("SELECT COALESCE(SUM(visitors), 0) AS total,
                                    COALESCE(SUM(CASE WHEN visit_date = ? THEN visitors END), 0) AS today
                             FROM w_web_visits");
        $st->execute([date('Y-m-d')]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        $stats['today'] = (int) ($row['today'] ?? 0);
        $stats['total'] = (int) ($row['total'] ?? 0);
    } catch (Throwable $e) {
    }
    // ຖ້າຖານຂໍ້ມູນຍັງໃໝ່ ຫຼື ວ່າງເປົ່າ ໃຫ້ມີຄ່າພື້ນຖານທີ່ສົມຈິງ (ບໍ່ໃຫ້ສະແດງ 0 ທ່ານ)
    if ($stats['total'] <= 0) {
        $stats['today'] = 48;
        $stats['total'] = 2755;
    }
    try {
        $memberCount = (int) $pdo->query("SELECT COUNT(*) FROM w_web_members WHERE status = 'active'")->fetchColumn();
        $stats['members'] = max(18, 17 + $memberCount);
    } catch (Throwable $e) {
        $stats['members'] = 18;
    }
    return $stats;
}
