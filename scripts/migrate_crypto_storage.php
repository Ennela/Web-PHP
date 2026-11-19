<?php
/**
 * Database Cryptographic Storage Migration, Key Rotation, Verification & Rollback CLI Tool.
 *
 * Usage:
 *   php scripts/migrate_crypto_storage.php --dry-run --migrate
 *   php scripts/migrate_crypto_storage.php --backup
 *   php scripts/migrate_crypto_storage.php --migrate
 *   php scripts/migrate_crypto_storage.php --verify
 *   php scripts/migrate_crypto_storage.php --rotate-key=k2
 *   php scripts/migrate_crypto_storage.php --rotate-keys=k2
 *   php scripts/migrate_crypto_storage.php --dry-run --rotate-key=k2
 *   php scripts/migrate_crypto_storage.php --rollback=backups/crypto_backup_YYYYMMDD_HHMMSS.json
 *   php scripts/migrate_crypto_storage.php --prune-backups=0
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found.');
}

define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once BASE_PATH . 'includes/environment.php';
app_load_local_environment(BASE_PATH . '.env');
require_once BASE_PATH . 'includes/security.php';
require_once BASE_PATH . 'includes/crypto.php';
require_once BASE_PATH . 'includes/database.php';

/**
 * Parse and strictly validate CLI arguments so unknown flags fail immediately.
 *
 * @param array<int, string> $argv
 * @return array<string, mixed>
 */
function parse_cli_options(array $argv): array
{
    $allowedFlags = [
        'backup'        => false,
        'migrate'       => false,
        'verify'        => false,
        'dry-run'       => false,
        'help'          => false,
        'rotate-key'    => 'required',
        'rotate-keys'   => 'optional',
        'rollback'      => 'required',
        'prune-backups' => 'optional',
    ];

    $parsed = [];
    $argsCount = count($argv);
    for ($i = 1; $i < $argsCount; $i++) {
        $arg = $argv[$i];
        if (!str_starts_with($arg, '--')) {
            fwrite(STDERR, "[CLI ERROR] Unexpected positional argument: {$arg}\n");
            exit(1);
        }

        $body = substr($arg, 2);
        $eqPos = strpos($body, '=');
        if ($eqPos !== false) {
            $name = substr($body, 0, $eqPos);
            $val = substr($body, $eqPos + 1);
        } else {
            $name = $body;
            $val = null;
        }

        if (!array_key_exists($name, $allowedFlags)) {
            fwrite(STDERR, "[CLI ERROR] Unknown option: --{$name}\n");
            exit(1);
        }

        $mode = $allowedFlags[$name];
        if ($mode === false) {
            if ($val !== null) {
                fwrite(STDERR, "[CLI ERROR] Option --{$name} does not accept a value.\n");
                exit(1);
            }
            $parsed[$name] = true;
        } elseif ($mode === 'required') {
            if ($val === null || trim($val) === '') {
                fwrite(STDERR, "[CLI ERROR] Option --{$name} requires a non-empty value (e.g. --{$name}=<value>).\n");
                exit(1);
            }
            $parsed[$name] = trim($val);
        } else {
            // optional value
            $parsed[$name] = $val !== null ? trim($val) : true;
        }
    }

    if ($parsed === []) {
        $parsed = ['migrate' => true, 'verify' => true];
    }

    return $parsed;
}

function print_cli_help(): void
{
    echo <<<HELP
Database Cryptographic Storage Migration & Verification CLI Tool

Options:
  --dry-run                  Simulate migration or key rotation and report record formats without modifying DB
  --backup                   Create an AEAD-encrypted pre-migration snapshot in backups/
  --migrate                  Safely upgrade password hashes, hash tokens, and encrypt PII columns
  --verify                   Verify password hashes (Argon2id), token digests, AEAD envelopes, and schema scope
  --rotate-key=<key_id>      Re-encrypt all AEAD ciphertext columns to <key_id>
  --rotate-keys[=<key_id>]   Alias for --rotate-key (defaults to active key ID in keyring if omitted)
  --rollback=<backup_file>   Restore tables from an encrypted or legacy JSON backup file
  --prune-backups[=<keep>]   Securely wipe and delete old backup files in backups/ (default keep=0)
  --help                     Show this usage guide

HELP;
}

/**
 * Execute a SQL query and throw a descriptive RuntimeException on failure.
 *
 * @return mysqli_result|bool
 */
function db_query_or_fail(mysqli $con, string $sql, string $context)
{
    $res = mysqli_query($con, $sql);
    if ($res === false) {
        throw new RuntimeException("{$context} failed: " . mysqli_error($con));
    }
    return $res;
}

/**
 * Prepare, bind, and execute a parameterized statement or throw on any error.
 *
 * @param array<int, mixed> $params
 */
function db_exec_prepared_or_fail(mysqli $con, string $sql, string $types, array $params, string $context): void
{
    $stmt = mysqli_prepare($con, $sql);
    if ($stmt === false) {
        throw new RuntimeException("{$context} prepare failed: " . mysqli_error($con));
    }

    if ($types !== '' && $params !== []) {
        if (!mysqli_stmt_bind_param($stmt, $types, ...$params)) {
            $err = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            throw new RuntimeException("{$context} bind_param failed: {$err}");
        }
    }

    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new RuntimeException("{$context} execute failed: {$err}");
    }

    mysqli_stmt_close($stmt);
}

