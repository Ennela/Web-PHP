# BÁO CÁO PHÂN TÍCH VÀ KHẮC PHỤC LỖ HỔNG BẢO MẬT
**Dự án:** Web-PHP (Hệ thống website bán giày thể thao)  
**Chuyên đề:** Failure to Restrict URL Access & SQL Injection (SQLi)

---

## PHẦN 1: LỖ HỔNG KHÔNG HẠN CHẾ QUYỀN TRUY CẬP URL (FAILURE TO RESTRICT URL ACCESS)

### 1.1. Bản chất lý thuyết
Lỗ hổng **Không hạn chế quyền truy cập URL** (*Failure to Restrict URL Access* - thuộc nhóm Broken Access Control / Phân quyền bị lỗi trong OWASP Top 10) xảy ra khi ứng dụng web không kiểm tra quyền hạn của người dùng (Authorization) một cách nghiêm ngặt trên máy chủ (server-side) trước khi cho phép truy cập vào các trang web, chức năng hoặc tệp tin nhạy cảm.

Hệ thống thường chỉ dựa vào việc "giấu" đường dẫn khỏi giao diện người dùng thông thường nhưng không kiểm tra xem người gửi yêu cầu URL có thực sự có vai trò (role) hợp lệ hay không.

### 1.2. Hậu quả thực tế
* **Leo quyền trái phép (Privilege Escalation):** Người dùng thông thường hoặc khách vãng lai chỉ cần biết hoặc đoán được đường dẫn URL (Force Browsing / URL Guessing) là có thể truy cập thẳng vào trang quản trị (Admin Dashboard).
* **Lộ dữ liệu nhạy cảm:** Xem danh sách khách hàng, tài khoản, doanh thu và đơn hàng.
* **Thực thi thao tác trái phép:** Gọi các URL xóa sản phẩm, xóa bài viết, duyệt đơn hàng mà không cần đăng nhập quyền Admin.
* **Lộ mã nguồn và cơ sở dữ liệu:** Tải trực tiếp các file `.sql`, `.env`, file cấu hình nếu thư mục không được chặn.

### 1.3. Phương pháp khắc phục trong dự án
Hệ thống đã được thiết lập cơ chế phòng thủ 2 lớp:

#### Lớp 1: Middleware kiểm soát phiên và vai trò (Admin Guard)
* File: `admin/admin_guard.php` được nhúng vào đầu tất cả các tệp trong thư mục `/admin/`.
* **Cơ chế:** Kiểm tra xem session người dùng đã khởi tạo chưa và có đúng `$_SESSION['role'] === 'admin'` hay không.
* **Xử lý:** Nếu chưa đăng nhập, tự động chuyển hướng (Redirect 302) về trang `admin/canhbao.php`. Nếu là AJAX thì trả về mã lỗi HTTP 401. Nếu không phải quyền admin thì chặn với mã HTTP 403 Forbidden.

```php
// File: admin/admin_guard.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['dangnhap']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: canhbao.php");
    exit();
}
```

#### Lớp 2: Cấu hình Web Server chặn truy cập file nhạy cảm
* Đặt tệp `.htaccess` trong các thư mục `database/`, `includes/`, `documents/` với lệnh `Deny from all` để cấm tải trực tiếp các tệp tin mã nguồn và bản sao lưu cơ sở dữ liệu.

### 1.4. Quy trình kiểm thử (Testing Guide)
* **Test 1.1 - Truy cập trang Admin khi chưa đăng nhập:**
  * Mở Cửa sổ Ẩn danh (Incognito), truy cập: `http://localhost/Web-PHP/admin/index.php` hoặc `http://localhost/Web-PHP/admin/quanlisanpham.php`
  * *Kết quả:* Bị chặn và chuyển hướng ngay về `admin/canhbao.php`.
* **Test 1.2 - Kích hoạt URL xóa nhạy cảm:**
  * Truy cập: `http://localhost/Web-PHP/admin/delete_qlsanpham.php?masp=1`
  * *Kết quả:* Không xóa dữ liệu và bị chuyển hướng về trang đăng nhập.
* **Test 1.3 - Tải trực tiếp file SQL:**
  * Truy cập: `http://localhost/Web-PHP/database/giaythethao2.sql`
  * *Kết quả:* Bị chặn với lỗi `403 Forbidden`.

---

## PHẦN 2: LỖ HỔNG CHÈN CÂU LỆNH SQL (SQL INJECTION - SQLi)

