# Software Requirement Specification (SRS)
## Chức năng: Hệ thống Đặt hàng và Thanh toán (Checkout & Payment)
**Mã chức năng:** CHECKOUT-01  
**Trạng thái:** Draft / Review  
**Người soạn thảo:** Đội ngũ phát triển
**Vai trò:** Lead Developer

---

### 1. Mô tả tổng quan (Description)
Cung cấp luồng quy trình cho phép khách hàng chuyển đổi các sản phẩm đã chọn trong Giỏ hàng (Cart) thành một Đơn hàng (Order) hoàn chỉnh. Quá trình bao gồm việc thu thập thông tin người nhận, hiển thị chi phí tổng, và tích hợp các phương thức thanh toán an toàn (thanh toán khi nhận hàng - COD và thanh toán trực tuyến qua VNPAY).

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động người dùng | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Truy cập trang Giỏ hàng và nhấn "Thanh toán/Đặt hàng" | Hệ thống điều hướng người dùng tới trang Thanh toán (Checkout). |
| 2 | Điền thông tin giao nhận hàng | Kiểm tra tính hợp lệ và sự tồn tại của dữ liệu (điện thoại, địa chỉ). |
| 3 | Lựa chọn phương thức thanh toán (COD hoặc VNPAY) | Hệ thống tự động tính toán và báo cáo tổng số tiền cuối cùng. |
| 4 | Nhấn nút "Xác nhận đặt hàng" | Sinh mã đơn hàng duy nhất, lưu thông tin vào cơ sở dữ liệu. Xử lý logic theo phương thức đã chọn. |
| 5a | *Đối với COD:* Hoàn tất hành động | Hiển thị màn hình "Đặt hàng thành công", hệ thống gửi Email xác nhận hóa đơn. |
| 5b | *Đối với VNPAY:* Xác nhận chuyển hướng thanh toán | Khởi tạo yêu cầu thanh toán với chữ ký bảo mật và chuyển hướng sang cổng thanh toán. |
| 6 | Thực hiện thanh toán trên cổng VNPAY | VNPAY xử lý yêu cầu, trừ tiền khách và đồng thời gọi lại API trả kết quả. |
| 7 | Cập nhật kết quả thanh toán | Hệ thống đối soát trạng thái tức thời; Cập nhật tình trạng "Đã thanh toán", làm rỗng giỏ hàng cá nhân. |

### 3. Yêu cầu dữ liệu (Data Requirements)
#### 3.1. Dữ liệu đầu vào (Input Fields - Checkout Form)
* **Họ và tên:** Yêu cầu nhập tên đầy đủ bằng văn bản, bắt buộc.
* **Số điện thoại:** Yêu cầu đúng tiêu chuẩn định dạng (10 chữ số), bắt buộc.
* **Địa chỉ giao hàng:** Chi tiết số nhà, đường, xã/phường, quận/huyện, bắt buộc.
* **Phương thức thanh toán:** Lựa chọn một trong các phương thức (thường là thiết kế Radio button), mặc định chọn Thanh toán khi nhận hàng (COD).
* **Ghi chú đơn hàng:** Văn bản tự do, tùy chọn.

#### 3.2. Dữ liệu lưu trữ thông tin
* **Lưu trữ thông tin Đơn Hàng**: 
  * Mã định danh hoặc mã hóa đơn ngẫu nhiên duy nhất (VD: DH1029).
  * Liên kết với thông tin tài khoản (nếu khách mua đã đăng nhập trên hệ thống).
  * Thông tin giao nhận hàng: Họ tên, số điện thoại, địa chỉ nhận hàng đích, ghi chú cho tài xế/người đóng gói.
  * Thông tin giao dịch: Tổng thành tiền, phương thức thanh toán được áp dụng, trạng thái hiện tại (Chờ xử lý, Đang giao, Đã thanh toán, Đã hủy).
