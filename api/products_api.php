<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id']) || (!hasPermission('products'))) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$activeStoreId = getActiveStoreId($pdo);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// --- Upload one product image ahead of a bulk-add submit ---
// Each bulk row uploads its picture as soon as it is picked and keeps only the returned
// file name, so a large batch never runs into PHP's max_file_uploads limit on submit.
if ($action === 'upload_image' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../pages/products/components/product_image_helpers.php';
    $uploadError = null;
    $img = saveUploadedProductImage('image', $uploadError);
    echo json_encode($img
        ? ['success' => true, 'file' => $img, 'url' => getProductImagePath($img)]
        : ['success' => false, 'message' => $uploadError ?: 'ບໍ່ພົບໄຟລ໌ຮູບພາບ']);
    exit();
}

// --- GET all products ---
if ($action === 'get_products') {
    $stmt = $pdo->prepare("
        SELECT p.*, c.category_name
        FROM st_products p
        LEFT JOIN st_categories c ON p.category_id = c.category_id
        WHERE p.store_id = ?
        ORDER BY p.product_id DESC
    ");
    $stmt->execute([$activeStoreId]);
    $products = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $products]);
    exit();
}

// --- GET single product ---
if ($action === 'get_product' && !empty($_GET['id'])) {
    $id   = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT p.*, c.category_name FROM st_products p LEFT JOIN st_categories c ON p.category_id = c.category_id WHERE p.product_id = ? AND p.store_id = ?");
    $stmt->execute([$id, $activeStoreId]);
    $product = $stmt->fetch();
    echo json_encode(['success' => true, 'data' => $product]);
    exit();
}

// --- GET products by category ---
if ($action === 'get_by_category' && isset($_GET['category_id'])) {
    $catId = intval($_GET['category_id']);
    if ($catId > 0) {
        $stmt = $pdo->prepare("SELECT p.*, c.category_name FROM st_products p LEFT JOIN st_categories c ON p.category_id = c.category_id WHERE p.category_id = ? AND p.store_id = ? ORDER BY p.product_id DESC");
        $stmt->execute([$catId, $activeStoreId]);
    } else {
        $stmt = $pdo->prepare("SELECT p.*, c.category_name FROM st_products p LEFT JOIN st_categories c ON p.category_id = c.category_id WHERE p.store_id = ? ORDER BY p.product_id DESC");
        $stmt->execute([$activeStoreId]);
    }
    $products = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $products]);
    exit();
}

// --- Search products ---
if ($action === 'search' && isset($_GET['q'])) {
    $q    = '%' . trim($_GET['q']) . '%';
    $stmt = $pdo->prepare("
        SELECT p.*, c.category_name
        FROM st_products p
        LEFT JOIN st_categories c ON p.category_id = c.category_id
        WHERE (p.product_name LIKE ? OR p.barcode LIKE ?) AND p.store_id = ?
        ORDER BY p.product_id DESC
        LIMIT 50
    ");
    $stmt->execute([$q, $q, $activeStoreId]);
    $products = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $products]);
    exit();
}

// --- GET next product ID (branch code + category id + running number, see generateProductId) ---
if ($action === 'get_next_product_id') {
    $catId = intval($_GET['category_id'] ?? 0);
    $nextId = generateProductId($pdo, $catId, $activeStoreId);
    $seqFormatted = substr((string)$nextId, -4);

    echo json_encode([
        'success' => true,
        'next_id' => $nextId,
        'seq' => (int)$seqFormatted,
        'seq_formatted' => $seqFormatted,
        'prefix' => getProductCodePrefix($pdo, $activeStoreId) // branch code; the form shows prefix + category + seq
    ]);
    exit();
}

// --- GET product units ---
if ($action === 'get_product_units' && isset($_GET['product_id'])) {
    $productId = intval($_GET['product_id']);
    if ($productId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM st_product_units WHERE product_id = ? ORDER BY multiplier ASC, id ASC");
        $stmt->execute([$productId]);
        $units = $stmt->fetchAll();
        echo json_encode(['success' => true, 'units' => $units]);
    } else {
        echo json_encode(['success' => false, 'units' => []]);
    }
    exit();
}

