<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

// Check authorization
if (empty($_SESSION['user_id']) || (!hasPermission('users'))) {
    echo json_encode(['status' => 'error', 'message' => t('users_manage.api_no_permission')]);
    exit();
}

// Users who can't see every branch may only list, create, edit or delete accounts in their own branch.
$canAllBranches = canViewAllBranches($pdo);
$userStoreId = intval($_SESSION['store_id'] ?? 1);

function handleUserProfileUpload($fileInputName, $existingImg = 'default.png') {
    if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES[$fileInputName]['tmp_name'];
        $fileName = $_FILES[$fileInputName]['name'];
        $fileSize = $_FILES[$fileInputName]['size'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExtension, $allowedExtensions) && $fileSize <= 2 * 1024 * 1024) {
            $newFileName = 'user_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
            $uploadFileDir = __DIR__ . '/../assets/img/users/';
            if (!is_dir($uploadFileDir)) {
                @mkdir($uploadFileDir, 0777, true);
            }
            $dest_path = $uploadFileDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // Delete old profile image if not default
                if ($existingImg && $existingImg !== 'default.png' && file_exists($uploadFileDir . $existingImg)) {
                    @unlink($uploadFileDir . $existingImg);
                }
                return $newFileName;
            }
        }
    }
    return $existingImg;
}

function parseLaoDateToSql($rawDate) {
    $rawDate = trim($rawDate ?? '');
    if (empty($rawDate)) return null;
    if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $rawDate, $m)) {
        return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) {
        return $rawDate;
    }
    return $rawDate;
}

