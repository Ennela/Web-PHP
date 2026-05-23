<?php
/**
 * Mail Helper v2.0 — Gửi email qua Mailjet HTTP API v3.1.
 * 
 * Tính năng:
 * - Phân luồng Transactional / Marketing / Admin
 * - Retry với Exponential Backoff (lỗi 5xx, Rate Limit)
 * - Email Blacklist check (Bounce/Spam)
 * - Priority headers cho Transactional emails
 * - Unsubscribe headers cho Marketing emails
 *
 * Backward compatible: hàm sendMailBrevo() vẫn hoạt động.
 */

require_once __DIR__ . '/mail_config.php';

// ═══════════════════════════════════════════════════════════
// CORE: Gửi email qua Mailjet API
// ═══════════════════════════════════════════════════════════

/**
 * Gửi email qua Mailjet API v3.1 với retry logic.
 *
 * @param string $toEmail    Email người nhận
 * @param string $toName     Tên người nhận
 * @param string $subject    Tiêu đề
 * @param string $htmlBody   Nội dung HTML
 * @param string $mailType   MAIL_TYPE_TRANSACTIONAL | MAIL_TYPE_MARKETING | MAIL_TYPE_ADMIN
 * @param array  $options    Tùy chọn bổ sung:
 *   - 'templateId'  => int    (ID template trên Mailjet)
 *   - 'variables'   => array  (Biến truyền vào template)
 *   - 'customId'    => string (ID tùy chỉnh để tracking)
 *   - 'priority'    => int    (1-4, mặc định: 2 cho transactional)
 * @return array ['success' => bool, 'messageId' => string|null, 'error' => string|null]
 */
function sendMailjet($toEmail, $toName, $subject, $htmlBody, $mailType = MAIL_TYPE_TRANSACTIONAL, $options = []) {
    $apiKey    = getenv('MAILJET_API_KEY');
    $secretKey = getenv('MAILJET_SECRET_KEY');

    // ─── Validate ───
    if (empty($apiKey) || empty($secretKey)) {
        error_log("MAILJET_ERROR: API keys not configured. To=$toEmail Subject=$subject");
        return ['success' => false, 'messageId' => null, 'error' => 'API keys not configured'];
    }
    if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        error_log("MAILJET_ERROR: Invalid recipient email. To=$toEmail");
        return ['success' => false, 'messageId' => null, 'error' => 'Invalid email'];
    }

    // ─── Blacklist Check (nếu có DB connection) ───
    if (isEmailBlacklisted($toEmail)) {
        error_log("MAILJET_SKIP: Email blacklisted. To=$toEmail");
        return ['success' => false, 'messageId' => null, 'error' => 'Email blacklisted'];
    }

    // ─── Sender Selection theo loại email ───
    switch ($mailType) {
        case MAIL_TYPE_MARKETING:
            $fromEmail = MAIL_MARKETING_FROM_EMAIL;
            $fromName  = MAIL_MARKETING_FROM_NAME;
            break;
        case MAIL_TYPE_ADMIN:
            $fromEmail = MAIL_TRANS_FROM_EMAIL;
            $fromName  = MAIL_TRANS_FROM_NAME;
            break;
        default: // TRANSACTIONAL
            $fromEmail = MAIL_TRANS_FROM_EMAIL;
            $fromName  = MAIL_TRANS_FROM_NAME;
    }

    // ─── Build Message ───
    $message = [
        'From'    => ['Email' => $fromEmail, 'Name' => $fromName],
        'To'      => [['Email' => $toEmail, 'Name' => $toName]],
        'Subject' => $subject,
    ];

    // Template mode vs HTML mode
    if (!empty($options['templateId'])) {
        $message['TemplateID']       = (int) $options['templateId'];
        $message['TemplateLanguage'] = true;
        if (!empty($options['variables'])) {
            $message['Variables'] = $options['variables'];
        }
    } else {
        $message['HTMLPart'] = $htmlBody;
    }

    // Priority cho Transactional
    if ($mailType === MAIL_TYPE_TRANSACTIONAL) {
        $message['Priority'] = $options['priority'] ?? 2;
    }

    // Unsubscribe header cho Marketing
    if ($mailType === MAIL_TYPE_MARKETING && !empty(MAIL_UNSUBSCRIBE_URL)) {
        $message['Headers'] = [
            'List-Unsubscribe' => '<' . MAIL_UNSUBSCRIBE_URL . '?email=' . urlencode($toEmail) . '>'
        ];
    }

    // Custom tracking ID
    if (!empty($options['customId'])) {
        $message['CustomID'] = $options['customId'];
    }

    $payload = json_encode(['Messages' => [$message]]);

    // ─── Send with Retry (Exponential Backoff) ───
    $maxRetries = MAIL_MAX_RETRIES;
    $lastError  = '';
    $lastCode   = 0;

    for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
        if ($attempt > 0) {
            $delay = pow(2, $attempt) * 1000000; // 2s, 4s, 8s (microseconds)
            error_log("MAILJET_RETRY: Attempt $attempt/$maxRetries after " . ($delay/1000000) . "s delay. To=$toEmail");
            usleep($delay);
        }

        $ch = curl_init('https://api.mailjet.com/v3.1/send');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => MAIL_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => MAIL_CONNECT_TIMEOUT,
            CURLOPT_USERPWD        => $apiKey . ':' . $secretKey,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Success
        if ($httpCode >= 200 && $httpCode < 300) {
            $data = json_decode($response, true);
            $messageId = $data['Messages'][0]['To'][0]['MessageID'] ?? null;
            error_log("MAILJET_OK: Type=$mailType To=$toEmail MessageID=$messageId");
            return ['success' => true, 'messageId' => $messageId, 'error' => null];
        }

        $lastError = $curlError ?: $response;
        $lastCode  = $httpCode;

        // Chỉ retry nếu 5xx hoặc 429 (Rate Limit)
        if ($httpCode < 500 && $httpCode !== 429) {
            break; // 4xx errors — don't retry
        }
    }

    error_log("MAILJET_FAIL: HTTP=$lastCode Type=$mailType To=$toEmail Error=$lastError");
    return ['success' => false, 'messageId' => null, 'error' => "HTTP $lastCode: $lastError"];
}

