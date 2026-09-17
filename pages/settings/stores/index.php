<?php
// ============================================================
// stores.php - MASTER STORE PROFILE & RECEIPT SETTINGS
// ໜ້າຕັ້ງຄ່າຂໍ້ມູນຮ້ານຕົ້ນແບບ & ໃບບິນ POS (ໃນ settings)
// ============================================================
session_start();
$base_path = '../../../';
require_once __DIR__ . '/../../../config/db.php';

// Check authorization
if (empty($_SESSION['user_id']) || (!hasPermission('stores') && !hasPermission('setup'))) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$message = '';
$message_type = '';

// Handle POST Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Save Store Profile Info
    if ($action === 'save_store_info') {
        $com_name_la         = trim($_POST['com_name_la'] ?? '');
        $com_address         = trim($_POST['com_address'] ?? '');
        $com_tel             = trim($_POST['com_tel'] ?? '');
        $com_email           = trim($_POST['com_email'] ?? '');
        $tax_id              = trim($_POST['tax_id'] ?? '');
        $receipt_footer      = trim($_POST['receipt_footer'] ?? '');
        $tax_type            = trim($_POST['tax_type'] ?? 'none');
        $vat_percent         = ($tax_type === 'none') ? 0.00 : floatval($_POST['vat_percent'] ?? 0);
        $license_start_date  = trim($_POST['license_start_date'] ?? '2026-09-15');
        $license_expire_date = trim($_POST['license_expire_date'] ?? '2027-09-15');

        $branch_id_save = getActiveStoreId($pdo);

        // Daily-resetting queue number on the receipt (coffee-shop style) — one global on/off.
        // Only the main branch renders (and can submit) this toggle.
        if (isMainBranch($pdo, $branch_id_save)) {
            try {
                $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('queue_enabled', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
                    ->execute([!empty($_POST['queue_enabled']) ? '1' : '0']);
            } catch (Throwable $e) {}
        }
        $isMainBranchSave = isMainBranch($pdo, $branch_id_save);

        // Only the main branch can set the shared name/address/phone/tax/etc — sub-branches only
        // ever change their own logo, so this required check doesn't apply to them.
        if ($com_name_la !== '' || !$isMainBranchSave) {
            try {
                // Each branch has its own tbcompanyinfo row, but only for its LOGO — the rest of
                // the profile (name/address/phone/tax/etc) is shared and lives on the main
                // branch's row only, so sub-branches don't need to re-enter it.
                $stmtCurComp = $pdo->prepare("SELECT * FROM tbcompanyinfo WHERE branch_id = ? LIMIT 1");
                $stmtCurComp->execute([$branch_id_save]);
                $curComp = $stmtCurComp->fetch();

                $newLogoName = null;
                if (isset($_FILES['logo_img']) && !empty($_FILES['logo_img']['name']) && $_FILES['logo_img']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['logo_img']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $newLogoName = 'logo_' . time() . '.' . $ext;
                        $targetDir = __DIR__ . '/../../../assets/img/logo/';
                        if (!is_dir($targetDir)) {
                            mkdir($targetDir, 0777, true);
                        }
                        if (move_uploaded_file($_FILES['logo_img']['tmp_name'], $targetDir . $newLogoName)) {
                            $oldLogo = $curComp['img_url'] ?? '';
                            if ($oldLogo && $oldLogo !== 'logo.png' && file_exists($targetDir . $oldLogo)) {
                                @unlink($targetDir . $oldLogo);
                            }
                        }
                    }
                }

                $newQrName = null;
                if (isset($_FILES['qr_img']) && !empty($_FILES['qr_img']['name']) && $_FILES['qr_img']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['qr_img']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $newQrName = 'qr_' . time() . '.' . $ext;
                        $targetDir = __DIR__ . '/../../../assets/img/qr/';
                        if (!is_dir($targetDir)) {
                            mkdir($targetDir, 0777, true);
                        }
                        if (move_uploaded_file($_FILES['qr_img']['tmp_name'], $targetDir . $newQrName)) {
                            $oldQr = $curComp['qr_img'] ?? '';
                            if ($oldQr && file_exists($targetDir . $oldQr)) {
                                @unlink($targetDir . $oldQr);
                            }
                        }
                    }
                }

                if ($isMainBranchSave) {
                    // Main branch: this is the one shared source of truth — save everything.
                    if ($curComp) {
                        $sql = "UPDATE tbcompanyinfo SET com_name_la = ?, com_address = ?, com_tel = ?, com_email = ?, tax_id = ?, barcode = ?, tax_type = ?, vat_percent = ?, license_start_date = ?, license_expire_date = ?";
                        $params = [$com_name_la, $com_address, $com_tel, $com_email, $tax_id, $receipt_footer, $tax_type, $vat_percent, $license_start_date, $license_expire_date];

                        if ($newLogoName) {
                            $sql .= ", img_url = ?";
                            $params[] = $newLogoName;
                        }
                        if ($newQrName) {
                            $sql .= ", qr_img = ?";
                            $params[] = $newQrName;
                        }
                        $sql .= " WHERE Id = ?";
                        $params[] = $curComp['Id'];

                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);
                    } else {
                        $logo = $newLogoName ? $newLogoName : 'logo.png';
                        $qr = $newQrName ? $newQrName : '';
                        $stmt = $pdo->prepare("INSERT INTO tbcompanyinfo (com_name_la, com_address, com_tel, com_email, tax_id, barcode, img_url, qr_img, tax_type, vat_percent, license_start_date, license_expire_date, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$com_name_la, $com_address, $com_tel, $com_email, $tax_id, $receipt_footer, $logo, $qr, $tax_type, $vat_percent, $license_start_date, $license_expire_date, $branch_id_save]);
                    }

                    // tbstore's shared name/address/tel is also only owned by the main branch
                    $stmtStore = $pdo->prepare("UPDATE tbstore SET store_name = ?, address = ?, tel = ? WHERE store_id = ?");
                    $stmtStore->execute([$com_name_la, $com_address, $com_tel, $branch_id_save]);
                } else {
                    // Sub-branch: only the logo is branch-specific — everything else stays on
                    // the main branch's row untouched, even though this form submitted the
                    // (read-only, inherited) values for those fields too.
                    if ($newLogoName) {
                        if ($curComp) {
                            $pdo->prepare("UPDATE tbcompanyinfo SET img_url = ? WHERE Id = ?")->execute([$newLogoName, $curComp['Id']]);
                        } else {
                            $pdo->prepare("INSERT INTO tbcompanyinfo (com_name_la, img_url, branch_id) VALUES ('', ?, ?)")->execute([$newLogoName, $branch_id_save]);
                        }
                    }
                }

                $message = 'ບັນທຶກຂໍ້ມູນຮ້ານຄ້າສຳເລັດແລ້ວ!';
                $message_type = 'success';
                logActivity($pdo, "ແກ້ໄຂຂໍ້ມູນຮ້ານຄ້າ", $com_name_la);
            } catch (Exception $e) {
                $message = 'ຜິດພາດ: ' . $e->getMessage();
                $message_type = 'danger';
            }
        } else {
            $message = 'ກະລຸນາປ້ອນຊື່ຮ້ານຄ້າ!';
            $message_type = 'warning';
        }
    }

    // 2. Save / Update Bank Account & Bank QR Code
    if ($action === 'save_bank_account') {
        $bank_id        = intval($_POST['bank_id'] ?? 0);
        $bank_name      = trim($_POST['bank_name'] ?? '');
        $account_number = trim($_POST['account_number'] ?? '');
        $account_name   = trim($_POST['account_name'] ?? '');
        $bank_code      = trim($_POST['bank_code'] ?? 'BCEL');
        $is_active      = intval($_POST['is_active'] ?? 1);

        $bank_logo = null;
        if (isset($_FILES['bank_logo']) && !empty($_FILES['bank_logo']['name'])) {
            $ext = pathinfo($_FILES['bank_logo']['name'], PATHINFO_EXTENSION);
            $newLogo = 'bank_' . time() . '_' . rand(100,999) . '.' . $ext;
            $uploadDir = __DIR__ . '/../../../assets/img/banks/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            if (move_uploaded_file($_FILES['bank_logo']['tmp_name'], $uploadDir . $newLogo)) {
                $bank_logo = $newLogo;
            }
        }

        $qr_code_img = null;
        if (isset($_FILES['qr_code_img']) && !empty($_FILES['qr_code_img']['name'])) {
            $ext = pathinfo($_FILES['qr_code_img']['name'], PATHINFO_EXTENSION);
            $newQr = 'qr_bank_' . time() . '_' . rand(100,999) . '.' . $ext;
            $uploadDir = __DIR__ . '/../../../assets/img/qr/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            if (move_uploaded_file($_FILES['qr_code_img']['tmp_name'], $uploadDir . $newQr)) {
                $qr_code_img = $newQr;
            }
        }

        try {
            if ($bank_id > 0) {
                $sql = "UPDATE bank_accounts SET bank_name = ?, account_number = ?, account_name = ?, bank_code = ?, is_active = ?";
                $params = [$bank_name, $account_number, $account_name, $bank_code, $is_active];

                if ($bank_logo) {
                    $sql .= ", bank_logo = ?";
                    $params[] = $bank_logo;
                }
                if ($qr_code_img) {
                    $sql .= ", qr_code_img = ?";
                    $params[] = $qr_code_img;
                }
                $sql .= " WHERE id = ?";
                $params[] = $bank_id;

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $message = "ອັບເດດຂໍ້ມູນບັນຊີທະນາຄານ \"$bank_name\" ສຳເລັດແລ້ວ!";
                $message_type = "success";
            } else {
                $stmt = $pdo->prepare("INSERT INTO bank_accounts (bank_name, account_number, account_name, bank_code, bank_logo, qr_code_img, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$bank_name, $account_number, $account_name, $bank_code, $bank_logo, $qr_code_img, $is_active]);
                $message = "ເພີ່ມບັນຊີທະນາຄານ \"$bank_name\" ສຳເລັດແລ້ວ!";
                $message_type = "success";
            }
        } catch (Exception $e) {
            $message = "ຜິດພາດ: " . $e->getMessage();
            $message_type = "danger";
        }
    }

    // 3. Delete Bank Account
    if ($action === 'delete_bank_account') {
        $bank_id = intval($_POST['bank_id'] ?? 0);
        if ($bank_id > 0) {
            try {
                // Fetch image filenames before deleting
                $bankRow = $pdo->prepare("SELECT bank_logo, qr_code_img FROM bank_accounts WHERE id = ?");
                $bankRow->execute([$bank_id]);
                $bankData = $bankRow->fetch(PDO::FETCH_ASSOC);

                $pdo->prepare("DELETE FROM bank_accounts WHERE id = ?")->execute([$bank_id]);

                // Delete image files from filesystem
                if ($bankData) {
                    $bankLogoDir = __DIR__ . '/../../../assets/img/banks/';
                    $qrDir       = __DIR__ . '/../../../assets/img/qr/';

                    if (!empty($bankData['bank_logo']) && file_exists($bankLogoDir . $bankData['bank_logo'])) {
                        @unlink($bankLogoDir . $bankData['bank_logo']);
                    }
                    if (!empty($bankData['qr_code_img']) && file_exists($qrDir . $bankData['qr_code_img'])) {
                        @unlink($qrDir . $bankData['qr_code_img']);
                    }
                }

                $message = "ລົບບັນຊີທະນາຄານສຳເລັດແລ້ວ!";
                $message_type = "success";
            } catch (Exception $e) {
                $message = "ຜິດພາດ: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }
}

// ຊື່ຮ້ານ/ທີ່ຢູ່/ເບີໂທ/ອາກອນ ແມ່ນຂໍ້ມູນຮ່ວມກັນ ດຶງມາຈາກສາຂາຫຼັກສະເໝີ (ບໍ່ຕ້ອງປ້ອນຊ້ຳໃນແຕ່ລະສາຂາ)
$branch_id = getActiveStoreId($pdo);
$mainStoreRow = $pdo->query("SELECT store_id FROM tbstore WHERE is_main = 1 ORDER BY store_id ASC LIMIT 1")->fetch();
$mainStoreId = $mainStoreRow ? intval($mainStoreRow['store_id']) : 1;
$isMainBranchView = isMainBranch($pdo, $branch_id);

$stmtComp = $pdo->prepare("SELECT * FROM tbcompanyinfo WHERE branch_id = ? LIMIT 1");
$stmtComp->execute([$mainStoreId]);
$company = $stmtComp->fetch(PDO::FETCH_ASSOC);

// ...ຍົກເວັ້ນ "ໂລໂກ້" ທີ່ເປັນຂອງສະເພາະສາຂາທີ່ກຳລັງໃຊ້ງານຢູ່ ບໍ່ປົນກັບສາຂາອື່ນ
if (!$isMainBranchView) {
    $stmtBranchLogo = $pdo->prepare("SELECT img_url FROM tbcompanyinfo WHERE branch_id = ? LIMIT 1");
    $stmtBranchLogo->execute([$branch_id]);
    $branchLogoRow = $stmtBranchLogo->fetch(PDO::FETCH_ASSOC);
    if (!is_array($company)) $company = [];
    $company['img_url'] = $branchLogoRow['img_url'] ?? '';
}

if (!$company) {
    $company = [];
}

// Determine logo path — verify file exists on disk; fall back to default image
$logoImg  = !empty($company['img_url']) ? $company['img_url'] : '';
$logoDir  = __DIR__ . '/../../../assets/img/logo/';
if (!is_dir($logoDir)) {
    mkdir($logoDir, 0777, true);
}
if ($logoImg && file_exists($logoDir . $logoImg)) {
    $logoPath = $base_path . 'assets/img/logo/' . $logoImg;
} else {
    $logoPath = $base_path . 'assets/img/image.jpg';
}

require_once __DIR__ . '/../../../layouts/header.php';
?>

<link rel="stylesheet" href="../../../themes/settings.css?v=<?php echo file_exists(__DIR__ . '/../../../themes/settings.css') ? filemtime(__DIR__ . '/../../../themes/settings.css') : time(); ?>">

<div class="container-fluid p-4">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h5 class="m-0 font-weight-bold" style="color: #1e293b; font-size: 1.15rem;">
        <i class="fas fa-store text-primary mr-2"></i> <?php echo htmlspecialchars(t('stores.page_title', 'ຂໍ້ມູນຮ້ານຄ້າ')); ?>
      </h5>
    </div>
  </div>

  <?php if ($message !== ''): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
          icon: '<?php echo $message_type === "success" ? "success" : "error"; ?>',
          title: '<?php echo $message_type === "success" ? "ສຳເລັດ" : "ແຈ້ງເຕືອນ"; ?>',
          text: <?php echo json_encode($message); ?>,
          confirmButtonColor: '#2563eb'
        });
      });
    </script>
  <?php endif; ?>

  <div class="row">
    <!-- Store Information Edit Form -->
    <div class="col-lg-12 mb-4">
      <div class="store-card p-4 p-md-5">
        <h6 class="font-weight-bold text-dark mb-4 pb-2 border-bottom d-flex align-items-center">
          <i class="fas fa-edit text-primary mr-2"></i> <?php echo htmlspecialchars(t('stores.section_edit_info', 'ແກ້ໄຂຂໍ້ມູນຮ້ານ / ບໍລິສັດ')); ?>
        </h6>

        <form id="storeInfoForm" action="" method="POST" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="action" value="save_store_info">

          <!-- Logo Upload Section -->
          <div class="row align-items-center mb-4 pb-3 border-bottom">
            <!-- Store Logo -->
            <div class="col-md-12">
              <div class="d-flex align-items-center">
                <div class="position-relative mr-3" style="width: 100px; height: 100px; flex-shrink: 0;">
                  <img id="logo_preview" src="<?php echo htmlspecialchars($logoPath); ?>" 
                       style="width: 100px; height: 100px; object-fit: contain; border-radius: 50%; border: 2px solid #93c5fd; background: #f8fafc; padding: 4px; cursor: pointer;"
                       onclick="document.getElementById('logo_input').click();"
                       onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\' viewBox=\'0 0 100 100\'><circle cx=\'50\' cy=\'50\' r=\'50\' fill=\'%23e2e8f0\'/><text x=\'50\' y=\'58\' text-anchor=\'middle\' font-size=\'38\' fill=\'%2394a3b8\'>&#128722;</text></svg>'">
                  <button type="button" class="btn btn-sm btn-primary position-absolute shadow-sm" 
                          style="right: -2px; bottom: -2px; border-radius: 50%; width: 32px; height: 32px; border: 2px solid #fff;"
                          onclick="document.getElementById('logo_input').click();"
                          title="<?php echo htmlspecialchars(t('stores.logo_change_title', 'ປ່ຽນຮູບໂລໂກ້')); ?>">
                    <i class="fas fa-camera"></i>
                  </button>
                </div>
                <div>
                  <h6 class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('stores.logo_heading', 'ໂລໂກ້ຮ້ານຄ້າ')); ?></h6>
                  <small class="text-muted d-block mb-2"><?php echo htmlspecialchars(t('stores.logo_help', 'ຮອງຮັບຮູບ JPG, PNG, WEBP (500x500px)')); ?></small>
                  <button type="button" class="btn btn-sm btn-outline-primary font-weight-bold" style="border-radius: 6px;" onclick="document.getElementById('logo_input').click();">
                    <i class="fas fa-upload mr-1"></i> <?php echo htmlspecialchars(t('stores.logo_choose_btn', 'ເລືອກຮູບໂລໂກ້')); ?>
                  </button>
                  <input type="file" name="logo_img" id="logo_input" accept="image/*" style="display: none;" onchange="previewStoreLogo(this)">
                </div>
              </div>
            </div>
          </div>

          <?php
            // Everything below the logo (name/address/phone/tax/etc) is shared across all
            // branches and lives on the main branch's row — only the main branch can edit it.
            $sharedReadonly = $isMainBranchView ? '' : 'readonly';
            $sharedDisabled = $isMainBranchView ? '' : 'disabled';
            $sharedFieldClass = $isMainBranchView ? '' : 'bg-light';
          ?>

          <?php if (!$isMainBranchView): ?>
            <div class="alert alert-info border-0 mb-4" style="border-radius: 10px; font-size: 0.88rem;">
              <i class="fas fa-info-circle mr-1"></i> <?php echo htmlspecialchars(t('stores.shared_info_note', 'ຊື່ຮ້ານ, ທີ່ຢູ່, ເບີໂທ ແລະ ຂໍ້ມູນອາກອນ ແມ່ນໃຊ້ຮ່ວມກັນຈາກສາຂາຫຼັກ ແກ້ໄຂໄດ້ສະເພາະຈາກສາຂາຫຼັກເທົ່ານັ້ນ — ມີແຕ່ "ໂລໂກ້" ດ້ານເທິງນີ້ທີ່ປ່ຽນສະເພາະສາຂານີ້ໄດ້.')); ?>
            </div>
          <?php endif; ?>

          <!-- Store Name & Phone -->
          <div class="row">
            <div class="col-md-7 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('stores.field_name', 'ຊື່ຮ້ານຄ້າ / ບໍລິສັດ:')); ?> <span class="text-danger">*</span></label>
              <input type="text" name="com_name_la" id="input_store_name" class="form-control font-weight-bold text-primary <?php echo $sharedFieldClass; ?>"
                     value="<?php echo htmlspecialchars($company['com_name_la'] ?? ''); ?>"
                     placeholder="<?php echo htmlspecialchars(t('stores.field_name_ph', 'ປ້ອນຊື່ຮ້ານຄ້າ...')); ?>" <?php echo $sharedReadonly; ?> required>
            </div>
            <div class="col-md-5 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('stores.field_tel', 'ເບີໂທລະສັບຕິດຕໍ່:')); ?> <span class="text-danger">*</span></label>
              <input type="text" name="com_tel" id="input_store_tel" class="form-control <?php echo $sharedFieldClass; ?>"
                     value="<?php echo htmlspecialchars($company['com_tel'] ?? ''); ?>"
                     placeholder="020 xxxxxxxx" <?php echo $sharedReadonly; ?> required>
            </div>
          </div>

          <!-- Store Address -->
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('stores.field_address', 'ທີ່ຢູ່ຮ້ານຄ້າ (ສະແດງໃນໃບບິນ):')); ?></label>
            <textarea name="com_address" id="input_store_address" rows="2" class="form-control <?php echo $sharedFieldClass; ?>"
                      placeholder="<?php echo htmlspecialchars(t('stores.field_address_ph', 'ບ້ານ, ເມືອງ, ແຂວງ...')); ?>" <?php echo $sharedReadonly; ?>><?php echo htmlspecialchars($company['com_address'] ?? ''); ?></textarea>
          </div>

          <!-- Taxpayer ID / Tax Number -->
          <div class="form-group mb-3" id="tax_id_wrap">
            <label class="font-weight-bold text-dark small mb-1">
              <i class="fas fa-id-card text-primary mr-1"></i> <?php echo htmlspecialchars(t('stores.field_tax_id', 'ເລກປະຈຳຕົວຜູ້ເສຍອາກອນ:')); ?>
            </label>
            <input type="text" name="tax_id" class="form-control font-weight-bold <?php echo $sharedFieldClass; ?>"
                   value="<?php echo htmlspecialchars($company['tax_id'] ?? ''); ?>"
                   placeholder="<?php echo htmlspecialchars(t('stores.field_tax_id_ph', 'ປ້ອນເລກປະຈຳຕົວຜູ້ເສຍອາກອນ (ຖ້າບໍ່ປ້ອນ ຈະບໍ່ສະແດງໃນໃບບິນ)...')); ?>" <?php echo $sharedReadonly; ?>>
            <small class="text-muted"><i class="fas fa-info-circle mr-1"></i> <?php echo htmlspecialchars(t('stores.tax_id_note', 'ໝາຍເຫດ: ຖ້າປ້ອນເລກປະຈຳຕົວຜູ້ເສຍອາກອນ ລະບົບຈະສະແດງໃນໃບບິນອັດໂນມັດ, ຖ້າປະຫວ່າງໄວ້ ຈະບໍ່ສະແດງໃນໃບບິນ.')); ?></small>
          </div>

          <!-- Tax / VAT Configuration Section -->
          <div class="form-group mb-4 p-3 bg-light rounded border">
            <label class="font-weight-bold text-dark small mb-2 d-block">
              <i class="fas fa-percent text-danger mr-1"></i> <?php echo htmlspecialchars(t('stores.vat_section_label', 'ການຕັ້ງຄ່າອາກອນມູນຄ່າເພີ່ມ (ອມພ):')); ?>
            </label>
            <div class="row align-items-center">
              <div class="col-md-7 mb-2 mb-md-0">
                <div class="custom-control custom-radio custom-control-inline">
                  <input type="radio" id="tax_inc" name="tax_type" value="inclusive" class="custom-control-input" <?php echo (($company['tax_type'] ?? '') === 'inclusive') ? 'checked' : ''; ?> <?php echo $sharedDisabled; ?>>
                  <label class="custom-control-label font-weight-bold text-dark small" for="tax_inc">
                    <?php echo htmlspecialchars(t('stores.tax_inclusive', 'ອາກອນພາຍໃນ')); ?> <span class="text-muted"><?php echo htmlspecialchars(t('stores.tax_inclusive_hint', '(Inclusive - ລາຄາລວມ ອມພ ແລ້ວ)')); ?></span>
                  </label>
                </div>
                <div class="custom-control custom-radio custom-control-inline mt-1">
                  <input type="radio" id="tax_exc" name="tax_type" value="exclusive" class="custom-control-input" <?php echo (($company['tax_type'] ?? '') === 'exclusive') ? 'checked' : ''; ?> <?php echo $sharedDisabled; ?>>
                  <label class="custom-control-label font-weight-bold text-dark small" for="tax_exc">
                    <?php echo htmlspecialchars(t('stores.tax_exclusive', 'ອາກອນພາຍນອກ')); ?> <span class="text-muted"><?php echo htmlspecialchars(t('stores.tax_exclusive_hint', '(Exclusive - ບວກເພີ່ມ ອມພ %)')); ?></span>
                  </label>
                </div>
                <div class="custom-control custom-radio custom-control-inline mt-1">
                  <input type="radio" id="tax_none" name="tax_type" value="none" class="custom-control-input" <?php echo (($company['tax_type'] ?? '') === 'none') ? 'checked' : ''; ?> <?php echo $sharedDisabled; ?>>
                  <label class="custom-control-label font-weight-bold text-muted small" for="tax_none">
                    <?php echo htmlspecialchars(t('stores.tax_none', 'ບໍ່ມີອາກອນມູນຄ່າເພີ່ມ (0%)')); ?>
                  </label>
                </div>
              </div>
              <div class="col-md-5" id="vat_percent_wrap">
                <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('stores.vat_rate', 'ອັດຕາ ອມພ (%):')); ?></label>
                <div class="input-group">
                  <input type="number" step="any" min="0" max="100" name="vat_percent" class="form-control font-weight-bold text-primary <?php echo $sharedFieldClass; ?>"
                         value="<?php echo htmlspecialchars($company['vat_percent'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars(t('stores.vat_rate_ph', 'ກະລຸນາປ້ອນອັດຕາ ອມພ %')); ?>" <?php echo $sharedReadonly; ?>>
                  <div class="input-group-append">
                    <span class="input-group-text font-weight-bold">%</span>
                  </div>
                </div>
              </div>
            </div>
          </div>



          <!-- Receipt Footer Message -->
          <div class="form-group mb-4">
            <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('stores.field_footer', 'ຂໍ້ຄວາມທ້າຍໃບບິນ:')); ?></label>
            <input type="text" name="receipt_footer" id="input_store_footer" class="form-control <?php echo $sharedFieldClass; ?>"
                   value="<?php echo htmlspecialchars($company['barcode'] ?? ''); ?>"
                   placeholder="<?php echo htmlspecialchars(t('stores.field_footer_ph', 'ເຊັ່ນ: ຂອບໃຈທີ່ມາອຸດໜູນ, ສິນຄ້າຊື້ແລ້ວບໍ່ຮັບປ່ຽນຄືນ...')); ?>" <?php echo $sharedReadonly; ?>>
          </div>

          <!-- Queue number on receipt (coffee-shop style) -->
          <?php if ($isMainBranchView): ?>
          <div class="form-group mb-4">
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input" id="queue_enabled" name="queue_enabled" value="1"
                     <?php echo (getSetting($pdo, 'queue_enabled', '0') === '1') ? 'checked' : ''; ?>>
              <label class="custom-control-label font-weight-bold text-dark small" for="queue_enabled">
                <?php echo htmlspecialchars(t('stores.field_queue_enabled', 'ສະແດງເລກຄິວເທິງໃບບິນ (ຮ້ານກາເຟ / ຮ້ານທີ່ມີຄິວ)')); ?>
              </label>
            </div>
            <small class="text-muted"><?php echo htmlspecialchars(t('stores.field_queue_hint', 'ເລກຄິວເລີ່ມ 1 ໃໝ່ທຸກມື້ ພິມໂຕໃຫຍ່ຢູ່ເທິງໂລໂກ້. ຮ້ານທົ່ວໄປປິດໄວ້.')); ?></small>
          </div>
          <?php endif; ?>

          <!-- Submit Button (Aligned to the Right) -->
          <div class="pt-3 border-top text-right d-flex justify-content-end">
            <button type="submit" class="btn btn-primary px-4 py-2 font-weight-bold shadow-sm" style="border-radius: 8px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
              <i class="fas fa-save mr-2"></i> <?php echo htmlspecialchars($isMainBranchView ? t('stores.btn_save_info', 'ບັນທຶກຂໍ້ມູນຮ້ານຄ້າ') : t('stores.btn_save_logo', 'ບັນທຶກໂລໂກ້')); ?>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function previewStoreLogo(input) {
  if (input.files && input.files[0]) {
    var file = input.files[0];
    // Validate file type
    var allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!allowed.includes(file.type)) {
      Swal.fire({
        icon: 'error',
        title: 'ໄຟລ໌ບໍ່ຖືກຕ້ອງ',
        text: 'ກະລຸນາເລືອກຮູບທີ່ເປັນ JPG, PNG ຫຼື WEBP ເທົ່ານັ້ນ!',
        confirmButtonColor: '#2563eb'
      });
      input.value = '';
      return;
    }
    var reader = new FileReader();
    reader.onload = function(e) {
      var img = document.getElementById('logo_preview');
      img.style.opacity = '0.3';
      img.src = e.target.result;
      img.onload = function() {
        img.style.transition = 'opacity 0.4s ease';
        img.style.opacity = '1';
      };
    };
    reader.readAsDataURL(file);
  }
}

