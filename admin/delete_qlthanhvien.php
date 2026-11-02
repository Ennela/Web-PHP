<?php
require_once dirname(__DIR__) . '/config.php';
include './connect_db.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Lỗi bảo mật: Thao tác xóa thành viên chỉ được phép thực hiện qua phương thức POST.');
}

verify_csrf_or_abort();

$error = false;
$id = intval($_POST['id'] ?? 0);
$currentAdminId = get_current_admin_id();

if ($id <= 0) {
    $error = "Mã thành viên không hợp lệ.";
} elseif ($id === $currentAdminId) {
    // Ngăn chặn admin tự xóa tài khoản của chính mình (IDOR / Self-destruction defense)
    $error = "Bạn không thể tự xóa tài khoản quản trị của chính mình khi đang đăng nhập!";
} else {
    $stmt = mysqli_prepare($con, "DELETE FROM `tbl_qlthanhvien` WHERE `id` = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$result) {
        $error = "Không thể xóa tài khoản thành viên.";
    }
}

mysqli_close($con);

if ($error === false) {
    header('Location: ./quanlithanhvien.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Xoá thành viên</title>
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
        <a href="./quanlithanhvien.php">Quay lại danh sách thành viên</a>
    </div>
</body>
</html>
