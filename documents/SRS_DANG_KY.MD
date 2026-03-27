# Software Requirement Specification (SRS)
## Chß╗⌐c n─âng: Hß╗ç thß╗æng ─É─âng k├╜ t├ái khoß║ún (Register)
**M├ú chß╗⌐c n─âng:** AUTH-REGISTER-01  
**Trß║íng th├íi:** Draft / Review  
**Ng╞░ß╗¥i soß║ín thß║úo:** ─Éß╗Öi ng┼⌐ ph├ít triß╗ân
**Vai tr├▓:** Lead Developer

---

### 1. M├┤ tß║ú tß╗òng quan (Description)
Hß╗ç thß╗æng cho ph├⌐p ng╞░ß╗¥i d├╣ng v├úng lai hoß║╖c kh├ích h├áng mß╗¢i tß║ío lß║¡p mß╗Öt t├ái khoß║ún hß╗ô s╞í c├í nh├ón tr├¬n nß╗ün tß║úng cß╗¡a h├áng. Viß╗çc ─æ─âng k├╜ t├ái khoß║ún gi├║p kh├ích h├áng l╞░u trß╗» th├┤ng tin giao h├áng, ─æß╗ông bß╗Ö h├│a lß╗ïch sß╗¡ giß╗Å h├áng, thuß║¡n tiß╗çn theo d├╡i trß║íng th├íi ─æ╞ín h├áng v├á ─æ╞░ß╗úc h╞░ß╗ƒng c├íc ─æß║╖c quyß╗ün khuyß║┐n m├úi d├ánh ri├¬ng cho th├ánh vi├¬n c├│ ─æ─âng nhß║¡p.

### 2. Luß╗ông nghiß╗çp vß╗Ñ (User Workflow)
| B╞░ß╗¢c | H├ánh ─æß╗Öng ng╞░ß╗¥i d├╣ng | Phß║ún hß╗ôi hß╗ç thß╗æng |
| :--- | :--- | :--- |
| 1 | Truy cß║¡p trang ─É─âng k├╜ t├ái khoß║ún tß╗½ thanh ─æiß╗üu h╞░ß╗¢ng | Hß╗ç thß╗æng bß║»t ─æß║ºu hiß╗ân thß╗ï biß╗âu mß║½u ─æiß╗ün th├┤ng tin ─É─âng k├╜. |
| 2 | Cung cß║Ñp th├┤ng tin bß║»t buß╗Öc (Hß╗ì t├¬n, Email/Sß╗æ ─æiß╗çn thoß║íi, Mß║¡t khß║⌐u) | Hß╗ç thß╗æng kiß╗âm tra nhß║╣ t├¡nh hß╗úp lß╗ç c╞í bß║ún cß╗ºa dß╗» liß╗çu nhß║¡p (─æß╗ïnh dß║íng, ─æß╗Ö d├ái nß╗Öi dung). |
| 3 | Ng╞░ß╗¥i d├╣ng nhß║Ñn chß╗ìn n├║t "Tß║ío t├ái khoß║ún" | M├íy chß╗º ─æß╗æi chiß║┐u lß║íi tß╗òng qu├ít dß╗» liß╗çu vß╗¢i kho dß╗▒ trß╗» xem Email/Sß╗æ ─æiß╗çn thoß║íi ─æ├ú bß╗ï tr├╣ng lß║╖p hay ch╞░a. |
| 4 | *Tr╞░ß╗¥ng hß╗úp th├ánh c├┤ng:* Th├┤ng tin hß╗úp lß╗ç v├á ho├án to├án mß╗¢i mß║╗ | L╞░u th├┤ng tin ng╞░ß╗¥i d├╣ng mß╗¢i n├áy v├áo hß╗ç thß╗æng, th├┤ng b├ío tin b├ío "─É─âng k├╜ th├ánh c├┤ng" v├á tß╗▒ ─æß╗Öng ─æiß╗üu h╞░ß╗¢ng sang trang ─É─âng nhß║¡p. |
| 5 | *Tr╞░ß╗¥ng hß╗úp thß║Ñt bß║íi:* Dß╗» liß╗çu sai cß║Ñu tr├║c hoß║╖c nß╗Öi dung bß╗ï tr├╣ng lß║╖p | Dß╗½ng lß║íi tiß║┐n tr├¼nh, giß╗» nguy├¬n th├┤ng tin vß╗½a nhß║¡p (ngoß║íi trß╗½ ├┤ v├╣ng chß╗⌐a mß║¡t khß║⌐u), b├┤i m├áu cß║únh b├ío ─æoß║ín bß╗ï lß╗ùi k├¿m hiß╗ân thß╗ï nhß║»c nhß╗ƒ khß║»c phß╗Ñc. |