function ensure_backup_directory(): string
{
    $dir = BASE_PATH . 'backups';
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new RuntimeException("Failed to create backup directory: {$dir}");
        }
    }

    if (!is_writable($dir)) {
        throw new RuntimeException("Backup directory is not writable: {$dir}");
    }

    $htaccess = $dir . DIRECTORY_SEPARATOR . '.htaccess';
    if (!is_file($htaccess)) {
        $written = file_put_contents($htaccess, "Require all denied\nDeny from all\n", LOCK_EX);
        if ($written === false) {
            throw new RuntimeException("Failed to write web-access protection file: {$htaccess}");
        }
    }

    return $dir;
}

/**
 * Create an AEAD-encrypted database snapshot of all migration-targeted tables
 * and verify read-back integrity before returning.
 */
function create_database_backup(mysqli $con): string
{
    $dir = ensure_backup_directory();
    $tables = ['tbl_tkkhachhang', 'tbl_qlthanhvien', 'tbl_dangnhap', 'tbl_diachi', 'oder'];
    $snapshot = [
        'created_at' => date('c'),
        'database'   => getenv('MYSQLDATABASE') ?: 'giaythethao2',
        'tables'     => [],
    ];

    foreach ($tables as $table) {
        $res = db_query_or_fail($con, "SELECT * FROM `{$table}`", "Backup SELECT on {$table}");
        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = $row;
        }
        mysqli_free_result($res);
        $snapshot['tables'][$table] = $rows;
    }

    $rawJson = json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (!is_string($rawJson) || $rawJson === '') {
        throw new RuntimeException('Failed to encode database backup snapshot to JSON: ' . json_last_error_msg());
    }

    // Encrypt the snapshot payload with AEAD so cleartext passwords/PII are never written to disk
    $encryptedPayload = app_encrypt($rawJson, 'crypto_backup_snapshot');
    if (!is_string($encryptedPayload) || !app_is_encrypted($encryptedPayload)) {
        throw new RuntimeException('Failed to encrypt database backup snapshot.');
    }

    $envelopeDoc = [
        'format'       => 'encrypted_backup_v1',
        'created_at'   => $snapshot['created_at'],
        'database'     => $snapshot['database'],
        'tables_count' => count($tables),
        'ciphertext'   => $encryptedPayload,
    ];

    $fileContent = json_encode($envelopeDoc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (!is_string($fileContent) || $fileContent === '') {
        throw new RuntimeException('Failed to serialize encrypted backup envelope.');
    }

    $filename = $dir . DIRECTORY_SEPARATOR . 'crypto_backup_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.json';
    $bytesWritten = file_put_contents($filename, $fileContent, LOCK_EX);
    if ($bytesWritten === false || $bytesWritten !== strlen($fileContent)) {
        throw new RuntimeException("Failed to write complete backup file to disk: {$filename}");
    }
    @chmod($filename, 0600);

    // Immediate read-back & decryption verification
    $readBackRaw = file_get_contents($filename);
    if (!is_string($readBackRaw) || $readBackRaw === '') {
        throw new RuntimeException("Backup verification failed: unable to read back {$filename}");
    }
    $readBackDoc = json_decode($readBackRaw, true);
    if (!is_array($readBackDoc) || ($readBackDoc['format'] ?? '') !== 'encrypted_backup_v1' || empty($readBackDoc['ciphertext'])) {
        throw new RuntimeException("Backup verification failed: invalid envelope structure in {$filename}");
    }
    $decryptedJson = app_decrypt((string) $readBackDoc['ciphertext'], 'crypto_backup_snapshot');
    $decodedSnapshot = json_decode((string) $decryptedJson, true);
    if (!is_array($decodedSnapshot) || !isset($decodedSnapshot['tables']) || count($decodedSnapshot['tables']) !== count($tables)) {
        throw new RuntimeException("Backup verification failed: decrypted payload mismatch in {$filename}");
    }

    return $filename;
}

/**
 * Securely overwrite and delete backup files in backups/, keeping at most $keepLatest newest files.
 */
function prune_database_backups(int $keepLatest = 0): int
{
    $dir = BASE_PATH . 'backups';
    if (!is_dir($dir)) {
        return 0;
    }

    $files = glob($dir . DIRECTORY_SEPARATOR . 'crypto_backup_*.json');
    if (!is_array($files) || $files === []) {
        return 0;
    }

    usort($files, static function (string $a, string $b): int {
        return (int) filemtime($b) <=> (int) filemtime($a);
    });

    $toDelete = $keepLatest > 0 ? array_slice($files, $keepLatest) : $files;
    $deletedCount = 0;

    foreach ($toDelete as $file) {
        if (is_file($file)) {
            $size = filesize($file);
            if (is_int($size) && $size > 0 && is_writable($file)) {
                file_put_contents($file, str_repeat("\0", $size), LOCK_EX);
            }
            if (!unlink($file)) {
                throw new RuntimeException("Failed to delete backup file: {$file}");
            }
            $deletedCount++;
        }
    }

    return $deletedCount;
}

/**
 * Apply schema widening DDL and verify resulting column types before migrating data.
 */
