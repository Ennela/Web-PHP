<?php
require_once dirname(__DIR__) . '/config.php';
session_start();

if (isset($_GET['login']) && $_GET['login'] === 'dangxuat') {
    session_destroy();
    header('Location: shop.php');
    exit;
}

include BASE_PATH . 'includes/connect.php';

// ===== UC1: Chọn danh mục sản phẩm =====
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

// ===== UC3: Lọc & Sắp xếp =====
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';
$price_min = isset($_GET['price_min']) && $_GET['price_min'] !== '' ? (int)$_GET['price_min'] : null;
$price_max = isset($_GET['price_max']) && $_GET['price_max'] !== '' ? (int)$_GET['price_max'] : null;

// Build WHERE clause
$where_clauses = [];
$where_params = [];

if (!empty($category)) {
    $cat_safe = mysqli_real_escape_string($con, $category);
    $where_clauses[] = "`nhomsp` = '$cat_safe'";
}

if (!empty($search)) {
    $search_safe = mysqli_real_escape_string($con, $search);
    $where_clauses[] = "`tensp` LIKE '%$search_safe%'";
}

if ($price_min !== null) {
    $where_clauses[] = "`giasanpham` >= $price_min";
}

if ($price_max !== null) {
    $where_clauses[] = "`giasanpham` <= $price_max";
}

$where_sql = !empty($where_clauses) ? ' WHERE ' . implode(' AND ', $where_clauses) : '';

// Sort
$order_sql = ' ORDER BY `masp` DESC'; // Mới nhất mặc định
switch ($sort) {
    case 'price_asc':
        $order_sql = ' ORDER BY `giasanpham` ASC';
        break;
    case 'price_desc':
        $order_sql = ' ORDER BY `giasanpham` DESC';
        break;
    case 'name_asc':
        $order_sql = ' ORDER BY `tensp` ASC';
        break;
    case 'name_desc':
        $order_sql = ' ORDER BY `tensp` DESC';
        break;
    case 'newest':
        $order_sql = ' ORDER BY `masp` DESC';
        break;
    case 'oldest':
        $order_sql = ' ORDER BY `masp` ASC';
        break;
}

// ===== UC4: Phân trang =====
$item_per_page = !empty($_GET['per_page']) ? (int)$_GET['per_page'] : 9;
$current_page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $item_per_page;

$products = mysqli_query($con,
    "SELECT * FROM `tbl_qlsanpham`" . $where_sql . $order_sql . " LIMIT " . $item_per_page . " OFFSET " . $offset);

$totalRecords = mysqli_query($con, "SELECT COUNT(*) as cnt FROM `tbl_qlsanpham`" . $where_sql);
$totalRow = mysqli_fetch_assoc($totalRecords);
$totalRecords = $totalRow['cnt'];
$totalPages = ceil($totalRecords / $item_per_page);

// Lấy danh mục cho bộ lọc
$categories = mysqli_query($con, "SELECT DISTINCT `nhomsp` FROM `tbl_qlsanpham` WHERE `nhomsp` IS NOT NULL AND `nhomsp` != '' ORDER BY `nhomsp` ASC");

// Build query string cho pagination (giữ filter)
function buildQueryString($overrides = []) {
    $params = $_GET;
    foreach ($overrides as $k => $v) {
        $params[$k] = $v;
    }
    // Xóa params rỗng
    $params = array_filter($params, function($v) { return $v !== '' && $v !== null; });
    return http_build_query($params);
}

include BASE_PATH . 'includes/header.php';
?>

