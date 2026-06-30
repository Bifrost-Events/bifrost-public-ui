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

/**
 * Greenfield-migrering via mysql-klient (mer robust enn PHP split på Windows/MariaDB).
 * Hopper over prod-backfill-filer (krever jaktfelt_*-data).
 */
function quality_is_greenfield_migration(string $name): bool
{
    if ($name === '001_initial_bifrost_schema.sql') {
        return true;
    }
    if (str_starts_with($name, 'auth_')) {
        return true;
    }
    if (str_starts_with($name, 'bifrost_') && !str_contains($name, 'backfill')) {
        return true;
    }

    return false;
}

function quality_run_greenfield_migrations(): void
{
    $db = quality_db_config();
    $migrationsDir = quality_migrations_path();
    $files = glob($migrationsDir . DIRECTORY_SEPARATOR . '*.sql') ?: [];
    sort($files, SORT_NATURAL);

    foreach ($files as $file) {
        $name = basename($file);
        if (!quality_is_greenfield_migration($name)) {
            echo "Hopper over migrering (backfill/ikke greenfield): $name\n";
            continue;
        }

        echo "Migrerer: $name\n";
        quality_run_mysql_file($db, $file, $db['name']);
        $escaped = str_replace("'", "''", $name);
        quality_run_mysql(
            $db,
            "INSERT IGNORE INTO schema_migrations (migration) VALUES ('{$escaped}')",
            $db['name'],
        );
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
            echo "Kjører greenfield-migreringer via mysql …\n";
            quality_run_greenfield_migrations();
            echo "Migrate fullført.\n";
            exit(0);

        case 'seed':
            DatabaseResetGuard::assertSeedAllowed();
            $db = quality_db_config();
            $seeds = quality_shared_seeds_path();
            $files = [
                '001_local_tenants.sql',
                '001_local_greenfield_cup_data.sql',
                '002_local_admin_user.sql',
                '003_quality_local_hosts.sql',
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
