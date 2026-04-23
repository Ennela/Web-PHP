<?php
require_once dirname(__DIR__) . '/config.php';
    session_start();
    date_default_timezone_set('Asia/Ho_Chi_Minh');
    if (isset($_GET['login'])) {
        $dangxuat = $_GET['login'];
    } else {
        $dangxuat = '';
    }
    if ($dangxuat == 'dangxuat') {
        session_destroy();
        header('Location: giohang.php');
    }
    include BASE_PATH . 'includes/connect.php';

    // ===== MIGRATE OLD SESSION FORMAT =====
    // Old format: $_SESSION['giohang'][$masp] = quantity (int)
    // New format: $_SESSION['giohang'][$masp] = ['quantity' => int, 'size' => int|null]
    if (isset($_SESSION['giohang']) && is_array($_SESSION['giohang'])) {
        foreach ($_SESSION['giohang'] as $masp => $val) {
            if (!is_array($val)) {
                $_SESSION['giohang'][$masp] = ['quantity' => (int)$val, 'size' => null];
            }
        }
    }

    if (!isset($_SESSION['giohang'])) {
        $_SESSION['giohang']
            = array(); // nếu như chưa tồn tại thì tạo từ một mảng rỗng
    }
    if (isset($_GET['action'])) {
        function update_cart($add = false)
        {
            if (isset($_POST['quantity'])) {
                foreach ($_POST['quantity'] as $masp => $quantity) {
                    if ($quantity == 0) {
                        unset($_SESSION["giohang"][$masp]);
                    } else {
                        $currentSize = isset($_POST['size'][$masp]) ? (int)$_POST['size'][$masp] : null;
                        if ($add) {
                            $existingQty = isset($_SESSION["giohang"][$masp]) ? (int)$_SESSION["giohang"][$masp]['quantity'] : 0;
                            $_SESSION["giohang"][$masp] = [
                                'quantity' => $existingQty + (int)$quantity,
                                'size' => $currentSize ?: (isset($_SESSION["giohang"][$masp]) ? $_SESSION["giohang"][$masp]['size'] : null)
                            ];
                        } else {
                            $_SESSION["giohang"][$masp] = [
                                'quantity' => (int)$quantity,
                                'size' => $currentSize ?: (isset($_SESSION["giohang"][$masp]) ? $_SESSION["giohang"][$masp]['size'] : null)
                            ];
                        }
                    }
                }
            } elseif (isset($_GET['masp'])) {
                $masp = $_GET['masp'];
                $quantity = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1;
                $size = isset($_GET['size']) ? (int)$_GET['size'] : null;
                if ($add) {
                    $existingQty = isset($_SESSION["giohang"][$masp]) ? (int)$_SESSION["giohang"][$masp]['quantity'] : 0;
                    $_SESSION["giohang"][$masp] = [
                        'quantity' => $existingQty + $quantity,
                        'size' => $size ?: (isset($_SESSION["giohang"][$masp]) ? $_SESSION["giohang"][$masp]['size'] : null)
                    ];
                } else {
                    $_SESSION["giohang"][$masp] = [
                        'quantity' => $quantity,
                        'size' => $size ?: (isset($_SESSION["giohang"][$masp]) ? $_SESSION["giohang"][$masp]['size'] : null)
                    ];
                }
            }
        }

        switch ($_GET['action']) {
            case "add":
                update_cart(true);
                header('Location:./giohang.php');// cho đẹp đường dẫn
                break;

            case "delete":
                if (isset($_GET['masp'])) {
                    unset($_SESSION["giohang"][$_GET['masp']]);
                }
                header('Location:./giohang.php');// cho đẹp đường dẫn
                break;
            case "submit":
                if (isset($_POST['capnhat'])) { //Cập nhật số lượng và size sản phẩm
                    update_cart();
                    header('Location: ./giohang.php');
                } elseif (isset($_POST['dathang'])) { //Đặt hàng sản phẩm
                    $chuyen = $_POST['quantity'];
                    $_SESSION['chuyen'] = $chuyen;
                    // Also pass sizes to checkout
                    $_SESSION['chuyen_size'] = isset($_POST['size']) ? $_POST['size'] : [];

                    header('Location: ./infodathang.php');
                } elseif (isset($_POST['thanhtoanonline'])) { //Đặt hàng sản phẩm
                    $chuyen = $_POST['quantity'];
                    $_SESSION['chuyen'] = $chuyen;
                    $_SESSION['chuyen_size'] = isset($_POST['size']) ? $_POST['size'] : [];

                    header('Location: ./dathangonline.php');
                }


                break;
        }
    }
    if (!empty($_SESSION["giohang"])) {
        $products = mysqli_query($con,
            "SELECT * FROM `tbl_qlsanpham` WHERE `masp` IN (" . implode(",", array_keys($_SESSION["giohang"])) . ")");
    }
    include BASE_PATH . 'includes/header.php';
