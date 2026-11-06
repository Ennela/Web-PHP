#!/usr/bin/env python3
"""Isolated application transport-policy checks, no database or external traffic.

python3 scripts/test_transport.py
Real TLS handshakes are covered by scripts/test_tls_listener.py.
"""
import json
import os
from pathlib import Path
import socket
import subprocess
import tempfile
import time
import unittest
import urllib.error
import urllib.parse
import urllib.request

from test_security_configuration import NoRedirect, RUNTIME

ROOT = Path(__file__).resolve().parent.parent


@unittest.skipUnless(Path(RUNTIME).is_file(), 'FrankenPHP required')
class TransportPolicyTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.folder = tempfile.TemporaryDirectory(prefix='webphp-transport-')
        cls.addClassCleanup(cls.folder.cleanup)
        php = r'''<?php
putenv('APP_ENV=' . ($_GET['env'] ?? 'test'));
putenv('APP_DEBUG=0');
putenv('APP_URL=' . ($_GET['canonical'] ?? 'https://shop.example.test/'));
putenv('APP_REQUIRE_HTTPS=' . ($_GET['enforce'] ?? '1'));
putenv('APP_TRUSTED_HOSTS=');
putenv('TRUSTED_PROXIES=' . ($_GET['proxy'] ?? ''));
if (isset($_GET['backend_https'])) $_SERVER['HTTPS'] = $_GET['backend_https'];
require __ROOT__ . '/config.php';
header('Content-Type: application/json');
echo json_encode(['handler_ran' => true, 'https' => app_is_https(), 'base' => BASE_URL,
                  'session_active' => session_status() === PHP_SESSION_ACTIVE]);
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
                if cls.process.poll() is not None:
                    raise RuntimeError('Transport fixture failed to start')
                time.sleep(0.05)
        raise RuntimeError('Transport fixture did not start')

    def call(self, params=None, headers=None, method='GET', body=None):
        url = self.base + '?' + urllib.parse.urlencode(params or {})
        req = urllib.request.Request(url, headers={'Host': 'shop.example.test', **(headers or {})}, method=method, data=body)
        try:
            response = urllib.request.build_opener(NoRedirect()).open(req, timeout=10)
        except urllib.error.HTTPError as error:
            response = error
        with response:
            return response.status, response.read().decode(), response.headers

    def test_get_upgrades_before_session_and_never_uses_forwarded_host(self):
        for method in ['GET', 'HEAD']:
            status, body, headers = self.call(headers={
                'X-Forwarded-Proto': 'https', 'X-Forwarded-Host': 'attacker.invalid',
                'Forwarded': 'proto=https;host=attacker.invalid',
            }, method=method)
            self.assertEqual(status, 308)
            self.assertTrue(headers['Location'].startswith('https://shop.example.test/'))
            self.assertNotIn('attacker.invalid', headers['Location'])
            self.assertIsNone(headers.get('Set-Cookie'))
            self.assertIsNone(headers.get('Strict-Transport-Security'))
            self.assertNotIn('handler_ran', body)

    def test_plaintext_post_rejected_without_redirect_session_or_handler(self):
        for env in ['local', 'staging', 'production']:
            status, body, headers = self.call({'env': env}, method='POST',
                body=b'taikhoan=temporary&matkhau=not-a-real-password')
            self.assertEqual(status, 400)
            self.assertIsNone(headers.get('Location'))
            self.assertIsNone(headers.get('Set-Cookie'))
            self.assertNotIn('handler_ran', body)
            self.assertNotIn('not-a-real-password', body)

    def test_secure_cookie_hsts_and_mixed_content_policy(self):
        status, body, headers = self.call({'env': 'production', 'proxy': '127.0.0.1/32'},
                                         {'X-Forwarded-Proto': 'https'})
        self.assertEqual(status, 200)
        self.assertTrue(json.loads(body)['https'])
        cookie = headers['Set-Cookie'].lower()
        self.assertIn('; secure', cookie)
        self.assertIn('; httponly', cookie)
        self.assertIn('samesite=lax', cookie)
        self.assertEqual(headers['Strict-Transport-Security'], 'max-age=31536000')
        self.assertIn('upgrade-insecure-requests', headers['Content-Security-Policy'])
        self.assertIn('no-store', headers['Cache-Control'])

    def test_trusted_proxy_original_scheme_wins_over_backend_tls(self):
        status, body, headers = self.call({'proxy': '127.0.0.1/32', 'backend_https': 'on'})
        self.assertEqual(status, 400, 'Backend TLS cannot replace a missing original-client scheme')
        self.assertIsNone(headers.get('Set-Cookie'))
        self.assertNotIn('handler_ran', body)
        status, body, headers = self.call({'proxy': '127.0.0.1/32', 'backend_https': 'on'},
                                         {'X-Forwarded-Proto': 'http'})
        self.assertEqual(status, 308)
        self.assertIsNone(headers.get('Set-Cookie'))
        self.assertNotIn('handler_ran', body)
        for proto in ['https,http', 'https, https', 'HTTPS', 'garbage']:
            status, body, headers = self.call({'proxy': '127.0.0.1/32'}, {'X-Forwarded-Proto': proto})
            self.assertEqual(status, 400)
            self.assertIsNone(headers.get('Set-Cookie'))

    def test_bad_proxy_and_https_settings_fail_closed(self):
        for params in [
            {'proxy': '0.0.0.0/0'}, {'proxy': '::/0'}, {'proxy': '127.0.0.1/invalid'},
            {'proxy': '*'}, {'proxy': '127.0.0.1/33'}, {'enforce': 'typo'},
            {'canonical': ''}, {'canonical': 'http://shop.example.test/'},
            {'env': 'production', 'enforce': '0', 'canonical': 'http://shop.example.test/'},
            {'enforce': '0', 'backend_https': 'on', 'canonical': 'http://localhost/'},
        ]:
            with self.subTest(params=params):
                status, body, headers = self.call(params)
                self.assertEqual(status, 503)
                self.assertIsNone(headers.get('Set-Cookie'))
                self.assertNotIn('handler_ran', body)
        self.assertEqual(self.call({'env': 'production', 'enforce': '0'})[0], 308)

    def test_direct_https_and_local_http_compatibility(self):
        status, body, headers = self.call({'backend_https': 'on'})
        self.assertEqual(status, 200)
        self.assertTrue(json.loads(body)['https'])
        self.assertIn('; secure', headers['Set-Cookie'].lower())
        self.assertIsNone(headers.get('Strict-Transport-Security'), 'Do not persist HSTS on local demo hosts')
        for value in ['off', '0', 'garbage']:
            self.assertEqual(self.call({'backend_https': value})[0], 308)
        status, body, headers = self.call({'enforce': '0', 'canonical': ''},
                                         {'Host': 'localhost', 'X-Forwarded-Proto': 'https'})
        self.assertEqual(status, 200)
        self.assertFalse(json.loads(body)['https'])
        self.assertNotIn('; secure', headers['Set-Cookie'].lower())
        self.assertNotIn('upgrade-insecure-requests', headers['Content-Security-Policy'])


if __name__ == '__main__':
    unittest.main(verbosity=2)
