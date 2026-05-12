<?php
/**
 * Test script để debug Brevo email trên production.
 * Truy cập: https://your-domain/test_mail.php
 * XÓA FILE NÀY SAU KHI DEBUG XONG!
 */
require_once __DIR__ . '/config.php';
require_once BASE_PATH . 'includes/mail_helper.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== BREVO EMAIL DIAGNOSTIC ===\n\n";

// 1. Check API Key
$apiKey = getenv('BREVO_API_KEY');
echo "1. BREVO_API_KEY: ";
if (empty($apiKey)) {
    echo "❌ NOT SET! This is why emails are not sending.\n";
    echo "   → Go to Railway Dashboard → Service → Variables → Add BREVO_API_KEY\n\n";
} else {
    echo "✅ Set (length=" . strlen($apiKey) . ", starts with: " . substr($apiKey, 0, 8) . "...)\n\n";
}

// 2. Check cURL extension
echo "2. cURL extension: ";
echo function_exists('curl_init') ? "✅ Loaded\n" : "❌ NOT loaded!\n";
echo "\n";

// 3. Check json extension
echo "3. JSON extension: ";
echo function_exists('json_encode') ? "✅ Loaded\n" : "❌ NOT loaded!\n";
echo "\n";

// 4. Test Brevo API connection (without sending email)
echo "4. Brevo API connectivity test:\n";
if (!empty($apiKey)) {
    $ch = curl_init('https://api.brevo.com/v3/account');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER     => [
            'api-key: ' . $apiKey,
            'Accept: application/json',
        ],
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        echo "   ❌ cURL Error: $curlError\n";
    } else {
        echo "   HTTP Status: $httpCode\n";
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            echo "   ✅ API Key is VALID!\n";
            echo "   Company: " . ($data['companyName'] ?? 'N/A') . "\n";
            echo "   Email: " . ($data['email'] ?? 'N/A') . "\n";
            // Check email credits
            if (isset($data['plan'])) {
                echo "   Plan: " . json_encode($data['plan']) . "\n";
            }
        } elseif ($httpCode === 401) {
            echo "   ❌ API Key is INVALID or EXPIRED!\n";
            echo "   Response: $response\n";
            echo "   → Generate a new API key at: https://app.brevo.com/settings/keys/api\n";
        } else {
            echo "   ⚠️ Unexpected response:\n";
            echo "   Response: $response\n";
        }
    }
} else {
    echo "   ⏭️ Skipped (no API key)\n";
}
echo "\n";

// 5. Check sender verification
echo "5. Sender verification check:\n";
if (!empty($apiKey)) {
    $ch = curl_init('https://api.brevo.com/v3/senders');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            'api-key: ' . $apiKey,
            'Accept: application/json',
        ],
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        $senders = $data['senders'] ?? [];
        $found = false;
        echo "   Registered senders:\n";
        foreach ($senders as $s) {
            $active = $s['active'] ? '✅' : '❌';
            echo "   $active {$s['name']} <{$s['email']}>\n";
            if ($s['email'] === 'remkyorosi@gmail.com' && $s['active']) {
                $found = true;
            }
        }
        if (!$found) {
            echo "   ⚠️ remkyorosi@gmail.com is NOT verified as sender!\n";
            echo "   → Go to Brevo → Settings → Senders & IPs → Add & verify this email\n";
        } else {
            echo "   ✅ remkyorosi@gmail.com is verified!\n";
        }
    } else {
        echo "   Could not check senders (HTTP $httpCode)\n";
    }
} else {
    echo "   ⏭️ Skipped (no API key)\n";
}
echo "\n";

// 6. Test send (only if ?send=1 is in URL)
if (isset($_GET['send'])) {
    $testEmail = $_GET['to'] ?? 'remkyorosi@gmail.com';
    echo "6. SENDING TEST EMAIL to: $testEmail\n";
    $result = sendMailBrevo(
        $testEmail,
        'Test User',
        'Test Email from Shop Sneakers - ' . date('H:i:s'),
        '<h2>Test Email</h2><p>This is a test email sent at ' . date('Y-m-d H:i:s') . '</p><p>If you receive this, Brevo API is working correctly!</p>'
    );
    echo "   Result: " . ($result ? "✅ SUCCESS" : "❌ FAILED (check error_log)") . "\n";
} else {
    echo "6. To send a test email, visit: test_mail.php?send=1\n";
    echo "   Or to a specific email: test_mail.php?send=1&to=your@email.com\n";
}

echo "\n=== END DIAGNOSTIC ===\n";
