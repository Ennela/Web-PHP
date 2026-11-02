# Bảo vệ CSRF

Ứng dụng dùng synchronizer token gắn với session, theo [hướng dẫn OWASP](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html). `config.php` nạp `includes/csrf.php`, khởi tạo session và kiểm tra token **trước khi xử lý nghiệp vụ** cho mọi phương thức ngoài GET, HEAD, OPTIONS.

## Cách hoạt động

- Token là 32 byte ngẫu nhiên từ `random_bytes()`, mã hóa thành 64 ký tự hex và lưu trong session phía server. Kiểm tra bằng `hash_equals()`; token của session khác không hợp lệ.
- Form POST gửi trường `csrf_token`. AJAX JSON hoặc upload gửi header `X-CSRF-Token`. Không chấp nhận token trong query string.
- Token giữ ổn định giữa các form/tab trong cùng session. Đăng nhập thành công đổi session ID và token; đăng xuất hủy session. Tab mở trước khi đăng nhập hoặc phiên đã hết hạn cần tải lại trang.
- Token thiếu, sai hoặc có kiểu dữ liệu không hợp lệ trả HTTP 403. API trả JSON với `success: false`, `error: CSRF_INVALID` và `message`; form thường nhận trang thông báo bằng tiếng Việt.
- Session chỉ dùng cookie, bật strict mode, `HttpOnly`, `SameSite=Lax` và `Secure` khi phục vụ qua HTTPS. `Lax` cho phép trình duyệt trở về từ VNPAY bằng GET.
- Thêm/sửa/xóa giỏ hàng, đặt hàng, cập nhật tài khoản, thay đổi đơn, thao tác quản trị và đăng xuất dùng POST. Các endpoint xóa và in hóa đơn có cập nhật trạng thái từ chối GET/HEAD bằng HTTP 405. Link đăng xuất GET cũ không đăng xuất người dùng.
- Trang đơn hàng có nút **Cập nhật trạng thái giao hàng** gửi POST có token. Việc hoàn tất đơn SHIPPING quá 15 ngày chỉ áp dụng cho đơn của khách hàng đang đăng nhập, thay cho thao tác ghi dữ liệu khi mở trang bằng GET.

## Callback bên ngoài

`shop/vnpay_return.php` chỉ nhận GET và yêu cầu chữ ký HMAC-SHA512 hợp lệ trước khi đọc/cập nhật đơn hoặc hoàn tồn kho. Callback thiếu/sai chữ ký bị trả HTTP 403, không bị coi là giao dịch thất bại để hủy đơn.

`webhooks/mailjet_webhook.php` là ngoại lệ duy nhất cho token trình duyệt. Ngoại lệ khớp chính xác file entry point phía server, không dùng URL hoặc tham số do client gửi. Endpoint chỉ nhận POST và bắt buộc xác thực secret **trước khi kết nối database**:

- Đặt biến môi trường `MAILJET_WEBHOOK_SECRET` thành secret riêng đủ dài.
- Cấu hình phía gửi truyền cùng giá trị qua `X-Webhook-Secret` hoặc `Authorization` như hợp đồng webhook hiện có của dự án.
- Secret chưa được cấu hình hoặc còn là giá trị mẫu: HTTP 503. Secret thiếu/sai: HTTP 401. Endpoint không dùng cookie đăng nhập để xác thực.

## Khi thêm tính năng mới

Luôn nạp `config.php` trước output và trước mọi thay đổi dữ liệu. Không viết thao tác thay đổi dữ liệu trong GET, HEAD hoặc OPTIONS. Endpoint chỉ nhận POST gọi `csrf_require_post()` sau khi nạp cấu hình.

```php
<?php
require_once dirname(__DIR__) . '/config.php';
// Với handler chỉ nhận POST: csrf_require_post();
?>
<form method="post">
    <?= csrf_field() ?>
    <!-- Các trường nghiệp vụ -->
    <button type="submit">Lưu</button>
</form>
```

Header dùng chung xuất token trong `<meta name="csrf-token">`. Với AJAX:

```javascript
fetch(url, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify(payload)
});
```

Với `FormData`, chỉ thêm `X-CSRF-Token`, để trình duyệt tự đặt `Content-Type` và multipart boundary. Chỉ gửi token đến endpoint cùng ứng dụng. Không lưu toàn bộ `$_POST` làm dữ liệu nghiệp vụ/bộ lọc; chọn rõ các trường được phép để loại `csrf_token`.

## Kiểm thử

Khởi động ứng dụng rồi chạy bộ HTTP regression bằng Python 3, không cần thư viện bổ sung:

```sh
python3 scripts/test_csrf.py
```

Đổi địa chỉ bằng `CSRF_BASE_URL`, ví dụ `http://localhost/WEB-PHP/`. Bộ kiểm thử kiểm tra token thiếu/sai/mảng/query/session khác, form/JSON/multipart, cookie, các phương thức thay đổi dữ liệu, form render và callback không hợp lệ.

Kiểm thử có đăng nhập cần MySQL CLI trong PATH và các biến kết nối trùng với server (`MYSQLPASSWORD`, tùy chọn `MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLDATABASE`):

```sh
python3 scripts/test_csrf_authenticated.py
```

Bộ này tạo tài khoản khách hàng, quản trị và đơn hàng tạm; kiểm tra đăng ký, hai đường đăng nhập quản trị, đổi session/token, chống đăng xuất GET, API sau đăng nhập, phân quyền xóa, dữ liệu đơn không đổi khi thiếu token, bộ lọc quản trị và đăng xuất. Dữ liệu tạm được xóa trong `finally`.
