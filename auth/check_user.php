<?php
// Buffer everything from here on: config/db.php below runs a large block of schema
// migrations + session refresh on every request, and on a production PHP setup that's
// stricter/differently configured than dev, so much as one stray Notice/Warning/Deprecated
// line printed ahead of our JSON breaks jQuery's dataType:'json' parse entirely — the
// request then falls into the AJAX error handler, which shows a generic "wrong password"
// text regardless of what this file actually computed (e.g. a correct expired-account
// redirect). respond_json() below discards any such leaked output so only real JSON ever
// reaches the client.
ob_start();
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config/db.php';

if (!function_exists('respond_json')) {
    function respond_json($data) {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        echo json_encode($data);
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond_json(['success' => false, 'message' => 'Invalid request method']);
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    respond_json(['success' => false, 'message' => t('login.err_both_required', 'ກະລຸນາປ້ອນຊື່ຜູ້ໃຊ້ ແລະ ລະຫັດຜ່ານ')]);
}

if (!$pdo) {
    respond_json(['success' => false, 'message' => t('login.err_db_connection', 'ບໍ່ສາມາດເຊື່ອມຕໍ່ຖານຂໍ້ມູນໄດ້ (Database connection failed)')]);
}

try {
    // 1. ຄົ້ນຫາຜູ້ໃຊ້ງານຈາກຕາຕະລາງ tbuser ໂດຍກົງ
    $stmt = $pdo->prepare("SELECT * FROM st_tbuser WHERE username = ? OR fname = ? LIMIT 1");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if ($user) {
        // 2. ກວດສອບລະຫັດຜ່ານ (ຮອງຮັບທັງ bcrypt, SHA-256, legacy PASSWORD() ແລະ plain text)
        $password_matches = false;
        $userpass = $user['password'] ?? $user['userpass'] ?? '';

        if (password_verify($password, $userpass)) {
            $password_matches = true;
        } elseif (hash('sha256', $password) === $userpass || hash('sha256', $password) === strtolower($userpass)) {
            $password_matches = true;
        } elseif ($password === $userpass) {
            $password_matches = true;
        } else {
            // ກວດສອບດ້ວຍ MySQL PASSWORD() ແບບເກົ່າ
            try {
                $hasUserpassCol = false;
                $userCols = $pdo->query("SHOW COLUMNS FROM st_tbuser")->fetchAll(PDO::FETCH_COLUMN);
                $hasUserpassCol = in_array('userpass', $userCols);

                if ($hasUserpassCol) {
                    $stmtLegacy = $pdo->prepare("SELECT * FROM st_tbuser WHERE (username = ? OR fname = ?) AND (password = PASSWORD(?) OR userpass = PASSWORD(?)) LIMIT 1");
                    $stmtLegacy->execute([$username, $username, $password, $password]);
                } else {
                    $stmtLegacy = $pdo->prepare("SELECT * FROM st_tbuser WHERE (username = ? OR fname = ?) AND password = PASSWORD(?) LIMIT 1");
                    $stmtLegacy->execute([$username, $username, $password]);
                }
                if ($stmtLegacy->fetch()) {
                    $password_matches = true;
                }
            } catch (Exception $ex) {}
        }

        if ($password_matches) {
            // Package/license expiry is checked before a session is ever created — an expired
            // shop can't log in at all, admin included, until the vendor is contacted to renew.
            try {
                $licExpire = $pdo->query("SELECT license_expire_date FROM st_tbcompanyinfo WHERE branch_id = (SELECT store_id FROM st_tbstore WHERE is_main = 1 ORDER BY store_id ASC LIMIT 1) LIMIT 1")->fetchColumn();
            } catch (Throwable $ex) {
                $licExpire = null;
            }
            if (!empty($licExpire) && strtotime($licExpire) < strtotime(date('Y-m-d'))) {
                respond_json(['success' => false, 'expired' => true, 'redirect' => 'license_expired.php']);
            }

            // Per-user access expiry — owner-only field, never set on Id 1 itself.
            $userAccessExpire = $user['access_expire_date'] ?? null;
            if (($user['Id'] ?? 0) != 1 && !empty($userAccessExpire) && strtotime($userAccessExpire) <= strtotime(date('Y-m-d'))) {
                respond_json(['success' => false, 'expired' => true, 'redirect' => 'access_expired.php']);
            }

            $userStatusVal = $user['status'] ?? $user['userstatus'] ?? '';
            $isAdmin = (
                ($user['Id'] ?? 0) == 1 ||
                strtolower($userStatusVal) === 'admin' || 
                strtolower($userStatusVal) === 'super admin' ||
                $userStatusVal === 'ຜູ້ບໍລິຫານ'
            );

            $userStoreId = intval($user['store_id'] ?? $user['branch_id'] ?? 1);

            // Check if assigned branch store is active (unless Admin/Executive)
            if (!$isAdmin) {
                $stmtBranchCheck = $pdo->prepare("SELECT status, store_name FROM st_tbstore WHERE store_id = ?");
                $stmtBranchCheck->execute([$userStoreId]);
                $branchInfo = $stmtBranchCheck->fetch(PDO::FETCH_ASSOC);

                if ($branchInfo && ($branchInfo['status'] ?? '') !== 'active') {
                    $branchName = htmlspecialchars($branchInfo['store_name'] ?? 'ສາຂານີ້');
                    header("Location: ../login.php?error=branch_inactive&msg=" . urlencode("ສາຂາ ({$branchName}) ຖືກປິດໃຊ້ງານຊົ່ວຄາວ! ບໍ່ສາມາດເຂົ້າໃຊ້ງານໄດ້"));
                    exit();
                }
            }

            // ບັນທຶກຂໍ້ມູນ Session
            $_SESSION['checked'] = 1;
            $_SESSION['user_id'] = $user['Id'];
            $_SESSION['last_activity'] = time();

            // ອັບເດດເວລາເຄື່ອນໄຫວລ້າສຸດໃນຖານຂໍ້ມູນ
            $nowVientiane = date('Y-m-d H:i:s');
            try {
                $pdo->exec("UPDATE st_tbuser SET last_activity = '{$nowVientiane}' WHERE Id = " . intval($user['Id']));
            } catch (Throwable $ex) {}

            $_SESSION['username'] = $user['username'];
            $_SESSION['user_code'] = $user['user_code'] ?? '';
            $_SESSION['fname'] = $user['username'];
            $_SESSION['lname'] = '';
            $_SESSION['status'] = $isAdmin ? 'ຜູ້ບໍລິຫານ' : ($userStatusVal ?: 'ພະນັກງານ');
            $_SESSION['store_id'] = $user['store_id'] ?? $user['branch_id'] ?? 1;

            // ໂຫຼດສິດການໃຊ້ງານຕາມໂມດູນ (Module Permissions - Strict No Fallback)
            $_SESSION['permissions'] = [
                'dashboard' => $isAdmin ? 1 : (int)($user['dashboard'] ?? 0),
                'sale' => $isAdmin ? 1 : (int)($user['sale'] ?? 0),
                'item_sales' => $isAdmin ? 1 : (int)($user['item_sales'] ?? 0),
                'stock' => $isAdmin ? 1 : (int)($user['stock'] ?? 0),
                'categories' => $isAdmin ? 1 : (int)($user['categories'] ?? 0),
                'products' => $isAdmin ? 1 : (int)($user['products'] ?? 0),
                'import_stock' => $isAdmin ? 1 : (int)($user['import_stock'] ?? 0),
                'stock_check' => $isAdmin ? 1 : (int)($user['stock_check'] ?? $user['products'] ?? 0),
                'stock_count' => $isAdmin ? 1 : (int)($user['stock_count'] ?? 0),
                'import_list' => $isAdmin ? 1 : (int)($user['import_list'] ?? 0),
                'report' => $isAdmin ? 1 : (int)($user['report'] ?? 0),
                'daily_report' => $isAdmin ? 1 : (int)($user['daily_report'] ?? 0),
                'all_sales' => $isAdmin ? 1 : (int)($user['all_sales'] ?? 0),
                'best_seller' => $isAdmin ? 1 : (int)($user['best_seller'] ?? 0),
                'profit_cost' => $isAdmin ? 1 : (int)($user['profit_cost'] ?? 0),
                'financial' => $isAdmin ? 1 : (int)($user['financial'] ?? 0),
                'category_sales' => $isAdmin ? 1 : (int)($user['category_sales'] ?? 0),
                'delete_bills' => $isAdmin ? 1 : (int)($user['delete_bills'] ?? 0),
                'accounting' => $isAdmin ? 1 : (int)($user['accounting'] ?? 0),
                'setup' => $isAdmin ? 1 : (int)($user['setup'] ?? 0),
                'users' => $isAdmin ? 1 : (int)($user['users'] ?? 0),
                'permissions' => $isAdmin ? 1 : (int)($user['permissions'] ?? 0),
                'branches' => $isAdmin ? 1 : (int)($user['branches'] ?? 0),
                'edit' => $isAdmin ? 1 : (int)($user['edit'] ?? 0),
                'customers' => $isAdmin ? 1 : (int)($user['customers'] ?? 0),
                'database' => $isAdmin ? 1 : (int)($user['database'] ?? 0),
                'stock_transfer' => $isAdmin ? 1 : (int)($user['stock_transfer'] ?? 0),
                'transfer_history' => $isAdmin ? 1 : (int)($user['transfer_history'] ?? 0),
                'customer_debt' => $isAdmin ? 1 : (int)($user['customer_debt'] ?? 0)
            ];
            
            $_SESSION['profile_img'] = 'default.png';

            // ສົ່ງທຸກຄົນໄປທີ່ໜ້າ dashboard.php (Unified Dashboard)
            $redirect = '../home/dashboard.php';
            
            logActivity($pdo, "ເຂົ້າສູ່ລະບົບ", "ຊື່ຜູ້ໃຊ້: " . $user['username']);

            respond_json([
                'success' => true,
                'redirect' => $redirect
            ]);
        } else {
            respond_json(['success' => false, 'message' => t('login.err_invalid', 'ລະຫັດບໍ່ຖືກຕ້ອງ ກະລຸນາລອງໃໝ່')]);
        }
    } else {
        respond_json(['success' => false, 'message' => t('login.err_invalid', 'ລະຫັດບໍ່ຖືກຕ້ອງ ກະລຸນາລອງໃໝ່')]);
    }
} catch (Throwable $e) {
    // Anything unexpected in the block above (a missing column after a failed auto-migration,
    // a stricter SQL mode on the live server, etc.) used to be silently reported as "wrong
    // password", which hid the real cause. Log it so it shows up in the server's PHP error log.
    error_log('check_user.php login error: ' . $e->getMessage());
    respond_json(['success' => false, 'message' => t('login.err_invalid', 'ລະຫັດບໍ່ຖືກຕ້ອງ ກະລຸນາລອງໃໝ່')]);
}
