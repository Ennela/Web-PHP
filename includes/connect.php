<?php
// ─── Database Connection ───
// Uses Railway environment variables when available, falls back to local XAMPP defaults
$db_host     = getenv('MYSQLHOST')     ?: '127.0.0.1';
$db_user     = getenv('MYSQLUSER')     ?: 'root';
$db_password = getenv('MYSQLPASSWORD') ?: '';
$db_name     = getenv('MYSQLDATABASE') ?: 'giaythethao2';
$db_port     = getenv('MYSQLPORT')     ?: 3306;

$con = mysqli_connect($db_host, $db_user, $db_password, $db_name, (int)$db_port);

// Check connection
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit;
}
// Change character set to utf8
mysqli_set_charset($con, "utf8mb4");
?>