<?php
/**
 * API: Upload avatar
 * Method: POST (multipart/form-data)
 * Trả về JSON
 */
require_once dirname(__DIR__) . '/config.php';
init_secure_session();
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
header('Content-Type: application/json; charset=utf-8');

include BASE_PATH . 'includes/connect.php';

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

verify_csrf_or_abort();

$makh = get_current_user_id();

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy file upload']);
    exit;
}

$file = $_FILES['avatar'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$maxSize = 2 * 1024 * 1024; // 2MB

// Validate type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận file JPG, PNG, WebP, GIF']);
    exit;
}

// Validate size
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File quá lớn. Tối đa 2MB']);
    exit;
}

// Tạo thư mục nếu chưa có
$uploadDir = BASE_PATH . 'auth/uploads/avatars/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Tạo tên file unique
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$newName = 'avatar_' . $makh . '_' . time() . '.' . $ext;
$destPath = $uploadDir . $newName;
$dbPath = 'auth/uploads/avatars/' . $newName;

if (move_uploaded_file($file['tmp_name'], $destPath)) {
    // Xóa avatar cũ nếu có
    $oldQuery = mysqli_query($con, "SELECT `avatar` FROM `tbl_tkkhachhang` WHERE `makh` = $makh");
    $oldRow = mysqli_fetch_assoc($oldQuery);
    if (!empty($oldRow['avatar']) && file_exists(BASE_PATH . $oldRow['avatar'])) {
        @unlink(BASE_PATH . $oldRow['avatar']);
    }

    // Update DB
    $dbPath_safe = mysqli_real_escape_string($con, $dbPath);
    $now = time();
    mysqli_query($con, "UPDATE `tbl_tkkhachhang` SET `avatar` = '$dbPath_safe', `last_updated` = $now WHERE `makh` = $makh");

    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật avatar thành công!',
        'avatar_url' => BASE_URL . $dbPath
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu file']);
}

mysqli_close($con);
