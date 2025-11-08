-- Activity Logs Table Migration
-- Creates a table to track all system activities for audit and monitoring

CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `asset_id` INT(11) UNSIGNED DEFAULT NULL,
  `action_type` VARCHAR(50) NOT NULL,
  `description` TEXT NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `metadata` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_asset_id` (`asset_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_activity_logs_asset` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for better query performance
CREATE INDEX idx_user_action ON activity_logs(user_id, action_type);
CREATE INDEX idx_asset_action ON activity_logs(asset_id, action_type);

-- Insert sample activities for demonstration (optional)
-- INSERT INTO activity_logs (user_id, asset_id, action_type, description, ip_address)
-- VALUES
-- (1, 1, 'ASSET_CREATED', 'Created new asset: Laptop Dell XPS', '127.0.0.1'),
-- (2, 1, 'ASSET_REQUESTED', 'Requested asset: Laptop Dell XPS', '127.0.0.1'),
-- (1, 1, 'REQUEST_APPROVED', 'Approved asset request', '127.0.0.1');
