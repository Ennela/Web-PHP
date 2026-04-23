<?php
require_once dirname(__DIR__) . '/config.php';
session_start();
include BASE_PATH . 'includes/connect.php';
require_once BASE_PATH . 'includes/inventory_helper.php';
include BASE_PATH . 'includes/header.php';
require_once(BASE_PATH . 'vnpay_php/config.php');

$vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';
$inputData = array();
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}
if (isset($inputData['vnp_SecureHash']))
    unset($inputData['vnp_SecureHash']);
ksort($inputData);
$i = 0;
$hashData = "";
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
}
$secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

$vnpaySuccess = ($secureHash == $vnp_SecureHash && $_GET['vnp_ResponseCode'] == '00');
$orderId = intval($_GET['vnp_TxnRef'] ?? 0);

// ===== KIỂM TRA TRẠNG THÁI ĐƠN HÀNG TRONG DB =====
$order = null;
$duplicatePayment = false;
$orderCancelled = false;
$alreadyProcessed = false;
$cardTitle = '';
$cardColor = '';
$iconClass = '';
$statusMessage = '';

if ($orderId > 0) {
    $orderResult = mysqli_query($con, "SELECT * FROM `oder` WHERE `id` = $orderId LIMIT 1");
    $order = mysqli_fetch_assoc($orderResult);
}

