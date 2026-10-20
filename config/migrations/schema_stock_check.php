<?php
/**
 * ສ່ວນ: ອັບເດດໂຄງສ້າງ DB: ເຊັກສະຕັອກ (ນັບສິນຄ້າຈິງທຽບກັບລະບົບ)
 * ສ້າງຕາຕະລາງ st_stock_checks (ໃບເຊັກສະຕັອກ) ແລະ st_stock_check_items (ລາຍການສິນຄ້າໃນໃບ),
 * ແລະ ຄໍລຳສິດ st_tbuser.stock_check. ກວດຄັ້ງດຽວຕໍ່ session ແບບດຽວກັບ schema_loyalty.php
 * (ບໍ່ອີງໃສ່ໄຟລ໌ flag ເພາະອາດຖືກສຳເນົາມາຈາກເຄື່ອງອື່ນ).
 * ຖືກ require ຈາກ config/db.php ຕໍ່ຈາກ schema_loyalty.php.
 *
 * ໝາຍເຫດ: ຫ້າມໃຊ້ຊື່ stock_adjustments / stock_adjustment_items — ຢູ່ໃນ $unusedTables ຂອງ
 * schema_core.php ແລະ ຈະຖືກ DROP ອັດຕະໂນມັດ.
 */

if (isset($pdo) && $pdo instanceof PDO && empty($_SESSION['stock_check_schema_v5'])) {
    try {
        // ໃບເຊັກສະຕັອກ: draft = ກຳລັງນັບ, pending = ລໍຖ້າຫົວໜ້າອະນຸມັດ, approved = ອະນຸມັດໃຫ້ປັບແລ້ວ, completed = ປິດໃບ (ປັບສະຕັອກແລ້ວ)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `st_stock_checks` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `check_no` VARCHAR(30) NOT NULL,
            `store_id` INT NOT NULL,
            `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
            `note` VARCHAR(255) NULL,
            `user_id` INT NULL,
            `approved_by` INT NULL,
            `approved_at` DATETIME NULL,
            `completed_by` INT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `completed_at` DATETIME NULL,
            INDEX `idx_sc_store` (`store_id`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $scCols = $pdo->query("SHOW COLUMNS FROM st_stock_checks")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('approved_by', $scCols)) {
            $pdo->exec("ALTER TABLE st_stock_checks ADD COLUMN `approved_by` INT NULL AFTER `user_id`");
        }
        if (!in_array('approved_at', $scCols)) {
            $pdo->exec("ALTER TABLE st_stock_checks ADD COLUMN `approved_at` DATETIME NULL AFTER `approved_by`");
        }
        // v5: ໃຜສົ່ງໃຫ້ຫົວໜ້າກວດ ແລະ ເມື່ອໃດ — ໃຊ້ແຈ້ງເຕືອນ (ຫົວໜ້າເຫັນຜູ້ສົ່ງ; ອະນຸມັດແລ້ວແຈ້ງກັບຫາຜູ້ສົ່ງ)
        if (!in_array('submitted_by', $scCols)) {
            $pdo->exec("ALTER TABLE st_stock_checks ADD COLUMN `submitted_by` INT NULL AFTER `user_id`");
        }
        if (!in_array('submitted_at', $scCols)) {
            $pdo->exec("ALTER TABLE st_stock_checks ADD COLUMN `submitted_at` DATETIME NULL AFTER `submitted_by`");
        }

        // ລາຍການ: system_qty = ຈຳນວນໃນລະບົບຕອນເລີ່ມນັບ (snapshot), counted_qty = NULL ຖ້າຍັງບໍ່ໄດ້ນັບ.
        // ຕອນຢືນຢັນ ສະຕັອກຖືກປັບດ້ວຍສ່ວນຕ່າງ (counted - system) ເພື່ອບໍ່ໃຫ້ລົບລ້າງການຂາຍທີ່ເກີດລະຫວ່າງນັບ.
        $pdo->exec("CREATE TABLE IF NOT EXISTS `st_stock_check_items` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `check_id` INT NOT NULL,
            `product_id` INT NOT NULL,
            `barcode` VARCHAR(50) NULL,
            `product_name` VARCHAR(255) NOT NULL,
            `unit` VARCHAR(100) NULL,
            `cost_price` DECIMAL(15,2) NOT NULL DEFAULT 0,
            `system_qty` INT NOT NULL DEFAULT 0,
            `counted_qty` INT NULL,
            `note` VARCHAR(255) NULL,
            INDEX `idx_sci_check` (`check_id`),
            UNIQUE KEY `uk_sci_check_product` (`check_id`, `product_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // v2: ປະຫວັດວ່າສິນຄ້າໃດນັບແລ້ວ — ໃຜນັບ ແລະ ນັບເມື່ອໃດ (ອັບເດດທຸກຄັ້ງທີ່ຈຳນວນທີ່ນັບປ່ຽນ)
        $sciCols = $pdo->query("SHOW COLUMNS FROM st_stock_check_items")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('counted_by', $sciCols)) {
            $pdo->exec("ALTER TABLE st_stock_check_items ADD COLUMN `counted_by` INT NULL AFTER `counted_qty`");
        }
        if (!in_array('counted_at', $sciCols)) {
            $pdo->exec("ALTER TABLE st_stock_check_items ADD COLUMN `counted_at` DATETIME NULL AFTER `counted_by`");
        }

        // ສິດເມນູເຊັກສະຕັອກ — ຕອນສ້າງຄໍລຳຄັ້ງທຳອິດ ໃຫ້ຜູ້ທີ່ມີສິດ "ລາຍການສິນຄ້າ" ໄດ້ສິດນີ້ນຳ
        $scUserCols = $pdo->query("SHOW COLUMNS FROM st_tbuser")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('stock_check', $scUserCols)) {
            $pdo->exec("ALTER TABLE `st_tbuser` ADD COLUMN `stock_check` TINYINT(1) DEFAULT 0");
            if (in_array('products', $scUserCols)) {
                $pdo->exec("UPDATE `st_tbuser` SET `stock_check` = IFNULL(`products`, 0)");
            }
        }

        // v3: ສິດ "ນັບສະຕັອກ" (ພະນັກງານນັບ) — ປ້ອນ/ບັນທຶກຈຳນວນຕົວຈິງ + ເບິ່ງ/ສົ່ງອອກລາຍງານ ເທົ່ານັ້ນ.
        // ຄ່າເລີ່ມຕົ້ນ 0: ຜູ້ບໍລິຫານເປັນຄົນເປີດໃຫ້ໃນໜ້າກຳນົດສິດ.
        if (!in_array('stock_count', $scUserCols)) {
            $pdo->exec("ALTER TABLE `st_tbuser` ADD COLUMN `stock_count` TINYINT(1) DEFAULT 0");
        }

        $_SESSION['stock_check_schema_v5'] = 1;
    } catch (Throwable $ex) {
        // Leave the session flag unset so the next request tries again.
        error_log('MiniPos stock_check schema: ' . $ex->getMessage());
    }
}
