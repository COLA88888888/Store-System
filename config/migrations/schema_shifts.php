<?php
/**
 * ສ່ວນ: ອັບເດດໂຄງສ້າງ DB: ຈັດການກະເຮັດວຽກ (Work Shifts & Cash Drawer)
 * ສ້າງຕາຕະລາງ:
 *  - st_shifts: ຕາຕະລາງຕັ້ງຄ່າກະ (ຊື່ກະ, ເວລາເລີ່ມ, ເວລາສິ້ນສຸດ, ສະຖານະ)
 *  - st_shift_sessions: ຕາຕະລາງປະຫວັດການເປີດ-ປິດກະ, ເງິນເລີ່ມຕົ້ນ, ຍອດຂາຍ, ເງິນນັບໄດ້, ສ່ວນຕ່າງ
 * ເພີ່ມຄໍລຳ st_tbsale_save.shift_session_id
 */

if (isset($pdo) && $pdo instanceof PDO && empty($_SESSION['shift_schema_v2'])) {
    try {
        // 1. ຕາຕະລາງຕັ້ງຄ່າກະ (Shifts definition)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `st_shifts` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `shift_name` VARCHAR(100) NOT NULL,
            `start_time` TIME NOT NULL,
            `end_time` TIME NOT NULL,
            `store_id` INT NOT NULL DEFAULT 1,
            `status` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_shift_store` (`store_id`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // ບໍ່ໃສ່ກະຕົວຢ່າງ (ກະເຊົ້າ/ກະບ່າຍ) ເອງ — ຮ້ານຕັ້ງກະເອງໃນ ຕັ້ງຄ່າ › ຈັດການກະເຮັດວຽກ

        // 2. ຕາຕະລາງ session ການເປີດ-ປິດກະ
        $pdo->exec("CREATE TABLE IF NOT EXISTS `st_shift_sessions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `shift_id` INT NOT NULL,
            `shift_name` VARCHAR(100) NOT NULL,
            `store_id` INT NOT NULL DEFAULT 1,
            `branch_id` INT NOT NULL DEFAULT 1,
            `opened_by_user_id` INT NOT NULL,
            `opened_by_name` VARCHAR(100) NOT NULL,
            `open_time` DATETIME NOT NULL,
            `initial_cash` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `closed_by_user_id` INT NULL,
            `closed_by_name` VARCHAR(100) NULL,
            `close_time` DATETIME NULL,
            `system_sales_cash` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `system_sales_transfer` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `system_sales_debt` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `system_total_sales` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `system_total_bills` INT NOT NULL DEFAULT 0,
            `counted_sales_cash` DECIMAL(15,2) NULL,
            `difference_cash` DECIMAL(15,2) NULL,
            `status` ENUM('open', 'closed') NOT NULL DEFAULT 'open',
            `notes` TEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_ss_store_status` (`store_id`, `status`),
            INDEX `idx_ss_open_time` (`open_time`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // 3. ເພີ່ມຄໍລຳ shift_session_id ໃນ st_tbsale_save ຖ້າຍັງບໍ່ມີ
        $saleCols = $pdo->query("SHOW COLUMNS FROM st_tbsale_save")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('shift_session_id', $saleCols)) {
            $pdo->exec("ALTER TABLE st_tbsale_save ADD COLUMN `shift_session_id` INT NULL AFTER `store_id`, ADD INDEX `idx_shift_session` (`shift_session_id`)");
        }

        $_SESSION['shift_schema_v2'] = 1;
    } catch (Throwable $e) {
        error_log("Shift schema migration error: " . $e->getMessage());
    }
}

// v3: ພະນັກງານປະຈຳກະ — st_shifts.staff_user_ids (Id ຜູ້ໃຊ້ຄັ່ນດ້ວຍຈຸດ, ເລືອກໃນ ຕັ້ງຄ່າ > ກະເຮັດວຽກ)
// ແລະ st_shift_sessions.staff_names (ຊື່ພະນັກງານ ຕອນເປີດກະ — ລາຍງານປິດກະບໍ່ປ່ຽນ ເຖິງແກ້ກະພາຍຫຼັງ)
if (isset($pdo) && $pdo instanceof PDO && empty($_SESSION['shift_schema_v3'])) {
    try {
        $shiftCols = $pdo->query("SHOW COLUMNS FROM st_shifts")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('staff_user_ids', $shiftCols, true)) {
            $pdo->exec("ALTER TABLE st_shifts ADD COLUMN `staff_user_ids` VARCHAR(500) NULL AFTER `end_time`");
        }
        $sessCols = $pdo->query("SHOW COLUMNS FROM st_shift_sessions")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('staff_names', $sessCols, true)) {
            $pdo->exec("ALTER TABLE st_shift_sessions ADD COLUMN `staff_names` VARCHAR(500) NULL AFTER `opened_by_name`");
        }
        $_SESSION['shift_schema_v3'] = 1;
    } catch (Throwable $e) {
        error_log("Shift schema v3 migration error: " . $e->getMessage());
    }
}

// v4: st_shift_sessions.last_active_at — ເວລາທີ່ພະນັກງານໃຊ້ງານໜ້າຂາຍຫຼ້າສຸດ (heartbeat ຈາກ POS).
// ໃຊ້ຕັດສິນປິດກະອໍໂຕ້: ຮອດໂມງປິດແລ້ວ ແລະ ບໍ່ມີໃຜໃຊ້ງານ (config/helpers/shift_auto.php)
if (isset($pdo) && $pdo instanceof PDO && empty($_SESSION['shift_schema_v4'])) {
    try {
        $sessCols = $pdo->query("SHOW COLUMNS FROM st_shift_sessions")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('last_active_at', $sessCols, true)) {
            $pdo->exec("ALTER TABLE st_shift_sessions ADD COLUMN `last_active_at` DATETIME NULL AFTER `open_time`");
        }
        $_SESSION['shift_schema_v4'] = 1;
    } catch (Throwable $e) {
        error_log("Shift schema v4 migration error: " . $e->getMessage());
    }
}
