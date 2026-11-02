<?php
/**
 * API: Cập nhật thông tin cá nhân
 * Method: POST
 * Trả về JSON
 */
require_once dirname(__DIR__) . '/config.php';
init_secure_session();
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
header('Content-Type: application/json; charset=utf-8');

include BASE_PATH . 'includes/connect.php';

// Check auth & IDOR protection: user ID strictly from server session
if (!is_user_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method không hợp lệ']);
    exit;
}

// CSRF Protection
verify_csrf_or_abort();

$makh = get_current_user_id();

// Lấy dữ liệu
$hoten = trim($_POST['hoten'] ?? '');
$email = trim($_POST['email'] ?? '');
$sdt = trim($_POST['sdt'] ?? '');
$ngaysinh = trim($_POST['ngaysinh'] ?? '');
$gioitinh = trim($_POST['gioitinh'] ?? '');

// === VALIDATE ===
$errors = [];

if (empty($hoten)) {
    $errors[] = 'Họ tên không được để trống';
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email không đúng định dạng';
}

if (!empty($sdt) && !preg_match('/^(0|\+84)[0-9]{9,10}$/', $sdt)) {
    $errors[] = 'Số điện thoại không hợp lệ (10-11 số, bắt đầu bằng 0 hoặc +84)';
}

if (!empty($gioitinh) && !in_array($gioitinh, ['Nam', 'Nữ', 'Khác'])) {
    $errors[] = 'Giới tính không hợp lệ';
}

if (!empty($ngaysinh)) {
    $d = DateTime::createFromFormat('Y-m-d', $ngaysinh);
    if (!$d || $d->format('Y-m-d') !== $ngaysinh) {
        $errors[] = 'Ngày sinh không hợp lệ';
    }
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// === UPDATE ===
$hoten_safe = mysqli_real_escape_string($con, $hoten);
$email_safe = mysqli_real_escape_string($con, $email);
$sdt_safe = mysqli_real_escape_string($con, $sdt);
$ngaysinh_safe = !empty($ngaysinh) ? "'" . mysqli_real_escape_string($con, $ngaysinh) . "'" : "NULL";
$gioitinh_safe = !empty($gioitinh) ? "'" . mysqli_real_escape_string($con, $gioitinh) . "'" : "NULL";
$now = time();

$sql = "UPDATE `tbl_tkkhachhang` SET 
    `hoten` = '$hoten_safe',
    `email` = '$email_safe',
    `sdt` = '$sdt_safe',
    `ngaysinh` = $ngaysinh_safe,
    `gioitinh` = $gioitinh_safe,
    `last_updated` = $now
    WHERE `makh` = $makh";

if (mysqli_query($con, $sql)) {
    // Cập nhật session
    $_SESSION['dangnhap'] = $hoten;
    
    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật thông tin thành công!',
        'data' => [
            'hoten' => $hoten,
            'email' => $email,
            'sdt' => $sdt,
            'ngaysinh' => $ngaysinh,
            'gioitinh' => $gioitinh
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . mysqli_error($con)]);
}

mysqli_close($con);
