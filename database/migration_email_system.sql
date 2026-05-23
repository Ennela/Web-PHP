-- ============================================================
-- Migration: Email Blacklist & Webhook Log Tables
-- Run this AFTER the main database schema is created.
-- ============================================================

USE `giaythethao2`;

-- ─── 1. Bảng Blacklist Email ───
-- Lưu các email bị bounce / spam complaint / blocked
-- Hệ thống sẽ kiểm tra bảng này trước khi gửi email
CREATE TABLE IF NOT EXISTS `tbl_email_blacklist` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `email`      VARCHAR(255) NOT NULL,
    `reason`     VARCHAR(50)  NOT NULL DEFAULT 'manual'
                 COMMENT 'hard_bounce | spam_complaint | blocked | manual',
    `blocked_at` INT(11)      NOT NULL COMMENT 'Unix timestamp',
    `active`     TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_blacklist_email` (`email`),
    KEY `idx_blacklist_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─── 2. Bảng Log Webhook Events ───
-- Lưu lịch sử tất cả events từ Mailjet webhook
CREATE TABLE IF NOT EXISTS `tbl_email_webhook_log` (
    `id`          INT(11)      NOT NULL AUTO_INCREMENT,
    `email`       VARCHAR(255) NOT NULL,
    `event_type`  VARCHAR(30)  NOT NULL COMMENT 'bounce | spam | blocked | unsub',
    `message_id`  VARCHAR(100) DEFAULT NULL,
    `error_info`  TEXT         DEFAULT NULL,
    `event_time`  INT(11)      NOT NULL COMMENT 'Timestamp from Mailjet',
    `created_at`  INT(11)      NOT NULL COMMENT 'Timestamp khi nhận webhook',
    PRIMARY KEY (`id`),
    KEY `idx_webhook_email` (`email`),
    KEY `idx_webhook_event` (`event_type`),
    KEY `idx_webhook_time` (`event_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─── 3. Thêm cột cho bảng khách hàng (opt-out marketing) ───
ALTER TABLE `tbl_tkkhachhang`
    ADD COLUMN IF NOT EXISTS `email` VARCHAR(255) DEFAULT NULL AFTER `hoten`,
    ADD COLUMN IF NOT EXISTS `sdt` VARCHAR(20) DEFAULT NULL AFTER `email`,
    ADD COLUMN IF NOT EXISTS `diachi` VARCHAR(500) DEFAULT NULL AFTER `sdt`,
    ADD COLUMN IF NOT EXISTS `email_marketing_opt_out` TINYINT(1) NOT NULL DEFAULT 0 AFTER `diachi`,
    ADD COLUMN IF NOT EXISTS `reset_token` VARCHAR(64) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `reset_token_expiry` INT(11) DEFAULT NULL;
