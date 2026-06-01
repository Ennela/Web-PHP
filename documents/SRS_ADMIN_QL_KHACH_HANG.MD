# Software Requirement Specification (SRS)
## Chức năng: Quản lý Khách hàng và Người dùng
**Mã chức năng:** ADMIN-USER-01  
**Trạng thái:** Implemented  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Hệ thống cho phép Quản trị viên xem danh sách các tài khoản khách hàng (`tbl_tkkhachhang`) và người dùng nội bộ/thành viên quản trị (`tbl_qlthanhvien`). Cho phép xóa bỏ các tài khoản vi phạm hoặc tạo thêm thành viên ban quản trị. Mật khẩu thành viên admin hiện đã được mã hóa **Bcrypt** với cơ chế auto-migration.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động Admin | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Xem "Quản lý Khách hàng" (`quanlikhachhang.php`) hoặc "Quản lý Thành viên" (`quanlithanhvien.php`) | Bảng HTML hiển thị danh sách từ CSDL tương ứng. |
| 2 | Với Khách hàng: Nhấn Xóa (`delete_qlkhachhang.php`) | Xóa khách hàng `DELETE FROM tbl_tkkhachhang`. |
| 3 | Với Thành viên: Nhấn Thêm/Sửa | Form tạo/chỉnh sửa thành viên. Mật khẩu mới hash Bcrypt. |

### 3. Yêu cầu dữ liệu
* **Khách hàng (`tbl_tkkhachhang`):** `makh`, `username`, `password` (Bcrypt), `hoten`, `email`, `sdt`, `avatar`.
* **Thành viên (`tbl_qlthanhvien`):** `id`, `hoten`, `chucvu`, `taikhoan`, `matkhau` (Bcrypt with auto-migration).

### 4. Ràng buộc kỹ thuật & Bảo mật
* **Phiên bảo mật Admin:** Kiểm tra `$_SESSION['dangnhap1']`.
* **Mật khẩu Bcrypt:** Admin đăng nhập dùng `password_verify()`. Plaintext cũ tự động hash khi đăng nhập thành công.
* **Khách hàng Bcrypt:** Mật khẩu khách dùng `password_hash(PASSWORD_BCRYPT)` từ lúc đăng ký.

### 5. Trường hợp ngoại lệ
* **Xóa khách có đơn hàng:** Bảng `oder` lưu trực tiếp tên khách dưới dạng text, không khóa ngoại makh → xóa khách không ảnh hưởng đơn cũ.

### 6. Giao diện (UI/UX)
* Phân định 2 menu: Khách hàng website + Thành viên quản trị.
* Bảng HTML Table với nút Sửa/Xóa, phân trang.
