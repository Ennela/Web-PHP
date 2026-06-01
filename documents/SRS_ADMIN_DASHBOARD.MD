# Software Requirement Specification (SRS)
## Chức năng: Admin Dashboard (Tổng quan)
**Mã chức năng:** ADMIN-DASH-01  
**Trạng thái:** Implemented  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Hệ thống cung cấp một bảng điều khiển trung tâm (Dashboard) dành riêng cho Quản trị viên tại đường dẫn `admin/trangchu.php`. Chức năng này giúp Admin theo dõi các chỉ số cơ bản như số lượng đơn hàng đã đặt, số lượng sản phẩm đang bán, tổng số thành viên và báo cáo tổng Doanh Thu theo tháng dựa trên thời gian thực. Đăng nhập admin sử dụng **Bcrypt** với auto-migration từ mật khẩu plaintext.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động Admin | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Truy cập `admin/index.php` hoặc `admin/trangchu.php` | Kiểm tra `$_SESSION['dangnhap1']`. Nếu chưa đăng nhập → redirect `canhbao.php`. |
| 2 | Đăng nhập tại `canhbao.php` | Prepared Statement: `SELECT * FROM tbl_qlthanhvien WHERE taikhoan = ? LIMIT 1`. Xác thực bằng `password_verify()`. Nếu mật khẩu cũ (plaintext) khớp → auto-hash sang Bcrypt + đăng nhập. |
| 3 | Xem Dashboard | Hiển thị cards thống kê: Tổng đơn hàng, Tổng sản phẩm, Tổng khách hàng, Doanh thu (DELIVERED). |

### 3. Yêu cầu dữ liệu (Data Requirements)
* **Doanh thu:** `SELECT SUM(tongtien) FROM oder WHERE status = 'DELIVERED'` + timestamp filter.
* **Đơn hàng:** `SELECT COUNT(id) FROM oder;`
* **Khách hàng:** `SELECT COUNT(makh) FROM tbl_tkkhachhang;`
* **Sản phẩm:** `SELECT COUNT(masp) FROM tbl_qlsanpham;`

### 4. Ràng buộc kỹ thuật & Bảo mật
* **Bcrypt Authentication:** `password_verify()` cho mật khẩu đã hash, fallback plaintext comparison → auto-hash migration.
* **Prepared Statements:** Chống SQL injection cho form đăng nhập.
* **Toast Notifications:** Thay thế alert() bằng custom Toast system (warning/error) với animation slide-in.

### 5. Trường hợp ngoại lệ
* **CSDL rỗng:** COUNT trả về 0, hiển thị bình thường.
* **Sai mật khẩu:** Toast error "Sai tài khoản hoặc mật khẩu".
* **Truy cập trực tiếp:** Toast warning "Bạn phải đăng nhập trước".

### 6. Giao diện (UI/UX)
* Dashboard cards gradient với icon FontAwesome.
* Login form: Floating label input, green accent animation, avatar SVG.
* Sidebar navigation: 6 menu items (Trang chủ, Sản phẩm, Khách hàng, Bài đăng, Thành viên, Đơn hàng).
