<?php
/**
 * Migration: Create live_chat_messages table
 * 
 * This table stores live chat conversations between users and admin/support
 */

return [
    'up' => "
        CREATE TABLE IF NOT EXISTS live_chat_messages (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            conversation_id VARCHAR(64) NOT NULL,
            sender_id INT NULL,
            receiver_id INT NULL,
            sender_role ENUM('user','admin','seller','system') NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_conversation_id (conversation_id),
            INDEX idx_sender_id (sender_id),
            INDEX idx_receiver_id (receiver_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'down' => "
        DROP TABLE IF EXISTS live_chat_messages;
    "
];
