-- ISP360 admin_users extended columns
-- Adds email, mobile, last_login_at, last_login_ip (idempotent via information_schema check)

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_users' AND COLUMN_NAME = 'email');
SET @sql = IF(@col = 0, 'ALTER TABLE admin_users ADD COLUMN email VARCHAR(190) NULL AFTER full_name', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_users' AND COLUMN_NAME = 'mobile');
SET @sql = IF(@col = 0, 'ALTER TABLE admin_users ADD COLUMN mobile VARCHAR(30) NULL AFTER email', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_users' AND COLUMN_NAME = 'last_login_at');
SET @sql = IF(@col = 0, 'ALTER TABLE admin_users ADD COLUMN last_login_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_users' AND COLUMN_NAME = 'last_login_ip');
SET @sql = IF(@col = 0, 'ALTER TABLE admin_users ADD COLUMN last_login_ip VARCHAR(45) NULL DEFAULT NULL AFTER last_login_at', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
