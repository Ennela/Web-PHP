# Software Requirement Specification (SRS)
## Chức năng: Hệ thống Đăng nhập (Login)
**Mã chức năng:** AUTH-LOGIN-01  
**Trạng thái:** Draft / Review  
**Người soạn thảo:** Đội ngũ phát triển
**Vai trò:** Lead Developer

---

### 1. Mô tả tổng quan (Description)
Hệ thống xác thực cho phép khách hàng truy cập vào tài khoản cá nhân thông qua thông tin đã đăng ký trước đó nhằm quản lý thông tin hồ sơ, theo dõi đơn hàng và lưu trữ lịch sử mua sắm một cách cá nhân hóa.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động người dùng | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Truy cập vào trang Đăng nhập từ thanh điều hướng | Hệ thống điều hướng và hiển thị biểu mẫu Đăng nhập. |
| 2 | Nhập Tên đăng nhập/Email và Mật khẩu rồi nhấn "Đăng nhập" | Kiểm tra tính hợp lệ cơ bản của định dạng dữ liệu được cung cấp. |
| 3 | Chờ xét duyệt | Hệ thống kiểm tra thông tin đối chiếu với kho dữ liệu an toàn. |
| 4 | *Trường hợp thành công:* Tài khoản hợp lệ | Thiết lập phiên làm việc bảo mật, tự động chuyển hướng khách hàng về lại Trang Chủ hoặc chức năng trước đó. |
| 5 | *Trường hợp thất bại:* Sai thông tin | Giữ nguyên trang, xóa trống ô mật khẩu, hiển thị thông báo lỗi yêu cầu kiểm tra lại. |

### 3. Yêu cầu dữ liệu (Data Requirements)
#### 3.1. Dữ liệu đầu vào (Input Fields - Login Form)
* **Tài khoản đăng nhập:** Yêu cầu nhập đúng Email hoặc tên người dùng hợp lệ đã đăng ký trước đó, bắt buộc.
* **Mật khẩu:** Chuỗi văn bản được ẩn đi dưới dạng dấu sao/chấm tròn, yêu cầu nhập đúng độ dài tối thiểu, bắt buộc.
* **Ghi nhớ đăng nhập:** Lựa chọn hộp kiểm (Checkbox) tùy chọn để lưu phiên làm việc lâu hơn trên thiết bị tin tưởng.

#### 3.2. Dữ liệu đối chiếu (Database Verification)
* **Thông tin xác thực:** 
  * Tên đăng nhập (hoặc Email) của người dùng phải tồn tại duy nhất trên hệ thống.
  * So khớp thông tin Mật khẩu đã được mã hóa an toàn (không hề lưu giữ dạng văn bản thuần túy).
  * Trạng thái tài khoản người dùng phải ở diện khả dụng (Không bị khóa do vi phạm hoặc đình chỉ).

### 4. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Truyền tải đường truyền mạng:** Toàn bộ thông tin đăng nhập của người dùng phải được truyền tải thông qua kết nối có chứng chỉ mã hóa dữ liệu, đảm bảo không bị nghe lén.
* **Chống đoán mật khẩu liên tục:** Nếu có ai đó cố tình nhập sai mật khẩu quá số lần quy định, hệ thống sẽ tạm khóa chức năng đăng nhập của địa chỉ thiết bị đó trong một khoảng thời gian nhằm chặn nguy cơ bị dò mật khẩu.
* **Bảo vệ mã độc gửi chéo:** Áp dụng lớp xác thực an toàn trên mọi biểu mẫu gửi yêu cầu để ngăn chặn việc bị nhúng mã độc nạp dữ liệu sai cấu trúc.
* **Mã hóa một chiều mật khẩu:** Mật khẩu của khách hàng hoàn toàn biến đổi theo thuật toán một chiều. Không một tổ chức hay người trực quản trị cơ sở dữ liệu nào có thể tra cứu và khôi phục được mật khẩu gốc đó.

### 5. Trường hợp ngoại lệ & Xử lý lỗi (Edge Cases)
* **Trường hợp:** Thông tin tên đăng nhập và mật khẩu hoàn toàn không có sự khớp nối ở một trong hai thông tin này.
  * **Xử lý:** Trả về thông báo cảnh báo chung cực kỳ trung lập: *"Tên đăng nhập hoặc mật khẩu không chính xác"*. Tuyệt đối không chỉ rõ rằng lỗi nằm ở đâu nhằm ngăn chặn việc dò quét những email nào đã tồn tại trong kho dữ liệu.
* **Trường hợp:** Khách hàng đăng nhập đúng chuẩn các thông tin nhưng tài khoản của họ đang bị nằm trong danh sách cấm từ nhà quản trị.
  * **Xử lý:** Hệ thống dừng việc cấp quyền tạo phiên liên lạc, lập tức hiển thị lỗi chú thích: *"Tài khoản của bạn tạm thời đang bị khóa hoặc nằm trong hạn chế. Vui lòng liên hệ bộ phận hỗ trợ ngay"*.
* **Trường hợp:** Phiên làm việc sau một khoảng thời gian dài bị bỏ không có sự tương tác sử dụng.
  * **Xử lý:** Tự động đăng xuất người dùng khỏi hệ thống một cách im lặng. Tại lần nhấp tương tác kế tiếp đòi hỏi quyền hạn, hệ thống sẽ yêu cầu họ đăng nhập lại để xác thực chủ sở hữu thiết bị đích thực.

### 6. Giao diện (UI/UX)
* Thiết kế khung biểu mẫu đăng nhập tuân thủ khả năng tương thích trải nghiệm trên cả thiết bị điện thoại, máy tính bảng và màn hình lớn.
* Các nút bấm tương tác ("Đăng nhập") phải thay đổi trạng thái hình dáng hiệu ứng (như nút bị xám đi, quay vòng tải tín hiệu) và đóng băng hoàn toàn ngay khi đang chờ hệ thống xử lý để chống rủi ro nhấn đúp.
* Hỗ trợ tích hợp sẵn biểu tượng bật/tắt mắt thường ở phần khung văn bản điền Mật khẩu để người dùng kiểm ra được chuỗi ký tự họ vừa gõ mà không bị làm mờ một cách tùy ý.
* Bổ sung nhanh tiện ích phím tắt (Nhấn phím Enter trên bàn phím cứng) để đẩy luồng nộp biểu mẫu thân thiện hơn đối với thao tác trên máy tính cá nhân.

---
