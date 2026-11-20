<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/momo.php';

// IPN: MoMo gọi từ máy chủ (POST JSON), không có session/CSRF; chữ ký thay thế (xem csrf_bootstrap).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit;
}
if (!momo_is_configured()) {
    http_response_code(503);
    exit;
}
$data = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($data) || !momo_verify($data)) {
    http_response_code(400);
    exit;
}

include BASE_PATH . 'includes/connect.php';
$result = momo_finalize($con, $data);
error_log('MOMO_IPN: order=' . momo_db_order_id($data['orderId'] ?? '') . ' result=' . $result);
// MoMo chỉ cần HTTP 204; 'invalid' (sai số tiền/đơn) trả 400 để MoMo không coi là đã nhận.
http_response_code($result === 'invalid' ? 400 : 204);
