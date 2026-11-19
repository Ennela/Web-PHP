#!/usr/bin/env python3
"""HTTP hardening tests plus isolated production/error-policy tests.

Run against the hardened local server: python3 scripts/test_security_configuration.py
Isolated checks use FRANKENPHP_BIN, frankenphp in PATH, or scratch/runtime/frankenphp.
Fixtures and their listener are removed after the run; no real database is changed.
"""
import json
import os
from pathlib import Path
import shutil
import socket
import subprocess
import tempfile
import time
import unittest
import urllib.error
import urllib.request
import uuid

from test_csrf import BASE, Browser

ROOT = Path(__file__).resolve().parent.parent
RUNTIME = os.environ.get('FRANKENPHP_BIN') or shutil.which('frankenphp') or str(ROOT / 'scratch/runtime/frankenphp')


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, request, response, code, message, headers, url):
        return None


def request(url, headers=None, method='GET'):
    req = urllib.request.Request(url, headers=headers or {}, method=method)
    try:
        response = urllib.request.build_opener(NoRedirect()).open(req, timeout=10)
    except urllib.error.HTTPError as error:
        response = error
    with response:
        return response.status, response.read().decode('utf-8', errors='replace'), response.headers


class PublicServerTests(unittest.TestCase):
    def test_existing_upload_scripts_are_never_executed(self):
        directory = ROOT / 'auth/uploads/avatars'
        directory.mkdir(parents=True, exist_ok=True)
        created = []
        try:
            for extension in ['php', 'PHP', 'phtml.png', 'php.jpg', 'svg']:
                file = directory / ('security_check_' + uuid.uuid4().hex + '.' + extension)
                file.write_text('<?php echo "UPLOAD_EXECUTION_SENTINEL"; ?>')
                created.append(file)
                status, body, _ = request(BASE + 'auth/uploads/avatars/' + file.name)
                self.assertIn(status, [403, 404])
                self.assertNotIn('UPLOAD_EXECUTION_SENTINEL', body)
                if extension == 'php':
                    status, body, _ = request(BASE + 'auth/uploads/avatars/' + file.name + '/image.jpg')
                    self.assertIn(status, [403, 404])
                    self.assertNotIn('UPLOAD_EXECUTION_SENTINEL', body)
        finally:
            for file in created:
                file.unlink(missing_ok=True)

    def test_private_files_directories_backups_and_encoded_paths(self):
        paths = [
            '.env', '.env.example', '.git/config', '.git/HEAD', '.htaccess',
            '%2eenv.example', '%2egit/config', 'database/giaythethao2.sql',
            'database/railway_migration_v2.sql', 'documents/CSRF_SECURITY.md',
            'scripts/test_csrf.py', 'scratch/backups/giaythethao2-before-migration.sql',
            'scratch/runtime/frankenphp', 'includes/connect.php', 'includes/security.php',
            'config.php', 'config.php/extra', 'admin/connect_db.php', 'admin/function.php',
            'vnpay_php/config.php', 'deploy/php-security.ini', 'Dockerfile', 'Caddyfile',
            'lib/htmlpurifier/library/HTMLPurifier.php', 'lib/htmlpurifier/UPSTREAM.json',
            'docker-entrypoint.sh', 'composer.json', 'composer.lock',
            'backup.sql.gz', 'backup.sql.zip', 'settings.ini.old', 'application.log.1',
            'assets/', 'images/', 'auth/uploads/', 'admin/uploads/shell.php',
            'admin/uploads/shell.php.jpg', 'auth/uploads/avatars/shell.phtml',
        ]
        for path in paths:
            with self.subTest(path=path):
                status, body, _ = request(BASE + path)
                self.assertIn(status, [403, 404])
                self.assertNotIn('Index of', body)
                self.assertNotIn('CREATE TABLE', body)

    def test_headers_assets_and_no_version_disclosure(self):
        for path in ['', 'auth/dangki.php', 'assets/css/account.css', 'assets/js/account.js']:
            with self.subTest(path=path):
                status, _, headers = request(BASE + path)
                self.assertEqual(status, 200)
                self.assertEqual(headers['X-Content-Type-Options'], 'nosniff')
                self.assertEqual(headers['X-Frame-Options'], 'SAMEORIGIN')
                self.assertEqual(headers['Referrer-Policy'], 'strict-origin-when-cross-origin')
                self.assertIsNotNone(headers['Permissions-Policy'])
                self.assertIsNone(headers['X-Powered-By'])
                self.assertIsNone(headers['Server'])
        _, _, headers = request(BASE)
        policy = headers['Content-Security-Policy']
        for directive in ["object-src 'none'", "base-uri 'self'", "frame-ancestors 'self'"]:
            self.assertIn(directive, policy)
        self.assertIn('no-store', headers['Cache-Control'])

    def test_untrusted_proxy_headers_do_not_change_origin_or_cookie(self):
        status, body, headers = request(BASE + 'auth/dangki.php', {
            'X-Forwarded-Proto': 'https', 'X-Forwarded-Host': 'attacker.invalid',
        })
        self.assertEqual(status, 200)
        self.assertNotIn('attacker.invalid', body)
        self.assertNotIn('; secure', headers['Set-Cookie'].lower())
        self.assertIn('httponly', headers['Set-Cookie'].lower())

    def test_unconfigured_payment_and_webhook_are_closed(self):
        status, body, _ = request(BASE + 'shop/dathangonline.php')
        # A configured merchant may redirect an empty cart to the shop.
        self.assertIn(status, [302, 503])
        self.assertNotIn('Fatal error', body)
        browser = Browser()
        token = browser.token()
        status, body, _ = browser.request('webhooks/mailjet_webhook.php', {
            'csrf_token': token,
        })
        self.assertIn(status, [401, 503])


