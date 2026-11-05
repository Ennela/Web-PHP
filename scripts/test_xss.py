#!/usr/bin/env python3
"""XSS regression: encoding, rich HTML, reflected/stored data and browser checks.

python3 scripts/test_xss.py
MYSQLPASSWORD=... python3 scripts/test_xss.py
Optional isolated Chrome CDP endpoint: XSS_CHROME_URL=http://127.0.0.1:9223
"""
import json
import os
from pathlib import Path
import re
import secrets
import socket
import subprocess
import tempfile
import time
import unittest
import urllib.parse
from html.parser import HTMLParser

from test_csrf import BASE, Browser
from test_security_configuration import RUNTIME, request

ROOT = Path(__file__).resolve().parent.parent
PAYLOAD = 'XSS_MARKER\'\"><img src=x onerror="window.__XSS_EXECUTED=1"><svg onload="window.__XSS_EXECUTED=2"></svg>'
RICH = '<p>Rich <strong>SAFE_FORMAT_MARKER</strong></p>' + PAYLOAD + '<script>window.__XSS_EXECUTED=3</script><a href="javascript:window.__XSS_EXECUTED=4">link</a>'


class Audit(HTMLParser):
    def __init__(self):
        super().__init__()
        self.dangerous = []
        self.scripts = []

    def handle_starttag(self, tag, attributes):
        attrs = dict(attributes)
        if tag == 'script':
            self.scripts.append(attrs)
        for key, value in attributes:
            if key.lower().startswith('on'):
                self.dangerous.append((tag, key))
            if key in ['src', 'href'] and re.match(r'\s*(?:javascript|vbscript):', value or '', re.I):
                self.dangerous.append((tag, key))


class LiveXssTests(unittest.TestCase):
    def test_reflected_payloads_and_nonce_policy(self):
        nonces = []
        for path in ['shop/shop.php?search=', 'shop/shop.php?category=', 'blog/baiviet.php?search=']:
            status, body, headers = request(BASE + path + urllib.parse.quote(PAYLOAD))
            self.assertEqual(status, 200)
            self.assertNotIn('<b>Fatal error</b>', body)
            self.assertIn('XSS_MARKER', body)
            audit = Audit()
            audit.feed(body)
            self.assertEqual(audit.dangerous, [])
            policy = headers['Content-Security-Policy']
            nonce = re.search(r"'nonce-([^']+)'", policy)[1]
            nonces.append(nonce)
            script_policy = re.search(r'(?:^|; )script-src ([^;]+)', policy)[1]
            self.assertNotIn("'unsafe-inline'", script_policy)
            self.assertIn("script-src-attr 'none'", policy)
            self.assertTrue(audit.scripts)
            self.assertTrue(all(script.get('nonce') == nonce for script in audit.scripts))
        self.assertEqual(len(set(nonces)), len(nonces))


