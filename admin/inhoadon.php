<?php
session_start();
if (empty($_SESSION['dangnhap1'])) {
    header('Location: canhbao.php');
    exit;
}

include './connect_db.php';

// Kiểm tra có ID đơn hàng không
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('Không tìm thấy đơn hàng!'); window.location.href='quanlidonhang.php';</script>";
    exit;
}

$id = (int)$_GET['id'];

// Lấy trạng thái hiện tại của đơn hàng
$orderStatus = mysqli_query($con, "SELECT `status` FROM `oder` WHERE `id` = $id LIMIT 1");
$orderRow = mysqli_fetch_assoc($orderStatus);

if (!$orderRow) {
    echo "<script>alert('Đơn hàng không tồn tại!'); window.location.href='quanlidonhang.php';</script>";
    exit;
}

// Không cho in đơn đã hủy
$currentStatus = $orderRow['status'] ?? 'PENDING';
if ($currentStatus === 'CANCELLED') {
    echo "<script>alert('Đơn hàng đã bị hủy, không thể in!'); window.location.href='quanlidonhang.php';</script>";
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
    echo "<script>alert('Đơn hàng không tồn tại hoặc chưa có sản phẩm!'); window.location.href='quanlidonhang.php';</script>";
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
        ✓ Đơn hàng đã tự động chuyển sang trạng thái <strong>"Đang giao hàng"</strong>
    </div>
    <?php endif; ?>
    <div id="order-detail-wrapper">
        <div id="order-detail">
            <h1>Chi tiết đơn hàng #<?= $id ?></h1>
            <label>Trạng thái: </label><span><strong><?php
                $statusMap = [
                    'PENDING' => 'Chờ xử lý',
                    'CONFIRMED' => 'Đã xác nhận',
                    'SHIPPING' => 'Đang giao hàng',
                    'DELIVERED' => 'Đã giao hàng',
                    'CANCELLED' => 'Đã hủy'
                ];
                echo $statusMap[$statusUpdated ? 'SHIPPING' : $currentStatus] ?? $currentStatus;
            ?></strong></span><br />
            <label>Người nhận: </label><span> <?= $oder[0]['tenkh'] ?></span><br />

            <label>Điện
                thoại: </label><span> <?= $oder[0]['sdt'] ?></span><br />
            <label>Địa chỉ: </label><span> <?= $oder[0]['diachi'] ?></span><br />
            <hr />
            <h3>Danh sách sản phẩm</h3>
            <ul>
                <?php
                $totalQuantity = 0;
                $totalMoney    = 0;
                foreach ($oder as $row) {
                ?>
                    <li>
                        <span class="item-name"><?= $row['tbl_qlsanpham_tensp'] ?></span>
                        <?php if (!empty($row['size'])): ?>
                        <span class="item-size"> - Size: <?= $row['size'] ?></span>
                        <?php endif; ?>
                        <span class="item-quantity"> - SL: <?= $row['quantity'] ?> sản phẩm</span>
                    </li>
                <?php
                    $totalMoney    += ($row['price'] * $row['quantity']);
                    $totalQuantity += $row['quantity'];
                }
                ?>
            </ul>
            <hr />
            <label>Tổng SL:</label> <?= $totalQuantity ?> - <label>Tổng
                tiền:</label> <?= number_format($totalMoney, 0, ",", ".") ?>&nbsp;đ
            <p><label>Ghi chú: </label><?= $oder[0]['note'] ?></p>
        </div>
    </div>
<script>
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