<?php

declare(strict_types=1);

/**
 * Avtaler som brukere må godkjenne ved registrering.
 */
return [
    'user' => [
        'version' => '1.0',
        'title' => 'Brukeravtale',
        'text' => "Ved å registrere deg godtar du vilkårene for bruk av denne tjenesten.\n\n"
            . "Du er ansvarlig for at opplysningene du oppgir er riktige. Vi behandler personopplysninger i tråd med personvernreglene og bruker dem til å levere tjenesten, administrere brukere og arrangere stevner.\n\n"
            . "Du kan når som helst be om innsyn eller sletting av dine data. Ved spørsmål, ta kontakt med arrangør.",
    ],
    'organizer' => [
        'version' => '1.0',
        'title' => 'Arrangøravtale',
        'text' => "Som arrangør forplikter du deg til å følge retningslinjene for arrangement av stevner.\n\n"
            . "Du er ansvarlig for at påmeldinger og resultater registreres korrekt, og at deltakeres personopplysninger behandles trygt. Arrangør har tilgang til å administrere egne stevner og tilknyttede data.\n\n"
            . "Ved brudd på avtalen kan arrangørrettigheter trekkes tilbake.",
    ],
];
