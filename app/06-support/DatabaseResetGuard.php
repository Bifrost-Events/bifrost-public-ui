<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Sperrer destruktive database-operasjoner utenfor tillatte miljøer.
 * Production nektes alltid – uavhengig av QUALITY_RESET_DATABASE.
 */
final class DatabaseResetGuard
{
    public static function assertResetAllowed(): void
    {
        if (Environment::isProduction()) {
            throw new \RuntimeException(
                'Database-reset er forbudt når APP_ENV=production.',
            );
        }

        if (Environment::isTest()) {
            throw new \RuntimeException(
                'Database-reset er forbudt når APP_ENV=test.',
            );
        }

        if (!Environment::isLocalQuality() && !Environment::isStaging()) {
            throw new \RuntimeException(
                'Database-reset er kun tillatt for APP_ENV=local-quality eller staging.',
            );
        }

        if (!Environment::qualityResetDatabaseRequested()) {
            throw new \RuntimeException(
                'Database-reset krever QUALITY_RESET_DATABASE=true.',
            );
        }
    }

    public static function assertSeedAllowed(): void
    {
        if (Environment::isProduction()) {
            throw new \RuntimeException(
                'Database-seed er forbudt når APP_ENV=production.',
            );
        }

        if (Environment::isTest()) {
            throw new \RuntimeException(
                'Database-seed er forbudt når APP_ENV=test.',
            );
        }

        if (!Environment::isLocalQuality() && !Environment::isStaging()) {
            throw new \RuntimeException(
                'Automatisk seed er kun tillatt for APP_ENV=local-quality eller staging.',
            );
        }

        if (!Environment::qualitySeedDatabaseRequested()) {
            throw new \RuntimeException(
                'Database-seed krever QUALITY_SEED_DATABASE=true.',
            );
        }
    }

    public static function assertMigrateAllowed(): void
    {
        if (Environment::isProduction()) {
            throw new \RuntimeException(
                'Automatisk migrate via quality-scripts er forbudt når APP_ENV=production.',
            );
        }

        if (Environment::isTest()) {
            throw new \RuntimeException(
                'Automatisk migrate via quality-scripts er forbudt når APP_ENV=test.',
            );
        }

        if (!Environment::isLocalQuality() && !Environment::isStaging()) {
            throw new \RuntimeException(
                'Automatisk migrate via quality-scripts er kun tillatt for local-quality og staging.',
            );
        }
    }

    public static function canReset(): bool
    {
        try {
            self::assertResetAllowed();

            return true;
        } catch (\RuntimeException) {
            return false;
        }
    }

    public static function canSeed(): bool
    {
        try {
            self::assertSeedAllowed();

            return true;
        } catch (\RuntimeException) {
            return false;
        }
    }
}
