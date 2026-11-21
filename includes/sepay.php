<?php
/**
 * SePay — Cổng thanh toán (payment gateway).
 * Luồng: tạo đơn chờ thanh toán → trình duyệt POST form đã ký sang SePay (/v1/checkout/init)
 *        → khách thanh toán trên trang SePay → SePay chuyển về success/error/cancel_url
 *        → SePay gọi IPN (POST JSON, header X-Secret-Key) để xác nhận đã thanh toán.
 * Chỉ IPN mới chốt "đã thanh toán"; URL quay về không có chữ ký nên chỉ để hiển thị / hủy đơn của chính phiên.
 * Cấu hình: SEPAY_ENV (sandbox|production), SEPAY_MERCHANT_ID, SEPAY_SECRET_KEY (ký form), SEPAY_IPN_SECRET (X-Secret-Key).
 */

const SEPAY_PENDING = 'SEPAY_PENDING';  // giá trị cột vnpay_tranId của đơn SePay chưa thanh toán
const SEPAY_EXPIRE_SECONDS = 1800;      // giữ hàng 30 phút, quá hạn thì hủy đơn và hoàn kho
const SEPAY_CHECKOUT_URLS = [
    'sandbox'    => 'https://pay-sandbox.sepay.vn/v1/checkout/init',
    'production' => 'https://pay.sepay.vn/v1/checkout/init',
];

function sepay_config(): array
{
    return [
        'env'      => strtolower(trim((string)getenv('SEPAY_ENV')) ?: 'sandbox'),
        'merchant' => trim((string)getenv('SEPAY_MERCHANT_ID')),
        'secret'   => trim((string)getenv('SEPAY_SECRET_KEY')),
        'ipn'      => trim((string)getenv('SEPAY_IPN_SECRET')),
    ];
}

function sepay_is_configured(): bool
{
    $c = sepay_config();
    return isset(SEPAY_CHECKOUT_URLS[$c['env']])
        && preg_match('/\ASP-[A-Z0-9-]{4,40}\z/', $c['merchant']) === 1
        && strlen($c['secret']) >= 16 && strlen($c['ipn']) >= 16;
}

function sepay_require_configuration(): void
{
    if (sepay_is_configured()) {
        return;
    }
    error_log('SEPAY_UNAVAILABLE: merchant configuration is missing or invalid.');
    http_response_code(503);
    header('Cache-Control: no-store');
    exit('Thanh toán SePay tạm thời không khả dụng. Vui lòng chọn phương thức khác.');
}

function sepay_checkout_url(): string
{
    return SEPAY_CHECKOUT_URLS[sepay_config()['env']];
}

/** Mã hóa đơn gửi SePay (chỉ chữ, số, _ -): "DH" + order_code 9 chữ số. */
function sepay_invoice_number(array $order): string
{
    return 'DH' . (string)$order['order_code'];
}

/** Chữ ký form theo SDK sepay-pg-php: HMAC-SHA256("k=v,k=v,...") theo đúng thứ tự trường, base64 dạng nhị phân. */
function sepay_sign(array $fields): string
{
    $signed = [];
    foreach ($fields as $k => $v) {
        $signed[] = $k . '=' . $v;
    }
    return base64_encode(hash_hmac('sha256', implode(',', $signed), sepay_config()['secret'], true));
}

/** Các trường form gửi sang SePay, thứ tự giống SDK (thứ tự này cũng là thứ tự ký). */
function sepay_form_fields(array $order): array
{
    $back = BASE_URL . 'shop/thanhtoansepay.php?r=';
    $fields = [
        'merchant'             => sepay_config()['merchant'],
        'currency'             => 'VND',
        'order_amount'         => (string)(int)$order['tongtien'],
        'operation'            => 'PURCHASE',
        'order_description'    => 'Thanh toan don hang ' . $order['order_code'],
        'order_invoice_number' => sepay_invoice_number($order),
        'success_url'          => $back . 'success',
        'error_url'            => $back . 'error',
        'cancel_url'           => $back . 'cancel',
    ];
    $fields['signature'] = sepay_sign($fields);
    return $fields;
}

/** IPN chỉ hợp lệ khi có header X-Secret-Key đúng (chọn Auth Type = SECRET_KEY ở tab IPN trên SePay). */
function sepay_verify_ipn(): bool
{
    $given = (string)($_SERVER['HTTP_X_SECRET_KEY'] ?? '');
    return sepay_is_configured() && $given !== '' && hash_equals(sepay_config()['ipn'], $given);
}

function sepay_find_order(mysqli $con, int $orderId): ?array
{
    $s = mysqli_prepare($con, 'SELECT * FROM `oder` WHERE `id` = ? LIMIT 1');
    mysqli_stmt_bind_param($s, 'i', $orderId);
    mysqli_stmt_execute($s);
    $order = mysqli_fetch_assoc(mysqli_stmt_get_result($s)) ?: null;
    mysqli_stmt_close($s);
    return $order && str_starts_with((string)$order['vnpay_tranId'], 'SEPAY') ? $order : null;
}

