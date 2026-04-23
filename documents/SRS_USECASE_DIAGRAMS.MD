# Biểu đồ Use Case (Mermaid Diagrams)

Dưới đây là các biểu đồ Use Case sử dụng cú pháp Mermaid cho các chức năng được yêu cầu: Xem trang chủ, Xem danh mục sản phẩm, Xem blog, Xem giới thiệu, Liên hệ, và Quản lý tài khoản.

## 1. Xem Trang Chủ (View Homepage)

Biểu đồ mô tả các tương tác của người dùng khi truy cập vào trang chủ của hệ thống, bao gồm việc xem banner và các sản phẩm nổi bật/đại diện.

```mermaid
flowchart LR
    User((Người dùng\nKhách/Thành viên))
    
    subgraph Chức năng Xem Trang Chủ
        UC1([Truy cập trang chủ])
        UC2([Xem Banner tĩnh])
        UC3([Xem danh sách Hàng Hot])
        UC4([Cuộn trình duyệt qua các section])
    end
    
    User --> UC1
    UC1 -. << include >> .-> UC2
    UC1 -. << include >> .-> UC3
    User --> UC4
    UC4 -. << include >> .-> UC2
    UC4 -. << include >> .-> UC3
```

### Đặc tả Use Case: Xem Trang Chủ

| Thuộc tính | Mô Tả |
| :
## 7. Thanh toán (Checkout)

```mermaid
flowchart LR
    ThanhVien((Người dùng))
    
    subgraph Chức năng Thanh Toán
        UC_Cart([Xem giỏ hàng])
        UC_Checkout([Thanh toán tiền mặt])
    end
    
    ThanhVien --> UC_Checkout
    UC_Checkout -. << include >> .-> UC_Cart
```

### Đặc tả Use Case: Thanh toán tiền mặt

| Thuộc tính | Mô Tả |
| :--- | :--- |
| **Tên chức năng** | Thanh toán tiền mặt |
| **Chức năng của module** | Cho phép người dùng thực hiện đặt hàng bằng hình thức trả tiền khi nhận hàng (COD). |
| **Mô tả nghiệp vụ** | **Người dùng:**<br>- Truy cập trang Giỏ hàng.<br>- Kiểm tra danh sách sản phẩm: Tên, Size, Số lượng, Đơn giá.<br>- Có thể thay đổi size, tăng/giảm số lượng hoặc xóa sản phẩm (Hệ thống tự cập nhật lại Tổng cộng).<br>- Nhấn nút "Thanh toán tiền mặt".<br>**Hệ thống:**<br>- Kiểm tra trạng thái giỏ hàng (nếu rỗng thì báo lỗi).<br>- Hiển thị form "Thông tin đặt hàng" để người dùng nhập: Họ tên, Số điện thoại, Địa chỉ, Ghi chú.<br>**Xác nhận:**<br>- Người dùng nhấn "Xác nhận".<br>- Hệ thống lưu đơn hàng vào Cơ sở dữ liệu với trạng thái "Chờ xử lý/Thanh toán khi nhận hàng". |
| **Tác nhân** | Người dùng (đã đăng nhập) |
| **Điều kiện kích hoạt** | Người dùng nhấn vào nút "Thanh toán tiền mặt" tại trang Giỏ hàng. |
| **Điều kiện trước** | - Hệ thống hoạt động bình thường<br>- Người dùng đã đăng nhập vào hệ thống<br>- Giỏ hàng phải có ít nhất 1 sản phẩm |
| **Điều kiện sau** | - Thông tin đơn hàng được lưu thành công vào hệ thống.<br>- Giỏ hàng của người dùng được làm trống.<br>- Nếu đặt hàng thành công hiển thị thông báo và quay lại trang quản lý giỏ hàng. |
| **Ngoại lệ** | - Giỏ hàng rỗng thì không cho thanh toán<br>- Người dùng bỏ trống các trường bắt buộc |

