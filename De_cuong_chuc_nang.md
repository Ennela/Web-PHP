# ĐỀ CƯƠNG CHỨC NĂNG DỰ KIẾN - WEBSITE BÁN GIÀY THÔNG MINH

## 1. Các chức năng đối với Người Dùng (Khách hàng)

### 1.1. Quản lý tài khoản cá nhân
- **Đăng ký / Đăng nhập**: Khách hàng có thể tạo mới một tài khoản, đăng nhập vào hệ thống để mua hàng và xem trạng thái đơn hàng.
- **Đăng xuất**: Thoát khỏi tài khoản hiện tại.
- **Quản lý thông tin tài khoản**: Cập nhật thông tin cá nhân như tên, địa chỉ, số điện thoại, mật khẩu,...

### 1.2. Chức năng Trang Chủ & Khám phá sản phẩm
- **Xem banner quảng cáo & Carousel**: Hiển thị các sự kiện, khuyến mãi hiện hành.
- **Danh mục sản phẩm**: Hiển thị danh sách các phân loại giày thể thao, danh mục giày nam/giày nữ,...
- **Sản phẩm mới / Sản phẩm nổi bật**: Tự động gợi ý những đôi giày mới nhất, được mua nhiều nhất.

### 1.3. Chức năng Sản phẩm (Shop)
- **Xem danh sách sản phẩm**: Hiển thị lưới hình ảnh tất cả các mẫu giày.
- **Lọc và phân trang**: Cho phép người dùng xem sản phẩm theo trang nếu có số lượng lớn mặt hàng.
- **Xem chi tiết sản phẩm**: Hiển thị hình ảnh chi tiết, giá bán, mô tả, chất liệu, size giày, số lượng tồn.
- **Tìm kiếm sản phẩm**: Tìm kiếm nhanh chóng một đôi giày thông qua thanh công cụ tìm kiếm.

### 1.4. Chuyên trang Blog / Bài viết
- **Giới thiệu**: Cung cấp thông tin về thương hiệu, cửa hàng, chính sách.
- **Danh sách bài viết**: Cập nhật các kiến thức về giày dép, phối đồ, sự kiện.
- **Chi tiết bài viết**: Đọc chi tiết từng bài blog.

### 1.5. Chức năng Giỏ hàng và Đặt hàng
- **Thêm sản phẩm vào giỏ**: Tùy chỉnh size, số lượng, cập nhật giỏ hàng trực tiếp.
- **Xem và Cập nhật giỏ hàng**: Thay đổi số lượng, xóa các mặt hàng không muốn mua.
- **Thông tin đặt hàng**: Nhập thông tin người nhận hàng (họ tên, số điện thoại, địa chỉ cụ thể).

### 1.6. Chức năng Thanh Toán
- **Thanh toán khi nhận hàng (COD)**: Gửi thông tin đơn đặt hàng đến hệ thống chờ Admin xử lý.
- **Thanh toán trực tuyến**: Hỗ trợ thanh toán nhanh chóng, an toàn qua cổng thanh toán VNPAY (ATM, thẻ quốc tế, quét mã QR).

### 1.7. Hỗ trợ khách hàng
- **Liên hệ**: Gửi câu hỏi, khiếu nại hoặc nhận hỗ trợ từ cửa hàng thông qua biểu mẫu.

---

## 2. Các chức năng đối với Quản trị viên (Admin)

### 2.1. Tổng quan - Dashboard
- **Đăng nhập quản trị**: Cổng đăng nhập bảo mật riêng biệt.
- **Thống kê / Báo cáo biểu đồ**:
  - Xem tổng doanh thu đơn hàng.
  - Số lượng đơn thành công / chờ xử lý / đã hủy.
  - Số lượng người dùng, khách hàng.

### 2.2. Quản lý Sản phẩm và Danh mục
- **Quản lý Danh mục**: Thêm mới, sửa tên, xóa danh mục các loại giày.
- **Quản lý Sản phẩm**: 
  - Thêm giày mới (hình ảnh, tên, mô tả, giá cả, size, số lượng).
  - Cập nhật, sửa đổi giá/số lượng sản phẩm trên trang chủ.
  - Xóa sản phẩm khỏi gian hàng.
  - Quản lý thư viện ảnh: Upload và điều chỉnh các hình mô tả phụ cho sản phẩm.

### 2.3. Quản lý Đơn hàng
- **Danh sách đơn hàng**: Xem toàn bộ thông tin các đơn đặt mua (từ COD đến Online VNPay).
- **Chi tiết đơn hàng**: Theo dõi một đơn cụ thể bao gồm các đôi giày nào, size bao nhiêu, giá chi tiết.
- **Xử lý trạng thái**: Chuyển trạng thái đơn hàng (Mới đặt -> Đang giao hàng -> Hoàn thành / Hủy bỏ).

### 2.4. Quản lý Khách hàng và Người dùng
- **Danh sách Khách hàng**: Xem hồ sơ người dùng đăng ký trên hệ thống.
- **Bảo mật và khóa hạn chế**: Tắt hoặc khóa một số tài khoản có hành vi không chính đáng.

### 2.5. Quản lý Tin tức (Blog)
- **Đăng bài viết mới**: Thêm tin tức, thủ thuật về giày.
- **Chỉnh sửa / Xóa bài**: Quản lý nội dung bài viết hiển thị ở trang tin tức của cửa hàng.

---

## 3. Đặc điểm nổi bật khác của hệ thống (Tính thông minh)
- **Thanh toán bảo mật**: Tích hợp các giải pháp IPN của VNPAY đảm bảo khách hàng luôn đối soát được giao dịch ngay cả khi rớt mạng.
- **Phân trang và tìm kiếm động**: Giao diện phản hồi thân thiện với người dùng.
- **Gửi Email hoặc thông báo tự động (PHPMailer)**: Gửi thông báo khi đơn hàng được đặt hoặc khi nhận các form liên hệ của khách hàng.
