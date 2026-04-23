<?php
/**
 * Sinh mã đơn hàng 9 số random, đảm bảo không trùng trong DB.
 * @param mysqli $con  Database connection
 * @return string      9-digit order code (zero-padded)
 */
function generateOrderCode($con) {
    $maxAttempts = 20;
    for ($i = 0; $i < $maxAttempts; $i++) {
        // Sinh số ngẫu nhiên từ 100000000 đến 999999999 (đúng 9 chữ số)
        $code = str_pad(mt_rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);
        
        // Kiểm tra trùng trong DB
        $check = mysqli_query($con, "SELECT 1 FROM `oder` WHERE `order_code` = '" . mysqli_real_escape_string($con, $code) . "' LIMIT 1");
        if (mysqli_num_rows($check) === 0) {
            return $code;
        }
    }
    // Fallback: dùng timestamp + random (cực hiếm khi xảy ra)
    return substr(time() . mt_rand(100, 999), -9);
}
