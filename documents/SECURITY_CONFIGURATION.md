# Security Misconfiguration

Thay đổi này áp dụng cấu hình an toàn cho PHP, web server, môi trường triển khai, kết nối database và upload, theo nhóm rủi ro [OWASP Security Misconfiguration](https://top10.owasp.org/2025/A02_2025-Security_Misconfiguration/). Cơ chế CSRF hiện có tiếp tục hoạt động.

## Cấu hình đã áp dụng

- `includes/security.php` được nạp trước session, HTML và database: ẩn lỗi mặc định, trả thông báo HTML/JSON chung, loại output đang render khi có exception. Log server chỉ ghi loại exception, tên file/dòng hoặc mã driver DB, không ghi mật khẩu, SQL hay request body trong các handler mới.
- `.htaccess`, `deploy/apache-security.conf` và `Caddyfile` chặn file môi trường, dotfile, Git, dump SQL kể cả file nén, log/backup/khóa riêng, tài liệu, công cụ kiểm thử và PHP chỉ dùng để include. Tắt directory listing và chặn phương thức TRACE/TRACK/CONNECT; PHP chỉ xử lý GET/HEAD/POST/OPTIONS.
- Header HTTP gồm `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, CSP và `Cache-Control: no-store` trên trang động. Phiên bản PHP được ẩn; Caddy loại Server header, Apache giới hạn còn tên `Apache` và tắt chữ ký/phiên bản chi tiết.
- CSP giới hạn domain CDN/font/API/Google Maps/VNPAY đang dùng, cấm plugin/object, giới hạn frame và base URL. Script tin cậy dùng nonce theo từng phản hồi; HTML event handlers đã chuyển sang `addEventListener`, `script-src-attr 'none'`. CSS inline vẫn được giữ để tương thích giao diện. Xem thêm `XSS_SECURITY.md`.
- Host được kiểm tra allowlist trước khi tạo URL. Không dùng `X-Forwarded-Host`. Chỉ tin `X-Forwarded-Proto` nếu IP kết nối khớp `TRUSTED_PROXIES`.
- Production/staging yêu cầu `APP_URL` HTTPS. HTTP được chuyển tới origin đã cấu hình, không lấy host tùy ý từ client. Cookie bật Secure khi HTTPS được xác minh; production HTTPS có HSTS một năm, không tự bật `includeSubDomains`/preload.
- Production không hiển thị lỗi kể cả `APP_DEBUG=1`. Kết nối MySQL yêu cầu đủ biến môi trường, cấm user `root`, timeout 5 giây và trả 503 chung khi thất bại.
- Upload chỉ nhận JPG/JPEG/PNG/WebP/GIF, kiểm tra extension, MIME thực, kích thước và cấu trúc ảnh; tên file ngẫu nhiên, avatar tối đa 2MB và ảnh quản trị tối đa 5MB. Ảnh phụ lỗi chặn toàn bộ lần gửi. Web server chặn PHP/script/HTML/SVG trong thư mục upload, kể cả file đã tồn tại từ trước và tên kép/PATH_INFO.
- Docker dùng PHP 8.4 Apache, tắt module autoindex/status, giới hạn request body, giấu lỗi từ lúc PHP khởi động. Mã nguồn thuộc `root`; chỉ thư mục upload cho `www-data` ghi. Secrets, database dump và công cụ phát triển bị loại khỏi build context. Cần rebuild định kỳ để nhận bản vá của nhánh PHP đang được [hỗ trợ](https://www.php.net/supported-versions.php).

## Chạy local

Tạo `.env` từ `.env.example`, đặt thông tin MySQL thực tế. File này được Git bỏ qua và chỉ được đọc ở local/test; biến môi trường của tiến trình luôn được ưu tiên, kể cả khi giá trị rỗng.

```dotenv
APP_ENV=local
APP_DEBUG=0
# Để trống APP_URL nếu muốn tự nhận URL localhost hiện tại.
APP_URL=
APP_TRUSTED_HOSTS=
TRUSTED_PROXIES=
MYSQLHOST=127.0.0.1
MYSQLPORT=3306
MYSQLUSER=root
MYSQLPASSWORD=your_local_password
MYSQLDATABASE=giaythethao2
```

Chạy bằng cấu hình Caddy đã bảo vệ static file, thay cho lệnh `php-server` không đọc `.htaccess`:

```sh
scratch/runtime/frankenphp run --config Caddyfile
```

Nếu đã cài FrankenPHP vào PATH, dùng `frankenphp run --config Caddyfile`. Mặc định chỉ bind `127.0.0.1:8000`, HTTP. Có thể đổi `SITE_ADDRESS`, `SITE_BIND`, `APP_ROOT` qua môi trường. Khi dùng XAMPP/Apache, bật `mod_rewrite`, `mod_headers` và cho phép `.htaccess` theo cấu hình trong `deploy/apache-security.conf`.

## Triển khai production

Docker mặc định `APP_ENV=production`, `APP_DEBUG=0`. Đặt các giá trị sau trong nền tảng hosting, không đặt secrets vào Dockerfile hoặc Git:

```dotenv
APP_ENV=production
APP_DEBUG=0
APP_URL=https://shop.example.com/
APP_TRUSTED_HOSTS=shop.example.com
TRUSTED_PROXIES=
MYSQLHOST=your_database_host
MYSQLPORT=3306
MYSQLUSER=shop_app
MYSQLPASSWORD=your_unique_database_password
MYSQLDATABASE=giaythethao2
```

`APP_TRUSTED_HOSTS` nhận hostname không có port, cách nhau bằng dấu phẩy. Host từ `APP_URL` được thêm vào allowlist tự động; local còn cho phép localhost, 127.0.0.1 và IPv6 loopback. Production không tự cho phép localhost.

Nếu HTTPS kết thúc ở reverse proxy, đặt `TRUSTED_PROXIES` bằng IP/CIDR thực tế của proxy, cách nhau bằng dấu phẩy. Ví dụ proxy cùng máy có thể là `127.0.0.1/32,::1/128`. Tránh `0.0.0.0/0` hoặc `::/0`; việc tin toàn bộ client sẽ làm mất ý nghĩa kiểm tra header proxy. Caddyfile trong repo phục vụ HTTP local; production phải có TLS trực tiếp hoặc tại proxy được tin cậy.

Tạo DB user riêng, chỉ cấp quyền cần thiết trên `giaythethao2`; tài khoản runtime không cần quyền quản trị toàn MySQL. Thực hiện migration bằng tài khoản triển khai riêng. Kiểm tra/đổi tài khoản quản trị mẫu trong database trước khi đưa dịch vụ ra Internet.

## Email, VNPAY và webhook

`.env.example` không còn chứa khóa Mailjet thật. VNPAY không còn merchant ID/hash secret mặc định:

- Email cần `MAILJET_API_KEY`, `MAILJET_SECRET_KEY` và sender được cấu hình đúng.
- Thanh toán online cần `VNPAY_TMN_CODE`, `VNPAY_HASH_SECRET` riêng. Chỉ chấp nhận hai endpoint HTTPS đã định nghĩa trong `vnpay_php/config.php`. Thiếu/sai cấu hình trả 503 trước khi tạo đơn hoặc trừ tồn kho; COD và catalog vẫn dùng được.
- Webhook cần `MAILJET_WEBHOOK_SECRET`; sai secret trả 401, chưa cấu hình trả 503, trước khi mở kết nối database.

Các khóa đã từng xuất hiện trong mã nguồn/lịch sử Git cần được chủ tài khoản thu hồi và tạo lại tại nhà cung cấp. Xóa khỏi phiên bản hiện tại không vô hiệu hóa khóa cũ và không sửa lịch sử Git.

## Kiểm thử

```sh
python3 scripts/test_security_configuration.py
python3 scripts/test_csrf.py
```

Bộ cấu hình kiểm tra header, private file, encoded path, file upload cũ không thể thực thi, Host giả, proxy giả, HTTPS/HSTS, root DB production, lỗi không lộ thông tin, thanh toán thiếu/sai khóa và cú pháp toàn bộ PHP. Test production/error policy chạy trong server loopback tạm, tự dừng/xóa sau khi chạy; cần `FRANKENPHP_BIN`, lệnh `frankenphp` hoặc runtime local trong `scratch/runtime/`.

Với biến môi trường `MYSQLPASSWORD` và các thông tin MySQL trùng server, chạy thêm:

```sh
python3 scripts/test_csrf_authenticated.py
python3 scripts/test_security_uploads.py
```

Hai bộ này tạo dữ liệu tạm để thử đăng nhập/CSRF và upload avatar/sản phẩm/bài viết, rồi dọn trong `finally`. `CSRF_BASE_URL` đổi địa chỉ server kiểm thử.

Trong phiên triển khai này, kiểm thử HTTP dùng PHP 8.5 của FrankenPHP; cấu hình Caddy, Apache và shell đã kiểm tra cú pháp. Chưa build/chạy image PHP 8.4 vì máy hiện tại không có Docker; cần xác minh image trên môi trường có Docker trước khi phát hành production.
