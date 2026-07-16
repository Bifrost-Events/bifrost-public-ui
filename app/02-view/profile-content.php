<?php

declare(strict_types=1);

/** @var array<string, mixed> $user */
/** @var array{ok: bool, error: string|null, people: list<array<string, mixed>>, selected_person_id: int|null, selected: array<string, mixed>|null} $picker */
/** @var string $auth_source */
/** @var array{type: string, message: string, errors: array<string, string>}|null $flash */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$person = is_array($user['person'] ?? null) ? $user['person'] : null;
$displayName = (string) ($user['name'] ?? ($person['display_name'] ?? 'Bruker'));
$selected = is_array($picker['selected'] ?? null) ? $picker['selected'] : null;
?>
<section class="card">
    <h1>Min side</h1>
    <?php if (is_array($flash) && ($flash['message'] ?? '') !== ''): ?>
        <p class="<?= ($flash['type'] ?? '') === 'error' ? 'status-bad' : 'muted' ?>"><?= $h((string) $flash['message']) ?></p>
    <?php endif; ?>
    <p class="muted">Innlogget via <?= $h($auth_source === 'v3' ? 'V3 (Bifrost Admin Core)' : 'V2 (midlertidig)') ?>.</p>
    <dl class="profile-dl">
        <dt>Navn</dt>
        <dd><?= $h($displayName) ?></dd>
        <dt>E-post</dt>
        <dd><?= $h((string) ($user['email'] ?? '')) ?></dd>
        <dt>Bruker-ID</dt>
        <dd><?= (int) ($user['user_id'] ?? 0) ?></dd>
        <dt>Person-ID</dt>
        <dd><?= (int) ($user['person_id'] ?? 0) ?></dd>
    </dl>
</section>

<?php if ($selected !== null): ?>
<section class="card">
    <h2>Handler på vegne av</h2>
    <p>
        <strong><?= $h((string) ($selected['label'] ?? $selected['display_name'] ?? '')) ?></strong>
        <?php if (!empty($selected['is_self'])): ?>
            <span class="muted">(deg selv)</span>
        <?php else: ?>
            <span class="muted">(<?= $h((string) ($selected['relationship_type'] ?? 'representert')) ?>)</span>
        <?php endif; ?>
    </p>
</section>
<?php endif; ?>

<?php
$people = is_array($picker['people'] ?? null) ? $picker['people'] : [];
$selectedId = $picker['selected_person_id'] ?? null;
include __DIR__ . '/partials/_person_picker.php';
?>

<section class="card">
    <h2>Opprett representert person</h2>
    <p class="muted">Oppretter en ny person og kobler den til din bruker (f.eks. foresatt).</p>
    <form method="post" action="/min-side/personer" class="stack-form">
        <label>Fornavn <input type="text" name="first_name" required></label>
        <label>Etternavn <input type="text" name="last_name" required></label>
        <label>Fødselsdato <input type="date" name="birth_date"></label>
        <label>E-post <input type="email" name="email"></label>
        <label>Telefon <input type="text" name="phone"></label>
        <label>
            Relasjon
            <select name="relationship_type">
                <option value="guardian">Foresatt</option>
                <option value="manual">Manuell</option>
                <option value="delegated">Delegert</option>
            </select>
        </label>
        <label class="checkbox">
            <input type="checkbox" name="confirm" value="1" required>
            Jeg bekrefter at jeg har rett til å opprette og administrere denne personen.
        </label>
        <button type="submit" class="button">Opprett person</button>
    </form>
</section>

<section class="card">
    <h2>Hybrid</h2>
    <p class="muted">
        Påmelding og «Mine deltakere» bruker fortsatt V2 midlertidig.
        <a href="/min-side/deltakere">Mine deltakere (V2)</a> ·
        <a href="/min-side/pameldinger">Mine påmeldinger</a>
    </p>
</section>
