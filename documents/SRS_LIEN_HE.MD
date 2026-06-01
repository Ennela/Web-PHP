# Software Requirement Specification (SRS)
## Chức năng: Liên hệ (Contact Form)
**Mã chức năng:** CONTACT-01  
**Trạng thái:** Implemented  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Trang `lienhe.php` cung cấp biểu mẫu liên hệ cho khách hàng gửi thắc mắc, phản hồi đến shop. Thông điệp được gửi tự động đến email quản trị viên thông qua **Mailjet HTTP API v3.1** — giải pháp gửi email qua API REST, không phụ thuộc vào cổng SMTP (tránh bị block port trên Cloud).

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động người dùng | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Truy cập `lienhe.php` | Hiển thị form liên hệ: Họ tên, Email, Nội dung. Kèm thông tin shop (địa chỉ, SĐT, giờ mở cửa) và Google Maps. |
| 2 | Điền thông tin và nhấn **GỬI YÊU CẦU** | **Client-side validation** (JS): Kiểm tra không để trống, email đúng định dạng. Nếu lỗi → hiện thông báo đỏ inline. |
| 3 | Submit form (POST) | **Server-side validation** (PHP): Kiểm tra `!empty()` + `filter_var(FILTER_VALIDATE_EMAIL)`. |
| 4 | Gọi `sendMailBrevo()` | Backward compatible → gọi `sendMailjet()`. Gửi HTTP POST đến `https://api.mailjet.com/v3.1/send` với API key. Timeout 15 giây + retry. |
| 5 | *Thành công:* | Hiện Alert xanh: "Thư của bạn đã được gửi thành công!". |
| 5b | *Thất bại:* | Hiện Alert đỏ: "Vui lòng điền đầy đủ thông tin hợp lệ." |

### 3. Kiến trúc Email (Mail Architecture)

#### 3.1 Mailjet HTTP API v3.1 (Production)
- **Endpoint:** `https://api.mailjet.com/v3.1/send`
- **Xác thực:** HTTP Basic Auth (`MAILJET_API_KEY:MAILJET_SECRET_KEY`)
- **Sender:** Cấu hình qua `MAIL_TRANS_FROM_EMAIL` (mail_config.php)
- **Retry:** Exponential Backoff cho lỗi 5xx/429, tối đa 3 lần.
- **Blacklist Check:** Kiểm tra `tbl_email_blacklist` trước khi gửi.

#### 3.2 Shared Helper (`includes/mail_helper.php`)
Hàm `sendMailBrevo()` (backward compatible) → gọi `sendMailjet()`. Dùng chung cho toàn bộ hệ thống (Liên hệ, Đặt hàng COD, Thanh toán VNPAY, Quên mật khẩu). Tập trung logic gửi email tại 1 file duy nhất, dễ bảo trì.

### 4. Yêu cầu dữ liệu (Data Requirements)
* **Input:** `name`, `email`, `message` (từ form POST).
* **Config:** Biến môi trường `MAILJET_API_KEY`, `MAILJET_SECRET_KEY` (cấu hình trên Railway Dashboard hoặc `.env` local).
* **Không lưu DB:** Tin nhắn liên hệ không lưu vào CSDL — gửi thẳng đến email admin.

### 5. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Timeout 15s:** Đảm bảo trang không bị treo dù API gặp sự cố.
* **Không đính kèm tệp:** Không hỗ trợ file upload để tránh rủi ro mã độc.
* **Dual Validation:** Client-side (JS) + Server-side (PHP) đảm bảo dữ liệu hợp lệ.
* **XSS Prevention:** Tất cả input được escape bằng `htmlspecialchars()`.

### 6. So sánh lịch sử thay đổi

| | Phiên bản 1 (PHPMailer) | Phiên bản 2 (Brevo API) | Phiên bản hiện tại (Mailjet API) |
|---|---|---|---|
| **Phương thức** | SMTP Gmail (port 465) | HTTP REST API (port 443) | HTTP REST API v3.1 |
| **Vấn đề** | Block port trên Cloud | Hạn chế free tier | Phân luồng email, retry logic |
| **Timeout** | Không có → treo | 10 giây | 15s + 5s connect |
| **Retry** | Không | Không | Exponential Backoff 3 lần |
| **Blacklist** | Không | Không | Có (tbl_email_blacklist) |

### 7. Giao diện (UI/UX)
* **Mobile-first** responsive layout với CSS Grid.
* Form liên hệ bên trái, Google Maps bên phải (stacked trên mobile).
* 4 info cards: Địa chỉ, Điện thoại, Email, Giờ mở cửa.
* Hover effects, gradient buttons, micro-animations.
