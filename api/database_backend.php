<?php
// ============================================================
// database_backend.php - BACKEND HANDLER FOR DATABASE BACKUP & RESTORE
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Calculate Base Path & Include DB
require_once __DIR__ . '/../config/db.php';

// 2. Check Permissions
if (empty($_SESSION['user_id']) || (!hasPermission('database') && !hasPermission('setup') && !hasPermission('permissions'))) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ທ່ານບໍ່ມີສິດເຂົ້າເຖິງການຈັດການຖານຂໍ້ມູນ!']);
    exit();
}

$backupDir = __DIR__ . '/../backups/';
if (!file_exists($backupDir)) {
    @mkdir($backupDir, 0777, true);
}

// ປ່ຽນ charset/collation ທຸກບ່ອນໃນ CREATE ໃຫ້ເປັນ utf8mb4_general_ci ຢ່າງດຽວ
// (ຖານຂໍ້ມູນເກົ່າມີປົນກັນ: utf8mb4_0900_ai_ci, utf8mb4_unicode_ci, utf8mb3_unicode_ci)
function normalizeCollationSQL($createSql) {
    $createSql = preg_replace('/\bCHARACTER SET (utf8mb3|utf8mb4|utf8)\b/i', 'CHARACTER SET utf8mb4', $createSql);
    $createSql = preg_replace('/\bCOLLATE([ =])(utf8mb3|utf8mb4|utf8)_\w+/i', 'COLLATE$1utf8mb4_general_ci', $createSql);
    $createSql = preg_replace(
        '/\bDEFAULT CHARSET=\w+(\s+COLLATE=\w+)?/i',
        'DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci',
        $createSql
    );
    return $createSql;
}

// Export database function (ທຸກຕາຕະລາງ + ຂໍ້ມູນທັງໝົດ + View, ເປັນ utf8mb4_general_ci)
function exportDatabaseSQL($pdo, $dbname, $outputPath) {
    $fh = @fopen($outputPath, 'wb');
    if (!$fh) return false;

    try {
        fwrite($fh, "-- MiniPOS Database Backup\n");
        fwrite($fh, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
        fwrite($fh, "-- Database: {$dbname}\n");
        fwrite($fh, "-- Collation: utf8mb4_general_ci\n\n");
        fwrite($fh, "SET NAMES utf8mb4 COLLATE utf8mb4_general_ci;\n");
        fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n");
        fwrite($fh, "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

        $objects = $pdo->query("SHOW FULL TABLES")->fetchAll(PDO::FETCH_NUM);
        $views = [];

        foreach ($objects as [$table, $type]) {
            if ($type === 'VIEW') {
                $views[] = $table;
                continue;
            }
            $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
            if (!$createStmt) continue;

            fwrite($fh, "-- Table: {$table}\n");
            fwrite($fh, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($fh, normalizeCollationSQL($createStmt[1]) . ";\n\n");

            // ຂ້າມ generated column, ແລະ ເກັບ binary/blob ເປັນ hex ເພື່ອບໍ່ໃຫ້ໄຟລ໌ເສຍ
            $insertCols = [];
            $binaryCols = [];
            foreach ($pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC) as $col) {
                if (stripos($col['Extra'] ?? '', 'GENERATED') !== false) continue;
                $insertCols[] = $col['Field'];
                if (preg_match('/^(tiny|medium|long)?blob|^(var)?binary/i', $col['Type'])) {
                    $binaryCols[$col['Field']] = true;
                }
            }
            if (empty($insertCols)) continue;

            $colList = implode(', ', array_map(function ($c) { return "`{$c}`"; }, $insertCols));
            $stmt = $pdo->query("SELECT {$colList} FROM `{$table}`");

            $batch = [];
            $flush = function () use (&$batch, $fh, $table, $colList) {
                if (empty($batch)) return;
                fwrite($fh, "INSERT INTO `{$table}` ({$colList}) VALUES\n" . implode(",\n", $batch) . ";\n");
                $batch = [];
            };
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $vals = [];
                foreach ($row as $field => $v) {
                    if ($v === null) {
                        $vals[] = 'NULL';
                    } elseif (isset($binaryCols[$field])) {
                        $vals[] = $v === '' ? "''" : '0x' . bin2hex($v);
                    } else {
                        $vals[] = $pdo->quote($v);
                    }
                }
                $batch[] = '(' . implode(', ', $vals) . ')';
                if (count($batch) >= 200) $flush();
            }
            $flush();
            $stmt->closeCursor();
            fwrite($fh, "\n");
        }

        foreach ($views as $view) {
            $createView = $pdo->query("SHOW CREATE VIEW `{$view}`")->fetch(PDO::FETCH_NUM);
            if (!$createView) continue;
            // ຕັດ DEFINER ອອກ ເພື່ອໃຫ້ restore ໄດ້ໃນເຄື່ອງອື່ນ
            $viewSql = preg_replace('/\sDEFINER=`[^`]*`@`[^`]*`/', '', $createView[1]);
            fwrite($fh, "-- View: {$view}\n");
            fwrite($fh, "DROP VIEW IF EXISTS `{$view}`;\n");
            fwrite($fh, normalizeCollationSQL($viewSql) . ";\n\n");
        }

        fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fh);
        return true;
    } catch (Throwable $e) {
        fclose($fh);
        @unlink($outputPath);
        return false;
    }
}

