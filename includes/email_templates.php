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

function _emailPreheader($text)
{
    return '<div style="display:none;font-size:1px;color:#f8f9fa;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">'
        . htmlspecialchars($text)
        . '</div>';
}

function _emailHeader($title, $bgColor = '#2563eb')
{
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

function _emailFooter($includeUnsubscribe = false)
{
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

function _emailWrapper($content)
{
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
function getOrderConfirmationTemplate($data)
{
    $customerName = htmlspecialchars($data['customerName'] ?? 'Quý khách');
    $phone = htmlspecialchars($data['phone'] ?? '');
    $address = htmlspecialchars($data['address'] ?? '');
    $orderCode = htmlspecialchars($data['orderCode'] ?? '');
    $totalFmt = number_format($data['total'] ?? 0, 0, ',', '.');
    $paymentMethod = htmlspecialchars($data['paymentMethod'] ?? 'COD');
    $trackingLink = $data['trackingLink'] ?? '#';

    // Build product rows
    $productRows = '';
    foreach (($data['products'] ?? []) as $p) {
        $pName = htmlspecialchars($p['name'] ?? '');
        $pQty = (int) ($p['qty'] ?? 1);
        $pPrice = number_format(($p['price'] ?? 0) * $pQty, 0, ',', '.');
        $pSize = !empty($p['size']) ? ' · Size: ' . $p['size'] : '';

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

    $preheader = _emailPreheader("Đơn #$orderCode đã chốt — tổng {$totalFmt}đ. Xem chi tiết bên trong.");

    $body = $preheader
        . _emailHeader('Đơn hàng đã chốt')
        . '
    <div style="padding:28px 24px;">
        <p style="color:#334155;font-size:15px;line-height:1.6;"><strong>' . $customerName . '</strong> ơi,</p>
        <p style="color:#64748b;font-size:14px;line-height:1.6;">Bên mình nhận đơn của bạn rồi nha. Đây là thông tin để bạn kiểm tra lại:</p>

        <!-- Order Info -->
        <div style="background:#f8fafc;border-radius:12px;padding:16px;margin:20px 0;">
            <table style="width:100%;font-size:13px;color:#475569;">
                <tr><td style="padding:6px 0;"><strong>Mã đơn:</strong></td><td style="padding:6px 0;text-align:right;font-weight:700;color:#2563eb;">#' . $orderCode . '</td></tr>
                <tr><td style="padding:6px 0;"><strong>Người nhận:</strong></td><td style="padding:6px 0;text-align:right;">' . $customerName . '</td></tr>
                <tr><td style="padding:6px 0;"><strong>SĐT:</strong></td><td style="padding:6px 0;text-align:right;">' . $phone . '</td></tr>
                <tr><td style="padding:6px 0;"><strong>Giao tới:</strong></td><td style="padding:6px 0;text-align:right;">' . $address . '</td></tr>
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
            <span style="color:rgba(255,255,255,0.8);font-size:13px;">TỔNG CỘNG</span>
            <div style="color:#fff;font-size:28px;font-weight:800;margin-top:4px;">' . $totalFmt . '&nbsp;đ</div>
        </div>

        <p style="color:#64748b;font-size:14px;line-height:1.6;">Bạn có thể theo dõi tình trạng đơn bất cứ lúc nào tại đây:</p>

        <!-- CTA -->
        <div style="text-align:center;margin:24px 0;">
            <a href="' . $trackingLink . '" style="display:inline-block;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;text-decoration:none;padding:14px 36px;border-radius:10px;font-weight:700;font-size:15px;">Xem đơn hàng của tôi</a>
        </div>

        <p style="color:#94a3b8;font-size:12px;text-align:center;">Có gì thắc mắc thì gọi ngay <strong>' . BRAND_PHONE . '</strong>, mình hỗ trợ liền.</p>
    </div>'
        . _emailFooter();

    // Subject A: Shop Sneakers — đơn #ORD123 đã chốt, chờ giao thôi!
    // Subject B: Nhận đơn rồi nha #ORD123 · Shop Sneakers
    return [
        'subject' => BRAND_NAME . ' — đơn #' . $orderCode . ' đã chốt, chờ giao thôi!',
        'html' => _emailWrapper($body),
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
function getOTPTemplate($data)
{
    $name = htmlspecialchars($data['customerName'] ?? 'Quý khách');
    $otp = htmlspecialchars($data['otpCode'] ?? '------');
    $expiry = (int) ($data['expiryMinutes'] ?? 5);

    $preheader = _emailPreheader("Mã của bạn: $otp — dùng trong $expiry phút.");

    $body = $preheader
        . _emailHeader('Mã xác thực của bạn', '#059669')
        . '
    <div style="padding:32px 24px;text-align:center;">
        <p style="color:#334155;font-size:15px;"><strong>' . $name . '</strong> ơi,</p>
        <p style="color:#64748b;font-size:14px;">Nhập mã bên dưới để hoàn tất xác thực. Đừng gửi mã này cho ai khác nhé.</p>

        <div style="background:#f0fdf4;border:2px dashed #22c55e;border-radius:16px;padding:24px;margin:28px auto;max-width:280px;">
            <div style="font-size:40px;font-weight:900;letter-spacing:8px;color:#16a34a;font-family:monospace;">' . $otp . '</div>
        </div>

        <p style="color:#dc2626;font-size:13px;font-weight:700;">
            <span style="display:inline-block;width:16px;height:16px;background:#dc2626;color:#fff;border-radius:4px;text-align:center;line-height:16px;font-size:10px;margin-right:4px;vertical-align:middle;">&#10033;</span>
            Mã này chỉ dành cho bạn &mdash; không chia sẻ với ai
        </p>
        <p style="color:#94a3b8;font-size:13px;">Mã hết hạn sau <strong>' . $expiry . ' phút</strong>.</p>
        <p style="color:#94a3b8;font-size:12px;margin-top:20px;">Không phải bạn? Bỏ qua email này, tài khoản vẫn an toàn.</p>
    </div>'
        . _emailFooter();

    // Subject A: Mã xác thực: 123456 · Shop Sneakers
    // Subject B: 123456 là mã OTP của bạn (hết hạn sau 5 phút)
    return [
        'subject' => 'Mã xác thực: ' . $otp . ' · ' . BRAND_NAME,
        'html' => _emailWrapper($body),
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
function getPasswordResetTemplate($data)
{
    $name = htmlspecialchars($data['customerName'] ?? 'Quý khách');
    $resetLink = $data['resetLink'] ?? '#';
    $expiry = (int) ($data['expiryMinutes'] ?? 30);

    $preheader = _emailPreheader("Đặt lại mật khẩu — link dùng được trong $expiry phút.");

    $body = $preheader
        . _emailHeader('Đặt lại mật khẩu', '#7c3aed')
        . '
    <div style="padding:32px 24px;">
        <p style="color:#334155;font-size:15px;line-height:1.6;"><strong>' . $name . '</strong> ơi,</p>
        <p style="color:#64748b;font-size:14px;line-height:1.6;">Ai đó (hy vọng là bạn) vừa yêu cầu đổi mật khẩu. Bấm nút bên dưới để tạo mật khẩu mới:</p>

        <div style="text-align:center;margin:28px 0;">
            <a href="' . $resetLink . '" style="display:inline-block;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;text-decoration:none;padding:14px 40px;border-radius:10px;font-weight:700;font-size:15px;">Tạo mật khẩu mới</a>
        </div>

        <p style="color:#94a3b8;font-size:13px;line-height:1.6;">Link này hết hạn sau <strong>' . $expiry . ' phút</strong>.</p>
        <p style="color:#94a3b8;font-size:13px;line-height:1.6;">Không phải bạn yêu cầu? Cứ bỏ qua email này — mật khẩu cũ vẫn giữ nguyên, không ai truy cập được.</p>

        <hr style="border:none;border-top:1px solid #e2e8f0;margin:24px 0;">
        <p style="color:#cbd5e1;font-size:11px;text-align:center;">Nút không bấm được? Copy link này vào trình duyệt:<br>
        <a href="' . $resetLink . '" style="color:#7c3aed;word-break:break-all;font-size:11px;">' . $resetLink . '</a></p>
    </div>'
        . _emailFooter();

    // Subject A: Đặt lại mật khẩu tài khoản Shop Sneakers
    // Subject B: Bạn vừa yêu cầu đổi mật khẩu · Shop Sneakers
    return [
        'subject' => 'Đặt lại mật khẩu tài khoản ' . BRAND_NAME,
        'html' => _emailWrapper($body),
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
function getAbandonedCartTemplate($data)
{
    $name = htmlspecialchars($data['customerName'] ?? 'bạn');
    $cartLink = $data['cartLink'] ?? '#';
    $couponCode = $data['couponCode'] ?? null;
    $couponText = $data['couponText'] ?? 'Giảm 5%';

    // Product list
    $productRows = '';
    foreach (($data['products'] ?? []) as $p) {
        $pName = htmlspecialchars($p['name'] ?? '');
        $pPrice = number_format($p['price'] ?? 0, 0, ',', '.');
        $pImg = $p['image'] ?? '';

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
            <p style="color:#92400e;font-size:13px;margin:0 0 8px;font-weight:600;">Bonus nhỏ cho bạn — dùng ngay kẻ0 mất</p>
            <div style="background:#fff;border-radius:8px;padding:10px 20px;display:inline-block;">
                <span style="font-size:20px;font-weight:900;color:#d97706;letter-spacing:2px;">' . htmlspecialchars($couponCode) . '</span>
            </div>
            <p style="color:#b45309;font-size:12px;margin:8px 0 0;">' . htmlspecialchars($couponText) . ' khi checkout</p>
        </div>';
    }

    $preheader = _emailPreheader("Giỏ hàng của bạn vẫn còn đây nha!");

    $body = $preheader
        . _emailHeader('Giỏ hàng vẫn chờ bạn', '#f59e0b')
        . '
    <div style="padding:28px 24px;">
        <p style="color:#334155;font-size:15px;line-height:1.6;"><strong>' . $name . '</strong> ơi,</p>
        <p style="color:#64748b;font-size:14px;line-height:1.6;">Mấy món này bạn xem hôm trước vẫn nằm trong giỏ. Mình giữ cho bạn nhưng không chắc được lâu đâu nha:</p>

        <table style="width:100%;border-collapse:collapse;margin:20px 0;">
            ' . $productRows . '
        </table>

        ' . $couponHtml . '

        <div style="text-align:center;margin:24px 0;">
            <a href="' . $cartLink . '" style="display:inline-block;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;text-decoration:none;padding:14px 36px;border-radius:10px;font-weight:700;font-size:15px;">Quay lại giỏ hàng</a>
        </div>

        <p style="color:#94a3b8;font-size:12px;text-align:center;">Số lượng có hạn — hết là hết nha.</p>
    </div>'
        . _emailFooter(true); // includeUnsubscribe = true

    // Subject A: giỏ hàng của bạn vẫn đang chờ · Shop Sneakers
    // Subject B: mấy món bạn thích sắp hết rồi kìa!
    return [
        'subject' => 'Giỏ hàng của bạn vẫn đang chờ · ' . BRAND_NAME,
        'html' => _emailWrapper($body),
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
function getContactNotificationTemplate($data)
{
    $name = htmlspecialchars($data['customerName'] ?? 'Không rõ');
    $email = htmlspecialchars($data['customerEmail'] ?? '');
    $message = nl2br(htmlspecialchars($data['message'] ?? ''));
    $sentAt = htmlspecialchars($data['sentAt'] ?? date('d/m/Y H:i:s'));

    $preheader = _emailPreheader("Liên hệ mới: $name ($email)");

    $body = $preheader
        . _emailHeader('Có người liên hệ', '#0f172a')
        . '
    <div style="padding:28px 24px;">
        <!-- Customer Info -->
        <div style="background:#f8fafc;border-radius:12px;padding:20px;margin:0 0 20px;border-left:4px solid #2563eb;">
            <table style="width:100%;font-size:14px;color:#334155;">
                <tr>
                    <td style="padding:6px 0;width:80px;color:#94a3b8;"><strong>Từ:</strong></td>
                    <td style="padding:6px 0;font-weight:700;color:#1e293b;">' . $name . '</td>
                </tr>
                <tr>
                    <td style="padding:6px 0;color:#94a3b8;"><strong>Email:</strong></td>
                    <td style="padding:6px 0;"><a href="mailto:' . $email . '" style="color:#2563eb;text-decoration:none;font-weight:600;">' . $email . '</a></td>
                </tr>
                <tr>
                    <td style="padding:6px 0;color:#94a3b8;"><strong>Lúc:</strong></td>
                    <td style="padding:6px 0;color:#64748b;">' . $sentAt . '</td>
                </tr>
            </table>
        </div>

        <!-- Message Content -->
        <div style="margin:0 0 20px;">
            <p style="color:#94a3b8;font-size:11px;text-transform:uppercase;letter-spacing:1px;font-weight:700;margin:0 0 8px;">NỘI DUNG</p>
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;color:#334155;font-size:14px;line-height:1.8;">
                ' . $message . '
            </div>
        </div>

        <!-- Quick Reply CTA -->
        <div style="text-align:center;margin:24px 0;">
            <a href="mailto:' . $email . '?subject=Re: Phản hồi từ ' . BRAND_NAME . '" style="display:inline-block;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;text-decoration:none;padding:14px 36px;border-radius:10px;font-weight:700;font-size:15px;">Trả lời ngay</a>
        </div>
    </div>'
        . _emailFooter();

    // Subject A: [Liên hệ] Nguyễn Văn A — Shop Sneakers
    // Subject B: có người nhắn tin · Nguyễn Văn A (email@gmail.com)
    return [
        'subject' => '[Liên hệ] ' . $name . ' — ' . BRAND_NAME,
        'html' => _emailWrapper($body),
    ];
}

