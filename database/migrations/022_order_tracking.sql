-- Order Tracking Enhancement
-- Add tracking fields to orders table

ALTER TABLE `orders` 
ADD COLUMN IF NOT EXISTS `tracking_number` varchar(255) DEFAULT NULL AFTER `payment_method`,
ADD COLUMN IF NOT EXISTS `carrier` varchar(100) DEFAULT NULL AFTER `tracking_number`,
ADD COLUMN IF NOT EXISTS `tracking_url` varchar(500) DEFAULT NULL AFTER `carrier`,
ADD COLUMN IF NOT EXISTS `shipped_at` timestamp NULL DEFAULT NULL AFTER `tracking_url`;

-- Add index for tracking number
ALTER TABLE `orders` ADD INDEX IF NOT EXISTS `idx_tracking_number` (`tracking_number`);

-- Create order tracking updates table
CREATE TABLE IF NOT EXISTS `order_tracking_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `status` varchar(100) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
