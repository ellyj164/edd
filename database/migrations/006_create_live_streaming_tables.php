<?php
/**
 * Migration: Create live streaming enhancement tables
 * 
 * This migration creates tables for:
 * - saved_streams: Store saved stream videos
 * - stream_interactions: Track likes, dislikes, comments
 * - stream_orders: Track purchases made during streams
 */

return [
    'up' => "
        -- Table for saved stream videos
        CREATE TABLE IF NOT EXISTS saved_streams (
            id INT PRIMARY KEY AUTO_INCREMENT,
            stream_id INT NOT NULL,
            vendor_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            video_url VARCHAR(500) NOT NULL,
            thumbnail_url VARCHAR(255),
            duration INT NOT NULL COMMENT 'Duration in seconds',
            viewer_count INT NOT NULL DEFAULT 0,
            total_revenue DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            streamed_at TIMESTAMP NOT NULL,
            saved_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (stream_id) REFERENCES live_streams(id) ON DELETE CASCADE,
            FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
            INDEX idx_vendor_id (vendor_id),
            INDEX idx_streamed_at (streamed_at),
            INDEX idx_saved_at (saved_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        
        -- Table for stream interactions (likes, dislikes, comments)
        CREATE TABLE IF NOT EXISTS stream_interactions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            stream_id INT NOT NULL,
            user_id INT,
            interaction_type ENUM('like', 'dislike', 'comment') NOT NULL,
            comment_text TEXT,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (stream_id) REFERENCES live_streams(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_stream_user (stream_id, user_id),
            INDEX idx_stream_type (stream_id, interaction_type),
            INDEX idx_created_at (created_at),
            UNIQUE KEY unique_user_like_dislike (stream_id, user_id, interaction_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        
        -- Table for tracking orders placed during streams
        CREATE TABLE IF NOT EXISTS stream_orders (
            id INT PRIMARY KEY AUTO_INCREMENT,
            stream_id INT NOT NULL,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            vendor_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (stream_id) REFERENCES live_streams(id) ON DELETE CASCADE,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
            INDEX idx_stream_id (stream_id),
            INDEX idx_vendor_id (vendor_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ",
    'down' => "
        DROP TABLE IF EXISTS stream_orders;
        DROP TABLE IF EXISTS stream_interactions;
        DROP TABLE IF EXISTS saved_streams;
    "
];
