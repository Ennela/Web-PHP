-- ============================================================
-- CHECK & FIX ALL — Chạy 1 lần trên Railway MySQL
-- Tự động thêm cột/bảng còn thiếu, bỏ qua nếu đã có
-- ============================================================

USE giaythethao2;

-- ============================================================
-- 1. KIỂM TRA BẢNG `tbl_tkkhachhang` — thêm cột còn thiếu
-- ============================================================

-- email
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_tkkhachhang' AND COLUMN_NAME = 'email') = 0,
    'ALTER TABLE `tbl_tkkhachhang` ADD COLUMN `email` VARCHAR(255) DEFAULT NULL',
    'SELECT "email: OK"'));
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- sdt
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_tkkhachhang' AND COLUMN_NAME = 'sdt') = 0,
    'ALTER TABLE `tbl_tkkhachhang` ADD COLUMN `sdt` VARCHAR(15) DEFAULT NULL',
    'SELECT "sdt: OK"'));
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ngaysinh
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_tkkhachhang' AND COLUMN_NAME = 'ngaysinh') = 0,
    'ALTER TABLE `tbl_tkkhachhang` ADD COLUMN `ngaysinh` DATE DEFAULT NULL',
    'SELECT "ngaysinh: OK"'));
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- gioitinh
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_tkkhachhang' AND COLUMN_NAME = 'gioitinh') = 0,
    'ALTER TABLE `tbl_tkkhachhang` ADD COLUMN `gioitinh` ENUM(''Nam'',''Nữ'',''Khác'') DEFAULT NULL',
    'SELECT "gioitinh: OK"'));
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- avatar
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_tkkhachhang' AND COLUMN_NAME = 'avatar') = 0,
    'ALTER TABLE `tbl_tkkhachhang` ADD COLUMN `avatar` VARCHAR(255) DEFAULT NULL',
    'SELECT "avatar: OK"'));
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- diachi
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_tkkhachhang' AND COLUMN_NAME = 'diachi') = 0,
    'ALTER TABLE `tbl_tkkhachhang` ADD COLUMN `diachi` VARCHAR(255) DEFAULT NULL',
    'SELECT "diachi: OK"'));
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- last_updated
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_tkkhachhang' AND COLUMN_NAME = 'last_updated') = 0,
    'ALTER TABLE `tbl_tkkhachhang` ADD COLUMN `last_updated` INT(11) DEFAULT NULL',
    'SELECT "last_updated: OK"'));
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- reset_token
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_tkkhachhang' AND COLUMN_NAME = 'reset_token') = 0,
    'ALTER TABLE `tbl_tkkhachhang` ADD COLUMN `reset_token` VARCHAR(64) DEFAULT NULL',
    'SELECT "reset_token: OK"'));
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- reset_token_expiry
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_tkkhachhang' AND COLUMN_NAME = 'reset_token_expiry') = 0,
    'ALTER TABLE `tbl_tkkhachhang` ADD COLUMN `reset_token_expiry` INT(11) DEFAULT NULL',
    'SELECT "reset_token_expiry: OK"'));
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 2. KIỂM TRA BẢNG `oder` — thêm cột còn thiếu
-- ============================================================

-- token
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oder' AND COLUMN_NAME = 'token') = 0,
    'ALTER TABLE `oder` ADD COLUMN `token` VARCHAR(64) DEFAULT NULL',
    'SELECT "oder.token: OK"'));
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- email
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oder' AND COLUMN_NAME = 'email') = 0,
    'ALTER TABLE `oder` ADD COLUMN `email` VARCHAR(255) DEFAULT NULL',
    'SELECT "oder.email: OK"'));
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- payment_status
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oder' AND COLUMN_NAME = 'payment_status') = 0,
    'ALTER TABLE `oder` ADD COLUMN `payment_status` VARCHAR(20) DEFAULT ''UNPAID''',
    'SELECT "oder.payment_status: OK"'));
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- order_code
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oder' AND COLUMN_NAME = 'order_code') = 0,
    'ALTER TABLE `oder` ADD COLUMN `order_code` VARCHAR(20) DEFAULT NULL',
    'SELECT "oder.order_code: OK"'));
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 3. TẠO BẢNG CÒN THIẾU
-- ============================================================

