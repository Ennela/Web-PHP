<?php
/**
 * Mail Configuration — Tập trung cấu hình email cho toàn hệ thống.
 * Load biến môi trường và định nghĩa constants.
 *
 * Sử dụng: require_once 'mail_config.php'; trước khi gọi mail_helper.php
 */

// ─── Load .env file nếu chạy local (XAMPP) ───
// Trên Railway/Docker, biến môi trường đã được set sẵn.
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!getenv($key)) {
            putenv("$key=$value");
        }
    }
}

// ─── Mail Type Constants ───
define('MAIL_TYPE_TRANSACTIONAL', 'transactional');
define('MAIL_TYPE_MARKETING',     'marketing');
define('MAIL_TYPE_ADMIN',         'admin');

// ─── Sender Configuration ───
define('MAIL_TRANS_FROM_EMAIL', getenv('MAIL_TRANS_FROM_EMAIL') ?: 'no-reply@yourdomain.com');
define('MAIL_TRANS_FROM_NAME',  getenv('MAIL_TRANS_FROM_NAME')  ?: 'Shop Sneakers');

define('MAIL_MARKETING_FROM_EMAIL', getenv('MAIL_MARKETING_FROM_EMAIL') ?: 'hello@yourdomain.com');
define('MAIL_MARKETING_FROM_NAME',  getenv('MAIL_MARKETING_FROM_NAME')  ?: 'Shop Sneakers');

define('MAIL_ADMIN_EMAIL', getenv('MAIL_ADMIN_EMAIL') ?: 'remkyorosi@gmail.com');
define('MAIL_ADMIN_NAME',  getenv('MAIL_ADMIN_NAME')  ?: 'Shop Admin');

// ─── Brand Info (dùng trong email templates) ───
define('BRAND_NAME',     getenv('BRAND_NAME')     ?: 'Shop Sneakers');
define('BRAND_URL',      getenv('BRAND_URL')      ?: 'https://yourdomain.com');
define('BRAND_LOGO_URL', getenv('BRAND_LOGO_URL') ?: '');
define('BRAND_PHONE',    getenv('BRAND_PHONE')    ?: '0394 680 113');
define('BRAND_ADDRESS',  getenv('BRAND_ADDRESS')  ?: 'Số 6 đường Thắng Lợi 1, Hồng Hà, Hà Nội');
define('BRAND_TAX_ID',   getenv('BRAND_TAX_ID')   ?: '');

// ─── Timeout & Retry ───
define('MAIL_CONNECT_TIMEOUT', (int)(getenv('MAIL_CONNECT_TIMEOUT') ?: 5));
define('MAIL_TIMEOUT',         (int)(getenv('MAIL_TIMEOUT')         ?: 15));
define('MAIL_MAX_RETRIES',     (int)(getenv('MAIL_MAX_RETRIES')     ?: 3));

// ─── Unsubscribe ───
define('MAIL_UNSUBSCRIBE_URL', getenv('MAIL_UNSUBSCRIBE_URL') ?: '');
