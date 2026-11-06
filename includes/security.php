<?php
/** Shared runtime hardening & security defense module. Loaded before sessions, output and database access. */

if (!defined('SECURITY_MODULE_LOADED')) {
    define('SECURITY_MODULE_LOADED', true);

function app_is_production(): bool
{
    return in_array(getenv('APP_ENV') ?: 'local', ['production', 'staging'], true);
}

function app_json_request(): bool
{
    $script = basename($_SERVER['SCRIPT_FILENAME'] ?? '');
    return strpos($script, 'api_') === 0
        || in_array($script, ['cart_update_ajax.php', 'gallery_delete.php', 'mailjet_webhook.php'], true)
        || stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
        || stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false
        || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}

function app_fail(int $status, string $message = 'Hệ thống tạm thời không khả dụng. Vui lòng thử lại sau.'): void
{
    // Discard partially rendered pages so exception output cannot leak HTML/data.
    $level = defined('APP_OUTPUT_LEVEL') ? APP_OUTPUT_LEVEL : ob_get_level();
    while (ob_get_level() > $level) {
        ob_end_clean();
    }
    http_response_code($status);
    header('Cache-Control: no-store');
    header_remove('X-Powered-By');
    if (app_json_request()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'REQUEST_FAILED', 'message' => $message], JSON_UNESCAPED_UNICODE);
    } else {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="vi"><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>Không thể xử lý yêu cầu</title><body><h1>Không thể xử lý yêu cầu</h1><p>'
            . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></body></html>';
    }
    exit;
}

function app_ip_in_range(string $address, string $range): bool
{
    $parts = explode('/', trim($range), 2);
    $ip = @inet_pton($address);
    $network = @inet_pton($parts[0]);
    if ($ip === false || $network === false || strlen($ip) !== strlen($network)) {
        return false;
    }
    $maxBits = strlen($ip) * 8;
    if (isset($parts[1]) && (!ctype_digit($parts[1]) || (int)$parts[1] > $maxBits)) {
        return false;
    }
    $bits = isset($parts[1]) ? (int)$parts[1] : $maxBits;
    $bytes = intdiv($bits, 8);
    if (substr($ip, 0, $bytes) !== substr($network, 0, $bytes)) {
        return false;
    }
    $remainder = $bits % 8;
    return $remainder === 0
        || ((ord($ip[$bytes]) ^ ord($network[$bytes])) & (0xff << (8 - $remainder))) === 0;
}

function app_trusted_proxy(): bool
{
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    foreach (explode(',', getenv('TRUSTED_PROXIES') ?: '') as $range) {
        if ($range !== '' && app_ip_in_range($remote, trim($range))) {
            return true;
        }
    }
    return false;
}

function app_is_https(): bool
{
    // A trusted TLS terminator describes the ORIGINAL client connection. Its
    // explicit "http" must not be overridden by TLS on the proxy-to-app hop.
    if (app_trusted_proxy()) {
        return ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }
    return in_array(strtolower((string)($_SERVER['HTTPS'] ?? '')), ['on', '1'], true);
}

