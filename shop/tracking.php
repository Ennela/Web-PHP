<?php
require_once dirname(__DIR__) . '/config.php';
session_start();

include BASE_PATH . 'includes/connect.php';

$orderCode = isset($_GET['order']) ? mysqli_real_escape_string($con, $_GET['order']) : '';
$token = isset($_GET['token']) ? mysqli_real_escape_string($con, $_GET['token']) : '';

$error = '';
$orderData = null;
$orderDetails = [];

if (empty($orderCode) || empty($token)) {
    $error = "Đường dẫn không hợp lệ. Vui lòng kiểm tra lại link trong email của bạn.";
} else {
    // Kiểm tra đơn hàng và token
    $query = "SELECT * FROM `oder` WHERE `order_code` = '$orderCode' AND `token` = '$token' LIMIT 1";
    $result = mysqli_query($con, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $orderData = mysqli_fetch_assoc($result);

        // Lấy chi tiết đơn hàng
        $orderId = $orderData['id'];
        $detailsQuery = "SELECT c.*, p.tensp, p.anhdaidien FROM `oder_chitiet` c JOIN `tbl_qlsanpham` p ON c.masp = p.masp WHERE c.madonhang = '$orderId'";
        $detailsResult = mysqli_query($con, $detailsQuery);
        if ($detailsResult) {
            while ($row = mysqli_fetch_assoc($detailsResult)) {
                $orderDetails[] = $row;
            }
        }
    } else {
        $error = "Không tìm thấy đơn hàng hoặc token không đúng.";
    }
}

function getStatusBadge($status) {
    $status = strtoupper(trim($status));
    switch ($status) {
        case 'PENDING': return '<span class="badge" style="background-color:#ffc107; color:#212529; padding:0.4em 0.6em;">Chờ xử lý</span>';
        case 'CONFIRMED': return '<span class="badge" style="background-color:#17a2b8; color:#fff; padding:0.4em 0.6em;">Đã xác nhận</span>';
        case 'PROCESSING': return '<span class="badge" style="background-color:#17a2b8; color:#fff; padding:0.4em 0.6em;">Đang xử lý</span>';
        case 'SHIPPING': return '<span class="badge" style="background-color:#007bff; color:#fff; padding:0.4em 0.6em;">Đang giao hàng</span>';
        case 'DELIVERED': return '<span class="badge" style="background-color:#28a745; color:#fff; padding:0.4em 0.6em;">Đã giao hàng</span>';
        case 'CANCELLED': return '<span class="badge" style="background-color:#dc3545; color:#fff; padding:0.4em 0.6em;">Đã hủy</span>';
        default: return '<span class="badge" style="background-color:#6c757d; color:#fff; padding:0.4em 0.6em;">' . htmlspecialchars($status) . '</span>';
    }
}

function getPaymentStatusBadge($status, $orderStatus = '') {
    $status = strtoupper(trim($status));
    $orderStatus = strtoupper(trim($orderStatus));
    if ($status === 'PAID' || $orderStatus === 'DELIVERED') return '<span class="badge" style="background-color:#28a745; color:#fff; padding:0.4em 0.6em;">Đã thanh toán</span>';
    return '<span class="badge" style="background-color:#ffc107; color:#212529; padding:0.4em 0.6em;">Chưa thanh toán</span>';
}

include BASE_PATH . 'includes/header.php';
?>
<style>
    .tracking-page {
        padding-top: clamp(90px, 14vw, 120px);
        min-height: 80vh;
        background: #f8f9fa;
        padding-bottom: clamp(30px, 5vw, 50px);
    }
    .tracking-page .block-heading h2 {
        font-family: 'Montserrat', sans-serif;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: clamp(0.5px, 0.3vw, 1px);
        font-size: clamp(1.2rem, 3.5vw, 1.8rem);
        color: #0f172a;
    }
    .tracking-page .block-heading p {
        font-size: clamp(0.85rem, 2vw, 1rem);
        color: #64748b;
    }
    .tracking-page .card {
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        overflow: hidden;
    }
    .tracking-page .card-header {
        background: #fff;
        border-bottom: none;
    }
    .tracking-page .card-title {
        font-family: 'Montserrat', sans-serif;
        font-weight: 800;
        font-size: clamp(1rem, 2.5vw, 1.3rem);
        color: #0f172a;
    }
    .tracking-page .badge {
        font-size: clamp(0.72rem, 1.5vw, 0.82rem);
        padding: 0.5em 0.8em;
        border-radius: 8px;
    }
    .tracking-page h5, .tracking-page h6 {
        font-size: clamp(0.88rem, 2vw, 1rem);
    }
    .tracking-page p {
        font-size: clamp(0.82rem, 1.8vw, 0.95rem);
    }
    .tracking-page .text-danger {
        font-size: clamp(1.1rem, 3vw, 1.5rem) !important;
    }
    /* Responsive table for products */
    .tracking-page .table td {
        vertical-align: middle;
        padding: clamp(8px, 2vw, 12px);
    }
    .tracking-page .table img {
        width: clamp(50px, 12vw, 80px);
        border-radius: 8px;
    }
    .tracking-page .table h6 {
        font-size: clamp(0.8rem, 1.8vw, 0.95rem);
        word-break: break-word;
    }
    .tracking-page .table small {
        font-size: clamp(0.7rem, 1.5vw, 0.82rem);
    }
    /* Alert responsive */
    .tracking-page .alert {
        font-size: clamp(0.85rem, 2vw, 1rem);
        border-radius: 12px;
        padding: clamp(12px, 3vw, 16px) clamp(16px, 3vw, 24px);
    }
    /* Mobile: stack info rows */
    @media (max-width: 575.98px) {
        .tracking-page .row.mb-4 > .col-sm-6 {
            margin-bottom: 16px;
        }
    }
