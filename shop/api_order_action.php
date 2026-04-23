<?php
/**
 * API: Hành động đơn hàng của khách hàng
 * POST: { action: 'receive'|'cancel', order_id: int }
 * - receive: Xác nhận đã nhận hàng (SHIPPING → DELIVERED)
 * - cancel: Hủy đơn (PENDING/CONFIRMED → CANCELLED + hoàn tồn kho)
 */
require_once dirname(__DIR__) . '/config.php';
session_start();
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
header('Content-Type: application/json; charset=utf-8');

include BASE_PATH . 'includes/connect.php';
require_once BASE_PATH . 'includes/inventory_helper.php';

// Bắt buộc đăng nhập
if (empty($_SESSION['dangnhap']) || empty($_SESSION['makh'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']);
    exit;
}

$makh = intval($_SESSION['makh']);

// Parse input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    // Fallback to POST
    $input = $_POST;
}

$action = $input['action'] ?? '';
$orderId = intval($input['order_id'] ?? 0);

if ($orderId <= 0 || !in_array($action, ['receive', 'cancel'])) {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ']);
    exit;
}

// Lấy đơn hàng và kiểm tra quyền sở hữu
$result = mysqli_query($con, "SELECT * FROM `oder` WHERE `id` = $orderId AND `makh` = $makh LIMIT 1");
$order = mysqli_fetch_assoc($result);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Đơn hàng không tồn tại hoặc bạn không có quyền']);
    exit;
}

$currentStatus = strtoupper($order['status'] ?? 'PENDING');
$now = time();

switch ($action) {
    case 'receive':
        // Chỉ cho phép xác nhận nhận hàng khi đơn đang ở trạng thái SHIPPING
        if ($currentStatus !== 'SHIPPING') {
            $statusLabels = [
                'PENDING' => 'Chờ xử lý', 'CONFIRMED' => 'Đã xác nhận',
                'DELIVERED' => 'Đã giao hàng', 'CANCELLED' => 'Đã hủy'
            ];
            $label = $statusLabels[$currentStatus] ?? $currentStatus;
            echo json_encode(['success' => false, 'message' => "Không thể xác nhận nhận hàng. Đơn hàng đang ở trạng thái: $label"]);
            exit;
        }

        mysqli_query($con, "UPDATE `oder` SET `status` = 'DELIVERED' WHERE `id` = $orderId AND `makh` = $makh");
        
        if (mysqli_affected_rows($con) > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Đã xác nhận nhận hàng thành công!',
                'new_status' => 'DELIVERED',
                'new_label' => 'Đã giao hàng'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể cập nhật đơn hàng']);
        }
        break;

    case 'cancel':
        // Chỉ cho phép hủy khi đơn ở trạng thái PENDING hoặc CONFIRMED
        // KHÔNG cho hủy khi SHIPPING hoặc DELIVERED
        if (!in_array($currentStatus, ['PENDING', 'CONFIRMED'])) {
            $reasons = [
                'SHIPPING' => 'Đơn hàng đang được giao, không thể hủy',
                'DELIVERED' => 'Đơn hàng đã giao thành công, không thể hủy',
                'CANCELLED' => 'Đơn hàng đã bị hủy trước đó'
            ];
            $reason = $reasons[$currentStatus] ?? 'Không thể hủy đơn hàng ở trạng thái hiện tại';
            echo json_encode(['success' => false, 'message' => $reason]);
            exit;
        }

        // Hủy đơn
        $paymentUpdate = ($order['payment_status'] === 'PAID') ? ", `payment_status` = 'REFUNDED'" : "";
        mysqli_query($con, "UPDATE `oder` SET `status` = 'CANCELLED' $paymentUpdate WHERE `id` = $orderId AND `makh` = $makh");
        
        if (mysqli_affected_rows($con) > 0) {
            // Hoàn tồn kho
            restoreStock($con, $orderId);
            
            echo json_encode([
                'success' => true,
                'message' => 'Đơn hàng đã được hủy thành công!',
                'new_status' => 'CANCELLED',
                'new_label' => 'Đã hủy'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể hủy đơn hàng']);
        }
        break;
}

mysqli_close($con);