@unittest.skipUnless(Path(RUNTIME).is_file(), 'FrankenPHP required for isolated sanitizer checks')
class HelperTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.folder = tempfile.TemporaryDirectory(prefix='webphp-xss-')
        root_literal = "'" + str(ROOT).replace("'", "\\'") + "'"
        php = '''<?php
putenv('APP_ENV=test'); putenv('APP_URL='); putenv('APP_DEBUG=0');
require __ROOT__ . '/config.php';
header('Content-Type: application/json');
$value = $_GET['value'] ?? '';
$result = match ($_GET['op'] ?? '') {
    'html' => clean_html($value), 'url' => safe_url($value),
    'js' => js_value($value), default => e($value),
};
echo json_encode(['result' => $result]);
'''.replace('__ROOT__', root_literal)
        Path(cls.folder.name, 'index.php').write_text(php)
        with socket.socket() as sock:
            sock.bind(('127.0.0.1', 0))
            port = sock.getsockname()[1]
        cls.base = f'http://127.0.0.1:{port}/'
        cls.log = open(Path(cls.folder.name, 'runtime.log'), 'w')
        env = dict(os.environ, APP_ROOT=cls.folder.name, SITE_ADDRESS=f'http://:{port}', SITE_BIND='127.0.0.1')
        cls.process = subprocess.Popen([RUNTIME, 'run', '--config', str(ROOT / 'Caddyfile')], env=env, stdout=cls.log, stderr=cls.log)
        for _ in range(100):
            try:
                with socket.create_connection(('127.0.0.1', port), timeout=0.1):
                    return
            except OSError:
                time.sleep(0.05)
        raise RuntimeError('XSS fixture did not start')

    @classmethod
    def tearDownClass(cls):
        cls.process.terminate()
        cls.process.wait(timeout=10)
        cls.log.close()
        cls.folder.cleanup()

    def helper(self, operation, value):
        status, body, _ = request(self.base + '?' + urllib.parse.urlencode({'op': operation, 'value': value}))
        self.assertEqual(status, 200)
        return json.loads(body)['result']

    def test_text_attribute_and_javascript_contexts(self):
        escaped = self.helper('text', PAYLOAD)
        self.assertNotIn('<img', escaped)
        self.assertIn('&lt;img', escaped)
        self.assertIn('&quot;', escaped)
        payload = '</script><script>window.__XSS_EXECUTED=1</script>\u2028'
        literal = self.helper('js', payload)
        self.assertNotIn('<', literal)
        self.assertEqual(json.loads(literal), payload)

    def test_url_scheme_and_encoding_bypasses(self):
        unsafe = ['javascript:alert(1)', 'JaVaScRiPt:alert(1)', 'java&#x0a;script:alert(1)',
                  '&#106;avascript:alert(1)', '%6a%61vascript:alert(1)', 'data:text/html,test',
                  '//attacker.invalid/a', '\\\\attacker.invalid/a', '%2f%2fattacker.invalid/a',
                  'https://user:pass@example.com/a']
        for value in unsafe:
            with self.subTest(value=value):
                self.assertEqual(self.helper('url', value), '#')
        for value in ['/shop/shop.php', 'uploads/photo.png', 'https://example.com/a?q=1&x=2']:
            self.assertEqual(self.helper('url', value), value)
        self.assertEqual(self.helper('url', 'uploads/anh giay.png'), 'uploads/anh%20giay.png')
        self.assertEqual(self.helper('url', 'https://example.com/anh%20giay.png'), 'https://example.com/anh%20giay.png')

    def test_rich_html_preserves_format_and_removes_active_content(self):
        corpus = [RICH,
                  '<iframe srcdoc="<script>alert(1)</script>"></iframe><object data="x"></object>',
                  '<math><mtext><table><mglyph><style><!--</style><img title="--><img src=x onerror=alert(1)>">',
                  '<form name="location"><input name="innerHTML"></form><p id="location">safe</p>',
                  '<span style="color:red;background-image:url(javascript:alert(1))">color</span>']
        for payload in corpus:
            with self.subTest(payload=payload[:50]):
                cleaned = self.helper('html', payload)
                audit = Audit()
                audit.feed(cleaned)
                self.assertEqual(audit.dangerous, [])
                self.assertEqual(audit.scripts, [])
                self.assertNotRegex(cleaned, r'<(?:iframe|object|svg|math|form|input)\b')
                self.assertNotIn('id="location"', cleaned)
        cleaned = self.helper('html', RICH)
        self.assertIn('<strong>SAFE_FORMAT_MARKER</strong>', cleaned)
        self.assertIn('href="https://example.com"', self.helper('html', '<a href="https://example.com">safe</a>'))


