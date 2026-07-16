<?php

declare(strict_types=1);

namespace App\Support;

use App\Service\BackendApiClient;

/**
 * Resolver aktiv V2-tenant fra HTTP-host via bifrost-backend.
 *
 * Hybridperiode: brukes fortsatt for auth, resultater, påmelding og deltakerflyt.
 * Portal-identitet for V3 (kalender/application) skal gå via PublicPortalContext.
 */
final class TenantContext
{
    /** @var array<string, mixed>|null */
    private static ?array $cached = null;

    /**
     * @return array{
     *   host: string,
     *   resolved: bool,
     *   error: string|null,
     *   tenant: array<string, mixed>|null,
     *   display_name: string,
     *   features: array<string, bool>
     * }
     */
    public static function current(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        $host = self::requestHost();
        $client = new BackendApiClient();
        $response = $client->resolveTenant($host);

        $tenant = null;
        $error = null;
        if ($response['ok'] && is_array($response['data']['tenant'] ?? null)) {
            $tenant = $response['data']['tenant'];
        } else {
            $error = (string) ($response['error'] ?? 'Kunne ikke finne cup for dette domenet');
        }

        $displayName = is_array($tenant)
            ? trim((string) ($tenant['name'] ?? $tenant['display_name'] ?? 'Bifrost'))
            : (string) Config::get('app.name', 'Bifrost');

        self::$cached = [
            'host' => $host,
            'resolved' => $tenant !== null,
            'error' => $error,
            'tenant' => $tenant,
            'display_name' => $displayName !== '' ? $displayName : 'Bifrost',
            'features' => self::defaultFeatures(),
        ];

        return self::$cached;
    }

    public static function requestHost(): string
    {
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));

        return explode(':', $host)[0];
    }

    /** @return array<string, bool> */
    private static function defaultFeatures(): array
    {
        return [
            'standings' => true,
            'archive' => false,
            'sponsor_page' => false,
        ];
    }
}
