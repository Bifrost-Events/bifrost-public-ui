<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\BackendApiClient;
use App\Support\Auth;
use App\Support\PublicView;
use App\Support\Response;
use App\Support\Session;
use App\Support\TenantContext;

final class OnboardingController
{
    public function index(): array
    {
        Session::startRequired();
        if ($redirect = Auth::requireBackendSession()) {
            return $redirect;
        }

        $user = Auth::user();
        $step = trim((string) ($_GET['step'] ?? ''));
        if ($step === '' || $step === 'choose') {
            $step = 'participant';
        }

        $client = new BackendApiClient();
        $participantCandidate = null;
        $createdParticipant = null;

        if ($step === 'participant') {
            $apiResult = $client->onboardingParticipant();
            if ($apiResult['ok'] && is_array($apiResult['data'])) {
                $existing = $apiResult['data']['existing_participant'] ?? null;
                $created = $apiResult['data']['created_participant'] ?? null;
                if (is_array($existing)) {
                    $participantCandidate = $existing;
                } elseif (is_array($created)) {
                    $createdParticipant = $created;
                }
            }
        }

        $tenant = TenantContext::current();
        $welcome = (string) ($tenant['display_name'] ?? 'Velkommen');

        $arrangorPortalUrl = trim((string) ($_ENV['ARRANGOR_PORTAL_URL'] ?? ''));
        $resolve = $client->resolveTenant($tenant['host']);
        if ($resolve['ok'] && is_array($resolve['data']['urls'] ?? null)) {
            $fromApi = trim((string) ($resolve['data']['urls']['arrangor'] ?? ''));
            if ($fromApi !== '') {
                $arrangorPortalUrl = $fromApi;
            }
        }

        return PublicView::render('onboarding-content', [
            'user' => $user,
            'step' => $step,
            'participant_candidate' => $participantCandidate,
            'created_participant' => $createdParticipant,
            'onboarding_welcome' => $welcome,
            'arrangor_portal_url' => $arrangorPortalUrl,
        ], 'Velkommen');
    }

    public function claimParticipant(int $id): array
    {
        Session::startRequired();
        if ($redirect = Auth::requireBackendSession()) {
            return $redirect;
        }

        $returnTo = trim((string) ($_POST['return_to'] ?? ''));
        if ($returnTo === '' || !str_starts_with($returnTo, '/') || str_starts_with($returnTo, '//')) {
            $returnTo = '/onboarding?step=done';
        }

        $result = (new BackendApiClient())->claimParticipant($id);
        if ($result['ok']) {
            Session::setFlash('success', (string) ($result['data']['message'] ?? 'Forespørselen er sendt.'));
        } else {
            Session::setFlash('error', (string) ($result['error'] ?? 'Kunne ikke sende forespørsel'));
        }

        return Response::redirect($returnTo);
    }
}
