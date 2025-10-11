-- API Logs Table
-- For tracking developer API requests and debugging
-- Run this migration if api_logs table doesn't exist

CREATE TABLE IF NOT EXISTS `api_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `api_key_id` int(11) DEFAULT NULL,
  `endpoint` varchar(255) NOT NULL,
  `method` varchar(10) NOT NULL,
  `request_headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`request_headers`)),
  `request_body` longtext DEFAULT NULL,
  `response_status` int(11) NOT NULL,
  `response_headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response_headers`)),
  `response_body` longtext DEFAULT NULL,
  `response_time` int(11) NOT NULL COMMENT 'Response time in milliseconds',
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_api_logs_key_id` (`api_key_id`),
  KEY `idx_api_logs_endpoint` (`endpoint`),
  KEY `idx_api_logs_status` (`response_status`),
  KEY `idx_api_logs_created_at` (`created_at`),
  CONSTRAINT `fk_api_logs_key` FOREIGN KEY (`api_key_id`) REFERENCES `api_keys` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add index for performance
CREATE INDEX IF NOT EXISTS idx_api_logs_key_date ON api_logs(api_key_id, created_at);
CREATE INDEX IF NOT EXISTS idx_api_logs_ip ON api_logs(ip_address);
