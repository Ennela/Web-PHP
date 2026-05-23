<?php
/**
 * Mailjet Webhook Handler
 * 
 * Endpoint: POST /webhooks/mailjet_webhook.php
 * 
 * Xử lý các sự kiện từ Mailjet:
 * - bounce    → Đánh dấu email không hợp lệ, ngừng gửi
 * - spam      → Đưa vào blacklist ngay lập tức
 * - blocked   → Ghi log, đánh dấu
 * - unsub     → Xóa khỏi danh sách marketing
 *
 * Cấu hình trên Mailjet Dashboard:
 * 1. Vào Account Settings → Event Tracking (Webhooks)
 * 2. Thêm URL: https://yourdomain.com/webhooks/mailjet_webhook.php
 * 3. Chọn events: bounce, spam, blocked, unsub
 */

// Chỉ chấp nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Load config & DB
require_once dirname(__DIR__) . '/config.php';
include BASE_PATH . 'includes/connect.php';
require_once BASE_PATH . 'includes/mail_helper.php';

// ─── Đọc payload từ Mailjet ───
$rawInput = file_get_contents('php://input');
$events   = json_decode($rawInput, true);

if (empty($events) || !is_array($events)) {
    error_log("MAILJET_WEBHOOK: Invalid payload received");
    http_response_code(400);
    exit('Bad Request');
}

// ─── Xác thực webhook (optional nhưng recommended) ───
$webhookSecret = getenv('MAILJET_WEBHOOK_SECRET');
if (!empty($webhookSecret)) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
    if ($authHeader !== $webhookSecret) {
        error_log("MAILJET_WEBHOOK: Unauthorized request");
        http_response_code(401);
        exit('Unauthorized');
    }
}

// ─── Xử lý từng event ───
$processed = 0;

foreach ($events as $event) {
    $eventType = $event['event']  ?? '';
    $email     = $event['email']  ?? '';
    $messageId = $event['MessageID'] ?? '';
    $timestamp = $event['time']   ?? time();

    if (empty($email)) continue;

    $emailSafe  = mysqli_real_escape_string($con, strtolower(trim($email)));
    $eventSafe  = mysqli_real_escape_string($con, $eventType);
    $msgIdSafe  = mysqli_real_escape_string($con, $messageId);
    $errorInfo  = mysqli_real_escape_string($con, json_encode($event['error_related_to'] ?? $event['source'] ?? ''));

    switch ($eventType) {
        // ━━━ HARD BOUNCE: Email không tồn tại / bị chặn vĩnh viễn ━━━
        case 'bounce':
            $hardBounce = ($event['hard_bounce'] ?? false) === true;
            
            if ($hardBounce) {
                // Đưa vào blacklist — ngừng gửi vĩnh viễn
                addToBlacklist($email, 'hard_bounce');
                error_log("MAILJET_WEBHOOK: HARD BOUNCE → Blacklisted. Email=$email");
            } else {
                // Soft bounce — ghi log nhưng chưa block
                error_log("MAILJET_WEBHOOK: Soft bounce. Email=$email Error=$errorInfo");
            }

            // Log vào bảng webhook
            logWebhookEvent($con, $emailSafe, 'bounce', $msgIdSafe, $errorInfo, $timestamp);
            $processed++;
            break;

        // ━━━ SPAM COMPLAINT: Khách báo cáo spam ━━━
        case 'spam':
            // Blacklist NGAY LẬP TỨC — bảo vệ Sender Reputation
            addToBlacklist($email, 'spam_complaint');
            error_log("MAILJET_WEBHOOK: SPAM COMPLAINT → Blacklisted immediately. Email=$email");
            
            logWebhookEvent($con, $emailSafe, 'spam', $msgIdSafe, $errorInfo, $timestamp);
            $processed++;
            break;

        // ━━━ BLOCKED: Mailjet chặn email (reputation quá thấp) ━━━
        case 'blocked':
            addToBlacklist($email, 'blocked');
            error_log("MAILJET_WEBHOOK: BLOCKED → Blacklisted. Email=$email");

            logWebhookEvent($con, $emailSafe, 'blocked', $msgIdSafe, $errorInfo, $timestamp);
            $processed++;
            break;

        // ━━━ UNSUBSCRIBE: Khách hủy đăng ký ━━━
        case 'unsub':
            // Đánh dấu không gửi marketing nữa (nhưng vẫn gửi transactional)
            $now = time();
            mysqli_query($con, 
                "UPDATE `tbl_tkkhachhang` SET `email_marketing_opt_out` = 1 
                 WHERE LOWER(`email`) = '$emailSafe'"
            );
            error_log("MAILJET_WEBHOOK: UNSUBSCRIBE. Email=$email");

            logWebhookEvent($con, $emailSafe, 'unsub', $msgIdSafe, '', $timestamp);
            $processed++;
            break;

        default:
            error_log("MAILJET_WEBHOOK: Unknown event type '$eventType' for Email=$email");
            break;
    }
}

// ─── Response ───
http_response_code(200);
echo json_encode(['processed' => $processed]);
mysqli_close($con);

// ═══════════════════════════════════════════════════════════
// HELPER: Log webhook event vào DB
// ═══════════════════════════════════════════════════════════
function logWebhookEvent($con, $email, $event, $messageId, $errorInfo, $timestamp) {
    $sql = "INSERT INTO `tbl_email_webhook_log` (`email`, `event_type`, `message_id`, `error_info`, `event_time`, `created_at`)
            VALUES ('$email', '$event', '$messageId', '$errorInfo', $timestamp, " . time() . ")";
    mysqli_query($con, $sql);
}
