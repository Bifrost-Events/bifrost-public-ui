<?php

declare(strict_types=1);

/** @var array<string, mixed> $series */
/** @var array<string, array{singular: string, plural: string}> $labels */
/** @var string $standings_mode */
/** @var int $count_best */
/** @var list<array<string, mixed>> $class_groups */
/** @var bool $has_standings */
/** @var int|null $resolved_from_series_id */
/** @var string $series_url */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$seriesLabel = (string) ($labels['series']['singular'] ?? 'Serie');
$eventPlural = (string) ($labels['event']['plural'] ?? 'Arrangementer');
?>
<section class="card">
    <p><a href="<?= $h($series_url) ?>">← Tilbake til <?= $h(mb_strtolower($seriesLabel)) ?></a></p>
    <?php if (empty($hide_page_title)): ?>
        <h1>Sammenlagt</h1>
    <?php endif; ?>
    <p class="muted"><?= $h((string) ($series['name'] ?? '')) ?></p>
    <?php if ($resolved_from_series_id): ?>
        <p class="muted">Viser sammenlagt for toppserien (forespurt underserie #<?= (int) $resolved_from_series_id ?>).</p>
    <?php endif; ?>
    <p class="muted">
        Modus: <?= $h($standings_mode) ?>
        <?php if ($count_best > 0): ?>
            · Beste <?= (int) $count_best ?> <?= $h(mb_strtolower($eventPlural)) ?>
        <?php else: ?>
            · Alle tellende <?= $h(mb_strtolower($eventPlural)) ?>
        <?php endif; ?>
    </p>
</section>

<?php if (!$has_standings): ?>
<section class="card"><p class="muted">Ingen sammenlagt å vise ennå.</p></section>
<?php endif; ?>

<?php foreach ($class_groups as $group): ?>
    <?php if (!is_array($group)) { continue; } ?>
    <section class="card">
        <h2><?= $h((string) ($group['label'] ?? 'Klasse')) ?></h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Plass</th>
                    <th>Navn</th>
                    <th>Klubb</th>
                    <th>Poeng</th>
                    <th><?= $h($eventPlural) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($group['rows'] ?? []) as $row): ?>
                    <?php if (!is_array($row)) { continue; } ?>
                    <tr>
                        <td><?= $h((string) ($row['place'] ?? $row['placement'] ?? '')) ?></td>
                        <td><?= $h((string) ($row['display_name'] ?? $row['name'] ?? '')) ?></td>
                        <td><?= $h((string) ($row['organization_name'] ?? '–')) ?></td>
                        <td><?= $h((string) ($row['total_score'] ?? '')) ?></td>
                        <td><?= $h((string) ($row['events_count'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
<?php endforeach; ?>