### 3. Y├¬u cß║ºu dß╗» liß╗çu (Data Requirements)
#### 3.1. Dß╗» liß╗çu cß║ºn thiß║┐t ─æ├¡nh k├¿m y├¬u cß║ºu (Input Fields)
* **Hß╗ì v├á t├¬n:** Y├¬u cß║ºu ng╞░ß╗¥i d├╣ng nhß║¡p t├¬n ─æß║ºy ─æß╗º bß║▒ng chuß║⌐n v─ân bß║ún th├┤ng th╞░ß╗¥ng, bß║»t buß╗Öc.
* **Email hoß║╖c Sß╗æ ─æiß╗çn thoß║íi:** Th├┤ng tin ─æß╗ïnh danh ch├¡nh ─æ├│ng vai tr├▓ ID, y├¬u cß║ºu ─æ├íp ß╗⌐ng chuß║⌐n ─æß╗ïnh dß║íng (C├│ @ vß╗¢i h├▓m th╞░ ─æiß╗çn tß╗¡ email, hoß║╖c ─æß╗º 10 chß╗» sß╗æ ri├¬ng cho di ─æß╗Öng), bß║»t buß╗Öc.
* **Mß║¡t khß║⌐u:** Chuß╗ùi th├┤ng ─æiß╗çp v─ân bß║ún bß║úo mß║¡t (─æ╞░ß╗úc ß║⌐n ─æi), y├¬u cß║ºu phß║úi ─æ├íp ß╗⌐ng ─æß╗Ö bß╗ün v├á ─æß╗Ö d├ái tß╗æi thiß╗âu (v├¡ dß╗Ñ: ├¡t nhß║Ñt l├á 6 ─æß║┐n 8 k├╜ tß╗▒), bß║»t buß╗Öc.
* **X├íc nhß║¡n mß║¡t khß║⌐u:** Y├¬u cß║ºu kh├ích h├áng g├╡ lß║íi ch├¡nh x├íc chuß╗ùi mß║¡t khß║⌐u vß╗½a viß║┐t ß╗ƒ ├┤ ngay ph├¡a tr├¬n ─æß╗â ─æß╗ü ph├▓ng tr╞░ß╗¥ng hß╗úp g├╡ nhß║ºm chß╗», bß║»t buß╗Öc.
* **─Éß╗ông ├╜ ─æiß╗üu khoß║ún c╞í bß║ún:** Thao t├íc lß╗▒a chß╗ìn hß╗Öp kiß╗âm nhß╗Å (Checkbox) nhß║▒m phß║ún ├ính ─æß╗ông ├╜ vß╗¢i bß╗Ö quy tß║»c dß╗ïch vß╗Ñ cß╗ºa ch├¡nh cß╗¡a h├áng n├áy, quy ─æß╗ïnh bß║»t buß╗Öc ─æ├ính dß║Ñu check.

#### 3.2. Dß╗» liß╗çu ─æ╞░ß╗úc khß╗ƒi tß║ío ß╗ƒ hß╗ç thß╗æng (Data Storage)
* **Hß╗ô s╞í th├┤ng tin th├ánh vi├¬n (User Profile):**
  * Nhß╗»ng th├┤ng tin c├í nh├ón li├¬n hß╗ç d├╣ng cho giao thß╗⌐c ─æß║╖t h├áng (Hß╗ì T├¬n, ─Éß╗ïnh danh li├¬n lß║íc).
  * Chuß╗ùi chß╗⌐a mß║¡t khß║⌐u gß╗æc ─æ├ú ─æ╞░ß╗úc biß║┐n ─æß╗òi m├ú h├│a bß║úo vß╗ç bß║▒ng thuß║¡t to├ín nhß║▒m mß╗Ñc ─æ├¡ch ß║⌐n nguy├¬n dß║íng (kh├┤ng sao k├¬ lß║íi bß║ún nh├íp ban ─æß║ºu cß╗ºa ng╞░ß╗¥i d├╣ng).
  * Trß║íng th├íi th├┤ng qua t├ái khoß║ún lß║¡p tß╗⌐c ─æ╞░ß╗úc g├ín cß╗¥ mß║╖c ─æß╗ïnh l├á "─Éang hoß║ít ─æß╗Öng" ─æß╗â c├│ thß╗â d├╣ng ngay m├á kh├┤ng cß║ºn ─æß╗úi Admin ph├¬ duyß╗çt.
  * Ph├ón luß╗ông quyß╗ün lß╗▒c mß║╖c ─æß╗ïnh sß║╜ l├á "Kh├ích h├áng th├┤ng th╞░ß╗¥ng".
  * Ng├áy giß╗¥ ghi nhß║¡n th├┤ng qua tß║ío lß║¡p (ph├║t/giß╗¥/ng├áy), tiß╗çn ├¡ch cho c├íc kß╗ïch bß║ún ch╞░╞íng tr├¼nh hß║¡u m├úi hoß║╖c gß╗¡i qu├á tri ├ón kß╗╖ niß╗çm t├ái khoß║ún.

