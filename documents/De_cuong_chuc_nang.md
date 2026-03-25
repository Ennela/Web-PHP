# ĐỀ CƯƠNG CHỨC NĂNG DỰ KIẾN - WEBSITE BÁN GIÀY THÔNG MINH

## 1. Các chức năng đối với Người Dùng (Khách hàng)

### 1.1. Quản lý tài khoản cá nhân
- **Đăng ký / Đăng nhập**: Khách hàng có thể tạo mới một tài khoản bằng email hoặc số điện thoại, đăng nhập vào hệ thống để lưu trữ thông tin cá nhân, đồng bộ giỏ hàng trên nhiều thiết bị và dễ dàng theo dõi trạng thái các đơn hàng đã đặt.
- **Quên mật khẩu**: Hỗ trợ khách hàng lấy lại quyền truy cập tài khoản thông qua OTP hoặc link khôi phục gửi qua email đã đăng ký.
- **Đăng xuất**: Thoát khỏi phiên làm việc hiện tại một cách an toàn nhằm bảo vệ thông tin tài khoản trên các thiết bị công cộng.
- **Quản lý thông tin tài khoản**: Xem và cập nhật hồ sơ cá nhân bao gồm họ tên, ngày sinh, địa chỉ nhận hàng mặc định, số điện thoại, khả năng thay đổi mật khẩu định kỳ để tăng cường bảo mật và xem lại chi tiết lịch sử mua sắm.

### 1.2. Chức năng Trang Chủ & Khám phá sản phẩm
- **Xem banner quảng cáo & Carousel**: Hiển thị trực quan các sự kiện ưu đãi, chương trình khuyến mãi hiện hành, bộ sưu tập giày mới ra mắt bằng các slider hình ảnh động (Carousel) bắt mắt trên đầu trang chủ.
- **Danh mục sản phẩm**: Hiển thị đa dạng danh sách các phân loại giày (ví dụ: Sneakers, Running, Training, Boots) và phân loại theo giới tính (Nam, Nữ, Unisex), tự động điều hướng danh sách nhóm phù hợp.
- **Sản phẩm mới / nổi bật / bán chạy**: Tự động trích xuất các mẫu giày mới cập bến, sản phẩm có số lượng bán nhiều nhất, hoặc được đánh giá cao nhằm thu hút cho khách hàng ngay từ đầu.

### 1.3. Chức năng Sản phẩm (Shop)
- **Xem danh sách sản phẩm**: Hiển thị bố cục dạng lưới (Grid) của tất cả mẫu giày theo các phân mảnh,  kèm theo giá bán (giá gốc/khuyến mãi), tên sản phẩm, và các nhãn đánh dấu (như "New", "Sale -20%").
- **Lọc và phân trang**: Cho phép người dùng kết hợp hiển thị các tiêu chí lọc tinh chuẩn (lọc theo khoảng giá, thương hiệu, màu sắc, size) và tự động phân trang (Pagination) nhằm tối ưu tốc độ tải trang.
- **Xem chi tiết sản phẩm**: Cung cấp thông tin trực quan cho mỗi đôi giày bao gồm: bộ sưu tập nhiều ảnh chụp mượt mà, mô tả chi tiết từng chất liệu, bảng tư vấn chọn size (Size chart), quy định bảo hành, và số lượng minh bạch còn trong kho.
- **Tìm kiếm sản phẩm động**: Thanh công cụ tìm kiếm tức thì (Search Bar) hỗ trợ gọi từ khóa thông minh, cung cấp tính năng Live Search.

### 1.4. Chuyên trang Blog / Bài viết
- **Giới thiệu**: Tóm tắt lại câu chuyện thương hiệu, sứ mệnh phục vụ, giới thiệu đội ngũ bán hàng và các chính sách bảo hành, hoàn trả đặc trưng.
- **Danh sách báo/tin tức bài viết**: Cập nhật tần số cao các tin tức hot, trend thời trang giày dép, mẹo hướng dẫn vệ sinh giày và các quy mô sự kiện do shop triển khai.
- **Chi tiết bài viết**: Nội dung đọc được đầu tư kỹ lưỡng cùng hình ảnh, video chất lượng minh họa rõ nét nhất chủ đề của bài viết.

### 1.5. Chức năng Giỏ hàng và Đặt hàng
- **Thêm sản phẩm vào giỏ**: Tiện ích cho phép lựa chọn chính xác size, màu, và số lượng. Hệ thống thêm sản phẩm ngay bằng giao tiếp ngầm AJAX không làm giật trang.
- **Xem và Cập nhật giỏ hàng**: Quản lý giỏ hàng ở dạng Fly-out (Mini-cart) hoặc trang giỏ hàng chi tiết. Cho phép thay đổi số lượng từng mặt hàng, tự động cập nhật tổng tiền, hoặc xóa sản phẩm khỏi giỏ.
- **Thông tin đặt hàng (Checkout Form)**: Form kiểm duyệt tối ưu: tự động nhận diện tài khoản, điền lại thông tin có sẵn, yêu cầu điền đầy đủ và đúng định dạng các mục người nhận, địa chỉ cụ thể và phần ghi chú đơn hàng.