// Restore database function
function restoreDatabaseSQL($pdo, $sqlFilePath) {
    if (!file_exists($sqlFilePath)) return false;
    $sql = file_get_contents($sqlFilePath);
    if (empty($sql)) return false;

    $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
    $queries = preg_split('/;\s*[\r\n]+/', $sql);
    foreach ($queries as $query) {
        // ຕັດແຖວ comment (-- ...) ອອກ ໂດຍບໍ່ທິ້ງຄຳສັ່ງທີ່ຕາມມາ
        $q = trim(preg_replace('/^\s*--[^\n]*\n?/m', '', $query));
        if (!empty($q)) {
            try {
                $pdo->exec($q);
            } catch (Throwable $e) {
                // Continue executing remaining statements
            }
        }
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
    return true;
}

// Direct File Download Handler
if (isset($_GET['action']) && $_GET['action'] === 'download_backup') {
    $filename = basename($_GET['file'] ?? '');
    $filePath = $backupDir . $filename;
    if (!empty($filename) && file_exists($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'sql') {
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
        die('ໄຟລ໌ບໍ່ມີຢູ່ ຫຼື Path ບໍ່ຖືກຕ້ອງ');
    }
}

// Handle AJAX Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    // Action 1: Create Backup
    if ($action === 'create_backup') {
        $dbName = $database ?? 'storepos';
        $filename = 'minipos_backup_' . date('Y-m-d_H-i-s') . '.sql';
        $outputPath = $backupDir . $filename;

        if (exportDatabaseSQL($pdo, $dbName, $outputPath)) {
            logActivity($pdo, "ສ້າງສຳຮອງຖານຂໍ້ມູນ", "ສ້າງໄຟລ໌: {$filename}");
            echo json_encode(['success' => true, 'message' => "ສ້າງໄຟລ໌ສຳຮອງ ({$filename}) ສຳເລັດ!", 'filename' => $filename]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ບໍ່ສາມາດສ້າງໄຟລ໌ສຳຮອງໄດ້!']);
        }
        exit();
    }

    // Action 2: Delete Backup
    if ($action === 'delete_backup') {
        $filename = basename($_POST['filename'] ?? '');
        $filePath = $backupDir . $filename;
        if (!empty($filename) && file_exists($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'sql') {
            unlink($filePath);
            logActivity($pdo, "ລົບໄຟລ໌ສຳຮອງຖານຂໍ້ມູນ", "ລົບໄຟລ໌: {$filename}");
            echo json_encode(['success' => true, 'message' => "ລົບໄຟລ໌ສຳຮອງ ({$filename}) ສຳເລັດ!"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ບໍ່ພົບໄຟລ໌ສຳຮອງທີ່ຕ້ອງການລົບ!']);
        }
        exit();
    }

    // Action 3: Restore Backup (From Existing File)
    if ($action === 'restore_backup') {
        $filename = basename($_POST['filename'] ?? '');
        $filePath = $backupDir . $filename;
        if (!empty($filename) && file_exists($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'sql') {
            if (restoreDatabaseSQL($pdo, $filePath)) {
                logActivity($pdo, "ຟື້ນຟູຖານຂໍ້ມູນ", "ຟື້ນຟູຈາກໄຟລ໌: {$filename}");
                echo json_encode(['success' => true, 'message' => "ຟື້ນຟູຖານຂໍ້ມູນຈາກ ({$filename}) ສຳເລັດ!"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ເກີດຂໍ້ຜິດພາດໃນການຟື້ນຟູຖານຂໍ້ມູນ!']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'ບໍ່ພົບໄຟລ໌ສຳຮອງ!']);
        }
        exit();
    }

    // Action 4: Restore Backup (From Uploaded File)
    if ($action === 'upload_restore') {
        if (!empty($_FILES['backup_file']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['backup_file']['name'], PATHINFO_EXTENSION));
            if ($ext !== 'sql') {
                echo json_encode(['success' => false, 'message' => 'ກະລຸນາເລືອກໄຟລ໌ .sql ເທົ່ານັ້ນ!']);
                exit();
            }
            $tmpPath = $_FILES['backup_file']['tmp_name'];
            if (restoreDatabaseSQL($pdo, $tmpPath)) {
                $origName = basename($_FILES['backup_file']['name']);
                logActivity($pdo, "ຟື້ນຟູຖານຂໍ້ມູນອັບໂຫຼດ", "ຟື້ນຟູຈາກໄຟລ໌: {$origName}");
                echo json_encode(['success' => true, 'message' => "ອັບໂຫຼດ ແລະ ຟື້ນຟູຖານຂໍ້ມູນ ({$origName}) ສຳເລັດ!"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ເກີດຂໍ້ຜິດພາດໃນການອັບໂຫຼດ/ຟື້ນຟູຖານຂໍ້ມູນ!']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'ກະລຸນາເລືອກໄຟລ໌ SQL ທີ່ຈະອັບໂຫຼດ!']);
        }
        exit();
    }
}
