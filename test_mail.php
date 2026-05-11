<?php
/**
 * Test gửi mail Brevo — XÓA FILE NÀY SAU KHI DEBUG XONG!
 * Truy cập: https://domain/test_mail.php?key=testmail2026&email=your@email.com
 */
if (($_GET['key'] ?? '') !== 'testmail2026') {
    http_response_code(404);
    echo "Not Found";
    exit;
}

require_once __DIR__ . '/config.php';
include_once BASE_PATH . 'includes/mail_helper.php';

header('Content-Type: text/html; charset=utf-8');

$toEmail = trim($_GET['email'] ?? '');
if (empty($toEmail)) {
    echo "<h2>❌ Thiếu param email</h2>";
    echo "<p>Dùng: ?key=testmail2026&email=your@email.com</p>";
    exit;
}

echo "<h1>🧪 Test Brevo Mail</h1>";

// Check API key
$apiKey = getenv('BREVO_API_KEY');
echo "<p><b>BREVO_API_KEY:</b> " . (empty($apiKey) ? '❌ CHƯA SET!' : '✅ Đã set (' . strlen($apiKey) . ' ký tự, bắt đầu: ' . substr($apiKey, 0, 8) . '...)') . "</p>";

if (empty($apiKey)) {
    echo "<h2 style='color:red'>⚠️ Không tìm thấy BREVO_API_KEY trong environment! Hãy set biến này trên Railway.</h2>";
    exit;
}

// Test gửi
$htmlBody = '<h2>Test email từ Shop Sneakers</h2><p>Nếu bạn thấy email này, Brevo đang hoạt động! 🎉</p><p>Thời gian: ' . date('Y-m-d H:i:s') . '</p>';

echo "<p>Đang gửi tới: <b>$toEmail</b>...</p>";

// Gọi trực tiếp API để xem response chi tiết
$payload = json_encode([
    'sender'      => ['name' => 'Shop Sneakers', 'email' => 'remkyorosi@gmail.com'],
    'to'          => [['email' => $toEmail, 'name' => 'Test User']],
    'subject'     => 'Test Brevo Mail - ' . date('H:i:s'),
    'htmlContent' => $htmlBody,
]);

$ch = curl_init('https://api.brevo.com/v3/smtp/email');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 15,
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
$curlErrno = curl_errno($ch);
curl_close($ch);

echo "<hr>";
echo "<p><b>HTTP Code:</b> $httpCode</p>";
echo "<p><b>Curl Error:</b> " . ($curlError ?: 'Không có') . " (errno: $curlErrno)</p>";
echo "<p><b>Response:</b></p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

if ($httpCode >= 200 && $httpCode < 300) {
    echo "<h2 style='color:green'>✅ Gửi thành công! Kiểm tra hộp thư (kể cả Spam).</h2>";
} else {
    echo "<h2 style='color:red'>❌ Gửi thất bại! Xem response ở trên để tìm nguyên nhân.</h2>";
}

echo "<p style='color:red; font-weight:bold; margin-top:20px;'>⚠️ Xóa file này sau khi debug xong!</p>";
?>
