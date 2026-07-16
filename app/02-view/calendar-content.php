<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $competitions */
/** @var array<string, mixed>|null $season */
/** @var array<string, array{singular: string, plural: string}> $labels */
/** @var string|null $error */
/** @var string $series_label */
/** @var string $event_label_plural */
/** @var string $event_label_singular */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$seriesLabel = $series_label ?? (string) ($labels['series']['singular'] ?? 'Serie');
$eventPlural = $event_label_plural ?? (string) ($labels['event']['plural'] ?? 'Arrangementer');
$eventSingular = $event_label_singular ?? (string) ($labels['event']['singular'] ?? 'Arrangement');
?>
<section class="card">
    <h1><?= $h($eventPlural) ?></h1>
    <?php if ($season !== null): ?>
        <p class="muted">
            <?= $h($seriesLabel) ?>:
            <?php
            $seasonUrl = $season['detail_url'] ?? null;
            $seasonName = (string) ($season['name'] ?? '');
            if (is_string($seasonUrl) && $seasonUrl !== ''):
            ?>
                <a href="<?= $h($seasonUrl) ?>"><?= $h($seasonName) ?></a>
            <?php else: ?>
                <?= $h($seasonName) ?>
            <?php endif; ?>
            <?php
            $year = $season['year'] ?? null;
            if ($year !== null && $year !== ''):
                ?> (<?= $h((string) $year) ?>)<?php
            endif;
            ?>
        </p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="status-bad"><?= $h($error) ?></p>
        <p class="muted">Kalenderen hentes fra Bifrost Events (V3). Det er ingen automatisk fallback til V2-kalender.</p>
    <?php elseif ($competitions === []): ?>
        <p class="muted">Ingen kommende <?= $h(mb_strtolower($eventPlural)) ?> akkurat nå.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Dato</th>
                    <th><?= $h($eventSingular) ?></th>
                    <th>Sted</th>
                    <th>Arrangør</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($competitions as $comp): ?>
                    <?php if (!is_array($comp)) { continue; } ?>
                    <tr>
                        <td><?= $h((string) ($comp['competition_date'] ?? $comp['starts_at'] ?? '')) ?></td>
                        <td>
                            <?php
                            $name = (string) ($comp['name'] ?? '');
                            $detailUrl = $comp['detail_url'] ?? null;
                            if (is_string($detailUrl) && $detailUrl !== ''):
                            ?>
                                <a href="<?= $h($detailUrl) ?>"><?= $h($name) ?></a>
                            <?php else: ?>
                                <?= $h($name) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= $h((string) ($comp['location'] ?? '')) ?></td>
                        <td><?= $h((string) ($comp['organizer_name'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
