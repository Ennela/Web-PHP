<?php
require_once dirname(__DIR__) . '/config.php';
session_start();
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
date_default_timezone_set('Asia/Ho_Chi_Minh');

include BASE_PATH . 'includes/connect.php';
require_once BASE_PATH . 'includes/inventory_helper.php';

// Tự động chuyển đơn SHIPPING > 15 ngày sang DELIVERED
autoCompleteShippingOrders($con);

// ===== BẮT BUỘC ĐĂNG NHẬP =====
if (empty($_SESSION['dangnhap']) || empty($_SESSION['makh'])) {
    echo "<script>alert('Vui lòng đăng nhập để tra cứu đơn hàng!'); window.location.href='" . BASE_URL . "auth/dangnhap.php';</script>";
    exit;
}

$makh = intval($_SESSION['makh']);

// Map status to Vietnamese labels and colors
function getStatusInfo($status) {
    $map = [
        'PENDING'    => ['label' => 'Chờ xử lý',      'color' => '#f59e0b', 'bg' => '#fef3c7', 'icon' => 'fa-clock'],
        'CONFIRMED'  => ['label' => 'Đã xác nhận',    'color' => '#3b82f6', 'bg' => '#dbeafe', 'icon' => 'fa-check-circle'],
        'SHIPPING'   => ['label' => 'Đang giao hàng', 'color' => '#8b5cf6', 'bg' => '#ede9fe', 'icon' => 'fa-truck'],
        'DELIVERED'  => ['label' => 'Đã giao hàng',   'color' => '#10b981', 'bg' => '#d1fae5', 'icon' => 'fa-box-open'],
        'CANCELLED'  => ['label' => 'Đã hủy',         'color' => '#ef4444', 'bg' => '#fee2e2', 'icon' => 'fa-times-circle'],
    ];
    return $map[strtoupper($status)] ?? $map['PENDING'];
}

// ===== CHỈ LẤY ĐƠN HÀNG CỦA USER ĐANG ĐĂNG NHẬP =====
$orders = [];
$filterStatus = $_GET['status'] ?? '';

$sql = "SELECT * FROM `oder` WHERE `makh` = $makh";
if (!empty($filterStatus) && in_array($filterStatus, ['PENDING', 'CONFIRMED', 'SHIPPING', 'DELIVERED', 'CANCELLED'])) {
    $sql .= " AND `status` = '" . mysqli_real_escape_string($con, $filterStatus) . "'";
}
$sql .= " ORDER BY `ngaytao` DESC";

$result = mysqli_query($con, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $orders[] = $row;
}

include BASE_PATH . 'includes/header.php';
?>

