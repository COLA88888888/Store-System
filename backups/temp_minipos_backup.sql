-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: minipos
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `action` varchar(255) NOT NULL,
  `detail` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,'ເຂົ້າສູ່ລະບົບ','ຊື່ຜູ້ໃຊ້: admin','2026-08-28 15:11:27'),(2,'ເຂົ້າສູ່ລະບົບ','ຊື່ຜູ້ໃຊ້: admin','2026-08-28 15:12:01'),(3,'ເຂົ້າສູ່ລະບົບ','ຊື່ຜູ້ໃຊ້: admin','2026-08-28 15:15:19'),(4,'ເຂົ້າສູ່ລະບົບ','ຊື່ຜູ້ໃຊ້: admin','2026-08-28 15:19:14'),(5,'ເຂົ້າສູ່ລະບົບ','ຊື່ຜູ້ໃຊ້: admin','2026-08-28 15:19:52'),(6,'ອອກຈາກລະບົບ','ຜູ້ໃຊ້: admin','2026-08-28 15:21:15'),(7,'ເຂົ້າສູ່ລະບົບ','ຊື່ຜູ້ໃຊ້: admin','2026-08-28 15:21:25'),(8,'ເຂົ້າສູ່ລະບົບ','ຊື່ຜູ້ໃຊ້: admin','2026-08-28 15:25:39');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_accounts`
--

DROP TABLE IF EXISTS `bank_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_accounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bank_name` varchar(100) NOT NULL,
  `account_number` varchar(100) NOT NULL,
  `account_name` varchar(150) NOT NULL,
  `bank_code` varchar(50) DEFAULT 'BCEL',
  `bank_logo` varchar(255) DEFAULT NULL,
  `qr_code_img` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_accounts`
--

