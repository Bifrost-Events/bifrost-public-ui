<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\BackendApiClient;
use App\Support\PublicView;
use App\Support\TenantContext;

final class ResultsController
{
    public function index(): array
    {
        $host = TenantContext::requestHost();
        $response = (new BackendApiClient())->publicResultsIndex($host);

        return PublicView::render('results-content', [
            'competitions' => ($response['ok'] && is_array($response['data']['competitions'] ?? null))
                ? $response['data']['competitions']
                : [],
            'error' => $response['ok'] ? null : (string) ($response['error'] ?? 'Kunne ikke hente resultater'),
        ], 'Resultater');
    }

    public function show(int $id): array
    {
        $host = TenantContext::requestHost();
        $response = (new BackendApiClient())->publicCompetitionResults($id, $host);
        if (!$response['ok']) {
            return PublicView::render('placeholder-content', [
                'page_title' => 'Resultater',
                'page_description' => (string) ($response['error'] ?? 'Stevne ikke funnet'),
            ], 'Resultater', 404);
        }

        $data = $response['data'] ?? [];

        return PublicView::render('results-show-content', [
            'competition' => is_array($data['competition'] ?? null) ? $data['competition'] : [],
            'results' => is_array($data['results'] ?? null) ? $data['results'] : [],
        ], (string) (($data['competition']['name'] ?? null) ?: 'Resultater'));
    }
}
