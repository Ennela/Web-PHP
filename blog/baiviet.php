<?php
require_once dirname(__DIR__) . '/config.php';
session_start();

if (isset($_GET['login']) && $_GET['login'] === 'dangxuat') {
    session_destroy();
    header('Location: baiviet.php');
    exit;
}

include BASE_PATH . 'includes/connect.php';

// ===== UC3: Tìm kiếm bài viết =====
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build WHERE clause (chỉ hiện bài "Hiện")
$where_sql = " WHERE `chedo` = 'Hiện'";
if (!empty($search)) {
    $search_safe = mysqli_real_escape_string($con, $search);
    $where_sql .= " AND (`tieude` LIKE '%$search_safe%' OR `noidung` LIKE '%$search_safe%')";
}

// Phân trang
$item_per_page = !empty($_GET['per_page']) ? (int)$_GET['per_page'] : 6;
$current_page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $item_per_page;

$products = mysqli_query($con,
    "SELECT * FROM `tbl_qlbaidang`" . $where_sql . " ORDER BY `id` DESC LIMIT " . $item_per_page . " OFFSET " . $offset
);

$totalRecords = mysqli_query($con, "SELECT COUNT(*) as cnt FROM `tbl_qlbaidang`" . $where_sql);
$totalRow = mysqli_fetch_assoc($totalRecords);
$totalRecords = $totalRow['cnt'];
$totalPages = ceil($totalRecords / $item_per_page);

// Build query string cho pagination
function buildBlogQueryString($overrides = []) {
    $params = $_GET;
    foreach ($overrides as $k => $v) {
        $params[$k] = $v;
    }
    $params = array_filter($params, function($v) { return $v !== '' && $v !== null; });
    return http_build_query($params);
}

include_once BASE_PATH . 'includes/header.php';
?>

<style>
/* ===== BLOG PAGE (MOBILE-FIRST) ===== */
.blog-section {
    padding: clamp(90px, 12vw, 120px) 0 clamp(40px, 8vw, 80px);
    background-color: #f1f5f9;
    min-height: 100vh;
}
.editorial-heading {
    font-family: 'Montserrat', sans-serif;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: clamp(1px, 0.5vw, 2px);
    color: #0f172a;
    margin-bottom: 10px;
    font-size: clamp(1.6rem, 4vw, 2.8rem);
}
.editorial-subheading {
    color: #64748b;
    font-size: clamp(1rem, 2vw, 1.15rem);
    margin-bottom: clamp(15px, 3vw, 20px);
    font-weight: 300;
}

/* Search box */
.blog-search-bar {
    max-width: 600px;
    margin: 0 auto clamp(25px, 5vw, 40px);
}
.blog-search-form {
    display: flex;
    gap: 0;
    border: 2px solid #e2e8f0;
    border-radius: clamp(10px, 2vw, 16px);
    overflow: hidden;
    transition: all 0.3s;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
}
.blog-search-form:focus-within {
    border-color: #3b82f6;
    box-shadow: 0 8px 30px rgba(59, 130, 246, 0.15);
}
.blog-search-input {
    flex: 1;
    border: none;
    padding: clamp(10px, 2vw, 14px) clamp(14px, 3vw, 20px);
    font-size: clamp(0.88rem, 2vw, 0.95rem);
    font-weight: 500;
    color: #334155;
    outline: none;
    background: #fff;
    min-height: 48px; /* Touch target */
}
.blog-search-input::placeholder {
    color: #94a3b8;
}
.blog-search-btn {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: #fff;
    border: none;
    padding: clamp(10px, 2vw, 14px) clamp(16px, 3vw, 24px);
    font-weight: 700;
    font-size: clamp(0.8rem, 2vw, 0.9rem);
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 48px; /* Touch target */
}
@media (hover: hover) {
    .blog-search-btn:hover {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
    }
}

