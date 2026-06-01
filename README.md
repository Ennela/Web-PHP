# Website Bán Giày Thể Thao

Dự án môn học xây dựng website bán giày thể thao fullstack với PHP thuần, triển khai trên XAMPP và Railway. Hệ thống cung cấp đầy đủ chức năng cho **khách hàng** (duyệt sản phẩm, giỏ hàng, đặt hàng, thanh toán VNPAY/COD, tra cứu đơn hàng, quản lý tài khoản) và **quản trị viên** (quản lý sản phẩm, đơn hàng, khách hàng, bài viết, danh mục, thành viên).


---

## Thành viên

- **Lớp**: D18CNPM2

| STT | Họ và tên | Mã sinh viên |
|-----|-----------|--------------|
| 1 | Nguyễn Văn Kiên | 23810310138 |
| 2 | Đỗ Quang Hà | 23810310132 |
| 3 | Nguyễn Bá Nhân | 23810310144 |

---

## Phân công công việc

### Hệ thống Quản trị (Admin) — Hà
- Dashboard doanh thu, Quản lý Danh mục, Đơn hàng, Khách hàng, Sản phẩm, Tin tức, Thành viên

### Giao diện mua hàng (Shop) — Kiên
- Tìm kiếm sản phẩm, Chi tiết sản phẩm, Giỏ hàng, Đặt hàng Online, Tra cứu đơn hàng cá nhân, Thanh toán (VNPAY/COD), Tracking đơn hàng

### Chức năng hỗ trợ & Thông tin — Nhân
- Trang chủ (Banner Carousel), Quản lý tài khoản (Thông tin, Địa chỉ, Size giày, Đổi mật khẩu), Quên mật khẩu, Blog/Tin tức, Liên hệ, Về chúng tôi

---

## Mục lục