LOCK TABLES `bank_accounts` WRITE;
/*!40000 ALTER TABLE `bank_accounts` DISABLE KEYS */;
INSERT INTO `bank_accounts` VALUES (61,'BCEL One','123456789','khola','BCEL ONE','bank_1787291746_575.png','qr_bank_1787291746_127.jpeg',1,'2026-08-21 12:55:46'),(62,'LDB Bank','466468753356','la','LDB BANK','bank_1787292079_144.png','qr_bank_1787292079_985.jpeg',1,'2026-08-21 13:01:19'),(63,'JDB Bank','546846546745','dev','JDB BANK','bank_1787292104_963.png','qr_bank_1787292104_574.jpeg',1,'2026-08-21 13:01:44');
/*!40000 ALTER TABLE `bank_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(150) NOT NULL,
  `description` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `store_id` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'ເຄື່ອງດື່ມ','','2026-08-22 17:02:39',1),(2,'ຢາສູບ','','2026-08-23 13:26:48',1),(3,'ເຄື່ອງສຳອາງ','','2026-08-23 13:26:54',1),(4,'ເບຍ','','2026-08-23 13:27:04',1),(5,'ສີນຄ້າ ບໍ່ມີບາໂຄດ','','2026-08-23 13:27:19',1),(6,'ເຄື່ອງໃຊ້ຫ້ອງການ','','2026-08-23 13:27:30',1),(7,'ເຄື່ອງປຸງ&ໃຊ້ຄົວເຮືອນ','','2026-08-23 13:27:47',1),(8,'ນົມ','','2026-08-23 13:27:59',1),(9,'ເຂົ້າໜົມ','','2026-08-23 13:28:19',1),(10,'ຫມີ່','','2026-08-23 13:50:48',1);
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `customer_id` int NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(50) NOT NULL,
  `customer_name` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `member_card` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text,
  `notes` text,
  `store_id` int DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `points` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `customer_code` (`customer_code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `import_details`
--

DROP TABLE IF EXISTS `import_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `import_details` (
  `import_detail_id` int NOT NULL AUTO_INCREMENT,
  `import_id` int NOT NULL,
  `product_id` int NOT NULL,
  `unit_name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `multiplier` int DEFAULT '1',
  `quantity` int NOT NULL DEFAULT '1',
  `total_base_qty` int NOT NULL DEFAULT '1',
  `cost_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_cost` decimal(15,2) NOT NULL DEFAULT '0.00',
  `expiry_date` date DEFAULT NULL,
  PRIMARY KEY (`import_detail_id`),
  KEY `import_id` (`import_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `import_details`
--

LOCK TABLES `import_details` WRITE;
/*!40000 ALTER TABLE `import_details` DISABLE KEYS */;
INSERT INTO `import_details` VALUES (25,10,10001,'ແກ້ວ',1,100,100,8000.00,800000.00,NULL),(26,11,10009,'ແກ້ວ',1,100,100,5000.00,500000.00,NULL),(27,11,80002,'ຂວດ',1,100,100,6000.00,600000.00,NULL),(28,11,100016,'ຫໍ່',1,100,100,5000.00,500000.00,NULL),(29,11,20003,'ຊອງ',1,100,100,20000.00,2000000.00,NULL),(30,11,10005,'ຕຸກ',1,100,100,8000.00,800000.00,NULL),(31,11,10010,'ຕຸກ',1,100,100,8000.00,800000.00,NULL),(32,11,100014,'ຫໍ່',1,100,100,15000.00,1500000.00,NULL),(33,11,100015,'ຫໍ່',1,100,100,15000.00,1500000.00,NULL),(34,11,100013,'ຫໍ່',1,100,100,3000.00,300000.00,NULL),(35,11,60008,'ຕັບ',1,100,100,60000.00,6000000.00,NULL),(36,11,30007,'ຊອງ',1,100,100,20000.00,2000000.00,NULL),(37,11,10001,'ແກ້ວ',1,100,100,8000.00,800000.00,NULL),(38,11,40012,'ປ໋ອງສັ້ນ',1,100,100,8000.00,800000.00,NULL),(39,11,40011,'ແກ້ວ',1,100,100,12000.00,1200000.00,NULL),(40,11,90004,'ຖົງໃຫຍ່',1,100,100,25000.00,2500000.00,NULL),(41,11,70006,'ຖົງໃຫຍ່',1,100,100,25000.00,2500000.00,NULL);
/*!40000 ALTER TABLE `import_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `imports`
--

DROP TABLE IF EXISTS `imports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `imports` (
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `imports`
--

LOCK TABLES `imports` WRITE;
/*!40000 ALTER TABLE `imports` DISABLE KEYS */;
INSERT INTO `imports` VALUES (10,'000001','',800000.00,1,'2026-08-22 17:03:45','',1),(11,'000011','',24300000.00,1,'2026-08-23 13:56:43','',1);
/*!40000 ALTER TABLE `imports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `price_adjustments`
--

DROP TABLE IF EXISTS `price_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `price_adjustments` (
  `adjust_id` int NOT NULL AUTO_INCREMENT,
  `adjust_date` date DEFAULT NULL,
  `adjust_time` time DEFAULT NULL,
  `target_mode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'product',
  `category_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_price_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'price',
  `calc_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'set',
  `adjust_value` decimal(12,2) DEFAULT '0.00',
  `old_bprice` decimal(12,2) DEFAULT '0.00',
  `old_price` decimal(12,2) DEFAULT '0.00',
  `new_bprice` decimal(12,2) DEFAULT '0.00',
  `new_price` decimal(12,2) DEFAULT '0.00',
  `diff_amount` decimal(12,2) DEFAULT '0.00',
  `adjust_mode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adjust_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `remark` text COLLATE utf8mb4_unicode_ci,
  `branch_id` int DEFAULT '1',
  PRIMARY KEY (`adjust_id`),
  KEY `idx_price_adj_branch` (`branch_id`,`adjust_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `price_adjustments`
--

LOCK TABLES `price_adjustments` WRITE;
/*!40000 ALTER TABLE `price_adjustments` DISABLE KEYS */;
/*!40000 ALTER TABLE `price_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_batches`
--

DROP TABLE IF EXISTS `product_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_batches` (
  `batch_id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `import_detail_id` int DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `initial_qty` int NOT NULL DEFAULT '0',
  `quantity` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`batch_id`),
  KEY `product_id` (`product_id`),
  KEY `expiry_date` (`expiry_date`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_batches`
--

LOCK TABLES `product_batches` WRITE;
/*!40000 ALTER TABLE `product_batches` DISABLE KEYS */;
INSERT INTO `product_batches` VALUES (25,10001,25,NULL,100,100,'2026-08-22 17:03:45'),(26,10009,26,NULL,100,100,'2026-08-23 13:56:43'),(27,80002,27,NULL,100,100,'2026-08-23 13:56:43'),(28,100016,28,NULL,100,100,'2026-08-23 13:56:43'),(29,20003,29,NULL,100,100,'2026-08-23 13:56:43'),(30,10005,30,NULL,100,100,'2026-08-23 13:56:43'),(31,10010,31,NULL,100,100,'2026-08-23 13:56:43'),(32,100014,32,NULL,100,100,'2026-08-23 13:56:43'),(33,100015,33,NULL,100,100,'2026-08-23 13:56:43'),(34,100013,34,NULL,100,100,'2026-08-23 13:56:43'),(35,60008,35,NULL,100,100,'2026-08-23 13:56:43'),(36,30007,36,NULL,100,100,'2026-08-23 13:56:43'),(37,10001,37,NULL,100,100,'2026-08-23 13:56:43'),(38,40012,38,NULL,100,100,'2026-08-23 13:56:43'),(39,40011,39,NULL,100,100,'2026-08-23 13:56:43'),(40,90004,40,NULL,100,100,'2026-08-23 13:56:43'),(41,70006,41,NULL,100,100,'2026-08-23 13:56:43');
/*!40000 ALTER TABLE `product_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_units`
--

DROP TABLE IF EXISTS `product_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_units` (
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_units`
--

LOCK TABLES `product_units` WRITE;
/*!40000 ALTER TABLE `product_units` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `code1` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL DEFAULT '0',
  `barcode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_id` int NOT NULL,
  `bprice` decimal(10,2) NOT NULL DEFAULT '0.00',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `qty` int NOT NULL DEFAULT '0',
  `min_qty` int NOT NULL DEFAULT '5',
  `unit` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `img_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cook` tinyint(1) NOT NULL DEFAULT '0',
  `cut_qty` tinyint(1) NOT NULL DEFAULT '1',
  `branch_id` int DEFAULT '1',
  `store_id` int NOT NULL DEFAULT '1',
  `unit_id` int DEFAULT NULL,
  PRIMARY KEY (`code1`),
  UNIQUE KEY `uk_product_store` (`product_id`,`store_id`),
  KEY `idx_products_category_id` (`category_id`),
  KEY `idx_products_barcode` (`barcode`),
  KEY `idx_products_store` (`store_id`,`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (34,10001,'2048466388643','ເບຍລາວ',1,8000.00,15000.00,190,5,'ແກ້ວ','prod_1787393007_2718.png',0,1,1,1,NULL),(35,80002,'2073203999158','ນົມເລັກຕາຊອຍ',8,6000.00,12000.00,100,5,'ຂວດ','prod_1787466585_5780.jpg',0,1,1,1,NULL),(36,20003,'2033067720862','ຢາສູບ PINE CHANG 7',2,20000.00,35000.00,100,5,'ຊອງ','prod_1787466721_3056.jfif',0,1,1,1,NULL),(37,90004,'2075318035929','ເລ',9,25000.00,30000.00,100,5,'ຖົງໃຫຍ່','prod_1787466775_5260.png',0,1,1,1,NULL),(38,10005,'2078263953966','ສະຕິງຂວດເຫຼືອງ',1,8000.00,15000.00,100,5,'ຕຸກ','prod_1787466828_4118.png',0,1,1,1,NULL),(39,70006,'2000458862838','ແປງນົວ',7,25000.00,30000.00,100,5,'ຖົງໃຫຍ່','prod_1787466907_7369.jfif',0,1,1,1,NULL),(40,30007,'2010319654595','ເຊລຳ clear nose',3,20000.00,25000.00,100,5,'ຊອງ','prod_1787467112_6955.avif',0,1,1,1,NULL),(41,60008,'2026140885572','ເຈ້ຍ A 4',6,60000.00,80000.00,100,5,'ຕັບ','prod_1787467332_6010.jpg',0,1,1,1,NULL),(42,10009,'2021360299994','M 150',1,5000.00,8000.00,100,5,'ແກ້ວ','prod_1787467373_4461.jpg',0,1,1,1,NULL),(43,10010,'2057366619368','ສະຕິງຂວດເເດງ',1,8000.00,15000.00,100,5,'ຕຸກ','prod_1787467419_8307.png',0,1,1,1,NULL),(44,40011,'2077782445808','ເບຍໄຮນິເກັນ',4,12000.00,18000.00,100,5,'ແກ້ວ','prod_1787467474_7877.png',0,1,1,1,NULL),(45,40012,'2004456587038','ເບຍລາວ',4,8000.00,12000.00,100,5,'ປ໋ອງສັ້ນ','prod_1787467525_3699.jfif',0,1,1,1,NULL),(46,100013,'2047888140464','ຫມີ່ໄວໄວ',10,3000.00,5000.00,100,5,'ຫໍ່','prod_1787467890_5597.png',0,1,1,1,NULL),(47,100014,'2011578918404','ຫມີ່ເກົາຫລີຫໍ່ສີດຳ',10,15000.00,25000.00,100,5,'ຫໍ່','prod_1787467928_9909.jpg',0,1,1,1,NULL),(48,100015,'2094690941642','ຫມີ່ເກົາຫລີຫໍ່ສີບົວ',10,15000.00,25000.00,100,5,'ຫໍ່','prod_1787467984_3359.jpg',0,1,1,1,NULL),(49,100016,'2094815295841','ມ່າມ່າຫໍ່ສີສົ້ມ',10,5000.00,8000.00,100,5,'ຫໍ່','prod_1787468063_1744.jpg',0,1,1,1,NULL);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promotions`
--

DROP TABLE IF EXISTS `promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `promo_name` varchar(255) NOT NULL,
  `promo_type` varchar(50) DEFAULT 'discount',
  `discount_type` varchar(20) DEFAULT 'percentage',
  `discount_value` decimal(12,2) DEFAULT '0.00',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `min_qty` int DEFAULT '0',
  `min_amount` decimal(12,2) DEFAULT '0.00',
  `target_type` varchar(50) DEFAULT 'all',
  `target_name` varchar(255) DEFAULT 'ທຸກສິນຄ້າ',
  `status` tinyint(1) DEFAULT '1',
  `branch_id` int DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promotions`
--

LOCK TABLES `promotions` WRITE;
/*!40000 ALTER TABLE `promotions` DISABLE KEYS */;
/*!40000 ALTER TABLE `promotions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales` (
  `sale_id` int NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL,
  `sold_by` int DEFAULT NULL,
  `customer_id` int DEFAULT NULL,
  `customer_name` varchar(150) DEFAULT 'ລູກຄ້າທົ່ວໄປ',
  `subtotal` decimal(12,2) DEFAULT '0.00',
  `discount_amount` decimal(12,2) DEFAULT '0.00',
  `vat_amount` decimal(12,2) DEFAULT '0.00',
  `total_amount` decimal(12,2) DEFAULT '0.00',
  `total_profit` decimal(12,2) DEFAULT '0.00',
  `cash_received` decimal(12,2) DEFAULT '0.00',
  `change_amount` decimal(12,2) DEFAULT '0.00',
  `payment_type` varchar(50) DEFAULT 'ເງິນສົດ',
  `status` varchar(20) DEFAULT 'SUCCESS',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `store_id` int NOT NULL DEFAULT '1',
  `tax_type` varchar(20) DEFAULT 'inclusive',
  `vat_rate` decimal(5,2) DEFAULT '7.00',
  `bank_account_id` int DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`sale_id`),
  UNIQUE KEY `inv_num` (`invoice_number`)
) ENGINE=InnoDB AUTO_INCREMENT=109 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
INSERT INTO `sales` VALUES (107,'20260822-0001',1,0,'ລູກຄ້າທົ່ວໄປ',150000.00,0.00,0.00,150000.00,0.00,150000.00,0.00,'ເງິນສົດ','SUCCESS','2026-08-22 17:17:58',1,'none',0.00,NULL,NULL),(108,'20260823-0001',1,0,'ລູກຄ້າທົ່ວໄປ',296000.00,0.00,0.00,296000.00,0.00,296000.00,0.00,'ໂອນເງິນ / QR','SUCCESS','2026-08-23 16:18:27',1,'none',0.00,61,'BCEL One');
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_transfer_details`
--

DROP TABLE IF EXISTS `stock_transfer_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_transfer_details` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transfer_id` int NOT NULL,
  `product_id` int NOT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `barcode` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qty` int NOT NULL DEFAULT '0',
  `unit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bprice` decimal(10,2) DEFAULT '0.00',
  `price` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `idx_transfer_id` (`transfer_id`),
  KEY `idx_product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_transfer_details`
--

LOCK TABLES `stock_transfer_details` WRITE;
/*!40000 ALTER TABLE `stock_transfer_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_transfer_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_transfers`
--

DROP TABLE IF EXISTS `stock_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_transfers` (
  `transfer_id` int NOT NULL AUTO_INCREMENT,
  `transfer_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_store_id` int NOT NULL,
  `to_store_id` int NOT NULL,
  `transfer_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'completed',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`transfer_id`),
  KEY `idx_from_store` (`from_store_id`),
  KEY `idx_to_store` (`to_store_id`),
  KEY `idx_transfers_stores` (`from_store_id`,`to_store_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_transfers`
--

LOCK TABLES `stock_transfers` WRITE;
/*!40000 ALTER TABLE `stock_transfers` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_transfers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_settings` (
  `setting_id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'vat_rate','0','2026-08-28 12:18:06'),(2,'expiry_warning_days','30','2026-08-28 12:18:06'),(3,'loyalty_enabled','0','2026-08-28 12:18:06'),(4,'loyalty_spend_amount','100000','2026-08-28 12:18:06'),(5,'loyalty_points_earned','1','2026-08-28 12:18:06');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbcompanyinfo`
--

DROP TABLE IF EXISTS `tbcompanyinfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbcompanyinfo` (
  `com_id` int NOT NULL AUTO_INCREMENT,
  `com_name_la` varchar(255) DEFAULT 'Mini POS Store',
  `com_address` text,
  `com_tel` varchar(50) DEFAULT NULL,
  `barcode` text,
  `img_url` varchar(255) DEFAULT 'logo.png',
  `qr_img` varchar(255) DEFAULT NULL,
  `license_start_date` date DEFAULT '2026-01-01',
  `license_expire_date` date DEFAULT '2026-12-31',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `vat_percent` decimal(5,2) DEFAULT '7.00',
  `tax_type` varchar(20) DEFAULT 'inclusive',
  `branch_id` int DEFAULT '1',
  `store_id` int DEFAULT '1',
  `com_email` varchar(100) DEFAULT NULL,
  `tax_id` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`com_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbcompanyinfo`
--

LOCK TABLES `tbcompanyinfo` WRITE;
/*!40000 ALTER TABLE `tbcompanyinfo` DISABLE KEYS */;
INSERT INTO `tbcompanyinfo` VALUES (1,'','','','ຂອບໃຈທີ່ມາອຸດໜູນ, ໂອກາດໜ້າເຊີນໃໝ່!','logo.png',NULL,'2026-01-01','2026-12-31','2026-08-28 15:25:16',7.00,'inclusive',1,1,NULL,NULL);
/*!40000 ALTER TABLE `tbcompanyinfo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbexchange`
--

DROP TABLE IF EXISTS `tbexchange`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbexchange` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `ex_date` date DEFAULT NULL,
  `ex_time` time DEFAULT NULL,
  `ex_kip_bath` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `ex_kip_us` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `ex_status` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `ex_userlogin` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `branch_id` int DEFAULT '1',
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbexchange`
--

LOCK TABLES `tbexchange` WRITE;
/*!40000 ALTER TABLE `tbexchange` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbexchange` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_printer`
--

DROP TABLE IF EXISTS `tbl_printer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_printer` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('ip','browser') COLLATE utf8mb4_unicode_ci DEFAULT 'ip',
  `status` tinyint(1) DEFAULT '1',
  `branch_id` int DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_printer`
--

LOCK TABLES `tbl_printer` WRITE;
/*!40000 ALTER TABLE `tbl_printer` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbl_printer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbsale_save`
--

DROP TABLE IF EXISTS `tbsale_save`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbsale_save` (
  `sale_save_id` int NOT NULL AUTO_INCREMENT,
  `sale_save_bill` varchar(50) NOT NULL,
  `sale_date` date DEFAULT NULL,
  `sale_time` time DEFAULT NULL,
  `user_receive` int DEFAULT NULL,
  `customer_id` int DEFAULT NULL,
  `customer_name` varchar(150) DEFAULT 'ລູກຄ້າທົ່ວໄປ',
  `ip_address` varchar(50) DEFAULT NULL,
  `sale_qty` decimal(10,2) DEFAULT '0.00',
  `sale_amount` decimal(12,2) DEFAULT '0.00',
  `sale_discount_bill` decimal(12,2) DEFAULT '0.00',
  `sale_barlance` decimal(12,2) DEFAULT '0.00',
  `sale_pay` decimal(12,2) DEFAULT '0.00',
  `sale_return` decimal(12,2) DEFAULT '0.00',
  `type_pay` varchar(50) DEFAULT 'ເງິນສົດ',
  `sale_status` varchar(20) DEFAULT 'SUCCESS',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `store_id` int NOT NULL DEFAULT '1',
  `bank_account_id` int DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `points_earned` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`sale_save_id`),
  KEY `sale_save_bill` (`sale_save_bill`),
  KEY `sale_date` (`sale_date`),
  KEY `user_receive` (`user_receive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbsale_save`
--

LOCK TABLES `tbsale_save` WRITE;
/*!40000 ALTER TABLE `tbsale_save` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbsale_save` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbsale_save_detail`
--

DROP TABLE IF EXISTS `tbsale_save_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbsale_save_detail` (
  `save_id` int NOT NULL AUTO_INCREMENT,
  `save_bill` varchar(50) NOT NULL,
  `save_date` date DEFAULT NULL,
  `save_time` time DEFAULT NULL,
  `save_proid` int DEFAULT NULL,
  `save_proname` varchar(255) DEFAULT NULL,
  `save_qty` decimal(10,2) DEFAULT '1.00',
  `save_price` decimal(12,2) DEFAULT '0.00',
  `cost_price` decimal(12,2) DEFAULT '0.00',
  `save_money` decimal(12,2) DEFAULT '0.00',
  `save_net_money` decimal(12,2) DEFAULT '0.00',
  `user_receives` int DEFAULT NULL,
  PRIMARY KEY (`save_id`),
  KEY `save_bill` (`save_bill`),
  KEY `save_proid` (`save_proid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbsale_save_detail`
--

LOCK TABLES `tbsale_save_detail` WRITE;
/*!40000 ALTER TABLE `tbsale_save_detail` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbsale_save_detail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbstore`
--

DROP TABLE IF EXISTS `tbstore`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbstore` (
  `store_id` int NOT NULL AUTO_INCREMENT,
  `store_code` varchar(50) NOT NULL,
  `store_name` varchar(150) NOT NULL,
  `is_main` tinyint(1) DEFAULT '0',
  `address` text,
  `tel` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`store_id`),
  UNIQUE KEY `store_code` (`store_code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbstore`
--

LOCK TABLES `tbstore` WRITE;
/*!40000 ALTER TABLE `tbstore` DISABLE KEYS */;
INSERT INTO `tbstore` VALUES (1,'1','ສາຂາໃຫຍ່ ວຽງຈັນ (HQ)',1,'ນະຄອນຫຼວງວຽງຈັນ','020-55555555','active','2026-08-28 12:18:02');
/*!40000 ALTER TABLE `tbstore` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbuser`
--

DROP TABLE IF EXISTS `tbuser`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbuser` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `user_code` varchar(50) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `fname` varchar(100) DEFAULT NULL,
  `lname` varchar(100) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `tel` varchar(30) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'employee',
  `store_id` int NOT NULL DEFAULT '1',
  `address` text,
  `notes` text,
  `profile_img` varchar(255) DEFAULT 'default.png',
  `last_activity` datetime DEFAULT NULL,
  `dashboard` tinyint(1) DEFAULT '0',
  `sale` tinyint(1) DEFAULT '0',
  `item_sales` tinyint(1) DEFAULT '0',
  `stock` tinyint(1) DEFAULT '0',
  `categories` tinyint(1) DEFAULT '0',
  `products` tinyint(1) DEFAULT '0',
  `import_stock` tinyint(1) DEFAULT '0',
  `import_list` tinyint(1) DEFAULT '0',
  `report` tinyint(1) DEFAULT '0',
  `daily_report` tinyint(1) DEFAULT '0',
  `all_sales` tinyint(1) DEFAULT '0',
  `best_seller` tinyint(1) DEFAULT '0',
  `profit_cost` tinyint(1) DEFAULT '0',
  `financial` tinyint(1) DEFAULT '0',
  `category_sales` tinyint(1) DEFAULT '0',
  `delete_bills` tinyint(1) DEFAULT '0',
  `accounting` tinyint(1) DEFAULT '0',
  `setup` tinyint(1) DEFAULT '0',
  `users` tinyint(1) DEFAULT '0',
  `permissions` tinyint(1) DEFAULT '0',
  `branches` tinyint(1) DEFAULT '0',
  `edit` tinyint(1) DEFAULT '0',
  `customers` tinyint(1) DEFAULT '1',
  `database` tinyint(1) DEFAULT '0',
  `stock_transfer` tinyint(1) DEFAULT '0',
  `transfer_history` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbuser`
--

LOCK TABLES `tbuser` WRITE;
/*!40000 ALTER TABLE `tbuser` DISABLE KEYS */;
INSERT INTO `tbuser` VALUES (1,'001','admin',NULL,NULL,NULL,NULL,NULL,'8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92','Admin',1,NULL,NULL,'default.png','2026-08-28 15:26:39',1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,'2026-08-28 14:42:49');
/*!40000 ALTER TABLE `tbuser` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `units`
--

DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `units` (
  `unit_id` int NOT NULL AUTO_INCREMENT,
  `unit_name` varchar(100) NOT NULL,
  `description` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `store_id` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `units`
--

LOCK TABLES `units` WRITE;
/*!40000 ALTER TABLE `units` DISABLE KEYS */;
/*!40000 ALTER TABLE `units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_permission_switch_states`
--

DROP TABLE IF EXISTS `user_permission_switch_states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_permission_switch_states` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `switch_key` varchar(100) NOT NULL,
  `is_enabled` tinyint(1) DEFAULT '1',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_switch` (`user_id`,`switch_key`)
) ENGINE=InnoDB AUTO_INCREMENT=208 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_permission_switch_states`
--

LOCK TABLES `user_permission_switch_states` WRITE;
/*!40000 ALTER TABLE `user_permission_switch_states` DISABLE KEYS */;
INSERT INTO `user_permission_switch_states` VALUES (1,2,'perm_accounting_add_2',0,'2026-08-28 08:26:05'),(2,2,'perm_accounting_del_2',0,'2026-08-28 08:26:05'),(3,2,'perm_accounting_edit_2',0,'2026-08-28 08:26:05'),(4,2,'perm_accounting_view_2',0,'2026-08-28 08:26:05'),(5,2,'perm_all_sales_view_2',0,'2026-08-28 08:26:05'),(6,2,'perm_best_seller_view_2',0,'2026-08-28 08:26:05'),(7,2,'perm_branches_add_2',0,'2026-08-28 08:26:05'),(8,2,'perm_branches_del_2',0,'2026-08-28 08:26:05'),(9,2,'perm_branches_edit_2',0,'2026-08-28 08:26:05'),(10,2,'perm_branches_view_2',0,'2026-08-28 08:26:05'),(11,2,'perm_categories_add_2',0,'2026-08-28 08:26:05'),(12,2,'perm_categories_del_2',0,'2026-08-28 08:26:05'),(13,2,'perm_categories_edit_2',0,'2026-08-28 08:26:05'),(14,2,'perm_categories_view_2',0,'2026-08-28 08:26:05'),(15,2,'perm_category_sales_2',0,'2026-08-28 08:26:05'),(16,2,'perm_customers_add_2',1,'2026-08-28 08:26:05'),(17,2,'perm_customers_del_2',0,'2026-08-28 08:26:05'),(18,2,'perm_customers_edit_2',1,'2026-08-28 08:26:05'),(19,2,'perm_customers_view_2',1,'2026-08-28 08:26:05'),(20,2,'perm_daily_report_view_2',0,'2026-08-28 08:26:05'),(21,2,'perm_dashboard_view_2',0,'2026-08-28 08:26:05'),(22,2,'perm_database_view_2',0,'2026-08-28 08:26:05'),(23,2,'perm_delete_bills_2',0,'2026-08-28 08:26:05'),(24,2,'perm_exchange_rate_add_2',0,'2026-08-28 08:26:05'),(25,2,'perm_exchange_rate_del_2',0,'2026-08-28 08:26:05'),(26,2,'perm_exchange_rate_edit_2',0,'2026-08-28 08:26:05'),(27,2,'perm_exchange_rate_view_2',0,'2026-08-28 08:26:05'),(28,2,'perm_financial_view_2',0,'2026-08-28 08:26:05'),(29,2,'perm_import_list_del_2',0,'2026-08-28 08:26:05'),(30,2,'perm_import_list_edit_2',1,'2026-08-28 08:26:05'),(31,2,'perm_import_list_view_2',1,'2026-08-28 08:26:05'),(32,2,'perm_import_stock_add_2',1,'2026-08-28 08:26:05'),(33,2,'perm_import_stock_view_2',1,'2026-08-28 08:26:05'),(34,2,'perm_item_sales_del_2',0,'2026-08-28 08:26:05'),(35,2,'perm_item_sales_edit_2',1,'2026-08-28 08:26:05'),(36,2,'perm_item_sales_view_2',1,'2026-08-28 08:26:05'),(37,2,'perm_permissions_edit_2',0,'2026-08-28 08:26:05'),(38,2,'perm_permissions_view_2',0,'2026-08-28 08:26:05'),(39,2,'perm_price_adjustment_add_2',0,'2026-08-28 08:26:05'),(40,2,'perm_price_adjustment_del_2',0,'2026-08-28 08:26:05'),(41,2,'perm_price_adjustment_edit_2',0,'2026-08-28 08:26:05'),(42,2,'perm_price_adjustment_view_2',0,'2026-08-28 08:26:05'),(43,2,'perm_printers_add_2',0,'2026-08-28 08:26:05'),(44,2,'perm_printers_del_2',0,'2026-08-28 08:26:05'),(45,2,'perm_printers_edit_2',0,'2026-08-28 08:26:05'),(46,2,'perm_printers_view_2',0,'2026-08-28 08:26:05'),(47,2,'perm_print_barcode_view_2',0,'2026-08-28 08:26:05'),(48,2,'perm_products_add_2',0,'2026-08-28 08:26:05'),(49,2,'perm_products_del_2',0,'2026-08-28 08:26:05'),(50,2,'perm_products_edit_2',0,'2026-08-28 08:26:05'),(51,2,'perm_products_view_2',0,'2026-08-28 08:26:05'),(52,2,'perm_profit_cost_view_2',0,'2026-08-28 08:26:05'),(53,2,'perm_promotions_add_2',0,'2026-08-28 08:26:05'),(54,2,'perm_promotions_del_2',0,'2026-08-28 08:26:05'),(55,2,'perm_promotions_edit_2',0,'2026-08-28 08:26:05'),(56,2,'perm_promotions_view_2',0,'2026-08-28 08:26:05'),(57,2,'perm_sale_add_2',1,'2026-08-28 08:26:05'),(58,2,'perm_sale_del_2',0,'2026-08-28 08:26:05'),(59,2,'perm_sale_edit_2',1,'2026-08-28 08:26:05'),(60,2,'perm_sale_view_2',1,'2026-08-28 08:26:05'),(61,2,'perm_stock_transfer_add_2',0,'2026-08-28 08:26:05'),(62,2,'perm_stock_transfer_view_2',0,'2026-08-28 08:26:05'),(63,2,'perm_stores_edit_2',0,'2026-08-28 08:26:05'),(64,2,'perm_stores_view_2',0,'2026-08-28 08:26:05'),(65,2,'perm_transfer_history_view_2',0,'2026-08-28 08:26:05'),(66,2,'perm_users_add_2',0,'2026-08-28 08:26:05'),(67,2,'perm_users_del_2',0,'2026-08-28 08:26:05'),(68,2,'perm_users_edit_2',0,'2026-08-28 08:26:05'),(69,2,'perm_users_view_2',0,'2026-08-28 08:26:05'),(70,3,'perm_accounting_add_3',0,'2026-08-28 08:26:05'),(71,3,'perm_accounting_del_3',0,'2026-08-28 08:26:05'),(72,3,'perm_accounting_edit_3',0,'2026-08-28 08:26:05'),(73,3,'perm_accounting_view_3',0,'2026-08-28 08:26:05'),(74,3,'perm_all_sales_view_3',0,'2026-08-28 08:26:05'),(75,3,'perm_best_seller_view_3',0,'2026-08-28 08:26:05'),(76,3,'perm_branches_add_3',0,'2026-08-28 08:26:05'),(77,3,'perm_branches_del_3',0,'2026-08-28 08:26:05'),(78,3,'perm_branches_edit_3',0,'2026-08-28 08:26:05'),(79,3,'perm_branches_view_3',0,'2026-08-28 08:26:05'),(80,3,'perm_categories_add_3',1,'2026-08-28 08:26:05'),(81,3,'perm_categories_del_3',0,'2026-08-28 08:26:05'),(82,3,'perm_categories_edit_3',1,'2026-08-28 08:26:05'),(83,3,'perm_categories_view_3',1,'2026-08-28 08:26:05'),(84,3,'perm_category_sales_3',0,'2026-08-28 08:26:05'),(85,3,'perm_customers_add_3',1,'2026-08-28 08:26:05'),(86,3,'perm_customers_del_3',0,'2026-08-28 08:26:05'),(87,3,'perm_customers_edit_3',1,'2026-08-28 08:26:05'),(88,3,'perm_customers_view_3',1,'2026-08-28 08:26:05'),(89,3,'perm_daily_report_view_3',0,'2026-08-28 08:26:05'),(90,3,'perm_dashboard_view_3',0,'2026-08-28 08:26:05'),(91,3,'perm_database_view_3',0,'2026-08-28 08:26:05'),(92,3,'perm_delete_bills_3',0,'2026-08-28 08:26:05'),(93,3,'perm_exchange_rate_add_3',0,'2026-08-28 08:26:05'),(94,3,'perm_exchange_rate_del_3',0,'2026-08-28 08:26:05'),(95,3,'perm_exchange_rate_edit_3',0,'2026-08-28 08:26:05'),(96,3,'perm_exchange_rate_view_3',0,'2026-08-28 08:26:05'),(97,3,'perm_financial_view_3',0,'2026-08-28 08:26:05'),(98,3,'perm_import_list_del_3',0,'2026-08-28 08:26:05'),(99,3,'perm_import_list_edit_3',1,'2026-08-28 08:26:05'),(100,3,'perm_import_list_view_3',1,'2026-08-28 08:26:05'),(101,3,'perm_import_stock_add_3',1,'2026-08-28 08:26:05'),(102,3,'perm_import_stock_view_3',1,'2026-08-28 08:26:05'),(103,3,'perm_item_sales_del_3',0,'2026-08-28 08:26:05'),(104,3,'perm_item_sales_edit_3',1,'2026-08-28 08:26:05'),(105,3,'perm_item_sales_view_3',1,'2026-08-28 08:26:05'),(106,3,'perm_permissions_edit_3',0,'2026-08-28 08:26:05'),(107,3,'perm_permissions_view_3',0,'2026-08-28 08:26:05'),(108,3,'perm_price_adjustment_add_3',0,'2026-08-28 08:26:05'),(109,3,'perm_price_adjustment_del_3',0,'2026-08-28 08:26:05'),(110,3,'perm_price_adjustment_edit_3',0,'2026-08-28 08:26:05'),(111,3,'perm_price_adjustment_view_3',0,'2026-08-28 08:26:05'),(112,3,'perm_printers_add_3',0,'2026-08-28 08:26:05'),(113,3,'perm_printers_del_3',0,'2026-08-28 08:26:05'),(114,3,'perm_printers_edit_3',0,'2026-08-28 08:26:05'),(115,3,'perm_printers_view_3',1,'2026-08-28 08:26:05'),(116,3,'perm_print_barcode_view_3',1,'2026-08-28 08:26:05'),(117,3,'perm_products_add_3',1,'2026-08-28 08:26:05'),(118,3,'perm_products_del_3',0,'2026-08-28 08:26:05'),(119,3,'perm_products_edit_3',1,'2026-08-28 08:26:05'),(120,3,'perm_products_view_3',1,'2026-08-28 08:26:05'),(121,3,'perm_profit_cost_view_3',0,'2026-08-28 08:26:05'),(122,3,'perm_promotions_add_3',0,'2026-08-28 08:26:05'),(123,3,'perm_promotions_del_3',0,'2026-08-28 08:26:05'),(124,3,'perm_promotions_edit_3',0,'2026-08-28 08:26:05'),(125,3,'perm_promotions_view_3',1,'2026-08-28 08:26:05'),(126,3,'perm_sale_add_3',1,'2026-08-28 08:26:05'),(127,3,'perm_sale_del_3',0,'2026-08-28 08:26:05'),(128,3,'perm_sale_edit_3',1,'2026-08-28 08:26:05'),(129,3,'perm_sale_view_3',1,'2026-08-28 08:26:05'),(130,3,'perm_stock_transfer_add_3',0,'2026-08-28 08:26:05'),(131,3,'perm_stock_transfer_view_3',0,'2026-08-28 08:26:05'),(132,3,'perm_stores_edit_3',0,'2026-08-28 08:26:05'),(133,3,'perm_stores_view_3',1,'2026-08-28 08:26:05'),(134,3,'perm_transfer_history_view_3',0,'2026-08-28 08:26:05'),(135,3,'perm_users_add_3',0,'2026-08-28 08:26:05'),(136,3,'perm_users_del_3',0,'2026-08-28 08:26:05'),(137,3,'perm_users_edit_3',0,'2026-08-28 08:26:05'),(138,3,'perm_users_view_3',0,'2026-08-28 08:26:05'),(139,4,'perm_accounting_add_4',0,'2026-08-28 08:26:05'),(140,4,'perm_accounting_del_4',0,'2026-08-28 08:26:05'),(141,4,'perm_accounting_edit_4',0,'2026-08-28 08:26:05'),(142,4,'perm_accounting_view_4',1,'2026-08-28 08:26:05'),(143,4,'perm_all_sales_view_4',1,'2026-08-28 08:26:05'),(144,4,'perm_best_seller_view_4',1,'2026-08-28 08:26:05'),(145,4,'perm_branches_add_4',0,'2026-08-28 08:26:05'),(146,4,'perm_branches_del_4',0,'2026-08-28 08:26:05'),(147,4,'perm_branches_edit_4',0,'2026-08-28 08:26:05'),(148,4,'perm_branches_view_4',0,'2026-08-28 08:26:05'),(149,4,'perm_categories_add_4',0,'2026-08-28 08:26:05'),(150,4,'perm_categories_del_4',0,'2026-08-28 08:26:05'),(151,4,'perm_categories_edit_4',0,'2026-08-28 08:26:05'),(152,4,'perm_categories_view_4',0,'2026-08-28 08:26:05'),(153,4,'perm_category_sales_4',1,'2026-08-28 08:26:05'),(154,4,'perm_customers_add_4',0,'2026-08-28 08:26:05'),(155,4,'perm_customers_del_4',0,'2026-08-28 08:26:05'),(156,4,'perm_customers_edit_4',0,'2026-08-28 08:26:05'),(157,4,'perm_customers_view_4',0,'2026-08-28 08:26:05'),(158,4,'perm_daily_report_view_4',1,'2026-08-28 08:26:05'),(159,4,'perm_dashboard_view_4',1,'2026-08-28 08:26:05'),(160,4,'perm_database_view_4',0,'2026-08-28 08:26:05'),(161,4,'perm_delete_bills_4',1,'2026-08-28 08:26:05'),(162,4,'perm_exchange_rate_add_4',0,'2026-08-28 08:26:05'),(163,4,'perm_exchange_rate_del_4',0,'2026-08-28 08:26:05'),(164,4,'perm_exchange_rate_edit_4',0,'2026-08-28 08:26:05'),(165,4,'perm_exchange_rate_view_4',0,'2026-08-28 08:26:05'),(166,4,'perm_financial_view_4',1,'2026-08-28 08:26:05'),(167,4,'perm_import_list_del_4',0,'2026-08-28 08:26:05'),(168,4,'perm_import_list_edit_4',0,'2026-08-28 08:26:05'),(169,4,'perm_import_list_view_4',1,'2026-08-28 08:26:05'),(170,4,'perm_import_stock_add_4',0,'2026-08-28 08:26:05'),(171,4,'perm_import_stock_view_4',0,'2026-08-28 08:26:05'),(172,4,'perm_item_sales_del_4',0,'2026-08-28 08:26:05'),(173,4,'perm_item_sales_edit_4',0,'2026-08-28 08:26:05'),(174,4,'perm_item_sales_view_4',0,'2026-08-28 08:26:05'),(175,4,'perm_permissions_edit_4',0,'2026-08-28 08:26:05'),(176,4,'perm_permissions_view_4',0,'2026-08-28 08:26:05'),(177,4,'perm_price_adjustment_add_4',0,'2026-08-28 08:26:05'),(178,4,'perm_price_adjustment_del_4',0,'2026-08-28 08:26:05'),(179,4,'perm_price_adjustment_edit_4',0,'2026-08-28 08:26:05'),(180,4,'perm_price_adjustment_view_4',0,'2026-08-28 08:26:05'),(181,4,'perm_printers_add_4',0,'2026-08-28 08:26:05'),(182,4,'perm_printers_del_4',0,'2026-08-28 08:26:05'),(183,4,'perm_printers_edit_4',0,'2026-08-28 08:26:05'),(184,4,'perm_printers_view_4',0,'2026-08-28 08:26:05'),(185,4,'perm_print_barcode_view_4',0,'2026-08-28 08:26:05'),(186,4,'perm_products_add_4',0,'2026-08-28 08:26:05'),(187,4,'perm_products_del_4',0,'2026-08-28 08:26:05'),(188,4,'perm_products_edit_4',0,'2026-08-28 08:26:05'),(189,4,'perm_products_view_4',0,'2026-08-28 08:26:05'),(190,4,'perm_profit_cost_view_4',1,'2026-08-28 08:26:05'),(191,4,'perm_promotions_add_4',0,'2026-08-28 08:26:05'),(192,4,'perm_promotions_del_4',0,'2026-08-28 08:26:05'),(193,4,'perm_promotions_edit_4',0,'2026-08-28 08:26:05'),(194,4,'perm_promotions_view_4',0,'2026-08-28 08:26:05'),(195,4,'perm_sale_add_4',0,'2026-08-28 08:26:05'),(196,4,'perm_sale_del_4',0,'2026-08-28 08:26:05'),(197,4,'perm_sale_edit_4',0,'2026-08-28 08:26:05'),(198,4,'perm_sale_view_4',0,'2026-08-28 08:26:05'),(199,4,'perm_stock_transfer_add_4',0,'2026-08-28 08:26:05'),(200,4,'perm_stock_transfer_view_4',0,'2026-08-28 08:26:05'),(201,4,'perm_stores_edit_4',0,'2026-08-28 08:26:05'),(202,4,'perm_stores_view_4',0,'2026-08-28 08:26:05'),(203,4,'perm_transfer_history_view_4',1,'2026-08-28 08:26:05'),(204,4,'perm_users_add_4',0,'2026-08-28 08:26:05'),(205,4,'perm_users_del_4',0,'2026-08-28 08:26:05'),(206,4,'perm_users_edit_4',0,'2026-08-28 08:26:05'),(207,4,'perm_users_view_4',0,'2026-08-28 08:26:05');
/*!40000 ALTER TABLE `user_permission_switch_states` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-28 15:27:22
