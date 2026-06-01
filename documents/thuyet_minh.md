# TÀI LIỆU THUYẾT MINH DỰ ÁN
## WEBSITE BÁN GIÀY THỂ THAO THÔNG MINH (SNKRS SHOP)

Tài liệu này được biên soạn nhằm phục vụ quá trình thuyết minh, giới thiệu và demo hệ thống website bán giày thể thao. Nội dung tập trung vào kiến trúc hệ thống, cấu trúc thư mục dự án, kịch bản demo chi tiết và các giải pháp kỹ thuật nổi bật được áp dụng.

---

## 1. Giới thiệu tổng quan hệ thống

Dự án là một hệ thống e-Commerce fullstack xây dựng trên nền tảng **PHP thuần (PHP 8.x)** và cơ sở dữ liệu **MariaDB (MySQL)**. Hệ thống được thiết kế hướng tới sự hoàn thiện về mặt trải nghiệm người dùng (giao diện Mobile-First, thông báo toast mượt mà) lẫn độ tin cậy về mặt nghiệp vụ backend (chống Race Condition, gửi email giao dịch, bảo mật mật khẩu).

Hệ thống được phân mảnh thành hai khu vực chính:
*   **Giao diện Khách hàng (Storefront):** Cung cấp luồng mua sắm hoàn chỉnh từ tìm kiếm, xem chi tiết (chọn size, zoom ảnh), giỏ hàng AJAX, checkout linh hoạt (COD & VNPAY) đến tra cứu đơn hàng không cần đăng nhập.
*   **Bảng quản trị (Admin Dashboard):** Giúp admin/nhân viên kiểm soát doanh thu trực quan bằng biểu đồ, quản lý tồn kho chi tiết theo từng size sản phẩm, xử lý luồng đơn hàng tự động và quản trị thành viên, tin tức.

---

## 2. Cấu trúc Project & Tổ chức mã nguồn

Mã nguồn được tổ chức sạch sẽ, phân tách rõ ràng giữa các module nghiệp vụ khách hàng, quản trị viên, tài nguyên tĩnh và các thư viện helper dùng chung:

```
WEB-PHP/
├── config.php                 # Định nghĩa BASE_URL và các hằng số cấu hình toàn cục
├── index.php                  # Điểm đón đầu tiên, tự động redirect sang trang chủ
├── sua.css                    # Tệp stylesheet global (navbar, footer, responsive overrides)
│
├── assets/                    # Quản lý tài nguyên tĩnh của ứng dụng
│   ├── bootstrap/             # Thư viện Bootstrap 5 (CSS & JS)
│   ├── css/                   # Các file CSS riêng (account.css, vanilla-zoom.css...)
│   ├── fonts/                 # Bộ phông chữ Montserrat và icon FontAwesome, Simple Line Icons
│   ├── img/                   # Lưu trữ hình ảnh tĩnh hệ thống và avatar mặc định
│   └── js/                    # Các kịch bản JS xử lý AJAX giỏ hàng, zoom ảnh, xử lý tab
│
├── includes/                  # Thư mục chứa các thành phần cốt lõi dùng chung
│   ├── connect.php            # Thiết lập kết nối cơ sở dữ liệu MySQLi
│   ├── header.php             # Giao diện header, nạp thẻ meta SEO và các file CSS
│   ├── footer.php             # Giao diện chân trang và import thư viện JS (SweetAlert2...)
│   ├── pagination.php         # Phân trang động cho danh sách sản phẩm
│   ├── inventory_helper.php   # Xử lý tồn kho: trừ kho khi mua, hoàn kho khi hủy đơn
│   ├── order_helper.php       # Tạo mã đơn hàng ngẫu nhiên 9 chữ số duy nhất
│   ├── mail_config.php        # Cấu hình API Key và các tham số gửi email Mailjet
│   ├── mail_helper.php        # Hàm xử lý gửi mail qua Mailjet HTTP API (hỗ trợ tương thích ngược)
│   └── email_templates.php    # Chứa cấu trúc HTML các mẫu thư giao dịch gửi cho khách hàng
│
├── home/                      # Các trang thông tin tĩnh và trang chủ
│   ├── trangchu.php           # Trang chủ: Carousel banner, Editorial, Sản phẩm bán chạy, Team
│   ├── lienhe.php             # Form liên hệ tích hợp Google Maps gửi email về Admin
│   └── vechungtoi.php         # Trang giới thiệu thông tin cửa hàng
│
├── auth/                      # Module xác thực và quản lý hồ sơ khách hàng
│   ├── dangnhap.php           # Đăng nhập khách hàng (mật khẩu bcrypt)
│   ├── dangki.php             # Đăng ký tài khoản mới (validate trùng lặp email/SĐT)
│   ├── quenmatkhau.php        # Giao diện yêu cầu khôi phục mật khẩu qua email
│   ├── datlamatkhau.php       # Đặt lại mật khẩu mới thông qua Token bảo mật gửi trong email
│   ├── thongtintaikhoan.php   # Quản trị thông tin cá nhân: Đổi mật khẩu, Sổ địa chỉ, Size giày
│   └── api_*.php              # Các API xử lý AJAX cập nhật thông tin cá nhân bất đồng bộ
│
├── shop/                      # Module cửa hàng và đặt hàng
│   ├── shop.php               # Danh sách sản phẩm tích hợp bộ lọc đa tiêu chí và phân trang
│   ├── chitietsanpham.php     # Chi tiết giày: Chọn size, xem ảnh album, phóng to ảnh
│   ├── giohang.php            # Giỏ hàng AJAX tự động cập nhật và kiểm tra số lượng tồn kho
│   ├── infodathang.php        # Form checkout điền thông tin đặt hàng COD (tích hợp Auto-fill)
│   ├── dathangonline.php      # Khởi tạo thanh toán trực tuyến qua cổng VNPAY
│   ├── vnpay_return.php       # Đón nhận kết quả thanh toán từ VNPAY và ghi nhận đơn hàng
│   ├── tradonhang.php         # Trang lịch sử mua hàng cá nhân
│   ├── chitietdonhang.php     # Timeline hành trình đơn hàng và thông tin hóa đơn chi tiết
│   └── tracking.php           # Tra cứu đơn hàng bằng Mã đơn + Token bảo mật từ Email
│
├── blog/                      # Chuyên trang tin tức
│   ├── baiviet.php            # Hiển thị danh sách tin tức
│   └── chitietbaiviet.php     # Nội dung chi tiết bài viết
│
├── admin/                     # Hệ thống quản trị viên (Admin Panel)
│   ├── canhbao.php            # Đăng nhập admin an toàn (bcrypt + prepared statements)
│   ├── dangnhap.php           # Đăng nhập admin (backup, tự động nâng cấp mật khẩu cũ)
│   ├── trangchu.php           # Dashboard thống kê doanh thu, số liệu đơn, biểu đồ tăng trưởng
│   ├── quanlidonhang.php      # Quản lý đơn hàng, cập nhật trạng thái đơn
│   ├── ajax_order_detail.php  # API lấy chi tiết đơn hàng hiển thị inline nhanh bằng AJAX
│   ├── quanlisanpham.php      # Quản lý danh sách sản phẩm và tồn kho từng size
│   ├── themsanpham.php        # Thêm sản phẩm, ảnh đại diện và album ảnh phụ
│   ├── edit_qlsanpham.php     # Cập nhật thông tin sản phẩm và điều chỉnh số lượng kho từng size
│   ├── quanlidanhmuc.php      # CRUD danh mục phân loại giày
│   ├── quanlikhachhang.php    # Quản lý tài khoản khách hàng, khóa/mở khóa tài khoản
│   ├── quanlibaidang.php      # Đăng tải và chỉnh sửa bài viết (WYSIWYG editor)
│   ├── quanlithanhvien.php    # Quản lý nhân viên/admin, phân quyền tài khoản
│   ├── inhoadon.php           # In hóa đơn đơn hàng (bố cục tối ưu hóa cho máy in)
│   └── connect_db.php         # Cấu hình kết nối cơ sở dữ liệu riêng của admin
│
├── vnpay_php/                 # Module tích hợp SDK VNPAY
├── webhooks/                  # Nơi tiếp nhận Webhooks từ các dịch vụ bên thứ ba
│   └── mailjet_webhook.php    # Tiếp nhận phản hồi email lỗi (bounce, spam) từ Mailjet
│
└── database/                  # Quản lý cơ sở dữ liệu SQL
    ├── giaythethao2.sql       # File dump CSDL ban đầu của dự án
    ├── railway_migration_v2.sql # File migration cập nhật CSDL phiên bản mới nhất
    └── check_and_fix_all.sql  # Script tự động rà soát, đồng bộ cột/bảng thiếu trên Cloud
```

---

## 3. Kịch bản Demo các chức năng chính

Để thuyết minh hệ thống một cách trơn tru, bạn có thể thực hiện theo kịch bản 4 bước dưới đây:

### Bước 1: Trải nghiệm Trang chủ & Khám phá sản phẩm
1.  **Duyệt trang chủ (`trangchu.php`):** Giới thiệu thanh trượt banner chính (Hero Carousel) chuyển động mượt mà, Editorial Section nổi bật giới thiệu giày tiêu biểu và khối USP dịch vụ (giao hàng, đổi trả).
2.  **Tìm kiếm & Lọc sản phẩm (`shop.php`):**
    *   Sử dụng thanh tìm kiếm (Search Bar) để tìm kiếm sản phẩm theo từ khóa.
    *   Sử dụng bộ lọc kết hợp: Lọc theo danh mục, khoảng giá, màu sắc hoặc size giày. Hệ thống sẽ tự lọc và phân trang tự động mà không bị gián đoạn.