--- | :--- |
| **Tên chức năng** | Xem Trang Chủ |
| **Chức năng của module** | Hiển thị giao diện trang chủ đập vào mắt người dùng đầu tiên, bao gồm Banner tĩnh và giới thiệu các sản phẩm đại diện (Hàng Hot). |
| **Mô tả nghiệp vụ** | **Người dùng:**<br>- Truy cập vào đường dẫn gốc (domain) của hệ thống.<br>- Cuộn (scroll) qua các khối section trên trang.<br>**Hệ thống:**<br>- Tự động nạp file `trangchu.php`.<br>- Tải và hiển thị các thành phần tĩnh (Header, Menu, Footer, Banner).<br>- Truy vấn cơ sở dữ liệu để lấy danh sách sản phẩm nổi bật (Hàng Hot, LIMIT 4).<br>- Render giao diện và hiển thị cho người dùng. |
| **Tác nhân** | Người dùng (Khách/Thành viên) |
| **Điều kiện kích hoạt** | Người dùng truy cập đường dẫn gốc (domain) của website hoặc click vào Logo trang chủ. |
| **Điều kiện trước** | - Hệ thống đang hoạt động bình thường.<br>- Có kết nối mạng. |
| **Điều kiện sau** | - Giao diện trang chủ (Banner, Hàng Hot) được hiển thị đầy đủ.<br>- Trạng thái hệ thống không thay đổi. |
| **Ngoại lệ** | - Chưa có sản phẩm trong CSDL: Vùng Hàng Hot để trống hoặc hiển thị "Đang cập nhật", không gây lỗi trang.<br>- Mất kết nối CSDL: Trả về trang bảo trì. |

## 2. Xem danh mục sản phẩm (View Product Category)

Biểu đồ mô tả các tương tác của người dùng (bao gồm khách vãng lai và thành viên) khi duyệt qua các danh mục sản phẩm của cửa hàng.

```mermaid
flowchart LR
    User((Người dùng\nKhách/Thành viên))
    
    subgraph Chức năng Xem Danh Mục Sản Phẩm
        UC1([Chọn danh mục sản phẩm])
        UC2([Xem danh sách sản phẩm])
        UC3([Lọc & Sắp xếp sản phẩm])
        UC4([Phân trang])
        UC5([Xem chi tiết sản phẩm])
    end
    
    User --> UC1
    User --> UC2
    UC1 -. << include >> .-> UC2
    UC2 -. << extend >> .-> UC3
    UC2 -. << extend >> .-> UC4
    UC2 -. << extend >> .-> UC5
```

### Đặc tả Use Case: Xem danh mục sản phẩm

| Thuộc tính | Mô Tả |
| :--- | :--- |
| **Tên chức năng** | Xem danh mục sản phẩm |
| **Chức năng của module** | Cho phép người dùng duyệt qua các danh mục sản phẩm, xem danh sách, chi tiết sản phẩm, lọc, sắp xếp và phân trang. |
| **Mô tả nghiệp vụ** | **Người dùng:**<br>- Truy cập trang chủ hoặc trang cửa hàng.<br>- Chọn một danh mục sản phẩm cụ thể (VD: Điện thoại).<br>- (Tùy chọn) Chọn bộ lọc (theo giá, thương hiệu) hoặc sắp xếp (A-Z, Giá tăng/giảm).<br>- (Tùy chọn) Chọn phân trang để xem các trang tiếp theo.<br>- Click vào một sản phẩm để xem chi tiết.<br>**Hệ thống:**<br>- Tiếp nhận ID/Slug danh mục và truy vấn CSDL.<br>- Hiển thị danh sách sản phẩm thuộc danh mục đó.<br>- Cập nhật lại danh sách sản phẩm ngay lập tức nếu người dùng chọn bộ lọc hoặc sắp xếp.<br>- Hiển thị chi tiết sản phẩm khi người dùng click vào. |
| **Tác nhân** | Người dùng (Khách/Thành viên) |
| **Điều kiện kích hoạt** | Người dùng chọn một danh mục sản phẩm từ menu hoặc trang cửa hàng. |
| **Điều kiện trước** | - Hệ thống hoạt động bình thường. |
| **Điều kiện sau** | - Danh sách sản phẩm thuộc danh mục được hiển thị chính xác theo yêu cầu (kèm bộ lọc/sắp xếp). |
| **Ngoại lệ** | - Không có sản phẩm nào trong danh mục: Hiển thị thông báo "Hiện chưa có sản phẩm nào trong danh mục này".<br>- Danh mục không tồn tại: Điều hướng về trang lỗi 404. |

## 3. Xem Blog (View Blog)

Biểu đồ mô tả quá trình người dùng truy cập và đọc các bài viết tin tức/blog trên hệ thống.

