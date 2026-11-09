<?php
/**
 * API: Quản lý sổ địa chỉ
 * Methods: GET (danh sách), POST (thêm/sửa/xóa/đặt mặc định)
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
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']);
    exit;
}

$makh = intval($_SESSION['makh']);

// === GET: Lấy danh sách ===
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = mysqli_prepare($con, "SELECT * FROM `tbl_diachi` WHERE `makh` = ? ORDER BY `macdinh` DESC, `ngaytao` DESC");
    mysqli_stmt_bind_param($stmt, "i", $makh);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $addresses = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['sdt'] = app_decrypt_field($row['sdt'] ?? '', 'tbl_diachi.sdt');
        $row['diachi_cuthe'] = app_decrypt_field($row['diachi_cuthe'] ?? '', 'tbl_diachi.diachi_cuthe');
        $addresses[] = $row;
    }
    mysqli_stmt_close($stmt);
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

        if (empty($hoten) || empty($sdt) || empty($diachi_cuthe)) {
            echo json_encode(['success' => false, 'message' => 'Họ tên, SĐT và địa chỉ cụ thể là bắt buộc']);
            exit;
        }

        if (!preg_match('/^(0|\+84)[0-9]{9,10}$/', $sdt)) {
            echo json_encode(['success' => false, 'message' => 'Số điện thoại không hợp lệ']);
            exit;
        }

        if ($macdinh) {
            mysqli_query($con, "UPDATE `tbl_diachi` SET `macdinh` = 0 WHERE `makh` = $makh");
        }

        if ($countR['cnt'] == 0) {
            $macdinh = 1;
        }

        $now = time();
        $sdt_enc = (string)app_encrypt($sdt, 'tbl_diachi.sdt');
        $diachi_cuthe_enc = (string)app_encrypt($diachi_cuthe, 'tbl_diachi.diachi_cuthe');

        $stmt = mysqli_prepare(
            $con,
            "INSERT INTO `tbl_diachi` (`makh`, `hoten`, `sdt`, `tinh`, `quan_huyen`, `phuong_xa`, `diachi_cuthe`, `macdinh`, `ngaytao`, `ngaycapnhat`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "issssssiii", $makh, $hoten, $sdt_enc, $tinh, $quan_huyen, $phuong_xa, $diachi_cuthe_enc, $macdinh, $now, $now);

        if (mysqli_stmt_execute($stmt)) {
            $newId = mysqli_insert_id($con);
            mysqli_stmt_close($stmt);
            echo json_encode(['success' => true, 'message' => 'Thêm địa chỉ thành công!', 'id' => $newId]);
        } else {
            mysqli_stmt_close($stmt);
            echo json_encode(['success' => false, 'message' => 'Không thể xử lý yêu cầu. Vui lòng thử lại sau.']);
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
        $sdt_enc = (string)app_encrypt($sdt, 'tbl_diachi.sdt');
        $diachi_cuthe_enc = (string)app_encrypt($diachi_cuthe, 'tbl_diachi.diachi_cuthe');

        $stmt = mysqli_prepare(
            $con,
            "UPDATE `tbl_diachi` SET `hoten` = ?, `sdt` = ?, `tinh` = ?, `quan_huyen` = ?, `phuong_xa` = ?, `diachi_cuthe` = ?, `ngaycapnhat` = ? WHERE `id` = ? AND `makh` = ?"
        );
        mysqli_stmt_bind_param($stmt, "ssssssiii", $hoten, $sdt_enc, $tinh, $quan_huyen, $phuong_xa, $diachi_cuthe_enc, $now, $id, $makh);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            echo json_encode(['success' => true, 'message' => 'Cập nhật địa chỉ thành công!']);
        } else {
            mysqli_stmt_close($stmt);
            echo json_encode(['success' => false, 'message' => 'Không thể xử lý yêu cầu. Vui lòng thử lại sau.']);
        }
        break;

    // --- XÓA ---
    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
            exit;
        }

        $chk = mysqli_query($con, "SELECT `macdinh` FROM `tbl_diachi` WHERE `id` = $id AND `makh` = $makh");
        $chkRow = mysqli_fetch_assoc($chk);

        if (mysqli_query($con, "DELETE FROM `tbl_diachi` WHERE `id` = $id AND `makh` = $makh")) {
            if ($chkRow && $chkRow['macdinh'] == 1) {
                $firstQ = mysqli_query($con, "SELECT `id` FROM `tbl_diachi` WHERE `makh` = $makh ORDER BY `ngaytao` DESC LIMIT 1");
                $firstR = mysqli_fetch_assoc($firstQ);
                if ($firstR) {
                    mysqli_query($con, "UPDATE `tbl_diachi` SET `macdinh` = 1 WHERE `id` = " . intval($firstR['id']));
                }
            }
            echo json_encode(['success' => true, 'message' => 'Xóa địa chỉ thành công!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể xử lý yêu cầu. Vui lòng thử lại sau.']);
        }
        break;

    // --- ĐẶT MẶC ĐỊNH ---
    case 'set_default':
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
            exit;
        }

        mysqli_query($con, "UPDATE `tbl_diachi` SET `macdinh` = 0 WHERE `makh` = $makh");
        if (mysqli_query($con, "UPDATE `tbl_diachi` SET `macdinh` = 1 WHERE `id` = $id AND `makh` = $makh")) {
            echo json_encode(['success' => true, 'message' => 'Đã đặt làm địa chỉ mặc định!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể xử lý yêu cầu. Vui lòng thử lại sau.']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
}

mysqli_close($con);
