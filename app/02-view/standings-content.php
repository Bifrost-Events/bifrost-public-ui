<?php

declare(strict_types=1);

/** @var array<string, mixed>|null $season */
/** @var array<string, mixed>|null $standings */
/** @var string|null $error */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$classGroups = is_array($standings['class_groups'] ?? null) ? $standings['class_groups'] : [];
?>
<section class="card">
    <?php if (empty($hide_page_title)): ?>
        <h1>Sammenlagt</h1>
    <?php endif; ?>
    <?php if ($season !== null): ?>
        <p class="muted"><?= $h((string) ($season['name'] ?? '')) ?> (<?= $h((string) ($season['year'] ?? '')) ?>)</p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="status-bad"><?= $h($error) ?></p>
    <?php elseif ($classGroups === []): ?>
        <p class="muted">Ingen sammenlagt å vise ennå.</p>
    <?php endif; ?>
</section>

<?php foreach ($classGroups as $group): ?>
    <?php if (!is_array($group)) { continue; } ?>
    <section class="card">
        <h2><?= $h((string) ($group['label'] ?? 'Klasse')) ?></h2>
        <table class="data-table">
            <thead>
                <tr><th>Plass</th><th>Navn</th><th>Poeng</th><th>Stevner</th></tr>
            </thead>
            <tbody>
                <?php foreach (($group['rows'] ?? []) as $row): ?>
                    <?php if (!is_array($row)) { continue; } ?>
                    <tr>
                        <td><?= $h((string) ($row['place'] ?? '')) ?></td>
                        <td><?= $h((string) ($row['name'] ?? '')) ?></td>
                        <td><?= $h((string) ($row['total_score'] ?? '')) ?></td>
                        <td><?= $h((string) ($row['events_count'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
<?php endforeach; ?>
