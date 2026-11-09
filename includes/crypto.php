<?php
/**
 * Centralized Cryptographic Storage, Password Hashing, Key Management & Data Protection.
 *
 * Security controls implemented:
 * 1. Password Hashing:
 *    - Uses PHP native password_hash(), password_verify(), password_needs_rehash().
 *    - Prefers PASSWORD_ARGON2ID when available, with safe fallback to PASSWORD_DEFAULT (Bcrypt).
 *    - Supports controlled legacy verification (plaintext / MD5 / SHA1) for seamless on-login
 *      and batch migration without locking out existing accounts.
 * 2. Authenticated Encryption (AEAD):
 *    - Supports Libsodium (sodium_crypto_aead_xchacha20poly1305_ietf_*) and OpenSSL (aes-256-gcm).
 *    - Uses CSPRNG random_bytes() for every IV/nonce (24 bytes for XChaCha20-Poly1305, 12 bytes for AES-256-GCM).
 *    - Enforces 16-byte authentication tag and binds Additional Authenticated Data (AAD) including
 *      envelope header (version, algorithm, key_id) and caller context (table.column).
 *    - Self-describing versioned envelope format:
 *      enc:v1:<alg>:<key_id>:<iv_b64>:<tag_b64>:<ct_b64>
 * 3. Key Management & Key Rotation:
 *    - Loads 256-bit (32-byte) master keys from environment variables (APP_ENCRYPTION_KEY,
 *      APP_ENCRYPTION_KEY_ID, APP_ENCRYPTION_KEYS) or external key file outside public web access.
 *    - Enforces key separation via HKDF-SHA256 (separate subkeys for AEAD encryption vs HMAC/blind index).
 *    - Supports multi-key keyring and key rotation (app_rotate_ciphertext()).
 */

class CryptoException extends RuntimeException
{
}

if (!defined('APP_CRYPTO_ENVELOPE_PREFIX')) {
    define('APP_CRYPTO_ENVELOPE_PREFIX', 'enc:v1:');
}

/**
 * Determine the preferred password hashing algorithm for the current PHP runtime.
 *
 * @return string|int
 */
function app_password_algo()
{
    if (defined('PASSWORD_ARGON2ID')) {
        return PASSWORD_ARGON2ID;
    }
    return PASSWORD_DEFAULT;
}

/**
 * Return safe cost options for password_hash() and password_needs_rehash().
 *
 * @return array<string, int>
 */
function app_password_options(): array
{
    $algo = app_password_algo();
    if (defined('PASSWORD_ARGON2ID') && $algo === PASSWORD_ARGON2ID) {
        return [
            'memory_cost' => 65536, // 64 MB
            'time_cost'   => 4,
            'threads'     => 1,
        ];
    }

    return [
        'cost' => 12,
    ];
}

/**
 * Check whether a stored password string is already a modern PHP password_hash() digest.
 */
function app_is_modern_password_hash(?string $storedValue): bool
{
    if (!is_string($storedValue) || $storedValue === '') {
        return false;
    }

    $info = password_get_info($storedValue);
    return !empty($info['algo']);
}

/**
 * Classify the storage format of a password column value.
 *
 * Returns one of:
 *   - 'empty'
 *   - 'argon2id'
 *   - 'modern_other'      (e.g., Bcrypt $2y$ or Argon2i $argon2i$)
 *   - 'prehashed_md5'     (prehashed:md5:$argon2id$...)
 *   - 'prehashed_sha1'    (prehashed:sha1:$argon2id$...)
 *   - 'prehashed_sha256'  (prehashed:sha256:$argon2id$...)
 *   - 'legacy_md5'        (32-character hex digest)
 *   - 'legacy_sha1'       (40-character hex digest)
 *   - 'legacy_sha256'     (64-character hex digest)
 *   - 'plaintext'         (unhashed cleartext password)
 */
function app_classify_password_storage(?string $storedValue): string
{
    if (!is_string($storedValue) || $storedValue === '') {
        return 'empty';
    }

    if (preg_match('/\Aprehashed:(md5|sha1|sha256):(.+)\z/s', $storedValue, $m) === 1) {
        if (app_is_modern_password_hash($m[2])) {
            return 'prehashed_' . $m[1];
        }
    }

    if (app_is_modern_password_hash($storedValue)) {
        $info = password_get_info($storedValue);
        $algoName = strtolower((string) ($info['algoName'] ?? ''));
        if ($algoName === 'argon2id' || str_starts_with($storedValue, '$argon2id$')) {
            return 'argon2id';
        }
        return 'modern_other';
    }

    if (preg_match('/\A[a-f0-9]{32}\z/i', $storedValue) === 1) {
        return 'legacy_md5';
    }

    if (preg_match('/\A[a-f0-9]{40}\z/i', $storedValue) === 1) {
        return 'legacy_sha1';
    }

    if (preg_match('/\A[a-f0-9]{64}\z/i', $storedValue) === 1) {
        return 'legacy_sha256';
    }

    return 'plaintext';
}

