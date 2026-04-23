<?php
require_once dirname(__DIR__) . '/config.php';
    session_start();
    include BASE_PATH . 'includes/connect.php';

    if (isset($_GET['login'])) {
        $dangxuat = $_GET['login'];
    } else {
        $dangxuat = '';
    }
    if ($dangxuat == 'dangxuat') {
        session_destroy();
        header('Location: dangnhap.php');
    }

    if(isset($_POST['dangnhap'])) {
        $taikhoan = $_POST['taikhoan'];
        $matkhau = $_POST['matkhau'];
        if($taikhoan=='' || $matkhau ==''){
            ?>
            <script type="text/javascript">
                alert('Mời bạn nhập đủ thông tin');
                window.location.href = 'dangnhap.php';
            </script>
            <?php
        }else{
            $sql_select_admin = mysqli_query($con,"SELECT * FROM tbl_tkkhachhang WHERE username='$taikhoan' AND password='$matkhau' LIMIT 1");
            $count = mysqli_num_rows($sql_select_admin);
            $row_dangnhap = mysqli_fetch_array($sql_select_admin);
            if($count>0){
                $_SESSION['dangnhap'] = $row_dangnhap['hoten'];
                $_SESSION['makh'] = $row_dangnhap['makh'];
                header('Location: ' . BASE_URL . 'home/trangchu.php');

            }else{
                ?>
                <script type="text/javascript">
                    alert('Sai tài khoản hoặc mật khẩu');
                    window.location.href = 'dangnhap.php';
                </script>
                <?php
            }
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
            <form action="<?php echo BASE_URL; ?>auth/dangnhap.php" method="POST">
                <div class="mb-3">
                    <label class="form-label" for="email">Tài khoản</label>
                    <input name="taikhoan" class="form-control" type="text" id="email" placeholder="Nhập tên đăng nhập">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Mật khẩu</label>
                    <input name="matkhau" class="form-control" type="password" id="password" placeholder="Nhập mật khẩu">
                </div>
                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="checkbox">
                        <label class="form-check-label" for="checkbox">Ghi nhớ đăng nhập</label>
                    </div>
                </div>
                <button class="btn-auth" name="dangnhap" type="submit">Đăng nhập</button>
            </form>
            <div class="auth-link">Chưa có tài khoản? <a href="<?php echo BASE_URL; ?>auth/dangki.php">Đăng ký ngay</a></div>
        </div>
    </div>
</main>

<?php include BASE_PATH . 'includes/footer.php'; ?>