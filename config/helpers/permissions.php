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
            'branches'         => 'branches',
            'database'         => 'database',
            'permissions'      => 'permissions',
            'user_manage'      => 'users',
            'users'            => 'users',
            'customers'        => 'customers',
            'customer_debt'    => 'customer_debt',
            'debt_report'      => 'report',
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