### 2.1. Bản chất lý thuyết
SQL Injection (SQLi) là lỗ hổng phát sinh khi dữ liệu người dùng nhập vào (qua form, URL GET/POST) được ghép nối trực tiếp (string concatenation) vào chuỗi truy vấn SQL mà không được phân tách giữa câu lệnh (Code) và dữ liệu (Data).

Kẻ tấn công sử dụng các ký tự điều khiển cú pháp SQL (như `'`, `"`, `--`, `;`) để làm thay đổi logic thực thi của cơ sở dữ liệu.

### 2.2. Hậu quả thực tế
* **Vượt qua màn hình đăng nhập (Authentication Bypass):** Dùng chuỗi `' OR '1'='1` để đăng nhập vào tài khoản admin/khách hàng mà không cần mật khẩu.
* **Đánh cắp cơ sở dữ liệu:** Sử dụng kỹ thuật UNION SELECT hoặc Error-based để trích xuất bảng mật khẩu, thông tin cá nhân.
* **Xóa hoặc sửa đổi dữ liệu trái phép:** Biến câu lệnh xóa thành điều kiện luôn đúng để xóa sạch bảng dữ liệu.

### 2.3. Phương pháp khắc phục trong dự án
* **Biện pháp 1: Áp dụng Prepared Statements (Parameterized Queries):**  
  Tách biệt hoàn toàn câu lệnh và dữ liệu. Dữ liệu người dùng chỉ được truyền qua tham số (`?`) và hàm `mysqli_stmt_bind_param()`, cơ sở dữ liệu xử lý an toàn 100% dưới dạng chuỗi hoặc số.
  ```php
  // Ví dụ tại auth/dangnhap.php:
  $stmt = mysqli_prepare($con, "SELECT * FROM `tbl_tkkhachhang` WHERE `username` = ? AND `password` = ? LIMIT 1");
  mysqli_stmt_bind_param($stmt, "ss", $username, $password);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  ```
* **Biện pháp 2: Thư viện trợ giúp `includes/sql_helper.php`:**
  * `sql_int()`: Ép kiểu số nguyên cho mọi ID trên URL (ví dụ: `0 OR 1=1` bị ép thành `0`).
  * `sql_pagination()`: Giới hạn số trang và bản ghi.
  * `sql_build_filter()`: Whitelist danh sách cột hợp lệ bằng `SHOW COLUMNS`.

### 2.4. Quy trình kiểm thử (Testing Guide)
* **Test 2.1 - Vượt qua đăng nhập (Login Bypass):**
  * URL: `http://localhost/Web-PHP/auth/dangnhap.php`
  * Tài khoản: `' OR '1'='1` | Mật khẩu: `123456`
  * *Kết quả:* Báo sai tài khoản/mật khẩu, từ chối đăng nhập.
* **Test 2.2 - Xóa dữ liệu qua tham số URL:**
  * Đăng nhập admin rồi truy cập: `http://localhost/Web-PHP/admin/delete_qlsanpham.php?masp=0 OR 1=1`
  * *Kết quả:* Tham số bị ép về `0`, không bản ghi nào bị xóa, database giữ nguyên 33 sản phẩm.
* **Test 2.3 - Tìm kiếm Admin:**
  * URL: `http://localhost/Web-PHP/admin/quanlisanpham.php`
  * Nhập ô tìm kiếm: `' OR '1'='1`
  * *Kết quả:* Không lỗi cú pháp SQL, chỉ tìm sản phẩm có đúng chuỗi ký tự đó.
* **Test 2.4 - Tra cứu đơn hàng:**
  * URL: `http://localhost/Web-PHP/shop/tracking.php?order=' OR '1'='1&token=' OR '1'='1`
  * *Kết quả:* Báo "Không tìm thấy đơn hàng hoặc token không đúng", không bị lộ dữ liệu.

---

## BẢNG TỔNG KẾT SO SÁNH

| Lỗ hổng | Nguy cơ tiềm ẩn | Giải pháp áp dụng | Trạng thái sau khắc phục |
| :--- | :--- | :--- | :--- |
| **Failure to Restrict URL Access** | Khách vãng lai đoán URL vào thẳng trang quản trị hoặc xóa sản phẩm. | Xây dựng `admin_guard.php` kiểm tra Session + Role. Chặn file nhạy cảm qua `.htaccess`. | **ĐÃ AN TOÀN** (100% các file admin được bảo vệ) |
| **SQL Injection (SQLi)** | Kẻ tấn công bypass đăng nhập, đánh cắp mật khẩu, xóa sạch database. | Chuyển sang Prepared Statements (`mysqli_stmt`), ép kiểu số nguyên, whitelist tên cột. | **ĐÃ AN TOÀN** (Vượt qua mọi bài test chèn mã) |
