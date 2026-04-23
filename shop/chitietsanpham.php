<?php
require_once dirname(__DIR__) . '/config.php';
    session_start();
   
    if (isset($_GET['login'])) {
        $dangxuat = $_GET['login'];
    } else {
        $dangxuat = '';
    }
    if ($dangxuat == 'dangxuat') {
        session_destroy();
        header('Location: shop.php');
    }
    include BASE_PATH . 'includes/connect.php';
    $result = mysqli_query($con, "SELECT * FROM `tbl_qlsanpham` WHERE `masp` = ".(int)$_GET['masp']);
    $product = mysqli_fetch_assoc($result);
    include_once BASE_PATH . 'includes/header.php';
?>

<style>
/* ===== PRODUCT DETAIL PAGE ===== */
.product-detail-page {
    padding: 130px 0 80px;
    background: #f1f5f9;
    min-height: 100vh;
}

/* Breadcrumb */
.product-breadcrumb {
    font-size: 0.8rem;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 40px;
}
.product-breadcrumb a {
    color: #999;
    text-decoration: none;
    transition: color 0.2s;
}
.product-breadcrumb a:hover { color: #1e293b; }
.product-breadcrumb span { margin: 0 8px; }

/* ===== IMAGE GALLERY ===== */
.product-gallery {
    position: sticky;
    top: 100px;
}

.gallery-main {
    position: relative;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
    aspect-ratio: 1 / 1;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: crosshair;
}

.gallery-main img#main-product-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 30px;
    transition: opacity 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    pointer-events: none;
}

.gallery-main img#main-product-img.img-switching {
    opacity: 0;
}

/* ===== INLINE ZOOM LAYER ===== */
.zoom-layer {
    position: absolute;
    inset: 0;
    background-repeat: no-repeat;
    background-size: 250%;
    opacity: 0;
    transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    pointer-events: none;
    z-index: 5;
    border-radius: 4px;
}

.gallery-main:hover .zoom-layer {
    opacity: 1;
}

.gallery-main:hover img#main-product-img {
    opacity: 0;
}

/* Zoom lens cursor follower */
.zoom-cursor {
    position: absolute;
    width: 120px;
    height: 120px;
    border: 2px solid rgba(30,41,59,0.25);
    border-radius: 50%;
    pointer-events: none;
    z-index: 6;
    opacity: 0;
    transition: opacity 0.25s ease, transform 0.08s linear;
    box-shadow: 0 0 0 1px rgba(255,255,255,0.5),
                0 8px 32px rgba(0,0,0,0.15);
    backdrop-filter: blur(0.5px);
    transform: translate(-50%, -50%);
}

.gallery-main:hover .zoom-cursor {
    opacity: 1;
}

/* Zoom hint overlay */
.gallery-zoom-hint {
    position: absolute;
    bottom: 12px;
    right: 12px;
    background: rgba(0,0,0,0.6);
    color: #fff;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    padding: 6px 14px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    gap: 5px;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 7;
}
.gallery-main:not(:hover) .gallery-zoom-hint { opacity: 0; }
.gallery-main .gallery-zoom-hint.show { opacity: 1; }

/* ---- LIGHTBOX ---- */
.lightbox-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.92);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: zoom-out;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.35s cubic-bezier(0.4, 0, 0.2, 1),
                visibility 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(8px);
}

.lightbox-overlay.open {
    opacity: 1;
    visibility: visible;
}

.lightbox-img-wrap {
    position: relative;
    max-width: 90vw;
    max-height: 90vh;
    cursor: default;
    transform: scale(0.88) translateY(20px);
    opacity: 0;
    transition: transform 0.45s cubic-bezier(0.16, 1, 0.3, 1),
                opacity 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}

.lightbox-overlay.open .lightbox-img-wrap {
    transform: scale(1) translateY(0);
    opacity: 1;
}

.lightbox-img-wrap img {
    max-width: 90vw;
    max-height: 90vh;
    object-fit: contain;
    border-radius: 6px;
    display: block;
    transition: opacity 0.3s ease;
}

