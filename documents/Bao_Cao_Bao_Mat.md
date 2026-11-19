# BÁO CÁO PHÂN TÍCH VÀ KHẮC PHỤC LỖ HỔNG BẢO MẬT
**Dự án:** Web-PHP (Hệ thống website bán giày thể thao)  
**Chuyên đề:** Failure to Restrict URL Access, SQL Injection (SQLi) & Broken Authentication and Session Management

---

## PHẦN 1: LỖ HỔNG KHÔNG HẠN CHẾ QUYỀN TRUY CẬP URL (FAILURE TO RESTRICT URL ACCESS)

### 1.1. Bản chất lý thuyết
Lỗ hổng **Không hạn chế quyền truy cập URL** (*Failure to Restrict URL Access* - thuộc nhóm Broken Access Control / Phân quyền bị lỗi trong OWASP Top 10) xảy ra khi ứng dụng web không kiểm tra quyền hạn của người dùng (Authorization) một cách nghiêm ngặt trên máy chủ (server-side) trước khi cho phép truy cập vào các trang web, chức năng hoặc tệp tin nhạy cảm.

Hệ thống thường chỉ dựa vào việc "giấu" đường dẫn khỏi giao diện người dùng thông thường nhưng không kiểm tra xem người gửi yêu cầu URL có thực sự có vai trò (role) hợp lệ hay không.

### 1.2. Hậu quả thực tế
* **Leo quyền trái phép (Privilege Escalation):** Người dùng thông thường hoặc khách vãng lai chỉ cần biết hoặc đoán được đường dẫn URL (Force Browsing / URL Guessing) là có thể truy cập thẳng vào trang quản trị (Admin Dashboard).
* **Lộ dữ liệu nhạy cảm:** Xem danh sách khách hàng, tài khoản, doanh thu và đơn hàng.
* **Thực thi thao tác trái phép:** Gọi các URL xóa sản phẩm, xóa bài viết, duyệt đơn hàng mà không cần đăng nhập quyền Admin.
* **Lộ mã nguồn và cơ sở dữ liệu:** Tải trực tiếp các file `.sql`, `.env`, file cấu hình nếu thư mục không được chặn.

### 1.3. Phương pháp khắc phục trong dự án
Hệ thống đã được thiết lập cơ chế phòng thủ 2 lớp:

#### Lớp 1: Middleware kiểm soát phiên và vai trò (Admin Guard)
* File: `admin/admin_guard.php` được nhúng vào đầu tất cả các tệp trong thư mục `/admin/`.
* **Cơ chế:** Kiểm tra xem session người dùng đã khởi tạo chưa và có đúng `$_SESSION['role'] === 'admin'` hay không.
* **Xử lý:** Nếu chưa đăng nhập, tự động chuyển hướng (Redirect 302) về trang `admin/canhbao.php`. Nếu là AJAX thì trả về mã lỗi HTTP 401. Nếu không phải quyền admin thì chặn với mã HTTP 403 Forbidden.

```php
// File: admin/admin_guard.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['dangnhap']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: canhbao.php");
    exit();
}
```

#### Lớp 2: Cấu hình Web Server chặn truy cập file nhạy cảm
* Đặt tệp `.htaccess` trong các thư mục `database/`, `includes/`, `documents/` với lệnh `Deny from all` để cấm tải trực tiếp các tệp tin mã nguồn và bản sao lưu cơ sở dữ liệu.

### 1.4. Quy trình kiểm thử (Testing Guide)
* **Test 1.1 - Truy cập trang Admin khi chưa đăng nhập:**
  * Mở Cửa sổ Ẩn danh (Incognito), truy cập: `http://localhost/Web-PHP/admin/index.php` hoặc `http://localhost/Web-PHP/admin/quanlisanpham.php`
  * *Kết quả:* Bị chặn và chuyển hướng ngay về `admin/canhbao.php`.
* **Test 1.2 - Kích hoạt URL xóa nhạy cảm:**
  * Truy cập: `http://localhost/Web-PHP/admin/delete_qlsanpham.php?masp=1`
  * *Kết quả:* Không xóa dữ liệu và bị chuyển hướng về trang đăng nhập.
