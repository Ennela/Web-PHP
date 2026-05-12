<?php
/**
 * Mail Helper — Gửi email qua Mailjet HTTP API v3.1.
 * Miễn phí 200 email/ngày, gửi được đến MỌI địa chỉ email.
 * Không cần verify domain, không bị block port trên Cloud.
 *
 * Cấu hình: Thêm biến môi trường MAILJET_API_KEY & MAILJET_SECRET_KEY trên Railway.
 */

/**
 * Gửi email qua Mailjet API.
 * Giữ tên hàm cũ (sendMailBrevo) để không phải sửa toàn bộ project.
 * @param string $toEmail   Email người nhận
 * @param string $toName    Tên người nhận
 * @param string $subject   Tiêu đề
 * @param string $htmlBody  Nội dung HTML
 * @return bool             true nếu gửi thành công
 */
function sendMailBrevo($toEmail, $toName, $subject, $htmlBody) {
    $apiKey    = getenv('MAILJET_API_KEY');
    $secretKey = getenv('MAILJET_SECRET_KEY');

    if (empty($apiKey) || empty($secretKey)) {
        error_log("MAILJET_MAIL_ERROR: MAILJET_API_KEY or MAILJET_SECRET_KEY is NOT set! Email will not be sent. To=$toEmail | Subject=$subject");
        return false;
    }
    if (empty($toEmail)) {
        error_log("MAILJET_MAIL_ERROR: toEmail is empty! Subject=$subject");
        return false;
    }

    $payload = json_encode([
        'Messages' => [
            [
                'From' => [
                    'Email' => 'remkyorosi@gmail.com',
                    'Name'  => 'Shop Sneakers',
                ],
                'To' => [
                    [
                        'Email' => $toEmail,
                        'Name'  => $toName,
                    ],
                ],
                'Subject'  => $subject,
                'HTMLPart' => $htmlBody,
            ],
        ],
    ]);

    $ch = curl_init('https://api.mailjet.com/v3.1/send');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_USERPWD        => $apiKey . ':' . $secretKey,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
        ],
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $success = ($httpCode >= 200 && $httpCode < 300);

    if (!$success) {
        error_log("MAILJET_MAIL_ERROR: HTTP=$httpCode | Response=$response | CurlError=$curlError | To=$toEmail | Subject=$subject");
    }

    return $success;
}
