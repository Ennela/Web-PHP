# Software Requirement Specification (SRS)
## Chức năng: Khôi phục Mật Khẩu (Forgot Password)
**Mã chức năng:** PWD-RCV-01  
**Trạng thái:** Implemented  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Hệ thống cho phép khách hàng **khôi phục mật khẩu** khi quên thông qua email. Quy trình sử dụng **token bảo mật** (hex 64 ký tự) có thời hạn 30 phút, gửi link reset qua **Brevo API**. Bao gồm 4 file chính: `quenmatkhau.php` (form nhập), `api_quenmatkhau.php` (tạo token & gửi mail), `datlamatkhau.php` (form đặt mật khẩu mới), `api_datlamatkhau.php` (cập nhật password).

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động người dùng | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Tại trang Đăng nhập, click **"Quên mật khẩu?"** | Chuyển hướng đến `auth/quenmatkhau.php`. |
| 2 | Nhập **email hoặc tên đăng nhập** → Bấm "Gửi yêu cầu" | AJAX POST đến `api_quenmatkhau.php`. Hệ thống tìm user trong `tbl_tkkhachhang` (WHERE email = ? OR username = ?). |
| 3 | (Hệ thống) Tạo token & gửi email | Tạo `bin2hex(random_bytes(32))` → 64 ký tự hex. Lưu `reset_token` + `reset_token_expiry` (30 phút) vào DB. Gửi email HTML chứa link reset qua `sendMailBrevo()`. |
| 4 | Hiển thị **thông báo thành công** (animation) | "Nếu tài khoản tồn tại, chúng tôi đã gửi email..." (bảo mật — không leak tài khoản có tồn tại hay không). |
| 5 | Mở email → click link **"Đặt lại mật khẩu"** | Truy cập `auth/datlamatkhau.php?token=xxx`. Server validate token tồn tại + chưa hết hạn. |
| 6 | Nhập **mật khẩu mới** + xác nhận → Bấm "Đặt lại mật khẩu" | AJAX POST đến `api_datlamatkhau.php`. Validate mật khẩu (≥ 6 ký tự, khớp xác nhận). UPDATE `password`, xoá `reset_token`. |
| 7 | Hiển thị **SweetAlert thành công** → Redirect đăng nhập | `$_SESSION['swal_success']` hiển thị toast "Đặt lại mật khẩu thành công!" trên trang đăng nhập. |

### 3. Yêu cầu dữ liệu (Data Requirements)
* **Bảng:** `tbl_tkkhachhang`
* **Cột mới (migration):**
  - `reset_token` VARCHAR(64) DEFAULT NULL — token hex ngẫu nhiên
  - `reset_token_expiry` INT(11) DEFAULT NULL — Unix timestamp hết hạn
* **File migration:** `migration_quenmatkhau.sql` (idempotent, kiểm tra cột trước khi thêm)

### 4. Kiến trúc Email Reset (Brevo API)
* **Template:** Email HTML responsive với gradient header xanh, nút CTA "Đặt lại mật khẩu", link backup text, thông tin hết hạn.
* **Sender:** `Shop Sneakers <remkyorosi@gmail.com>` (qua Brevo).
* **Thời hạn:** Link có hiệu lực **30 phút** (`time() + 1800`).
* **Bảo mật response:** API luôn trả thông báo chung ("Nếu tài khoản tồn tại...") để không leak thông tin tài khoản.

### 5. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Token bảo mật:** `bin2hex(random_bytes(32))` → 64 ký tự hex, entropy 256-bit, không đoán được.
* **Hết hạn tự động:** Token chỉ valid trong 30 phút (`reset_token_expiry >= time()`).
* **Sử dụng 1 lần:** Sau khi đặt lại mật khẩu, token bị xoá (SET NULL).
* **Bảo mật response:** Không tiết lộ tài khoản có tồn tại hay không (tránh enumeration attack).
* **Tài khoản không có email:** Hiển thị thông báo "Tài khoản chưa cập nhật email. Vui lòng liên hệ admin."
* **Token validation:** Kiểm tra độ dài = 64 ký tự trước khi query DB.
* **SQL injection:** Sử dụng `mysqli_real_escape_string()` cho tất cả input.

### 6. Trường hợp ngoại lệ & Xử lý lỗi (Edge Cases)
| Trường hợp | Xử lý |
| :--- | :--- |
| Email/username không tồn tại | Trả thông báo chung (bảo mật) |
| Tài khoản không có email | Báo lỗi rõ ràng — liên hệ admin |
| Token hết hạn (> 30 phút) | Hiển thị trang "Link không hợp lệ" + nút "Yêu cầu lại" |
| Token đã sử dụng | Hiển thị trang "Link không hợp lệ" (token đã bị xoá) |
| Mật khẩu < 6 ký tự | Validate client-side + server-side |
| Mật khẩu xác nhận không khớp | Validate client-side + server-side |
| Brevo API gửi mail thất bại | Log lỗi server, vẫn trả success (bảo mật) |
| Spam yêu cầu liên tục | Token mới ghi đè token cũ (chỉ token cuối cùng valid) |

### 7. Giao diện (UI/UX)
* **Trang quên mật khẩu:** Auth card đồng bộ style với trang Đăng nhập/Đăng ký (Montserrat, gradient button, rounded input). Icon key trên đầu form. Info box hướng dẫn. Animation chuyển sang trạng thái thành công (envelope icon pulse).
* **Trang đặt mật khẩu mới:** Password strength indicator (4 mức: Yếu/Trung bình/Khá/Mạnh). Toggle hiện/ẩn mật khẩu. Token hết hạn → hiển thị error state với icon cảnh báo đỏ + nút "Yêu cầu lại".
* **Trang đăng nhập:** Link "Quên mật khẩu?" nằm cùng dòng với checkbox "Ghi nhớ đăng nhập" (flex, space-between).
* **SweetAlert2:** Toast notification cho lỗi/cảnh báo, full dialog cho thành công (redirect về đăng nhập).

### 8. Danh sách file liên quan
| File | Loại | Mô tả |
| :--- | :--- | :--- |
| `auth/quenmatkhau.php` | Page | Form nhập email/username, AJAX, success animation |
| `auth/api_quenmatkhau.php` | API | Tạo token, lưu DB, gửi email Brevo |
| `auth/datlamatkhau.php` | Page | Form đặt mật khẩu mới, validate token |
| `auth/api_datlamatkhau.php` | API | Validate token, update password, xoá token |
| `auth/dangnhap.php` | Page (sửa) | Thêm link "Quên mật khẩu?" |
| `migration_quenmatkhau.sql` | Migration | Thêm cột `reset_token`, `reset_token_expiry` |
