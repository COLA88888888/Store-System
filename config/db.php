<?php
date_default_timezone_set('Asia/Vientiane');

require_once __DIR__ . '/../lang/translator.php';

// Don't leak PHP errors/warnings to visitors in production; still log them to the
// server's error log so `tail error.log` keeps working the same way it does in dev.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// TODO before going live: replace with the production MySQL host/user/password.
$server = "127.0.0.1";
$username = "root";
$password = "";
$database = "minipos";

$conn = null;
$pdo = null;
$port = 3306;
$portsToTry = [3306, 3336];
foreach ($portsToTry as $p) {
    try {
        $testConn = @mysqli_connect($server, $username, $password, $database, $p);
        if ($testConn) {
            $conn = $testConn;
            $port = $p;
            mysqli_set_charset($conn, "utf8");
            try {
                $pdo = new PDO(
                    "mysql:host={$server};port={$port};dbname={$database};charset=utf8mb4",
                    $username,
                    $password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
                $pdo->exec("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
            } catch (Throwable $pe) {}
            @mysqli_query($conn, "SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
            break;
        }
    } catch (Throwable $ex) {}
}


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

    // Shop-wide license expiry — auth/check_user.php already blocks a NEW login once the
    // package has lapsed, but a shop that stays logged in would otherwise keep working
    // indefinitely. Checked every request so an expiry mid-session still kicks everyone
    // (admin included) to auth/license_expired.php, not just new sign-ins.
    try {
        $licExpire = $pdo ? $pdo->query("SELECT license_expire_date FROM tbcompanyinfo WHERE branch_id = (SELECT store_id FROM tbstore WHERE is_main = 1 ORDER BY store_id ASC LIMIT 1) LIMIT 1")->fetchColumn() : null;
    } catch (Throwable $ex) {
        $licExpire = null;
    }
    if (!empty($licExpire) && strtotime($licExpire) < strtotime(date('Y-m-d'))) {
        session_unset();
        session_destroy();
        $redirectExpiredPath = $authPrefix . 'auth/license_expired.php';
        echo "<script>if (window.top) { window.top.location.href = '{$redirectExpiredPath}'; } else { window.location.href = '{$redirectExpiredPath}'; }</script>";
        exit();
    }

    // Per-user access expiry (owner-only field, set from Users Management) — same gap as the
    // license check above: auth/check_user.php blocks a NEW login once it's lapsed, but a user
    // already logged in would otherwise keep working past their own 0-days-left date until
    // their session naturally idles out. Id 1 (the owner) is exempt — this field is never set
    // on it, same rule check_user.php already uses.
    if (intval($_SESSION['user_id']) !== 1 && $pdo) {
        try {
            $myAccessStmt = $pdo->prepare("SELECT access_expire_date FROM tbuser WHERE Id = ? LIMIT 1");
            $myAccessStmt->execute([intval($_SESSION['user_id'])]);
            $myAccessExpire = $myAccessStmt->fetchColumn();
        } catch (Throwable $ex) {
            $myAccessExpire = null;
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
    $_SESSION['last_activity'] = time();
}
if (!empty($_SESSION['user_id'])) {
    $session_user_id = intval($_SESSION['user_id']);
    $nowVientiane = date('Y-m-d H:i:s');
    // ອັບເດດເວລາເຄື່ອນໄຫວລ້າສຸດ (Online activity status for current logged-in user)
    try {
        if (!empty($pdo)) {
            $stmtAct = $pdo->prepare("UPDATE tbuser SET last_activity = ? WHERE Id = ?");
            $stmtAct->execute([$nowVientiane, $session_user_id]);
        } elseif ($conn) {
            @mysqli_query($conn, "UPDATE tbuser SET last_activity = '$nowVientiane' WHERE Id = '$session_user_id'");
        }
    } catch (Throwable $ex) {}
}
if ($conn && !empty($_SESSION['user_id'])) {
    $session_user_id = mysqli_real_escape_string($conn, (string)$_SESSION['user_id']);
    // ອັບເດດຂໍ້ມູນສະຖານະ ແລະ ສິດການໃຊ້ງານຈາກຕາຕະລາງ tbuser ຕາມ user_id
    $refresh_sql = "SELECT * FROM tbuser WHERE Id = '$session_user_id' LIMIT 1";
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
            'import_list' => $isAdmin ? 1 : (int)($refresh_row['import_list'] ?? 0),
            'report' => $isAdmin ? 1 : (int)($refresh_row['report'] ?? 0),
            'daily_report' => $isAdmin ? 1 : (int)($refresh_row['daily_report'] ?? $refresh_row['report'] ?? 0),
            'all_sales' => $isAdmin ? 1 : (int)($refresh_row['all_sales'] ?? $refresh_row['report'] ?? 0),
            'best_seller' => $isAdmin ? 1 : (int)($refresh_row['best_seller'] ?? $refresh_row['report'] ?? 0),
            'profit_cost' => $isAdmin ? 1 : (int)($refresh_row['profit_cost'] ?? $refresh_row['report'] ?? 0),
            'financial' => $isAdmin ? 1 : (int)($refresh_row['financial'] ?? $refresh_row['report'] ?? 0),
            'category_sales' => $isAdmin ? 1 : (int)($refresh_row['category_sales'] ?? $refresh_row['report'] ?? 0),
            'delete_bills' => $isAdmin ? 1 : (int)($refresh_row['delete_bills'] ?? 0),
            'accounting' => $isAdmin ? 1 : (int)($refresh_row['accounting'] ?? 0),
            'setup' => $isAdmin ? 1 : (int)($refresh_row['setup'] ?? 0),
            'stores' => $isAdmin ? 1 : (int)($refresh_row['stores'] ?? $refresh_row['setup'] ?? 0),
            'print_barcode' => $isAdmin ? 1 : (int)($refresh_row['print_barcode'] ?? $refresh_row['setup'] ?? 0),
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
            $swRes = mysqli_query($conn, "SELECT switch_key, is_enabled FROM user_permission_switch_states WHERE user_id = '$uid'");
            if ($swRes) {
                while ($swRow = mysqli_fetch_assoc($swRes)) {
                    $swStates[$swRow['switch_key']] = (int)$swRow['is_enabled'];
                }
            }
        } catch (Throwable $ex) {}
        $_SESSION['switch_states'] = $swStates;


        // Enforce active branch status check (log out non-admin user if their branch is disabled/inactive)
        if (!$isAdmin) {
            $userBranchId = (int)($_SESSION['store_id'] ?? 1);
            $chkBranchRes = mysqli_query($conn, "SELECT status, store_name FROM tbstore WHERE store_id = '$userBranchId' LIMIT 1");
            if ($chkBranchRes && $bRow = mysqli_fetch_assoc($chkBranchRes)) {
                if (($bRow['status'] ?? '') !== 'active') {
                    // Destroy session and redirect to login
                    session_unset();
                    session_destroy();
                    $bName = htmlspecialchars($bRow['store_name'] ?? 'ສາຂານີ້');
                    $baseLoginPath = (basename($scriptDir) === 'pages') ? '../login.php' : '../../login.php';
                    echo "<script>alert('ສາຂາ ({$bName}) ຖືກປິດໃຊ້ງານຊົ່ວຄາວ! ລະບົບຈະອອກຈາກປະຕູເຂົ້າໃຊ້ງານ.'); window.location.href = '{$baseLoginPath}';</script>";
                    exit();
                }
            }
        }

        // Multi-Branch Store Switcher Handler runs at the very bottom of this file —
        // it needs isMainBranch() and $pdo, both of which are defined further down.
    }
}

if (!$pdo) {
    try {
        $pdo = new PDO(
            "mysql:host={$server};port={$port};dbname={$database};charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    } catch (Throwable $e) {}
}

if ($pdo) {

    // Migration for database tables & schema (runs once per installation or when flag is missing for extreme performance)
    // Bump the version suffix whenever a new guarded ALTER is added below so every install re-runs the block once.
    // v2: paid_currency / paid_rate / paid_amount_foreign on tbsale_save + sales (multi-currency receipts).
    // v5: tbl_printer created here (it only existed in old dumps) + paper_width for LAN kitchen tickets.
    // v10: tbl_printer.is_receipt_printer + print_jobs table (print agent / off-site server mode).
    // v11: bank_accounts.store_id — each branch now only sees/manages its own bank accounts.
    $schemaFlagFile = __DIR__ . '/.db_schema_v11';
    if (!file_exists($schemaFlagFile)) {
        try {


        $pdo->exec("CREATE TABLE IF NOT EXISTS `tbstore` (
            `store_id` INT AUTO_INCREMENT PRIMARY KEY,
            `store_code` VARCHAR(50) NOT NULL UNIQUE,
            `store_name` VARCHAR(150) NOT NULL,
            `is_main` TINYINT(1) DEFAULT 0,
            `address` TEXT NULL,
            `tel` VARCHAR(50) NULL,
            `status` VARCHAR(20) DEFAULT 'active',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $storeCount = (int)$pdo->query("SELECT COUNT(*) FROM tbstore")->fetchColumn();
        if ($storeCount === 0) {
            $pdo->exec("INSERT INTO tbstore (store_id, store_code, store_name, is_main, address, tel, status) 
                        VALUES (1, '1', 'ສາຂາໃຫຍ່ ວຽງຈັນ (HQ)', 1, 'ນະຄອນຫຼວງວຽງຈັນ', '020-55555555', 'active')");
        }

        // Auto update ST-001 to numeric '1'
        $pdo->exec("UPDATE tbstore SET store_code = '1' WHERE store_id = 1 AND store_code = 'ST-001'");

        // Ensure is_main column exists
        $hasMain = $pdo->query("SHOW COLUMNS FROM `tbstore` LIKE 'is_main'")->fetch();
        if (!$hasMain) {
            $pdo->exec("ALTER TABLE `tbstore` ADD COLUMN `is_main` TINYINT(1) DEFAULT 0 AFTER `store_name`");
        }
        $pdo->exec("UPDATE `tbstore` SET is_main = 1 WHERE store_id = 1");
    } catch (Throwable $ex) {}

    // Ensure store_id column exists in all core data tables for multi-branch isolation
    $coreTablesForStore = ['sales', 'tbsale_save', 'products', 'accounting_records', 'imports', 'customers', 'tbuser', 'categories', 'units'];
    foreach ($coreTablesForStore as $tblName) {
        try {
            $hasCol = $pdo->query("SHOW COLUMNS FROM `{$tblName}` LIKE 'store_id'")->fetch();
            if (!$hasCol) {
                $pdo->exec("ALTER TABLE `{$tblName}` ADD COLUMN `store_id` INT(11) NOT NULL DEFAULT 1");
            }
        } catch (Throwable $ex) {}
    }

    // Ensure all permission columns exist in tbuser
    $allPermCols = ['dashboard', 'sale', 'stock', 'report', 'accounting', 'setup', 'users', 'permissions', 'edit', 'customers', 'branches', 'database'];
    foreach ($allPermCols as $pCol) {
        try {
            $hasCol = $pdo->query("SHOW COLUMNS FROM `tbuser` LIKE '{$pCol}'")->fetch();
            if (!$hasCol) {
                $pdo->exec("ALTER TABLE `tbuser` ADD COLUMN `{$pCol}` TINYINT(1) DEFAULT 0");
            }
        } catch (Throwable $ex) {}
    }

    // Ensure Tax/VAT columns exist in tbcompanyinfo and sales tables
    try {
        $hasVat = $pdo->query("SHOW COLUMNS FROM `tbcompanyinfo` LIKE 'vat_percent'")->fetch();
        if (!$hasVat) {
            $pdo->exec("ALTER TABLE `tbcompanyinfo` ADD COLUMN `vat_percent` DECIMAL(5,2) DEFAULT 7.00");
        }
        $hasTaxType = $pdo->query("SHOW COLUMNS FROM `tbcompanyinfo` LIKE 'tax_type'")->fetch();
        if (!$hasTaxType) {
            $pdo->exec("ALTER TABLE `tbcompanyinfo` ADD COLUMN `tax_type` VARCHAR(20) DEFAULT 'inclusive'");
        }
        $hasSalesTaxType = $pdo->query("SHOW COLUMNS FROM `sales` LIKE 'tax_type'")->fetch();
        if (!$hasSalesTaxType) {
            $pdo->exec("ALTER TABLE `sales` ADD COLUMN `tax_type` VARCHAR(20) DEFAULT 'inclusive'");
        }
        $hasSalesVatRate = $pdo->query("SHOW COLUMNS FROM `sales` LIKE 'vat_rate'")->fetch();
        if (!$hasSalesVatRate) {
            $pdo->exec("ALTER TABLE `sales` ADD COLUMN `vat_rate` DECIMAL(5,2) DEFAULT 7.00");
        }
        $hasQrImg = $pdo->query("SHOW COLUMNS FROM `tbcompanyinfo` LIKE 'qr_img'")->fetch();
        if (!$hasQrImg) {
            $pdo->exec("ALTER TABLE `tbcompanyinfo` ADD COLUMN `qr_img` VARCHAR(255) NULL AFTER `img_url`");
        }
        $hasTaxId = $pdo->query("SHOW COLUMNS FROM `tbcompanyinfo` LIKE 'tax_id'")->fetch();
        if (!$hasTaxId) {
            $pdo->exec("ALTER TABLE `tbcompanyinfo` ADD COLUMN `tax_id` VARCHAR(100) NULL AFTER `com_email`");
        }
    } catch (Throwable $ex) {}

    // CNY (ຢວນຈີນ) exchange-rate column — kept in its OWN try/catch so an unrelated
    // migration failure above can never stop it landing (the exchange_rate save page
    // does INSERT/UPDATE ... ex_kip_cn and hard-fails without it).
    try {
        $hasExKipCn = $pdo->query("SHOW COLUMNS FROM `tbexchange` LIKE 'ex_kip_cn'")->fetch();
        if (!$hasExKipCn) {
            try {
                $pdo->exec("ALTER TABLE `tbexchange` ADD COLUMN `ex_kip_cn` VARCHAR(255) NULL AFTER `ex_kip_us`");
            } catch (Throwable $e2) {
                // fall back to a plain ADD if AFTER-column resolution failed
                $pdo->exec("ALTER TABLE `tbexchange` ADD COLUMN `ex_kip_cn` VARCHAR(255) NULL");
            }
        }
    } catch (Throwable $ex) {}

    // Multi-currency receipts: record which currency the customer actually tendered on
    // each sale (chosen per bill at checkout) plus the KIP-per-unit rate used at that
    // moment, so the daily report can break "money received today" down by currency.
    // Own try/catch — never let this stall the checkout insert if it can't run.
    try {
        foreach (['tbsale_save', 'sales'] as $__ccTbl) {
            $__ccCols = $pdo->query("SHOW COLUMNS FROM `{$__ccTbl}`")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('paid_currency', $__ccCols, true)) {
                $pdo->exec("ALTER TABLE `{$__ccTbl}` ADD COLUMN `paid_currency` VARCHAR(8) NOT NULL DEFAULT 'KIP'");
            }
            if (!in_array('paid_rate', $__ccCols, true)) {
                $pdo->exec("ALTER TABLE `{$__ccTbl}` ADD COLUMN `paid_rate` DECIMAL(18,6) NOT NULL DEFAULT 1");
            }
            if (!in_array('paid_amount_foreign', $__ccCols, true)) {
                $pdo->exec("ALTER TABLE `{$__ccTbl}` ADD COLUMN `paid_amount_foreign` DECIMAL(18,2) NULL");
            }
        }
    } catch (Throwable $ex) {}

    // Kitchen-ticket routing: which printer a product category's items print to at the POS.
    // NULL = that category prints on no separate ticket (only the customer receipt).
    try {
        $catCols = $pdo->query("SHOW COLUMNS FROM `categories`")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('printer_id', $catCols, true)) {
            $pdo->exec("ALTER TABLE `categories` ADD COLUMN `printer_id` INT NULL");
        }
    } catch (Throwable $ex) {}

    // Printers. type 'ip' printers get kitchen tickets straight over the LAN (api/lan_printer.php);
    // paper_width (58 / 80 mm) sets the raster width those tickets are drawn at.
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `tbl_printer` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) DEFAULT NULL,
            `ip_address` VARCHAR(100) DEFAULT NULL,
            `type` ENUM('ip','browser') DEFAULT 'ip',
            `status` TINYINT(1) DEFAULT 1,
            `branch_id` INT DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $printerCols = $pdo->query("SHOW COLUMNS FROM `tbl_printer`")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('paper_width', $printerCols, true)) {
            $pdo->exec("ALTER TABLE `tbl_printer` ADD COLUMN `paper_width` TINYINT UNSIGNED NOT NULL DEFAULT 80");
        }
        if (!in_array('is_receipt_printer', $printerCols, true)) {
            $pdo->exec("ALTER TABLE `tbl_printer` ADD COLUMN `is_receipt_printer` TINYINT(1) NOT NULL DEFAULT 0");
        }
    } catch (Throwable $ex) {}

    // Print agent queue — only used when Settings > Printers has print_mode = 'agent' (the
    // server is hosted off-site and can't reach a shop printer's 192.168.x.x directly). A LAN
    // print job is parked here instead of sent straight to the socket; tools/print-agent.php,
    // running on a shop PC that IS on that LAN, polls api/print_agent.php and prints it.
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `print_jobs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `printer_id` INT NOT NULL,
            `store_id` INT DEFAULT 1,
            `payload` LONGTEXT NOT NULL,
            `status` ENUM('pending','done','failed') NOT NULL DEFAULT 'pending',
            `error` VARCHAR(500) DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `printed_at` DATETIME DEFAULT NULL,
            INDEX idx_print_jobs_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Throwable $ex) {}

    // Add performance indexes if missing for lightning-fast queries
    try {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_products_store ON products (store_id, category_id)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_products_barcode ON products (barcode)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_sales_store_date ON sales (store_id, created_at)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_sales_invoice ON sales (invoice_number)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_customers_store ON customers (store_id, customer_id)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_price_adj_branch ON price_adjustments (branch_id, adjust_id)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_promotions_branch ON promotions (branch_id, status)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_transfers_stores ON stock_transfers (from_store_id, to_store_id, status)");
    } catch (Throwable $ex) {}

    // Clean up unused/deprecated database tables
    $unusedTables = [
        'tbbranch', 'tbunit', 'tbsale', 'tbsale_detail', 'category', 'tbsupplier', 'tbcurrency',
        'customer', 'tb_expenses', 'tb_queue_daily', 'tbdeposit_beer', 'tbfinancial_report',
        'tbreceive', 'tbreceive_detail', 'tbsale_save_data', 'stock_adjustments', 'stock_adjustment_items',
        'price_adjustment_items', 'promotion_items', 'tb_delete_bill_log'
    ];
    foreach ($unusedTables as $uTbl) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS `{$uTbl}`");
        } catch (Throwable $ex) {}
    }

    // Ensure tbuser table exists (auto-create on fresh install)
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `tbuser` (
            `Id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `user_code` VARCHAR(50) DEFAULT NULL,
            `username` VARCHAR(100) NOT NULL,
            `fname` VARCHAR(100) DEFAULT NULL,
            `lname` VARCHAR(100) DEFAULT NULL,
            `gender` VARCHAR(20) DEFAULT NULL,
            `dob` DATE DEFAULT NULL,
            `tel` VARCHAR(30) DEFAULT NULL,
            `password` VARCHAR(255) DEFAULT NULL,
            `status` VARCHAR(50) DEFAULT 'ພະນັກງານ',
            `store_id` INT(11) NOT NULL DEFAULT 1,
            `address` TEXT DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `profile_img` VARCHAR(255) DEFAULT 'default.png',
            `last_activity` DATETIME DEFAULT NULL,
            `dashboard` TINYINT(1) DEFAULT 0,
            `sale` TINYINT(1) DEFAULT 0,
            `item_sales` TINYINT(1) DEFAULT 0,
            `stock` TINYINT(1) DEFAULT 0,
            `categories` TINYINT(1) DEFAULT 0,
            `products` TINYINT(1) DEFAULT 0,
            `import_stock` TINYINT(1) DEFAULT 0,
            `import_list` TINYINT(1) DEFAULT 0,
            `report` TINYINT(1) DEFAULT 0,
            `daily_report` TINYINT(1) DEFAULT 0,
            `all_sales` TINYINT(1) DEFAULT 0,
            `best_seller` TINYINT(1) DEFAULT 0,
            `profit_cost` TINYINT(1) DEFAULT 0,
            `financial` TINYINT(1) DEFAULT 0,
            `category_sales` TINYINT(1) DEFAULT 0,
            `delete_bills` TINYINT(1) DEFAULT 0,
            `accounting` TINYINT(1) DEFAULT 0,
            `setup` TINYINT(1) DEFAULT 0,
            `users` TINYINT(1) DEFAULT 0,
            `permissions` TINYINT(1) DEFAULT 0,
            `branches` TINYINT(1) DEFAULT 0,
            `edit` TINYINT(1) DEFAULT 0,
            `customers` TINYINT(1) DEFAULT 1,
            `database` TINYINT(1) DEFAULT 0,
            `stock_transfer` TINYINT(1) DEFAULT 0,
            `transfer_history` TINYINT(1) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Auto-seed admin user if tbuser is empty
        $userCount = (int)$pdo->query("SELECT COUNT(*) FROM `tbuser`")->fetchColumn();
        if ($userCount === 0) {
            $pdo->exec("INSERT INTO `tbuser` (Id, user_code, username, password, status, store_id,
                dashboard, sale, item_sales, stock, categories, products, import_stock, import_list,
                report, daily_report, all_sales, best_seller, profit_cost, financial, category_sales,
                delete_bills, accounting, setup, users, permissions, branches, edit, customers,
                `database`, stock_transfer, transfer_history)
                VALUES (1, '001', 'admin', SHA2('123456', 256), 'Admin', 1,
                1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1)");
        }
    } catch (Throwable $ex) {}

    try {
        $hasBranchId = $pdo->query("SHOW COLUMNS FROM tbuser LIKE 'branch_id'")->fetch();
        if ($hasBranchId) {
            $pdo->exec("ALTER TABLE tbuser CHANGE COLUMN `branch_id` `store_id` INT(11) DEFAULT 1");
        }
    } catch (Throwable $ex) {}

    // Migration for tbuser columns: drop userid, rename userpass->password, userstatus->status
    try {
        $hasUserid = $pdo->query("SHOW COLUMNS FROM tbuser LIKE 'userid'")->fetch();
        if ($hasUserid) {
            $pdo->exec("ALTER TABLE tbuser DROP COLUMN `userid`");
        }
    } catch (Throwable $ex) {}

    try {
        $hasUserpass = $pdo->query("SHOW COLUMNS FROM tbuser LIKE 'userpass'")->fetch();
        if ($hasUserpass) {
            $pdo->exec("ALTER TABLE tbuser CHANGE COLUMN `userpass` `password` VARCHAR(255) DEFAULT NULL");
        }
    } catch (Throwable $ex) {}

    try {
        $hasUserstatus = $pdo->query("SHOW COLUMNS FROM tbuser LIKE 'userstatus'")->fetch();
        if ($hasUserstatus) {
            $pdo->exec("ALTER TABLE tbuser CHANGE COLUMN `userstatus` `status` VARCHAR(50) DEFAULT NULL");
        }
    } catch (Throwable $ex) {}

    // Migration for customers table
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `customers` (
            `customer_id` INT AUTO_INCREMENT PRIMARY KEY,
            `customer_code` VARCHAR(50) NOT NULL UNIQUE,
            `customer_name` VARCHAR(150) NOT NULL,
            `phone` VARCHAR(50) NULL,
            `email` VARCHAR(100) NULL,
            `address` TEXT NULL,
            `notes` TEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $custCols = $pdo->query("SHOW COLUMNS FROM customers")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('member_card', $custCols)) {
            $pdo->exec("ALTER TABLE `customers` ADD COLUMN `member_card` VARCHAR(50) NULL AFTER `phone`");
        }
        if (!in_array('address', $custCols)) {
            $pdo->exec("ALTER TABLE `customers` ADD COLUMN `address` TEXT NULL AFTER `phone`");
        }
        if (!in_array('points', $custCols)) {
            $pdo->exec("ALTER TABLE `customers` ADD COLUMN `points` INT NOT NULL DEFAULT 0");
        }
        if (!in_array('store_id', $custCols)) {
            $pdo->exec("ALTER TABLE `customers` ADD COLUMN `store_id` INT DEFAULT 1 AFTER `notes`");
            $pdo->exec("UPDATE `customers` SET `store_id` = 1 WHERE `store_id` IS NULL OR `store_id` = 0");
        }
    } catch (Throwable $ex) {}

    // Auto Migration for User Detail Columns
    $userCols = [
        'user_code' => "VARCHAR(50) DEFAULT NULL",
        'fname' => "VARCHAR(100) DEFAULT NULL",
        'lname' => "VARCHAR(100) DEFAULT NULL",
        'gender' => "VARCHAR(20) DEFAULT NULL",
        'dob' => "DATE DEFAULT NULL",
        'tel' => "VARCHAR(30) DEFAULT NULL",
        'password' => "VARCHAR(255) DEFAULT NULL",
        'status' => "VARCHAR(50) DEFAULT 'ພະນັກງານ'",
        'store_id' => "INT(11) DEFAULT 1",
        'address' => "TEXT DEFAULT NULL",
        'notes' => "TEXT DEFAULT NULL",
        'profile_img' => "VARCHAR(255) DEFAULT 'default.png'",
        'last_activity' => "DATETIME DEFAULT NULL",
        'accounting' => "TINYINT(1) DEFAULT 0",
        'customers' => "TINYINT(1) DEFAULT 1"
    ];
    foreach ($userCols as $col => $def) {
        try {
            $colCheck = $pdo->query("SHOW COLUMNS FROM tbuser LIKE '{$col}'")->fetch();
            if (!$colCheck) {
                $pdo->exec("ALTER TABLE tbuser ADD COLUMN `{$col}` {$def}");
            }
        } catch (Throwable $ex) {}
    }

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `categories` (
            `category_id` INT AUTO_INCREMENT PRIMARY KEY,
            `category_name` VARCHAR(150) NOT NULL,
            `description` TEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $ex) {}

    // Backfill: every main-branch category must also exist in each sub-branch (its own
    // row/category_id there, matched by name). Covers categories created before the
    // add/edit propagation existed, or inserted directly in the DB. No-op once caught up.
    try {
        $pdo->exec("
            INSERT INTO categories (category_name, description, store_id)
            SELECT mc.category_name, mc.description, s.store_id
            FROM categories mc
            CROSS JOIN tbstore s
            WHERE mc.store_id = (SELECT store_id FROM tbstore WHERE is_main = 1 LIMIT 1)
              AND s.store_id != mc.store_id
              AND NOT EXISTS (
                  SELECT 1 FROM categories sc
                  WHERE sc.store_id = s.store_id AND sc.category_name = mc.category_name
              )
        ");
    } catch (Throwable $ex) {}

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `units` (
            `unit_id` INT AUTO_INCREMENT PRIMARY KEY,
            `unit_name` VARCHAR(100) NOT NULL,
            `description` TEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $ex) {}

    // Backfill: every main-branch unit must also exist in each sub-branch (its own
    // row/unit_id there, matched by name) — same sync rule as categories above.
    try {
        $pdo->exec("
            INSERT INTO units (unit_name, description, store_id)
            SELECT mu.unit_name, mu.description, s.store_id
            FROM units mu
            CROSS JOIN tbstore s
            WHERE mu.store_id = (SELECT store_id FROM tbstore WHERE is_main = 1 LIMIT 1)
              AND s.store_id != mu.store_id
              AND NOT EXISTS (
                  SELECT 1 FROM units su
                  WHERE su.store_id = s.store_id AND su.unit_name = mu.unit_name
              )
        ");
    } catch (Throwable $ex) {}

    // Ensure tbcompanyinfo exists and has DB record
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `tbcompanyinfo` (
            `com_id` INT AUTO_INCREMENT PRIMARY KEY,
            `com_name_la` VARCHAR(255) DEFAULT 'Mini POS Store',
            `com_address` TEXT DEFAULT NULL,
            `com_tel` VARCHAR(50) DEFAULT NULL,
            `barcode` TEXT DEFAULT NULL,
            `img_url` VARCHAR(255) DEFAULT 'logo.png',
            `license_start_date` DATE DEFAULT '2026-01-01',
            `license_expire_date` DATE DEFAULT '2026-12-31',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $hasStartCol = $pdo->query("SHOW COLUMNS FROM `tbcompanyinfo` LIKE 'license_start_date'")->fetch();
        if (!$hasStartCol) {
            $pdo->exec("ALTER TABLE `tbcompanyinfo` ADD COLUMN `license_start_date` DATE DEFAULT '2026-01-01'");
        }
        $hasExpireCol = $pdo->query("SHOW COLUMNS FROM `tbcompanyinfo` LIKE 'license_expire_date'")->fetch();
        if (!$hasExpireCol) {
            $pdo->exec("ALTER TABLE `tbcompanyinfo` ADD COLUMN `license_expire_date` DATE DEFAULT '2026-12-31'");
        }

        $compCount = (int)$pdo->query("SELECT COUNT(*) FROM tbcompanyinfo")->fetchColumn();
        if ($compCount === 0) {
            $pdo->exec("INSERT INTO tbcompanyinfo (com_name_la, com_address, com_tel, barcode, img_url, license_start_date, license_expire_date, branch_id) VALUES 
                ('', '', '', 'ຂອບໃຈທີ່ມາອຸດໜູນ, ໂອກາດໜ້າເຊີນໃໝ່!', 'logo.png', '2026-01-01', '2026-12-31', 1)");
        }
    } catch (Throwable $ex) {}

    // Ensure tbstore exists and has DB record
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `tbstore` (
            `store_id` INT AUTO_INCREMENT PRIMARY KEY,
            `store_code` VARCHAR(50) DEFAULT 'STORE01',
            `store_name` VARCHAR(255) DEFAULT 'Mini POS Store',
            `address` TEXT DEFAULT NULL,
            `tel` VARCHAR(50) DEFAULT NULL,
            `logo_path` VARCHAR(255) DEFAULT 'assets/img/logo/logo.png',
            `status` VARCHAR(20) DEFAULT 'active',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $storeCount = (int)$pdo->query("SELECT COUNT(*) FROM tbstore")->fetchColumn();
        if ($storeCount === 0) {
            $pdo->exec("INSERT INTO tbstore (store_code, store_name, address, tel, logo_path, status) VALUES 
                ('STORE01', 'Mini POS Store', 'ນະຄອນຫຼວງວຽງຈັນ', '020-55555555', 'assets/img/logo/logo.png', 'active')");
        }
    } catch (Throwable $ex) {}

    // Ensure bank_accounts table exists and has seed bank records
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `bank_accounts` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `bank_name` VARCHAR(100) NOT NULL,
            `account_number` VARCHAR(100) NOT NULL,
            `account_name` VARCHAR(150) NOT NULL,
            `bank_code` VARCHAR(50) DEFAULT 'BCEL',
            `bank_logo` VARCHAR(255) DEFAULT NULL,
            `qr_code_img` VARCHAR(255) DEFAULT NULL,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Each branch manages and sees only its own bank accounts — existing rows (added
        // before this column existed) default to the main branch (store_id 1) rather than
        // vanishing from every branch's list.
        $bankCols = $pdo->query("SHOW COLUMNS FROM bank_accounts")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('store_id', $bankCols)) {
            $pdo->exec("ALTER TABLE bank_accounts ADD COLUMN `store_id` INT NOT NULL DEFAULT 1");
        }

        // Add columns to tbsale_save & sales if missing
        $saleCols = $pdo->query("SHOW COLUMNS FROM tbsale_save")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('bank_account_id', $saleCols)) {
            $pdo->exec("ALTER TABLE tbsale_save ADD COLUMN `bank_account_id` INT DEFAULT NULL");
        }
        if (!in_array('bank_name', $saleCols)) {
            $pdo->exec("ALTER TABLE tbsale_save ADD COLUMN `bank_name` VARCHAR(100) DEFAULT NULL");
        }
        if (!in_array('points_earned', $saleCols)) {
            $pdo->exec("ALTER TABLE tbsale_save ADD COLUMN `points_earned` INT NOT NULL DEFAULT 0");
        }

        $salesCols = $pdo->query("SHOW COLUMNS FROM sales")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('bank_account_id', $salesCols)) {
            $pdo->exec("ALTER TABLE sales ADD COLUMN `bank_account_id` INT DEFAULT NULL");
        }
        if (!in_array('bank_name', $salesCols)) {
            $pdo->exec("ALTER TABLE sales ADD COLUMN `bank_name` VARCHAR(100) DEFAULT NULL");
        }
    } catch (Throwable $ex) {}

    try {
        $hasSize = $pdo->query("SHOW COLUMNS FROM products LIKE 'size'")->fetch();
        if ($hasSize) {
            $pdo->exec("ALTER TABLE products CHANGE COLUMN `size` `unit` VARCHAR(100) DEFAULT NULL");
        }
        $hasUnit = $pdo->query("SHOW COLUMNS FROM products LIKE 'unit'")->fetch();
        if (!$hasUnit) {
            $pdo->exec("ALTER TABLE products ADD COLUMN `unit` VARCHAR(100) DEFAULT NULL");
        }

        // products.unit_id is the real link into the units table (mirrors category_id ->
        // categories). products.unit (the plain-text name) stays alongside it as a synced
        // display cache — dropping it would require touching every existing consumer that
        // still reads it directly (POS cart/receipt JS, stock_transfer, reports, product_units).
        $hasUnitId = $pdo->query("SHOW COLUMNS FROM products LIKE 'unit_id'")->fetch();
        if (!$hasUnitId) {
            $pdo->exec("ALTER TABLE products ADD COLUMN `unit_id` INT DEFAULT NULL");
        }

        // One-time (idempotent) backfill: link existing products to their matching row in
        // units by name, scoped to the same branch — covers products added/edited before
        // this FK existed. COLLATE needed since products.unit and units.unit_name don't
        // share a collation (see api/units_backend.php for the same mismatch).
        $pdo->exec("
            UPDATE products p
            JOIN units u ON u.store_id = p.store_id AND u.unit_name = p.unit COLLATE utf8mb4_unicode_ci
            SET p.unit_id = u.unit_id
            WHERE p.unit_id IS NULL AND p.unit IS NOT NULL AND p.unit != ''
        ");

        // ລົບຟິວພາສາອັງກິດ (_en) ທີ່ບໍ່ໄດ້ໃຊ້ງານ
        $pdo->exec("ALTER TABLE categories DROP COLUMN IF EXISTS category_name_en");
        $pdo->exec("ALTER TABLE products DROP COLUMN IF EXISTS product_name_en");
        $pdo->exec("ALTER TABLE tbcompanyinfo DROP COLUMN IF EXISTS com_name_en");

        // ສ້າງຕາຕະລາງ product_units ສຳລັບສິນຄ້າທີ່ມີຫຼາຍລາຄາ/ຫຼາຍຫົວໜ່ວຍ
        $pdo->exec("CREATE TABLE IF NOT EXISTS product_units (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            unit_name VARCHAR(50) NOT NULL,
            multiplier INT NOT NULL DEFAULT 1,
            bprice DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            barcode VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // ສ້າງຕາຕະລາງ imports ສຳລັບໃບບິນຮັບສິນຄ້າເຂົ້າ
        $pdo->exec("CREATE TABLE IF NOT EXISTS imports (
            import_id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_number VARCHAR(100) NOT NULL,
            supplier_name VARCHAR(150) DEFAULT NULL,
            total_cost DECIMAL(15,2) DEFAULT 0.00,
            created_by INT DEFAULT NULL,
            import_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            notes TEXT DEFAULT NULL,
            INDEX (invoice_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // ສ້າງຕາຕະລາງ import_details ສຳລັບລາຍລະອຽດສິນຄ້ານຳເຂົ້າ
        $pdo->exec("CREATE TABLE IF NOT EXISTS import_details (
            import_detail_id INT AUTO_INCREMENT PRIMARY KEY,
            import_id INT NOT NULL,
            product_id INT NOT NULL,
            unit_name VARCHAR(50) DEFAULT NULL,
            multiplier INT DEFAULT 1,
            quantity INT NOT NULL DEFAULT 1,
            total_base_qty INT NOT NULL DEFAULT 1,
            cost_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            total_cost DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            expiry_date DATE DEFAULT NULL,
            INDEX (import_id),
            INDEX (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // ສ້າງຕາຕະລາງ product_batches ສຳລັບຕິດຕາມລັອດສິນຄ້າ ແລະ ວັນໝົດອາຍຸ
        $pdo->exec("CREATE TABLE IF NOT EXISTS product_batches (
            batch_id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            import_detail_id INT DEFAULT NULL,
            expiry_date DATE DEFAULT NULL,
            initial_qty INT NOT NULL DEFAULT 0,
            quantity INT NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (product_id),
            INDEX (expiry_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $ex) {}

    // Auto Migration: suppliers (ຜູ້ສະໜອງ) — ໃນ try/catch ຂອງຕົນເອງ ເພື່ອບໍ່ໃຫ້ຖືກຂ້າມ
    // ຖ້າ migration ອື່ນຂ້າງເທິງລົ້ມກ່ອນ. ໃຊ້ເລືອກຕອນເພີ່ມ/ແກ້ໄຂສິນຄ້າ + ໜ້າ pages/suppliers.
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `suppliers` (
            `supplier_id` INT AUTO_INCREMENT PRIMARY KEY,
            `supplier_name` VARCHAR(191) NOT NULL,
            `phone` VARCHAR(50) DEFAULT NULL,
            `contact_person` VARCHAR(120) DEFAULT NULL,
            `address` VARCHAR(255) DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `status` VARCHAR(20) NOT NULL DEFAULT 'active',
            `store_id` INT NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (`store_id`),
            INDEX (`supplier_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $hasSupplierId = $pdo->query("SHOW COLUMNS FROM products LIKE 'supplier_id'")->fetch();
        if (!$hasSupplierId) {
            $pdo->exec("ALTER TABLE products ADD COLUMN `supplier_id` INT DEFAULT NULL");
        }
    } catch (Throwable $ex) {}

    // Auto Migration for promotions table
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `promotions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `promo_name` VARCHAR(255) NOT NULL,
            `promo_type` VARCHAR(50) DEFAULT 'discount',
            `discount_type` VARCHAR(20) DEFAULT 'percentage',
            `discount_value` DECIMAL(12,2) DEFAULT 0.00,
            `start_date` DATE DEFAULT NULL,
            `end_date` DATE DEFAULT NULL,
            `min_qty` INT DEFAULT 0,
            `min_amount` DECIMAL(12,2) DEFAULT 0.00,
            `status` TINYINT(1) DEFAULT 1,
            `branch_id` INT DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $hasMinQty = $pdo->query("SHOW COLUMNS FROM `promotions` LIKE 'min_qty'")->fetch();
        if (!$hasMinQty) {
            $pdo->exec("ALTER TABLE `promotions` ADD COLUMN `min_qty` INT DEFAULT 0 AFTER `end_date`");
        }
        $hasTargetType = $pdo->query("SHOW COLUMNS FROM `promotions` LIKE 'target_type'")->fetch();
        if (!$hasTargetType) {
            $pdo->exec("ALTER TABLE `promotions` ADD COLUMN `target_type` VARCHAR(50) DEFAULT 'all' AFTER `min_amount`");
            $pdo->exec("ALTER TABLE `promotions` ADD COLUMN `target_name` VARCHAR(255) DEFAULT 'ທຸກສິນຄ້າ' AFTER `target_type`");
        }

        // Auto-deactivate expired promotions
        $pdo->exec("UPDATE promotions SET status = 0 WHERE status = 1 AND end_date < CURDATE()");
    } catch (Throwable $ex) {}

    // Auto Migration for system_settings table
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `system_settings` (
            `setting_id` INT AUTO_INCREMENT PRIMARY KEY,
            `setting_key` VARCHAR(100) NOT NULL UNIQUE,
            `setting_value` TEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $setCount = (int)$pdo->query("SELECT COUNT(*) FROM system_settings")->fetchColumn();
        if ($setCount === 0) {
            $pdo->exec("INSERT INTO system_settings (setting_key, setting_value) VALUES
                ('vat_rate', '0'),
                ('expiry_warning_days', '30')");
        }
        // Customer loyalty points rule: earn Y points for every X kip of net spend
        $pdo->exec("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES
            ('loyalty_enabled', '0'),
            ('loyalty_spend_amount', '100000'),
            ('loyalty_points_earned', '1')");
        // Active display currency for POS/receipt (KIP | THB | USD | CNY). KIP = base, no conversion.
        $pdo->exec("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES ('display_currency', 'KIP')");
        // Show a daily-resetting queue number on the receipt (coffee-shop style). '0' = off.
        $pdo->exec("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES ('queue_enabled', '0')");
    } catch (Throwable $ex) {}

    // Auto Migration for quotations & quotation_details
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `quotations` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `quotation_number` varchar(50) NOT NULL,
          `customer_id` int(11) DEFAULT NULL,
          `customer_name` varchar(255) DEFAULT NULL,
          `subtotal` decimal(15,2) DEFAULT '0.00',
          `discount_amount` decimal(15,2) DEFAULT '0.00',
          `vat_amount` decimal(15,2) DEFAULT '0.00',
          `total_amount` decimal(15,2) DEFAULT '0.00',
          `status` varchar(50) DEFAULT 'pending',
          `store_id` int(11) DEFAULT '1',
          `created_by` varchar(100) DEFAULT NULL,
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `quotation_details` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `quotation_id` int(11) NOT NULL,
          `product_id` int(11) NOT NULL,
          `product_name` varchar(255) NOT NULL,
          `quantity` decimal(15,2) NOT NULL,
          `price` decimal(15,2) NOT NULL,
          `total` decimal(15,2) NOT NULL,
          PRIMARY KEY (`id`),
          KEY `fk_quotation_id` (`quotation_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    } catch (Throwable $ex) {}

    // Auto Migration for tbsale_save, tbsale_save_detail & sales view
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `tbsale_save` (
            `sale_save_id` INT AUTO_INCREMENT PRIMARY KEY,
            `sale_save_bill` VARCHAR(50) NOT NULL,
            `sale_date` DATE DEFAULT NULL,
            `sale_time` TIME DEFAULT NULL,
            `user_receive` INT DEFAULT NULL,
            `customer_id` INT DEFAULT NULL,
            `customer_name` VARCHAR(150) DEFAULT 'ລູກຄ້າທົ່ວໄປ',
            `ip_address` VARCHAR(50) DEFAULT NULL,
            `sale_qty` DECIMAL(10,2) DEFAULT 0.00,
            `sale_amount` DECIMAL(12,2) DEFAULT 0.00,
            `sale_discount_bill` DECIMAL(12,2) DEFAULT 0.00,
            `sale_barlance` DECIMAL(12,2) DEFAULT 0.00,
            `sale_pay` DECIMAL(12,2) DEFAULT 0.00,
            `sale_return` DECIMAL(12,2) DEFAULT 0.00,
            `type_pay` VARCHAR(50) DEFAULT 'ເງິນສົດ',
            `sale_status` VARCHAR(20) DEFAULT 'SUCCESS',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (`sale_save_bill`),
            INDEX (`sale_date`),
            INDEX (`user_receive`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `tbsale_save_detail` (
            `save_id` INT AUTO_INCREMENT PRIMARY KEY,
            `save_bill` VARCHAR(50) NOT NULL,
            `save_date` DATE DEFAULT NULL,
            `save_time` TIME DEFAULT NULL,
            `save_proid` INT DEFAULT NULL,
            `save_proname` VARCHAR(255) DEFAULT NULL,
            `save_qty` DECIMAL(10,2) DEFAULT 1.00,
            `save_price` DECIMAL(12,2) DEFAULT 0.00,
            `cost_price` DECIMAL(12,2) DEFAULT 0.00,
            `save_money` DECIMAL(12,2) DEFAULT 0.00,
            `save_net_money` DECIMAL(12,2) DEFAULT 0.00,
            `user_receives` INT DEFAULT NULL,
            INDEX (`save_bill`),
            INDEX (`save_proid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `sales` (
            `sale_id` INT AUTO_INCREMENT PRIMARY KEY,
            `invoice_number` VARCHAR(50) NOT NULL,
            `sold_by` INT DEFAULT NULL,
            `customer_id` INT DEFAULT NULL,
            `customer_name` VARCHAR(150) DEFAULT 'ລູກຄ້າທົ່ວໄປ',
            `subtotal` DECIMAL(12,2) DEFAULT 0.00,
            `discount_amount` DECIMAL(12,2) DEFAULT 0.00,
            `vat_amount` DECIMAL(12,2) DEFAULT 0.00,
            `total_amount` DECIMAL(12,2) DEFAULT 0.00,
            `total_profit` DECIMAL(12,2) DEFAULT 0.00,
            `cash_received` DECIMAL(12,2) DEFAULT 0.00,
            `change_amount` DECIMAL(12,2) DEFAULT 0.00,
            `payment_type` VARCHAR(50) DEFAULT 'ເງິນສົດ',
            `status` VARCHAR(20) DEFAULT 'SUCCESS',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `inv_num` (`invoice_number`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $salesCols = $pdo->query("SHOW COLUMNS FROM sales")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('created_at', $salesCols)) {
            $pdo->exec("ALTER TABLE sales ADD COLUMN `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP");
        }
        if (!in_array('sold_by', $salesCols)) {
            $pdo->exec("ALTER TABLE sales ADD COLUMN `sold_by` INT DEFAULT NULL");
        }
        if (!in_array('subtotal', $salesCols)) {
            $pdo->exec("ALTER TABLE sales ADD COLUMN `subtotal` DECIMAL(12,2) DEFAULT 0.00");
        }
        if (!in_array('discount_amount', $salesCols)) {
            $pdo->exec("ALTER TABLE sales ADD COLUMN `discount_amount` DECIMAL(12,2) DEFAULT 0.00");
        }
        if (!in_array('vat_amount', $salesCols)) {
            $pdo->exec("ALTER TABLE sales ADD COLUMN `vat_amount` DECIMAL(12,2) DEFAULT 0.00");
        }
        if (!in_array('total_amount', $salesCols)) {
            $pdo->exec("ALTER TABLE sales ADD COLUMN `total_amount` DECIMAL(12,2) DEFAULT 0.00");
        }
        if (!in_array('total_profit', $salesCols)) {
            $pdo->exec("ALTER TABLE sales ADD COLUMN `total_profit` DECIMAL(12,2) DEFAULT 0.00");
        }

        $pdo->exec("INSERT IGNORE INTO sales (sale_id, invoice_number, sold_by, customer_id, customer_name, subtotal, discount_amount, total_amount, cash_received, change_amount, payment_type, status, created_at)
            SELECT 
                sale_save_id,
                sale_save_bill,
                user_receive,
                customer_id,
                customer_name,
                sale_amount,
                sale_discount_bill,
                sale_barlance,
                sale_pay,
                sale_return,
                type_pay,
                sale_status,
                COALESCE(created_at, CONCAT(sale_date, ' ', COALESCE(sale_time, '00:00:00')), NOW())
            FROM tbsale_save");
    } catch (Throwable $ex) {}

    // Ensure user_permission_switch_states exists
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `user_permission_switch_states` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `switch_key` VARCHAR(100) NOT NULL,
            `is_enabled` TINYINT(1) DEFAULT 1,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_user_switch` (`user_id`, `switch_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $ex) {}

    @file_put_contents($schemaFlagFile, date('Y-m-d H:i:s'));
}
}




if (!function_exists('getSetting')) {
    function getSetting($pdo, $key, $default = '') {
        if (!$pdo) return $default;
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1");
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            return ($val !== false && $val !== null) ? $val : $default;
        } catch (Exception $e) {
            return $default;
        }
    }
}

if (!function_exists('formatCurrency')) {
    function formatCurrency($amount)
    {
        return number_format((float)$amount, 0, '.', ',') . ' ₭';
    }
}

if (!function_exists('getExchangeRates')) {
    /**
     * KIP value of 1 unit of each currency, from the newest Active tbexchange row.
     * KIP is always 1. A 0 means that rate has not been configured yet.
     */
    function getExchangeRates($pdo)
    {
        $rates = ['KIP' => 1.0, 'THB' => 0.0, 'USD' => 0.0, 'CNY' => 0.0];
        if (!$pdo) return $rates;
        try {
            // `ex_kip_cn` may be missing on a not-yet-migrated install — SELECT * so one
            // absent column can never wipe out the THB/USD rates too.
            $row = $pdo->query("SELECT * FROM tbexchange ORDER BY (ex_status = 'Active') DESC, Id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $rates['THB'] = (float) str_replace(',', '', (string)($row['ex_kip_bath'] ?? '0'));
                $rates['USD'] = (float) str_replace(',', '', (string)($row['ex_kip_us'] ?? '0'));
                $rates['CNY'] = (float) str_replace(',', '', (string)($row['ex_kip_cn'] ?? '0'));
            }
        } catch (Throwable $e) {}
        return $rates;
    }
}

if (!function_exists('getCurrencyMeta')) {
    function getCurrencyMeta()
    {
        // decimals 0 everywhere: amounts show whole numbers with a comma thousands
        // separator and no decimal point (e.g. "1,234 $").
        return [
            'KIP' => ['name' => 'ກີບ',  'symbol' => '₭', 'decimals' => 0],
            'THB' => ['name' => 'ບາດ',  'symbol' => '฿', 'decimals' => 0],
            'USD' => ['name' => 'ໂດລາ', 'symbol' => '$', 'decimals' => 0],
            'CNY' => ['name' => 'ຢວນ',  'symbol' => '¥', 'decimals' => 0],
        ];
    }
}

if (!function_exists('getDisplayCurrency')) {
    /**
     * Returns the active display currency: ['code','name','symbol','decimals','rate','rates'].
     * `rate` = KIP per 1 unit of this currency (1 for KIP). Falls back to KIP when the
     * chosen currency has no configured exchange rate yet.
     */
    function getDisplayCurrency($pdo)
    {
        // A report that lets the user filter down to one tendered currency (e.g. the
        // currency_filter dropdown on reports/daily_report) overrides the global
        // display currency for the rest of the request — see setDisplayCurrencyOverride().
        if (!empty($GLOBALS['__display_currency_override'])) {
            return $GLOBALS['__display_currency_override'];
        }
        $code  = strtoupper(trim((string) getSetting($pdo, 'display_currency', 'KIP')));
        $meta  = getCurrencyMeta();
        $rates = getExchangeRates($pdo);
        if (!isset($meta[$code])) $code = 'KIP';
        if ($code !== 'KIP' && (empty($rates[$code]) || $rates[$code] <= 0)) $code = 'KIP';
        return array_merge(
            ['code' => $code, 'rate' => ($code === 'KIP' ? 1.0 : (float)$rates[$code]), 'rates' => $rates],
            $meta[$code]
        );
    }
}

if (!function_exists('convertFromKip')) {
    /** Convert a KIP amount into `$code`, rounded to that currency's decimals. */
    function convertFromKip($amountKip, $code, $rates = null, $pdo = null)
    {
        $code = strtoupper((string)$code);
        if ($code === 'KIP' || $code === '') return round((float)$amountKip);
        if ($rates === null) $rates = getExchangeRates($pdo);
        $rate = isset($rates[$code]) ? (float)$rates[$code] : 0.0;
        if ($rate <= 0) return (float)$amountKip;
        $meta = getCurrencyMeta();
        $dec  = $meta[$code]['decimals'] ?? 2;
        return round((float)$amountKip / $rate, $dec);
    }
}

if (!function_exists('formatMoneyIn')) {
    /** Format a KIP amount as a string in currency `$code` (e.g. "$ 12.50", "1,000 ₭"). */
    function formatMoneyIn($amountKip, $code, $rates = null, $pdo = null)
    {
        $code = strtoupper((string)$code) ?: 'KIP';
        $meta = getCurrencyMeta();
        if (!isset($meta[$code])) $code = 'KIP';
        $dec = $meta[$code]['decimals'];
        $val = convertFromKip($amountKip, $code, $rates, $pdo);
        return number_format((float)$val, $dec, '.', ',')
             . ' <span class="cur-sym">' . $meta[$code]['symbol'] . '</span>';
    }
}

if (!function_exists('setDisplayCurrencyOverride')) {
    /**
     * Force displayMoney() to render in one specific currency for the rest of this
     * request, instead of the global system_settings 'display_currency'. Used when a
     * report page lets the user filter down to one tendered currency (e.g. the daily
     * report's currency_filter) — the amounts on screen should switch to match.
     */
    function setDisplayCurrencyOverride($dc)
    {
        $GLOBALS['__display_currency_override'] = $dc;
    }
}

if (!function_exists('displayMoney')) {
    /**
     * Format a KIP amount in the request's active display currency, with the
     * currency lookup cached for the whole request. Use everywhere a report shows
     * money: displayMoney($pdo, $kipAmount). Data stays KIP — this is display only.
     */
    function displayMoney($pdo, $amountKip, $withSymbol = true)
    {
        static $dc = null;
        if (!empty($GLOBALS['__display_currency_override'])) {
            $dc = $GLOBALS['__display_currency_override'];
        } elseif ($dc === null) {
            $dc = function_exists('getDisplayCurrency') ? getDisplayCurrency($pdo)
                : ['code' => 'KIP', 'rates' => ['KIP' => 1]];
        }
        if ($withSymbol) {
            return formatMoneyIn($amountKip, $dc['code'], $dc['rates']);
        }
        $meta = getCurrencyMeta();
        $code = $dc['code'];
        $val  = convertFromKip($amountKip, $code, $dc['rates']);
        return number_format((float)$val, $meta[$code]['decimals'], '.', ',');
    }
}

if (!function_exists('logDebug')) {
    function logDebug($message)
    {
        // debug logging helper
    }
}

if (!function_exists('logActivity')) {
    function logActivity($pdo, $action, $detail = '')
    {
        if (!$pdo) {
            return;
        }
        try {
            // Ensure table exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS `activity_logs` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `action` VARCHAR(255) NOT NULL,
                `detail` TEXT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $stmt = $pdo->prepare('INSERT INTO activity_logs (action, detail, created_at) VALUES (?, ?, NOW())');
            $stmt->execute([$action, $detail]);
        } catch (Throwable $e) {
            // Ignore activity log failures safely
        }
    }
}

// === Permission Helper Functions ===
if (!function_exists('hasPermission')) {
    function hasPermission($module, $action = 'view', $checkUserId = null) {
        $userId = $checkUserId !== null ? intval($checkUserId) : intval($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) return false;
        
        // If checking current logged-in user
        if ($userId === intval($_SESSION['user_id'] ?? 0)) {
            $status = $_SESSION['status'] ?? '';
            if ($status === 'ຜູ້ບໍລິຫານ' || strtolower($status) === 'admin' || $userId === 1) {
                return true;
            }
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
                $uStmt = $pdo->prepare("SELECT * FROM tbuser WHERE Id = ?");
                $uStmt->execute([$userId]);
                $uRow = $uStmt->fetch(PDO::FETCH_ASSOC);
            }

            if (!$uRow) return false;

            $userStatusVal = $uRow['status'] ?? $uRow['userstatus'] ?? '';
            $isAdmin = ($userId === 1 || strtolower($userStatusVal) === 'admin' || strtolower($userStatusVal) === 'super admin' || $userStatusVal === 'ຜູ້ບໍລິຫານ');
            if ($isAdmin) return true;

            // Fetch target user's switch states
            $switchStates = [];
            if (isset($pdo)) {
                $swStmt = $pdo->prepare("SELECT switch_key, is_enabled FROM user_permission_switch_states WHERE user_id = ?");
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
            'expiry_check'     => 'expiry_check',
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

        // Check general edit/delete permission columns in tbuser
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

        // Check view/module access flag
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
            $stmt = $pdo->prepare("SELECT * FROM tbuser WHERE Id = ? LIMIT 1");
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

// Global Helper Functions for Bank Assets
if (!function_exists('resolveBankLogo')) {
    function resolveBankLogo($logoFile, $bankCode = '') {
        if (empty($logoFile) || trim($logoFile) === '') {
            return '';
        }
        $filename = basename(trim($logoFile));
        $root = dirname(__DIR__);
        $htdocsRoot = 'D:/xampp/htdocs/MiniPos';
        if (file_exists($root . '/assets/img/banks/' . $filename) || file_exists($htdocsRoot . '/assets/img/banks/' . $filename)) {
            return '../../assets/img/banks/' . $filename;
        }
        return '../../assets/img/banks/' . $filename;
    }
}

if (!function_exists('resolveBankQr')) {
    function resolveBankQr($qrFile, $bankCode = '') {
        if (empty($qrFile) || trim($qrFile) === '') {
            return '';
        }
        $filename = basename(trim($qrFile));
        $root = dirname(__DIR__);
        $htdocsRoot = 'D:/xampp/htdocs/MiniPos';
        if (file_exists($root . '/assets/img/qr/' . $filename) || file_exists($htdocsRoot . '/assets/img/qr/' . $filename)) {
            return '../../assets/img/qr/' . $filename;
        }
        return '../../assets/img/qr/' . $filename;
    }
}

// Global Branch Store Helper Functions
if (!function_exists('isMainBranch')) {
    function isMainBranch($pdo, $store_id = null) {
        if ($store_id === null) {
            $store_id = intval($_SESSION['store_id'] ?? 1);
        }
        $isAdmin = (
            ($_SESSION['status'] ?? '') === 'ຜູ້ບໍລິຫານ' ||
            strtolower($_SESSION['status'] ?? '') === 'admin' ||
            ($_SESSION['user_id'] ?? 0) == 1
        );
        if ($isAdmin && $store_id == 1) {
            return true;
        }
        if (!$pdo) return ($store_id == 1);
        try {
            $stmt = $pdo->prepare("SELECT is_main FROM tbstore WHERE store_id = ? LIMIT 1");
            $stmt->execute([$store_id]);
            $isMain = $stmt->fetchColumn();
            return (bool)$isMain;
        } catch (Exception $e) {
            return ($store_id == 1);
        }
    }
}

if (!function_exists('canViewAllBranches')) {
    // Seeing or switching between every branch is decided by the user's own branch, not their
    // role: the owner account (Id 1) and any user assigned to the main branch see every branch;
    // any user assigned to a sub-branch (admin or staff) is locked to their own store_id.
    function canViewAllBranches($pdo) {
        if (intval($_SESSION['user_id'] ?? 0) === 1) {
            return true;
        }
        if (!$pdo) {
            return false;
        }
        static $mainByStore = [];
        $storeId = intval($_SESSION['store_id'] ?? 0);
        if (!array_key_exists($storeId, $mainByStore)) {
            try {
                $stmt = $pdo->prepare("SELECT is_main FROM tbstore WHERE store_id = ? LIMIT 1");
                $stmt->execute([$storeId]);
                $mainByStore[$storeId] = (bool)$stmt->fetchColumn();
            } catch (Throwable $e) {
                $mainByStore[$storeId] = false;
            }
        }
        return $mainByStore[$storeId];
    }
}

if (!function_exists('getActiveStoreId')) {
    function getActiveStoreId($pdo) {
        $userStoreId = intval($_SESSION['store_id'] ?? 1);
        if (!canViewAllBranches($pdo)) {
            return $userStoreId;
        }

        // May see every branch: honour a branch picked via the switcher, if any
        if (isset($_SESSION['active_store_id']) && $_SESSION['active_store_id'] !== '') {
            return intval($_SESSION['active_store_id']);
        }
        return $userStoreId;
    }
}

if (!function_exists('getCompanyInfoForBranch')) {
    // Each branch owns its full profile (name/address/phone/tax/receipt-footer/logo/QR) on its
    // own tbcompanyinfo row. A branch that hasn't customised its own row yet inherits the main
    // branch's row for the fields that live ONLY there (tax/logo/receipt-footer/QR) — but
    // name/address/phone always come from THIS branch's own tbstore row instead, since the
    // main branch already enters those once, per branch, in Branch Management when the branch
    // is created; there's no reason to make the shop type them a second time here.
    function getCompanyInfoForBranch($pdo, $storeId) {
        if (!$pdo) {
            return [
                'com_id' => 1,
                'com_name_la' => 'Mini POS Store',
                'com_address' => '',
                'com_tel' => '',
                'barcode' => 'ຂອບໃຈທີ່ມາອຸດໜູນ, ໂອກາດໜ້າເຊີນໃໝ່!',
                'img_url' => 'logo.png',
                'vat_percent' => 0,
                'tax_type' => 'none'
            ];
        }
        $mainStoreId = intval($pdo->query("SELECT store_id FROM tbstore WHERE is_main = 1 ORDER BY store_id ASC LIMIT 1")->fetchColumn() ?: 1);
        $storeId = intval($storeId);

        $company = [];
        try {
            $stmt = $pdo->prepare("SELECT * FROM tbcompanyinfo WHERE branch_id = ? LIMIT 1");
            $stmt->execute([$storeId]);
            $ownRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            if (!empty($ownRow['com_name_la'])) {
                $company = $ownRow;
            }
        } catch (Throwable $e) {}

        // This branch hasn't entered its own profile yet — inherit the main branch's, so a
        // freshly added branch shows something sensible instead of a blank receipt.
        if (!$company && $storeId !== $mainStoreId) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM tbcompanyinfo WHERE branch_id = ? LIMIT 1");
                $stmt->execute([$mainStoreId]);
                $company = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $e) {}
        }

        if (!$company) {
            try {
                // `tbstore.logo_path` doesn't exist on every install — only SELECT it when present.
                $stCols = $pdo->query("SHOW COLUMNS FROM `tbstore`")->fetchAll(PDO::FETCH_COLUMN);
                $logoExpr = in_array('logo_path', $stCols, true) ? '`logo_path`' : "'logo.png'";
                $stmt = $pdo->prepare("SELECT store_name as com_name_la, address as com_address, tel as com_tel, 'ຂອບໃຈທີ່ມາອຸດໜູນ, ໂອກາດໜ້າເຊີນໃໝ່!' as barcode, {$logoExpr} as img_url FROM tbstore WHERE store_id = ? LIMIT 1");
                $stmt->execute([$storeId]);
                $company = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $e) { $company = []; }
        }

        // Empty DB (fresh / restored / wiped): return a safe shape so callers never hit
        // "undefined array key" on every field.
        $company = array_merge([
            'com_name_la' => '', 'com_address' => '', 'com_tel' => '', 'com_email' => '',
            'tax_id' => '', 'barcode' => '', 'img_url' => 'logo.png', 'qr_img' => '',
            'vat_percent' => 0, 'tax_type' => 'none',
        ], $company);

        // This branch's own tbstore row always wins for name/address/phone — even when the
        // rest of $company above came from the main branch's tbcompanyinfo fallback.
        if (empty($ownRow['com_name_la'])) {
            try {
                $stmt = $pdo->prepare("SELECT store_name, address, tel FROM tbstore WHERE store_id = ? LIMIT 1");
                $stmt->execute([$storeId]);
                $ownStoreRow = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($ownStoreRow) {
                    $company['com_name_la'] = $ownStoreRow['store_name'];
                    if (empty($company['com_address'])) $company['com_address'] = $ownStoreRow['address'];
                    if (empty($company['com_tel']))     $company['com_tel']     = $ownStoreRow['tel'];
                }
            } catch (Throwable $e) {}
        }

        return $company;
    }
}

if (!function_exists('getLowStockAlerts')) {
    function getLowStockAlerts($pdo, $store_id = null) {
        if (!$pdo) return [];
        try {
            // cut_qty = 0 = service / made-to-order item that never deducts stock — its qty
            // column isn't meaningful inventory, so it must never trigger a low/out-of-stock alert.
            if ($store_id !== null && intval($store_id) > 0) {
                $sql = "SELECT p.*, s.store_name, s.is_main
                        FROM products p
                        JOIN tbstore s ON p.store_id = s.store_id
                        WHERE (p.qty <= 10 OR (p.min_qty IS NOT NULL AND p.min_qty > 0 AND p.qty <= p.min_qty) OR p.qty <= 0)
                          AND p.cut_qty != 0
                          AND s.status = 'active'
                          AND p.store_id = ?
                        ORDER BY p.qty ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([intval($store_id)]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $sql = "SELECT p.*, s.store_name, s.is_main
                        FROM products p
                        JOIN tbstore s ON p.store_id = s.store_id
                        WHERE (p.qty <= 10 OR (p.min_qty IS NOT NULL AND p.min_qty > 0 AND p.qty <= p.min_qty) OR p.qty <= 0)
                          AND p.cut_qty != 0
                          AND s.status = 'active'
                        ORDER BY s.is_main ASC, p.qty ASC";
                return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            return [];
        }
    }
}

if (!function_exists('getIncomingTransfersForStore')) {
    function getIncomingTransfersForStore($pdo, $store_id) {
        if (!$pdo || empty($store_id)) return [];
        try {
            $sql = "SELECT t.*, s.store_name as from_store_name, to_s.store_name as to_store_name
                    FROM stock_transfers t
                    LEFT JOIN tbstore s ON t.from_store_id = s.store_id
                    LEFT JOIN tbstore to_s ON t.to_store_id = to_s.store_id
                    WHERE (t.to_store_id = ? OR t.from_store_id = ?)
                      AND t.transfer_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    ORDER BY t.transfer_id DESC LIMIT 10";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([intval($store_id), intval($store_id)]);
            $transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($transfers as &$t) {
                $trfId = intval($t['transfer_id']);
                $stmtDet = $pdo->prepare("
                    SELECT COALESCE(NULLIF(TRIM(d.product_name), ''), p.product_name, 'ສິນຄ້າ') as prod_name,
                           SUM(d.qty) as total_qty,
                           SUM(d.qty * d.bprice) as line_value
                    FROM stock_transfer_details d
                    LEFT JOIN products p ON d.product_id = p.product_id
                    WHERE d.transfer_id = ?
                    GROUP BY d.product_id, prod_name
                ");
                $stmtDet->execute([$trfId]);
                $details = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

                $summaryParts = [];
                $totalValue = 0;
                foreach ($details as $d) {
                    $summaryParts[] = $d['prod_name'] . ' x' . $d['total_qty'];
                    $totalValue += floatval($d['line_value']);
                }

                $t['total_items'] = count($details);
                $t['item_summary'] = implode(', ', $summaryParts);
                $t['total_value'] = $totalValue;
            }
            unset($t);

            return $transfers;
        } catch (Exception $e) {
            return [];
        }
    }
}

if (!function_exists('getNewProductsForStore')) {
    function getNewProductsForStore($pdo, $store_id) {
        if (!$pdo || empty($store_id)) return [];
        try {
            $sql = "SELECT p.*, s.store_name 
                    FROM products p 
                    JOIN tbstore s ON p.store_id = s.store_id 
                    WHERE p.store_id = ? AND p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    ORDER BY p.product_id DESC LIMIT 10";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([intval($store_id)]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}

if (!function_exists('getSubBranchStockImportsForMain')) {
    function getSubBranchStockImportsForMain($pdo) {
        if (!$pdo) return [];
        try {
            $sql = "SELECT id.import_detail_id, id.import_id, id.product_id, id.unit_name, id.quantity, id.cost_price, id.total_cost,
                           COALESCE(p.product_name, 'ສິນຄ້າ') as product_name, s.store_id, s.store_name, i.import_date, i.invoice_number, i.supplier_name, i.notes,
                           COALESCE(NULLIF(u.fname, ''), u.username, 'Admin') as creator_name
                    FROM import_details id
                    JOIN imports i ON id.import_id = i.import_id
                    JOIN tbstore s ON (
                        CASE 
                            WHEN i.store_id > 0 THEN i.store_id
                            ELSE (SELECT store_id FROM products WHERE product_id = id.product_id LIMIT 1)
                        END
                    ) = s.store_id
                    LEFT JOIN products p ON (id.product_id = p.product_id AND p.store_id = s.store_id)
                    LEFT JOIN tbuser u ON i.created_by = u.Id
                    WHERE (s.is_main = 0 OR s.store_id != 1)
                      AND i.import_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    ORDER BY id.import_detail_id DESC LIMIT 20";
            $stmt = $pdo->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Exception $e) {
            return [];
        }
    }
}

// ============================================================
// Multi-Branch Store Switcher Handler
// Kept at the very bottom: it depends on isMainBranch() and $pdo, both defined above.
// A URL carrying ?switch_store_id=N (explicit switcher) or ?store_id=N (report branch
// filter) parks the choice in $_SESSION['active_store_id'], which getActiveStoreId()
// reads on the next request. Only users who pass canViewAllBranches() may switch; everyone
// else is ignored here and stays locked to their own store.
// ============================================================
if (!empty($_SESSION['user_id'])) {
    $switchStoreParam = null;
    if (isset($_GET['switch_store_id'])) {
        $switchStoreParam = intval($_GET['switch_store_id']);
    } elseif (isset($_GET['store_id']) && $_GET['store_id'] !== '') {
        $switchStoreParam = intval($_GET['store_id']);
    }
    if ($switchStoreParam !== null && $switchStoreParam > 0) {
        $canSwitchStore = canViewAllBranches($pdo);
        if ($canSwitchStore) {
            $_SESSION['active_store_id'] = $switchStoreParam;
        }
    }
}
?>