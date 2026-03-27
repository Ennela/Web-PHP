# Software Requirement Specification (SRS)
## Chức năng: Quản lý Tin tức (Bài đăng)
**Mã chức năng:** ADMIN-NEWS-01  
**Trạng thái:** Draft / Review  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Quản trị viên (Admin) được cấp quyền tạo mới, chỉnh sửa và quản lý các bài đăng hiển thị trên hệ thống. Dữ liệu được lưu trữ tại bảng `tbl_qlbaidang` và liên kết với người phụ trách (thuộc bảng thành viên). Quản trị viên có thể điều khiển việc Ẩn/Hiện bài viết.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động Admin | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Bấm chọn menu "Quản lý Bài Viết" (`quanlibaidang.php`) | Khởi chạy bảng HTML cung cấp danh sách mọi bài báo đã viết (`SELECT * FROM tbl_qlbaidang`). |
| 2 | Chọn nút lệnh "Thêm Bài Viết" (`thembaidang.php`) | Cung cấp giao diện Form với các trường Tên bài, ảnh rọi, Nội dung, Chế độ. |
| 3 | Chọn trường dữ liệu và nhấn Lưu | Hệ thống tải file ảnh lên máy chủ (thư mục `uploads/` của Admin). |
| 4 | *Trường hợp thành công:* Lưu vào kho | Gọi hàm `INSERT INTO tbl_qlbaidang` với dữ liệu lấy trực tiếp từ `$_POST`. |

### 3. Yêu cầu dữ liệu (Data Requirements)
#### 3.1. Dữ liệu chỉnh sửa (Input Control)
* **Tiêu đề tin tức (`tieude`):** Tên của bài đăng.
* **Người phụ trách (`nguoiphutrach`):** Tên của người dùng Admin (Gán cứng qua danh sách xổ xuống).
* **Tài nguyên Ảnh:** 3 File hình ảnh gồm `anhdaidien`, `anhgioithieu1`, `anhgioithieu2`.
* **Chế độ hiển thị (`chedo`):** Các giá trị như "Hiện", "Ẩn".
* **Nội dung Chi tiết (`noidung`):** Khung nhập văn bản nội dung.

### 4. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Xác thực phiên (Session):** Chức năng chỉ có thể được gọi nếu Session admin vẫn còn giá trị.
* **Tải tệp tin Uploads:** Hình ảnh sẽ nằm chung thư mục `admin/uploads/`. Cần lọc kỹ phần đuôi định dạng để tránh lọt file `.php` rác.

### 5. Trường hợp ngoại lệ & Xử lý lỗi (Edge Cases)
* **Trường hợp:** Xóa một bài đăng (`delete_qlbaidang.php`).
  * **Xử lý:** Xóa trực tiếp `DELETE FROM tbl_qlbaidang WHERE id=?`. Các tệp hình ảnh còn lưu trong thiết bị lưu trữ trên ổ cứng (thư mục `uploads`) có thể không bị xóa mềm tự động qua SQL, cần có lưu ý dọn rác thủ công (nếu cần).

### 6. Giao diện (UI/UX)
* Khung Form soạn thảo HTML truyền thống gồm các nhóm Inputs (Label và Control) đi kèm.