/**
 * Hash a user password using Argon2id (or PASSWORD_DEFAULT fallback).
 *
 * @throws InvalidArgumentException if password is empty
 */
function app_hash_password(string $plainPassword): string
{
    if ($plainPassword === '') {
        throw new InvalidArgumentException('Password must not be empty.');
    }

    $hash = password_hash($plainPassword, app_password_algo(), app_password_options());
    if (!is_string($hash) || $hash === '') {
        throw new CryptoException('Failed to compute secure password hash.');
    }

    return $hash;
}

/**
 * Safely upgrade a stored password value during offline batch migration without locking out
 * accounts that currently store legacy one-way digests (MD5 / SHA-1 / SHA-256).
 *
 * - Plaintext passwords are hashed directly with Argon2id (status = 'upgraded_plaintext').
 * - Legacy unsalted hex digests (MD5/SHA-1/SHA-256) are wrapped in a tagged Argon2id
 *   pre-hashed envelope ("prehashed:<algo>:<argon2id_of_lowercase_hex>", status = 'wrapped_legacy_hash')
 *   so they are protected by salted Argon2id at rest immediately and transparently upgraded to a
 *   direct Argon2id hash of the real password upon the user's next login.
 * - Existing Argon2id / modern / prehashed values are preserved untouched.
 *
 * @return array{value: string, status: string, format: string}
 */
function app_upgrade_stored_password_for_migration(string $storedValue): array
{
    $format = app_classify_password_storage($storedValue);

    switch ($format) {
        case 'empty':
            return ['value' => $storedValue, 'status' => 'empty', 'format' => $format];

        case 'argon2id':
            return ['value' => $storedValue, 'status' => 'already_argon2id', 'format' => $format];

        case 'modern_other':
            return ['value' => $storedValue, 'status' => 'deferred_on_login', 'format' => $format];

        case 'prehashed_md5':
        case 'prehashed_sha1':
        case 'prehashed_sha256':
            return ['value' => $storedValue, 'status' => 'already_wrapped', 'format' => $format];

        case 'legacy_md5':
            return [
                'value'  => 'prehashed:md5:' . app_hash_password(strtolower($storedValue)),
                'status' => 'wrapped_legacy_hash',
                'format' => $format,
            ];

        case 'legacy_sha1':
            return [
                'value'  => 'prehashed:sha1:' . app_hash_password(strtolower($storedValue)),
                'status' => 'wrapped_legacy_hash',
                'format' => $format,
            ];

        case 'legacy_sha256':
            return [
                'value'  => 'prehashed:sha256:' . app_hash_password(strtolower($storedValue)),
                'status' => 'wrapped_legacy_hash',
                'format' => $format,
            ];

        case 'plaintext':
        default:
            return [
                'value'  => app_hash_password($storedValue),
                'status' => 'upgraded_plaintext',
                'format' => 'plaintext',
            ];
    }
}

/**
 * Verify a password against a stored hash (or controlled legacy format) and determine
 * whether the stored hash must be upgraded/rehashed.
 *
 * @param string      $plainPassword Plaintext password provided by user
 * @param string|null $storedValue   Stored password hash or legacy value from DB
 * @param bool        &$needsRehash  Populated with true when DB record should be updated with app_hash_password()
 */
