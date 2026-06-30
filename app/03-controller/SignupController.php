<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\BackendApiClient;
use App\Support\PublicView;
use App\Support\Response;
use App\Support\Session;
use App\Support\TenantContext;

final class SignupController
{
    public function index(): array
    {
        $host = TenantContext::requestHost();
        $response = (new BackendApiClient())->participantSignups($host);

        $signups = [];
        $error = null;
        if ($response['ok'] && is_array($response['data'])) {
            $signups = is_array($response['data']['signups'] ?? null) ? $response['data']['signups'] : [];
        } else {
            $error = (string) ($response['error'] ?? 'Kunne ikke hente påmeldinger');
        }

        return PublicView::render('signups-content', [
            'signups' => $signups,
            'error' => $error,
        ], 'Mine påmeldinger');
    }
}
