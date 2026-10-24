-- Backup ຂໍ້ມູນໜີ້ລູກຄ້າ ກ່ອນລຶບໂມດູນ ຕິດໜີ້ (2026-10-23 05:53:36)
-- Tables: st_customer_debts, st_customer_debt_payments + st_tbuser.customer_debt

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `st_customer_debts`;
CREATE TABLE `st_customer_debts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `store_id` int NOT NULL DEFAULT '1',
  `customer_id` int NOT NULL,
  `customer_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `invoice_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bill_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `deposit_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'unpaid',
  `due_date` date DEFAULT NULL,
  `note` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_debts_store_customer` (`store_id`,`customer_id`),
  KEY `idx_debts_invoice` (`invoice_number`),
  KEY `idx_debts_status` (`store_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `st_customer_debts` (`id`,`store_id`,`customer_id`,`customer_name`,`invoice_number`,`bill_amount`,`deposit_amount`,`amount`,`paid_amount`,`balance`,`status`,`due_date`,`note`,`created_by`,`created_at`) VALUES ('1','7','1','ໂຄລ່າ ສີນົນທາ','20261006-0002','42000.00','0.00','42000.00','42000.00','0.00','paid',NULL,NULL,'admin','2026-10-06 13:56:19');
INSERT INTO `st_customer_debts` (`id`,`store_id`,`customer_id`,`customer_name`,`invoice_number`,`bill_amount`,`deposit_amount`,`amount`,`paid_amount`,`balance`,`status`,`due_date`,`note`,`created_by`,`created_at`) VALUES ('6','7','1','ໂຄລ່າ ສີນົນທາ','1-20261006-0003','54000.00','0.00','54000.00','54000.00','0.00','paid',NULL,NULL,'admin','2026-10-06 15:12:33');
INSERT INTO `st_customer_debts` (`id`,`store_id`,`customer_id`,`customer_name`,`invoice_number`,`bill_amount`,`deposit_amount`,`amount`,`paid_amount`,`balance`,`status`,`due_date`,`note`,`created_by`,`created_at`) VALUES ('11','7','1','ໂຄລ່າ ສີນົນທາ','1-20261006-0004','50000.00','0.00','50000.00','50000.00','0.00','paid','2026-10-13',NULL,'admin','2026-10-06 15:36:04');
INSERT INTO `st_customer_debts` (`id`,`store_id`,`customer_id`,`customer_name`,`invoice_number`,`bill_amount`,`deposit_amount`,`amount`,`paid_amount`,`balance`,`status`,`due_date`,`note`,`created_by`,`created_at`) VALUES ('18','7','1','ໂຄລ່າ ສີນົນທາ','1-20261013-0001','38000.00','0.00','38000.00','38000.00','0.00','paid',NULL,NULL,'admin','2026-10-13 10:38:29');

DROP TABLE IF EXISTS `st_customer_debt_payments`;
CREATE TABLE `st_customer_debt_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `debt_id` int NOT NULL,
  `store_id` int NOT NULL DEFAULT '1',
  `customer_id` int NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `payment_method` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ເງິນສົດ',
  `bank_account_id` int DEFAULT NULL,
  `bank_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `note` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `received_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `paid_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_debt_pay_debt` (`debt_id`),
  KEY `idx_debt_pay_store_date` (`store_id`,`paid_at`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `st_customer_debt_payments` (`id`,`debt_id`,`store_id`,`customer_id`,`amount`,`payment_method`,`bank_account_id`,`bank_name`,`note`,`received_by`,`paid_at`) VALUES ('4','1','7','1','42000.00','ເງິນສົດ',NULL,NULL,NULL,'admin','2026-10-06 15:08:09');
INSERT INTO `st_customer_debt_payments` (`id`,`debt_id`,`store_id`,`customer_id`,`amount`,`payment_method`,`bank_account_id`,`bank_name`,`note`,`received_by`,`paid_at`) VALUES ('9','6','7','1','30000.00','ໂອນເງິນ / QR','1','BCEL One',NULL,'admin','2026-10-06 15:14:38');
INSERT INTO `st_customer_debt_payments` (`id`,`debt_id`,`store_id`,`customer_id`,`amount`,`payment_method`,`bank_account_id`,`bank_name`,`note`,`received_by`,`paid_at`) VALUES ('10','6','7','1','24000.00','ເງິນສົດ',NULL,NULL,NULL,'admin','2026-10-06 15:15:14');
INSERT INTO `st_customer_debt_payments` (`id`,`debt_id`,`store_id`,`customer_id`,`amount`,`payment_method`,`bank_account_id`,`bank_name`,`note`,`received_by`,`paid_at`) VALUES ('18','11','7','1','50000.00','ໂອນເງິນ / QR','1','BCEL One',NULL,'admin','2026-10-13 10:08:04');
INSERT INTO `st_customer_debt_payments` (`id`,`debt_id`,`store_id`,`customer_id`,`amount`,`payment_method`,`bank_account_id`,`bank_name`,`note`,`received_by`,`paid_at`) VALUES ('19','18','7','1','38000.00','ເງິນສົດ',NULL,NULL,NULL,'admin','2026-10-13 11:08:59');

-- st_tbuser.customer_debt (ສິດ) ກ່ອນລຶບ:
-- UPDATE st_tbuser SET customer_debt = 0 WHERE Id = 1;
-- UPDATE st_tbuser SET customer_debt = 0 WHERE Id = 2;
-- UPDATE st_tbuser SET customer_debt = 0 WHERE Id = 3;
-- UPDATE st_tbuser SET customer_debt = 0 WHERE Id = 20;
