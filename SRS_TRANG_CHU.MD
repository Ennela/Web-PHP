# Software Requirement Specification (SRS)
## Chức năng: Giao diện Trang chủ (Home)
**Mã chức năng:** HOME-UI-01  
**Trạng thái:** Draft / Review  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Trang chủ (`trangchu.php`) đóng vai trò đập thẳng vào cái nhìn đầu tiên và phô bày thông số thiết kế chung, giới thiệu hệ thông Banner / Slider tĩnh hoặc hiển thị vài món hàng đại diện.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động người dùng | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Truy cập thư mục dải Domain | Tự động chuyển nạp nội dung của file cấu trúc `trangchu.php`. |
| 2 | Cuộn trình duyệt (Scroll) qua các khối Section | Đọc tải dữ liệu cấu trúc Text và Hình Banner cố định. Lấy ảnh nhỏ sản phẩm để chèn giới thiệu "Hàng Hot". |

### 3. Yêu cầu dữ liệu (Data Requirements)
* **Quản trị Ảnh Banner:** Có thể nạp cứng qua chuỗi tĩnh `<img>` thay vì nằm trong MySQL CSDL. 
* **Hàng đại diện:** Gọi truy cập nhanh vài mẫu `SELECT * FROM tbl_qlsanpham LIMIT 4` để trưng bày giới thiệu.

### 4. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Hiệu Suất (Performance):** Khối lượng Ảnh nằm tại trang chủ rất nặng, cần phải bọc `lazyload` ở các mã HTML hoặc cắt xén kích thước size thumbnail.

### 5. Trường hợp ngoại lệ & Xử lý lỗi (Edge Cases)
* **Trường hợp:** Chưa có sản phẩm nào thuộc nền tảng Database để trưng bày.
  * **Xử lý:** Nền Banner vẫn nạp, trong khi đó khu vực thẻ Hàng Hóa trỗ lại một khoảng bao bọc vắng không lỗi Crash.

### 6. Giao diện (UI/UX)
* Khối `Carousel` nạp thanh mảnh xoay tròn tạo điểm nhấn bắt mắt ở đỉnh.
* Footer có sọc chéo đệm chóp cuối chân màng kết quả.
