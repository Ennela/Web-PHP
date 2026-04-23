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
    .blog-section {
        padding: 120px 0 80px;
        background-color: #f1f5f9;
        min-height: 100vh;
    }
    .editorial-heading {
        font-family: 'Montserrat', sans-serif;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: #0f172a;
        margin-bottom: 10px;
        font-size: 2.8rem;
    }
    .editorial-subheading {
        color: #64748b;
        font-size: 1.15rem;
        margin-bottom: 20px;
        font-weight: 300;
    }

    /* Search box */
    .blog-search-bar {
        max-width: 600px;
        margin: 0 auto 40px;
    }
    .blog-search-form {
        display: flex;
        gap: 0;
        border: 2px solid #e2e8f0;
        border-radius: 16px;
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
        padding: 14px 20px;
        font-size: 0.95rem;
        font-weight: 500;
        color: #334155;
        outline: none;
        background: #fff;
    }
    .blog-search-input::placeholder {
        color: #94a3b8;
    }
    .blog-search-btn {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: #fff;
        border: none;
        padding: 14px 24px;
        font-weight: 700;
        font-size: 0.9rem;
        text-transform: uppercase;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .blog-search-btn:hover {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
    }

    /* Search result info */
    .search-result-info {
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 20px;
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
    }
    .search-result-info strong {
        color: #1e293b;
    }
    .search-clear-link {
        color: #ef4444;
        font-weight: 700;
        font-size: 0.85rem;
        text-decoration: none;
    }
    .search-clear-link:hover {
        text-decoration: underline;
    }

    .blog-post-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        transition: all 0.3s ease;
    }
    .blog-post-card:hover {
        box-shadow: 0 12px 40px rgba(0,0,0,0.1);
        transform: translateY(-4px);
        border-color: #cbd5e1;
    }
    .blog-post-title {
        font-family: 'Montserrat', sans-serif;
        font-weight: 800;
        font-size: 1.5rem;
        color: #1e293b;
        margin-top: 15px;
        margin-bottom: 10px;
        line-height: 1.4;
    }
    .blog-post-meta {
        font-size: 0.85rem;
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
    }
    .btn-editorial-outline {
        background: transparent;
        color: #3b82f6;
        border: 2px solid #3b82f6;
        border-radius: 10px;
        padding: 10px 25px;
        font-weight: 800;
        font-size: 0.85rem;
        text-transform: uppercase;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
    }
    .btn-editorial-outline:hover {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: #fff;
        border-color: #3b82f6;
        text-decoration: none;
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
    }

    /* Empty state */
    .blog-empty {
        text-align: center;
        padding: 60px 20px;
    }
    .blog-empty i {
        font-size: 4rem;
        color: #cbd5e1;
        margin-bottom: 16px;
    }
    .blog-empty h4 {
        color: #475569;
        font-weight: 700;
    }
    .blog-empty p {
        color: #94a3b8;
    }

    /* ===== BLOG RESPONSIVE ===== */
    @media (max-width: 768px) {
        .blog-section {
            padding: 100px 0 50px;
        }
        .editorial-heading {
            font-size: 2rem;
        }
        .editorial-subheading {
            font-size: 1rem;
        }
        .blog-post-card {
            padding: 20px;
        }
        .blog-post-title {
            font-size: 1.2rem;
        }
        .blog-post-card .col-md-5 img {
            height: 200px !important;
        }
        .blog-search-bar {
            max-width: 100%;
        }
    }
    @media (max-width: 576px) {
        .blog-section {
            padding: 90px 0 40px;
        }
        .editorial-heading {
            font-size: 1.6rem;
            letter-spacing: 1px;
        }
        .blog-post-card {
            padding: 15px;
        }
        .blog-post-title {
            font-size: 1.1rem;
        }
        .blog-post-excerpt {
            font-size: 0.9rem;
        }
        .blog-search-form {
            border-radius: 10px;
        }
        .blog-search-input {
            padding: 10px 14px;
            font-size: 0.88rem;
        }
        .blog-search-btn {
            padding: 10px 16px;
            font-size: 0.8rem;
        }
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
