<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\EventsApiClient;
use App\Support\CupConfigLoader;
use App\Support\PublicPortalContext;
use App\Support\PublicView;
use App\Support\Response;

final class HealthController
{
    public function __invoke(): array
    {
        $host = PublicPortalContext::requestHost();
        $context = (new EventsApiClient())->publicContext($host);
        $ok = (bool) ($context['ok'] ?? false);

        return Response::json([
            'public_ui' => 'ok',
            'events' => $ok
                ? [
                    'status' => 'ok',
                    'application_key' => is_array($context['data']['application'] ?? null)
                        ? ($context['data']['application']['key'] ?? null)
                        : null,
                ]
                : ['error' => $context['error'] ?? 'unreachable'],
            'cup' => (string) (CupConfigLoader::current()['cup_id'] ?? 'default'),
        ], $ok ? 200 : 503);
    }
}
