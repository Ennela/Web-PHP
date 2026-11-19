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
$errors = array_merge($errors, auth_password_policy_errors($mk_moi));
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

// Chống dò mật khẩu hiện tại qua chức năng đổi mật khẩu (khóa sau 5 lần sai / 15 phút)
$throttleKey = 'makh:' . $makh;
$lock = auth_throttle_lock_seconds($con, 'change_pw', $throttleKey);
if ($lock > 0) {
    echo json_encode(['success' => false, 'message' => auth_lock_message($lock)]);
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
    auth_throttle_record_failure($con, 'change_pw', $throttleKey);
    echo json_encode(['success' => false, 'message' => 'Mật khẩu hiện tại không đúng']);
    exit;
}
auth_throttle_clear($con, 'change_pw', $throttleKey);

// Update mật khẩu mới bằng bcrypt hash
$hashedPassword = app_hash_password($mk_moi);
$now = time();
$updateStmt = mysqli_prepare($con, "UPDATE `tbl_tkkhachhang` SET `password` = ?, `last_updated` = ? WHERE `makh` = ?");
mysqli_stmt_bind_param($updateStmt, "sii", $hashedPassword, $now, $makh);

if (mysqli_stmt_execute($updateStmt)) {
    mysqli_stmt_close($updateStmt);
    // Đổi mật khẩu = sự kiện nhạy cảm -> cấp session ID mới (ID cũ nếu bị lộ sẽ vô hiệu)
    session_regenerate_id(true);
    echo json_encode(['success' => true, 'message' => 'Đổi mật khẩu thành công!']);
} else {
    mysqli_stmt_close($updateStmt);
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống. Vui lòng thử lại sau.']);
}

mysqli_close($con);
