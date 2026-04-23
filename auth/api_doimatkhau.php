<?php
/**
 * API: Đổi mật khẩu
 * Method: POST
 * Trả về JSON
 */
require_once dirname(__DIR__) . '/config.php';
session_start();
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
header('Content-Type: application/json; charset=utf-8');

include BASE_PATH . 'includes/connect.php';

if (empty($_SESSION['dangnhap']) || empty($_SESSION['makh'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']);
    exit;
}

$makh = intval($_SESSION['makh']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method không hợp lệ']);
    exit;
}

$mk_hientai = $_POST['matkhau_hientai'] ?? '';
$mk_moi = $_POST['matkhau_moi'] ?? '';
$mk_xacnhan = $_POST['matkhau_xacnhan'] ?? '';

// Validate
$errors = [];

if (empty($mk_hientai)) {
    $errors[] = 'Mật khẩu hiện tại không được để trống';
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
if ($mk_moi === $mk_hientai) {
    $errors[] = 'Mật khẩu mới phải khác mật khẩu hiện tại';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Kiểm tra mật khẩu hiện tại
$query = mysqli_query($con, "SELECT `password` FROM `tbl_tkkhachhang` WHERE `makh` = $makh LIMIT 1");
$user = mysqli_fetch_assoc($query);

if (!$user || $user['password'] !== $mk_hientai) {
    echo json_encode(['success' => false, 'message' => 'Mật khẩu hiện tại không đúng']);
    exit;
}

// Update mật khẩu
$mk_moi_safe = mysqli_real_escape_string($con, $mk_moi);
$now = time();
$sql = "UPDATE `tbl_tkkhachhang` SET `password` = '$mk_moi_safe', `last_updated` = $now WHERE `makh` = $makh";

if (mysqli_query($con, $sql)) {
    echo json_encode(['success' => true, 'message' => 'Đổi mật khẩu thành công!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . mysqli_error($con)]);
}

mysqli_close($con);
