-- MySQL dump 10.13  Distrib 9.1.0, for Win64 (x86_64)
--
-- Host: localhost    Database: portal_db
-- ------------------------------------------------------
-- Server version	9.1.0

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
-- Table structure for table `authors`
--

DROP TABLE IF EXISTS `authors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `authors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(170) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `authors_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `authors`
--

LOCK TABLES `authors` WRITE;
/*!40000 ALTER TABLE `authors` DISABLE KEYS */;
INSERT INTO `authors` VALUES (1,'James Clear','james-clear',NULL,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(2,'Daniel Kahneman','daniel-kahneman',NULL,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(3,'Robin Sharma','robin-sharma',NULL,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(4,'Brené Brown','brene-brown',NULL,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(5,'Eric Ries','eric-ries',NULL,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(6,'Ray Dalio','ray-dalio',NULL,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(7,'Walter Isaacson','walter-isaacson',NULL,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(8,'Avinash Dixit & Barry Nalebuff','avinash-dixit-barry-nalebuff',NULL,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(9,'Dr. Evelyn Vance','dr-evelyn-vance',NULL,'2026-10-15 09:14:06','2026-10-15 09:14:06');
/*!40000 ALTER TABLE `authors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `book_author`
--

DROP TABLE IF EXISTS `book_author`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `book_author` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `book_id` bigint unsigned NOT NULL,
  `author_id` bigint unsigned NOT NULL,
  `role` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'author',
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `book_author_book_id_author_id_role_unique` (`book_id`,`author_id`,`role`),
  KEY `book_author_author_id_foreign` (`author_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `book_author`
--

LOCK TABLES `book_author` WRITE;
/*!40000 ALTER TABLE `book_author` DISABLE KEYS */;
/*!40000 ALTER TABLE `book_author` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `book_bookmarks`
--

DROP TABLE IF EXISTS `book_bookmarks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `book_bookmarks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `book_id` bigint unsigned NOT NULL,
  `page_number` int unsigned NOT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `book_bookmarks_book_id_foreign` (`book_id`),
  KEY `book_bookmarks_user_id_book_id_page_number_index` (`user_id`,`book_id`,`page_number`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `book_bookmarks`
--

LOCK TABLES `book_bookmarks` WRITE;
/*!40000 ALTER TABLE `book_bookmarks` DISABLE KEYS */;
/*!40000 ALTER TABLE `book_bookmarks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `book_files`
--

DROP TABLE IF EXISTS `book_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `book_files` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `book_id` bigint unsigned NOT NULL,
  `file_path` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` bigint unsigned NOT NULL DEFAULT '0',
  `file_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pdf',
  `disk` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'private',
  `page_count` int unsigned NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `book_files_book_id_foreign` (`book_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `book_files`
--

LOCK TABLES `book_files` WRITE;
/*!40000 ALTER TABLE `book_files` DISABLE KEYS */;
INSERT INTO `book_files` VALUES (1,1,'private/books/atomic-habits.pdf','atomic-habits-executive-edition.pdf',2928,'pdf','local',3,1,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(2,2,'private/books/thinking-fast-and-slow.pdf','thinking-fast-and-slow-executive-edition.pdf',2947,'pdf','local',3,1,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(3,3,'private/books/the-5-am-club.pdf','the-5-am-club-executive-edition.pdf',2926,'pdf','local',3,1,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(4,4,'private/books/dare-to-lead.pdf','dare-to-lead-executive-edition.pdf',2925,'pdf','local',3,1,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(5,5,'private/books/the-lean-startup.pdf','the-lean-startup-executive-edition.pdf',2933,'pdf','local',3,1,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(6,6,'private/books/principles.pdf','principles-executive-edition.pdf',2926,'pdf','local',3,1,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(7,7,'private/books/the-innovators.pdf','the-innovators-executive-edition.pdf',2945,'pdf','local',3,1,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(8,8,'private/books/the-art-of-strategy.pdf','the-art-of-strategy-executive-edition.pdf',2959,'pdf','local',3,1,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(9,9,'private/books/the-sovereign-executive.pdf','the-sovereign-executive-executive-edition.pdf',2892,'pdf','local',3,1,'2026-10-15 09:14:06','2026-10-15 09:14:06'),(10,10,'private/books/rBje7pGsEfez7iwNKGgbG55u0S64hkU1RdpzyZKK.pdf','OCTOBER ENDORSEMENT 2026.pdf',162536,'pdf','local',240,1,'2026-10-15 09:39:42','2026-10-15 09:39:42');
/*!40000 ALTER TABLE `book_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `books`
--

DROP TABLE IF EXISTS `books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `books` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtitle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publication_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'book',
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `author_id` bigint unsigned DEFAULT NULL,
  `author_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `publisher` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `edition` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publication_year` smallint unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `citation_text` text COLLATE utf8mb4_unicode_ci,
  `cover_bg` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#102946',
  `cover_fg` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#ffffff',
  `cover_image` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `pages_count` int unsigned NOT NULL DEFAULT '1',
  `published_year` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `isbn` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doi` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `allow_download` tinyint(1) NOT NULL DEFAULT '0',
  `visibility` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'public',
  `published_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `books_slug_unique` (`slug`),
  KEY `books_category_id_foreign` (`category_id`),
  KEY `books_author_id_foreign` (`author_id`),
  KEY `books_status_index` (`status`),
  KEY `books_published_at_index` (`published_at`),
  KEY `books_publication_type_index` (`publication_type`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `books`
--

LOCK TABLES `books` WRITE;
/*!40000 ALTER TABLE `books` DISABLE KEYS */;
INSERT INTO `books` VALUES (1,'Atomic Habits',NULL,'book','atomic-habits',5,1,'James Clear',NULL,NULL,NULL,'An exploration of small daily improvements and the power of consistent habits.',NULL,'#e6d9c3','#9c6325',NULL,1,320,'2018',NULL,NULL,'published',0,'public','2026-10-15 11:49:16',NULL,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(2,'Thinking, Fast and Slow',NULL,'book','thinking-fast-and-slow',2,2,'Daniel Kahneman',NULL,NULL,NULL,'Explore two modes of thinking and the biases that shape decisions in leadership.',NULL,'#e7e3d9','#243449',NULL,1,499,'2011',NULL,NULL,'published',0,'public','2026-10-15 11:49:16',NULL,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(3,'The 5 AM Club',NULL,'book','the-5-am-club',5,3,'Robin Sharma',NULL,NULL,NULL,'A practical approach to routines, morning productivity and personal growth.',NULL,'#e36c34','#ffffff',NULL,1,336,'2018',NULL,NULL,'published',0,'public','2026-10-15 11:49:16',NULL,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(4,'Dare to Lead',NULL,'book','dare-to-lead',2,4,'Brené Brown',NULL,NULL,NULL,'A guide to courage, vulnerability and effective transformative leadership.',NULL,'#e7eee8','#17565d',NULL,1,320,'2018',NULL,NULL,'published',0,'public','2026-10-15 11:49:16',NULL,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(5,'The Lean Startup',NULL,'book','the-lean-startup',1,5,'Eric Ries',NULL,NULL,NULL,'How to build agile products, test assumptions and learn rapidly from stakeholders.',NULL,'#12669b','#ffffff',NULL,1,336,'2011',NULL,NULL,'published',0,'public','2026-10-15 11:49:16',NULL,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(6,'Principles',NULL,'book','principles',1,6,'Ray Dalio',NULL,NULL,NULL,'Principles for work, life and radical transparency in thoughtful decision-making.',NULL,'#1a1a1c','#ffffff',NULL,0,592,'2017',NULL,NULL,'published',0,'public','2026-10-15 11:49:16',NULL,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(7,'The Innovators',NULL,'book','the-innovators',3,7,'Walter Isaacson',NULL,NULL,NULL,'Stories about the people and collaborations behind the digital and computer revolution.',NULL,'#2d5671','#ffffff',NULL,0,544,'2014',NULL,NULL,'published',0,'public','2026-10-15 11:49:16',NULL,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(8,'The Art of Strategy',NULL,'book','the-art-of-strategy',4,8,'Avinash Dixit & Barry Nalebuff',NULL,NULL,NULL,'An introduction to game theory, strategic thinking and high-impact decision-making.',NULL,'#755d4b','#ffffff',NULL,0,512,'2008',NULL,NULL,'published',0,'public','2026-10-15 11:49:16',NULL,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(9,'The Sovereign Executive',NULL,'book','the-sovereign-executive',1,9,'Dr. Evelyn Vance',NULL,NULL,NULL,NULL,NULL,'#e36c34','#ffffff',NULL,0,240,'2026',NULL,NULL,'published',0,'public','2026-10-15 11:49:16',NULL,'2026-10-15 09:14:06','2026-10-15 09:14:06'),(10,'MFANO',NULL,'book','mfano',1,9,'Dr. Evelyn Vance',NULL,NULL,NULL,'he server is active on http://127.0.0.1:8001/. You can proceed to test or request any specific enhancements.',NULL,'#e6d9c3','#9c6325','covers/MWuF4gklubw1CRTxidIVIKTXGDjMYwJOjg3wumD3.jpg',0,240,'2026',NULL,NULL,'published',0,'public','2026-10-15 11:49:16',NULL,'2026-10-15 09:39:42','2026-10-15 09:39:42');
/*!40000 ALTER TABLE `books` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
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
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
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
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint unsigned DEFAULT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(140) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '▥',
  `description` text COLLATE utf8mb4_unicode_ci,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`),
  KEY `categories_parent_id_foreign` (`parent_id`),
  CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,NULL,'Business & Economics','business-economics','▥','Market dynamics, economic frameworks and sustainable enterprise models.',1,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(2,NULL,'Leadership & Management','leadership-management','♟','Executive governance, decision architecture and organizational culture.',2,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(3,NULL,'Technology & Innovation','technology-innovation','⌘','Digital transformation, artificial intelligence and emerging paradigms.',3,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(4,NULL,'Politics & Governance','politics-governance','⚖','Policy evaluation, geopolitical strategy and public sector leadership.',4,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(5,NULL,'Personal Development','personal-development','✧','Habit mastery, cognitive resilience and continuous lifelong learning.',5,'2026-10-15 08:31:41','2026-10-15 08:31:41'),(6,NULL,'Global Affairs','global-affairs','◎','International systems, trade networks and multilateral developments.',6,'2026-10-15 08:31:41','2026-10-15 08:31:41');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
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
-- Table structure for table `insights`
--

DROP TABLE IF EXISTS `insights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `insights` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Leadership',
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '♟',
  `read_time` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '5 min read',
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `insights_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `insights`
--

LOCK TABLES `insights` WRITE;
/*!40000 ALTER TABLE `insights` DISABLE KEYS */;
INSERT INTO `insights` VALUES (1,'The Future of Leadership in a Changing World','the-future-of-leadership-in-a-changing-world','Leadership','♟','5 min read','Leadership begins with curiosity. Good decisions require time to reflect, a willingness to listen and the discipline to learn from evidence.\n\nIn an era characterized by rapid technological advancement and geopolitical shifts, executive competence is defined by cognitive adaptability. Leaders who foster intellectual safety and deliberate reflection consistently outperform those relying on legacy command-and-control structures.','2026-10-15 08:31:41','2026-10-15 08:31:41','2026-10-15 08:31:41'),(2,'Building a Growth Mindset','building-a-growth-mindset','Personal Growth','🌱','5 min read','Progress begins with a willingness to learn. The most resilient executives treat operational setbacks not as personal verdicts, but as diagnostic indicators.\n\nBy viewing intellectual and professional challenges through the lens of systematic experimentation, teams unlock superior innovation and long-term institutional agility.','2026-10-15 08:31:41','2026-10-15 08:31:41','2026-10-15 08:31:41'),(3,'Why Innovation Matters for Africa’s Development','why-innovation-matters-for-africas-development','Innovation','✦','5 min read','Sustainable prosperity depends on leapfrogging legacy infrastructure with high-leverage digital solutions.\n\nFrom fintech and mobile payment ecosystems to localized logistics and green energy generation, African enterprise is actively pioneering models of frugal innovation that hold profound lessons for executives globally.','2026-10-15 08:31:41','2026-10-15 08:31:41','2026-10-15 08:31:41');
/*!40000 ALTER TABLE `insights` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `queue` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
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
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_10_15_112801_create_portal_tables',2),(5,'2026_10_15_112945_add_role_to_users_table',3),(6,'2026_10_15_144701_convert_tables_to_innodb_and_add_integrity',4),(7,'2026_10_15_144832_add_lifecycle_and_download_permissions_to_books_table',5),(8,'2026_10_15_152000_add_p1_publication_engine_and_reader_tables',6),(9,'2026_10_15_152200_add_reader_profile_fields_to_users_table',7);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
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
-- Table structure for table `reading_progress`
--

DROP TABLE IF EXISTS `reading_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reading_progress` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `book_id` bigint unsigned NOT NULL,
  `current_page` int unsigned NOT NULL DEFAULT '1',
  `total_pages` int unsigned NOT NULL DEFAULT '1',
  `percent_complete` decimal(5,2) NOT NULL DEFAULT '0.00',
  `is_completed` tinyint(1) NOT NULL DEFAULT '0',
  `last_read_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reading_progress_user_id_book_id_unique` (`user_id`,`book_id`),
  KEY `reading_progress_book_id_foreign` (`book_id`),
  KEY `reading_progress_user_id_last_read_at_index` (`user_id`,`last_read_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reading_progress`
--

LOCK TABLES `reading_progress` WRITE;
/*!40000 ALTER TABLE `reading_progress` DISABLE KEYS */;
/*!40000 ALTER TABLE `reading_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `saved_books`
--

DROP TABLE IF EXISTS `saved_books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `saved_books` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `book_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `session_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `saved_books_book_id_foreign` (`book_id`),
  KEY `saved_books_user_id_foreign` (`user_id`),
  KEY `saved_books_session_id_index` (`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saved_books`
--

LOCK TABLES `saved_books` WRITE;
/*!40000 ALTER TABLE `saved_books` DISABLE KEYS */;
INSERT INTO `saved_books` VALUES (1,4,1,NULL,'2026-10-15 09:36:24','2026-10-15 09:36:24'),(2,4,NULL,'S7rjYV0aZN5d7lzl6bW3KbyAcT9HakS8UA5sIefF','2026-10-15 14:23:49','2026-10-15 14:23:49');
/*!40000 ALTER TABLE `saved_books` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
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
INSERT INTO `sessions` VALUES ('Fhq1xfQNnD9T2o39aYMxLWL7O60oNBX5yUf1fMMz',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJzR21ia0hZbkVJZjU5d0NuM3VIWVhFMUxWWUtQcGtvelJpSURxNGpQIiwiX2ZsYXNoIjp7Im5ldyI6W10sIm9sZCI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgwMDFcL215LWxpYnJhcnkiLCJyb3V0ZSI6ImxpYnJhcnkifSwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjEsInBhc3N3b3JkX2hhc2hfd2ViIjoiMDVhMTgyYjJiMmY2ZDFkNzMzNDQ3MzU0Y2Q2ZjE0YzRiOGQyZGQ1NzM0NjMzM2RlYzcyNThlMmFlZWExMDIyOCIsImF1dGhvcml6ZWRfcmVhZGVyXzQiOnRydWV9',1792080146),('S7rjYV0aZN5d7lzl6bW3KbyAcT9HakS8UA5sIefF',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJJdEZpVTdqYXdQZlZranlYRGVkc0xsZHIxaHZTQ0ZTYVpCWmhCNjlTIiwiX2ZsYXNoIjp7Im5ldyI6W10sIm9sZCI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgwMDEiLCJyb3V0ZSI6ImhvbWUifSwiYXV0aG9yaXplZF9yZWFkZXJfNCI6dHJ1ZSwicmVhZGluZ19wcm9ncmVzc180Ijp7ImN1cnJlbnRfcGFnZSI6MiwidG90YWxfcGFnZXMiOjN9fQ==',1792085087);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `position_title` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Executive Director',
  `institution` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Executive Director','admin@executive.portal','admin','active','Chief Executive & Board Administrator',NULL,NULL,'$2y$12$JIpDenn6K.dDLGMfVs0yQOKVXA.9lWKlfSJzyaJwIcVrTMBdV4fUW',NULL,'2026-10-15 08:31:41','2026-10-15 08:31:41');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-15 20:31:55
