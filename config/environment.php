<?php

declare(strict_types=1);

use App\Support\Environment;

require_once __DIR__ . '/require-env.php';

require_env('APP_ENV');

return [
    'profile' => Environment::current(),
    'mail_mode' => Environment::mailMode(),
    'payment_mode' => Environment::paymentMode(),
    'allow_writes' => Environment::allowsWrites(),
    'robots_mode' => Environment::robotsMode(),
    'quality_reset_database' => Environment::qualityResetDatabaseRequested(),
    'quality_seed_database' => Environment::qualitySeedDatabaseRequested(),
    'allows_database_reset' => Environment::allowsDatabaseReset(),
    'allows_database_seed' => Environment::allowsDatabaseSeed(),
    'backend' => [
        'api_base_url' => rtrim((string) ($_ENV['BACKEND_API_URL'] ?? ''), '/'),
        'path' => $_ENV['BACKEND_PATH'] ?? dirname(__DIR__, 2) . '/bifrost-backend',
        'dotenv' => $_ENV['BACKEND_DOTENV'] ?? '.env',
    ],
];
