<?php
require_once dirname(__DIR__) . '/config.php';
session_start();
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

include BASE_PATH . 'includes/connect.php';

// ── Validate token từ URL ──
$token = trim($_GET['token'] ?? '');
$validToken = false;
$errorMessage = '';

if (empty($token) || strlen($token) !== 64) {
    $errorMessage = 'Link đặt lại mật khẩu không hợp lệ.';
} else {
    $tokenSafe = mysqli_real_escape_string($con, $token);
    $query = mysqli_query($con, "SELECT `makh`, `hoten` FROM `tbl_tkkhachhang` WHERE `reset_token` = '$tokenSafe' AND `reset_token_expiry` >= " . time() . " LIMIT 1");
    $user = mysqli_fetch_assoc($query);

    if (!$user) {
        $errorMessage = 'Link đặt lại mật khẩu đã hết hạn hoặc đã được sử dụng. Vui lòng yêu cầu lại.';
    } else {
        $validToken = true;
    }
}

include BASE_PATH . 'includes/header.php';
?>

<style>
    .auth-page {
        padding: clamp(100px, 15vw, 140px) 0 clamp(40px, 8vw, 80px);
        background: #f1f5f9;
        min-height: 100vh;
    }
    .auth-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        padding: clamp(24px, 5vw, 40px);
        max-width: 480px;
        margin: 0 auto;
    }
    .auth-card .auth-title {
        font-family: 'Montserrat', sans-serif;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: clamp(1px, 0.3vw, 2px);
        font-size: clamp(1.3rem, 4vw, 1.8rem);
        color: #0f172a;
        margin-bottom: 6px;
        text-align: center;
    }
    .auth-card .auth-subtitle {
        color: #64748b;
        font-size: clamp(0.85rem, 2vw, 0.95rem);
        text-align: center;
        margin-bottom: clamp(24px, 4vw, 32px);
    }
    .auth-card .form-label {
        font-weight: 700;
        font-size: clamp(0.78rem, 1.8vw, 0.85rem);
        color: #1e293b;
        margin-bottom: 8px;
    }
    .auth-card .form-control {
        border-radius: 10px;
        border: 2px solid #e2e8f0;
        padding: 12px 16px;
        font-size: clamp(0.88rem, 2vw, 0.95rem);
        font-weight: 500;
        min-height: 48px;
        transition: border-color 0.3s, box-shadow 0.3s;
    }
    .auth-card .form-control:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    .auth-card .btn-auth {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 14px 24px;
        font-weight: 700;
        font-size: clamp(0.85rem, 2vw, 0.95rem);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        min-height: 48px;
        width: 100%;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    @media (hover: hover) {
        .auth-card .btn-auth:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
        }
    }
    .auth-card .btn-auth:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
    }
    .auth-card .auth-link {
        text-align: center;
        margin-top: 20px;
        font-size: clamp(0.82rem, 1.8vw, 0.88rem);
        color: #64748b;
    }
    .auth-card .auth-link a {
        color: #3b82f6;
        font-weight: 700;
        text-decoration: none;
    }
    @media (hover: hover) {
        .auth-card .auth-link a:hover { text-decoration: underline; }
    }
    .auth-icon-wrap {
        text-align: center;
        margin-bottom: 20px;
    }
    .auth-icon-wrap .auth-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 64px;
        height: 64px;
        border-radius: 50%;
        font-size: 1.6rem;
    }
    .auth-icon-wrap .auth-icon.blue {
        background: linear-gradient(135deg, #dbeafe, #eff6ff);
        color: #3b82f6;
    }
    .auth-icon-wrap .auth-icon.red {
        background: linear-gradient(135deg, #fee2e2, #fef2f2);
        color: #ef4444;
    }
    /* Password input with toggle */
    .password-wrap {
        position: relative;
    }
    .password-wrap .form-control {
        padding-right: 48px;
    }
    .password-wrap .toggle-pw {
        position: absolute;
        right: 4px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        padding: 8px 10px;
        font-size: 1rem;
        transition: color 0.2s;
    }
    .password-wrap .toggle-pw:hover {
        color: #3b82f6;
    }
    /* Strength bar */
    .pw-strength {
        margin-top: 8px;
        height: 4px;
        border-radius: 2px;
        background: #e2e8f0;
        overflow: hidden;
    }
    .pw-strength-bar {
        height: 100%;
        width: 0;
        border-radius: 2px;
        transition: width 0.3s, background 0.3s;
    }
    .pw-strength-text {
        font-size: 0.78rem;
        font-weight: 600;
        margin-top: 4px;
        min-height: 18px;
    }
    /* Error box */
    .error-box {
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 10px;
        padding: 16px;
        color: #991b1b;
        font-size: 0.9rem;
        line-height: 1.5;
        margin-bottom: 20px;
    }
    .error-box i {
        margin-right: 8px;
        color: #ef4444;
    }
    /* Spinner */
    .btn-spinner {
        display: inline-block;
        width: 18px;
        height: 18px;
        border: 3px solid rgba(255,255,255,0.3);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
        margin-right: 8px;
        vertical-align: middle;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
</style>

<main class="page auth-page">
    <div class="container">
        <div class="auth-card">

<?php if (!$validToken): ?>
            <!-- ── Token không hợp lệ / hết hạn ── -->
            <div class="auth-icon-wrap">
                <div class="auth-icon red"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
            <h2 class="auth-title">Link không hợp lệ</h2>
            <div class="error-box">
                <i class="fas fa-times-circle"></i>
                <?= htmlspecialchars($errorMessage) ?>
            </div>
            <a href="<?= BASE_URL ?>auth/quenmatkhau.php" class="btn-auth" style="display:block; text-align:center; text-decoration:none;">
                <i class="fas fa-redo"></i> Yêu cầu lại
            </a>
            <div class="auth-link">
                <a href="<?= BASE_URL ?>auth/dangnhap.php"><i class="fas fa-arrow-left"></i> Quay lại đăng nhập</a>
            </div>

<?php else: ?>
            <!-- ── Form đặt mật khẩu mới ── -->
            <div class="auth-icon-wrap">
                <div class="auth-icon blue"><i class="fas fa-lock-open"></i></div>
            </div>
            <h2 class="auth-title">Đặt mật khẩu mới</h2>
            <p class="auth-subtitle">Nhập mật khẩu mới cho tài khoản <strong><?= htmlspecialchars($user['hoten'] ?? '') ?></strong></p>

            <form id="resetForm" onsubmit="return false;">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="mb-3">
                    <label class="form-label" for="matkhau_moi">Mật khẩu mới</label>
                    <div class="password-wrap">
                        <input name="matkhau_moi" class="form-control" type="password" id="matkhau_moi" placeholder="Tối thiểu 6 ký tự" autocomplete="new-password" required>
                        <button type="button" class="toggle-pw" tabindex="-1"><i class="fas fa-eye"></i></button>
                    </div>
                    <div class="pw-strength"><div class="pw-strength-bar" id="strengthBar"></div></div>
                    <div class="pw-strength-text" id="strengthText"></div>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="matkhau_xacnhan">Xác nhận mật khẩu</label>
                    <div class="password-wrap">
                        <input name="matkhau_xacnhan" class="form-control" type="password" id="matkhau_xacnhan" placeholder="Nhập lại mật khẩu mới" autocomplete="new-password" required>
                        <button type="button" class="toggle-pw" tabindex="-1"><i class="fas fa-eye"></i></button>
                    </div>
                </div>

                <button class="btn-auth" type="submit" id="btnReset">
                    <i class="fas fa-check-circle"></i> Đặt lại mật khẩu
                </button>
            </form>

            <div class="auth-link">
                <a href="<?= BASE_URL ?>auth/dangnhap.php"><i class="fas fa-arrow-left"></i> Quay lại đăng nhập</a>
            </div>
<?php endif; ?>

        </div>
    </div>
</main>

<?php if ($validToken): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('resetForm');
    const btn = document.getElementById('btnReset');
    const passwordInput = document.getElementById('matkhau_moi');
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');

    // ── Password strength indicator ──
    passwordInput.addEventListener('input', function() {
        const val = this.value;
        let score = 0, label = '', color = '';

        if (val.length >= 6) score++;
        if (val.length >= 8) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        if (val.length === 0) {
            strengthBar.style.width = '0';
            strengthText.textContent = '';
            return;
        }
        if (score <= 1) { label = 'Yếu'; color = '#ef4444'; }
        else if (score <= 2) { label = 'Trung bình'; color = '#f59e0b'; }
        else if (score <= 3) { label = 'Khá'; color = '#3b82f6'; }
        else { label = 'Mạnh'; color = '#10b981'; }

        strengthBar.style.width = (score / 5 * 100) + '%';
        strengthBar.style.background = color;
        strengthText.textContent = label;
        strengthText.style.color = color;
    });

    // ── Toggle password visibility ──
    document.querySelectorAll('.toggle-pw').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    });

    // ── Submit form ──
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const mk = document.getElementById('matkhau_moi').value;
        const mkx = document.getElementById('matkhau_xacnhan').value;

        if (mk.length < 6) {
            Swal.fire({ icon: 'warning', title: 'Mật khẩu phải có ít nhất 6 ký tự', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
            return;
        }
        if (mk !== mkx) {
            Swal.fire({ icon: 'warning', title: 'Mật khẩu xác nhận không khớp', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="btn-spinner"></span> Đang xử lý...';

        const fd = new FormData(form);

        fetch('<?= BASE_URL ?>auth/api_datlamatkhau.php', {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công!',
                    text: data.message,
                    confirmButtonText: 'Đăng nhập ngay',
                    confirmButtonColor: '#3b82f6',
                    allowOutsideClick: false
                }).then(() => {
                    window.location.href = data.redirect || '<?= BASE_URL ?>auth/dangnhap.php';
                });
            } else {
                Swal.fire({ icon: 'error', title: data.message || 'Có lỗi xảy ra', toast: true, position: 'top-end', showConfirmButton: false, timer: 4000, timerProgressBar: true });
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Đặt lại mật khẩu';
            }
        })
        .catch(() => {
            Swal.fire({ icon: 'error', title: 'Lỗi kết nối! Vui lòng thử lại.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle"></i> Đặt lại mật khẩu';
        });
    });
});
</script>
<?php endif; ?>

<?php include BASE_PATH . 'includes/footer.php'; ?>
