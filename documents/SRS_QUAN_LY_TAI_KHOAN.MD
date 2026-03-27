# Software Requirement Specification (SRS)
## Chức năng: Quản lý Thông tin Tài khoản
**Mã chức năng:** USER-PROFILE-01  
**Trạng thái:** Draft / Review  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Trang `thongtintaikhoan.php` cung cấp cho khách mua hàng xem lại thông tin định danh cá nhân đã khai báo khi đăng ký tài khoản. Hệ thống đóng vai trò thông báo Profile cá nhân dạng thẻ tĩnh (Read-only) và cung cấp đường đi tắt cho việc Đăng xuất thiết bị.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động người dùng | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Truy cập `thongtintaikhoan.php` | Hệ thống kiểm tra ID tài khoản `$_SESSION['makh']`. Nếu mảng rỗng thì sẽ tự động Alert "Vui lòng đăng nhập" chặn người dùng vào. |
| 2 | Kiểm duyệt thành công | Bắn chuỗi `SELECT * FROM tbl_tkkhachhang WHERE makh = ? LIMIT 1`. |
| 3 | Quan sát hiển thị | Dán các Record trả về của người dùng (Họ tên, Username). |
| 4 | (Tùy chọn) Bấm "Đăng Xuất Thiết Bị" | Ném cờ parameter `?login=dangxuat`, kích hoạt giải phóng mảng bộ nhớ hủy phiên hoạt động. |

### 3. Yêu cầu dữ liệu (Data Requirements)
#### 3.1. Dữ liệu Hiển thị Tĩnh
* Lấy ra Họ Tên (`hoten`) và Tên Đăng Nhập Hệ Thống (`username`).
* Không lấy Mật khẩu (`password`). Cốt yếu tránh lộ cho ánh nhìn ngẫu nhiên người kế bên.

### 4. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Đóng Form Sửa đổi (Immutable Rule):** Tại nền tảng người tiêu dùng (Customer site), mọi thông số định danh thiết lập tại bước Đăng ký là không thể sửa đổi (No Edit Input).
* **Cross-Site Scripting (XSS):** Dữ liệu in ra khung View phải dùng `htmlspecialchars` bọc lại đề phòng `username` kẹp mã HTML bẩn.

### 5. Trường hợp ngoại lệ & Xử lý lỗi (Edge Cases)
* **Trường hợp:** SQL truy xuất ID trả về Null (tài khoản đã bị quản trị viên xóa, nhưng Session Cookie của khách chưa hết hạn).
  * **Xử lý:** Nhận diện mảng Fetch bị False, quăng Alert `"Lỗi truy xuất hệ thống! Vui lòng thử đăng nhập lại"` khóa trang.

### 6. Giao diện (UI/UX)
* Khung hồ sơ Card theo phong cách Identity vạch dọc cạnh viền làm nổi text. Trải giữa màn hình có Icon màu nổi đậm bao bọc Avatar.
