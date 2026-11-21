<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'includes/sepay.php';

// IPN: SePay gọi từ máy chủ (POST JSON), không có session/CSRF; xác thực bằng header X-Secret-Key (xem csrf_bootstrap).
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit;
}
if (!sepay_is_configured()) {
    http_response_code(503);
    exit;
}
if (!sepay_verify_ipn()) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit;
}
$data = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false]);
    exit;
}

include BASE_PATH . 'includes/connect.php';
$result = sepay_handle_ipn($con, $data);
$invoice = is_array($data['order'] ?? null) ? (string)($data['order']['order_invoice_number'] ?? '') : '';
error_log('SEPAY_IPN: invoice=' . preg_replace('/[^A-Za-z0-9_-]/', '', $invoice) . ' result=' . $result);
// Đã xử lý xong (kể cả lệch tiền/đơn lạ, đã ghi log cho admin) thì báo nhận để SePay không gửi lại.
echo json_encode(['success' => true]);
