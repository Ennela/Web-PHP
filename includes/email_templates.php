<?php
/**
 * Email Templates — Mẫu email HTML responsive cho Shop Sneakers.
 * 
 * Tất cả template đều:
 * - Responsive (mobile + desktop)
 * - Có Preheader text
 * - Có Footer chuẩn CAN-SPAM (tên công ty, địa chỉ, SĐT)
 * - Tránh từ khóa spam
 * - Inline CSS (tương thích mọi email client)
 */

require_once __DIR__ . '/mail_config.php';

// ═══════════════════════════════════════════════════════════
// SHARED COMPONENTS
// ═══════════════════════════════════════════════════════════

function _emailPreheader($text) {
    return '<div style="display:none;font-size:1px;color:#f8f9fa;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">'
         . htmlspecialchars($text)
         . '</div>';
}

function _emailHeader($title, $bgColor = '#2563eb') {
    $logoHtml = '';
    if (!empty(BRAND_LOGO_URL)) {
        $logoHtml = '<img src="' . BRAND_LOGO_URL . '" alt="' . BRAND_NAME . '" style="max-width:120px;margin-bottom:12px;" /><br>';
    }
    return '
    <div style="background:linear-gradient(135deg,' . $bgColor . ',#1e40af);padding:32px 24px;text-align:center;">
        ' . $logoHtml . '
        <h1 style="color:#fff;margin:0;font-size:22px;font-weight:800;letter-spacing:1px;">' . htmlspecialchars($title) . '</h1>
    </div>';
}

function _emailFooter($includeUnsubscribe = false) {
    $year = date('Y');
    $unsub = '';
    if ($includeUnsubscribe && !empty(MAIL_UNSUBSCRIBE_URL)) {
        $unsub = '<p style="margin:8px 0;">
            <a href="' . MAIL_UNSUBSCRIBE_URL . '" style="color:#94a3b8;font-size:12px;text-decoration:underline;">Hủy đăng ký nhận tin</a>
        </p>';
    }
    return '
    <div style="background:#f8fafc;padding:20px 24px;text-align:center;border-top:1px solid #e2e8f0;">
        <p style="color:#64748b;font-size:12px;margin:0 0 4px;font-weight:600;">' . BRAND_NAME . '</p>
        <p style="color:#94a3b8;font-size:11px;margin:0 0 4px;">' . BRAND_ADDRESS . '</p>
        <p style="color:#94a3b8;font-size:11px;margin:0 0 4px;">SĐT: ' . BRAND_PHONE
        . (!empty(BRAND_TAX_ID) ? ' | MST: ' . BRAND_TAX_ID : '') . '</p>
        ' . $unsub . '
        <p style="color:#cbd5e1;font-size:11px;margin:8px 0 0;">© ' . $year . ' ' . BRAND_NAME . ' — Tất cả quyền được bảo lưu</p>
    </div>';
}

function _emailWrapper($content) {
    return '<!DOCTYPE html>
<html lang="vi">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;">
<div style="max-width:600px;margin:40px auto;background:#fff;border-radius:16px;border:1px solid #e2e8f0;box-shadow:0 4px 24px rgba(0,0,0,0.06);overflow:hidden;">
' . $content . '
</div>
</body>
</html>';
}

// ═══════════════════════════════════════════════════════════
// TEMPLATE 1: XÁC NHẬN ĐƠN HÀNG (Order Confirmation)
// ═══════════════════════════════════════════════════════════

/**
 * @param array $data [
 *   'customerName'  => string,
 *   'phone'         => string,
 *   'address'       => string,
 *   'orderCode'     => string,
 *   'products'      => [['name'=>..,'qty'=>..,'price'=>..,'size'=>..,'image'=>..], ...],
 *   'total'         => int,
 *   'paymentMethod' => string,
 *   'trackingLink'  => string,
 * ]
 */
