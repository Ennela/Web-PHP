# Software Requirement Specification (SRS)
## Chß╗⌐c n─âng: Quß║ún l├╜ ─É╞ín h├áng
**M├ú chß╗⌐c n─âng:** ADMIN-ORDER-01  
**Trß║íng th├íi:** Draft / Review  
**Ng╞░ß╗¥i soß║ín thß║úo:** ─Éß╗Öi ng┼⌐ ph├ít triß╗ân

---

### 1. M├┤ tß║ú tß╗òng quan (Description)
Quß║ún trß╗ï vi├¬n sß╗¡ dß╗Ñng hß╗ç thß╗æng `quanlidonhang.php` ─æß╗â gi├ím s├ít danh s├ích c├íc ─æ╞ín ─æß║╖t h├áng ─æ╞░ß╗úc ─æß║╖t tß╗½ website, theo d├╡i th├┤ng tin ng╞░ß╗¥i nhß║¡n, c╞░ß╗¢c ph├¡ v├á chi tiß║┐t tß╗½ng mß║╖t h├áng. Hß╗ç thß╗æng li├¬n kß║┐t giß╗»a bß║úng `oder` v├á `oder_chitiet`.

### 2. Luß╗ông nghiß╗çp vß╗Ñ (User Workflow)
| B╞░ß╗¢c | H├ánh ─æß╗Öng Admin | Phß║ún hß╗ôi hß╗ç thß╗æng |
| :--- | :--- | :--- |
| 1 | Truy cß║¡p `quanlidonhang.php` | Hß╗ç thß╗æng `SELECT * FROM oder ORDER BY id DESC` v├á hiß╗ân thß╗ï tr├¬n bß║úng. |
| 2 | Nhß║Ñn d├▓ng In / Xem ─æ╞ín | Mß╗ƒ trang `inhoadon.php` chß╗⌐a th├┤ng tin chi tiß║┐t bß║úng `oder_chitiet` (Sß║ún phß║⌐m, sß╗æ l╞░ß╗úng, gi├í tiß╗ün). |
| 3 | N├║t X├│a ─æ╞ín h├áng | Gß╗¡i lß╗çnh `DELETE FROM oder WHERE id = ?`. Dß╗» liß╗çu tß║íi `oder_chitiet` tß╗▒ ─æß╗Öng bß╗ï x├│a theo nhß╗¥ r├áng buß╗Öc kh├│a ngoß║íi (CASCADE). |

### 3. Y├¬u cß║ºu dß╗» liß╗çu (Data Requirements)
#### 3.1. Dß╗» liß╗çu Hiß╗ân thß╗ï (Data Output)
* **Bß║úng ─É╞ín h├áng (`oder`):** `id`, `tenkh`, `sdt`, `diachi`, `note`, `ngaytao`, `tongtien`, `status`.
* **Bß║úng Chi tiß║┐t (`oder_chitiet`):** ├ünh xß║í tß╗½ `masp` sang bß║úng `tbl_qlsanpham` ─æß╗â lß║Ñy t├¬n v├á gi├í gß╗æc tß║íi thß╗¥i ─æiß╗âm mua (`price`, `quantity`).

### 4. R├áng buß╗Öc kß╗╣ thuß║¡t & Bß║úo mß║¡t (Technical Constraints)
* **Kh├│a ngoß║íi Cascade:** Hß╗ç thß╗æng sß╗¡ dß╗Ñng r├áng buß╗Öc x├│a xß║┐p tß║ºng `ON DELETE CASCADE` n├¬n chß╗ë cß║ºn x├│a ID cß╗ºa h├│a ─æ╞ín mß║╣ ß╗ƒ bß║úng `oder`, c├íc d├▓ng con ß╗ƒ `oder_chitiet` sß║╜ tß╗▒ loß║íi bß╗Å, tr├ính r├íc DB.

### 5. Tr╞░ß╗¥ng hß╗úp ngoß║íi lß╗ç & Xß╗¡ l├╜ lß╗ùi (Edge Cases)
* **Tr╞░ß╗¥ng hß╗úp:** ─É╞ín h├áng bß╗ï lß╗ùi hoß║╖c th├┤ng tin r├íc.
  * **Xß╗¡ l├╜:** Admin sß║╜ xem v├á thao t├íc x├│a thß╗º c├┤ng (Thß╗▒c thi chuß╗ùi `delete_qldonhang.php?id=`).

### 6. Giao diß╗çn (UI/UX)
* Giao diß╗çn dß║íng Data Table ─æ╞ín giß║ún c├│ hß╗ù trß╗ú n├║t In h├│a ─æ╞ín (`inhoadon.php`) vß╗¢i bß╗æ cß╗Ñc sß║╡n s├áng ra giß║Ñy A4 ─æ╞ín giß║ún cho m├íy in nhiß╗çt hoß║╖c PDF.