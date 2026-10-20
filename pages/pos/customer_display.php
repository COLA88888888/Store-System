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
require_once __DIR__ . '/../../config/db.php';

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
    : ['code' => 'KIP', 'symbol' => '₭', 'decimals' => 0, 'rate' => 1];

$base_path = '../../';
?>
<!DOCTYPE html>
<html lang="lo" translate="no">
<head>
  <meta charset="utf-8">
  <!-- ບໍ່ໃຫ້ Chrome ຂຶ້ນ "ແປໜ້ານີ້ບໍ?" ທຸກເທື່ອທີ່ເປີດຈໍລູກຄ້າ -->
  <meta name="google" content="notranslate">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Customer Display - <?php echo htmlspecialchars($company['com_name']); ?></title>

  <link rel="shortcut icon" href="<?php echo htmlspecialchars($company['com_logo']); ?>" type="image/x-icon">
  <link rel="icon" href="<?php echo htmlspecialchars($company['com_logo']); ?>" type="image/png">

  <link rel="stylesheet" href="<?php echo $base_path; ?>plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/local-font.css">
  <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/main.min.css">
  <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/global-custom/01-base.css">
  <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/global-custom/02-components.css">

<?php require __DIR__ . '/partials/customer_display/styles_layout.php'; /* CSS (1): ໂຄງໜ້າຈໍ ແລະ ຮ້ານ */ ?>
<?php require __DIR__ . '/partials/customer_display/styles_payment.php'; /* CSS (2): QR ຊຳລະ ແລະ ຍອດລວມ */ ?>
<?php require __DIR__ . '/partials/customer_display/styles_theme.php'; /* CSS (3): ໂສມໃໝ່ (ທັບ 1–2) */ ?>
</head>
<body>

<div class="display-wrapper">
  <button type="button" class="fullscreen-toggle-btn" id="fullscreenToggleBtn" title="ເຕັມຈໍ / ອອກຈາກເຕັມຈໍ">
    <i class="fas fa-expand" id="fullscreenToggleIcon"></i>
  </button>

<?php require __DIR__ . '/partials/customer_display/page_body.php'; /* ສ່ວນສະແດງຜົນ */ ?>
<script src="<?php echo $base_path; ?>plugins/jquery/jquery.min.js"></script>
<?php require __DIR__ . '/partials/customer_display/display_js.php'; /* JS ຂອງຈໍລູກຄ້າ */ ?>

</body>
</html>
