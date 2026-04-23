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
    <?php
    session_start();
    if (!empty($_SESSION['dangnhap1'])) {
        include './connect_db.php';
        $oder = mysqli_query($con, "SELECT oder.tenkh, oder.diachi, oder.sdt, oder.note, oder_chitiet.*, tbl_qlsanpham.tensp as tbl_qlsanpham_tensp
FROM oder
INNER JOIN oder_chitiet ON oder.id = oder_chitiet.madonhang
INNER JOIN tbl_qlsanpham ON tbl_qlsanpham.masp = oder_chitiet.masp
WHERE oder.id = " . $_GET['id']);

        $oder = mysqli_fetch_all($oder, MYSQLI_ASSOC);

    }
    ?>
    <div id="order-detail-wrapper">
        <div id="order-detail">
            <h1>Chi tiết đơn hàng</h1>
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
                tiền:</label> <?= number_format($totalMoney, 0, ",", ".") ?> đ
            <p><label>Ghi chú: </label><?= $oder[0]['note'] ?></p>
        </div>
    </div>
</body>

</html>