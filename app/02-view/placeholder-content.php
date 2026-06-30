<?php

declare(strict_types=1);

/** @var string $page_title */
/** @var string $page_description */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<section class="card">
    <h1><?= $h($page_title) ?></h1>
    <p class="muted"><?= $h($page_description) ?></p>
</section>
