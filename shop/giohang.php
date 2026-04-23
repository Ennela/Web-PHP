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
    require_once BASE_PATH . 'includes/inventory_helper.php';

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
            global $con;
            $makh = isset($_SESSION['makh']) ? intval($_SESSION['makh']) : 0;
            $preferredSize = null;
            if ($makh > 0) {
                $sizeResult = mysqli_query($con, "SELECT `size_value` FROM `tbl_sizegiay` WHERE `makh` = $makh AND `he_size` = 'EU' ORDER BY `ngaycapnhat` DESC LIMIT 1");
                if ($sizeResult && $sizeRow = mysqli_fetch_assoc($sizeResult)) {
                    $preferredSize = (int)$sizeRow['size_value'];
                }
            }

            if (isset($_POST['quantity'])) {
                foreach ($_POST['quantity'] as $masp => $quantity) {
                    if ($quantity == 0) {
                        unset($_SESSION["giohang"][$masp]);
                    } else {
                        $currentSize = isset($_POST['size'][$masp]) ? (int)$_POST['size'][$masp] : null;
                        
                        if ($add && empty($currentSize) && $preferredSize) {
                            $stockCheck = mysqli_query($con, "SELECT `soluong` FROM `tbl_tonkho` WHERE `masp` = " . (int)$masp . " AND `size` = $preferredSize");
                            if ($stockCheck && $stockRow = mysqli_fetch_assoc($stockCheck)) {
                                if ((int)$stockRow['soluong'] > 0) {
                                    $currentSize = $preferredSize;
                                }
                            }
                        }

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

                if ($add && empty($size) && $preferredSize) {
                    $stockCheck = mysqli_query($con, "SELECT `soluong` FROM `tbl_tonkho` WHERE `masp` = " . (int)$masp . " AND `size` = $preferredSize");
                    if ($stockCheck && $stockRow = mysqli_fetch_assoc($stockCheck)) {
                        if ((int)$stockRow['soluong'] > 0) {
                            $size = $preferredSize;
                        }
                    }
                }

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
        
        // Query stock for all cart products
        $cartMasps = array_keys($_SESSION['giohang']);
        $cartStockData = [];
        if (!empty($cartMasps)) {
            $stockResult = mysqli_query($con, "SELECT `masp`, `size`, `soluong` FROM `tbl_tonkho` WHERE `masp` IN (" . implode(',', array_map('intval', $cartMasps)) . ") ORDER BY `masp`, `size`");
            if ($stockResult) {
                while ($sr = mysqli_fetch_assoc($stockResult)) {
                    $cartStockData[(int)$sr['masp']][(int)$sr['size']] = (int)$sr['soluong'];
                }
            }
        }
    }
    
    // Check for stock issues
    $hasStockIssues = false;
    $stockWarnings = [];

    include BASE_PATH . 'includes/header.php';
?>


<style>
/* ===== CART PAGE (MOBILE-FIRST) ===== */
.cart-section {
    padding: clamp(90px, 12vw, 120px) 0 clamp(40px, 8vw, 80px);
    background-color: #f1f5f9;
}
.editorial-heading {
    font-family: 'Montserrat', sans-serif;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: clamp(1px, 0.5vw, 2px);
    color: #0f172a;
    margin-bottom: 10px;
    font-size: clamp(1.5rem, 4vw, 2.8rem);
}
.editorial-subheading {
    color: #64748b;
    font-size: clamp(1rem, 2vw, 1.15rem);
    margin-bottom: clamp(25px, 4vw, 40px);
    font-weight: 300;
}
.cart-items-wrapper {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    padding: clamp(12px, 3vw, 30px);
    margin-bottom: 30px;
    overflow: hidden; /* Prevent inner elements overflowing */
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
.cart-product-item .row {
    gap: 5px; /* Default for mobile */
}
@media (min-width: 768px) {
    .cart-product-item .row {
        gap: 0;
    }
}
.cart-size-col {
    position: relative;
    min-height: 50px;
}
.cart-product-name {
    font-weight: 800;
    font-size: clamp(0.95rem, 2.5vw, 1.1rem);
    color: #1e293b;
    text-decoration: none;
    transition: color 0.3s;
    display: block;
    word-break: break-word; /* Prevent overflow */
}
@media (hover: hover) {
    .cart-product-name:hover {
        color: #3b82f6;
    }
}
.cart-price {
    font-weight: 700;
    font-size: clamp(1rem, 2.5vw, 1.2rem);
    color: #2563eb;
    transition: opacity 0.3s ease;
}
.cart-price.updating {
    opacity: 0.4;
}
.quantity-input {
    width: clamp(40px, 8vw, 56px);
    height: 44px; /* Touch target */
    text-align: center;
    font-weight: 800;
    border: none;
    border-top: 2px solid #e2e8f0;
    border-bottom: 2px solid #e2e8f0;
    padding: 0;
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
    margin: 0 auto; /* Center on mobile by default */
}
@media (min-width: 768px) {
    .qty-cart-control {
        margin: 0; /* Align left on md+ */
    }
}
.qty-cart-btn {
    background: #1e293b;
    color: #fff;
    border: none;
    width: clamp(40px, 8vw, 44px);
    height: 44px; /* Touch target min 44px */
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
    line-height: 1;
}
@media (hover: hover) {
    .qty-cart-btn:hover {
        background: #3b82f6;
    }
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
    min-height: 44px; /* Touch target */
    width: 100%;
}
@media (hover: hover) {
    .cart-size-select:hover {
        border-color: #3b82f6;
    }
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
    white-space: nowrap;
}

.cart-summary {
    background: #fff;
    color: #1e293b;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    padding: clamp(20px, 4vw, 30px);
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
    font-size: clamp(1.1rem, 2.5vw, 1.3rem);
    color: #0f172a;
}
.cart-summary h4 {
    display: flex;
    justify-content: space-between;
    font-size: 1.05rem;
    margin-bottom: 15px;
    color: #64748b;
    flex-wrap: wrap; /* Prevent overflow */
}
.cart-summary h4 .price {
    color: #1e293b;
    font-weight: 700;
}
.cart-summary h4.total {
    font-size: clamp(1.2rem, 3vw, 1.3rem);
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
    min-height: 48px; /* Touch target */
    display: flex;
    align-items: center;
    justify-content: center;
}
@media (hover: hover) {
    .btn-editorial-light:hover {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
    }
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
    min-height: 48px; /* Touch target */
    display: flex;
    align-items: center;
    justify-content: center;
}
@media (hover: hover) {
    .btn-editorial-outline-light:hover {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: #fff;
        border-color: #3b82f6;
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
    }
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
    min-height: 48px; /* Touch target */
    display: flex;
    align-items: center;
    justify-content: center;
}
@media (hover: hover) {
    .btn-editorial-danger:hover {
        background: linear-gradient(135deg, #1e293b, #334155);
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(30, 41, 59, 0.3);
    }
}

/* Delete button */
.btn-delete-cart {
    color: #ef4444;
    border: none;
    background: transparent;
    min-width: 44px;
    min-height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: color 0.2s;
}
@media (hover: hover) {
    .btn-delete-cart:hover {
        color: #b91c1c;
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
                                                    <div class="col-4 col-md-2 mb-3 mb-md-0 cart-size-col">
                                                        <span class="cart-size-label">Size</span>
                                                        <?php
                                                        $itemStockMap = $cartStockData[$row['masp']] ?? [];
                                                        $selectedSizeStock = (!empty($itemSize) && isset($itemStockMap[$itemSize])) ? $itemStockMap[$itemSize] : -1;
                                                        $sizeOutOfStock = ($selectedSizeStock === 0);
                                                        $sizeOverQty = ($selectedSizeStock > 0 && $itemQty > $selectedSizeStock);
                                                        if ($sizeOutOfStock || $sizeOverQty || empty($itemSize)) $hasStockIssues = true;
                                                        ?>
                                                        <select name="size[<?= $row['masp'] ?>]" class="cart-size-select <?= $sizeOutOfStock ? 'stock-error-border' : '' ?>" data-masp="<?= $row['masp'] ?>">
                                                            <option value="">--</option>
                                                            <?php
                                                            $sizes = [36, 37, 38, 39, 40, 41, 42, 43, 44];
                                                            foreach ($sizes as $s):
                                                                $sStock = $itemStockMap[$s] ?? 0;
                                                                $isDisabled = ($sStock <= 0);
                                                            ?>
                                                            <option value="<?= $s ?>" 
                                                                    <?= ($itemSize == $s) ? 'selected' : '' ?>
                                                                    <?= $isDisabled ? 'disabled' : '' ?>
                                                                    data-stock="<?= $sStock ?>">
                                                                <?= $s ?><?= $isDisabled ? ' (Hết hàng)' : '' ?>
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <?php if (empty($itemSize)): ?>
                                                        <div class="cart-size-warning" data-masp="<?= $row['masp'] ?>">⚠ Chọn size</div>
                                                        <?php elseif ($sizeOutOfStock): ?>
                                                        <div class="cart-size-warning" data-masp="<?= $row['masp'] ?>">⚠ Hết hàng</div>
                                                        <?php elseif ($sizeOverQty): ?>
                                                        <div class="cart-size-warning" data-masp="<?= $row['masp'] ?>">⚠ Còn <?= $selectedSizeStock ?></div>
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
                                                        <button type="button" class="btn-delete-cart text-danger border-0 bg-transparent" title="Xóa" onclick="cartDeleteItem(<?= $row['masp'] ?>)"><i class="fas fa-trash-alt fs-5"></i></button>
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
                                    <div class="alert-stock-issue" id="global-stock-alert" style="background:#fef2f2; border:1px solid #fecaca; border-radius:10px; padding:12px 16px; margin-bottom:14px; color:#dc2626; font-size:0.85rem; font-weight:600; display:<?= $hasStockIssues ? 'block' : 'none' ?>;">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> Giỏ hàng có sản phẩm hết hàng hoặc vượt tồn kho. Vui lòng kiểm tra lại trước khi thanh toán.
                                    </div>
                                    <button class="btn-editorial-outline-light" id="btn-cod" name="dathang" type="submit" <?= $hasStockIssues ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' ?>>THANH TOÁN TIỀN MẶT</button>
                                    <button class="btn-editorial-danger" id="btn-vnpay" name="thanhtoanonline" type="submit" <?= $hasStockIssues ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' ?>><i class="fas fa-credit-card me-2"></i> THANH TOÁN VNPAY</button>
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
        
        let qty = Math.max(1, parseInt(qtyInput.value) || 1);
        
        // Kiểm tra tồn kho trước khi gửi AJAX
        if (sizeSelect && sizeSelect.value) {
            const selectedOption = sizeSelect.options[sizeSelect.selectedIndex];
            const maxStock = parseInt(selectedOption.dataset.stock) || 999;
            if (qty > maxStock) {
                qty = maxStock;
                qtyInput.value = qty;
                showStockAlert(masp, maxStock);
            }
        }
        
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
        }, (res) => {
            if (res.stockWarning) {
                showStockAlert(masp, 0);
            }
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
    
    // Kiểm tra tồn kho
    const sizeSelect = document.querySelector('.cart-size-select[data-masp="' + masp + '"]');
    if (sizeSelect && sizeSelect.value) {
        const selectedOption = sizeSelect.options[sizeSelect.selectedIndex];
        const maxStock = parseInt(selectedOption.dataset.stock) || 999;
        if (val > maxStock) {
            val = maxStock;
            showStockAlert(masp, maxStock);
        }
    }
    
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

// Hiển thị cảnh báo tồn kho
function showStockAlert(masp, maxStock) {
    // Xóa cảnh báo cũ nếu có
    const existingAlert = document.querySelector('.stock-alert-toast');
    if (existingAlert) existingAlert.remove();
    
    const alertDiv = document.createElement('div');
    alertDiv.className = 'stock-alert-toast';
    alertDiv.innerHTML = '<i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i> Số lượng vượt quá tồn kho! Chỉ còn <strong>' + maxStock + '</strong> sản phẩm.';
    alertDiv.style.cssText = 'position:fixed; top:20px; right:20px; z-index:99999; background:linear-gradient(135deg,#ef4444,#dc2626); color:#fff; padding:14px 24px; border-radius:12px; font-weight:600; font-size:0.88rem; box-shadow:0 8px 30px rgba(239,68,68,0.4); animation:slideInRight 0.3s ease; max-width:400px;';
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.style.opacity = '0';
        alertDiv.style.transform = 'translateX(20px)';
        alertDiv.style.transition = 'all 0.3s ease';
        setTimeout(() => alertDiv.remove(), 300);
    }, 3000);
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
            }, function(res) {
                // Re-check all stock issues and update global alert + buttons
                recheckStockIssues(res);
            });
        });
    });
});

