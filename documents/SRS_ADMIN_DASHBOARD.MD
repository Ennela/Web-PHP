# Software Requirement Specification (SRS)
## Chức năng: Admin Dashboard (Tổng quan)
**Mã chức năng:** ADMIN-DASH-01  
**Trạng thái:** Draft / Review  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Hệ thống cung cấp một bảng điều khiển trung tâm (Dashboard) dành riêng cho Quản trị viên tại đường dẫn `admin/trangchu.php`. Chức năng này giúp Admin theo dõi các chỉ số cơ bản như số lượng đơn hàng đã đặt, số lượng sản phẩm đang bán, tổng số thành viên và số lượng bài viết.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động Admin | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Truy cập vào `admin/index.php` hoặc `admin/trangchu.php` | Hệ thống kiểm tra session đăng nhập admin (`$_SESSION['admin']`). |
| 2 | Đăng nhập thành công | Hiển thị giao diện trang chủ Admin. |
| 3 | Xem các thẻ tóm tắt số liệu (Cards) | Hệ thống thực thi các câu truy vấn `COUNT` từ các bảng `oder`, `tbl_qlsanpham`, `tbl_tkkhachhang` để in con số. |

### 3. Yêu cầu dữ liệu (Data Requirements)
#### 3.1. Dữ liệu đối chiếu
* **Bảng Đơn hàng (`oder`):** `SELECT COUNT(id) FROM oder;`
* **Bảng Khách hàng (`tbl_tkkhachhang`):** `SELECT COUNT(makh) FROM tbl_tkkhachhang;`
* **Bảng Sản phẩm (`tbl_qlsanpham`):** `SELECT COUNT(masp) FROM tbl_qlsanpham;`

### 4. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Xác thực phiên làm việc:** Bắt buộc phải có session admin tồn tại, nếu không sẽ tự động redirect về trang đăng nhập.

### 5. Trường hợp ngoại lệ & Xử lý lỗi (Edge Cases)
* **Trường hợp:** CSDL bị rỗng (chưa có đơn hàng hay khách hàng).
  * **Xử lý:** Các câu truy vấn `COUNT` trả về 0 và hiển thị bình thường, không gây lỗi logic (Exception).

### 6. Giao diện (UI/UX)
* Thiết kế sử dụng các khối thẻ màu sắc nổi bật (Blue, Green, Red, Yellow) để làm nổi bật các con số thống kê.
