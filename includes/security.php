<?php
/**
 * ─────────────────────────────────────────────────────────────────────────────
 * CORE SECURITY MODULE (CSRF, SESSION & AUTHORIZATION / IDOR DEFENSE)
 * Native PHP 8.2 Security Implementation
 * ─────────────────────────────────────────────────────────────────────────────
 */

if (!defined('SECURITY_MODULE_LOADED')) {
    define('SECURITY_MODULE_LOADED', true);

    /**
     * Kiểm tra xem request hiện tại có chạy trên giao thức HTTPS an toàn hay không.
     */
    function is_https_request(): bool {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    }

    /**
     * Khởi tạo PHP Session với các cờ bảo mật nghiêm ngặt.
     * Cấu hình Cookie: HttpOnly, SameSite=Lax, Secure (khi chạy HTTPS).
     */
    function init_secure_session() {
        if (session_status() === PHP_SESSION_NONE) {
            $isHttps = is_https_request();

            if (!headers_sent()) {
                ini_set('session.use_strict_mode', '1');
                ini_set('session.use_only_cookies', '1');

                session_set_cookie_params([
                    'lifetime' => 0,
                    'path'     => '/',
                    'domain'   => '',
                    'secure'   => $isHttps,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            }

            session_start();
        }
    }

    /**
     * Tái tạo Session ID chống Session Fixation khi đăng nhập hoặc thay đổi quyền.
     */
    function secure_session_regenerate($forceRegenerate = true) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (!headers_sent()) {
                session_regenerate_id($forceRegenerate);
            }
            // Làm mới CSRF token gắn với session mới
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    /**
     * Hủy session an toàn khi đăng xuất: xóa cookie, xóa biến session, hủy session.
     */
    function secure_logout($redirectUrl = null) {
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
                header('Location: ' . $redirectUrl);
            }
            exit;
        }
    }

    /**
     * Lấy CSRF token hiện tại, tạo mới nếu chưa tồn tại trong Session.
     * Sử dụng random_bytes(32) chuẩn mật mã học.
     *
     * @return string
     */
    function get_csrf_token() {
        init_secure_session();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Tạo thẻ hidden input chứa CSRF token cho HTML Form.
     *
     * @return string
     */
    function csrf_input() {
        $token = htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /**
     * Tạo thẻ meta tag chứa CSRF token cho JavaScript/AJAX.
     *
     * @return string
     */
    function csrf_meta() {
        $token = htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8');
        return '<meta name="csrf-token" content="' . $token . '">';
    }

    /**
     * Kiểm tra tính hợp lệ của CSRF token bằng hash_equals (chống timing attack).
     *
     * @param string|null $providedToken
     * @param string|null $rawBody
     * @return bool
     */
    function validate_csrf_token($providedToken = null, $rawBody = null) {
        init_secure_session();

        if (empty($_SESSION['csrf_token'])) {
            return false;
        }

        if ($providedToken === null) {
            // 1. Kiểm tra trong $_POST
            if (!empty($_POST['csrf_token'])) {
                $providedToken = $_POST['csrf_token'];
            }
            // 2. Kiểm tra trong HTTP Request Header (X-CSRF-Token)
            elseif (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
                $providedToken = $_SERVER['HTTP_X_CSRF_TOKEN'];
            }
            // 3. Fallback: kiểm tra raw JSON input nếu có
            else {
                $rawInput = ($rawBody !== null) ? $rawBody : file_get_contents('php://input');
                if (!empty($rawInput)) {
                    $json = json_decode($rawInput, true);
                    if (is_array($json) && !empty($json['csrf_token'])) {
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

    /**
     * Kiểm tra Origin / Referer như một lớp bảo vệ bổ sung.
     *
     * @return bool
     */
    function validate_trusted_origin() {
        $currentHost = $_SERVER['HTTP_HOST'] ?? '';
        if (empty($currentHost)) {
            return true; // CLI mode
        }

        // Kiểm tra Origin nếu có
        if (!empty($_SERVER['HTTP_ORIGIN'])) {
            $originHost = parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST);
            $originPort = parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_PORT);
            $originStr = $originHost . ($originPort ? ':' . $originPort : '');
            if (strcasecmp($originStr, $currentHost) !== 0) {
                return false;
            }
        } elseif (!empty($_SERVER['HTTP_REFERER'])) {
            $refererHost = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
            $refererPort = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PORT);
            $refererStr = $refererHost . ($refererPort ? ':' . $refererPort : '');
            if (strcasecmp($refererStr, $currentHost) !== 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Bắt buộc xác minh CSRF token và Origin cho request thay đổi dữ liệu (POST, PUT, DELETE, PATCH).
     * Nếu không hợp lệ, trả về HTTP 403 Forbidden và dừng thực thi.
     */
    function verify_csrf_or_abort() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            // Lớp 1: Kiểm tra Origin / Referer
            if (!validate_trusted_origin()) {
                send_csrf_forbidden_response('Yêu cầu từ Origin không được tin cậy.');
            }

            // Lớp 2: Kiểm tra Synchronizer Token
            if (!validate_csrf_token()) {
                send_csrf_forbidden_response('CSRF token không hợp lệ hoặc phiên làm việc đã hết hạn.');
            }
        }
    }

    /**
     * Trả về phản hồi 403 Forbidden chuẩn cho CSRF.
     *
     * @param string $message
     */
    function send_csrf_forbidden_response($message = 'Truy cập bị từ chối: Lỗi xác thực CSRF token.') {
        http_response_code(403);

        $isJson = (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
            || (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
            || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if ($isJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'csrf_error' => true,
                'message' => $message
            ]);
            exit;
        }

        echo '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>403 Forbidden - Lỗi CSRF</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .box { background: #fff; padding: 32px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); max-width: 480px; text-align: center; border: 1px solid #e2e8f0; }
        h1 { color: #ef4444; font-size: 24px; margin-top: 0; }
        p { color: #64748b; font-size: 15px; line-height: 1.5; }
        a { display: inline-block; margin-top: 16px; padding: 10px 20px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; }
        a:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="box">
        <h1>403 - Lỗi bảo mật CSRF</h1>
        <p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>
        <p>Vui lòng quay lại trang trước, làm mới trình duyệt và thử lại.</p>
        <a href="javascript:window.history.back()">Quay lại</a>
    </div>
</body>
</html>';
        exit;
    }

    /**
     * ─────────────────────────────────────────────────────────────────────────
     * AUTHORIZATION & IDOR DEFENSE HELPERS
     * ─────────────────────────────────────────────────────────────────────────
     */

    /**
     * Kiểm tra khách hàng (USER) đã đăng nhập chưa.
     *
     * @return bool
     */
    function is_user_logged_in() {
        init_secure_session();
        return !empty($_SESSION['dangnhap']) && !empty($_SESSION['makh']);
    }

    /**
     * Lấy ID khách hàng hiện tại từ Session đã xác thực (không tin cậy request body/params).
     *
     * @return int
     */
    function get_current_user_id() {
        init_secure_session();
        return isset($_SESSION['makh']) ? intval($_SESSION['makh']) : 0;
    }

    /**
     * Yêu cầu người dùng (USER) phải đăng nhập.
     *
     * @param string|null $redirectUrl
     */
    function require_user($redirectUrl = null) {
        if (!is_user_logged_in()) {
            $isJson = (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
                || (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
                || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

            if ($isJson) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thực hiện hành động này!']);
                exit;
            }

            if ($redirectUrl === null) {
                $base = defined('BASE_URL') ? BASE_URL : '/WEB-PHP/';
                $redirectUrl = $base . 'auth/dangnhap.php';
            }
            header('Location: ' . $redirectUrl);
            exit;
        }
    }

    /**
     * Kiểm tra quản trị viên (ADMIN) đã đăng nhập chưa.
     *
     * @return bool
     */
    function is_admin_logged_in() {
        init_secure_session();
        return !empty($_SESSION['dangnhap1']);
    }

    /**
     * Lấy ID nhân viên/quản trị viên hiện tại từ Session.
     *
     * @return int
     */
    function get_current_admin_id() {
        init_secure_session();
        return isset($_SESSION['manv']) ? intval($_SESSION['manv']) : 0;
    }

    /**
     * Yêu cầu phải là ADMIN đã đăng nhập.
     * Chặn triệt để truy cập trái phép từ USER thường hoặc khách chưa đăng nhập.
     *
     * @param string|null $redirectUrl
     */
    function require_admin($redirectUrl = null) {
        if (!is_admin_logged_in()) {
            $isJson = (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
                || (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
                || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

            if ($isJson) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền quản trị!']);
                exit;
            }

            if ($redirectUrl === null) {
                $base = defined('BASE_URL') ? BASE_URL : '/WEB-PHP/';
                $redirectUrl = $base . 'admin/canhbao.php';
            }
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
}
