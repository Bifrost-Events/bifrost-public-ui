<?php

declare(strict_types=1);

namespace App\Support;

final class RegistrationAgreements
{
    /** @return array{version: string, title: string, text: string} */
    public static function userAgreement(): array
    {
        $raw = Config::get('agreements.user', []);
        if (!is_array($raw)) {
            return ['version' => '1.0', 'title' => 'Brukeravtale', 'text' => ''];
        }

        return [
            'version' => (string) ($raw['version'] ?? '1.0'),
            'title' => (string) ($raw['title'] ?? 'Brukeravtale'),
            'text' => (string) ($raw['text'] ?? ''),
        ];
    }

    /** @return array{version: string, title: string, text: string} */
    public static function organizerAgreement(): array
    {
        $raw = Config::get('agreements.organizer', []);
        if (!is_array($raw)) {
            return ['version' => '1.0', 'title' => 'Arrangøravtale', 'text' => ''];
        }

        return [
            'version' => (string) ($raw['version'] ?? '1.0'),
            'title' => (string) ($raw['title'] ?? 'Arrangøravtale'),
            'text' => (string) ($raw['text'] ?? ''),
        ];
    }
}
