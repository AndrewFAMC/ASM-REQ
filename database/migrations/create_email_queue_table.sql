-- Email Queue Table Migration
-- This table stores emails to be sent asynchronously

CREATE TABLE IF NOT EXISTS email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    body_html TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    error_message TEXT NULL,
    priority ENUM('low', 'normal', 'high') DEFAULT 'normal',

    -- Related data for tracking
    related_type VARCHAR(50) NULL COMMENT 'e.g., request, asset, user',
    related_id INT NULL,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    next_retry_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,

    -- Indexes for performance
    INDEX idx_status (status),
    INDEX idx_next_retry (next_retry_at),
    INDEX idx_created (created_at),
    INDEX idx_related (related_type, related_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
