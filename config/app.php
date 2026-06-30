<?php

require_once __DIR__ . '/require-env.php';

require_env('APP_ENV');
require_env('APP_DEBUG');

$baseUrl = trim((string) ($_ENV['APP_BASE_URL'] ?? $_ENV['APP_URL'] ?? ''));
if ($baseUrl === '') {
    throw new RuntimeException(
        'Manglende APP_BASE_URL eller APP_URL. Se .env.local-dev.example.',
    );
}

return [
    'name' => $_ENV['APP_NAME'] ?? 'Bifrost',
    'env' => $_ENV['APP_ENV'],
    'base_url' => rtrim($baseUrl, '/'),
    'debug' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
];