// ===== RE-CHECK STOCK ISSUES AFTER AJAX =====
function recheckStockIssues(res) {
    let hasIssues = false;
    const stockData = res.stock || {};
    
    document.querySelectorAll('.cart-product-item').forEach(item => {
        const masp = item.dataset.masp;
        const sizeSelect = item.querySelector('.cart-size-select');
        const qtyInput = item.querySelector('.quantity-input');
        const selectedSize = sizeSelect ? sizeSelect.value : '';
        const qty = qtyInput ? parseInt(qtyInput.value) || 1 : 1;
        
        // Remove old warning
        const oldWarning = item.querySelector('.cart-size-warning');
        if (oldWarning) oldWarning.remove();
        
        if (!selectedSize) {
            // No size selected
            hasIssues = true;
            const w = document.createElement('div');
            w.className = 'cart-size-warning';
            w.setAttribute('data-masp', masp);
            w.textContent = '⚠ Chọn size';
            if (sizeSelect) sizeSelect.parentElement.appendChild(w);
        } else {
            // Check stock for selected size
            const sizeStock = (stockData[masp] && stockData[masp][selectedSize]) ? stockData[masp][selectedSize] : 0;
            if (sizeStock <= 0) {
                hasIssues = true;
                const w = document.createElement('div');
                w.className = 'cart-size-warning';
                w.setAttribute('data-masp', masp);
                w.textContent = '⚠ Hết hàng';
                if (sizeSelect) sizeSelect.parentElement.appendChild(w);
            } else if (qty > sizeStock) {
                hasIssues = true;
                const w = document.createElement('div');
                w.className = 'cart-size-warning';
                w.setAttribute('data-masp', masp);
                w.textContent = '⚠ Còn ' + sizeStock;
                if (sizeSelect) sizeSelect.parentElement.appendChild(w);
            }
        }
    });
    
    // Update global alert and buttons
    const alertEl = document.getElementById('global-stock-alert');
    const btnCod = document.getElementById('btn-cod');
    const btnVnpay = document.getElementById('btn-vnpay');
    
    if (hasIssues) {
        if (alertEl) alertEl.style.display = 'block';
        if (btnCod) { btnCod.disabled = true; btnCod.style.opacity = '0.5'; btnCod.style.cursor = 'not-allowed'; }
        if (btnVnpay) { btnVnpay.disabled = true; btnVnpay.style.opacity = '0.5'; btnVnpay.style.cursor = 'not-allowed'; }
    } else {
        if (alertEl) alertEl.style.display = 'none';
        if (btnCod) { btnCod.disabled = false; btnCod.style.opacity = '1'; btnCod.style.cursor = 'pointer'; }
        if (btnVnpay) { btnVnpay.disabled = false; btnVnpay.style.opacity = '1'; btnVnpay.style.cursor = 'pointer'; }
    }
}
</script>

<?php include BASE_PATH . 'includes/footer.php'; ?>