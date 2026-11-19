# Bảo vệ Insufficient Transport Layer Protection

Insufficient Transport Layer Protection là thiếu bảo vệ dữ liệu khi truyền qua mạng: dùng HTTP, TLS quá cũ, không xác minh chứng chỉ máy chủ, hoặc gửi cookie phiên qua kết nối không mã hóa. TLS cung cấp tính bí mật, toàn vẹn và xác thực máy chủ khi chứng chỉ được kiểm tra đúng. Thiết kế dựa trên [OWASP Transport Layer Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Transport_Layer_Security_Cheat_Sheet.html).

## Các lớp đã triển khai

| Phạm vi | Cách bảo vệ |
|---|---|
| Trình duyệt → website | Cấu hình Caddy chỉ cho TLS 1.2/1.3; chứng chỉ có hostname và chuỗi tin cậy được xác minh |
| HTTP → HTTPS | GET/HEAD trả 308 về địa chỉ cố định; POST và các phương thức ghi qua HTTP bị từ chối trước xử lý nghiệp vụ |
| Phiên đăng nhập | HTTPS đặt cookie `Secure`, `HttpOnly`, `SameSite=Lax`; không tạo session trên request HTTP bị từ chối/chuyển hướng |
| Production/staging | Bắt buộc `APP_URL=https://...`; `APP_REQUIRE_HTTPS=0` không tắt được yêu cầu HTTPS |
| Reverse proxy | Chỉ nhận `X-Forwarded-Proto` từ IP/CIDR trong `TRUSTED_PROXIES`; chỉ nhận một giá trị chính xác `http` hoặc `https` |
| Nội dung HTTPS | CSP bổ sung `upgrade-insecure-requests`, tránh tải tài nguyên bằng HTTP |
| HSTS production | `Strict-Transport-Security: max-age=31536000`; Caddy production đặt cả trên tài nguyên tĩnh và lỗi |
| Mailjet API | HTTPS, TLS tối thiểu 1.2, kiểm tra CA/hostname, không tự đi theo redirect mang thông tin API |
| MySQL từ xa | Bắt buộc CA tin cậy, xác minh chứng chỉ và kết nối TLS; không tự hạ xuống kết nối không mã hóa |