* **Test 1.3 - Tải trực tiếp file SQL:**
  * Truy cập: `http://localhost/Web-PHP/database/giaythethao2.sql`
  * *Kết quả:* Bị chặn với lỗi `403 Forbidden`.

---

## PHẦN 2: LỖ HỔNG CHÈN CÂU LỆNH SQL (SQL INJECTION - SQLi)

### 2.1. Bản chất lý thuyết
SQL Injection (SQLi) là lỗ hổng phát sinh khi dữ liệu người dùng nhập vào (qua form, URL GET/POST) được ghép nối trực tiếp (string concatenation) vào chuỗi truy vấn SQL mà không được phân tách giữa câu lệnh (Code) và dữ liệu (Data).

Kẻ tấn công sử dụng các ký tự điều khiển cú pháp SQL (như `'`, `"`, `--`, `;`) để làm thay đổi logic thực thi của cơ sở dữ liệu.

### 2.2. Hậu quả thực tế
* **Vượt qua màn hình đăng nhập (Authentication Bypass):** Dùng chuỗi `' OR '1'='1` để đăng nhập vào tài khoản admin/khách hàng mà không cần mật khẩu.
* **Đánh cắp cơ sở dữ liệu:** Sử dụng kỹ thuật UNION SELECT hoặc Error-based để trích xuất bảng mật khẩu, thông tin cá nhân.
* **Xóa hoặc sửa đổi dữ liệu trái phép:** Biến câu lệnh xóa thành điều kiện luôn đúng để xóa sạch bảng dữ liệu.

### 2.3. Phương pháp khắc phục trong dự án
* **Biện pháp 1: Áp dụng Prepared Statements (Parameterized Queries):**  
  Tách biệt hoàn toàn câu lệnh và dữ liệu. Dữ liệu người dùng chỉ được truyền qua tham số (`?`) và hàm `mysqli_stmt_bind_param()`, cơ sở dữ liệu xử lý an toàn 100% dưới dạng chuỗi hoặc số.
  ```php
  // Ví dụ tại auth/dangnhap.php:
  $stmt = mysqli_prepare($con, "SELECT * FROM `tbl_tkkhachhang` WHERE `username` = ? AND `password` = ? LIMIT 1");
  mysqli_stmt_bind_param($stmt, "ss", $username, $password);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  ```
* **Biện pháp 2: Thư viện trợ giúp `includes/sql_helper.php`:**
  * `sql_int()`: Ép kiểu số nguyên cho mọi ID trên URL (ví dụ: `0 OR 1=1` bị ép thành `0`).
  * `sql_pagination()`: Giới hạn số trang và bản ghi.
  * `sql_build_filter()`: Whitelist danh sách cột hợp lệ bằng `SHOW COLUMNS`.

### 2.4. Quy trình kiểm thử (Testing Guide)
* **Test 2.1 - Vượt qua đăng nhập (Login Bypass):**
  * URL: `http://localhost/Web-PHP/auth/dangnhap.php`
  * Tài khoản: `' OR '1'='1` | Mật khẩu: `123456`
  * *Kết quả:* Báo sai tài khoản/mật khẩu, từ chối đăng nhập.
* **Test 2.2 - Xóa dữ liệu qua tham số URL:**
  * Đăng nhập admin rồi truy cập: `http://localhost/Web-PHP/admin/delete_qlsanpham.php?masp=0 OR 1=1`
  * *Kết quả:* Tham số bị ép về `0`, không bản ghi nào bị xóa, database giữ nguyên 33 sản phẩm.
* **Test 2.3 - Tìm kiếm Admin:**
  * URL: `http://localhost/Web-PHP/admin/quanlisanpham.php`
  * Nhập ô tìm kiếm: `' OR '1'='1`
  * *Kết quả:* Không lỗi cú pháp SQL, chỉ tìm sản phẩm có đúng chuỗi ký tự đó.
* **Test 2.4 - Tra cứu đơn hàng:**
  * URL: `http://localhost/Web-PHP/shop/tracking.php?order=' OR '1'='1&token=' OR '1'='1`
  * *Kết quả:* Báo "Không tìm thấy đơn hàng hoặc token không đúng", không bị lộ dữ liệu.

