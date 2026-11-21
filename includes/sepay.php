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

/**
 * Các trường form gửi sang SePay, thứ tự giống SDK (thứ tự này cũng là thứ tự ký).
 * $returnToken: mã ngẫu nhiên của phiên, gắn vào URL quay về để link hủy giả mạo (CSRF) không hủy được đơn.
 */
function sepay_form_fields(array $order, string $returnToken): array
{
    $back = BASE_URL . 'shop/thanhtoansepay.php?t=' . rawurlencode($returnToken) . '&r=';
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

function sepay_find_by_invoice(mysqli $con, string $invoice): ?array
{
    if (!preg_match('/\ADH(\d{9})\z/', $invoice, $m)) {
        return null;
    }
    $s = mysqli_prepare($con, 'SELECT * FROM `oder` WHERE `order_code` = ? LIMIT 1');
    mysqli_stmt_bind_param($s, 's', $m[1]);
    mysqli_stmt_execute($s);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($s)) ?: null;
    mysqli_stmt_close($s);
    return $row && str_starts_with((string)$row['vnpay_tranId'], 'SEPAY') ? $row : null;
}

/** Giá trị đầu tiên của một khóa trong JSON lồng nhau (phản hồi chi tiết đơn của API chưa có tài liệu dạng dữ liệu). */
function sepay_json_find($data, string $key)
{
    if (!is_array($data)) {
        return null;
    }
    if (array_key_exists($key, $data) && !is_array($data[$key])) {
        return $data[$key];
    }
    foreach ($data as $v) {
        $found = sepay_json_find($v, $key);
        if ($found !== null) {
            return $found;
        }
    }
    return null;
}

/**
 * Hỏi SePay trạng thái thật của hóa đơn (GET /v1/order/detail/{invoice}, Basic merchant:secret — như SDK).
 * SePay chỉ tạo đơn khi khách đã thanh toán, nên 404 = chưa thanh toán.
 * @return array ['paid', order] | ['unpaid', null] | ['unknown', null]
 */
function sepay_api_invoice_state(string $invoice): array
{
    $c = sepay_config();
    $base = $c['env'] === 'production' ? 'https://pgapi.sepay.vn' : 'https://pgapi-sandbox.sepay.vn';
    $ch = curl_init($base . '/v1/order/detail/' . rawurlencode($invoice));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_TIMEOUT => 6,
        CURLOPT_HTTPHEADER => ['Accept: application/json',
            'Authorization: Basic ' . base64_encode($c['merchant'] . ':' . $c['secret'])],
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code === 404) {
        return ['unpaid', null];
    }
    $json = ($code === 200 && is_string($body)) ? json_decode($body, true) : null;
    $status = sepay_json_find($json, 'order_status');
    if (!is_string($status)) {
        error_log('SEPAY_API_UNKNOWN: invoice=' . $invoice . ' http=' . $code);
        return ['unknown', null];
    }
    if ($status !== 'CAPTURED') {
        return ['unpaid', null];
    }
    return ['paid', ['order_invoice_number' => sepay_json_find($json, 'order_invoice_number'),
        'order_amount' => sepay_json_find($json, 'order_amount'), 'id' => sepay_json_find($json, 'id')]];
}

/**
 * Đơn SePay quá hạn: hỏi SePay trước khi hủy, để không hủy đơn khách đã trả tiền.
 * Đã trả → chốt như IPN; chưa trả → hủy, hoàn kho; không hỏi được → để lần sau (quá 24 giờ thì vẫn hủy).
 * $lookup: thay hàm hỏi API (dùng trong kiểm thử).
 */
