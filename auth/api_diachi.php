<?php
/**
 * API: Quản lý sổ địa chỉ
 * Methods: GET (danh sách), POST (thêm/sửa/xóa/đặt mặc định)
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
    $result = mysqli_query($con, "SELECT * FROM `tbl_diachi` WHERE `makh` = $makh ORDER BY `macdinh` DESC, `ngaytao` DESC");
    $addresses = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $addresses[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $addresses]);
    exit;
}

// === POST ===
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method không hợp lệ']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    // --- THÊM ---
    case 'add':
        // Kiểm tra giới hạn 5
        $countQ = mysqli_query($con, "SELECT COUNT(*) AS cnt FROM `tbl_diachi` WHERE `makh` = $makh");
        $countR = mysqli_fetch_assoc($countQ);
        if ($countR['cnt'] >= 5) {
            echo json_encode(['success' => false, 'message' => 'Bạn chỉ có thể lưu tối đa 5 địa chỉ']);
            exit;
        }

        $hoten = trim($_POST['hoten'] ?? '');
        $sdt = trim($_POST['sdt'] ?? '');
        $tinh = trim($_POST['tinh'] ?? '');
        $quan_huyen = trim($_POST['quan_huyen'] ?? '');
        $phuong_xa = trim($_POST['phuong_xa'] ?? '');
        $diachi_cuthe = trim($_POST['diachi_cuthe'] ?? '');
        $macdinh = intval($_POST['macdinh'] ?? 0);

        // Validate
        if (empty($hoten) || empty($sdt) || empty($diachi_cuthe)) {
            echo json_encode(['success' => false, 'message' => 'Họ tên, SĐT và địa chỉ cụ thể là bắt buộc']);
            exit;
        }

        if (!preg_match('/^(0|\+84)[0-9]{9,10}$/', $sdt)) {
            echo json_encode(['success' => false, 'message' => 'Số điện thoại không hợp lệ']);
            exit;
        }

        // Nếu đặt mặc định, bỏ mặc định các cái khác
        if ($macdinh) {
            mysqli_query($con, "UPDATE `tbl_diachi` SET `macdinh` = 0 WHERE `makh` = $makh");
        }

        // Nếu là địa chỉ đầu tiên, tự động đặt mặc định
        if ($countR['cnt'] == 0) {
            $macdinh = 1;
        }

        $now = time();
        $sql = sprintf(
            "INSERT INTO `tbl_diachi` (`makh`, `hoten`, `sdt`, `tinh`, `quan_huyen`, `phuong_xa`, `diachi_cuthe`, `macdinh`, `ngaytao`, `ngaycapnhat`) VALUES (%d, '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, %d)",
            $makh,
            mysqli_real_escape_string($con, $hoten),
            mysqli_real_escape_string($con, $sdt),
            mysqli_real_escape_string($con, $tinh),
            mysqli_real_escape_string($con, $quan_huyen),
            mysqli_real_escape_string($con, $phuong_xa),
            mysqli_real_escape_string($con, $diachi_cuthe),
            $macdinh,
            $now, $now
        );

        if (mysqli_query($con, $sql)) {
            $newId = mysqli_insert_id($con);
            echo json_encode(['success' => true, 'message' => 'Thêm địa chỉ thành công!', 'id' => $newId]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . mysqli_error($con)]);
        }
        break;

    // --- SỬA ---
    case 'edit':
        $id = intval($_POST['id'] ?? 0);
        $hoten = trim($_POST['hoten'] ?? '');
        $sdt = trim($_POST['sdt'] ?? '');
        $tinh = trim($_POST['tinh'] ?? '');
        $quan_huyen = trim($_POST['quan_huyen'] ?? '');
        $phuong_xa = trim($_POST['phuong_xa'] ?? '');
        $diachi_cuthe = trim($_POST['diachi_cuthe'] ?? '');

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
            exit;
        }

        if (empty($hoten) || empty($sdt) || empty($diachi_cuthe)) {
            echo json_encode(['success' => false, 'message' => 'Họ tên, SĐT và địa chỉ cụ thể là bắt buộc']);
            exit;
        }

        if (!preg_match('/^(0|\+84)[0-9]{9,10}$/', $sdt)) {
            echo json_encode(['success' => false, 'message' => 'Số điện thoại không hợp lệ']);
            exit;
        }

        $now = time();
        $sql = sprintf(
            "UPDATE `tbl_diachi` SET `hoten`='%s', `sdt`='%s', `tinh`='%s', `quan_huyen`='%s', `phuong_xa`='%s', `diachi_cuthe`='%s', `ngaycapnhat`=%d WHERE `id`=%d AND `makh`=%d",
            mysqli_real_escape_string($con, $hoten),
            mysqli_real_escape_string($con, $sdt),
            mysqli_real_escape_string($con, $tinh),
            mysqli_real_escape_string($con, $quan_huyen),
            mysqli_real_escape_string($con, $phuong_xa),
            mysqli_real_escape_string($con, $diachi_cuthe),
            $now, $id, $makh
        );

        if (mysqli_query($con, $sql)) {
            echo json_encode(['success' => true, 'message' => 'Cập nhật địa chỉ thành công!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . mysqli_error($con)]);
        }
        break;

    // --- XÓA ---
    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
            exit;
        }

        // Kiểm tra xem có phải mặc định không
        $chk = mysqli_query($con, "SELECT `macdinh` FROM `tbl_diachi` WHERE `id` = $id AND `makh` = $makh");
        $chkRow = mysqli_fetch_assoc($chk);

        if (mysqli_query($con, "DELETE FROM `tbl_diachi` WHERE `id` = $id AND `makh` = $makh")) {
            // Nếu xóa cái mặc định, set cái đầu tiên còn lại làm mặc định
            if ($chkRow && $chkRow['macdinh'] == 1) {
                $firstQ = mysqli_query($con, "SELECT `id` FROM `tbl_diachi` WHERE `makh` = $makh ORDER BY `ngaytao` DESC LIMIT 1");
                $firstR = mysqli_fetch_assoc($firstQ);
                if ($firstR) {
                    mysqli_query($con, "UPDATE `tbl_diachi` SET `macdinh` = 1 WHERE `id` = " . $firstR['id']);
                }
            }
            echo json_encode(['success' => true, 'message' => 'Xóa địa chỉ thành công!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . mysqli_error($con)]);
        }
        break;

    // --- ĐẶT MẶC ĐỊNH ---
    case 'set_default':
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
            exit;
        }

        // Bỏ mặc định cũ
        mysqli_query($con, "UPDATE `tbl_diachi` SET `macdinh` = 0 WHERE `makh` = $makh");
        // Đặt mặc định mới
        if (mysqli_query($con, "UPDATE `tbl_diachi` SET `macdinh` = 1 WHERE `id` = $id AND `makh` = $makh")) {
            echo json_encode(['success' => true, 'message' => 'Đã đặt làm địa chỉ mặc định!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . mysqli_error($con)]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
}

mysqli_close($con);
