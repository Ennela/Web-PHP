# Software Requirement Specification (SRS)
## Chß╗⌐c n─âng: Admin Dashboard (Tß╗òng quan)
**M├ú chß╗⌐c n─âng:** ADMIN-DASH-01  
**Trß║íng th├íi:** Draft / Review  
**Ng╞░ß╗¥i soß║ín thß║úo:** ─Éß╗Öi ng┼⌐ ph├ít triß╗ân

---

### 1. M├┤ tß║ú tß╗òng quan (Description)
Hß╗ç thß╗æng cung cß║Ñp mß╗Öt bß║úng ─æiß╗üu khiß╗ân trung t├óm (Dashboard) d├ánh ri├¬ng cho Quß║ún trß╗ï vi├¬n tß║íi ─æ╞░ß╗¥ng dß║½n `admin/trangchu.php`. Chß╗⌐c n─âng n├áy gi├║p Admin theo d├╡i c├íc chß╗ë sß╗æ c╞í bß║ún nh╞░ sß╗æ l╞░ß╗úng ─æ╞ín h├áng ─æ├ú ─æß║╖t, sß╗æ l╞░ß╗úng sß║ún phß║⌐m ─æang b├ín, tß╗òng sß╗æ th├ánh vi├¬n v├á sß╗æ l╞░ß╗úng b├ái viß║┐t.

### 2. Luß╗ông nghiß╗çp vß╗Ñ (User Workflow)
| B╞░ß╗¢c | H├ánh ─æß╗Öng Admin | Phß║ún hß╗ôi hß╗ç thß╗æng |
| :--- | :--- | :--- |
| 1 | Truy cß║¡p v├áo `admin/index.php` hoß║╖c `admin/trangchu.php` | Hß╗ç thß╗æng kiß╗âm tra session ─æ─âng nhß║¡p admin (`$_SESSION['admin']`). |
| 2 | ─É─âng nhß║¡p th├ánh c├┤ng | Hiß╗ân thß╗ï giao diß╗çn trang chß╗º Admin. |
| 3 | Xem c├íc thß║╗ t├│m tß║»t sß╗æ liß╗çu (Cards) | Hß╗ç thß╗æng thß╗▒c thi c├íc c├óu truy vß║Ñn `COUNT` tß╗½ c├íc bß║úng `oder`, `tbl_qlsanpham`, `tbl_tkkhachhang` ─æß╗â in con sß╗æ. |

### 3. Y├¬u cß║ºu dß╗» liß╗çu (Data Requirements)
#### 3.1. Dß╗» liß╗çu ─æß╗æi chiß║┐u
* **Bß║úng ─É╞ín h├áng (`oder`):** `SELECT COUNT(id) FROM oder;`
* **Bß║úng Kh├ích h├áng (`tbl_tkkhachhang`):** `SELECT COUNT(makh) FROM tbl_tkkhachhang;`
* **Bß║úng Sß║ún phß║⌐m (`tbl_qlsanpham`):** `SELECT COUNT(masp) FROM tbl_qlsanpham;`

### 4. R├áng buß╗Öc kß╗╣ thuß║¡t & Bß║úo mß║¡t (Technical Constraints)
* **X├íc thß╗▒c phi├¬n l├ám viß╗çc:** Bß║»t buß╗Öc phß║úi c├│ session admin tß╗ôn tß║íi, nß║┐u kh├┤ng sß║╜ tß╗▒ ─æß╗Öng redirect vß╗ü trang ─æ─âng nhß║¡p.

### 5. Tr╞░ß╗¥ng hß╗úp ngoß║íi lß╗ç & Xß╗¡ l├╜ lß╗ùi (Edge Cases)
* **Tr╞░ß╗¥ng hß╗úp:** CSDL bß╗ï rß╗ùng (ch╞░a c├│ ─æ╞ín h├áng hay kh├ích h├áng).
  * **Xß╗¡ l├╜:** C├íc c├óu truy vß║Ñn `COUNT` trß║ú vß╗ü 0 v├á hiß╗ân thß╗ï b├¼nh th╞░ß╗¥ng, kh├┤ng g├óy lß╗ùi logic (Exception).

### 6. Giao diß╗çn (UI/UX)
* Thiß║┐t kß║┐ sß╗¡ dß╗Ñng c├íc khß╗æi thß║╗ m├áu sß║»c nß╗òi bß║¡t (Blue, Green, Red, Yellow) ─æß╗â l├ám nß╗òi bß║¡t c├íc con sß╗æ thß╗æng k├¬.