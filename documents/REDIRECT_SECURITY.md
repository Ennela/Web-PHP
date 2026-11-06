# Bảo vệ Unvalidated Redirects and Forwards

Unvalidated Redirect là việc ứng dụng chuyển trình duyệt đến địa chỉ chưa được kiểm tra, thường lấy từ tham số người dùng. Kẻ tấn công có thể lợi dụng đường link mang tên miền của cửa hàng để dẫn nạn nhân sang trang đăng nhập giả. Forward là việc máy chủ chuyển xử lý sang tài nguyên khác; nếu bỏ qua kiểm tra quyền ở đích thì có thể dẫn đến truy cập trái phép.

Thiết kế tham khảo [OWASP Unvalidated Redirects and Forwards Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Unvalidated_Redirects_and_Forwards_Cheat_Sheet.html): ưu tiên đích cố định hoặc mã trang ánh xạ ở máy chủ, kiểm tra URL bằng allowlist và giữ kiểm tra quyền ở trang đích.

## Cách triển khai trong dự án

`config.php` nạp `includes/redirect.php`. Các chuyển hướng HTTP trong PHP dùng bộ kiểm tra chung:

| Hàm | Trách nhiệm |
|---|---|
| `app_redirect($target, $status = 302)` | Chỉ chuyển đến trang điều hướng được phép trong ứng dụng; gửi Location và kết thúc xử lý |
| `app_local_redirect_url($target)` | Trả về đường dẫn bắt đầu bằng `/` đã kiểm tra, hoặc `null` nếu không hợp lệ |
| `app_return_destination($id)` | Đổi mã `home`, `shop`, `cart`, `account`, `orders` thành đích cố định cho đăng nhập khách hàng |
| `app_payment_redirect($target)` | Chỉ chuyển đến hai endpoint HTTPS chính xác của VNPay; dùng HTTP 303 |
| `app_https_redirect($canonical, $uri)` | Nâng cấp HTTP lên HTTPS bằng origin từ APP_URL đã kiểm tra; dùng HTTP 308 |

Ví dụ trong mã nguồn:

```php
// Đích do lập trình viên chọn; không phụ thuộc thư mục của URL yêu cầu.
app_redirect(BASE_URL . 'shop/giohang.php', 303);

// Dữ liệu người dùng chỉ chọn một mã trang được phép.
$destination = app_return_destination($_POST['return_to'] ?? 'home');
// Chỉ chuyển đến $destination SAU KHI xác thực đăng nhập thành công.
```

Bộ kiểm tra nội bộ yêu cầu URL tuyệt đối cùng **scheme, hostname và cổng hiệu lực** với BASE_URL, hoặc đường dẫn bắt đầu bằng `/` nằm trong thư mục triển khai. Đường dẫn phải thuộc `app_redirect_pages()`. URL ngoài, `//host`, userinfo (`user@host`), backslash, ký tự điều khiển/CRLF kể cả dạng mã hóa lồng nhau, path traversal, PATH_INFO, đường dẫn mã hóa và đích không thuộc danh sách đều bị từ chối với HTTP 400. Không tự sửa URL lỗi để cố chuyển hướng. Địa chỉ `APP_URL` dùng đường dẫn triển khai đơn giản, ví dụ `/` hoặc `/Web-PHP/`; không hỗ trợ đường dẫn có dấu cách hay percent encoding.

Query và fragment hợp lệ được giữ nguyên; chúng không được dùng để thay đổi origin. Sau kiểm tra, chuyển hướng nội bộ xuất đường dẫn từ gốc website để không phụ thuộc URL hiện tại. Không lấy đích từ `Referer`, `X-Forwarded-Host` hay URL tùy ý trong dữ liệu khách hàng. `safe_url()` của lớp XSS **không** dùng thay bộ kiểm tra redirect, vì nó cho phép liên kết HTTP/HTTPS bên ngoài.

VNPay là ngoại lệ bên ngoài duy nhất cho chuyển hướng HTTP của ứng dụng:

- `https://sandbox.vnpayment.vn/paymentv2/vpcpay.html`
- `https://pay.vnpay.vn/vpcpay.html`

Cấu hình merchant được kiểm tra trước khi tạo đơn/trừ kho; URL cuối cùng được kiểm tra lại ngay trước chuyển hướng. Query thanh toán được tạo và ký trên máy chủ như trước. Không cho phép hostname gần giống, cổng thêm, userinfo, path khác hay fragment. Bộ kiểm thử chỉ quan sát header, không gửi giao dịch đến VNPay.

## Đăng nhập và phân quyền

