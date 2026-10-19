<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($base_path)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';
}
require_once __DIR__ . '/../config/db.php';

// Check authorization
if (empty($_SESSION['user_id']) || (!hasPermission('branches') && !hasPermission('setup'))) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$message = '';
$message_type = '';

// Creating, editing, opening/closing, deleting or switching branches spans more than one
// branch, so it's reserved for users who can see every branch.
$canManageBranches = canViewAllBranches($pdo);

// Handle POST switch_branch
if ($canManageBranches && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'switch_branch') {
    $store_id = intval($_POST['store_id'] ?? 1);
    $_SESSION['active_store_id'] = $store_id;
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'store_id' => $store_id]);
    exit();
}

// Auto-generate next numeric Branch Code (1, 2, 3...)
$max_id = (int)$pdo->query("SELECT COALESCE(MAX(store_id), 0) FROM st_tbstore")->fetchColumn();
$next_store_code = (string)($max_id + 1);

// Handle POST Add Branch Store
if ($canManageBranches && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_branch') {
    $store_code = trim($_POST['store_code'] ?? '');
    if ($store_code === '') {
        $store_code = $next_store_code;
    }
    $store_name = trim($_POST['store_name'] ?? '');
    $address    = trim($_POST['address'] ?? '');
    $tel        = trim($_POST['tel'] ?? '');
    $is_main    = isset($_POST['is_main']) ? 1 : 0;

    if ($store_code !== '' && $store_name !== '') {
        try {
            if ($is_main === 1) {
                $pdo->exec("UPDATE st_tbstore SET is_main = 0");
            }
            $stmt = $pdo->prepare("INSERT INTO st_tbstore (store_code, store_name, is_main, address, tel, status) VALUES (?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$store_code, $store_name, $is_main, $address, $tel]);
            $message = 'ເພີ່ມສາຂາໃໝ່ ສຳເລັດແລ້ວ!';
            $message_type = 'success';
            logActivity($pdo, "ເພີ່ມສາຂາໃໝ່", $store_name);
        } catch (Exception $e) {
            $message = 'ຜິດພາດ: ' . $e->getMessage();
            $message_type = 'danger';
        }
    } else {
        $message = 'ກະລຸນາປ້ອນລະຫັດສາຂາ ແລະ ຊື່ສາຂາ!';
        $message_type = 'warning';
    }
}

// Handle POST Edit Branch Store
if ($canManageBranches && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_branch') {
    $store_id   = intval($_POST['store_id'] ?? 0);
    $store_code = trim($_POST['store_code'] ?? '');
    $store_name = trim($_POST['store_name'] ?? '');
    $address    = trim($_POST['address'] ?? '');
    $tel        = trim($_POST['tel'] ?? '');
    $status     = trim($_POST['status'] ?? 'active');
    $is_main    = isset($_POST['is_main']) ? 1 : 0;

    if ($store_id > 0 && $store_name !== '') {
        try {
            if ($is_main === 1) {
                $pdo->exec("UPDATE st_tbstore SET is_main = 0");
            }
            $stmt = $pdo->prepare("UPDATE st_tbstore SET store_code = ?, store_name = ?, is_main = ?, address = ?, tel = ?, status = ? WHERE store_id = ?");
            $stmt->execute([$store_code, $store_name, $is_main, $address, $tel, $status, $store_id]);
            $message = 'ແກ້ໄຂຂໍ້ມູນສາຂາ ສຳເລັດແລ້ວ!';
            $message_type = 'success';
            logActivity($pdo, "ແກ້ໄຂຂໍ້ມູນສາຂາ", $store_name);
        } catch (Exception $e) {
            $message = 'ຜິດພາດ: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Handle GET Toggle Branch Status (Open / Close Branch)
if ($canManageBranches && isset($_GET['toggle_status_id'])) {
    $toggle_id = intval($_GET['toggle_status_id']);
    if ($toggle_id > 0) {
        try {
            $curStatus = $pdo->query("SELECT status, store_name FROM st_tbstore WHERE store_id = {$toggle_id}")->fetch(PDO::FETCH_ASSOC);
            if ($curStatus) {
                $newStatus = ($curStatus['status'] === 'active') ? 'inactive' : 'active';
                $stmt = $pdo->prepare("UPDATE st_tbstore SET status = ? WHERE store_id = ?");
                $stmt->execute([$newStatus, $toggle_id]);

                $statusLabel = ($newStatus === 'active') ? 'ເປີດໃຊ້ງານ' : 'ປິດໃຊ້ງານ';
                $message = "ປ່ຽນສະຖານະສາຂາ \"{$curStatus['store_name']}\" ເປັນ {$statusLabel} ສຳເລັດແລ້ວ!";
                $message_type = 'success';
                logActivity($pdo, "ປ່ຽນສະຖານະສາຂາ", "{$curStatus['store_name']} ({$statusLabel})");
            }
        } catch (Exception $e) {
            $message = 'ຜິດພາດ: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Handle GET Delete Branch
if ($canManageBranches && isset($_GET['delete_branch_id'])) {
    $del_id = intval($_GET['delete_branch_id']);
    if ($del_id > 0) {
        try {
            $branch = $pdo->query("SELECT store_name, is_main FROM st_tbstore WHERE store_id = {$del_id}")->fetch(PDO::FETCH_ASSOC);
            if (!$branch) {
                $message = 'ບໍ່ພົບຂໍ້ມູນສາຂານີ້!';
                $message_type = 'danger';
            } elseif ($branch['is_main']) {
                $message = 'ບໍ່ສາມາດລົບສາຂາໃຫຍ່ໄດ້!';
                $message_type = 'warning';
            } else {
                // A branch that used to be "main" can be re-flagged non-main by adding/editing
                // another branch as main, which would otherwise make it pass the is_main check
                // above and get deleted while products/users/sales still reference its store_id
                // — orphaning them permanently. Block deletion while any such row still exists.
                $linkedCounts = [
                    'ສິນຄ້າ'      => $pdo->prepare("SELECT COUNT(*) FROM st_products WHERE store_id = ?"),
                    'ຜູ້ໃຊ້ງານ'    => $pdo->prepare("SELECT COUNT(*) FROM st_tbuser WHERE store_id = ?"),
                    'ລູກຄ້າ'      => $pdo->prepare("SELECT COUNT(*) FROM st_customers WHERE store_id = ?"),
                    'ການຂາຍ'      => $pdo->prepare("SELECT COUNT(*) FROM st_tbsale_save WHERE store_id = ?"),
                ];
                $blockedBy = null;
                foreach ($linkedCounts as $label => $stmtCount) {
                    $stmtCount->execute([$del_id]);
                    if ((int)$stmtCount->fetchColumn() > 0) {
                        $blockedBy = $label;
                        break;
                    }
                }
                if ($blockedBy !== null) {
                    $message = "ບໍ່ສາມາດລົບສາຂາໄດ້ ເນື່ອງຈາກຍັງມີຂໍ້ມູນ{$blockedBy}ຢູ່ໃນສາຂານີ້! ກະລຸນາຍ້າຍຂໍ້ມູນອອກກ່ອນ.";
                    $message_type = 'warning';
                } else {
                    $pdo->prepare("DELETE FROM st_tbstore WHERE store_id = ?")->execute([$del_id]);
                    // ສາຂາຖືກລົບແລ້ວ — ໂລໂກ້ຂອງສາຂານັ້ນ (ຖ້າບໍ່ມີສາຂາອື່ນໃຊ້ນຳ) ບໍ່ຕ້ອງຄ້າງໃນໂຟນເດີ
                    cleanupUnusedLogos($pdo);
                    $message = "ລົບສາຂາ \"{$branch['store_name']}\" ສຳເລັດແລ້ວ!";
                    $message_type = 'success';
                    logActivity($pdo, "ລົບສາຂາ", $branch['store_name']);
                }
            }
        } catch (Exception $e) {
            $message = 'ຜິດພາດ: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Fetch all branch stores
if ($canManageBranches) {
    $all_branches = $pdo->query("SELECT * FROM st_tbstore ORDER BY is_main DESC, store_id ASC")->fetchAll();
} else {
    $ownBranchStmt = $pdo->prepare("SELECT * FROM st_tbstore WHERE store_id = ?");
    $ownBranchStmt->execute([intval($_SESSION['store_id'] ?? 0)]);
    $all_branches = $ownBranchStmt->fetchAll();
}
$branches = $all_branches;