TLS không thay thế CSRF, XSS, kiểm tra quyền hoặc mã hóa dữ liệu lưu trữ. HTTP redirect cũng không thể mã hóa ngược dữ liệu đã được gửi qua HTTP: biểu mẫu và liên kết phải sử dụng HTTPS ngay từ đầu. HSTS giúp trình duyệt đã biết chính sách chủ động dùng HTTPS ở các lần sau; không tự bật preload hoặc `includeSubDomains` khi chưa kiểm soát tất cả tên miền con. Xem [OWASP HSTS](https://cheatsheetseries.owasp.org/cheatsheets/HTTP_Strict_Transport_Security_Cheat_Sheet.html).

## File chính

- `includes/security.php`: yêu cầu HTTPS, xác định kết nối qua proxy, HSTS và CSP.
- `includes/csrf.php`: khởi tạo cookie phiên với thuộc tính bảo mật sau khi kiểm tra transport.
- `includes/outbound_transport.php`, `includes/mail_helper.php`: chính sách HTTPS cho API email.
- `includes/database.php`: kết nối MySQL có xác minh TLS.
- `deploy/Caddyfile.local-https`: demo HTTPS local.
- `deploy/Caddyfile.production`: HTTPS production với chứng chỉ công khai.
- `deploy/Caddyfile.app`, `deploy/Caddyfile.runtime`: quy tắc bảo vệ file và PHP dùng chung giữa HTTP/HTTPS.
- `run-https.sh`: chạy demo riêng, giữ CA/private key trong `scratch/https/` đã được Git bỏ qua.

## Chạy demo trên máy Mac

Tại Terminal trong thư mục `Web-PHP`:

```sh
./run-https.sh
```

Giữ Terminal đang chạy, sau đó dùng:

- Website: `https://localhost:8443/`
- Điểm thử chuyển HTTP sang HTTPS: `http://localhost:8080/`
- Trang quản trị: `https://localhost:8443/admin/`

Script nạp cấu hình database local hiện có, đặt `APP_URL=https://localhost:8443`, `APP_ENV=local`, `APP_REQUIRE_HTTPS=1` và không tin header proxy. Dùng đúng hostname **localhost** vì chứng chỉ được cấp cho tên này. Cookie của `127.0.0.1:8000` không được dùng chung; đăng nhập lại bằng tài khoản thử trên HTTPS. `./run.sh` vẫn cung cấp HTTP loopback để phát triển, không dùng link đó làm bằng chứng bảo vệ TLS.

Có thể đổi cổng, ví dụ:

```sh
LOCAL_HTTPS_PORT=9443 LOCAL_HTTP_PORT=9080 ./run-https.sh
```

### Xác minh chứng chỉ mà không thay đổi hệ thống

Caddy tạo CA local. Script **không** tự thêm CA vào Keychain hoặc tắt xác minh chứng chỉ. Sau khi khởi động, mở Terminal khác và chạy:

```sh
curl --cacert scratch/https/data/caddy/pki/authorities/local/root.crt \
  -I https://localhost:8443/
```

Mong đợi: HTTP 200, `Set-Cookie` có `Secure; HttpOnly; SameSite=Lax`, CSP có `upgrade-insecure-requests`. Dùng `--cacert` chỉ tin CA này cho lệnh kiểm tra; không dùng `curl -k` vì nó bỏ qua xác thực chứng chỉ.

### Hiển thị kết nối được tin cậy trong Chrome

Lần đầu Chrome có thể báo CA chưa được tin cậy. Để demo trên trình duyệt, bạn có thể tự thêm đúng **CA của dự án này** vào Keychain của tài khoản:

1. Mở file `scratch/https/data/caddy/pki/authorities/local/root.crt` trong Keychain Access và thêm vào keychain `login`.
2. Mở thông tin chứng chỉ vừa nhập, mở **Trust**, đặt tin cậy cho **Secure Sockets Layer (SSL)** rồi lưu thay đổi.
3. Mở lại Chrome và truy cập `https://localhost:8443/`. Kiểm tra chứng chỉ áp dụng cho `localhost` và thuộc CA đã nhập.

Đây là thiết lập tin cậy của máy cá nhân, không phải thay đổi mã nguồn. Chỉ nhập public certificate `root.crt`; không chia sẻ `root.key` hoặc thư mục chứa private key. Có thể xóa đúng chứng chỉ đã thêm khỏi Keychain khi không còn dùng demo. Không cần làm bước này nếu chỉ kiểm thử bằng `curl --cacert`. Hướng dẫn giao diện tham khảo [Apple: thay đổi độ tin cậy của chứng chỉ](https://support.apple.com/en-ca/guide/keychain-access/kyca11871/mac).

### Các tình huống trình bày

1. **Kết nối được mã hóa:** mở website HTTPS đã tin CA, kiểm tra thông tin kết nối/chứng chỉ trong Chrome. Trong DevTools → Network → request trang → Headers, kiểm tra CSP; trong Application → Cookies, kiểm tra `Secure`, `HttpOnly`, `SameSite`.
2. **HTTP chỉ chuyển hướng các yêu cầu đọc:** chạy lệnh sau, kết quả 308 và `Location: https://localhost:8443/`:

   ```sh
   curl -I http://localhost:8080/
   ```

3. **Chặn gửi dữ liệu qua HTTP:** dùng dữ liệu giả, kết quả 400, không có Location hay Set-Cookie:

   ```sh
   curl -i --data 'demo=transport-test' http://localhost:8080/auth/dangnhap.php
   ```

4. **Từ chối chứng chỉ chưa tin và TLS cũ:** chạy `python3 scripts/test_tls_listener.py`. Bộ kiểm thử dùng CA tạm riêng, kiểm tra TLS 1.2/1.3 thành công, TLS 1.0/1.1 bị máy chủ từ chối, CA chưa tin và hostname sai bị client từ chối.
5. **HSTS và proxy:** chạy `python3 scripts/test_transport.py`. Các tình huống production được mô phỏng trong máy chủ tạm, xác nhận HSTS một năm và không thể dùng header giả để vượt yêu cầu HTTPS. Local không phát HSTS để tránh trình duyệt ghi nhớ nâng cấp HTTPS cho mọi cổng localhost.

Cách trình bày: “Em bảo vệ đường truyền bằng HTTPS và chỉ cho TLS 1.2/1.3. Cookie phiên có Secure nên trình duyệt không gửi qua HTTP. Máy chủ từ chối thao tác ghi qua HTTP và kiểm tra chứng chỉ khi kết nối dịch vụ bên ngoài. Production có HSTS; việc tin header proxy được giới hạn vào proxy đã cấu hình.”

## Triển khai production

Chứng chỉ local chỉ phục vụ phát triển, không dùng làm chứng chỉ công khai. Đặt biến môi trường trên máy chủ/hosting; production không nạp `.env` local.

### FrankenPHP/Caddy trực tiếp

Ví dụ cấu hình, thay tên miền và đường dẫn bằng giá trị thật:

```sh
export APP_ENV=production
export APP_URL=https://shop.example.com
export APP_DOMAIN=shop.example.com
export APP_ROOT=/srv/web-php
export TRUSTED_PROXIES=
frankenphp run --config /srv/web-php/deploy/Caddyfile.production
```

Cần DNS đúng, cổng 80/443 đến máy chủ, quyền lắng nghe cổng và nơi lưu chứng chỉ Caddy bền vững. Caddy quản lý chứng chỉ công khai và gia hạn; cấu hình giới hạn TLS 1.2/1.3. Nguồn: [Caddy TLS](https://caddyserver.com/docs/caddyfile/directives/tls), [Automatic HTTPS](https://caddyserver.com/docs/automatic-https). Các biến database/email/thanh toán vẫn phải được cấu hình riêng. Không chạy cấu hình production trên local chỉ để thử TLS.

### Docker Apache / Railway qua reverse proxy

Docker hiện dùng Apache HTTP phía sau proxy, không tự cấp chứng chỉ công khai. Proxy/hosting phải cung cấp TLS 1.2/1.3, chứng chỉ hợp lệ, ép HTTPS cho cả file tĩnh và trang PHP, và HSTS trên phản hồi HTTPS. Không công khai cổng HTTP backend để người dùng có thể đi vòng qua proxy.

Đặt `APP_ENV=production`, `APP_URL=https://ten-mien-thuc`, và `TRUSTED_PROXIES` bằng IP/CIDR nhỏ nhất của proxy thực tế. Proxy phải **ghi đè** `X-Forwarded-Proto`, không nối giá trị do client gửi. Không đặt `0.0.0.0/0` hoặc `::/0`; ứng dụng sẽ từ chối cấu hình đó. TLS trên chặng proxy → ứng dụng không chứng minh chặng trình duyệt → proxy đã mã hóa; giá trị `http` từ proxy tin cậy vẫn khiến yêu cầu bị chuyển hướng/từ chối. Nếu backend đi qua mạng khác, bảo vệ cả chặng đó bằng TLS có xác minh hoặc kênh được mã hóa thích hợp.

## MySQL và email

MySQL ở literal `localhost`, `127.0.0.1`, `::1` được phép không TLS cho kết nối cùng máy. Mọi host khác, kể cả mạng private, cần cấu hình:

```dotenv
MYSQLHOST=db.example.com
MYSQL_SSL_CA=/run/secrets/mysql-ca.pem
MYSQL_SSL_CERT=
MYSQL_SSL_KEY=
```

CA phải xác minh được chứng chỉ của server, hostname phải khớp. Nếu server yêu cầu chứng chỉ client, cung cấp cả `MYSQL_SSL_CERT` và `MYSQL_SSL_KEY`. Các file nằm ngoài thư mục public, chỉ tiến trình cần thiết được đọc. Cấu hình thiếu/sai hoặc không thương lượng được TLS trả 503; không tự thử lại bằng kết nối không mã hóa. Nhà cung cấp MySQL phải hỗ trợ TLS và cung cấp CA tương ứng trước khi dùng cấu hình này. Không khai báo loopback để che giấu một đường hầm không mã hóa đi qua mạng.

Mailjet dùng endpoint HTTPS cố định, kiểm tra chuỗi CA và hostname bằng trust store của máy chủ, yêu cầu TLS tối thiểu 1.2, không follow redirect. Máy chủ cần bộ CA tin cậy được cập nhật. Không có SMTP fallback trong mã hiện tại; lỗi TLS không được bỏ qua bằng cách chuyển sang SMTP/HTTP không an toàn.

## Kiểm thử

Các kiểm thử transport tự tạo server/CA tạm trên loopback, không gửi email/giao dịch thật và không sửa trust store:

```sh
python3 scripts/test_transport.py
python3 scripts/test_tls_listener.py
python3 scripts/test_outbound_transport.py
```

Các kiểm thử hồi quy dùng website HTTP local đang chạy bằng `./run.sh`:

```sh
python3 scripts/test_redirects.py
python3 scripts/test_csrf.py
python3 scripts/test_security_configuration.py
python3 scripts/test_xss.py
```

Kiểm thử local không chứng minh cấu hình TLS của hosting đã đúng. Khi triển khai thật, kiểm tra lại chứng chỉ/hostname/ngày hết hạn, phiên bản TLS, HTTP redirect, cookie, HSTS và chặng proxy/backend bằng chính tên miền production.
