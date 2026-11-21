<?php
require_once dirname(__DIR__) . '/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once BASE_PATH . 'includes/sepay.php';
sepay_require_configuration();
include BASE_PATH . 'includes/connect.php';

// Chỉ đơn vừa đặt trong phiên này (id lưu ở session, không nhận từ URL → không thao tác được đơn người khác).
$orderId = (int)($_SESSION['sepay_order_id'] ?? 0);
$order = $orderId > 0 ? sepay_find_order($con, $orderId) : null;
if (!$order) {
    unset($_SESSION['sepay_order_id']);
    app_redirect(BASE_URL . 'shop/giohang.php');
}
sepay_expire_stale($con);

// SePay chuyển về: URL không có chữ ký nên chỉ được HỦY đơn của chính phiên, không bao giờ đánh dấu đã thanh toán.
$back = is_string($_GET['r'] ?? null) ? $_GET['r'] : '';
if ($back === 'cancel' || $back === 'error') {
    sepay_cancel($con, $orderId);
}
$order = sepay_find_order($con, $orderId);
$state = sepay_order_state($order);

if (isset($_GET['status'])) { // trang "đang xác nhận" hỏi định kỳ, chờ IPN
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['state' => $state]);
    exit;
}

$redirectToGateway = $state === 'pending' && $back === '';
include BASE_PATH . 'includes/header.php';
$failText = $back === 'cancel' ? 'Bạn đã hủy thanh toán.' : ($back === 'error' ? 'Thanh toán không thành công.' : 'Đơn hàng đã hết thời gian thanh toán.');
?>
<main class="page landing-page" style="padding-top: 100px; min-height: 80vh; background: #f8f9fa;">
    <section class="clean-block clean-info dark">
        <div class="container d-flex justify-content-center">
            <div class="card shadow-lg border-0" style="width: 100%; max-width: 520px; border-radius: 12px;">
                <div class="card-body text-center p-4 p-sm-5">
                    <?php if ($redirectToGateway): ?>
                        <h3 class="text-info fw-bold mb-2">Đang chuyển sang SePay…</h3>
                        <p class="text-muted">Đơn hàng số <?= e($order['order_code']) ?> — <?= e(number_format((int)$order['tongtien'], 0, ',', '.')) ?> đ</p>
                        <form id="sepay-checkout-form" method="post" action="<?= e(sepay_checkout_url()) ?>">
                            <?php foreach (sepay_form_fields($order) as $name => $value): ?>
                            <input type="hidden" name="<?= e($name) ?>" value="<?= e($value) ?>">
                            <?php endforeach; ?>
                            <div class="d-grid"><button type="submit" class="btn btn-info text-white">Tiếp tục thanh toán</button></div>
                        </form>
                    <?php else: ?>
                    <?php if ($state === 'pending'): ?>
                        <div class="spinner-border text-info mb-3" role="status" aria-hidden="true"></div>
                        <h3 class="text-info fw-bold mb-1">Đang xác nhận thanh toán</h3>
                        <p class="text-muted mb-4">Hệ thống đang chờ SePay xác nhận giao dịch. Trang sẽ tự cập nhật.</p>
                    <?php elseif ($state === 'paid'): ?>
                        <div class="mb-3"><i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i></div>
                        <h3 class="text-success fw-bold mb-1">Thanh Toán Thành Công!</h3>
                        <p class="text-muted mb-4">Email xác nhận đơn hàng sẽ được gửi trong ít phút.</p>
                    <?php else: ?>
                        <div class="mb-3"><i class="fas fa-times-circle text-danger" style="font-size: 4rem;"></i></div>
                        <h3 class="text-danger fw-bold mb-1">Giao Dịch Không Thành Công</h3>
                        <p class="text-muted mb-4"><?= e($failText) ?> Đơn hàng đã được hủy và tồn kho được hoàn lại.</p>
                    <?php endif; ?>
                    <div class="text-start bg-light p-3 rounded mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Mã đơn hàng:</span>
                            <span class="fw-bold">#<?= e($order['order_code']) ?></span>
                        </div>
                        <?php if ($state === 'paid'): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Mã giao dịch:</span>
                            <span class="fw-bold text-break ms-3"><?= e(substr((string)$order['vnpay_tranId'], 6) ?: 'N/A') ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Phương thức:</span>
                            <span class="fw-bold">Cổng thanh toán SePay</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Thời gian đặt:</span>
                            <span class="fw-bold"><?= e(date('d/m/Y H:i', (int)$order['ngaytao'])) ?></span>
                        </div>
                        <div class="border-top pt-2 mt-2 d-flex justify-content-between align-items-center">
                            <span class="text-muted">SỐ TIỀN:</span>
                            <span class="h4 fw-bold text-info m-0"><?= e(number_format((int)$order['tongtien'], 0, ',', '.')) ?> VND</span>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <?php if (!empty($_SESSION['dangnhap'])): ?>
                        <a href="<?= e(BASE_URL) ?>shop/tradonhang.php" class="btn btn-outline-info btn-lg fw-bold" style="font-size: 1rem;"><i class="fas fa-list-alt me-2"></i>XEM ĐƠN HÀNG CỦA TÔI</a>
                        <?php endif; ?>
                        <a href="<?= e(BASE_URL) ?>home/trangchu.php" class="btn btn-info btn-lg fw-bold text-white" style="font-size: 1rem;">TIẾP TỤC MUA SẮM</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>
<script nonce="<?= e(csp_nonce()) ?>">
(function () {
    const form = document.getElementById('sepay-checkout-form');
    if (form) { form.submit(); return; }
    if (<?= js_value($state) ?> !== 'pending') return;
    const poll = setInterval(async () => {
        try {
            const res = await fetch('thanhtoansepay.php?status=1', { cache: 'no-store', credentials: 'same-origin' });
            if (res.ok && (await res.json()).state !== 'pending') { clearInterval(poll); location.reload(); }
        } catch (e) { /* mạng chập chờn: thử lại lần sau */ }
    }, 3000);
})();
</script>
<?php include BASE_PATH . 'includes/footer.php'; ?>
