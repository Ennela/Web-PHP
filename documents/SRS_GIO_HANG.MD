# Software Requirement Specification (SRS)
## Chức năng: Giỏ hàng
**Mã chức năng:** CART-01  
**Trạng thái:** Implemented  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Trang Giỏ hàng (`giohang.php`) đóng vai trò trạm trung chuyển lưu trữ tạm thời các mặt hàng giày dép mà khách đang có ý định mua. Dữ liệu giỏ được lưu trên PHP Session với cấu trúc mới bao gồm **size** cho mỗi sản phẩm. Hệ thống tích hợp **AJAX auto-save** (thay đổi số lượng/size tự động lưu không reload), **kiểm tra tồn kho real-time** theo size, và **gợi ý size yêu thích** từ hồ sơ cá nhân.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động người dùng | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Mở trang Giỏ Hàng | Hệ thống migrate session cũ (int) sang format mới (array). Lùng sục `$_SESSION['giohang']` lấy danh sách. |
| 2 | Truy vấn hiển thị | Nối chuỗi ID `SELECT * FROM tbl_qlsanpham WHERE masp IN (...)`. Query tồn kho `tbl_tonkho` cho tất cả sản phẩm trong giỏ. |
| 3 | Thay đổi size bằng dropdown | AJAX POST đến `cart_update_ajax.php`. Server cập nhật session + trả JSON (giá mới, tổng mới). Client cập nhật UI ngay lập tức. |
| 4 | Thay đổi số lượng bằng nút +/- hoặc gõ trực tiếp | Debounced AJAX (500ms) tự động gửi. Kiểm tra tồn kho theo size đã chọn. Cảnh báo nếu vượt stock. |
| 5 | Nhấn nút xóa (icon thùng rác) | Animation slide-out (CSS transition). AJAX xóa `unset($_SESSION['giohang'][$masp])`. |
| 6 | Nhấn "Thanh toán tiền mặt" | POST action=submit, name=dathang → redirect `infodathang.php` (COD). |
| 7 | Nhấn "Thanh toán VNPAY" | POST action=submit, name=thanhtoanonline → redirect `dathangonline.php`. |

### 3. Yêu cầu dữ liệu (Data Requirements)
#### 3.1. Dữ liệu Session (Lưu trữ tạm)
* **Format mới:** `$_SESSION['giohang'][$masp] = ['quantity' => int, 'size' => int|null]`
* **Auto-migration:** Phát hiện format cũ (int) → chuyển sang array tự động.
* **Gợi ý size:** Nếu user đã lưu size yêu thích (bảng `tbl_sizegiay`, hệ EU), hệ thống tự động gán size khi thêm sản phẩm mà chưa chọn size.

#### 3.2. Dữ liệu Hiển thị
* Từ `tbl_qlsanpham`: `tensp`, `giasanpham`, `anhdaidien`.
* Từ `tbl_tonkho`: `masp`, `size`, `soluong` — hiển thị tình trạng tồn kho cho dropdown size (hết hàng = disabled, còn X = cảnh báo).

#### 3.3. AJAX Endpoint
* **`cart_update_ajax.php`**: Nhận JSON `{masp, quantity?, size?, action?}`. Trả `{success, cart: {[masp]: {quantity, size, price, itemTotalFormatted}}, grandTotal, grandTotalFormatted}`.

### 4. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Kích hoạt Session:** `session_start()` ngay dòng đầu.
* **AJAX Auto-save:** Sử dụng `fetch()` + debounce 500ms. Toast indicator cố định góc phải dưới (saving/saved/error).
* **Tồn kho Real-time:** Dropdown size hiển thị "(Hết hàng)" và disabled cho size stock = 0. Cảnh báo inline nếu quantity > stock.
* **Chặn thanh toán:** Nút COD/VNPAY bị disable + `cursor: not-allowed` khi có bất kỳ sản phẩm nào hết hàng, vượt tồn kho, hoặc chưa chọn size. Alert box đỏ hiển thị phía trên nút.
* **Không yêu cầu đăng nhập:** Người dùng ẩn danh vẫn thêm được giỏ hàng. Chỉ bắt đăng nhập khi thanh toán.

### 5. Trường hợp ngoại lệ & Xử lý lỗi (Edge Cases)
* **Giỏ hàng rỗng:** Hiển thị icon shopping-basket + "Bạn chưa có sản phẩm nào" + nút "MUA SẮM NGAY".
* **Sản phẩm hết hàng sau khi thêm vào giỏ:** Dropdown size cập nhật real-time, cảnh báo đỏ inline "Hết hàng". Nút thanh toán tự động disable.
* **Mất kết nối AJAX:** Toast error "Lỗi kết nối" hiển thị 1.8 giây.
* **Giỏ hàng trống khi submit:** Redirect lại giỏ hàng với SweetAlert warning "Giỏ hàng đang trống!".

### 6. Giao diện (UI/UX)
* **Layout Responsive:** CSS Grid 2 cột (desktop: 8/4 - items/summary), stacked trên mobile.
* **Cart Item Grid:** Responsive grid — mobile: 2 hàng (img+name, size+qty, price+delete); desktop: 1 hàng 6 cột.
* **Quantity Controls:** Nút +/- tối thiểu 44px (touch target), input ẩn spin buttons.
* **Size Selector:** Custom dropdown `<select>` với border-radius, icon arrow, hiển thị stock, min-height 44px.
* **Auto-save Status:** Fixed bottom-right toast indicator: spinner khi saving, checkmark khi saved, đỏ khi error.
* **Delete Animation:** `transform: translateX(-30px)`, `opacity: 0`, `max-height: 0` transition.
* **Summary Card:** Rounded card với tạm tính, chiết khấu, phí vận chuyển, TỔNG CỘNG; 2 nút thanh toán full-width.

### 7. Danh sách file liên quan
| File | Loại | Mô tả |
| :--- | :--- | :--- |
| `shop/giohang.php` | Page | Trang giỏ hàng chính |
| `shop/cart_update_ajax.php` | API | AJAX endpoint cập nhật giỏ hàng |
| `includes/inventory_helper.php` | Helper | Hàm kiểm tra/trừ tồn kho |