function getOrderConfirmationTemplate($data) {
    $customerName  = htmlspecialchars($data['customerName'] ?? 'Quý khách');
    $phone         = htmlspecialchars($data['phone'] ?? '');
    $address       = htmlspecialchars($data['address'] ?? '');
    $orderCode     = htmlspecialchars($data['orderCode'] ?? '');
    $totalFmt      = number_format($data['total'] ?? 0, 0, ',', '.');
    $paymentMethod = htmlspecialchars($data['paymentMethod'] ?? 'COD');
    $trackingLink  = $data['trackingLink'] ?? '#';

    // Build product rows
    $productRows = '';
    foreach (($data['products'] ?? []) as $p) {
        $pName  = htmlspecialchars($p['name'] ?? '');
        $pQty   = (int)($p['qty'] ?? 1);
        $pPrice = number_format(($p['price'] ?? 0) * $pQty, 0, ',', '.');
        $pSize  = !empty($p['size']) ? ' · Size: ' . $p['size'] : '';
        
        $productRows .= '
        <tr>
            <td style="padding:12px;border-bottom:1px solid #f1f5f9;">
                <strong style="color:#1e293b;font-size:14px;">' . $pName . '</strong>
                <br><span style="color:#94a3b8;font-size:12px;">SL: ' . $pQty . $pSize . '</span>
            </td>
            <td style="padding:12px;border-bottom:1px solid #f1f5f9;text-align:right;color:#2563eb;font-weight:700;font-size:14px;white-space:nowrap;">
                ' . $pPrice . '&nbsp;đ
            </td>
        </tr>';
    }

    $preheader = _emailPreheader("Đơn hàng #$orderCode đã được xác nhận. Tổng: {$totalFmt}đ");

    $body = $preheader
          . _emailHeader('ĐƠN HÀNG ĐÃ ĐƯỢC GHI NHẬN')
          . '
    <div style="padding:28px 24px;">
        <p style="color:#334155;font-size:15px;line-height:1.6;">Xin chào <strong>' . $customerName . '</strong>,</p>
        <p style="color:#64748b;font-size:14px;line-height:1.6;">Cảm ơn bạn đã mua sắm tại <strong>' . BRAND_NAME . '</strong>. Đơn hàng của bạn đã được ghi nhận thành công.</p>

        <!-- Order Info -->
        <div style="background:#f8fafc;border-radius:12px;padding:16px;margin:20px 0;">
            <table style="width:100%;font-size:13px;color:#475569;">
                <tr><td style="padding:6px 0;"><strong>Mã đơn:</strong></td><td style="padding:6px 0;text-align:right;font-weight:700;color:#2563eb;">#' . $orderCode . '</td></tr>
                <tr><td style="padding:6px 0;"><strong>Người nhận:</strong></td><td style="padding:6px 0;text-align:right;">' . $customerName . '</td></tr>
                <tr><td style="padding:6px 0;"><strong>SĐT:</strong></td><td style="padding:6px 0;text-align:right;">' . $phone . '</td></tr>
                <tr><td style="padding:6px 0;"><strong>Địa chỉ:</strong></td><td style="padding:6px 0;text-align:right;">' . $address . '</td></tr>
                <tr><td style="padding:6px 0;"><strong>Thanh toán:</strong></td><td style="padding:6px 0;text-align:right;">' . $paymentMethod . '</td></tr>
            </table>
        </div>

        <!-- Product List -->
        <table style="width:100%;border-collapse:collapse;margin:16px 0;">
            <tr style="background:#f1f5f9;">
                <td style="padding:10px 12px;font-weight:700;color:#475569;font-size:13px;">Sản phẩm</td>
                <td style="padding:10px 12px;font-weight:700;color:#475569;font-size:13px;text-align:right;">Thành tiền</td>
            </tr>
            ' . $productRows . '
        </table>

        <!-- Total -->
        <div style="background:linear-gradient(135deg,#2563eb,#1d4ed8);border-radius:12px;padding:16px 20px;text-align:center;margin:20px 0;">
            <span style="color:rgba(255,255,255,0.8);font-size:13px;">TỔNG THANH TOÁN</span>
            <div style="color:#fff;font-size:28px;font-weight:800;margin-top:4px;">' . $totalFmt . '&nbsp;đ</div>
        </div>

        <!-- CTA -->
        <div style="text-align:center;margin:24px 0;">
            <a href="' . $trackingLink . '" style="display:inline-block;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;text-decoration:none;padding:14px 36px;border-radius:10px;font-weight:700;font-size:15px;">Theo dõi đơn hàng</a>
        </div>

        <p style="color:#94a3b8;font-size:12px;text-align:center;">Email này được gửi tự động. Nếu cần hỗ trợ, liên hệ <strong>' . BRAND_PHONE . '</strong></p>
    </div>'
    . _emailFooter();

    return [
        'subject' => BRAND_NAME . ' — Xác nhận đơn hàng #' . $orderCode,
        'html'    => _emailWrapper($body),
    ];
}

