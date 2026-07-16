<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\EventsApiClient;
use App\Support\Auth;
use App\Support\PublicPortalContext;
use App\Support\PublicView;
use App\Support\Response;
use App\Support\Session;

/**
 * Mine påmeldinger — V3 primær, V2-liste beholdes som hybridinfo der relevant.
 */
final class SignupController
{
    public function index(): array
    {
        if ($deny = Auth::requireLogin()) {
            return $deny;
        }

        $host = PublicPortalContext::requestHost();
        $client = new EventsApiClient();
        $upcoming = $client->myRegistrations($host, null, 'upcoming');
        $past = $client->myRegistrations($host, null, 'past');

        $error = null;
        if (!($upcoming['ok'] ?? false) && !($past['ok'] ?? false)) {
            $error = (string) ($upcoming['error'] ?? $past['error'] ?? 'Kunne ikke hente påmeldinger');
        }

        return PublicView::render('signups-content', [
            'upcoming' => ($upcoming['ok'] ?? false) && is_array($upcoming['data']['registrations'] ?? null)
                ? $upcoming['data']['registrations']
                : [],
            'past' => ($past['ok'] ?? false) && is_array($past['data']['registrations'] ?? null)
                ? $past['data']['registrations']
                : [],
            'error' => $error,
            'flash' => Session::pullFlash(),
            'source' => 'v3',
        ], 'Mine påmeldinger');
    }

    public function cancel(): array
    {
        if ($deny = Auth::requireLogin()) {
            return $deny;
        }

        $registrationId = (int) ($_POST['registration_id'] ?? 0);
        $result = (new EventsApiClient())->cancelRegistration($registrationId);
        if ($result['ok'] ?? false) {
            Session::setFlash('info', 'Påmelding avmeldt.');
        } else {
            Session::setFlash('error', (string) ($result['error'] ?? 'Avmelding feilet.'));
        }

        return Response::redirect('/min-side/pameldinger');
    }
}
