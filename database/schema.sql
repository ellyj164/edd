/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.13-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: ecommerce_platform
-- ------------------------------------------------------
-- Server version	10.11.13-MariaDB-0ubuntu0.24.04.1

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
-- Table structure for table `account_closure_requests`
--

DROP TABLE IF EXISTS `account_closure_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_closure_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `additional_comments` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_closure_request_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_closure_requests`
--

LOCK TABLES `account_closure_requests` WRITE;
/*!40000 ALTER TABLE `account_closure_requests` DISABLE KEYS */;
INSERT INTO `account_closure_requests` VALUES
(1,4,'Privacy concerns','','pending',NULL,NULL,NULL,'2025-10-07 08:17:25','2025-10-07 08:17:25');
/*!40000 ALTER TABLE `account_closure_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activity_feed`
--

DROP TABLE IF EXISTS `activity_feed`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_feed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `actor_id` int(11) DEFAULT NULL,
  `actor_type` enum('user','system','admin') NOT NULL DEFAULT 'user',
  `action` varchar(255) NOT NULL,
  `target_type` varchar(100) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `description` text NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_actor_id` (`actor_id`),
  KEY `idx_actor_type` (`actor_type`),
  KEY `idx_action` (`action`),
  KEY `idx_target` (`target_type`,`target_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_activity_feed_actor_action` (`actor_id`,`action`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_feed`
--

LOCK TABLES `activity_feed` WRITE;
/*!40000 ALTER TABLE `activity_feed` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_feed` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('billing','shipping','both') NOT NULL DEFAULT 'both',
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(2) NOT NULL DEFAULT 'US',
  `phone` varchar(20) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_is_default` (`is_default`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `addresses`
--

LOCK TABLES `addresses` WRITE;
/*!40000 ALTER TABLE `addresses` DISABLE KEYS */;
INSERT INTO `addresses` VALUES
(1,4,'both','Joseph','Niyogushimwa','','Kigali','','Kigali','Rwanda','0000','US','',1,'2025-10-03 21:12:15','2025-10-03 21:12:15');
/*!40000 ALTER TABLE `addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_actions`
--

DROP TABLE IF EXISTS `admin_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `target_type` varchar(50) NOT NULL,
  `target_id` int(11) DEFAULT NULL,
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_data`)),
  `notes` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_target` (`target_type`,`target_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_actions`
--

LOCK TABLES `admin_actions` WRITE;
/*!40000 ALTER TABLE `admin_actions` DISABLE KEYS */;
INSERT INTO `admin_actions` VALUES
(1,1,'update','category',1,NULL,'{\"name\":\"Electronics\",\"parent_id\":null,\"slug\":\"electronics\",\"is_active\":1}','',NULL,'2025-09-14 20:04:01');
/*!40000 ALTER TABLE `admin_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_activity_logs`
--

DROP TABLE IF EXISTS `admin_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `resource_type` varchar(50) DEFAULT NULL,
  `resource_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_action` (`action`),
  KEY `idx_resource_type` (`resource_type`),
  KEY `idx_resource_id` (`resource_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_admin_activity_logs_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_activity_logs`
--

LOCK TABLES `admin_activity_logs` WRITE;
/*!40000 ALTER TABLE `admin_activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_analytics`
--

DROP TABLE IF EXISTS `admin_analytics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `metric_name` varchar(100) NOT NULL,
  `metric_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `metric_type` enum('sales','revenue','orders','users','products','views','clicks') NOT NULL,
  `period_type` enum('daily','weekly','monthly','yearly') NOT NULL DEFAULT 'daily',
  `date_recorded` date NOT NULL,
  `additional_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_metric_period_date` (`metric_name`,`period_type`,`date_recorded`),
  KEY `idx_metric_type` (`metric_type`),
  KEY `idx_period_type` (`period_type`),
  KEY `idx_date_recorded` (`date_recorded`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_analytics`
--

LOCK TABLES `admin_analytics` WRITE;
/*!40000 ALTER TABLE `admin_analytics` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_analytics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_dashboards`
--

DROP TABLE IF EXISTS `admin_dashboards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_dashboards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `layout_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`layout_config`)),
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_shared` tinyint(1) NOT NULL DEFAULT 0,
  `shared_with` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`shared_with`)),
  `refresh_interval` int(11) NOT NULL DEFAULT 300,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_default` (`is_default`),
  KEY `idx_is_shared` (`is_shared`),
  CONSTRAINT `fk_admin_dashboards_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_dashboards`
--

LOCK TABLES `admin_dashboards` WRITE;
/*!40000 ALTER TABLE `admin_dashboards` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_dashboards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_roles`
--

DROP TABLE IF EXISTS `admin_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`permissions`)),
  `is_system_role` tinyint(1) NOT NULL DEFAULT 0,
  `hierarchy_level` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`),
  KEY `idx_name` (`name`),
  KEY `idx_is_system_role` (`is_system_role`),
  KEY `idx_hierarchy_level` (`hierarchy_level`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `fk_admin_roles_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_roles`
--

LOCK TABLES `admin_roles` WRITE;
/*!40000 ALTER TABLE `admin_roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_widgets`
--

DROP TABLE IF EXISTS `admin_widgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_widgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dashboard_id` int(11) NOT NULL,
  `widget_type` enum('chart','table','counter','progress','list','map','calendar','custom') NOT NULL,
  `widget_name` varchar(255) NOT NULL,
  `data_source` varchar(255) NOT NULL,
  `configuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`configuration`)),
  `position_x` int(11) NOT NULL DEFAULT 0,
  `position_y` int(11) NOT NULL DEFAULT 0,
  `width` int(11) NOT NULL DEFAULT 6,
  `height` int(11) NOT NULL DEFAULT 4,
  `refresh_interval` int(11) NOT NULL DEFAULT 300,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_dashboard_id` (`dashboard_id`),
  KEY `idx_widget_type` (`widget_type`),
  KEY `idx_is_visible` (`is_visible`),
  CONSTRAINT `fk_admin_widgets_dashboard` FOREIGN KEY (`dashboard_id`) REFERENCES `admin_dashboards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_widgets`
--

LOCK TABLES `admin_widgets` WRITE;
/*!40000 ALTER TABLE `admin_widgets` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_widgets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agent_presence`
--

DROP TABLE IF EXISTS `agent_presence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `agent_presence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) NOT NULL,
  `status` enum('online','away','busy','offline') DEFAULT 'offline',
  `last_seen` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `current_chats` int(11) DEFAULT 0,
  `max_chats` int(11) DEFAULT 5,
  PRIMARY KEY (`id`),
  UNIQUE KEY `agent_id` (`agent_id`),
  KEY `idx_status` (`status`),
  KEY `idx_last_seen` (`last_seen`),
  CONSTRAINT `agent_presence_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agent_presence`
--

LOCK TABLES `agent_presence` WRITE;
/*!40000 ALTER TABLE `agent_presence` DISABLE KEYS */;
/*!40000 ALTER TABLE `agent_presence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai_interactions`
--

DROP TABLE IF EXISTS `ai_interactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_interactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(100) NOT NULL,
  `chat_id` int(11) DEFAULT NULL,
  `prompt` text NOT NULL,
  `response` text DEFAULT NULL,
  `provider` varchar(50) DEFAULT 'openai',
  `model` varchar(50) DEFAULT 'gpt-3.5-turbo',
  `tokens_used` int(11) DEFAULT 0,
  `response_time_ms` int(11) DEFAULT 0,
  `error` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_chat_id` (`chat_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `ai_interactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ai_interactions_ibfk_2` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_interactions`
--

LOCK TABLES `ai_interactions` WRITE;
/*!40000 ALTER TABLE `ai_interactions` DISABLE KEYS */;
INSERT INTO `ai_interactions` VALUES
(1,4,'session_1759827252543_kl7fw0g27',NULL,'hi','I\'m here to help! I can assist you with:\n\n• Order tracking and status\n• Returns and refunds\n• Product information\n• Account and payment issues\n• Shipping details\n\nWhat would you like to know more about? Or I can connect you with our support team for personalized assistance.','fallback','faq',0,0,NULL,'2025-10-07 08:54:16'),
(2,4,'session_1759827252543_kl7fw0g27',NULL,'I need assistant','I\'m here to help! I can assist you with:\n\n• Order tracking and status\n• Returns and refunds\n• Product information\n• Account and payment issues\n• Shipping details\n\nWhat would you like to know more about? Or I can connect you with our support team for personalized assistance.','fallback','faq',0,0,NULL,'2025-10-07 08:54:35'),
(3,4,'session_1759827364949_q76q17em1',NULL,'How do I contact support?','I\'d be happy to connect you with our support team! You can reach them via email at support@fezamarket.com or use our live chat during business hours (Mon-Fri, 9AM-6PM). Would you like me to start a support chat for you?','fallback','faq',0,0,NULL,'2025-10-07 08:56:24'),
(4,4,'session_1759827772741_qjmykk083',NULL,'What is your return policy?','We have a 30-day return policy for most items. To initiate a return, go to your Order History, select the order, and click \'Request Return\'. You\'ll receive a prepaid shipping label. Refunds are processed within 5-7 business days after we receive your return.','fallback','faq',0,0,NULL,'2025-10-07 09:02:56'),
(5,NULL,'session_1759827896837_nk5acja9z',NULL,'How do I track my order?','We offer multiple shipping options! You can track your order from your account dashboard. Standard shipping takes 5-7 business days, while express shipping arrives in 2-3 days. For tracking help, visit your Orders page or contact our support team.','fallback','faq',0,0,NULL,'2025-10-07 09:05:01'),
(6,4,'session_1759848591071_vm520yzan',NULL,'How do I contact support?','I\'d be happy to connect you with our support team! You can reach them via email at support@fezamarket.com or use our live chat during business hours (Mon-Fri, 9AM-6PM). Would you like me to start a support chat for you?','fallback','faq',0,0,NULL,'2025-10-07 14:49:52'),
(7,NULL,'session_1759853810627_fgzx6p74c',NULL,'can I get a shoes','I\'m Feza AI, your shopping assistant! I can help you with:\n\n• Order tracking and status\n• Returns and refunds\n• Product information and availability\n• Account and payment issues\n• Shipping details\n• Making offers on products\n• Wishlist and watchlist features\n• Current deals and promotions\n\nWhat would you like to know more about? Or I can connect you with our support team for personalized assistance.','fallback','faq',0,0,NULL,'2025-10-07 16:17:36'),
(8,NULL,'session_1759853810627_fgzx6p74c',NULL,'Can I get a shoes','I\'m Feza AI, your shopping assistant! I can help you with:\n\n• Order tracking and status\n• Returns and refunds\n• Product information and availability\n• Account and payment issues\n• Shipping details\n• Making offers on products\n• Wishlist and watchlist features\n• Current deals and promotions\n\nWhat would you like to know more about? Or I can connect you with our support team for personalized assistance.','fallback','faq',0,0,NULL,'2025-10-07 16:18:11'),
(9,NULL,'session_1759853810627_fgzx6p74c',NULL,'delivery','We offer multiple shipping options! You can track your order from your account dashboard. Standard shipping takes 5-7 business days, while express shipping arrives in 2-3 days. For tracking help, visit your Orders page or contact our support team.','fallback','faq',0,0,NULL,'2025-10-07 16:18:29'),
(10,NULL,'session_1759853810627_fgzx6p74c',NULL,'how can I get my account','Having trouble with your account? You can reset your password from the login page. If you haven\'t verified your email yet, check your inbox for the verification link. For other account issues, please contact our support team.','fallback','faq',0,0,NULL,'2025-10-07 16:19:54'),
(11,4,'session_1759863734500_boompiw0l',NULL,'How do I contact support?','I\'d be happy to connect you with our support team! You can reach them via email at support@fezamarket.com or use our live chat during business hours (Mon-Fri, 9AM-6PM). Would you like me to start a support chat for you?','fallback','faq',0,0,NULL,'2025-10-07 19:02:16'),
(12,4,'session_1759864588280_ikmxygs9f',NULL,'How do I contact support?','I\'d be happy to connect you with our support team! You can reach them via email at support@fezamarket.com or use our live chat during business hours (Mon-Fri, 9AM-6PM). Would you like me to start a support chat for you?','fallback','faq',0,0,NULL,'2025-10-07 19:16:34'),
(13,NULL,'session_1759873168241_66dsvmslk',NULL,'How do I track my order?','We offer multiple shipping options! You can track your order from your account dashboard. Standard shipping takes 5-7 business days, while express shipping arrives in 2-3 days. For tracking help, visit your Orders page or contact our support team.','fallback','faq',0,0,NULL,'2025-10-07 21:39:34'),
(14,4,'session_1759907251258_20w0eoka5',NULL,'What is your return policy?','We have a 30-day return policy for most items. To initiate a return, go to your Order History, select the order, and click \'Request Return\'. You\'ll receive a prepaid shipping label. Refunds are processed within 5-7 business days after we receive your return.','fallback','faq',0,0,NULL,'2025-10-08 07:07:34'),
(15,NULL,'session_1759955927279_hygxbm7bl',NULL,'Kemie','I\'m Feza AI, your shopping assistant! I can help you with:\n\n• Order tracking and status\n• Returns and refunds\n• Product information and availability\n• Account and payment issues\n• Shipping details\n• Making offers on products\n• Wishlist and watchlist features\n• Current deals and promotions\n\nWhat would you like to know more about? Or I can connect you with our support team for personalized assistance.','fallback','faq',0,0,NULL,'2025-10-08 20:39:17'),
(16,4,'session_1760023336420_jp6ri9fwx',NULL,'What is your return policy?','We have a 30-day return policy for most items. To initiate a return, go to your Order History, select the order, and click \'Request Return\'. You\'ll receive a prepaid shipping label. Refunds are processed within 5-7 business days after we receive your return.','fallback','faq',0,0,NULL,'2025-10-09 15:22:18'),
(17,4,'session_1760037085069_j27wt7fb4',NULL,'How do I track my order?','We offer multiple shipping options! You can track your order from your account dashboard. Standard shipping takes 5-7 business days, while express shipping arrives in 2-3 days. For tracking help, visit your Orders page or contact our support team.','fallback','faq',0,0,NULL,'2025-10-09 19:11:29'),
(18,4,'session_1760050522653_dqbql6ul0',NULL,'hi','Hi there 👋! Welcome to FezaMarket. How can I help you today?','fallback','faq',0,5,NULL,'2025-10-09 22:55:33'),
(19,4,'session_1760050522653_dqbql6ul0',NULL,'i need help','I\\\'m here to help! What do you need assistance with?','fallback','faq',0,0,NULL,'2025-10-09 22:55:43'),
(20,4,'session_1760050522653_dqbql6ul0',NULL,'shipping','Standard shipping takes 2-5 business days. Express shipping arrives in 1-2 days. Delivery times depend on your location.','fallback','faq',0,0,NULL,'2025-10-09 22:56:00'),
(21,4,'session_1760050522653_dqbql6ul0',NULL,'thanks','You\'re welcome! 😊 Is there anything else I can help you with?','fallback','faq',0,0,NULL,'2025-10-09 22:56:13'),
(22,4,'session_1760050522653_dqbql6ul0',NULL,'yes','I may not know that yet, but I\'m here to help! Try asking about:\n• Order tracking\n• Returns & refunds\n• Shipping info\n• Payment methods\n• Account issues','fallback','faq',0,1,NULL,'2025-10-09 22:56:23'),
(23,4,'session_1760050522653_dqbql6ul0',NULL,'closing account','No, guest checkout is available. However, an account lets you track orders and save preferences.','fallback','faq',0,0,NULL,'2025-10-09 22:56:44'),
(24,4,'session_1760050522653_dqbql6ul0',NULL,'shopping','Click \'Continue Shopping\' from cart to browse while keeping items saved.','fallback','faq',0,1,NULL,'2025-10-09 22:57:12'),
(25,4,'session_1760050522653_dqbql6ul0',NULL,'thanks','You\'re welcome! 😊 Is there anything else I can help you with?','fallback','faq',0,0,NULL,'2025-10-09 22:57:23'),
(26,4,'session_1760050522653_dqbql6ul0',NULL,'none','I may not know that yet, but I\'m here to help! Try asking about:\n• Order tracking\n• Returns & refunds\n• Shipping info\n• Payment methods\n• Account issues','fallback','faq',0,2,NULL,'2025-10-09 22:57:27'),
(27,4,'session_1760050522653_dqbql6ul0',NULL,'nothing','I\'m not quite sure about that, but I can help with shopping, orders, shipping, and account questions!','fallback','faq',0,1,NULL,'2025-10-09 22:57:31'),
(28,4,'session_1760050806342_kf4uwhuwr',NULL,'good  morning','Good morning! ☀️ Ready to start shopping? How can I help you today?','fallback','faq',0,1,NULL,'2025-10-09 23:00:16'),
(29,4,'session_1760050806342_kf4uwhuwr',NULL,'good evenning','Good morning! ☀️ Ready to start shopping? How can I help you today?','fallback','faq',0,0,NULL,'2025-10-09 23:00:29'),
(30,4,'session_1760050806342_kf4uwhuwr',NULL,'what is fezamarket','FezaMarket is Rwanda\'s premier online marketplace connecting buyers and sellers across Africa!','fallback','faq',0,0,NULL,'2025-10-09 23:00:39'),
(31,4,'session_1760050806342_kf4uwhuwr',NULL,'When did fezamarket start?','FezaMarket is Rwanda\'s premier online marketplace connecting buyers and sellers across Africa!','fallback','faq',0,0,NULL,'2025-10-09 23:01:00'),
(32,NULL,'session_1760076853600_ijtaxxzoq',NULL,'Hi','Hi there 👋! Welcome to FezaMarket. How can I help you today?','fallback','faq',0,0,NULL,'2025-10-10 06:14:19'),
(33,NULL,'session_1760076853600_ijtaxxzoq',NULL,'I need help','I\\\'m here to help! What do you need assistance with?','fallback','faq',0,9,NULL,'2025-10-10 06:14:29'),
(34,4,'session_1760077721274_eynpvbssx',NULL,'Hi','Hi there 👋! Welcome to FezaMarket. How can I help you today?','fallback','faq',0,5,NULL,'2025-10-10 06:28:44'),
(35,4,'session_1760078536942_76bnsc9z6',NULL,'How do I contact support?','Reach support by email, live chat (Mon-Fri 9AM-6PM), or phone. We respond within 24 hours!','fallback','faq',0,1,NULL,'2025-10-10 06:42:20'),
(36,4,'session_1760104737854_hxcyc72oi',NULL,'What is your return policy?','We have a 30-day return policy! Items must be unused and in original packaging. Start returns from \'My Orders\'.','fallback','faq',0,20,NULL,'2025-10-10 13:59:13');
/*!40000 ALTER TABLE `ai_interactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai_recommendations`
--

DROP TABLE IF EXISTS `ai_recommendations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_recommendations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `recommendation_type` enum('product','category','vendor','content','promotion') NOT NULL,
  `algorithm_used` varchar(100) NOT NULL,
  `recommendation_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`recommendation_data`)),
  `confidence_score` decimal(5,4) NOT NULL DEFAULT 0.0000,
  `interaction_context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`interaction_context`)),
  `is_clicked` tinyint(1) NOT NULL DEFAULT 0,
  `is_purchased` tinyint(1) NOT NULL DEFAULT 0,
  `clicked_at` timestamp NULL DEFAULT NULL,
  `purchased_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_recommendation_type` (`recommendation_type`),
  KEY `idx_algorithm_used` (`algorithm_used`),
  KEY `idx_confidence_score` (`confidence_score`),
  KEY `idx_is_clicked` (`is_clicked`),
  KEY `idx_is_purchased` (`is_purchased`),
  KEY `idx_expires_at` (`expires_at`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_ai_recommendations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_recommendations`
--

LOCK TABLES `ai_recommendations` WRITE;
/*!40000 ALTER TABLE `ai_recommendations` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_recommendations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_endpoints`
--

DROP TABLE IF EXISTS `api_endpoints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_endpoints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `endpoint_path` varchar(255) NOT NULL,
  `http_method` enum('GET','POST','PUT','PATCH','DELETE') NOT NULL DEFAULT 'GET',
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `requires_auth` tinyint(1) NOT NULL DEFAULT 1,
  `required_permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`required_permissions`)),
  `rate_limit_requests` int(11) NOT NULL DEFAULT 100,
  `rate_limit_window` int(11) NOT NULL DEFAULT 3600,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `version` varchar(10) NOT NULL DEFAULT 'v1',
  `documentation_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_endpoint_method_version` (`endpoint_path`,`http_method`,`version`),
  KEY `idx_is_public` (`is_public`),
  KEY `idx_requires_auth` (`requires_auth`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_version` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_endpoints`
--

LOCK TABLES `api_endpoints` WRITE;
/*!40000 ALTER TABLE `api_endpoints` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_endpoints` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_keys`
--

DROP TABLE IF EXISTS `api_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `subscription_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `environment` enum('sandbox','live') NOT NULL DEFAULT 'sandbox',
  `api_key` varchar(64) NOT NULL,
  `api_secret` varchar(128) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `rate_limit` int(11) NOT NULL DEFAULT 100,
  `rate_window` int(11) NOT NULL DEFAULT 3600,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_api_key` (`api_key`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_last_used_at` (`last_used_at`),
  KEY `idx_expires_at` (`expires_at`),
  KEY `idx_environment` (`environment`),
  KEY `idx_api_keys_subscription` (`subscription_id`),
  CONSTRAINT `fk_api_key_subscription` FOREIGN KEY (`subscription_id`) REFERENCES `api_subscriptions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_api_keys_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_keys`
--

LOCK TABLES `api_keys` WRITE;
/*!40000 ALTER TABLE `api_keys` DISABLE KEYS */;
INSERT INTO `api_keys` VALUES
(1,4,NULL,'models','live','feza_live_9127ce894cca9c12209fff978d2e080e17515b4a432f5c9a','041e0a14364d7a7d1c3204eef75a5478f3753f7bfe1edabb72d062aea0b80123',NULL,100,3600,1,NULL,NULL,'2025-10-05 13:12:22','2025-10-05 13:12:22'),
(2,4,NULL,'models','live','feza_live_630bc1a797a6b865c27270290941ac0cc846c0dbb482ff80','7cc39461075f3a16ecfc7861e1d6059be643049609eb6622d64b47e8b1559624',NULL,100,3600,1,NULL,NULL,'2025-10-05 13:12:22','2025-10-06 18:00:22');
/*!40000 ALTER TABLE `api_keys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_logs`
--

DROP TABLE IF EXISTS `api_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `api_key_id` int(11) DEFAULT NULL,
  `endpoint` varchar(255) NOT NULL,
  `method` varchar(10) NOT NULL,
  `request_headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`request_headers`)),
  `request_body` longtext DEFAULT NULL,
  `response_status` int(11) NOT NULL,
  `response_headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response_headers`)),
  `response_body` longtext DEFAULT NULL,
  `response_time` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_api_key_id` (`api_key_id`),
  KEY `idx_endpoint` (`endpoint`),
  KEY `idx_method` (`method`),
  KEY `idx_response_status` (`response_status`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_api_logs_key_date` (`api_key_id`,`created_at`),
  KEY `idx_api_logs_ip` (`ip_address`),
  CONSTRAINT `fk_api_logs_api_key` FOREIGN KEY (`api_key_id`) REFERENCES `api_keys` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_logs`
--

LOCK TABLES `api_logs` WRITE;
/*!40000 ALTER TABLE `api_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_subscription_invoices`
--

DROP TABLE IF EXISTS `api_subscription_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_subscription_invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subscription_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','failed','refunded','cancelled') NOT NULL DEFAULT 'pending',
  `invoice_number` varchar(50) NOT NULL,
  `billing_period_start` timestamp NULL DEFAULT NULL,
  `billing_period_end` timestamp NULL DEFAULT NULL,
  `due_date` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `invoice_url` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_invoice_number` (`invoice_number`),
  KEY `subscription_id` (`subscription_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_invoice_status` (`status`),
  KEY `idx_due_date` (`due_date`),
  CONSTRAINT `fk_invoice_subscription` FOREIGN KEY (`subscription_id`) REFERENCES `api_subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Invoices for API subscription payments';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_subscription_invoices`
--

LOCK TABLES `api_subscription_invoices` WRITE;
/*!40000 ALTER TABLE `api_subscription_invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_subscription_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_subscriptions`
--

DROP TABLE IF EXISTS `api_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan_id` varchar(50) NOT NULL,
  `payment_processor_subscription_id` varchar(255) NOT NULL,
  `status` enum('active','cancelled','past_due') NOT NULL,
  `start_date` timestamp NOT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id_unique` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='API subscription plans - sandbox (free), live ($150/month), government (special access)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_subscriptions`
--

LOCK TABLES `api_subscriptions` WRITE;
/*!40000 ALTER TABLE `api_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_usage_metrics`
--

DROP TABLE IF EXISTS `api_usage_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_usage_metrics` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `api_key_id` int(11) NOT NULL,
  `subscription_id` int(11) DEFAULT NULL,
  `endpoint` varchar(255) NOT NULL,
  `method` varchar(10) NOT NULL,
  `status_code` int(11) NOT NULL,
  `response_time_ms` int(11) DEFAULT NULL,
  `request_size_bytes` int(11) DEFAULT NULL,
  `response_size_bytes` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `api_key_id` (`api_key_id`),
  KEY `subscription_id` (`subscription_id`),
  KEY `idx_usage_date` (`created_at`),
  KEY `idx_usage_endpoint` (`endpoint`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='API usage tracking for analytics and billing';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_usage_metrics`
--

LOCK TABLES `api_usage_metrics` WRITE;
/*!40000 ALTER TABLE `api_usage_metrics` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_usage_metrics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `resource_type` varchar(100) DEFAULT NULL,
  `resource_id` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `new_values` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_action` (`action`)
) ENGINE=InnoDB AUTO_INCREMENT=160 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
INSERT INTO `audit_log` VALUES
(1,1,'login_failed_inactive','user','1','105.178.104.198','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','[]','2025-09-11 18:55:47'),
(2,1,'login_success','user','1','105.178.104.198','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','[]','2025-09-11 19:11:33'),
(3,1,'login_success','user','1','105.178.104.198','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','[]','2025-09-11 19:27:53'),
(4,1,'login_success','user','1','105.178.104.198','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','[]','2025-09-11 19:33:28'),
(5,1,'login_success','user','1','105.178.104.198','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','[]','2025-09-11 19:36:42'),
(6,1,'login_success','user','1','105.178.104.198','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','[]','2025-09-11 19:39:47'),
(7,1,'login_success','user','1','105.178.104.198','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','[]','2025-09-11 19:41:07'),
(8,1,'login_success','user','1','105.178.104.198','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','[]','2025-09-11 19:41:10'),
(9,1,'login_success','user','1','197.157.155.163','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','[]','2025-09-11 21:46:44'),
(10,1,'login_success','user','1','197.157.155.163','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','[]','2025-09-11 21:50:31'),
(11,4,'login_failed_inactive','user','4','197.157.155.163','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','[]','2025-09-11 21:57:03'),
(12,1,'update','admin_action','1','197.157.145.25','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','{\"action\":\"update\",\"target_type\":\"category\",\"notes\":\"\",\"new_data\":{\"name\":\"Electronics\",\"parent_id\":null,\"slug\":\"electronics\",\"is_active\":1}}','2025-09-15 00:04:01'),
(13,4,'login_failed_inactive','user','4','197.157.145.25','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-15 00:44:24'),
(14,4,'login_success','user','4','197.157.145.25','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-15 00:45:42'),
(15,4,'login_success','user','4','105.178.32.82','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-15 19:09:28'),
(16,4,'login_success','user','4','197.157.155.6','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-15 21:22:52'),
(17,4,'login_success','user','4','197.157.155.6','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-15 21:29:35'),
(18,4,'login_success','user','4','197.157.155.6','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-15 22:00:11'),
(19,4,'login_success','user','4','197.157.155.6','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-15 22:08:43'),
(20,4,'login_success','user','4','197.157.155.22','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-15 23:11:16'),
(21,4,'login_success','user','4','197.157.155.22','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-15 23:14:55'),
(22,4,'login_success','user','4','197.157.155.22','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-16 00:19:58'),
(23,4,'login_success','user','4','197.157.155.22','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-16 01:21:19'),
(24,4,'login_success','user','4','197.157.155.22','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-16 02:58:23'),
(25,4,'login_success','user','4','105.178.32.56','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-16 09:50:29'),
(26,4,'login_success','user','4','105.178.32.56','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-16 11:35:24'),
(27,4,'login_success','user','4','105.178.104.56','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-16 12:10:19'),
(28,4,'login_success','user','4','105.178.104.129','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-16 15:25:14'),
(29,4,'login_success','user','4','105.178.32.65','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-16 20:10:15'),
(30,4,'login_success','user','4','105.178.104.65','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-16 20:51:50'),
(31,4,'login_success','user','4','197.157.135.133','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-21 02:20:08'),
(32,4,'login_success','user','4','197.157.135.133','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-21 02:21:19'),
(33,NULL,'login_failed','user',NULL,'197.157.135.133','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','{\"email\":\"niyogushimwaj967@gmail.com\"}','2025-09-21 02:26:56'),
(34,5,'login_success','user','5','197.157.135.133','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-21 02:27:04'),
(35,4,'login_success','user','4','197.157.135.133','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-21 02:31:22'),
(36,NULL,'login_failed','user',NULL,'41.186.132.60','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','{\"email\":\"ellyj164@gmail.com\"}','2025-09-21 10:56:56'),
(37,4,'login_success','user','4','41.186.132.60','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-21 10:57:04'),
(38,4,'login_success','user','4','197.157.187.91','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-21 13:07:53'),
(39,4,'login_success','user','4','105.178.104.165','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-27 11:29:06'),
(40,4,'login_success','user','4','105.178.32.38','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-27 14:29:31'),
(41,4,'login_success','user','4','102.22.163.69','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-27 16:20:48'),
(42,4,'login_success','user','4','105.178.104.79','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-27 18:43:44'),
(43,NULL,'login_failed','user',NULL,'105.178.104.79','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','{\"email\":\"ellyj164@gmail.com\"}','2025-09-27 19:26:39'),
(44,4,'login_success','user','4','105.178.104.79','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-27 19:26:47'),
(45,4,'login_success','user','4','105.178.104.79','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-27 22:44:06'),
(46,4,'login_success','user','4','197.157.155.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-28 21:59:25'),
(47,4,'login_success','user','4','197.157.155.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-28 21:59:42'),
(48,4,'login_success','user','4','197.157.155.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-28 22:01:47'),
(49,4,'login_success','user','4','197.157.155.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-28 23:41:55'),
(50,5,'login_success','user','5','197.157.155.7','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36','[]','2025-09-29 00:06:43'),
(51,4,'login_success','user','4','197.157.155.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-29 11:17:37'),
(52,4,'login_success','user','4','105.178.104.179','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-29 12:59:37'),
(53,4,'login_success','user','4','197.157.155.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-29 14:03:37'),
(54,4,'login_success','user','4','197.157.145.29','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-29 22:00:41'),
(55,NULL,'login_failed','user',NULL,'197.157.145.29','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','{\"email\":\"ellyj164@gmail.com\"}','2025-09-29 22:03:34'),
(56,4,'login_success','user','4','197.157.145.29','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-29 22:03:41'),
(57,4,'login_success','user','4','197.157.145.29','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-30 00:23:24'),
(58,4,'login_success','user','4','197.157.145.29','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-30 00:59:28'),
(59,4,'login_success','user','4','197.157.145.29','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-30 08:17:58'),
(60,NULL,'login_failed','user',NULL,'105.178.104.179','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','{\"email\":\"ellyj164@gmail.com\"}','2025-09-30 09:43:21'),
(61,4,'login_success','user','4','105.178.104.179','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-30 09:43:26'),
(62,4,'login_success','user','4','105.178.32.179','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-09-30 10:48:23'),
(63,4,'login_success','user','4','197.157.155.132','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-01 22:40:32'),
(64,4,'login_success','user','4','197.157.155.132','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-01 23:41:18'),
(65,NULL,'login_failed','user',NULL,'197.157.155.132','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','{\"email\":\"ellyj164@gmail.com\"}','2025-10-02 01:03:44'),
(66,4,'login_success','user','4','197.157.155.132','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-02 01:03:58'),
(67,4,'login_success','user','4','105.178.104.110','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-03 15:40:58'),
(68,4,'login_success','user','4','105.178.32.109','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-03 16:37:32'),
(69,NULL,'login_failed','user',NULL,'197.157.135.231','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','{\"email\":\"ellyj164@gmail.com\"}','2025-10-03 21:15:31'),
(70,4,'login_success','user','4','197.157.135.231','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-03 21:15:40'),
(71,4,'login_success','user','4','197.157.165.150','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-03 22:41:13'),
(72,NULL,'login_failed','user',NULL,'197.157.165.150','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','{\"email\":\"ellyj164@gmail.com\"}','2025-10-03 23:46:19'),
(73,4,'login_success','user','4','197.157.165.150','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-03 23:46:26'),
(74,4,'login_success','user','4','197.157.165.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-04 06:46:31'),
(75,4,'login_success','user','4','197.157.165.87','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36','[]','2025-10-04 07:39:23'),
(76,4,'login_success','user','4','197.157.165.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-04 09:03:20'),
(77,4,'login_success','user','4','197.157.165.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-04 09:19:38'),
(78,4,'login_success','user','4','197.157.165.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-04 11:14:01'),
(79,4,'login_success','user','4','197.157.165.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-04 12:18:18'),
(80,5,'login_success','user','5','105.178.104.109','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36','[]','2025-10-04 12:46:56'),
(81,NULL,'login_failed','user',NULL,'197.157.165.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','{\"email\":\"ellyj164@gmail.com\"}','2025-10-04 15:04:04'),
(82,4,'login_success','user','4','197.157.165.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-04 15:04:11'),
(83,5,'login_success','user','5','197.157.165.87','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36','[]','2025-10-04 16:05:17'),
(84,4,'login_success','user','4','102.22.139.51','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-04 18:14:43'),
(85,4,'login_success','user','4','197.157.165.35','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-05 00:54:31'),
(86,NULL,'login_failed','user',NULL,'197.157.135.63','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','{\"email\":\"ellyj164@gmail.com\"}','2025-10-05 12:39:47'),
(87,4,'login_success','user','4','197.157.135.63','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-05 12:39:55'),
(88,4,'login_success','user','4','197.157.135.63','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-05 14:45:41'),
(89,4,'login_success','user','4','197.157.135.63','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-05 14:48:44'),
(90,4,'login_success','user','4','197.157.135.63','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36','[]','2025-10-05 16:29:25'),
(91,4,'login_success','user','4','197.157.135.63','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-06 00:46:08'),
(92,5,'login_success','user','5','197.157.135.63','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36','[]','2025-10-06 01:00:13'),
(93,4,'login_success','user','4','105.178.104.56','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-06 10:44:07'),
(94,4,'login_success','user','4','105.178.104.56','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-06 11:55:14'),
(95,5,'login_success','user','5','105.178.32.56','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36','[]','2025-10-06 12:52:09'),
(96,4,'login_success','user','4','105.178.32.56','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-06 12:58:02'),
(97,NULL,'login_failed','user',NULL,'41.186.139.85','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36','{\"email\":\"ellyj164@gmail.com\"}','2025-10-06 13:14:53'),
(98,4,'login_success','user','4','41.186.139.85','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36','[]','2025-10-06 13:15:05'),
(99,4,'login_success','user','4','105.178.104.56','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-06 15:22:44'),
(100,4,'login_success','user','4','105.178.104.56','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-06 16:00:25'),
(101,5,'login_success','user','5','105.178.104.56','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-06 16:01:49'),
(102,4,'login_success','user','4','197.157.155.84','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-06 19:57:17'),
(103,5,'login_success','user','5','197.157.155.84','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-06 20:01:21'),
(104,4,'login_success','user','4','197.157.155.84','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-06 22:55:51'),
(105,6,'login_failed_inactive','user','6','197.157.135.201','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-07 00:32:12'),
(106,4,'login_success','user','4','197.157.135.201','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-07 08:48:28'),
(107,4,'login_success','user','4','197.157.135.201','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-07 10:15:54'),
(108,4,'login_success','user','4','197.157.135.201','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-07 16:46:50'),
(109,12,'login_failed_inactive','user','12','105.178.32.123','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-07 18:27:36'),
(110,NULL,'login_failed','user',NULL,'105.178.104.74','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','{\"email\":\"ellyj164@gmail.com\"}','2025-10-07 18:59:38'),
(111,4,'login_success','user','4','105.178.104.74','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-07 18:59:45'),
(112,13,'login_failed_inactive','user','13','105.178.32.74','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-07 19:06:46'),
(113,13,'login_success','user','13','105.178.32.74','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-07 19:07:32'),
(114,4,'login_success','user','4','105.178.104.74','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-07 19:33:01'),
(115,NULL,'login_failed','user',NULL,'105.178.32.74','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','{\"email\":\"ellyj164@gmail.com\"}','2025-10-07 20:51:48'),
(116,4,'login_success','user','4','105.178.32.74','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-07 20:51:55'),
(117,4,'login_success','user','4','197.157.145.87','Mozilla/5.0 (Linux; Android 13; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.6312.105 Mobile Safari/537.36','[]','2025-10-07 21:53:10'),
(118,4,'login_success','user','4','197.157.145.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-07 22:40:43'),
(119,4,'login_success','user','4','197.157.145.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-08 01:52:13'),
(120,4,'login_success','user','4','197.157.145.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-08 09:06:55'),
(121,4,'login_success','user','4','197.157.145.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-08 10:18:03'),
(122,4,'login_success','user','4','197.157.135.108','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-08 23:00:43'),
(123,4,'login_success','user','4','197.157.135.108','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-08 23:39:22'),
(124,NULL,'login_failed','user',NULL,'197.157.135.108','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','{\"email\":\"ellyj164@gmail.com\"}','2025-10-09 00:41:48'),
(125,NULL,'login_failed','user',NULL,'197.157.135.108','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','{\"email\":\"ellyj164@gmail.com\"}','2025-10-09 00:41:56'),
(126,NULL,'login_failed','user',NULL,'197.157.135.108','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','{\"email\":\"ellyj164@gmail.com\"}','2025-10-09 00:42:18'),
(127,4,'login_success','user','4','197.157.135.108','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-09 00:42:28'),
(128,4,'login_success','user','4','41.186.139.192','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-09 01:43:21'),
(129,4,'login_success','user','4','197.157.135.11','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-09 08:37:27'),
(130,4,'login_success','user','4','197.157.135.11','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-09 09:20:31'),
(131,NULL,'login_failed','user',NULL,'197.157.135.11','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','{\"email\":\"ellyj164@gmail.com\"}','2025-10-09 10:15:40'),
(132,4,'login_success','user','4','197.157.135.11','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-09 10:15:52'),
(133,4,'login_success','user','4','105.178.104.255','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-09 11:11:32'),
(134,NULL,'login_failed','user',NULL,'105.178.32.255','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','{\"email\":\"ellyj164@gmail.com\"}','2025-10-09 13:25:55'),
(135,4,'login_success','user','4','105.178.32.255','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-09 13:26:00'),
(136,NULL,'login_failed','user',NULL,'2401:4900:839b:85b7:211d:bd45:b205:7089','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','{\"email\":\"amarjit18000@gmail.com\"}','2025-10-09 15:18:24'),
(137,NULL,'login_failed','user',NULL,'2401:4900:839b:85b7:211d:bd45:b205:7089','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','{\"email\":\"amarjit18000@gmail.com\"}','2025-10-09 15:18:37'),
(138,NULL,'login_failed','user',NULL,'2401:4900:839b:85b7:211d:bd45:b205:7089','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','{\"email\":\"amarjit18000@gmail.com\"}','2025-10-09 15:21:51'),
(139,16,'login_failed_inactive','user','16','2401:4900:839b:85b7:211d:bd45:b205:7089','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-09 15:25:15'),
(140,16,'login_failed_inactive','user','16','27.59.68.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-09 15:26:55'),
(141,16,'password_reset_requested','user','16','2401:4900:839b:85b7:211d:bd45:b205:7089','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-09 15:34:40'),
(142,17,'login_success','user','17','2401:4900:839b:85b7:211d:bd45:b205:7089','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-09 15:42:04'),
(143,17,'login_success','user','17','2401:4900:839b:85b7:211d:bd45:b205:7089','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-09 15:53:48'),
(144,4,'login_success','user','4','105.178.32.255','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-09 16:28:28'),
(145,4,'login_success','user','4','105.178.32.255','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-09 18:12:36'),
(146,4,'login_success','user','4','197.157.135.155','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-09 18:33:59'),
(147,4,'login_success','user','4','197.157.135.155','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-09 20:05:18'),
(148,4,'login_success','user','4','197.157.165.125','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-09 21:26:00'),
(149,4,'login_success','user','4','197.157.165.125','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-09 23:24:26'),
(150,4,'login_success','user','4','197.157.165.125','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-10 00:37:22'),
(151,NULL,'login_failed','user',NULL,'197.157.155.94','Mozilla/5.0 (Linux; Android 13; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.6312.105 Mobile Safari/537.36','{\"email\":\"ellyj164@gmail.com\"}','2025-10-10 08:15:10'),
(152,4,'login_success','user','4','197.157.155.94','Mozilla/5.0 (Linux; Android 13; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.6312.105 Mobile Safari/537.36','[]','2025-10-10 08:15:19'),
(153,4,'login_success','user','4','197.157.155.94','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-10 08:24:55'),
(154,4,'login_success','user','4','197.157.155.94','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-10 08:53:59'),
(155,17,'login_success','user','17','2401:4900:a06e:7ded:9445:653:2877:4a56','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-10 09:10:41'),
(156,NULL,'login_failed','user',NULL,'88.210.3.196','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Avast/131.0.0.0','{\"email\":\"st.i.l.tbtsb@web.de\"}','2025-10-10 10:13:25'),
(157,4,'login_success','user','4','197.157.155.94','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-10 11:15:06'),
(158,4,'login_success','user','4','197.157.155.159','Mozilla/5.0 (Linux; Android 13; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.6312.105 Mobile Safari/537.36','[]','2025-10-10 15:59:43'),
(159,4,'login_success','user','4','105.178.104.166','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','[]','2025-10-10 20:04:22');
/*!40000 ALTER TABLE `audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `event` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `level` enum('info','warning','error','critical') NOT NULL DEFAULT 'info',
  `target_id` int(11) DEFAULT NULL,
  `target_type` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL,
  `method` varchar(10) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_event` (`event`),
  KEY `idx_category` (`category`),
  KEY `idx_level` (`level`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_target` (`target_type`,`target_id`),
  KEY `idx_audit_logs_composite` (`user_id`,`category`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `backups`
--

DROP TABLE IF EXISTS `backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `backups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_type` enum('database','files','full') NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `compression` enum('none','gzip','zip') NOT NULL DEFAULT 'gzip',
  `status` enum('in_progress','completed','failed') NOT NULL DEFAULT 'in_progress',
  `tables_included` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tables_included`)),
  `paths_included` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`paths_included`)),
  `checksum` varchar(64) DEFAULT NULL,
  `retention_days` int(11) NOT NULL DEFAULT 30,
  `delete_after` date NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_backup_type` (`backup_type`),
  KEY `idx_status` (`status`),
  KEY `idx_delete_after` (`delete_after`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `fk_backups_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backups`
--

LOCK TABLES `backups` WRITE;
/*!40000 ALTER TABLE `backups` DISABLE KEYS */;
/*!40000 ALTER TABLE `backups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `banners`
--

DROP TABLE IF EXISTS `banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `banners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slot_key` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `subtitle` varchar(500) DEFAULT NULL,
  `link_url` varchar(500) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `bg_image_path` varchar(500) DEFAULT NULL,
  `fg_image_path` varchar(500) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slot_key` (`slot_key`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banners`
--

LOCK TABLES `banners` WRITE;
/*!40000 ALTER TABLE `banners` DISABLE KEYS */;
INSERT INTO `banners` VALUES
(1,'shoes-banner','Buy artificial intelligence tools','buy this today','https://shop.fezamarket.com','https://fezalogistics.com','/uploads/banners/bnr_68e007f2a094a.webp','/uploads/banners/bnr_68e007f2a0ce7.webp',NULL,NULL,'2025-10-03 14:36:01','2025-10-03 17:29:22'),
(3,'trending-2','banner shop','shopd','https://fezamarket.com','https://fezamarket.com','/uploads/banners/bnr_68e0f41c8d5d8.jpg','/uploads/banners/bnr_68e008432f9d5.webp',NULL,NULL,'2025-10-03 17:30:43','2025-10-04 10:17:00');
/*!40000 ALTER TABLE `banners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bounces`
--

DROP TABLE IF EXISTS `bounces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bounces` (
  `bounce_id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` int(11) NOT NULL,
  `bounce_type` enum('hard','soft','complaint') NOT NULL,
  `reason` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `email_address` varchar(255) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `gateway_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gateway_data`)),
  PRIMARY KEY (`bounce_id`),
  KEY `idx_message_id` (`message_id`),
  KEY `idx_bounce_type` (`bounce_type`),
  KEY `idx_email_address` (`email_address`),
  KEY `idx_timestamp` (`timestamp`),
  KEY `idx_bounces_email_type` (`email_address`,`bounce_type`,`timestamp`),
  CONSTRAINT `fk_bounces_message` FOREIGN KEY (`message_id`) REFERENCES `comm_messages` (`message_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bounces`
--

LOCK TABLES `bounces` WRITE;
/*!40000 ALTER TABLE `bounces` DISABLE KEYS */;
/*!40000 ALTER TABLE `bounces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `brands` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `slug` varchar(160) NOT NULL,
  `description` text DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_brands_name` (`name`),
  UNIQUE KEY `uq_brands_slug` (`slug`),
  KEY `idx_brands_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=131 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `brands`
--

LOCK TABLES `brands` WRITE;
/*!40000 ALTER TABLE `brands` DISABLE KEYS */;
INSERT INTO `brands` VALUES
(1,'Generic Brand','generic-brand','Default brand placeholder',NULL,NULL,1,'2025-09-15 15:25:42','2025-09-15 15:25:42'),
(4,'Apple','apple','Technology and consumer electronics',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(5,'Samsung','samsung','Electronics and mobile devices',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(6,'Sony','sony','Electronics and entertainment',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(7,'LG','lg','Electronics and appliances',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(8,'Dell','dell','Computers and technology',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(9,'HP','hp','Computers and printers',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(10,'Lenovo','lenovo','Computers and tablets',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(11,'Asus','asus','Computer hardware',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(12,'Acer','acer','Computers and monitors',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(13,'Microsoft','microsoft','Software and hardware',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(14,'Google','google','Technology and services',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(15,'Amazon','amazon','Technology and services',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(16,'Panasonic','panasonic','Electronics and appliances',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(17,'Philips','philips','Electronics and healthcare',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(18,'Canon','canon','Cameras and printers',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(19,'Nikon','nikon','Cameras and optics',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(20,'JBL','jbl','Audio equipment',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(21,'Bose','bose','Audio equipment',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(22,'Beats','beats','Headphones and audio',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(23,'Logitech','logitech','Computer peripherals',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(24,'Razer','razer','Gaming peripherals',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(25,'Corsair','corsair','Gaming and PC components',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(26,'Intel','intel','Processors and technology',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(27,'AMD','amd','Processors and graphics',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(28,'NVIDIA','nvidia','Graphics cards',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(29,'Nike','nike','Athletic wear and footwear',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(30,'Adidas','adidas','Sportswear and footwear',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(31,'Puma','puma','Athletic apparel',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(32,'Under Armour','under-armour','Athletic apparel',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(33,'Reebok','reebok','Athletic footwear and apparel',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(34,'New Balance','new-balance','Athletic footwear',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(35,'Levi\'s','levis','Denim and casual wear',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(36,'Gap','gap','Casual clothing',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(37,'H&M','hm','Fashion retailer',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(38,'Zara','zara','Fashion apparel',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(39,'Uniqlo','uniqlo','Casual wear',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(40,'Ralph Lauren','ralph-lauren','Fashion and lifestyle',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(41,'Tommy Hilfiger','tommy-hilfiger','Fashion apparel',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(42,'Calvin Klein','calvin-klein','Fashion and accessories',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(43,'Gucci','gucci','Luxury fashion',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(44,'Prada','prada','Luxury fashion',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(45,'Louis Vuitton','louis-vuitton','Luxury goods',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(46,'Versace','versace','Luxury fashion',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(47,'Burberry','burberry','Luxury fashion',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(48,'Coach','coach','Leather goods and accessories',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(49,'Michael Kors','michael-kors','Fashion accessories',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(50,'L\'Oréal','loreal','Beauty and cosmetics',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(51,'Estée Lauder','estee-lauder','Cosmetics and skincare',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(52,'MAC','mac','Cosmetics',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(53,'Clinique','clinique','Skincare and cosmetics',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(54,'Lancôme','lancome','Luxury beauty',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(55,'Maybelline','maybelline','Cosmetics',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(56,'Revlon','revlon','Beauty products',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(57,'NYX','nyx','Cosmetics',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(58,'Dove','dove','Personal care',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(59,'Nivea','nivea','Skincare products',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(60,'Olay','olay','Skincare',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(61,'Neutrogena','neutrogena','Skincare products',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(62,'KitchenAid','kitchenaid','Kitchen appliances',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(63,'Cuisinart','cuisinart','Kitchen products',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(64,'Ninja','ninja','Kitchen appliances',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(65,'Instant Pot','instant-pot','Pressure cookers',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(66,'Dyson','dyson','Vacuum cleaners and appliances',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(67,'Roomba','roomba','Robot vacuums',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(68,'Shark','shark','Cleaning products',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(69,'Bissell','bissell','Cleaning equipment',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(70,'IKEA','ikea','Furniture and home goods',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(71,'Wayfair','wayfair','Home furnishings',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(72,'The North Face','the-north-face','Outdoor apparel',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(73,'Patagonia','patagonia','Outdoor clothing',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(74,'Columbia','columbia','Outdoor apparel',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(75,'REI','rei','Outdoor gear',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(76,'Yeti','yeti','Outdoor products',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(77,'GoPro','gopro','Action cameras',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(78,'Garmin','garmin','GPS and fitness devices',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(79,'Fitbit','fitbit','Fitness trackers',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(80,'Peloton','peloton','Fitness equipment',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(81,'Wilson','wilson','Sports equipment',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(82,'Spalding','spalding','Sports balls',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(83,'Titleist','titleist','Golf equipment',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(84,'Callaway','callaway','Golf equipment',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(85,'Bosch','bosch','Auto parts and tools',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(86,'Michelin','michelin','Tires',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(87,'Goodyear','goodyear','Tires',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(88,'Bridgestone','bridgestone','Tires',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(89,'Castrol','castrol','Motor oil',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(90,'Mobil','mobil','Motor oil',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(91,'Fisher-Price','fisher-price','Toys and baby products',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(92,'Lego','lego','Building toys',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(93,'Mattel','mattel','Toys',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(94,'Hasbro','hasbro','Toys and games',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(95,'Pampers','pampers','Baby care',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(96,'Huggies','huggies','Baby care',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(97,'Graco','graco','Baby products',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(98,'Chicco','chicco','Baby products',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(99,'Coca-Cola','coca-cola','Beverages',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(100,'Pepsi','pepsi','Beverages',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(101,'Nestlé','nestle','Food and beverages',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(102,'Kraft','kraft','Food products',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(103,'Kellogg\'s','kelloggs','Cereals and snacks',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(104,'General Mills','general-mills','Food products',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(105,'DeWalt','dewalt','Power tools',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(106,'Black & Decker','black-decker','Tools and appliances',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(107,'Makita','makita','Power tools',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(108,'Milwaukee','milwaukee','Power tools',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(109,'Stanley','stanley','Hand tools',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(110,'Craftsman','craftsman','Tools',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(111,'Pfizer','pfizer','Pharmaceuticals',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(112,'Johnson & Johnson','johnson-johnson','Healthcare products',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(113,'Bayer','bayer','Pharmaceuticals',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(114,'Abbott','abbott','Healthcare',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(115,'GNC','gnc','Nutritional supplements',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(116,'Optimum Nutrition','optimum-nutrition','Sports nutrition',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(117,'Staples','staples','Office supplies',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(118,'Sharpie','sharpie','Markers and writing instruments',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(119,'Post-it','post-it','Sticky notes',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(120,'Scotch','scotch','Adhesive products',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(121,'Moleskine','moleskine','Notebooks',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(122,'Parker','parker','Pens',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(123,'Rolex','rolex','Luxury watches',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(124,'Omega','omega','Luxury watches',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(125,'Seiko','seiko','Watches',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(126,'Casio','casio','Watches and electronics',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(127,'Fossil','fossil','Watches and accessories',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(128,'Tiffany & Co.','tiffany-co','Luxury jewelry',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(129,'Pandora','pandora','Jewelry',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18'),
(130,'Swarovski','swarovski','Crystal jewelry',NULL,NULL,1,'2025-10-04 10:22:18','2025-10-04 10:22:18');
/*!40000 ALTER TABLE `brands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyer_addresses`
--

DROP TABLE IF EXISTS `buyer_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) NOT NULL,
  `label` varchar(50) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `company` varchar(100) DEFAULT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(2) NOT NULL DEFAULT 'US',
  `phone` varchar(20) DEFAULT NULL,
  `is_default_billing` tinyint(1) NOT NULL DEFAULT 0,
  `is_default_shipping` tinyint(1) NOT NULL DEFAULT 0,
  `delivery_instructions` text DEFAULT NULL,
  `access_code` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_buyer` (`buyer_id`),
  KEY `idx_defaults` (`is_default_billing`,`is_default_shipping`),
  CONSTRAINT `fk_buyer_addresses_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyer_addresses`
--

LOCK TABLES `buyer_addresses` WRITE;
/*!40000 ALTER TABLE `buyer_addresses` DISABLE KEYS */;
/*!40000 ALTER TABLE `buyer_addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyer_consents`
--

DROP TABLE IF EXISTS `buyer_consents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_consents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) NOT NULL,
  `consent_type` enum('marketing','analytics','functional','necessary','data_processing','third_party_sharing') NOT NULL,
  `consent_given` tinyint(1) NOT NULL,
  `consent_method` enum('checkbox','opt_in','opt_out','implicit','legal_basis') NOT NULL,
  `legal_basis` varchar(255) DEFAULT NULL,
  `consent_text` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `withdrawn_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_buyer_type` (`buyer_id`,`consent_type`),
  KEY `idx_consent_given` (`consent_given`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_buyer_consents_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyer_consents`
--

LOCK TABLES `buyer_consents` WRITE;
/*!40000 ALTER TABLE `buyer_consents` DISABLE KEYS */;
/*!40000 ALTER TABLE `buyer_consents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyer_dispute_evidence`
--

DROP TABLE IF EXISTS `buyer_dispute_evidence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_dispute_evidence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dispute_id` int(11) NOT NULL,
  `submitted_by` int(11) NOT NULL,
  `evidence_type` enum('document','image','email','communication','tracking','receipt','screenshot') NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `description` text NOT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_dispute` (`dispute_id`),
  KEY `idx_submission_date` (`submission_date`),
  KEY `fk_buyer_dispute_evidence_user` (`submitted_by`),
  CONSTRAINT `fk_buyer_dispute_evidence_dispute` FOREIGN KEY (`dispute_id`) REFERENCES `buyer_disputes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_buyer_dispute_evidence_user` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyer_dispute_evidence`
--

LOCK TABLES `buyer_dispute_evidence` WRITE;
/*!40000 ALTER TABLE `buyer_dispute_evidence` DISABLE KEYS */;
/*!40000 ALTER TABLE `buyer_dispute_evidence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyer_dispute_messages`
--

DROP TABLE IF EXISTS `buyer_dispute_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_dispute_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dispute_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('buyer','seller','admin','system') NOT NULL,
  `message` text NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `is_internal` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_dispute` (`dispute_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `fk_buyer_dispute_messages_sender` (`sender_id`),
  CONSTRAINT `fk_buyer_dispute_messages_dispute` FOREIGN KEY (`dispute_id`) REFERENCES `buyer_disputes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_buyer_dispute_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyer_dispute_messages`
--

LOCK TABLES `buyer_dispute_messages` WRITE;
/*!40000 ALTER TABLE `buyer_dispute_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `buyer_dispute_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyer_disputes`
--

DROP TABLE IF EXISTS `buyer_disputes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_disputes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `vendor_id` int(11) NOT NULL,
  `dispute_number` varchar(50) NOT NULL,
  `type` enum('chargeback','refund_request','product_issue','service_issue','payment_issue','fraud') NOT NULL,
  `status` enum('open','under_review','awaiting_response','resolved','escalated','closed') NOT NULL DEFAULT 'open',
  `amount_disputed` decimal(10,2) NOT NULL,
  `claim_description` text NOT NULL,
  `desired_resolution` text DEFAULT NULL,
  `evidence_provided` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`evidence_provided`)),
  `resolution` text DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `deadline` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `dispute_number` (`dispute_number`),
  KEY `idx_buyer_status` (`buyer_id`,`status`),
  KEY `idx_order` (`order_id`),
  KEY `idx_vendor` (`vendor_id`),
  KEY `idx_deadline` (`deadline`),
  KEY `fk_buyer_disputes_resolver` (`resolved_by`),
  CONSTRAINT `fk_buyer_disputes_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_buyer_disputes_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_buyer_disputes_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_buyer_disputes_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyer_disputes`
--

LOCK TABLES `buyer_disputes` WRITE;
/*!40000 ALTER TABLE `buyer_disputes` DISABLE KEYS */;
/*!40000 ALTER TABLE `buyer_disputes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyer_dsr_requests`
--

DROP TABLE IF EXISTS `buyer_dsr_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_dsr_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) NOT NULL,
  `request_type` enum('access','portability','rectification','erasure','restrict_processing','object_processing') NOT NULL,
  `status` enum('received','in_progress','completed','rejected','cancelled') NOT NULL DEFAULT 'received',
  `request_details` text DEFAULT NULL,
  `verification_method` enum('email','phone','document','in_person') DEFAULT NULL,
  `verification_completed` tinyint(1) NOT NULL DEFAULT 0,
  `verification_date` timestamp NULL DEFAULT NULL,
  `completion_date` timestamp NULL DEFAULT NULL,
  `response_method` enum('email','download','mail','in_person') DEFAULT NULL,
  `response_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response_data`)),
  `rejection_reason` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_buyer_status` (`buyer_id`,`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_processed_by` (`processed_by`),
  CONSTRAINT `fk_buyer_dsr_requests_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_buyer_dsr_requests_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyer_dsr_requests`
--

LOCK TABLES `buyer_dsr_requests` WRITE;
/*!40000 ALTER TABLE `buyer_dsr_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `buyer_dsr_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyer_kpis`
--

DROP TABLE IF EXISTS `buyer_kpis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_kpis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) NOT NULL,
  `metric_date` date NOT NULL,
  `orders_count` int(11) NOT NULL DEFAULT 0,
  `total_spent` decimal(15,2) NOT NULL DEFAULT 0.00,
  `avg_order_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `returns_count` int(11) NOT NULL DEFAULT 0,
  `loyalty_points_earned` int(11) NOT NULL DEFAULT 0,
  `loyalty_points_spent` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `buyer_date` (`buyer_id`,`metric_date`),
  KEY `idx_metric_date` (`metric_date`),
  CONSTRAINT `fk_buyer_kpis_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyer_kpis`
--

LOCK TABLES `buyer_kpis` WRITE;
/*!40000 ALTER TABLE `buyer_kpis` DISABLE KEYS */;
/*!40000 ALTER TABLE `buyer_kpis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyer_loyalty_accounts`
--

DROP TABLE IF EXISTS `buyer_loyalty_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_loyalty_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) NOT NULL,
  `program_name` varchar(100) NOT NULL DEFAULT 'main',
  `current_points` int(11) NOT NULL DEFAULT 0,
  `lifetime_points` int(11) NOT NULL DEFAULT 0,
  `tier` enum('bronze','silver','gold','platinum','diamond') NOT NULL DEFAULT 'bronze',
  `tier_progress` decimal(5,2) NOT NULL DEFAULT 0.00,
  `next_tier_threshold` int(11) DEFAULT NULL,
  `tier_expiry` timestamp NULL DEFAULT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `buyer_program` (`buyer_id`,`program_name`),
  KEY `idx_tier` (`tier`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_buyer_loyalty_accounts_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyer_loyalty_accounts`
--

LOCK TABLES `buyer_loyalty_accounts` WRITE;
/*!40000 ALTER TABLE `buyer_loyalty_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `buyer_loyalty_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyer_loyalty_ledger`
--

DROP TABLE IF EXISTS `buyer_loyalty_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_loyalty_ledger` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loyalty_account_id` int(11) NOT NULL,
  `transaction_type` enum('earned','redeemed','expired','adjusted','bonus','refund') NOT NULL,
  `points` int(11) NOT NULL,
  `balance_after` int(11) NOT NULL,
  `reference_type` enum('order','review','referral','birthday','bonus','redemption','expiration','adjustment') DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `description` varchar(500) NOT NULL,
  `expiry_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_loyalty_account` (`loyalty_account_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_expiry_date` (`expiry_date`),
  KEY `idx_reference` (`reference_type`,`reference_id`),
  CONSTRAINT `fk_buyer_loyalty_ledger_account` FOREIGN KEY (`loyalty_account_id`) REFERENCES `buyer_loyalty_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyer_loyalty_ledger`
--

LOCK TABLES `buyer_loyalty_ledger` WRITE;
/*!40000 ALTER TABLE `buyer_loyalty_ledger` DISABLE KEYS */;
/*!40000 ALTER TABLE `buyer_loyalty_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyer_messages`
--

DROP TABLE IF EXISTS `buyer_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) NOT NULL,
  `conversation_id` varchar(50) NOT NULL,
  `sender_type` enum('buyer','seller','admin','system') NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `message_type` enum('text','image','file','system') NOT NULL DEFAULT 'text',
  `content` text NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_buyer_conversation` (`buyer_id`,`conversation_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_read_at` (`read_at`),
  KEY `fk_buyer_messages_sender` (`sender_id`),
  CONSTRAINT `fk_buyer_messages_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_buyer_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyer_messages`
--

LOCK TABLES `buyer_messages` WRITE;
/*!40000 ALTER TABLE `buyer_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `buyer_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyer_notifications`
--

DROP TABLE IF EXISTS `buyer_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) NOT NULL,
  `type` enum('order','shipping','delivery','promotion','wishlist','loyalty','system') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `read_at` timestamp NULL DEFAULT NULL,
  `action_url` varchar(500) DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_buyer_read` (`buyer_id`,`read_at`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_buyer_notifications_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyer_notifications`
--

LOCK TABLES `buyer_notifications` WRITE;
/*!40000 ALTER TABLE `buyer_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `buyer_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyer_orders`
--

DROP TABLE IF EXISTS `buyer_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled','refunded') NOT NULL,
  `tracking_preference` enum('email','sms','push','all') NOT NULL DEFAULT 'email',
  `delivery_instructions` text DEFAULT NULL,
  `special_requests` text DEFAULT NULL,
  `rating` tinyint(1) DEFAULT NULL,
  `review` text DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `can_cancel` tinyint(1) NOT NULL DEFAULT 1,
  `can_return` tinyint(1) NOT NULL DEFAULT 1,
  `return_deadline` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `buyer_order` (`buyer_id`,`order_id`),
  KEY `idx_status` (`status`),
  KEY `idx_rating` (`rating`),
  KEY `fk_buyer_orders_order` (`order_id`),
  CONSTRAINT `fk_buyer_orders_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_buyer_orders_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyer_orders`
--

LOCK TABLES `buyer_orders` WRITE;
/*!40000 ALTER TABLE `buyer_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `buyer_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyer_payment_methods`
--

DROP TABLE IF EXISTS `buyer_payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_payment_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) NOT NULL,
  `type` enum('card','paypal','bank_account','crypto','mobile_money','buy_now_pay_later') NOT NULL,
  `provider` varchar(50) NOT NULL,
  `last_four` varchar(4) DEFAULT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `exp_month` tinyint(2) DEFAULT NULL,
  `exp_year` smallint(4) DEFAULT NULL,
  `billing_address_id` int(11) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `token` varchar(255) NOT NULL,
  `fingerprint` varchar(100) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_buyer` (`buyer_id`),
  KEY `idx_is_default` (`is_default`),
  KEY `idx_fingerprint` (`fingerprint`),
  KEY `fk_buyer_payment_methods_address` (`billing_address_id`),
  CONSTRAINT `fk_buyer_payment_methods_address` FOREIGN KEY (`billing_address_id`) REFERENCES `buyer_addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_buyer_payment_methods_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyer_payment_methods`
--

LOCK TABLES `buyer_payment_methods` WRITE;
/*!40000 ALTER TABLE `buyer_payment_methods` DISABLE KEYS */;
/*!40000 ALTER TABLE `buyer_payment_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyer_preferences`
--

DROP TABLE IF EXISTS `buyer_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `preference_key` varchar(100) NOT NULL,
  `preference_value` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `buyer_category_key` (`buyer_id`,`category`,`preference_key`),
  CONSTRAINT `fk_buyer_preferences_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyer_preferences`
--

LOCK TABLES `buyer_preferences` WRITE;
/*!40000 ALTER TABLE `buyer_preferences` DISABLE KEYS */;
/*!40000 ALTER TABLE `buyer_preferences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyer_profiles`
--

DROP TABLE IF EXISTS `buyer_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) NOT NULL,
  `display_name` varchar(100) DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other','prefer_not_to_say') DEFAULT NULL,
  `phone_verified` tinyint(1) NOT NULL DEFAULT 0,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `privacy_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`privacy_settings`)),
  `notification_preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notification_preferences`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `buyer_id` (`buyer_id`),
  CONSTRAINT `fk_buyer_profiles_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyer_profiles`
--

LOCK TABLES `buyer_profiles` WRITE;
/*!40000 ALTER TABLE `buyer_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `buyer_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyer_rma_messages`
--

DROP TABLE IF EXISTS `buyer_rma_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_rma_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rma_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('buyer','seller','admin','system') NOT NULL,
  `message` text NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rma` (`rma_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `fk_buyer_rma_messages_sender` (`sender_id`),
  CONSTRAINT `fk_buyer_rma_messages_rma` FOREIGN KEY (`rma_id`) REFERENCES `buyer_rmas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_buyer_rma_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyer_rma_messages`
--

LOCK TABLES `buyer_rma_messages` WRITE;
/*!40000 ALTER TABLE `buyer_rma_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `buyer_rma_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyer_rmas`
--

DROP TABLE IF EXISTS `buyer_rmas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_rmas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `rma_number` varchar(50) NOT NULL,
  `reason` enum('defective','wrong_item','damaged','not_as_described','change_of_mind','warranty') NOT NULL,
  `status` enum('requested','approved','rejected','shipped','received','refunded','completed') NOT NULL DEFAULT 'requested',
  `return_value` decimal(10,2) NOT NULL,
  `refund_amount` decimal(10,2) DEFAULT NULL,
  `return_tracking` varchar(100) DEFAULT NULL,
  `return_label_url` varchar(500) DEFAULT NULL,
  `customer_notes` text DEFAULT NULL,
  `photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`photos`)),
  `approved_at` timestamp NULL DEFAULT NULL,
  `shipped_at` timestamp NULL DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `refunded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `rma_number` (`rma_number`),
  KEY `idx_buyer_status` (`buyer_id`,`status`),
  KEY `idx_order` (`order_id`),
  KEY `idx_vendor` (`vendor_id`),
  CONSTRAINT `fk_buyer_rmas_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_buyer_rmas_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_buyer_rmas_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyer_rmas`
--

LOCK TABLES `buyer_rmas` WRITE;
/*!40000 ALTER TABLE `buyer_rmas` DISABLE KEYS */;
/*!40000 ALTER TABLE `buyer_rmas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyer_subscriptions`
--

DROP TABLE IF EXISTS `buyer_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) NOT NULL,
  `subscription_type` enum('newsletter','product_updates','price_alerts','promotions','order_updates','security_alerts') NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `status` enum('active','paused','unsubscribed') NOT NULL DEFAULT 'active',
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferences`)),
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `buyer_type_category_vendor` (`buyer_id`,`subscription_type`,`category`,`vendor_id`),
  KEY `idx_status` (`status`),
  KEY `fk_buyer_subscriptions_vendor` (`vendor_id`),
  CONSTRAINT `fk_buyer_subscriptions_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_buyer_subscriptions_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyer_subscriptions`
--

LOCK TABLES `buyer_subscriptions` WRITE;
/*!40000 ALTER TABLE `buyer_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `buyer_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyer_ticket_replies`
--

DROP TABLE IF EXISTS `buyer_ticket_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_ticket_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('buyer','agent','system') NOT NULL,
  `message` text NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `is_internal` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ticket` (`ticket_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `fk_buyer_ticket_replies_sender` (`sender_id`),
  CONSTRAINT `fk_buyer_ticket_replies_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_buyer_ticket_replies_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `buyer_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyer_ticket_replies`
--

LOCK TABLES `buyer_ticket_replies` WRITE;
/*!40000 ALTER TABLE `buyer_ticket_replies` DISABLE KEYS */;
/*!40000 ALTER TABLE `buyer_ticket_replies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyer_tickets`
--

DROP TABLE IF EXISTS `buyer_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) NOT NULL,
  `ticket_number` varchar(50) NOT NULL,
  `category` enum('order_issue','product_issue','payment_issue','account_issue','technical_issue','general_inquiry') NOT NULL,
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `status` enum('open','in_progress','waiting_customer','resolved','closed') NOT NULL DEFAULT 'open',
  `subject` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `assigned_to` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `first_response_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `satisfaction_rating` tinyint(1) DEFAULT NULL,
  `satisfaction_comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_number` (`ticket_number`),
  KEY `idx_buyer_status` (`buyer_id`,`status`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_category` (`category`),
  KEY `fk_buyer_tickets_order` (`order_id`),
  KEY `fk_buyer_tickets_product` (`product_id`),
  CONSTRAINT `fk_buyer_tickets_assignee` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_buyer_tickets_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_buyer_tickets_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_buyer_tickets_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyer_tickets`
--

LOCK TABLES `buyer_tickets` WRITE;
/*!40000 ALTER TABLE `buyer_tickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `buyer_tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyer_tracking`
--

DROP TABLE IF EXISTS `buyer_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_order_id` int(11) NOT NULL,
  `tracking_number` varchar(100) NOT NULL,
  `carrier` varchar(100) NOT NULL,
  `status` enum('label_created','picked_up','in_transit','out_for_delivery','delivered','exception','returned') NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `estimated_delivery` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `tracking_events` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tracking_events`)),
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_buyer_order` (`buyer_order_id`),
  KEY `idx_tracking_number` (`tracking_number`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_buyer_tracking_buyer_order` FOREIGN KEY (`buyer_order_id`) REFERENCES `buyer_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyer_tracking`
--

LOCK TABLES `buyer_tracking` WRITE;
/*!40000 ALTER TABLE `buyer_tracking` DISABLE KEYS */;
/*!40000 ALTER TABLE `buyer_tracking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyer_wallet_entries`
--

DROP TABLE IF EXISTS `buyer_wallet_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_wallet_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wallet_id` int(11) NOT NULL,
  `transaction_type` enum('credit','debit','refund','cashback','loyalty_conversion','adjustment') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `balance_after` decimal(15,2) NOT NULL,
  `reference_type` enum('order','refund','cashback','loyalty','promotion','adjustment') DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `description` varchar(500) NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_wallet` (`wallet_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_reference` (`reference_type`,`reference_id`),
  CONSTRAINT `fk_buyer_wallet_entries_wallet` FOREIGN KEY (`wallet_id`) REFERENCES `buyer_wallets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyer_wallet_entries`
--

LOCK TABLES `buyer_wallet_entries` WRITE;
/*!40000 ALTER TABLE `buyer_wallet_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `buyer_wallet_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyer_wallets`
--

DROP TABLE IF EXISTS `buyer_wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_wallets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `status` enum('active','suspended','frozen') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `buyer_currency` (`buyer_id`,`currency`),
  CONSTRAINT `fk_buyer_wallets_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyer_wallets`
--

LOCK TABLES `buyer_wallets` WRITE;
/*!40000 ALTER TABLE `buyer_wallets` DISABLE KEYS */;
/*!40000 ALTER TABLE `buyer_wallets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyer_wishlist`
--

DROP TABLE IF EXISTS `buyer_wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_wishlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variant_info`)),
  `list_name` varchar(100) NOT NULL DEFAULT 'default',
  `notes` text DEFAULT NULL,
  `privacy` enum('private','public','friends') NOT NULL DEFAULT 'private',
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `price_alert_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `target_price` decimal(10,2) DEFAULT NULL,
  `stock_alert_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `buyer_product_list` (`buyer_id`,`product_id`,`list_name`),
  KEY `idx_list_name` (`list_name`),
  KEY `idx_privacy` (`privacy`),
  KEY `idx_price_alert` (`price_alert_enabled`,`target_price`),
  KEY `fk_buyer_wishlist_product` (`product_id`),
  CONSTRAINT `fk_buyer_wishlist_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_buyer_wishlist_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyer_wishlist`
--

LOCK TABLES `buyer_wishlist` WRITE;
/*!40000 ALTER TABLE `buyer_wishlist` DISABLE KEYS */;
/*!40000 ALTER TABLE `buyer_wishlist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyer_wishlist_alerts`
--

DROP TABLE IF EXISTS `buyer_wishlist_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_wishlist_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wishlist_id` int(11) NOT NULL,
  `alert_type` enum('price_drop','back_in_stock','sale','discontinued') NOT NULL,
  `triggered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `old_value` varchar(255) DEFAULT NULL,
  `new_value` varchar(255) DEFAULT NULL,
  `notification_sent` tinyint(1) NOT NULL DEFAULT 0,
  `notification_sent_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_wishlist` (`wishlist_id`),
  KEY `idx_triggered_at` (`triggered_at`),
  CONSTRAINT `fk_buyer_wishlist_alerts_wishlist` FOREIGN KEY (`wishlist_id`) REFERENCES `buyer_wishlist` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyer_wishlist_alerts`
--

LOCK TABLES `buyer_wishlist_alerts` WRITE;
/*!40000 ALTER TABLE `buyer_wishlist_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `buyer_wishlist_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyers`
--

DROP TABLE IF EXISTS `buyers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `tier` enum('bronze','silver','gold','platinum','diamond') NOT NULL DEFAULT 'bronze',
  `total_spent` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_orders` int(11) NOT NULL DEFAULT 0,
  `loyalty_points` int(11) NOT NULL DEFAULT 0,
  `preferred_language` varchar(5) DEFAULT 'en',
  `preferred_currency` varchar(3) DEFAULT 'USD',
  `marketing_consent` tinyint(1) NOT NULL DEFAULT 0,
  `data_processing_consent` tinyint(1) NOT NULL DEFAULT 0,
  `last_activity` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_tier` (`tier`),
  KEY `idx_last_activity` (`last_activity`),
  CONSTRAINT `fk_buyers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyers`
--

LOCK TABLES `buyers` WRITE;
/*!40000 ALTER TABLE `buyers` DISABLE KEYS */;
/*!40000 ALTER TABLE `buyers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campaign_assets`
--

DROP TABLE IF EXISTS `campaign_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `asset_type` enum('image','video','html','text','banner') NOT NULL,
  `asset_name` varchar(255) NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `click_url` varchar(500) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `a_b_test_variant` varchar(50) DEFAULT NULL,
  `performance_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`performance_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_campaign_id` (`campaign_id`),
  KEY `idx_asset_type` (`asset_type`),
  KEY `idx_is_primary` (`is_primary`),
  CONSTRAINT `fk_campaign_assets_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campaign_assets`
--

LOCK TABLES `campaign_assets` WRITE;
/*!40000 ALTER TABLE `campaign_assets` DISABLE KEYS */;
/*!40000 ALTER TABLE `campaign_assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campaign_messages`
--

DROP TABLE IF EXISTS `campaign_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `send_time` timestamp NULL DEFAULT NULL,
  `status` enum('scheduled','sent','failed','cancelled') NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_campaign_message` (`campaign_id`,`message_id`),
  KEY `idx_campaign_id` (`campaign_id`),
  KEY `idx_message_id` (`message_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_campaign_messages_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_campaign_messages_message` FOREIGN KEY (`message_id`) REFERENCES `comm_messages` (`message_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campaign_messages`
--

LOCK TABLES `campaign_messages` WRITE;
/*!40000 ALTER TABLE `campaign_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `campaign_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campaign_products`
--

DROP TABLE IF EXISTS `campaign_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_campaign_product_vendor` (`campaign_id`,`product_id`,`vendor_id`),
  KEY `idx_campaign_id` (`campaign_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_joined_at` (`joined_at`),
  CONSTRAINT `fk_campaign_products_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `marketing_campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_campaign_products_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_campaign_products_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campaign_products`
--

LOCK TABLES `campaign_products` WRITE;
/*!40000 ALTER TABLE `campaign_products` DISABLE KEYS */;
/*!40000 ALTER TABLE `campaign_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campaign_recipients`
--

DROP TABLE IF EXISTS `campaign_recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_recipients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('sent','failed') NOT NULL DEFAULT 'sent',
  `opened_at` datetime DEFAULT NULL,
  `clicked_at` datetime DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_user` (`campaign_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `campaign_recipients_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `marketing_campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `campaign_recipients_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campaign_recipients`
--

LOCK TABLES `campaign_recipients` WRITE;
/*!40000 ALTER TABLE `campaign_recipients` DISABLE KEYS */;
/*!40000 ALTER TABLE `campaign_recipients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campaign_stats`
--

DROP TABLE IF EXISTS `campaign_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `metric_date` date NOT NULL,
  `impressions` int(11) NOT NULL DEFAULT 0,
  `clicks` int(11) NOT NULL DEFAULT 0,
  `conversions` int(11) NOT NULL DEFAULT 0,
  `revenue` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reach` int(11) NOT NULL DEFAULT 0,
  `engagement_rate` decimal(5,4) NOT NULL DEFAULT 0.0000,
  `click_through_rate` decimal(5,4) NOT NULL DEFAULT 0.0000,
  `conversion_rate` decimal(5,4) NOT NULL DEFAULT 0.0000,
  `return_on_ad_spend` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_campaign_date` (`campaign_id`,`metric_date`),
  KEY `idx_metric_date` (`metric_date`),
  CONSTRAINT `fk_campaign_stats_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campaign_stats`
--

LOCK TABLES `campaign_stats` WRITE;
/*!40000 ALTER TABLE `campaign_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `campaign_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campaign_targets`
--

DROP TABLE IF EXISTS `campaign_targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_targets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `target_type` enum('user','segment','category','product','location') NOT NULL,
  `target_criteria` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`target_criteria`)),
  `estimated_reach` int(11) DEFAULT NULL,
  `actual_reach` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_campaign_id` (`campaign_id`),
  KEY `idx_target_type` (`target_type`),
  CONSTRAINT `fk_campaign_targets_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campaign_targets`
--

LOCK TABLES `campaign_targets` WRITE;
/*!40000 ALTER TABLE `campaign_targets` DISABLE KEYS */;
/*!40000 ALTER TABLE `campaign_targets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campaigns`
--

DROP TABLE IF EXISTS `campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `campaign_type` enum('email','social','banner','flash_sale','push','sms','affiliate') NOT NULL,
  `status` enum('draft','scheduled','active','paused','completed','cancelled') NOT NULL DEFAULT 'draft',
  `budget` decimal(10,2) DEFAULT NULL,
  `spent_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `target_audience` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_audience`)),
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `automation_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`automation_rules`)),
  `tracking_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tracking_data`)),
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_campaign_type` (`campaign_type`),
  KEY `idx_status` (`status`),
  KEY `idx_start_date` (`start_date`),
  KEY `idx_end_date` (`end_date`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_campaigns_type_status_dates` (`campaign_type`,`status`,`start_date`,`end_date`),
  CONSTRAINT `fk_campaigns_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campaigns`
--

LOCK TABLES `campaigns` WRITE;
/*!40000 ALTER TABLE `campaigns` DISABLE KEYS */;
/*!40000 ALTER TABLE `campaigns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `canned_responses`
--

DROP TABLE IF EXISTS `canned_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `canned_responses` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_canned_responses_category` (`category`),
  KEY `idx_canned_responses_active` (`is_active`),
  KEY `idx_canned_responses_creator` (`created_by`),
  CONSTRAINT `fk_canned_responses_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `canned_responses`
--

LOCK TABLES `canned_responses` WRITE;
/*!40000 ALTER TABLE `canned_responses` DISABLE KEYS */;
/*!40000 ALTER TABLE `canned_responses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `session_id` varchar(128) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_product` (`user_id`,`product_id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `fk_cart_product` (`product_id`),
  CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart`
--

LOCK TABLES `cart` WRITE;
/*!40000 ALTER TABLE `cart` DISABLE KEYS */;
INSERT INTO `cart` VALUES
(24,13,5,1,1700.00,NULL,NULL,'2025-10-07 17:30:04','2025-10-07 17:30:04'),
(30,4,6,2,5600.00,NULL,NULL,'2025-10-07 19:53:50','2025-10-07 23:52:30'),
(32,4,5,2,1700.00,NULL,NULL,'2025-10-07 20:44:45','2025-10-10 06:15:37');
/*!40000 ALTER TABLE `cart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `slug` varchar(120) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_status` (`status`),
  KEY `idx_sort_order` (`sort_order`),
  CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1504 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES
(1,'Electronics','Electronic devices and accessories',NULL,'electronics',NULL,1,1,'active','','','2025-09-14 19:54:24','2025-09-14 20:04:01'),
(2,'Clothing & Fashion','Apparel and fashion accessories',NULL,'clothing-fashion',NULL,2,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(3,'Home & Garden','Home improvement and garden supplies',NULL,'home-garden',NULL,3,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(4,'Sports & Outdoors','Sports equipment and outdoor gear',NULL,'sports-outdoors',NULL,4,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(5,'Books & Media','Books, movies, music and digital media',NULL,'books-media',NULL,5,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(6,'Health & Beauty','Health products and beauty supplies',NULL,'health-beauty',NULL,6,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(7,'Toys & Games','Toys, games and hobby supplies',NULL,'toys-games',NULL,7,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(8,'Automotive','Car parts and automotive accessories',NULL,'automotive',NULL,8,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(9,'Food & Beverages','Food items and beverages',NULL,'food-beverages',NULL,9,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(10,'Baby & Kids','Baby products and children supplies',NULL,'baby-kids',NULL,10,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(11,'Office & Business','Office supplies and business equipment',NULL,'office-business',NULL,11,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(12,'Pet Supplies','Pet food, toys and accessories',NULL,'pet-supplies',NULL,12,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(13,'Arts & Crafts','Art supplies and crafting materials',NULL,'arts-crafts',NULL,13,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(14,'Travel & Luggage','Travel accessories and luggage',NULL,'travel-luggage',NULL,14,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(15,'Music & Instruments','Musical instruments and equipment',NULL,'music-instruments',NULL,15,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(101,'Smartphones','Mobile phones and smartphones',1,'smartphones',NULL,1,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(102,'Laptops & Computers','Laptops, desktops and computer parts',1,'laptops-computers',NULL,2,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(103,'Tablets','Tablet computers and e-readers',1,'tablets',NULL,3,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(104,'TV & Audio','Televisions and audio equipment',1,'tv-audio',NULL,4,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(105,'Cameras','Digital cameras and photography equipment',1,'cameras',NULL,5,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(106,'Gaming','Video game consoles and accessories',1,'gaming',NULL,6,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(107,'Wearable Tech','Smartwatches and fitness trackers',1,'wearable-tech',NULL,7,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(108,'Home Electronics','Small appliances and home tech',1,'home-electronics',NULL,8,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(201,'Men\'s Clothing','Clothing for men',2,'mens-clothing',NULL,1,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(202,'Women\'s Clothing','Clothing for women',2,'womens-clothing',NULL,2,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(203,'Shoes','Footwear for all occasions',2,'shoes',NULL,3,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(204,'Accessories','Fashion accessories and jewelry',2,'accessories',NULL,4,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(205,'Bags & Luggage','Handbags, backpacks and travel bags',2,'bags-luggage',NULL,5,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(206,'Watches','Wristwatches and timepieces',2,'watches',NULL,6,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(207,'Sunglasses','Sunglasses and eyewear',2,'sunglasses',NULL,7,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(301,'Furniture','Home and office furniture',3,'furniture',NULL,1,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(302,'Kitchen & Dining','Kitchen appliances and dining ware',3,'kitchen-dining',NULL,2,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(303,'Bedding & Bath','Bedding, towels and bathroom accessories',3,'bedding-bath',NULL,3,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(304,'Home Decor','Decorative items and artwork',3,'home-decor',NULL,4,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(305,'Garden & Outdoor','Gardening tools and outdoor furniture',3,'garden-outdoor',NULL,5,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(306,'Lighting','Lamps and lighting fixtures',3,'lighting',NULL,6,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(307,'Storage & Organization','Storage solutions and organizers',3,'storage-organization',NULL,7,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(401,'Fitness Equipment','Exercise and fitness gear',4,'fitness-equipment',NULL,1,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(402,'Team Sports','Equipment for team sports',4,'team-sports',NULL,2,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(403,'Outdoor Recreation','Camping, hiking and outdoor gear',4,'outdoor-recreation',NULL,3,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(404,'Water Sports','Swimming and water activity gear',4,'water-sports',NULL,4,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(405,'Winter Sports','Skiing, snowboarding and winter gear',4,'winter-sports',NULL,5,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(406,'Athletic Wear','Sports clothing and footwear',4,'athletic-wear',NULL,6,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(501,'Books','Physical and digital books',5,'books',NULL,1,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(502,'Movies & TV','DVDs, Blu-rays and digital movies',5,'movies-tv',NULL,2,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(503,'Music','CDs, vinyl and digital music',5,'music',NULL,3,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(504,'Magazines','Magazine subscriptions and back issues',5,'magazines',NULL,4,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(505,'Video Games','Game software and digital downloads',5,'video-games',NULL,5,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(601,'Skincare','Facial care and skin treatments',6,'skincare',NULL,1,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(602,'Makeup','Cosmetics and beauty products',6,'makeup',NULL,2,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(603,'Hair Care','Shampoo, conditioner and styling products',6,'hair-care',NULL,3,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(604,'Personal Care','Personal hygiene and grooming products',6,'personal-care',NULL,4,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(605,'Vitamins & Supplements','Health supplements and vitamins',6,'vitamins-supplements',NULL,5,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(606,'Fragrances','Perfumes and colognes',6,'fragrances',NULL,6,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(701,'Action Figures','Action figures and collectibles',7,'action-figures',NULL,1,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(702,'Board Games','Board games and card games',7,'board-games',NULL,2,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(703,'Building Sets','LEGO and construction toys',7,'building-sets',NULL,3,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(704,'Dolls & Accessories','Dolls and doll accessories',7,'dolls-accessories',NULL,4,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(705,'Educational Toys','Learning and educational toys',7,'educational-toys',NULL,5,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(706,'Outdoor Toys','Outdoor play equipment',7,'outdoor-toys',NULL,6,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(801,'Car Parts','Replacement parts and accessories',8,'car-parts',NULL,1,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(802,'Car Electronics','GPS, stereos and car electronics',8,'car-electronics',NULL,2,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(803,'Motorcycles','Motorcycle parts and accessories',8,'motorcycles',NULL,3,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(901,'Snacks','Snack foods and treats',9,'snacks',NULL,1,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(902,'Beverages','Drinks and beverages',9,'beverages',NULL,2,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(903,'Gourmet Foods','Specialty and gourmet food items',9,'gourmet-foods',NULL,3,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(1001,'Baby Clothing','Clothing for babies and toddlers',10,'baby-clothing',NULL,1,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(1002,'Baby Gear','Strollers, car seats and baby equipment',10,'baby-gear',NULL,2,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(1003,'Baby Feeding','Bottles, high chairs and feeding supplies',10,'baby-feeding',NULL,3,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(1101,'Office Supplies','Pens, paper and office essentials',11,'office-supplies',NULL,1,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(1102,'Office Furniture','Desks, chairs and office furniture',11,'office-furniture',NULL,2,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(1201,'Dog Supplies','Food, toys and accessories for dogs',12,'dog-supplies',NULL,1,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(1202,'Cat Supplies','Food, toys and accessories for cats',12,'cat-supplies',NULL,2,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(1203,'Small Pet Supplies','Supplies for birds, fish and small pets',12,'small-pet-supplies',NULL,3,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(1301,'Painting Supplies','Paints, brushes and canvases',13,'painting-supplies',NULL,1,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(1302,'Crafting Materials','Fabric, yarn and crafting supplies',13,'crafting-materials',NULL,2,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(1401,'Suitcases','Travel suitcases and carry-ons',14,'suitcases',NULL,1,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(1402,'Travel Accessories','Travel pillows, adapters and accessories',14,'travel-accessories',NULL,2,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(1501,'Guitars','Acoustic and electric guitars',15,'guitars',NULL,1,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(1502,'Keyboards & Pianos','Digital pianos and keyboards',15,'keyboards-pianos',NULL,2,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24'),
(1503,'Drums','Drum sets and percussion',15,'drums',NULL,3,1,'active',NULL,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `category_attributes`
--

DROP TABLE IF EXISTS `category_attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_attributes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `attribute_name` varchar(100) NOT NULL,
  `attribute_type` enum('text','number','boolean','select','multiselect','date') NOT NULL,
  `attribute_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attribute_options`)),
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `is_filterable` tinyint(1) NOT NULL DEFAULT 0,
  `is_searchable` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_attribute_name` (`attribute_name`),
  KEY `idx_is_filterable` (`is_filterable`),
  KEY `idx_sort_order` (`sort_order`),
  CONSTRAINT `fk_category_attributes_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category_attributes`
--

LOCK TABLES `category_attributes` WRITE;
/*!40000 ALTER TABLE `category_attributes` DISABLE KEYS */;
/*!40000 ALTER TABLE `category_attributes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stream_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `message` text NOT NULL,
  `message_type` enum('text','emoji','system','product_link','moderation') NOT NULL DEFAULT 'text',
  `is_highlighted` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_reason` varchar(255) DEFAULT NULL,
  `parent_message_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_stream_id` (`stream_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_message_type` (`message_type`),
  KEY `idx_is_deleted` (`is_deleted`),
  KEY `idx_parent_message_id` (`parent_message_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `fk_chat_messages_moderator` (`deleted_by`),
  CONSTRAINT `fk_chat_messages_moderator` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_chat_messages_stream` FOREIGN KEY (`stream_id`) REFERENCES `live_streams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_chat_messages_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_messages`
--

LOCK TABLES `chat_messages` WRITE;
/*!40000 ALTER TABLE `chat_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_metadata`
--

DROP TABLE IF EXISTS `chat_metadata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_metadata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chat_id` int(11) NOT NULL,
  `meta_key` varchar(100) NOT NULL,
  `meta_value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_chat_id` (`chat_id`),
  KEY `idx_meta_key` (`meta_key`),
  CONSTRAINT `chat_metadata_ibfk_1` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_metadata`
--

LOCK TABLES `chat_metadata` WRITE;
/*!40000 ALTER TABLE `chat_metadata` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_metadata` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chats`
--

DROP TABLE IF EXISTS `chats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` enum('active','closed','archived') DEFAULT 'active',
  `type` enum('support','ai','sales') DEFAULT 'support',
  `assigned_agent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `closed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`type`),
  KEY `idx_created_at` (`created_at`),
  KEY `assigned_agent_id` (`assigned_agent_id`),
  CONSTRAINT `chats_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chats_ibfk_2` FOREIGN KEY (`assigned_agent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chats`
--

LOCK TABLES `chats` WRITE;
/*!40000 ALTER TABLE `chats` DISABLE KEYS */;
/*!40000 ALTER TABLE `chats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_media`
--

DROP TABLE IF EXISTS `cms_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_url` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `media_type` enum('image','video','audio','document','other') NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `uploaded_by` int(11) NOT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_filename` (`filename`),
  KEY `idx_media_type` (`media_type`),
  KEY `idx_uploaded_by` (`uploaded_by`),
  KEY `idx_is_public` (`is_public`),
  CONSTRAINT `fk_cms_media_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_media`
--

LOCK TABLES `cms_media` WRITE;
/*!40000 ALTER TABLE `cms_media` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_media` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_pages`
--

DROP TABLE IF EXISTS `cms_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `page_type` enum('static','policy','help','blog','custom') NOT NULL DEFAULT 'static',
  `template` varchar(100) DEFAULT 'default',
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `featured_image` varchar(500) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `requires_auth` tinyint(1) NOT NULL DEFAULT 0,
  `allowed_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_roles`)),
  `custom_css` text DEFAULT NULL,
  `custom_js` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`),
  KEY `idx_page_type` (`page_type`),
  KEY `idx_status` (`status`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_sort_order` (`sort_order`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_updated_by` (`updated_by`),
  CONSTRAINT `fk_cms_pages_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cms_pages_parent` FOREIGN KEY (`parent_id`) REFERENCES `cms_pages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_cms_pages_updater` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_pages`
--

LOCK TABLES `cms_pages` WRITE;
/*!40000 ALTER TABLE `cms_pages` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_posts`
--

DROP TABLE IF EXISTS `cms_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` text DEFAULT NULL,
  `content` longtext NOT NULL,
  `post_type` enum('blog','news','announcement','tutorial','faq') NOT NULL DEFAULT 'blog',
  `status` enum('draft','published','scheduled','archived') NOT NULL DEFAULT 'draft',
  `featured_image` varchar(500) DEFAULT NULL,
  `author_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `view_count` int(11) NOT NULL DEFAULT 0,
  `comment_count` int(11) NOT NULL DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`),
  KEY `idx_post_type` (`post_type`),
  KEY `idx_status` (`status`),
  KEY `idx_author_id` (`author_id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_published_at` (`published_at`),
  KEY `idx_cms_posts_type_status_published` (`post_type`,`status`,`published_at`),
  CONSTRAINT `fk_cms_posts_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cms_posts_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_posts`
--

LOCK TABLES `cms_posts` WRITE;
/*!40000 ALTER TABLE `cms_posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comm_messages`
--

DROP TABLE IF EXISTS `comm_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comm_messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `channel` enum('email','sms','push','in_app') NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body` longtext NOT NULL,
  `status` enum('pending','sent','delivered','failed','bounced') NOT NULL DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `opened_at` timestamp NULL DEFAULT NULL,
  `clicked_at` timestamp NULL DEFAULT NULL,
  `template_id` int(11) DEFAULT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `personalization_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`personalization_data`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`message_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_channel` (`channel`),
  KEY `idx_status` (`status`),
  KEY `idx_template_id` (`template_id`),
  KEY `idx_campaign_id` (`campaign_id`),
  KEY `idx_sent_at` (`sent_at`),
  KEY `idx_comm_messages_channel_status` (`channel`,`status`,`sent_at`),
  CONSTRAINT `fk_comm_messages_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comm_messages`
--

LOCK TABLES `comm_messages` WRITE;
/*!40000 ALTER TABLE `comm_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `comm_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `message` text NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `status` enum('unread','read','replied','archived') NOT NULL DEFAULT 'unread',
  `admin_reply` text DEFAULT NULL,
  `replied_by` int(11) DEFAULT NULL,
  `replied_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_replied_by` (`replied_by`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_messages`
--

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
INSERT INTO `contact_messages` VALUES
(1,'Gerard Driskell','gerard.driskell@msn.com','Info about Doogee Smartphones','Hey,\r\n\r\nI wanted to share some general information about Doogee Android Smartphones. These devices are known for their reliable performance, durable battery life, and reasonable pricing.\r\n\r\nFor further details, you can check the information page here:\r\nhttps://www.quality.for.less.longislandservicesnet.com/product/doogee-smartphones-android-phones/\r\n\r\nIf this message is not relevant to you, please feel free to disregard.\r\n\r\nBest regards,','feedback','unread',NULL,NULL,NULL,'2025-10-08 00:09:41','2025-10-08 00:09:41'),
(2,'Leefok','dinanikolskaya99@gmail.com','Hallo  i writing about your   prices','Hola, volia saber el seu preu.','account_help','unread',NULL,NULL,NULL,'2025-10-08 00:55:51','2025-10-08 00:55:51'),
(3,'123bDup','123b1@123bv1.it.com','123B online is a all the rage online casino that provides jackpot, fish shooting, and tangible tradesman tables.','&lt;b&gt;&lt;a href=https://cheapjerseysfromchinaonline.us.com/&gt;123B&lt;/a&gt;&lt;/b&gt; stands as a top-ranking destination with a view players who pursue real performance in the vibrant realm of online gaming. This platform brings together thousands of enthusiasts from across the globe, sacrifice an savoir vivre that blends furore, literalism, and trust. Whether you are into &lt;b&gt;casino&lt;/b&gt; adventures, thrilling &lt;b&gt;x? s?&lt;/b&gt; draws, enthusiastic &lt;b&gt;th? thao&lt;/b&gt; matches, or immersive &lt;b&gt;trò choi&lt;/b&gt; challenges, &lt;b&gt;123B&lt;/b&gt; delivers an ecosystem where every half a mo counts. \r\n \r\nWithin its extensive portfolio, members can explore countless categories — from &lt;b&gt;game slots&lt;/b&gt; with large &lt;b&gt;jackpot&lt;/b&gt; rewards to competitive &lt;b&gt;b?n cá&lt;/b&gt; arenas and ritual titles like &lt;b&gt;tài x?u md5&lt;/b&gt;, &lt;b&gt;xóc dia&lt;/b&gt;, &lt;b&gt;baccarat&lt;/b&gt;, and &lt;b&gt;r?ng h?&lt;/b&gt;. Each misrepresent is optimized for suave about and fairness, ensuring that both stylish and established players can enjoy every whirl and wager with buxom confidence. \r\n \r\nBeyond the games themselves, &lt;b&gt;123B&lt;/b&gt; focuses on providing a thorough ecosystem — featuring diaphanous &lt;b&gt;khuy?n mãi&lt;/b&gt;, dedicated &lt;b&gt;cskh&lt;/b&gt; champion, and flexile &lt;b&gt;uu dãi&lt;/b&gt; programs that compensate loyalty. As a service to those who plan for to develop a occupation in this digital entertainment boundary, the &lt;b&gt;d?i lý&lt;/b&gt; system opens up opportunities to initiate consistent income finished with decision-making partnership models. \r\n \r\nFor esports lovers and strategic gamers, &lt;b&gt;123B&lt;/b&gt; has expanded its coverage into global &lt;b&gt;esports&lt;/b&gt; competitions, integrating real-time odds and analytics. This modernization bridges the thrill of gaming with competitive text, serving users space more informed decisions and bespeak deeper in their favorite titles. \r\n \r\nTo episode this growing universe of amusement, visit &lt;a href=https://cheapjerseysfromchinaonline.us.com/&gt;https://cheapjerseysfromchinaonline.us.com/&lt;/a&gt; — the pompous gateway where every click leads to limitless upset and the next big win.','feedback','unread',NULL,NULL,NULL,'2025-10-09 06:43:58','2025-10-09 06:43:58'),
(4,'Stevepal','xrumer23Cog@gmail.com','Best the best database for data leaks','Data-Leaks – Find what google can’t find \r\nGreat in data leak: With over 20 billion collected passwords \r\nSuper fast search speed: Allows easy and super fast search of any user or domain. \r\nMany options for buy, many discout. Just 2$ to experience all functions, Allows downloading clean data from your query. \r\nGo to : https://Data-Leaks.org','technical_issue','unread',NULL,NULL,NULL,'2025-10-10 00:51:08','2025-10-10 00:51:08'),
(5,'CharlesStype','st.i.l.tbtsb@web.de','blsm-at','Рабочие ссылки на bs2best at \r\n \r\nПривет! Нашел рабочие зеркала для bs2best at: \r\n \r\nСсылки для входа: \r\n• &lt;a href=https://blsa-at.bond&gt;bs2best at&lt;/a&gt; \r\n• &lt;a href=https://bs2best-at.lol&gt;вход&lt;/a&gt; \r\n• &lt;a href=https://blsp.quest&gt;обход блокировки&lt;/a&gt; \r\n \r\nВсе проверено, работает стабильно.','return_refund','unread',NULL,NULL,NULL,'2025-10-10 08:13:27','2025-10-10 08:13:27'),
(6,'BrianAppom','mrtommyk@gmail.com','In the latest loli porn','Unconversant with loli porn cp pthc \r\n \r\n \r\nhttps://afpo.eu/jk6Va \r\n \r\nhttps://go.euserv.org/17w','product_question','unread',NULL,NULL,NULL,'2025-10-10 16:48:15','2025-10-10 16:48:15');
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupon_redemptions`
--

DROP TABLE IF EXISTS `coupon_redemptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupon_redemptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `coupon_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `original_order_amount` decimal(10,2) NOT NULL,
  `final_order_amount` decimal(10,2) NOT NULL,
  `redeemed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_coupon_id` (`coupon_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_redeemed_at` (`redeemed_at`),
  CONSTRAINT `fk_coupon_redemptions_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_coupon_redemptions_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_coupon_redemptions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupon_redemptions`
--

LOCK TABLES `coupon_redemptions` WRITE;
/*!40000 ALTER TABLE `coupon_redemptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `coupon_redemptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupon_rules`
--

DROP TABLE IF EXISTS `coupon_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupon_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `coupon_id` int(11) NOT NULL,
  `rule_type` enum('minimum_amount','product_category','user_segment','date_range','usage_limit','first_time_buyer') NOT NULL,
  `rule_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`rule_data`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_coupon_id` (`coupon_id`),
  KEY `idx_rule_type` (`rule_type`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_coupon_rules_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupon_rules`
--

LOCK TABLES `coupon_rules` WRITE;
/*!40000 ALTER TABLE `coupon_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `coupon_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupon_usage`
--

DROP TABLE IF EXISTS `coupon_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupon_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `coupon_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_coupon_id` (`coupon_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_used_at` (`used_at`),
  CONSTRAINT `fk_coupon_usage_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_coupon_usage_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_coupon_usage_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupon_usage`
--

LOCK TABLES `coupon_usage` WRITE;
/*!40000 ALTER TABLE `coupon_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `coupon_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `type` enum('fixed','percentage') NOT NULL DEFAULT 'fixed',
  `value` decimal(10,2) NOT NULL,
  `minimum_amount` decimal(10,2) DEFAULT NULL,
  `maximum_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `user_usage_limit` int(11) DEFAULT NULL,
  `status` enum('active','inactive','expired') NOT NULL DEFAULT 'active',
  `valid_from` timestamp NULL DEFAULT NULL,
  `valid_to` timestamp NULL DEFAULT NULL,
  `applies_to` enum('all','categories','products','users') NOT NULL DEFAULT 'all',
  `applicable_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applicable_items`)),
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_code` (`code`),
  KEY `idx_status` (`status`),
  KEY `idx_valid_from` (`valid_from`),
  KEY `idx_valid_to` (`valid_to`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `fk_coupons_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupons`
--

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
/*!40000 ALTER TABLE `coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `currencies`
--

DROP TABLE IF EXISTS `currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `currencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(3) NOT NULL,
  `name` varchar(100) NOT NULL,
  `symbol` varchar(10) NOT NULL,
  `decimal_places` tinyint(2) NOT NULL DEFAULT 2,
  `exchange_rate` decimal(10,6) NOT NULL DEFAULT 1.000000,
  `is_base_currency` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_code` (`code`),
  KEY `idx_is_base_currency` (`is_base_currency`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `currencies`
--

LOCK TABLES `currencies` WRITE;
/*!40000 ALTER TABLE `currencies` DISABLE KEYS */;
INSERT INTO `currencies` VALUES
(1,'USD','US Dollar','$',2,1.000000,1,1,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(2,'EUR','Euro','â‚¬',2,0.850000,0,1,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(3,'GBP','British Pound','Â£',2,0.750000,0,1,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(4,'JPY','Japanese Yen','Â¥',0,110.000000,0,1,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(5,'CAD','Canadian Dollar','C$',2,1.250000,0,1,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(6,'AUD','Australian Dollar','A$',2,1.350000,0,1,'2025-09-14 19:54:26','2025-09-14 19:54:26');
/*!40000 ALTER TABLE `currencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `currency_rates`
--

DROP TABLE IF EXISTS `currency_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `currency_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `currency_code` varchar(3) NOT NULL,
  `currency_name` varchar(50) NOT NULL,
  `currency_symbol` varchar(10) NOT NULL,
  `rate_to_usd` decimal(18,6) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `currency_code` (`currency_code`),
  KEY `last_updated` (`last_updated`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `currency_rates`
--

LOCK TABLES `currency_rates` WRITE;
/*!40000 ALTER TABLE `currency_rates` DISABLE KEYS */;
INSERT INTO `currency_rates` VALUES
(1,'USD','US Dollar','$',1.000000,'2025-10-05 10:46:10'),
(2,'EUR','Euro','€',1.162791,'2025-10-09 19:26:07'),
(3,'RWF','Rwandan Franc','FRw',1452.410000,'2025-10-09 19:26:07');
/*!40000 ALTER TABLE `currency_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_downloads`
--

DROP TABLE IF EXISTS `customer_downloads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_downloads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `digital_product_id` bigint(20) unsigned NOT NULL,
  `download_token` varchar(255) NOT NULL,
  `download_count` int(11) NOT NULL DEFAULT 0,
  `download_limit` int(11) DEFAULT NULL COMMENT 'NULL = unlimited',
  `expires_at` timestamp NULL DEFAULT NULL,
  `last_downloaded_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_download_token` (`download_token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_digital_product_id` (`digital_product_id`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `fk_cd_digital_product` FOREIGN KEY (`digital_product_id`) REFERENCES `digital_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cd_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cd_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cd_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_downloads`
--

LOCK TABLES `customer_downloads` WRITE;
/*!40000 ALTER TABLE `customer_downloads` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_downloads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_order_feedback`
--

DROP TABLE IF EXISTS `customer_order_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_order_feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` between 1 and 5),
  `delivery_rating` tinyint(1) DEFAULT NULL CHECK (`delivery_rating` between 1 and 5),
  `communication_rating` tinyint(1) DEFAULT NULL CHECK (`communication_rating` between 1 and 5),
  `feedback_text` text DEFAULT NULL,
  `would_recommend` tinyint(1) DEFAULT NULL,
  `issues_encountered` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`issues_encountered`)),
  `seller_response` text DEFAULT NULL,
  `seller_responded_at` timestamp NULL DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `helpful_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_order_customer` (`order_id`,`customer_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_rating` (`rating`),
  KEY `idx_is_public` (`is_public`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_customer_order_feedback_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_customer_order_feedback_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_customer_order_feedback_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_order_feedback`
--

LOCK TABLES `customer_order_feedback` WRITE;
/*!40000 ALTER TABLE `customer_order_feedback` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_order_feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_profiles`
--

DROP TABLE IF EXISTS `customer_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other','prefer_not_to_say') DEFAULT NULL,
  `interests` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`interests`)),
  `preferred_language` varchar(5) NOT NULL DEFAULT 'en',
  `preferred_currency` varchar(3) NOT NULL DEFAULT 'USD',
  `marketing_consent` tinyint(1) NOT NULL DEFAULT 0,
  `data_processing_consent` tinyint(1) NOT NULL DEFAULT 1,
  `newsletter_subscription` tinyint(1) NOT NULL DEFAULT 0,
  `sms_notifications` tinyint(1) NOT NULL DEFAULT 0,
  `loyalty_points` int(11) NOT NULL DEFAULT 0,
  `total_spent` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_orders` int(11) NOT NULL DEFAULT 0,
  `favorite_categories` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`favorite_categories`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_id` (`user_id`),
  KEY `idx_preferred_language` (`preferred_language`),
  KEY `idx_loyalty_points` (`loyalty_points`),
  KEY `idx_total_spent` (`total_spent`),
  CONSTRAINT `fk_customer_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_profiles`
--

LOCK TABLES `customer_profiles` WRITE;
/*!40000 ALTER TABLE `customer_profiles` DISABLE KEYS */;
INSERT INTO `customer_profiles` VALUES
(1,4,NULL,NULL,NULL,'en','USD',0,1,0,0,0,0.00,0,NULL,'2025-09-14 19:54:24','2025-09-14 19:54:24');
/*!40000 ALTER TABLE `customer_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_support_conversations`
--

DROP TABLE IF EXISTS `customer_support_conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_support_conversations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `message` longtext NOT NULL,
  `message_type` enum('customer','vendor','admin','system','auto') NOT NULL DEFAULT 'customer',
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `is_internal` tinyint(1) NOT NULL DEFAULT 0,
  `read_by_customer` tinyint(1) NOT NULL DEFAULT 0,
  `read_by_vendor` tinyint(1) NOT NULL DEFAULT 0,
  `read_by_admin` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ticket_id` (`ticket_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_message_type` (`message_type`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_support_conversations_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_support_conversations_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_support_conversations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_support_conversations_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_support_conversations`
--

LOCK TABLES `customer_support_conversations` WRITE;
/*!40000 ALTER TABLE `customer_support_conversations` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_support_conversations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `stripe_customer_id` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_customer_email` (`email`),
  UNIQUE KEY `uniq_customer_stripe` (`stripe_customer_id`),
  KEY `idx_customer_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dashboard_widgets`
--

DROP TABLE IF EXISTS `dashboard_widgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dashboard_widgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `widget_type` enum('kpi','chart','table','notification','counter','link','activity') NOT NULL,
  `widget_name` varchar(255) NOT NULL,
  `widget_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`widget_config`)),
  `position_x` int(11) NOT NULL DEFAULT 0,
  `position_y` int(11) NOT NULL DEFAULT 0,
  `width` int(11) NOT NULL DEFAULT 4,
  `height` int(11) NOT NULL DEFAULT 4,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_widget_type` (`widget_type`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_dashboard_widgets_user_active` (`user_id`,`is_active`),
  CONSTRAINT `fk_dashboard_widgets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dashboard_widgets`
--

LOCK TABLES `dashboard_widgets` WRITE;
/*!40000 ALTER TABLE `dashboard_widgets` DISABLE KEYS */;
/*!40000 ALTER TABLE `dashboard_widgets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `digital_products`
--

DROP TABLE IF EXISTS `digital_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `digital_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL COMMENT 'Size in bytes',
  `file_type` varchar(100) DEFAULT NULL,
  `version` varchar(50) DEFAULT '1.0',
  `download_limit` int(11) DEFAULT NULL COMMENT 'NULL = unlimited',
  `expiry_days` int(11) DEFAULT NULL COMMENT 'Days after purchase before link expires',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_product_version` (`product_id`,`version`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_dp_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `digital_products`
--

LOCK TABLES `digital_products` WRITE;
/*!40000 ALTER TABLE `digital_products` DISABLE KEYS */;
/*!40000 ALTER TABLE `digital_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dispute_decisions`
--

DROP TABLE IF EXISTS `dispute_decisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dispute_decisions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dispute_id` int(11) NOT NULL,
  `decided_by` int(11) NOT NULL,
  `decision` enum('favor_customer','favor_vendor','split_decision','need_more_info','escalate') NOT NULL,
  `reasoning` text NOT NULL,
  `resolution_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`resolution_details`)),
  `financial_impact` decimal(10,2) DEFAULT NULL,
  `follow_up_required` tinyint(1) NOT NULL DEFAULT 0,
  `follow_up_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_dispute_id` (`dispute_id`),
  KEY `idx_decided_by` (`decided_by`),
  KEY `idx_decision` (`decision`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_dispute_decisions_decider` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dispute_decisions_dispute` FOREIGN KEY (`dispute_id`) REFERENCES `disputes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dispute_decisions`
--

LOCK TABLES `dispute_decisions` WRITE;
/*!40000 ALTER TABLE `dispute_decisions` DISABLE KEYS */;
/*!40000 ALTER TABLE `dispute_decisions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dispute_evidence`
--

DROP TABLE IF EXISTS `dispute_evidence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dispute_evidence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dispute_id` int(11) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `evidence_type` enum('image','document','video','other') NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_dispute_id` (`dispute_id`),
  KEY `idx_uploaded_by` (`uploaded_by`),
  KEY `idx_evidence_type` (`evidence_type`),
  KEY `idx_is_public` (`is_public`),
  CONSTRAINT `fk_dispute_evidence_dispute` FOREIGN KEY (`dispute_id`) REFERENCES `disputes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dispute_evidence_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dispute_evidence`
--

LOCK TABLES `dispute_evidence` WRITE;
/*!40000 ALTER TABLE `dispute_evidence` DISABLE KEYS */;
/*!40000 ALTER TABLE `dispute_evidence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dispute_messages`
--

DROP TABLE IF EXISTS `dispute_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dispute_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dispute_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('customer','vendor','admin','system') NOT NULL,
  `message` text NOT NULL,
  `is_internal` tinyint(1) NOT NULL DEFAULT 0,
  `read_by_customer` tinyint(1) NOT NULL DEFAULT 0,
  `read_by_vendor` tinyint(1) NOT NULL DEFAULT 0,
  `read_by_admin` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_dispute_id` (`dispute_id`),
  KEY `idx_sender_id` (`sender_id`),
  KEY `idx_sender_type` (`sender_type`),
  KEY `idx_is_internal` (`is_internal`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_dispute_messages_dispute` FOREIGN KEY (`dispute_id`) REFERENCES `disputes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dispute_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dispute_messages`
--

LOCK TABLES `dispute_messages` WRITE;
/*!40000 ALTER TABLE `dispute_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `dispute_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `disputes`
--

DROP TABLE IF EXISTS `disputes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `disputes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `dispute_number` varchar(50) NOT NULL,
  `type` enum('refund','return','quality','delivery','billing','other') NOT NULL,
  `category` enum('item_not_received','item_damaged','wrong_item','quality_issue','billing_error','shipping_issue','other') NOT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `amount_disputed` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('open','investigating','pending_vendor','pending_customer','escalated','resolved','closed') NOT NULL DEFAULT 'open',
  `sla_deadline` datetime DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `assigned_to` int(11) DEFAULT NULL,
  `escalated_to` int(11) DEFAULT NULL,
  `sla_due_date` timestamp NULL DEFAULT NULL,
  `resolution_type` enum('refund','replacement','partial_refund','discount','no_action') DEFAULT NULL,
  `resolution_amount` decimal(10,2) DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `customer_satisfaction` tinyint(1) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_dispute_number` (`dispute_number`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_sla_due_date` (`sla_due_date`),
  KEY `fk_disputes_escalated` (`escalated_to`),
  KEY `idx_disputes_status_priority` (`status`,`priority`,`sla_due_date`),
  CONSTRAINT `fk_disputes_assignee` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_disputes_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_disputes_escalated` FOREIGN KEY (`escalated_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_disputes_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_disputes_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `disputes`
--

LOCK TABLES `disputes` WRITE;
/*!40000 ALTER TABLE `disputes` DISABLE KEYS */;
/*!40000 ALTER TABLE `disputes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_log`
--

DROP TABLE IF EXISTS `email_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_log` (
  `id` int(11) NOT NULL,
  `to_email` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `template_name` varchar(100) DEFAULT NULL,
  `status` enum('sent','failed') NOT NULL,
  `error_message` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email_log_status` (`status`),
  KEY `idx_email_log_sent` (`sent_at`),
  KEY `idx_email_log_to_email` (`to_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_log`
--

LOCK TABLES `email_log` WRITE;
/*!40000 ALTER TABLE `email_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_logs`
--

DROP TABLE IF EXISTS `email_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'general',
  `user_id` int(11) DEFAULT NULL,
  `status` enum('pending','sent','failed','error') NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_recipient` (`recipient`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`type`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_logs`
--

LOCK TABLES `email_logs` WRITE;
/*!40000 ALTER TABLE `email_logs` DISABLE KEYS */;
INSERT INTO `email_logs` VALUES
(1,'fezamarketgroup@gmail.com','Verify your email address - FezaMarket','verification',13,'sent',NULL,'2025-10-07 17:04:44','2025-10-07 17:04:44'),
(2,'fezamarketgroup@gmail.com','Verify your email address - FezaMarket','verification',13,'sent',NULL,'2025-10-07 17:06:50','2025-10-07 17:06:50'),
(3,'fezalogistics@gmail.com','Verify your email address - FezaMarket','verification',14,'sent',NULL,'2025-10-07 18:17:13','2025-10-07 18:17:13'),
(4,'jumajumaa987@gmail.com','Verify your email address - FezaMarket','verification',15,'sent',NULL,'2025-10-07 21:40:33','2025-10-07 21:40:33'),
(5,'amarjit18000@gmail.com','Verify your email address - FezaMarket','verification',16,'sent',NULL,'2025-10-09 13:23:32','2025-10-09 13:23:32'),
(6,'amarjitfatehgarh05@gmail.com','Verify your email address - FezaMarket','verification',17,'sent',NULL,'2025-10-09 13:40:41','2025-10-09 13:40:41');
/*!40000 ALTER TABLE `email_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_queue`
--

DROP TABLE IF EXISTS `email_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `to_email` varchar(255) NOT NULL,
  `to_name` varchar(255) DEFAULT NULL,
  `subject` varchar(500) NOT NULL,
  `body` longtext NOT NULL,
  `template` varchar(100) DEFAULT NULL,
  `template_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`template_data`)),
  `priority` tinyint(1) NOT NULL DEFAULT 3,
  `status` enum('pending','sending','sent','failed','cancelled') NOT NULL DEFAULT 'pending',
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `max_attempts` tinyint(3) unsigned NOT NULL DEFAULT 3,
  `error_message` text DEFAULT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_scheduled_at` (`scheduled_at`),
  KEY `idx_attempts` (`attempts`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_queue`
--

LOCK TABLES `email_queue` WRITE;
/*!40000 ALTER TABLE `email_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_tokens`
--

DROP TABLE IF EXISTS `email_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `type` enum('email_verification','password_reset','email_change','two_fa_backup') NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `expires_at` timestamp NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_token` (`token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_expires_at` (`expires_at`),
  KEY `idx_used_at` (`used_at`),
  CONSTRAINT `fk_email_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_tokens`
--

LOCK TABLES `email_tokens` WRITE;
/*!40000 ALTER TABLE `email_tokens` DISABLE KEYS */;
INSERT INTO `email_tokens` VALUES
(1,5,'7ab495d655a50fd0e2f4317d4a2af14cdea18c06caccbc25010954ee7bbcc194','email_verification','niyogushimwaj967@gmail.com','2025-09-20 20:38:57',NULL,'172.68.42.184','2025-09-20 20:23:57'),
(11,16,'215ba2be2c455c229163697ff15c1d6ed41dcac10bc1ef7d2c1a9aa3d7d51ec2','password_reset','amarjit18000@gmail.com','2025-10-09 11:49:40',NULL,NULL,'2025-10-09 11:34:40');
/*!40000 ALTER TABLE `email_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_verifications`
--

DROP TABLE IF EXISTS `email_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `verified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_token` (`token`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_email_verifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_verifications`
--

LOCK TABLES `email_verifications` WRITE;
/*!40000 ALTER TABLE `email_verifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_verifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fact_campaigns`
--

DROP TABLE IF EXISTS `fact_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fact_campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `date_key` int(11) NOT NULL,
  `impressions` int(11) NOT NULL DEFAULT 0,
  `clicks` int(11) NOT NULL DEFAULT 0,
  `conversions` int(11) NOT NULL DEFAULT 0,
  `revenue` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_campaign_date` (`campaign_id`,`date_key`),
  KEY `idx_date_key` (`date_key`),
  CONSTRAINT `fk_fact_campaigns_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fact_campaigns`
--

LOCK TABLES `fact_campaigns` WRITE;
/*!40000 ALTER TABLE `fact_campaigns` DISABLE KEYS */;
/*!40000 ALTER TABLE `fact_campaigns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fact_sales`
--

DROP TABLE IF EXISTS `fact_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fact_sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `date_key` int(11) NOT NULL,
  `time_key` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `commission_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT NULL,
  `order_status` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_date_key` (`date_key`),
  KEY `idx_time_key` (`time_key`),
  KEY `fk_fact_sales_order_item` (`order_item_id`),
  KEY `idx_fact_sales_date_vendor` (`date_key`,`vendor_id`,`total_amount`),
  CONSTRAINT `fk_fact_sales_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fact_sales_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fact_sales`
--

LOCK TABLES `fact_sales` WRITE;
/*!40000 ALTER TABLE `fact_sales` DISABLE KEYS */;
/*!40000 ALTER TABLE `fact_sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fact_users`
--

DROP TABLE IF EXISTS `fact_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fact_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `date_key` int(11) NOT NULL,
  `registration_date` date DEFAULT NULL,
  `last_login_date` date DEFAULT NULL,
  `total_orders` int(11) NOT NULL DEFAULT 0,
  `total_spent` decimal(15,2) NOT NULL DEFAULT 0.00,
  `average_order_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `days_since_last_order` int(11) DEFAULT NULL,
  `user_segment` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_date` (`user_id`,`date_key`),
  KEY `idx_date_key` (`date_key`),
  KEY `idx_user_segment` (`user_segment`),
  CONSTRAINT `fk_fact_users_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fact_users`
--

LOCK TABLES `fact_users` WRITE;
/*!40000 ALTER TABLE `fact_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `fact_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `file_uploads`
--

DROP TABLE IF EXISTS `file_uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `file_uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) unsigned NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_hash` varchar(64) DEFAULT NULL,
  `upload_type` enum('product_image','user_avatar','document','attachment','other') NOT NULL DEFAULT 'other',
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `download_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_file_hash` (`file_hash`),
  KEY `idx_upload_type` (`upload_type`),
  KEY `idx_reference` (`reference_type`,`reference_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_file_uploads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `file_uploads`
--

LOCK TABLES `file_uploads` WRITE;
/*!40000 ALTER TABLE `file_uploads` DISABLE KEYS */;
/*!40000 ALTER TABLE `file_uploads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gift_card_transactions`
--

DROP TABLE IF EXISTS `gift_card_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `gift_card_transactions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `gift_card_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL COMMENT 'Order where gift card was used',
  `transaction_type` enum('purchase','redemption','refund','adjustment') NOT NULL,
  `amount` decimal(10,2) NOT NULL COMMENT 'Amount added or deducted',
  `balance_before` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_gift_card_id` (`gift_card_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_transaction_type` (`transaction_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gift_card_transactions`
--

LOCK TABLES `gift_card_transactions` WRITE;
/*!40000 ALTER TABLE `gift_card_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `gift_card_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gift_cards`
--

DROP TABLE IF EXISTS `gift_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `gift_cards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL COMMENT 'Unique gift card code',
  `amount` decimal(10,2) NOT NULL COMMENT 'Original gift card value',
  `balance` decimal(10,2) NOT NULL COMMENT 'Current balance',
  `card_type` enum('digital','physical') NOT NULL DEFAULT 'digital',
  `design` varchar(50) DEFAULT 'generic' COMMENT 'Card design theme',
  `recipient_name` varchar(255) NOT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `sender_name` varchar(255) NOT NULL,
  `sender_email` varchar(255) NOT NULL,
  `personal_message` text DEFAULT NULL,
  `status` enum('pending','active','redeemed','expired','cancelled') NOT NULL DEFAULT 'pending',
  `order_id` int(11) DEFAULT NULL COMMENT 'Order ID for purchase',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `activated_at` timestamp NULL DEFAULT NULL COMMENT 'When gift card was activated after payment',
  `expires_at` timestamp NULL DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  UNIQUE KEY `idx_code` (`code`),
  KEY `idx_recipient_email` (`recipient_email`),
  KEY `idx_sender_email` (`sender_email`),
  KEY `idx_status` (`status`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gift_cards`
--

LOCK TABLES `gift_cards` WRITE;
/*!40000 ALTER TABLE `gift_cards` DISABLE KEYS */;
/*!40000 ALTER TABLE `gift_cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `government_api_access`
--

DROP TABLE IF EXISTS `government_api_access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `government_api_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_name` varchar(255) NOT NULL,
  `contact_name` varchar(255) NOT NULL,
  `contact_email` varchar(255) NOT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `api_key_id` int(11) NOT NULL,
  `access_level` enum('read_only','analytics_only','full') NOT NULL DEFAULT 'read_only',
  `allowed_endpoints` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_endpoints`)),
  `ip_whitelist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ip_whitelist`)),
  `status` enum('pending','approved','active','suspended','revoked') NOT NULL DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `api_key_id` (`api_key_id`),
  KEY `idx_gov_status` (`status`),
  CONSTRAINT `fk_gov_api_key` FOREIGN KEY (`api_key_id`) REFERENCES `api_keys` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gov_api_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Special government API access with restricted permissions';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `government_api_access`
--

LOCK TABLES `government_api_access` WRITE;
/*!40000 ALTER TABLE `government_api_access` DISABLE KEYS */;
/*!40000 ALTER TABLE `government_api_access` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `homepage_banners`
--

DROP TABLE IF EXISTS `homepage_banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `homepage_banners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(500) NOT NULL,
  `link_url` varchar(500) DEFAULT NULL,
  `button_text` varchar(100) DEFAULT NULL,
  `background_color` varchar(7) DEFAULT '#ffffff',
  `text_color` varchar(7) DEFAULT '#000000',
  `position` enum('hero','top','middle','bottom','sidebar') NOT NULL DEFAULT 'hero',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive','draft','scheduled') NOT NULL DEFAULT 'draft',
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `click_count` int(11) NOT NULL DEFAULT 0,
  `view_count` int(11) NOT NULL DEFAULT 0,
  `target_audience` enum('all','customers','vendors','new_users') NOT NULL DEFAULT 'all',
  `device_target` enum('all','desktop','mobile','tablet') NOT NULL DEFAULT 'all',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_position` (`position`),
  KEY `idx_sort_order` (`sort_order`),
  KEY `idx_start_date` (`start_date`),
  KEY `idx_end_date` (`end_date`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_status_position_sort` (`status`,`position`,`sort_order`),
  KEY `idx_status_start_end` (`status`,`start_date`,`end_date`),
  CONSTRAINT `fk_homepage_banners_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `homepage_banners`
--

LOCK TABLES `homepage_banners` WRITE;
/*!40000 ALTER TABLE `homepage_banners` DISABLE KEYS */;
INSERT INTO `homepage_banners` VALUES
(1,'The fall shoes to shop now','Shop and enjoy the discount','Shop and enjoy the discount','https://www.google.com/url?sa=i&amp;url=https%3A%2F%2Fwww.toyota.com.sg%2Fshowroom%2Fnew-models%2Fprius&amp;psig=AOvVaw2aZF5dfyd26HNpJaPoc7Hn&amp;ust=1759177830478000&amp;source=images&amp;cd=vfe&amp;opi=89978449&amp;ved=0CBUQjRxqFwoTCNC8iJ-m_I8DFQAAAAAdAAAAABAE','','shop now','#ffffff','#000000','top',0,'active',NULL,NULL,0,0,'all','all',4,'2025-09-28 20:30:52','2025-09-28 20:30:52'),
(2,'The fall shoes to shop now','Shop and enjoy the discount','Shop and enjoy the discount','https://www.google.com/url?sa=i&amp;url=https%3A%2F%2Fwww.toyota.com.sg%2Fshowroom%2Fnew-models%2Fprius&amp;psig=AOvVaw2aZF5dfyd26HNpJaPoc7Hn&amp;ust=1759177830478000&amp;source=images&amp;cd=vfe&amp;opi=89978449&amp;ved=0CBUQjRxqFwoTCNC8iJ-m_I8DFQAAAAAdAAAAABAE','','shop now','#ffffff','#000000','top',0,'active',NULL,NULL,0,0,'all','all',4,'2025-09-28 20:30:53','2025-09-28 20:30:53'),
(3,'SHOP THE FALL','SHOP THE FAILL','SHOP THE FAILL','https://www.google.com/url?sa=i&amp;url=https%3A%2F%2Fwww.amazon.com%2F2020-HP-Touchscreen-Premium-Laptop%2Fdp%2FB081SM57RY&amp;psig=AOvVaw3Ld7iMDpipLV0uLhuOmE2I&amp;ust=1759182259462000&amp;source=images&amp;cd=vfe&amp;opi=89978449&amp;ved=0CBUQjRxqFwoTCJDR3KK5_I8DFQAAAAAdAAAAABAE','https://www.google.com/url?sa=i&amp;url=https%3A%2F%2Fwww.amazon.com%2F2020-HP-Touchscreen-Premium-Laptop%2Fdp%2FB081SM57RY&amp;psig=AOvVaw3Ld7iMDpipLV0uLhuOmE2I&amp;ust=1759182259462000&amp;source=images&amp;cd=vfe&amp;opi=89978449&amp;ved=0CBUQjRxqFwoTCJDR3KK5_I8DFQAAAAAdAAAAABAE','SHOP NOW','#ffffff','#000000','top',0,'active',NULL,NULL,0,0,'all','all',4,'2025-09-28 21:58:13','2025-09-28 21:58:13'),
(4,'SHOPPING ONLINE','SHOP ONLINE TODAY','SHOP ONLINE TODAY','/uploads/banners/banner_1759176162_68dae5e209a27.jpg','https://duns1.fezalogistics.com/','SHOP NOW','#ffffff','#000000','hero',0,'active',NULL,NULL,0,0,'all','all',4,'2025-09-29 20:02:42','2025-09-29 20:02:42'),
(5,'shop Onlie','Online shopping destination','Online shopping destination','/uploads/banners/banner_1759176266_68dae64a0025c.jpg','https://duns1.fezalogistics.com/','shop now','#ffffff','#000000','top',0,'active',NULL,NULL,0,0,'all','all',4,'2025-09-29 20:04:26','2025-09-29 20:04:26');
/*!40000 ALTER TABLE `homepage_banners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `homepage_sections`
--

DROP TABLE IF EXISTS `homepage_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `homepage_sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section_key` varchar(100) NOT NULL,
  `section_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`section_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_section_key` (`section_key`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `homepage_sections`
--

LOCK TABLES `homepage_sections` WRITE;
/*!40000 ALTER TABLE `homepage_sections` DISABLE KEYS */;
INSERT INTO `homepage_sections` VALUES
(1,'layout_config','[{\"id\":\"hero\",\"type\":\"hero\",\"title\":\"Hero Banner\",\"enabled\":true},{\"id\":\"categories\",\"type\":\"categories\",\"title\":\"Featured Categories\",\"enabled\":true},{\"id\":\"deals\",\"type\":\"deals\",\"title\":\"Daily Deals\",\"enabled\":true},{\"id\":\"trending\",\"type\":\"products\",\"title\":\"Trending Products\",\"enabled\":true},{\"id\":\"brands\",\"type\":\"brands\",\"title\":\"Top Brands\",\"enabled\":true},{\"id\":\"featured\",\"type\":\"products\",\"title\":\"Featured Products\",\"enabled\":true},{\"id\":\"new-arrivals\",\"type\":\"products\",\"title\":\"New Arrivals\",\"enabled\":true},{\"id\":\"recommendations\",\"type\":\"products\",\"title\":\"Recommended for You\",\"enabled\":true}]','2025-09-27 18:28:52','2025-09-27 18:46:41');
/*!40000 ALTER TABLE `homepage_sections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `integrations`
--

DROP TABLE IF EXISTS `integrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `integrations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('payment','shipping','marketing','analytics','communication','storage','other') NOT NULL,
  `provider` varchar(100) NOT NULL,
  `status` enum('active','inactive','error','pending') NOT NULL DEFAULT 'inactive',
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`config`)),
  `api_credentials` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`api_credentials`)),
  `webhook_url` varchar(500) DEFAULT NULL,
  `webhook_secret` varchar(255) DEFAULT NULL,
  `last_sync` timestamp NULL DEFAULT NULL,
  `sync_frequency` int(11) DEFAULT NULL,
  `error_count` int(11) NOT NULL DEFAULT 0,
  `last_error` text DEFAULT NULL,
  `installed_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_integration_name` (`name`),
  KEY `idx_integration_type` (`type`),
  KEY `idx_integration_status` (`status`),
  KEY `idx_integration_installer` (`installed_by`),
  CONSTRAINT `fk_integrations_installer` FOREIGN KEY (`installed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `integrations`
--

LOCK TABLES `integrations` WRITE;
/*!40000 ALTER TABLE `integrations` DISABLE KEYS */;
/*!40000 ALTER TABLE `integrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 0,
  `safety_stock` int(11) NOT NULL DEFAULT 0,
  `min_stock_level` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_warehouse` (`product_id`,`warehouse_id`),
  KEY `warehouse_id` (`warehouse_id`),
  CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory`
--

LOCK TABLES `inventory` WRITE;
/*!40000 ALTER TABLE `inventory` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_adjustments`
--

DROP TABLE IF EXISTS `inventory_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_adjustments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `adjustment` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `adjusted_by` int(11) DEFAULT NULL,
  `adjusted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `warehouse_id` (`warehouse_id`),
  CONSTRAINT `inventory_adjustments_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_adjustments_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_adjustments`
--

LOCK TABLES `inventory_adjustments` WRITE;
/*!40000 ALTER TABLE `inventory_adjustments` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_alerts`
--

DROP TABLE IF EXISTS `inventory_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `alert_type` enum('low_stock','out_of_stock','high_demand','slow_moving') NOT NULL,
  `threshold_value` int(11) NOT NULL,
  `current_value` int(11) NOT NULL,
  `priority` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `status` enum('active','acknowledged','resolved','dismissed') NOT NULL DEFAULT 'active',
  `acknowledged_by` int(11) DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_alert_type` (`alert_type`),
  KEY `idx_priority` (`priority`),
  KEY `idx_status` (`status`),
  KEY `idx_acknowledged_by` (`acknowledged_by`),
  CONSTRAINT `fk_inventory_alerts_acknowledger` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_inventory_alerts_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_alerts`
--

LOCK TABLES `inventory_alerts` WRITE;
/*!40000 ALTER TABLE `inventory_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `status` enum('draft','sent','paid','overdue','cancelled') NOT NULL DEFAULT 'draft',
  `pdf_path` varchar(500) DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_invoice_number` (`invoice_number`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_status` (`status`),
  KEY `idx_invoice_date` (`invoice_date`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `fk_invoices_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_invoices_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `job_type` enum('scheduled','manual','automatic') NOT NULL DEFAULT 'scheduled',
  `command` varchar(500) NOT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `schedule` varchar(100) DEFAULT NULL,
  `status` enum('pending','running','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `priority` enum('low','normal','high','critical') NOT NULL DEFAULT 'normal',
  `progress` int(11) NOT NULL DEFAULT 0,
  `output` longtext DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `retry_count` int(11) NOT NULL DEFAULT 0,
  `max_retries` int(11) NOT NULL DEFAULT 3,
  `timeout` int(11) NOT NULL DEFAULT 3600,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `next_run_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_job_type` (`job_type`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_next_run_at` (`next_run_at`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_jobs_status_priority_next_run` (`status`,`priority`,`next_run_at`),
  CONSTRAINT `fk_jobs_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kpi_daily`
--

DROP TABLE IF EXISTS `kpi_daily`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `kpi_daily` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `metric_date` date NOT NULL,
  `total_sales` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_orders` int(11) NOT NULL DEFAULT 0,
  `gmv` decimal(15,2) NOT NULL DEFAULT 0.00,
  `commission_revenue` decimal(15,2) NOT NULL DEFAULT 0.00,
  `active_users` int(11) NOT NULL DEFAULT 0,
  `active_buyers` int(11) NOT NULL DEFAULT 0,
  `active_sellers` int(11) NOT NULL DEFAULT 0,
  `guest_visitors` int(11) NOT NULL DEFAULT 0,
  `conversion_rate` decimal(5,4) NOT NULL DEFAULT 0.0000,
  `average_order_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_metric_date` (`metric_date`),
  KEY `idx_total_sales` (`total_sales`),
  KEY `idx_total_orders` (`total_orders`),
  KEY `idx_kpi_daily_date_sales` (`metric_date`,`total_sales`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kpi_daily`
--

LOCK TABLES `kpi_daily` WRITE;
/*!40000 ALTER TABLE `kpi_daily` DISABLE KEYS */;
/*!40000 ALTER TABLE `kpi_daily` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kyc_decisions`
--

DROP TABLE IF EXISTS `kyc_decisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `kyc_decisions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kyc_request_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `decision` enum('approve','reject','request_more_info','escalate') NOT NULL,
  `reason` text NOT NULL,
  `risk_assessment` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`risk_assessment`)),
  `follow_up_required` tinyint(1) NOT NULL DEFAULT 0,
  `follow_up_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_kyc_request_id` (`kyc_request_id`),
  KEY `idx_reviewer_id` (`reviewer_id`),
  KEY `idx_decision` (`decision`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_kyc_decisions_request` FOREIGN KEY (`kyc_request_id`) REFERENCES `kyc_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_kyc_decisions_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kyc_decisions`
--

LOCK TABLES `kyc_decisions` WRITE;
/*!40000 ALTER TABLE `kyc_decisions` DISABLE KEYS */;
/*!40000 ALTER TABLE `kyc_decisions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kyc_documents`
--

DROP TABLE IF EXISTS `kyc_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `kyc_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kyc_request_id` int(11) NOT NULL,
  `document_type` enum('passport','drivers_license','national_id','utility_bill','bank_statement','business_registration','other') NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `ocr_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ocr_data`)),
  `verification_status` enum('pending','processing','verified','failed') NOT NULL DEFAULT 'pending',
  `verification_notes` text DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_kyc_request_id` (`kyc_request_id`),
  KEY `idx_document_type` (`document_type`),
  KEY `idx_verification_status` (`verification_status`),
  CONSTRAINT `fk_kyc_documents_request` FOREIGN KEY (`kyc_request_id`) REFERENCES `kyc_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kyc_documents`
--

LOCK TABLES `kyc_documents` WRITE;
/*!40000 ALTER TABLE `kyc_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `kyc_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kyc_flags`
--

DROP TABLE IF EXISTS `kyc_flags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `kyc_flags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kyc_request_id` int(11) NOT NULL,
  `flag_type` enum('duplicate_identity','suspicious_activity','high_risk_country','document_mismatch','other') NOT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `description` text NOT NULL,
  `flag_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`flag_data`)),
  `is_resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_kyc_request_id` (`kyc_request_id`),
  KEY `idx_flag_type` (`flag_type`),
  KEY `idx_severity` (`severity`),
  KEY `idx_is_resolved` (`is_resolved`),
  KEY `fk_kyc_flags_resolver` (`resolved_by`),
  CONSTRAINT `fk_kyc_flags_request` FOREIGN KEY (`kyc_request_id`) REFERENCES `kyc_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_kyc_flags_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kyc_flags`
--

LOCK TABLES `kyc_flags` WRITE;
/*!40000 ALTER TABLE `kyc_flags` DISABLE KEYS */;
/*!40000 ALTER TABLE `kyc_flags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kyc_requests`
--

DROP TABLE IF EXISTS `kyc_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `kyc_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `request_type` enum('individual','business','enhanced') NOT NULL DEFAULT 'individual',
  `status` enum('pending','in_review','approved','rejected','requires_more_info') NOT NULL DEFAULT 'pending',
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `risk_score` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `personal_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`personal_info`)),
  `business_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`business_info`)),
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_submitted_at` (`submitted_at`),
  KEY `idx_kyc_requests_status_priority` (`status`,`priority`,`submitted_at`),
  CONSTRAINT `fk_kyc_requests_assignee` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_kyc_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kyc_requests`
--

LOCK TABLES `kyc_requests` WRITE;
/*!40000 ALTER TABLE `kyc_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `kyc_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kyc_verifications`
--

DROP TABLE IF EXISTS `kyc_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `kyc_verifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `verification_type` enum('identity','address','business','financial') NOT NULL,
  `status` enum('pending','approved','rejected','expired') NOT NULL DEFAULT 'pending',
  `verification_level` enum('basic','intermediate','advanced') NOT NULL DEFAULT 'basic',
  `documents_provided` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`documents_provided`)),
  `verification_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`verification_data`)),
  `reviewer_id` int(11) DEFAULT NULL,
  `reviewer_notes` text DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_kyc_user` (`user_id`),
  KEY `idx_kyc_status` (`status`),
  KEY `idx_kyc_type` (`verification_type`),
  KEY `idx_kyc_reviewer` (`reviewer_id`),
  CONSTRAINT `fk_kyc_verifications_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_kyc_verifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kyc_verifications`
--

LOCK TABLES `kyc_verifications` WRITE;
/*!40000 ALTER TABLE `kyc_verifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `kyc_verifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `live_chat_messages`
--

DROP TABLE IF EXISTS `live_chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `live_chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stream_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `message` text NOT NULL,
  `message_type` enum('chat','system','product','reaction') NOT NULL DEFAULT 'chat',
  `is_highlighted` tinyint(1) NOT NULL DEFAULT 0,
  `is_moderated` tinyint(1) NOT NULL DEFAULT 0,
  `moderated_by` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_stream_id` (`stream_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_message_type` (`message_type`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_is_moderated` (`is_moderated`),
  KEY `fk_live_chat_messages_moderator` (`moderated_by`),
  CONSTRAINT `fk_live_chat_messages_moderator` FOREIGN KEY (`moderated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_live_chat_messages_stream` FOREIGN KEY (`stream_id`) REFERENCES `live_streams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_live_chat_messages_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `live_chat_messages`
--

LOCK TABLES `live_chat_messages` WRITE;
/*!40000 ALTER TABLE `live_chat_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `live_chat_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `live_stream_analytics`
--

DROP TABLE IF EXISTS `live_stream_analytics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `live_stream_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stream_id` int(11) NOT NULL,
  `metric_type` enum('viewer_join','viewer_leave','chat_message','product_click','purchase','share','like') NOT NULL,
  `metric_value` decimal(10,2) NOT NULL DEFAULT 1.00,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_stream_id` (`stream_id`),
  KEY `idx_metric_type` (`metric_type`),
  KEY `idx_recorded_at` (`recorded_at`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_product_id` (`product_id`),
  CONSTRAINT `fk_stream_analytics_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_stream_analytics_stream` FOREIGN KEY (`stream_id`) REFERENCES `live_streams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stream_analytics_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `live_stream_analytics`
--

LOCK TABLES `live_stream_analytics` WRITE;
/*!40000 ALTER TABLE `live_stream_analytics` DISABLE KEYS */;
/*!40000 ALTER TABLE `live_stream_analytics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `live_stream_chat`
--

DROP TABLE IF EXISTS `live_stream_chat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `live_stream_chat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stream_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `message_type` enum('text','emoji','system','product') NOT NULL DEFAULT 'text',
  `product_id` int(11) DEFAULT NULL,
  `is_moderator` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_stream_id` (`stream_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_product_id` (`product_id`),
  CONSTRAINT `fk_stream_chat_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_stream_chat_stream` FOREIGN KEY (`stream_id`) REFERENCES `live_streams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stream_chat_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `live_stream_chat`
--

LOCK TABLES `live_stream_chat` WRITE;
/*!40000 ALTER TABLE `live_stream_chat` DISABLE KEYS */;
/*!40000 ALTER TABLE `live_stream_chat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `live_stream_moderators`
--

DROP TABLE IF EXISTS `live_stream_moderators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `live_stream_moderators` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stream_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '["delete_messages", "timeout_users"]' CHECK (json_valid(`permissions`)),
  `assigned_by` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_stream_moderator` (`stream_id`,`user_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_assigned_by` (`assigned_by`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_stream_moderators_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stream_moderators_stream` FOREIGN KEY (`stream_id`) REFERENCES `live_streams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stream_moderators_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `live_stream_moderators`
--

LOCK TABLES `live_stream_moderators` WRITE;
/*!40000 ALTER TABLE `live_stream_moderators` DISABLE KEYS */;
/*!40000 ALTER TABLE `live_stream_moderators` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `live_stream_notifications`
--

DROP TABLE IF EXISTS `live_stream_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `live_stream_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stream_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notification_type` enum('stream_starting','stream_live','stream_ended','new_product','special_offer') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `is_sent` tinyint(1) NOT NULL DEFAULT 0,
  `sent_at` datetime DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `delivery_method` enum('push','email','sms','in_app') NOT NULL DEFAULT 'in_app',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_stream_id` (`stream_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_notification_type` (`notification_type`),
  KEY `idx_is_sent` (`is_sent`),
  KEY `idx_is_read` (`is_read`),
  CONSTRAINT `fk_stream_notifications_stream` FOREIGN KEY (`stream_id`) REFERENCES `live_streams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stream_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `live_stream_notifications`
--

LOCK TABLES `live_stream_notifications` WRITE;
/*!40000 ALTER TABLE `live_stream_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `live_stream_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `live_stream_recordings`
--

DROP TABLE IF EXISTS `live_stream_recordings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `live_stream_recordings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stream_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT NULL,
  `quality` enum('240p','360p','480p','720p','1080p') NOT NULL DEFAULT '720p',
  `format` enum('mp4','webm','hls') NOT NULL DEFAULT 'mp4',
  `status` enum('recording','processing','completed','failed') NOT NULL DEFAULT 'recording',
  `thumbnail_path` varchar(500) DEFAULT NULL,
  `download_count` int(11) NOT NULL DEFAULT 0,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `processing_started_at` datetime DEFAULT NULL,
  `processing_completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_stream_id` (`stream_id`),
  KEY `idx_status` (`status`),
  KEY `idx_is_public` (`is_public`),
  CONSTRAINT `fk_stream_recordings_stream` FOREIGN KEY (`stream_id`) REFERENCES `live_streams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `live_stream_recordings`
--

LOCK TABLES `live_stream_recordings` WRITE;
/*!40000 ALTER TABLE `live_stream_recordings` DISABLE KEYS */;
/*!40000 ALTER TABLE `live_stream_recordings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `live_stream_settings`
--

DROP TABLE IF EXISTS `live_stream_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `live_stream_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','integer','boolean','json') NOT NULL DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_vendor_setting` (`vendor_id`,`setting_key`),
  CONSTRAINT `fk_stream_settings_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `live_stream_settings`
--

LOCK TABLES `live_stream_settings` WRITE;
/*!40000 ALTER TABLE `live_stream_settings` DISABLE KEYS */;
INSERT INTO `live_stream_settings` VALUES
(1,4,'max_concurrent_streams','1','integer','Maximum number of concurrent live streams','2025-10-01 21:16:01','2025-10-01 21:16:01'),
(2,3,'max_concurrent_streams','1','integer','Maximum number of concurrent live streams','2025-10-01 21:16:01','2025-10-01 21:16:01'),
(4,4,'auto_record_streams','true','boolean','Automatically record all live streams','2025-10-01 21:16:01','2025-10-01 21:16:01'),
(5,3,'auto_record_streams','true','boolean','Automatically record all live streams','2025-10-01 21:16:01','2025-10-01 21:16:01'),
(7,4,'chat_moderation_enabled','true','boolean','Enable chat moderation features','2025-10-01 21:16:01','2025-10-01 21:16:01'),
(8,3,'chat_moderation_enabled','true','boolean','Enable chat moderation features','2025-10-01 21:16:01','2025-10-01 21:16:01'),
(10,4,'notification_settings','{\"email\": true, \"push\": true, \"sms\": false}','json','Notification preferences for streams','2025-10-01 21:16:01','2025-10-01 21:16:01'),
(11,3,'notification_settings','{\"email\": true, \"push\": true, \"sms\": false}','json','Notification preferences for streams','2025-10-01 21:16:01','2025-10-01 21:16:01');
/*!40000 ALTER TABLE `live_stream_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `live_stream_subscriptions`
--

DROP TABLE IF EXISTS `live_stream_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `live_stream_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `notification_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '["stream_starting", "stream_live"]' CHECK (json_valid(`notification_types`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_vendor` (`user_id`,`vendor_id`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_stream_subscriptions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stream_subscriptions_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `live_stream_subscriptions`
--

LOCK TABLES `live_stream_subscriptions` WRITE;
/*!40000 ALTER TABLE `live_stream_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `live_stream_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `live_stream_viewers`
--

DROP TABLE IF EXISTS `live_stream_viewers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `live_stream_viewers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stream_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(128) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `joined_at` datetime NOT NULL DEFAULT current_timestamp(),
  `left_at` datetime DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `device_type` enum('desktop','mobile','tablet','tv') DEFAULT NULL,
  `location_country` varchar(2) DEFAULT NULL,
  `location_city` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_stream_id` (`stream_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_joined_at` (`joined_at`),
  CONSTRAINT `fk_stream_viewers_stream` FOREIGN KEY (`stream_id`) REFERENCES `live_streams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stream_viewers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `live_stream_viewers`
--

LOCK TABLES `live_stream_viewers` WRITE;
/*!40000 ALTER TABLE `live_stream_viewers` DISABLE KEYS */;
/*!40000 ALTER TABLE `live_stream_viewers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `live_streams`
--

DROP TABLE IF EXISTS `live_streams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `live_streams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `stream_key` varchar(128) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `stream_url` varchar(500) DEFAULT NULL,
  `chat_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `recording_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `recording_url` varchar(500) DEFAULT NULL,
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `category_id` int(11) DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `status` enum('scheduled','live','ended','cancelled') NOT NULL DEFAULT 'scheduled',
  `viewer_count` int(11) NOT NULL DEFAULT 0,
  `max_viewers` int(11) NOT NULL DEFAULT 0,
  `revenue` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_revenue` decimal(10,2) NOT NULL DEFAULT 0.00,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `stream_key` (`stream_key`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_status` (`status`),
  KEY `idx_scheduled_at` (`scheduled_at`),
  KEY `idx_started_at` (`started_at`),
  KEY `idx_viewer_count` (`viewer_count`),
  KEY `idx_live_streams_status_scheduled` (`status`,`scheduled_at`),
  CONSTRAINT `fk_live_streams_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `live_streams`
--

LOCK TABLES `live_streams` WRITE;
/*!40000 ALTER TABLE `live_streams` DISABLE KEYS */;
/*!40000 ALTER TABLE `live_streams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `identifier` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `user_agent` text DEFAULT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_identifier` (`identifier`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_success` (`success`),
  KEY `idx_attempted_at` (`attempted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=159 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
INSERT INTO `login_attempts` VALUES
(3,'ellyj164@gmail.com','172.69.254.164',1,NULL,'2025-09-11 15:11:33'),
(4,'ellyj164@gmail.com','172.69.254.164',1,NULL,'2025-09-11 15:27:53'),
(5,'ellyj164@gmail.com','172.69.254.164',1,NULL,'2025-09-11 15:33:28'),
(6,'ellyj164@gmail.com','172.69.254.164',1,NULL,'2025-09-11 15:36:42'),
(7,'ellyj164@gmail.com','172.69.254.165',1,NULL,'2025-09-11 15:39:47'),
(8,'ellyj164@gmail.com','172.69.254.164',1,NULL,'2025-09-11 15:41:07'),
(9,'ellyj164@gmail.com','172.69.254.164',1,NULL,'2025-09-11 15:41:10'),
(10,'ellyj164@gmail.com','172.68.42.185',1,NULL,'2025-09-11 17:46:44'),
(11,'ellyj164@gmail.com','197.234.242.181',1,NULL,'2025-09-11 17:50:31'),
(14,'ellyj164@gmail.com','197.234.242.180',1,NULL,'2025-09-14 20:45:42'),
(15,'ellyj164@gmail.com','172.69.254.164',1,NULL,'2025-09-15 15:09:28'),
(16,'ellyj164@gmail.com','197.234.242.180',1,NULL,'2025-09-15 17:22:52'),
(17,'ellyj164@gmail.com','172.68.42.184',1,NULL,'2025-09-15 17:29:35'),
(18,'ellyj164@gmail.com','172.68.42.185',1,NULL,'2025-09-15 18:00:11'),
(19,'ellyj164@gmail.com','197.234.242.180',1,NULL,'2025-09-15 18:08:43'),
(20,'ellyj164@gmail.com','197.234.242.180',1,NULL,'2025-09-15 19:11:16'),
(21,'ellyj164@gmail.com','197.234.242.181',1,NULL,'2025-09-15 19:14:55'),
(22,'ellyj164@gmail.com','197.234.242.181',1,NULL,'2025-09-15 20:19:58'),
(23,'ellyj164@gmail.com','172.68.42.184',1,NULL,'2025-09-15 21:21:19'),
(24,'ellyj164@gmail.com','172.68.42.185',1,NULL,'2025-09-15 22:58:23'),
(25,'ellyj164@gmail.com','172.69.254.163',1,NULL,'2025-09-16 05:50:29'),
(26,'ellyj164@gmail.com','172.69.254.165',1,NULL,'2025-09-16 07:35:24'),
(27,'ellyj164@gmail.com','172.69.254.165',1,NULL,'2025-09-16 08:10:19'),
(28,'ellyj164@gmail.com','172.69.254.164',1,NULL,'2025-09-16 11:25:14'),
(29,'ellyj164@gmail.com','172.69.254.164',1,NULL,'2025-09-16 16:10:15'),
(30,'ellyj164@gmail.com','172.69.254.164',1,NULL,'2025-09-16 16:51:50'),
(31,'ellyj164@gmail.com','172.68.42.185',1,NULL,'2025-09-20 22:20:08'),
(32,'ellyj164@gmail.com','172.68.42.185',1,NULL,'2025-09-20 22:21:19'),
(34,'niyogushimwaj967@gmail.com','197.234.242.180',1,NULL,'2025-09-20 22:27:04'),
(35,'ellyj164@gmail.com','172.68.42.185',1,NULL,'2025-09-20 22:31:22'),
(37,'ellyj164@gmail.com','197.234.242.154',1,NULL,'2025-09-21 06:57:04'),
(38,'ellyj164@gmail.com','172.68.42.184',1,NULL,'2025-09-21 09:07:53'),
(39,'ellyj164@gmail.com','172.69.254.165',1,NULL,'2025-09-27 09:29:06'),
(40,'ellyj164@gmail.com','172.69.254.165',1,NULL,'2025-09-27 12:29:31'),
(41,'ellyj164@gmail.com','172.68.42.184',1,NULL,'2025-09-27 14:20:48'),
(42,'ellyj164@gmail.com','172.69.254.165',1,NULL,'2025-09-27 16:43:44'),
(44,'ellyj164@gmail.com','172.69.254.165',1,NULL,'2025-09-27 17:26:47'),
(45,'ellyj164@gmail.com','172.69.254.164',1,NULL,'2025-09-27 20:44:06'),
(46,'ellyj164@gmail.com','197.234.242.181',1,NULL,'2025-09-28 19:59:25'),
(47,'ellyj164@gmail.com','197.234.242.181',1,NULL,'2025-09-28 19:59:42'),
(48,'ellyj164@gmail.com','197.234.242.180',1,NULL,'2025-09-28 20:01:47'),
(49,'ellyj164@gmail.com','172.68.42.184',1,NULL,'2025-09-28 21:41:55'),
(50,'niyogushimwaj967@gmail.com','197.234.242.181',1,NULL,'2025-09-28 22:06:43'),
(51,'ellyj164@gmail.com','172.68.42.184',1,NULL,'2025-09-29 09:17:37'),
(52,'ellyj164@gmail.com','172.69.254.164',1,NULL,'2025-09-29 10:59:37'),
(53,'ellyj164@gmail.com','172.68.103.104',1,NULL,'2025-09-29 12:03:37'),
(54,'ellyj164@gmail.com','197.234.242.181',1,NULL,'2025-09-29 20:00:41'),
(56,'ellyj164@gmail.com','197.234.242.181',1,NULL,'2025-09-29 20:03:41'),
(57,'ellyj164@gmail.com','172.68.42.185',1,NULL,'2025-09-29 22:23:24'),
(58,'ellyj164@gmail.com','197.234.242.180',1,NULL,'2025-09-29 22:59:28'),
(59,'ellyj164@gmail.com','197.234.242.107',1,NULL,'2025-09-30 06:17:58'),
(61,'ellyj164@gmail.com','172.69.254.164',1,NULL,'2025-09-30 07:43:26'),
(62,'ellyj164@gmail.com','172.69.254.165',1,NULL,'2025-09-30 08:48:23'),
(63,'ellyj164@gmail.com','172.68.42.185',1,NULL,'2025-10-01 20:40:32'),
(64,'ellyj164@gmail.com','197.234.242.106',1,NULL,'2025-10-01 21:41:18'),
(66,'ellyj164@gmail.com','197.234.242.162',1,NULL,'2025-10-01 23:03:58'),
(67,'ellyj164@gmail.com','172.69.254.165',1,NULL,'2025-10-03 13:40:58'),
(68,'ellyj164@gmail.com','172.69.254.165',1,NULL,'2025-10-03 14:37:32'),
(70,'ellyj164@gmail.com','197.234.243.90',1,NULL,'2025-10-03 19:15:40'),
(71,'ellyj164@gmail.com','197.234.243.90',1,NULL,'2025-10-03 20:41:13'),
(73,'ellyj164@gmail.com','197.234.243.90',1,NULL,'2025-10-03 21:46:26'),
(74,'ellyj164@gmail.com','172.68.42.185',1,NULL,'2025-10-04 04:46:31'),
(75,'ellyj164@gmail.com','197.234.243.90',1,NULL,'2025-10-04 05:39:23'),
(76,'ellyj164@gmail.com','172.68.42.184',1,NULL,'2025-10-04 07:03:20'),
(77,'ellyj164@gmail.com','197.234.243.90',1,NULL,'2025-10-04 07:19:38'),
(78,'ellyj164@gmail.com','172.68.42.184',1,NULL,'2025-10-04 09:14:01'),
(79,'ellyj164@gmail.com','197.234.243.89',1,NULL,'2025-10-04 10:18:18'),
(80,'niyogushimwaj967@gmail.com','172.69.254.165',1,NULL,'2025-10-04 10:46:56'),
(82,'ellyj164@gmail.com','197.234.243.90',1,NULL,'2025-10-04 13:04:11'),
(83,'niyogushimwaj967@gmail.com','197.234.243.90',1,NULL,'2025-10-04 14:05:17'),
(84,'ellyj164@gmail.com','197.234.243.89',1,NULL,'2025-10-04 16:14:43'),
(85,'ellyj164@gmail.com','172.68.42.184',1,NULL,'2025-10-04 22:54:31'),
(87,'ellyj164@gmail.com','197.234.243.90',1,NULL,'2025-10-05 10:39:55'),
(88,'ellyj164@gmail.com','172.68.42.185',1,NULL,'2025-10-05 12:45:41'),
(89,'ellyj164@gmail.com','197.234.243.89',1,NULL,'2025-10-05 12:48:44'),
(90,'ellyj164@gmail.com','172.68.42.184',1,NULL,'2025-10-05 14:29:25'),
(91,'ellyj164@gmail.com','172.68.42.10',1,NULL,'2025-10-05 22:46:08'),
(92,'niyogushimwaj967@gmail.com','197.234.243.88',1,NULL,'2025-10-05 23:00:13'),
(93,'ellyj164@gmail.com','172.69.254.165',1,NULL,'2025-10-06 08:44:07'),
(94,'ellyj164@gmail.com','172.69.254.165',1,NULL,'2025-10-06 09:55:14'),
(95,'niyogushimwaj967@gmail.com','172.69.254.164',1,NULL,'2025-10-06 10:52:09'),
(96,'ellyj164@gmail.com','172.69.254.164',1,NULL,'2025-10-06 10:58:02'),
(98,'ellyj164@gmail.com','172.68.42.185',1,NULL,'2025-10-06 11:15:05'),
(99,'ellyj164@gmail.com','172.69.254.165',1,NULL,'2025-10-06 13:22:44'),
(100,'ellyj164@gmail.com','172.69.254.164',1,NULL,'2025-10-06 14:00:25'),
(101,'niyogushimwaj967@gmail.com','172.69.254.165',1,NULL,'2025-10-06 14:01:49'),
(102,'ellyj164@gmail.com','172.68.42.160',1,NULL,'2025-10-06 17:57:17'),
(103,'niyogushimwaj967@gmail.com','172.68.42.160',1,NULL,'2025-10-06 18:01:21'),
(104,'ellyj164@gmail.com','172.68.42.184',1,NULL,'2025-10-06 20:55:51'),
(105,'uninejacky@gmail.com','172.68.42.109',0,NULL,'2025-10-06 22:32:12'),
(106,'ellyj164@gmail.com','197.234.242.126',1,NULL,'2025-10-07 06:48:28'),
(107,'ellyj164@gmail.com','172.68.102.250',1,NULL,'2025-10-07 08:15:54'),
(108,'ellyj164@gmail.com','197.234.242.126',1,NULL,'2025-10-07 14:46:50'),
(111,'ellyj164@gmail.com','172.69.254.163',1,NULL,'2025-10-07 16:59:45'),
(113,'fezamarketgroup@gmail.com','172.69.254.163',1,NULL,'2025-10-07 17:07:32'),
(114,'ellyj164@gmail.com','172.69.254.162',1,NULL,'2025-10-07 17:33:01'),
(116,'ellyj164@gmail.com','172.69.254.162',1,NULL,'2025-10-07 18:51:55'),
(117,'ellyj164@gmail.com','172.68.42.71',1,NULL,'2025-10-07 19:53:10'),
(118,'ellyj164@gmail.com','172.68.42.70',1,NULL,'2025-10-07 20:40:43'),
(119,'ellyj164@gmail.com','172.68.42.49',1,NULL,'2025-10-07 23:52:13'),
(120,'ellyj164@gmail.com','172.68.42.131',1,NULL,'2025-10-08 07:06:55'),
(121,'ellyj164@gmail.com','172.68.42.71',1,NULL,'2025-10-08 08:18:03'),
(122,'ellyj164@gmail.com','197.234.242.127',1,NULL,'2025-10-08 21:00:43'),
(123,'ellyj164@gmail.com','197.234.242.127',1,NULL,'2025-10-08 21:39:22'),
(127,'ellyj164@gmail.com','172.68.42.129',1,NULL,'2025-10-08 22:42:28'),
(128,'ellyj164@gmail.com','197.234.242.96',1,NULL,'2025-10-08 23:43:21'),
(129,'ellyj164@gmail.com','172.68.42.47',1,NULL,'2025-10-09 06:37:27'),
(130,'ellyj164@gmail.com','172.68.42.71',1,NULL,'2025-10-09 07:20:31'),
(132,'ellyj164@gmail.com','172.68.102.251',1,NULL,'2025-10-09 08:15:52'),
(133,'ellyj164@gmail.com','172.69.254.163',1,NULL,'2025-10-09 09:11:32'),
(135,'ellyj164@gmail.com','172.68.47.134',1,NULL,'2025-10-09 11:26:00'),
(136,'amarjit18000@gmail.com','172.68.234.150',0,NULL,'2025-10-09 13:18:24'),
(137,'amarjit18000@gmail.com','172.68.234.150',0,NULL,'2025-10-09 13:18:37'),
(138,'amarjit18000@gmail.com','172.68.234.150',0,NULL,'2025-10-09 13:21:51'),
(139,'amarjit18000@gmail.com','172.70.108.85',0,NULL,'2025-10-09 13:25:15'),
(140,'amarjit18000@gmail.com','162.158.22.160',0,NULL,'2025-10-09 13:26:55'),
(141,'amarjitfatehgarh05@gmail.com','162.158.22.160',1,NULL,'2025-10-09 13:42:04'),
(142,'amarjitfatehgarh05@gmail.com','162.158.22.160',1,NULL,'2025-10-09 13:53:48'),
(143,'ellyj164@gmail.com','172.68.47.140',1,NULL,'2025-10-09 14:28:28'),
(144,'ellyj164@gmail.com','172.68.47.140',1,NULL,'2025-10-09 16:12:36'),
(145,'ellyj164@gmail.com','172.68.102.13',1,NULL,'2025-10-09 16:33:59'),
(146,'ellyj164@gmail.com','172.68.134.59',1,NULL,'2025-10-09 18:05:18'),
(147,'ellyj164@gmail.com','197.234.242.127',1,NULL,'2025-10-09 19:26:00'),
(148,'ellyj164@gmail.com','197.234.242.127',1,NULL,'2025-10-09 21:24:26'),
(149,'ellyj164@gmail.com','197.234.242.97',1,NULL,'2025-10-09 22:37:22'),
(151,'ellyj164@gmail.com','197.234.242.127',1,NULL,'2025-10-10 06:15:19'),
(152,'ellyj164@gmail.com','172.68.42.70',1,NULL,'2025-10-10 06:24:55'),
(153,'ellyj164@gmail.com','172.68.42.71',1,NULL,'2025-10-10 06:53:59'),
(154,'amarjitfatehgarh05@gmail.com','172.68.234.127',1,NULL,'2025-10-10 07:10:41'),
(155,'st.i.l.tbtsb@web.de','104.23.166.59',0,NULL,'2025-10-10 08:13:25'),
(156,'ellyj164@gmail.com','172.68.42.71',1,NULL,'2025-10-10 09:15:06'),
(157,'ellyj164@gmail.com','172.68.102.30',1,NULL,'2025-10-10 13:59:43'),
(158,'ellyj164@gmail.com','172.69.254.162',1,NULL,'2025-10-10 18:04:22');
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loyalty_accounts`
--

DROP TABLE IF EXISTS `loyalty_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loyalty_accounts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `program_name` varchar(100) NOT NULL DEFAULT 'main',
  `current_points` int(11) NOT NULL DEFAULT 0,
  `lifetime_points` int(11) NOT NULL DEFAULT 0,
  `tier` enum('bronze','silver','gold','platinum','diamond') NOT NULL DEFAULT 'bronze',
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_program` (`user_id`,`program_name`),
  KEY `idx_loyalty_tier` (`tier`),
  KEY `idx_loyalty_status` (`status`),
  CONSTRAINT `fk_loyalty_accounts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loyalty_accounts`
--

LOCK TABLES `loyalty_accounts` WRITE;
/*!40000 ALTER TABLE `loyalty_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `loyalty_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loyalty_ledger`
--

DROP TABLE IF EXISTS `loyalty_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loyalty_ledger` (
  `id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `transaction_type` enum('earned','redeemed','expired','adjusted','bonus','penalty') NOT NULL,
  `points` int(11) NOT NULL,
  `balance_after` int(11) NOT NULL,
  `reference_type` enum('order','review','referral','birthday','adjustment','redemption','expiration') DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `description` varchar(500) NOT NULL,
  `expiry_date` timestamp NULL DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_loyalty_account` (`account_id`),
  KEY `idx_loyalty_type` (`transaction_type`),
  KEY `idx_loyalty_created` (`created_at`),
  KEY `idx_loyalty_ledger_processor` (`processed_by`),
  CONSTRAINT `fk_loyalty_ledger_account` FOREIGN KEY (`account_id`) REFERENCES `loyalty_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_loyalty_ledger_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loyalty_ledger`
--

LOCK TABLES `loyalty_ledger` WRITE;
/*!40000 ALTER TABLE `loyalty_ledger` DISABLE KEYS */;
/*!40000 ALTER TABLE `loyalty_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loyalty_redemptions`
--

DROP TABLE IF EXISTS `loyalty_redemptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loyalty_redemptions` (
  `id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `reward_id` int(11) NOT NULL,
  `points_used` int(11) NOT NULL,
  `redemption_value` decimal(10,2) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `status` enum('pending','applied','expired','cancelled') NOT NULL DEFAULT 'pending',
  `redemption_code` varchar(50) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `redeemed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_loyalty_redemption_account` (`account_id`),
  KEY `idx_loyalty_redemption_reward` (`reward_id`),
  KEY `idx_loyalty_redemption_order` (`order_id`),
  KEY `idx_loyalty_redemption_status` (`status`),
  CONSTRAINT `fk_loyalty_redemptions_account` FOREIGN KEY (`account_id`) REFERENCES `loyalty_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_loyalty_redemptions_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_loyalty_redemptions_reward` FOREIGN KEY (`reward_id`) REFERENCES `loyalty_rewards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loyalty_redemptions`
--

LOCK TABLES `loyalty_redemptions` WRITE;
/*!40000 ALTER TABLE `loyalty_redemptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `loyalty_redemptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loyalty_rewards`
--

DROP TABLE IF EXISTS `loyalty_rewards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loyalty_rewards` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `reward_type` enum('discount','free_shipping','product','cashback','custom') NOT NULL,
  `reward_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `points_required` int(11) NOT NULL,
  `max_redemptions` int(11) DEFAULT NULL,
  `current_redemptions` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `valid_from` timestamp NULL DEFAULT NULL,
  `valid_until` timestamp NULL DEFAULT NULL,
  `terms_conditions` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_loyalty_rewards_active` (`is_active`),
  KEY `idx_loyalty_rewards_points` (`points_required`),
  KEY `idx_loyalty_rewards_creator` (`created_by`),
  KEY `idx_loyalty_rewards_type` (`reward_type`),
  CONSTRAINT `fk_loyalty_rewards_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loyalty_rewards`
--

LOCK TABLES `loyalty_rewards` WRITE;
/*!40000 ALTER TABLE `loyalty_rewards` DISABLE KEYS */;
/*!40000 ALTER TABLE `loyalty_rewards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loyalty_settings`
--

DROP TABLE IF EXISTS `loyalty_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loyalty_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_type` enum('string','integer','boolean','json') NOT NULL DEFAULT 'string',
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT 'general',
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_loyalty_setting_key` (`setting_key`),
  KEY `idx_loyalty_settings_category` (`category`),
  KEY `idx_loyalty_settings_user` (`updated_by`),
  CONSTRAINT `fk_loyalty_settings_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loyalty_settings`
--

LOCK TABLES `loyalty_settings` WRITE;
/*!40000 ALTER TABLE `loyalty_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `loyalty_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loyalty_tiers`
--

DROP TABLE IF EXISTS `loyalty_tiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loyalty_tiers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `min_points` int(11) NOT NULL DEFAULT 0,
  `max_points` int(11) DEFAULT NULL,
  `benefits` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`benefits`)),
  `point_multiplier` decimal(3,2) NOT NULL DEFAULT 1.00,
  `icon` varchar(255) DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_min_points` (`min_points`),
  KEY `idx_sort_order` (`sort_order`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loyalty_tiers`
--

LOCK TABLES `loyalty_tiers` WRITE;
/*!40000 ALTER TABLE `loyalty_tiers` DISABLE KEYS */;
INSERT INTO `loyalty_tiers` VALUES
(1,'Bronze','Entry level tier',0,999,'{\"free_shipping_threshold\": 100, \"birthday_bonus\": 50}',1.00,NULL,NULL,1,1,'2025-09-14 19:54:26'),
(2,'Silver','Intermediate tier',1000,4999,'{\"free_shipping_threshold\": 75, \"birthday_bonus\": 100, \"early_access\": true}',1.25,NULL,NULL,2,1,'2025-09-14 19:54:26'),
(3,'Gold','Premium tier',5000,14999,'{\"free_shipping\": true, \"birthday_bonus\": 200, \"early_access\": true, \"priority_support\": true}',1.50,NULL,NULL,3,1,'2025-09-14 19:54:26'),
(4,'Platinum','Elite tier',15000,NULL,'{\"free_shipping\": true, \"birthday_bonus\": 500, \"early_access\": true, \"priority_support\": true, \"exclusive_offers\": true}',2.00,NULL,NULL,4,1,'2025-09-14 19:54:26');
/*!40000 ALTER TABLE `loyalty_tiers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mail_queue`
--

DROP TABLE IF EXISTS `mail_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mail_queue` (
  `id` int(11) NOT NULL,
  `to_email` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `template_name` varchar(100) NOT NULL,
  `template_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`template_data`)),
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `status` enum('pending','sent','failed','retry') NOT NULL DEFAULT 'pending',
  `attempts` int(11) NOT NULL DEFAULT 0,
  `last_attempt` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_mail_status` (`status`),
  KEY `idx_mail_created` (`created_at`),
  KEY `idx_mail_to_email` (`to_email`),
  KEY `idx_mail_template` (`template_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mail_queue`
--

LOCK TABLES `mail_queue` WRITE;
/*!40000 ALTER TABLE `mail_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `mail_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marketing_campaigns`
--

DROP TABLE IF EXISTS `marketing_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `marketing_campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `type` enum('email','sms') NOT NULL DEFAULT 'email',
  `description` text DEFAULT NULL,
  `campaign_type` enum('flash_sale','daily_deal','seasonal','promotion','affiliate','email','social') NOT NULL,
  `status` enum('draft','scheduled','active','paused','completed','cancelled') NOT NULL DEFAULT 'draft',
  `budget` decimal(10,2) DEFAULT NULL,
  `spent_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `target_audience` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_audience`)),
  `discount_type` enum('percentage','fixed','bogo','free_shipping') DEFAULT NULL,
  `discount_value` decimal(10,2) DEFAULT NULL,
  `minimum_order_amount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_limit_per_user` int(11) DEFAULT 1,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `applicable_products` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applicable_products`)),
  `applicable_categories` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applicable_categories`)),
  `tracking_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tracking_data`)),
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_campaign_type` (`campaign_type`),
  KEY `idx_status` (`status`),
  KEY `idx_start_date` (`start_date`),
  KEY `idx_end_date` (`end_date`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `fk_marketing_campaigns_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marketing_campaigns`
--

LOCK TABLES `marketing_campaigns` WRITE;
/*!40000 ALTER TABLE `marketing_campaigns` DISABLE KEYS */;
/*!40000 ALTER TABLE `marketing_campaigns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menus`
--

DROP TABLE IF EXISTS `menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `menus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `location` varchar(50) NOT NULL,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`items`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_location` (`location`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menus`
--

LOCK TABLES `menus` WRITE;
/*!40000 ALTER TABLE `menus` DISABLE KEYS */;
/*!40000 ALTER TABLE `menus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `message_delivery_logs`
--

DROP TABLE IF EXISTS `message_delivery_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_delivery_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` int(11) NOT NULL,
  `event_type` enum('sent','delivered','opened','clicked','bounced','complained','unsubscribed') NOT NULL,
  `event_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `gateway_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gateway_response`)),
  `user_agent` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `additional_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_data`)),
  PRIMARY KEY (`log_id`),
  KEY `idx_message_id` (`message_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_event_timestamp` (`event_timestamp`),
  KEY `idx_delivery_logs_message_event` (`message_id`,`event_type`,`event_timestamp`),
  CONSTRAINT `fk_message_delivery_logs_message` FOREIGN KEY (`message_id`) REFERENCES `comm_messages` (`message_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `message_delivery_logs`
--

LOCK TABLES `message_delivery_logs` WRITE;
/*!40000 ALTER TABLE `message_delivery_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `message_delivery_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `message_templates`
--

DROP TABLE IF EXISTS `message_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_templates` (
  `template_id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('email','sms','push','in_app') NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `content_html` longtext DEFAULT NULL,
  `content_text` longtext DEFAULT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `version` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `category` varchar(100) DEFAULT NULL,
  `language` varchar(5) NOT NULL DEFAULT 'en',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`template_id`),
  KEY `idx_type` (`type`),
  KEY `idx_category` (`category`),
  KEY `idx_language` (`language`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_message_templates_type_active` (`type`,`is_active`,`category`),
  CONSTRAINT `fk_message_templates_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `message_templates`
--

LOCK TABLES `message_templates` WRITE;
/*!40000 ALTER TABLE `message_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `message_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` varchar(100) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `message_type` enum('text','image','file','system') NOT NULL DEFAULT 'text',
  `attachment_url` varchar(500) DEFAULT NULL,
  `attachment_type` varchar(50) DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `parent_message_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_conversation_id` (`conversation_id`),
  KEY `idx_sender_id` (`sender_id`),
  KEY `idx_recipient_id` (`recipient_id`),
  KEY `idx_read_at` (`read_at`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_parent_message_id` (`parent_message_id`),
  CONSTRAINT `fk_messages_parent` FOREIGN KEY (`parent_message_id`) REFERENCES `messages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_messages_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL DEFAULT 1,
  `executed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_filename` (`filename`),
  KEY `idx_batch` (`batch`),
  KEY `idx_executed_at` (`executed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `multi_language_content`
--

DROP TABLE IF EXISTS `multi_language_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `multi_language_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content_type` enum('product','category','cms_page','banner','notification') NOT NULL,
  `content_id` int(11) NOT NULL,
  `language_code` varchar(5) NOT NULL DEFAULT 'en',
  `field_name` varchar(100) NOT NULL,
  `translated_content` longtext NOT NULL,
  `is_auto_translated` tinyint(1) NOT NULL DEFAULT 0,
  `translator_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_content_language_field` (`content_type`,`content_id`,`language_code`,`field_name`),
  KEY `idx_content_type` (`content_type`),
  KEY `idx_content_id` (`content_id`),
  KEY `idx_language_code` (`language_code`),
  KEY `idx_translator_id` (`translator_id`),
  CONSTRAINT `fk_multi_language_content_translator` FOREIGN KEY (`translator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `multi_language_content`
--

LOCK TABLES `multi_language_content` WRITE;
/*!40000 ALTER TABLE `multi_language_content` DISABLE KEYS */;
/*!40000 ALTER TABLE `multi_language_content` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_preferences`
--

DROP TABLE IF EXISTS `notification_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('order','promotion','wishlist','account','system','vendor','live_shopping','security') NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `email_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `push_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `sms_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `frequency` enum('immediate','hourly','daily','weekly') NOT NULL DEFAULT 'immediate',
  `quiet_hours_start` time DEFAULT NULL,
  `quiet_hours_end` time DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_type` (`user_id`,`type`),
  CONSTRAINT `fk_notification_preferences_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
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
-- Table structure for table `notification_settings`
--

DROP TABLE IF EXISTS `notification_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `email_new_order` tinyint(1) NOT NULL DEFAULT 1,
  `email_order_shipped` tinyint(1) NOT NULL DEFAULT 1,
  `email_order_delivered` tinyint(1) NOT NULL DEFAULT 1,
  `email_customer_message` tinyint(1) NOT NULL DEFAULT 1,
  `email_product_review` tinyint(1) NOT NULL DEFAULT 1,
  `email_low_stock` tinyint(1) NOT NULL DEFAULT 1,
  `email_payout_completed` tinyint(1) NOT NULL DEFAULT 1,
  `email_weekly_summary` tinyint(1) NOT NULL DEFAULT 0,
  `email_monthly_report` tinyint(1) NOT NULL DEFAULT 0,
  `sms_new_order` tinyint(1) NOT NULL DEFAULT 0,
  `sms_urgent_alerts` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_vendor` (`vendor_id`),
  CONSTRAINT `fk_notification_settings_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_settings`
--

LOCK TABLES `notification_settings` WRITE;
/*!40000 ALTER TABLE `notification_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_templates`
--

DROP TABLE IF EXISTS `notification_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body_template` text NOT NULL,
  `variables` text DEFAULT NULL COMMENT 'JSON array of available variables',
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_templates`
--

LOCK TABLES `notification_templates` WRITE;
/*!40000 ALTER TABLE `notification_templates` DISABLE KEYS */;
INSERT INTO `notification_templates` VALUES
(1,'order_placed','Order Confirmation','Order #{order_number} Confirmed','Thank you for your order! Your order #{order_number} has been received and is being processed. Total: {total_amount}. Track your order at {order_url}','[\"order_number\",\"total_amount\",\"order_url\",\"customer_name\"]',1,'2025-10-07 14:52:11','2025-10-07 14:52:11'),
(2,'order_shipped','Order Shipped','Your Order #{order_number} Has Shipped!','Great news! Your order #{order_number} has been shipped. Tracking number: {tracking_number}. Expected delivery: {estimated_delivery}. Track at {tracking_url}','[\"order_number\",\"tracking_number\",\"estimated_delivery\",\"tracking_url\"]',1,'2025-10-07 14:52:11','2025-10-07 14:52:11'),
(3,'order_delivered','Order Delivered','Order #{order_number} Delivered','Your order #{order_number} has been delivered! We hope you enjoy your purchase. Leave a review at {review_url}','[\"order_number\",\"review_url\",\"customer_name\"]',1,'2025-10-07 14:52:11','2025-10-07 14:52:11'),
(4,'order_cancelled','Order Cancelled','Order #{order_number} Cancelled','Your order #{order_number} has been cancelled. Refund will be processed within 5-7 business days. Contact support if you have questions.','[\"order_number\",\"refund_amount\",\"customer_name\"]',1,'2025-10-07 14:52:11','2025-10-07 14:52:11'),
(5,'payment_received','Payment Confirmed','Payment Received for Order #{order_number}','We have received your payment of {amount} for order #{order_number}. Thank you!','[\"order_number\",\"amount\",\"payment_method\"]',1,'2025-10-07 14:52:11','2025-10-07 14:52:11'),
(6,'payment_failed','Payment Failed','Payment Failed for Order #{order_number}','Payment for order #{order_number} could not be processed. Please update your payment method at {payment_url}','[\"order_number\",\"amount\",\"payment_url\"]',1,'2025-10-07 14:52:11','2025-10-07 14:52:11'),
(7,'refund_issued','Refund Processed','Refund for Order #{order_number}','A refund of {refund_amount} has been issued for order #{order_number}. It will appear in your account within 5-7 business days.','[\"order_number\",\"refund_amount\"]',1,'2025-10-07 14:52:11','2025-10-07 14:52:11'),
(8,'item_back_in_stock','Item Back in Stock','{product_name} is Back in Stock!','Good news! {product_name} is now back in stock. Get it before it sells out again! Shop now: {product_url}','[\"product_name\",\"product_url\",\"price\"]',1,'2025-10-07 14:52:11','2025-10-07 14:52:11'),
(9,'price_drop','Price Drop Alert','Price Drop: {product_name}','The price of {product_name} has dropped from {old_price} to {new_price}! Save {discount}% now. Shop: {product_url}','[\"product_name\",\"old_price\",\"new_price\",\"discount\",\"product_url\"]',1,'2025-10-07 14:52:11','2025-10-07 14:52:11'),
(10,'wishlist_sale','Wishlist Item on Sale','Item in Your Wishlist is on Sale!','{product_name} from your wishlist is now on sale! Get it for {sale_price} (was {original_price}). Shop now: {product_url}','[\"product_name\",\"sale_price\",\"original_price\",\"product_url\"]',1,'2025-10-07 14:52:11','2025-10-07 14:52:11'),
(11,'abandoned_cart','Cart Reminder','You Left Items in Your Cart','Don\'t forget! You have {item_count} items waiting in your cart. Complete your purchase now: {cart_url}','[\"item_count\",\"cart_url\",\"total_amount\"]',1,'2025-10-07 14:52:11','2025-10-07 14:52:11'),
(12,'welcome','Welcome','Welcome to FezaMarket!','Welcome {customer_name}! Thank you for joining FezaMarket. Start shopping now and enjoy exclusive deals!','[\"customer_name\"]',1,'2025-10-07 14:52:11','2025-10-07 14:52:11'),
(13,'account_verified','Email Verified','Email Verified Successfully','Your email has been verified! You can now enjoy full access to your FezaMarket account.','[\"customer_name\"]',1,'2025-10-07 14:52:11','2025-10-07 14:52:11'),
(14,'password_changed','Password Changed','Your Password Was Changed','Your password has been changed successfully. If you did not make this change, please contact support immediately.','[\"customer_name\"]',1,'2025-10-07 14:52:11','2025-10-07 14:52:11'),
(15,'order_review_request','Review Request','How Was Your Order #{order_number}?','We hope you enjoyed your recent purchase! Please take a moment to review your order #{order_number}. Leave a review: {review_url}','[\"order_number\",\"review_url\",\"product_name\"]',1,'2025-10-07 14:52:11','2025-10-07 14:52:11'),
(16,'promotion','Special Promotion','{promotion_title}','{promotion_message}. Shop now: {promotion_url}','[\"promotion_title\",\"promotion_message\",\"promotion_url\",\"discount_code\"]',1,'2025-10-07 14:52:11','2025-10-07 14:52:11');
/*!40000 ALTER TABLE `notification_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('order','promotion','wishlist','account','system','vendor','live_shopping','security') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `action_url` varchar(500) DEFAULT NULL,
  `action_text` varchar(100) DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `read_at` timestamp NULL DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `expires_at` timestamp NULL DEFAULT NULL,
  `sent_via_email` tinyint(1) NOT NULL DEFAULT 0,
  `sent_via_push` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_read_at` (`read_at`),
  KEY `idx_priority` (`priority`),
  KEY `idx_expires_at` (`expires_at`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
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
-- Table structure for table `offers`
--

DROP TABLE IF EXISTS `offers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `offers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `offer_price` decimal(12,2) NOT NULL,
  `status` enum('pending','accepted','rejected','countered') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `offers_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `offers_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `offers`
--

LOCK TABLES `offers` WRITE;
/*!40000 ALTER TABLE `offers` DISABLE KEYS */;
/*!40000 ALTER TABLE `offers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_disputes`
--

DROP TABLE IF EXISTS `order_disputes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_disputes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `order_item_id` int(11) DEFAULT NULL,
  `buyer_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `dispute_type` enum('product_not_received','product_not_as_described','quality_issue','shipping_damage','refund_request','warranty_claim') NOT NULL,
  `dispute_reason` text NOT NULL,
  `buyer_evidence` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`buyer_evidence`)),
  `vendor_response` text DEFAULT NULL,
  `vendor_evidence` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`vendor_evidence`)),
  `admin_notes` text DEFAULT NULL,
  `status` enum('open','under_review','pending_buyer_response','pending_vendor_response','escalated','resolved','closed') NOT NULL DEFAULT 'open',
  `resolution` enum('refund_full','refund_partial','replacement','repair','no_action','favor_vendor','favor_buyer') DEFAULT NULL,
  `resolution_amount` decimal(10,2) DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `escalated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_order_item_id` (`order_item_id`),
  KEY `idx_buyer_id` (`buyer_id`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_status` (`status`),
  KEY `idx_dispute_type` (`dispute_type`),
  KEY `idx_resolved_by` (`resolved_by`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_order_disputes_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_disputes_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_disputes_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_disputes_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_order_disputes_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_disputes`
--

LOCK TABLES `order_disputes` WRITE;
/*!40000 ALTER TABLE `order_disputes` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_disputes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `status` enum('pending','processing','shipped','delivered','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `tracking_number` varchar(100) DEFAULT NULL,
  `shipped_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_status` (`status`),
  KEY `idx_orders_vendor_status` (`vendor_id`,`status`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_order_items_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_status_history`
--

DROP TABLE IF EXISTS `order_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_status_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `from_status` varchar(50) DEFAULT NULL,
  `to_status` varchar(50) NOT NULL,
  `reason` text DEFAULT NULL,
  `changed_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `notify_customer` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_to_status` (`to_status`),
  KEY `idx_changed_by` (`changed_by`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_order_status_history_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_status_history_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_status_history`
--

LOCK TABLES `order_status_history` WRITE;
/*!40000 ALTER TABLE `order_status_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_status_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_status_logs`
--

DROP TABLE IF EXISTS `order_status_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_status_logs` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `change_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_status_order` (`order_id`),
  KEY `idx_order_status_changed_by` (`changed_by`),
  KEY `idx_order_status_created` (`created_at`),
  CONSTRAINT `fk_order_status_logs_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_status_logs_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_status_logs`
--

LOCK TABLES `order_status_logs` WRITE;
/*!40000 ALTER TABLE `order_status_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_status_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_tracking`
--

DROP TABLE IF EXISTS `order_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_tracking` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `tracking_number` varchar(100) NOT NULL,
  `carrier` varchar(100) NOT NULL,
  `status` enum('label_created','picked_up','in_transit','out_for_delivery','delivered','exception','returned') NOT NULL DEFAULT 'label_created',
  `location` varchar(255) DEFAULT NULL,
  `estimated_delivery` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `tracking_events` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tracking_events`)),
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_tracking_order` (`order_id`),
  KEY `idx_order_tracking_number` (`tracking_number`),
  KEY `idx_order_tracking_status` (`status`),
  CONSTRAINT `fk_order_tracking_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_tracking`
--

LOCK TABLES `order_tracking` WRITE;
/*!40000 ALTER TABLE `order_tracking` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_tracking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_tracking_updates`
--

DROP TABLE IF EXISTS `order_tracking_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_tracking_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `status` varchar(100) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `order_tracking_updates_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_tracking_updates`
--

LOCK TABLES `order_tracking_updates` WRITE;
/*!40000 ALTER TABLE `order_tracking_updates` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_tracking_updates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `status` enum('pending_payment','pending','processing','shipped','delivered','cancelled','refunded','failed') NOT NULL DEFAULT 'pending_payment',
  `payment_status` enum('pending','paid','failed','refunded','partial_refund') NOT NULL DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_transaction_id` varchar(255) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `billing_address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `shipping_address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `shipping_method` varchar(100) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `carrier` varchar(100) DEFAULT NULL,
  `tracking_url` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `shipped_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `refunded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `order_reference` varchar(50) DEFAULT NULL COMMENT 'Human-readable order reference (ORD-YYYYMMDD-######)',
  `amount_minor` int(11) NOT NULL DEFAULT 0 COMMENT 'Total amount in minor units (cents)',
  `tax_minor` int(11) NOT NULL DEFAULT 0 COMMENT 'Tax amount in minor units',
  `shipping_minor` int(11) NOT NULL DEFAULT 0 COMMENT 'Shipping amount in minor units',
  `stripe_payment_intent_id` varchar(255) DEFAULT NULL,
  `stripe_customer_id` varchar(255) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `placed_at` timestamp NULL DEFAULT NULL COMMENT 'When order was placed/paid',
  `stripe_checkout_session_id` varchar(255) DEFAULT NULL COMMENT 'Stripe Checkout Session ID (cs_...)',
  `courier` varchar(100) DEFAULT NULL,
  `expected_delivery` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_order_number` (`order_number`),
  UNIQUE KEY `order_reference` (`order_reference`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_payment_transaction_id` (`payment_transaction_id`),
  KEY `idx_orders_status_created` (`status`,`created_at`),
  KEY `idx_tracking_number` (`tracking_number`),
  KEY `idx_order_reference` (`order_reference`),
  KEY `idx_stripe_payment_intent` (`stripe_payment_intent_id`),
  KEY `idx_stripe_customer` (`stripe_customer_id`),
  KEY `idx_customer_email` (`customer_email`),
  KEY `idx_stripe_checkout_session` (`stripe_checkout_session_id`),
  KEY `idx_user_status` (`user_id`,`status`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `otp_attempts`
--

DROP TABLE IF EXISTS `otp_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `otp_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `token_type` enum('email_verification','password_reset','email_change','two_fa_backup') NOT NULL DEFAULT 'email_verification',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_email` (`email`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_attempted_at` (`attempted_at`),
  KEY `idx_token_type` (`token_type`),
  CONSTRAINT `fk_otp_attempts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `otp_attempts`
--

LOCK TABLES `otp_attempts` WRITE;
/*!40000 ALTER TABLE `otp_attempts` DISABLE KEYS */;
INSERT INTO `otp_attempts` VALUES
(1,5,'niyogushimwaj967@gmail.com','172.68.42.185','2025-09-20 22:24:28',0,'email_verification');
/*!40000 ALTER TABLE `otp_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_events`
--

DROP TABLE IF EXISTS `payment_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) NOT NULL,
  `event_type` enum('created','processed','completed','failed','refunded','disputed','settled') NOT NULL,
  `event_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`event_data`)),
  `gateway_event_id` varchar(255) DEFAULT NULL,
  `webhook_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`webhook_data`)),
  `processed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_payment_id` (`payment_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_gateway_event_id` (`gateway_event_id`),
  KEY `idx_processed` (`processed`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_payment_events_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_events`
--

LOCK TABLES `payment_events` WRITE;
/*!40000 ALTER TABLE `payment_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_gateways`
--

DROP TABLE IF EXISTS `payment_gateways`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_gateways` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `provider` enum('stripe','paypal','square','authorize_net','braintree','razorpay','custom') NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `supported_currencies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`supported_currencies`)),
  `supported_countries` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`supported_countries`)),
  `configuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`configuration`)),
  `test_mode` tinyint(1) NOT NULL DEFAULT 1,
  `transaction_fee_percentage` decimal(5,4) NOT NULL DEFAULT 0.0000,
  `transaction_fee_fixed` decimal(10,2) NOT NULL DEFAULT 0.00,
  `min_amount` decimal(10,2) NOT NULL DEFAULT 0.01,
  `max_amount` decimal(10,2) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`),
  KEY `idx_provider` (`provider`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_is_default` (`is_default`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_gateways`
--

LOCK TABLES `payment_gateways` WRITE;
/*!40000 ALTER TABLE `payment_gateways` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_gateways` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_methods`
--

DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('credit_card','debit_card','paypal','bank_transfer','wallet') NOT NULL,
  `provider` varchar(50) DEFAULT NULL,
  `last_four` varchar(4) DEFAULT NULL,
  `exp_month` tinyint(2) DEFAULT NULL,
  `exp_year` smallint(4) DEFAULT NULL,
  `cardholder_name` varchar(100) DEFAULT NULL,
  `brand` varchar(20) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `fingerprint` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_is_default` (`is_default`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_payment_methods_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_methods`
--

LOCK TABLES `payment_methods` WRITE;
/*!40000 ALTER TABLE `payment_methods` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_reconciliations`
--

DROP TABLE IF EXISTS `payment_reconciliations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_reconciliations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `reconciled_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_reconciled_by_user` (`reconciled_by`),
  CONSTRAINT `fk_reconciled_by_user` FOREIGN KEY (`reconciled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_reconciliations`
--

LOCK TABLES `payment_reconciliations` WRITE;
/*!40000 ALTER TABLE `payment_reconciliations` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_reconciliations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_tokens`
--

DROP TABLE IF EXISTS `payment_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL COMMENT 'Tokenized payment method identifier',
  `gateway` enum('stripe','paypal','flutterwave','mobile_momo','mock') NOT NULL DEFAULT 'stripe' COMMENT 'Payment gateway provider',
  `type` enum('card','bank_account','paypal','mobile_money','crypto') NOT NULL DEFAULT 'card' COMMENT 'Payment method type',
  `last_four` varchar(4) DEFAULT NULL COMMENT 'Last 4 digits for cards',
  `brand` varchar(50) DEFAULT NULL COMMENT 'Card brand or payment method brand',
  `exp_month` tinyint(2) DEFAULT NULL COMMENT 'Card expiration month',
  `exp_year` smallint(4) DEFAULT NULL COMMENT 'Card expiration year',
  `holder_name` varchar(100) DEFAULT NULL COMMENT 'Cardholder or account holder name',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Default payment method for user',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Active status of the token',
  `metadata` text DEFAULT NULL COMMENT 'Additional payment method data (e.g., JSON)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id_idx` (`user_id`),
  CONSTRAINT `fk_payment_tokens_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_tokens`
--

LOCK TABLES `payment_tokens` WRITE;
/*!40000 ALTER TABLE `payment_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL,
  `gateway` varchar(50) NOT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `gateway_transaction_id` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `status` enum('pending','processing','completed','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `gateway_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gateway_response`)),
  `fees` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(10,2) NOT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `refunded_at` timestamp NULL DEFAULT NULL,
  `refund_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_transaction_id` (`transaction_id`),
  KEY `idx_gateway_transaction_id` (`gateway_transaction_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_payments_status_gateway` (`status`,`gateway`,`created_at`),
  CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_payments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payout_requests`
--

DROP TABLE IF EXISTS `payout_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payout_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `wallet_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_amount` decimal(10,2) NOT NULL,
  `processing_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `final_amount` decimal(10,2) NOT NULL,
  `payout_method` enum('bank_transfer','paypal','stripe','wise','check','manual') NOT NULL,
  `payout_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payout_details`)),
  `status` enum('pending','approved','processing','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `approval_notes` text DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `external_transaction_id` varchar(255) DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_wallet_id` (`wallet_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_payout_method` (`payout_method`),
  KEY `idx_processed_by` (`processed_by`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_payout_requests_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_payout_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payout_requests_wallet` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payout_requests`
--

LOCK TABLES `payout_requests` WRITE;
/*!40000 ALTER TABLE `payout_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `payout_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payouts`
--

DROP TABLE IF EXISTS `payouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payouts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payout_request_id` int(11) NOT NULL,
  `batch_id` varchar(100) DEFAULT NULL,
  `gateway_payout_id` varchar(255) DEFAULT NULL,
  `gateway_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gateway_response`)),
  `fees_charged` decimal(10,2) NOT NULL DEFAULT 0.00,
  `exchange_rate` decimal(10,6) DEFAULT NULL,
  `final_amount` decimal(10,2) NOT NULL,
  `final_currency` varchar(3) NOT NULL DEFAULT 'USD',
  `status` enum('initiated','processing','completed','failed','returned') NOT NULL DEFAULT 'initiated',
  `tracking_reference` varchar(255) DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_payout_request_id` (`payout_request_id`),
  KEY `idx_batch_id` (`batch_id`),
  KEY `idx_gateway_payout_id` (`gateway_payout_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_payouts_request` FOREIGN KEY (`payout_request_id`) REFERENCES `payout_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payouts`
--

LOCK TABLES `payouts` WRITE;
/*!40000 ALTER TABLE `payouts` DISABLE KEYS */;
/*!40000 ALTER TABLE `payouts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_permission_name` (`name`),
  KEY `idx_permission_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platform_notification_reads`
--

DROP TABLE IF EXISTS `platform_notification_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `platform_notification_reads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `clicked_at` timestamp NULL DEFAULT NULL,
  `dismissed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_notification_user` (`notification_id`,`user_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_read_at` (`read_at`),
  CONSTRAINT `fk_platform_notification_reads_notification` FOREIGN KEY (`notification_id`) REFERENCES `platform_notifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_platform_notification_reads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platform_notification_reads`
--

LOCK TABLES `platform_notification_reads` WRITE;
/*!40000 ALTER TABLE `platform_notification_reads` DISABLE KEYS */;
/*!40000 ALTER TABLE `platform_notification_reads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platform_notifications`
--

DROP TABLE IF EXISTS `platform_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `platform_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('announcement','maintenance','promotion','warning','info') NOT NULL DEFAULT 'info',
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `target_audience` enum('all','customers','vendors','admins') NOT NULL DEFAULT 'all',
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(7) DEFAULT '#007bff',
  `action_url` varchar(500) DEFAULT NULL,
  `action_text` varchar(100) DEFAULT NULL,
  `is_dismissible` tinyint(1) NOT NULL DEFAULT 1,
  `auto_dismiss_after` int(11) DEFAULT NULL COMMENT 'Auto dismiss after X seconds',
  `status` enum('draft','active','paused','expired') NOT NULL DEFAULT 'draft',
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `total_sent` int(11) NOT NULL DEFAULT 0,
  `total_read` int(11) NOT NULL DEFAULT 0,
  `total_clicked` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`type`),
  KEY `idx_priority` (`priority`),
  KEY `idx_target_audience` (`target_audience`),
  KEY `idx_start_date` (`start_date`),
  KEY `idx_end_date` (`end_date`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `fk_platform_notifications_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platform_notifications`
--

LOCK TABLES `platform_notifications` WRITE;
/*!40000 ALTER TABLE `platform_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `platform_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_analytics`
--

DROP TABLE IF EXISTS `product_analytics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_analytics` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `metric_date` date NOT NULL,
  `views` int(11) NOT NULL DEFAULT 0,
  `clicks` int(11) NOT NULL DEFAULT 0,
  `conversions` int(11) NOT NULL DEFAULT 0,
  `revenue` decimal(15,2) NOT NULL DEFAULT 0.00,
  `profit_margin` decimal(5,2) DEFAULT NULL,
  `competitor_price` decimal(10,2) DEFAULT NULL,
  `search_ranking` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_analytics_product_date` (`product_id`,`metric_date`),
  CONSTRAINT `fk_product_analytics_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_analytics`
--

LOCK TABLES `product_analytics` WRITE;
/*!40000 ALTER TABLE `product_analytics` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_analytics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_approvals`
--

DROP TABLE IF EXISTS `product_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected','revision_requested') NOT NULL DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_product_id` (`product_id`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_status` (`status`),
  KEY `idx_reviewed_by` (`reviewed_by`),
  KEY `idx_submitted_at` (`submitted_at`),
  CONSTRAINT `fk_product_approvals_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_approvals_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_product_approvals_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_approvals`
--

LOCK TABLES `product_approvals` WRITE;
/*!40000 ALTER TABLE `product_approvals` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_approvals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_attributes`
--

DROP TABLE IF EXISTS `product_attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_attributes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `value` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `attr_key` varchar(100) DEFAULT NULL,
  `attr_value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_product_attributes_product_id` (`product_id`),
  CONSTRAINT `fk_attributes_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_attributes`
--

LOCK TABLES `product_attributes` WRITE;
/*!40000 ALTER TABLE `product_attributes` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_attributes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_audit_logs`
--

DROP TABLE IF EXISTS `product_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `reason` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_product_audit_logs_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_audit_logs`
--

LOCK TABLES `product_audit_logs` WRITE;
/*!40000 ALTER TABLE `product_audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_autosaves`
--

DROP TABLE IF EXISTS `product_autosaves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_autosaves` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(10) unsigned NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `slug` varchar(275) DEFAULT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `brand` varchar(160) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `compare_price` decimal(10,2) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `currency_code` char(3) DEFAULT 'USD',
  `stock_qty` int(11) DEFAULT NULL,
  `low_stock_threshold` int(11) DEFAULT NULL,
  `track_inventory` tinyint(1) DEFAULT 1,
  `allow_backorder` tinyint(1) DEFAULT 0,
  `condition` enum('new','used','refurbished') DEFAULT 'new',
  `tags` text DEFAULT NULL,
  `weight_kg` decimal(10,3) DEFAULT NULL,
  `length_cm` decimal(10,2) DEFAULT NULL,
  `width_cm` decimal(10,2) DEFAULT NULL,
  `height_cm` decimal(10,2) DEFAULT NULL,
  `seo_title` varchar(70) DEFAULT NULL,
  `seo_description` varchar(170) DEFAULT NULL,
  `seo_keywords` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_seller_id` (`seller_id`),
  KEY `idx_updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_autosaves`
--

LOCK TABLES `product_autosaves` WRITE;
/*!40000 ALTER TABLE `product_autosaves` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_autosaves` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_bulk_operations`
--

DROP TABLE IF EXISTS `product_bulk_operations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_bulk_operations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `operation_type` varchar(20) NOT NULL COMMENT 'import | export | update | delete',
  `file_path` varchar(500) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending | processing | completed | failed',
  `total_records` int(11) NOT NULL DEFAULT 0,
  `processed_records` int(11) NOT NULL DEFAULT 0,
  `error_records` int(11) NOT NULL DEFAULT 0,
  `error_log` text DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_bulk_ops_user_id` (`user_id`),
  CONSTRAINT `fk_product_bulk_ops_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_bulk_operations`
--

LOCK TABLES `product_bulk_operations` WRITE;
/*!40000 ALTER TABLE `product_bulk_operations` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_bulk_operations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_bulk_uploads`
--

DROP TABLE IF EXISTS `product_bulk_uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_bulk_uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `total_rows` int(11) NOT NULL DEFAULT 0,
  `processed_rows` int(11) NOT NULL DEFAULT 0,
  `successful_rows` int(11) NOT NULL DEFAULT 0,
  `failed_rows` int(11) NOT NULL DEFAULT 0,
  `status` enum('pending','processing','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `error_log` longtext DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `processing_started_at` timestamp NULL DEFAULT NULL,
  `processing_completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_product_bulk_uploads_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_bulk_uploads`
--

LOCK TABLES `product_bulk_uploads` WRITE;
/*!40000 ALTER TABLE `product_bulk_uploads` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_bulk_uploads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_categories`
--

DROP TABLE IF EXISTS `product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_categories` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`product_id`,`category_id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_product_id` (`product_id`),
  CONSTRAINT `fk_pc_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pc_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_categories`
--

LOCK TABLES `product_categories` WRITE;
/*!40000 ALTER TABLE `product_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_certificates`
--

DROP TABLE IF EXISTS `product_certificates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_certificates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `certificate_type` varchar(100) DEFAULT NULL,
  `certificate_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `issuing_authority` varchar(255) DEFAULT NULL,
  `certificate_number` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_certificates_product_id` (`product_id`),
  CONSTRAINT `fk_product_certificates_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_certificates`
--

LOCK TABLES `product_certificates` WRITE;
/*!40000 ALTER TABLE `product_certificates` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_certificates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_drafts`
--

DROP TABLE IF EXISTS `product_drafts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_drafts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `draft_name` varchar(255) DEFAULT NULL,
  `product_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`product_data`)),
  `auto_save` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_drafts_user_id` (`user_id`),
  CONSTRAINT `fk_product_drafts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_drafts`
--

LOCK TABLES `product_drafts` WRITE;
/*!40000 ALTER TABLE `product_drafts` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_drafts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `file_path` varchar(500) DEFAULT NULL,
  `sort` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_images`
--

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
INSERT INTO `product_images` VALUES
(9,5,'/uploads/products/2025/10/img_1759572748_48954b57dc882d4c.jpg',NULL,1,'2025-10-04 10:12:28','2025-10-04 10:12:28','/uploads/products/2025/10/img_1759572748_48954b57dc882d4c.jpg',0),
(10,5,'/uploads/products/2025/10/img_1759572748_5f2ca0fe2875d7d7.jpg',NULL,0,'2025-10-04 10:12:28','2025-10-04 10:12:28','/uploads/products/2025/10/img_1759572748_5f2ca0fe2875d7d7.jpg',0),
(11,5,'/uploads/products/2025/10/img_1759572748_3647b7c6a5f60b30.jpg',NULL,0,'2025-10-04 10:12:28','2025-10-04 10:12:28','/uploads/products/2025/10/img_1759572748_3647b7c6a5f60b30.jpg',0),
(12,6,'/uploads/products/2025/10/img_1759573981_502b782b6f37f2ac.jpg',NULL,1,'2025-10-04 10:33:01','2025-10-04 10:33:01','/uploads/products/2025/10/img_1759573981_502b782b6f37f2ac.jpg',0),
(13,6,'/uploads/products/2025/10/img_1759573981_df60ec347386bb3d.webp',NULL,0,'2025-10-04 10:33:01','2025-10-04 10:33:01','/uploads/products/2025/10/img_1759573981_df60ec347386bb3d.webp',0),
(14,6,'/uploads/products/2025/10/img_1759573981_418c63c7d04cb91c.jpg',NULL,0,'2025-10-04 10:33:01','2025-10-04 10:33:01','/uploads/products/2025/10/img_1759573981_418c63c7d04cb91c.jpg',0),
(15,6,'/uploads/products/2025/10/img_1759573981_6c6205d2d6cbe659.jpg',NULL,0,'2025-10-04 10:33:01','2025-10-04 10:33:01','/uploads/products/2025/10/img_1759573981_6c6205d2d6cbe659.jpg',0);
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_inventory`
--

DROP TABLE IF EXISTS `product_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_inventory` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `reserved_quantity` int(11) NOT NULL DEFAULT 0,
  `low_stock_threshold` int(11) NOT NULL DEFAULT 5,
  `out_of_stock_threshold` int(11) NOT NULL DEFAULT 0,
  `backorder_limit` int(11) DEFAULT NULL,
  `reorder_point` int(11) DEFAULT NULL,
  `reorder_quantity` int(11) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_inventory_product_id` (`product_id`),
  KEY `idx_product_inventory_sku` (`sku`),
  CONSTRAINT `fk_product_inventory_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_inventory`
--

LOCK TABLES `product_inventory` WRITE;
/*!40000 ALTER TABLE `product_inventory` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_media`
--

DROP TABLE IF EXISTS `product_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `media_type` varchar(20) NOT NULL DEFAULT 'image' COMMENT 'image | video | 360_image',
  `file_path` varchar(500) DEFAULT NULL,
  `thumbnail_path` varchar(500) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `youtube_url` varchar(500) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_thumbnail` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_media_product_id` (`product_id`),
  KEY `idx_product_media_type` (`media_type`),
  CONSTRAINT `fk_product_media_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_media`
--

LOCK TABLES `product_media` WRITE;
/*!40000 ALTER TABLE `product_media` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_media` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_offers`
--

DROP TABLE IF EXISTS `product_offers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_offers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `offer_price` decimal(10,2) NOT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','approved','countered','declined','expired') NOT NULL DEFAULT 'pending',
  `counter_price` decimal(10,2) DEFAULT NULL,
  `admin_message` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_processed_by` (`processed_by`),
  CONSTRAINT `product_offers_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_offers_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_offers`
--

LOCK TABLES `product_offers` WRITE;
/*!40000 ALTER TABLE `product_offers` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_offers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_pricing`
--

DROP TABLE IF EXISTS `product_pricing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_pricing` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `sale_start_date` datetime DEFAULT NULL,
  `sale_end_date` datetime DEFAULT NULL,
  `bulk_pricing` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`bulk_pricing`)),
  `tier_pricing` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tier_pricing`)),
  `currency_code` char(3) NOT NULL DEFAULT 'USD',
  `tax_class` varchar(50) DEFAULT NULL,
  `margin_percentage` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_pricing_product_id` (`product_id`),
  CONSTRAINT `fk_product_pricing_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_pricing`
--

LOCK TABLES `product_pricing` WRITE;
/*!40000 ALTER TABLE `product_pricing` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_pricing` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_recommendations`
--

DROP TABLE IF EXISTS `product_recommendations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_recommendations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `recommended_product_id` int(11) NOT NULL,
  `type` enum('viewed_together','bought_together','similar','complementary','trending') NOT NULL,
  `score` decimal(5,4) NOT NULL DEFAULT 0.0000,
  `algorithm` varchar(50) DEFAULT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `clicked` tinyint(1) NOT NULL DEFAULT 0,
  `purchased` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_product_recommended` (`user_id`,`product_id`,`recommended_product_id`,`type`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_recommended_product_id` (`recommended_product_id`),
  KEY `idx_type` (`type`),
  KEY `idx_score` (`score`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `fk_product_recommendations_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_recommendations_recommended` FOREIGN KEY (`recommended_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_recommendations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_recommendations`
--

LOCK TABLES `product_recommendations` WRITE;
/*!40000 ALTER TABLE `product_recommendations` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_recommendations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_related`
--

DROP TABLE IF EXISTS `product_related`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_related` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `related_product_id` int(11) NOT NULL,
  `relation_type` varchar(50) NOT NULL DEFAULT 'related',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_product_related_unique` (`product_id`,`related_product_id`,`relation_type`),
  KEY `idx_product_related_product` (`product_id`),
  KEY `idx_product_related_related` (`related_product_id`),
  KEY `idx_product_related_type` (`relation_type`),
  CONSTRAINT `fk_product_related_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_related_related_product` FOREIGN KEY (`related_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_related`
--

LOCK TABLES `product_related` WRITE;
/*!40000 ALTER TABLE `product_related` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_related` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_relations`
--

DROP TABLE IF EXISTS `product_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_relations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `related_product_id` int(11) NOT NULL,
  `relation_type` varchar(20) NOT NULL COMMENT 'cross_sell | upsell | related | bundle',
  `priority` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_relations_product_id` (`product_id`),
  KEY `idx_product_relations_related_product_id` (`related_product_id`),
  KEY `idx_product_relations_type` (`relation_type`),
  CONSTRAINT `fk_product_relations_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_relations_related_product` FOREIGN KEY (`related_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_relations`
--

LOCK TABLES `product_relations` WRITE;
/*!40000 ALTER TABLE `product_relations` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_relations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_reviews`
--

DROP TABLE IF EXISTS `product_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` between 1 and 5),
  `title` varchar(255) DEFAULT NULL,
  `review_text` text DEFAULT NULL,
  `pros` text DEFAULT NULL,
  `cons` text DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `status` enum('pending','approved','rejected','spam') NOT NULL DEFAULT 'pending',
  `moderated_by` int(11) DEFAULT NULL,
  `moderation_notes` text DEFAULT NULL,
  `helpful_votes` int(11) NOT NULL DEFAULT 0,
  `verified_purchase` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_rating` (`rating`),
  KEY `idx_status` (`status`),
  KEY `fk_product_reviews_moderator` (`moderated_by`),
  CONSTRAINT `fk_product_reviews_moderator` FOREIGN KEY (`moderated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_product_reviews_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_product_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
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
-- Table structure for table `product_seo`
--

DROP TABLE IF EXISTS `product_seo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_seo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `meta_title` varchar(60) DEFAULT NULL,
  `meta_description` varchar(160) DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `focus_keyword` varchar(100) DEFAULT NULL,
  `canonical_url` varchar(500) DEFAULT NULL,
  `og_title` varchar(60) DEFAULT NULL,
  `og_description` varchar(160) DEFAULT NULL,
  `og_image` varchar(500) DEFAULT NULL,
  `twitter_title` varchar(60) DEFAULT NULL,
  `twitter_description` varchar(160) DEFAULT NULL,
  `schema_markup` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`schema_markup`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_seo_product_id` (`product_id`),
  CONSTRAINT `fk_product_seo_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_seo`
--

LOCK TABLES `product_seo` WRITE;
/*!40000 ALTER TABLE `product_seo` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_seo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_shipping`
--

DROP TABLE IF EXISTS `product_shipping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_shipping` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `weight` decimal(10,3) DEFAULT NULL,
  `length` decimal(10,2) DEFAULT NULL,
  `width` decimal(10,2) DEFAULT NULL,
  `height` decimal(10,2) DEFAULT NULL,
  `shipping_class` varchar(50) DEFAULT NULL,
  `handling_time` int(11) NOT NULL DEFAULT 1,
  `free_shipping` tinyint(1) NOT NULL DEFAULT 0,
  `shipping_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`shipping_rules`)),
  `hs_code` varchar(20) DEFAULT NULL,
  `country_of_origin` char(2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_shipping_product_id` (`product_id`),
  CONSTRAINT `fk_product_shipping_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_shipping`
--

LOCK TABLES `product_shipping` WRITE;
/*!40000 ALTER TABLE `product_shipping` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_shipping` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_tag`
--

DROP TABLE IF EXISTS `product_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_tag` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_tag`
--

LOCK TABLES `product_tag` WRITE;
/*!40000 ALTER TABLE `product_tag` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_tag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_variants` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `stock_qty` int(11) DEFAULT NULL,
  `attributes_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attributes_json`)),
  `image_path` varchar(512) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `variant_options_json` longtext DEFAULT NULL,
  `option_name` varchar(100) DEFAULT NULL,
  `option_value` varchar(100) DEFAULT NULL,
  `price_delta` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `idx_product_variants_product_id` (`product_id`),
  CONSTRAINT `fk_variants_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variants`
--

LOCK TABLES `product_variants` WRITE;
/*!40000 ALTER TABLE `product_variants` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_variants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_views`
--

DROP TABLE IF EXISTS `product_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `referrer` varchar(500) DEFAULT NULL,
  `view_duration` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_ip_address` (`ip_address`),
  CONSTRAINT `fk_product_views_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_views_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_views`
--

LOCK TABLES `product_views` WRITE;
/*!40000 ALTER TABLE `product_views` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_views` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_views_detailed`
--

DROP TABLE IF EXISTS `product_views_detailed`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_views_detailed` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `referrer` text DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_product_views_date` (`product_id`,`viewed_at`),
  KEY `idx_views_session` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Detailed product view tracking for analytics';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_views_detailed`
--

LOCK TABLES `product_views_detailed` WRITE;
/*!40000 ALTER TABLE `product_views_detailed` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_views_detailed` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(10) unsigned DEFAULT NULL,
  `vendor_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `brand_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(275) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `short_description` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `compare_price` decimal(12,2) DEFAULT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `currency_code` char(3) NOT NULL DEFAULT 'USD',
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `min_stock_level` int(11) NOT NULL DEFAULT 5,
  `max_stock_level` int(11) DEFAULT NULL,
  `weight` decimal(8,2) DEFAULT NULL,
  `dimensions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dimensions`)),
  `status` enum('active','inactive','draft','archived') NOT NULL DEFAULT 'draft',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_ai_recommended` tinyint(1) NOT NULL DEFAULT 0,
  `is_digital` tinyint(1) NOT NULL DEFAULT 0,
  `digital_delivery_info` text DEFAULT NULL,
  `visibility` enum('public','private','hidden') NOT NULL DEFAULT 'public',
  `track_inventory` tinyint(1) NOT NULL DEFAULT 1,
  `allow_backorder` tinyint(1) NOT NULL DEFAULT 0,
  `stock_qty` int(11) DEFAULT NULL,
  `low_stock_threshold` int(11) DEFAULT NULL,
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `digital` tinyint(1) NOT NULL DEFAULT 0,
  `downloadable` tinyint(1) NOT NULL DEFAULT 0,
  `virtual` tinyint(1) NOT NULL DEFAULT 0,
  `tags` text DEFAULT NULL,
  `attributes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attributes`)),
  `variations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variations`)),
  `shipping_class` varchar(50) DEFAULT NULL,
  `weight_kg` decimal(10,3) DEFAULT NULL,
  `length_cm` decimal(10,2) DEFAULT NULL,
  `width_cm` decimal(10,2) DEFAULT NULL,
  `height_cm` decimal(10,2) DEFAULT NULL,
  `seo_title` varchar(70) DEFAULT NULL,
  `seo_description` varchar(170) DEFAULT NULL,
  `seo_keywords` varchar(255) DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `return_policy_text` text DEFAULT NULL,
  `warranty_text` text DEFAULT NULL,
  `compliance_notes` text DEFAULT NULL,
  `age_restriction` tinyint(1) NOT NULL DEFAULT 0,
  `digital_is` tinyint(1) NOT NULL DEFAULT 0,
  `digital_url` varchar(512) DEFAULT NULL,
  `digital_file_path` varchar(512) DEFAULT NULL,
  `thumbnail_path` varchar(512) DEFAULT NULL,
  `custom_barcode` varchar(64) DEFAULT NULL,
  `mpn` varchar(64) DEFAULT NULL,
  `gtin` varchar(64) DEFAULT NULL,
  `condition` enum('new','used','refurbished') NOT NULL DEFAULT 'new',
  `brand` varchar(160) DEFAULT NULL,
  `tax_status` enum('taxable','shipping','none') NOT NULL DEFAULT 'taxable',
  `tax_class` varchar(50) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `keywords` text DEFAULT NULL,
  `view_count` int(11) NOT NULL DEFAULT 0,
  `sales_count` int(11) NOT NULL DEFAULT 0,
  `purchase_count` int(11) NOT NULL DEFAULT 0,
  `average_rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `review_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_sku` (`sku`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_status` (`status`),
  KEY `idx_featured` (`featured`),
  KEY `idx_price` (`price`),
  KEY `idx_name` (`name`),
  KEY `idx_slug` (`slug`),
  KEY `idx_stock_quantity` (`stock_quantity`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_products_status_featured` (`status`,`featured`),
  KEY `idx_products_vendor_status` (`vendor_id`,`status`),
  KEY `idx_products_brand` (`brand_id`),
  KEY `idx_products_keywords` (`keywords`(255)),
  KEY `idx_is_digital` (`is_digital`),
  KEY `idx_is_ai_recommended` (`is_ai_recommended`,`status`),
  KEY `idx_view_count` (`view_count` DESC),
  KEY `idx_sales_count` (`sales_count` DESC),
  FULLTEXT KEY `idx_search` (`name`,`description`,`tags`),
  CONSTRAINT `fk_products_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `fk_products_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Products table with AI recommendations, view tracking, and sales tracking';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES
(5,NULL,3,5,NULL,'Iphone 16 Promax','iphone-16-promax-','this is iphone in the phones of the fnn','this is iphone in the phones of the fnn','/uploads/products/2025/10/img_1759572748_48954b57dc882d4c.jpg','Iphone sii',NULL,1700.00,NULL,NULL,NULL,'USD',1005,5,NULL,NULL,NULL,'active',0,0,0,NULL,'public',1,1,NULL,5,0,0,0,0,'',NULL,NULL,'standard',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,'new',NULL,'taxable',NULL,'','',NULL,0,0,0,0.00,0,'2025-10-04 08:12:28','2025-10-04 10:12:28'),
(6,NULL,3,1003,22,'TOYOTA HYBRID 2025','toyota-hybrid-2025','TOYOTA HYBRID 2025','TOYOTA HYBRID 2025','/uploads/products/2025/10/img_1759573981_502b782b6f37f2ac.jpg','XNN',NULL,5600.00,NULL,NULL,NULL,'USD',1000,5,NULL,NULL,NULL,'active',0,0,0,'','public',1,0,NULL,5,0,0,0,0,'',NULL,NULL,'express',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,'new',NULL,'taxable',NULL,'','',NULL,0,0,0,0.00,0,'2025-10-04 08:33:01','2025-10-04 10:33:01');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `push_subscriptions`
--

DROP TABLE IF EXISTS `push_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `push_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `endpoint` varchar(500) NOT NULL,
  `p256dh_key` varchar(255) NOT NULL,
  `auth_token` varchar(255) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_used` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_endpoint` (`endpoint`(255)),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_last_used` (`last_used`),
  CONSTRAINT `fk_push_subscriptions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `push_subscriptions`
--

LOCK TABLES `push_subscriptions` WRITE;
/*!40000 ALTER TABLE `push_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `push_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reconciliations`
--

DROP TABLE IF EXISTS `reconciliations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reconciliations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reconciliation_date` date NOT NULL,
  `gateway` varchar(50) NOT NULL,
  `total_transactions` int(11) NOT NULL DEFAULT 0,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_fees` decimal(15,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','in_progress','completed','failed','manual_review') NOT NULL DEFAULT 'pending',
  `discrepancies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`discrepancies`)),
  `gateway_report_path` varchar(500) DEFAULT NULL,
  `reconciled_by` int(11) DEFAULT NULL,
  `reconciled_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_date_gateway` (`reconciliation_date`,`gateway`),
  KEY `idx_gateway` (`gateway`),
  KEY `idx_status` (`status`),
  KEY `idx_reconciled_by` (`reconciled_by`),
  CONSTRAINT `fk_reconciliations_user` FOREIGN KEY (`reconciled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reconciliations`
--

LOCK TABLES `reconciliations` WRITE;
/*!40000 ALTER TABLE `reconciliations` DISABLE KEYS */;
/*!40000 ALTER TABLE `reconciliations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `redirects`
--

DROP TABLE IF EXISTS `redirects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `redirects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_url` varchar(500) NOT NULL,
  `to_url` varchar(500) NOT NULL,
  `redirect_type` enum('301','302','307','308') NOT NULL DEFAULT '301',
  `reason` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `hit_count` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_from_url` (`from_url`),
  KEY `idx_to_url` (`to_url`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `fk_redirects_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `redirects`
--

LOCK TABLES `redirects` WRITE;
/*!40000 ALTER TABLE `redirects` DISABLE KEYS */;
/*!40000 ALTER TABLE `redirects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `refunds`
--

DROP TABLE IF EXISTS `refunds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `refunds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `order_item_id` int(11) DEFAULT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `refund_amount` decimal(10,2) NOT NULL,
  `refund_reason` enum('customer_request','defective_product','wrong_item','damaged_shipping','cancelled_order','dispute_resolution','admin_decision') NOT NULL,
  `refund_method` enum('original_payment','store_credit','bank_transfer','manual') NOT NULL DEFAULT 'original_payment',
  `status` enum('pending','processing','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `external_refund_id` varchar(255) DEFAULT NULL,
  `processor_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`processor_response`)),
  `admin_notes` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_order_item_id` (`order_item_id`),
  KEY `idx_transaction_id` (`transaction_id`),
  KEY `idx_status` (`status`),
  KEY `idx_refund_method` (`refund_method`),
  KEY `idx_processed_by` (`processed_by`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_refunds_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_refunds_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_refunds_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_refunds_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `refunds`
--

LOCK TABLES `refunds` WRITE;
/*!40000 ALTER TABLE `refunds` DISABLE KEYS */;
/*!40000 ALTER TABLE `refunds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `report_jobs`
--

DROP TABLE IF EXISTS `report_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `report_type` enum('sales','users','inventory','financial','marketing','custom') NOT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `format` enum('csv','excel','pdf','json') NOT NULL DEFAULT 'csv',
  `schedule` varchar(100) DEFAULT NULL,
  `email_recipients` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`email_recipients`)),
  `file_path` varchar(500) DEFAULT NULL,
  `status` enum('queued','running','completed','failed') NOT NULL DEFAULT 'queued',
  `progress` int(11) NOT NULL DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_report_type` (`report_type`),
  KEY `idx_status` (`status`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_report_jobs_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_jobs`
--

LOCK TABLES `report_jobs` WRITE;
/*!40000 ALTER TABLE `report_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `report_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `returns`
--

DROP TABLE IF EXISTS `returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `returns` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `return_number` varchar(50) NOT NULL,
  `reason` enum('defective','wrong_item','damaged','not_as_described','change_of_mind','warranty','other') NOT NULL,
  `status` enum('requested','approved','rejected','shipped','received','refunded','completed','cancelled') NOT NULL DEFAULT 'requested',
  `description` text DEFAULT NULL,
  `return_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `refund_amount` decimal(10,2) DEFAULT NULL,
  `return_tracking` varchar(100) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `customer_notes` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_return_number` (`return_number`),
  KEY `idx_return_order` (`order_id`),
  KEY `idx_return_user` (`user_id`),
  KEY `idx_return_vendor` (`vendor_id`),
  KEY `idx_return_status` (`status`),
  KEY `idx_return_created` (`created_at`),
  KEY `idx_return_processed_by` (`processed_by`),
  CONSTRAINT `fk_returns_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_returns_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_returns_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_returns_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `returns`
--

LOCK TABLES `returns` WRITE;
/*!40000 ALTER TABLE `returns` DISABLE KEYS */;
/*!40000 ALTER TABLE `returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `review_helpfulness`
--

DROP TABLE IF EXISTS `review_helpfulness`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_helpfulness` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_helpful` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_review_user` (`review_id`,`user_id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_review_helpfulness_review` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_helpfulness_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `review_helpfulness`
--

LOCK TABLES `review_helpfulness` WRITE;
/*!40000 ALTER TABLE `review_helpfulness` DISABLE KEYS */;
/*!40000 ALTER TABLE `review_helpfulness` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `order_item_id` int(11) DEFAULT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` between 1 and 5),
  `title` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `pros` text DEFAULT NULL,
  `cons` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','hidden') NOT NULL DEFAULT 'pending',
  `helpful_count` int(11) NOT NULL DEFAULT 0,
  `unhelpful_count` int(11) NOT NULL DEFAULT 0,
  `verified_purchase` tinyint(1) NOT NULL DEFAULT 0,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `admin_response` text DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `responded_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_order_item_id` (`order_item_id`),
  KEY `idx_status` (`status`),
  KEY `idx_rating` (`rating`),
  KEY `idx_verified_purchase` (`verified_purchase`),
  KEY `idx_created_at` (`created_at`),
  KEY `fk_reviews_responder` (`responded_by`),
  KEY `fk_reviews_approver` (`approved_by`),
  CONSTRAINT `fk_reviews_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_reviews_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_responder` FOREIGN KEY (`responded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_role_permission` (`role_id`,`permission_id`),
  KEY `idx_role_permissions_permission` (`permission_id`),
  CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `level` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_role_name` (`name`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_role_active` (`is_active`),
  KEY `idx_role_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `saved_streams`
--

DROP TABLE IF EXISTS `saved_streams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `saved_streams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `stream_title` varchar(255) NOT NULL,
  `stream_description` text DEFAULT NULL,
  `video_url` varchar(255) NOT NULL COMMENT 'URL or path to the saved video file',
  `thumbnail_url` varchar(255) DEFAULT NULL COMMENT 'URL or path to the video thumbnail',
  `duration` int(11) DEFAULT 0 COMMENT 'Duration of the video in seconds',
  `views` int(11) DEFAULT 0,
  `likes` int(11) DEFAULT 0,
  `dislikes` int(11) DEFAULT 0,
  `streamed_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'When the stream was originally live',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `seller_id_idx` (`seller_id`),
  CONSTRAINT `fk_saved_streams_seller_id` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saved_streams`
--

LOCK TABLES `saved_streams` WRITE;
/*!40000 ALTER TABLE `saved_streams` DISABLE KEYS */;
/*!40000 ALTER TABLE `saved_streams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scheduled_streams`
--

DROP TABLE IF EXISTS `scheduled_streams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `scheduled_streams` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `scheduled_start` datetime NOT NULL,
  `scheduled_end` datetime DEFAULT NULL,
  `estimated_duration` int(11) DEFAULT NULL COMMENT 'Duration in minutes',
  `featured_products` text DEFAULT NULL COMMENT 'JSON array of product IDs',
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `status` enum('scheduled','live','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `notification_sent` tinyint(1) NOT NULL DEFAULT 0,
  `reminder_sent` tinyint(1) NOT NULL DEFAULT 0,
  `actual_start` datetime DEFAULT NULL,
  `actual_end` datetime DEFAULT NULL,
  `viewer_count` int(11) DEFAULT 0,
  `peak_viewers` int(11) DEFAULT 0,
  `total_sales` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_scheduled_start` (`scheduled_start`),
  KEY `idx_status` (`status`),
  KEY `idx_vendor_status` (`vendor_id`,`status`),
  CONSTRAINT `fk_ss_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scheduled_streams`
--

LOCK TABLES `scheduled_streams` WRITE;
/*!40000 ALTER TABLE `scheduled_streams` DISABLE KEYS */;
INSERT INTO `scheduled_streams` VALUES
(1,3,'FASHION SELLING DIAMOND','Let\'s sell the fashion of this case now and on','2025-10-11 15:09:00',NULL,60,'[]',NULL,'scheduled',0,0,NULL,NULL,0,0,0.00,'2025-10-04 13:07:49','2025-10-04 13:07:49');
/*!40000 ALTER TABLE `scheduled_streams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `search_queries`
--

DROP TABLE IF EXISTS `search_queries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `search_queries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `query` varchar(500) NOT NULL,
  `results_count` int(11) NOT NULL DEFAULT 0,
  `clicked_product_id` int(11) DEFAULT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `filters_used` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters_used`)),
  `sort_order` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_query` (`query`),
  KEY `idx_clicked_product_id` (`clicked_product_id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_search_queries_product` FOREIGN KEY (`clicked_product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_search_queries_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `search_queries`
--

LOCK TABLES `search_queries` WRITE;
/*!40000 ALTER TABLE `search_queries` DISABLE KEYS */;
/*!40000 ALTER TABLE `search_queries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_logs`
--

DROP TABLE IF EXISTS `security_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `event_type` enum('login_success','login_failed','login_blocked','logout','password_change','email_change','two_fa_enabled','two_fa_disabled','account_locked','account_unlocked','suspicious_activity','access_denied','data_breach','privilege_escalation') NOT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `resource_type` varchar(50) DEFAULT NULL,
  `resource_id` int(11) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `risk_score` tinyint(3) unsigned DEFAULT NULL,
  `country` varchar(2) DEFAULT NULL,
  `is_resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_severity` (`severity`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_risk_score` (`risk_score`),
  KEY `idx_is_resolved` (`is_resolved`),
  KEY `idx_created_at` (`created_at`),
  KEY `fk_security_logs_resolver` (`resolved_by`),
  CONSTRAINT `fk_security_logs_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_security_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_logs`
--

LOCK TABLES `security_logs` WRITE;
/*!40000 ALTER TABLE `security_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `security_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_ads`
--

DROP TABLE IF EXISTS `seller_ads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_ads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `budget` decimal(18,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'USD',
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `target` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target`)),
  `status` enum('pending','approved','rejected','running','paused','completed') DEFAULT 'pending',
  `impressions` int(11) DEFAULT 0,
  `clicks` int(11) DEFAULT 0,
  `spend` decimal(18,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_seller_id` (`seller_id`),
  KEY `idx_status` (`status`),
  KEY `idx_starts_at` (`starts_at`),
  KEY `idx_ends_at` (`ends_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_ads`
--

LOCK TABLES `seller_ads` WRITE;
/*!40000 ALTER TABLE `seller_ads` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_ads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_analytics`
--

DROP TABLE IF EXISTS `seller_analytics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `metric_date` date NOT NULL,
  `total_views` int(11) NOT NULL DEFAULT 0,
  `total_sales` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_orders` int(11) NOT NULL DEFAULT 0,
  `total_revenue` decimal(15,2) NOT NULL DEFAULT 0.00,
  `conversion_rate` decimal(5,4) NOT NULL DEFAULT 0.0000,
  `average_order_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `return_rate` decimal(5,4) NOT NULL DEFAULT 0.0000,
  `customer_satisfaction` decimal(3,2) DEFAULT NULL,
  `traffic_sources` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`traffic_sources`)),
  `top_products` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`top_products`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_vendor_date` (`vendor_id`,`metric_date`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_metric_date` (`metric_date`),
  KEY `idx_total_revenue` (`total_revenue`),
  CONSTRAINT `fk_seller_analytics_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_analytics`
--

LOCK TABLES `seller_analytics` WRITE;
/*!40000 ALTER TABLE `seller_analytics` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_analytics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_bank_details`
--

DROP TABLE IF EXISTS `seller_bank_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_bank_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `account_type` enum('checking','savings','business') NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `account_holder_name` varchar(255) NOT NULL,
  `account_number_encrypted` varchar(500) NOT NULL,
  `routing_number_encrypted` varchar(500) NOT NULL,
  `swift_code` varchar(20) DEFAULT NULL,
  `iban` varchar(50) DEFAULT NULL,
  `bank_address` text DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vendor` (`vendor_id`),
  KEY `idx_is_default` (`is_default`),
  CONSTRAINT `fk_seller_bank_details_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_bank_details`
--

LOCK TABLES `seller_bank_details` WRITE;
/*!40000 ALTER TABLE `seller_bank_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_bank_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_campaign_assets`
--

DROP TABLE IF EXISTS `seller_campaign_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_campaign_assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `asset_type` enum('image','video','text','html','banner') NOT NULL,
  `name` varchar(255) NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `dimensions` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive','pending_approval') NOT NULL DEFAULT 'pending_approval',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_campaign` (`campaign_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_seller_campaign_assets_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `seller_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_campaign_assets`
--

LOCK TABLES `seller_campaign_assets` WRITE;
/*!40000 ALTER TABLE `seller_campaign_assets` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_campaign_assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_campaign_stats`
--

DROP TABLE IF EXISTS `seller_campaign_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_campaign_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `impressions` int(11) NOT NULL DEFAULT 0,
  `clicks` int(11) NOT NULL DEFAULT 0,
  `conversions` int(11) NOT NULL DEFAULT 0,
  `spend` decimal(15,2) NOT NULL DEFAULT 0.00,
  `revenue` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_date` (`campaign_id`,`date`),
  KEY `idx_date` (`date`),
  CONSTRAINT `fk_seller_campaign_stats_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `seller_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_campaign_stats`
--

LOCK TABLES `seller_campaign_stats` WRITE;
/*!40000 ALTER TABLE `seller_campaign_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_campaign_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_campaigns`
--

DROP TABLE IF EXISTS `seller_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('email','social','display','search','affiliate','influencer') NOT NULL,
  `status` enum('draft','active','paused','completed','cancelled') NOT NULL DEFAULT 'draft',
  `budget` decimal(15,2) DEFAULT NULL,
  `spent` decimal(15,2) NOT NULL DEFAULT 0.00,
  `target_audience` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_audience`)),
  `objectives` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`objectives`)),
  `start_date` timestamp NOT NULL,
  `end_date` timestamp NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vendor_status` (`vendor_id`,`status`),
  KEY `idx_dates` (`start_date`,`end_date`),
  CONSTRAINT `fk_seller_campaigns_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_campaigns`
--

LOCK TABLES `seller_campaigns` WRITE;
/*!40000 ALTER TABLE `seller_campaigns` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_campaigns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_chat_messages`
--

DROP TABLE IF EXISTS `seller_chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stream_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `message_type` enum('text','emoji','system','product_link') NOT NULL DEFAULT 'text',
  `is_moderator` tinyint(1) NOT NULL DEFAULT 0,
  `is_seller` tinyint(1) NOT NULL DEFAULT 0,
  `is_highlighted` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_stream` (`stream_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_seller_chat_messages_stream` FOREIGN KEY (`stream_id`) REFERENCES `seller_live_streams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seller_chat_messages_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_chat_messages`
--

LOCK TABLES `seller_chat_messages` WRITE;
/*!40000 ALTER TABLE `seller_chat_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_chat_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_commissions`
--

DROP TABLE IF EXISTS `seller_commissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_commissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `sale_amount` decimal(10,2) NOT NULL,
  `commission_rate` decimal(5,4) NOT NULL,
  `commission_amount` decimal(10,2) NOT NULL,
  `platform_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','paid','disputed') NOT NULL DEFAULT 'pending',
  `approved_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `payout_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_order_item_commission` (`order_item_id`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_status` (`status`),
  KEY `idx_payout_id` (`payout_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_seller_commissions_vendor_status` (`vendor_id`,`status`),
  CONSTRAINT `fk_seller_commissions_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seller_commissions_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seller_commissions_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seller_commissions_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_commissions`
--

LOCK TABLES `seller_commissions` WRITE;
/*!40000 ALTER TABLE `seller_commissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_commissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_coupon_redemptions`
--

DROP TABLE IF EXISTS `seller_coupon_redemptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_coupon_redemptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `coupon_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `redeemed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_coupon` (`coupon_id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_redeemed_at` (`redeemed_at`),
  CONSTRAINT `fk_seller_coupon_redemptions_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `seller_coupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seller_coupon_redemptions_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seller_coupon_redemptions_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_coupon_redemptions`
--

LOCK TABLES `seller_coupon_redemptions` WRITE;
/*!40000 ALTER TABLE `seller_coupon_redemptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_coupon_redemptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_coupon_rules`
--

DROP TABLE IF EXISTS `seller_coupon_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_coupon_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `coupon_id` int(11) NOT NULL,
  `rule_type` enum('customer_group','first_time_buyer','geographic','time_based','purchase_history') NOT NULL,
  `rule_condition` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`rule_condition`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_coupon` (`coupon_id`),
  KEY `idx_rule_type` (`rule_type`),
  CONSTRAINT `fk_seller_coupon_rules_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `seller_coupons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_coupon_rules`
--

LOCK TABLES `seller_coupon_rules` WRITE;
/*!40000 ALTER TABLE `seller_coupon_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_coupon_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_coupons`
--

DROP TABLE IF EXISTS `seller_coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `type` enum('percentage','fixed_amount','free_shipping','buy_x_get_y') NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `minimum_amount` decimal(10,2) DEFAULT NULL,
  `maximum_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_limit_per_customer` int(11) DEFAULT 1,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive','expired') NOT NULL DEFAULT 'active',
  `start_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `end_date` timestamp NULL DEFAULT NULL,
  `applicable_products` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applicable_products`)),
  `applicable_categories` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applicable_categories`)),
  `excluded_products` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`excluded_products`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendor_code` (`vendor_id`,`code`),
  KEY `idx_code` (`code`),
  KEY `idx_status` (`status`),
  KEY `idx_dates` (`start_date`,`end_date`),
  CONSTRAINT `fk_seller_coupons_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_coupons`
--

LOCK TABLES `seller_coupons` WRITE;
/*!40000 ALTER TABLE `seller_coupons` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_dispute_evidence`
--

DROP TABLE IF EXISTS `seller_dispute_evidence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_dispute_evidence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dispute_id` int(11) NOT NULL,
  `submitted_by` int(11) NOT NULL,
  `evidence_type` enum('document','image','email','communication','tracking','receipt') NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `description` text NOT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_dispute` (`dispute_id`),
  KEY `idx_submission_date` (`submission_date`),
  KEY `fk_seller_dispute_evidence_user` (`submitted_by`),
  CONSTRAINT `fk_seller_dispute_evidence_dispute` FOREIGN KEY (`dispute_id`) REFERENCES `seller_disputes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seller_dispute_evidence_user` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_dispute_evidence`
--

LOCK TABLES `seller_dispute_evidence` WRITE;
/*!40000 ALTER TABLE `seller_dispute_evidence` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_dispute_evidence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_dispute_messages`
--

DROP TABLE IF EXISTS `seller_dispute_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_dispute_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dispute_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('customer','seller','admin','system') NOT NULL,
  `message` text NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `is_internal` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_dispute` (`dispute_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `fk_seller_dispute_messages_sender` (`sender_id`),
  CONSTRAINT `fk_seller_dispute_messages_dispute` FOREIGN KEY (`dispute_id`) REFERENCES `seller_disputes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seller_dispute_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_dispute_messages`
--

LOCK TABLES `seller_dispute_messages` WRITE;
/*!40000 ALTER TABLE `seller_dispute_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_dispute_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_disputes`
--

DROP TABLE IF EXISTS `seller_disputes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_disputes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `dispute_number` varchar(50) NOT NULL,
  `type` enum('chargeback','refund_request','product_issue','service_issue','payment_issue') NOT NULL,
  `status` enum('open','under_review','awaiting_response','resolved','escalated','closed') NOT NULL DEFAULT 'open',
  `amount_disputed` decimal(10,2) NOT NULL,
  `customer_claim` text NOT NULL,
  `seller_response` text DEFAULT NULL,
  `resolution` text DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `deadline` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `dispute_number` (`dispute_number`),
  KEY `idx_vendor_status` (`vendor_id`,`status`),
  KEY `idx_order` (`order_id`),
  KEY `idx_deadline` (`deadline`),
  KEY `fk_seller_disputes_customer` (`customer_id`),
  KEY `fk_seller_disputes_resolver` (`resolved_by`),
  CONSTRAINT `fk_seller_disputes_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seller_disputes_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_seller_disputes_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_seller_disputes_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_disputes`
--

LOCK TABLES `seller_disputes` WRITE;
/*!40000 ALTER TABLE `seller_disputes` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_disputes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_documents`
--

DROP TABLE IF EXISTS `seller_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `document_type` enum('business_license','tax_id','identity','address_proof','bank_statement','tax_form','insurance','certification') NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `document_number` varchar(100) DEFAULT NULL,
  `issued_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('pending','approved','rejected','expired') NOT NULL DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vendor_type` (`vendor_id`,`document_type`),
  KEY `idx_status` (`status`),
  KEY `idx_expiry_date` (`expiry_date`),
  KEY `fk_seller_documents_reviewer` (`reviewed_by`),
  CONSTRAINT `fk_seller_documents_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_seller_documents_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_documents`
--

LOCK TABLES `seller_documents` WRITE;
/*!40000 ALTER TABLE `seller_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_inventory`
--

DROP TABLE IF EXISTS `seller_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `quantity_available` int(11) NOT NULL DEFAULT 0,
  `quantity_reserved` int(11) NOT NULL DEFAULT 0,
  `quantity_damaged` int(11) NOT NULL DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendor_product_variant_location` (`vendor_id`,`product_id`,`variant_id`,`location`),
  KEY `idx_quantity_available` (`quantity_available`),
  KEY `fk_seller_inventory_product` (`product_id`),
  KEY `fk_seller_inventory_updater` (`updated_by`),
  CONSTRAINT `fk_seller_inventory_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seller_inventory_updater` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_seller_inventory_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_inventory`
--

LOCK TABLES `seller_inventory` WRITE;
/*!40000 ALTER TABLE `seller_inventory` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_kpis`
--

DROP TABLE IF EXISTS `seller_kpis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_kpis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `metric_date` date NOT NULL,
  `total_sales` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_orders` int(11) NOT NULL DEFAULT 0,
  `total_customers` int(11) NOT NULL DEFAULT 0,
  `conversion_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `avg_order_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `return_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendor_date` (`vendor_id`,`metric_date`),
  KEY `idx_metric_date` (`metric_date`),
  CONSTRAINT `fk_seller_kpis_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_kpis`
--

LOCK TABLES `seller_kpis` WRITE;
/*!40000 ALTER TABLE `seller_kpis` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_kpis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_kyc`
--

DROP TABLE IF EXISTS `seller_kyc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_kyc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `verification_type` enum('individual','business','corporation') NOT NULL,
  `business_registration_number` varchar(100) DEFAULT NULL,
  `tax_identification_number` varchar(100) DEFAULT NULL,
  `identity_documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`identity_documents`)),
  `business_documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`business_documents`)),
  `address_verification` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`address_verification`)),
  `bank_verification` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`bank_verification`)),
  `verification_status` enum('pending','in_review','approved','rejected','requires_resubmission') NOT NULL DEFAULT 'pending',
  `verification_notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_verification_status` (`verification_status`),
  KEY `idx_verification_type` (`verification_type`),
  KEY `idx_verified_by` (`verified_by`),
  KEY `idx_submitted_at` (`submitted_at`),
  CONSTRAINT `fk_seller_kyc_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seller_kyc_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_kyc`
--

LOCK TABLES `seller_kyc` WRITE;
/*!40000 ALTER TABLE `seller_kyc` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_kyc` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_live_streams`
--

DROP TABLE IF EXISTS `seller_live_streams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_live_streams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `stream_key` varchar(255) NOT NULL,
  `stream_url` varchar(500) DEFAULT NULL,
  `status` enum('scheduled','live','ended','cancelled') NOT NULL DEFAULT 'scheduled',
  `scheduled_start` timestamp NOT NULL,
  `actual_start` timestamp NULL DEFAULT NULL,
  `actual_end` timestamp NULL DEFAULT NULL,
  `max_viewers` int(11) DEFAULT 0,
  `total_views` int(11) DEFAULT 0,
  `chat_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `recording_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `recording_url` varchar(500) DEFAULT NULL,
  `products_featured` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`products_featured`)),
  `stream_analytics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`stream_analytics`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `stream_key` (`stream_key`),
  KEY `idx_vendor_status` (`vendor_id`,`status`),
  KEY `idx_scheduled_start` (`scheduled_start`),
  CONSTRAINT `fk_seller_live_streams_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_live_streams`
--

LOCK TABLES `seller_live_streams` WRITE;
/*!40000 ALTER TABLE `seller_live_streams` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_live_streams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_message_templates`
--

DROP TABLE IF EXISTS `seller_message_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_message_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` enum('order_confirmation','shipping_notification','delivery_confirmation','return_approved','general_inquiry','support') NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `content` text NOT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vendor_category` (`vendor_id`,`category`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_seller_message_templates_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_message_templates`
--

LOCK TABLES `seller_message_templates` WRITE;
/*!40000 ALTER TABLE `seller_message_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_message_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_messages`
--

DROP TABLE IF EXISTS `seller_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `conversation_id` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `sender_type` enum('seller','customer','system') NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `message_type` enum('text','image','file','order_update','system') NOT NULL DEFAULT 'text',
  `content` text NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `order_id` int(11) DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vendor_conversation` (`vendor_id`,`conversation_id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `fk_seller_messages_order` (`order_id`),
  CONSTRAINT `fk_seller_messages_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seller_messages_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_seller_messages_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_messages`
--

LOCK TABLES `seller_messages` WRITE;
/*!40000 ALTER TABLE `seller_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_notifications`
--

DROP TABLE IF EXISTS `seller_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `type` enum('order','product','payout','dispute','system','marketing') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `read_at` timestamp NULL DEFAULT NULL,
  `action_url` varchar(500) DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vendor_read` (`vendor_id`,`read_at`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_seller_notifications_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_notifications`
--

LOCK TABLES `seller_notifications` WRITE;
/*!40000 ALTER TABLE `seller_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_order_items`
--

DROP TABLE IF EXISTS `seller_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_order_id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT NULL,
  `commission_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','fulfilled','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_seller_order` (`seller_order_id`),
  KEY `idx_order_item` (`order_item_id`),
  KEY `idx_product` (`product_id`),
  CONSTRAINT `fk_seller_order_items_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seller_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seller_order_items_seller_order` FOREIGN KEY (`seller_order_id`) REFERENCES `seller_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_order_items`
--

LOCK TABLES `seller_order_items` WRITE;
/*!40000 ALTER TABLE `seller_order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_orders`
--

DROP TABLE IF EXISTS `seller_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `tracking_number` varchar(100) DEFAULT NULL,
  `shipping_carrier` varchar(100) DEFAULT NULL,
  `shipping_cost` decimal(10,2) DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT NULL,
  `commission_amount` decimal(10,2) DEFAULT NULL,
  `payout_status` enum('pending','processing','paid','on_hold') NOT NULL DEFAULT 'pending',
  `shipped_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendor_order` (`vendor_id`,`order_id`),
  KEY `idx_status` (`status`),
  KEY `idx_payout_status` (`payout_status`),
  KEY `idx_tracking_number` (`tracking_number`),
  KEY `fk_seller_orders_order` (`order_id`),
  CONSTRAINT `fk_seller_orders_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seller_orders_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_orders`
--

LOCK TABLES `seller_orders` WRITE;
/*!40000 ALTER TABLE `seller_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_payment_info`
--

DROP TABLE IF EXISTS `seller_payment_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_payment_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `payment_method` enum('bank_transfer','paypal','mobile_money','other') NOT NULL DEFAULT 'bank_transfer',
  `bank_name` varchar(100) DEFAULT NULL,
  `account_holder_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `routing_number` varchar(50) DEFAULT NULL,
  `swift_code` varchar(20) DEFAULT NULL,
  `paypal_email` varchar(100) DEFAULT NULL,
  `mobile_money_provider` varchar(50) DEFAULT NULL,
  `mobile_money_number` varchar(50) DEFAULT NULL,
  `additional_info` text DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vendor_id` (`vendor_id`),
  CONSTRAINT `fk_payment_info_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_payment_info`
--

LOCK TABLES `seller_payment_info` WRITE;
/*!40000 ALTER TABLE `seller_payment_info` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_payment_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_payout_requests`
--

DROP TABLE IF EXISTS `seller_payout_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_payout_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `fee` decimal(15,2) DEFAULT 0.00,
  `net_amount` decimal(15,2) NOT NULL,
  `method` enum('bank_transfer','paypal','crypto','check') NOT NULL DEFAULT 'bank_transfer',
  `account_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`account_details`)),
  `status` enum('pending','processing','approved','paid','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vendor_status` (`vendor_id`,`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `fk_seller_payout_requests_processor` (`processed_by`),
  CONSTRAINT `fk_seller_payout_requests_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_seller_payout_requests_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_payout_requests`
--

LOCK TABLES `seller_payout_requests` WRITE;
/*!40000 ALTER TABLE `seller_payout_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_payout_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_payouts`
--

DROP TABLE IF EXISTS `seller_payouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_payouts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `request_amount` decimal(10,2) NOT NULL,
  `processing_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `final_amount` decimal(10,2) NOT NULL,
  `payout_method` enum('bank_transfer','paypal','stripe','wise','manual') NOT NULL DEFAULT 'bank_transfer',
  `payout_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payout_details`)),
  `status` enum('requested','pending','processing','completed','failed','cancelled') NOT NULL DEFAULT 'requested',
  `reference_number` varchar(100) DEFAULT NULL,
  `external_transaction_id` varchar(255) DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_status` (`status`),
  KEY `idx_requested_at` (`requested_at`),
  KEY `idx_processed_by` (`processed_by`),
  KEY `idx_reference_number` (`reference_number`),
  KEY `idx_seller_payouts_vendor_status` (`vendor_id`,`status`),
  CONSTRAINT `fk_seller_payouts_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_seller_payouts_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_payouts`
--

LOCK TABLES `seller_payouts` WRITE;
/*!40000 ALTER TABLE `seller_payouts` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_payouts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_performance_metrics`
--

DROP TABLE IF EXISTS `seller_performance_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_performance_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `metric_date` date NOT NULL,
  `response_time_avg` decimal(8,2) DEFAULT NULL,
  `customer_satisfaction` decimal(3,2) DEFAULT NULL,
  `order_fulfillment_rate` decimal(5,2) DEFAULT NULL,
  `return_rate` decimal(5,2) DEFAULT NULL,
  `dispute_rate` decimal(5,2) DEFAULT NULL,
  `on_time_shipping_rate` decimal(5,2) DEFAULT NULL,
  `product_quality_score` decimal(3,2) DEFAULT NULL,
  `communication_score` decimal(3,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendor_date` (`vendor_id`,`metric_date`),
  KEY `idx_metric_date` (`metric_date`),
  CONSTRAINT `fk_seller_performance_metrics_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_performance_metrics`
--

LOCK TABLES `seller_performance_metrics` WRITE;
/*!40000 ALTER TABLE `seller_performance_metrics` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_performance_metrics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_product_media`
--

DROP TABLE IF EXISTS `seller_product_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_product_media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_product_id` int(11) NOT NULL,
  `media_type` enum('image','video','document') NOT NULL DEFAULT 'image',
  `file_path` varchar(500) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `approval_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_seller_product` (`seller_product_id`),
  KEY `idx_sort_order` (`sort_order`),
  CONSTRAINT `fk_seller_product_media_product` FOREIGN KEY (`seller_product_id`) REFERENCES `seller_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_product_media`
--

LOCK TABLES `seller_product_media` WRITE;
/*!40000 ALTER TABLE `seller_product_media` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_product_media` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_product_variants`
--

DROP TABLE IF EXISTS `seller_product_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_product_variants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_product_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `price_adjustment` decimal(10,2) DEFAULT 0.00,
  `cost_adjustment` decimal(10,2) DEFAULT 0.00,
  `stock_quantity` int(11) DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_seller_product` (`seller_product_id`),
  KEY `idx_sku` (`sku`),
  CONSTRAINT `fk_seller_product_variants_product` FOREIGN KEY (`seller_product_id`) REFERENCES `seller_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_product_variants`
--

LOCK TABLES `seller_product_variants` WRITE;
/*!40000 ALTER TABLE `seller_product_variants` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_product_variants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_products`
--

DROP TABLE IF EXISTS `seller_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `profit_margin` decimal(5,2) DEFAULT NULL,
  `min_stock_level` int(11) DEFAULT 0,
  `max_stock_level` int(11) DEFAULT NULL,
  `reorder_point` int(11) DEFAULT NULL,
  `lead_time_days` int(11) DEFAULT NULL,
  `approval_status` enum('pending','approved','rejected','under_review') NOT NULL DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendor_product` (`vendor_id`,`product_id`),
  KEY `idx_approval_status` (`approval_status`),
  KEY `idx_sku` (`sku`),
  KEY `fk_seller_products_product` (`product_id`),
  KEY `fk_seller_products_approver` (`approved_by`),
  CONSTRAINT `fk_seller_products_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_seller_products_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seller_products_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_products`
--

LOCK TABLES `seller_products` WRITE;
/*!40000 ALTER TABLE `seller_products` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_profiles`
--

DROP TABLE IF EXISTS `seller_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `store_name` varchar(255) NOT NULL,
  `store_description` longtext DEFAULT NULL,
  `store_logo` varchar(500) DEFAULT NULL,
  `store_banner` varchar(500) DEFAULT NULL,
  `store_address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`store_address`)),
  `social_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_links`)),
  `business_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`business_hours`)),
  `shipping_policy` longtext DEFAULT NULL,
  `return_policy` longtext DEFAULT NULL,
  `store_policies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`store_policies`)),
  `notification_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notification_settings`)),
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_is_verified` (`is_verified`),
  CONSTRAINT `fk_seller_profiles_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_profiles`
--

LOCK TABLES `seller_profiles` WRITE;
/*!40000 ALTER TABLE `seller_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_reports_jobs`
--

DROP TABLE IF EXISTS `seller_reports_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_reports_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `report_type` varchar(100) NOT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`parameters`)),
  `status` enum('queued','processing','completed','failed','cancelled') NOT NULL DEFAULT 'queued',
  `progress` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vendor_status` (`vendor_id`,`status`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_seller_reports_jobs_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_reports_jobs`
--

LOCK TABLES `seller_reports_jobs` WRITE;
/*!40000 ALTER TABLE `seller_reports_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_reports_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_rma_notes`
--

DROP TABLE IF EXISTS `seller_rma_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_rma_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rma_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('customer','seller','admin') NOT NULL,
  `note` text NOT NULL,
  `is_internal` tinyint(1) NOT NULL DEFAULT 0,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rma` (`rma_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `fk_seller_rma_notes_user` (`user_id`),
  CONSTRAINT `fk_seller_rma_notes_rma` FOREIGN KEY (`rma_id`) REFERENCES `seller_rmas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seller_rma_notes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_rma_notes`
--

LOCK TABLES `seller_rma_notes` WRITE;
/*!40000 ALTER TABLE `seller_rma_notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_rma_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_rmas`
--

DROP TABLE IF EXISTS `seller_rmas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_rmas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `rma_number` varchar(50) NOT NULL,
  `reason` enum('defective','wrong_item','damaged','not_as_described','change_of_mind','warranty') NOT NULL,
  `status` enum('pending','approved','rejected','received','refunded','completed') NOT NULL DEFAULT 'pending',
  `return_value` decimal(10,2) NOT NULL,
  `refund_amount` decimal(10,2) DEFAULT NULL,
  `return_label_generated` tinyint(1) DEFAULT 0,
  `return_tracking` varchar(100) DEFAULT NULL,
  `received_condition` enum('good','damaged','unopened','used') DEFAULT NULL,
  `resolution` enum('full_refund','partial_refund','replacement','repair','rejected') DEFAULT NULL,
  `customer_notes` text DEFAULT NULL,
  `seller_notes` text DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `rma_number` (`rma_number`),
  KEY `idx_vendor_status` (`vendor_id`,`status`),
  KEY `idx_order` (`order_id`),
  KEY `idx_customer` (`customer_id`),
  CONSTRAINT `fk_seller_rmas_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seller_rmas_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seller_rmas_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_rmas`
--

LOCK TABLES `seller_rmas` WRITE;
/*!40000 ALTER TABLE `seller_rmas` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_rmas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_sales_reports`
--

DROP TABLE IF EXISTS `seller_sales_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_sales_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `report_type` enum('daily','weekly','monthly','quarterly','yearly','custom') NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `total_sales` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_orders` int(11) NOT NULL DEFAULT 0,
  `total_customers` int(11) NOT NULL DEFAULT 0,
  `avg_order_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `top_products` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`top_products`)),
  `geographic_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`geographic_breakdown`)),
  `payment_methods` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_methods`)),
  `report_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`report_data`)),
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vendor_type` (`vendor_id`,`report_type`),
  KEY `idx_period` (`period_start`,`period_end`),
  CONSTRAINT `fk_seller_sales_reports_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_sales_reports`
--

LOCK TABLES `seller_sales_reports` WRITE;
/*!40000 ALTER TABLE `seller_sales_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_sales_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_shipping_rates`
--

DROP TABLE IF EXISTS `seller_shipping_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_shipping_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `shipping_zone_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `method` enum('flat_rate','weight_based','price_based','quantity_based','free') NOT NULL,
  `rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`conditions`)),
  `min_order_amount` decimal(10,2) DEFAULT NULL,
  `max_weight` decimal(8,2) DEFAULT NULL,
  `estimated_delivery_days` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_shipping_zone_id` (`shipping_zone_id`),
  KEY `idx_method` (`method`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_seller_shipping_rates_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seller_shipping_rates_zone` FOREIGN KEY (`shipping_zone_id`) REFERENCES `seller_shipping_zones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_shipping_rates`
--

LOCK TABLES `seller_shipping_rates` WRITE;
/*!40000 ALTER TABLE `seller_shipping_rates` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_shipping_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_shipping_settings`
--

DROP TABLE IF EXISTS `seller_shipping_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_shipping_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `carrier_name` varchar(100) NOT NULL,
  `shipping_zone` varchar(100) DEFAULT 'Domestic',
  `base_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `per_item_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `free_shipping_threshold` decimal(10,2) DEFAULT NULL,
  `estimated_delivery_days` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_shipping_settings_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_shipping_settings`
--

LOCK TABLES `seller_shipping_settings` WRITE;
/*!40000 ALTER TABLE `seller_shipping_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_shipping_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_shipping_zones`
--

DROP TABLE IF EXISTS `seller_shipping_zones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_shipping_zones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `countries` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`countries`)),
  `states` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`states`)),
  `postal_codes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`postal_codes`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_sort_order` (`sort_order`),
  CONSTRAINT `fk_seller_shipping_zones_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_shipping_zones`
--

LOCK TABLES `seller_shipping_zones` WRITE;
/*!40000 ALTER TABLE `seller_shipping_zones` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_shipping_zones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_staff`
--

DROP TABLE IF EXISTS `seller_staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('manager','editor','viewer','support') NOT NULL DEFAULT 'viewer',
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `invited_by` int(11) NOT NULL,
  `invitation_token` varchar(255) DEFAULT NULL,
  `invitation_expires_at` timestamp NULL DEFAULT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','active','suspended','removed') NOT NULL DEFAULT 'pending',
  `last_active_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_vendor_user` (`vendor_id`,`user_id`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`),
  KEY `idx_invited_by` (`invited_by`),
  CONSTRAINT `fk_seller_staff_inviter` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seller_staff_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seller_staff_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_staff`
--

LOCK TABLES `seller_staff` WRITE;
/*!40000 ALTER TABLE `seller_staff` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_stock_logs`
--

DROP TABLE IF EXISTS `seller_stock_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_stock_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inventory_id` int(11) NOT NULL,
  `movement_type` enum('in','out','adjustment','reserved','released','damaged') NOT NULL,
  `quantity` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `reference_type` enum('order','return','adjustment','damage','restock') DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `performed_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_inventory` (`inventory_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_reference` (`reference_type`,`reference_id`),
  KEY `fk_seller_stock_logs_user` (`performed_by`),
  CONSTRAINT `fk_seller_stock_logs_inventory` FOREIGN KEY (`inventory_id`) REFERENCES `seller_inventory` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seller_stock_logs_user` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_stock_logs`
--

LOCK TABLES `seller_stock_logs` WRITE;
/*!40000 ALTER TABLE `seller_stock_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_stock_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_tax_settings`
--

DROP TABLE IF EXISTS `seller_tax_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_tax_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `tax_type` enum('VAT','GST','Sales Tax','Other') NOT NULL DEFAULT 'VAT',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_region` varchar(100) DEFAULT NULL,
  `tax_id_number` varchar(50) DEFAULT NULL,
  `apply_to_shipping` tinyint(1) NOT NULL DEFAULT 0,
  `is_inclusive` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_tax_settings_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_tax_settings`
--

LOCK TABLES `seller_tax_settings` WRITE;
/*!40000 ALTER TABLE `seller_tax_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_tax_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_wallets`
--

DROP TABLE IF EXISTS `seller_wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_wallets` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `pending_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_earned` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_withdrawn` decimal(15,2) NOT NULL DEFAULT 0.00,
  `commission_rate` decimal(5,2) NOT NULL DEFAULT 10.00,
  `payment_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_details`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_vendor_wallet` (`vendor_id`),
  KEY `idx_seller_wallets_balance` (`balance`),
  KEY `idx_seller_wallets_created` (`created_at`),
  CONSTRAINT `fk_seller_wallets_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_wallets`
--

LOCK TABLES `seller_wallets` WRITE;
/*!40000 ALTER TABLE `seller_wallets` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_wallets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seo_meta`
--

DROP TABLE IF EXISTS `seo_meta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seo_meta` (
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `meta_title` varchar(200) DEFAULT NULL,
  `meta_description` varchar(300) DEFAULT NULL,
  `canonical_url` varchar(500) DEFAULT NULL,
  `og_title` varchar(200) DEFAULT NULL,
  `og_description` varchar(300) DEFAULT NULL,
  `og_image` varchar(500) DEFAULT NULL,
  `robots` varchar(50) DEFAULT 'index,follow',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  UNIQUE KEY `uniq_entity` (`entity_type`,`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seo_meta`
--

LOCK TABLES `seo_meta` WRITE;
/*!40000 ALTER TABLE `seo_meta` DISABLE KEYS */;
/*!40000 ALTER TABLE `seo_meta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seo_metadata`
--

DROP TABLE IF EXISTS `seo_metadata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seo_metadata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entity_type` enum('product','category','page','vendor') NOT NULL,
  `entity_id` int(11) NOT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `canonical_url` varchar(500) DEFAULT NULL,
  `og_title` varchar(255) DEFAULT NULL,
  `og_description` text DEFAULT NULL,
  `og_image` varchar(500) DEFAULT NULL,
  `twitter_title` varchar(255) DEFAULT NULL,
  `twitter_description` text DEFAULT NULL,
  `twitter_image` varchar(500) DEFAULT NULL,
  `schema_markup` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`schema_markup`)),
  `robots_directive` varchar(255) DEFAULT 'index,follow',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_entity_type` (`entity_type`),
  KEY `idx_entity_id` (`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seo_metadata`
--

LOCK TABLES `seo_metadata` WRITE;
/*!40000 ALTER TABLE `seo_metadata` DISABLE KEYS */;
/*!40000 ALTER TABLE `seo_metadata` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_group` varchar(100) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext DEFAULT NULL,
  `setting_type` enum('string','integer','decimal','boolean','json','text','password') NOT NULL DEFAULT 'string',
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `is_encrypted` tinyint(1) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `validation_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`validation_rules`)),
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_group_key` (`setting_group`,`setting_key`),
  KEY `idx_setting_group` (`setting_group`),
  KEY `idx_is_public` (`is_public`),
  KEY `idx_updated_by` (`updated_by`),
  KEY `idx_settings_group_public` (`setting_group`,`is_public`),
  CONSTRAINT `fk_settings_updater` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES
(1,'general','site_name','E-Commerce Platform','string',1,0,'Site name displayed in header and emails',NULL,NULL,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(2,'general','site_description','Professional E-Commerce Platform','string',1,0,'Site description for SEO',NULL,NULL,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(3,'general','admin_email','admin@example.com','string',0,0,'Administrator email address',NULL,NULL,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(4,'general','timezone','UTC','string',1,0,'Default timezone',NULL,NULL,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(5,'general','currency','USD','string',1,0,'Default currency',NULL,NULL,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(6,'general','maintenance_mode','false','boolean',0,0,'Enable maintenance mode',NULL,NULL,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(7,'email','smtp_host','localhost','string',0,0,'SMTP server hostname',NULL,NULL,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(8,'email','smtp_port','587','integer',0,0,'SMTP server port',NULL,NULL,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(9,'email','smtp_username','','string',0,0,'SMTP username',NULL,NULL,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(10,'email','smtp_password','','password',0,0,'SMTP password',NULL,NULL,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(11,'email','smtp_encryption','tls','string',0,0,'SMTP encryption method',NULL,NULL,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(12,'payments','default_gateway','stripe','string',0,0,'Default payment gateway',NULL,NULL,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(13,'payments','stripe_publishable_key','','string',0,0,'Stripe publishable key',NULL,NULL,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(14,'payments','stripe_secret_key','','password',0,0,'Stripe secret key',NULL,NULL,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(15,'payments','paypal_client_id','','string',0,0,'PayPal client ID',NULL,NULL,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(16,'payments','paypal_client_secret','','password',0,0,'PayPal client secret',NULL,NULL,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(17,'security','session_timeout','3600','integer',0,0,'Session timeout in seconds',NULL,NULL,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(18,'security','max_login_attempts','5','integer',0,0,'Maximum login attempts before lockout',NULL,NULL,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(19,'security','lockout_duration','900','integer',0,0,'Account lockout duration in seconds',NULL,NULL,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(20,'security','require_2fa','false','boolean',0,0,'Require two-factor authentication',NULL,NULL,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(21,'features','enable_reviews','true','boolean',1,0,'Enable product reviews',NULL,NULL,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(22,'features','enable_wishlist','true','boolean',1,0,'Enable wishlist functionality',NULL,NULL,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(23,'features','enable_loyalty','true','boolean',1,0,'Enable loyalty program',NULL,NULL,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(24,'features','enable_live_streaming','true','boolean',1,0,'Enable live streaming features',NULL,NULL,'2025-09-14 19:54:26','2025-09-14 19:54:26');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipment_items`
--

DROP TABLE IF EXISTS `shipment_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipment_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shipment_id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `shipment_id` (`shipment_id`),
  KEY `order_item_id` (`order_item_id`),
  CONSTRAINT `fk_shipment_items_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_shipment_items_shipment` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipment_items`
--

LOCK TABLES `shipment_items` WRITE;
/*!40000 ALTER TABLE `shipment_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `shipment_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipments`
--

DROP TABLE IF EXISTS `shipments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `tracking_number` varchar(255) DEFAULT NULL,
  `carrier` varchar(100) DEFAULT NULL,
  `shipping_method` varchar(100) DEFAULT NULL,
  `shipping_cost` decimal(10,2) DEFAULT 0.00,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `shipped_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `vendor_id` (`vendor_id`),
  KEY `tracking_number` (`tracking_number`),
  KEY `fk_shipments_created_by` (`created_by`),
  CONSTRAINT `fk_shipments_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_shipments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_shipments_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipments`
--

LOCK TABLES `shipments` WRITE;
/*!40000 ALTER TABLE `shipments` DISABLE KEYS */;
/*!40000 ALTER TABLE `shipments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipping_carriers`
--

DROP TABLE IF EXISTS `shipping_carriers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipping_carriers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `tracking_url` varchar(255) DEFAULT NULL COMMENT 'URL template for tracking, e.g., https://www.fedex.com/apps/fedextrack/?tracknumbers={tracking_number}',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipping_carriers`
--

LOCK TABLES `shipping_carriers` WRITE;
/*!40000 ALTER TABLE `shipping_carriers` DISABLE KEYS */;
INSERT INTO `shipping_carriers` VALUES
(1,'FedEx','https://www.fedex.com/apps/fedextrack/?tracknumbers=',1,'2025-09-27 10:19:25','2025-09-27 10:19:25'),
(2,'UPS','https://www.ups.com/track?loc=en_US&tracknum=',1,'2025-09-27 10:19:25','2025-09-27 10:19:25'),
(3,'USPS','https://tools.usps.com/go/TrackConfirmAction_input?qtc_tLabels1=',1,'2025-09-27 10:19:25','2025-09-27 10:19:25'),
(4,'DHL','https://www.dhl.com/en/express/tracking.html?AWB=',1,'2025-09-27 10:19:25','2025-09-27 10:19:25');
/*!40000 ALTER TABLE `shipping_carriers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sponsored_product_transactions`
--

DROP TABLE IF EXISTS `sponsored_product_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sponsored_product_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sponsored_product_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_type` enum('payment','refund','adjustment') NOT NULL DEFAULT 'payment',
  `payment_method` varchar(50) DEFAULT 'wallet',
  `description` text DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sponsored_product_id` (`sponsored_product_id`),
  KEY `seller_id` (`seller_id`),
  KEY `idx_transaction_date` (`created_at`),
  CONSTRAINT `fk_sponsor_transaction` FOREIGN KEY (`sponsored_product_id`) REFERENCES `sponsored_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Financial transactions for product sponsorships';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sponsored_product_transactions`
--

LOCK TABLES `sponsored_product_transactions` WRITE;
/*!40000 ALTER TABLE `sponsored_product_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sponsored_product_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sponsored_products`
--

DROP TABLE IF EXISTS `sponsored_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sponsored_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `sponsorship_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `daily_cost` decimal(10,2) NOT NULL DEFAULT 5.00,
  `total_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `start_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `end_date` timestamp NULL DEFAULT NULL,
  `status` enum('active','paused','completed','cancelled') NOT NULL DEFAULT 'active',
  `position` int(11) NOT NULL DEFAULT 0,
  `clicks` int(11) NOT NULL DEFAULT 0,
  `views` int(11) NOT NULL DEFAULT 0,
  `orders` int(11) NOT NULL DEFAULT 0,
  `revenue_generated` decimal(10,2) NOT NULL DEFAULT 0.00,
  `admin_approved` tinyint(1) NOT NULL DEFAULT 0,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `seller_id` (`seller_id`),
  KEY `status` (`status`),
  KEY `idx_sponsored_active` (`status`,`start_date`,`end_date`),
  KEY `idx_sponsored_position` (`position`,`status`),
  CONSTRAINT `fk_sponsored_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sponsored_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Sponsored products for paid promotions on homepage';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sponsored_products`
--

LOCK TABLES `sponsored_products` WRITE;
/*!40000 ALTER TABLE `sponsored_products` DISABLE KEYS */;
/*!40000 ALTER TABLE `sponsored_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `store_appearance`
--

DROP TABLE IF EXISTS `store_appearance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `store_appearance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `store_logo` varchar(255) DEFAULT NULL,
  `store_banner` varchar(255) DEFAULT NULL,
  `theme_color` varchar(7) DEFAULT '#3b82f6',
  `theme_name` varchar(50) DEFAULT 'default',
  `custom_css` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_vendor` (`vendor_id`),
  CONSTRAINT `fk_store_appearance_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `store_appearance`
--

LOCK TABLES `store_appearance` WRITE;
/*!40000 ALTER TABLE `store_appearance` DISABLE KEYS */;
INSERT INTO `store_appearance` VALUES
(1,3,NULL,NULL,'#1f1cc4','modern',NULL,'2025-10-01 23:12:29','2025-10-01 23:12:48');
/*!40000 ALTER TABLE `store_appearance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `store_policies`
--

DROP TABLE IF EXISTS `store_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `store_policies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `return_policy` text DEFAULT NULL,
  `refund_policy` text DEFAULT NULL,
  `exchange_policy` text DEFAULT NULL,
  `shipping_policy` text DEFAULT NULL,
  `privacy_policy` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_vendor` (`vendor_id`),
  CONSTRAINT `fk_store_policies_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `store_policies`
--

LOCK TABLES `store_policies` WRITE;
/*!40000 ALTER TABLE `store_policies` DISABLE KEYS */;
/*!40000 ALTER TABLE `store_policies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stream_followers`
--

DROP TABLE IF EXISTS `stream_followers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stream_followers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `notify_on_live` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_vendor` (`user_id`,`vendor_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_vendor_id` (`vendor_id`),
  CONSTRAINT `fk_sf_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sf_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stream_followers`
--

LOCK TABLES `stream_followers` WRITE;
/*!40000 ALTER TABLE `stream_followers` DISABLE KEYS */;
/*!40000 ALTER TABLE `stream_followers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stream_viewers`
--

DROP TABLE IF EXISTS `stream_viewers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stream_viewers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stream_id` int(11) NOT NULL COMMENT 'Reference to the active live stream session',
  `user_id` int(11) NOT NULL COMMENT 'Reference to the user viewing the stream',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `stream_user_unique_idx` (`stream_id`,`user_id`),
  KEY `stream_id_idx` (`stream_id`),
  KEY `fk_stream_viewers_user_id` (`user_id`),
  CONSTRAINT `fk_stream_viewers_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stream_viewers`
--

LOCK TABLES `stream_viewers` WRITE;
/*!40000 ALTER TABLE `stream_viewers` DISABLE KEYS */;
/*!40000 ALTER TABLE `stream_viewers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stripe_events`
--

DROP TABLE IF EXISTS `stripe_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stripe_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` varchar(255) NOT NULL COMMENT 'Stripe Event ID (evt_...)',
  `event_type` varchar(100) NOT NULL COMMENT 'Event type (e.g., payment_intent.succeeded)',
  `processed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Full event payload for debugging' CHECK (json_valid(`payload`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_id` (`event_id`),
  KEY `idx_event_id` (`event_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_processed_at` (`processed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stripe_events`
--

LOCK TABLES `stripe_events` WRITE;
/*!40000 ALTER TABLE `stripe_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `stripe_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stripe_payment_intents`
--

DROP TABLE IF EXISTS `stripe_payment_intents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stripe_payment_intents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_intent_id` varchar(255) NOT NULL COMMENT 'Stripe PaymentIntent ID (pi_...)',
  `order_id` int(11) DEFAULT NULL COMMENT 'Internal order ID',
  `order_reference` varchar(50) DEFAULT NULL COMMENT 'Human-readable order reference',
  `amount_minor` int(11) NOT NULL COMMENT 'Amount in minor units (cents)',
  `currency` varchar(3) NOT NULL DEFAULT 'usd',
  `status` varchar(50) NOT NULL COMMENT 'Stripe PI status: requires_payment_method, requires_confirmation, requires_action, processing, requires_capture, canceled, succeeded',
  `client_secret` varchar(255) DEFAULT NULL COMMENT 'Client secret for frontend confirmation',
  `payment_method` varchar(255) DEFAULT NULL COMMENT 'Stripe payment method ID',
  `customer_id` varchar(255) DEFAULT NULL COMMENT 'Stripe customer ID',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional metadata from Stripe' CHECK (json_valid(`metadata`)),
  `last_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Last webhook payload received' CHECK (json_valid(`last_payload`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_intent_id` (`payment_intent_id`),
  KEY `idx_payment_intent_id` (`payment_intent_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_order_reference` (`order_reference`),
  KEY `idx_status` (`status`),
  KEY `idx_customer_id` (`customer_id`),
  CONSTRAINT `fk_stripe_pi_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stripe_payment_intents`
--

LOCK TABLES `stripe_payment_intents` WRITE;
/*!40000 ALTER TABLE `stripe_payment_intents` DISABLE KEYS */;
/*!40000 ALTER TABLE `stripe_payment_intents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stripe_refunds`
--

DROP TABLE IF EXISTS `stripe_refunds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stripe_refunds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `refund_id` varchar(255) NOT NULL COMMENT 'Stripe Refund ID (re_...)',
  `payment_intent_id` varchar(255) NOT NULL COMMENT 'Related Stripe PaymentIntent ID',
  `order_id` int(11) DEFAULT NULL COMMENT 'Internal order ID',
  `amount_minor` int(11) NOT NULL COMMENT 'Refunded amount in minor units',
  `currency` varchar(3) NOT NULL DEFAULT 'usd',
  `status` varchar(50) NOT NULL COMMENT 'Refund status: pending, succeeded, failed, canceled',
  `reason` varchar(255) DEFAULT NULL COMMENT 'Refund reason',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional metadata' CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `refund_id` (`refund_id`),
  KEY `idx_refund_id` (`refund_id`),
  KEY `idx_payment_intent_id` (`payment_intent_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_stripe_refund_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stripe_refunds`
--

LOCK TABLES `stripe_refunds` WRITE;
/*!40000 ALTER TABLE `stripe_refunds` DISABLE KEYS */;
/*!40000 ALTER TABLE `stripe_refunds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `subscription_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `channel` enum('email','sms','push','in_app') NOT NULL,
  `opt_in_status` tinyint(1) NOT NULL DEFAULT 1,
  `subscription_type` enum('marketing','transactional','notifications','all') NOT NULL DEFAULT 'all',
  `source` varchar(100) DEFAULT NULL,
  `opted_in_at` timestamp NULL DEFAULT NULL,
  `opted_out_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`subscription_id`),
  UNIQUE KEY `idx_user_channel_type` (`user_id`,`channel`,`subscription_type`),
  KEY `idx_channel` (`channel`),
  KEY `idx_opt_in_status` (`opt_in_status`),
  KEY `idx_subscription_type` (`subscription_type`),
  KEY `idx_subscriptions_user_channel` (`user_id`,`channel`,`opt_in_status`),
  CONSTRAINT `fk_subscriptions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscriptions`
--

LOCK TABLES `subscriptions` WRITE;
/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;
INSERT INTO `subscriptions` VALUES
(1,4,'email',1,'all',NULL,'2025-09-11 15:56:21',NULL,'2025-09-14 19:54:26','2025-09-14 19:54:26');
/*!40000 ALTER TABLE `subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_messages`
--

DROP TABLE IF EXISTS `support_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_messages`
--

LOCK TABLES `support_messages` WRITE;
/*!40000 ALTER TABLE `support_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_ticket_replies`
--

DROP TABLE IF EXISTS `support_ticket_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_ticket_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `guest_email` varchar(255) DEFAULT NULL,
  `reply_type` enum('customer','admin','system','auto') NOT NULL DEFAULT 'customer',
  `message` text NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `is_internal` tinyint(1) NOT NULL DEFAULT 0,
  `is_solution` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ticket_id` (`ticket_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_reply_type` (`reply_type`),
  KEY `idx_is_internal` (`is_internal`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_support_ticket_replies_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_support_ticket_replies_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_ticket_replies`
--

LOCK TABLES `support_ticket_replies` WRITE;
/*!40000 ALTER TABLE `support_ticket_replies` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_ticket_replies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_tickets`
--

DROP TABLE IF EXISTS `support_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_number` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `guest_email` varchar(255) DEFAULT NULL,
  `guest_name` varchar(100) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `category` enum('general','technical','billing','shipping','returns','product','account','complaint','suggestion') NOT NULL DEFAULT 'general',
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `status` enum('open','in_progress','pending_customer','pending_vendor','escalated','resolved','closed') NOT NULL DEFAULT 'open',
  `description` text NOT NULL,
  `resolution` text DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `escalated_to` int(11) DEFAULT NULL,
  `related_order_id` int(11) DEFAULT NULL,
  `related_product_id` int(11) DEFAULT NULL,
  `satisfaction_rating` tinyint(1) DEFAULT NULL,
  `satisfaction_feedback` text DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `first_response_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_ticket_number` (`ticket_number`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_guest_email` (`guest_email`),
  KEY `idx_category` (`category`),
  KEY `idx_priority` (`priority`),
  KEY `idx_status` (`status`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_escalated_to` (`escalated_to`),
  KEY `idx_related_order_id` (`related_order_id`),
  KEY `idx_related_product_id` (`related_product_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_support_tickets_assignee` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_support_tickets_escalated` FOREIGN KEY (`escalated_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_support_tickets_order` FOREIGN KEY (`related_order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_support_tickets_product` FOREIGN KEY (`related_product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_support_tickets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_tickets`
--

LOCK TABLES `support_tickets` WRITE;
/*!40000 ALTER TABLE `support_tickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_alerts`
--

DROP TABLE IF EXISTS `system_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alert_type` enum('security','performance','business','technical','compliance') NOT NULL,
  `severity` enum('info','warning','error','critical') NOT NULL DEFAULT 'info',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `alert_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`alert_data`)),
  `is_resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_alert_type` (`alert_type`),
  KEY `idx_severity` (`severity`),
  KEY `idx_is_resolved` (`is_resolved`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_resolved_by` (`resolved_by`),
  KEY `idx_system_alerts_type_severity` (`alert_type`,`severity`,`is_resolved`),
  CONSTRAINT `fk_system_alerts_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_alerts`
--

LOCK TABLES `system_alerts` WRITE;
/*!40000 ALTER TABLE `system_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_events`
--

DROP TABLE IF EXISTS `system_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` enum('performance','security','backup','maintenance','error','warning','info') NOT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `component` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `event_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`event_data`)),
  `metrics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metrics`)),
  `is_resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_severity` (`severity`),
  KEY `idx_component` (`component`),
  KEY `idx_is_resolved` (`is_resolved`),
  KEY `idx_created_at` (`created_at`),
  KEY `fk_system_events_resolver` (`resolved_by`),
  CONSTRAINT `fk_system_events_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_events`
--

LOCK TABLES `system_events` WRITE;
/*!40000 ALTER TABLE `system_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext DEFAULT NULL,
  `setting_type` enum('string','integer','decimal','boolean','json','text') NOT NULL DEFAULT 'string',
  `category` varchar(50) NOT NULL DEFAULT 'general',
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `is_encrypted` tinyint(1) NOT NULL DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_setting_key` (`setting_key`),
  KEY `idx_category` (`category`),
  KEY `idx_is_public` (`is_public`),
  KEY `idx_updated_by` (`updated_by`),
  CONSTRAINT `fk_system_settings_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tags`
--

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_rules`
--

DROP TABLE IF EXISTS `tax_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `tax_type` enum('vat','gst','sales_tax','other') NOT NULL,
  `rate` decimal(5,4) NOT NULL,
  `country` varchar(2) NOT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_codes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`postal_codes`)),
  `product_categories` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`product_categories`)),
  `is_inclusive` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_country` (`country`),
  KEY `idx_state` (`state`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_effective_from` (`effective_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_rules`
--

LOCK TABLES `tax_rules` WRITE;
/*!40000 ALTER TABLE `tax_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `tax_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `templates`
--

DROP TABLE IF EXISTS `templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `template_type` enum('email','sms','notification','page','component') NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `content` longtext NOT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_template_type` (`template_type`),
  KEY `idx_is_system` (`is_system`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `fk_templates_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `templates`
--

LOCK TABLES `templates` WRITE;
/*!40000 ALTER TABLE `templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('payment','refund','partial_refund','chargeback','fee') NOT NULL,
  `status` enum('pending','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `payment_method` varchar(50) DEFAULT NULL,
  `gateway` varchar(50) DEFAULT NULL,
  `gateway_transaction_id` varchar(255) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `gateway_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gateway_response`)),
  `fees` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_gateway_transaction_id` (`gateway_transaction_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_transactions_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `unsubscribe_links`
--

DROP TABLE IF EXISTS `unsubscribe_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `unsubscribe_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `channel` enum('email','sms','push','in_app') NOT NULL,
  `subscription_type` enum('marketing','transactional','notifications','all') NOT NULL DEFAULT 'marketing',
  `message_id` int(11) DEFAULT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_token` (`token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_channel` (`channel`),
  KEY `idx_message_id` (`message_id`),
  KEY `idx_is_used` (`is_used`),
  CONSTRAINT `fk_unsubscribe_links_message` FOREIGN KEY (`message_id`) REFERENCES `comm_messages` (`message_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_unsubscribe_links_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unsubscribe_links`
--

LOCK TABLES `unsubscribe_links` WRITE;
/*!40000 ALTER TABLE `unsubscribe_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `unsubscribe_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_activities`
--

DROP TABLE IF EXISTS `user_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_activities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(64) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `activity_type` enum('view','add_to_cart','purchase') NOT NULL DEFAULT 'view',
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_activities_user` (`user_id`),
  KEY `idx_user_activities_product` (`product_id`),
  KEY `idx_user_activities_session` (`session_id`),
  KEY `idx_user_activities_action` (`activity_type`),
  KEY `idx_user_activities_created` (`created_at`),
  CONSTRAINT `fk_user_activities_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_activities`
--

LOCK TABLES `user_activities` WRITE;
/*!40000 ALTER TABLE `user_activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_addresses`
--

DROP TABLE IF EXISTS `user_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `address_type` enum('billing','shipping') NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address_line` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(50) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_addresses`
--

LOCK TABLES `user_addresses` WRITE;
/*!40000 ALTER TABLE `user_addresses` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_audit_logs`
--

DROP TABLE IF EXISTS `user_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_user_audit_logs_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_audit_logs`
--

LOCK TABLES `user_audit_logs` WRITE;
/*!40000 ALTER TABLE `user_audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_documents`
--

DROP TABLE IF EXISTS `user_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `document_type` enum('identity','address','business','tax','other') NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `verification_status` enum('pending','approved','rejected','expired') NOT NULL DEFAULT 'pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_document_type` (`document_type`),
  KEY `idx_verification_status` (`verification_status`),
  KEY `idx_verified_by` (`verified_by`),
  CONSTRAINT `fk_user_documents_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_documents_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_documents`
--

LOCK TABLES `user_documents` WRITE;
/*!40000 ALTER TABLE `user_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_follows`
--

DROP TABLE IF EXISTS `user_follows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_follows` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `follower_id` int(11) NOT NULL,
  `following_id` int(11) NOT NULL,
  `type` enum('user','vendor') NOT NULL DEFAULT 'user',
  `notifications_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_follower_following` (`follower_id`,`following_id`,`type`),
  KEY `idx_following_id` (`following_id`),
  KEY `idx_type` (`type`),
  CONSTRAINT `fk_user_follows_follower` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_follows_following` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_follows`
--

LOCK TABLES `user_follows` WRITE;
/*!40000 ALTER TABLE `user_follows` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_follows` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_logins`
--

DROP TABLE IF EXISTS `user_logins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_logins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `login_type` enum('password','oauth','two_factor','sso') NOT NULL DEFAULT 'password',
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `device_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`device_info`)),
  `success` tinyint(1) NOT NULL DEFAULT 1,
  `failure_reason` varchar(255) DEFAULT NULL,
  `session_duration` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_login_type` (`login_type`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_success` (`success`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_user_logins_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_logins`
--

LOCK TABLES `user_logins` WRITE;
/*!40000 ALTER TABLE `user_logins` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_logins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_payment_methods`
--

DROP TABLE IF EXISTS `user_payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_payment_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `stripe_payment_method_id` varchar(255) DEFAULT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `last4` varchar(4) DEFAULT NULL,
  `exp_month` int(11) DEFAULT NULL,
  `exp_year` int(11) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_stripe_pm_id` (`stripe_payment_method_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_payment_methods`
--

LOCK TABLES `user_payment_methods` WRITE;
/*!40000 ALTER TABLE `user_payment_methods` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_payment_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_preferences`
--

DROP TABLE IF EXISTS `user_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_preferences` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `language` varchar(10) DEFAULT 'en',
  `currency` varchar(10) DEFAULT 'USD',
  `timezone` varchar(50) DEFAULT 'UTC',
  `marketing_opt_in` tinyint(1) DEFAULT 1,
  `email_notifications` tinyint(1) DEFAULT 1,
  `sms_notifications` tinyint(1) DEFAULT 0,
  `push_notifications` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_prefs` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_preferences`
--

LOCK TABLES `user_preferences` WRITE;
/*!40000 ALTER TABLE `user_preferences` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_preferences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_product_views`
--

DROP TABLE IF EXISTS `user_product_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_product_views` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `referrer` varchar(500) DEFAULT NULL,
  `view_duration` int(11) DEFAULT 0 COMMENT 'Time spent viewing in seconds',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_user_product` (`user_id`,`product_id`),
  CONSTRAINT `fk_upv_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_upv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=128 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_product_views`
--

LOCK TABLES `user_product_views` WRITE;
/*!40000 ALTER TABLE `user_product_views` DISABLE KEYS */;
INSERT INTO `user_product_views` VALUES
(2,4,5,'gllkuju74p0bk7lnfkvtmv7hi7','172.68.42.185','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','https://duns1.fezalogistics.com/product.php?id=5',10,'2025-10-04 10:27:40'),
(3,4,6,'gllkuju74p0bk7lnfkvtmv7hi7','172.68.42.184','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','https://duns1.fezalogistics.com/product.php?id=6',10,'2025-10-04 10:42:36'),
(6,4,6,'gllkuju74p0bk7lnfkvtmv7hi7','197.234.243.89','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','https://duns1.fezalogistics.com/product.php?id=6',5,'2025-10-04 10:54:15'),
(9,4,5,'2fcoj19v80drm3chn628c8dp8o','172.68.42.185','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','https://duns1.fezalogistics.com/product.php?id=5',5,'2025-10-05 10:46:48'),
(10,4,5,'5t4fmnsk376h5v7e9rj20i02rm','197.234.243.89','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','https://duns1.fezalogistics.com/product.php?id=5',3,'2025-10-05 12:49:14'),
(13,NULL,6,'0hqqd50ab5n6qbq01jljt2e4t7','172.68.42.185','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36','https://duns1.fezalogistics.com/product.php?id=6',54,'2025-10-05 20:45:12'),
(15,4,5,'7nj1qibuv935ev4u2dcdbsb5df','197.234.243.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','https://duns1.fezalogistics.com/product.php?id=5',286,'2025-10-05 22:59:10'),
(16,4,5,'7k06j81h4qqthu36dse9pect81','172.69.254.165','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','https://duns1.fezalogistics.com/product.php?id=5',6,'2025-10-06 10:44:08'),
(17,4,5,'7k06j81h4qqthu36dse9pect81','172.69.254.165','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','https://duns1.fezalogistics.com/product.php?id=5',4,'2025-10-06 10:47:22'),
(18,NULL,5,'ig79tt2ahh2als6fig484b726h','172.68.42.185','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36','https://duns1.fezalogistics.com/product.php?id=5',13,'2025-10-06 11:14:11'),
(20,5,5,'5lr49i9p7jc50meom482j5ilqj','172.68.42.185','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','https://duns1.fezalogistics.com/product.php?id=5',7,'2025-10-06 18:09:20'),
(21,4,5,'9ag5gnnn9b4kk0ebuodo86j48g','172.68.42.185','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','https://duns1.fezalogistics.com/product.php?id=5',13,'2025-10-06 21:48:45'),
(22,4,5,'9ag5gnnn9b4kk0ebuodo86j48g','172.68.42.185','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','https://duns1.fezalogistics.com/product.php?id=5',1,'2025-10-06 21:48:49'),
(26,NULL,5,'9ag5gnnn9b4kk0ebuodo86j48g','197.234.243.78','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','https://duns1.fezalogistics.com/product.php?id=5',9,'2025-10-06 22:40:11'),
(27,NULL,6,'n0rlrahh7tta62j23g0kdd4rdv','172.68.42.137','Mozilla/5.0 (Linux; Android 13; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.6312.105 Mobile Safari/537.36','https://fezamarket.com/product.php?id=6',283,'2025-10-07 06:56:47'),
(28,NULL,6,'n0rlrahh7tta62j23g0kdd4rdv','197.234.242.126','Mozilla/5.0 (Linux; Android 13; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.6312.105 Mobile Safari/537.36','https://fezamarket.com/product.php?id=6',327,'2025-10-07 07:02:26'),
(29,NULL,5,'n0rlrahh7tta62j23g0kdd4rdv','197.234.242.126','Mozilla/5.0 (Linux; Android 13; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.6312.105 Mobile Safari/537.36','https://fezamarket.com/product.php?id=5',13,'2025-10-07 07:03:40'),
(32,NULL,5,'n0rlrahh7tta62j23g0kdd4rdv','172.68.42.99','Mozilla/5.0 (Linux; Android 13; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.6312.105 Mobile Safari/537.36','https://fezamarket.com/product.php?id=5',5,'2025-10-07 07:05:36'),
(33,NULL,5,'n0rlrahh7tta62j23g0kdd4rdv','172.68.42.99','Mozilla/5.0 (Linux; Android 13; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.6312.105 Mobile Safari/537.36','https://fezamarket.com/product.php?id=5',6,'2025-10-07 07:05:37'),
(34,NULL,5,'n0rlrahh7tta62j23g0kdd4rdv','172.68.42.98','Mozilla/5.0 (Linux; Android 13; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.6312.105 Mobile Safari/537.36','https://fezamarket.com/product.php?id=5',5,'2025-10-07 07:05:37'),
(35,NULL,5,'n0rlrahh7tta62j23g0kdd4rdv','172.68.42.98','Mozilla/5.0 (Linux; Android 13; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.6312.105 Mobile Safari/537.36','https://fezamarket.com/product.php?id=5',6,'2025-10-07 07:05:37'),
(45,4,5,'srro74jd0kk9ui75h5e0f12tim','172.68.102.251','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','https://fezamarket.com/product.php?id=5',33,'2025-10-07 08:16:33'),
(46,4,5,'srro74jd0kk9ui75h5e0f12tim','172.68.102.250','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','https://fezamarket.com/product.php?id=5',7,'2025-10-07 08:16:58'),
(47,4,5,'srro74jd0kk9ui75h5e0f12tim','172.68.102.250','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','https://fezamarket.com/product.php?id=5',4,'2025-10-07 08:17:52'),
(48,4,5,'srro74jd0kk9ui75h5e0f12tim','172.68.42.71','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','https://fezamarket.com/product.php?id=5',1579,'2025-10-07 08:45:58'),
(49,4,5,'srro74jd0kk9ui75h5e0f12tim','172.68.42.71','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','https://fezamarket.com/product.php?id=5',466,'2025-10-07 08:53:45'),
(50,4,5,'srro74jd0kk9ui75h5e0f12tim','172.68.42.70','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','https://fezamarket.com/product.php?id=5',0,'2025-10-07 08:53:47'),
(61,NULL,6,'l0u1vnvvp3c2jb8hjk9tuin62r','172.68.42.70','Mozilla/5.0 (Linux; Android 13; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.6312.105 Mobile Safari/537.36','https://fezamarket.com/product.php?id=6',5,'2025-10-07 19:52:20'),
(62,4,6,'6i92l19mjuvbidpk2vgn6tsapt','172.68.42.71','Mozilla/5.0 (Linux; Android 13; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.6312.105 Mobile Safari/537.36','https://fezamarket.com/product.php?id=6',4,'2025-10-07 19:53:52'),
(64,NULL,5,'qfsru5fi6vnmbsgqdpihfqagdv','172.68.42.71','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','https://fezamarket.com/product.php?id=5',15,'2025-10-07 20:02:33'),
(68,4,5,'brg0qejtnkjch2cfadgqa6n51v','197.234.242.127','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','https://fezamarket.com/product.php?id=5',9,'2025-10-07 20:44:47'),
(71,4,6,'e0khufm7guchnts4s22poj5861','172.68.42.48','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','https://fezamarket.com/product.php?id=6',13,'2025-10-07 23:52:32'),
(72,NULL,5,'mouqtvdj9bfdu70i6j3lvjh0jc','172.68.42.130','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','https://fezamarket.com/product.php?id=5',3,'2025-10-08 07:06:47'),
(113,NULL,6,'6i92l19mjuvbidpk2vgn6tsapt','197.234.242.126','Mozilla/5.0 (Linux; Android 13; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.6312.105 Mobile Safari/537.36','https://fezamarket.com/product.php?id=6',3,'2025-10-10 06:14:58'),
(114,4,5,'31ho1rvpr8d4de689krn7198la','197.234.242.127','Mozilla/5.0 (Linux; Android 13; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.6312.105 Mobile Safari/537.36','https://fezamarket.com/product.php?id=5',3,'2025-10-10 06:15:38'),
(115,4,5,'31ho1rvpr8d4de689krn7198la','197.234.242.126','Mozilla/5.0 (Linux; Android 13; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.6312.105 Mobile Safari/537.36','https://fezamarket.com/product.php?id=5',10,'2025-10-10 06:16:26'),
(120,4,6,'rgsf62dhl1p5l8jj9gqa7bh3lf','172.68.42.71','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','https://fezamarket.com/product.php?id=6',5,'2025-10-10 06:44:25'),
(127,4,6,'31ho1rvpr8d4de689krn7198la','172.68.102.31','Mozilla/5.0 (Linux; Android 13; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.6312.105 Mobile Safari/537.36','https://fezamarket.com/product.php?id=6',37,'2025-10-10 13:59:29');
/*!40000 ALTER TABLE `user_product_views` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_profiles`
--

DROP TABLE IF EXISTS `user_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `profile_image` varchar(500) DEFAULT NULL,
  `cover_image` varchar(500) DEFAULT NULL,
  `biography` text DEFAULT NULL,
  `website` varchar(500) DEFAULT NULL,
  `social_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_links`)),
  `privacy_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`privacy_settings`)),
  `notification_preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notification_preferences`)),
  `timezone` varchar(50) DEFAULT 'UTC',
  `language` varchar(5) DEFAULT 'en',
  `currency` varchar(3) DEFAULT 'USD',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_id` (`user_id`),
  KEY `idx_user_profiles_timezone_language` (`timezone`,`language`),
  CONSTRAINT `fk_user_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_profiles`
--

LOCK TABLES `user_profiles` WRITE;
/*!40000 ALTER TABLE `user_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_role_assignments`
--

DROP TABLE IF EXISTS `user_role_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_role_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_role_unique` (`user_id`,`role_id`),
  KEY `role_id` (`role_id`),
  KEY `assigned_by` (`assigned_by`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_role_assignments`
--

LOCK TABLES `user_role_assignments` WRITE;
/*!40000 ALTER TABLE `user_role_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_role_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`permissions`)),
  `is_system_role` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_name` (`name`),
  KEY `idx_is_system_role` (`is_system_role`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `fk_user_roles_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_roles`
--

LOCK TABLES `user_roles` WRITE;
/*!40000 ALTER TABLE `user_roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `csrf_token` varchar(64) NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_session_token` (`session_token`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=119 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_sessions`
--

LOCK TABLES `user_sessions` WRITE;
/*!40000 ALTER TABLE `user_sessions` DISABLE KEYS */;
INSERT INTO `user_sessions` VALUES
(1,1,'e1654d2204296d2e9e2e580616a9744bdde20c55f326981c90dd58e01e1e2d0dacc37f6992964a9a4ae3f1f1f0fa3653656d90cc1e5ee1448442b4522e62a81b','197.157.155.163','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2025-09-11 17:50:31','2025-09-11 18:50:31',1,'09331009433d0babe15f2a59f0705954d359e6951cc10c6ea8ab261b6ea72854','2025-09-11 21:50:31'),
(2,4,'fe8d72ba1cd07e56ac312d78f08701d3257c0af5481f2f6bd5fdd52367a685926b027db24ebbbca2d681e48e7af61e64b715419eeb098986a55291dd7293cf3d','197.157.145.25','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-14 20:45:42','2025-09-14 21:45:42',1,'6b923c8a66d1674bbcd17ebbfe977793eef8d2670de119389104dcef2051357e','2025-09-15 00:45:42'),
(3,4,'ff880e6fb3c6ab3bababc31e633d5be0324ffe4dc0688e39dbc01bbaf2042d1c18f54d56e5c6aa58708de36db0ad462f1476754091065d0ad269c27c1c14be1b','105.178.32.82','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-15 15:09:28','2025-09-15 16:09:28',1,'b3233fde0d2673231819456355f082f470cbbec8a69eed9bb4a0969a6f0a5ad7','2025-09-15 19:09:28'),
(4,4,'5b25d47ee859b5b6f6c515e7f0acf4e56e285a3f59a6451c8a5a166d5f9e5fea98fefd67eafdabd08744908fe239959353006e8e120e05367381a321c6e116c2','197.157.155.6','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-15 17:22:52','2025-09-15 18:22:52',1,'ce3b39495005f73048a5cf91e3c8deaf684defae8fcb70517dc774a680847239','2025-09-15 21:22:52'),
(5,4,'0d1dcfbfbd356513bb531a18627a96114d1d5321d9ffae9d79d08332a8004ced3fa0dd42b86316050fc3c0da471ca54ca2cf5ac03a2eadc47d22b96e0cc54e0e','197.157.155.6','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-15 17:29:35','2025-09-15 18:29:35',1,'474d6a4b6a916600337f2f9f0aa39697f668b22105ce44cc6c84d8593649c6c1','2025-09-15 21:29:35'),
(6,4,'9ecce897b923b4f8461cbfc308be28634ed4d7a8c6b520e5fe37658b59b29986b6c2acca0233957a2d1c1ace4795526c2d51aedea55b06cbd7ef70bb1a1f6ff8','197.157.155.6','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-15 18:00:11','2025-09-15 19:00:11',1,'9a684e5a7360e8cfc8fa9eba31b8107f22996e38fdd881c7aeb8478fe14baecb','2025-09-15 22:00:11'),
(7,4,'6a209fb3fa928c9818f50d945d86e1b2eef1c0bb6387f44c00cb0285f982ffed747f545da5e5e91b21c589374a5dec86a3d9161487ad7884de7e9dc42be3a9df','197.157.155.6','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-15 18:08:43','2025-09-15 19:08:43',1,'5aae8c8ec3ef68809ed03865471b93ee6e7ded6b35506ec03c60eb77326956e4','2025-09-15 22:08:43'),
(8,4,'31eff7e649fbef5b5009df6c69c11a883a1f8aaaa74cb7be40d7ad2d8d177f0decc7c499b3781f717869a3de951f968b57bbc1f5e413c991210a1c83db28945d','197.157.155.22','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-15 19:11:16','2025-09-15 20:11:16',1,'7e6e0c0e65090089112602c2405d0a5a523ab011b9b1c4ba46e81f8fc57dadb1','2025-09-15 23:11:16'),
(9,4,'2a41df3d0661b91a1a3c3f4089f1f1e602e9a1afa129dd904dfe747044f75d5206e35c7dac8045a07be13b65167f64225341973552f69c68bd093c2ec1e35d98','197.157.155.22','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-15 19:14:55','2025-09-15 20:14:55',1,'99d79fcbccc6cc24421caa733efeb49cbff537bb6d1d6b1ec1f2e56ca47e1d05','2025-09-15 23:14:55'),
(10,4,'ad96b5bd247d2f2a75a101e99f1ba83f02ee6790a857e72e9bc1ba4147b222983125f860f1bb4a573851e2d96ae40368ed3066f67dcf9988986592f1473b3767','197.157.155.22','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-15 20:19:58','2025-09-15 21:19:58',1,'bf6b85fd9531318f8aab8e140b249491786b3de80ca7cf9d0c3c23ea0acc2e8d','2025-09-16 00:19:58'),
(11,4,'9bbd26db276e02923b827a2e4d94460319d17e8a0d95dba916caad1ab9659956a456edd147faeffe2d0ffb28d22edc27d4e0918c145512e3c766e247c6ac7425','197.157.155.22','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-15 21:21:19','2025-09-15 22:21:19',1,'d1b5078286463a664178429896c413e9a762421e49970e2c69c3e808631955a0','2025-09-16 01:21:19'),
(12,4,'e71ea2be86bd51b48d108666fe1d058379f4010c8230dd88a421040a18e70fa36f68a43518d39464f88a5822ae3a63260753ce130db87cad50fc682f1032355d','197.157.155.22','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-15 22:58:23','2025-09-15 23:58:23',1,'3184ab76216f6b2b23019d7748fa9de084abaf302c91cb18447f45b3458f7fd4','2025-09-16 02:58:23'),
(13,4,'ad51fa8ca9f6dbfd513aceb12e16b84f4f82fc424383d7e40e9e1796cfed94356a2dc84eb074596fc386fb89658135105f213a748b0e6b46f8844c7274713b66','105.178.32.56','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-16 05:50:29','2025-09-16 06:50:29',1,'8a0c4fbb69399122a88237ef943c1a71287e9123d409255e5e1b8ec0b92c1be5','2025-09-16 09:50:29'),
(14,4,'ab820e967e2a60803ae8ad708caa94ddaf76397c8cdb2e2c859b9c0e4750ccf07f5e6011674a823fe71ffe7f29b7f903acbd13ce6af9d686538eff0136b37b57','105.178.32.56','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-16 07:35:24','2025-09-16 08:35:24',1,'00c8ab0dc8f51bdae66fbf4287379f0c696e3850ebdf2b474b82d9a0cf74ebda','2025-09-16 11:35:24'),
(15,4,'43566b07beb8b96eec9c5093b266f8e19e5036cca2a3188e4e969ededbae37e04ec7395b77605ab954418b2683c95126fe0c44d9694b22cfa0dec634db29745e','105.178.104.56','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-16 08:10:19','2025-09-16 09:10:19',1,'07060d5c4c96784bfdcca697fb0faf2a9a61d4306f5aafce65ba84bd14071fb4','2025-09-16 12:10:19'),
(16,4,'17a70883d6dc922f23b79cf26e86398643777262ca39e562414b838c44dc6b59f9645c6798d60a6a6d9c97d5bb8aaad5771c9bd42d817af3d621186fea3b5a09','105.178.104.129','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-16 11:25:14','2025-09-16 12:25:14',1,'5d8a570eaa0df9911f432762c89a302623ef14bf76b78dbf293330f2b2dc992d','2025-09-16 15:25:14'),
(17,4,'5f06dba291252182a8b2f0a42d27e664370dcbf24b6acad465640eec09158de832e4e0e24a493bac638a1fb4074e521506340b26d15ddd67dd59202f61f78fe4','105.178.32.65','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-16 16:10:15','2025-09-16 17:10:15',1,'643bfd2f1d062cb8ddc92397773c92c179773243fc704f9d6f1686a6ed260814','2025-09-16 20:10:15'),
(18,4,'d48785759ea8b4e32517219ae0e188a3392d11b7b691de768621d869fb45173486bd0c35aac6fa275c5dbe7705fa03e9059f92c66cabf920ff077f40c707a8c8','105.178.104.65','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-16 16:51:50','2025-09-16 17:51:50',1,'e008343dc0e36d559227eb129dd83f0d31b5795065360d0c8996e0b6ae0084b3','2025-09-16 20:51:50'),
(19,4,'8245a00989a6f128a028c5a0d28e56e1028ec4420468ef05ffd0fd3a2ccefe6744f93188b8051d4cb49a0085635d6b2532f20468313343d638278281bfc0afad','197.157.135.133','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-20 22:20:08','2025-09-20 23:20:08',1,'647ccf73e5fd4d974e8f051eeef2ed60e7214a523ebe8e62949dc51b631ba9c0','2025-09-21 02:20:08'),
(20,4,'592af2f528d10a25a9e3f81d5d50208360ca6e8858f21c97dbbdb033fe501ddc6acc37150bce90284ac0749a35859ccd2978a0214f1a9237b56326b16335f9db','197.157.135.133','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-20 22:21:19','2025-09-20 23:21:19',1,'390744a4592115ab2ddd1c07ceb1de1ca3fd481a6f70a56ab39de4801df1e51b','2025-09-21 02:21:19'),
(21,5,'6704b4de660ddc6e8d9164f3766f4fb51fa2e69d426914839dce67aa088fdf31040980c2da4814c55e0f7d394452d8cf65344ddcbd811f75ca1dc3e5a165847f','197.157.135.133','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-20 22:27:04','2025-09-20 23:27:04',1,'b378b0d439c6140bcfe6e651ae07d6238c17825ceaeb2c8c07b7b144d21f85b3','2025-09-21 02:27:04'),
(22,4,'f5e9dfdcd300c36413a2b387bc9b43f7c61bdab1a18a57cad62a41a4b1bb9e5e80902e3241ddb9b3baa402172811d0cdb074cf6e90021dae3bed8021f7cc2d45','197.157.135.133','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-20 22:31:22','2025-09-20 23:31:22',1,'e35af7b71dc7111e95584e52ddc69eead7715ee96484874670ec1f9c8c7e0696','2025-09-21 02:31:22'),
(23,4,'17f2c2a513cb00ea106ded0edfdc8465cf5ce7e94ee7a904eca94cb214fd4cfbe0e1ccd24a246831a472ec1bec15e64ec6586fb864076bf5d199da7b254e8c3f','41.186.132.60','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-21 06:57:04','2025-09-21 07:57:04',1,'6d761f39b7a07245e02f186554c44ddaf4b1ba0620d0a5f2900adf5c7abb030b','2025-09-21 10:57:04'),
(24,4,'4fb2f4a6be0ff7e4ce7a37455d4270094b7180ddeb15c0443817ce56b31c0fb34202f7cfd13e2c0a9f4502a3209fa9095c5be8ca6c4c9dd6850ca263115ec952','197.157.187.91','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-21 09:07:53','2025-09-21 10:07:53',1,'cd24a266e6ba806401e754034eaa260adebdec513f755fc4a276e7be12591015','2025-09-21 13:07:53'),
(25,4,'031730c9413b2bc112a1b8079542234cdf516ae67b41e63b744956aa8cfe70ed668bf0997479062aee68405c8214a86a281bc735b4f0331801249c30e7fb11c6','105.178.104.165','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-27 09:29:06','2025-09-27 10:29:06',1,'57becd5acda28d9ba6494d5229b87015d662c43dba50e78a7ab900c080caadb0','2025-09-27 11:29:06'),
(26,4,'2c270d1f3db48df927fd814152fe264f2193cec550c15a52d928835f2147a0e99112d7db0e4d52af6a75184e74c0cccb5b7f6b63b9f4671062c98453508bdc44','105.178.32.38','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-27 12:29:31','2025-09-27 13:29:31',1,'8c1ae6be54d60be95aec8f9de52bc07903ee90fb2acb62a0ddf3b6aeb084c972','2025-09-27 14:29:31'),
(27,4,'8578ddaec20f803f947ee962300a696d16e8b46b1779879e7227a4720610b8d7d3b379840372ed9c3d27c889537b3642b8dedd7ba6eb57db9e307f2969cb0df5','102.22.163.69','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-27 14:20:48','2025-09-27 15:20:48',1,'825db3a2d91457ba5207469df11722671107a066dc7c4fbe39325dfab372e8bc','2025-09-27 16:20:48'),
(28,4,'684c6bc5b380a264ad958463f0a226fb18c5811e28f57fa7d49dfea3ed06cfc0676ca96b3797f8f36b239861b6aa6bbd41d97cb1070b1aec449bc4f546c6beea','105.178.104.79','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-27 16:43:44','2025-09-27 17:43:44',1,'55e5420a7a4827c30822c04a90b5105f95a8741b8a011cea1d36e707e06918a6','2025-09-27 18:43:44'),
(29,4,'f6214bb5cdd9fa00a0da42123cdee0deae7b037dafd5ed5388419b894ff9852afdc00d196621873a55e2ee09d79c94c7a22454e38528962a879ad85d1c2e4235','105.178.104.79','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-27 17:26:47','2025-09-27 18:26:47',1,'8cf34af13f09929fbe934e1d33237600c6a6891c0f748e6f389257ef329edb70','2025-09-27 19:26:47'),
(30,4,'e06f0e1c761a523ae9debbddc229ccf36ba24e38c23de0ebeb742f9f298d83ae201a15ed741226a0525803a907d095acd13e340553745c9d7c31e2892ac579bd','105.178.104.79','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-27 20:44:06','2025-09-27 21:44:06',1,'6dcf8006144476cb1fa39c4eb77a51eb408dc1d6c170d761dee1d8af503254e8','2025-09-27 22:44:06'),
(31,4,'5dcf73d5a3e6ea2e1892b60e97b9e178c770b6fd910bfa84a6265e5c5585c58370ec2a82dc4ee350335b7becd15724c013e96cbfeab9df96888948e78404c67e','197.157.155.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-28 19:59:25','2025-09-28 20:59:25',1,'16cb55ad0e3660830ef85912f384848568c69ba82bd03e30f4298112ce8940ef','2025-09-28 21:59:25'),
(32,4,'b3c6ee02edd14faed1a563e12392bbb709f0028d02f37624de9f4f28ca429b0d8806bb6d739de17766049bb7835ed3db7722b0f6e3d64bd72ba380f632336f07','197.157.155.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-28 19:59:42','2025-09-28 20:59:42',1,'337515e2557d57195a027682a27d25a0d5ac7fd397d61c8fcc14f358808bc825','2025-09-28 21:59:42'),
(33,4,'bfc7b3693d12e1265c95e43133915967123bba580cf5dc9292c9a6fad4ed74de3d3c5d24caf8ec45421ae14ebc6fb0cc8db192501783fd98e2bb417e25777973','197.157.155.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-28 20:01:47','2025-09-28 21:01:47',1,'05115566b9fbf3d9a8df495f69044865717f069c2f3112bf1e76e113853b6af2','2025-09-28 22:01:47'),
(34,4,'f800eeee23f5af83c733ba28754198d66b90bd00ab0d3b645c5e5f889d5d247bb11f6addb191e9761599ecb5c5424966abcb8010c5613b972d5777acf05554b0','197.157.155.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-28 21:41:55','2025-09-28 22:41:55',1,'97791dd65cebf2f744f56cf943aee3f39aeda9d6237c0d39e73a01665961ba57','2025-09-28 23:41:55'),
(35,5,'b4325a34675292811edb3e1f9effdd4ddba65520e6ac85e2fdf20c395debdaa5e2b98d1ea4d07e3cb5724f4bf3a41a6cb0685dce6e8bee787c4e173bc4677845','197.157.155.7','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36','2025-09-28 22:06:43','2025-09-28 23:06:43',1,'ab39e5b5bf1749c35838f0515378d9314c244803593e05e4aedda2f075f612ee','2025-09-29 00:06:43'),
(36,4,'58b62e270a39c4844374068d20ce56f6453c6a42595695d8df61b374183c741dcdf5232310615ceb1b52edb422c063122d1c4b503425d3c188f55b3285303fe8','197.157.155.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-29 09:17:37','2025-09-29 10:17:37',1,'12c5918c7bc8281d3c8c43e467556e6ce7b8f577fee7342ba4c54e05f7150288','2025-09-29 11:17:37'),
(37,4,'c27c80fad43a570f78ef162a1e097cd6809e0e74f8f6eec3e4265778ce0c6e6a5e43e32cebd2bb34664523380c112c139bdc926827fa8a1d76b3a709994ea01a','105.178.104.179','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-29 10:59:37','2025-09-29 11:59:37',1,'3035757785b160ee890104315cad50aacf959c44d2c2deb50027d79f5724519d','2025-09-29 12:59:37'),
(38,4,'5dd0498b487cda1d234c36f8d9961084f4eb17222afc8416a5f09512bf274a957223372e11991b9e2d93cb601dd7003844f5a1c95811b198fa0d32c0beeba7c0','197.157.155.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-29 12:03:37','2025-09-29 13:03:37',1,'14361791933e49d8926f78052180fa7bb3b1dacf1b5f0ffd636a39051b8454c1','2025-09-29 14:03:37'),
(39,4,'f5cb06f07f2e95e10e431c72538807c04cfd84ae59cd45fc0d6a6e62af13b4d99e0f025c144169144a6a370df7940a00d6eabf0696c42f6085b334a20c6c91e8','197.157.145.29','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-29 20:00:41','2025-09-29 21:00:41',1,'e255444c25f55b822e1e1f9e59612a66d4926d9d7e6b39e3a947b3d4ae927bbd','2025-09-29 22:00:41'),
(40,4,'793aa6137c35413f9b5ff319d16f80ccf95362c18a3de1dacabe489b123c8b57d80fd0ed292932611d749a89c41b21eaa20dd7718192508b59c9a8114e3e154f','197.157.145.29','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-29 20:03:41','2025-09-29 21:03:41',1,'3a92d5dbf282bdc7bd0e14df477dab5a8dd3fc5bb45e88251c10fdb53d27a1f6','2025-09-29 22:03:41'),
(41,4,'44a68da316bab1583c308e8d1d63db83a2053a81078e037f80700837c367ec966afb6db6e81dcd4bee4234488697e90d90afbbd5bae8e4cd5accc9820a1b6e56','197.157.145.29','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-29 22:23:24','2025-09-29 23:23:24',1,'43605aa849a3604588392ad974b6b81981a48ab7f489ef8361ab2fb8b4a792f6','2025-09-30 00:23:24'),
(42,4,'ac88524ab244bce5e329c3bf13c26cf931fc6f8709e676c6bc10b9ce58db31a9b1091b44b574c5aaa0737df72a5a587edab7cda38f3fd06bc663864d13e09390','197.157.145.29','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-29 22:59:28','2025-09-29 23:59:28',1,'fe6a26e135a5ec15a158aeec52d0be8a4ac0d6d0cfc08c37ae97df7afe9e1f37','2025-09-30 00:59:28'),
(43,4,'9bfa9b51c7f42c72b38246dffe3e4b528218976513a5c846ccb81d9a344044dcdd09361e3b5d53971f94a036cbe24fac62aa4fd2815106b4af7f2b9a78948511','197.157.145.29','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-30 06:17:58','2025-09-30 07:17:58',1,'25264a17ab86e203fa8bf0ba03a263ba3fa2fc2158775d59afbfdffe3c4168c0','2025-09-30 08:17:58'),
(44,4,'4a00163e7970084cd76d7c123d477d819bd8f8f3f5ae532fffd5954cf1b13d9fe5043b7a19f76cdf3003f27cef70e9a7ccc5d439f7e9fdf0a0bea2bbc9f1afdf','105.178.104.179','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-30 07:43:26','2025-09-30 08:43:26',1,'876d6ae330ea0b48cd7628630ce939a81a0393b1a8537186033bc00957b097e0','2025-09-30 09:43:26'),
(45,4,'22eee994f35dcca04b268f7de5a345493af14817974b5cc1e2492bd4a1026ec01aa8b95538f8e6e1c086c9df65ac961c981a72d6e85da826ade94e490c6ecc27','105.178.32.179','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-30 08:48:23','2025-09-30 09:48:23',1,'802612fe026168d7dcd7986b0f46c5266728ef6ab2e9040a5b3dc10872a71304','2025-09-30 10:48:23'),
(46,4,'3541912729002aaf5fb4cd7fc81c79aade247f68fb3bdd4cf1dab6b7cdb07adaca69edb051e80528d69064486c26657a14d2d869ee889caf57d53843c6406033','197.157.155.132','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-01 20:40:33','2025-10-01 21:40:33',1,'39cac70a9c1ddce1aa62311e08912c81e41b6533d60e75ca98dcfa4801a3967f','2025-10-01 22:40:33'),
(47,4,'051f1dd921ef46c81bf61c83cedd720254a0d19140482e322b0a68dad8ffc896a2f757f4257b73c0dc02067df17334fea3ef9f661ba7d278a0ac950e446e6b57','197.157.155.132','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-01 21:41:18','2025-10-01 22:41:18',1,'64b1bc00173911ca6321cf84fff00d8a529babb318246c6a142fc2c4a06b2fd9','2025-10-01 23:41:18'),
(48,4,'44ad0165f43c1ed21110eb75c95cffc94e12a585bf6745f55ff72ee53c462e414764af9625488bbcbb4c991bee6af932d1747bd626819bc23b06b5edb8aaaa61','197.157.155.132','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-01 23:03:58','2025-10-02 00:03:58',1,'ff89d94cf1b71c4bee043b8108d687037397c9c77faa71cac9f3acba60bffb7d','2025-10-02 01:03:58'),
(49,4,'8a748ae0e01cb387805ec1fb67dd68e7cb7c3933833f9af0a4bdb26a601d9a995299b27a6d5654d010b2be55da6064afee19da0d9458cd77ff51aee092b0f32d','105.178.104.110','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-03 13:40:58','2025-10-03 14:40:58',1,'5148352a1cbba64168efc5d6feee4ea1a6c8a42505223a0d0568777aa9ed30db','2025-10-03 15:40:58'),
(50,4,'297da0c1557eb22aa93fc85a646f1b839b51f9aba1e83419e21bd1b27715462346af41c3ff0a93d53e451c718514c310e58f70b2ae706fba2258752b5c657ef7','105.178.32.109','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-03 14:37:32','2025-10-03 15:37:32',1,'c99ca84a9ced6b3d1dc3d77a759c0264b44aa60465f86ae4dbb40fa9bdeaa639','2025-10-03 16:37:32'),
(51,4,'0ce4b9d5786c9311fd5ea14d1172499b231182aa89d92044dfe61a26cdd9bbd470dcbe2696fa0edeffe214d4d1913b4d1317c469cb2b0459ea2c7b7c4464dce9','197.157.135.231','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-03 19:15:40','2025-10-03 20:15:40',1,'872a735f4ee0b6848549c9a8372557ebde25f481abb6013a8553feb6336c31ff','2025-10-03 21:15:40'),
(52,4,'e4167636298ffccd7a9990ffeaa5b9359b030ccee71041fdd52bb4a2a85997c36a51aeab5c7d480f9908d04ded70c3296016bba6b37753201e5686d47a6710eb','197.157.165.150','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-03 20:41:13','2025-10-03 21:41:13',1,'55c9a7671ee63d871fd5091537d2248ce44dbbd851fd351215610bc904356d51','2025-10-03 22:41:13'),
(53,4,'a4188d67841785a4b67f88dc9a785fbf1246bef8bc95402ed68d5b596584df0985eebabd1a2a816064dd1dccffaa9e57d90ced1dd496d1841a706277a2168f52','197.157.165.150','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-03 21:46:27','2025-10-03 22:46:27',1,'280c36d182e631427b50c83f9bfa6c0ba720302632b48ec5e30bd09a52456c04','2025-10-03 23:46:27'),
(54,4,'dfec9a488a59f33f87b90702971a9c8e60a44909413c021c683abeb1a3388049923286cade8ffc3be086b72409ffefbe9b1c3c3006ebe39c72b1e4830bcc6337','197.157.165.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-04 04:46:31','2025-10-04 05:46:31',1,'65ce1b94bedbbff53b5ccc7ca66b007fd4450d5de800a27f9e60d9696dc3ce51','2025-10-04 06:46:31'),
(55,4,'cb1dedcf7ad2feb58f63c7d525e5f993336fb002953c21ba2bb38a2652eef98c9620d0cf4200724892605f34e2e7ce64d11b1886e946c70bba73bd1bff9dca92','197.157.165.87','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36','2025-10-04 05:39:23','2025-10-04 06:39:23',1,'318356a320933509f6fa4db3be5ddac6a8c7b2695bcb2279da689bbb8258f621','2025-10-04 07:39:23'),
(56,4,'775f55588d2f2ae207eefa4ee72926c4056bad1a56b9068c1ba3cbb283fb3baf33ce07e8ff9acb3b6a4de2dd6df4c3912e5fcdc341ce8c7450b562a0a694ae97','197.157.165.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-04 07:03:20','2025-10-04 08:03:20',1,'479a06e02c309cfe46e03950e32e74ba1371b06ee10fa1ec84d9bb73fca6f680','2025-10-04 09:03:20'),
(57,4,'d55bf671964a86129413e4d76cb725e9078bbccc1185023cd984a911a400194a1281de0b87401387c595a1295cea24777e9216fa938cd2af57346821159079a0','197.157.165.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-04 07:19:38','2025-10-04 08:19:38',1,'a5018cd0d109be0bf3f38947064df24e361a3de192be0818c30c858112faa34d','2025-10-04 09:19:38'),
(58,4,'c8bc5fa4b866ee150c2df6cb5d7c3961a0282cfa85d294bc6efae687d4854dc61ac1bf30c341e85f32ec5c11ac1641d510bd41ebc48f3110fdce0f7f3b3b405b','197.157.165.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-04 09:14:01','2025-10-04 10:14:01',1,'d46b8258aa13cb9e345844435bd619950431e83cbe73e890547f1bd0d2e0f9a3','2025-10-04 11:14:01'),
(59,4,'73577409c0e95ded583d0f9cc42a892246f89650390168aa85fc350d0f5049a6c98609aa0958838b98113ff86752a9a9348b8fd891afdde8aa74147866973b76','197.157.165.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-04 10:18:18','2025-10-04 11:18:18',1,'f284450a4cd735586fb2cc151f1cc4129f695324755255b25621f84dfd570338','2025-10-04 12:18:18'),
(60,5,'57a2bbd775efd72ec1a33c0d2ea48494cb9243028e2315808db48e2fe88064068477fe014758741379aa9afb9e412c185e63859aa3ac9ef4a7bf6ab2ccdc1147','105.178.104.109','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36','2025-10-04 10:46:56','2025-10-04 11:46:56',1,'68bbc3395d254daa5479623aa615a93343441550769bf9bbfc45566d56dfa6fc','2025-10-04 12:46:56'),
(61,4,'a99f28c2b70123d788c45f0edba6118c909a1c8371e2e4fb87e11073692b44d3f3ba65606dd6332bcf9cd501284bc965a0734ed424798b821449c3c292b7e201','197.157.165.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-04 13:04:11','2025-10-04 14:04:11',1,'8d8553642d3ac87218834ffe281caf6bd1ee84b8e96c9f51800c2df7fd47b9b8','2025-10-04 15:04:11'),
(62,5,'20674bd6ef7408c3ca13cd3fc52488a2ba336eac2ea6c088688ca88e86a776eb24f7bf52d5e1c532fc3ec67b29f0c31412bfe60e0074d6ce6db37e3b0169d683','197.157.165.87','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36','2025-10-04 14:05:17','2025-10-04 15:05:17',1,'719f2e68a4d4a26442e72018e2d17b347ecae100356c4c9ee66c02e31be2698d','2025-10-04 16:05:17'),
(63,4,'2a949db87b9761d43b4cad1b590c4414ce22993413cede3d9b9cd7193cf6ce518a9dc207fe031d3e14ce8ec82dff13f35307fef538f394b20f7a8eb98e1dd07a','102.22.139.51','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-04 16:14:43','2025-10-04 17:14:43',1,'7d57afdae14f51c41bf8ee17ff8c54216efd9e09ac5165edf3e3d7918dd54810','2025-10-04 18:14:43'),
(64,4,'cf3523b0c52305b63d6c6b8d82f30f3e925dde7fd095dde95992db6f31c39ba3ee9df54313bd0bae0195809818a51a4c709389d71df7e3f78fa36b1e4041eaa6','197.157.165.35','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-04 22:54:31','2025-10-04 23:54:31',1,'12426cb06f1160e196ffa747ea649ba349aa309651bd5e0ffcc595a510888791','2025-10-05 00:54:31'),
(65,4,'f644b683ef25ccb3677616eae5e6be3243c5279dc5ef37cc9dbfdfd00fabb7d9140e2cb40cc1025d1b2022ec784f9f9af6d6c57b7dcf71bf2a3fd0eba11a8522','197.157.135.63','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-05 10:39:55','2025-10-05 11:39:55',1,'93b1d1a093d45937c42ebb666fcbe6e7f6347b7d5d6c3dfc21e4d88535840abd','2025-10-05 12:39:55'),
(66,4,'527b602be0c68eb9a5e0640d5e5ead4cc123accf8c9544137e47bd0bdb5c3d438ba65c691d9c81d2a34d30390f7cf840225e65703c900328df1474839197540e','197.157.135.63','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-05 12:45:41','2025-10-05 13:45:41',1,'2a8f3d708e8e551ed75063f917801fd401bba8fd6166f14d988dc2b05e1f6686','2025-10-05 14:45:41'),
(67,4,'c65f9d4d0fdd2df09ab40e244749c169fe23f4701e48450740e1a65f52c68b9b034ed3ed13405365790451eee74bb5cc9c60a74e3b783288df6dae8513d925b1','197.157.135.63','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-05 12:48:44','2025-10-05 13:48:44',1,'08d18bff8f410afa783b11a098491b8e1ff1fe67e58bae4f8d4ad16438c57f8a','2025-10-05 14:48:44'),
(68,4,'e4180f0c4765ee11b4b649f2bad9beac0a7d6e1502354b755515cf78a8d1d9c5e7d5a15d923f01de8f686b1452ae23b9b59dd8db26b47a701e7b479122511748','197.157.135.63','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36','2025-10-05 14:29:25','2025-10-05 15:29:25',1,'2d722df7ee00bb3f645422a6fa4ed9be07d80e7f2bcada1839614d11ca8df7bb','2025-10-05 16:29:25'),
(69,4,'3094176a0714c1454b1ac20a0aa1f260fb79c7db3acbbdee46e41b737cbbf636dd31f9ed434d518d2712067162d3ec7f425413a254db5f5b17e1f63e246c9890','197.157.135.63','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-05 22:46:08','2025-10-05 23:46:08',1,'405b2efb96221b0f1abdfc01dfc642dd68f6ff104a74cded8e0afa4909a365d8','2025-10-06 00:46:08'),
(70,5,'6c2e2195ee700a6b5ac271da5d360540aadaf9eb8f09288c8ed6c42d280c776ed9df283eb1ec9cf65cec8ad13e3e719b5b118a7be0a47d8930fdd84effcc4bef','197.157.135.63','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36','2025-10-05 23:00:13','2025-10-06 00:00:13',1,'0a40d32b10b76c2aafb61ae89ed4a4935fb807fdf78270849157ce94f9a274bb','2025-10-06 01:00:13'),
(71,4,'3c6f882ca0651cda0825535105980ff1706cb54dcfa5a9814cd4fce39f78ad63665a04f226c32b885c6d63f30ce7feb1893ac60592aeb2d7c2e8b90f351eacda','105.178.104.56','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-06 08:44:07','2025-10-06 09:44:07',1,'b0bad75297fc6917cf2c469aa9a91c408237b1e763c1fd6f520c125effb9e5c7','2025-10-06 10:44:07'),
(72,4,'8ed15acf60c94e2bf66e1ebe551bb864e2dc24ee1fe823bf61f33d8185228f571d01c605a5385797e99cc06ef9ae7e114e568e292d1f032e3ec35bbf928e851a','105.178.104.56','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-06 09:55:14','2025-10-06 10:55:14',1,'e99ff199b7531037e6b8b3f1f8c70dafb628e53acc1399a3051f705e4ff29bf6','2025-10-06 11:55:14'),
(73,5,'1a1005c42bba0b08ed541e9a33d0c9b2398c755b14b11232791b91bc9a0e0a072eac62fd668dcd481f7682fa2fa7b5571909c2b229e9c2a825830e55697f6f89','105.178.32.56','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36','2025-10-06 10:52:09','2025-10-06 11:52:09',1,'6fa2ab36d40b2592c0dacddba4339033d730846fedb0c37a5a4c84fb91d7fd0e','2025-10-06 12:52:09'),
(74,4,'fb01347cce35f49c50a5fb214fc65d82e4deef5ba80b7f140867c2ff950a2607ab753ec934e940574aaa63af95b68343d009f4a7517932869a80a2c4650baa8e','105.178.32.56','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-06 10:58:02','2025-10-06 11:58:02',1,'fa14f9ca6e3a97f245d815fc5af23c23b466e120096e708cac2f576808986d78','2025-10-06 12:58:02'),
(75,4,'59aa167be7c3135c6dbd02120b520fde5a300159c83adfcebddb345cc31a3aad1279e1f66a24ae4d3f2d8b0df1865b04e2bbdd44ad4357e6273cfc9653f1518e','41.186.139.85','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36','2025-10-06 11:15:05','2025-10-06 12:15:05',1,'4407a6e71f8e95f733dc197272cff5d6d92f0df00cf8a40d6045f0002bf037f0','2025-10-06 13:15:05'),
(76,4,'5e73a029e7c67466105a3a6452b40274d48b05fb20e9110de875aa4d24841a175120a520f268aafba6a858fb89c67eb62e462072d6bd305499c353703324b96a','105.178.104.56','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-06 13:22:44','2025-10-06 14:22:44',1,'b904d1c1f0e032785421425071234e2f57a087cb9bbc05a3f418d40bc34f6416','2025-10-06 15:22:44'),
(77,4,'256aa85305ad907fc08e5b55e84f45e67f390a0065266448d56f2c5191bf7f364b5f6c0b453393dd6fd46e1beba0dfb8bae278af4fd2c322da8e0c1eee5eea2f','105.178.104.56','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-06 14:00:25','2025-10-06 15:00:25',1,'69576d5f3615db2c502b1b21793748c21473bb4f36a0f9bf5e60421160226173','2025-10-06 16:00:25'),
(78,5,'542696266278fe03cf9992bb3e33e6fd3529d76c354d7fc7654e3c09c9b3c060dcdc7677022065678b6de1e3b117331204d8154549833510dd70c6e15acf101a','105.178.104.56','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-06 14:01:49','2025-10-06 15:01:49',1,'5ed41757404d88ec1a22d046d98374da6c899f5019dafda582f936f83cebd5bc','2025-10-06 16:01:49'),
(79,4,'94bd7e20e4f750b183369748a17cd2325c9b1dcb4c0f7d03492413d715cbde60a6e60e6e15f879fe65feca4e171fe8e967dceded1dd70c10035ce5cd3e71e627','197.157.155.84','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-06 17:57:17','2025-10-06 18:57:17',1,'63b5a47c4a5f4fe791054211712419ad53605cea1bfe88a11bb448a0bc0e53cf','2025-10-06 19:57:17'),
(80,5,'8d56c38d33b61ba6621c5721dcc224b5d006e0382bc593387cce2efa98a8ae3e4416df2e36fd33f4b3b348ea58fce20f6f66098d473f01675b3c6f84d10303b9','197.157.155.84','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-06 18:01:21','2025-10-06 19:01:21',1,'f3158c371c7470eadb584b41dbd01efb18758ca28352606126dc6d400b1a9c84','2025-10-06 20:01:21'),
(81,4,'54872b8c9b75ad0f4656fd8d4873c5ff54fc84097b6c307421244353133adcd3b7ced9f1d722c57db7c168bcc4c02d0aa02b49bbef33379ebc420f80a686c9d3','197.157.155.84','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-06 20:55:51','2025-10-06 21:55:51',1,'04d85b13b9d12c7b787317c9cb95414b0c69d629cb10fa274c30ccd9b5aabf48','2025-10-06 22:55:51'),
(82,4,'1b857ebba26ac1897138dceb04ce3c59014d87b37b7400c8ed491dea458a4f6601132c3170cca1cf0d0f1f4d1d81de2a3266a84fdf76154377f39abf93f3c882','197.157.135.201','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-07 06:48:28','2025-10-07 07:48:28',1,'890e4856908dc667bc10ac9e44e321401420a56d8ce00c85546ad44541b35ff3','2025-10-07 08:48:28'),
(83,4,'4c8dfc8d50d95d99d9d75e0ee0d55ebb63ab1813b735535402a93dabe61b52ea70ae2ab5c148ebf70969d98469795fb9a3cbf35d27e05467bcd03d494d4a74a0','197.157.135.201','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-07 08:15:54','2025-10-07 09:15:54',1,'f47811096607767d1ba1d97ed999e16d36dabb2b19c93afb345cb7026042dac0','2025-10-07 10:15:54'),
(84,4,'26db5ba62f5b5a497223e2a042401557a2d8f24dcfae7308655265f6766dfa88bba5cbbe4fdda1142dc2dfb91c06c61cd1b337204d280f5afb6c4056b15d0930','197.157.135.201','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-07 14:46:50','2025-10-07 15:46:50',1,'cd013b4b3375ab104ab88566bb1487bd023ef716f1161a447da62ef4d689a581','2025-10-07 16:46:50'),
(85,4,'26773b05476a38a9ac2d29b113286761b834e9748b379e8a65c3920de7d4a4f76e9f572d1d8ffe805e00315095cb06b515a0b7784103607867a8acb877686df0','105.178.104.74','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-07 16:59:45','2025-10-07 17:59:45',1,'9db074d30044945a49a031abfa08608cd66f976d6ad201b807fcf9eb22452c45','2025-10-07 18:59:45'),
(86,13,'562def2e3e74d0719fc41a5dab71f9de383b2becadcf4eff0b9819f9119d9d84155a654d0e8b4058b3e910cbacf8cf04c180c94ab98991b8c259a1049bb223ba','105.178.32.74','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-07 17:07:32','2025-10-07 18:07:32',1,'f3ab7bc4f64f88749be702d40a98f1ed8c49184836f3376209e3315d8f023f70','2025-10-07 19:07:32'),
(87,4,'82a45864ec54ded59baf34df43f0f9d3adadc8545f3e14a7c29338e274ac3d8f75f451cdcdf6fb9939e49ed9522914adcf8368c320fb716b254345e39ba32a4d','105.178.104.74','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-07 17:33:01','2025-10-07 18:33:01',1,'2baddd119fe8c55cb83605553f0fae1ead2301e16a68c54bf0911b776bd56786','2025-10-07 19:33:01'),
(88,4,'2807a9fd8e9c8bbc8dfb1468c11924690c4c87848b4ef79627dda0392c3fed8d78fdfa77a90b4137f731c46a04aaebf5295b76ba3fcad41d3fcb990f3d75ae50','105.178.32.74','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-07 18:51:55','2025-10-07 19:51:55',1,'75c6dc6f4b467215232a3c65e99e38f0a566b5b4bc7ccbf3cb0f00c63ef6279a','2025-10-07 20:51:55'),
(89,4,'104d73d48906ba2571330ef155cb378df6330016852689148b5aeb50fc7d1e399e335b9eb6a82f0a60012f95054b4693ae9187c4f5a1b6003c5909126bad3ae9','197.157.145.87','Mozilla/5.0 (Linux; Android 13; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.6312.105 Mobile Safari/537.36','2025-10-07 19:53:10','2025-10-07 20:53:10',1,'89d4941bb76d05228d945caf5af04519b90945e0b2e6a9e8931ac95efba5cdbb','2025-10-07 21:53:10'),
(90,4,'8ddc6804f3ca47fc4d147d626700d36c55d9225965cecdfe8f3928872967d75b3405a68c73fdd2b3ceb234d93cbbf3fd29ef62f3a751c123efbb86a7ce7ce616','197.157.145.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-07 20:40:43','2025-10-07 21:40:43',1,'2a106ba7ed88cfb5971c1a644975e569f287ed1f6a3a7cbda189f26063edf881','2025-10-07 22:40:43'),
(91,4,'db27971c6b30e0ad4032d90e6d2e406a84d7d6b3d621b7272ff3164de0c160d87bf1a3111682ea8d3bae646e3687aaba5b9e39217d041f9d8886c40339dcffa7','197.157.145.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-07 23:52:13','2025-10-08 00:52:13',1,'1a82f80e48d9e2b427b3c25358c2c0daf91447db490d0f19e3b40cec4db811b6','2025-10-08 01:52:13'),
(92,4,'90407eb8777e1e5e687a739bbb7b6f99b3223a378278f6d9d2e2940ff3710202cfd1f58f60b16421822364febdcd20703541a505d116a445a108448ebdf7ef65','197.157.145.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-08 07:06:55','2025-10-08 08:06:55',1,'135fe292325d21f512430471987bc03925f84b23bc58c29fdda79e08190c771c','2025-10-08 09:06:55'),
(93,4,'e2eb2f1e8ef675f5a13b87c472e55f356a2762aa3013ee331df6f3f4e1a5d36524e5c92130e076449714bfbe69d5e8d6f1883681585bf23d4ed2000d09f246e6','197.157.145.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-08 08:18:03','2025-10-08 09:18:03',1,'526c74d03435a9748c4dec8428d52b7ce16aefd19eee9b15d06808cb6a602297','2025-10-08 10:18:03'),
(94,4,'71a277e689e44f0b57a48ad09232e96f468e120e8e37ea3f022f6f103dca350a2fa25a92ea4348593996079dbe435f83319674c3b90455ffc7182c58d68758ca','197.157.135.108','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-08 21:00:43','2025-10-08 22:00:43',1,'209a65533e72b25dc2a669b7c0195624ee03501ff332e611cbbaca5a9c1818f8','2025-10-08 23:00:43'),
(95,4,'9fe805f7cc1d2380772a583b459577fa6d9099389940e8a95c623d51ccec48d4009fc3b29d0c58e273fa1df45f8936e4b6c7c6324bc5ebdf4be58644d6178185','197.157.135.108','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-08 21:39:22','2025-10-08 22:39:22',1,'6dd494ace9780f1a164fa48382d8f391a2434fdd6327219256f58e0e3f69df94','2025-10-08 23:39:22'),
(96,4,'69fbb2284597d77d6bdfc312be78e30532eed220685aca6aa92d5943bcd9bfdf861b20ad608dda7b84e0ba6a1c7848e49bf85d6fa4d6a30700ba5f7a44b72e01','197.157.135.108','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-08 22:42:28','2025-10-08 23:42:28',1,'6ee7dffff830e71cd8a897a8659a3d5785c93a3f76a02bfc4f53dc9d3571e8b2','2025-10-09 00:42:28'),
(97,4,'8d348702a54e483d6d4510991f16ca580cdc13e8725df751676b9ea72213df20ee804aaa71c0be6f0cdd37fc76c0958b34a6bd171bc09050d48fa63a06adc6d8','41.186.139.192','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-08 23:43:21','2025-10-09 00:43:21',1,'37d5934fb5080d5584466c22e3d4a6abcc30bb043f41a8a76fe02c4bc6098e1e','2025-10-09 01:43:21'),
(98,4,'d25912e96d78af802d210d4bbe3d88b72edd6e37dee7a0a7ad2ab1396e0829fc3ca948cae78bcfdb2b1a8a7b12e6183e4568400739b242aee877c69162707c24','197.157.135.11','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-09 06:37:27','2025-10-09 07:37:27',1,'50f3870774080365396a10cc42b2ef90ebe286fe82dd54c338643ab8ea085a62','2025-10-09 08:37:27'),
(99,4,'5ea7e0a26859483dc80b5cf54a151a0e25dbcafa6a0f1535d9769c50e3fd94b72163f137744f64b204d46b1ac48782cbba2392bb9588fc735976acabafe1c83c','197.157.135.11','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-09 07:20:31','2025-10-09 08:20:31',1,'630cc2d18b73d75aa19ac63a08e8f5e2a05cdf27ee69efb79915732da6709ec1','2025-10-09 09:20:31'),
(100,4,'febbe3e02f2990605832ee0a0e3776f45553c4fd59bfb752c0b8fbf0fbd69d24d240e2672d00511ad07749117b2f80702035f852411128f4f65ef3f535462d2b','197.157.135.11','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-09 08:15:52','2025-10-09 09:15:52',1,'f736a018ae9866648d6ce22cbb414a2c409b0765c92f57cdfa4360c82ba19644','2025-10-09 10:15:52'),
(101,4,'fb44e2411a415d9697cb97911b42b2ae86caa2bc1c0c7d226f857bc8973fb82548a1d2abef19ad9bc7601fd037bb42fb70856df9840b51a821131a6ac6dcab0f','105.178.104.255','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-09 09:11:32','2025-10-09 10:11:32',1,'4efdac97ca61ec01c846788c8e28c7eb183098bc80cb824ff22a678701b3d5e5','2025-10-09 11:11:32'),
(102,4,'3440ad9408a3d3067ebd3fae48fe2f4aa642ae17377820a8334465c7c7f962dbc60762c9769e0682c5d3307b1424c24380c004a2fbb83044bd685272b3fd654a','105.178.32.255','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-09 11:26:00','2025-10-09 12:26:00',1,'fd0bc2b79170ef201d5e89a187e4f4678f15a862d3064687e2faa786aaf94538','2025-10-09 13:26:00'),
(103,17,'d502e84f3af158ac1d340d0cb9d250fc6b46b78cd066a7dab44b5d053432afaa8c08e8b0be6bd34e60078c83d7b52bbb2f2df4e68c549ffd25ebda6bf774e80e','2401:4900:839b:85b7:211d:bd45:b205:7089','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-09 13:42:04','2025-10-09 14:42:04',1,'b673421efd0eb73fa84bbbb1644c3b56575ed725d2f275f98f56355bd53c6eed','2025-10-09 15:42:04'),
(104,17,'71a0dae683b42193f9056ceac6bff775e6bb6f67e283ef6d0eb6a7a565bb31fb0f7b711cab013f7f79c538641fa7ce2003cd60db6a94f17f1a0dc14c7922073f','2401:4900:839b:85b7:211d:bd45:b205:7089','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-09 13:53:48','2025-10-09 14:53:48',1,'69861b14ece7955a3f74f66e31baea7c1fd994a02da495cff712ad9f8547374f','2025-10-09 15:53:48'),
(105,4,'417df46e35d82a2a5bd1f27f0f2fd61b9f8851df23fe2f26d1e52a25e158dc47d0e774612a9fb8e29e0877ae49cdb7e9ac77e75ba39e642946d9c186b0c87a7b','105.178.32.255','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-09 14:28:28','2025-10-09 15:28:28',1,'fd67d260743e1aa1a0a9308b4b3cbc69c599a8e05864a5073b2330bf823ba330','2025-10-09 16:28:28'),
(106,4,'1b4210ecebc468bafabeaac541c91d1bbbfda9efe43bc8d90bd3d3a02f567e0f916b5eaf3d2b1e2a795dbd04f80b153887b51eea50f365542c76698918925d02','105.178.32.255','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-09 16:12:36','2025-10-09 17:12:36',1,'aab555633eb2b039cc2dc89c748784139d43bcf48d8b0931514ae0bcfc2b887b','2025-10-09 18:12:36'),
(107,4,'67d3e9d8089d85a3ea8b03f2c1998edc0ecdd1fbb3c4435acda056f09da39b6214b4c425fe12f54b9a79ab404676cf833e1e8874cab24a77fcf574c6dbc1258f','197.157.135.155','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-09 16:33:59','2025-10-09 17:33:59',1,'bdb8f7b4eaff1702b509acd50559fd3f83556e0d63854fb9779ba05619706118','2025-10-09 18:33:59'),
(108,4,'b93f8bb2290934ec4907806e4756e488e351312ec6fa76e4148ccefa734ee50a74103bfb4d9d1a21834702b0c65ba093914e11b79d2becac84e9bbde85f9b724','197.157.135.155','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-09 18:05:18','2025-10-09 19:05:18',1,'afb8c08f0b9a62c7a9933df6953696f8820309111757e138d3fd69299c5f38d3','2025-10-09 20:05:18'),
(109,4,'0074c8cc4d8866404fb2c34c6d0e641b6f3a2f99b03d1f130eda2326c6065c3f286abe8ed4cba32f9ee2e223611cfba8809c4a16e7cc32ee1808aeec712cc370','197.157.165.125','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-09 19:26:00','2025-10-09 20:26:00',1,'7662c5ff110377c81d11a230a2794d6651fdfe99ee640ae4f757854f938f0e28','2025-10-09 21:26:00'),
(110,4,'0aca6b767ada45e343c87330ad36d44835477cd6c6eefb3918ce134eb37ce095fcdca9dbceb4b905ca49e4aeb9a386d0f5d746d3db41a0ea343cc2704cec3c7e','197.157.165.125','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-09 21:24:26','2025-10-09 22:24:26',1,'8a775c49c1301f52d140d36187bf3797fb3eaeab06f475ded6ff2999f67e9fb4','2025-10-09 23:24:26'),
(111,4,'1d84f11387f8bd8f6f95afc8ab616be7ce4fa54243c2330b470c25dbc96ae6a6bccdd867f374f17519e76f8c32ac656be76213e54bd8064d8780a858adf1da73','197.157.165.125','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-09 22:37:22','2025-10-09 23:37:22',1,'477d87b42be899f6ced773a557d9f713aece9cbdfefc7a276363a05286ee34fa','2025-10-10 00:37:22'),
(112,4,'cc5cddb41c07f66ddc5915f557ae5dba7f84686a7332869fac48628d126b6bb7e9770c208c371ab416ebb4bcaed61fe7625b93b6bb8a8a116d81cd6aee8c465a','197.157.155.94','Mozilla/5.0 (Linux; Android 13; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.6312.105 Mobile Safari/537.36','2025-10-10 06:15:19','2025-10-10 07:15:19',1,'fbd5c013d9fd44cd50d690c38cfcc7adbae3e1208e78f64d47934e4abea229e5','2025-10-10 08:15:19'),
(113,4,'216db0fd617b54a6b6f2b7bd3d05cd9e4b32c42a2f01a1ac190649760fc9b839afefbfcbe3f905e6f655fe3e0d148e8f4da658dffdf97e8947ad7e45361a85b8','197.157.155.94','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-10 06:24:55','2025-10-10 07:24:55',1,'d6ee17629bcc06d33dde8ca22d17a27ae51b9f428c6535a4af594c37b64c981a','2025-10-10 08:24:55'),
(114,4,'b02a89e0c1a6abf17b2fc7055d47acf1da545405d58ed1fda9bd2e20e13e51b3abb537f1cebc175446e206f79740915476650e9d590d007d34644d5db6666654','197.157.155.94','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-10 06:53:59','2025-10-10 07:53:59',1,'affe19e62401f7a538eb6b2ba08e909fc5b9c8677b78ed5ca0843e15b6b8c54a','2025-10-10 08:53:59'),
(115,17,'92d9cd26681a20c76fc1336f4c7060f0fdfd01828da94d333b1a61fde26ee5233365f9612b6c9d8dbfe651c54c708019852e659864c3a629ac460d8006605ec9','2401:4900:a06e:7ded:9445:653:2877:4a56','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-10 07:10:41','2025-10-10 08:10:41',1,'9e298ffaf8be23839421099406b82406928bcc065a648a9c5a8ec647c1507163','2025-10-10 09:10:41'),
(116,4,'013f9c5893de28279875111d9ce24148b84238c2be1b9c4f67c5107ffafc5129a9101051be9fed7ef286b5418ac9c9159eefcb47db7a5596465f0c42089ad493','197.157.155.94','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-10 09:15:06','2025-10-10 10:15:06',1,'d9affb15da2cfb14189d834250f8d760f618dae55009c9536dab270cdcb8c285','2025-10-10 11:15:06'),
(117,4,'82c1aa8815ca7dba1d428a930fe4ac9af4f16d8b7f16b11bda26117edece4177def07fda40a2f87061f49c94df3e6ad430dc37ad4e658cd4355a44daee997544','197.157.155.159','Mozilla/5.0 (Linux; Android 13; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.6312.105 Mobile Safari/537.36','2025-10-10 13:59:43','2025-10-10 14:59:43',1,'1238e9cad92e4272bbcd4847038212eec542b35ee44a0373328b022523b48a99','2025-10-10 15:59:43'),
(118,4,'9b3eb35fdb1b2dbd87243f64135bea600758178cb909ac3429530bf981138dcfc9cee520c238f7e0bf7e47f3d7d96373abf8efddc981138e980d504e1bb9aefe','105.178.104.166','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-10 18:04:22','2025-10-10 19:04:22',1,'00e7614a1a26d336b97bc4e5394dd87e2c117a19c5118f1fae824ed93541654a','2025-10-10 20:04:22');
/*!40000 ALTER TABLE `user_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_two_factor_auth`
--

DROP TABLE IF EXISTS `user_two_factor_auth`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_two_factor_auth` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `method` enum('totp','sms','email','backup_codes') NOT NULL,
  `secret_key` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `backup_codes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`backup_codes`)),
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `recovery_codes_used` int(11) NOT NULL DEFAULT 0,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `enabled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_method` (`user_id`,`method`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_method` (`method`),
  KEY `idx_is_enabled` (`is_enabled`),
  CONSTRAINT `fk_user_two_factor_auth_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_two_factor_auth`
--

LOCK TABLES `user_two_factor_auth` WRITE;
/*!40000 ALTER TABLE `user_two_factor_auth` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_two_factor_auth` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_wallets`
--

DROP TABLE IF EXISTS `user_wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_wallets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_deposits` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_spent` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_user_wallets_balance` (`balance`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_wallets`
--

LOCK TABLES `user_wallets` WRITE;
/*!40000 ALTER TABLE `user_wallets` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_wallets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `pass_hash` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'customer',
  `status` enum('active','inactive','pending','suspended','deleted') NOT NULL DEFAULT 'pending',
  `verified_at` timestamp NULL DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferences`)),
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `two_fa_secret` varchar(255) DEFAULT NULL,
  `login_email_alerts` tinyint(1) NOT NULL DEFAULT 1,
  `login_sms_alerts` tinyint(1) NOT NULL DEFAULT 0,
  `new_device_alerts` tinyint(1) NOT NULL DEFAULT 1,
  `suspicious_activity_alerts` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `stripe_customer_id` varchar(255) DEFAULT NULL COMMENT 'Stripe Customer ID for repeat purchases',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_username` (`username`),
  UNIQUE KEY `idx_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_users_role_status` (`role`,`status`),
  KEY `idx_stripe_customer_id` (`stripe_customer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(4,'Joseph','ellyj164@gmail.com',0,'$argon2id$v=19$m=65536,t=4,p=3$Yjg2Y2dNN0wzdFZZOUEuUA$XCK6vnbTtHx4S8EJvZP0qHf3xXNl0UQKNxa9fIcTHWs','xxxx','Mark bb','+250 789 721 783','admin','active',NULL,NULL,NULL,NULL,1,NULL,NULL,1,1,1,1,NULL,NULL,'2025-09-11 15:56:21','2025-10-10 06:47:35',NULL,NULL),
(5,'niyo','niyogushimwaj967@gmail.com',0,'$argon2id$v=19$m=65536,t=4,p=3$RW9vWGRWVHNRY0xrTVpKRg$4NdBl5tNh3vcmVSxIt5ROsXzYLH8z1YFnd8HLkxxZAY','NIYogu','Joseph','+250 785 241 817','customer','deleted',NULL,NULL,NULL,NULL,0,NULL,NULL,1,0,1,1,NULL,NULL,'2025-09-20 20:23:57','2025-10-07 18:59:03',NULL,NULL),
(13,'fezdd','fezamarketgroup@gmail.com',1,'$argon2id$v=19$m=65536,t=4,p=3$OTN0dlF0R0FrNWRMVzRoTQ$9/9gaiwvzRz4dGBb1hoN4hWlyX/S7znA8sssYOcmg+k','ellyj156','joss','+250 785 241 817','seller','deleted','2025-10-07 17:07:14',NULL,NULL,NULL,0,'2025-10-07 17:07:14',NULL,1,0,1,1,NULL,NULL,'2025-10-07 15:04:44','2025-10-07 18:59:11',NULL,NULL),
(14,'josephgd','fezalogistics@gmail.com',0,'$argon2id$v=19$m=65536,t=4,p=3$aEE4ZmJ2amMyczdKZWNabA$dURCb5IggTCmJdI+tXKWhfvYKq7dDcw3hmosQhZ9SXk','jOSEPH','Niyogushimwa','0781845188','customer','deleted',NULL,NULL,NULL,NULL,0,NULL,NULL,1,0,1,1,NULL,NULL,'2025-10-07 16:17:13','2025-10-07 18:58:47',NULL,NULL),
(15,'jumajumaa987@gmail.com','jumajumaa987@gmail.com',0,'$argon2id$v=19$m=65536,t=4,p=3$L2FjQml3RWxDcFVQdnRaVQ$nwSlyOjJKRat/EwWJjX9R17BTGhl06diDZbhGLz35AI','Juma','Jumaa','0712382141','customer','deleted',NULL,NULL,NULL,NULL,0,NULL,NULL,1,0,1,1,NULL,NULL,'2025-10-07 19:40:33','2025-10-09 22:39:10',NULL,NULL),
(16,'Punjab60','amarjit18000@gmail.com',0,'$argon2id$v=19$m=65536,t=4,p=3$Y3dKZWZDU0lTWUVHQmdLSg$WaBEh+WOBoQqST6MIcISfHKnTd3YwAMvIABZztKVsVw','Amarjit','Singh','9266240118','customer','deleted',NULL,NULL,NULL,NULL,0,NULL,NULL,1,0,1,1,NULL,NULL,'2025-10-09 11:23:32','2025-10-09 22:38:58',NULL,NULL),
(17,'Amar60','amarjitfatehgarh05@gmail.com',1,'$argon2id$v=19$m=65536,t=4,p=3$NEtEeG9xY2w0U0lrMnh2TA$qQO1r1F77B8CbpGhSpHNe2dEpH6mLoYKhUhYwuyYCr8','Amarjit','Singh','9266240118','customer','active','2025-10-09 13:41:15',NULL,NULL,NULL,0,'2025-10-09 13:41:15',NULL,1,0,1,1,NULL,NULL,'2025-10-09 11:40:41','2025-10-09 13:41:15',NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_commissions`
--

DROP TABLE IF EXISTS `vendor_commissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_commissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `commission_type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `commission_rate` decimal(8,4) NOT NULL DEFAULT 5.0000,
  `commission_amount` decimal(10,2) DEFAULT NULL,
  `minimum_payout` decimal(10,2) NOT NULL DEFAULT 50.00,
  `payout_schedule` enum('weekly','monthly','quarterly') NOT NULL DEFAULT 'monthly',
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `effective_from` timestamp NOT NULL DEFAULT current_timestamp(),
  `effective_until` timestamp NULL DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_status` (`status`),
  KEY `idx_effective_from` (`effective_from`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `fk_vendor_commissions_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vendor_commissions_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_commissions`
--

LOCK TABLES `vendor_commissions` WRITE;
/*!40000 ALTER TABLE `vendor_commissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_commissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_payouts`
--

DROP TABLE IF EXISTS `vendor_payouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_payouts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `commission_earned` decimal(10,2) NOT NULL,
  `platform_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(10,2) NOT NULL,
  `payout_method` enum('bank_transfer','paypal','stripe','manual') NOT NULL DEFAULT 'bank_transfer',
  `reference_number` varchar(100) NOT NULL,
  `status` enum('pending','processing','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `processed_at` timestamp NULL DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `payment_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_details`)),
  `period_from` date NOT NULL,
  `period_to` date NOT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_reference_number` (`reference_number`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_status` (`status`),
  KEY `idx_processed_at` (`processed_at`),
  KEY `idx_period_from` (`period_from`),
  KEY `idx_period_to` (`period_to`),
  KEY `idx_processed_by` (`processed_by`),
  CONSTRAINT `fk_vendor_payouts_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vendor_payouts_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_payouts`
--

LOCK TABLES `vendor_payouts` WRITE;
/*!40000 ALTER TABLE `vendor_payouts` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_payouts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendors`
--

DROP TABLE IF EXISTS `vendors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `business_name` varchar(100) NOT NULL,
  `business_description` text DEFAULT NULL,
  `business_type` enum('individual','business','corporation') NOT NULL DEFAULT 'individual',
  `tax_id` varchar(50) DEFAULT NULL,
  `business_address` text DEFAULT NULL,
  `business_phone` varchar(20) DEFAULT NULL,
  `business_email` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `banner_url` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','suspended','rejected') NOT NULL DEFAULT 'pending',
  `commission_rate` decimal(5,2) NOT NULL DEFAULT 10.00,
  `payment_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_details`)),
  `business_documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`business_documents`)),
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `name` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_business_name` (`business_name`),
  KEY `fk_vendors_approver` (`approved_by`),
  CONSTRAINT `fk_vendors_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vendors_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendors`
--

LOCK TABLES `vendors` WRITE;
/*!40000 ALTER TABLE `vendors` DISABLE KEYS */;
INSERT INTO `vendors` VALUES
(3,4,'ffffeza','ffffff','individual','','fffffffffffffff',NULL,NULL,NULL,NULL,NULL,NULL,'approved',10.00,NULL,NULL,NULL,NULL,'2025-09-14 20:46:17','2025-10-01 21:13:10','',''),
(4,5,'Joseph store','Businesss managenebt','individual','','BUsiness tools to manage my account',NULL,NULL,NULL,NULL,NULL,NULL,'pending',10.00,NULL,NULL,NULL,NULL,'2025-09-20 22:27:33','2025-09-20 20:31:07','','');
/*!40000 ALTER TABLE `vendors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wallet`
--

DROP TABLE IF EXISTS `wallet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) NOT NULL DEFAULT 'RWF' COMMENT 'Currency code, e.g., RWF, USD',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id_unique_idx` (`user_id`),
  CONSTRAINT `fk_wallet_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallet`
--

LOCK TABLES `wallet` WRITE;
/*!40000 ALTER TABLE `wallet` DISABLE KEYS */;
INSERT INTO `wallet` VALUES
(1,4,0.00,'USD','2025-10-03 20:51:00','2025-10-03 20:51:00');
/*!40000 ALTER TABLE `wallet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wallet_entries`
--

DROP TABLE IF EXISTS `wallet_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallet_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wallet_id` int(11) NOT NULL,
  `entry_type` enum('credit','debit') NOT NULL,
  `transaction_type` enum('sale','commission','payout','refund','adjustment','fee','bonus') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance_before` decimal(15,2) NOT NULL,
  `balance_after` decimal(15,2) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `description` text NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_wallet_id` (`wallet_id`),
  KEY `idx_entry_type` (`entry_type`),
  KEY `idx_transaction_type` (`transaction_type`),
  KEY `idx_reference` (`reference_type`,`reference_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `fk_wallet_entries_creator` (`created_by`),
  CONSTRAINT `fk_wallet_entries_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_wallet_entries_wallet` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallet_entries`
--

LOCK TABLES `wallet_entries` WRITE;
/*!40000 ALTER TABLE `wallet_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `wallet_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wallet_transactions`
--

DROP TABLE IF EXISTS `wallet_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallet_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wallet_id` int(11) NOT NULL,
  `type` enum('credit','debit','system_adjustment','initial') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance_before` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `description` varchar(255) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `wallet_id` (`wallet_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `wallet_transactions_ibfk_1` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wallet_transactions_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallet_transactions`
--

LOCK TABLES `wallet_transactions` WRITE;
/*!40000 ALTER TABLE `wallet_transactions` DISABLE KEYS */;
INSERT INTO `wallet_transactions` VALUES
(1,1,'credit',100.00,0.00,100.00,'pay',4,'2025-10-05 22:50:26'),
(2,1,'credit',500.00,100.00,600.00,'gt',4,'2025-10-06 17:58:56');
/*!40000 ALTER TABLE `wallet_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wallets`
--

DROP TABLE IF EXISTS `wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `wallet_type` enum('vendor','affiliate','customer') NOT NULL DEFAULT 'vendor',
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','suspended') NOT NULL DEFAULT 'active',
  `pending_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `frozen_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_earned` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_withdrawn` decimal(15,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `minimum_payout` decimal(10,2) NOT NULL DEFAULT 50.00,
  `auto_payout_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `auto_payout_threshold` decimal(10,2) NOT NULL DEFAULT 100.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_wallet_type` (`user_id`,`wallet_type`),
  KEY `idx_wallet_type` (`wallet_type`),
  KEY `idx_balance` (`balance`),
  CONSTRAINT `fk_wallets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallets`
--

LOCK TABLES `wallets` WRITE;
/*!40000 ALTER TABLE `wallets` DISABLE KEYS */;
INSERT INTO `wallets` VALUES
(1,5,'vendor',600.00,'active',0.00,0.00,0.00,0.00,'USD',50.00,0,100.00,'2025-10-05 22:50:03','2025-10-06 17:58:56'),
(2,4,'vendor',0.00,'active',0.00,0.00,0.00,0.00,'USD',50.00,0,100.00,'2025-10-05 22:50:11','2025-10-05 22:50:11');
/*!40000 ALTER TABLE `wallets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(2) NOT NULL DEFAULT 'US',
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `operating_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`operating_hours`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_code` (`code`),
  KEY `idx_name` (`name`),
  KEY `idx_manager_id` (`manager_id`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_warehouses_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouses`
--

LOCK TABLES `warehouses` WRITE;
/*!40000 ALTER TABLE `warehouses` DISABLE KEYS */;
INSERT INTO `warehouses` VALUES
(1,'Main Warehouse','MAIN','123 Warehouse St','Los Angeles','CA','90210','US','+1-555-0123','warehouse@example.com',NULL,NULL,1,NULL,'2025-09-14 19:54:26','2025-09-14 19:54:26'),
(2,'East Coast Facility','EAST','456 Shipping Ave','New York','NY','10001','US','+1-555-0124','east@example.com',NULL,NULL,1,NULL,'2025-09-14 19:54:26','2025-09-14 19:54:26');
/*!40000 ALTER TABLE `warehouses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `watchlist`
--

DROP TABLE IF EXISTS `watchlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `watchlist` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_watchlist_user_product` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `watchlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `watchlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `watchlist`
--

LOCK TABLES `watchlist` WRITE;
/*!40000 ALTER TABLE `watchlist` DISABLE KEYS */;
INSERT INTO `watchlist` VALUES
(6,4,5,'2025-10-07 08:16:21');
/*!40000 ALTER TABLE `watchlist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webhook_deliveries`
--

DROP TABLE IF EXISTS `webhook_deliveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhook_deliveries` (
  `id` int(11) NOT NULL,
  `integration_id` int(11) NOT NULL,
  `webhook_url` varchar(500) NOT NULL,
  `event_type` varchar(100) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `response_status` int(11) DEFAULT NULL,
  `response_headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response_headers`)),
  `response_body` longtext DEFAULT NULL,
  `delivery_attempts` int(11) NOT NULL DEFAULT 1,
  `last_attempt` timestamp NOT NULL DEFAULT current_timestamp(),
  `next_attempt` timestamp NULL DEFAULT NULL,
  `status` enum('pending','delivered','failed','abandoned') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_webhook_integration` (`integration_id`),
  KEY `idx_webhook_status` (`status`),
  KEY `idx_webhook_event` (`event_type`),
  KEY `idx_webhook_next_attempt` (`next_attempt`),
  CONSTRAINT `fk_webhook_deliveries_integration` FOREIGN KEY (`integration_id`) REFERENCES `integrations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `webhook_deliveries`
--

LOCK TABLES `webhook_deliveries` WRITE;
/*!40000 ALTER TABLE `webhook_deliveries` DISABLE KEYS */;
/*!40000 ALTER TABLE `webhook_deliveries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webhook_subscriptions`
--

DROP TABLE IF EXISTS `webhook_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhook_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(500) NOT NULL,
  `events` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`events`)),
  `secret` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `retry_count` int(11) NOT NULL DEFAULT 3,
  `timeout` int(11) NOT NULL DEFAULT 30,
  `headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`headers`)),
  `last_triggered_at` timestamp NULL DEFAULT NULL,
  `failure_count` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_last_triggered_at` (`last_triggered_at`),
  CONSTRAINT `fk_webhook_subscriptions_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `webhook_subscriptions`
--

LOCK TABLES `webhook_subscriptions` WRITE;
/*!40000 ALTER TABLE `webhook_subscriptions` DISABLE KEYS */;
INSERT INTO `webhook_subscriptions` VALUES
(1,'https://duns1.fezalogistics.com/','[\"payment.completed\"]','12c9d3d35669aed34578d6c3682eeaa828cf11e5882ad65ae0654785c1697998',1,3,30,NULL,NULL,0,4,'2025-10-05 13:13:41','2025-10-05 13:13:41'),
(2,'https://duns1.fezalogistics.com/','[\"order.cancelled\",\"payment.completed\"]','e65b1169f7141ac3c73edd9e774e1b1d098570d77148d8eed889130e5a7bbc32',1,3,30,NULL,NULL,0,4,'2025-10-05 13:14:35','2025-10-05 13:14:35'),
(3,'https://duns1.fezalogistics.com/','[\"order.cancelled\",\"payment.completed\"]','c210704fd4ac33da8bde58b2c1c72127944d2a82583186d17d14cc3c8744f16a',1,3,30,NULL,NULL,0,4,'2025-10-05 13:15:02','2025-10-05 13:15:02');
/*!40000 ALTER TABLE `webhook_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webhooks`
--

DROP TABLE IF EXISTS `webhooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhooks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `webhook_subscription_id` int(11) DEFAULT NULL,
  `url` varchar(255) NOT NULL,
  `environment` enum('sandbox','live') NOT NULL,
  `events` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`events`)),
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `secret` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id_idx` (`user_id`),
  KEY `fk_webhook_subscription_idx` (`webhook_subscription_id`),
  CONSTRAINT `fk_webhook_subscription` FOREIGN KEY (`webhook_subscription_id`) REFERENCES `api_subscriptions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_webhooks_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `webhooks`
--

LOCK TABLES `webhooks` WRITE;
/*!40000 ALTER TABLE `webhooks` DISABLE KEYS */;
/*!40000 ALTER TABLE `webhooks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_product_wishlist` (`user_id`,`product_id`),
  KEY `idx_user_wishlist` (`user_id`),
  KEY `idx_product_wishlist` (`product_id`),
  KEY `idx_wishlist_created` (`created_at`),
  CONSTRAINT `fk_wishlist_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlist`
--

LOCK TABLES `wishlist` WRITE;
/*!40000 ALTER TABLE `wishlist` DISABLE KEYS */;
/*!40000 ALTER TABLE `wishlist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlists`
--

DROP TABLE IF EXISTS `wishlists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wishlists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `priority` tinyint(1) NOT NULL DEFAULT 3,
  `notes` text DEFAULT NULL,
  `price_alert` tinyint(1) NOT NULL DEFAULT 0,
  `alert_price` decimal(10,2) DEFAULT NULL,
  `notify_on_restock` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_product` (`user_id`,`product_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_priority` (`priority`),
  KEY `idx_price_alert` (`price_alert`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_wishlists_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wishlists_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlists`
--

LOCK TABLES `wishlists` WRITE;
/*!40000 ALTER TABLE `wishlists` DISABLE KEYS */;
INSERT INTO `wishlists` VALUES
(13,4,5,3,NULL,0,NULL,0,'2025-10-06 21:48:40','2025-10-06 21:48:40');
/*!40000 ALTER TABLE `wishlists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'ecommerce_platform'
--

--
-- Dumping routines for database 'ecommerce_platform'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-10 20:14:39