// --- Check Barcode Duplicate ---
if ($action === 'check_barcode' && isset($_GET['barcode'])) {
    $barcode = trim($_GET['barcode']);
    $exclude_id = intval($_GET['product_id'] ?? 0);
    
    if ($barcode === '') {
        echo json_encode(['exists' => false]);
        exit();
    }

    // Check main products table within current branch
    $stmt = $pdo->prepare("SELECT product_id, product_name FROM st_products WHERE barcode = ? AND product_id != ? AND store_id = ?");
    $stmt->execute([$barcode, $exclude_id, $activeStoreId]);
    $p = $stmt->fetch();

    if ($p) {
        echo json_encode([
            'exists' => true,
            'message' => 'ລະຫັດບາໂຄ້ດ "' . $barcode . '" ນີ້ຖືກໃຊ້ແລ້ວນຳສິນຄ້າ: "' . $p['product_name'] . '" (ID: ' . $p['product_id'] . ')'
        ]);
        exit();
    }

    // Check product_units table within current branch
    $stmt2 = $pdo->prepare("SELECT u.product_id, p.product_name FROM st_product_units u JOIN st_products p ON u.product_id = p.product_id WHERE u.barcode = ? AND u.product_id != ? AND p.store_id = ?");
    $stmt2->execute([$barcode, $exclude_id, $activeStoreId]);
    $u = $stmt2->fetch();

    if ($u) {
        echo json_encode([
            'exists' => true,
            'message' => 'ລະຫັດບາໂຄ້ດ "' . $barcode . '" ນີ້ຖືກໃຊ້ແລ້ວນຳຫົວໜ່ວຍຍ່ອຍຂອງສິນຄ້າ: "' . $u['product_name'] . '" (ID: ' . $u['product_id'] . ')'
        ]);
        exit();
    }

    echo json_encode(['exists' => false]);
    exit();
}

// --- Quick Add Zone ---
if ($action === 'add_zone' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $zoneName = trim($_POST['zone_name'] ?? '');
    if ($zoneName === '') {
        echo json_encode(['success' => false, 'message' => 'ກະລຸນາປ້ອນຊື່ໂຊນ']);
        exit();
    }
    $chk = $pdo->prepare("SELECT zone_id, zone_name FROM st_zones WHERE zone_name = ? AND store_id = ? LIMIT 1");
    $chk->execute([$zoneName, $activeStoreId]);
    $existing = $chk->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        echo json_encode(['success' => true, 'zone_id' => $existing['zone_id'], 'zone_name' => $existing['zone_name']]);
        exit();
    }
    $ins = $pdo->prepare("INSERT INTO st_zones (zone_name, store_id) VALUES (?, ?)");
    $ins->execute([$zoneName, $activeStoreId]);
    $newId = (int)$pdo->lastInsertId();
    echo json_encode(['success' => true, 'zone_id' => $newId, 'zone_name' => $zoneName]);
    exit();
}

// --- Quick Add Shelf ---
if ($action === 'add_shelf' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $shelfName = trim($_POST['shelf_name'] ?? '');
    $zoneId = intval($_POST['zone_id'] ?? 0) ?: null;
    if ($shelfName === '') {
        echo json_encode(['success' => false, 'message' => 'ກະລຸນາປ້ອນຊື່ຊັ້ນວາງ']);
        exit();
    }
    $chk = $pdo->prepare("SELECT shelf_id, shelf_name FROM st_shelves WHERE shelf_name = ? AND zone_id <=> ? AND store_id = ? LIMIT 1");
    $chk->execute([$shelfName, $zoneId, $activeStoreId]);
    $existing = $chk->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        echo json_encode(['success' => true, 'shelf_id' => $existing['shelf_id'], 'shelf_name' => $existing['shelf_name']]);
        exit();
    }
    $ins = $pdo->prepare("INSERT INTO st_shelves (shelf_name, zone_id, store_id) VALUES (?, ?, ?)");
    $ins->execute([$shelfName, $zoneId, $activeStoreId]);
    $newId = (int)$pdo->lastInsertId();
    echo json_encode(['success' => true, 'shelf_id' => $newId, 'shelf_name' => $shelfName]);
    exit();
}

// --- Quick Add Unit ---
if ($action === 'add_unit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $unitName = trim($_POST['unit_name'] ?? '');
    if ($unitName === '') {
        echo json_encode(['success' => false, 'message' => 'ກະລຸນາປ້ອນຊື່ຫົວໜ່ວຍ']);
        exit();
    }
    $chk = $pdo->prepare("SELECT unit_id, unit_name FROM st_units WHERE unit_name = ? AND store_id = ? LIMIT 1");
    $chk->execute([$unitName, $activeStoreId]);
    $existing = $chk->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        echo json_encode(['success' => true, 'unit_id' => (int)$existing['unit_id'], 'unit_name' => $existing['unit_name']]);
        exit();
    }
    $ins = $pdo->prepare("INSERT INTO st_units (unit_name, store_id) VALUES (?, ?)");
    $ins->execute([$unitName, $activeStoreId]);
    $newId = (int)$pdo->lastInsertId();
    echo json_encode(['success' => true, 'unit_id' => $newId, 'unit_name' => $unitName]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid Action']);
