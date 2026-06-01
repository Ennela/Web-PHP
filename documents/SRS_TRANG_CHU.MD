# Software Requirement Specification (SRS)
## Chức năng: Giao diện Trang chủ (Home)
**Mã chức năng:** HOME-UI-01  
**Trạng thái:** Implemented  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Trang chủ (`home/trangchu.php`) đóng vai trò là ấn tượng đầu tiên của khách hàng khi truy cập website. Trang được thiết kế theo phong cách **Editorial-Minimalist** với 5 section chính: Hero Carousel, Editorial Spotlight, Features/USP, Bestseller, và Team Members. Giao diện sử dụng Mobile-First Responsive Design với CSS Clamp Typography.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động người dùng | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Truy cập đường dẫn gốc (domain) | `index.php` redirect → `home/trangchu.php`. Nạp `includes/header.php`. |
| 2 | Xem Hero Carousel | Bootstrap 5 Carousel auto-slide 3 banner: Nike LeBron 7, Air Jordan 1 High, Adidas Superstar. Caption slide-in animation (`translateX`). Nút "Khám phá ngay" link đến chi tiết sản phẩm. |
| 3 | Cuộn xuống Editorial Section | Khối giới thiệu Air Jordan 1 Low 'Paris' — 2 cột (ảnh + mô tả). Ảnh có `hover: translateY(-10px)`. Nút "Sở hữu ngay" gradient blue. |
| 4 | Cuộn xuống Features Section | 4 card USP với icon wrapper tròn (đen → xanh khi hover): Giao hàng, Thanh toán, Bảo hành, Đổi hàng. |
| 5 | Cuộn xuống Bestseller Section | Query `SELECT * FROM tbl_qlsanpham WHERE masp IN (79, 83, 67)`. Hiển thị 3 product card với ảnh, tên, giá format, nút "Xem chi tiết". Nút "Xem tất cả sản phẩm" cuối section. |
| 6 | Cuộn xuống Team Section | 3 team member card: Nguyễn Văn Kiên (CEO), Đỗ Quang Hà (Brand Manager), Nguyễn Bá Nhân (Head of Operations). Ảnh aspect-ratio 1:1, hover scale. Link Facebook. |

### 3. Yêu cầu dữ liệu (Data Requirements)
* **Banner/Carousel:** Dữ liệu cứng (hardcoded) — 3 ảnh tĩnh từ thư mục `images/`.
* **Bestseller:** `SELECT * FROM tbl_qlsanpham WHERE masp IN (79, 83, 67)` — 3 sản phẩm cố định.
* **Team:** Dữ liệu cứng — ảnh từ `images/`, link Facebook tĩnh.

### 4. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Performance:** Ảnh hero sử dụng `object-fit: cover` với `height: clamp(260px, 60vw, 600px)` responsive.
* **Touch Target:** Tất cả nút CTA có `min-height: 44px` hoặc `48px` đạt chuẩn accessibility.
* **Hover vs Touch:** Sử dụng `@media (hover: hover)` để chỉ áp dụng hover effects trên thiết bị hỗ trợ, tránh sticky hover trên mobile.

### 5. Trường hợp ngoại lệ & Xử lý lỗi (Edge Cases)
* **Chưa có sản phẩm bestseller trong CSDL:** Vòng lặp `while` không chạy, section vẫn render nhưng không có card — không gây lỗi.
* **Ảnh banner bị thiếu:** Trình duyệt hiển thị alt text, layout không vỡ nhờ `object-fit`.

### 6. Giao diện (UI/UX)
* **Typography:** Montserrat 800 cho headings, sans-serif 300 cho subheadings. Clamp responsive.
* **Color Palette:** Primary: `#0f172a` (dark navy), `#3b82f6` (blue), `#2563eb` (darker blue). Text: `#64748b` (muted).
* **Animations:** Carousel caption slide-in 0.7s cubic-bezier. Product card hover translateY(-6px). Feature icon bg transition. Team img scale 1.05.
* **Border Radius:** 16px cho cards, 10px cho buttons. Box-shadow subtle `0 4px 20px rgba(0,0,0,0.06)`.
