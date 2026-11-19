<?php
/**
 * API: Quên mật khẩu — Tạo token & gửi email reset
 * Method: POST
 * Params: email_or_username
 * Trả về JSON
 */
require_once dirname(__DIR__) . '/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
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

// Thông báo chung — KHÔNG tiết lộ tài khoản/email có tồn tại hay không (chống dò tài khoản)
$genericResponse = ['success' => true, 'message' => 'Nếu tài khoản tồn tại và đã có email, link đặt lại mật khẩu sẽ được gửi trong vài phút. Kiểm tra hộp thư (kể cả Spam) nhé.'];

// Giới hạn: tối đa 3 yêu cầu / tài khoản / 15 phút (chống spam email, dò tài khoản)
$lock = auth_throttle_lock_seconds($con, 'reset', $input, 3);
if ($lock > 0) {
    echo json_encode(['success' => false, 'message' => 'Bạn đã yêu cầu quá nhiều lần. Vui lòng thử lại sau ' . max(1, (int)ceil($lock / 60)) . ' phút.']);
    exit;
}
auth_throttle_record_failure($con, 'reset', $input);

// Tìm user bằng email hoặc username (prepared statement)
$stmt = mysqli_prepare($con, "SELECT `makh`, `hoten`, `email`, `username` FROM `tbl_tkkhachhang` WHERE `email` = ? OR `username` = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'ss', $input, $input);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$user || empty($user['email'])) {
    echo json_encode($genericResponse);
    exit;
}

// Tạo token bảo mật bằng CSPRNG và chỉ lưu giá trị băm SHA-256 vào DB
$token = bin2hex(random_bytes(32)); // 64 ký tự hex gửi qua link email
$tokenHash = app_hash_token($token);
$expiry = time() + 1800; // 30 phút

// Lưu token hash vào DB
$makh = intval($user['makh']);
$stmtToken = mysqli_prepare($con, "UPDATE `tbl_tkkhachhang` SET `reset_token` = ?, `reset_token_expiry` = ? WHERE `makh` = ?");
mysqli_stmt_bind_param($stmtToken, "sii", $tokenHash, $expiry, $makh);
$saved = mysqli_stmt_execute($stmtToken);
mysqli_stmt_close($stmtToken);
if (!$saved) {
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
    $maskedEmail = app_mask_email($user['email']);
    error_log("FORGOT_PASSWORD: Failed to send email to {$maskedEmail} for makh=$makh. Error: " . ($result['error'] ?? 'unknown'));
}

echo json_encode($genericResponse);
mysqli_close($con);