?>


<style>
    .cart-section {
        padding: 120px 0 80px;
        background-color: #f1f5f9;
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
        margin-bottom: 40px;
        font-weight: 300;
    }
    .cart-items-wrapper {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        padding: 30px;
        margin-bottom: 30px;
    }
    .cart-product-item {
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 20px;
        margin-bottom: 20px;
    }
    .cart-product-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
        margin-bottom: 0;
    }
    .cart-product-name {
        font-weight: 800;
        font-size: 1.1rem;
        color: #1e293b;
        text-decoration: none;
        transition: color 0.3s;
    }
    .cart-product-name:hover {
        color: #3b82f6;
    }
    .cart-price {
        font-weight: 700;
        font-size: 1.2rem;
        color: #2563eb;
        transition: opacity 0.3s ease;
    }
    .cart-price.updating {
        opacity: 0.4;
    }
    .quantity-input {
        width: 56px;
        text-align: center;
        font-weight: 800;
        border: none;
        border-top: 2px solid #e2e8f0;
        border-bottom: 2px solid #e2e8f0;
        padding: 6px 0;
        font-size: 0.95rem;
        color: #1e293b;
        outline: none;
        -moz-appearance: textfield;
    }
    .quantity-input::-webkit-inner-spin-button,
    .quantity-input::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .qty-cart-control {
        display: flex;
        align-items: center;
        border-radius: 10px;
        overflow: hidden;
        width: fit-content;
    }
    .qty-cart-btn {
        background: #1e293b;
        color: #fff;
        border: none;
        width: 34px;
        height: 36px;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
        line-height: 1;
    }
    .qty-cart-btn:hover {
        background: #3b82f6;
    }

    /* Auto-save indicator */
    .cart-save-status {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: #1e293b;
        color: #fff;
        padding: 10px 20px;
        border-radius: 30px;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 8px;
        opacity: 0;
        transform: translateY(10px);
        transition: opacity 0.3s ease, transform 0.3s ease;
        z-index: 100;
        box-shadow: 0 8px 30px rgba(0,0,0,0.2);
        pointer-events: none;
    }
    .cart-save-status.show {
        opacity: 1;
        transform: translateY(0);
    }
    .cart-save-status.error {
        background: #ef4444;
    }
    .cart-save-spinner {
        width: 14px;
        height: 14px;
        border: 2px solid rgba(255,255,255,0.3);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Delete animation */
    .cart-product-item.removing {
        opacity: 0;
        transform: translateX(-30px);
        max-height: 0;
        padding: 0;
        margin: 0;
        overflow: hidden;
        transition: opacity 0.3s ease, transform 0.3s ease, max-height 0.4s ease 0.1s, padding 0.4s ease 0.1s, margin 0.4s ease 0.1s;
    }

    /* Size selector in cart */
    .cart-size-select {
        appearance: none;
        -webkit-appearance: none;
        background: #fff;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        padding: 6px 30px 6px 12px;
        font-size: 0.88rem;
        font-weight: 700;
        color: #1e293b;
        cursor: pointer;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3e%3cpath fill='%2364748b' d='M6 8L1 3h10z'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 10px center;
        min-width: 75px;
    }
    .cart-size-select:hover {
        border-color: #3b82f6;
    }
    .cart-size-select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
    }
    .cart-size-label {
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #94a3b8;
        display: block;
        margin-bottom: 4px;
    }
    .cart-size-warning {
        font-size: 0.72rem;
        color: #ef4444;
        font-weight: 600;
        margin-top: 3px;
    }

    .cart-summary {
        background: #fff;
        color: #1e293b;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        padding: 30px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }
    .cart-summary h3 {
        font-family: 'Montserrat', sans-serif;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 25px;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 15px;
        font-size: 1.3rem;
        color: #0f172a;
    }
    .cart-summary h4 {
        display: flex;
        justify-content: space-between;
        font-size: 1.05rem;
        margin-bottom: 15px;
        color: #64748b;
    }
    .cart-summary h4 .price {
        color: #1e293b;
        font-weight: 700;
    }
    .cart-summary h4.total {
        font-size: 1.3rem;
        font-weight: 800;
        color: #2563eb;
        margin-top: 20px;
        border-top: 1px solid #e2e8f0;
        padding-top: 20px;
    }
    .cart-summary h4.total .price {
        color: #2563eb;
    }
    .btn-editorial-light {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 14px 20px;
        font-weight: 700;
        font-size: 0.9rem;
        text-transform: uppercase;
        width: 100%;
        margin-bottom: 10px;
        transition: all 0.3s ease;
        letter-spacing: 0.5px;
    }
    .btn-editorial-light:hover {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
    }
    .btn-editorial-outline-light {
        background: transparent;
        color: #3b82f6;
        border: 2px solid #3b82f6;
        border-radius: 10px;
        padding: 14px 20px;
        font-weight: 700;
        font-size: 0.9rem;
        text-transform: uppercase;
        width: 100%;
        margin-bottom: 10px;
        transition: all 0.3s ease;
        cursor: pointer;
        letter-spacing: 0.5px;
    }
    .btn-editorial-outline-light:hover {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: #fff;
        border-color: #3b82f6;
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
    }
    .btn-editorial-danger {
        background: linear-gradient(135deg, #0f172a, #1e293b);
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 14px 20px;
        font-weight: 700;
        font-size: 0.9rem;
        text-transform: uppercase;
        width: 100%;
        transition: all 0.3s ease;
        cursor: pointer;
        letter-spacing: 0.5px;
    }
    .btn-editorial-danger:hover {
        background: linear-gradient(135deg, #1e293b, #334155);
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(30, 41, 59, 0.3);
    }

    /* ===== CART RESPONSIVE ===== */
    @media (max-width: 768px) {
        .cart-section {
            padding: 100px 0 50px;
        }
        .editorial-heading {
            font-size: 1.8rem;
        }
        .editorial-subheading {
            font-size: 1rem;
            margin-bottom: 25px;
        }
        .cart-items-wrapper {
            padding: 15px;
        }
        .cart-product-name {
            font-size: 0.95rem;
        }
        .cart-price {
            font-size: 1rem;
        }
        .cart-summary {
            padding: 20px;
        }
        .cart-summary h3 {
            font-size: 1.1rem;
        }
    }
    @media (max-width: 576px) {
        .cart-section {
            padding: 90px 0 40px;
        }
        .editorial-heading {
            font-size: 1.5rem;
            letter-spacing: 1px;
        }
        .cart-items-wrapper {
            padding: 12px;
        }
        .cart-product-item .row {
            gap: 5px;
        }
        .qty-cart-control {
            margin: 0 auto;
        }
    }
</style>

<main class="page shopping-cart-page">
    <section class="cart-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="editorial-heading">GIỎ HÀNG</h2>
                <p class="editorial-subheading">Kiểm tra lại sản phẩm và hoàn tất đặt hàng.</p>
            </div>
            <div class="content">
                <form method="post" action="<?php echo BASE_URL; ?>shop/giohang.php?action=submit">
                    <div class="row">
                        <div class="col-lg-8 mb-4">
                            <div class="cart-items-wrapper">
                                <?php
                                    if (!empty($products)) {
                                        $num = 1;
                                        $total = 0;
                                        while ($row = mysqli_fetch_array($products)) {
                                            $cartItem = $_SESSION["giohang"][$row['masp']];
                                            $itemQty = is_array($cartItem) ? $cartItem['quantity'] : (int)$cartItem;
                                            $itemSize = is_array($cartItem) ? ($cartItem['size'] ?? null) : null;
                                ?>
                                            <div class="cart-product-item" data-masp="<?= $row['masp'] ?>" data-price="<?= $row['giasanpham'] ?>">
                                                <div class="row align-items-center text-center text-md-start">
                                                    <div class="col-md-2 mb-3 mb-md-0">
                                                        <img class="img-fluid d-block mx-auto image" style="border-radius: 4px;" src="<?php echo BASE_URL; ?>admin/<?= $row['anhdaidien'] ?>">
                                                    </div>
                                                    <div class="col-md-3 mb-3 mb-md-0">
                                                        <a class="cart-product-name" href="<?php echo BASE_URL; ?>shop/chitietsanpham.php?masp=<?= $row['masp'] ?>"><?= $row['tensp'] ?></a>
                                                    </div>
                                                    <div class="col-4 col-md-2 mb-3 mb-md-0">
                                                        <span class="cart-size-label">Size</span>
                                                        <select name="size[<?= $row['masp'] ?>]" class="cart-size-select" data-masp="<?= $row['masp'] ?>">
                                                            <option value="">--</option>
                                                            <?php
                                                            $sizes = [36, 37, 38, 39, 40, 41, 42, 43, 44];
                                                            foreach ($sizes as $s):
                                                            ?>
                                                            <option value="<?= $s ?>" <?= ($itemSize == $s) ? 'selected' : '' ?>><?= $s ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <?php if (empty($itemSize)): ?>
                                                        <div class="cart-size-warning">⚠ Chọn size</div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col-4 col-md-2 mb-3 mb-md-0">
                                                        <span class="cart-size-label">Số lượng</span>
                                                        <div class="qty-cart-control mx-auto mx-md-0">
                                                            <button type="button" class="qty-cart-btn" onclick="cartChangeQty(<?= $row['masp'] ?>, -1)">−</button>
                                                            <input type="number" min="1" value="<?= $itemQty ?>" name="quantity[<?= $row['masp'] ?>]" class="quantity-input" data-masp="<?= $row['masp'] ?>">
                                                            <button type="button" class="qty-cart-btn" onclick="cartChangeQty(<?= $row['masp'] ?>, 1)">+</button>
                                                        </div>
                                                    </div>
                                                    <div class="col-3 col-md-2 mb-3 mb-md-0 text-md-end">
                                                        <span class="cart-price" id="price-<?= $row['masp'] ?>"><?= number_format($row['giasanpham'] * $itemQty, 0, ",", ".") ?> đ</span>
                                                    </div>
                                                    <div class="col-1 col-md-1 text-md-end">
                                                        <button type="button" class="text-danger border-0 bg-transparent" title="Xóa" onclick="cartDeleteItem(<?= $row['masp'] ?>)"><i class="fas fa-trash-alt fs-5"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php
                                            $total += $row['giasanpham'] * $itemQty;
                                            $num++;
                                        }
                                    } else {
                                        echo "<div class='text-center py-5'>
                                                <i class='fas fa-shopping-basket fs-1 text-muted mb-3 d-block'></i>
                                                <p class='fs-5 text-muted'>Bạn chưa có sản phẩm nào trong giỏ hàng</p>
                                                <a href='".BASE_URL."shop/shop.php' class='btn btn-dark mt-3 px-4 py-2 fw-bold'>MUA SẮM NGAY</a>
                                              </div>";
                                    }
                                ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="cart-summary">
                                <h3>TÓM LƯỢC ORDER</h3>
                                <?php if (isset($total)) { ?>
                                    <h4><span class="text">Tạm tính</span><span class="price" id="summary-subtotal"><?= number_format($total, 0, ",", ".") ?> đ</span></h4>
                                    <h4><span class="text">Chiết khấu</span><span class="price">0 đ</span></h4>
                                    <h4><span class="text">Phí vận chuyển</span><span class="price">0 đ</span></h4>
                                    <h4 class="total"><span class="text">TỔNG CỘNG</span><span class="price" id="summary-total"><?= number_format($total, 0, ",", ".") ?> đ</span></h4>
                                <?php } else { ?>
                                    <h4 class="total"><span class="text">TỔNG CỘNG</span><span class="price" id="summary-total">0 đ</span></h4>
                                <?php } ?>
                                
                                <div class="mt-4">
                                    <button class="btn-editorial-outline-light" name="dathang" type="submit">THANH TOÁN TIỀN MẶT</button>
                                    <button class="btn-editorial-danger" name="thanhtoanonline" type="submit"><i class="fas fa-credit-card me-2"></i> THANH TOÁN VNPAY</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>

<!-- Auto-save status indicator -->
<div class="cart-save-status" id="save-status">
    <div class="cart-save-spinner" id="save-spinner"></div>
    <span id="save-text">Đang lưu...</span>
</div>

<script>
// ===== CART AUTO-UPDATE SYSTEM =====
const AJAX_URL = '<?php echo BASE_URL; ?>shop/cart_update_ajax.php';
let saveTimeout = null;
let isSaving = false;

// Show save status toast
function showStatus(text, type = 'saving') {
    const el = document.getElementById('save-status');
    const spinner = document.getElementById('save-spinner');
    const textEl = document.getElementById('save-text');
    
    textEl.textContent = text;
    el.classList.remove('error');
    
    if (type === 'saving') {
        spinner.style.display = 'block';
    } else if (type === 'saved') {
        spinner.style.display = 'none';
        textEl.textContent = '✓ ' + text;
    } else if (type === 'error') {
        spinner.style.display = 'none';
        el.classList.add('error');
    }
    
    el.classList.add('show');
    
    if (type !== 'saving') {
        setTimeout(() => el.classList.remove('show'), 1800);
    }
}

// Send AJAX update
function ajaxUpdate(masp, data, onSuccess) {
    showStatus('Đang lưu...', 'saving');
    isSaving = true;
    
    fetch(AJAX_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ masp: masp, ...data })
    })
    .then(r => r.json())
    .then(res => {
        isSaving = false;
        if (res.success) {
            showStatus('Đã cập nhật', 'saved');
            // Update all prices and totals from server response
            updatePricesFromResponse(res);
            if (onSuccess) onSuccess(res);
        } else {
            showStatus('Lỗi cập nhật', 'error');
        }
    })
    .catch(() => {
        isSaving = false;
        showStatus('Lỗi kết nối', 'error');
    });
}

