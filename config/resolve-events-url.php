<?php

declare(strict_types=1);

/**
 * Base-URL for bifrost-events public API.
 * EVENTS_URL overstyrer; ellers ADMIN_URL; ellers lokal default.
 */
function resolve_public_events_api_base_url(): string
{
    $explicit = trim((string) ($_ENV['EVENTS_URL'] ?? ''));
    if ($explicit !== '') {
        return rtrim($explicit, '/');
    }

    $admin = trim((string) ($_ENV['ADMIN_URL'] ?? ''));
    if ($admin !== '') {
        return rtrim($admin, '/');
    }

    if (in_array((string) ($_ENV['APP_ENV'] ?? ''), ['local-dev', 'local-quality', 'development'], true)) {
        return 'http://admin.bifrost.local';
    }

    return '';
}
