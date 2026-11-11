<?php
/**
 * ສ່ວນ: Session ແລະ ສິດຜູ້ໃຊ້
 * ເລີ່ມ session, ກວດໝົດອາຍຸສິດເຂົ້າໃຊ້, ອອກຈາກລະບົບອັດຕະໂນມັດເມື່ອບໍ່ເຄື່ອນໄຫວ 15 ນາທີ,
 * ອ່ານຂໍ້ມູນຜູ້ໃຊ້ຈາກ tbuser ທຸກຄັ້ງເພື່ອສ້າງ $_SESSION[permissions] ໃໝ່ ແລະ
 * ອອກຈາກລະບົບຖ້າສາຂາຖືກປິດ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ config/db.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

if (session_status() === PHP_SESSION_NONE) {
    // ຕັ້ງຄ່າ Session Cookie ໃຫ້ເປັນ 0 (Expired ເມື່ອປິດ Browser)
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

// 15 Minutes Inactivity Idle Timeout (900 seconds)
$maxIdleTime = 900;
if (isset($_SESSION['user_id'])) {
    // Depth-aware redirect prefix, shared by both checks below: a flat '../' only reaches
    // auth/ from home/<file>.php (1 level deep). pages/pos/pos.php is 2 levels deep and needs
    // '../../'; pages/settings/printers/index.php is 3 levels deep and needs '../../../' — a
    // single hardcoded '../' 404s from any page nested more than one directory under pages/.
    $scriptDepth = 0;
    if (preg_match('#/(pages|home)/(.+)$#', $_SERVER['SCRIPT_NAME'] ?? '', $m)) {
        $scriptDepth = substr_count($m[2], '/') + 1;
    }
    $authPrefix = str_repeat('../', $scriptDepth);

    // Per-user access expiry (owner-only field, set from Users Management) — same gap as the
    // license check above: auth/check_user.php blocks a NEW login once it's lapsed, but a user
    // already logged in would otherwise keep working past their own 0-days-left date until
    // their session naturally idles out. Id 1 (the owner) is exempt — this field is never set
    // on it, same rule check_user.php already uses.
    if (intval($_SESSION['user_id']) !== 1 && $pdo) {
        try {
            $myAccessStmt = $pdo->prepare("SELECT * FROM w_tbuser WHERE Id = ? LIMIT 1");
            $myAccessStmt->execute([intval($_SESSION['user_id'])]);
            $myAccessRow = $myAccessStmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $myAccessExpire = $myAccessRow['access_expire_date'] ?? null;
        } catch (Throwable $ex) {
            $myAccessRow = [];
            $myAccessExpire = null;
        }
        // ບັນຊີຖືກປິດໃຊ້ງານລະຫວ່າງທີ່ຍັງເຂົ້າລະບົບຢູ່ (ພະນັກງານລາອອກ) → ອອກຈາກລະບົບທັນທີ
        if (isset($myAccessRow['account_active']) && (int)$myAccessRow['account_active'] === 0) {
            session_unset();
            session_destroy();
            $redirectDisabledPath = $authPrefix . 'auth/login.php?disabled=1';
            echo "<script>if (window.top) { window.top.location.href = '{$redirectDisabledPath}'; } else { window.location.href = '{$redirectDisabledPath}'; }</script>";
            exit();
        }
        // ສາຂາຂອງຜູ້ໃຊ້ຖືກປິດ ຫຼື ຖືກລຶບ ລະຫວ່າງທີ່ຍັງເຂົ້າລະບົບຢູ່ → ອອກຈາກລະບົບ ແລ້ວໜ້າເຂົ້າສູ່ລະບົບເຕືອນ
        // "ບໍ່ສາມາດເຂົ້າສາຂານີ້ໄດ້" (?branch_closed=1 — auth/partials/login/login_js.php). ກວດກ່ອນອັບເດດ last_activity
        // ຂ້າງລຸ່ມ ເພື່ອບໍ່ໃຫ້ຜູ້ໃຊ້ທີ່ເຂົ້າບໍ່ໄດ້ ຂຶ້ນເປັນ "ອອນລາຍ" ໃນໜ້າຈັດການຜູ້ໃຊ້. Id 1 ຍົກເວັ້ນ (ຄືກັບ auth/check_user.php)
        if ($myAccessRow) {
            try {
                $myBranchStmt = $pdo->prepare("SELECT status FROM w_tbstore WHERE store_id = ? LIMIT 1");
                $myBranchStmt->execute([(int)($myAccessRow['store_id'] ?? 0)]);
                $myBranchStatus = $myBranchStmt->fetchColumn();
            } catch (Throwable $ex) {
                $myBranchStatus = 'active';   // ກວດບໍ່ໄດ້ → ບໍ່ໄລ່ອອກ
            }
            if ($myBranchStatus !== 'active') {
                session_unset();
                session_destroy();
                $closedPath = $authPrefix . 'auth/login.php?branch_closed=1';
                echo "<script>if (window.top) { window.top.location.href = '{$closedPath}'; } else { window.location.href = '{$closedPath}'; }</script>";
                exit();
            }
        }
        if (!empty($myAccessExpire) && strtotime($myAccessExpire) <= strtotime(date('Y-m-d'))) {
            session_unset();
            session_destroy();
            $redirectAccessExpiredPath = $authPrefix . 'auth/access_expired.php';
            echo "<script>if (window.top) { window.top.location.href = '{$redirectAccessExpiredPath}'; } else { window.location.href = '{$redirectAccessExpiredPath}'; }</script>";
            exit();
        }
    }

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $maxIdleTime)) {
        session_unset();
        session_destroy();
        $redirectLoginPath = $authPrefix . 'auth/login.php?expired=1';
        echo "<script>if (window.top) { window.top.location.href = '{$redirectLoginPath}'; } else { window.location.href = '{$redirectLoginPath}'; }</script>";
        exit();
    }
    // ຄຳຂໍເບື້ອງຫຼັງ (define SESSION_PASSIVE_REQUEST ກ່ອນ require config/db.php — ເຊັ່ນ api/my_permissions.php
    // ທີ່ຖືກເອີ້ນທຸກໆສອງສາມວິນາທີ) ບໍ່ນັບເປັນການໃຊ້ງານ: ບໍ່ຕໍ່ເວລາ idle ແລະ ບໍ່ອັບເດດສະຖານະອອນໄລນ໌
    if (!defined('SESSION_PASSIVE_REQUEST')) {
        $_SESSION['last_activity'] = time();
    }
}
if (!empty($_SESSION['user_id']) && !defined('SESSION_PASSIVE_REQUEST')) {
    $session_user_id = intval($_SESSION['user_id']);
    $nowVientiane = date('Y-m-d H:i:s');
    // ອັບເດດເວລາເຄື່ອນໄຫວລ້າສຸດ (Online activity status for current logged-in user)
    try {
        if (!empty($pdo)) {
            $stmtAct = $pdo->prepare("UPDATE w_tbuser SET last_activity = ? WHERE Id = ?");
            $stmtAct->execute([$nowVientiane, $session_user_id]);
        } elseif ($conn) {
            @mysqli_query($conn, "UPDATE w_tbuser SET last_activity = '$nowVientiane' WHERE Id = '$session_user_id'");
        }
    } catch (Throwable $ex) {}
    // ເຄື່ອງທີ່ພະນັກງານ login ແລ້ວ → ເວັບໄຊໜ້າບ້ານບໍ່ນັບເປັນຜູ້ເຂົ້າຊົມ (frontend/models/visitors.php ອ່ານ cookie ນີ້, 10 ປີ)
    if (empty($_COOKIE['wlaoweb_nocount']) && !headers_sent()) {
        setcookie('wlaoweb_nocount', '1', ['expires' => time() + 315360000, 'path' => '/', 'samesite' => 'Lax']);
    }
}
if ($conn && !empty($_SESSION['user_id'])) {
    $session_user_id = mysqli_real_escape_string($conn, (string)$_SESSION['user_id']);
    // ອັບເດດຂໍ້ມູນສະຖານະ ແລະ ສິດການໃຊ້ງານຈາກຕາຕະລາງ tbuser ຕາມ user_id
    $refresh_sql = "SELECT * FROM w_tbuser WHERE Id = '$session_user_id' LIMIT 1";
    $refresh_result = mysqli_query($conn, $refresh_sql);
    if ($refresh_result && $refresh_row = mysqli_fetch_assoc($refresh_result)) {
        $userStatusVal = $refresh_row['status'] ?? $refresh_row['userstatus'] ?? '';
        $isAdmin = (
            ($refresh_row['Id'] ?? 0) == 1 ||
            strtolower($userStatusVal) === 'admin' ||
            strtolower($userStatusVal) === 'super admin' ||
            $userStatusVal === 'ຜູ້ບໍລິຫານ'
        );
        $_SESSION['status'] = $isAdmin ? 'ຜູ້ບໍລິຫານ' : ($userStatusVal ?: 'ພະນັກງານ');
        $_SESSION['username'] = $refresh_row['username'] ?? ($_SESSION['username'] ?? 'user');
        $_SESSION['store_id'] = (int)($refresh_row['store_id'] ?? $refresh_row['branch_id'] ?? 1);
        $_SESSION['permissions'] = [
            'dashboard' => $isAdmin ? 1 : (int)($refresh_row['dashboard'] ?? 0),
            'sale' => $isAdmin ? 1 : (int)($refresh_row['sale'] ?? 0),
            'item_sales' => $isAdmin ? 1 : (int)($refresh_row['item_sales'] ?? $refresh_row['sale'] ?? 0),
            'stock' => $isAdmin ? 1 : (int)($refresh_row['stock'] ?? 0),
            'categories' => $isAdmin ? 1 : (int)($refresh_row['categories'] ?? 0),
            'units' => $isAdmin ? 1 : (int)($refresh_row['units'] ?? 0),
            'products' => $isAdmin ? 1 : (int)($refresh_row['products'] ?? 0),
            'suppliers' => $isAdmin ? 1 : (int)($refresh_row['suppliers'] ?? $refresh_row['products'] ?? 0),
            'import_stock' => $isAdmin ? 1 : (int)($refresh_row['import_stock'] ?? 0),
            // ເຊັກສະຕັອກ — ກ່ອນມີຄໍລຳຂອງຕົນເອງ ໃຊ້ສິດ products ແທນ
            // ນັບສະຕັອກ (ພະນັກງານນັບ): ປ້ອນຈຳນວນຕົວຈິງ + ລາຍງານ ເທົ່ານັ້ນ
            'import_list' => $isAdmin ? 1 : (int)($refresh_row['import_list'] ?? 0),
            'report' => $isAdmin ? 1 : (int)($refresh_row['report'] ?? 0),
            'daily_report' => $isAdmin ? 1 : (int)($refresh_row['daily_report'] ?? $refresh_row['report'] ?? 0),
            'all_sales' => $isAdmin ? 1 : (int)($refresh_row['all_sales'] ?? $refresh_row['report'] ?? 0),
            'best_seller' => $isAdmin ? 1 : (int)($refresh_row['best_seller'] ?? $refresh_row['report'] ?? 0),
            'profit_cost' => $isAdmin ? 1 : (int)($refresh_row['profit_cost'] ?? $refresh_row['report'] ?? 0),
            'financial' => $isAdmin ? 1 : (int)($refresh_row['financial'] ?? $refresh_row['report'] ?? 0),
            'category_sales' => $isAdmin ? 1 : (int)($refresh_row['category_sales'] ?? $refresh_row['report'] ?? 0),
            'delete_bills' => $isAdmin ? 1 : (int)($refresh_row['delete_bills'] ?? 0),
            // ລາຍງານປິດກະ, ລາຍງານລາຍຮັບຕາມກະ / ຈັດການກະ — ມີສະວິດຂອງຕົນເອງໃນໜ້າກຳນົດສິດ; ກ່ອນນັ້ນໃຊ້ report / setup
            'accounting' => $isAdmin ? 1 : (int)($refresh_row['accounting'] ?? 0),
            'setup' => $isAdmin ? 1 : (int)($refresh_row['setup'] ?? 0),
            'stores' => $isAdmin ? 1 : (int)($refresh_row['stores'] ?? $refresh_row['setup'] ?? 0),
            'print_barcode' => $isAdmin ? 1 : (int)($refresh_row['print_barcode'] ?? $refresh_row['setup'] ?? 0),
            // ສິນຄ້າໝົດອາຍຸ — falls back to setup, which is what gated the page before it had its own switch
            'exchange_rate' => $isAdmin ? 1 : (int)($refresh_row['exchange_rate'] ?? $refresh_row['setup'] ?? 0),
            'promotions' => $isAdmin ? 1 : (int)($refresh_row['promotions'] ?? $refresh_row['setup'] ?? 0),
            'price_adjustment' => $isAdmin ? 1 : (int)($refresh_row['price_adjustment'] ?? $refresh_row['setup'] ?? 0),
            'printers' => $isAdmin ? 1 : (int)($refresh_row['printers'] ?? $refresh_row['setup'] ?? 0),
            'loyalty' => $isAdmin ? 1 : (int)($refresh_row['loyalty'] ?? $refresh_row['setup'] ?? 0),
            'users' => $isAdmin ? 1 : (int)($refresh_row['users'] ?? 0),
            'permissions' => $isAdmin ? 1 : (int)($refresh_row['permissions'] ?? 0),
            'branches' => $isAdmin ? 1 : (int)($refresh_row['branches'] ?? 0),
            'edit' => $isAdmin ? 1 : (int)($refresh_row['edit'] ?? 0),
            'customers' => $isAdmin ? 1 : (int)($refresh_row['customers'] ?? 0),
            'database' => $isAdmin ? 1 : (int)($refresh_row['database'] ?? 0),
            'stock_transfer' => $isAdmin ? 1 : (int)($refresh_row['stock_transfer'] ?? 0),
            'transfer_history' => $isAdmin ? 1 : (int)($refresh_row['transfer_history'] ?? 0)
        ];

        // Refresh switch states from database table user_permission_switch_states
        $uid = intval($_SESSION['user_id']);
        $swStates = [];
        try {
            $swRes = mysqli_query($conn, "SELECT switch_key, is_enabled FROM w_user_permission_switch_states WHERE user_id = '$uid'");
            if ($swRes) {
                while ($swRow = mysqli_fetch_assoc($swRes)) {
                    $swStates[$swRow['switch_key']] = (int)$swRow['is_enabled'];
                }
            }
        } catch (Throwable $ex) {}
        $_SESSION['switch_states'] = $swStates;


        // (ສາຂາຖືກປິດ/ລຶບ: ກວດຢູ່ເທິງສຸດ ກ່ອນອັບເດດ last_activity — ເບິ່ງຂ້າງເທິງ)

        // Multi-Branch Store Switcher Handler runs at the very bottom of this file —
        // it needs isMainBranch() and $pdo, both of which are defined further down.
    }
}

