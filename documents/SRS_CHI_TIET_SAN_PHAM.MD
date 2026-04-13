# Software Requirement Specification (SRS)
## Chức năng: Xem Chi tiết Sản phẩm
**Mã chức năng:** PROD-DETAIL-01  
**Trạng thái:** Draft / Review  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Trang chi tiết cung cấp mọi thông số về thiết kế, mức giá cũng như hình ảnh thực tế của một mẫu đôi giày (`chitietsanpham.php`). Tại đây, khách hàng có thể quyết định tuỳ chọn **Kích Cỡ/Size** và số lượng đôi cần mua để tống vào hộp Giỏ Hàng chung nhằm chuyển sang pha thanh toán. Tính năng này được bổ trợ thêm giao diện Zoom Cận Cảnh Mượt được viết thuần không sử dụng lib lạ, nâng cao chất lượng editorial.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động người dùng | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Bấm chọn một sản phẩm tại giao diện Shop | Chuyển hướng tới `chitietsanpham.php?id=(masp)`. Hệ thống lấy thông tin từ kho với truy vấn đối chiếu. |
| 2 | Rê trỏ chuột vào Ảnh Lớn bề mặt sản phẩm | Kích hoạt hiệu ứng Custom Image Tracker Zoom để soi cận cảnh chất liệu vải/da. |
| 3 | Bắt buộc chọn Kích Cỡ (Size) | Nếu ấn Add to Cart mà bỏ quên chọn Size, hệ thống phát cảnh báo `Vui lòng chọn size` và chặn tác vụ gửi form. |
| 4 | Nhấn "Thêm vào Giỏ Hàng" | Điều hướng `action=add` sang tệp xử lý logic `giohang.php` bằng phương thức POST để ghi nhận thông số sản phẩm kèm thêm Size lên biến `$_SESSION`. |

### 3. Yêu cầu dữ liệu (Data Requirements)
#### 3.1. Dữ liệu Hiển thị Nội dung (Data Output)
* Dữ liệu bóc tách từ `tbl_qlsanpham` bằng điều kiện `masp = ?`.
* Tên sản phẩm, Giá Bán (`giasanpham`), Giá Gốc (`giagoc` cho gạch bỏ).
* Các bức ảnh kèm theo: `anhdaidien`, `anhgiuoithieu1`, `anhgiuoithieu2`.

#### 3.2. Dữ liệu Điều phối Tương tác (Interaction Input)
* **Parameter `$id`:** Lưu trữ dưới dạng ID (số integer) độc nhất để giữ mối liên kết vào phiên Session.
* **Kích cỡ Size (`size`):** Nút Radio Button chọn Size (Ví dụ từ 36 - 44). Đây là giá trị bắt buộc truyền đi `required`.
* **Số lượng:** Do khách tự gõ quyết định (mặc định lấy 1).

### 4. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Phiên không ràng buộc đăng nhập:** Người dùng không cần phải Đăng nhập (no authentication require) để thêm một mặt hàng vào phiên Giỏ Hàng. Hệ thống sử dụng phiên ẩn danh PHP Header Session.

### 5. Trường hợp ngoại lệ & Xử lý lỗi (Edge Cases)
* **Trường hợp:** Không truyền `$id` trên trang, truy cập thẳng URL thô `chitietsanpham.php`.
  * **Xử lý:** Trang sẽ không hiển thị được nội dung sản phẩm. Nền tảng PHP tự vứt lỗi Null hoặc cảnh báo biến không chạm tới kết quả.

### 6. Giao diện (UI/UX)
* Áp dụng bố cục Brutalist-Minimalist Editorials: Chiết xuất ra 2 cột hiển thị lớn, cột trái đính chặt hình ảnh cho phép cuộn cố định, cột phải thiết kế tinh gọn bảng tuỳ biến thanh toán.
* Thay thế library Vanilla Zoom bằng cơ chế Tracker trực tiếp: Tối giản rườm rà HTML, chuột khách hàng lướt đến đâu trên ảnh, Javascript phóng to và pan ảnh theo đúng toạ độ không độ trễ.
