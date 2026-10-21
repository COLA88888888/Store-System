<?php
/**
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * ໜ້າຈັດການສິນຄ້າ: ຮັບການກະທຳ POST (ເພີ່ມ / ເພີ່ມຫຼາຍລາຍການ / ແກ້ໄຂ / ລົບ) → ດຶງຂໍ້ມູນ → ສະແດງຕາຕະລາງ. ແຕ່ລະການກະທຳແຍກໄວ້ໃນ actions/, ສ່ວນສະແດງຜົນ ແລະ JS ແຍກໄວ້ໃນ partials/products/.
 *   - actions/add_product.php : ການກະທຳ: ເພີ່ມສິນຄ້າທີລະລາຍການ
 *   - actions/add_products_bulk.php : ການກະທຳ: ເພີ່ມສິນຄ້າຫຼາຍລາຍການພ້ອມກັນ
 *   - actions/edit_product.php : ການກະທຳ: ແກ້ໄຂສິນຄ້າ
 *   - actions/delete_product.php : ການກະທຳ: ລົບສິນຄ້າ
 *   - partials/products/page_table.php : ສ່ວນສະແດງຜົນ: ຫົວໜ້າ ແລະ ຕາຕະລາງສິນຄ້າ
 *   - partials/products/products_js.php : JS ຂອງໜ້າສິນຄ້າ
 */
session_start();
$base_path = '../../';
require_once __DIR__ . '/../../config/db.php';

// Check if logged in and has access to stock
if (empty($_SESSION['user_id']) || (!hasPermission('products'))) {
    echo "<script>window.top.location.href = '../../index.php';</script>";
    exit();
}

$message = '';
$message_type = '';

$isAdmin = (
    ($_SESSION['status'] ?? '') === 'ຜູ້ບໍລິຫານ' ||
    strtolower($_SESSION['status'] ?? '') === 'admin' ||
    ($_SESSION['user_id'] ?? 0) == 1
);

// Multi-currency: prices stored in KIP; the list shows the active display currency.
$__pdc = getDisplayCurrency($pdo);
$__psym = $__pdc['symbol']; $__pdec = (int)$__pdc['decimals'];

require_once __DIR__ . '/components/product_image_helpers.php';

// ====== Handle POST actions ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // --- ADD ---
    if ($_POST['action'] === 'add_product') {
        // ການກະທຳ: ເພີ່ມສິນຄ້າທີລະລາຍການ
        require __DIR__ . '/actions/add_product.php';
    }

    // --- ADD BULK (ເພີ່ມຫຼາຍລາຍການພ້ອມກັນ) ---
    // Each row carries: category, name, barcode, unit, buy price, sell price, and an optional
    // image (uploaded beforehand, see api_product.php?action=upload_image). One supplier box
    // (bulk_supplier_id) applies to the whole batch. Starting stock qty is always 0. Fully-blank rows are skipped silently;
    // partially-filled / invalid rows are reported back and the rest still go in.
    elseif ($_POST['action'] === 'add_products_bulk') {
        // ການກະທຳ: ເພີ່ມສິນຄ້າຫຼາຍລາຍການພ້ອມກັນ
        require __DIR__ . '/actions/add_products_bulk.php';
    }

    // --- EDIT ---
    elseif ($_POST['action'] === 'edit_product') {
        // ການກະທຳ: ແກ້ໄຂສິນຄ້າ
        require __DIR__ . '/actions/edit_product.php';
    }

    // --- DELETE ---
    elseif ($_POST['action'] === 'delete_product') {
        // ການກະທຳ: ລົບສິນຄ້າ
        require __DIR__ . '/actions/delete_product.php';
    }
}

// ====== Fetch data ======
$userStoreId = intval($_SESSION['store_id'] ?? 1);
$isMain = isMainBranch($pdo, $userStoreId);
$canAllBranches = canViewAllBranches($pdo);

if (isset($_GET['store_id']) && ($canAllBranches)) {
    $_SESSION['active_store_id'] = intval($_GET['store_id']);
}

$currentStoreId = getActiveStoreId($pdo);

// Sub-branch users are strictly restricted to their own branch
if (!$canAllBranches) {
    $selectedStoreId = $userStoreId;
} else {
    $selectedStoreId = isset($_GET['store_id']) ? intval($_GET['store_id']) : $currentStoreId;
}

