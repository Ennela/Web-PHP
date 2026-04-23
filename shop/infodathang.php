<?php
require_once dirname(__DIR__) . '/config.php';
    session_start();

    include BASE_PATH . 'includes/connect.php';

    if (isset($_GET['login'])) {
        $dangxuat = $_GET['login'];
    } else {
        $dangxuat = '';
    }
    if ($dangxuat == 'dangxuat') {
        session_destroy();
        header('Location: ' . BASE_URL . 'home/trangchu.php');
    }

    function GuiMail()
    {
        require BASE_PATH . 'PHPMailer-master/src/PHPMailer.php';
        require BASE_PATH . 'PHPMailer-master/src/SMTP.php';
        require BASE_PATH . 'PHPMailer-master/src/Exception.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);//true:enables exceptions
        try {
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->CharSet = "utf-8";
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'remkyorosi@gmail.com'; // Gmail người bán
            $mail->Password = 'nvui gcgt snxd rpib';  // App Password
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;
            $mail->setFrom('remkyorosi@gmail.com', 'Shop Sneakers');
            $mail->addAddress('remkyorosi@gmail.com', 'Nguyễn Văn Kiên'); // Thông báo về mail chủ shop
            $mail->isHTML(true);  // Set email format to HTML
            $mail->Subject = 'Liên hệ';
            $noidungthu = "
     <h3>Thông báo có đơn hàng</h3>
     <p> Tên khách hàng: <br>{$_POST['tenkh']} </p>
   ";
            $mail->Body = $noidungthu;
            $mail->smtpConnect(array("ssl" => array("verify_peer" => false, "verify_peer_name" => false,
                "allow_self_signed" => true)));
            $mail->send();
//            echo 'Đã gửi mail xong';
        } catch (Exception $e) {
//            echo 'Mail không gửi được. Lỗi: ', $mail->ErrorInfo;
        }
    }//function GuiMail

    // Load the cart from session for display
    $cart = isset($_SESSION['chuyen']) ? $_SESSION['chuyen'] : [];
    $cartSizes = isset($_SESSION['chuyen_size']) ? $_SESSION['chuyen_size'] : [];
    $total = 0;
    $orderProducts = array();

    if (!empty($cart)) {
        $ids = implode(",", array_keys($cart));
        if (!empty($ids)) {
            $query = mysqli_query($con, "SELECT * FROM `tbl_qlsanpham` WHERE `masp` IN ($ids)");
            while ($row = mysqli_fetch_array($query)) {
                $qty = $cart[$row['masp']];
                if ($qty > 0) {
                    $row['qty_in_cart'] = $qty;
                    $row['order_size'] = isset($cartSizes[$row['masp']]) ? (int)$cartSizes[$row['masp']] : null;
                    $orderProducts[] = $row;
                    $total += $row['giasanpham'] * $qty;
                }
            }
        }
    }

    // Redirect if cart is empty
    if (empty($orderProducts)) {
        echo "<script>alert('Giỏ hàng trống!'); window.location.href='" . BASE_URL . "shop/giohang.php';</script>";
        exit;
    }

    if (isset($_GET['action'])) {
        $_POST['quantity'] = $_SESSION['chuyen'];
        $sizes = isset($_SESSION['chuyen_size']) ? $_SESSION['chuyen_size'] : [];
        $timsanpham = mysqli_query($con, "SELECT * FROM `tbl_qlsanpham` WHERE `masp` IN (" . implode(",", array_keys($_POST['quantity'])) . ")");
        $total = 0;
        $submitProducts = array();
        while ($row = mysqli_fetch_array($timsanpham)) {
            $row['order_size'] = isset($sizes[$row['masp']]) ? (int)$sizes[$row['masp']] : null;
            $submitProducts[] = $row;
            $total += $row['giasanpham'] * $_POST['quantity'][$row['masp']];
        }
        if ($_POST['tenkh'] == '' || $_POST['sdt'] == '' || $_POST['diachi'] == '' || $_POST['quantity'] == '') {
            ?>
            <script>
                alert('Mời bạn nhập đầy đủ thông tin');
                window.location.href = 'infodathang.php';
            </script>
            <?php
        }
        $makh_val = !empty($_SESSION['makh']) ? intval($_SESSION['makh']) : 'NULL';
        require_once BASE_PATH . 'includes/order_helper.php';
        $orderCode = generateOrderCode($con);
        $insertOrder = mysqli_query($con, "INSERT INTO `oder` (`id`, `order_code`, `tenkh`, `sdt`, `diachi`, `note`, `tongtien`, `ngaytao`,`donhangthang`, `makh`) VALUES (NULL, '$orderCode', '" . $_POST['tenkh'] . "', '" . $_POST['sdt'] . "', '" . $_POST['diachi'] . "', '" . $_POST['note'] . "', '" . $total . "', '" . time() . "', '" . date('m') . "', $makh_val)");
        $orderID = $con->insert_id;// lưu id giỏ hàng
        $insertString = "";
        foreach ($submitProducts as $key => $timsanpham) {
            $sizeVal = !empty($timsanpham['order_size']) ? "'" . (int)$timsanpham['order_size'] . "'" : "NULL";
            $insertString .= "(NULL, '" . $orderID . "', '" . $timsanpham['masp'] . "', '" . $_POST['quantity'][$timsanpham['masp']] . "', " . $sizeVal . ", '" . $timsanpham['giasanpham'] . "', '" . time() . "', '" . time() . "')";

            if ($key != count($submitProducts) - 1) {
                $insertString .= ",";
            }
        }
        $insertOrder = mysqli_query($con, "INSERT INTO `oder_chitiet` (`id`, `madonhang`, `masp`, `quantity`, `size`, `price`, `created_time`, `last_updated`) VALUES " . $insertString . ";");
        unset($_SESSION['giohang']); // xoá lại giỏ hàng
        unset($_SESSION['chuyen_size']);
        GuiMail();
        ?>
        <script>
            alert('Đơn hàng đã đặt thành công !');
            window.location.href = 'giohang.php';

        </script>
        <?php

    }

    include BASE_PATH . 'includes/header.php';
