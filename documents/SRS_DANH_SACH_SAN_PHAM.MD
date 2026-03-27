# Software Requirement Specification (SRS)
## Chức năng: Xem Danh sách Sản phẩm (Cửa hàng)
**Mã chức năng:** PROD-LIST-01  
**Trạng thái:** Draft / Review  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Trang Danh sách Sản phẩm, hay còn gọi là trang Cửa hàng (`shop.php`), là khu vực truy xuất tập trung tất cả các mẫu thiết kế hiện đang được bày bán từ CSDL. Khách hàng có thể trải nghiệm công cụ tự động phân trang (Pagination) để lướt xem các bộ sưu tập tiếp theo mà không bị quá tải trang web.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động người dùng | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Mở trang `shop.php` | Hệ thống thực thi tập lệnh xác định số trang hiện hành `$_GET['per_page']` và `$_GET['page']`. |
| 2 | Truy vấn dữ liệu sản phẩm | Gọi `SELECT * FROM tbl_qlsanpham ORDER BY masp DESC LIMIT ?, ?` để kết xuất danh sách lô hàng. |
| 3 | Lướt giao diện và bấm sang Số trang tiếp theo (Pagination) | Chuyển URL (ví dụ `shop.php?per_page=12&page=2`) lấy lô hàng từ trong CSDL tương ứng. |

### 3. Yêu cầu dữ liệu (Data Requirements)
#### 3.1. Dữ liệu Hiển thị Dưới dạng Lưới (Grid Output)
* Thẻ hình dạng một khung ảnh sản phẩm bao gồm:
  * Hình Ảnh Thumbnail chính (`anhdaidien`).
  * Tên gọi của Sản phẩm (`tensp`).
  * Mức giá bán chính thức của Shop (`giasanpham`). Mức giá gạch mờ kế bên là (`giagoc`).

#### 3.2. Dữ liệu Phân trang (Pagination Inputs)
* `$item_per_page`: Số cấu kiện giày sẽ bóc ra trong 1 đợt hiển thị.
* `$current_page`: Lấy biến định vị trên thanh địa chỉ URL.

### 4. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Kỹ thuật tải giới hạn SQL (`LIMIT & OFFSET`):** Danh sách hàng hóa thường chứa bản ghi dày đặc, phải sử dụng LIMIT (Giới hạn con số hàng) kết hợp OFFSET (Mốc bỏ qua) để ngắt lô truyền tải, tránh giật vỡ máy chủ khi kéo cả ngàn kết quả của bảng `tbl_qlsanpham`.

### 5. Trường hợp ngoại lệ & Xử lý lỗi (Edge Cases)
* **Trường hợp:** Cố sửa URL ép tham số `page=9999` (số quá cao vượt ngoài lượng hàng).
  * **Xử lý:** Truy vấn LIMIT tới giới hạn rỗng, thẻ lưới grid trả về màn hình trống tinh khiết. Thanh thiết đặt dưới cùng cũng biến dạng thu thập do không nảy số theo thuật toán ở file phân trang.

### 6. Giao diện (UI/UX)
* Hệ thống bố trí hệ thẻ Flex hoặc Grid Card vuông vức xếp thành nhiều cột (thường là 3 thẻ trên 1 dòng máy vi tính phẳng). Có khoảng viền thụt lùi và nút phân trang đánh vị trí thứ tự nằm ở trung tâm của đuôi giao diện.