@unittest.skipUnless(Path(RUNTIME).is_file(), 'FrankenPHP is required for isolated deployment-policy tests')
class IsolatedConfigurationTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.directory = tempfile.TemporaryDirectory(prefix='webphp-security-')
        folder = Path(cls.directory.name)
        php = r'''<?php
putenv('APP_ENV=test'); putenv('APP_DEBUG=0'); putenv('APP_URL=');
putenv('APP_TRUSTED_HOSTS='); putenv('TRUSTED_PROXIES=');
$case = $_GET['case'] ?? '';
if (in_array($case, ['production_missing', 'production_http', 'production_https', 'production_proxy', 'production_db_root'], true)) {
    putenv('APP_ENV=production'); putenv('APP_DEBUG=1');
    if ($case !== 'production_missing') putenv('APP_URL=' . ($case === 'production_http' ? 'http' : 'https') . '://shop.example.test/');
    if (in_array($case, ['production_proxy', 'production_db_root'], true)) putenv('TRUSTED_PROXIES=127.0.0.1/32');
}
require __ROOT__ . '/config.php';
if ($case === 'exception') {
    echo 'PARTIAL_PRIVATE_OUTPUT';
    throw new RuntimeException('PRIVATE_SQL_PASSWORD_SENTINEL');
}
if ($case === 'db_failure' || $case === 'production_db_root') {
    putenv('MYSQLHOST=127.0.0.1'); putenv('MYSQLPORT=1');
    putenv('MYSQLUSER=root'); putenv('MYSQLPASSWORD=PRIVATE_DB_PASSWORD_SENTINEL'); putenv('MYSQLDATABASE=private_db');
    require __ROOT__ . '/includes/database.php';
    app_database_connection();
}
if (in_array($case, ['payment_valid', 'payment_invalid_url', 'payment_missing'], true)) {
    putenv('VNPAY_TMN_CODE=TEST0001');
    putenv('VNPAY_HASH_SECRET=' . ($case === 'payment_missing' ? '' : 'isolated-payment-test-secret'));
    putenv('VNPAY_URL=' . ($case === 'payment_invalid_url'
        ? 'https://sandbox.vnpayment.vn.attacker.invalid/paymentv2/vpcpay.html'
        : 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'));
    require __ROOT__ . '/vnpay_php/config.php';
    vnpay_require_configuration();
}
header('Content-Type: application/json; charset=utf-8');
if ($case === 'lint') {
    $root = __ROOT__; $results = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        $path = $file->getPathname();
        if ($file->getExtension() !== 'php' || preg_match('~/(scratch|vendor|node_modules|\.git)/~', $path)) continue;
        try { token_get_all(file_get_contents($path), TOKEN_PARSE); $results[substr($path, strlen($root) + 1)] = true; }
        catch (ParseError $error) { $results[substr($path, strlen($root) + 1)] = false; }
    }
    echo json_encode($results); exit;
}
if ($case === 'cidr') {
    echo json_encode([
        app_ip_in_range('127.0.0.1', '127.0.0.0/8'),
        !app_ip_in_range('128.0.0.1', '127.0.0.0/8'),
        app_ip_in_range('::1', '::1/128'),
        !app_ip_in_range('::2', '::1/128'),
        !app_ip_in_range('127.0.0.1', '0.0.0.0/invalid'),
        !app_ip_in_range('127.0.0.1', '0.0.0.0/33'),
    ]); exit;
}
echo json_encode(['https' => app_is_https(), 'base' => BASE_URL, 'display_errors' => ini_get('display_errors')]);
'''.replace('__ROOT__', "'" + str(ROOT).replace("'", "\\'") + "'")
        (folder / 'index.php').write_text(php)
        with socket.socket() as connection:
            connection.bind(('127.0.0.1', 0))
            port = connection.getsockname()[1]
        cls.base = f'http://127.0.0.1:{port}/'
        env = dict(os.environ, SITE_ADDRESS=f'http://:{port}', SITE_BIND='127.0.0.1', APP_ROOT=str(folder))
        cls.log = (folder / 'runtime.log').open('w')
        cls.process = subprocess.Popen([RUNTIME, 'run', '--config', str(ROOT / 'Caddyfile')], env=env, stdout=cls.log, stderr=cls.log)
        for _ in range(100):
            try:
                with socket.create_connection(('127.0.0.1', port), timeout=0.1):
                    return
            except OSError:
                if cls.process.poll() is not None:
                    raise RuntimeError('Isolated server failed to start')
                time.sleep(0.05)
        raise RuntimeError('Isolated server did not become ready')

    @classmethod
    def tearDownClass(cls):
        cls.process.terminate()
        cls.process.wait(timeout=10)
        cls.log.close()
        cls.directory.cleanup()

    def test_host_allowlist_and_proxy_boundaries(self):
        status, body, _ = request(self.base, {'Host': 'attacker.invalid'})
        self.assertEqual(status, 400)
        self.assertNotIn('attacker.invalid', body)
        status, body, headers = request(self.base, {'X-Forwarded-Proto': 'https'})
        self.assertEqual(status, 200)
        self.assertFalse(json.loads(body)['https'])
        self.assertNotIn('; secure', headers['Set-Cookie'].lower())
        status, body, _ = request(self.base + '?case=cidr')
        self.assertEqual(status, 200)
        self.assertTrue(all(json.loads(body)))

    def test_production_configuration_https_and_hsts(self):
        for case in ['production_missing', 'production_http']:
            status, body, _ = request(self.base + '?case=' + case)
            self.assertEqual(status, 503)
            self.assertNotIn('APP_URL', body)
        headers = {'Host': 'shop.example.test', 'X-Forwarded-Proto': 'https'}
        status, _, response_headers = request(self.base + '?case=production_https', headers)
        self.assertEqual(status, 308, 'Untrusted proxy header must not bypass HTTPS enforcement')
        self.assertTrue(response_headers['Location'].startswith('https://shop.example.test/'))
        status, body, response_headers = request(self.base + '?case=production_proxy', headers)
        self.assertEqual(status, 200)
        self.assertEqual(json.loads(body)['display_errors'], '0')
        self.assertTrue(json.loads(body)['https'])
        self.assertIn('; secure', response_headers['Set-Cookie'].lower())
        self.assertEqual(response_headers['Strict-Transport-Security'], 'max-age=31536000')

    def test_errors_and_database_details_are_hidden(self):
        for case, expected, headers in [
            ('exception', 500, {}),
            ('exception', 500, {'Accept': 'application/json'}),
            ('db_failure', 503, {}),
            ('production_db_root', 503, {'Host': 'shop.example.test', 'X-Forwarded-Proto': 'https'}),
        ]:
            status, body, response_headers = request(self.base + '?case=' + case, headers)
            self.assertEqual(status, expected)
            for secret in ['PRIVATE_', str(ROOT), 'Stack trace', 'mysqli_', 'private_db']:
                self.assertNotIn(secret, body)
            if headers.get('Accept') == 'application/json':
                self.assertFalse(json.loads(body)['success'])
                self.assertIn('application/json', response_headers['Content-Type'])

    def test_php_source_parses(self):
        status, body, _ = request(self.base + '?case=lint')
        self.assertEqual(status, 200)
        results = json.loads(body)
        self.assertGreaterEqual(len(results), 72)
        self.assertEqual([path for path, valid in results.items() if not valid], [])
        print(f'\nPHP files parsed: {len(results)}')

    def test_payment_configuration_has_no_insecure_fallback(self):
        for case, expected in [('payment_valid', 200), ('payment_invalid_url', 503), ('payment_missing', 503)]:
            status, body, _ = request(self.base + '?case=' + case)
            self.assertEqual(status, expected)
            self.assertNotIn('isolated-payment-test-secret', body)


if __name__ == '__main__':
    unittest.main(verbosity=2)
