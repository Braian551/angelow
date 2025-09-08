-- MySQL dump 10.13  Distrib 8.0.43, for Win64 (x86_64)
--
-- Host: localhost    Database: angelow
-- ------------------------------------------------------
-- Server version	8.0.43

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `access_tokens`
--

DROP TABLE IF EXISTS `access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `access_tokens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  `is_revoked` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `access_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `access_tokens`
--

LOCK TABLES `access_tokens` WRITE;
/*!40000 ALTER TABLE `access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_categories`
--

DROP TABLE IF EXISTS `audit_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_categories` (
  `audit_id` int NOT NULL AUTO_INCREMENT,
  `category_id` int DEFAULT NULL,
  `action_type` varchar(10) DEFAULT NULL,
  `old_name` varchar(100) DEFAULT NULL,
  `new_name` varchar(100) DEFAULT NULL,
  `action_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`audit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_categories`
--

LOCK TABLES `audit_categories` WRITE;
/*!40000 ALTER TABLE `audit_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_orders`
--

DROP TABLE IF EXISTS `audit_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `orden_id` int NOT NULL,
  `accion` varchar(10) NOT NULL,
  `usuario_id` varchar(20) DEFAULT NULL,
  `sql_usuario` varchar(255) DEFAULT NULL,
  `fecha` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `detalles` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_orders`
--

LOCK TABLES `audit_orders` WRITE;
/*!40000 ALTER TABLE `audit_orders` DISABLE KEYS */;
INSERT INTO `audit_orders` VALUES (1,5,'INSERT','6861e06ddcf49','root@localhost','2025-08-20 02:37:44','Se creó la orden #TEST001 con total $0.00');
/*!40000 ALTER TABLE `audit_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_users`
--

DROP TABLE IF EXISTS `audit_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` varchar(20) NOT NULL,
  `accion` varchar(10) NOT NULL,
  `usuario_modificador` varchar(20) DEFAULT NULL,
  `sql_usuario` varchar(255) DEFAULT NULL,
  `fecha` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `detalles` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_users`
--

LOCK TABLES `audit_users` WRITE;
/*!40000 ALTER TABLE `audit_users` DISABLE KEYS */;
INSERT INTO `audit_users` VALUES (1,'TEST_USER_001','INSERT','TEST_USER_001','root@localhost','2025-08-20 02:53:23','Se creó el usuario: Usuario Prueba (prueba@ejemplo.com). Rol: customer'),(2,'TEST_USER_001','UPDATE','TEST_USER_001','root@localhost','2025-08-20 02:53:23','Usuario actualizado. Cambios: Nombre: Usuario Prueba → Usuario Modificado. Rol: customer → admin. '),(3,'TEST_USER_001','DELETE','TEST_USER_001','root@localhost','2025-08-20 02:53:24','Se eliminó el usuario: Usuario Modificado (prueba@ejemplo.com). Rol: admin');
/*!40000 ALTER TABLE `audit_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bulk_discount_rules`
--

DROP TABLE IF EXISTS `bulk_discount_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bulk_discount_rules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `min_quantity` int NOT NULL,
  `max_quantity` int DEFAULT NULL,
  `discount_percentage` decimal(5,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_quantity_range` (`min_quantity`,`max_quantity`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bulk_discount_rules`
--

LOCK TABLES `bulk_discount_rules` WRITE;
/*!40000 ALTER TABLE `bulk_discount_rules` DISABLE KEYS */;
INSERT INTO `bulk_discount_rules` VALUES (1,30,50,10.00,1,'2025-07-27 15:50:11','2025-07-27 15:50:11'),(2,51,NULL,20.00,1,'2025-07-27 15:50:38','2025-07-27 15:50:46');
/*!40000 ALTER TABLE `bulk_discount_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart_items`
--

DROP TABLE IF EXISTS `cart_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cart_id` int NOT NULL,
  `product_id` int NOT NULL,
  `color_variant_id` int DEFAULT NULL,
  `size_variant_id` int DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cart_id` (`cart_id`),
  KEY `product_id` (`product_id`),
  KEY `color_variant_id` (`color_variant_id`),
  KEY `size_variant_id` (`size_variant_id`),
  CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_items_ibfk_3` FOREIGN KEY (`color_variant_id`) REFERENCES `product_color_variants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cart_items_ibfk_4` FOREIGN KEY (`size_variant_id`) REFERENCES `product_size_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart_items`
--

LOCK TABLES `cart_items` WRITE;
/*!40000 ALTER TABLE `cart_items` DISABLE KEYS */;
INSERT INTO `cart_items` VALUES (17,3,61,10,19,2,'2025-07-21 18:38:37','2025-07-25 11:47:21'),(19,3,61,9,17,4,'2025-07-22 11:12:53','2025-07-25 11:47:27');
/*!40000 ALTER TABLE `cart_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `carts`
--

DROP TABLE IF EXISTS `carts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `carts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `session_id` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carts`
--

LOCK TABLES `carts` WRITE;
/*!40000 ALTER TABLE `carts` DISABLE KEYS */;
INSERT INTO `carts` VALUES (3,'6861e06ddcf49',NULL,'2025-06-29 19:55:24','2025-06-29 19:55:24');
/*!40000 ALTER TABLE `carts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `parent_id` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Vestidos','vestidos','Vestidos infantiles para ocasiones especiales',NULL,NULL,1,'2025-06-21 21:11:42','2025-06-21 21:11:42'),(2,'Conjuntos','conjuntos','Conjuntos de ropa coordinados',NULL,NULL,1,'2025-06-21 21:11:42','2025-06-21 21:11:42'),(3,'Pijamas','pijamas','Pijamas y ropa para dormir',NULL,NULL,1,'2025-06-21 21:11:42','2025-07-25 19:43:54'),(4,'Ropa Deportiva','ropa-deportiva','Ropa para actividades físicas',NULL,NULL,1,'2025-06-21 21:11:42','2025-06-21 21:11:42'),(5,'Accesorios','accesorios','Complementos y accesorios infantiles',NULL,NULL,1,'2025-06-21 21:11:42','2025-07-25 19:57:36'),(6,'Ropa Casual','ropa-casual','Ropa informal para el día a día',NULL,NULL,1,'2025-06-21 21:11:42','2025-06-21 21:11:42'),(7,'Ropa Formal','ropa-formal','Ropa para eventos especiales',NULL,NULL,1,'2025-06-21 21:11:42','2025-06-21 21:11:42'),(8,'Ropa de Baño','ropa-de-bano','Trajes de baño y ropa playera',NULL,NULL,1,'2025-06-21 21:11:42','2025-06-21 21:11:42');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_categories_insert` AFTER INSERT ON `categories` FOR EACH ROW INSERT INTO audit_categories(category_id, action_type, new_name)
VALUES (NEW.id, 'INSERT', NEW.name) */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_categories_update` AFTER UPDATE ON `categories` FOR EACH ROW INSERT INTO audit_categories(category_id, action_type, old_name, new_name)
VALUES (OLD.id, 'UPDATE', OLD.name, NEW.name) */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_categories_delete` AFTER DELETE ON `categories` FOR EACH ROW INSERT INTO audit_categories(category_id, action_type, old_name)
VALUES (OLD.id, 'DELETE', OLD.name) */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `collections`
--

