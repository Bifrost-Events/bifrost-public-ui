<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Henter og mapper V3 public calendar for public-ui.
 * Faller IKKE tilbake til V2 ved feil.
 */
final class PublicCalendarService
{
    public function __construct(
        private readonly EventsApiClient $client = new EventsApiClient(),
        private readonly V3EventMapper $mapper = new V3EventMapper(),
        private readonly EventUrlResolver $urls = new EventUrlResolver(),
    ) {
    }

    /**
     * @return array{
     *   ok: bool,
     *   status: int,
     *   error: string|null,
     *   code: string|null,
     *   application: array<string, mixed>|null,
     *   space: array<string, mixed>|null,
     *   season: array<string, mixed>|null,
     *   labels: array<string, array{singular: string, plural: string}>,
     *   competitions: list<array<string, mixed>>,
     *   source: 'v3'
     * }
     */
    public function forHost(string $host): array
    {
        $response = $this->client->publicCalendar($host);
        if (!($response['ok'] ?? false) || !is_array($response['data'] ?? null)) {
            return [
                'ok' => false,
                'status' => (int) ($response['status'] ?? 0),
                'error' => (string) ($response['error'] ?? 'Kunne ikke hente kalender fra Events API'),
                'code' => isset($response['code']) ? (string) $response['code'] : null,
                'application' => null,
                'space' => null,
                'season' => null,
                'labels' => $this->mapper->mapCalendar([])['labels'],
                'competitions' => [],
                'source' => 'v3',
            ];
        }

        $mapped = $this->mapper->mapCalendar($response['data']);
        $competitions = [];
        foreach ($mapped['competitions'] as $event) {
            $detailUrl = $this->urls->v3EventUrl($event);
            $event['detail_url'] = $detailUrl;
            $event['has_detail_link'] = $detailUrl !== null;
            $competitions[] = $event;
        }

        $season = $mapped['season'];
        if (is_array($season)) {
            $season['detail_url'] = $this->urls->v3SeriesUrl($season);
        }

        return [
            'ok' => true,
            'status' => (int) ($response['status'] ?? 200),
            'error' => null,
            'code' => null,
            'application' => $mapped['application'],
            'space' => $mapped['space'],
            'season' => $season,
            'labels' => $mapped['labels'],
            'competitions' => $competitions,
            'source' => 'v3',
        ];
    }
}