function app_verify_password(string $plainPassword, ?string $storedValue, bool &$needsRehash = false): bool
{
    $needsRehash = false;

    if ($plainPassword === '' || !is_string($storedValue) || $storedValue === '') {
        return false;
    }

    // 1. Offline-migrated prehashed legacy digest ("prehashed:<md5|sha1|sha256>:<argon2id_hash>")
    if (preg_match('/\Aprehashed:(md5|sha1|sha256):(.+)\z/s', $storedValue, $m) === 1) {
        $legacyAlgo = $m[1];
        $innerHash = $m[2];
        if (!app_is_modern_password_hash($innerHash)) {
            return false;
        }

        if ($legacyAlgo === 'md5') {
            $preDigest = md5($plainPassword);
        } elseif ($legacyAlgo === 'sha1') {
            $preDigest = sha1($plainPassword);
        } else {
            $preDigest = hash('sha256', $plainPassword);
        }

        if (password_verify($preDigest, $innerHash)) {
            // Always upgrade to direct Argon2id hash of the user's plaintext password on login
            $needsRehash = true;
            return true;
        }
        return false;
    }

    // 2. Standard modern password_hash() digest (Argon2id / Bcrypt)
    if (app_is_modern_password_hash($storedValue)) {
        if (!password_verify($plainPassword, $storedValue)) {
            return false;
        }

        $needsRehash = password_needs_rehash($storedValue, app_password_algo(), app_password_options());
        return true;
    }

    // 3. Controlled legacy verification for pre-migration records:
    // 3a. Legacy MD5 (32 hex chars)
    if (preg_match('/\A[a-f0-9]{32}\z/i', $storedValue) === 1) {
        if (hash_equals(strtolower($storedValue), md5($plainPassword))) {
            $needsRehash = true;
            return true;
        }
        return false;
    }

    // 3b. Legacy SHA-1 (40 hex chars)
    if (preg_match('/\A[a-f0-9]{40}\z/i', $storedValue) === 1) {
        if (hash_equals(strtolower($storedValue), sha1($plainPassword))) {
            $needsRehash = true;
            return true;
        }
        return false;
    }

    // 3c. Legacy SHA-256 (64 hex chars)
    if (preg_match('/\A[a-f0-9]{64}\z/i', $storedValue) === 1) {
        if (hash_equals(strtolower($storedValue), hash('sha256', $plainPassword))) {
            $needsRehash = true;
            return true;
        }
        return false;
    }

    // 3d. Legacy plaintext password
    if (hash_equals($storedValue, $plainPassword)) {
        $needsRehash = true;
        return true;
    }

    return false;
}

/**
 * Upgrade a user's stored password hash in the database using a prepared statement.
 */
function app_upgrade_password_hash(
    mysqli $con,
    string $table,
    string $pkColumn,
    int $pkValue,
    string $passwordColumn,
    string $plainPassword
): bool {
    $allowedTargets = [
        'tbl_tkkhachhang' => ['pks' => ['makh'],       'col' => 'password'],
        'tbl_qlthanhvien' => ['pks' => ['id'],         'col' => 'matkhau'],
        'tbl_dangnhap'    => ['pks' => ['mavn', 'id'], 'col' => 'matkhau'],
    ];

    if (
        !isset($allowedTargets[$table])
        || !in_array($pkColumn, $allowedTargets[$table]['pks'], true)
        || $allowedTargets[$table]['col'] !== $passwordColumn
    ) {
        throw new InvalidArgumentException('Invalid table or column target for password upgrade.');
    }

    $newHash = app_hash_password($plainPassword);
    $sql = sprintf('UPDATE `%s` SET `%s` = ? WHERE `%s` = ?', $table, $passwordColumn, $pkColumn);
    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'si', $newHash, $pkValue);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return (bool) $ok;
}

/**
 * Decode and validate a 256-bit (32-byte) cryptographic key from string representation.
 * Accepts:
 *   - "base64:<standard_base64>"
 *   - "hex:<64_hex_chars>" or raw 64-character hex string
 *   - raw 44-character standard base64 string decoding to 32 bytes
 *   - raw 32-byte binary string
 *
 * @throws CryptoException if the key is empty, malformed, low-entropy, or not 32 bytes
 */
function app_parse_raw_key(string $encodedKey): string
{
    $trimmed = trim($encodedKey);
    if ($trimmed === '') {
        throw new CryptoException('Encryption key must not be empty.');
    }

    $raw = null;

    if ( str_starts_with($trimmed, 'base64:') ) {
        $b64 = substr($trimmed, 7);
        $decoded = base64_decode($b64, true);
        if ($decoded === false) {
            throw new CryptoException('Invalid base64 encoding in encryption key.');
        }
        $raw = $decoded;
    } elseif ( str_starts_with($trimmed, 'hex:') ) {
        $hex = substr($trimmed, 4);
        if (preg_match('/\A[a-f0-9]{64}\z/i', $hex) !== 1) {
            throw new CryptoException('Invalid hex encoding in encryption key.');
        }
        $raw = hex2bin($hex);
    } elseif (preg_match('/\A[a-f0-9]{64}\z/i', $trimmed) === 1) {
        $raw = hex2bin($trimmed);
    } else {
        $decoded = base64_decode($trimmed, true);
        if ($decoded !== false && strlen($decoded) === 32) {
            $raw = $decoded;
        } elseif (strlen($trimmed) === 32) {
            $raw = $trimmed;
        }
    }

    if (!is_string($raw) || strlen($raw) !== 32) {
        throw new CryptoException('Encryption key must be exactly 32 bytes (256 bits).');
    }

    // Reject trivial all-same-byte keys (e.g., all zeros or all 'A's)
    if (count( array_unique(str_split($raw)) ) < 4) {
        throw new CryptoException('Encryption key has insufficient entropy.');
    }

    return $raw;
}

