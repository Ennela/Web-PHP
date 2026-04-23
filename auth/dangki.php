<?php
require_once dirname(__DIR__) . '/config.php';

    include_once BASE_PATH . 'includes/connect.php';
    if (isset($_POST['dangki'])) {
        session_start();
        $hoten = trim($_POST['hoten'] ?? '');
        $taikhoan = trim($_POST['taikhoan'] ?? '');
        $matkhau = trim($_POST['matkhau'] ?? '');

        if ($hoten === '' || $taikhoan === '' || $matkhau === '') {
            $_SESSION['swal_warning'] = 'Vui lòng nhập đầy đủ thông tin!';
            header('Location: dangki.php');
            exit;
        }

        // Check for duplicate username
        $check_query = mysqli_query($con, "SELECT * FROM tbl_tkkhachhang WHERE username='$taikhoan' LIMIT 1");
        if (mysqli_num_rows($check_query) > 0) {
            $_SESSION['swal_error'] = 'Tên đăng nhập đã tồn tại. Vui lòng chọn tên khác!';
            header('Location: dangki.php');
            exit;
        }

        $sql_query = "INSERT INTO tbl_tkkhachhang(hoten,username,password) VALUES('$hoten','$taikhoan','$matkhau')";
        if (mysqli_query($con, $sql_query)) {
            $_SESSION['swal_success'] = 'Đăng ký tài khoản thành công!';
            header('Location: dangnhap.php');
            exit;
        } else {
            $_SESSION['swal_error'] = 'Xảy ra lỗi trong khi đăng ký. Vui lòng thử lại!';
            header('Location: dangki.php');
            exit;
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
</style>

    <main class="page auth-page">
        <div class="container">
            <div class="auth-card">
                <h2 class="auth-title">Đăng ký</h2>
                <p class="auth-subtitle">Tạo tài khoản để mua sắm dễ dàng hơn</p>
                <form method="post">
                    <div class="mb-3"><label class="form-label">Họ và tên</label><input class="form-control" name="hoten" type="text" placeholder="Nhập họ và tên"></div>
                    <div class="mb-3"><label class="form-label">Tên đăng nhập</label><input class="form-control" type="text" name="taikhoan" placeholder="Nhập tên đăng nhập"></div>
                    <div class="mb-4"><label class="form-label">Mật khẩu</label><input class="form-control" name="matkhau" type="password" placeholder="Nhập mật khẩu"></div>
                    <button name="dangki" class="btn-auth" type="submit">Đăng ký</button>
                </form>
                <div class="auth-link">Đã có tài khoản? <a href="<?php echo BASE_URL; ?>auth/dangnhap.php">Đăng nhập</a></div>
            </div>
        </div>
    </main>

<?php include BASE_PATH . 'includes/footer.php'; ?>