<?php require_once __DIR__ . '/admin_guard.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Xóa sản phẩm</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .box-content {
            margin: 0 auto;
            width: 800px;
            border: 1px solid #ccc;
            text-align: center;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        h1 {
            color: #dc2626;
        }
        a {
            display: inline-block;
            margin-top: 15px;
            color: #2563eb;
            text-decoration: underline;
        }
    </style>
</head>
<body>
<?php
    // Chặn mọi thao tác xóa qua URL GET
    if (isset($_GET['masp'])) {
        ?>
        <div id="error-notify" class="box-content">
            <h1>Không xóa thành công</h1>
            <p>Thao tác xóa trực tiếp qua URL đã bị vô hiệu hóa vì lý do bảo mật!</p>
            <a href="./quanlisanpham.php">Quay lại</a>
        </div>
        <?php
        exit;
    }

    // Chỉ thực hiện khi gửi qua POST từ form có xác nhận
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['masp'])) {
        include './connect_db.php';
        $__id   = sql_int($_POST['masp']);
        $__stmt = mysqli_prepare($con, "DELETE FROM `tbl_qlsanpham` WHERE `masp` = ?");
        mysqli_stmt_bind_param($__stmt, "i", $__id);
        $result = mysqli_stmt_execute($__stmt);
        mysqli_stmt_close($__stmt);
        mysqli_close($con);

        if (!$result) {
            ?>
            <div id="error-notify" class="box-content">
                <h1>Không xóa thành công</h1>
                <p>Không thể xóa sản phẩm.</p>
                <a href="./quanlisanpham.php">Quay lại</a>
            </div>
            <?php
        } else {
            ?>
            <div id="success-notify" class="box-content">
                <h1 style="color: #16a34a;">Xóa sản phẩm thành công</h1>
                <a href="./quanlisanpham.php">Quay lại</a>
            </div>
            <?php
        }
    } else {
        header('Location: quanlisanpham.php');
        exit;
    }
?>
</body>
</html>
