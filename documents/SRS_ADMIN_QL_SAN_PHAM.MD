# Software Requirement Specification (SRS)
## Chức năng: Admin Quản lý Sản phẩm
**Mã chức năng:** ADMIN-PRODUCT-01  
**Trạng thái:** Implemented  
**Người soạn thảo:** Đội ngũ phát triển

---

### 1. Mô tả tổng quan (Description)
Quản trị viên có toàn quyền thao tác CRUD (Create, Read, Update, Delete) trên sản phẩm giày dép: thêm mới, sửa thông tin, quản lý ảnh, và **quản lý tồn kho chi tiết theo từng size**. Hệ thống sử dụng Rich Text Editor cho mô tả sản phẩm, upload ảnh vào thư mục `admin/uploads/`.

### 2. Luồng nghiệp vụ (User Workflow)
| Bước | Hành động Admin | Phản hồi hệ thống |
| :--- | :--- | :--- |
| 1 | Xem danh sách sản phẩm | `quanlisanpham.php`: bảng HTML Table + phân trang + lọc. |
| 2 | Thêm sản phẩm mới | Form: tên, giá, giá KM, danh mục, mô tả (CKEditor), ảnh đại diện, album ảnh phụ. Upload vào `admin/uploads/`. |
| 3 | Quản lý tồn kho | Form nhập số lượng cho từng size (36-44). INSERT/UPDATE `tbl_tonkho`. |
| 4 | Sửa sản phẩm | Load dữ liệu hiện tại → form edit. UPDATE `tbl_qlsanpham`. |
| 5 | Xóa sản phẩm | DELETE FROM `tbl_qlsanpham` WHERE masp = ?. |

### 3. Yêu cầu dữ liệu
* **Bảng `tbl_qlsanpham`:** masp, tensp, giasanpham, giakhuyenmai, madm, mota, anhdaidien, ngaytao.
* **Bảng `tbl_hinhanh`:** id, masp, hinhanh — nhiều ảnh cho 1 sản phẩm.
* **Bảng `tbl_tonkho`:** id, masp, size, soluong, ngaytao, ngaycapnhat — tồn kho chi tiết per size.

### 4. Ràng buộc kỹ thuật & Bảo mật
* **Session Admin:** `$_SESSION['dangnhap1']` bắt buộc.
* **File Upload:** `move_uploaded_file()` vào `admin/uploads/`. Kiểm tra extension.
* **Tồn kho:** Liên kết chặt với `inventory_helper.php` — stock biến động tự động khi có đơn hàng/hủy đơn.

### 5. Giao diện (UI/UX)
* Bảng sản phẩm: ảnh thumbnail, tên, giá, danh mục, tồn kho, nút Sửa/Xóa.
* Form thêm/sửa: Tabs thông tin + ảnh + tồn kho.
* Tồn kho: Grid input cho từng size (36-44) hiển thị trực quan.
