<?php
/**
 * Session-bound CSRF protection, initialized by config.php before request handlers.
 * Browser forms use csrf_field(); JSON/multipart clients use X-CSRF-Token.
 */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        csrf_rotate_token();
    }
    return $_SESSION['csrf_token'];
}

function csrf_rotate_token(): void
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_valid_token($token): bool
{
    return is_string($token)
        && strlen($token) === 64
        && isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_json_request(): bool
{
    $script = basename($_SERVER['SCRIPT_FILENAME'] ?? '');
    return strpos($script, 'api_') === 0
        || in_array($script, ['cart_update_ajax.php', 'gallery_delete.php'], true)
        || stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
        || stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false
        || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}

function csrf_reject(int $status = 403): void
{
    http_response_code($status);
    header('Cache-Control: no-store');
    $message = $status === 405
        ? 'Thao tác này chỉ chấp nhận phương thức POST.'
        : 'Yêu cầu không hợp lệ hoặc phiên đã hết hạn. Vui lòng tải lại trang và thử lại.';
    if (csrf_json_request()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => $status === 405 ? 'METHOD_NOT_ALLOWED' : 'CSRF_INVALID',
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE);
    } else {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="vi"><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>Yêu cầu không hợp lệ</title><body><h1>Không thể thực hiện yêu cầu</h1><p>'
            . $message . '</p></body></html>';
    }
    exit;
}

function csrf_require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        header('Allow: POST');
        csrf_reject(405);
    }
}

function csrf_bootstrap(string $cookiePath = '/', bool $secure = false): void
{
    // This server-to-server endpoint authenticates its own secret before DB access.
    // Match the executed file, never a client-controlled URL or query parameter.
    $entry = realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '';
    if ($entry === realpath(dirname(__DIR__) . '/webhooks/mailjet_webhook.php')) {
        return;
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $cookiePath,
            'secure' => $secure,
            'httponly' => true,
            // Lax permits the signed top-level GET return from the payment gateway.
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    // Stable across forms/tabs; rotate explicitly when authentication succeeds.
    csrf_token();
    if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD', 'OPTIONS'], true)) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? null;
        if (!csrf_valid_token($token)) {
            csrf_reject();
        }
    }
}
