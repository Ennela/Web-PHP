<?php
/**
 * auth.php — Chống "Broken Authentication and Session Management".
 *
 * Gồm 4 nhóm chức năng dùng chung cho khách hàng (auth/) và quản trị (admin/):
 *   1. Vòng đời phiên: idle timeout, absolute timeout, xoay vòng session ID định kỳ,
 *      ràng buộc phiên với trình duyệt (chống session hijacking).
 *   2. Đăng nhập / đăng xuất an toàn: regenerate ID, xóa sạch session + cookie.
 *   3. Kiểm tra mật khẩu: bcrypt, tự nâng cấp mật khẩu cũ, không chấp nhận "đăng nhập bằng hash".
 *   4. Chống brute-force: khóa tạm thời theo tài khoản và theo IP (lưu trong DB).
 *
 * Được nạp tự động trong config.php — mọi trang đều được bảo vệ.
 */

// ── Cấu hình vòng đời phiên ─────────────────────────────────────────────────
const AUTH_IDLE_TIMEOUT      = 1800;   // 30 phút không thao tác -> hết phiên
const AUTH_ABSOLUTE_TIMEOUT  = 28800;  // 8 giờ kể từ lúc đăng nhập -> bắt đăng nhập lại
const AUTH_ROTATE_INTERVAL   = 900;    // 15 phút đổi session ID một lần

// ── Cấu hình chống brute-force ──────────────────────────────────────────────
const AUTH_MAX_FAILS_PER_ACCOUNT = 5;    // 5 lần sai / tài khoản
const AUTH_MAX_FAILS_PER_IP      = 20;   // 20 lần sai / IP (chống dò nhiều tài khoản)
const AUTH_FAIL_WINDOW           = 900;  // trong 15 phút
const AUTH_LOCK_SECONDS          = 900;  // khóa 15 phút

const AUTH_SESSION_KEYS = ['dangnhap', 'makh', 'dangnhap1', 'manv', 'role'];

/* =========================================================================
 * 1. VÒNG ĐỜI PHIÊN
 * ========================================================================= */

/** Phiên hiện tại có đang đăng nhập (khách hàng hoặc admin) không? */
function auth_is_authenticated(): bool
{
    return !empty($_SESSION['makh']) || !empty($_SESSION['manv']);
}

/** Dấu vân tay trình duyệt — phiên bị đánh cắp sang trình duyệt khác sẽ bị hủy. */
function auth_fingerprint(): string
{
    return hash('sha256', (string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
}

/**
 * Kiểm tra vòng đời phiên ở MỌI request (gọi trong config.php).
 * Hết hạn / nghi bị chiếm phiên -> hủy phiên, các trang tự chuyển về trang đăng nhập.
 */
function auth_session_enforce(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE || !auth_is_authenticated()) {
        return;
    }

    $now = time();

    // Phiên đăng nhập từ trước khi có bản vá -> khởi tạo mốc thời gian
    if (empty($_SESSION['auth_created_at'])) {
        $_SESSION['auth_created_at']  = $now;
        $_SESSION['auth_last_active'] = $now;
        $_SESSION['auth_rotated_at']  = $now;
        $_SESSION['auth_fingerprint'] = auth_fingerprint();
    }

    $reason = null;
    if (!hash_equals((string)($_SESSION['auth_fingerprint'] ?? ''), auth_fingerprint())) {
        $reason = 'Phiên đăng nhập không hợp lệ. Vui lòng đăng nhập lại.';
    } elseif ($now - (int)$_SESSION['auth_last_active'] > AUTH_IDLE_TIMEOUT) {
        $reason = 'Phiên đăng nhập đã hết hạn do không hoạt động. Vui lòng đăng nhập lại.';
    } elseif ($now - (int)$_SESSION['auth_created_at'] > AUTH_ABSOLUTE_TIMEOUT) {
        $reason = 'Phiên đăng nhập đã quá thời gian cho phép. Vui lòng đăng nhập lại.';
    }

    if ($reason !== null) {
        $cart = $_SESSION['giohang'] ?? null; // giữ giỏ hàng, chỉ bỏ thông tin đăng nhập
        auth_restart_session();
        if ($cart !== null) {
            $_SESSION['giohang'] = $cart;
        }
        $_SESSION['swal_warning'] = $reason;
        return;
    }

    $_SESSION['auth_last_active'] = $now;

    // Xoay vòng session ID định kỳ: ID bị lộ chỉ dùng được trong thời gian ngắn
    if ($now - (int)($_SESSION['auth_rotated_at'] ?? 0) > AUTH_ROTATE_INTERVAL && !headers_sent()) {
        session_regenerate_id(true);
        $_SESSION['auth_rotated_at'] = $now;
    }
}

/* =========================================================================
 * 2. ĐĂNG NHẬP / ĐĂNG XUẤT
 * ========================================================================= */

/**
 * Thiết lập phiên sau khi xác thực thành công.
 * Chống Session Fixation: luôn cấp session ID mới + CSRF token mới.
 */
function auth_login(array $identity): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    session_regenerate_id(true);
    if (function_exists('csrf_rotate_token')) {
        csrf_rotate_token();
    }

    foreach ($identity as $key => $value) {
        $_SESSION[$key] = $value;
    }

    $now = time();
    $_SESSION['auth_created_at']  = $now;
    $_SESSION['auth_last_active'] = $now;
    $_SESSION['auth_rotated_at']  = $now;
    $_SESSION['auth_fingerprint'] = auth_fingerprint();
}