### 1.6. Chức năng Thanh Toán
- **Thanh toán khi nhận hàng (COD)**: Nền tảng ghi nhận hóa đơn chờ đóng gói để thu tiền khi sản phẩm chuyển tới tận tay người nhận hàng bằng đơn vị vận chuyển (shipper).
- **Thanh toán trực tuyến (VNPAY)**: Tích hợp bảo mật 100% cổng thanh toán VNPAY, cho phép thanh toán liền mạch thông qua tất cả thiết bị bằng thẻ ATM nội địa, thẻ quốc tế hoặc QR Code, ví điện tử với tốc độ tức thời.

### 1.7. Hỗ trợ khách hàng
- **Liên hệ**: Mẫu biểu mẫu thu thập thông tin để khách hàng có thể gửi thắc mắc, kiểm tra hệ thống, phản hồi, hoặc yêu cầu hỗ trợ trực tiếp đến bộ phận CSKH.

---

## 2. Các chức năng đối với Quản trị viên (Admin)

### 2.1. Tổng quan - Dashboard
- **Đăng nhập quản trị**: Trang đăng nhập với bảo mật cao, tách biệt với giao diện khách hàng.
- **Thống kê / Báo cáo**:
  - Trực quan hóa dữ liệu kinh doanh bằng các bảng biểu đồ báo cáo doanh thu theo ngày, tuần, tháng.
  - Số liệu số hóa hiện trạng đơn hàng bao gồm: Số đơn thành công, số đơn đang chờ, số đơn đã hủy.
  - Thống kê top sản phẩm bán chạy, sản phẩm sắp hết hàng, độ tăng trưởng số lượng người dùng.

### 2.2. Quản lý Sản phẩm và Danh mục
- **Quản lý Danh mục**: Thêm mới, chỉnh sửa cấu trúc danh mục sản phẩm, sắp xếp vị trí hiển thị hoặc ẩn các danh mục giày đặc biệt.
- **Quản lý Sản phẩm**: 
  - Khởi tạo và thiết lập sản phẩm đầy đủ với ảnh đại diện, album ảnh phụ, mã chi tiết, chỉnh sửa nội dung bằng Rich Text Editor, thiết lập giá trị thuộc tính.
  - Quản lý trạng thái tồn kho, kiểm soát lượng hàng biến động liên tục sau mỗi lượt đặt hàng/hủy đơn.
  - Xóa hoặc vô hiệu hóa sản phẩm khỏi hệ thống.

### 2.3. Quản lý Đơn hàng
- **Danh sách đơn hàng**: Trình bày toàn bộ các đơn hàng (từ COD đến VNPAY) với bộ lọc, công cụ tìm kiếm linh hoạt.
- **Chi tiết đơn hàng**: Quản lý sâu sát từng đơn hàng (thông tin khách hàng, số lượng từng mẫu mã đã mua, giá cước, và ghi chú).
- **Xử lý trạng thái (Order Flow)**:  Chuyển đổi các trạng thái đơn vị thủ công trên Admin panel (Chờ xử lý, Đã xác nhận, Đang đóng gói, Đang giao hàng, Đã giao thành công, Hủy đơn).

### 2.4. Quản lý Khách hàng và Người dùng
- **Danh sách Khách hàng**: Theo dõi danh sách tài khoản, chi tiêu trung bình và số lượng đơn hàng của từng cá nhân.
- **Quản lý Quyền truy cập**: Tạm ngưng, cấm chức năng (Ban) hoặc kích hoạt lại tài khoản đối với ng dùng vi phạm. Thiết lập vai trò Admin - Nhân viên.

### 2.5. Quản lý Tin tức (Blog) & Nội dung
- **Đăng/Chỉnh sửa bài viết**: Trình soạn thảo (WYSIWYG) hỗ trợ admin toàn quyền thao tác tạo bài đăng phong phú, ảnh nổi bật.
- **Quản lý Banner**: Cập nhật ảnh chính, banner trang chủ quảng bá sự kiện linh hoạt mỗi dịp lễ tết.

---

## 3. Đặc điểm kỹ thuật và nổi bật của hệ thống
- **Tích hợp thanh toán an toàn IPN (VNPAY)**: Cơ chế Instant Payment Notification đảm bảo việc đối soát và xác thực trạng thái online vô cùng chính xác, khắc phục bất cập khách đóng trình duyệt sau khi trả thẻ.
- **Trải nghiệm thao tác (AJAX)**: Ưu tiên không load lại website với những tác vụ nhẹ. Chuyên nghiệp hóa trang bị tối ưu hóa trải nghiệm tương thích cao (Responsive) cho mọi kích thước smartphone.
- **Hệ thống xử lý kịch bản gửi Email (PHPMailer)**: Chạy kịch bản tự động cảnh báo từ giao thức SMTP của Google (gửi Email xác nhận hóa đơn, thông báo tồn kho Admin).
- **Bảo mật An Toàn (Security)**: Lớp hóa mật khẩu Bcrypt băm, ngăn chặn tất cả hành động đưa truy vấn tấn công lỗ hổng XSS / SQL Injection thông qua xử lý PDO Prepared Statements.
