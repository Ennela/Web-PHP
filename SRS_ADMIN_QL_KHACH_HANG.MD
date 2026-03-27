# Software Requirement Specification (SRS)
## Chức năng: Quản lý Khách hàng và Người dùng
**Mã chức năng:** ADMIN-USER-01  
**Trạng thái:** Draft / Review  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Hệ thống cho phép Quản trị viên xem danh sách các tài khoản khách hàng (`tbl_tkkhachhang`) và người dùng nội bộ/thành viên quản trị (`tbl_qlthanhvien`). Cho phép xóa bỏ các tài khoản vi phạm hoặc tạo thêm thành viên ban quản trị.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động Admin | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Bấm chọn xem "Quản lý Khách hàng" (`quanlikhachhang.php`) hoặc "Quản lý Thành viên" (`quanlithanhvien.php`) | Bảng HTML hiển thị danh sách từ CSDL tương ứng. |
| 2 | Với Khách hàng: Nhấn Xóa (`delete_qlkhachhang.php`) | Xóa khách hàng bằng `DELETE FROM tbl_tkkhachhang`. |
| 3 | Với Thành viên: Nhấn Thêm mới hoặc Sửa (`themthanhvien.php` / `edit_qlthanhvien.php`) | Cho phép cập nhật thông tin Chức vụ (Quản lí, Nhân viên), tên, mật khẩu lưu dạng chữ thuần (Plaintext). |

### 3. Yêu cầu dữ liệu (Data Requirements)
#### 3.1. Đối với khách hàng (Tài khoản Shop)
* **Bảng `tbl_tkkhachhang`:** `makh` (ID), `username`, `password`, `hoten`.

#### 3.2. Đối với Thành viên nội bộ (Admin)
* **Bảng `tbl_qlthanhvien`:** Chứa chi tiết hơn bao gồm liên hệ, `chucvu` (Role), `taikhoan`, `matkhau`.

### 4. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Phiên bảo mật Admin:** Toàn bộ file dạng `delete_` hay `edit_` đều bị giấu kín sau lớp kiểm tra Auth của file `connect_db`. 
* **Mật khẩu cơ bản:** Dự án hiện đang lưu trữ Mật khẩu dạng nguyên bản (Plaintext) tại cột `password` thay vì mã hóa.

### 5. Trường hợp ngoại lệ & Xử lý lỗi (Edge Cases)
* **Trường hợp:** Admin xóa tài khoản khách hàng, nhưng khách đó đã từng mua hàng.
  * **Xử lý:** Việc xóa khách hàng (`tbl_tkkhachhang`) không ảnh hưởng tới bảng đơn hàng (`oder`) vì bảng `oder` lưu trực tiếp tên khách dưới dạng chữ phụ độc lập chứ không xài rành buộc khóa ngoại makh.

### 6. Giao diện (UI/UX)
* Phân định 2 menu rõ ràng: một cho Người dùng web, một cho Nhân viên quản trị hệ thống. 
