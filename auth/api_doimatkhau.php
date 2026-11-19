<?php
/**
 * API: Đổi mật khẩu
 * Method: POST
 * Trả về JSON
 */
require_once dirname(__DIR__) . '/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
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

// Kiểm tra mật khẩu hiện tại bằng prepared statement
$stmt = mysqli_prepare($con, "SELECT `password` FROM `tbl_tkkhachhang` WHERE `makh` = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $makh);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

$currentPasswordMatch = false;
if ($user) {
    if (app_verify_password($mk_hientai, $user['password'] ?? '')) {
        $currentPasswordMatch = true;
    }
}

if (!$currentPasswordMatch) {
    echo json_encode(['success' => false, 'message' => 'Mật khẩu hiện tại không đúng']);
    exit;
}

// Update mật khẩu mới bằng bcrypt hash
$hashedPassword = app_hash_password($mk_moi);
$now = time();
$updateStmt = mysqli_prepare($con, "UPDATE `tbl_tkkhachhang` SET `password` = ?, `last_updated` = ? WHERE `makh` = ?");
mysqli_stmt_bind_param($updateStmt, "sii", $hashedPassword, $now, $makh);

if (mysqli_stmt_execute($updateStmt)) {
    mysqli_stmt_close($updateStmt);
    echo json_encode(['success' => true, 'message' => 'Đổi mật khẩu thành công!']);
} else {
    mysqli_stmt_close($updateStmt);
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống. Vui lòng thử lại sau.']);
}

mysqli_close($con);
