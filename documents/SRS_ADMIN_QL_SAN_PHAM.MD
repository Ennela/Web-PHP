# Software Requirement Specification (SRS)
## Chß╗⌐c n─âng: Quß║ún l├╜ Sß║ún phß║⌐m
**M├ú chß╗⌐c n─âng:** ADMIN-PROD-01  
**Trß║íng th├íi:** Draft / Review  
**Ng╞░ß╗¥i soß║ín thß║úo:** ─Éß╗Öi ng┼⌐ ph├ít triß╗ân

---

### 1. M├┤ tß║ú tß╗òng quan (Description)
Hß╗ç thß╗æng cho ph├⌐p Quß║ún trß╗ï vi├¬n (Admin) tß║ío mß╗¢i, chß╗ënh sß╗¡a th├┤ng tin hoß║╖c x├│a bß╗Å c├íc sß║ún phß║⌐m gi├áy d├⌐p (`quanlisanpham.php`). Chß╗⌐c n─âng y├¬u cß║ºu nß║íp h├¼nh ß║únh ch├¡nh v├á ß║únh phß╗Ñ, ─æß╗ïnh gi├í sß║ún phß║⌐m (gi├í b├ín/gi├í gß╗æc) v├á ─æiß╗ün trß╗▒c tiß║┐p t├¬n Nh├│m sß║ún phß║⌐m (nhomsp). Cß║Ñu tr├║c hiß╗çn tß║íi kh├┤ng y├¬u cß║ºu quß║ún l├╜ sß╗æ l╞░ß╗úng tß╗ôn kho hay ph├ón loß║íi cß╗í k├¡ch size (Variants).

### 2. Luß╗ông nghiß╗çp vß╗Ñ (User Workflow)
| B╞░ß╗¢c | H├ánh ─æß╗Öng Admin | Phß║ún hß╗ôi hß╗ç thß╗æng |
| :--- | :--- | :--- |
| 1 | Truy cß║¡p "Quß║ún l├╜ Sß║ún phß║⌐m" | Hß╗ç thß╗æng Load bß║úng k├¬ h├áng h├│a (`tbl_qlsanpham`). |
| 2 | Nhß║Ñn "Th├¬m sß║ún phß║⌐m mß╗¢i" (`themsanpham.php`) | M├án h├¼nh Form nhß║¡p liß╗çu vß╗¢i c├íc tr╞░ß╗¥ng (T├¬n, ß║ónh 1, 2, 3, Gi├í gß╗æc, Gi├í b├ín, M├┤ tß║ú, Nh├│m SP). |
| 3 | Khai b├ío v├á ß║Ñn L╞░u | ß╗¿ng dß╗Ñng tß║úi 3 h├¼nh ß║únh l├¬n th╞░ mß╗Ñc `uploads/` tr├¬n m├íy chß╗º sß╗¡ dß╗Ñng h├ám `move_uploaded_file()`. |
| 4 | *Tr╞░ß╗¥ng hß╗úp th├ánh c├┤ng:* L╞░u v├áo kho | Ch├¿n th├┤ng sß╗æ bao gß╗ôm ─æ╞░ß╗¥ng dß║½n ß║únh v├áo CSDL (`INSERT INTO tbl_qlsanpham`). |

### 3. Y├¬u cß║ºu dß╗» liß╗çu (Data Requirements)
#### 3.1. Dß╗» liß╗çu ─Éß║ºu v├áo (Input Fields)
* **T├¬n sß║ún phß║⌐m (`tensp`):** T├¬n ─æß╗ïnh danh hiß╗ân thß╗ï.
* **Nh├│m Sß║ún Phß║⌐m (`nhomsp`):** Chuß╗ùi gi├í trß╗ï tß╗▒ do (VD: "Gi├áy nam", "Gi├áy nß╗»").
* **T├ái nguy├¬n ß║únh:** Hß╗ù trß╗ú cß╗⌐ng ─æ├║ng 03 ├┤ upload ß║únh (ß║ónh ─æß║íi diß╗çn, ß║ónh phß╗Ñ 1, ß║ónh phß╗Ñ 2). Ghi lß║íi ─æ╞░ß╗¥ng dß║½n t╞░╞íng ─æß╗æi (V├¡ dß╗Ñ: `uploads/giay.jpg`).
* **Gi├í cß║ú:** `giasanpham` (Gi├í thanh to├ín) v├á `giagoc` (Gi├í ß║úo gß║ích ngang ─æß╗æi chß╗⌐ng hiß╗ân thß╗ï). 
* **Nß╗Öi dung (`noidung`):** Thß║╗ ─æoß║ín v─ân m├┤ tß║ú dß║íng Text/HTML.

### 4. R├áng buß╗Öc kß╗╣ thuß║¡t & Bß║úo mß║¡t (Technical Constraints)
* **Xß╗¡ l├╜ th╞░ mß╗Ñc Uploads:** C├íc tß╗çp h├¼nh ß║únh tß║úi l├¬n sß║╜ trß╗▒c tiß║┐p l╞░u h├ánh v├áo th╞░ mß╗Ñc gß╗æc `admin/uploads/`.
* **X├│a R├áng Buß╗Öc Li├¬n Kß║┐t:** Trong SQL, bß║úng `oder_chitiet` c├│ kh├│a ngoß║íi trß╗Å tß╗¢i `tbl_qlsanpham(masp) ON DELETE CASCADE`. Do ─æ├│ viß╗çc x├│a mß╗Öt sß║ún phß║⌐m trong kho (`delete_qlsanpham.php`) c┼⌐ng sß║╜ tß╗▒ ─æß╗Öng x├│a sß║ích c├íc d├▓ng lß╗ïch sß╗¡ mua m├│n h├áng n├áy ß╗ƒ c├íc ─æ╞ín c┼⌐.

### 5. Tr╞░ß╗¥ng hß╗úp ngoß║íi lß╗ç & Xß╗¡ l├╜ lß╗ùi (Edge Cases)
* **Tr╞░ß╗¥ng hß╗úp:** Admin kh├┤ng nhß║¡p ─æß╗º 3 bß╗⌐c ß║únh m├á chß╗ë nhß║¡p ß║únh ─Éß║íi diß╗çn.
  * **Xß╗¡ l├╜:** Hß╗ç thß╗æng chß║Ñp nhß║¡n th├┤ng qua, biß║┐n l╞░u trß╗» ─æ╞░ß╗¥ng dß║½n ß║únh phß╗Ñ bß║▒ng mß║úng trß╗æng. ß╗₧ ph├¡a giao diß╗çn ng╞░ß╗¥i d├╣ng nß║┐u ß║únh rß╗ùng th├¼ sß║╜ tß╗▒ bß╗Å mß║úng (hoß║╖c b├ío lß╗ùi vß╗í nhß╗Å).

### 6. Giao diß╗çn (UI/UX)
* Ph├ón chia chia m├án h├¼nh Form r├╡ r├áng hß╗ù trß╗ú c├íc thß║╗ `<input type="file">` ri├¬ng lß║╗. Kh├┤ng ├íp dß╗Ñng k├⌐o/thß║ú.