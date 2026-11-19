<?php
require_once dirname(__DIR__) . '/config.php';
    // A known page ID is accepted here, never an arbitrary URL or an admin destination.
    $returnId = $_POST['return_to'] ?? $_GET['return_to'] ?? 'home';
    $returnDestination = app_return_destination($returnId);
    $loginDestination = BASE_URL . 'auth/dangnhap.php?return_to=' . rawurlencode($returnId);
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    include BASE_PATH . 'includes/connect.php';

    // Đăng xuất được xử lý tập trung trong config.php (auth_handle_logout_request)

    if (isset($_POST['dangnhap'])) {
        $taikhoan = trim($_POST['taikhoan'] ?? '');
        $matkhau = trim($_POST['matkhau'] ?? '');
        if ($taikhoan === '' || $matkhau === '') {
            $_SESSION['swal_warning'] = 'Vui lòng nhập đầy đủ thông tin!';
            app_redirect($loginDestination);
            exit;
        }

        // Chống brute-force: kiểm tra khóa tạm thời trước khi truy vấn tài khoản
        $lock = auth_throttle_lock_seconds($con, 'customer', $taikhoan);
        if ($lock > 0) {
            $_SESSION['swal_error'] = auth_lock_message($lock);
            app_redirect($loginDestination);
            exit;
        }

        $stmt = mysqli_prepare($con, "SELECT * FROM tbl_tkkhachhang WHERE username = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $taikhoan);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row_dangnhap = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        // Luôn chạy password_verify kể cả khi không có tài khoản (không lộ tài khoản tồn tại qua thời gian phản hồi)
        [$loginSuccess, $newHash] = auth_verify_password($matkhau, $row_dangnhap['password'] ?? null);

        if ($loginSuccess && $newHash !== null) {
            $upStmt = mysqli_prepare($con, "UPDATE tbl_tkkhachhang SET password = ? WHERE makh = ?");
            mysqli_stmt_bind_param($upStmt, "si", $newHash, $row_dangnhap['makh']);
            mysqli_stmt_execute($upStmt);
            mysqli_stmt_close($upStmt);
        }

        if ($loginSuccess) {
            auth_throttle_clear($con, 'customer', $taikhoan);
            auth_login([
                'dangnhap' => $row_dangnhap['hoten'],
                'makh'     => (int)$row_dangnhap['makh'],
            ]);
            app_redirect($returnDestination, 303);
            exit;
        }

        auth_throttle_record_failure($con, 'customer', $taikhoan);
        // Thông báo chung chung: không tiết lộ sai tài khoản hay sai mật khẩu
        $_SESSION['swal_error'] = 'Sai tài khoản hoặc mật khẩu!';
        app_redirect($loginDestination);
        exit;
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
    .auth-card .form-check-label {
        font-size: clamp(0.82rem, 1.8vw, 0.88rem);
        font-weight: 500;
        color: #64748b;
    }
    .auth-card .form-check-input {
        width: 18px;
        height: 18px;
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
</style>

<main class="page auth-page">
    <div class="container">
        <div class="auth-card">
            <h2 class="auth-title">Đăng nhập</h2>
            <p class="auth-subtitle">Đăng nhập để trải nghiệm mua sắm</p>
            <form action="<?= e(BASE_URL) ?>auth/dangnhap.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="return_to" value="<?= e($returnId) ?>">
                <div class="mb-3">
                    <label class="form-label" for="email">Tài khoản</label>
                    <input name="taikhoan" class="form-control" type="text" id="email" placeholder="Nhập tên đăng nhập">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Mật khẩu</label>
                    <input name="matkhau" class="form-control" type="password" id="password" placeholder="Nhập mật khẩu">
                </div>
                <div class="mb-4" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                    <div class="form-check" style="margin:0;">
                        <input class="form-check-input" type="checkbox" id="checkbox">
                        <label class="form-check-label" for="checkbox">Ghi nhớ đăng nhập</label>
                    </div>
                    <a href="<?= e(BASE_URL) ?>auth/quenmatkhau.php" style="color:#3b82f6; font-weight:600; font-size:clamp(0.8rem,1.8vw,0.85rem); text-decoration:none;">Quên mật khẩu?</a>
                </div>
                <button class="btn-auth" name="dangnhap" type="submit">Đăng nhập</button>
            </form>
            <div class="auth-link">Chưa có tài khoản? <a href="<?= e(BASE_URL) ?>auth/dangki.php">Đăng ký ngay</a></div>
        </div>
    </div>
</main>

<?php include BASE_PATH . 'includes/footer.php'; ?>
