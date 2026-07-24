<?php

declare(strict_types=1);

/** @var array<string, mixed> $competition */
/** @var list<array<string, mixed>> $results */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$byClass = [];
foreach ($results as $row) {
    if (!is_array($row)) {
        continue;
    }
    $label = trim((string) ($row['class'] ?? 'Uten klasse'));
    if ($label === '') {
        $label = 'Uten klasse';
    }
    $byClass[$label][] = $row;
}
?>
<section class="card">
    <p><a href="/results">← Tilbake til resultater</a></p>
    <?php if (empty($hide_page_title)): ?>
        <h1><?= $h((string) ($competition['name'] ?? 'Stevne')) ?></h1>
    <?php endif; ?>
    <p class="muted">
        <?= $h((string) ($competition['competition_date'] ?? '')) ?>
        <?php if (!empty($competition['location'])): ?>
            · <?= $h((string) $competition['location']) ?>
        <?php endif; ?>
    </p>
</section>

<?php if ($byClass === []): ?>
<section class="card"><p class="muted">Ingen resultater å vise.</p></section>
<?php endif; ?>

<?php foreach ($byClass as $classLabel => $rows): ?>
<section class="card">
    <h2><?= $h($classLabel) ?></h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Plass</th>
                <th>Navn</th>
                <th>Poeng</th>
                <th>Lag</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= $row['place'] !== null ? $h((string) $row['place']) : '–' ?></td>
                    <td><?= $h((string) ($row['name'] ?? '')) ?></td>
                    <td><?= $row['score'] !== null ? $h((string) $row['score']) : '–' ?></td>
                    <td><?= $row['slot_number'] !== null ? $h((string) $row['slot_number']) : '–' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php endforeach; ?>
