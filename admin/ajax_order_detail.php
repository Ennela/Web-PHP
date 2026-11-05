<?php
require_once dirname(__DIR__) . '/config.php';
/**
 * AJAX endpoint for admin - returns HTML table with order detail (products + sizes).
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['dangnhap1'])) {
    http_response_code(403);
    echo 'Không có quyền truy cập';
    exit;
}

include './connect_db.php';

$orderId = intval($_GET['id'] ?? 0);
if ($orderId <= 0) {
    echo '<p style="color:#64748b; font-size:0.85rem;">Mã đơn hàng không hợp lệ.</p>';
    exit;
}

$stmt = mysqli_prepare($con, "SELECT oc.quantity, oc.size, oc.price, sp.tensp, sp.anhdaidien 
        FROM `oder_chitiet` oc 
        LEFT JOIN `tbl_qlsanpham` sp ON oc.masp = sp.masp 
        WHERE oc.madonhang = ?");
mysqli_stmt_bind_param($stmt, "i", $orderId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) == 0) {
    echo '<p style="color:#64748b; font-size:0.85rem;">Không có sản phẩm nào trong đơn hàng này.</p>';
    exit;
}
?>
<div class="ajax-order-detail-wrap" style="padding: 10px; background: #f8fafc; border-radius: 8px;">
    <table class="table-auto w-full text-left" style="font-size: 0.82rem; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 2px solid #e2e8f0; color: #475569;">
                <th style="padding: 6px 8px;">Ảnh</th>
                <th style="padding: 6px 8px;">Sản phẩm</th>
                <th style="padding: 6px 8px; text-align: center;">Size</th>
                <th style="padding: 6px 8px; text-align: center;">SL</th>
                <th style="padding: 6px 8px; text-align: right;">Đơn giá</th>
                <th style="padding: 6px 8px; text-align: right;">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $tongTien = 0;
            while ($row = mysqli_fetch_assoc($result)): 
                $subtotal = $row['quantity'] * $row['price'];
                $tongTien += $subtotal;
                $anh = !empty($row['anhdaidien']) ? $row['anhdaidien'] : 'uploads/default.png';
            ?>
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 6px 8px;">
                    <img src="<?= e(safe_url($anh)) ?>" alt="" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #e2e8f0;">
                </td>
                <td style="padding: 6px 8px; font-weight: 500; color: #1e293b;">
                    <?= e($row['tensp'] ?? 'Sản phẩm đã xóa') ?>
                </td>
                <td style="padding: 6px 8px; text-align: center;">
                    <span style="display: inline-block; padding: 2px 6px; background: #e0e7ff; color: #3730a3; border-radius: 4px; font-weight: 600; font-size: 0.75rem;">
                        <?= !empty($row['size']) ? (int)$row['size'] : 'N/A' ?>
                    </span>
                </td>
                <td style="padding: 6px 8px; text-align: center; font-weight: 600;">
                    <?= (int)$row['quantity'] ?>
                </td>
                <td style="padding: 6px 8px; text-align: right; color: #64748b;">
                    <?= number_format($row['price'], 0, ',', '.') ?>đ
                </td>
                <td style="padding: 6px 8px; text-align: right; font-weight: 600; color: #0f172a;">
                    <?= number_format($subtotal, 0, ',', '.') ?>đ
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr style="border-top: 2px solid #e2e8f0; font-weight: 700;">
                <td colspan="5" style="padding: 8px; text-align: right; color: #334155;">Tổng cộng:</td>
                <td style="padding: 8px; text-align: right; color: #dc2626; font-size: 0.9rem;">
                    <?= number_format($tongTien, 0, ',', '.') ?>đ
                </td>
            </tr>
        </tfoot>
    </table>
</div>

<?php mysqli_stmt_close($stmt); mysqli_close($con); ?>
