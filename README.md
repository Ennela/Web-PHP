# 🏪 Website Bán Giày Thông Minh

Dự án môn học xây dựng một website bán giày thể thao fullstack với PHP thuần, triển khai trên XAMPP. Hệ thống cung cấp đầy đủ chức năng cho **khách hàng** (duyệt sản phẩm, giỏ hàng, đặt hàng, tra cứu lịch sử mua hàng cá nhân, thanh toán VNPAY) và **quản trị viên** (quản lý sản phẩm với thuộc tính size, đơn hàng, khách hàng, bài viết, thành viên). 

Vừa qua, dự án đã được cập nhật lớn về mặt giao diện thiết kế (Brutalist-Minimalist trên các trang chính) cùng nâng cấp logic doanh thu, liên kết người dùng và tuỳ biến chi tiết sản phẩm.

---

## 👥 Thành viên

- **Lớp**: D18CNPM2

| STT | Họ và tên | Mã sinh viên |
|---|---|---|
| 1 | Nguyễn Văn Kiên | 23810310138 |
| 2 | Đỗ Quang Hà | 23810310132 |
| 3 | Nguyễn Bá Nhân | 23810310144 |

---

## 📝 Phân công công việc

### Hệ thống Quản trị (Admin) — Hà
- Dashboard, Quản lý Danh mục, Đơn hàng, Khách hàng, Sản phẩm, Tin tức

### Giao diện mua hàng (Shop) — Kiên
- Tìm kiếm sản phẩm, Chi tiết sản phẩm, Giỏ hàng, Đặt hàng Online, Tra cứu Đơn hàng cá nhân, Thanh toán (VNPAY/COD)

### Chức năng hỗ trợ & Thông tin — Nhân
- Trang chủ (Banner Carousel), Quản lý tài khoản, Quên mật khẩu, Blog/Tin tức, Liên hệ

---

## 📋 Mục lục

