-- Enhanced Notifications System
-- E-commerce platform notifications for orders, shipping, promotions, etc.

-- Notifications table (if not exists)
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'general',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `action_url` varchar(255) DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `type` (`type`),
  KEY `read_at` (`read_at`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Notification templates for e-commerce events
CREATE TABLE IF NOT EXISTS `notification_templates` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default e-commerce notification templates
INSERT INTO `notification_templates` (`type`, `name`, `subject`, `body_template`, `variables`, `enabled`) VALUES
('order_placed', 'Order Confirmation', 'Order #{order_number} Confirmed', 'Thank you for your order! Your order #{order_number} has been received and is being processed. Total: {total_amount}. Track your order at {order_url}', '["order_number","total_amount","order_url","customer_name"]', 1),
('order_shipped', 'Order Shipped', 'Your Order #{order_number} Has Shipped!', 'Great news! Your order #{order_number} has been shipped. Tracking number: {tracking_number}. Expected delivery: {estimated_delivery}. Track at {tracking_url}', '["order_number","tracking_number","estimated_delivery","tracking_url"]', 1),
('order_delivered', 'Order Delivered', 'Order #{order_number} Delivered', 'Your order #{order_number} has been delivered! We hope you enjoy your purchase. Leave a review at {review_url}', '["order_number","review_url","customer_name"]', 1),
('order_cancelled', 'Order Cancelled', 'Order #{order_number} Cancelled', 'Your order #{order_number} has been cancelled. Refund will be processed within 5-7 business days. Contact support if you have questions.', '["order_number","refund_amount","customer_name"]', 1),
('payment_received', 'Payment Confirmed', 'Payment Received for Order #{order_number}', 'We have received your payment of {amount} for order #{order_number}. Thank you!', '["order_number","amount","payment_method"]', 1),
('payment_failed', 'Payment Failed', 'Payment Failed for Order #{order_number}', 'Payment for order #{order_number} could not be processed. Please update your payment method at {payment_url}', '["order_number","amount","payment_url"]', 1),
('refund_issued', 'Refund Processed', 'Refund for Order #{order_number}', 'A refund of {refund_amount} has been issued for order #{order_number}. It will appear in your account within 5-7 business days.', '["order_number","refund_amount"]', 1),
('item_back_in_stock', 'Item Back in Stock', '{product_name} is Back in Stock!', 'Good news! {product_name} is now back in stock. Get it before it sells out again! Shop now: {product_url}', '["product_name","product_url","price"]', 1),
('price_drop', 'Price Drop Alert', 'Price Drop: {product_name}', 'The price of {product_name} has dropped from {old_price} to {new_price}! Save {discount}% now. Shop: {product_url}', '["product_name","old_price","new_price","discount","product_url"]', 1),
('wishlist_sale', 'Wishlist Item on Sale', 'Item in Your Wishlist is on Sale!', '{product_name} from your wishlist is now on sale! Get it for {sale_price} (was {original_price}). Shop now: {product_url}', '["product_name","sale_price","original_price","product_url"]', 1),
('abandoned_cart', 'Cart Reminder', 'You Left Items in Your Cart', 'Don\'t forget! You have {item_count} items waiting in your cart. Complete your purchase now: {cart_url}', '["item_count","cart_url","total_amount"]', 1),
('welcome', 'Welcome', 'Welcome to FezaMarket!', 'Welcome {customer_name}! Thank you for joining FezaMarket. Start shopping now and enjoy exclusive deals!', '["customer_name"]', 1),
('account_verified', 'Email Verified', 'Email Verified Successfully', 'Your email has been verified! You can now enjoy full access to your FezaMarket account.', '["customer_name"]', 1),
('password_changed', 'Password Changed', 'Your Password Was Changed', 'Your password has been changed successfully. If you did not make this change, please contact support immediately.', '["customer_name"]', 1),
('order_review_request', 'Review Request', 'How Was Your Order #{order_number}?', 'We hope you enjoyed your recent purchase! Please take a moment to review your order #{order_number}. Leave a review: {review_url}', '["order_number","review_url","product_name"]', 1),
('promotion', 'Special Promotion', '{promotion_title}', '{promotion_message}. Shop now: {promotion_url}', '["promotion_title","promotion_message","promotion_url","discount_code"]', 1);

-- Notification preferences (user can control what they receive)
CREATE TABLE IF NOT EXISTS `notification_preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `email_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `push_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `sms_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_notification` (`user_id`, `notification_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
