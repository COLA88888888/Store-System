<?php
/**
 * ສ່ວນ: ອັບເດດໂຄງສ້າງ DB (3): ຮ້ານ, ທະນາຄານ, ໂປຣໂມຊັ່ນ
 * ສ້າງຂໍ້ມູນຮ້ານ (tbcompanyinfo), ສາຂາ, ບັນຊີທະນາຄານ, ສິນຄ້າ/ການນຳເຂົ້າ, ຜູ້ສະໜອງ ແລະ ໂປຣໂມຊັ່ນ
 * ພ້ອມຂໍ້ມູນເລີ່ມຕົ້ນ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ config/db.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

    // Ensure tbcompanyinfo exists and has DB record
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `st_tbcompanyinfo` (
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

        $hasStartCol = $pdo->query("SHOW COLUMNS FROM `st_tbcompanyinfo` LIKE 'license_start_date'")->fetch();
        if (!$hasStartCol) {
            $pdo->exec("ALTER TABLE `st_tbcompanyinfo` ADD COLUMN `license_start_date` DATE DEFAULT '2026-01-01'");
        }
        $hasExpireCol = $pdo->query("SHOW COLUMNS FROM `st_tbcompanyinfo` LIKE 'license_expire_date'")->fetch();
        if (!$hasExpireCol) {
            $pdo->exec("ALTER TABLE `st_tbcompanyinfo` ADD COLUMN `license_expire_date` DATE DEFAULT '2026-12-31'");
        }

        $compCount = (int)$pdo->query("SELECT COUNT(*) FROM st_tbcompanyinfo")->fetchColumn();
        if ($compCount === 0) {
            $pdo->exec("INSERT INTO st_tbcompanyinfo (com_name_la, com_address, com_tel, barcode, img_url, license_start_date, license_expire_date, branch_id) VALUES 
                ('', '', '', 'ຂອບໃຈທີ່ມາອຸດໜູນ, ໂອກາດໜ້າເຊີນໃໝ່!', 'logo.png', '2026-01-01', '2026-12-31', 1)");
        }
    } catch (Throwable $ex) {}

    // Ensure tbstore exists and has DB record
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `st_tbstore` (
            `store_id` INT AUTO_INCREMENT PRIMARY KEY,
            `store_code` VARCHAR(50) DEFAULT 'STORE01',
            `store_name` VARCHAR(255) DEFAULT 'Mini POS Store',
            `address` TEXT DEFAULT NULL,
            `tel` VARCHAR(50) DEFAULT NULL,
            `logo_path` VARCHAR(255) DEFAULT 'assets/img/logo/logo.png',
            `status` VARCHAR(20) DEFAULT 'active',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $storeCount = (int)$pdo->query("SELECT COUNT(*) FROM st_tbstore")->fetchColumn();
        if ($storeCount === 0) {
            $pdo->exec("INSERT INTO st_tbstore (store_code, store_name, address, tel, logo_path, status) VALUES 
                ('STORE01', 'Mini POS Store', 'ນະຄອນຫຼວງວຽງຈັນ', '020-55555555', 'assets/img/logo/logo.png', 'active')");
        }
    } catch (Throwable $ex) {}

    // Ensure bank_accounts table exists and has seed bank records
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `st_bank_accounts` (
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
        $bankCols = $pdo->query("SHOW COLUMNS FROM st_bank_accounts")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('store_id', $bankCols)) {
            $pdo->exec("ALTER TABLE st_bank_accounts ADD COLUMN `store_id` INT NOT NULL DEFAULT 1");
        }

        // Add columns to tbsale_save & sales if missing
        $saleCols = $pdo->query("SHOW COLUMNS FROM st_tbsale_save")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('bank_account_id', $saleCols)) {
            $pdo->exec("ALTER TABLE st_tbsale_save ADD COLUMN `bank_account_id` INT DEFAULT NULL");
        }
        if (!in_array('bank_name', $saleCols)) {
            $pdo->exec("ALTER TABLE st_tbsale_save ADD COLUMN `bank_name` VARCHAR(100) DEFAULT NULL");
        }
        if (!in_array('points_earned', $saleCols)) {
            $pdo->exec("ALTER TABLE st_tbsale_save ADD COLUMN `points_earned` INT NOT NULL DEFAULT 0");
        }
        // Split-payment breakdown (cash portion vs QR/transfer portion) — checkout already
        // computes both for mixed-channel payments, but they were never persisted, so bill
        // reprints / report modals showed 0 for whichever channel wasn't sale_pay's cash figure.
        if (!in_array('cash_received', $saleCols)) {
            $pdo->exec("ALTER TABLE st_tbsale_save ADD COLUMN `cash_received` DECIMAL(12,2) DEFAULT 0.00");
        }
        if (!in_array('qr_received', $saleCols)) {
            $pdo->exec("ALTER TABLE st_tbsale_save ADD COLUMN `qr_received` DECIMAL(12,2) DEFAULT 0.00");
        }

        $salesCols = $pdo->query("SHOW COLUMNS FROM st_sales")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('bank_account_id', $salesCols)) {
            $pdo->exec("ALTER TABLE st_sales ADD COLUMN `bank_account_id` INT DEFAULT NULL");
        }
        if (!in_array('bank_name', $salesCols)) {
            $pdo->exec("ALTER TABLE st_sales ADD COLUMN `bank_name` VARCHAR(100) DEFAULT NULL");
        }
    } catch (Throwable $ex) {}

    try {
        $hasSize = $pdo->query("SHOW COLUMNS FROM st_products LIKE 'size'")->fetch();
        if ($hasSize) {
            $pdo->exec("ALTER TABLE st_products CHANGE COLUMN `size` `unit` VARCHAR(100) DEFAULT NULL");
        }
        $hasUnit = $pdo->query("SHOW COLUMNS FROM st_products LIKE 'unit'")->fetch();
        if (!$hasUnit) {
            $pdo->exec("ALTER TABLE st_products ADD COLUMN `unit` VARCHAR(100) DEFAULT NULL");
        }

        // products.unit_id is the real link into the units table (mirrors category_id ->
        // categories). products.unit (the plain-text name) stays alongside it as a synced
        // display cache — dropping it would require touching every existing consumer that
        // still reads it directly (POS cart/receipt JS, stock_transfer, reports, product_units).
        $hasUnitId = $pdo->query("SHOW COLUMNS FROM st_products LIKE 'unit_id'")->fetch();
        if (!$hasUnitId) {
            $pdo->exec("ALTER TABLE st_products ADD COLUMN `unit_id` INT DEFAULT NULL");
        }

        // One-time (idempotent) backfill: link existing products to their matching row in
        // units by name, scoped to the same branch — covers products added/edited before
        // this FK existed. COLLATE needed since products.unit and units.unit_name don't
        // share a collation (see api/units_backend.php for the same mismatch).
        $pdo->exec("
            UPDATE st_products p
            JOIN st_units u ON u.store_id = p.store_id AND u.unit_name = p.unit COLLATE utf8mb4_unicode_ci
            SET p.unit_id = u.unit_id
            WHERE p.unit_id IS NULL AND p.unit IS NOT NULL AND p.unit != ''
        ");

        // ລົບຟິວພາສາອັງກິດ (_en) ທີ່ບໍ່ໄດ້ໃຊ້ງານ
        $pdo->exec("ALTER TABLE st_categories DROP COLUMN IF EXISTS category_name_en");
        $pdo->exec("ALTER TABLE st_products DROP COLUMN IF EXISTS product_name_en");
        $pdo->exec("ALTER TABLE st_tbcompanyinfo DROP COLUMN IF EXISTS com_name_en");

        // ສ້າງຕາຕະລາງ product_units ສຳລັບສິນຄ້າທີ່ມີຫຼາຍລາຄາ/ຫຼາຍຫົວໜ່ວຍ
        $pdo->exec("CREATE TABLE IF NOT EXISTS st_product_units (
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
        $pdo->exec("CREATE TABLE IF NOT EXISTS st_imports (
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
        $pdo->exec("CREATE TABLE IF NOT EXISTS st_import_details (
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
        $pdo->exec("CREATE TABLE IF NOT EXISTS st_product_batches (
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
        $pdo->exec("CREATE TABLE IF NOT EXISTS `st_suppliers` (
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

        $hasSupplierId = $pdo->query("SHOW COLUMNS FROM st_products LIKE 'supplier_id'")->fetch();
        if (!$hasSupplierId) {
            $pdo->exec("ALTER TABLE st_products ADD COLUMN `supplier_id` INT DEFAULT NULL");
        }
    } catch (Throwable $ex) {}

    // Auto Migration for promotions table
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `st_promotions` (
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

        $hasMinQty = $pdo->query("SHOW COLUMNS FROM `st_promotions` LIKE 'min_qty'")->fetch();
        if (!$hasMinQty) {
            $pdo->exec("ALTER TABLE `st_promotions` ADD COLUMN `min_qty` INT DEFAULT 0 AFTER `end_date`");
        }
        $hasTargetType = $pdo->query("SHOW COLUMNS FROM `st_promotions` LIKE 'target_type'")->fetch();
        if (!$hasTargetType) {
            $pdo->exec("ALTER TABLE `st_promotions` ADD COLUMN `target_type` VARCHAR(50) DEFAULT 'all' AFTER `min_amount`");
            $pdo->exec("ALTER TABLE `st_promotions` ADD COLUMN `target_name` VARCHAR(255) DEFAULT 'ທຸກສິນຄ້າ' AFTER `target_type`");
        }

        // Auto-deactivate expired promotions
        $pdo->exec("UPDATE st_promotions SET status = 0 WHERE status = 1 AND end_date < CURDATE()");
    } catch (Throwable $ex) {}

