<?php
require_once __DIR__ . '/includes/environment.php';
app_load_local_environment(__DIR__ . '/.env');
require_once __DIR__ . '/includes/security.php';
$canonical_url = app_security_bootstrap();
$protocol = app_is_https() ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? __DIR__), '/');
$dir = str_replace('\\', '/', __DIR__);
if (strpos($dir, $docRoot) === 0) {
    $base_dir = substr($dir, strlen($docRoot)) . '/';
} else {
    $base_dir = '/WEB-PHP/'; 
}

// On Railway / Docker, DocumentRoot IS the app root → base_dir = "/"
if ($base_dir === '/' || $base_dir === '//') {
    $base_dir = '/';
}

define('BASE_URL', $canonical_url ?? ($protocol . '://' . $host . $base_dir));
define('BASE_PATH', __DIR__ . DIRECTORY_SEPARATOR);

require_once BASE_PATH . 'includes/csrf.php';
csrf_bootstrap($base_dir, $protocol === 'https');
?>
