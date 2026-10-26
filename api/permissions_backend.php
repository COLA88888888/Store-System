<?php
/**
 * --------------------------------------------------------------------------
 * ຟາຍ Backend: ປະມວນຜົນຂໍ້ມູນ ແລະ AJAX Action Handler
 * Path: pages/permissions/partials/permissions_backend.php
 * --------------------------------------------------------------------------
 * ໜ້າທີ່:
 * - ກວດເຊັກ Session ວ່າໄດ້ Login ຫຼື ບໍ່ ແລະ ມີສິດເຂົ້າເຖິງໜ້າກຳນົດສິດ
 * - ຮັບ ແລະ ປະມວນຜົນ AJAX Request ຈາກ JavaScript (ປ່ຽນສິດເອກະລາດ / ນຳໃຊ້ Preset ດ່ວນ)
 * - ດຶງຂໍ້ມູນຜູ້ນຳໃຊ້ທັງໝົດ (`tbuser`) ພ້ອມ Join ຊື່ສາຂາ (`tbstore`)
 */

// 1. ເລີ່ມ Session ຖ້າຍັງບໍ່ທັນເລີ່ມ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. ຄຳນວນ Path ຫຼັກຂອງລະບົບ
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

// 3. ໂຫຼດໄຟລ໌ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ
require_once __DIR__ . '/../config/db.php';

// 4. ກວດເຊັກສິດການເຂົ້າເຖິງ (ສະເພາະ ຜູ້ບໍລິຫານ ຫຼື ຜູ້ທີ່ມີສິດ permissions/users ເທົ່ານັ້ນ)
if (empty($_SESSION['user_id']) || (!hasPermission('permissions') && !hasPermission('users'))) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

