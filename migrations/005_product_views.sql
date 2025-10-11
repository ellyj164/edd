-- Create product_views table for tracking product views and analytics
-- Logs each product view with timestamp for analytics

CREATE TABLE IF NOT EXISTS `product_views` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'NULL for anonymous users',
  `session_id` varchar(100) DEFAULT NULL COMMENT 'Session ID for tracking anonymous users',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `referrer` varchar(500) DEFAULT NULL COMMENT 'Where the user came from',
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_viewed_at` (`viewed_at`),
  KEY `idx_product_views_24h` (`product_id`, `viewed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add index for faster 24-hour queries
CREATE INDEX IF NOT EXISTS idx_product_views_recent ON product_views(product_id, viewed_at);
