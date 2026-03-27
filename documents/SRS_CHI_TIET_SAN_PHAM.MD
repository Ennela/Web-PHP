# Software Requirement Specification (SRS)
## Chức năng: Xem Chi tiết Sản phẩm
**Mã chức năng:** PROD-DETAIL-01  
**Trạng thái:** Draft / Review  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Trang chi tiết cung cấp mọi thông số về thiết kế, mức giá cũng như hình ảnh thực tế của một mẫu đôi giày (`chitietsanpham.php`). Tại đây, khách hàng có thể quyết định số lượng đôi cần mua để tống vào hộp Giỏ Hàng chung nhằm chuyển sang pha thanh toán. Cấu trúc hiện tại không yêu cầu người dùng lựa chọn size (kích cỡ).

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động người dùng | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Bấm chọn một sản phẩm tại giao diện Shop | Chuyển hướng tới `chitietsanpham.php?id=(masp)`. Hệ thống lấy thông tin từ kho với truy vấn đối chiếu. |
| 2 | Quan sát sản phẩm | Load giá thành, các ảnh giới thiệu và văn bản mô tả. |
| 3 | Tăng giảm Số lượng qua thanh Input Quantity | Form chuẩn bị dữ liệu ẩn (Hidden values) gói ghém `id` đi kèm với biến số lượng. |
| 4 | Nhấn "Thêm vào Giỏ Hàng" | Điều hướng `action=add` sang tệp xử lý logic `giohang.php` bằng phương thức POST/GET để cộng dồn lên biến `$_SESSION`. |

### 3. Yêu cầu dữ liệu (Data Requirements)
#### 3.1. Dữ liệu Hiển thị Nội dung (Data Output)
* Dữ liệu bóc tách từ `tbl_qlsanpham` bằng điều kiện `masp = ?`.
* Tên sản phẩm, Giá Bán (`giasanpham`), Giá Gốc (`giagoc` cho gạch bỏ).
* Các bức ảnh kèm theo: `anhdaidien`, `anhgiuoithieu1`, `anhgiuoithieu2`.

#### 3.2. Dữ liệu Điều phối Tương tác (Interaction Input)
* **Parameter `$id`:** Lưu trữ dưới dạng ID (số integer) độc nhất để giữ mối liên kết vào phiên Session của người tiêu dùng.
* **Số lượng:** Do khách tự gõ quyết định (mặc định lấy 1).

### 4. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Phiên không ràng buộc đăng nhập:** Người dùng không cần phải Đăng nhập (no authentication require) để thêm một mặt hàng vào phiên Giỏ Hàng. Hệ thống sử dụng phiên ẩn danh PHP Header Session.

### 5. Trường hợp ngoại lệ & Xử lý lỗi (Edge Cases)
* **Trường hợp:** Không truyền `$id` trên trang, truy cập thẳng URL thô `chitietsanpham.php`.
  * **Xử lý:** Trang sẽ không hiển thị được nội dung sản phẩm. Nền tảng PHP tự vứt lỗi Null hoặc cảnh báo biến không chạm tới kết quả.

### 6. Giao diện (UI/UX)
* Chiết xuất ra 2 cột hiển thị (Cột ảnh to ở bên trái và cột Mua sắm thông số ở bên phải) dành cho phiên bản Web PC.
* Thiết lập Image Gallery nhỏ ở dưới thẻ ảnh lớn để người dùng bấm trỏ lựa ảnh coi cận cảnh thiết kế bề mặt đôi giày.
