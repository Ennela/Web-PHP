-- ============================================================
-- RAILWAY MIGRATION V2 (compatible with MySQL < 8.0.31)
-- Dùng INFORMATION_SCHEMA để kiểm tra trước khi ALTER
-- ============================================================

/*!40014 SET FOREIGN_KEY_CHECKS=0 */;

-- ============================================================
-- 1. Thêm cột còn thiếu vào bảng `oder`
-- ============================================================

-- token
SET @db = DATABASE();
SET @s = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'oder' AND COLUMN_NAME = 'token') = 0,
    'ALTER TABLE `oder` ADD COLUMN `token` varchar(64) DEFAULT NULL AFTER `order_code`',
    'SELECT 1'
);
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- email
SET @s = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'oder' AND COLUMN_NAME = 'email') = 0,
    'ALTER TABLE `oder` ADD COLUMN `email` varchar(255) DEFAULT NULL AFTER `sdt`',
    'SELECT 1'
);
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- payment_status
SET @s = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'oder' AND COLUMN_NAME = 'payment_status') = 0,
    "ALTER TABLE `oder` ADD COLUMN `payment_status` varchar(20) DEFAULT 'UNPAID' AFTER `status`",
    'SELECT 1'
);
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 2. Thêm cột còn thiếu vào bảng `tbl_tkkhachhang`
-- ============================================================

-- email
SET @s = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'tbl_tkkhachhang' AND COLUMN_NAME = 'email') = 0,
    'ALTER TABLE `tbl_tkkhachhang` ADD COLUMN `email` varchar(255) DEFAULT NULL AFTER `ngaytao`',
    'SELECT 1'
);
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- sdt
SET @s = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'tbl_tkkhachhang' AND COLUMN_NAME = 'sdt') = 0,
    'ALTER TABLE `tbl_tkkhachhang` ADD COLUMN `sdt` varchar(15) DEFAULT NULL',
    'SELECT 1'
);
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ngaysinh
SET @s = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'tbl_tkkhachhang' AND COLUMN_NAME = 'ngaysinh') = 0,
    'ALTER TABLE `tbl_tkkhachhang` ADD COLUMN `ngaysinh` date DEFAULT NULL',
    'SELECT 1'
);
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- gioitinh
SET @s = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'tbl_tkkhachhang' AND COLUMN_NAME = 'gioitinh') = 0,
    "ALTER TABLE `tbl_tkkhachhang` ADD COLUMN `gioitinh` enum('Nam','Nữ','Khác') DEFAULT NULL",
    'SELECT 1'
);
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- avatar
SET @s = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'tbl_tkkhachhang' AND COLUMN_NAME = 'avatar') = 0,
    'ALTER TABLE `tbl_tkkhachhang` ADD COLUMN `avatar` varchar(255) DEFAULT NULL',
    'SELECT 1'
);
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- diachi
SET @s = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'tbl_tkkhachhang' AND COLUMN_NAME = 'diachi') = 0,
    'ALTER TABLE `tbl_tkkhachhang` ADD COLUMN `diachi` varchar(255) DEFAULT NULL',
    'SELECT 1'
);
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- last_updated
SET @s = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'tbl_tkkhachhang' AND COLUMN_NAME = 'last_updated') = 0,
    'ALTER TABLE `tbl_tkkhachhang` ADD COLUMN `last_updated` int(11) DEFAULT NULL',
    'SELECT 1'
);
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 3. Tạo bảng `tbl_diachi` (sổ địa chỉ)
-- ============================================================

CREATE TABLE IF NOT EXISTS `tbl_diachi` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `makh` int(11) NOT NULL,
    `hoten` varchar(255) NOT NULL,
    `sdt` varchar(15) NOT NULL,
    `tinh` varchar(100) DEFAULT NULL,
    `quan_huyen` varchar(100) DEFAULT NULL,
    `phuong_xa` varchar(100) DEFAULT NULL,
    `diachi_cuthe` varchar(255) NOT NULL,
    `macdinh` tinyint(1) DEFAULT 0,
    `ngaytao` int(11) DEFAULT NULL,
    `ngaycapnhat` int(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `fk_diachi_kh` (`makh`),
    CONSTRAINT `fk_diachi_kh` FOREIGN KEY (`makh`)
        REFERENCES `tbl_tkkhachhang` (`makh`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 4. Tạo bảng `tbl_sizegiay`
-- ============================================================

CREATE TABLE IF NOT EXISTS `tbl_sizegiay` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `makh` int(11) NOT NULL,
    `he_size` enum('EU','US','CM') DEFAULT 'EU',
    `size_value` varchar(10) NOT NULL,
    `ghichu` varchar(255) DEFAULT NULL,
    `ngaytao` int(11) DEFAULT NULL,
    `ngaycapnhat` int(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `fk_size_kh` (`makh`),
    CONSTRAINT `fk_size_kh` FOREIGN KEY (`makh`)
        REFERENCES `tbl_tkkhachhang` (`makh`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 5. Tạo bảng `tbl_tonkho` + seed
-- ============================================================

CREATE TABLE IF NOT EXISTS `tbl_tonkho` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `masp` INT(11) NOT NULL,
    `size` INT(11) NOT NULL,
    `soluong` INT(11) NOT NULL DEFAULT 0,
    `ngaytao` INT(11) DEFAULT NULL,
    `ngaycapnhat` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_masp_size` (`masp`, `size`),
    CONSTRAINT `fk_tonkho_sanpham` FOREIGN KEY (`masp`)
        REFERENCES `tbl_qlsanpham` (`masp`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT IGNORE INTO `tbl_tonkho` (`masp`, `size`, `soluong`, `ngaytao`, `ngaycapnhat`)
SELECT sp.masp, s.size_val, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `tbl_qlsanpham` sp
CROSS JOIN (
    SELECT 36 AS size_val UNION ALL SELECT 37 UNION ALL SELECT 38 UNION ALL SELECT 39
    UNION ALL SELECT 40 UNION ALL SELECT 41 UNION ALL SELECT 42 UNION ALL SELECT 43 UNION ALL SELECT 44
) s;

/*!40014 SET FOREIGN_KEY_CHECKS=1 */;
