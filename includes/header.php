<?php require_once dirname(__DIR__) . '/config.php'; ?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Blog - Brand</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat:400,400i,700,700i,600,600i">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/fonts/font-awesome.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/fonts/simple-line-icons.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/fonts/fontawesome5-overrides.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/Bootstrap-4---Product-List.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/baguettebox.js/1.10.0/baguetteBox.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/MUSA_carousel-product-cart-slider-1.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/MUSA_carousel-product-cart-slider.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/untitled-1.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/untitled.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/vanilla-zoom.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/test.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>sua.css">
</head>

<body>
<div class="container fixed-top d-none d-lg-block">
    <div class="row">
        <div class="col ">
            <ul class="top-bar__social-list">
                <li><a href=""><img src="<?php echo BASE_URL; ?>images/fb.png" height="25" width="25"/></a></li>
                <li><a href=""><img src="<?php echo BASE_URL; ?>images/g.png" height="25" width="25"/></a></li>
                <li><a href=""><img src="<?php echo BASE_URL; ?>images/mail.png" height="25" width="25"/></a></li>
            </ul>
        </div>
        <div class="col ">
            <div class=" container top-bar__link">
                <form class="row" action="<?php echo BASE_URL; ?>shop/shop.php" method="GET">
                    <input class=" col search " type="search" name="search" placeholder="Nhập tên sản phẩm cần tìm..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    <div class=" col ">
                        <button class="btn btn-primary" type="submit" >Tìm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<nav class="navbar navbar-light navbar-expand-lg fixed-top bg-white clean-navbar">
    <div class="container">

        <a class="navbar-brand logo" href="<?php echo BASE_URL; ?>home/trangchu.php">
            <img src="<?php echo BASE_URL; ?>assets/img/LogoMakr-5wk3kI.png" style="width: 109px;" alt="Logo">
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navcol-1" aria-controls="navcol-1" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navcol-1">
            <?php
                if (!empty($_SESSION['dangnhap'])) { ?>
                    <div class="them d-none d-lg-block"><?php echo 'Chào: ' . $_SESSION['dangnhap'] ?><a class="dangxuat"
                                                                                       href="?login=dangxuat">Đăng
                            xuất</a>
                    </div>
                    <?php
                }
            ?>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item "><a class="nav-link active test-color" href="<?php echo BASE_URL; ?>home/trangchu.php">Trang chủ</a></li>
                <li class="nav-item"><a class="nav-link active test-color" href="<?php echo BASE_URL; ?>shop/shop.php">Cửa hàng</a></li>
                <li class="nav-item"><a class="nav-link active test-color" href="<?php echo BASE_URL; ?>blog/baiviet.php">blog</a></li>
                <li class="nav-item"><a class="nav-link active test-color" href="<?php echo BASE_URL; ?>home/vechungtoi.php">giới thiệu</a></li>
                <li class="nav-item"><a class="nav-link active test-color" href="<?php echo BASE_URL; ?>home/lienhe.php"
                                        style="font-family: Montserrat, sans-serif;">liên hệ</a></li>
                <li class="nav-item"><a class="nav-link active test-color" href="<?php echo BASE_URL; ?>shop/tradonhang.php">tra cứu đơn hàng</a></li>
                <li class="nav-item dropdown" style="padding-right: 2rem;"><a
                            aria-expanded="false"
                            class="dropdown-toggle nav-link test-color"
                            data-bs-toggle="dropdown" href="#"
                            style="padding-right: .5rem;padding-left: .5rem;font-weight: 600;font-size: .8rem;text-transform: uppercase;transform: scale(1.03);color: var(--bs-dark);text-decoration: none;font-family: Montserrat, sans-serif;">Tài
                        khoản</a>
                    <div class="dropdown-menu">
                        <?php if (empty($_SESSION['dangnhap'])) { ?>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>auth/dangnhap.php" style="padding-right: .5rem;padding-left: .5rem;font-weight: 600;font-size: .8rem;text-transform: uppercase;transform: scale(1.03);color: var(--bs-dark);text-decoration: none;font-family: Montserrat, sans-serif;">Đăng nhập</a>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>auth/dangki.php" style="padding-right: .5rem;padding-left: .5rem;font-weight: 600;font-size: .8rem;text-transform: uppercase;transform: scale(1.03);color: var(--bs-dark);text-decoration: none;font-family: Montserrat, sans-serif;">Đăng ký</a>
                        <?php } else { ?>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>auth/thongtintaikhoan.php" style="padding-right: .5rem;padding-left: .5rem;font-weight: 600;font-size: .8rem;text-transform: uppercase;transform: scale(1.03);color: var(--bs-dark);text-decoration: none;font-family: Montserrat, sans-serif;">Thông tin tài khoản</a>
                            <a class="dropdown-item" href="?login=dangxuat" style="padding-right: .5rem;padding-left: .5rem;font-weight: 600;font-size: .8rem;text-transform: uppercase;transform: scale(1.03);color: var(--bs-dark);text-decoration: none;font-family: Montserrat, sans-serif;">Đăng xuất</a>
                        <?php } ?>
                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>shop/giohang.php" style="padding-right: .5rem;padding-left: .5rem;font-weight: 600;font-size: .8rem;text-transform: uppercase;transform: scale(1.03);color: var(--bs-dark);text-decoration: none;font-family: Montserrat, sans-serif;">Giỏ hàng</a>
                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>shop/tradonhang.php" style="padding-right: .5rem;padding-left: .5rem;font-weight: 600;font-size: .8rem;text-transform: uppercase;transform: scale(1.03);color: var(--bs-dark);text-decoration: none;font-family: Montserrat, sans-serif;">Tra cứu đơn hàng</a>
                    </div>
                </li>

            </ul>
        </div>
        <!-- <i class="fas fa-shopping-cart"></i> -->
    </div>




</nav>