```mermaid
flowchart LR
    User((Người dùng\nKhách/Thành viên))
    
    subgraph Chức năng Xem Blog
        UC1([Truy cập trang Blog])
        UC2([Xem danh sách bài viết])
        UC3([Tìm kiếm bài viết])
        UC4([Xem chi tiết bài viết])
    end
    
    User --> UC1
    UC1 -. << include >> .-> UC2
    UC2 -. << extend >> .-> UC3
    UC2 -. << extend >> .-> UC4
```

### Đặc tả Use Case: Xem Blog

| Thuộc tính | Mô Tả |
| :--- | :--- |
| **Tên chức năng** | Xem Blog |
| **Chức năng của module** | Cho phép người dùng đọc các bài viết tin tức, blog trên hệ thống và tìm kiếm bài viết. |
| **Mô tả nghiệp vụ** | **Người dùng:**<br>- Truy cập vào trang Blog.<br>- (Tùy chọn) Nhập từ khóa vào ô tìm kiếm bài viết.<br>- Click vào tiêu đề hoặc ảnh đại diện của một bài viết để đọc nội dung chi tiết.<br>**Hệ thống:**<br>- Truy vấn CSDL, lấy danh sách các bài viết mới nhất (đã xuất bản).<br>- Hiển thị danh sách bài viết (Ảnh thumbnail, tiêu đề, tóm tắt, ngày đăng).<br>- Lọc và hiển thị kết quả nếu người dùng thực hiện tìm kiếm.<br>- Render nội dung chi tiết của bài viết khi người dùng click vào. |
| **Tác nhân** | Người dùng (Khách/Thành viên) |
| **Điều kiện kích hoạt** | Người dùng chọn chức năng "Blog" trên menu. |
| **Điều kiện trước** | - Hệ thống hoạt động bình thường. |
| **Điều kiện sau** | - Danh sách bài viết hoặc nội dung chi tiết bài viết được hiển thị thành công. |
| **Ngoại lệ** | - Không có bài viết nào: Hiển thị thông báo "Chưa có bài viết nào được đăng".<br>- Tìm kiếm không ra kết quả: Hiển thị "Không tìm thấy bài viết nào phù hợp". |

## 4. Xem Giới Thiệu (Brand & Team Members)

Biểu đồ mô tả tương tác khi người dùng tìm hiểu về thông tin thương hiệu và các thành viên.

```mermaid
flowchart LR
    User((Người dùng\nKhách/Thành viên))
    
    subgraph Chức năng Xem Giới Thiệu
        UC1([Truy cập trang Giới thiệu])
        UC2([Xem thông tin thương hiệu])
        UC3([Xem thông tin thành viên])
    end
    
    User --> UC1
    UC1 -. << include >> .-> UC2
    UC1 -. << include >> .-> UC3
```

### Đặc tả Use Case: Xem Giới Thiệu

| Thuộc tính | Mô Tả |
| :--- | :--- |
| **Tên chức năng** | Xem Giới Thiệu |
| **Chức năng của module** | Cung cấp thông tin về thương hiệu, lịch sử hình thành và đội ngũ thành viên của cửa hàng. |
| **Mô tả nghiệp vụ** | **Người dùng:**<br>- Click vào liên kết "Giới thiệu" trên menu chính hoặc footer.<br>- Cuộn trang để đọc thông tin doanh nghiệp và xem đội ngũ nhân sự.<br>**Hệ thống:**<br>- Nhận yêu cầu và tải nội dung text, hình ảnh liên quan đến giới thiệu công ty.<br>- Truy vấn CSDL hoặc file cấu hình để lấy dữ liệu các thành viên nổi bật trong đội ngũ.<br>- Render giao diện và hiển thị trang Giới thiệu cho người dùng. |
| **Tác nhân** | Người dùng (Khách/Thành viên) |
| **Điều kiện kích hoạt** | Người dùng click vào liên kết "Giới thiệu" trên menu hoặc footer. |
| **Điều kiện trước** | - Hệ thống hoạt động bình thường. |
| **Điều kiện sau** | - Trang giới thiệu hiển thị đầy đủ thông tin thương hiệu và thành viên. |
| **Ngoại lệ** | - Lỗi truy xuất danh sách thành viên: Phần giới thiệu chung vẫn hiển thị bình thường, phần danh sách thành viên báo lỗi hoặc ẩn đi. |