DROP TABLE IF EXISTS `collections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `collections` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` mediumtext COLLATE utf8mb4_general_ci,
  `image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `launch_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collections`
--

LOCK TABLES `collections` WRITE;
/*!40000 ALTER TABLE `collections` DISABLE KEYS */;
INSERT INTO `collections` VALUES (1,'Verano Mágico','verano-magico','Colección de verano con colores vibrantes y diseños frescos',NULL,'2025-05-01',1,'2025-06-29 00:15:58','2025-06-29 00:15:58'),(2,'Aventura Infantil','aventura-infantil','Ropa cómoda y resistente para pequeños exploradores',NULL,'2025-04-15',1,'2025-06-29 00:15:58','2025-06-29 00:15:58'),(3,'Dulces Sueños','dulces-suenos','Pijamas y ropa de dormir ultra suaves',NULL,'2025-03-20',1,'2025-06-29 00:15:58','2025-06-29 00:15:58'),(4,'Colección Clásica','coleccion-clasica','Diseños atemporales para ocasiones especiales',NULL,'2025-01-10',1,'2025-06-29 00:15:58','2025-06-29 00:15:58'),(5,'Mini Trendsetters','mini-trendsetters','Las últimas tendencias en moda infantil',NULL,'2025-06-01',1,'2025-06-29 00:15:58','2025-06-29 00:15:58');
/*!40000 ALTER TABLE `collections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `colombian_banks`
--

