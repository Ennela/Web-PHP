# Software Requirement Specification (SRS)
## Chß╗⌐c n─âng: Quß║ún l├╜ Tin tß╗⌐c (B├ái ─æ─âng)
**M├ú chß╗⌐c n─âng:** ADMIN-NEWS-01  
**Trß║íng th├íi:** Draft / Review  
**Ng╞░ß╗¥i soß║ín thß║úo:** ─Éß╗Öi ng┼⌐ ph├ít triß╗ân

---

### 1. M├┤ tß║ú tß╗òng quan (Description)
Quß║ún trß╗ï vi├¬n (Admin) ─æ╞░ß╗úc cß║Ñp quyß╗ün tß║ío mß╗¢i, chß╗ënh sß╗¡a v├á quß║ún l├╜ c├íc b├ái ─æ─âng hiß╗ân thß╗ï tr├¬n hß╗ç thß╗æng. Dß╗» liß╗çu ─æ╞░ß╗úc l╞░u trß╗» tß║íi bß║úng `tbl_qlbaidang` v├á li├¬n kß║┐t vß╗¢i ng╞░ß╗¥i phß╗Ñ tr├ích (thuß╗Öc bß║úng th├ánh vi├¬n). Quß║ún trß╗ï vi├¬n c├│ thß╗â ─æiß╗üu khiß╗ân viß╗çc ß║¿n/Hiß╗çn b├ái viß║┐t.

### 2. Luß╗ông nghiß╗çp vß╗Ñ (User Workflow)
| B╞░ß╗¢c | H├ánh ─æß╗Öng Admin | Phß║ún hß╗ôi hß╗ç thß╗æng |
| :--- | :--- | :--- |
| 1 | Bß║Ñm chß╗ìn menu "Quß║ún l├╜ B├ái Viß║┐t" (`quanlibaidang.php`) | Khß╗ƒi chß║íy bß║úng HTML cung cß║Ñp danh s├ích mß╗ìi b├ái b├ío ─æ├ú viß║┐t (`SELECT * FROM tbl_qlbaidang`). |
| 2 | Chß╗ìn n├║t lß╗çnh "Th├¬m B├ái Viß║┐t" (`thembaidang.php`) | Cung cß║Ñp giao diß╗çn Form vß╗¢i c├íc tr╞░ß╗¥ng T├¬n b├ái, ß║únh rß╗ìi, Nß╗Öi dung, Chß║┐ ─æß╗Ö. |
| 3 | Chß╗ìn tr╞░ß╗¥ng dß╗» liß╗çu v├á nhß║Ñn L╞░u | Hß╗ç thß╗æng tß║úi file ß║únh l├¬n m├íy chß╗º (th╞░ mß╗Ñc `uploads/` cß╗ºa Admin). |
| 4 | *Tr╞░ß╗¥ng hß╗úp th├ánh c├┤ng:* L╞░u v├áo kho | Gß╗ìi h├ám `INSERT INTO tbl_qlbaidang` vß╗¢i dß╗» liß╗çu lß║Ñy trß╗▒c tiß║┐p tß╗½ `$_POST`. |

### 3. Y├¬u cß║ºu dß╗» liß╗çu (Data Requirements)
#### 3.1. Dß╗» liß╗çu chß╗ënh sß╗¡a (Input Control)
* **Ti├¬u ─æß╗ü tin tß╗⌐c (`tieude`):** T├¬n cß╗ºa b├ái ─æ─âng.
* **Ng╞░ß╗¥i phß╗Ñ tr├ích (`nguoiphutrach`):** T├¬n cß╗ºa ng╞░ß╗¥i d├╣ng Admin (G├ín cß╗⌐ng qua danh s├ích xß╗ò xuß╗æng).
* **T├ái nguy├¬n ß║ónh:** 3 File h├¼nh ß║únh gß╗ôm `anhdaidien`, `anhgioithieu1`, `anhgioithieu2`.
* **Chß║┐ ─æß╗Ö hiß╗ân thß╗ï (`chedo`):** C├íc gi├í trß╗ï nh╞░ "Hiß╗çn", "ß║¿n".
* **Nß╗Öi dung Chi tiß║┐t (`noidung`):** Khung nhß║¡p v─ân bß║ún nß╗Öi dung.

### 4. R├áng buß╗Öc kß╗╣ thuß║¡t & Bß║úo mß║¡t (Technical Constraints)
* **X├íc thß╗▒c phi├¬n (Session):** Chß╗⌐c n─âng chß╗ë c├│ thß╗â ─æ╞░ß╗úc gß╗ìi nß║┐u Session admin vß║½n c├▓n gi├í trß╗ï.
* **Tß║úi tß╗çp tin Uploads:** H├¼nh ß║únh sß║╜ nß║▒m chung th╞░ mß╗Ñc `admin/uploads/`. Cß║ºn lß╗ìc kß╗╣ phß║ºn ─æu├┤i ─æß╗ïnh dß║íng ─æß╗â tr├ính lß╗ìt file `.php` r├íc.

### 5. Tr╞░ß╗¥ng hß╗úp ngoß║íi lß╗ç & Xß╗¡ l├╜ lß╗ùi (Edge Cases)
* **Tr╞░ß╗¥ng hß╗úp:** X├│a mß╗Öt b├ái ─æ─âng (`delete_qlbaidang.php`).
  * **Xß╗¡ l├╜:** X├│a trß╗▒c tiß║┐p `DELETE FROM tbl_qlbaidang WHERE id=?`. C├íc tß╗çp h├¼nh ß║únh c├▓n l╞░u trong thiß║┐t bß╗ï l╞░u trß╗» tr├¬n ß╗ò cß╗⌐ng (th╞░ mß╗Ñc `uploads`) c├│ thß╗â kh├┤ng bß╗ï x├│a mß╗üm tß╗▒ ─æß╗Öng qua SQL, cß║ºn c├│ l╞░u ├╜ dß╗ìn r├íc thß╗º c├┤ng (nß║┐u cß║ºn).

### 6. Giao diß╗çn (UI/UX)
* Khung Form soß║ín thß║úo HTML truyß╗ün thß╗æng gß╗ôm c├íc nh├│m Inputs (Label v├á Control) ─æi k├¿m.