# Software Requirement Specification (SRS)
## Chß╗⌐c n─âng: Quß║ún l├╜ Th├┤ng tin T├ái khoß║ún
**M├ú chß╗⌐c n─âng:** USER-PROFILE-01  
**Trß║íng th├íi:** Draft / Review  
**Ng╞░ß╗¥i soß║ín thß║úo:** ─Éß╗Öi ng┼⌐ ph├ít triß╗ân

---

### 1. M├┤ tß║ú tß╗òng quan (Description)
Trang `thongtintaikhoan.php` cung cß║Ñp cho kh├ích mua h├áng xem lß║íi th├┤ng tin ─æß╗ïnh danh c├í nh├ón ─æ├ú khai b├ío khi ─æ─âng k├╜ t├ái khoß║ún. Hß╗ç thß╗æng ─æ├│ng vai tr├▓ th├┤ng b├ío Profile c├í nh├ón dß║íng thß║╗ t─⌐nh (Read-only) v├á cung cß║Ñp ─æ╞░ß╗¥ng ─æi tß║»t cho viß╗çc ─É─âng xuß║Ñt thiß║┐t bß╗ï.

### 2. Luß╗ông nghiß╗çp vß╗Ñ (User Workflow)
| B╞░ß╗¢c | H├ánh ─æß╗Öng ng╞░ß╗¥i d├╣ng | Phß║ún hß╗ôi hß╗ç thß╗æng |
| :--- | :--- | :--- |
| 1 | Truy cß║¡p `thongtintaikhoan.php` | Hß╗ç thß╗æng kiß╗âm tra ID t├ái khoß║ún `$_SESSION['makh']`. Nß║┐u mß║úng rß╗ùng th├¼ sß║╜ tß╗▒ ─æß╗Öng Alert "Vui l├▓ng ─æ─âng nhß║¡p" chß║╖n ng╞░ß╗¥i d├╣ng v├áo. |
| 2 | Kiß╗âm duyß╗çt th├ánh c├┤ng | Bß║»n chuß╗ùi `SELECT * FROM tbl_tkkhachhang WHERE makh = ? LIMIT 1`. |
| 3 | Quan s├ít hiß╗ân thß╗ï | D├ín c├íc Record trß║ú vß╗ü cß╗ºa ng╞░ß╗¥i d├╣ng (Hß╗ì t├¬n, Username). |
| 4 | (T├╣y chß╗ìn) Bß║Ñm "─É─âng Xuß║Ñt Thiß║┐t Bß╗ï" | N├⌐m cß╗¥ parameter `?login=dangxuat`, k├¡ch hoß║ít giß║úi ph├│ng mß║úng bß╗Ö nhß╗¢ hß╗ºy phi├¬n hoß║ít ─æß╗Öng. |

### 3. Y├¬u cß║ºu dß╗» liß╗çu (Data Requirements)
#### 3.1. Dß╗» liß╗çu Hiß╗ân thß╗ï T─⌐nh
* Lß║Ñy ra Hß╗ì T├¬n (`hoten`) v├á T├¬n ─É─âng Nhß║¡p Hß╗ç Thß╗æng (`username`).
* Kh├┤ng lß║Ñy Mß║¡t khß║⌐u (`password`). Cß╗æt yß║┐u tr├ính lß╗Ö cho ├ính nh├¼n ngß║½u nhi├¬n ng╞░ß╗¥i kß║┐ b├¬n.

### 4. R├áng buß╗Öc kß╗╣ thuß║¡t & Bß║úo mß║¡t (Technical Constraints)
* **─É├│ng Form Sß╗¡a ─æß╗òi (Immutable Rule):** Tß║íi nß╗ün tß║úng ng╞░ß╗¥i ti├¬u d├╣ng (Customer site), mß╗ìi th├┤ng sß╗æ ─æß╗ïnh danh thiß║┐t lß║¡p tß║íi b╞░ß╗¢c ─É─âng k├╜ l├á kh├┤ng thß╗â sß╗¡a ─æß╗òi (No Edit Input).
* **Cross-Site Scripting (XSS):** Dß╗» liß╗çu in ra khung View phß║úi d├╣ng `htmlspecialchars` bß╗ìc lß║íi ─æß╗ü ph├▓ng `username` kß║╣p m├ú HTML bß║⌐n.

### 5. Tr╞░ß╗¥ng hß╗úp ngoß║íi lß╗ç & Xß╗¡ l├╜ lß╗ùi (Edge Cases)
* **Tr╞░ß╗¥ng hß╗úp:** SQL truy xuß║Ñt ID trß║ú vß╗ü Null (t├ái khoß║ún ─æ├ú bß╗ï quß║ún trß╗ï vi├¬n x├│a, nh╞░ng Session Cookie cß╗ºa kh├ích ch╞░a hß║┐t hß║ín).
  * **Xß╗¡ l├╜:** Nhß║¡n diß╗çn mß║úng Fetch bß╗ï False, qu─âng Alert `"Lß╗ùi truy xuß║Ñt hß╗ç thß╗æng! Vui l├▓ng thß╗¡ ─æ─âng nhß║¡p lß║íi"` kh├│a trang.

### 6. Giao diß╗çn (UI/UX)
* Khung hß╗ô s╞í Card theo phong c├ích Identity vß║ích dß╗ìc cß║ính viß╗ün l├ám nß╗òi text. Trß║úi giß╗»a m├án h├¼nh c├│ Icon m├áu nß╗òi ─æß║¡m bao bß╗ìc Avatar.