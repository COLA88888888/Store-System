<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($base_path)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';
}
require_once __DIR__ . '/../config/db.php';

// Check permissions
if (empty($_SESSION['user_id']) || (!hasPermission('customers'))) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$message = '';
$message_type = '';

$userStoreId = intval($_SESSION['store_id'] ?? 1);
$isAdmin = ($_SESSION['status'] ?? '') === 'ຜູ້ບໍລິຫານ' || strtolower($_SESSION['status'] ?? '') === 'admin' || ($_SESSION['user_id'] ?? 0) == 1;
$isMain = isMainBranch($pdo, $userStoreId);
$canAllBranches = canViewAllBranches($pdo);

// Fetch stores list for branch selection/filter
$stores = $pdo->query("SELECT * FROM st_tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC")->fetchAll(PDO::FETCH_ASSOC);

// GET filter_store parameter
$filter_store = isset($_GET['store_id']) && $_GET['store_id'] !== '' ? intval($_GET['store_id']) : intval($_SESSION['store_id'] ?? 1);
if (!$canAllBranches) {
    $filter_store = $userStoreId;
}

// ປະກາດຕົວປ່ຽນ $targetCodeStore ໄວ້ບ່ອນນີ້ ເພື່ອໃຫ້ຮຽກໃຊ້ໄດ້ທັງໃນ POST ແລະ ທ້າຍໄຟລ໌
$targetCodeStore = ($filter_store > 0) ? $filter_store : $userStoreId;

// Auto-ensure member_card & store_id columns exist
try {
    $custCols = $pdo->query("SHOW COLUMNS FROM st_customers")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('member_card', $custCols)) {
        $pdo->exec("ALTER TABLE `st_customers` ADD COLUMN `member_card` VARCHAR(50) NULL AFTER `phone`");
    }
    if (!in_array('store_id', $custCols)) {
        $pdo->exec("ALTER TABLE `st_customers` ADD COLUMN `store_id` INT DEFAULT 1 AFTER `notes`");
        $pdo->exec("UPDATE `st_customers` SET `store_id` = 1 WHERE `store_id` IS NULL OR `store_id` = 0");
    }
} catch (Throwable $ex) {}

