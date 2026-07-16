<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Sentral URL-resolver for publikumsportal (V3 primær, V2 kun hybrid).
 */
final class EventUrlResolver
{
    /**
     * Primær V3-arrangementslenke — alltid basert på event_id.
     *
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
     * @deprecated Use v3EventUrl() for calendar detail links. Kept as alias during transition.
     * @param array<string, mixed> $event
     */
    public function detailUrl(array $event): ?string
    {
        return $this->v3EventUrl($event);
    }

    /**
     * Eksplisitt hybridregel for påmelding — ikke basert på API-feil.
     *
     * - `jaktfelt_v3`: event.modules.jaktfelt (settings_json)
     * - `v2_legacy`: sikker jaktfelt_competitions-mapping uten V3-jaktfelt
     * - `v3`: generell V3 self-service
     *
     * @param array<string, mixed> $event
     */
    public function registrationFlow(array $event): string
    {
        $modules = is_array($event['modules'] ?? null) ? $event['modules'] : [];
        if (!empty($modules['jaktfelt'])) {
            return 'jaktfelt_v3';
        }

        return $this->v2SignupUrl($event) !== null ? 'v2_legacy' : 'v3';
    }

    /**
     * V2-hybrid: påmelding / gammel stevnedetalj — kun ved sikker legacy-mapping.
     *
     * @param array<string, mixed> $event Must include legacy source/table/id
     */
    public function v2SignupUrl(array $event): ?string
    {
        $legacyId = $this->safeJaktfeltCompetitionId($event);

        return $legacyId !== null ? '/calendar/' . $legacyId : null;
    }

    /**
     * V2-hybrid: resultater for samme competition-id.
     *
     * @param array<string, mixed> $event
     */
    public function v2ResultsUrl(array $event): ?string
    {
        $legacyId = $this->safeJaktfeltCompetitionId($event);

        return $legacyId !== null ? '/results/' . $legacyId : null;
    }

    /**
     * @param array<string, mixed> $event
     */
    private function safeJaktfeltCompetitionId(array $event): ?int
    {
        $legacy = is_array($event['legacy'] ?? null) ? $event['legacy'] : null;
        if ($legacy === null) {
            return null;
        }

        $table = (string) ($legacy['table'] ?? '');
        $legacyId = trim((string) ($legacy['id'] ?? ''));

        if ($table === 'jaktfelt_competitions' && $legacyId !== '' && ctype_digit($legacyId)) {
            return (int) $legacyId;
        }

        return null;
    }
}
