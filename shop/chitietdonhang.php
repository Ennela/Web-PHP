<?php
require_once dirname(__DIR__) . '/config.php';
session_start();
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
date_default_timezone_set('Asia/Ho_Chi_Minh');

include BASE_PATH . 'includes/connect.php';
require_once BASE_PATH . 'includes/inventory_helper.php';

// ===== BẮT BUỘC ĐĂNG NHẬP =====
if (empty($_SESSION['dangnhap']) || empty($_SESSION['makh'])) {
    echo "<script>alert('Vui lòng đăng nhập để xem chi tiết đơn hàng!'); window.location.href='" . BASE_URL . "auth/dangnhap.php';</script>";
    exit;
}

$makh = intval($_SESSION['makh']);
$orderId = intval($_GET['id'] ?? 0);

if ($orderId <= 0) {
    echo "<script>alert('Mã đơn hàng không hợp lệ!'); window.location.href='" . BASE_URL . "shop/tradonhang.php';</script>";
    exit;
}

// ===== CHỈ CHO XEM ĐƠN HÀNG CỦA CHÍNH USER =====
$sql = "SELECT * FROM `oder` WHERE `id` = $orderId AND `makh` = $makh";
$result = mysqli_query($con, $sql);
$order = mysqli_fetch_assoc($result);

if (!$order) {
    echo "<script>alert('Bạn không có quyền xem đơn hàng này!'); window.location.href='" . BASE_URL . "shop/tradonhang.php';</script>";
    exit;
}

// Fetch order details with product info
$sqlDetail = "SELECT oc.*, sp.tensp, sp.anhdaidien 
              FROM `oder_chitiet` oc 
              LEFT JOIN `tbl_qlsanpham` sp ON oc.masp = sp.masp 
              WHERE oc.madonhang = $orderId";
$resultDetail = mysqli_query($con, $sqlDetail);
$orderItems = [];
while ($row = mysqli_fetch_assoc($resultDetail)) {
    $orderItems[] = $row;
}

// Status definitions
$allStatuses = [
    'PENDING'   => ['label' => 'Chờ xử lý',      'icon' => 'fa-clock',        'color' => '#f59e0b', 'bg' => '#fef3c7', 'step' => 1],
    'CONFIRMED' => ['label' => 'Đã xác nhận',    'icon' => 'fa-check-circle', 'color' => '#3b82f6', 'bg' => '#dbeafe', 'step' => 2],
    'SHIPPING'  => ['label' => 'Đang giao hàng', 'icon' => 'fa-truck',        'color' => '#8b5cf6', 'bg' => '#ede9fe', 'step' => 3],
    'DELIVERED' => ['label' => 'Đã giao hàng',   'icon' => 'fa-box-open',     'color' => '#10b981', 'bg' => '#d1fae5', 'step' => 4],
];

$currentStatus = strtoupper($order['status'] ?? 'PENDING');
$isCancelled = ($currentStatus === 'CANCELLED');
$currentStep = $isCancelled ? 0 : ($allStatuses[$currentStatus]['step'] ?? 1);

include BASE_PATH . 'includes/header.php';
?>

