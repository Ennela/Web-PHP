# Software Requirement Specification (SRS)
## Chức năng: Cổng Thanh Toán (Payment Gateway)
**Mã chức năng:** PAYMENT-01  
**Trạng thái:** Implemented  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Hệ thống hỗ trợ **2 phương thức thanh toán**: Tiền mặt khi nhận hàng (COD) qua `infodathang.php` và thanh toán trực tuyến qua VNPAY Sandbox tại `dathangonline.php` + `vnpay_return.php`. Cả 2 phương thức đều tích hợp **chống Race Condition** bằng MySQL Transaction và gửi email xác nhận qua **Brevo API**.

### 2. Luồng nghiệp vụ — COD (infodathang.php)
| Bước | Hành động | Chi tiết kỹ thuật |
| :--- | :--- | :--- |
| 1 | Nhập thông tin giao hàng | Form auto-fill từ `tbl_tkkhachhang` + `tbl_diachi` (ưu tiên địa chỉ mặc định). |
| 2 | Bấm "Xác nhận Đặt hàng" | Server-side validate: tenkh, sdt, email, diachi không rỗng. |
| 3 | Trừ tồn kho (Transaction) | Gọi `validateAndDeductStock()` — `SELECT...FOR UPDATE` khoá hàng, trừ stock trong transaction. |
| 4 | Tạo đơn hàng | INSERT vào `oder` (status=PENDING, payment_status=UNPAID) + INSERT hàng loạt `oder_chitiet`. |
| 5 | Gửi email | `sendMailBrevo()` gửi thông báo cho admin + xác nhận cho khách (kèm link tracking). |
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

### 5. Kiến trúc Email (Brevo API)
- **Admin notification:** `sendMailBrevo()` gửi đến `remkyorosi@gmail.com`.
- **Customer confirmation:** `sendMailBrevo()` gửi đến email khách kèm mã đơn, tổng tiền, link tracking.
- **Brevo free tier:** 300 email/ngày, gửi được đến MỌI địa chỉ email (không cần verify domain).
- **Template:** Email HTML responsive có gradient header, bảng thông tin, nút tracking.

### 6. Hoàn tồn kho khi thất bại (`restoreStock`)
- Khi VNPAY trả về lỗi hoặc đơn bị huỷ.
- Query `oder_chitiet` lấy danh sách SP + size + qty.
- Dùng Transaction: `SELECT...FOR UPDATE` + `UPDATE soluong = soluong + qty`.
- Đảm bảo tồn kho luôn chính xác.

### 7. Ràng buộc kỹ thuật & Bảo mật
* **SHA512 HMAC Hash:** Xác thực chữ ký VNPAY chống giả mạo URL.
* **Duplicate Prevention:** Kiểm tra `payment_status = 'UNPAID'` trước khi UPDATE, tránh xử lý trùng (nhiều tab).
* **Timeout:** Brevo API có timeout 10 giây, không ảnh hưởng trải nghiệm.
* **Giỏ hàng trống:** Kiểm tra `empty($_SESSION['giohang'])` trước khi cho thanh toán.

### 8. Giao diện (UI/UX)
* Form nhập thông tin bên trái, tóm tắt đơn hàng bên phải.
* Mobile-first responsive (stacked trên mobile).
* Trang kết quả VNPAY: Card hiển thị mã giao dịch, số tiền, ngân hàng, trạng thái (xanh/đỏ).
