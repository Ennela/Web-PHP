# Software Requirement Specification (SRS)
## Chß╗⌐c n─âng: Hß╗ç thß╗æng ─É─âng nhß║¡p (Login)
**M├ú chß╗⌐c n─âng:** AUTH-LOGIN-01  
**Trß║íng th├íi:** Draft / Review  
**Ng╞░ß╗¥i soß║ín thß║úo:** ─Éß╗Öi ng┼⌐ ph├ít triß╗ân
**Vai tr├▓:** Lead Developer

---

### 1. M├┤ tß║ú tß╗òng quan (Description)
Hß╗ç thß╗æng x├íc thß╗▒c cho ph├⌐p kh├ích h├áng truy cß║¡p v├áo t├ái khoß║ún c├í nh├ón th├┤ng qua th├┤ng tin ─æ├ú ─æ─âng k├╜ tr╞░ß╗¢c ─æ├│ nhß║▒m quß║ún l├╜ th├┤ng tin hß╗ô s╞í, theo d├╡i ─æ╞ín h├áng v├á l╞░u trß╗» lß╗ïch sß╗¡ mua sß║»m mß╗Öt c├ích c├í nh├ón h├│a.

### 2. Luß╗ông nghiß╗çp vß╗Ñ (User Workflow)
| B╞░ß╗¢c | H├ánh ─æß╗Öng ng╞░ß╗¥i d├╣ng | Phß║ún hß╗ôi hß╗ç thß╗æng |
| :--- | :--- | :--- |
| 1 | Truy cß║¡p v├áo trang ─É─âng nhß║¡p tß╗½ thanh ─æiß╗üu h╞░ß╗¢ng | Hß╗ç thß╗æng ─æiß╗üu h╞░ß╗¢ng v├á hiß╗ân thß╗ï biß╗âu mß║½u ─É─âng nhß║¡p. |
| 2 | Nhß║¡p T├¬n ─æ─âng nhß║¡p/Email v├á Mß║¡t khß║⌐u rß╗ôi nhß║Ñn "─É─âng nhß║¡p" | Kiß╗âm tra t├¡nh hß╗úp lß╗ç c╞í bß║ún cß╗ºa ─æß╗ïnh dß║íng dß╗» liß╗çu ─æ╞░ß╗úc cung cß║Ñp. |
| 3 | Chß╗¥ x├⌐t duyß╗çt | Hß╗ç thß╗æng kiß╗âm tra th├┤ng tin ─æß╗æi chiß║┐u vß╗¢i kho dß╗» liß╗çu an to├án. |
| 4 | *Tr╞░ß╗¥ng hß╗úp th├ánh c├┤ng:* T├ái khoß║ún hß╗úp lß╗ç | Thiß║┐t lß║¡p phi├¬n l├ám viß╗çc bß║úo mß║¡t, tß╗▒ ─æß╗Öng chuyß╗ân h╞░ß╗¢ng kh├ích h├áng vß╗ü lß║íi Trang Chß╗º hoß║╖c chß╗⌐c n─âng tr╞░ß╗¢c ─æ├│. |
| 5 | *Tr╞░ß╗¥ng hß╗úp thß║Ñt bß║íi:* Sai th├┤ng tin | Giß╗» nguy├¬n trang, x├│a trß╗æng ├┤ mß║¡t khß║⌐u, hiß╗ân thß╗ï th├┤ng b├ío lß╗ùi y├¬u cß║ºu kiß╗âm tra lß║íi. |

### 3. Y├¬u cß║ºu dß╗» liß╗çu (Data Requirements)
#### 3.1. Dß╗» liß╗çu ─æß║ºu v├áo (Input Fields - Login Form)
* **T├ái khoß║ún ─æ─âng nhß║¡p:** Y├¬u cß║ºu nhß║¡p ─æ├║ng Email hoß║╖c t├¬n ng╞░ß╗¥i d├╣ng hß╗úp lß╗ç ─æ├ú ─æ─âng k├╜ tr╞░ß╗¢c ─æ├│, bß║»t buß╗Öc.
* **Mß║¡t khß║⌐u:** Chuß╗ùi v─ân bß║ún ─æ╞░ß╗úc ß║⌐n ─æi d╞░ß╗¢i dß║íng dß║Ñu sao/chß║Ñm tr├▓n, y├¬u cß║ºu nhß║¡p ─æ├║ng ─æß╗Ö d├ái tß╗æi thiß╗âu, bß║»t buß╗Öc.
* **Ghi nhß╗¢ ─æ─âng nhß║¡p:** Lß╗▒a chß╗ìn hß╗Öp kiß╗âm (Checkbox) t├╣y chß╗ìn ─æß╗â l╞░u phi├¬n l├ám viß╗çc l├óu h╞ín tr├¬n thiß║┐t bß╗ï tin t╞░ß╗ƒng.