<style>
    /* ===== Order Detail Styles ===== */
    .detail-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
        padding: 55px 0 50px;
        position: relative;
        overflow: hidden;
    }
    .detail-hero::before {
        content: '';
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
                    radial-gradient(circle at 80% 50%, rgba(16, 185, 129, 0.08) 0%, transparent 50%);
    }
    .detail-hero h2 {
        font-family: 'Montserrat', sans-serif;
        font-weight: 700;
        color: #fff;
        font-size: 1.8rem;
        position: relative;
    }
    .detail-hero p {
        color: #94a3b8;
        position: relative;
    }
    .back-link {
        color: #94a3b8;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s;
        position: relative;
        font-size: 0.9rem;
    }
    .back-link:hover {
        color: #fff;
        text-decoration: none;
    }

    /* Status Timeline */
    .timeline-section {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        border: 1px solid #e2e8f0;
        padding: 40px 30px;
        margin-top: -35px;
        position: relative;
        z-index: 5;
    }
    .status-timeline {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        position: relative;
        padding: 0;
        margin: 30px 0 0;
    }
    .status-timeline::before {
        content: '';
        position: absolute;
        top: 24px;
        left: 30px;
        right: 30px;
        height: 4px;
        background: #e2e8f0;
        border-radius: 2px;
        z-index: 0;
    }
    .status-timeline::after {
        content: '';
        position: absolute;
        top: 24px;
        left: 30px;
        height: 4px;
        background: linear-gradient(90deg, #3b82f6, #10b981);
        border-radius: 2px;
        z-index: 1;
        transition: width 1s ease;
    }
    .timeline-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 2;
        flex: 1;
    }
    .timeline-dot {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        margin-bottom: 12px;
        transition: all 0.5s ease;
        border: 3px solid #e2e8f0;
        background: #fff;
        color: #94a3b8;
    }
    .timeline-dot.active {
        border-color: #3b82f6;
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: #fff;
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.35);
        animation: dotPulse 2s ease-in-out infinite;
    }
    .timeline-dot.completed {
        border-color: #10b981;
        background: linear-gradient(135deg, #10b981, #059669);
        color: #fff;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.25);
    }
    .timeline-dot.cancelled {
        border-color: #ef4444;
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: #fff;
        box-shadow: 0 4px 15px rgba(239, 68, 68, 0.25);
    }
    @keyframes dotPulse {
        0%, 100% { box-shadow: 0 4px 15px rgba(59, 130, 246, 0.35); }
        50% { box-shadow: 0 4px 25px rgba(59, 130, 246, 0.5); }
    }
    .timeline-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #94a3b8;
        text-align: center;
        max-width: 100px;
        line-height: 1.3;
    }
    .timeline-label.active {
        color: #3b82f6;
    }
    .timeline-label.completed {
        color: #10b981;
    }

    /* Cancelled banner */
    .cancelled-banner {
        background: linear-gradient(135deg, #fef2f2, #fee2e2);
        border: 1px solid #fecaca;
        border-radius: 12px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        margin-top: 20px;
    }
    .cancelled-banner i {
        font-size: 2rem;
        color: #ef4444;
    }
    .cancelled-banner h5 {
        color: #991b1b;
        font-weight: 700;
        margin: 0 0 4px;
    }
    .cancelled-banner p {
        color: #b91c1c;
        margin: 0;
        font-size: 0.9rem;
    }

    /* Info cards */
    .info-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        border: 1px solid #e2e8f0;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    .info-card:hover {
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
    }
    .info-card-header {
        padding: 18px 24px;
        border-bottom: 1px solid #f1f5f9;
        font-family: 'Montserrat', sans-serif;
        font-weight: 700;
        font-size: 1rem;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .info-card-header i {
        color: #3b82f6;
    }
    .info-card-body {
        padding: 20px 24px;
    }
    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #f8fafc;
    }
    .info-row:last-child { border-bottom: none; }
    .info-label {
        color: #64748b;
        font-size: 0.88rem;
    }
    .info-value {
        font-weight: 600;
        color: #1e293b;
        text-align: right;
    }

    /* Product table */
    .product-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    .product-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 0.8rem;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.5px;
        padding: 14px 16px;
        border-bottom: 2px solid #e2e8f0;
    }
    .product-table tbody td {
        padding: 16px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        font-size: 0.92rem;
    }
    .product-table tbody tr:hover {
        background: #f8fafc;
    }
    .product-table tbody tr:last-child td {
        border-bottom: none;
    }
    .product-thumb {
        width: 56px;
        height: 56px;
        border-radius: 10px;
        object-fit: cover;
        border: 2px solid #e2e8f0;
    }
    .product-name {
        font-weight: 600;
        color: #1e293b;
    }
    .total-row td {
        background: #f0f9ff;
        font-weight: 700;
        font-size: 1.05rem;
    }
    .total-amount {
        color: #2563eb;
        font-size: 1.2rem;
    }

    /* Section styles */
    .detail-section {
        background: #f1f5f9;
        padding: 30px 0 60px;
    }

    /* Button styles */
    .btn-detail {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 10px 24px;
        font-weight: 600;
        font-size: 0.88rem;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-detail:hover {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #fff;
        text-decoration: none;
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(37, 99, 235, 0.3);
    }

    /* Animations */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-in {
        animation: fadeIn 0.6s ease forwards;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .detail-hero { padding: 40px 0 35px; }
        .detail-hero h2 { font-size: 1.3rem; }
        .detail-hero p { font-size: 0.88rem; }
        .timeline-section { padding: 24px 16px; margin-top: -25px; }
        .status-timeline {
            flex-direction: column;
            align-items: flex-start;
            gap: 20px;
            padding-left: 20px;
        }
        .status-timeline::before,
        .status-timeline::after {
            display: none;
        }
        .timeline-step {
            flex-direction: row;
            gap: 16px;
        }
        .timeline-dot {
            width: 40px;
            height: 40px;
            font-size: 0.9rem;
            margin-bottom: 0;
            flex-shrink: 0;
        }
        .timeline-label {
            text-align: left;
            max-width: none;
        }
        .info-card-header {
            padding: 14px 16px;
            font-size: 0.9rem;
        }
        .info-card-body {
            padding: 14px 16px;
        }
        .info-row {
            flex-direction: column;
            gap: 4px;
            align-items: flex-start;
        }
        .info-value {
            text-align: left;
        }
        /* Product table → stacked cards on mobile */
        .product-table {
            min-width: 0 !important;
        }
        .product-table thead {
            display: none;
        }
        .product-table tbody tr {
            display: block;
            padding: 12px 16px;
            border-bottom: 1px solid #e2e8f0;
            position: relative;
        }
        .product-table tbody tr:last-child {
            border-bottom: none;
        }
        .product-table tbody td {
            display: block;
            text-align: right !important;
            padding: 4px 0;
            border-bottom: none;
            font-size: 0.88rem;
        }
        .product-table tbody td::before {
            content: attr(data-label);
            float: left;
            font-weight: 700;
            color: #475569;
            font-size: 0.82rem;
        }
        .product-table tbody td:first-child {
            text-align: left !important;
            margin-bottom: 4px;
        }
        .product-table tbody td:first-child::before {
            display: none;
        }
        .product-thumb {
            width: 48px;
            height: 48px;
        }
        .total-row td {
            display: block;
            text-align: right !important;
        }
        .total-row td[colspan] {
            display: none;
        }
        .total-row td:last-child::before {
            content: 'Tổng cộng:';
            float: left;
            font-weight: 700;
            color: #1e293b;
        }
        /* Action buttons */
        .text-center.mt-5 {
            display: flex;
            flex-direction: column;
            gap: 12px;
            align-items: stretch;
        }
        .text-center.mt-5 a,
        .text-center.mt-5 button {
            width: 100%;
            justify-content: center;
            text-align: center;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }
    }
</style>

<main class="page landing-page" style="padding-top: 80px;">

    <!-- Hero -->
    <div class="detail-hero">
        <div class="container">
            <a href="<?= BASE_URL ?>shop/tradonhang.php" class="back-link">
                <i class="fas fa-arrow-left mr-2"></i> Quay lại danh sách đơn hàng
            </a>
            <h2 class="mt-3">
                <i class="fas fa-receipt mr-2"></i> 
                Đơn hàng <span style="color: #60a5fa;">#<?= !empty($order['order_code']) ? $order['order_code'] : $order['id'] ?></span>
            </h2>
            <p class="mt-1">Ngày đặt: <?= date('d/m/Y H:i', $order['ngaytao']) ?></p>
        </div>
    </div>

    <!-- Status Timeline -->
    <div class="container">
        <div class="timeline-section animate-in">
            <h6 style="font-weight: 700; color: #1e293b; margin-bottom: 0;">
                <i class="fas fa-stream mr-2" style="color: #3b82f6;"></i> Trạng thái đơn hàng
            </h6>

            <?php if ($isCancelled): ?>
                <div class="cancelled-banner">
                    <i class="fas fa-times-circle"></i>
                    <div>
                        <h5>Đơn hàng đã bị hủy</h5>
                        <p>Đơn hàng này đã bị hủy. Vui lòng liên hệ hotline 0394680113 để biết thêm chi tiết.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="status-timeline">
                    <?php 
                    $stepIndex = 0;
                    foreach ($allStatuses as $key => $info): 
                        $stepIndex++;
                        $isCompleted = $stepIndex < $currentStep;
                        $isActive = $stepIndex === $currentStep;
                        $dotClass = $isCompleted ? 'completed' : ($isActive ? 'active' : '');
                        $labelClass = $isCompleted ? 'completed' : ($isActive ? 'active' : '');
                    ?>
                        <div class="timeline-step">
                            <div class="timeline-dot <?= $dotClass ?>">
                                <i class="fas <?= $isCompleted ? 'fa-check' : $info['icon'] ?>"></i>
                            </div>
                            <span class="timeline-label <?= $labelClass ?>"><?= $info['label'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <!-- Progress bar overlay -->
                <style>
                    .status-timeline::after {
                        width: calc(<?= max(0, ($currentStep - 1)) / (count($allStatuses) - 1) * 100 ?>% - 0px) !important;
                    }
                </style>
            <?php endif; ?>
        </div>
    </div>

    <!-- Details -->
    <div class="detail-section">
        <div class="container">
            <div class="row g-4 mt-2">

                <!-- Customer Info -->
                <div class="col-md-5">
                    <div class="info-card animate-in" style="animation-delay: 0.1s;">
                        <div class="info-card-header">
                            <i class="fas fa-user-circle"></i> Thông tin Nhận hàng
                        </div>
                        <div class="info-card-body">
                            <div class="info-row">
                                <span class="info-label"><i class="far fa-user mr-2"></i>Họ tên</span>
                                <span class="info-value"><?= htmlspecialchars($order['tenkh']) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-phone-alt mr-2"></i>Điện thoại</span>
                                <span class="info-value"><?= htmlspecialchars($order['sdt']) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-map-marker-alt mr-2"></i>Địa chỉ</span>
                                <span class="info-value"><?= htmlspecialchars($order['diachi']) ?></span>
                            </div>
                            <?php if (!empty($order['note'])): ?>
                            <div class="info-row">
                                <span class="info-label"><i class="far fa-sticky-note mr-2"></i>Ghi chú</span>
                                <span class="info-value"><?= htmlspecialchars($order['note']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Payment Summary -->
                    <div class="info-card animate-in mt-4" style="animation-delay: 0.2s;">
                        <div class="info-card-header">
                            <i class="fas fa-wallet"></i> Thanh toán
                        </div>
                        <div class="info-card-body">
                            <div class="info-row">
                                <span class="info-label">Phương thức</span>
                                <span class="info-value">
                                    <?= !empty($order['vnpay_tranId']) ? '<i class="fas fa-credit-card mr-1" style="color:#3b82f6"></i> VNPAY' : '<i class="fas fa-money-bill-wave mr-1" style="color:#10b981"></i> COD' ?>
                                </span>
                            </div>
                            <?php if (!empty($order['vnpay_tranId'])): ?>
                            <div class="info-row">
                                <span class="info-label">Mã giao dịch</span>
                                <span class="info-value" style="color: #3b82f6;"><?= htmlspecialchars($order['vnpay_tranId']) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="info-row" style="background: #f0f9ff; margin: 10px -24px -20px; padding: 16px 24px; border-radius: 0 0 16px 16px;">
                                <span class="info-label" style="font-weight: 700; font-size: 1rem; color: #1e293b;">Tổng tiền</span>
                                <span class="info-value" style="color: #2563eb; font-size: 1.3rem;"><?= number_format($order['tongtien'], 0, ',', '.') ?>&nbsp;đ</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="col-md-7">
                    <div class="info-card animate-in" style="animation-delay: 0.15s;">
                        <div class="info-card-header">
                            <i class="fas fa-shopping-bag"></i> Sản phẩm đã đặt (<?= count($orderItems) ?> sản phẩm)
                        </div>
                        <div class="info-card-body" style="padding: 0;">
                            <div class="table-responsive" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                            <table class="product-table">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;"></th>
                                        <th style="min-width: 150px; white-space: nowrap;">Sản phẩm</th>
                                        <th style="text-align: center; white-space: nowrap;">Size</th>
                                        <th style="text-align: center; white-space: nowrap;">SL</th>
                                        <th style="text-align: right; white-space: nowrap;">Đơn giá</th>
                                        <th style="text-align: right; white-space: nowrap;">Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderItems as $item): ?>
                                    <tr>
                                        <td data-label="">
                                            <?php if (!empty($item['anhdaidien'])): ?>
                                                <img src="<?= BASE_URL ?>admin/<?= htmlspecialchars($item['anhdaidien']) ?>" 
                                                     class="product-thumb" alt="<?= htmlspecialchars($item['tensp'] ?? '') ?>">
                                            <?php else: ?>
                                                <div class="product-thumb" style="display: flex; align-items: center; justify-content: center; background: #f1f5f9;">
                                                    <i class="fas fa-shoe-prints" style="color: #94a3b8;"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Sản phẩm">
                                            <span class="product-name"><?= htmlspecialchars($item['tensp'] ?? 'Sản phẩm #' . $item['masp']) ?></span>
                                        </td>
                                        <td data-label="Size" style="text-align: center;">
                                            <?php if (!empty($item['size'])): ?>
                                            <span style="background: #111; color: #fff; padding: 3px 10px; border-radius: 4px; font-weight: 700; font-size: 0.85rem;">
                                                <?= $item['size'] ?>
                                            </span>
                                            <?php else: ?>
                                            <span style="color: #94a3b8;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Số lượng" style="text-align: center;">
                                            <span style="background: #f1f5f9; padding: 4px 12px; border-radius: 8px; font-weight: 600;">
                                                <?= $item['quantity'] ?>
                                            </span>
                                        </td>
                                        <td data-label="Đơn giá" style="text-align: right;">
                                            <?= number_format($item['price'], 0, ',', '.') ?>&nbsp;đ
                                        </td>
                                        <td data-label="Thành tiền" style="text-align: right; font-weight: 600; color: #1e293b;">
                                            <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>&nbsp;đ
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="total-row">
                                        <td colspan="5" style="text-align: right;">Tổng cộng:</td>
                                        <td style="text-align: right;" class="total-amount">
                                            <?= number_format($order['tongtien'], 0, ',', '.') ?>&nbsp;đ
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Action + Back buttons -->
            <div class="text-center mt-5 animate-in" style="animation-delay: 0.3s;">
                <?php if ($currentStatus === 'SHIPPING'): ?>
                <button class="btn-action-detail btn-receive-detail" onclick="orderDetailAction(<?= $order['id'] ?>, 'receive', this)">
                    <i class="fas fa-check-circle mr-2"></i> Xác nhận đã nhận hàng
                </button>
                <?php endif; ?>
                <?php if (in_array($currentStatus, ['PENDING', 'CONFIRMED'])): ?>
                <button class="btn-action-detail btn-cancel-detail" onclick="orderDetailAction(<?= $order['id'] ?>, 'cancel', this)">
                    <i class="fas fa-times mr-2"></i> Hủy đơn hàng
                </button>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>shop/tradonhang.php" class="btn-detail" style="padding: 14px 40px; font-size: 1rem;">
                    <i class="fas fa-arrow-left mr-2"></i> Quay lại danh sách
                </a>
                <a href="<?= BASE_URL ?>home/trangchu.php" 
                   style="background: transparent; color: #3b82f6; border: 2px solid #3b82f6; border-radius: 10px; padding: 12px 40px; font-weight: 600; font-size: 1rem; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; margin-left: 12px; transition: all 0.3s ease;">
                    <i class="fas fa-home"></i> Về trang chủ
                </a>
            </div>

        </div>
    </div>

</main>

<style>
.btn-action-detail {
    border: none;
    border-radius: 10px;
    padding: 14px 32px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-right: 12px;
    margin-bottom: 8px;
}
.btn-receive-detail {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff;
}
.btn-receive-detail:hover {
    background: linear-gradient(135deg, #059669, #047857);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
}
.btn-cancel-detail {
    background: #fff;
    color: #ef4444;
    border: 2px solid #fecaca;
}
.btn-cancel-detail:hover {
    background: #fef2f2;
    border-color: #ef4444;
    transform: translateY(-2px);
}
.btn-action-detail:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}
</style>

<?php include BASE_PATH . 'includes/footer.php'; ?>

<script>
function orderDetailAction(orderId, action, btn) {
    let confirmMsg = action === 'receive' 
        ? 'Xác nhận bạn đã nhận được hàng thành công?' 
        : 'Bạn chắc chắn muốn hủy đơn hàng này? Hành động này không thể hoàn tác.';
    
    if (!confirm(confirmMsg)) return;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Đang xử lý...';
    
    fetch('<?= BASE_URL ?>shop/api_order_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: action, order_id: orderId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || 'Có lỗi xảy ra');
            btn.disabled = false;
            btn.innerHTML = action === 'receive' 
                ? '<i class="fas fa-check-circle mr-2"></i> Xác nhận đã nhận hàng'
                : '<i class="fas fa-times mr-2"></i> Hủy đơn hàng';
        }
    })
    .catch(() => {
        alert('Lỗi kết nối. Vui lòng thử lại.');
        btn.disabled = false;
        btn.innerHTML = action === 'receive' 
            ? '<i class="fas fa-check-circle mr-2"></i> Xác nhận đã nhận hàng'
            : '<i class="fas fa-times mr-2"></i> Hủy đơn hàng';
    });
}
</script>
