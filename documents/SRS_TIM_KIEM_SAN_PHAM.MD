# Software Requirement Specification (SRS)
## Chức năng: Tìm kiếm Sản phẩm
**Mã chức năng:** SEARCH-01  
**Trạng thái:** Draft / Review  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Hệ thống thiết lập một thanh Input Tìm Kiếm ở khu vực Header (Thanh điều hướng chung của `header.php`) cho phép tra cứu mẫu giày qua ký tự Tên sản phẩm. Phản hồi tải lại trang và bóc tách các dòng bị dính chuỗi.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động người dùng | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Gõ Text vào ô thanh `Search` | Chuẩn bị dữ liệu chuỗi tìm kiếm. |
| 2 | Nhấn phím Enter hoặc nút Mũi Tên | Kích hoạt nạp Form qua cấu trúc GET hoặc POST nhắm tới đích. |
| 3 | Khớp lệnh truy xuất | SQL chạy lệnh `SELECT * FROM tbl_qlsanpham WHERE tensp LIKE '%keyword%'`. |
| 4 | Đổ ra Layout | Chèn đè vào khung Lưới Giao Diện trang cửa hàng. |

### 3. Yêu cầu dữ liệu (Data Requirements)
* **Từ khóa (`keyword`):** Định dạng kiểu chuỗi String. 

### 4. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Độ chính xác tương đối (%):** Sử dụng mệnh đề `LIKE '%...'` để khớp lỏng lẻo các kết quả. Bất chấp in hoa hay in thường.
* **XSS Attack Prevention:** Keyword in ngược lại phía HTML bắt buộc bị mã hóa chặn ký tự lớn bé `<script>` để không bị chèn Virus.

### 5. Trường hợp ngoại lệ & Xử lý lỗi (Edge Cases)
* **Trường hợp:** Tìm kiếm một cái tên không hề tồn tại (Ví dụ "Giày tàng hình").
  * **Xử lý:** Vòng lặp `while` từ fetch data sẽ không chạy (Do affected_rows = 0), xuất hiện giao diện lưới trống trơn không sinh lỗi Crash.

### 6. Giao diện (UI/UX)
* Thanh tìm kiếm thiết kế mỏng dài dán chặt tại Header để người dùng có thể nhấp trỏ chuột được ở bất kì trang web nào mà không phải bấm tìm liên kết.
