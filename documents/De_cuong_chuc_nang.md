# ĐỀ CƯƠNG CHỨC NĂNG DỰ KIẾN - WEBSITE BÁN GIÀY THÔNG MINH

## 1. Các chức năng đối với Người Dùng (Khách hàng)

### 1.1. Quản lý tài khoản cá nhân
- **Đăng ký / Đăng nhập**: Khách hàng có thể tạo mới một tài khoản bằng email hoặc số điện thoại, đăng nhập vào hệ thống để lưu trữ thông tin cá nhân, đồng bộ giỏ hàng trên nhiều thiết bị và dễ dàng theo dõi trạng thái các đơn hàng đã đặt.
- **Quên mật khẩu**: Hỗ trợ khách hàng lấy lại quyền truy cập tài khoản thông qua token bảo mật 256-bit gửi qua email (Mailjet API), có thời hạn 30 phút.
- **Đăng xuất**: Thoát khỏi phiên làm việc hiện tại một cách an toàn nhằm bảo vệ thông tin tài khoản trên các thiết bị công cộng.
- **Quản lý thông tin tài khoản**: Trang quản lý tài khoản đầy đủ gồm 5 tab chức năng:
  - **Thông tin cá nhân**: Xem và chỉnh sửa họ tên, email, SĐT, ngày sinh, giới tính; Upload avatar (JPG/PNG/WebP, max 2MB) qua AJAX.
  - **Đổi mật khẩu**: Nhập mật khẩu cũ, mật khẩu mới (có Password Strength Indicator 4 mức), xác nhận mật khẩu; mã hóa Bcrypt.
  - **Sổ địa chỉ**: Quản lý tối đa 5 địa chỉ giao hàng (thêm/sửa/xóa), tích hợp API tỉnh/huyện/xã Việt Nam, đặt địa chỉ mặc định.
  - **Size giày yêu thích**: Lưu size theo hệ EU/US/CM, ghi chú sở thích cá nhân (chân rộng, bàn chân dẹt...), hệ thống tự động gợi ý size khi thêm sản phẩm vào giỏ.
  - **Đơn hàng của tôi**: Xem danh sách 20 đơn hàng gần nhất với mã đơn 9 số, trạng thái màu sắc, liên kết xem chi tiết.

### 1.2. Chức năng Trang Chủ & Khám phá sản phẩm
- **Hero Carousel (Bootstrap 5)**: Slider hình ảnh động với 3 slide, caption chuyển động slide-in, nút CTA liên kết đến chi tiết sản phẩm.
- **Editorial Section**: Khối giới thiệu sản phẩm nổi bật dạng Editorial với ảnh góc bo tròn, hiệu ứng hover float, và nút "Sở hữu ngay".
- **Features Section**: 4 khối USP (Giao hàng toàn quốc, Thanh toán khi nhận, Bảo hành dài hạn, Dễ dàng đổi hàng) với icon và hover effect.
- **Bestseller Section**: 3 sản phẩm bán chạy nhất trích xuất từ CSDL, hiển thị card với ảnh, tên, giá, nút xem chi tiết.
- **Team Section**: Giới thiệu đội ngũ 3 thành viên với ảnh, chức vụ và liên kết mạng xã hội.

### 1.3. Chức năng Sản phẩm (Shop)
- **Xem danh sách sản phẩm**: Hiển thị bố cục dạng lưới (Grid) của tất cả mẫu giày theo các phân mảnh, kèm theo giá bán (giá gốc/khuyến mãi), tên sản phẩm, và các nhãn đánh dấu (như "New", "Sale -20%").
- **Lọc và phân trang**: Cho phép người dùng kết hợp hiển thị các tiêu chí lọc tinh chuẩn (lọc theo khoảng giá, thương hiệu, màu sắc, size) và tự động phân trang (Pagination) nhằm tối ưu tốc độ tải trang.
- **Xem chi tiết sản phẩm**: Cung cấp thông tin trực quan cho mỗi đôi giày bao gồm: bộ sưu tập nhiều ảnh chụp mượt mà, mô tả chi tiết từng chất liệu, hiển thị tồn kho real-time theo từng size, bảng tư vấn chọn size (Size chart), quy định bảo hành, và số lượng minh bạch còn trong kho.
- **Tìm kiếm sản phẩm động**: Thanh công cụ tìm kiếm tức thì (Search Bar) hỗ trợ gọi từ khóa thông minh, cung cấp tính năng Live Search.

