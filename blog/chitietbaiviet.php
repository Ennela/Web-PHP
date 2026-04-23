<?php
require_once dirname(__DIR__) . '/config.php';
session_start();

if (isset($_GET['login']) && $_GET['login'] === 'dangxuat') {
    session_destroy();
    header('Location: baiviet.php');
    exit;
}

include BASE_PATH . 'includes/connect.php';

// Fix SQL injection: cast to int
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$result = mysqli_query($con, "SELECT * FROM `tbl_qlbaidang` WHERE `id` = $id LIMIT 1");
$post = mysqli_fetch_assoc($result);

// Edge case: bài viết không tồn tại
if (!$post) {
    include_once BASE_PATH . 'includes/header.php';
    ?>
    <main class="page" style="padding: 140px 0 80px; min-height: 60vh; text-align: center;">
        <div class="container">
            <i class="far fa-newspaper" style="font-size: 4rem; color: #ddd; margin-bottom: 16px; display: block;"></i>
            <h3 style="font-weight: 800; color: #333; margin-bottom: 10px;">Bài viết không tồn tại</h3>
            <p style="color: #888; margin-bottom: 24px;">Bài viết bạn đang tìm không tồn tại hoặc đã bị xóa.</p>
            <a href="<?php echo BASE_URL; ?>blog/baiviet.php" style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; padding: 12px 30px; border-radius: 10px; text-decoration: none; font-weight: 700; text-transform: uppercase; font-size: 0.85rem;">
                <i class="fas fa-arrow-left"></i> Quay lại Blog
            </a>
        </div>
    </main>
    <?php
    include BASE_PATH . 'includes/footer.php';
    exit;
}

include_once BASE_PATH . 'includes/header.php';
?>

<style>
/* ===== Blog Detail Page (MOBILE-FIRST) ===== */
.blog-detail-page {
    padding: clamp(90px, 12vw, 120px) 0 clamp(40px, 8vw, 80px);
    background: #f1f5f9;
    min-height: 100vh;
}

/* Breadcrumb */
.blog-breadcrumb {
    font-size: clamp(0.7rem, 1.5vw, 0.78rem);
    color: #999;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: clamp(20px, 4vw, 40px);
}
.blog-breadcrumb a {
    color: #999;
    text-decoration: none;
    transition: color 0.2s;
    min-height: 44px;
    display: inline-flex;
    align-items: center;
}
@media (hover: hover) {
    .blog-breadcrumb a:hover { color: #1e293b; }
}
.blog-breadcrumb span { margin: 0 8px; }

/* Article wrapper */
.article-wrapper {
    max-width: 820px;
    margin: 0 auto;
}

/* Hero image */
.article-hero {
    position: relative;
    border-radius: clamp(8px, 2vw, 12px);
    overflow: hidden;
    margin-bottom: clamp(20px, 4vw, 40px);
    box-shadow: 0 16px 48px rgba(0,0,0,0.12);
}
.article-hero img {
    width: 100%;
    height: clamp(250px, 50vw, 450px);
    object-fit: cover;
    display: block;
}
.article-hero-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(transparent, rgba(0,0,0,0.7));
    padding: clamp(20px, 4vw, 40px) clamp(15px, 3vw, 30px) clamp(15px, 3vw, 30px);
}

/* Meta */
.article-meta {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: clamp(20px, 4vw, 30px);
    padding-bottom: clamp(15px, 3vw, 20px);
    border-bottom: 2px solid #e2e8f0;
    flex-wrap: wrap; /* Prevent overflow */
}
.article-meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: clamp(0.75rem, 2vw, 0.85rem);
    color: #888;
    font-weight: 600;
}
.article-meta-item i {
    color: #3b82f6;
    font-size: clamp(0.9rem, 2vw, 1rem);
}

/* Title */
.article-title {
    font-size: clamp(1.4rem, 4vw, 2.4rem);
    font-weight: 900;
    color: #1e293b;
    line-height: 1.3;
    margin-bottom: clamp(10px, 2vw, 20px);
    letter-spacing: -0.5px;
    word-break: break-word; /* Prevent overflow */
}

/* Content */
.article-content {
    background: #fff;
    border-radius: clamp(10px, 2vw, 16px);
    padding: clamp(20px, 4vw, 40px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.08);
    border: 1px solid #e2e8f0;
    margin-bottom: 30px;
}
.article-body {
    font-size: clamp(0.95rem, 2vw, 1.05rem);
    color: #444;
    line-height: 1.9;
    margin-bottom: 30px;
    word-break: break-word; /* Prevent overflow */
}
.article-body p {
    margin-bottom: 16px;
}

/* Article images */
.article-images {
    display: grid;
    grid-template-columns: 1fr; /* Mobile first */
    gap: clamp(12px, 3vw, 20px);
    margin-top: 30px;
}
@media (min-width: 640px) {
    .article-images.two-cols {
        grid-template-columns: 1fr 1fr;
    }
}
.article-img-item {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
}
.article-img-item img {
    width: 100%;
    height: auto;
    display: block;
    transition: transform 0.5s ease;
}
@media (hover: hover) {
    .article-img-item:hover img {
        transform: scale(1.03);
    }
}

