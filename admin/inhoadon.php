<?php
require_once dirname(__DIR__) . '/config.php';
csrf_require_post();
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['dangnhap1'])) {
    app_redirect(BASE_URL . 'admin/canhbao.php');
    exit;
}

include './connect_db.php';

// Kiểm tra có ID đơn hàng không
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    $_SESSION['swal_warning'] = 'Không tìm thấy đơn hàng!';
    app_redirect(BASE_URL . 'admin/quanlidonhang.php', 303);
    exit;
}

$id = (int)$_POST['id'];

// Lấy trạng thái hiện tại của đơn hàng bằng prepared statement
$stmtStatus = mysqli_prepare($con, "SELECT `status` FROM `oder` WHERE `id` = ? LIMIT 1");
mysqli_stmt_bind_param($stmtStatus, "i", $id);
mysqli_stmt_execute($stmtStatus);
$resStatus = mysqli_stmt_get_result($stmtStatus);
$orderRow = mysqli_fetch_assoc($resStatus);
mysqli_stmt_close($stmtStatus);

if (!$orderRow) {
    $_SESSION['swal_warning'] = 'Đơn hàng không tồn tại!';
    app_redirect(BASE_URL . 'admin/quanlidonhang.php', 303);
    exit;
}

// Không cho in đơn đã hủy
$currentStatus = $orderRow['status'] ?? 'PENDING';
if ($currentStatus === 'CANCELLED') {
    $_SESSION['swal_warning'] = 'Đơn hàng đã bị hủy, không thể in!';
    app_redirect(BASE_URL . 'admin/quanlidonhang.php', 303);
    exit;
}
$statusUpdated = false;
if (in_array($currentStatus, ['PENDING', 'CONFIRMED'])) {
    mysqli_query($con, "UPDATE `oder` SET `status` = 'SHIPPING' WHERE `id` = $id");
    $statusUpdated = true;
}

$oder = mysqli_query($con, "SELECT oder.tenkh, oder.diachi, oder.sdt, oder.note, oder.status, oder_chitiet.*, tbl_qlsanpham.tensp as tbl_qlsanpham_tensp
FROM oder
INNER JOIN oder_chitiet ON oder.id = oder_chitiet.madonhang
INNER JOIN tbl_qlsanpham ON tbl_qlsanpham.masp = oder_chitiet.masp
WHERE oder.id = " . $id);

$oder = mysqli_fetch_all($oder, MYSQLI_ASSOC);

if (empty($oder)) {
    $_SESSION['swal_warning'] = 'Đơn hàng không tồn tại hoặc chưa có sản phẩm!';
    app_redirect(BASE_URL . 'admin/quanlidonhang.php', 303);
    exit;
}
?>
<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="./in.css">
</head>

<body>
    <?php if ($statusUpdated): ?>
    <div style="background:#d1fae5; border:1px solid #6ee7b7; border-radius:8px; padding:10px 16px; margin:10px auto; max-width:700px; color:#065f46; font-size:14px; font-family:sans-serif;">
        &check; Đơn hàng đã tự động chuyển sang trạng thái <strong>"Đang giao hàng"</strong>
    </div>
    <?php endif; ?>
    <div id="order-detail-wrapper">
        <div id="order-detail">
            <h1>Chi tiết đơn hàng #<?= e($id) ?></h1>
            <label>Trạng thái: </label><span><strong><?php
                $statusMap = [
                    'PENDING' => 'Chờ xử lý',
                    'CONFIRMED' => 'Đã xác nhận',
                    'SHIPPING' => 'Đang giao hàng',
                    'DELIVERED' => 'Đã giao hàng',
                    'CANCELLED' => 'Đã hủy'
                ];
                echo e($statusMap[$statusUpdated ? 'SHIPPING' : $currentStatus] ?? $currentStatus);
            ?></strong></span><br />
            <label>Người nhận: </label><span> <?= e($oder[0]['tenkh']) ?></span><br />

            <label>Điện
                thoại: </label><span> <?= e($oder[0]['sdt']) ?></span><br />
            <label>Địa chỉ: </label><span> <?= e($oder[0]['diachi']) ?></span><br />
            <hr />
            <h3>Danh sách sản phẩm</h3>
            <ul>
                <?php
                $totalQuantity = 0;
                $totalMoney    = 0;
                foreach ($oder as $row) {
                ?>
                    <li>
                        <span class="item-name"><?= e($row['tbl_qlsanpham_tensp']) ?></span>
                        <?php if (!empty($row['size'])): ?>
                        <span class="item-size"> - Size: <?= (int)$row['size'] ?></span>
                        <?php endif; ?>
                        <span class="item-quantity"> - SL: <?= (int)$row['quantity'] ?> sản phẩm</span>
                    </li>
                <?php
                    $totalMoney    += ($row['price'] * $row['quantity']);
                    $totalQuantity += $row['quantity'];
                }
                ?>
            </ul>
            <hr />
            <label>Tổng SL:</label> <?= e($totalQuantity) ?> - <label>Tổng
                tiền:</label> <?= e(number_format($totalMoney, 0, ",", ".")) ?>&nbsp;đ
            <p><label>Ghi chú: </label><?= e($oder[0]['note']) ?></p>
        </div>
    </div>
<script nonce="<?= e(csp_nonce()) ?>">
    // Tự động mở hộp thoại in và quay lại trang quản lí đơn hàng sau khi in xong/hủy in
    window.onload = function() {
        window.print();
        // Sau khi đóng hộp thoại in (in hoặc hủy), quay về trang quản lí
        window.onafterprint = function() {
            window.location.href = 'quanlidonhang.php';
        };
        // Fallback cho trình duyệt không hỗ trợ onafterprint
        setTimeout(function() {
            window.location.href = 'quanlidonhang.php';
        }, 1000);
    };
</script>
</body>

</html>