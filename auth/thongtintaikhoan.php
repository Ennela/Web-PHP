<?php
require_once dirname(__DIR__) . '/config.php';
session_start();
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
date_default_timezone_set('Asia/Ho_Chi_Minh');

include BASE_PATH . 'includes/connect.php';

// ── Handle logout ──
if (isset($_GET['login']) && $_GET['login'] === 'dangxuat') {
    session_destroy();
    header('Location: ' . BASE_URL . 'auth/dangnhap.php');
    exit;
}

// ── Check authentication ──
if (empty($_SESSION['dangnhap']) || empty($_SESSION['makh'])) {
    echo "<script>alert('Vui lòng đăng nhập trước!'); window.location.href='" . BASE_URL . "auth/dangnhap.php';</script>";
    exit;
}

$makh = intval($_SESSION['makh']);
$query = mysqli_query($con, "SELECT * FROM `tbl_tkkhachhang` WHERE `makh` = '$makh' LIMIT 1");
$user = mysqli_fetch_array($query);

// UC5: Lịch sử mua hàng
$orders = [];
$orderQuery = mysqli_query($con, "SELECT * FROM `oder` WHERE `makh` = $makh ORDER BY `ngaytao` DESC LIMIT 20");
if ($orderQuery) {
    while ($orderRow = mysqli_fetch_assoc($orderQuery)) {
        $orders[] = $orderRow;
    }
}

if (!$user) {
    echo "<script>alert('Lỗi truy xuất! Vui lòng đăng nhập lại.'); window.location.href='" . BASE_URL . "auth/dangnhap.php';</script>";
    exit;
}

// Avatar URL
$avatarUrl = !empty($user['avatar']) ? BASE_URL . $user['avatar'] : '';
$defaultAvatar = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect fill="#0f172a" width="100" height="100"/><text x="50" y="58" text-anchor="middle" fill="#3b82f6" font-size="40" font-family="Arial" font-weight="bold">' . mb_substr($user['hoten'] ?? 'U', 0, 1) . '</text></svg>');
$avatarSrc = $avatarUrl ?: $defaultAvatar;

include BASE_PATH . 'includes/header.php';
?>

<meta name="base-url" content="<?= BASE_URL ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/account.css">

<!-- Mobile Toggle -->
<button class="mobile-account-toggle" id="mobileAccountToggle">
    <i class="fas fa-bars"></i>
