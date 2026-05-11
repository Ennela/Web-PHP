<?php
require_once dirname(__DIR__) . '/config.php';
session_start();
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
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
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
        .auth-card .auth-link a:hover {
            text-decoration: underline;
        }
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
        background: linear-gradient(135deg, #dbeafe, #eff6ff);
        color: #3b82f6;
        font-size: 1.6rem;
    }

    .auth-info-text {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px 16px;
        font-size: 0.85rem;
        color: #64748b;
        line-height: 1.5;
        margin-bottom: 20px;
    }

    .auth-info-text i {
        color: #3b82f6;
        margin-right: 6px;
    }

    /* Success state */
    .success-state {
        display: none;
        text-align: center;
    }

    .success-state.active {
        display: block;
    }

    .success-state .success-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #d1fae5, #ecfdf5);
        color: #10b981;
        font-size: 2rem;
        margin-bottom: 16px;
        animation: successPulse 0.6s ease;
    }

    @keyframes successPulse {
        0% {
            transform: scale(0.5);
            opacity: 0;
        }

        50% {
            transform: scale(1.1);
        }

        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    .success-state h3 {
        font-family: 'Montserrat', sans-serif;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 8px;
    }

    .success-state p {
        color: #64748b;
        font-size: 0.9rem;
        line-height: 1.5;
        margin-bottom: 20px;
    }

    /* Spinner */
    .btn-spinner {
        display: inline-block;
        width: 18px;
        height: 18px;
        border: 3px solid rgba(255, 255, 255, 0.3);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
        margin-right: 8px;
        vertical-align: middle;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>

<main class="page auth-page">
    <div class="container">
        <div class="auth-card">
            <!-- Form State -->
            <div id="formState">
                <div class="auth-icon-wrap">
                    <div class="auth-icon"><i class="fas fa-key"></i></div>
                </div>
                <h2 class="auth-title">Quên mật khẩu</h2>
                <p class="auth-subtitle">Nhập email hoặc tên đăng nhập để khôi phục tài khoản</p>

                <div class="auth-info-text">
                    <i class="fas fa-info-circle"></i>
                    Chúng tôi sẽ gửi một liên kết đặt lại mật khẩu đến email đã đăng ký. Link có hiệu lực trong
                    <strong>30 phút</strong>.
                </div>

                <form id="forgotForm" onsubmit="return false;">
                    <div class="mb-4">
                        <label class="form-label" for="email_or_username">Email hoặc tên đăng nhập</label>
                        <input name="email_or_username" class="form-control" type="text" id="email_or_username"
                            placeholder="Nhập email hoặc tên đăng nhập" autocomplete="username" required>
                    </div>
                    <button class="btn-auth" type="submit" id="btnSubmit">
                        <i class="fas fa-paper-plane"></i> Gửi yêu cầu
                    </button>
                </form>

                <div class="auth-link">
                    <a href="<?php echo BASE_URL; ?>auth/dangnhap.php"><i class="fas fa-arrow-left"></i> Quay lại đăng
                        nhập</a>
                </div>
            </div>

            <!-- Success State -->
            <div class="success-state" id="successState">
                <div class="success-icon"><i class="fas fa-envelope-open-text"></i></div>
                <h3>Đã gửi email!</h3>
                <p id="successMessage">Vui lòng kiểm tra hộp thư (bao gồm thư mục Spam) để đặt lại mật khẩu.</p>
                <a href="<?php echo BASE_URL; ?>auth/dangnhap.php" class="btn-auth"
                    style="display:inline-block; text-decoration:none; width:auto; padding:12px 32px;">
                    <i class="fas fa-sign-in-alt"></i> Về trang đăng nhập
                </a>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('forgotForm');
        const btn = document.getElementById('btnSubmit');
        const formState = document.getElementById('formState');
        const successState = document.getElementById('successState');
        const successMessage = document.getElementById('successMessage');

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const input = document.getElementById('email_or_username').value.trim();
            if (!input) {
                Swal.fire({ icon: 'warning', title: 'Vui lòng nhập email hoặc tên đăng nhập', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
                return;
            }

            // Disable button + spinner
            btn.disabled = true;
            btn.innerHTML = '<span class="btn-spinner"></span> Đang xử lý...';

            const fd = new FormData();
            fd.append('email_or_username', input);

            fetch('<?php echo BASE_URL; ?>auth/api_quenmatkhau.php', {
                method: 'POST',
                body: fd
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        successMessage.textContent = data.message || 'Vui lòng kiểm tra hộp thư để đặt lại mật khẩu.';
                        formState.style.display = 'none';
                        successState.classList.add('active');
                    } else {
                        Swal.fire({ icon: 'error', title: data.message || 'Có lỗi xảy ra', toast: true, position: 'top-end', showConfirmButton: false, timer: 4000, timerProgressBar: true });
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Gửi yêu cầu';
                    }
                })
                .catch(() => {
                    Swal.fire({ icon: 'error', title: 'Lỗi kết nối! Vui lòng thử lại.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Gửi yêu cầu';
                });
        });
    });
</script>

<?php include BASE_PATH . 'includes/footer.php'; ?>