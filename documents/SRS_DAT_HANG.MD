# Software Requirement Specification (SRS)
## Chức năng: Đặt hàng (Checkout)
**Mã chức năng:** ORDER-CHECKOUT-01  
**Trạng thái:** Implemented  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Hệ thống cung cấp quy trình Checkout gồm 2 luồng:
- **COD** (`infodathang.php`): Thanh toán khi nhận hàng.
- **VNPAY Online** (`dathangonline.php`): Thanh toán trực tuyến qua VNPAY Sandbox.

Cả 2 đều tích hợp: auto-fill từ tài khoản/sổ địa chỉ, trừ tồn kho bằng MySQL Transaction, gửi email xác nhận qua Mailjet API, tạo mã đơn 9 chữ số + tracking token.

### 2. Luồng nghiệp vụ — COD (Checkout tiền mặt)
| Bước | Hành động người dùng | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Từ Giỏ hàng, bấm "Thanh toán tiền mặt" | Session `$_SESSION['chuyen']` + `$_SESSION['chuyen_size']` lưu giỏ. Redirect `infodathang.php`. |
| 2 | Điền form (auto-fill nếu đã login) | Tự động điền Tên, SĐT, Email, Địa chỉ từ `tbl_tkkhachhang` + `tbl_diachi` (mặc định). |
| 3 | Xác nhận đặt hàng (Submit) | Validate server-side → `validateAndDeductStock()` → INSERT `oder` + `oder_chitiet`. |
| 4 | Gửi email xác nhận | `sendMailBrevo()` → Mailjet: admin notification + customer confirmation (link tracking). |
| 5 | Dọn dẹp + thông báo | Xóa giỏ hàng session. Redirect với SweetAlert success. |

### 3. Luồng nghiệp vụ — VNPAY
| Bước | Hành động | Chi tiết |
| :--- | :--- | :--- |
| 1 | Từ Giỏ hàng, bấm "Thanh toán VNPAY" | Tương tự COD, redirect `dathangonline.php`. |
| 2 | Điền form + Submit | Tạo đơn `oder` (PENDING/UNPAID), trừ tồn kho, tạo URL VNPAY (SHA512 HMAC), redirect. |
| 3 | Thanh toán trên VNPAY Sandbox | Nhập thẻ ATM/QR. |
| 4 | VNPAY callback `vnpay_return.php` | Verify hash → thành công: UPDATE CONFIRMED/PAID + email. Thất bại: restoreStock() + CANCELLED/FAILED. |

### 4. Mã đơn hàng & Token
* **order_code:** 9 chữ số ngẫu nhiên, unique. Generated bởi `generateOrderCode()`.
* **token:** hex string ngẫu nhiên dùng cho link tracking email.

### 5. Ràng buộc kỹ thuật
* **Chống Race Condition:** `validateAndDeductStock()` — Transaction + SELECT...FOR UPDATE.
* **Hoàn tồn kho:** `restoreStock()` khi VNPAY fail hoặc admin hủy.
* **Chống double submit:** Kiểm tra `payment_status = 'UNPAID'` trước khi cập nhật.
* **Giỏ hàng trống:** Redirect lại giỏ hàng với SweetAlert warning.

### 6. Giao diện (UI/UX)
* Layout 2 cột: Form thông tin (trái) + Tóm tắt đơn hàng (phải).
* Auto-fill từ sổ địa chỉ mặc định.
* Loading spinner + disable button khi đang xử lý.
* Trang VNPAY return: Card kết quả với mã giao dịch, số tiền, trạng thái.

### 7. File liên quan
| File | Mô tả |
| :--- | :--- |
| `shop/infodathang.php` | Checkout COD |
| `shop/dathangonline.php` | Checkout VNPAY |
| `shop/vnpay_return.php` | Callback VNPAY |
| `vnpay_php/config.php` | Cấu hình VNPAY Sandbox |
| `includes/inventory_helper.php` | Tồn kho (Transaction) |
| `includes/mail_helper.php` | Gửi email Mailjet |
| `includes/order_helper.php` | generateOrderCode() |
