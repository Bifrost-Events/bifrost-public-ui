<?php

declare(strict_types=1);

/** @var array<string, mixed> $series */
/** @var array<string, mixed>|null $parent */
/** @var list<array<string, mixed>> $children */
/** @var list<array<string, mixed>> $events */
/** @var list<array<string, mixed>> $breadcrumb */
/** @var array<string, mixed>|null $space */
/** @var array<string, array{singular: string, plural: string}> $labels */
/** @var string|null $standings_url */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$seriesLabel = (string) ($labels['series']['singular'] ?? 'Serie');
$subseriesLabel = (string) ($labels['subseries']['singular'] ?? 'Underserie');
$subseriesPlural = (string) ($labels['subseries']['plural'] ?? 'Underserier');
$eventPlural = (string) ($labels['event']['plural'] ?? 'Arrangementer');
$eventSingular = (string) ($labels['event']['singular'] ?? 'Arrangement');
$spaceLabel = (string) ($labels['event_space']['singular'] ?? 'Event Space');

$isSubseries = ($series['parent_series_id'] ?? null) !== null;
$pageKindLabel = $isSubseries ? $subseriesLabel : $seriesLabel;
?>
<section class="card">
    <nav class="breadcrumb muted" aria-label="Brødsmule">
        <a href="/calendar"><?= $h($eventPlural) ?></a>
        <?php foreach ($breadcrumb as $crumb): ?>
            <?php if (!is_array($crumb)) { continue; } ?>
            <?php
            $crumbId = (int) ($crumb['id'] ?? $crumb['series_id'] ?? 0);
            $isLast = $crumbId === (int) ($series['id'] ?? $series['series_id'] ?? 0);
            ?>
            <span aria-hidden="true"> → </span>
            <?php if ($isLast || $crumbId <= 0): ?>
                <span><?= $h((string) ($crumb['name'] ?? '')) ?></span>
            <?php else: ?>
                <a href="/serier/<?= $crumbId ?>"><?= $h((string) ($crumb['name'] ?? '')) ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <p class="muted"><?= $h($pageKindLabel) ?></p>
    <?php if (empty($hide_page_title)): ?>
        <h1><?= $h((string) ($series['name'] ?? '')) ?></h1>
    <?php endif; ?>

    <?php if (!empty($series['season_label'])): ?>
        <p class="muted"><?= $h($seriesLabel) ?>/periode: <?= $h((string) $series['season_label']) ?></p>
    <?php endif; ?>

    <?php if (!empty($series['starts_at']) || !empty($series['ends_at'])): ?>
        <p class="muted">
            <?php if (!empty($series['starts_at'])): ?>Fra <?= $h((string) $series['starts_at']) ?><?php endif; ?>
            <?php if (!empty($series['ends_at'])): ?> til <?= $h((string) $series['ends_at']) ?><?php endif; ?>
        </p>
    <?php endif; ?>

    <?php if (is_array($space) && ($space['name'] ?? '') !== ''): ?>
        <p class="muted"><?= $h($spaceLabel) ?>: <?= $h((string) $space['name']) ?></p>
    <?php endif; ?>

    <?php
    $owner = is_array($series['owner_organization'] ?? null) ? $series['owner_organization'] : null;
    if ($owner !== null && ($owner['name'] ?? '') !== ''):
    ?>
        <p class="muted">Arrangør: <?= $h((string) $owner['name']) ?></p>
    <?php endif; ?>

    <?php if (!empty($series['description'])): ?>
        <div class="prose"><?= nl2br($h((string) $series['description'])) ?></div>
    <?php endif; ?>

    <?php
    $standingsUrl = is_string($standings_url ?? null) ? $standings_url : ('/serier/' . (int) ($series['id'] ?? $series['series_id'] ?? 0) . '/sammenlagt');
    ?>
    <p><a class="button" href="<?= $h($standingsUrl) ?>">Sammenlagt</a></p>

    <?php if ($parent !== null): ?>
        <h2><?= $h($seriesLabel) ?></h2>
        <p><a href="/serier/<?= (int) ($parent['id'] ?? $parent['series_id'] ?? 0) ?>"><?= $h((string) ($parent['name'] ?? '')) ?></a></p>
    <?php endif; ?>

    <h2><?= $h($subseriesPlural) ?></h2>
    <?php if ($children === []): ?>
        <p class="muted">Ingen <?= $h(mb_strtolower($subseriesPlural)) ?>.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($children as $child): ?>
                <?php if (!is_array($child)) { continue; } ?>
                <li>
                    <a href="/serier/<?= (int) ($child['id'] ?? $child['series_id'] ?? 0) ?>">
                        <?= $h((string) ($child['name'] ?? '')) ?>
                    </a>
                    <?php if ((int) ($child['event_count'] ?? 0) > 0): ?>
                        <span class="muted">(<?= (int) $child['event_count'] ?> <?= $h(mb_strtolower($eventPlural)) ?>)</span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h2><?= $h($eventPlural) ?></h2>
    <?php if ($events === []): ?>
        <p class="muted">Ingen <?= $h(mb_strtolower($eventPlural)) ?> i denne <?= $h(mb_strtolower($pageKindLabel)) ?>.</p>
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
                <?php foreach ($events as $ev): ?>
                    <?php if (!is_array($ev)) { continue; } ?>
                    <tr>
                        <td><?= $h((string) ($ev['starts_at'] ?? '')) ?></td>
                        <td>
                            <a href="/arrangementer/<?= (int) ($ev['id'] ?? $ev['event_id'] ?? 0) ?>">
                                <?= $h((string) ($ev['name'] ?? '')) ?>
                            </a>
                        </td>
                        <td><?= $h((string) ($ev['location_name'] ?? '')) ?></td>
                        <td><?= $h((string) ($ev['organizer_name'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
