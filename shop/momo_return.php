<?php
require_once dirname(__DIR__) . '/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once BASE_PATH . 'includes/momo.php';

// MoMo gọi lại bằng GET trên trình duyệt: xác thực chữ ký trước mọi thay đổi.
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    exit('Method not allowed');
}
momo_require_configuration();
$data = array_map(static fn($v) => is_string($v) ? $v : '', $_GET);
if (!momo_verify($data)) {
    http_response_code(403);
    exit('Chữ ký thanh toán không hợp lệ.');
}

include BASE_PATH . 'includes/connect.php';
$result = momo_finalize($con, $data);
include BASE_PATH . 'includes/header.php';

$cards = [
    'paid'      => ['Thanh Toán Thành Công!', 'text-success', 'fa-check-circle', 'Cảm ơn bạn. Email xác nhận đơn hàng sẽ được gửi trong ít phút.'],
    'duplicate' => ['Đơn hàng đã được xử lý', 'text-info', 'fa-info-circle', 'Giao dịch này đã được ghi nhận trước đó.'],
    'failed'    => ['Giao Dịch Thất Bại', 'text-danger', 'fa-times-circle', 'Thanh toán MoMo không thành công hoặc đã bị hủy. Đơn hàng đã được hủy và tồn kho hoàn lại.'],
    'invalid'   => ['Không xác nhận được đơn hàng', 'text-danger', 'fa-question-circle', 'Thông tin thanh toán không khớp với đơn hàng.'],
];
[$title, $color, $icon, $message] = $cards[$result];
?>
<main class="page landing-page" style="padding-top: 100px; min-height: 80vh; background: #f8f9fa;">
    <section class="clean-block clean-info dark">
        <div class="container d-flex justify-content-center align-items-center">
            <div class="card shadow-lg border-0" style="width: 100%; max-width: 500px; border-radius: 12px; overflow: hidden;">
                <div class="card-body text-center p-5">
                    <div class="mb-4"><i class="fas <?= e($icon) ?> <?= e($color) ?>" style="font-size: 4rem;"></i></div>
                    <h3 class="<?= e($color) ?> font-weight-bold mb-3"><?= e($title) ?></h3>
                    <p class="text-muted mb-4"><?= e($message) ?></p>
                    <?php if ($result === 'paid' || $result === 'duplicate'): ?>
                        <p class="text-muted">Mã giao dịch MoMo: <strong><?= e($data['transId'] ?? 'N/A') ?></strong></p>
                    <?php endif; ?>
                    <div class="mt-4">
                        <?php if (!empty($_SESSION['dangnhap'])): ?>
                        <a href="<?= e(BASE_URL) ?>shop/tradonhang.php" class="btn btn-outline-info btn-block btn-lg shadow-sm font-weight-bold mb-2" style="font-size: 1rem;">XEM ĐƠN HÀNG CỦA TÔI</a>
                        <?php endif; ?>
                        <a href="<?= e(BASE_URL) ?>home/trangchu.php" class="btn btn-info btn-block btn-lg shadow-sm font-weight-bold" style="font-size: 1rem;">TIẾP TỤC MUA SẮM</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<?php include BASE_PATH . 'includes/footer.php'; ?>
