<?php

declare(strict_types=1);

namespace App\Support;

use App\Service\BackendApiClient;

final class Auth
{
    /** @var array<string, mixed>|null */
    private static ?array $resolvedUser = null;

    private static bool $resolved = false;

    /** @return array<string, mixed>|null */
    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$resolvedUser;
        }

        self::$resolved = true;
        self::$resolvedUser = self::resolveUser();

        return self::$resolvedUser;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function requireLogin(): ?array
    {
        if (!self::check()) {
            Session::setFlash('info', 'Du må logge inn for å se denne siden.');

            return Response::redirect(self::loginUrlForCurrentRequest());
        }

        return null;
    }

    /** Krever gyldig backend-session før API-kall som muterer data. */
    public static function requireBackendSession(): ?array
    {
        if (self::user() !== null) {
            return null;
        }

        Session::setFlash('error', 'Sesjonen utløp. Logg inn på nytt for å fortsette.');

        return Response::redirect(self::loginUrlForCurrentRequest());
    }

  /** @return array<string, mixed>|null */
    private static function resolveUser(): ?array
    {
        if (!Session::startIfExists()) {
            return null;
        }

        if (Session::getBackendCookie() === '') {
            Session::clearAuth();

            return null;
        }

        $me = (new BackendApiClient())->me();
        if (($me['ok'] ?? false) && is_array($me['data']['user'] ?? null)) {
            $user = $me['data']['user'];
            Session::setAuth($user);

            return $user;
        }

        Session::clearAuth();
        Session::clearBackendCookie();

        return null;
    }

    private static function loginUrlForCurrentRequest(): string
    {
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';

        return '/auth/login?return_to=' . rawurlencode($path);
    }
}