// ═══════════════════════════════════════════════════════════
// TEMPLATE 2: MÃ OTP / XÁC THỰC TÀI KHOẢN
// ═══════════════════════════════════════════════════════════

/**
 * @param array $data [
 *   'customerName' => string,
 *   'otpCode'      => string,
 *   'expiryMinutes'=> int (mặc định 5),
 * ]
 */
function getOTPTemplate($data) {
    $name    = htmlspecialchars($data['customerName'] ?? 'Quý khách');
    $otp     = htmlspecialchars($data['otpCode'] ?? '------');
    $expiry  = (int)($data['expiryMinutes'] ?? 5);

    $preheader = _emailPreheader("Mã xác thực của bạn là $otp. Hiệu lực $expiry phút.");

    $body = $preheader
          . _emailHeader('XÁC THỰC TÀI KHOẢN', '#059669')
          . '
    <div style="padding:32px 24px;text-align:center;">
        <p style="color:#334155;font-size:15px;">Xin chào <strong>' . $name . '</strong>,</p>
        <p style="color:#64748b;font-size:14px;">Đây là mã xác thực OTP cho tài khoản của bạn tại <strong>' . BRAND_NAME . '</strong>:</p>

        <div style="background:#f0fdf4;border:2px dashed #22c55e;border-radius:16px;padding:24px;margin:28px auto;max-width:280px;">
            <div style="font-size:40px;font-weight:900;letter-spacing:8px;color:#16a34a;font-family:monospace;">' . $otp . '</div>
        </div>

        <p style="color:#dc2626;font-size:13px;font-weight:700;">
            <span style="font-size:16px;">⚠️</span> Không chia sẻ mã này với bất kỳ ai
        </p>
        <p style="color:#94a3b8;font-size:13px;">Mã có hiệu lực trong <strong>' . $expiry . ' phút</strong>. Sau thời gian này, bạn cần yêu cầu mã mới.</p>
        <p style="color:#94a3b8;font-size:12px;margin-top:20px;">Nếu bạn không yêu cầu mã này, hãy bỏ qua email này.</p>
    </div>'
    . _emailFooter();

    return [
        'subject' => BRAND_NAME . ' — Mã xác thực OTP: ' . $otp,
        'html'    => _emailWrapper($body),
    ];
}

// ═══════════════════════════════════════════════════════════
// TEMPLATE 3: KHÔI PHỤC MẬT KHẨU (Password Reset)
// ═══════════════════════════════════════════════════════════

/**
 * @param array $data [
 *   'customerName' => string,
 *   'resetLink'    => string,
 *   'expiryMinutes'=> int (mặc định 30),
 * ]
 */
function getPasswordResetTemplate($data) {
    $name      = htmlspecialchars($data['customerName'] ?? 'Quý khách');
    $resetLink = $data['resetLink'] ?? '#';
    $expiry    = (int)($data['expiryMinutes'] ?? 30);

    $preheader = _emailPreheader("Yêu cầu đặt lại mật khẩu tài khoản " . BRAND_NAME . ". Link có hiệu lực $expiry phút.");

    $body = $preheader
          . _emailHeader('ĐẶT LẠI MẬT KHẨU', '#7c3aed')
          . '
    <div style="padding:32px 24px;">
        <p style="color:#334155;font-size:15px;line-height:1.6;">Xin chào <strong>' . $name . '</strong>,</p>
        <p style="color:#64748b;font-size:14px;line-height:1.6;">Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn tại <strong>' . BRAND_NAME . '</strong>.</p>
        <p style="color:#64748b;font-size:14px;line-height:1.6;">Nhấn vào nút bên dưới để tạo mật khẩu mới:</p>

        <div style="text-align:center;margin:28px 0;">
            <a href="' . $resetLink . '" style="display:inline-block;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;text-decoration:none;padding:14px 40px;border-radius:10px;font-weight:700;font-size:15px;">Đặt lại mật khẩu</a>
        </div>

        <p style="color:#94a3b8;font-size:13px;line-height:1.6;">Link có hiệu lực trong <strong>' . $expiry . ' phút</strong>.</p>
        <p style="color:#94a3b8;font-size:13px;line-height:1.6;">Nếu bạn không yêu cầu đặt lại mật khẩu, hãy bỏ qua email này. Tài khoản của bạn vẫn an toàn.</p>

        <hr style="border:none;border-top:1px solid #e2e8f0;margin:24px 0;">
        <p style="color:#cbd5e1;font-size:11px;text-align:center;">Nếu nút không hoạt động, copy link sau vào trình duyệt:<br>
        <a href="' . $resetLink . '" style="color:#7c3aed;word-break:break-all;font-size:11px;">' . $resetLink . '</a></p>
    </div>'
    . _emailFooter();

    return [
        'subject' => BRAND_NAME . ' — Yêu cầu khôi phục mật khẩu',
        'html'    => _emailWrapper($body),
    ];
}

