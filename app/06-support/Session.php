<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Lazy session: offentlige GET uten cookie starter ikke PHP-session.
 */
final class Session
{
    private const SESSION_NAME = 'BIFROSTPUBLIC';

    private const AUTH_KEY = 'bifrost_public_auth';
    private const BACKEND_COOKIE_KEY = 'bifrost_public_backend_cookie';
    private const FLASH_KEY = 'bifrost_public_flash';

    /** @var bool|null */
    private static ?bool $configured = null;

    public static function isActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public static function startIfExists(): bool
    {
        if (self::isActive()) {
            return true;
        }

        if (!isset($_COOKIE[self::SESSION_NAME])) {
            return false;
        }

        self::startWithConfig();

        return self::isActive();
    }

    public static function startRequired(): bool
    {
        if (self::isActive()) {
            return true;
        }

        self::startWithConfig();

        return self::isActive();
    }

    public static function pathSkipsSessionEntirely(string $path): bool
    {
        return in_array($path, ['/health', '/robots.txt'], true);
    }

    public static function shouldStart(string $path, string $method): bool
    {
        return self::requiresNewSession($path, $method);
    }

    /** Opprett session for POST og beskyttede ruter (uten å sjekke eksisterende cookie). */
    public static function requiresNewSession(string $path, string $method): bool
    {
        if (self::pathSkipsSessionEntirely($path)) {
            return false;
        }

        if (strtoupper($method) !== 'GET') {
            return true;
        }

        $prefixes = [
            '/auth',
            '/min-side',
            '/onboarding',
            '/calendar',
        ];

        foreach ($prefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        if (preg_match('#^/calendar/\d+/(register|unregister)$#', $path) === 1) {
            return true;
        }

        return false;
    }

    /** @param array<string, mixed> $user */
    public static function setAuth(array $user): void
    {
        self::startRequired();
        $_SESSION[self::AUTH_KEY] = $user;
    }

    /** @return array<string, mixed>|null */
    public static function getAuth(): ?array
    {
        if (!self::startIfExists()) {
            return null;
        }

        $auth = $_SESSION[self::AUTH_KEY] ?? null;

        return is_array($auth) ? $auth : null;
    }

    public static function clear(): void
    {
        if (!self::isActive()) {
            return;
        }

        unset($_SESSION[self::AUTH_KEY], $_SESSION[self::BACKEND_COOKIE_KEY], $_SESSION[self::FLASH_KEY]);
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 3600,
                'path' => $p['path'] ?? '/',
                'domain' => $p['domain'] ?? '',
                'secure' => (bool) ($p['secure'] ?? false),
                'httponly' => (bool) ($p['httponly'] ?? true),
                'samesite' => $p['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
    }

    public static function clearAuth(): void
    {
        if (!self::isActive()) {
            return;
        }

        unset($_SESSION[self::AUTH_KEY]);
    }

    public static function setBackendCookie(string $cookie): void
    {
        self::startRequired();
        $_SESSION[self::BACKEND_COOKIE_KEY] = $cookie;
    }

    public static function getBackendCookie(): string
    {
        if (!self::startIfExists()) {
            return '';
        }

        $cookie = $_SESSION[self::BACKEND_COOKIE_KEY] ?? '';

        return is_string($cookie) ? $cookie : '';
    }

    public static function clearBackendCookie(): void
    {
        if (!self::isActive()) {
            return;
        }

        unset($_SESSION[self::BACKEND_COOKIE_KEY]);
    }

    /** @param array<string, string> $errors */
    public static function setFlash(string $type, string $message, array $errors = []): void
    {
        self::startRequired();
        $_SESSION[self::FLASH_KEY] = [
            'type' => $type,
            'message' => $message,
            'errors' => $errors,
        ];
    }

    /** @return array{type: string, message: string, errors: array<string, string>}|null */
    public static function pullFlash(): ?array
    {
        if (!self::startIfExists()) {
            return null;
        }

        $flash = $_SESSION[self::FLASH_KEY] ?? null;
        unset($_SESSION[self::FLASH_KEY]);
        if (!is_array($flash)) {
            return null;
        }

        return [
            'type' => (string) ($flash['type'] ?? 'info'),
            'message' => (string) ($flash['message'] ?? ''),
            'errors' => is_array($flash['errors'] ?? null) ? $flash['errors'] : [],
        ];
    }

    private static function startWithConfig(): void
    {
        if (self::isActive()) {
            return;
        }

        self::configureCookieParams();
        session_name(self::SESSION_NAME);
        session_start();
    }

    private static function configureCookieParams(): void
    {
        if (self::$configured === true) {
            return;
        }

        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $domain = '';
        if (str_ends_with($host, '.bifrost.local') || $host === 'bifrost.local') {
            $domain = '.bifrost.local';
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => $domain,
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        self::$configured = true;
    }
}