/**
 * Generate a new cryptographically secure 256-bit key formatted as "base64:...".
 */
function app_generate_encryption_key(): string
{
    return 'base64:' . base64_encode(random_bytes(32));
}

/**
 * Internal state holder for test/runtime keyring overrides.
 *
 * @param array{active_key_id: string, keys: array<string, string>}|null $override
 * @return array{active_key_id: string, keys: array<string, string>}|null
 */
function &app_keyring_override_Slot(?array $override = null, bool $set = false): ?array
{
    static $slot = null;
    if ($set) {
        $slot = $override;
    }
    return $slot;
}

/**
 * Override the keyring in memory (used for key-rotation operations and unit testing).
 * Pass null to reset back to environment configuration.
 *
 * @param array<string, string>|null $rawOrEncodedKeys Map of key_id => key string
 */
function app_set_keyring_override(?array $rawOrEncodedKeys, ?string $activeKeyId = null): void
{
    if ($rawOrEncodedKeys === null) {
        app_keyring_override_Slot(null, true);
        return;
    }

    if ($rawOrEncodedKeys === []) {
        throw new CryptoException('Keyring override cannot be empty.');
    }

    $parsed = [];
    foreach ($rawOrEncodedKeys as $kid => $keyVal) {
        $kid = trim((string) $kid);
        if (!preg_match('/\A[a-zA-Z0-9_-]{1,32}\z/', $kid)) {
            throw new CryptoException('Invalid key identifier: ' . $kid);
        }
        $parsed[$kid] = app_parse_raw_key((string) $keyVal);
    }

    $active = $activeKeyId !== null ? trim($activeKeyId) : (string) array_key_first($parsed);
    if (!isset($parsed[$active])) {
        throw new CryptoException('Active key ID is not present in the keyring.');
    }

    app_keyring_override_Slot([
        'active_key_id' => $active,
        'keys'          => $parsed,
    ], true);
}

/**
 * Load and validate the application keyring from environment variables or protected secret file.
 *
 * Supported configuration sources:
 * 1. APP_ENCRYPTION_KEYS="k1:base64:...,k2:base64:..." (comma-separated or JSON object)
 * 2. APP_ENCRYPTION_KEY="base64:..." + optional APP_ENCRYPTION_KEY_ID="k1"
 * 3. APP_KEY_FILE="/path/outside/webroot/crypto_keys.json"
 * 4. Local non-production fallback secret file (.secrets/crypto_keys.php, gitignored and blocked by web server)
 *
 * @return array{active_key_id: string, keys: array<string, string>}
 * @throws CryptoException if no valid key is available or configured key is malformed
 */
