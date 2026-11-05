<?php
/**
 * API: Upload avatar
 * Method: POST (multipart/form-data)
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

if (!isset($_FILES['avatar']) || !is_array($_FILES['avatar'])) {
    app_fail(422, 'Không tìm thấy file ảnh tải lên.');
}

require_once BASE_PATH . 'includes/upload_helper.php';
$uploadDir = BASE_PATH . 'auth/uploads/avatars/';
try {
    $dbPath = app_store_image_upload($_FILES['avatar'], $uploadDir, 'auth/uploads/avatars', 2 * 1024 * 1024);
} catch (InvalidArgumentException | RuntimeException $error) {
    app_fail(422, $error->getMessage());
}

$oldQuery = mysqli_query($con, "SELECT `avatar` FROM `tbl_tkkhachhang` WHERE `makh` = $makh");
$oldRow = mysqli_fetch_assoc($oldQuery);
$now = time();
$update = mysqli_prepare($con, "UPDATE `tbl_tkkhachhang` SET `avatar` = ?, `last_updated` = ? WHERE `makh` = ?");
mysqli_stmt_bind_param($update, 'sii', $dbPath, $now, $makh);
mysqli_stmt_execute($update);
mysqli_stmt_close($update);

// Delete only an old file directly inside the avatar folder, never a DB-supplied path elsewhere.
if (!empty($oldRow['avatar']) && is_string($oldRow['avatar'])) {
    $oldPath = realpath(BASE_PATH . $oldRow['avatar']);
    $avatarDirectory = realpath($uploadDir);
    if ($oldPath !== false && $avatarDirectory !== false
        && dirname($oldPath) === $avatarDirectory && is_file($oldPath)) {
        @unlink($oldPath);
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Cập nhật avatar thành công!',
    'avatar_url' => BASE_URL . $dbPath
]);
mysqli_close($con);
