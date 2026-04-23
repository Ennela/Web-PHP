# Software Requirement Specification (SRS)
## Chức năng: Hệ thống Tra cứu và Theo dõi Đơn hàng (Order Tracking)
**Mã chức năng:** ORDER-TRACK-01  
**Trạng thái:** Draft / Review  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Hệ thống cho phép khách hàng đăng nhập để xem danh sách các đơn hàng đã đặt của cá nhân (`tradonhang.php`) và xem chi tiết trạng thái, mặt hàng của từng đơn hàng cụ thể (`chitietdonhang.php`). Đây là tính năng bảo mật, đảm bảo khách hàng chỉ xem được đơn hàng của chính mình nhằm bảo vệ thông tin cá nhân.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động người dùng | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Đăng nhập tài khoản cá nhân và truy cập "Tra cứu đơn hàng" | Hệ thống truy xuất CSDL bảng `oder` theo `makh` tương ứng với session hiện tại và hiển thị thẻ thông tin tóm tắt từng đơn với mã đơn hàng 9 số (`order_code`). |
| 2 | Chọn bộ lọc trạng thái (VD: Đang giao, Chờ xử lý) | Truy vấn SQL có thêm tham chiếu `status` giúp trả về danh sách lọc linh hoạt. |
| 3 | Nhấn "Xem chi tiết" tại một đơn hàng | Điều hướng sang `chitietdonhang.php?id=...`. Hệ thống xác thực quyền sở hữu đơn hàng (so sánh `makh` của đơn hàng với `$_SESSION['makh']`). |
| 4 | Xem hành trình đơn hàng | Hiển thị Timeline (Chờ xử lý -> Xác nhận -> Đang giao -> Đã giao) và danh sách sản phẩm cùng tổng chi phí. |

### 3. Yêu cầu dữ liệu (Data Requirements)
#### 3.1. Dữ liệu Đầu vào (Input Fields / Parameters)
* **Status Filter:** Lựa chọn `status` truyển trên URL bằng GET method để lọc.
* **Order ID:** Tham số `$id` độc nhất truyền vào URL trang chi tiết đơn hàng.

#### 3.2. Dữ liệu Đầu ra (Data Output Output)
* **Mã đơn hàng:** Mã 9 chữ số ngẫu nhiên (`order_code`) hiển thị cho khách hàng thay vì `id` nội bộ.
* **Thông tin giao vận:** Địa chỉ đích, SĐT, ngày giờ khởi tạo (`ngaytao`), trạng thái hiện tại.
* **Dữ liệu Thanh Toán:** Tổng thành tiền, Phương thức thanh toán (COD hoặc thẻ qua `vnpay_tranId`).
* **Chi tiết giỏ hàng lúc mua:** `oder_chitiet` lồng ghép tên mặt hàng (`tensp`), hình ảnh (`anhdaidien`), số lượng, kích cỡ (`size`), và giá mua ngay lúc chốt hoá đơn.

### 4. Ràng buộc kỹ thuật & Bảo mật (Technical Constraints)
* **Xác thực quyền phiên (Authentication Required):** Người dùng bắt buộc phải đăng nhập (Session `dangnhap` tồn tại), nếu không tự động tước quyền và điều hướng sang trang Đăng nhập.
* **Phân quyền dữ liệu (Authorization Policy):** Mệnh đề `WHERE id = $orderId AND makh = $makh` nghiêm ngặt khóa lại lỗ hổng IDOR, người lạ dù biết Order ID cũng không tra cứu được nếu nó không thuộc tài khoản của họ.

### 5. Trường hợp ngoại lệ & Xử lý lỗi (Edge Cases)
* **Trường hợp khách hàng muốn xem đơn hàng dạng chưa đăng nhập (Khách lạ):** 
  * Định hướng phát triển tương lai có thể xét URL token gửi qua email để xem. Hiện tại bị chặn cứng.
* **Trường hợp truy cập ID đơn hàng không thuộc về mình:**
  * **Xử lý:** SQL không query ra result, JS tung catch `alert('Bạn không có quyền xem đơn hàng này!');` rồi đẩy người dùng lùi về danh sách cũ.

### 6. Giao diện (UI/UX)
* **Trực quan trạng thái:** Sử dụng Timeline dọc/ngang (hoặc các Alert banners) với màu phân biệt: Đỏ (Đã huỷ), Xanh lá (Đã giao), Cam (Chờ xử lí), nhằm cung cấp ngay lập tức Insight về kiện hàng.
* Giao diện Responsive chia bảng chi tiết thành các info-cards hiện đại phù hợp trên di động.
