<?php
/**
 * ສ່ວນ: ອັບເດດໂຄງສ້າງ DB (4): ການຕັ້ງຄ່າ, ການຂາຍ, ໜີ້
 * ສ້າງ system_settings, ລູກຄ້າຕິດໜີ້ (customer_debts / customer_debt_payments), ຕາຕະລາງບິນຂາຍ
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

    // ລູກຄ້າຕິດໜີ້ (customer debts). One row per bill sold on credit at the POS
    // (invoice_number set) or per opening balance entered by hand (invoice_number NULL).
    // amount = what was put on credit (bill total minus any deposit paid at the till);
    // paid_amount / balance move as repayments land in customer_debt_payments.
    // status: unpaid | partial | paid | cancelled (cancelled when the sale bill is deleted).
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `st_customer_debts` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `store_id` INT NOT NULL DEFAULT 1,
          `customer_id` INT NOT NULL,
          `customer_name` VARCHAR(150) DEFAULT NULL,
          `invoice_number` VARCHAR(50) DEFAULT NULL,
          `bill_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
          `deposit_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
          `amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
          `paid_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
          `balance` DECIMAL(15,2) NOT NULL DEFAULT 0,
          `status` VARCHAR(20) NOT NULL DEFAULT 'unpaid',
          `due_date` DATE DEFAULT NULL,
          `note` VARCHAR(255) DEFAULT NULL,
          `created_by` VARCHAR(100) DEFAULT NULL,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_debts_store_customer` (`store_id`, `customer_id`),
          KEY `idx_debts_invoice` (`invoice_number`),
          KEY `idx_debts_status` (`store_id`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `st_customer_debt_payments` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `debt_id` INT NOT NULL,
          `store_id` INT NOT NULL DEFAULT 1,
          `customer_id` INT NOT NULL,
          `amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
          `payment_method` VARCHAR(50) NOT NULL DEFAULT 'ເງິນສົດ',
          `bank_account_id` INT DEFAULT NULL,
          `bank_name` VARCHAR(100) DEFAULT NULL,
          `note` VARCHAR(255) DEFAULT NULL,
          `received_by` VARCHAR(100) DEFAULT NULL,
          `paid_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_debt_pay_debt` (`debt_id`),
          KEY `idx_debt_pay_store_date` (`store_id`, `paid_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
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

        $pdo->exec("INSERT IGNORE INTO st_sales (sale_id, invoice_number, sold_by, customer_id, customer_name, subtotal, discount_amount, total_amount, cash_received, change_amount, payment_type, status, created_at)
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
            FROM st_tbsale_save");
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
