# Software Requirement Specification (SRS)
## Chức năng: Cổng Thanh Toán (Payment Gateway)
**Mã chức năng:** PAYMENT-01  
**Trạng thái:** Implemented  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Hệ thống hỗ trợ **2 phương thức thanh toán**: Tiền mặt khi nhận hàng (COD) qua `infodathang.php` và thanh toán trực tuyến qua VNPAY Sandbox tại `dathangonline.php` + `vnpay_return.php`. Cả 2 phương thức đều tích hợp **chống Race Condition** bằng MySQL Transaction, gửi email xác nhận qua **Mailjet API v3.1**, và tracking token cho mỗi đơn hàng.

### 2. Luồng nghiệp vụ — COD (infodathang.php)
| Bước | Hành động | Chi tiết kỹ thuật |
| :--- | :--- | :--- |
| 1 | Nhập thông tin giao hàng | Form auto-fill từ `tbl_tkkhachhang` + `tbl_diachi` (ưu tiên địa chỉ mặc định). |
| 2 | Bấm "Xác nhận Đặt hàng" | Server-side validate: tenkh, sdt, email, diachi không rỗng. |
| 3 | Trừ tồn kho (Transaction) | Gọi `validateAndDeductStock()` — `SELECT...FOR UPDATE` khoá hàng, trừ stock trong transaction. |
| 4 | Tạo đơn hàng | INSERT vào `oder` (status=PENDING, payment_status=UNPAID, token=random) + INSERT hàng loạt `oder_chitiet`. |
| 5 | Gửi email | `sendMailBrevo()` → `sendMailjet()` gửi thông báo cho admin + xác nhận cho khách (kèm link tracking token). |
| 6 | Dọn dẹp | `unset($_SESSION['giohang'])`, redirect với SweetAlert thành công. |

### 3. Luồng nghiệp vụ — VNPAY (dathangonline.php → vnpay_return.php)
| Bước | Hành động | Chi tiết kỹ thuật |
| :--- | :--- | :--- |
| 1 | Nhập thông tin + Submit | Tương tự COD. Lưu email vào `$_SESSION['email_kh']`. |
| 2 | Trừ tồn kho | `validateAndDeductStock()` — trừ trước khi redirect VNPAY. |
| 3 | Tạo đơn + Redirect VNPAY | Ghép tham số (`vnp_TmnCode`, `vnp_Amount = total*100`, `vnp_TxnRef`). Hash bằng `SHA512 HMAC`. `ksort()` trước khi hash. Redirect đến `sandbox.vnpayment.vn`. |
| 4 | Khách thanh toán | Nhập thẻ ATM/QR trên trang VNPAY Sandbox. |
| 5 | VNPAY redirect về | `vnpay_return.php` nhận `vnp_ResponseCode`, `vnp_SecureHash`. |
| 6a | **Thành công** (`00`) | Verify hash SHA512. UPDATE `oder` → status=CONFIRMED, payment_status=PAID. Gửi email xác nhận qua `sendMailBrevo()`. |
| 6b | **Thất bại** | Gọi `restoreStock()` hoàn tồn kho. UPDATE → status=CANCELLED, payment_status=FAILED. |

### 4. Chống Race Condition (`validateAndDeductStock`)
```
BEGIN TRANSACTION
  → SELECT soluong FROM tbl_tonkho WHERE masp=? AND size=? FOR UPDATE
    (KHOÁ dòng — khách khác phải đợi)
  → Nếu soluong >= qty: UPDATE soluong = soluong - qty
  → Nếu không đủ: ghi lỗi
Nếu có lỗi → ROLLBACK (không trừ gì)
Nếu OK → COMMIT (xác nhận trừ kho, mở khoá)
```

### 5. Kiến trúc Email (Mailjet API v3.1)
- **API Endpoint:** `https://api.mailjet.com/v3.1/send`
- **Xác thực:** HTTP Basic Auth (`MAILJET_API_KEY:MAILJET_SECRET_KEY`)
- **Phân luồng:** Transactional (đơn hàng, OTP) / Marketing (newsletter) / Admin (thông báo nội bộ)
- **Admin notification:** `sendMailBrevo()` → `sendMailjet()` gửi đến `MAIL_ADMIN_EMAIL`.
- **Customer confirmation:** Gửi đến email khách kèm mã đơn, tổng tiền, link tracking (`tracking.php?order=XXX&token=YYY`).
- **Retry logic:** Exponential Backoff cho 5xx/429, tối đa 3 lần (2s→4s→8s).
- **Email Blacklist:** Kiểm tra `tbl_email_blacklist` trước khi gửi, skip email bị bounce/spam.
- **Template:** Email HTML responsive có gradient header, bảng thông tin, nút tracking.

### 6. Tracking Token
- Mỗi đơn hàng được gán `token` ngẫu nhiên (hex 32 ký tự) khi tạo.
- Link tracking: `shop/tracking.php?order={order_code}&token={token}` — cho phép tra cứu không cần đăng nhập.
- Trang tracking hiển thị: trạng thái đơn, trạng thái thanh toán, ngày đặt, tổng tiền, thông tin giao hàng, danh sách sản phẩm.

### 7. Hoàn tồn kho khi thất bại (`restoreStock`)
- Khi VNPAY trả về lỗi hoặc đơn bị huỷ (admin).
- Query `oder_chitiet` lấy danh sách SP + size + qty.
- Dùng Transaction: `SELECT...FOR UPDATE` + `UPDATE soluong = soluong + qty`.
- Đảm bảo tồn kho luôn chính xác.

### 8. Ràng buộc kỹ thuật & Bảo mật
* **SHA512 HMAC Hash:** Xác thực chữ ký VNPAY chống giả mạo URL.
* **Duplicate Prevention:** Kiểm tra `payment_status = 'UNPAID'` trước khi UPDATE, tránh xử lý trùng (nhiều tab).
* **Timeout:** Mailjet API có timeout 15 giây + connect timeout 5 giây, không ảnh hưởng trải nghiệm.
* **Giỏ hàng trống:** Kiểm tra `empty($_SESSION['giohang'])` trước khi cho thanh toán.
* **Chưa chọn size:** Validate server-side từng sản phẩm phải có size trước khi trừ kho.

### 9. Giao diện (UI/UX)
* Form nhập thông tin bên trái, tóm tắt đơn hàng bên phải.
* Mobile-first responsive (stacked trên mobile).
* Auto-fill từ sổ địa chỉ mặc định + thông tin tài khoản.
* Trang kết quả VNPAY: Card hiển thị mã giao dịch, số tiền, ngân hàng, trạng thái (xanh/đỏ).
* Nút xác nhận disable + spinner khi đang xử lý (chống double submit).

### 10. Danh sách file liên quan
| File | Loại | Mô tả |
| :--- | :--- | :--- |
| `shop/infodathang.php` | Page | Checkout COD |
| `shop/dathangonline.php` | Page | Checkout VNPAY |
| `shop/vnpay_return.php` | Page | Xử lý callback VNPAY |
| `shop/tracking.php` | Page | Tra cứu đơn hàng qua token |
| `vnpay_php/config.php` | Config | Cấu hình VNPAY Sandbox |
| `includes/inventory_helper.php` | Helper | validateAndDeductStock, restoreStock |
| `includes/mail_helper.php` | Helper | sendMailjet, sendMailBrevo (backward compat) |
| `includes/mail_config.php` | Config | Constants email, sender, retry |
| `includes/email_templates.php` | Helper | HTML templates cho email |
| `includes/order_helper.php` | Helper | generateOrderCode() — mã đơn 9 số |
