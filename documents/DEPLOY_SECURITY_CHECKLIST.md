# Checklist triển khai bảo mật (sau khi gộp #9, #10, #11, #12)

Làm theo thứ tự, trên staging trước, production sau.

1. **Backup DB** đầy đủ (mysqldump) và lưu ngoài web root.
2. **Khóa mã hóa**: sinh khóa bằng
   `php -r "require 'includes/crypto.php'; echo app_generate_encryption_key(), PHP_EOL;"`
   rồi đặt `APP_ENCRYPTION_KEY` + `APP_ENCRYPTION_KEY_ID` (hoặc `APP_ENCRYPTION_KEYS` / `APP_KEY_FILE`).
   Lưu khóa ở nơi an toàn: mất khóa = mất dữ liệu PII đã mã hóa. Không commit khóa.
3. **MySQL từ xa**: đặt `MYSQL_SSL_CA` (đường dẫn CA PEM ngoài web root). Thiếu sẽ trả 503.
4. **HTTPS/proxy**: cấu hình `TRUSTED_PROXIES`, bật HTTPS bắt buộc; request HTTP sẽ bị chặn.
5. **Migration**:
   - `php scripts/migrate_crypto_storage.php --dry-run`
   - `php scripts/migrate_crypto_storage.php --verify`
   - chạy thật khi dry-run sạch; giữ backup đã mã hóa cho đến khi xác nhận ổn.
6. Sau khi migration xong và đăng nhập/đặt hàng/tra cứu đơn đã kiểm thử: đặt `APP_CRYPTO_STRICT_READ=1`.
7. **Kiểm thử trên staging** (cần server + DB): `scripts/test_xss.py`, `test_csrf.py`,
   `test_csrf_authenticated.py`, `test_redirects.py`, `test_security_configuration.py`,
   `test_transport.py`, và thử tay: đăng nhập khách/admin, quên + đặt lại mật khẩu, đặt hàng (COD + MoMo), tra cứu đơn.

---

## Bổ sung: triển khai bằng Docker Compose + Cloudflare Tunnel

Hướng dẫn từng bước nằm trong [README](../README.md#triển-khai-bằng-docker--cloudflare-tunnel-khuyên-dùng). Điểm cần kiểm tra khi dùng cách này:

1. **Khóa mã hóa**: đặt `APP_ENCRYPTION_KEY` (+ `APP_ENCRYPTION_KEY_ID`) trong `.env.docker`, sao lưu khóa ra nơi khác máy chạy. Thay cho bước 2 ở trên (không dùng `php` trên host).
2. **Secret**: chỉ nằm trong `.env.docker` (bị `.gitignore`/`.dockerignore` loại). Không commit, không dán vào chat. Lộ `TUNNEL_TOKEN` thì xoay token trên dashboard Cloudflare.
3. **MySQL**: container `db` bắt buộc TLS (`--require-secure-transport`), app kết nối bằng user `shopapp` (không root) và CA do `dbcerts` sinh. Không publish cổng 3306. Thay cho bước 3 ở trên.
4. **Proxy tin cậy**: `TRUSTED_PROXIES` đặt đúng IP cố định của container `cloudflared` (`172.30.0.10/32`), không tin cả subnet. Thay cho bước 4 ở trên; HTTPS do Cloudflare chấm dứt.
5. **Cloudflare**: tunnel riêng cho shop (không dùng chung tunnel khác); SSL/TLS: *Always Use HTTPS*, *Minimum TLS Version = 1.2*.
6. **Backup trước migration**: chạy `scripts\backup_db.ps1`, đăng ký Task Scheduler. Bản sao lưu không chứa khóa mã hóa.
7. **Migration mã hóa** (bước 5 ở trên) chạy trong container tạm có mount `scripts/` (image production không chứa `scripts/`); sau đó đặt `APP_CRYPTO_STRICT_READ=1` rồi tạo lại container `app`.
8. **Kiểm tra sau triển khai**: `curl -I https://<domain>/` có `Strict-Transport-Security`; cookie phiên có `Secure; HttpOnly`; `docker ps` không thấy cổng nào publish của app/db; thử đăng nhập sai và xác nhận IP ghi trong `tbl_login_attempts` là IP khách (không phải `172.30.0.10`).
9. **Chạy test từ xa**: Cloudflare chặn User-Agent `Python-urllib` (403); đặt User-Agent giống trình duyệt. `test_untrusted_proxy_headers_do_not_change_origin_or_cookie` chỉ có nghĩa trên HTTP thuần/local, bỏ qua khi test qua HTTPS.
10. **Dọn dữ liệu thử** (tài khoản/đơn hàng tạo khi kiểm thử) trước khi mở chính thức. `docker compose down -v` xóa cả DB và ảnh upload.
11. **Vận hành**: bật *Start Docker Desktop when you sign in*; không chạy `docker compose down -v` trừ khi cố ý.
