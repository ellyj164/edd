-- Migration: Live Stream Scheduling
-- Created: 2025
-- Description: Creates table for scheduling future live streaming events

CREATE TABLE IF NOT EXISTS `scheduled_streams` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
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
  KEY `idx_vendor_status` (`vendor_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add foreign key constraint
ALTER TABLE `scheduled_streams`
  ADD CONSTRAINT `fk_ss_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

-- Create table for stream followers/subscribers
CREATE TABLE IF NOT EXISTS `stream_followers` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `notify_on_live` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_vendor` (`user_id`, `vendor_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_vendor_id` (`vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add foreign key constraints
ALTER TABLE `stream_followers`
  ADD CONSTRAINT `fk_sf_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sf_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;
