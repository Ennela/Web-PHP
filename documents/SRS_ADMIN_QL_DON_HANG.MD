# Software Requirement Specification (SRS)
## Chức năng: Quản lý Đơn hàng
**Mã chức năng:** ADMIN-ORDER-01  
**Trạng thái:** Draft / Review  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Quản trị viên sử dụng hệ thống `quanlidonhang.php` để giám sát danh sách các đơn đặt hàng được đặt từ website, theo dõi thông tin người nhận, cước phí và chi tiết từng mặt hàng. Hệ thống liên kết giữa bảng `oder` và `oder_chitiet`.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động Admin | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Truy cập `quanlidonhang.php` | Hệ thống `SELECT * FROM oder ORDER BY id DESC` và hiển thị trên bảng. |
| 2 | Nhấn dòng In / Xem đơn | Mở trang `inhoadon.php` chứa thông tin chi tiết bảng `oder_chitiet` (Sản phẩm, số lượng, giá tiền). |
| 3 | Nút Xóa đơn hàng | Gửi lệnh `DELETE FROM oder WHERE id = ?`. Dữ liệu tại `oder_chitiet` tự động bị xóa theo nhờ ràng buộc khóa ngoại (CASCADE). |

### 3. Yêu cầu dữ liệu (Data Requirements)
#### 3.1. Dữ liệu Hiển thị (Data Output)
* **Bảng Đơn hàng (`oder`):** `id`, `tenkh`, `sdt`, `diachi`, `note`, `ngaytao`, `tongtien`, `status`.
* **Bảng Chi tiết (`oder_chitiet`):** Ánh xạ từ `masp` sang bảng `tbl_qlsanpham` để lấy tên và giá gốc tại thời điểm mua (`price`, `quantity`).

### 4. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Khóa ngoại Cascade:** Hệ thống sử dụng ràng buộc xóa xếp tầng `ON DELETE CASCADE` nên chỉ cần xóa ID của hóa đơn mẹ ở bảng `oder`, các dòng con ở `oder_chitiet` sẽ tự loại bỏ, tránh rác DB.

### 5. Trường hợp ngoại lệ & Xử lý lỗi (Edge Cases)
* **Trường hợp:** Đơn hàng bị lỗi hoặc thông tin rác.
  * **Xử lý:** Admin sẽ xem và thao tác xóa thủ công (Thực thi chuỗi `delete_qldonhang.php?id=`).

### 6. Giao diện (UI/UX)
* Giao diện dạng Data Table đơn giản có hỗ trợ nút In hóa đơn (`inhoadon.php`) với bố cục sẵn sàng ra giấy A4 đơn giản cho máy in nhiệt hoặc PDF.
