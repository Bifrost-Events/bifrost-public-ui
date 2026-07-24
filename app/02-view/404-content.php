<?php

declare(strict_types=1);

/** @var string $title */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<section class="card">
    <?php if (empty($hide_page_title)): ?>
        <h1><?= $h($title) ?></h1>
    <?php endif; ?>
    <p class="muted">Siden finnes ikke.</p>
    <p><a href="/">Tilbake til forsiden</a></p>
</section>
