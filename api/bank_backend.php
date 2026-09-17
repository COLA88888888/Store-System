<?php
if (!defined('MINIPOS_APP')) {
    define('MINIPOS_APP', true);
}

if (!isset($base_path)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';
}
require_once __DIR__ . '/../config/db.php';

// Check authorization (Executive, Accountant, or users with accounting/bank permission)
if (empty($_SESSION['user_id']) || (!hasPermission('accounting') && !hasPermission('report') && ($_SESSION['status'] ?? '') !== 'ນັກບັນຊີ')) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$message = '';
$message_type = '';

// Handle POST actions for Bank Accounts
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // 1. ADD BANK ACCOUNT
    if ($action === 'add_bank_account') {
        $bank_name      = trim($_POST['bank_name'] ?? '');
        $account_number = trim($_POST['account_number'] ?? '');
        $account_name   = trim($_POST['account_name'] ?? '');
        $bank_code      = strtoupper(trim($_POST['bank_code'] ?? '')) ?: strtoupper(trim($bank_name));
        $is_active      = intval($_POST['is_active'] ?? 1);

        $bank_logo = null;
        if (!empty($_FILES['bank_logo']['name']) && $_FILES['bank_logo']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['bank_logo']['name'], PATHINFO_EXTENSION);
            $newLogo = 'bank_' . time() . '_' . rand(100,999) . '.' . $ext;
            $uploadDir = __DIR__ . '/../assets/img/banks/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            if (move_uploaded_file($_FILES['bank_logo']['tmp_name'], $uploadDir . $newLogo)) {
                $bank_logo = $newLogo;
            }
        }

        $qr_code_img = null;
        if (!empty($_FILES['qr_code_img']['name']) && $_FILES['qr_code_img']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['qr_code_img']['name'], PATHINFO_EXTENSION);
            $newQr = 'qr_bank_' . time() . '_' . rand(100,999) . '.' . $ext;
            $uploadDir = __DIR__ . '/../assets/img/qr/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            if (move_uploaded_file($_FILES['qr_code_img']['tmp_name'], $uploadDir . $newQr)) {
                $qr_code_img = $newQr;
            }
        }

        if (!empty($bank_name) && !empty($account_number)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO bank_accounts (bank_name, account_number, account_name, bank_code, bank_logo, qr_code_img, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$bank_name, $account_number, $account_name, $bank_code, $bank_logo, $qr_code_img, $is_active]);
                $message = "ເພີ່ມບັນຊີທະນາຄານ \"$bank_name\" ສໍາເລັດແລ້ວ!";
                $message_type = "success";
                logActivity($pdo, "ເພີ່ມບັນຊີທະນາຄານ", "$bank_name ($account_number)");
            } catch (Exception $e) {
                $message = "ຜິດພາດ: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }

    // 2. EDIT BANK ACCOUNT
    if ($action === 'edit_bank_account') {
        $bank_id        = intval($_POST['bank_id'] ?? 0);
        $bank_name      = trim($_POST['bank_name'] ?? '');
        $account_number = trim($_POST['account_number'] ?? '');
        $account_name   = trim($_POST['account_name'] ?? '');
        $bank_code      = strtoupper(trim($_POST['bank_code'] ?? '')) ?: strtoupper(trim($bank_name));
        $is_active      = intval($_POST['is_active'] ?? 1);

        if ($bank_id > 0 && !empty($bank_name)) {
            try {
                // ດຶງຮູບເກົ່າກ່ອນ update
                $oldRow = $pdo->prepare("SELECT bank_logo, qr_code_img FROM bank_accounts WHERE id = ?");
                $oldRow->execute([$bank_id]);
                $oldData = $oldRow->fetch(PDO::FETCH_ASSOC);

                $pdo->prepare("UPDATE bank_accounts SET bank_name = ?, account_number = ?, account_name = ?, bank_code = ?, is_active = ? WHERE id = ?")
                    ->execute([$bank_name, $account_number, $account_name, $bank_code, $is_active, $bank_id]);

                // ແກ້ໄຂ bank_logo ຖ້າ upload ໃໝ່
                if (!empty($_FILES['bank_logo']['name']) && $_FILES['bank_logo']['error'] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['bank_logo']['name'], PATHINFO_EXTENSION);
                    $newLogo = 'bank_' . time() . '_' . rand(100,999) . '.' . $ext;
                    $uploadDir = __DIR__ . '/../assets/img/banks/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    if (move_uploaded_file($_FILES['bank_logo']['tmp_name'], $uploadDir . $newLogo)) {
                        // ລົບຮູບ bank_logo ເກົ່າ
                        if (!empty($oldData['bank_logo']) && file_exists($uploadDir . $oldData['bank_logo'])) {
                            @unlink($uploadDir . $oldData['bank_logo']);
                        }
                        $pdo->prepare("UPDATE bank_accounts SET bank_logo = ? WHERE id = ?")
                            ->execute([$newLogo, $bank_id]);
                    }
                }

                // ແກ້ໄຂ qr_code_img ຖ້າ upload ໃໝ່
                if (!empty($_FILES['qr_code_img']['name']) && $_FILES['qr_code_img']['error'] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['qr_code_img']['name'], PATHINFO_EXTENSION);
                    $newQr = 'qr_bank_' . time() . '_' . rand(100,999) . '.' . $ext;
                    $uploadDir = __DIR__ . '/../assets/img/qr/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    if (move_uploaded_file($_FILES['qr_code_img']['tmp_name'], $uploadDir . $newQr)) {
                        // ລົບ qr_code_img ເກົ່າ
                        if (!empty($oldData['qr_code_img']) && file_exists($uploadDir . $oldData['qr_code_img'])) {
                            @unlink($uploadDir . $oldData['qr_code_img']);
                        }
                        $pdo->prepare("UPDATE bank_accounts SET qr_code_img = ? WHERE id = ?")
                            ->execute([$newQr, $bank_id]);
                    }
                }

                $message = "ແກ້ໄຂຂໍ້ມູນບັນຊີທະນາຄານ ສໍາເລັດແລ້ວ!";
                $message_type = "success";
                logActivity($pdo, "ແກ້ໄຂບັນຊີທະນາຄານ", "$bank_name ($account_number)");
            } catch (Exception $e) {
                $message = "ຜິດພາດ: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }

    // 3. DELETE BANK ACCOUNT
    if ($action === 'delete_bank_account') {
        $bank_id = intval($_POST['bank_id'] ?? 0);
        if ($bank_id > 0) {
            try {
                // ດຶງຊື່ໄຟລ໌ຮູບກ່ອນລົບ record
                $delRow = $pdo->prepare("SELECT bank_logo, qr_code_img FROM bank_accounts WHERE id = ?");
                $delRow->execute([$bank_id]);
                $delData = $delRow->fetch(PDO::FETCH_ASSOC);

                $pdo->prepare("DELETE FROM bank_accounts WHERE id = ?")->execute([$bank_id]);

                // ລົບຮູບ bank_logo ແລະ QR ອອກຈາກ folder
                if ($delData) {
                    if (!empty($delData['bank_logo'])) {
                        $f = __DIR__ . '/../assets/img/banks/' . $delData['bank_logo'];
                        if (file_exists($f)) @unlink($f);
                    }
                    if (!empty($delData['qr_code_img'])) {
                        $f = __DIR__ . '/../assets/img/qr/' . $delData['qr_code_img'];
                        if (file_exists($f)) @unlink($f);
                    }
                }

                $message = "ລົບບັນຊີທະນາຄານ ສໍາເລັດແລ້ວ!";
                $message_type = "success";
                logActivity($pdo, "ລົບບັນຊີທະນາຄານ", "ID: $bank_id");
            } catch (Exception $e) {
                $message = "ຜິດພາດ: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }
}

// Date Range & Store/Branch Filter parameters for revenue breakdown (Default to all sales history)
$start_date = $_GET['start_date'] ?? '2000-01-01';
$end_date   = $_GET['end_date'] ?? date('Y-m-d');
$filter_store_id = isset($_GET['store_id']) && $_GET['store_id'] !== '' ? intval($_GET['store_id']) : (isset($_GET['branch_id']) && $_GET['branch_id'] !== '' ? intval($_GET['branch_id']) : 0);
if (!canViewAllBranches($pdo)) {
    $filter_store_id = intval($_SESSION['store_id'] ?? 1);
}

// Fetch active branches for store filter dropdown
$branches_list = [];
try {
    $branches_stmt = $pdo->query("SELECT store_id, store_code, store_name, is_main, status FROM tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC");
    $branches_list = $branches_stmt ? $branches_stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Exception $e) {}