### 4. R├áng buß╗Öc kß╗╣ thuß║¡t & Bß║úo mß║¡t (Technical Constraints)
* **Chß╗æng nß║ín tß║ío t├ái khoß║ún ß║úo/tß╗▒ ─æß╗Öng:** Biß╗âu mß║½u ─æ─âng k├╜ cß║ºn t├¡ch hß╗úp c├íc kß╗ïch bß║ún x├íc minh (v├¡ dß╗Ñ: b╞░ß╗¢c thß╗º c├┤ng Captcha) ─æß╗â ng─ân chß║╖n c├íc m├íy chß╗º lß║¡p tr├¼nh cß╗æ t├¼nh nh├⌐t h├áng ngh├¼n nick r├íc l├ám suy yß║┐u t├ái nguy├¬n.
* **Biß╗âu ─æß╗ô ─æß╗Ö kh├│ mß║¡t khß║⌐u:** Qu├í tr├¼nh ─æiß╗ün th├┤ng tin c├│ thß╗â hß╗ù trß╗ú hiß╗ân thß╗ï lß╗¥i cß║únh b├ío ─æß╗Ö tin cß║¡y cß╗ºa mß║¡t khß║⌐u ng╞░ß╗¥i kh├ích ─æß╗ïnh ─æß║╖t, chß║╖n lß║íi nß║┐u ─æß║╖t chuß╗ùi qu├í dß╗à (v├¡ dß╗Ñ: 123456).
* **Bß║úo vß╗ç m├ú ─æß╗Öc gß╗¡i ch├⌐o ─æß╗Öc hß║íi:** Mß╗ìi ├┤ chß╗» cß║ºn c├│ chß╗⌐c n─âng chß╗æng khai th├íc lß╗ò hß╗òng mß║íng (XSS), tiß║┐n h├ánh thanh lß╗ìc v├┤ h├│a nhß╗»ng d├▓ng tin cß╗æ nhß╗ôi nh├⌐t chß╗» k├╜ ─æß╗Öc hß║íi.
* **To├án diß╗çn m├ú h├│a mß║¡t khß║⌐u:** Mß║¡t khß║⌐u sau khi ─æ╞░ß╗úc thu thß║¡p sß║╜ bß╗ï dß╗ïch m├ú theo chiß╗üu tß╗ïnh tiß║┐n, tß╗⌐c l├á c├íc th├ánh vi├¬n nh├│m l├ám viß╗çc d├╣ c├│ quyß╗ün lß╗▒c cao ─æß║┐n mß║Ñy tr├¬n hß╗ç thß╗æng gß╗æc c┼⌐ng chß╗ë thß║Ñy ─æ╞░ß╗úc chuß╗ùi nhß╗»ng k├╜ tß╗▒ v├┤ ngh─⌐a lß╗Ön xß╗Ön, kh├┤ng thß╗â giß║úi m├ú lß║íi t├¼m ra phi├¬n bß║ún gß╗æc.

