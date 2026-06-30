<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Applikasjonsmiljøprofiler for Bifrost public-ui.
 *
 * Profiler: local-dev, local-quality, test, staging, production
 * Legacy: development → behandles som local-dev
 */
final class Environment
{
    public const LOCAL_DEV = 'local-dev';
    public const LOCAL_QUALITY = 'local-quality';
    public const TEST = 'test';
    public const STAGING = 'staging';
    public const PRODUCTION = 'production';

    /** @var list<string> */
    private const RESETTABLE = [self::LOCAL_QUALITY, self::STAGING];

    public static function current(): string
    {
        $env = strtolower(trim((string) ($_ENV['APP_ENV'] ?? self::PRODUCTION)));

        return match ($env) {
            'development' => self::LOCAL_DEV,
            default => $env,
        };
    }

    public static function isLocalDev(): bool
    {
        return self::current() === self::LOCAL_DEV;
    }

    public static function isLocalQuality(): bool
    {
        return self::current() === self::LOCAL_QUALITY;
    }

    public static function isTest(): bool
    {
        return self::current() === self::TEST;
    }

    public static function isStaging(): bool
    {
        return self::current() === self::STAGING;
    }

    public static function isProduction(): bool
    {
        return self::current() === self::PRODUCTION;
    }

    /** Utviklingslignende miljø (dev-banner, verbose feil). */
    public static function isDevelopment(): bool
    {
        return self::isLocalDev() || self::isLocalQuality();
    }

    public static function isCloud(): bool
    {
        return self::isTest() || self::isStaging() || self::isProduction();
    }

    public static function allowsWrites(): bool
    {
        return filter_var($_ENV['ALLOW_WRITES'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
    }

    public static function qualityResetDatabaseRequested(): bool
    {
        return filter_var($_ENV['QUALITY_RESET_DATABASE'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
    }

    public static function qualitySeedDatabaseRequested(): bool
    {
        return filter_var($_ENV['QUALITY_SEED_DATABASE'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Om automatisk database-reset er tillatt (sperret i production og test).
     */
    public static function allowsDatabaseReset(): bool
    {
        if (self::isProduction() || self::isTest()) {
            return false;
        }

        if (!in_array(self::current(), self::RESETTABLE, true)) {
            return false;
        }

        return self::qualityResetDatabaseRequested();
    }

    /**
     * Om automatisk seed er tillatt (sperret i production; test kun ved eksplisitt flagg).
     */
    public static function allowsDatabaseSeed(): bool
    {
        if (self::isProduction()) {
            return false;
        }

        if (self::isTest()) {
            return false;
        }

        if (!self::qualitySeedDatabaseRequested()) {
            return false;
        }

        return self::isLocalQuality() || self::isStaging();
    }

    public static function robotsMode(): string
    {
        $mode = strtolower(trim((string) ($_ENV['ROBOTS_MODE'] ?? 'noindex')));

        return in_array($mode, ['noindex', 'index'], true) ? $mode : 'noindex';
    }

    public static function mailMode(): string
    {
        $mode = strtolower(trim((string) ($_ENV['MAIL_MODE'] ?? 'log')));

        return in_array($mode, ['log', 'off', 'real'], true) ? $mode : 'log';
    }

    public static function paymentMode(): string
    {
        $mode = strtolower(trim((string) ($_ENV['PAYMENT_MODE'] ?? 'off')));

        return in_array($mode, ['off', 'test', 'real'], true) ? $mode : 'off';
    }
}
