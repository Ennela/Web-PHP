<?php
// ─── Admin Database Connection ───
// Uses Railway environment variables when available, falls back to local XAMPP defaults
$host     = getenv('MYSQLHOST')     ?: '127.0.0.1';
$user     = getenv('MYSQLUSER')     ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: '';
$database = getenv('MYSQLDATABASE') ?: 'giaythethao2';
$port     = getenv('MYSQLPORT')     ?: 3306;

$con = mysqli_connect($host, $user, $password, $database, (int)$port);
if (mysqli_connect_errno()) {
    echo "Connection Fail: " . mysqli_connect_errno();
    exit;
}
mysqli_set_charset($con, "utf8mb4");
