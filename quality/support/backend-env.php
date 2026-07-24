<?php

declare(strict_types=1);

/**
 * Leser databasekonfigurasjon fra bifrost-admin-core sitt .env (ikke public-ui).
 * Preferer ADMIN_CORE_PATH / ADMIN_CORE_DOTENV; aksepterer også legacy BACKEND_*.
 */

function quality_backend_path_from_config(): string
{
    $path = (string) \App\Support\Config::get('environment.admin_core.path', '');
    if ($path === '') {
        $path = (string) \App\Support\Config::get('environment.backend.path', '');
    }
    if ($path === '' || !is_dir($path)) {
        throw new RuntimeException('Ugyldig ADMIN_CORE_PATH (forventet bifrost-admin-core): ' . $path);
    }

    return realpath($path) ?: $path;
}

function quality_backend_dotenv_name(): string
{
    $name = (string) \App\Support\Config::get('environment.admin_core.dotenv', '');
    if ($name === '') {
        $name = (string) \App\Support\Config::get('environment.backend.dotenv', '.env');
    }

    return $name !== '' ? $name : '.env';
}

/**
 * @return array<string, string>
 */
function quality_load_backend_env(): array
{
    $backendPath = quality_backend_path_from_config();
    $envFile = $backendPath . DIRECTORY_SEPARATOR . basename(quality_backend_dotenv_name());

    if (!is_file($envFile)) {
        throw new RuntimeException(
            'Mangler admin-core env-fil: ' . $envFile . ' (sjekk ADMIN_CORE_DOTENV i public-ui)',
        );
    }

    $vars = [];
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        $vars[$name] = $value;
    }

    return $vars;
}

/**
 * @return array{host: string, name: string, user: string, pass: string, dsn: string}
 */
function quality_db_config_from_backend(): array
{
    $env = quality_load_backend_env();
    $dsn = (string) ($env['DB_DSN'] ?? '');
    if ($dsn === '') {
        throw new RuntimeException('DB_DSN mangler i admin-core env (' . quality_backend_dotenv_name() . ')');
    }

    $host = '127.0.0.1';
    $name = '';
    foreach (explode(';', $dsn) as $part) {
        if (str_contains($part, '=')) {
            [$key, $value] = explode('=', $part, 2);
            $key = strtolower(trim($key));
            $value = trim($value);
            if ($key === 'host') {
                $host = $value;
            }
            if ($key === 'dbname') {
                $name = $value;
            }
        }
    }

    if ($name === '') {
        throw new RuntimeException('Kunne ikke lese dbname fra DB_DSN: ' . $dsn);
    }

    return [
        'host' => $host,
        'name' => $name,
        'user' => (string) ($env['DB_USER'] ?? 'root'),
        'pass' => (string) ($env['DB_PASS'] ?? ''),
        'dsn' => $dsn,
    ];
}
