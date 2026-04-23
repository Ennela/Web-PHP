<?php
require_once dirname(__DIR__) . '/config.php';
session_start();
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once(BASE_PATH . 'vnpay_php/config.php');
include BASE_PATH . 'includes/connect.php'; 
require_once BASE_PATH . 'includes/inventory_helper.php';

// Load the cart from session
$cart = isset($_SESSION['chuyen']) ? $_SESSION['chuyen'] : [];
$cartSizes = isset($_SESSION['chuyen_size']) ? $_SESSION['chuyen_size'] : [];
$total = 0;
$orderProducts = array();

if (!empty($cart)) {
    $ids = implode(",", array_keys($cart));
    if(!empty($ids)) {
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
if(empty($orderProducts)){
    $_SESSION['swal_warning'] = 'Giỏ hàng trống!';
    header('Location: ' . BASE_URL . 'shop/giohang.php');
    exit;
}

if(isset($_GET['action']) && $_GET['action'] == 'submit'){
    $tenkh = $_POST['tenkh'] ?? '';
    $sdt = $_POST['sdt'] ?? '';
    $diachi = $_POST['diachi'] ?? '';
    $note = $_POST['note'] ?? '';
    $email_kh = $_POST['email_kh'] ?? '';

    if ($tenkh=='' || $sdt =='' || $diachi =='' || $email_kh =='') {
        $_SESSION['swal_error'] = 'Vui lòng nhập đầy đủ thông tin giao hàng (bao gồm Email)!';
        header('Location: dathangonline.php');
        exit;
    }

    // Lưu email khách vào session để dùng ở vnpay_return.php
    $_SESSION['email_kh'] = $email_kh;
    $_SESSION['tenkh_order'] = $tenkh;

    // Validate + trừ tồn kho trong 1 transaction (chống race condition)
    $stockCheckItems = [];
    foreach ($orderProducts as $sp) {
        $stockCheckItems[] = [
            'masp' => $sp['masp'],
            'size' => $sp['order_size'] ?? 0,
            'quantity' => $sp['qty_in_cart']
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

    $makh_val = !empty($_SESSION['makh']) ? intval($_SESSION['makh']) : 'NULL';
    require_once BASE_PATH . 'includes/order_helper.php';
    $orderCode = generateOrderCode($con);
    $insertOrder = mysqli_query($con, "INSERT INTO `oder` (`id`, `order_code`, `tenkh`, `sdt`, `diachi`, `note`, `tongtien`, `ngaytao`, `status`, `payment_status`, `makh`) VALUES (NULL, '$orderCode', '$tenkh', '$sdt', '$diachi', '$note', '$total', '" . time() . "', 'PENDING', 'UNPAID', $makh_val)");
    $orderID = $con->insert_id;

    $insertString = "";
    foreach ($orderProducts as $key => $sp) {
        $sizeVal = !empty($sp['order_size']) ? "'" . (int)$sp['order_size'] . "'" : "NULL";
        $insertString .= "(NULL, '$orderID', '{$sp['masp']}', '{$sp['qty_in_cart']}', $sizeVal, '{$sp['giasanpham']}', '" . time() . "', '" . time() . "')";
        if ($key != count($orderProducts) - 1) {
            $insertString .= ",";
        }
    }
    mysqli_query($con, "INSERT INTO `oder_chitiet` (`id`, `madonhang`, `masp`, `quantity`, `size`, `price`, `created_time`, `last_updated`) VALUES " . $insertString . ";");

    // Tồn kho đã được trừ trong validateAndDeductStock() ở trên
    unset($_SESSION['giohang']); 
    unset($_SESSION['chuyen']);
    unset($_SESSION['chuyen_size']);

    // Generate VNPAY URL
    $vnp_TxnRef = $orderID;
    $vnp_OrderInfo = "Thanh_toan_don_hang_" . $orderID; 
    $vnp_OrderType = "billpayment";
    $vnp_Amount = (int)($total * 100);
    $vnp_Locale = "vn";
    $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];

    $inputData = array(
        "vnp_Version" => "2.1.0",
        "vnp_TmnCode" => $vnp_TmnCode,
        "vnp_Amount" => $vnp_Amount,
        "vnp_Command" => "pay",
        "vnp_CreateDate" => date('YmdHis'),
        "vnp_CurrCode" => "VND",
        "vnp_IpAddr" => $vnp_IpAddr,
        "vnp_Locale" => $vnp_Locale,
        "vnp_OrderInfo" => $vnp_OrderInfo,
        "vnp_OrderType" => $vnp_OrderType,
        "vnp_ReturnUrl" => $vnp_Returnurl,
        "vnp_TxnRef" => $vnp_TxnRef,
        "vnp_ExpireDate" => $expire
    );

    // Loại bỏ các trường rỗng
    $inputData = array_filter($inputData, function($val) {
        return $val !== "" && $val !== null;
    });

    ksort($inputData);
    $query_string = "";
    $i = 0;
    $hashdata = "";

    function hashUrlEncode($value) {
        return urlencode($value);
    }

    foreach ($inputData as $key => $value) {
        if ($i == 1) {
            $hashdata .= '&' . hashUrlEncode($key) . "=" . hashUrlEncode($value);
        } else {
            $hashdata .= hashUrlEncode($key) . "=" . hashUrlEncode($value);
            $i = 1;
        }
        $query_string .= urlencode($key) . "=" . urlencode($value) . '&';
    }

    $vnp_Url = $vnp_Url . "?" . $query_string;
    if (isset($vnp_HashSecret)) {
        $vnpSecureHash = hash_hmac('sha512', $hashdata, trim($vnp_HashSecret));
        $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
    }

    header('Location: ' . $vnp_Url);
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
                <h2 class="text-info font-weight-bold">Thanh toán Đơn hàng VNPAY</h2>
                <p>Nhập thông tin giao hàng để tiếp tục thanh toán nhanh chóng và bảo mật</p>
            </div>
            
            <div class="row align-items-start">
                <div class="col-md-7 mb-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                            <h4 class="card-title text-info"><i class="fas fa-truck mr-2"></i> Thông tin Nhận hàng</h4>
                        </div>
                        <div class="card-body">
                            <form method="post" action="<?php echo BASE_URL; ?>shop/dathangonline.php?action=submit">
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Họ tên người nhận <span class="text-danger">*</span></label>
                                    <input name="tenkh" required class="form-control" type="text" placeholder="Nhập đầy đủ họ tên" value="<?= htmlspecialchars($default_name) ?>"/>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Số điện thoại <span class="text-danger">*</span></label>
                                    <input name="sdt" required class="form-control" type="text" placeholder="09xxxxxxx" value="<?= htmlspecialchars($default_phone) ?>"/>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Email nhận xác nhận đơn hàng <span class="text-danger">*</span></label>
                                    <input name="email_kh" required class="form-control" type="email" placeholder="example@gmail.com" value="<?= htmlspecialchars($default_email) ?>"/>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Địa chỉ chi tiết <span class="text-danger">*</span></label>
                                    <textarea name="diachi" required class="form-control" rows="2" placeholder="Số nhà, Phường/Xã, Quận/Huyện..."><?= htmlspecialchars($default_address) ?></textarea>
                                </div>
                                <div class="form-group mb-4">
                                    <label class="font-weight-bold">Ghi chú (nếu có)</label>
                                    <textarea name="note" class="form-control" rows="2" placeholder="Ghi chú về thời gian giao hàng..."></textarea>
                                </div>
                                <button type="submit" name="xacnhan" class="btn btn-info btn-lg w-100 font-weight-bold shadow-sm" style="font-size: 1.1rem; padding: 12px;">
                                    Tiến hành Thanh Toán VNPAY <i class="fas fa-arrow-right ml-2"></i>
                                </button>
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
                            <div class="d-flex justify-content-between font-weight-bold p-3 bg-light rounded text-info align-items-center">
                                <span style="font-size: 18px">Tổng cộng</span>
                                <strong style="font-size: 24px"><?= number_format($total, 0, ',', '.') ?>&nbsp;đ</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>


<?php include BASE_PATH . 'includes/footer.php'; ?>

