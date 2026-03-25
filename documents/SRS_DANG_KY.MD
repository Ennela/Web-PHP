# Software Requirement Specification (SRS)
## Chức năng: Hệ thống Đăng ký tài khoản (Register)
**Mã chức năng:** AUTH-REGISTER-01  
**Trạng thái:** Draft / Review  
**Người soạn thảo:** Đội ngũ phát triển
**Vai trò:** Lead Developer

---

### 1. Mô tả tổng quan (Description)
Hệ thống cho phép người dùng vãng lai hoặc khách hàng mới tạo lập một tài khoản hồ sơ cá nhân trên nền tảng cửa hàng. Việc đăng ký tài khoản giúp khách hàng lưu trữ thông tin giao hàng, đồng bộ hóa lịch sử giỏ hàng, thuận tiện theo dõi trạng thái đơn hàng và được hưởng các đặc quyền khuyến mãi dành riêng cho thành viên có đăng nhập.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động người dùng | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Truy cập trang Đăng ký tài khoản từ thanh điều hướng | Hệ thống bắt đầu hiển thị biểu mẫu điền thông tin Đăng ký. |
| 2 | Cung cấp thông tin bắt buộc (Họ tên, Email/Số điện thoại, Mật khẩu) | Hệ thống kiểm tra nhẹ tính hợp lệ cơ bản của dữ liệu nhập (định dạng, độ dài nội dung). |
| 3 | Người dùng nhấn chọn nút "Tạo tài khoản" | Máy chủ đối chiếu lại tổng quát dữ liệu với kho dự trữ xem Email/Số điện thoại đã bị trùng lặp hay chưa. |
| 4 | *Trường hợp thành công:* Thông tin hợp lệ và hoàn toàn mới mẻ | Lưu thông tin người dùng mới này vào hệ thống, thông báo tin báo "Đăng ký thành công" và tự động điều hướng sang trang Đăng nhập. |
| 5 | *Trường hợp thất bại:* Dữ liệu sai cấu trúc hoặc nội dung bị trùng lặp | Dừng lại tiến trình, giữ nguyên thông tin vừa nhập (ngoại trừ ô vùng chứa mật khẩu), bôi màu cảnh báo đoạn bị lỗi kèm hiển thị nhắc nhở khắc phục. |

### 3. Yêu cầu dữ liệu (Data Requirements)
#### 3.1. Dữ liệu cần thiết đính kèm yêu cầu (Input Fields)
* **Họ và tên:** Yêu cầu người dùng nhập tên đầy đủ bằng chuẩn văn bản thông thường, bắt buộc.
* **Email hoặc Số điện thoại:** Thông tin định danh chính đóng vai trò ID, yêu cầu đáp ứng chuẩn định dạng (Có @ với hòm thư điện tử email, hoặc đủ 10 chữ số riêng cho di động), bắt buộc.
* **Mật khẩu:** Chuỗi thông điệp văn bản bảo mật (được ẩn đi), yêu cầu phải đáp ứng độ bền và độ dài tối thiểu (ví dụ: ít nhất là 6 đến 8 ký tự), bắt buộc.
* **Xác nhận mật khẩu:** Yêu cầu khách hàng gõ lại chính xác chuỗi mật khẩu vừa viết ở ô ngay phía trên để đề phòng trường hợp gõ nhầm chữ, bắt buộc.
* **Đồng ý điều khoản cơ bản:** Thao tác lựa chọn hộp kiểm nhỏ (Checkbox) nhằm phản ánh đồng ý với bộ quy tắc dịch vụ của chính cửa hàng này, quy định bắt buộc đánh dấu check.