function app_get_keyring(): array
{
    $override = app_keyring_override_Slot();
    if ($override !== null) {
        return $override;
    }

    if (function_exists('app_load_environment')) {
        app_load_environment();
    }

    $keys = [];
    $activeKeyId = trim((string) (getenv('APP_ENCRYPTION_KEY_ID') ?: ($_ENV['APP_ENCRYPTION_KEY_ID'] ?? 'k1')));
    if ($activeKeyId === '' || !preg_match('/\A[a-zA-Z0-9_-]{1,32}\z/', $activeKeyId)) {
        throw new CryptoException('Invalid APP_ENCRYPTION_KEY_ID format.');
    }

    // Check if APP_ENCRYPTION_KEY was explicitly set in env (even if empty string)
    $rawSingleEnv = getenv('APP_ENCRYPTION_KEY');
    $hasExplicitSingleKey = ($rawSingleEnv !== false) || array_key_exists('APP_ENCRYPTION_KEY', $_ENV);
    $singleKeyVal = $rawSingleEnv !== false ? $rawSingleEnv : ($_ENV['APP_ENCRYPTION_KEY'] ?? null);

    if ($hasExplicitSingleKey && trim((string) $singleKeyVal) === '') {
        throw new CryptoException('APP_ENCRYPTION_KEY is set but empty.');
    }

    // Parse APP_ENCRYPTION_KEYS (multi-key keyring for key rotation)
    $multiKeysEnv = getenv('APP_ENCRYPTION_KEYS');
    if ($multiKeysEnv === false && array_key_exists('APP_ENCRYPTION_KEYS', $_ENV)) {
        $multiKeysEnv = $_ENV['APP_ENCRYPTION_KEYS'];
    }

    if (is_string($multiKeysEnv) && trim($multiKeysEnv) !== '') {
        $trimmedMulti = trim($multiKeysEnv);
        if (str_starts_with($trimmedMulti, '{')) {
            $decodedJson = json_decode($trimmedMulti, true);
            if (!is_array($decodedJson) || $decodedJson === []) {
                throw new CryptoException('Invalid JSON in APP_ENCRYPTION_KEYS.');
            }
            foreach ($decodedJson as $kid => $kval) {
                $kid = trim((string) $kid);
                if (!preg_match('/\A[a-zA-Z0-9_-]{1,32}\z/', $kid)) {
                    throw new CryptoException('Invalid key ID in APP_ENCRYPTION_KEYS.');
                }
                $keys[$kid] = app_parse_raw_key((string) $kval);
            }
        } else {
            $entries = explode(',', $trimmedMulti);
            foreach ($entries as $entry) {
                $entry = trim($entry);
                if ($entry === '') {
                    continue;
                }
                $colonPos = strpos($entry, ':');
                if ($colonPos === false) {
                    throw new CryptoException('Each entry in APP_ENCRYPTION_KEYS must be formatted as <key_id>:<key>.');
                }
                $kid = trim(substr($entry, 0, $colonPos));
                $kval = trim(substr($entry, $colonPos + 1));
                if (!preg_match('/\A[a-zA-Z0-9_-]{1,32}\z/', $kid)) {
                    throw new CryptoException('Invalid key ID in APP_ENCRYPTION_KEYS.');
                }
                $keys[$kid] = app_parse_raw_key($kval);
            }
        }
    }

    if (is_string($singleKeyVal) && trim($singleKeyVal) !== '') {
        $keys[$activeKeyId] = app_parse_raw_key($singleKeyVal);
    }

    // Check external APP_KEY_FILE if keys not loaded from env
    if ($keys === []) {
        $keyFile = trim((string) (getenv('APP_KEY_FILE') ?: ($_ENV['APP_KEY_FILE'] ?? '')));
        if ($keyFile !== '') {
            if (!is_file($keyFile) || !is_readable($keyFile)) {
                throw new CryptoException('Configured APP_KEY_FILE does not exist or is not readable.');
            }
            $fileContent = (string) file_get_contents($keyFile);
            $json = json_decode($fileContent, true);
            if (is_array($json) && !empty($json['keys']) && is_array($json['keys'])) {
                $activeKeyId = trim((string) ($json['active_key_id'] ?? $activeKeyId));
                foreach ($json['keys'] as $kid => $kval) {
                    $keys[(string) $kid] = app_parse_raw_key((string) $kval);
                }
            } else {
                $keys[$activeKeyId] = app_parse_raw_key(trim($fileContent));
            }
        }
    }

    // Non-production local development secret file fallback (never committed to git)
    if ($keys === []) {
        $isProd = function_exists('app_is_production') ? app_is_production() : false;
        if ($isProd) {
            throw new CryptoException('Encryption key is not configured in production environment.');
        }

        $basePath = defined('BASE_PATH') ? BASE_PATH : (dirname(__DIR__) . DIRECTORY_SEPARATOR);
        $secretDir = $basePath . '.secrets';
        $secretFile = $secretDir . DIRECTORY_SEPARATOR . 'crypto_keys.php';

        if (is_file($secretFile)) {
            $loaded = include $secretFile;
            if (is_array($loaded) && !empty($loaded['keys']) && is_array($loaded['keys'])) {
                $activeKeyId = trim((string) ($loaded['active_key_id'] ?? $activeKeyId));
                foreach ($loaded['keys'] as $kid => $kval) {
                    $keys[(string) $kid] = app_parse_raw_key((string) $kval);
                }
            }
        } else {
            if (!is_dir($secretDir)) {
                @mkdir($secretDir, 0700, true);
            }
            @file_put_contents($secretDir . DIRECTORY_SEPARATOR . '.htaccess', "Require all denied\nDeny from all\n");
            $generatedKey = app_generate_encryption_key();
            $phpConfig = "<?php\nreturn [\n    'active_key_id' => 'k1',\n    'keys' => [\n        'k1' => " . var_export($generatedKey, true) . ",\n    ],\n];\n";
            @file_put_contents($secretFile, $phpConfig, LOCK_EX);
            @chmod($secretFile, 0600);
            $activeKeyId = 'k1';
            $keys['k1'] = app_parse_raw_key($generatedKey);
        }
    }

    if (!isset($keys[$activeKeyId])) {
        $activeKeyId = (string) array_key_first($keys);
    }

    return [
        'active_key_id' => $activeKeyId,
        'keys'          => $keys,
    ];
}

/**
 * Derive a purpose-isolated 256-bit subkey from a 256-bit master key using HKDF-SHA256.
 * Ensures strict cryptographic key separation between encryption and HMAC/blind-indexing.
 */
