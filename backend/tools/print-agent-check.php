<?php
/**
 * Print agent diagnostic — run this on the SHOP PC to find out why nothing is printing.
 *
 *   php print-agent-check.php --url=https://your-minipos-site.com --token=YOUR_TOKEN
 *
 * It walks the whole chain (PHP extensions -> server reachable -> token accepted -> server in
 * agent mode -> queue table -> printers reachable on this LAN) and says which link is broken
 * instead of failing silently the way the agent loop does.
 */

$opts    = getopt('', ['url:', 'token:']);
$baseUrl = rtrim($opts['url'] ?? '', '/');
$token   = $opts['token'] ?? '';

if ($baseUrl === '' || $token === '') {
    fwrite(STDERR, "Usage: php print-agent-check.php --url=https://your-minipos-site.com --token=YOUR_TOKEN\n");
    exit(1);
}

$pass = 0;
$fail = 0;
function ok($m)   { global $pass; $pass++; echo "  [OK]   $m\n"; }
function bad($m, $fix = '') { global $fail; $fail++; echo "  [ຜິດ]  $m\n"; if ($fix) echo "         -> $fix\n"; }
function info($m) { echo "         $m\n"; }

echo "\n=== 1. PHP ຢູ່ຄອມນີ້ ===\n";
echo "  PHP " . PHP_VERSION . "\n";
extension_loaded('curl') ? ok('ມີ curl') : bad('ບໍ່ມີ curl extension', 'ເປີດ extension=curl ໃນ php.ini ຂອງຄອມນີ້');

if (!extension_loaded('curl')) {
    echo "\nຢຸດກວດ: ຕ້ອງມີ curl ກ່ອນຈຶ່ງກວດຕໍ່ໄດ້.\n";
    exit(1);
}

echo "\n=== 2. ຕິດຕໍ່ເຊີບເວີ ===\n";
$endpoint = $baseUrl . '/api/print_agent.php?action=status';
$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query(['token' => $token]),
    CURLOPT_TIMEOUT        => 20,
]);
$body     = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
$curlNo   = curl_errno($ch);
curl_close($ch);

if ($body === false) {
    if (in_array($curlNo, [60, 77, 35], true)) {
        bad("SSL ຜິດພາດ: $curlErr", 'PHP ຢູ່ Windows ມັກບໍ່ມີໄຟລ໌ CA — ດາວໂຫຼດ cacert.pem ແລ້ວຕັ້ງ curl.cainfo ໃນ php.ini (ຫຼືໃຊ້ http ຊົ່ວຄາວເພື່ອທົດສອບ)');
    } else {
        bad("ຕິດຕໍ່ບໍ່ໄດ້: $curlErr", 'ກວດ --url ວ່າຖືກບໍ່ ແລະ ຄອມນີ້ເຂົ້າອິນເຕີເນັດໄດ້ບໍ່');
    }
    echo "\nສະຫຼຸບ: ຜ່ານ $pass / ຜິດ $fail\n";
    exit(1);
}
ok("ຕິດຕໍ່ໄດ້ (HTTP $httpCode)");

$data = json_decode($body, true);
if (!is_array($data)) {
    bad('ຕອບກັບມາບໍ່ແມ່ນ JSON', 'ອາດເປັນໜ້າ error ຂອງເຊີບເວີ — ຂໍ້ຄວາມ: ' . substr(strip_tags((string)$body), 0, 200));
    echo "\nສະຫຼຸບ: ຜ່ານ $pass / ຜິດ $fail\n";
    exit(1);
}

echo "\n=== 3. ລະຫັດຕົວແທນພິມ ===\n";
if ($httpCode === 403 || empty($data['success'])) {
    bad('ລະຫັດບໍ່ຖືກຕ້ອງ: ' . ($data['message'] ?? '?'), 'ຄັດລອກລະຫັດໃໝ່ຈາກ Settings > ຕັ້ງຄ່າເຄື່ອງພິມ ໃສ່ໃນ print-agent.bat');
    echo "\nສະຫຼຸບ: ຜ່ານ $pass / ຜິດ $fail\n";
    exit(1);
}
ok('ລະຫັດຖືກຕ້ອງ');

echo "\n=== 4. ຄ່າຕັ້ງຢູ່ເຊີບເວີ ===\n";
if (($data['print_mode'] ?? '') === 'agent') {
    ok('ຮູບແບບການພິມ = ຜ່ານຕົວແທນພິມ');
} else {
    bad('ຮູບແບບການພິມ = ' . ($data['print_mode'] ?? '?') . ' (ຍັງເປັນພິມໂດຍກົງ)', 'ໄປ Settings > ຕັ້ງຄ່າເຄື່ອງພິມ ປ່ຽນເປັນ "ຜ່ານຕົວແທນພິມ" ແລ້ວກົດບັນທຶກ');
}
!empty($data['print_jobs_table'])
    ? ok('ມີຕາຕະລາງ print_jobs')
    : bad('ບໍ່ມີຕາຕະລາງ print_jobs', 'migration ບໍ່ໄດ້ແລ່ນ — ລຶບໄຟລ໌ config/.db_schema_v* ຢູ່ເຊີບເວີ ແລ້ວເປີດເວັບ 1 ຄັ້ງ');
info('ງານພິມຄ້າງໃນຄິວ: ' . (int)($data['pending'] ?? 0));

echo "\n=== 5. ຕໍ່ຫາປິ່ນເຕີຈາກຄອມນີ້ ===\n";
$printers = $data['printers'] ?? [];
if (!$printers) {
    bad('ເຊີບເວີບໍ່ມີລາຍການປິ່ນເຕີ Network LAN', 'ເພີ່ມປິ່ນເຕີໃນ Settings > ຕັ້ງຄ່າເຄື່ອງພິມ (ປະເພດ Network LAN)');
}
foreach ($printers as $p) {
    $label = $p['name'] . ' (' . ($p['ip_address'] ?: '-') . ')';
    if (empty($p['active'])) {
        info("ຂ້າມ $label — ຖືກປິດໃຊ້ງານ");
        continue;
    }
    if (!empty($p['ip_address'])) {
        $sock = @fsockopen($p['ip_address'], 9100, $errno, $errstr, 3);
        if ($sock) {
            fclose($sock);
            ok("ຕໍ່ຫາ $label ໄດ້");
        } else {
            bad("ຕໍ່ຫາ $label ບໍ່ໄດ້ ($errstr)", 'ກວດວ່າປິ່ນເຕີເປີດຢູ່, ຢູ່ວົງເນັດດຽວກັນກັບຄອມນີ້ ແລະ IP ຍັງບໍ່ປ່ຽນ');
        }
    } else {
        info("ຂ້າມ $label — ບໍ່ມີ IP");
    }
}

echo "\n===============================\n";
echo ($fail === 0 ? "ທຸກຢ່າງພ້ອມ — ແລ່ນ print-agent.bat ໄດ້ເລີຍ\n" : "ພົບບັນຫາ $fail ຈຸດ (ຜ່ານ $pass) — ແກ້ຕາມລູກສອນຂ້າງເທິງ\n");
echo "===============================\n";
