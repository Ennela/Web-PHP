<?php
/**
 * AJAX endpoint for real-time cart updates.
 * Accepts POST with JSON body: { masp, quantity, size }
 * Returns JSON: { success, cart, totalHtml, itemTotalHtml }
 */
require_once dirname(__DIR__) . '/config.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

include BASE_PATH . 'includes/connect.php';
require_once BASE_PATH . 'includes/inventory_helper.php';

// Migrate old session format
if (isset($_SESSION['giohang']) && is_array($_SESSION['giohang'])) {
    foreach ($_SESSION['giohang'] as $masp => $val) {
        if (!is_array($val)) {
            $_SESSION['giohang'][$masp] = ['quantity' => (int)$val, 'size' => null];
        }
    }
}

if (!isset($_SESSION['giohang'])) {
    $_SESSION['giohang'] = [];
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['masp'])) {
    echo json_encode(['success' => false, 'error' => 'Missing masp']);
    exit;
}

$masp = (int)$input['masp'];
$action = $input['action'] ?? 'update'; // 'update' or 'delete'

if ($action === 'delete') {
    unset($_SESSION['giohang'][$masp]);
} else {
    if (!isset($_SESSION['giohang'][$masp])) {
        echo json_encode(['success' => false, 'error' => 'Product not in cart']);
        exit;
    }

    $quantity = isset($input['quantity']) ? max(1, (int)$input['quantity']) : $_SESSION['giohang'][$masp]['quantity'];
    $size = isset($input['size']) ? ($input['size'] !== '' ? (int)$input['size'] : null) : $_SESSION['giohang'][$masp]['size'];

    // Validate tồn kho
    $stockWarning = null;
    if (!empty($size)) {
        $available = getStockForSize($con, $masp, $size);
        if ($available <= 0) {
            $stockWarning = 'Size này hiện đang hết hàng';
        } elseif ($quantity > $available) {
            $quantity = $available; // Auto-cap
            $stockWarning = "Chỉ còn $available sản phẩm";
        }
    }

    if ($quantity <= 0) {
        unset($_SESSION['giohang'][$masp]);
    } else {
        $_SESSION['giohang'][$masp] = [
            'quantity' => $quantity,
            'size' => $size
        ];
    }
}

// Recalculate totals
$cartItems = [];
$grandTotal = 0;

if (!empty($_SESSION['giohang'])) {
    $ids = implode(",", array_keys($_SESSION['giohang']));
    $result = mysqli_query($con, "SELECT * FROM `tbl_qlsanpham` WHERE `masp` IN ($ids)");
    while ($row = mysqli_fetch_assoc($result)) {
        $item = $_SESSION['giohang'][$row['masp']];
        $itemTotal = $row['giasanpham'] * $item['quantity'];
        $grandTotal += $itemTotal;
        $cartItems[$row['masp']] = [
            'quantity' => $item['quantity'],
            'size' => $item['size'],
            'price' => $row['giasanpham'],
            'itemTotal' => $itemTotal,
            'itemTotalFormatted' => number_format($itemTotal, 0, ",", ".") . '&nbsp;đ',
        ];
    }
}

// Build stock info for response
$stockInfo = [];
if (!empty($_SESSION['giohang'])) {
    $allMasps = array_keys($_SESSION['giohang']);
    $stockResult = mysqli_query($con, "SELECT `masp`, `size`, `soluong` FROM `tbl_tonkho` WHERE `masp` IN (" . implode(',', array_map('intval', $allMasps)) . ")");
    if ($stockResult) {
        while ($sr = mysqli_fetch_assoc($stockResult)) {
            $stockInfo[(int)$sr['masp']][(int)$sr['size']] = (int)$sr['soluong'];
        }
    }
}

$response = [
    'success' => true,
    'cart' => $cartItems,
    'grandTotal' => $grandTotal,
    'grandTotalFormatted' => number_format($grandTotal, 0, ",", ".") . '&nbsp;đ',
    'cartCount' => count($_SESSION['giohang']),
    'stock' => $stockInfo,
];

if (!empty($stockWarning)) {
    $response['stockWarning'] = $stockWarning;
}

echo json_encode($response);
