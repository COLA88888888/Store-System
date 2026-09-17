<?php
// ============================================================
// pages/settings/print_barcode/index.php
// ຟາຍຫຼັກຄວບຄຸມໜ້າພິມບາໂຄ້ດສິນຄ້າ (Print Barcode Module)
// ============================================================
session_start();
$base_path = '../../../';
require_once __DIR__ . '/../../../config/db.php';

// ກວດສອບສິດທິການເຂົ້າເຖິງ
if (empty($_SESSION['user_id']) || (!hasPermission('print_barcode') && !hasPermission('setup'))) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

// ດຶງຂໍ້ມູນຊື່ຮ້ານ (ຊື່ຮ້ານແມ່ນຂໍ້ມູນຮ່ວມກັນ ມາຈາກສາຂາຫຼັກ)
$company_name = getCompanyInfoForBranch($pdo, getActiveStoreId($pdo))['com_name_la'] ?? 'MiniPos';
if (empty($company_name)) $company_name = 'MiniPos';

// ດຶງຂໍ້ມູນປະເພດ ແລະ ສິນຄ້າ (ສະເພາະສາຂາທີ່ເລືອກຢູ່)
$printBarcodeStoreId = getActiveStoreId($pdo);
$catStmt = $pdo->prepare("SELECT * FROM categories WHERE store_id = ? ORDER BY category_name ASC");
$catStmt->execute([$printBarcodeStoreId]);
$categories = $catStmt->fetchAll();
$prodStmt = $pdo->prepare("
    SELECT p.*, c.category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    WHERE p.store_id = ?
    ORDER BY p.product_name ASC
");
$prodStmt->execute([$printBarcodeStoreId]);
$products = $prodStmt->fetchAll();

// Helper ດຶງຮູບພາບສິນຄ້າ
function getProductImg($imgFile) {
    if (empty($imgFile)) return '../../../assets/img/image.jpg';
    $filename = basename(trim($imgFile));
    return '../../../assets/product_img/' . $filename;
}

$products_for_js = [];
foreach ($products as $p) {
    $p_copy = $p;
    $p_copy['img_path'] = getProductImg($p['img_url'] ?? '');
    $products_for_js[] = $p_copy;
}

require_once __DIR__ . '/../../../layouts/header.php';
?>

<script src="<?php echo $base_path; ?>assets/js/JsBarcode.all.min.js"></script>

<link rel="stylesheet" href="../../../themes/settings.css?v=<?php echo filemtime(__DIR__ . '/../../../themes/settings.css'); ?>">

<!-- Layout ຫຼັກ ສະແດງຜົນແຕ່ລະສ່ວນຍ່ອຍ (Modular Components) -->
<div class="container-fluid p-4 no-print">

  <!-- 1. ສ່ວນ Header ຫົວຂໍ້ໜ້າ -->
  <?php require_once __DIR__ . '/print_barcode_header.php'; ?>

  <!-- 2. ສ່ວນ ແຜງຄວບຄຸມ Filter ແລະ ຕັ້ງຄ່າສະຕິກເກີ -->
  <?php require_once __DIR__ . '/print_barcode_controls.php'; ?>

  <!-- 3. ສ່ວນ Container ສະແດງບັອກສິນຄ້າ -->
  <?php require_once __DIR__ . '/print_barcode_grid.php'; ?>

</div>

<!-- 4. ສ່ວນ ແຖບ Action Bar ດ້ານລຸ່ມ ແລະ Iframe ສັ່ງພິມ -->
<?php require_once __DIR__ . '/print_barcode_footer.php'; ?>

<!-- 5. ສ່ວນ JavaScript Render Barcode & Engine ສັ່ງພິມ -->
<?php require_once __DIR__ . '/print_barcode_js.php'; ?>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>