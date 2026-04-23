<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once("./config.php");
// Fix connection include path to root connect.php
include '../connect.php'; 

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
    echo "<script>alert('Giỏ hàng trống!'); window.location.href='../giohang.php';</script>";
    exit;
}

if(isset($_GET['action']) && $_GET['action'] == 'submit'){
    $tenkh = $_POST['tenkh'] ?? '';
    $sdt = $_POST['sdt'] ?? '';
    $diachi = $_POST['diachi'] ?? '';
    $note = $_POST['note'] ?? '';

    if ($tenkh=='' || $sdt =='' || $diachi =='') {
        echo "<script>alert('Vui lòng nhập đầy đủ thông tin giao hàng!'); window.location.href= 'dathangonline.php';</script>";
        exit;
    }

    // Insert Order
    require_once dirname(__DIR__) . '/includes/order_helper.php';
    $orderCode = generateOrderCode($con);
    $insertOrder = mysqli_query($con, "INSERT INTO `oder` (`id`, `order_code`, `tenkh`, `sdt`, `diachi`, `note`, `tongtien`, `ngaytao`, `status`) VALUES (NULL, '$orderCode', '$tenkh', '$sdt', '$diachi', '$note', '$total', '" . time() . "', 'PENDING')");
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

    unset($_SESSION['giohang']); 
    unset($_SESSION['chuyen']);
    unset($_SESSION['chuyen_size']);

    // Generate VNPAY URL
    $vnp_TxnRef = $orderID;
    $vnp_OrderInfo = "Thanh_toan_don_hang_" . $orderID; 
    $vnp_OrderType = "billpayment";
    $vnp_Amount = (int)($total * 100);
    $vnp_Locale = "vn";
    $vnp_IpAddr = "127.0.0.1";

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

    // Loại bỏ các trường rỗng để không bị lỗi chữ ký
    $inputData = array_filter($inputData, function($val) {
        return $val !== "" && $val !== null;
    });

    ksort($inputData);
    $query_string = "";
    $i = 0;
    $hashdata = "";
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
        $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
        $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
    }

    // Redirect chuẩn back-end
    header('Location: ' . $vnp_Url);
    exit;
}

function hashUrlEncode($value) {
    return urlencode($value);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thanh Toán Online VNPAY</title>
    <!-- Modern Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .vnpay-gradient {
            background: linear-gradient(135deg, #00509E 0%, #0072bc 100%);
        }
        .animate-fade-in { animation: fadeIn 0.5s ease-out forwards; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="min-h-screen text-gray-800">
    <!-- Navbar placeholder -->
    <nav class="vnpay-gradient shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <span class="font-bold text-white text-xl tracking-tight">Cửa Hàng Thể Thao</span>
                </div>
                <div>
                    <a href="../giohang.php" class="text-white hover:text-blue-100 font-medium px-3 py-2 rounded-md text-sm transition-colors duration-200">
                        Quay lại giỏ hàng
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 animate-fade-in">
        
        <div class="flex flex-col md:flex-row gap-8">
            
            <!-- Thông tin giao hàng -->
            <div class="w-full md:w-7/12">
                <div class="glass-panel shadow-xl rounded-2xl overflow-hidden">
                    <div class="px-6 py-6 border-b border-gray-100">
                        <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            Thông tin giao hàng
                        </h2>
                    </div>

                    <form method="post" action="dathangonline.php?action=submit" class="p-6">
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Họ tên người nhận <span class="text-red-500">*</span></label>
                                <input name="tenkh" required class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all duration-200 bg-white" type="text" placeholder="Nhập họ và tên đầy đủ"/>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại <span class="text-red-500">*</span></label>
                                <input name="sdt" required class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all duration-200 bg-white" type="text" placeholder="Ví dụ: 0901234567"/>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ nhận hàng <span class="text-red-500">*</span></label>
                                <input name="diachi" required class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all duration-200 bg-white" type="text" placeholder="Số nhà, Tên đường, Xã/Phường, Quận/Huyện, Tỉnh/Thành phố"/>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú thêm</label>
                                <textarea name="note" rows="3" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all duration-200 bg-white" placeholder="Ghi chú về thời gian móng muốn nhận hàng..."></textarea>
                            </div>
                            
                            <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                                <span class="flex items-center text-sm text-gray-500">
                                    <svg class="w-4 h-4 mr-1 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                    Thanh toán an toàn với VNPAY
                                </span>
                                <button type="submit" name="xacnhan" class="vnpay-gradient text-white px-8 py-3 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transform transition-all duration-200 font-medium text-lg flex items-center">
                                    Tiến hành Thanh Toán
                                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tóm tắt đơn hàng -->
            <div class="w-full md:w-5/12">
                <div class="glass-panel shadow-xl rounded-2xl overflow-hidden sticky top-8">
                    <div class="px-6 py-6 border-b border-gray-100 bg-gray-50">
                        <h2 class="text-xl font-bold text-gray-800">Tóm tắt đơn hàng</h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="space-y-4 max-h-60 overflow-y-auto mb-6 pr-2">
                            <?php foreach($orderProducts as $sp): ?>
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h4 class="text-sm font-semibold text-gray-800 line-clamp-2"><?= htmlspecialchars($sp['tensp']) ?></h4>
                                    <p class="text-xs text-gray-500 mt-1">Số lượng: <span class="font-medium text-gray-700"><?= $sp['qty_in_cart'] ?></span></p>
                                </div>
                                <div class="ml-4 font-medium text-gray-900">
                                    <?= number_format($sp['giasanpham'] * $sp['qty_in_cart'], 0, ',', '.') ?>đ
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="border-t border-gray-200 pt-4 space-y-3">
                            <div class="flex justify-between text-gray-600">
                                <span>Tạm tính</span>
                                <span><?= number_format($total, 0, ',', '.') ?>đ</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Phí vận chuyển</span>
                                <span class="text-green-600">Miễn phí</span>
                            </div>
                            <div class="flex justify-between items-center pt-3 border-t border-gray-200">
                                <span class="text-lg font-bold text-gray-800">Tổng cộng</span>
                                <span class="text-2xl font-black text-blue-600"><?= number_format($total, 0, ',', '.') ?>đ</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 border-t border-gray-100 flex items-center justify-center space-x-4">
                        <img src="https://vnpay.vn/wp-content/uploads/2020/07/Logo-VNPAYQR-update.png" alt="VNPAY QR" class="h-8 object-contain opacity-80 mix-blend-multiply">
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
