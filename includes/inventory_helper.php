<?php
/**
 * Inventory Helper — Core stock management functions.
 * Include this file wherever stock operations are needed.
 * 
 * Sử dụng MySQL transaction + SELECT ... FOR UPDATE để tránh race condition.
 */

/**
 * Lấy tồn kho tất cả size của 1 sản phẩm.
 * @return array [size => soluong, ...]
 */
function getStockByProduct($con, $masp) {
    $masp = intval($masp);
    $result = mysqli_query($con, "SELECT `size`, `soluong` FROM `tbl_tonkho` WHERE `masp` = $masp ORDER BY `size` ASC");
    $stock = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $stock[(int)$row['size']] = (int)$row['soluong'];
        }
    }
    return $stock;
}

/**
 * Lấy tồn kho 1 size cụ thể.
 * @return int Số lượng tồn kho (0 nếu không tìm thấy)
 */
function getStockForSize($con, $masp, $size) {
    $masp = intval($masp);
    $size = intval($size);
    $result = mysqli_query($con, "SELECT `soluong` FROM `tbl_tonkho` WHERE `masp` = $masp AND `size` = $size LIMIT 1");
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return (int)$row['soluong'];
    }
    return 0;
}

/**
 * Kiểm tra đủ tồn kho cho danh sách items (KHÔNG lock).
 * Dùng cho hiển thị / cảnh báo, KHÔNG dùng cho checkout.
 * @param array $items [{masp, size, quantity}, ...]
 * @return array ['valid' => bool, 'errors' => [...]]
 */
