<?php
require_once dirname(__DIR__) . '/config.php';
include './connect_db.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Lỗi bảo mật: Thao tác xóa chỉ được phép thực hiện qua phương thức POST.');
}

verify_csrf_or_abort();

$error = false;
$masp = intval($_POST['masp'] ?? 0);

if ($masp > 0) {
    // Xóa liên kết tồn kho và thư viện ảnh trước
    $stmtStock = mysqli_prepare($con, "DELETE FROM `tbl_tonkho` WHERE `masp` = ?");
    mysqli_stmt_bind_param($stmtStock, "i", $masp);
    mysqli_stmt_execute($stmtStock);
    mysqli_stmt_close($stmtStock);

    $stmtImg = mysqli_prepare($con, "DELETE FROM `image_library` WHERE `product_id` = ?");
    mysqli_stmt_bind_param($stmtImg, "i", $masp);
    mysqli_stmt_execute($stmtImg);
    mysqli_stmt_close($stmtImg);

    // Xóa sản phẩm bằng prepared statement
    $stmt = mysqli_prepare($con, "DELETE FROM `tbl_qlsanpham` WHERE `masp` = ?");
    mysqli_stmt_bind_param($stmt, "i", $masp);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$result) {
        $error = "Không thể xóa sản phẩm.";
    }
} else {
    $error = "Mã sản phẩm không hợp lệ.";
}

mysqli_close($con);

if ($error === false) {
    header('Location: ./quanlisanpham.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Xoá sản phẩm</title>
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
        <a href="./quanlisanpham.php">Quay lại danh sách sản phẩm</a>
    </div>
</body>
</html>
