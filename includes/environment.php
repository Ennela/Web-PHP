<?php
/**
 * Read local development settings without overriding process configuration.
 * Staging/production must receive their environment from the hosting platform.
 */
function app_load_local_environment(string $path): void
{
    $environment = getenv('APP_ENV');
    if ($environment !== false && !in_array($environment, ['local', 'test'], true)) {
        return;
    }
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    // A file can disappear between the checks and this read. Initialization is
    // earlier than the error handler, so avoid exposing a local path on failure.
    $lines = @file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        // Only application settings are allowed: never PATH, PHP_INI_SCAN_DIR,
        // library-loading settings, or arbitrary process environment variables.
        if (!preg_match('/\A(?:APP_[A-Z0-9_]+|MYSQL(?:HOST|USER|PASSWORD|DATABASE|PORT)|MAIL(?:JET)?_[A-Z0-9_]+|BRAND_[A-Z0-9_]+|VNPAY_[A-Z0-9_]+|TRUSTED_PROXIES)\z/', $name)
            || getenv($name) !== false
            || strpos($value, "\0") !== false) {
            continue;
        }
        if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
            // Quotes are literal delimiters, with no evaluation/interpolation.
            $quote = $value[0];
            if (strlen($value) < 2 || substr($value, -1) !== $quote) {
                continue;
            }
            $value = substr($value, 1, -1);
        } else {
            $value = preg_replace('/\s+#.*\z/', '', $value);
        }
        putenv($name . '=' . $value);
    }
}