function app_derive_subkey(string $masterKey, string $purpose, string $keyId = 'k1'): string
{
    if (strlen($masterKey) !== 32) {
        throw new CryptoException('Master key must be exactly 32 bytes for HKDF derivation.');
    }

    $info = 'web-php-v1|' . $purpose . '|' . $keyId;
    return hash_hkdf('sha256', $masterKey, 32, $info, 'web-php-hkdf-salt-v1');
}

/**
 * Determine the preferred AEAD cipher algorithm supported by the current PHP runtime.
 * Prefers Libsodium XChaCha20-Poly1305 IETF when available, otherwise OpenSSL AES-256-GCM.
 */
function app_preferred_cipher_algorithm(): string
{
    $forced = strtolower(trim((string) (getenv('APP_CIPHER_ALGO') ?: ($_ENV['APP_CIPHER_ALGO'] ?? ''))));
    if ($forced !== '') {
        if ($forced === 'xchacha20poly1305') {
            if (!function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_encrypt')) {
                throw new CryptoException('Requested cipher xchacha20poly1305 is not available in this PHP runtime.');
            }
            return 'xchacha20poly1305';
        }
        if ($forced === 'aes-256-gcm') {
            if (!function_exists('openssl_encrypt') || !in_array('aes-256-gcm', openssl_get_cipher_methods(), true)) {
                throw new CryptoException('Requested cipher aes-256-gcm is not available in this PHP runtime.');
            }
            return 'aes-256-gcm';
        }
        throw new CryptoException('Unsupported APP_CIPHER_ALGO: ' . $forced);
    }

    if (function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_encrypt')) {
        return 'xchacha20poly1305';
    }

    if (function_exists('openssl_encrypt') && in_array('aes-256-gcm', openssl_get_cipher_methods(), true)) {
        return 'aes-256-gcm';
    }

    throw new CryptoException('Neither Libsodium XChaCha20-Poly1305 nor OpenSSL AES-256-GCM is available.');
}

/**
 * Check whether a string starts with the encrypted envelope marker ("enc:").
 */
function app_is_encrypted(?string $value): bool
{
    if (!is_string($value) || $value === '') {
        return false;
    }

    return str_starts_with($value, 'enc:');
}

/**
 * Build the canonical Additional Authenticated Data (AAD) string bound into the AEAD authentication tag.
 */
function app_build_canonical_aad(string $version, string $algorithm, string $keyId, string $contextAad): string
{
    return $version . '|' . $algorithm . '|' . $keyId . '|' . $contextAad;
}

/**
 * Encrypt sensitive plaintext using Authenticated Encryption with Associated Data (AEAD).
 *
 * Output format:
 *   enc:v1:<alg>:<key_id>:<iv_b64>:<tag_b64>:<ct_b64>
 *
 * @param string|null $plaintext Plaintext to encrypt (null or empty string returns as-is)
 * @param string      $aad       Additional Authenticated Data (e.g. "tbl_tkkhachhang.sdt")
 * @param string|null $keyId     Optional key ID from keyring (defaults to active_key_id)
 * @throws CryptoException on key or encryption error
 */
function app_encrypt(?string $plaintext, string $aad = '', ?string $keyId = null): ?string
{
    if ($plaintext === null || $plaintext === '') {
        return $plaintext;
    }

    $keyring = app_get_keyring();
    $targetKeyId = $keyId !== null ? trim($keyId) : $keyring['active_key_id'];
    if (!isset($keyring['keys'][$targetKeyId])) {
        throw new CryptoException('Requested key ID is not found in keyring: ' . $targetKeyId);
    }

    $algorithm = app_preferred_cipher_algorithm();
    $subKey = app_derive_subkey($keyring['keys'][$targetKeyId], 'aead-enc:' . $algorithm, $targetKeyId);
    $canonicalAad = app_build_canonical_aad('v1', $algorithm, $targetKeyId, $aad);

    if ($algorithm === 'xchacha20poly1305') {
        $nonceLen = defined('SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES')
            ? SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES
            : 24;
        $tagLen = defined('SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_ABYTES')
            ? SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_ABYTES
            : 16;

        $nonce = random_bytes($nonceLen);
        $combined = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($plaintext, $canonicalAad, $nonce, $subKey);
        if (!is_string($combined) || strlen($combined) < $tagLen) {
            throw new CryptoException('Libsodium AEAD encryption failed.');
        }

        $ctRaw = substr($combined, 0, -$tagLen);
        $tagRaw = substr($combined, -$tagLen);

        return sprintf(
            'enc:v1:%s:%s:%s:%s:%s',
            $algorithm,
            $targetKeyId,
            base64_encode($nonce),
            base64_encode($tagRaw),
            base64_encode($ctRaw)
        );
    }

    // OpenSSL AES-256-GCM (12-byte random IV, 16-byte authentication tag)
    $iv = random_bytes(12);
    $tag = '';
    $ctRaw = openssl_encrypt(
        $plaintext,
        'aes-256-gcm',
        $subKey,
        OPENSSL_RAW_DATA,
        $iv,
        $tag,
        $canonicalAad,
        16
    );

    if ($ctRaw === false || strlen($tag) !== 16) {
        throw new CryptoException('OpenSSL AES-256-GCM encryption failed.');
    }

    return sprintf(
        'enc:v1:%s:%s:%s:%s:%s',
        $algorithm,
        $targetKeyId,
        base64_encode($iv),
        base64_encode($tag),
        base64_encode($ctRaw)
    );
}

