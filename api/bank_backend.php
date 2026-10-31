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
if (empty($_SESSION['user_id']) || (!hasPermission('accounting'))) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$message = '';
$message_type = '';

// Same "branch_id = 0 means every branch" convention promotions already use — a shop can share
// one bank account across all its branches, or give a branch its own. Only a user who can see
// every branch (canViewAllBranches) is allowed to pick 0 or another branch's id; everyone else
// is locked to their own store_id regardless of what the form posts.
$canAllBranches = canViewAllBranches($pdo);
$userStoreId    = intval($_SESSION['store_id'] ?? 1);

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
        $bank_store_id  = isset($_POST['store_id']) ? intval($_POST['store_id']) : $userStoreId;
        if (!$canAllBranches) {
            $bank_store_id = $userStoreId;
        }

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
                $stmt = $pdo->prepare("INSERT INTO st_bank_accounts (bank_name, account_number, account_name, bank_code, bank_logo, qr_code_img, is_active, store_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$bank_name, $account_number, $account_name, $bank_code, $bank_logo, $qr_code_img, $is_active, $bank_store_id]);
                $message = ((("" . tl('ເພີ່ມບັນຊີທະນາຄານ') . " \"$bank_name\" ")) . tl('ສໍາເລັດແລ້ວ!') . "");
                $message_type = "success";
                logActivity($pdo, "ເພີ່ມບັນຊີທະນາຄານ", "$bank_name ($account_number)");
            } catch (Exception $e) {
                $message = (tl('ຜິດພາດ:') . ' ') . $e->getMessage();
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
        $bank_store_id  = isset($_POST['store_id']) ? intval($_POST['store_id']) : $userStoreId;
        if (!$canAllBranches) {
            $bank_store_id = $userStoreId;
        }

        if ($bank_id > 0 && !empty($bank_name)) {
            try {
                // A sub-branch can only touch its own accounts by id; a canAllBranches user
                // (main branch / admin) can edit any — same rule promotions uses.
                $ownerSql = "";
                $ownerParams = [];
                if (!$canAllBranches) {
                    $ownerSql = " AND store_id = ?";
                    $ownerParams = [$userStoreId];
                }

                // ດຶງຮູບເກົ່າກ່ອນ update
                $oldRow = $pdo->prepare("SELECT bank_logo, qr_code_img FROM st_bank_accounts WHERE id = ?{$ownerSql}");
                $oldRow->execute(array_merge([$bank_id], $ownerParams));
                $oldData = $oldRow->fetch(PDO::FETCH_ASSOC);

                $pdo->prepare("UPDATE st_bank_accounts SET bank_name = ?, account_number = ?, account_name = ?, bank_code = ?, is_active = ?, store_id = ? WHERE id = ?{$ownerSql}")
                    ->execute(array_merge([$bank_name, $account_number, $account_name, $bank_code, $is_active, $bank_store_id, $bank_id], $ownerParams));

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
                        $pdo->prepare("UPDATE st_bank_accounts SET bank_logo = ? WHERE id = ?")
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
                        $pdo->prepare("UPDATE st_bank_accounts SET qr_code_img = ? WHERE id = ?")
                            ->execute([$newQr, $bank_id]);
                    }
                }

                // ລົບຮູບທີ່ກົດ × ໃນຟອມ (ເມື່ອບໍ່ໄດ້ upload ຮູບໃໝ່ແທນ)
                if ($oldData) {
                    $imgDirs = ['bank_logo' => 'banks', 'qr_code_img' => 'qr'];
                    foreach ($imgDirs as $col => $dir) {
                        $replaced = !empty($_FILES[$col]['name']) && $_FILES[$col]['error'] === UPLOAD_ERR_OK;
                        if (($_POST['remove_' . $col] ?? '0') !== '1' || $replaced) continue;
                        $oldFile = __DIR__ . '/../assets/img/' . $dir . '/' . basename((string)$oldData[$col]);
                        if (!empty($oldData[$col]) && is_file($oldFile)) {
                            @unlink($oldFile);
                        }
                        $pdo->prepare("UPDATE st_bank_accounts SET {$col} = NULL WHERE id = ?")
                            ->execute([$bank_id]);
                    }
                }

                $message = tl('ແກ້ໄຂຂໍ້ມູນບັນຊີທະນາຄານ ສໍາເລັດແລ້ວ!');
                $message_type = "success";
                logActivity($pdo, "ແກ້ໄຂບັນຊີທະນາຄານ", "$bank_name ($account_number)");
            } catch (Exception $e) {
                $message = (tl('ຜິດພາດ:') . ' ') . $e->getMessage();
                $message_type = "danger";
            }
        }
    }

    // 3. DELETE BANK ACCOUNT
    if ($action === 'delete_bank_account') {
        $bank_id = intval($_POST['bank_id'] ?? 0);
        if ($bank_id > 0) {
            try {
                // Same ownership rule as edit: a sub-branch can't delete another branch's (or a
                // shared/global) bank by guessing its id; a canAllBranches user can delete any.
                $ownerSql = "";
                $ownerParams = [];
                if (!$canAllBranches) {
                    $ownerSql = " AND store_id = ?";
                    $ownerParams = [$userStoreId];
                }

                // ດຶງຊື່ໄຟລ໌ຮູບກ່ອນລົບ record
                $delRow = $pdo->prepare("SELECT bank_logo, qr_code_img FROM st_bank_accounts WHERE id = ?{$ownerSql}");
                $delRow->execute(array_merge([$bank_id], $ownerParams));
                $delData = $delRow->fetch(PDO::FETCH_ASSOC);

                $pdo->prepare("DELETE FROM st_bank_accounts WHERE id = ?{$ownerSql}")->execute(array_merge([$bank_id], $ownerParams));

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

                $message = tl('ລົບບັນຊີທະນາຄານ ສໍາເລັດແລ້ວ!');
                $message_type = "success";
                logActivity($pdo, "ລົບບັນຊີທະນາຄານ", "ID: $bank_id");
            } catch (Exception $e) {
                $message = (tl('ຜິດພາດ:') . ' ') . $e->getMessage();
                $message_type = "danger";
            }
        }
    }
}

// Date Range & Store/Branch Filter parameters for revenue breakdown (Default to all sales history)
$start_date = $_GET['start_date'] ?? '2000-01-01';
$end_date   = $_GET['end_date'] ?? date('Y-m-d');
$filter_store_id = isset($_GET['store_id']) && $_GET['store_id'] !== '' ? intval($_GET['store_id']) : (isset($_GET['branch_id']) && $_GET['branch_id'] !== '' ? intval($_GET['branch_id']) : intval($_SESSION['store_id'] ?? 1));
if (!canViewAllBranches($pdo)) {
    $filter_store_id = intval($_SESSION['store_id'] ?? 1);
}

// Fetch active branches for store filter dropdown
$branches_list = [];
try {
    $branches_stmt = $pdo->query("SELECT store_id, store_code, store_name, is_main, status FROM st_tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC");
    $branches_list = $branches_stmt ? $branches_stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Exception $e) {}
// ຮ້ານທີ່ມີສາຂາດຽວ: ບໍ່ຕ້ອງສະແດງຫຍັງກ່ຽວກັບສາຂາ (ບັອກເລືອກສາຂາ, ປ້າຍສາຂາໃນບັດທະນາຄານ, ຊ່ອງສາຂາໃນຟອມ)
$bankMultiBranch = count($branches_list) > 1;

