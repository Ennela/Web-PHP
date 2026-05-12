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
| **Email** | Brevo (Sendinblue) HTTP API | Gửi email giao dịch (xác nhận đơn hàng, đặt lại mật khẩu, liên hệ) qua REST API. Free tier 300 email/ngày, gửi được đến mọi địa chỉ email mà không cần verify domain. |
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
├── includes/                  # Shared components
│   ├── connect.php            # Kết nối MySQLi đến DB giaythethao2
│   ├── header.php             # Header chung (navbar, meta, CSS imports)
│   ├── footer.php             # Footer chung
│   ├── pagination.php         # Helper phân trang
│   ├── inventory_helper.php   # Quản lý tồn kho: validateAndDeductStock(), restoreStock()
│   ├── order_helper.php       # Sinh mã đơn hàng: generateOrderCode()
│   └── mail_helper.php        # Gửi email qua Brevo API: sendMailBrevo()
│
├── home/                      # Trang giới thiệu (Khách hàng)
│   ├── trangchu.php           # Landing Page, Carousel Banner, Sản phẩm Highlights
│   ├── lienhe.php             # Trang liên hệ (gửi email qua Brevo)
│   └── vechungtoi.php         # Trang giới thiệu về shop
│
├── auth/                      # Xác thực người dùng
│   ├── dangnhap.php           # Đăng nhập khách hàng
│   ├── dangki.php             # Đăng ký tài khoản (có trường email)
│   ├── quenmatkhau.php        # Yêu cầu đặt lại mật khẩu (gửi link qua email)
│   ├── datlamatkhau.php       # Trang đặt lại mật khẩu (token + thời hạn)
│   ├── thongtintaikhoan.php   # Quản lý tài khoản: thông tin, địa chỉ, size giày, đổi MK
│   ├── api_capnhat_thongtin.php  # API cập nhật thông tin cá nhân
│   ├── api_doimatkhau.php     # API đổi mật khẩu
│   ├── api_quenmatkhau.php    # API xử lý quên mật khẩu
│   ├── api_datlamatkhau.php   # API đặt lại mật khẩu mới
│   ├── api_diachi.php         # API CRUD sổ địa chỉ
│   ├── api_sizegiay.php       # API lưu size giày yêu thích
│   └── api_upload_avatar.php  # API upload ảnh đại diện
│
├── shop/                      # Module mua sắm
│   ├── shop.php               # Danh sách sản phẩm, lọc theo danh mục
│   ├── chitietsanpham.php     # Chi tiết SP: gallery, zoom, chọn size, tồn kho
│   ├── giohang.php            # Giỏ hàng AJAX, kiểm tra tồn kho realtime
│   ├── infodathang.php        # Đặt hàng COD (auto-fill thông tin)
│   ├── dathangonline.php      # Đặt hàng VNPAY (tạo link thanh toán)
│   ├── vnpay_return.php       # Xử lý kết quả thanh toán VNPAY
│   ├── tradonhang.php         # Danh sách đơn hàng của user (theo makh)
│   ├── chitietdonhang.php     # Chi tiết + Timeline trạng thái đơn hàng
│   ├── tracking.php           # Tra cứu đơn hàng bằng mã + token (không cần đăng nhập)
│   ├── cart_update_ajax.php   # API cập nhật giỏ hàng
│   ├── api_stock.php          # API kiểm tra tồn kho
│   └── api_order_action.php   # API hủy đơn hàng (phía khách)
│
├── blog/                      # Module tin tức
│   ├── baiviet.php            # Danh sách bài viết
│   └── chitietbaiviet.php     # Chi tiết bài viết
│
├── admin/                     # Trang quản trị (Admin Panel)
│   ├── canhbao.php            # Trang đăng nhập admin (bcrypt + prepared statements)
│   ├── dangnhap.php           # Trang đăng nhập admin (backup)
│   ├── trangchu.php           # Dashboard doanh thu (lọc theo DELIVERED)
│   ├── quanlidonhang.php      # Quản lý đơn hàng, cập nhật trạng thái, in hóa đơn
│   ├── quanlisanpham.php      # Quản lý sản phẩm
│   ├── quanlikhachhang.php    # Quản lý khách hàng
│   ├── quanlibaidang.php      # Quản lý bài viết/tin tức
│   ├── quanlidanhmuc.php      # Quản lý danh mục sản phẩm
│   ├── quanlithanhvien.php    # Quản lý thành viên admin
│   ├── themthanhvien.php      # Tạo thành viên mới (có tài khoản + mật khẩu bcrypt)
│   ├── inhoadon.php           # In hóa đơn đơn hàng
│   └── connect_db.php         # Kết nối DB riêng cho admin
│
├── vnpay_php/                 # Cấu hình VNPAY
│   └── config.php             # Key, secret, return URL của VNPAY Sandbox
│
├── database/                  # File SQL
│   ├── giaythethao2.sql       # Dump toàn bộ CSDL
│   ├── railway_migration.sql  # Migration cho Railway (v1)
│   ├── railway_migration_v2.sql # Migration cho Railway (v2 — dùng file này)
│   ├── migration_tonkho.sql   # Migration thêm bảng tồn kho
│   └── migration_quenmatkhau.sql # Migration thêm trường reset token
│
├── documents/                 # Tài liệu SRS & kiến trúc
├── Dockerfile                 # Cấu hình container cho Railway
└── docker-entrypoint.sh       # Script khởi động: fix MPM, bind PORT động
```

---

## Cơ sở dữ liệu

**Database**: `giaythethao2` — **Engine**: InnoDB — **Charset**: UTF-8

### Các bảng chính

| Bảng dữ liệu | Chức năng |
|---------------|-----------|
| `tbl_tkkhachhang` | Tài khoản khách hàng (username, password bcrypt, email, họ tên, SĐT, địa chỉ, avatar). |
| `tbl_qlsanpham` | Thông tin sản phẩm: tên, giá, ảnh, mô tả, nhóm SP. |
| `tbl_tonkho` | Tồn kho theo từng size của sản phẩm (masp + size + số lượng). |
| `oder` | Đơn hàng: mã đơn (order_code), thông tin KH, tổng tiền, trạng thái (PENDING/CONFIRMED/SHIPPING/DELIVERED/CANCELLED), payment_status, liên kết makh. |
| `oder_chitiet` | Chi tiết đơn hàng: sản phẩm, số lượng, size, giá. Liên kết N-1 với `oder`. |
| `tbl_qlthanhvien` | Tài khoản admin/nhân viên (tài khoản, mật khẩu bcrypt, họ tên, chức vụ). |
| `tbl_qlbaidang` | Bài viết / tin tức. |
| `tbl_danhmuc` | Danh mục sản phẩm. |
| `tbl_diachi` | Sổ địa chỉ của khách hàng (nhiều địa chỉ, chọn mặc định). |
| `tbl_sizegiay` | Size giày yêu thích của khách hàng. |
| `tbl_thuvienanh` | Thư viện ảnh bổ sung của sản phẩm. |

---

## Chức năng nổi bật

### Phía Khách hàng

- **Giao diện Mobile-First:** Toàn bộ trang shop, giỏ hàng, thanh toán được tối ưu hóa hiển thị trên mọi thiết bị với Responsive Grid và Clamp Typography.
- **Đăng ký / Đăng nhập:** Tài khoản khách hàng với mật khẩu mã hóa bcrypt. Đăng ký yêu cầu email để nhận thông báo đơn hàng.
- **Quên mật khẩu:** Gửi link đặt lại mật khẩu qua email (Brevo API), token có thời hạn 1 giờ.
- **Quản lý tài khoản:** Cập nhật thông tin cá nhân, đổi mật khẩu, upload avatar, quản lý sổ địa chỉ (CRUD nhiều địa chỉ, chọn mặc định), lưu size giày yêu thích.
- **Chi tiết sản phẩm:** Gallery nhiều ảnh, Image Zoom theo tọa độ chuột, lightbox xem ảnh lớn, chọn size với hiển thị tồn kho realtime, tự động chọn size yêu thích.
- **Giỏ hàng AJAX:** Thêm/xóa/cập nhật số lượng không tải lại trang, kiểm tra tồn kho realtime, debounce tránh spam.
- **Tự động điền thông tin (Auto-fill Checkout):** Hệ thống nhận diện người dùng đã đăng nhập để điền trước Tên, SĐT, Địa chỉ, Email từ sổ địa chỉ mặc định.
- **Thanh toán COD & VNPAY:** 2 hình thức thanh toán. VNPAY sử dụng HMAC-SHA512 để bảo mật, xử lý duplicate tab, đơn đã hủy, chống race condition bằng MySQL Transaction.
- **Email xác nhận:** Gửi email xác nhận đơn hàng tự động qua Brevo API cho cả COD và VNPAY.
- **Tra cứu đơn hàng:** Xem danh sách đơn hàng cá nhân (theo tài khoản), hoặc tra cứu bằng mã đơn + token (không cần đăng nhập) qua link trong email.
- **Timeline trạng thái:** Theo dõi hành trình đơn hàng qua các trạng thái: Chờ xử lý > Đã xác nhận > Đang giao > Đã giao.
- **Hủy đơn hàng:** Khách hàng có thể tự hủy đơn khi trạng thái còn là "Chờ xử lý".
- **SweetAlert2:** Mọi thông báo đều hiển thị bằng Toast notification chuyên nghiệp.

### Phía Quản trị viên (Admin Panel)

- **Đăng nhập bảo mật:** Sử dụng prepared statements chống SQL injection, mật khẩu mã hóa bcrypt. Hệ thống tự động hash lại mật khẩu cũ khi đăng nhập lần đầu (tương thích ngược).
- **Tạo tài khoản admin:** Chỉ admin đã đăng nhập mới có quyền tạo tài khoản mới (không có trang đăng ký công khai).
- **Dashboard doanh thu:** Thống kê doanh thu thực tế chỉ tính các đơn trạng thái DELIVERED, lọc theo thời gian bằng timestamp `ngaytao`.
- **Quản lý đơn hàng:** Xem chi tiết, đổi trạng thái, in hóa đơn, xem thông tin khách hàng liên kết.
- **Quản lý sản phẩm:** Thêm/sửa/xóa sản phẩm với nhiều ảnh, quản lý tồn kho theo từng size.
- **Quản lý danh mục:** CRUD danh mục sản phẩm.
- **Quản lý khách hàng:** Xem danh sách, thông tin khách hàng.
- **Quản lý bài viết:** Thêm/sửa/xóa bài viết tin tức.
- **Quản lý thành viên:** Thêm/sửa/xóa thành viên admin với tài khoản và mật khẩu (bcrypt).

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
   - `BREVO_API_KEY` — API key từ [Brevo](https://www.brevo.com) (miễn phí 300 email/ngày)
   - `VNPAY_TMN_CODE`, `VNPAY_HASH_SECRET` (nếu dùng VNPAY production)
5. Railway sẽ tự động build từ `Dockerfile` và cấp phát tên miền.
6. **CI/CD:** Mỗi lần `git push`, Railway tự động build & deploy.

---

## Kiến trúc Email

| Thành phần | Chi tiết |
|-----------|---------|
| **Service** | Brevo (Sendinblue) — HTTP REST API |
| **Free tier** | 300 email/ngày, gửi đến mọi email |
| **Helper** | `includes/mail_helper.php` — hàm `sendMailBrevo()` |
| **Nơi sử dụng** | Xác nhận đơn hàng (COD + VNPAY), Quên mật khẩu, Liên hệ |
| **Env variable** | `BREVO_API_KEY` |
| **Timeout** | 10 giây (tránh treo trang) |

---

## Tài liệu SRS

Tất cả tài liệu đặc tả yêu cầu phần mềm (SRS) được lưu trong thư mục `documents/`:

| File | Mô tả |
|------|-------|
| [De_cuong_chuc_nang.md](./documents/De_cuong_chuc_nang.md) | Đề cương chức năng kiến trúc |
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

*Website bán giày thể thao được xây dựng theo tiêu chuẩn Web Responsive, Mobile-First, với hệ thống bảo mật bcrypt, chống Race Condition và SQL Injection.*