// =========================================================================
// 5. ຈັດການ AJAX Actions (ເມື່ອມີການສົ່ງ POST ajax_action ຈາກ JavaScript)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $action = $_POST['ajax_action'];

    // Users who can't see every branch may only change permissions of accounts in their own branch.
    $targetUserId = intval($_POST['user_id'] ?? 0);
    if ($targetUserId > 0 && !canViewAllBranches($pdo)) {
        $targetStoreStmt = $pdo->prepare("SELECT store_id FROM st_tbuser WHERE Id = ?");
        $targetStoreStmt->execute([$targetUserId]);
        if (intval($targetStoreStmt->fetchColumn()) !== intval($_SESSION['store_id'] ?? 0)) {
            echo json_encode(['success' => false, 'message' => 'ທ່ານບໍ່ມີສິດຈັດການຜູ້ໃຊ້ຂອງສາຂາອື່ນ!']);
            exit();
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS st_user_permission_switch_states (
        user_id INT NOT NULL,
        switch_key VARCHAR(120) NOT NULL,
        is_enabled TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (user_id, switch_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    if ($action === 'get_switch_states') {
        $stateStmt = $pdo->query('SELECT switch_key, is_enabled FROM st_user_permission_switch_states');
        $states = [];
        foreach ($stateStmt->fetchAll() as $state) {
            $states[$state['switch_key']] = (int)$state['is_enabled'];
        }

        echo json_encode(['success' => true, 'states' => $states]);
        exit();
    }

    // ---------------------------------------------------------------------
    // Action 1: ປ່ຽນສິດເອກະລາດ single toggle (toggle_perm)
    // ---------------------------------------------------------------------
    if ($action === 'toggle_perm') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $perm    = trim($_POST['perm'] ?? '');
        $val     = intval($_POST['val'] ?? 0);
        $switchKey = trim($_POST['switch_key'] ?? '');
        $switchStates = json_decode($_POST['switch_states'] ?? '{}', true);

        // ລາຍຊື່ຄໍລຳສິດທີ່ອະນຸຍາດໃຫ້ປັບປ່ຽນ (ລວມທັງເມນູຍ່ອຍລາຍງານ)
        $allowed_perms = [
            'dashboard', 'sale', 'item_sales', 'stock', 'categories', 'units', 'products', 'suppliers', 'import_stock', 'import_list',
            'stock_check', 'stock_count', 'stock_transfer', 'transfer_history',
            'report', 'daily_report', 'all_sales', 'best_seller', 'profit_cost', 'financial', 'category_sales', 'delete_bills', 'shift_close_report', 'shift_revenue_report',
            'accounting', 'setup', 'stores', 'print_barcode', 'expiry', 'exchange_rate', 'promotions', 'price_adjustment', 'printers', 'loyalty', 'shifts',
            'users', 'permissions', 'branches', 'edit', 'customers', 'database'
        ];

        $switchKeyPattern = '/^perm_[a-z0-9_]+_' . preg_quote((string)$user_id, '/') . '$/i';
        if ($user_id <= 0 || !in_array($perm, $allowed_perms, true) || !preg_match($switchKeyPattern, $switchKey) || !is_array($switchStates)) {
            echo json_encode(['success' => false, 'message' => 'ຂໍ້ມູນບໍ່ຖືກຕ້ອງ']);
            exit();
        }

        // Id 1 is permanently locked. Any OTHER admin-labeled account can only be touched by
        // the owner (Id 1) — a non-owner user must not be able to change another admin's
        // permissions even though they can see that admin in the list.
        if ($user_id === 1) {
            echo json_encode(['success' => false, 'message' => 'ຜູ້ບໍລິຫານຄົນທຳອິດ (ID: 1) ມີສິດເຕັມ 100% ໃນລະບົບຢູ່ແລ້ວ ບໍ່ສາມາດປັບປ່ຽນສິດໄດ້!']);
            exit();
        }
        if (!isOwnerUser() && isAdminAccount($pdo, $user_id)) {
            echo json_encode(['success' => false, 'message' => 'ມີແຕ່ຜູ້ບໍລິຫານຄົນທຳອິດເທົ່ານັ້ນທີ່ສາມາດປັບປ່ຽນສິດຂອງຜູ້ບໍລິຫານຄົນອື່ນໄດ້!']);
            exit();
        }

        try {
            // ເພີ່ມຄໍລຳອໍໂຕ້ໃນຖານຂໍ້ມູນຖ້າຍັງບໍ່ມີ
            try {
                $pdo->exec("ALTER TABLE st_tbuser ADD COLUMN `{$perm}` TINYINT(1) DEFAULT 0");
            } catch (Throwable $ex) {}

            // ອັບເດດສິດໃນຕາຕະລາງ tbuser
            $stmt = $pdo->prepare("UPDATE st_tbuser SET `{$perm}` = ? WHERE Id = ?");
            $stmt->execute([$val, $user_id]);

            $switchStmt = $pdo->prepare(
                'INSERT INTO st_user_permission_switch_states (user_id, switch_key, is_enabled)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE is_enabled = VALUES(is_enabled)'
            );
            foreach ($switchStates as $stateKey => $stateValue) {
                if (preg_match($switchKeyPattern, $stateKey)) {
                    $switchStmt->execute([$user_id, $stateKey, (int)!empty($stateValue)]);
                }
            }
            $switchStmt->execute([$user_id, $switchKey, $val]);

            // ອັບເດດ Session permissions ທັນທີ ຖ້າແມ່ນຜູ້ໃຊ້ທີ່ກຳລັງ Login ຢູ່
            if ($user_id == ($_SESSION['user_id'] ?? 0)) {
                $_SESSION['permissions'][$perm] = $val;
                if (!isset($_SESSION['switch_states']) || !is_array($_SESSION['switch_states'])) {
                    $_SESSION['switch_states'] = [];
                }
                $_SESSION['switch_states'][$switchKey] = $val;
                foreach ($switchStates as $sK => $sV) {
                    if (preg_match($switchKeyPattern, $sK)) {
                        $_SESSION['switch_states'][$sK] = (int)!empty($sV);
                    }
                }
            }

            // ດຶງຂໍ້ມູນຜູ້ໃຊ້ທີ່ຖືກອັບເດດ ເພື່ອຄຳນວນຈຳນວນສິດທີ່ເປີດຢູ່
            $uStmt = $pdo->prepare("SELECT * FROM st_tbuser WHERE Id = ?");
            $uStmt->execute([$user_id]);
            $userData = $uStmt->fetch();
            $targetUser = $userData['username'] ?? 'User';

            $permCount = 0;
            $countKeys = ['dashboard', 'sale', 'stock', 'report', 'accounting', 'setup', 'users', 'permissions', 'branches', 'edit', 'customers', 'database'];
            foreach ($countKeys as $pKey) {
                if (!empty($userData[$pKey])) {
                    $permCount++;
                }
            }

            // ຊື່ສິດພາສາລາວສຳລັບບັນທຶກ Log
            $perm_names_lao = [
                'dashboard' => 'ສິດ ດາດຊ໌ບອດ',
                'sale' => 'ສິດ ຂາຍສິນຄ້າ POS',
                'item_sales' => 'ສິດ ເບິ່ງລາຍການຂາຍສິນຄ້າ',
                'customers' => 'ສິດ ຈັດການລູກຄ້າ',
                'stock' => 'ສິດ ຂໍ້ມູນສິນຄ້າ & ຄັງສິນຄ້າ',
                'stock_check' => 'ສິດ ເຊັກສະຕັອກ (ຜູ້ຈັດການ)',
                'stock_count' => 'ສິດ ນັບສະຕັອກ (ພະນັກງານນັບ)',
                'accounting' => 'ສິດ ຈັດການບັນຊີ',
                'report' => 'ສິດ ເບິ່ງລາຍງານ',
                'daily_report' => 'ສິດ ລາຍງານປະຈຳວັນ',
                'all_sales' => 'ສິດ ລາຍງານການຂາຍທັງໝົດ',
                'best_seller' => 'ສິດ ລາຍງານສິນຄ້າຂາຍດີ',
                'profit_cost' => 'ສິດ ລາຍງານກຳໄລ-ຕົ້ນທຶນ',
                'financial' => 'ສິດ ລາຍງານການເງິນ',
                'category_sales' => 'ສິດ ລາຍງານຕາມປະເພດສິນຄ້າ',
                'delete_bills' => 'ສິດ ປະຫວັດການລົບບິນຂາຍ',
                'shift_close_report' => 'ສິດ ລາຍງານປິດກະ',
                'shift_revenue_report' => 'ສິດ ລາຍງານລາຍຮັບຕາມກະ',
                'shifts' => 'ສິດ ຈັດການກະເຮັດວຽກ',
                'users' => 'ສິດ ຈັດການຜູ້ນຳໃຊ້',
                'permissions' => 'ສິດ ກຳນົດສິດ',
                'branches' => 'ສິດ ຈັດການສາຂາ',
                'setup' => 'ສິດ ຕັ້ງຄ່າລະບົບ',
                'edit' => 'ສິດ ແກ້ໄຂ & ລົບຂໍ້ມູນ',
                'database' => 'ສິດ ຈັດການຖານຂໍ້ມູນ'
            ];
            $perm_lao = $perm_names_lao[$perm] ?? $perm;
            $status_text = ($val === 1) ? 'ເປີດສິດ' : 'ປິດສິດ';

            // ບັນທຶກປະຫວັດການເຮັດວຽກ (Activity Log)
            logActivity($pdo, "ປ່ຽນສິດການໃຊ້ງານ", "ຜູ້ໃຊ້: {$targetUser} (ID: {$user_id}), {$perm_lao}: {$status_text}");

            echo json_encode([
                'success' => true, 
                'message' => "{$status_text} \"{$perm_lao}\" ໃຫ້ {$targetUser} ສຳເລັດ!",
                'user_id' => $user_id,
                'perm' => $perm,
                'val' => (int)($userData[$perm] ?? $val),
                'perm_count' => $permCount
            ]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'ຜິດພາດ: ' . $e->getMessage()]);
        }
        exit();
    }

    // ---------------------------------------------------------------------
    // Action 2: ກຳນົດສິດແບບດ່ວນ (apply_preset)
    // ---------------------------------------------------------------------
    if ($action === 'apply_preset') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $preset  = trim($_POST['preset'] ?? '');

        if ($user_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ບໍ່ພົບຜູ້ໃຊ້']);
            exit();
        }

        // ແຜນຜັງຮູບແບບສິດ (Preset Template Mapping)
        $presets_map = [
            'cashier'      => ['dashboard' => 0, 'sale' => 1, 'stock' => 0, 'report' => 0, 'accounting' => 0, 'setup' => 0, 'users' => 0, 'edit' => 0, 'customers' => 1, 'database' => 0, 'role_name' => 'ພະນັກງານຂາຍ POS'],
            'accountant'   => ['dashboard' => 1, 'sale' => 0, 'stock' => 0, 'report' => 1, 'accounting' => 1, 'setup' => 0, 'users' => 0, 'edit' => 0, 'customers' => 0, 'database' => 0, 'role_name' => 'ຄົນຈັດການບັນຊີ'],
            'stock_keeper' => ['dashboard' => 1, 'sale' => 0, 'stock' => 1, 'report' => 0, 'accounting' => 0, 'setup' => 0, 'users' => 0, 'edit' => 1, 'customers' => 0, 'database' => 0, 'role_name' => 'ພະນັກງານຄັງສິນຄ້າ'],
            'auditor'      => ['dashboard' => 1, 'sale' => 1, 'stock' => 1, 'report' => 1, 'accounting' => 1, 'setup' => 0, 'users' => 0, 'edit' => 0, 'customers' => 1, 'database' => 0, 'role_name' => 'ຜູ້ກວດສອບບັນຊີ'],
            'manager'      => ['dashboard' => 1, 'sale' => 1, 'stock' => 1, 'report' => 1, 'accounting' => 1, 'setup' => 1, 'users' => 1, 'edit' => 1, 'customers' => 1, 'permissions' => 1, 'database' => 1, 'role_name' => 'ຜູ້ບໍລິຫານ / ຈັດການທັງໝົດ'],
            'all_on'       => ['dashboard' => 1, 'sale' => 1, 'stock' => 1, 'report' => 1, 'accounting' => 1, 'setup' => 1, 'users' => 1, 'edit' => 1, 'customers' => 1, 'permissions' => 1, 'database' => 1, 'role_name' => 'ເປີດທຸກສິດ'],
            'all_off'      => ['dashboard' => 0, 'sale' => 0, 'stock' => 0, 'report' => 0, 'accounting' => 0, 'setup' => 0, 'users' => 0, 'edit' => 0, 'customers' => 0, 'permissions' => 0, 'database' => 0, 'role_name' => 'ປິດທຸກສິດ']
        ];

        if (!isset($presets_map[$preset])) {
            echo json_encode(['success' => false, 'message' => 'ບໍ່ພົບຮູບແບບສິດທີ່ເລືອກ']);
            exit();
        }

        if ($user_id === 1) {
            echo json_encode(['success' => false, 'message' => 'ຜູ້ບໍລິຫານຄົນທຳອິດ (ID: 1) ບໍ່ສາມາດປັບປ່ຽນສິດໄດ້!']);
            exit();
        }
        if (!isOwnerUser() && isAdminAccount($pdo, $user_id)) {
            echo json_encode(['success' => false, 'message' => 'ມີແຕ່ຜູ້ບໍລິຫານຄົນທຳອິດເທົ່ານັ້ນທີ່ສາມາດປັບປ່ຽນສິດຂອງຜູ້ບໍລິຫານຄົນອື່ນໄດ້!']);
            exit();
        }

        $p = $presets_map[$preset];

        // ເມນູຍ່ອຍບໍ່ມີຄ່າໃນ Preset ໂດຍກົງ — ໃຫ້ຮັບຄ່າຈາກໂມດູນແມ່
        $parentPerm = [
            'item_sales' => 'sale',
            'categories' => 'stock', 'units' => 'stock', 'products' => 'stock', 'suppliers' => 'stock',
            'import_stock' => 'stock', 'import_list' => 'stock', 'stock_check' => 'stock', 'stock_count' => 'stock', 'stock_transfer' => 'stock', 'transfer_history' => 'stock',
            'daily_report' => 'report', 'all_sales' => 'report', 'best_seller' => 'report', 'profit_cost' => 'report',
            'financial' => 'report', 'category_sales' => 'report', 'delete_bills' => 'report',
            'branches' => 'setup', 'stores' => 'setup', 'print_barcode' => 'setup', 'expiry' => 'setup', 'exchange_rate' => 'setup',
            'promotions' => 'setup', 'price_adjustment' => 'setup', 'printers' => 'setup', 'loyalty' => 'setup',
        ];
        $presetValue = function ($perm) use ($p, $parentPerm) {
            if (isset($p[$perm])) return (int)$p[$perm];
            return (int)($p[$parentPerm[$perm] ?? ''] ?? 0);
        };

        // ສະວິດທຸກອັນໃນຕາຕະລາງ ({switch_id: perm}) ທີ່ JS ສົ່ງມາ
        $switches = json_decode($_POST['switches'] ?? '{}', true);
        if (!is_array($switches)) $switches = [];
        $switchKeyPattern = '/^perm_[a-z0-9_]+_' . preg_quote((string)$user_id, '/') . '$/i';

        try {
            $tbuserCols = [];
            foreach ($pdo->query("SHOW COLUMNS FROM st_tbuser")->fetchAll() as $col) {
                $tbuserCols[$col['Field']] = true;
            }
            $colPerms = array_unique(array_merge(
                ['dashboard', 'sale', 'stock', 'report', 'accounting', 'setup', 'users', 'permissions', 'edit', 'customers', 'database'],
                array_keys($parentPerm)
            ));
            $sets = [];
            $params = [];
            foreach ($colPerms as $perm) {
                if (isset($tbuserCols[$perm])) {
                    $sets[] = "`{$perm}` = ?";
                    $params[] = $presetValue($perm);
                }
            }
            $params[] = $user_id;
            $pdo->prepare("UPDATE st_tbuser SET " . implode(', ', $sets) . " WHERE Id = ?")->execute($params);

            // ບັນທຶກສະຖານະສະວິດທຸກອັນ — ຖ້າບໍ່ບັນທຶກ, ຄ່າເກົ່າໃນ user_permission_switch_states
            // ຈະທັບຄ່າ Preset ທັງໃນໜ້າຈໍ (ຫຼັງ reload) ແລະ ໃນ hasPermission()
            $switchValues = [];
            $switchStmt = $pdo->prepare(
                'INSERT INTO st_user_permission_switch_states (user_id, switch_key, is_enabled)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE is_enabled = VALUES(is_enabled)'
            );
            foreach ($switches as $switchKey => $perm) {
                if (!is_string($switchKey) || !is_string($perm) || !preg_match($switchKeyPattern, $switchKey)) continue;
                $v = $presetValue($perm);
                $switchStmt->execute([$user_id, $switchKey, $v]);
                $switchValues[$switchKey] = $v;
            }
            if ($user_id == ($_SESSION['user_id'] ?? 0)) {
                $_SESSION['switch_states'] = array_merge($_SESSION['switch_states'] ?? [], $switchValues);
            }

            $uStmt = $pdo->prepare("SELECT username FROM st_tbuser WHERE Id = ?");
            $uStmt->execute([$user_id]);
            $targetUser = $uStmt->fetchColumn();

            $permCount = 0;
            $allKeys = ['dashboard', 'sale', 'stock', 'report', 'accounting', 'setup', 'users', 'permissions', 'branches', 'edit', 'customers', 'database'];
            foreach ($allKeys as $pk) {
                if (!empty($p[$pk])) {
                    $permCount++;
                }
            }

            logActivity($pdo, "ກຳນົດສິດແບບດ່ວນ", "ຜູ້ໃຊ້: {$targetUser} (ID: {$user_id}), ຮູບແບບ: {$p['role_name']}");

            echo json_encode([
                'success' => true, 
                'message' => "ນຳໃຊ້ຮູບແບບ \"{$p['role_name']}\" ໃຫ້ {$targetUser} ສຳເລັດ!",
                'user_id' => $user_id,
                'perm_count' => $permCount,
                'switch_values' => $switchValues,
                'permissions' => [
                    'dashboard' => $p['dashboard'] ?? 0,
                    'sale' => $p['sale'] ?? 0,
                    'stock' => $p['stock'] ?? 0,
                    'report' => $p['report'] ?? 0,
                    'accounting' => $p['accounting'] ?? 0,
                    'setup' => $p['setup'] ?? 0,
                    'users' => $p['users'] ?? 0,
                    'permissions' => $p['permissions'] ?? 0,
                    'edit' => $p['edit'] ?? 0,
                    'customers' => $p['customers'] ?? 0,
                    'database' => $p['database'] ?? 0
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'ຜິດພາດ: ' . $e->getMessage()]);
        }
        exit();
    }
}

// =========================================================================
// 6. ດຶງຂໍ້ມູນຜູ້ນຳໃຊ້ທັງໝົດ ພ້ອມ Join ຊື່ສາຂາ (tbstore)
// =========================================================================
if (canViewAllBranches($pdo)) {
    $stmtUsers = $pdo->query("SELECT u.*, s.store_name FROM st_tbuser u LEFT JOIN st_tbstore s ON u.store_id = s.store_id ORDER BY u.Id ASC");
} else {
    $stmtUsers = $pdo->prepare("SELECT u.*, s.store_name FROM st_tbuser u LEFT JOIN st_tbstore s ON u.store_id = s.store_id WHERE u.store_id = ? ORDER BY u.Id ASC");
    $stmtUsers->execute([intval($_SESSION['store_id'] ?? 0)]);
}
$users = $stmtUsers->fetchAll();

$totalUsers = count($users);
$firstUserId = !empty($users) ? $users[0]['Id'] : 0;
