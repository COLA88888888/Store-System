<?php
/**
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * ໜ້າຈໍລູກຄ້າ (Customer Display): ເປີດຢູ່ຈໍທີສອງ, ຮັບຂໍ້ມູນກະຕ່າຈາກໜ້າຂາຍຜ່ານ BroadcastChannel/localStorage ແລ້ວສະແດງລາຍການ, ຍອດເງິນ ແລະ QR ຊຳລະ.
 *   - partials/customer_display/styles_layout.php : CSS (1): ໂຄງໜ້າຈໍ ແລະ ຮ້ານ
 *   - partials/customer_display/styles_payment.php : CSS (2): QR ຊຳລະ ແລະ ຍອດລວມ
 *   - partials/customer_display/styles_theme.php : CSS (3): ໂສມໃໝ່ — ໂລໂກ້/ຊື່ຮ້ານຢູ່ແຖບເທິງ (ທັບ CSS 1–2)
 *   - partials/customer_display/page_body.php : ສ່ວນສະແດງຜົນ
 *   - partials/customer_display/display_js.php : JS ຂອງຈໍລູກຄ້າ
 */
// ============================================================
// customer_display.php - DUAL-SCREEN POS CUSTOMER DISPLAY
// ໜ້າຈໍສະແດງຜົນລູກຄ້າ Real-Time (POS System Primary Blue Theme)
// ============================================================
if (isset($_GET['action']) && $_GET['action'] === 'native_f11') {
    header('Content-Type: application/json; charset=utf-8');
    $lockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wlaodev_cd_f11.lock';
    $force = !empty($_GET['force']);
    $nowMs = (int)round(microtime(true) * 1000);
    $lastMs = is_file($lockFile) ? (int)@file_get_contents($lockFile) : 0;
    if (!$force && ($nowMs - $lastMs) < 320) {
        echo json_encode(['ok' => true, 'dedup' => true]);
        exit;
    }
    @file_put_contents($lockFile, (string)$nowMs);

    $triggered = false;
    // ທາງຫຼັກ: tools/cd-fullscreen.cs (ຄອມໄພລ໌ເປັນ .exe ຄັ້ງດຽວ) — ຍ້າຍຈໍລູກຄ້າໄປຈໍທີ 2, ກົດ F11 ສະເພາະເມື່ອດຶງຂຶ້ນໜ້າໄດ້ແທ້,
    // ແລ້ວຄືນ focus ໃຫ້ຈໍພະນັກງານ. VBScript ລຸ່ມນີ້ (AppActivate) ມັກຖືກ Windows ບລັອກບໍ່ໃຫ້ດຶງໜ້າຕ່າງຂຶ້ນໜ້າ → ເປັນທາງສຳຮອງ.
    if (stripos(PHP_OS, 'WIN') === 0 && function_exists('exec')) {
        $cs = realpath(__DIR__ . '/../../tools/cd-fullscreen.cs');
        $exe = $cs ? sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'MiniPosCdFullscreen_' . filemtime($cs) . '.exe' : '';
        if ($cs && !is_file($exe)) {
            $csc = (getenv('WINDIR') ?: 'C:\\Windows') . '\\Microsoft.NET\\Framework64\\v4.0.30319\\csc.exe';
            if (!is_file($csc)) $csc = (getenv('WINDIR') ?: 'C:\\Windows') . '\\Microsoft.NET\\Framework\\v4.0.30319\\csc.exe';
            if (is_file($csc)) {
                $part = $exe . '.' . getmypid() . '.part';
                @exec('"' . $csc . '" /nologo /optimize /target:winexe /out:"' . $part . '" "' . $cs . '" 2>&1', $o, $c);
                if ($c === 0 && is_file($part) && !@rename($part, $exe)) @unlink($part);
            }
        }
        if ($exe && is_file($exe)) {
            $out = [];
            $code = 1;
            @exec('"' . $exe . '" "WlaoDev Customer Display"' . ($force ? ' --force' : '') . ' 2>&1', $out, $code);
            echo json_encode(['ok' => $code === 0, 'result' => trim(implode(' ', $out))]);
            exit;
        }
    }
    if (stripos(PHP_OS, 'WIN') === 0 && function_exists('exec')) {
        $vbsPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wlaodev_cd_f11.vbs';
        $vbsCode = "Set sh = CreateObject(\"WScript.Shell\")\r\n"
                 . "For i = 1 To 25\r\n"
                 . "    If sh.AppActivate(\"WlaoDev Customer Display\") Then\r\n"
                 . "        WScript.Sleep 20\r\n"
                 . "        sh.SendKeys \"{F11}\"\r\n"
                 . "        Exit For\r\n"
                 . "    End If\r\n"
                 . "    WScript.Sleep 10\r\n"
                 . "Next\r\n";
        @file_put_contents($vbsPath, $vbsCode);
        if (is_file($vbsPath)) {
            @exec('cscript //NoLogo //B ' . escapeshellarg($vbsPath));
            $triggered = true;
        }
    }
    echo json_encode(['ok' => $triggered]);
    exit;
}

