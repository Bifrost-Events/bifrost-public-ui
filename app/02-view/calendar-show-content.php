<?php

declare(strict_types=1);

/** @var array<string, mixed> $competition */
/** @var bool $registration_open */
/** @var bool $advance_registration_enabled */
/** @var list<array<string, mixed>> $slots */
/** @var list<array<string, mixed>> $registrations */
/** @var list<array<string, mixed>> $reserved_places */
/** @var list<array<string, mixed>> $participants */
/** @var list<int> $my_participant_ids */
/** @var array<string, mixed>|null $organizer */
/** @var bool $logged_in */
/** @var int $auth_user_id */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$compId = (int) ($competition['id'] ?? 0);
$antallFigurer = max(1, (int) ($competition['antall_skyttere_per_lag'] ?? 1));
$ownedParticipantIds = array_map(static fn (array $p): int => (int) ($p['id'] ?? 0), array_filter($participants, 'is_array'));
$slotsForParticipant = array_values(array_filter($slots, static fn (array $s): bool => empty($s['is_reserved'])));

$reservedSet = [];
foreach ($reserved_places as $rp) {
    if (!is_array($rp)) { continue; }
    $sn = (int) ($rp['slot_number'] ?? 0);
    $fn = (int) ($rp['figure_number'] ?? 0);
    if ($sn > 0 && $fn > 0) {
        $reservedSet[$sn . '_' . $fn] = true;
    }
}

$occupantBySlotFig = [];
foreach ($registrations as $r) {
    if (!is_array($r)) { continue; }
    $sid = (int) ($r['slot_id'] ?? 0);
    $fn = (int) ($r['figure_number'] ?? 0);
    if ($sid < 1 || $fn < 1) { continue; }
    $pid = (int) ($r['participant_id'] ?? 0);
    $regBy = $r['registered_by_user_id'] ?? null;
    $ownsOccupant = $pid > 0 && in_array($pid, $ownedParticipantIds, true);
    $registeredByCurrentUser = $regBy !== null && $auth_user_id > 0 && (int) $regBy === $auth_user_id;
    $occupantBySlotFig[$sid . '_' . $fn] = [
        'name' => trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')),
        'participant_id' => $pid,
        'can_unregister' => $ownsOccupant || $registeredByCurrentUser,
    ];
}

