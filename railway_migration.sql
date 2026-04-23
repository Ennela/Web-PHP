-- ============================================================
-- RAILWAY MIGRATION — Chạy trực tiếp trên database `railway`
-- KHÔNG có CREATE DATABASE / USE — kết nối thẳng vào `railway`
-- ============================================================

/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- ============================================================
-- 1. tbl_qlsanpham (phải tạo trước oder_chitiet)
-- ============================================================
CREATE TABLE IF NOT EXISTS `tbl_qlsanpham` (
  `masp` int(11) NOT NULL AUTO_INCREMENT,
  `tensp` varchar(255) DEFAULT NULL,
  `anhgiuoithieu1` varchar(255) DEFAULT NULL,
  `anhgiuoithieu2` varchar(255) DEFAULT NULL,
  `anhdaidien` varchar(255) DEFAULT NULL,
  `giasanpham` int(11) DEFAULT NULL,
  `giagoc` int(11) DEFAULT NULL,
  `noidung` varchar(5000) DEFAULT NULL,
  `ngaytao` int(11) DEFAULT NULL,
  `nhomsp` varchar(255) DEFAULT NULL,
  `ngaycapnhat` int(11) DEFAULT NULL,
  PRIMARY KEY (`masp`)
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT IGNORE INTO `tbl_qlsanpham` VALUES (1,'Converse Chuck 70 Low Top Black (1970s)','uploads/giay-converse-chuck-1.jpg','uploads/c11-1.jpg','uploads/giay-converse-1970s-low-top-black-1.jpg',945000,1000000,'Fullbox CV 1970 cổ thấp màu đen.',1775894400,'Giày nam',1775894400),(2,'Balenciaga Triple S Trainer Black Red','uploads/balemc-12.jpg','uploads/balemc-6.jpg','uploads/Triple-S-Trainer-Black-Red-2018.jpg',600000,0,'Fullbox Balen Triple S Trainer Black Red 2018.',1775808000,'Giày nam',1775808000),(3,'Vans Old Skool Classic Black','uploads/nbv-11.jpg','uploads/unisex.jpg','uploads/vans.jpg',775000,1295000,'Fullbox Old Skool Black.',1775721600,'Giày nam',1775721600),(4,'Air Force 1 \'07 LV8 Overbranding','uploads/IMG_1818.jpg','uploads/IMG_1819.jpg','uploads/IMG_1036.jpg',1185000,0,'Fullbox Air Force 1 \'07 LV8 Utility.',1775635200,'Giày nam',1775635200),(5,'Alexander McQueen Oversized Sneaker Black','uploads/cach-mix-do-nu-ca-tinh.jpg','uploads/balemc-17.jpg','uploads/cac-thuong-hieu-giay-noi-tieng-1.jpg',295000,300000,'Giày Thể Thao Nam.',1775548800,'Giày nam',1775548800),(6,'Converse Chuck 70 Low Top White (1970s)','uploads/giay-converse-1970s-low-1.jpg','uploads/','uploads/converse-1970s-low-1.jpg',900000,0,'Fullbox CV 1970 cổ thấp màu trắng.',1775462400,'Giày nam',1775462400),(7,'Adidas Alphabounce Beyond Black','uploads/3-1.jpg','uploads/alphabounce-beyond-black-768x768.jpg','uploads/1.jpg',1000000,0,'Fullbox Alphabounce Beyond Black.',1775376000,'Giày nam',1775376000),(8,'Adidas Alphabounce Instinct Core Black','uploads/','uploads/','uploads/giay-the-thao-nu-mau-den.jpg',1000000,0,'Giày thể thao Nam Nữ màu Đen.',1775289600,'Giày nam',1775289600),(9,'New Balance CRT 300 2.0 Blue Aqua','uploads/96751ff09655560b0f443.jpg','uploads/','uploads/b5fed964d2f212ac4be325.jpg',945000,1000000,'',1775203200,'Giày nam',1775203200),(10,'New Balance CRT 300 2.0 Beige Green','uploads/d828ecd679249cbd5180e03851d0ad4f.jpg','uploads/','uploads/7e5f5dbdd41814464d0912.jpg',1095000,0,'',1775116800,'Giày nam',1775116800),(11,'Dép Crocs Duet Sport Unisex White','uploads/crocs-slipper-saigonsneaker-20.jpg','uploads/crocs-slipper-saigonsneaker-25.jpg','uploads/crocs-slipper-saigonsneaker-7.jpg',1000,0,'',1775030400,'Giày nam',1775030400),(12,'Dép Quai Ngang Nike Classic All Black','uploads/Screenshot-2022-12-21-124813.jpg','uploads/','uploads/Screenshot-2022-12-21-124735.jpg',400000,0,'',1774944000,'Giày nam',1774944000),(13,'Dép Quai Ngang Nike Classic Trắng','uploads/nike-benassi-just-do-it-sandal-343880-3.jpg','uploads/','uploads/p0268186878882-item-9217xf4x0600x0600-m.jpg',500000,0,'',1774857600,'Giày nam',1774857600),(14,'Dép Crocs Bayaband Pink Slides','uploads/IMG_0067-1.jpg','uploads/','uploads/IMG_0071-1.jpg',395000,0,'',1774771200,'Giày nữ',1774771200),(15,'Adidas Originals Adilette 22 Slides Desert Sand','uploads/','uploads/','uploads/dep-adidas-adilette-22-desert-sand-gx6945.jpg',590000,0,'',1774684800,'Giày nam',1774684800),(16,'Nike Men\'s Black Asuna Slide Sandals','uploads/','uploads/','uploads/Nike-Mens-Black-Asuna-Slide-Sandals-04-860x860.png',390000,500000,'',1774598400,'Giày nam',1774598400),(17,'Dép Quai Ngang Nike Benassi Just Do It Black','uploads/IMG_0058.jpg','uploads/','uploads/IMG_1863.jpg',390000,600000,'Dép Nike Benassi JDI Đen.',1774512000,'Giày nam',1774512000),(18,'Nike Men\'s Beige Asuna Slide Sandals','uploads/','uploads/','uploads/Nike-Mens-Beige-Asuna-Slide-Sandals01.png',390000,490000,'',1774425600,'Giày nam',1774425600),(66,'Nike Air Max 270 React Nữ Hồng Pastel','uploads/nike_airmax270_angle2.png','uploads/nike_airmax270_react_pink.png','uploads/nike_airmax270_react_pink.png',1890000,2200000,'Nike Air Max 270 React phiên bản nữ.',1775980800,'Giày nữ',1775936631),(67,'Adidas Superstar Classic White Black Nữ','uploads/adidas_superstar_angle2.png','uploads/adidas_superstar_white.png','uploads/adidas_superstar_white.png',1350000,1600000,'Adidas Superstar - biểu tượng bất hủ.',1775894400,'Giày nữ',1775936631),(68,'Converse Run Star Hike Nữ Platform White','uploads/converse_runstar_angle2.png','uploads/converse_runstar_hike.png','uploads/converse_runstar_hike.png',1650000,1900000,'Converse Run Star Hike platform 4cm.',1775808000,'Giày nữ',1775936631),(69,'Nike ZoomX Vaporfly Next% 2 Racing','uploads/nike_vaporfly_angle2.png','uploads/nike_vaporfly_next.png','uploads/nike_vaporfly_next.png',3500000,4200000,'Nike ZoomX Vaporfly Next% 2.',1775980800,'Giày nam',1775936631),(70,'Adidas Ultraboost 22 Core Black','uploads/adidas_ultraboost_angle2.png','uploads/adidas_ultraboost_black.png','uploads/adidas_ultraboost_black.png',2800000,3500000,'Adidas Ultraboost 22 Core Black.',1775894400,'Giày nam',1775936631),(71,'Puma RS-X Reinvention White Red Blue','uploads/puma_rsx_reinvention.png','uploads/puma_rsx_reinvention.png','uploads/puma_rsx_reinvention.png',1450000,1800000,'Puma RS-X Reinvention retro-futuristic.',1775808000,'Giày nam',1775936631),(72,'Nike Dunk Low Retro Panda Black White','uploads/nike_dunk_low_panda.png','uploads/nike_dunk_low_panda.png','uploads/nike_dunk_low_panda.png',2200000,2800000,'Nike Dunk Low Retro Panda.',1775721600,'Giày nam',1775936631),(73,'Jordan 1 Low Light Smoke Grey','uploads/jordan1_low_smoke_grey.png','uploads/jordan1_low_smoke_grey.png','uploads/jordan1_low_smoke_grey.png',2500000,3200000,'Air Jordan 1 Low Light Smoke Grey.',1775635200,'Giày nam',1775936631),(74,'New Balance 550 White Green','uploads/newbalance_550_green.png','uploads/newbalance_550_green.png','uploads/newbalance_550_green.png',2100000,2600000,'New Balance 550 White Green.',1775548800,'Giày nam',1775936631),(75,'Vans Sk8 Hi Classic Black White','uploads/vans_sk8hi_black.png','uploads/vans_sk8hi_black.png','uploads/vans_sk8hi_black.png',1200000,1500000,'Vans Sk8-Hi cổ cao.',1775462400,'Giày nam',1775936631),(76,'Crocs Classic Clog Lavender Nữ','uploads/crocs_classic_lavender.png','uploads/crocs_classic_lavender.png','uploads/crocs_classic_lavender.png',890000,1100000,'Crocs Classic Clog Lavender.',1775376000,'Giày nữ',1775936631),(77,'Nike Air Force 1 Shadow Nữ Pastel','uploads/nike_af1_shadow_pastel.png','uploads/nike_af1_shadow_pastel.png','uploads/nike_af1_shadow_pastel.png',2400000,2900000,'Nike Air Force 1 Shadow nữ pastel.',1775289600,'Giày nữ',1775936631),(79,'Air Jordan 1 Low Paris','uploads/jordan_paris_angle1.png','uploads/jordan_paris_angle2.png','uploads/jordan_paris_banner.png',3890000,4500000,'Air Jordan 1 Low Paris.',NULL,'Giày nam',1776027843),(80,'Nike LeBron 7','uploads/lebron_7_angle1_1776019613633.png','uploads/lebron_7_angle2_1776019627225.png','uploads/lebron_7_main_1776019598680.png',4200000,5000000,'Nike LeBron 7.',NULL,'Giày nam',1776027853),(83,'Air Jordan 1 High Light Smoke Gray','uploads/jordan1_high_angle1.png','uploads/jordan1_high_angle2.png','uploads/jordan1_high_smoke_gray.jpg',3890000,4500000,'Air Jordan 1 High Light Smoke Gray.',NULL,'Giày nam',1776027863);

-- ============================================================
-- 2. oder
-- ============================================================
CREATE TABLE IF NOT EXISTS `oder` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_code` varchar(9) DEFAULT NULL,
  `tenkh` varchar(255) DEFAULT NULL,
  `diachi` varchar(255) DEFAULT NULL,
  `sdt` varchar(255) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `ngaytao` int(11) DEFAULT NULL,
  `tongtien` int(11) DEFAULT NULL,
  `donhangthang` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'PENDING',
  `payment_status` varchar(20) DEFAULT 'UNPAID',
  `vnpay_tranId` varchar(100) DEFAULT NULL,
  `makh` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `oder_order_code_uq` (`order_code`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT IGNORE INTO `oder` VALUES (6,'805659294','Nguyễn Văn Kiên','số 6 đường Thắng Lợi 1','0394680113','',1775917869,945000,4,'DELIVERED','PAID',NULL,1),(7,'278410495','Nguyễn Văn Kiên','a','0394680113','',1775918564,600000,NULL,'DELIVERED','PAID',NULL,1),(8,'975074622','Nguyễn Văn Kiên','a','0394680113','',1776025821,3890000,NULL,'DELIVERED','PAID',NULL,NULL),(9,'040141662','Nguyễn Văn Kiên','a','0394680113','',1776063382,7780000,NULL,'DELIVERED','PAID',NULL,1);

-- ============================================================
-- 3. oder_chitiet
-- ============================================================
CREATE TABLE IF NOT EXISTS `oder_chitiet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `madonhang` int(11) NOT NULL,
  `masp` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `size` int(11) DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `created_time` int(11) DEFAULT NULL,
  `last_updated` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `1` (`madonhang`),
  KEY `2` (`masp`),
  CONSTRAINT `fk_chitiet_oder` FOREIGN KEY (`madonhang`) REFERENCES `oder` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_chitiet_sp` FOREIGN KEY (`masp`) REFERENCES `tbl_qlsanpham` (`masp`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT IGNORE INTO `oder_chitiet` VALUES (7,6,1,1,NULL,945000,1775917869,1775917869),(8,7,2,1,NULL,600000,1775918564,1775918564),(9,8,79,1,37,3890000,1776025821,1776025821),(10,9,79,2,42,3890000,1776063382,1776063382);

-- ============================================================
-- 4. tbl_dangnhap
-- ============================================================
CREATE TABLE IF NOT EXISTS `tbl_dangnhap` (
  `mavn` int(11) NOT NULL AUTO_INCREMENT,
  `hoten` varchar(255) DEFAULT NULL,
  `taikhoan` varchar(255) DEFAULT NULL,
  `matkhau` varchar(255) DEFAULT NULL,
  `ngaysinh` int(11) DEFAULT NULL,
  `created_time` int(11) DEFAULT NULL,
  `last_updated` int(11) DEFAULT NULL,
  PRIMARY KEY (`mavn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT IGNORE INTO `tbl_dangnhap` VALUES (9,'Nguyễn Văn Kiên','noah2005','kudo-kun',NULL,NULL,NULL);

-- ============================================================
-- 5. tbl_danhmuc
-- ============================================================
CREATE TABLE IF NOT EXISTS `tbl_danhmuc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tendanhmuc` varchar(255) DEFAULT NULL,
  `mota` varchar(500) DEFAULT NULL,
  `ngaytao` int(11) DEFAULT NULL,
  `ngaycapnhat` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT IGNORE INTO `tbl_danhmuc` VALUES (1,'Giày nam','Các loại giày thể thao dành cho nam',1775894400,1775894400),(2,'Giày nữ','Các loại giày thể thao dành cho nữ',1775894400,1775894400),(3,'Dép nam','Các loại dép dành cho nam',1775894400,1775894400),(4,'Phụ kiện','Phụ kiện giày thể thao',1775894400,1775894400),(5,'Sale','Sản phẩm đang giảm giá',1775894400,1775894400);

-- ============================================================
-- 6. tbl_qlthanhvien
-- ============================================================
CREATE TABLE IF NOT EXISTS `tbl_qlthanhvien` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hoten` varchar(255) NOT NULL,
  `gioitinh` varchar(255) DEFAULT NULL,
  `ngaysinh` int(11) DEFAULT NULL,
  `diachicuthe` varchar(255) DEFAULT NULL,
  `tinh` varchar(255) DEFAULT NULL,
  `thanhpho` varchar(255) DEFAULT NULL,
  `phuongxa` varchar(255) DEFAULT NULL,
  `chucvu` varchar(255) DEFAULT NULL,
  `motacongviec` varchar(255) DEFAULT NULL,
  `ngaytao` int(11) DEFAULT NULL,
  `ngaycapnhat` int(11) DEFAULT NULL,
  `taikhoan` varchar(255) DEFAULT NULL,
  `matkhau` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `taikhoan` (`taikhoan`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT IGNORE INTO `tbl_qlthanhvien` VALUES (1,'Nguyễn Văn Kiên','Nam',1119218400,'Hà Nội',NULL,NULL,NULL,'Quản lí','Super Admin',1775183812,1775183812,'noah2005','kudo-kun'),(2,'Đỗ Quang Hà','',1775944800,'',NULL,NULL,NULL,'Quản lí','',1775183812,1775183812,'ha','ha123'),(3,'Nguyễn Bá Nhân','',1775944800,'',NULL,NULL,NULL,'Quản lí','',1775183812,1775183812,'nhan','nhan123');

-- ============================================================
-- 7. tbl_tkkhachhang
-- ============================================================
CREATE TABLE IF NOT EXISTS `tbl_tkkhachhang` (
  `makh` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `hoten` varchar(255) DEFAULT NULL,
  `ngaytao` int(11) DEFAULT NULL,
  PRIMARY KEY (`makh`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT IGNORE INTO `tbl_tkkhachhang` VALUES (1,'test05','kudo-kun','test1',NULL);

-- ============================================================
-- 8. tbl_qlbaidang
-- ============================================================
CREATE TABLE IF NOT EXISTS `tbl_qlbaidang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nguoiphutrach` varchar(255) NOT NULL,
  `anhgiuoithieu1` varchar(255) DEFAULT NULL,
  `anhgiuoithieu2` varchar(255) DEFAULT NULL,
  `anhdaidien` varchar(255) DEFAULT NULL,
  `tieude` varchar(255) DEFAULT NULL,
  `chedo` varchar(255) DEFAULT NULL,
  `noidung` varchar(1000) DEFAULT NULL,
  `ngaytao` int(11) DEFAULT NULL,
  `ngaycapnhat` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT IGNORE INTO `tbl_qlbaidang` VALUES (1,'Kiên','uploads/z2193352750215-3033be33fe89b3df566ee80298de656d.jpg','uploads/nen-mua-giay-the-thao-hang-nao-1(1).jpg','uploads/z2167276892938-024daacff85d3521a82b4eca93f40a4b.jpg','TOP 10+ CÁC HÃNG GIÀY SNEAKER THỂ THAO NỔI TIẾNG BẠN NÊN BIẾT','Hiện','Y-3 là sự kết hợp giữa Yohji Yamamoto và Adidas.',1775980800,1775980800),(2,'Kiên','uploads/cac-thuong-hieu-giay-noi-tieng-1.jpg','uploads/cac-hang-giay-noi-tieng-1.jpg','uploads/nen-mua-giay-the-thao-hang-nao-1.jpg','TOP 10+ CÁC HÃNG GIÀY SNEAKER THỂ THAO NỔI TIẾNG BẠN NÊN BIẾT','Hiện','Y-3 là sự kết hợp giữa Yohji Yamamoto và Adidas.',1775894400,1775894400),(4,'Kiên','uploads/large_Top_5_mau_giay_sneaker_doc_nhat_vo_nhi_02_79071801ce.png','uploads/large_Top_5_mau_giay_sneaker_doc_nhat_vo_nhi_03_e1c0e3f670.png','uploads/large_Top_5_mau_giay_sneaker_doc_nhat_vo_nhi_01_684ae74c17.png','TOP MẪU GIÀY SNEAKER ĐỘC NHẤT VÔ NHỊ','Hiện','Adidas Yeezy Boost 350 V2 Zebra.',1775721600,1775721600),(5,'Kiên','uploads/large_Kham_pha_lich_su_giay_handmade_03_a4ced94f93.jpg','uploads/medium_Kham_pha_lich_su_giay_handmade_04_7aac9a389f.jpg','uploads/large_Kham_pha_lich_su_giay_handmade_03_a4ced94f93.jpg','KHÁM PHÁ LỊCH SỬ GIÀY HANDMADE','Hiện','Giày handmade là những tác phẩm nghệ thuật.',1775635200,1775635200),(6,'Hà','uploads/cac-thuong-hieu-giay-noi-tieng-1.jpg','uploads/nen-mua-giay-the-thao-hang-nao-1.jpg','uploads/cac-hang-giay-noi-tieng-1.jpg','TOP 10+ CÁC HÃNG GIÀY SNEAKER THỂ THAO NỔI TIẾNG BẠN NÊN BIẾT1','Hiện','Y-3 là sự kết hợp giữa Yohji Yamamoto và Adidas.',1775548800,1775548800),(8,'Hà','uploads/large_3_ly_do_vi_sao_giay_chay_bo_khong_day_la_lua_chon_tuyet_voi_01_d8c1965c44.jpg','uploads/medium_3_ly_do_vi_sao_giay_chay_bo_khong_day_la_lua_chon_tuyet_voi_02_00be715a2b.jpg','uploads/medium_3_ly_do_vi_sao_giay_chay_bo_khong_day_la_lua_chon_tuyet_voi_03_cf3477e4a2.png','LÝ DO VÌ SAO GIÀY CHẠY BỘ KHÔNG DÂY LÀ LỰA CHỌN TUYỆT VỜI','Hiện','Sự nhẹ nhàng, linh hoạt là tiêu chí tiên quyết khi chọn giày chạy.',1775376000,1775376000),(10,'Hà','uploads/small_Pharrell_x_BAPE_2006_2a08ff4d00.png','uploads/large_billionaire_boys_club_shoes_ea04d08fcb.png','uploads/Pharell_Williams_054ac1765b.png','MÀN COLLAB ĐÌNH ĐÁM CỦA PHARRELL WILLIAMS','Hiện','Y-3 là sự kết hợp giữa Yohji Yamamoto và Adidas.',1775203200,1775203200),(11,'Nhân','uploads/large_Giay_vegan_2_1a47582d3a.png','uploads/large_Giay_vegan_1_74aa4df9d2.png','uploads/Giay_vegan_7_36fd2bb042.png','THƯƠNG HIỆU GIÀY VEGAN THUẦN CHAY','Hiện','Mỗi năm ngành da toàn cầu sử dụng hơn một tỷ động vật.',1775116800,1775116800),(15,'Nhân','uploads/medium_Khu_mui_do_da_3_bb0a60d048.jpeg','uploads/small_Khu_mui_do_da_2_aa95b6e9ed.jpg','uploads/Khu_mui_do_da_1_793fc08be5.png','NHỮNG CÁCH HIỆU QUẢ GIÚP KHỬ MÙI ĐỒ DA','Hiện','Đồ da của bạn có mùi hơi khó chịu?',1774771200,1774771200);

-- ============================================================
-- 9. tbl_thuvienanh
-- ============================================================
CREATE TABLE IF NOT EXISTS `tbl_thuvienanh` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `masp` int(11) NOT NULL,
  `path` varchar(255) DEFAULT NULL,
  `ngaytao` int(11) DEFAULT NULL,
  `ngaycapnhat` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_thuvienanh_sp` (`masp`),
  CONSTRAINT `fk_thuvienanh_sp` FOREIGN KEY (`masp`) REFERENCES `tbl_qlsanpham` (`masp`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ============================================================
-- 10. tbl_tonkho (inventory)
-- ============================================================
CREATE TABLE IF NOT EXISTS `tbl_tonkho` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `masp` INT(11) NOT NULL,
  `size` INT(11) NOT NULL,
  `soluong` INT(11) NOT NULL DEFAULT 0,
  `ngaytao` INT(11) DEFAULT NULL,
  `ngaycapnhat` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_masp_size` (`masp`, `size`),
  CONSTRAINT `fk_tonkho_sanpham` FOREIGN KEY (`masp`)
    REFERENCES `tbl_qlsanpham` (`masp`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Seed tồn kho: 20 đơn vị/size cho mỗi sản phẩm
INSERT IGNORE INTO `tbl_tonkho` (`masp`, `size`, `soluong`, `ngaytao`, `ngaycapnhat`)
SELECT sp.masp, s.size_val, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `tbl_qlsanpham` sp
CROSS JOIN (
  SELECT 36 AS size_val UNION ALL SELECT 37 UNION ALL SELECT 38 UNION ALL SELECT 39
  UNION ALL SELECT 40 UNION ALL SELECT 41 UNION ALL SELECT 42 UNION ALL SELECT 43 UNION ALL SELECT 44
) s;

/*!40014 SET FOREIGN_KEY_CHECKS=1 */;
