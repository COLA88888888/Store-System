<?php
/**
 * ສ່ວນ: ອັບເດດໂຄງສ້າງ DB (4): ການຕັ້ງຄ່າ, ການຂາຍ
 * ສ້າງ system_settings, ຕາຕະລາງບິນຂາຍ
 * tbsale_save / tbsale_save_detail / sales ແລະ ສະວິດສິດຂອງຜູ້ໃຊ້.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ config/db.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

    // Auto Migration for system_settings table
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `st_system_settings` (
            `setting_id` INT AUTO_INCREMENT PRIMARY KEY,
            `setting_key` VARCHAR(100) NOT NULL UNIQUE,
            `setting_value` TEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $setCount = (int)$pdo->query("SELECT COUNT(*) FROM st_system_settings")->fetchColumn();
        if ($setCount === 0) {
            $pdo->exec("INSERT INTO st_system_settings (setting_key, setting_value) VALUES
                ('vat_rate', '0'),
                ('expiry_warning_days', '30')");
        }
        // Customer loyalty points rule: earn Y points for every X kip of net spend
        $pdo->exec("INSERT IGNORE INTO st_system_settings (setting_key, setting_value) VALUES
            ('loyalty_enabled', '0'),
            ('loyalty_spend_amount', '100000'),
            ('loyalty_points_earned', '1')");
        // Active display currency for POS/receipt (KIP | THB | USD | CNY). KIP = base, no conversion.
        $pdo->exec("INSERT IGNORE INTO st_system_settings (setting_key, setting_value) VALUES ('display_currency', 'KIP')");
        // Show a daily-resetting queue number on the receipt (coffee-shop style). '0' = off.
        $pdo->exec("INSERT IGNORE INTO st_system_settings (setting_key, setting_value) VALUES ('queue_enabled', '0')");
    } catch (Throwable $ex) {}

    // Auto Migration for tbsale_save, tbsale_save_detail & sales view
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `st_tbsale_save` (
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

        $pdo->exec("CREATE TABLE IF NOT EXISTS `st_tbsale_save_detail` (
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

        $pdo->exec("CREATE TABLE IF NOT EXISTS `st_sales` (
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

        $salesCols = $pdo->query("SHOW COLUMNS FROM st_sales")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('created_at', $salesCols)) {
            $pdo->exec("ALTER TABLE st_sales ADD COLUMN `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP");
        }
        if (!in_array('sold_by', $salesCols)) {
            $pdo->exec("ALTER TABLE st_sales ADD COLUMN `sold_by` INT DEFAULT NULL");
        }
        if (!in_array('subtotal', $salesCols)) {
            $pdo->exec("ALTER TABLE st_sales ADD COLUMN `subtotal` DECIMAL(12,2) DEFAULT 0.00");
        }
        if (!in_array('discount_amount', $salesCols)) {
            $pdo->exec("ALTER TABLE st_sales ADD COLUMN `discount_amount` DECIMAL(12,2) DEFAULT 0.00");
        }
        if (!in_array('vat_amount', $salesCols)) {
            $pdo->exec("ALTER TABLE st_sales ADD COLUMN `vat_amount` DECIMAL(12,2) DEFAULT 0.00");
        }
        if (!in_array('total_amount', $salesCols)) {
            $pdo->exec("ALTER TABLE st_sales ADD COLUMN `total_amount` DECIMAL(12,2) DEFAULT 0.00");
        }
        if (!in_array('total_profit', $salesCols)) {
            $pdo->exec("ALTER TABLE st_sales ADD COLUMN `total_profit` DECIMAL(12,2) DEFAULT 0.00");
        }

        // ບໍ່ຄັດລອກບິນຈາກ st_tbsale_save ໄປ st_sales ເອງ (ຂໍ້ມູນເກີດຂຶ້ນເອງ) — ການຂາຍໃໝ່ບັນທຶກທັງສອງຕາຕະລາງຢູ່ແລ້ວ
    } catch (Throwable $ex) {}

    // Ensure user_permission_switch_states exists
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `st_user_permission_switch_states` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `switch_key` VARCHAR(100) NOT NULL,
            `is_enabled` TINYINT(1) DEFAULT 1,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_user_switch` (`user_id`, `switch_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $ex) {}