// ?action=cashiers — ຄຳຂໍເບື້ອງຫຼັງຂອງຈໍລູກຄ້າ: ບໍ່ນັບເປັນການເຄື່ອນໄຫວຂອງຜູ້ໃຊ້ (ບໍ່ຕໍ່ເວລາ / ບໍ່ອັບເດດ last_activity)
if (($_GET['action'] ?? '') === 'cashiers' && !defined('SESSION_PASSIVE_REQUEST')) define('SESSION_PASSIVE_REQUEST', true);

require_once __DIR__ . '/../../config/db.php';

// ພະນັກງານຂາຍທີ່ກຳລັງລັອກອິນຢູ່ສາຂານີ້ (ເຄື່ອນໄຫວພາຍໃນ 5 ນາທີ ຄືກັບ "ໃຊ້ງານຢູ່" ໜ້າຜູ້ນຳໃຊ້; logout ລຶບ last_activity)
// — ສາກ 3D ສ້າງພະນັກງານຢູ່ເຄົ້າເຕີຕາມຈຳນວນນີ້ ແລະ ຂຽນຊື່ໄວ້ເທິງຫົວ (partials/customer_display/minimart_3d/people_tags.php)
if (!function_exists('cdOnlineCashiers')) {
    function cdOnlineCashiers(PDO $pdo, $storeId) {
        $st = $pdo->prepare("SELECT Id, COALESCE(NULLIF(TRIM(fname), ''), username) AS name, gender, last_activity
                               FROM st_tbuser
                              WHERE store_id = ? AND COALESCE(account_active, 1) = 1
                                AND last_activity IS NOT NULL AND last_activity >= ?
                                AND (sale = 1 OR Id = 1 OR status IN ('ຜູ້ບໍລິຫານ', 'admin', 'super admin'))
                              ORDER BY last_activity DESC
                              LIMIT 4");
        $st->execute([(int)$storeId, date('Y-m-d H:i:s', time() - 300)]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
if (!function_exists('cdOnlineCashierNames')) {
    function cdOnlineCashierNames(PDO $pdo, $storeId) {
        $rows = cdOnlineCashiers($pdo, $storeId);
        return array_values(array_filter(array_map(function($r) { return strval($r['name'] ?? ''); }, $rows)));
    }
}
if (($_GET['action'] ?? '') === 'cashiers') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    $names = [];
    $cashiers = [];
    try {
        if (isset($pdo) && !empty($_SESSION['user_id'])) {
            $raw = cdOnlineCashiers($pdo, getPosStoreId());
            foreach ($raw as $r) {
                $n = trim((string)($r['name'] ?? ''));
                if ($n !== '') {
                    $names[] = $n;
                    $cashiers[] = [
                        'id' => (int)($r['Id'] ?? 0),
                        'name' => $n,
                        'gender' => (string)($r['gender'] ?? ''),
                        'last_activity' => (string)($r['last_activity'] ?? '')
                    ];
                }
            }
        }
    } catch (Throwable $e) {}
    echo json_encode(['names' => $names, 'cashiers' => $cashiers], JSON_UNESCAPED_UNICODE);
    exit;
}

// Store Info Retrieval directly from Database
$company = [
    'com_name'    => '',
    'com_tel'     => '',
    'com_address' => '',
    'com_logo'    => ''
];

try {
    if (isset($pdo)) {
        $cinfo = getCompanyInfoForBranch($pdo, getPosStoreId());
        if ($cinfo) {
            $company['com_name']    = !empty($cinfo['com_name_la']) ? $cinfo['com_name_la'] : 'POS Retail Store';
            $company['com_tel']     = $cinfo['com_tel'] ?? '';
            $company['com_address'] = $cinfo['com_address'] ?? '';
            $company['qr_img']      = !empty($cinfo['qr_img']) ? ('../../assets/img/qr/' . $cinfo['qr_img']) : '';
            if (!empty($cinfo['img_url'])) {
                $company['com_logo'] = '../../assets/img/logo/' . $cinfo['img_url'];
            }
        } else {
            $company['com_name'] = 'POS Retail Store';
        }
    }
} catch (Throwable $e) {}

// Active bank accounts — shown as payment QR codes on the customer screen (replaces the
// old "thank you" card). Only the bank name + QR image are shown; account name / number
// are deliberately kept off the public-facing screen.
$cd_bank_accounts = [];
try {
    if (isset($pdo)) {
        // This branch's own banks, plus any shared across every branch (store_id = 0).
        $bStmt = $pdo->prepare("SELECT * FROM st_bank_accounts WHERE is_active = 1 AND (store_id = ? OR store_id = 0) ORDER BY id ASC");
        $bStmt->execute([getPosStoreId()]);
        if ($bStmt) {
            foreach ($bStmt->fetchAll(PDO::FETCH_ASSOC) as $ba) {
                $qr = resolveBankQr($ba['qr_code_img'] ?? '', $ba['bank_code'] ?? '');
                if ($qr === '') continue; // no QR uploaded -> nothing to scan, skip
                $cd_bank_accounts[] = [
                    'id'        => (int)$ba['id'],   // ກົງກັບ bank_account_id ທີ່ໜ້າຂາຍເລືອກ → ເດັ້ງ QR ທະນາຄານນັ້ນ
                    'bank_name' => trim($ba['bank_name'] ?? '') ?: strtoupper(trim($ba['bank_code'] ?? '')),
                    'logo'      => resolveBankLogo($ba['bank_logo'] ?? '', $ba['bank_code'] ?? ''),
                    'qr'        => $qr,
                ];
            }
        }
    }
} catch (Throwable $e) {}

// Fall back to the single company-wide QR if no per-bank QR is configured.
if (empty($cd_bank_accounts) && !empty($company['qr_img'])) {
    $cd_bank_accounts[] = [
        'bank_name' => $company['com_name'],
        'logo'      => '',
        'qr'        => $company['qr_img'],
    ];
}

// ສະກຸນເງິນທີ່ສະແດງ — ໃຊ້ທັງແຖບຍອດລວມ (page_body.php) ແລະ JS (display_js.php).
// ຕ້ອງກຳນົດກ່ອນ page_body: ເມື່ອກ່ອນກຳນົດໃນ display_js ທີ່ໂຫຼດທີຫຼັງ → ສັນຍາລັກເງິນຫວ່າງ + warning ໃນ log.
$cd = function_exists('getDisplayCurrency') ? getDisplayCurrency($pdo)
    : ['code' => 'KIP', 'symbol' => kipSymbol(), 'decimals' => 0, 'rate' => 1];

$base_path = '../../';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(getCurrentLang()); ?>" translate="no">
<head>
  <meta charset="utf-8">
  <!-- ບໍ່ໃຫ້ Chrome ຂຶ້ນ "ແປໜ້ານີ້ບໍ?" ທຸກເທື່ອທີ່ເປີດຈໍລູກຄ້າ -->
  <meta name="google" content="notranslate">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>WlaoDev Customer Display - <?php echo htmlspecialchars($company['com_name']); ?></title>
  <link rel="preload" href="<?php echo $base_path; ?>plugins/three.min.js" as="script">
  <link rel="preload" href="<?php echo $base_path; ?>assets/img/logosystem/wlaodev_system_logo.jpg" as="image">

  <link rel="shortcut icon" href="<?php echo htmlspecialchars($company['com_logo']); ?>" type="image/x-icon">
  <link rel="icon" href="<?php echo htmlspecialchars($company['com_logo']); ?>" type="image/png">

  <link rel="stylesheet" href="<?php echo $base_path; ?>plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/local-font.css?v=<?php echo @filemtime(__DIR__ . '/../../assets/css/local-font.css'); ?>">
  <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/main.min.css">
  <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/global-custom/01-base.css?v=<?php echo @filemtime(__DIR__ . '/../../assets/css/global-custom/01-base.css'); ?>">
  <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/global-custom/02-components.css?v=<?php echo @filemtime(__DIR__ . '/../../assets/css/global-custom/02-components.css'); ?>">

<?php require __DIR__ . '/partials/customer_display/styles_layout.php'; /* CSS (1): ໂຄງໜ້າຈໍ ແລະ ຮ້ານ */ ?>
<?php require __DIR__ . '/partials/customer_display/styles_payment.php'; /* CSS (2): QR ຊຳລະ ແລະ ຍອດລວມ */ ?>
<?php require __DIR__ . '/partials/customer_display/styles_theme.php'; /* CSS (3): ໂສມໃໝ່ (ທັບ 1–2) */ ?>
  <script src="<?php echo $base_path; ?>plugins/three.min.js"></script>
</head>
<body>

<div class="display-wrapper">
  <button type="button" class="fullscreen-toggle-btn" id="fullscreenToggleBtn" title="<?php echo htmlspecialchars(t('cd.fullscreen')); ?>">
    <i class="fas fa-expand" id="fullscreenToggleIcon"></i>
  </button>

<?php require __DIR__ . '/partials/customer_display/page_body.php'; /* ສ່ວນສະແດງຜົນ */ ?>
<?php require __DIR__ . '/partials/customer_display/minimart_3d.php'; /* 3D Cartoon Mini-Mart World (ແລ່ນທັນທີແບບ 0ms ກ່ອນ Paint) */ ?>
<script src="<?php echo $base_path; ?>plugins/jquery/jquery.min.js"></script>
<script src="<?php echo $base_path; ?>assets/js/pos-sounds.js?v=<?php echo @filemtime(__DIR__ . '/../../assets/js/pos-sounds.js'); ?>"></script>
<?php /* ສະວິດລະບົບສຽງ (ຕັ້ງຄ່າ › ລະບົບສຽງ) — assets/js/tts-speak.js ກວດກ່ອນເວົ້າ ແລະ ອ່ານຄືນທຸກ 10 ວິນາທີ */
$__ttsCfg = @include __DIR__ . '/../../config/tts_config.php'; $__ttsCfg = is_array($__ttsCfg) ? $__ttsCfg : [];
$__ttsSw = ['enabled' => !array_key_exists('enabled', $__ttsCfg) || !empty($__ttsCfg['enabled'])];
foreach (['pos_total', 'pos_sale', 'pos_stock', 'pos_queue', 'pos_welcome'] as $__k) $__ttsSw[$__k] = !array_key_exists($__k, $__ttsCfg) || !empty($__ttsCfg[$__k]); ?>
<script>window.TTS_CONFIG = <?php echo json_encode($__ttsSw); ?>;</script>
<script src="<?php echo $base_path; ?>assets/js/tts-speak.js?v=<?php echo @filemtime(__DIR__ . '/../../assets/js/tts-speak.js'); ?>"></script>
<?php require __DIR__ . '/partials/customer_display/display_js.php'; /* JS ຂອງຈໍລູກຄ້າ */ ?>

</body>
</html>
