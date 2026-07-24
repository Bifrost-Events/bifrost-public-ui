<?php

declare(strict_types=1);

/** @var array<string, mixed> $user */
/** @var array{ok: bool, error: string|null, people: list<array<string, mixed>>, selected_person_id: int|null, selected: array<string, mixed>|null} $picker */
/** @var string $auth_source */
/** @var array{type: string, message: string, errors: array<string, string>}|null $flash */
/** @var string $arrangor_portal_url */
/** @var bool $focus_people */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$person = is_array($user['person'] ?? null) ? $user['person'] : null;
$displayName = (string) ($user['name'] ?? ($person['display_name'] ?? 'Bruker'));
$selected = is_array($picker['selected'] ?? null) ? $picker['selected'] : null;
$arrangorPortalUrl = trim((string) ($arrangor_portal_url ?? ''));
$focusPeople = !empty($focus_people);

$firstName = (string) ($person['first_name'] ?? '');
$lastName = (string) ($person['last_name'] ?? '');
if ($firstName === '' && $displayName !== '') {
    $parts = preg_split('/\s+/', $displayName, 2) ?: [];
    $firstName = (string) ($parts[0] ?? '');
    $lastName = (string) ($parts[1] ?? $lastName);
}
?>
<section class="card">
    <?php if (empty($hide_page_title)): ?>
        <h1>Min side</h1>
    <?php endif; ?>
    <?php if (is_array($flash) && ($flash['message'] ?? '') !== ''): ?>
        <p class="<?= ($flash['type'] ?? '') === 'error' ? 'status-bad' : 'muted' ?>"><?= $h((string) $flash['message']) ?></p>
    <?php endif; ?>
    <p class="muted">Innlogget via Bifrost.</p>
    <dl class="profile-dl">
        <dt>Navn</dt>
        <dd><?= $h($displayName) ?></dd>
        <dt>E-post</dt>
        <dd><?= $h((string) ($user['email'] ?? ($person['email'] ?? ''))) ?></dd>
        <dt>Bruker-ID</dt>
        <dd><?= (int) ($user['user_id'] ?? 0) ?></dd>
        <dt>Person-ID</dt>
        <dd><?= (int) ($user['person_id'] ?? 0) ?></dd>
    </dl>
</section>

<section class="card">
    <h2>Rediger profil</h2>
    <p class="muted">Oppdater dine egne personopplysninger.</p>
    <form method="post" action="/min-side/profil" class="stack-form">
        <input type="hidden" name="person_id" value="<?= (int) ($user['person_id'] ?? 0) ?>">
        <label>Fornavn <input type="text" name="first_name" required value="<?= $h($firstName) ?>"></label>
        <label>Etternavn <input type="text" name="last_name" required value="<?= $h($lastName) ?>"></label>
        <label>Visningsnavn <input type="text" name="display_name" value="<?= $h((string) ($person['display_name'] ?? $displayName)) ?>"></label>
        <label>Fødselsdato <input type="date" name="birth_date" value="<?= $h((string) ($person['birth_date'] ?? '')) ?>"></label>
        <label>E-post <input type="email" name="email" value="<?= $h((string) ($person['email'] ?? $user['email'] ?? '')) ?>"></label>
        <label>Telefon <input type="text" name="phone" value="<?= $h((string) ($person['phone'] ?? $user['phone'] ?? '')) ?>"></label>
        <button type="submit" class="button">Lagre profil</button>
    </form>
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

<section class="card"<?= $focusPeople ? ' id="personer"' : '' ?>>
    <h2>Representerte personer</h2>
    <p class="muted">Velg hvem du handler på vegne av, eller opprett en ny person under.</p>
    <?php
    $people = is_array($picker['people'] ?? null) ? $picker['people'] : [];
    $selectedId = $picker['selected_person_id'] ?? null;
    include __DIR__ . '/partials/_person_picker.php';
    ?>
</section>

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
    <h2>Arrangør?</h2>
    <p class="muted">Representerer du en arrangør?</p>
    <p>
        <a class="button" href="<?= $h($arrangorPortalUrl !== '' ? $arrangorPortalUrl : '#') ?>">
            Gå til arrangørportalen
        </a>
    </p>
</section>

<section class="card">
    <h2>Hybrid</h2>
    <p class="muted">
        Påmelding og «Mine deltakere» bruker fortsatt V2 midlertidig.
        <a href="/min-side/deltakere">Mine deltakere (V2)</a> ·
        <a href="/min-side/pameldinger">Mine påmeldinger</a>
    </p>
</section>
