<?php
/**
 * API: Quên mật khẩu — Tạo token & gửi email reset
 * Method: POST
 * Params: email_or_username
 * Trả về JSON
 */
require_once dirname(__DIR__) . '/config.php';
session_start();
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
header('Content-Type: application/json; charset=utf-8');

include BASE_PATH . 'includes/connect.php';
include_once BASE_PATH . 'includes/mail_helper.php';
include_once BASE_PATH . 'includes/email_templates.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method không hợp lệ']);
    exit;
}

$input = trim($_POST['email_or_username'] ?? '');

if (empty($input)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập email hoặc tên đăng nhập']);
    exit;
}

// Tìm user bằng email hoặc username
$inputSafe = mysqli_real_escape_string($con, $input);
$query = mysqli_query($con, "SELECT `makh`, `hoten`, `email`, `username` FROM `tbl_tkkhachhang` WHERE `email` = '$inputSafe' OR `username` = '$inputSafe' LIMIT 1");
$user = mysqli_fetch_assoc($query);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Email hoặc tên đăng nhập không tồn tại trong hệ thống. Vui lòng kiểm tra lại hoặc đăng ký tài khoản mới.']);
    exit;
}

// Kiểm tra user có email không
if (empty($user['email'])) {
    echo json_encode(['success' => false, 'message' => 'Tài khoản chưa cập nhật email. Vui lòng liên hệ admin để được hỗ trợ.']);
    exit;
}

// Tạo token bảo mật
$token = bin2hex(random_bytes(32)); // 64 ký tự hex
$expiry = time() + 1800; // 30 phút

// Lưu token vào DB
$makh = intval($user['makh']);
$sql = "UPDATE `tbl_tkkhachhang` SET `reset_token` = '$token', `reset_token_expiry` = $expiry WHERE `makh` = $makh";
if (!mysqli_query($con, $sql)) {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống. Vui lòng thử lại sau.']);
    exit;
}

// Tạo link reset
$resetLink = BASE_URL . 'auth/datlamatkhau.php?token=' . $token;

// Dùng template chuẩn từ email_templates.php
$hoTen = $user['hoten'] ?? $user['username'];
$emailData = getPasswordResetTemplate([
    'customerName'  => $hoTen,
    'resetLink'     => $resetLink,
    'expiryMinutes' => 30,
]);

// Gửi email qua Mailjet
$result = sendTransactionalEmail($user['email'], $hoTen, $emailData['subject'], $emailData['html']);

if (!$result['success']) {
    error_log("FORGOT_PASSWORD: Failed to send email to {$user['email']} for makh=$makh. Error: " . ($result['error'] ?? 'unknown'));
}

echo json_encode(['success' => true, 'message' => 'Mình đã gửi link đặt lại mật khẩu đến ' . substr($user['email'], 0, 3) . '***. Kiểm tra hộp thư (kể cả Spam) nhé.']);
mysqli_close($con);