// Only the owner (Id 1) may grant or change how many days an account is allowed to use the
// system. $rawDays is the "days from today" value posted by the (owner-only) form field;
// returns [changed, value] — changed=false means "leave the column exactly as it is".
function resolveAccessExpireDate($rawDays) {
    if (!isOwnerUser()) {
        return [false, null];
    }
    $rawDays = trim((string)($rawDays ?? ''));
    if ($rawDays === '') {
        return [true, null]; // owner cleared the field -> unlimited
    }
    $days = intval($rawDays);
    if ($days <= 0) {
        return [false, null];
    }
    return [true, date('Y-m-d', strtotime("+{$days} days"))];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// === 0. GET LIVE USER STATUSES (Real-time Polling) ===
if ($action === 'get_live_user_statuses') {
    try {
        $stmt = $pdo->query("SELECT Id, status, last_activity, account_active FROM st_tbuser" . ($canAllBranches ? '' : ' WHERE store_id = ' . $userStoreId));
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        $now = time();
        foreach ($users as $u) {
            $isOnline = false;
            if (!empty($u['last_activity'])) {
                $lastActiveTime = strtotime($u['last_activity']);
                if (($now - $lastActiveTime) <= 300) {
                    $isOnline = true;
                }
            }
            $result[] = [
                'id' => (int)$u['Id'],
                'is_online' => $isOnline,
                'account_active' => (int)($u['account_active'] ?? 1),
                'status' => trim($u['status'] ?? $u['userstatus'] ?? 'ພະນັກງານ')
            ];
        }
        echo json_encode(['status' => 'success', 'users' => $result]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}

// === 0.1 GET NEXT USER CODE (GET request) ===
if ($action === 'get_next_user_code') {
    // ລະຫັດຜູ້ໃຊ້ອໍໂຕ້ແຍກຕາມສາຂາ "<ລະຫັດສາຂາ>-<ລຳດັບ>" (1-1, 1-2 …) ຕາມສາຂາທີ່ເລືອກໃນຟອມ
    $codeStoreId = $canAllBranches ? intval($_GET['store_id'] ?? $userStoreId) : $userStoreId;
    if ($codeStoreId <= 0) $codeStoreId = $userStoreId;
    ensureBranchNos($pdo, 'st_tbuser');
    $nextCode = branchCodeLabel($pdo, $codeStoreId, nextBranchNo($pdo, 'st_tbuser', $codeStoreId));
    echo json_encode(['status' => 'success', 'code' => $nextCode]);
    exit();
}

// === 1. ADD USER ===
if ($action === 'add_user') {
    $user_code = trim($_POST['user_code'] ?? '');
    $fname = trim($_POST['fname'] ?? '');
    $lname = '';
    $gender = trim($_POST['gender'] ?? 'ຊາຍ');
    $dob = parseLaoDateToSql($_POST['dob'] ?? '');
    $tel = trim($_POST['tel'] ?? '');
    $userstatus = $_POST['status'] ?? $_POST['userstatus'] ?? 'ພະນັກງານ';
    // Use the displayed user name as the login name.
    $username = $fname;
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (empty($user_code) || empty($fname) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => t('users_manage.api_required')]);
        exit();
    }

    if ($password !== $confirm_password) {
        echo json_encode(['status' => 'error', 'message' => t('users_manage.api_pw_mismatch')]);
        exit();
    }

    try {
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM st_tbuser WHERE username = ?");
        $stmtCheck->execute([$username]);
        if ($stmtCheck->fetchColumn() > 0) {
            echo json_encode(['status' => 'error', 'message' => t('users_manage.api_name_taken')]);
            exit();
        }

        $sale = 0; $stock = 0; $report = 0; $accounting = 0; $setup = 0; $users_perm = 0; $edit = 0;
        if ($userstatus === 'ຜູ້ບໍລິຫານ' || $userstatus === 'Admin') {
            $sale = 1; $stock = 1; $report = 1; $accounting = 1; $setup = 1; $users_perm = 1; $edit = 1;
        } elseif ($userstatus === 'ຄົນຈັດການບັນຊີ' || $userstatus === 'ຜູ້ກວດສອບ') {
            $report = 1; $accounting = 1;
        } elseif ($userstatus === 'ພະນັກງານຂາຍ' || $userstatus === 'ຄົນຂາຍ') {
            $sale = 1;
        } elseif ($userstatus === 'ພະນັກງານຄັງ') {
            $stock = 1; $edit = 1;
        } else {
            $sale = 1; $stock = 1; $edit = 1;
        }

        $store_id = $canAllBranches ? intval($_POST['store_id'] ?? 1) : $userStoreId;
        // ສາຂາທີ່ສົ່ງມາຕ້ອງມີຢູ່ຈິງ (ບໍ່ດັ່ງນັ້ນຜູ້ໃຊ້ຈະຢູ່ສາຂາທີ່ບໍ່ມີ ແລະ ບໍ່ເຫັນຂໍ້ມູນຫຍັງເລີຍ)
        $__stOk = $pdo->prepare("SELECT COUNT(*) FROM st_tbstore WHERE store_id = ?");
        $__stOk->execute([$store_id]);
        if (!$__stOk->fetchColumn()) {
            echo json_encode(['status' => 'error', 'message' => t('users_manage.api_bad_branch')]);
            exit();
        }
        $profile_img = handleUserProfileUpload('profile_img', 'default.png');
        [, $accessExpireDate] = resolveAccessExpireDate($_POST['access_days'] ?? '');

        // ລຳດັບຜູ້ໃຊ້ໃນສາຂານີ້. ລະຫັດອໍໂຕ້ ("1-3") ສ້າງໃໝ່ຢູ່ server ຕາມສາຂາທີ່ບັນທຶກແທ້ (ບໍ່ຊ້ຳ);
        // ລະຫັດທີ່ພິມເອງ (ຮູບແບບອື່ນ) ເກັບໄວ້ຕາມເດີມ.
        ensureBranchNos($pdo, 'st_tbuser');
        $userBranchNo = nextBranchNo($pdo, 'st_tbuser', $store_id);
        if (preg_match('/^[0-9A-Za-z]+-[0-9]+$/', $user_code)) {
            $user_code = branchCodeLabel($pdo, $store_id, $userBranchNo);
        }

        $stmt = $pdo->prepare("INSERT INTO st_tbuser (user_code, fname, lname, gender, dob, tel, status, username, password, address, notes, profile_img, sale, stock, report, accounting, setup, users, edit, store_id, branch_no, access_expire_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_code, $fname, $lname, $gender, $dob, $tel, $userstatus, $username, password_hash($password, PASSWORD_DEFAULT), $address, $notes, $profile_img, $sale, $stock, $report, $accounting, $setup, $users_perm, $edit, $store_id, $userBranchNo, $accessExpireDate]);
        // ຕຳແໜ່ງຜູ້ຈັດການ: ໃສ່ສິດເລີ່ມຕົ້ນຂອງຜູ້ຈັດການ (config/helpers/permissions.php)
        if (isManagerRole($userstatus)) {
            applyManagerPermissions($pdo, (int)$pdo->lastInsertId());
        }

        logActivity($pdo, "ເພີ່ມຜູ້ໃຊ້", "ຊື່: $username, ຕຳແໜ່ງ: $userstatus");
        echo json_encode(['status' => 'success', 'message' => t('users_manage.api_added')]);
        exit();
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => t('users_manage.api_error_prefix') . ' ' . $e->getMessage()]);
        exit();
    }
}

