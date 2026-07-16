<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\EventsApiClient;
use App\Service\PublicCatalogClient;
use App\Support\PublicPortalContext;
use App\Support\PublicView;

/**
 * Toppnavigasjon /sammenlagt — V3 Events (sesong fra kalenderkontekst).
 */
final class StandingsController
{
    public function index(): array
    {
        $host = PublicPortalContext::requestHost();
        $calendar = (new EventsApiClient())->publicCalendar($host);
        if (!($calendar['ok'] ?? false)) {
            return PublicView::render('standings-content', [
                'season' => null,
                'standings' => null,
                'error' => (string) ($calendar['error'] ?? 'Kunne ikke hente sammenlagt'),
            ], 'Sammenlagt');
        }

        $data = is_array($calendar['data'] ?? null) ? $calendar['data'] : [];
        $season = is_array($data['season'] ?? null) ? $data['season'] : null;
        $seriesId = (int) ($season['id'] ?? $season['series_id'] ?? 0);
        if ($seriesId <= 0) {
            return PublicView::render('standings-content', [
                'season' => $season,
                'standings' => null,
                'error' => null,
            ], 'Sammenlagt');
        }

        $result = (new PublicCatalogClient())->seriesStandings($host, $seriesId);
        if (!($result['ok'] ?? false) || !is_array($result['data'] ?? null)) {
            return PublicView::render('standings-content', [
                'season' => $season,
                'standings' => null,
                'error' => (string) ($result['error'] ?? 'Kunne ikke hente sammenlagt'),
            ], 'Sammenlagt');
        }

        $standingsData = $result['data'];
        $series = is_array($standingsData['series'] ?? null) ? $standingsData['series'] : [];
        $labels = is_array($standingsData['labels'] ?? null) ? $standingsData['labels'] : [];

        return PublicView::render('series-standings-content', [
            'series' => $series !== [] ? $series : ($season ?? []),
            'labels' => $labels,
            'standings_mode' => (string) ($standingsData['standings_mode'] ?? ''),
            'count_best' => (int) ($standingsData['count_best'] ?? 0),
            'class_groups' => is_array($standingsData['class_groups'] ?? null) ? $standingsData['class_groups'] : [],
            'has_standings' => (bool) ($standingsData['has_standings'] ?? false),
            'resolved_from_series_id' => $standingsData['resolved_from_series_id'] ?? null,
            'series_url' => '/serier/' . $seriesId,
            'source' => 'v3',
        ], 'Sammenlagt');
    }
}