// Update displayed prices from AJAX response
function updatePricesFromResponse(res) {
    // Update each item's total price
    for (const [masp, item] of Object.entries(res.cart)) {
        const priceEl = document.getElementById('price-' + masp);
        if (priceEl) {
            priceEl.classList.add('updating');
            setTimeout(() => {
                priceEl.textContent = item.itemTotalFormatted;
                priceEl.classList.remove('updating');
            }, 150);
        }
    }
    
    // Update summary totals
    const subtotalEl = document.getElementById('summary-subtotal');
    const totalEl = document.getElementById('summary-total');
    if (subtotalEl) subtotalEl.textContent = res.grandTotalFormatted;
    if (totalEl) totalEl.textContent = res.grandTotalFormatted;
}

// Debounced update (for typing in quantity field)
function debouncedUpdate(masp) {
    clearTimeout(saveTimeout);
    saveTimeout = setTimeout(() => {
        const qtyInput = document.querySelector('.quantity-input[data-masp="' + masp + '"]');
        const sizeSelect = document.querySelector('.cart-size-select[data-masp="' + masp + '"]');
        if (!qtyInput) return;
        
        const qty = Math.max(1, parseInt(qtyInput.value) || 1);
        qtyInput.value = qty;
        
        // Update local price immediately
        const item = document.querySelector('.cart-product-item[data-masp="' + masp + '"]');
        if (item) {
            const unitPrice = parseInt(item.dataset.price);
            const priceEl = document.getElementById('price-' + masp);
            if (priceEl) priceEl.textContent = formatCurrency(unitPrice * qty) + ' đ';
        }
        
        ajaxUpdate(masp, {
            quantity: qty,
            size: sizeSelect ? sizeSelect.value : ''
        });
    }, 400);
}

