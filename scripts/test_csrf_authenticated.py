#!/usr/bin/env python3
"""Checks with temporary database records, removed in finally.

Set MYSQLPASSWORD (plus optional MYSQLHOST, MYSQLPORT, MYSQLUSER, MYSQLDATABASE),
then run: python3 scripts/test_csrf_authenticated.py
The database must belong to the running local application.
"""
import json
import os
import re
import secrets
import subprocess
import unittest

from test_csrf import Browser, FormAudit


@unittest.skipUnless(os.environ.get('MYSQLPASSWORD'), 'MYSQLPASSWORD is required for temporary fixtures')
class AuthenticatedCsrfTests(unittest.TestCase):
    def sql(self, query):
        env = dict(os.environ, MYSQL_PWD=os.environ['MYSQLPASSWORD'])
        args = [
            'mysql', '--protocol=TCP', '-h', os.environ.get('MYSQLHOST', '127.0.0.1'),
            '-P', os.environ.get('MYSQLPORT', '3306'), '-u', os.environ.get('MYSQLUSER', 'root'),
            '-N', os.environ.get('MYSQLDATABASE', 'giaythethao2'), '-e', query,
        ]
        return subprocess.check_output(args, env=env, text=True).strip()

    def audit(self, browser, path):
        status, body, _ = browser.request(path)
        self.assertEqual(status, 200, path)
        self.assertNotRegex(body, r'<b>(?:Fatal error|Warning|Parse error|Notice)</b>')
        audit = FormAudit()
        audit.feed(body)
        self.assertFalse(audit.nested, path)
        for form in audit.forms:
            if form['method'] == 'post':
                self.assertRegex(form['token'] or '', r'^[0-9a-f]{64}$', path)
        return body

    def test_registration_login_apis_admin_deletion_and_logout(self):
        username = 'csrf_test_' + secrets.token_hex(8)
        password = secrets.token_hex(16)
        customer = Browser()
        admin = Browser()
        user_id = order_id = None
        try:
            token = customer.token()
            old_session = next(c.value for c in customer.cookies if c.name == 'PHPSESSID')
            status, _, _ = customer.request('auth/dangki.php', {
                'dangki': '1', 'hoten': 'CSRF Test', 'taikhoan': username,
                'email': username + '@example.invalid', 'matkhau': password, 'csrf_token': token,
            })
            self.assertEqual(status, 200)
            user_id = self.sql(f"SELECT makh FROM tbl_tkkhachhang WHERE username='{username}'")
            self.assertTrue(user_id.isdigit(), 'Registration did not create the temporary account')
            customer.request('auth/dangnhap.php', {
                'dangnhap': '1', 'taikhoan': username, 'matkhau': password, 'csrf_token': token,
            })
            new_token = customer.token()
            self.assertNotEqual(new_token, token)
            self.assertNotEqual(old_session, next(c.value for c in customer.cookies if c.name == 'PHPSESSID'))
            self.assertEqual(customer.request('auth/api_sizegiay.php', {'csrf_token': token})[0], 403)
            for path in ['auth/thongtintaikhoan.php', 'shop/tradonhang.php', 'shop/giohang.php']:
                self.audit(customer, path)
            # A legacy GET logout must not end the logged-in session.
            customer.request('auth/dangnhap.php?login=dangxuat')
            body = self.audit(customer, 'auth/thongtintaikhoan.php')
            self.assertIn('id="profileForm"', body)
            status, body, _ = customer.request('auth/api_sizegiay.php', {
                'action': 'save', 'he_size': 'EU', 'size_value': '40', 'csrf_token': new_token,
            })
            self.assertEqual(status, 200)
            self.assertTrue(json.loads(body)['success'])
            self.assertEqual(self.sql(f'SELECT COUNT(*) FROM tbl_sizegiay WHERE makh={user_id}'), '1')
            # Actual target records remain unchanged under forged authenticated requests.
            order_id = self.sql(
                "INSERT INTO oder(order_code,tenkh,makh,status,payment_status,ngaytao) "
                f"VALUES(NULL,'CSRF Test',{user_id},'PENDING','UNPAID',UNIX_TIMESTAMP()); SELECT LAST_INSERT_ID();"
            )
            payload = json.dumps({'action': 'cancel', 'order_id': int(order_id)}).encode()
            status, _, _ = customer.request('shop/api_order_action.php', payload, {'Content-Type': 'application/json'})
            self.assertEqual(status, 403)
            self.assertEqual(self.sql(f'SELECT status FROM oder WHERE id={order_id}'), 'PENDING')
            status, body, _ = customer.request('shop/api_order_action.php', payload, {
                'Content-Type': 'application/json', 'X-CSRF-Token': new_token,
            })
            self.assertEqual(status, 200)
            self.assertTrue(json.loads(body)['success'])
            self.assertEqual(self.sql(f'SELECT status FROM oder WHERE id={order_id}'), 'CANCELLED')
            # An authenticated customer still cannot use admin deletion with a valid token.
            status, _, _ = customer.request('admin/delete_qldonhang.php', {'id': order_id, 'csrf_token': new_token})
            self.assertEqual(status, 403)
            self.sql(f"INSERT INTO tbl_qlthanhvien(hoten,taikhoan,matkhau) VALUES('CSRF Test','{username}','{password}')")
            for login in ['admin/canhbao.php', 'admin/dangnhap.php']:
                admin_token = admin.token()
                old_session = next(c.value for c in admin.cookies if c.name == 'PHPSESSID')
                status, body, _ = admin.request(login, {
                    'dangnhap': '1', 'taikhoan': username, 'matkhau': password, 'csrf_token': admin_token,
                })
                self.assertEqual(status, 200)
                self.assertNotRegex(body, r'<b>(?:Fatal error|Warning)</b>')
                self.assertNotEqual(old_session, next(c.value for c in admin.cookies if c.name == 'PHPSESSID'))
                self.assertNotEqual(admin.token(), admin_token)
            admin_token = admin.token()
            for path in ['admin/trangchu.php', 'admin/quanlidonhang.php', 'admin/quanlisanpham.php',
                         'admin/quanlikhachhang.php', 'admin/quanlithanhvien.php', 'admin/quanlibaidang.php',
                         'admin/themsanpham.php', 'admin/thembaidang.php', 'admin/themthanhvien.php',
                         'admin/quanlidanhmuc.php', 'admin/themdanhmuc.php', 'admin/themkhachhang.php']:
                self.audit(admin, path)
            status, _, _ = admin.request('admin/delete_qldonhang.php', {'id': order_id})
            self.assertEqual(status, 403)
            self.assertEqual(self.sql(f'SELECT COUNT(*) FROM oder WHERE id={order_id}'), '1')
            status, _, _ = admin.request('admin/delete_qldonhang.php', {'id': order_id, 'csrf_token': admin_token})
            self.assertEqual(status, 200)  # Follows the 303 redirect to the list.
            self.assertEqual(self.sql(f'SELECT COUNT(*) FROM oder WHERE id={order_id}'), '0')
            for path, fields in [
                ('admin/quanlisanpham.php?action=search', {'masp': '', 'tensp': 'CSRF'}),
                ('admin/quanlikhachhang.php?action=search', {'makh': '', 'hoten': 'CSRF'}),
            ]:
                status, body, _ = admin.request(path, dict(fields, csrf_token=admin_token))
                self.assertEqual(status, 200)
                self.assertNotIn('Unknown column', body)
            # Logout invalidates the old session token, including in already open tabs.
            self.assertEqual(customer.request('auth/dangnhap.php', {'login': 'dangxuat'})[0], 403)
            customer.request('auth/dangnhap.php', {'login': 'dangxuat', 'csrf_token': new_token})
            self.assertNotEqual(customer.token(), new_token)
            self.assertEqual(customer.request('auth/api_sizegiay.php', {'csrf_token': new_token})[0], 403)
        finally:
            if order_id and order_id.isdigit():
                self.sql(f'DELETE FROM oder WHERE id={order_id}')
            self.sql(f"DELETE FROM tbl_qlthanhvien WHERE taikhoan='{username}'")
            if user_id and user_id.isdigit():
                self.sql(f'DELETE FROM tbl_sizegiay WHERE makh={user_id}')
            self.sql(f"DELETE FROM tbl_tkkhachhang WHERE username='{username}'")


if __name__ == '__main__':
    unittest.main(verbosity=2)
