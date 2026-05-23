# Hướng dẫn Cấu hình DNS cho Mailjet — Shop Sneakers

## Mục tiêu
Cấu hình SPF, DKIM, DMARC để email không bị đánh dấu Spam và tăng tỷ lệ vào Inbox.

> **Quan trọng**: Thay `yourdomain.com` bằng tên miền thật của bạn.

---

## 1. SPF (Sender Policy Framework)

SPF cho phép server nhận email xác minh rằng email được gửi từ server được ủy quyền.

### Bản ghi DNS cần thêm:

| Type | Name | Value | TTL |
|------|------|-------|-----|
| TXT | `@` | `v=spf1 include:spf.mailjet.com ~all` | Auto |

### Nếu đã có bản ghi SPF sẵn:
Thêm `include:spf.mailjet.com` vào bản ghi hiện tại. Ví dụ:
```
v=spf1 include:_spf.google.com include:spf.mailjet.com ~all
```

> **Lưu ý**: Chỉ được có **1 bản ghi SPF** cho mỗi domain. Nếu thêm bản ghi thứ 2, SPF sẽ fail.

---

## 2. DKIM (DomainKeys Identified Mail)

DKIM ký số mỗi email bằng private key, server nhận dùng public key để xác minh.

### Bước 1: Lấy DKIM key từ Mailjet
1. Đăng nhập [Mailjet Dashboard](https://app.mailjet.com)
2. Vào **Account Settings → Sender Domains & Addresses**
3. Thêm domain `yourdomain.com`
4. Mailjet sẽ cung cấp bản ghi DKIM (dạng TXT)

### Bước 2: Thêm bản ghi DNS

| Type | Name | Value | TTL |
|------|------|-------|-----|
| TXT | `mailjet._domainkey` | *(Giá trị do Mailjet cung cấp, dạng `k=rsa; p=MIGfMA0GCS...`)* | Auto |

### Trên Cloudflare:
1. DNS → Add Record → Type: **TXT**
2. Name: `mailjet._domainkey`
3. Content: paste value từ Mailjet
4. **Tắt Proxy** (chỉ DNS Only — biểu tượng đám mây xám)

---

## 3. DMARC (Domain-based Message Authentication)

DMARC kết hợp SPF + DKIM để quyết định xử lý email không hợp lệ.

### Giai đoạn 1 — Monitor (2-4 tuần đầu):
Quan sát báo cáo trước khi áp dụng chính sách nghiêm ngặt.

| Type | Name | Value | TTL |
|------|------|-------|-----|
| TXT | `_dmarc` | `v=DMARC1; p=none; rua=mailto:dmarc-reports@yourdomain.com; pct=100` | Auto |

### Giai đoạn 2 — Quarantine (sau khi xác nhận SPF/DKIM hoạt động):

| Type | Name | Value | TTL |
|------|------|-------|-----|
| TXT | `_dmarc` | `v=DMARC1; p=quarantine; rua=mailto:dmarc-reports@yourdomain.com; pct=100; adkim=r; aspf=r` | Auto |

### Giai đoạn 3 — Reject (nghiêm ngặt nhất, khuyến nghị):

| Type | Name | Value | TTL |
|------|------|-------|-----|
| TXT | `_dmarc` | `v=DMARC1; p=reject; rua=mailto:dmarc-reports@yourdomain.com; pct=100; adkim=s; aspf=s` | Auto |

> **Giải thích tham số:**
> - `p=reject` — Từ chối email giả mạo
> - `rua=mailto:...` — Email nhận báo cáo DMARC hàng ngày
> - `adkim=s` — Strict DKIM alignment
> - `aspf=s` — Strict SPF alignment
> - `pct=100` — Áp dụng cho 100% email

---

## 4. Xác minh trên Cloudflare

### Thao tác trên Cloudflare Dashboard:
1. Đăng nhập → Chọn domain → **DNS**
2. Thêm lần lượt 3 bản ghi TXT ở trên
3. **Quan trọng**: Đảm bảo tất cả bản ghi TXT đều ở chế độ **DNS Only** (đám mây xám)

### Kiểm tra cấu hình:
Sau khi thêm DNS records, chờ 15-30 phút rồi kiểm tra:

- **SPF**: [MXToolbox SPF Check](https://mxtoolbox.com/spf.aspx)
- **DKIM**: [MXToolbox DKIM Check](https://mxtoolbox.com/dkim.aspx) — Selector: `mailjet`
- **DMARC**: [MXToolbox DMARC Check](https://mxtoolbox.com/dmarc.aspx)
- **Tổng hợp**: [mail-tester.com](https://www.mail-tester.com/) — Gửi test email và kiểm tra điểm

---

## 5. Xác minh trên Mailjet Dashboard

1. Vào **Account Settings → Sender Domains & Addresses**
2. Nhấn **"Check Now"** hoặc **"Validate"** bên cạnh domain
3. Cả SPF và DKIM phải hiển thị ✅ (xanh)

---

## Checklist Hoàn thành

- [ ] Thêm bản ghi SPF (TXT `@`)
- [ ] Thêm bản ghi DKIM (TXT `mailjet._domainkey`)
- [ ] Thêm bản ghi DMARC (TXT `_dmarc`) — bắt đầu với `p=none`
- [ ] Xác minh trên Mailjet Dashboard → SPF ✅, DKIM ✅
- [ ] Kiểm tra trên MXToolbox → Tất cả PASS
- [ ] Gửi test email → Kiểm tra mail-tester.com ≥ 9/10
- [ ] Sau 2-4 tuần, nâng DMARC lên `p=quarantine`
- [ ] Sau 1 tháng ổn định, nâng DMARC lên `p=reject`