## 5. Liên Hệ (Contact)

Biểu đồ mô tả hành động gửi thông tin liên hệ, phản hồi từ người dùng tới ban quản trị cửa hàng.

```mermaid
flowchart LR
    User((Người dùng\nKhách/Thành viên))
    Admin((Quản trị viên))
    
    subgraph Chức năng Liên Hệ
        UC1([Truy cập trang Liên hệ])
        UC2([Xem thông tin liên hệ của Shop])
        UC3([Điền Form liên hệ])
        UC4([Gửi thông điệp/Feedback])
        UC5([Tiếp nhận & Quản lý liên hệ])
    end
    
    User --> UC1
    UC1 -. << include >> .-> UC2
    UC1 -. << extend >> .-> UC3
    UC3 -. << include >> .-> UC4
    
    Admin --> UC5
    UC4 -. << trigger >> .-> UC5
```

### Đặc tả Use Case: Liên Hệ

| Thuộc tính | Mô Tả |
| :--- | :--- |
| **Tên chức năng** | Liên Hệ |
| **Chức năng của module** | Cho phép người dùng xem thông tin liên hệ của cửa hàng và gửi tin nhắn, phản hồi đến ban quản trị. |
| **Mô tả nghiệp vụ** | **Người dùng:**<br>- Truy cập trang Liên hệ.<br>- Xem các thông tin liên lạc (SĐT, Email, Địa chỉ, Bản đồ).<br>- Điền đầy đủ thông tin vào Form liên hệ: Họ tên, Email, Tiêu đề, Nội dung.<br>- Nhấn nút "Gửi".<br>**Hệ thống:**<br>- Hiển thị giao diện trang Liên hệ và Form điền thông tin.<br>- Nhận dữ liệu Form, tiến hành kiểm tra tính hợp lệ (Validate).<br>- Lưu nội dung thông điệp vào CSDL và gửi thông báo cho admin (nếu có cấu hình).<br>- Hiển thị thông báo gửi thành công cho người dùng. |
| **Tác nhân** | Người dùng (Khách/Thành viên), Admin (Tiếp nhận) |
| **Điều kiện kích hoạt** | Người dùng truy cập trang "Liên hệ" và quyết định điền form gửi phản hồi. |
| **Điều kiện trước** | - Hệ thống hoạt động bình thường. |
| **Điều kiện sau** | - Thông điệp được lưu trữ thành công vào hệ thống để Admin xử lý. |
| **Ngoại lệ** | - Dữ liệu form không hợp lệ (Email sai định dạng, thiếu nội dung): Hệ thống hiển thị thông báo yêu cầu nhập đúng định dạng. |

## 6. Quản Lý Tài Khoản (Account Management)

Phần Quản lý tài khoản bao gồm các chức năng dành riêng cho người dùng đã đăng nhập (Thành viên). Dưới đây là biểu đồ Use Case và đặc tả chi tiết cho từng chức năng nhỏ.

### 6.1. Cập nhật thông tin cá nhân

```mermaid
flowchart LR
    ThanhVien((Thành viên))
    
    subgraph Chức năng Quản Lý Tài Khoản
        UC_Login([Đăng nhập hệ thống])
        UC_Update([Cập nhật thông tin cá nhân])
    end
    
    ThanhVien --> UC_Update
    UC_Update -. << include >> .-> UC_Login
```

#### Đặc tả Use Case: Cập nhật thông tin cá nhân

