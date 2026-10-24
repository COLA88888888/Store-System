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
        $com_name_la    = trim($_POST['com_name_la'] ?? '');
        $com_address    = trim($_POST['com_address'] ?? '');
        $com_tel        = trim($_POST['com_tel'] ?? '');
        $com_email      = trim($_POST['com_email'] ?? '');
        $receipt_footer = trim($_POST['receipt_footer'] ?? '');
        $tax_type       = trim($_POST['tax_type'] ?? 'none');
        $vat_percent    = ($tax_type === 'none') ? 0.00 : floatval($_POST['vat_percent'] ?? 0);

        if ($com_name_la !== '') {
            try {
                // Get existing images for cleanup
                $curComp = $pdo->query("SELECT img_url, qr_img FROM st_tbcompanyinfo WHERE Id = 1")->fetch();

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

                // Check if record exists in tbcompanyinfo
                $cnt = $pdo->query("SELECT COUNT(*) FROM st_tbcompanyinfo")->fetchColumn();
                if ($cnt > 0) {
                    $sql = "UPDATE st_tbcompanyinfo SET com_name_la = ?, com_address = ?, com_tel = ?, com_email = ?, barcode = ?, tax_type = ?, vat_percent = ?";
                    $params = [$com_name_la, $com_address, $com_tel, $com_email, $receipt_footer, $tax_type, $vat_percent];

                    if ($newLogoName) {
                        $sql .= ", img_url = ?";
                        $params[] = $newLogoName;
                    }
                    if ($newQrName) {
                        $sql .= ", qr_img = ?";
                        $params[] = $newQrName;
                    }
                    $hasIdCol = $pdo->query("SHOW COLUMNS FROM st_tbcompanyinfo LIKE 'Id'")->fetch();
                    $pkCol = $hasIdCol ? 'Id' : ($pdo->query("SHOW COLUMNS FROM st_tbcompanyinfo LIKE 'com_id'")->fetch() ? 'com_id' : '1');
                    $sql .= " WHERE {$pkCol} = 1";

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                } else {
                    $logo = $newLogoName ? $newLogoName : 'logo.png';
                    $qr = $newQrName ? $newQrName : '';
                    $stmt = $pdo->prepare("INSERT INTO st_tbcompanyinfo (Id, com_name_la, com_address, com_tel, com_email, barcode, img_url, qr_img, tax_type, vat_percent, branch_id) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
                    $stmt->execute([$com_name_la, $com_address, $com_tel, $com_email, $receipt_footer, $logo, $qr, $tax_type, $vat_percent]);
                }

                // Sync with main store in tbstore if exists
                $storeCnt = $pdo->query("SELECT COUNT(*) FROM st_tbstore WHERE store_id = 1 OR is_main = 1")->fetchColumn();
                if ($storeCnt > 0) {
                    $stmtStore = $pdo->prepare("UPDATE st_tbstore SET store_name = ?, address = ?, tel = ? WHERE store_id = 1 OR is_main = 1");
                    $stmtStore->execute([$com_name_la, $com_address, $com_tel]);
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
                $sql = "UPDATE st_bank_accounts SET bank_name = ?, account_number = ?, account_name = ?, bank_code = ?, is_active = ?";
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
                $stmt = $pdo->prepare("INSERT INTO st_bank_accounts (bank_name, account_number, account_name, bank_code, bank_logo, qr_code_img, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
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
                $stmt = $pdo->prepare("DELETE FROM st_bank_accounts WHERE id = ?");
                $stmt->execute([$bank_id]);
                $message = "ລົບບັນຊີທະນາຄານສຳເລັດແລ້ວ!";
                $message_type = "success";
            } catch (Exception $e) {
                $message = "ຜິດພາດ: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }
}

// ດຶງຂໍ້ມູນຮ້ານຄ້າຕາມ branch_id ຂອງ Session (ຮອງຮັບຫຼາຍສາຂາ)
$branch_id = $_SESSION['branch_id'] ?? 1;
$stmtComp = $pdo->prepare("SELECT * FROM st_tbcompanyinfo WHERE branch_id = ? OR Id = 1 ORDER BY branch_id DESC, Id ASC LIMIT 1");
$stmtComp->execute([$branch_id]);
$company = $stmtComp->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    $company = [];
}

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
        <i class="fas fa-store text-primary mr-2"></i> ຂໍ້ມູນຮ້ານຄ້າ / ບໍລິສັດ
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
          <i class="fas fa-edit text-primary mr-2"></i> ແກ້ໄຂຂໍ້ມູນຮ້ານ
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
                       onerror="this.src='<?php echo $base_path; ?>assets/img/logo/logo.png';">
                  <button type="button" class="btn btn-sm btn-primary position-absolute shadow-sm" 
                          style="right: -2px; bottom: -2px; border-radius: 50%; width: 32px; height: 32px; border: 2px solid #fff;"
                          onclick="document.getElementById('logo_input').click();"
                          title="ປ່ຽນຮູບໂລໂກ້">
                    <i class="fas fa-camera"></i>
                  </button>
                </div>
                <div>
                  <h6 class="font-weight-bold text-dark mb-1">ໂລໂກ້ຮ້ານຄ້າ</h6>
                  <small class="text-muted d-block mb-2">ຮອງຮັບຮູບ JPG, PNG, WEBP (500x500px)</small>
                  <button type="button" class="btn btn-sm btn-outline-primary font-weight-bold" style="border-radius: 6px;" onclick="document.getElementById('logo_input').click();">
                    <i class="fas fa-upload mr-1"></i> ເລືອກຮູບໂລໂກ້
                  </button>
                  <input type="file" name="logo_img" id="logo_input" accept="image/*" style="display: none;" onchange="previewStoreLogo(this)">
                </div>
              </div>
            </div>
          </div>

          <!-- Store Name & Phone -->
          <div class="row">
            <div class="col-md-7 mb-3">
              <label class="font-weight-bold text-dark small mb-1">ຊື່ຮ້ານຄ້າ / ບໍລິສັດ: <span class="text-danger">*</span></label>
              <input type="text" name="com_name_la" id="input_store_name" class="form-control font-weight-bold text-primary" 
                     value="<?php echo htmlspecialchars($company['com_name_la'] ?? ''); ?>" 
                     placeholder="ປ້ອນຊື່ຮ້ານຄ້າ..." required>
            </div>
            <div class="col-md-5 mb-3">
              <label class="font-weight-bold text-dark small mb-1">ເບີໂທລະສັບຕິດຕໍ່: <span class="text-danger">*</span></label>
              <input type="text" name="com_tel" id="input_store_tel" class="form-control" 
                     value="<?php echo htmlspecialchars($company['com_tel'] ?? ''); ?>" 
                     placeholder="020 xxxxxxxx" required>
            </div>
          </div>

          <!-- Store Address -->
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark small mb-1">ທີ່ຢູ່ຮ້ານຄ້າ (ສະແດງໃນໃບບິນ):</label>
            <textarea name="com_address" id="input_store_address" rows="2" class="form-control" 
                      placeholder="ບ້ານ, ເມືອງ, ແຂວງ..."><?php echo htmlspecialchars($company['com_address'] ?? ''); ?></textarea>
          </div>

          <!-- Email / Contact -->
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark small mb-1">ອີເມວ ຫຼື ຊ່ອງທາງຕິດຕໍ່ອື່ນໆ:</label>
            <input type="text" name="com_email" class="form-control" 
                   value="<?php echo htmlspecialchars($company['com_email'] ?? ''); ?>" 
                   placeholder="example@gmail.com ຫຼື Facebook Page...">
          </div>

          <!-- Tax / VAT Configuration Section -->
          <div class="form-group mb-4 p-3 bg-light rounded border">
            <label class="font-weight-bold text-dark small mb-2 d-block">
              <i class="fas fa-percent text-danger mr-1"></i> ການຕັ້ງຄ່າອາກອນມູນຄ່າເພີ່ມ (ອມພ):
            </label>
            <div class="row align-items-center">
              <div class="col-md-7 mb-2 mb-md-0">
                <div class="custom-control custom-radio custom-control-inline">
                  <input type="radio" id="tax_inc" name="tax_type" value="inclusive" class="custom-control-input" <?php echo (($company['tax_type'] ?? '') === 'inclusive') ? 'checked' : ''; ?>>
                  <label class="custom-control-label font-weight-bold text-dark small" for="tax_inc">
                    ອາກອນພາຍໃນ <span class="text-muted">(Inclusive - ລາຄາລວມ ອມພ ແລ້ວ)</span>
                  </label>
                </div>
                <div class="custom-control custom-radio custom-control-inline mt-1">
                  <input type="radio" id="tax_exc" name="tax_type" value="exclusive" class="custom-control-input" <?php echo (($company['tax_type'] ?? '') === 'exclusive') ? 'checked' : ''; ?>>
                  <label class="custom-control-label font-weight-bold text-dark small" for="tax_exc">
                    ອາກອນພາຍນອກ <span class="text-muted">(Exclusive - ບວກເພີ່ມ ອມພ %)</span>
                  </label>
                </div>
                <div class="custom-control custom-radio custom-control-inline mt-1">
                  <input type="radio" id="tax_none" name="tax_type" value="none" class="custom-control-input" <?php echo (($company['tax_type'] ?? '') === 'none') ? 'checked' : ''; ?>>
                  <label class="custom-control-label font-weight-bold text-muted small" for="tax_none">
                    ບໍ່ມີອາກອນມູນຄ່າເພີ່ມ (0%)
                  </label>
                </div>
              </div>
              <div class="col-md-5" id="vat_percent_wrap">
                <label class="font-weight-bold text-dark small mb-1">ອັດຕາ ອມພ (%):</label>
                <div class="input-group">
                  <input type="number" step="any" min="0" max="100" name="vat_percent" class="form-control font-weight-bold text-primary" 
                         value="<?php echo htmlspecialchars($company['vat_percent'] ?? ''); ?>" placeholder="0.00">
                  <div class="input-group-append">
                    <span class="input-group-text font-weight-bold">%</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Receipt Footer Message -->
          <div class="form-group mb-4">
            <label class="font-weight-bold text-dark small mb-1">ຂໍ້ຄວາມທ້າຍໃບບິນ:</label>
            <input type="text" name="receipt_footer" id="input_store_footer" class="form-control" 
                   value="<?php echo htmlspecialchars($company['barcode'] ?? 'ຂອບໃຈທີ່ມາອຸດໜູນ, ໂອກາດໜ້າເຊີນໃໝ່!'); ?>" 
                   placeholder="ເຊັ່ນ: ຂອບໃຈທີ່ມາອຸດໜູນ, ສິນຄ້າຊື້ແລ້ວບໍ່ຮັບປ່ຽນຄືນ...">
          </div>

          <!-- Submit Button (Aligned to the Right) -->
          <div class="pt-3 border-top text-right d-flex justify-content-end">
            <button type="submit" class="btn btn-primary px-4 py-2 font-weight-bold shadow-sm" style="border-radius: 6px;">
              <i class="fas fa-save mr-2"></i> ບັນທຶກຂໍ້ມູນຮ້ານຄ້າ
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
    var reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('logo_preview').src = e.target.result;
    }
    reader.readAsDataURL(input.files[0]);
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