// === 2. EDIT USER ===
if ($action === 'edit_user') {
    $user_id = intval($_POST['user_id'] ?? 0);
    $user_code = trim($_POST['user_code'] ?? '');
    $fname = trim($_POST['fname'] ?? '');
    $lname = '';
    $gender = trim($_POST['gender'] ?? 'ຊາຍ');
    $dob = parseLaoDateToSql($_POST['dob'] ?? '');
    $tel = trim($_POST['tel'] ?? '');
    $userstatus = $_POST['status'] ?? $_POST['userstatus'] ?? 'ພະນັກງານ';
    // Keep the account's login name in sync with the displayed user name.
    $username = $fname;
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($user_id <= 0 || empty($fname)) {
        echo json_encode(['status' => 'error', 'message' => t('users_manage.api_invalid')]);
        exit();
    }
    // Only the owner (Id 1) may edit ANOTHER admin-labeled account — but any admin may still
    // edit their own record (change their own password, phone, etc.), just never someone else's.
    $isEditingSelf = ($user_id === intval($_SESSION['user_id'] ?? 0));
    if (!isOwnerUser() && !$isEditingSelf && isAdminAccount($pdo, $user_id)) {
        echo json_encode(['status' => 'error', 'message' => t('users_manage.api_only_owner_edit')]);
        exit();
    }

    if (!empty($password) && $password !== $confirm_password) {
        echo json_encode(['status' => 'error', 'message' => t('users_manage.api_pw_mismatch')]);
        exit();
    }

    try {
        $usernameCheck = $pdo->prepare("SELECT COUNT(*) FROM st_tbuser WHERE username = ? AND Id <> ?");
        $usernameCheck->execute([$username, $user_id]);
        if ($usernameCheck->fetchColumn() > 0) {
            echo json_encode(['status' => 'error', 'message' => t('users_manage.api_name_taken')]);
            exit();
        }

        $cStmt = $pdo->prepare("SELECT profile_img, status, sale, stock, report, accounting, setup, users, edit, store_id, access_expire_date FROM st_tbuser WHERE Id = ?");
        $cStmt->execute([$user_id]);
        $currUser = $cStmt->fetch();
        if (!$currUser || (!$canAllBranches && intval($currUser['store_id']) !== $userStoreId)) {
            echo json_encode(['status' => 'error', 'message' => t('users_manage.api_not_found')]);
            exit();
        }

        $currImg = $currUser['profile_img'] ?: 'default.png';
        $sale = intval($currUser['sale'] ?? 1);
        $stock = intval($currUser['stock'] ?? 1);
        $report = intval($currUser['report'] ?? 0);
        $accounting = intval($currUser['accounting'] ?? 0);
        $setup = intval($currUser['setup'] ?? 0);
        $users_perm = intval($currUser['users'] ?? 0);
        $edit = intval($currUser['edit'] ?? 1);

        if ($userstatus === 'ຜູ້ບໍລິຫານ' || $userstatus === 'Admin') {
            $sale = 1; $stock = 1; $report = 1; $accounting = 1; $setup = 1; $users_perm = 1; $edit = 1;
        }

        $remove_flag = intval($_POST['remove_profile_img'] ?? 0);
        if ($remove_flag === 1) {
            if ($currImg && $currImg !== 'default.png' && file_exists(__DIR__ . '/../assets/img/users/' . $currImg)) {
                @unlink(__DIR__ . '/../assets/img/users/' . $currImg);
            }
            $profile_img = 'default.png';
        } else {
            $profile_img = handleUserProfileUpload('profile_img', $currImg);
        }

        $store_id = $canAllBranches ? intval($_POST['store_id'] ?? 1) : $userStoreId;
        // ສາຂາທີ່ສົ່ງມາຕ້ອງມີຢູ່ຈິງ (ບໍ່ດັ່ງນັ້ນຜູ້ໃຊ້ຈະຢູ່ສາຂາທີ່ບໍ່ມີ ແລະ ບໍ່ເຫັນຂໍ້ມູນຫຍັງເລີຍ)
        $__stOk = $pdo->prepare("SELECT COUNT(*) FROM st_tbstore WHERE store_id = ?");
        $__stOk->execute([$store_id]);
        if (!$__stOk->fetchColumn()) $store_id = intval($currUser['store_id']);   // ສາຂາບໍ່ມີ → ຮັກສາສາຂາເດີມ

        // Id 1 can never be given an expiry; anyone else's is only touched when the current
        // user is the owner and actually posted the (owner-only) access_days field.
        if ($user_id === 1) {
            $accessExpireDate = null;
        } else {
            [$expireChanged, $newExpireDate] = resolveAccessExpireDate($_POST['access_days'] ?? null);
            $accessExpireDate = $expireChanged ? $newExpireDate : $currUser['access_expire_date'];
        }

        if ($password !== '') {
            $stmt = $pdo->prepare("UPDATE st_tbuser SET user_code = ?, fname = ?, lname = ?, gender = ?, dob = ?, tel = ?, status = ?, username = ?, password = ?, address = ?, notes = ?, profile_img = ?, sale = ?, stock = ?, report = ?, accounting = ?, setup = ?, users = ?, edit = ?, store_id = ?, access_expire_date = ? WHERE Id = ?");
            $stmt->execute([$user_code, $fname, $lname, $gender, $dob, $tel, $userstatus, $username, password_hash($password, PASSWORD_DEFAULT), $address, $notes, $profile_img, $sale, $stock, $report, $accounting, $setup, $users_perm, $edit, $store_id, $accessExpireDate, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE st_tbuser SET user_code = ?, fname = ?, lname = ?, gender = ?, dob = ?, tel = ?, status = ?, username = ?, address = ?, notes = ?, profile_img = ?, sale = ?, stock = ?, report = ?, accounting = ?, setup = ?, users = ?, edit = ?, store_id = ?, access_expire_date = ? WHERE Id = ?");
            $stmt->execute([$user_code, $fname, $lname, $gender, $dob, $tel, $userstatus, $username, $address, $notes, $profile_img, $sale, $stock, $report, $accounting, $setup, $users_perm, $edit, $store_id, $accessExpireDate, $user_id]);
        }

        // ປ່ຽນຕຳແໜ່ງ: ເປັນຜູ້ຈັດການ → ໃສ່ສິດຜູ້ຈັດການ; ຈາກຜູ້ຈັດການເປັນພະນັກງານ → ເອົາສິດຄືນ.
        // ບໍ່ປ່ຽນຕຳແໜ່ງ = ບໍ່ແຕະສິດ (ສິດທີ່ຜູ້ບໍລິຫານປັບລາຍຄົນໄວ້ຍັງຢູ່).
        $wasManager = isManagerRole($currUser['status'] ?? '');
        $isManagerNow = isManagerRole($userstatus);
        if ($isManagerNow && !$wasManager) {
            applyManagerPermissions($pdo, $user_id);
        } elseif ($wasManager && !$isManagerNow && !isAdminAccount($pdo, $user_id)) {
            revokeManagerPermissions($pdo, $user_id);
        }

        logActivity($pdo, "ແກ້ໄຂຜູ້ໃຊ້", "ID: $user_id, ຊື່: $username");
        echo json_encode(['status' => 'success', 'message' => t('users_manage.api_updated')]);
        exit();
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => t('users_manage.api_error_prefix') . ' ' . $e->getMessage()]);
        exit();
    }
}

