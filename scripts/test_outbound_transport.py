#!/usr/bin/env python3
"""Real local TLS handshakes for outgoing HTTP/MySQL; never contacts a provider.

python3 scripts/test_outbound_transport.py
Requires FrankenPHP, Python ssl and openssl. Certificates/listeners are temporary.
"""
import http.server
import json
import os
from pathlib import Path
import shutil
import socket
import socketserver
import ssl
import struct
import subprocess
import tempfile
import threading
import time
import unittest
import urllib.parse

from test_security_configuration import RUNTIME, request

ROOT = Path(__file__).resolve().parent.parent


def packet(data, seq):
    return len(data).to_bytes(3, 'little') + bytes([seq]) + data


def read_packet(connection):
    def exact(length):
        data = b''
        while len(data) < length:
            chunk = connection.recv(length - len(data))
            if not chunk:
                return b''
            data += chunk
        return data
    header = exact(4)
    if not header:
        return b''
    return exact(int.from_bytes(header[:3], 'little'))


class MysqlHandshake(socketserver.BaseRequestHandler):
    """Only an initial handshake: observe whether auth is sent before certificate validation."""
    def handle(self):
        conn = self.request
        conn.settimeout(3)
        caps = 0x00000200 | 0x00008000 | 0x00080000 | 0x00000008
        if self.server.tls:
            caps |= 0x800
        greet = (b'\x0a8.0.0-transport-fixture\0' + struct.pack('<I', 1) + b'12345678\0'
                 + struct.pack('<H', caps & 0xffff) + b'\x21' + struct.pack('<H', 2)
                 + struct.pack('<H', caps >> 16) + b'\x15' + b'\0' * 10
                 + b'abcdefghijkl\0mysql_native_password\0')
        try:
            conn.sendall(packet(greet, 0))
            first = read_packet(conn)
            self.server.first_payloads.append(first)
            if self.server.tls and len(first) == 32:
                with self.server.context.wrap_socket(conn, server_side=True) as secured:
                    auth = read_packet(secured)
                    if auth:
                        self.server.auth_received += 1
                        # Stop after handshake, no database operations or real credentials.
                        secured.sendall(packet(b'\xff\x15\x04#28000Fixture authentication refused', 3))
        except (OSError, ssl.SSLError):
            pass


