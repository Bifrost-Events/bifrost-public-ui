<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\PublicCalendarService;
use App\Support\PublicPortalContext;
use App\Support\PublicView;
use App\Support\Response;

final class CalendarController
{
    public function index(): array
    {
        $host = PublicPortalContext::requestHost();
        $calendar = (new PublicCalendarService())->forHost($host);
        $labels = $calendar['labels'];
        $eventPlural = (string) ($labels['event']['plural'] ?? 'Arrangementer');
        $seriesSingular = (string) ($labels['series']['singular'] ?? 'Serie');

        return PublicView::render('calendar-content', [
            'calendar' => $calendar,
            'competitions' => $calendar['competitions'],
            'season' => $calendar['season'],
            'labels' => $labels,
            'space' => $calendar['space'],
            'application' => $calendar['application'],
            'error' => $calendar['ok'] ? null : (string) ($calendar['error'] ?? 'Kunne ikke hente kalender'),
            'error_status' => $calendar['ok'] ? null : (int) ($calendar['status'] ?? 0),
            'series_label' => $seriesSingular,
            'event_label_plural' => $eventPlural,
            'event_label_singular' => (string) ($labels['event']['singular'] ?? 'Arrangement'),
            'source' => 'v3',
        ], $eventPlural, $calendar['ok'] ? 200 : (($calendar['status'] ?? 0) === 404 ? 404 : 503));
    }

    /**
     * Legacy URL: /calendar/{legacyCompetitionId} → V3 arrangement når mapping finnes.
     */
    public function show(int $id): array
    {
        $eventId = $this->findEventIdByLegacyCompetitionId(PublicPortalContext::requestHost(), $id);
        if ($eventId !== null) {
            return Response::redirect('/arrangementer/' . $eventId);
        }

        return PublicView::render('placeholder-content', [
            'page_title' => 'Ikke tilgjengelig',
            'page_description' => 'Denne stevnelenken er ikke lenger i bruk. Gå til stevnekalenderen for aktuelle arrangementer.',
        ], 'Ikke tilgjengelig', 410);
    }

    public function register(int $id): array
    {
        $eventId = $this->findEventIdByLegacyCompetitionId(PublicPortalContext::requestHost(), $id);
        if ($eventId !== null) {
            return Response::redirect('/arrangementer/' . $eventId);
        }

        return Response::redirect('/calendar');
    }

    public function unregister(int $id): array
    {
        return $this->register($id);
    }

    private function findEventIdByLegacyCompetitionId(string $host, int $legacyId): ?int
    {
        $calendar = (new PublicCalendarService())->forHost($host);
        if (!($calendar['ok'] ?? false)) {
            return null;
        }

        foreach ($calendar['competitions'] as $event) {
            if (!is_array($event)) {
                continue;
            }
            $legacy = is_array($event['legacy'] ?? null) ? $event['legacy'] : null;
            if ($legacy === null) {
                continue;
            }
            $table = (string) ($legacy['table'] ?? '');
            $lid = trim((string) ($legacy['id'] ?? ''));
            if ($table === 'jaktfelt_competitions' && $lid !== '' && ctype_digit($lid) && (int) $lid === $legacyId) {
                $eventId = (int) ($event['event_id'] ?? $event['id'] ?? 0);

                return $eventId > 0 ? $eventId : null;
            }
        }

        return null;
    }
}
