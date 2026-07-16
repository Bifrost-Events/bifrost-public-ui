<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\EventsApiClient;
use App\Service\PublicCatalogClient;
use App\Support\PublicPortalContext;
use App\Support\PublicView;

/**
 * Toppnavigasjon /results — V3 Events (ikke bifrost-backend).
 */
final class ResultsController
{
    public function index(): array
    {
        $host = PublicPortalContext::requestHost();
        $calendar = (new EventsApiClient())->publicCalendar($host);
        if (!($calendar['ok'] ?? false)) {
            return PublicView::render('results-content', [
                'competitions' => [],
                'error' => (string) ($calendar['error'] ?? 'Kunne ikke hente resultater'),
            ], 'Resultater');
        }

        $data = is_array($calendar['data'] ?? null) ? $calendar['data'] : [];
        $competitions = [];
        $client = new EventsApiClient();
        foreach ($data['competitions'] ?? [] as $comp) {
            if (!is_array($comp)) {
                continue;
            }
            $eventId = (int) ($comp['event_id'] ?? $comp['id'] ?? 0);
            if ($eventId <= 0) {
                continue;
            }
            $results = $client->publicEventResults($host, $eventId);
            if (!($results['ok'] ?? false) || empty($results['data']['has_results'])) {
                continue;
            }
            $competitions[] = [
                'id' => $eventId,
                'name' => (string) ($comp['name'] ?? ''),
                'competition_date' => (string) ($comp['competition_date'] ?? $comp['starts_at'] ?? ''),
                'url' => '/arrangementer/' . $eventId . '/resultater',
            ];
        }

        return PublicView::render('results-content', [
            'competitions' => $competitions,
            'error' => null,
        ], 'Resultater');
    }

    public function show(int $id): array
    {
        // Primær: V3 event_id
        $host = PublicPortalContext::requestHost();
        $result = (new PublicCatalogClient())->eventResults($host, $id);
        if ($result['ok'] ?? false) {
            return (new EventController())->results($id);
        }

        return PublicView::render('placeholder-content', [
            'page_title' => 'Resultater',
            'page_description' => (string) ($result['error'] ?? 'Stevne ikke funnet'),
        ], 'Resultater', 404);
    }
}
