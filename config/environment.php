<?php

declare(strict_types=1);

use App\Support\Environment;

require_once __DIR__ . '/require-env.php';

require_env('APP_ENV');

$adminCorePath = (string) ($_ENV['ADMIN_CORE_PATH'] ?? $_ENV['BACKEND_PATH'] ?? '');
if ($adminCorePath === '') {
    $adminCorePath = dirname(__DIR__, 2) . '/bifrost-admin-core';
}

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
    'admin_core' => [
        'path' => $adminCorePath,
        'dotenv' => $_ENV['ADMIN_CORE_DOTENV'] ?? $_ENV['BACKEND_DOTENV'] ?? '.env',
    ],
    // Bakoverkompatibilitet for quality-scripts som fortsatt leser environment.backend.*
    'backend' => [
        'path' => $adminCorePath,
        'dotenv' => $_ENV['ADMIN_CORE_DOTENV'] ?? $_ENV['BACKEND_DOTENV'] ?? '.env',
    ],
];
