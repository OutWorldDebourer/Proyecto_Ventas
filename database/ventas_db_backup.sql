-- MySQL dump 10.13  Distrib 9.0.1, for Win64 (x86_64)
--
-- Host: localhost    Database: ventas_db
-- ------------------------------------------------------
-- Server version	9.0.1

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
-- Table structure for table `anulados`
--

DROP TABLE IF EXISTS `anulados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `anulados` (
  `id` int NOT NULL AUTO_INCREMENT,
  `venta_id` int NOT NULL,
  `fecha_anulacion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `venta_id` (`venta_id`),
  CONSTRAINT `anulados_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `anulados`
--

LOCK TABLES `anulados` WRITE;
/*!40000 ALTER TABLE `anulados` DISABLE KEYS */;
/*!40000 ALTER TABLE `anulados` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compras`
--

DROP TABLE IF EXISTS `compras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `compras` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL,
  `cantidad` int NOT NULL,
  `tipo_doc` enum('DNI','RUC','OTROS') NOT NULL DEFAULT 'DNI',
  `numero_doc` varchar(20) DEFAULT NULL,
  `direccion_fiscal` varchar(255) DEFAULT NULL,
  `telefonos` varchar(100) DEFAULT NULL,
  `emails` varchar(255) DEFAULT NULL,
  `otros` text,
  `fecha_compra` datetime DEFAULT CURRENT_TIMESTAMP,
  `monto` decimal(10,2) NOT NULL DEFAULT '0.00',
  `moneda` varchar(10) NOT NULL DEFAULT 'USD',
  `tipo_cambio` decimal(10,4) NOT NULL DEFAULT '1.0000',
  `entidad` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `compras_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compras`
--

LOCK TABLES `compras` WRITE;
/*!40000 ALTER TABLE `compras` DISABLE KEYS */;
INSERT INTO `compras` VALUES (1,4,50,'DNI','71309287','','942961598','m.taira@polisakura.org','','2024-09-18 17:56:51',0.00,'USD',1.0000,NULL),(2,1,50,'RUC','10713092873','Urb. Alejandro Delgado','942961598,2460931','masahiko987@gmail.com,masahiko.taira.yoshidaira@gmail.com','LOL','2024-09-18 18:00:39',0.00,'USD',1.0000,NULL),(3,6,50,'DNI','10713092','','942961598','masahiko987@gmail.com','','2024-09-18 18:22:21',0.00,'USD',1.0000,NULL),(4,1,50,'DNI','10713092','','942961598','masahiko987@gmail.com','','2024-09-18 18:22:49',0.00,'USD',1.0000,NULL),(5,2,50,'RUC','10713092873','Urb Alejandro Delgado, Sauna Las Rocas, Huaral 15202','2460931,942961598','masahiko987@gmail.com,masahiko_taira@usmp.pe','Con estas modificaciones, ahora en almacen.php podrás ver el stock actual de cada producto o servicio en el desplegable de Producto o Servicio. Esto te permitirá tener una mejor visión del inventario al registrar nuevas compras, asegurando una gestión más eficiente y precisa de tu almacén.\r\n\r\nSi necesitas más asistencia o tienes alguna otra modificación en mente, ¡no dudes en preguntar!','2024-09-18 18:37:30',0.00,'USD',1.0000,NULL),(6,3,9,'DNI','71309287','','','','','2024-09-22 19:13:10',0.00,'USD',1.0000,NULL),(7,25,50,'DNI','71309287','','942961598','masahiko987@gmail.com','Increíble','2024-09-24 13:21:46',0.00,'USD',1.0000,NULL),(8,8,100,'DNI','71309287','','942961598','masahiko987@gmail.com','','2024-09-24 14:58:58',0.00,'USD',1.0000,NULL),(9,1,100,'OTROS','','','942961598','masahiko987@gmail.com','','2024-09-24 15:24:22',0.00,'USD',1.0000,NULL),(10,2,6,'OTROS','','','','','','2024-09-24 15:27:13',0.00,'USD',1.0000,NULL),(11,2,6,'OTROS','','','','','','2024-09-24 15:32:35',0.00,'USD',1.0000,NULL),(12,8,3,'DNI','','','','','','2024-09-24 15:33:01',0.00,'USD',1.0000,NULL),(13,4,2,'OTROS','','','','','','2024-09-24 15:33:35',0.00,'USD',1.0000,NULL),(14,5,5,'DNI','','','','','','2024-09-24 15:54:48',5.00,'PEN',1.0000,NULL),(15,2,4,'RUC','10713092873','Urb Alejandro Delgado, Sauna Las Rocas, Huaral 15202','2460931,942961598','masahiko987@gmail.com,masahiko_taira@usmp.pe','LOL','2024-09-24 16:56:51',8000.00,'PEN',1.0000,'Masahiko'),(16,3,7,'DNI','','','','','','2024-09-24 17:31:40',21000.00,'PEN',1.0000,''),(17,1,5,'RUC','10713092873','Urb Alejandro Delgado, Sauna Las Rocas, Huaral 15202','2460931,942961598','masahiko987@gmail.com,masahiko_taira@usmp.pe','LOL','2024-09-24 17:33:05',1000.00,'PEN',1.0000,'Masahiko'),(18,1,100,'RUC','10713092873','Urb Alejandro Delgado, Sauna Las Rocas, Huaral 15202','2460931','masahiko987@gmail.com','','2024-09-25 15:53:35',1000.00,'PEN',1.0000,'Masahiko');
/*!40000 ALTER TABLE `compras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `historial`
--

