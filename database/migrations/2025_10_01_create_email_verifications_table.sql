-- Migration: Create email_verifications table for user registration
-- Target DB: hcc_asset_management

CREATE TABLE IF NOT EXISTS `email_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `verification_code` varchar(10) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: Add comment
ALTER TABLE `email_verifications` COMMENT = 'Stores email verification codes for user registration';
