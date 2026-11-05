<?php
require_once dirname(__DIR__) . '/config.php';
date_default_timezone_set('Asia/Ho_Chi_Minh');

// ─── VNPay Configuration ───
// Merchant credentials are required environment settings, never shared defaults.
$vnp_TmnCode    = trim((string) getenv('VNPAY_TMN_CODE'));
$vnp_HashSecret = trim((string) getenv('VNPAY_HASH_SECRET'));
$vnp_Url        = getenv('VNPAY_URL') ?: 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';
$vnp_Returnurl = BASE_URL . 'shop/vnpay_return.php';

function vnpay_require_configuration(): void
{
    global $vnp_TmnCode, $vnp_HashSecret, $vnp_Url;
    // Exact HTTPS endpoints reject userinfo, nonstandard ports, query strings,
    // fragments and look-alike hosts before stock/orders can be changed.
    $gateways = [
        'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
        'https://pay.vnpay.vn/vpcpay.html',
    ];
    if (!preg_match('/\A[A-Za-z0-9]{4,32}\z/', $vnp_TmnCode)
        || strlen($vnp_HashSecret) < 16
        || preg_match('/[\x00-\x20\x7f]/', $vnp_HashSecret)
        || !in_array($vnp_Url, $gateways, true)) {
        error_log('VNPAY_UNAVAILABLE: merchant configuration is missing or invalid.');
        http_response_code(503);
        header('Cache-Control: no-store');
        header('Content-Type: text/html; charset=utf-8');
        exit('<!doctype html><html lang="vi"><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>Thanh toán tạm thời không khả dụng</title><body>'
            . '<h1>Thanh toán trực tuyến tạm thời không khả dụng.</h1>'
            . '<p>Vui lòng thử lại sau hoặc chọn thanh toán khi nhận hàng (COD).</p></body></html>');
    }
}

// Cấu hình thời gian hết hạn đơn hàng (15 phút)
$startTime = date("YmdHis");
$expire = date('YmdHis', strtotime('+15 minutes'));