// ═══════════════════════════════════════════════════════════
// TEMPLATE 4: KHÔI PHỤC GIỎ HÀNG BỎ QUÊN (Abandoned Cart)
// ═══════════════════════════════════════════════════════════

/**
 * @param array $data [
 *   'customerName' => string,
 *   'products'     => [['name'=>..,'price'=>..,'image'=>..], ...],
 *   'cartLink'     => string,
 *   'couponCode'   => string|null,
 *   'couponText'   => string|null (vd: 'Giảm 5%'),
 * ]
 */
function getAbandonedCartTemplate($data) {
    $name       = htmlspecialchars($data['customerName'] ?? 'bạn');
    $cartLink   = $data['cartLink'] ?? '#';
    $couponCode = $data['couponCode'] ?? null;
    $couponText = $data['couponText'] ?? 'Giảm 5%';

    // Product list
    $productRows = '';
    foreach (($data['products'] ?? []) as $p) {
        $pName  = htmlspecialchars($p['name'] ?? '');
        $pPrice = number_format($p['price'] ?? 0, 0, ',', '.');
        $pImg   = $p['image'] ?? '';

        $imgHtml = '';
        if (!empty($pImg)) {
            $imgHtml = '<img src="' . $pImg . '" alt="' . $pName . '" style="width:60px;height:60px;object-fit:cover;border-radius:8px;margin-right:12px;" />';
        }

        $productRows .= '
        <tr>
            <td style="padding:12px;border-bottom:1px solid #f1f5f9;">
                <div style="display:flex;align-items:center;">
                    ' . $imgHtml . '
                    <span style="color:#1e293b;font-size:14px;font-weight:600;">' . $pName . '</span>
                </div>
            </td>
            <td style="padding:12px;border-bottom:1px solid #f1f5f9;text-align:right;color:#2563eb;font-weight:700;white-space:nowrap;">' . $pPrice . '&nbsp;đ</td>
        </tr>';
    }

    // Coupon section
    $couponHtml = '';
    if (!empty($couponCode)) {
        $couponHtml = '
        <div style="background:#fef3c7;border:1px solid #fbbf24;border-radius:12px;padding:16px;text-align:center;margin:20px 0;">
            <p style="color:#92400e;font-size:13px;margin:0 0 8px;font-weight:600;">Ưu đãi dành riêng cho bạn</p>
            <div style="background:#fff;border-radius:8px;padding:10px 20px;display:inline-block;">
                <span style="font-size:20px;font-weight:900;color:#d97706;letter-spacing:2px;">' . htmlspecialchars($couponCode) . '</span>
            </div>
            <p style="color:#b45309;font-size:12px;margin:8px 0 0;">' . htmlspecialchars($couponText) . ' cho đơn hàng này</p>
        </div>';
    }

    $preheader = _emailPreheader("Bạn đã quên sản phẩm trong giỏ hàng! Hoàn tất mua sắm ngay.");

    $body = $preheader
          . _emailHeader('BẠN QUÊN GÌ ĐÓ KÌA!', '#f59e0b')
          . '
    <div style="padding:28px 24px;">
        <p style="color:#334155;font-size:15px;line-height:1.6;">Xin chào <strong>' . $name . '</strong>,</p>
        <p style="color:#64748b;font-size:14px;line-height:1.6;">Có vẻ như bạn đã để lại một vài sản phẩm trong giỏ hàng. Đừng để chúng đợi quá lâu nhé!</p>

        <table style="width:100%;border-collapse:collapse;margin:20px 0;">
            ' . $productRows . '
        </table>

        ' . $couponHtml . '

        <div style="text-align:center;margin:24px 0;">
            <a href="' . $cartLink . '" style="display:inline-block;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;text-decoration:none;padding:14px 36px;border-radius:10px;font-weight:700;font-size:15px;">Hoàn tất mua sắm ngay</a>
        </div>

        <p style="color:#94a3b8;font-size:12px;text-align:center;">Sản phẩm có thể hết hàng bất cứ lúc nào.</p>
    </div>'
    . _emailFooter(true); // includeUnsubscribe = true

    return [
        'subject' => 'Bạn quên gì đó trong giỏ hàng kìa! | ' . BRAND_NAME,
        'html'    => _emailWrapper($body),
    ];
}