/** Hủy phiên hoàn toàn: xóa dữ liệu, xóa cookie phía trình duyệt, hủy file phiên phía server. */
function auth_destroy_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies') && !headers_sent()) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}

/**
 * Hủy phiên cũ rồi mở phiên MỚI với session ID mới hoàn toàn
 * (dùng khi cần hiển thị thông báo sau khi hủy phiên).
 */
function auth_restart_session(): void
{
    auth_destroy_session();
    session_id(session_create_id());
    session_start();
    if (function_exists('csrf_token')) {
        csrf_token();
    }
}

/**
 * Xử lý nút "Đăng xuất" (POST login=dangxuat, đã qua kiểm tra CSRF) cho toàn site.
 * Gọi trong config.php nên mọi trang đều đăng xuất đúng cách.
 */
function auth_handle_logout_request(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || ($_POST['login'] ?? '') !== 'dangxuat') {
        return;
    }
    $script  = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $isAdmin = strpos($script, '/admin/') !== false;

    auth_destroy_session();
    header('Location: ' . BASE_URL . ($isAdmin ? 'admin/canhbao.php' : 'auth/dangnhap.php'), true, 303);
    exit;
}

/* =========================================================================
 * 3. MẬT KHẨU
 * ========================================================================= */

/**
 * So khớp mật khẩu. Trả về [bool $ok, ?string $newHash].
 * - Mật khẩu đã hash: password_verify (an toàn trước timing attack).
 * - Mật khẩu cũ còn plaintext: chỉ so sánh khi giá trị lưu KHÔNG phải hash,
 *   tránh việc kẻ tấn công lấy được hash rồi gõ thẳng hash để đăng nhập.
 * - $newHash != null nghĩa là cần cập nhật lại DB (nâng cấp plaintext / rehash).
 */
function auth_verify_password(string $input, ?string $stored): array
{
    $stored = (string)$stored;
    if ($stored === '' || $input === '') {
        password_verify($input, '$2y$10$rSAL88pX94NIAc9sEuJ/1OPhYQLjCLfLfZNjI3SloCyYD0wLHPYc.'); // cân bằng thời gian
        return [false, null];
    }

    // Ủy quyền cho lớp mật khẩu thống nhất (Argon2id, bcrypt, envelope prehashed:* của migration, dữ liệu cũ).
    $needsRehash = false;
    if (!app_verify_password($input, $stored, $needsRehash)) {
        return [false, null];
    }
    return [true, $needsRehash ? app_hash_password($input) : null];
}
/** Chính sách mật khẩu: >= 8 ký tự, có chữ và số, tối đa 72 byte (giới hạn bcrypt). */
function auth_password_policy_errors(string $password): array
{
    $errors = [];
    if (strlen($password) < 8) {
        $errors[] = 'Mật khẩu phải có ít nhất 8 ký tự';
    }
    if (strlen($password) > 72) {
        $errors[] = 'Mật khẩu không được dài quá 72 ký tự';
    }
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors[] = 'Mật khẩu phải chứa cả chữ cái và chữ số';
    }
    return $errors;
}

