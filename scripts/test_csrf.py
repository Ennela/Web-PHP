#!/usr/bin/env python3
"""HTTP regression checks. Run against a running local copy of the application.

    python3 scripts/test_csrf.py
    CSRF_BASE_URL=http://localhost/WEB-PHP/ python3 scripts/test_csrf.py

No database credentials needed; tests use anonymous sessions and non-existing cart ids.
"""
import http.cookiejar
import json
import os
import re
import unittest
import urllib.error
import urllib.parse
import urllib.request
from html.parser import HTMLParser


BASE = os.environ.get('CSRF_BASE_URL', 'http://127.0.0.1:8000/').rstrip('/') + '/'


class Browser:
    def __init__(self):
        self.cookies = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.cookies))

    def request(self, path, data=None, headers=None, method=None):
        if isinstance(data, dict):
            data = urllib.parse.urlencode(data).encode()
        request = urllib.request.Request(BASE + path, data=data, headers=headers or {}, method=method)
        try:
            response = self.opener.open(request, timeout=15)
        except urllib.error.HTTPError as error:
            response = error
        with response:
            return response.status, response.read().decode('utf-8', errors='replace'), response.headers

    def token(self):
        status, body, _ = self.request('auth/dangki.php')
        assert status == 200, status
        return re.search(r'<meta name="csrf-token" content="([0-9a-f]{64})">', body)[1]


class FormAudit(HTMLParser):
    def __init__(self):
        super().__init__()
        self.forms = []
        self.current = None
        self.nested = False

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == 'form':
            self.nested |= self.current is not None
            self.current = {'method': attrs.get('method', 'get').lower(), 'token': None}
        elif tag == 'input' and self.current is not None and attrs.get('name') == 'csrf_token':
            self.current['token'] = attrs.get('value')

    def handle_endtag(self, tag):
        if tag == 'form' and self.current is not None:
            self.forms.append(self.current)
            self.current = None


