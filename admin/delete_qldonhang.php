<?php
require_once dirname(__DIR__) . '/config.php';
include './connect_db.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Lỗi bảo mật: Thao tác xóa đơn hàng chỉ được phép thực hiện qua phương thức POST.');
}

verify_csrf_or_abort();

$error = false;
$id = intval($_POST['id'] ?? 0);

if ($id > 0) {
    // Xóa chi tiết đơn hàng trước
    $stmtDetail = mysqli_prepare($con, "DELETE FROM `oder_chitiet` WHERE `madonhang` = ?");
    mysqli_stmt_bind_param($stmtDetail, "i", $id);
    mysqli_stmt_execute($stmtDetail);
    mysqli_stmt_close($stmtDetail);

    // Xóa đơn hàng
    $stmt = mysqli_prepare($con, "DELETE FROM `oder` WHERE `id` = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$result) {
        $error = "Không thể xóa đơn hàng.";
    }
} else {
    $error = "Mã đơn hàng không hợp lệ.";
}

mysqli_close($con);

if ($error === false) {
    header('Location: ./quanlidonhang.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Xoá đơn hàng</title>
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
        <a href="./quanlidonhang.php">Quay lại danh sách đơn hàng</a>
    </div>
</body>
</html>
