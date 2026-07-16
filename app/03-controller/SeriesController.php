<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\PublicCatalogClient;
use App\Support\PublicPortalContext;
use App\Support\PublicView;

final class SeriesController
{
    public function show(int $seriesId): array
    {
        $host = PublicPortalContext::requestHost();
        $result = (new PublicCatalogClient())->series($host, $seriesId);

        if (!($result['ok'] ?? false) || !is_array($result['data'] ?? null)) {
            $status = (int) ($result['status'] ?? 404);
            if ($status < 400) {
                $status = 404;
            }

            return PublicView::render('placeholder-content', [
                'page_title' => 'Ikke funnet',
                'page_description' => (string) ($result['error'] ?? 'Serien finnes ikke eller er ikke offentlig for dette domenet.'),
            ], 'Ikke funnet', $status === 404 ? 404 : 503);
        }

        $data = $result['data'];
        $labels = is_array($data['labels'] ?? null) ? $data['labels'] : [];
        $series = is_array($data['series'] ?? null) ? $data['series'] : [];
        $title = (string) ($series['name'] ?? ($labels['series']['singular'] ?? 'Serie'));

        return PublicView::render('series-show-content', [
            'series' => $series,
            'parent' => is_array($data['parent'] ?? null) ? $data['parent'] : null,
            'children' => is_array($data['children'] ?? null) ? $data['children'] : [],
            'events' => is_array($data['events'] ?? null) ? $data['events'] : [],
            'breadcrumb' => is_array($data['breadcrumb'] ?? null) ? $data['breadcrumb'] : [],
            'space' => is_array($data['space'] ?? null) ? $data['space'] : null,
            'application' => is_array($data['application'] ?? null) ? $data['application'] : null,
            'labels' => $labels,
            'standings_url' => '/serier/' . $seriesId . '/sammenlagt',
            'source' => 'v3',
        ], $title);
    }

    public function standings(int $seriesId): array
    {
        $host = PublicPortalContext::requestHost();
        $result = (new PublicCatalogClient())->seriesStandings($host, $seriesId);

        if (!($result['ok'] ?? false) || !is_array($result['data'] ?? null)) {
            $status = (int) ($result['status'] ?? 404);
            if ($status < 400) {
                $status = 404;
            }

            return PublicView::render('placeholder-content', [
                'page_title' => 'Ikke funnet',
                'page_description' => (string) ($result['error'] ?? 'Sammenlagt ikke tilgjengelig for denne serien.'),
            ], 'Ikke funnet', $status === 404 ? 404 : 503);
        }

        $data = $result['data'];
        $labels = is_array($data['labels'] ?? null) ? $data['labels'] : [];
        $series = is_array($data['series'] ?? null) ? $data['series'] : [];
        $title = 'Sammenlagt — ' . (string) ($series['name'] ?? ($labels['series']['singular'] ?? 'Serie'));

        return PublicView::render('series-standings-content', [
            'series' => $series,
            'labels' => $labels,
            'standings_mode' => (string) ($data['standings_mode'] ?? ''),
            'count_best' => (int) ($data['count_best'] ?? 0),
            'class_groups' => is_array($data['class_groups'] ?? null) ? $data['class_groups'] : [],
            'has_standings' => (bool) ($data['has_standings'] ?? false),
            'resolved_from_series_id' => $data['resolved_from_series_id'] ?? null,
            'series_url' => '/serier/' . (int) ($series['id'] ?? $seriesId),
            'source' => 'v3',
        ], $title);
    }
}
