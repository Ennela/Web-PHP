<?php
/**
 * API: Size giày yêu thích
 * Methods: GET (danh sách), POST (thêm/xóa)
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

// === GET: Lấy danh sách ===
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = mysqli_query($con, "SELECT * FROM `tbl_sizegiay` WHERE `makh` = $makh ORDER BY `he_size`, `size_value`");
    $sizes = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $sizes[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $sizes]);
    exit;
}

// === POST ===
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method không hợp lệ']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'save':
        $he_size = $_POST['he_size'] ?? 'EU';
        $size_value = trim($_POST['size_value'] ?? '');
        $ghichu = trim($_POST['ghichu'] ?? '');

        if (!in_array($he_size, ['EU', 'US', 'CM'])) {
            echo json_encode(['success' => false, 'message' => 'Hệ size không hợp lệ']);
            exit;
        }

        if (empty($size_value)) {
            echo json_encode(['success' => false, 'message' => 'Chưa chọn size']);
            exit;
        }

        // Kiểm tra đã tồn tại chưa
        $size_safe = mysqli_real_escape_string($con, $size_value);
        $he_safe = mysqli_real_escape_string($con, $he_size);
        $checkQ = mysqli_query($con, "SELECT `id` FROM `tbl_sizegiay` WHERE `makh` = $makh AND `he_size` = '$he_safe' AND `size_value` = '$size_safe'");
        
        $now = time();
        $ghichu_safe = mysqli_real_escape_string($con, $ghichu);

        if (mysqli_num_rows($checkQ) > 0) {
            // Update ghi chú
            $existRow = mysqli_fetch_assoc($checkQ);
            $sql = "UPDATE `tbl_sizegiay` SET `ghichu` = '$ghichu_safe', `ngaycapnhat` = $now WHERE `id` = " . $existRow['id'];
        } else {
            $sql = "INSERT INTO `tbl_sizegiay` (`makh`, `he_size`, `size_value`, `ghichu`, `ngaytao`, `ngaycapnhat`) VALUES ($makh, '$he_safe', '$size_safe', '$ghichu_safe', $now, $now)";
        }

        if (mysqli_query($con, $sql)) {
            echo json_encode(['success' => true, 'message' => 'Lưu size yêu thích thành công!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . mysqli_error($con)]);
        }
        break;

    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
            exit;
        }

        if (mysqli_query($con, "DELETE FROM `tbl_sizegiay` WHERE `id` = $id AND `makh` = $makh")) {
            echo json_encode(['success' => true, 'message' => 'Đã xóa size!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . mysqli_error($con)]);
        }
        break;

    case 'update_note':
        $he_size = $_POST['he_size'] ?? 'EU';
        $ghichu = trim($_POST['ghichu'] ?? '');
        
        if (!in_array($he_size, ['EU', 'US', 'CM'])) {
            echo json_encode(['success' => false, 'message' => 'Hệ size không hợp lệ']);
            exit;
        }

        $he_safe = mysqli_real_escape_string($con, $he_size);
        $ghichu_safe = mysqli_real_escape_string($con, $ghichu);
        $now = time();

        // Cập nhật ghi chú cho tất cả size trong hệ đó
        mysqli_query($con, "UPDATE `tbl_sizegiay` SET `ghichu` = '$ghichu_safe', `ngaycapnhat` = $now WHERE `makh` = $makh AND `he_size` = '$he_safe'");
        echo json_encode(['success' => true, 'message' => 'Cập nhật ghi chú thành công!']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
}

mysqli_close($con);