/* Search result info */
.search-result-info {
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: clamp(10px, 2vw, 14px) clamp(14px, 3vw, 20px);
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}
.search-result-info span {
    font-weight: 600;
    color: #64748b;
    font-size: clamp(0.85rem, 2vw, 0.95rem);
}
.search-result-info strong {
    color: #1e293b;
}
.search-clear-link {
    color: #ef4444;
    font-weight: 700;
    font-size: clamp(0.8rem, 2vw, 0.85rem);
    text-decoration: none;
    min-height: 44px;
    display: inline-flex;
    align-items: center;
}
@media (hover: hover) {
    .search-clear-link:hover {
        text-decoration: underline;
    }
}

.blog-post-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: clamp(15px, 3vw, 30px);
    margin-bottom: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    transition: all 0.3s ease;
}
@media (hover: hover) {
    .blog-post-card:hover {
        box-shadow: 0 12px 40px rgba(0,0,0,0.1);
        transform: translateY(-4px);
        border-color: #cbd5e1;
    }
}
.blog-post-card .row {
    display: flex;
    flex-wrap: wrap; /* Ensure columns wrap on mobile */
}
.blog-post-img {
    width: 100%;
    height: clamp(200px, 40vw, 250px); /* Fluid height instead of important */
    object-fit: cover;
    border-radius: 12px;
}
@media (min-width: 768px) {
    .blog-post-img {
        height: 100%;
    }
}

