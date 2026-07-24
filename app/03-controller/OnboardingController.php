<?php

declare(strict_types=1);

namespace App\Controller;

use App\Support\Auth;
use App\Support\CupConfigLoader;
use App\Support\PublicPortalContext;
use App\Support\PublicView;
use App\Support\Session;

final class OnboardingController
{
    public function index(): array
    {
        Session::startRequired();
        if ($redirect = Auth::requireLogin()) {
            return $redirect;
        }

        $portal = PublicPortalContext::current();
        $welcome = trim((string) ($portal['display_name'] ?? ''));
        if ($welcome === '') {
            $welcome = trim((string) (CupConfigLoader::current()['name'] ?? ''));
        }
        if ($welcome === '') {
            $welcome = 'Velkommen';
        }

        return PublicView::render('onboarding-content', [
            'user' => Auth::user(),
            'step' => 'v3',
            'participant_candidate' => null,
            'created_participant' => null,
            'onboarding_welcome' => $welcome,
            'arrangor_portal_url' => CupConfigLoader::organizerPortalUrl(),
        ], 'Velkommen');
    }
}
