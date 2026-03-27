# Software Requirement Specification (SRS)
## Chß╗⌐c n─âng: Giao diß╗çn Trang chß╗º (Home)
**M├ú chß╗⌐c n─âng:** HOME-UI-01  
**Trß║íng th├íi:** Draft / Review  
**Ng╞░ß╗¥i soß║ín thß║úo:** ─Éß╗Öi ng┼⌐ ph├ít triß╗ân

---

### 1. M├┤ tß║ú tß╗òng quan (Description)
Trang chß╗º (`trangchu.php`) ─æ├│ng vai tr├▓ ─æß║¡p thß║│ng v├áo c├íi nh├¼n ─æß║ºu ti├¬n v├á ph├┤ b├áy th├┤ng sß╗æ thiß║┐t kß║┐ chung, giß╗¢i thiß╗çu hß╗ç th├┤ng Banner / Slider t─⌐nh hoß║╖c hiß╗ân thß╗ï v├ái m├│n h├áng ─æß║íi diß╗çn.

### 2. Luß╗ông nghiß╗çp vß╗Ñ (User Workflow)
| B╞░ß╗¢c | H├ánh ─æß╗Öng ng╞░ß╗¥i d├╣ng | Phß║ún hß╗ôi hß╗ç thß╗æng |
| :--- | :--- | :--- |
| 1 | Truy cß║¡p th╞░ mß╗Ñc dß║úi Domain | Tß╗▒ ─æß╗Öng chuyß╗ân nß║íp nß╗Öi dung cß╗ºa file cß║Ñu tr├║c `trangchu.php`. |
| 2 | Cuß╗Ön tr├¼nh duyß╗çt (Scroll) qua c├íc khß╗æi Section | ─Éß╗ìc tß║úi dß╗» liß╗çu cß║Ñu tr├║c Text v├á H├¼nh Banner cß╗æ ─æß╗ïnh. Lß║Ñy ß║únh nhß╗Å sß║ún phß║⌐m ─æß╗â ch├¿n giß╗¢i thiß╗çu "H├áng Hot". |

### 3. Y├¬u cß║ºu dß╗» liß╗çu (Data Requirements)
* **Quß║ún trß╗ï ß║ónh Banner:** C├│ thß╗â nß║íp cß╗⌐ng qua chuß╗ùi t─⌐nh `<img>` thay v├¼ nß║▒m trong MySQL CSDL. 
* **H├áng ─æß║íi diß╗çn:** Gß╗ìi truy cß║¡p nhanh v├ái mß║½u `SELECT * FROM tbl_qlsanpham LIMIT 4` ─æß╗â tr╞░ng b├áy giß╗¢i thiß╗çu.

### 4. R├áng buß╗Öc kß╗╣ thuß║¡t & Bß║úo mß║¡t (Technical Constraints)
* **Hiß╗çu Suß║Ñt (Performance):** Khß╗æi l╞░ß╗úng ß║ónh nß║▒m tß║íi trang chß╗º rß║Ñt nß║╖ng, cß║ºn phß║úi bß╗ìc `lazyload` ß╗ƒ c├íc m├ú HTML hoß║╖c cß║»t x├⌐n k├¡ch th╞░ß╗¢c size thumbnail.

### 5. Tr╞░ß╗¥ng hß╗úp ngoß║íi lß╗ç & Xß╗¡ l├╜ lß╗ùi (Edge Cases)
* **Tr╞░ß╗¥ng hß╗úp:** Ch╞░a c├│ sß║ún phß║⌐m n├áo thuß╗Öc nß╗ün tß║úng Database ─æß╗â tr╞░ng b├áy.
  * **Xß╗¡ l├╜:** Nß╗ün Banner vß║½n nß║íp, trong khi ─æ├│ khu vß╗▒c thß║╗ H├áng H├│a trß╗ù lß║íi mß╗Öt khoß║úng bao bß╗ìc vß║»ng kh├┤ng lß╗ùi Crash.

### 6. Giao diß╗çn (UI/UX)
* Khß╗æi `Carousel` nß║íp thanh mß║únh xoay tr├▓n tß║ío ─æiß╗âm nhß║Ñn bß║»t mß║»t ß╗ƒ ─æß╗ënh.
* Footer c├│ sß╗ìc ch├⌐o ─æß╗çm ch├│p cuß╗æi ch├ón m├áng kß║┐t quß║ú.