<?php
/**
 * admin_guard.php — Kiểm tra quyền truy cập (Authorization) cho khu vực /admin/
 *
 * Cách dùng: đặt ở dòng đầu tiên của mỗi trang quản trị (trước mọi output):
 *     require_once __DIR__ . '/admin_guard.php';
 *
 * Quy tắc:
 *  - Chưa đăng nhập (hoặc session cũ chưa có role) -> chuyển hướng về trang đăng nhập admin
 *    (hoặc trả JSON 401 nếu là request AJAX).
 *  - Đã đăng nhập nhưng role khác 'admin'          -> 403 Forbidden.
 *
 * Session được thiết lập khi đăng nhập admin thành công (canhbao.php / dangnhap.php):
 *     $_SESSION['dangnhap1'] (họ tên), $_SESSION['manv'] (id), $_SESSION['role'] = 'admin'
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('ADMIN_LOGIN_PAGE')) {
    define('ADMIN_LOGIN_PAGE', 'canhbao.php');
}

/** Request có phải AJAX / yêu cầu JSON không? */
function admin_is_ajax_request()
{
    $xhr    = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
              && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $accept = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
    return $xhr || $accept;
}

/** Đã đăng nhập admin (có session hợp lệ và có role)? */
function admin_is_logged_in()
{
    return !empty($_SESSION['dangnhap1'])
        && !empty($_SESSION['manv'])
        && isset($_SESSION['role']);
}

/** Đúng vai trò admin? */
function admin_has_admin_role()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/** Chưa đăng nhập: redirect về trang đăng nhập (hoặc 401 JSON cho AJAX). */
function admin_redirect_to_login()
{
    if (admin_is_ajax_request()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập.']);
        exit;
    }
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    header('Location: ' . $dir . '/' . ADMIN_LOGIN_PAGE);
    exit;
}

/** Sai vai trò: trả 403 Forbidden. */
function admin_forbidden()
{
    http_response_code(403);
    if (admin_is_ajax_request()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => '403 Forbidden - Bạn không có quyền truy cập.']);
        exit;
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>403 Forbidden</title>'
       . '<style>body{font-family:Arial,sans-serif;background:#f3f4f6;display:flex;align-items:center;'
       . 'justify-content:center;height:100vh;margin:0}.box{background:#fff;padding:40px 56px;'
       . 'border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.08);text-align:center}'
       . 'h1{font-size:64px;margin:0;color:#dc2626}p{color:#4b5563}a{color:#16a34a}</style></head><body>'
       . '<div class="box"><h1>403</h1><p>Forbidden — Bạn không có quyền truy cập trang quản trị.</p>'
       . '<a href="' . htmlspecialchars(ADMIN_LOGIN_PAGE) . '">Đăng nhập tài khoản admin</a></div></body></html>';
    exit;
}

/** Hàm kiểm tra chính. */
function admin_require_auth()
{
    // Không cho trình duyệt cache các trang quản trị (tránh xem lại bằng nút Back sau khi đăng xuất)
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    if (!admin_is_logged_in()) {
        admin_redirect_to_login();
    }
    if (!admin_has_admin_role()) {
        admin_forbidden();
    }
}

admin_require_auth();