| Thuộc tính | Mô Tả |
| :--- | :--- |
| **Tên chức năng** | Cập nhật thông tin cá nhân |
| **Chức năng của module** | Cho phép thành viên đã đăng nhập chỉnh sửa và cập nhật các thông tin cá nhân của mình. |
| **Mô tả nghiệp vụ** | **Người dùng:**<br>- Truy cập trang "Quản lý tài khoản" -> "Cập nhật thông tin".<br>- Xem thông tin hiện tại trên Form.<br>- Chỉnh sửa các trường dữ liệu: Họ tên, Số điện thoại, Địa chỉ...<br>- Nhấn nút "Lưu thay đổi".<br>**Hệ thống:**<br>- Hiển thị màn hình quản lý tài khoản kèm Form đã điền sẵn dữ liệu hiện tại.<br>- Khi người dùng nhấn lưu, hệ thống kiểm tra validate dữ liệu đầu vào (Ví dụ: SĐT phải là số).<br>- Thực thi cập nhật thông tin mới vào CSDL.<br>- Hiển thị thông báo "Cập nhật thông tin thành công". |
| **Tác nhân** | Thành viên (Người dùng đã đăng nhập) |
| **Điều kiện kích hoạt** | Người dùng click vào chức năng "Cập nhật thông tin" trong trang tài khoản. |
| **Điều kiện trước** | - Hệ thống hoạt động bình thường.<br>- Người dùng đã đăng nhập thành công. |
| **Điều kiện sau** | - Thông điệp cập nhật thành công được hiển thị, CSDL cập nhật thông tin. |
| **Ngoại lệ** | - Dữ liệu nhập không hợp lệ (SĐT sai định dạng, thiếu thông tin bắt buộc): Báo lỗi ngay trên Form, yêu cầu nhập lại. |

### 6.2. Đổi mật khẩu

```mermaid
flowchart LR
    ThanhVien((Thành viên))
    
    subgraph Chức năng Quản Lý Tài Khoản
        UC_Login([Đăng nhập hệ thống])
        UC_Pass([Đổi mật khẩu])
    end
    
    ThanhVien --> UC_Pass
    UC_Pass -. << include >> .-> UC_Login
```

#### Đặc tả Use Case: Đổi mật khẩu

| Thuộc tính | Mô Tả |
| :--- | :--- |
| **Tên chức năng** | Đổi mật khẩu |
| **Chức năng của module** | Cho phép thành viên thay đổi mật khẩu đăng nhập để bảo mật tài khoản. |
| **Mô tả nghiệp vụ** | **Người dùng:**<br>- Truy cập chức năng "Đổi mật khẩu".<br>- Nhập Mật khẩu hiện tại, Mật khẩu mới và Xác nhận mật khẩu mới vào Form.<br>- Nhấn nút "Cập nhật mật khẩu".<br>**Hệ thống:**<br>- Hiển thị Form đổi mật khẩu.<br>- Tiếp nhận dữ liệu, mã hóa Mật khẩu hiện tại và đối chiếu với CSDL.<br>- Kiểm tra Mật khẩu mới và Xác nhận mật khẩu có trùng khớp không.<br>- Nếu hợp lệ, hệ thống mã hóa Mật khẩu mới và ghi đè vào CSDL.<br>- Hiển thị thông báo "Đổi mật khẩu thành công". |
| **Tác nhân** | Thành viên (Người dùng đã đăng nhập) |
| **Điều kiện kích hoạt** | Người dùng chọn chức năng "Đổi mật khẩu" trong trang quản trị cá nhân. |
| **Điều kiện trước** | - Hệ thống hoạt động bình thường.<br>- Người dùng đã đăng nhập thành công. |
| **Điều kiện sau** | - Mật khẩu mới được cập nhật và có thể dùng cho lần đăng nhập kế tiếp. |
| **Ngoại lệ** | - Sai mật khẩu hiện tại: Thông báo "Mật khẩu hiện tại không chính xác".<br>- Mật khẩu mới không trùng khớp: Thông báo "Xác nhận mật khẩu mới không khớp". |

### 6.3. Xem lịch sử mua hàng

```mermaid
flowchart LR
    ThanhVien((Thành viên))
    
    subgraph Chức năng Quản Lý Tài Khoản
        UC_Login([Đăng nhập hệ thống])
        UC_History([Xem lịch sử mua hàng])
        UC_Detail([Xem chi tiết đơn hàng])
    end
    
    ThanhVien --> UC_History
    UC_History -. << include >> .-> UC_Login
    UC_History -. << extend >> .-> UC_Detail
```

#### Đặc tả Use Case: Xem lịch sử mua hàng

