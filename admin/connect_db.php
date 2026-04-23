<?php

$host = "127.0.0.1";
$user = "root";
$password = "";
$database = "giaythethao2";
$con = mysqli_connect($host, $user, $password, $database);
if (mysqli_connect_errno()) {
    echo "Connection Fail: " . mysqli_connect_errno();
    exit;
}
mysqli_set_charset($con, "utf8mb4");
