<?php

declare(strict_types=1);

/** @var string $step */
/** @var array<string, mixed>|null $user */
/** @var string $onboarding_welcome */
/** @var string $arrangor_portal_url */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$calendarUrl = '/calendar';
?>
<section class="onboarding">
    <h2><?= $h($onboarding_welcome) ?></h2>

    <p>Kontoen din er klar. Fullfør profilen og legg til representerte personer om nødvendig.</p>
    <ol class="onboarding-steps" style="padding-left:1.2rem;">
        <li>Konto opprettet</li>
        <li><a href="/min-side/profil">Fullfør profil</a></li>
        <li><a href="/min-side/personer">Representerte personer</a></li>
        <li>Ferdig</li>
    </ol>
    <div class="onboarding-actions" style="margin-top:1rem;">
        <a class="onboarding-btn primary" href="/min-side/profil">Gå til profil</a>
        <a class="onboarding-btn secondary" href="<?= $h($calendarUrl) ?>">Se stevnekalender</a>
    </div>
    <?php if ($arrangor_portal_url !== ''): ?>
        <p class="onboarding-muted" style="margin-top: 1.5em;">Representerer du en arrangør? <a href="<?= $h($arrangor_portal_url) ?>">Gå til arrangørportalen</a></p>
    <?php endif; ?>
</section>
