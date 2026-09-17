<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id']) || (!hasPermission('products'))) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$activeStoreId = getActiveStoreId($pdo);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// --- GET all products ---
if ($action === 'get_products') {
    $stmt = $pdo->prepare("
        SELECT p.*, c.category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
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
    $stmt = $pdo->prepare("SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id WHERE p.product_id = ? AND p.store_id = ?");
    $stmt->execute([$id, $activeStoreId]);
    $product = $stmt->fetch();
    echo json_encode(['success' => true, 'data' => $product]);
    exit();
}

// --- GET products by category ---
if ($action === 'get_by_category' && isset($_GET['category_id'])) {
    $catId = intval($_GET['category_id']);
    if ($catId > 0) {
        $stmt = $pdo->prepare("SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id WHERE p.category_id = ? AND p.store_id = ? ORDER BY p.product_id DESC");
        $stmt->execute([$catId, $activeStoreId]);
    } else {
        $stmt = $pdo->prepare("SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id WHERE p.store_id = ? ORDER BY p.product_id DESC");
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
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE (p.product_name LIKE ? OR p.barcode LIKE ?) AND p.store_id = ?
        ORDER BY p.product_id DESC
        LIMIT 50
    ");
    $stmt->execute([$q, $q, $activeStoreId]);
    $products = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $products]);
    exit();
}

// --- GET next product ID (Category ID + Global Total Count Sequence) ---
if ($action === 'get_next_product_id') {
    $catId = intval($_GET['category_id'] ?? 0);
    $totalCount = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $seq = $totalCount + 1;
    $seqFormatted = str_pad($seq, 4, '0', STR_PAD_LEFT);
    
    $nextId = $catId > 0 ? intval($catId . $seqFormatted) : intval($seqFormatted);
    while ((int)$pdo->query("SELECT COUNT(*) FROM products WHERE product_id = " . intval($nextId))->fetchColumn() > 0) {
        $seq++;
        $seqFormatted = str_pad($seq, 4, '0', STR_PAD_LEFT);
        $nextId = $catId > 0 ? intval($catId . $seqFormatted) : intval($seqFormatted);
    }
    
    echo json_encode([
        'success' => true,
        'next_id' => $nextId,
        'seq' => $seq,
        'seq_formatted' => $seqFormatted
    ]);
    exit();
}

// --- GET product units ---
if ($action === 'get_product_units' && isset($_GET['product_id'])) {
    $productId = intval($_GET['product_id']);
    if ($productId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM product_units WHERE product_id = ? ORDER BY multiplier ASC, id ASC");
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
    $stmt = $pdo->prepare("SELECT product_id, product_name FROM products WHERE barcode = ? AND product_id != ? AND store_id = ?");
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
    $stmt2 = $pdo->prepare("SELECT u.product_id, p.product_name FROM product_units u JOIN products p ON u.product_id = p.product_id WHERE u.barcode = ? AND u.product_id != ? AND p.store_id = ?");
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

echo json_encode(['success' => false, 'message' => 'Invalid Action']);