$registeredParticipantIds = $my_participant_ids;
$availableOwned = array_values(array_filter($participants, static function (array $p) use ($registeredParticipantIds): bool {
    return !in_array((int) ($p['id'] ?? 0), $registeredParticipantIds, true);
}));
?>
<section class="card">
    <p><a href="/calendar">← Tilbake til stevnekalender</a></p>

    <h1><?= $h((string) ($competition['name'] ?? 'Stevne')) ?></h1>
    <div class="competition-meta">
        <p><strong>Dato:</strong> <?= !empty($competition['competition_date']) ? $h(date('d.m.Y', strtotime((string) $competition['competition_date']))) : '–' ?></p>
        <p><strong>Sted:</strong> <?= $h((string) ($competition['location'] ?? '')) ?></p>
        <?php if (!empty($competition['antall_skyttere_per_lag']) && !empty($competition['antall_lag'])): ?>
            <p><strong>Oppsett:</strong> <?= (int) $competition['antall_skyttere_per_lag'] ?> skyttere per lag × <?= (int) $competition['antall_lag'] ?> lag</p>
        <?php endif; ?>
        <?php if (!empty($competition['description'])): ?>
            <p><?= nl2br($h((string) $competition['description'])) ?></p>
        <?php endif; ?>
        <?php if (!empty($competition['invitation_text'])): ?>
            <div class="invitation">
                <h2>Invitasjon</h2>
                <p><?= nl2br($h((string) $competition['invitation_text'])) ?></p>
            </div>
        <?php endif; ?>
        <?php if (is_array($organizer) && !empty($organizer['name'])): ?>
            <div class="organizer-contact">
                <h2>Kontakt arrangør</h2>
                <p><strong><?= $h((string) $organizer['name']) ?></strong></p>
                <?php if (!empty($organizer['email'])): ?>
                    <p>E-post: <a href="mailto:<?= $h((string) $organizer['email']) ?>"><?= $h((string) $organizer['email']) ?></a></p>
                <?php endif; ?>
                <?php if (!empty($organizer['phone'])): ?>
                    <p>Telefon: <?= $h((string) $organizer['phone']) ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="card">
    <h2>Påmelding</h2>

    <?php if (!$advance_registration_enabled): ?>
        <p class="status-bad"><strong>Dette stevnet tar ikke imot forhåndspåmelding på nett.</strong> Møt opp på stevnedagen eller kontakt arrangør.</p>
    <?php elseif (!$registration_open): ?>
        <p class="status-bad"><strong>Påmelding er stengt for dette stevnet.</strong></p>
    <?php elseif (!$logged_in): ?>
        <p>Du må <a href="/auth/login?return_to=/calendar/<?= $compId ?>">logge inn</a> for å melde deg på.</p>
    <?php elseif ($participants === []): ?>
        <p>Du har ingen deltakere ennå. <a href="/min-side/deltakere">Opprett en deltaker</a> først.</p>
    <?php elseif ($slots === []): ?>
        <p class="muted">Påmeldingsoppsett (lag/skiver) er ikke klart ennå.</p>
    <?php else: ?>
        <p class="muted">Trykk på en grønn «Ledig»-rute for å velge skive og deltaker.</p>

        <div class="slot-grid-wrap">
            <table class="data-table slot-grid">
                <thead>
                    <tr>
                        <th>Lag</th>
                        <th>Tid</th>
                        <?php for ($f = 1; $f <= $antallFigurer; $f++): ?>
                            <th>Skive <?= $f ?></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($slotsForParticipant as $s): ?>
                        <?php
                        if (!is_array($s)) { continue; }
                        $sid = (int) ($s['id'] ?? 0);
                        $sn = (int) ($s['slot_number'] ?? 0);
                        ?>
                        <tr>
                            <td>Lag <?= $sn ?></td>
                            <td><?= $h((string) ($s['start_time'] ?? '')) ?></td>
                            <?php for ($f = 1; $f <= $antallFigurer; $f++):
                                $key = $sid . '_' . $f;
                                $occ = $occupantBySlotFig[$key] ?? null;
                                $isReservedFig = isset($reservedSet[$sn . '_' . $f]);
                                ?>
                                <td class="slot-cell">
                                    <?php if ($isReservedFig): ?>
                                        <span class="slot-reserved">Reservert</span>
                                    <?php elseif ($occ !== null): ?>
                                        <span class="slot-taken"><?= $h($occ['name'] ?: 'Opptatt') ?></span>
                                        <?php if ($occ['can_unregister'] && $occ['participant_id'] > 0): ?>
                                            <form method="post" action="/calendar/<?= $compId ?>/unregister" class="inline-form">
                                                <input type="hidden" name="participant_id" value="<?= (int) $occ['participant_id'] ?>">
                                                <button type="submit" class="link-btn">Avmeld</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php elseif ($registration_open): ?>
                                        <button type="button" class="slot-free pick-slot"
                                                data-slot-id="<?= $sid ?>"
                                                data-figure="<?= $f ?>"
                                                data-slot-label="Lag <?= $sn ?>, skive <?= $f ?>">Ledig</button>
                                    <?php else: ?>
                                        <span class="muted">–</span>
                                    <?php endif; ?>
                                </td>
                            <?php endfor; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <dialog id="signup-dialog">
            <form method="post" action="/calendar/<?= $compId ?>/register" id="signup-form">
                <h3>Meld på</h3>
                <p id="signup-slot-label" class="muted"></p>
                <input type="hidden" name="slot_id" id="signup_slot_id">
                <input type="hidden" name="figure_number" id="signup_figure_number">
                <div class="form-group">
                    <label for="signup_participant_id">Deltaker *</label>
                    <select name="participant_id" id="signup_participant_id" required>
                        <option value="">Velg deltaker</option>
                        <?php foreach ($availableOwned as $p): ?>
                            <?php if (!is_array($p)) { continue; } ?>
                            <option value="<?= (int) ($p['id'] ?? 0) ?>">
                                <?= $h(trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($availableOwned === []): ?>
                    <p class="status-bad">Alle dine deltakere er allerede påmeldt dette stevnet.</p>
                <?php endif; ?>
                <div class="dialog-actions">
                    <button type="button" class="btn" id="signup-cancel">Avbryt</button>
                    <button type="submit" class="btn btn-primary" <?= $availableOwned === [] ? 'disabled' : '' ?>>Meld på</button>
                </div>
            </form>
        </dialog>
    <?php endif; ?>
</section>

<style>
.competition-meta p { margin: 0.35rem 0; }
.invitation, .organizer-contact { margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid var(--line); }
.invitation h2, .organizer-contact h2 { font-size: 1rem; margin: 0 0 0.5rem; }
.slot-grid td { vertical-align: middle; min-width: 5.5rem; }
.slot-free { background: #d4edda; color: #155724; border: 1px solid #b7dfc3; border-radius: 6px; padding: 0.35rem 0.5rem; cursor: pointer; width: 100%; font: inherit; }
.slot-free:hover { background: #c3e6cb; }
.slot-taken { display: block; font-size: 0.88rem; }
.slot-reserved { color: var(--muted); font-size: 0.88rem; }
.link-btn { background: none; border: none; color: var(--bad); cursor: pointer; padding: 0; font-size: 0.82rem; text-decoration: underline; }
.inline-form { margin-top: 0.2rem; }
#signup-dialog { border: none; border-radius: 8px; padding: 1.25rem; max-width: 400px; width: 90%; box-shadow: 0 8px 30px rgba(0,0,0,0.2); }
#signup-dialog::backdrop { background: rgba(0,0,0,0.45); }
.dialog-actions { display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1rem; }
</style>
<script>
(function () {
    const dialog = document.getElementById('signup-dialog');
    if (!dialog) return;
    const slotLabel = document.getElementById('signup-slot-label');
    const slotId = document.getElementById('signup_slot_id');
    const figure = document.getElementById('signup_figure_number');
    document.querySelectorAll('.pick-slot').forEach(function (btn) {
        btn.addEventListener('click', function () {
            slotId.value = btn.dataset.slotId || '';
            figure.value = btn.dataset.figure || '';
            slotLabel.textContent = btn.dataset.slotLabel || '';
            dialog.showModal();
        });
    });
    document.getElementById('signup-cancel')?.addEventListener('click', function () { dialog.close(); });
})();
</script>
