<?php

declare(strict_types=1);

/** @var string $error */
/** @var string $return_to */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<section class="card">
    <h1>Logg inn</h1>
    <?php include __DIR__ . '/auth-login-fragment.php'; ?>
</section>