#### 3.2. Dß╗» liß╗çu ─æß╗æi chiß║┐u (Database Verification)
* **Th├┤ng tin x├íc thß╗▒c:** 
  * T├¬n ─æ─âng nhß║¡p (hoß║╖c Email) cß╗ºa ng╞░ß╗¥i d├╣ng phß║úi tß╗ôn tß║íi duy nhß║Ñt tr├¬n hß╗ç thß╗æng.
  * So khß╗¢p th├┤ng tin Mß║¡t khß║⌐u ─æ├ú ─æ╞░ß╗úc m├ú h├│a an to├án (kh├┤ng hß╗ü l╞░u giß╗» dß║íng v─ân bß║ún thuß║ºn t├║y).
  * Trß║íng th├íi t├ái khoß║ún ng╞░ß╗¥i d├╣ng phß║úi ß╗ƒ diß╗çn khß║ú dß╗Ñng (Kh├┤ng bß╗ï kh├│a do vi phß║ím hoß║╖c ─æ├¼nh chß╗ë).

### 4. R├áng buß╗Öc kß╗╣ thuß║¡t & Bß║úo mß║¡t (Technical Constraints)
* **Truyß╗ün tß║úi ─æ╞░ß╗¥ng truyß╗ün mß║íng:** To├án bß╗Ö th├┤ng tin ─æ─âng nhß║¡p cß╗ºa ng╞░ß╗¥i d├╣ng phß║úi ─æ╞░ß╗úc truyß╗ün tß║úi th├┤ng qua kß║┐t nß╗æi c├│ chß╗⌐ng chß╗ë m├ú h├│a dß╗» liß╗çu, ─æß║úm bß║úo kh├┤ng bß╗ï nghe l├⌐n.
* **Chß╗æng ─æo├ín mß║¡t khß║⌐u li├¬n tß╗Ñc:** Nß║┐u c├│ ai ─æ├│ cß╗æ t├¼nh nhß║¡p sai mß║¡t khß║⌐u qu├í sß╗æ lß║ºn quy ─æß╗ïnh, hß╗ç thß╗æng sß║╜ tß║ím kh├│a chß╗⌐c n─âng ─æ─âng nhß║¡p cß╗ºa ─æß╗ïa chß╗ë thiß║┐t bß╗ï ─æ├│ trong mß╗Öt khoß║úng thß╗¥i gian nhß║▒m chß║╖n nguy c╞í bß╗ï d├▓ mß║¡t khß║⌐u.
* **Bß║úo vß╗ç m├ú ─æß╗Öc gß╗¡i ch├⌐o:** ├üp dß╗Ñng lß╗¢p x├íc thß╗▒c an to├án tr├¬n mß╗ìi biß╗âu mß║½u gß╗¡i y├¬u cß║ºu ─æß╗â ng─ân chß║╖n viß╗çc bß╗ï nh├║ng m├ú ─æß╗Öc nß║íp dß╗» liß╗çu sai cß║Ñu tr├║c.
* **M├ú h├│a mß╗Öt chiß╗üu mß║¡t khß║⌐u:** Mß║¡t khß║⌐u cß╗ºa kh├ích h├áng ho├án to├án biß║┐n ─æß╗òi theo thuß║¡t to├ín mß╗Öt chiß╗üu. Kh├┤ng mß╗Öt tß╗ò chß╗⌐c hay ng╞░ß╗¥i trß╗▒c quß║ún trß╗ï c╞í sß╗ƒ dß╗» liß╗çu n├áo c├│ thß╗â tra cß╗⌐u v├á kh├┤i phß╗Ñc ─æ╞░ß╗úc mß║¡t khß║⌐u gß╗æc ─æ├│.

### 5. Tr╞░ß╗¥ng hß╗úp ngoß║íi lß╗ç & Xß╗¡ l├╜ lß╗ùi (Edge Cases)
* **Tr╞░ß╗¥ng hß╗úp:** Th├┤ng tin t├¬n ─æ─âng nhß║¡p v├á mß║¡t khß║⌐u ho├án to├án kh├┤ng c├│ sß╗▒ khß╗¢p nß╗æi ß╗ƒ mß╗Öt trong hai th├┤ng tin n├áy.
  * **Xß╗¡ l├╜:** Trß║ú vß╗ü th├┤ng b├ío cß║únh b├ío chung cß╗▒c kß╗│ trung lß║¡p: *"T├¬n ─æ─âng nhß║¡p hoß║╖c mß║¡t khß║⌐u kh├┤ng ch├¡nh x├íc"*. Tuyß╗çt ─æß╗æi kh├┤ng chß╗ë r├╡ rß║▒ng lß╗ùi nß║▒m ß╗ƒ ─æ├óu nhß║▒m ng─ân chß║╖n viß╗çc d├▓ qu├⌐t nhß╗»ng email n├áo ─æ├ú tß╗ôn tß║íi trong kho dß╗» liß╗çu.
