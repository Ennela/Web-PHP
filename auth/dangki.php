<?php
require_once dirname(__DIR__) . '/config.php';

    include_once BASE_PATH . 'includes/connect.php';
    if (isset($_POST['dangki'])) {
        // variables for input data
        $hoten = $_POST['hoten'];
        $taikhoan = $_POST['taikhoan'];
        $matkhau = $_POST['matkhau'];

        // variables for input data

        // sql query for inserting data into database
        $sql_query = "INSERT INTO tbl_tkkhachhang(hoten,username,password) VALUES('$hoten','$taikhoan','$matkhau') ";
        // sql query for inserting data into database

        // sql query execution function
        if (mysqli_query($con, $sql_query)) {
            ?>
            <script type="text/javascript">
                alert('Dữ liệu được chèn thành công ');
                window.location.href = 'dangnhap.php';
            </script>
            <?php
        } else {
            ?>
            <script type="text/javascript">
                alert('Xảy ra lỗi trong khi chèn dữ liệu của bạn');
            </script>
            <?php
        }
        // sql query execution function
    }
    include BASE_PATH . 'includes/header.php';
?>

    <main class="page registration-page">
        <section class="clean-block clean-form dark">
            <div class="container">
                <div class="block-heading">
                    <h2 class="text-info">Đăng kí</h2>
                    <p>Đăng kí tài khoản tại đây</p>
                </div>
                <form method="post">
                    <div class="mb-3"><label class="form-label">Họ và tên</label><input class="form-control item" name="hoten" type="text"></div>
                    <div class="mb-3"><label class="form-label">Tên đăng nhập</label><input class="form-control item" type="text" name="taikhoan"></div>
                    <div class="mb-3"><label class="form-label">Mật khẩu</label><input class="form-control item" name="matkhau" type="password"></div>
                    <button name="dangki" class="btn btn-primary" type="submit">Đăng kí</button>
                </form>
            </div>
        </section>
    </main>

<?php include BASE_PATH . 'includes/footer.php'; ?>