function previewStoreQr(input) {
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('qr_preview').src = e.target.result;
    }
    reader.readAsDataURL(input.files[0]);
  }
}

function updateReceiptPreview() {
  var name = document.getElementById('input_store_name').value || 'ຊື່ຮ້ານຄ້າ';
  var tel = document.getElementById('input_store_tel').value || '-';
  var address = document.getElementById('input_store_address').value || '-';
  var footer = document.getElementById('input_store_footer').value || 'ຂອບໃຈທີ່ມາອຸດໜູນ!';

  document.getElementById('receipt_name').textContent = name;
  document.getElementById('receipt_tel').textContent = 'ໂທ: ' + tel;
  document.getElementById('receipt_address').textContent = address;
  document.getElementById('receipt_footer').textContent = footer;
}

function handleTaxTypeChange() {
  var taxType = $('input[name="tax_type"]:checked').val();
  var $vatWrap = $('#vat_percent_wrap');
  var $taxIdWrap = $('#tax_id_wrap');
  var $vatInput = $('input[name="vat_percent"]');
  if (taxType === 'none') {
    $vatInput.val('0').prop('readonly', true);
    if ($vatWrap.length) $vatWrap.hide();
    if ($taxIdWrap.length) $taxIdWrap.hide();
  } else {
    $vatInput.prop('readonly', false);
    if ($vatWrap.length) $vatWrap.show();
    if ($taxIdWrap.length) $taxIdWrap.show();
  }
}