/* Back button */
.article-back {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: #fff;
    padding: clamp(10px, 2vw, 12px) clamp(20px, 4vw, 28px);
    border-radius: 10px;
    text-decoration: none;
    font-weight: 700;
    font-size: clamp(0.8rem, 2vw, 0.85rem);
    text-transform: uppercase;
    letter-spacing: 1px;
    transition: all 0.3s ease;
    min-height: 48px; /* Touch target */
}
@media (hover: hover) {
    .article-back:hover {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #fff;
        text-decoration: none;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(37, 99, 235, 0.3);
    }
}

/* Share section */
.article-share {
    display: flex;
    align-items: center;
    gap: 12px;
    padding-top: 24px;
    border-top: 2px solid #e2e8f0;
    flex-wrap: wrap; /* Prevent overflow */
}
.article-share span {
    font-size: clamp(0.75rem, 1.8vw, 0.82rem);
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #1e293b;
}
.share-btn {
    width: clamp(40px, 8vw, 44px);
    height: clamp(40px, 8vw, 44px);
    min-width: 44px; /* Touch target */
    min-height: 44px; /* Touch target */
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f0f0f0;
    color: #555;
    text-decoration: none;
    transition: all 0.3s;
}
@media (hover: hover) {
    .share-btn:hover {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: #fff;
    }
}

</style>

<main class="blog-detail-page">
    <div class="container">
        <!-- Breadcrumb -->
        <nav class="blog-breadcrumb">
            <a href="<?php echo BASE_URL; ?>home/trangchu.php">Trang chủ</a>
            <span>/</span>
            <a href="<?php echo BASE_URL; ?>blog/baiviet.php">Blog</a>
            <span>/</span>
            <span style="color:#1e293b; font-weight:700;"><?= htmlspecialchars(mb_substr($post['tieude'], 0, 40)) ?><?= mb_strlen($post['tieude']) > 40 ? '...' : '' ?></span>
        </nav>

        <div class="article-wrapper">
            <!-- Hero Image -->
            <div class="article-hero">
                <img src="<?php echo BASE_URL; ?>admin/<?= htmlspecialchars($post['anhdaidien']) ?>" 
                     alt="<?= htmlspecialchars($post['tieude']) ?>"
                     onerror="this.style.background='#eee'">
            </div>

            <!-- Title -->
            <h1 class="article-title"><?= htmlspecialchars($post['tieude']) ?></h1>

            <!-- Meta -->
            <div class="article-meta">
                <div class="article-meta-item">
                    <i class="far fa-calendar-alt"></i>
                    <?= date('d/m/Y H:i', $post['ngaycapnhat']) ?>
                </div>
                <div class="article-meta-item">
                    <i class="far fa-user"></i>
                    <?= htmlspecialchars($post['nguoiphutrach']) ?>
                </div>
            </div>

            <!-- Article Content -->
            <div class="article-content">
                <div class="article-body">
                    <?= nl2br(htmlspecialchars($post['noidung'])) ?>
                </div>

                <!-- Article Images -->
                <?php
                $img1 = $post['anhgiuoithieu1'] ?? '';
                $img2 = $post['anhgiuoithieu2'] ?? '';
                $hasImg1 = !empty($img1);
                $hasImg2 = !empty($img2);
                ?>
                <?php if ($hasImg1 || $hasImg2): ?>
                <div class="article-images <?= ($hasImg1 && $hasImg2) ? 'two-cols' : '' ?>">
                    <?php if ($hasImg1): ?>
                    <div class="article-img-item">
                        <img src="<?php echo BASE_URL; ?>admin/<?= htmlspecialchars($img1) ?>" 
                             alt="<?= htmlspecialchars($post['tieude']) ?>"
                             onerror="this.parentElement.style.display='none'">
                    </div>
                    <?php endif; ?>
                    <?php if ($hasImg2): ?>
                    <div class="article-img-item">
                        <img src="<?php echo BASE_URL; ?>admin/<?= htmlspecialchars($img2) ?>" 
                             alt="<?= htmlspecialchars($post['tieude']) ?>"
                             onerror="this.parentElement.style.display='none'">
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Share -->
                <div class="article-share" style="margin-top: 30px;">
                    <span>Chia sẻ:</span>
                    <a href="#" class="share-btn" title="Facebook" onclick="window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(window.location.href), '_blank', 'width=600,height=400'); return false;">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="share-btn" title="Twitter" onclick="window.open('https://twitter.com/intent/tweet?url=' + encodeURIComponent(window.location.href), '_blank', 'width=600,height=400'); return false;">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="share-btn" title="Copy Link" onclick="navigator.clipboard.writeText(window.location.href); alert('Đã copy link!'); return false;">
                        <i class="fas fa-link"></i>
                    </a>
                </div>
            </div>

            <!-- Back Button -->
            <div class="mt-4">
                <a href="<?php echo BASE_URL; ?>blog/baiviet.php" class="article-back">
                    <i class="fas fa-arrow-left"></i> Quay lại danh sách bài viết
                </a>
            </div>
        </div>
    </div>
</main>

<?php include BASE_PATH . 'includes/footer.php'; ?>