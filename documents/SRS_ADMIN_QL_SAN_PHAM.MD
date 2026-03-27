# Software Requirement Specification (SRS)
## Chức năng: Quản lý Sản phẩm
**Mã chức năng:** ADMIN-PROD-01  
**Trạng thái:** Draft / Review  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Hệ thống cho phép Quản trị viên (Admin) tạo mới, chỉnh sửa thông tin hoặc xóa bỏ các sản phẩm giày dép (`quanlisanpham.php`). Chức năng yêu cầu nạp hình ảnh chính và ảnh phụ, định giá sản phẩm (giá bán/giá gốc) và điền trực tiếp tên Nhóm sản phẩm (nhomsp). Cấu trúc hiện tại không yêu cầu quản lý số lượng tồn kho hay phân loại cỡ kích size (Variants).

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động Admin | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Truy cập "Quản lý Sản phẩm" | Hệ thống Load bảng kê hàng hóa (`tbl_qlsanpham`). |
| 2 | Nhấn "Thêm sản phẩm mới" (`themsanpham.php`) | Màn hình Form nhập liệu với các trường (Tên, Ảnh 1, 2, 3, Giá gốc, Giá bán, Mô tả, Nhóm SP). |
| 3 | Khai báo và ấn Lưu | Ứng dụng tải 3 hình ảnh lên thư mục `uploads/` trên máy chủ sử dụng hàm `move_uploaded_file()`. |
| 4 | *Trường hợp thành công:* Lưu vào kho | Chèn thông số bao gồm đường dẫn ảnh vào CSDL (`INSERT INTO tbl_qlsanpham`). |

### 3. Yêu cầu dữ liệu (Data Requirements)
#### 3.1. Dữ liệu Đầu vào (Input Fields)
* **Tên sản phẩm (`tensp`):** Tên định danh hiển thị.
* **Nhóm Sản Phẩm (`nhomsp`):** Chuỗi giá trị tự do (VD: "Giày nam", "Giày nữ").
* **Tài nguyên ảnh:** Hỗ trợ cứng đúng 03 ô upload ảnh (Ảnh đại diện, Ảnh phụ 1, Ảnh phụ 2). Ghi lại đường dẫn tương đối (Ví dụ: `uploads/giay.jpg`).
* **Giá cả:** `giasanpham` (Giá thanh toán) và `giagoc` (Giá ảo gạch ngang đối chứng hiển thị). 
* **Nội dung (`noidung`):** Thẻ đoạn văn mô tả dạng Text/HTML.

### 4. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Xử lý thư mục Uploads:** Các tệp hình ảnh tải lên sẽ trực tiếp lưu hành vào thư mục gốc `admin/uploads/`.
* **Xóa Ràng Buộc Liên Kết:** Trong SQL, bảng `oder_chitiet` có khóa ngoại trỏ tới `tbl_qlsanpham(masp) ON DELETE CASCADE`. Do đó việc xóa một sản phẩm trong kho (`delete_qlsanpham.php`) cũng sẽ tự động xóa sạch các dòng lịch sử mua món hàng này ở các đơn cũ.

### 5. Trường hợp ngoại lệ & Xử lý lỗi (Edge Cases)
* **Trường hợp:** Admin không nhập đủ 3 bức ảnh mà chỉ nhập ảnh Đại diện.
  * **Xử lý:** Hệ thống chấp nhận thông qua, biến lưu trữ đường dẫn ảnh phụ bằng mảng trống. Ở phía giao diện người dùng nếu ảnh rỗng thì sẽ tự bỏ mảng (hoặc báo lỗi vỡ nhỏ).

### 6. Giao diện (UI/UX)
* Phân chia chia màn hình Form rõ ràng hỗ trợ các thẻ `<input type="file">` riêng lẻ. Không áp dụng kéo/thả.