| Thuộc tính | Mô Tả |
| :--- | :--- |
| **Tên chức năng** | Xem lịch sử mua hàng |
| **Chức năng của module** | Hiển thị danh sách các đơn hàng đã đặt của thành viên cùng với chi tiết trạng thái. |
| **Mô tả nghiệp vụ** | **Người dùng:**<br>- Truy cập mục "Lịch sử mua hàng" hoặc "Đơn hàng của tôi".<br>- Xem danh sách tổng quan các đơn hàng.<br>- Click vào "Xem chi tiết" (hoặc mã đơn) của một đơn hàng cụ thể.<br>**Hệ thống:**<br>- Lấy thông tin ID của người dùng hiện tại và truy vấn CSDL để lấy tất cả đơn hàng tương ứng.<br>- Hiển thị danh sách đơn hàng (Mã đơn, Ngày đặt, Tổng tiền, Trạng thái đơn).<br>- Khi người dùng chọn xem chi tiết, hệ thống truy vấn và hiển thị danh sách các sản phẩm thuộc đơn hàng đó. |
| **Tác nhân** | Thành viên (Người dùng đã đăng nhập) |
| **Điều kiện kích hoạt** | Người dùng click vào "Lịch sử đơn hàng". |
| **Điều kiện trước** | - Hệ thống hoạt động bình thường.<br>- Người dùng đã đăng nhập thành công. |
| **Điều kiện sau** | - Danh sách đơn hàng và chi tiết đơn hàng được hiển thị chính xác. |
| **Ngoại lệ** | - Chưa có đơn hàng nào: Hiển thị thông báo "Bạn chưa có đơn hàng nào". |

### 6.4. Đăng xuất

```mermaid
flowchart LR
    ThanhVien((Thành viên))
    
    subgraph Chức năng Quản Lý Tài Khoản
        UC_Logout([Đăng xuất])
    end
    
    ThanhVien --> UC_Logout
```

#### Đặc tả Use Case: Đăng xuất

| Thuộc tính | Mô Tả |
| :--- | :--- |
| **Tên chức năng** | Đăng xuất |
| **Chức năng của module** | Hủy bỏ phiên làm việc hiện tại của thành viên. |
| **Mô tả nghiệp vụ** | **Người dùng:**<br>- Click vào nút "Đăng xuất" trên Header hoặc menu tài khoản.<br>**Hệ thống:**<br>- Xóa bỏ toàn bộ dữ liệu Session lưu trữ thông tin đăng nhập của người dùng.<br>- Xóa Cookie ghi nhớ đăng nhập (nếu có).<br>- Điều hướng (Redirect) người dùng trở về Trang chủ hoặc Trang đăng nhập. |
| **Tác nhân** | Thành viên (Người dùng đã đăng nhập) |
| **Điều kiện kích hoạt** | Người dùng click nút "Đăng xuất". |
| **Điều kiện trước** | - Người dùng đang ở trạng thái đã đăng nhập. |
| **Điều kiện sau** | - Phiên đăng nhập bị hủy, giao diện trở về chế độ Khách (Guest). |
| **Ngoại lệ** | - Lỗi server không thể xóa session (hiếm gặp). |

---

# Biểu đồ Hoạt động (Activity Diagrams)

Dưới đây là các biểu đồ hoạt động (Activity Diagrams) tương ứng với các luồng nghiệp vụ chính của hệ thống.

## 1. Xem Trang Chủ

```mermaid
flowchart TD
    subgraph Người dùng
        A[Truy cập Domain Website]
        D[Cuộn trình duyệt qua các khối Section]
    end
    subgraph Hệ thống
        B[Nạp file trangchu.php]
        C[Hiển thị khối Carousel/Banner tĩnh trên đỉnh]
        E{Truy vấn CSDL lấy sản phẩm đại diện}
        F[Hiển thị danh sách 'Hàng Hot']
        G[Hiển thị khoảng trống, không báo lỗi]
        H[Hiển thị Footer đệm chóp cuối]
    end
    
    Start((Bắt đầu)) --> A
    A --> B
    B --> C
    C --> D
    D --> E
    E -->|Có sản phẩm| F
    E -->|Trống| G
    F --> H
    G --> H
    H --> I((Kết thúc))
```

## 2. Xem danh mục sản phẩm

```mermaid
flowchart TD
    subgraph Người dùng
        A[Truy cập trang chủ/Cửa hàng]
        B[Chọn danh mục sản phẩm]
        F{Muốn lọc/sắp xếp?}
        G[Chọn tiêu chí lọc/sắp xếp]
        I{Xem chi tiết?}
        J[Click vào sản phẩm]
    end
    subgraph Hệ thống
        C{Tải dữ liệu}
        D[Hiển thị danh sách sản phẩm]
        E[Hiển thị thông báo 'Không có sản phẩm']
        H[Cập nhật lại danh sách]
        K[Hiển thị chi tiết sản phẩm]
    end
    
    Start((Bắt đầu)) --> A
    A --> B
    B --> C
    C -->|Thành công| D
    C -->|Lỗi/Trống| E
    D --> F
    F -->|Có| G
    G --> H
    H --> I
    F -->|Không| I
    I -->|Có| J
    J --> K
    I -->|Không| L((Kết thúc))
    K --> L
    E --> L
```

