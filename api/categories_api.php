<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id']) || (!hasPermission('categories'))) {
    echo json_encode(['success' => false, 'message' => 'ບໍ່ມີສິດເຂົ້າເຖິງ']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'get_categories') {
    $categories = $pdo->query("SELECT * FROM st_categories ORDER BY category_id ASC")->fetchAll();
    echo json_encode(['success' => true, 'data' => $categories]);
    exit();
}

if ($action === 'get_category' && !empty($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM st_categories WHERE category_id = ?");
    $stmt->execute([$id]);
    $cat = $stmt->fetch();
    echo json_encode(['success' => true, 'data' => $cat]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid Action']);
