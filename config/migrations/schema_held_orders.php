<?php
/**
 * Migration: Held Orders (ບິນທີ່ພັກໄວ້) Table
 * ບັນທຶກບິນທີ່ພັກໄວ້ລົງໃນຖານຂໍ້ມູນຢ່າງຖາວອນ
 * ບໍ່ໃຫ້ຫາຍຈັກມື້ ຈົນກວ່າຈະມີການມາກົດຊຳລະ
 */
if (isset($pdo) && $pdo instanceof PDO && empty($_SESSION['held_orders_schema_v1'])) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `st_held_orders` (
            `id` VARCHAR(60) NOT NULL PRIMARY KEY,
            `store_id` INT NOT NULL DEFAULT 1,
            `user_id` INT NULL,
            `user_name` VARCHAR(100) NULL,
            `customer_id` INT NULL,
            `customer_name` VARCHAR(150) NULL,
            `customer_phone` VARCHAR(50) NULL,
            `hold_date` DATE NOT NULL,
            `hold_time` VARCHAR(20) NOT NULL,
            `items_count` INT NOT NULL DEFAULT 0,
            `total_qty` DECIMAL(12,2) NOT NULL DEFAULT 0,
            `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
            `discount` VARCHAR(50) NULL DEFAULT '0',
            `discount_mode` VARCHAR(20) NULL DEFAULT 'amount',
            `cart_json` LONGTEXT NOT NULL,
            `customer_json` TEXT NULL,
            `status` ENUM('held', 'paid', 'deleted') NOT NULL DEFAULT 'held',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_held_store_status` (`store_id`, `status`),
            INDEX `idx_held_date` (`hold_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $_SESSION['held_orders_schema_v1'] = true;
    } catch (Throwable $e) {
        error_log("Held orders migration error: " . $e->getMessage());
    }
}
