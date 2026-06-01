# Software Requirement Specification (SRS)
## Chức năng: Chi tiết Sản phẩm
**Mã chức năng:** PRODUCT-DETAIL-01  
**Trạng thái:** Implemented  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Trang chi tiết sản phẩm (`chitietsanpham.php?masp=X`) hiển thị thông tin đầy đủ của 1 đôi giày: bộ sưu tập ảnh, mô tả chi tiết, bảng giá, và hệ thống chọn size với **tồn kho real-time** từ bảng `tbl_tonkho`. Tích hợp nút "Thêm vào giỏ" với gợi ý size yêu thích.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động người dùng | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Click sản phẩm từ Shop/Bestseller/Carousel | Chuyển đến `chitietsanpham.php?masp=X`. SELECT sản phẩm + ảnh phụ + tồn kho tất cả size. |
| 2 | Xem ảnh sản phẩm | Gallery ảnh: ảnh đại diện lớn + album ảnh phụ thu nhỏ. Click thumbnail → đổi ảnh chính. |
| 3 | Chọn size | Grid các nút size (36-44). Hiển thị tồn kho: xanh (còn hàng), xám disabled (hết). Nếu đã đăng nhập và lưu size yêu thích → tự động highlight. |
| 4 | Chọn số lượng + "Thêm vào giỏ" | Validate: size đã chọn? stock >= quantity? AJAX thêm vào `$_SESSION['giohang']` với size. SweetAlert success toast. |

### 3. Yêu cầu dữ liệu
* **Sản phẩm:** `tbl_qlsanpham` (tensp, giasanpham, mota, anhdaidien, masp, madm).
* **Ảnh phụ:** `tbl_hinhanh` (masp, hinhanh) — nhiều ảnh cho 1 sản phẩm.
* **Tồn kho:** `tbl_tonkho` (masp, size, soluong) — real-time stock per size.
* **Size yêu thích:** `tbl_sizegiay` (makh, he_size='EU', size_value) — gợi ý cho user đã login.

### 4. Ràng buộc kỹ thuật & Bảo mật
* **masp Validation:** `intval()` cho parameter.
* **Tồn kho real-time:** Query `tbl_tonkho` mỗi lần load trang, hiển thị chính xác stock per size.
* **XSS:** `htmlspecialchars()` cho tất cả output.

### 5. Trường hợp ngoại lệ
* **masp không tồn tại:** Hiển thị thông báo "Sản phẩm không tồn tại".
* **Tất cả size hết hàng:** Nút "Thêm vào giỏ" disable.
* **Chưa chọn size:** SweetAlert warning "Vui lòng chọn size trước khi thêm vào giỏ".
* **Vượt tồn kho:** Giới hạn max quantity = stock của size đã chọn.

### 6. Giao diện (UI/UX)
* 2 cột: Gallery ảnh (trái) + Thông tin (phải). Stacked trên mobile.
* Size grid: Nút bo tròn, border active = primary blue, disabled = xám.
* Quantity +/- controls, min-height 44px touch target.
* Nút "Thêm vào giỏ" gradient blue, uppercase, icon shopping bag.
