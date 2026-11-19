-- ============================================================
-- MIGRATION: CRYPTOGRAPHIC STORAGE & DATA PROTECTION
-- Widens columns to store Argon2id/Bcrypt password hashes,
-- SHA-256 token digests, and AEAD (AES-256-GCM / XChaCha20-Poly1305)
-- encrypted envelopes without truncation.
-- ============================================================

ALTER TABLE `tbl_tkkhachhang`
    MODIFY COLUMN `password` VARCHAR(255) DEFAULT NULL,
    MODIFY COLUMN `sdt` VARCHAR(512) DEFAULT NULL,
    MODIFY COLUMN `diachi` TEXT DEFAULT NULL,
    MODIFY COLUMN `reset_token` VARCHAR(128) DEFAULT NULL;

ALTER TABLE `tbl_diachi`
    MODIFY COLUMN `sdt` VARCHAR(512) NOT NULL,
    MODIFY COLUMN `diachi_cuthe` TEXT NOT NULL;

ALTER TABLE `tbl_qlthanhvien`
    MODIFY COLUMN `matkhau` VARCHAR(255) DEFAULT NULL,
    MODIFY COLUMN `diachicuthe` TEXT DEFAULT NULL;

ALTER TABLE `oder`
    MODIFY COLUMN `sdt` VARCHAR(512) DEFAULT NULL,
    MODIFY COLUMN `diachi` TEXT DEFAULT NULL,
    MODIFY COLUMN `note` TEXT DEFAULT NULL,
    MODIFY COLUMN `token` VARCHAR(128) DEFAULT NULL;

ALTER TABLE `tbl_dangnhap`
    MODIFY COLUMN `matkhau` VARCHAR(255) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `tbl_email_webhook_log` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `event_type` VARCHAR(50) NOT NULL,
    `message_id` VARCHAR(100) DEFAULT NULL,
    `error_info` TEXT DEFAULT NULL,
    `event_time` INT(11) DEFAULT NULL,
    `created_at` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_webhook_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
