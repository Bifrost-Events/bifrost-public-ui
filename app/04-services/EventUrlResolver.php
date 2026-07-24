<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Sentral URL-resolver for publikumsportal (V3).
 */
final class EventUrlResolver
{
    /**
     * @param array<string, mixed> $event
     */
    public function v3EventUrl(array $event): ?string
    {
        $id = (int) ($event['event_id'] ?? $event['id'] ?? 0);

        return $id > 0 ? '/arrangementer/' . $id : null;
    }

    /**
     * @param array<string, mixed> $series
     */
    public function v3SeriesUrl(array $series): ?string
    {
        $id = (int) ($series['series_id'] ?? $series['id'] ?? 0);

        return $id > 0 ? '/serier/' . $id : null;
    }

    /**
     * @param array<string, mixed> $event
     */
    public function v3EventResultsUrl(array $event): ?string
    {
        $id = (int) ($event['event_id'] ?? $event['id'] ?? 0);

        return $id > 0 ? '/arrangementer/' . $id . '/resultater' : null;
    }

    /**
     * @param array<string, mixed> $series
     */
    public function v3StandingsUrl(array $series): ?string
    {
        $id = (int) ($series['series_id'] ?? $series['id'] ?? 0);

        return $id > 0 ? '/serier/' . $id . '/sammenlagt' : null;
    }

    /**
     * @param array<string, mixed> $event
     */
    public function detailUrl(array $event): ?string
    {
        return $this->v3EventUrl($event);
    }

    /**
     * - `jaktfelt_v3`: event.modules.jaktfelt
     * - `v3`: generell self-service
     *
     * @param array<string, mixed> $event
     */
    public function registrationFlow(array $event): string
    {
        $modules = is_array($event['modules'] ?? null) ? $event['modules'] : [];
        if (!empty($modules['jaktfelt'])) {
            return 'jaktfelt_v3';
        }

        return 'v3';
    }
}
