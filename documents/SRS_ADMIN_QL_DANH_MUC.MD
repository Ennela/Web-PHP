# Software Requirement Specification (SRS)
## Chß╗⌐c n─âng: Quß║ún l├╜ Danh mß╗Ñc (Nh├│m sß║ún phß║⌐m)
**M├ú chß╗⌐c n─âng:** ADMIN-CAT-01  
**Trß║íng th├íi:** Draft / Review  
**Ng╞░ß╗¥i soß║ín thß║úo:** ─Éß╗Öi ng┼⌐ ph├ít triß╗ân

---

### 1. M├┤ tß║ú tß╗òng quan (Description)
Hß╗ç thß╗æng cho ph├⌐p Quß║ún trß╗ï vi├¬n ph├ón loß║íi sß║ún phß║⌐m v├áo c├íc nh├│m kh├íc nhau th├┤ng qua bß║úng ─æiß╗üu khiß╗ân. Trong cß║Ñu tr├║c dß╗» liß╗çu hiß╗çn tß║íi, danh mß╗Ñc ─æ╞░ß╗úc l╞░u trß╗» trß╗▒c tiß║┐p th├┤ng qua tr╞░ß╗¥ng `nhomsp` cß╗ºa bß║úng sß║ún phß║⌐m hoß║╖c danh s├ích cß╗⌐ng.

### 2. Luß╗ông nghiß╗çp vß╗Ñ (User Workflow)
| B╞░ß╗¢c | H├ánh ─æß╗Öng Admin | Phß║ún hß╗ôi hß╗ç thß╗æng |
| :--- | :--- | :--- |
| 1 | Truy cß║¡p "Quß║ún l├╜ Danh mß╗Ñc" (`quanlidanhmuc.php`) | Hiß╗ân thß╗ï bß║úng danh s├ích c├íc danh mß╗Ñc ─æang tß╗ôn tß║íi cß╗ºa nß╗ün tß║úng website. |
| 2 | Chß╗ìn t├¡nh n─âng "Th├¬m mß╗¢i" (`themdanhmuc.php`) | Mß╗ƒ biß╗âu mß║½u (Form) ─æiß╗ün t├¬n danh mß╗Ñc. |
| 3 | ─Éiß╗ün t├¬n danh mß╗Ñc v├á nhß║Ñn L╞░u | Tiß║┐n h├ánh kiß╗âm tra x├íc thß╗▒c. |

### 3. Y├¬u cß║ºu dß╗» liß╗çu (Data Requirements)
#### 3.1. Dß╗» liß╗çu chß╗ënh sß╗¡a (Input Fields)
* **T├¬n Danh mß╗Ñc:** T├¬n cß╗ºa loß║íi nh├│m h├áng h├│a.

### 4. R├áng buß╗Öc kß╗╣ thuß║¡t & Bß║úo mß║¡t (Technical Constraints)
* **Bß║úo vß╗ç qua phi├¬n ─æ─âng nhß║¡p:** Phß║úi c├│ Authentication Session cß╗ºa cß║Ñp Quß║ún trß╗ï vi├¬n.

### 5. Tr╞░ß╗¥ng hß╗úp ngoß║íi lß╗ç & Xß╗¡ l├╜ lß╗ùi (Edge Cases)
* **Tr╞░ß╗¥ng hß╗úp:** T├¬n danh mß╗Ñc bß╗ï bß╗Å trß╗æng.
  * **Xß╗¡ l├╜:** Hiß╗ân thß╗ï cß║únh b├ío JavaScript bß║»t buß╗Öc nhß║¡p liß╗çu.

### 6. Giao diß╗çn (UI/UX)
* Sß╗¡ dß╗Ñng bß║úng biß╗âu HTML Table c╞í bß║ún ─æß╗â hiß╗ân thß╗ï danh s├ích, ─æi k├¿m n├║t Sß╗¡a/X├│a.