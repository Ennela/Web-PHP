<?php
/** Redirect policy. Never use safe_url() as a redirect validator: it allows external links. */

/** Reject ambiguous URL syntax and encoded header/control characters, without rewriting input. */
function app_redirect_syntax_valid($value): bool
{
    if (!is_string($value) || $value === '' || strlen($value) > 8192
        || preg_match('/[^\x21-\x7e]|[\\\\<>"\']/', $value)
        || preg_match('/%(?![a-f0-9]{2})/i', $value)) {
        return false;
    }
    $decoded = $value;
    for ($i = 0; $i < 8; $i++) {
        if (preg_match('/[\x00-\x1f\x7f\\\\]/', $decoded)) {
            return false;
        }
        $next = rawurldecode($decoded);
        if ($next === $decoded) {
            return true;
        }
        $decoded = $next;
    }
    return false;
}

/** Canonical path grammar excludes encoding, traversal, authority and PATH_INFO ambiguity. */
function app_redirect_path_valid(string $path): bool
{
    return preg_match('~\A/(?:[A-Za-z0-9_-]+(?:\.[A-Za-z0-9_-]+)?/)*[A-Za-z0-9_-]*(?:\.[A-Za-z0-9_-]+)?\z~D', $path) === 1;
}

function app_redirect_origin(array $parts): string
{
    $scheme = strtolower($parts['scheme'] ?? '');
    return $scheme . '://' . strtolower($parts['host'] ?? '') . ':'
        . ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
}

/** Only navigable pages, never private files, upload paths or mutation/API endpoints. */
function app_redirect_pages(): array
{
    return [
        '', 'index.php',
        'home/trangchu.php', 'home/vechungtoi.php', 'home/lienhe.php',
        'blog/baiviet.php', 'blog/chitietbaiviet.php',
        'auth/dangnhap.php', 'auth/dangki.php', 'auth/quenmatkhau.php',
        'auth/datlamatkhau.php', 'auth/thongtintaikhoan.php',
        'shop/shop.php', 'shop/chitietsanpham.php', 'shop/giohang.php',
        'shop/infodathang.php', 'shop/dathangonline.php', 'shop/tradonhang.php', 'shop/chitietdonhang.php',
        'admin/', 'admin/index.php', 'admin/canhbao.php', 'admin/dangnhap.php', 'admin/trangchu.php',
        'admin/quanlisanpham.php', 'admin/quanlibaidang.php', 'admin/quanlidanhmuc.php',
        'admin/quanlithanhvien.php', 'admin/quanlikhachhang.php', 'admin/quanlidonhang.php',
        'admin/themsanpham.php', 'admin/thembaidang.php', 'admin/themdanhmuc.php',
        'admin/themthanhvien.php', 'admin/themkhachhang.php',
        'admin/edit_qlsanpham.php', 'admin/edit_qlbaidang.php', 'admin/edit_qlthanhvien.php',
    ];
}

/** Accept an app-root path or exact-origin absolute URL; return a root-relative Location. */
function app_local_redirect_url($target): ?string
{
    if (!app_redirect_syntax_valid($target) || !defined('BASE_URL')) {
        return null;
    }
    $base = parse_url(BASE_URL);
    $parts = parse_url($target);
    if ($parts === false || isset($parts['user']) || isset($parts['pass'])) {
        return null;
    }
    if (isset($parts['scheme']) || isset($parts['host'])) {
        if (!isset($parts['scheme'], $parts['host'])
            || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            || app_redirect_origin($parts) !== app_redirect_origin($base)) {
            return null;
        }
    } elseif ($target[0] !== '/' || str_starts_with($target, '//')) {
        return null;
    }
    $path = $parts['path'] ?? '/';
    $basePath = $base['path'] ?? '/';
    if (!app_redirect_path_valid($path) || !str_starts_with($path, $basePath)
        || !in_array(substr($path, strlen($basePath)), app_redirect_pages(), true)) {
        return null;
    }
    // Only the already-validated path and unchanged query/fragment reach the header.
    return $path . (isset($parts['query']) ? '?' . $parts['query'] : '')
        . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
}

function app_redirect($target, int $status = 302): never
{
    $location = app_local_redirect_url($target);
    if ($location === null) {
        app_fail(400, 'Đích chuyển hướng không hợp lệ.');
    }
    app_send_redirect($location, $status);
}

/** Internal sink: callers must validate against the local or payment policy first. */
function app_send_redirect(string $validatedLocation, int $status): never
{
    if (!in_array($status, [302, 303, 307, 308], true)) {
        throw new InvalidArgumentException('Unsupported redirect status');
    }
    header('Location: ' . $validatedLocation, true, $status);
    exit; // Prevent a forward-like fall-through into protected handlers/output.
}

/** User input selects a fixed customer page, never an admin page or an arbitrary URL. */
function app_return_destination($id): string
{
    $pages = [
        'home' => 'home/trangchu.php', 'shop' => 'shop/shop.php', 'cart' => 'shop/giohang.php',
        'account' => 'auth/thongtintaikhoan.php', 'orders' => 'shop/tradonhang.php',
    ];
    if (!is_string($id) || !isset($pages[$id])) {
        app_fail(400, 'Đích chuyển hướng không hợp lệ.');
    }
    return BASE_URL . $pages[$id];
}

function app_payment_endpoints(): array
{
    return ['https://sandbox.vnpayment.vn/paymentv2/vpcpay.html', 'https://pay.vnpay.vn/vpcpay.html'];
}

/** Payment is the sole external redirect: exact HTTPS endpoint, no fragment/userinfo/alternate port. */
function app_payment_redirect_url($target): ?string
{
    if (!app_redirect_syntax_valid($target) || str_contains($target, '#')) {
        return null;
    }
    $endpoint = explode('?', $target, 2)[0];
    return in_array($endpoint, app_payment_endpoints(), true) ? $target : null;
}

function app_payment_redirect($target): never
{
    $location = app_payment_redirect_url($target);
    if ($location === null) {
        app_fail(400, 'Đích thanh toán không hợp lệ.');
    }
    app_send_redirect($location, 303);
}

/** HTTPS upgrade happens before BASE_URL exists. Pin origin to validated APP_URL. */
function app_https_redirect(string $canonical, $uri): never
{
    if (!app_redirect_syntax_valid($uri) || !str_starts_with($uri, '/')
        || str_starts_with($uri, '//') || str_contains($uri, '#')) {
        app_fail(400, 'Địa chỉ truy cập không hợp lệ.');
    }
    $parts = parse_url($canonical);
    $origin = $parts['scheme'] . '://' . $parts['host']
        . (isset($parts['port']) ? ':' . $parts['port'] : '');
    app_send_redirect($origin . $uri, 308);
}