?>

<main class="page landing-page" style="padding-top: 100px; min-height: 80vh; background: #f8f9fa;">
    <style>
        @media (max-width: 768px) {
            .page.landing-page { padding-top: 80px !important; }
            .block-heading h2 { font-size: 1.4rem; }
        }
        @media (max-width: 576px) {
            .page.landing-page { padding-top: 70px !important; }
            .block-heading h2 { font-size: 1.2rem; }
            .block-heading p { font-size: 0.9rem; }
        }
    </style>
    <section class="clean-block clean-info dark">
        <div class="container">
            <div class="block-heading text-center mb-5">
                <h2 class="text-info font-weight-bold">Thanh toán Tiền mặt (COD)</h2>
                <p>Nhập thông tin giao hàng để hoàn tất đơn hàng. Bạn sẽ thanh toán khi nhận hàng.</p>
            </div>

            <div class="row align-items-start">
                <div class="col-md-7 mb-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                            <h4 class="card-title text-info"><i class="fas fa-truck mr-2"></i> Thông tin Nhận hàng</h4>
                        </div>
                        <div class="card-body">
                            <form method="post" action="<?php echo BASE_URL; ?>shop/infodathang.php?action=submit">
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Họ tên người nhận <span class="text-danger">*</span></label>
                                    <input name="tenkh" required class="form-control" type="text" placeholder="Nhập đầy đủ họ tên"/>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Số điện thoại <span class="text-danger">*</span></label>
                                    <input name="sdt" required class="form-control" type="text" placeholder="09xxxxxxx"/>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Địa chỉ chi tiết <span class="text-danger">*</span></label>
                                    <textarea name="diachi" required class="form-control" rows="2" placeholder="Số nhà, Phường/Xã, Quận/Huyện..."></textarea>
                                </div>
                                <div class="form-group mb-4">
                                    <label class="font-weight-bold">Ghi chú (nếu có)</label>
                                    <textarea name="note" class="form-control" rows="2" placeholder="Ghi chú về thời gian giao hàng..."></textarea>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="<?php echo BASE_URL; ?>shop/giohang.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left mr-2"></i> Quay lại
                                    </a>
                                    <button type="submit" name="xacnhan" class="btn btn-info btn-lg font-weight-bold shadow-sm" style="font-size: 1.1rem; padding: 12px 30px;">
                                        Xác nhận Đặt hàng <i class="fas fa-check ml-2"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                            <h4 class="card-title text-info"><i class="fas fa-shopping-basket mr-2"></i> Tóm tắt Đơn hàng</h4>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush mb-3">
                                <?php foreach($orderProducts as $sp): ?>
                                <li class="list-group-item d-flex justify-content-between lh-condensed px-0">
                                    <div>
                                        <h6 class="my-0"><?= htmlspecialchars($sp['tensp']) ?></h6>
                                        <small class="text-muted">SL: <?= $sp['qty_in_cart'] ?><?php if (!empty($sp['order_size'])): ?> · Size: <?= $sp['order_size'] ?><?php endif; ?></small>
                                    </div>
                                    <span class="text-muted"><?= number_format($sp['giasanpham'] * $sp['qty_in_cart'], 0, ',', '.') ?>đ</span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <hr>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Phí vận chuyển</span>
                                <span class="text-success font-weight-bold">Miễn phí</span>
                            </div>
                            <div class="d-flex justify-content-between font-weight-bold p-3 bg-light rounded text-info align-items-center">
                                <span style="font-size: 18px">Tổng cộng</span>
                                <strong style="font-size: 24px"><?= number_format($total, 0, ',', '.') ?>đ</strong>
                            </div>
                            <div class="text-center mt-3">
                                <small class="text-muted"><i class="fas fa-shield-alt text-success mr-1"></i> Thanh toán khi nhận hàng — An toàn & Tiện lợi</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>


<?php include BASE_PATH . 'includes/footer.php'; ?>
