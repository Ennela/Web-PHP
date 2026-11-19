# Bảo vệ Cross-Site Scripting (XSS)

Dự án xử lý ba bề mặt XSS: dữ liệu phản chiếu từ URL/tìm kiếm, dữ liệu đã lưu trong database và nội dung được JavaScript dựng vào DOM. Cách xử lý dựa trên [OWASP XSS Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html): mã hóa theo ngữ cảnh, lọc HTML có định dạng và dùng DOM API an toàn. CSP bổ sung một lớp chặn thực thi script.

## Các lớp bảo vệ

`config.php` nạp `includes/xss.php` cho toàn ứng dụng:

| Helper | Mục đích |
|---|---|
| `e($value)` | Xuất văn bản hoặc giá trị thuộc tính HTML đã được đặt trong dấu nháy; xử lý cả ký tự nháy và UTF-8 lỗi |
| `js_value($value)` | Tạo JSON literal an toàn để đưa vào script, mã hóa các ký tự có thể đóng thẻ script |
| `safe_url($value)` | Chỉ nhận URL HTTP/HTTPS hoặc đường dẫn tương đối, loại scheme nguy hiểm, URL có userinfo và các dạng control/encoding bypass; vẫn dùng `e()` khi xuất thuộc tính |
| `clean_html($value)` | Lọc nội dung có định dạng bằng HTML Purifier, chỉ giữ tag/attribute/CSS/scheme trong allowlist |
| `csp_nonce()` | Nonce ngẫu nhiên mới cho mỗi phản hồi, dùng cho script từ template tin cậy |

Ví dụ:

```php
<h2><?= e($product['tensp']) ?></h2>
<img src="<?= e(safe_url($imageUrl)) ?>" alt="<?= e($product['tensp']) ?>">
<textarea name="noidung"><?= e($product['noidung']) ?></textarea>
<div class="description"><?= clean_html($product['noidung']) ?></div>
<script nonce="<?= e(csp_nonce()) ?>">
const productName = <?= js_value($product['tensp']) ?>;
</script>
```

Luôn đặt giá trị thuộc tính trong dấu nháy. `e()` không phải bộ mã hóa JavaScript/CSS và không tự kiểm tra scheme của URL. Không nối dữ liệu người dùng vào HTML event handler hoặc mã JavaScript.

Mô tả sản phẩm và bài viết được lọc cả trước khi lưu và khi hiển thị, để bảo vệ cả dữ liệu cũ. Nội dung textarea được escape, nên chuỗi `</textarea>` không thoát ra khỏi ô chỉnh sửa. HTML có định dạng an toàn như đoạn văn, chữ đậm, danh sách, bảng và liên kết HTTP/HTTPS vẫn được giữ.

JavaScript quản lý tài khoản dựng tên, địa chỉ, size, ghi chú và toast bằng `createElement`, `textContent`, `dataset` và `addEventListener`. Thông báo SweetAlert dùng `titleText`. Các `innerHTML` còn lại là mẫu tĩnh hoặc fragment đơn hàng cùng origin đã escape từng trường ở server.

## CSP

Policy ở `includes/security.php` có nonce cho script tin cậy và `script-src-attr 'none'`; không còn `'unsafe-inline'` trong `script-src`. Các nút/form/gallery được chuyển sang `addEventListener` để tiếp tục hoạt động. CSS inline được giữ để tương thích giao diện.

Chỉ gắn nonce trong template nguồn đã được kiểm soát. Không tự gắn nonce cho mọi thẻ script trong HTML sau khi render: cách đó có thể cấp quyền cho script do người dùng chèn vào. Token CSRF và nonce CSP là hai giá trị có mục đích riêng; bảo vệ CSRF không thay thế xử lý XSS.

## Thư viện lọc HTML

HTML Purifier **4.19.1**, commit `5e0539132d934f936fbae2c88b55d58f9438623f`, được đóng gói trong `lib/htmlpurifier/` để chạy được với dự án PHP thuần hiện tại mà không cần bước Composer ở máy người dùng. Nguồn: [ezyang/htmlpurifier](https://github.com/ezyang/htmlpurifier).

Thư viện giữ nguyên mã upstream, kèm LICENSE, VERSION và `UPSTREAM.json` ghi SHA-256 của từng file. Cache định nghĩa được tắt để không cần quyền ghi vào mã nguồn trong Docker. Thư mục `lib/` bị chặn truy cập HTTP bằng Caddy/Apache và guard ứng dụng.

Khi cập nhật thư viện, lấy release chính thức, thay đúng phần `library/` cùng license/version, cập nhật manifest và chạy lại các bộ kiểm thử. Không tự sửa mã thư viện hoặc thay sanitizer bằng regex/`strip_tags()`.

## Demo trên local

Chạy `./run.sh`, mở `http://127.0.0.1:8000/` và tải lại trang để nhận CSP mới.

1. Trong tìm kiếm cửa hàng, nhập `<img src=x onerror=alert('XSS')>`. Chuỗi được hiển thị như dữ liệu, không tạo ảnh thực thi sự kiện và không bật alert.
2. Với tài khoản thử nghiệm, nhập payload vào tên hiển thị/địa chỉ/ghi chú size rồi xem lại trang tài khoản và quản trị. Nội dung hiển thị dạng văn bản; không thực thi script.
3. Trong mô tả sản phẩm hoặc bài viết thử nghiệm, nhập `<p><strong>Nội dung hợp lệ</strong></p><script>alert('XSS')</script>`. Khi lưu/xem lại, chữ đậm vẫn giữ, script bị loại.
4. Trong DevTools → Network → Headers, kiểm tra `Content-Security-Policy` có `nonce-...`, `script-src-attr 'none'` và không có `'unsafe-inline'` ở chỉ thị `script-src`.

Nếu giảng viên hỏi về ba loại XSS: tìm kiếm kiểm tra đường phản chiếu; tên/mô tả đã lưu kiểm tra đường lưu trữ; địa chỉ, size và toast từ AJAX kiểm tra cách dữ liệu được dựng vào DOM. Các payload chỉ dùng trên dữ liệu thử do bạn kiểm soát.

## Kiểm thử

```sh
python3 scripts/test_xss.py
python3 scripts/test_csrf.py
python3 scripts/test_security_configuration.py
```

`test_xss.py` kiểm tra ngữ cảnh HTML/thuộc tính/JavaScript, URL nguy hiểm và encoding bypass, HTML có định dạng, payload SVG/iframe/mXSS và CSP nonce. Đặt `MYSQLPASSWORD` cùng các biến MySQL phù hợp để bật kiểm thử dữ liệu lưu trữ; dữ liệu tạm được dọn trong `finally`.

Kiểm tra JavaScript account bằng Node không cần dependency:

```sh
node scripts/test_xss_dom.cjs
```

Kiểm thử Chrome thật là tùy chọn, sử dụng Chrome headless với hồ sơ tạm riêng và CDP chỉ bind loopback. Đặt `XSS_CHROME_URL` trỏ đến CDP endpoint, rồi chạy `test_xss.py` với biến kết nối database. Script `test_xss_browser.cjs` nhận cookie fixture qua stdin, tạo browser context riêng, kiểm tra payload không thực thi, CSP chặn script/event không nonce và kiểm tra các nút chọn size/số lượng/menu vẫn hoạt động.
