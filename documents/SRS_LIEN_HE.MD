# Software Requirement Specification (SRS)
## Chß╗⌐c n─âng: Li├¬n hß╗ç (Contact Form)
**M├ú chß╗⌐c n─âng:** CONTACT-01  
**Trß║íng th├íi:** Draft / Review  
**Ng╞░ß╗¥i soß║ín thß║úo:** ─Éß╗Öi ng┼⌐ ph├ít triß╗ân

---

### 1. M├┤ tß║ú tß╗òng quan (Description)
Trang cung cß║Ñp biß╗âu mß║½u h├▓m th╞░ `lienhe.php`. Mß╗ìi th├┤ng ─æiß╗çp b├ío c├ío hoß║╖c thß║»c mß║»c mua h├áng sß║╜ ─æ╞░ß╗úc thu thß║¡p v├á tß╗▒ ─æß╗Öng gß╗¡i thß║│ng ─æß║┐n h├▓m Email hß╗ç thß╗æng quß║ún trß╗ï nhß╗¥ giao thß╗⌐c SMTP cß╗ºa PHPMailer Library.

### 2. Luß╗ông nghiß╗çp vß╗Ñ (User Workflow)
| B╞░ß╗¢c | H├ánh ─æß╗Öng ng╞░ß╗¥i d├╣ng | Phß║ún hß╗ôi hß╗ç thß╗æng |
| :--- | :--- | :--- |
| 1 | Truy cß║¡p `lienhe.php` | Frontend vß║╜ Input y├¬u cß║ºu: Hß╗ì t├¬n, Email ng╞░ß╗¥i gß╗¡i, Nß╗Öi dung nh├íp. |
| 2 | Nhß║Ñn Gß╗¡i th╞░ (N├║t POST) | ß╗¿ng dß╗Ñng nß║íp ─æß╗æi t╞░ß╗úng class `PHPMailer`. |
| 3 | Chuyß╗ân tiß║┐p cß╗òng kß║┐t nß╗æi | Hß╗ç thß╗æng ─æ─âng nhß║¡p v├áo Gmail cß║Ñp quß║ún trß╗ï qua port SSL 465. |
| 4 | *Tr╞░ß╗¥ng hß╗úp th├ánh c├┤ng:* ─Éß║⌐y nß╗Öi dung | In m├án h├¼nh Alert "Th╞░ ─æ├ú ─æ╞░ß╗úc gß╗¡i" v├á tß║úi lß║íi luß╗ông `lienhe.php` dß╗ìn trß╗æng textbox. |

### 3. Y├¬u cß║ºu dß╗» liß╗çu (Data Requirements)
* **Dß╗» liß╗çu Biß╗âu mß║½u (Input):** `name`, `email`, `message`.
* **Dß╗» liß╗çu Cß╗⌐ng (Config):** ─Éß╗ïa chß╗ë m├íy chß╗º `smtp.gmail.com`, th├┤ng tß╗▒ t├ái khoß║ún `hethong1fx...` ─æß╗â k├¡ch hoß║ít bß║»n Mail. Bß╗Å qua cß║Ñu tr├║c l╞░u CSDL bß║úng SQL ─æß╗â chß╗æng qu├í tß║úi r├íc tin nhß║»n.

### 4. R├áng buß╗Öc kß╗╣ thuß║¡t & Bß║úo mß║¡t (Technical Constraints)
* **T├ái khoß║ún Mail cß║Ñp ß╗⌐ng dß╗Ñng:** Viß╗çc d├╣ng `PHPMailer` ─æ├▓i hß╗Åi t├ái khoß║ún Google phß║úi tß║ío Mß║¡t khß║⌐u ß╗¿ng dß╗Ñng (App Passwords) mß╗¢i ─æ╞░ß╗úc ph├⌐p Bypass.
* **Kh├┤ng ─É├¡nh Kß║┐t Tß╗çp T├¡n (Attachments):** Kh├┤ng hß╗ù trß╗ú File upload ─æß╗â ─æß╗ü ph├▓ng rß╗ºi ro bß║úo mß║¡t tß╗çp m├ú ─æß╗Öc ch├¿n tß╗¢i Admin.

### 5. Tr╞░ß╗¥ng hß╗úp ngoß║íi lß╗ç & Xß╗¡ l├╜ lß╗ùi (Edge Cases)
* **Tr╞░ß╗¥ng hß╗úp:** Kß║┐t nß╗æi mß║íng chß║¡p chß╗¥n / Mß║¡t khß║⌐u ß╗⌐ng dß╗Ñng bß╗ï ─æß╗òi l├ám sai c╞░ß╗¢c ph├¡ SMTP Authentication.
  * **Xß╗¡ l├╜:** Khß╗æi lß╗çnh `Try-Catch(Exception)` t├│m lß╗ùi ├óm thß║ºm kß║┐t th├║c, kh├┤ng in StackTrace cho ng╞░ß╗¥i ngo├ái l├ám ph╞íi b├áy Mß║¡t khß║⌐u cß╗⌐ng.

### 6. Giao diß╗çn (UI/UX)
* Khung Card nß╗òi ngay ch├¡nh giß╗»a trang c├│ 3 ─æoß║ín Input Field.