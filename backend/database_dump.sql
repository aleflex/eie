-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: localhost    Database: eie_db
-- ------------------------------------------------------
-- Server version	8.0.46

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
-- Table structure for table `armas`
--

DROP TABLE IF EXISTS `armas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `armas` (
  `id_arma` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_arma` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_arma`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `armas`
--

LOCK TABLES `armas` WRITE;
/*!40000 ALTER TABLE `armas` DISABLE KEYS */;
INSERT INTO `armas` VALUES (1,'Infantería','2026-08-01 23:30:22','2026-08-01 23:30:22',NULL),(2,'Caballería','2026-08-01 23:30:22','2026-08-01 23:30:22',NULL),(3,'Artallería','2026-08-01 23:30:22','2026-08-01 23:30:22',NULL),(4,'Ingeniería','2026-08-01 23:30:22','2026-08-01 23:30:22',NULL),(5,'Comunicaciones','2026-08-01 23:30:22','2026-08-01 23:30:22',NULL),(6,'Blindados','2026-08-01 23:30:22','2026-08-01 23:30:22',NULL),(7,'Servicios','2026-08-01 23:30:22','2026-08-01 23:30:22',NULL);
/*!40000 ALTER TABLE `armas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asistencias`
--

DROP TABLE IF EXISTS `asistencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asistencias` (
  `id_asistencia` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_inscripcion` bigint unsigned NOT NULL,
  `fecha` date NOT NULL,
  `estado` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `observacion` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_asistencia`),
  UNIQUE KEY `asistencias_id_inscripcion_fecha_unique` (`id_inscripcion`,`fecha`),
  CONSTRAINT `asistencias_id_inscripcion_foreign` FOREIGN KEY (`id_inscripcion`) REFERENCES `inscripciones` (`id_inscripcion`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asistencias`
--