DROP TABLE IF EXISTS `colombian_banks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `colombian_banks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bank_code` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `bank_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `bank_code` (`bank_code`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `colombian_banks`
--

LOCK TABLES `colombian_banks` WRITE;
/*!40000 ALTER TABLE `colombian_banks` DISABLE KEYS */;
INSERT INTO `colombian_banks` VALUES (1,'001','Banco de Bogotá',1),(2,'002','Banco Popular',1),(3,'006','Banco Santander',1),(4,'007','BBVA Colombia',1),(5,'009','Citibank',1),(6,'012','Banco GNB Sudameris',1),(7,'013','Banco AV Villas',1),(8,'014','Banco de Occidente',1),(9,'019','Bancoomeva',1),(10,'023','Banco Itaú',1),(11,'031','Bancolombia',1),(12,'032','Banco Caja Social',1),(13,'040','Banco Agrario de Colombia',1),(14,'051','Bancamía',1),(15,'052','Banco WWB',1),(16,'053','Banco Falabella',1),(17,'054','Banco Pichincha',1),(18,'058','Banco ProCredit',1),(19,'059','Banco Mundo Mujer',1),(20,'060','Banco Finandina',1),(21,'061','Bancoomeva S.A.',1),(22,'062','Banco Davivienda',1),(23,'063','Banco Cooperativo Coopcentral',1),(24,'065','Banco Santander',1),(25,'101','Nequi',1),(26,'102','Daviplata',1),(27,'103','Movii',1);
/*!40000 ALTER TABLE `colombian_banks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `colors`
--

DROP TABLE IF EXISTS `colors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `colors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `hex_code` varchar(7) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `colors`
--

LOCK TABLES `colors` WRITE;
/*!40000 ALTER TABLE `colors` DISABLE KEYS */;
INSERT INTO `colors` VALUES (1,'Blanco','#FFFFFF',1,'2025-06-21 19:10:34'),(2,'Negro','#000000',1,'2025-06-21 19:10:34'),(3,'Rojo','#FF0000',1,'2025-06-21 19:10:34'),(4,'Azul','#0000FF',1,'2025-06-21 19:10:34'),(5,'Azul Marino','#000080',1,'2025-06-21 19:10:34'),(6,'Azul Cielo','#87CEEB',1,'2025-06-21 19:10:34'),(7,'Rosado','#FFC0CB',1,'2025-06-21 19:10:34'),(8,'Rosado Pastel','#FFD1DC',1,'2025-06-21 19:10:34'),(9,'Morado','#800080',1,'2025-06-21 19:10:34'),(10,'Lila','#C8A2C8',1,'2025-06-21 19:10:34'),(11,'Amarillo','#FFFF00',1,'2025-06-21 19:10:34'),(12,'Amarillo Pastel','#FFFACD',1,'2025-06-21 19:10:34'),(13,'Verde','#008000',1,'2025-06-21 19:10:34'),(14,'Verde Mentha','#98FF98',1,'2025-06-21 19:10:34'),(15,'Naranja','#FFA500',1,'2025-06-21 19:10:34'),(16,'Melón','#FDBCB4',1,'2025-06-21 19:10:34'),(17,'Gris','#808080',1,'2025-06-21 19:10:34'),(18,'Beige','#F5F5DC',1,'2025-06-21 19:10:34'),(19,'Café','#A52A2A',1,'2025-06-21 19:10:34'),(20,'Estampado',NULL,1,'2025-06-21 19:10:34');
/*!40000 ALTER TABLE `colors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `delivery_cities`
--

DROP TABLE IF EXISTS `delivery_cities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_cities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `city_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `city_name` (`city_name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_cities`
--

LOCK TABLES `delivery_cities` WRITE;
/*!40000 ALTER TABLE `delivery_cities` DISABLE KEYS */;
INSERT INTO `delivery_cities` VALUES (1,'Medellín',1,'2025-06-30 09:13:02');
/*!40000 ALTER TABLE `delivery_cities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discount_code_products`
--

DROP TABLE IF EXISTS `discount_code_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `discount_code_products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `discount_code_id` int NOT NULL,
  `product_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `discount_product` (`discount_code_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `discount_code_products_ibfk_1` FOREIGN KEY (`discount_code_id`) REFERENCES `discount_codes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `discount_code_products_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discount_code_products`
--

LOCK TABLES `discount_code_products` WRITE;
/*!40000 ALTER TABLE `discount_code_products` DISABLE KEYS */;
/*!40000 ALTER TABLE `discount_code_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discount_code_usage`
--

DROP TABLE IF EXISTS `discount_code_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `discount_code_usage` (
  `id` int NOT NULL AUTO_INCREMENT,
  `discount_code_id` int NOT NULL,
  `user_id` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `order_id` int DEFAULT NULL,
  `used_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `discount_code_id` (`discount_code_id`),
  KEY `user_id` (`user_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `discount_code_usage_ibfk_1` FOREIGN KEY (`discount_code_id`) REFERENCES `discount_codes` (`id`),
  CONSTRAINT `discount_code_usage_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `discount_code_usage_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discount_code_usage`
--

LOCK TABLES `discount_code_usage` WRITE;
/*!40000 ALTER TABLE `discount_code_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `discount_code_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discount_codes`
--

DROP TABLE IF EXISTS `discount_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `discount_codes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `discount_type_id` int NOT NULL,
  `max_uses` int DEFAULT NULL,
  `used_count` int DEFAULT '0',
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `is_single_use` tinyint(1) DEFAULT '0',
  `created_by` varchar(20) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'ID del admin que lo creó',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `discount_type_id` (`discount_type_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `discount_codes_ibfk_1` FOREIGN KEY (`discount_type_id`) REFERENCES `discount_types` (`id`),
  CONSTRAINT `discount_codes_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discount_codes`
--

LOCK TABLES `discount_codes` WRITE;
/*!40000 ALTER TABLE `discount_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `discount_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discount_types`
--

DROP TABLE IF EXISTS `discount_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `discount_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discount_types`
--

LOCK TABLES `discount_types` WRITE;
/*!40000 ALTER TABLE `discount_types` DISABLE KEYS */;
INSERT INTO `discount_types` VALUES (1,'Porcentaje','Descuento porcentual sobre el total',1,'2025-07-27 17:38:50','2025-07-27 17:38:50'),(2,'Monto fijo','Descuento de monto fijo',1,'2025-07-27 17:38:50','2025-07-27 17:38:50'),(3,'Envío gratis','Descuento para envío gratuito',1,'2025-07-27 17:38:50','2025-07-27 17:38:50');
/*!40000 ALTER TABLE `discount_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `eliminaciones_auditoria`
--

DROP TABLE IF EXISTS `eliminaciones_auditoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eliminaciones_auditoria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `accion` varchar(50) NOT NULL DEFAULT 'Eliminado',
  `fecha_eliminacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `eliminaciones_auditoria`
--

LOCK TABLES `eliminaciones_auditoria` WRITE;
/*!40000 ALTER TABLE `eliminaciones_auditoria` DISABLE KEYS */;
/*!40000 ALTER TABLE `eliminaciones_auditoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fixed_amount_discounts`
--

DROP TABLE IF EXISTS `fixed_amount_discounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fixed_amount_discounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `discount_code_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `discount_code_id` (`discount_code_id`),
  CONSTRAINT `fk_fixed_discount_code` FOREIGN KEY (`discount_code_id`) REFERENCES `discount_codes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fixed_amount_discounts`
--

LOCK TABLES `fixed_amount_discounts` WRITE;
/*!40000 ALTER TABLE `fixed_amount_discounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `fixed_amount_discounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `free_shipping_discounts`
--

DROP TABLE IF EXISTS `free_shipping_discounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `free_shipping_discounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `discount_code_id` int NOT NULL,
  `shipping_method_id` int DEFAULT NULL COMMENT 'NULL para todos los métodos',
  PRIMARY KEY (`id`),
  UNIQUE KEY `discount_code_id` (`discount_code_id`),
  CONSTRAINT `fk_shipping_discount_code` FOREIGN KEY (`discount_code_id`) REFERENCES `discount_codes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `free_shipping_discounts`
--

LOCK TABLES `free_shipping_discounts` WRITE;
/*!40000 ALTER TABLE `free_shipping_discounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `free_shipping_discounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `google_auth`
--

DROP TABLE IF EXISTS `google_auth`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `google_auth` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `google_id` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `access_token` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `google_auth_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `google_auth`
--

LOCK TABLES `google_auth` WRITE;
/*!40000 ALTER TABLE `google_auth` DISABLE KEYS */;
INSERT INTO `google_auth` VALUES (4,'6860007924a6a','100021586628962750893','ya29.a0AS3H6NxWuxsKzvVZ78hlXIpNbkEuyBhlqCM-TAZVUOGequUWV3a07XgX6zOV1CBF5qW5qfR_7FaFucKHLluMZBfjTZw_MhKSVmbokJERwtzQbROc1a4BocIWIQ0ZL_W40z-KWYjh0I9SLbEtH3W2B_XqUlIn12l0fHRav4jESwaCgYKAZwSARESFQHGX2MihiMSealW_Ok6ItYSjWrP7Q0177','2025-07-09 18:18:26');
/*!40000 ALTER TABLE `google_auth` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci NOT NULL,
  `attempt_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_date` (`attempt_date`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
INSERT INTO `login_attempts` VALUES (1,'braianoquen@gmail.com','::1','2025-07-20 11:18:27'),(2,'3013636902','::1','2025-07-21 15:18:28');
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news`
--

DROP TABLE IF EXISTS `news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `news` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `content` text COLLATE utf8mb4_general_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_published` (`published_at`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news`
--

LOCK TABLES `news` WRITE;
/*!40000 ALTER TABLE `news` DISABLE KEYS */;
/*!40000 ALTER TABLE `news` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_preferences`
--

DROP TABLE IF EXISTS `notification_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_preferences` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `type_id` int NOT NULL,
  `email_enabled` tinyint(1) DEFAULT '1',
  `sms_enabled` tinyint(1) DEFAULT '0',
  `push_enabled` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_type` (`user_id`,`type_id`),
  KEY `type_id` (`type_id`),
  CONSTRAINT `notification_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notification_preferences_ibfk_2` FOREIGN KEY (`type_id`) REFERENCES `notification_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_preferences`
--

LOCK TABLES `notification_preferences` WRITE;
/*!40000 ALTER TABLE `notification_preferences` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_preferences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_queue`
--

DROP TABLE IF EXISTS `notification_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_queue` (
  `id` int NOT NULL AUTO_INCREMENT,
  `notification_id` int NOT NULL,
  `channel` enum('email','sms','push') COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('pending','processing','sent','failed') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `attempts` tinyint DEFAULT '0',
  `last_attempt_at` datetime DEFAULT NULL,
  `scheduled_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `sent_at` datetime DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `notification_id` (`notification_id`),
  KEY `status` (`status`),
  CONSTRAINT `notification_queue_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_queue`
--

LOCK TABLES `notification_queue` WRITE;
/*!40000 ALTER TABLE `notification_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_types`
--

DROP TABLE IF EXISTS `notification_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `template` text COLLATE utf8mb4_general_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_types`
--

LOCK TABLES `notification_types` WRITE;
/*!40000 ALTER TABLE `notification_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `type_id` int NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `message` text COLLATE utf8mb4_general_ci NOT NULL,
  `related_entity_type` enum('order','product','promotion','system','account') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `related_entity_id` int DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `is_email_sent` tinyint(1) DEFAULT '0',
  `is_sms_sent` tinyint(1) DEFAULT '0',
  `is_push_sent` tinyint(1) DEFAULT '0',
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `type_id` (`type_id`),
  KEY `related_entity` (`related_entity_type`,`related_entity_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`type_id`) REFERENCES `notification_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `color_variant_id` int DEFAULT NULL,
  `size_variant_id` int DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `variant_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  KEY `color_variant_id` (`color_variant_id`),
  KEY `size_variant_id` (`size_variant_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_3` FOREIGN KEY (`color_variant_id`) REFERENCES `product_color_variants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_items_ibfk_4` FOREIGN KEY (`size_variant_id`) REFERENCES `product_size_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_number` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `invoice_number` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_id` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled','refunded') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `subtotal` decimal(10,2) NOT NULL,
  `tax` decimal(10,2) DEFAULT '0.00',
  `shipping_cost` decimal(10,2) DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_status` enum('pending','paid','failed','refunded') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `shipping_address` text COLLATE utf8mb4_general_ci,
  `shipping_city` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `delivery_notes` text COLLATE utf8mb4_general_ci,
  `billing_address` text COLLATE utf8mb4_general_ci,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `invoice_resolution` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Resolución DIAN para facturación',
  `invoice_date` datetime DEFAULT NULL,
  `client_identification` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Documento del cliente',
  `client_phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (5,'TEST001',NULL,'6861e06ddcf49','pending',0.00,0.00,0.00,0.00,NULL,'pending',NULL,NULL,NULL,NULL,NULL,'2025-08-19 21:37:44','2025-08-19 21:37:44',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `auditoria_orden_insert` AFTER INSERT ON `orders` FOR EACH ROW BEGIN
  INSERT INTO audit_orders (orden_id, accion, usuario_id, sql_usuario, detalles)
  VALUES (
    NEW.id, 
    'INSERT', 
    NEW.user_id,          
    CURRENT_USER(),       
    CONCAT('Se creó la orden #', NEW.order_number, ' con total $', NEW.total)
  );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `auditoria_orden_update` AFTER UPDATE ON `orders` FOR EACH ROW BEGIN
  IF (NEW.status != OLD.status OR NEW.total != OLD.total) THEN
    INSERT INTO audit_orders (orden_id, accion, usuario_id, sql_usuario, detalles)
    VALUES (
      NEW.id, 
      'UPDATE', 
      NEW.user_id,         
      CURRENT_USER(),    
      CONCAT('Orden actualizada. Estado: ', OLD.status, ' → ', NEW.status, 
             '. Total: $', OLD.total, ' → $', NEW.total)
    );
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `auditoria_orden_delete` BEFORE DELETE ON `orders` FOR EACH ROW BEGIN
  INSERT INTO audit_orders (orden_id, accion, usuario_id, sql_usuario, detalles)
  VALUES (
    OLD.id, 
    'DELETE', 
    OLD.user_id,         
    CURRENT_USER(),        
    CONCAT('Se eliminó la orden #', OLD.order_number, ' con total $', OLD.total)
  );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_transactions`
--

DROP TABLE IF EXISTS `payment_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int DEFAULT NULL,
  `user_id` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('transferencia','contra_entrega','pse','efectivo') COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('pending','completed','failed','refunded') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `reference_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `account_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `account_type` enum('ahorros','corriente') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `account_holder` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_proof` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `payment_transactions_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payment_transactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_transactions`
--

LOCK TABLES `payment_transactions` WRITE;
/*!40000 ALTER TABLE `payment_transactions` DISABLE KEYS */;
INSERT INTO `payment_transactions` VALUES (3,NULL,'6861e06ddcf49',105000.00,'transferencia','pending','12313131','Banco Finandina','1231331','ahorros','Braian Oquendo','uploads/payment_proofs/6863680e6fd16_braian-caricatura.jpg','adjfufdcusadhfi','2025-06-30 23:46:06','2025-06-30 23:46:06');
/*!40000 ALTER TABLE `payment_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `percentage_discounts`
--

DROP TABLE IF EXISTS `percentage_discounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `percentage_discounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `discount_code_id` int NOT NULL,
  `percentage` decimal(5,2) NOT NULL,
  `max_discount_amount` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `discount_code_id` (`discount_code_id`),
  CONSTRAINT `fk_percentage_discount_code` FOREIGN KEY (`discount_code_id`) REFERENCES `discount_codes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `percentage_discounts`
--

LOCK TABLES `percentage_discounts` WRITE;
/*!40000 ALTER TABLE `percentage_discounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `percentage_discounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `popular_searches`
--

DROP TABLE IF EXISTS `popular_searches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `popular_searches` (
  `id` int NOT NULL AUTO_INCREMENT,
  `search_term` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `search_count` int NOT NULL DEFAULT '1',
  `last_searched` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `search_term` (`search_term`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `popular_searches`
--

LOCK TABLES `popular_searches` WRITE;
/*!40000 ALTER TABLE `popular_searches` DISABLE KEYS */;
INSERT INTO `popular_searches` VALUES (1,'ropa deportiva de niños',1,'2025-07-21 17:16:30'),(2,'ropa',4,'2025-08-10 15:59:00'),(4,'Ropa deportiva',2,'2025-08-10 15:59:08');
/*!40000 ALTER TABLE `popular_searches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_color_variants`
--

DROP TABLE IF EXISTS `product_color_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_color_variants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `color_id` int DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `color_id` (`color_id`),
  CONSTRAINT `product_color_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_color_variants_ibfk_2` FOREIGN KEY (`color_id`) REFERENCES `colors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_color_variants`
--

LOCK TABLES `product_color_variants` WRITE;
/*!40000 ALTER TABLE `product_color_variants` DISABLE KEYS */;
INSERT INTO `product_color_variants` VALUES (9,61,2,1),(10,61,18,0);
/*!40000 ALTER TABLE `product_color_variants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `color_variant_id` int DEFAULT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `alt_text` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `order` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `is_primary` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `color_variant_id` (`color_variant_id`),
  CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_images_ibfk_2` FOREIGN KEY (`color_variant_id`) REFERENCES `product_color_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=92 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_images`
--

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
INSERT INTO `product_images` VALUES (91,61,NULL,'uploads/productos/687d07be0095b_deportivo.jpg','Ropa deportiva - Imagen principal',0,'2025-07-20 10:14:06',1);
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_questions`
--

DROP TABLE IF EXISTS `product_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_questions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `user_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `question` text COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_questions`
--

LOCK TABLES `product_questions` WRITE;
/*!40000 ALTER TABLE `product_questions` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_reviews`
--

DROP TABLE IF EXISTS `product_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `user_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `order_id` int DEFAULT NULL COMMENT 'Para verificar compra',
  `rating` tinyint(1) NOT NULL COMMENT '1-5 estrellas',
  `title` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `comment` text COLLATE utf8mb4_general_ci NOT NULL,
  `images` text COLLATE utf8mb4_general_ci COMMENT 'JSON de imágenes subidas',
  `is_verified` tinyint(1) DEFAULT '0' COMMENT 'Compra verificada',
  `is_approved` tinyint(1) DEFAULT '1' COMMENT 'Moderación',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_reviews`
--

LOCK TABLES `product_reviews` WRITE;
/*!40000 ALTER TABLE `product_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_size_variants`
--

DROP TABLE IF EXISTS `product_size_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_size_variants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `color_variant_id` int NOT NULL,
  `size_id` int DEFAULT NULL,
  `sku` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `barcode` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `compare_price` decimal(10,2) DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `color_variant_id` (`color_variant_id`),
  KEY `size_id` (`size_id`),
  CONSTRAINT `product_size_variants_ibfk_1` FOREIGN KEY (`color_variant_id`) REFERENCES `product_color_variants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_size_variants_ibfk_2` FOREIGN KEY (`size_id`) REFERENCES `sizes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_size_variants`
--

LOCK TABLES `product_size_variants` WRITE;
/*!40000 ALTER TABLE `product_size_variants` DISABLE KEYS */;
INSERT INTO `product_size_variants` VALUES (16,9,1,'O',NULL,35000.00,NULL,10,1),(17,9,2,'P',NULL,35000.00,NULL,20,1),(18,10,1,'O',NULL,35000.00,NULL,20,1),(19,10,2,'P',NULL,35000.00,NULL,10,1);
/*!40000 ALTER TABLE `product_size_variants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos_auditoria`
--

DROP TABLE IF EXISTS `productos_auditoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos_auditoria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `accion` varchar(50) NOT NULL DEFAULT 'Creado',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos_auditoria`
--

LOCK TABLES `productos_auditoria` WRITE;
/*!40000 ALTER TABLE `productos_auditoria` DISABLE KEYS */;
INSERT INTO `productos_auditoria` VALUES (1,'Camiseta','Creado','2025-09-03 14:23:04','2025-09-03 14:23:04'),(2,'Pantalón','Creado','2025-09-03 14:23:04','2025-09-03 14:23:04');
/*!40000 ALTER TABLE `productos_auditoria` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `tr_insert_producto` BEFORE INSERT ON `productos_auditoria` FOR EACH ROW BEGIN
    SET NEW.created_at = CURRENT_TIMESTAMP;
    SET NEW.accion = 'Creado';
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `tr_update_producto` BEFORE UPDATE ON `productos_auditoria` FOR EACH ROW BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
    SET NEW.accion = 'Modificado';
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `tr_delete_producto` BEFORE DELETE ON `productos_auditoria` FOR EACH ROW BEGIN
    INSERT INTO eliminaciones_auditoria (nombre)
    VALUES (OLD.nombre);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `brand` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gender` enum('niño','niña','bebe','unisex') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'unisex',
  `collection` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `material` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `care_instructions` text COLLATE utf8mb4_general_ci,
  `compare_price` decimal(10,2) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `category_id` int NOT NULL,
  `collection_id` int DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  KEY `fk_product_collection` (`collection_id`),
  CONSTRAINT `fk_product_collection` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (61,'Ropa deportiva','ropa-deportiva','¡Comodidad, estilo y libertad de movimiento!\r\nEste conjunto deportivo de la Colección Clásica de Angelow es ideal para los pequeños que no paran. Fabricado en 100% algodón, ofrece frescura y suavidad, cuidando la piel de tu hijo durante todo el día.','angelow','niño',NULL,'100% algodon','Para conservar la calidad y durabilidad de esta prenda, se recomienda lavarla a máquina con agua fría y colores similares. No usar blanqueador, ya que puede dañar las fibras del algodón. Secar a baja temperatura o al aire libre para evitar el encogimiento. Si es necesario, planchar a temperatura media. No lavar en seco.',NULL,35000.00,4,4,0,1,'2025-07-20 10:14:06','2025-07-20 10:14:06');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `question_answers`
--

DROP TABLE IF EXISTS `question_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `question_answers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `question_id` int NOT NULL,
  `user_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Puede ser vendedor o usuario',
  `answer` text COLLATE utf8mb4_general_ci NOT NULL,
  `is_seller` tinyint(1) DEFAULT '0' COMMENT '1=respuesta del vendedor',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_answers`
--

LOCK TABLES `question_answers` WRITE;
/*!40000 ALTER TABLE `question_answers` DISABLE KEYS */;
/*!40000 ALTER TABLE `question_answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `review_votes`
--

DROP TABLE IF EXISTS `review_votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_votes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `review_id` int NOT NULL,
  `user_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `is_helpful` tinyint(1) NOT NULL COMMENT '1=útil, 0=no útil',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_review` (`user_id`,`review_id`),
  KEY `review_id` (`review_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `review_votes`
--

LOCK TABLES `review_votes` WRITE;
/*!40000 ALTER TABLE `review_votes` DISABLE KEYS */;
/*!40000 ALTER TABLE `review_votes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `search_history`
--

DROP TABLE IF EXISTS `search_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `search_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `search_term` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `search_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `search_history`
--

LOCK TABLES `search_history` WRITE;
/*!40000 ALTER TABLE `search_history` DISABLE KEYS */;
INSERT INTO `search_history` VALUES (1,'6861e06ddcf49','da','2025-07-17 19:11:43'),(2,'6861e06ddcf49','ropa deportiva de niños','2025-07-21 17:16:30'),(3,'6861e06ddcf49','ropa','2025-08-10 15:58:59'),(4,'6861e06ddcf49','ropa','2025-08-10 15:58:59'),(5,'6861e06ddcf49','Ropa deportiva','2025-08-10 15:59:08');
/*!40000 ALTER TABLE `search_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('S7Q6FdzNOJz68rB8xyis0eOG9cOG9OxHszxndIjm',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoibjFNQ3U1UDNxN2xqeElla3dQeTN3S2RzVlRWdkJIMUsyc3FZN2FZQyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzc6Imh0dHA6Ly9sb2NhbGhvc3QvYW5nZWxvdy9wdWJsaWMvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1755574461);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipping_price_rules`
--

DROP TABLE IF EXISTS `shipping_price_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipping_price_rules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `min_price` decimal(10,2) NOT NULL,
  `max_price` decimal(10,2) DEFAULT NULL,
  `shipping_cost` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_price_range` (`min_price`,`max_price`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipping_price_rules`
--

LOCK TABLES `shipping_price_rules` WRITE;
/*!40000 ALTER TABLE `shipping_price_rules` DISABLE KEYS */;
INSERT INTO `shipping_price_rules` VALUES (1,50000.00,100000.00,8000.00,1,'2025-07-27 15:17:16','2025-07-27 15:21:17'),(2,0.00,49999.00,20000.00,1,'2025-07-27 15:19:03','2025-07-27 15:19:03');
/*!40000 ALTER TABLE `shipping_price_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sizes`
--

DROP TABLE IF EXISTS `sizes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sizes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sizes`
--

LOCK TABLES `sizes` WRITE;
/*!40000 ALTER TABLE `sizes` DISABLE KEYS */;
INSERT INTO `sizes` VALUES (1,'XS','Extra Small',1,'2025-06-21 20:47:14'),(2,'S','Small',1,'2025-06-21 20:47:14'),(3,'M','Medium',1,'2025-06-21 20:47:14'),(4,'L','Large',1,'2025-06-21 20:47:14'),(5,'XL','Extra Large',1,'2025-06-21 20:47:14'),(6,'XXL','Double Extra Large',1,'2025-06-21 20:47:14'),(7,'3XL','Triple Extra Large',1,'2025-06-21 20:47:14');
/*!40000 ALTER TABLE `sizes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_history`
--

DROP TABLE IF EXISTS `stock_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `variant_id` int NOT NULL,
  `user_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `previous_qty` int NOT NULL,
  `new_qty` int NOT NULL,
  `operation` enum('add','subtract','set','transfer_in','transfer_out') COLLATE utf8mb4_general_ci NOT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `variant_id` (`variant_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_history`
--

LOCK TABLES `stock_history` WRITE;
/*!40000 ALTER TABLE `stock_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_addresses`
--

DROP TABLE IF EXISTS `user_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_addresses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `address_type` enum('casa','apartamento','oficina','otro') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'casa',
  `alias` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `recipient_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `recipient_phone` varchar(15) COLLATE utf8mb4_general_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `complement` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `neighborhood` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `building_type` enum('casa','apartamento','edificio','conjunto','local') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'casa',
  `building_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `apartment_number` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `delivery_instructions` text COLLATE utf8mb4_general_ci,
  `is_default` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_user_addresses_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_addresses`
--

LOCK TABLES `user_addresses` WRITE;
/*!40000 ALTER TABLE `user_addresses` DISABLE KEYS */;
INSERT INTO `user_addresses` VALUES (1,'6861e06ddcf49','casa','Hogar','Braian Oquendo','3013636902','Cra 16D #57 B 162',NULL,'Enciso','casa',NULL,NULL,'llamar al llegar',1,1,'2025-07-12 22:25:54','2025-07-22 14:57:41'),(2,'6861e06ddcf49','oficina','Trabajo','Braian Oquendo','3013636902','Cra 16D #57 B 163','Bloque 3','Belen','edificio','El miranda','210','llamar antes de llegar',0,1,'2025-07-13 17:18:49','2025-07-22 14:57:41');
/*!40000 ALTER TABLE `user_addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `identification_type` enum('cc','ce','ti','pasaporte') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'cc',
  `identification_number` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` enum('customer','admin','delivery') COLLATE utf8mb4_general_ci DEFAULT 'customer',
  `is_blocked` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_access` datetime DEFAULT NULL,
  `remember_token` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES ('6860007924a6a','Braian','braianoquen@gmail.com',NULL,'cc',NULL,'$2y$10$safkUgrODd3iixhDIq/y9eG7RnlUq.I3MAq3OsG4PXOsT7bZoss76',NULL,'admin',0,'2025-06-28 09:47:21','2025-08-24 19:28:34','2025-08-24 19:28:34',NULL,NULL),('6861e06ddcf49','Braian','braianoquendurango@gmail.com','3013636902','cc','1023526011','$2y$10$K5B1CBsezIVKb2osCQrgEuTwIr.JMvG2EVPUYZqIhS9yzuboS8prq',NULL,'customer',0,'2025-06-29 19:55:10','2025-08-10 15:58:37','2025-08-10 15:58:37',NULL,NULL),('6862b7448112f','Juan','braianoquen2@gmail.com',NULL,'cc',NULL,'$2y$10$lIkReeDLfMBHL7Mj2Vqrk.0LhoLlVboNNliNulgXzEiIrrexwMtrS',NULL,'delivery',0,'2025-06-30 11:11:48','2025-06-30 11:25:30','2025-06-30 11:25:30',NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `auditoria_usuario_insert` AFTER INSERT ON `users` FOR EACH ROW BEGIN
  INSERT INTO audit_users (usuario_id, accion, usuario_modificador, sql_usuario, detalles)
  VALUES (
    NEW.id, 
    'INSERT', 
    NEW.id, -- En una inserción, el usuario que se crea es el mismo que "se modifica"
    CURRENT_USER(),       
    CONCAT('Se creó el usuario: ', NEW.name, ' (', NEW.email, '). Rol: ', NEW.role)
  );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `auditoria_usuario_update` AFTER UPDATE ON `users` FOR EACH ROW BEGIN
  DECLARE cambios TEXT DEFAULT '';
  
  -- Verificar cambios en los campos principales
  IF (NEW.name != OLD.name) THEN
    SET cambios = CONCAT(cambios, 'Nombre: ', OLD.name, ' → ', NEW.name, '. ');
  END IF;
  
  IF (NEW.email != OLD.email) THEN
    SET cambios = CONCAT(cambios, 'Email: ', OLD.email, ' → ', NEW.email, '. ');
  END IF;
  
  IF (NEW.role != OLD.role) THEN
    SET cambios = CONCAT(cambios, 'Rol: ', OLD.role, ' → ', NEW.role, '. ');
  END IF;
  
  IF (NEW.is_blocked != OLD.is_blocked) THEN
    SET cambios = CONCAT(cambios, 'Bloqueo: ', OLD.is_blocked, ' → ', NEW.is_blocked, '. ');
  END IF;
  
  IF (NEW.phone != OLD.phone OR (NEW.phone IS NULL AND OLD.phone IS NOT NULL) OR (NEW.phone IS NOT NULL AND OLD.phone IS NULL)) THEN
    SET cambios = CONCAT(cambios, 'Teléfono: ', COALESCE(OLD.phone, 'NULL'), ' → ', COALESCE(NEW.phone, 'NULL'), '. ');
  END IF;
  
  -- Solo registrar si hubo cambios relevantes
  IF (LENGTH(cambios) > 0) THEN
    INSERT INTO audit_users (usuario_id, accion, usuario_modificador, sql_usuario, detalles)
    VALUES (
      NEW.id, 
      'UPDATE', 
      NEW.id, -- Asumiendo que el usuario se modifica a sí mismo o hay otro sistema de tracking
      CURRENT_USER(),    
      CONCAT('Usuario actualizado. Cambios: ', cambios)
    );
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `auditoria_usuario_delete` BEFORE DELETE ON `users` FOR EACH ROW BEGIN
  INSERT INTO audit_users (usuario_id, accion, usuario_modificador, sql_usuario, detalles)
  VALUES (
    OLD.id, 
    'DELETE', 
    OLD.id, -- El usuario que se elimina
    CURRENT_USER(),        
    CONCAT('Se eliminó el usuario: ', OLD.name, ' (', OLD.email, '). Rol: ', OLD.role)
  );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `variant_images`
--

DROP TABLE IF EXISTS `variant_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `variant_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `color_variant_id` int NOT NULL,
  `product_id` int NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `alt_text` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `order` int DEFAULT '0',
  `is_primary` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `color_variant_id` (`color_variant_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `variant_images_ibfk_1` FOREIGN KEY (`color_variant_id`) REFERENCES `product_color_variants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `variant_images_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `variant_images`
--

LOCK TABLES `variant_images` WRITE;
/*!40000 ALTER TABLE `variant_images` DISABLE KEYS */;
INSERT INTO `variant_images` VALUES (26,9,61,'uploads/productos/687d07be0106c_conjunto_niño2.jpg','Ropa deportiva - Imagen 1',0,1,'2025-07-20 10:14:06'),(27,9,61,'uploads/productos/687d07be015ec_deportivo.jpg','Ropa deportiva - Imagen 2',1,0,'2025-07-20 10:14:06'),(28,10,61,'uploads/productos/687d07be2aff9_coleccion primavera.jpg','Ropa deportiva - Imagen 1',0,1,'2025-07-20 10:14:06'),(29,10,61,'uploads/productos/687d07be3da3f_conjunto_niño.jpg','Ropa deportiva - Imagen 2',1,0,'2025-07-20 10:14:06'),(30,10,61,'uploads/productos/687d07be3e4d3_deportivo2.jpg','Ropa deportiva - Imagen 3',2,0,'2025-07-20 10:14:06'),(31,10,61,'uploads/productos/687d07be3ef1c_simba.jpg','Ropa deportiva - Imagen 4',3,0,'2025-07-20 10:14:06');
/*!40000 ALTER TABLE `variant_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wishlist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `product_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_product` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlist`
--

LOCK TABLES `wishlist` WRITE;
/*!40000 ALTER TABLE `wishlist` DISABLE KEYS */;
/*!40000 ALTER TABLE `wishlist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'angelow'
--

--
-- Dumping routines for database 'angelow'
--
/*!50003 DROP PROCEDURE IF EXISTS `add_category` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_category`(IN cat_name VARCHAR(100))
BEGIN
    INSERT INTO categories(name) VALUES (cat_name);
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `GenerateUserNotifications` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `GenerateUserNotifications`(IN p_user_id VARCHAR(20))
BEGIN
    -- Declarar variables
    DECLARE v_notification_text TEXT DEFAULT '';
    DECLARE v_order_count INT DEFAULT 0;
    DECLARE v_cart_item_count INT DEFAULT 0;
    DECLARE v_current_date DATETIME DEFAULT CURRENT_TIMESTAMP; -- 12:04 PM -05, 27 de agosto de 2025

    -- Crear tabla temporal para almacenar notificaciones
    CREATE TEMPORARY TABLE IF NOT EXISTS temp_notifications (
        message TEXT NOT NULL
    );

    -- Contar pedidos pendientes (status no completado)
    SELECT COUNT(*) INTO v_order_count
    FROM orders o
    WHERE o.user_id = p_user_id 
    AND o.status NOT IN ('completed', 'cancelled');

    -- Contar artículos en el carrito
    SELECT COUNT(*) INTO v_cart_item_count
    FROM cart_items ci
    JOIN carts c ON ci.cart_id = c.id
    WHERE c.user_id = p_user_id;

    -- Insertar notificación de encabezado
    INSERT INTO temp_notifications (message)
    VALUES (CONCAT('Notificaciones para ', (SELECT name FROM users WHERE id = p_user_id), ' (', p_user_id, '):\n'));

    -- Notificación de pedidos pendientes
    IF v_order_count > 0 THEN
        INSERT INTO temp_notifications (message)
        VALUES (CONCAT('- Tienes ', v_order_count, ' pedido(s) pendiente(s) por procesar.\n'));
        
        -- Verificar pedidos próximos a vencer (ejemplo: dentro de 3 días)
        INSERT INTO temp_notifications (message)
        SELECT CONCAT('- Atención: El pedido #', o.order_number, ' vence el ', DATE(o.expires_at), '.\n')
        FROM orders o
        WHERE o.user_id = p_user_id 
        AND o.status NOT IN ('completed', 'cancelled')
        AND DATEDIFF(o.expires_at, v_current_date) <= 3
        AND DATEDIFF(o.expires_at, v_current_date) >= 0;
    ELSE
        INSERT INTO temp_notifications (message)
        VALUES ('- No tienes pedidos pendientes.\n');
    END IF;

    -- Notificación de artículos en el carrito
    IF v_cart_item_count > 0 THEN
        INSERT INTO temp_notifications (message)
        VALUES (CONCAT('- Tienes ', v_cart_item_count, ' artículo(s) en tu carrito. ¡Completa tu compra!\n'));
    ELSE
        INSERT INTO temp_notifications (message)
        VALUES ('- Tu carrito está vacío.\n');
    END IF;

    -- Devolver todas las notificaciones
    SELECT GROUP_CONCAT(message SEPARATOR '') AS notifications
    FROM temp_notifications;

    -- Limpiar tabla temporal
    DROP TEMPORARY TABLE IF EXISTS temp_notifications;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `GetFilteredProducts` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetFilteredProducts`(
    IN p_search_query VARCHAR(255),
    IN p_category_id INT,
    IN p_gender VARCHAR(10),
    IN p_min_price DECIMAL(10,2),
    IN p_max_price DECIMAL(10,2),
    IN p_sort_by VARCHAR(20),
    IN p_limit INT,
    IN p_offset INT,
    IN p_user_id VARCHAR(20)
)
BEGIN
    -- Consulta principal para obtener productos
    SELECT 
        p.id, 
        p.name, 
        p.slug, 
        p.description,
        p.gender,
        p.category_id,
        p.is_featured,
        p.created_at,
        pi.image_path as primary_image,
        MIN(psv.price) as min_price,
        MAX(psv.price) as max_price,
        IFNULL((SELECT COUNT(*) FROM wishlist w WHERE w.user_id = p_user_id AND w.product_id = p.id), 0) as is_favorite,
        IFNULL((SELECT AVG(rating) FROM product_reviews pr WHERE pr.product_id = p.id AND pr.is_approved = 1), 0) as avg_rating,
        IFNULL((SELECT COUNT(*) FROM product_reviews pr WHERE pr.product_id = p.id AND pr.is_approved = 1), 0) as review_count
    FROM 
        products p
    LEFT JOIN 
        product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    LEFT JOIN 
        product_color_variants pcv ON p.id = pcv.product_id
    LEFT JOIN 
        product_size_variants psv ON pcv.id = psv.color_variant_id
    WHERE 
        p.is_active = 1
        AND (p_search_query IS NULL OR p_search_query = '' OR p.name LIKE CONCAT('%', p_search_query, '%') OR p.description LIKE CONCAT('%', p_search_query, '%'))
        AND (p_category_id IS NULL OR p.category_id = p_category_id)
        AND (p_gender IS NULL OR p_gender = '' OR p.gender = p_gender)
        AND (p_min_price IS NULL OR psv.price >= p_min_price)
        AND (p_max_price IS NULL OR psv.price <= p_max_price)
    GROUP BY 
        p.id
    ORDER BY
        CASE WHEN p_sort_by = 'price_asc' THEN MIN(psv.price) END ASC,
        CASE WHEN p_sort_by = 'price_desc' THEN MIN(psv.price) END DESC,
        CASE WHEN p_sort_by = 'name_asc' THEN p.name END ASC,
        CASE WHEN p_sort_by = 'name_desc' THEN p.name END DESC,
        CASE WHEN p_sort_by = 'popular' THEN p.is_featured END DESC,
        p.is_featured DESC,
        p.created_at DESC
    LIMIT p_limit OFFSET p_offset;

    -- Consulta para contar el total de productos
    SELECT COUNT(DISTINCT p.id) as total
    FROM products p
    LEFT JOIN product_color_variants pcv ON p.id = pcv.product_id
    LEFT JOIN product_size_variants psv ON pcv.id = psv.color_variant_id
    WHERE p.is_active = 1
        AND (p_search_query IS NULL OR p_search_query = '' OR p.name LIKE CONCAT('%', p_search_query, '%') OR p.description LIKE CONCAT('%', p_search_query, '%'))
        AND (p_category_id IS NULL OR p.category_id = p_category_id)
        AND (p_gender IS NULL OR p_gender = '' OR p.gender = p_gender)
        AND (p_min_price IS NULL OR psv.price >= p_min_price)
        AND (p_max_price IS NULL OR psv.price <= p_max_price);
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `GetUserByEmail` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetUserByEmail`(
    IN p_email VARCHAR(255)
)
BEGIN
    SELECT 
        u.id,
        u.name,
        u.email,
        u.phone,
        u.role,
        u.is_active,
        u.created_at,
        u.updated_at
    FROM users u
    WHERE u.email = p_email
    LIMIT 1; 
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_categories` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_categories`()
BEGIN
    SELECT * FROM categories;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `SearchProductsAndTerms` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `SearchProductsAndTerms`(IN `p_search_term` VARCHAR(255), IN `p_user_id` VARCHAR(20))
BEGIN
    -- Variables para manejar resultados
    DECLARE history_count INT;
    
    -- Resultados de productos coincidentes
    SELECT 
        p.id, 
        p.name, 
        p.slug, 
        pi.image_path
    FROM 
        products p
    LEFT JOIN 
        product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    WHERE 
        (p.name LIKE CONCAT('%', p_search_term, '%') OR p.description LIKE CONCAT('%', p_search_term, '%')) 
        AND p.is_active = 1
    LIMIT 5;
    
    -- Obtener términos de búsqueda del historial del usuario
    CREATE TEMPORARY TABLE IF NOT EXISTS temp_history_terms AS
    SELECT DISTINCT search_term 
    FROM search_history 
    WHERE user_id = p_user_id
    AND search_term LIKE CONCAT(p_search_term, '%')
    AND search_term IS NOT NULL
    AND search_term != ''
    ORDER BY created_at DESC
    LIMIT 6;
    
    -- Contar cuántos términos hay en el historial
    SELECT COUNT(*) INTO history_count FROM temp_history_terms;
    
    -- Si no hay suficientes términos, buscar en nombres de productos
    IF history_count < 4 THEN
        SELECT DISTINCT name 
        FROM products 
        WHERE name LIKE CONCAT('%', p_search_term, '%') AND is_active = 1
        LIMIT 4;
    ELSE
        SELECT * FROM temp_history_terms LIMIT 4;
    END IF;
    
    -- Limpiar tabla temporal
    DROP TEMPORARY TABLE IF EXISTS temp_history_terms;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-09-03  9:29:43
