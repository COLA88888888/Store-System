<?php
// ============================================================
// database_backend.php - BACKEND HANDLER FOR DATABASE BACKUP & RESTORE
// ------------------------------------------------------------
// ສຳຮອງໄດ້ 2 ແບບ:
//   ທັງໝົດ (store_id = 0) — DROP + CREATE + INSERT ທຸກຕາຕະລາງ; ຟື້ນຟູ = ແທນທີ່ຖານຂໍ້ມູນທັງໝົດ
//   ສາຂາດຽວ (store_id > 0) — ສະເພາະແຖວຂອງສາຂານັ້ນ: DELETE ແຖວຂອງສາຂາ ແລ້ວ INSERT IGNORE ຄືນ;
//     ຟື້ນຟູ = ແທນທີ່ຂໍ້ມູນສາຂານັ້ນເທົ່ານັ້ນ, ສາຂາອື່ນ ແລະ ຂໍ້ມູນໃຊ້ຮ່ວມ (ຜູ້ໃຊ້, ການຕັ້ງຄ່າ) ບໍ່ຖືກແຕະ.
//     ແຖວທຳອິດຂອງໄຟລ໌ = "-- StoreMini branch backup: store_id=N" (backupFileStoreId ອ່ານອັນນີ້)
// ຜູ້ທີ່ບໍ່ຜ່ານ canViewAllBranches() ສຳຮອງ/ຟື້ນຟູ/ລົບ/ດາວໂຫຼດ ໄດ້ສະເພາະໄຟລ໌ຂອງສາຂາຕົນເອງ.
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Calculate Base Path & Include DB
require_once __DIR__ . '/../config/db.php';

// 2. Check Permissions
if (empty($_SESSION['user_id']) || (!hasPermission('database'))) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => tl('ທ່ານບໍ່ມີສິດເຂົ້າເຖິງການຈັດການຖານຂໍ້ມູນ!')]);
    exit();
}

$backupDir = __DIR__ . '/../backups/';
if (!file_exists($backupDir)) {
    @mkdir($backupDir, 0777, true);
}

$dbCanAllBranches = canViewAllBranches($pdo);
$dbOwnStoreId = (int)getActiveStoreId($pdo);

// ຟັງຊັນສຳຮອງ/ຟື້ນຟູ (export, ເງື່ອນໄຂສາຂາ, ແຍກຄຳສັ່ງ SQL, ຟື້ນຟູ + ກວດຈຳນວນແຖວ)
require_once __DIR__ . '/database/backup_functions.php';

// ຜູ້ໃຊ້ສາຂາຍ່ອຍ ແຕະໄດ້ສະເພາະໄຟລ໌ຂອງສາຂາຕົນເອງ
function backupFileAllowed($path) {
    global $dbCanAllBranches, $dbOwnStoreId;
    return $dbCanAllBranches || backupFileStoreId($path) === $dbOwnStoreId;
}

// ຄຳຕອບຫຼັງຟື້ນຟູ: ສຳເລັດ = ທຸກຄຳສັ່ງຜ່ານ ແລະ ຈຳນວນແຖວທຸກຕາຕະລາງກົງກັບຕອນສຳຮອງ;
// ບໍ່ດັ່ງນັ້ນ ບອກຄົບວ່າຕາຕະລາງໃດຂາດ ແລະ ຄຳສັ່ງໃດຜິດພາດ (ບໍ່ລາຍງານວ່າສຳເລັດທັງທີ່ຂໍ້ມູນບໍ່ຄົບ)
function restoreResponse($res, $label) {
    if ($res['statements'] === 0) {
        return ['success' => false, 'message' => tl('ເກີດຂໍ້ຜິດພາດໃນການຟື້ນຟູຖານຂໍ້ມູນ!')];
    }
    if ($res['ok']) {
        // ໄຟລ໌ເກົ່າ (ກ່ອນມີແຖວ "-- rows") ບໍ່ມີຈຳນວນໃຫ້ກວດ → ບອກແຕ່ວ່າສຳເລັດ
        $verified = empty($res['tables']) ? '' : ' ' . sprintf(t('database.restore_verified', 'ກວດແລ້ວ: ຂໍ້ມູນກັບມາຄົບຄືຕອນສຳຮອງ (%s ຕາຕະລາງ)'), number_format(count($res['tables'])));
        return ['success' => true, 'message' => tl('ຟື້ນຟູຖານຂໍ້ມູນຈາກ') . " ({$label}) " . tl('ສຳເລັດ!') . $verified];
    }
    $lines = [];
    foreach ($res['mismatch'] as $tb => [$want, $got]) {
        $lines[] = sprintf(t('database.restore_mismatch_line', '%s: ສຳຮອງ %s ແຖວ, ຫຼັງຟື້ນຟູ %s ແຖວ'), $tb, number_format($want), $got < 0 ? '?' : number_format($got));
    }
    if ($res['failed'] > 0) {
        $lines[] = sprintf(t('database.restore_failed_line', 'ຄຳສັ່ງຜິດພາດ %s ຄຳສັ່ງ: %s'), number_format($res['failed']), mb_substr($res['first_error'], 0, 200));
    }
    return ['success' => false, 'partial' => true,
        'message' => t('database.restore_incomplete', 'ຟື້ນຟູແລ້ວ ແຕ່ຂໍ້ມູນບໍ່ຄົບຄືຕອນສຳຮອງ:') . "\n" . implode("\n", $lines)];
}

