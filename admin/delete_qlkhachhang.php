<?php
require_once dirname(__DIR__) . '/config.php';
include './connect_db.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Lỗi bảo mật: Thao tác xóa khách hàng chỉ được phép thực hiện qua phương thức POST.');
}

verify_csrf_or_abort();

$error = false;
$makh = intval($_POST['makh'] ?? 0);

if ($makh > 0) {
    // Xóa địa chỉ và size giày liên kết
    $stmtAddr = mysqli_prepare($con, "DELETE FROM `tbl_diachi` WHERE `makh` = ?");
    mysqli_stmt_bind_param($stmtAddr, "i", $makh);
    mysqli_stmt_execute($stmtAddr);
    mysqli_stmt_close($stmtAddr);

    $stmtSize = mysqli_prepare($con, "DELETE FROM `tbl_sizegiay` WHERE `makh` = ?");
    mysqli_stmt_bind_param($stmtSize, "i", $makh);
    mysqli_stmt_execute($stmtSize);
    mysqli_stmt_close($stmtSize);

    // Xóa tài khoản khách hàng
    $stmt = mysqli_prepare($con, "DELETE FROM `tbl_tkkhachhang` WHERE `makh` = ?");
    mysqli_stmt_bind_param($stmt, "i", $makh);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$result) {
        $error = "Không thể xóa khách hàng.";
    }
} else {
    $error = "Mã khách hàng không hợp lệ.";
}

mysqli_close($con);

if ($error === false) {
    header('Location: ./quanlikhachhang.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Xoá khách hàng</title>
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
        <a href="./quanlikhachhang.php">Quay lại danh sách khách hàng</a>
    </div>
</body>
</html>