LOCK TABLES `asistencias` WRITE;
/*!40000 ALTER TABLE `asistencias` DISABLE KEYS */;
/*!40000 ALTER TABLE `asistencias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auditorias`
--

DROP TABLE IF EXISTS `auditorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auditorias` (
  `id_auditoria` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tabla` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `registro_id` bigint unsigned NOT NULL,
  `accion` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario_id` bigint unsigned DEFAULT NULL,
  `datos_anteriores` json DEFAULT NULL,
  `datos_nuevos` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_auditoria`),
  KEY `auditorias_tabla_registro_id_index` (`tabla`,`registro_id`),
  KEY `auditorias_usuario_id_index` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auditorias`
--

LOCK TABLES `auditorias` WRITE;
/*!40000 ALTER TABLE `auditorias` DISABLE KEYS */;
/*!40000 ALTER TABLE `auditorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `aulas`
--

DROP TABLE IF EXISTS `aulas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aulas` (
  `id_aula` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_aula` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `capacidad` int DEFAULT NULL,
  `estado` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Activo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_aula`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `aulas`
--

LOCK TABLES `aulas` WRITE;
/*!40000 ALTER TABLE `aulas` DISABLE KEYS */;
INSERT INTO `aulas` VALUES (1,'Aula 101 - Bloque A',30,'Activo',NULL,NULL,NULL),(2,'Aula 102 - Bloque A',30,'Activo',NULL,NULL,NULL),(3,'Aula 201 - Bloque B',35,'Activo',NULL,NULL,NULL),(4,'Laboratorio de Idiomas 1',25,'Activo',NULL,NULL,NULL),(5,'Aula Virtual Zoom 1',100,'Activo',NULL,NULL,NULL);
/*!40000 ALTER TABLE `aulas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
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
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
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
-- Table structure for table `configuraciones`
--

DROP TABLE IF EXISTS `configuraciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `configuraciones` (
  `id_configuracion` bigint unsigned NOT NULL AUTO_INCREMENT,
  `clave` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` text COLLATE utf8mb4_unicode_ci,
  `tipo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `grupo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_configuracion`),
  UNIQUE KEY `configuraciones_clave_unique` (`clave`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuraciones`
--

LOCK TABLES `configuraciones` WRITE;
/*!40000 ALTER TABLE `configuraciones` DISABLE KEYS */;
INSERT INTO `configuraciones` VALUES (1,'fecha_inicio_inscripcion','2026-08-01T08:00','string','academic','2026-08-01 23:30:25','2026-08-01 23:30:25'),(2,'fecha_fin_inscripcion','2026-11-01T18:00','string','academic','2026-08-01 23:30:25','2026-08-01 23:30:25'),(3,'limite_pdf_mb','5','int','files','2026-08-01 23:30:25','2026-08-01 23:30:25'),(4,'comprimir_imagenes','1','bool','files','2026-08-01 23:30:25','2026-08-01 23:30:25'),(5,'nombre_institucion','Escuela de Idiomas del Ejército','string','institution','2026-08-01 23:30:25','2026-08-01 23:30:25'),(6,'nombre_director','Cnl. DAEN Juan Pérez López','string','institution','2026-08-01 23:30:25','2026-08-01 23:30:25'),(7,'grado_director','Coronel DAEN','string','institution','2026-08-01 23:30:25','2026-08-01 23:30:25'),(8,'cupo_defecto_paralelo','25','int','general','2026-08-01 23:30:25','2026-08-01 23:30:25');
/*!40000 ALTER TABLE `configuraciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contactos_emergencia`
--

DROP TABLE IF EXISTS `contactos_emergencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contactos_emergencia` (
  `id_contacto_emergencia` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_estudiante` bigint unsigned NOT NULL,
  `nombre_contacto` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ci` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `relacion` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `es_principal` tinyint(1) NOT NULL DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_contacto_emergencia`),
  UNIQUE KEY `contactos_emergencia_id_estudiante_es_principal_unique` (`id_estudiante`,`es_principal`),
  KEY `contactos_emergencia_id_estudiante_index` (`id_estudiante`),
  CONSTRAINT `contactos_emergencia_id_estudiante_foreign` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contactos_emergencia`
--

LOCK TABLES `contactos_emergencia` WRITE;
/*!40000 ALTER TABLE `contactos_emergencia` DISABLE KEYS */;
INSERT INTO `contactos_emergencia` VALUES (1,1,'Sgt. Mario Mamani Perez y Sra. Rosa Claros',NULL,'Padre/Madre/Tutor','71239081',NULL,NULL,1,NULL,NULL,NULL),(2,2,'Sr. Carlos Vargas y Sra. Elena Rios de Vargas',NULL,'Padre/Madre/Tutor','76510928',NULL,NULL,1,NULL,NULL,NULL),(3,3,'My. Eduardo Siles P. y Sra. Maria Torrez',NULL,'Padre/Madre/Tutor','79812340',NULL,NULL,1,NULL,NULL,NULL),(4,4,'Gral. Rodrigo Alarcon V. y Sra. Carmen Peñaranda',NULL,'Padre/Madre/Tutor','71209845',NULL,NULL,1,NULL,NULL,NULL),(5,5,'Cnl. DAEN Fernando Gutierrez y Sra. Sofia Morales',NULL,'Padre/Madre/Tutor','76590124',NULL,NULL,1,NULL,NULL,NULL);
/*!40000 ALTER TABLE `contactos_emergencia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cursos`
--

DROP TABLE IF EXISTS `cursos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cursos` (
  `id_curso` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_idioma` bigint unsigned NOT NULL,
  `id_nivel` bigint unsigned NOT NULL,
  `id_modalidad` bigint unsigned NOT NULL,
  `cupo_minimo` int NOT NULL DEFAULT '0',
  `cupo_maximo` int NOT NULL DEFAULT '30',
  `estado` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Activo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_curso`),
  KEY `cursos_id_idioma_foreign` (`id_idioma`),
  KEY `cursos_id_nivel_foreign` (`id_nivel`),
  KEY `cursos_id_modalidad_foreign` (`id_modalidad`),
  CONSTRAINT `cursos_id_idioma_foreign` FOREIGN KEY (`id_idioma`) REFERENCES `idiomas` (`id_idioma`) ON DELETE CASCADE,
  CONSTRAINT `cursos_id_modalidad_foreign` FOREIGN KEY (`id_modalidad`) REFERENCES `modalidades` (`id_modalidad`) ON DELETE CASCADE,
  CONSTRAINT `cursos_id_nivel_foreign` FOREIGN KEY (`id_nivel`) REFERENCES `niveles` (`id_nivel`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cursos`
--

LOCK TABLES `cursos` WRITE;
/*!40000 ALTER TABLE `cursos` DISABLE KEYS */;
INSERT INTO `cursos` VALUES (1,1,1,1,5,30,'Activo',NULL,NULL,NULL),(2,2,1,2,5,25,'Activo',NULL,NULL,NULL);
/*!40000 ALTER TABLE `cursos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `docente_paralelo`
--

DROP TABLE IF EXISTS `docente_paralelo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `docente_paralelo` (
  `id_docente_paralelo` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_docente` bigint unsigned NOT NULL,
  `id_paralelo` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_docente_paralelo`),
  KEY `docente_paralelo_id_docente_foreign` (`id_docente`),
  KEY `docente_paralelo_id_paralelo_foreign` (`id_paralelo`),
  CONSTRAINT `docente_paralelo_id_docente_foreign` FOREIGN KEY (`id_docente`) REFERENCES `docentes` (`id_docente`) ON DELETE CASCADE,
  CONSTRAINT `docente_paralelo_id_paralelo_foreign` FOREIGN KEY (`id_paralelo`) REFERENCES `paralelos` (`id_paralelo`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `docente_paralelo`
--

LOCK TABLES `docente_paralelo` WRITE;
/*!40000 ALTER TABLE `docente_paralelo` DISABLE KEYS */;
INSERT INTO `docente_paralelo` VALUES (1,1,1,'2026-08-01 23:30:25','2026-08-01 23:30:25'),(2,2,2,'2026-08-01 23:30:25','2026-08-01 23:30:25'),(3,1,1,'2026-08-01 23:30:45','2026-08-01 23:30:45'),(4,2,2,'2026-08-01 23:30:45','2026-08-01 23:30:45');
/*!40000 ALTER TABLE `docente_paralelo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `docentes`
--

DROP TABLE IF EXISTS `docentes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `docentes` (
  `id_docente` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` bigint unsigned NOT NULL,
  `especialidad` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_tipo_contrato` bigint unsigned DEFAULT NULL,
  `telefono` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Activo',
  `fecha_contrato` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_docente`),
  KEY `docentes_id_usuario_foreign` (`id_usuario`),
  KEY `docentes_id_tipo_contrato_foreign` (`id_tipo_contrato`),
  CONSTRAINT `docentes_id_tipo_contrato_foreign` FOREIGN KEY (`id_tipo_contrato`) REFERENCES `tipos_contrato_docente` (`id_tipo_contrato`) ON DELETE SET NULL,
  CONSTRAINT `docentes_id_usuario_foreign` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `docentes`
--

LOCK TABLES `docentes` WRITE;
/*!40000 ALTER TABLE `docentes` DISABLE KEYS */;
INSERT INTO `docentes` VALUES (1,4,'Lingüística e Inglés Técnico',2,'71728394','Activo','2025-01-15',NULL,NULL,NULL),(2,5,'Lengua y Cultura Francesa',1,'72839405','Activo','2026-02-01',NULL,NULL,NULL);
/*!40000 ALTER TABLE `docentes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documentos`
--

DROP TABLE IF EXISTS `documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documentos` (
  `id_documento` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_estudiante` bigint unsigned NOT NULL,
  `tipo_documento` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_archivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ruta_archivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_documento`),
  KEY `documentos_id_estudiante_foreign` (`id_estudiante`),
  CONSTRAINT `documentos_id_estudiante_foreign` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documentos`
--

LOCK TABLES `documentos` WRITE;
/*!40000 ALTER TABLE `documentos` DISABLE KEYS */;
INSERT INTO `documentos` VALUES (1,1,'Cédula de Identidad','ci_juan_mamani.pdf','/storage/documentos/estudiantes/1/ci_juan_mamani.pdf','2026-08-01 23:30:25','2026-08-01 23:30:25'),(2,1,'Certificado de Nacimiento','certificado_nacimiento_juan.pdf','/storage/documentos/estudiantes/1/certificado_nacimiento_juan.pdf','2026-08-01 23:30:25','2026-08-01 23:30:25'),(3,1,'Carnet Militar','carnet_militar_juan.pdf','/storage/documentos/estudiantes/1/carnet_militar_juan.pdf','2026-08-01 23:30:25','2026-08-01 23:30:25'),(4,1,'Comprobante de Depósito','comprobante_deposito_juan.pdf','/storage/documentos/estudiantes/1/comprobante_deposito_juan.pdf','2026-08-01 23:30:25','2026-08-01 23:30:25'),(5,2,'Cédula de Identidad','ci_ana_vargas.pdf','/storage/documentos/estudiantes/2/ci_ana_vargas.pdf','2026-08-01 23:30:25','2026-08-01 23:30:25'),(6,2,'Título de Bachiller','titulo_bachiller_ana.pdf','/storage/documentos/estudiantes/2/titulo_bachiller_ana.pdf','2026-08-01 23:30:25','2026-08-01 23:30:25'),(7,2,'Comprobante de Depósito','deposito_bancario_ana.pdf','/storage/documentos/estudiantes/2/deposito_bancario_ana.pdf','2026-08-01 23:30:25','2026-08-01 23:30:25'),(8,3,'Cédula de Identidad','ci_carlos_siles.pdf','/storage/documentos/estudiantes/3/ci_carlos_siles.pdf','2026-08-01 23:30:25','2026-08-01 23:30:25'),(9,3,'Carnet Militar','carnet_militar_carlos.pdf','/storage/documentos/estudiantes/3/carnet_militar_carlos.pdf','2026-08-01 23:30:25','2026-08-01 23:30:25');
/*!40000 ALTER TABLE `documentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estados_civil`
--

DROP TABLE IF EXISTS `estados_civil`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `estados_civil` (
  `id_estado_civil` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_estado_civil` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_estado_civil`),
  UNIQUE KEY `estados_civil_nombre_estado_civil_unique` (`nombre_estado_civil`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estados_civil`
--

LOCK TABLES `estados_civil` WRITE;
/*!40000 ALTER TABLE `estados_civil` DISABLE KEYS */;
INSERT INTO `estados_civil` VALUES (1,'Soltero',NULL,'2026-08-01 23:30:18','2026-08-01 23:30:18'),(2,'Casado',NULL,'2026-08-01 23:30:18','2026-08-01 23:30:18'),(3,'Divorciado',NULL,'2026-08-01 23:30:18','2026-08-01 23:30:18'),(4,'Viudo',NULL,'2026-08-01 23:30:18','2026-08-01 23:30:18'),(5,'Unión Libre',NULL,'2026-08-01 23:30:18','2026-08-01 23:30:18'),(6,'Separado',NULL,'2026-08-01 23:30:18','2026-08-01 23:30:18');
/*!40000 ALTER TABLE `estados_civil` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estudiante_responsable`
--

DROP TABLE IF EXISTS `estudiante_responsable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `estudiante_responsable` (
  `id_estudiante_responsable` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_estudiante` bigint unsigned NOT NULL,
  `id_responsable` bigint unsigned NOT NULL,
  `parentesco` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_estudiante_responsable`),
  UNIQUE KEY `estudiante_responsable_id_estudiante_id_responsable_unique` (`id_estudiante`,`id_responsable`),
  KEY `estudiante_responsable_id_responsable_foreign` (`id_responsable`),
  CONSTRAINT `estudiante_responsable_id_estudiante_foreign` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON DELETE CASCADE,
  CONSTRAINT `estudiante_responsable_id_responsable_foreign` FOREIGN KEY (`id_responsable`) REFERENCES `responsables` (`id_responsable`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estudiante_responsable`
--

LOCK TABLES `estudiante_responsable` WRITE;
/*!40000 ALTER TABLE `estudiante_responsable` DISABLE KEYS */;
INSERT INTO `estudiante_responsable` VALUES (1,1,1,'Padre/Madre/Tutor',NULL,NULL),(2,2,2,'Padre/Madre/Tutor',NULL,NULL),(3,3,3,'Padre/Madre/Tutor',NULL,NULL),(4,4,4,'Padre/Madre/Tutor',NULL,NULL),(5,5,5,'Padre/Madre/Tutor',NULL,NULL);
/*!40000 ALTER TABLE `estudiante_responsable` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estudiantes`
--

DROP TABLE IF EXISTS `estudiantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `estudiantes` (
  `id_estudiante` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` bigint unsigned NOT NULL,
  `id_grado` bigint unsigned DEFAULT NULL,
  `id_arma` bigint unsigned DEFAULT NULL,
  `id_estado_civil` bigint unsigned DEFAULT NULL,
  `id_grupo_sanguineo` bigint unsigned DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `lugar_nacimiento` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `carnet_militar` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `carnet_cossmil` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `celular` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anio_egreso_bachiller` smallint DEFAULT NULL,
  `domicilio` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_4x4_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hermanos_inscritos` int NOT NULL DEFAULT '0',
  `tipo_usuario` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `estado` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Activo',
  `documentos_habilitados_hasta` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_estudiante`),
  UNIQUE KEY `estudiantes_nueva_carnet_militar_unique` (`carnet_militar`),
  UNIQUE KEY `estudiantes_nueva_carnet_cossmil_unique` (`carnet_cossmil`),
  KEY `estudiantes_nueva_id_grado_foreign` (`id_grado`),
  KEY `estudiantes_nueva_id_arma_foreign` (`id_arma`),
  KEY `estudiantes_nueva_id_estado_civil_foreign` (`id_estado_civil`),
  KEY `estudiantes_nueva_id_grupo_sanguineo_foreign` (`id_grupo_sanguineo`),
  KEY `estudiantes_nueva_id_usuario_index` (`id_usuario`),
  KEY `estudiantes_nueva_estado_index` (`estado`),
  KEY `estudiantes_nueva_carnet_militar_index` (`carnet_militar`),
  KEY `estudiantes_nueva_tipo_usuario_index` (`tipo_usuario`),
  CONSTRAINT `estudiantes_nueva_id_arma_foreign` FOREIGN KEY (`id_arma`) REFERENCES `armas` (`id_arma`) ON DELETE SET NULL,
  CONSTRAINT `estudiantes_nueva_id_estado_civil_foreign` FOREIGN KEY (`id_estado_civil`) REFERENCES `estados_civil` (`id_estado_civil`) ON DELETE SET NULL,
  CONSTRAINT `estudiantes_nueva_id_grado_foreign` FOREIGN KEY (`id_grado`) REFERENCES `grados` (`id_grado`) ON DELETE SET NULL,
  CONSTRAINT `estudiantes_nueva_id_grupo_sanguineo_foreign` FOREIGN KEY (`id_grupo_sanguineo`) REFERENCES `grupos_sanguineo` (`id_grupo_sanguineo`) ON DELETE SET NULL,
  CONSTRAINT `estudiantes_nueva_id_usuario_foreign` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estudiantes`
--

LOCK TABLES `estudiantes` WRITE;
/*!40000 ALTER TABLE `estudiantes` DISABLE KEYS */;
INSERT INTO `estudiantes` VALUES (1,6,1,1,1,1,'1998-05-14','Cochabamba','CM-849201','CS-48921','71234567',2016,'Av. Ejército Nro. 450, Zona Muyurina, Cochabamba',NULL,1,'militar','Activo','2027-12-31 23:59:59',NULL,NULL,NULL),(2,7,8,NULL,1,3,'2001-09-22','La Paz',NULL,NULL,'76543210',2019,'Calle España Nro. 120, Zona Central, Cochabamba',NULL,0,'normal','Activo','2027-12-31 23:59:59',NULL,NULL,NULL),(3,8,2,2,2,1,'1995-11-03','Santa Cruz','CM-901248','CS-51029','79812345',2013,'Av. Heroínas Nro. 890, Cochabamba',NULL,0,'militar','Activo','2027-12-31 23:59:59',NULL,NULL,NULL),(4,9,1,4,1,1,'1999-03-18','La Paz','CM-994012','CS-88123','71209845',2017,'Av. Irpavi Nro. 300, La Paz',NULL,1,'emi','Activo','2027-12-31 23:59:59',NULL,NULL,NULL),(5,10,8,NULL,1,1,'2004-12-10','Cochabamba',NULL,'CS-99120','76590124',2022,'Av. Ballivián Nro. 500, Cochabamba',NULL,2,'hijo_militar','Activo','2027-12-31 23:59:59',NULL,NULL,NULL),(6,11,9,2,1,1,'1990-05-15','La Paz','CM-887766',NULL,'71234567',2008,'Av. Arce 1234',NULL,0,'militar','Activo',NULL,NULL,NULL,NULL),(7,12,NULL,NULL,1,1,'2001-10-20','Cochabamba',NULL,NULL,'60555444',2019,'Calle Jordan 456',NULL,0,'emi','Activo',NULL,NULL,NULL,NULL),(8,13,NULL,NULL,1,1,'2004-03-12','Santa Cruz',NULL,'COS-112233','79998888',2021,'Av. Beni 789',NULL,0,'hijo_militar','Activo',NULL,NULL,NULL,NULL),(9,14,NULL,NULL,1,1,'1999-07-30','Oruro',NULL,NULL,'68887777',2017,'Calle Junin 101',NULL,0,'normal','Activo',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `estudiantes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
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
-- Table structure for table `grados`
--

DROP TABLE IF EXISTS `grados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grados` (
  `id_grado` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_grado` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_grado`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grados`
--

LOCK TABLES `grados` WRITE;
/*!40000 ALTER TABLE `grados` DISABLE KEYS */;
INSERT INTO `grados` VALUES (1,'Subteniente','2026-08-01 23:30:22','2026-08-01 23:30:22',NULL),(2,'Teniente','2026-08-01 23:30:22','2026-08-01 23:30:22',NULL),(3,'Capitán','2026-08-01 23:30:22','2026-08-01 23:30:22',NULL),(4,'Mayor','2026-08-01 23:30:22','2026-08-01 23:30:22',NULL),(5,'Teniente Coronel','2026-08-01 23:30:22','2026-08-01 23:30:22',NULL),(6,'Coronel','2026-08-01 23:30:22','2026-08-01 23:30:22',NULL),(7,'Premilitar','2026-08-01 23:30:22','2026-08-01 23:30:22',NULL),(8,'Civil','2026-08-01 23:30:22','2026-08-01 23:30:22',NULL),(9,'Sargento','2026-08-01 23:31:00','2026-08-01 23:31:00',NULL);
/*!40000 ALTER TABLE `grados` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grupos_sanguineo`
--

DROP TABLE IF EXISTS `grupos_sanguineo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grupos_sanguineo` (
  `id_grupo_sanguineo` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_grupo_sanguineo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_grupo_sanguineo`),
  UNIQUE KEY `grupos_sanguineo_nombre_grupo_sanguineo_unique` (`nombre_grupo_sanguineo`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grupos_sanguineo`
--

LOCK TABLES `grupos_sanguineo` WRITE;
/*!40000 ALTER TABLE `grupos_sanguineo` DISABLE KEYS */;
INSERT INTO `grupos_sanguineo` VALUES (1,'O+',NULL,'2026-08-01 23:30:18','2026-08-01 23:30:18'),(2,'O-',NULL,'2026-08-01 23:30:18','2026-08-01 23:30:18'),(3,'A+',NULL,'2026-08-01 23:30:18','2026-08-01 23:30:18'),(4,'A-',NULL,'2026-08-01 23:30:18','2026-08-01 23:30:18'),(5,'B+',NULL,'2026-08-01 23:30:18','2026-08-01 23:30:18'),(6,'B-',NULL,'2026-08-01 23:30:18','2026-08-01 23:30:18'),(7,'AB+',NULL,'2026-08-01 23:30:18','2026-08-01 23:30:18'),(8,'AB-',NULL,'2026-08-01 23:30:18','2026-08-01 23:30:18');
/*!40000 ALTER TABLE `grupos_sanguineo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `horario_paralelo`
--

DROP TABLE IF EXISTS `horario_paralelo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `horario_paralelo` (
  `id_horario_paralelo` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_paralelo` bigint unsigned NOT NULL,
  `id_horario` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_horario_paralelo`),
  KEY `horario_paralelo_id_paralelo_foreign` (`id_paralelo`),
  KEY `horario_paralelo_id_horario_foreign` (`id_horario`),
  CONSTRAINT `horario_paralelo_id_horario_foreign` FOREIGN KEY (`id_horario`) REFERENCES `horarios` (`id_horario`) ON DELETE CASCADE,
  CONSTRAINT `horario_paralelo_id_paralelo_foreign` FOREIGN KEY (`id_paralelo`) REFERENCES `paralelos` (`id_paralelo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `horario_paralelo`
--

LOCK TABLES `horario_paralelo` WRITE;
/*!40000 ALTER TABLE `horario_paralelo` DISABLE KEYS */;
/*!40000 ALTER TABLE `horario_paralelo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `horarios`
--

DROP TABLE IF EXISTS `horarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `horarios` (
  `id_horario` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dia_semana` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `estado` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Activo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_horario`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `horarios`
--

LOCK TABLES `horarios` WRITE;
/*!40000 ALTER TABLE `horarios` DISABLE KEYS */;
INSERT INTO `horarios` VALUES (1,'Lunes a Viernes','08:00:00','10:00:00','Activo',NULL,NULL,NULL),(2,'Lunes a Viernes','14:30:00','16:30:00','Activo',NULL,NULL,NULL),(3,'Lunes a Viernes','19:00:00','21:00:00','Activo',NULL,NULL,NULL),(4,'Sábados','08:30:00','12:30:00','Activo',NULL,NULL,NULL);
/*!40000 ALTER TABLE `horarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `idiomas`
--

DROP TABLE IF EXISTS `idiomas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `idiomas` (
  `id_idioma` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_idioma` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_idioma`),
  UNIQUE KEY `idiomas_nombre_idioma_unique` (`nombre_idioma`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `idiomas`
--

LOCK TABLES `idiomas` WRITE;
/*!40000 ALTER TABLE `idiomas` DISABLE KEYS */;
INSERT INTO `idiomas` VALUES (1,'Inglés',NULL,NULL,NULL),(2,'Francés',NULL,NULL,NULL),(3,'Chino Mandarín',NULL,NULL,NULL),(4,'Alemán',NULL,NULL,NULL),(5,'Quechua',NULL,NULL,NULL),(6,'Aymara',NULL,NULL,NULL);
/*!40000 ALTER TABLE `idiomas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inscripciones`
--

DROP TABLE IF EXISTS `inscripciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inscripciones` (
  `id_inscripcion` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_estudiante` bigint unsigned NOT NULL,
  `id_curso` bigint unsigned NOT NULL,
  `id_paralelo` bigint unsigned DEFAULT NULL,
  `fecha_registro` date DEFAULT NULL,
  `estado` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Activo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_inscripcion`),
  KEY `inscripciones_id_paralelo_foreign` (`id_paralelo`),
  KEY `inscripciones_id_estudiante_index` (`id_estudiante`),
  KEY `inscripciones_id_curso_index` (`id_curso`),
  KEY `inscripciones_estado_index` (`estado`),
  CONSTRAINT `inscripciones_id_curso_foreign` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE,
  CONSTRAINT `inscripciones_id_estudiante_foreign` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON DELETE CASCADE,
  CONSTRAINT `inscripciones_id_paralelo_foreign` FOREIGN KEY (`id_paralelo`) REFERENCES `paralelos` (`id_paralelo`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inscripciones`
--

LOCK TABLES `inscripciones` WRITE;
/*!40000 ALTER TABLE `inscripciones` DISABLE KEYS */;
INSERT INTO `inscripciones` VALUES (1,1,1,1,'2026-02-10','Activo',NULL,NULL,NULL),(2,2,1,1,'2026-02-11','Activo',NULL,NULL,NULL),(3,3,2,2,'2026-02-12','Activo',NULL,NULL,NULL),(4,6,1,NULL,'2026-08-01','activo',NULL,NULL,NULL),(5,7,1,NULL,'2026-08-01','activo',NULL,NULL,NULL),(6,8,1,NULL,'2026-08-01','activo',NULL,NULL,NULL),(7,9,1,NULL,'2026-08-01','activo',NULL,NULL,NULL);
/*!40000 ALTER TABLE `inscripciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
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
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
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
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_base_system_tables',1),(2,'2026_05_30_000000_create_academic_3nf_tables',1),(3,'2026_06_04_000000_refactor_to_strict_3nf',1),(4,'2026_06_08_000000_rename_generic_nombre_columns',1),(5,'2026_06_29_211828_add_documentos_habilitados_hasta_to_estudiantes_table',1),(6,'2026_07_25_000000_add_usuario_and_debe_cambiar_password_to_usuarios_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modalidades`
--

DROP TABLE IF EXISTS `modalidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `modalidades` (
  `id_modalidad` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_modalidad` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_modalidad`),
  UNIQUE KEY `modalidades_nombre_modalidad_unique` (`nombre_modalidad`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modalidades`
--

LOCK TABLES `modalidades` WRITE;
/*!40000 ALTER TABLE `modalidades` DISABLE KEYS */;
INSERT INTO `modalidades` VALUES (1,'Presencial',NULL,NULL,NULL),(2,'Virtual',NULL,NULL,NULL),(3,'Semipresencial',NULL,NULL,NULL);
/*!40000 ALTER TABLE `modalidades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `niveles`
--

DROP TABLE IF EXISTS `niveles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `niveles` (
  `id_nivel` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_nivel` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_nivel`),
  UNIQUE KEY `niveles_nombre_nivel_unique` (`nombre_nivel`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `niveles`
--

LOCK TABLES `niveles` WRITE;
/*!40000 ALTER TABLE `niveles` DISABLE KEYS */;
INSERT INTO `niveles` VALUES (1,'NIVEL I (BOOK 1-6)',NULL,NULL,NULL),(2,'NIVEL II (BOOK 7-12)',NULL,NULL,NULL),(3,'NIVEL III (BOOK 13-18)',NULL,NULL,NULL),(4,'AVANZADO I',NULL,NULL,NULL),(5,'AVANZADO II',NULL,NULL,NULL);
/*!40000 ALTER TABLE `niveles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notas`
--

DROP TABLE IF EXISTS `notas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notas` (
  `id_nota` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_inscripcion` bigint unsigned NOT NULL,
  `nota` decimal(5,2) DEFAULT NULL,
  `periodo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Parcial 1',
  `observacion` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_nota`),
  KEY `notas_id_inscripcion_index` (`id_inscripcion`),
  KEY `notas_periodo_index` (`periodo`),
  CONSTRAINT `notas_id_inscripcion_foreign` FOREIGN KEY (`id_inscripcion`) REFERENCES `inscripciones` (`id_inscripcion`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notas`
--

LOCK TABLES `notas` WRITE;
/*!40000 ALTER TABLE `notas` DISABLE KEYS */;
/*!40000 ALTER TABLE `notas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paralelos`
--

DROP TABLE IF EXISTS `paralelos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paralelos` (
  `id_paralelo` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_curso` bigint unsigned NOT NULL,
  `id_aula` bigint unsigned DEFAULT NULL,
  `nombre_paralelo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Activo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_paralelo`),
  KEY `paralelos_id_curso_foreign` (`id_curso`),
  KEY `paralelos_id_aula_foreign` (`id_aula`),
  CONSTRAINT `paralelos_id_aula_foreign` FOREIGN KEY (`id_aula`) REFERENCES `aulas` (`id_aula`) ON DELETE SET NULL,
  CONSTRAINT `paralelos_id_curso_foreign` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paralelos`
--

LOCK TABLES `paralelos` WRITE;
/*!40000 ALTER TABLE `paralelos` DISABLE KEYS */;
INSERT INTO `paralelos` VALUES (1,1,1,'Paralelo A','Activo',NULL,NULL,NULL),(2,2,NULL,'Paralelo A-Virtual','Activo',NULL,NULL,NULL);
/*!40000 ALTER TABLE `paralelos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (1,'App\\Models\\User',16,'auth_token','f2f831dbed1fd3a5d6999eaa0203c04d73593353581253beedb9f636fa2665ff','[\"*\"]',NULL,NULL,'2026-08-01 23:45:15','2026-08-01 23:45:15');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `responsables`
--

DROP TABLE IF EXISTS `responsables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `responsables` (
  `id_responsable` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombres_responsable` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido_paterno_responsable` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `apellido_materno_responsable` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ci_responsable` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `celular_responsable` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion_responsable` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_responsable`),
  UNIQUE KEY `responsables_ci_responsable_unique` (`ci_responsable`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `responsables`
--

LOCK TABLES `responsables` WRITE;
/*!40000 ALTER TABLE `responsables` DISABLE KEYS */;
INSERT INTO `responsables` VALUES (1,'Sgt.','Mario','Mamani Perez y Sra. Rosa Claros','3920194 CB','','',NULL,NULL,NULL),(2,'Sr.','Carlos','Vargas y Sra. Elena Rios de Vargas','2981049 LP','','',NULL,NULL,NULL),(3,'My.','Eduardo','Siles P. y Sra. Maria Torrez','3109284 SC','','',NULL,NULL,NULL),(4,'Gral.','Rodrigo','Alarcon V. y Sra. Carmen Peñaranda','2981048 LP','','',NULL,NULL,NULL),(5,'Cnl.','DAEN','Fernando Gutierrez y Sra. Sofia Morales','3412980 CB','','',NULL,NULL,NULL);
/*!40000 ALTER TABLE `responsables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id_rol` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `roles_nombre_rol_unique` (`nombre_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin','Administrador del sistema',NULL,'2026-08-01 23:30:18','2026-08-01 23:30:18'),(2,'estudiante','Estudiante de la institución',NULL,'2026-08-01 23:30:18','2026-08-01 23:30:18'),(3,'docente','Docente/Profesor',NULL,'2026-08-01 23:30:18','2026-08-01 23:30:18'),(4,'directivo','Directivo/Rector',NULL,'2026-08-01 23:30:18','2026-08-01 23:30:18'),(5,'secretaria','Personal administrativo',NULL,'2026-08-01 23:30:18','2026-08-01 23:30:18');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
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
INSERT INTO `sessions` VALUES ('8LgkoYQuIEvrsguXexNNwn9HNiKyLUboL8WRD43B',16,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUXpINDRKa3hlRnhackFsRWkweXF2UGxxSXZ5MVE5Rm4yczNWdE5URyI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTY7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1785627915),('v0sTeRqOrI5B0dxU61kpoS0mqnExpG7VpCYE5KP7',NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoidXBFelZJdUxtYlBkb3dkYWc5WlBLYkJ6S0JWYzUyaTc5OWhQYkZOMiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mzk6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9hcGkvaW5zY3JpcGNpb25lcyI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1785627918),('wocpE4sd3Xd2LJ3NjGpHwk3C7f2PrR3CVFkRCzAO',NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0','YToyOntzOjY6Il90b2tlbiI7czo0MDoicU9MQmlleGRwRW9Calk1U1RyMUo4a3NGeVN1bExnRnhUazhTTVdJWiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1785627270);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipos_contrato_docente`
--

DROP TABLE IF EXISTS `tipos_contrato_docente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_contrato_docente` (
  `id_tipo_contrato` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_tipo_contrato` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_tipo_contrato`),
  UNIQUE KEY `tipos_contrato_docente_nombre_tipo_contrato_unique` (`nombre_tipo_contrato`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipos_contrato_docente`
--

LOCK TABLES `tipos_contrato_docente` WRITE;
/*!40000 ALTER TABLE `tipos_contrato_docente` DISABLE KEYS */;
INSERT INTO `tipos_contrato_docente` VALUES (1,'Contratado','Docente contratado por período',NULL,'2026-08-01 23:30:19','2026-08-01 23:30:19'),(2,'Titular','Docente en plantilla permanente',NULL,'2026-08-01 23:30:19','2026-08-01 23:30:19'),(3,'Interino','Docente interino/temporal',NULL,'2026-08-01 23:30:19','2026-08-01 23:30:19'),(4,'Practicante','Docente en período de prácticas',NULL,'2026-08-01 23:30:19','2026-08-01 23:30:19'),(5,'Becario','Docente becario',NULL,'2026-08-01 23:30:19','2026-08-01 23:30:19');
/*!40000 ALTER TABLE `tipos_contrato_docente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipos_documentos`
--

DROP TABLE IF EXISTS `tipos_documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_documentos` (
  `id_tipo_documento` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_tipo_documento` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_tipo_documento`),
  UNIQUE KEY `tipos_documentos_nombre_tipo_documento_unique` (`nombre_tipo_documento`),
  UNIQUE KEY `tipos_documentos_codigo_unique` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipos_documentos`
--

LOCK TABLES `tipos_documentos` WRITE;
/*!40000 ALTER TABLE `tipos_documentos` DISABLE KEYS */;
INSERT INTO `tipos_documentos` VALUES (1,'Cédula de Identidad','CI',NULL,'2026-08-01 23:30:18','2026-08-01 23:30:18'),(2,'Pasaporte','PASS',NULL,'2026-08-01 23:30:18','2026-08-01 23:30:18'),(3,'Licencia de Conducir','LDER',NULL,'2026-08-01 23:30:18','2026-08-01 23:30:18'),(4,'Carnet de Extranjería','CE',NULL,'2026-08-01 23:30:18','2026-08-01 23:30:18'),(5,'Documento de Identidad','DI',NULL,'2026-08-01 23:30:18','2026-08-01 23:30:18'),(6,'Carnet Militar','CM',NULL,'2026-08-01 23:30:18','2026-08-01 23:30:18'),(7,'Certificado de Nacimiento','CN',NULL,'2026-08-01 23:30:18','2026-08-01 23:30:18');
/*!40000 ALTER TABLE `tipos_documentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id_usuario` bigint unsigned NOT NULL AUTO_INCREMENT,
  `correo_institucional` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `debe_cambiar_password` tinyint(1) NOT NULL DEFAULT '0',
  `nombres` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `apellidos` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ci` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_rol` bigint unsigned NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVO',
  `foto_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verificado_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `usuarios_correo_institucional_unique` (`correo_institucional`),
  UNIQUE KEY `usuarios_ci_unique` (`ci`),
  UNIQUE KEY `usuarios_usuario_unique` (`usuario`),
  KEY `usuarios_correo_institucional_index` (`correo_institucional`),
  KEY `usuarios_ci_index` (`ci`),
  KEY `usuarios_id_rol_index` (`id_rol`),
  CONSTRAINT `usuarios_id_rol_foreign` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'admin@eie.edu.bo',NULL,'$2y$12$hFpj0.bSggzsE1Xgl81S/ecXLE/VRJQXEALnaTsWLeniBVEGwo706',0,'Carlos Mario','Mendoza Claros','4589123 LP',1,'ACTIVO',NULL,NULL,NULL,'2026-08-01 23:30:23','2026-08-01 23:30:23',NULL),(2,'rector@eie.edu.bo',NULL,'$2y$12$hFpj0.bSggzsE1Xgl81S/ecXLE/VRJQXEALnaTsWLeniBVEGwo706',0,'Fernando','Gutierrez DAEN','3412980 CB',4,'ACTIVO',NULL,NULL,NULL,'2026-08-01 23:30:23','2026-08-01 23:30:23',NULL),(3,'secretaria@eie.edu.bo',NULL,'$2y$12$hFpj0.bSggzsE1Xgl81S/ecXLE/VRJQXEALnaTsWLeniBVEGwo706',0,'Maria Elena','Paredes Rojas','5123908 LP',5,'ACTIVO',NULL,NULL,NULL,'2026-08-01 23:30:23','2026-08-01 23:30:23',NULL),(4,'docente.ingles@eie.edu.bo',NULL,'$2y$12$hFpj0.bSggzsE1Xgl81S/ecXLE/VRJQXEALnaTsWLeniBVEGwo706',0,'Roberto','Valenzuela Solares','4892105 CB',3,'ACTIVO',NULL,NULL,NULL,'2026-08-01 23:30:23','2026-08-01 23:30:23',NULL),(5,'docente.frances@eie.edu.bo',NULL,'$2y$12$hFpj0.bSggzsE1Xgl81S/ecXLE/VRJQXEALnaTsWLeniBVEGwo706',0,'Patricia','Morales Villarroel','3981204 LP',3,'ACTIVO',NULL,NULL,NULL,'2026-08-01 23:30:24','2026-08-01 23:30:24',NULL),(6,'estudiante.juan@eie.edu.bo','roberto.valenzuela','$2y$12$u2Zi1xnBDpsWlRjPKTRAI.ubZ61ABmemiLxdw3E/lc3vWIH.XHZdu',0,'Juan Pablo','Mamani Claros','8421093 CB',2,'ACTIVO',NULL,NULL,NULL,'2026-08-01 23:30:24','2026-08-01 23:30:24',NULL),(7,'estudiante.ana@eie.edu.bo',NULL,'$2y$12$hFpj0.bSggzsE1Xgl81S/ecXLE/VRJQXEALnaTsWLeniBVEGwo706',0,'Ana Isabel','Vargas Rios','9120485 LP',2,'ACTIVO',NULL,NULL,NULL,'2026-08-01 23:30:24','2026-08-01 23:30:24',NULL),(8,'estudiante.carlos@eie.edu.bo',NULL,'$2y$12$hFpj0.bSggzsE1Xgl81S/ecXLE/VRJQXEALnaTsWLeniBVEGwo706',0,'Carlos Eduardo','Siles Torrez','7410928 SC',2,'ACTIVO',NULL,NULL,NULL,'2026-08-01 23:30:24','2026-08-01 23:30:24',NULL),(9,'estudiante.emi@eie.edu.bo',NULL,'$2y$12$hFpj0.bSggzsE1Xgl81S/ecXLE/VRJQXEALnaTsWLeniBVEGwo706',0,'Rodrigo','Alarcon Peñaranda','6190284 LP',2,'ACTIVO',NULL,NULL,NULL,'2026-08-01 23:30:24','2026-08-01 23:30:24',NULL),(10,'estudiante.hijo@eie.edu.bo',NULL,'$2y$12$hFpj0.bSggzsE1Xgl81S/ecXLE/VRJQXEALnaTsWLeniBVEGwo706',0,'Mateo Fernando','Gutierrez Morales','9840192 CB',2,'ACTIVO',NULL,NULL,NULL,'2026-08-01 23:30:25','2026-08-01 23:30:25',NULL),(11,'juan.perez@est.eie.edu.bo','juan.mamani','$2y$12$u2Zi1xnBDpsWlRjPKTRAI.ubZ61ABmemiLxdw3E/lc3vWIH.XHZdu',0,'Juan Carlos','Pérez Militar','1000001',3,'ACTIVO',NULL,NULL,NULL,'2026-08-01 23:31:00','2026-08-01 23:32:45',NULL),(12,'maria.flores@est.eie.edu.bo','aleflex','$2y$12$u2Zi1xnBDpsWlRjPKTRAI.ubZ61ABmemiLxdw3E/lc3vWIH.XHZdu',0,'María Belén','Flores EMI','1000002',3,'ACTIVO',NULL,NULL,NULL,'2026-08-01 23:31:00','2026-08-01 23:32:46',NULL),(13,'carlos.lopez@est.eie.edu.bo',NULL,'$2y$12$hFpj0.bSggzsE1Xgl81S/ecXLE/VRJQXEALnaTsWLeniBVEGwo706',0,'Carlos Daniel','López Hijo','1000003',3,'ACTIVO',NULL,NULL,NULL,'2026-08-01 23:31:00','2026-08-01 23:32:46',NULL),(14,'ana.guzman@gmail.com',NULL,'$2y$12$hFpj0.bSggzsE1Xgl81S/ecXLE/VRJQXEALnaTsWLeniBVEGwo706',0,'Ana Sofía','Guzmán Civil','1000004',3,'ACTIVO',NULL,NULL,NULL,'2026-08-01 23:31:00','2026-08-01 23:32:46',NULL),(16,'admin_nuevo@eie.edu.bo','admin','$2y$12$u2Zi1xnBDpsWlRjPKTRAI.ubZ61ABmemiLxdw3E/lc3vWIH.XHZdu',0,NULL,NULL,NULL,1,'ACTIVO',NULL,NULL,NULL,'2026-08-01 23:37:10','2026-08-01 23:37:10',NULL);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-05 15:59:58
