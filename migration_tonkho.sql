-- ============================================================
-- MIGRATION: Hệ thống tồn kho (Inventory Management)
-- Chạy trên database giaythethao2
-- ============================================================

USE `giaythethao2`;

-- 1. Tạo bảng tồn kho theo size
DROP TABLE IF EXISTS `tbl_tonkho`;
CREATE TABLE `tbl_tonkho` (
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

-- 2. Thêm cột payment_status cho bảng đơn hàng (bỏ qua nếu đã có)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'giaythethao2' AND TABLE_NAME = 'oder' AND COLUMN_NAME = 'payment_status');
SET @sql = IF(@col_exists = 0, "ALTER TABLE `oder` ADD COLUMN `payment_status` VARCHAR(20) DEFAULT 'UNPAID' AFTER `status`", 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Thêm cột vnpay_tranId nếu chưa có
SET @col2 = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'giaythethao2' AND TABLE_NAME = 'oder' AND COLUMN_NAME = 'vnpay_tranId');
SET @sql2 = IF(@col2 = 0, "ALTER TABLE `oder` ADD COLUMN `vnpay_tranId` VARCHAR(50) DEFAULT NULL", 'SELECT 1');
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- 4. Cập nhật đơn hàng DELIVERED cũ thành PAID
UPDATE `oder` SET `payment_status` = 'PAID' WHERE `status` = 'DELIVERED' AND `payment_status` = 'UNPAID';

-- 5. Seed tồn kho mẫu: 20 đơn vị/size cho mỗi sản phẩm
INSERT INTO `tbl_tonkho` (`masp`, `size`, `soluong`, `ngaytao`, `ngaycapnhat`)
SELECT sp.masp, s.size_val, 
  CASE 
    WHEN sp.masp = (SELECT MIN(masp) FROM tbl_qlsanpham) AND s.size_val IN (38, 44) THEN 0
    WHEN sp.masp = (SELECT MIN(masp) FROM tbl_qlsanpham) + 1 AND s.size_val = 36 THEN 2
    ELSE 20
  END,
  UNIX_TIMESTAMP(),
  UNIX_TIMESTAMP()
FROM `tbl_qlsanpham` sp
CROSS JOIN (
  SELECT 36 AS size_val UNION ALL SELECT 37 UNION ALL SELECT 38 UNION ALL SELECT 39 
  UNION ALL SELECT 40 UNION ALL SELECT 41 UNION ALL SELECT 42 UNION ALL SELECT 43 UNION ALL SELECT 44
) s;
