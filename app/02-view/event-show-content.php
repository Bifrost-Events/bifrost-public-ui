<?php

declare(strict_types=1);

/** @var array<string, mixed> $event */
/** @var array<string, mixed>|null $series */
/** @var list<array<string, mixed>> $breadcrumb */
/** @var array<string, mixed>|null $space */
/** @var array<string, array{singular: string, plural: string}> $labels */
/** @var bool $has_v3_results */
/** @var string|null $results_url */
/** @var string $registration_flow */
/** @var bool $logged_in */
/** @var array<string, mixed>|null $registration_me */
/** @var array{type: string, message: string, errors: array<string, string>}|null $flash */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$eventSingular = (string) ($labels['event']['singular'] ?? 'Arrangement');
$eventPlural = (string) ($labels['event']['plural'] ?? 'Arrangementer');
$seriesLabel = (string) ($labels['series']['singular'] ?? 'Serie');
$subseriesLabel = (string) ($labels['subseries']['singular'] ?? 'Underserie');
$spaceLabel = (string) ($labels['event_space']['singular'] ?? 'Event Space');

$owner = is_array($event['owner_organization'] ?? null) ? $event['owner_organization'] : null;
?>
<section class="card">
    <nav class="breadcrumb muted" aria-label="Brødsmule">
        <a href="/calendar"><?= $h($eventPlural) ?></a>
        <?php foreach ($breadcrumb as $crumb): ?>
            <?php if (!is_array($crumb)) { continue; } ?>
            <?php $crumbId = (int) ($crumb['id'] ?? $crumb['series_id'] ?? 0); ?>
            <span aria-hidden="true"> → </span>
            <?php if ($crumbId > 0): ?>
                <a href="/serier/<?= $crumbId ?>"><?= $h((string) ($crumb['name'] ?? '')) ?></a>
            <?php else: ?>
                <span><?= $h((string) ($crumb['name'] ?? '')) ?></span>
            <?php endif; ?>
        <?php endforeach; ?>
        <span aria-hidden="true"> → </span>
        <span><?= $h((string) ($event['name'] ?? '')) ?></span>
    </nav>

    <p class="muted"><?= $h($eventSingular) ?></p>
    <?php if (empty($hide_page_title)): ?>
        <h1><?= $h((string) ($event['name'] ?? '')) ?></h1>
    <?php endif; ?>

    <?php if (!empty($event['starts_at'])): ?>
        <p><strong>Tid:</strong> <?= $h((string) $event['starts_at']) ?><?php
            if (!empty($event['ends_at'])):
                ?> – <?= $h((string) $event['ends_at']) ?><?php
            endif;
        ?></p>
    <?php endif; ?>

    <?php if (!empty($event['location_name'])): ?>
        <p><strong>Sted:</strong> <?= $h((string) $event['location_name']) ?></p>
    <?php endif; ?>

    <?php if ($owner !== null && ($owner['name'] ?? '') !== ''): ?>
        <p><strong>Arrangør:</strong> <?= $h((string) $owner['name']) ?></p>
    <?php endif; ?>

    <?php if (is_array($space) && ($space['name'] ?? '') !== ''): ?>
        <p class="muted"><?= $h($spaceLabel) ?>: <?= $h((string) $space['name']) ?></p>
    <?php endif; ?>

    <?php if (is_array($series)): ?>
        <?php
        $seriesType = (string) ($series['series_type'] ?? '');
        $seriesTypeLabel = in_array($seriesType, ['round', 'day', 'stage'], true) ? $subseriesLabel : $seriesLabel;
        $seriesId = (int) ($series['id'] ?? 0);
        ?>
        <p>
            <strong><?= $h($seriesTypeLabel) ?>:</strong>
            <?php if ($seriesId > 0): ?>
                <a href="/serier/<?= $seriesId ?>"><?= $h((string) ($series['name'] ?? '')) ?></a>
            <?php else: ?>
                <?= $h((string) ($series['name'] ?? '')) ?>
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <?php if (!empty($event['registration_opens_at']) || !empty($event['registration_closes_at'])): ?>
        <p class="muted">
            Påmeldingsperiode:
            <?php if (!empty($event['registration_opens_at'])): ?>
                fra <?= $h((string) $event['registration_opens_at']) ?>
            <?php endif; ?>
            <?php if (!empty($event['registration_closes_at'])): ?>
                til <?= $h((string) $event['registration_closes_at']) ?>
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <?php if (($event['max_participants'] ?? null) !== null): ?>
        <p class="muted">Kapasitet: <?= (int) $event['max_participants'] ?></p>
    <?php endif; ?>

    <?php if (!empty($event['status'])): ?>
        <p class="muted">Status: <?= $h((string) $event['status']) ?></p>
    <?php endif; ?>

    <?php if (!empty($event['description'])): ?>
        <div class="prose"><?= nl2br($h((string) $event['description'])) ?></div>
    <?php endif; ?>

    <?php
    $flow = (string) ($registration_flow ?? 'v3');
    $isLoggedIn = (bool) ($logged_in ?? false);
    $regMe = is_array($registration_me ?? null) ? $registration_me : null;
    $eventId = (int) ($event['id'] ?? $event['event_id'] ?? 0);
    ?>
    <div class="registration-section">
        <h2>Påmelding</h2>
        <?php if (is_array($flash) && ($flash['message'] ?? '') !== ''): ?>
            <p class="<?= ($flash['type'] ?? '') === 'error' ? 'status-bad' : 'muted' ?>"><?= $h((string) $flash['message']) ?></p>
        <?php endif; ?>

        <?php if ($flow === 'jaktfelt_v3'): ?>
            <?php
            $jfSlots = is_array($jaktfelt_slots ?? null) ? $jaktfelt_slots : null;
            $jfRegs = is_array($regMe['registrations'] ?? null) ? $regMe['registrations'] : [];
            ?>
            <?php if (!$isLoggedIn): ?>
                <p class="muted">Logg inn for å melde på med lag og plass.</p>
                <p><a class="button" href="/auth/login?return_to=<?= rawurlencode('/arrangementer/' . $eventId) ?>">Logg inn for å melde på</a></p>
            <?php else: ?>
                <?php if ($jfRegs !== []): ?>
                    <h3>Mine påmeldinger</h3>
                    <?php foreach ($jfRegs as $jr): ?>
                        <?php if (!is_array($jr)) { continue; } ?>
                        <?php $slot = is_array($jr['slot'] ?? null) ? $jr['slot'] : null; ?>
                        <div class="registration-person-row">
                            <p>
                                <strong><?= $h((string) ($jr['person_display_name'] ?? '')) ?></strong>
                                — <?= $h((string) ($jr['registration_status'] ?? '')) ?>
                                <?php if (!empty($jr['class_name'])): ?> · klasse <?= $h((string) $jr['class_name']) ?><?php endif; ?>
                                <?php if ($slot !== null): ?>
                                    · lag <?= (int) ($slot['slot_number'] ?? 0) ?>
                                    <?php if (!empty($slot['starts_at'])): ?> (<?= $h((string) $slot['starts_at']) ?>)<?php endif; ?>
                                    · plass <?= (int) ($slot['position_number'] ?? 0) ?>
                                <?php endif; ?>
                            </p>
                            <?php if (($jr['registration_status'] ?? '') === 'confirmed' || ($jr['registration_status'] ?? '') === 'pending'): ?>
                                <form method="post" action="/arrangementer/<?= $eventId ?>/avmelding" style="display:inline"
                                      onsubmit="return confirm('Avmelde? Plassen frigjøres.');">
                                    <input type="hidden" name="registration_flow" value="jaktfelt_v3">
                                    <input type="hidden" name="registration_id" value="<?= (int) ($jr['registration_id'] ?? 0) ?>">
                                    <button type="submit" class="button">Avmeld</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <h3>Ny påmelding</h3>
                <?php
                $pickerPeople = [];
                $picker = (new \App\Service\PersonPickerService())->forCurrentUser();
                if ($picker['ok'] ?? false) {
                    $pickerPeople = is_array($picker['people'] ?? null) ? $picker['people'] : [];
                }
                ?>
                <?php if ($pickerPeople === []): ?>
                    <p class="muted">Ingen personer tilgjengelig for påmelding.</p>
                <?php else: ?>
                    <form method="post" action="/arrangementer/<?= $eventId ?>/pamelding" class="jaktfelt-register-form">
                        <input type="hidden" name="registration_flow" value="jaktfelt_v3">
                        <label for="person_id">Person</label>
                        <select id="person_id" name="person_id" required>
                            <?php foreach ($pickerPeople as $pp): ?>
                                <option value="<?= (int) ($pp['person_id'] ?? 0) ?>"><?= $h((string) ($pp['display_name'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label for="class_name">Klasse</label>
                        <input type="text" id="class_name" name="class_name" required placeholder="f.eks. Åpen voksen">
                        <label for="class_key">Klasse-nøkkel (valgfritt)</label>
                        <input type="text" id="class_key" name="class_key" placeholder="apen_voksen">
                        <label for="slot_position_id">Lag / plass</label>
                        <select id="slot_position_id" name="slot_position_id" required>
                            <option value="">Velg ledig plass</option>
                            <?php foreach (($jfSlots['slots'] ?? []) as $slot): ?>
                                <?php if (!is_array($slot)) { continue; } ?>
                                <?php foreach (($slot['positions'] ?? []) as $pos): ?>
                                    <?php if (!is_array($pos) || empty($pos['available'])) { continue; } ?>
                                    <option value="<?= (int) ($pos['slot_position_id'] ?? 0) ?>">
                                        Lag <?= (int) ($slot['slot_number'] ?? 0) ?>
                                        <?php if (!empty($slot['starts_at'])): ?> · <?= $h((string) $slot['starts_at']) ?><?php endif; ?>
                                        · plass <?= (int) ($pos['position_number'] ?? 0) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </select>
                        <p style="margin-top:.75rem;"><button type="submit" class="button">Meld på</button></p>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        <?php elseif (!$isLoggedIn): ?>
            <p class="muted">Logg inn for å melde på <?= $h(mb_strtolower($eventSingular)) ?>.</p>
            <p><a class="button" href="/auth/login?return_to=<?= rawurlencode('/arrangementer/' . $eventId) ?>">Logg inn for å melde på</a></p>
        <?php elseif ($regMe === null): ?>
            <p class="status-bad">Kunne ikke hente påmeldingsstatus. Prøv å logge inn på nytt.</p>
        <?php else: ?>
            <?php
            $evMeta = is_array($regMe['event'] ?? null) ? $regMe['event'] : [];
            $open = (bool) ($regMe['registration_open'] ?? false);
            ?>
            <p class="muted">
                <?php if (!empty($evMeta['registration_opens_at']) || !empty($evMeta['registration_closes_at'])): ?>
                    Frist:
                    <?php if (!empty($evMeta['registration_opens_at'])): ?>fra <?= $h((string) $evMeta['registration_opens_at']) ?> <?php endif; ?>
                    <?php if (!empty($evMeta['registration_closes_at'])): ?>til <?= $h((string) $evMeta['registration_closes_at']) ?><?php endif; ?>
                    ·
                <?php endif; ?>
                <?php if (($evMeta['max_participants'] ?? null) !== null): ?>
                    Kapasitet: <?= (int) $evMeta['active_registrations'] ?> / <?= (int) $evMeta['max_participants'] ?>
                    <?php if (($evMeta['spots_remaining'] ?? null) !== null): ?>
                        (<?= (int) $evMeta['spots_remaining'] ?> ledige)
                    <?php endif; ?>
                <?php else: ?>
                    Ingen kapasitetsgrense
                <?php endif; ?>
            </p>
            <?php if (!$open): ?>
                <p class="muted">Påmelding er ikke åpen for dette arrangementet akkurat nå.</p>
            <?php endif; ?>

            <?php foreach (($regMe['people'] ?? []) as $personRow): ?>
                <?php if (!is_array($personRow)) { continue; } ?>
                <?php
                $pid = (int) ($personRow['person_id'] ?? 0);
                $reg = is_array($personRow['registration'] ?? null) ? $personRow['registration'] : null;
                $status = is_array($reg) ? (string) ($reg['registration_status'] ?? '') : '';
                ?>
                <div class="registration-person-row">
                    <p>
                        <strong><?= $h((string) ($personRow['display_name'] ?? '')) ?></strong>
                        <?php if (!empty($personRow['is_self'])): ?>
                            <span class="muted">(meg selv)</span>
                        <?php else: ?>
                            <span class="muted">(representert)</span>
                        <?php endif; ?>
                        <?php if ($status !== ''): ?>
                            — status: <?= $h($status) ?>
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($personRow['can_register']) && $open): ?>
                        <form method="post" action="/arrangementer/<?= $eventId ?>/pamelding" style="display:inline">
                            <input type="hidden" name="person_id" value="<?= $pid ?>">
                            <button type="submit" class="button">Meld på</button>
                        </form>
                    <?php elseif (!empty($personRow['can_cancel'])): ?>
                        <form method="post" action="/arrangementer/<?= $eventId ?>/avmelding" style="display:inline"
                              onsubmit="return confirm('Avmelde denne personen?');">
                            <input type="hidden" name="registration_id" value="<?= (int) ($reg['registration_id'] ?? 0) ?>">
                            <button type="submit" class="button">Avmeld</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php
    $hasV3Results = (bool) ($has_v3_results ?? false);
    $resultsUrl = is_string($results_url ?? null) ? $results_url : null;
    ?>
    <div class="results-section">
        <h2>Resultater</h2>
        <?php if ($hasV3Results && $resultsUrl !== null): ?>
            <p><a class="button" href="<?= $h($resultsUrl) ?>">Se resultater</a></p>
        <?php else: ?>
            <p class="muted">Ingen publiserte resultater ennå.</p>
            <?php if ($resultsUrl !== null): ?>
                <p><a href="<?= $h($resultsUrl) ?>">Åpne resultatside</a></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