</style>
<main class="page catalog-page tracking-page">
    <section class="clean-block clean-catalog dark">
        <div class="container">
            <div class="block-heading text-center mb-5">
                <h2>Tra cứu trạng thái đơn hàng</h2>
                <p>Theo dõi tình trạng đơn hàng của bạn</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8">
                    <?php if ($error): ?>
                        <div class="alert alert-danger shadow-sm text-center">
                            <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php elseif ($orderData): ?>
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                                <h4 class="card-title text-info"><i class="fas fa-box-open mr-2"></i> Đơn hàng #<?= htmlspecialchars($orderData['order_code']) ?></h4>
                            </div>
                            <div class="card-body">
                                <div class="row mb-4">
                                    <div class="col-sm-6">
                                        <p class="mb-1 text-muted">Trạng thái đơn hàng:</p>
                                        <h5 class="mb-3"><?= getStatusBadge($orderData['status']) ?></h5>
                                        
                                        <p class="mb-1 text-muted">Trạng thái thanh toán:</p>
                                        <h5 class="mb-0"><?= getPaymentStatusBadge($orderData['payment_status'], $orderData['status']) ?></h5>
                                    </div>
                                    <div class="col-sm-6">
                                        <p class="mb-1 text-muted">Ngày đặt:</p>
                                        <h6 class="mb-3"><?= date('d/m/Y H:i', $orderData['ngaytao']) ?></h6>
                                        
                                        <p class="mb-1 text-muted">Tổng tiền:</p>
                                        <h4 class="text-danger font-weight-bold"><?= number_format($orderData['tongtien'], 0, ',', '.') ?>&nbsp;đ</h4>
                                    </div>
                                </div>
                                
                                <hr>
                                <h5 class="mb-3 mt-4">Thông tin giao hàng</h5>
                                <p class="mb-1"><strong>Người nhận:</strong> <?= htmlspecialchars($orderData['tenkh']) ?></p>
                                <p class="mb-1"><strong>SĐT:</strong> <?= htmlspecialchars($orderData['sdt']) ?></p>
                                <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($orderData['email']) ?></p>
                                <p class="mb-1"><strong>Địa chỉ:</strong> <?= htmlspecialchars($orderData['diachi']) ?></p>
                                <?php if (!empty($orderData['note'])): ?>
                                    <p class="mb-0 text-muted"><em>Ghi chú: <?= htmlspecialchars($orderData['note']) ?></em></p>
                                <?php endif; ?>

                                <hr>
                                <h5 class="mb-3 mt-4">Sản phẩm đã đặt</h5>
                                <div class="table-responsive">
                                    <table class="table table-borderless">
                                        <tbody>
                                            <?php foreach ($orderDetails as $item): ?>
                                                <tr>
                                                    <td width="80">
                                                        <img src="<?= BASE_URL ?>admin/<?= htmlspecialchars($item['anhdaidien']) ?>" class="img-fluid rounded border" alt="<?= htmlspecialchars($item['tensp']) ?>">
                                                    </td>
                                                    <td>
                                                        <h6 class="mb-1"><?= htmlspecialchars($item['tensp']) ?></h6>
                                                        <small class="text-muted">
                                                            Số lượng: <?= htmlspecialchars($item['quantity']) ?>
                                                            <?php if (!empty($item['size'])): ?> | Size: <?= htmlspecialchars($item['size']) ?><?php endif; ?>
                                                        </small>
                                                    </td>
                                                    <td class="text-right align-middle">
                                                        <strong><?= number_format($item['price'], 0, ',', '.') ?>&nbsp;đ</strong>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>
<?php include BASE_PATH . 'includes/footer.php'; ?>