// === 3. DELETE USER ===
if ($action === 'delete_user') {
    $user_id = intval($_POST['user_id'] ?? 0);
    if ($user_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => t('users_manage.api_invalid')]);
        exit();
    }
    // Nobody — admin or not — may delete the account they're currently logged in as.
    if ($user_id === intval($_SESSION['user_id'] ?? 0)) {
        echo json_encode(['status' => 'error', 'message' => t('users_manage.api_cannot_delete_self')]);
        exit();
    }
    if ($user_id === 1) {
        echo json_encode(['status' => 'error', 'message' => t('users_manage.api_cannot_delete_owner')]);
        exit();
    }
    if (!isOwnerUser() && isAdminAccount($pdo, $user_id)) {
        echo json_encode(['status' => 'error', 'message' => t('users_manage.api_only_owner_delete')]);
        exit();
    }

    try {
        $uStmt = $pdo->prepare("SELECT profile_img, store_id FROM st_tbuser WHERE Id = ?");
        $uStmt->execute([$user_id]);
        $uRow = $uStmt->fetch(PDO::FETCH_ASSOC);
        if (!$uRow || (!$canAllBranches && intval($uRow['store_id']) !== $userStoreId)) {
            echo json_encode(['status' => 'error', 'message' => t('users_manage.api_not_found')]);
            exit();
        }
        $uImg = $uRow['profile_img'];
        if ($uImg && $uImg !== 'default.png') {
            $uPath = __DIR__ . '/../assets/img/users/' . $uImg;
            if (file_exists($uPath)) {
                @unlink($uPath);
            }
        }

        $stmt = $pdo->prepare("DELETE FROM st_tbuser WHERE Id = ?");
        $stmt->execute([$user_id]);
        logActivity($pdo, "ລົບຜູ້ໃຊ້", "ID: $user_id");
        echo json_encode(['status' => 'success', 'message' => t('users_manage.api_deleted')]);
        exit();
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => t('users_manage.api_error_prefix') . ' ' . $e->getMessage()]);
        exit();
    }
}