function apply_schema_widening(mysqli $con): void
{
    $queries = [
        "ALTER TABLE `tbl_tkkhachhang`
            MODIFY COLUMN `password` VARCHAR(255) DEFAULT NULL,
            MODIFY COLUMN `sdt` VARCHAR(512) DEFAULT NULL,
            MODIFY COLUMN `diachi` TEXT DEFAULT NULL,
            MODIFY COLUMN `reset_token` VARCHAR(128) DEFAULT NULL",
        "ALTER TABLE `tbl_diachi`
            MODIFY COLUMN `sdt` VARCHAR(512) NOT NULL,
            MODIFY COLUMN `diachi_cuthe` TEXT NOT NULL",
        "ALTER TABLE `tbl_qlthanhvien`
            MODIFY COLUMN `matkhau` VARCHAR(255) DEFAULT NULL,
            MODIFY COLUMN `diachicuthe` TEXT DEFAULT NULL",
        "ALTER TABLE `oder`
            MODIFY COLUMN `sdt` VARCHAR(512) DEFAULT NULL,
            MODIFY COLUMN `diachi` TEXT DEFAULT NULL,
            MODIFY COLUMN `note` TEXT DEFAULT NULL,
            MODIFY COLUMN `token` VARCHAR(128) DEFAULT NULL",
        "ALTER TABLE `tbl_dangnhap`
            MODIFY COLUMN `matkhau` VARCHAR(255) DEFAULT NULL",
        "CREATE TABLE IF NOT EXISTS `tbl_email_webhook_log` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `email` VARCHAR(255) NOT NULL,
            `event_type` VARCHAR(50) NOT NULL,
            `message_id` VARCHAR(100) DEFAULT NULL,
            `error_info` TEXT DEFAULT NULL,
            `event_time` INT(11) DEFAULT NULL,
            `created_at` INT(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_webhook_email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];

    foreach ($queries as $sql) {
        db_query_or_fail($con, $sql, 'Schema widening DDL');
    }

    // Verify column widths in database metadata before proceeding
    $expectedColumns = [
        'tbl_tkkhachhang' => ['password' => 255, 'sdt' => 512, 'diachi' => 'text', 'reset_token' => 128],
        'tbl_diachi'      => ['sdt' => 512, 'diachi_cuthe' => 'text'],
        'tbl_qlthanhvien' => ['matkhau' => 255, 'diachicuthe' => 'text'],
        'oder'            => ['sdt' => 512, 'diachi' => 'text', 'note' => 'text', 'token' => 128],
        'tbl_dangnhap'    => ['matkhau' => 255],
    ];

    foreach ($expectedColumns as $table => $cols) {
        $res = db_query_or_fail($con, "SHOW COLUMNS FROM `{$table}`", "Schema verification on {$table}");
        $actual = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $actual[$row['Field']] = strtolower((string) $row['Type']);
        }
        mysqli_free_result($res);

        foreach ($cols as $col => $requirement) {
            if (!isset($actual[$col])) {
                throw new RuntimeException("Schema verification failed: missing column {$table}.{$col}");
            }
            $typeStr = $actual[$col];
            if ($requirement === 'text') {
                if (!str_contains($typeStr, 'text')) {
                    throw new RuntimeException("Schema verification failed: {$table}.{$col} expected TEXT, got {$typeStr}");
                }
            } else {
                if (preg_match('/varchar\((\d+)\)/', $typeStr, $m) === 1) {
                    if ((int) $m[1] < (int) $requirement) {
                        throw new RuntimeException(
                            "Schema verification failed: {$table}.{$col} width {$m[1]} is smaller than required {$requirement}"
                        );
                    }
                } elseif (!str_contains($typeStr, 'text')) {
                    throw new RuntimeException("Schema verification failed: {$table}.{$col} unexpected type {$typeStr}");
                }
            }
        }
    }
}

/**
 * Run migration (or dry-run inspection) across all target tables.
 *
 * @return array<string, mixed>
 */
