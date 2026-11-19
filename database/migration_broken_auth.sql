-- =====================================================================
-- Migration: Broken Authentication & Session Management
-- Bảng ghi nhận lần đăng nhập / đặt lại mật khẩu thất bại để chống brute-force.
-- (includes/auth.php cũng tự tạo bảng này nếu chưa có — file này để chạy thủ công.)
-- =====================================================================
CREATE TABLE IF NOT EXISTS `tbl_login_attempts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `scope` VARCHAR(20) NOT NULL COMMENT 'customer | admin | reset | reset_token | change_pw',
    `identifier` VARCHAR(191) NOT NULL COMMENT 'username (lowercase) hoặc khóa định danh',
    `ip` VARCHAR(45) NOT NULL,
    `attempted_at` INT UNSIGNED NOT NULL,
    KEY `idx_scope_identifier` (`scope`, `identifier`, `attempted_at`),
    KEY `idx_scope_ip` (`scope`, `ip`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Token đặt lại mật khẩu giờ được lưu dạng SHA-256 (64 ký tự hex) -> VARCHAR(64) vẫn đủ.
-- Vô hiệu hóa các token cũ đang lưu dạng plaintext:
UPDATE `tbl_tkkhachhang` SET `reset_token` = NULL, `reset_token_expiry` = NULL WHERE `reset_token` IS NOT NULL;