#### 3.2. Dữ liệu được khởi tạo ở hệ thống (Data Storage)
* **Hồ sơ thông tin thành viên (User Profile):**
  * Những thông tin cá nhân liên hệ dùng cho giao thức đặt hàng (Họ Tên, Định danh liên lạc).
  * Chuỗi chứa mật khẩu gốc đã được biến đổi mã hóa bảo vệ bằng thuật toán nhằm mục đích ẩn nguyên dạng (không sao kê lại bản nháp ban đầu của người dùng).
  * Trạng thái thông qua tài khoản lập tức được gán cờ mặc định là "Đang hoạt động" để có thể dùng ngay mà không cần đợi Admin phê duyệt.
  * Phân luồng quyền lực mặc định sẽ là "Khách hàng thông thường".
  * Ngày giờ ghi nhận thông qua tạo lập (phút/giờ/ngày), tiện ích cho các kịch bản chương trình hậu mãi hoặc gửi quà tri ân kỷ niệm tài khoản.

### 4. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Chống nạn tạo tài khoản ảo/tự động:** Biểu mẫu đăng ký cần tích hợp các kịch bản xác minh (ví dụ: bước thủ công Captcha) để ngăn chặn các máy chủ lập trình cố tình nhét hàng nghìn nick rác làm suy yếu tài nguyên.
* **Biểu đồ độ khó mật khẩu:** Quá trình điền thông tin có thể hỗ trợ hiển thị lời cảnh báo độ tin cậy của mật khẩu người khách định đặt, chặn lại nếu đặt chuỗi quá dễ (ví dụ: 123456).
* **Bảo vệ mã độc gửi chéo độc hại:** Mọi ô chữ cần có chức năng chống khai thác lổ hổng mạng (XSS), tiến hành thanh lọc vô hóa những dòng tin cố nhồi nhét chữ ký độc hại.
* **Toàn diện mã hóa mật khẩu:** Mật khẩu sau khi được thu thập sẽ bị dịch mã theo chiều tịnh tiến, tức là các thành viên nhóm làm việc dù có quyền lực cao đến mấy trên hệ thống gốc cũng chỉ thấy được chuỗi những ký tự vô nghĩa lộn xộn, không thể giải mã lại tìm ra phiên bản gốc.

### 5. Trường hợp ngoại lệ & Xử lý lỗi (Edge Cases)
* **Trường hợp:** Số lượng dữ liệu là Số điện thoại hoặc hộp thư Email mà khách hàng chuẩn bị đăng ký đó đã được một chủ khoản hệ thống khác chiếm dụng từ trước.
  * **Xử lý:** Hiện đỏ hiển thị cảnh báo ngay tại vùng vi phạm: *"Số điện thoại/Email này đã được ghi danh vào hệ thống. Xin bấm vào mục Quên mật khẩu nếu đây chính là tài khoản cũ của bạn"*.
* **Trường hợp:** Phần ô "Mật khẩu" cùng phần ô "Xác nhận Lại mật khẩu" được gõ thành nội dung không tương đồng do vội vã.
  * **Xử lý:** Không duyệt cho máy tiếp nhận, in thông điệp *"Hai luồng mật khẩu khai báo không khớp nhau trùng lặp. Đề nghị sửa sai cấu trúc"*.
* **Trường hợp:** Bỏ sót không chọn tích vào ô vuông quy định "Đồng ý với nền sử dụng".
  * **Xử lý:** Vẫn cấm nộp lệnh tới máy chủ. Vùng đó sẽ bị nhấp nháy chuyển màu và hiển thị nhãn lỗi.

### 6. Giao diện (UI/UX)
* Khung thao tác chữ tự động chèn kèm những thông điệp kiểu chữ ẩn nhạt để dẫn dụ/làm mẫu cho người viết (VD: *nguyenvana_123@gmail.com*).
* Nút "Tạo mới tài khoản" ngay khi kích hoạt sẽ chuyển pha mờ đi, kết hợp thể hiện vòng tải tín hiệu (Loading Spinner) với mục tiêu để hệ thống tự xử, tránh gặp sự cố nhấp nháy nhiều lần cùng một lệnh.
* Hiệu suất nhập liệu mượt mà, gọi đúng bàn phím cảm ứng hỗ trợ tương đương với dữ kiện (Dĩ nhiên bàn phím con số tự động chường ra khi màn hình tới cấu kiện cần gõ Số Điện Thoại).

---