if ($order) {
    $paymentStatus = $order['payment_status'] ?? 'UNPAID';
    $orderStatus = strtoupper($order['status'] ?? 'PENDING');
    
    if ($paymentStatus === 'PAID') {
        // === DUPLICATE TAB: Đơn hàng đã được thanh toán ===
        $duplicatePayment = true;
        $alreadyProcessed = true;
        $cardTitle = 'Đơn hàng đã được thanh toán';
        $cardColor = 'text-info';
        $iconClass = 'fa-info-circle';
        $statusMessage = 'Đơn hàng #' . (!empty($order['order_code']) ? $order['order_code'] : $orderId) . ' đã được thanh toán trước đó. Không cần thanh toán lại.';
        
    } elseif ($orderStatus === 'CANCELLED') {
        // === DUPLICATE TAB: Đơn hàng đã bị hủy ===
        $orderCancelled = true;
        $alreadyProcessed = true;
        $cardTitle = 'Đơn hàng đã bị hủy';
        $cardColor = 'text-warning';
        $iconClass = 'fa-exclamation-triangle';
        $statusMessage = 'Đơn hàng #' . (!empty($order['order_code']) ? $order['order_code'] : $orderId) . ' đã bị hủy. Vui lòng tạo đơn hàng mới nếu cần.';
        
    } elseif ($vnpaySuccess) {
        // === THANH TOÁN THÀNH CÔNG ===
        $transNo = $_GET['vnp_TransactionNo'] ?? '';
        
        // Cập nhật trạng thái đơn hàng
        $transNoSafe = mysqli_real_escape_string($con, $transNo);
        mysqli_query($con, "UPDATE `oder` SET `status` = 'CONFIRMED', `payment_status` = 'PAID', `vnpay_tranId` = '$transNoSafe' WHERE `id` = $orderId AND `payment_status` = 'UNPAID'");
        
        if (mysqli_affected_rows($con) > 0) {
            $cardTitle = 'Thanh Toán Thành Công!';
            $cardColor = 'text-success';
            $iconClass = 'fa-check-circle';
            
            // Gửi email xác nhận
            if (!empty($_SESSION['email_kh'])) {
                require BASE_PATH . 'PHPMailer-master/src/PHPMailer.php';
                require BASE_PATH . 'PHPMailer-master/src/SMTP.php';
                require BASE_PATH . 'PHPMailer-master/src/Exception.php';
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                try {
                    $amount = number_format(($_GET['vnp_Amount'] ?? 0) / 100, 0, ',', '.');
                    $bank = $_GET['vnp_BankCode'] ?? 'N/A';
                    $tenkh = $_SESSION['tenkh_order'] ?? 'Quý khách';
                    
                    $mail->SMTPDebug = 0;
                    $mail->isSMTP();
                    $mail->CharSet = 'utf-8';
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'remkyorosi@gmail.com';
                    $mail->Password = 'nvui gcgt snxd rpib';
                    $mail->SMTPSecure = 'ssl';
                    $mail->Port = 465;
                    $mail->setFrom('remkyorosi@gmail.com', 'Shop Sneakers');
                    $mail->addAddress($_SESSION['email_kh'], $tenkh);
                    $mail->isHTML(true);
                    $mail->Subject = '✅ Xác nhận đơn hàng #' . $orderId . ' - Shop Giày Thể Thao';
                    $mail->Body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #ddd; border-radius: 8px; overflow: hidden;'>
                        <div style='background: #17a2b8; padding: 24px; text-align: center;'>
                            <h2 style='color: white; margin: 0;'>✅ Đơn hàng đã được xác nhận!</h2>
                        </div>
                        <div style='padding: 24px;'>
                            <p>Xin chào <strong>{$tenkh}</strong>,</p>
                            <p>Đơn hàng của bạn đã được thanh toán thành công qua VNPAY!</p>
                            <table style='width: 100%; border-collapse: collapse; margin: 16px 0;'>
                                <tr style='background: #f8f9fa;'>
                                    <td style='padding: 10px; border: 1px solid #dee2e6;'><strong>Mã đơn hàng</strong></td>
                                    <td style='padding: 10px; border: 1px solid #dee2e6;'>#{$orderId}</td>
                                </tr>
                                <tr>
                                    <td style='padding: 10px; border: 1px solid #dee2e6;'><strong>Mã giao dịch</strong></td>
                                    <td style='padding: 10px; border: 1px solid #dee2e6;'>{$transNo}</td>
                                </tr>
                                <tr style='background: #f8f9fa;'>
                                    <td style='padding: 10px; border: 1px solid #dee2e6;'><strong>Ngân hàng</strong></td>
                                    <td style='padding: 10px; border: 1px solid #dee2e6;'>{$bank}</td>
                                </tr>
                                <tr>
                                    <td style='padding: 10px; border: 1px solid #dee2e6;'><strong>Số tiền</strong></td>
                                    <td style='padding: 10px; border: 1px solid #dee2e6; color: #17a2b8; font-weight: bold;'>{$amount} VND</td>
                                </tr>
                            </table>
                            <p>Chúng tôi sẽ xử lý và giao hàng sớm nhất có thể.</p>
                            <p style='color: #6c757d; font-size: 12px;'>Email này được gửi tự động, vui lòng không reply.</p>
                        </div>
                    </div>";
                    $mail->smtpConnect(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]]);
                    $mail->send();
                    unset($_SESSION['email_kh']);
                    unset($_SESSION['tenkh_order']);
                } catch (Exception $e) {
                    // Không hiển thị lỗi cho user
                }
            }
        } else {
            // Đã được xử lý bởi tab khác
            $duplicatePayment = true;
            $alreadyProcessed = true;
            $cardTitle = 'Đơn hàng đã được thanh toán';
            $cardColor = 'text-info';
            $iconClass = 'fa-info-circle';
            $statusMessage = 'Đơn hàng này đã được xử lý thành công trước đó.';
        }
    } else {
        // === THANH TOÁN THẤT BẠI ===
        $cardTitle = 'Giao Dịch Thất Bại';
        $cardColor = 'text-danger';
        $iconClass = 'fa-times-circle';
        
        // Hoàn tồn kho vì thanh toán thất bại
        if ($paymentStatus === 'UNPAID' && $orderStatus !== 'CANCELLED') {
            restoreStock($con, $orderId);
            mysqli_query($con, "UPDATE `oder` SET `status` = 'CANCELLED', `payment_status` = 'FAILED' WHERE `id` = $orderId AND `payment_status` = 'UNPAID'");
        }
    }
} else {
    $cardTitle = 'Đơn Hàng Không Tồn Tại';
    $cardColor = 'text-danger';
    $iconClass = 'fa-question-circle';
    $alreadyProcessed = true;
    $statusMessage = 'Không tìm thấy thông tin đơn hàng.';
}
?>

