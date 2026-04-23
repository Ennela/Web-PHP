<?php
    session_start();

    if (isset($_GET['login'])) {
        $dangxuat = $_GET['login'];
    } else {
        $dangxuat = '';
    }
    if ($dangxuat == 'dangxuat') {
        session_destroy();
        header('Location: giohang.php');
    }
    include './connect.php';

    if (!isset($_SESSION['giohang'])) {
        $_SESSION['giohang']
            = array(); // nếu như chưa tồn tại thì tạo từ một mảng rỗng
    }
    if (isset($_GET['action'])) {
        function update_cart($add = false)
        {
            foreach ($_POST['quantity'] as $masp => $quantity) {
                if ($quantity == 0) {
                    unset($_SESSION["giohang"][$masp]); //số lượng bằng 0 thì xoá ko hiện
                } else {
                    if ($add) {
                        $_SESSION["giohang"][$masp] += $quantity;
                    } else {
                        $_SESSION["giohang"][$masp] = $quantity;
                    }
                }
            }
        }

        switch ($_GET['action']) {
            case "add":
//                foreach ($_POST['quantity'] as $masp => $quantity) {
//                    $_SESSION["giohang"][$masp] = $quantity;
//                }
                update_cart(true);
                header('Location:./giohang.php');// cho đẹp đường dẫn
                break;

            case "delete":
                if (isset($_GET['masp'])) {
                    unset($_SESSION["giohang"][$_GET['masp']]);
                }
                header('Location:./giohang.php');// cho đẹp đường dẫn
//                var_dump($_SESSION); exit; lấy đc rồi
                break;
            case "submit":
                if (isset($_POST['capnhat'])) { //Cập nhật số lượng sản phẩm
                    update_cart();
                    header('Location: ./giohang.php');
                } elseif (isset($_POST['dathang'])) { //Đặt hàng sản phẩm
                    $chuyen = $_POST['quantity'];
                    $_SESSION['chuyen'] = $chuyen;

                    header('Location: ./infodathang.php');
                } elseif (isset($_POST['thanhtoanonline'])) { //Đặt hàng sản phẩm
                    $chuyen = $_POST['quantity'];
                    $_SESSION['chuyen'] = $chuyen;

                    header('Location: ./vnpay_php/dathangonline.php');
                }


                break;
        }
    }
    if (!empty($_SESSION["giohang"])) {
        $products = mysqli_query($con,
            "SELECT * FROM `tbl_qlsanpham` WHERE `masp` IN (" . implode(",", array_keys($_SESSION["giohang"])) . ")");
    }
    include 'header.php';
?>