## 3. Xem Blog

```mermaid
flowchart TD
    subgraph Người dùng
        A[Truy cập trang Blog]
        E{Tìm kiếm bài viết?}
        F[Nhập từ khóa]
        H{Xem chi tiết?}
        I[Click vào bài viết]
    end
    subgraph Hệ thống
        B{Tải dữ liệu}
        C[Hiển thị danh sách bài viết]
        D[Hiển thị thông báo lỗi]
        G[Hiển thị kết quả tìm kiếm]
        J[Hiển thị nội dung chi tiết bài viết]
    end
    
    Start((Bắt đầu)) --> A
    A --> B
    B -->|Thành công| C
    B -->|Lỗi| D
    C --> E
    E -->|Có| F
    F --> G
    G --> H
    E -->|Không| H
    H -->|Có| I
    I --> J
    H -->|Không| K((Kết thúc))
    J --> K
    D --> K
```

## 4. Xem Giới Thiệu

```mermaid
flowchart TD
    subgraph Người dùng
        A[Chọn mục Giới thiệu từ Menu/Footer]
    end
    subgraph Hệ thống
        B{Tải dữ liệu}
        C[Hiển thị thông tin thương hiệu và lịch sử]
        D[Hiển thị thông tin đội ngũ thành viên]
        E[Hiển thị thông báo lỗi]
    end
    
    Start((Bắt đầu)) --> A
    A --> B
    B -->|Thành công| C
    C --> D
    B -->|Lỗi| E
    D --> F((Kết thúc))
    E --> F
```

## 5. Liên Hệ

```mermaid
flowchart TD
    subgraph Người dùng
        A[Truy cập trang Liên hệ]
        C{Gửi phản hồi?}
        D[Điền thông tin vào form: Tên, Email, Nội dung]
        E[Bấm gửi]
    end
    subgraph Hệ thống
        B[Hiển thị thông tin liên hệ của cửa hàng]
        F{Validate dữ liệu form}
        G[Hiển thị thông báo lỗi yêu cầu nhập lại]
        H[Lưu thông điệp vào cơ sở dữ liệu]
        I[Hiển thị thông báo gửi thành công]
    end
    
    Start((Bắt đầu)) --> A
    A --> B
    B --> C
    C -->|Có| D
    D --> E
    E --> F
    F -->|Không hợp lệ| G
    G --> D
    F -->|Hợp lệ| H
    H --> I
    C -->|Không| J((Kết thúc))
    I --> J
```

## 6. Quản Lý Tài Khoản

Phần Quản lý tài khoản được chia nhỏ thành các biểu đồ hoạt động tương ứng với từng chức năng cụ thể:

### 6.1. Cập nhật thông tin cá nhân

```mermaid
flowchart TD
    subgraph Người dùng
        A[Truy cập trang Quản lý tài khoản]
        F[Thay đổi thông tin: Họ tên, SĐT, Địa chỉ...]
        G[Bấm 'Lưu thay đổi']
    end
    subgraph Hệ thống
        B{Đã đăng nhập?}
        C[Chuyển hướng trang Đăng nhập]
        D{Đăng nhập thành công?}
        E[Hiển thị thông tin cá nhân hiện tại]
        H{Validate dữ liệu}
        I[Hiển thị thông báo lỗi]
        J[Cập nhật vào CSDL]
        K[Hiển thị thông báo thành công]
    end
    
    Start((Bắt đầu)) --> A
    A --> B
    B -->|Chưa| C
    C --> D
    D -->|Không| C
    D -->|Có| E
    B -->|Đã đăng nhập| E
    E --> F
    F --> G
    G --> H
    H -->|Không hợp lệ| I
    I --> F
    H -->|Hợp lệ| J
    J --> K
    K --> L((Kết thúc))
```

### 6.2. Đổi mật khẩu