- [Công nghệ sử dụng](#công-nghệ-sử-dụng)
- [Cấu trúc thư mục](#cấu-trúc-thư-mục)
- [Cơ sở dữ liệu](#cơ-sở-dữ-liệu)
- [Chức năng nổi bật](#chức-năng-nổi-bật)
- [Hướng dẫn cài đặt](#hướng-dẫn-cài-đặt)
- [Tài liệu SRS](#tài-liệu-srs)

---

## Công nghệ sử dụng

| Thành phần | Công nghệ | Lý do lựa chọn |
|------------|-----------|----------------|
| **Ngôn ngữ** | PHP 8.x (thuần) | Dễ triển khai, cú pháp trực quan, giúp sinh viên nắm vững kiến thức cốt lõi về backend, session và luồng xử lý web động trước khi học Framework. |
| **Database** | MariaDB 10.4 | Hệ quản trị cơ sở dữ liệu quan hệ mạnh mẽ, mã nguồn mở, tích hợp sẵn trong XAMPP. |
| **Web Server** | Apache (XAMPP) | Môi trường phát triển cục bộ trọn gói, thao tác khởi động dịch vụ web thuận tiện cho Windows. |
| **Frontend** | HTML5, Vanilla JS, CSS/Bootstrap | Áp dụng thiết kế Mobile-First hiện đại trên các trang shop/cart/thanh toán, kết hợp tùy biến linh hoạt hiệu ứng micro-animations. |
| **Font & Icon** | Montserrat, FontAwesome 5.3.1 | Phông chữ hiện đại cùng bộ icon đầy đủ giúp giao diện trở nên chuyên nghiệp, thân thiện. |
| **Thanh toán** | VNPAY Sandbox | API thanh toán uy tín và sát thực tế, giúp làm quen với luồng thanh toán bảo mật, xác thực giao dịch chuẩn e-Commerce (HMAC-SHA512). |
| **Email** | Mailjet HTTP API v3.1 | Gửi email giao dịch (xác nhận đơn hàng, đặt lại mật khẩu, liên hệ) qua REST API. Free tier 200 email/ngày, gửi được đến mọi địa chỉ email mà không cần verify domain. |
| **Thông báo UI** | SweetAlert2 | Hiển thị thông báo (Toast Notifications) không chặn màn hình, thay thế hoàn toàn `alert()` mặc định của trình duyệt. |
| **Bảo mật** | bcrypt (password_hash/password_verify) | Mã hóa mật khẩu người dùng và admin bằng bcrypt, chống SQL injection bằng prepared statements. |
| **Triển khai** | Docker & Railway | Đóng gói ứng dụng linh hoạt, tự động bind cổng và biến môi trường, tối ưu hóa quá trình CI/CD. |

---

## Cấu trúc thư mục

```
WEB-PHP/
├── config.php                 # Cấu hình BASE_URL, BASE_PATH
├── index.php                  # Redirect về trang chủ
├── sua.css                    # CSS global (navbar, footer, form overrides)
│
├── assets/                    # Thư mục lưu trữ tài nguyên tĩnh (CSS, JS, Fonts, Images)
│   ├── bootstrap/             # Thư viện Bootstrap 5 (CSS/JS)
│   ├── css/                   # File CSS riêng (account.css, vanilla-zoom.css...)
│   ├── fonts/                 # Phông chữ & Icon (FontAwesome, Simple Line Icons)
│   ├── img/                   # Ảnh tĩnh của giao diện, avatar mặc định
│   └── js/                    # File Javascript xử lý AJAX, zoom ảnh, tabs
│
├── includes/                  # Thành phần dùng chung (Shared Components & Helpers)
│   ├── connect.php            # Kết nối MySQLi đến DB giaythethao2
│   ├── header.php             # Header chung (navbar, meta, CSS imports)
│   ├── footer.php             # Footer chung
│   ├── pagination.php         # Helper phân trang
│   ├── inventory_helper.php   # Quản lý tồn kho: validateAndDeductStock(), restoreStock()
│   ├── order_helper.php       # Sinh mã đơn hàng: generateOrderCode()
│   ├── mail_helper.php        # Gửi email qua Mailjet API: sendMailBrevo() (tương thích ngược)
│   ├── mail_config.php        # Cấu hình API key, sender email cho Mailjet
│   └── email_templates.php    # Các template email giao dịch (xác nhận đơn, reset mật khẩu)
│
├── home/                      # Các trang tĩnh & giới thiệu (Khách hàng)
│   ├── trangchu.php           # Trang chủ: Hero Carousel, Editorial, Bestsellers, Team
│   ├── lienhe.php             # Trang liên hệ (gửi email hỗ trợ qua Mailjet + Google Maps)
│   └── vechungtoi.php         # Trang giới thiệu về shop
│
├── auth/                      # Module xác thực & quản lý tài khoản người dùng
│   ├── dangnhap.php           # Đăng nhập khách hàng
│   ├── dangki.php             # Đăng ký tài khoản
│   ├── quenmatkhau.php        # Giao diện yêu cầu đặt lại mật khẩu
│   ├── datlamatkhau.php       # Giao diện đặt lại mật khẩu với token
│   ├── thongtintaikhoan.php   # Trang cá nhân: thông tin, đổi MK, sổ địa chỉ, size giày
│   ├── api_capnhat_thongtin.php  # API cập nhật thông tin cá nhân (AJAX)
│   ├── api_doimatkhau.php     # API đổi mật khẩu (AJAX, check độ mạnh)
│   ├── api_quenmatkhau.php    # API xử lý quên mật khẩu (gửi token qua mail)
│   ├── api_datlamatkhau.php   # API đặt lại mật khẩu mới
│   ├── api_diachi.php         # API CRUD sổ địa chỉ (tối đa 5 địa chỉ)
│   ├── api_sizegiay.php       # API lưu size giày yêu thích
│   └── api_upload_avatar.php  # API upload ảnh đại diện (AJAX)
│
├── shop/                      # Module mua sắm & đặt hàng
│   ├── shop.php               # Cửa hàng: lọc theo danh mục, size, màu, giá, phân trang
│   ├── chitietsanpham.php     # Chi tiết sản phẩm: chọn size, tồn kho real-time, zoom ảnh
│   ├── giohang.php            # Giỏ hàng AJAX: thay đổi size/số lượng, auto-save, kiểm tra tồn kho
│   ├── infodathang.php        # Thanh toán & đặt hàng COD (auto-fill thông tin)
│   ├── dathangonline.php      # Thanh toán & đặt hàng VNPAY (tạo cổng thanh toán)
│   ├── vnpay_return.php       # Xử lý kết quả trả về từ VNPAY, cập nhật trạng thái đơn
│   ├── tradonhang.php         # Danh sách đơn hàng cá nhân của khách
│   ├── chitietdonhang.php     # Chi tiết & timeline trạng thái của đơn hàng
│   ├── tracking.php           # Tra cứu đơn hàng bằng mã đơn + token (không cần login)
│   ├── cart_update_ajax.php   # API cập nhật giỏ hàng qua AJAX
│   ├── api_stock.php          # API kiểm tra số lượng tồn kho của size sản phẩm
│   └── api_order_action.php   # API hủy đơn hàng (trạng thái PENDING)
│
├── blog/                      # Module tin tức
│   ├── baiviet.php            # Danh sách bài viết tin tức
│   └── chitietbaiviet.php     # Chi tiết bài viết tin tức
│
├── admin/                     # Hệ thống quản trị (Admin Panel)
│   ├── canhbao.php            # Đăng nhập admin với prepared statements + bcrypt (ưu tiên)
│   ├── dangnhap.php           # Đăng nhập admin (backup, tự động hash mật khẩu cũ)
│   ├── trangchu.php           # Dashboard thống kê doanh thu, top sản phẩm, trạng thái đơn
│   ├── quanlidonhang.php      # Quản lý đơn hàng: đổi trạng thái, lọc, in hóa đơn
│   ├── ajax_order_detail.php  # API lấy nhanh chi tiết đơn hàng (inline AJAX)
│   ├── quanlisanpham.php      # Quản lý danh sách sản phẩm
│   ├── themsanpham.php        # Thêm sản phẩm mới (có thư viện ảnh phụ)
│   ├── edit_qlsanpham.php     # Chỉnh sửa sản phẩm & cập nhật tồn kho từng size
│   ├── delete_qlsanpham.php   # Xóa sản phẩm khỏi hệ thống
│   ├── quanlidanhmuc.php      # Quản lý danh mục sản phẩm
│   ├── themdanhmuc.php        # Thêm danh mục sản phẩm
│   ├── quanlikhachhang.php    # Quản lý tài khoản khách hàng, ban/unban người dùng
│   ├── themkhachhang.php      # Thêm tài khoản khách hàng mới
│   ├── delete_qlkhachhang.php # Xóa tài khoản khách hàng
│   ├── quanlibaidang.php      # Quản lý bài viết tin tức
│   ├── thembaidang.php        # Đăng bài viết mới (WYSIWYG editor)
│   ├── edit_qlbaidang.php     # Chỉnh sửa bài viết
│   ├── delete_qlbaidang.php   # Xóa bài viết
│   ├── quanlithanhvien.php    # Quản lý thành viên quản trị (Admin/Nhân viên)
│   ├── themthanhvien.php      # Tạo thành viên admin mới (mật khẩu bcrypt)
│   ├── edit_qlthanhvien.php   # Chỉnh sửa thông tin thành viên admin
│   ├── delete_qlthanhvien.php # Xóa thành viên admin
│   ├── function.php           # Thư viện hàm hỗ trợ admin
│   ├── gallery_delete.php     # API xóa ảnh trong thư viện ảnh sản phẩm
│   ├── inhoadon.php           # Xuất hóa đơn in ấn cho đơn hàng
│   ├── pagination.php         # Helper phân trang của trang admin
│   └── connect_db.php         # Kết nối DB riêng cho admin
│
├── vnpay_php/                 # Tích hợp cổng thanh toán VNPAY
│   └── config.php             # Cấu hình tham số kết nối VNPAY Sandbox
│
├── webhooks/                  # Webhook xử lý sự kiện từ bên thứ ba
│   └── mailjet_webhook.php    # Endpoint tiếp nhận sự kiện bounce/spam từ Mailjet
│
├── database/                  # Cơ sở dữ liệu SQL
│   ├── giaythethao2.sql       # Dump toàn bộ database ban đầu
│   ├── railway_migration.sql  # Migration database v1
│   ├── railway_migration_v2.sql # Migration database v2
│   ├── migration_tonkho.sql   # Migration thêm bảng tồn kho theo size
│   ├── migration_quenmatkhau.sql # Migration thêm token reset mật khẩu
│   ├── migration_email_system.sql # Migration bảng email blacklist & log webhook
│   └── check_and_fix_all.sql  # Script kiểm tra và tự động sửa lỗi cấu trúc CSDL
│
├── documents/                 # Tài liệu đặc tả SRS, kiến trúc & báo cáo
│   ├── De_cuong_chuc_nang.md  # Đề cương chức năng hệ thống
│   ├── MAILJET_DNS_SETUP.md   # Hướng dẫn cấu hình DNS cho email
│   ├── RECORD_2026_04_03.md   # Báo cáo tiến độ phân tích và code
│   └── ... (các tài liệu đặc tả SRS chi tiết khác)
│
├── Dockerfile                 # File Docker cấu hình container cho Railway
└── docker-entrypoint.sh       # Script khởi động tự động trên môi trường cloud
```

---

## Cơ sở dữ liệu

**Database**: `giaythethao2` — **Engine**: InnoDB — **Charset**: UTF-8

### Các bảng chính

| Bảng dữ liệu | Chức năng |
|---------------|-----------|
| `tbl_tkkhachhang` | Tài khoản khách hàng (username, password bcrypt, email, họ tên, SĐT, địa chỉ, avatar, trạng thái nhận tin, token reset mật khẩu). |
| `tbl_qlsanpham` | Thông tin sản phẩm: tên, giá, ảnh, mô tả, nhóm SP. |
| `tbl_tonkho` | Tồn kho theo từng size của sản phẩm (masp + size + số lượng). |
| `oder` | Đơn hàng: mã đơn (order_code), thông tin KH, tổng tiền, trạng thái (PENDING/CONFIRMED/SHIPPING/DELIVERED/CANCELLED), payment_status, token bảo mật, liên kết makh. |
| `oder_chitiet` | Chi tiết đơn hàng: sản phẩm, số lượng, size, giá. Liên kết N-1 với `oder`. |
| `tbl_qlthanhvien` | Tài khoản admin/nhân viên (tài khoản, mật khẩu bcrypt, họ tên, chức vụ, địa chỉ, ngày sinh, giới tính). |
| `tbl_qlbaidang` | Bài viết / tin tức. |
| `tbl_danhmuc` | Danh mục sản phẩm. |
| `tbl_diachi` | Sổ địa chỉ của khách hàng (nhiều địa chỉ, tối đa 5 địa chỉ, chọn mặc định). |
| `tbl_sizegiay` | Size giày yêu thích của khách hàng (he_size EU/US/CM, size_value, ghichu). |
| `tbl_thuvienanh` | Thư viện ảnh bổ sung của sản phẩm. |
| `tbl_email_blacklist` | Danh sách email bị chặn (bounce, spam_complaint, blocked) để ngừng gửi mail tự động. |
| `tbl_email_logs` | Nhật ký chi tiết gửi email (to_email, subject, status, error, sent_at). |
| `tbl_email_webhook_log` | Nhật ký nhận các sự kiện phản hồi từ Webhook của Mailjet (event_type, message_id, error_info, event_time). |

---

## Chức năng nổi bật

### Phía Khách hàng

- **Giao diện Mobile-First & Responsive:** Toàn bộ trang shop, giỏ hàng, thanh toán được tối ưu hóa hiển thị trên mọi thiết bị với CSS Grid/Flexbox và kĩ thuật clamp typography.
- **Trang chủ sinh động:** Hero Carousel (Bootstrap 5) slide hình ảnh động kèm caption mượt mà, Editorial Section với hiệu ứng hover float, Features Section giới thiệu USP, Bestseller Section hiển thị sản phẩm bán chạy trích xuất từ DB, và Team Section giới thiệu đội ngũ.
- **Đăng ký / Đăng nhập:** Tài khoản khách hàng hỗ trợ đăng ký bằng email/SĐT, mật khẩu mã hóa bcrypt. Đăng nhập để đồng bộ giỏ hàng đa thiết bị, lưu profile.
- **Quên mật khẩu:** Gửi link đặt lại mật khẩu với token bảo mật 256-bit qua Mailjet API, token có thời hạn 30 phút.
- **Quản lý tài khoản:** Trang cá nhân gồm 5 tab chức năng:
  - *Thông tin cá nhân:* Sửa profile, upload avatar (JPG/PNG/WebP, max 2MB) qua AJAX.
  - *Đổi mật khẩu:* Nhập mật khẩu cũ, mật khẩu mới (có Password Strength Indicator 4 mức), mã hóa bcrypt.
  - *Sổ địa chỉ:* Quản lý tối đa 5 địa chỉ giao hàng, tích hợp API tỉnh/huyện/xã Việt Nam, thiết lập địa chỉ mặc định.
  - *Size giày yêu thích:* Lưu size theo hệ EU/US/CM và ghi chú sở thích (chân rộng, dẹt...), tự động gợi ý size khi mua hàng.
  - *Đơn hàng của tôi:* Xem lịch sử 20 đơn hàng gần nhất với timeline trạng thái và xem chi tiết.
- **Chi tiết sản phẩm:** Gallery nhiều ảnh, zoom ảnh theo tọa độ chuột (Vanilla Zoom), lightbox, hiển thị tồn kho real-time theo size, tự động áp dụng size giày yêu thích.
- **Giỏ hàng AJAX thông minh:** AJAX auto-save (thay đổi size/số lượng tự động cập nhật không tải lại trang), chọn size trực tiếp trong giỏ hàng, cảnh báo hết hàng/vượt tồn kho, animation xóa sản phẩm mượt mà, debounce tránh spam.
- **Tự động điền thông tin (Auto-fill Checkout):** Tự động điền trước thông tin thanh toán (Tên, SĐT, Địa chỉ từ sổ mặc định, Email) của khách hàng đã đăng nhập.
- **Thanh toán COD & VNPAY:** 2 hình thức thanh toán. VNPAY sử dụng HMAC-SHA512 để bảo mật chữ ký, xử lý duplicate tab, chống race condition bằng MySQL Transaction.
- **Email xác nhận tự động:** Gửi email xác nhận đơn hàng tự động cho khách hàng qua Mailjet API cho cả COD và VNPAY.
- **Tra cứu đơn hàng linh hoạt:** Xem lịch sử mua hàng cá nhân sau khi đăng nhập, hoặc tracking bằng mã đơn + token (không cần login) qua link gửi trong email xác nhận.
- **SweetAlert2 & Toast:** Hiển thị thông báo Toast đẹp mắt, không chặn màn hình.

### Phía Quản trị viên (Admin Panel)

- **Đăng nhập bảo mật:** Tách biệt hoàn toàn, sử dụng prepared statements chống SQL injection, mật khẩu mã hóa bcrypt. Hỗ trợ auto-hash migration (tự động cập nhật mật khẩu plaintext cũ sang bcrypt khi đăng nhập lần đầu).
- **Phân quyền Admin:** Chỉ admin đã đăng nhập mới có quyền quản trị và tạo tài khoản admin mới.
- **Dashboard tổng quan:** Trực quan hóa doanh thu bằng biểu đồ theo ngày/tuần/tháng; số liệu trạng thái đơn (thành công - DELIVERED, chờ, hủy); thống kê top sản phẩm chạy nhất, sản phẩm sắp hết hàng, tăng trưởng user.
- **Quản lý đơn hàng:** Lọc đơn hàng theo tên, trạng thái; xem chi tiết nhanh bằng inline AJAX không tải lại trang; in hóa đơn chuẩn in ấn.
- **Xử lý trạng thái (Order Flow):** Chuyển đổi trạng thái đơn hàng (PENDING -> CONFIRMED -> SHIPPING -> DELIVERED -> CANCELLED). Tự động trừ/hoàn tồn kho trong CSDL khi thay đổi trạng thái, tự động cập nhật `payment_status` khi giao thành công.
- **Quản lý sản phẩm & tồn kho:** CRUD sản phẩm, album ảnh phụ, nội dung mô tả bằng Rich Text Editor; quản lý tồn kho chi tiết theo từng size sản phẩm.
- **Quản lý danh mục:** CRUD phân loại sản phẩm.
- **Quản lý khách hàng:** Theo dõi danh sách tài khoản, chi tiêu trung bình, số đơn hàng đã đặt; ban/unban người dùng vi phạm.
- **Quản lý tin tức:** CRUD bài viết blog bằng WYSIWYG editor.
- **Quản lý thành viên:** CRUD tài khoản admin/nhân viên với mật khẩu bcrypt.

---

## Hướng dẫn cài đặt

### Môi trường Local (XAMPP)

1. **Clone mã nguồn** vào `C:\xampp\htdocs\WEB-PHP`.
2. **Khởi động XAMPP** (Apache + MySQL).
3. **Database:** Truy cập phpMyAdmin, import file `database/railway_migration_v2.sql` để khởi tạo CSDL.
4. Cấu hình kết nối DB trong `includes/connect.php` và `admin/connect_db.php` (mặc định: localhost, root, không mật khẩu).
5. **Truy cập:**
   - Trang khách hàng: `http://localhost/WEB-PHP/`
   - Trang quản trị: `http://localhost/WEB-PHP/admin/`
   - Tài khoản admin mẫu: `noah2005` / `kudo-kun`

### Triển khai lên Railway (Cloud)

Dự án đã được cấu hình sẵn `Dockerfile` + `docker-entrypoint.sh` để triển khai tự động:

1. Khởi tạo dự án trên Railway.
2. Thêm dịch vụ MySQL, thực thi file `database/railway_migration_v2.sql`.
3. Liên kết kho GitHub chứa dự án vào dịch vụ Web trên Railway.
4. Cấu hình các biến môi trường (Environment Variables):
   - `MYSQLHOST`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`, `MYSQLPORT` (tự động khi liên kết MySQL service)
   - `MAILJET_API_KEY` — API Key (Public) từ [Mailjet](https://www.mailjet.com) (miễn phí 200 email/ngày)
   - `MAILJET_SECRET_KEY` — Secret Key (Private) từ Mailjet
   - `VNPAY_TMN_CODE`, `VNPAY_HASH_SECRET` (nếu dùng VNPAY production)
5. Railway sẽ tự động build từ `Dockerfile` và cấp phát tên miền.
6. **CI/CD:** Mỗi lần `git push`, Railway tự động build & deploy.

---

## Kiến trúc Email

| Thành phần | Chi tiết |
|-----------|---------|
| **Service** | Mailjet — HTTP REST API v3.1 |
| **Free tier** | 200 email/ngày, gửi đến mọi email |
| **Helper** | `includes/mail_helper.php` — hàm `sendMailBrevo()` |
| **Nơi sử dụng** | Xác nhận đơn hàng (COD + VNPAY), Quên mật khẩu, Liên hệ |
| **Env variables** | `MAILJET_API_KEY`, `MAILJET_SECRET_KEY` |
| **Auth** | HTTP Basic Auth (API Key : Secret Key) |
| **Timeout** | 10 giây (tránh treo trang) |

---

## Tài liệu SRS

Tất cả tài liệu đặc tả yêu cầu phần mềm (SRS) được lưu trong thư mục `documents/`:

| File | Mô tả |
|------|-------|
| [De_cuong_chuc_nang.md](./documents/De_cuong_chuc_nang.md) | Đề cương chức năng kiến trúc |
| [MAILJET_DNS_SETUP.md](./documents/MAILJET_DNS_SETUP.md) | Hướng dẫn cấu hình DNS (SPF, DKIM, DMARC) cho Mailjet |
| [RECORD_2026_04_03.md](./documents/RECORD_2026_04_03.md) | Nhật ký báo cáo tiến độ phân tích & lập trình hàng ngày |
| [SRS_ADMIN_DASHBOARD.MD](./documents/SRS_ADMIN_DASHBOARD.MD) | Đặc tả Dashboard & Báo cáo doanh thu |
| [SRS_ADMIN_QL_SAN_PHAM.MD](./documents/SRS_ADMIN_QL_SAN_PHAM.MD) | Đặc tả Quản lý Sản phẩm / Size |
| [SRS_ADMIN_QL_DON_HANG.MD](./documents/SRS_ADMIN_QL_DON_HANG.MD) | Đặc tả Quản lý Đơn hàng (Admin) |
| [SRS_ADMIN_QL_KHACH_HANG.MD](./documents/SRS_ADMIN_QL_KHACH_HANG.MD) | Đặc tả Quản lý Khách hàng |
| [SRS_ADMIN_QL_DANH_MUC.MD](./documents/SRS_ADMIN_QL_DANH_MUC.MD) | Đặc tả Quản lý Danh mục |
| [SRS_ADMIN_QL_TIN_TUC.MD](./documents/SRS_ADMIN_QL_TIN_TUC.MD) | Đặc tả Quản lý Tin tức |
| [SRS_CHI_TIET_SAN_PHAM.MD](./documents/SRS_CHI_TIET_SAN_PHAM.MD) | Đặc tả Chi tiết sản phẩm, Chọn size & Zoom |
| [SRS_GIO_HANG.MD](./documents/SRS_GIO_HANG.MD) | Đặc tả Giỏ hàng AJAX |
| [SRS_DAT_HANG.MD](./documents/SRS_DAT_HANG.MD) | Đặc tả Đặt hàng (COD) |
| [SRS_THANH_TOAN.MD](./documents/SRS_THANH_TOAN.MD) | Đặc tả Thanh toán COD + VNPAY |
| [SRS_TIM_KIEM_SAN_PHAM.MD](./documents/SRS_TIM_KIEM_SAN_PHAM.MD) | Đặc tả Tìm kiếm & Lọc sản phẩm |
| [SRS_LIEN_HE.MD](./documents/SRS_LIEN_HE.MD) | Đặc tả Trang liên hệ |
| [SRS_DANG_KY.MD](./documents/SRS_DANG_KY.MD) | Đặc tả Đăng ký tài khoản |
| [SRS_DANG_NHAP.MD](./documents/SRS_DANG_NHAP.MD) | Đặc tả Đăng nhập |
| [SRS_DANG_XUAT.MD](./documents/SRS_DANG_XUAT.MD) | Đặc tả Đăng xuất |
| [SRS_QUEN_MAT_KHAU.MD](./documents/SRS_QUEN_MAT_KHAU.MD) | Đặc tả Quên mật khẩu |
| [SRS_QUAN_LY_TAI_KHOAN.MD](./documents/SRS_QUAN_LY_TAI_KHOAN.MD) | Đặc tả Quản lý tài khoản |
| [SRS_TRA_CUU_DON_HANG.MD](./documents/SRS_TRA_CUU_DON_HANG.MD) | Đặc tả Tra cứu đơn hàng |
| [SRS_TRANG_CHU.MD](./documents/SRS_TRANG_CHU.MD) | Đặc tả Trang chủ |
| [SRS_BLOG_TIN_TUC.MD](./documents/SRS_BLOG_TIN_TUC.MD) | Đặc tả Blog tin tức |
| [SRS_USECASE_DIAGRAMS.MD](./documents/SRS_USECASE_DIAGRAMS.MD) | Sơ đồ Use Case tổng hợp (Mermaid) |

---
> **Deploy:** [https://kienhanhan.up.railway.app](https://kienhanhan.up.railway.app)
>
> **Video demo:** [Google Drive](https://drive.google.com/file/d/1UZFdJLBt1ztZObS_CWFB_YAro2ERURke/view)

*Website bán giày thể thao được xây dựng theo tiêu chuẩn Web Responsive, Mobile-First, với hệ thống bảo mật bcrypt, chống Race Condition và SQL Injection.*