---

## PHẦN 3: XÁC THỰC VÀ QUẢN LÝ PHIÊN BỊ LỖI (BROKEN AUTHENTICATION AND SESSION MANAGEMENT)

### 3.1. Cơ chế đăng nhập – session – cookie

HTTP là giao thức **không trạng thái**: mỗi request độc lập, server không tự biết request sau là của ai. PHP giải quyết bằng **session**:

1. Người dùng gửi tài khoản + mật khẩu (POST).
2. Server kiểm tra mật khẩu (so `password_verify()` với hash bcrypt trong DB).
3. Đúng → server tạo **session** (file trên server chứa `$_SESSION['makh']`, `$_SESSION['role']`…) và gửi cho trình duyệt **cookie `PHPSESSID`** chứa **session ID** (chuỗi ngẫu nhiên).
4. Các request sau, trình duyệt tự gửi cookie → server tra session ID → biết đang là ai.

```mermaid
sequenceDiagram
    participant B as Trình duyệt
    participant S as Server PHP
    B->>S: POST dangnhap.php (taikhoan, matkhau, csrf_token)
    S->>S: password_verify() + kiểm tra brute-force
    S->>S: session_regenerate_id(true) — cấp ID mới
    S-->>B: Set-Cookie: PHPSESSID=abc...; HttpOnly; SameSite=Lax
    B->>S: GET trangchu.php (Cookie: PHPSESSID=abc...)
    S->>S: auth_session_enforce(): idle / absolute / fingerprint
    S-->>B: Trang đã đăng nhập
    B->>S: POST login=dangxuat
    S->>S: Xóa $_SESSION, session_destroy()
    S-->>B: Set-Cookie: PHPSESSID=deleted (hết hạn)
```

👉 **Session ID chính là "chìa khóa" đăng nhập.** Ai có session ID là người đó — không cần mật khẩu. Vì vậy mọi lỗi làm lộ, đoán được, hoặc kéo dài vô hạn session ID đều là lỗ hổng.

### 3.2. Vòng đời phiên (Session Lifecycle)

| Giai đoạn | Việc phải làm | Cài đặt trong dự án |
| :--- | :--- | :--- |
| **Tạo** | ID ngẫu nhiên, chỉ qua cookie, từ chối ID do client tự đặt | `session.use_strict_mode=1`, `use_only_cookies=1`, `use_trans_sid=0` |
| **Nâng quyền (đăng nhập)** | Cấp **ID mới** để ID cũ (có thể bị cài sẵn) vô hiệu | `auth_login()` → `session_regenerate_id(true)` + đổi CSRF token |
| **Sử dụng** | Cookie `HttpOnly` (JS không đọc được), `Secure` (HTTPS), `SameSite`; đổi ID định kỳ | cookie flags trong `csrf_bootstrap()`; xoay ID mỗi 15 phút |
| **Hết hạn** | Không thao tác lâu → hết phiên; quá thời gian tối đa → đăng nhập lại | Idle 30 phút, absolute 8 giờ (`auth_session_enforce()`) |
| **Hủy (đăng xuất)** | Xóa dữ liệu + xóa cookie + hủy session phía server | `auth_destroy_session()` / `auth_handle_logout_request()` |

### 3.3. Các lỗ hổng tìm thấy trong mã nguồn (trước khi sửa)