.lightbox-close {
    position: absolute;
    top: -42px;
    right: 0;
    background: none;
    border: none;
    color: #fff;
    font-size: 2rem;
    cursor: pointer;
    line-height: 1;
    opacity: 0.8;
    transition: opacity 0.2s, transform 0.2s;
}
.lightbox-close:hover { opacity: 1; transform: scale(1.15); }

.lightbox-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255,255,255,0.15);
    border: none;
    color: #fff;
    font-size: 1.8rem;
    width: 46px;
    height: 46px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
    transition: background 0.2s;
}
.lightbox-nav:hover { background: rgba(255,255,255,0.3); }
.lightbox-prev { left: -60px; }
.lightbox-next { right: -60px; }

.gallery-badge {
    position: absolute;
    top: 16px;
    left: 16px;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: #fff;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 2px;
    text-transform: uppercase;
    padding: 5px 12px;
    border-radius: 50px;
}

/* Thumbnails */
.gallery-thumbs {
    display: flex;
    gap: 10px;
    margin-top: 14px;
}

.thumb-item {
    flex: 1;
    aspect-ratio: 1 / 1;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    cursor: pointer;
    transition: border-color 0.2s, transform 0.2s;
    background: #fff;
}

.thumb-item:hover {
    border-color: #3b82f6;
    transform: translateY(-2px);
}

.thumb-item.active {
    border-color: #3b82f6;
}

.thumb-item img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 6px;
}

/* ===== PRODUCT INFO ===== */
.product-info-panel {
    padding-left: 20px;
}

.product-category-tag {
    display: inline-block;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 3px;
    color: #fff;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    padding: 4px 14px;
    border-radius: 50px;
    margin-bottom: 18px;
}

.product-name {
    font-size: 2.4rem;
    font-weight: 900;
    color: #1e293b;
    line-height: 1.15;
    letter-spacing: -0.5px;
    margin-bottom: 16px;
    text-transform: uppercase;
}

