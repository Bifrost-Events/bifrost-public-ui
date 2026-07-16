<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\EventsApiClient;
use App\Service\EventUrlResolver;
use App\Service\PublicCatalogClient;
use App\Support\Auth;
use App\Support\PublicPortalContext;
use App\Support\PublicView;
use App\Support\Response;
use App\Support\Session;

final class EventController
{
    public function show(int $eventId): array
    {
        $host = PublicPortalContext::requestHost();
        $result = (new PublicCatalogClient())->event($host, $eventId);

        if (!($result['ok'] ?? false) || !is_array($result['data'] ?? null)) {
            $status = (int) ($result['status'] ?? 404);
            if ($status < 400) {
                $status = 404;
            }

            return PublicView::render('placeholder-content', [
                'page_title' => 'Ikke funnet',
                'page_description' => (string) ($result['error'] ?? 'Arrangementet finnes ikke eller er ikke offentlig for dette domenet.'),
            ], 'Ikke funnet', $status === 404 ? 404 : 503);
        }

        $data = $result['data'];
        $labels = is_array($data['labels'] ?? null) ? $data['labels'] : [];
        $event = is_array($data['event'] ?? null) ? $data['event'] : [];
        $title = (string) ($event['name'] ?? ($labels['event']['singular'] ?? 'Arrangement'));
        $hasV3Results = (bool) ($data['has_v3_results'] ?? false);
        $v2Links = is_array($data['v2_links'] ?? null) ? $data['v2_links'] : [];
        if ($hasV3Results) {
            $v2Links['results'] = null;
        }

        $flow = (new EventUrlResolver())->registrationFlow($event);
        $registrationMe = null;
        $jaktfeltSlots = null;
        $loggedIn = Auth::check();
        $api = new EventsApiClient();
        if ($loggedIn && $flow === 'v3') {
            $reg = $api->eventRegistrationsMe($host, $eventId);
            $registrationMe = ($reg['ok'] ?? false) ? ($reg['data'] ?? null) : null;
        }
        if ($flow === 'jaktfelt_v3') {
            $slots = $api->jaktfeltSlots($host, $eventId);
            $jaktfeltSlots = ($slots['ok'] ?? false) ? ($slots['data'] ?? null) : null;
            if ($loggedIn) {
                $reg = $api->jaktfeltRegistrationsMe($host, $eventId);
                $registrationMe = ($reg['ok'] ?? false) ? ($reg['data'] ?? null) : null;
            }
        }

        return PublicView::render('event-show-content', [
            'event' => $event,
            'series' => is_array($data['series'] ?? null) ? $data['series'] : null,
            'breadcrumb' => is_array($data['breadcrumb'] ?? null) ? $data['breadcrumb'] : [],
            'space' => is_array($data['space'] ?? null) ? $data['space'] : null,
            'application' => is_array($data['application'] ?? null) ? $data['application'] : null,
            'labels' => $labels,
            'v2_links' => $v2Links,
            'has_v3_results' => $hasV3Results,
            'results_url' => is_array($data['urls'] ?? null) ? ($data['urls']['results'] ?? null) : null,
            'registration_flow' => $flow,
            'logged_in' => $loggedIn,
            'registration_me' => is_array($registrationMe) ? $registrationMe : null,
            'jaktfelt_slots' => is_array($jaktfeltSlots) ? $jaktfeltSlots : null,
            'flash' => Session::pullFlash(),
            'source' => 'v3',
        ], $title);
    }

    public function register(int $eventId): array
    {
        if ($deny = Auth::requireLogin()) {
            return $deny;
        }

        $host = PublicPortalContext::requestHost();
        $personId = (int) ($_POST['person_id'] ?? 0);
        $flow = (string) ($_POST['registration_flow'] ?? 'v3');
        $api = new EventsApiClient();

        if ($flow === 'jaktfelt_v3') {
            $result = $api->createJaktfeltRegistration($host, $eventId, [
                'person_id' => $personId,
                'slot_position_id' => (int) ($_POST['slot_position_id'] ?? 0),
                'class_key' => trim((string) ($_POST['class_key'] ?? '')) ?: null,
                'class_name' => trim((string) ($_POST['class_name'] ?? '')),
                'confirm' => true,
            ]);
        } else {
            $result = $api->createEventRegistration($host, $eventId, $personId);
        }

        if ($result['ok'] ?? false) {
            Session::setFlash('info', 'Påmelding bekreftet.');
        } else {
            Session::setFlash('error', (string) ($result['error'] ?? 'Påmelding feilet.'));
        }

        return Response::redirect('/arrangementer/' . $eventId);
    }

    public function cancelRegistration(int $eventId): array
    {
        if ($deny = Auth::requireLogin()) {
            return $deny;
        }

        $registrationId = (int) ($_POST['registration_id'] ?? 0);
        $flow = (string) ($_POST['registration_flow'] ?? 'v3');
        $api = new EventsApiClient();
        $result = $flow === 'jaktfelt_v3'
            ? $api->cancelJaktfeltRegistration($registrationId)
            : $api->cancelRegistration($registrationId);

        if ($result['ok'] ?? false) {
            Session::setFlash('info', 'Påmelding avmeldt.');
        } else {
            Session::setFlash('error', (string) ($result['error'] ?? 'Avmelding feilet.'));
        }

        return Response::redirect('/arrangementer/' . $eventId);
    }

    public function results(int $eventId): array
    {
        $host = PublicPortalContext::requestHost();
        $client = new PublicCatalogClient();
        $eventResult = $client->event($host, $eventId);
        $resultsResult = $client->eventResults($host, $eventId);

        if (!($eventResult['ok'] ?? false) && !($resultsResult['ok'] ?? false)) {
            $status = (int) ($resultsResult['status'] ?? $eventResult['status'] ?? 404);

            return PublicView::render('placeholder-content', [
                'page_title' => 'Ikke funnet',
                'page_description' => (string) ($resultsResult['error'] ?? $eventResult['error'] ?? 'Fant ikke resultater.'),
            ], 'Ikke funnet', $status === 404 ? 404 : 503);
        }

        $eventData = is_array($eventResult['data'] ?? null) ? $eventResult['data'] : [];
        $resultsData = is_array($resultsResult['data'] ?? null) ? $resultsResult['data'] : [];
        $labels = is_array($resultsData['labels'] ?? null)
            ? $resultsData['labels']
            : (is_array($eventData['labels'] ?? null) ? $eventData['labels'] : []);
        $event = is_array($resultsData['event'] ?? null)
            ? $resultsData['event']
            : (is_array($eventData['event'] ?? null) ? $eventData['event'] : []);
        $hasResults = (bool) ($resultsData['has_results'] ?? false);
        $v2Results = null;
        if (!$hasResults && is_array($eventData['v2_links'] ?? null)) {
            $v2Results = $eventData['v2_links']['results'] ?? null;
        }

        $title = 'Resultater — ' . (string) ($event['name'] ?? ($labels['event']['singular'] ?? 'Arrangement'));

        return PublicView::render('event-results-content', [
            'event' => $event,
            'labels' => $labels,
            'has_results' => $hasResults,
            'results_by_class' => is_array($resultsData['results_by_class'] ?? null) ? $resultsData['results_by_class'] : [],
            'classes' => is_array($resultsData['classes'] ?? null) ? $resultsData['classes'] : [],
            'v2_results_url' => is_string($v2Results) ? $v2Results : null,
            'event_url' => '/arrangementer/' . $eventId,
            'source' => 'v3',
            'error' => ($resultsResult['ok'] ?? false) ? null : (string) ($resultsResult['error'] ?? null),
        ], $title);
    }
}