```mermaid
flowchart TD
    subgraph Người dùng
        A[Truy cập trang Đổi mật khẩu]
        F[Nhập Mật khẩu cũ, Mật khẩu mới, Nhập lại]
        G[Bấm 'Cập nhật']
    end
    subgraph Hệ thống
        B{Đã đăng nhập?}
        C[Chuyển hướng trang Đăng nhập]
        D{Đăng nhập thành công?}
        E[Hiển thị form Đổi mật khẩu]
        H{Kiểm tra mật khẩu cũ}
        I[Thông báo lỗi mật khẩu cũ không đúng]
        J{Mật khẩu mới trùng khớp?}
        K[Thông báo lỗi không khớp]
        L[Mã hóa mật khẩu mới và lưu CSDL]
        M[Thông báo đổi mật khẩu thành công]
    end
    
    Start((Bắt đầu)) --> A
    A --> B
    B -->|Chưa| C
    C --> D
    D -->|Không| C
    D -->|Có| E
    B -->|Đã đăng nhập| E
    E --> F
    F --> G
    G --> H
    H -->|Sai| I
    I --> F
    H -->|Đúng| J
    J -->|Không| K
    K --> F
    J -->|Có| L
    L --> M
    M --> N((Kết thúc))
```

### 6.3. Xem lịch sử mua hàng

```mermaid
flowchart TD
    subgraph Người dùng
        A[Truy cập trang Lịch sử đơn hàng]
        I{Xem chi tiết đơn hàng?}
        J[Click mã đơn hàng]
    end
    subgraph Hệ thống
        B{Đã đăng nhập?}
        C[Chuyển hướng trang Đăng nhập]
        D{Đăng nhập thành công?}
        E[Truy vấn CSDL lấy danh sách đơn hàng]
        F{Có đơn hàng?}
        G[Hiển thị 'Bạn chưa có đơn hàng nào']
        H[Hiển thị danh sách các đơn hàng]
        K[Hiển thị chi tiết: Sản phẩm, Giá, Trạng thái]
    end
    
    Start((Bắt đầu)) --> A
    A --> B
    B -->|Chưa| C
    C --> D
    D -->|Không| C
    D -->|Có| E
    B -->|Đã đăng nhập| E
    E --> F
    F -->|Không| G
    F -->|Có| H
    H --> I
    I -->|Có| J
    J --> K
    I -->|Không| L((Kết thúc))
    G --> L
    K --> L
```

### 6.4. Đăng xuất

```mermaid
flowchart TD
    subgraph Người dùng
        A[Click nút 'Đăng xuất']
    end
    subgraph Hệ thống
        B[Xóa thông tin Session/Cookie]
        C[Chuyển hướng về Trang chủ hoặc Đăng nhập]
    end
    
    Start((Bắt đầu)) --> A
    A --> B
    B --> C
    C --> D((Kết thúc))
```

## 7. Thanh toán (Checkout)

### 7.1. Thanh toán tiền mặt (COD)

```mermaid
flowchart TD
    subgraph Người dùng
        A[Truy cập trang Giỏ hàng]
        B[Kiểm tra danh sách sản phẩm]
        C[Thay đổi size, số lượng, xóa sản phẩm]
        D[Nhấn nút 'Thanh toán tiền mặt']
        H[Nhập: Họ tên, SĐT, Địa chỉ, Ghi chú]
        I[Nhấn 'Xác nhận']
    end
    subgraph Hệ thống
        E{Kiểm tra trạng thái giỏ hàng}
        F[Báo lỗi giỏ hàng rỗng]
        G[Hiển thị form 'Thông tin đặt hàng']
        J{Validate dữ liệu bắt buộc}
        K[Hiển thị báo lỗi thiếu thông tin]
        L[Lưu đơn hàng vào CSDL trạng thái 'Chờ xử lý']
        M[Làm trống giỏ hàng]
        N[Hiển thị thông báo thành công và chuyển hướng]
    end
    
    Start((Bắt đầu)) --> A
    A --> B
    B --> C
    C --> D
    D --> E
    E -->|Rỗng| F
    F --> B
    E -->|Có sản phẩm| G
    G --> H
    H --> I
    I --> J
    J -->|Thiếu| K
    K --> H
    J -->|Hợp lệ| L
    L --> M
    M --> N
    N --> End((Kết thúc))
```