$catStmt = $pdo->query("SELECT * FROM st_categories ORDER BY category_id ASC");
$categories = $catStmt->fetchAll();

$unitStmt = $pdo->query("SELECT * FROM st_units ORDER BY unit_id ASC");
$units = $unitStmt->fetchAll();

// ຜູ້ສະໜອງ (ສະເພາະທີ່ຍັງໃຊ້ງານ) ສຳລັບ dropdown ໃນຟອມເພີ່ມ/ແກ້ໄຂສິນຄ້າ
$suppliers = [];
try {
    $supStmt = $pdo->prepare("SELECT supplier_id, supplier_name FROM st_suppliers WHERE store_id = ? AND status = 'active' ORDER BY supplier_name ASC");
    $supStmt->execute([$selectedStoreId]);
    $suppliers = $supStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

// ໂຊນ ແລະ ຊັ້ນວາງ ສຳລັບ dropdown ໃນຟອມເພີ່ມ/ແກ້ໄຂສິນຄ້າ
$zones = [];
$shelves = [];
try {
    $zStmt = $pdo->prepare("SELECT zone_id, zone_name FROM st_zones WHERE store_id = ? ORDER BY zone_name ASC");
    $zStmt->execute([$selectedStoreId]);
    $zones = $zStmt->fetchAll(PDO::FETCH_ASSOC);

    $shStmt = $pdo->prepare("SELECT s.shelf_id, s.zone_id, s.shelf_name, z.zone_name 
                             FROM st_shelves s 
                             LEFT JOIN st_zones z ON z.zone_id = s.zone_id 
                             WHERE s.store_id = ? 
                             ORDER BY s.shelf_name ASC");
    $shStmt->execute([$selectedStoreId]);
    $shelves = $shStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}
$stores = $pdo->query("SELECT * FROM st_tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC")->fetchAll(PDO::FETCH_ASSOC);

$products_stmt = $pdo->prepare("
    SELECT p.*, c.category_name, s.store_name, s.store_code
    FROM st_products p
    LEFT JOIN st_categories c ON p.category_id = c.category_id
    LEFT JOIN st_tbstore s ON p.store_id = s.store_id
    WHERE p.store_id = ?
    ORDER BY p.code1 DESC, p.product_id DESC
");
$products_stmt->execute([$selectedStoreId]);
$products = $products_stmt->fetchAll();
$expiryAlertDays = getExpiryAlertDays($pdo);

// ດຶງຂໍ້ມູນ product_units ທັງໝົດມາ map
$allUnitsStmt = $pdo->query("SELECT * FROM st_product_units ORDER BY multiplier ASC, id ASC");
$productUnitsMap = [];
while ($u = $allUnitsStmt->fetch()) {
    $productUnitsMap[$u['product_id']][] = $u;
}

// ຄຳນວນລຳດັບສິນຄ້າຖັດໄປ (Global entry sequence number: 0001, 0002, 0003...)
$totalProductCount = count($products);
$nextSeqNum = $totalProductCount + 1;
$nextSeqFormatted = str_pad($nextSeqNum, 4, '0', STR_PAD_LEFT);

require_once __DIR__ . '/../../layouts/header.php';
?>

<link rel="stylesheet" href="../../themes/products.css?v=<?php echo filemtime(__DIR__ . '/../../themes/products.css'); ?>">

<div class="container-fluid p-4">

<?php require __DIR__ . '/partials/products/page_table.php'; /* ສ່ວນສະແດງຜົນ: ຫົວໜ້າ ແລະ ຕາຕະລາງສິນຄ້າ */ ?>
<!-- ====== Modal Components ====== -->
<?php require_once __DIR__ . '/components/form_add_product.php'; ?>
<?php require_once __DIR__ . '/components/form_edit_product.php'; ?>



<?php require __DIR__ . '/partials/products/products_zone_shelf_js.php'; /* JS ໂຊນ ແລະ ຊັ້ນວາງ */ ?>
<?php require __DIR__ . '/partials/products/products_js.php'; /* JS ຂອງໜ້າສິນຄ້າ */ ?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
