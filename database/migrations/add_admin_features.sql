-- Add Banner Management Table for Homepage Content
-- This table will store promotional banners that admins can manage

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
  CONSTRAINT `fk_homepage_banners_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add Platform Notifications Table for System-wide Announcements
-- This extends the existing notifications for platform-wide messages

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Track which users have seen/dismissed platform notifications
CREATE TABLE `platform_notification_reads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `clicked_at` timestamp NULL DEFAULT NULL,
  `dismissed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_notification_user` (`notification_id`, `user_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_read_at` (`read_at`),
  CONSTRAINT `fk_platform_notification_reads_notification` FOREIGN KEY (`notification_id`) REFERENCES `platform_notifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_platform_notification_reads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;