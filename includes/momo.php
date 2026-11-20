<?php
/**
 * MoMo (v2, captureWallet): tạo thanh toán, xác thực chữ ký HMAC-SHA256, chốt đơn hàng.
 * Cấu hình qua biến môi trường MOMO_PARTNER_CODE / MOMO_ACCESS_KEY / MOMO_SECRET_KEY / MOMO_ENDPOINT.
 */

const MOMO_ENDPOINTS = [
    'https://test-payment.momo.vn/v2/gateway/api/create',
    'https://payment.momo.vn/v2/gateway/api/create',
];
// Thứ tự trường trong chuỗi ký (theo bảng chữ cái, theo tài liệu MoMo).
const MOMO_CREATE_FIELDS = ['accessKey', 'amount', 'extraData', 'ipnUrl', 'orderId', 'orderInfo',
    'partnerCode', 'redirectUrl', 'requestId', 'requestType'];
const MOMO_RESULT_FIELDS = ['accessKey', 'amount', 'extraData', 'message', 'orderId', 'orderInfo',
    'orderType', 'partnerCode', 'payType', 'requestId', 'responseTime', 'resultCode', 'transId'];

function momo_config(): array
{
    return [
        'partner'  => trim((string)getenv('MOMO_PARTNER_CODE')),
        'access'   => trim((string)getenv('MOMO_ACCESS_KEY')),
        'secret'   => trim((string)getenv('MOMO_SECRET_KEY')),
        'endpoint' => trim((string)getenv('MOMO_ENDPOINT')) ?: MOMO_ENDPOINTS[0],
    ];
}

function momo_is_configured(): bool
{
    $c = momo_config();
    return $c['partner'] !== '' && $c['access'] !== '' && $c['secret'] !== ''
        && in_array($c['endpoint'], MOMO_ENDPOINTS, true);
}

function momo_require_configuration(): void
{
    if (momo_is_configured()) {
        return;
    }
    error_log('MOMO_UNAVAILABLE: merchant configuration is missing or invalid.');
    http_response_code(503);
    header('Cache-Control: no-store');
    exit('Thanh toán MoMo tạm thời không khả dụng. Vui lòng chọn phương thức khác.');
}

function momo_sign(array $data, array $fields): string
{
    $raw = [];
    foreach ($fields as $f) {
        $raw[] = $f . '=' . (string)($data[$f] ?? '');
    }
    return hash_hmac('sha256', implode('&', $raw), momo_config()['secret']);
}

/** Dữ liệu trả về từ MoMo (redirect hoặc IPN): chữ ký phải khớp, chưa có accessKey nên thêm từ cấu hình. */
function momo_verify(array $data): bool
{
    $given = $data['signature'] ?? '';
    if (!is_string($given) || !preg_match('/\A[0-9a-f]{64}\z/i', $given) || !momo_is_configured()) {
        return false;
    }
    $data['accessKey'] = momo_config()['access'];
    return hash_equals(momo_sign($data, MOMO_RESULT_FIELDS), strtolower($given));
}

/** orderId gửi MoMo có dạng "<id đơn>-<timestamp>" để mỗi lần thanh toán là duy nhất. */
function momo_db_order_id($momoOrderId): int
{
    return is_string($momoOrderId) && preg_match('/\A(\d{1,10})-\d{1,12}\z/', $momoOrderId, $m) ? (int)$m[1] : 0;
}

/** Trả về payUrl (đã kiểm tra thuộc MoMo) hoặc null nếu MoMo từ chối/lỗi mạng. */
function momo_create_payment(int $dbOrderId, int $amount, string $orderInfo): ?string
{
    $c = momo_config();
    $orderId = $dbOrderId . '-' . time();
    $req = [
        'accessKey' => $c['access'], 'amount' => (string)$amount, 'extraData' => '',
        'ipnUrl' => BASE_URL . 'shop/momo_ipn.php', 'orderId' => $orderId, 'orderInfo' => $orderInfo,
        'partnerCode' => $c['partner'], 'redirectUrl' => BASE_URL . 'shop/momo_return.php',
        'requestId' => $orderId, 'requestType' => 'captureWallet',
    ];
    $body = [
        'partnerCode' => $c['partner'], 'requestId' => $orderId, 'amount' => $amount, 'orderId' => $orderId,
        'orderInfo' => $orderInfo, 'redirectUrl' => $req['redirectUrl'], 'ipnUrl' => $req['ipnUrl'],
        'lang' => 'vi', 'requestType' => 'captureWallet', 'autoCapture' => true, 'extraData' => '',
        'signature' => momo_sign($req, MOMO_CREATE_FIELDS),
    ];
    $ch = curl_init($c['endpoint']);
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 15,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    $json = is_string($res) ? json_decode($res, true) : null;
    if (!is_array($json) || (int)($json['resultCode'] ?? -1) !== 0 || empty($json['payUrl'])) {
        error_log('MOMO_CREATE_FAIL: order=' . $dbOrderId . ' code=' . (is_array($json) ? ($json['resultCode'] ?? '?') : 'no-response'));
        return null;
    }
    $url = (string)$json['payUrl'];
    return preg_match('~\Ahttps://(?:test-payment|payment)\.momo\.vn/~', $url) ? $url : null;
}

