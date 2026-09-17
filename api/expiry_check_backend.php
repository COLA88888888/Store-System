<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($base_path)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';
}
require_once __DIR__ . '/../config/db.php';

// Check if logged in and has access to stock
if (empty($_SESSION['user_id']) || (!hasPermission('products'))) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$message = '';
$message_type = '';

$expiry_warning_days = intval(getSetting($pdo, 'expiry_warning_days', '30'));

// Handle Disposal Write-Off
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'dispose_batch') {
    $batch_id = intval($_POST['batch_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? 'ສິນຄ້າໝົດອາຍຸ');
    
    if ($batch_id > 0) {
        try {
            $pdo->beginTransaction();
            
            // Fetch batch details
            $stmt = $pdo->prepare("SELECT * FROM product_batches WHERE batch_id = ? AND quantity > 0 FOR UPDATE");
            $stmt->execute([$batch_id]);
            $batch = $stmt->fetch();
            
            if (!$batch) {
                throw new Exception("ບໍ່ພົບລັອດສິນຄ້າ ຫຼື ສິນຄ້າໃນລັອດນີ້ຖືກຕັດຈຳໜ່າຍໝົດແລ້ວ");
            }
            
            $product_id = $batch['product_id'];
            $qty_to_dispose = $batch['quantity'];
            
            // 1. Log disposal
            $stmt = $pdo->prepare("INSERT INTO disposals (product_id, batch_id, quantity, disposed_by, reason) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$product_id, $batch_id, $qty_to_dispose, $_SESSION['user_id'], $reason]);
            
            // 2. Reduce batch quantity to 0
            $stmt = $pdo->prepare("UPDATE product_batches SET quantity = 0 WHERE batch_id = ?");
            $stmt->execute([$batch_id]);
            
            // 3. Deduct from product stock cache
            $stmt = $pdo->prepare("UPDATE products SET stock_qty = stock_qty - ? WHERE product_id = ?");
            $stmt->execute([$qty_to_dispose, $product_id]);
            
            $pdo->commit();
            
            $message = 'ຕັດຈຳໜ່າຍສິນຄ້າໝົດອາຍຸສຳເລັດ!';
            $message_type = 'success';
            
            // Fetch product name for logging
            $p_stmt = $pdo->prepare("SELECT product_name FROM products WHERE product_id = ?");
            $p_stmt->execute([$product_id]);
            $p_name = $p_stmt->fetchColumn();
            
            logActivity($pdo, "ຕັດຈຳໜ່າຍສິນຄ້າ", "ສິນຄ້າ: $p_name, ຈຳນວນ: $qty_to_dispose, ເຫດຜົນ: $reason");
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = 'ຜິດພາດ: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Filters
$search_query = trim($_GET['search_query'] ?? '');
$category_filter = !empty($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$status_filter = $_GET['status_filter'] ?? ''; // 'expired', 'near', 'normal'

$activeStoreId = getActiveStoreId($pdo);

// Base Query
$query = "
    SELECT pb.*, p.product_name, p.base_unit, c.category_name, NULL AS shelf_name,
           DATEDIFF(pb.expiry_date, CURDATE()) AS days_left
    FROM product_batches pb
    JOIN products p ON pb.product_id = p.product_id
    JOIN categories c ON p.category_id = c.category_id
    WHERE pb.quantity > 0 AND pb.expiry_date IS NOT NULL AND p.store_id = ?
";

$where_clauses = [];
$params = [$activeStoreId];

if ($search_query !== '') {
    $query .= " AND p.product_name LIKE ?";
    $params[] = '%' . $search_query . '%';
}
if ($category_filter > 0) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_filter;
}

if ($status_filter === 'expired') {
    $query .= " AND pb.expiry_date < CURDATE()";
} elseif ($status_filter === 'near') {
    $query .= " AND pb.expiry_date >= CURDATE() AND pb.expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)";
    $params[] = $expiry_warning_days;
} elseif ($status_filter === 'normal') {
    $query .= " AND pb.expiry_date > DATE_ADD(CURDATE(), INTERVAL ? DAY)";
    $params[] = $expiry_warning_days;
}

$query .= " ORDER BY pb.expiry_date ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$batches = $stmt->fetchAll();

$catStmt = $pdo->prepare("SELECT * FROM categories WHERE store_id = ? ORDER BY category_name ASC");
$catStmt->execute([$activeStoreId]);
$categories = $catStmt->fetchAll();