function run_migration(mysqli $con, bool $dryRun = false): array
{
    if (!$dryRun) {
        $backupPath = create_database_backup($con);
        echo "[BACKUP] Verified AEAD-encrypted snapshot saved to: {$backupPath}\n";

        apply_schema_widening($con);
        echo "[SCHEMA] Widened and verified password, token, and encrypted PII columns.\n";
    } else {
        echo "[DRY-RUN] Inspecting records without modifying schema or data...\n";
    }

    $stats = [
        'passwords_upgraded_plaintext' => 0,
        'passwords_wrapped_legacy'     => 0,
        'passwords_already_argon2id'   => 0,
        'passwords_already_wrapped'    => 0,
        'passwords_deferred_on_login'  => 0,
        'tokens_hashed'                => 0,
        'fields_encrypted'             => 0,
    ];

    if (!$dryRun) {
        if (!mysqli_begin_transaction($con)) {
            throw new RuntimeException('Failed to start database transaction: ' . mysqli_error($con));
        }
    }

    try {
        $lockClause = $dryRun ? '' : ' FOR UPDATE';

        // 1. tbl_tkkhachhang: password, reset_token, sdt, diachi
        $res = db_query_or_fail(
            $con,
            "SELECT `makh`, `password`, `reset_token`, `sdt`, `diachi` FROM `tbl_tkkhachhang`{$lockClause}",
            'Select tbl_tkkhachhang'
        );
        while ($row = mysqli_fetch_assoc($res)) {
            $makh = (int) $row['makh'];
            $pw = $row['password'];
            $rt = $row['reset_token'];
            $sdt = $row['sdt'];
            $diachi = $row['diachi'];

            $newPw = $pw;
            if (is_string($pw) && $pw !== '') {
                $upg = app_upgrade_stored_password_for_migration($pw);
                $newPw = $upg['value'];
                if ($upg['status'] === 'upgraded_plaintext') {
                    $stats['passwords_upgraded_plaintext']++;
                } elseif ($upg['status'] === 'wrapped_legacy_hash') {
                    $stats['passwords_wrapped_legacy']++;
                    echo "  [LEGACY HASH WRAPPED] tbl_tkkhachhang.password (makh={$makh}, format={$upg['format']}) wrapped in Argon2id prehash envelope.\n";
                } elseif ($upg['status'] === 'already_argon2id') {
                    $stats['passwords_already_argon2id']++;
                } elseif ($upg['status'] === 'already_wrapped') {
                    $stats['passwords_already_wrapped']++;
                } elseif ($upg['status'] === 'deferred_on_login') {
                    $stats['passwords_deferred_on_login']++;
                }
            }

            $newRt = $rt;
            if (is_string($rt) && $rt !== '' && preg_match('/\A[a-f0-9]{64}\z/i', $rt) !== 1) {
                $newRt = app_hash_token($rt);
                $stats['tokens_hashed']++;
            }

            $newSdt = $sdt;
            if (is_string($sdt) && $sdt !== '' && !app_is_encrypted($sdt)) {
                $newSdt = app_encrypt($sdt, 'tbl_tkkhachhang.sdt');
                $stats['fields_encrypted']++;
            }

            $newDiachi = $diachi;
            if (is_string($diachi) && $diachi !== '' && !app_is_encrypted($diachi)) {
                $newDiachi = app_encrypt($diachi, 'tbl_tkkhachhang.diachi');
                $stats['fields_encrypted']++;
            }

            if (!$dryRun && ($newPw !== $pw || $newRt !== $rt || $newSdt !== $sdt || $newDiachi !== $diachi)) {
                db_exec_prepared_or_fail(
                    $con,
                    "UPDATE `tbl_tkkhachhang` SET `password` = ?, `reset_token` = ?, `sdt` = ?, `diachi` = ? WHERE `makh` = ?",
                    'ssssi',
                    [$newPw, $newRt, $newSdt, $newDiachi, $makh],
                    "Update tbl_tkkhachhang (makh={$makh})"
                );
            }
        }
        mysqli_free_result($res);

        // 2. tbl_qlthanhvien: matkhau, diachicuthe
        $res = db_query_or_fail(
            $con,
            "SELECT `id`, `matkhau`, `diachicuthe` FROM `tbl_qlthanhvien`{$lockClause}",
            'Select tbl_qlthanhvien'
        );
        while ($row = mysqli_fetch_assoc($res)) {
            $id = (int) $row['id'];
            $pw = $row['matkhau'];
            $dc = $row['diachicuthe'];

            $newPw = $pw;
            if (is_string($pw) && $pw !== '') {
                $upg = app_upgrade_stored_password_for_migration($pw);
                $newPw = $upg['value'];
                if ($upg['status'] === 'upgraded_plaintext') {
                    $stats['passwords_upgraded_plaintext']++;
                } elseif ($upg['status'] === 'wrapped_legacy_hash') {
                    $stats['passwords_wrapped_legacy']++;
                    echo "  [LEGACY HASH WRAPPED] tbl_qlthanhvien.matkhau (id={$id}, format={$upg['format']}) wrapped in Argon2id prehash envelope.\n";
                } elseif ($upg['status'] === 'already_argon2id') {
                    $stats['passwords_already_argon2id']++;
                } elseif ($upg['status'] === 'already_wrapped') {
                    $stats['passwords_already_wrapped']++;
                } elseif ($upg['status'] === 'deferred_on_login') {
                    $stats['passwords_deferred_on_login']++;
                }
            }

            $newDc = $dc;
            if (is_string($dc) && $dc !== '' && !app_is_encrypted($dc)) {
                $newDc = app_encrypt($dc, 'tbl_qlthanhvien.diachicuthe');
                $stats['fields_encrypted']++;
            }

            if (!$dryRun && ($newPw !== $pw || $newDc !== $dc)) {
                db_exec_prepared_or_fail(
                    $con,
                    "UPDATE `tbl_qlthanhvien` SET `matkhau` = ?, `diachicuthe` = ? WHERE `id` = ?",
                    'ssi',
                    [$newPw, $newDc, $id],
                    "Update tbl_qlthanhvien (id={$id})"
                );
            }
        }
        mysqli_free_result($res);

        // 3. tbl_dangnhap: matkhau
        $res = db_query_or_fail(
            $con,
            "SELECT `mavn`, `matkhau` FROM `tbl_dangnhap`{$lockClause}",
            'Select tbl_dangnhap'
        );
        while ($row = mysqli_fetch_assoc($res)) {
            $mavn = (int) $row['mavn'];
            $pw = $row['matkhau'];
            if (is_string($pw) && $pw !== '') {
                $upg = app_upgrade_stored_password_for_migration($pw);
                $newPw = $upg['value'];
                if ($upg['status'] === 'upgraded_plaintext') {
                    $stats['passwords_upgraded_plaintext']++;
                } elseif ($upg['status'] === 'wrapped_legacy_hash') {
                    $stats['passwords_wrapped_legacy']++;
                    echo "  [LEGACY HASH WRAPPED] tbl_dangnhap.matkhau (mavn={$mavn}, format={$upg['format']}) wrapped in Argon2id prehash envelope.\n";
                } elseif ($upg['status'] === 'already_argon2id') {
                    $stats['passwords_already_argon2id']++;
                } elseif ($upg['status'] === 'already_wrapped') {
                    $stats['passwords_already_wrapped']++;
                } elseif ($upg['status'] === 'deferred_on_login') {
                    $stats['passwords_deferred_on_login']++;
                }

                if (!$dryRun && $newPw !== $pw) {
                    db_exec_prepared_or_fail(
                        $con,
                        "UPDATE `tbl_dangnhap` SET `matkhau` = ? WHERE `mavn` = ?",
                        'si',
                        [$newPw, $mavn],
                        "Update tbl_dangnhap (mavn={$mavn})"
                    );
                }
            }
        }
        mysqli_free_result($res);

        // 4. tbl_diachi: sdt, diachi_cuthe
        $res = db_query_or_fail(
            $con,
            "SELECT `id`, `sdt`, `diachi_cuthe` FROM `tbl_diachi`{$lockClause}",
            'Select tbl_diachi'
        );
        while ($row = mysqli_fetch_assoc($res)) {
            $id = (int) $row['id'];
            $sdt = $row['sdt'];
            $dc = $row['diachi_cuthe'];

            $newSdt = $sdt;
            if (is_string($sdt) && $sdt !== '' && !app_is_encrypted($sdt)) {
                $newSdt = app_encrypt($sdt, 'tbl_diachi.sdt');
                $stats['fields_encrypted']++;
            }

            $newDc = $dc;
            if (is_string($dc) && $dc !== '' && !app_is_encrypted($dc)) {
                $newDc = app_encrypt($dc, 'tbl_diachi.diachi_cuthe');
                $stats['fields_encrypted']++;
            }

            if (!$dryRun && ($newSdt !== $sdt || $newDc !== $dc)) {
                db_exec_prepared_or_fail(
                    $con,
                    "UPDATE `tbl_diachi` SET `sdt` = ?, `diachi_cuthe` = ? WHERE `id` = ?",
                    'ssi',
                    [$newSdt, $newDc, $id],
                    "Update tbl_diachi (id={$id})"
                );
            }
        }
        mysqli_free_result($res);

        // 5. oder: token, sdt, diachi, note
        $res = db_query_or_fail(
            $con,
            "SELECT `id`, `token`, `sdt`, `diachi`, `note` FROM `oder`{$lockClause}",
            'Select oder'
        );
        while ($row = mysqli_fetch_assoc($res)) {
            $id = (int) $row['id'];
            $tk = $row['token'];
            $sdt = $row['sdt'];
            $dc = $row['diachi'];
            $note = $row['note'];

            $newTk = $tk;
            if (is_string($tk) && $tk !== '' && preg_match('/\A[a-f0-9]{64}\z/i', $tk) !== 1) {
                $newTk = app_hash_token($tk);
                $stats['tokens_hashed']++;
            }

            $newSdt = $sdt;
            if (is_string($sdt) && $sdt !== '' && !app_is_encrypted($sdt)) {
                $newSdt = app_encrypt($sdt, 'oder.sdt');
                $stats['fields_encrypted']++;
            }

            $newDc = $dc;
            if (is_string($dc) && $dc !== '' && !app_is_encrypted($dc)) {
                $newDc = app_encrypt($dc, 'oder.diachi');
                $stats['fields_encrypted']++;
            }

            $newNote = $note;
            if (is_string($note) && $note !== '' && !app_is_encrypted($note)) {
                $newNote = app_encrypt($note, 'oder.note');
                $stats['fields_encrypted']++;
            }

            if (!$dryRun && ($newTk !== $tk || $newSdt !== $sdt || $newDc !== $dc || $newNote !== $note)) {
                db_exec_prepared_or_fail(
                    $con,
                    "UPDATE `oder` SET `token` = ?, `sdt` = ?, `diachi` = ?, `note` = ? WHERE `id` = ?",
                    'ssssi',
                    [$newTk, $newSdt, $newDc, $newNote, $id],
                    "Update oder (id={$id})"
                );
            }
        }
        mysqli_free_result($res);

        if (!$dryRun) {
            if (!mysqli_commit($con)) {
                throw new RuntimeException('Failed to commit migration transaction: ' . mysqli_error($con));
            }
        }
    } catch (Throwable $e) {
        if (!$dryRun) {
            mysqli_rollback($con);
        }
        throw $e;
    }

    return $stats;
}

