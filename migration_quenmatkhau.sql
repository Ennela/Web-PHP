-- Migration: Thêm cột reset_token cho chức năng Quên mật khẩu
-- Chạy trên database giaythethao2

SET @db = DATABASE();

-- Thêm cột reset_token (token hex 64 ký tự)
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'tbl_tkkhachhang' AND COLUMN_NAME = 'reset_token') = 0,
    'ALTER TABLE `tbl_tkkhachhang` ADD COLUMN `reset_token` VARCHAR(64) DEFAULT NULL',
    'SELECT 1'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Thêm cột reset_token_expiry (Unix timestamp, hết hạn sau 30 phút)
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'tbl_tkkhachhang' AND COLUMN_NAME = 'reset_token_expiry') = 0,
    'ALTER TABLE `tbl_tkkhachhang` ADD COLUMN `reset_token_expiry` INT(11) DEFAULT NULL',
    'SELECT 1'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