/** Production cannot opt out. Local HTTPS demos opt in explicitly. */
function app_requires_https(): bool
{
    $setting = getenv('APP_REQUIRE_HTTPS');
    $enabled = $setting === false ? false : filter_var($setting, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($enabled === null) {
        app_fail(503);
    }
    return app_is_production() || $enabled;
}

function app_validate_transport_configuration(): void
{
    app_requires_https(); // Validate even when production already requires TLS.
    foreach (explode(',', getenv('TRUSTED_PROXIES') ?: '') as $range) {
        $range = trim($range);
        if ($range === '') {
            continue;
        }
        $parts = explode('/', $range, 2);
        if (!app_ip_in_range($parts[0], $range) || (isset($parts[1]) && (int)$parts[1] === 0)) {
            // Trusting every client allows forged HTTPS headers; fail closed.
            app_fail(503);
        }
    }
    if (app_trusted_proxy()
        && !in_array($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '', ['http', 'https'], true)) {
        // The last trusted proxy must overwrite, not append client-supplied values.
        app_fail(400, 'Thông tin kết nối không hợp lệ.');
    }
}

/** Return a normalized host without a port; reject malformed Host headers. */
function app_hostname(string $authority): ?string
{
    if (!preg_match('/\A(\[[0-9a-f:.]+\]|[a-z0-9.-]+)(?::([0-9]{1,5}))?\z/iD', $authority, $match)) {
        return null;
    }
    if (isset($match[2]) && ((int)$match[2] < 1 || (int)$match[2] > 65535)) {
        return null;
    }
    $host = strtolower($match[1]);
    if ($host[0] === '[') {
        return filter_var(trim($host, '[]'), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? $host : null;
    }
    return filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) ? $host : null;
}

function app_security_headers(): void
{
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Cache-Control: no-store');
    // Nonces authorize scripts from trusted source templates, never arbitrary rendered HTML.
    $nonce = csp_nonce();
    header("Content-Security-Policy: default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; "
        . "form-action 'self' https://sandbox.vnpayment.vn https://pay.vnpay.vn; "
        . "script-src 'self' 'nonce-$nonce' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://kit.fontawesome.com; script-src-attr 'none'; "
        . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://unpkg.com https://use.fontawesome.com https://ka-f.fontawesome.com https://afeld.github.io; "
        . "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com https://use.fontawesome.com https://ka-f.fontawesome.com; "
        . "img-src 'self' data: blob: https:; connect-src 'self' https://provinces.open-api.vn https://kit.fontawesome.com https://ka-f.fontawesome.com; "
        . "frame-src https://www.google.com https://maps.google.com; media-src 'self' blob:"
        . (app_is_https() ? '; upgrade-insecure-requests' : ''));
    if (app_is_production() && app_is_https()) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

/** Validate deployment config and return the canonical base URL, if configured. */
function app_security_bootstrap(): ?string
{
    $environment = getenv('APP_ENV') ?: 'local';
    if (!in_array($environment, ['local', 'test', 'staging', 'production'], true)) {
        app_fail(503);
    }
    $debug = !app_is_production() && filter_var(getenv('APP_DEBUG') ?: '0', FILTER_VALIDATE_BOOLEAN);
    ini_set('display_errors', $debug ? '1' : '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    ini_set('zend.exception_ignore_args', '1');
    error_reporting(E_ALL);
    define('APP_OUTPUT_LEVEL', ob_get_level());
    ob_start();
    set_exception_handler(function (Throwable $exception): void {
        error_log(sprintf('APP_EXCEPTION type=%s file=%s line=%d', get_class($exception), basename($exception->getFile()), $exception->getLine()));
        app_fail(500);
    });
    register_shutdown_function(function (): void {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            error_log(sprintf('APP_FATAL file=%s line=%d', basename($error['file']), $error['line']));
            app_fail(500);
        }
    });
    app_validate_transport_configuration();
    app_security_headers();

    if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD', 'POST', 'OPTIONS'], true)) {
        header('Allow: GET, HEAD, POST, OPTIONS');
        app_fail(405, 'Phương thức yêu cầu không được hỗ trợ.');
    }

    $url = getenv('APP_URL');
    $canonical = null;
    $urlHost = null;
    if ($url !== false && $url !== '') {
        $parsed = parse_url($url);
        if ($parsed === false || !isset($parsed['scheme'], $parsed['host'])
            || !in_array($parsed['scheme'], ['http', 'https'], true)
            || isset($parsed['user']) || isset($parsed['pass']) || isset($parsed['query']) || isset($parsed['fragment'])
            || !app_redirect_path_valid($parsed['path'] ?? '/')
            || preg_match('/[\x00-\x20\x7f\\\\]/', $url)
            || ($urlHost = app_hostname($parsed['host'] . (isset($parsed['port']) ? ':' . $parsed['port'] : ''))) === null
            || ((app_requires_https() || app_is_https()) && $parsed['scheme'] !== 'https')) {
            app_fail(503);
        }
        $canonical = rtrim($url, '/') . '/';
    } elseif (app_requires_https()) {
        app_fail(503);
    }

    $authority = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host = app_hostname($authority);
    $allowed = array_filter(array_map('trim', explode(',', getenv('APP_TRUSTED_HOSTS') ?: '')));
    if ($urlHost !== null) {
        $allowed[] = $urlHost;
    }
    if (!app_is_production()) {
        $allowed = array_merge($allowed, ['localhost', '127.0.0.1', '[::1]']);
    }
    $allowed = array_map('strtolower', $allowed);
    if ($host === null || !in_array($host, $allowed, true)) {
        app_fail(400, 'Địa chỉ truy cập không hợp lệ.');
    }

    $entry = realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '';
    $project = dirname(__DIR__) . DIRECTORY_SEPARATOR;
    $relative = str_replace('\\', '/', substr($entry, strlen($project)));
    if (strpos($entry, $project) === 0 && (
        preg_match('~^(?:includes|database|documents|scripts|scratch|deploy|vendor|lib|\.git|\.codex|\.agents)(?:/|$)~i', $relative)
        || in_array($relative, ['config.php', 'admin/connect_db.php', 'admin/function.php', 'admin/pagination.php', 'vnpay_php/config.php'], true)
        || preg_match('~^(?:auth|admin)/uploads/~i', $relative)
    )) {
        app_fail(404, 'Không tìm thấy trang.');
    }

    if (app_requires_https() && !app_is_https()) {
        if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
            app_fail(400, 'Vui lòng sử dụng kết nối HTTPS.');
        }
        app_https_redirect($canonical, $_SERVER['REQUEST_URI'] ?? '/');
    }
    return $canonical;
}

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * SESSION & AUTHORIZATION / IDOR DEFENSE HELPERS
 * ─────────────────────────────────────────────────────────────────────────────
 */