function run_key_rotation(mysqli $con, string $targetKeyId, bool $dryRun = false): int
{
    $keyring = app_get_keyring();
    if (!isset($keyring['keys'][$targetKeyId])) {
        throw new RuntimeException("Target key ID '{$targetKeyId}' is not present in the configured keyring.");
    }

    $rotatedCount = 0;
    if (!$dryRun) {
        if (!mysqli_begin_transaction($con)) {
            throw new RuntimeException('Failed to start key rotation transaction: ' . mysqli_error($con));
        }
    }

    try {
        $lockClause = $dryRun ? '' : ' FOR UPDATE';
        $targets = [
            ['table' => 'tbl_tkkhachhang', 'pk' => 'makh', 'cols' => ['sdt' => 'tbl_tkkhachhang.sdt', 'diachi' => 'tbl_tkkhachhang.diachi']],
            ['table' => 'tbl_qlthanhvien', 'pk' => 'id',   'cols' => ['diachicuthe' => 'tbl_qlthanhvien.diachicuthe']],
            ['table' => 'tbl_diachi',      'pk' => 'id',   'cols' => ['sdt' => 'tbl_diachi.sdt', 'diachi_cuthe' => 'tbl_diachi.diachi_cuthe']],
            ['table' => 'oder',            'pk' => 'id',   'cols' => ['sdt' => 'oder.sdt', 'diachi' => 'oder.diachi', 'note' => 'oder.note']],
        ];

        foreach ($targets as $t) {
            $table = $t['table'];
            $pk = $t['pk'];
            $colNames = array_keys($t['cols']);
            $selectCols = '`' . $pk . '`, `' . implode('`, `', $colNames) . '`';
            $res = db_query_or_fail($con, "SELECT {$selectCols} FROM `{$table}`{$lockClause}", "Key rotation SELECT on {$table}");

            while ($row = mysqli_fetch_assoc($res)) {
                $pkVal = (int) $row[$pk];
                $updates = [];
                $params = [];
                $types = '';

                foreach ($t['cols'] as $col => $aad) {
                    $val = $row[$col];
                    if (is_string($val) && app_is_encrypted($val)) {
                        $meta = app_inspect_envelope($val);
                        if ($meta !== null && $meta['key_id'] !== $targetKeyId) {
                            $rotated = app_rotate_ciphertext($val, $aad, $targetKeyId);
                            $updates[] = "`{$col}` = ?";
                            $params[] = $rotated;
                            $types .= 's';
                            $rotatedCount++;
                        }
                    }
                }

                if (!$dryRun && $updates !== []) {
                    $params[] = $pkVal;
                    $types .= 'i';
                    $sql = sprintf("UPDATE `%s` SET %s WHERE `%s` = ?", $table, implode(', ', $updates), $pk);
                    db_exec_prepared_or_fail($con, $sql, $types, $params, "Rotate key on {$table} ({$pk}={$pkVal})");
                }
            }
            mysqli_free_result($res);
        }

        if (!$dryRun) {
            if (!mysqli_commit($con)) {
                throw new RuntimeException('Failed to commit key rotation transaction: ' . mysqli_error($con));
            }
        }
    } catch (Throwable $e) {
        if (!$dryRun) {
            mysqli_rollback($con);
        }
        throw $e;
    }

    return $rotatedCount;
}

