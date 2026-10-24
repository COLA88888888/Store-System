<?php
/**
 * ສ່ວນ: ຟັງຊັນຊ່ວຍ: ກວດສິດ
 * hasPermission() — ດ່ານກວດສິດດຽວຂອງລະບົບ (admin ຜ່ານໝົດ → ສະວິດສິດລາຍຄົນ → ຄໍລຳສິດໃນ tbuser),
 * isOwnerUser, isAdminAccount, getPermissionLimit.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ config/db.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

// === Permission Helper Functions ===
if (!function_exists('hasPermission')) {
    function hasPermission($module, $action = 'view', $checkUserId = null) {
        $userId = $checkUserId !== null ? intval($checkUserId) : intval($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) return false;
        
        // If checking current logged-in user
        if ($userId === intval($_SESSION['user_id'] ?? 0)) {
            $status = $_SESSION['status'] ?? '';
            if ($userId === 1) {
                return true;
            }
            $isAdminUser = ($status === 'ຜູ້ບໍລິຫານ' || strtolower($status) === 'admin' || strtolower($status) === 'super admin');
            $switchStates = $_SESSION['switch_states'] ?? [];
            $perms = $_SESSION['permissions'] ?? [];
            if (is_string($perms)) {
                $decoded = json_decode($perms, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $perms = $decoded;
                }
            }
            if (!is_array($perms)) {
                $perms = [];
            }
        } else {
            // If checking another specified user ID
            global $pdo;
            $uRow = null;
            if (isset($pdo)) {
                $uStmt = $pdo->prepare("SELECT * FROM st_tbuser WHERE Id = ?");
                $uStmt->execute([$userId]);
                $uRow = $uStmt->fetch(PDO::FETCH_ASSOC);
            }

            if (!$uRow) return false;

            $userStatusVal = $uRow['status'] ?? $uRow['userstatus'] ?? '';
            if ($userId === 1) return true;
            $isAdminUser = (strtolower($userStatusVal) === 'admin' || strtolower($userStatusVal) === 'super admin' || $userStatusVal === 'ຜູ້ບໍລິຫານ');

            // Fetch target user's switch states
            $switchStates = [];
            if (isset($pdo)) {
                $swStmt = $pdo->prepare("SELECT switch_key, is_enabled FROM st_user_permission_switch_states WHERE user_id = ?");
                $swStmt->execute([$userId]);
                foreach ($swStmt->fetchAll(PDO::FETCH_ASSOC) as $sRow) {
                    $switchStates[$sRow['switch_key']] = (int)$sRow['is_enabled'];
                }
            }

            $perms = $uRow;
        }

        // Action Code Mapping
        $actKeyMap = [
            'view'   => 'view',
            'add'    => 'add',
            'create' => 'add',
            'edit'   => 'edit',
            'update' => 'edit',
            'delete' => 'del',
            'del'    => 'del',
            'remove' => 'del'
        ];
        $actCode = $actKeyMap[strtolower($action)] ?? 'view';

        // Module alias mappings
        $aliasMap = [
            'dashboard'        => 'dashboard',
            'pos'              => 'sale',
            'categories'       => 'categories',
            'units'            => 'units',
            'products'         => 'products',
            'suppliers'        => 'products',
            'import'           => 'import',
            'import_stock'     => 'import_stock',
            'import_list'      => 'import_list',
            'stock_check'      => 'stock_check',
            'stock_count'      => 'stock_count',
            'reports'          => 'report',
            'financial'        => 'financial',
            'daily_report'     => 'report',
            'all_sales'        => 'report',
            'best_seller'      => 'report',
            'profit_cost'      => 'report',
            'category_sales'   => 'report',
            'delete_bills'     => 'report',
            'item_sales'       => 'item_sales',
            'accounting'       => 'accounting',
            'bank'             => 'accounting',
            'settings'         => 'setup',
            'stores'           => 'setup',
            'print_barcode'    => 'setup',
            'exchange_rate'    => 'setup',
            'promotions'       => 'setup',
            'price_adjustment' => 'setup',
            'printers'         => 'setup',
            'loyalty'          => 'setup',
            'shifts'           => 'setup',
            'branches'         => 'branches',
            'database'         => 'database',
            'permissions'      => 'permissions',
            'user_manage'      => 'users',
            'users'            => 'users',
            'customers'        => 'customers',
            'shift_report'     => 'report',
            'stock_transfer'   => 'stock_transfer',
            'transfer_history' => 'transfer_history'
        ];

        $targetModule = $aliasMap[$module] ?? $module;

        // Check explicit switch key for exact module or target module alias (e.g., perm_bank_edit_6 or perm_accounting_edit_6)
        $switchKey1 = 'perm_' . $module . '_' . $actCode . '_' . $userId;
        if (isset($switchStates[$switchKey1])) {
            return (int)$switchStates[$switchKey1] === 1;
        }

        $switchKey2 = 'perm_' . $targetModule . '_' . $actCode . '_' . $userId;
        if (isset($switchStates[$switchKey2])) {
            return (int)$switchStates[$switchKey2] === 1;
        }

        // Some permission-matrix toggles store the key without an action segment
        // (e.g. "perm_category_sales_<uid>", "perm_delete_bills_<uid>"). Treat those as a "view" grant.
        if ($actCode === 'view') {
            foreach (['perm_' . $module . '_' . $userId, 'perm_' . $targetModule . '_' . $userId] as $shortKey) {
                if (isset($switchStates[$shortKey])) {
                    return (int)$switchStates[$shortKey] === 1;
                }
            }
        }

        // Admins other than Id 1 have full access unless the owner explicitly switched
        // a module off for them in the Permissions page (checked above).
        if ($isAdminUser) {
            return true;
        }

        // Check view/module access flag ($perms is the session map for the current user,
        // or the target user's tbuser row when $checkUserId points elsewhere)
        $hasModuleAccess = (
            !empty($perms[$targetModule]) ||
            !empty($perms[$module])
        );

        return $hasModuleAccess;
    }
}

// The system owner is user Id 1. A plain admin still has full module access, but only the
// owner may act on ANOTHER admin's account — edit or delete their row in Users Management and
// change their permissions — and only the owner sees the access-expiry column there.
if (!function_exists('isOwnerUser')) {
    function isOwnerUser() {
        return intval($_SESSION['user_id'] ?? 0) === 1;
    }
}

// Whether the TARGET account (not the logged-in one) counts as an admin, by the same rule used
// everywhere else: Id 1, or a status of admin / super admin / ຜູ້ບໍລິຫານ. Callers use it as
// `!isOwnerUser() && isAdminAccount(...)` to deny, so a failed lookup answers "yes, admin" —
// denying the action is the safe way to be wrong here.
if (!function_exists('isAdminAccount')) {
    function isAdminAccount($pdo, $userId) {
        $userId = intval($userId);
        if ($userId <= 0) return false;
        if ($userId === 1) return true;
        try {
            $stmt = $pdo->prepare("SELECT * FROM st_tbuser WHERE Id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return true;
        }
        if (!$row) return false;
        $status = trim((string)($row['status'] ?? $row['userstatus'] ?? ''));
        return $status === 'ຜູ້ບໍລິຫານ'
            || strtolower($status) === 'admin'
            || strtolower($status) === 'super admin';
    }
}

if (!function_exists('getPermissionLimit')) {
    function getPermissionLimit($module) {
        return 0; // Visibility limit feature removed, always return 0 (no limit)
    }
}


// ຕຳແໜ່ງ "ຜູ້ຈັດການ" (st_tbuser.status): ຈັດການວຽກປະຈຳວັນຂອງຮ້ານ ແຕ່ເຫັນ/ຈັດການໄດ້ສະເພາະສາຂາຂອງຕົນເອງ
// (canViewAllBranches() ຄືນ false ສະເໝີ, ເຖິງຢູ່ສາຂາຫຼັກ). ບໍ່ແມ່ນຜູ້ບໍລິຫານ — ໃຊ້ສິດຕາມຖັນຂ້າງລຸ່ມ.
if (!function_exists('managerRoleName')) {
    function managerRoleName() { return 'ຜູ້ຈັດການ'; }
}
if (!function_exists('isManagerRole')) {
    function isManagerRole($status = null) {
        if ($status === null) $status = $_SESSION['status'] ?? '';
        return trim((string)$status) === managerRoleName();
    }
}
if (!function_exists('managerDefaultPermissions')) {
    // ສິດເລີ່ມຕົ້ນຂອງຜູ້ຈັດການ — ເປີດວຽກປະຈຳວັນ (ຂາຍ, ລູກຄ້າ/ໜີ້, ສິນຄ້າ/ສະຕັອກ + ອະນຸມັດເຊັກສະຕັອກ, ລາຍງານ,
    // ບັນຊີ, ໂປຣໂມຊັ່ນ/ປັບລາຄາ/ເຄື່ອງພິມ/ບາໂຄ້ດ/ສະສົມແຕ້ມ); ປິດສິ່ງທີ່ກະທົບທັງລະບົບ (ຜູ້ໃຊ້, ສິດ, ຖານຂໍ້ມູນ,
    // ສາຂາ, ຂໍ້ມູນຮ້ານ, ອັດຕາແລກປ່ຽນ, ລຶບບິນ). ຜູ້ບໍລິຫານປັບເພີ່ມ/ຫຼຸດລາຍຄົນໄດ້ໃນໜ້າກຳນົດສິດ.
    function managerDefaultPermissions() {
        $on = ['dashboard', 'sale', 'item_sales', 'customers',
               'stock', 'edit', 'products', 'categories', 'units', 'suppliers', 'import_stock', 'import_list',
               'stock_check', 'stock_count', 'stock_transfer', 'transfer_history',
               'report', 'daily_report', 'all_sales', 'best_seller', 'profit_cost', 'financial', 'category_sales',
               'accounting', 'setup', 'promotions', 'price_adjustment', 'printers', 'print_barcode', 'loyalty'];
        $off = ['users', 'permissions', 'database', 'branches', 'stores', 'exchange_rate', 'delete_bills'];
        return array_fill_keys($on, 1) + array_fill_keys($off, 0);
    }
}
if (!function_exists('applyManagerPermissions')) {
    // ໃສ່ສິດເລີ່ມຕົ້ນຂອງຜູ້ຈັດການໃຫ້ຜູ້ໃຊ້ (ສະເພາະຖັນທີ່ມີໃນ st_tbuser) ແລະ ລຶບສິດລາຍຄົນເກົ່າ
    // (st_user_permission_switch_states) ທີ່ຈະທັບຄ່າໃໝ່. ເອີ້ນຕອນເພີ່ມຜູ້ໃຊ້ ຫຼື ປ່ຽນຕຳແໜ່ງເປັນຜູ້ຈັດການ.
    function applyManagerPermissions($pdo, $userId) {
        $userId = intval($userId);
        if ($userId <= 1 || !$pdo) return;
        try {
            $cols = [];
            foreach ($pdo->query("SHOW COLUMNS FROM st_tbuser")->fetchAll(PDO::FETCH_ASSOC) as $c) $cols[$c['Field']] = true;
            $set = []; $vals = [];
            foreach (managerDefaultPermissions() as $col => $v) {
                if (isset($cols[$col])) { $set[] = "`$col` = ?"; $vals[] = $v; }
            }
            if ($set) {
                $vals[] = $userId;
                $pdo->prepare("UPDATE st_tbuser SET " . implode(', ', $set) . " WHERE Id = ?")->execute($vals);
            }
            $pdo->prepare("DELETE FROM st_user_permission_switch_states WHERE user_id = ? AND switch_key LIKE 'perm\\_%'")->execute([$userId]);
        } catch (Throwable $e) {}
    }
}
if (!function_exists('revokeManagerPermissions')) {
    // ປ່ຽນຕຳແໜ່ງຈາກຜູ້ຈັດການເປັນພະນັກງານ: ເອົາສິດຜູ້ຈັດການຄືນ → ເທົ່າກັບພະນັກງານໃໝ່
    // (ຂາຍ, ສິນຄ້າ/ສະຕັອກ, ແກ້ໄຂ, ລູກຄ້າ; ອື່ນໆປິດ) — ບໍ່ໃຫ້ຄົນທີ່ຖືກປົດຍັງຖືສິດເກົ່າ.
    function revokeManagerPermissions($pdo, $userId) {
        $userId = intval($userId);
        if ($userId <= 1 || !$pdo) return;
        $keep = ['sale' => 1, 'stock' => 1, 'edit' => 1, 'customers' => 1];
        try {
            $cols = [];
            foreach ($pdo->query("SHOW COLUMNS FROM st_tbuser")->fetchAll(PDO::FETCH_ASSOC) as $c) $cols[$c['Field']] = true;
            $set = []; $vals = [];
            foreach (array_keys(managerDefaultPermissions()) as $col) {
                if (isset($cols[$col])) { $set[] = "`$col` = ?"; $vals[] = $keep[$col] ?? 0; }
            }
            if ($set) {
                $vals[] = $userId;
                $pdo->prepare("UPDATE st_tbuser SET " . implode(', ', $set) . " WHERE Id = ?")->execute($vals);
            }
            $pdo->prepare("DELETE FROM st_user_permission_switch_states WHERE user_id = ? AND switch_key LIKE 'perm\\_%'")->execute([$userId]);
        } catch (Throwable $e) {}
    }
}
