<?php
/**
 * API: Đặt lại mật khẩu mới (từ link email)
 * Method: POST
 * Params: token, matkhau_moi, matkhau_xacnhan
 * Trả về JSON
 */
require_once dirname(__DIR__) . '/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
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
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    echo json_encode(['success' => false, 'message' => 'Token không hợp lệ']);
    exit;
}

// Chống dò token: tối đa 10 lần thử sai / IP / 15 phút
$lock = auth_throttle_lock_seconds($con, 'reset_token', auth_client_ip(), 10);
if ($lock > 0) {
    echo json_encode(['success' => false, 'message' => auth_lock_message($lock)]);
    exit;
}

$errors = auth_password_policy_errors($mk_moi);
if ($mk_moi !== $mk_xacnhan) {
    $errors[] = 'Mật khẩu xác nhận không khớp';
}
if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// ── Validate token trong DB (DB chỉ lưu SHA-256 của token) ──
$tokenHash = hash('sha256', $token);
$now = time();
$stmt = mysqli_prepare($con, "SELECT `makh`, `hoten` FROM `tbl_tkkhachhang` WHERE `reset_token` = ? AND `reset_token_expiry` >= ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'si', $tokenHash, $now);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$user) {
    auth_throttle_record_failure($con, 'reset_token', auth_client_ip());
    echo json_encode(['success' => false, 'message' => 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu lại.']);
    exit;
}

// ── Lưu mật khẩu mới dạng bcrypt & hủy token (dùng 1 lần) ──
$makh = intval($user['makh']);
$hashedPassword = password_hash($mk_moi, PASSWORD_BCRYPT);
$stmt = mysqli_prepare($con, "UPDATE `tbl_tkkhachhang` SET `password` = ?, `reset_token` = NULL, `reset_token_expiry` = NULL, `last_updated` = ? WHERE `makh` = ?");
mysqli_stmt_bind_param($stmt, 'sii', $hashedPassword, $now, $makh);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($ok) {
    // Đặt lại mật khẩu xong: hủy mọi phiên đăng nhập đang có trên trình duyệt này
    // và mở khóa brute-force cho tài khoản
    $usernameStmt = mysqli_prepare($con, "SELECT `username` FROM `tbl_tkkhachhang` WHERE `makh` = ?");
    mysqli_stmt_bind_param($usernameStmt, 'i', $makh);
    mysqli_stmt_execute($usernameStmt);
    $u = mysqli_fetch_assoc(mysqli_stmt_get_result($usernameStmt));
    mysqli_stmt_close($usernameStmt);
    if (!empty($u['username'])) {
        auth_throttle_clear($con, 'customer', $u['username']);
    }

    auth_restart_session();
    $_SESSION['swal_success'] = 'Đặt lại mật khẩu thành công! Vui lòng đăng nhập bằng mật khẩu mới.';
    echo json_encode(['success' => true, 'message' => 'Đặt lại mật khẩu thành công!', 'redirect' => BASE_URL . 'auth/dangnhap.php']);
} else {
    echo json_encode(['success' => false, 'message' => 'Không thể xử lý yêu cầu. Vui lòng thử lại sau.']);
}

mysqli_close($con);