Trang `auth/dangnhap.php` nhận `return_to` qua GET khi mở form và POST khi gửi form. Giá trị được kiểm tra lại tại POST, được escape trong hidden input và giữ lại khi đăng nhập thất bại. Không cung cấp tham số này thì mặc định `home`. Đăng nhập thành công trả HTTP 303 đến trang đã chọn; token CSRF và session vẫn được thay mới.

Danh sách dành cho khách hàng không chứa trang quản trị. Allowlist điều hướng tổng thể có trang quản trị để phục vụ luồng admin, nhưng **không cấp quyền truy cập**: kiểm tra session/quyền ở trang đích vẫn bắt buộc. Các hàm `require_user`, `require_admin`, `secure_logout` cũng kiểm tra đích trước khi chuyển hướng.

Dự án hiện dùng `include`/`require` với đích được xác định trong mã nguồn; không thêm cơ chế forward nhận đường dẫn từ người dùng. Không dùng `include($_GET['page'])`, không chuyển thẳng sang handler để bỏ qua kiểm tra quyền. `app_redirect()` luôn kết thúc request sau khi gửi header. Những chuyển trang bằng JavaScript hiện có dùng đích cố định trong mã nguồn; không nhận URL điều hướng từ người dùng.

## Demo từng bước

1. Tại Terminal của dự án chạy `./run.sh`, mở `http://127.0.0.1:8000/`.
2. Mở **DevTools → Network**, bật **Preserve log** để giữ request khi chuyển trang.
3. Mở `http://127.0.0.1:8000/auth/dangnhap.php?return_to=shop` và đăng nhập bằng tài khoản thử nghiệm. Request POST đăng nhập trả **303**, header **Location: /shop/shop.php**, rồi trình duyệt mở cửa hàng.
4. Thử `http://127.0.0.1:8000/auth/dangnhap.php?return_to=https%3A%2F%2Fexample.org`. Kết quả **400**, thông báo “Đích chuyển hướng không hợp lệ.”, không có Location và không truy cập example.org. Bước này không cần đăng nhập.
5. Thử `http://127.0.0.1:8000/auth/dangnhap.php?return_to=admin` hoặc `?return_to=%2F%2Fexample.org`. Kết quả cũng **400**.
6. Trong cửa sổ ẩn danh chưa đăng nhập quản trị, mở `/admin/trangchu.php`. Request trả **302** về `/admin/canhbao.php`; dữ liệu quản trị không được hiển thị. Điều này kiểm tra rằng truy cập trực tiếp vào đích vẫn cần quyền.

Có thể kiểm tra không tự đi theo chuyển hướng bằng Terminal:

```sh
curl -i 'http://127.0.0.1:8000/auth/dangnhap.php?return_to=https%3A%2F%2Fexample.org'
curl -i 'http://127.0.0.1:8000/admin/trangchu.php'
```

Cách trình bày: “Em chỉ nhận mã trang trong danh sách cho phép ở bước đăng nhập. Khi thay mã trang bằng website lạ, máy chủ trả 400, không phát header chuyển hướng. Các chuyển hướng nội bộ được kiểm tra chung; thanh toán chỉ được ra đúng endpoint VNPay. Trang quản trị vẫn kiểm tra quyền khi truy cập trực tiếp.”

## Kiểm thử

Máy chủ local phải đang chạy:

```sh
python3 scripts/test_redirects.py
python3 scripts/test_csrf.py
python3 scripts/test_security_configuration.py
python3 scripts/test_xss.py
```

`test_redirects.py` dùng máy chủ FrankenPHP tạm trên loopback để thử URL cùng origin, khác cổng/scheme, hostname giả, userinfo, CRLF, encoding, traversal, triển khai dưới thư mục con, VNPay, HTTPS upgrade và việc kết thúc request. Fixture không được thêm vào website thật và được dọn sau kiểm thử. Các kiểm thử HTTP không tự đi theo đích bên ngoài.

Đặt `MYSQLPASSWORD` cùng các biến MySQL tương ứng với database local để bật kiểm thử đăng nhập. MySQL CLI cần có trong PATH. Tài khoản thử có tên ngẫu nhiên, được xóa trong `finally`. Khi không cung cấp biến này, kiểm thử đăng nhập có dữ liệu sẽ báo **skipped**, các kiểm thử khác vẫn chạy. Kết quả kiểm thử xác nhận các tình huống đã kiểm tra, không thay thế việc rà soát khi thêm route hoặc tích hợp mới.

Khi thêm đích chuyển hướng, dùng helper và chỉ bổ sung trang phù hợp vào allowlist; giữ kiểm tra quyền tại đích. Không gọi trực tiếp `app_send_redirect()` từ mã nghiệp vụ: đây là điểm gửi header nội bộ của các helper đã kiểm tra.
