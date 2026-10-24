<?php
/**
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * ໜ້າຕັ້ງຄ່າຂໍ້ມູນຮ້ານຂອງແຕ່ລະສາຂາ: ໂລໂກ້, QR, ຊື່/ທີ່ຢູ່/ເບີໂທ, ອາກອນ, ຂໍ້ຄວາມທ້າຍບິນ ແລະ ໝາຍເລກຄິວ.
 *   - partials/actions.php : ການກະທຳ POST: ບັນທຶກຂໍ້ມູນຮ້ານ
 *   - partials/page_body.php : ຟອມຂໍ້ມູນຮ້ານ
 */
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

// Save an uploaded logo / QR image. The type is read from the file's actual content, not its
// name: a JPEG saved from the web on Windows is often named ".jfif", which the browser accepts
// (it checks the real type) but the old extension check here rejected silently — so the page
// said "saved" while the old logo stayed. Returns the new file name, or null with $error set.
function saveStoreImageUpload($field, $prefix, $targetDir, &$error) {
    $error = '';
    if (!isset($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || empty($_FILES[$field]['name'])) {
        return null; // nothing chosen — keep the current image
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        $error = ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE)
            ? 'ຮູບໃຫຍ່ເກີນກຳນົດ' : ('ອັບໂຫຼດຮູບບໍ່ສຳເລັດ (code ' . (int)$f['error'] . ')');
        return null;
    }
    $info = @getimagesize($f['tmp_name']);
    $extByType = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
    if (!$info || !isset($extByType[$info[2]])) {
        $error = 'ໄຟລ໌ບໍ່ແມ່ນຮູບ JPG / PNG / WEBP / GIF';
        return null;
    }
    if (!is_dir($targetDir)) @mkdir($targetDir, 0777, true);
    // time() + random suffix: two saves in the same second must not reuse (or overwrite) a name
    $name = $prefix . time() . '_' . mt_rand(100, 999) . '.' . $extByType[$info[2]];
    if (!@move_uploaded_file($f['tmp_name'], $targetDir . $name)) {
        $error = 'ບັນທຶກຮູບລົງເຊີເວີບໍ່ໄດ້ — ກວດສອບສິດໂຟນເດີ';
        return null;
    }
    return $name;
}

// Handle POST Requests
// ການກະທຳ POST: ບັນທຶກຂໍ້ມູນຮ້ານ
require __DIR__ . '/partials/actions.php';
// Each branch owns its full profile — this settings form reads and edits THIS branch's own
// tbcompanyinfo row, never another branch's data.
// Name/address/phone specifically: the main branch already enters these once, for every
// branch, in Branch Management (tbstore.store_name/address/tel) when the branch is created —
// so a branch that hasn't customised its own tbcompanyinfo row yet pre-fills FROM st_tbstore
// instead of showing blank fields the shop would otherwise have to type a second time.
$branch_id = getActiveStoreId($pdo);
$mainStoreRow = $pdo->query("SELECT store_id FROM st_tbstore WHERE is_main = 1 ORDER BY store_id ASC LIMIT 1")->fetch();
$mainStoreId = $mainStoreRow ? intval($mainStoreRow['store_id']) : 1;
$isMainBranchView = isMainBranch($pdo, $branch_id);

$stmtComp = $pdo->prepare("SELECT * FROM st_tbcompanyinfo WHERE branch_id = ? LIMIT 1");
$stmtComp->execute([$branch_id]);
$company = $stmtComp->fetch(PDO::FETCH_ASSOC);

if (empty($company['com_name_la'])) {
    $stmtOwnStore = $pdo->prepare("SELECT store_name, address, tel FROM st_tbstore WHERE store_id = ? LIMIT 1");
    $stmtOwnStore->execute([$branch_id]);
    $ownStoreRow = $stmtOwnStore->fetch(PDO::FETCH_ASSOC);
    if ($ownStoreRow) {
        if (!is_array($company)) $company = [];
        $company['com_name_la'] = $ownStoreRow['store_name'];
        if (empty($company['com_address'])) $company['com_address'] = $ownStoreRow['address'];
        if (empty($company['com_tel']))     $company['com_tel']     = $ownStoreRow['tel'];
    }
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
$hasCustomLogo = $logoImg && file_exists($logoDir . $logoImg);   // ສະແດງປຸ່ມ ✕ ລຶບໂລໂກ້ (partials/page_body.php)
$defaultLogoPath = $base_path . 'assets/img/image.jpg';
if ($hasCustomLogo) {
    // ?v=mtime: the browser can never show a cached copy of an older logo
    $logoPath = $base_path . 'assets/img/logo/' . $logoImg . '?v=' . @filemtime($logoDir . $logoImg);
} else {
    $logoPath = $base_path . 'assets/img/image.jpg';
}

require_once __DIR__ . '/../../../layouts/header.php';
?>

<link rel="stylesheet" href="../../../themes/settings.css?v=<?php echo file_exists(__DIR__ . '/../../../themes/settings.css') ? filemtime(__DIR__ . '/../../../themes/settings.css') : time(); ?>">

<?php require __DIR__ . '/partials/page_body.php'; /* ຟອມຂໍ້ມູນຮ້ານ */ ?>
<script>
function previewStoreLogo(input) {
  if (input.files && input.files[0]) {
    var file = input.files[0];
    // Validate file type
    var allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!allowed.includes(file.type)) {
      Swal.fire({
        icon: 'error',
        title: 'ໄຟລ໌ບໍ່ຖືກຕ້ອງ',
        text: 'ກະລຸນາເລືອກຮູບທີ່ເປັນ JPG, PNG, WEBP ຫຼື GIF ເທົ່ານັ້ນ!',
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
    // ເລືອກຮູບໃໝ່ = ບໍ່ລຶບແລ້ວ, ສະແດງປຸ່ມ ✕ ໃຫ້ເອົາອອກໄດ້
    document.getElementById('remove_logo').value = '0';
    document.getElementById('logo_remove_btn').style.setProperty('display', 'flex', 'important');
  }
}

// ປຸ່ມ ✕ ສີແດງເທິງຮູບ: ເອົາໂລໂກ້ອອກ (ລຶບແທ້ຕອນກົດບັນທຶກ — partials/actions.php)
function removeStoreLogo() {
  Swal.fire({
    icon: 'warning',
    title: 'ລຶບຮູບໂລໂກ້?',
    text: 'ໂລໂກ້ຈະຖືກເອົາອອກ ເມື່ອກົດບັນທຶກ',
    showCancelButton: true,
    confirmButtonText: 'ຕົກລົງ',
    cancelButtonText: 'ຍົກເລີກ',
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#64748b'
  }).then(function(r) {
    if (!r.isConfirmed) return;
    document.getElementById('logo_input').value = '';
    document.getElementById('remove_logo').value = '1';
    document.getElementById('logo_preview').src = <?php echo json_encode($defaultLogoPath); ?>;
    document.getElementById('logo_remove_btn').style.setProperty('display', 'none', 'important');
  });
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