// Format number as Vietnamese currency
function formatCurrency(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

// Quantity +/- buttons
function cartChangeQty(masp, delta) {
    const input = document.querySelector('.quantity-input[data-masp="' + masp + '"]');
    if (!input) return;
    let val = Math.max(1, (parseInt(input.value) || 1) + delta);
    input.value = val;
    
    // Instant local price update
    const item = document.querySelector('.cart-product-item[data-masp="' + masp + '"]');
    if (item) {
        const unitPrice = parseInt(item.dataset.price);
        const priceEl = document.getElementById('price-' + masp);
        if (priceEl) priceEl.textContent = formatCurrency(unitPrice * val) + ' đ';
    }
    
    // Recalculate local grand total
    recalcLocalTotal();
    
    debouncedUpdate(masp);
}

// Delete item with animation
function cartDeleteItem(masp) {
    const item = document.querySelector('.cart-product-item[data-masp="' + masp + '"]');
    if (!item) return;
    
    // Animate out
    item.style.maxHeight = item.offsetHeight + 'px';
    requestAnimationFrame(() => {
        item.classList.add('removing');
    });
    
    ajaxUpdate(masp, { action: 'delete' }, (res) => {
        // Remove element after animation
        setTimeout(() => {
            item.remove();
            // Check if cart is now empty
            const remaining = document.querySelectorAll('.cart-product-item');
            if (remaining.length === 0) {
                document.querySelector('.cart-items-wrapper').innerHTML = 
                    '<div class="text-center py-5">' +
                    '<i class="fas fa-shopping-basket fs-1 text-muted mb-3 d-block"></i>' +
                    '<p class="fs-5 text-muted">Bạn chưa có sản phẩm nào trong giỏ hàng</p>' +
                    '<a href="<?php echo BASE_URL; ?>shop/shop.php" class="btn btn-dark mt-3 px-4 py-2 fw-bold">MUA SẮM NGAY</a>' +
                    '</div>';
            }
        }, 450);
    });
}

// Recalculate local grand total from all items on page
function recalcLocalTotal() {
    let total = 0;
    document.querySelectorAll('.cart-product-item').forEach(item => {
        const price = parseInt(item.dataset.price) || 0;
        const qtyInput = item.querySelector('.quantity-input');
        const qty = qtyInput ? (parseInt(qtyInput.value) || 1) : 1;
        total += price * qty;
    });
    const subtotalEl = document.getElementById('summary-subtotal');
    const totalEl = document.getElementById('summary-total');
    if (subtotalEl) subtotalEl.textContent = formatCurrency(total) + ' đ';
    if (totalEl) totalEl.textContent = formatCurrency(total) + ' đ';
}

// ===== EVENT LISTENERS =====
document.addEventListener('DOMContentLoaded', () => {
    // Quantity input change
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('input', function() {
            const masp = this.dataset.masp;
            recalcLocalTotal();
            debouncedUpdate(masp);
        });
    });
    
    // Size select change
    document.querySelectorAll('.cart-size-select').forEach(select => {
        select.addEventListener('change', function() {
            const masp = this.dataset.masp;
            const qtyInput = document.querySelector('.quantity-input[data-masp="' + masp + '"]');
            
            // Remove warning if size selected
            const warning = this.parentElement.querySelector('.cart-size-warning');
            if (warning && this.value) warning.remove();
            
            ajaxUpdate(masp, {
                quantity: qtyInput ? parseInt(qtyInput.value) : 1,
                size: this.value
            });
        });
    });
});
</script>

<?php include BASE_PATH . 'includes/footer.php'; ?>