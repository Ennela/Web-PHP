<?php
/** Shared connection policy for the storefront and administration pages. */
function app_database_tls_configuration(string $host): ?array
{
    // Do not resolve hostnames here: a remote/private-network address still needs TLS.
    $loopback = in_array(strtolower($host), ['localhost', '127.0.0.1', '::1'], true);
    $files = [];
    foreach (['ca' => 'MYSQL_SSL_CA', 'cert' => 'MYSQL_SSL_CERT', 'key' => 'MYSQL_SSL_KEY'] as $key => $name) {
        $value = getenv($name);
        $files[$key] = ($value === false || $value === '') ? null : $value;
    }
    if ($files['ca'] === null) {
        if (!$loopback || $files['cert'] !== null || $files['key'] !== null) {
            throw new RuntimeException('Database TLS CA is required');
        }
        return null; // Same-machine development remains usable without a database CA.
    }
    if (($files['cert'] === null) !== ($files['key'] === null)) {
        throw new RuntimeException('Database client certificate and key must be paired');
    }
    foreach ($files as $key => $path) {
        if ($path !== null) {
            if (str_contains($path, "\0") || !is_file($path) || !is_readable($path)) {
                throw new RuntimeException('Database TLS file is unavailable');
            }
            $files[$key] = realpath($path);
        }
    }
    return $files;
}

function app_database_connection(): mysqli
{
    $required = ['MYSQLHOST', 'MYSQLUSER', 'MYSQLPASSWORD', 'MYSQLDATABASE'];
    if (app_is_production()) {
        foreach ($required as $name) {
            if (getenv($name) === false || getenv($name) === '') {
                error_log('Database configuration is incomplete.');
                app_fail(503, 'Dịch vụ dữ liệu tạm thời không khả dụng.');
            }
        }
        if (strtolower(trim((string)getenv('MYSQLUSER'))) === 'root') {
            error_log('Production database account must not be root.');
            app_fail(503, 'Dịch vụ dữ liệu tạm thời không khả dụng.');
        }
    }

    $host = getenv('MYSQLHOST');
    $host = $host === false || $host === '' ? '127.0.0.1' : $host;
    $user = getenv('MYSQLUSER');
    $user = $user === false || $user === '' ? 'root' : $user;
    $password = getenv('MYSQLPASSWORD');
    $password = $password === false ? '' : $password;
    $database = getenv('MYSQLDATABASE');
    $database = $database === false || $database === '' ? 'giaythethao2' : $database;
    $port = getenv('MYSQLPORT') ?: '3306';
    if (!ctype_digit((string)$port) || (int)$port < 1 || (int)$port > 65535) {
        error_log('Database port configuration is invalid.');
        app_fail(503, 'Dịch vụ dữ liệu tạm thời không khả dụng.');
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $tls = app_database_tls_configuration($host);
        $connection = mysqli_init();
        mysqli_options($connection, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
        $flags = 0;
        if ($tls !== null) {
            if (!extension_loaded('openssl')) {
                throw new RuntimeException('Database TLS support is unavailable');
            }
            // GCM suites require TLS 1.2; TLS 1.3 uses its own secure cipher suites.
            $ciphers = 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:'
                . 'ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384';
            if (!mysqli_options($connection, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true)) {
                throw new RuntimeException('Database TLS verification is unavailable');
            }
            mysqli_ssl_set($connection, $tls['key'], $tls['cert'], $tls['ca'], null, $ciphers);
            $flags = MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_VERIFY_SERVER_CERT;
        }
        mysqli_real_connect($connection, $host, $user, $password, $database, (int)$port, null, $flags);
        if ($tls !== null) {
            $result = mysqli_query($connection, "SHOW SESSION STATUS WHERE Variable_name IN ('Ssl_cipher', 'Ssl_version')");
            $negotiated = array_column(mysqli_fetch_all($result, MYSQLI_ASSOC), 'Value', 'Variable_name');
            mysqli_free_result($result);
            if (empty($negotiated['Ssl_cipher']) || !in_array($negotiated['Ssl_version'] ?? '', ['TLSv1.2', 'TLSv1.3'], true)) {
                mysqli_close($connection);
                throw new RuntimeException('Database transport is not secure');
            }
        }
        mysqli_set_charset($connection, 'utf8mb4');
        return $connection;
    } catch (mysqli_sql_exception $error) {
        // Never log the password, connection parameters, or driver error text.
        error_log('Database connection failed; driver code ' . (int)$error->getCode() . '.');
        app_fail(503, 'Dịch vụ dữ liệu tạm thời không khả dụng.');
        exit;
    } catch (RuntimeException $error) {
        // Never print CA/key paths, credentials or raw TLS errors to the response/log.
        error_log('Database TLS configuration or negotiation failed.');
        app_fail(503, 'Dịch vụ dữ liệu tạm thời không khả dụng.');
        exit;
    }
}
