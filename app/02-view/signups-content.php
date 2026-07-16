<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $upcoming */
/** @var list<array<string, mixed>> $past */
/** @var string|null $error */
/** @var array{type: string, message: string, errors: array<string, string>}|null $flash */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$renderTable = static function (array $rows, bool $allowCancel) use ($h): void {
    if ($rows === []) {
        echo '<p class="muted">Ingen påmeldinger.</p>';

        return;
    }
    ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Dato</th>
                <th>Arrangement</th>
                <th>Person</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $s): ?>
                <?php if (!is_array($s)) { continue; } ?>
                <?php
                $event = is_array($s['event'] ?? null) ? $s['event'] : [];
                $eventId = (int) ($event['id'] ?? $s['event_id'] ?? 0);
                $status = (string) ($s['registration_status'] ?? '');
                $canCancel = $allowCancel && in_array($status, ['pending', 'confirmed', 'waitlisted'], true);
                ?>
                <tr>
                    <td><?= $h((string) ($event['starts_at'] ?? '–')) ?></td>
                    <td>
                        <?php if ($eventId > 0): ?>
                            <a href="/arrangementer/<?= $eventId ?>"><?= $h((string) ($event['name'] ?? '')) ?></a>
                        <?php else: ?>
                            <?= $h((string) ($event['name'] ?? '')) ?>
                        <?php endif; ?>
                        <?php if (!empty($event['location_name'])): ?>
                            <br><span class="muted"><?= $h((string) $event['location_name']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= $h((string) ($s['person_display_name'] ?? '')) ?></td>
                    <td><?= $h($status) ?></td>
                    <td>
                        <?php if ($canCancel): ?>
                            <form method="post" action="/min-side/pameldinger/avmeld" style="display:inline"
                                  onsubmit="return confirm('Avmelde?');">
                                <input type="hidden" name="registration_id" value="<?= (int) ($s['registration_id'] ?? 0) ?>">
                                <button type="submit" class="button">Avmeld</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
};
?>
<section class="card">
    <h1>Mine påmeldinger</h1>
    <p class="muted">V3-påmeldinger for deg og personer du representerer.</p>
    <?php if (is_array($flash) && ($flash['message'] ?? '') !== ''): ?>
        <p class="<?= ($flash['type'] ?? '') === 'error' ? 'status-bad' : 'muted' ?>"><?= $h((string) $flash['message']) ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="status-bad"><?= $h($error) ?></p>
    <?php endif; ?>
</section>

<section class="card">
    <h2>Kommende</h2>
    <?php $renderTable($upcoming, true); ?>
</section>

<section class="card">
    <h2>Tidligere</h2>
    <?php $renderTable($past, false); ?>
</section>

<section class="card">
    <p class="muted"><a href="/calendar">Gå til kalenderen</a> for å melde på flere arrangementer.</p>
</section>