<main class="page shopping-cart-page">
    <section class="clean-block clean-cart dark">
        <div class="container">
            <div class="block-heading">
                <h2 class="text-info">Giỏ hàng</h2>
                <p>Thanh toán ngay khi còn hàng</p>
            </div>
            <div class="content">
                <form method="post" action="giohang.php?action=submit">

                    <div class="row g-0">
                        <div class="col-md-12 col-lg-8">

                            <div class="items">
                                <?php
                                    if (!empty($products)) {
                                        $num = 1;
                                        $total = 0;
                                        while ($row = mysqli_fetch_array($products)) { ?>
                                            <div class="product">

                                                <div class="row justify-content-center align-items-center">
                                                    <div class="col-md-3">
                                                        <div class="product-image"><img
                                                                    class="img-fluid d-block mx-auto image"
                                                                    src="<?php echo BASE_URL; ?>admin/<?= $row['anhdaidien'] ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 product-info">
                                                        <a style="text-decoration: none"
                                                           class="product-name"
                                                           href="chitietsanpham.php?masp=<?= $row['masp'] ?>"><?= $row['tensp'] ?></a>
                                                    </div>
                                                    <div class="col-6 col-md-2 quantity">
                                                        <label class="form-label d-none d-md-block">Số
                                                            lượng</label><input
                                                                type="text" id="number"
                                                                value="<?= $_SESSION["giohang"][$row['masp']] ?>"
                                                                name="quantity[<?= $row['masp'] ?>]"
                                                                class="form-control quantity-input">
                                                    </div>
                                                    <div class="col-5 col-md-2 price">
                                                        <span><?= number_format($row['giasanpham'] * $_SESSION["giohang"][$row['masp']], 0, ",", ".") ?></span>
                                                    </div>
                                                    <div class="col-1 col-md-1 price"><a
                                                                href="giohang.php?action=delete&masp=<?= $row['masp'] ?>">xoá</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php
                                            $total += $row['giasanpham'] * $_SESSION["giohang"][$row['masp']];
                                            $num++;
                                        }

                                    } else {
                                        echo "Bạn đang không có sản phẩm nào";
                                    }


                                ?>
                                <!--                              sản phẩn dưới này-->
                            </div>
                        </div>
                        <div class="col-md-12 col-lg-4">

                            <div class="summary">
                                <h3>Tóm lược</h3>
                                <?php
                                    if (isset($total)) {
                                        ?>
                                        <h4><span class="text">Tổng tiền</span><span
                                                    class="price"><?= number_format($total, 0, ",", ".") ?> VND</span>
                                        </h4>
                                        <h4><span class="text">Chiết khấu</span><span
                                                    class="price">0 VND</span></h4>
                                        <h4>
                                            <span class="text">Phí vận chuyển</span><span
                                                    class="price">0 VND</span></h4>
                                        <h4><span class="text">Tổng</span><span
                                                    class="price"><?= number_format($total, 0, ",", ".") ?> VND</span>
                                        </h4>
                                        <?php
                                    }
                                ?>
                                <button class="btn btn-danger btn-lg d-block w-100"
                                        name="capnhat"
                                        type="submit">Cập nhật
                                </button>

                                <button class="btn btn-primary btn-lg d-block w-100"
                                        name="dathang"
                                        type="submit">Đặt hàng
                                </button>
                                <button class="btn btn-warning btn-lg d-block w-100"
                                        name="thanhtoanonline"
                                        type="submit">Đặt và Thanh toán online
                                </button>
                            </div>


                </form>

            </div>
        </div>
        </div>
        </div>
    </section>
</main>
<footer class="page-footer dark">
    <div class="container">
        <div class="row">
            <div class="col-sm-3">
                <h5>Bắt đầu</h5>
                <ul>
                    <li><a href="#">Trang chủ</a></li>
                    <li><a href="#">Đăng nhập</a></li>
                    <li><a href="#">Giỏ hàng</a></li>
                </ul>
            </div>
            <div class="col-sm-3">
                <h5>Thông tin</h5>
                <ul>
                    <li><a href="#">Liên lạc</a></li>
                    <li><a href="#">Giưới thiệu</a></li>
                    <li><a href="#">Reviews</a></li>
                </ul>
            </div>
            <div class="col-sm-3">
                <h5>Hỗ trợ</h5>
                <ul>
                    <li><a href="#">FAQ</a></li>
                    <li><a href="#">SĐT</a></li>
                    <li><a href="#">Diễn đàn&nbsp;</a></li>
                </ul>
            </div>
            <div class="col-sm-3">
                <h5>Hợp pháp</h5>
                <ul>
                    <li><a href="#">Điều kiện dịch vụ</a></li>
                    <li><a href="#">Điều kiện sử dụng</a></li>
                    <li><a href="#">Chính sách riêng tư</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="footer-copyright">
        <p>© 2021 Copyright Text</p>
    </div>
</footer>
<script src="assets/bootstrap/js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/baguettebox.js/1.10.0/baguetteBox.min.js"></script>
<script src="assets/js/vanilla-zoom.js"></script>
<script src="assets/js/theme.js"></script>
</body>

</html>