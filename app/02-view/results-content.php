<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $competitions */
/** @var string|null $error */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<section class="card">
    <h1>Resultater</h1>
    <?php if ($error): ?>
        <p class="status-bad"><?= $h($error) ?></p>
    <?php elseif ($competitions === []): ?>
        <p class="muted">Ingen publiserte resultater ennå.</p>
    <?php else: ?>
        <ul class="link-list">
            <?php foreach ($competitions as $comp): ?>
                <?php if (!is_array($comp)) { continue; } ?>
                <li>
                    <a href="/results/<?= (int) ($comp['id'] ?? 0) ?>">
                        <strong><?= $h((string) ($comp['name'] ?? '')) ?></strong>
                        <span class="muted"><?= $h((string) ($comp['competition_date'] ?? '')) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
