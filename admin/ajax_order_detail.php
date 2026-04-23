<?php
/**
 * AJAX endpoint for admin - returns HTML table with order detail (products + sizes).
 */
session_start();
if (empty($_SESSION['dangnhap1'])) {
    http_response_code(403);
    echo 'Không có quyền truy cập';
    exit;
}

include './connect_db.php';

$orderId = intval($_GET['id'] ?? 0);
if ($orderId <= 0) {
    echo '<p>ID đơn hàng không hợp lệ</p>';
    exit;
}

$sql = "SELECT oc.quantity, oc.size, oc.price, sp.tensp, sp.anhdaidien 
        FROM `oder_chitiet` oc 
        LEFT JOIN `tbl_qlsanpham` sp ON oc.masp = sp.masp 
        WHERE oc.madonhang = $orderId";
$result = mysqli_query($con, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    echo '<p style="color:#64748b; font-size:0.85rem;">Không có sản phẩm nào trong đơn hàng này.</p>';
    exit;
}
?>
<table class="detail-product-table">
    <thead>
        <tr>
            <th style="width:50px;">Ảnh</th>
            <th>Sản phẩm</th>
            <th style="text-align:center;">Size</th>
            <th style="text-align:center;">Số lượng</th>
            <th style="text-align:right;">Đơn giá</th>
            <th style="text-align:right;">Thành tiền</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $total = 0;
        while ($row = mysqli_fetch_assoc($result)): 
            $lineTotal = $row['price'] * $row['quantity'];
            $total += $lineTotal;
        ?>
        <tr>
            <td>
                <?php if (!empty($row['anhdaidien'])): ?>
                <img src="<?= $row['anhdaidien'] ?>" style="width:40px; height:40px; object-fit:cover; border-radius:4px;">
                <?php else: ?>
                <span style="color:#94a3b8;">--</span>
                <?php endif; ?>
            </td>
            <td style="font-weight:600;"><?= htmlspecialchars($row['tensp'] ?? 'N/A') ?></td>
            <td style="text-align:center;">
                <?php if (!empty($row['size'])): ?>
                <span class="size-tag"><?= $row['size'] ?></span>
                <?php else: ?>
                <span style="color:#94a3b8;">--</span>
                <?php endif; ?>
            </td>
            <td style="text-align:center; font-weight:600;"><?= $row['quantity'] ?></td>
            <td style="text-align:right;"><?= number_format($row['price'], 0, ',', '.') ?>đ</td>
            <td style="text-align:right; font-weight:700;"><?= number_format($lineTotal, 0, ',', '.') ?>đ</td>
        </tr>
        <?php endwhile; ?>
        <tr style="background:#e2e8f0; font-weight:800;">
            <td colspan="5" style="text-align:right; padding:8px 10px;">Tổng cộng:</td>
            <td style="text-align:right; padding:8px 10px; color:#2563eb;"><?= number_format($total, 0, ',', '.') ?>đ</td>
        </tr>
    </tbody>
</table>
<?php mysqli_close($con); ?>
