<?php
/**
 * Migration: Thêm cột reset_token, reset_token_expiry cho chức năng Quên mật khẩu
 * 
 * Cách dùng: Truy cập URL:
 *   https://your-domain.com/run_migration_quenmatkhau.php?key=run_now_2026
 * 
 * ⚠️ XÓA FILE NÀY SAU KHI CHẠY XONG!
 */

// Bảo mật đơn giản: cần query param ?key=run_now_2026
if (($_GET['key'] ?? '') !== 'run_now_2026') {
    http_response_code(404);
    echo "Not Found";
    exit;
}

require_once __DIR__ . '/config.php';
include BASE_PATH . 'includes/connect.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h1>🔧 Migration: Quên mật khẩu</h1>";
echo "<hr>";

$migrations = [
    [
        'name' => 'reset_token',
        'sql'  => "ALTER TABLE `tbl_tkkhachhang` ADD COLUMN `reset_token` VARCHAR(64) DEFAULT NULL"
    ],
    [
        'name' => 'reset_token_expiry',
        'sql'  => "ALTER TABLE `tbl_tkkhachhang` ADD COLUMN `reset_token_expiry` INT(11) DEFAULT NULL"
    ]
];

$success = 0;
$skipped = 0;
$errors  = 0;

foreach ($migrations as $m) {
    echo "<p><b>Cột:</b> <code>{$m['name']}</code> → ";
    
    // Kiểm tra cột đã tồn tại chưa
    $check = mysqli_query($con, "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_tkkhachhang' AND COLUMN_NAME = '{$m['name']}'");
    $row = mysqli_fetch_assoc($check);
    
    if ($row['cnt'] > 0) {
        echo "<span style='color: orange; font-weight:bold;'>⚠️ Đã tồn tại (bỏ qua)</span></p>";
        $skipped++;
        continue;
    }
    
    if (mysqli_query($con, $m['sql'])) {
        echo "<span style='color: green; font-weight:bold;'>✅ Thêm thành công!</span></p>";
        $success++;
    } else {
        echo "<span style='color: red; font-weight:bold;'>❌ Lỗi: " . mysqli_error($con) . "</span></p>";
        $errors++;
    }
}

echo "<hr>";
echo "<h2>Kết quả: ✅ $success thành công | ⚠️ $skipped bỏ qua | ❌ $errors lỗi</h2>";
echo "<p style='color: red; font-size: 18px; font-weight: bold;'>⚠️ QUAN TRỌNG: Hãy xóa file này khỏi repo ngay sau khi chạy xong!</p>";
?>
