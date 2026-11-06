#!/usr/bin/env python3
"""Redirect regression tests. Isolated fixtures never contact external destinations.

python3 scripts/test_redirects.py
MYSQLPASSWORD=... python3 scripts/test_redirects.py  # includes temporary login fixture
"""
import json
import os
from pathlib import Path
import secrets
import socket
import subprocess
import tempfile
import time
import unittest
import urllib.parse
import urllib.request

from test_csrf import BASE, Browser
import test_csrf_authenticated
from test_security_configuration import RUNTIME, NoRedirect, request

ROOT = Path(__file__).resolve().parent.parent


@unittest.skipUnless(Path(RUNTIME).is_file(), 'FrankenPHP required for isolated policy checks')
class RedirectPolicyTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.folder = tempfile.TemporaryDirectory(prefix='webphp-redirect-')
        cls.addClassCleanup(cls.folder.cleanup)
        php = r'''<?php
putenv('APP_ENV=test'); putenv('APP_DEBUG=0');
putenv('APP_URL=https://shop.example.test' . ($_GET['mount'] ?? '/'));
require __ROOT__ . '/config.php';
$value = $_GET['value'] ?? '';
$op = $_GET['op'] ?? '';
switch ($op) {
    case 'redirect': app_redirect($value, 303);
    case 'payment_redirect': app_payment_redirect($value);
    case 'https': app_https_redirect('https://shop.example.test/', $value);
    case 'require_user': require_user($value); break;
    case 'require_admin': require_admin($value); break;
    case 'logout': secure_logout($value); break;
    default:
        header('Content-Type: application/json');
        echo json_encode(match ($op) {
            'payment' => app_payment_redirect_url($value),
            'return' => app_return_destination($value),
            default => app_local_redirect_url($value),
        });
        exit;
}
echo 'REDIRECT_FELL_THROUGH';
'''.replace('__ROOT__', "'" + str(ROOT).replace("'", "\\'") + "'")
        Path(cls.folder.name, 'index.php').write_text(php)
        with socket.socket() as sock:
            sock.bind(('127.0.0.1', 0))
            port = sock.getsockname()[1]
        cls.base = f'http://127.0.0.1:{port}/'
        cls.log = open(Path(cls.folder.name, 'runtime.log'), 'w')
        cls.addClassCleanup(cls.log.close)
        env = dict(os.environ, APP_ROOT=cls.folder.name, SITE_ADDRESS=f'http://:{port}', SITE_BIND='127.0.0.1')
        cls.process = subprocess.Popen([RUNTIME, 'run', '--config', str(ROOT / 'Caddyfile')], env=env, stdout=cls.log, stderr=cls.log)
        def stop():
            cls.process.terminate()
            cls.process.wait(timeout=10)
        cls.addClassCleanup(stop)
        for _ in range(100):
            try:
                with socket.create_connection(('127.0.0.1', port), timeout=0.1):
                    return
            except OSError:
                time.sleep(0.05)
        raise RuntimeError('Redirect fixture did not start')

    def call(self, value, op='local', mount='/'):
        return request(self.base + '?' + urllib.parse.urlencode({'op': op, 'value': value, 'mount': mount}))

    def test_allowed_local_urls_and_subdirectory(self):
        valid = {
            '/': '/', '/shop/shop.php?search=giay%20nam&page=2': '/shop/shop.php?search=giay%20nam&page=2',
            'https://shop.example.test/shop/giohang.php': '/shop/giohang.php',
            'https://SHOP.EXAMPLE.TEST:443/auth/thongtintaikhoan.php#profile': '/auth/thongtintaikhoan.php#profile',
            '/admin/quanlidonhang.php?page=2': '/admin/quanlidonhang.php?page=2',
        }
        for value, expected in valid.items():
            with self.subTest(value=value):
                status, body, _ = self.call(value)
                self.assertEqual(status, 200)
                self.assertEqual(json.loads(body), expected)
        for value, expected in [('/store/shop/shop.php', '/store/shop/shop.php'),
                                ('https://shop.example.test/store/', '/store/'),
                                ('/shop/shop.php', None), ('/store2/shop/shop.php', None)]:
            status, body, _ = self.call(value, mount='/store/')
            self.assertEqual(status, 200)
            self.assertEqual(json.loads(body), expected)

    def test_external_ambiguous_encoded_and_private_destinations(self):
        targets = [
            'https://attacker.invalid/', '//attacker.invalid/', '///attacker.invalid/',
            '/\\attacker.invalid/', '\\\\attacker.invalid/',
            'https://shop.example.test.attacker.invalid/shop/shop.php',
            'https://shop.example.test@attacker.invalid/shop/shop.php',
            'https://attacker.invalid@shop.example.test/shop/shop.php',
            'https://shop.example.test:444/shop/shop.php', 'http://shop.example.test/shop/shop.php',
            'https://shop.example.test./shop/shop.php', 'javascript:alert(1)', 'data:text/html,test',
            '/%2f%2fattacker.invalid', '/%252f%252fattacker.invalid',
            '/shop/../admin/trangchu.php', '/shop/%2e%2e/admin/trangchu.php',
            '/shop/shop.php/extra', '/shop//shop.php', '/.env', '/includes/security.php',
            '/admin/delete_qldonhang.php?id=1', '/auth/api_sizegiay.php',
            '/shop/shop.php?x=%0d%0aLocation:https://attacker.invalid',
            '/shop/shop.php?x=%250d%250a', '/shop/shop.php?x=%5c',
            '/shop/shop.php?x=%00', '/shop/shop.php?x=%zz', ' /shop/shop.php',
            '/shop/shop.php\n', 'shop/shop.php', '', '/shop/shop.php?x=' + 'a' * 8192,
        ]
        for value in targets:
            with self.subTest(value=value[:100]):
                status, body, _ = self.call(value)
                self.assertEqual(status, 200)
                self.assertIsNone(json.loads(body))
                status, body, headers = self.call(value, 'redirect')
                self.assertEqual(status, 400)
                self.assertIsNone(headers.get('Location'))
                self.assertNotIn('REDIRECT_FELL_THROUGH', body)
        status, _, headers = request(self.base + '?op=redirect&value[]=shop')
        self.assertEqual(status, 400)
        self.assertIsNone(headers.get('Location'))

    def test_header_status_exit_and_authorization_helpers(self):
        for op, target, status in [('redirect', '/shop/shop.php', 303),
                                    ('require_user', '/auth/dangnhap.php', 302),
                                    ('require_admin', '/admin/canhbao.php', 302),
                                    ('logout', '/auth/dangnhap.php', 302)]:
            code, body, headers = self.call(target, op)
            self.assertEqual(code, status)
            self.assertEqual(headers['Location'], target)
            self.assertNotIn('REDIRECT_FELL_THROUGH', body)
            code, body, headers = self.call('https://attacker.invalid/', op)
            self.assertEqual(code, 400)
            self.assertIsNone(headers.get('Location'))
            self.assertNotIn('REDIRECT_FELL_THROUGH', body)

    def test_payment_exact_endpoint_and_https_upgrade(self):
        endpoint = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'
        valid = [endpoint + '?vnp_TxnRef=123&vnp_ReturnUrl=https%3A%2F%2Fshop.example.test%2Fshop%2Fvnpay_return.php',
                 'https://pay.vnpay.vn/vpcpay.html?vnp_TxnRef=123']
        for url in valid:
            status, _, headers = self.call(url, 'payment_redirect')
            self.assertEqual(status, 303)
            self.assertEqual(headers['Location'], url)
        for url in [endpoint.replace('https:', 'http:'), endpoint + '/extra', endpoint + '#evil',
                    endpoint.replace('.vn/', '.vn.attacker.invalid/'),
                    endpoint.replace('.vn/', '.vn:443/'), endpoint.replace('.vn/', '.vn@attacker.invalid/'),
                    endpoint.replace('/paymentv2/', '/other/'), endpoint + '?x=%0a',
                    '//sandbox.vnpayment.vn/paymentv2/vpcpay.html']:
            status, _, headers = self.call(url, 'payment_redirect')
            self.assertEqual(status, 400)
            self.assertIsNone(headers.get('Location'))
        status, _, headers = self.call('/shop/shop.php?search=giay%20nam', 'https')
        self.assertEqual(status, 308)
        self.assertEqual(headers['Location'], 'https://shop.example.test/shop/shop.php?search=giay%20nam')
        for uri in ['//attacker.invalid/', '/\\attacker.invalid/', '/x%0d%0aevil', 'https://attacker.invalid/']:
            self.assertEqual(self.call(uri, 'https')[0], 400)
        for mount in ['//attacker.invalid/', '/store/../', '/%2f/', '/store\\evil/']:
            self.assertEqual(self.call('/shop/shop.php', mount=mount)[0], 503)