3.  **Trang Chi tiết sản phẩm (`chitietsanpham.php`):**
    *   Trình diễn chức năng **Vanilla Zoom** (phóng to ảnh theo tọa độ di chuột trên ảnh sản phẩm).
    *   Chọn các size khác nhau để thấy số lượng tồn kho hiển thị thay đổi real-time. Nếu hết hàng, nút thêm vào giỏ sẽ bị vô hiệu hóa.

### Bước 2: Giỏ hàng AJAX & Đặt hàng COD
1.  **Thêm sản phẩm:** Tại trang chi tiết sản phẩm, chọn size và nhấn "Thêm vào giỏ hàng". SweetAlert2 sẽ hiển thị Toast thông báo thành công ở góc màn hình mà không cần reload trang.
2.  **Điều chỉnh Giỏ hàng (`giohang.php`):**
    *   Vào giỏ hàng, thay đổi số lượng của sản phẩm hoặc chọn một size khác trực tiếp từ dropdown trong bảng giỏ hàng.
    *   Hệ thống tự động tính toán lại tổng tiền qua AJAX và kiểm tra tồn kho tương ứng (cơ chế AJAX Auto-Save).
3.  **Đặt hàng COD (`infodathang.php`):**
    *   Bấm nút thanh toán, hệ thống sẽ chuyển đến form nhập thông tin giao hàng.
    *   Nếu đã đăng nhập, thông tin (Họ tên, SĐT, Email, Địa chỉ mặc định) sẽ được tự động điền (Auto-fill) từ sổ địa chỉ của khách hàng.
    *   Chọn thanh toán khi nhận hàng (COD) và hoàn tất. Hệ thống lập tức kích hoạt luồng trừ kho và gửi email hóa đơn xác nhận đơn hàng qua Mailjet.

### Bước 3: Đặt hàng trực tuyến VNPAY & Tra cứu đơn hàng
1.  **Đặt hàng trực tuyến (`dathangonline.php`):**
    *   Tạo một đơn hàng mới và chọn phương thức thanh toán "VNPAY".
    *   Hệ thống chuyển hướng người dùng sang trang sandbox của cổng thanh toán VNPAY.
    *   Nhập thông tin thẻ test của VNPAY Sandbox để tiến hành thanh toán.
2.  **Kết quả thanh toán (`vnpay_return.php`):**
    *   Sau khi thanh toán xong, VNPAY redirect về trang kết quả của hệ thống.
    *   Hệ thống đối soát chữ ký bảo mật SHA512, ghi nhận trạng thái thanh toán thành `PAID` và trạng thái đơn hàng `CONFIRMED`, trừ tồn kho, đồng thời gửi email thông báo thành công cho khách hàng.
3.  **Tra cứu đơn hàng (`tracking.php`):**
    *   Mở email xác nhận đơn hàng để lấy đường dẫn tracking dạng `tracking.php?order=XXX&token=YYY`.
    *   Nhấp vào link để xem thông tin đơn hàng và timeline vận chuyển trực quan mà không yêu cầu khách hàng phải đăng nhập hệ thống.

### Bước 4: Trang Quản trị viên (Admin Panel)
1.  **Đăng nhập Admin (`admin/canhbao.php`):** Trình diễn trang đăng nhập riêng biệt có xác thực Prepared Statements và mã hóa Bcrypt.
2.  **Dashboard thống kê (`admin/trangchu.php`):**
    *   Giới thiệu các biểu đồ trực quan hóa doanh số bán lẻ theo ngày, tuần, tháng.
    *   Hiển thị số liệu tổng hợp: Số đơn giao thành công, số đơn đang chờ xử lý, số đơn đã hủy, các sản phẩm sắp hết hàng cần nhập thêm.
3.  **Quản lý đơn hàng (`admin/quanlidonhang.php`):**
    *   Trình diễn xem nhanh chi tiết đơn hàng dạng inline (bằng AJAX ngay tại bảng danh sách, không cần tải lại trang).
    *   Thay đổi trạng thái đơn hàng (PENDING -> CONFIRMED -> SHIPPING -> DELIVERED).
    *   Chuyển trạng thái đơn hàng sang `CANCELLED` (Hủy đơn) -> chứng minh hệ thống tự động cộng trả lại tồn kho vào bảng `tbl_tonkho` thời gian thực.
    *   Bấm "In hóa đơn" để hiển thị trang `inhoadon.php` tối ưu riêng cho máy in khổ giấy A4/A5.

