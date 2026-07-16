<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Mapper V3 Events public calendar/context-payload til view-modeller for public-ui.
 */
final class V3EventMapper
{
    /**
     * @param array<string, mixed> $data Raw `data` from GET /api/public/calendar
     * @return array{
     *   application: array<string, mixed>|null,
     *   space: array<string, mixed>|null,
     *   spaces: list<array<string, mixed>>,
     *   season: array<string, mixed>|null,
     *   labels: array<string, array{singular: string, plural: string}>,
     *   competitions: list<array<string, mixed>>,
     *   events: list<array<string, mixed>>
     * }
     */
    public function mapCalendar(array $data): array
    {
        $labels = $this->mapLabels(is_array($data['labels'] ?? null) ? $data['labels'] : []);
        $competitions = [];
        foreach ($data['competitions'] ?? [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $competitions[] = $this->mapEventRow($row);
        }

        return [
            'application' => is_array($data['application'] ?? null) ? $data['application'] : null,
            'space' => is_array($data['space'] ?? null) ? $data['space'] : null,
            'spaces' => $this->mapSpaces($data['spaces'] ?? []),
            'season' => is_array($data['season'] ?? null) ? $data['season'] : null,
            'labels' => $labels,
            'competitions' => $competitions,
            'events' => $competitions,
        ];
    }

    /**
     * @param array<string, mixed> $data Raw `data` from GET /api/public/context
     * @return array{
     *   application_id: int,
     *   application_key: string,
     *   application_name: string,
     *   hostname: string,
     *   space: array<string, mixed>|null,
     *   spaces: list<array<string, mixed>>,
     *   labels: array<string, array{singular: string, plural: string}>
     * }
     */
    public function mapContext(array $data): array
    {
        $app = is_array($data['application'] ?? null) ? $data['application'] : [];

        return [
            'application_id' => (int) ($app['id'] ?? 0),
            'application_key' => (string) ($app['key'] ?? ''),
            'application_name' => (string) ($app['name'] ?? ''),
            'hostname' => (string) ($data['hostname'] ?? ''),
            'space' => is_array($data['space'] ?? null) ? $data['space'] : null,
            'spaces' => $this->mapSpaces($data['spaces'] ?? []),
            'labels' => $this->mapLabels(is_array($data['labels'] ?? null) ? $data['labels'] : []),
        ];
    }

    /**
     * @param mixed $spaces
     * @return list<array<string, mixed>>
     */
    private function mapSpaces(mixed $spaces): array
    {
        if (!is_array($spaces)) {
            return [];
        }
        $out = [];
        foreach ($spaces as $space) {
            if (is_array($space)) {
                $out[] = $space;
            }
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $labels
     * @return array<string, array{singular: string, plural: string}>
     */
    private function mapLabels(array $labels): array
    {
        $defaults = [
            'event_space' => ['singular' => 'Event Space', 'plural' => 'Event Spaces'],
            'series' => ['singular' => 'Serie', 'plural' => 'Serier'],
            'subseries' => ['singular' => 'Underserie', 'plural' => 'Underserier'],
            'event' => ['singular' => 'Arrangement', 'plural' => 'Arrangementer'],
        ];

        $out = $defaults;
        foreach ($defaults as $key => $fallback) {
            $block = $labels[$key] ?? null;
            if (!is_array($block)) {
                continue;
            }
            $singular = trim((string) ($block['singular'] ?? ''));
            $plural = trim((string) ($block['plural'] ?? ''));
            $out[$key] = [
                'singular' => $singular !== '' ? $singular : $fallback['singular'],
                'plural' => $plural !== '' ? $plural : $fallback['plural'],
            ];
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function mapEventRow(array $row): array
    {
        $eventId = (int) ($row['event_id'] ?? $row['id'] ?? 0);
        $startsAt = (string) ($row['starts_at'] ?? $row['competition_date'] ?? '');

        return [
            'id' => $eventId,
            'event_id' => $eventId,
            'name' => (string) ($row['name'] ?? ''),
            'competition_date' => $startsAt,
            'starts_at' => $startsAt,
            'ends_at' => $row['ends_at'] ?? null,
            'location' => (string) ($row['location'] ?? ''),
            'organizer_name' => (string) ($row['organizer_name'] ?? ''),
            'series_id' => isset($row['series_id']) ? (int) $row['series_id'] : null,
            'series' => is_array($row['series'] ?? null) ? $row['series'] : null,
            'status' => (string) ($row['status'] ?? ''),
            'visibility' => (string) ($row['visibility'] ?? ''),
            'slug' => isset($row['slug']) ? $row['slug'] : null,
            'legacy' => is_array($row['legacy'] ?? null) ? $row['legacy'] : null,
            'registration_start' => $row['registration_start'] ?? null,
            'registration_end' => $row['registration_end'] ?? null,
            'description' => $row['description'] ?? null,
            'is_published' => (bool) ($row['is_published'] ?? true),
        ];
    }
}
