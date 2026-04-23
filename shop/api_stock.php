<?php
/**
 * API: Tồn kho sản phẩm
 * GET ?masp=X → trả về tồn kho tất cả size
 */
require_once dirname(__DIR__) . '/config.php';
header('Content-Type: application/json; charset=utf-8');

include BASE_PATH . 'includes/connect.php';
require_once BASE_PATH . 'includes/inventory_helper.php';

$masp = intval($_GET['masp'] ?? 0);

if ($masp <= 0) {
    echo json_encode(['success' => false, 'message' => 'Mã sản phẩm không hợp lệ']);
    exit;
}

$stock = getStockByProduct($con, $masp);

echo json_encode([
    'success' => true,
    'masp' => $masp,
    'stock' => $stock
]);

mysqli_close($con);