/**
 * Chốt đơn theo kết quả MoMo (dùng chung cho redirect và IPN, an toàn khi gọi nhiều lần).
 * @return string 'paid' | 'duplicate' | 'failed' | 'invalid'
 */
function momo_finalize(mysqli $con, array $data): string
{
    $orderId = momo_db_order_id($data['orderId'] ?? '');
    $stmt = mysqli_prepare($con, 'SELECT * FROM `oder` WHERE `id` = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $orderId);
    mysqli_stmt_execute($stmt);
    $order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    // Số tiền MoMo xác nhận phải đúng bằng tổng đơn, tránh thanh toán thiếu.
    if (!$order || (int)($data['amount'] ?? -1) !== (int)$order['tongtien']) {
        error_log('MOMO_INVALID_ORDER: id=' . $orderId);
        return 'invalid';
    }
    require_once BASE_PATH . 'includes/inventory_helper.php';

    if ((int)($data['resultCode'] ?? -1) !== 0) {
        $u = mysqli_prepare($con, "UPDATE `oder` SET `status` = 'CANCELLED', `payment_status` = 'FAILED' WHERE `id` = ? AND `payment_status` = 'UNPAID' AND `status` <> 'CANCELLED'");
        mysqli_stmt_bind_param($u, 'i', $orderId);
        mysqli_stmt_execute($u);
        $changed = mysqli_stmt_affected_rows($u) > 0;
        mysqli_stmt_close($u);
        if ($changed) {
            restoreStock($con, $orderId); // chỉ hoàn kho một lần: người thắng cập nhật có điều kiện
        }
        return 'failed';
    }

    $trans = substr((string)($data['transId'] ?? ''), 0, 100);
    $u = mysqli_prepare($con, "UPDATE `oder` SET `status` = 'CONFIRMED', `payment_status` = 'PAID', `vnpay_tranId` = ? WHERE `id` = ? AND `payment_status` = 'UNPAID' AND `status` <> 'CANCELLED'");
    mysqli_stmt_bind_param($u, 'si', $trans, $orderId);
    mysqli_stmt_execute($u);
    $paid = mysqli_stmt_affected_rows($u) > 0;
    mysqli_stmt_close($u);
    if (!$paid) {
        return 'duplicate';
    }
    momo_send_confirmation($con, $order);
    return 'paid';
}

function momo_send_confirmation(mysqli $con, array $order): void
{
    $email = (string)($order['email'] ?? '');
    if ($email === '') {
        return;
    }
    require_once BASE_PATH . 'includes/mail_helper.php';
    require_once BASE_PATH . 'includes/email_templates.php';
    $orderId = (int)$order['id'];
    $items = [];
    $q = mysqli_prepare($con, 'SELECT oc.quantity, oc.price, oc.size, sp.tensp FROM `oder_chitiet` oc LEFT JOIN `tbl_qlsanpham` sp ON oc.masp = sp.masp WHERE oc.madonhang = ?');
    mysqli_stmt_bind_param($q, 'i', $orderId);
    mysqli_stmt_execute($q);
    $rs = mysqli_stmt_get_result($q);
    while ($d = mysqli_fetch_assoc($rs)) {
        $items[] = ['name' => $d['tensp'] ?? '', 'qty' => $d['quantity'], 'price' => $d['price'], 'size' => $d['size'] ?? ''];
    }
    mysqli_stmt_close($q);
    // Token tra cứu chỉ lưu dạng băm: cấp token mới (IPN không có session của khách).
    $token = bin2hex(random_bytes(16));
    $hash = app_hash_token($token);
    $t = mysqli_prepare($con, 'UPDATE `oder` SET `token` = ? WHERE `id` = ?');
    mysqli_stmt_bind_param($t, 'si', $hash, $orderId);
    mysqli_stmt_execute($t);
    mysqli_stmt_close($t);
    $code = !empty($order['order_code']) ? $order['order_code'] : $orderId;
    $name = $order['tenkh'] ?? 'Quý khách';
    $tpl = getOrderConfirmationTemplate([
        'customerName' => $name,
        'phone' => app_decrypt_field($order['sdt'] ?? '', 'oder.sdt'),
        'address' => app_decrypt_field($order['diachi'] ?? '', 'oder.diachi'),
        'orderCode' => $code, 'products' => $items, 'total' => $order['tongtien'] ?? 0,
        'paymentMethod' => 'MoMo (đã thanh toán)',
        'trackingLink' => BASE_URL . 'shop/tracking.php?order=' . $code . '&token=' . $token,
    ]);
    sendTransactionalEmail($email, $name, $tpl['subject'], $tpl['html']);
}
