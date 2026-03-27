# Software Requirement Specification (SRS)
## Chức năng: Liên hệ (Contact Form)
**Mã chức năng:** CONTACT-01  
**Trạng thái:** Draft / Review  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Trang cung cấp biểu mẫu hòm thư `lienhe.php`. Mọi thông điệp báo cáo hoặc thắc mắc mua hàng sẽ được thu thập và tự động gửi thẳng đến hòm Email hệ thống quản trị nhờ giao thức SMTP của PHPMailer Library.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động người dùng | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Truy cập `lienhe.php` | Frontend vẽ Input yêu cầu: Họ tên, Email người gửi, Nội dung nháp. |
| 2 | Nhấn Gửi thư (Nút POST) | Ứng dụng nạp đối tượng class `PHPMailer`. |
| 3 | Chuyển tiếp cổng kết nối | Hệ thống đăng nhập vào Gmail cấp quản trị qua port SSL 465. |
| 4 | *Trường hợp thành công:* Đẩy nội dung | In màn hình Alert "Thư đã được gửi" và tải lại luồng `lienhe.php` dọn trống textbox. |

### 3. Yêu cầu dữ liệu (Data Requirements)
* **Dữ liệu Biểu mẫu (Input):** `name`, `email`, `message`.
* **Dữ liệu Cứng (Config):** Địa chỉ máy chủ `smtp.gmail.com`, thông tự tài khoản `hethong1fx...` để kích hoạt bắn Mail. Bỏ qua cấu trúc lưu CSDL bảng SQL để chống quá tải rác tin nhắn.

### 4. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Tài khoản Mail cấp ứng dụng:** Việc dùng `PHPMailer` đòi hỏi tài khoản Google phải tạo Mật khẩu Ứng dụng (App Passwords) mới được phép Bypass.
* **Không Đính Kết Tệp Tín (Attachments):** Không hỗ trợ File upload để đề phòng rủi ro bảo mật tệp mã độc chèn tới Admin.

### 5. Trường hợp ngoại lệ & Xử lý lỗi (Edge Cases)
* **Trường hợp:** Kết nối mạng chập chờn / Mật khẩu ứng dụng bị đổi làm sai cước phí SMTP Authentication.
  * **Xử lý:** Khối lệnh `Try-Catch(Exception)` tóm lỗi âm thầm kết thúc, không in StackTrace cho người ngoài làm phơi bày Mật khẩu cứng.

### 6. Giao diện (UI/UX)
* Khung Card nổi ngay chính giữa trang có 3 đoạn Input Field.
