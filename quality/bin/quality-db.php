#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Quality database-kommandoer med miljøsperrer.
 *
 * Bruk:
 *   set BIFROST_DOTENV=.env.local-quality
 *   php quality/bin/quality-db.php status
 *   php quality/bin/quality-db.php reset
 *   php quality/bin/quality-db.php migrate
 *   php quality/bin/quality-db.php seed
 *   php quality/bin/quality-db.php prepare
 *
 * Production og test nektes alltid for reset/seed/migrate/prepare.
 */

use App\Support\Config;
use App\Support\DatabaseResetGuard;
use App\Support\Environment;

$basePath = dirname(__DIR__, 2);

require_once $basePath . '/vendor/autoload.php';
require_once $basePath . '/app/06-support/EnvLoader.php';
\App\Support\EnvLoader::load($basePath);

\App\Support\Config::load('app');
\App\Support\Config::load('environment');

require_once dirname(__DIR__) . '/support/backend-env.php';

$command = $argv[1] ?? 'status';

function quality_db_config(): array
{
    return quality_db_config_from_backend();
}

function quality_mysql_bin(): string
{
    $candidates = [
        getenv('MYSQL_BIN') ?: '',
        'C:\\xampp\\mysql\\bin\\mysql.exe',
        'mysql',
    ];

    foreach ($candidates as $bin) {
        if ($bin !== '' && (is_file($bin) || quality_command_exists($bin))) {
            return $bin;
        }
    }

    throw new RuntimeException('Fant ikke mysql-klient. Sett MYSQL_BIN eller legg XAMPP mysql i PATH.');
}

function quality_command_exists(string $command): bool
{
    $where = stripos(PHP_OS_FAMILY, 'Windows') === 0 ? 'where' : 'command -v';
    $result = shell_exec($where . ' ' . escapeshellarg($command) . ' 2>NUL');

    return is_string($result) && trim($result) !== '';
}

function quality_run_mysql(array $db, string $sql, ?string $database = null): void
{
    $bin = quality_mysql_bin();
    $args = [
        escapeshellarg($bin),
        '-h', escapeshellarg($db['host']),
        '-u', escapeshellarg($db['user']),
    ];
    if ($db['pass'] !== '') {
        $args[] = '-p' . escapeshellarg($db['pass']);
    }
    if ($database !== null) {
        $args[] = escapeshellarg($database);
    }
    $args[] = '-e';
    $args[] = escapeshellarg($sql);

    $cmd = implode(' ', $args);
    passthru($cmd, $exitCode);
    if ($exitCode !== 0) {
        throw new RuntimeException('mysql-kommando feilet (exit ' . $exitCode . ').');
    }
}

function quality_run_mysql_file(array $db, string $file, string $database): void
{
    if (!is_file($file)) {
        throw new RuntimeException('Mangler SQL-fil: ' . $file);
    }

    $bin = quality_mysql_bin();
    $args = [
        escapeshellarg($bin),
        '-h', escapeshellarg($db['host']),
        '-u', escapeshellarg($db['user']),
    ];
    if ($db['pass'] !== '') {
        $args[] = '-p' . escapeshellarg($db['pass']);
    }
    $args[] = escapeshellarg($database);

    $redirect = stripos(PHP_OS_FAMILY, 'Windows') === 0
        ? ' < ' . escapeshellarg($file)
        : ' < ' . escapeshellarg($file);

    $cmd = implode(' ', $args) . $redirect;
    passthru($cmd, $exitCode);
    if ($exitCode !== 0) {
        throw new RuntimeException('mysql import feilet for ' . $file);
    }
}

function quality_backend_path(): string
{
    return quality_backend_path_from_config();
}

function quality_run_backend_migrate(bool $greenfield): void
{
    $backendPath = quality_backend_path();
    $dotenv = (string) Config::get('environment.backend.dotenv', '.env');
    $console = $backendPath . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'console';

    if (!is_file($console)) {
        throw new RuntimeException('Mangler backend console: ' . $console);
    }

    putenv('BIFROST_DOTENV=' . $dotenv);
    $_ENV['BIFROST_DOTENV'] = $dotenv;
    $_SERVER['BIFROST_DOTENV'] = $dotenv;

    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($console)
        . ' migrate' . ($greenfield ? ' --greenfield' : '');
    passthru($cmd, $exitCode);
    if ($exitCode !== 0) {
        throw new RuntimeException('backend migrate feilet.');
    }
}

function quality_shared_seeds_path(): string
{
    $backendPath = quality_backend_path();
    $seeds = realpath($backendPath . '/../bifrost-shared/database/seeds');
    if ($seeds === false) {
        throw new RuntimeException('Fant ikke bifrost-shared/database/seeds');
    }

    return $seeds;
}

function quality_migrations_path(): string
{
    $backendPath = quality_backend_path();
    $migrations = realpath($backendPath . '/../bifrost-shared/database/migrations');
    if ($migrations === false) {
        throw new RuntimeException('Fant ikke bifrost-shared/database/migrations');
    }

    return $migrations;
}