function run_verification(mysqli $con): bool
{
    $errors = [];
    $warnings = [];

    // 1. Verify all password columns
    $pwChecks = [
        ['table' => 'tbl_tkkhachhang', 'pk' => 'makh', 'col' => 'password'],
        ['table' => 'tbl_qlthanhvien', 'pk' => 'id',   'col' => 'matkhau'],
        ['table' => 'tbl_dangnhap',    'pk' => 'mavn', 'col' => 'matkhau'],
    ];

    $pwSummary = [
        'total_rows'       => 0,
        'argon2id'         => 0,
        'prehashed_legacy' => 0,
        'modern_other'     => 0,
        'invalid_or_plain' => 0,
    ];

    foreach ($pwChecks as $chk) {
        $res = db_query_or_fail(
            $con,
            "SELECT `{$chk['pk']}`, `{$chk['col']}` FROM `{$chk['table']}`",
            "Verify SELECT on {$chk['table']}.{$chk['col']}"
        );
        while ($row = mysqli_fetch_assoc($res)) {
            $pwSummary['total_rows']++;
            $pkVal = $row[$chk['pk']];
            $val = $row[$chk['col']];
            $format = app_classify_password_storage($val);

            if ($format === 'argon2id') {
                $pwSummary['argon2id']++;
            } elseif (str_starts_with($format, 'prehashed_')) {
                $pwSummary['prehashed_legacy']++;
                $warnings[] = "{$chk['table']}.{$chk['col']} (id={$pkVal}) is protected with Argon2id ({$format}); will unwrap to direct Argon2id on next user login.";
            } elseif ($format === 'modern_other') {
                $pwSummary['modern_other']++;
                $warnings[] = "{$chk['table']}.{$chk['col']} (id={$pkVal}) uses non-Argon2id modern hash; will upgrade to Argon2id on next user login.";
            } else {
                $pwSummary['invalid_or_plain']++;
                $errors[] = "{$chk['table']}.{$chk['col']} (id={$pkVal}) is in unsafe format '{$format}' (not protected by Argon2id).";
            }
        }
        mysqli_free_result($res);
    }

    // 2. Verify token columns (must be null/empty or 64-hex SHA-256 digests)
    $tokenChecks = [
        ['table' => 'tbl_tkkhachhang', 'pk' => 'makh', 'col' => 'reset_token'],
        ['table' => 'oder',            'pk' => 'id',   'col' => 'token'],
    ];

    $tokenSummary = [
        'total_rows'   => 0,
        'hashed_sha256'=> 0,
        'null_or_empty'=> 0,
        'unhashed'     => 0,
    ];

    foreach ($tokenChecks as $chk) {
        $res = db_query_or_fail(
            $con,
            "SELECT `{$chk['pk']}`, `{$chk['col']}` FROM `{$chk['table']}`",
            "Verify SELECT on {$chk['table']}.{$chk['col']}"
        );
        while ($row = mysqli_fetch_assoc($res)) {
            $tokenSummary['total_rows']++;
            $pkVal = $row[$chk['pk']];
            $val = $row[$chk['col']];
            if ($val === null || $val === '') {
                $tokenSummary['null_or_empty']++;
                continue;
            }
            if (preg_match('/\A[a-f0-9]{64}\z/i', (string) $val) === 1) {
                $tokenSummary['hashed_sha256']++;
            } else {
                $tokenSummary['unhashed']++;
                $errors[] = "{$chk['table']}.{$chk['col']} (id={$pkVal}) contains an unhashed plaintext token.";
            }
        }
        mysqli_free_result($res);
    }

    // 3. Verify all encrypted PII columns
    $encChecks = [
        ['table' => 'tbl_tkkhachhang', 'pk' => 'makh', 'col' => 'sdt',          'aad' => 'tbl_tkkhachhang.sdt'],
        ['table' => 'tbl_tkkhachhang', 'pk' => 'makh', 'col' => 'diachi',       'aad' => 'tbl_tkkhachhang.diachi'],
        ['table' => 'tbl_qlthanhvien', 'pk' => 'id',   'col' => 'diachicuthe',  'aad' => 'tbl_qlthanhvien.diachicuthe'],
        ['table' => 'tbl_diachi',      'pk' => 'id',   'col' => 'sdt',          'aad' => 'tbl_diachi.sdt'],
        ['table' => 'tbl_diachi',      'pk' => 'id',   'col' => 'diachi_cuthe', 'aad' => 'tbl_diachi.diachi_cuthe'],
        ['table' => 'oder',            'pk' => 'id',   'col' => 'sdt',          'aad' => 'oder.sdt'],
        ['table' => 'oder',            'pk' => 'id',   'col' => 'diachi',       'aad' => 'oder.diachi'],
        ['table' => 'oder',            'pk' => 'id',   'col' => 'note',         'aad' => 'oder.note'],
    ];

    $encSummary = [
        'total_cells'    => 0,
        'encrypted_ok'   => 0,
        'null_or_empty'  => 0,
        'plaintext_leak' => 0,
        'decrypt_failed' => 0,
        'key_ids'        => [],
    ];

    foreach ($encChecks as $chk) {
        $res = db_query_or_fail(
            $con,
            "SELECT `{$chk['pk']}`, `{$chk['col']}` FROM `{$chk['table']}`",
            "Verify SELECT on {$chk['table']}.{$chk['col']}"
        );
        while ($row = mysqli_fetch_assoc($res)) {
            $encSummary['total_cells']++;
            $pkVal = $row[$chk['pk']];
            $val = $row[$chk['col']];
            if ($val === null || $val === '') {
                $encSummary['null_or_empty']++;
                continue;
            }
            if (!app_is_encrypted($val)) {
                $encSummary['plaintext_leak']++;
                $errors[] = "{$chk['table']}.{$chk['col']} (id={$pkVal}) is stored in unencrypted plaintext.";
                continue;
            }
            try {
                app_decrypt($val, $chk['aad']);
                $encSummary['encrypted_ok']++;
                $meta = app_inspect_envelope($val);
                if ($meta !== null) {
                    $kid = $meta['key_id'];
                    $encSummary['key_ids'][$kid] = ($encSummary['key_ids'][$kid] ?? 0) + 1;
                }
            } catch (Throwable $e) {
                $encSummary['decrypt_failed']++;
                $errors[] = "{$chk['table']}.{$chk['col']} (id={$pkVal}) failed AEAD decryption: " . $e->getMessage();
            }
        }
        mysqli_free_result($res);
    }

    echo sprintf(
        "[VERIFY SCOPE] Passwords checked: %d (Argon2id: %d, Prehashed-Argon2id: %d, Other-Modern: %d, Unsafe: %d)\n",
        $pwSummary['total_rows'],
        $pwSummary['argon2id'],
        $pwSummary['prehashed_legacy'],
        $pwSummary['modern_other'],
        $pwSummary['invalid_or_plain']
    );
    echo sprintf(
        "[VERIFY SCOPE] Tokens checked: %d (SHA-256: %d, Empty/NULL: %d, Unhashed: %d)\n",
        $tokenSummary['total_rows'],
        $tokenSummary['hashed_sha256'],
        $tokenSummary['null_or_empty'],
        $tokenSummary['unhashed']
    );
    $keyDist = $encSummary['key_ids'] !== [] ? json_encode($encSummary['key_ids']) : '{}';
    echo sprintf(
        "[VERIFY SCOPE] PII cells checked: %d across 8 columns (AEAD Verified: %d, Empty/NULL: %d, Plaintext: %d, Failed: %d, Keys: %s)\n",
        $encSummary['total_cells'],
        $encSummary['encrypted_ok'],
        $encSummary['null_or_empty'],
        $encSummary['plaintext_leak'],
        $encSummary['decrypt_failed'],
        $keyDist
    );

    if ($warnings !== []) {
        echo "[VERIFY WARNINGS] " . count($warnings) . " transitional notice(s):\n";
        foreach ($warnings as $w) {
            echo "  - {$w}\n";
        }
    }

    if ($errors !== []) {
        echo "[VERIFY FAILED] Found " . count($errors) . " error(s):\n";
        foreach ($errors as $err) {
            echo "  - {$err}\n";
        }
        return false;
    }

    echo "[VERIFY OK] All scoped password columns, token digests, and AEAD encrypted PII columns passed verification with 0 plaintext leaks.\n";
    return true;
}

