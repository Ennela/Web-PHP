<?php
/**
 * API: Cập nhật thông tin cá nhân
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

// Check auth
if (empty($_SESSION['dangnhap']) || empty($_SESSION['makh'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']);
    exit;
}

$makh = intval($_SESSION['makh']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method không hợp lệ']);
    exit;
}

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

if (!empty($gioitinh) && !in_array($gioitinh, ['Nam', 'Nữ', 'Khác'], true)) {
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

// === ENCRYPT SENSITIVE PII & UPDATE VIA PREPARED STATEMENT ===
$sdt_enc = $sdt !== '' ? (string)app_encrypt($sdt, 'tbl_tkkhachhang.sdt') : '';
$ngaysinh_val = $ngaysinh !== '' ? $ngaysinh : null;
$gioitinh_val = $gioitinh !== '' ? $gioitinh : null;
$now = time();

$stmt = mysqli_prepare(
    $con,
    "UPDATE `tbl_tkkhachhang` SET `hoten` = ?, `email` = ?, `sdt` = ?, `ngaysinh` = ?, `gioitinh` = ?, `last_updated` = ? WHERE `makh` = ?"
);
mysqli_stmt_bind_param($stmt, "sssssii", $hoten, $email, $sdt_enc, $ngaysinh_val, $gioitinh_val, $now, $makh);
$updated = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($updated) {
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
    echo json_encode(['success' => false, 'message' => 'Không thể xử lý yêu cầu. Vui lòng thử lại sau.']);
}

mysqli_close($con);
