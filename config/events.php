<?php

declare(strict_types=1);

require_once __DIR__ . '/require-env.php';
require_once __DIR__ . '/resolve-events-url.php';

$baseUrl = resolve_public_events_api_base_url();
if ($baseUrl === '') {
    // Soft-fail: allow boot; EventsApiClient returns clear error at call time.
    $baseUrl = '';
}

return [
    'api_base_url' => $baseUrl,
];
