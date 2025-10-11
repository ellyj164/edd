-- Customer Support Role and Permissions
-- Create a Customer Support role with limited permissions (live chat only)

-- Insert Customer Support role if it doesn't exist
INSERT INTO `roles` (`name`, `slug`, `description`, `level`, `is_system`, `created_at`, `updated_at`) 
VALUES ('Customer Support', 'customer_support', 'Customer support agent with live chat access only', 30, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE name = name;

-- Get the role ID (assuming it will be auto-incremented)
SET @support_role_id = LAST_INSERT_ID();

-- If role already exists, get its ID
SELECT @support_role_id := id FROM roles WHERE slug = 'customer_support' LIMIT 1;

-- Add live chat permissions for Customer Support role
INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`)
VALUES 
('View Live Chat', 'live_chat.view', 'chat', 'View live chat sessions', NOW(), NOW()),
('Reply to Chat', 'live_chat.reply', 'chat', 'Reply to customer chat messages', NOW(), NOW()),
('Assign Chat', 'live_chat.assign', 'chat', 'Assign chat to agents', NOW(), NOW()),
('Close Chat', 'live_chat.close', 'chat', 'Close chat sessions', NOW(), NOW());

-- Link permissions to Customer Support role
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT @support_role_id, p.id, NOW()
FROM permissions p
WHERE p.module = 'chat'
ON DUPLICATE KEY UPDATE role_id = role_id;
