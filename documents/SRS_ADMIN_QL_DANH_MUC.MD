# Software Requirement Specification (SRS)
## Chức năng: Quản lý Danh mục (Nhóm sản phẩm)
**Mã chức năng:** ADMIN-CAT-01  
**Trạng thái:** Draft / Review  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Hệ thống cho phép Quản trị viên phân loại sản phẩm vào các nhóm khác nhau thông qua bảng điều khiển. Trong cấu trúc dữ liệu hiện tại, danh mục được lưu trữ trực tiếp thông qua trường `nhomsp` của bảng sản phẩm hoặc danh sách cứng.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động Admin | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Truy cập "Quản lý Danh mục" (`quanlidanhmuc.php`) | Hiển thị bảng danh sách các danh mục đang tồn tại của nền tảng website. |
| 2 | Chọn tính năng "Thêm mới" (`themdanhmuc.php`) | Mở biểu mẫu (Form) điền tên danh mục. |
| 3 | Điền tên danh mục và nhấn Lưu | Tiến hành kiểm tra xác thực. |

### 3. Yêu cầu dữ liệu (Data Requirements)
#### 3.1. Dữ liệu chỉnh sửa (Input Fields)
* **Tên Danh mục:** Tên của loại nhóm hàng hóa.

### 4. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Bảo vệ qua phiên đăng nhập:** Phải có Authentication Session của cấp Quản trị viên.

### 5. Trường hợp ngoại lệ & Xử lý lỗi (Edge Cases)
* **Trường hợp:** Tên danh mục bị bỏ trống.
  * **Xử lý:** Hiển thị cảnh báo JavaScript bắt buộc nhập liệu.

### 6. Giao diện (UI/UX)
* Sử dụng bảng biểu HTML Table cơ bản để hiển thị danh sách, đi kèm nút Sửa/Xóa.
