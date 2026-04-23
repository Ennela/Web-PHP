<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
<head>
    <title>Xoá sản phẩm</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <style>
        .box-content {
            margin: 0 auto;
            width: 800px;
            border: 1px solid #ccc;
            text-align: center;
            padding: 20px;
        }

        #create_user form {
            width: 200px;
            margin: 40px auto;
        }

        #create_user form input {
            margin: 5px 0;
        }
    </style>
</head>
<body>
<?php
    $error = false;
    if (isset($_GET['makh']) && ! empty($_GET['makh'])) {
        include './connect_db.php';
        $result = mysqli_query($con,
            "DELETE FROM `tbl_tkkhachhang` WHERE `makh` = ".$_GET['makh']);
        if ( ! $result) {
            $error = "Không thể xóa sản phẩm.";
        }
        mysqli_close($con);
        if ($error !== false) {
            ?>
            <div id="error-notify" class="box-content">
                <h1>Thông báo</h1>
                <h4><?= $error ?></h4>
                <a href="./index.php">Danh sách tài khoản</a>
            </div>
            <?php
        } else { ?>
            <div id="success-notify" class="box-content">
                <h1>Xóa tài khoản thành công</h1>
                <a href="./quanlikhachhang.php">Quay lại</a>
            </div>
            <?php
        } ?>
        <?php
    } ?>
</body>
</html>
