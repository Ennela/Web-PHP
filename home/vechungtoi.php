<?php
require_once dirname(__DIR__) . '/config.php';
session_start();

if (isset($_GET['login']) && $_GET['login'] === 'dangxuat') {
    session_destroy();
    header('Location: vechungtoi.php');
    exit;
}

include BASE_PATH . 'includes/connect.php';

// Lấy số liệu thống kê từ DB
$countProducts = 0;
$countPosts = 0;
$countMembers = 0;

$r = mysqli_query($con, "SELECT COUNT(*) as cnt FROM `tbl_qlsanpham`");
if ($r) { $countProducts = mysqli_fetch_assoc($r)['cnt']; }

$r = mysqli_query($con, "SELECT COUNT(*) as cnt FROM `tbl_qlbaidang` WHERE `chedo` = 'Hiện'");
if ($r) { $countPosts = mysqli_fetch_assoc($r)['cnt']; }

$r = mysqli_query($con, "SELECT COUNT(*) as cnt FROM `tbl_qlthanhvien`");
if ($r) { $countMembers = mysqli_fetch_assoc($r)['cnt']; }

include BASE_PATH . 'includes/header.php';
?>

<style>
    /* ===== About Page ===== */
    .about-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
        color: #fff;
        padding: 160px 0 100px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .about-hero::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle at 30% 40%, rgba(59, 130, 246, 0.08) 0%, transparent 50%),
                    radial-gradient(circle at 70% 80%, rgba(139, 92, 246, 0.06) 0%, transparent 50%);
        animation: heroFloat 20s ease-in-out infinite;
    }
    @keyframes heroFloat {
        0%, 100% { transform: translate(0, 0); }
        50% { transform: translate(-20px, -10px); }
    }
    .about-hero h1 {
        font-family: 'Montserrat', sans-serif;
        font-weight: 900;
        letter-spacing: 3px;
        text-transform: uppercase;
        margin-bottom: 20px;
        font-size: 3.2rem;
        position: relative;
        z-index: 1;
    }
    .about-hero p {
        font-size: 1.15rem;
        max-width: 700px;
        margin: 0 auto;
        color: #94a3b8;
        line-height: 1.7;
        position: relative;
        z-index: 1;
    }
    .hero-divider {
        width: 60px;
        height: 4px;
        background: #3b82f6;
        margin: 24px auto;
        border-radius: 2px;
        position: relative;
        z-index: 1;
    }

    /* Stats Section */
    .stats-section {
        background: #1e293b;
        padding: 50px 0;
        margin-top: -1px;
    }
    .stat-item {
        text-align: center;
        padding: 20px;
    }
    .stat-number {
        font-family: 'Montserrat', sans-serif;
        font-size: 3rem;
        font-weight: 900;
        color: #fff;
        line-height: 1;
        margin-bottom: 8px;
    }
    .stat-label {
        font-size: 0.82rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: #64748b;
    }

    /* Values Section */
    .about-values {
        padding: 80px 0;
        background-color: #f1f5f9;
    }
    .section-heading {
        text-align: center;
        margin-bottom: 50px;
    }
    .section-heading h2 {
        font-family: 'Montserrat', sans-serif;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: #0f172a;
        font-size: 2.2rem;
        margin-bottom: 10px;
    }
    .section-heading p {
        color: #94a3b8;
        font-size: 1rem;
        max-width: 500px;
        margin: 0 auto;
    }
    .value-box {
        text-align: center;
        padding: 40px 30px;
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        transition: all 0.4s ease;
        height: 100%;
    }
    .value-box:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
        border-color: #cbd5e1;
    }
    .value-icon {
        width: 70px;
        height: 70px;
        line-height: 70px;
        font-size: 28px;
        color: #fff;
        background: #1e293b;
        border-radius: 50%;
        margin: 0 auto 24px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .value-box h4 {
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 12px;
        font-size: 1.15rem;
    }
    .value-box p {
        color: #64748b;
        line-height: 1.7;
        font-size: 0.92rem;
    }

    /* Team Section */
    .team-section {
        padding: 80px 0;
        background: #fff;
    }
    .team-member {
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        overflow: hidden;
        transition: all 0.4s ease;
    }
    .team-member:hover {
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
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
        padding: 28px;
        background: #fff;
        text-align: center;
    }
    .team-info h4 {
        font-weight: 800;
        margin-bottom: 6px;
        text-transform: uppercase;
        font-size: 1.15rem;
        color: #1e293b;
    }
    .team-info p {
        color: #94a3b8;
        font-weight: 700;
        letter-spacing: 1.5px;
        margin-bottom: 16px;
        font-size: 0.78rem;
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

    /* CTA Section */
    .cta-section {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        padding: 80px 0;
        text-align: center;
        color: #fff;
        position: relative;
        overflow: hidden;
    }
    .cta-section::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle at 50% 50%, rgba(59, 130, 246, 0.08) 0%, transparent 50%);
    }
    .cta-section h2 {
        font-family: 'Montserrat', sans-serif;
        font-weight: 900;
        font-size: 2.2rem;
        text-transform: uppercase;
        letter-spacing: 2px;
        margin-bottom: 16px;
        position: relative;
        z-index: 1;
    }
    .cta-section p {
        color: #94a3b8;
        font-size: 1.05rem;
        max-width: 500px;
        margin: 0 auto 30px;
        position: relative;
        z-index: 1;
    }
    .btn-cta {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: #fff;
        padding: 14px 36px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 800;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: all 0.3s ease;
        position: relative;
        z-index: 1;
    }
    .btn-cta:hover {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #fff;
        text-decoration: none;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
    }

    /* Scroll Animations */
    .fade-in-up {
        opacity: 0;
        transform: translateY(30px);
        transition: opacity 0.7s ease, transform 0.7s ease;
    }
    .fade-in-up.visible {
        opacity: 1;
        transform: translateY(0);
    }

    @media (max-width: 768px) {
        .about-hero { padding: 120px 0 60px; }
        .about-hero h1 { font-size: 2rem; }
        .stat-number { font-size: 2rem; }
    }