// Direct File Download Handler
if (isset($_GET['action']) && $_GET['action'] === 'download_backup') {
    $filename = basename($_GET['file'] ?? '');
    $filePath = $backupDir . $filename;
    if (!empty($filename) && file_exists($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'sql' && backupFileAllowed($filePath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit();
    } else {
        die(tl('ໄຟລ໌ບໍ່ມີຢູ່ ຫຼື Path ບໍ່ຖືກຕ້ອງ'));
    }
}

// Handle AJAX Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    // Action 1: Create Backup (store_id: 0 = ທັງໝົດ, N = ສາຂາ N)
    if ($action === 'create_backup') {
        $dbName = $database ?? 'storepos';
        $storeId = $dbCanAllBranches ? max(0, (int)($_POST['store_id'] ?? 0)) : $dbOwnStoreId;

        if ($storeId > 0) {
            $sStmt = $pdo->prepare('SELECT store_name FROM st_tbstore WHERE store_id = ? LIMIT 1');
            $sStmt->execute([$storeId]);
            $storeName = $sStmt->fetchColumn();
            if ($storeName === false) {
                echo json_encode(['success' => false, 'message' => t('database.err_branch_invalid', 'ບໍ່ພົບສາຂາທີ່ເລືອກ')]);
                exit();
            }
            $filename = 'minipos_backup_branch' . $storeId . '_' . date('Y-m-d_H-i-s') . '.sql';
            $ok = exportBranchSQL($pdo, $storeId, $storeName, $backupDir . $filename);
        } else {
            $filename = 'minipos_backup_' . date('Y-m-d_H-i-s') . '.sql';
            $ok = exportDatabaseSQL($pdo, $dbName, $backupDir . $filename);
        }

        if ($ok) {
            logActivity($pdo, "ສ້າງສຳຮອງຖານຂໍ້ມູນ", "ສ້າງໄຟລ໌: {$filename}");
            echo json_encode(['success' => true, 'message' => ("" . tl('ສ້າງໄຟລ໌ສຳຮອງ') . " ({$filename}) " . tl('ສຳເລັດ!') . ""), 'filename' => $filename]);
        } else {
            echo json_encode(['success' => false, 'message' => tl('ບໍ່ສາມາດສ້າງໄຟລ໌ສຳຮອງໄດ້!')]);
        }
        exit();
    }

    // Action 2: Delete Backup
    if ($action === 'delete_backup') {
        $filename = basename($_POST['filename'] ?? '');
        $filePath = $backupDir . $filename;
        if (!empty($filename) && file_exists($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'sql' && backupFileAllowed($filePath)) {
            unlink($filePath);
            logActivity($pdo, "ລົບໄຟລ໌ສຳຮອງຖານຂໍ້ມູນ", "ລົບໄຟລ໌: {$filename}");
            echo json_encode(['success' => true, 'message' => ("" . tl('ລົບໄຟລ໌ສຳຮອງ') . " ({$filename}) " . tl('ສຳເລັດ!') . "")]);
        } else {
            echo json_encode(['success' => false, 'message' => tl('ບໍ່ພົບໄຟລ໌ສຳຮອງທີ່ຕ້ອງການລົບ!')]);
        }
        exit();
    }

    // Action 3: Restore Backup (From Existing File)
    if ($action === 'restore_backup') {
        $filename = basename($_POST['filename'] ?? '');
        $filePath = $backupDir . $filename;
        if (!empty($filename) && file_exists($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'sql' && backupFileAllowed($filePath)) {
            $res = restoreDatabaseSQL($pdo, $filePath);
            logActivity($pdo, "ຟື້ນຟູຖານຂໍ້ມູນ", "ຟື້ນຟູຈາກໄຟລ໌: {$filename}");
            echo json_encode(restoreResponse($res, $filename));
        } else {
            echo json_encode(['success' => false, 'message' => tl('ບໍ່ພົບໄຟລ໌ສຳຮອງ!')]);
        }
        exit();
    }

    // Action 4: Restore Backup (From Uploaded File)
    if ($action === 'upload_restore') {
        $upErr = (int)($_FILES['backup_file']['error'] ?? UPLOAD_ERR_NO_FILE);
        if (in_array($upErr, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            echo json_encode(['success' => false, 'message' => sprintf(t('database.err_upload_too_big', 'ໄຟລ໌ໃຫຍ່ເກີນກຳນົດຂອງເຊີເວີ (%s)'), ini_get('upload_max_filesize'))]);
            exit();
        }
        if (!empty($_FILES['backup_file']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['backup_file']['name'], PATHINFO_EXTENSION));
            if ($ext !== 'sql') {
                echo json_encode(['success' => false, 'message' => tl('ກະລຸນາເລືອກໄຟລ໌ .sql ເທົ່ານັ້ນ!')]);
                exit();
            }
            $tmpPath = $_FILES['backup_file']['tmp_name'];
            if (!backupFileAllowed($tmpPath)) {
                echo json_encode(['success' => false, 'message' => t('database.err_other_branch_file', 'ທ່ານຟື້ນຟູໄດ້ສະເພາະໄຟລ໌ສຳຮອງຂອງສາຂາຕົນເອງ')]);
                exit();
            }
            $origName = basename($_FILES['backup_file']['name']);
            $res = restoreDatabaseSQL($pdo, $tmpPath);
            logActivity($pdo, "ຟື້ນຟູຖານຂໍ້ມູນອັບໂຫຼດ", "ຟື້ນຟູຈາກໄຟລ໌: {$origName}");
            echo json_encode(restoreResponse($res, $origName));
        } else {
            echo json_encode(['success' => false, 'message' => tl('ກະລຸນາເລືອກໄຟລ໌ SQL ທີ່ຈະອັບໂຫຼດ!')]);
        }
        exit();
    }
}