<style>
    /* ===== Order Tracking Page Styles ===== */
    .tracking-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
        padding: 60px 0 40px;
        position: relative;
        overflow: hidden;
    }
    .tracking-hero::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle at 30% 50%, rgba(59, 130, 246, 0.08) 0%, transparent 50%),
                    radial-gradient(circle at 70% 80%, rgba(139, 92, 246, 0.06) 0%, transparent 50%);
        animation: heroFloat 20s ease-in-out infinite;
    }
    @keyframes heroFloat {
        0%, 100% { transform: translate(0, 0); }
        50% { transform: translate(-20px, -10px); }
    }
    .tracking-hero h2 {
        font-family: 'Montserrat', sans-serif;
        font-weight: 700;
        color: #fff;
        font-size: 2rem;
        position: relative;
        z-index: 1;
    }
    .tracking-hero p {
        color: #94a3b8;
        font-size: 1rem;
        position: relative;
        z-index: 1;
    }
    .user-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(59, 130, 246, 0.15);
        border: 1px solid rgba(59, 130, 246, 0.3);
        border-radius: 50px;
        padding: 6px 16px;
        color: #93c5fd;
        font-size: 0.88rem;
        font-weight: 600;
        position: relative;
        z-index: 1;
    }

    /* Filter tabs */
    .filter-bar {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        border: 1px solid #e2e8f0;
        padding: 16px 24px;
        margin-top: -25px;
        position: relative;
        z-index: 5;
        display: flex;
        justify-content: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .filter-tab {
        padding: 8px 20px;
        border-radius: 50px;
        border: 2px solid #e2e8f0;
        background: #fff;
        color: #64748b;
        font-weight: 600;
        font-size: 0.85rem;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .filter-tab:hover {
        border-color: #3b82f6;
        color: #3b82f6;
        text-decoration: none;
        background: #eff6ff;
    }
    .filter-tab.active {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: #fff;
        border-color: #3b82f6;
    }
    .filter-tab.active:hover {
        color: #fff;
    }
    .filter-count {
        background: rgba(0,0,0,0.1);
        padding: 1px 8px;
        border-radius: 50px;
        font-size: 0.75rem;
    }
    .filter-tab.active .filter-count {
        background: rgba(255,255,255,0.25);
    }

    /* Order cards */
    .orders-section {
        padding: 30px 0 60px;
        background: #f1f5f9;
        min-height: 40vh;
    }
    .order-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        border: 1px solid #e2e8f0;
        overflow: hidden;
        transition: all 0.3s ease;
        margin-bottom: 20px;
    }
    .order-card:hover {
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
        transform: translateY(-3px);
        border-color: #cbd5e1;
    }
    .order-card-header {
        padding: 20px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #f1f5f9;
    }
    .order-id {
        font-family: 'Montserrat', sans-serif;
        font-weight: 700;
        font-size: 1.1rem;
        color: #1e293b;
    }
    .order-id span {
        color: #3b82f6;
    }
    .order-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.82rem;
        letter-spacing: 0.3px;
    }
    .order-card-body {
        padding: 20px 24px;
    }
    .order-info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #f8fafc;
    }
    .order-info-row:last-child {
        border-bottom: none;
    }
    .order-info-label {
        color: #64748b;
        font-size: 0.88rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .order-info-value {
        font-weight: 600;
        color: #1e293b;
        font-size: 0.92rem;
    }
    .order-total {
        color: #2563eb !important;
        font-size: 1.1rem !important;
    }
    .order-card-footer {
        padding: 16px 24px;
        background: #f8fafc;
        display: flex;
        justify-content: flex-end;
    }
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

    /* Action buttons */
    .btn-action {
        border: none;
        border-radius: 10px;
        padding: 10px 20px;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-right: 8px;
    }
    .btn-receive {
        background: linear-gradient(135deg, #10b981, #059669);
        color: #fff;
    }
    .btn-receive:hover {
        background: linear-gradient(135deg, #059669, #047857);
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    }
    .btn-cancel-order {
        background: #fff;
        color: #ef4444;
        border: 2px solid #fecaca;
    }
    .btn-cancel-order:hover {
        background: #fef2f2;
        border-color: #ef4444;
        transform: translateY(-1px);
    }
    .btn-action:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none !important;
    }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    .empty-state i {
        font-size: 4rem;
        color: #cbd5e1;
        margin-bottom: 20px;
    }
    .empty-state h4 {
        color: #475569;
        font-weight: 700;
        margin-bottom: 8px;
    }
    .empty-state p {
        color: #94a3b8;
        font-size: 0.95rem;
    }

    /* Animations */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in-up {
        animation: fadeInUp 0.5s ease forwards;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .tracking-hero { padding: 40px 0 30px; }
        .tracking-hero h2 { font-size: 1.5rem; }
        .order-card-header { flex-direction: column; align-items: flex-start; gap: 10px; }
        .order-info-row { flex-direction: column; align-items: flex-start; gap: 4px; }
        .filter-bar { gap: 6px; }
        .filter-tab { padding: 6px 14px; font-size: 0.8rem; }
    }
</style>

<main class="page landing-page" style="padding-top: 80px;">

    <!-- Hero Section -->
    <div class="tracking-hero">
        <div class="container text-center">
            <h2><i class="fas fa-shipping-fast mr-2"></i> Đơn Hàng Của Tôi</h2>
            <p class="mt-2">Theo dõi trạng thái tất cả đơn hàng của bạn</p>
            <div class="mt-3">
                <span class="user-badge">
                    <i class="fas fa-user-circle"></i>
                    <?= htmlspecialchars($_SESSION['dangnhap']) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="container">
        <div class="filter-bar">
            <a href="<?= BASE_URL ?>shop/tradonhang.php" 
               class="filter-tab <?= empty($filterStatus) ? 'active' : '' ?>">
                <i class="fas fa-list"></i> Tất cả
            </a>
            <a href="<?= BASE_URL ?>shop/tradonhang.php?status=PENDING" 
               class="filter-tab <?= $filterStatus === 'PENDING' ? 'active' : '' ?>">
                <i class="fas fa-clock"></i> Chờ xử lý
            </a>
            <a href="<?= BASE_URL ?>shop/tradonhang.php?status=CONFIRMED" 
               class="filter-tab <?= $filterStatus === 'CONFIRMED' ? 'active' : '' ?>">
                <i class="fas fa-check-circle"></i> Đã xác nhận
            </a>
            <a href="<?= BASE_URL ?>shop/tradonhang.php?status=SHIPPING" 
               class="filter-tab <?= $filterStatus === 'SHIPPING' ? 'active' : '' ?>">
                <i class="fas fa-truck"></i> Đang giao
            </a>
            <a href="<?= BASE_URL ?>shop/tradonhang.php?status=DELIVERED" 
               class="filter-tab <?= $filterStatus === 'DELIVERED' ? 'active' : '' ?>">
                <i class="fas fa-box-open"></i> Đã giao
            </a>
            <a href="<?= BASE_URL ?>shop/tradonhang.php?status=CANCELLED" 
               class="filter-tab <?= $filterStatus === 'CANCELLED' ? 'active' : '' ?>">
                <i class="fas fa-times-circle"></i> Đã hủy
            </a>
        </div>
    </div>

    <!-- Results Section -->
    <section class="orders-section">
        <div class="container">

            <?php if (!empty($orders)): ?>
                <h5 style="font-weight: 700; color: #1e293b; margin-bottom: 20px;">
                    <i class="fas fa-list-alt mr-2" style="color: #3b82f6;"></i>
                    Bạn có <span style="color: #3b82f6;"><?= count($orders) ?></span> đơn hàng
                    <?= !empty($filterStatus) ? '(' . getStatusInfo($filterStatus)['label'] . ')' : '' ?>
                </h5>
                <?php foreach ($orders as $index => $order):
                    $statusInfo = getStatusInfo($order['status'] ?? 'PENDING');
                ?>
                    <div class="order-card animate-fade-in-up" style="opacity: 0; animation-delay: <?= $index * 0.08 ?>s;">
                        <div class="order-card-header">
                            <div class="order-id">
                                <i class="fas fa-box" style="color: #3b82f6; margin-right: 6px;"></i>
                                Đơn hàng <span>#<?= !empty($order['order_code']) ? $order['order_code'] : $order['id'] ?></span>
                            </div>
                            <div class="order-status-badge" style="color: <?= $statusInfo['color'] ?>; background: <?= $statusInfo['bg'] ?>;">
                                <i class="fas <?= $statusInfo['icon'] ?>"></i>
                                <?= $statusInfo['label'] ?>
                            </div>
                        </div>
                        <div class="order-card-body">
                            <div class="order-info-row">
                                <span class="order-info-label"><i class="fas fa-map-marker-alt"></i> Địa chỉ giao</span>
                                <span class="order-info-value"><?= htmlspecialchars($order['diachi']) ?></span>
                            </div>
                            <div class="order-info-row">
                                <span class="order-info-label"><i class="far fa-calendar-alt"></i> Ngày đặt</span>
                                <span class="order-info-value"><?= date('d/m/Y H:i', $order['ngaytao']) ?></span>
                            </div>
                            <div class="order-info-row">
                                <span class="order-info-label"><i class="fas fa-coins"></i> Tổng tiền</span>
                                <span class="order-info-value order-total"><?= number_format($order['tongtien'], 0, ',', '.') ?>đ</span>
                            </div>
                        </div>
                        <div class="order-card-footer">
                            <?php
                            $orderStatus = strtoupper($order['status'] ?? 'PENDING');
                            ?>
                            <?php if ($orderStatus === 'SHIPPING'): ?>
                            <button class="btn-action btn-receive" onclick="orderAction(<?= $order['id'] ?>, 'receive', this)">
                                <i class="fas fa-check-circle mr-1"></i> Đã nhận hàng
                            </button>
                            <?php endif; ?>
                            <?php if (in_array($orderStatus, ['PENDING', 'CONFIRMED'])): ?>
                            <button class="btn-action btn-cancel-order" onclick="orderAction(<?= $order['id'] ?>, 'cancel', this)">
                                <i class="fas fa-times mr-1"></i> Hủy đơn
                            </button>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>shop/chitietdonhang.php?id=<?= $order['id'] ?>" class="btn-detail">
                                Xem chi tiết <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php else: ?>
                <div class="empty-state animate-fade-in-up" style="opacity: 0; animation-delay: 0.1s;">
                    <i class="fas fa-box-open"></i>
                    <h4>
                        <?= !empty($filterStatus) ? 'Không có đơn hàng nào ở trạng thái "' . getStatusInfo($filterStatus)['label'] . '"' : 'Bạn chưa có đơn hàng nào' ?>
                    </h4>
                    <p>
                        <?= !empty($filterStatus) ? 'Thử chọn tab khác để xem các đơn hàng.' : 'Hãy mua sắm và quay lại đây để theo dõi nhé!' ?>
                    </p>
                    <?php if (empty($filterStatus)): ?>
                        <a href="<?= BASE_URL ?>shop/shop.php" class="btn-detail mt-3" style="display: inline-flex;">
                            <i class="fas fa-shopping-bag mr-2"></i> Bắt đầu mua sắm
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </section>

</main>

<?php include BASE_PATH . 'includes/footer.php'; ?>

<script>
function orderAction(orderId, action, btn) {
    let confirmMsg = '';
    if (action === 'receive') {
        confirmMsg = 'Xác nhận bạn đã nhận được hàng thành công?';
    } else if (action === 'cancel') {
        confirmMsg = 'Bạn chắc chắn muốn hủy đơn hàng này? Hành động này không thể hoàn tác.';
    }
    
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
            if (action === 'receive') btn.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Đã nhận hàng';
            if (action === 'cancel') btn.innerHTML = '<i class="fas fa-times mr-1"></i> Hủy đơn';
        }
    })
    .catch(() => {
        alert('Lỗi kết nối. Vui lòng thử lại.');
        btn.disabled = false;
        if (action === 'receive') btn.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Đã nhận hàng';
        if (action === 'cancel') btn.innerHTML = '<i class="fas fa-times mr-1"></i> Hủy đơn';
    });
}
</script>