</button>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="account-wrapper">
    <!-- ============ SIDEBAR ============ -->
    <aside class="account-sidebar">
        <div class="sidebar-profile">
            <img src="<?= $avatarSrc ?>" alt="Avatar" class="sidebar-avatar avatar-img" id="sidebarAvatar">
            <div class="sidebar-name"><?= htmlspecialchars($user['hoten'] ?? 'Người dùng') ?></div>
            <div class="sidebar-email"><?= htmlspecialchars($user['email'] ?? $user['username'] ?? '') ?></div>
        </div>

        <nav class="sidebar-nav">
            <a href="#" class="sidebar-nav-item active" data-tab="tab-profile">
                <i class="fas fa-user"></i>
                <span>Thông tin cá nhân</span>
            </a>
            <a href="#" class="sidebar-nav-item" data-tab="tab-password">
                <i class="fas fa-lock"></i>
                <span>Đổi mật khẩu</span>
            </a>
            <a href="#" class="sidebar-nav-item" data-tab="tab-address">
                <i class="fas fa-map-marker-alt"></i>
                <span>Sổ địa chỉ</span>
            </a>
            <a href="#" class="sidebar-nav-item" data-tab="tab-size">
                <i class="fas fa-shoe-prints"></i>
                <span>Size giày yêu thích</span>
            </a>

            <a href="#" class="sidebar-nav-item" data-tab="tab-orders">
                <i class="fas fa-box"></i>
                <span>Đơn hàng của tôi</span>
            </a>

            <div class="sidebar-nav-divider"></div>

            <a href="?login=dangxuat" class="sidebar-nav-item logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Đăng xuất</span>
            </a>
        </nav>
    </aside>

    <!-- ============ CONTENT ============ -->
    <main class="account-content">

        <!-- ──────── TAB 1: THÔNG TIN CÁ NHÂN ──────── -->
        <div class="account-tab-content active" id="tab-profile">
            <div class="content-header">
                <h2>Thông tin cá nhân</h2>
                <p>Quản lý thông tin hồ sơ để bảo mật tài khoản</p>
            </div>

            <div class="acc-card">
                <!-- Avatar Section -->
                <div class="profile-avatar-section">
                    <div class="profile-avatar-wrap" id="avatarWrap">
                        <img src="<?= $avatarSrc ?>" alt="Avatar" class="profile-avatar-large avatar-img">
                        <div class="profile-avatar-overlay">
                            <i class="fas fa-camera"></i>
                        </div>
                        <input type="file" id="avatarInput" accept="image/*" style="display:none">
                    </div>
                    <div class="profile-avatar-info">
                        <h3><?= htmlspecialchars($user['hoten'] ?? '') ?></h3>
                        <p>Nhấn vào ảnh đại diện để thay đổi (JPG, PNG, WebP — max 2MB)</p>
                    </div>
                </div>

                <!-- Profile Form -->
                <form id="profileForm" onsubmit="return false;">
                    <div class="acc-form-row">
                        <div class="acc-form-group">
                            <label class="acc-form-label">Họ và tên *</label>
                            <input type="text" name="hoten" class="acc-form-input" value="<?= htmlspecialchars($user['hoten'] ?? '') ?>" disabled>
                            <div class="acc-form-error"></div>
                        </div>
                        <div class="acc-form-group">
                            <label class="acc-form-label">Tên đăng nhập</label>
                            <input type="text" class="acc-form-input" value="<?= htmlspecialchars($user['username'] ?? '') ?>" disabled data-always-readonly="1">
                            <div class="acc-form-error"></div>
                        </div>
                    </div>

                    <div class="acc-form-row">
                        <div class="acc-form-group">
                            <label class="acc-form-label">Email</label>
                            <input type="email" name="email" class="acc-form-input" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
                            <div class="acc-form-error"></div>
                        </div>
                        <div class="acc-form-group">
                            <label class="acc-form-label">Số điện thoại</label>
                            <input type="tel" name="sdt" class="acc-form-input" value="<?= htmlspecialchars($user['sdt'] ?? '') ?>" disabled>
                            <div class="acc-form-error"></div>
                        </div>
                    </div>

                    <div class="acc-form-row">
                        <div class="acc-form-group">
                            <label class="acc-form-label">Ngày sinh</label>
                            <input type="date" name="ngaysinh" class="acc-form-input" value="<?= htmlspecialchars($user['ngaysinh'] ?? '') ?>" disabled>
                            <div class="acc-form-error"></div>
                        </div>
                        <div class="acc-form-group">
                            <label class="acc-form-label">Giới tính</label>
                            <select name="gioitinh" class="acc-form-select" disabled>
                                <option value="">-- Chọn --</option>
                                <option value="Nam" <?= ($user['gioitinh'] ?? '') === 'Nam' ? 'selected' : '' ?>>Nam</option>
                                <option value="Nữ" <?= ($user['gioitinh'] ?? '') === 'Nữ' ? 'selected' : '' ?>>Nữ</option>
                                <option value="Khác" <?= ($user['gioitinh'] ?? '') === 'Khác' ? 'selected' : '' ?>>Khác</option>
                            </select>
                            <div class="acc-form-error"></div>
                        </div>
                    </div>

                    <div class="acc-btn-group">
                        <button type="button" class="acc-btn acc-btn-dark" id="btnEditProfile">
                            <i class="fas fa-pen"></i> Chỉnh sửa
                        </button>
                        <button type="button" class="acc-btn acc-btn-primary" id="btnSaveProfile" style="display:none">
                            <i class="fas fa-save"></i> Lưu thay đổi
                        </button>
                        <button type="button" class="acc-btn acc-btn-outline" id="btnCancelProfile" style="display:none">
                            <i class="fas fa-times"></i> Hủy
                        </button>
                    </div>
                </form>
            </div>

            <div class="acc-info-box success">
                <i class="fas fa-shield-alt"></i>
                <span>Thông tin của bạn được bảo mật an toàn trên hệ thống.</span>
            </div>
        </div>

        <!-- ──────── TAB 2: ĐỔI MẬT KHẨU ──────── -->
        <div class="account-tab-content" id="tab-password">
            <div class="content-header">
                <h2>Đổi mật khẩu</h2>
                <p>Để bảo mật tài khoản, vui lòng không chia sẻ mật khẩu cho người khác</p>
            </div>

            <div class="acc-card" style="max-width: 520px;">
                <div class="acc-card-title">
                    <i class="fas fa-key"></i>
                    Cập nhật mật khẩu
                </div>

                <form id="passwordForm" onsubmit="return false;">
                    <div class="acc-form-group">
                        <label class="acc-form-label">Mật khẩu hiện tại</label>
                        <div class="password-input-wrap">
                            <input type="password" name="matkhau_hientai" class="acc-form-input" placeholder="Nhập mật khẩu hiện tại">
                            <button type="button" class="password-toggle"><i class="fas fa-eye"></i></button>
                        </div>
                        <div class="acc-form-error"></div>
                    </div>

                    <div class="acc-form-group">
                        <label class="acc-form-label">Mật khẩu mới</label>
                        <div class="password-input-wrap">
                            <input type="password" name="matkhau_moi" id="matkhau_moi" class="acc-form-input" placeholder="Tối thiểu 6 ký tự">
                            <button type="button" class="password-toggle"><i class="fas fa-eye"></i></button>
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar"></div>
                        </div>
                        <div class="password-strength-text"></div>
                        <div class="acc-form-error"></div>
                    </div>

                    <div class="acc-form-group">
                        <label class="acc-form-label">Xác nhận mật khẩu mới</label>
                        <div class="password-input-wrap">
                            <input type="password" name="matkhau_xacnhan" class="acc-form-input" placeholder="Nhập lại mật khẩu mới">
                            <button type="button" class="password-toggle"><i class="fas fa-eye"></i></button>
                        </div>
                        <div class="acc-form-error"></div>
                    </div>

                    <div class="acc-btn-group">
                        <button type="button" class="acc-btn acc-btn-primary" id="btnChangePassword">
                            <i class="fas fa-key"></i> Đổi mật khẩu
                        </button>
                    </div>
                </form>
            </div>

            <div class="acc-info-box warning">
                <i class="fas fa-info-circle"></i>
                <span>Sau khi đổi mật khẩu, bạn cần sử dụng mật khẩu mới để đăng nhập ở lần sau.</span>
            </div>
        </div>

        <!-- ──────── TAB 3: SỔ ĐỊA CHỈ ──────── -->
        <div class="account-tab-content" id="tab-address">
            <div class="content-header">
                <h2>Sổ địa chỉ</h2>
                <p>Quản lý địa chỉ giao hàng của bạn (tối đa 5 địa chỉ)</p>
            </div>

            <div class="acc-card">
                <div class="address-grid" id="addressGrid">
                    <!-- Rendered by JS -->
                    <div style="text-align: center; padding: 40px; color: #888;">
                        <span class="acc-spinner acc-spinner-dark"></span>
                        <p style="margin-top: 12px;">Đang tải...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ──────── TAB 4: SIZE GIÀY YÊU THÍCH ──────── -->
        <div class="account-tab-content" id="tab-size">
            <div class="content-header">
                <h2>Size giày yêu thích</h2>
                <p>Lưu size hay dùng để mua sắm nhanh hơn</p>
            </div>

            <div class="acc-card">
                <div class="acc-card-title">
                    <i class="fas fa-ruler"></i>
                    Chọn hệ size
                </div>

                <div class="size-system-tabs">
                    <button class="size-system-tab active" data-system="EU">EU</button>
                    <button class="size-system-tab" data-system="US">US</button>
                    <button class="size-system-tab" data-system="CM">CM</button>
                </div>

                <div class="acc-info-box info" style="margin-bottom: 16px;">
                    <i class="fas fa-info-circle"></i>
                    <span>Nhấn vào size để chọn, sau đó nhấn <strong>"Lưu size"</strong>. Nhấn vào size đỏ để bỏ.</span>
                </div>

                <div class="size-grid" id="sizeGrid">
                    <!-- Rendered by JS -->
                </div>

                <div class="acc-form-group">
                    <label class="acc-form-label">Ghi chú sở thích</label>
                    <textarea id="sizeNote" class="acc-form-input" rows="3" placeholder="VD: Chân rộng, thích mang rộng hơn 0.5 size, bàn chân dẹt..."  style="resize: vertical;"></textarea>
                </div>

                <div class="acc-btn-group">
                    <button type="button" class="acc-btn acc-btn-primary" id="btnSaveSize">
                        <i class="fas fa-save"></i> Lưu size yêu thích
                    </button>
                </div>
            </div>

            <div class="acc-card">
                <div class="acc-card-title">
                    <i class="fas fa-heart"></i>
                    Size đã lưu
                </div>
                <div id="savedSizesList">
                    <div style="text-align: center; padding: 24px; color: #888;">
                        <span class="acc-spinner acc-spinner-dark"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ──────── TAB 5: LỊCH SỬ MUA HÀNG ──────── -->
        <div class="account-tab-content" id="tab-orders">
            <div class="content-header">
                <h2>Đơn hàng của tôi</h2>
                <p>Theo dõi trạng thái và lịch sử tất cả đơn hàng</p>
            </div>

            <?php if (!empty($orders)): ?>
                <div class="acc-info-box info" style="margin-bottom: 20px;">
                    <i class="fas fa-info-circle"></i>
                    <span>Bạn có <strong><?= count($orders) ?></strong> đơn hàng. Nhấn "Xem chi tiết" để xem thông tin đầy đủ.</span>
                </div>

                <?php foreach ($orders as $order):
                    $statusMap = [
                        'PENDING'   => ['label' => 'Chờ xử lý',      'color' => '#f59e0b', 'bg' => '#fef3c7', 'icon' => 'fa-clock'],
                        'CONFIRMED' => ['label' => 'Đã xác nhận',    'color' => '#3b82f6', 'bg' => '#dbeafe', 'icon' => 'fa-check-circle'],
                        'SHIPPING'  => ['label' => 'Đang giao hàng', 'color' => '#8b5cf6', 'bg' => '#ede9fe', 'icon' => 'fa-truck'],
                        'DELIVERED' => ['label' => 'Đã giao hàng',   'color' => '#10b981', 'bg' => '#d1fae5', 'icon' => 'fa-box-open'],
                        'CANCELLED' => ['label' => 'Đã hủy',         'color' => '#ef4444', 'bg' => '#fee2e2', 'icon' => 'fa-times-circle'],
                    ];
                    $status = $statusMap[strtoupper($order['status'] ?? 'PENDING')] ?? $statusMap['PENDING'];
                ?>
                <div class="acc-card" style="margin-bottom: 16px; padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 16px;">
                        <div>
                            <span style="font-weight: 800; color: #111; font-size: 1.05rem;">
                                <i class="fas fa-box" style="color: #3b82f6; margin-right: 4px;"></i>
                                Đơn hàng #<?= !empty($order['order_code']) ? $order['order_code'] : $order['id'] ?>
                            </span>
                        </div>
                        <span style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 14px; border-radius: 50px; font-weight: 600; font-size: 0.82rem; color: <?= $status['color'] ?>; background: <?= $status['bg'] ?>;">
                            <i class="fas <?= $status['icon'] ?>"></i>
                            <?= $status['label'] ?>
                        </span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 0.9rem;">
                        <div style="color: #888;"><i class="fas fa-map-marker-alt" style="width: 18px;"></i> Địa chỉ:</div>
                        <div style="font-weight: 600; color: #333;"><?= htmlspecialchars($order['diachi'] ?? '') ?></div>
                        <div style="color: #888;"><i class="far fa-calendar-alt" style="width: 18px;"></i> Ngày đặt:</div>
                        <div style="font-weight: 600; color: #333;"><?= date('d/m/Y H:i', $order['ngaytao']) ?></div>
                        <div style="color: #888;"><i class="fas fa-coins" style="width: 18px;"></i> Tổng tiền:</div>
                        <div style="font-weight: 700; color: #2563eb; font-size: 1.05rem;"><?= number_format($order['tongtien'], 0, ',', '.') ?>&nbsp;đ</div>
                    </div>
                    <div style="margin-top: 16px; text-align: right;">
                        <a href="<?= BASE_URL ?>shop/chitietdonhang.php?id=<?= $order['id'] ?>" 
                           style="display: inline-flex; align-items: center; gap: 6px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; border-radius: 8px; padding: 8px 20px; font-weight: 600; font-size: 0.85rem; text-decoration: none; transition: all 0.3s;">
                            Xem chi tiết <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>

                <div style="text-align: center; margin-top: 20px;">
                    <a href="<?= BASE_URL ?>shop/tradonhang.php" 
                       style="display: inline-flex; align-items: center; gap: 8px; color: #111; font-weight: 700; text-decoration: none; font-size: 0.9rem; padding: 10px 24px; border: 2px solid #111; border-radius: 6px; transition: all 0.3s;">
                        <i class="fas fa-list"></i> Xem tất cả đơn hàng
                    </a>
                </div>

            <?php else: ?>
                <div class="acc-card" style="text-align: center; padding: 50px 20px;">
                    <i class="fas fa-box-open" style="font-size: 3.5rem; color: #ddd; margin-bottom: 16px; display: block;"></i>
                    <h4 style="font-weight: 700; color: #555; margin-bottom: 8px;">Bạn chưa có đơn hàng nào</h4>
                    <p style="color: #999; margin-bottom: 20px;">Hãy mua sắm và quay lại đây để theo dõi nhé!</p>
                    <a href="<?= BASE_URL ?>shop/shop.php" 
                       style="display: inline-flex; align-items: center; gap: 8px; background: #111; color: #fff; padding: 12px 28px; border-radius: 6px; text-decoration: none; font-weight: 700; font-size: 0.9rem; text-transform: uppercase;">
                        <i class="fas fa-shopping-bag"></i> Bắt đầu mua sắm
                    </a>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<!-- ============ ADDRESS MODAL ============ -->
