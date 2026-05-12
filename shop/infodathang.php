<?php
require_once dirname(__DIR__) . '/config.php';
    session_start();

    include BASE_PATH . 'includes/connect.php';
    require_once BASE_PATH . 'includes/inventory_helper.php';

    if (isset($_GET['login'])) {
        $dangxuat = $_GET['login'];
    } else {
        $dangxuat = '';
    }
    if ($dangxuat == 'dangxuat') {
        session_destroy();
        header('Location: ' . BASE_URL . 'home/trangchu.php');
    }
    require_once BASE_PATH . 'includes/mail_helper.php';

    /**
     * Gửi mail thông báo đơn hàng mới cho shop owner (qua Brevo API)
     */
    function GuiMail()
    {
        $body = "<h3>Thông báo có đơn hàng mới</h3>"
              . "<p>Tên khách hàng: <strong>" . htmlspecialchars($_POST['tenkh'] ?? '') . "</strong></p>"
              . "<p>SĐT: " . htmlspecialchars($_POST['sdt'] ?? '') . "</p>"
              . "<p>Địa chỉ: " . htmlspecialchars($_POST['diachi'] ?? '') . "</p>";

        sendMailBrevo('remkyorosi@gmail.com', 'Shop Admin', 'Có đơn hàng mới từ Shop Sneakers', $body);
    }

    /**
     * Gửi mail xác nhận đơn hàng cho khách hàng (qua Brevo API)
     * Brevo free tier: 300 email/ngày, gửi được đến MỌI địa chỉ email
     */
    function GuiMailKhachHang($email, $tenkh, $orderCode, $token, $total)
    {
        if (empty($email)) return;

        $trackingLink = BASE_URL . "shop/tracking.php?order=" . $orderCode . "&token=" . $token;
        $totalFmt     = number_format($total, 0, ',', '.');

        $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 8px; overflow: hidden;'>
                <div style='background: linear-gradient(135deg, #3b82f6, #2563eb); padding: 24px; text-align: center;'>
                    <h2 style='color: white; margin: 0;'>Đơn hàng đã được ghi nhận!</h2>
                </div>
                <div style='padding: 24px;'>
                    <p>Xin chào <strong>" . htmlspecialchars($tenkh) . "</strong>,</p>
                    <p>Cảm ơn bạn đã đặt hàng tại <strong>Shop Sneakers</strong>.</p>
                    <table style='width: 100%; border-collapse: collapse; margin: 16px 0;'>
                        <tr style='background: #f8f9fa;'>
                            <td style='padding: 10px; border: 1px solid #dee2e6;'><strong>Mã đơn hàng</strong></td>
                            <td style='padding: 10px; border: 1px solid #dee2e6;'>#$orderCode</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px; border: 1px solid #dee2e6;'><strong>Tổng tiền</strong></td>
                            <td style='padding: 10px; border: 1px solid #dee2e6; color: #2563eb; font-weight: bold;'>{$totalFmt}&nbsp;đ</td>
                        </tr>
                        <tr style='background: #f8f9fa;'>
                            <td style='padding: 10px; border: 1px solid #dee2e6;'><strong>Hình thức</strong></td>
                            <td style='padding: 10px; border: 1px solid #dee2e6;'>Thanh toán khi nhận hàng (COD)</td>
                        </tr>
                    </table>
                    <p>Theo dõi đơn hàng tại: <a href='$trackingLink'>Xem trạng thái đơn hàng</a></p>
                    <p style='color: #6c757d; font-size: 12px;'>Email này được gửi tự động, vui lòng không reply.</p>
                </div>
            </div>";

        sendMailBrevo($email, $tenkh, "Xác nhận đơn hàng #$orderCode - Shop Sneakers", $body);
    }


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
        $_SESSION['swal_warning'] = 'Giỏ hàng trống!';
        header('Location: ' . BASE_URL . 'shop/giohang.php');
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
        if ($_POST['tenkh'] == '' || $_POST['sdt'] == '' || $_POST['diachi'] == '' || empty($_POST['email']) || $_POST['quantity'] == '') {
            $_SESSION['swal_error'] = 'Mời bạn nhập đầy đủ thông tin';
            header('Location: infodathang.php');
            exit;
        }
        $makh_val = !empty($_SESSION['makh']) ? intval($_SESSION['makh']) : 'NULL';
        require_once BASE_PATH . 'includes/order_helper.php';
        $orderCode = generateOrderCode($con);
        
        // Validate + trừ tồn kho trong 1 transaction (chống race condition)
        $stockCheckItems = [];
        foreach ($submitProducts as $sp) {
            $stockCheckItems[] = [
                'masp' => $sp['masp'],
                'size' => $sp['order_size'] ?? 0,
                'quantity' => $_POST['quantity'][$sp['masp']]
            ];
        }
        $stockResult = validateAndDeductStock($con, $stockCheckItems);
        if (!$stockResult['success']) {
            $errorMsgs = array_map(function($e) { return $e['message']; }, $stockResult['errors']);
            $errorText = implode('\n', $errorMsgs);
            $_SESSION['swal_error'] = "Không thể đặt hàng:\n" . $errorText;
            header('Location: ' . BASE_URL . 'shop/giohang.php');
            exit;
        }
        
        $email = mysqli_real_escape_string($con, trim($_POST['email']));
        $token = bin2hex(random_bytes(16)); // generate token for tracking

        $insertOrder = mysqli_query($con, "INSERT INTO `oder` (`id`, `order_code`, `token`, `tenkh`, `sdt`, `email`, `diachi`, `note`, `tongtien`, `ngaytao`,`donhangthang`, `status`, `payment_status`, `makh`) VALUES (NULL, '$orderCode', '$token', '" . $_POST['tenkh'] . "', '" . $_POST['sdt'] . "', '$email', '" . $_POST['diachi'] . "', '" . $_POST['note'] . "', '" . $total . "', '" . time() . "', '" . date('m') . "', 'PENDING', 'UNPAID', $makh_val)");
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
        
        // Tồn kho đã được trừ trong validateAndDeductStock() ở trên
        
        unset($_SESSION['giohang']); // xoá lại giỏ hàng
        unset($_SESSION['chuyen_size']);
        GuiMail();
        if (!empty($email)) {
            GuiMailKhachHang($email, $_POST['tenkh'], $orderCode, $token, $total);
        }
        
        $_SESSION['swal_success'] = 'Đơn hàng đã đặt thành công!';
        header('Location: giohang.php');
        exit;
    }

    // --- Lấy thông tin mặc định để điền sẵn vào form ---
    $default_name = '';
    $default_phone = '';
    $default_address = '';
    $default_email = '';

    if (!empty($_SESSION['makh'])) {
        $makh_val_prefill = intval($_SESSION['makh']);
        
        // Luôn lấy email từ thông tin tài khoản
        $customer_query = mysqli_query($con, "SELECT * FROM `tbl_tkkhachhang` WHERE `makh` = $makh_val_prefill LIMIT 1");
        if ($customer_query && mysqli_num_rows($customer_query) > 0) {
            $customer_row = mysqli_fetch_assoc($customer_query);
            $default_name = $customer_row['hoten'] ?? '';
            $default_phone = $customer_row['sdt'] ?? '';
            $default_address = $customer_row['diachi'] ?? '';
            $default_email = $customer_row['email'] ?? '';
        }
        
        // Ưu tiên lấy từ sổ địa chỉ (địa chỉ mặc định) nếu có
        $address_query = mysqli_query($con, "SELECT * FROM `tbl_diachi` WHERE `makh` = $makh_val_prefill ORDER BY `macdinh` DESC, `ngaytao` DESC LIMIT 1");
        if ($address_query && mysqli_num_rows($address_query) > 0) {
            $address_row = mysqli_fetch_assoc($address_query);
            $default_name = $address_row['hoten'];
            $default_phone = $address_row['sdt'];
            
            $addr_parts = [];
            if (!empty($address_row['diachi_cuthe'])) $addr_parts[] = $address_row['diachi_cuthe'];
            if (!empty($address_row['phuong_xa'])) $addr_parts[] = $address_row['phuong_xa'];
            if (!empty($address_row['quan_huyen'])) $addr_parts[] = $address_row['quan_huyen'];
            if (!empty($address_row['tinh'])) $addr_parts[] = $address_row['tinh'];
            
            $default_address = implode(', ', $addr_parts);
        }
    }

    include BASE_PATH . 'includes/header.php';