-- tbl_diachi (sổ địa chỉ)
CREATE TABLE IF NOT EXISTS `tbl_diachi` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `makh` INT(11) NOT NULL,
    `hoten` VARCHAR(255) NOT NULL,
    `sdt` VARCHAR(15) NOT NULL,
    `tinh` VARCHAR(100) DEFAULT NULL,
    `quan_huyen` VARCHAR(100) DEFAULT NULL,
    `phuong_xa` VARCHAR(100) DEFAULT NULL,
    `diachi_cuthe` VARCHAR(255) NOT NULL,
    `macdinh` TINYINT(1) DEFAULT 0,
    `ngaytao` INT(11) DEFAULT NULL,
    `ngaycapnhat` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `fk_diachi_kh` (`makh`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- tbl_sizegiay
CREATE TABLE IF NOT EXISTS `tbl_sizegiay` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `makh` INT(11) NOT NULL,
    `he_size` ENUM('EU','US','CM') DEFAULT 'EU',
    `size_value` VARCHAR(10) NOT NULL,
    `ghichu` VARCHAR(255) DEFAULT NULL,
    `ngaytao` INT(11) DEFAULT NULL,
    `ngaycapnhat` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `fk_size_kh` (`makh`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- tbl_tonkho
CREATE TABLE IF NOT EXISTS `tbl_tonkho` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `masp` INT(11) NOT NULL,
    `size` INT(11) NOT NULL,
    `soluong` INT(11) NOT NULL DEFAULT 0,
    `ngaytao` INT(11) DEFAULT NULL,
    `ngaycapnhat` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_masp_size` (`masp`, `size`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- tbl_email_blacklist
CREATE TABLE IF NOT EXISTS `tbl_email_blacklist` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `reason` VARCHAR(50) DEFAULT 'manual',
    `blocked_at` INT(11) DEFAULT NULL,
    `active` TINYINT(1) DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- tbl_email_logs
CREATE TABLE IF NOT EXISTS `tbl_email_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `message_id` VARCHAR(100) DEFAULT NULL,
    `to_email` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(500) DEFAULT NULL,
    `mail_type` VARCHAR(20) DEFAULT 'transactional',
    `status` VARCHAR(20) DEFAULT 'sent',
    `error` TEXT DEFAULT NULL,
    `sent_at` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_message_id` (`message_id`),
    KEY `idx_to_email` (`to_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 4. SEED dữ liệu tồn kho (nếu bảng trống)
-- ============================================================

INSERT IGNORE INTO `tbl_tonkho` (`masp`, `size`, `soluong`, `ngaytao`, `ngaycapnhat`)
SELECT sp.masp, s.size_val, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `tbl_qlsanpham` sp
CROSS JOIN (
    SELECT 36 AS size_val UNION ALL SELECT 37 UNION ALL SELECT 38 UNION ALL SELECT 39
    UNION ALL SELECT 40 UNION ALL SELECT 41 UNION ALL SELECT 42 UNION ALL SELECT 43 UNION ALL SELECT 44
) s;

-- ============================================================
-- 5. KIỂM TRA KẾT QUẢ
-- ============================================================

SELECT '=== KẾT QUẢ KIỂM TRA ===' AS '';

-- Đếm cột trong tbl_tkkhachhang
SELECT CONCAT('tbl_tkkhachhang: ', COUNT(*), ' cột') AS `Bảng`
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_tkkhachhang';

-- Đếm cột trong oder
SELECT CONCAT('oder: ', COUNT(*), ' cột') AS `Bảng`
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oder';

-- Liệt kê tất cả bảng
SELECT TABLE_NAME AS `Tất cả bảng`, TABLE_ROWS AS `Số dòng (ước tính)`
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;

SELECT '=== HOÀN TẤT ===' AS '';
