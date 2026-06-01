# Software Requirement Specification (SRS)
## Chức năng: Khôi phục Mật Khẩu (Forgot Password)
**Mã chức năng:** PWD-RCV-01  
**Trạng thái:** Implemented  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Hệ thống cho phép khách hàng **khôi phục mật khẩu** khi quên thông qua email. Quy trình sử dụng **token bảo mật** (hex 64 ký tự, 256-bit entropy) có thời hạn 30 phút, gửi link reset qua **Mailjet API v3.1** (backward compatible qua hàm `sendMailBrevo()`). Bao gồm 4 file chính: `quenmatkhau.php`, `api_quenmatkhau.php`, `datlamatkhau.php`, `api_datlamatkhau.php`.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động người dùng | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Click "Quên mật khẩu?" tại trang Đăng nhập | Chuyển hướng đến `auth/quenmatkhau.php`. |
| 2 | Nhập email/username → "Gửi yêu cầu" | AJAX POST đến `api_quenmatkhau.php`. Tìm user WHERE email=? OR username=?. |
| 3 | Hệ thống tạo token & gửi email | `bin2hex(random_bytes(32))` → 64 hex chars. Lưu `reset_token` + `reset_token_expiry` (30 phút). Gửi email HTML qua `sendMailBrevo()` → `sendMailjet()`. |
| 4 | Hiển thị thông báo chung | "Nếu tài khoản tồn tại, chúng tôi đã gửi email..." (bảo mật). |
| 5 | Click link trong email | `auth/datlamatkhau.php?token=xxx`. Validate token + chưa hết hạn. |
| 6 | Nhập mật khẩu mới + xác nhận | AJAX POST `api_datlamatkhau.php`. Validate ≥ 6 ký tự. UPDATE password (`password_hash(BCRYPT)`), xoá token. |
| 7 | SweetAlert thành công → Redirect đăng nhập | Toast "Đặt lại mật khẩu thành công!" |

### 3. Yêu cầu dữ liệu
* **Bảng:** `tbl_tkkhachhang` — cột `reset_token` VARCHAR(64), `reset_token_expiry` INT(11)
* **Migration:** `migration_quenmatkhau.sql` (idempotent)

### 4. Kiến trúc Email (Mailjet API)
* **Hàm:** `sendMailBrevo()` → `sendMailjet()` (backward compatible)
* **Endpoint:** `https://api.mailjet.com/v3.1/send`, Basic Auth
* **Retry:** Exponential Backoff 3 lần cho 5xx/429
* **Template:** HTML responsive, gradient header, nút CTA, link backup text

### 5. Bảo mật
* Token 256-bit entropy, hết hạn 30 phút, sử dụng 1 lần
* Response chung (không leak tài khoản)
* Password hash: `password_hash(PASSWORD_BCRYPT)`
* SQL: `mysqli_real_escape_string()`

### 6. Edge Cases
| Trường hợp | Xử lý |
| :--- | :--- |
| Email/username không tồn tại | Thông báo chung (bảo mật) |
| Tài khoản không có email | Báo lỗi — liên hệ admin |
| Token hết hạn/đã dùng | Trang "Link không hợp lệ" + nút yêu cầu lại |
| Mật khẩu < 6 ký tự / không khớp | Validate client + server |
| Mailjet thất bại | Log lỗi, retry, vẫn trả success |

### 7. Giao diện (UI/UX)
* Auth card style đồng bộ Đăng nhập/Đăng ký (Montserrat, gradient)
* Password strength indicator 4 mức, toggle hiện/ẩn
* SweetAlert2 toast cho lỗi, full dialog cho thành công

### 8. File liên quan
| File | Mô tả |
| :--- | :--- |
| `auth/quenmatkhau.php` | Form nhập email, AJAX, success animation |
| `auth/api_quenmatkhau.php` | Tạo token, gửi email Mailjet |
| `auth/datlamatkhau.php` | Form mật khẩu mới, validate token |
| `auth/api_datlamatkhau.php` | Update password Bcrypt, xoá token |
| `includes/mail_helper.php` | sendMailBrevo → sendMailjet |
| `includes/mail_config.php` | Mailjet API config |
