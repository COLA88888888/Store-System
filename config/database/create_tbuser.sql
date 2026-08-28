USE minipos;

CREATE TABLE IF NOT EXISTS `tbuser` (
  `Id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_code` VARCHAR(50) DEFAULT NULL,
  `username` VARCHAR(100) NOT NULL,
  `fname` VARCHAR(100) DEFAULT NULL,
  `lname` VARCHAR(100) DEFAULT NULL,
  `gender` VARCHAR(20) DEFAULT NULL,
  `dob` DATE DEFAULT NULL,
  `tel` VARCHAR(30) DEFAULT NULL,
  `password` VARCHAR(255) DEFAULT NULL,
  `status` VARCHAR(50) DEFAULT 'employee',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DELETE FROM `tbuser` WHERE `username` = 'admin';

INSERT INTO `tbuser` (Id, user_code, username, password, status, store_id, dashboard, sale, item_sales, stock, categories, products, import_stock, import_list, report, daily_report, all_sales, best_seller, profit_cost, financial, category_sales, delete_bills, accounting, setup, users, permissions, branches, edit, customers, `database`, stock_transfer, transfer_history) VALUES (1, '001', 'admin', SHA2('123456', 256), 'Admin', 1, 1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1);

SELECT Id, username, status, store_id FROM `tbuser`;