### 1.4. Chuyên trang Blog / Bài viết
- **Giới thiệu**: Tóm tắt lại câu chuyện thương hiệu, sứ mệnh phục vụ, giới thiệu đội ngũ bán hàng và các chính sách bảo hành, hoàn trả đặc trưng.
- **Danh sách báo/tin tức bài viết**: Cập nhật tần số cao các tin tức hot, trend thời trang giày dép, mẹo hướng dẫn vệ sinh giày và các quy mô sự kiện do shop triển khai.
- **Chi tiết bài viết**: Nội dung đọc được đầu tư kỹ lưỡng cùng hình ảnh, video chất lượng minh họa rõ nét nhất chủ đề của bài viết.

### 1.5. Chức năng Giỏ hàng và Đặt hàng
- **Thêm sản phẩm vào giỏ**: Lựa chọn chính xác size (với kiểm tra tồn kho real-time), màu, và số lượng. Hệ thống tự động gợi ý size yêu thích đã lưu. Hệ thống thêm sản phẩm ngay bằng giao tiếp ngầm AJAX không làm giật trang.
- **Xem và Cập nhật giỏ hàng**: Giao diện giỏ hàng hiện đại với AJAX auto-save (thay đổi số lượng/size tự động lưu không cần reload). Chọn size trực tiếp trong giỏ hàng với dropdown hiển thị tồn kho. Cảnh báo real-time khi sản phẩm hết hàng hoặc vượt tồn kho. Animation xóa sản phẩm mượt mà.
- **Thông tin đặt hàng (Checkout Form)**: Form kiểm duyệt tối ưu với tính năng Auto-fill: tự động nhận diện người dùng đã đăng nhập để điền trước thông tin thanh toán (Tên, SĐT, Địa chỉ từ sổ địa chỉ, Email), giúp tiết kiệm thời gian và giảm thiểu sai sót.

### 1.6. Chức năng Thanh Toán
- **Thanh toán khi nhận hàng (COD)**: Nền tảng ghi nhận hóa đơn chờ đóng gói để thu tiền khi sản phẩm chuyển tới tận tay người nhận hàng bằng đơn vị vận chuyển (shipper). Tích hợp trừ tồn kho bằng MySQL Transaction (SELECT...FOR UPDATE) chống Race Condition.
- **Thanh toán trực tuyến (VNPAY)**: Tích hợp bảo mật 100% cổng thanh toán VNPAY Sandbox, cho phép thanh toán liền mạch thông qua tất cả thiết bị bằng thẻ ATM nội địa, thẻ quốc tế hoặc QR Code, ví điện tử với tốc độ tức thời.
- **Cơ chế bảo vệ tồn kho**: Chống Race Condition bằng MySQL Transaction + SELECT...FOR UPDATE, tự động hoàn tồn kho khi VNPAY thất bại hoặc admin hủy đơn.

### 1.7. Tra cứu đơn hàng
- **Tra cứu đơn hàng cá nhân**: Đăng nhập để xem danh sách đơn hàng với bộ lọc trạng thái, timeline trực quan (Chờ xử lý → Xác nhận → Đang giao → Đã giao).
- **Tracking đơn hàng qua Email**: Mỗi đơn hàng được gán token bảo mật riêng. Email xác nhận chứa link tracking trực tiếp (`tracking.php?order=XXX&token=YYY`) cho phép khách tra cứu mà không cần đăng nhập.