$(document).ready(function() {
  $('input[name="tax_type"]').on('change', handleTaxTypeChange);
  handleTaxTypeChange();

  // SweetAlert Validation
  $('#storeInfoForm').on('submit', function(e) {
    var storeName = $.trim($('#input_store_name').val());
    var storeTel  = $.trim($('#input_store_tel').val());

    if (storeName === '') {
      e.preventDefault();
      Swal.fire({
        icon: 'warning',
        title: 'ກະລຸນາປ້ອນຂໍ້ມູນ',
        text: 'ກະລຸນາປ້ອນ "ຊື່ຮ້ານຄ້າ / ບໍລິສັດ" ກ່ອນບັນທຶກ!',
        confirmButtonColor: '#2563eb',
        confirmButtonText: 'ຕົກລົງ'
      }).then(function() {
        $('#input_store_name').focus();
      });
      return false;
    }

    if (storeTel === '') {
      e.preventDefault();
      Swal.fire({
        icon: 'warning',
        title: 'ກະລຸນາປ້ອນຂໍ້ມູນ',
        text: 'ກະລຸນາປ້ອນ "ເບີໂທລະສັບຕິດຕໍ່" ກ່ອນບັນທຶກ!',
        confirmButtonColor: '#2563eb',
        confirmButtonText: 'ຕົກລົງ'
      }).then(function() {
        $('#input_store_tel').focus();
      });
      return false;
    }
  });
});
</script>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
