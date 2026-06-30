<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $signups */
/** @var string|null $error */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<section class="card">
    <h1>Mine påmeldinger</h1>
    <p class="muted">Kommende stevner du er påmeldt til.</p>

    <?php if ($error): ?>
        <p class="status-bad"><?= $h($error) ?></p>
    <?php elseif ($signups === []): ?>
        <p class="muted">Du har ingen aktive påmeldinger.</p>
        <p><a href="/calendar">Gå til stevnekalenderen</a> for å melde deg på.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Dato</th>
                    <th>Stevne</th>
                    <th>Deltaker</th>
                    <th>Lag / skive</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($signups as $s): ?>
                    <?php if (!is_array($s)) { continue; } ?>
                    <tr>
                        <td><?= $h((string) ($s['competition_date'] ?? '')) ?></td>
                        <td>
                            <?= $h((string) ($s['competition_name'] ?? '')) ?>
                            <?php if (!empty($s['location'])): ?>
                                <br><span class="muted"><?= $h((string) $s['location']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= $h(trim(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? ''))) ?></td>
                        <td>
                            Lag <?= (int) ($s['slot_number'] ?? 0) ?>
                            <?php if (!empty($s['start_time'])): ?> (<?= $h((string) $s['start_time']) ?>)<?php endif; ?>
                            · Skive <?= (int) ($s['figure_number'] ?? 0) ?>
                        </td>
                        <td><a href="/calendar/<?= (int) ($s['competition_id'] ?? 0) ?>">Vis stevne</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
