<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\BackendApiClient;
use App\Support\PublicView;
use App\Support\Response;
use App\Support\Session;
use App\Support\TenantContext;

final class ParticipantController
{
    public function index(): array
    {
        $api = new BackendApiClient();
        $response = $api->participantShooters();

        $participants = [];
        $classes = [];
        $clubSuggestions = [];
        $error = null;

        if ($response['ok'] && is_array($response['data'])) {
            $participants = is_array($response['data']['participants'] ?? null) ? $response['data']['participants'] : [];
            $classes = is_array($response['data']['classes'] ?? null) ? $response['data']['classes'] : [];
            $clubSuggestions = is_array($response['data']['club_suggestions'] ?? null) ? $response['data']['club_suggestions'] : [];
        } else {
            $error = (string) ($response['error'] ?? 'Kunne ikke hente deltakere');
        }

        return PublicView::render('participants-content', [
            'participants' => $participants,
            'classes' => $classes,
            'club_suggestions' => $clubSuggestions,
            'error' => $error,
        ], 'Mine deltakere');
    }

    public function store(): array
    {
        $api = new BackendApiClient();
        $result = $api->createParticipant([
            'first_name' => trim((string) ($_POST['first_name'] ?? '')),
            'last_name' => trim((string) ($_POST['last_name'] ?? '')),
            'class_id' => (int) ($_POST['class_id'] ?? 0),
            'date_of_birth' => trim((string) ($_POST['date_of_birth'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'club' => trim((string) ($_POST['club'] ?? '')),
        ]);

        if ($result['ok'] ?? false) {
            Session::setFlash('success', 'Deltaker er opprettet med Jaktfelt-ID.');
        } else {
            Session::setFlash('error', (string) ($result['error'] ?? 'Kunne ikke opprette deltaker'));
        }

        return Response::redirect('/min-side/deltakere');
    }

    public function update(int $id): array
    {
        $api = new BackendApiClient();
        $result = $api->updateParticipant($id, [
            'first_name' => trim((string) ($_POST['first_name'] ?? '')),
            'last_name' => trim((string) ($_POST['last_name'] ?? '')),
            'class_id' => (int) ($_POST['class_id'] ?? 0),
            'date_of_birth' => trim((string) ($_POST['date_of_birth'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'club' => trim((string) ($_POST['club'] ?? '')),
        ]);

        if ($result['ok'] ?? false) {
            Session::setFlash('success', 'Deltaker er oppdatert.');
        } else {
            Session::setFlash('error', (string) ($result['error'] ?? 'Kunne ikke oppdatere deltaker'));
        }

        return Response::redirect('/min-side/deltakere');
    }
}