// ═══════════════════════════════════════════════════════════
// TEMPLATE 5: LIÊN HỆ TỪ KHÁCH HÀNG (Contact Notification)
// ═══════════════════════════════════════════════════════════

/**
 * Email gửi cho ADMIN khi khách hàng gửi form liên hệ.
 * 
 * @param array $data [
 *   'customerName'  => string,
 *   'customerEmail' => string,
 *   'message'       => string,
 *   'sentAt'        => string (formatted datetime, optional),
 * ]
 */
function getContactNotificationTemplate($data) {
    $name    = htmlspecialchars($data['customerName'] ?? 'Không rõ');
    $email   = htmlspecialchars($data['customerEmail'] ?? '');
    $message = nl2br(htmlspecialchars($data['message'] ?? ''));
    $sentAt  = htmlspecialchars($data['sentAt'] ?? date('d/m/Y H:i:s'));

    $preheader = _emailPreheader("Thư liên hệ mới từ $name ($email)");

    $body = $preheader
          . _emailHeader('THƯ LIÊN HỆ MỚI', '#0f172a')
          . '
    <div style="padding:28px 24px;">
        <p style="color:#64748b;font-size:14px;line-height:1.6;">Có khách hàng vừa gửi thông điệp qua form liên hệ trên website.</p>

        <!-- Customer Info -->
        <div style="background:#f8fafc;border-radius:12px;padding:20px;margin:20px 0;border-left:4px solid #2563eb;">
            <table style="width:100%;font-size:14px;color:#334155;">
                <tr>
                    <td style="padding:8px 0;width:100px;color:#94a3b8;vertical-align:top;"><strong>Họ tên:</strong></td>
                    <td style="padding:8px 0;font-weight:700;color:#1e293b;">' . $name . '</td>
                </tr>
                <tr>
                    <td style="padding:8px 0;color:#94a3b8;vertical-align:top;"><strong>Email:</strong></td>
                    <td style="padding:8px 0;">
                        <a href="mailto:' . $email . '" style="color:#2563eb;text-decoration:none;font-weight:600;">' . $email . '</a>
                    </td>
                </tr>
                <tr>
                    <td style="padding:8px 0;color:#94a3b8;vertical-align:top;"><strong>Thời gian:</strong></td>
                    <td style="padding:8px 0;color:#64748b;">' . $sentAt . '</td>
                </tr>
            </table>
        </div>

        <!-- Message Content -->
        <div style="margin:20px 0;">
            <p style="color:#94a3b8;font-size:12px;text-transform:uppercase;letter-spacing:1px;font-weight:700;margin-bottom:8px;">Nội dung tin nhắn</p>
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;color:#334155;font-size:14px;line-height:1.8;">
                ' . $message . '
            </div>
        </div>

        <!-- Quick Reply CTA -->
        <div style="text-align:center;margin:28px 0;">
            <a href="mailto:' . $email . '?subject=Re: Phản hồi từ ' . BRAND_NAME . '" style="display:inline-block;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;text-decoration:none;padding:14px 36px;border-radius:10px;font-weight:700;font-size:15px;">Trả lời khách hàng</a>
        </div>

        <p style="color:#cbd5e1;font-size:12px;text-align:center;">Email này được gửi tự động từ hệ thống ' . BRAND_NAME . '</p>
    </div>'
    . _emailFooter();

    return [
        'subject' => 'Liên hệ mới từ ' . $name . ' — ' . BRAND_NAME,
        'html'    => _emailWrapper($body),
    ];
}