// === 4. ເປີດ / ປິດໃຊ້ງານບັນຊີ (ພະນັກງານລາອອກ) ===
// ປິດແທນການລົບ: ເຂົ້າລະບົບບໍ່ໄດ້ອີກ ແຕ່ປະຫວັດການຂາຍ/ບິນທີ່ຜູກກັບ user ນີ້ຍັງຢູ່ຄົບ
if ($action === 'toggle_user_active' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id'] ?? 0);
    $active = intval($_POST['active'] ?? 0) === 1 ? 1 : 0;
    if (!hasPermission('users', 'edit') || $user_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => t('users_manage.api_no_permission')]);
        exit();
    }
    if ($user_id === 1) {
        echo json_encode(['status' => 'error', 'message' => t('users_manage.api_cannot_disable_owner')]);
        exit();
    }
    if ($user_id === intval($_SESSION['user_id'] ?? 0)) {
        echo json_encode(['status' => 'error', 'message' => t('users_manage.api_cannot_disable_self')]);
        exit();
    }
    if (!isOwnerUser() && isAdminAccount($pdo, $user_id)) {
        echo json_encode(['status' => 'error', 'message' => t('users_manage.api_only_owner_disable')]);
        exit();
    }

    try {
        $uStmt = $pdo->prepare("SELECT username, store_id FROM st_tbuser WHERE Id = ?");
        $uStmt->execute([$user_id]);
        $uRow = $uStmt->fetch(PDO::FETCH_ASSOC);
        if (!$uRow || (!$canAllBranches && intval($uRow['store_id']) !== $userStoreId)) {
            echo json_encode(['status' => 'error', 'message' => t('users_manage.api_not_found')]);
            exit();
        }

        $pdo->prepare("UPDATE st_tbuser SET account_active = ? WHERE Id = ?")->execute([$active, $user_id]);
        logActivity($pdo, $active ? "ເປີດໃຊ້ງານບັນຊີຜູ້ໃຊ້" : "ປິດໃຊ້ງານບັນຊີຜູ້ໃຊ້", "ID: $user_id, ຊື່: " . $uRow['username']);
        echo json_encode(['status' => 'success', 'message' => $active ? t('users_manage.api_enabled') : t('users_manage.api_disabled')]);
        exit();
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => t('users_manage.api_error_prefix') . ' ' . $e->getMessage()]);
        exit();
    }
}

echo json_encode(['status' => 'error', 'message' => t('users_manage.api_bad_action')]);
