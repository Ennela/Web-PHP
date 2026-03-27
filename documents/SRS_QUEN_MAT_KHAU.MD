# Software Requirement Specification (SRS)
## Chß╗⌐c n─âng: Kh├┤i phß╗Ñc Mß║¡t Khß║⌐u
**M├ú chß╗⌐c n─âng:** PWD-RCV-01  
**Trß║íng th├íi:** Draft / Review  
**Ng╞░ß╗¥i soß║ín thß║úo:** ─Éß╗Öi ng┼⌐ ph├ít triß╗ân

---

### 1. M├┤ tß║ú tß╗òng quan (Description)
Trang Qu├¬n mß║¡t khß║⌐u (V├¡ dß╗Ñ nh╞░ thiß║┐t lß║¡p `admin/quenmatkhau.php`) cung cß║Ñp mß╗Öt form nh├íp ban ─æß║ºu trong quy tr├¼nh lß║Ñy lß║íi th├┤ng tin truy cß║¡p. Tß║íi cß║Ñu tr├║c hiß╗çn h├ánh, file `quenmatkhau.php` mß╗¢i chß╗ë dß╗▒ng khung Giao Diß╗çn t─⌐nh chß╗⌐ ch╞░a c├│ code xß╗¡ l├╜ PHP ch├¿n m├ú phß╗Ñc hß╗ôi OTP nß╗Öi bß╗Ö.

### 2. Luß╗ông nghiß╗çp vß╗Ñ (User Workflow)
| B╞░ß╗¢c | H├ánh ─æß╗Öng ng╞░ß╗¥i d├╣ng | Phß║ún hß╗ôi hß╗ç thß╗æng |
| :--- | :--- | :--- |
| 1 | Thß║Ñt lß║íc mß║¡t m├ú, cß║ºn t├¼m khung hß╗ù trß╗ú tß║íi trang ─É─âng nhß║¡p | Hß╗ç thß╗æng cung cß║Ñp Li├¬n kß║┐t "Qu├¬n mß║¡t khß║⌐u?". |
| 2 | Chuyß╗ân tß╗¢i `quenmatkhau.php` | Tr├¼nh duyß╗çt nß║íp giao diß╗çn t─⌐nh mß╗Öt khung Form ─æiß╗ün Email. |
| 3 | (Trong Phi├¬n Bß║ún T╞░╞íng Lai) ─Éiß╗ün Cß╗⌐u lß║Ñy File Li├¬n Hß╗ç | - Ch╞░a k├¡ch hoß║ít luß╗ông bß║»n Email x├íc thß╗▒c - |

### 3. Y├¬u cß║ºu dß╗» liß╗çu (Data Requirements)
* **Form dß╗» liß╗çu:** Textbox chß╗⌐a m├ú ─æß╗ïa chß╗ë Email.

### 4. R├áng buß╗Öc kß╗╣ thuß║¡t & Bß║úo mß║¡t (Technical Constraints)
* **Trß║íng th├íi t├¡nh n─âng (Limitation):** V├╣ng chß╗⌐c n─âng n├áy nß║▒m ß╗ƒ mß║úng giao diß╗çn Front-end Dummy (Chß╗ë c├│ HTML/CSS khung Form Bootstrap chß╗⌐ kh├┤ng c├│ c├óu lß╗çnh `SELECT` ─æß╗òi Password bß║▒ng PHP).

### 5. Tr╞░ß╗¥ng hß╗úp ngoß║íi lß╗ç & Xß╗¡ l├╜ lß╗ùi (Edge Cases)
* **Tr╞░ß╗¥ng hß╗úp:** Lß║ím dß╗Ñng bß║Ñm n├║t Ghi nhß║¡n Form.
  * **Xß╗¡ l├╜:** Trang web sß║╜ ─æß╗⌐ng y├¬n hoß║╖c Refressh tß║úi trß╗æng v├¼ ch╞░a nß╗æi kß║┐t ─æ╞░ß╗¥ng hß║ºm POST v├áo Server.

### 6. Giao diß╗çn (UI/UX)
* Giao diß╗çn HTML thß╗½a h╞░ß╗ƒng Template chung vß╗¢i trang Login/Register cß╗ºa hß╗ç thß╗æng Bootstrap.