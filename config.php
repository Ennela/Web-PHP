<?php
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
// Railway sets X-Forwarded-Proto for HTTPS
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $protocol = "https";
}
$host = $_SERVER['HTTP_HOST'];

$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
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

define('BASE_URL', $protocol . '://' . $host . $base_dir);
define('BASE_PATH', __DIR__ . DIRECTORY_SEPARATOR);
?>