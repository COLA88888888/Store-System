DROP TABLE IF EXISTS `st_bank_accounts`;
CREATE TABLE `st_bank_accounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bank_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `account_number` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `account_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `bank_code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'BCEL',
  `bank_logo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `qr_code_img` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `store_id` int NOT NULL DEFAULT '1',
  `branch_no` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_branch_no` (`store_id`,`branch_no`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `st_bank_accounts` (`id`, `bank_name`, `account_number`, `account_name`, `bank_code`, `bank_logo`, `qr_code_img`, `is_active`, `created_at`, `store_id`, `branch_no`) VALUES
('1', 'BCEL One', '123456789', 'Khola Dev', 'BCEL ONE', 'bank_1789632020_771.png', 'qr_bank_1789632020_540.jpeg', '1', '2026-09-17 15:00:20', '1', '1'),
('2', 'ພົງສະຫວັນ', 'aa', '344', 'ພົງສະຫວັນ', 'bank_1792480748_822.jfif', 'qr_bank_1792480748_885.jpeg', '1', '2026-10-20 14:19:08', '1', '2'),
('3', 'LDB', 'f', '554', 'LDB', 'bank_1792480933_324.png', 'qr_bank_1792480933_169.jpeg', '1', '2026-10-20 14:22:13', '1', '3'),
('4', 'JDB', 'ghfdgh', '23456', 'JDB', 'bank_1793426915_143.png', 'qr_bank_1792654945_186.jpeg', '1', '2026-10-22 14:42:25', '1', '4');
-- rows `st_bank_accounts` 4

DROP TABLE IF EXISTS `st_categories`;
CREATE TABLE `st_categories` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `store_id` int NOT NULL DEFAULT '1',
  `branch_no` int DEFAULT NULL,
  `printer_id` int DEFAULT NULL,
  PRIMARY KEY (`category_id`),
  KEY `idx_branch_no` (`store_id`,`branch_no`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `st_categories` (`category_id`, `category_name`, `description`, `created_at`, `store_id`, `branch_no`, `printer_id`) VALUES
('1', 'ເຄື່ອງດື່ມ', 'ນ້ຳດື່ມ, ນ້ຳຫວານ ແລະ ເຄື່ອງດື່ມທົ່ວໄປ', '2026-10-14 16:33:02', '1', '1', NULL),
('2', 'ຢາສູບ', 'ຢາສູບ ແລະ ອຸປະກອນ', '2026-10-14 16:33:02', '1', '2', NULL),
('3', 'ເຄື່ອງສຳອາງ', 'ເຄື່ອງສຳອາງ ແລະ ບຳລຸງຜິວ', '2026-10-14 16:33:02', '1', '3', NULL),
('4', 'ເບຍ', 'ເບຍລາວ, ເບຍນອກ ແລະ ເຄື່ອງດື່ມແອນກໍຮໍ', '2026-10-14 16:33:02', '1', '4', NULL),
('5', 'ສິນຄ້າ ບໍ່ມີບາໂຄ້ດ', 'ສິນຄ້າທົ່ວໄປ ບໍ່ມີບາໂຄ້ດ', '2026-10-14 16:33:02', '1', '5', NULL),
('6', 'ເຄື່ອງໃຊ້ຫ້ອງການ', 'ເຈ້ຍ A4 ແລະ ອຸປະກອນເຄື່ອງຂຽນ', '2026-10-14 16:33:02', '1', '6', NULL),
('7', 'ເຄື່ອງປຸງ & ໃຊ້ຄົວເຮືອນ', 'ແປງນົວ, ນ້ຳປາ, ນ້ຳມັນພືດ', '2026-10-14 16:33:02', '1', '7', NULL),
('8', 'ນົມ', 'ນົມກ່ອງ, ນົມສົ້ມ, ນົມຖົ່ວເຫຼືອງ', '2026-10-14 16:33:02', '1', '8', NULL),
('9', 'ເຂົ້າໜົມ', 'ເຂົ້າໜົມ, ມັນຝຣັ່ງທອດ, ບິສກິດ', '2026-10-14 16:33:02', '1', '9', NULL),
('10', 'ຫມີ່', 'ຫມີ່ໄວໄວ, ມ່າມ່າ, ຫມີ່ເກົາຫຼີ', '2026-10-14 16:33:02', '1', '10', NULL),
('11', 'ອາຫານກະປ໋ອງ', 'ປາກະປ໋ອງ ແລະ ອາຫານສຳເລັດຮູບ', '2026-10-14 16:33:02', '1', '11', NULL),
('12', 'ຂອງໃຊ້ສ່ວນຕົວ', 'ຢາສີຟັນ, ສະບູ, ຢາສະຜົມ', '2026-10-14 16:33:02', '1', '12', NULL),
('13', 'ຂອງຫວານ', '', '2026-10-19 12:50:15', '1', '13', NULL);
-- rows `st_categories` 13

DROP TABLE IF EXISTS `st_customers`;
CREATE TABLE `st_customers` (
  `customer_id` int NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `customer_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `member_card` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_general_ci,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `store_id` int NOT NULL DEFAULT '1',
  `branch_no` int DEFAULT NULL,
  `points` int NOT NULL DEFAULT '0',
  `allergies` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `customer_code` (`customer_code`),
  KEY `idx_customers_store` (`store_id`,`customer_id`),
  KEY `idx_branch_no` (`store_id`,`branch_no`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- rows `st_customers` 0

DROP TABLE IF EXISTS `st_held_orders`;
CREATE TABLE `st_held_orders` (
  `id` varchar(60) NOT NULL,
  `store_id` int NOT NULL DEFAULT '1',
  `user_id` int DEFAULT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `customer_id` int DEFAULT NULL,
  `customer_name` varchar(150) DEFAULT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `hold_date` date NOT NULL,
  `hold_time` varchar(20) NOT NULL,
  `items_count` int NOT NULL DEFAULT '0',
  `total_qty` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount` varchar(50) DEFAULT '0',
  `discount_mode` varchar(20) DEFAULT 'amount',
  `cart_json` longtext NOT NULL,
  `customer_json` text,
  `status` enum('held','paid','deleted') NOT NULL DEFAULT 'held',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_held_store_status` (`store_id`,`status`),
  KEY `idx_held_date` (`hold_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- rows `st_held_orders` 0

DROP TABLE IF EXISTS `st_import_details`;
CREATE TABLE `st_import_details` (
  `import_detail_id` int NOT NULL AUTO_INCREMENT,
  `import_id` int NOT NULL,
  `product_id` int NOT NULL,
  `unit_name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `multiplier` int DEFAULT '1',
  `quantity` int NOT NULL DEFAULT '1',
  `total_base_qty` int NOT NULL DEFAULT '1',
  `cost_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `sell_price` decimal(15,2) DEFAULT NULL,
  `total_cost` decimal(15,2) NOT NULL DEFAULT '0.00',
  `expiry_date` date DEFAULT NULL,
  `batch_no` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`import_detail_id`),
  KEY `import_id` (`import_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- rows `st_import_details` 0

DROP TABLE IF EXISTS `st_imports`;
CREATE TABLE `st_imports` (
  `import_id` int NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `supplier_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `total_cost` decimal(15,2) DEFAULT '0.00',
  `created_by` int DEFAULT NULL,
  `import_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `notes` text COLLATE utf8mb4_general_ci,
  `store_id` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`import_id`),
  KEY `invoice_number` (`invoice_number`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- rows `st_imports` 0

DROP TABLE IF EXISTS `st_loyalty_points_log`;
CREATE TABLE `st_loyalty_points_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `store_id` int DEFAULT NULL,
  `change_points` int NOT NULL,
  `log_type` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `sale_bill` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `note` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `balance_after` int NOT NULL DEFAULT '0',
  `user_id` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lpl_customer` (`customer_id`),
  KEY `idx_lpl_bill` (`sale_bill`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- rows `st_loyalty_points_log` 0

DROP TABLE IF EXISTS `st_loyalty_rewards`;
CREATE TABLE `st_loyalty_rewards` (
  `id` int NOT NULL AUTO_INCREMENT,
  `store_id` int NOT NULL DEFAULT '0',
  `reward_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `points_cost` int NOT NULL,
  `reward_type` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'discount',
  `discount_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `min_spend` decimal(15,2) NOT NULL DEFAULT '0.00',
  `product_id` int DEFAULT NULL,
  `product_qty` int NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lr_store` (`store_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- rows `st_loyalty_rewards` 0

DROP TABLE IF EXISTS `st_price_adjustments`;
CREATE TABLE `st_price_adjustments` (
  `adjust_id` int NOT NULL AUTO_INCREMENT,
  `adjust_date` date DEFAULT NULL,
  `adjust_time` time DEFAULT NULL,
  `target_mode` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'product',
  `category_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `target_price_type` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'price',
  `calc_type` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'set',
  `adjust_value` decimal(12,2) DEFAULT '0.00',
  `old_bprice` decimal(12,2) DEFAULT '0.00',
  `old_price` decimal(12,2) DEFAULT '0.00',
  `new_bprice` decimal(12,2) DEFAULT '0.00',
  `new_price` decimal(12,2) DEFAULT '0.00',
  `diff_amount` decimal(12,2) DEFAULT '0.00',
  `adjust_mode` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `adjust_type` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `price_type` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `username` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `remark` text COLLATE utf8mb4_general_ci,
  `branch_id` int DEFAULT '1',
  PRIMARY KEY (`adjust_id`),
  KEY `idx_price_adj_branch` (`branch_id`,`adjust_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- rows `st_price_adjustments` 0

DROP TABLE IF EXISTS `st_print_jobs`;
CREATE TABLE `st_print_jobs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `printer_id` int NOT NULL,
  `store_id` int DEFAULT '1',
  `payload` longtext COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('pending','done','failed') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `error` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `printed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_print_jobs_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- rows `st_print_jobs` 0

DROP TABLE IF EXISTS `st_product_batches`;
CREATE TABLE `st_product_batches` (
  `batch_id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `store_id` int DEFAULT NULL,
  `import_detail_id` int DEFAULT NULL,
  `batch_no` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `initial_qty` int NOT NULL DEFAULT '0',
  `quantity` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`batch_id`),
  KEY `product_id` (`product_id`),
  KEY `expiry_date` (`expiry_date`),
  KEY `idx_pb_prod_store` (`product_id`,`store_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `st_product_batches` (`batch_id`, `product_id`, `store_id`, `import_detail_id`, `batch_no`, `expiry_date`, `initial_qty`, `quantity`, `created_at`) VALUES
('1', '130004', '1', '1', NULL, NULL, '100', '0', '2026-09-25 10:12:45'),
('2', '80005', '1', '2', NULL, NULL, '100', '0', '2026-09-25 10:12:45'),
('3', '60003', '1', '3', NULL, NULL, '100', '0', '2026-09-25 10:12:45'),
('4', '60002', '1', '4', NULL, NULL, '100', '0', '2026-09-25 10:12:45'),
('5', '170006', '1', '5', NULL, NULL, '100', '0', '2026-09-25 10:12:45'),
('6', '130004', '1', '6', NULL, NULL, '100', '55', '2026-09-29 10:38:48'),
('7', '80005', '1', '7', NULL, NULL, '100', '76', '2026-09-29 10:38:48'),
('8', '60003', '1', '8', NULL, NULL, '100', '100', '2026-09-29 10:38:48'),
('9', '60002', '1', '9', NULL, NULL, '100', '31', '2026-09-29 10:38:48'),
('10', '170006', '1', '10', NULL, NULL, '100', '98', '2026-09-29 10:38:48'),
('11', '60002', '1', '11', NULL, NULL, '10', '10', '2026-09-29 10:42:07'),
('15', '30001', '1', '1', NULL, NULL, '20', '7', '2026-09-30 11:28:40'),
('16', '10002', '1', '2', NULL, NULL, '30', '9', '2026-09-30 11:28:40'),
('17', '30003', '1', '3', NULL, NULL, '12', '11', '2026-10-06 10:59:48'),
('20', '30003', NULL, '4', NULL, NULL, '100', '100', '2026-10-08 12:56:37'),
('21', '100004', NULL, '5', NULL, NULL, '100', '100', '2026-10-08 12:56:37'),
('22', '30001', NULL, '6', NULL, NULL, '100', '100', '2026-10-08 12:56:37'),
('23', '10002', NULL, '7', NULL, NULL, '100', '100', '2026-10-08 12:56:37'),
('27', '30003', '1', '11', NULL, NULL, '1', '1', '2026-10-09 10:25:50'),
('28', '30001', '1', '12', NULL, NULL, '1', '1', '2026-10-09 10:25:50'),
('29', '10002', '1', '13', NULL, NULL, '1', '1', '2026-10-09 10:25:50'),
('31', '30003', '1', '15', NULL, NULL, '5', '5', '2026-10-09 12:44:59'),
('32', '100004', '1', '16', NULL, NULL, '5', '5', '2026-10-09 12:44:59'),
('33', '30001', '1', '17', NULL, NULL, '5', '5', '2026-10-09 12:44:59');
-- rows `st_product_batches` 24

DROP TABLE IF EXISTS `st_product_units`;
CREATE TABLE `st_product_units` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `unit_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `multiplier` int NOT NULL DEFAULT '1',
  `bprice` decimal(10,2) NOT NULL DEFAULT '0.00',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `barcode` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `idx_product_units_barcode` (`barcode`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `st_product_units` (`id`, `product_id`, `unit_name`, `multiplier`, `bprice`, `price`, `barcode`, `created_at`) VALUES
('4', '130004', 'ກັບ', '6', '130000.00', '146000.00', '2021218212748', '2026-09-25 10:02:46'),
('6', '60003', 'ເເພັກ', '6', '130000.00', '149000.00', '2083892803537', '2026-09-25 10:11:45'),
('7', '60003', 'ເເກັດ', '24', '250000.00', '350000.00', '2061539829010', '2026-09-25 10:11:45'),
('8', '60002', 'ເເກັດ', '24', '120000.00', '240000.00', '2070573558831', '2026-09-25 10:12:13'),
('10', '170006', 'ແພັກ', '6', '120000.00', '220000.00', '2068928268672', '2026-09-30 09:16:29'),
('11', '170006', 'ເເກັດ', '24', '250000.00', '420000.00', '2006453227160', '2026-09-30 09:16:29'),
('18', '11002', 'ແພັກ', '6', '72000.00', '96000.00', '2022595983313', '2026-10-28 10:03:53'),
('20', '1120001', 'ແພັກ', '6', '42000.00', '60000.00', '2040739106862', '2026-10-30 15:12:51');
-- rows `st_product_units` 8

DROP TABLE IF EXISTS `st_products`;
CREATE TABLE `st_products` (
  `code1` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL DEFAULT '0',
  `barcode` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `category_id` int NOT NULL,
  `bprice` decimal(10,2) NOT NULL DEFAULT '0.00',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `qty` int NOT NULL DEFAULT '0',
  `unit` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `zone` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `shelf` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `img_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cook` tinyint(1) NOT NULL DEFAULT '0',
  `cut_qty` tinyint(1) NOT NULL DEFAULT '1',
  `branch_id` int DEFAULT '1',
  `store_id` int NOT NULL DEFAULT '1',
  `branch_no` int DEFAULT NULL,
  `unit_id` int DEFAULT NULL,
  `supplier_id` int DEFAULT NULL,
  `generic_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dosage_form` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `strength` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `drug_type` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'general',
  `usage_instructions` text COLLATE utf8mb4_general_ci,
  `drug_warning` text COLLATE utf8mb4_general_ci,
  `min_qty` int NOT NULL DEFAULT '5',
  `expiry_date` date DEFAULT NULL,
  PRIMARY KEY (`code1`),
  UNIQUE KEY `uk_product_store` (`product_id`,`store_id`),
  KEY `idx_products_category_id` (`category_id`),
  KEY `idx_products_barcode` (`barcode`),
  KEY `idx_products_store` (`store_id`,`category_id`),
  KEY `idx_branch_no` (`store_id`,`branch_no`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='select *from products';

INSERT INTO `st_products` (`code1`, `product_id`, `barcode`, `product_name`, `category_id`, `bprice`, `price`, `qty`, `unit`, `zone`, `shelf`, `img_url`, `cook`, `cut_qty`, `branch_id`, `store_id`, `branch_no`, `unit_id`, `supplier_id`, `generic_name`, `dosage_form`, `strength`, `drug_type`, `usage_instructions`, `drug_warning`, `min_qty`, `expiry_date`) VALUES
('1', '10001', '2048466388643', 'ເບຍລາວ ຂວດໃຫຍ່', '4', '10000.00', '15000.00', '150', 'ແກ້ວ', NULL, NULL, 'prod_beerlao_bottle_640ml.jpg', '0', '1', '1', '1', '1', '22', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('2', '40012', '2004456587038', 'ເບຍລາວ ປ໋ອງສັ້ນ', '4', '9000.00', '12000.00', '120', 'ປ໋ອງ', NULL, NULL, 'prod_beerlao_can_330ml.jpg', '0', '1', '1', '1', '2', '11', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('3', '40011', '2077782445808', 'ເບຍໄຮນິເກັນ', '4', '14000.00', '18000.00', '80', 'ແກ້ວ', NULL, NULL, 'prod_1792489130_7561.png', '0', '1', '1', '1', '3', '22', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('4', '80002', '2073203999158', 'ນົມເລັກຕາຊອຍ', '8', '8000.00', '12000.00', '90', 'ກ່ອງ', NULL, NULL, 'prod_lactasoy_250ml.jpg', '0', '1', '1', '1', '4', '12', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('5', '10002', '8851717041234', 'ນ້ຳດື່ມຫົວເສືອ', '1', '3000.00', '5000.00', '195', 'ຕຸກ', NULL, NULL, 'prod_tigerhead_water_600ml.jpg', '0', '1', '1', '1', '5', NULL, NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('6', '10003', '8851959132014', 'ໂຄກກະປ໋ອງ', '1', '6000.00', '9000.00', '110', 'ປ໋ອງ', NULL, NULL, 'prod_cocacola_can_325ml.jpg', '0', '1', '1', '1', '6', '11', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('7', '10004', '8851959142021', 'ເປັບຊີກະປ໋ອງ', '1', '6000.00', '9000.00', '105', 'ປ໋ອງ', NULL, NULL, 'prod_pepsi_can_325ml.jpg', '0', '1', '1', '1', '7', '11', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('8', '10005', '2078263953966', 'ສະຕິງຂວດເຫຼືອງ', '1', '7000.00', '10000.00', '82', 'ຕຸກ', NULL, NULL, 'prod_carabao_150ml.jpg', '0', '1', '1', '1', '8', NULL, NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('9', '10009', '2021360299994', 'M-150 ເຄື່ອງດື່ມຊູກຳລັງ', '1', '6000.00', '9000.00', '79', 'ແກ້ວ', NULL, NULL, 'prod_kratingdaeng_150ml.jpg', '0', '1', '1', '1', '9', '22', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('10', '10010', '8850188200115', 'ຄາຣາບາວແດງ', '1', '6000.00', '9000.00', '94', 'ແກ້ວ', NULL, NULL, 'prod_carabao_150ml.jpg', '0', '1', '1', '1', '10', '22', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('11', '10011', '8850188300221', 'ກາເຟ ເບີດີ້ ໂຣບັສຕ້າ', '1', '8000.00', '12000.00', '14', 'ປ໋ອງ', NULL, NULL, 'prod_birdy_robusta_180ml.jpg', '0', '1', '1', '1', '11', '11', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('12', '90004', '2075318035929', 'ເລ ມັນຝຣັ່ງທອດ', '9', '8000.00', '12000.00', '89', 'ຖົງ', NULL, NULL, 'prod_lays_classic_50g.jpg', '0', '1', '1', '1', '12', '15', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('13', '90005', '8850029012345', 'ໂອຣິໂອ້ ລົດດັ້ງເດີມ', '9', '10000.00', '15000.00', '70', 'ຊອງ', NULL, NULL, 'prod_oreo_original_133g.jpg', '0', '1', '1', '1', '13', '7', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('14', '90006', '8850029054321', 'ຄິດແຄັດ ຊັອກໂກແລັດ', '9', '8000.00', '12000.00', '80', 'ຊອງ', NULL, NULL, 'prod_kitkat_35g.jpg', '0', '1', '1', '1', '14', '7', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('15', '100013', '2047888140464', 'ຫມີ່ໄວໄວ ຕົ້ມຍຳ', '10', '3000.00', '5000.00', '180', 'ຫໍ່', NULL, NULL, 'prod_mama_pork_60g.jpg', '0', '1', '1', '1', '15', NULL, NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('16', '100016', '2094815295841', 'ມ່າມ່າ ຕົ້ມຍຳກຸ້ງ', '10', '4000.00', '6000.00', '153', 'ຫໍ່', NULL, NULL, 'prod_mama_tomyum_55g.jpg', '0', '1', '1', '1', '16', NULL, NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('17', '70006', '2000458862838', 'ແປງນົວ ອາຍິໂນະໂມະໂຕ', '7', '12000.00', '16000.00', '58', 'ຖົງ', NULL, NULL, 'prod_ajinomoto_msg_250g.jpg', '0', '1', '1', '1', '17', '15', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('18', '70007', '8850157001015', 'ນ້ຳປາ ທິບພະຣົດ', '7', '15000.00', '20000.00', '53', 'ຕຸກ', NULL, NULL, 'prod_tiparos_fishsauce_700ml.jpg', '0', '1', '1', '1', '18', NULL, NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('19', '70008', '8850157002022', 'ນ້ຳມັນພືດ ມໍຣະກົດ', '7', '22000.00', '28000.00', '43', 'ຕຸກ', NULL, NULL, 'prod_morakot_palmoil_1l.jpg', '0', '1', '1', '1', '19', NULL, NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('20', '11001', '8850157003033', 'ປາກະປ໋ອງ ໂຣຊ່າ', '11', '12000.00', '16000.00', '80', 'ປ໋ອງ', NULL, NULL, 'prod_roza_mackerel_155g.jpg', '0', '1', '1', '1', '20', '11', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('21', '11002', '8850157004044', 'ປາກະປ໋ອງ ສາມແມ່ຄົວ', '11', '12000.00', '16000.00', '22', 'ປ໋ອງ', NULL, NULL, 'prod_threelady_sardines_155g.jpg', '0', '1', '1', '1', '21', '11', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('22', '12001', '8850157005055', 'ຢາສີຟັນ ຄອນເກດ', '12', '15000.00', '22000.00', '60', 'ກ່ອງ', NULL, NULL, 'prod_colgate_regular_150g.jpg', '0', '1', '1', '1', '22', '12', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('23', '130023', '2026880235040', 'ຊານົມ', '13', '20000.00', '45000.00', '2', 'ອັນ', NULL, NULL, NULL, '0', '1', '1', '1', '23', '16', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('24', '110001', '8851959132052', 'ແຟນຕ້າ ນ້ຳສົ້ມ ກະປ໋ອງ', '1', '7000.00', '10000.00', '55', 'ປ໋ອງ', NULL, NULL, 'prod_fanta_orange_325ml.jpg', '0', '1', '1', '1', '24', '11', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '24', '2027-09-30'),
('25', '110002', '8851959132038', 'ສະໄປຣທ໌ ກະປ໋ອງ', '1', '7000.00', '10000.00', '55', 'ປ໋ອງ', NULL, NULL, 'prod_sprite_can_325ml.jpg', '0', '1', '1', '1', '25', '11', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '24', '2027-09-30'),
('26', '110003', '8850228000205', 'ສະປອນເຊີ ເຄື່ອງດື່ມເກືອແຮ່', '1', '8000.00', '12000.00', '44', 'ຂວດ', NULL, NULL, 'prod_sponsor_250ml.jpg', '0', '1', '1', '1', '26', '5', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '24', '2027-10-31'),
('27', '110004', '8854698005012', 'ຊາຂຽວ ໂອອິຊິ ນ້ຳເຜິ້ງໝາກນາວ', '1', '10000.00', '14000.00', '55', 'ຂວດ', NULL, NULL, 'prod_oishi_greentea_380ml.jpg', '0', '1', '1', '1', '27', '5', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '20', '2027-08-31'),
('28', '110005', '8850124064555', 'ກາເຟ ເນສກາເຟ ເຣດຄັບ', '1', '28000.00', '35000.00', '81', 'ຂວດ', NULL, NULL, 'prod_nescafe_redcup_45g.jpg', '0', '1', '1', '1', '28', '5', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '10', '2028-03-31'),
('29', '180001', '8851717901019', 'ນົມສົ້ມ ດັຊມິວ 4in1', '8', '5500.00', '8000.00', '19', 'ກ່ອງ', NULL, NULL, 'prod_dutchmill_4in1_180ml.jpg', '0', '1', '1', '1', '29', '12', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '24', '2027-06-30'),
('30', '180002', '8850188101016', 'ນົມຈືດ ໂຟຣໂມສ', '8', '6500.00', '9000.00', '29', 'ກ່ອງ', NULL, NULL, 'prod_foremost_milk_225ml.jpg', '0', '1', '1', '1', '30', '12', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '24', '2027-07-31'),
('31', '180003', '8850124041808', 'ນົມໄມໂລ UHT', '8', '6500.00', '9000.00', '50', 'ກ່ອງ', NULL, NULL, 'prod_milo_uht_180ml.jpg', '0', '1', '1', '1', '31', '12', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '24', '2027-07-31'),
('32', '180004', '8850124011405', 'ນົມກາໝີ ກະປ໋ອງ', '8', '9000.00', '13000.00', '17', 'ປ໋ອງ', NULL, NULL, 'prod_bearbrand_milk_140ml.jpg', '0', '1', '1', '1', '32', '11', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '24', '2027-11-30'),
('33', '180005', '8850135003808', 'ນົມຂຸ້ນຫວານ ກາມະລິ', '8', '16000.00', '22000.00', '10', 'ປ໋ອງ', NULL, NULL, 'prod_mali_condensed_milk_380g.jpg', '0', '1', '1', '1', '33', '11', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '12', '2028-02-28'),
('34', '190001', '8850101001012', 'ປ໊ອກກີ້ ລົດຊັອກໂກແລັດ', '9', '11000.00', '15000.00', '8', 'ກ່ອງ', NULL, NULL, 'prod_pocky_chocolate_47g.jpg', '0', '1', '1', '1', '34', '12', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '15', '2027-08-31'),
('35', '190002', '8850332101011', 'ປາມຶກອົບ ເບນໂຕະ ລົດເຜັດ', '9', '11000.00', '15000.00', '38', 'ຊອງ', NULL, NULL, 'prod_bento_squid_20g.jpg', '0', '1', '1', '1', '35', '7', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '15', '2027-09-30'),
('36', '190003', '8858705600015', 'ສາຫຼ່າຍທອດ ເຖົ້າແກ່ນ້ອຍ', '9', '18000.00', '24000.00', '48', 'ຊອງ', NULL, NULL, 'prod_taokaenoi_seaweed_32g.jpg', '0', '1', '1', '1', '36', '7', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '12', '2027-08-31'),
('37', '190004', '8850157106017', 'ຂ້າວໜົມກຸ້ງ ຮານາມິ', '9', '11000.00', '15000.00', '11', 'ຖົງ', NULL, NULL, 'prod_hanami_prawn_60g.jpg', '0', '1', '1', '1', '37', '15', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '15', '2027-07-31'),
('38', '1100001', '8801073110502', 'ໝີ່ເຜັດເກົາຫຼີ ຊຳຢັງ', '10', '22000.00', '28000.00', '65', 'ຊອງ', NULL, NULL, 'prod_samyang_buldak_140g.jpg', '0', '1', '1', '1', '38', '7', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '20', '2027-10-31'),
('39', '170001', '8850250001553', 'ຜົງປຸງລົດ ຣົດດີ ລົດໝູ', '7', '12000.00', '16000.00', '76', 'ຖົງ', NULL, NULL, 'prod_rosdee_pork_155g.jpg', '0', '1', '1', '1', '39', '15', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '15', '2028-06-30'),
('40', '170002', '8850124002007', 'ຊອດປຸງລົດ ແມັກກີ້', '7', '14000.00', '19000.00', '52', 'ຂວດ', NULL, NULL, 'prod_maggi_sauce_200ml.jpg', '0', '1', '1', '1', '40', '5', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '12', '2028-05-31'),
('41', '170003', '8850643006004', 'ນ້ຳມັນຫອຍ ກາແມ່ຄົວ', '7', '26000.00', '34000.00', '28', 'ຂວດ', NULL, NULL, 'prod_maekrua_oyster_sauce_600ml.jpg', '0', '1', '1', '1', '41', '5', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '12', '2028-06-30'),
('42', '170004', '8851932350013', 'ນ້ຳຢາລ້າງຖ້ວຍ ຊັນໄລ', '7', '17000.00', '23000.00', '47', 'ຂວດ', NULL, NULL, 'prod_sunlight_dishwash_500ml.jpg', '0', '1', '1', '1', '42', '5', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '12', '2028-12-31'),
('43', '1120001', '8851932100700', 'ສະບູກ້ອນ ລັກສ໌', '12', '7000.00', '10000.00', '11', 'ກ້ອນ', NULL, NULL, 'prod_lux_soap_70g.jpg', '0', '1', '1', '1', '43', '24', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '15', '2028-12-31');
-- rows `st_products` 43

DROP TABLE IF EXISTS `st_promotions`;
CREATE TABLE `st_promotions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `promo_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `promo_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `discount_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `discount_value` decimal(10,2) DEFAULT NULL,
  `gift_product_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gift_qty` int DEFAULT '1',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `min_qty` int DEFAULT '0',
  `min_amount` decimal(10,2) DEFAULT '0.00',
  `target_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'all',
  `target_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'ທຸກສິນຄ້າ',
  `target_unit_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'all',
  `status` int DEFAULT '1',
  `branch_id` int DEFAULT '1',
  `branch_no` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`,`start_date`,`end_date`),
  KEY `idx_promotions_branch` (`branch_id`,`status`),
  KEY `idx_branch_no` (`branch_id`,`branch_no`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- rows `st_promotions` 0

DROP TABLE IF EXISTS `st_sale_batch_allocations`;
CREATE TABLE `st_sale_batch_allocations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `save_bill` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `detail_id` int DEFAULT NULL,
  `product_id` int NOT NULL,
  `store_id` int NOT NULL,
  `batch_id` int DEFAULT NULL,
  `qty` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sba_bill` (`save_bill`),
  KEY `idx_sba_batch` (`batch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- rows `st_sale_batch_allocations` 0

DROP TABLE IF EXISTS `st_sales`;
CREATE TABLE `st_sales` (
  `sale_id` int NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `sold_by` int DEFAULT NULL,
  `customer_id` int DEFAULT NULL,
  `customer_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT 'ລູກຄ້າທົ່ວໄປ',
  `subtotal` decimal(12,2) DEFAULT '0.00',
  `discount_amount` decimal(12,2) DEFAULT '0.00',
  `vat_amount` decimal(12,2) DEFAULT '0.00',
  `total_amount` decimal(12,2) DEFAULT '0.00',
  `total_profit` decimal(12,2) DEFAULT '0.00',
  `cash_received` decimal(12,2) DEFAULT '0.00',
  `change_amount` decimal(12,2) DEFAULT '0.00',
  `payment_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'ເງິນສົດ',
  `status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'SUCCESS',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `store_id` int NOT NULL DEFAULT '1',
  `tax_type` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'inclusive',
  `vat_rate` decimal(5,2) DEFAULT '7.00',
  `bank_account_id` int DEFAULT NULL,
  `bank_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `paid_currency` varchar(8) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'KIP',
  `paid_rate` decimal(18,6) NOT NULL DEFAULT '1.000000',
  `paid_amount_foreign` decimal(18,2) DEFAULT NULL,
  PRIMARY KEY (`sale_id`),
  UNIQUE KEY `inv_num` (`invoice_number`),
  KEY `idx_sales_store_date` (`store_id`,`created_at`),
  KEY `idx_sales_invoice` (`invoice_number`)
) ENGINE=InnoDB AUTO_INCREMENT=266 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `st_sales` (`sale_id`, `invoice_number`, `sold_by`, `customer_id`, `customer_name`, `subtotal`, `discount_amount`, `vat_amount`, `total_amount`, `total_profit`, `cash_received`, `change_amount`, `payment_type`, `status`, `created_at`, `store_id`, `tax_type`, `vat_rate`, `bank_account_id`, `bank_name`, `paid_currency`, `paid_rate`, `paid_amount_foreign`) VALUES
('265', '20261102-10001', '1', NULL, 'ລູກຄ້າທົ່ວໄປ', '58000.00', '0.00', '0.00', '58000.00', '0.00', '0.00', '0.00', 'ໂອນເງິນ / QR', 'SUCCESS', '2026-11-02 09:10:02', '1', 'none', '0.00', '2', 'ພົງສະຫວັນ', 'KIP', '1.000000', '58000.00');
-- rows `st_sales` 1

DROP TABLE IF EXISTS `st_shelves`;
CREATE TABLE `st_shelves` (
  `shelf_id` int NOT NULL AUTO_INCREMENT,
  `zone_id` int DEFAULT NULL,
  `shelf_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `store_id` int NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`shelf_id`),
  KEY `idx_shelf_zone` (`zone_id`),
  KEY `idx_shelf_store` (`store_id`,`shelf_name`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- rows `st_shelves` 0

DROP TABLE IF EXISTS `st_shift_sessions`;
CREATE TABLE `st_shift_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `shift_id` int NOT NULL,
  `shift_name` varchar(100) NOT NULL,
  `store_id` int NOT NULL DEFAULT '1',
  `branch_id` int NOT NULL DEFAULT '1',
  `opened_by_user_id` int NOT NULL,
  `opened_by_name` varchar(100) NOT NULL,
  `staff_names` varchar(500) DEFAULT NULL,
  `open_time` datetime NOT NULL,
  `last_active_at` datetime DEFAULT NULL,
  `initial_cash` decimal(15,2) NOT NULL DEFAULT '0.00',
  `closed_by_user_id` int DEFAULT NULL,
  `closed_by_name` varchar(100) DEFAULT NULL,
  `close_time` datetime DEFAULT NULL,
  `system_sales_cash` decimal(15,2) NOT NULL DEFAULT '0.00',
  `system_sales_transfer` decimal(15,2) NOT NULL DEFAULT '0.00',
  `system_sales_debt` decimal(15,2) NOT NULL DEFAULT '0.00',
  `system_total_sales` decimal(15,2) NOT NULL DEFAULT '0.00',
  `system_total_bills` int NOT NULL DEFAULT '0',
  `counted_sales_cash` decimal(15,2) DEFAULT NULL,
  `difference_cash` decimal(15,2) DEFAULT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `notes` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ss_store_status` (`store_id`,`status`),
  KEY `idx_ss_open_time` (`open_time`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `st_shift_sessions` (`id`, `shift_id`, `shift_name`, `store_id`, `branch_id`, `opened_by_user_id`, `opened_by_name`, `staff_names`, `open_time`, `last_active_at`, `initial_cash`, `closed_by_user_id`, `closed_by_name`, `close_time`, `system_sales_cash`, `system_sales_transfer`, `system_sales_debt`, `system_total_sales`, `system_total_bills`, `counted_sales_cash`, `difference_cash`, `status`, `notes`, `created_at`, `updated_at`) VALUES
('7', '8', 'ກະແລງ', '1', '1', '3', 'la', 'la', '2026-10-23 16:13:05', '2026-10-23 16:13:05', '0.00', NULL, 'ລະບົບ (ປິດອໍໂຕ້)', '2026-10-23 16:15:00', '0.00', '132000.00', '0.00', '132000.00', '1', NULL, NULL, 'closed', 'ປິດອໍໂຕ້: ຮອດໂມງປິດກະແລ້ວ ແລະ ບໍ່ມີພະນັກງານໃຊ້ງານ 15 ນາທີ', '2026-10-23 16:13:05', '2026-10-31 10:37:21'),
('8', '8', 'ກະແລງ', '1', '1', '3', 'la', 'la', '2026-10-23 16:29:12', '2026-10-23 16:29:12', '0.00', NULL, 'ລະບົບ (ປິດອໍໂຕ້)', '2026-10-23 16:29:12', '0.00', '125000.00', '0.00', '125000.00', '2', NULL, NULL, 'closed', 'ປິດອໍໂຕ້: ຮອດໂມງປິດກະແລ້ວ ແລະ ບໍ່ມີພະນັກງານໃຊ້ງານ 15 ນາທີ', '2026-10-23 16:29:12', '2026-10-31 10:37:21'),
('9', '8', 'ກະແລງ', '1', '1', '3', 'la', 'la', '2026-10-23 16:47:00', '2026-10-23 16:47:00', '0.00', '3', 'la', '2026-10-23 16:50:16', '39000.00', '153000.00', '0.00', '92000.00', '2', '39.00', '-38961.00', 'closed', '', '2026-10-23 16:47:00', '2026-10-31 10:37:21'),
('10', '8', 'ກະແລງ', '1', '1', '3', 'la', 'la', '2026-10-26 09:32:49', '2026-10-26 09:32:49', '100000.00', '3', 'la', '2026-10-26 10:55:50', '0.00', '622000.00', '0.00', '622000.00', '3', '0.00', '0.00', 'closed', '', '2026-10-26 09:32:49', '2026-10-31 10:37:21'),
('11', '8', 'ກະແລງ', '1', '1', '3', 'la', 'la', '2026-10-26 14:05:22', '2026-10-26 14:05:22', '50000.00', '3', 'la', '2026-10-26 14:08:04', '700000.00', '0.00', '0.00', '674000.00', '1', '680000.00', '-20000.00', 'closed', '', '2026-10-26 14:05:22', '2026-10-31 10:37:21'),
('12', '8', 'ກະແລງ', '1', '1', '3', 'la', 'la', '2026-10-26 14:09:07', '2026-10-26 14:09:07', '0.00', '3', 'la', '2026-10-26 14:11:55', '135000.00', '129000.00', '0.00', '264000.00', '3', '259000.00', '124000.00', 'closed', '', '2026-10-26 14:09:07', '2026-10-31 10:37:21'),
('16', '11', 'ກະບ່າຍ', '1', '1', '3', 'la', 'la', '2026-10-30 11:42:08', '2026-10-30 11:42:08', '50000.00', '3', 'la', '2026-10-30 11:43:08', '136000.00', '0.00', '0.00', '136000.00', '1', NULL, NULL, 'closed', 'ປິດກະອັດຕະໂນມັດ: ອອກຈາກລະບົບ', '2026-10-30 11:42:08', '2026-10-31 10:37:21'),
('17', '11', 'ກະບ່າຍ', '1', '1', '3', 'la', 'la', '2026-10-30 11:43:48', '2026-10-30 11:43:48', '50000.00', '3', 'la', '2026-10-30 11:44:17', '106000.00', '0.00', '0.00', '106000.00', '1', '106000.00', '0.00', 'closed', '', '2026-10-30 11:43:48', '2026-10-31 10:37:21'),
('18', '11', 'ກະບ່າຍ', '1', '1', '3', 'la', 'la', '2026-10-30 14:08:53', '2026-10-30 14:08:53', '50000.00', '0', 'ລະບົບ (ປິດອໍໂຕ້)', '2026-10-30 14:12:00', '0.00', '74000.00', '0.00', '74000.00', '1', NULL, NULL, 'closed', 'ປິດກະອັດຕະໂນມັດ: ໝົດໂມງກະ (ພະນັກງານບໍ່ອອນລາຍ)', '2026-10-30 14:08:53', '2026-10-31 10:37:21');
-- rows `st_shift_sessions` 9

DROP TABLE IF EXISTS `st_shifts`;
CREATE TABLE `st_shifts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `shift_name` varchar(100) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `staff_user_ids` varchar(500) DEFAULT NULL,
  `store_id` int NOT NULL DEFAULT '1',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_shift_store` (`store_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- rows `st_shifts` 0

DROP TABLE IF EXISTS `st_stock_check_count_log`;
CREATE TABLE `st_stock_check_count_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `check_id` int NOT NULL,
  `item_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `old_qty` int DEFAULT NULL,
  `new_qty` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sccl_item` (`check_id`,`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- rows `st_stock_check_count_log` 0

DROP TABLE IF EXISTS `st_stock_check_items`;
CREATE TABLE `st_stock_check_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `check_id` int NOT NULL,
  `product_id` int NOT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `unit` varchar(100) DEFAULT NULL,
  `zone` varchar(100) DEFAULT NULL,
  `shelf` varchar(100) DEFAULT NULL,
  `cost_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `system_qty` int NOT NULL DEFAULT '0',
  `counted_qty` int DEFAULT NULL,
  `counted_by` int DEFAULT NULL,
  `counted_at` datetime DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sci_check_product` (`check_id`,`product_id`),
  KEY `idx_sci_check` (`check_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2005 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `st_stock_check_items` (`id`, `check_id`, `product_id`, `barcode`, `product_name`, `unit`, `zone`, `shelf`, `cost_price`, `system_qty`, `counted_qty`, `counted_by`, `counted_at`, `note`) VALUES
('1942', '39', '10009', '2021360299994', 'M-150 ເຄື່ອງດື່ມຊູກຳລັງ', 'ແກ້ວ', NULL, NULL, '6000.00', '79', NULL, NULL, NULL, NULL),
('1943', '39', '110005', '8850124064555', 'ກາເຟ ເນສກາເຟ ເຣດຄັບ', 'ຂວດ', NULL, NULL, '28000.00', '81', NULL, NULL, NULL, NULL),
('1944', '39', '10011', '8850188300221', 'ກາເຟ ເບີດີ້ ໂຣບັສຕ້າ', 'ປ໋ອງ', NULL, NULL, '8000.00', '14', NULL, NULL, NULL, NULL),
('1945', '39', '190004', '8850157106017', 'ຂ້າວໜົມກຸ້ງ ຮານາມິ', 'ຖົງ', NULL, NULL, '11000.00', '11', NULL, NULL, NULL, NULL),
('1946', '39', '10010', '8850188200115', 'ຄາຣາບາວແດງ', 'ແກ້ວ', NULL, NULL, '6000.00', '94', NULL, NULL, NULL, NULL),
('1947', '39', '90006', '8850029054321', 'ຄິດແຄັດ ຊັອກໂກແລັດ', 'ຊອງ', NULL, NULL, '8000.00', '80', NULL, NULL, NULL, NULL),
('1948', '39', '170002', '8850124002007', 'ຊອດປຸງລົດ ແມັກກີ້', 'ຂວດ', NULL, NULL, '14000.00', '52', NULL, NULL, NULL, NULL),
('1949', '39', '110004', '8854698005012', 'ຊາຂຽວ ໂອອິຊິ ນ້ຳເຜິ້ງໝາກນາວ', 'ຂວດ', NULL, NULL, '10000.00', '55', NULL, NULL, NULL, NULL),
('1950', '39', '130023', '2026880235040', 'ຊານົມ', 'ອັນ', NULL, NULL, '20000.00', '2', NULL, NULL, NULL, NULL),
('1951', '39', '180004', '8850124011405', 'ນົມກາໝີ ກະປ໋ອງ', 'ປ໋ອງ', NULL, NULL, '9000.00', '17', NULL, NULL, NULL, NULL),
('1952', '39', '180005', '8850135003808', 'ນົມຂຸ້ນຫວານ ກາມະລິ', 'ປ໋ອງ', NULL, NULL, '16000.00', '10', NULL, NULL, NULL, NULL),
('1953', '39', '180002', '8850188101016', 'ນົມຈືດ ໂຟຣໂມສ', 'ກ່ອງ', NULL, NULL, '6500.00', '28', NULL, NULL, NULL, NULL),
('1954', '39', '180001', '8851717901019', 'ນົມສົ້ມ ດັຊມິວ 4in1', 'ກ່ອງ', NULL, NULL, '5500.00', '14', NULL, NULL, NULL, NULL),
('1955', '39', '80002', '2073203999158', 'ນົມເລັກຕາຊອຍ', 'ກ່ອງ', NULL, NULL, '8000.00', '90', NULL, NULL, NULL, NULL),
('1956', '39', '180003', '8850124041808', 'ນົມໄມໂລ UHT', 'ກ່ອງ', NULL, NULL, '6500.00', '49', NULL, NULL, NULL, NULL),
('1957', '39', '10002', '8851717041234', 'ນ້ຳດື່ມຫົວເສືອ', 'ຕຸກ', NULL, NULL, '3000.00', '195', NULL, NULL, NULL, NULL),
('1958', '39', '70007', '8850157001015', 'ນ້ຳປາ ທິບພະຣົດ', 'ຕຸກ', NULL, NULL, '15000.00', '53', NULL, NULL, NULL, NULL),
('1959', '39', '70008', '8850157002022', 'ນ້ຳມັນພືດ ມໍຣະກົດ', 'ຕຸກ', NULL, NULL, '22000.00', '43', NULL, NULL, NULL, NULL),
('1960', '39', '170003', '8850643006004', 'ນ້ຳມັນຫອຍ ກາແມ່ຄົວ', 'ຂວດ', NULL, NULL, '26000.00', '28', NULL, NULL, NULL, NULL),
('1961', '39', '170004', '8851932350013', 'ນ້ຳຢາລ້າງຖ້ວຍ ຊັນໄລ', 'ຂວດ', NULL, NULL, '17000.00', '47', NULL, NULL, NULL, NULL),
('1962', '39', '11002', '8850157004044', 'ປາກະປ໋ອງ ສາມແມ່ຄົວ', 'ປ໋ອງ', NULL, NULL, '12000.00', '22', NULL, NULL, NULL, NULL),
('1963', '39', '11001', '8850157003033', 'ປາກະປ໋ອງ ໂຣຊ່າ', 'ປ໋ອງ', NULL, NULL, '12000.00', '80', NULL, NULL, NULL, NULL),
('1964', '39', '190002', '8850332101011', 'ປາມຶກອົບ ເບນໂຕະ ລົດເຜັດ', 'ຊອງ', NULL, NULL, '11000.00', '38', NULL, NULL, NULL, NULL),
('1965', '39', '190001', '8850101001012', 'ປ໊ອກກີ້ ລົດຊັອກໂກແລັດ', 'ກ່ອງ', NULL, NULL, '11000.00', '8', NULL, NULL, NULL, NULL),
('1966', '39', '170001', '8850250001553', 'ຜົງປຸງລົດ ຣົດດີ ລົດໝູ', 'ຖົງ', NULL, NULL, '12000.00', '76', NULL, NULL, NULL, NULL),
('1967', '39', '100016', '2094815295841', 'ມ່າມ່າ ຕົ້ມຍຳກຸ້ງ', 'ຫໍ່', NULL, NULL, '4000.00', '153', NULL, NULL, NULL, NULL),
('1968', '39', '12001', '8850157005055', 'ຢາສີຟັນ ຄອນເກດ', 'ກ່ອງ', NULL, NULL, '15000.00', '60', NULL, NULL, NULL, NULL),
('1969', '39', '10005', '2078263953966', 'ສະຕິງຂວດເຫຼືອງ', 'ຕຸກ', NULL, NULL, '7000.00', '82', NULL, NULL, NULL, NULL),
('1970', '39', '1120001', '8851932100700', 'ສະບູກ້ອນ ລັກສ໌', 'ກ້ອນ', NULL, NULL, '7000.00', '11', NULL, NULL, NULL, NULL),
('1971', '39', '110003', '8850228000205', 'ສະປອນເຊີ ເຄື່ອງດື່ມເກືອແຮ່', 'ຂວດ', NULL, NULL, '8000.00', '44', NULL, NULL, NULL, NULL),
('1972', '39', '110002', '8851959132038', 'ສະໄປຣທ໌ ກະປ໋ອງ', 'ປ໋ອງ', NULL, NULL, '7000.00', '55', NULL, NULL, NULL, NULL),
('1973', '39', '190003', '8858705600015', 'ສາຫຼ່າຍທອດ ເຖົ້າແກ່ນ້ອຍ', 'ຊອງ', NULL, NULL, '18000.00', '48', NULL, NULL, NULL, NULL),
('1974', '39', '100013', '2047888140464', 'ຫມີ່ໄວໄວ ຕົ້ມຍຳ', 'ຫໍ່', NULL, NULL, '3000.00', '180', NULL, NULL, NULL, NULL),
('1975', '39', '10001', '2048466388643', 'ເບຍລາວ ຂວດໃຫຍ່', 'ແກ້ວ', NULL, NULL, '10000.00', '150', NULL, NULL, NULL, NULL),
('1976', '39', '40012', '2004456587038', 'ເບຍລາວ ປ໋ອງສັ້ນ', 'ປ໋ອງ', NULL, NULL, '9000.00', '120', NULL, NULL, NULL, NULL),
('1977', '39', '40011', '2077782445808', 'ເບຍໄຮນິເກັນ', 'ແກ້ວ', NULL, NULL, '14000.00', '80', NULL, NULL, NULL, NULL),
('1978', '39', '10004', '8851959142021', 'ເປັບຊີກະປ໋ອງ', 'ປ໋ອງ', NULL, NULL, '6000.00', '105', NULL, NULL, NULL, NULL),
('1979', '39', '90004', '2075318035929', 'ເລ ມັນຝຣັ່ງທອດ', 'ຖົງ', NULL, NULL, '8000.00', '89', NULL, NULL, NULL, NULL),
('1980', '39', '70006', '2000458862838', 'ແປງນົວ ອາຍິໂນະໂມະໂຕ', 'ຖົງ', NULL, NULL, '12000.00', '58', NULL, NULL, NULL, NULL),
('1981', '39', '110001', '8851959132052', 'ແຟນຕ້າ ນ້ຳສົ້ມ ກະປ໋ອງ', 'ປ໋ອງ', NULL, NULL, '7000.00', '55', NULL, NULL, NULL, NULL),
('1982', '39', '10003', '8851959132014', 'ໂຄກກະປ໋ອງ', 'ປ໋ອງ', NULL, NULL, '6000.00', '110', NULL, NULL, NULL, NULL),
('1983', '39', '90005', '8850029012345', 'ໂອຣິໂອ້ ລົດດັ້ງເດີມ', 'ຊອງ', NULL, NULL, '10000.00', '70', NULL, NULL, NULL, NULL),
('1984', '39', '1100001', '8801073110502', 'ໝີ່ເຜັດເກົາຫຼີ ຊຳຢັງ', 'ຊອງ', NULL, NULL, '22000.00', '65', NULL, NULL, NULL, NULL);
-- rows `st_stock_check_items` 43

DROP TABLE IF EXISTS `st_stock_checks`;
CREATE TABLE `st_stock_checks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `check_no` varchar(30) NOT NULL,
  `store_id` int NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `note` varchar(255) DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `submitted_by` int DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `completed_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sc_store` (`store_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `st_stock_checks` (`id`, `check_no`, `store_id`, `status`, `note`, `user_id`, `submitted_by`, `submitted_at`, `approved_by`, `approved_at`, `completed_by`, `created_at`, `completed_at`) VALUES
('38', 'SC-20261102-20001', '2', 'draft', NULL, '20', NULL, NULL, NULL, NULL, NULL, '2026-11-02 09:32:47', NULL),
('39', 'SC-20261102-10001', '1', 'draft', NULL, '1', NULL, NULL, NULL, NULL, NULL, '2026-11-02 09:41:03', NULL);
-- rows `st_stock_checks` 2

DROP TABLE IF EXISTS `st_stock_transfer_details`;
CREATE TABLE `st_stock_transfer_details` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transfer_id` int NOT NULL,
  `product_id` int NOT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `barcode` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `qty` int NOT NULL DEFAULT '0',
  `unit` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bprice` decimal(10,2) DEFAULT '0.00',
  `price` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `idx_transfer_id` (`transfer_id`),
  KEY `idx_product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- rows `st_stock_transfer_details` 0

DROP TABLE IF EXISTS `st_stock_transfers`;
CREATE TABLE `st_stock_transfers` (
  `transfer_id` int NOT NULL AUTO_INCREMENT,
  `transfer_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `from_store_id` int NOT NULL,
  `to_store_id` int NOT NULL,
  `transfer_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'completed',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`transfer_id`),
  KEY `idx_from_store` (`from_store_id`),
  KEY `idx_to_store` (`to_store_id`),
  KEY `idx_transfers_stores` (`from_store_id`,`to_store_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- rows `st_stock_transfers` 0

DROP TABLE IF EXISTS `st_stock_writeoffs`;
CREATE TABLE `st_stock_writeoffs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `store_id` int NOT NULL,
  `product_id` int NOT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `batch_id` int DEFAULT NULL,
  `qty` int NOT NULL,
  `reason` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `note` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cost_value` decimal(15,2) NOT NULL DEFAULT '0.00',
  `user_id` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sw_store_date` (`store_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- rows `st_stock_writeoffs` 0

DROP TABLE IF EXISTS `st_suppliers`;
CREATE TABLE `st_suppliers` (
  `supplier_id` int NOT NULL AUTO_INCREMENT,
  `supplier_name` varchar(191) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contact_person` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `store_id` int NOT NULL DEFAULT '1',
  `branch_no` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`supplier_id`),
  KEY `store_id` (`store_id`),
  KEY `supplier_name` (`supplier_name`),
  KEY `idx_branch_no` (`store_id`,`branch_no`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- rows `st_suppliers` 0

DROP TABLE IF EXISTS `st_system_settings`;
CREATE TABLE `st_system_settings` (
  `setting_id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=5936 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `st_system_settings` (`setting_id`, `setting_key`, `setting_value`, `created_at`) VALUES
('1', 'queue_enabled', '0', '2026-09-10 16:02:20'),
('4', 'loyalty_enabled', '0', '2026-09-15 11:41:46'),
('5', 'loyalty_spend_amount', '1000000', '2026-09-15 11:41:46'),
('6', 'loyalty_points_earned', '1', '2026-09-15 11:41:46'),
('7', 'display_currency', 'KIP', '2026-09-15 11:41:46'),
('111', 'print_agent_token', '1441d0b2e9cc7970c4fb4a57a34b06b61b861c80e8992c04', '2026-09-18 17:04:38'),
('117', 'print_mode', 'direct', '2026-09-18 17:08:11'),
('2994', 'pharmacy_batches_reconciled', '1', '2026-09-30 09:24:26'),
('5913', 'tts_api_key', 'sd_E1IJIY9nZlJpZkbk2iIsTtzVO5gl-aILGfThx0a7Jqk', '2026-10-31 13:29:12');
-- rows `st_system_settings` 9

DROP TABLE IF EXISTS `st_tbcompanyinfo`;
CREATE TABLE `st_tbcompanyinfo` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `com_name_la` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `com_address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `com_tel` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `com_email` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tax_id` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `img_url` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `qr_img` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `barcode` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `barcode2` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `latitude` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `longitude` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `expire_date` date DEFAULT NULL,
  `branch_id` int DEFAULT '1',
  `vat_percent` decimal(5,2) DEFAULT '7.00',
  `tax_type` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'inclusive',
  `license_start_date` date DEFAULT '2026-01-01',
  `license_expire_date` date DEFAULT '2026-12-31',
  `receipt_footer_en` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `receipt_footer_th` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `receipt_footer_zh` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `st_tbcompanyinfo` (`Id`, `com_name_la`, `com_address`, `com_tel`, `com_email`, `tax_id`, `img_url`, `qr_img`, `barcode`, `barcode2`, `latitude`, `longitude`, `start_date`, `expire_date`, `branch_id`, `vat_percent`, `tax_type`, `license_start_date`, `license_expire_date`, `receipt_footer_en`, `receipt_footer_th`, `receipt_footer_zh`) VALUES
('1', 'ຮ້ານ ຂາຍດີ ຈະເລີນຊັບ', 'ໂນນຄໍ້ເໜືອ ໄຊເສດຖາ ນະຄອນຫຼວງວຽງຈັນ', '020 95321848', '', '', 'logo_1789110671.png', '', '**ຂອບໃຈທີ່ມາອຸດໜູນ, ໂອກາດໜ້າເຊີນໃໝ່**', NULL, NULL, NULL, NULL, NULL, '-1', '0.00', 'none', '2026-09-15', '2027-09-15', NULL, NULL, NULL),
('2', 'ຮ້ານ ຂາຍດີ ຈະເລີນຊັບ', 'ບ້ານ ໂນນຄໍ້ເໜືອ ເມືອງ ໄຊເສດຖາ ນະຄອນຫຼວງວຽງຈັນ', '020 55 949 007', '', '', 'logo_1792724248_506.jpg', '', '**ຂອບໃຈທີ່ມາອຸດໜູນ, ໂອກາດໜ້າເຊີນໃໝ່**', NULL, NULL, NULL, NULL, NULL, '1', '0.00', 'none', '2026-09-15', '2027-09-15', NULL, NULL, NULL);
-- rows `st_tbcompanyinfo` 2

DROP TABLE IF EXISTS `st_tbexchange`;
CREATE TABLE `st_tbexchange` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `ex_date` date DEFAULT NULL,
  `ex_time` time DEFAULT NULL,
  `ex_kip_bath` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ex_kip_us` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ex_kip_cn` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ex_status` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ex_userlogin` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `branch_id` int DEFAULT '1',
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `st_tbexchange` (`Id`, `ex_date`, `ex_time`, `ex_kip_bath`, `ex_kip_us`, `ex_kip_cn`, `ex_status`, `ex_userlogin`, `branch_id`) VALUES
('3', '2026-09-11', '09:18:40', '720', '22000', '3100', 'Active', 'admin', '-1');
-- rows `st_tbexchange` 1

DROP TABLE IF EXISTS `st_tbl_printer`;
CREATE TABLE `st_tbl_printer` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ip_address` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type` enum('ip','browser','usb') COLLATE utf8mb4_general_ci DEFAULT 'ip',
  `status` tinyint(1) DEFAULT '1',
  `branch_id` int DEFAULT '1',
  `paper_width` tinyint unsigned NOT NULL DEFAULT '80',
  `is_receipt_printer` tinyint(1) NOT NULL DEFAULT '0',
  `print_offset_mm` decimal(4,1) NOT NULL DEFAULT '0.0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `st_tbl_printer` (`id`, `name`, `ip_address`, `type`, `status`, `branch_id`, `paper_width`, `is_receipt_printer`, `print_offset_mm`) VALUES
('2', 'ໜ້າຮ້ານ', '192.168.1.200', 'ip', '1', '-1', '80', '0', '0.0'),
('3', 'ເຮືອນຄົວ', '192.168.1.8', 'ip', '1', '-1', '80', '0', '0.0'),
('7', 'ໜ້າຮ້ານ', '192.168.1.200', 'ip', '1', '2', '80', '1', '0.0'),
('8', 'ເຮືອນຄົວ', '192.168.1.8', 'ip', '1', '2', '80', '0', '0.0'),
('9', 'ເຮືອນຄົວ', '', 'browser', '1', '1', '80', '0', '0.0'),
('11', 'ທົດລອງ', '192.168.1.8', 'ip', '1', '1', '80', '0', '0.0'),
('12', 'XP-80C', 'XP-80C', 'usb', '1', '1', '80', '1', '0.0'),
('13', 'XP-80C', 'XP-80C', 'usb', '1', '-1', '80', '1', '0.0');
-- rows `st_tbl_printer` 8

DROP TABLE IF EXISTS `st_tbsale_save`;
CREATE TABLE `st_tbsale_save` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `sale_save_bill` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sale_date` date DEFAULT NULL,
  `sale_save_table` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sale_save_status` int DEFAULT NULL,
  `sale_qty` int DEFAULT NULL,
  `sale_amount` decimal(10,2) DEFAULT NULL COMMENT '(ยอดรวมก่อนส่วนลด)',
  `sale_discount_item` decimal(10,2) DEFAULT NULL,
  `sale_after_item` decimal(10,2) DEFAULT NULL,
  `sale_pay` decimal(10,2) DEFAULT NULL COMMENT '(เงินสด)',
  `sale_transfer` decimal(10,2) DEFAULT NULL COMMENT '(เงินโอน)',
  `sale_percented_bill` int DEFAULT NULL COMMENT '(ส่วนลด %)',
  `sale_discount_bill` decimal(10,2) DEFAULT NULL COMMENT '(ส่วนลดเป็นเงิน)',
  `sale_tip` decimal(10,2) DEFAULT NULL,
  `sale_barlance` decimal(10,2) DEFAULT NULL COMMENT '(ยอดสุทธิที่ต้องจ่าย)',
  `sale_return` decimal(10,2) DEFAULT NULL COMMENT '(เงินทอน)',
  `user_receive` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '(ผู้รับเงิน)',
  `sale_remark` mediumtext COLLATE utf8mb4_general_ci,
  `sale_status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '(paid / cancel)',
  `customer_id` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type_pay` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '(cash / transfer / split)',
  `sale_time` time DEFAULT '00:00:00',
  `queue_no` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `branch_id` int DEFAULT '1',
  `store_id` int NOT NULL DEFAULT '1',
  `shift_session_id` int DEFAULT NULL,
  `bank_account_id` int DEFAULT NULL,
  `bank_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `points_earned` int NOT NULL DEFAULT '0',
  `paid_currency` varchar(8) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'KIP',
  `paid_rate` decimal(18,6) NOT NULL DEFAULT '1.000000',
  `paid_amount_foreign` decimal(18,2) DEFAULT NULL,
  `cash_received` decimal(12,2) DEFAULT '0.00',
  `qr_received` decimal(12,2) DEFAULT '0.00',
  `points_redeemed` int NOT NULL DEFAULT '0',
  `points_discount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `rx_doctor` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rx_number` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rx_note` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sale_channel` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'store',
  `dl_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dl_phone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dl_courier` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dl_branch` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dl_address` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dl_payment` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dl_printed_at` datetime DEFAULT NULL,
  `dl_ship_pay` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `uq_sale_save_bill` (`sale_save_bill`),
  KEY `idx_sale_date` (`sale_date`),
  KEY `idx_sale_status` (`sale_status`),
  KEY `idx_shift_session` (`shift_session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=478 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `st_tbsale_save` (`Id`, `sale_save_bill`, `sale_date`, `sale_save_table`, `sale_save_status`, `sale_qty`, `sale_amount`, `sale_discount_item`, `sale_after_item`, `sale_pay`, `sale_transfer`, `sale_percented_bill`, `sale_discount_bill`, `sale_tip`, `sale_barlance`, `sale_return`, `user_receive`, `sale_remark`, `sale_status`, `customer_id`, `customer_name`, `ip_address`, `type_pay`, `sale_time`, `queue_no`, `branch_id`, `store_id`, `shift_session_id`, `bank_account_id`, `bank_name`, `points_earned`, `paid_currency`, `paid_rate`, `paid_amount_foreign`, `cash_received`, `qr_received`, `points_redeemed`, `points_discount`, `rx_doctor`, `rx_number`, `rx_note`, `sale_channel`, `dl_name`, `dl_phone`, `dl_courier`, `dl_branch`, `dl_address`, `dl_payment`, `dl_printed_at`, `dl_ship_pay`) VALUES
('477', '20261102-10001', '2026-11-02', NULL, NULL, '7', '58000.00', NULL, NULL, '0.00', NULL, NULL, '0.00', NULL, '58000.00', '0.00', 'admin', NULL, 'CANCEL', '', 'ລູກຄ້າທົ່ວໄປ', '::1', 'ໂອນເງິນ / QR', '09:10:02', '01', '1', '1', NULL, '2', 'ພົງສະຫວັນ', '0', 'KIP', '1.000000', '58000.00', '0.00', '58000.00', '0', '0.00', NULL, NULL, NULL, 'store', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
-- rows `st_tbsale_save` 1

DROP TABLE IF EXISTS `st_tbsale_save_detail`;
CREATE TABLE `st_tbsale_save_detail` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `save_bill` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `save_date` date DEFAULT NULL,
  `save_time` time NOT NULL,
  `save_table` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `save_proid` int DEFAULT NULL,
  `save_proname` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `save_qty` int DEFAULT NULL,
  `save_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cost_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'ຕົ້ນທືນ',
  `save_money` decimal(10,2) NOT NULL DEFAULT '0.00',
  `save_discount_item` decimal(10,2) DEFAULT NULL,
  `save_net_money` decimal(10,2) DEFAULT NULL,
  `save_status` int DEFAULT NULL,
  `user_receives` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sale_sale_remark` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `branch_id` int DEFAULT '1',
  `multiplier` int NOT NULL DEFAULT '1',
  `save_orig_price` decimal(15,2) DEFAULT NULL,
  `is_reward` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`Id`),
  KEY `idx_save_bill` (`save_bill`),
  KEY `idx_save_proid` (`save_proid`),
  KEY `idx_save_date` (`save_date`)
) ENGINE=InnoDB AUTO_INCREMENT=1009 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `st_tbsale_save_detail` (`Id`, `save_bill`, `save_date`, `save_time`, `save_table`, `save_proid`, `save_proname`, `save_qty`, `save_price`, `cost_price`, `save_money`, `save_discount_item`, `save_net_money`, `save_status`, `user_receives`, `sale_sale_remark`, `branch_id`, `multiplier`, `save_orig_price`, `is_reward`) VALUES
('1006', '20261102-10001', '2026-11-02', '09:10:02', NULL, '180002', 'ນົມຈືດ ໂຟຣໂມສ (ກ່ອງ)', '1', '9000.00', '6500.00', '9000.00', '0.00', '9000.00', NULL, 'admin', NULL, '1', '1', '9000.00', '0'),
('1007', '20261102-10001', '2026-11-02', '09:10:02', NULL, '180003', 'ນົມໄມໂລ UHT (ກ່ອງ)', '1', '9000.00', '6500.00', '9000.00', '0.00', '9000.00', NULL, 'admin', NULL, '1', '1', '9000.00', '0'),
('1008', '20261102-10001', '2026-11-02', '09:10:02', NULL, '180001', 'ນົມສົ້ມ ດັຊມິວ 4in1 (ກ່ອງ)', '5', '8000.00', '5500.00', '40000.00', '0.00', '40000.00', NULL, 'admin', NULL, '1', '1', '8000.00', '0');
-- rows `st_tbsale_save_detail` 3

DROP TABLE IF EXISTS `st_tbstore`;
CREATE TABLE `st_tbstore` (
  `store_id` int NOT NULL AUTO_INCREMENT,
  `store_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `store_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `is_main` tinyint(1) DEFAULT '0',
  `address` text COLLATE utf8mb4_general_ci,
  `tel` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`store_id`),
  UNIQUE KEY `store_code` (`store_code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `st_tbstore` (`store_id`, `store_code`, `store_name`, `is_main`, `address`, `tel`, `status`, `created_at`) VALUES
('1', '1', 'ຮ້ານ ຂາຍດີ ຈະເລີນຊັບ', '1', 'ບ້ານ ໂນນຄໍ້ເໜືອ ເມືອງ ໄຊເສດຖາ ນະຄອນຫຼວງວຽງຈັນ', '020 55 949 007', 'active', '2026-09-11 10:38:25'),
('2', '2', 'ຮ້ານ ຂາຍດີ ສາຂາ 2', '0', 'ໂນນສະຫວ່າງ ສີໂຄດຕະບອດ ນະຄອນຫຼວງວຽງຈັນ', '020 95321848', 'inactive', '2026-09-11 10:40:13');
-- rows `st_tbstore` 2

DROP TABLE IF EXISTS `st_tbuser`;
CREATE TABLE `st_tbuser` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `user_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sale` int NOT NULL DEFAULT '0',
  `cafe` int NOT NULL DEFAULT '0',
  `order` int NOT NULL DEFAULT '0',
  `kitchen` int NOT NULL DEFAULT '0',
  `tbl` int NOT NULL DEFAULT '0',
  `report` int NOT NULL DEFAULT '0',
  `stock` int NOT NULL DEFAULT '0',
  `setup` int NOT NULL DEFAULT '0',
  `users` int NOT NULL DEFAULT '0',
  `edit` int NOT NULL DEFAULT '0',
  `expired_date` date DEFAULT NULL,
  `store_id` int DEFAULT '1',
  `branch_no` int DEFAULT NULL,
  `accounting` tinyint(1) DEFAULT '0',
  `fname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gender` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `tel` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `notes` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `profile_img` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'default.png',
  `customers` tinyint(1) DEFAULT '1',
  `dashboard` tinyint(1) DEFAULT '0',
  `permissions` tinyint(1) DEFAULT '0',
  `branches` tinyint(1) DEFAULT '0',
  `database` tinyint(1) DEFAULT '0',
  `products` tinyint(1) DEFAULT '0',
  `import_stock` tinyint(1) DEFAULT '0',
  `import_list` tinyint(1) DEFAULT '0',
  `categories` tinyint(1) DEFAULT '0',
  `daily_report` tinyint(1) DEFAULT '0',
  `all_sales` tinyint(1) DEFAULT '0',
  `best_seller` tinyint(1) DEFAULT '0',
  `profit_cost` tinyint(1) DEFAULT '0',
  `financial` tinyint(1) DEFAULT '0',
  `category_sales` tinyint(1) DEFAULT '0',
  `delete_bills` tinyint(1) DEFAULT '0',
  `stock_transfer` tinyint(1) DEFAULT '0',
  `transfer_history` tinyint(1) DEFAULT '0',
  `promotions` tinyint(1) DEFAULT '0',
  `price_adjustment` tinyint(1) DEFAULT '0',
  `stores` tinyint(1) DEFAULT '0',
  `exchange_rate` tinyint(1) DEFAULT '0',
  `printers` tinyint(1) DEFAULT '0',
  `print_barcode` tinyint(1) DEFAULT '0',
  `last_activity` datetime DEFAULT NULL,
  `item_sales` tinyint(1) DEFAULT '0',
  `suppliers` tinyint(1) DEFAULT '0',
  `loyalty` tinyint(1) DEFAULT '0',
  `access_expire_date` date DEFAULT NULL,
  `units` tinyint(1) DEFAULT '0',
  `stock_check` tinyint(1) DEFAULT '0',
  `stock_count` tinyint(1) DEFAULT '0',
  `account_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`Id`),
  KEY `idx_branch_no` (`store_id`,`branch_no`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `st_tbuser` (`Id`, `user_code`, `username`, `password`, `status`, `sale`, `cafe`, `order`, `kitchen`, `tbl`, `report`, `stock`, `setup`, `users`, `edit`, `expired_date`, `store_id`, `branch_no`, `accounting`, `fname`, `lname`, `gender`, `dob`, `tel`, `address`, `notes`, `profile_img`, `customers`, `dashboard`, `permissions`, `branches`, `database`, `products`, `import_stock`, `import_list`, `categories`, `daily_report`, `all_sales`, `best_seller`, `profit_cost`, `financial`, `category_sales`, `delete_bills`, `stock_transfer`, `transfer_history`, `promotions`, `price_adjustment`, `stores`, `exchange_rate`, `printers`, `print_barcode`, `last_activity`, `item_sales`, `suppliers`, `loyalty`, `access_expire_date`, `units`, `stock_check`, `stock_count`, `account_active`) VALUES
('1', '001', 'admin', '$2y$10$9BvGKbTZwcNGC5VkIoV6Nuu5Or0rYKWY6bvKpSgS2Y2s1BeQE3kje', 'ຜູ້ບໍລິຫານ', '1', '0', '0', '0', '0', '1', '1', '1', '1', '1', NULL, '1', '1', '1', 'admin', '', 'ຊາຍ', '2000-05-19', '020 95321848', '', '', 'default.png', '1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '2026-11-02 10:08:51', '0', '0', '0', NULL, '0', '0', '0', '1'),
('2', '002', 'khola', '$2y$10$A7n6zV2iDvqv8sraZklC2.KtBpO8mY6tBU9QayjBd6dDw8r5aZaAG', 'ຜູ້ຈັດການ', '1', '0', '0', '0', '0', '1', '1', '1', '0', '1', NULL, '2', '1', '1', 'khola', '', 'ຊາຍ', '2003-09-19', '020 77354334', 'ນະຄອນຫຼວງ', '', 'user_1787132063_9592.png', '1', '1', '0', '0', '0', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '0', '1', '1', '1', '1', '0', '0', '1', '1', '2026-10-31 15:30:20', '1', '1', '1', '2027-02-20', '1', '1', '1', '1'),
('3', '003', 'la', '$2y$10$nh0R9y15EKwT0lrE1GZnmu9Rt8YSillY41fsufN0/SgbTk77Gut3u', 'ພະນັກງານ', '1', '0', '0', '0', '0', '0', '0', '0', '0', '0', NULL, '1', '2', '0', 'la', '', 'ຊາຍ', '2006-06-19', '020-00000000', 'ໂນນສະຫວ່າງ', '', 'user_1787132125_3279.png', '1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', NULL, '1', '0', '0', NULL, '0', '0', '0', '1'),
('20', 'some1', 'st', '$2y$10$VzVoAQaPy5QZMpEnq7gfSeCXHDyNYZE085vL2cJetHJxz9s/5AbBC', 'ຜູ້ບໍລິຫານ', '1', '0', '0', '0', '0', '1', '1', '1', '1', '1', NULL, '2', '2', '1', 'st', '', 'ຊາຍ', '2026-09-16', '02055949007', 'ວຽງຈັນ', '', 'default.png', '1', '1', '1', '0', '0', '1', '1', '1', '1', '0', '0', '0', '0', '0', '0', '0', '1', '1', '0', '0', '0', '0', '0', '0', '2026-11-02 10:00:06', '0', '1', '0', '2026-11-12', '1', '1', '0', '1');
-- rows `st_tbuser` 4

DROP TABLE IF EXISTS `st_units`;
CREATE TABLE `st_units` (
  `unit_id` int NOT NULL AUTO_INCREMENT,
  `unit_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `store_id` int NOT NULL DEFAULT '1',
  `branch_no` int DEFAULT NULL,
  PRIMARY KEY (`unit_id`),
  KEY `idx_branch_no` (`store_id`,`branch_no`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `st_units` (`unit_id`, `unit_name`, `description`, `created_at`, `store_id`, `branch_no`) VALUES
('5', 'ຂວດ', 'ຫົວໜ່ວຍບັນຈຸເປັນຂວດ (Bottle)', '2026-10-14 11:42:08', '1', '1'),
('6', 'ຫຼອດ', 'ຫົວໜ່ວຍບັນຈຸເປັນຫຼອດ (Tube)', '2026-10-14 11:42:08', '1', '2'),
('7', 'ຊອງ', 'ຫົວໜ່ວຍບັນຈຸເປັນຊອງ (Sachet)', '2026-10-14 11:42:08', '1', '3'),
('11', 'ປ໋ອງ', 'ຫົວໜ່ວຍບັນຈຸເປັນກະປ໋ອງ (Can)', '2026-10-14 15:07:38', '1', '4'),
('12', 'ກ່ອງ', 'ຫົວໜ່ວຍບັນຈຸເປັນກ່ອງ (Box)', '2026-10-14 15:07:38', '1', '5'),
('13', 'ແພັກ', 'ຫົວໜ່ວຍບັນຈຸເປັນແພັກ (Pack)', '2026-10-14 15:07:38', '1', '6'),
('14', 'ແກັດ', 'ຫົວໜ່ວຍບັນຈຸເປັນແກັດ ຫຼື ລັງ (Carton / Case)', '2026-10-14 15:07:38', '1', '7'),
('15', 'ຖົງ', 'ຫົວໜ່ວຍບັນຈຸເປັນຖົງ (Bag)', '2026-10-14 15:07:38', '1', '8'),
('16', 'ອັນ', 'ຫົວໜ່ວຍນັບເປັນອັນ (Piece)', '2026-10-14 15:07:38', '1', '9'),
('22', 'ແກ້ວ', '', '2026-10-20 16:38:58', '1', '11'),
('24', 'ກ້ອນ', 'ຫົວໜ່ວຍນັບເປັນກ້ອນ (Bar)', '2026-10-19 14:25:36', '1', '10'),
('26', 'ລັງ', NULL, '2026-10-22 15:04:27', '-1', '1');
-- rows `st_units` 12

DROP TABLE IF EXISTS `st_user_permission_switch_states`;
CREATE TABLE `st_user_permission_switch_states` (
  `user_id` int NOT NULL,
  `switch_key` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`,`switch_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `st_user_permission_switch_states` (`user_id`, `switch_key`, `is_enabled`) VALUES
('3', 'perm_accounting_add_3', '0'),
('3', 'perm_accounting_del_3', '0'),
('3', 'perm_accounting_edit_3', '0'),
('3', 'perm_accounting_view_3', '0'),
('3', 'perm_all_sales_view_3', '0'),
('3', 'perm_best_seller_view_3', '0'),
('3', 'perm_branches_add_3', '0'),
('3', 'perm_branches_del_3', '0'),
('3', 'perm_branches_edit_3', '0'),
('3', 'perm_branches_view_3', '0'),
('3', 'perm_categories_add_3', '0'),
('3', 'perm_categories_del_3', '0'),
('3', 'perm_categories_edit_3', '0'),
('3', 'perm_categories_view_3', '0'),
('3', 'perm_category_sales_3', '0'),
('3', 'perm_customers_add_3', '1'),
('3', 'perm_customers_del_3', '1'),
('3', 'perm_customers_edit_3', '1'),
('3', 'perm_customers_view_3', '1'),
('3', 'perm_daily_report_view_3', '0'),
('3', 'perm_dashboard_view_3', '0'),
('3', 'perm_database_view_3', '0'),
('3', 'perm_delete_bills_3', '0'),
('3', 'perm_exchange_rate_add_3', '0'),
('3', 'perm_exchange_rate_del_3', '0'),
('3', 'perm_exchange_rate_edit_3', '0'),
('3', 'perm_exchange_rate_view_3', '0'),
('3', 'perm_expiry_add_3', '0'),
('3', 'perm_expiry_del_3', '0'),
('3', 'perm_expiry_edit_3', '0'),
('3', 'perm_expiry_view_3', '0'),
('3', 'perm_financial_view_3', '0'),
('3', 'perm_import_list_del_3', '0'),
('3', 'perm_import_list_edit_3', '0'),
('3', 'perm_import_list_view_3', '0'),
('3', 'perm_import_stock_add_3', '0'),
('3', 'perm_import_stock_view_3', '0'),
('3', 'perm_item_sales_del_3', '0'),
('3', 'perm_item_sales_edit_3', '0'),
('3', 'perm_item_sales_view_3', '1'),
('3', 'perm_loyalty_add_3', '0'),
('3', 'perm_loyalty_del_3', '0'),
('3', 'perm_loyalty_edit_3', '0'),
('3', 'perm_loyalty_view_3', '0'),
('3', 'perm_permissions_edit_3', '0'),
('3', 'perm_permissions_view_3', '0'),
('3', 'perm_price_adjustment_add_3', '0'),
('3', 'perm_price_adjustment_del_3', '0'),
('3', 'perm_price_adjustment_edit_3', '0'),
('3', 'perm_price_adjustment_view_3', '0'),
('3', 'perm_printers_add_3', '0'),
('3', 'perm_printers_del_3', '0'),
('3', 'perm_printers_edit_3', '0'),
('3', 'perm_printers_view_3', '0'),
('3', 'perm_print_barcode_view_3', '0'),
('3', 'perm_products_add_3', '0'),
('3', 'perm_products_del_3', '0'),
('3', 'perm_products_edit_3', '0'),
('3', 'perm_products_view_3', '0'),
('3', 'perm_profit_cost_view_3', '0'),
('3', 'perm_promotions_add_3', '0'),
('3', 'perm_promotions_del_3', '0'),
('3', 'perm_promotions_edit_3', '0'),
('3', 'perm_promotions_view_3', '0'),
('3', 'perm_sale_add_3', '1'),
('3', 'perm_sale_del_3', '0'),
('3', 'perm_sale_edit_3', '0'),
('3', 'perm_sale_view_3', '1'),
('3', 'perm_stock_check_add_3', '0'),
('3', 'perm_stock_check_del_3', '0'),
('3', 'perm_stock_check_edit_3', '0'),
('3', 'perm_stock_check_view_3', '0'),
('3', 'perm_stock_count_edit_3', '0'),
('3', 'perm_stock_count_view_3', '0'),
('3', 'perm_stock_transfer_add_3', '0'),
('3', 'perm_stock_transfer_view_3', '0'),
('3', 'perm_stores_edit_3', '0'),
('3', 'perm_stores_view_3', '0'),
('3', 'perm_suppliers_add_3', '0'),
('3', 'perm_suppliers_del_3', '0'),
('3', 'perm_suppliers_edit_3', '0'),
('3', 'perm_suppliers_view_3', '0'),
('3', 'perm_transfer_history_view_3', '0'),
('3', 'perm_units_add_3', '0'),
('3', 'perm_units_del_3', '0'),
('3', 'perm_units_edit_3', '0'),
('3', 'perm_units_view_3', '0'),
('3', 'perm_users_add_3', '0'),
('3', 'perm_users_del_3', '0'),
('3', 'perm_users_edit_3', '0'),
('3', 'perm_users_view_3', '0'),
('11', 'perm_stock_view_11', '0'),
('12', 'perm_products_view_12', '0'),
('14', 'perm_stock_view_14', '0'),
('20', 'perm_accounting_add_20', '1'),
('20', 'perm_accounting_del_20', '1'),
('20', 'perm_accounting_edit_20', '1'),
('20', 'perm_accounting_view_20', '1'),
('20', 'perm_all_sales_view_20', '1'),
('20', 'perm_best_seller_view_20', '1'),
('20', 'perm_branches_add_20', '1'),
('20', 'perm_branches_del_20', '1'),
('20', 'perm_branches_edit_20', '1'),
('20', 'perm_branches_view_20', '1'),
('20', 'perm_categories_add_20', '1'),
('20', 'perm_categories_del_20', '1'),
('20', 'perm_categories_edit_20', '1'),
('20', 'perm_categories_view_20', '1'),
('20', 'perm_category_sales_20', '1'),
('20', 'perm_customers_add_20', '1'),
('20', 'perm_customers_del_20', '1'),
('20', 'perm_customers_edit_20', '1'),
('20', 'perm_customers_view_20', '1'),
('20', 'perm_daily_report_view_20', '1'),
('20', 'perm_dashboard_view_20', '1'),
('20', 'perm_database_view_20', '0'),
('20', 'perm_delete_bills_20', '1'),
('20', 'perm_exchange_rate_add_20', '1'),
('20', 'perm_exchange_rate_del_20', '1'),
('20', 'perm_exchange_rate_edit_20', '1'),
('20', 'perm_exchange_rate_view_20', '1'),
('20', 'perm_financial_view_20', '1'),
('20', 'perm_import_list_del_20', '1'),
('20', 'perm_import_list_edit_20', '1'),
('20', 'perm_import_list_view_20', '1'),
('20', 'perm_import_stock_add_20', '1'),
('20', 'perm_import_stock_view_20', '1'),
('20', 'perm_item_sales_del_20', '1'),
('20', 'perm_item_sales_edit_20', '1'),
('20', 'perm_item_sales_view_20', '1'),
('20', 'perm_loyalty_edit_20', '1'),
('20', 'perm_loyalty_view_20', '1'),
('20', 'perm_permissions_edit_20', '1'),
('20', 'perm_permissions_view_20', '1'),
('20', 'perm_price_adjustment_add_20', '1'),
('20', 'perm_price_adjustment_del_20', '1'),
('20', 'perm_price_adjustment_edit_20', '1'),
('20', 'perm_price_adjustment_view_20', '1'),
('20', 'perm_printers_add_20', '1'),
('20', 'perm_printers_del_20', '1'),
('20', 'perm_printers_edit_20', '1'),
('20', 'perm_printers_view_20', '1'),
('20', 'perm_print_barcode_view_20', '1'),
('20', 'perm_products_add_20', '1'),
('20', 'perm_products_del_20', '1'),
('20', 'perm_products_edit_20', '1'),
('20', 'perm_products_view_20', '1'),
('20', 'perm_profit_cost_view_20', '1'),
('20', 'perm_promotions_add_20', '1'),
('20', 'perm_promotions_del_20', '1'),
('20', 'perm_promotions_edit_20', '1'),
('20', 'perm_promotions_view_20', '1'),
('20', 'perm_sale_add_20', '1'),
('20', 'perm_sale_del_20', '1'),
('20', 'perm_sale_edit_20', '1'),
('20', 'perm_sale_view_20', '1'),
('20', 'perm_stock_transfer_add_20', '1'),
('20', 'perm_stock_transfer_view_20', '1'),
('20', 'perm_stores_edit_20', '1'),
('20', 'perm_stores_view_20', '1'),
('20', 'perm_suppliers_add_20', '1'),
('20', 'perm_suppliers_del_20', '1'),
('20', 'perm_suppliers_edit_20', '1'),
('20', 'perm_suppliers_view_20', '1'),
('20', 'perm_transfer_history_view_20', '1'),
('20', 'perm_units_add_20', '1'),
('20', 'perm_units_del_20', '1'),
('20', 'perm_units_edit_20', '1'),
('20', 'perm_units_view_20', '1'),
('20', 'perm_users_add_20', '1'),
('20', 'perm_users_del_20', '1'),
('20', 'perm_users_edit_20', '1'),
('20', 'perm_users_view_20', '1');
-- rows `st_user_permission_switch_states` 173

DROP TABLE IF EXISTS `st_zones`;
CREATE TABLE `st_zones` (
  `zone_id` int NOT NULL AUTO_INCREMENT,
  `zone_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `store_id` int NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`zone_id`),
  KEY `idx_zone_store` (`store_id`,`zone_name`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- rows `st_zones` 0

