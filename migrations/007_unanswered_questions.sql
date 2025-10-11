-- Unanswered Questions Table for FEZA AI Learning
-- Stores questions that the AI couldn't answer for future improvement

CREATE TABLE IF NOT EXISTS unanswered_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question TEXT NOT NULL,
    user_id INT NULL,
    session_id VARCHAR(100) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    resolved BOOLEAN DEFAULT FALSE,
    resolved_at DATETIME NULL,
    resolved_answer TEXT NULL,
    frequency INT DEFAULT 1,
    INDEX idx_created_at (created_at),
    INDEX idx_resolved (resolved),
    INDEX idx_user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create a trigger to increment frequency for duplicate questions
DELIMITER $$

CREATE TRIGGER IF NOT EXISTS update_unanswered_frequency
BEFORE INSERT ON unanswered_questions
FOR EACH ROW
BEGIN
    DECLARE existing_id INT;
    DECLARE existing_freq INT;
    
    -- Check if similar question exists (not resolved)
    SELECT id, frequency INTO existing_id, existing_freq
    FROM unanswered_questions
    WHERE question = NEW.question AND resolved = FALSE
    LIMIT 1;
    
    -- If exists, update frequency instead of inserting
    IF existing_id IS NOT NULL THEN
        UPDATE unanswered_questions
        SET frequency = frequency + 1,
            created_at = CURRENT_TIMESTAMP
        WHERE id = existing_id;
        
        -- Prevent the insert
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Question already exists, frequency updated';
    END IF;
END$$

DELIMITER ;