| # | Lỗ hổng | Vị trí | Hậu quả |
| :-: | :--- | :--- | :--- |
| 1 | **Không giới hạn số lần đăng nhập sai** | `auth/dangnhap.php`, `admin/canhbao.php` | Brute-force / dò mật khẩu admin không giới hạn |
| 2 | **Đặt lại mật khẩu lưu PLAINTEXT** vào DB | `auth/api_datlamatkhau.php` | Lộ DB = lộ mật khẩu thật của khách hàng |
| 3 | **Token reset lưu nguyên văn** trong DB | `auth/api_quenmatkhau.php` | Lộ DB → chiếm mọi tài khoản đang yêu cầu reset |
| 4 | **Dò tài khoản (User Enumeration)** ở Quên mật khẩu | `api_quenmatkhau.php` báo "tài khoản không tồn tại" | Kẻ tấn công biết username/email nào có thật |
| 5 | **Đăng nhập bằng chính chuỗi hash** | nhánh fallback `password === $matkhau` | Lấy được hash (SQLi, backup) là gõ thẳng hash để đăng nhập |
| 6 | **Không có timeout phiên** | toàn site | Cookie bị đánh cắp dùng được mãi; máy dùng chung không tự đăng xuất |
| 7 | **Đăng xuất không xóa cookie** | ~30 file gọi `session_destroy()` trần | Phiên phía trình duyệt còn sót lại |
| 8 | **Mật khẩu yếu** (chỉ cần 6 ký tự, `123456` hợp lệ) | đăng ký / đổi / đặt lại mật khẩu | Dễ bị đoán / brute-force |
| 9 | **Hai trang đăng nhập admin** (`dangnhap.php` dự phòng) | `admin/dangnhap.php` | Cửa sau không được gia cố như trang chính |
| 10 | Đổi mật khẩu không đổi session ID; không giới hạn đoán mật khẩu hiện tại | `api_doimatkhau.php` | Session cũ bị lộ vẫn sống; dò mật khẩu qua form đổi MK |

### 3.4. Phương pháp khắc phục

Toàn bộ logic nằm trong **`includes/auth.php`**, được nạp trong `config.php` nên **mọi trang đều được bảo vệ**:

```php
// config.php
require_once BASE_PATH . 'includes/auth.php';
auth_session_enforce();        // kiểm tra vòng đời phiên ở MỌI request
auth_handle_logout_request();  // đăng xuất đúng cách cho toàn site
```

**① Chống brute-force** — bảng `tbl_login_attempts` (lưu DB, xóa cookie cũng không lách được):
* 5 lần sai / tài khoản / 15 phút → khóa 15 phút; 20 lần sai / IP → khóa IP.
* Áp dụng cho: đăng nhập khách hàng, đăng nhập admin, quên mật khẩu (3 lần), dò token reset, đổi mật khẩu.

**② Chống Session Fixation** — `auth_login()` luôn `session_regenerate_id(true)` + đổi CSRF token ngay khi đăng nhập thành công.

**③ Vòng đời phiên** — `auth_session_enforce()`:
* **Idle timeout 30 phút**, **absolute timeout 8 giờ**, **đổi session ID mỗi 15 phút**.
* **Ràng buộc trình duyệt**: lưu hash User-Agent; cookie bị đánh cắp đem sang trình duyệt khác → hủy phiên.
* Hết phiên chỉ bỏ thông tin đăng nhập, **giữ giỏ hàng**.

**④ Đăng xuất an toàn** — `auth_destroy_session()`: `$_SESSION = []` + xóa cookie (`expires` quá khứ) + `session_destroy()`.

**⑤ Lưu trữ mật khẩu & token**
* `auth_verify_password()`: bcrypt + `password_needs_rehash`; mật khẩu cũ plaintext chỉ được chấp nhận khi giá trị lưu **không phải hash** (so sánh `hash_equals`) rồi được hash lại ngay → **không thể đăng nhập bằng chuỗi hash**.
* Đặt lại mật khẩu lưu **bcrypt**; token reset chỉ lưu **SHA-256** trong DB, dùng **1 lần**, hết hạn sau 30 phút.

**⑥ Chính sách mật khẩu** — tối thiểu 8 ký tự, có chữ và số, tối đa 72 byte (giới hạn bcrypt); kiểm tra cả phía server lẫn client.

**⑦ Không tiết lộ thông tin** — đăng nhập luôn báo chung "Sai tài khoản hoặc mật khẩu"; quên mật khẩu luôn trả cùng một thông báo; luôn chạy `password_verify` kể cả khi tài khoản không tồn tại (không lộ qua thời gian phản hồi).

**⑧ Một cửa đăng nhập admin** — `admin/dangnhap.php` chuyển hướng về `admin/canhbao.php`.

> Chạy `database/migration_broken_auth.sql` (hoặc để `auth.php` tự tạo bảng) — migration cũng vô hiệu các token reset cũ đang lưu plaintext.

### 3.5. Quy trình kiểm thử (Testing Guide)

