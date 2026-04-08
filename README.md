# 🏪 Website Bán Giày Thông Minh

Dự án môn học xây dựng một website bán giày thể thao fullstack với PHP thuần, triển khai trên XAMPP. Hệ thống cung cấp đầy đủ chức năng cho **khách hàng** (duyệt sản phẩm, giỏ hàng, đặt hàng, thanh toán VNPAY) và **quản trị viên** (quản lý sản phẩm, đơn hàng, khách hàng, bài viết, thành viên).

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
- Tìm kiếm sản phẩm, Chi tiết sản phẩm, Giỏ hàng, Thanh toán (VNPAY/COD)

### Chức năng hỗ trợ & Thông tin — Nhân
- Trang chủ, Quản lý tài khoản, Quên mật khẩu, Blog/Tin tức, Liên hệ

---

## 📋 Mục lục

- [Công nghệ sử dụng](#-công-nghệ-sử-dụng)
- [Cấu trúc thư mục](#-cấu-trúc-thư-mục)
- [Cơ sở dữ liệu](#-cơ-sở-dữ-liệu)
- [Chức năng chính](#-chức-năng-chính)
- [Hướng dẫn cài đặt](#-hướng-dẫn-cài-đặt)
- [Tài liệu SRS](#-tài-liệu-srs)

---

## 🛠 Công nghệ sử dụng

| Thành phần      | Công nghệ | Lý do lựa chọn |
| --------------- | --------- | -------------- |
| **Ngôn ngữ**    | PHP 8.x (thuần) | Dễ triển khai, cú pháp trực quan, giúp sinh viên nắm vững kiến thức cốt lõi về backend, session và luồng xử lý web động trước khi học Framework. |
| **Database**    | MariaDB 10.4 | Hệ quản trị cơ sở dữ liệu quan hệ mạnh mẽ, mã nguồn mở, tích hợp sẵn trong XAMPP, truy vấn SQL chuẩn và tối ưu tốt với PHP. |
| **Web Server**  | Apache (XAMPP) | Môi trường phát triển cục bộ trọn gói, dễ cài đặt và chạy ngay trên Windows mà không cần tự cấu hình các service rời rạc. |
| **Frontend**    | HTML5, JS, Bootstrap 5 | Chuẩn web hiện đại kết hợp framework CSS phổ biến, hỗ trợ dàn trang (Grid/Flexbox) và responsive UI trên di động nhanh chóng. |
| **Font & Icon** | Montserrat, FontAwesome | Phông chữ hiện đại cùng bộ icon đầy đủ giúp giao diện trở nên chuyên nghiệp, thân thiện và tạo trải nghiệm người dùng tốt hơn. |
| **Thanh toán**  | VNPAY (IPN) | API thanh toán uy tín và sát thực tế, giúp làm quen với luồng thanh toán bảo mật, webhook xác thực giao dịch chuẩn e-Commerce. |
| **Email**       | PHPMailer (SMTP) | Thư viện gửi email ổn định qua Mail Server thực tế (Gmail SMTP), khắc phục hoàn toàn những giới hạn của hàm `mail()` mặc định trên localhost. |
| **Thư viện JS** | BaguetteBox, Vanilla Zoom | Các thư viện thao tác DOM bằng Vanilla JS (không phụ thuộc jQuery), gọn nhẹ, mượt mà, chuyên dùng để hiện thực hiệu ứng xem chi tiết ảnh (zoom/lightbox). |

---

## 📁 Cấu trúc thư mục

```
WEB-PHP/
├── index.php                  # Entry point – redirect đến trang chủ
├── config.php                 # Cấu hình BASE_URL, BASE_PATH
│
├── includes/                  # Shared components
│   ├── connect.php            # Kết nối MySQLi đến DB giaythethao2
│   ├── header.php             # Header + Navbar chung (Bootstrap)
│   ├── pagination.php         # Component phân trang
│   └── fix_db.php             # Script sửa lỗi DB
│
├── home/                      # Trang công khai (Khách hàng)
│   ├── trangchu.php           # Trang chủ – banner, sản phẩm nổi bật
│   ├── vechungtoi.php         # Giới thiệu về cửa hàng
│   └── lienhe.php             # Trang liên hệ
│
├── auth/                      # Xác thực người dùng
│   ├── dangnhap.php           # Đăng nhập khách hàng
│   ├── dangki.php             # Đăng ký tài khoản mới
│   └── thongtintaikhoan.php   # Quản lý thông tin cá nhân
│
├── shop/                      # Module mua sắm
│   ├── shop.php               # Danh sách sản phẩm (grid + phân trang)
│   ├── chitietsanpham.php     # Chi tiết sản phẩm (ảnh, mô tả, giá)
│   ├── giohang.php            # Giỏ hàng (Session-based)
│   ├── infodathang.php        # Form thông tin đặt hàng
│   ├── dathangonline.php      # Xử lý đặt hàng (COD)
│   ├── test_vnpay.php         # Tạo URL thanh toán VNPAY
│   └── vnpay_return.php       # Callback xử lý kết quả VNPAY
│
├── blog/                      # Module tin tức
│   ├── baiviet.php            # Danh sách bài viết
│   └── chitietbaiviet.php     # Chi tiết bài viết
│
├── admin/                     # 🔒 Trang quản trị (Admin Panel)
│   ├── index.php              # Redirect admin
│   ├── trangchu.php           # Dashboard – thống kê tổng quan
│   ├── dangnhap.php           # Đăng nhập admin
│   ├── dangki.php             # Đăng ký tài khoản admin
│   ├── quenmatkhau.php        # Quên mật khẩu admin
│   ├── connect_db.php         # Kết nối DB cho admin
│   ├── function.php           # Hàm tiện ích admin
│   ├── pagination.php         # Phân trang admin
│   ├── inhoadon.php           # In hóa đơn đơn hàng
│   │
│   ├── quanlisanpham.php      # CRUD sản phẩm (danh sách)
│   ├── themsanpham.php        # Thêm sản phẩm mới
│   ├── edit_qlsanpham.php     # Sửa sản phẩm
│   ├── delete_qlsanpham.php   # Xóa sản phẩm
│   │
│   ├── quanlidanhmuc.php      # Quản lý danh mục sản phẩm
│   ├── themdanhmuc.php        # Thêm danh mục
│   │
│   ├── quanlidonhang.php      # Quản lý đơn hàng
│   ├── themdonhang.php        # Tạo đơn hàng thủ công
│   ├── delete_qldonhang.php   # Xóa đơn hàng
│   │
│   ├── quanlikhachhang.php    # Quản lý tài khoản khách hàng
│   ├── themkhachhang.php      # Thêm khách hàng
│   ├── delete_qlkhachhang.php # Xóa khách hàng
│   │
│   ├── quanlithanhvien.php    # Quản lý thành viên (nhân viên)
│   ├── themthanhvien.php      # Thêm thành viên
│   ├── edit_qlthanhvien.php   # Sửa thành viên
│   ├── delete_qlthanhvien.php # Xóa thành viên
│   │
│   ├── quanlibaidang.php      # Quản lý bài đăng (blog)
│   ├── thembaidang.php        # Thêm bài đăng
│   ├── edit_qlbaidang.php     # Sửa bài đăng
│   ├── delete_qlbaidang.php   # Xóa bài đăng
│   │
│   ├── canhbao.php            # Cảnh báo hệ thống
│   ├── gallery_delete.php     # Xóa ảnh thư viện
│   └── uploads/               # Thư mục lưu ảnh upload
│
├── vnpay_php/                 # SDK thanh toán VNPAY
│   ├── config.php             # Cấu hình VNPAY (TmnCode, HashSecret)
│   ├── vnpay_create_payment.php
│   ├── vnpay_return.php
│   ├── vnpay_ipn.php          # Instant Payment Notification
│   ├── vnpay_query.php        # Truy vấn giao dịch
│   └── vnpay_refund.php       # Hoàn tiền giao dịch
│
├── PHPMailer-master/          # Thư viện gửi email (SMTP)
├── assets/                    # Tài nguyên tĩnh
│   ├── bootstrap/             # Bootstrap 5 CSS/JS
│   ├── css/                   # Custom CSS
│   ├── js/                    # JavaScript
│   ├── fonts/                 # FontAwesome, icon fonts
│   └── img/                   # Ảnh giao diện
│
├── images/                    # Ảnh tĩnh (logo, icon mạng xã hội)
├── documents/                 # 📄 Tài liệu SRS & đề cương chức năng
├── giaythethao2.sql           # 💾 File dump cơ sở dữ liệu
└── import_sql.php             # Script tự động import SQL
```

---

## 🗄 Cơ sở dữ liệu

**Database**: `giaythethao2` · **Engine**: InnoDB · **Charset**: UTF-8

### Sơ đồ các bảng

```
┌──────────────────────┐     ┌──────────────────────┐
│   tbl_qlthanhvien    │     │    tbl_dangnhap      │
│ (Thành viên/Admin)   │     │  (Đăng nhập Admin)   │
├──────────────────────┤     ├──────────────────────┤
│ id (PK)              │     │ mavn (PK)            │
│ hoten                │     │ hoten                │
│ gioitinh             │     │ taikhoan             │
│ ngaysinh             │     │ matkhau              │
│ diachicuthe          │     │ ngaysinh             │
│ tinh, thanhpho       │     │ created_time         │
│ phuongxa             │     │ last_updated         │
│ chucvu               │     └──────────────────────┘
│ motacongviec         │
│ taikhoan (UNIQUE)    │
│ matkhau              │
│ ngaytao, ngaycapnhat │
└──────────────────────┘

┌──────────────────────┐     ┌──────────────────────┐
│   tbl_qlsanpham      │────▶│   tbl_thuvienanh     │
│   (Sản phẩm)         │ 1:N │  (Thư viện ảnh SP)   │
├──────────────────────┤     ├──────────────────────┤
│ masp (PK)            │     │ id (PK)              │
│ tensp                │     │ masp (FK)            │
│ anhdaidien           │     │ path                 │
│ anhgiuoithieu1       │     │ ngaytao              │
│ anhgiuoithieu2       │     │ ngaycapnhat          │
│ giasanpham           │     └──────────────────────┘
│ giagoc               │
│ noidung              │
│ nhomsp               │
│ ngaytao, ngaycapnhat │
└──────┬───────────────┘
       │ 1:N
       ▼
┌──────────────────────┐     ┌──────────────────────┐
│    oder_chitiet      │────▶│       oder           │
│ (Chi tiết đơn hàng)  │ N:1 │   (Đơn hàng)         │
├──────────────────────┤     ├──────────────────────┤
│ id (PK)              │     │ id (PK)              │
│ madonhang (FK→oder)  │     │ tenkh                │
│ masp (FK→tbl_qlsp)   │     │ diachi               │
│ quantity             │     │ sdt                  │
│ price                │     │ note                 │
│ created_time         │     │ ngaytao              │
│ last_updated         │     │ tongtien             │
└──────────────────────┘     │ donhangthang         │
                             │ status               │
                             │ vnpay_tranId         │
                             └──────────────────────┘

┌──────────────────────┐     ┌──────────────────────┐
│   tbl_tkkhachhang    │     │   tbl_qlbaidang      │
│ (Tài khoản KH)       │     │  (Bài đăng/Blog)     │
├──────────────────────┤     ├──────────────────────┤
│ makh (PK)            │     │ id (PK)              │
│ username (UNIQUE)    │     │ nguoiphutrach        │
│ password             │     │ tieude               │
│ hoten                │     │ noidung              │
│ ngaytao              │     │ anhdaidien           │
└──────────────────────┘     │ anhgiuoithieu1, 2    │
                             │ chedo (Hiện/Ẩn)      │
                             │ ngaytao, ngaycapnhat │
                             └──────────────────────┘
```

### Quan hệ giữa các bảng

| Quan hệ | Mô tả |
|---|---|
| `oder_chitiet.madonhang` → `oder.id` | Mỗi đơn hàng có nhiều chi tiết sản phẩm (CASCADE) |
| `oder_chitiet.masp` → `tbl_qlsanpham.masp` | Mỗi chi tiết liên kết đến 1 sản phẩm (CASCADE) |
| `tbl_thuvienanh.masp` → `tbl_qlsanpham.masp` | Mỗi sản phẩm có nhiều ảnh phụ (CASCADE) |

---

## ⚡ Chức năng chính

### 👤 Phía Khách hàng (Frontend)

| Chức năng | Mô tả |
|---|---|
| **Trang chủ** | Banner quảng cáo, carousel sản phẩm nổi bật, sản phẩm bán chạy |
| **Cửa hàng** | Xem danh sách sản phẩm dạng grid, phân trang, lọc theo danh mục |
| **Chi tiết SP** | Xem ảnh sản phẩm (zoom), mô tả, giá gốc/giá khuyến mãi |
| **Tìm kiếm** | Thanh tìm kiếm sản phẩm theo từ khóa |
| **Giỏ hàng** | Thêm/sửa/xóa sản phẩm, tính tổng tiền (Session-based) |
| **Đặt hàng** | Form thông tin nhận hàng, ghi chú đơn hàng |
| **Thanh toán COD** | Thanh toán khi nhận hàng |
| **Thanh toán VNPAY** | Thanh toán trực tuyến qua cổng VNPAY (ATM, Visa, QR Code) |
| **Đăng ký/Đăng nhập** | Tạo tài khoản, đăng nhập để quản lý thông tin |
| **Quên mật khẩu** | Khôi phục mật khẩu qua email (PHPMailer) |
| **Blog/Tin tức** | Đọc bài viết về xu hướng giày, mẹo vệ sinh, review |
| **Giới thiệu** | Thông tin về cửa hàng |
| **Liên hệ** | Form gửi liên hệ đến bộ phận CSKH |

### 🔒 Phía Quản trị viên (Admin Panel)

| Chức năng | Mô tả |
|---|---|
| **Dashboard** | Tổng quan thống kê: doanh thu, đơn hàng, sản phẩm bán chạy |
| **Quản lý sản phẩm** | CRUD sản phẩm (ảnh, giá, mô tả, danh mục), upload nhiều ảnh |
| **Quản lý danh mục** | Thêm/sửa nhóm sản phẩm (Giày nam, Giày nữ, ...) |
| **Quản lý đơn hàng** | Xem/sửa trạng thái đơn, in hóa đơn, xóa đơn |
| **Quản lý khách hàng** | Xem danh sách tài khoản KH, thêm/xóa |
| **Quản lý thành viên** | CRUD nhân viên quản trị (phân quyền: Quản lí / Nhân viên) |
| **Quản lý bài đăng** | CRUD bài viết blog, ẩn/hiện bài viết |
| **Đăng nhập Admin** | Hệ thống xác thực riêng biệt cho quản trị viên |

---

## 🚀 Hướng dẫn cài đặt

### Yêu cầu hệ thống

- [XAMPP](https://www.apachefriends.org/) (Apache + MariaDB/MySQL + PHP 8.x)
- Trình duyệt web hiện đại (Chrome, Firefox, Edge)

### Các bước cài đặt

1. **Clone hoặc tải source code** vào thư mục `htdocs` của XAMPP:
   ```bash
   cd C:\xampp\htdocs
   git clone <repository-url> WEB-PHP
   ```

2. **Khởi động XAMPP** → Bật **Apache** và **MySQL**

3. **Tạo database** bằng một trong hai cách:

   - **Cách 1 – phpMyAdmin**: Truy cập `http://localhost/phpmyadmin`, tạo database `giaythethao2` (charset `utf8_general_ci`), sau đó Import file `giaythethao2.sql`

   - **Cách 2 – Script tự động**: Truy cập `http://localhost/WEB-PHP/import_sql.php`

4. **Kiểm tra kết nối DB**: Mở `includes/connect.php`, đảm bảo thông tin phù hợp:
   ```php
   $con = mysqli_connect("127.0.0.1", "root", "", "giaythethao2");
   ```

5. **Truy cập website**:
   - 🌐 Trang khách hàng: `http://localhost/WEB-PHP/`
   - 🔐 Trang quản trị: `http://localhost/WEB-PHP/admin/`

### Tài khoản mặc định

| Vai trò | Tài khoản | Mật khẩu |
|---|---|---|
| Admin (Quản lí) | `noah2005` | `kudo-kun` |
| Nhân viên | `ha` | `ha123` |
| Nhân viên | `nhan` | `nhan123` |
| Khách hàng | `test05` | `kudo-kun` |

### Cấu hình VNPAY (tùy chọn)

Chỉnh sửa file `vnpay_php/config.php` với thông tin Merchant do VNPAY cấp:
- `vnp_TmnCode` – Mã website
- `vnp_HashSecret` – Chuỗi bí mật

---

## 📄 Tài liệu SRS

Tất cả tài liệu đặc tả yêu cầu phần mềm (SRS) được lưu trong thư mục `documents/`:

| File | Mô tả |
|---|---|
| `De_cuong_chuc_nang.md` | Đề cương chức năng tổng quan toàn hệ thống |
| `SRS_TRANG_CHU.MD` | Đặc tả trang chủ |
| `SRS_DANG_KY.MD` | Đặc tả chức năng đăng ký |
| `SRS_DANG_NHAP.MD` | Đặc tả chức năng đăng nhập |
| `SRS_DANG_XUAT.MD` | Đặc tả chức năng đăng xuất |
| `SRS_QUAN_LY_TAI_KHOAN.MD` | Đặc tả quản lý tài khoản |
| `SRS_QUEN_MAT_KHAU.MD` | Đặc tả quên mật khẩu |
| `SRS_TIM_KIEM_SAN_PHAM.MD` | Đặc tả tìm kiếm sản phẩm |
| `SRS_CHI_TIET_SAN_PHAM.MD` | Đặc tả chi tiết sản phẩm |
| `SRS_GIO_HANG.MD` | Đặc tả giỏ hàng |
| `SRS_DAT_HANG.MD` | Đặc tả đặt hàng |
| `SRS_THANH_TOAN.MD` | Đặc tả thanh toán (VNPAY/COD) |
| `SRS_BLOG_TIN_TUC.MD` | Đặc tả blog/tin tức |
| `SRS_LIEN_HE.MD` | Đặc tả trang liên hệ |
| `SRS_ADMIN_DASHBOARD.MD` | Đặc tả dashboard admin |
| `SRS_ADMIN_QL_SAN_PHAM.MD` | Đặc tả quản lý sản phẩm |
| `SRS_ADMIN_QL_DANH_MUC.MD` | Đặc tả quản lý danh mục |
| `SRS_ADMIN_QL_DON_HANG.MD` | Đặc tả quản lý đơn hàng |
| `SRS_ADMIN_QL_KHACH_HANG.MD` | Đặc tả quản lý khách hàng |
| `SRS_ADMIN_QL_TIN_TUC.MD` | Đặc tả quản lý tin tức |