// Handle Customer Form Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $targetStoreId = isset($_POST['store_id']) && intval($_POST['store_id']) > 0 ? intval($_POST['store_id']) : $targetCodeStore;
        if (!$canAllBranches) {
            $targetStoreId = $userStoreId;
        }

        if ($_POST['action'] === 'add_customer') {
            $code        = trim($_POST['customer_code'] ?? '');
            $name        = trim($_POST['customer_name'] ?? '');
            $phone       = trim($_POST['phone'] ?? '');
            $member_card = trim($_POST['member_card'] ?? '');
            $email       = trim($_POST['email'] ?? '');
            $address     = trim($_POST['address'] ?? '');
            $notes       = trim($_POST['notes'] ?? '');

            if (empty($code)) {
                $stmtMaxCode = $pdo->prepare("SELECT IFNULL(MAX(customer_id), 0) + 1 FROM st_customers WHERE store_id = ?");
                $stmtMaxCode->execute([$targetStoreId]);
                $maxId = (int)$stmtMaxCode->fetchColumn();
                $code = 'CUST-' . str_pad($targetStoreId, 2, '0', STR_PAD_LEFT) . '-' . str_pad($maxId, 3, '0', STR_PAD_LEFT);
            }

            if ($name !== '') {
                try {
                    $stmt = $pdo->prepare("INSERT INTO st_customers (customer_code, customer_name, phone, member_card, email, address, notes, store_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$code, $name, $phone, $member_card, $email, $address, $notes, $targetStoreId]);
                    $message = 'ເພີ່ມຂໍ້ມູນລູກຄ້າສຳເລັດ!';
                    $message_type = 'success';
                    logActivity($pdo, "ເພີ່ມຂໍ້ມູນລູກຄ້າ", "ລະຫັດ: $code, ຊື່: $name (ສາຂາ #$targetStoreId)");
                } catch (Exception $e) {
                    $message = 'ຜິດພາດ: ລະຫັດລູກຄ້ານີ້ອາດມີໃນລະບົບແລ້ວ ຫຼື ' . $e->getMessage();
                    $message_type = 'danger';
                }
            } else {
                $message = 'ກະລຸນາປ້ອນລະຫັດ ແລະ ຊື່ລູກຄ້າໃຫ້ຄົບຖ້ວນ!';
                $message_type = 'danger';
            }
        }
        elseif ($_POST['action'] === 'edit_customer') {
            $id          = intval($_POST['customer_id'] ?? 0);
            $name        = trim($_POST['customer_name'] ?? '');
            $phone       = trim($_POST['phone'] ?? '');
            $member_card = trim($_POST['member_card'] ?? '');
            $email       = trim($_POST['email'] ?? '');
            $address     = trim($_POST['address'] ?? '');
            $notes       = trim($_POST['notes'] ?? '');

            if ($id > 0 && $name !== '') {
                try {
                    if ($canAllBranches) {
                        $stmt = $pdo->prepare("UPDATE st_customers SET customer_name = ?, phone = ?, member_card = ?, email = ?, address = ?, notes = ?, store_id = ? WHERE customer_id = ?");
                        $stmt->execute([$name, $phone, $member_card, $email, $address, $notes, $targetStoreId, $id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE st_customers SET customer_name = ?, phone = ?, member_card = ?, email = ?, address = ?, notes = ? WHERE customer_id = ? AND store_id = ?");
                        $stmt->execute([$name, $phone, $member_card, $email, $address, $notes, $id, $userStoreId]);
                    }
                    $message = 'ແກ້ໄຂຂໍ້ມູນລູກຄ້າສຳເລັດ!';
                    $message_type = 'success';
                    logActivity($pdo, "ແກ້ໄຂຂໍ້ມູນລູກຄ້າ", "ID: $id, ຊື່: $name");
                } catch (Exception $e) {
                    $message = 'ຜິດພາດ: ' . $e->getMessage();
                    $message_type = 'danger';
                }
            } else {
                $message = 'ກະລຸນາປ້ອນຊື່ລູກຄ້າໃຫ້ຄົບຖ້ວນ!';
                $message_type = 'danger';
            }
        }
        elseif ($_POST['action'] === 'delete_customer') {
            $id = intval($_POST['customer_id'] ?? 0);
            if ($id > 0) {
                try {
                    if ($canAllBranches) {
                        $stmt = $pdo->prepare("DELETE FROM st_customers WHERE customer_id = ?");
                        $stmt->execute([$id]);
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM st_customers WHERE customer_id = ? AND store_id = ?");
                        $stmt->execute([$id, $userStoreId]);
                    }
                    $message = 'ລົບຂໍ້ມູນລູກຄ້າສຳເລັດ!';
                    $message_type = 'success';
                    logActivity($pdo, "ລົບຂໍ້ມູນລູກຄ້າ", "ID: $id");
                } catch (Exception $e) {
                    $message = 'ຜິດພາດ: ' . $e->getMessage();
                    $message_type = 'danger';
                }
            }
        }
    }
}

if (!empty($_POST['is_ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => ($message_type === 'success'),
        'message' => $message
    ]);
    exit();
}

// Fetch Customers Scoped to Filtered Store
$where = ["1=1"];
$params = [];
if ($filter_store > 0) {
    $where[] = "c.store_id = :filter_store";
    $params[':filter_store'] = $filter_store;
}
$whereSql = implode(' AND ', $where);

$stmtCust = $pdo->prepare("SELECT c.*, s.store_name FROM st_customers c LEFT JOIN st_tbstore s ON c.store_id = s.store_id WHERE {$whereSql} ORDER BY c.customer_id DESC");
$stmtCust->execute($params);
$allCustomers = $stmtCust->fetchAll();
$total_records = count($allCustomers);

// Generate Next Customer Code Scoped to Store (ໃຊ້ Prepared Statement ປອດໄພ 100%)
$stmtNext = $pdo->prepare("SELECT IFNULL(MAX(customer_id), 0) + 1 FROM st_customers WHERE store_id = ?");
$stmtNext->execute([$targetCodeStore]);
$maxId = (int)$stmtNext->fetchColumn();

$next_cust_code = 'CUST-' . str_pad($targetCodeStore, 2, '0', STR_PAD_LEFT) . '-' . str_pad($maxId, 3, '0', STR_PAD_LEFT);

if (!empty($_GET['fetch_table'])) {
    require_once __DIR__ . '/../pages/customers/components/customer_table.php';
    exit();
}