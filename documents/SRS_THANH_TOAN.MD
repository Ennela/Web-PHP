# Software Requirement Specification (SRS)
## Chức năng: Cổng Thanh Toán VNPAY (Payment Gateway)
**Mã chức năng:** PAYMENT-VNPAY-01  
**Trạng thái:** Draft / Review  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Hệ thống kết nối nguyên bản (Native Integration) thư viện Sandbox VNPAY tại `dathangonline.php` và nhận biến số phản hồi tại `vnpay_return.php`. Chức năng cho phép khách quẹt thẻ ngân hàng ATM/QR Code.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động khách hàng | Phản hồi từ Server PHP & Cổng VNPAY |
| :--- | :--- | :--- |
| 1 | Xác nhận Đặt hàng qua VNPAY | Chạy qua file `dathangonline.php`, lưu Giỏ vào biến Database Đơn Hàng. Đọc và tải thông số mảng `$inputData` cấu trúc VNPAY. |
| 2 | Redirect HTTP | Redirect thẳng URL máy khách văng tới domain thanh toán của ví `sandbox.vnpayment.vn`. |
| 3 | Tương tác thẻ ATM/QR Test | Tại ví VNPAY, khách nạp thẻ ảo hoặc quét QR giả lập. VNPAY bẻ luồng HTTP đẩy biến ngược lại vào `vnpay_return.php`. |
| 4 | Đọc Biến Return | Nếu biến `vnp_ResponseCode` == '00', báo giao dịch rổ tiền đã nhận thành công. Nếu thất bại, hệ thống báo "Lỗi thanh toán". |

### 3. Yêu cầu dữ liệu (Data Requirements)
#### 3.1. Dữ liệu Request Mã VNPAY
* Thông số Hash bí mật `vnp_HashSecret`, Terminal ID `vnp_TmnCode` (Cắm cứng từ file `vnpay_php/config.php`).
* Số tiền `vnp_Amount = total * 100`. Mã tham chiếu trỏ chéo Order ID.

### 4. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Secure Hash Verification:** Yêu cầu tại trang `return` phải mã hóa lại chuỗi `$hashdata` qua chuẩn `sha512` so sánh lại với chuỗi bảo mật của VNPAY đưa về thì mới coi là lệnh thật, tránh bị Hacker ép URL.

### 5. Trường hợp ngoại lệ & Xử lý lỗi (Edge Cases)
* **Trường hợp:** Khách qua tới VNPAY nhưng nhấn Hủy bỏ / đóng Tab ngang.
  * **Xử lý:** Website đã kịp `INSERT` dòng Đơn Hàng vào CSDL `oder` với trạng thái `PENDING` ở bước 1. Nó sẽ mãi kẹt lại thành đơn bỏ hoang.
* **Trường hợp:** Tính toán băm (Hash) sai lệch do dấu cách.
  * **Xử lý:** Hiển thị "Chữ ký không hợp lệ".

### 6. Giao diện (UI/UX)
* File `vnpay_return.php` hiển thị một khung thẻ bọc khối chi tiết hóa đơn: Số Tiền Đã Chuyển, Nội Dung Bill, Thời Gian và Nhấn Trở Về Cửa Hàng.
