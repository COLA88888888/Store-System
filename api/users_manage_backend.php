<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Dynamic base_path calculation based on URL request path
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

require_once __DIR__ . '/../config/db.php';

// Check if logged in and has access to users management
if (empty($_SESSION['user_id']) || (!hasPermission('users'))) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

// Users who can't see every branch only see accounts (and can only assign the branch) of their own branch.
$canAllBranches = canViewAllBranches($pdo);
$userStoreId = intval($_SESSION['store_id'] ?? 1);
$userScopeSql = $canAllBranches ? '' : ' WHERE u.store_id = ' . $userStoreId;

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$total_records = (int) $pdo->query("SELECT COUNT(*) FROM st_tbuser u" . $userScopeSql)->fetchColumn();
$total_pages = max(1, ceil($total_records / $limit));
if ($page > $total_pages) {
    $page = $total_pages;
}
$offset = ($page - 1) * $limit;

// Fetch paginated users with branch store name
$stmtUsers = $pdo->prepare("SELECT u.*, s.store_name FROM st_tbuser u LEFT JOIN st_tbstore s ON u.store_id = s.store_id" . $userScopeSql . " ORDER BY u.Id ASC LIMIT ? OFFSET ?");
$stmtUsers->bindValue(1, $limit, PDO::PARAM_INT);
$stmtUsers->bindValue(2, $offset, PDO::PARAM_INT);
$stmtUsers->execute();
$allUsers = $stmtUsers->fetchAll();

// Fetch branches/stores for dropdown selection
$storesList = $pdo->query("SELECT * FROM st_tbstore WHERE status = 'active'" . ($canAllBranches ? '' : ' AND store_id = ' . $userStoreId) . " ORDER BY is_main DESC, store_id ASC")->fetchAll();