/**
 * Strictly decrypt and authenticate an AEAD envelope created by app_encrypt().
 *
 * Validates envelope format, version, algorithm, key ID, IV length, tag length, and
 * cryptographic authentication tag (including AAD).
 *
 * @throws CryptoException if ciphertext is malformed, truncated, wrong version, wrong key, wrong AAD, or tampered
 */
function app_decrypt(?string $stored, string $aad = ''): ?string
{
    if ($stored === null || $stored === '') {
        return $stored;
    }

    if (!str_starts_with($stored, 'enc:')) {
        throw new CryptoException('Input value is not a valid encrypted envelope.');
    }

    $parts = explode(':', $stored, 7);
    if (count($parts) !== 7 || $parts[0] !== 'enc') {
        throw new CryptoException('Malformed ciphertext envelope structure.');
    }

    [, $version, $algorithm, $keyId, $ivB64, $tagB64, $ctB64] = $parts;

    if ($version !== 'v1') {
        throw new CryptoException('Unsupported ciphertext envelope version: ' . $version);
    }

    if ($algorithm !== 'aes-256-gcm' && $algorithm !== 'xchacha20poly1305') {
        throw new CryptoException('Unsupported cipher algorithm: ' . $algorithm);
    }

    if (!preg_match('/\A[a-zA-Z0-9_-]{1,32}\z/', $keyId)) {
        throw new CryptoException('Invalid key identifier in ciphertext envelope.');
    }

    if ($ivB64 === '' || $tagB64 === '' || $ctB64 === '') {
        throw new CryptoException('Truncated ciphertext envelope components.');
    }

    $iv = base64_decode($ivB64, true);
    $tag = base64_decode($tagB64, true);
    $ctRaw = base64_decode($ctB64, true);

    if ($iv === false || $tag === false || $ctRaw === false) {
        throw new CryptoException('Invalid base64 encoding in ciphertext envelope.');
    }

    if (strlen($tag) !== 16) {
        throw new CryptoException('Invalid authentication tag length.');
    }

    $keyring = app_get_keyring();
    if (!isset($keyring['keys'][$keyId])) {
        throw new CryptoException('Unknown encryption key ID: ' . $keyId);
    }

    $subKey = app_derive_subkey($keyring['keys'][$keyId], 'aead-enc:' . $algorithm, $keyId);
    $canonicalAad = app_build_canonical_aad($version, $algorithm, $keyId, $aad);

    if ($algorithm === 'xchacha20poly1305') {
        if (strlen($iv) !== 24) {
            throw new CryptoException('Invalid nonce length for xchacha20poly1305.');
        }
        if (!function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_decrypt')) {
            throw new CryptoException('Libsodium extension is required to decrypt xchacha20poly1305 payload.');
        }

        $plaintext = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($ctRaw . $tag, $canonicalAad, $iv, $subKey);
        if ($plaintext === false) {
            throw new CryptoException('AEAD authentication or decryption failed (tampered data, wrong key, or wrong AAD).');
        }

        return $plaintext;
    }

    // OpenSSL AES-256-GCM
    if (strlen($iv) !== 12) {
        throw new CryptoException('Invalid IV length for aes-256-gcm.');
    }

    $plaintext = openssl_decrypt(
        $ctRaw,
        'aes-256-gcm',
        $subKey,
        OPENSSL_RAW_DATA,
        $iv,
        $tag,
        $canonicalAad
    );

    if ($plaintext === false) {
        throw new CryptoException('AEAD authentication or decryption failed (tampered data, wrong key, or wrong AAD).');
    }

    return $plaintext;
}

/**
 * Safely read a database field that is encrypted at rest.
 * - If the stored value is null or empty, returns as-is.
 * - If the stored value is an encrypted envelope ("enc:..."), strictly verifies and decrypts it via app_decrypt().
 *   If tampering/integrity failure occurs, logs a sanitized error and returns empty string (never leaks raw ciphertext).
 * - If the stored value does not start with "enc:" (unmigrated plaintext record):
 *   logs a warning so missed plaintext records are surfaced immediately. When APP_CRYPTO_STRICT_READ=1
 *   is enabled, returns empty string instead of leaking unencrypted data; otherwise returns the legacy
 *   value for transition compatibility.
 */
