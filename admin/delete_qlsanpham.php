<?php
require_once dirname(__DIR__) . '/config.php';
csrf_require_post();

if (empty($_SESSION['dangnhap1'])) {
    http_response_code(403);
    exit('Không có quyền truy cập.');
}

$id = filter_var($_POST['masp'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
    http_response_code(400);
    exit('Mã bản ghi không hợp lệ.');
}

require __DIR__ . '/connect_db.php';
try {
    $stmt = mysqli_prepare($con, 'DELETE FROM `tbl_qlsanpham` WHERE `masp` = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
} catch (mysqli_sql_exception $exception) {
    http_response_code(409);
    exit('Không thể xóa bản ghi đang được sử dụng.');
}
header('Location: quanlisanpham.php', true, 303);
exit;
