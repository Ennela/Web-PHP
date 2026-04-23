<?php
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];

$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$dir = str_replace('\\', '/', __DIR__);
if (strpos($dir, $docRoot) === 0) {
    $base_dir = substr($dir, strlen($docRoot)) . '/';
} else {
    $base_dir = '/WEB-PHP/'; 
}

define('BASE_URL', $protocol . '://' . $host . $base_dir);
define('BASE_PATH', __DIR__ . DIRECTORY_SEPARATOR);
?>