class LiveRedirectTests(unittest.TestCase):
    def test_login_destination_ids_and_tampering(self):
        for page in ['home', 'shop', 'cart', 'account', 'orders']:
            status, body, _ = request(BASE + 'auth/dangnhap.php?return_to=' + page)
            self.assertEqual(status, 200)
            self.assertIn(f'name="return_to" value="{page}"', body)
        for page in ['https://example.org', '//example.org', '/admin/trangchu.php', 'admin', '../admin', 'SHOP', '']:
            status, _, headers = request(BASE + 'auth/dangnhap.php?' + urllib.parse.urlencode({'return_to': page}))
            self.assertEqual(status, 400)
            self.assertIsNone(headers.get('Location'))
        self.assertEqual(request(BASE + 'auth/dangnhap.php?return_to[]=shop')[0], 400)
        browser = Browser()
        token = browser.token()
        status, _, headers = browser.request('auth/dangnhap.php', {
            'dangnhap': '1', 'return_to': 'https://example.org', 'csrf_token': token,
        })
        self.assertEqual(status, 400)
        self.assertIsNone(headers.get('Location'))

    def test_direct_admin_and_account_access_still_require_login(self):
        for path, destination in [('admin/trangchu.php', 'admin/canhbao.php'),
                                   ('auth/thongtintaikhoan.php', 'auth/dangnhap.php')]:
            status, _, headers = request(BASE + path + '?return_to=shop')
            self.assertEqual(status, 302)
            self.assertEqual(headers['Location'], urllib.parse.urlsplit(BASE).path + destination)

    @unittest.skipUnless(os.environ.get('MYSQLPASSWORD'), 'MYSQLPASSWORD required for login fixture')
    def test_successful_login_and_rejected_post_target(self):
        sql = lambda query: test_csrf_authenticated.AuthenticatedCsrfTests.sql(self, query)
        username = 'redirect_test_' + secrets.token_hex(8)
        password = secrets.token_hex(16)
        browser = Browser()
        browser.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(browser.cookies), NoRedirect())
        try:
            sql("INSERT INTO tbl_tkkhachhang(hoten,username,password,email) "
                f"VALUES('Redirect Test','{username}','{password}','{username}@example.invalid')")
            for target in ['shop', 'cart', 'account', 'orders']:
                token = browser.token()
                credentials = dict(dangnhap='1', taikhoan=username, matkhau=password, csrf_token=token)
                status, _, headers = browser.request('auth/dangnhap.php', dict(credentials, return_to='admin'))
                self.assertEqual(status, 400)
                status, body, headers = browser.request('auth/dangnhap.php', dict(credentials, return_to=target))
                self.assertEqual(status, 303)
                path = {'shop': 'shop/shop.php', 'cart': 'shop/giohang.php',
                        'account': 'auth/thongtintaikhoan.php', 'orders': 'shop/tradonhang.php'}[target]
                self.assertEqual(headers['Location'], urllib.parse.urlsplit(BASE).path + path)
                self.assertEqual(browser.request(path)[0], 200)
                # A logged-in customer still cannot forward into the administrator page.
                self.assertEqual(browser.request('admin/trangchu.php')[1], '')
                self.assertEqual(browser.request('admin/trangchu.php')[2]['Location'],
                                 urllib.parse.urlsplit(BASE).path + 'admin/canhbao.php')
        finally:
            sql(f"DELETE FROM tbl_tkkhachhang WHERE username='{username}'")


if __name__ == '__main__':
    unittest.main(verbosity=2)