/* =========================================================================
 * 4. CHỐNG BRUTE-FORCE (lưu DB — xóa cookie cũng không vượt qua được)
 * ========================================================================= */

function auth_client_ip(): string
{
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    // Sau Cloudflare Tunnel/proxy tin cậy, REMOTE_ADDR là proxy: lấy IP khách thật từ CF-Connecting-IP.
    if (function_exists('app_trusted_proxy') && app_trusted_proxy()) {
        $cf = trim((string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));
        if ($cf !== '' && filter_var($cf, FILTER_VALIDATE_IP)) {
            $ip = $cf;
        }
    }
    return substr($ip, 0, 45);
}

function auth_throttle_ensure_table(mysqli $con): void
{
    static $done = false;
    if ($done) {
        return;
    }
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS `tbl_login_attempts` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `scope` VARCHAR(20) NOT NULL,
        `identifier` VARCHAR(191) NOT NULL,
        `ip` VARCHAR(45) NOT NULL,
        `attempted_at` INT UNSIGNED NOT NULL,
        KEY `idx_scope_identifier` (`scope`, `identifier`, `attempted_at`),
        KEY `idx_scope_ip` (`scope`, `ip`, `attempted_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $done = true;
}

/**
 * Số giây còn bị khóa (0 = được phép thử).
 * $scope: 'customer' | 'admin' | 'reset'
 */
function auth_throttle_lock_seconds(mysqli $con, string $scope, string $identifier, ?int $maxPerAccount = null): int
{
    auth_throttle_ensure_table($con);
    $identifier = mb_strtolower(trim($identifier));
    $ip         = auth_client_ip();
    $since      = time() - AUTH_FAIL_WINDOW;
    $maxAccount = $maxPerAccount ?? AUTH_MAX_FAILS_PER_ACCOUNT;

    // Dọn bản ghi cũ
    $old = time() - max(AUTH_FAIL_WINDOW, AUTH_LOCK_SECONDS) * 2;
    $stmt = mysqli_prepare($con, 'DELETE FROM `tbl_login_attempts` WHERE `attempted_at` < ?');
    mysqli_stmt_bind_param($stmt, 'i', $old);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $checks = [
        ['identifier', $identifier, $maxAccount],
        ['ip', $ip, AUTH_MAX_FAILS_PER_IP],
    ];
    $lock = 0;
    foreach ($checks as [$column, $value, $limit]) {
        $stmt = mysqli_prepare($con, "SELECT COUNT(*), MAX(`attempted_at`) FROM `tbl_login_attempts`
                                      WHERE `scope` = ? AND `$column` = ? AND `attempted_at` >= ?");
        mysqli_stmt_bind_param($stmt, 'ssi', $scope, $value, $since);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $count, $last);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        if ((int)$count >= $limit) {
            $lock = max($lock, (int)$last + AUTH_LOCK_SECONDS - time());
        }
    }
    return max(0, $lock);
}

/** Ghi nhận một lần thất bại. */
function auth_throttle_record_failure(mysqli $con, string $scope, string $identifier): void
{
    auth_throttle_ensure_table($con);
    $identifier = mb_strtolower(trim($identifier));
    $ip  = auth_client_ip();
    $now = time();
    $stmt = mysqli_prepare($con, 'INSERT INTO `tbl_login_attempts` (`scope`, `identifier`, `ip`, `attempted_at`) VALUES (?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'sssi', $scope, $identifier, $ip, $now);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/** Đăng nhập thành công -> xóa lịch sử thất bại của tài khoản đó. */
function auth_throttle_clear(mysqli $con, string $scope, string $identifier): void
{
    auth_throttle_ensure_table($con);
    $identifier = mb_strtolower(trim($identifier));
    $stmt = mysqli_prepare($con, 'DELETE FROM `tbl_login_attempts` WHERE `scope` = ? AND `identifier` = ?');
    mysqli_stmt_bind_param($stmt, 'ss', $scope, $identifier);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/** Thông báo khóa dạng "x phút". */
function auth_lock_message(int $seconds): string
{
    $minutes = max(1, (int)ceil($seconds / 60));
    return "Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau {$minutes} phút.";
}
