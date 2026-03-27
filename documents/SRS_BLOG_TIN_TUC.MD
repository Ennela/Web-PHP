# Software Requirement Specification (SRS)
## Chß╗⌐c n─âng: ─Éß╗ìc B├ái Viß║┐t / Tin Tß╗⌐c
**M├ú chß╗⌐c n─âng:** BLOG-NEWS-01  
**Trß║íng th├íi:** Draft / Review  
**Ng╞░ß╗¥i soß║ín thß║úo:** ─Éß╗Öi ng┼⌐ ph├ít triß╗ân

---

### 1. M├┤ tß║ú tß╗òng quan (Description)
Khu vß╗▒c cung cß║Ñp c├íc b├ái viß║┐t chuy├¬n ─æß╗ü hiß╗ân thß╗ï cho ng╞░ß╗¥i d├╣ng ngo├ái giao diß╗çn (Frontend). Ng╞░ß╗¥i d├╣ng truy cß║¡p trang `baiviet.php` ─æß╗â xem danh s├ích tß╗òng hß╗úp, v├á trang `chitietbaiviet.php` ─æß╗â ─æß╗ìc c├íc nß╗Öi dung ─æß║ºy ─æß╗º.

### 2. Luß╗ông nghiß╗çp vß╗Ñ (User Workflow)
| B╞░ß╗¢c | H├ánh ─æß╗Öng ng╞░ß╗¥i d├╣ng | Phß║ún hß╗ôi hß╗ç thß╗æng |
| :--- | :--- | :--- |
| 1 | Nhß║Ñn chß╗ìn mß╗Ñc "Bß║ún tin" hoß║╖c "Vß╗ü ch├║ng t├┤i" | Truy xuß║Ñt file `baiviet.php` v├á chß║íy lß╗çnh r├║t tr├¡ch `SELECT * FROM tbl_qlbaidang WHERE chedo='Hiß╗çn'`. |
| 2 | Xem danh s├ích tin (Listing) | Hß╗ç thß╗æng In v├▓ng lß║╖p c├íc khß╗æi Card chß╗⌐a ß║ónh ─æß║íi diß╗çn nhß╗Å v├á Ti├¬u ─æß╗ü b├ái viß║┐t. |
| 3 | Click v├áo thß║╗ "─Éß╗ìc th├¬m" | ─Éiß╗üu h╞░ß╗¢ng m├án h├¼nh chuyß╗ân sang trang ─æß╗ìc b├ái chi tiß║┐t `chitietbaiviet.php?id=XXX`. |

### 3. Y├¬u cß║ºu dß╗» liß╗çu (Data Requirements)
#### 3.1. Dß╗» liß╗çu Hiß╗ân thß╗ï (Data Outputs)
* **Danh s├ích t├│m l╞░ß╗úc:** Chß╗ë tß║úi ra `tieude`, `anhdaidien` v├á ─æoß║ín chß╗» `noidung` bß╗ï cß║»t ngß║»n lß║íi.
* **Chi Tiß║┐t:** Hiß╗ân thß╗ï `tieude` tß║íi thß║╗ Title ch├│p m├án h├¼nh, hiß╗ân thß╗ï nß╗Öi dung to├án v─ân `noidung` v├á bß╗ò tr├¡ c├íc ß║únh `anhgioithieu1`, `anhgioithieu2` xen kß║╜.

### 4. R├áng buß╗Öc kß╗╣ thuß║¡t & Bß║úo mß║¡t (Technical Constraints)
* **Lß╗ìc ─æiß╗üu kiß╗çn `chedo`:** Bß║»t buß╗Öc mß╗çnh ─æß╗ü truy vß║Ñn lu├┤n phß║úi c├│ bß╗Ö lß╗ìc loß║íi bß╗Å gi├í trß╗ï `chedo='ß║¿n'` ─æß╗â ─æß║úm bß║úo Admin c├│ quyß╗ün giß║Ñu b├ái nh├íp.
* **Tß╗æi ╞░u Tß║úi Form:** Truyß╗ün tß║úi nß╗Öi dung v─ân bß║ún mß╗Öt c├ích nguy├¬n bß║ún ─æß╗â bß║úo vß╗ç cß║Ñu tr├║c ─æoß║ín v─ân bß║ún/k├╜ tß╗▒ xuß╗æng d├▓ng cß╗ºa ng╞░ß╗¥i bi├¬n tß║¡p.

### 5. Tr╞░ß╗¥ng hß╗úp ngoß║íi lß╗ç & Xß╗¡ l├╜ lß╗ùi (Edge Cases)
* **Tr╞░ß╗¥ng hß╗úp:** ID b├ái viß║┐t truyß╗ün v├áo ─æ╞░ß╗¥ng dß║½n URL (v├¡ dß╗Ñ `?id=999`) kh├┤ng tß╗ôn tß║íi trong CSDL.
  * **Xß╗¡ l├╜:** Trang chi tiß║┐t sß║╜ bß╗ï ─æß╗ò rß╗ùng dß╗» liß╗çu m├┤ tß║ú v├¼ truy vß║Ñn `mysqli_fetch_array` trß║ú vß╗ü null (c├│ thß╗â g├óy ra lß╗ùi vß╗í nhß╗Å giao diß╗çn).

### 6. Giao diß╗çn (UI/UX)
* ß╗₧ m├án h├¼nh danh s├ích, sß╗¡ dß╗Ñng hß╗ç thß╗æng L╞░ß╗¢i tß╗▒ ─æß║⌐y card theo Responsive (vd: Bootstrap). 
* ß╗₧ m├án h├¼nh chi tiß║┐t, c─ân lß╗ü v─ân bß║ún vß╗½a phß║úi vß╗¢i khung h├¼nh ß║únh lß╗¢n d├ún nß╗ƒ 100% ─æß╗Ö rß╗Öng (Width 100%).