/** Hủy đơn SePay quá hạn chưa thanh toán và hoàn kho (chạy khi có người mở trang đặt hàng/thanh toán). */
function sepay_expire_stale(mysqli $con): void
{
    $before = time() - SEPAY_EXPIRE_SECONDS;
    $pending = SEPAY_PENDING;
    $s = mysqli_prepare($con, "SELECT `id` FROM `oder` WHERE `vnpay_tranId` = ? AND `payment_status` = 'UNPAID' AND `status` = 'PENDING' AND `ngaytao` < ?");
    mysqli_stmt_bind_param($s, 'si', $pending, $before);
    mysqli_stmt_execute($s);
    $ids = array_map('intval', array_column(mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC), 'id'));
    mysqli_stmt_close($s);
    foreach ($ids as $id) {
        sepay_cancel($con, $id);
    }
}

/** Hủy đơn SePay còn chờ thanh toán; true nếu chính lần gọi này hủy (hoàn kho đúng một lần). */
function sepay_cancel(mysqli $con, int $orderId): bool
{
    require_once BASE_PATH . 'includes/inventory_helper.php';
    $pending = SEPAY_PENDING;
    $u = mysqli_prepare($con, "UPDATE `oder` SET `status` = 'CANCELLED', `payment_status` = 'FAILED' WHERE `id` = ? AND `vnpay_tranId` = ? AND `payment_status` = 'UNPAID' AND `status` = 'PENDING'");
    mysqli_stmt_bind_param($u, 'is', $orderId, $pending);
    mysqli_stmt_execute($u);
    $changed = mysqli_stmt_affected_rows($u) > 0;
    mysqli_stmt_close($u);
    if ($changed) {
        restoreStock($con, $orderId);
    }
    return $changed;
}

/**
 * Xử lý IPN đã xác thực (an toàn khi SePay gửi lại nhiều lần).
 * @return string 'paid' | 'duplicate' | 'ignored' | 'mismatch' | 'late' | 'unknown'
 */
function sepay_handle_ipn(mysqli $con, array $data): string
{
    $order = is_array($data['order'] ?? null) ? $data['order'] : [];
    $tx = is_array($data['transaction'] ?? null) ? $data['transaction'] : [];
    if (($data['notification_type'] ?? '') !== 'ORDER_PAID' || ($order['order_status'] ?? '') !== 'CAPTURED'
        || (isset($tx['transaction_status']) && $tx['transaction_status'] !== 'APPROVED')) {
        return 'ignored';
    }
    $invoice = (string)($order['order_invoice_number'] ?? '');
    if (!preg_match('/\ADH(\d{9})\z/', $invoice, $m)) {
        return 'unknown';
    }
    $s = mysqli_prepare($con, 'SELECT * FROM `oder` WHERE `order_code` = ? LIMIT 1');
    mysqli_stmt_bind_param($s, 's', $m[1]);
    mysqli_stmt_execute($s);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
    mysqli_stmt_close($s);
    if (!$row || !str_starts_with((string)$row['vnpay_tranId'], 'SEPAY')) {
        error_log('SEPAY_UNKNOWN_ORDER: invoice=' . $invoice);
        return 'unknown';
    }
    $orderId = (int)$row['id'];
    // order_amount dạng chuỗi "100000.00": phải đúng bằng tổng đơn.
    $amount = $order['order_amount'] ?? null;
    if (!is_numeric($amount) || (int)round((float)$amount) !== (int)$row['tongtien']) {
        error_log('SEPAY_AMOUNT_MISMATCH: order=' . $orderId);
        return 'mismatch';
    }
    $ref = 'SEPAY:' . substr(preg_replace('/[^A-Za-z0-9_-]/', '', (string)($tx['transaction_id'] ?? $tx['id'] ?? $order['id'] ?? '')), 0, 60);
    $pending = SEPAY_PENDING;
    $u = mysqli_prepare($con, "UPDATE `oder` SET `status` = 'CONFIRMED', `payment_status` = 'PAID', `vnpay_tranId` = ? WHERE `id` = ? AND `vnpay_tranId` = ? AND `payment_status` = 'UNPAID' AND `status` = 'PENDING'");
    mysqli_stmt_bind_param($u, 'sis', $ref, $orderId, $pending);
    mysqli_stmt_execute($u);
    $paid = mysqli_stmt_affected_rows($u) > 0;
    mysqli_stmt_close($u);
    if ($paid) {
        sepay_send_confirmation($con, $row);
        return 'paid';
    }
    if ($row['status'] === 'CANCELLED') {
        // Tiền đến sau khi đơn đã hủy/hết hạn: không tự mở lại đơn (kho đã hoàn), admin hoàn tiền.
        error_log('SEPAY_LATE_PAYMENT: order=' . $orderId . ' ref=' . $ref);
        return 'late';
    }
    return 'duplicate';
}

function sepay_order_state(array $order): string
{
    if ($order['payment_status'] === 'PAID') {
        return 'paid';
    }
    return $order['status'] === 'CANCELLED' ? 'cancelled' : 'pending';
}

function sepay_send_confirmation(mysqli $con, array $order): void
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
        'paymentMethod' => 'SePay (đã thanh toán)',
        'trackingLink' => BASE_URL . 'shop/tracking.php?order=' . $code . '&token=' . $token,
    ]);
    sendTransactionalEmail($email, $name, $tpl['subject'], $tpl['html']);
}
