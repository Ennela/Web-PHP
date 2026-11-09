<?php
/** Shared connection policy for the storefront and administration pages. */
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
        $connection = mysqli_init();
        mysqli_options($connection, MYSQLI_OPT_CONNECT_TIMEOUT, 5);

        $flags = 0;
        $sslCa = trim((string)(getenv('MYSQL_SSL_CA') ?: ''));
        $sslRequired = filter_var(getenv('MYSQL_SSL_REQUIRED') ?: '0', FILTER_VALIDATE_BOOLEAN);
        $sslVerify = filter_var(getenv('MYSQL_SSL_VERIFY') ?: '1', FILTER_VALIDATE_BOOLEAN);

        if ($sslCa !== '' || $sslRequired) {
            if ($sslCa !== '') {
                mysqli_ssl_set($connection, null, null, $sslCa, null, null);
            }
            $flags |= MYSQLI_CLIENT_SSL;
            if (defined('MYSQLI_OPT_SSL_VERIFY_SERVER_CERT')) {
                mysqli_options($connection, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, $sslVerify);
            }
        }

        mysqli_real_connect($connection, $host, $user, $password, $database, (int)$port, null, $flags);
        mysqli_set_charset($connection, 'utf8mb4');
        return $connection;
    } catch (mysqli_sql_exception $error) {
        // Never log the password, connection parameters, or driver error text.
        error_log('Database connection failed; driver code ' . (int)$error->getCode() . '.');
        app_fail(503, 'Dịch vụ dữ liệu tạm thời không khả dụng.');
        exit;
    }
}
