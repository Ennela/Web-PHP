<?php
/**
 * Mail Helper — Gửi email qua Brevo (Sendinblue) HTTP API.
 * Miễn phí 300 email/ngày, gửi được đến MỌI địa chỉ email.
 * Không cần verify domain, không bị block port trên Cloud.
 *
 * Cấu hình: Thêm biến môi trường BREVO_API_KEY trên Railway.
 */

/**
 * Gửi email qua Brevo API.
 * @param string $toEmail   Email người nhận
 * @param string $toName    Tên người nhận
 * @param string $subject   Tiêu đề
 * @param string $htmlBody  Nội dung HTML
 * @return bool             true nếu gửi thành công
 */
function sendMailBrevo($toEmail, $toName, $subject, $htmlBody) {
    $apiKey = getenv('BREVO_API_KEY');
    if (empty($apiKey)) {
        error_log("BREVO_MAIL_ERROR: BREVO_API_KEY is NOT set! Email will not be sent. To=$toEmail | Subject=$subject");
        return false;
    }
    if (empty($toEmail)) {
        error_log("BREVO_MAIL_ERROR: toEmail is empty! Subject=$subject");
        return false;
    }

    $payload = json_encode([
        'sender'      => ['name' => 'Shop Sneakers', 'email' => 'remkyorosi@gmail.com'],
        'to'          => [['email' => $toEmail, 'name' => $toName]],
        'subject'     => $subject,
        'htmlContent' => $htmlBody,
    ]);

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER     => [
            'api-key: ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $success = ($httpCode >= 200 && $httpCode < 300);

    if (!$success) {
        error_log("BREVO_MAIL_ERROR: HTTP=$httpCode | Response=$response | CurlError=$curlError | To=$toEmail | Subject=$subject");
    }

    return $success;
}
