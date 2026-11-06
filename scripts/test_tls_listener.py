#!/usr/bin/env python3
"""Real TLS listener checks using a temporary local CA, never installed into system trust.

python3 scripts/test_tls_listener.py
Requires the project's FrankenPHP runtime. No database or external service is used.
"""
import http.client
import json
import os
from pathlib import Path
import socket
import ssl
import subprocess
import tempfile
import threading
import time
import unittest
import warnings

from test_security_configuration import ROOT, RUNTIME


@unittest.skipUnless(Path(RUNTIME).is_file(), 'FrankenPHP required for real TLS listener checks')
class TlsListenerTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.temp = tempfile.TemporaryDirectory(prefix='webphp-tls-')
        cls.addClassCleanup(cls.temp.cleanup)
        folder = Path(cls.temp.name)
        app = folder / 'app'
        app.mkdir()
        root_literal = "'" + str(ROOT).replace("'", "\\'") + "'"
        (app / 'index.php').write_text("<?php\nrequire " + root_literal + " . '/config.php';\n"
                                      "header('Content-Type: application/json');\n"
                                      "echo json_encode(['https'=>app_is_https(), 'base'=>BASE_URL]);\n")
        (app / '.env').write_text('PRIVATE_TLS_TEST_SENTINEL=do-not-serve\n')
        (app / 'asset.css').write_text('body { color: black; }')
        reserved = []
        for _ in range(2):
            connection = socket.socket()
            connection.bind(('127.0.0.1', 0))
            reserved.append(connection)
        cls.https_port, cls.http_port = [item.getsockname()[1] for item in reserved]
        for connection in reserved:
            connection.close()
        env = dict(os.environ, APP_ENV='local', APP_DEBUG='0', APP_REQUIRE_HTTPS='1',
                   APP_URL=f'https://localhost:{cls.https_port}', APP_TRUSTED_HOSTS='localhost',
                   TRUSTED_PROXIES='', APP_ROOT=str(app), LOCAL_HTTPS_PORT=str(cls.https_port),
                   LOCAL_HTTP_PORT=str(cls.http_port), XDG_DATA_HOME=str(folder / 'data'),
                   XDG_CONFIG_HOME=str(folder / 'config'))
        cls.ca = folder / 'data/caddy/pki/authorities/local/root.crt'
        cls.log = (folder / 'runtime.log').open('w')
        cls.addClassCleanup(cls.log.close)
        cls.process = subprocess.Popen([RUNTIME, 'run', '--config', str(ROOT / 'deploy/Caddyfile.local-https')],
                                       env=env, stdout=cls.log, stderr=cls.log)
        def stop():
            cls.process.terminate()
            cls.process.wait(timeout=10)
        cls.addClassCleanup(stop)
        for _ in range(160):
            if cls.process.poll() is not None:
                raise RuntimeError('TLS fixture failed: ' + (folder / 'runtime.log').read_text())
            try:
                with socket.create_connection(('127.0.0.1', cls.https_port), timeout=0.1):
                    if cls.ca.is_file():
                        break
            except OSError:
                pass
            time.sleep(0.05)
        else:
            raise RuntimeError('TLS fixture did not start')
        cls.context = ssl.create_default_context(cafile=str(cls.ca))

    def connect_tls(self, context=None, hostname='localhost'):
        raw = socket.create_connection(('127.0.0.1', self.https_port), timeout=5)
        try:
            return (context or self.context).wrap_socket(raw, server_hostname=hostname)
        except Exception:
            raw.close()
            raise

    def https_request(self, path='/'):
        # Connect to loopback with localhost SNI/hostname verification, independent of IPv6 DNS.
        with self.connect_tls() as connection:
            connection.sendall((f'GET {path} HTTP/1.1\r\nHost: localhost:{self.https_port}\r\nConnection: close\r\n\r\n').encode())
            response = http.client.HTTPResponse(connection)
            response.begin()
            return response.status, response.read().decode(), response.headers

    def test_tls12_and_tls13_and_verified_certificate(self):
        for version, label in [(ssl.TLSVersion.TLSv1_2, 'TLSv1.2'), (ssl.TLSVersion.TLSv1_3, 'TLSv1.3')]:
            with self.subTest(version=label):
                context = ssl.create_default_context(cafile=str(self.ca))
                context.minimum_version = version
                context.maximum_version = version
                with self.connect_tls(context) as connection:
                    self.assertEqual(connection.version(), label)
                    self.assertIn(('DNS', 'localhost'), connection.getpeercert()['subjectAltName'])

    def test_obsolete_tls_versions_are_rejected_by_server(self):
        for version in [ssl.TLSVersion.TLSv1, ssl.TLSVersion.TLSv1_1]:
            with self.subTest(version=version), warnings.catch_warnings():
                warnings.simplefilter('ignore', DeprecationWarning)
                context = ssl.create_default_context(cafile=str(self.ca))
                context.minimum_version = version
                context.maximum_version = version
                context.set_ciphers('ALL:@SECLEVEL=0')
                with self.assertRaises(ssl.SSLError) as failure:
                    self.connect_tls(context)
                self.assertEqual(failure.exception.reason, 'TLSV1_ALERT_PROTOCOL_VERSION')

    def test_untrusted_ca_and_wrong_hostname_are_rejected(self):
        with self.assertRaises(ssl.SSLCertVerificationError):
            self.connect_tls(ssl.create_default_context())
        # Serve Caddy's actual certificate over a socketpair, without SNI routing,
        # so the client must reject its name rather than Caddy rejecting unknown SNI.
        certificate = next(Path(self.temp.name, 'data/caddy/certificates').rglob('localhost.crt'))
        server_context = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
        server_context.load_cert_chain(certificate, certificate.with_suffix('.key'))
        server_socket, client_socket = socket.socketpair()
        server_socket.settimeout(5)
        client_socket.settimeout(5)
        def serve():
            try:
                with server_context.wrap_socket(server_socket, server_side=True):
                    pass
            except ssl.SSLError:
                pass  # Expected alert when the client rejects the hostname.
            finally:
                server_socket.close()
        thread = threading.Thread(target=serve, daemon=True)
        thread.start()
        try:
            with self.assertRaises(ssl.SSLCertVerificationError) as failure:
                self.context.wrap_socket(client_socket, server_hostname='wrong-host.example.test')
            self.assertIn('Hostname mismatch', failure.exception.verify_message)
        finally:
            client_socket.close()
            thread.join(timeout=6)
        self.assertFalse(thread.is_alive())

    def test_https_php_cookie_and_file_protection(self):
        status, body, headers = self.https_request()
        self.assertEqual(status, 200)
        self.assertTrue(json.loads(body)['https'])
        self.assertEqual(json.loads(body)['base'], f'https://localhost:{self.https_port}/')
        cookie = headers['Set-Cookie'].lower()
        self.assertIn('; secure', cookie)
        self.assertIn('; httponly', cookie)
        self.assertIn('samesite=lax', cookie)
        self.assertEqual(self.https_request('/asset.css')[0], 200)
        status, body, _ = self.https_request('/.env')
        self.assertEqual(status, 404)
        self.assertNotIn('PRIVATE_TLS_TEST_SENTINEL', body)

    def test_http_redirects_reads_and_refuses_writes_without_php(self):
        for method in ['GET', 'HEAD', 'POST', 'PUT', 'DELETE']:
            with self.subTest(method=method):
                connection = http.client.HTTPConnection('127.0.0.1', self.http_port, timeout=5)
                try:
                    connection.request(method, '/?next=shop', body=None if method in ['GET', 'HEAD'] else 'secret=fixture',
                                       headers={'Host': f'localhost:{self.http_port}'})
                    response = connection.getresponse()
                    body = response.read().decode()
                    if method in ['GET', 'HEAD']:
                        self.assertEqual(response.status, 308)
                        self.assertEqual(response.getheader('Location'), f'https://localhost:{self.https_port}/?next=shop')
                    else:
                        self.assertEqual(response.status, 400)
                        self.assertIsNone(response.getheader('Location'))
                    self.assertIsNone(response.getheader('Set-Cookie'))
                    self.assertNotIn('secret=fixture', body)
                finally:
                    connection.close()


if __name__ == '__main__':
    unittest.main(verbosity=2)