- [Công nghệ sử dụng](#-công-nghệ-sử-dụng)
- [Cấu trúc thư mục](#-cấu-trúc-thư-mục)
- [Cơ sở dữ liệu](#-cơ-sở-dữ-liệu)
- [Chức năng nổi bật](#-chức-năng-nổi-bật)
- [Hướng dẫn cài đặt](#-hướng-dẫn-cài-đặt)
- [Tài liệu SRS](#-tài-liệu-srs)

---

## 🛠 Công nghệ sử dụng

| Thành phần      | Công nghệ | Lý do lựa chọn |
| --------------- | --------- | -------------- |
| **Ngôn ngữ**    | PHP 8.x (thuần) | Dễ triển khai, cú pháp trực quan, giúp sinh viên nắm vững kiến thức cốt lõi về backend, session và luồng xử lý web động trước khi học Framework. |
| **Database**    | MariaDB 10.4 | Hệ quản trị cơ sở dữ liệu quan hệ mạnh mẽ, mã nguồn mở, tích hợp sẵn trong XAMPP. |
| **Web Server**  | Apache (XAMPP) | Môi trường phát triển cục bộ trọn gói, thao tác khởi động dịch vụ web thuận tiện cho Windows. |
| **Frontend**    | HTML5, Vanilla JS, CSS/Bootstrap | Áp dụng xu hướng thiết kế "Editorial/Brutalist" tân tiến trên các trang shop/cart/thanh toán, kết hợp tuỳ biến linh hoạt hiệu ứng micro-animations. |
| **Font & Icon** | Montserrat, FontAwesome | Phông chữ hiện đại cùng bộ icon đầy đủ giúp giao diện trở nên chuyên nghiệp, thân thiện. |
| **Thanh toán**  | VNPAY (IPN) | API thanh toán uy tín và sát thực tế, giúp làm quen với luồng thanh toán bảo mật, webhook xác thực giao dịch chuẩn e-Commerce. |
| **Email**       | Resend HTTP API | Tích hợp gửi email bằng API HTTP thay vì SMTP truyền thống (PHPMailer), giải quyết triệt để lỗi block cổng outbound (Port 25/465/587) trên các nền tảng Cloud (Railway), giúp luồng thanh toán không bị treo. |
| **Thông báo UI**| SweetAlert2 | Hiển thị thông báo (Toast Notifications) không chặn màn hình, thay thế hoàn toàn `alert()` mặc định của trình duyệt, nâng cao trải nghiệm người dùng (UX). |
| **Triển khai**  | Docker & Railway | Đóng gói ứng dụng linh hoạt, tự động bind cổng và biến môi trường, tối ưu hoá quá trình CI/CD và đưa website lên mạng chạy trực tuyến (Cloud Serverless). |
| **Thư viện Ảnh**| Custom Image Zoom | Thay thế các thư viện rườm rà bằng hiệu ứng di chuột (hover/mousemove zoom gallery) chính xác, tương thích mượt mà cho ảnh SP. |

---

## 📁 Cấu trúc thư mục

```
WEB-PHP/
├── config.php                 # Cấu hình BASE_URL, BASE_PATH
├── includes/                  # Shared components
│   └── connect.php            # Kết nối MySQLi đến DB giaythethao2
│
├── home/                      # Trang đánh giới thiệu (Khách hàng)
│   ├── trangchu.php           # Landing Page hiện đại, Carousel Banner, Sản phẩm Highlights
│
├── auth/                      # Xác thực người dùng
│   ├── dangnhap.php           # Đăng nhập & bảo mật Session KH
│
├── shop/                      # Module mua sắm & Tài khoản KH
│   ├── shop.php               # Bố cục UI Grid cao cấp lọc sản phẩm
│   ├── chitietsanpham.php     # Slider đa ảnh, Custom UI chọn Size
│   ├── giohang.php            # Giỏ hàng, kết nối AJAX 
│   ├── infodathang.php        # Form đặt hàng tự điền (Auto-fill profiles)
│   ├── tradonhang.php         # Quản lý danh sách mọi đơn hàng của user [TÍNH NĂNG MỚI]
│   └── chitietdonhang.php     # Tracking Timeline đơn hàng theo Status [TÍNH NĂNG MỚI]
│
├── admin/                     # 🔒 Trang quản trị (Admin Panel)
│   ├── trangchu.php           # Dashboard doanh thu thực (Lọc theo ngaytao/DELIVERED)
│   ├── quanlidonhang.php      # Thay đổi quy trình đơn, cập nhật Tracking.
│   ├── quanlisanpham.php      # Thêm mặt hàng kèm mảng Sizes/Biến thể.
│
├── vnpay_php/                 # API tích hợp cổng thanh toán Sandbox VNPAY
├── vendor/                    # Các thư viện Composer (bao gồm Resend API)
├── Dockerfile                 # File cấu hình container triển khai Railway
└── documents/                 # 📄 Tài liệu SRS & kiến trúc đề cương yêu cầu
```

---

## 🗄 Cơ sở dữ liệu

**Database**: `giaythethao2` · **Engine**: InnoDB · **Charset**: UTF-8

### Sơ đồ các bảng liên hệ nghiệp vụ (Lọc điểm chính)

| Bảng dữ liệu | Chức năng nổi bật |
| --- | --- |
| `tbl_tkkhachhang` | Lưu giữ định danh `makh`. |
| `tbl_qlsanpham` | Chứa dữ liệu danh mục, cấu trúc mô tả sản phẩm. |
| `oder` | Đã liên kết với `makh` (Khách hàng). Thay đổi tính giá trị doanh thu bằng trường `ngaytao` (Timestamp sinh lời thực) thay vì mốc tháng tĩnh. |
| `oder_chitiet` | Liên kết N-1 với đơn hàng (`madonhang`). Gặp sự bổ sung thuộc tính **`size`** – đáp ứng cá nhân hóa theo từng biến thể sản phẩm bán ra. |

---

## ⚡ Chức năng nổi bật (Mới cập nhật)

### 👤 Phía Khách hàng (Frontend)

- **Giao diện Cao Cấp (Premium UI) & Mobile-First:** Trang bán hàng (Shop, Cart, Order) được viết lại theo phong cách tối giản, sử dụng các gradient mượt, thẻ sản phẩm thiết kế góc bo hiện đại và tối ưu hóa hiển thị trên mọi thiết bị di động (Responsive Grid, Clamp Typography).
- **Trải nghiệm thông báo mượt mà:** Ứng dụng SweetAlert2 cho mọi luồng thao tác (Thêm giỏ hàng, Đăng nhập, Thanh toán), cung cấp các Toast notifications chuyên nghiệp thay vì cảnh báo gián đoạn của trình duyệt.
- **Tự động điền dữ liệu (Auto-fill Checkout):** Hệ thống tự động nhận diện người dùng đã đăng nhập để điền trước các thông tin thanh toán (Tên, SĐT, Địa chỉ, Email), giảm thiểu thời gian hoàn tất đơn hàng.
- **Hero Carousel Banner:** Vòng lặp banner chuyển động trực quan giúp trang chủ (Landing Area) trông sinh động, chuyên nghiệp hơn.
- **Tùy chọn Cỡ (Size):** Người dùng khi chọn mua sản phẩm bắt buộc phải chọn cỡ chuẩn tại trang chi tiết sản phẩm trước khi mua đồ.
- **Image Zoom Tracker:** Tích hợp logic tuỳ chỉnh xử lý hiệu ứng Zoom theo toạ độ chuột trực tiếp trên chi tiết sản phẩm. Không phụ thuộc nặng nề vào các dependencies bên thứ ba.
- **Tracking Hành trình (Order Timeline):** Khi đăng nhập, khách hàng có thể theo sát kiện hàng tiến triển qua thẻ "Timeline báo hiệu" (Từ Chờ xét duyệt -> Xác Nhận -> Giao Xong). Việc tra cứu được mã hoá chặt chẽ chốt ID `makh`.

### 🔒 Phía Quản trị viên (Admin Panel)

- **Dashboard Doanh thu thông minh:** Thay vì lưu và đối soát doanh số qua một file tĩnh hay tháng cưng nhắc, logic được quy về việc query đếm tổng `tongtien` từ bảng `oder` được trích xuất bằng timestamp `ngaytao`. Khẳng định số liệu báo trước mặt Admin chỉ cộng gộp cho các đơn đạt trạng thái `DELIVERED` (Đã giao tận tay).
- **Hỗ trợ Admin Sizes:** Các biểu mẫu quản lý / thêm sửa sản phẩm, cập nhật form sửa đơn hàng để theo dõi Size chân cụ thể của khách mà không phải dựa vào mô tả chung.

---

## 🚀 Hướng dẫn cài đặt & Triển khai

### 💻 Môi trường Local (XAMPP)
1. **Clone mã nguồn** vào `C:\xampp\htdocs\WEB-PHP`.
2. Chạy lệnh `composer install` để cài đặt các thư viện phụ thuộc (Resend API).
3. **Khởi động XAMPP (Apache + MySQL)**.
4. **Database:** Import CSDL từ file `railway_migration_v2.sql`.
5. Cấu hình biến môi trường (mã nguồn ưu tiên đọc file `.env` nếu có) hoặc sửa trực tiếp trong `includes/connect.php` và `config.php`.
6. **Truy cập:**
   - 🌐 Trang khách hàng: `http://localhost/WEB-PHP/` (Tài khoản mẫu: `test05` / `kudo-kun`)
   - 🔐 Trang quản trị: `http://localhost/WEB-PHP/admin/` (Tài khoản Admin: `noah2005` / `kudo-kun`)

### ☁️ Triển khai lên Railway (Cloud)
Dự án đã được cấu hình sẵn `Dockerfile` để triển khai tự động lên Railway:
1. Khởi tạo dự án trên Railway.
2. Thêm dịch vụ MySQL, thực thi file `railway_migration_v2.sql` để khởi tạo cấu trúc dữ liệu.
3. Liên kết kho lưu trữ GitHub chứa dự án vào dịch vụ Web trên Railway.
4. Cấu hình các biến môi trường (Environment Variables) trên Web service:
   - `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`, `DB_PORT`
   - `RESEND_API_KEY`
5. Railway sẽ tự động build từ `Dockerfile` và cấp phát tên miền.

---

## 📄 Tài liệu SRS

Tất cả tài liệu đặc tả yêu cầu phần mềm (SRS) được lưu trong thư mục `documents/`:

| File | Mô tả |
|---|---|
| [`De_cuong_chuc_nang.md`](./documents/De_cuong_chuc_nang.md) | Đề cương chức năng kiến trúc |
| [`SRS_ADMIN_DASHBOARD.MD`](./documents/SRS_ADMIN_DASHBOARD.MD) | Đặc tả Dashboard & Báo cáo doanh thu thời gian thực |
| [`SRS_ADMIN_QL_SAN_PHAM.MD`](./documents/SRS_ADMIN_QL_SAN_PHAM.MD) | Đặc tả Quản lý Sản phẩm / Kích cỡ Size |
| [`SRS_CHI_TIET_SAN_PHAM.MD`](./documents/SRS_CHI_TIET_SAN_PHAM.MD) | Đặc tả Giao diện View SP, Chọn Cỡ & Hover Zoom |
| [`SRS_DAT_HANG.MD`](./documents/SRS_DAT_HANG.MD) | Đặc tả Khâu Đặt hàng liên kết tài khoản KH ẩn danh/cá nhân hoá |
| [`SRS_TRA_CUU_DON_HANG.MD`](./documents/SRS_TRA_CUU_DON_HANG.MD) | (MỚI) Đặc tả Tracking hành trình kiên hàng |
| Và các tài liệu khác... | ... (Tồn tại trong `/documents`) |

*Website bán giày được củng cố theo tiêu chuẩn Web Responsive - Cung cấp định nghĩa bảo mật hiện vật an toàn, trải nghiệm người dùng tuyệt hảo.*
