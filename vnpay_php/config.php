<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

// ─── VNPay Configuration ───
// Read from environment variables (Railway), fallback to sandbox defaults
$vnp_TmnCode    = getenv('VNPAY_TMN_CODE')    ?: "GMVE96ZZ"; 
$vnp_HashSecret = getenv('VNPAY_HASH_SECRET') ?: "SZDL8D5VSU4HODQFQ0WQO6VECPHZZKTF"; 
$vnp_Url        = getenv('VNPAY_URL')         ?: "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";

if (defined('BASE_URL')) {
    $vnp_Returnurl = BASE_URL . "shop/vnpay_return.php";
} else {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        $protocol = "https";
    }
    $vnp_Returnurl = "$protocol://$_SERVER[HTTP_HOST]/shop/vnpay_return.php";
}

// API (tạm thời chưa dùng tới)
$vnp_apiUrl = "http://sandbox.vnpayment.vn/merchant_webapi/merchant.html";

// Cấu hình thời gian hết hạn đơn hàng (15 phút)
$startTime = date("YmdHis");
$expire = date('YmdHis', strtotime('+15 minutes'));
?>