function init_secure_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function secure_session_regenerate(bool $forceRegenerate = true): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        if (!headers_sent()) {
            session_regenerate_id($forceRegenerate);
        }
        if (function_exists('csrf_rotate_token')) {
            csrf_rotate_token();
        } else {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
}

function secure_logout(?string $redirectUrl = null): void
{
    init_secure_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies') && !headers_sent()) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    if (!empty($redirectUrl)) {
        if (!headers_sent()) {
            app_redirect($redirectUrl);
        }
        exit;
    }
}

function get_csrf_token(): string
{
    if (function_exists('csrf_token')) {
        return csrf_token();
    }
    init_secure_session();
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_input(): string
{
    if (function_exists('csrf_field')) {
        return csrf_field();
    }
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_meta(): string
{
    return '<meta name="csrf-token" content="'
        . htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function validate_csrf_token(?string $providedToken = null, ?string $rawBody = null): bool
{
    if (function_exists('csrf_valid_token') && $providedToken !== null) {
        return csrf_valid_token($providedToken);
    }
    init_secure_session();
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        return false;
    }
    if ($providedToken === null) {
        if (!empty($_POST['csrf_token']) && is_string($_POST['csrf_token'])) {
            $providedToken = $_POST['csrf_token'];
        } elseif (!empty($_SERVER['HTTP_X_CSRF_TOKEN']) && is_string($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $providedToken = $_SERVER['HTTP_X_CSRF_TOKEN'];
        } else {
            $rawInput = ($rawBody !== null) ? $rawBody : file_get_contents('php://input');
            if (!empty($rawInput)) {
                $json = json_decode($rawInput, true);
                if (is_array($json) && !empty($json['csrf_token']) && is_string($json['csrf_token'])) {
                    $providedToken = $json['csrf_token'];
                }
            }
        }
    }
    if (empty($providedToken) || !is_string($providedToken)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $providedToken);
}

function verify_csrf_or_abort(): void
{
    if (function_exists('csrf_require_post')) {
        csrf_require_post();
    }
    if (!validate_csrf_token()) {
        if (function_exists('csrf_reject')) {
            csrf_reject(403);
        } else {
            http_response_code(403);
            exit('CSRF token không hợp lệ.');
        }
    }
}

function is_user_logged_in(): bool
{
    init_secure_session();
    return !empty($_SESSION['dangnhap']) && !empty($_SESSION['makh']);
}

function get_current_user_id(): int
{
    init_secure_session();
    return isset($_SESSION['makh']) ? intval($_SESSION['makh']) : 0;
}

function require_user(?string $redirectUrl = null): void
{
    if (!is_user_logged_in()) {
        if (app_json_request()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thực hiện hành động này!'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($redirectUrl === null) {
            $base = defined('BASE_URL') ? BASE_URL : '/WEB-PHP/';
            $redirectUrl = $base . 'auth/dangnhap.php';
        }
        app_redirect($redirectUrl);
        exit;
    }
}

function is_admin_logged_in(): bool
{
    init_secure_session();
    return !empty($_SESSION['dangnhap1']);
}

function get_current_admin_id(): int
{
    init_secure_session();
    return isset($_SESSION['manv']) ? intval($_SESSION['manv']) : 0;
}

function require_admin(?string $redirectUrl = null): void
{
    if (!is_admin_logged_in()) {
        if (app_json_request()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền quản trị!'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($redirectUrl === null) {
            $base = defined('BASE_URL') ? BASE_URL : '/WEB-PHP/';
            $redirectUrl = $base . 'admin/canhbao.php';
        }
        app_redirect($redirectUrl);
        exit;
    }
}

}
