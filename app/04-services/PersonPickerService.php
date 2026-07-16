<?php

declare(strict_types=1);

namespace App\Service;

use App\Support\Session;

/**
 * Generell personvelger for public-ui (uten jaktfelt/slots/figurer).
 * Brukes senere av V3-påmelding.
 */
final class PersonPickerService
{
    public function __construct(
        private readonly AdminAuthClient $client = new AdminAuthClient(),
    ) {
    }

    /**
     * @return array{
     *   ok: bool,
     *   error: string|null,
     *   people: list<array<string, mixed>>,
     *   selected_person_id: int|null,
     *   selected: array<string, mixed>|null
     * }
     */
    public function forCurrentUser(): array
    {
        $response = $this->client->people();
        if (!($response['ok'] ?? false) || !is_array($response['data'] ?? null)) {
            return [
                'ok' => false,
                'error' => (string) ($response['error'] ?? 'Kunne ikke hente personer'),
                'people' => [],
                'selected_person_id' => null,
                'selected' => null,
            ];
        }

        $people = is_array($response['data']['people'] ?? null) ? $response['data']['people'] : [];
        $primaryId = (int) ($response['data']['primary_person_id'] ?? 0);
        $selectedId = Session::getActingPersonId();
        if ($selectedId === null || !$this->containsPerson($people, $selectedId)) {
            $selectedId = $primaryId > 0 ? $primaryId : null;
            if ($selectedId !== null) {
                Session::setActingPersonId($selectedId);
            }
        }

        $selected = null;
        foreach ($people as $person) {
            if (is_array($person) && (int) ($person['person_id'] ?? 0) === $selectedId) {
                $selected = $person;
                break;
            }
        }

        return [
            'ok' => true,
            'error' => null,
            'people' => $people,
            'selected_person_id' => $selectedId,
            'selected' => $selected,
        ];
    }

    public function selectPerson(int $personId): bool
    {
        $state = $this->forCurrentUser();
        if (!($state['ok'] ?? false) || !$this->containsPerson($state['people'], $personId)) {
            return false;
        }
        Session::setActingPersonId($personId);

        return true;
    }

    /**
     * @param list<array<string, mixed>> $people
     */
    private function containsPerson(array $people, int $personId): bool
    {
        foreach ($people as $person) {
            if (is_array($person) && (int) ($person['person_id'] ?? 0) === $personId) {
                return true;
            }
        }

        return false;
    }
}
