<?php
/**
 * Test script để debug Mailjet email trên production.
 * Truy cập: https://your-domain/test_mail.php
 * XÓA FILE NÀY SAU KHI DEBUG XONG!
 */
require_once __DIR__ . '/config.php';
require_once BASE_PATH . 'includes/mail_helper.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== MAILJET EMAIL DIAGNOSTIC ===\n\n";

// 1. Check API Keys
$apiKey    = getenv('MAILJET_API_KEY');
$secretKey = getenv('MAILJET_SECRET_KEY');
echo "1. MAILJET_API_KEY: ";
if (empty($apiKey)) {
    echo "❌ NOT SET!\n";
} else {
    echo "✅ Set (length=" . strlen($apiKey) . ")\n";
}
echo "   MAILJET_SECRET_KEY: ";
if (empty($secretKey)) {
    echo "❌ NOT SET!\n";
} else {
    echo "✅ Set (length=" . strlen($secretKey) . ")\n";
}
echo "\n";

// 2. Check cURL extension
echo "2. cURL extension: ";
echo function_exists('curl_init') ? "✅ Loaded\n" : "❌ NOT loaded!\n";
echo "\n";

// 3. Test send (only if ?send=1 is in URL)
if (isset($_GET['send'])) {
    $testEmail = $_GET['to'] ?? 'remkyorosi@gmail.com';
    echo "3. SENDING TEST EMAIL to: $testEmail\n";

    $payload = json_encode([
        'Messages' => [
            [
                'From' => ['Email' => 'remkyorosi@gmail.com', 'Name' => 'Shop Sneakers'],
                'To' => [['Email' => $testEmail, 'Name' => 'Test User']],
                'Subject' => 'Test Email from Shop Sneakers - ' . date('H:i:s'),
                'HTMLPart' => '<h2>✅ Test Email thành công!</h2><p>Gửi lúc: ' . date('Y-m-d H:i:s') . '</p><p>Mailjet API is working correctly!</p>',
            ],
        ],
    ]);

    $ch = curl_init('https://api.mailjet.com/v3.1/send');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_USERPWD        => $apiKey . ':' . $secretKey,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);
    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
    curl_close($ch);

    echo "   HTTP Status: $httpCode\n";
    echo "   Response: $response\n";
    echo "   cURL Error: " . ($curlError ?: '(none)') . "\n";
    echo "   Total Time: {$totalTime}s\n\n";

    if ($httpCode >= 200 && $httpCode < 300) {
        echo "   ✅ SUCCESS — Email sent! Check inbox (and spam folder).\n";
    } else {
        echo "   ❌ FAILED\n";
        if ($httpCode === 401) echo "   → API key/secret invalid. Check Mailjet dashboard.\n";
        if ($httpCode === 400) echo "   → Bad request. Sender email may need verification on Mailjet.\n";
    }
} else {
    echo "3. To send a test email, visit: test_mail.php?send=1\n";
    echo "   Or to a specific email: test_mail.php?send=1&to=your@email.com\n";
}

echo "\n=== END DIAGNOSTIC ===\n";