/* Rating */
.star-rating {
    display: flex;
    align-items: center;
    gap: 3px;
    margin-bottom: 20px;
}
.star { color: #f5a623; font-size: 1rem; }
.star.empty { color: #ddd; }
.rating-text {
    font-size: 0.82rem;
    color: #888;
    margin-left: 8px;
}

/* Price */
.price-block {
    display: flex;
    align-items: baseline;
    gap: 14px;
    margin-bottom: 24px;
    padding: 20px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
}

.price-sale {
    font-size: 2.4rem;
    font-weight: 900;
    color: #2563eb;
    line-height: 1;
}

.price-original {
    font-size: 1.1rem;
    color: #bbb;
    text-decoration: line-through;
    font-weight: 500;
}

.price-discount-badge {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: #fff;
    font-size: 0.72rem;
    font-weight: 800;
    padding: 3px 10px;
    border-radius: 20px;
    letter-spacing: 0.5px;
    margin-left: 4px;
}

/* Description */
.product-desc {
    font-size: 0.97rem;
    color: #555;
    line-height: 1.85;
    margin-bottom: 28px;
    padding-bottom: 24px;
    border-bottom: 1px solid #eee;
}



/* Size Selector */
.size-selector-block {
    margin-bottom: 24px;
    padding: 20px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
}

.size-selector-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}

.size-label {
    font-size: 0.8rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: #1e293b;
}

.size-guide-link {
    font-size: 0.78rem;
    color: #888;
    text-decoration: underline;
    font-weight: 600;
    transition: color 0.2s;
}
.size-guide-link:hover { color: #3b82f6; }

.size-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.size-btn {
    width: 50px;
    height: 50px;
    border: 2px solid #ddd;
    border-radius: 4px;
    background: #fff;
    color: #333;
    font-size: 0.88rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.18s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.size-btn:hover {
    border-color: #3b82f6;
    color: #3b82f6;
    background: #eff6ff;
}

.size-btn.active {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: #fff;
    border-color: #3b82f6;
    transform: scale(1.05);
}

.size-error {
    margin-top: 10px;
    font-size: 0.82rem;
    color: #ef4444;
    font-weight: 600;
}

/* Add to cart form */
.add-to-cart-section {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 20px;
}

.qty-label {
    font-size: 0.8rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: #1e293b;
    margin-bottom: 12px;
}

.qty-control {
    display: flex;
    align-items: center;
    gap: 0;
    border: 2px solid #3b82f6;
    border-radius: 10px;
    overflow: hidden;
    width: fit-content;
    margin-bottom: 20px;
}

.qty-btn {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: #fff;
    border: none;
    width: 42px;
    height: 42px;
    font-size: 1.3rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
}

.qty-btn:hover { background: linear-gradient(135deg, #2563eb, #1d4ed8); }

.qty-input {
    width: 60px;
    height: 42px;
    border: none;
    border-left: 2px solid #3b82f6;
    border-right: 2px solid #3b82f6;
    text-align: center;
    font-size: 1.1rem;
    font-weight: 800;
    color: #1e293b;
    outline: none;
}

.btn-add-cart {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 16px 30px;
    font-size: 0.9rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 2px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-add-cart:hover {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(37, 99, 235, 0.35);
}

/* Trust badges */
.trust-badges {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.trust-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.78rem;
    color: #666;
    font-weight: 600;
}

.trust-badge-icon { font-size: 1rem; }

/* ===== TABS SECTION ===== */
.product-tabs-section {
    margin-top: 60px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
}

.product-tabs-nav {
    display: flex;
    border-bottom: 2px solid #eee;
    background: #fff;
}

.tab-btn {
    padding: 16px 30px;
    font-size: 0.82rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: #888;
    background: none;
    border: none;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s;
}

.tab-btn.active {
    color: #2563eb;
    border-bottom-color: #2563eb;
}

.tab-btn:hover { color: #3b82f6; }

.tab-content-panel {
    padding: 36px;
    display: none;
}

.tab-content-panel.active { display: block; }

.tab-description {
    font-size: 0.97rem;
    color: #555;
    line-height: 1.85;
    margin-bottom: 30px;
}

/* Description images */
.desc-images-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-top: 24px;
}

.desc-img-item {
    aspect-ratio: 4/3;
    border-radius: 4px;
    overflow: hidden;
    border: 1px solid #eee;
}

.desc-img-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.desc-img-item:hover img { transform: scale(1.04); }

/* Reviews */
.review-card {
    padding: 24px 0;
    border-bottom: 1px solid #f0f0f0;
}

.review-card:last-child { border-bottom: none; }

.review-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 10px;
}

.reviewer-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 1rem;
    flex-shrink: 0;
}

.reviewer-name {
    font-weight: 800;
    font-size: 0.9rem;
    color: #1e293b;
}

.reviewer-date {
    font-size: 0.78rem;
    color: #aaa;
}

.review-text {
    font-size: 0.92rem;
    color: #555;
    line-height: 1.75;
    margin-top: 8px;
}

/* ===== RELATED PRODUCTS ===== */
.related-section {
    margin-top: 70px;
}

.section-label {
    display: inline-block;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 4px;
    color: #999;
    margin-bottom: 6px;
}

.section-title {
    font-size: 2rem;
    font-weight: 900;
    color: #1e293b;
    text-transform: uppercase;
    letter-spacing: -0.5px;
    margin-bottom: 32px;
    padding-bottom: 16px;
    border-bottom: 3px solid #3b82f6;
}

.related-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s ease;
    text-decoration: none;
    display: block;
}

.related-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.1);
    border-color: #cbd5e1;
}

.related-img-wrap {
    aspect-ratio: 1 / 1;
    background: #f8f8f8;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.related-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 20px;
    transition: transform 0.4s ease;
}

.related-card:hover .related-img-wrap img {
    transform: scale(1.06);
}

.related-info {
    padding: 16px;
    border-top: 1px solid #f0f0f0;
}

.related-name {
    font-size: 0.9rem;
    font-weight: 800;
    color: #1e293b;
    line-height: 1.3;
    margin-bottom: 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.related-price {
    font-size: 1rem;
    font-weight: 900;
    color: #2563eb;
}

.related-stars {
    display: flex;
    gap: 2px;
    margin-bottom: 6px;
}
.related-stars span { color: #f5a623; font-size: 0.75rem; }

/* ===== PRODUCT DETAIL RESPONSIVE ===== */
@media (max-width: 768px) {
    .product-detail-page {
        padding: 100px 0 50px;
    }
    .product-gallery {
        position: static;
    }
    .product-name {
        font-size: 1.6rem;
    }
    .price-sale {
        font-size: 1.8rem;
    }
    .price-block {
        padding: 14px;
    }
    .product-info-panel {
        padding-left: 0;
        margin-top: 20px;
    }
    .tab-btn {
        padding: 12px 16px;
        font-size: 0.75rem;
        letter-spacing: 1px;
    }
    .tab-content-panel {
        padding: 20px;
    }
    .product-tabs-section {
        margin-top: 40px;
    }
    .desc-images-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    .related-section {
        margin-top: 40px;
    }
    .section-title {
        font-size: 1.5rem;
    }
    .lightbox-prev { left: -30px; }
    .lightbox-next { right: -30px; }
    .gallery-zoom-hint { display: none; }
    .zoom-layer { display: none; }
    .zoom-cursor { display: none; }
}
@media (max-width: 576px) {
    .product-detail-page {
        padding: 90px 0 40px;
    }
    .product-name {
        font-size: 1.3rem;
    }
    .price-sale {
        font-size: 1.5rem;
    }
    .tab-btn {
        padding: 10px 12px;
        font-size: 0.7rem;
    }
    .tab-content-panel {
        padding: 15px;
    }
    .desc-images-grid {
        grid-template-columns: 1fr;
    }
    .product-breadcrumb {
        font-size: 0.7rem;
        margin-bottom: 20px;
    }
    .size-btn {
        width: 42px;
        height: 42px;
        font-size: 0.8rem;
    }
    .lightbox-prev { left: 10px; }
    .lightbox-next { right: 10px; }
}
</style>

<main class="product-detail-page">
    <div class="container">

        <!-- Breadcrumb -->
        <nav class="product-breadcrumb">
            <a href="<?php echo BASE_URL; ?>home/trangchu.php">Trang chủ</a>
            <span>/</span>
            <a href="<?php echo BASE_URL; ?>shop/shop.php">Cửa hàng</a>
            <span>/</span>
            <span style="color:#1e293b; font-weight:700;"><?= htmlspecialchars($product['tensp']) ?></span>
        </nav>

        <!-- Main Product Block -->
        <div class="row g-5">

            <!-- LEFT: Image Gallery -->
            <div class="col-lg-6">
                <div class="product-gallery">
                    <!-- Main Image -->
                    <div class="gallery-main" id="gallery-main">
                        <?php if(!empty($product['nhomsp'])): ?>
                        <div class="gallery-badge"><?= htmlspecialchars($product['nhomsp']) ?></div>
                        <?php endif; ?>
                        <img
                            id="main-product-img"
                            src="<?php echo BASE_URL; ?>admin/<?= htmlspecialchars($product['anhdaidien']) ?>"
                            alt="<?= htmlspecialchars($product['tensp']) ?>"
                            onerror="this.src='<?php echo BASE_URL; ?>assets/img/no-image.png'"
                        >
                        <!-- Zoom magnification layer -->
                        <div class="zoom-layer" id="zoom-layer"></div>
                        <!-- Cursor lens indicator -->
                        <div class="zoom-cursor" id="zoom-cursor"></div>
                        <div class="gallery-zoom-hint" id="zoom-hint">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                            Di chuột để phóng to · Click để toàn màn hình
                        </div>
                    </div>

                    <!-- Thumbnails -->
                    <div class="gallery-thumbs" id="gallery-thumbs">
                        <?php
                        $images = [
                            $product['anhdaidien'],
                            $product['anhgiuoithieu1'],
                            $product['anhgiuoithieu2'],
                        ];
                        foreach ($images as $i => $img):
                            if (empty($img)) continue;
                        ?>
                        <div class="thumb-item <?= $i === 0 ? 'active' : '' ?>"
                             onclick="switchImage(this, '<?php echo BASE_URL; ?>admin/<?= htmlspecialchars($img) ?>')">
                            <img src="<?php echo BASE_URL; ?>admin/<?= htmlspecialchars($img) ?>"
                                 alt="Ảnh <?= $i+1 ?>"
                                 onerror="this.parentElement.style.display='none'">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Product Info -->
            <div class="col-lg-6">
                <div class="product-info-panel">

                    <?php if(!empty($product['nhomsp'])): ?>
                    <div class="product-category-tag"><?= htmlspecialchars($product['nhomsp']) ?></div>
                    <?php endif; ?>

                    <h1 class="product-name"><?= htmlspecialchars($product['tensp']) ?></h1>

                    <!-- Star Rating -->
                    <div class="star-rating">
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star empty">★</span>
                        <span class="rating-text">(12 đánh giá)</span>
                    </div>

                    <!-- Price -->
                    <div class="price-block">
                        <?php if(!empty($product['giagoc']) && $product['giagoc'] > $product['giasanpham']): ?>
                        <span class="price-original"><?= number_format($product['giagoc'], 0, ',', '.') ?>₫</span>
                        <?php
                            $discount = round((1 - $product['giasanpham'] / $product['giagoc']) * 100);
                        ?>
                        <?php endif; ?>
                        <span class="price-sale"><?= number_format($product['giasanpham'], 0, ',', '.') ?>₫</span>
                        <?php if(!empty($discount) && $discount > 0): ?>
                        <span class="price-discount-badge">-<?= $discount ?>%</span>
                        <?php endif; ?>
                    </div>

                    <!-- Short Description -->
                    <?php if(!empty($product['noidung'])): ?>
                    <div class="product-desc">
                        <?= nl2br(htmlspecialchars(mb_substr($product['noidung'], 0, 200))) ?>
                        <?= mb_strlen($product['noidung']) > 200 ? '...' : '' ?>
                    </div>
                    <?php endif; ?>

                    <!-- Size Selector -->
                    <div class="size-selector-block">
                        <div class="size-selector-header">
                            <span class="size-label">Chọn size</span>
                            <a href="#" class="size-guide-link" onclick="return false;">Hướng dẫn chọn size ↗</a>
                        </div>
                        <div class="size-grid" id="size-grid">
                            <?php
                            $sizes = [36, 37, 38, 39, 40, 41, 42, 43, 44];
                            foreach ($sizes as $s): ?>
                            <button type="button" class="size-btn" onclick="selectSize(this, <?= $s ?>)"><?= $s ?></button>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="size" id="selected-size" value="">
                        <p class="size-error" id="size-error" style="display:none;">⚠ Vui lòng chọn size trước khi thêm vào giỏ</p>
                    </div>

                    <!-- Add to Cart -->
                    <form action="<?php echo BASE_URL; ?>shop/giohang.php?action=add" method="POST" onsubmit="return validateSize()">
                        <input type="hidden" name="size[<?= $product['masp'] ?>]" id="form-size" value="">
                        <div class="add-to-cart-section">
                            <div class="qty-label">Số lượng</div>
                            <div class="qty-control">
                                <button type="button" class="qty-btn" onclick="changeQty(-1)">−</button>
                                <input class="qty-input" type="number" value="1" min="1" max="99"
                                       name="quantity[<?= $product['masp'] ?>]" id="qty-field">
                                <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
                            </div>
                            <button class="btn-add-cart" type="submit">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                                </svg>
                                Thêm vào giỏ hàng
                            </button>
                        </div>
                    </form>

                    <!-- Trust Badges -->
                    <div class="trust-badges">
                        <div class="trust-badge">
                            <span class="trust-badge-icon">🛡️</span>
                            Bảo hành 12 tháng
                        </div>
                        <div class="trust-badge">
                            <span class="trust-badge-icon">⚡</span>
                            Giao trong 2-3 ngày
                        </div>
                        <div class="trust-badge">
                            <span class="trust-badge-icon">💳</span>
                            Hỗ trợ trả góp
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Tabs: Description & Reviews -->
        <div class="product-tabs-section">
            <div class="product-tabs-nav">
                <button class="tab-btn active" onclick="switchTab(this, 'tab-desc')">Mô tả sản phẩm</button>
                <button class="tab-btn" onclick="switchTab(this, 'tab-reviews')">Đánh giá (3)</button>
            </div>

            <!-- Description Tab -->
            <div class="tab-content-panel active" id="tab-desc">
                <p class="tab-description"><?= nl2br(htmlspecialchars($product['noidung'])) ?></p>

                <!-- Image Grid -->
                <div class="desc-images-grid">
                    <?php
                    $descImages = array_filter([
                        $product['anhdaidien'],
                        $product['anhgiuoithieu1'],
                        $product['anhgiuoithieu2'],
                    ]);
                    foreach ($descImages as $img):
                    ?>
                    <div class="desc-img-item">
                        <img src="<?php echo BASE_URL; ?>admin/<?= htmlspecialchars($img) ?>"
                             alt="<?= htmlspecialchars($product['tensp']) ?>"
                             onerror="this.parentElement.style.display='none'">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Reviews Tab -->
            <div class="tab-content-panel" id="tab-reviews">
                <?php
                $reviews = [
                    ['name' => 'Nguyễn Văn A', 'date' => '12 Mar 2024', 'stars' => 5, 'text' => 'Sản phẩm rất chất lượng, đúng như mô tả. Đế giày êm ái, phần upper chắc chắn. Mình rất hài lòng với lần mua này!'],
                    ['name' => 'Trần Thị B', 'date' => '28 Feb 2024', 'stars' => 4, 'text' => 'Giày đẹp, giao hàng nhanh. Chỉ tiếc là size hơi rộng hơn dự kiến một chút, nhưng vẫn dùng được.'],
                    ['name' => 'Lê Minh C', 'date' => '15 Jan 2024', 'stars' => 5, 'text' => 'Mua lần thứ 3 rồi, chưa bao giờ thất vọng. Shop uy tín, hàng đẹp đúng chuẩn. Sẽ tiếp tục ủng hộ.'],
                ];
                foreach ($reviews as $rv): ?>
                <div class="review-card">
                    <div class="review-header">
                        <div class="reviewer-avatar"><?= mb_substr($rv['name'], 0, 1) ?></div>
                        <div>
                            <div class="reviewer-name"><?= $rv['name'] ?></div>
                            <div class="reviewer-date"><?= $rv['date'] ?></div>
                        </div>
                        <div class="star-rating ms-auto">
                            <?php for($s=1;$s<=5;$s++): ?>
                            <span class="star <?= $s > $rv['stars'] ? 'empty' : '' ?>">★</span>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <p class="review-text"><?= $rv['text'] ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Related Products -->
        <div class="related-section">
            <span class="section-label">Có thể bạn thích</span>
            <h2 class="section-title">Sản phẩm liên quan</h2>
            <div class="row g-4">
                <?php
                $related_query = mysqli_query($con, "SELECT * FROM `tbl_qlsanpham` WHERE `masp` != " . (int)$_GET['masp'] . " ORDER BY RAND() LIMIT 4");
                if ($related_query) {
                    while ($related = mysqli_fetch_assoc($related_query)) {
                ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="chitietsanpham.php?masp=<?= $related['masp'] ?>" class="related-card">
                        <div class="related-img-wrap">
                            <img src="<?php echo BASE_URL; ?>admin/<?= htmlspecialchars($related['anhdaidien']) ?>"
                                 alt="<?= htmlspecialchars($related['tensp']) ?>"
                                 onerror="this.src='<?php echo BASE_URL; ?>assets/img/no-image.png'">
                        </div>
                        <div class="related-info">
                            <div class="related-stars">
                                <span>★</span><span>★</span><span>★</span><span>★</span><span style="color:#ddd;">★</span>
                            </div>
                            <div class="related-name"><?= htmlspecialchars($related['tensp']) ?></div>
                            <div class="related-price"><?= number_format($related['giasanpham'], 0, ',', '.') ?>₫</div>
                        </div>
                    </a>
                </div>
                <?php
                    }
                }
                ?>
            </div>
        </div>

    </div>
</main>

<!-- ===== LIGHTBOX MODAL ===== -->
<div class="lightbox-overlay" id="lightbox" onclick="closeLightbox(event)">
    <div class="lightbox-img-wrap" id="lightbox-wrap">
        <button class="lightbox-close" onclick="closeLightbox()">&#x2715;</button>
        <button class="lightbox-nav lightbox-prev" onclick="lbNav(-1, event)">&#8249;</button>
        <img id="lightbox-img" src="" alt="">
        <button class="lightbox-nav lightbox-next" onclick="lbNav(1, event)">&#8250;</button>
    </div>
</div>

<script>
// ---- Image Gallery & Zoom System ----

// Collect all gallery image URLs
const lbImages = [
    <?php
    $allImgs = array_filter([$product['anhdaidien'], $product['anhgiuoithieu1'], $product['anhgiuoithieu2']]);
    foreach ($allImgs as $img) {
        echo "'" . BASE_URL . "admin/" . htmlspecialchars($img, ENT_QUOTES) . "',";
    }
    ?>
];
let lbCurrentIdx = 0;

// === SMOOTH HOVER ZOOM ===
(function initZoom() {
    const galleryMain = document.getElementById('gallery-main');
    const mainImg     = document.getElementById('main-product-img');
    const zoomLayer   = document.getElementById('zoom-layer');
    const zoomCursor  = document.getElementById('zoom-cursor');
    const zoomHint    = document.getElementById('zoom-hint');

    let isZooming  = false;
    let zoomScale  = 2.5;  // magnification factor
    let rafId      = null;
    let mouseX = 0, mouseY = 0;
    let hintTimeout = null;

    // Preload zoom image & set as background
    function setZoomImage(src) {
        zoomLayer.style.backgroundImage = `url('${src}')`;
        zoomLayer.style.backgroundSize = `${zoomScale * 100}%`;
    }

    // Initialize with current image
    mainImg.addEventListener('load', function() {
        setZoomImage(this.src);
    });
    if (mainImg.complete && mainImg.src) {
        setZoomImage(mainImg.src);
    }

    // Show hint briefly on first hover
    let hintShown = false;

    galleryMain.addEventListener('mouseenter', function(e) {
        isZooming = true;
        updateZoom();

        // Show hint briefly
        if (!hintShown) {
            hintShown = true;
            zoomHint.classList.add('show');
            clearTimeout(hintTimeout);
            hintTimeout = setTimeout(() => {
                zoomHint.classList.remove('show');
            }, 2000);
        }
    });

    galleryMain.addEventListener('mouseleave', function() {
        isZooming = false;
        if (rafId) {
            cancelAnimationFrame(rafId);
            rafId = null;
        }
    });

    galleryMain.addEventListener('mousemove', function(e) {
        const rect = galleryMain.getBoundingClientRect();
        mouseX = e.clientX - rect.left;
        mouseY = e.clientY - rect.top;

        // Hide hint on move
        zoomHint.classList.remove('show');
    });

    // Click to open lightbox
    galleryMain.addEventListener('click', function(e) {
        // Don't trigger on badge or hint clicks
        if (e.target.closest('.gallery-badge')) return;
        openLightbox(mainImg.src);
    });

    // Smooth animation loop using requestAnimationFrame
    function updateZoom() {
        if (!isZooming) return;

        const rect = galleryMain.getBoundingClientRect();
        const w = rect.width;
        const h = rect.height;

        // Calculate percentage position (clamped 0-1)
        const px = Math.max(0, Math.min(1, mouseX / w));
        const py = Math.max(0, Math.min(1, mouseY / h));

        // Update zoom background position
        zoomLayer.style.backgroundPosition = `${px * 100}% ${py * 100}%`;

        // Update cursor lens position
        zoomCursor.style.left  = mouseX + 'px';
        zoomCursor.style.top   = mouseY + 'px';

        rafId = requestAnimationFrame(updateZoom);
    }

    // Expose zoom reset for image switching
    window._resetZoom = function(src) {
        setZoomImage(src);
    };
})();

// === IMAGE SWITCHER (with zoom sync) ===
function switchImage(thumbEl, src) {
    const mainImg = document.getElementById('main-product-img');
    document.querySelectorAll('.thumb-item').forEach(t => t.classList.remove('active'));
    thumbEl.classList.add('active');
    lbCurrentIdx = lbImages.indexOf(src);

    mainImg.classList.add('img-switching');
    setTimeout(() => {
        mainImg.src = src;
        mainImg.onload = function() {
            mainImg.classList.remove('img-switching');
            if (window._resetZoom) window._resetZoom(src);
        };
        // Fallback if image is cached
        if (mainImg.complete) {
            mainImg.classList.remove('img-switching');
            if (window._resetZoom) window._resetZoom(src);
        }
    }, 300);
}

// === LIGHTBOX (smooth transitions) ===
function openLightbox(src) {
    lbCurrentIdx = lbImages.indexOf(src);
    if (lbCurrentIdx < 0) lbCurrentIdx = 0;
    const overlay = document.getElementById('lightbox');
    const img = document.getElementById('lightbox-img');
    img.src = lbImages[lbCurrentIdx];
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeLightbox(e) {
    if (e && e.target !== document.getElementById('lightbox') && e.target !== document.getElementById('lightbox-img')) return;
    const overlay = document.getElementById('lightbox');
    overlay.classList.remove('open');
    document.body.style.overflow = '';
}

function lbNav(dir, e) {
    if (e) e.stopPropagation();
    lbCurrentIdx = (lbCurrentIdx + dir + lbImages.length) % lbImages.length;
    const img = document.getElementById('lightbox-img');
    // Smooth cross-fade
    img.style.opacity = '0';
    img.style.transform = dir > 0 ? 'translateX(-20px)' : 'translateX(20px)';
    setTimeout(() => {
        img.src = lbImages[lbCurrentIdx];
        img.onload = function() {
            img.style.transition = 'opacity 0.3s cubic-bezier(0.4,0,0.2,1), transform 0.3s cubic-bezier(0.4,0,0.2,1)';
            img.style.opacity = '1';
            img.style.transform = 'translateX(0)';
        };
        // Fallback for cached images
        if (img.complete) {
            img.style.transition = 'opacity 0.3s cubic-bezier(0.4,0,0.2,1), transform 0.3s cubic-bezier(0.4,0,0.2,1)';
            img.style.opacity = '1';
            img.style.transform = 'translateX(0)';
        }
    }, 200);
}

// Keyboard controls
document.addEventListener('keydown', e => {
    const overlay = document.getElementById('lightbox');
    if (e.key === 'Escape' && overlay.classList.contains('open')) {
        overlay.classList.remove('open');
        document.body.style.overflow = '';
    }
    if (overlay.classList.contains('open')) {
        if (e.key === 'ArrowLeft')  lbNav(-1, null);
        if (e.key === 'ArrowRight') lbNav(1, null);
    }
});

// ---- Size Selector ----
let selectedSize = null;

function selectSize(btn, size) {
    document.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    selectedSize = size;
    document.getElementById('form-size').value = size;
    document.getElementById('size-error').style.display = 'none';
}

function validateSize() {
    if (!selectedSize) {
        const err = document.getElementById('size-error');
        err.style.display = 'block';
        err.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        return false;
    }
    return true;
}

// ---- Quantity Control ----
function changeQty(delta) {
    const field = document.getElementById('qty-field');
    let val = parseInt(field.value) || 1;
    val = Math.max(1, Math.min(99, val + delta));
    field.value = val;
}

// ---- Tab Switcher ----
function switchTab(btn, tabId) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(tabId).classList.add('active');
}
</script>

<?php include BASE_PATH . 'includes/footer.php'; ?>