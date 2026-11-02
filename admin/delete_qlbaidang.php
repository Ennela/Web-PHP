<?php
require_once dirname(__DIR__) . '/config.php';
include './connect_db.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Lỗi bảo mật: Thao tác xóa bài đăng chỉ được phép thực hiện qua phương thức POST.');
}

verify_csrf_or_abort();

$error = false;
$id = intval($_POST['id'] ?? 0);

if ($id > 0) {
    $stmt = mysqli_prepare($con, "DELETE FROM `tbl_qlbaidang` WHERE `id` = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$result) {
        $error = "Không thể xóa bài đăng.";
    }
} else {
    $error = "Mã bài đăng không hợp lệ.";
}

mysqli_close($con);

if ($error === false) {
    header('Location: ./quanlibaidang.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Xoá bài đăng</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .box-content {
            margin: 40px auto;
            width: 800px;
            border: 1px solid #ccc;
            text-align: center;
            padding: 20px;
            font-family: sans-serif;
        }
    </style>
</head>
<body>
    <div id="error-notify" class="box-content">
        <h1>Thông báo lỗi</h1>
        <h4 style="color:red;"><?= htmlspecialchars($error) ?></h4>
        <a href="./quanlibaidang.php">Quay lại danh sách bài đăng</a>
    </div>
</body>
</html>
