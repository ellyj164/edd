-- Chat System Tables Migration
-- Created for Live Chat feature and Feza AI Assistant

-- Chats table to store chat sessions
CREATE TABLE IF NOT EXISTS chats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    name VARCHAR(100) NULL,
    email VARCHAR(100) NULL,
    status ENUM('active', 'closed', 'archived') DEFAULT 'active',
    type ENUM('support', 'ai', 'sales') DEFAULT 'support',
    assigned_agent_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_agent_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat messages table
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chat_id INT NOT NULL,
    sender ENUM('user', 'agent', 'ai', 'system') NOT NULL,
    sender_id INT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_chat_id (chat_id),
    INDEX idx_sender (sender),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat metadata for storing additional info (optional)
CREATE TABLE IF NOT EXISTS chat_metadata (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chat_id INT NOT NULL,
    meta_key VARCHAR(100) NOT NULL,
    meta_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_chat_id (chat_id),
    INDEX idx_meta_key (meta_key),
    FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI interactions log for Feza AI
CREATE TABLE IF NOT EXISTS ai_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    session_id VARCHAR(100) NOT NULL,
    chat_id INT NULL,
    prompt TEXT NOT NULL,
    response TEXT,
    provider VARCHAR(50) DEFAULT 'openai',
    model VARCHAR(50) DEFAULT 'gpt-3.5-turbo',
    tokens_used INT DEFAULT 0,
    response_time_ms INT DEFAULT 0,
    error TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_chat_id (chat_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agent presence/status table
CREATE TABLE IF NOT EXISTS agent_presence (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT NOT NULL UNIQUE,
    status ENUM('online', 'away', 'busy', 'offline') DEFAULT 'offline',
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    current_chats INT DEFAULT 0,
    max_chats INT DEFAULT 5,
    INDEX idx_status (status),
    INDEX idx_last_seen (last_seen),
    FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
