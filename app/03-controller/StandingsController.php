<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\BackendApiClient;
use App\Support\PublicView;
use App\Support\TenantContext;

final class StandingsController
{
    public function index(): array
    {
        $host = TenantContext::requestHost();
        $response = (new BackendApiClient())->publicStandings($host);
        $data = $response['data'] ?? [];

        return PublicView::render('standings-content', [
            'season' => is_array($data['season'] ?? null) ? $data['season'] : null,
            'standings' => is_array($data['standings'] ?? null) ? $data['standings'] : null,
            'error' => $response['ok'] ? null : (string) ($response['error'] ?? 'Kunne ikke hente sammenlagt'),
        ], 'Sammenlagt');
    }
}