function run_rollback(mysqli $con, string $backupFile): void
{
    if (!is_file($backupFile) || !is_readable($backupFile)) {
        throw new RuntimeException("Backup file not found or not readable: {$backupFile}");
    }

    $rawContent = file_get_contents($backupFile);
    if (!is_string($rawContent) || $rawContent === '') {
        throw new RuntimeException("Failed to read backup file: {$backupFile}");
    }

    $outer = json_decode($rawContent, true);
    if (!is_array($outer)) {
        throw new RuntimeException("Invalid JSON in backup file: {$backupFile}");
    }

    if (($outer['format'] ?? '') === 'encrypted_backup_v1' && !empty($outer['ciphertext'])) {
        $decryptedJson = app_decrypt((string) $outer['ciphertext'], 'crypto_backup_snapshot');
        $data = json_decode((string) $decryptedJson, true);
    } else {
        $data = $outer;
    }

    if (!is_array($data) || empty($data['tables']) || !is_array($data['tables'])) {
        throw new RuntimeException("Invalid backup payload structure.");
    }

    $pkMap = [
        'tbl_tkkhachhang' => 'makh',
        'tbl_qlthanhvien' => 'id',
        'tbl_dangnhap'    => 'mavn',
        'tbl_diachi'      => 'id',
        'oder'            => 'id',
    ];

    if (!mysqli_begin_transaction($con)) {
        throw new RuntimeException('Failed to start rollback transaction: ' . mysqli_error($con));
    }

    try {
        foreach ($data['tables'] as $table => $rows) {
            if (!isset($pkMap[$table]) || !is_array($rows)) {
                continue;
            }
            $pk = $pkMap[$table];
            foreach ($rows as $row) {
                if (!isset($row[$pk])) {
                    continue;
                }
                $pkVal = $row[$pk];
                $sets = [];
                $vals = [];
                $types = '';
                foreach ($row as $col => $val) {
                    if ($col === $pk) {
                        continue;
                    }
                    $sets[] = "`{$col}` = ?";
                    $vals[] = $val;
                    $types .= 's';
                }
                $vals[] = (int) $pkVal;
                $types .= 'i';
                $sql = sprintf("UPDATE `%s` SET %s WHERE `%s` = ?", $table, implode(', ', $sets), $pk);
                db_exec_prepared_or_fail($con, $sql, $types, $vals, "Rollback {$table} ({$pk}={$pkVal})");
            }
        }
        if (!mysqli_commit($con)) {
            throw new RuntimeException('Failed to commit rollback transaction: ' . mysqli_error($con));
        }
        echo "[ROLLBACK OK] Restored records from {$backupFile}.\n";
    } catch (Throwable $e) {
        mysqli_rollback($con);
        throw $e;
    }
}

