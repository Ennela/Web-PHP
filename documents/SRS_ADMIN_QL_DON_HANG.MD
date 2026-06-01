# Software Requirement Specification (SRS)
## Chức năng: Quản lý Đơn hàng
**Mã chức năng:** ADMIN-ORDER-01  
**Trạng thái:** Implemented  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Quản trị viên sử dụng hệ thống `quanlidonhang.php` để giám sát danh sách các đơn đặt hàng, cập nhật trạng thái đơn hàng trực tiếp bằng dropdown, xem chi tiết sản phẩm bằng AJAX inline, và in hóa đơn. Hệ thống tích hợp **quản lý tồn kho tự động** — hoàn kho khi hủy đơn, trừ lại khi khôi phục đơn đã hủy.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động Admin | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Truy cập `quanlidonhang.php` | Kiểm tra `$_SESSION['dangnhap1']`. SELECT từ `oder` ORDER BY id DESC, phân trang 6 đơn/trang. |
| 2 | Lọc đơn hàng | Form tìm kiếm: ID đơn, tên khách hàng (LIKE), trạng thái (dropdown). |
| 3 | Thay đổi trạng thái bằng dropdown | `<select onchange="this.form.submit()">` → POST action=update_status. Hệ thống xử lý logic tồn kho tự động. |
| 4 | Xem chi tiết đơn hàng | Nút "Xem" toggle expand row → AJAX fetch `ajax_order_detail.php?id=X` → hiển thị bảng sản phẩm (ảnh, tên, size, số lượng, giá). |
| 5 | In hóa đơn | Link `inhoadon.php?id=X` mở tab mới với layout A4 sẵn sàng in. |

### 3. Order Flow & Quản lý Tồn kho
| Chuyển trạng thái | Xử lý tồn kho |
| :--- | :--- |
| Bất kỳ → CANCELLED | `restoreStock()` — hoàn lại tồn kho cho tất cả size/sản phẩm. Nếu đã thanh toán → `payment_status = 'REFUNDED'`. |
| CANCELLED → Trạng thái khác | `validateAndDeductStock()` — kiểm tra + trừ tồn kho. Nếu không đủ → hiển thị lỗi, không cho chuyển. |
| Bất kỳ → DELIVERED | Tự động `payment_status = 'PAID'` nếu đang UNPAID. |
| Trạng thái khác → khác | UPDATE `status` trực tiếp. |

### 4. Yêu cầu dữ liệu (Data Requirements)
* **Bảng `oder`:** `id`, `order_code` (mã đơn 9 số), `tenkh`, `sdt`, `diachi`, `email`, `note`, `ngaytao`, `tongtien`, `status`, `payment_status`, `token`.
* **Bảng `oder_chitiet`:** `madonhang`, `masp`, `size`, `quantity`, `price`.
* **Trạng thái:** PENDING, CONFIRMED, SHIPPING, DELIVERED, CANCELLED.
* **Payment status:** UNPAID, PAID, FAILED, REFUNDED.

### 5. Ràng buộc kỹ thuật & Bảo mật
* **Session Admin:** `$_SESSION['dangnhap1']` bắt buộc, redirect `canhbao.php` nếu thiếu.
* **Inventory Helper:** `includes/inventory_helper.php` — validateAndDeductStock, restoreStock sử dụng MySQL Transaction + SELECT...FOR UPDATE.
* **AJAX Chi tiết:** `ajax_order_detail.php` trả HTML fragment chứa bảng sản phẩm.

### 6. Trường hợp ngoại lệ
* **Không đủ tồn kho khi khôi phục đơn hủy:** `$_SESSION['admin_error']` hiển thị alert đỏ chi tiết sản phẩm/size nào thiếu.
* **Đơn hàng rỗng:** COUNT trả 0, bảng vẫn render header, không lỗi.

### 7. Giao diện (UI/UX)
* Bảng dữ liệu (HTML Table) với cột: Mã đơn (xanh), Tên KH, SĐT, Địa chỉ, Tổng tiền (xanh đậm), Ngày tạo, Trạng thái (badge màu), Cập nhật, Chi tiết, In.
* Status badge: vàng (Chờ), xanh dương (Xác nhận), tím (Đang giao), xanh lá (Đã giao), đỏ (Hủy).
* Chi tiết đơn hàng: expand row inline, AJAX load, bảng sản phẩm với ảnh thumbnail, size tag đen.
* Responsive: table scroll horizontal trên mobile, form search stack vertical.
