-- Additional schema updates for homepage enhancements
-- This file adds the banners table if needed (uses existing homepage_banners structure)

-- Ensure homepage_banners table exists with all required fields
CREATE TABLE IF NOT EXISTS `banners` (
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
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ensure cart_items table exists for add to cart functionality
CREATE TABLE IF NOT EXISTS `cart_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_product` (`user_id`, `product_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample banner data for testing (will only insert if no banners exist)
INSERT IGNORE INTO `banners` (`id`, `title`, `subtitle`, `description`, `image_url`, `link_url`, `button_text`, `position`, `sort_order`, `status`, `created_by`) VALUES
(1, 'The fall shoe edit', 'Latest footwear trends', 'Discover the perfect shoes for the season with our curated collection', 'https://picsum.photos/1200/400?random=shoes', '/category/shoes', 'Shop now', 'top', 1, 'active', 1),
(2, 'FezaMarket Cash Back', '5% cash back on all purchases', 'Members earn incredible rewards with every purchase', 'https://picsum.photos/1200/400?random=cashback', '/membership', 'Learn how', 'top', 2, 'active', 1),
(3, 'Leaf blowers, mowers & more', 'Garden equipment sale', 'Get your yard ready with professional grade tools', 'https://picsum.photos/1200/400?random=garden', '/category/garden', 'Shop now', 'top', 3, 'active', 1);