function quality_jaktfelt_migrations_path(): string
{
    $backendPath = quality_backend_path();
    $candidates = [
        $backendPath . '/../../main-projects/jaktfeltnamdalen/database/migrations',
        $backendPath . '/../main-projects/jaktfeltnamdalen/database/migrations',
    ];

    foreach ($candidates as $candidate) {
        $path = realpath($candidate);
        if ($path !== false && is_dir($path)) {
            return $path;
        }
    }

    throw new RuntimeException(
        'Fant ikke jaktfeltnamdalen/database/migrations (forventet under main-projects/jaktfeltnamdalen).',
    );
}

function quality_ensure_schema_migrations_table(array $db): void
{
    quality_run_mysql(
        $db,
        'CREATE TABLE IF NOT EXISTS schema_migrations (migration VARCHAR(255) PRIMARY KEY, applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        $db['name'],
    );
}

function quality_record_migration(array $db, string $name): void
{
    $escaped = str_replace("'", "''", $name);
    quality_run_mysql(
        $db,
        "INSERT IGNORE INTO schema_migrations (migration) VALUES ('{$escaped}')",
        $db['name'],
    );
}

/**
 * Bifrost additive migreringer (auth_*, bifrost_*) — ikke greenfield 001_initial eller prod-backfill.
 */
function quality_is_bifrost_additive_migration(string $name): bool
{
    if ($name === '001_initial_bifrost_schema.sql') {
        return false;
    }
    if (str_contains($name, 'backfill')) {
        return false;
    }
    if (str_starts_with($name, 'auth_')) {
        return true;
    }
    if (str_starts_with($name, 'bifrost_')) {
        return true;
    }

    return false;
}

function quality_applied_migrations(array $db): array
{
    quality_ensure_schema_migrations_table($db);

    $bin = quality_mysql_bin();
    $args = [
        escapeshellarg($bin),
        '-h', escapeshellarg($db['host']),
        '-u', escapeshellarg($db['user']),
        '-N',
        '-B',
    ];
    if ($db['pass'] !== '') {
        $args[] = '-p' . escapeshellarg($db['pass']);
    }
    $args[] = escapeshellarg($db['name']);
    $args[] = '-e';
    $args[] = escapeshellarg('SELECT migration FROM schema_migrations');

    $cmd = implode(' ', $args);
    $output = shell_exec($cmd . ' 2>&1');
    if (!is_string($output)) {
        return [];
    }

    $applied = [];
    foreach (preg_split('/\R/', trim($output)) ?: [] as $line) {
        $name = trim($line);
        if ($name !== '' && !str_starts_with($name, 'ERROR')) {
            $applied[$name] = true;
        }
    }

    return $applied;
}

function quality_run_jaktfelt_migrations(): void
{
    $db = quality_db_config();
    $applied = quality_applied_migrations($db);

    $migrationsDir = quality_jaktfelt_migrations_path();
    $files = glob($migrationsDir . DIRECTORY_SEPARATOR . '*.sql') ?: [];
    sort($files, SORT_NATURAL);

    foreach ($files as $file) {
        $name = basename($file);
        if (isset($applied[$name])) {
            echo "Hoppet over (allerede kjørt): $name\n";
            continue;
        }

        echo "Jaktfelt-migrering: $name\n";
        quality_run_mysql_file($db, $file, $db['name']);
        quality_record_migration($db, $name);
    }
}

function quality_align_jaktfelt_auth_for_bifrost(): void
{
    $db = quality_db_config();
    $applied = quality_applied_migrations($db);
    if (!isset($applied['v2_000_auth_shared_schema_stub.sql'])) {
        return;
    }

    $marker = '__quality_jaktfelt_auth_bifrost_align';
    if (isset($applied[$marker])) {
        return;
    }

    echo "Tilpasser jaktfelt auth-stub (INT id) for Bifrost FK …\n";
    foreach ([
        'ALTER TABLE auth_users MODIFY id INT NOT NULL AUTO_INCREMENT',
        'ALTER TABLE auth_roles MODIFY id INT NOT NULL AUTO_INCREMENT',
        'ALTER TABLE auth_applications MODIFY id INT NOT NULL AUTO_INCREMENT',
    ] as $sql) {
        quality_run_mysql($db, $sql, $db['name']);
    }
    quality_record_migration($db, $marker);
}

function quality_bootstrap_bifrost_auth_applications(): void
{
    $db = quality_db_config();
    quality_run_mysql(
        $db,
        "INSERT IGNORE INTO auth_applications (name) VALUES ('bifrost-admin'), ('bifrost-arrangor'), ('bifrost-public')",
        $db['name'],
    );
}

