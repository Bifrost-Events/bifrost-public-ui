#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Quality database-kommandoer med miljøsperrer (mot bifrost-admin-core).
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

function quality_admin_core_path(): string
{
    return quality_backend_path_from_config();
}

/**
 * @param array<string, string> $extraEnv
 */
function quality_run_admin_core_console(string $subcommand, array $extraEnv = []): void
{
    $corePath = quality_admin_core_path();
    $dotenv = quality_backend_dotenv_name();
    $console = $corePath . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'console';

    if (!is_file($console)) {
        throw new RuntimeException('Mangler admin-core console: ' . $console);
    }

    putenv('BIFROST_DOTENV=' . $dotenv);
    $_ENV['BIFROST_DOTENV'] = $dotenv;
    $_SERVER['BIFROST_DOTENV'] = $dotenv;

    foreach ($extraEnv as $key => $value) {
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    $php = escapeshellarg(PHP_BINARY);
    $consoleArg = escapeshellarg($console);
    $subArg = escapeshellarg($subcommand);

    // Explicit env on the command line — putenv alone is unreliable for child PHP on Windows.
    $envPairs = array_merge(['BIFROST_DOTENV' => $dotenv], $extraEnv);
    if (stripos(PHP_OS_FAMILY, 'Windows') === 0) {
        $prefix = '';
        foreach ($envPairs as $key => $value) {
            $prefix .= 'set ' . $key . '=' . $value . '&& ';
        }
        $cmd = $prefix . $php . ' ' . $consoleArg . ' ' . $subArg;
    } else {
        $prefix = '';
        foreach ($envPairs as $key => $value) {
            $prefix .= $key . '=' . escapeshellarg($value) . ' ';
        }
        $cmd = $prefix . $php . ' ' . $consoleArg . ' ' . $subArg;
    }

    $cwd = getcwd();
    chdir($corePath);
    passthru($cmd, $exitCode);
    if ($cwd !== false) {
        chdir($cwd);
    }
    if ($exitCode !== 0) {
        throw new RuntimeException("admin-core {$subcommand} feilet.");
    }
}

function quality_minimal_seeds_path(): string
{
    $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seeds';
    if (!is_dir($path)) {
        throw new RuntimeException('Mangler quality seed-mappe: ' . $path);
    }

    return $path;
}

function quality_events_path(): string
{
    $corePath = quality_admin_core_path();
    $candidate = realpath($corePath . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'events');
    if ($candidate === false || !is_dir($candidate)) {
        throw new RuntimeException('Fant ikke events-modul under admin-core/modules/events.');
    }

    return $candidate;
}

function quality_run_events_console(string $subcommand): void
{
    $eventsPath = quality_events_path();
    $corePath = quality_admin_core_path();
    $dotenv = '.env.local-quality';
    $console = $eventsPath . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'console';

    if (!is_file($console)) {
        throw new RuntimeException('Mangler events console: ' . $console);
    }

    $moduleDotenv = $eventsPath . DIRECTORY_SEPARATOR . $dotenv;
    $coreDotenv = $corePath . DIRECTORY_SEPARATOR . $dotenv;
    if (!is_file($moduleDotenv) && !is_file($coreDotenv)) {
        throw new RuntimeException(
            'Mangler ' . $dotenv . ' i admin-core eller modules/events (kopier fra .env.local-quality.example).',
        );
    }

    $php = escapeshellarg(PHP_BINARY);
    $consoleArg = escapeshellarg($console);
    $subArg = escapeshellarg($subcommand);

    if (stripos(PHP_OS_FAMILY, 'Windows') === 0) {
        $cmd = 'set BIFROST_DOTENV=' . $dotenv . '&& ' . $php . ' ' . $consoleArg . ' ' . $subArg;
    } else {
        $cmd = 'BIFROST_DOTENV=' . escapeshellarg($dotenv) . ' ' . $php . ' ' . $consoleArg . ' ' . $subArg;
    }

    $cwd = getcwd();
    chdir($eventsPath);
    passthru($cmd, $exitCode);
    if ($cwd !== false) {
        chdir($cwd);
    }
    if ($exitCode !== 0) {
        throw new RuntimeException("events-modul {$subcommand} feilet.");
    }
}

function quality_print_status(): void
{
    $db = quality_db_config();
    $backendEnv = quality_backend_dotenv_name();
    echo 'APP_ENV (public-ui): ' . Environment::current() . PHP_EOL;
    echo 'Admin-core env: ' . $backendEnv . PHP_EOL;
    echo "Database (admin-core): {$db['name']} @ {$db['host']}" . PHP_EOL;
    echo 'Admin-core path: ' . quality_admin_core_path() . PHP_EOL;
    echo 'Events path: ' . quality_events_path() . PHP_EOL;
    echo 'Quality seeds: ' . quality_minimal_seeds_path() . PHP_EOL;
    echo 'canReset: ' . (DatabaseResetGuard::canReset() ? 'yes' : 'no') . PHP_EOL;
    echo 'canSeed: ' . (DatabaseResetGuard::canSeed() ? 'yes' : 'no') . PHP_EOL;
    echo 'allowsWrites: ' . (Environment::allowsWrites() ? 'yes' : 'no') . PHP_EOL;
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
                throw new RuntimeException('Database-navn er tomt i admin-core DB_DSN.');
            }
            if (in_array($db['name'], ['jaktfeltkarusell_prod', 'bifrost', 'bifrost_admin_core'], true)) {
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
            echo "Kjører bifrost-admin-core migrate …\n";
            quality_run_admin_core_console('migrate');
            echo "Kjører events-modul migrate …\n";
            quality_run_events_console('migrate');
            echo "Migrate fullført.\n";
            exit(0);

        case 'seed':
            DatabaseResetGuard::assertSeedAllowed();
            // Kun grunndata for staging: standardroller + én admin-bruker.
            // Organisasjoner, cuper, stevner m.m. opprettes via UI i staging-tester.
            // Events-demo-seeds kjøres ikke.
            $seedsPath = str_replace('\\', '/', quality_minimal_seeds_path());
            echo "Kjører minimal quality-seed (SEEDS_PATH={$seedsPath}) …\n";
            quality_run_admin_core_console('seed', ['SEEDS_PATH' => $seedsPath]);
            echo "Seed fullført (roller + quality-admin).\n";
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