### 1.8. Hỗ trợ khách hàng
- **Liên hệ**: Mẫu biểu mẫu thu thập thông tin để khách hàng có thể gửi thắc mắc, phản hồi, hoặc yêu cầu hỗ trợ. Tin nhắn được gửi trực tiếp đến email admin qua Mailjet API, kèm Google Maps nhúng.

---

## 2. Các chức năng đối với Quản trị viên (Admin)

### 2.1. Tổng quan - Dashboard
- **Đăng nhập quản trị**: Trang đăng nhập với bảo mật cao (Bcrypt + Prepared Statements), tách biệt với giao diện khách hàng. Hỗ trợ auto-hash migration từ mật khẩu plaintext sang Bcrypt.
- **Thống kê / Báo cáo**:
  - Trực quan hóa dữ liệu kinh doanh bằng các bảng biểu đồ báo cáo doanh thu theo ngày, tuần, tháng.
  - Số liệu số hóa hiện trạng đơn hàng bao gồm: Số đơn thành công, số đơn đang chờ, số đơn đã hủy.
  - Thống kê top sản phẩm bán chạy, sản phẩm sắp hết hàng, độ tăng trưởng số lượng người dùng.

### 2.2. Quản lý Sản phẩm và Danh mục
- **Quản lý Danh mục**: Thêm mới, chỉnh sửa cấu trúc danh mục sản phẩm, sắp xếp vị trí hiển thị hoặc ẩn các danh mục giày đặc biệt.
- **Quản lý Sản phẩm**: 
  - Khởi tạo và thiết lập sản phẩm đầy đủ với ảnh đại diện, album ảnh phụ, mã chi tiết, chỉnh sửa nội dung bằng Rich Text Editor, thiết lập giá trị thuộc tính.
  - Quản lý tồn kho chi tiết theo từng size (bảng `tbl_tonkho`), kiểm soát lượng hàng biến động liên tục sau mỗi lượt đặt hàng/hủy đơn.
  - Xóa hoặc vô hiệu hóa sản phẩm khỏi hệ thống.

### 2.3. Quản lý Đơn hàng
- **Danh sách đơn hàng**: Trình bày toàn bộ các đơn hàng (từ COD đến VNPAY) với bộ lọc theo tên khách, trạng thái; phân trang; mã đơn 9 chữ số.
- **Chi tiết đơn hàng**: Xem chi tiết inline bằng AJAX (không cần tải trang mới) — danh sách sản phẩm, size, số lượng, giá.
- **Xử lý trạng thái (Order Flow)**: Chuyển đổi trạng thái đơn trực tiếp bằng dropdown trên bảng (PENDING → CONFIRMED → SHIPPING → DELIVERED → CANCELLED). Tự động hoàn tồn kho khi hủy đơn, tự động trừ lại khi khôi phục đơn đã hủy. Tự động cập nhật `payment_status` khi giao hàng thành công.
- **In hóa đơn**: Xuất hóa đơn (`inhoadon.php`) với bố cục sẵn sàng cho máy in.

### 2.4. Quản lý Khách hàng và Người dùng
- **Danh sách Khách hàng**: Theo dõi danh sách tài khoản, chi tiêu trung bình và số lượng đơn hàng của từng cá nhân.
- **Quản lý Quyền truy cập**: Tạm ngưng, cấm chức năng (Ban) hoặc kích hoạt lại tài khoản đối với người dùng vi phạm. Thiết lập vai trò Admin - Nhân viên.

### 2.5. Quản lý Tin tức (Blog) & Nội dung
- **Đăng/Chỉnh sửa bài viết**: Trình soạn thảo (WYSIWYG) hỗ trợ admin toàn quyền thao tác tạo bài đăng phong phú, ảnh nổi bật.
- **Quản lý Banner**: Cập nhật ảnh chính, banner trang chủ quảng bá sự kiện linh hoạt mỗi dịp lễ tết.

---