function quality_run_bifrost_additive_migrations(): void
{
    $db = quality_db_config();
    quality_align_jaktfelt_auth_for_bifrost();
    $applied = quality_applied_migrations($db);

    $migrationsDir = quality_migrations_path();
    $files = glob($migrationsDir . DIRECTORY_SEPARATOR . '*.sql') ?: [];
    sort($files, SORT_NATURAL);

    foreach ($files as $file) {
        $name = basename($file);
        if (!quality_is_bifrost_additive_migration($name)) {
            echo "Hopper over (ikke additiv): $name\n";
            continue;
        }
        if (isset($applied[$name])) {
            echo "Hoppet over (allerede kjørt): $name\n";
            continue;
        }
        if ($name === 'auth_001_core_schema.sql' && isset($applied['v2_000_auth_shared_schema_stub.sql'])) {
            echo "Hopper over auth_001 (jaktfelt auth-stub dekker auth_* for quality)\n";
            quality_bootstrap_bifrost_auth_applications();
            quality_record_migration($db, $name);
            continue;
        }

        echo "Bifrost additiv: $name\n";
        quality_run_mysql_file($db, $file, $db['name']);
        quality_record_migration($db, $name);
    }
}

function quality_print_status(): void
{
    $db = quality_db_config();
    $backendEnv = quality_backend_dotenv_name();
    echo "APP_ENV (public-ui): " . Environment::current() . PHP_EOL;
    echo "Backend env: " . $backendEnv . PHP_EOL;
    echo "Database (backend): {$db['name']} @ {$db['host']}" . PHP_EOL;
    echo "canReset: " . (DatabaseResetGuard::canReset() ? 'yes' : 'no') . PHP_EOL;
    echo "canSeed: " . (DatabaseResetGuard::canSeed() ? 'yes' : 'no') . PHP_EOL;
    echo "allowsWrites: " . (Environment::allowsWrites() ? 'yes' : 'no') . PHP_EOL;
}

function quality_run_subcommand(string $subcommand): int
{
    $dotenv = getenv('BIFROST_DOTENV') ?: ($_ENV['BIFROST_DOTENV'] ?? '');
    $php = escapeshellarg(PHP_BINARY);
    $script = escapeshellarg(__FILE__);

    if (stripos(PHP_OS_FAMILY, 'Windows') === 0) {
        $cmd = $dotenv !== ''
            ? 'set BIFROST_DOTENV=' . $dotenv . ' && ' . $php . ' ' . $script . ' ' . $subcommand
            : $php . ' ' . $script . ' ' . $subcommand;
    } else {
        $prefix = $dotenv !== '' ? 'BIFROST_DOTENV=' . escapeshellarg($dotenv) . ' ' : '';
        $cmd = $prefix . $php . ' ' . $script . ' ' . $subcommand;
    }

    passthru($cmd, $exitCode);

    return (int) $exitCode;
}

try {
    switch ($command) {
        case 'status':
            quality_print_status();
            exit(0);

        case 'reset':
            DatabaseResetGuard::assertResetAllowed();
            $db = quality_db_config();
            if ($db['name'] === '') {
                throw new RuntimeException('Database-navn er tomt i backend DB_DSN.');
            }
            if (in_array($db['name'], ['jaktfeltkarusell_prod', 'bifrost'], true)) {
                throw new RuntimeException(
                    'Sikkerhetsstopp: nekter reset av database "' . $db['name'] . '". Bruk bifrost_quality_local.',
                );
            }
            echo "Dropper og oppretter database {$db['name']} …\n";
            quality_run_mysql($db, 'DROP DATABASE IF EXISTS `' . str_replace('`', '``', $db['name']) . '`');
            quality_run_mysql($db, 'CREATE DATABASE `' . str_replace('`', '``', $db['name']) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            echo "Database klar.\n";
            exit(0);

        case 'migrate':
            DatabaseResetGuard::assertMigrateAllowed();
            echo "Kjører jaktfeltnamdalen-migreringer via mysql …\n";
            quality_run_jaktfelt_migrations();
            echo "Kjører Bifrost additive migreringer …\n";
            quality_run_bifrost_additive_migrations();
            echo "Migrate fullført.\n";
            exit(0);

        case 'seed':
            DatabaseResetGuard::assertSeedAllowed();
            $db = quality_db_config();
            $seeds = quality_shared_seeds_path();
            $files = [
                '001_local_tenants.sql',
                '001_local_jaktfelt_cup_data.sql',
                '002_local_admin_user.sql',
                '003_quality_local_hosts.sql',
                '004_quality_competition_fixtures.sql',
            ];
            foreach ($files as $file) {
                $path = $seeds . DIRECTORY_SEPARATOR . $file;
                if (!is_file($path)) {
                    echo "Hopper over (finnes ikke): $file\n";
                    continue;
                }
                echo "Seeder: $file\n";
                quality_run_mysql_file($db, $path, $db['name']);
            }
            echo "Seed fullført.\n";
            exit(0);

        case 'prepare':
            DatabaseResetGuard::assertResetAllowed();
            DatabaseResetGuard::assertSeedAllowed();
            $code = quality_run_subcommand('reset');
            if ($code !== 0) {
                exit($code);
            }
            $code = quality_run_subcommand('migrate');
            if ($code !== 0) {
                exit($code);
            }
            exit(quality_run_subcommand('seed'));

        default:
            fwrite(STDERR, "Ukjent kommando: $command\n");
            fwrite(STDERR, "Bruk: status | reset | migrate | seed | prepare\n");
            exit(1);
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'Feil: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
