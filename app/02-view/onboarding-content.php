<?php

declare(strict_types=1);

/** @var string $step */
/** @var list<array{id: string, label: string}> $steps */
/** @var int $current_step_number */
/** @var int $total_steps */
/** @var int $remaining_steps */
/** @var string|null $onboarding_mode */
/** @var bool $has_organizer_terms */
/** @var bool $has_organizer */
/** @var array{version: string, title: string, text: string} $organizer_agreement */
/** @var array<string, mixed>|null $participant_candidate */
/** @var array<string, mixed>|null $created_participant */
/** @var array<string, mixed> $summary */
/** @var array<string, mixed>|null $user */
/** @var string $onboarding_welcome */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$onboardingUrl = '/onboarding';
$participantsUrl = '/min-side/deltakere';
$signupsUrl = '/min-side/pameldinger';
$calendarUrl = '/calendar';
$nextAfterParticipant = ($onboarding_mode === 'both') ? ($onboardingUrl . '?step=organizer_terms') : ($onboardingUrl . '?step=done');

$userName = '';
if (is_array($user)) {
    $userName = trim((string) ($user['name'] ?? ''));
    if ($userName === '') {
        $userName = trim((string) ($user['email'] ?? ''));
    }
}
$prefillContactPerson = $userName !== '' ? $userName : '';
$prefillEmail = is_array($user) && !empty($user['email']) ? (string) $user['email'] : '';
$prefillPhone = is_array($user) && !empty($user['phone']) ? (string) $user['phone'] : '';
$organizerAgreementHtml = nl2br($h($organizer_agreement['text'] ?? ''));
?>
<section class="onboarding">
    <h2><?= $h($onboarding_welcome) ?></h2>

    <div class="onboarding-progress" role="status" aria-live="polite">
        <div class="onboarding-progress-top">
            <div class="onboarding-progress-title">Steg <?= (int) $current_step_number ?> av <?= (int) $total_steps ?></div>
            <div class="onboarding-progress-remaining"><?= (int) $remaining_steps ?> steg igjen</div>
        </div>
        <ol class="onboarding-progress-steps">
            <?php foreach ($steps as $idx => $s): ?>
                <?php
                $isDone = ($idx + 1) < $current_step_number;
                $isActive = ($idx + 1) === $current_step_number;
                ?>
                <li class="onboarding-step<?= $isDone ? ' done' : '' ?><?= $isActive ? ' active' : '' ?>">
                    <span class="dot" aria-hidden="true"></span>
                    <span class="label"><?= $h((string) ($s['label'] ?? '')) ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>

    <?php if ($step === 'choose'): ?>
        <p>Velg hva du vil gjøre videre. Du kan endre dette senere på min side.</p>
        <div class="onboarding-cards onboarding-cards--choose">
            <article class="onboarding-card">
                <h3>Bare deltaker</h3>
                <p>Opprett (eller finn) deltakeren din og kom i gang.</p>
                <form method="post" action="/onboarding/choose">
                    <input type="hidden" name="mode" value="shooter">
                    <button type="submit" class="onboarding-btn primary">Fortsett</button>
                </form>
            </article>
            <article class="onboarding-card">
                <h3>Både deltaker og arrangør</h3>
                <p>Først deltaker, deretter arrangør-oppsett.</p>
                <form method="post" action="/onboarding/choose">
                    <input type="hidden" name="mode" value="both">
                    <button type="submit" class="onboarding-btn primary">Fortsett</button>
                </form>
            </article>
        </div>
    <?php elseif ($step === 'participant'): ?>
        <p>Vi sjekker om det finnes en deltaker som matcher opplysningene dine.</p>
        <div class="onboarding-cards onboarding-cards--single">
            <?php if ($participant_candidate !== null): ?>
                <article class="onboarding-card">
                    <?php
                    $isMine = !empty($participant_candidate['is_mine']);
                    $summaryCreatedSame = (($summary['participant_status'] ?? null) === 'created')
                        && (int) ($summary['participant_id'] ?? 0) === (int) ($participant_candidate['id'] ?? 0);
                    ?>
                    <?php if ($isMine && $summaryCreatedSame): ?>
                        <h3>Vi opprettet en deltaker for deg</h3>
                        <p class="onboarding-muted">Denne ble opprettet automatisk basert på brukerprofilen din.</p>
                    <?php elseif ($isMine): ?>
                        <h3>Vi fant deltakeren din</h3>
                    <?php else: ?>
                        <h3>Vi fant en mulig match</h3>
                        <p>Det ser ut som det finnes en deltaker som matcher navnet og telefonen din. Ønsker du å be om å overta den?</p>
                    <?php endif; ?>
                    <p><strong><?= $h((string) ($participant_candidate['name'] ?? '')) ?></strong></p>
                    <ul class="onboarding-participant-details">
                        <?php if (!empty($participant_candidate['date_of_birth'])): ?>
                            <li>Fødselsdato: <?= $h((string) $participant_candidate['date_of_birth']) ?></li>
                        <?php endif; ?>
                        <?php if (!empty($participant_candidate['phone'])): ?>
                            <li>Telefon: <?= $h((string) $participant_candidate['phone']) ?></li>
                        <?php endif; ?>
                        <?php if (!empty($participant_candidate['jaktfelt_id'])): ?>
                            <li>Jaktfelt-ID: <code><?= $h((string) $participant_candidate['jaktfelt_id']) ?></code></li>
                        <?php endif; ?>
                    </ul>
                    <div class="onboarding-actions">
                        <?php if ($isMine): ?>
                            <a class="onboarding-btn primary" href="<?= $h($nextAfterParticipant) ?>">Fortsett</a>
                            <a class="onboarding-btn secondary" href="<?= $h($participantsUrl) ?>">Mine deltakere</a>
                        <?php else: ?>
                            <form method="post" action="/onboarding/participants/<?= (int) ($participant_candidate['id'] ?? 0) ?>/claim">
                                <input type="hidden" name="return_to" value="<?= $h($nextAfterParticipant) ?>">
                                <input type="hidden" name="onboarding_claim" value="1">
                                <button type="submit" class="onboarding-btn primary">Ja, be om å overta</button>
                            </form>
                            <a class="onboarding-btn secondary" href="<?= $h($nextAfterParticipant) ?>">Nei, fortsett</a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php elseif ($created_participant !== null): ?>
                <article class="onboarding-card">
                    <h3>Vi opprettet en deltaker for deg</h3>
                    <p><strong><?= $h((string) ($created_participant['name'] ?? '')) ?></strong></p>
                    <ul class="onboarding-participant-details">
                        <?php if (!empty($created_participant['date_of_birth'])): ?>
                            <li>Fødselsdato: <?= $h((string) $created_participant['date_of_birth']) ?></li>
                        <?php endif; ?>
                        <?php if (!empty($created_participant['phone'])): ?>
                            <li>Telefon: <?= $h((string) $created_participant['phone']) ?></li>
                        <?php endif; ?>
                        <?php if (!empty($created_participant['jaktfelt_id'])): ?>
                            <li>Jaktfelt-ID: <code><?= $h((string) $created_participant['jaktfelt_id']) ?></code></li>
                        <?php endif; ?>
                    </ul>
                    <div class="onboarding-actions">
                        <a class="onboarding-btn primary" href="<?= $h($nextAfterParticipant) ?>">Fortsett</a>
                        <a class="onboarding-btn secondary" href="<?= $h($participantsUrl) ?>">Mine deltakere</a>
                    </div>
                </article>
            <?php else: ?>
                <article class="onboarding-card">
                    <h3>Kunne ikke sjekke deltaker akkurat nå</h3>
                    <p>Prøv igjen om litt, eller gå til Mine deltakere.</p>
                    <div class="onboarding-actions">
                        <a class="onboarding-btn primary" href="<?= $h($onboardingUrl) ?>?step=participant">Prøv igjen</a>
                        <a class="onboarding-btn secondary" href="<?= $h($participantsUrl) ?>">Mine deltakere</a>
                    </div>
                </article>
            <?php endif; ?>
        </div>
    <?php elseif ($step === 'organizer_terms'): ?>
        <p>Før du kan opprette arrangør må du godta arrangøravtalen.</p>
        <div class="onboarding-cards onboarding-cards--single">
            <article class="onboarding-card">
                <h3>Arrangøravtale</h3>
                <?php if ($has_organizer_terms): ?>
                    <p class="onboarding-muted">Arrangøravtalen er allerede godkjent for din bruker.</p>
                    <div class="onboarding-actions">
                        <a class="onboarding-btn primary" href="<?= $h($onboardingUrl) ?>?step=organizer_create">Fortsett til opprettelse</a>
                        <a class="onboarding-btn secondary" href="<?= $h($onboardingUrl) ?>?step=done">Hopp over</a>
                    </div>
                <?php else: ?>
                    <div class="onboarding-agreement"><?= $organizerAgreementHtml ?></div>
                    <form method="post" action="/onboarding/organizer/accept-terms">
                        <input type="hidden" name="version" value="<?= $h($organizer_agreement['version'] ?? '') ?>">
                        <label class="onboarding-checkbox">
                            <input type="checkbox" name="accept" value="1" required>
                            Jeg godtar arrangøravtalen
                        </label>
                        <div class="onboarding-actions">
                            <button type="submit" class="onboarding-btn primary">Godta og fortsett</button>
                            <a class="onboarding-btn secondary" href="<?= $h($onboardingUrl) ?>?step=done">Hopp over</a>
                        </div>
                    </form>
                <?php endif; ?>
            </article>
        </div>
    <?php elseif ($step === 'organizer_create'): ?>
        <p>Opprett arrangør. Kontaktinfo er forhåndsutfylt fra brukeren din.</p>
        <div class="onboarding-cards onboarding-cards--single">
            <article class="onboarding-card">
                <h3>Opprett arrangør</h3>
                <?php if ($has_organizer): ?>
                    <p>Du har allerede minst én arrangør. Du kan likevel opprette flere.</p>
                <?php endif; ?>
                <form method="post" action="/onboarding/organizer/create">
                    <div class="onboarding-form-grid">
                        <label>Navn *<input type="text" name="name" required></label>
                        <label>Kontaktperson<input type="text" name="contact_person" value="<?= $h($prefillContactPerson) ?>"></label>
                        <label>E-post<input type="email" name="email" value="<?= $h($prefillEmail) ?>"></label>
                        <label>Telefon<input type="text" name="phone" value="<?= $h($prefillPhone) ?>"></label>
                        <label>Postnummer<input type="text" name="postal_code" maxlength="4" inputmode="numeric" pattern="\d{4}" placeholder="f.eks. 7800" autocomplete="postal-code"></label>
                        <label>Poststed<input type="text" name="city" placeholder="Poststed" autocomplete="address-level2"></label>
                        <label>Distrikter<input type="text" name="districts" placeholder="f.eks. #namdalen #Indre Namdal"></label>
                    </div>
                    <?php if (!$has_organizer_terms): ?>
                        <p class="onboarding-muted">Du må godta arrangøravtalen før du kan opprette arrangør. <a href="<?= $h($onboardingUrl) ?>?step=organizer_terms">Gå til avtale</a></p>
                        <button type="submit" class="onboarding-btn primary" disabled>Opprett arrangør</button>
                    <?php else: ?>
                        <button type="submit" class="onboarding-btn primary">Opprett arrangør</button>
                    <?php endif; ?>
                    <a class="onboarding-btn secondary" href="<?= $h($onboardingUrl) ?>?step=done">Hopp over</a>
                </form>
            </article>
        </div>
    <?php else: ?>
        <?php
        $did = [];
        $participantStatus = $summary['participant_status'] ?? null;
        $participantName = $summary['participant_name'] ?? null;
        $participantJaktfeltId = $summary['participant_jaktfelt_id'] ?? null;
        if ($participantStatus === 'created') {
            $did[] = 'Opprettet deltaker' . ($participantName ? ' (' . $participantName . ')' : '') . ($participantJaktfeltId ? ' – Jaktfelt-ID ' . $participantJaktfeltId : '');
        } elseif ($participantStatus === 'found_mine') {
            $did[] = 'Fant eksisterende deltaker' . ($participantName ? ' (' . $participantName . ')' : '');
        } elseif ($participantStatus === 'found_other') {
            $did[] = 'Fant mulig match på eksisterende deltaker' . ($participantName ? ' (' . $participantName . ')' : '');
        }
        if (!empty($summary['claim_requested'])) {
            $did[] = 'Sendte forespørsel om å overta deltakeren';
        }
        if (!empty($summary['organizer_terms_accepted'])) {
            $did[] = 'Godkjente arrangøravtalen';
        }
        if (!empty($summary['organizer_created_name'])) {
            $did[] = 'Opprettet arrangør "' . (string) $summary['organizer_created_name'] . '"';
        }
        $mode = $summary['mode'] ?? $onboarding_mode;
        ?>
        <p>Du er klar.</p>
        <div class="onboarding-cards onboarding-cards--single">
            <article class="onboarding-card">
                <h3>Oppsummering</h3>
                <?php if ($did !== []): ?>
                    <ul class="onboarding-summary">
                        <?php foreach ($did as $line): ?>
                            <li><?= $h($line) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>Onboarding er fullført.</p>
                <?php endif; ?>
                <p class="onboarding-muted">Hva vil du gjøre videre?</p>
                <div class="onboarding-actions">
                    <a class="onboarding-btn primary" href="<?= $h($participantsUrl) ?>">Mine deltakere</a>
                    <a class="onboarding-btn secondary" href="<?= $h($signupsUrl) ?>">Mine påmeldinger</a>
                    <a class="onboarding-btn secondary" href="<?= $h($calendarUrl) ?>">Stevnekalender</a>
                    <?php if ($mode === 'both'): ?>
                        <a class="onboarding-btn secondary" href="/min-side/profil">Min profil</a>
                    <?php endif; ?>
                </div>
            </article>
        </div>
    <?php endif; ?>
</section>