.blog-post-title {
    font-family: 'Montserrat', sans-serif;
    font-weight: 800;
    font-size: clamp(1.1rem, 3vw, 1.5rem);
    color: #1e293b;
    margin-top: 15px;
    margin-bottom: 10px;
    line-height: 1.4;
    word-break: break-word;
}
.blog-post-meta {
    font-size: clamp(0.75rem, 1.8vw, 0.85rem);
    color: #94a3b8;
    margin-bottom: 20px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.blog-post-excerpt {
    color: #64748b;
    line-height: 1.7;
    margin-bottom: 25px;
    font-size: clamp(0.9rem, 2vw, 1rem);
    word-break: break-word;
}
.btn-editorial-outline {
    background: transparent;
    color: #3b82f6;
    border: 2px solid #3b82f6;
    border-radius: 10px;
    padding: clamp(8px, 2vw, 10px) clamp(20px, 4vw, 25px);
    font-weight: 800;
    font-size: clamp(0.8rem, 2vw, 0.85rem);
    text-transform: uppercase;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 44px; /* Touch target */
}
@media (hover: hover) {
    .btn-editorial-outline:hover {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: #fff;
        border-color: #3b82f6;
        text-decoration: none;
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
    }
}

/* Empty state */
.blog-empty {
    text-align: center;
    padding: clamp(40px, 8vw, 60px) 20px;
}
.blog-empty i {
    font-size: clamp(3rem, 8vw, 4rem);
    color: #cbd5e1;
    margin-bottom: 16px;
}
.blog-empty h4 {
    color: #475569;
    font-weight: 700;
    font-size: clamp(1.2rem, 3vw, 1.5rem);
}
.blog-empty p {
    color: #94a3b8;
    font-size: clamp(0.9rem, 2vw, 1rem);
}

</style>

<main class="page blog-post-list">
    <section class="blog-section">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="editorial-heading">TIN TỨC & BÀI VIẾT</h2>
                <p class="editorial-subheading">Thông tin, trao đổi, chia sẻ những câu chuyện về văn hóa thời trang và thể thao.</p>
            </div>

            <!-- UC3: Tìm kiếm bài viết -->
            <div class="blog-search-bar">
                <form method="GET" action="<?php echo BASE_URL; ?>blog/baiviet.php" class="blog-search-form">
                    <input type="text" name="search" class="blog-search-input" 
                           placeholder="Tìm kiếm bài viết..." 
                           value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="blog-search-btn">
                        <i class="fas fa-search"></i> Tìm
                    </button>
                </form>
            </div>

            <?php if (!empty($search)): ?>
            <div class="search-result-info">
                <span>
                    <i class="fas fa-search"></i>
                    Kết quả cho "<strong><?= htmlspecialchars($search) ?></strong>" — <?= $totalRecords ?> bài viết
                </span>
                <a href="<?php echo BASE_URL; ?>blog/baiviet.php" class="search-clear-link">
                    <i class="fas fa-times"></i> Xóa tìm kiếm
                </a>
            </div>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <?php if ($totalRecords > 0): ?>
                        <?php while ($row = mysqli_fetch_array($products)): ?>
                            <div class="blog-post-card">
                                <div class="row align-items-center">
                                    <div class="col-md-5 mb-4 mb-md-0">
                                        <div style="overflow: hidden; border-radius: 8px;">
                                            <img class="img-fluid w-100" style="object-fit:cover; height: 250px; transition: transform 0.5s;" 
                                                 src="<?php echo BASE_URL; ?>admin/<?= $row['anhdaidien'] ?>" 
                                                 alt="<?= htmlspecialchars($row['tieude']) ?>"
                                                 onmouseover="this.style.transform='scale(1.05)'" 
                                                 onmouseout="this.style.transform='scale(1)'">
                                        </div>
                                    </div>
                                    <div class="col-md-7 ps-md-4">
                                        <div class="blog-post-meta">
                                            <span><i class="far fa-calendar-alt me-1"></i> <?= date('d/m/Y H:i', $row['ngaycapnhat']) ?></span>
                                            <span class="mx-2">|</span>
                                            <span><i class="far fa-user me-1"></i> <?= htmlspecialchars($row['nguoiphutrach']) ?></span>
                                        </div>
                                        <h3 class="blog-post-title"><?= htmlspecialchars($row['tieude']) ?></h3>
                                        <p class="blog-post-excerpt"><?= htmlspecialchars(substr(strip_tags($row['noidung']), 0, 150)) ?>...</p>
                                        <a href="<?php echo BASE_URL; ?>blog/chitietbaiviet.php?id=<?= $row['id'] ?>" class="btn-editorial-outline">
                                            Đọc tiếp <i class="fas fa-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="blog-empty">
                            <i class="far fa-newspaper d-block"></i>
                            <h4>Không tìm thấy bài viết nào</h4>
                            <p>Hãy thử từ khóa khác hoặc <a href="<?php echo BASE_URL; ?>blog/baiviet.php">xem tất cả bài viết</a>.</p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($totalPages > 1): ?>
                    <div class="clear-both"></div>
                    <div class="d-flex justify-content-center mt-5">
                        <div id="pagination">
                            <?php if ($current_page > 3): ?>
                                <a class="page-item" href="?<?= buildBlogQueryString(['page' => 1]) ?>">First</a>
                            <?php endif; ?>
                            <?php if ($current_page > 1): ?>
                                <a class="page-item" href="?<?= buildBlogQueryString(['page' => $current_page - 1]) ?>">Prev</a>
                            <?php endif; ?>
                            <?php for ($num = 1; $num <= $totalPages; $num++): ?>
                                <?php if ($num > $current_page - 3 && $num < $current_page + 3): ?>
                                    <?php if ($num != $current_page): ?>
                                        <a class="page-item" href="?<?= buildBlogQueryString(['page' => $num]) ?>"><?= $num ?></a>
                                    <?php else: ?>
                                        <strong class="current-page page-item"><?= $num ?></strong>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <?php if ($current_page < $totalPages - 1): ?>
                                <a class="page-item" href="?<?= buildBlogQueryString(['page' => $current_page + 1]) ?>">Next</a>
                            <?php endif; ?>
                            <?php if ($current_page < $totalPages - 3): ?>
                                <a class="page-item" href="?<?= buildBlogQueryString(['page' => $totalPages]) ?>">Last</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include BASE_PATH . 'includes/footer.php'; ?>
