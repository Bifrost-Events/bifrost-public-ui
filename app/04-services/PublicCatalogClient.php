<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Henter V3 serie-/arrangementsdetaljer for public-ui.
 */
final class PublicCatalogClient
{
    public function __construct(
        private readonly EventsApiClient $client = new EventsApiClient(),
        private readonly EventUrlResolver $urls = new EventUrlResolver(),
    ) {
    }

    /**
     * @return array{ok: bool, status: int, error: string|null, data: array<string, mixed>|null, source: 'v3'}
     */
    public function series(string $host, int $seriesId): array
    {
        $response = $this->client->publicSeries($host, $seriesId);
        if (!($response['ok'] ?? false) || !is_array($response['data'] ?? null)) {
            return [
                'ok' => false,
                'status' => (int) ($response['status'] ?? 0),
                'error' => (string) ($response['error'] ?? 'Kunne ikke hente serie'),
                'data' => null,
                'source' => 'v3',
            ];
        }

        $data = $response['data'];
        $data['urls'] = [
            'self' => $this->urls->v3SeriesUrl(['id' => $seriesId]),
        ];

        return [
            'ok' => true,
            'status' => (int) ($response['status'] ?? 200),
            'error' => null,
            'data' => $data,
            'source' => 'v3',
        ];
    }

    /**
     * @return array{ok: bool, status: int, error: string|null, data: array<string, mixed>|null, source: 'v3'}
     */
    public function event(string $host, int $eventId): array
    {
        $response = $this->client->publicEvent($host, $eventId);
        if (!($response['ok'] ?? false) || !is_array($response['data'] ?? null)) {
            return [
                'ok' => false,
                'status' => (int) ($response['status'] ?? 0),
                'error' => (string) ($response['error'] ?? 'Kunne ikke hente arrangement'),
                'data' => null,
                'source' => 'v3',
            ];
        }

        $data = $response['data'];
        $event = is_array($data['event'] ?? null) ? $data['event'] : [];
        $data['urls'] = [
            'self' => $this->urls->v3EventUrl($event),
            'results' => $this->urls->v3EventResultsUrl($event),
        ];

        $resultsResponse = $this->client->publicEventResults($host, $eventId);
        $hasV3Results = ($resultsResponse['ok'] ?? false)
            && is_array($resultsResponse['data'] ?? null)
            && (bool) ($resultsResponse['data']['has_results'] ?? false);
        $data['has_v3_results'] = $hasV3Results;

        return [
            'ok' => true,
            'status' => (int) ($response['status'] ?? 200),
            'error' => null,
            'data' => $data,
            'source' => 'v3',
        ];
    }

    /**
     * @return array{ok: bool, status: int, error: string|null, data: array<string, mixed>|null, source: 'v3'}
     */
    public function eventResults(string $host, int $eventId): array
    {
        $response = $this->client->publicEventResults($host, $eventId);
        if (!($response['ok'] ?? false) || !is_array($response['data'] ?? null)) {
            return [
                'ok' => false,
                'status' => (int) ($response['status'] ?? 0),
                'error' => (string) ($response['error'] ?? 'Kunne ikke hente resultater'),
                'data' => null,
                'source' => 'v3',
            ];
        }

        $data = $response['data'];
        $event = is_array($data['event'] ?? null) ? $data['event'] : ['id' => $eventId, 'event_id' => $eventId];
        $data['urls'] = [
            'self' => $this->urls->v3EventResultsUrl($event),
            'event' => $this->urls->v3EventUrl($event),
        ];

        return [
            'ok' => true,
            'status' => (int) ($response['status'] ?? 200),
            'error' => null,
            'data' => $data,
            'source' => 'v3',
        ];
    }

    /**
     * @return array{ok: bool, status: int, error: string|null, data: array<string, mixed>|null, source: 'v3'}
     */
    public function seriesStandings(string $host, int $seriesId): array
    {
        $response = $this->client->publicSeriesStandings($host, $seriesId);
        if (!($response['ok'] ?? false) || !is_array($response['data'] ?? null)) {
            return [
                'ok' => false,
                'status' => (int) ($response['status'] ?? 0),
                'error' => (string) ($response['error'] ?? 'Kunne ikke hente sammenlagt'),
                'data' => null,
                'source' => 'v3',
            ];
        }

        $data = $response['data'];
        $series = is_array($data['series'] ?? null) ? $data['series'] : ['id' => $seriesId];
        $data['urls'] = [
            'self' => $this->urls->v3StandingsUrl($series),
            'series' => $this->urls->v3SeriesUrl($series),
        ];

        return [
            'ok' => true,
            'status' => (int) ($response['status'] ?? 200),
            'error' => null,
            'data' => $data,
            'source' => 'v3',
        ];
    }
}
