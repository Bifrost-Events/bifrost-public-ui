<?php

declare(strict_types=1);

namespace App\Support;

use App\Service\AdminAuthClient;
use App\Service\BackendApiClient;

/**
 * Public auth: V3 (admin-core) er primær.
 * V2 backend-session beholdes separat for hybrid påmelding/deltakere.
 */
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

    public static function authSource(): string
    {
        if (!self::check()) {
            return '';
        }

        $source = Session::getAuthSource();

        return $source !== '' ? $source : 'v3';
    }

    public static function requireLogin(): ?array
    {
        if (!self::check()) {
            Session::setFlash('info', 'Du må logge inn for å se denne siden.');

            return Response::redirect(self::loginUrlForCurrentRequest());
        }

        return null;
    }

    /** Krever gyldig V2 backend-session for hybrid påmelding/deltakere. */
    public static function requireBackendSession(): ?array
    {
        if (Session::getBackendCookie() === '') {
            Session::setFlash('error', 'Denne funksjonen bruker fortsatt V2. Logg inn på nytt, eller bruk V2-påmelding når V3-sesjon ikke er koblet.');

            return Response::redirect(self::loginUrlForCurrentRequest());
        }

        $me = (new BackendApiClient())->me();
        if (($me['ok'] ?? false) && is_array($me['data']['user'] ?? null)) {
            return null;
        }

        Session::setFlash('error', 'V2-sesjonen utløp. Logg inn på nytt for å fortsette med påmelding/deltakere.');

        return Response::redirect(self::loginUrlForCurrentRequest());
    }

    /** @return array<string, mixed>|null */
    private static function resolveUser(): ?array
    {
        if (!Session::startIfExists()) {
            return null;
        }

        // Primær: V3 admin-core
        if (Session::getAdminCookie() !== '') {
            $me = (new AdminAuthClient())->me();
            if (($me['ok'] ?? false) && is_array($me['data'] ?? null)) {
                $user = self::normalizeV3User($me['data']);
                Session::setAuth($user);
                Session::setAuthSource('v3');

                return $user;
            }
            Session::clearAdminCookie();
        }

        // Ingen automatisk V2-fallback for V3-sider — kun eksplisitt V2-cookie for hybrid
        if (Session::getBackendCookie() !== '' && Session::getAuthSource() === 'v2') {
            $me = (new BackendApiClient())->me();
            if (($me['ok'] ?? false) && is_array($me['data']['user'] ?? null)) {
                $user = $me['data']['user'];
                Session::setAuth($user);
                Session::setAuthSource('v2');

                return $user;
            }
            Session::clearBackendCookie();
        }

        Session::clearAuth();

        return null;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function normalizeV3User(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '' && is_array($data['person'] ?? null)) {
            $name = trim((string) ($data['person']['display_name'] ?? ''));
        }

        return [
            'user_id' => (int) ($data['user_id'] ?? 0),
            'person_id' => (int) ($data['person_id'] ?? 0),
            'email' => (string) ($data['email'] ?? ''),
            'name' => $name !== '' ? $name : (string) ($data['email'] ?? 'Bruker'),
            'username' => $data['username'] ?? null,
            'status' => (string) ($data['status'] ?? 'active'),
            'person' => is_array($data['person'] ?? null) ? $data['person'] : null,
            'people' => is_array($data['people'] ?? null) ? $data['people'] : [],
            'auth_source' => 'v3',
        ];
    }

    private static function loginUrlForCurrentRequest(): string
    {
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';

        return '/auth/login?return_to=' . rawurlencode($path);
    }
}
