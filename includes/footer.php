<footer class="page-footer dark">
    <div class="container">
        <div class="row">
            <div class="col-6 col-md-3 mb-4 mb-md-0">
                <h5>Bắt đầu</h5>
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>home/trangchu.php">Trang chủ</a></li>
                    <li><a href="<?php echo BASE_URL; ?>auth/dangnhap.php">Đăng nhập</a></li>
                    <li><a href="<?php echo BASE_URL; ?>shop/giohang.php">Giỏ hàng</a></li>
                    <li><a href="<?php echo BASE_URL; ?>shop/tradonhang.php">Tra cứu đơn hàng</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-3 mb-4 mb-md-0">
                <h5>Thông tin</h5>
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>home/lienhe.php">Liên lạc</a></li>
                    <li><a href="<?php echo BASE_URL; ?>home/vechungtoi.php">Giới thiệu</a></li>
                    <li><a href="<?php echo BASE_URL; ?>blog/baiviet.php">Blog</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-3 mb-4 mb-md-0">
                <h5>Hỗ trợ</h5>
                <ul>
                    <li><a href="#">FAQ</a></li>
                    <li><a href="#">SĐT: 0394680113</a></li>
                    <li><a href="#">Diễn đàn</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-3">
                <h5>Hợp pháp</h5>
                <ul>
                    <li><a href="#">Điều kiện dịch vụ</a></li>
                    <li><a href="#">Điều kiện sử dụng</a></li>
                    <li><a href="#">Chính sách riêng tư</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="footer-copyright">
        <p>&copy; <?php echo date('Y'); ?> Shop Giày Thể Thao</p>
    </div>
</footer>

<script src="<?php echo BASE_URL; ?>assets/bootstrap/js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/baguettebox.js/1.10.0/baguetteBox.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/vanilla-zoom.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/theme.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        <?php if (!empty($_SESSION['swal_error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: '<?php echo addslashes($_SESSION['swal_error']); ?>',
                confirmButtonColor: '#3b82f6'
            });
            <?php unset($_SESSION['swal_error']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['swal_success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Thành công!',
                text: '<?php echo addslashes($_SESSION['swal_success']); ?>',
                confirmButtonColor: '#3b82f6'
            });
            <?php unset($_SESSION['swal_success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['swal_warning'])): ?>
            Swal.fire({
                icon: 'warning',
                title: 'Cảnh báo',
                text: '<?php echo addslashes($_SESSION['swal_warning']); ?>',
                confirmButtonColor: '#3b82f6'
            });
            <?php unset($_SESSION['swal_warning']); ?>
        <?php endif; ?>
    });
</script>
</body>
</html>