* **Tr╞░ß╗¥ng hß╗úp:** Kh├ích h├áng ─æ─âng nhß║¡p ─æ├║ng chuß║⌐n c├íc th├┤ng tin nh╞░ng t├ái khoß║ún cß╗ºa hß╗ì ─æang bß╗ï nß║▒m trong danh s├ích cß║Ñm tß╗½ nh├á quß║ún trß╗ï.
  * **Xß╗¡ l├╜:** Hß╗ç thß╗æng dß╗½ng viß╗çc cß║Ñp quyß╗ün tß║ío phi├¬n li├¬n lß║íc, lß║¡p tß╗⌐c hiß╗ân thß╗ï lß╗ùi ch├║ th├¡ch: *"T├ái khoß║ún cß╗ºa bß║ín tß║ím thß╗¥i ─æang bß╗ï kh├│a hoß║╖c nß║▒m trong hß║ín chß║┐. Vui l├▓ng li├¬n hß╗ç bß╗Ö phß║¡n hß╗ù trß╗ú ngay"*.
* **Tr╞░ß╗¥ng hß╗úp:** Phi├¬n l├ám viß╗çc sau mß╗Öt khoß║úng thß╗¥i gian d├ái bß╗ï bß╗Å kh├┤ng c├│ sß╗▒ t╞░╞íng t├íc sß╗¡ dß╗Ñng.
  * **Xß╗¡ l├╜:** Tß╗▒ ─æß╗Öng ─æ─âng xuß║Ñt ng╞░ß╗¥i d├╣ng khß╗Åi hß╗ç thß╗æng mß╗Öt c├ích im lß║╖ng. Tß║íi lß║ºn nhß║Ñp t╞░╞íng t├íc kß║┐ tiß║┐p ─æ├▓i hß╗Åi quyß╗ün hß║ín, hß╗ç thß╗æng sß║╜ y├¬u cß║ºu hß╗ì ─æ─âng nhß║¡p lß║íi ─æß╗â x├íc thß╗▒c chß╗º sß╗ƒ hß╗»u thiß║┐t bß╗ï ─æ├¡ch thß╗▒c.

### 6. Giao diß╗çn (UI/UX)
* Thiß║┐t kß║┐ khung biß╗âu mß║½u ─æ─âng nhß║¡p tu├ón thß╗º khß║ú n─âng t╞░╞íng th├¡ch trß║úi nghiß╗çm tr├¬n cß║ú thiß║┐t bß╗ï ─æiß╗çn thoß║íi, m├íy t├¡nh bß║úng v├á m├án h├¼nh lß╗¢n.
* C├íc n├║t bß║Ñm t╞░╞íng t├íc ("─É─âng nhß║¡p") phß║úi thay ─æß╗òi trß║íng th├íi h├¼nh d├íng hiß╗çu ß╗⌐ng (nh╞░ n├║t bß╗ï x├ím ─æi, quay v├▓ng tß║úi t├¡n hiß╗çu) v├á ─æ├│ng b─âng ho├án to├án ngay khi ─æang chß╗¥ hß╗ç thß╗æng xß╗¡ l├╜ ─æß╗â chß╗æng rß╗ºi ro nhß║Ñn ─æ├║p.
* Hß╗ù trß╗ú t├¡ch hß╗úp sß║╡n biß╗âu t╞░ß╗úng bß║¡t/tß║»t mß║»t th╞░ß╗¥ng ß╗ƒ phß║ºn khung v─ân bß║ún ─æiß╗ün Mß║¡t khß║⌐u ─æß╗â ng╞░ß╗¥i d├╣ng kiß╗âm ra ─æ╞░ß╗úc chuß╗ùi k├╜ tß╗▒ hß╗ì vß╗½a g├╡ m├á kh├┤ng bß╗ï l├ám mß╗¥ mß╗Öt c├ích t├╣y ├╜.
* Bß╗ò sung nhanh tiß╗çn ├¡ch ph├¡m tß║»t (Nhß║Ñn ph├¡m Enter tr├¬n b├án ph├¡m cß╗⌐ng) ─æß╗â ─æß║⌐y luß╗ông nß╗Öp biß╗âu mß║½u th├ón thiß╗çn h╞ín ─æß╗æi vß╗¢i thao t├íc tr├¬n m├íy t├¡nh c├í nh├ón.

---