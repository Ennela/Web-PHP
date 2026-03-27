# Software Requirement Specification (SRS)
## Chức năng: Giỏ hàng
**Mã chức năng:** CART-01  
**Trạng thái:** Draft / Review  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Trang Giỏ hàng (`giohang.php`) đóng vai trò trạm trung chuyển lưu trữ tạm thời các mặt hàng giày dép mà khách đang có ý định mua. Dữ liệu giỏ được lưu trên RAM của máy chủ thông qua mảng mảng PHP Session thay vì lưu cơ sở dữ liệu để tiết kiệm tài nguyên. Khách hàng có thể Tăng/giảm số lượng, Xóa một món hoặc Xóa toàn bộ giỏ.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động người dùng | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Mở trang Giỏ Hàng | Hệ thống lùng sục mảng `$_SESSION['chuyen']` lấy danh sách ID kiện hàng. |
| 2 | Truy vấn hiển thị | Nối chuỗi ID (`implode(",", array_keys)`) làm tham số nạp vào mệnh đề `SELECT * FROM tbl_qlsanpham WHERE masp IN (...)`. |
| 3 | Sửa đổi số lượng gõ bằng tay rồi ấn "Update" | POST một cục mảng mảng mới đè vào biến Session tương ứng làm thay đổi tổng tiền tạm định. |
| 4 | Nhấn nút "Xóa" cạnh tên đồ | Chạy action delete kích hoạt `unset($_SESSION['chuyen'][$id])`. |

### 3. Yêu cầu dữ liệu (Data Requirements)
#### 3.1. Dữ liệu Session (Lưu trữ tạm)
* Mảng khóa-giá trị, ví dụ: `$_SESSION['chuyen'] = array( '1' => 2, '5' => 1 )` hiểu là "Mua Sản phẩm số 1 (2 cái) và Sản phẩm số 5 (1 cái)".

#### 3.2. Dữ liệu Hiển thị 
* Lấy `tensp`, `giasanpham`, `anhdaidien` từ DB ra bảng tính tổng nội bộ để in chi phí hóa đơn.

### 4. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Kích hoạt Session:** Trang nào liên quan tới Giỏ Hàng buộc phải khai báo hàm `session_start();` ngay từ dòng thứ 1 để không bị lỗi header. 
* **Session Expiry:** Mảng bị dập tắt (đóng) tự động khi đóng cấu trúc tab trình duyệt. Hoặc reset khi thực hiện thanh toán xong.

### 5. Trường hợp ngoại lệ & Xử lý lỗi (Edge Cases)
* **Trường hợp:** Giỏ hàng bị bỏ trống, hoặc người dùng xóa sạch các món.
  * **Xử lý:** Mảng sẽ không có keys nào, phát sinh lỗi truy vấn IN(). Trang `giohang.php` buộc phải có hàm IF dò điều kiện mảng `!empty()` để ngắt lệnh SQL và In ra dòng chữ "Giỏ hàng rỗng".

### 6. Giao diện (UI/UX)
* Khung Table kẻ bảng có Input Text để điền số thực. Dưới gầm bảng là Nút Tổng kết Thanh Toán / Đặt Hàng điều hướng sang Checkout.