class CsrfTests(unittest.TestCase):
    def setUp(self):
        self.browser = Browser()
        self.token = self.browser.token()

    def assert_no_php_error(self, body):
        self.assertNotRegex(body, r'<b>(?:Fatal error|Warning|Parse error|Notice)</b>')

    def test_all_browser_mutations_reject_missing_token(self):
        paths = [
            'auth/dangki.php', 'auth/dangnhap.php',
            'auth/api_capnhat_thongtin.php', 'auth/api_datlamatkhau.php',
            'auth/api_diachi.php', 'auth/api_doimatkhau.php',
            'auth/api_quenmatkhau.php', 'auth/api_sizegiay.php', 'auth/api_upload_avatar.php',
            'auth/thongtintaikhoan.php', 'home/lienhe.php',
            'shop/giohang.php?action=add', 'shop/giohang.php?action=delete',
            'shop/giohang.php?action=submit', 'shop/infodathang.php?action=submit',
            'shop/dathangonline.php?action=submit', 'shop/cart_update_ajax.php',
            'shop/api_order_action.php', 'shop/tradonhang.php',
            'admin/canhbao.php', 'admin/dangnhap.php', 'admin/trangchu.php',
            'admin/thembaidang.php', 'admin/themdanhmuc.php', 'admin/themkhachhang.php',
            'admin/themsanpham.php', 'admin/themthanhvien.php',
            'admin/edit_qlbaidang.php', 'admin/edit_qlsanpham.php', 'admin/edit_qlthanhvien.php',
            'admin/quanlibaidang.php', 'admin/quanlidanhmuc.php', 'admin/quanlidonhang.php',
            'admin/quanlikhachhang.php', 'admin/quanlisanpham.php', 'admin/quanlithanhvien.php',
            'admin/delete_qlbaidang.php', 'admin/delete_qldonhang.php',
            'admin/delete_qlkhachhang.php', 'admin/delete_qlsanpham.php',
            'admin/delete_qlthanhvien.php', 'admin/gallery_delete.php', 'admin/inhoadon.php',
        ]
        for path in paths:
            with self.subTest(path=path):
                status, body, _ = self.browser.request(path, data={})
                self.assertEqual(status, 403)
                self.assert_no_php_error(body)

    def test_reject_wrong_array_query_and_other_session_tokens(self):
        path = 'shop/cart_update_ajax.php'
        for data in [{'csrf_token': '0' * 64}, {'csrf_token[]': self.token}, {}]:
            with self.subTest(data=list(data)):
                status, body, _ = self.browser.request(path, data=data)
                self.assertEqual(status, 403)
                self.assertEqual(json.loads(body)['error'], 'CSRF_INVALID')
        status, _, _ = self.browser.request(path + '?csrf_token=' + self.token, data={})
        self.assertEqual(status, 403)
        status, _, _ = Browser().request(path, data={'csrf_token': self.token})
        self.assertEqual(status, 403)
        self.assertEqual(self.browser.token(), self.token, 'Token must remain stable between tabs')

    def test_json_multipart_and_form_transport(self):
        path = 'shop/cart_update_ajax.php'
        payload = json.dumps({'masp': 2147483647, 'action': 'delete'}).encode()
        headers = {'Content-Type': 'application/json', 'X-CSRF-Token': self.token}
        status, body, _ = self.browser.request(path, payload, headers)
        self.assertEqual(status, 200)
        self.assertTrue(json.loads(body)['success'])
        headers['X-CSRF-Token'] = '0' * 64
        status, _, _ = self.browser.request(path, payload, headers)
        self.assertEqual(status, 403)
        # Form token is accepted before application-level validation.
        status, body, _ = self.browser.request('auth/api_datlamatkhau.php', {'csrf_token': self.token})
        self.assertEqual(status, 200)
        self.assertFalse(json.loads(body)['success'])
        # Multipart uploads are protected without changing browser-generated boundaries.
        boundary = 'csrf-regression-boundary'
        payload = ('--' + boundary + '\r\nContent-Disposition: form-data; name="csrf_token"'
                   + '\r\n\r\n' + self.token + '\r\n--' + boundary + '--\r\n').encode()
        status, body, _ = self.browser.request('auth/api_upload_avatar.php', payload, {
            'Content-Type': 'multipart/form-data; boundary=' + boundary,
        })
        self.assertEqual(status, 200)
        self.assertFalse(json.loads(body)['success'])  # Authentication still required.

    def test_get_and_head_cannot_trigger_mutations(self):
        paths = [
            'shop/giohang.php?action=add&masp=1&quantity=1',
            'shop/giohang.php?action=delete&masp=1',
            'shop/infodathang.php?action=submit', 'shop/dathangonline.php?action=submit',
            'shop/cart_update_ajax.php', 'shop/api_order_action.php',
            'admin/delete_qlbaidang.php?id=1', 'admin/delete_qldonhang.php?id=1',
            'admin/delete_qlkhachhang.php?makh=1', 'admin/delete_qlsanpham.php?masp=1',
            'admin/delete_qlthanhvien.php?id=1', 'admin/gallery_delete.php?id=1',
            'admin/inhoadon.php?id=1',
        ]
        for path in paths:
            for method in ['GET', 'HEAD']:
                with self.subTest(path=path, method=method):
                    status, _, headers = self.browser.request(path, method=method)
                    self.assertEqual(status, 405)
                    self.assertEqual(headers['Allow'], 'POST')

    def test_public_forms_and_cookie_attributes(self):
        for path in ['', 'auth/dangnhap.php', 'auth/dangki.php', 'auth/quenmatkhau.php',
                     'home/lienhe.php', 'shop/shop.php', 'shop/giohang.php',
                     'admin/canhbao.php', 'admin/dangnhap.php']:
            with self.subTest(path=path):
                status, body, _ = self.browser.request(path)
                self.assertEqual(status, 200)
                self.assert_no_php_error(body)
                audit = FormAudit()
                audit.feed(body)
                self.assertFalse(audit.nested)
                for form in audit.forms:
                    if form['method'] == 'post':
                        self.assertRegex(form['token'] or '', r'^[0-9a-f]{64}$')
                    else:
                        self.assertIsNone(form['token'], 'CSRF token must never enter GET URLs')
        _, _, headers = Browser().request('auth/dangki.php')
        cookie = headers['Set-Cookie'].lower()
        self.assertIn('httponly', cookie)
        self.assertIn('samesite=lax', cookie)

    def test_unsafe_methods_and_signed_callback_boundaries(self):
        for method in ['PUT', 'PATCH', 'DELETE']:
            status, _, _ = self.browser.request('auth/dangki.php', b'', method=method)
            self.assertEqual(status, 405)
        for query in ['', '?vnp_TxnRef=1&vnp_ResponseCode=24', '?vnp_SecureHash[]=bad']:
            status, _, _ = self.browser.request('shop/vnpay_return.php' + query)
            self.assertIn(status, [403, 503])  # Payment is disabled until merchant secrets are configured.
        status, _, _ = self.browser.request('webhooks/mailjet_webhook.php', b'[]', {
            'Content-Type': 'application/json', 'X-Webhook-Secret': 'invalid-secret',
        })
        self.assertIn(status, [401, 503])


if __name__ == '__main__':
    unittest.main(verbosity=2)