// ═══════════════════════════════════════════════════════════
// CONVENIENCE FUNCTIONS
// ═══════════════════════════════════════════════════════════

/** Gửi email giao dịch (OTP, đơn hàng, reset password) — Ưu tiên cao */
function sendTransactionalEmail($toEmail, $toName, $subject, $htmlBody, $options = []) {
    return sendMailjet($toEmail, $toName, $subject, $htmlBody, MAIL_TYPE_TRANSACTIONAL, $options);
}

/** Gửi email marketing (newsletter, abandoned cart) — Có Unsubscribe */
function sendMarketingEmail($toEmail, $toName, $subject, $htmlBody, $options = []) {
    return sendMailjet($toEmail, $toName, $subject, $htmlBody, MAIL_TYPE_MARKETING, $options);
}

/** Gửi thông báo cho admin */
function sendAdminNotification($subject, $htmlBody) {
    return sendMailjet(MAIL_ADMIN_EMAIL, MAIL_ADMIN_NAME, $subject, $htmlBody, MAIL_TYPE_ADMIN);
}

// ═══════════════════════════════════════════════════════════
// BACKWARD COMPATIBLE — Giữ hàm cũ để không break code
// ═══════════════════════════════════════════════════════════

/**
 * @deprecated Dùng sendTransactionalEmail() hoặc sendMarketingEmail() thay thế.
 */
function sendMailBrevo($toEmail, $toName, $subject, $htmlBody) {
    $result = sendMailjet($toEmail, $toName, $subject, $htmlBody, MAIL_TYPE_TRANSACTIONAL);
    return $result['success'];
}

// ═══════════════════════════════════════════════════════════
// BLACKLIST CHECK
// ═══════════════════════════════════════════════════════════

/**
 * Kiểm tra email có trong blacklist không (bounce/spam).
 * Trả về true nếu email bị chặn.
 */
function isEmailBlacklisted($email) {
    global $con;
    if (empty($con)) return false;

    $emailSafe = mysqli_real_escape_string($con, strtolower(trim($email)));
    $result = mysqli_query($con, "SELECT 1 FROM `tbl_email_blacklist` WHERE `email` = '$emailSafe' AND `active` = 1 LIMIT 1");
    
    if ($result && mysqli_num_rows($result) > 0) {
        return true;
    }
    return false;
}

/**
 * Thêm email vào blacklist.
 * @param string $email
 * @param string $reason  'hard_bounce' | 'spam_complaint' | 'manual'
 */
function addToBlacklist($email, $reason = 'manual') {
    global $con;
    if (empty($con)) return false;

    $emailSafe  = mysqli_real_escape_string($con, strtolower(trim($email)));
    $reasonSafe = mysqli_real_escape_string($con, $reason);
    $now        = time();

    // INSERT or UPDATE
    $sql = "INSERT INTO `tbl_email_blacklist` (`email`, `reason`, `blocked_at`, `active`)
            VALUES ('$emailSafe', '$reasonSafe', $now, 1)
            ON DUPLICATE KEY UPDATE `reason` = '$reasonSafe', `blocked_at` = $now, `active` = 1";

    return mysqli_query($con, $sql);
}
