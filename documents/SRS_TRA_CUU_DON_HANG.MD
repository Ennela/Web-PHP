# Software Requirement Specification (SRS)
## Chức năng: Hệ thống Tra cứu và Theo dõi Đơn hàng (Order Tracking)
**Mã chức năng:** ORDER-TRACK-01  
**Trạng thái:** Implemented  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Hệ thống cho phép khách hàng tra cứu đơn hàng qua **2 phương thức**:
1. **Đăng nhập** → xem danh sách đơn hàng cá nhân (`tradonhang.php`) và chi tiết (`chitietdonhang.php`).
2. **Tracking Token** → tra cứu không cần đăng nhập qua link email (`tracking.php?order=XXX&token=YYY`).

### 2. Luồng nghiệp vụ — Tra cứu cá nhân (đăng nhập)
| Bước | Hành động người dùng | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Đăng nhập + truy cập "Tra cứu đơn hàng" | SELECT từ `oder` WHERE `makh` = session. Hiển thị card tóm tắt từng đơn với mã 9 số (`order_code`). |
| 2 | Chọn bộ lọc trạng thái | Truy vấn thêm WHERE `status` = ?. |
| 3 | Nhấn "Xem chi tiết" | `chitietdonhang.php?id=X`. Xác thực quyền sở hữu (`WHERE id=? AND makh=?`). |
| 4 | Xem hành trình + sản phẩm | Timeline trạng thái + bảng chi tiết sản phẩm (ảnh, tên, size, số lượng, giá). |

### 3. Luồng nghiệp vụ — Tracking Token (không đăng nhập)
| Bước | Hành động | Chi tiết |
| :--- | :--- | :--- |
| 1 | Khách mở link từ email xác nhận | `tracking.php?order={order_code}&token={token}` |
| 2 | Server validate | SELECT từ `oder` WHERE `order_code` = ? AND `token` = ?. |
| 3 | Hiển thị thông tin | Trạng thái đơn (badge), trạng thái thanh toán, ngày đặt, tổng tiền, thông tin giao hàng, danh sách sản phẩm. |

### 4. Yêu cầu dữ liệu
* **Mã đơn hàng:** 9 chữ số ngẫu nhiên (`order_code`).
* **Token tracking:** hex string ngẫu nhiên gán khi tạo đơn.
* **Trạng thái:** PENDING, CONFIRMED, SHIPPING, DELIVERED, CANCELLED.
* **Payment status:** UNPAID, PAID, FAILED, REFUNDED.
* **Chi tiết:** `oder_chitiet` JOIN `tbl_qlsanpham` (tensp, anhdaidien, size, quantity, price).

### 5. Ràng buộc kỹ thuật & Bảo mật
* **Authentication (cá nhân):** Bắt buộc đăng nhập. Session `dangnhap` + `makh`.
* **Authorization (IDOR):** `WHERE id = ? AND makh = ?` — người lạ không xem được đơn người khác.
* **Tracking Token (công khai):** Không cần đăng nhập nhưng phải có token hợp lệ.

### 6. Trường hợp ngoại lệ
* **Truy cập ID không thuộc mình:** SweetAlert Toast "Bạn không có quyền xem đơn hàng này!" + redirect.
* **Token sai/link hỏng:** Hiển thị alert "Không tìm thấy đơn hàng hoặc token không đúng."
* **Chưa có đơn hàng:** Hiển thị "Bạn chưa có đơn hàng nào" + nút mua sắm.

### 7. Giao diện (UI/UX)
* **Trang tra cứu cá nhân:** Cards đơn hàng với status badge màu. Bộ lọc trạng thái. Timeline dọc.
* **Trang tracking:** Card responsive hiển thị đầy đủ thông tin đơn + bảng sản phẩm.
* **Status Badge màu:** Vàng (Chờ), Xanh dương (Xác nhận), Xanh dương đậm (Đang giao), Xanh lá (Đã giao), Đỏ (Đã hủy).

### 8. File liên quan
| File | Mô tả |
| :--- | :--- |
| `shop/tradonhang.php` | Danh sách đơn hàng cá nhân (đăng nhập) |
| `shop/chitietdonhang.php` | Chi tiết đơn hàng cá nhân (kiểm tra quyền) |
| `shop/tracking.php` | Tra cứu qua token (công khai) |
