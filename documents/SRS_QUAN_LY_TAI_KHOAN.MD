# Software Requirement Specification (SRS)
## Chức năng: Quản lý Thông tin Tài khoản
**Mã chức năng:** USER-PROFILE-01  
**Trạng thái:** Implemented  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Trang `thongtintaikhoan.php` cung cấp trung tâm quản lý tài khoản toàn diện cho khách hàng đã đăng nhập. Giao diện sử dụng kiến trúc **Sidebar + Tab Content** với 5 tab chức năng: Thông tin cá nhân, Đổi mật khẩu, Sổ địa chỉ, Size giày yêu thích, và Đơn hàng của tôi. Mọi thao tác CRUD đều sử dụng AJAX không tải lại trang.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động người dùng | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Truy cập `auth/thongtintaikhoan.php` | Kiểm tra `$_SESSION['dangnhap']` + `$_SESSION['makh']`. Nếu chưa đăng nhập → redirect `dangnhap.php` với SweetAlert warning. |
| 2 | Xem thông tin cá nhân (Tab 1) | SELECT user từ `tbl_tkkhachhang`, hiển thị Họ tên, Username (readonly), Email, SĐT, Ngày sinh, Giới tính. Avatar được hiển thị (hoặc tạo SVG mặc định từ chữ cái đầu tên). |
| 3 | Bấm "Chỉnh sửa" → sửa thông tin → "Lưu thay đổi" | AJAX POST đến `api_capnhat_thongtin.php`. Server validate + UPDATE `tbl_tkkhachhang`. Trả JSON. SweetAlert toast thông báo. |
| 4 | Click vào avatar → chọn file ảnh | AJAX upload qua `api_upload_avatar.php`. Validate (JPG/PNG/WebP, max 2MB). Lưu vào `auth/uploads/`. UPDATE `avatar` column. Cập nhật ảnh ngay lập tức không reload. |
| 5 | Tab 2: Đổi mật khẩu | Nhập mật khẩu hiện tại + mới + xác nhận. AJAX POST đến `api_doimatkhau.php`. Server `password_verify()` mật khẩu cũ, `password_hash()` mật khẩu mới. |
| 6 | Tab 3: Sổ địa chỉ | AJAX CRUD qua `api_diachi.php`. Hiển thị grid các address card. Thêm mới qua Modal với dropdown Tỉnh/Huyện/Xã (API bên thứ 3). Tối đa 5 địa chỉ. Đặt mặc định. |
| 7 | Tab 4: Size giày yêu thích | AJAX CRUD qua `api_sizegiay.php`. Chọn hệ size EU/US/CM. Click grid size để chọn/bỏ. Lưu ghi chú sở thích. Bảng `tbl_sizegiay`. |
| 8 | Tab 5: Đơn hàng của tôi | SELECT 20 đơn gần nhất từ `oder` WHERE `makh`. Hiển thị card với mã đơn 9 số, trạng thái badge màu, địa chỉ, ngày đặt, tổng tiền. Nút "Xem chi tiết" → `chitietdonhang.php`. |
| 9 | Bấm "Đăng xuất" | `session_destroy()` → redirect `dangnhap.php`. |

### 3. Yêu cầu dữ liệu (Data Requirements)
#### 3.1. Dữ liệu Hiển thị & Chỉnh sửa
* **Bảng `tbl_tkkhachhang`:** `makh`, `username` (readonly), `hoten`, `email`, `sdt`, `ngaysinh`, `gioitinh`, `avatar`, `password`.
* **Bảng `tbl_diachi`:** `id`, `makh`, `hoten`, `sdt`, `tinh`, `quan_huyen`, `phuong_xa`, `diachi_cuthe`, `mac_dinh`, `ngaytao`.
* **Bảng `tbl_sizegiay`:** `id`, `makh`, `he_size` (EU/US/CM), `size_value`, `ghichu`, `ngaycapnhat`.
* **Bảng `oder`:** `id`, `order_code`, `makh`, `tenkh`, `sdt`, `diachi`, `tongtien`, `status`, `ngaytao`.

#### 3.2. API Endpoints
| File | Method | Mô tả |
| :--- | :--- | :--- |
| `auth/api_capnhat_thongtin.php` | POST (AJAX) | Cập nhật hoten, email, sdt, ngaysinh, gioitinh |
| `auth/api_upload_avatar.php` | POST (File) | Upload ảnh avatar, lưu vào `auth/uploads/` |
| `auth/api_doimatkhau.php` | POST (AJAX) | Đổi mật khẩu (verify old + hash new) |
| `auth/api_diachi.php` | POST (AJAX) | CRUD địa chỉ (action: list/add/update/delete/set_default) |
| `auth/api_sizegiay.php` | POST (AJAX) | CRUD size yêu thích (action: list/save/delete) |

### 4. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Authentication Required:** Mọi API đều kiểm tra `$_SESSION['makh']` tồn tại, trả HTTP 401 nếu không.
* **Mã hóa mật khẩu Bcrypt:** Sử dụng `password_hash(PASSWORD_BCRYPT)` / `password_verify()`.
* **XSS Prevention:** Tất cả output dùng `htmlspecialchars()`.
* **Avatar Validation:** Chỉ chấp nhận image/jpeg, image/png, image/webp. Giới hạn 2MB.
* **Giới hạn địa chỉ:** Tối đa 5 địa chỉ/người dùng (check server-side).

### 5. Trường hợp ngoại lệ & Xử lý lỗi (Edge Cases)
* **Session hết hạn giữa thao tác:** API trả `{"success": false, "message": "Phiên đăng nhập hết hạn"}` → JS redirect về đăng nhập.
* **Tài khoản đã bị admin xóa:** `mysqli_fetch_array` trả null → redirect đăng nhập với SweetAlert error.
* **Upload avatar quá lớn:** Server trả lỗi "File vượt quá 2MB" trước khi xử lý.
* **Đổi mật khẩu sai mật khẩu cũ:** Server trả `{"success": false, "message": "Mật khẩu hiện tại không đúng"}`.

### 6. Giao diện (UI/UX)
* **Layout Sidebar + Content:** Sidebar cố định bên trái (desktop) hoặc hamburger menu (mobile) với avatar, tên, email. Navigation tabs với icons FontAwesome.
* **Mobile-First Responsive:** CSS `account.css` riêng, sidebar overlay trên mobile với nút toggle.
* **Password Strength Indicator:** Bar 4 mức (Yếu/Trung bình/Khá/Mạnh) cập nhật real-time.
* **Toggle hiện/ẩn mật khẩu:** Icon mắt ở mọi input password.
* **Address Modal:** Popup form thêm địa chỉ với dropdown cascade Tỉnh → Huyện → Xã.
* **Size Grid:** Grid các nút size, click để toggle selected (xanh = đã chọn, đỏ = bỏ chọn).

### 7. Danh sách file liên quan
| File | Loại | Mô tả |
| :--- | :--- | :--- |
| `auth/thongtintaikhoan.php` | Page | Trang chính 5 tabs |
| `auth/api_capnhat_thongtin.php` | API | AJAX cập nhật thông tin |
| `auth/api_upload_avatar.php` | API | AJAX upload avatar |
| `auth/api_doimatkhau.php` | API | AJAX đổi mật khẩu |
| `auth/api_diachi.php` | API | AJAX CRUD địa chỉ |
| `auth/api_sizegiay.php` | API | AJAX CRUD size yêu thích |
| `assets/css/account.css` | CSS | Stylesheet riêng trang tài khoản |
| `assets/js/account.js` | JS | Logic client-side tabs, AJAX, validation |
