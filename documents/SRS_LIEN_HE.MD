# Software Requirement Specification (SRS)
## Chức năng: Liên hệ (Contact Form)
**Mã chức năng:** CONTACT-01  
**Trạng thái:** Implemented  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Trang `lienhe.php` cung cấp biểu mẫu liên hệ cho khách hàng gửi thắc mắc, phản hồi đến shop. Thông điệp được gửi tự động đến email quản trị viên thông qua **Brevo (Sendinblue) HTTP API** — giải pháp gửi email qua API REST, không phụ thuộc vào cổng SMTP (tránh bị block port trên Cloud).

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động người dùng | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Truy cập `lienhe.php` | Hiển thị form liên hệ: Họ tên, Email, Nội dung. Kèm thông tin shop (địa chỉ, SĐT, giờ mở cửa) và Google Maps. |
| 2 | Điền thông tin và nhấn **GỬI YÊU CẦU** | **Client-side validation** (JS): Kiểm tra không để trống, email đúng định dạng. Nếu lỗi → hiện thông báo đỏ inline. |
| 3 | Submit form (POST) | **Server-side validation** (PHP): Kiểm tra `!empty()` + `filter_var(FILTER_VALIDATE_EMAIL)`. |
| 4 | Gọi `sendMailBrevo()` | Gửi HTTP POST đến `https://api.brevo.com/v3/smtp/email` với API key. Timeout 10 giây. |
| 5 | *Thành công:* | Hiện Alert xanh: "Thư của bạn đã được gửi thành công!". |
| 5b | *Thất bại:* | Hiện Alert đỏ: "Vui lòng điền đầy đủ thông tin hợp lệ." |

### 3. Kiến trúc Email (Mail Architecture)

#### 3.1 Brevo HTTP API (Production)
- **Endpoint:** `https://api.brevo.com/v3/smtp/email`
- **Xác thực:** Header `api-key: {BREVO_API_KEY}`
- **Sender:** `remkyorosi@gmail.com` (email xác thực trên Brevo)
- **Payload mẫu:**
```json
{
  "sender": {"name": "Shop Sneakers", "email": "remkyorosi@gmail.com"},
  "to": [{"email": "remkyorosi@gmail.com", "name": "Shop Admin"}],
  "subject": "📩 Liên hệ từ khách hàng - Nguyễn Văn A",
  "htmlContent": "<div>...</div>"
}
```

#### 3.2 Shared Helper (`includes/mail_helper.php`)
Hàm `sendMailBrevo()` dùng chung cho toàn bộ hệ thống (Liên hệ, Đặt hàng COD, Thanh toán VNPAY). Tập trung logic gửi email tại 1 file duy nhất, dễ bảo trì.

### 4. Yêu cầu dữ liệu (Data Requirements)
* **Input:** `name`, `email`, `message` (từ form POST).
* **Config:** Biến môi trường `BREVO_API_KEY` (cấu hình trên Railway Dashboard).
* **Không lưu DB:** Tin nhắn liên hệ không lưu vào CSDL — gửi thẳng đến email admin.

### 5. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Timeout 10s:** Đảm bảo trang không bị treo (loading mãi) dù API gặp sự cố.
* **Không đính kèm tệp:** Không hỗ trợ file upload để tránh rủi ro mã độc.
* **Dual Validation:** Client-side (JS) + Server-side (PHP) đảm bảo dữ liệu hợp lệ.
* **XSS Prevention:** Tất cả input được escape bằng `htmlspecialchars()`.

### 6. So sánh với phiên bản cũ

| | Phiên bản cũ (PHPMailer) | Phiên bản hiện tại (Brevo API) |
|---|---|---|
| **Phương thức** | SMTP Gmail (port 465) | HTTP REST API (port 443) |
| **Vấn đề** | Bị block port trên Cloud/Firewall → trang load mãi | Hoạt động mọi nơi |
| **Timeout** | Không có → treo vô thời hạn | 10 giây |
| **Free tier** | Không giới hạn (nhưng hay bị block) | 300 email/ngày |

### 7. Giao diện (UI/UX)
* **Mobile-first** responsive layout với CSS Grid.
* Form liên hệ bên trái, Google Maps bên phải (stacked trên mobile).
* 4 info cards: Địa chỉ, Điện thoại, Email, Giờ mở cửa.
* Hover effects, gradient buttons, micro-animations.
