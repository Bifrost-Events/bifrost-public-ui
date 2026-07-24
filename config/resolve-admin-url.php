<?php

declare(strict_types=1);

/**
 * Base-URL for bifrost-admin-core auth/person API.
 * ADMIN_URL overstyrer; ellers EVENTS_URL; ellers lokal default.
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

    if (in_array((string) ($_ENV['APP_ENV'] ?? ''), ['local-dev', 'local-quality', 'development'], true)) {
        return 'http://admin.bifrost.local';
    }

    return '';
}