function app_decrypt_field(?string $stored, string $aad = ''): string
{
    if ($stored === null || $stored === '') {
        return '';
    }

    if (!app_is_encrypted($stored)) {
        error_log('[CRYPTO_UNENCRYPTED_READ] Unencrypted plaintext encountered in protected field context "' . $aad . '".');
        $strictRead = strtolower(trim((string) (getenv('APP_CRYPTO_STRICT_READ') ?: ($_ENV['APP_CRYPTO_STRICT_READ'] ?? '0'))));
        if (in_array($strictRead, ['1', 'true', 'yes', 'on'], true)) {
            return '';
        }
        return $stored;
    }

    try {
        return (string) app_decrypt($stored, $aad);
    } catch (Throwable $e) {
        error_log('[CRYPTO_INTEGRITY_ERROR] Failed to decrypt field for context "' . $aad . '": ' . $e->getMessage());
        return '';
    }
}

/**
 * Rotate a ciphertext envelope to the active key (or a specified target key ID)
 * without exposing plaintext outside the cryptographic module.
 *
 * @throws CryptoException if old ciphertext fails verification or new key is missing
 */
function app_rotate_ciphertext(string $storedCiphertext, string $aad = '', ?string $targetKeyId = null): string
{
    $plaintext = app_decrypt($storedCiphertext, $aad);
    return (string) app_encrypt($plaintext, $aad, $targetKeyId);
}

/**
 * Inspect metadata of an encrypted envelope without decrypting it.
 *
 * @return array{version: string, algorithm: string, key_id: string}|null
 */
function app_inspect_envelope(?string $stored): ?array
{
    if (!is_string($stored) || !str_starts_with($stored, 'enc:')) {
        return null;
    }

    $parts = explode(':', $stored, 7);
    if (count($parts) !== 7) {
        return null;
    }

    return [
        'version'   => $parts[1],
        'algorithm' => $parts[2],
        'key_id'    => $parts[3],
    ];
}

/**
 * Compute a deterministic blind index (HMAC-SHA256) using the purpose-separated MAC subkey.
 * Useful when exact-match lookup on an encrypted field is required.
 */
function app_blind_index(string $value, string $context): string
{
    $normalized = mb_strtolower(trim($value), 'UTF-8');
    $keyring = app_get_keyring();
    $primaryKey = $keyring['keys'][$keyring['active_key_id']];
    $macKey = app_derive_subkey($primaryKey, 'blind-index:' . $context, $keyring['active_key_id']);

    return hash_hmac('sha256', $normalized, $macKey);
}

/**
 * Compute a one-way cryptographic hash (SHA-256) of a high-entropy verification token
 * (such as a password reset token or order tracking token) before storing in the database.
 */
function app_hash_token(string $rawToken): string
{
    $trimmed = trim($rawToken);
    if ($trimmed === '') {
        throw new InvalidArgumentException('Token must not be empty.');
    }

    return hash('sha256', $trimmed);
}

/**
 * Verify a raw high-entropy token against a stored token value in constant time.
 * Supports both SHA-256 hashed tokens and controlled legacy plaintext tokens.
 */
function app_verify_stored_token(string $rawToken, ?string $storedToken, bool &$isLegacyPlaintext = false): bool
{
    $isLegacyPlaintext = false;
    if ($rawToken === '' || !is_string($storedToken) || $storedToken === '') {
        return false;
    }

    $expectedHash = app_hash_token($rawToken);
    if (hash_equals($storedToken, $expectedHash)) {
        return true;
    }

    // Legacy fallback if stored token was saved before token-hashing migration
    if (hash_equals($storedToken, $rawToken)) {
        $isLegacyPlaintext = true;
        return true;
    }

    return false;
}

/**
 * Mask an email address for safe application logging (e.g., "ng***@gmail.com").
 */
function app_mask_email(string $email): string
{
    $email = trim($email);
    $atPos = strpos($email, '@');
    if ($atPos === false || $atPos === 0) {
        return '***';
    }

    $local = substr($email, 0, $atPos);
    $domain = substr($email, $atPos + 1);
    $visible = substr($local, 0, min(2, strlen($local)));

    return $visible . '***@' . $domain;
}

/**
 * Mask a phone number for safe logging (e.g., "09******89").
 */
function app_mask_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    $len = strlen($digits);
    if ($len < 4) {
        return '***';
    }

    return substr($digits, 0, 2) . str_repeat('*', max(2, $len - 4)) . substr($digits, -2);
}
