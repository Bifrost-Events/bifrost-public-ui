<?php

declare(strict_types=1);

namespace App\Support;

use App\Service\EventsApiClient;
use App\Service\V3EventMapper;

/**
 * V3 portal-kontekst: host → application / Event Space / labels.
 *
 * Primær kilde for portal-identitet i V3-flyt. TenantContext beholdes midlertidig
 * for V2 auth/resultater/påmelding.
 */
final class PublicPortalContext
{
    /** @var array<string, mixed>|null */
    private static ?array $cached = null;

    /**
     * @return array{
     *   host: string,
     *   resolved: bool,
     *   error: string|null,
     *   status: int,
     *   application_id: int|null,
     *   application_key: string|null,
     *   application_name: string|null,
     *   space: array<string, mixed>|null,
     *   spaces: list<array<string, mixed>>,
     *   labels: array<string, array{singular: string, plural: string}>,
     *   branding: array<string, mixed>,
     *   display_name: string,
     *   source: 'v3'
     * }
     */
    public static function current(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        $host = self::requestHost();
        $cupConfig = CupConfigLoader::current();
        $client = new EventsApiClient();
        $mapper = new V3EventMapper();
        $response = $client->publicContext($host);

        $labels = $mapper->mapCalendar([])['labels'];
        $applicationId = null;
        $applicationKey = null;
        $applicationName = null;
        $space = null;
        $spaces = [];
        $error = null;
        $resolved = false;
        $status = (int) ($response['status'] ?? 0);

        if (($response['ok'] ?? false) && is_array($response['data'] ?? null)) {
            $mapped = $mapper->mapContext($response['data']);
            $resolved = $mapped['application_id'] > 0;
            $applicationId = $mapped['application_id'] > 0 ? $mapped['application_id'] : null;
            $applicationKey = $mapped['application_key'] !== '' ? $mapped['application_key'] : null;
            $applicationName = $mapped['application_name'] !== '' ? $mapped['application_name'] : null;
            $space = $mapped['space'];
            $spaces = $mapped['spaces'];
            $labels = $mapped['labels'];
            $host = $mapped['hostname'] !== '' ? $mapped['hostname'] : $host;
        } else {
            $error = (string) ($response['error'] ?? 'Kunne ikke resolvere applikasjon for dette domenet');
            if ($status === 0) {
                $status = 503;
            }
        }

        $displayName = (string) ($cupConfig['name'] ?? '');
        if ($displayName === '' && is_string($applicationName) && $applicationName !== '') {
            $displayName = $applicationName;
        }
        if ($displayName === '') {
            $displayName = (string) Config::get('app.name', 'Bifrost');
        }

        self::$cached = [
            'host' => $host,
            'resolved' => $resolved,
            'error' => $error,
            'status' => $status,
            'application_id' => $applicationId,
            'application_key' => $applicationKey,
            'application_name' => $applicationName,
            'space' => $space,
            'spaces' => $spaces,
            'labels' => $labels,
            'branding' => $cupConfig,
            'display_name' => $displayName,
            'source' => 'v3',
        ];

        return self::$cached;
    }

    public static function requestHost(): string
    {
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));

        return explode(':', $host)[0];
    }

    public static function label(string $key, string $form = 'singular'): string
    {
        $labels = self::current()['labels'];
        $block = $labels[$key] ?? null;
        if (!is_array($block)) {
            return $key;
        }
        $form = $form === 'plural' ? 'plural' : 'singular';

        return (string) ($block[$form] ?? $key);
    }

    /** @internal for tests */
    public static function resetCache(): void
    {
        self::$cached = null;
    }
}
