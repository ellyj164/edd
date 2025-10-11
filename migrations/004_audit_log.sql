-- Create audit_log table for tracking administrative actions
-- This table logs all admin actions for accountability and security

CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `entity_type` varchar(50) NOT NULL COMMENT 'Type of entity (user, product, order, etc.)',
  `entity_id` bigint(20) NOT NULL COMMENT 'ID of the entity being acted upon',
  `action` varchar(50) NOT NULL COMMENT 'Action performed (create, update, delete, etc.)',
  `data` text DEFAULT NULL COMMENT 'JSON data with additional details',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address of the user',
  `user_agent` varchar(500) DEFAULT NULL COMMENT 'Browser user agent string',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_entity_type` (`entity_type`),
  KEY `idx_entity_id` (`entity_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_entity_lookup` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
