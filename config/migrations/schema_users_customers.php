<?php
/**
 * ສ່ວນ: ອັບເດດໂຄງສ້າງ DB (2): ຜູ້ໃຊ້ ແລະ ລູກຄ້າ
 * ສ້າງ/ປັບ tbuser (ຄໍລຳຂໍ້ມູນສ່ວນຕົວ, ປ່ຽນຊື່ຄໍລຳເກົ່າ), ຕາຕະລາງລູກຄ້າ, ແລະ
 * ສຳເນົາໝວດໝູ່/ຫົວໜ່ວຍຂອງສາຂາໃຫຍ່ໄປໃຫ້ທຸກສາຂາຍ່ອຍ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ config/db.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

    // Ensure tbuser table exists (auto-create on fresh install)
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `st_tbuser` (
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
        $userCount = (int)$pdo->query("SELECT COUNT(*) FROM `st_tbuser`")->fetchColumn();
        if ($userCount === 0) {
            $pdo->exec("INSERT INTO `st_tbuser` (Id, user_code, username, password, status, store_id,
                dashboard, sale, item_sales, stock, categories, products, import_stock, import_list,
                report, daily_report, all_sales, best_seller, profit_cost, financial, category_sales,
                delete_bills, accounting, setup, users, permissions, branches, edit, customers,
                `database`, stock_transfer, transfer_history)
                VALUES (1, '001', 'admin', SHA2('123456', 256), 'Admin', 1,
                1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1)");
        }
    } catch (Throwable $ex) {}

    try {
        $hasBranchId = $pdo->query("SHOW COLUMNS FROM st_tbuser LIKE 'branch_id'")->fetch();
        if ($hasBranchId) {
            $pdo->exec("ALTER TABLE st_tbuser CHANGE COLUMN `branch_id` `store_id` INT(11) DEFAULT 1");
        }
    } catch (Throwable $ex) {}

    // Migration for tbuser columns: drop userid, rename userpass->password, userstatus->status
    try {
        $hasUserid = $pdo->query("SHOW COLUMNS FROM st_tbuser LIKE 'userid'")->fetch();
        if ($hasUserid) {
            $pdo->exec("ALTER TABLE st_tbuser DROP COLUMN `userid`");
        }
    } catch (Throwable $ex) {}

    try {
        $hasUserpass = $pdo->query("SHOW COLUMNS FROM st_tbuser LIKE 'userpass'")->fetch();
        if ($hasUserpass) {
            $pdo->exec("ALTER TABLE st_tbuser CHANGE COLUMN `userpass` `password` VARCHAR(255) DEFAULT NULL");
        }
    } catch (Throwable $ex) {}

    try {
        $hasUserstatus = $pdo->query("SHOW COLUMNS FROM st_tbuser LIKE 'userstatus'")->fetch();
        if ($hasUserstatus) {
            $pdo->exec("ALTER TABLE st_tbuser CHANGE COLUMN `userstatus` `status` VARCHAR(50) DEFAULT NULL");
        }
    } catch (Throwable $ex) {}

    // Migration for customers table
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `st_customers` (
            `customer_id` INT AUTO_INCREMENT PRIMARY KEY,
            `customer_code` VARCHAR(50) NOT NULL UNIQUE,
            `customer_name` VARCHAR(150) NOT NULL,
            `phone` VARCHAR(50) NULL,
            `email` VARCHAR(100) NULL,
            `address` TEXT NULL,
            `notes` TEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $custCols = $pdo->query("SHOW COLUMNS FROM st_customers")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('member_card', $custCols)) {
            $pdo->exec("ALTER TABLE `st_customers` ADD COLUMN `member_card` VARCHAR(50) NULL AFTER `phone`");
        }
        if (!in_array('address', $custCols)) {
            $pdo->exec("ALTER TABLE `st_customers` ADD COLUMN `address` TEXT NULL AFTER `phone`");
        }
        if (!in_array('points', $custCols)) {
            $pdo->exec("ALTER TABLE `st_customers` ADD COLUMN `points` INT NOT NULL DEFAULT 0");
        }
        if (!in_array('store_id', $custCols)) {
            $pdo->exec("ALTER TABLE `st_customers` ADD COLUMN `store_id` INT DEFAULT 1 AFTER `notes`");
            $pdo->exec("UPDATE `st_customers` SET `store_id` = 1 WHERE `store_id` IS NULL OR `store_id` = 0");
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
        'customers' => "TINYINT(1) DEFAULT 1",
        // ສະຖານະບັນຊີ: 0 = ປິດໃຊ້ງານ (ພະນັກງານລາອອກແລ້ວ) — ເຂົ້າລະບົບບໍ່ໄດ້ ແຕ່ປະຫວັດການຂາຍຍັງຢູ່
        'account_active' => "TINYINT(1) NOT NULL DEFAULT 1"
    ];
    foreach ($userCols as $col => $def) {
        try {
            $colCheck = $pdo->query("SHOW COLUMNS FROM st_tbuser LIKE '{$col}'")->fetch();
            if (!$colCheck) {
                $pdo->exec("ALTER TABLE st_tbuser ADD COLUMN `{$col}` {$def}");
            }
        } catch (Throwable $ex) {}
    }

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `st_categories` (
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
            INSERT INTO st_categories (category_name, description, store_id)
            SELECT mc.category_name, mc.description, s.store_id
            FROM st_categories mc
            CROSS JOIN st_tbstore s
            WHERE mc.store_id = (SELECT store_id FROM st_tbstore WHERE is_main = 1 LIMIT 1)
              AND s.store_id != mc.store_id
              AND NOT EXISTS (
                  SELECT 1 FROM st_categories sc
                  WHERE sc.store_id = s.store_id AND sc.category_name = mc.category_name
              )
        ");
    } catch (Throwable $ex) {}

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `st_units` (
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
            INSERT INTO st_units (unit_name, description, store_id)
            SELECT mu.unit_name, mu.description, s.store_id
            FROM st_units mu
            CROSS JOIN st_tbstore s
            WHERE mu.store_id = (SELECT store_id FROM st_tbstore WHERE is_main = 1 LIMIT 1)
              AND s.store_id != mu.store_id
              AND NOT EXISTS (
                  SELECT 1 FROM st_units su
                  WHERE su.store_id = s.store_id AND su.unit_name = mu.unit_name
              )
        ");
    } catch (Throwable $ex) {}