</style>

<main class="page">
    <!-- UC2: Thông tin thương hiệu -->
    <section class="about-hero">
        <div class="container">
            <h1>Câu chuyện của chúng tôi</h1>
            <div class="hero-divider"></div>
            <p>Không chỉ là một cửa hàng bán giày, chúng tôi là nơi đam mê văn hóa sát mặt đất hội tụ. Chúng tôi mang
                đến những dòng sneaker thời thượng nhất, từ những huyền thoại cổ điển đến những siêu phẩm giới hạn.</p>
        </div>
    </section>

    <!-- Stats Counter -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-6 col-md-3">
                    <div class="stat-item fade-in-up">
                        <div class="stat-number" data-count="<?= $countProducts ?>">0</div>
                        <div class="stat-label">Sản phẩm</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-item fade-in-up">
                        <div class="stat-number" data-count="500">0</div>
                        <div class="stat-label">Khách hàng</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-item fade-in-up">
                        <div class="stat-number" data-count="<?= $countPosts ?>">0</div>
                        <div class="stat-label">Bài viết</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-item fade-in-up">
                        <div class="stat-number" data-count="<?= $countMembers ?>">0</div>
                        <div class="stat-label">Thành viên</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Values -->
    <section class="about-values">
        <div class="container">
            <div class="section-heading fade-in-up">
                <h2>Giá trị cốt lõi</h2>
                <p>Những cam kết không thay đổi trong hành trình phát triển của chúng tôi.</p>
            </div>
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <div class="value-box fade-in-up">
                        <div class="value-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4>100% Chính hãng</h4>
                        <p>Mọi sản phẩm đều trải qua quy trình kiểm định nghiêm ngặt để đảm bảo tính
                            xác thực tuyệt đối.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <div class="value-box fade-in-up">
                        <div class="value-icon">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <h4>Cập nhật thần tốc</h4>
                        <p>Tiên phong mang những xu hướng streetwear mới nhất trên thế giới về tay
                            bạn sớm nhất.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="value-box fade-in-up">
                        <div class="value-icon">
                            <i class="fas fa-headphones-alt"></i>
                        </div>
                        <h4>Hỗ trợ tận tâm</h4>
                        <p>Đội ngũ dịch vụ khách hàng chuyên nghiệp, sẵn sàng giải đáp và tư vấn
                            style 24/7.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- UC3: Thông tin thành viên -->
    <section class="team-section">
        <div class="container">
            <div class="section-heading fade-in-up">
                <h2>Đội ngũ của chúng tôi</h2>
                <p>Những người đứng sau sự thành công và định hình phong cách cho thương hiệu.</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-sm-6 col-lg-4 mb-4">
                    <div class="card team-member fade-in-up">
                        <div class="team-img-wrapper">
                            <img class="card-img-top w-100 d-block" src="<?php echo BASE_URL; ?>images/imame2.png"
                                alt="Nguyễn Văn Kiên" style="height: 350px; object-fit: cover;" />
                        </div>
                        <div class="team-info">
                            <h4>Nguyễn Văn Kiên</h4>
                            <p>CHIEF EXECUTIVE OFFICER</p>
                            <div class="social-icons">
                                <a href="#"><i class="fab fa-facebook-f"></i></a>
                                <a href="#"><i class="fab fa-instagram"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4 mb-4">
                    <div class="card team-member fade-in-up">
                        <div class="team-img-wrapper">
                            <img class="card-img-top w-100 d-block" src="<?php echo BASE_URL; ?>images/imame3.png"
                                alt="Đỗ Quang Hà" style="height: 350px; object-fit: cover;" />
                        </div>
                        <div class="team-info">
                            <h4>Đỗ Quang Hà</h4>
                            <p>BRAND MANAGER</p>
                            <div class="social-icons">
                                <a href="#"><i class="fab fa-facebook-f"></i></a>
                                <a href="#"><i class="fab fa-instagram"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4 mb-4">
                    <div class="card team-member fade-in-up">
                        <div class="team-img-wrapper">
                            <img class="card-img-top w-100 d-block" src="<?php echo BASE_URL; ?>images/imame1.png"
                                alt="Nguyễn Bá Nhân" style="height: 350px; object-fit: cover;" />
                        </div>
                        <div class="team-info">
                            <h4>Nguyễn Bá Nhân</h4>
                            <p>HEAD OF OPERATIONS</p>
                            <div class="social-icons">
                                <a href="#"><i class="fab fa-facebook-f"></i></a>
                                <a href="#"><i class="fab fa-instagram"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <div class="container fade-in-up">
            <h2>Sẵn sàng khám phá?</h2>
            <p>Ghé thăm cửa hàng của chúng tôi để tìm đôi giày hoàn hảo cho phong cách của bạn.</p>
            <a href="<?php echo BASE_URL; ?>shop/shop.php" class="btn-cta">
                <i class="fas fa-shopping-bag"></i> Khám phá ngay
            </a>
        </div>
    </section>
</main>

<script>
// === Scroll Animations ===
(function() {
    const fadeEls = document.querySelectorAll('.fade-in-up');
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });

    fadeEls.forEach(function(el) {
        observer.observe(el);
    });

    // === Counter Animation ===
    const counters = document.querySelectorAll('.stat-number[data-count]');
    
    const counterObserver = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                const el = entry.target;
                const target = parseInt(el.getAttribute('data-count'));
                let current = 0;
                const increment = Math.ceil(target / 60);
                const timer = setInterval(function() {
                    current += increment;
                    if (current >= target) {
                        el.textContent = target + '+';
                        clearInterval(timer);
                    } else {
                        el.textContent = current;
                    }
                }, 25);
                counterObserver.unobserve(el);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(function(c) {
        counterObserver.observe(c);
    });
})();
</script>

<?php include BASE_PATH . 'includes/footer.php'; ?>