# Software Requirement Specification (SRS)
## Chức năng: Hệ thống Xác thực người dùng (User Authentication)
**Mã chức năng:** AUTH-01  
**Trạng thái:** Draft / Review  
**Người soạn thảo:** [diepcd]  
**Vai trò:** Lead Researcher / Developer

---

### 1. Mô tả tổng quan (Description)
Cung cấp cơ chế xác thực an toàn để người dùng (Nghiên cứu viên/Quản trị viên) truy cập vào hệ thống quản lý dữ liệu và mô hình AI. Đảm bảo tính bảo mật cho các tài sản trí tuệ và kết quả thí nghiệm.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động người dùng | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Truy cập URL `/login` | Hiển thị Form đăng nhập (Email, Password, Remember Me). |
| 2 | Nhập thông tin và nhấn "Login" | Validate định dạng dữ liệu đầu vào (Client-side & Server-side). |
| 3 | Hệ thống kiểm tra thông tin | So khớp Email và mã hóa Password (Bcrypt) trong Database. |
| 4 | Xác thực thành công | Khởi tạo Session/Token, chuyển hướng về Dashboard. |
| 5 | Xác thực thất bại | Giữ nguyên trang, hiển thị thông báo lỗi và xóa trường Password. |

### 3. Yêu cầu dữ liệu (Data Requirements)
#### 3.1. Dữ liệu đầu vào (Input Fields)
* **Email:** `string`, định dạng email hợp lệ, bắt buộc.
* **Password:** `string`, tối thiểu 8 ký tự, ẩn ký tự khi nhập, bắt buộc.
* **Remember Me:** `boolean`, tùy chọn (mặc định false).

#### 3.2. Dữ liệu lưu trữ (Database - Bảng `users`)
* `email`: unique, index.
* `password`: hashed string.
* `last_login_at`: timestamp (để theo dõi truy cập).
* `login_ip`: string (phục vụ Audit Log).

### 4. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Giao thức:** Bắt buộc sử dụng **HTTPS** để mã hóa dữ liệu trên đường truyền.
* **Bảo mật Form:** Tích hợp mã **CSRF Token** trong mọi request POST.
* **Mã hóa:** Mật khẩu không bao giờ được lưu dưới dạng văn bản thuần (Plaintext). Sử dụng thuật toán `Argon2` hoặc `Bcrypt`.
* **Throttling (Chống Brute-force):** Khóa tạm thời IP/Tài khoản nếu đăng nhập sai quá 5 lần trong 1 phút.

### 5. Trường hợp ngoại lệ & Xử lý lỗi (Edge Cases)
* **Trường hợp:** Người dùng nhập sai định dạng email.  
  * **Xử lý:** Hiển thị lỗi ngay tại field: "Email không đúng định dạng".
* **Trường hợp:** Tài khoản đã bị quản trị viên khóa (Inactive).  
  * **Xử lý:** Thông báo: "Tài khoản của bạn tạm thời bị đình chỉ. Vui lòng liên hệ Admin".
* **Trường hợp:** Token CSRF hết hạn (do để trang quá lâu).  
  * **Xử lý:** Redirect về trang login với thông báo "Phiên làm việc hết hạn, vui lòng thử lại".

### 6. Giao diện (UI/UX)
* Thiết kế Responsive (hoạt động tốt trên cả Desktop và Mobile).
* Nút "Login" hiển thị trạng thái `processing` (spinner) khi đang gửi request.
* Hỗ trợ phím tắt: Nhấn `Enter` để gửi form.

---
