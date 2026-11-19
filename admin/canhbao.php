<?php
require_once dirname(__DIR__) . '/config.php';
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
include './connect_db.php';

// Xử lý đăng nhập
if (isset($_POST['dangnhap'])) {
    $taikhoan = trim((string)($_POST['taikhoan'] ?? ''));
    $matkhau = (string)($_POST['matkhau'] ?? '');

    if ($taikhoan === '' || $matkhau === '') {
        $thongbao = 'empty';
    } elseif (($lock = auth_throttle_lock_seconds($con, 'admin', $taikhoan)) > 0) {
        // Chống brute-force tài khoản quản trị
        $thongbao = 'locked';
        $lockMessage = auth_lock_message($lock);
    } else {
        // Prepared statement chống SQL injection
        $stmt = mysqli_prepare($con, "SELECT * FROM tbl_qlthanhvien WHERE taikhoan = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $taikhoan);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row_dangnhap = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        [$ok, $newHash] = auth_verify_password($matkhau, $row_dangnhap['matkhau'] ?? null);

        if ($ok) {
            if ($newHash !== null) {
                // Hash cũ / yếu / envelope migration -> nâng cấp lên Argon2id
                app_upgrade_password_hash($con, 'tbl_qlthanhvien', 'id', (int)$row_dangnhap['id'], 'matkhau', $matkhau);
            }
            auth_throttle_clear($con, 'admin', $taikhoan);
            // Chống Session Fixation: cấp session ID mới + ghi mốc vòng đời phiên
            auth_login([
                'dangnhap1' => $row_dangnhap['hoten'],
                'manv'      => (int)$row_dangnhap['id'],
                'role'      => 'admin',
            ]);
            app_redirect(BASE_URL . 'admin/trangchu.php');
            ob_end_flush();
            exit;
        }

        auth_throttle_record_failure($con, 'admin', $taikhoan);
        $thongbao = 'wrong';
    }
} elseif (!empty($_SESSION['swal_warning'])) {
    // Phiên vừa bị hủy do hết hạn / nghi bị chiếm phiên
    $thongbao = 'expired';
    $expiredMessage = $_SESSION['swal_warning'];
    unset($_SESSION['swal_warning']);
} else {
    // Chỉ hiện cảnh báo khi user truy cập trực tiếp mà chưa đăng nhập
    $thongbao = 'require_login';
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Trang đăng nhập</title>
    <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">
    <script nonce="<?= e(csp_nonce()) ?>" src="https://kit.fontawesome.com/a81368914c.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
</head>
<style>
    .i {
        color: #d9d9d9;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .i i {
        transition: .3s;
    }

    .input-div > div {
        position: relative;
        height: 45px;
    }

    .input-div > div > h5 {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
        font-size: 18px;
        transition: .3s;
    }

    .input-div:before,
    .input-div:after {
        content: '';
        position: absolute;
        bottom: -2px;
        width: 0%;
        height: 2px;
        background-color: #38d39f;
        transition: .4s;
    }

    .input-div:before {
        right: 50%;
    }

    .input-div:after {
        left: 50%;
    }

    .input-div.focus:before,
    .input-div.focus:after {
        width: 50%;
    }

    .input-div.focus > div > h5 {
        top: -5px;
        font-size: 15px;
    }

    .input-div.focus > .i > i {
        color: #38d39f;
    }

    /* ===== Toast Notification ===== */
    .toast-container {
        position: fixed;
        top: 24px;
        right: 24px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .toast {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 20px;
        border-radius: 12px;
        min-width: 320px;
        max-width: 420px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        animation: toastIn 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        position: relative;
        overflow: hidden;
        font-family: system-ui, -apple-system, sans-serif;
    }

    .toast.toast-hide {
        animation: toastOut 0.4s ease forwards;
    }

    @keyframes toastIn {
        from { opacity: 0; transform: translateX(100%); }
        to { opacity: 1; transform: translateX(0); }
    }

    @keyframes toastOut {
        from { opacity: 1; transform: translateX(0); }
        to { opacity: 0; transform: translateX(100%); }
    }

    .toast::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        height: 3px;
        background: rgba(255, 255, 255, 0.4);
        animation: timer 4s linear forwards;
    }

    @keyframes timer {
        from { width: 100%; }
        to { width: 0%; }
    }

    .toast-warning {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: #fff;
    }

    .toast-error {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: #fff;
    }

    .toast-icon {
        width: 32px;
        height: 32px;
        min-width: 32px;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }

    .toast-content { flex: 1; }

    .toast-title {
        font-weight: 700;
        font-size: 13px;
        margin-bottom: 2px;
    }

    .toast-message {
        font-size: 12px;
        opacity: 0.9;
    }

    .toast-close {
        background: rgba(255, 255, 255, 0.15);
        border: none;
        color: #fff;
        width: 26px;
        height: 26px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 12px;
        transition: background 0.2s;
    }

    .toast-close:hover {
        background: rgba(255, 255, 255, 0.3);
    }
</style>

<body class="bg-gray-300" style="font-family: Roboto;">

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<div class="h-screen flex justify-center items-center">
    <div class="bg-white rounded-lg w-2/5 px-16 py-16">
        <form method="post">
            <?= csrf_field() ?>
            <div class="flex font-bold justify-center">
                <img class="h-20 w-20"
                     src="https://raw.githubusercontent.com/sefyudem/Responsive-Login-Form/master/img/avatar.svg">
            </div>
            <h2 class="text-3xl text-center text-gray-700 mb-4">Đăng nhập</h2>
            <div class="input-div border-b-2 relative grid my-5 py-1 focus:outline-none"
                 style="grid-template-columns: 7% 93%;">
                <div class="i">
                    <i class="fas fa-user"></i>
                </div>
                <div class="div">
                    <h5>Tài khoản</h5>
                    <input type="text" name="taikhoan" class="absolute w-full h-full py-2 px-3 outline-none inset-0 text-gray-700"
                           style="background:none;">
                </div>
            </div>
            <div class="input-div border-b-2 relative grid my-5 py-1 focus:outline-none"
                 style="grid-template-columns: 7% 93%;">
                <div class="i">
                    <i class="fas fa-lock"></i>
                </div>
                <div class="div">
                    <h5>Mật khẩu</h5>
                    <input name="matkhau" type="password"
                           class="absolute w-full h-full py-2 px-3 outline-none inset-0 text-gray-700"
                           style="background:none;">
                </div>
            </div>
            <button type="submit" name="dangnhap"
                    class="w-full py-2 rounded-full bg-green-600 text-gray-100  focus:outline-none">Xác nhận</button>
        </form>
    </div>
</div>

<script nonce="<?= e(csp_nonce()) ?>">
    // ===== Toast System =====
    function showToast(type, title, message) {
        const container = document.getElementById('toastContainer');

        const toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        const content = document.createElement('div');
        content.className = 'toast-content';
        const heading = document.createElement('div');
        heading.className = 'toast-title';
        heading.textContent = title;
        const description = document.createElement('div');
        description.className = 'toast-message';
        description.textContent = message;
        content.append(heading, description);
        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'toast-close';
        close.textContent = '\u00d7';
        close.addEventListener('click', function () { dismissToast(close); });
        toast.append(content, close);

        container.appendChild(toast);
        setTimeout(function() { dismissToast(toast.querySelector('.toast-close')); }, 4000);
    }

    function dismissToast(btn) {
        var toast = btn.closest('.toast');
        if (!toast || toast.classList.contains('toast-hide')) return;
        toast.classList.add('toast-hide');
        setTimeout(function() { toast.remove(); }, 400);
    }

    // ===== Input Focus =====
    const inputs = document.querySelectorAll("input");

    function addcl() {
        let parent = this.parentNode.parentNode;
        parent.classList.add("focus");
    }

    function remcl() {
        let parent = this.parentNode.parentNode;
        if (this.value == "") {
            parent.classList.remove("focus");
        }
    }

    inputs.forEach(input => {
        input.addEventListener("focus", addcl);
        input.addEventListener("blur", remcl);
    });

    // ===== Hiện thông báo =====
    <?php if (isset($thongbao)): ?>
        <?php if ($thongbao === 'require_login'): ?>
            showToast('warning', 'Yêu cầu đăng nhập', 'Bạn phải đăng nhập trước khi vào trang quản trị!');
        <?php elseif ($thongbao === 'empty'): ?>
            showToast('error', 'Thiếu thông tin', 'Vui lòng nhập đầy đủ tài khoản và mật khẩu!');
        <?php elseif ($thongbao === 'wrong'): ?>
            showToast('error', 'Đăng nhập thất bại', 'Sai tài khoản hoặc mật khẩu. Vui lòng thử lại!');
        <?php elseif ($thongbao === 'locked'): ?>
            showToast('error', 'Tạm khóa đăng nhập', <?= json_encode($lockMessage ?? '', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>);
        <?php elseif ($thongbao === 'expired'): ?>
            showToast('warning', 'Hết phiên đăng nhập', <?= json_encode($expiredMessage ?? '', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>);
        <?php endif; ?>
    <?php endif; ?>
</script>
</body>

</html>
<?php ob_end_flush(); ?>