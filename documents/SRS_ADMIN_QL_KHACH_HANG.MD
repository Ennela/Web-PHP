# Software Requirement Specification (SRS)
## Chß╗⌐c n─âng: Quß║ún l├╜ Kh├ích h├áng v├á Ng╞░ß╗¥i d├╣ng
**M├ú chß╗⌐c n─âng:** ADMIN-USER-01  
**Trß║íng th├íi:** Draft / Review  
**Ng╞░ß╗¥i soß║ín thß║úo:** ─Éß╗Öi ng┼⌐ ph├ít triß╗ân

---

### 1. M├┤ tß║ú tß╗òng quan (Description)
Hß╗ç thß╗æng cho ph├⌐p Quß║ún trß╗ï vi├¬n xem danh s├ích c├íc t├ái khoß║ún kh├ích h├áng (`tbl_tkkhachhang`) v├á ng╞░ß╗¥i d├╣ng nß╗Öi bß╗Ö/th├ánh vi├¬n quß║ún trß╗ï (`tbl_qlthanhvien`). Cho ph├⌐p x├│a bß╗Å c├íc t├ái khoß║ún vi phß║ím hoß║╖c tß║ío th├¬m th├ánh vi├¬n ban quß║ún trß╗ï.

### 2. Luß╗ông nghiß╗çp vß╗Ñ (User Workflow)
| B╞░ß╗¢c | H├ánh ─æß╗Öng Admin | Phß║ún hß╗ôi hß╗ç thß╗æng |
| :--- | :--- | :--- |
| 1 | Bß║Ñm chß╗ìn xem "Quß║ún l├╜ Kh├ích h├áng" (`quanlikhachhang.php`) hoß║╖c "Quß║ún l├╜ Th├ánh vi├¬n" (`quanlithanhvien.php`) | Bß║úng HTML hiß╗ân thß╗ï danh s├ích tß╗½ CSDL t╞░╞íng ß╗⌐ng. |
| 2 | Vß╗¢i Kh├ích h├áng: Nhß║Ñn X├│a (`delete_qlkhachhang.php`) | X├│a kh├ích h├áng bß║▒ng `DELETE FROM tbl_tkkhachhang`. |
| 3 | Vß╗¢i Th├ánh vi├¬n: Nhß║Ñn Th├¬m mß╗¢i hoß║╖c Sß╗¡a (`themthanhvien.php` / `edit_qlthanhvien.php`) | Cho ph├⌐p cß║¡p nhß║¡t th├┤ng tin Chß╗⌐c vß╗Ñ (Quß║ún l├¡, Nh├ón vi├¬n), t├¬n, mß║¡t khß║⌐u l╞░u dß║íng chß╗» thuß║ºn (Plaintext). |

### 3. Y├¬u cß║ºu dß╗» liß╗çu (Data Requirements)
#### 3.1. ─Éß╗æi vß╗¢i kh├ích h├áng (T├ái khoß║ún Shop)
* **Bß║úng `tbl_tkkhachhang`:** `makh` (ID), `username`, `password`, `hoten`.

#### 3.2. ─Éß╗æi vß╗¢i Th├ánh vi├¬n nß╗Öi bß╗Ö (Admin)
* **Bß║úng `tbl_qlthanhvien`:** Chß╗⌐a chi tiß║┐t h╞ín bao gß╗ôm li├¬n hß╗ç, `chucvu` (Role), `taikhoan`, `matkhau`.

### 4. R├áng buß╗Öc kß╗╣ thuß║¡t & Bß║úo mß║¡t (Technical Constraints)
* **Phi├¬n bß║úo mß║¡t Admin:** To├án bß╗Ö file dß║íng `delete_` hay `edit_` ─æß╗üu bß╗ï giß║Ñu k├¡n sau lß╗¢p kiß╗âm tra Auth cß╗ºa file `connect_db`. 
* **Mß║¡t khß║⌐u c╞í bß║ún:** Dß╗▒ ├ín hiß╗çn ─æang l╞░u trß╗» Mß║¡t khß║⌐u dß║íng nguy├¬n bß║ún (Plaintext) tß║íi cß╗Öt `password` thay v├¼ m├ú h├│a.

### 5. Tr╞░ß╗¥ng hß╗úp ngoß║íi lß╗ç & Xß╗¡ l├╜ lß╗ùi (Edge Cases)
* **Tr╞░ß╗¥ng hß╗úp:** Admin x├│a t├ái khoß║ún kh├ích h├áng, nh╞░ng kh├ích ─æ├│ ─æ├ú tß╗½ng mua h├áng.
  * **Xß╗¡ l├╜:** Viß╗çc x├│a kh├ích h├áng (`tbl_tkkhachhang`) kh├┤ng ß║únh h╞░ß╗ƒng tß╗¢i bß║úng ─æ╞ín h├áng (`oder`) v├¼ bß║úng `oder` l╞░u trß╗▒c tiß║┐p t├¬n kh├ích d╞░ß╗¢i dß║íng chß╗» phß╗Ñ ─æß╗Öc lß║¡p chß╗⌐ kh├┤ng x├ái r├ánh buß╗Öc kh├│a ngoß║íi makh.

### 6. Giao diß╗çn (UI/UX)
* Ph├ón ─æß╗ïnh 2 menu r├╡ r├áng: mß╗Öt cho Ng╞░ß╗¥i d├╣ng web, mß╗Öt cho Nh├ón vi├¬n quß║ún trß╗ï hß╗ç thß╗æng. 