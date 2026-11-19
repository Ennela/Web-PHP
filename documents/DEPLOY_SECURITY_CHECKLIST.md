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
   `test_transport.py`, và thử tay: đăng nhập khách/admin, quên + đặt lại mật khẩu, đặt hàng (COD + VNPay), tra cứu đơn.
