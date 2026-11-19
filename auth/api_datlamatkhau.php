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
$errors = [];

if (empty($token) || strlen($token) !== 64 || !ctype_xdigit($token)) {
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

// ── Validate token trong DB (băm SHA-256 + prepared statement) ──
$tokenHash = app_hash_token($token);
$now = time();
$stmtCheck = mysqli_prepare($con, "SELECT `makh`, `hoten` FROM `tbl_tkkhachhang` WHERE (`reset_token` = ? OR `reset_token` = ?) AND `reset_token_expiry` >= ? LIMIT 1");
mysqli_stmt_bind_param($stmtCheck, "ssi", $tokenHash, $token, $now);
mysqli_stmt_execute($stmtCheck);
$resCheck = mysqli_stmt_get_result($stmtCheck);
$user = mysqli_fetch_assoc($resCheck);
mysqli_stmt_close($stmtCheck);

if (!$user) {
    auth_throttle_record_failure($con, 'reset_token', auth_client_ip());
    echo json_encode(['success' => false, 'message' => 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu lại.']);
    exit;
}

// ── Update mật khẩu bằng Argon2id/Bcrypt hash & xóa token ──
$makh = intval($user['makh']);
$hashedPassword = app_hash_password($mk_moi);

$stmtUpdate = mysqli_prepare($con, "UPDATE `tbl_tkkhachhang` SET `password` = ?, `reset_token` = NULL, `reset_token_expiry` = NULL, `last_updated` = ? WHERE `makh` = ?");
mysqli_stmt_bind_param($stmtUpdate, "sii", $hashedPassword, $now, $makh);
$updated = mysqli_stmt_execute($stmtUpdate);
mysqli_stmt_close($stmtUpdate);

if ($updated) {
    // Hủy mọi phiên đăng nhập hiện có trên trình duyệt này và mở khóa brute-force cho tài khoản
    $uStmt = mysqli_prepare($con, 'SELECT username FROM 	bl_tkkhachhang WHERE makh = ?');
    mysqli_stmt_bind_param($uStmt, 'i', $makh);
    mysqli_stmt_execute($uStmt);
    $u = mysqli_fetch_assoc(mysqli_stmt_get_result($uStmt));
    mysqli_stmt_close($uStmt);
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
