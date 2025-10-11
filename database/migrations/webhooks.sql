-- Webhook Subscriptions and Deliveries Tables
-- For developer API webhook management
-- Run this migration if webhook tables don't exist

-- Webhook Subscriptions Table
CREATE TABLE IF NOT EXISTS `webhook_subscriptions` (
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
  KEY `idx_webhook_active` (`is_active`),
  KEY `idx_webhook_created_by` (`created_by`),
  KEY `fk_webhook_subscriptions_creator` (`created_by`),
  CONSTRAINT `fk_webhook_subscriptions_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Webhook Deliveries Table
CREATE TABLE IF NOT EXISTS `webhook_deliveries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webhook_id` int(11) NOT NULL,
  `integration_id` int(11) DEFAULT NULL,
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
  `success` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_webhook_integration` (`integration_id`),
  KEY `idx_webhook_status` (`status`),
  KEY `idx_webhook_event` (`event_type`),
  KEY `idx_webhook_next_attempt` (`next_attempt`),
  KEY `idx_webhook_id` (`webhook_id`),
  CONSTRAINT `fk_webhook_deliveries_webhook` FOREIGN KEY (`webhook_id`) REFERENCES `webhook_subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Indexes for better query performance
CREATE INDEX IF NOT EXISTS idx_webhook_deliveries_webhook_id ON webhook_deliveries(webhook_id);
CREATE INDEX IF NOT EXISTS idx_webhook_deliveries_created_at ON webhook_deliveries(created_at);
CREATE INDEX IF NOT EXISTS idx_webhook_subscriptions_user ON webhook_subscriptions(created_by, is_active);
