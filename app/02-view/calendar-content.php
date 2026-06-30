<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $competitions */
/** @var array<string, mixed>|null $season */
/** @var string|null $error */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<section class="card">
    <h1>Stevnekalender</h1>
    <?php if ($season !== null): ?>
        <p class="muted">Sesong: <?= $h((string) ($season['name'] ?? '')) ?> (<?= $h((string) ($season['year'] ?? '')) ?>)</p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="status-bad"><?= $h($error) ?></p>
    <?php elseif ($competitions === []): ?>
        <p class="muted">Ingen kommende stevner akkurat nå.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Dato</th>
                    <th>Stevne</th>
                    <th>Sted</th>
                    <th>Arrangør</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($competitions as $comp): ?>
                    <?php if (!is_array($comp)) { continue; } ?>
                    <tr>
                        <td><?= $h((string) ($comp['competition_date'] ?? '')) ?></td>
                        <td><a href="/calendar/<?= (int) ($comp['id'] ?? 0) ?>"><?= $h((string) ($comp['name'] ?? '')) ?></a></td>
                        <td><?= $h((string) ($comp['location'] ?? '')) ?></td>
                        <td><?= $h((string) ($comp['organizer_name'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
