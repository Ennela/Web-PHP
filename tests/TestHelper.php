<?php
/**
 * TestHelper - Các hàm tiện ích dùng chung cho test
 */
class TestHelper
{
    private static ?mysqli $con = null;

    /**
     * Lấy kết nối DB test (singleton)
     */
    public static function getConnection(): mysqli
    {
        if (self::$con === null || !@self::$con->ping()) {
            self::$con = new mysqli(TEST_DB_HOST, TEST_DB_USER, TEST_DB_PASS, TEST_DB_NAME);
            if (self::$con->connect_error) {
                throw new \RuntimeException('Test DB connection failed: ' . self::$con->connect_error);
            }
            self::$con->set_charset('utf8mb4');
        }
        return self::$con;
    }

    /**
     * Tạo database test và import schema từ giaythethao2.sql
     */
    public static function setupTestDatabase(): void
    {
        // Kết nối không chọn DB
        $con = new mysqli(TEST_DB_HOST, TEST_DB_USER, TEST_DB_PASS);
        if ($con->connect_error) {
            throw new \RuntimeException('Cannot connect to MySQL: ' . $con->connect_error);
        }
        $con->set_charset('utf8mb4');

        // Tạo DB test nếu chưa có
        $con->query("CREATE DATABASE IF NOT EXISTS `" . TEST_DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $con->select_db(TEST_DB_NAME);

        // Kiểm tra đã có bảng chưa (tránh import lại)
        $result = $con->query("SHOW TABLES LIKE 'tbl_qlsanpham'");
        if ($result->num_rows > 0) {
            $con->close();
            return; // Đã có schema
        }

        // Import schema (chỉ structure, không data) 
        self::importSchemaOnly($con);
        
        // Chèn dữ liệu test mẫu
        self::seedTestData($con);

        $con->close();
    }

    /**
     * Import chỉ phần CREATE TABLE từ SQL file
     */
    private static function importSchemaOnly(mysqli $con): void
    {
        // Drop và tạo lại các bảng theo thứ tự đúng (child trước parent)
        $con->query("SET FOREIGN_KEY_CHECKS = 0");

        // Bảng sản phẩm
        $con->query("DROP TABLE IF EXISTS `tbl_qlsanpham`");
        $con->query("CREATE TABLE `tbl_qlsanpham` (
            `masp` int(11) NOT NULL AUTO_INCREMENT,
            `tensp` varchar(255) DEFAULT NULL,
            `anhgiuoithieu1` varchar(255) DEFAULT NULL,
            `anhgiuoithieu2` varchar(255) DEFAULT NULL,
            `anhdaidien` varchar(255) DEFAULT NULL,
            `giasanpham` int(11) DEFAULT NULL,
            `giagoc` int(11) DEFAULT NULL,
            `noidung` varchar(5000) DEFAULT NULL,
            `ngaytao` int(11) DEFAULT NULL,
            `nhomsp` varchar(255) DEFAULT NULL,
            `ngaycapnhat` int(11) DEFAULT NULL,
            PRIMARY KEY (`masp`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");

        // Bảng đơn hàng  
        $con->query("DROP TABLE IF EXISTS `oder`");
        $con->query("CREATE TABLE `oder` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `order_code` varchar(9) DEFAULT NULL,
            `tenkh` varchar(255) DEFAULT NULL,
            `diachi` varchar(255) DEFAULT NULL,
            `sdt` varchar(255) DEFAULT NULL,
            `note` varchar(255) DEFAULT NULL,
            `ngaytao` int(11) DEFAULT NULL,
            `tongtien` int(11) DEFAULT NULL,
            `donhangthang` int(11) DEFAULT NULL,
            `status` varchar(20) DEFAULT 'PENDING',
            `vnpay_tranId` varchar(100) DEFAULT NULL,
            `makh` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `oder_order_code_uq` (`order_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");

        // Bảng chi tiết đơn hàng
        $con->query("DROP TABLE IF EXISTS `oder_chitiet`");
        $con->query("CREATE TABLE `oder_chitiet` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `madonhang` int(11) NOT NULL,
            `masp` int(11) DEFAULT NULL,
            `quantity` int(11) DEFAULT NULL,
            `size` int(11) DEFAULT NULL,
            `price` int(11) DEFAULT NULL,
            `created_time` int(11) DEFAULT NULL,
            `last_updated` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `1` (`madonhang`),
            KEY `2` (`masp`),
            CONSTRAINT `fk_oder_chitiet_oder` FOREIGN KEY (`madonhang`) REFERENCES `oder` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `fk_oder_chitiet_sp` FOREIGN KEY (`masp`) REFERENCES `tbl_qlsanpham` (`masp`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");

        // Bảng đăng nhập admin
        $con->query("DROP TABLE IF EXISTS `tbl_dangnhap`");
        $con->query("CREATE TABLE `tbl_dangnhap` (
            `mavn` int(11) NOT NULL AUTO_INCREMENT,
            `hoten` varchar(255) DEFAULT NULL,
            `taikhoan` varchar(255) DEFAULT NULL,
            `matkhau` varchar(255) DEFAULT NULL,
            `ngaysinh` int(11) DEFAULT NULL,
            `created_time` int(11) DEFAULT NULL,
            `last_updated` int(11) DEFAULT NULL,
            PRIMARY KEY (`mavn`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");

        // Bảng danh mục
        $con->query("DROP TABLE IF EXISTS `tbl_danhmuc`");
        $con->query("CREATE TABLE `tbl_danhmuc` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `tendanhmuc` varchar(255) DEFAULT NULL,
            `mota` varchar(500) DEFAULT NULL,
            `ngaytao` int(11) DEFAULT NULL,
            `ngaycapnhat` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");

        // Bảng bài đăng
        $con->query("DROP TABLE IF EXISTS `tbl_qlbaidang`");
        $con->query("CREATE TABLE `tbl_qlbaidang` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `nguoiphutrach` varchar(255) NOT NULL,
            `anhgiuoithieu1` varchar(255) DEFAULT NULL,
            `anhgiuoithieu2` varchar(255) DEFAULT NULL,
            `anhdaidien` varchar(255) DEFAULT NULL,
            `tieude` varchar(255) DEFAULT NULL,
            `chedo` varchar(255) DEFAULT NULL,
            `noidung` varchar(1000) DEFAULT NULL,
            `ngaytao` int(11) DEFAULT NULL,
            `ngaycapnhat` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");

        // Bảng thành viên (admin)
        $con->query("DROP TABLE IF EXISTS `tbl_qlthanhvien`");
        $con->query("CREATE TABLE `tbl_qlthanhvien` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `hoten` varchar(255) NOT NULL,
            `gioitinh` varchar(255) DEFAULT NULL,
            `ngaysinh` int(11) DEFAULT NULL,
            `diachicuthe` varchar(255) DEFAULT NULL,
            `tinh` varchar(255) DEFAULT NULL,
            `thanhpho` varchar(255) DEFAULT NULL,
            `phuongxa` varchar(255) DEFAULT NULL,
            `chucvu` varchar(255) DEFAULT NULL,
            `motacongviec` varchar(255) DEFAULT NULL,
            `ngaytao` int(11) DEFAULT NULL,
            `ngaycapnhat` int(11) DEFAULT NULL,
            `taikhoan` varchar(255) DEFAULT NULL,
            `matkhau` varchar(255) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `taikhoan` (`taikhoan`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");

        // Bảng thư viện ảnh
        $con->query("DROP TABLE IF EXISTS `tbl_thuvienanh`");
        $con->query("CREATE TABLE `tbl_thuvienanh` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `masp` int(11) NOT NULL,
            `path` varchar(255) DEFAULT NULL,
            `ngaytao` int(11) DEFAULT NULL,
            `ngaycapnhat` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `tbl_thuvienanh_tbl_qlsanpham_masp_fk` (`masp`),
            CONSTRAINT `tbl_thuvienanh_fk` FOREIGN KEY (`masp`) REFERENCES `tbl_qlsanpham` (`masp`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");

        // Bảng tài khoản khách hàng
        $con->query("DROP TABLE IF EXISTS `tbl_tkkhachhang`");
        $con->query("CREATE TABLE `tbl_tkkhachhang` (
            `makh` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(255) DEFAULT NULL,
            `password` varchar(255) DEFAULT NULL,
            `hoten` varchar(255) DEFAULT NULL,
            `ngaytao` int(11) DEFAULT NULL,
            `email` varchar(255) DEFAULT NULL,
            `sdt` varchar(15) DEFAULT NULL,
            `ngaysinh` date DEFAULT NULL,
            `gioitinh` enum('Nam','Nữ','Khác') DEFAULT NULL,
            `avatar` varchar(255) DEFAULT NULL,
            `last_updated` int(11) DEFAULT NULL,
            PRIMARY KEY (`makh`),
            UNIQUE KEY `username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");

        // Bảng địa chỉ
        $con->query("DROP TABLE IF EXISTS `tbl_diachi`");
        $con->query("CREATE TABLE `tbl_diachi` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `makh` int(11) NOT NULL,
            `hoten` varchar(255) NOT NULL,
            `sdt` varchar(15) NOT NULL,
            `tinh` varchar(100) DEFAULT NULL,
            `quan_huyen` varchar(100) DEFAULT NULL,
            `phuong_xa` varchar(100) DEFAULT NULL,
            `diachi_cuthe` varchar(255) NOT NULL,
            `macdinh` tinyint(1) DEFAULT 0,
            `ngaytao` int(11) DEFAULT NULL,
            `ngaycapnhat` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `fk_diachi_kh` (`makh`),
            CONSTRAINT `fk_diachi_kh_test` FOREIGN KEY (`makh`) REFERENCES `tbl_tkkhachhang`(`makh`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Bảng size giày
        $con->query("DROP TABLE IF EXISTS `tbl_sizegiay`");
        $con->query("CREATE TABLE `tbl_sizegiay` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `makh` int(11) NOT NULL,
            `he_size` enum('EU','US','CM') DEFAULT 'EU',
            `size_value` varchar(10) NOT NULL,
            `ghichu` varchar(255) DEFAULT NULL,
            `ngaytao` int(11) DEFAULT NULL,
            `ngaycapnhat` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `fk_size_kh` (`makh`),
            CONSTRAINT `fk_size_kh_test` FOREIGN KEY (`makh`) REFERENCES `tbl_tkkhachhang`(`makh`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $con->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    /**
     * Chèn dữ liệu test mẫu
     */
    private static function seedTestData(mysqli $con): void
    {
        $now = time();

        // Sản phẩm test
        $con->query("INSERT INTO `tbl_qlsanpham` (`masp`, `tensp`, `anhdaidien`, `giasanpham`, `giagoc`, `noidung`, `ngaytao`, `nhomsp`, `ngaycapnhat`) VALUES
            (1, 'Giày Test Nike Air Force 1', 'uploads/test1.jpg', 1500000, 2000000, 'Mô tả sản phẩm test 1', $now, 'Giày nam', $now),
            (2, 'Giày Test Adidas Ultraboost', 'uploads/test2.jpg', 2500000, 3000000, 'Mô tả sản phẩm test 2', $now, 'Giày nam', $now),
            (3, 'Giày Test Converse Chuck 70', 'uploads/test3.jpg', 900000, 1200000, 'Mô tả sản phẩm test 3', $now, 'Giày nữ', $now)
        ");

        // Tài khoản khách hàng test
        $con->query("INSERT INTO `tbl_tkkhachhang` (`makh`, `username`, `password`, `hoten`, `ngaytao`, `email`, `sdt`) VALUES
            (1, 'testuser', 'test123456', 'Nguyễn Test User', $now, 'test@example.com', '0394680113'),
            (2, 'testuser2', 'pass123456', 'Trần Test User 2', $now, 'test2@example.com', '0912345678')
        ");

        // Admin test
        $con->query("INSERT INTO `tbl_qlthanhvien` (`id`, `hoten`, `taikhoan`, `matkhau`, `chucvu`, `ngaytao`, `ngaycapnhat`) VALUES
            (1, 'Admin Test', 'admin_test', 'admin123', 'Quản lí', $now, $now)
        ");

        // Danh mục test
        $con->query("INSERT INTO `tbl_danhmuc` (`id`, `tendanhmuc`, `mota`, `ngaytao`, `ngaycapnhat`) VALUES
            (1, 'Giày nam', 'Giày thể thao nam', $now, $now),
            (2, 'Giày nữ', 'Giày thể thao nữ', $now, $now)
        ");

        // Đơn hàng test
        $con->query("INSERT INTO `oder` (`id`, `order_code`, `tenkh`, `sdt`, `diachi`, `note`, `tongtien`, `ngaytao`, `status`, `makh`) VALUES
            (1, '123456789', 'Nguyễn Test', '0394680113', 'Hà Nội', 'Ghi chú test', 1500000, $now, 'PENDING', 1),
            (2, '987654321', 'Trần Test', '0912345678', 'HCM', '', 2500000, $now, 'DELIVERED', 1)
        ");

        // Chi tiết đơn hàng test
        $con->query("INSERT INTO `oder_chitiet` (`id`, `madonhang`, `masp`, `quantity`, `size`, `price`, `created_time`, `last_updated`) VALUES
            (1, 1, 1, 1, 42, 1500000, $now, $now),
            (2, 2, 2, 1, 43, 2500000, $now, $now)
        ");
    }

    /**
     * Tạo một user test mới và trả về makh
     */
    public static function createTestUser(string $username = null, string $password = 'test123456'): int
    {
        $con = self::getConnection();
        $username = $username ?: 'testuser_' . uniqid();
        $hoten = 'Test User ' . substr($username, -5);
        $now = time();

        $stmt = $con->prepare("INSERT INTO `tbl_tkkhachhang` (`username`, `password`, `hoten`, `ngaytao`) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('sssi', $username, $password, $hoten, $now);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();

        return $id;
    }

    /**
     * Xoá dữ liệu test (dùng sau mỗi test)
     */
    public static function cleanTable(string $table): void
    {
        $con = self::getConnection();
        $con->query("SET FOREIGN_KEY_CHECKS = 0");
        $con->query("TRUNCATE TABLE `$table`");
        $con->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    /**
     * Reset toàn bộ DB test về trạng thái ban đầu
     */
    public static function resetDatabase(): void
    {
        $con = self::getConnection();
        $tables = ['oder_chitiet', 'oder', 'tbl_diachi', 'tbl_sizegiay', 'tbl_thuvienanh', 'tbl_qlbaidang', 'tbl_danhmuc', 'tbl_qlthanhvien', 'tbl_dangnhap', 'tbl_tkkhachhang', 'tbl_qlsanpham'];
        $con->query("SET FOREIGN_KEY_CHECKS = 0");
        foreach ($tables as $t) {
            $con->query("TRUNCATE TABLE `$t`");
        }
        $con->query("SET FOREIGN_KEY_CHECKS = 1");

        // Re-seed
        self::seedTestData($con);
    }

    /**
     * Gửi HTTP request (dùng cho API/System tests)
     * Yêu cầu Apache đang chạy
     */
    public static function httpRequest(string $url, string $method = 'GET', array $data = [], string $cookie = ''): array
    {
        $ch = curl_init();

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } elseif ($method === 'POST_JSON') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        if ($cookie) {
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $error = curl_error($ch);
        curl_close($ch);

        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        // Trích xuất cookie từ response
        preg_match_all('/Set-Cookie:\s*([^;]+)/i', $headers, $cookieMatches);
        $responseCookies = implode('; ', $cookieMatches[1] ?? []);

        return [
            'code' => $httpCode,
            'body' => $body,
            'headers' => $headers,
            'cookies' => $responseCookies,
            'error' => $error,
        ];
    }

    /**
     * Đăng nhập KH qua HTTP và trả về session cookie
     */
    public static function loginCustomer(string $username = 'testuser', string $password = 'test123456'): string
    {
        $resp = self::httpRequest(
            BASE_URL . 'auth/dangnhap.php',
            'POST',
            ['taikhoan' => $username, 'matkhau' => $password, 'dangnhap' => '1']
        );
        return $resp['cookies'];
    }

    /**
     * Đăng nhập Admin qua HTTP và trả về session cookie
     */
    public static function loginAdmin(string $username = 'admin_test', string $password = 'admin123'): string
    {
        $resp = self::httpRequest(
            BASE_URL . 'admin/dangnhap.php',
            'POST',
            ['taikhoan' => $username, 'matkhau' => $password, 'dangnhap' => '1']
        );
        return $resp['cookies'];
    }
}