?>

<main class="page landing-page">
    <style>
        .page.landing-page {
            padding-top: clamp(90px, 14vw, 120px);
            min-height: 80vh;
            background: #f8f9fa;
            padding-bottom: clamp(30px, 5vw, 50px);
        }
        .page.landing-page .block-heading h2 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: clamp(0.5px, 0.3vw, 1px);
            font-size: clamp(1.2rem, 3.5vw, 1.8rem);
            color: #0f172a;
        }
        .page.landing-page .block-heading p {
            font-size: clamp(0.85rem, 2vw, 1rem);
            color: #64748b;
        }
        .page.landing-page .card {
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .page.landing-page .card-header {
            background: #fff;
            border-bottom: none;
        }
        .page.landing-page .card-title {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: clamp(1rem, 2.5vw, 1.3rem);
            color: #0f172a;
        }
        .page.landing-page .form-control {
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            padding: 12px 16px;
            font-size: clamp(0.88rem, 2vw, 0.95rem);
            min-height: 48px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .page.landing-page .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .page.landing-page .font-weight-bold {
            font-size: clamp(0.82rem, 1.8vw, 0.9rem);
        }
        .page.landing-page .btn-info {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border: none;
            border-radius: 10px;
            min-height: 48px;
            font-size: clamp(0.85rem, 2vw, 1.1rem) !important;
            padding: 12px 24px !important;
            font-weight: 700 !important;
            transition: all 0.3s ease;
        }
        @media (hover: hover) {
            .page.landing-page .btn-info:hover {
                background: linear-gradient(135deg, #2563eb, #1d4ed8);
                transform: translateY(-1px);
                box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
            }
        }
        .page.landing-page .btn-outline-secondary {
            min-height: 48px;
            border-radius: 10px;
            font-size: clamp(0.82rem, 2vw, 0.95rem);
            padding: 10px 20px;
        }
        /* Responsive: stack buttons on small screens */
        @media (max-width: 575.98px) {
            .page.landing-page .d-flex.justify-content-between {
                flex-direction: column-reverse;
                gap: 12px;
            }
            .page.landing-page .d-flex.justify-content-between .btn,
            .page.landing-page .d-flex.justify-content-between button {
                width: 100%;
                text-align: center;
                justify-content: center;
            }
        }
        /* Order summary responsive */
        .page.landing-page .list-group-item h6 {
            font-size: clamp(0.82rem, 2vw, 0.95rem);
            word-break: break-word;
        }
        .page.landing-page .list-group-item small {
            font-size: clamp(0.72rem, 1.5vw, 0.82rem);
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
                                    <input name="tenkh" required class="form-control" type="text" placeholder="Nhập đầy đủ họ tên" value="<?= htmlspecialchars($default_name) ?>"/>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Số điện thoại <span class="text-danger">*</span></label>
                                    <input name="sdt" required class="form-control" type="text" placeholder="09xxxxxxx" value="<?= htmlspecialchars($default_phone) ?>"/>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Email nhận thông báo <span class="text-danger">*</span></label>
                                    <input name="email" required class="form-control" type="email" placeholder="example@gmail.com" value="<?= htmlspecialchars($default_email) ?>"/>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Địa chỉ chi tiết <span class="text-danger">*</span></label>
                                    <textarea name="diachi" required class="form-control" rows="2" placeholder="Số nhà, Phường/Xã, Quận/Huyện..."><?= htmlspecialchars($default_address) ?></textarea>
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
                                    <span class="text-muted"><?= number_format($sp['giasanpham'] * $sp['qty_in_cart'], 0, ',', '.') ?>&nbsp;đ</span>
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
                                <strong style="font-size: 24px"><?= number_format($total, 0, ',', '.') ?>&nbsp;đ</strong>
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
