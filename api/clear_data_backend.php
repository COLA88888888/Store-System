<?php
/**
 * --------------------------------------------------------------------------
 * API Backend: ລຶບ / ລ້າງຂໍ້ມູນລະບົບ (Clear & Reset System Data Backend)
 * Path: api/clear_data_backend.php
 * --------------------------------------------------------------------------
 * ສ່ວນຂໍ້ມູນທີ່ລຶບໄດ້ ແລະ ຕາຕະລາງຂອງແຕ່ລະສ່ວນ ຢູ່ api/clear_data/sections.php (ໜ້າໃຊ້ລາຍການດຽວກັນ).
 *   GET  action=get_counts                  → { counts: { <item_key>: n } }
 *   POST action=execute_clean, scope[], password → ລຶບສ່ວນທີ່ເລືອກ ໃນ transaction ດຽວ
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

$userId = intval($_SESSION['user_id'] ?? 0);
if ($userId <= 0 || !hasPermission('clear_data')) {
    echo json_encode(['success' => false, 'message' => t('clear_data.error_no_permission', 'ທ່ານບໍ່ມີສິດເຂົ້າເຖິງສ່ວນນີ້')]);
    exit();
}

$sections = require __DIR__ . '/clear_data/sections.php';
$items = [];
foreach ($sections as $group) {
    foreach ($group['items'] as $key => $item) {
        $items[$key] = $item;
    }
}

function clearDataTableExists(PDO $pdo, string $table): bool {
    static $cache = [];
    if (!isset($cache[$table])) {
        $st = $pdo->prepare('SHOW TABLES LIKE ?');
        $st->execute([$table]);
        $cache[$table] = (bool)$st->fetchColumn();
    }
    return $cache[$table];
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// =========================================================================
// 1. ຈຳນວນຂໍ້ມູນຂອງແຕ່ລະສ່ວນ (ຕາຕະລາງທີ່ບໍ່ມີ → 0)
// =========================================================================
if ($action === 'get_counts') {
    $counts = [];
    foreach ($items as $key => $item) {
        try {
            $counts[$key] = (int)$pdo->query($item['count'])->fetchColumn();
        } catch (Throwable $e) {
            $counts[$key] = 0;
        }
    }
    echo json_encode(['success' => true, 'counts' => $counts]);
    exit();
}

// =========================================================================
// 2. ລຶບສ່ວນທີ່ເລືອກ
// =========================================================================
if ($action === 'execute_clean' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string)($_POST['password'] ?? '');
    $scope = $_POST['scope'] ?? [];
    $scope = is_array($scope) ? array_values(array_intersect(array_keys($items), $scope)) : [];
    if (empty($scope)) {
        echo json_encode(['success' => false, 'message' => t('clear_data.alert_no_selection', 'ກະລຸນາເລືອກຢ່າງໜ້ອຍ 1 ສ່ວນທີ່ຕ້ອງການລຶບ!')]);
        exit();
    }
    if (trim($password) === '') {
        echo json_encode(['success' => false, 'message' => t('clear_data.alert_password_required', 'ກະລຸນາປ້ອນລະຫັດຜ່ານຂອງທ່ານ!')]);
        exit();
    }

    // ລະຫັດຜ່ານຂອງຜູ້ໃຊ້ທີ່ເຂົ້າລະບົບຢູ່ (ຮູບແບບດຽວກັບໜ້າເຂົ້າລະບົບ: bcrypt, SHA-256, MD5, ຂໍ້ຄວາມທຳມະດາ)
    $uStmt = $pdo->prepare('SELECT password FROM st_tbuser WHERE Id = ? LIMIT 1');
    $uStmt->execute([$userId]);
    $uHash = (string)$uStmt->fetchColumn();
    $validPassword = $uHash !== '' && (
        password_verify($password, $uHash)
        || hash('sha256', $password) === strtolower($uHash)
        || md5($password) === strtolower($uHash)
        || $password === $uHash
    );
    if (!$validPassword) {
        echo json_encode(['success' => false, 'message' => t('clear_data.error_password_incorrect', 'ລະຫັດຜ່ານບໍ່ຖືກຕ້ອງ!')]);
        exit();
    }

    // DELETE (ບໍ່ແມ່ນ TRUNCATE) ເພື່ອໃຫ້ຢູ່ໃນ transaction ດຽວ: ຜິດພາດບ່ອນໃດ → ບໍ່ມີຫຍັງຖືກລຶບ
    $tables = [];
    $updates = [];
    foreach ($scope as $key) {
        foreach ($items[$key]['tables'] as $tb) {
            if (clearDataTableExists($pdo, $tb)) $tables[$tb] = true;
        }
        foreach ($items[$key]['updates'] ?? [] as $sql) {
            $updates[] = $sql;
        }
    }
    $tables = array_keys($tables);

    try {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $pdo->beginTransaction();
        foreach ($tables as $tb) {
            $pdo->exec('DELETE FROM `' . $tb . '`');
        }
        foreach ($updates as $sql) {
            try { $pdo->exec($sql); } catch (PDOException $eUpd) {
                // ຕາຕະລາງ/ຖັນທີ່ບໍ່ມີໃນເຄື່ອງນີ້ (42S02/42S22) → ຂ້າມ; ຜິດພາດອື່ນ → ຍົກເລີກທັງໝົດ
                if (!in_array($eUpd->getCode(), ['42S02', '42S22'], true)) throw $eUpd;
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        try { $pdo->exec('SET FOREIGN_KEY_CHECKS = 1'); } catch (Throwable $e2) {}
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
    try { $pdo->exec('SET FOREIGN_KEY_CHECKS = 1'); } catch (Throwable $e) {}

    // ເລກລຳດັບ (id) ເລີ່ມໃໝ່ຈາກ 1 ໃນຕາຕະລາງທີ່ວ່າງແລ້ວ (ALTER commit ເອງ ຈຶ່ງເຮັດຫຼັງ transaction)
    foreach ($tables as $tb) {
        try { $pdo->exec('ALTER TABLE `' . $tb . '` AUTO_INCREMENT = 1'); } catch (Throwable $e) {}
    }

    $labels = array_map(function ($key) { return t('clear_data.item_' . $key, $key); }, $scope);
    try {
        if (function_exists('logActivity')) {
            logActivity($pdo, 'ລຶບ/ລ້າງຂໍ້ມູນລະບົບ', 'ສ່ວນທີ່ລຶບ: ' . implode(', ', $scope));
        }
    } catch (Throwable $eLog) {}

    echo json_encode([
        'success' => true,
        'message' => t('clear_data.success_text', 'ຂໍ້ມູນທີ່ເລືອກໄດ້ຖືກລຶບອອກຈາກລະບົບຮຽບຮ້ອຍແລ້ວ.'),
        'cleared' => $labels,
    ]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
