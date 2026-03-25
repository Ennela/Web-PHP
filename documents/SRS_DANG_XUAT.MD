# Software Requirement Specification (SRS)
## Chức năng: Hệ thống Đăng xuất (Logout)
**Mã chức năng:** AUTH-LOGOUT-01  
**Trạng thái:** Draft / Review  
**Người soạn thảo:** Đội ngũ phát triển
**Vai trò:** Lead Developer

---

### 1. Mô tả tổng quan (Description)
Hệ thống cung cấp chức năng đăng xuất để người dùng có thể chủ động kết thúc phiên làm việc an toàn, dọn dẹp các lịch sử truy cập nhạy cảm, nhằm bảo vệ thông tin liên lạc của tài khoản trên các thiết bị chia sẻ tập thể hoặc không an toàn.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động người dùng | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Truy cập vào khu vực chứa menu tài khoản cá nhân | Hiển thị bảng điều khiển thả xuống chứa nút mục Đăng xuất. |
| 2 | Nhấn chọn mục "Đăng xuất" | Gửi yêu cầu chấm dứt phiên làm việc bảo mật hiện tại tới máy chủ. |
| 3 | Chờ hệ thống thực thi lệnh | Xóa toàn bộ dữ liệu tạm dùng định danh (phiên liên lạc/bánh quy chứng thực) trên thiết bị. |
| 4 | Hoàn tất thao tác | Chuyển hướng người dùng về lại màn hình Trang Chủ với tư cách là một khách vãng lai hoàn toàn mới. |

### 3. Yêu cầu dữ liệu (Data Requirements)
#### 3.1. Dữ liệu cần thiết đính kèm yêu cầu (Request Payload)
* **Khóa phiên hoạt động:** Mã định danh bí mật liên kết với phiên làm việc của người dùng hiện tại, nhằm hỗ trợ hệ thống đối chiếu và xác nhận đúng người trước khi cho phép ngắt kết nối.

#### 3.2. Trạng thái sau hậu kỳ (Post-conditions)
* **Kho lưu trữ trên máy chủ:** Bản ghi phiên tương ứng của người dùng này phải bị loại bỏ chức năng vô hiệu hóa.
* **Kho lưu trữ cục bộ (Client-side):** Các chứng thực truy cập còn đọng lại trên trình duyệt khách hàng bắt buộc phải được quét sạch sẽ toàn diện không chừa một dấu vết nào.

### 4. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Ngăn ngừa Tấn công Giả mạo (CSRF):** Nút Đăng xuất không thể đơn thuần chỉ là một đường dẫn kích hoạt tĩnh (ví dụ: truy cập link trực tiếp để mất nick), mà cần phải kẹp thêm cơ chế chống giả mạo chữ ký (CSRF token) nhằm không bị kẻ khác cố tình ép nạn nhân đăng xuất ngoài ý muốn ở hệ thống website thứ ba.
* **Chấm dứt Phiên làm việc Thực thụ:** Đăng xuất không chỉ là việc đổi trạng thái "đã thoát" ở giao diện hiển thị, mà máy chủ bắt buộc phải đánh dấu vô hiệu hóa hoàn toàn mã phiên đó (Invalidate Session). Ngay cả khi người dùng nhấn nút chuyển lại trang cũ của trình duyệt thì cũng không thể có quyền tạo hay làm mới giao dịch.

### 5. Trường hợp ngoại lệ & Xử lý lỗi (Edge Cases)
* **Trường hợp:** Người dùng đang thiết lập ở trang giỏ hàng hay trang tiến hành thanh toán nhưng lại mở qua một tab thứ hai trên trình duyệt rồi nhấn "Đăng xuất". Tại tab thứ nhất vẫn lầm tưởng là còn quyền hạn phiên và tiếp tục cố gắng gửi yêu cầu cập nhật mua đồ.
  * **Xử lý:** Máy chủ lập tức phát hiện ra tài khoản đã kết thúc phiên. Chặn lại mọi hành động đặt hàng giao dịch này, đưa thông báo và điều hướng người dùng quay trở lại màn hình đăng nhập để nhận dạng danh tính lại từ đầu.
* **Trường hợp:** Phiên giao dịch vốn dĩ đã hết hạn thời gian sử dụng tự nhiên trên máy chủ trước đó do để treo máy quá lâu, tuy nhiên người dùng bấm "Đăng xuất" ở giao diện cũ.
  * **Xử lý:** Vẫn phản hồi kết quả trơn tru và chuyển hướng người dùng trở về màn hình Trang Chủ như không hề có lỗi, tránh phản hồi thông báo rườm rà gây bối rối hoảng sợ cho người dùng trải nghiệm thực tế.

### 6. Giao diện (UI/UX)
* Phân bổ thiết kế rõ ràng Nút/Menu "Đăng xuất" trực diện hoặc ngay trong trung tâm bảng điều khiển người dùng với màu sắc cảnh báo tách biệt hoặc có biểu tượng cánh cửa đi ra.
* Tại môi trường trải nghiệm trên màn hình di động, yêu cầu mục tùy chọn thả xuống phải có độ rộng dư dả để dễ dàng cảm ứng bằng đầu ngón tay cái mà không bị thao tác dính nhầm với thiết lập thay đổi mật khẩu quan trọng.

---
