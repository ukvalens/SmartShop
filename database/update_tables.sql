-- Update existing tables with new fields
-- Add new columns to users table if they don't exist

ALTER TABLE users 
ADD COLUMN IF NOT EXISTS language_preference VARCHAR(5) DEFAULT 'en',
ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS reset_token_expires TIMESTAMP NULL;

-- Update system settings with new language options
INSERT INTO system_settings (setting_key, setting_value) VALUES
('default_language', 'en'),
('supported_languages', 'en,rw')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);