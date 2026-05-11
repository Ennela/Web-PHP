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
    // Bảo mật: không tiết lộ tài khoản có tồn tại hay không
    echo json_encode(['success' => true, 'message' => 'Nếu tài khoản tồn tại, chúng tôi đã gửi email hướng dẫn đặt lại mật khẩu.']);
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

// Tạo nội dung email HTML
$hoTen = htmlspecialchars($user['hoten'] ?? $user['username']);
$htmlBody = '
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="margin:0; padding:0; background:#f1f5f9; font-family: Arial, sans-serif;">
<div style="max-width:520px; margin:40px auto; background:#fff; border-radius:16px; border:1px solid #e2e8f0; box-shadow:0 4px 20px rgba(0,0,0,0.06); overflow:hidden;">
    <!-- Header -->
    <div style="background:linear-gradient(135deg,#3b82f6,#2563eb); padding:32px 24px; text-align:center;">
        <h1 style="color:#fff; margin:0; font-size:22px; font-weight:800; letter-spacing:1px;"> ĐẶT LẠI MẬT KHẨU</h1>
    </div>
    <!-- Body -->
    <div style="padding:32px 24px;">
        <p style="color:#333; font-size:15px; line-height:1.6;">Xin chào <strong>' . $hoTen . '</strong>,</p>
        <p style="color:#555; font-size:14px; line-height:1.6;">Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn tại <strong>Shop Sneakers</strong>.</p>
        <p style="color:#555; font-size:14px; line-height:1.6;">Nhấn vào nút bên dưới để đặt mật khẩu mới:</p>
        <div style="text-align:center; margin:28px 0;">
            <a href="' . $resetLink . '" style="display:inline-block; background:linear-gradient(135deg,#3b82f6,#2563eb); color:#fff; text-decoration:none; padding:14px 36px; border-radius:10px; font-weight:700; font-size:15px; letter-spacing:0.5px;">Đặt lại mật khẩu</a>
        </div>
        <p style="color:#888; font-size:13px; line-height:1.6;"> Link này có hiệu lực trong <strong>30 phút</strong>. Sau thời gian này, bạn cần yêu cầu đặt lại mật khẩu mới.</p>
        <p style="color:#888; font-size:13px; line-height:1.6;">Nếu bạn không yêu cầu đặt lại mật khẩu, hãy bỏ qua email này. Tài khoản của bạn vẫn an toàn.</p>
        <hr style="border:none; border-top:1px solid #e2e8f0; margin:24px 0;">
        <p style="color:#aaa; font-size:12px; text-align:center;">Nếu nút không hoạt động, copy đường link sau vào trình duyệt:<br>
        <a href="' . $resetLink . '" style="color:#3b82f6; word-break:break-all; font-size:11px;">' . $resetLink . '</a></p>
    </div>
    <!-- Footer -->
    <div style="background:#f8fafc; padding:16px 24px; text-align:center; border-top:1px solid #e2e8f0;">
        <p style="color:#999; font-size:12px; margin:0;">© ' . date('Y') . ' Shop Sneakers — Tất cả quyền được bảo lưu</p>
    </div>
</div>
</body>
</html>';

// Gửi email qua Brevo
$sent = sendMailBrevo($user['email'], $hoTen, 'Đặt lại mật khẩu — Shop Sneakers', $htmlBody);

if (!$sent) {
    // Vẫn trả success để không leak thông tin, nhưng log lỗi
    error_log("FORGOT_PASSWORD: Failed to send email to {$user['email']} for makh=$makh");
}

echo json_encode(['success' => true, 'message' => 'Nếu tài khoản tồn tại, chúng tôi đã gửi email hướng dẫn đặt lại mật khẩu. Vui lòng kiểm tra hộp thư (bao gồm thư mục Spam).']);
mysqli_close($con);
