<?php

declare(strict_types=1);

/** @var string $step */
/** @var array<string, mixed>|null $participant_candidate */
/** @var array<string, mixed>|null $created_participant */
/** @var array<string, mixed>|null $user */
/** @var string $onboarding_welcome */
/** @var string $arrangor_portal_url */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$participantsUrl = '/min-side/deltakere';
$signupsUrl = '/min-side/pameldinger';
$calendarUrl = '/calendar';
?>
<section class="onboarding">
    <h2><?= $h($onboarding_welcome) ?></h2>

    <?php if ($step === 'participant'): ?>
        <p>Vi sjekker om det finnes en deltaker som matcher opplysningene dine.</p>
        <div class="onboarding-cards onboarding-cards--single">
            <?php if ($participant_candidate !== null): ?>
                <article class="onboarding-card">
                    <?php $isMine = !empty($participant_candidate['is_mine']); ?>
                    <h3><?= $isMine ? 'Vi fant deltakeren din' : 'Vi fant en mulig match' ?></h3>
                    <?php if (!$isMine): ?>
                        <p>Det ser ut som det finnes en deltaker som matcher navnet og telefonen din. Ønsker du å be om å overta den?</p>
                    <?php endif; ?>
                    <p><strong><?= $h((string) ($participant_candidate['name'] ?? '')) ?></strong></p>
                    <ul class="onboarding-participant-details">
                        <?php if (!empty($participant_candidate['jaktfelt_id'])): ?>
                            <li>Jaktfelt-ID: <code><?= $h((string) $participant_candidate['jaktfelt_id']) ?></code></li>
                        <?php endif; ?>
                    </ul>
                    <div class="onboarding-actions">
                        <?php if ($isMine): ?>
                            <a class="onboarding-btn primary" href="/onboarding?step=done">Fortsett</a>
                        <?php else: ?>
                            <form method="post" action="/onboarding/participants/<?= (int) ($participant_candidate['id'] ?? 0) ?>/claim">
                                <input type="hidden" name="return_to" value="/onboarding?step=done">
                                <button type="submit" class="onboarding-btn primary">Ja, be om å overta</button>
                            </form>
                        <?php endif; ?>
                        <a class="onboarding-btn secondary" href="<?= $h($participantsUrl) ?>">Mine deltakere</a>
                    </div>
                </article>
            <?php elseif ($created_participant !== null): ?>
                <article class="onboarding-card">
                    <h3>Vi opprettet en deltaker for deg</h3>
                    <p><strong><?= $h((string) ($created_participant['name'] ?? '')) ?></strong></p>
                    <div class="onboarding-actions">
                        <a class="onboarding-btn primary" href="/onboarding?step=done">Fortsett</a>
                    </div>
                </article>
            <?php else: ?>
                <article class="onboarding-card">
                    <p class="onboarding-muted">Ingen deltaker ble funnet eller opprettet automatisk. Du kan opprette deltaker på min side.</p>
                    <a class="onboarding-btn primary" href="<?= $h($participantsUrl) ?>">Gå til mine deltakere</a>
                </article>
            <?php endif; ?>
        </div>
    <?php elseif ($step === 'done'): ?>
        <p>Du er klar som deltaker.</p>
        <div class="onboarding-actions">
            <a class="onboarding-btn primary" href="<?= $h($calendarUrl) ?>">Se stevnekalender</a>
            <a class="onboarding-btn secondary" href="<?= $h($signupsUrl) ?>">Mine påmeldinger</a>
        </div>
        <?php if ($arrangor_portal_url !== ''): ?>
            <p class="onboarding-muted" style="margin-top: 1.5em;">Er du arrangør? <a href="<?= $h($arrangor_portal_url) ?>">Gå til arrangørportalen</a></p>
        <?php endif; ?>
    <?php else: ?>
        <p><a class="onboarding-btn primary" href="/onboarding?step=participant">Start deltaker-oppsett</a></p>
    <?php endif; ?>
</section>