function validateStock($con, $items) {
    $errors = [];
    foreach ($items as $item) {
        $masp = intval($item['masp']);
        $size = intval($item['size']);
        $qty = intval($item['quantity']);
        
        if (empty($size)) {
            $errors[] = [
                'masp' => $masp,
                'size' => $size,
                'message' => 'Chưa chọn size'
            ];
            continue;
        }
        
        $available = getStockForSize($con, $masp, $size);
        if ($available < $qty) {
            $nameResult = mysqli_query($con, "SELECT `tensp` FROM `tbl_qlsanpham` WHERE `masp` = $masp LIMIT 1");
            $name = ($nameResult && $row = mysqli_fetch_assoc($nameResult)) ? $row['tensp'] : "SP #$masp";
            
            if ($available <= 0) {
                $errors[] = [
                    'masp' => $masp,
                    'size' => $size,
                    'available' => 0,
                    'requested' => $qty,
                    'message' => "\"$name\" size $size đã hết hàng"
                ];
            } else {
                $errors[] = [
                    'masp' => $masp,
                    'size' => $size,
                    'available' => $available,
                    'requested' => $qty,
                    'message' => "\"$name\" size $size chỉ còn $available sản phẩm (bạn yêu cầu $qty)"
                ];
            }
        }
    }
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * VALIDATE + DEDUCT tồn kho trong 1 transaction duy nhất.
 * Sử dụng SELECT ... FOR UPDATE để lock rows, tránh race condition hoàn toàn.
 * 
 * Đây là hàm DUY NHẤT nên được dùng khi đặt hàng (checkout).
 * 
 * @param mysqli $con
 * @param array $items [{masp, size, quantity}, ...]
 * @return array ['success' => bool, 'errors' => [...]]
 */
function validateAndDeductStock($con, $items) {
    $errors = [];
    
    // Bắt đầu transaction
    mysqli_autocommit($con, false);
    mysqli_begin_transaction($con);
    
    try {
        foreach ($items as $item) {
            $masp = intval($item['masp']);
            $size = intval($item['size']);
            $qty = intval($item['quantity']);
            
            if (empty($size)) {
                $errors[] = ['masp' => $masp, 'size' => $size, 'message' => 'Chưa chọn size'];
                continue;
            }
            
            if ($qty <= 0) continue;
            
            // SELECT ... FOR UPDATE: lock row này cho đến khi COMMIT/ROLLBACK
            // Khách hàng khác sẽ phải ĐỢI cho đến khi transaction này hoàn tất
            $lockResult = mysqli_query($con, 
                "SELECT `soluong` FROM `tbl_tonkho` WHERE `masp` = $masp AND `size` = $size FOR UPDATE"
            );
            
            if (!$lockResult || mysqli_num_rows($lockResult) === 0) {
                $errors[] = [
                    'masp' => $masp, 'size' => $size,
                    'message' => "Sản phẩm #$masp size $size không có trong kho"
                ];
                continue;
            }
            
            $stockRow = mysqli_fetch_assoc($lockResult);
            $available = (int)$stockRow['soluong'];
            
            if ($available < $qty) {
                // Lấy tên sản phẩm cho thông báo lỗi
                $nameResult = mysqli_query($con, "SELECT `tensp` FROM `tbl_qlsanpham` WHERE `masp` = $masp LIMIT 1");
                $name = ($nameResult && $nr = mysqli_fetch_assoc($nameResult)) ? $nr['tensp'] : "SP #$masp";
                
                if ($available <= 0) {
                    $errors[] = [
                        'masp' => $masp, 'size' => $size, 'available' => 0, 'requested' => $qty,
                        'message' => "\"$name\" size $size đã hết hàng"
                    ];
                } else {
                    $errors[] = [
                        'masp' => $masp, 'size' => $size, 'available' => $available, 'requested' => $qty,
                        'message' => "\"$name\" size $size chỉ còn $available sản phẩm (bạn yêu cầu $qty)"
                    ];
                }
                continue;
            }
            
            // Trừ tồn kho ngay trong transaction
            $now = time();
            $newQty = $available - $qty;
            mysqli_query($con, 
                "UPDATE `tbl_tonkho` SET `soluong` = $newQty, `ngaycapnhat` = $now 
                 WHERE `masp` = $masp AND `size` = $size"
            );
        }
        
        if (!empty($errors)) {
            // Có lỗi → ROLLBACK toàn bộ, không trừ gì cả
            mysqli_rollback($con);
            mysqli_autocommit($con, true);
            return ['success' => false, 'errors' => $errors];
        }
        
        // Tất cả OK → COMMIT
        mysqli_commit($con);
        mysqli_autocommit($con, true);
        return ['success' => true, 'errors' => []];
        
    } catch (Exception $e) {
        mysqli_rollback($con);
        mysqli_autocommit($con, true);
        return ['success' => false, 'errors' => [['message' => 'Lỗi hệ thống: ' . $e->getMessage()]]];
    }
}

/**
 * Hoàn tồn kho từ chi tiết đơn hàng (dùng khi hủy đơn).
 * Cũng sử dụng transaction để đảm bảo tính nhất quán.
 * @param int $orderId
 * @return bool
 */
function restoreStock($con, $orderId) {
    $orderId = intval($orderId);
    $result = mysqli_query($con, "SELECT `masp`, `size`, `quantity` FROM `oder_chitiet` WHERE `madonhang` = $orderId");
    
    if (!$result) return false;
    
    mysqli_autocommit($con, false);
    mysqli_begin_transaction($con);
    
    try {
        $now = time();
        while ($item = mysqli_fetch_assoc($result)) {
            $masp = intval($item['masp']);
            $size = intval($item['size']);
            $qty = intval($item['quantity']);
            
            if (empty($size) || $qty <= 0) continue;
            
            // Lock + cộng lại tồn kho
            $lockResult = mysqli_query($con, 
                "SELECT `soluong` FROM `tbl_tonkho` WHERE `masp` = $masp AND `size` = $size FOR UPDATE"
            );
            
            if ($lockResult && mysqli_num_rows($lockResult) > 0) {
                $currentRow = mysqli_fetch_assoc($lockResult);
                $newQty = (int)$currentRow['soluong'] + $qty;
                mysqli_query($con, 
                    "UPDATE `tbl_tonkho` SET `soluong` = $newQty, `ngaycapnhat` = $now 
                     WHERE `masp` = $masp AND `size` = $size"
                );
            } else {
                // Record chưa tồn tại → tạo mới
                mysqli_query($con, 
                    "INSERT INTO `tbl_tonkho` (`masp`, `size`, `soluong`, `ngaytao`, `ngaycapnhat`) 
                     VALUES ($masp, $size, $qty, $now, $now)
                     ON DUPLICATE KEY UPDATE `soluong` = `soluong` + $qty, `ngaycapnhat` = $now"
                );
            }
        }
        
        mysqli_commit($con);
        mysqli_autocommit($con, true);
        return true;
        
    } catch (Exception $e) {
        mysqli_rollback($con);
        mysqli_autocommit($con, true);
        return false;
    }
}

/**
 * Tự động chuyển đơn hàng SHIPPING > 15 ngày sang DELIVERED.
 * Gọi hàm này khi load trang tra cứu đơn hàng.
 */
function autoCompleteShippingOrders($con) {
    $fifteenDaysAgo = time() - (15 * 24 * 60 * 60);
    
    $sql = "UPDATE `oder` SET `status` = 'DELIVERED', `payment_status` = CASE 
                WHEN `payment_status` = 'PAID' THEN 'PAID'
                ELSE 'UNPAID'
            END
            WHERE `status` = 'SHIPPING' AND `ngaytao` < $fifteenDaysAgo";
    mysqli_query($con, $sql);
    
    return mysqli_affected_rows($con);
}
