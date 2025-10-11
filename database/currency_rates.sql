-- Currency Rates Table
-- Stores daily exchange rates for automatic currency conversion

CREATE TABLE IF NOT EXISTS `currency_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `currency_code` varchar(3) NOT NULL,
  `currency_name` varchar(50) NOT NULL,
  `currency_symbol` varchar(10) NOT NULL,
  `rate_to_usd` decimal(18,6) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `currency_code` (`currency_code`),
  KEY `last_updated` (`last_updated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default currency rates (base: 1 USD)
INSERT INTO `currency_rates` (`currency_code`, `currency_name`, `currency_symbol`, `rate_to_usd`) VALUES
('USD', 'US Dollar', '$', 1.000000),
('EUR', 'Euro', '€', 1.000000),
('RWF', 'Rwandan Franc', 'FRw', 1.000000)
ON DUPLICATE KEY UPDATE 
  `currency_name` = VALUES(`currency_name`),
  `currency_symbol` = VALUES(`currency_symbol`);
