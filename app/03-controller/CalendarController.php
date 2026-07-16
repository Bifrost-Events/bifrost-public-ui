<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\BackendApiClient;
use App\Service\PublicCalendarService;
use App\Support\Auth;
use App\Support\PublicPortalContext;
use App\Support\PublicView;
use App\Support\Response;
use App\Support\Session;
use App\Support\TenantContext;

final class CalendarController
{
    public function index(): array
    {
        $host = PublicPortalContext::requestHost();
        $calendar = (new PublicCalendarService())->forHost($host);
        $labels = $calendar['labels'];
        $eventPlural = (string) ($labels['event']['plural'] ?? 'Arrangementer');
        $seriesSingular = (string) ($labels['series']['singular'] ?? 'Serie');

        $title = $eventPlural;

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
        ], $title, $calendar['ok'] ? 200 : (($calendar['status'] ?? 0) === 404 ? 404 : 503));
    }

    public function show(int $id): array
    {
        // Hybrid: detalj + påmelding fortsatt V2 inntil V3 event-detalj finnes.
        $host = TenantContext::requestHost();
        $response = (new BackendApiClient())->competitionSignup($id, $host);

        if (!($response['ok'] ?? false) || !is_array($response['data'])) {
            Session::setFlash('error', (string) ($response['error'] ?? 'Stevnet finnes ikke'));

            return Response::redirect('/calendar');
        }

        $data = $response['data'];
        $user = Auth::user();

        return PublicView::render('calendar-show-content', [
            'competition' => is_array($data['competition'] ?? null) ? $data['competition'] : [],
            'registration_open' => (bool) ($data['registration_open'] ?? false),
            'advance_registration_enabled' => (bool) ($data['advance_registration_enabled'] ?? false),
            'slots' => is_array($data['slots'] ?? null) ? $data['slots'] : [],
            'registrations' => is_array($data['registrations'] ?? null) ? $data['registrations'] : [],
            'reserved_places' => is_array($data['reserved_places'] ?? null) ? $data['reserved_places'] : [],
            'participants' => is_array($data['participants'] ?? null) ? $data['participants'] : [],
            'classes' => is_array($data['classes'] ?? null) ? $data['classes'] : [],
            'my_participant_ids' => is_array($data['my_participant_ids'] ?? null) ? $data['my_participant_ids'] : [],
            'organizer' => is_array($data['organizer'] ?? null) ? $data['organizer'] : null,
            'logged_in' => $user !== null,
            'auth_user_id' => is_array($user) ? (int) ($user['id'] ?? 0) : 0,
            'data_source' => 'v2',
        ], (string) (($data['competition']['name'] ?? null) ?: 'Stevne'));
    }

    public function register(int $id): array
    {
        if ($redirect = Auth::requireBackendSession()) {
            return $redirect;
        }

        $host = TenantContext::requestHost();
        $result = (new BackendApiClient())->registerSignup($host, [
            'competition_id' => $id,
            'participant_id' => (int) ($_POST['participant_id'] ?? 0),
            'slot_id' => !empty($_POST['slot_id']) ? (int) $_POST['slot_id'] : null,
            'figure_number' => !empty($_POST['figure_number']) ? (int) $_POST['figure_number'] : null,
        ]);

        if ($result['ok'] ?? false) {
            Session::setFlash('success', 'Påmelding registrert.');
        } else {
            Session::setFlash('error', (string) ($result['error'] ?? 'Kunne ikke melde på'));
        }

        return Response::redirect('/calendar/' . $id);
    }

    public function unregister(int $id): array
    {
        if ($redirect = Auth::requireBackendSession()) {
            return $redirect;
        }

        $host = TenantContext::requestHost();
        $result = (new BackendApiClient())->unregisterSignup($host, [
            'competition_id' => $id,
            'participant_id' => (int) ($_POST['participant_id'] ?? 0),
        ]);

        if ($result['ok'] ?? false) {
            Session::setFlash('success', 'Påmelding er avmeldt.');
        } else {
            Session::setFlash('error', (string) ($result['error'] ?? 'Kunne ikke avmelde'));
        }

        return Response::redirect('/calendar/' . $id);
    }
}
