<?php
/**
 * ສ່ວນ: ອັບເດດໂຄງສ້າງ DB (1): ພື້ນຖານ
 * ສ້າງ/ເພີ່ມຄໍລຳພື້ນຖານ: tbstore, store_id ໃນຕາຕະລາງຫຼັກ, ຄໍລຳສິດໃນ tbuser, VAT, ສະກຸນເງິນທີ່ຮັບ,
 * ເຄື່ອງພິມ ແລະ ຄິວພິມ, index ເພື່ອຄວາມໄວ, ແລະ ລົບຕາຕະລາງເກົ່າທີ່ບໍ່ໃຊ້ແລ້ວ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ config/db.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

        try {


        $pdo->exec("CREATE TABLE IF NOT EXISTS `st_tbstore` (
            `store_id` INT AUTO_INCREMENT PRIMARY KEY,
            `store_code` VARCHAR(50) NOT NULL UNIQUE,
            `store_name` VARCHAR(150) NOT NULL,
            `is_main` TINYINT(1) DEFAULT 0,
            `address` TEXT NULL,
            `tel` VARCHAR(50) NULL,
            `status` VARCHAR(20) DEFAULT 'active',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $storeCount = (int)$pdo->query("SELECT COUNT(*) FROM st_tbstore")->fetchColumn();
        if ($storeCount === 0) {
            $pdo->exec("INSERT INTO st_tbstore (store_id, store_code, store_name, is_main, address, tel, status) 
                        VALUES (1, '1', 'ສາຂາໃຫຍ່ ວຽງຈັນ (HQ)', 1, 'ນະຄອນຫຼວງວຽງຈັນ', '020-55555555', 'active')");
        }

        // Auto update ST-001 to numeric '1'
        $pdo->exec("UPDATE st_tbstore SET store_code = '1' WHERE store_id = 1 AND store_code = 'ST-001'");

        // Ensure is_main column exists
        $hasMain = $pdo->query("SHOW COLUMNS FROM `st_tbstore` LIKE 'is_main'")->fetch();
        if (!$hasMain) {
            $pdo->exec("ALTER TABLE `st_tbstore` ADD COLUMN `is_main` TINYINT(1) DEFAULT 0 AFTER `store_name`");
        }
        $pdo->exec("UPDATE `st_tbstore` SET is_main = 1 WHERE store_id = 1");
    } catch (Throwable $ex) {}

    // Ensure store_id column exists in all core data tables for multi-branch isolation
    $coreTablesForStore = ['st_sales', 'st_tbsale_save', 'st_products', 'st_accounting_records', 'st_imports', 'st_customers', 'st_tbuser', 'st_categories', 'st_units'];
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
            $hasCol = $pdo->query("SHOW COLUMNS FROM `st_tbuser` LIKE '{$pCol}'")->fetch();
            if (!$hasCol) {
                $pdo->exec("ALTER TABLE `st_tbuser` ADD COLUMN `{$pCol}` TINYINT(1) DEFAULT 0");
            }
        } catch (Throwable $ex) {}
    }

    // Ensure Tax/VAT columns exist in tbcompanyinfo and sales tables
    try {
        $hasVat = $pdo->query("SHOW COLUMNS FROM `st_tbcompanyinfo` LIKE 'vat_percent'")->fetch();
        if (!$hasVat) {
            $pdo->exec("ALTER TABLE `st_tbcompanyinfo` ADD COLUMN `vat_percent` DECIMAL(5,2) DEFAULT 7.00");
        }
        $hasTaxType = $pdo->query("SHOW COLUMNS FROM `st_tbcompanyinfo` LIKE 'tax_type'")->fetch();
        if (!$hasTaxType) {
            $pdo->exec("ALTER TABLE `st_tbcompanyinfo` ADD COLUMN `tax_type` VARCHAR(20) DEFAULT 'inclusive'");
        }
        $hasSalesTaxType = $pdo->query("SHOW COLUMNS FROM `st_sales` LIKE 'tax_type'")->fetch();
        if (!$hasSalesTaxType) {
            $pdo->exec("ALTER TABLE `st_sales` ADD COLUMN `tax_type` VARCHAR(20) DEFAULT 'inclusive'");
        }
        $hasSalesVatRate = $pdo->query("SHOW COLUMNS FROM `st_sales` LIKE 'vat_rate'")->fetch();
        if (!$hasSalesVatRate) {
            $pdo->exec("ALTER TABLE `st_sales` ADD COLUMN `vat_rate` DECIMAL(5,2) DEFAULT 7.00");
        }
        $hasQrImg = $pdo->query("SHOW COLUMNS FROM `st_tbcompanyinfo` LIKE 'qr_img'")->fetch();
        if (!$hasQrImg) {
            $pdo->exec("ALTER TABLE `st_tbcompanyinfo` ADD COLUMN `qr_img` VARCHAR(255) NULL AFTER `img_url`");
        }
        $hasTaxId = $pdo->query("SHOW COLUMNS FROM `st_tbcompanyinfo` LIKE 'tax_id'")->fetch();
        if (!$hasTaxId) {
            $pdo->exec("ALTER TABLE `st_tbcompanyinfo` ADD COLUMN `tax_id` VARCHAR(100) NULL AFTER `com_email`");
        }
    } catch (Throwable $ex) {}

    // CNY (ຢວນຈີນ) exchange-rate column — kept in its OWN try/catch so an unrelated
    // migration failure above can never stop it landing (the exchange_rate save page
    // does INSERT/UPDATE ... ex_kip_cn and hard-fails without it).
    try {
        $hasExKipCn = $pdo->query("SHOW COLUMNS FROM `st_tbexchange` LIKE 'ex_kip_cn'")->fetch();
        if (!$hasExKipCn) {
            try {
                $pdo->exec("ALTER TABLE `st_tbexchange` ADD COLUMN `ex_kip_cn` VARCHAR(255) NULL AFTER `ex_kip_us`");
            } catch (Throwable $e2) {
                // fall back to a plain ADD if AFTER-column resolution failed
                $pdo->exec("ALTER TABLE `st_tbexchange` ADD COLUMN `ex_kip_cn` VARCHAR(255) NULL");
            }
        }
    } catch (Throwable $ex) {}

    // Multi-currency receipts: record which currency the customer actually tendered on
    // each sale (chosen per bill at checkout) plus the KIP-per-unit rate used at that
    // moment, so the daily report can break "money received today" down by currency.
    // Own try/catch — never let this stall the checkout insert if it can't run.
    try {
        foreach (['st_tbsale_save', 'st_sales'] as $__ccTbl) {
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
        $catCols = $pdo->query("SHOW COLUMNS FROM `st_categories`")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('printer_id', $catCols, true)) {
            $pdo->exec("ALTER TABLE `st_categories` ADD COLUMN `printer_id` INT NULL");
        }
    } catch (Throwable $ex) {}

    // Printers. type 'ip' printers get kitchen tickets straight over the LAN (api/lan_printer.php);
    // paper_width (58 / 80 mm) sets the raster width those tickets are drawn at.
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `st_tbl_printer` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) DEFAULT NULL,
            `ip_address` VARCHAR(100) DEFAULT NULL,
            `type` ENUM('ip','browser') DEFAULT 'ip',
            `status` TINYINT(1) DEFAULT 1,
            `branch_id` INT DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $printerCols = $pdo->query("SHOW COLUMNS FROM `st_tbl_printer`")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('paper_width', $printerCols, true)) {
            $pdo->exec("ALTER TABLE `st_tbl_printer` ADD COLUMN `paper_width` TINYINT UNSIGNED NOT NULL DEFAULT 80");
        }
        if (!in_array('is_receipt_printer', $printerCols, true)) {
            $pdo->exec("ALTER TABLE `st_tbl_printer` ADD COLUMN `is_receipt_printer` TINYINT(1) NOT NULL DEFAULT 0");
        }
        // type 'usb': a Windows USB printer (name kept in ip_address) that gets the same RAW
        // ESC/POS bytes as a LAN printer, through api/usb_printer.php — no browser print dialog.
        $typeCol = $pdo->query("SHOW COLUMNS FROM `st_tbl_printer` LIKE 'type'")->fetch(PDO::FETCH_ASSOC);
        if ($typeCol && stripos($typeCol['Type'], "'usb'") === false) {
            $pdo->exec("ALTER TABLE `st_tbl_printer` MODIFY `type` ENUM('ip','browser','usb') DEFAULT 'ip'");
        }
    } catch (Throwable $ex) {}

    // Print agent queue — only used when Settings > Printers has print_mode = 'agent' (the
    // server is hosted off-site and can't reach a shop printer's 192.168.x.x directly). A LAN
    // print job is parked here instead of sent straight to the socket; tools/print-agent.php,
    // running on a shop PC that IS on that LAN, polls api/print_agent.php and prints it.
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `st_print_jobs` (
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
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_products_store ON st_products (store_id, category_id)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_products_barcode ON st_products (barcode)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_sales_store_date ON st_sales (store_id, created_at)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_sales_invoice ON st_sales (invoice_number)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_customers_store ON st_customers (store_id, customer_id)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_price_adj_branch ON st_price_adjustments (branch_id, adjust_id)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_promotions_branch ON st_promotions (branch_id, status)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_transfers_stores ON st_stock_transfers (from_store_id, to_store_id, status)");
    } catch (Throwable $ex) {}

    // v16: branch_no — ລຳດັບໝວດໝູ່ / ຫົວໜ່ວຍ ພາຍໃນແຕ່ລະສາຂາ, ສະແດງເປັນ "<ລະຫັດສາຂາ>-<branch_no>" (1-1, 2-1 …).
    // v17: ຂະຫຍາຍໄປ ລູກຄ້າ, ຜູ້ສະໜອງ, ຜູ້ໃຊ້, ທະນາຄານ, ໂປຣໂມຊັ່ນ ແລະ ສິນຄ້າ (ຄໍລຳສາຂາ: branchNoTables()).
    // ໃສ່ເລກໃຫ້ແຖວເກົ່າ ໂດຍ ensureBranchNos() (config/helpers/branches.php) ຕອນເປີດໜ້າລາຍການ.
    foreach (['st_categories' => 'store_id', 'st_units' => 'store_id', 'st_customers' => 'store_id',
              'st_suppliers' => 'store_id', 'st_tbuser' => 'store_id', 'st_bank_accounts' => 'store_id',
              'st_promotions' => 'branch_id', 'st_products' => 'store_id'] as $__bnTbl => $__bnStoreCol) {
        try {
            if (!$pdo->query("SHOW COLUMNS FROM `{$__bnTbl}` LIKE 'branch_no'")->fetch()) {
                $pdo->exec("ALTER TABLE `{$__bnTbl}` ADD COLUMN `branch_no` INT NULL DEFAULT NULL AFTER `{$__bnStoreCol}`");
            }
            // MySQL 8 ບໍ່ຮູ້ຈັກ CREATE INDEX IF NOT EXISTS — ກວດກ່ອນ
            if (!$pdo->query("SHOW INDEX FROM `{$__bnTbl}` WHERE Key_name = 'idx_branch_no'")->fetch()) {
                $pdo->exec("CREATE INDEX idx_branch_no ON `{$__bnTbl}` (`{$__bnStoreCol}`, branch_no)");
            }
        } catch (Throwable $ex) {}
    }

    // Clean up unused/deprecated database tables
    $unusedTables = [
        'tbbranch', 'tbunit', 'tbsale', 'tbsale_detail', 'category', 'tbsupplier', 'tbcurrency',
        'customer', 'tb_expenses', 'tb_queue_daily', 'tbdeposit_beer', 'tbfinancial_report',
        'tbreceive', 'tbreceive_detail', 'tbsale_save_data', 'stock_adjustments', 'stock_adjustment_items',
        'price_adjustment_items', 'promotion_items', 'tb_delete_bill_log',
        // ໃບສະເໜີລາຄາ — module removed in v15
        'quotations', 'quotation_details',
        // ລູກຄ້າຕິດໜີ້ — module removed in v18 (backup: backups/debts_before_removal_*.sql)
        'customer_debts', 'customer_debt_payments'
    ];
    foreach ($unusedTables as $uTbl) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS `st_{$uTbl}`");
        } catch (Throwable $ex) {}
    }
    try {
        if ($pdo->query("SHOW COLUMNS FROM `st_tbuser` LIKE 'quotations'")->fetch()) {
            $pdo->exec("ALTER TABLE `st_tbuser` DROP COLUMN `quotations`");
        }
    } catch (Throwable $ex) {}
    // v18: ສິດ "ລູກຄ້າຕິດໜີ້" ລຶບພ້ອມໂມດູນ
    try {
        if ($pdo->query("SHOW COLUMNS FROM `st_tbuser` LIKE 'customer_debt'")->fetch()) {
            $pdo->exec("ALTER TABLE `st_tbuser` DROP COLUMN `customer_debt`");
        }
    } catch (Throwable $ex) {}