@unittest.skipUnless(os.environ.get('MYSQLPASSWORD'), 'MYSQLPASSWORD required for temporary stored-XSS fixtures')
class StoredXssTests(unittest.TestCase):
    def sql(self, query):
        args = ['mysql', '--protocol=TCP', '-h', os.environ.get('MYSQLHOST', '127.0.0.1'),
                '-P', os.environ.get('MYSQLPORT', '3306'), '-u', os.environ.get('MYSQLUSER', 'root'),
                '-N', os.environ.get('MYSQLDATABASE', 'giaythethao2'), '-e', query]
        return subprocess.check_output(args, env=dict(os.environ, MYSQL_PWD=os.environ['MYSQLPASSWORD']), text=True).strip()

    def text(self, value):
        return 'CONVERT(0x' + value.encode().hex() + ' USING utf8mb4)'

    def login(self, browser, path, username, password):
        status, body, _ = browser.request(path, {'dangnhap': '1', 'taikhoan': username,
                                                'matkhau': password, 'csrf_token': browser.token()})
        self.assertEqual(status, 200)
        self.assertNotIn('<b>Fatal error</b>', body)

    def test_stored_fields_rich_content_and_real_browser(self):
        name = 'xss_test_' + secrets.token_hex(8)
        password = secrets.token_hex(16)
        customer = Browser()
        admin = Browser()
        ids = {}
        try:
            self.sql(f"INSERT INTO tbl_tkkhachhang(username,password,hoten,email) VALUES('{name}','{password}',{self.text(PAYLOAD)},'{name}@example.invalid')")
            ids['customer'] = int(self.sql(f"SELECT makh FROM tbl_tkkhachhang WHERE username='{name}'"))
            self.sql(f"INSERT INTO tbl_qlthanhvien(taikhoan,matkhau,hoten) VALUES('{name}','{password}',{self.text(PAYLOAD)})")
            ids['admin'] = int(self.sql(f"SELECT id FROM tbl_qlthanhvien WHERE taikhoan='{name}'"))
            ids['product'] = int(self.sql(f"INSERT INTO tbl_qlsanpham(tensp,noidung,anhdaidien,nhomsp,giasanpham,giagoc,ngaytao) VALUES({self.text(PAYLOAD)},{self.text(RICH)},{self.text(PAYLOAD)},'XSS_TEST',1000,2000,UNIX_TIMESTAMP()); SELECT LAST_INSERT_ID();"))
            self.sql(f"INSERT INTO tbl_tonkho(masp,size,soluong) VALUES({ids['product']},40,10)")
            ids['blog'] = int(self.sql(f"INSERT INTO tbl_qlbaidang(tieude,noidung,anhdaidien,nguoiphutrach,chedo,ngaytao) VALUES({self.text(PAYLOAD)},{self.text(RICH)},{self.text(PAYLOAD)},'{ids['admin']}','public',UNIX_TIMESTAMP()); SELECT LAST_INSERT_ID();"))
            ids['order'] = int(self.sql(f"INSERT INTO oder(tenkh,diachi,note,makh,status,payment_status,tongtien,ngaytao) VALUES({self.text(PAYLOAD)},{self.text(PAYLOAD)},{self.text(PAYLOAD)},{ids['customer']},'PENDING','UNPAID',1000,UNIX_TIMESTAMP()); SELECT LAST_INSERT_ID();"))
            self.sql(f"INSERT INTO oder_chitiet(madonhang,masp,quantity,price,size) VALUES({ids['order']},{ids['product']},1,1000,40)")
            self.sql(f"INSERT INTO tbl_diachi(makh,hoten,sdt,diachi_cuthe,tinh,macdinh) VALUES({ids['customer']},{self.text(PAYLOAD)},'0900000000',{self.text(PAYLOAD)},'Ha Noi',1)")
            self.sql(f"INSERT INTO tbl_sizegiay(makh,he_size,size_value,ghichu) VALUES({ids['customer']},'EU','40',{self.text(PAYLOAD)})")
            self.login(customer, 'auth/dangnhap.php', name, password)
            self.login(admin, 'admin/canhbao.php', name, password)
            paths = [(customer, 'auth/thongtintaikhoan.php'),
                     (customer, f"shop/chitietsanpham.php?masp={ids['product']}"),
                     (customer, f"blog/chitietbaiviet.php?id={ids['blog']}"),
                     (customer, f"shop/chitietdonhang.php?id={ids['order']}"),
                     (admin, 'admin/quanlikhachhang.php'), (admin, 'admin/quanlithanhvien.php'),
                     (admin, 'admin/quanlisanpham.php'), (admin, 'admin/quanlibaidang.php'),
                     (admin, f"admin/ajax_order_detail.php?id={ids['order']}"),
                     (admin, f"admin/edit_qlsanpham.php?masp={ids['product']}"),
                     (admin, f"admin/edit_qlbaidang.php?id={ids['blog']}")]
            for browser, path in paths:
                with self.subTest(path=path):
                    status, body, _ = browser.request(path)
                    self.assertEqual(status, 200)
                    self.assertNotIn('<b>Fatal error</b>', body)
                    audit = Audit()
                    audit.feed(body)
                    self.assertEqual(audit.dangerous, [])
            if os.environ.get('XSS_CHROME_URL'):
                fixture = dict(ids, base=BASE, chrome=os.environ['XSS_CHROME_URL'], payload=PAYLOAD,
                               customerCookie=next(c.value for c in customer.cookies if c.name == 'PHPSESSID'),
                               adminCookie=next(c.value for c in admin.cookies if c.name == 'PHPSESSID'))
                subprocess.run(['node', '--experimental-websocket', str(ROOT / 'scripts/test_xss_browser.cjs')], input=json.dumps(fixture), text=True, check=True)
            # Admin edits sanitize new rich content before storage as well.
            forms = [
                (f"admin/edit_qlsanpham.php?masp={ids['product']}", 'tbl_qlsanpham', 'masp', ids['product'],
                 {'tensp': name, 'giasanpham': '1000', 'giagoc': '2000', 'nhomsp': 'XSS_TEST'}),
                (f"admin/edit_qlbaidang.php?id={ids['blog']}", 'tbl_qlbaidang', 'id', ids['blog'],
                 {'tieude': name, 'nguoiphutrach': str(ids['admin']), 'chedo': 'public'}),
            ]
            for path, table, key, identifier, fields in forms:
                status, body, _ = admin.request(path, dict(fields, **{'btn-save': '1', 'csrf_token': admin.token(), 'noidung': RICH}))
                self.assertEqual(status, 200)
                self.assertNotIn('<b>Fatal error</b>', body)
                stored = self.sql(f'SELECT noidung FROM {table} WHERE {key}={identifier}')
                audit = Audit()
                audit.feed(stored)
                self.assertEqual(audit.dangerous, [])
                self.assertEqual(audit.scripts, [])
                self.assertIn('<strong>SAFE_FORMAT_MARKER</strong>', stored)
        finally:
            if 'order' in ids:
                self.sql(f"DELETE FROM oder_chitiet WHERE madonhang={ids['order']}; DELETE FROM oder WHERE id={ids['order']}")
            if 'product' in ids:
                self.sql(f"DELETE FROM tbl_tonkho WHERE masp={ids['product']}; DELETE FROM tbl_qlsanpham WHERE masp={ids['product']}")
            if 'blog' in ids:
                self.sql(f"DELETE FROM tbl_qlbaidang WHERE id={ids['blog']}")
            if 'customer' in ids:
                self.sql(f"DELETE FROM tbl_diachi WHERE makh={ids['customer']}; DELETE FROM tbl_sizegiay WHERE makh={ids['customer']}")
            self.sql(f"DELETE FROM tbl_tkkhachhang WHERE username='{name}'; DELETE FROM tbl_qlthanhvien WHERE taikhoan='{name}'")


if __name__ == '__main__':
    unittest.main(verbosity=2)