### 5. Tr╞░ß╗¥ng hß╗úp ngoß║íi lß╗ç & Xß╗¡ l├╜ lß╗ùi (Edge Cases)
* **Tr╞░ß╗¥ng hß╗úp:** Sß╗æ l╞░ß╗úng dß╗» liß╗çu l├á Sß╗æ ─æiß╗çn thoß║íi hoß║╖c hß╗Öp th╞░ Email m├á kh├ích h├áng chuß║⌐n bß╗ï ─æ─âng k├╜ ─æ├│ ─æ├ú ─æ╞░ß╗úc mß╗Öt chß╗º khoß║ún hß╗ç thß╗æng kh├íc chiß║┐m dß╗Ñng tß╗½ tr╞░ß╗¢c.
  * **Xß╗¡ l├╜:** Hiß╗çn ─æß╗Å hiß╗ân thß╗ï cß║únh b├ío ngay tß║íi v├╣ng vi phß║ím: *"Sß╗æ ─æiß╗çn thoß║íi/Email n├áy ─æ├ú ─æ╞░ß╗úc ghi danh v├áo hß╗ç thß╗æng. Xin bß║Ñm v├áo mß╗Ñc Qu├¬n mß║¡t khß║⌐u nß║┐u ─æ├óy ch├¡nh l├á t├ái khoß║ún c┼⌐ cß╗ºa bß║ín"*.
* **Tr╞░ß╗¥ng hß╗úp:** Phß║ºn ├┤ "Mß║¡t khß║⌐u" c├╣ng phß║ºn ├┤ "X├íc nhß║¡n Lß║íi mß║¡t khß║⌐u" ─æ╞░ß╗úc g├╡ th├ánh nß╗Öi dung kh├┤ng t╞░╞íng ─æß╗ông do vß╗Öi v├ú.
  * **Xß╗¡ l├╜:** Kh├┤ng duyß╗çt cho m├íy tiß║┐p nhß║¡n, in th├┤ng ─æiß╗çp *"Hai luß╗ông mß║¡t khß║⌐u khai b├ío kh├┤ng khß╗¢p nhau tr├╣ng lß║╖p. ─Éß╗ü nghß╗ï sß╗¡a sai cß║Ñu tr├║c"*.
* **Tr╞░ß╗¥ng hß╗úp:** Bß╗Å s├│t kh├┤ng chß╗ìn t├¡ch v├áo ├┤ vu├┤ng quy ─æß╗ïnh "─Éß╗ông ├╜ vß╗¢i nß╗ün sß╗¡ dß╗Ñng".
  * **Xß╗¡ l├╜:** Vß║½n cß║Ñm nß╗Öp lß╗çnh tß╗¢i m├íy chß╗º. V├╣ng ─æ├│ sß║╜ bß╗ï nhß║Ñp nh├íy chuyß╗ân m├áu v├á hiß╗ân thß╗ï nh├ún lß╗ùi.

### 6. Giao diß╗çn (UI/UX)
* Khung thao t├íc chß╗» tß╗▒ ─æß╗Öng ch├¿n k├¿m nhß╗»ng th├┤ng ─æiß╗çp kiß╗âu chß╗» ß║⌐n nhß║ít ─æß╗â dß║½n dß╗Ñ/l├ám mß║½u cho ng╞░ß╗¥i viß║┐t (VD: *nguyenvana_123@gmail.com*).
* N├║t "Tß║ío mß╗¢i t├ái khoß║ún" ngay khi k├¡ch hoß║ít sß║╜ chuyß╗ân pha mß╗¥ ─æi, kß║┐t hß╗úp thß╗â hiß╗çn v├▓ng tß║úi t├¡n hiß╗çu (Loading Spinner) vß╗¢i mß╗Ñc ti├¬u ─æß╗â hß╗ç thß╗æng tß╗▒ xß╗¡, tr├ính gß║╖p sß╗▒ cß╗æ nhß║Ñp nh├íy nhiß╗üu lß║ºn c├╣ng mß╗Öt lß╗çnh.
* Hiß╗çu suß║Ñt nhß║¡p liß╗çu m╞░ß╗út m├á, gß╗ìi ─æ├║ng b├án ph├¡m cß║úm ß╗⌐ng hß╗ù trß╗ú t╞░╞íng ─æ╞░╞íng vß╗¢i dß╗» kiß╗çn (D─⌐ nhi├¬n b├án ph├¡m con sß╗æ tß╗▒ ─æß╗Öng ch╞░ß╗¥ng ra khi m├án h├¼nh tß╗¢i cß║Ñu kiß╗çn cß║ºn g├╡ Sß╗æ ─Éiß╗çn Thoß║íi).

---