---

## 4. Điểm nổi bật & Tính năng kỹ thuật nâng cao

Để bài thuyết minh đạt điểm xuất sắc, hãy nhấn mạnh vào 5 giải pháp kỹ thuật cốt lõi dưới đây:

### 1. Giải pháp chống Race Condition trong Tồn kho (Concurrency Control)
*   **Vấn đề:** Khi có sự kiện khuyến mãi, nhiều khách hàng cùng bấm mua 1 sản phẩm cuối cùng tại một thời điểm. Nếu dùng lệnh `SELECT` và `UPDATE` thông thường, tồn kho sẽ bị âm hoặc đơn hàng bị ghi nhận sai lệch.
*   **Giải pháp:** Hệ thống sử dụng MySQL Transaction kết hợp với truy vấn khóa dòng `SELECT...FOR UPDATE` trong `includes/inventory_helper.php`. Khi một tiến trình đang kiểm tra và trừ kho của sản phẩm, các tiến trình khác muốn truy cập vào dòng dữ liệu đó sẽ phải xếp hàng chờ đợi, đảm bảo số lượng tồn kho luôn chính xác 100%.

### 2. Kiến trúc gửi Email giao dịch (Mailjet HTTP API)
*   **Vấn đề:** Việc gửi email qua giao thức SMTP truyền thống (như PHPMailer) thường làm treo ứng dụng (gây tải trang chậm vì phải đợi kết nối SMTP) và dễ bị các nhà cung cấp Cloud (như Railway) chặn cổng 25/465/587.
*   **Giải pháp:** 
    *   Tích hợp dịch vụ Mailjet bằng cách gọi trực tiếp HTTP REST API v3.1 qua cURL (non-blocking).
    *   Xây dựng thuật toán tự động thử lại (Retry with Exponential Backoff) khi API của Mailjet bị lỗi tạm thời (mã lỗi 5xx hoặc rate limit 429).
    *   Tối ưu cấu hình gửi thư bằng cách thêm các header ưu tiên gửi (Priority Header) và hỗ trợ tương thích ngược hoàn toàn với hàm gửi mail cũ của dự án.

### 3. Cơ chế Webhook & Email Blacklist bảo vệ uy tín tên miền
*   **Vấn đề:** Gửi email đến các địa chỉ không tồn tại (Hard Bounce) hoặc bị người dùng báo cáo spam sẽ làm giảm uy tín IP/Domain của shop, khiến email sau này gửi đi luôn rơi vào mục Spam.
*   **Giải pháp:**
    *   Xây dựng endpoint `webhooks/mailjet_webhook.php` tiếp nhận tín hiệu từ Mailjet khi xảy ra lỗi gửi thư.
    *   Khi Mailjet gửi sự kiện `bounce` hoặc `spam`, hệ thống tự động ghi nhận email này vào bảng `tbl_email_blacklist` trong cơ sở dữ liệu.
    *   Trước khi gửi bất kỳ email nào (xác nhận đơn, reset pass), hệ thống sẽ kiểm tra bảng blacklist này trước. Nếu email nằm trong danh sách đen, hệ thống sẽ bỏ qua để giữ uy tín gửi thư của tên miền.

### 4. Tự động nâng cấp mã hóa mật khẩu (Auto-hash Password Migration)
*   **Vấn đề:** Khi hệ thống nâng cấp từ lưu trữ mật khẩu dạng thô (plaintext) hoặc MD5 cũ lên chuẩn mã hóa bảo mật cao **Bcrypt**, làm thế nào để người dùng cũ vẫn đăng nhập được mà không phải bắt buộc họ đi reset mật khẩu?
*   **Giải pháp:** Tích hợp cơ chế tự động nâng cấp trong file đăng nhập (`admin/canhbao.php`, `auth/dangnhap.php`). Khi người dùng đăng nhập bằng mật khẩu đúng, hệ thống sẽ kiểm tra xem chuỗi hash trong DB có phải chuẩn bcrypt không (`password_needs_rehash`). Nếu không phải, hệ thống sẽ tự động băm mật khẩu đó bằng bcrypt và cập nhật lại vào DB ngay trong phiên đăng nhập đó.

### 5. AJAX Auto-Save Cart & UI Trực quan
*   **Mô tả:** Giỏ hàng loại bỏ hoàn toàn nút "Cập nhật giỏ hàng" truyền thống. Bất cứ hành động thay đổi số lượng, thay đổi size giày hay xóa sản phẩm đều kích hoạt một API AJAX ngầm gửi lên backend. Giao diện được cập nhật mượt mà kết hợp các thông báo SweetAlert2 dạng Toast không gây gián đoạn trải nghiệm mua sắm của khách hàng.
