<?php
/**
 * API: Đặt lại mật khẩu mới (từ link email)
 * Method: POST
 * Params: token, matkhau_moi, matkhau_xacnhan
 * Trả về JSON
 */
require_once dirname(__DIR__) . '/config.php';
session_start();
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
header('Content-Type: application/json; charset=utf-8');

include BASE_PATH . 'includes/connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method không hợp lệ']);
    exit;
}

$token       = trim($_POST['token'] ?? '');
$mk_moi      = $_POST['matkhau_moi'] ?? '';
$mk_xacnhan  = $_POST['matkhau_xacnhan'] ?? '';

// ── Validate ──
$errors = [];

if (empty($token) || strlen($token) !== 64) {
    echo json_encode(['success' => false, 'message' => 'Token không hợp lệ']);
    exit;
}
if (empty($mk_moi)) {
    $errors[] = 'Mật khẩu mới không được để trống';
}
if (strlen($mk_moi) < 6) {
    $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự';
}
if ($mk_moi !== $mk_xacnhan) {
    $errors[] = 'Mật khẩu xác nhận không khớp';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// ── Validate token trong DB ──
$tokenSafe = mysqli_real_escape_string($con, $token);
$query = mysqli_query($con, "SELECT `makh`, `hoten` FROM `tbl_tkkhachhang` WHERE `reset_token` = '$tokenSafe' AND `reset_token_expiry` >= " . time() . " LIMIT 1");
$user = mysqli_fetch_assoc($query);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu lại.']);
    exit;
}

// ── Update mật khẩu & xóa token ──
$makh = intval($user['makh']);
$mk_moi_safe = mysqli_real_escape_string($con, $mk_moi);
$now = time();

$sql = "UPDATE `tbl_tkkhachhang` SET `password` = '$mk_moi_safe', `reset_token` = NULL, `reset_token_expiry` = NULL, `last_updated` = $now WHERE `makh` = $makh";

if (mysqli_query($con, $sql)) {
    $_SESSION['swal_success'] = 'Đặt lại mật khẩu thành công! Vui lòng đăng nhập bằng mật khẩu mới.';
    echo json_encode(['success' => true, 'message' => 'Đặt lại mật khẩu thành công!', 'redirect' => BASE_URL . 'auth/dangnhap.php']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . mysqli_error($con)]);
}

mysqli_close($con);