* **Test 3.1 – Brute-force:** Vào `auth/dangnhap.php` (hoặc `admin/canhbao.php`), nhập sai mật khẩu 5 lần cho cùng một tài khoản.
  * *Kết quả:* Lần thứ 6 báo *"Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau 15 phút"* — kể cả nhập đúng mật khẩu. Xem bảng `tbl_login_attempts` trong phpMyAdmin.
* **Test 3.2 – Session Fixation:** F12 → Application → Cookies, ghi lại `PHPSESSID` trước khi đăng nhập; đăng nhập xong xem lại.
  * *Kết quả:* `PHPSESSID` đã đổi sang giá trị khác.
* **Test 3.3 – Cookie flags:** F12 → Application → Cookies → `PHPSESSID`.
  * *Kết quả:* `HttpOnly` ✔, `SameSite=Lax` ✔ (`Secure` ✔ khi chạy HTTPS). Gõ `document.cookie` trong Console không thấy `PHPSESSID`.
* **Test 3.4 – Session Hijacking:** Đăng nhập trên Chrome, copy giá trị `PHPSESSID`, dán vào cookie của Firefox/Edge (trình duyệt khác User-Agent) rồi tải lại trang.
  * *Kết quả:* Không vào được tài khoản, phiên bị hủy, hiện "Phiên đăng nhập không hợp lệ".
* **Test 3.5 – Idle timeout:** Tạm sửa `AUTH_IDLE_TIMEOUT = 60` trong `includes/auth.php`, đăng nhập, chờ hơn 1 phút rồi tải lại.
  * *Kết quả:* Bị đăng xuất, hiện "Phiên đăng nhập đã hết hạn do không hoạt động". (Nhớ trả lại 1800.)
* **Test 3.6 – Đăng xuất:** Đăng nhập → Đăng xuất → bấm nút Back của trình duyệt / dán lại `PHPSESSID` cũ.
  * *Kết quả:* Không vào lại được trang cần đăng nhập.
* **Test 3.7 – Mật khẩu yếu:** Đăng ký với mật khẩu `123456`.
  * *Kết quả:* Bị từ chối, yêu cầu ≥ 8 ký tự gồm chữ và số.
* **Test 3.8 – Dò tài khoản:** Quên mật khẩu với một username không tồn tại và một username có thật.
  * *Kết quả:* Hai lần nhận cùng một thông báo.
* **Test 3.9 – Lưu trữ an toàn:** Đặt lại mật khẩu qua email rồi mở bảng `tbl_tkkhachhang`.
  * *Kết quả:* Cột `password` là chuỗi `$2y$10$...` (bcrypt); khi đang chờ reset, cột `reset_token` là SHA-256, không trùng với token trong link email.

---

## BẢNG TỔNG KẾT SO SÁNH

| Lỗ hổng | Nguy cơ tiềm ẩn | Giải pháp áp dụng | Trạng thái sau khắc phục |
| :--- | :--- | :--- | :--- |
| **Failure to Restrict URL Access** | Khách vãng lai đoán URL vào thẳng trang quản trị hoặc xóa sản phẩm. | Xây dựng `admin_guard.php` kiểm tra Session + Role. Chặn file nhạy cảm qua `.htaccess`. | **ĐÃ AN TOÀN** (100% các file admin được bảo vệ) |
| **SQL Injection (SQLi)** | Kẻ tấn công bypass đăng nhập, đánh cắp mật khẩu, xóa sạch database. | Chuyển sang Prepared Statements (`mysqli_stmt`), ép kiểu số nguyên, whitelist tên cột. | **ĐÃ AN TOÀN** (Vượt qua mọi bài test chèn mã) |
| **Broken Authentication & Session Management** | Brute-force mật khẩu, chiếm phiên (fixation/hijacking), phiên sống vô hạn, lộ mật khẩu/token khi lộ DB, dò tài khoản. | `includes/auth.php`: khóa đăng nhập sai, regenerate ID, idle/absolute timeout, ràng buộc trình duyệt, đăng xuất xóa cookie, bcrypt + token SHA-256, chính sách mật khẩu. | **ĐÃ KHẮC PHỤC** (10 điểm yếu được xử lý) |
