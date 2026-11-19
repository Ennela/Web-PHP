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
    $_SESSION['swal_success'] = 'Đặt lại mật khẩu thành công! Vui lòng đăng nhập bằng mật khẩu mới.';
    echo json_encode(['success' => true, 'message' => 'Đặt lại mật khẩu thành công!', 'redirect' => BASE_URL . 'auth/dangnhap.php']);
} else {
    echo json_encode(['success' => false, 'message' => 'Không thể xử lý yêu cầu. Vui lòng thử lại sau.']);
}

mysqli_close($con);
