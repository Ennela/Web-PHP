# Software Requirement Specification (SRS)
## Chức năng: Khôi phục Mật Khẩu
**Mã chức năng:** PWD-RCV-01  
**Trạng thái:** Draft / Review  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Trang Quên mật khẩu (Ví dụ như thiết lập `admin/quenmatkhau.php`) cung cấp một form nháp ban đầu trong quy trình lấy lại thông tin truy cập. Tại cấu trúc hiện hành, file `quenmatkhau.php` mới chỉ dựng khung Giao Diện tĩnh chứ chưa có code xử lý PHP chèn mã phục hồi OTP nội bộ.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động người dùng | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Thất lạc mật mã, cần tìm khung hỗ trợ tại trang Đăng nhập | Hệ thống cung cấp Liên kết "Quên mật khẩu?". |
| 2 | Chuyển tới `quenmatkhau.php` | Trình duyệt nạp giao diện tĩnh một khung Form điền Email. |
| 3 | (Trong Phiên Bản Tương Lai) Điền Cứu lấy File Liên Hệ | - Chưa kích hoạt luồng bắn Email xác thực - |

### 3. Yêu cầu dữ liệu (Data Requirements)
* **Form dữ liệu:** Textbox chứa mã địa chỉ Email.

### 4. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Trạng thái tính năng (Limitation):** Vùng chức năng này nằm ở mảng giao diện Front-end Dummy (Chỉ có HTML/CSS khung Form Bootstrap chứ không có câu lệnh `SELECT` đổi Password bằng PHP).

### 5. Trường hợp ngoại lệ & Xử lý lỗi (Edge Cases)
* **Trường hợp:** Lạm dụng bấm nút Ghi nhận Form.
  * **Xử lý:** Trang web sẽ đứng yên hoặc Refressh tải trống vì chưa nối kết đường hầm POST vào Server.

### 6. Giao diện (UI/UX)
* Giao diện HTML thừa hưởng Template chung với trang Login/Register của hệ thống Bootstrap.
