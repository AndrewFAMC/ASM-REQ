-- Create activity_log table for tracking asset and user activities
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NULL,
    action VARCHAR(50) NOT NULL,
    description TEXT,
    performed_by VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE
);

-- Add index for better performance
CREATE INDEX idx_activity_log_asset_id ON activity_log(asset_id);
CREATE INDEX idx_activity_log_created_at ON activity_log(created_at);