* **Lưu trữ thông tin Chi Tiết Đơn Hàng**: 
  * Danh sách chi tiết các món đồ đã mua trong đơn: Mã sản phẩm/Sku, kích cỡ, số lượng, và đặc biệt là mức giá được chốt tại thời điểm lập hóa đơn, đảm bảo không thay đổi dù giá gốc sau này có cập nhật.

### 4. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Toàn vẹn hàng hóa:** Kiểm tra lại số lượng tồn kho theo thời gian thực (Real-time stock) ngay tại thời điểm khách bấm nút Xác nhận. Tránh tình huống báo lỗi ảo hoặc hết hàng do thao tác mua trùng lặp với người khác.
* **Đồng bộ tồn kho:** Sau khi duyệt đơn thành công (chờ xử lý hoặc đã thanh toán trực tuyến), hệ thống phải trừ ngay lập tức số lượng hàng hóa nằm trong kho để nhường slot.
* **Tích Hợp Thanh Toán Mạng:** Mọi dữ liệu chuyển dịch sang cổng thanh toán cần phải được mã hóa thành chữ ký điện tử an toàn kết hợp với mã bí mật của đối tác, tránh rò rỉ dữ liệu thẻ hay nguy cơ bị bên thứ ba sửa giá trên trình duyệt.
* **Cập nhật trạng thái Độc lập:** Trạng thái của đơn hàng thanh toán online luôn luôn phải được định đoạt hoàn toàn bởi các kịch bản đối soát ngầm gửi từ máy chủ đối tác tới máy chủ cửa hàng (server-to-server connection), loại bỏ việc dựa vào luồng duyệt của người dùng, qua đó xóa rủi ro sai lệch dữ liệu do người dùng đóng đột ngột trình duyệt.

### 5. Trường hợp ngoại lệ & Xử lý lỗi (Edge Cases)
* **Trường hợp:** Số lượng khách muốn mua lại nhiều hơn tổng số lượng hiện đang còn trong kho.
  * **Xử lý:** Tải lại trang Thanh toán và bắt thông báo lỗi: *"Sản phẩm [Tên sản phẩm] hiện phần chỉ còn [X] mẩu. Vui lòng quay trở lại giỏ hàng và điều chỉnh số lượng"*.
* **Trường hợp:** Người dùng chủ động chuyển Hủy Bỏ (Cancel) thanh toán trên trải nghiệm màn hình của ứng dụng thanh toán online.
  * **Xử lý:** Đối tác sẽ trả về mã lỗi đặc biệt. Hệ thống chuyển đổi tình trạng là "Thanh toán thất bại", và bảo lưu toàn bộ các món đồ đã chọn trong giỏ hàng để khách trải nghiệm thử lại một cách dễ dàng.
* **Trường hợp:** Chữ ký điện tử đối soát trạng thái từ nhà cung cấp dịch vụ không trùng khớp.
  * **Xử lý:** Hệ thống tự động từ chối hoàn toàn việc phê duyệt tiếp, cảnh báo dòng chữ *"Chữ ký giao dịch không hợp pháp"*, ghi nhận vào Log Audit sự cố nguy hiểm nhằm phòng ngừa hành vi Hack URL.

### 6. Giao diện (UI/UX)
* Khung biểu mẫu điền thông tin và Khung hiển thị tổng kết chi phí thanh toán được chia cục diện hai cột song song để dễ trực quan theo dõi.
* Khi nhấn nút "Xác nhận", nút bấm lập tức bị khóa lại (Disable state) và hiển thị biểu tượng tải vòng tròn (Loading spinner) nhằm ngăn việc nhấn đúp liên tục gây hệ quả trùng lặp đơn hàng (Double submit).
* Cung cấp các công cụ tự động điền sẵn (Autofill data) như tên, sdt, địa chỉ lấy từ thông tin hồ sơ của các tài khoản đã đăng nhập để đẩy nhanh tối đa tiến trình giao dịch chốt đơn.

---