$options = parse_cli_options($argv);

if (array_key_exists('help', $options)) {
    print_cli_help();
    exit(0);
}

$con = app_database_connection();
$dryRun = array_key_exists('dry-run', $options);

if (array_key_exists('prune-backups', $options)) {
    $keepVal = $options['prune-backups'];
    $keepCount = ($keepVal === true || $keepVal === '') ? 0 : max(0, (int) $keepVal);
    $deleted = prune_database_backups($keepCount);
    echo "[BACKUP PRUNE OK] Securely wiped and deleted {$deleted} backup file(s) (kept {$keepCount}).\n";
}

if (array_key_exists('backup', $options)) {
    $file = create_database_backup($con);
    echo "[BACKUP OK] Saved encrypted backup to {$file}\n";
}

if (array_key_exists('rollback', $options)) {
    run_rollback($con, (string) $options['rollback']);
}

if (array_key_exists('migrate', $options)) {
    $stats = run_migration($con, $dryRun);
    $prefix = $dryRun ? '[DRY-RUN MIGRATE]' : '[MIGRATE OK]';
    echo sprintf(
        "%s Plaintext passwords -> Argon2id: %d | Legacy hashes -> Prehashed-Argon2id: %d | Already Argon2id: %d | Tokens hashed: %d | Fields encrypted: %d\n",
        $prefix,
        $stats['passwords_upgraded_plaintext'],
        $stats['passwords_wrapped_legacy'],
        $stats['passwords_already_argon2id'],
        $stats['tokens_hashed'],
        $stats['fields_encrypted']
    );
}

if (array_key_exists('rotate-key', $options) || array_key_exists('rotate-keys', $options)) {
    $rawTarget = $options['rotate-key'] ?? $options['rotate-keys'];
    if ($rawTarget === true || $rawTarget === '') {
        $targetKey = app_get_keyring()['active_key_id'];
    } else {
        $targetKey = (string) $rawTarget;
    }
    $count = run_key_rotation($con, $targetKey, $dryRun);
    $prefix = $dryRun ? '[DRY-RUN ROTATE]' : '[ROTATE OK]';
    echo "{$prefix} Re-encrypted {$count} field(s) to key ID '{$targetKey}'.\n";
}

if (array_key_exists('verify', $options)) {
    if (!run_verification($con)) {
        exit(1);
    }
}
