<?php

declare(strict_types=1);

namespace App\Controller;

use App\Support\PublicView;

final class PlaceholderController
{
    public function calendar(): array
    {
        return PublicView::renderPlaceholder(
            'Stevnekalender',
            'Kommende stevner vises her når public API er på plass i bifrost-backend.'
        );
    }

    public function results(): array
    {
        return PublicView::renderPlaceholder(
            'Resultater',
            'Stevneresultater vises her når public API er på plass i bifrost-backend.'
        );
    }

    public function standings(): array
    {
        return PublicView::renderPlaceholder(
            'Sammenlagt',
            'Cup-sammenlagt vises her når public API er på plass i bifrost-backend.'
        );
    }

    public function about(): array
    {
        return PublicView::renderPlaceholder(
            'Om cupen',
            'Informasjon om cupen, regler og kontakt kommer her.'
        );
    }

    public function sponsor(): array
    {
        return PublicView::renderPlaceholder(
            'Sponsorer',
            'Sponsorvisning kommer her.'
        );
    }

    public function archive(): array
    {
        return PublicView::renderPlaceholder(
            'Arkiv',
            'Historiske sesonger og resultater kommer her.'
        );
    }

    public function login(): array
    {
        return PublicView::renderPlaceholder(
            'Logg inn',
            'Deltaker-innlogging kobles til auth-service når backend støtter participant-login.'
        );
    }

    public function register(): array
    {
        return PublicView::renderPlaceholder(
            'Registrer deg',
            'Registrering kobles til auth-service når backend støtter participant-login.'
        );
    }

    public function myPage(string $section): array
    {
        $titles = [
            'profil' => 'Min profil',
            'deltakere' => 'Mine deltakere',
            'pameldinger' => 'Mine påmeldinger',
        ];
        $title = $titles[$section] ?? 'Min side';

        return PublicView::renderPlaceholder(
            $title,
            'Innlogget deltakerflate bygges i neste fase.'
        );
    }
}
