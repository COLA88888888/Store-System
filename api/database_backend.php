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

// Export database function
function exportDatabaseSQL($pdo, $dbname, $outputPath) {
    // 1. Try mysqldump if available
    if (function_exists('exec')) {
        $mysqldumpPath = 'mysqldump';
        if (file_exists('C:/xampp/mysql/bin/mysqldump.exe')) {
            $mysqldumpPath = '"C:/xampp/mysql/bin/mysqldump.exe"';
        }
        $cmd = "{$mysqldumpPath} --host=localhost --user=root {$dbname} > \"{$outputPath}\"";
        @exec($cmd, $output, $returnVar);
        if ($returnVar === 0 && file_exists($outputPath) && filesize($outputPath) > 100) {
            return true;
        }
    }

    // 2. Pure PHP Exporter Fallback
    $sql = "-- MiniPOS Database Backup\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- Database: {$dbname}\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
        if (!$createStmt) continue;
        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sql .= $createStmt[1] . ";\n\n";

        $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $cols = array_map(function($c) { return "`{$c}`"; }, array_keys($rows[0]));
            $sql .= "INSERT INTO `{$table}` (" . implode(", ", $cols) . ") VALUES\n";
            
            $valRows = [];
            foreach ($rows as $row) {
                $vals = array_map(function($v) use ($pdo) {
                    if ($v === null) return 'NULL';
                    return $pdo->quote($v);
                }, array_values($row));
                $valRows[] = "(" . implode(", ", $vals) . ")";
            }
            $sql .= implode(",\n", $valRows) . ";\n\n";
        }
    }
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return (file_put_contents($outputPath, $sql) !== false);
}

// Restore database function
function restoreDatabaseSQL($pdo, $sqlFilePath) {
    if (!file_exists($sqlFilePath)) return false;
    $sql = file_get_contents($sqlFilePath);
    if (empty($sql)) return false;

    $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
    $queries = preg_split('/;\s*[\r\n]+/', $sql);
    foreach ($queries as $query) {
        $q = trim($query);
        if (!empty($q) && strpos($q, '--') !== 0) {
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
        $dbName = $database ?? 'minipos';
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
