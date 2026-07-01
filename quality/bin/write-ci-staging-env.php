#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Skriv .env.staging for public-ui og bifrost-backend fra miljøvariabler (CI).
 *
 * Krever: STAGING_DB_HOST, STAGING_DB_USER, STAGING_DB_PASS
 * Valgfri: STAGING_DB_NAME (default bifrost_quality_staging)
 */

$root = dirname(__DIR__, 2);
$backendPath = $root . DIRECTORY_SEPARATOR . 'bifrost-backend';

$host = trim((string) (getenv('STAGING_DB_HOST') ?: ''));
$user = trim((string) (getenv('STAGING_DB_USER') ?: ''));
$pass = (string) (getenv('STAGING_DB_PASS') ?: '');
$dbName = trim((string) (getenv('STAGING_DB_NAME') ?: 'bifrost_quality_staging'));

foreach (['STAGING_DB_HOST' => $host, 'STAGING_DB_USER' => $user] as $label => $value) {
    if ($value === '') {
        fwrite(STDERR, "Mangler {$label} (sett som GitHub repository secret).\n");
        exit(1);
    }
}

if (!is_dir($backendPath)) {
    fwrite(STDERR, "Mangler bifrost-backend/ ved {$backendPath} (checkout sibling repo i CI).\n");
    exit(1);
}

$publicUiEnv = <<<ENV
APP_ENV=staging
APP_DEBUG=false
APP_NAME=Bifrost
APP_BASE_URL=https://staging.jaktfeltcup.no

BACKEND_API_URL=https://staging.api.bifrostevents.no
BACKEND_PATH=bifrost-backend
BACKEND_DOTENV=.env.staging

MAIL_MODE=off
PAYMENT_MODE=test
ALLOW_WRITES=true
ROBOTS_MODE=noindex
QUALITY_RESET_DATABASE=true
QUALITY_SEED_DATABASE=true
ENV;

$dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $dbName);
$backendEnv = <<<ENV
APP_ENV=staging
APP_DEBUG=false
APP_NAME=Bifrost API

STORAGE_DRIVER=pdo
DB_DSN={$dsn}
DB_USER={$user}
DB_PASS={$pass}
ENV;

$publicFile = $root . DIRECTORY_SEPARATOR . '.env.staging';
$backendFile = $backendPath . DIRECTORY_SEPARATOR . '.env.staging';

file_put_contents($publicFile, $publicUiEnv);
file_put_contents($backendFile, $backendEnv);

echo "Skrev {$publicFile}\n";
echo "Skrev {$backendFile}\n";
echo "Database: {$dbName} @ {$host}\n";