<main class="page landing-page" style="padding-top: 100px; min-height: 80vh; background: #f8f9fa;">
    <section class="clean-block clean-info dark">
        <div class="container d-flex justify-content-center align-items-center">

            <div class="card shadow-lg border-0"
                style="width: 100%; max-width: 500px; border-radius: 12px; overflow: hidden;">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <i class="fas <?= $iconClass ?> <?= $cardColor ?>" style="font-size: 4rem;"></i>
                    </div>
                    <h3 class="<?= $cardColor ?> font-weight-bold mb-1"><?= $cardTitle ?></h3>
                    
                    <?php if ($alreadyProcessed): ?>
                        <!-- Duplicate tab / Already processed message -->
                        <p class="text-muted mb-4"><?= $statusMessage ?></p>
                        
                        <?php if ($duplicatePayment): ?>
                        <div class="alert alert-info text-left" style="font-size: 0.88rem; border-radius: 10px;">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Lưu ý:</strong> Giao dịch này đã được ghi nhận thành công. 
                            Bạn có thể kiểm tra chi tiết đơn hàng trong phần <strong>Lịch sử mua hàng</strong>.
                        </div>
                        <?php elseif ($orderCancelled): ?>
                        <div class="alert alert-warning text-left" style="font-size: 0.88rem; border-radius: 10px;">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Đơn hàng đã bị hủy.</strong> 
                            Nếu bạn vẫn muốn mua, vui lòng quay lại trang sản phẩm và đặt hàng lại.
                        </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <!-- Normal payment result -->
                        <p class="text-muted mb-4">Đơn hàng số: #<?= htmlspecialchars($_GET['vnp_TxnRef'] ?? 'N/A') ?></p>

                        <div class="text-left bg-light p-3 rounded mb-4" style="font-size: 0.95rem;">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Mã giao dịch:</span>
                                <span class="font-weight-bold text-dark"><?= htmlspecialchars($_GET['vnp_TransactionNo'] ?? 'N/A') ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Ngân hàng thanh toán:</span>
                                <span class="font-weight-bold text-dark"><?= htmlspecialchars($_GET['vnp_BankCode'] ?? 'N/A') ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Thời gian:</span>
                                <span class="font-weight-bold text-dark">
                                    <?php
                                    $dateStr = $_GET['vnp_PayDate'] ?? '';
                                    if (strlen($dateStr) == 14) {
                                        echo date('d/m/Y H:i:s', strtotime($dateStr));
                                    } else {
                                        echo date('d/m/Y H:i:s');
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="border-top pt-2 mt-2 d-flex justify-content-between align-items-center">
                                <span class="text-muted">SỐ TIỀN:</span>
                                <h4 class="font-weight-bold text-info m-0">
                                    <?= number_format(($_GET['vnp_Amount'] ?? 0) / 100, 0, ',', '.') ?> VND</h4>
                            </div>
                        </div>

                        <?php if ($secureHash != $vnp_SecureHash): ?>
                            <div class="alert alert-danger" role="alert" style="font-size: 0.85rem;">
                                <i class="fas fa-exclamation-triangle mr-2"></i>Cảnh báo bảo mật: Sai chữ ký gốc! Mọi số liệu có thể bị giả mạo.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="mt-4">
                        <?php if (!empty($_SESSION['dangnhap'])): ?>
                        <a href="<?php echo BASE_URL; ?>shop/tradonhang.php" class="btn btn-outline-info btn-block btn-lg shadow-sm font-weight-bold mb-2" style="font-size: 1rem;">
                            <i class="fas fa-list-alt mr-2"></i>XEM ĐƠN HÀNG CỦA TÔI
                        </a>
                        <?php endif; ?>
                        <a href="<?php echo BASE_URL; ?>home/trangchu.php" class="btn btn-info btn-block btn-lg shadow-sm font-weight-bold" style="font-size: 1rem;">
                            TIẾP TỤC MUA SẮM
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </section>
</main>

<?php include BASE_PATH . 'includes/footer.php'; ?>