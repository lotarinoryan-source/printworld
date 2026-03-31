-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: printing_shop
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'adminprintworld','$2y$10$mtmThzto5pyg3Pneb82vzu/sTCm9rPht/XDLHr3jXqHZsAJInwxY.','admin@printcraft.com','2026-03-16 13:21:56');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `client_branches`
--

DROP TABLE IF EXISTS `client_branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `client_branches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `branch_name` varchar(200) NOT NULL,
  `address` text DEFAULT NULL,
  `dear` varchar(200) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `client_branches_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `premium_clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client_branches`
--

LOCK TABLES `client_branches` WRITE;
/*!40000 ALTER TABLE `client_branches` DISABLE KEYS */;
INSERT INTO `client_branches` VALUES (1,1,'Digos','Roxas Ext., Digos City','',1,'2026-03-20 03:48:57');
/*!40000 ALTER TABLE `client_branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `client_color_codes`
--

DROP TABLE IF EXISTS `client_color_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `client_color_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_name` varchar(150) NOT NULL,
  `color_code` varchar(50) NOT NULL,
  `color_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_name`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client_color_codes`
--

LOCK TABLES `client_color_codes` WRITE;
/*!40000 ALTER TABLE `client_color_codes` DISABLE KEYS */;
INSERT INTO `client_color_codes` VALUES (19,'Xtreme','#21ba0d','HI LITE - HP123','2026-03-21 03:33:05');
/*!40000 ALTER TABLE `client_color_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `client_notes`
--

DROP TABLE IF EXISTS `client_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `client_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Unpaid','Paid') NOT NULL DEFAULT 'Unpaid',
  `date_added` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client_notes`
--

LOCK TABLES `client_notes` WRITE;
/*!40000 ALTER TABLE `client_notes` DISABLE KEYS */;
INSERT INTO `client_notes` VALUES (4,'Digos','Tarpaulin',60.00,'Unpaid','2026-03-21 04:42:44'),(5,'Digos','Keychain 15 pcs',500.00,'Unpaid','2026-03-21 05:13:40');
/*!40000 ALTER TABLE `client_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `final_quotation_items`
--

DROP TABLE IF EXISTS `final_quotation_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `final_quotation_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quotation_id` int(11) NOT NULL,
  `service_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `quotation_id` (`quotation_id`),
  CONSTRAINT `final_quotation_items_ibfk_1` FOREIGN KEY (`quotation_id`) REFERENCES `final_quotations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `final_quotation_items`
--

LOCK TABLES `final_quotation_items` WRITE;
/*!40000 ALTER TABLE `final_quotation_items` DISABLE KEYS */;
INSERT INTO `final_quotation_items` VALUES (1,1,'Service','6ft x 8ft Single Face - Non-lighted',1,88900.00,88900.00),(2,3,'Service','6ft x 8ft Single Face - Non-lighted',1,0.00,0.00),(3,4,'Service','6ft x 8ft Single Face - Non-lighted',1,123000.00,123000.00),(4,5,'Service','6ft x 8ft Single Face - Non-lighted',1,65430.00,65430.00),(5,6,'Service','6ft x 8ft Single Face - Non-lighted',1,5950.00,5950.00),(6,7,'Service','Tarpaulin Printing - 5ft x 7ft (Already Have Design)',100,50.00,5000.00),(7,8,'Service','Tarpaulin Printing - 5ft x 7ft (Already Have Design)',1,800.00,800.00),(8,9,'Service','6ft x 8ft Single Face - Non-lighted',1,999.00,999.00),(9,10,'Service','6ft x 8ft Single Face - Non-lighted',1,10000.00,10000.00),(10,10,'Service','Lampost',30,500.00,15000.00),(11,11,'Service','6ft x 8ft Single Face - Non-lighted',1,6000.00,6000.00),(12,12,'Service','Tarpaulin Printing - 5ft x 7ft (Already Have Design)',1,0.00,0.00),(13,13,'Service','Tarpaulin Printing - 4ft x 8ft (Already Have Design)',1,320.00,320.00),(14,14,'Service','Acrylic — Build Up Type — Non-lighted — 8ft × 9ft — Design: No',1,50000.00,50000.00),(15,15,'Service','Acrylic — Build Up Type — Non-lighted — 8ft × 9ft — Design: No',1,569315.00,569315.00),(16,16,'Service','Billboard — Single Frame — Non-lighted — 5ft × 6ft — Design: Yes',1,50000.00,50000.00),(17,17,'Service','Polo Shirt — Design: Yes',12,500.00,6000.00),(18,18,'Service','Tarpaulin — 8ft × 9ft',1,50.00,50.00),(19,19,'Service','Mug',52,100.00,5200.00),(20,20,'Service','Ref Magnets',80,25.00,2000.00),(21,21,'Service','Ref Magnets',80,25.00,2000.00),(22,22,'Service','Keychain (I have a design)',50,0.00,0.00),(23,23,'Service','Tarpaulin Printing - 4ft x 8ft (Already Have Design)',1,50.00,50.00),(24,24,'Service','Tarpaulin Printing - 5ft x 7ft (Already Have Design)',1,5000000.00,5000000.00),(25,25,'Service','Tarpaulin Printing - 4ft x 8ft (Already Have Design)',1,2035.00,2035.00),(26,26,'Service','Tarpaulin — 5ft × 9ft',1,50000.00,50000.00),(27,27,'Service','6ft x 8ft Single Face - Non-lighted',1,50000.00,50000.00),(28,28,'Service','6ft x 8ft Single Face - Non-lighted',1,5000.00,5000.00),(29,29,'Service','6ft x 8ft Single Face - Non-lighted',1,50000.00,50000.00),(30,30,'Service','6ft x 8ft Single Face - Non-lighted',1,50000.00,50000.00),(31,31,'Service','6ft x 8ft Single Face - Non-lighted',1,8888888.00,8888888.00),(32,32,'Service','6ft x 8ft Single Face - Non-lighted',1,555555.00,555555.00),(33,33,'Service','6ft x 8ft Single Face - Non-lighted',1,50000.00,50000.00),(34,34,'Service','6ft x 8ft Single Face - Non-lighted',1,51515.00,51515.00),(35,35,'Service','5',1,8.00,8.00),(36,36,'Service','5',1,8.00,8.00),(37,37,'Service','Keychain',14,25.00,350.00),(38,38,'Service','Polo Shirt',56,50.00,2800.00),(39,39,'Service','Keychain',50,50.00,2500.00);
/*!40000 ALTER TABLE `final_quotation_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `final_quotations`
--

DROP TABLE IF EXISTS `final_quotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `final_quotations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quotation_number` varchar(20) NOT NULL,
  `request_id` int(11) DEFAULT NULL,
  `customer_name` varchar(200) NOT NULL,
  `company_name` varchar(200) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `is_premium` tinyint(1) DEFAULT 0,
  `premium_client_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `prem_address` text DEFAULT NULL,
  `prem_branch` varchar(200) DEFAULT NULL,
  `prem_dear` varchar(200) DEFAULT NULL,
  `prem_prepared_by` varchar(200) DEFAULT NULL,
  `prem_checked_by` varchar(200) DEFAULT NULL,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `quotation_number` (`quotation_number`),
  KEY `request_id` (`request_id`),
  CONSTRAINT `final_quotations_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `quotation_requests` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `final_quotations`
--

LOCK TABLES `final_quotations` WRITE;
/*!40000 ALTER TABLE `final_quotations` DISABLE KEYS */;
INSERT INTO `final_quotations` VALUES (1,'QT-20260316-E3D2B',1,'0','Printworld','lotarinoryan@gmail.com','09956139821',0,0,NULL,'','','Ryan Mark Lotarino','Niño S. Del Rosario','Ryan Mark R. Lotarino',2.00,88900.00,1778.00,87122.00,'',NULL,'C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260316-E3D2B.pdf',NULL,'2026-03-16 15:30:21'),(3,'QT-20260316-297D8',1,'0','Printworld','lotarinoryan@gmail.com','09956139821',0,0,NULL,'','','Ryan Mark Lotarino','Niño S. Del Rosario','Ryan Mark R. Lotarino',0.00,0.00,0.00,0.00,'',NULL,'C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260316-297D8.pdf',NULL,'2026-03-16 16:25:50'),(4,'QT-20260316-594D1',NULL,'Ryan Mark Lotarino','Robinsons','lotarinoryan@gmail.com','09956139821',0,0,NULL,'Digos City, Davao del Sur','Digos','Evaporada','Nino S. Del Rosario','Ryan Mark R. Lotarino',0.00,123000.00,0.00,123000.00,'',NULL,'C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260316-594D1.pdf',NULL,'2026-03-16 16:35:08'),(5,'QT-20260316-20487',NULL,'Ryan Mark Lotarino','Printworld','lotarinoryan@gmail.com','09956139821',0,0,NULL,'Digos City, Davao del Sur','Digos','Evaporada','Nino S. Del Rosario','Ryan Mark R. Lotarino',0.00,65430.00,0.00,65430.00,'',NULL,'C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260316-20487.pdf',NULL,'2026-03-16 16:35:31'),(6,'QT-20260316-274DD',NULL,'Ryan Mark Lotarino','Mr DIY','lotarinoryan@gmail.com','09956139821',1,1,NULL,'3A floor Xeland Building Guerilla Street, Cor. Mayor Gil Fernando Ave. Marikina City, Philippines 1800','','Ryan Mark Lotarino','Nino S. Del Rosario','Ryan Mark R. Lotarino',0.00,5950.00,0.00,5950.00,'',NULL,'C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260316-274DD.pdf',NULL,'2026-03-16 16:35:57'),(7,'QT-20260316-C03B5',2,'0','Printworld','lotarinoryan@gmail.com','09956139821',0,0,NULL,'','','Ryan Mark Lotarino','Niño S. Del Rosario','Ryan Mark R. Lotarino',0.00,5000.00,0.00,5000.00,'',NULL,'C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260316-C03B5.pdf',NULL,'2026-03-16 16:41:19'),(8,'QT-20260316-BC579',NULL,'Ryan Mark Lotarino','Printworld','lotarinoryan@gmail.com','09956139821',0,0,NULL,'3A floor Xeland Building Guerilla Street, Cor. Mayor Gil Fernando Ave. Marikina City, Philippines 1800','Digos','Evaporada','Nino S. Del Rosario','Ryan Mark R. Lotarino',12.00,800.00,96.00,704.00,'Sige',NULL,'C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260316-BC579.pdf',NULL,'2026-03-16 16:56:21'),(9,'QT-20260316-C1AB2',NULL,'Ryan Mark Lotarino','Printworld','lotarinoryan@gmail.com','09956139821',0,0,NULL,'3A floor Xeland Building Guerilla Street, Cor. Mayor Gil Fernando Ave. Marikina City, Philippines 1800','','Evaporada','Nino S. Del Rosario','Ryan Mark R. Lotarino',0.00,999.00,0.00,999.00,'',NULL,'C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260316-C1AB2.pdf',NULL,'2026-03-16 16:57:32'),(10,'QT-20260316-266A5',NULL,'Ryan Mark Lotarino','Mr DIY','lotarinoryan@gmail.com','09956139821',0,0,NULL,'3A floor Xeland Building Guerilla Street, Cor. Mayor Gil Fernando Ave. Marikina City, Philippines 1800','Digos','Eva','Nino S. Del Rosario','Ryan Mark R. Lotarino',0.00,25000.00,0.00,25000.00,'',NULL,'C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260316-266A5.pdf',NULL,'2026-03-16 17:15:11'),(11,'QT-20260316-6C174',NULL,'Ryan Mark Lotarino','Printworld','lotarinoryan@gmail.com','09956139821',1,0,NULL,'3A floor Xeland Building Guerilla Street, Cor. Mayor Gil Fernando Ave. Marikina City, Philippines 1800','Digos','Evaporada','Nino S. Del Rosario','Ryan Mark R. Lotarino',0.00,6000.00,0.00,6000.00,'',NULL,'C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260316-6C174.pdf',NULL,'2026-03-16 17:29:19'),(12,'QT-20260317-85C9B',2,'0','Printworld','lotarinoryan@gmail.com','09956139821',0,0,NULL,'','','Ryan Mark Lotarino','Niño S. Del Rosario','Ryan Mark R. Lotarino',0.00,0.00,0.00,0.00,'',NULL,'C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260317-85C9B.pdf',NULL,'2026-03-17 05:04:16'),(13,'QT-20260317-D094D',3,'0','Printworld','lotarinoryan@gmail.com','09956139821',0,0,NULL,'','','Ryan Mark Lotarino','Niño S. Del Rosario','Ryan Mark R. Lotarino',0.00,320.00,0.00,320.00,'',NULL,'C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260317-D094D.pdf',NULL,'2026-03-17 09:22:06'),(14,'QT-20260317-A3301',5,'0','Mr DIY','lotarinoryan@gmail.com','09956139821',0,0,NULL,'','','Ryan Mark Lotarino','Niño S. Del Rosario','Ryan Mark R. Lotarino',0.00,50000.00,0.00,50000.00,'','Digos City','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260317-A3301.pdf',NULL,'2026-03-17 11:53:01'),(15,'QT-20260317-5B251',5,'0','Mr DIY','lotarinoryan@gmail.com','09956139821',0,0,NULL,'','','Ryan Mark Lotarino','Niño S. Del Rosario','Ryan Mark R. Lotarino',0.00,569315.00,315.00,569000.00,'','Digos City, Davao del Sur','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260317-5B251.pdf',NULL,'2026-03-17 12:11:07'),(16,'QT-20260317-E78CF',6,'0','BBBB','bbb@gmail.com','09956139821',0,0,NULL,'','','Bongbong','Niño S. Del Rosario','Ryan Mark R. Lotarino',0.00,50000.00,10000.00,40000.00,'','Digos City, Davao del Sur','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260317-E78CF.pdf',NULL,'2026-03-17 12:13:00'),(17,'QT-20260317-A0F89',7,'0','Mr DIY','lotarinoryan@gmail.com','09956139821',0,0,NULL,'','','Ryan Mark Lotarino','Niño S. Del Rosario','Ryan Mark R. Lotarino',0.00,6000.00,0.00,6000.00,'','Digos City, Davao del Sur','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260317-A0F89.pdf',NULL,'2026-03-17 12:27:18'),(18,'QT-20260317-5FDBD',8,'0','Printworld','lotarinoryan@gmail.com','09956139821',0,0,NULL,'','','Ryan Mark Lotarino','Niño S. Del Rosario','Ryan Mark R. Lotarino',0.00,50.00,0.00,50.00,'','Digos City, Davao del Sur','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260317-5FDBD.pdf',NULL,'2026-03-17 12:42:36'),(19,'QT-20260317-A7531',9,'0','Printworld','lotarinoryan@gmail.com','09956139821',0,0,NULL,'','','Ryan Mark Lotarino','Niño S. Del Rosario','Ryan Mark R. Lotarino',0.00,5200.00,200.00,5000.00,'','Digos City, Davao del Sur','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260317-A7531.pdf',NULL,'2026-03-17 12:49:04'),(20,'QT-20260317-7D73A',10,'0','Mr DIY','lotarinoryan@gmail.com','09956139821',0,0,NULL,'','','Ryan Mark Lotarino','Niño S. Del Rosario','Ryan Mark R. Lotarino',0.00,2000.00,500.00,1500.00,'','Digos City, Davao del Sur','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260317-7D73A.pdf',NULL,'2026-03-17 12:55:06'),(21,'QT-20260317-E3158',10,'0','Mr DIY','lotarinoryan@gmail.com','09956139821',0,0,NULL,'','','Ryan Mark Lotarino','Niño S. Del Rosario','Ryan Mark R. Lotarino',0.00,2000.00,500.00,1500.00,'','Digos City, Davao del Sur','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260317-E3158.pdf',NULL,'2026-03-17 12:55:10'),(22,'QT-20260317-5F922',4,'0','Printworld','lotarinoryan@gmail.com','09956139821',1,0,NULL,'','','Ryan Mark Lotarino','Niño S. Del Rosario','Ryan Mark R. Lotarino',0.00,0.00,0.00,0.00,'','Digos City','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260317-5F922.pdf',NULL,'2026-03-17 12:58:50'),(23,'QT-20260317-11630',NULL,'Mr. DIY','Bricolage','lotarinoryan@gmail.com','09956139821',1,0,NULL,'3A floor Xeland Building Guerilla Street, Cor. Mayor Gil Fernando Ave. Marikina City, Philippines 1800','Digos','Eva','Nino S. Del Rosario','Ryan Mark R. Lotarino',0.00,50.00,0.00,50.00,'DIY',NULL,'C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260317-11630.pdf',NULL,'2026-03-17 16:15:52'),(24,'QT-20260317-AC770',NULL,'Ryan Mark Lotarino','Mr DIY','lotarinoryan@gmail.com','09956139821',0,0,NULL,'3A floor Xeland Building Guerilla Street, Cor. Mayor Gil Fernando Ave. Marikina City, Philippines 1800','Digos','Eva','Nino S. Del Rosario','Ryan Mark R. Lotarino',NULL,5000000.00,10000.00,4990000.00,'',NULL,'C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260317-AC770.pdf',NULL,'2026-03-17 16:22:02'),(25,'QT-20260317-07A1F',NULL,'Ryan Mark Lotarino','Mr DIY','lotarinoryan@gmail.com','09956139821',1,1,NULL,'3A floor Xeland Building Guerilla Street, Cor. Mayor Gil Fernando Ave. Marikina City, Philippines 1800','Digos','Eva','Nino S. Del Rosario','Ryan Mark R. Lotarino',NULL,2035.00,35.00,2000.00,'',NULL,'C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260317-07A1F.pdf',NULL,'2026-03-17 16:23:03'),(26,'QT-20260318-87C4B',11,'0','Printworld','lotarinoryan@gmail.com','09956139821',0,0,NULL,'','','Ryan Mark Lotarino','Niño S. Del Rosario','Ryan Mark R. Lotarino',0.00,50000.00,0.00,50000.00,'','Digos City, Davao del Sur','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260318-87C4B.pdf',NULL,'2026-03-18 10:57:18'),(27,'QT-20260318-A94D0',NULL,'Ryan Mark Lotarino','Mr DIY','lotarinoryan@gmail.com','09956139821',1,1,NULL,'3A floor Xeland Building Guerilla Street, Cor. Mayor Gil Fernando Ave. Marikina City, Philippines 1800','Digos','Eva','Nino S. Del Rosario','Ryan Mark R. Lotarino',NULL,50000.00,10000.00,40000.00,'',NULL,'C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260318-A94D0.pdf',NULL,'2026-03-18 10:58:25'),(28,'QT-20260320-96A9C',NULL,'Store Manager','Mr DIY','diy@gmail.com','099999999',1,1,NULL,'3A floor Xeland Building Guerilla Street, Cor. Mayor Gil Fernando Ave. Marikina City, Philippines 1800','','Eva','Nino S. Del Rosario','Ryan Mark R. Lotarino',NULL,5000.00,0.00,5000.00,'',NULL,'C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260320-96A9C.pdf',NULL,'2026-03-20 03:39:16'),(29,'QT-20260320-83817',NULL,'Store Manager','Mr DIY','diy@gmail.com','099999999',1,1,NULL,'3A floor Xeland Building Guerilla Street, Cor. Mayor Gil Fernando Ave. Marikina City, Philippines 1800','Digos','evap','Nino S. Del Rosario','Ryan Mark R. Lotarino',NULL,50000.00,6300.00,43700.00,'',NULL,'C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260320-83817.pdf',NULL,'2026-03-20 03:55:34'),(30,'QT-20260320-B76DC',NULL,'Ryan Mark Lotarino','Pet One','lotarinoryan@gmail.com','',1,3,NULL,'Digos City','','','Nino S. Del Rosario','Ryan Mark R. Lotarino',NULL,50000.00,0.00,50000.00,'',NULL,'C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260320-B76DC.pdf',NULL,'2026-03-20 03:57:26'),(31,'QT-20260320-C02B4',NULL,'Ryan Mark Lotarino','Pet One','lotarinoryan@gmail.com','0954152',1,3,NULL,'Digos City','Digos','Eva','Nino S. Del Rosario','Ryan Mark R. Lotarino',NULL,8888888.00,0.00,8888888.00,'',NULL,'C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260320-C02B4.pdf',NULL,'2026-03-20 04:04:44'),(32,'QT-20260320-CE4C5',NULL,'Ryan Mark Lotarino','Pet One','lotarinoryan@gmail.com','0954152',1,3,NULL,'Digos City','','Eva','Nino S. Del Rosario','Ryan Mark R. Lotarino',NULL,555555.00,0.00,555555.00,'',NULL,'C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260320-CE4C5.pdf',NULL,'2026-03-20 04:53:51'),(33,'QT-20260320-30B4C',NULL,'Ryan Mark Lotarino','Pet One','lotarinoryan@gmail.com','0954152',1,3,NULL,'Digos City','Digos','','Nino S. Del Rosario','Ryan Mark R. Lotarino',0.00,50000.00,600.00,49400.00,'',NULL,'C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260320-30B4C.pdf',NULL,'2026-03-20 05:02:26'),(34,'QT-20260320-26FFC',NULL,'Ryan Mark Lotarino','Pet One','lotarinoryan@gmail.com','0954152',1,3,NULL,'Digos City','','','Nino S. Del Rosario','Ryan Mark R. Lotarino',0.00,51515.00,555.00,50960.00,'',NULL,'C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260320-26FFC.pdf',NULL,'2026-03-20 05:09:59'),(35,'QT-20260320-65F84',NULL,'Ryan Mark Lotarino','Mr DIY','lotarinoryan@gmail.com','09956139821',0,0,NULL,'3A floor Xeland Building Guerilla Street, Cor. Mayor Gil Fernando Ave. Marikina City, Philippines 1800','','Eva','Nino S. Del Rosario','Ryan Mark R. Lotarino',0.00,8.00,1.00,7.00,'',NULL,'C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260320-65F84.pdf',NULL,'2026-03-20 05:12:45'),(36,'QT-20260320-0AB38',NULL,'Ryan Mark Lotarino','Mr DIY','lotarinoryan@gmail.com','09956139821',0,0,NULL,'3A floor Xeland Building Guerilla Street, Cor. Mayor Gil Fernando Ave. Marikina City, Philippines 1800','','Eva','Nino S. Del Rosario','Ryan Mark R. Lotarino',0.00,8.00,1.00,7.00,'',NULL,'C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260320-0AB38.pdf',NULL,'2026-03-20 05:12:48'),(37,'QT-20260320-87FF5',12,'0','Printworld','lotarinoryan@gmail.com','09956139821',0,0,NULL,'','','Ryan Mark Lotarino','Niño S. Del Rosario','Ryan Mark R. Lotarino',0.00,350.00,0.00,350.00,'','Digos City','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260320-87FF5.pdf',NULL,'2026-03-20 05:19:28'),(38,'QT-20260320-74F84',13,'0','Mr DIY','lotarinoryan@gmail.com','09956139821',0,0,NULL,'','','Ryan Mark Lotarino','Niño S. Del Rosario','Ryan Mark R. Lotarino',0.00,2800.00,0.00,2800.00,'','Digos City','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260320-74F84.pdf',NULL,'2026-03-20 05:29:27'),(39,'QT-20260320-D70D3',14,'0','Mr DIY','lotarinoryan@gmail.com','09956139821',0,0,NULL,'','','Ryan Mark Lotarino','Niño S. Del Rosario','Ryan Mark R. Lotarino',0.00,2500.00,0.00,2500.00,'','Digos City, Davao del Sur','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260320-D70D3.pdf',NULL,'2026-03-20 05:39:24');
/*!40000 ALTER TABLE `final_quotations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gallery`
--

DROP TABLE IF EXISTS `gallery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gallery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gallery`
--

LOCK TABLES `gallery` WRITE;
/*!40000 ALTER TABLE `gallery` DISABLE KEYS */;
INSERT INTO `gallery` VALUES (2,'Signages','uploads/gallery/img_69b95ad349ab5.jpg','Signage',0,0,'2026-03-17 13:44:51'),(3,'Signages','uploads/gallery/img_69b95b398703d.jpg','Signage',0,0,'2026-03-17 13:46:33'),(4,'Signages','uploads/gallery/img_69b95b3987ca5.jpg','Signage',0,0,'2026-03-17 13:46:33'),(5,'Signages','uploads/gallery/img_69b95b3988591.jpg','Signage',0,0,'2026-03-17 13:46:33'),(6,'Signages','uploads/gallery/img_69b95b39891ad.jpg','Signage',0,0,'2026-03-17 13:46:33'),(7,'Signages','uploads/gallery/img_69b95b3989fb3.jpg','Signage',0,0,'2026-03-17 13:46:33'),(8,'Signages','uploads/gallery/img_69b95b398a743.jpg','Signage',0,0,'2026-03-17 13:46:33'),(9,'Signages','uploads/gallery/img_69b95b398b242.jpg','Signage',0,0,'2026-03-17 13:46:33'),(13,'Signage','uploads/gallery/img_69b95f92322eb.jpg','Signage',0,0,'2026-03-17 14:05:06');
/*!40000 ALTER TABLE `gallery` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gcash_cash_in`
--

DROP TABLE IF EXISTS `gcash_cash_in`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gcash_cash_in` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `amount` decimal(12,2) NOT NULL,
  `charge` decimal(12,2) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gcash_cash_in`
--

LOCK TABLES `gcash_cash_in` WRITE;
/*!40000 ALTER TABLE `gcash_cash_in` DISABLE KEYS */;
INSERT INTO `gcash_cash_in` VALUES (2,13000.00,260.00,13260.00,'2026-03-27 12:05:56'),(3,2940.00,58.80,2998.80,'2026-03-27 12:08:15'),(4,300.00,10.00,310.00,'2026-03-27 12:08:58'),(5,80.00,5.00,85.00,'2026-03-27 12:30:05'),(6,3000.00,60.00,3060.00,'2026-03-27 13:13:48');
/*!40000 ALTER TABLE `gcash_cash_in` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gcash_cash_out`
--

DROP TABLE IF EXISTS `gcash_cash_out`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gcash_cash_out` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `amount` decimal(12,2) NOT NULL,
  `charge` decimal(12,2) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gcash_cash_out`
--

LOCK TABLES `gcash_cash_out` WRITE;
/*!40000 ALTER TABLE `gcash_cash_out` DISABLE KEYS */;
INSERT INTO `gcash_cash_out` VALUES (2,3154.00,63.08,3217.08,'2026-03-27 12:13:58'),(3,440.00,10.00,450.00,'2026-03-27 12:41:14'),(4,2000.00,40.00,2040.00,'2026-03-27 13:32:39');
/*!40000 ALTER TABLE `gcash_cash_out` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `premium_clients`
--

DROP TABLE IF EXISTS `premium_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `premium_clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(200) NOT NULL,
  `contact_person` varchar(200) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `branch` varchar(200) DEFAULT NULL,
  `dear` varchar(200) DEFAULT NULL,
  `terms_conditions` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `premium_clients`
--

LOCK TABLES `premium_clients` WRITE;
/*!40000 ALTER TABLE `premium_clients` DISABLE KEYS */;
INSERT INTO `premium_clients` VALUES (1,'Mr DIY','Store Manager','diy@gmail.com','099999999','3A floor Xeland Building Guerilla Street, Cor. Mayor Gil Fernando Ave. Marikina City, Philippines 1800','Digos','','•Full payment must be made within 30 calendar days from project completion.\r\n•Printworld shall not be held liable for any acts of God that may occur before or during delivery, installation or execution of materials.\r\n•Signages for this project will be installed before the store opening.\r\n•Printworld will tap to the nearest electricity supply up to 2 meters in excess to this provision will be charged to client.\r\n•10% weekly interest will be charged as penalty for late payment.\r\n•Any intentional scratches or damages on the product will void the warranty.\r\n•(5) years of Avery Sticker warranty\r\n•(6) months of LED warranty.\r\n•(1) year of faulty workmanship.',1,'2026-03-16 14:39:48'),(3,'Pet One','Ryan Mark Lotarino','lotarinoryan@gmail.com','0954152','Digos City','','','(5) years of Avery Sticker warranty\r\n(6) months of LED warranty.\r\n(1) year of faulty workmanship.',1,'2026-03-16 14:39:48'),(9,'Printworld','Ryan Mark Lotarino','lotarinoryan@gmail.com','995-613-9821','Digos',NULL,NULL,NULL,1,'2026-03-16 16:46:18');
/*!40000 ALTER TABLE `premium_clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prices`
--

DROP TABLE IF EXISTS `prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_key` varchar(100) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(50) DEFAULT 'per piece',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_key` (`item_key`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prices`
--

LOCK TABLES `prices` WRITE;
/*!40000 ALTER TABLE `prices` DISABLE KEYS */;
INSERT INTO `prices` VALUES (2,'keychain','Keychain',80.00,'per piece','2026-03-16 13:21:56'),(3,'keyholder','Keyholder',90.00,'per piece','2026-03-16 13:21:56'),(4,'ref_magnet','Ref Magnet',70.00,'per piece','2026-03-16 13:21:56'),(5,'tshirt','T-Shirt Sublimation',250.00,'per piece','2026-03-16 13:21:56'),(6,'polo_shirt','Polo Shirt Sublimation',350.00,'per piece','2026-03-16 13:21:56');
/*!40000 ALTER TABLE `prices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotation_items`
--

DROP TABLE IF EXISTS `quotation_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quotation_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quotation_id` int(11) NOT NULL,
  `service_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `item_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`item_details`)),
  PRIMARY KEY (`id`),
  KEY `quotation_id` (`quotation_id`),
  CONSTRAINT `quotation_items_ibfk_1` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotation_items`
--

LOCK TABLES `quotation_items` WRITE;
/*!40000 ALTER TABLE `quotation_items` DISABLE KEYS */;
INSERT INTO `quotation_items` VALUES (1,1,'Signage','Signage 5ft × 7ft — Double Face, Lighted',1,15750.00,15750.00,'{\"signage_type\":\"Double Face\",\"light_type\":\"Lighted\",\"width\":5,\"height\":7,\"price_per_sqft\":450,\"lat\":\"6.7496902\",\"lng\":\"125.3508879\",\"address\":\"\"}'),(2,2,'Signage','Signage 4ft × 7ft — Double Face, Lighted',1,12600.00,12600.00,'{\"signage_type\":\"Double Face\",\"light_type\":\"Lighted\",\"width\":4,\"height\":7,\"price_per_sqft\":450,\"lat\":\"6.7497501\",\"lng\":\"125.3508745\",\"address\":\"\"}');
/*!40000 ALTER TABLE `quotation_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotation_requests`
--

DROP TABLE IF EXISTS `quotation_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quotation_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_number` varchar(20) NOT NULL,
  `customer_name` varchar(200) NOT NULL,
  `company_name` varchar(200) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `items_json` longtext NOT NULL,
  `design_file` varchar(255) DEFAULT NULL,
  `signage_lat` decimal(10,8) DEFAULT NULL,
  `signage_lng` decimal(11,8) DEFAULT NULL,
  `signage_address` text DEFAULT NULL,
  `request_pdf_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','viewed','quoted','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `request_number` (`request_number`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotation_requests`
--

LOCK TABLES `quotation_requests` WRITE;
/*!40000 ALTER TABLE `quotation_requests` DISABLE KEYS */;
INSERT INTO `quotation_requests` VALUES (1,'RQ-20260316-86B6B','Ryan Mark Lotarino','Printworld','lotarinoryan@gmail.com','09956139821',NULL,'way discount dha?','[{\"service_type\":\"Signage\",\"item_name\":\"Signage\",\"description\":\"6ft x 8ft Single Face - Non-lighted\",\"quantity\":1,\"details\":{\"signage_type\":\"Single Face\",\"light_type\":\"Non-lighted\",\"width\":6,\"height\":8,\"lat\":\"6.7496832\",\"lng\":\"125.3509429\",\"address\":\"\"},\"id\":\"item_1773674921303\"}]','uploads/designs/design_69b821ad83846.png',6.74968320,125.35094290,'','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Request_RQ-20260316-86B6B.pdf','quoted','2026-03-16 15:28:45'),(2,'RQ-20260316-302A6','Ryan Mark Lotarino','Printworld','lotarinoryan@gmail.com','09956139821',NULL,'Dalie','[{\"service_type\":\"Tarpaulin Printing\",\"item_name\":\"Tarpaulin Printing\",\"description\":\"Tarpaulin Printing - 5ft x 7ft (Already Have Design)\",\"quantity\":1,\"details\":{\"width\":5,\"height\":7,\"design\":\"Already Have Design\"},\"id\":\"item_1773679194072\"}]',NULL,6.74966300,125.35093520,'','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Request_RQ-20260316-302A6.pdf','quoted','2026-03-16 16:39:56'),(3,'RQ-20260317-3364E','Ryan Mark Lotarino','Printworld','lotarinoryan@gmail.com','09956139821',NULL,'','[{\"service_type\":\"Tarpaulin Printing\",\"item_name\":\"Tarpaulin Printing\",\"description\":\"Tarpaulin Printing - 4ft x 8ft (Already Have Design)\",\"quantity\":1,\"details\":{\"width\":4,\"height\":8,\"design\":\"Already Have Design\"},\"id\":\"item_1773739239127\"}]',NULL,NULL,NULL,'','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Request_RQ-20260317-3364E.pdf','quoted','2026-03-17 09:20:42'),(4,'RQ-20260317-286A2','Ryan Mark Lotarino','Printworld','lotarinoryan@gmail.com','09956139821','Digos City','','[{\"service_type\":\"basic\",\"item_name\":\"Keychain\",\"slug\":\"keychain\",\"description\":\"Keychain (I have a design)\",\"quantity\":50,\"details\":{\"has_design\":true}}]',NULL,NULL,NULL,'','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Request_RQ-20260317-286A2.pdf','quoted','2026-03-17 11:17:29'),(5,'RQ-20260317-62F74','Ryan Mark Lotarino','Mr DIY','lotarinoryan@gmail.com','09956139821','Digos City','','[{\"service_type\":\"signage\",\"item_name\":\"Acrylic\",\"slug\":\"acrylic\",\"description\":\"Acrylic — Build Up Type — Non-lighted — 8ft × 9ft — Design: No\",\"quantity\":1,\"details\":{\"option\":\"Build Up Type\",\"light\":\"Non-lighted\",\"width\":8,\"height\":9,\"has_design\":false}}]','uploads/designs/design_69b93ed35ccc4.jpg',NULL,NULL,'','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Request_RQ-20260317-62F74.pdf','quoted','2026-03-17 11:45:23'),(6,'RQ-20260317-BE441','Bongbong','BBBB','bbb@gmail.com','09956139821','Digos City, Davao del Sur','','[{\"service_type\":\"signage\",\"item_name\":\"Billboard\",\"slug\":\"billboard\",\"description\":\"Billboard — Single Frame — Non-lighted — 5ft × 6ft — Design: Yes\",\"quantity\":1,\"details\":{\"option\":\"Single Frame\",\"light\":\"Non-lighted\",\"width\":5,\"height\":6,\"has_design\":true}}]',NULL,NULL,NULL,'','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Request_RQ-20260317-BE441.pdf','quoted','2026-03-17 12:12:28'),(7,'RQ-20260317-CC047','Ryan Mark Lotarino','Mr DIY','lotarinoryan@gmail.com','09956139821','Digos City, Davao del Sur','','[{\"service_type\":\"sublimation\",\"item_name\":\"Polo Shirt\",\"slug\":\"polo-shirt\",\"description\":\"Polo Shirt — Design: Yes\",\"quantity\":12,\"details\":{\"has_design\":true}}]',NULL,NULL,NULL,'','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Request_RQ-20260317-CC047.pdf','quoted','2026-03-17 12:27:02'),(8,'RQ-20260317-F22A1','Ryan Mark Lotarino','Printworld','lotarinoryan@gmail.com','09956139821','Digos City, Davao del Sur','Naa koy design','[{\"service_type\":\"basic\",\"item_name\":\"Tarpaulin\",\"slug\":\"tarpaulin\",\"description\":\"Tarpaulin — 8ft × 9ft\",\"quantity\":1,\"details\":{\"width\":8,\"height\":9,\"unit\":\"ft\"}}]',NULL,NULL,NULL,'','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Request_RQ-20260317-F22A1.pdf','quoted','2026-03-17 12:42:14'),(9,'RQ-20260317-ECC71','Ryan Mark Lotarino','Printworld','lotarinoryan@gmail.com','09956139821','Digos City, Davao del Sur','hhhh','[{\"service_type\":\"basic\",\"item_name\":\"Mug\",\"slug\":\"mug\",\"description\":\"Mug\",\"quantity\":52,\"details\":{}}]',NULL,NULL,NULL,'','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Request_RQ-20260317-ECC71.pdf','quoted','2026-03-17 12:48:27'),(10,'RQ-20260317-55EC5','Ryan Mark Lotarino','Mr DIY','lotarinoryan@gmail.com','09956139821','Digos City, Davao del Sur','hhh','[{\"service_type\":\"basic\",\"item_name\":\"Ref Magnets\",\"slug\":\"ref-magnets\",\"description\":\"Ref Magnets\",\"quantity\":80,\"details\":{}}]',NULL,NULL,NULL,'','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Request_RQ-20260317-55EC5.pdf','quoted','2026-03-17 12:54:44'),(11,'RQ-20260318-DB24A','Ryan Mark Lotarino','Printworld','lotarinoryan@gmail.com','09956139821','Digos City, Davao del Sur','','[{\"service_type\":\"basic\",\"item_name\":\"Tarpaulin\",\"slug\":\"tarpaulin\",\"description\":\"Tarpaulin — 5ft × 9ft\",\"quantity\":1,\"details\":{\"width\":5,\"height\":9,\"unit\":\"ft\"}}]',NULL,NULL,NULL,'','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Request_RQ-20260318-DB24A.pdf','quoted','2026-03-18 10:56:50'),(12,'RQ-20260320-D166B','Ryan Mark Lotarino','Printworld','lotarinoryan@gmail.com','09956139821','Digos City','','[{\"service_type\":\"basic\",\"item_name\":\"Keychain\",\"slug\":\"keychain\",\"description\":\"Keychain\",\"quantity\":14,\"details\":{}}]',NULL,NULL,NULL,'','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Request_RQ-20260320-D166B.pdf','quoted','2026-03-20 05:19:08'),(13,'RQ-20260320-487B6','Ryan Mark Lotarino','Mr DIY','lotarinoryan@gmail.com','09956139821','Digos City','','[{\"service_type\":\"sublimation\",\"item_name\":\"Polo Shirt\",\"slug\":\"polo-shirt\",\"description\":\"Polo Shirt\",\"quantity\":56,\"details\":{}}]',NULL,NULL,NULL,'','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Request_RQ-20260320-487B6.pdf','quoted','2026-03-20 05:29:10'),(14,'RQ-20260320-5F1F8','Ryan Mark Lotarino','Mr DIY','lotarinoryan@gmail.com','09956139821','Digos City, Davao del Sur','','[{\"service_type\":\"basic\",\"item_name\":\"Keychain\",\"slug\":\"keychain\",\"description\":\"Keychain\",\"quantity\":50,\"details\":{}}]',NULL,NULL,NULL,'','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Request_RQ-20260320-5F1F8.pdf','quoted','2026-03-20 05:39:11'),(15,'RQ-20260321-7E1F6','Ryan Mark Lotarino','Mr DIY','lotarinoryan@gmail.com','09956139821','Digos City, Davao del Sur','','[{\"service_type\":\"basic\",\"item_name\":\"Tarpaulin\",\"slug\":\"tarpaulin\",\"description\":\"Tarpaulin - 5ft x 3ft\",\"quantity\":1,\"details\":{\"width\":5,\"height\":3,\"unit\":\"ft\"}}]','uploads/designs/design_69bdfe74779d1.jpg',NULL,NULL,'','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Request_RQ-20260321-7E1F6.pdf','viewed','2026-03-21 02:12:04');
/*!40000 ALTER TABLE `quotation_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotations`
--

DROP TABLE IF EXISTS `quotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quotations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quotation_number` varchar(20) NOT NULL,
  `customer_name` varchar(200) NOT NULL,
  `company_name` varchar(200) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `services_json` longtext NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','viewed','responded','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `quotation_number` (`quotation_number`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotations`
--

LOCK TABLES `quotations` WRITE;
/*!40000 ALTER TABLE `quotations` DISABLE KEYS */;
INSERT INTO `quotations` VALUES (1,'QT-20260316-B5077','Ryan Mark Lotarino','Printworld','lotarinoryan@gmail.com','09956139821','[{\"id\":\"sign_1773667704792\",\"type\":\"Signage\",\"description\":\"Signage 5ft × 7ft — Double Face, Lighted\",\"quantity\":1,\"unit_price\":15750,\"subtotal\":15750,\"details\":{\"signage_type\":\"Double Face\",\"light_type\":\"Lighted\",\"width\":5,\"height\":7,\"price_per_sqft\":450,\"lat\":\"6.7496902\",\"lng\":\"125.3508879\",\"address\":\"\"}}]',15750.00,'','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260316-B5077.pdf','pending','2026-03-16 13:28:26'),(2,'QT-20260316-4B30E','RYAN','Printworld','lotarinoryan@gmail.com','09956139821','[{\"id\":\"sign_1773668268318\",\"type\":\"Signage\",\"description\":\"Signage 4ft × 7ft — Double Face, Lighted\",\"quantity\":1,\"unit_price\":12600,\"subtotal\":12600,\"details\":{\"signage_type\":\"Double Face\",\"light_type\":\"Lighted\",\"width\":4,\"height\":7,\"price_per_sqft\":450,\"lat\":\"6.7497501\",\"lng\":\"125.3508745\",\"address\":\"\"}}]',12600.00,'','C:\\xampp\\printworld\\printing-shop/uploads/quotations/Quotation_QT-20260316-4B30E.pdf','pending','2026-03-16 13:38:17');
/*!40000 ALTER TABLE `quotations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `request_items`
--

DROP TABLE IF EXISTS `request_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `request_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `service_type` varchar(100) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `item_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`item_details`)),
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`),
  CONSTRAINT `request_items_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `quotation_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `request_items`
--

LOCK TABLES `request_items` WRITE;
/*!40000 ALTER TABLE `request_items` DISABLE KEYS */;
INSERT INTO `request_items` VALUES (1,1,'Signage','Signage','6ft x 8ft Single Face - Non-lighted',1,'{\"signage_type\":\"Single Face\",\"light_type\":\"Non-lighted\",\"width\":6,\"height\":8,\"lat\":\"6.7496832\",\"lng\":\"125.3509429\",\"address\":\"\"}'),(2,2,'Tarpaulin Printing','Tarpaulin Printing','Tarpaulin Printing - 5ft x 7ft (Already Have Design)',1,'{\"width\":5,\"height\":7,\"design\":\"Already Have Design\"}'),(3,3,'Tarpaulin Printing','Tarpaulin Printing','Tarpaulin Printing - 4ft x 8ft (Already Have Design)',1,'{\"width\":4,\"height\":8,\"design\":\"Already Have Design\"}'),(4,4,'basic','Keychain','Keychain (I have a design)',50,'{\"has_design\":true}'),(5,5,'signage','Acrylic','Acrylic — Build Up Type — Non-lighted — 8ft × 9ft — Design: No',1,'{\"option\":\"Build Up Type\",\"light\":\"Non-lighted\",\"width\":8,\"height\":9,\"has_design\":false}'),(6,6,'signage','Billboard','Billboard — Single Frame — Non-lighted — 5ft × 6ft — Design: Yes',1,'{\"option\":\"Single Frame\",\"light\":\"Non-lighted\",\"width\":5,\"height\":6,\"has_design\":true}'),(7,7,'sublimation','Polo Shirt','Polo Shirt — Design: Yes',12,'{\"has_design\":true}'),(8,8,'basic','Tarpaulin','Tarpaulin — 8ft × 9ft',1,'{\"width\":8,\"height\":9,\"unit\":\"ft\"}'),(9,9,'basic','Mug','Mug',52,'[]'),(10,10,'basic','Ref Magnets','Ref Magnets',80,'[]'),(11,11,'basic','Tarpaulin','Tarpaulin — 5ft × 9ft',1,'{\"width\":5,\"height\":9,\"unit\":\"ft\"}'),(12,12,'basic','Keychain','Keychain',14,'[]'),(13,13,'sublimation','Polo Shirt','Polo Shirt',56,'[]'),(14,14,'basic','Keychain','Keychain',50,'[]'),(15,15,'basic','Tarpaulin','Tarpaulin - 5ft x 3ft',1,'{\"width\":5,\"height\":3,\"unit\":\"ft\"}');
/*!40000 ALTER TABLE `request_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_categories`
--

DROP TABLE IF EXISTS `service_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `category` varchar(50) DEFAULT 'basic',
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_categories`
--

LOCK TABLES `service_categories` WRITE;
/*!40000 ALTER TABLE `service_categories` DISABLE KEYS */;
INSERT INTO `service_categories` VALUES (8,'T-Shirt','tshirt','Full-color sublimation on T-shirts','fa-tshirt',1,10,'sublimation',NULL),(9,'Polo Shirt','polo-shirt','Sublimation on polo shirts','fa-tshirt',1,11,'sublimation',NULL),(10,'Sport Jersey','sport-jersey','Custom sport jerseys','fa-tshirt',1,12,'sublimation',NULL),(11,'Short','short','Sublimation on shorts','fa-tshirt',1,13,'sublimation',NULL),(12,'Long Sleeve','long-sleeve','Sublimation on long sleeve shirts','fa-tshirt',1,14,'sublimation',NULL),(13,'Jacket','jacket','Custom printed jackets','fa-tshirt',1,15,'sublimation',NULL),(14,'Acrylic','acrylic','Custom acrylic signage','fa-sign-hanging',1,20,'signage',NULL),(15,'Stainless','stainless','Stainless steel signage','fa-sign-hanging',1,21,'signage',NULL),(16,'Panaflex','panaflex','Panaflex signage and lightboxes','fa-sign-hanging',1,22,'signage',NULL),(17,'Billboard','billboard','Large-format billboard printing','fa-sign-hanging',1,23,'signage',NULL),(18,'Keychain','keychain','Personalized keychains for any occasion','fa-key',1,0,'basic',NULL),(19,'Mug','mug','Custom printed mugs — perfect for gifts and promotions','fa-mug-hot',1,1,'basic',NULL),(20,'Invitation','invitation','Custom printed invitations for weddings, birthdays, and events','fa-gift',1,2,'basic',NULL),(21,'Keyholder','keyholder','Custom keyholders with your design or logo','fa-key',1,3,'basic',NULL),(22,'Ref Magnets','ref-magnets','Custom refrigerator magnets for promos and giveaways','fa-magnet',1,4,'basic',NULL),(23,'Tarpaulin','tarpaulin','High-quality tarpaulin printing for events and promotions','fa-image',1,5,'basic',NULL),(24,'Sintraboard','sintraboard','Durable sintraboard prints for indoor and outdoor use','fa-border-all',1,6,'basic',NULL),(35,'Lanyard','-anyard','','fa-print',1,0,'basic',NULL),(46,'Neon Lights','neon','','fa-sign-hanging',1,0,'signage',NULL),(49,'ID','id','','fa-id-card',1,0,'basic',NULL);
/*!40000 ALTER TABLE `service_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `signage_color_codes`
--

DROP TABLE IF EXISTS `signage_color_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `signage_color_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `signage_id` int(11) NOT NULL,
  `signage_label` varchar(150) DEFAULT NULL,
  `color_code` varchar(50) NOT NULL,
  `color_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_signage_id` (`signage_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `signage_color_codes`
--

LOCK TABLES `signage_color_codes` WRITE;
/*!40000 ALTER TABLE `signage_color_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `signage_color_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `signage_light_options`
--

DROP TABLE IF EXISTS `signage_light_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `signage_light_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_slug` varchar(100) NOT NULL,
  `light_label` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug_light` (`service_slug`,`light_label`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `signage_light_options`
--

LOCK TABLES `signage_light_options` WRITE;
/*!40000 ALTER TABLE `signage_light_options` DISABLE KEYS */;
INSERT INTO `signage_light_options` VALUES (8,'-ignage','Lighted'),(1,'acrylic','Lighted'),(2,'acrylic','Non-lighted'),(7,'billboard','Non-lighted'),(10,'neon','Lighted'),(5,'panaflex','Lighted'),(6,'panaflex','Non-lighted'),(9,'signage','Lighted'),(3,'stainless','Lighted'),(4,'stainless','Non-lighted');
/*!40000 ALTER TABLE `signage_light_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `signage_locations`
--

DROP TABLE IF EXISTS `signage_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `signage_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quotation_id` int(11) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `address` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quotation_id` (`quotation_id`),
  CONSTRAINT `signage_locations_ibfk_1` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `signage_locations`
--

LOCK TABLES `signage_locations` WRITE;
/*!40000 ALTER TABLE `signage_locations` DISABLE KEYS */;
INSERT INTO `signage_locations` VALUES (1,1,6.74969020,125.35088790,''),(2,2,6.74975010,125.35087450,'');
/*!40000 ALTER TABLE `signage_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `signage_type_options`
--

DROP TABLE IF EXISTS `signage_type_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `signage_type_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_slug` varchar(100) NOT NULL,
  `type_label` varchar(100) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug_label` (`service_slug`,`type_label`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `signage_type_options`
--

LOCK TABLES `signage_type_options` WRITE;
/*!40000 ALTER TABLE `signage_type_options` DISABLE KEYS */;
INSERT INTO `signage_type_options` VALUES (1,'acrylic','Flat Type',0),(2,'acrylic','Build Up Type',1),(3,'acrylic','Build Up Type with Cladding',2),(4,'stainless','Flat Type',0),(5,'stainless','Build Up Type',1),(6,'stainless','Build Up Type with Cladding',2),(7,'panaflex','Single Face',0),(8,'panaflex','Double Face',1),(9,'panaflex','Single Frame',2),(10,'panaflex','Double Face Frame',3),(11,'panaflex','Special Design',4),(12,'billboard','Single Frame',0),(13,'billboard','Double Face Frame',1),(14,'-ignage','Single face with Acrylic',0),(15,'signage','Single face with Acrylic',0);
/*!40000 ALTER TABLE `signage_type_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_content`
--

DROP TABLE IF EXISTS `site_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content_key` varchar(100) NOT NULL,
  `content_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `content_key` (`content_key`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_content`
--

LOCK TABLES `site_content` WRITE;
/*!40000 ALTER TABLE `site_content` DISABLE KEYS */;
INSERT INTO `site_content` VALUES (1,'hero_title','Printworld Advertising Services','2026-03-17 13:35:13'),(2,'hero_subtitle','Highest in Quality, Lowest in Prices.','2026-03-17 13:35:13'),(3,'about_title','About Printworld','2026-03-16 13:31:29'),(4,'about_text','We are a professional printing shop dedicated to delivering high-quality printing services. With years of experience, we bring your ideas to life with precision and creativity.','2026-03-16 13:21:57'),(5,'contact_address','Roxas Ext., Fronting UM 1st Gate, Digos City','2026-03-16 16:49:38'),(6,'contact_phone','0910 772 8888','2026-03-16 16:49:38'),(7,'contact_email','digosprinting@gmail.com','2026-03-16 16:49:38'),(8,'contact_hours','Mon-Sun: 9AM - 6PM','2026-03-16 16:50:07'),(9,'facebook_url','https://www.facebook.com/DigosTarpaulinPrinting','2026-03-16 14:39:48'),(18,'quotation_tnc','Full payment must be made within 30 calendar days from project completion.\nPrintworld shall not be held liable for any acts of God that may occur before or during delivery, installation or execution of materials.\nSignages for this project will be installed before the store opening.\nPrintworld will tap to the nearest electricity supply up to 2 meters in excess to this provision will be charged to client.\n10% weekly interest will be charged as penalty for late payment.\nAny intentional scratches or damages on the product will void the warranty.\n(5) years of Avery Sticker warranty\n(6) months of LED warranty.\n(1) year of faulty workmanship.','2026-03-16 17:25:18'),(20,'tnc_basic','Full payment must be made within 30 calendar days from project completion.\r\nPrintworld shall not be held liable for any acts of God that may occur before or during delivery, installation or execution of materials.\r\n10% weekly interest will be charged as penalty for late payment.\r\nAny intentional scratches or damages on the product will void the warranty.\r\n(5) years of Avery Sticker warranty.','2026-03-20 05:28:35'),(21,'tnc_sublimation','Full payment must be made within 30 calendar days from project completion.\r\nPrintworld shall not be held liable for any acts of God that may occur before or during delivery, installation or execution of materials.\r\n10% weekly interest will be charged as penalty for late payment.\r\nAny intentional scratches or damages on the product will void the warranty.\r\nSublimation colors may slightly vary due to fabric type and printing process.','2026-03-20 05:28:35'),(22,'tnc_signage','Full payment must be made within 30 calendar days from project completion.\r\nPrintworld shall not be held liable for any acts of God that may occur before or during delivery, installation or execution of materials.\r\nSignages for this project will be installed before the store opening.\r\nPrintworld will tap to the nearest electricity supply up to 2 meters in excess to this provision will be charged to client.\r\n10% weekly interest will be charged as penalty for late payment.\r\nAny intentional scratches or damages on the product will void the warranty.\r\n(5) years of Avery Sticker warranty.\r\n(6) months of LED warranty.\r\n(1) year of faulty workmanship.','2026-03-20 05:28:35');
/*!40000 ALTER TABLE `site_content` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-27 21:36:43