## 3. Đặc điểm kỹ thuật và nổi bật của hệ thống
- **Kiến trúc Triển khai Đám mây (Docker & Railway)**: Tích hợp sẵn `Dockerfile` chuẩn mực hỗ trợ môi trường biến (Environment Variables), sẵn sàng đưa hệ thống lên các nền tảng serverless đám mây như Railway với khả năng bind cổng linh hoạt.
- **Trải nghiệm thao tác mượt mà (AJAX & SweetAlert2)**: Ưu tiên không load lại website với những tác vụ nhẹ bằng AJAX. Cải thiện trải nghiệm người dùng với hệ thống Toast Notifications từ SweetAlert2 thay thế hoàn toàn cho các hộp thoại `alert` gián đoạn. Giỏ hàng AJAX auto-save real-time.
- **Giao diện Mobile-First Responsive**: Ứng dụng tư duy thiết kế Mobile-First, sử dụng CSS Grid/Flexbox và kĩ thuật clamp typography, đảm bảo bố cục hoàn hảo trên mọi kích thước màn hình smartphone, tablet và desktop.
- **Hệ thống gửi Email (Mailjet HTTP API v3.1)**: Tích hợp Mailjet API với kiến trúc phân luồng email (Transactional/Marketing/Admin). Retry tự động với Exponential Backoff cho lỗi 5xx/429, Email Blacklist check (Bounce/Spam), Priority headers cho email giao dịch, Unsubscribe headers cho email marketing. Backward compatible với hàm `sendMailBrevo()` cũ.
- **Quản lý Tồn kho Thông minh**: Hệ thống tồn kho chi tiết theo size (bảng `tbl_tonkho`), chống Race Condition bằng MySQL Transaction + `SELECT...FOR UPDATE`, tự động hoàn tồn kho khi hủy đơn, tự động chuyển đơn SHIPPING > 15 ngày sang DELIVERED.
- **Tích hợp thanh toán an toàn IPN (VNPAY)**: Cơ chế Instant Payment Notification đảm bảo việc đối soát và xác thực trạng thái online vô cùng chính xác, khắc phục bất cập khách đóng trình duyệt sau khi trả thẻ. Xác thực chữ ký SHA512 HMAC.
- **Bảo mật An Toàn (Security)**: Mật khẩu khách hàng mã hóa Bcrypt (`password_hash`/`password_verify`). Mật khẩu admin cũng Bcrypt với auto-migration từ plaintext. Chống XSS/SQL Injection thông qua `htmlspecialchars()` và Prepared Statements. Token bảo mật 256-bit cho reset password và tracking đơn hàng.
- **Webhook Mailjet**: Endpoint `webhooks/mailjet_webhook.php` tiếp nhận sự kiện Bounce/Spam từ Mailjet, tự động đưa email vào blacklist để bảo vệ uy tín gửi mail.


## Hệ thống Quản trị (Admin) - Hà :
- SRS_ADMIN_DASHBOARD.MD
- SRS_ADMIN_QL_DANH_MUC.MD
- SRS_ADMIN_QL_DON_HANG.MD
- SRS_ADMIN_QL_KHACH_HANG.MD
- SRS_ADMIN_QL_SAN_PHAM.MD
- SRS_ADMIN_QL_TIN_TUC.MD

## Giao diện Khách hàng & Tương tác mua hàng (User/Shop) - Kiên:
- SRS_TIM_KIEM_SAN_PHAM.MD
- SRS_CHI_TIET_SAN_PHAM.MD
- SRS_GIO_HANG.MD
- SRS_DAT_HANG_ONLINE.MD
- SRS_TRA_CUU_DON_HANG_CA_NHAN.MD
- SRS_THANH_TOAN.MD (Bao gồm tích hợp VNPAY/COD)

## Chức năng Hỗ trợ & Thông tin bổ sung - Nhân :
- SRS_TRANG_CHU.MD
- SRS_QUAN_LY_TAI_KHOAN.MD
- SRS_QUEN_MAT_KHAU.MD
- SRS_BLOG_TIN_TUC.MD
- SRS_LIEN_HE.MD