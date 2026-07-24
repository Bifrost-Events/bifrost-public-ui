<?php

declare(strict_types=1);

/**
 * Offentlig hovedmeny. Synlighet styres med feature-flagg per tenant (senere).
 *
 * @return list<array{id: string, label: string, url: string, feature?: string}>
 */
return [
    'main' => [
        ['id' => 'nav_calendar', 'label' => 'Stevnekalender', 'url' => '/calendar'],
        ['id' => 'nav_results', 'label' => 'Resultater', 'url' => '/results'],
        ['id' => 'nav_standings', 'label' => 'Sammenlagt', 'url' => '/sammenlagt', 'feature' => 'standings'],
        ['id' => 'nav_archive', 'label' => 'Arkiv', 'url' => '/arkiv', 'feature' => 'archive'],
        ['id' => 'nav_about', 'label' => 'Om', 'url' => '/om'],
    ],
    'user' => [
        ['id' => 'user_profile', 'label' => 'Min profil', 'url' => '/min-side/profil'],
        ['id' => 'user_people', 'label' => 'Representerte personer', 'url' => '/min-side/personer'],
        ['id' => 'user_signups', 'label' => 'Mine påmeldinger', 'url' => '/min-side/pameldinger'],
        ['id' => 'user_logout', 'label' => 'Logg ut', 'url' => '/auth/logout', 'class' => 'user-menu-logout'],
    ],
];
