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

<main class="page login-page">
    <section class="clean-block clean-form dark">
        <div class="container">
            <div class="block-heading">
                <h2 class="text-info">Đăng nhập</h2>
            </div>
            <form action="<?php echo BASE_URL; ?>auth/dangnhap.php" method="POST">
                <div class="mb-3"><label class="form-label" for="email">Tài khoản</label><input name="taikhoan" class="form-control item" type="text" id="email"></div>
                <div class="mb-3"><label class="form-label" for="password">Mật khẩu</label><input name="matkhau" class="form-control" type="password" id="password"></div>
                <div class="mb-3">
                    <div class="form-check"><input class="form-check-input" type="checkbox" id="checkbox"><label class="form-check-label" for="checkbox">Ghi nhớ&nbsp;</label></div>
                </div><button class="btn btn-primary" name="dangnhap" type="submit">Đăng nhập</button>
            </form>
        </div>
    </section>
</main>

<?php include BASE_PATH . 'includes/footer.php'; ?>