function sepay_expire_stale(mysqli $con, ?callable $lookup = null): void
{
    $lookup = $lookup ?? 'sepay_api_invoice_state';
    $before = time() - SEPAY_EXPIRE_SECONDS;
    $pending = SEPAY_PENDING;
    $s = mysqli_prepare($con, "SELECT * FROM `oder` WHERE `vnpay_tranId` = ? AND `payment_status` = 'UNPAID' AND `status` = 'PENDING' AND `ngaytao` < ? LIMIT 20");
    mysqli_stmt_bind_param($s, 'si', $pending, $before);
    mysqli_stmt_execute($s);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
    mysqli_stmt_close($s);
    foreach ($rows as $row) {
        [$state, $remote] = $lookup(sepay_invoice_number($row));
        if ($state === 'paid') {
            sepay_settle($con, $row, $remote, '');
        } elseif ($state === 'unpaid' || (int)$row['ngaytao'] < time() - 86400) {
            sepay_cancel($con, (int)$row['id']);
        }
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
 * Chốt đơn đã thanh toán (dùng chung cho IPN và kiểm tra qua API). $remote: object order từ SePay.
 * @return string 'paid' | 'duplicate' | 'mismatch' | 'late'
 */
function sepay_settle(mysqli $con, array $row, array $remote, string $txId): string
{
    $orderId = (int)$row['id'];
    // order_amount dạng chuỗi "100000.00": phải đúng bằng tổng đơn; mã hóa đơn phải khớp.
    $amount = $remote['order_amount'] ?? null;
    if (($remote['order_invoice_number'] ?? null) !== sepay_invoice_number($row)
        || !is_numeric($amount) || (int)round((float)$amount) !== (int)$row['tongtien']) {
        error_log('SEPAY_AMOUNT_MISMATCH: order=' . $orderId);
        return 'mismatch';
    }
    $ref = 'SEPAY:' . substr(preg_replace('/[^A-Za-z0-9_-]/', '', (string)($txId !== '' ? $txId : ($remote['id'] ?? ''))), 0, 60);
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
    // Tiền đến sau khi đơn đã hủy/hết hạn: đánh dấu LATE_PAID để admin thấy, hoàn tiền hoặc khôi phục đơn.
    $u = mysqli_prepare($con, "UPDATE `oder` SET `payment_status` = 'LATE_PAID', `vnpay_tranId` = ? WHERE `id` = ? AND `status` = 'CANCELLED' AND `payment_status` IN ('UNPAID', 'FAILED')");
    mysqli_stmt_bind_param($u, 'si', $ref, $orderId);
    mysqli_stmt_execute($u);
    $late = mysqli_stmt_affected_rows($u) > 0;
    mysqli_stmt_close($u);
    if ($late) {
        error_log('SEPAY_LATE_PAYMENT: order=' . $orderId . ' ref=' . $ref);
        return 'late';
    }
    return 'duplicate';
}

/**
 * Giao dịch bị hủy/hoàn phía SePay (TRANSACTION_VOID): đơn chưa giao thì hủy + hoàn kho,
 * đang giao/đã giao thì chỉ ghi REFUNDED để admin xử lý.
 * @return string 'voided' | 'refunded' | 'duplicate'
 */
function sepay_void(mysqli $con, array $row): string
{
    require_once BASE_PATH . 'includes/inventory_helper.php';
    $orderId = (int)$row['id'];
    $u = mysqli_prepare($con, "UPDATE `oder` SET `status` = 'CANCELLED', `payment_status` = 'REFUNDED' WHERE `id` = ? AND `payment_status` = 'PAID' AND `status` IN ('PENDING', 'CONFIRMED')");
    mysqli_stmt_bind_param($u, 'i', $orderId);
    mysqli_stmt_execute($u);
    $cancelled = mysqli_stmt_affected_rows($u) > 0;
    mysqli_stmt_close($u);
    if ($cancelled) {
        restoreStock($con, $orderId);
        return 'voided';
    }
    $u = mysqli_prepare($con, "UPDATE `oder` SET `payment_status` = 'REFUNDED' WHERE `id` = ? AND `payment_status` IN ('PAID', 'LATE_PAID')");
    mysqli_stmt_bind_param($u, 'i', $orderId);
    mysqli_stmt_execute($u);
    $refunded = mysqli_stmt_affected_rows($u) > 0;
    mysqli_stmt_close($u);
    if ($refunded) {
        error_log('SEPAY_VOID_AFTER_SHIPPING: order=' . $orderId);
        return 'refunded';
    }
    return 'duplicate';
}

/**
 * Xử lý IPN đã xác thực (an toàn khi SePay gửi lại nhiều lần).
 * @return string 'paid' | 'duplicate' | 'mismatch' | 'late' | 'voided' | 'refunded' | 'ignored' | 'unknown'
 */
function sepay_handle_ipn(mysqli $con, array $data): string
{
    $order = is_array($data['order'] ?? null) ? $data['order'] : [];
    $tx = is_array($data['transaction'] ?? null) ? $data['transaction'] : [];
    $type = $data['notification_type'] ?? '';
    if ($type !== 'ORDER_PAID' && $type !== 'TRANSACTION_VOID') {
        return 'ignored';
    }
    $invoice = (string)($order['order_invoice_number'] ?? '');
    $row = sepay_find_by_invoice($con, $invoice);
    if (!$row) {
        error_log('SEPAY_UNKNOWN_ORDER: invoice=' . preg_replace('/[^A-Za-z0-9_-]/', '', $invoice));
        return 'unknown';
    }
    if ($type === 'TRANSACTION_VOID') {
        return sepay_void($con, $row);
    }
    if (($order['order_status'] ?? '') !== 'CAPTURED'
        || (isset($tx['transaction_status']) && $tx['transaction_status'] !== 'APPROVED')) {
        return 'ignored';
    }
    return sepay_settle($con, $row, $order, (string)($tx['transaction_id'] ?? $tx['id'] ?? ''));
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
