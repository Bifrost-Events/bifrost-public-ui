<?php

declare(strict_types=1);

/** @var array<string, mixed> $event */
/** @var array<string, array{singular: string, plural: string}> $labels */
/** @var bool $has_results */
/** @var list<array{key: string, label: string, rows: list<array<string, mixed>>}> $results_by_class */
/** @var string|null $v2_results_url */
/** @var string $event_url */
/** @var string|null $error */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$eventSingular = (string) ($labels['event']['singular'] ?? 'Arrangement');
?>
<section class="card">
    <p><a href="<?= $h($event_url) ?>">← Tilbake til <?= $h(mb_strtolower($eventSingular)) ?></a></p>
    <h1>Resultater</h1>
    <p class="muted"><?= $h((string) ($event['name'] ?? '')) ?></p>
    <?php if (!empty($event['starts_at'])): ?>
        <p class="muted"><?= $h((string) $event['starts_at']) ?><?php
            if (!empty($event['location_name'])):
                ?> · <?= $h((string) $event['location_name']) ?><?php
            endif;
        ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="status-bad"><?= $h($error) ?></p>
    <?php endif; ?>
</section>

<?php if (!$has_results): ?>
<section class="card">
    <p class="muted">Ingen publiserte resultater i V3 ennå.</p>
    <?php if (is_string($v2_results_url) && $v2_results_url !== ''): ?>
        <p data-hybrid="v2">
            <a class="button" href="<?= $h($v2_results_url) ?>">Se resultater i V2 (midlertidig)</a>
        </p>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php foreach ($results_by_class as $group): ?>
    <?php if (!is_array($group)) { continue; } ?>
    <section class="card">
        <h2><?= $h((string) ($group['label'] ?? 'Klasse')) ?></h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Plass</th>
                    <th>Navn</th>
                    <th>Klubb</th>
                    <th>Resultat</th>
                    <th>Skille</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($group['rows'] ?? []) as $row): ?>
                    <?php if (!is_array($row)) { continue; } ?>
                    <tr>
                        <td><?= $row['placement'] !== null ? $h((string) $row['placement']) : '–' ?></td>
                        <td><?= $h((string) ($row['display_name'] ?? '')) ?></td>
                        <td><?= $h((string) ($row['organization_name'] ?? '–')) ?></td>
                        <td><?= $row['total_score'] !== null ? $h((string) $row['total_score']) : '–' ?></td>
                        <td><?= $row['tiebreak_score'] !== null ? $h((string) $row['tiebreak_score']) : '–' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
<?php endforeach; ?>
