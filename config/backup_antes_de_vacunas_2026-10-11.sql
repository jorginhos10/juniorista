-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: petcontrol
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
-- Table structure for table `api_tokens`
--

DROP TABLE IF EXISTS `api_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expira` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_tokens`
--

LOCK TABLES `api_tokens` WRITE;
/*!40000 ALTER TABLE `api_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `arqueos`
--

DROP TABLE IF EXISTS `arqueos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `arqueos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `veterinaria_id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `fecha` date NOT NULL,
  `conteo` text NOT NULL,
  `total_declarado` decimal(12,2) NOT NULL DEFAULT 0.00,
  `efectivo_sistema` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notas` text DEFAULT NULL,
  `estado` enum('pendiente','aprobado','observado') NOT NULL DEFAULT 'pendiente',
  `auditado_por` int(11) DEFAULT NULL,
  `auditado_at` datetime DEFAULT NULL,
  `nota_auditoria` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `vet_fecha` (`veterinaria_id`,`fecha`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `arqueos`
--

LOCK TABLES `arqueos` WRITE;
/*!40000 ALTER TABLE `arqueos` DISABLE KEYS */;
/*!40000 ALTER TABLE `arqueos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `citas`
--

DROP TABLE IF EXISTS `citas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `citas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `veterinaria_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `mascota_id` int(11) NOT NULL,
  `veterinario_id` int(11) DEFAULT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `duracion` smallint(6) NOT NULL DEFAULT 30,
  `tipo` enum('consulta','vacunacion','desparasitacion','control','cirugia','estetica','urgencia','otro') NOT NULL DEFAULT 'consulta',
  `motivo` varchar(200) DEFAULT NULL,
  `estado` enum('pendiente','confirmada','en_curso','atendida','cancelada','no_asistio') NOT NULL DEFAULT 'pendiente',
  `notas` text DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_citas_vet_fecha` (`veterinaria_id`,`fecha`),
  KEY `idx_citas_mascota` (`mascota_id`),
  KEY `idx_citas_veterinario` (`veterinario_id`,`fecha`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `citas`
--

LOCK TABLES `citas` WRITE;
/*!40000 ALTER TABLE `citas` DISABLE KEYS */;
/*!40000 ALTER TABLE `citas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `dni` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `direccion` varchar(250) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `veterinaria_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES (1,'Juan','Pérez','12345678','juan.perez@mail.com','555-1001','Av. Siempre Viva 123',NULL,1,'2026-06-02 05:34:36','2026-10-09 23:02:21',1),(2,'María','López','23456789','maria.lopez@mail.com','555-1002','Calle Falsa 456',NULL,1,'2026-06-02 05:34:36','2026-10-09 23:02:21',1),(3,'Carlos','Gómez','34567890','carlos.gomez@mail.com','555-1003','Jr. Los Pinos 789',NULL,1,'2026-06-02 05:34:36','2026-10-09 23:02:21',1),(4,'Ana','Torres','45678901','ana.torres@mail.com','555-1004','Pasaje El Sol 321',NULL,1,'2026-06-02 05:34:36','2026-06-02 05:34:36',1),(5,'Roberto','Díaz','56789012','roberto.diaz@mail.com','555-1005','Urb. Las Flores 654',NULL,1,'2026-06-02 05:34:36','2026-10-09 23:02:21',1);
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `descargos`
--

DROP TABLE IF EXISTS `descargos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `descargos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `veterinaria_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL DEFAULT 0.00,
  `motivo` enum('vencido','perdido','otros') NOT NULL,
  `motivo_detalle` varchar(255) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_descargos_vet_fecha` (`veterinaria_id`,`created_at`),
  KEY `idx_descargos_producto` (`producto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `descargos`
--

LOCK TABLES `descargos` WRITE;
/*!40000 ALTER TABLE `descargos` DISABLE KEYS */;
/*!40000 ALTER TABLE `descargos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gastos`
--

DROP TABLE IF EXISTS `gastos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gastos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `veterinaria_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `categoria` enum('alquiler','servicios','sueldos','insumos','medicamentos','mantenimiento','impuestos','marketing','transporte','otros') NOT NULL DEFAULT 'otros',
  `descripcion` varchar(200) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` enum('efectivo','transferencia','tarjeta','yape_plin','otro') NOT NULL DEFAULT 'efectivo',
  `comprobante` varchar(60) DEFAULT NULL,
  `proveedor_id` int(11) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_gastos_vet_fecha` (`veterinaria_id`,`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gastos`
--

LOCK TABLES `gastos` WRITE;
/*!40000 ALTER TABLE `gastos` DISABLE KEYS */;
/*!40000 ALTER TABLE `gastos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `historial_medico`
--

DROP TABLE IF EXISTS `historial_medico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `historial_medico` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mascota_id` int(11) NOT NULL,
  `veterinaria_id` int(11) DEFAULT NULL,
  `veterinario_id` int(11) DEFAULT NULL,
  `fecha` date NOT NULL,
  `tipo` enum('consulta','control','vacunacion','desparasitacion','cirugia','laboratorio','urgencia','otro') NOT NULL DEFAULT 'consulta',
  `motivo` varchar(200) NOT NULL,
  `anamnesis` text DEFAULT NULL,
  `peso` decimal(6,2) DEFAULT NULL,
  `temperatura` decimal(4,1) DEFAULT NULL,
  `frecuencia_cardiaca` smallint(6) DEFAULT NULL,
  `frecuencia_respiratoria` smallint(6) DEFAULT NULL,
  `mucosas` varchar(30) DEFAULT NULL,
  `tllc` varchar(10) DEFAULT NULL,
  `hidratacion` varchar(20) DEFAULT NULL,
  `condicion_corporal` tinyint(4) DEFAULT NULL,
  `examen_fisico` text DEFAULT NULL,
  `diagnostico` text DEFAULT NULL,
  `tratamiento` text DEFAULT NULL,
  `producto_aplicado` varchar(150) DEFAULT NULL,
  `lote` varchar(60) DEFAULT NULL,
  `proximo_control` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_historial_mascota` (`mascota_id`,`fecha`),
  KEY `idx_historial_proximo` (`proximo_control`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `historial_medico`
--

LOCK TABLES `historial_medico` WRITE;
/*!40000 ALTER TABLE `historial_medico` DISABLE KEYS */;
/*!40000 ALTER TABLE `historial_medico` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ingreso_detalles`
--

DROP TABLE IF EXISTS `ingreso_detalles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ingreso_detalles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ingreso_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(10,2) GENERATED ALWAYS AS (`cantidad` * `precio_unitario`) STORED,
  `lote` varchar(60) DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ingreso_id` (`ingreso_id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `ingreso_detalles_ibfk_1` FOREIGN KEY (`ingreso_id`) REFERENCES `ingresos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ingreso_detalles_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=341 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ingreso_detalles`
--

LOCK TABLES `ingreso_detalles` WRITE;
/*!40000 ALTER TABLE `ingreso_detalles` DISABLE KEYS */;
INSERT INTO `ingreso_detalles` VALUES (6,3,9,2,1.00,2.00,NULL,'2028-05-31'),(7,3,10,39,1.00,39.00,NULL,'2029-02-28'),(8,3,11,2,1.00,2.00,NULL,'2027-05-31'),(9,3,12,3,1.00,3.00,NULL,NULL),(10,3,13,6,1.00,6.00,NULL,'2027-06-30'),(11,3,14,5,1.00,5.00,NULL,'2027-08-31'),(12,3,15,14,1.00,14.00,NULL,'2030-10-31'),(13,3,16,16,1.00,16.00,NULL,NULL),(14,3,17,13,1.00,13.00,NULL,'2027-04-30'),(15,3,18,5,1.00,5.00,NULL,'2027-09-30'),(16,3,19,3,1.00,3.00,NULL,'2027-09-30'),(17,3,20,12,1.00,12.00,NULL,'2027-03-31'),(18,3,21,1,1.00,1.00,NULL,NULL),(19,3,22,1,1.00,1.00,NULL,NULL),(20,3,23,1,1.00,1.00,NULL,'2029-12-31'),(21,3,24,6,1.00,6.00,NULL,'2028-12-31'),(22,3,25,3,1.00,3.00,NULL,'2028-12-31'),(23,3,26,4,1.00,4.00,NULL,'2027-07-31'),(24,3,27,2,1.00,2.00,NULL,'2027-11-30'),(25,3,28,1,1.00,1.00,NULL,NULL),(26,3,29,41,1.00,41.00,NULL,NULL),(27,3,30,1,1.00,1.00,NULL,NULL),(28,3,31,2,1.00,2.00,NULL,NULL),(29,3,32,1,1.00,1.00,NULL,NULL),(30,3,33,1,1.00,1.00,NULL,NULL),(31,3,34,6,1.00,6.00,NULL,NULL),(32,3,35,4,1.00,4.00,NULL,NULL),(33,3,36,5,1.00,5.00,NULL,NULL),(34,3,37,1,1.00,1.00,NULL,NULL),(35,3,38,3,1.00,3.00,NULL,NULL),(36,3,39,4,1.00,4.00,NULL,NULL),(37,3,40,1,1.00,1.00,NULL,NULL),(38,3,41,1,1.00,1.00,NULL,NULL),(39,3,42,2,1.00,2.00,NULL,NULL),(40,3,43,2,1.00,2.00,NULL,NULL),(41,3,44,2,1.00,2.00,NULL,NULL),(42,3,45,2,1.00,2.00,NULL,NULL),(43,3,46,2,1.00,2.00,NULL,NULL),(44,3,47,3,1.00,3.00,NULL,NULL),(45,3,48,3,1.00,3.00,NULL,NULL),(46,3,49,2,1.00,2.00,NULL,NULL),(47,3,50,5,1.00,5.00,NULL,NULL),(48,3,51,2,1.00,2.00,NULL,NULL),(49,3,52,2,1.00,2.00,NULL,NULL),(50,3,53,3,1.00,3.00,NULL,NULL),(51,3,54,1,1.00,1.00,NULL,NULL),(52,3,55,2,1.00,2.00,NULL,NULL),(53,3,56,42,1.00,42.00,NULL,'2029-09-30'),(54,3,57,13,1.00,13.00,NULL,'2028-09-30'),(55,3,58,4,1.00,4.00,NULL,NULL),(56,3,59,2,1.00,2.00,NULL,NULL),(57,3,60,3,1.00,3.00,NULL,NULL),(58,3,61,3,1.00,3.00,NULL,NULL),(59,3,62,2,1.00,2.00,NULL,NULL),(60,3,63,5,1.00,5.00,NULL,NULL),(61,3,64,2,1.00,2.00,NULL,NULL),(62,3,65,1,1.00,1.00,NULL,NULL),(63,3,66,1,1.00,1.00,NULL,NULL),(64,3,67,6,1.00,6.00,NULL,'2027-11-30'),(65,3,68,1,1.00,1.00,NULL,NULL),(66,3,69,1,1.00,1.00,NULL,NULL),(67,3,70,2,1.00,2.00,NULL,NULL),(68,3,71,2,1.00,2.00,NULL,NULL),(69,3,72,1,1.00,1.00,NULL,NULL),(70,3,73,2,1.00,2.00,NULL,NULL),(71,3,74,2,1.00,2.00,NULL,NULL),(72,3,75,1,1.00,1.00,NULL,NULL),(73,3,76,1,1.00,1.00,NULL,NULL),(74,3,77,4,1.00,4.00,NULL,NULL),(75,3,78,2,1.00,2.00,NULL,NULL),(76,3,79,3,1.00,3.00,NULL,NULL),(77,3,80,3,1.00,3.00,NULL,NULL),(78,3,81,3,1.00,3.00,NULL,NULL),(79,3,82,2,1.00,2.00,NULL,NULL),(80,3,83,1,1.00,1.00,NULL,NULL),(81,3,84,1,1.00,1.00,NULL,NULL),(82,3,85,3,1.00,3.00,NULL,NULL),(83,3,86,4,1.00,4.00,NULL,NULL),(84,3,87,4,1.00,4.00,NULL,NULL),(85,3,88,4,1.00,4.00,NULL,NULL),(86,3,89,1,1.00,1.00,NULL,NULL),(87,3,90,1,1.00,1.00,NULL,NULL),(88,3,91,1,1.00,1.00,NULL,NULL),(89,3,92,8,1.00,8.00,NULL,NULL),(90,3,93,1,1.00,1.00,NULL,'2028-10-31'),(91,3,94,4,1.00,4.00,NULL,NULL),(92,3,95,1,1.00,1.00,NULL,NULL),(93,3,96,1,1.00,1.00,NULL,NULL),(94,3,97,3,1.00,3.00,NULL,NULL),(95,3,98,63,1.00,63.00,NULL,'2027-09-30'),(96,3,99,3,1.00,3.00,NULL,'2028-04-30'),(97,3,100,5,1.00,5.00,NULL,NULL),(98,3,101,4,1.00,4.00,NULL,NULL),(99,3,102,5,1.00,5.00,NULL,NULL),(100,3,103,3,1.00,3.00,NULL,NULL),(101,3,104,24,1.00,24.00,NULL,'2027-08-31'),(102,3,105,6,1.00,6.00,NULL,NULL),(103,3,106,1,1.00,1.00,NULL,NULL),(104,3,107,2,1.00,2.00,NULL,NULL),(105,3,108,2,1.00,2.00,NULL,NULL),(106,3,109,2,1.00,2.00,NULL,NULL),(107,3,110,64,1.00,64.00,NULL,'2027-02-28'),(108,3,111,1,1.00,1.00,NULL,'2026-11-30'),(109,3,112,2,1.00,2.00,NULL,'2027-09-30'),(110,3,113,59,1.00,59.00,NULL,'2028-02-29'),(111,3,114,98,1.00,98.00,NULL,'2027-06-30'),(112,3,115,3,1.00,3.00,NULL,NULL),(113,3,116,1,1.00,1.00,NULL,NULL),(114,3,117,5,1.00,5.00,NULL,NULL),(115,3,118,6,1.00,6.00,NULL,NULL),(116,3,119,6,1.00,6.00,NULL,NULL),(117,3,120,6,1.00,6.00,NULL,NULL),(118,3,121,1,1.00,1.00,NULL,NULL),(119,3,122,2,1.00,2.00,NULL,NULL),(120,3,123,1,1.00,1.00,NULL,NULL),(121,3,124,1,1.00,1.00,NULL,NULL),(122,3,125,3,1.00,3.00,NULL,NULL),(123,3,126,1,1.00,1.00,NULL,NULL),(124,3,127,7,1.00,7.00,NULL,NULL),(125,3,128,2,1.00,2.00,NULL,NULL),(126,3,129,12,1.00,12.00,NULL,NULL),(127,3,130,4,1.00,4.00,NULL,NULL),(128,3,131,6,1.00,6.00,NULL,NULL),(129,3,132,2,1.00,2.00,NULL,'2027-04-30'),(130,3,133,2,1.00,2.00,NULL,NULL),(131,3,134,9,1.00,9.00,NULL,NULL),(132,3,135,2,1.00,2.00,NULL,NULL),(133,3,136,2,1.00,2.00,NULL,NULL),(134,3,137,2,1.00,2.00,NULL,NULL),(135,3,138,4,1.00,4.00,NULL,NULL),(136,3,139,4,1.00,4.00,NULL,NULL),(137,3,140,2,1.00,2.00,NULL,NULL),(138,3,141,7,0.00,0.00,NULL,NULL),(139,3,142,1,1.00,1.00,NULL,NULL),(140,3,143,15,1.00,15.00,NULL,NULL),(141,3,144,1,1.00,1.00,NULL,NULL),(142,3,145,4,0.00,0.00,NULL,NULL),(143,3,146,4,1.00,4.00,NULL,NULL),(144,3,147,1,1.00,1.00,NULL,NULL),(145,3,148,4,1.00,4.00,NULL,NULL),(146,3,149,2,1.00,2.00,NULL,NULL),(147,3,150,3,1.00,3.00,NULL,NULL),(148,3,151,6,1.00,6.00,NULL,NULL),(149,3,152,12,1.00,12.00,NULL,NULL),(150,3,153,2,1.00,2.00,NULL,NULL),(151,3,154,6,1.00,6.00,NULL,NULL),(152,3,155,4,1.00,4.00,NULL,NULL),(153,3,156,1,1.00,1.00,NULL,NULL),(154,3,157,2,1.00,2.00,NULL,'2027-10-31'),(155,3,158,1,1.00,1.00,NULL,NULL),(156,3,159,3,1.00,3.00,NULL,NULL),(157,3,160,2,1.00,2.00,NULL,NULL),(158,3,161,2,1.00,2.00,NULL,NULL),(159,3,162,10,1.00,10.00,NULL,NULL),(160,3,163,3,1.00,3.00,NULL,NULL),(161,3,164,3,1.00,3.00,NULL,NULL),(162,3,165,8,1.00,8.00,NULL,'2027-01-31'),(163,3,166,4,1.00,4.00,NULL,'2030-01-31'),(164,3,167,2,1.00,2.00,NULL,'2025-10-31'),(165,3,168,3,1.00,3.00,NULL,'2028-01-31'),(166,3,169,4,1.00,4.00,NULL,'2029-03-31'),(167,3,170,38,1.00,38.00,NULL,'2027-01-31'),(168,3,171,7,1.00,7.00,NULL,'2027-01-31'),(169,3,172,7,1.00,7.00,NULL,'2028-10-31'),(170,3,173,1,1.00,1.00,NULL,'2028-10-31'),(171,3,174,9,1.00,9.00,NULL,'2027-06-30'),(172,3,175,5,1.00,5.00,NULL,'2028-01-31'),(173,3,176,6,1.00,6.00,NULL,'2028-01-31'),(174,3,177,94,1.00,94.00,NULL,'2028-06-30'),(175,3,178,7,1.00,7.00,NULL,'2027-03-31'),(176,3,179,55,1.00,55.00,NULL,'2027-03-31'),(177,3,180,4,1.00,4.00,NULL,'2028-12-31'),(178,3,181,1,1.00,1.00,NULL,'2027-10-31'),(179,3,182,3,1.00,3.00,NULL,'2028-03-31'),(180,3,183,4,1.00,4.00,NULL,'2027-11-30'),(181,3,184,1,1.00,1.00,NULL,'2028-04-30'),(182,3,185,34,1.00,34.00,NULL,'2027-10-31'),(183,3,186,74,1.00,74.00,NULL,'2027-08-31'),(184,3,187,294,1.00,294.00,NULL,'2027-06-30'),(185,3,188,2,1.00,2.00,NULL,'2028-04-30'),(186,3,189,5,1.00,5.00,NULL,'2028-10-31'),(187,3,190,2,1.00,2.00,NULL,'2030-07-31'),(188,3,191,2,1.00,2.00,NULL,NULL),(189,3,192,3,1.00,3.00,NULL,NULL),(190,3,193,1,1.00,1.00,NULL,NULL),(191,3,194,15,1.00,15.00,NULL,NULL),(192,3,195,5,1.00,5.00,NULL,NULL),(193,3,196,1,1.00,1.00,NULL,NULL),(194,3,197,1,1.00,1.00,NULL,'2027-10-31'),(195,3,198,3,1.00,3.00,NULL,'2027-10-31'),(196,3,199,45,1.00,45.00,NULL,'2026-02-28'),(197,3,200,1,1.00,1.00,NULL,'2028-11-30'),(198,3,201,1,1.00,1.00,NULL,'2028-11-30'),(199,3,202,18,1.00,18.00,NULL,'2027-10-31'),(200,3,203,2,1.00,2.00,NULL,'2028-08-31'),(201,3,204,4,1.00,4.00,NULL,'2028-05-31'),(202,3,205,2,1.00,2.00,NULL,NULL),(203,3,206,49,1.00,49.00,NULL,'2027-06-30'),(204,3,207,2,1.00,2.00,NULL,'2028-03-31'),(205,3,208,43,1.00,43.00,NULL,NULL),(206,3,209,2,1.00,2.00,NULL,NULL),(207,3,210,3,1.00,3.00,NULL,NULL),(208,3,211,2,1.00,2.00,NULL,NULL),(209,3,212,1,1.00,1.00,NULL,NULL),(210,3,213,2,1.00,2.00,NULL,NULL),(211,3,214,180,1.00,180.00,NULL,'2029-01-31'),(212,3,215,32,1.00,32.00,NULL,'2027-03-31'),(213,3,216,16,1.00,16.00,NULL,NULL),(214,3,217,2,1.00,2.00,NULL,NULL),(215,3,218,1,1.00,1.00,NULL,NULL),(216,3,219,8,1.00,8.00,NULL,'2028-06-30'),(217,3,220,9,1.00,9.00,NULL,'2028-09-30'),(218,3,221,12,1.00,12.00,NULL,'2028-12-31'),(219,3,222,7,1.00,7.00,NULL,'2027-06-30'),(220,3,223,7,1.00,7.00,NULL,'2027-06-30'),(221,3,224,7,1.00,7.00,NULL,'2027-03-31'),(222,3,225,1,1.00,1.00,NULL,NULL),(223,3,226,10,1.00,10.00,NULL,NULL),(224,3,227,2,1.00,2.00,NULL,'2027-12-31'),(225,3,228,1,1.00,1.00,NULL,'2028-03-31'),(226,3,229,5,1.00,5.00,NULL,'2028-01-31'),(227,3,230,2,1.00,2.00,NULL,NULL),(228,3,231,4,1.00,4.00,NULL,NULL),(229,3,232,4,1.00,4.00,NULL,NULL),(230,3,233,1,1.00,1.00,NULL,NULL),(231,3,234,4,1.00,4.00,NULL,'2027-03-31'),(232,3,235,32,1.00,32.00,NULL,'2030-02-28'),(233,3,236,13,1.00,13.00,NULL,'2028-04-30'),(234,3,237,11,1.00,11.00,NULL,'2028-04-30'),(235,3,238,44,1.00,44.00,NULL,'2029-09-30'),(236,3,239,32,1.00,32.00,NULL,NULL),(237,3,240,2,1.00,2.00,NULL,NULL),(238,3,241,2,1.00,2.00,NULL,NULL),(239,3,242,2,1.00,2.00,NULL,'2028-01-31'),(240,3,243,18,1.00,18.00,NULL,'2027-02-28'),(241,3,244,4,1.00,4.00,NULL,NULL),(242,3,245,4,1.00,4.00,NULL,NULL),(243,3,246,3,1.00,3.00,NULL,NULL),(244,3,247,4,1.00,4.00,NULL,NULL),(245,3,248,3,1.00,3.00,NULL,NULL),(246,3,249,4,1.00,4.00,NULL,NULL),(247,3,250,5,1.00,5.00,NULL,NULL),(248,3,251,18,1.00,18.00,NULL,NULL),(249,3,252,10,1.00,10.00,NULL,NULL),(250,3,253,4,1.00,4.00,NULL,NULL),(251,3,254,2,1.00,2.00,NULL,NULL),(252,3,255,6,1.00,6.00,NULL,NULL),(253,3,256,1,1.00,1.00,NULL,NULL),(254,3,257,3,1.00,3.00,NULL,NULL),(255,3,258,8,1.00,8.00,NULL,NULL),(256,3,259,4,1.00,4.00,NULL,NULL),(257,3,260,25,1.00,25.00,NULL,NULL),(258,3,261,2,1.00,2.00,NULL,NULL),(259,3,262,2,1.00,2.00,NULL,NULL),(260,3,263,3,1.00,3.00,NULL,NULL),(261,3,264,2,1.00,2.00,NULL,NULL),(262,3,265,5,1.00,5.00,NULL,NULL),(263,3,266,5,1.00,5.00,NULL,NULL),(264,3,267,1,1.00,1.00,NULL,NULL),(265,3,268,2,1.00,2.00,NULL,NULL),(266,3,269,8,1.00,8.00,NULL,NULL),(267,3,270,10,1.00,10.00,NULL,NULL),(268,3,271,3,1.00,3.00,NULL,NULL),(269,3,272,2,1.00,2.00,NULL,NULL),(270,3,273,2,1.00,2.00,NULL,NULL),(271,3,274,1,1.00,1.00,NULL,NULL),(272,3,275,1,1.00,1.00,NULL,NULL),(273,3,276,1,1.00,1.00,NULL,NULL),(274,3,277,1,1.00,1.00,NULL,NULL),(275,3,278,1,1.00,1.00,NULL,NULL),(276,3,279,2,1.00,2.00,NULL,NULL),(277,3,280,3,1.00,3.00,NULL,'2027-04-30'),(278,3,281,4,1.00,4.00,NULL,'2027-01-31'),(279,3,282,3,1.00,3.00,NULL,'2027-01-31'),(280,3,283,87,1.00,87.00,NULL,'2027-01-31'),(281,3,284,21,1.00,21.00,NULL,NULL),(282,3,285,10,1.00,10.00,NULL,NULL),(283,3,286,1,1.00,1.00,NULL,NULL),(284,3,287,77,1.00,77.00,NULL,'2027-01-31'),(285,3,288,10,1.00,10.00,NULL,'2028-02-29'),(286,3,289,6,1.00,6.00,NULL,NULL),(287,3,290,58,1.00,58.00,NULL,'2028-08-31'),(288,3,291,3,1.00,3.00,NULL,NULL),(289,3,292,7,1.00,7.00,NULL,'2027-02-28'),(290,3,293,1,1.00,1.00,NULL,'2028-09-30'),(291,3,294,20,1.00,20.00,NULL,'2027-01-31'),(292,3,295,8,1.00,8.00,NULL,NULL),(293,3,296,3,1.00,3.00,NULL,'2028-03-31'),(294,3,297,1,1.00,1.00,NULL,'2028-12-31'),(295,3,298,2,1.00,2.00,NULL,'2028-11-30'),(296,3,299,3,1.00,3.00,NULL,'2028-05-31'),(297,3,300,2,1.00,2.00,NULL,'2027-06-30'),(298,3,301,4,1.00,4.00,NULL,'2027-12-31'),(299,3,302,3,1.00,3.00,NULL,'2027-03-31'),(300,3,303,1,0.00,0.00,NULL,'2027-04-30'),(301,3,304,3,1.00,3.00,NULL,'2027-12-31'),(302,3,305,5,1.00,5.00,NULL,NULL),(303,3,306,4,1.00,4.00,NULL,'2028-03-31'),(304,3,307,6,1.00,6.00,NULL,NULL),(305,3,308,71,1.00,71.00,NULL,NULL),(306,3,309,11,1.00,11.00,NULL,'2028-03-31'),(307,3,310,4,1.00,4.00,NULL,'2027-09-30'),(308,3,311,16,1.00,16.00,NULL,'2028-09-30'),(309,3,312,1,1.00,1.00,NULL,NULL),(310,3,313,14,1.00,14.00,NULL,NULL),(311,3,314,8,1.00,8.00,NULL,NULL),(312,3,315,3,1.00,3.00,NULL,'2027-08-31'),(313,3,316,5,1.00,5.00,NULL,'2026-10-31'),(314,3,317,12,1.00,12.00,NULL,NULL),(315,3,318,65,1.00,65.00,NULL,'2030-01-31'),(316,3,319,2,1.00,2.00,NULL,'2027-03-31'),(317,3,320,3,1.00,3.00,NULL,'2027-10-31'),(318,3,321,6,1.00,6.00,NULL,'2027-05-31'),(319,3,322,1,1.00,1.00,NULL,'2027-01-31'),(320,3,323,82,1.00,82.00,NULL,'2027-09-30'),(321,3,324,5,1.00,5.00,NULL,NULL),(322,3,325,1,1.00,1.00,NULL,NULL),(323,3,326,1,1.00,1.00,NULL,NULL),(324,3,327,2,1.00,2.00,NULL,NULL),(325,3,328,2,1.00,2.00,NULL,NULL),(326,3,329,5,1.00,5.00,NULL,NULL),(327,3,330,5,1.00,5.00,NULL,NULL),(328,3,331,1,1.00,1.00,NULL,NULL),(329,3,332,7,1.00,7.00,NULL,'2029-01-31'),(330,3,333,2,1.00,2.00,NULL,'2027-05-31'),(331,3,334,2,1.00,2.00,NULL,'2027-06-30'),(332,3,335,3,1.00,3.00,NULL,'2028-03-31'),(333,3,336,17,1.00,17.00,NULL,NULL),(334,3,337,1,1.00,1.00,NULL,NULL),(335,3,338,7,1.00,7.00,NULL,NULL),(336,3,339,1,1.00,1.00,NULL,NULL),(337,3,340,1,1.00,1.00,NULL,NULL),(338,3,341,1,1.00,1.00,NULL,NULL),(339,3,342,7,1.00,7.00,NULL,NULL),(340,3,343,1,1.00,1.00,NULL,NULL);
/*!40000 ALTER TABLE `ingreso_detalles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ingresos`
--

DROP TABLE IF EXISTS `ingresos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ingresos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `veterinaria_id` int(11) NOT NULL,
  `proveedor` varchar(150) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipo` enum('compra','transferencia_salida','transferencia_entrada') NOT NULL DEFAULT 'compra',
  `transferencia_ref` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `veterinaria_id` (`veterinaria_id`),
  CONSTRAINT `ingresos_ibfk_1` FOREIGN KEY (`veterinaria_id`) REFERENCES `veterinarias` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ingresos`
--

LOCK TABLES `ingresos` WRITE;
/*!40000 ALTER TABLE `ingresos` DISABLE KEYS */;
INSERT INTO `ingresos` VALUES (3,1,'Surtido inicial','Surtido inicial - Reporte de Artículos Juniorista 09 de octubre',3169.00,1,'2026-10-11 17:07:10','compra',NULL);
/*!40000 ALTER TABLE `ingresos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventario`
--

DROP TABLE IF EXISTS `inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `veterinaria_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vet_prod` (`veterinaria_id`,`producto_id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `inventario_ibfk_1` FOREIGN KEY (`veterinaria_id`) REFERENCES `veterinarias` (`id`),
  CONSTRAINT `inventario_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=381 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventario`
--

LOCK TABLES `inventario` WRITE;
/*!40000 ALTER TABLE `inventario` DISABLE KEYS */;
INSERT INTO `inventario` VALUES (28,1,9,2,'2026-10-11 18:42:34'),(29,1,10,39,'2026-10-11 18:56:44'),(30,1,11,2,'2026-10-11 17:07:10'),(31,1,12,3,'2026-10-11 17:07:10'),(32,1,13,6,'2026-10-11 17:07:10'),(33,1,14,5,'2026-10-11 17:07:10'),(34,1,15,14,'2026-10-11 17:07:10'),(35,1,16,16,'2026-10-11 17:07:10'),(36,1,17,13,'2026-10-11 17:07:10'),(37,1,18,5,'2026-10-11 17:07:10'),(38,1,19,3,'2026-10-11 17:07:10'),(39,1,20,12,'2026-10-11 17:07:10'),(40,1,21,1,'2026-10-11 17:07:10'),(41,1,22,1,'2026-10-11 17:07:10'),(42,1,23,1,'2026-10-11 17:07:10'),(43,1,24,6,'2026-10-11 17:07:10'),(44,1,25,3,'2026-10-11 17:07:10'),(45,1,26,4,'2026-10-11 17:07:10'),(46,1,27,2,'2026-10-11 17:07:10'),(47,1,28,1,'2026-10-11 17:07:10'),(48,1,29,41,'2026-10-11 17:07:10'),(49,1,30,1,'2026-10-11 17:07:10'),(50,1,31,2,'2026-10-11 17:07:10'),(51,1,32,1,'2026-10-11 17:07:10'),(52,1,33,1,'2026-10-11 17:07:10'),(53,1,34,6,'2026-10-11 17:07:10'),(54,1,35,4,'2026-10-11 17:07:10'),(55,1,36,5,'2026-10-11 17:07:10'),(56,1,37,1,'2026-10-11 17:07:10'),(57,1,38,3,'2026-10-11 17:07:10'),(58,1,39,4,'2026-10-11 17:07:10'),(59,1,40,1,'2026-10-11 17:07:10'),(60,1,41,1,'2026-10-11 17:07:10'),(61,1,42,2,'2026-10-11 17:07:10'),(62,1,43,2,'2026-10-11 17:07:10'),(63,1,44,2,'2026-10-11 17:07:10'),(64,1,45,2,'2026-10-11 17:07:10'),(65,1,46,2,'2026-10-11 17:07:10'),(66,1,47,3,'2026-10-11 17:07:10'),(67,1,48,3,'2026-10-11 17:07:10'),(68,1,49,2,'2026-10-11 17:07:10'),(69,1,50,5,'2026-10-11 17:07:10'),(70,1,51,2,'2026-10-11 17:07:10'),(71,1,52,2,'2026-10-11 17:07:10'),(72,1,53,3,'2026-10-11 17:07:10'),(73,1,54,1,'2026-10-11 17:07:10'),(74,1,55,2,'2026-10-11 17:07:10'),(75,1,56,42,'2026-10-11 17:07:10'),(76,1,57,13,'2026-10-11 17:07:10'),(77,1,58,4,'2026-10-11 17:07:10'),(78,1,59,2,'2026-10-11 17:07:10'),(79,1,60,3,'2026-10-11 17:07:10'),(80,1,61,3,'2026-10-11 17:07:10'),(81,1,62,2,'2026-10-11 17:07:10'),(82,1,63,5,'2026-10-11 17:07:10'),(83,1,64,2,'2026-10-11 17:07:10'),(84,1,65,1,'2026-10-11 17:07:10'),(85,1,66,1,'2026-10-11 17:07:10'),(86,1,67,6,'2026-10-11 17:07:10'),(87,1,68,1,'2026-10-11 17:07:10'),(88,1,69,1,'2026-10-11 17:07:10'),(89,1,70,2,'2026-10-11 17:07:10'),(90,1,71,2,'2026-10-11 17:07:10'),(91,1,72,1,'2026-10-11 17:07:10'),(92,1,73,2,'2026-10-11 17:07:10'),(93,1,74,2,'2026-10-11 17:07:10'),(94,1,75,1,'2026-10-11 17:07:10'),(95,1,76,1,'2026-10-11 17:07:10'),(96,1,77,4,'2026-10-11 17:07:10'),(97,1,78,2,'2026-10-11 17:07:10'),(98,1,79,3,'2026-10-11 17:07:10'),(99,1,80,3,'2026-10-11 17:07:10'),(100,1,81,3,'2026-10-11 17:07:10'),(101,1,82,2,'2026-10-11 17:07:10'),(102,1,83,1,'2026-10-11 17:07:10'),(103,1,84,1,'2026-10-11 17:07:10'),(104,1,85,3,'2026-10-11 17:07:10'),(105,1,86,4,'2026-10-11 17:07:10'),(106,1,87,4,'2026-10-11 17:07:10'),(107,1,88,4,'2026-10-11 17:07:10'),(108,1,89,1,'2026-10-11 17:07:10'),(109,1,90,1,'2026-10-11 17:07:10'),(110,1,91,1,'2026-10-11 17:07:10'),(111,1,92,8,'2026-10-11 17:07:10'),(112,1,93,1,'2026-10-11 17:07:10'),(113,1,94,4,'2026-10-11 17:07:10'),(114,1,95,1,'2026-10-11 17:07:10'),(115,1,96,1,'2026-10-11 17:07:10'),(116,1,97,3,'2026-10-11 17:07:10'),(117,1,98,63,'2026-10-11 17:07:10'),(118,1,99,3,'2026-10-11 17:07:10'),(119,1,100,5,'2026-10-11 17:07:10'),(120,1,101,4,'2026-10-11 17:07:10'),(121,1,102,5,'2026-10-11 17:07:10'),(122,1,103,3,'2026-10-11 17:07:10'),(123,1,104,24,'2026-10-11 17:07:10'),(124,1,105,6,'2026-10-11 17:07:10'),(125,1,106,1,'2026-10-11 17:07:10'),(126,1,107,2,'2026-10-11 17:07:10'),(127,1,108,2,'2026-10-11 17:07:10'),(128,1,109,2,'2026-10-11 17:07:10'),(129,1,110,64,'2026-10-11 17:07:10'),(130,1,111,1,'2026-10-11 17:07:10'),(131,1,112,2,'2026-10-11 17:07:10'),(132,1,113,59,'2026-10-11 17:07:10'),(133,1,114,98,'2026-10-11 17:07:10'),(134,1,115,3,'2026-10-11 17:07:10'),(135,1,116,1,'2026-10-11 17:07:10'),(136,1,117,5,'2026-10-11 17:07:10'),(137,1,118,6,'2026-10-11 17:07:10'),(138,1,119,6,'2026-10-11 17:07:10'),(139,1,120,6,'2026-10-11 17:07:10'),(140,1,121,1,'2026-10-11 17:07:10'),(141,1,122,2,'2026-10-11 17:07:10'),(142,1,123,1,'2026-10-11 17:07:10'),(143,1,124,1,'2026-10-11 17:07:10'),(144,1,125,3,'2026-10-11 17:07:10'),(145,1,126,1,'2026-10-11 17:07:10'),(146,1,127,7,'2026-10-11 17:07:10'),(147,1,128,2,'2026-10-11 17:07:10'),(148,1,129,12,'2026-10-11 17:07:10'),(149,1,130,4,'2026-10-11 17:07:10'),(150,1,131,6,'2026-10-11 17:07:10'),(151,1,132,2,'2026-10-11 17:07:10'),(152,1,133,2,'2026-10-11 17:07:10'),(153,1,134,9,'2026-10-11 17:07:10'),(154,1,135,2,'2026-10-11 17:07:10'),(155,1,136,2,'2026-10-11 17:07:10'),(156,1,137,2,'2026-10-11 17:07:10'),(157,1,138,4,'2026-10-11 17:07:10'),(158,1,139,4,'2026-10-11 17:07:10'),(159,1,140,2,'2026-10-11 17:07:10'),(160,1,141,7,'2026-10-11 17:07:10'),(161,1,142,1,'2026-10-11 17:07:10'),(162,1,143,15,'2026-10-11 17:07:10'),(163,1,144,1,'2026-10-11 17:07:10'),(164,1,145,4,'2026-10-11 17:07:10'),(165,1,146,4,'2026-10-11 17:07:10'),(166,1,147,1,'2026-10-11 17:07:10'),(167,1,148,4,'2026-10-11 17:07:10'),(168,1,149,2,'2026-10-11 17:07:10'),(169,1,150,3,'2026-10-11 17:07:10'),(170,1,151,6,'2026-10-11 17:07:10'),(171,1,152,12,'2026-10-11 17:07:10'),(172,1,153,2,'2026-10-11 17:07:10'),(173,1,154,6,'2026-10-11 17:07:10'),(174,1,155,4,'2026-10-11 17:07:10'),(175,1,156,1,'2026-10-11 17:07:10'),(176,1,157,2,'2026-10-11 17:07:10'),(177,1,158,1,'2026-10-11 17:07:10'),(178,1,159,3,'2026-10-11 17:07:10'),(179,1,160,2,'2026-10-11 17:07:10'),(180,1,161,2,'2026-10-11 17:07:10'),(181,1,162,10,'2026-10-11 17:07:10'),(182,1,163,3,'2026-10-11 17:07:10'),(183,1,164,3,'2026-10-11 17:07:10'),(184,1,165,8,'2026-10-11 17:07:10'),(185,1,166,4,'2026-10-11 17:07:10'),(186,1,167,2,'2026-10-11 17:07:10'),(187,1,168,3,'2026-10-11 17:07:10'),(188,1,169,4,'2026-10-11 17:07:10'),(189,1,170,38,'2026-10-11 17:07:10'),(190,1,171,7,'2026-10-11 17:07:10'),(191,1,172,7,'2026-10-11 17:07:10'),(192,1,173,1,'2026-10-11 17:07:10'),(193,1,174,9,'2026-10-11 17:07:10'),(194,1,175,5,'2026-10-11 17:07:10'),(195,1,176,6,'2026-10-11 17:07:10'),(196,1,177,94,'2026-10-11 17:07:10'),(197,1,178,7,'2026-10-11 17:07:10'),(198,1,179,55,'2026-10-11 17:07:10'),(199,1,180,4,'2026-10-11 17:07:10'),(200,1,181,1,'2026-10-11 17:07:10'),(201,1,182,3,'2026-10-11 17:07:10'),(202,1,183,4,'2026-10-11 17:07:10'),(203,1,184,1,'2026-10-11 17:07:10'),(204,1,185,34,'2026-10-11 17:07:10'),(205,1,186,74,'2026-10-11 17:07:10'),(206,1,187,294,'2026-10-11 17:07:10'),(207,1,188,2,'2026-10-11 17:07:10'),(208,1,189,5,'2026-10-11 17:07:10'),(209,1,190,2,'2026-10-11 17:07:10'),(210,1,191,2,'2026-10-11 17:07:10'),(211,1,192,3,'2026-10-11 17:07:10'),(212,1,193,1,'2026-10-11 17:07:10'),(213,1,194,15,'2026-10-11 17:07:10'),(214,1,195,5,'2026-10-11 17:07:10'),(215,1,196,1,'2026-10-11 17:07:10'),(216,1,197,1,'2026-10-11 17:07:10'),(217,1,198,3,'2026-10-11 17:07:10'),(218,1,199,45,'2026-10-11 17:07:10'),(219,1,200,1,'2026-10-11 17:07:10'),(220,1,201,1,'2026-10-11 17:07:10'),(221,1,202,18,'2026-10-11 17:07:10'),(222,1,203,2,'2026-10-11 17:07:10'),(223,1,204,4,'2026-10-11 17:07:10'),(224,1,205,2,'2026-10-11 17:07:10'),(225,1,206,49,'2026-10-11 17:07:10'),(226,1,207,2,'2026-10-11 17:07:10'),(227,1,208,43,'2026-10-11 17:07:10'),(228,1,209,2,'2026-10-11 17:07:10'),(229,1,210,3,'2026-10-11 17:07:10'),(230,1,211,2,'2026-10-11 17:07:10'),(231,1,212,1,'2026-10-11 17:07:10'),(232,1,213,2,'2026-10-11 17:07:10'),(233,1,214,180,'2026-10-11 17:07:10'),(234,1,215,32,'2026-10-11 17:07:10'),(235,1,216,16,'2026-10-11 17:07:10'),(236,1,217,2,'2026-10-11 17:07:10'),(237,1,218,1,'2026-10-11 17:07:10'),(238,1,219,8,'2026-10-11 17:07:10'),(239,1,220,9,'2026-10-11 17:07:10'),(240,1,221,12,'2026-10-11 17:07:10'),(241,1,222,7,'2026-10-11 17:07:10'),(242,1,223,7,'2026-10-11 17:07:10'),(243,1,224,7,'2026-10-11 17:07:10'),(244,1,225,1,'2026-10-11 17:07:10'),(245,1,226,10,'2026-10-11 17:07:10'),(246,1,227,2,'2026-10-11 17:07:10'),(247,1,228,1,'2026-10-11 17:07:10'),(248,1,229,5,'2026-10-11 17:07:10'),(249,1,230,2,'2026-10-11 17:07:10'),(250,1,231,4,'2026-10-11 17:07:10'),(251,1,232,4,'2026-10-11 17:07:10'),(252,1,233,1,'2026-10-11 17:07:10'),(253,1,234,4,'2026-10-11 17:07:10'),(254,1,235,32,'2026-10-11 17:07:10'),(255,1,236,13,'2026-10-11 17:07:10'),(256,1,237,11,'2026-10-11 17:07:10'),(257,1,238,44,'2026-10-11 17:07:10'),(258,1,239,32,'2026-10-11 17:07:10'),(259,1,240,2,'2026-10-11 17:07:10'),(260,1,241,2,'2026-10-11 17:07:10'),(261,1,242,2,'2026-10-11 17:07:10'),(262,1,243,18,'2026-10-11 17:07:10'),(263,1,244,4,'2026-10-11 17:07:10'),(264,1,245,4,'2026-10-11 17:07:10'),(265,1,246,3,'2026-10-11 17:07:10'),(266,1,247,4,'2026-10-11 17:07:10'),(267,1,248,3,'2026-10-11 17:07:10'),(268,1,249,4,'2026-10-11 17:07:10'),(269,1,250,5,'2026-10-11 17:07:10'),(270,1,251,18,'2026-10-11 17:07:10'),(271,1,252,10,'2026-10-11 17:07:10'),(272,1,253,4,'2026-10-11 17:07:10'),(273,1,254,2,'2026-10-11 17:07:10'),(274,1,255,6,'2026-10-11 17:07:10'),(275,1,256,1,'2026-10-11 17:07:10'),(276,1,257,3,'2026-10-11 17:07:10'),(277,1,258,8,'2026-10-11 17:07:10'),(278,1,259,4,'2026-10-11 17:07:10'),(279,1,260,25,'2026-10-11 17:07:10'),(280,1,261,2,'2026-10-11 17:07:10'),(281,1,262,2,'2026-10-11 17:07:10'),(282,1,263,3,'2026-10-11 17:07:10'),(283,1,264,2,'2026-10-11 17:07:10'),(284,1,265,5,'2026-10-11 17:07:10'),(285,1,266,5,'2026-10-11 17:07:10'),(286,1,267,1,'2026-10-11 17:07:10'),(287,1,268,2,'2026-10-11 17:07:10'),(288,1,269,8,'2026-10-11 17:07:10'),(289,1,270,10,'2026-10-11 17:07:10'),(290,1,271,3,'2026-10-11 17:07:10'),(291,1,272,2,'2026-10-11 17:07:10'),(292,1,273,2,'2026-10-11 17:07:10'),(293,1,274,1,'2026-10-11 17:07:10'),(294,1,275,1,'2026-10-11 17:07:10'),(295,1,276,1,'2026-10-11 17:07:10'),(296,1,277,1,'2026-10-11 17:07:10'),(297,1,278,1,'2026-10-11 17:07:10'),(298,1,279,2,'2026-10-11 17:07:10'),(299,1,280,3,'2026-10-11 17:07:10'),(300,1,281,4,'2026-10-11 17:07:10'),(301,1,282,3,'2026-10-11 17:07:10'),(302,1,283,87,'2026-10-11 17:07:10'),(303,1,284,21,'2026-10-11 17:07:10'),(304,1,285,10,'2026-10-11 17:07:10'),(305,1,286,1,'2026-10-11 17:07:10'),(306,1,287,77,'2026-10-11 17:07:10'),(307,1,288,10,'2026-10-11 17:07:10'),(308,1,289,6,'2026-10-11 17:07:10'),(309,1,290,58,'2026-10-11 17:07:10'),(310,1,291,3,'2026-10-11 17:07:10'),(311,1,292,7,'2026-10-11 17:07:10'),(312,1,293,1,'2026-10-11 17:07:10'),(313,1,294,20,'2026-10-11 17:07:10'),(314,1,295,8,'2026-10-11 17:07:10'),(315,1,296,3,'2026-10-11 17:07:10'),(316,1,297,1,'2026-10-11 17:07:10'),(317,1,298,2,'2026-10-11 17:07:10'),(318,1,299,3,'2026-10-11 17:07:10'),(319,1,300,2,'2026-10-11 17:07:10'),(320,1,301,4,'2026-10-11 17:07:10'),(321,1,302,3,'2026-10-11 17:07:10'),(322,1,303,1,'2026-10-11 17:07:10'),(323,1,304,3,'2026-10-11 17:07:10'),(324,1,305,5,'2026-10-11 17:07:10'),(325,1,306,4,'2026-10-11 17:07:10'),(326,1,307,6,'2026-10-11 17:07:10'),(327,1,308,71,'2026-10-11 17:07:10'),(328,1,309,11,'2026-10-11 17:07:10'),(329,1,310,4,'2026-10-11 17:07:10'),(330,1,311,16,'2026-10-11 17:07:10'),(331,1,312,1,'2026-10-11 17:07:10'),(332,1,313,14,'2026-10-11 17:07:10'),(333,1,314,8,'2026-10-11 17:07:10'),(334,1,315,3,'2026-10-11 17:07:10'),(335,1,316,5,'2026-10-11 17:07:10'),(336,1,317,12,'2026-10-11 17:07:10'),(337,1,318,65,'2026-10-11 17:07:10'),(338,1,319,2,'2026-10-11 17:07:10'),(339,1,320,3,'2026-10-11 17:07:10'),(340,1,321,6,'2026-10-11 17:07:10'),(341,1,322,1,'2026-10-11 17:07:10'),(342,1,323,82,'2026-10-11 17:07:10'),(343,1,324,5,'2026-10-11 17:07:10'),(344,1,325,1,'2026-10-11 17:07:10'),(345,1,326,1,'2026-10-11 17:07:10'),(346,1,327,2,'2026-10-11 17:07:10'),(347,1,328,2,'2026-10-11 17:07:10'),(348,1,329,5,'2026-10-11 17:07:10'),(349,1,330,5,'2026-10-11 17:07:10'),(350,1,331,1,'2026-10-11 17:07:10'),(351,1,332,7,'2026-10-11 17:07:10'),(352,1,333,2,'2026-10-11 17:07:10'),(353,1,334,2,'2026-10-11 17:07:10'),(354,1,335,3,'2026-10-11 17:07:10'),(355,1,336,17,'2026-10-11 17:07:10'),(356,1,337,1,'2026-10-11 17:07:10'),(357,1,338,7,'2026-10-11 17:07:10'),(358,1,339,1,'2026-10-11 17:07:10'),(359,1,340,1,'2026-10-11 17:07:10'),(360,1,341,1,'2026-10-11 17:07:10'),(361,1,342,7,'2026-10-11 17:07:10'),(362,1,343,1,'2026-10-11 17:07:10');
/*!40000 ALTER TABLE `inventario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mascotas`
--

DROP TABLE IF EXISTS `mascotas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mascotas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `especie` varchar(50) NOT NULL DEFAULT 'Perro',
  `raza` varchar(100) DEFAULT NULL,
  `sexo` enum('macho','hembra') DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `veterinaria_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_mascotas_cliente` (`cliente_id`),
  KEY `idx_mascotas_vet` (`veterinaria_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mascotas`
--

LOCK TABLES `mascotas` WRITE;
/*!40000 ALTER TABLE `mascotas` DISABLE KEYS */;
/*!40000 ALTER TABLE `mascotas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `peluquerias`
--

DROP TABLE IF EXISTS `peluquerias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `peluquerias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `veterinaria_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `especie` enum('perro','gato') NOT NULL DEFAULT 'perro',
  `descripcion` varchar(255) NOT NULL,
  `valor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `pct_peluqueria` decimal(5,2) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_peluquerias_vet_fecha` (`veterinaria_id`,`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `peluquerias`
--

LOCK TABLES `peluquerias` WRITE;
/*!40000 ALTER TABLE `peluquerias` DISABLE KEYS */;
/*!40000 ALTER TABLE `peluquerias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `perdida_detalles`
--

DROP TABLE IF EXISTS `perdida_detalles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perdida_detalles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `perdida_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(10,2) GENERATED ALWAYS AS (`cantidad` * `precio_unitario`) STORED,
  PRIMARY KEY (`id`),
  KEY `perdida_id` (`perdida_id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `perdida_detalles_ibfk_1` FOREIGN KEY (`perdida_id`) REFERENCES `perdidas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `perdida_detalles_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `perdida_detalles`
--

LOCK TABLES `perdida_detalles` WRITE;
/*!40000 ALTER TABLE `perdida_detalles` DISABLE KEYS */;
/*!40000 ALTER TABLE `perdida_detalles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `perdidas`
--

DROP TABLE IF EXISTS `perdidas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perdidas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `veterinaria_id` int(11) NOT NULL,
  `motivo` enum('perdida','vencimiento','a_bodega','propietario') NOT NULL DEFAULT 'perdida',
  `responsable` varchar(150) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `veterinaria_id` (`veterinaria_id`),
  CONSTRAINT `perdidas_ibfk_1` FOREIGN KEY (`veterinaria_id`) REFERENCES `veterinarias` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `perdidas`
--

LOCK TABLES `perdidas` WRITE;
/*!40000 ALTER TABLE `perdidas` DISABLE KEYS */;
/*!40000 ALTER TABLE `perdidas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `producto_relacionados`
--

DROP TABLE IF EXISTS `producto_relacionados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `producto_relacionados` (
  `producto_id` int(11) NOT NULL,
  `relacionado_id` int(11) NOT NULL,
  PRIMARY KEY (`producto_id`,`relacionado_id`),
  KEY `relacionado_id` (`relacionado_id`),
  CONSTRAINT `producto_relacionados_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `producto_relacionados_ibfk_2` FOREIGN KEY (`relacionado_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto_relacionados`
--

LOCK TABLES `producto_relacionados` WRITE;
/*!40000 ALTER TABLE `producto_relacionados` DISABLE KEYS */;
/*!40000 ALTER TABLE `producto_relacionados` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `productos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `codigo` varchar(80) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `categoria` varchar(80) DEFAULT NULL,
  `unidad` varchar(40) DEFAULT 'unidad',
  `precio_compra` decimal(10,2) NOT NULL DEFAULT 0.00,
  `precio_venta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock` int(11) NOT NULL DEFAULT 0,
  `stock_minimo` int(11) NOT NULL DEFAULT 5,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `veterinaria_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=345 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES (9,'ACIFLUX SUSPENSION ORAL 50 ML','A35I3','SUSPENSION','PRODUCTOS','unidad',1.00,48000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(10,'ACIFLUX TABLETA X 20 UNID','0009123351','500 MG','PRODUCTOS','unidad',1.00,3000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(11,'ALGILIS 220 GR POMADA COASPHARMA','11913JFG1980','POMADA','PRODUCTOS','unidad',1.00,42000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(12,'ANTI ESTRES GATO PET VET','54034HSAQPÑ5','JUGUETE','PET SHOP','unidad',1.00,15000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(13,'ANTIARREICO CAOLIN PECTINA 120 ML','2231320019800','120 ML','PRODUCTOS','unidad',1.00,26000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(14,'ANTIBIOTICO QUIMOPEN 1.500.000','260840012600','5 ML','PRODUCTOS','unidad',1.00,22000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(15,'ANTICOCCIDIAL COCCIBEC 10 ML','225840012600','10 ML','PRODUCTOS','unidad',1.00,22000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(16,'ANTIHISTAMINICO ALERNEX X10 C/U','18424003600','5 MG','PRODUCTOS','unidad',1.00,4000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(17,'ANTIMICROBIANO KEIAPRIM SUSPENSION 30 ML','2431200018000','30 ML','PRODUCTOS','unidad',1.00,20000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(18,'ANTIMICROBIANO PACTRIM SUSPENSION','2421320019800','30 ML','PRODUCTOS','unidad',1.00,18500.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(19,'ANTISEPTICO BACTEROL 120 ML','2402160032400','120 ML','PRODUCTOS','unidad',1.00,36000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(20,'APOQUEL 16 MG LAB ZOETIS','AH33353','16 ML','PRODUCTOS','unidad',1.00,14000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(21,'ARENERO BAÑERA','3B3GBU3G3G','ARENERO BAÑERA GRANDE','PET SHOP','unidad',1.00,40000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(22,'ARNES DE GUIA + SEGURO TALLA XS','F45SV54654','PECHERA','PET SHOP','unidad',1.00,45000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(23,'BAÑO SECO DESODORATE 160 GR PELU-2','OHFGTW54937','BAÑO SECO','PRODUCTOS','unidad',1.00,24000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(24,'BAÑO SECO IRGASAN 6.5% MASCOTAS 100 GR','OHFGTW5493','BAÑO SECO','PRODUCTOS','unidad',1.00,18000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(25,'BAÑO SECO PROTERCAN 100 GR INSECTICIDA','OHFGTW54931','BAÑO SECO','PRODUCTOS','unidad',1.00,18000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(26,'BAÑOL EC 33 ML AMITRAZ 12,5%','K56UY464YRJKO','GARRAPATICIDA','PRODUCTOS','unidad',1.00,12000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(27,'BAXIDIN SPRAY SOLUCION ANTISEPTICA 120 ML','FRQW60369GRH','ANTISEPTICO','PRODUCTOS','unidad',1.00,42000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(28,'BOLA CON CUERDA','PS-01054D0W654','JUGUETE','PET SHOP','unidad',1.00,20000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(29,'BOLSA PARA RECOGER POPO','V86VB74521','ASEO','PET SHOP','unidad',1.00,2500.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(30,'BOLSO CARGADOR LONA GRANDE','PS-013','TRANSPORTADOR','PET SHOP','unidad',1.00,105000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(31,'BOLSO CARGADOR LONA MEDIANO','DGW841469DF','TRANSPORTADOR','PET SHOP','unidad',1.00,85000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(32,'BOLSO MALETA 624 PEQUEÑA','SWR94069GE9','TRANSPORTADOR','PET SHOP','unidad',1.00,95000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(33,'BOLSO MALETA 625 MEDIANO','PS-015','TRANSPORTADOR','PET SHOP','unidad',1.00,105000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(34,'BOZAL CANASTA #3','O54HJH546630','BOZAL','PET SHOP','unidad',1.00,31000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(35,'BOZAL CANASTA #4','KU446FR56G46','BOZAL','PET SHOP','unidad',1.00,35000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(36,'BOZAL CANASTA #5','H5446FR56G46','BOZAL','PET SHOP','unidad',1.00,41000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(37,'BOZAL CON REJILLA #2','IP546H46F65485','BOZAL','PET SHOP','unidad',1.00,32000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(38,'BOZAL CON REJILLA #3','PS-034','articulo petshop','PET SHOP','unidad',1.00,42000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(39,'BOZAL CON REJILLA #5','P546GJ46366','BOZAL','PET SHOP','unidad',1.00,52000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(40,'BOZAL CONICO TALLA # 0','OLPÑRTGQ8796036Q','BOZAL','PET SHOP','unidad',1.00,10000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(41,'BOZAL CONICO TALLA # 1','AQMLI49763WDF','BOZAL','PET SHOP','unidad',1.00,12000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(42,'BOZAL CONICO TALLA # 2','LJKNGFRXZ5469QD','BOZAL','PET SHOP','unidad',1.00,14000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(43,'BOZAL CONICO TALLA # 3','LKMQPÑMZAG7964F','BOZAL','PET SHOP','unidad',1.00,15000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(44,'BOZAL PITBULL TALLA # 1','JBMVFH46963A','BOZAL','PET SHOP','unidad',1.00,35000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(45,'BOZAL PITBULL TALLA # 2','MNJ7630AFGTJK','BOZAL','PET SHOP','unidad',1.00,40000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(46,'BOZAL PITBULL TALLA # 3','MNHJ7824SDDFE','BOZAL','PET SHOP','unidad',1.00,45000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(47,'BOZAL SILICONA CONICO TALLA L','PS-036','articulo petshop','PET SHOP','unidad',1.00,52000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(48,'BOZAL SILICONA CONICO TALLA M','UIAEHGUIAEGH','AHUIHAGEUI','PET SHOP','unidad',1.00,35000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(49,'BOZAL SILICONA CONICO TALLA S','PS-037','articulo petshop','PET SHOP','unidad',1.00,25000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(50,'BOZAL SILICONA CONICO TALLA XL','PS-035','articulo petshop','PET SHOP','unidad',1.00,68000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(51,'BOZAL TELA CONICO # 4','MNJHY4796AFR','BOZAL','PET SHOP','unidad',1.00,18000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(52,'BOZAL TELA SEGURIDAD # 4','MNHJK796435ASE','BOZAL','PET SHOP','unidad',1.00,28000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(53,'BOZAL TELA SEGURIDAD # 5','NBGJ79643ADWB','BOZAL','PET SHOP','unidad',1.00,30000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(54,'BOZAL TELA SEGURIDAD # 6','MNYHJ7963ADE','BOZAL','PET SHOP','unidad',1.00,32000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(55,'BOZAL TELA SEGURIDAD # 7','MNHJKW489661A','BOZAL','PET SHOP','unidad',1.00,38000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(56,'BROMU PET TABLETA BROMURO DE POTASIO','LKFBHY961032P','MEDICAMENTO','PRODUCTOS','unidad',1.00,13000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(57,'BUCAL BARKFRESH 5-15KL','19824003600','125 MG','PRODUCTOS','unidad',1.00,7000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(58,'BUZO DE NAVIDAD TALLA M','Y884ERE646','BUZO','PET SHOP','unidad',1.00,45000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(59,'BUZO DE NAVIDAD TALLA S','I47U6T4620','BUZO','PET SHOP','unidad',1.00,42000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(60,'CADENA BARBADA #10','PS-040','articulo petshop','PET SHOP','unidad',1.00,28000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(61,'CADENA BARBADA #8','PS-042','articulo petshop','PET SHOP','unidad',1.00,34000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(62,'CADENA COLLAR DE PERLAS CON 1 DIJE BURBUJA','PS-038','articulo petshop','PET SHOP','unidad',1.00,22000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(63,'CADENA COLLAR DORADA GRANDE','PS-043','articulo petshop','PET SHOP','unidad',1.00,40000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(64,'CADENA ESLABONADA #6','UG45125R45','PASEADOR','PET SHOP','unidad',1.00,33000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(65,'Cadena Retorcida #16','PS-043','articulo petshop','PET SHOP','unidad',1.00,18000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(66,'Cadena Retorcida #8','PS-044','articulo petshop','PET SHOP','unidad',1.00,30000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(67,'CALCIO VITAMINA VIGOCAL 120ML','1522160032400','120 ML','PRODUCTOS','unidad',1.00,38000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(68,'CAMA CUBO MEDIANA','RTYUJGD','IUY6TGHJKJH','PET SHOP','unidad',1.00,95000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(69,'CAMA MEDIANA FELPA PELUCHE','Q5445WE455','CAMA','PET SHOP','unidad',1.00,85000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(70,'CAMA NACIONAL TALLA GRANDE','MNFBGJH79640AF','CAMA','PET SHOP','unidad',1.00,95000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(71,'CAMA NACIONAL TALLA MEDIANA','KGDFWH5039D','CAMA','PET SHOP','unidad',1.00,85000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(72,'CAMA NACIONAL TALLA PEQUEÑA','JUDCV89607QD','CAMA','PET SHOP','unidad',1.00,75000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(73,'CAMISA IMPORTADA DE COLOMBIA GRANDE','Q54TYTR216541','CAMISA','PET SHOP','unidad',1.00,35000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(74,'CAMISA IMPORTADA DE COLOMBIA MEDIANA','L545HJ6416R','CAMISA','PET SHOP','unidad',1.00,30000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(75,'CAMISA IMPORTADA DE COLOMBIA PEQUEÑA','GDD5466HG4T','CAMISA','PET SHOP','unidad',1.00,25000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(76,'CAMISA NACIONAL TALLA L','UIAEGUIHAEGUI','AUIEGHUIAEGH','PET SHOP','unidad',1.00,25000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(77,'CAMISETA ESTAMPADA TALLA 1','PKHWBDFKW856','ROPA','PET SHOP','unidad',1.00,0.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(78,'CAMISETA ESTAMPADA TALLA 2','KHWBDFKW851','ROPA','PET SHOP','unidad',1.00,0.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(79,'CAMISETA ESTAMPADA TALLA 3','KHWBDFKW854','ROPA','PET SHOP','unidad',1.00,0.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(80,'CAMISETA ESTAMPADA TALLA 4','KHWBDFKW852','ROPA','PET SHOP','unidad',1.00,0.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(81,'CAMISETA ESTAMPADA TALLA 5','KHWBDFKW859','ROPA','PET SHOP','unidad',1.00,0.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(82,'CAMISETA HAWALLAIANA TALLA MEDIANA','HJTY4769LÑLOY','ACCESORIOS','PET SHOP','unidad',1.00,32000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(83,'CAMISETA TALLA MEDIANA SPORT','MNHT4796FEWGR','ACCESORIOS','PET SHOP','unidad',1.00,35000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(84,'CAMISILLA COLOMBIA TALLA M economica','PLMBGDTJ9642','ROPA','PET SHOP','unidad',1.00,24000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(85,'CAMISILLA COLOMBIA TALLA S economica','PLMBGDTJ9641','ROPA','PET SHOP','unidad',1.00,22000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(86,'CAMISILLA JUNIOR TU PAPA TALLA M economica','JDGWFOPMPÑQ2','ROPA','PET SHOP','unidad',1.00,24000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(87,'CAMISILLA JUNIOR TU PAPA TALLA S economica','JDGWFOPMPÑQ1','ROPA','PET SHOP','unidad',1.00,22000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(88,'CAMISILLA JUNIOR TU PAPA TALLA XS economica','JDGWFOPMPÑQ0','ROPA','PET SHOP','unidad',1.00,19000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(89,'CAMISILLA NACIONAL TALLA L','P58765OUY1','CAMISA','PET SHOP','unidad',1.00,25000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(90,'CAMISILLA NACIONAL TALLA M','LKMQP790346FWE','ACCESORIOS','PET SHOP','unidad',1.00,22000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(91,'CAMISILLA NACIONAL TALLA S','D578RY5469','CAMISA','PET SHOP','unidad',1.00,20000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(92,'CANDU DOG SAZONADOR GUORMET 45 GR','WETA64903610','COMIDA HUMEDA','PET SHOP','unidad',1.00,4000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(93,'CANES FULL SPOT 41 A 60','A9','41 A 60','PRODUCTOS','unidad',1.00,75000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(94,'CARDA CEPILLO PET CUADRADO TALLA GRANDE','LMG5540SE64T','ASEO','PET SHOP','unidad',1.00,32000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(95,'CARDA CEPILLO PET REDONDO TALLA PEQUEÑA','LHYHK4650DFDGR','ASEO','PET SHOP','unidad',1.00,32000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(96,'CARGADOR DE LUJO MEDIANO','U2UE2UHE','CARGADOR 0790','PET SHOP','unidad',1.00,170000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(97,'CARGADOR IMPORTADO MEDIANO','EFREWT','','PET SHOP','unidad',1.00,135000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(98,'CEFALEXINA 500 MG LAB CEFALONG','LKFBHY961037P','ANTIBIOTICO','PRODUCTOS','unidad',1.00,3000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(99,'CEFALEXINA B FEX 60 ML','2452160032400','ANTIBIOTICO','PRODUCTOS','unidad',1.00,38000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(100,'CEPILLO DESLANADOR TAMAÑO GRANDE','HNDCH540FJG','ASEO','PET SHOP','unidad',1.00,40000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(101,'CEPILLO PET CAM TALLA MEDIANA','KHWBDFKW753','ASEO','PET SHOP','unidad',1.00,28000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(102,'CEPILLO PLÁSTICO','PS-066','articulo petshop','PET SHOP','unidad',1.00,12000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(103,'CEPILLO RASCADOR PET VET','54034HSAQPÑ3','ASEO','PET SHOP','unidad',1.00,18000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(104,'CETIRIZINA ALER CAF C/U','24615002300','10 MG','PRODUCTOS','unidad',1.00,2500.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(105,'CHALECO ENCAJES MEDIANO','heuihuaevivhaehiaev','aevuiaehaevuiu','PET SHOP','unidad',1.00,40000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(106,'CINTA RETRACTIL 3 M DOG LESH','PS-075','articulo petshop','PET SHOP','unidad',1.00,40000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(107,'CINTA RETRACTIL 3 M DOG LESH 2 REFERENCIA','U4451T574521','PASEADOR','PET SHOP','unidad',1.00,60000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(108,'CINTA RETRACTIL 5 M DOG LESH','PS-076','articulo petshop','PET SHOP','unidad',1.00,46000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(109,'CINTA RETRACTIL 5 M DOG LESH 2 REFERENCIA','W545Q54527','PASEADOR','PET SHOP','unidad',1.00,60000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(110,'CIPERVET 100 MG','A5','100 MG','PRODUCTOS','unidad',1.00,2000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(111,'CIPROVET GOTAS 5 ML','A6','GOTAS|','PRODUCTOS','unidad',1.00,68000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(112,'CLAVUPET ORAL 50 ML','HHG','JVY','PRODUCTOS','unidad',1.00,62000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(113,'COASPHARMA KETOCONAZOL 200MG','10573470046800','200 MG','PRODUCTOS','unidad',1.00,3000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(114,'COASPHARMA PREDNISOLONA 20MG','3455031800','20MG','PRODUCTOS','unidad',1.00,3500.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(115,'COLLAR AHOGO MARTILLADO 30X55','PS-077','articulo petshop','PET SHOP','unidad',1.00,18000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(116,'COLLAR AHOGO MARTILLADO 35X60','PS-078','articulo petshop','PET SHOP','unidad',1.00,20000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(117,'COLLAR AHOGO MARTILLADO 40X70','E4785Q6744G','COLLAR','PET SHOP','unidad',1.00,22000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(118,'COLLAR DE AHOGO YOBER','baieghaeihg','abgihagi','PET SHOP','unidad',1.00,35000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(119,'COLLAR DOBLE FUNCION YOBER','IHAIHAEIF','AHEIHAE','PET SHOP','unidad',1.00,45000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(120,'COLLAR GRANDE PET VET','54034HSAQ11P','COLLAR','PET SHOP','unidad',1.00,16000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(121,'COLLAR IMPORTADO DOG TALLA M','TDYTGFK','','PET SHOP','unidad',1.00,35000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(122,'COLLAR IMPORTADO DOG TALLA XL','YUFYHJFV','','PET SHOP','unidad',1.00,52000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(123,'COLLAR IMPORTADO TALLA M COLORES SURTIDOS','3G2FGV3','GE3J','PET SHOP','unidad',1.00,30000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(124,'COLLAR ISABELINO # 9','HUIAEGHUIAEG','ISABELINO','PET SHOP','unidad',1.00,68000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(125,'COLLAR ISABELINO #1','71','ISABELINO','PET SHOP','unidad',1.00,15000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(126,'COLLAR ISABELINO #3','73','ISABELINO','PET SHOP','unidad',1.00,25000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(127,'COLLAR ISABELINO #4','74','ISABELINO','PET SHOP','unidad',1.00,30000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(128,'COLLAR MEDIANO PET VET','54034HSAQ10P','COLLAR','PET SHOP','unidad',1.00,12000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(129,'COLLAR NACIONAL ECONOMICO TALLA L','JWFQYOL99761','COLLAR','PET SHOP','unidad',1.00,16000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(130,'COLLAR PAÑOLETA','MBWHO58403QWQ','COLLAR','PET SHOP','unidad',1.00,8000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(131,'COLLAR TRADILLA YOBER','W871W64163','PASEADOR','PET SHOP','unidad',1.00,30000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(132,'COMBIT ORAL 120 ML','1457770019800','120 ML','PRODUCTOS','unidad',1.00,22000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(133,'COMEDERO ANTI HORMIGA PET VET','54034VSFGPÑOQ3','COMEDERO','PET SHOP','unidad',1.00,15000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(134,'COMEDERO ANTIDESLIZANTE DOBLE MEDIANO','PET-X-SHOP 009','COMEDERO','PET SHOP','unidad',1.00,20000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(135,'COMEDERO ANTIDESLIZANTE DOBLE PEQUEÑO','PET-X-SHOP 010','COMEDERO','PET SHOP','unidad',1.00,14000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(136,'COMEDERO ANTIDESLIZANTE DOBLE GRANDE','UIAEGHUIAEGHAEG','AEBGAEHUI','PET SHOP','unidad',1.00,30000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(137,'COMEDERO AUTOMATICO','C548B56F165','COMEDERO','PET SHOP','unidad',1.00,65000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(138,'COMEDERO CARA GATO PET VET','54034VSFGPÑO10','COMEDERO','PET SHOP','unidad',1.00,15000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(139,'COMEDERO CARA RATON PET VET','54034VSFGPÑO11','COMEDERO','PET SHOP','unidad',1.00,15000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(140,'COMEDERO CORAZON PET VET','54034VSFGPÑOQ7','COMEDERO','PET SHOP','unidad',1.00,16000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(141,'COMEDERO DOBLE ESPACIAL MEDIANO','UIGHUISGEHGUIS','IIUHEAUISGHE','PET SHOP','unidad',0.00,20000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(142,'COMEDERO DOBLE GATO PET VET','54034VSFGPÑOQ2','COMEDERO','PET SHOP','unidad',1.00,18000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(143,'COMEDERO DOBLE GRANDE','PET-X-SHOP 026','COMEDERO','PET SHOP','unidad',1.00,20000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(144,'COMEDERO DOBLE HUESITOS GRANDE','PET-X-SHOP 018','COMEDERO','PET SHOP','unidad',1.00,18000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(145,'COMEDERO DOBLE HUESITOS MEDIANO','PET-X-SHOP 019','COMEDERO','PET SHOP','unidad',0.00,16000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(146,'COMEDERO FIGURAS REDONDAS MEDIANA','PET-X-SHOP 020','COMEDERO','PET SHOP','unidad',1.00,14000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(147,'COMEDERO GATO MIUO PET VET','54034VSFGPÑOQ8','COMEDERO','PET SHOP','unidad',1.00,14000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(148,'COMEDERO DOBLE HUESO GRANDE PET VET','54034VSFGPÑOQ1','COMEDERO','PET SHOP','unidad',1.00,24000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(149,'COMEDERO IMPORTADO GATO','EDGY','COMEDERO','PET SHOP','unidad',1.00,20000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(150,'COMEDERO METALICO PET VET','54034VSFGPÑO12','COMEDERO','PET SHOP','unidad',1.00,18000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(151,'COMEDERO OVALADO DOBLE GRANDE PUNTAS','PET-X-SHOP 027','COMEDERO','PET SHOP','unidad',1.00,23000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(152,'COMEDERO OVALADO DOBLE PUNTAS PEQUEÑO','PET-X-SHOP 030','COMEDERO','PET SHOP','unidad',1.00,15000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(153,'COMEDERO REDONDO ANTIDESLIZANTE MEDIANO','PET-X-SHOP 033','COMEDERO','PET SHOP','unidad',1.00,14000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(154,'COMEDERO REDONDO ANTIDESLIZANTE PEQUEÑO','PET-X-SHOP 034','COMEDERO','PET SHOP','unidad',1.00,11000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(155,'COMEDERO REDONDO ECONOMICO PET VET','54034VSFGPÑOQ4','COMEDERO','PET SHOP','unidad',1.00,14000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(156,'COMEDERO REDONDO PET VET','54034VSFGPÑOQ5','COMEDERO','PET SHOP','unidad',1.00,10000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(157,'CONABSORB ORAL 120 ML LAB LATIN','PLNFDFR4582NHG0','VITAMINA','PRODUCTOS','unidad',1.00,78000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(158,'CONCENTRADO CHUNKY CANINO RAZA PEQUEÑA ADULTOS 500GR','2136005700','CONCENTRADO','PET SHOP','unidad',1.00,6000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(159,'CONCENTRADO LUCKY CUIDADO ARTICULAR 2 KG','9637LHBQAZ8','CONCENTRADO','PET SHOP','unidad',1.00,68000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(160,'CONCENTRADO LUCKY CUIDADO PIEL 2 KG','9637LHBQAZ9','CONCENTRADO','PET SHOP','unidad',1.00,65000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(161,'CONCENTRADO LUCKY GATOS CASTRADOS 2 KG','9637LHBQAZ7','CONCENTRADO','PET SHOP','unidad',1.00,82000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(162,'CONCENTRADO NUTRISS ADULTO 1KG','191080017100','CONCENTRADO','PET SHOP','unidad',1.00,16000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(163,'CORREA LIMITE TRAFICO','XCVB','XCV','PET SHOP','unidad',1.00,60000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(164,'CORTA UÑAS MINI','54034VSFGPÑO16','ASEO','PET SHOP','unidad',1.00,12000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(165,'CREMA ALIVE PET','20042006600','10 G','PRODUCTOS','unidad',1.00,10000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(166,'CREMA CICATRIVET','21542006600','10 G','PRODUCTOS','unidad',1.00,12000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(167,'CREMA NEXA 100 GR','21472001HYT','CREMA','PRODUCTOS','unidad',1.00,32000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(168,'DERKET SHAMPOO KETOCONAZOL 200ML','EFERHREHT','SHAMPOO','PRODUCTOS','unidad',1.00,47000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(169,'DERMAPET SPRAY ANTIBACTERIANO','1172280034200','220 ML','PRODUCTOS','unidad',1.00,38000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(170,'DESPARASITANTE CANIPETS FORT 10 ML','F54565D452','DESPARASITANTE','PRODUCTOS','unidad',1.00,27000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(171,'DESPARASITANTE CANIPETS FORT 5 ML','A1926252525','5ML','PRODUCTOS','unidad',1.00,36000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(172,'DESPARASITANTE LAZARTIN 10 ML','2031560023400','10 ML','PRODUCTOS','unidad',1.00,32000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(173,'DESPARASITANTE LAZARTIN 5 ML','2021320019800','5 ML','PRODUCTOS','unidad',1.00,26000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(174,'DESPARASITANTE LEVACAN 2 ML','47548007200','2ML','PRODUCTOS','unidad',1.00,10000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(175,'DESPARASITANTE PIRACAN 2 ML','GHIOAEIOEGHOIAE','QWERTYUJHGFCVBNM','PRODUCTOS','unidad',1.00,15000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(176,'DESPARASITANTE PIRACAN 5 ML','2181080016200','5 ML','PRODUCTOS','unidad',1.00,20000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(177,'DOXICICLINA COASPHARMA 100 MG','2651002300','ANTIBIOTICO','PRODUCTOS','unidad',1.00,3000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(178,'DOXICICLINA DICICLIN 50 MG','AF3FAFAFA','ANTIBIOTICO','PRODUCTOS','unidad',1.00,2500.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(179,'DOXICICLINA DROXINTAB 100 MG','26315H02300','ANTIBIOTICO','PRODUCTOS','unidad',1.00,2500.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(180,'DOXY PET DOXICICLINA 30 ML','2311920028800','ANTIBIOTICO','PRODUCTOS','unidad',1.00,36000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(181,'EDO 5 SPRAY 120 ML LAB EDO','JFHE03976GR','ANTISEPTICO','PRODUCTOS','unidad',1.00,42000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(182,'EDO ARTIFLEX ORAL 120ML','GYFGOUFGFUI','VITAMINA','PRODUCTOS','unidad',1.00,52000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(183,'EDO CALCITECH 120ML','OFHWB7067Y','VITAMINA','PRODUCTOS','unidad',1.00,42000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(184,'ENERVIT 120 ML MULTIVITAMINICO','159100016200','120 ml','PRODUCTOS','unidad',1.00,38000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(185,'ENERVIT 30 ML SACHET LAB COASPHARMA','LMNHF79634Q','VITAMINA','PRODUCTOS','unidad',1.00,12000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(186,'ENROFLOXACINA 50 MG COASPHARMA','HJK3','5 MG','PRODUCTOS','unidad',1.00,3000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(187,'ENROFLOXACINA QUINOCALF 50 C/U','26412001800','50 MG','PRODUCTOS','unidad',1.00,3000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(188,'EXPECTORANTE BRONCOVET 120 ML','2071320019800','120 ML','PRODUCTOS','unidad',1.00,36000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(189,'EXPECTORANTE LAZARTRIL','2082520037800','120 ML','PRODUCTOS','unidad',1.00,42000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(190,'EXTER PUL','UHAEUIGHUIAEG','AUHEGUIAEGHI','PRODUCTOS','unidad',1.00,82000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(191,'FORMULA NATURAL 1.5 KG GATOS URINARIO','HFTH4TY4UK64Y6K','CONCENTRADO','PET SHOP','unidad',1.00,130000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(192,'FORMULA NATURAL ADULTOS MINI 1KG','EDFWFE','CONCENTRADO','PET SHOP','unidad',1.00,48000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(193,'FORMULA NATURAL GASTROINTESTINAL PERROS 2KG','Jdjjeje','CONCENTRADO','PET SHOP','unidad',1.00,138000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(194,'FORMULA NATURAL GATO CASTRADO 500 GR','UI65464HGFUJ','CONCENTRADO','PET SHOP','unidad',1.00,27000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(195,'FORMULA NATURAL GATOS CACHORROS 1 KG','9637LHBQAZ11','CONCENTRADO','PET SHOP','unidad',1.00,57000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(196,'FORMULA NATURAL HIPOALERGENICO 2 KG PEQUEÑO','ZC45V4F852','CONCENTRADO','PET SHOP','unidad',1.00,128000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(197,'FRON PET CANINO DE 20 A 40','JDGWBO79204W','ANTIPULGAS','PRODUCTOS','unidad',1.00,58000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(198,'FRON PET GATO DE 2 A 4','JDGWBO79203W','ANTIPULGAS','PRODUCTOS','unidad',1.00,38000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(199,'GALGOCAL 200 TABLETA','F5475HF45465','DESPARASITANTE','PRODUCTOS','unidad',1.00,8000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(200,'GARRAPATICIDA COLLAR KILTIX 35CM','862160032400','GARRAPATICIDA','PRODUCTOS','unidad',1.00,35000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(201,'GARRAPATICIDA COLLAR KILTIX 48CM','872HDSG36000','GARRAPATICIDA','PRODUCTOS','unidad',1.00,55000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(202,'GARRAPATICIDA MI FINOX 30 ML','531500023700','30 ml','PRODUCTOS','unidad',1.00,28000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(203,'GARRAPATICIDA NEXGARD 10 - 25 KG','734120046800','GARRAPATICIDA','PRODUCTOS','unidad',1.00,68000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(204,'GARRAPATICIDA NEXGARD 5 - 10 KG','732640039600','GARRAPATICIDA','PRODUCTOS','unidad',1.00,56000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(205,'GUANTE RASCADOR PET VET','54034HSAQPÑ6','JUGUETE','PET SHOP','unidad',1.00,12000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(206,'HEPA-TAB','ASAGSG2','X60','PRODUCTOS','unidad',1.00,4500.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(207,'HIZOL-T ACIDO SALICILICO 120 ML','4598700200000X','120 ML','PRODUCTOS','unidad',1.00,42000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(208,'HUESO DE CARNAZA PEQUEÑO POR UNIDAD','UIAEGUIHAEGUIGAE','AGYUAEGUI','PET SHOP','unidad',1.00,2500.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(209,'HUESO DURO DE ROER','SHR6645RDF','HUESO','PET SHOP','unidad',1.00,4000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(210,'HUESOS LLAMADIENTES OSCURIDAD','NEWP-07','articulo petshop','PET SHOP','unidad',1.00,15000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(211,'IBASA SHAMPOO MEDICADO BLANQUEADOR','HAEGIUHAEU','AIOEHAEUIF','PRODUCTOS','unidad',1.00,72000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(212,'IBASA SHAMPOO MEDICADO EFECTO GLOSS','AEGAEGAEG','AEGAEG','PRODUCTOS','unidad',1.00,72000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(213,'IBASA SHAMPOO MEDICADO PELO OSCURO','HAEGIUHAEU','AIOEHAEUIF','PRODUCTOS','unidad',1.00,68000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(214,'IMMUNO DEPRESOR ENGYSTOL X100 LAB HEEL','1661NFS8002800','INMUNODEPRESOR','PRODUCTOS','unidad',1.00,3500.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(215,'JABON ASUNTOL','HUIAEGHUIAEGHI','ABGEUIEGHUI','PRODUCTOS','unidad',1.00,18000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(216,'JUGUETE DE GATO','0028454847','JUGETE GATO','PET SHOP','unidad',1.00,5000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(217,'JUGUETE MACIZO CANGREJO','FVDE540364WEW','JUGUETE','PET SHOP','unidad',1.00,12500.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(218,'JUGUETE MACIZO DONA ESTRELLA','LMBD550F54EW','JUGUETE','PET SHOP','unidad',1.00,10500.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(219,'KEIATEL 2 ML DESPARASITANTE','544FSD5456F','DESPARASITANTE','PRODUCTOS','unidad',1.00,18000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(220,'KEIATEL PUPPY 2 ML DESPARASITANTE','AIEG554G4D652','DESPARASITANTE','PRODUCTOS','unidad',1.00,12000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(221,'KEIATEL PUPPY 5 ML DESPARASITANTE','FEWF9670364D','DESPARASITANTE','PRODUCTOS','unidad',1.00,22000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(222,'KEIAVIT SUSPENSION 120 LAB PAC','5210426HUGLK','VITAMINA','PRODUCTOS','unidad',1.00,26000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(223,'KERATINA DOGPET 120 ML','DRGHREH','ASEO','PRODUCTOS','unidad',1.00,25000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(224,'KERATINA DOGPET 60 ML','FSE451FE32AW','ASEO','PRODUCTOS','unidad',1.00,15000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(225,'KIT CORTA UÑAS MEDIANO','345YEH','234TFWERT','PET SHOP','unidad',1.00,22000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(226,'KIT DE CORTA UÑAS GRANDE','TRE56Y879E','ASEO','PET SHOP','unidad',1.00,26000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(227,'LACTOREEMPLAZADOR BRAVE STRONG 300 GR','2692160032400','LACTOREEMPLAZADOR','PRODUCTOS','unidad',1.00,38000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(228,'LACTOREMPLAZADOR LACV-PAC 100GR','LP-2756282-CV','100GR','PRODUCTOS','unidad',1.00,38000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(229,'LACTOREMPLAZADOR LACV-PAC 300GR','KYH454H63TK','LACTOREEMPLAZADOR','PRODUCTOS','unidad',1.00,66000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(230,'LATA NUTRE CAT ATUN Y CAMARON 85 GR','UIYRE49036QF','COMIDA HUMEDA','PET SHOP','unidad',1.00,12000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(231,'LATA NUTRE CAT ATUN Y PARGO 85 GR','UIYRE49037QF','COMIDA HUMEDA','PET SHOP','unidad',1.00,12000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(232,'LLANTA MACISA MEDIANA','Z2485VC528552','JUGUETE','PET SHOP','unidad',1.00,15000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(233,'MAGIC PETS ODOURLOCK 12 KG','AFSAK3BRJHK3BJH3','12 KG','PET SHOP','unidad',1.00,126000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(234,'MARBOFLOXACINA MARBOQUIN 25 MG','25815002300','ANTIBIOTICO','PRODUCTOS','unidad',1.00,3000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(235,'MELOXICAM 2 MG TABLETAS COASPHARMA','459870020000','ANTIFLAMATORIO','PRODUCTOS','unidad',1.00,3000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(236,'METRONIDAZOL METRONIDAZOL 250 MG','2541080016200','250 MG','PRODUCTOS','unidad',1.00,24000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(237,'METRONIDAZOL METRONIDAZOL 500 MG','2551320019800','500 MG','PRODUCTOS','unidad',1.00,28000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(238,'MIELAN PET','LKFBHY961031P','MEDICAMENTO','PRODUCTOS','unidad',1.00,13000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(239,'MOÑO POMPOSO X2','NEWP-10','articulo petshop','PET SHOP','unidad',1.00,10000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(240,'NATURES MIRACLE SPRAY DESTRUCTOR DE ORINA','E4F487SFG4','ASEO','PRODUCTOS','unidad',1.00,12000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(241,'NATURES MIRACLE SPRAY REPELENTE PARA GATOS','SRW4R454S','ASEO','PRODUCTOS','unidad',1.00,12000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(242,'NEODOXYL 30 ML SOLUCION ORAL','14624602755800','ANTIBIOTICO','PRODUCTOS','unidad',1.00,62000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(243,'NERVO-HEEL X 50 TABLETAS','OPEHF50368Q0','HOMEOPATICO','PRODUCTOS','unidad',1.00,3500.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(244,'NUTRE CAN CON LECHE PAVO 2 KG ADULTO','9637LHBQAZ2','CONCENTRADO','PET SHOP','unidad',1.00,35000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(245,'NUTRE CAN CON LECHE SALMON 2 KG ADULTO','9637LHBQAZ3','CONCENTRADO','PET SHOP','unidad',1.00,38000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(246,'PALA RECOLECTORA EXTRA GRADE PARA ESCREMENTO','4GB4GJ4G','PALA','PET SHOP','unidad',1.00,20000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(247,'PALITROQUIS','00000687DEGR','SNACKS','PET SHOP','unidad',1.00,6000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(248,'PAÑALES DOG LOMBIA CLUB TALLA L (45 -63 CM)','GIK158R954GTJK','PAÑALES','PRODUCTOS','unidad',1.00,3000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(249,'PAÑALES DOG LOMBIA CLUB TALLA M (35 -50 CM)','GFH45FH6J58554','PAÑALES','PRODUCTOS','unidad',1.00,2500.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(250,'PAÑALES DOG LOMBIA CLUB TALLA S (25 -40 CM)','HD454D65JKKUJG','PAÑALES','PRODUCTOS','unidad',1.00,2000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(251,'PAÑALES DOG LOMBIA CLUB TALLA XL (64 -82 CM)','KJL1HJ55GH1FG5H4','PAÑALES','PRODUCTOS','unidad',1.00,3500.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(252,'PAÑOLETA COLLAR PET VET','54034HSAQ13P','COLLAR','PET SHOP','unidad',1.00,8000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(253,'PECHERA BICOLOR PINCHER PEQUEÑA','LDH525EFW65W','PECHERA','PET SHOP','unidad',1.00,7500.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(254,'PECHERA CHINA MEDIANA MOTIVOS','PS-0003','PET-SHOP','PET SHOP','unidad',1.00,20000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(255,'PECHERA CHINA PEQUEÑA MOTIVOS','PS-0004','PET-SHOP','PET SHOP','unidad',1.00,18000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(256,'PECHERA IMPORTADA ARCO IRIS','VBSE67400363W','PECHERA','PET SHOP','unidad',1.00,28000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(257,'PECHERA NACIONAL ECONOMICA','LJNGV540646FGB','PECHERA','PET SHOP','unidad',1.00,10000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(258,'PECHERA NACIONAL TALLA GRANDE','AEGAEGAE','AEGAEG','PET SHOP','unidad',1.00,35000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(259,'PECHERA NEGRA ECONOMICA TALLA L','HEWG4069AD79','PECHERA','PET SHOP','unidad',1.00,22000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(260,'PECHERA PINCHER PEQUEÑA','PS-0008','PET-SHOP','PET SHOP','unidad',1.00,15000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(261,'PECHERA PUNTICO ECONOMICA TALLA M','HEWG4069AD77','PECHERA','PET SHOP','unidad',1.00,18000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(262,'PECHERA TIPO TELA GRANDE','PS-0001','PET-SHOP','PET SHOP','unidad',1.00,75000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(263,'PECHERA TIPO TELA MEDIANA','UFGWI78039P','PECHERA','PET SHOP','unidad',1.00,55000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(264,'PECHERA TIPO TELA MOTIVO ESTANPANDO','DTRDTR','TDTRT','PET SHOP','unidad',1.00,20000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(265,'PECHERA VESTIDO','VB654','VB5EW','PET SHOP','unidad',1.00,20000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(266,'PEDIGREE DENTASTIX','5723004500','SNACKS','PET SHOP','unidad',1.00,5000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(267,'PEINE DESLANADOR PET VET','FWFJTH540676E','ASEO','PET SHOP','unidad',1.00,14000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(268,'PEINE LINEA PET VET','54034VSFGPÑO17','ASEO','PET SHOP','unidad',1.00,12000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(269,'PEINE SACA PIOJO PET VET','54034VSFGPÑO18','ASEO','PET SHOP','unidad',1.00,15000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(270,'PELOTA CHILLONA HUELLA','PS-0020','PET-SHOP','PET SHOP','unidad',1.00,19000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(271,'PELOTA CHILLONA RAYAS','RFHDGD','JUGUETE','PET SHOP','unidad',1.00,14000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(272,'PELOTA GRANADA CHILLONA','PS-0021','PET-SHOP','PET SHOP','unidad',1.00,18000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(273,'PELOTA NUDO PET VET','LDJW4D0W654','JUGUETE','PET SHOP','unidad',1.00,12000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(274,'PELOTA PESADO GRANDE','54034VSFGPÑO13','JUGUETE','PET SHOP','unidad',1.00,24000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(275,'PELOTA TENIS ESPUMA PET VET','54034VSFGPÑO15','JUGUETE','PET SHOP','unidad',1.00,15000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(276,'PELOTA TENIS PET VET','54034HSAQP8','JUGUETE','PET SHOP','unidad',1.00,8000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(277,'PELUCHE IMPORTADO DINO BLUE','PS-0027','PET-SHOP','PET SHOP','unidad',1.00,35000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(278,'PELUCHE IMPORTADO MAGUILA GORILA','PS-0029','PET-SHOP','PET SHOP','unidad',1.00,35000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(279,'PELUCHE VAQUITA','PS-0030','PET-SHOP','PET SHOP','unidad',1.00,35000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(280,'PERFUME CHANEL ULTRA','AM28258825682AA','120 ML','PRODUCTOS','unidad',1.00,38000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(281,'PERFUME CHANELL VET HEMBRA 120ML','411200018000','120 ML','PRODUCTOS','unidad',1.00,20000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(282,'PERFUME CHANELL VET MACHO 120ML','421200018000','120 ML','PRODUCTOS','unidad',1.00,20000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(283,'PERRITOS ENZIMAS DIGESTIVAS','A12','ENZIMAS DIGESTIVAS','PRODUCTOS','unidad',1.00,3200.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(284,'PERRITOS GLUCOSAMIDA','A11','GLUCOSAMIDA','PRODUCTOS','unidad',1.00,3200.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(285,'PERRITOS SILIMARINA + ACIDO LIPOICO + COMPLEJO B','104310002700','60 TABLETAS','PRODUCTOS','unidad',1.00,3200.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(286,'PET DOG TALLA S ARNES','PS-0032','PET-SHOP','PET SHOP','unidad',1.00,55000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(287,'PREDNISOLONA PRETAB 5 MG','24712001800','5 MG','PRODUCTOS','unidad',1.00,2000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(288,'PRONALPETS SUSPECION ORAL 10ML','196720010800','10 ML','PRODUCTOS','unidad',1.00,26000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(289,'QUITAPELOS MAXI CLEAN','PS-0040','PET-SHOP','PET SHOP','unidad',1.00,20000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(290,'RENAL BALANCE LAB PET PRIME','14021003300','60 TABLETAS','PRODUCTOS','unidad',1.00,4500.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(291,'RESORTE CORREA EN CUERO','PS-0042','PET-SHOP','PET SHOP','unidad',1.00,15000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(292,'RESTADERM CREMA 30 GR','A636335356','CREMA DERMATOLOGICA','PRODUCTOS','unidad',1.00,36000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(293,'RESTADERM SOLUCION TOPICA 100ML','A55725722','100ML','PRODUCTOS','unidad',1.00,38000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(294,'RETRO VIRAL INMUNOG G X 30','14812001800','TABLETA','PRODUCTOS','unidad',1.00,2500.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(295,'SACHET MIGLIOR GATOS 100 GR','KEHYDBDOW4068','COMIDA HUMEDA','PET SHOP','unidad',1.00,4500.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(296,'SERALIX GEL ANTISARRO 30 GR LAB PETS KINGDOM','PLHCDE45898NBH','ASEO','PRODUCTOS','unidad',1.00,48000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(297,'SHAMPOO ANTISARNA 125 ML','IKDBJODF69603W','SHAMPOO','PRODUCTOS','unidad',1.00,18000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(298,'SHAMPOO ANTISARNA 230 ML','DLOHBWD6973','SHAMPOO','PRODUCTOS','unidad',1.00,26000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(299,'SHAMPOO DERMOHEX SHAMPOO 240ML','381680025200','240 ML','PRODUCTOS','unidad',1.00,36000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(300,'SHAMPOO INSEPTICIDA KEIADERM 240ML','392280034200','240 ML','PRODUCTOS','unidad',1.00,45000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(301,'SHAMPOO LIMPIEZA Y SUAVIDAD 240 ML','HIUAEGHAEG01','SHAMPOO','PRODUCTOS','unidad',1.00,28000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(302,'SHAMPOO MEDICADO DERMAPET 220 ML','272100033200','230 ML','PRODUCTOS','unidad',1.00,38000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(303,'SHAMPOO MEDICADO KEIADERM 100 ML','ERHRUJTRF','CHAMPU','PRODUCTOS','unidad',0.00,24000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(304,'SHAMPOO PELUDOS CATS 240ML','K3T8738T3T','240ML','PRODUCTOS','unidad',1.00,28000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(305,'SHARK ARNES','PS-0044','PET-SHOP','PET SHOP','unidad',1.00,75000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(306,'SOLUCION OTICA EXTALOE EAR 30 ML','1991320019800','30 ML','PRODUCTOS','unidad',1.00,38000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(307,'SONDA DE GATO PET PRIME','AGA3UTHKA3RHA3R','SONDA','PRODUCTOS','unidad',1.00,39000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(308,'SPASCUPREEL X 50 TABLETAS LAB HEEL','OPEHF50368Q1','ANTIESPASMODICO','PRODUCTOS','unidad',1.00,3500.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(309,'SUERO ORAL PET 400 ML','BF654036SDG4ER','SUERO','PRODUCTOS','unidad',1.00,22000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(310,'SULFACAN 60ML','13114900014200','60ML','PRODUCTOS','unidad',1.00,15000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(311,'SUPLADE 30 ML','A9983','30 ML','PRODUCTOS','unidad',1.00,6000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(312,'TERMO BEBEDERO 800ML','AEGAEGAE','AEGAEGAEG','PET SHOP','unidad',1.00,25000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(313,'TEST CDV DISTEMPER','LNFGHWJKBE96','TEST','PRODUCTOS','unidad',1.00,90000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(314,'TEST CPV PARVO','LNFGHWJKBE97','TEST','PRODUCTOS','unidad',1.00,90000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(315,'TEST DIROFILARIA','PLMIV579636A6','TEST','PRODUCTOS','unidad',1.00,90000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(316,'TEST ERLICHA','GV2BJEHB','HGV2','PRODUCTOS','unidad',1.00,90000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(317,'TETERO PEQUEÑO 60','52546008500','TETERO','PET SHOP','unidad',1.00,12000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(318,'TRAUMEL X 50 TABLETAS LAB HEEL','A106564KKL','HOMEOPATICO','PRODUCTOS','unidad',1.00,3500.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(319,'TRIMETOPRIM SULFAMETOXAZOL 120 ML COASPHARMA','2411920028800','120 ML','PRODUCTOS','unidad',1.00,46000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(320,'TRIPETS SUSPENSION ORAL 2ML','GNHMF54204SH5E','DESPARASITANTE','PRODUCTOS','unidad',1.00,16000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(321,'TRIPETS SUSPENSION ORAL 5ML','1967200108800','5ML','PRODUCTOS','unidad',1.00,18000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(322,'UPRONE-VET 60ML','HAEUIGHAEGU','AEGGAEH','PRODUCTOS','unidad',1.00,68000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(323,'UT BALANCE','1392003300','60 TABLETAS','PRODUCTOS','unidad',1.00,3500.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(324,'VESTIDO DE CARNAVAL TALLA M','G545F8789D','ACCESORIOS','PET SHOP','unidad',1.00,25000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(325,'VESTIDO DE NAVIDAD TALLA L','G5458FE8456','ACCESORIOS','PET SHOP','unidad',1.00,45000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(326,'VESTIDO DE NAVIDAD TALLA S','G54587EED487S','ACCESORIOS','PET SHOP','unidad',1.00,35000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(327,'VESTIDO UNICORNIO TALLA GRANDE','O546GT663632','ROPA','PET SHOP','unidad',1.00,36000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(328,'VET CARE FORMULA NATURAL 1.5 KG GATOS RENAL','AWFWFAA2','CONCENTRADO','PET SHOP','unidad',1.00,128000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(329,'VET CARE PATE RENAL PERRO 270GR','UYF3UYTG3','COMIDA HUMEDA','PET SHOP','unidad',1.00,27000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(330,'VET CARE PATE URINARIO GATOS 100GR','GWES4','COMIDA HUMEDA','PET SHOP','unidad',1.00,18000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(331,'VET CARE RENAL FORMULA NATURAL 2KG PERROS','ADW73V3T3T','CONCENTRADO','PET SHOP','unidad',1.00,118000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(332,'VISIVET GOTAS 10 ML','295HJS5654','ANTIBIOTICO','PRODUCTOS','unidad',1.00,22000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(333,'VITAMINA BIOVIT 9 120ML','1591080016200','120 ML','PRODUCTOS','unidad',1.00,18000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(334,'VITAMINA EDO BONAPEL 120ML','1382160032400','120 ML','PRODUCTOS','unidad',1.00,38000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(335,'VITAMINA OL-TRANS EN POLVO 80 GR','LKFBHY962837P','VITAMINA','PRODUCTOS','unidad',1.00,86000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(336,'VITAMINAS + HIERRO LAB QUALIVET','D45FS56452F','VITAMINA','PRODUCTOS','unidad',1.00,2500.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(337,'ZAPATO FLIPS PERROS TALLA S','Z5785B4N6416','ACCESORIOS','PET SHOP','unidad',1.00,10000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(338,'ZERIVEC 10KG','AEGAEGAEG','AEGAEGAEG','PRODUCTOS','unidad',1.00,3000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(339,'CAMISA DE NACIONAL TALLA M','PS-057','ROPA','PET SHOP','unidad',1.00,20000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(340,'CAMISETA TALLA PEQUEÑA SPORT','65FGTW54936','ROPA','PET SHOP','unidad',1.00,30000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(341,'BUZO DE NAVIDAD TALLA XS','87FGTW54936','ROPA','PET SHOP','unidad',1.00,40000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(342,'COLLARES VARIADOS ECONOMICOS','85FGTW54936','COLLAR','PET SHOP','unidad',1.00,6000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1),(343,'PECHERA NACIONAL FRENCH ECO','14FGTW54936','PECHERA','PET SHOP','unidad',1.00,10000.00,0,5,1,'2026-10-11 17:07:10','2026-10-11 17:07:10',1);
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proveedores`
--

DROP TABLE IF EXISTS `proveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `proveedores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `direccion` varchar(250) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `veterinaria_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_proveedores_vet` (`veterinaria_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proveedores`
--

LOCK TABLES `proveedores` WRITE;
/*!40000 ALTER TABLE `proveedores` DISABLE KEYS */;
INSERT INTO `proveedores` VALUES (1,'jorge','','','','','',1,'2026-06-03 04:28:53','2026-06-03 04:28:53',1);
/*!40000 ALTER TABLE `proveedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tratamiento_abonos`
--

DROP TABLE IF EXISTS `tratamiento_abonos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tratamiento_abonos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tratamiento_id` int(11) NOT NULL,
  `veterinaria_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `nota` varchar(150) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_abonos_tratamiento` (`tratamiento_id`),
  KEY `idx_abonos_vet_fecha` (`veterinaria_id`,`fecha`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tratamiento_abonos`
--

LOCK TABLES `tratamiento_abonos` WRITE;
/*!40000 ALTER TABLE `tratamiento_abonos` DISABLE KEYS */;
INSERT INTO `tratamiento_abonos` VALUES (1,1,2,'2026-10-09',10000.00,'PRIMERA VEZ',1,1,'2026-10-09 22:40:55');
/*!40000 ALTER TABLE `tratamiento_abonos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tratamientos`
--

DROP TABLE IF EXISTS `tratamientos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tratamientos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `veterinaria_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `especie` enum('perro','gato') NOT NULL DEFAULT 'perro',
  `descripcion` varchar(255) NOT NULL,
  `valor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` enum('activo','cancelado') NOT NULL DEFAULT 'activo',
  `motivo_cancelacion` varchar(200) DEFAULT NULL,
  `fecha_cancelacion` date DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tratamientos_vet_fecha` (`veterinaria_id`,`fecha`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tratamientos`
--

LOCK TABLES `tratamientos` WRITE;
/*!40000 ALTER TABLE `tratamientos` DISABLE KEYS */;
INSERT INTO `tratamientos` VALUES (1,2,'2026-10-09','FIFI','perro','MIASIS',50000.00,'cancelado',NULL,'2026-10-09',1,1,'2026-10-09 22:39:53','2026-10-09 23:04:29');
/*!40000 ALTER TABLE `tratamientos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','veterinario','recepcion','vendedor') NOT NULL DEFAULT 'recepcion',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `eliminado` tinyint(1) NOT NULL DEFAULT 0,
  `eliminado_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ultima_veterinaria` int(11) DEFAULT NULL,
  `sucursal_id` int(11) DEFAULT NULL,
  `sucursales` text DEFAULT NULL,
  `tema` varchar(30) NOT NULL DEFAULT 'original',
  `permisos` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Administrador','admin@petcontrol.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin',1,0,NULL,'2026-06-02 05:13:21','2026-10-11 16:57:02',1,NULL,NULL,'original',NULL),(2,'Dra. García','eliminado-2-veterinario@petcontrol.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','veterinario',0,1,'2026-10-11 18:25:37','2026-06-02 05:13:21','2026-10-11 18:25:37',NULL,NULL,NULL,'original',NULL),(3,'Recepcionista','eliminado-3-recepcion@petcontrol.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','recepcion',0,1,'2026-10-11 18:25:39','2026-06-02 05:13:21','2026-10-11 18:25:39',NULL,NULL,NULL,'original',NULL);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `venta_comprobantes`
--

DROP TABLE IF EXISTS `venta_comprobantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `venta_comprobantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venta_id` int(11) NOT NULL,
  `veterinaria_id` int(11) NOT NULL,
  `archivo` varchar(255) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `venta_id` (`venta_id`),
  CONSTRAINT `venta_comprobantes_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venta_comprobantes`
--

LOCK TABLES `venta_comprobantes` WRITE;
/*!40000 ALTER TABLE `venta_comprobantes` DISABLE KEYS */;
/*!40000 ALTER TABLE `venta_comprobantes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `venta_detalles`
--

DROP TABLE IF EXISTS `venta_detalles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `venta_detalles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venta_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL DEFAULT 0.00,
  `descuento` decimal(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(10,2) GENERATED ALWAYS AS (`cantidad` * `precio_unitario` - `descuento`) STORED,
  PRIMARY KEY (`id`),
  KEY `venta_id` (`venta_id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `venta_detalles_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `venta_detalles_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venta_detalles`
--

LOCK TABLES `venta_detalles` WRITE;
/*!40000 ALTER TABLE `venta_detalles` DISABLE KEYS */;
INSERT INTO `venta_detalles` VALUES (4,4,9,1,48000.00,0.00,48000.00),(5,5,9,1,48000.00,0.00,48000.00);
/*!40000 ALTER TABLE `venta_detalles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ventas`
--

DROP TABLE IF EXISTS `ventas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ventas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `veterinaria_id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `descuento` decimal(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `metodo_pago` varchar(20) DEFAULT NULL,
  `pago_detalle` varchar(255) DEFAULT NULL,
  `estado` enum('pendiente','completada','anulada') NOT NULL DEFAULT 'completada',
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `transf_verificada_por` int(11) DEFAULT NULL,
  `transf_verificada_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `veterinaria_id` (`veterinaria_id`),
  KEY `cliente_id` (`cliente_id`),
  CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`veterinaria_id`) REFERENCES `veterinarias` (`id`),
  CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ventas`
--

LOCK TABLES `ventas` WRITE;
/*!40000 ALTER TABLE `ventas` DISABLE KEYS */;
INSERT INTO `ventas` VALUES (4,1,NULL,'',0.00,48000.00,48000.00,NULL,NULL,'anulada',1,'2026-10-11 18:34:13',NULL,NULL),(5,1,NULL,'',0.00,48000.00,48000.00,NULL,NULL,'anulada',1,'2026-10-11 18:35:22',NULL,NULL);
/*!40000 ALTER TABLE `ventas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `veterinarias`
--

DROP TABLE IF EXISTS `veterinarias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `veterinarias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `ruc` varchar(30) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `direccion` varchar(250) DEFAULT NULL,
  `horario` varchar(150) DEFAULT NULL,
  `sitio_web` varchar(200) DEFAULT NULL,
  `logo` varchar(200) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `pct_peluqueria` decimal(5,2) NOT NULL DEFAULT 50.00,
  `descuentos_habilitados` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `veterinarias`
--

LOCK TABLES `veterinarias` WRITE;
/*!40000 ALTER TABLE `veterinarias` DISABLE KEYS */;
INSERT INTO `veterinarias` VALUES (1,'VETERINARIA JUNIORISTA','20123456789','555-0100','central@petcontrol.com','Av. Principal 100','Lun–Vie 8:00–18:00','',NULL,1,'2026-06-02 05:46:12','2026-10-11 19:05:36',50.00,1),(2,'VETERINARIA SAN LUIS','AEVAEG3','32G23G23G','jorge.albeiro@hotmail.com','ABFSBSRB','Lun–Vie 8:00–18:00','',NULL,0,'2026-06-02 05:50:46','2026-10-11 16:57:10',50.00,1),(3,'VETERINARIA CAT AND DOGS','','','','','','',NULL,0,'2026-06-02 05:51:26','2026-10-11 16:58:44',50.00,1);
/*!40000 ALTER TABLE `veterinarias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'petcontrol'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-11 16:40:29