class HttpFixture(http.server.BaseHTTPRequestHandler):
    def do_GET(self):
        self.server.paths.append(self.path)
        body = b'TLS_FIXTURE_OK'
        self.send_response(302 if self.path == '/redirect' else 200)
        if self.path == '/redirect':
            self.send_header('Location', 'http://127.0.0.1:1/should-not-be-requested')
        self.send_header('Content-Length', str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def log_message(self, *args):
        pass


@unittest.skipUnless(Path(RUNTIME).is_file() and shutil.which('openssl'), 'FrankenPHP and openssl required')
class OutboundTransportTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.folder = tempfile.TemporaryDirectory(prefix='webphp-outbound-')
        cls.addClassCleanup(cls.folder.cleanup)
        root = Path(cls.folder.name)
        conf = root / 'cert.conf'
        conf.write_text('[req]\ndistinguished_name=dn\nprompt=no\nx509_extensions=v3\n'
                        '[dn]\nCN=localhost\n[v3]\nsubjectAltName=DNS:localhost\n'
                        'basicConstraints=critical,CA:TRUE\nkeyUsage=critical,digitalSignature,keyEncipherment,keyCertSign\n')
        cls.cert = root / 'ca.pem'
        key = root / 'key.pem'
        subprocess.run(['openssl', 'req', '-x509', '-newkey', 'rsa:2048', '-nodes', '-days', '1',
                        '-config', str(conf), '-keyout', str(key), '-out', str(cls.cert)],
                       check=True, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        key.chmod(0o600)
        cls.context = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
        cls.context.minimum_version = ssl.TLSVersion.TLSv1_2
        cls.context.load_cert_chain(cls.cert, key)
        # MySQL certificate also needs IP SAN because mysqli 'localhost' uses Unix sockets.
        db_conf = root / 'dbcert.conf'
        db_conf.write_text(conf.read_text().replace('DNS:localhost', 'DNS:localhost,IP:127.0.0.1'))
        cls.db_cert = root / 'dbca.pem'
        db_key = root / 'dbkey.pem'
        subprocess.run(['openssl', 'req', '-x509', '-newkey', 'rsa:2048', '-nodes', '-days', '1',
                        '-config', str(db_conf), '-keyout', str(db_key), '-out', str(cls.db_cert)],
                       check=True, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        db_key.chmod(0o600)
        cls.db_context = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
        cls.db_context.minimum_version = ssl.TLSVersion.TLSv1_2
        cls.db_context.load_cert_chain(cls.db_cert, db_key)
        cls.http = http.server.ThreadingHTTPServer(('127.0.0.1', 0), HttpFixture)
        cls.http.paths = []
        cls.http.socket = cls.context.wrap_socket(cls.http.socket, server_side=True)
        threading.Thread(target=cls.http.serve_forever, daemon=True).start()
        def close_http():
            cls.http.shutdown()
            cls.http.server_close()
        cls.addClassCleanup(close_http)
        cls.https_port = cls.http.server_address[1]
        php = r'''<?php
putenv('APP_ENV=test'); putenv('APP_URL='); putenv('APP_DEBUG=0');
require __ROOT__ . '/config.php';
require __ROOT__ . '/includes/outbound_transport.php';
require __ROOT__ . '/includes/database.php';
header('Content-Type: application/json');
$op = $_GET['op'] ?? '';
if ($op === 'db' || $op === 'db_policy') {
    foreach (['MYSQL_SSL_CA', 'MYSQL_SSL_CERT', 'MYSQL_SSL_KEY'] as $name) putenv($name . '=');
    if (($_GET['trust'] ?? '') === 'yes') putenv('MYSQL_SSL_CA=' . __CERT__);
    if (($_GET['trust'] ?? '') === 'db') putenv('MYSQL_SSL_CA=' . __DBCERT__);
    if (($_GET['trust'] ?? '') === 'missing') putenv('MYSQL_SSL_CA=/nonexistent/ca.pem');
    if (($_GET['client'] ?? '') === 'unpaired') putenv('MYSQL_SSL_CERT=' . __CERT__);
    $host = $_GET['host'] ?? '127.0.0.1';
    if ($op === 'db_policy') {
        try { $tls = app_database_tls_configuration($host); echo json_encode(['tls' => $tls !== null]); }
        catch (RuntimeException $error) { http_response_code(503); echo '{"error":"TLS_CONFIG"}'; }
        exit;
    }
    putenv('MYSQLHOST=' . $host); putenv('MYSQLPORT=' . $_GET['port']);
    putenv('MYSQLUSER=transport_fixture'); putenv('MYSQLPASSWORD=non-secret-fixture'); putenv('MYSQLDATABASE=test');
    app_database_connection(); exit;
}
$ch = curl_init($_GET['url']);
$options = app_https_curl_options() + [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3];
if (($_GET['trust'] ?? '') === 'yes') $options[CURLOPT_CAINFO] = __CERT__;
curl_setopt_array($ch, $options);
$response = curl_exec($ch);
echo json_encode(['ok' => $response !== false, 'errno' => curl_errno($ch),
    'status' => curl_getinfo($ch, CURLINFO_HTTP_CODE), 'body' => $response]);
'''
        def literal(value):
            return "'" + str(value).replace("'", "\\'") + "'"
        php = php.replace('__ROOT__', literal(ROOT)).replace('__CERT__', literal(cls.cert)).replace('__DBCERT__', literal(cls.db_cert))
        (root / 'index.php').write_text(php)
        with socket.socket() as sock:
            sock.bind(('127.0.0.1', 0))
            port = sock.getsockname()[1]
        cls.base = f'http://127.0.0.1:{port}/'
        cls.log = (root / 'runtime.log').open('w')
        cls.addClassCleanup(cls.log.close)
        env = dict(os.environ, APP_ROOT=str(root), SITE_ADDRESS=f'http://:{port}', SITE_BIND='127.0.0.1')
        cls.process = subprocess.Popen([RUNTIME, 'run', '--config', str(ROOT / 'Caddyfile')], env=env,
                                       stdout=cls.log, stderr=cls.log)
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
        raise RuntimeError('Outgoing transport fixture did not start')

    def call(self, **params):
        status, body, _ = request(self.base + '?' + urllib.parse.urlencode(params), {'Accept': 'application/json'})
        return status, json.loads(body)

    def test_https_valid_certificate_untrusted_ca_and_hostname(self):
        endpoint = f'https://localhost:{self.https_port}/ok'
        status, result = self.call(url=endpoint, trust='yes')
        self.assertEqual(status, 200)
        self.assertTrue(result['ok'], result)
        self.assertEqual(result['status'], 200)
        for url, trust in [(endpoint, 'no'), (endpoint.replace('localhost', '127.0.0.1'), 'yes')]:
            with self.subTest(url=url, trust=trust):
                status, result = self.call(url=url, trust=trust)
                self.assertEqual(status, 200)
                self.assertFalse(result['ok'])
                self.assertEqual(result['errno'], 60)  # CURLE_PEER_FAILED_VERIFICATION

    def test_http_blocked_and_https_redirect_is_not_followed(self):
        status, result = self.call(url=f'http://localhost:{self.https_port}/ok', trust='yes')
        self.assertEqual(status, 200)
        self.assertFalse(result['ok'])
        self.assertEqual(result['errno'], 1)  # CURLE_UNSUPPORTED_PROTOCOL
        status, result = self.call(url=f'https://localhost:{self.https_port}/redirect', trust='yes')
        self.assertEqual(status, 200)
        self.assertTrue(result['ok'], result)
        self.assertEqual(result['status'], 302)
        self.assertNotIn('/should-not-be-requested', self.http.paths)

    def test_database_remote_requires_ca_and_client_pair(self):
        for host in ['127.0.0.1', 'localhost', '::1']:
            self.assertEqual(self.call(op='db_policy', host=host), (200, {'tls': False}))
        for host in ['db.internal', '192.168.1.10', '127.0.0.1.attacker.invalid', 'p:127.0.0.1']:
            self.assertEqual(self.call(op='db_policy', host=host)[0], 503)
            self.assertEqual(self.call(op='db_policy', host=host, trust='yes'), (200, {'tls': True}))
        for kwargs in [dict(trust='missing'), dict(trust='yes', client='unpaired'), dict(client='unpaired')]:
            self.assertEqual(self.call(op='db_policy', host='127.0.0.1', **kwargs)[0], 503)

    def test_mysql_downgrade_and_invalid_certificate_fail_before_authentication(self):
        for tls, context, trust, expected_auth in [
            (False, self.context, 'yes', 0),
            (True, self.context, 'yes', 0),  # Trusted CA, wrong IP hostname.
            (True, self.db_context, 'yes', 0),  # Host matches, CA untrusted.
            (True, self.db_context, 'db', 1),  # Positive control: auth only after verified TLS.
        ]:
            with self.subTest(tls=tls, trust=trust, expected_auth=expected_auth):
                with socketserver.TCPServer(('127.0.0.1', 0), MysqlHandshake) as server:
                    server.tls, server.context = tls, context
                    server.first_payloads, server.auth_received = [], 0
                    thread = threading.Thread(target=server.handle_request, daemon=True)
                    thread.start()
                    status, result = self.call(op='db', port=server.server_address[1], trust=trust)
                    self.assertEqual(status, 503)
                    self.assertFalse(result['success'])
                    thread.join(timeout=5)
                    self.assertFalse(thread.is_alive())
                    self.assertEqual(server.auth_received, expected_auth)
                    if not tls:
                        self.assertEqual(server.first_payloads, [b''])


if __name__ == '__main__':
    unittest.main(verbosity=2)
