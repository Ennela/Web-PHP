<?php 
require_once __DIR__ . '/admin_guard.php'; 

// Chỉ chấp nhận method POST để chống kích hoạt trực tiếp từ URL GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: quanlidonhang.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Xoá đơn hàng</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .box-content {
            margin: 40px auto;
            width: 500px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            text-align: center;
            padding: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .btn-link {
            display: inline-block;
            margin-top: 15px;
            padding: 8px 16px;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
        }
    </style>
</head>
<body>
<?php
    $error = false;
    $id = $_POST['id'] ?? null;
    if (!empty($id)) {
        include './connect_db.php';
        $__id   = sql_int($id);
        $__stmt = mysqli_prepare($con, "DELETE FROM `oder` WHERE `id` = ?");
        mysqli_stmt_bind_param($__stmt, "i", $__id);
        $result = mysqli_stmt_execute($__stmt);
        mysqli_stmt_close($__stmt);
        if (!$result) {
            $error = "Không thể xóa đơn hàng.";
        }
        mysqli_close($con);
        if ($error !== false) {
            ?>
            <div id="error-notify" class="box-content">
                <h1 style="color:#dc2626;">Thông báo</h1>
                <h4><?= htmlspecialchars($error) ?></h4>
                <a class="btn-link" href="./quanlidonhang.php">Quay lại danh sách</a>
            </div>
            <?php
        } else { ?>
            <div id="success-notify" class="box-content">
                <h1 style="color:#16a34a;">Xóa đơn hàng thành công</h1>
                <a class="btn-link" href="./quanlidonhang.php">Quay lại</a>
            </div>
            <?php
        }
    } else {
        header('Location: quanlidonhang.php');
        exit;
    } ?>
</body>
</html>
