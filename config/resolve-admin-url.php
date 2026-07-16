<?php

declare(strict_types=1);

/**
 * Base-URL for bifrost-admin-core auth/person API.
 * ADMIN_URL overstyrer; ellers EVENTS_URL / utledet admin.* fra BACKEND_API_URL.
 */
function resolve_public_admin_api_base_url(): string
{
    $explicit = trim((string) ($_ENV['ADMIN_URL'] ?? ''));
    if ($explicit !== '') {
        return rtrim($explicit, '/');
    }

    if (function_exists('resolve_public_events_api_base_url')) {
        $events = resolve_public_events_api_base_url();
        if ($events !== '') {
            return $events;
        }
    }

    $backend = rtrim((string) ($_ENV['BACKEND_API_URL'] ?? ''), '/');
    if ($backend !== '') {
        $parsed = parse_url($backend);
        $host = is_array($parsed) ? strtolower((string) ($parsed['host'] ?? '')) : '';
        $scheme = is_array($parsed) ? (string) ($parsed['scheme'] ?? 'http') : 'http';
        $port = is_array($parsed) && isset($parsed['port']) ? ':' . (int) $parsed['port'] : '';

        if ($host !== '' && str_starts_with($host, 'api.')) {
            return $scheme . '://admin.' . substr($host, 4) . $port;
        }

        return $backend;
    }

    if (in_array((string) ($_ENV['APP_ENV'] ?? ''), ['local-dev', 'local-quality', 'development'], true)) {
        return 'http://admin.bifrost.local';
    }

    return '';
}