<div class="acc-modal-overlay" id="addressModal">
    <div class="acc-modal">
        <div class="acc-modal-header">
            <h3>Thêm địa chỉ mới</h3>
            <button class="acc-modal-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="acc-modal-body">
            <form id="addressForm" onsubmit="return false;">
                <div class="acc-form-row">
                    <div class="acc-form-group">
                        <label class="acc-form-label">Họ tên người nhận *</label>
                        <input type="text" name="hoten" class="acc-form-input" placeholder="Nguyễn Văn A">
                    </div>
                    <div class="acc-form-group">
                        <label class="acc-form-label">Số điện thoại *</label>
                        <input type="tel" name="sdt" class="acc-form-input" placeholder="0912345678">
                    </div>
                </div>

                <div class="acc-form-group">
                    <label class="acc-form-label">Tỉnh / Thành phố</label>
                    <select name="tinh" id="modal_tinh" class="acc-form-select">
                        <option value="">-- Chọn Tỉnh/TP --</option>
                    </select>
                </div>

                <div class="acc-form-row">
                    <div class="acc-form-group">
                        <label class="acc-form-label">Quận / Huyện</label>
                        <select name="quan_huyen" id="modal_quan" class="acc-form-select">
                            <option value="">-- Chọn Quận/Huyện --</option>
                        </select>
                    </div>
                    <div class="acc-form-group">
                        <label class="acc-form-label">Phường / Xã</label>
                        <select name="phuong_xa" id="modal_phuong" class="acc-form-select">
                            <option value="">-- Chọn Phường/Xã --</option>
                        </select>
                    </div>
                </div>

                <div class="acc-form-group">
                    <label class="acc-form-label">Địa chỉ cụ thể *</label>
                    <input type="text" name="diachi_cuthe" class="acc-form-input" placeholder="Số nhà, tên đường...">
                </div>
            </form>
        </div>
        <div class="acc-modal-footer">
            <button class="acc-btn acc-btn-outline btn-cancel-modal">Hủy</button>
            <button class="acc-btn acc-btn-primary" id="btnSaveAddress"><i class="fas fa-save"></i> Lưu</button>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>assets/js/account.js"></script>

<?php include BASE_PATH . 'includes/footer.php'; ?>