<style>
    .shop-section {
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
        margin-bottom: 30px;
        font-weight: 300;
    }

    /* === Filter Bar === */
    .shop-filter-bar {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        border: 1px solid #e2e8f0;
        padding: 24px 28px;
        margin-bottom: 40px;
    }
    .filter-row {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: flex-end;
    }
    .filter-group {
        flex: 1;
        min-width: 160px;
    }
    .filter-group label {
        display: block;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: #1e293b;
        margin-bottom: 8px;
    }
    .filter-group select,
    .filter-group input[type="number"] {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.9rem;
        font-weight: 600;
        color: #334155;
        background: #f8fafc;
        transition: border-color 0.3s;
        outline: none;
        appearance: auto;
    }
    .filter-group select:focus,
    .filter-group input:focus {
        border-color: #3b82f6;
        background: #fff;
    }

    /* Search box premium */
    .search-box-wrap {
        position: relative;
    }
    .search-box-wrap .search-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 1rem;
        transition: all 0.3s ease;
        pointer-events: none;
    }
    .search-box-wrap input {
        width: 100%;
        padding: 12px 16px 12px 44px;
        border: 2px solid #e2e8f0;
        border-radius: 50px;
        font-size: 0.9rem;
        font-weight: 600;
        color: #334155;
        background: #f8fafc;
        transition: all 0.3s ease;
        outline: none;
    }
    .search-box-wrap input::placeholder {
        color: #94a3b8;
        font-weight: 500;
    }
    .search-box-wrap input:focus {
        border-color: #3b82f6;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1), 0 4px 16px rgba(59, 130, 246, 0.12);
    }
    .search-box-wrap input:focus + .search-icon {
        color: #3b82f6;
    }
    .search-box-wrap .search-clear {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        background: #e2e8f0;
        border: none;
        color: #64748b;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        font-size: 0.65rem;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }
    .search-box-wrap .search-clear:hover {
        background: #ef4444;
        color: #fff;
    }
    .search-box-wrap input:not(:placeholder-shown) ~ .search-clear {
        display: flex;
    }
    .filter-actions {
        display: flex;
        gap: 10px;
        min-width: 200px;
    }
    .btn-filter-apply {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 10px 20px;
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        cursor: pointer;
        transition: all 0.3s ease;
        white-space: nowrap;
    }
    .btn-filter-apply:hover {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
    }
    .btn-filter-reset {
        background: transparent;
        color: #64748b;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 16px;
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        white-space: nowrap;
    }
    .btn-filter-reset:hover {
        border-color: #ef4444;
        color: #ef4444;
        text-decoration: none;
    }

    /* Category Tags */
    .category-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 30px;
    }
    .category-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 20px;
        border: 2px solid #e2e8f0;
        border-radius: 50px;
        background: #fff;
        color: #64748b;
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    .category-tag:hover {
        border-color: #3b82f6;
        color: #3b82f6;
        background: #eff6ff;
        text-decoration: none;
        transform: translateY(-2px);
    }
    .category-tag.active {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: #fff;
        border-color: #3b82f6;
    }
    .category-tag.active:hover {
        color: #fff;
    }

    /* Results info */
    .results-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        padding: 0 4px;
    }
    .results-count {
        font-size: 0.9rem;
        color: #94a3b8;
        font-weight: 600;
    }
    .results-count strong {
        color: #3b82f6;
    }
    .active-filters {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .active-filter-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #eff6ff;
        border-radius: 50px;
        padding: 4px 12px;
        font-size: 0.78rem;
        font-weight: 600;
        color: #3b82f6;
    }

    /* Product Cards */
    .product-card {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        transition: all 0.3s ease;
        background: #fff;
    }
    .product-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.1);
        border-color: #cbd5e1;
    }
    .pc-img-wrap {
        position: relative;
        overflow: hidden;
        background: #fff;
    }
    .pc-img-wrap img {
        transition: transform 0.5s ease;
    }
    .product-card:hover .pc-img-wrap img {
        transform: scale(1.05);
    }
    .pc-badge {
        position: absolute;
        top: 12px;
        left: 12px;
        background: #1e293b;
        color: #fff;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 1px;
        text-transform: uppercase;
        padding: 4px 10px;
        border-radius: 6px;
        z-index: 2;
    }
    .pc-badge.sale {
        background: #ef4444;
    }
    .btn-editorial-outline {
        background: transparent;
        color: #3b82f6;
        border: 2px solid #3b82f6;
        border-radius: 10px;
        padding: 10px 15px;
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        text-align: center;
    }
    .btn-editorial-outline:hover {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: #fff;
        border-color: #3b82f6;
        text-decoration: none;
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
    }
    .btn-editorial-solid {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 10px 15px;
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        text-align: center;
    }
    .btn-editorial-solid:hover {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #fff;
        text-decoration: none;
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
    }

    /* Search highlight */
    .search-highlight {
        background: linear-gradient(135deg, #dbeafe, #eff6ff);
        border: 1px solid #93c5fd;
        border-radius: 12px;
        padding: 14px 20px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        color: #1e40af;
    }
    .search-highlight i { font-size: 1.2rem; }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    .empty-state i {
        font-size: 4rem;
        color: #cbd5e1;
        margin-bottom: 16px;
    }
    .empty-state h4 {
        color: #475569;
        font-weight: 700;
        margin-bottom: 8px;
    }
    .empty-state p {
        color: #94a3b8;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .shop-section { padding: 100px 0 40px; }
        .editorial-heading { font-size: 2rem; }
        .filter-row { flex-direction: column; }
        .filter-group { min-width: 100%; }
        .filter-actions { width: 100%; }
        .results-info { flex-direction: column; gap: 8px; align-items: flex-start; }
    }
</style>

<main class="page catalog-page">
    <section class="shop-section">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="editorial-heading">CỬA HÀNG</h2>
                <p class="editorial-subheading">Nơi trưng bày những tinh hoa Streetwear mới nhất và chất nhất.</p>
            </div>

            <!-- UC1: Category Tags -->
            <div class="category-tags">
                <a href="<?php echo BASE_URL; ?>shop/shop.php" class="category-tag <?= empty($category) ? 'active' : '' ?>">
                    <i class="fas fa-th"></i> Tất cả
                </a>
                <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                <a href="<?php echo BASE_URL; ?>shop/shop.php?category=<?= urlencode($cat['nhomsp']) ?>" 
                   class="category-tag <?= $category === $cat['nhomsp'] ? 'active' : '' ?>">
                    <?= htmlspecialchars($cat['nhomsp']) ?>
                </a>
                <?php endwhile; ?>
            </div>

            <!-- UC3: Filter & Sort Bar -->
            <div class="shop-filter-bar">
                <form method="GET" action="<?php echo BASE_URL; ?>shop/shop.php" id="filterForm">
                    <?php if (!empty($category)): ?>
                    <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
                    <?php endif; ?>
                    <div class="filter-row">
                        <div class="filter-group" style="flex: 1.5;">
                            <label><i class="fas fa-search"></i> Tìm kiếm</label>
                            <div class="search-box-wrap">
                                <input type="text" name="search" placeholder="Tìm giày Nike, Jordan, Adidas..."
                                       value="<?= htmlspecialchars($search) ?>" id="shopSearchInput">
                                <i class="fas fa-search search-icon"></i>
                                <button type="button" class="search-clear" onclick="document.getElementById('shopSearchInput').value=''; this.style.display='none';" title="Xóa">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="filter-group">
                            <label><i class="fas fa-sort"></i> Sắp xếp</label>
                            <select name="sort">
                                <option value="">-- Mặc định --</option>
                                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Giá: Thấp → Cao</option>
                                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Giá: Cao → Thấp</option>
                                <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Tên: A → Z</option>
                                <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Tên: Z → A</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label><i class="fas fa-tag"></i> Giá từ</label>
                            <input type="number" name="price_min" placeholder="0đ" min="0" step="50000"
                                   value="<?= $price_min !== null ? $price_min : '' ?>">
                        </div>
                        <div class="filter-group">
                            <label>Đến</label>
                            <input type="number" name="price_max" placeholder="∞" min="0" step="50000"
                                   value="<?= $price_max !== null ? $price_max : '' ?>">
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn-filter-apply">
                                <i class="fas fa-filter"></i> Lọc
                            </button>
                            <a href="<?php echo BASE_URL; ?>shop/shop.php<?= !empty($category) ? '?category=' . urlencode($category) : '' ?>" class="btn-filter-reset">
                                <i class="fas fa-undo"></i> Xóa
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <?php if (!empty($search)): ?>
            <div class="search-highlight">
                <i class="fas fa-search"></i>
                Kết quả tìm kiếm cho: "<strong><?= htmlspecialchars($search) ?></strong>"
                — <?= $totalRecords ?> sản phẩm
            </div>
            <?php endif; ?>

            <!-- Results Info -->
            <div class="results-info">
                <span class="results-count">
                    Hiển thị <strong><?= min($totalRecords, $item_per_page) ?></strong> / <strong><?= $totalRecords ?></strong> sản phẩm
                    <?php if (!empty($category)): ?>
                        trong <strong><?= htmlspecialchars($category) ?></strong>
                    <?php endif; ?>
                </span>
            </div>

            <!-- UC2: Product Grid -->
            <?php if ($totalRecords > 0): ?>
            <div class="row">
                <?php while ($row = mysqli_fetch_array($products)): ?>
                <div class="col-md-6 col-lg-4 mb-5">
                    <div class="card product-card h-100">
                        <div class="pc-img-wrap">
                            <?php if(!empty($row['nhomsp'])): ?>
                            <span class="pc-badge"><?= htmlspecialchars($row['nhomsp']) ?></span>
                            <?php endif; ?>
                            <?php if(!empty($row['giagoc']) && $row['giagoc'] > $row['giasanpham']): 
                                $disc = round((1 - $row['giasanpham'] / $row['giagoc']) * 100);
                            ?>
                            <span class="pc-badge sale" style="left:auto; right:12px;">-<?= $disc ?>%</span>
                            <?php endif; ?>
                            <a href="<?php echo BASE_URL; ?>shop/chitietsanpham.php?masp=<?= $row['masp'] ?>">
                                <img class="card-img-top" src="<?php echo BASE_URL; ?>admin/<?= $row['anhdaidien'] ?>" alt="<?= htmlspecialchars($row['tensp']) ?>" style="height: 380px; object-fit: cover; width: 100%;">
                            </a>
                        </div>
                        <div class="card-body text-center d-flex flex-column" style="padding: 25px;">
                            <h5 class="card-title" style="font-weight: 800; font-size: 1.05rem; color: #1e293b; margin-bottom: 15px; line-height: 1.4;"><?= htmlspecialchars($row['tensp']) ?></h5>
                            
                            <p class="card-text mb-4" style="font-weight: 700; font-size: 1.25rem; color: #2563eb;">
                                <?php if(!empty($row['giagoc']) && $row['giagoc'] > $row['giasanpham']) { ?>
                                    <span style="font-size: 0.9rem; text-decoration: line-through; color: #999; margin-right: 8px;">
                                        <?= number_format($row['giagoc'], 0, ',', '.') ?> đ
                                    </span>
                                <?php } ?>
                                <?= number_format($row['giasanpham'], 0, ',', '.') ?> đ
                            </p>

                            <div class="mt-auto d-flex justify-content-between">
                                <a href="<?php echo BASE_URL; ?>shop/chitietsanpham.php?masp=<?= $row['masp'] ?>" class="btn-editorial-outline" style="width: 48%;">
                                    <i class="fa fa-eye mb-1 d-block"></i> Chi Tiết
                                </a>
                                <a href="<?php echo BASE_URL; ?>shop/giohang.php?action=add&masp=<?= $row['masp'] ?>&quantity=1" class="btn-editorial-solid" style="width: 48%;">
                                    <i class="fa fa-shopping-cart mb-1 d-block"></i> Mua Ngay
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-box-open d-block"></i>
                <h4>Không tìm thấy sản phẩm nào</h4>
                <p>Hãy thử thay đổi bộ lọc hoặc từ khóa tìm kiếm.</p>
                <a href="<?php echo BASE_URL; ?>shop/shop.php" class="btn-editorial-solid mt-3" style="display: inline-block; padding: 12px 30px;">
                    <i class="fas fa-undo"></i> Xem tất cả sản phẩm
                </a>
            </div>
            <?php endif; ?>
            
            <!-- UC4: Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="clear-both"></div>
            <div class="d-flex justify-content-center mt-4">
                <div id="pagination">
                    <?php if ($current_page > 3): ?>
                        <a class="page-item" href="?<?= buildQueryString(['page' => 1]) ?>">First</a>
                    <?php endif; ?>
                    <?php if ($current_page > 1): ?>
                        <a class="page-item" href="?<?= buildQueryString(['page' => $current_page - 1]) ?>">Prev</a>
                    <?php endif; ?>
                    <?php for ($num = 1; $num <= $totalPages; $num++): ?>
                        <?php if ($num > $current_page - 3 && $num < $current_page + 3): ?>
                            <?php if ($num != $current_page): ?>
                                <a class="page-item" href="?<?= buildQueryString(['page' => $num]) ?>"><?= $num ?></a>
                            <?php else: ?>
                                <strong class="current-page page-item"><?= $num ?></strong>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($current_page < $totalPages - 1): ?>
                        <a class="page-item" href="?<?= buildQueryString(['page' => $current_page + 1]) ?>">Next</a>
                    <?php endif; ?>
                    <?php if ($current_page < $totalPages - 3): ?>
                        <a class="page-item" href="?<?= buildQueryString(['page' => $totalPages]) ?>">Last</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include BASE_PATH . 'includes/footer.php'; ?>