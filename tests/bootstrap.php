<?php
/**
 * PHPUnit Bootstrap
 * Thiết lập môi trường test: DB connection, constants, autoload
 */

// Autoload Composer
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Define constants cho test environment
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}
if (!defined('BASE_URL')) {
    define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost/WEB-PHP/');
}

// Database test config
define('TEST_DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('TEST_DB_USER', getenv('DB_USER') ?: 'root');
define('TEST_DB_PASS', getenv('DB_PASS') ?: '');
define('TEST_DB_NAME', getenv('DB_NAME') ?: 'giaythethao2_test');

// Require TestHelper
require_once __DIR__ . '/TestHelper.php';

// Setup test database on first run
TestHelper::setupTestDatabase();
