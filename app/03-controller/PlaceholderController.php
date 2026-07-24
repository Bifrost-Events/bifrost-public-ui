<?php

declare(strict_types=1);

namespace App\Controller;

use App\Support\CupConfigLoader;
use App\Support\PublicView;

final class PlaceholderController
{
    public function about(): array
    {
        $cup = CupConfigLoader::current();
        $content = is_array($cup['content'] ?? null) ? $cup['content'] : [];
        $about = trim((string) ($content['about_text'] ?? ''));
        $howTo = trim((string) ($content['how_to_participate_text'] ?? ''));
        $body = $about !== '' ? $about : 'Informasjon om cupen, regler og kontakt.';
        if ($howTo !== '') {
            $body .= "\n\n" . $howTo;
        }

        return PublicView::renderPlaceholder('Om cupen', $body);
    }

    public function howToParticipate(): array
    {
        $cup = CupConfigLoader::current();
        $content = is_array($cup['content'] ?? null) ? $cup['content'] : [];
        $text = trim((string) ($content['how_to_participate_text'] ?? ''));
        if ($text === '') {
            $text = 'Informasjon om hvordan du deltar kommer her.';
        }

        return PublicView::renderPlaceholder('Hvordan delta', $text);
    }

    public function organizerInfo(): array
    {
        $cup = CupConfigLoader::current();
        $content = is_array($cup['content'] ?? null) ? $cup['content'] : [];
        $text = trim((string) ($content['organizer_info_text'] ?? ''));
        $portal = CupConfigLoader::organizerPortalUrl($cup);
        if ($text === '') {
            $text = 'Informasjon for arrangører.';
        }

        $extras = [];
        if ($portal !== '') {
            $extras = [
                'cta_url' => $portal,
                'cta_label' => 'Gå til arrangørportalen',
            ];
        }

        return PublicView::renderPlaceholder('Arrangør', $text, $extras);
    }

    public function sponsor(): array
    {
        $cup = CupConfigLoader::current();
        $sponsors = is_array($cup['sponsors'] ?? null) ? $cup['sponsors'] : [];
        $lead = trim((string) ($sponsors['lead'] ?? ''));
        $footer = trim((string) ($sponsors['footer_text'] ?? ''));
        $body = $lead !== '' ? $lead : 'Sponsorvisning.';
        if ($footer !== '') {
            $body .= "\n\n" . $footer;
        }

        return PublicView::renderPlaceholder(
            (string) ($sponsors['heading'] ?? 'Sponsorer'),
            $body
        );
    }

    public function archive(): array
    {
        return PublicView::renderPlaceholder(
            'Arkiv',
            'Historiske sesonger og resultater kommer her.'
        );
    }

    public function finals(): array
    {
        $cup = CupConfigLoader::current();
        $content = is_array($cup['content'] ?? null) ? $cup['content'] : [];
        $text = trim((string) ($content['finals_text'] ?? ''));
        if ($text === '') {
            $text = 'Informasjon om finalehelg kommer her.';
        }

        return PublicView::renderPlaceholder('Finaler', $text);
    }
}
