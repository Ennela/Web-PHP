<?php
require_once dirname(__DIR__) . '/config.php';
session_start();

//if (!isset($_SESSION['dangnhap'])) {
//
//    header('Location: index.php');
//}
if (isset($_GET['login'])) {
    $dangxuat = $_GET['login'];
} else {
    $dangxuat = '';
}
if ($dangxuat == 'dangxuat') {
    session_destroy();
    header('Location: trangchu.php');
}
include BASE_PATH . 'includes/header.php';

?>

<main class="page landing-page">
    <style>
        .modern-caption {
            background: rgba(15, 23, 42, 0.92);
            border-left: 6px solid #fff;
            padding: 30px 35px;
            bottom: 12%;
            top: auto;
            left: 8%;
            right: auto;
            max-width: 480px;
            text-align: left;
            position: absolute;
            transform: translateX(-40px);
            opacity: 0;
            transition: all 0.7s cubic-bezier(0.25, 1, 0.5, 1);
            transition-delay: 0.1s;
            z-index: 10;
        }
        .carousel-item.active .modern-caption {
            transform: translateX(0);
            opacity: 1;
        }
        .modern-caption h2 {
            font-weight: 900;
            font-size: 2.2rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #fff;
            margin-bottom: 15px;
            line-height: 1.1;
        }
        .modern-caption p {
            font-size: 1.1rem;
            color: #e0e0e0;
            margin-bottom: 30px;
            font-weight: 300;
            line-height: 1.6;
        }
        .modern-btn {
            background: #fff;
            color: #000 !important;
            border: 2px solid #fff;
            border-radius: 2px;
            border-color: #fff;
            padding: 12px 45px;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
        }
        .modern-btn:hover {
            background: rgba(0, 0, 0, 0.5);
            color: #fff !important;
            transform: scale(1.02);
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }
    </style>
    <section class="clean-block clean-hero p-0" style="color: transparent;">
        <div class="carousel slide" data-bs-ride="carousel" id="carousel-hero"
            style="width: 100%; position: relative; z-index: 2;">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img class="w-100 d-block" src="<?php echo BASE_URL; ?>images/632663.jpg" alt="Nike LeBron 7"
                        style="height: 600px; object-fit: cover; object-position: center;">
                    <div class="carousel-caption d-none d-md-block modern-caption">
                        <h2 class="text-white">Nike LeBron 7</h2>
                        <p class="text-white">Sức mạnh trên từng bước chạy — hiệu suất vượt trội cho mọi sân đấu.</p>
                        <a href="<?php echo BASE_URL; ?>shop/chitietsanpham.php?masp=80" class="modern-btn mt-3">Khám phá ngay</a>
                    </div>
                </div>
                <div class="carousel-item">
                    <img class="w-100 d-block" src="<?php echo BASE_URL; ?>images/Jordan-1-Light-Smoke-Gray-min.jpg"
                        alt="Air Jordan 1 High Light Smoke Gray"
                        style="height: 600px; object-fit: cover; object-position: center;">
                    <div class="carousel-caption d-none d-md-block modern-caption">
                        <h2 class="text-white">Air Jordan 1 High Light Smoke Gray</h2>
                        <p class="text-white">Huyền thoại sân bóng rổ — phong cách bất tử qua mọi thế hệ.</p>
                        <a href="<?php echo BASE_URL; ?>shop/chitietsanpham.php?masp=83" class="modern-btn mt-3">Khám phá ngay</a>
                    </div>
                </div>
                <div class="carousel-item">
                    <img class="w-100 d-block"
                        src="<?php echo BASE_URL; ?>images/pexels-howard-senton-2148272793-30707531.jpg"
                        alt="Adidas Superstar" style="height: 600px; object-fit: cover; object-position: center;">
                    <div class="carousel-caption d-none d-md-block modern-caption">
                        <h2 class="text-white">Adidas Superstar</h2>
                        <p class="text-white">Biểu tượng đường phố — đơn giản mà đẳng cấp, không bao giờ lỗi mốt.</p>
                        <a href="<?php echo BASE_URL; ?>shop/chitietsanpham.php?masp=67" class="modern-btn mt-3">Khám phá ngay</a>
                    </div>
                </div>
            </div>
            <div>
                <a class="carousel-control-prev" data-bs-slide="prev" href="#carousel-hero" role="button">
                    <span class="carousel-control-prev-icon"></span><span class="visually-hidden">Previous</span>
                </a>
                <a class="carousel-control-next" data-bs-slide="next" href="#carousel-hero" role="button">
                    <span class="carousel-control-next-icon"></span><span class="visually-hidden">Next</span>
                </a>
            </div>
            <ol class="carousel-indicators">
                <li class="active" data-bs-slide-to="0" data-bs-target="#carousel-hero"></li>
                <li data-bs-slide-to="1" data-bs-target="#carousel-hero"></li>
                <li data-bs-slide-to="2" data-bs-target="#carousel-hero"></li>
            </ol>
        </div>
    </section>
    <style>
        .editorial-section {
            background-color: #f1f5f9;
            padding: 80px 0;
        }
        .editorial-heading {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #0f172a;
            margin-bottom: 15px;
        }
        .editorial-subheading {
            font-size: 1.2rem;
            color: #64748b;
            margin-bottom: 50px;
            font-weight: 300;
        }
        .product-highlight-title {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 2.2rem;
            color: #0f172a;
            margin-bottom: 25px;
        }
        .product-highlight-text {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #64748b;
            margin-bottom: 30px;
        }
        .img-editorial {
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            transition: transform 0.5s ease;
        }
        .img-editorial:hover {
            transform: translateY(-10px);
        }
        .btn-editorial {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 14px 40px;
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-editorial:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.3);
        }
    </style>
    <section class="clean-block editorial-section">
        <div class="container">
            <div class="text-center">
                <h2 class="editorial-heading">Kiến tạo phong cách</h2>
                <p class="editorial-subheading">Thể hiện cá tính dứt khoát và nhịp đập bùng nổ của tuổi trẻ qua từng bước chân.</p>
            </div>
            <div class="row align-items-center mt-5">
                <div class="col-md-6 mb-5 mb-md-0">
                    <img class="img-fluid img-editorial" src="<?php echo BASE_URL; ?>admin/uploads/jordan_paris_banner.png" alt="Air Jordan 1 Low Paris">
                </div>
                <div class="col-md-6 ps-md-5">
                    <h3 class="product-highlight-title">AIR JORDAN 1 LOW 'PARIS'</h3>
                    <div class="getting-started-info">
                        <p class="product-highlight-text">Lấy cảm hứng từ kinh đô ánh sáng, phiên bản <strong>'Paris'</strong> mang đến vẻ đẹp thanh lịch vượt thời gian. Màn phối màu hoàn hảo giữa sắc trắng tinh khôi, xám nhạt và xanh pastel dịu mắt tạo nên sự hài hòa đẳng cấp.</p>
                        <p class="product-highlight-text">Lớp da lộn cao cấp kết hợp với da trơn siêu mịn mang lại cảm giác sang trọng. Điểm nhấn chói sáng nằm ở logo Jumpman được dập nổi mạ vàng kiêu hãnh trên lưỡi gà. Không chỉ là một đôi giày, đây là mảnh ghép hoàn hảo xác định đẳng cấp outfit của bạn.</p>
                    </div>
                    <a href="<?php echo BASE_URL; ?>shop/chitietsanpham.php?masp=79" class="btn-editorial mt-2">SỞ HỮU NGAY</a>
                </div>
            </div>
        </div>
    </section>
    <style>
        .features-section {
            padding: 80px 0;
            background-color: #f1f5f9;
        }
        .feature-box {
            text-align: center;
            padding: 40px 25px;
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
            height: 100%;
        }
        .feature-box:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.1);
            border-color: #cbd5e1;
        }
        .feature-icon-wrapper {
            width: 70px;
            height: 70px;
            margin: 0 auto 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #1e293b;
            color: #fff;
            border-radius: 50%;
            font-size: 28px;
            transition: background 0.3s;
        }
        .feature-box:hover .feature-icon-wrapper {
            background: #3b82f6;
        }
        .feature-title {
            font-weight: 800;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            color: #1e293b;
        }
        .feature-desc {
            color: #64748b;
            font-size: 0.95rem;
            line-height: 1.6;
        }
    </style>
    <section class="features-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="editorial-heading">TẠI SAO CHỌN CHÚNG TÔI</h2>
                <p class="editorial-subheading">Cam kết mang lại trải nghiệm mua sắm đẳng cấp và an tâm tuyệt đối.</p>
            </div>
            <div class="row">
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-box">
                        <div class="feature-icon-wrapper"><i class="icon-basket"></i></div>
                        <h4 class="feature-title">GIAO HÀNG TOÀN QUỐC</h4>
                        <p class="feature-desc">Vận chuyển tốc hành, an toàn. Đảm bảo đến tận tay bạn dù ở bất cứ đâu tại Việt Nam.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-box">
                        <div class="feature-icon-wrapper"><i class="far fa-money-bill-alt"></i></div>
                        <h4 class="feature-title">THANH TOÁN KHI NHẬN</h4>
                        <p class="feature-desc">Nhận hàng, kiểm tra kỹ lưỡng sản phẩm trực tiếp rồi mới phải thanh toán.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-box">
                        <div class="feature-icon-wrapper"><i class="far fa-laugh-beam"></i></div>
                        <h4 class="feature-title">BẢO HÀNH DÀI HẠN</h4>
                        <p class="feature-desc">Cam kết bảo hành lên đên 60 ngày cho các chi tiết về keo dán và đường chỉ.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-box">
                        <div class="feature-icon-wrapper"><i class="icon-refresh"></i></div>
                        <h4 class="feature-title">DỄ DÀNG ĐỔI HÀNG</h4>
                        <p class="feature-desc">Sẵn sàng hỗ trợ đổi size, đổi mẫu cực kỳ thoải mái và linh hoạt trong vòng 30 ngày.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <style>
        .bestseller-section {
            padding: 80px 0;
            background: #fff;
        }
        .bestseller-heading {
            font-family: 'Montserrat', sans-serif;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #0f172a;
            margin-bottom: 15px;
            font-size: 2.2rem;
        }
        .bestseller-subheading {
            color: #64748b;
            font-size: 1.1rem;
            margin-bottom: 50px;
            font-weight: 300;
        }
        .product-card {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.1);
            border-color: #cbd5e1;
        }
        .pc-img-wrap {
            position: relative;
            overflow: hidden;
            background: #f8fafc;
        }
        .pc-img-wrap img {
            transition: transform 0.5s ease;
        }
        .product-card:hover .pc-img-wrap img {
            transform: scale(1.05);
        }
        .btn-editorial-outline {
            background: transparent;
            color: #3b82f6;
            border: 2px solid #3b82f6;
            border-radius: 10px;
            padding: 10px 25px;
            font-weight: 700;
            font-size: 0.9rem;
            text-transform: uppercase;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-editorial-outline:hover {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
            border-color: #3b82f6;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
    </style>
    <section class="bestseller-section">
        <div class="container">
            <div class="text-center">
                <h2 class="bestseller-heading">SẢN PHẨM BÁN CHẠY</h2>
                <p class="bestseller-subheading">Những siêu phẩm được săn đón nhiều nhất trong tuần.</p>
            </div>
            <div class="row mt-5">
                <?php
                include_once BASE_PATH . 'includes/connect.php';
                // Lọc thủ công 3 ID giày bán chạy, premium và không bị trùng lặp (vd: Jordan 1 Paris, Jordan High, Adidas Superstar)
                $bestseller_query = mysqli_query($con, "SELECT * FROM `tbl_qlsanpham` WHERE `masp` IN (79, 83, 67)");
                while ($row = mysqli_fetch_array($bestseller_query)) {
                ?>
                <div class="col-md-4 mb-4">
                    <div class="card product-card h-100">
                        <div class="pc-img-wrap">
                            <a href="<?php echo BASE_URL; ?>shop/chitietsanpham.php?masp=<?= $row['masp'] ?>">
                                <img class="card-img-top" src="<?php echo BASE_URL; ?>admin/<?= $row['anhdaidien'] ?>" alt="<?= $row['tensp'] ?>" style="height: 380px; object-fit: cover; width: 100%;">
                            </a>
                        </div>
                        <div class="card-body text-center d-flex flex-column" style="padding: 25px;">
                            <h5 class="card-title" style="font-weight: 800; font-size: 1.15rem; color: #1e293b; margin-bottom: 15px;"><?= $row['tensp'] ?></h5>
                            <p class="card-text mb-4" style="font-weight: 700; font-size: 1.25rem; color: #2563eb;"><?= number_format($row['giasanpham'], 0, ',', '.') ?> đ</p>
                            <div class="mt-auto">
                                <a href="<?php echo BASE_URL; ?>shop/chitietsanpham.php?masp=<?= $row['masp'] ?>" class="btn-editorial-outline">XEM CHI TIẾT</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
            <div class="text-center mt-5">
                <a href="<?php echo BASE_URL; ?>shop/shop.php" class="btn-editorial-outline" style="padding: 14px 40px; font-size: 1.1rem; border-width: 2px;">XEM TẤT CẢ SẢN PHẨM &rarr;</a>
            </div>
        </div>
    </section>
    <style>
        .team-section {
            padding: 80px 0;
            background-color: #fff;
        }
        .team-member {
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .team-member:hover {
            box-shadow: 0 12px 40px rgba(0,0,0,0.1);
            transform: translateY(-6px);
            border-color: #cbd5e1;
        }
        .team-img-wrapper {
            position: relative;
            overflow: hidden;
        }
        .team-img-wrapper img {
            transition: transform 0.5s ease;
        }
        .team-member:hover .team-img-wrapper img {
            transform: scale(1.05);
        }
        .team-info {
            padding: 25px;
            background: #fff;
            text-align: center;
        }
        .team-info h4 {
            font-weight: 800;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-size: 1.2rem;
            color: #1e293b;
        }
        .team-info p {
            color: #94a3b8;
            font-weight: 600;
            letter-spacing: 1px;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
        .social-icons a {
            display: inline-flex;
            width: 38px;
            height: 38px;
            align-items: center;
            justify-content: center;
            background: #1e293b;
            color: #fff;
            border-radius: 50%;
            margin: 0 4px;
            transition: all 0.3s;
            text-decoration: none;
        }
        .social-icons a:hover {
            background: #3b82f6;
            transform: translateY(-3px);
        }
    </style>
    <section class="team-section">
        <div class="container">
            <div class="block-heading text-center mb-5">
                <h2 class="font-weight-bold text-uppercase" style="color: #1e293b; letter-spacing: 1.5px;">Đội ngũ của chúng tôi</h2>
                <p class="text-muted">Đam mê định hình phong cách cho thương hiệu.</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-sm-6 col-lg-4 mb-4">
                    <div class="card team-member">
                        <div class="team-img-wrapper">
                            <img class="card-img-top w-100 d-block" src="<?php echo BASE_URL; ?>images/imame2.png" alt="Nguyễn Văn Kiên" />
                        </div>
                        <div class="team-info">
                            <h4>Nguyễn Văn Kiên</h4>
                            <p>CHIEF EXECUTIVE OFFICER</p>
                            <div class="social-icons">
                                <a href="#"><i class="icon-social-facebook"></i></a>
                                <a href="#"><i class="icon-social-instagram"></i></a>
                                <a href="#"><i class="icon-social-twitter"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4 mb-4">
                    <div class="card team-member">
                        <div class="team-img-wrapper">
                            <img class="card-img-top w-100 d-block" src="<?php echo BASE_URL; ?>images/imame3.png" alt="Đỗ Quang Hà" />
                        </div>
                        <div class="team-info">
                            <h4>Đỗ Quang Hà</h4>
                            <p>BRAND MANAGER</p>
                            <div class="social-icons">
                                <a href="#"><i class="icon-social-facebook"></i></a>
                                <a href="#"><i class="icon-social-instagram"></i></a>
                                <a href="#"><i class="icon-social-twitter"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4 mb-4">
                    <div class="card team-member">
                        <div class="team-img-wrapper">
                            <img class="card-img-top w-100 d-block" src="<?php echo BASE_URL; ?>images/imame1.png" alt="Nguyễn Bá Nhân" />
                        </div>
                        <div class="team-info">
                            <h4>Nguyễn Bá Nhân</h4>
                            <p>HEAD OF OPERATIONS</p>
                            <div class="social-icons">
                                <a href="#"><i class="icon-social-facebook"></i></a>
                                <a href="#"><i class="icon-social-instagram"></i></a>
                                <a href="#"><i class="icon-social-twitter"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Responsive rules for homepage -->
    <style>
        @media (max-width: 768px) {
            .modern-caption {
                padding: 18px 20px;
                max-width: 280px;
                bottom: 8%;
                left: 4%;
                border-left-width: 4px;
            }
            .modern-caption h2 {
                font-size: 1.3rem;
                margin-bottom: 8px;
            }
            .modern-caption p {
                font-size: 0.85rem;
                margin-bottom: 15px;
            }
            .modern-btn {
                padding: 8px 25px;
                font-size: 0.8rem;
            }
            .carousel-item img {
                height: 350px !important;
            }
            .editorial-heading, .bestseller-heading {
                font-size: 1.6rem !important;
                letter-spacing: 1px;
            }
            .editorial-subheading, .bestseller-subheading {
                font-size: 1rem;
            }
            .product-highlight-title {
                font-size: 1.5rem;
            }
            .product-highlight-text {
                font-size: 0.95rem;
            }
            .btn-editorial {
                padding: 10px 25px;
                font-size: 0.95rem;
            }
            .editorial-section, .features-section, .bestseller-section, .team-section {
                padding: 50px 0;
            }
            .feature-box {
                padding: 25px 15px;
            }
            .team-info h4 {
                font-size: 1rem;
            }
        }
        @media (max-width: 480px) {
            .modern-caption {
                padding: 12px 14px;
                max-width: 220px;
                bottom: 5%;
            }
            .modern-caption h2 {
                font-size: 1rem;
                margin-bottom: 5px;
            }
            .modern-caption p {
                font-size: 0.75rem;
                margin-bottom: 10px;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            .modern-btn {
                padding: 6px 18px;
                font-size: 0.72rem;
            }
            .carousel-item img {
                height: 260px !important;
            }
            .editorial-heading, .bestseller-heading {
                font-size: 1.3rem !important;
            }
        }
    </style>

</main>


<?php include BASE_PATH . 'includes/footer.php'; ?>