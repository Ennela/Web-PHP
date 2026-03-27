# Software Requirement Specification (SRS)
## Chức năng: Đọc Bài Viết / Tin Tức
**Mã chức năng:** BLOG-NEWS-01  
**Trạng thái:** Draft / Review  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Khu vực cung cấp các bài viết chuyên đề hiển thị cho người dùng ngoài giao diện (Frontend). Người dùng truy cập trang `baiviet.php` để xem danh sách tổng hợp, và trang `chitietbaiviet.php` để đọc các nội dung đầy đủ.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động người dùng | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Nhấn chọn mục "Bản tin" hoặc "Về chúng tôi" | Truy xuất file `baiviet.php` và chạy lệnh rút trích `SELECT * FROM tbl_qlbaidang WHERE chedo='Hiện'`. |
| 2 | Xem danh sách tin (Listing) | Hệ thống In vòng lặp các khối Card chứa Ảnh đại diện nhỏ và Tiêu đề bài viết. |
| 3 | Click vào thẻ "Đọc thêm" | Điều hướng màn hình chuyển sang trang đọc bài chi tiết `chitietbaiviet.php?id=XXX`. |

### 3. Yêu cầu dữ liệu (Data Requirements)
#### 3.1. Dữ liệu Hiển thị (Data Outputs)
* **Danh sách tóm lược:** Chỉ tải ra `tieude`, `anhdaidien` và đoạn chữ `noidung` bị cắt ngắn lại.
* **Chi Tiết:** Hiển thị `tieude` tại thẻ Title chóp màn hình, hiển thị nội dung toàn văn `noidung` và bổ trí các ảnh `anhgioithieu1`, `anhgioithieu2` xen kẽ.

### 4. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Lọc điều kiện `chedo`:** Bắt buộc mệnh đề truy vấn luôn phải có bộ lọc loại bỏ giá trị `chedo='Ẩn'` để đảm bảo Admin có quyền giấu bài nháp.
* **Tối ưu Tải Form:** Truyền tải nội dung văn bản một cách nguyên bản để bảo vệ cấu trúc đoạn văn bản/ký tự xuống dòng của người biên tập.

### 5. Trường hợp ngoại lệ & Xử lý lỗi (Edge Cases)
* **Trường hợp:** ID bài viết truyền vào đường dẫn URL (ví dụ `?id=999`) không tồn tại trong CSDL.
  * **Xử lý:** Trang chi tiết sẽ bị đổ rỗng dữ liệu mô tả vì truy vấn `mysqli_fetch_array` trả về null (có thể gây ra lỗi vỡ nhỏ giao diện).

### 6. Giao diện (UI/UX)
* Ở màn hình danh sách, sử dụng hệ thống Lưới tự đẩy card theo Responsive (vd: Bootstrap). 
* Ở màn hình chi tiết, căn lề văn bản vừa phải với khung hình ảnh lớn dãn nở 100% độ rộng (Width 100%).