DROP TABLE IF EXISTS `historial`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `historial` (
  `id` int NOT NULL AUTO_INCREMENT,
  `venta_id` int NOT NULL,
  `accion` varchar(255) NOT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `usuario` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `venta_id` (`venta_id`),
  CONSTRAINT `historial_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `historial`
--

LOCK TABLES `historial` WRITE;
/*!40000 ALTER TABLE `historial` DISABLE KEYS */;
INSERT INTO `historial` VALUES (1,3,'Registrada venta ID 3','2024-09-17 17:58:48','Administrador'),(2,4,'Registrada venta ID 4','2024-09-17 18:06:26','Administrador'),(3,5,'Registrada venta ID 5','2024-09-17 18:20:36','Administrador'),(4,6,'Registrada venta ID 6','2024-09-17 18:32:24','Administrador'),(5,7,'Registrada venta ID 7','2024-09-18 12:07:49','Administrador'),(6,8,'Registrada venta ID 8','2024-09-18 18:43:49','Administrador'),(7,9,'Registrada venta ID 9','2024-09-18 18:44:21','Administrador'),(8,10,'Registrada venta ID 10','2024-09-18 18:45:18','Administrador'),(9,11,'Registrada venta ID 11','2024-09-18 18:57:06','Administrador'),(10,12,'Registrada venta ID 12','2024-09-18 19:04:40','Administrador'),(11,13,'Registrada venta ID 13','2024-09-18 19:10:53','Administrador'),(12,14,'Registrada venta ID 14','2024-09-18 19:11:30','Administrador'),(13,15,'Registrada venta ID 15','2024-09-18 19:43:47','Administrador'),(14,16,'Registrada venta ID 16','2024-09-18 19:44:00','Administrador'),(15,17,'Registrada venta ID 17','2024-09-18 19:51:32','Administrador'),(16,18,'Registrada venta ID 18','2024-09-18 20:10:37','Administrador'),(17,19,'Registrada venta ID 19','2024-09-18 20:10:47','Administrador'),(18,20,'Registrada venta ID 20','2024-09-22 19:18:38','Administrador'),(19,22,'Registrada venta ID 22','2024-09-22 19:38:38','Administrador'),(20,22,'Anulada venta ID 22','2024-09-22 19:56:49','Administrador'),(21,22,'Anulada venta ID 22','2024-09-22 19:58:22','Administrador'),(22,22,'Anulada venta ID 22','2024-09-22 19:58:28','Administrador'),(23,22,'Anulada venta ID 22','2024-09-22 19:58:30','Administrador'),(24,23,'Registrada venta ID 23','2024-09-22 21:05:04','Administrador'),(25,24,'Registrada venta ID 24','2024-09-22 21:05:32','Administrador'),(26,25,'Registrada venta ID 25','2024-09-22 21:20:17','Administrador'),(27,26,'Registrada venta ID 26','2024-09-22 21:26:44','Administrador'),(28,29,'Registrada venta ID 29','2024-09-22 21:32:40','Administrador'),(29,30,'Registrada venta ID 30','2024-09-22 21:37:09','Administrador'),(30,26,'Anulada venta ID 26','2024-09-23 04:00:54','Administrador'),(31,26,'Anulada venta ID 26','2024-09-23 04:11:05','Administrador'),(32,26,'Anulada venta ID 26','2024-09-23 04:11:11','Administrador'),(33,26,'Anulada venta ID 26','2024-09-23 04:11:13','Administrador'),(34,26,'Anulada venta ID 26','2024-09-23 04:17:53','Administrador'),(35,30,'Anulada venta ID 30','2024-09-23 04:24:13','Administrador'),(36,29,'Anulada venta ID 29','2024-09-23 04:24:38','Administrador'),(37,3,'Anulada venta ID 3','2024-09-23 05:24:31','Administrador'),(38,32,'Registrada venta ID 32','2024-09-23 05:30:27','Administrador'),(39,33,'Registrada venta ID 33','2024-09-23 05:36:28','Administrador'),(40,34,'Registrada venta ID 34','2024-09-23 05:38:35','Administrador'),(41,8,'Anulada venta ID 8','2024-09-23 19:24:25','Administrador'),(42,25,'Anulada venta ID 25','2024-09-23 19:24:51','Administrador'),(43,35,'Registrada venta ID 35','2024-09-23 19:27:54','Administrador'),(44,36,'Registrada venta ID 36','2024-09-23 19:28:21','Administrador'),(45,37,'Registrada venta ID 37','2024-09-23 21:49:22','Administrador'),(46,38,'Registrada venta ID 38','2024-09-23 23:58:11','Administrador'),(47,38,'Anulada venta ID 38','2024-09-24 13:28:52','Administrador'),(48,37,'Anulada venta ID 37','2024-09-24 14:28:59','Administrador'),(49,39,'Registrada venta ID 39','2024-09-24 14:33:17','Administrador'),(50,39,'Anulada venta ID 39','2024-09-24 14:33:25','Administrador'),(51,40,'Registrada venta ID 40','2024-09-24 14:59:26','Administrador'),(52,41,'Registrada venta ID 41','2024-09-24 15:04:22','Administrador'),(53,42,'Registrada venta ID 42','2024-09-24 15:04:31','Administrador'),(54,42,'Anulada venta ID 42','2024-09-24 15:04:42','Administrador'),(55,43,'Registrada venta ID 43','2024-09-24 15:04:47','Administrador'),(56,44,'Registrada venta ID 44','2024-09-24 15:13:33','Administrador'),(57,45,'Registrada venta ID 45','2024-09-25 15:52:28','Administrador'),(58,45,'Anulada venta ID 45','2024-09-25 15:55:09','Administrador');
/*!40000 ALTER TABLE `historial` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `historial_inventario`
--

DROP TABLE IF EXISTS `historial_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `historial_inventario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL,
  `accion` varchar(255) NOT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `usuario` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `historial_inventario_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `historial_inventario`
--

LOCK TABLES `historial_inventario` WRITE;
/*!40000 ALTER TABLE `historial_inventario` DISABLE KEYS */;
INSERT INTO `historial_inventario` VALUES (1,1,'Agregado producto/servicio: Samsung S22 Ultra','2024-09-17 16:55:23','Administrador'),(2,2,'Agregado producto/servicio: Samsung S23 Ultra','2024-09-17 16:57:23','Administrador'),(3,1,'Editado producto/servicio ID 1','2024-09-17 17:57:31','Administrador'),(4,3,'Agregado producto/servicio: Samsung S23 Ultra','2024-09-17 18:51:03','Administrador'),(5,1,'Editado producto/servicio ID 1','2024-09-17 18:51:11','Administrador'),(6,1,'Editado producto/servicio ID 1','2024-09-17 18:51:13','Administrador'),(7,1,'Editado producto/servicio ID 1','2024-09-17 18:51:16','Administrador'),(8,1,'Editado producto/servicio ID 1','2024-09-17 18:51:17','Administrador'),(9,2,'Editado producto/servicio ID 2','2024-09-17 18:51:20','Administrador'),(10,1,'Editado producto/servicio ID 1','2024-09-17 18:51:35','Administrador'),(11,1,'Editado producto/servicio ID 1','2024-09-17 18:51:39','Administrador'),(12,1,'Editado producto/servicio ID 1','2024-09-17 19:10:36','Administrador'),(13,1,'Editado producto/servicio ID 1','2024-09-17 19:10:37','Administrador'),(14,1,'Editado producto/servicio ID 1','2024-09-17 19:10:37','Administrador'),(15,1,'Editado nombre del producto/servicio ID 1 a \"Yo\"','2024-09-18 12:31:25','Administrador'),(16,1,'Editado nombre del producto/servicio ID 1 a \"Masa\"','2024-09-18 12:45:35','Administrador'),(17,2,'Editado nombre del producto/servicio ID 2 a \"Samsung S22 Ultra\"','2024-09-18 12:45:57','Administrador'),(18,1,'Editado nombre del producto/servicio ID 1 a \"Samsung S24 Ultra\"','2024-09-18 12:46:08','Administrador'),(19,4,'Agregado producto/servicio: Creación de programas','2024-09-18 12:52:43','Administrador'),(20,4,'Editado nombre del producto/servicio ID 4 a \"Creación de programas V0\"','2024-09-18 12:52:55','Administrador'),(21,5,'Agregado producto/servicio: Habla','2024-09-18 12:59:07','Administrador'),(22,5,'Editado nombre del producto/servicio ID 5 a \"Hablaasd\"','2024-09-18 12:59:18','Administrador'),(23,3,'Compra de 50 unidades del producto/servicio: Samsung S23 Ultra','2024-09-18 13:53:17','Administrador'),(24,3,'Compra de 50 unidades del producto/servicio: Samsung S23 Ultra','2024-09-18 14:04:18','Administrador'),(25,3,'Compra de 50 unidades del producto/servicio: Samsung S23 Ultra','2024-09-18 14:04:30','Administrador'),(26,3,'Compra de 50 unidades del producto/servicio: Samsung S23 Ultra','2024-09-18 14:04:32','Administrador'),(27,3,'Compra de 50 unidades del producto/servicio: Samsung S23 Ultra','2024-09-18 14:04:34','Administrador'),(28,3,'Compra de 50 unidades del producto/servicio: Samsung S23 Ultra','2024-09-18 14:04:38','Administrador'),(29,5,'Compra de 50 unidades del producto/servicio: Hablaasd','2024-09-18 14:05:20','Administrador'),(30,5,'Compra de 50 unidades del producto/servicio: Hablaasd','2024-09-18 14:05:27','Administrador'),(31,4,'Compra de 100 unidades del producto/servicio: Creación de programas V0','2024-09-18 14:06:43','Administrador'),(32,6,'Agregado producto/servicio: jkhasdjhkasd','2024-09-18 18:12:10','Administrador'),(33,6,'Agregada compra ID 3 para producto ID 6 con cantidad 50','2024-09-18 18:22:21','Administrador'),(34,1,'Agregada compra ID 4 para producto ID 1 con cantidad 50','2024-09-18 18:22:49','Administrador'),(35,2,'Agregada compra ID 5 para producto ID 2 con cantidad 50','2024-09-18 18:37:30','Administrador'),(36,3,'Agregada compra ID 6 para producto ID 3 con cantidad 9','2024-09-22 19:13:10','Administrador'),(37,7,'Agregado producto/servicio: 1','2024-09-23 21:50:28','Administrador'),(38,8,'Agregado producto/servicio: 2','2024-09-23 21:50:31','Administrador'),(39,9,'Agregado producto/servicio: 3','2024-09-23 21:50:32','Administrador'),(40,10,'Agregado producto/servicio: 3','2024-09-23 21:50:34','Administrador'),(41,11,'Agregado producto/servicio: 4','2024-09-23 21:50:37','Administrador'),(42,12,'Agregado producto/servicio: 5','2024-09-23 21:50:39','Administrador'),(43,13,'Agregado producto/servicio: 6','2024-09-23 21:50:41','Administrador'),(44,14,'Agregado producto/servicio: 7','2024-09-23 21:50:50','Administrador'),(45,15,'Agregado producto/servicio: 8','2024-09-23 21:50:54','Administrador'),(46,16,'Agregado producto/servicio: 9','2024-09-23 21:50:56','Administrador'),(47,17,'Agregado producto/servicio: 10','2024-09-23 21:50:59','Administrador'),(48,18,'Agregado producto/servicio: 11','2024-09-23 21:51:03','Administrador'),(49,19,'Agregado producto/servicio: 12','2024-09-23 21:51:05','Administrador'),(50,20,'Agregado producto/servicio: 13','2024-09-23 21:51:08','Administrador'),(51,21,'Agregado producto/servicio: 14','2024-09-23 21:51:11','Administrador'),(52,22,'Agregado producto/servicio: 15','2024-09-23 21:51:16','Administrador'),(53,23,'Agregado producto/servicio: 16','2024-09-23 21:51:22','Administrador'),(54,24,'Agregado producto/servicio: 17','2024-09-23 21:51:27','Administrador'),(55,1,'Editado nombre del producto/servicio ID 1 a \"Samsung S2444 Ultra\"','2024-09-23 21:56:44','Administrador'),(56,7,'Editado nombre del producto/servicio ID 7 a \"Hola! Soy masahiko\"','2024-09-23 23:57:19','Administrador'),(57,1,'Editado nombre del producto/servicio ID 1 de \"Samsung S2444 Ultra\" a \"Samsung S24 Ultra\"','2024-09-24 13:14:17','Administrador'),(58,25,'Agregado producto/servicio: Masahiko Albert Taira Yoshidaira','2024-09-24 13:20:49','Administrador'),(59,25,'Agregada compra ID 7 para producto ID 25 con cantidad 50','2024-09-24 13:21:46','Administrador'),(60,8,'Editado nombre del producto/servicio ID 8 de \"2\" a \"AAAAAAAAAAAAAAAAAAAA\"','2024-09-24 14:58:33','Administrador'),(61,8,'Agregada compra ID 8 para producto ID 8 con cantidad 100','2024-09-24 14:58:58','Administrador'),(62,1,'Agregada compra ID 9 para producto ID 1 con cantidad 100','2024-09-24 15:24:22','Administrador'),(63,2,'Agregada compra ID 10 para producto ID 2 con cantidad 6','2024-09-24 15:27:13','Administrador'),(64,2,'Agregada compra ID 11 para producto ID 2 con cantidad 6','2024-09-24 15:32:35','Administrador'),(65,8,'Agregada compra ID 12 para producto ID 8 con cantidad 3','2024-09-24 15:33:01','Administrador'),(66,4,'Agregada compra ID 13 para producto ID 4 con cantidad 2','2024-09-24 15:33:35','Administrador'),(67,5,'Agregada compra ID 14 para producto ID 5 con cantidad 5','2024-09-24 15:54:48','Administrador'),(68,2,'Agregada compra ID 15 para producto ID 2 con cantidad 4','2024-09-24 16:56:51','Administrador'),(69,2,'Editado nombre del producto/servicio ID 2 de \"Samsung S22 Ultra\" a \"Samsung S2222 Ultra\"','2024-09-24 17:03:15','Administrador'),(70,3,'Agregada compra ID 16 para producto ID 3 con cantidad 7','2024-09-24 17:31:40','Administrador'),(71,1,'Agregada compra ID 17 para producto ID 1 con cantidad 5','2024-09-24 17:33:05','Administrador'),(72,2,'Editado nombre del producto/servicio ID 2 de \"Samsung S2222 Ultra\" a \"Samsung S2 Ultra\"','2024-09-24 17:38:06','Administrador'),(73,2,'Editado nombre del producto/servicio ID 2 de \"Samsung S2 Ultra\" a \"Samsung S22 Ultra\"','2024-09-24 17:38:21','Administrador'),(74,1,'Agregada compra ID 18 para producto ID 1 con cantidad 100','2024-09-25 15:53:35','Administrador'),(75,8,'Editado nombre del producto/servicio ID 8 de \"AAAAAAAAAAAAAAAAAAAA\" a \"Hola Soy Joe\"','2024-09-25 15:56:28','Administrador');
/*!40000 ALTER TABLE `historial_inventario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo` enum('Producto','Servicio') NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `codigo` varchar(100) NOT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES (1,'Producto','Samsung S24 Ultra','P66e9facb598ac',2500.00,210),(2,'Producto','Samsung S22 Ultra','P66e9fb433ab32',3200.00,30),(3,'Producto','Samsung S23 Ultra','P66ea15e752900',3200.00,20),(4,'Servicio','Creación de programas V0','PBEC931',0.00,100),(5,'Producto','Hablaasd','PB772AC',0.00,100),(6,'Producto','jkhasdjhkasd','PAA8E8F',0.00,40),(7,'Servicio','Hola! Soy masahiko','P47E089',0.00,0),(8,'Producto','Hola Soy Joe','P76C69C',0.00,100),(9,'Producto','3','P8D3FD5',0.00,0),(10,'Producto','3','PA3502A',0.00,0),(11,'Producto','4','PDC573C',0.00,0),(12,'Producto','5','PF5CCBC',0.00,0),(13,'Producto','6','P100BD6',0.00,0),(14,'Producto','7','PA508D9',0.00,0),(15,'Producto','8','PE748D0',0.00,0),(16,'Producto','9','P084DE6',0.00,0),(17,'Producto','10','P3B433A',0.00,0),(18,'Producto','11','P74B3E0',0.00,0),(19,'Producto','12','P98B547',0.00,0),(20,'Producto','13','PCEE4F6',0.00,0),(21,'Producto','14','PFE41D0',0.00,0),(22,'Producto','15','P452579',0.00,0),(23,'Producto','16','PA05EDF',0.00,0),(24,'Producto','17','PFA8DF3',0.00,0),(25,'Servicio','Masahiko Albert Taira Yoshidaira','P10A531',0.00,50);
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `venta_items`
--

DROP TABLE IF EXISTS `venta_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `venta_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `venta_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `venta_id` (`venta_id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `venta_items_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`),
  CONSTRAINT `venta_items_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venta_items`
--

LOCK TABLES `venta_items` WRITE;
/*!40000 ALTER TABLE `venta_items` DISABLE KEYS */;
INSERT INTO `venta_items` VALUES (1,1,1,1,2300.00),(2,1,2,3,3000.00),(3,2,1,5,2500.00),(4,3,1,1,2.00),(5,4,1,1,2500.00),(6,5,1,1,2500.00),(7,6,1,2,2500.00),(8,6,2,2,3300.00),(9,7,1,2,5000.00),(10,8,2,3,2500.00),(11,8,1,6,4000.00),(12,8,4,20,1000.00),(13,8,5,5,2500.00),(14,8,3,5,3000.00),(15,9,3,299,1.00),(16,10,1,46,1.00),(17,11,2,3,3.00),(18,11,2,6,3.00),(19,12,2,4,4.00),(20,13,2,1,2500.00),(21,13,2,1,1.00),(22,14,2,2,2.00),(23,15,2,1,1.00),(24,15,2,1,1.00),(25,16,2,1,1.00),(26,17,2,1,1.00),(27,18,2,2,2.00),(28,18,4,2,2.00),(29,19,2,2,2.00),(30,20,2,2,2500.00),(31,20,2,3,2400.00),(33,22,1,1,1.00),(34,23,2,1,1.00),(35,24,2,2,0.00),(36,25,4,8,1000.00),(37,26,5,5,0.00),(40,29,3,3,3.00),(41,30,1,1,100.00),(44,32,1,2,2000.00),(45,32,3,2,3000.00),(46,33,2,1,22222.00),(47,34,2,2,1.00),(48,35,6,5,1000.00),(49,36,6,5,10000.00),(50,37,4,8,321.00),(51,38,1,2,1.00),(52,38,4,2,1.00),(53,39,1,1,1.00),(54,40,8,1,1.00),(55,41,8,1,1.00),(56,42,8,1,1.00),(57,43,8,1,1.00),(58,44,1,1,0.00),(59,45,1,50,500.00);
/*!40000 ALTER TABLE `venta_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ventas`
--

DROP TABLE IF EXISTS `ventas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ventas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `denominacion` varchar(255) DEFAULT NULL,
  `tipo_documento` enum('Factura','Boleta') NOT NULL,
  `moneda` varchar(50) NOT NULL,
  `tipo_cambio` decimal(10,4) NOT NULL,
  `fecha` date NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `igv` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `observaciones` text,
  `anulado` tinyint(1) DEFAULT '0',
  `estado` varchar(20) NOT NULL DEFAULT 'activo',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ventas`
--

LOCK TABLES `ventas` WRITE;
/*!40000 ALTER TABLE `ventas` DISABLE KEYS */;
INSERT INTO `ventas` VALUES (1,'','Boleta','PEN',1.0000,'2024-09-17',11300.00,2034.00,13334.00,'',0,'activo'),(2,'','Boleta','PEN',1.0000,'2024-09-17',12500.00,2250.00,14750.00,'',0,'activo'),(3,'','Boleta','PEN',1.0000,'2024-09-17',1.69,0.31,2.00,'',0,'anulado'),(4,'','Boleta','PEN',1.0000,'2024-09-17',2118.64,381.36,2500.00,'',0,'activo'),(5,'Masahiko','Factura','USD',3.7900,'2024-09-17',2118.64,381.36,2500.00,'',0,'activo'),(6,'','Boleta','PEN',1.0000,'2024-09-17',9830.51,1769.49,11600.00,'',0,'activo'),(7,'','Boleta','PEN',1.0000,'2024-09-18',8474.58,1525.42,10000.00,'',0,'activo'),(8,'','Boleta','PEN',1.0000,'2024-09-18',66949.15,12050.85,79000.00,'',0,'anulado'),(9,'','Boleta','PEN',1.0000,'2024-09-18',253.39,45.61,299.00,'',0,'activo'),(10,'','Boleta','PEN',1.0000,'2024-09-18',38.98,7.02,46.00,'',0,'activo'),(11,'','Boleta','PEN',1.0000,'2024-09-18',22.88,4.12,27.00,'',0,'activo'),(12,'','Boleta','PEN',1.0000,'2024-09-18',13.56,2.44,16.00,'Fecha modificada de 2024-09-19 a 2024-09-18.',0,'activo'),(13,'Masahiko','Factura','PEN',1.0000,'2024-09-19',2119.49,381.51,2501.00,'',0,'activo'),(14,'Masahiko','Factura','PEN',1.0000,'2024-09-19',3.39,0.61,4.00,'',0,'activo'),(15,'','Boleta','PEN',1.0000,'2024-09-19',1.69,0.31,2.00,'',0,'activo'),(16,'','Boleta','PEN',1.0000,'2024-09-19',0.85,0.15,1.00,'',0,'activo'),(17,'','Boleta','PEN',1.0000,'2024-09-19',1.00,0.18,1.18,'',0,'activo'),(18,'','Boleta','PEN',1.0000,'2024-09-19',6.78,1.22,8.00,'',0,'activo'),(19,'','Boleta','PEN',1.0000,'2024-09-19',3.39,0.61,4.00,'',0,'activo'),(20,'','Boleta','PEN',1.0000,'2024-09-23',10338.98,1861.02,12200.00,'',0,'activo'),(22,'','Boleta','PEN',1.0000,'2024-09-23',0.85,0.15,1.00,'',0,'anulado'),(23,'','Boleta','PEN',1.0000,'2024-09-23',0.85,0.15,1.00,'',0,'activo'),(24,'','Boleta','PEN',1.0000,'2024-09-23',0.00,0.00,0.00,'',0,'activo'),(25,'','Boleta','PEN',1.0000,'2024-09-23',6779.66,1220.34,8000.00,'',0,'anulado'),(26,'','Boleta','PEN',1.0000,'2024-09-23',0.00,0.00,0.00,'',0,'anulado'),(29,'','Boleta','PEN',1.0000,'2024-09-23',7.63,1.37,9.00,'',0,'anulado'),(30,'','Boleta','PEN',1.0000,'2024-09-23',84.75,15.25,100.00,'',0,'anulado'),(32,'','Boleta','PEN',1.0000,'2024-09-23',8474.58,1525.42,10000.00,'',0,'activo'),(33,'','Boleta','PEN',1.0000,'2024-09-23',18832.20,3389.80,22222.00,'Quedarian 13 S22',0,'activo'),(34,'Masahiko','Factura','USD',3.7500,'2024-09-23',1.69,0.31,2.00,'',0,'activo'),(35,'','Boleta','PEN',1.0000,'2024-09-24',4237.29,762.71,5000.00,'',0,'activo'),(36,'','Boleta','PEN',1.0000,'2024-09-24',42372.88,7627.12,50000.00,'',0,'activo'),(37,'','Boleta','PEN',1.0000,'2024-09-24',2176.27,391.73,2568.00,'',0,'anulado'),(38,'Masahiko','Factura','PEN',1.0000,'2024-09-24',3.39,0.61,4.00,'',0,'anulado'),(39,'','Boleta','PEN',1.0000,'2024-09-24',0.85,0.15,1.00,'',0,'anulado'),(40,'','Boleta','PEN',1.0000,'2024-09-24',0.85,0.15,1.00,'',0,'activo'),(41,'','Boleta','PEN',1.0000,'2024-09-24',0.85,0.15,1.00,'',0,'activo'),(42,'','Boleta','PEN',1.0000,'2024-09-24',0.85,0.15,1.00,'',0,'anulado'),(43,'','Boleta','PEN',1.0000,'2024-09-24',0.85,0.15,1.00,'',0,'activo'),(44,'','Boleta','PEN',1.0000,'2024-09-24',0.00,0.00,0.00,'',0,'activo'),(45,'Joe','Factura','PEN',1.0000,'2024-09-25',21186.44,3813.56,25000.00,'',0,'anulado');
/*!40000 ALTER TABLE `ventas` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-09-26 17:50:06
